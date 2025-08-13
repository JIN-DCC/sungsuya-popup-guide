<?php
/**
 * 업종별 상세정보 자동수집 관리자 페이지
 * 
 * @package SungsuyaV2
 * @version 1.0.0
 * @since 2025-01-02
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 장소 상세정보 자동수집 관리자 클래스
 */
class Place_Detail_Auto_Collector_Admin {
    
    /**
     * 생성자
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * 관리자 메뉴 추가
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=places',
            '업종별 상세정보 자동수집',
            '🤖 상세정보 자동수집',
            'edit_posts',
            'place-detail-auto-collector',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * 관리자 스크립트 및 스타일 등록
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'places_page_place-detail-auto-collector') {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'place-detail-collector-admin',
            get_template_directory_uri() . '/assets/css/place-detail-collector-admin.css',
            array(),
            '1.0.0'
        );
        
        // JavaScript
        wp_enqueue_script(
            'place-detail-collector-admin',
            get_template_directory_uri() . '/assets/js/place-detail-collector-admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // AJAX 설정
        wp_localize_script('place-detail-collector-admin', 'placeDetailCollector', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('collect_place_details'),
            'testNonce' => wp_create_nonce('test_api_availability'),
            'historyNonce' => wp_create_nonce('get_collection_history'),
            'messages' => array(
                'collecting' => '상세정보를 수집 중입니다...',
                'testing' => 'API 연결을 테스트 중입니다...',
                'error' => '오류가 발생했습니다. 다시 시도해주세요.',
                'noData' => '수집된 정보가 없습니다.',
                'success' => '작업이 완료되었습니다.'
            )
        ));
    }
    
    /**
     * 관리자 페이지 렌더링
     */
    public function render_admin_page() {
        // 권한 확인
        if (!current_user_can('edit_posts')) {
            wp_die(__('권한이 없습니다.'));
        }
        
        // 최근 수집 이력
        $recent_collections = $this->get_recent_collections();
        
        // 통계 데이터
        $stats = $this->get_collection_stats();
        ?>
        
        <div class="wrap place-detail-collector-admin">
            <h1>
                <span class="dashicons dashicons-database-import"></span>
                업종별 상세정보 자동수집
            </h1>
            
            <div class="notice notice-info">
                <p>
                    <strong>자동수집 시스템</strong>: 
                    네이버, 카카오, 구글 API를 통해 업종별 상세정보를 자동으로 수집합니다.
                    영업시간, 메뉴, 가격대, 서비스 정보 등을 한 번에 수집할 수 있습니다.
                </p>
            </div>
            
            <!-- API 상태 확인 -->
            <div class="api-status-section">
                <h2>API 연결 상태</h2>
                <div class="api-status-grid">
                    <div class="api-status-box" id="naver-status">
                        <h3>네이버 API</h3>
                        <div class="status-indicator">
                            <span class="dashicons dashicons-update spinning"></span>
                            <span class="status-text">확인 중...</span>
                        </div>
                    </div>
                    <div class="api-status-box" id="kakao-status">
                        <h3>카카오 API</h3>
                        <div class="status-indicator">
                            <span class="dashicons dashicons-update spinning"></span>
                            <span class="status-text">확인 중...</span>
                        </div>
                    </div>
                    <div class="api-status-box" id="google-status">
                        <h3>구글 API</h3>
                        <div class="status-indicator">
                            <span class="dashicons dashicons-update spinning"></span>
                            <span class="status-text">확인 중...</span>
                        </div>
                    </div>
                </div>
                <button class="button" id="test-api-connection">
                    <span class="dashicons dashicons-update"></span>
                    API 연결 재확인
                </button>
            </div>
            
            <!-- 통계 대시보드 -->
            <div class="stats-dashboard">
                <h2>수집 통계</h2>
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo number_format($stats['total_collected']); ?></div>
                        <div class="stat-label">전체 수집 장소</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo number_format($stats['fields_collected']); ?></div>
                        <div class="stat-label">수집된 필드</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $stats['avg_fields']; ?>개</div>
                        <div class="stat-label">평균 필드 수</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $stats['success_rate']; ?>%</div>
                        <div class="stat-label">수집 성공률</div>
                    </div>
                </div>
            </div>
            
            <!-- 개별 수집 섹션 -->
            <div class="collection-section">
                <h2>개별 장소 상세정보 수집</h2>
                <p class="description">각 장소별로 상세정보를 수집합니다. 테이블에서 원하는 장소의 [수집] 버튼을 클릭하세요.</p>
                
                <div class="collection-options">
                    <label>
                        <input type="checkbox" id="overwrite-existing" value="1">
                        기존 데이터 덮어쓰기 (체크하지 않으면 빈 필드만 채움)
                    </label>
                    
                    <div class="source-selection">
                        <strong>수집 소스:</strong>
                        <label>
                            <input type="checkbox" name="sources[]" value="naver" checked> 네이버
                        </label>
                        <label>
                            <input type="checkbox" name="sources[]" value="kakao" checked> 카카오
                        </label>
                        <label>
                            <input type="checkbox" name="sources[]" value="google" checked> 구글
                        </label>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>장소명</th>
                            <th>업종</th>
                            <th>주소</th>
                            <th>수집 필드</th>
                            <th>마지막 수집</th>
                            <th style="width: 200px;">작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $places = get_posts(array(
                            'post_type' => 'places',
                            'posts_per_page' => 20,
                            'orderby' => 'modified',
                            'order' => 'DESC'
                        ));
                        
                        foreach ($places as $place) :
                            $place_type = $this->get_place_type_label($place->ID);
                            $collected_fields = $this->get_collected_fields_count($place->ID);
                            $last_collected = get_post_meta($place->ID, '_auto_collected_details_cache', true);
                        ?>
                        <tr data-post-id="<?php echo $place->ID; ?>">
                            <td><?php echo $place->ID; ?></td>
                            <td>
                                <strong>
                                    <a href="<?php echo get_edit_post_link($place->ID); ?>">
                                        <?php echo esc_html($place->post_title); ?>
                                    </a>
                                </strong>
                            </td>
                            <td><?php echo esc_html($place_type); ?></td>
                            <td><?php echo esc_html(get_post_meta($place->ID, 'address', true)); ?></td>
                            <td>
                                <span class="field-count">
                                    <?php echo $collected_fields; ?>개
                                </span>
                            </td>
                            <td>
                                <?php 
                                if (!empty($last_collected['timestamp'])) {
                                    echo human_time_diff($last_collected['timestamp'], current_time('timestamp')) . ' 전';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <button class="button button-primary collect-single" 
                                        data-post-id="<?php echo $place->ID; ?>">
                                    <span class="dashicons dashicons-database-import"></span>
                                    수집
                                </button>
                                <button class="button view-history" 
                                        data-post-id="<?php echo $place->ID; ?>">
                                    <span class="dashicons dashicons-backup"></span>
                                    이력
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 일괄 수집 섹션 -->
            <div class="bulk-collection-section">
                <h2>일괄 상세정보 수집</h2>
                
                <div class="bulk-options">
                    <label>
                        업종 선택:
                        <select id="bulk-place-type">
                            <option value="">전체</option>
                            <option value="food">음식점 (카페, 레스토랑)</option>
                            <option value="shop">일반업종 (매장, 편집샵)</option>
                        </select>
                    </label>
                    
                    <label>
                        수집 대상:
                        <select id="bulk-target">
                            <option value="empty">빈 필드가 있는 장소</option>
                            <option value="never">한번도 수집하지 않은 장소</option>
                            <option value="old">30일 이상 경과한 장소</option>
                            <option value="all">모든 장소 (주의!)</option>
                        </select>
                    </label>
                </div>
                
                <div class="bulk-actions">
                    <button class="button button-primary" id="bulk-collect-filtered">
                        <span class="dashicons dashicons-database-import"></span>
                        선택한 조건으로 일괄 수집 시작
                    </button>
                    
                    <p class="description">
                        위의 조건에 맞는 장소들의 상세정보를 자동으로 수집합니다.<br>
                        예: "음식점" + "빈 필드가 있는 장소" = 정보가 부족한 음식점만 수집
                    </p>
                </div>
                
                <div class="bulk-progress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="progress-text">0 / 0</div>
                </div>
            </div>
            
            <!-- 최근 수집 결과 -->
            <div class="recent-collections-section">
                <h2>최근 수집 결과</h2>
                
                <?php if (!empty($recent_collections)) : ?>
                <div class="recent-collections-list">
                    <?php foreach ($recent_collections as $collection) : ?>
                    <div class="collection-item">
                        <div class="collection-header">
                            <h4><?php echo esc_html($collection['place_name']); ?></h4>
                            <span class="collection-time">
                                <?php echo human_time_diff(strtotime($collection['timestamp']), current_time('timestamp')); ?> 전
                            </span>
                        </div>
                        <div class="collection-details">
                            <div class="collected-fields">
                                <strong>수집된 필드:</strong>
                                <?php 
                                $fields = array_slice($collection['fields'], 0, 5);
                                echo implode(', ', array_map('esc_html', $fields));
                                if (count($collection['fields']) > 5) {
                                    echo ' 외 ' . (count($collection['fields']) - 5) . '개';
                                }
                                ?>
                            </div>
                            <div class="collection-sources">
                                <strong>수집 소스:</strong>
                                <?php echo implode(', ', array_map('ucfirst', $collection['sources'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <p>아직 수집된 정보가 없습니다.</p>
                <?php endif; ?>
            </div>
            
            <!-- 필드 매핑 가이드 -->
            <div class="field-mapping-guide">
                <h2>업종별 수집 필드 가이드</h2>
                
                <div class="mapping-grid">
                    <div class="mapping-box">
                        <h3>🍽️ 음식점 (카페, 레스토랑)</h3>
                        <ul>
                            <li>대표메뉴 및 가격</li>
                            <li>영업시간, 브레이크타임</li>
                            <li>라스트오더 시간</li>
                            <li>예약 가능 여부</li>
                            <li>배달/테이크아웃</li>
                            <li>주차 정보</li>
                            <li>와이파이, 콘센트</li>
                            <li>애완동물 동반</li>
                        </ul>
                    </div>
                    
                    <div class="mapping-box">
                        <h3>🛍️ 일반업종 (매장, 편집샵)</h3>
                        <ul>
                            <li>취급 브랜드</li>
                            <li>상품 종류</li>
                            <li>가격대</li>
                            <li>영업시간</li>
                            <li>특별 서비스</li>
                            <li>결제 방법</li>
                            <li>온라인샵 URL</li>
                            <li>주차 가능 여부</li>
                        </ul>
                    </div>
                    
                    <div class="mapping-box">
                        <h3>☕ 카페 특화</h3>
                        <ul>
                            <li>시그니처 메뉴</li>
                            <li>커피 원두 정보</li>
                            <li>로스팅 정보</li>
                            <li>디카페인 메뉴</li>
                            <li>디저트 메뉴</li>
                            <li>스터디 친화도</li>
                            <li>소음 레벨</li>
                        </ul>
                    </div>
                    
                    <div class="mapping-box">
                        <h3>🍷 레스토랑 특화</h3>
                        <ul>
                            <li>상세 요리 종류</li>
                            <li>시그니처 요리</li>
                            <li>코스 메뉴</li>
                            <li>채식 옵션</li>
                            <li>주류 판매</li>
                            <li>프라이빗 룸</li>
                            <li>단체석 가능</li>
                            <li>콜키지 정책</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 수집 이력 모달 -->
        <div id="collection-history-modal" class="collection-modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>수집 이력</h2>
                <div class="modal-body"></div>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * 장소 유형 레이블 가져오기
     */
    private function get_place_type_label($post_id) {
        $terms = wp_get_post_terms($post_id, 'place_type');
        
        if (!empty($terms) && !is_wp_error($terms)) {
            $term = $terms[0];
            $metafield_type = get_term_meta($term->term_id, 'metafield_type', true);
            
            if ($metafield_type === 'food') {
                return '🍽️ 음식점';
            } elseif ($metafield_type === 'shop') {
                return '🛍️ 일반업종';
            }
            
            return $term->name;
        }
        
        return '기타';
    }
    
    /**
     * 수집된 필드 수 가져오기
     */
    private function get_collected_fields_count($post_id) {
        $count = 0;
        
        // PlaceMetaFields 클래스 로드 시도
        if (!class_exists('PlaceMetaFields')) {
            $place_meta_fields_file = get_template_directory() . '/inc/places/place-meta-fields.php';
            if (file_exists($place_meta_fields_file)) {
                require_once $place_meta_fields_file;
            }
        }
        
        // 업종별 필드 목록 가져오기
        $place_type = $this->get_place_metafield_type($post_id);
        
        if (class_exists('PlaceMetaFields')) {
            $fields = PlaceMetaFields::get_fields_for_type($place_type);
        } else {
            // PlaceMetaFields가 없는 경우 기본 필드 사용
            $fields = array(
                'address' => array('label' => '주소'),
                'phone' => array('label' => '전화번호'),
                'opening_hours' => array('label' => '영업시간'),
                'website' => array('label' => '웹사이트'),
                'price_range' => array('label' => '가격대'),
                'menu_items' => array('label' => '메뉴'),
                'parking' => array('label' => '주차')
            );
        }
        
        // 실제로 값이 있는 필드 수 계산
        foreach ($fields as $field_name => $field_info) {
            $value = get_post_meta($post_id, $field_name, true);
            if (!empty($value)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * 장소의 메타필드 타입 가져오기
     */
    private function get_place_metafield_type($post_id) {
        $terms = wp_get_post_terms($post_id, 'place_type');
        
        if (!empty($terms) && !is_wp_error($terms)) {
            $term = $terms[0];
            $metafield_type = get_term_meta($term->term_id, 'metafield_type', true);
            
            if (!empty($metafield_type)) {
                return $metafield_type;
            }
            
            // 메타필드 타입이 없으면 slug로 판단
            if (in_array($term->slug, array('cafe', 'restaurant', 'bar'))) {
                return 'food';
            }
            
            if (in_array($term->slug, array('shop', 'retail-store', 'popup-store'))) {
                return 'shop';
            }
        }
        
        return 'general';
    }
    
    /**
     * 최근 수집 이력 가져오기
     */
    private function get_recent_collections() {
        global $wpdb;
        
        $results = $wpdb->get_results(
            "SELECT p.ID, p.post_title, pm.meta_value as history
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'places' 
             AND pm.meta_key = '_auto_collection_history'
             ORDER BY p.post_modified DESC
             LIMIT 10"
        );
        
        $collections = array();
        
        foreach ($results as $result) {
            $history = maybe_unserialize($result->history);
            if (is_array($history) && !empty($history)) {
                $latest = end($history);
                $collections[] = array(
                    'place_name' => $result->post_title,
                    'timestamp' => $latest['timestamp'],
                    'fields' => $latest['fields_collected'],
                    'sources' => array('naver', 'kakao', 'google') // 임시
                );
            }
        }
        
        return $collections;
    }
    
    /**
     * 수집 통계 가져오기
     */
    private function get_collection_stats() {
        global $wpdb;
        
        // 전체 수집된 장소 수
        $total_collected = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_auto_collection_history'"
        );
        
        // 총 수집된 필드 수
        $fields_collected = 0;
        $total_places = 0;
        
        $places = get_posts(array(
            'post_type' => 'places',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        foreach ($places as $place_id) {
            $fields_count = $this->get_collected_fields_count($place_id);
            if ($fields_count > 0) {
                $fields_collected += $fields_count;
                $total_places++;
            }
        }
        
        $avg_fields = $total_places > 0 ? round($fields_collected / $total_places, 1) : 0;
        $success_rate = count($places) > 0 ? round(($total_places / count($places)) * 100) : 0;
        
        return array(
            'total_collected' => $total_collected,
            'fields_collected' => $fields_collected,
            'avg_fields' => $avg_fields,
            'success_rate' => $success_rate
        );
    }
}

// 관리자 클래스 초기화
new Place_Detail_Auto_Collector_Admin();
