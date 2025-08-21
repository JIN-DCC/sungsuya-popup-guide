<?php
/**
 * 성수야! V2 - Data Migration Manager
 * 
 * 기존 popup_store 데이터를 places로 마이그레이션
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

class DataMigrationManager {
    
    private static $migration_option_key = 'sungsuya_migration_status';
    
    /**
     * 마이그레이션 상태 확인
     */
    public static function get_migration_status() {
        return get_option(self::$migration_option_key, array(
            'completed' => false,
            'popup_stores_migrated' => 0,
            'total_popup_stores' => 0,
            'started_at' => null,
            'completed_at' => null,
            'errors' => array()
        ));
    }
    
    /**
     * 마이그레이션 상태 업데이트
     */
    public static function update_migration_status($data) {
        $current = self::get_migration_status();
        $updated = array_merge($current, $data);
        update_option(self::$migration_option_key, $updated);
        return $updated;
    }
    
    /**
     * 기존 popup_store 데이터를 places로 마이그레이션
     */
    public static function migrate_popup_stores() {
        // 마이그레이션 시작 기록
        self::update_migration_status(array(
            'started_at' => current_time('mysql'),
            'completed' => false
        ));
        
        // 기존 팝업스토어 조회
        $popup_stores = get_posts(array(
            'post_type' => 'popup_store',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'draft', 'pending', 'private')
        ));
        
        $total_count = count($popup_stores);
        $migrated_count = 0;
        $errors = array();
        
        self::update_migration_status(array(
            'total_popup_stores' => $total_count
        ));
        
        foreach ($popup_stores as $store) {
            try {
                $result = self::migrate_single_popup_store($store);
                if ($result) {
                    $migrated_count++;
                } else {
                    $errors[] = "Failed to migrate store: {$store->post_title} (ID: {$store->ID})";
                }
            } catch (Exception $e) {
                $errors[] = "Error migrating store {$store->post_title} (ID: {$store->ID}): " . $e->getMessage();
            }
            
            // 진행 상황 업데이트
            self::update_migration_status(array(
                'popup_stores_migrated' => $migrated_count,
                'errors' => $errors
            ));
        }
        
        // 마이그레이션 완료 기록
        self::update_migration_status(array(
            'completed' => true,
            'completed_at' => current_time('mysql'),
            'popup_stores_migrated' => $migrated_count
        ));
        
        return array(
            'success' => true,
            'migrated' => $migrated_count,
            'total' => $total_count,
            'errors' => $errors
        );
    }
    
    /**
     * 개별 팝업스토어 마이그레이션
     */
    private static function migrate_single_popup_store($store) {
        // 새로운 places 포스트 생성
        $new_place_data = array(
            'post_title' => $store->post_title,
            'post_content' => $store->post_content,
            'post_excerpt' => $store->post_excerpt,
            'post_status' => $store->post_status,
            'post_type' => 'places',
            'post_author' => $store->post_author,
            'post_date' => $store->post_date,
            'post_date_gmt' => $store->post_date_gmt,
            'post_modified' => $store->post_modified,
            'post_modified_gmt' => $store->post_modified_gmt,
            'comment_status' => $store->comment_status,
            'ping_status' => $store->ping_status,
            'menu_order' => $store->menu_order
        );
        
        $new_place_id = wp_insert_post($new_place_data);
        
        if (is_wp_error($new_place_id)) {
            return false;
        }
        
        // 장소 유형을 popup_store로 설정
        wp_set_object_terms($new_place_id, 'popup_store', 'place_type');
        
        // 기존 카테고리를 place_category로 마이그레이션
        $store_categories = wp_get_post_terms($store->ID, 'store_category');
        if (!empty($store_categories)) {
            $category_names = array_map(function($term) {
                return $term->name;
            }, $store_categories);
            wp_set_object_terms($new_place_id, $category_names, 'place_category');
        }
        
        // 메타 데이터 복사 및 변환
        $meta_data = get_post_meta($store->ID);
        $meta_mapping = self::get_meta_field_mapping();
        
        foreach ($meta_data as $key => $values) {
            $value = is_array($values) ? $values[0] : $values;
            
            // 메타 키 매핑 적용
            $new_key = isset($meta_mapping[$key]) ? $meta_mapping[$key] : $key;
            
            // 빈 값이 아닌 경우에만 저장
            if ($value !== '' && $value !== null) {
                update_post_meta($new_place_id, $new_key, $value);
            }
        }
        
        // 팝업스토어 특화 필드 추가
        if (!get_post_meta($new_place_id, 'operating_status', true)) {
            update_post_meta($new_place_id, 'operating_status', 'open');
        }
        
        // 썸네일 복사
        $thumbnail_id = get_post_thumbnail_id($store->ID);
        if ($thumbnail_id) {
            set_post_thumbnail($new_place_id, $thumbnail_id);
        }
        
        // 원본 팝업스토어 ID 기록 (참조용)
        update_post_meta($new_place_id, '_migrated_from_popup_store', $store->ID);
        update_post_meta($new_place_id, '_migration_date', current_time('mysql'));
        
        return $new_place_id;
    }
    
    /**
     * 메타 필드 매핑 규칙
     */
    private static function get_meta_field_mapping() {
        return array(
            // 기존 키 => 새로운 키
            '_address' => 'address',
            '_latitude' => 'latitude',
            '_longitude' => 'longitude',
            '_phone' => 'phone',
            '_website' => 'website',
            '_instagram' => 'instagram',
            '_opening_hours' => 'opening_hours',
            '_start_date' => 'operation_start',
            '_end_date' => 'operation_end',
            '_brand' => 'brand_name',
            '_category' => 'popup_category',
            '_featured' => 'featured',
            '_nearest_subway' => 'nearest_subway',
            '_subway_distance' => 'subway_distance'
        );
    }
    
    /**
     * URL 리다이렉트 설정 (SEO 보존)
     */
    public static function setup_url_redirects() {
        add_action('template_redirect', function() {
            // /store/slug 형태의 URL을 /place/slug로 리다이렉트
            if (is_404() && strpos($_SERVER['REQUEST_URI'], '/store/') === 0) {
                $slug = str_replace('/store/', '', rtrim($_SERVER['REQUEST_URI'], '/'));
                
                // 해당 slug로 places 포스트 검색
                $place = get_posts(array(
                    'name' => $slug,
                    'post_type' => 'places',
                    'numberposts' => 1
                ));
                
                if ($place) {
                    wp_redirect(get_permalink($place[0]), 301);
                    exit;
                }
            }
        });
    }
    
    /**
     * 마이그레이션 롤백 (문제 발생 시)
     */
    public static function rollback_migration() {
        // 마이그레이션된 places 포스트 삭제
        $migrated_places = get_posts(array(
            'post_type' => 'places',
            'meta_key' => '_migrated_from_popup_store',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        $deleted_count = 0;
        
        foreach ($migrated_places as $place) {
            wp_delete_post($place->ID, true); // 완전 삭제
            $deleted_count++;
        }
        
        // 마이그레이션 상태 초기화
        delete_option(self::$migration_option_key);
        
        return array(
            'success' => true,
            'deleted' => $deleted_count
        );
    }
    
    /**
     * 마이그레이션 통계
     */
    public static function get_migration_stats() {
        $status = self::get_migration_status();
        
        // 추가 통계 계산
        $popup_store_count = wp_count_posts('popup_store');
        $places_count = wp_count_posts('places');
        
        return array(
            'status' => $status,
            'current_counts' => array(
                'popup_stores' => $popup_store_count->publish + $popup_store_count->draft,
                'places' => $places_count->publish + $places_count->draft
            )
        );
    }
}

/**
 * 관리자 페이지에 마이그레이션 도구 추가
 */
function sungsuya_add_migration_admin_page() {
    add_submenu_page(
        'edit.php?post_type=places',
        '데이터 마이그레이션',
        '데이터 마이그레이션',
        'manage_options',
        'sungsuya-migration',
        'sungsuya_migration_admin_page'
    );
}
add_action('admin_menu', 'sungsuya_add_migration_admin_page');

/**
 * 마이그레이션 관리자 페이지
 */
function sungsuya_migration_admin_page() {
    $status = DataMigrationManager::get_migration_status();
    
    // AJAX 액션 처리
    if (isset($_POST['action'])) {
        check_admin_referer('sungsuya_migration');
        
        switch ($_POST['action']) {
            case 'migrate':
                $result = DataMigrationManager::migrate_popup_stores();
                echo '<div class="notice notice-success"><p>마이그레이션 완료: ' . $result['migrated'] . '/' . $result['total'] . ' 개</p></div>';
                break;
                
            case 'rollback':
                $result = DataMigrationManager::rollback_migration();
                echo '<div class="notice notice-success"><p>롤백 완료: ' . $result['deleted'] . '개 삭제</p></div>';
                break;
        }
        
        $status = DataMigrationManager::get_migration_status();
    }
    
    ?>
    <div class="wrap">
        <h1>팝업스토어 → Places 데이터 마이그레이션</h1>
        
        <div class="card">
            <h2>마이그레이션 상태</h2>
            <table class="form-table">
                <tr>
                    <th>완료 여부</th>
                    <td><?php echo $status['completed'] ? '✅ 완료' : '❌ 미완료'; ?></td>
                </tr>
                <tr>
                    <th>마이그레이션된 팝업스토어</th>
                    <td><?php echo $status['popup_stores_migrated']; ?> / <?php echo $status['total_popup_stores']; ?></td>
                </tr>
                <?php if ($status['started_at']): ?>
                <tr>
                    <th>시작 시간</th>
                    <td><?php echo $status['started_at']; ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($status['completed_at']): ?>
                <tr>
                    <th>완료 시간</th>
                    <td><?php echo $status['completed_at']; ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <?php if (!empty($status['errors'])): ?>
        <div class="card">
            <h2>오류 로그</h2>
            <div style="background: #ffecec; padding: 10px; border-left: 4px solid #dc3232;">
                <?php foreach ($status['errors'] as $error): ?>
                    <p><?php echo esc_html($error); ?></p>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>액션</h2>
            
            <?php if (!$status['completed']): ?>
            <form method="post" onsubmit="return confirm('팝업스토어 데이터를 Places로 마이그레이션하시겠습니까?');">
                <?php wp_nonce_field('sungsuya_migration'); ?>
                <input type="hidden" name="action" value="migrate">
                <p class="submit">
                    <input type="submit" class="button-primary" value="마이그레이션 시작">
                </p>
            </form>
            <?php else: ?>
            <p>✅ 마이그레이션이 완료되었습니다.</p>
            
            <form method="post" onsubmit="return confirm('정말로 마이그레이션을 롤백하시겠습니까? 모든 Places 데이터가 삭제됩니다.');">
                <?php wp_nonce_field('sungsuya_migration'); ?>
                <input type="hidden" name="action" value="rollback">
                <p class="submit">
                    <input type="submit" class="button-secondary" value="마이그레이션 롤백 (위험)">
                </p>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// 마이그레이션 완료 후 URL 리다이렉트 설정
DataMigrationManager::setup_url_redirects();
