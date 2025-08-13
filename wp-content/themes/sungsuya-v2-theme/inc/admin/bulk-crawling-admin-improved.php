<?php
/**
 * 대량크롤링 시스템 - UI/UX 개선 버전
 * 
 * 개선사항:
 * 1. 직관적인 UI/UX
 * 2. 실시간 진행상황 표시
 * 3. 상세한 안내 문구
 * 4. 크롤링 히스토리
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 크롤링 관리 메뉴 추가
 */
function sungsuya_add_crawling_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=places',  // 부모 메뉴
        '대량크롤링 시스템',          // 페이지 제목
        '🕷️ 대량크롤링',             // 메뉴 제목
        'manage_options',             // 권한
        'bulk-crawling-system',       // 메뉴 슬러그
        'sungsuya_crawling_admin_page' // 콜백 함수
    );
}
add_action('admin_menu', 'sungsuya_add_crawling_admin_menu');

/**
 * 개선된 대량크롤링 관리 페이지
 */
function sungsuya_crawling_admin_page() {
    // 권한 체크
    if (!current_user_can('manage_options')) {
        wp_die(__('권한이 없습니다.'));
    }

    // Flush rewrite rules if requested
    if (isset($_GET['sungsuya_places_flush_rewrites']) && $_GET['sungsuya_places_flush_rewrites'] == '1') {
        flush_rewrite_rules();
        wp_redirect(remove_query_arg('sungsuya_places_flush_rewrites'));
        exit;
    }

    // 크롤링 통계 가져오기
    $total_places = wp_count_posts('places')->publish;
    $crawled_places = sungsuya_get_crawled_places_count();
    $manual_places = $total_places - $crawled_places;
    
    // 세션 카운트와 진행 상황 가져오기
    $session_count = get_option('sungsuya_crawling_session_count', 0);
    
    // 현재 선택된 키워드의 진행 상황 가져오기 (기본값: 맛집)
    $default_keyword = '맛집';
    $all_progress = get_option('crawling_type_progress', array());
    $current_progress = $all_progress[$default_keyword] ?? array('last_page' => 0);
    $current_page = $current_progress['last_page'] + 1;
    
    // 45페이지 넘으면 다시 1페이지로
    if ($current_page > 45) {
        $current_page = 1;
    }
    
    // 통계 데이터 가져오기
    $stats = get_option('sungsuya_crawling_stats', array(
        'total_sessions' => 0,
        'new_places' => 0,
        'duplicates' => 0,
        'latest_checks' => 0,
        'missed_checks' => 0
    ));
    
    ?>
    <div class="wrap sungsuya-admin-wrap">
        <style>
            /* 개선된 스타일 */
            .sungsuya-admin-wrap {
                max-width: 1200px;
                margin: 20px 0;
            }
            
            /* 헤더 스타일 */
            .crawling-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                border-radius: 10px;
                margin-bottom: 30px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            }
            
            .crawling-header h1 {
                color: white;
                margin: 0 0 10px 0;
                font-size: 28px;
            }
            
            .crawling-header p {
                margin: 0;
                opacity: 0.9;
                font-size: 16px;
            }
            
            /* 모드 선택 카드 */
            .crawling-modes {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .mode-card {
                background: white;
                border: 2px solid #e2e8f0;
                border-radius: 10px;
                padding: 25px;
                transition: all 0.3s ease;
                cursor: pointer;
                position: relative;
                overflow: hidden;
            }
            
            .mode-card:hover {
                border-color: #667eea;
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.1);
                transform: translateY(-2px);
            }
            
            .mode-card.recommended {
                border-color: #48bb78;
                background: linear-gradient(to bottom right, #f0fff4, #ffffff);
            }
            
            .mode-card.recommended::before {
                content: "추천";
                position: absolute;
                top: 10px;
                right: -20px;
                background: #48bb78;
                color: white;
                padding: 5px 30px;
                font-size: 12px;
                transform: rotate(45deg);
                font-weight: bold;
            }
            
            .mode-card h3 {
                font-size: 20px;
                margin: 0 0 10px 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .mode-card h3 .emoji {
                font-size: 28px;
            }
            
            .mode-card .description {
                color: #718096;
                margin-bottom: 15px;
                line-height: 1.6;
            }
            
            .mode-card .when-to-use {
                background: #f7fafc;
                padding: 10px 15px;
                border-radius: 5px;
                font-size: 14px;
                color: #4a5568;
            }
            
            .mode-card .when-to-use strong {
                color: #2d3748;
            }
            
            .mode-card button {
                width: 100%;
                padding: 12px;
                font-size: 16px;
                font-weight: 600;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-top: 20px;
            }
            
            .mode-card button:hover {
                transform: translateY(-1px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            
            .btn-data-collect {
                background: #667eea;
                color: white;
            }
            
            .btn-latest-check {
                background: #f56565;
                color: white;
            }
            
            .btn-missed-check {
                background: #4299e1;
                color: white;
            }
            
            /* 설정 섹션 */
            .crawling-settings {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                margin-bottom: 30px;
            }
            
            .settings-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 25px;
            }
            
            .setting-group {
                margin-bottom: 0;
            }
            
            .setting-group label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
                color: #2d3748;
                font-size: 15px;
            }
            
            .setting-group select,
            .setting-group input[type="text"] {
                width: 100%;
                padding: 10px 15px;
                border: 2px solid #e2e8f0;
                border-radius: 5px;
                font-size: 15px;
                transition: border-color 0.3s ease;
            }
            
            .setting-group select:focus,
            .setting-group input[type="text"]:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }
            
            .setting-help {
                font-size: 13px;
                color: #718096;
                margin-top: 5px;
            }
            
            /* 통계 카드 */
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .stat-card {
                background: white;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                transition: all 0.3s ease;
            }
            
            .stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            }
            
            .stat-card .number {
                font-size: 36px;
                font-weight: 700;
                color: #667eea;
                margin-bottom: 5px;
            }
            
            .stat-card .label {
                color: #718096;
                font-size: 14px;
            }
            
            /* 진행 상태 표시 */
            .progress-indicator {
                background: #f7fafc;
                padding: 20px;
                border-radius: 10px;
                margin-bottom: 20px;
                border-left: 4px solid #667eea;
            }
            
            .progress-indicator h4 {
                margin: 0 0 10px 0;
                color: #2d3748;
            }
            
            .progress-indicator p {
                margin: 0;
                color: #4a5568;
            }
            
            /* 최근 크롤링 목록 */
            .recent-crawls {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            
            .recent-crawls h3 {
                margin-top: 0;
                color: #2d3748;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .recent-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .recent-list li {
                padding: 12px 0;
                border-bottom: 1px solid #e2e8f0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .recent-list li:last-child {
                border-bottom: none;
            }
            
            .recent-list .place-info {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .recent-list .place-type {
                background: #edf2f7;
                color: #4a5568;
                padding: 4px 10px;
                border-radius: 20px;
                font-size: 12px;
            }
            
            .recent-list .place-time {
                color: #a0aec0;
                font-size: 13px;
            }
            
            /* 알림 박스 */
            .notice-box {
                padding: 15px 20px;
                border-radius: 5px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .notice-box.info {
                background: #e6fffa;
                border-left: 4px solid #319795;
                color: #234e52;
            }
            
            .notice-box.warning {
                background: #fffaf0;
                border-left: 4px solid #ed8936;
                color: #7b341e;
            }
            
            .notice-box.success {
                background: #f0fff4;
                border-left: 4px solid #48bb78;
                color: #1a202c;
            }
            
            /* 로딩 애니메이션 */
            .loading-spinner {
                display: none;
                text-align: center;
                padding: 20px;
            }
            
            .loading-spinner.active {
                display: block;
            }
            
            .spinner {
                border: 3px solid #f3f3f3;
                border-top: 3px solid #667eea;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
                margin: 0 auto 10px;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            /* 반응형 */
            @media (max-width: 768px) {
                .crawling-modes {
                    grid-template-columns: 1fr;
                }
                
                .stats-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
        </style>

        <!-- 헤더 -->
        <div class="crawling-header">
            <h1>🕷️ 스마트 대량크롤링 시스템</h1>
            <p>카카오 로컬 API를 활용하여 성수동의 인기 장소를 자동으로 수집합니다.</p>
        </div>

        <!-- 알림 -->
        <?php if (isset($_GET['message'])): ?>
            <?php if ($_GET['message'] == 'success'): ?>
                <div class="notice-box success">
                    <span>✅</span>
                    <span>크롤링이 성공적으로 완료되었습니다!</span>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 진행 상태 표시 -->
        <div class="progress-indicator">
            <h4>📍 현재 크롤링 진행 상태</h4>
            <p>다음 데이터 수집은 <strong><?php echo $current_page; ?>페이지</strong>부터 시작됩니다. (총 <?php echo $session_count; ?>회 크롤링 완료)</p>
        </div>

        <!-- 설정 섹션 -->
        <div class="crawling-settings">
            <h3>⚙️ 크롤링 설정</h3>
            <form id="crawling-form">
                <div class="settings-grid">
                    <div class="setting-group">
                        <label for="place_type">🏷️ 장소 유형</label>
                        <select name="place_type" id="place_type">
                            <option value="맛집">🍽️ 맛집 (음식점)</option>
                            <option value="카페">☕ 카페</option>
                            <option value="매장">🛍️ 쇼핑 (매장)</option>
                            <option value="팝업스토어">🎪 팝업스토어</option>
                            <option value="편의시설">🏪 편의시설</option>
                            <option value="편집샵">👗 편집샵</option>
                            <option value="custom">🔍 직접 입력...</option>
                        </select>
                        <input type="text" name="custom_keyword" id="custom_keyword" placeholder="원하는 키워드 입력" style="display:none; margin-top:10px;">
                        <p class="setting-help">수집할 장소의 카테고리를 선택하세요</p>
                    </div>

                    <div class="setting-group">
                        <label for="location">📍 지역</label>
                        <input type="text" name="location" id="location" value="성수동" readonly>
                        <p class="setting-help">현재는 성수동 지역만 지원됩니다</p>
                    </div>

                    <div class="setting-group">
                        <label for="max_count">📊 수집 개수</label>
                        <select name="max_count" id="max_count">
                            <option value="5">5개 (테스트용)</option>
                            <option value="10">10개</option>
                            <option value="15" selected>15개 (1페이지)</option>
                            <option value="30">30개 (2페이지)</option>
                            <option value="45">45개 (3페이지)</option>
                        </select>
                        <p class="setting-help">한 번에 수집할 최대 장소 수</p>
                    </div>
                </div>
            </form>
        </div>

        <!-- 크롤링 모드 선택 -->
        <div class="crawling-modes">
            <div class="mode-card recommended">
                <h3><span class="emoji">🚀</span>데이터 수집 모드</h3>
                <p class="description">
                    순차적으로 페이지를 이동하며 새로운 장소를 발견합니다. 
                    매번 다른 페이지를 탐색하여 다양한 장소를 수집할 수 있습니다.
                </p>
                <div class="when-to-use">
                    <strong>추천 상황:</strong> 초기 데이터 구축, 정기적인 신규 장소 발견
                </div>
                <button type="button" class="btn-data-collect" onclick="startCrawling('collect')">
                    데이터 수집 시작
                </button>
            </div>

            <div class="mode-card">
                <h3><span class="emoji">🔥</span>최신 인기 체크</h3>
                <p class="description">
                    1페이지만 빠르게 확인하여 최근 인기있는 핫플레이스를 놓치지 않습니다.
                    카카오 정확도순으로 가장 인기있는 장소들을 확인합니다.
                </p>
                <div class="when-to-use">
                    <strong>추천 상황:</strong> 매일 아침, 신규 핫플 체크
                </div>
                <button type="button" class="btn-latest-check" onclick="startCrawling('latest')">
                    인기 장소 체크
                </button>
            </div>

            <div class="mode-card">
                <h3><span class="emoji">🔍</span>놓친 데이터 체크</h3>
                <p class="description">
                    마지막 크롤링 이후 페이지를 확인하여 놓친 장소가 있는지 체크합니다.
                    데이터의 완성도를 높이는 보완 작업입니다.
                </p>
                <div class="when-to-use">
                    <strong>추천 상황:</strong> 주 1회, 데이터 보완 작업
                </div>
                <button type="button" class="btn-missed-check" onclick="startCrawling('missed')">
                    놓친 데이터 찾기
                </button>
            </div>
        </div>

        <!-- 로딩 표시 -->
        <div class="loading-spinner" id="loading-spinner">
            <div class="spinner"></div>
            <p>크롤링 중입니다... 잠시만 기다려주세요.</p>
        </div>

        <!-- 통계 표시 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?php echo number_format($stats['total_sessions']); ?></div>
                <div class="label">총 크롤링 세션</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo number_format($stats['new_places']); ?></div>
                <div class="label">신규 발견</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo number_format($stats['duplicates']); ?></div>
                <div class="label">중복 제외</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo number_format($total_places); ?></div>
                <div class="label">전체 장소</div>
            </div>
        </div>

        <!-- 최근 크롤링 목록 -->
        <div class="recent-crawls">
            <h3>🕐 최근 크롤링된 장소</h3>
            <?php
            $recent_places = sungsuya_get_recent_crawled_places(10);
            if (!empty($recent_places)): ?>
                <ul class="recent-list">
                    <?php foreach ($recent_places as $place): ?>
                        <li>
                            <div class="place-info">
                                <strong><?php echo esc_html($place->post_title); ?></strong>
                                <span class="place-type">
                                    <?php 
                                    $place_types = wp_get_post_terms($place->ID, 'place_type');
                                    echo !empty($place_types) ? esc_html($place_types[0]->name) : '미분류';
                                    ?>
                                </span>
                            </div>
                            <span class="place-time">
                                <?php echo human_time_diff(strtotime($place->post_date), current_time('timestamp')) . ' 전'; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: #718096;">아직 크롤링된 장소가 없습니다.</p>
            <?php endif; ?>
        </div>

        <!-- 안내 사항 -->
        <div class="notice-box info" style="margin-top: 30px;">
            <span>💡</span>
            <span>크롤링 후에는 <a href="<?php echo admin_url('edit.php?post_type=places&page=integrated-map-generation'); ?>">통합 지도생성 시스템</a>에서 좌표를 생성해주세요.</span>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // 장소 유형 선택시 커스텀 입력 표시
            $('#place_type').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#custom_keyword').show().focus();
                } else {
                    $('#custom_keyword').hide();
                }
            });

            // 크롤링 시작 함수
            window.startCrawling = function(mode) {
                // 로딩 표시
                $('#loading-spinner').addClass('active');
                $('.crawling-modes button').prop('disabled', true);

                // 폼 데이터 수집
                var formData = $('#crawling-form').serialize();
                formData += '&mode=' + mode;
                formData += '&action=sungsuya_bulk_crawling';
                formData += '&_wpnonce=<?php echo wp_create_nonce('sungsuya_bulk_crawling'); ?>';

                // AJAX 요청
                $.post(ajaxurl, formData, function(response) {
                    $('#loading-spinner').removeClass('active');
                    $('.crawling-modes button').prop('disabled', false);
                    
                    if (response.success) {
                        // 성공 메시지 표시
                        var successHtml = '<div class="notice-box success" style="display:none;">' +
                            '<span>✅</span>' +
                            '<span>크롤링이 성공적으로 완료되었습니다! ';
                        
                        if (response.data && response.data.success_count) {
                            successHtml += '(신규: ' + response.data.success_count + '개';
                            if (response.data.duplicate_count) {
                                successHtml += ', 중복: ' + response.data.duplicate_count + '개';
                            }
                            successHtml += ')';
                        }
                        
                        successHtml += '</span></div>';
                        
                        $('.crawling-header').after(successHtml);
                        $('.notice-box.success').slideDown();
                        
                        // 3초 후 페이지 새로고침으로 통계 업데이트
                        setTimeout(function() {
                            window.location.reload();
                        }, 3000);
                        
                    } else {
                        alert('크롤링 중 오류가 발생했습니다: ' + (response.data || '알 수 없는 오류'));
                    }
                }).fail(function() {
                    alert('서버 오류가 발생했습니다. 다시 시도해주세요.');
                    $('#loading-spinner').removeClass('active');
                    $('.crawling-modes button').prop('disabled', false);
                });
            };
        });
        </script>
    </div>
    <?php
}

