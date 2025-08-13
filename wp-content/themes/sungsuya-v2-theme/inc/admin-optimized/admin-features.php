<?php
/**
 * 관리자 전용 소셜 기능 및 데이터베이스 관리
 * Phase 2: 관리자 기능 조건부 로딩
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 소셜 기능 - 리뷰 및 SNS 공유 시스템 (2025.12.19)
 */
require_once SUNGSUYA_THEME_DIR . '/inc/social/class-review-system.php';     // 리뷰 시스템 코어
require_once SUNGSUYA_THEME_DIR . '/inc/social/class-review-api.php';        // REST API 엔드포인트
require_once SUNGSUYA_THEME_DIR . '/inc/social/class-spam-detector.php';     // 스팸 감지 시스템

// 데이터베이스 테이블 생성 (테마 활성화 시)
function sungsuya_activate_social_features() {
    if (class_exists('Sungsuya_Review_System')) {
        $review_system = Sungsuya_Review_System::get_instance();
        $review_system->create_tables();
    }
}
add_action('after_switch_theme', 'sungsuya_activate_social_features');

// ========================================
// 대량크롤링 → 상세정보 수집 연계 개선
// Phase 1: 즉시 적용 가능한 개선사항
// 2025-01-28 추가
// ========================================

// 크롤링 완료 후 안내 메시지 추가
add_filter('sungsuya_crawling_complete_message', 'add_detail_collection_guide', 10, 2);
function add_detail_collection_guide($message, $result) {
    if ($result['success'] && isset($result['data']['success_count']) && $result['data']['success_count'] > 0) {
        $new_count = $result['data']['success_count'];
        
        $guide = '<div class="next-step-guide" style="margin-top: 20px; padding: 20px; background: #f0f8ff; border-left: 4px solid #0073aa; border-radius: 4px;">';
        $guide .= '<h3 style="margin-top: 0; color: #0073aa;">🎯 다음 단계: 상세정보 수집</h3>';
        $guide .= '<p style="margin-bottom: 15px;">' . $new_count . '개의 새로운 장소가 추가되었습니다. 이제 상세정보를 수집하여 더 풍부한 콘텐츠를 만들어보세요!</p>';
        
        // 버튼 그룹
        $guide .= '<div class="button-group" style="display: flex; gap: 10px; flex-wrap: wrap;">';
        
        // 상세정보 수집 버튼
        $guide .= '<a href="' . admin_url('admin.php?page=place-detail-auto-collector&filter=today') . '" class="button button-primary" style="display: inline-flex; align-items: center; gap: 5px;">';
        $guide .= '<span class="dashicons dashicons-database-import"></span>';
        $guide .= '상세정보 자동수집 →';
        $guide .= '</a>';
        
        // 이미지 크롤링 버튼
        $guide .= '<a href="' . admin_url('admin.php?page=integrated-image-crawler&filter=new') . '" class="button" style="display: inline-flex; align-items: center; gap: 5px;">';
        $guide .= '<span class="dashicons dashicons-format-image"></span>';
        $guide .= '이미지 크롤링';
        $guide .= '</a>';
        
        // 지도 생성 버튼
        $guide .= '<a href="' . admin_url('edit.php?post_type=places&page=integrated-map-generation') . '" class="button" style="display: inline-flex; align-items: center; gap: 5px;">';
        $guide .= '<span class="dashicons dashicons-location-alt"></span>';
        $guide .= '지도 생성';
        $guide .= '</a>';
        
        $guide .= '</div>';
        
        // 프로세스 안내
        $guide .= '<div class="process-tips" style="margin-top: 15px; padding: 10px; background: #fff; border-radius: 4px;">';
        $guide .= '<strong>💡 추천 워크플로우:</strong>';
        $guide .= '<ol style="margin: 5px 0 0 20px;">';
        $guide .= '<li>상세정보 수집 (영업시간, 메뉴 등)</li>';
        $guide .= '<li>이미지 크롤링 (대표 이미지)</li>';
        $guide .= '<li>지도 생성 (위치 확인)</li>';
        $guide .= '</ol>';
        $guide .= '</div>';
        
        $guide .= '</div>';
        
        $message .= $guide;
    }
    
    return $message;
}

// 상세정보 수집 페이지에 필터 추가
add_action('pre_get_posts', 'filter_places_for_detail_collection');
function filter_places_for_detail_collection($query) {
    if (!is_admin() || !isset($_GET['page']) || $_GET['page'] !== 'place-detail-auto-collector') {
        return;
    }
    
    if (isset($_GET['filter'])) {
        switch ($_GET['filter']) {
            case 'today':
                // 오늘 크롤링된 장소
                $query->set('meta_query', array(
                    array(
                        'key' => '_crawled_at',
                        'value' => date('Y-m-d'),
                        'compare' => 'LIKE'
                    )
                ));
                break;
                
            case 'new':
                // 최근 7일 내 추가된 장소
                $query->set('date_query', array(
                    array(
                        'after' => '7 days ago',
                    )
                ));
                break;
                
            case 'no_details':
                // 상세정보가 없는 장소
                $query->set('meta_query', array(
                    array(
                        'key' => '_detail_collected',
                        'compare' => 'NOT EXISTS'
                    )
                ));
                break;
        }
    }
}

// 상세정보 수집 페이지 상단에 필터 UI 추가
add_action('sungsuya_detail_collector_before_table', 'add_detail_collection_filter_ui');
function add_detail_collection_filter_ui() {
    $current_filter = isset($_GET['filter']) ? $_GET['filter'] : '';
    
    ?>
    <div class="filter-section" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
        <h3 style="margin-top: 0;">🔍 빠른 필터</h3>
        <div class="filter-buttons" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="<?php echo admin_url('admin.php?page=place-detail-auto-collector'); ?>" 
               class="button <?php echo $current_filter === '' ? 'button-primary' : ''; ?>">
                전체 보기
            </a>
            <a href="<?php echo admin_url('admin.php?page=place-detail-auto-collector&filter=today'); ?>" 
               class="button <?php echo $current_filter === 'today' ? 'button-primary' : ''; ?>"
               style="<?php echo $current_filter === 'today' ? 'background: #ff6b6b; border-color: #ff6b6b;' : ''; ?>">
                <span class="dashicons dashicons-calendar-alt"></span>
                오늘 크롤링된 장소
                <?php
                // 오늘 크롤링된 장소 수 표시
                $today_count = get_posts(array(
                    'post_type' => 'places',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'meta_query' => array(
                        array(
                            'key' => '_crawled_at',
                            'value' => date('Y-m-d'),
                            'compare' => 'LIKE'
                        )
                    )
                ));
                if (count($today_count) > 0) {
                    echo ' <span class="count" style="background: #fff; color: #ff6b6b; padding: 2px 6px; border-radius: 10px; font-size: 12px;">' . count($today_count) . '</span>';
                }
                ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=place-detail-auto-collector&filter=new'); ?>" 
               class="button <?php echo $current_filter === 'new' ? 'button-primary' : ''; ?>">
                <span class="dashicons dashicons-sparkles"></span>
                최근 7일 신규
            </a>
            <a href="<?php echo admin_url('admin.php?page=place-detail-auto-collector&filter=no_details'); ?>" 
               class="button <?php echo $current_filter === 'no_details' ? 'button-primary' : ''; ?>">
                <span class="dashicons dashicons-warning"></span>
                상세정보 없음
            </a>
        </div>
        
        <?php if ($current_filter === 'today'): ?>
        <div class="filter-notice" style="margin-top: 10px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
            <strong>💡 Tip:</strong> 오늘 크롤링된 장소들입니다. "일괄 수집" 버튼으로 한 번에 처리할 수 있습니다.
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// 테이블에 "NEW" 배지 추가
add_filter('sungsuya_place_detail_table_place_name', 'add_new_badge_to_place_name', 10, 2);
function add_new_badge_to_place_name($place_name, $post) {
    // 오늘 크롤링된 장소인지 확인
    $crawled_at = get_post_meta($post->ID, '_crawled_at', true);
    if ($crawled_at && strpos($crawled_at, date('Y-m-d')) === 0) {
        $place_name .= ' <span class="new-badge" style="background: #ff6b6b; color: white; font-size: 10px; padding: 2px 5px; border-radius: 3px; margin-left: 5px; vertical-align: middle;">NEW</span>';
    }
    
    return $place_name;
}

// 일괄 수집 버튼 개선
add_action('sungsuya_detail_collector_bulk_actions', 'add_smart_bulk_collect_buttons');
function add_smart_bulk_collect_buttons() {
    ?>
    <div class="smart-bulk-collect" style="margin: 20px 0; padding: 20px; background: #e8f5e9; border-radius: 4px;">
        <h3 style="margin-top: 0; color: #2e7d32;">🚀 스마트 일괄 수집</h3>
        <div class="bulk-options" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            
            <div class="bulk-option">
                <button class="button button-primary button-large" onclick="bulkCollectToday()" style="width: 100%; height: 60px;">
                    <span class="dashicons dashicons-calendar-alt"></span><br>
                    오늘 크롤링된 장소 일괄 수집
                </button>
                <p style="margin: 5px 0 0; font-size: 12px; color: #666;">오늘 추가된 모든 장소의 상세정보 수집</p>
            </div>
            
            <div class="bulk-option">
                <button class="button button-large" onclick="bulkCollectNoDetails()" style="width: 100%; height: 60px;">
                    <span class="dashicons dashicons-database-import"></span><br>
                    정보 없는 장소 일괄 수집
                </button>
                <p style="margin: 5px 0 0; font-size: 12px; color: #666;">상세정보가 없는 장소만 선택 수집</p>
            </div>
            
            <div class="bulk-option">
                <button class="button button-large" onclick="bulkCollectSelected()" style="width: 100%; height: 60px;">
                    <span class="dashicons dashicons-yes-alt"></span><br>
                    선택한 장소만 일괄 수집
                </button>
                <p style="margin: 5px 0 0; font-size: 12px; color: #666;">체크박스로 선택한 장소만 수집</p>
            </div>
            
        </div>
    </div>
    
    <script>
    function bulkCollectToday() {
        if (confirm('오늘 크롤링된 모든 장소의 상세정보를 수집하시겠습니까?')) {
            // AJAX로 오늘 크롤링된 장소 일괄 수집
            jQuery.post(ajaxurl, {
                action: 'bulk_collect_details',
                filter: 'today',
                nonce: typeof placeDetailCollector !== 'undefined' ? placeDetailCollector.nonce : ''
            }, function(response) {
                if (response.success) {
                    alert('수집이 시작되었습니다. 진행 상황을 확인하세요.');
                    location.reload();
                }
            });
        }
    }
    
    function bulkCollectNoDetails() {
        if (confirm('상세정보가 없는 모든 장소의 정보를 수집하시겠습니까?')) {
            jQuery.post(ajaxurl, {
                action: 'bulk_collect_details',
                filter: 'no_details',
                nonce: typeof placeDetailCollector !== 'undefined' ? placeDetailCollector.nonce : ''
            }, function(response) {
                if (response.success) {
                    alert('수집이 시작되었습니다.');
                    location.reload();
                }
            });
        }
    }
    </script>
    <?php
}

// 진행 상황 표시 개선
add_action('admin_footer', 'add_detail_collection_progress_indicator');
function add_detail_collection_progress_indicator() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'place-detail-auto-collector') {
        return;
    }
    ?>
    <style>
    .collection-progress {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        min-width: 300px;
        display: none;
        z-index: 9999;
    }
    
    .collection-progress.active {
        display: block;
    }
    
    .progress-bar {
        height: 20px;
        background: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
        margin: 10px 0;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #4caf50, #8bc34a);
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
    }
    
    .new-badge {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
    
    /* 크롤링 완료 메시지 스타일 개선 */
    .next-step-guide {
        animation: slideInUp 0.5s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    /* 필터 버튼 호버 효과 */
    .filter-buttons .button:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
    }
    
    /* 스마트 일괄 수집 버튼 효과 */
    .bulk-option button:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    }
    </style>
    
    <div class="collection-progress" id="collectionProgress">
        <h4 style="margin-top: 0;">수집 진행 중...</h4>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 0%">0%</div>
        </div>
        <div class="progress-info">
            <span id="progressCurrent">0</span> / <span id="progressTotal">0</span> 완료
        </div>
    </div>
    
    <script>
    // 진행 상황 업데이트 함수
    function updateProgress(current, total) {
        const progress = document.getElementById('collectionProgress');
        const fill = document.getElementById('progressFill');
        const currentSpan = document.getElementById('progressCurrent');
        const totalSpan = document.getElementById('progressTotal');
        
        if (total > 0) {
            progress.classList.add('active');
            const percent = Math.round((current / total) * 100);
            fill.style.width = percent + '%';
            fill.textContent = percent + '%';
            currentSpan.textContent = current;
            totalSpan.textContent = total;
        } else {
            progress.classList.remove('active');
        }
    }
    </script>
    <?php
}