// 헬퍼 함수들
function sungsuya_get_crawled_places_count() {
    global $wpdb;
    return $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} 
         WHERE meta_key IN ('_sungsuya_crawled', '_bulk_crawled', '_crawled_at') 
         AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'places' AND post_status = 'publish')"
    );
}

function sungsuya_get_recent_crawled_places($limit = 5) {
    return get_posts(array(
        'post_type' => 'places',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_sungsuya_crawled',
                'value' => '1',
                'compare' => '='
            ),
            array(
                'key' => '_bulk_crawled',
                'value' => '1',
                'compare' => '='
            ),
            array(
                'key' => '_crawled_at',
                'compare' => 'EXISTS'
            )
        )
    ));
}

/**
 * AJAX 크롤링 처리 핸들러
 */
add_action('wp_ajax_sungsuya_bulk_crawling', 'sungsuya_handle_bulk_crawling');
function sungsuya_handle_bulk_crawling() {
    // 권한 체크
    if (!current_user_can('manage_options')) {
        wp_die('권한이 없습니다.');
    }

    // nonce 검증
    if (!wp_verify_nonce($_POST['_wpnonce'], 'sungsuya_bulk_crawling')) {
        wp_die('보안 검증 실패');
    }

    // 파라미터 수집
    $mode = sanitize_text_field($_POST['mode']);
    $place_type = sanitize_text_field($_POST['place_type']);
    $custom_keyword = sanitize_text_field($_POST['custom_keyword']);
    $location = sanitize_text_field($_POST['location']);
    $max_count = intval($_POST['max_count']);

    // 키워드 결정
    $keyword = ($place_type === 'custom' && !empty($custom_keyword)) ? $custom_keyword : $place_type;
    
    // 크롤링 모드에 따른 처리
    $session_count = get_option('sungsuya_crawling_session_count', 0);
    $current_page = 1;

    switch ($mode) {
        case 'collect':
            // 데이터 수집 모드: 페이지 순환
            $current_page = ($session_count % 5) + 1;
            update_option('sungsuya_crawling_session_count', $session_count + 1);
            break;
            
        case 'latest':
            // 최신 인기 체크: 1페이지만
            $current_page = 1;
            break;
            
        case 'missed':
            // 놓친 데이터 체크: 마지막 페이지부터
            $current_page = (($session_count - 1) % 5) + 1;
            break;
    }

    // 장소유형 Term 가져오기
    $place_type_term = null;
    if ($place_type !== 'custom') {
        // 장소유형 목록에서 해당하는 term 찾기
        $place_types = get_terms(array(
            'taxonomy' => 'place_type',
            'hide_empty' => false
        ));
        
        foreach ($place_types as $type) {
            if ($type->name === $place_type) {
                $place_type_term = $type;
                break;
            }
        }
    }
    
    // 기존 크롤링 시스템과 연동
    if (!function_exists('sungsuya_execute_crawling_v2')) {
        require_once get_template_directory() . '/inc/smart-crawling-strategy.php';
    }
    
    // 크롤링 모드 매핑
    $crawl_mode = 'normal';
    if ($mode === 'latest') {
        $crawl_mode = 'latest_popular';
    } elseif ($mode === 'missed') {
        $crawl_mode = 'check_missed';
    }
    
    // 크롤링 실행
    $result = sungsuya_execute_crawling_v2($keyword, $location, $max_count, $place_type_term, $crawl_mode);
    
    // 통계 업데이트
    if ($result['success']) {
        $stats = get_option('sungsuya_crawling_stats', array(
            'total_sessions' => 0,
            'new_places' => 0,
            'duplicates' => 0,
            'latest_checks' => 0,
            'missed_checks' => 0
        ));
        
        $stats['total_sessions']++;
        
        if ($mode === 'latest') {
            $stats['latest_checks']++;
        } elseif ($mode === 'missed') {
            $stats['missed_checks']++;
        }
        
        // 크롤링 결과에서 통계 추출
        if (isset($result['data']['success_count'])) {
            $stats['new_places'] += intval($result['data']['success_count']);
        }
        if (isset($result['data']['duplicate_count'])) {
            $stats['duplicates'] += intval($result['data']['duplicate_count']);
        }
        
        update_option('sungsuya_crawling_stats', $stats);
    }
    
    wp_send_json($result);
}