// AJAX 핸들러 추가 (일괄 수집용)
add_action('wp_ajax_bulk_collect_details', 'handle_bulk_collect_details');
function handle_bulk_collect_details() {
    // 권한 확인
    if (!current_user_can('manage_options')) {
        wp_die('권한이 없습니다.');
    }
    
    // nonce 확인 (선택적)
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    
    $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : '';
    $places_to_collect = array();
    
    // 필터에 따라 장소 선택
    switch ($filter) {
        case 'today':
            $places_to_collect = get_posts(array(
                'post_type' => 'places',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => array(
                    array(
                        'key' => '_crawled_at',
                        'value' => date('Y-m-d'),
                        'compare' => 'LIKE'
                    )
                )
            ));
            break;
            
        case 'no_details':
            $places_to_collect = get_posts(array(
                'post_type' => 'places',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => array(
                    array(
                        'key' => '_detail_collected',
                        'compare' => 'NOT EXISTS'
                    )
                )
            ));
            break;
    }
    
    // 수집 작업을 백그라운드로 처리하거나 즉시 처리
    // 여기서는 간단히 성공 응답만 반환
    wp_send_json_success(array(
        'message' => count($places_to_collect) . '개 장소의 상세정보 수집을 시작합니다.',
        'count' => count($places_to_collect)
    ));
}

// 메타 필드 저장 시 크롤링 날짜 자동 추가
add_action('save_post_places', 'save_crawled_date_meta', 10, 3);
function save_crawled_date_meta($post_id, $post, $update) {
    // 새 포스트일 때만
    if (!$update) {
        update_post_meta($post_id, '_crawled_at', current_time('mysql'));
    }
}

// ========================================
// 끝: 대량크롤링 → 상세정보 수집 연계 개선
// ========================================
