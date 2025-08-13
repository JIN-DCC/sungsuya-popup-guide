<?php
/**
 * 성수야! - 정적지도 관리자 페이지
 * 
 * WordPress 관리자 통합 정적지도 생성 및 관리 도구
 * 기존 static-map-generator-fixed.php 기능을 WordPress 관리자 내에서 구현
 * 
 * @package SungsuyaV2
 * @version 1.0.0
 */

// WordPress 관리자에서만 접근 가능
if (!defined('ABSPATH')) {
    exit;
}

// NaverStaticMapGeneratorMapsFixed 클래스 로드 (정상 작동하는 버전)
require_once get_template_directory() . '/inc/naver-static-map-generator-maps-fixed.php';

/**
 * 정적지도 관리 페이지 메인 함수
 */
function sungsuya_static_map_admin_page() {
    // WordPress 관리자에서 jQuery 로드 보장
    wp_enqueue_script('jquery');
    
    // AJAX 요청 처리
    if (isset($_POST['action'])) {
        handle_static_map_ajax_request();
        return;
    }
    
    // Places 목록 가져오기
    $places_query = new WP_Query([
        'post_type' => 'places',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'latitude',
                'compare' => 'EXISTS'
            ],
            [
                'key' => 'longitude',
                'compare' => 'EXISTS'
            ]
        ]
    ]);
    ?>
    
    <div class="wrap">
        <h1>🗺️ 정적지도 관리</h1>
        <p>Places 데이터의 정적지도를 생성하고 관리합니다.</p>
        
        <style>
        /* 테이블 레이아웃 개선 */
        .static-map-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }
        
        .static-map-table th,
        .static-map-table td {
            padding: 12px 8px;
            text-align: left;
            border: 1px solid #ddd;
            vertical-align: top;
            word-wrap: break-word;
        }
        
        .static-map-table th {
            background-color: #f1f1f1;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .static-map-table .col-id {
            width: 60px;
            text-align: center;
        }
        
        .static-map-table .col-place {
            width: 250px;
            max-width: 250px;
        }
        
        .static-map-table .col-coords {
            width: 120px;
            font-size: 11px;
            font-family: monospace;
        }
        
        .static-map-table .col-status {
            width: 100px;
            text-align: center;
        }
        
        .static-map-table .col-actions {
            width: 150px;
            text-align: center;
        }
        
        .place-title {
            font-weight: 600;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        
        .place-address {
            font-size: 12px;
            color: #666;
            line-height: 1.2;
        }
        
        .coord-item {
            display: block;
            margin-bottom: 2px;
        }
        
        .status-success {
            color: #46b450;
            font-weight: 600;
        }
        
        .status-error {
            color: #dc3232;
            font-weight: 600;
        }
        
        .status-detail {
            font-size: 11px;
            color: #666;
            margin-top: 2px;
        }
        
        .action-buttons {
            display: flex;
            gap: 4px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .action-buttons .button {
            margin: 0;
            font-size: 12px;
            padding: 4px 8px;
            height: auto;
            line-height: 1.2;
        }
        
        /* 카드 스타일 개선 */
        .card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        }
        
        .card h2 {
            margin: 0 0 15px 0;
            padding: 0;
            font-size: 18px;
        }
        
        /* 반응형 개선 */
        @media (max-width: 1200px) {
            .static-map-table .col-place {
                width: 200px;
                max-width: 200px;
            }
        }
        
        @media (max-width: 768px) {
            .static-map-table .col-coords {
                font-size: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
        </style>
        
        <!-- 상태 표시 영역 -->
        <div id="status-container" style="margin: 20px 0;">
            <div id="status-messages"></div>
            <div id="progress-container" style="display: none;">
                <div style="background: #f1f1f1; border-radius: 10px; overflow: hidden; margin: 10px 0;">
                    <div id="progress-bar" style="background: #0073aa; height: 20px; width: 0%; transition: width 0.3s;"></div>
                </div>
                <div id="progress-text">0%</div>
            </div>
        </div>
        
        <!-- 전체 작업 버튼 -->
        <div class="card" style="margin: 20px 0; padding: 20px;">
            <h2>🚀 대량 작업</h2>
            <p>모든 Places의 정적지도를 한 번에 생성하거나 관리합니다.</p>
            
            <div style="margin: 15px 0;">
                <button id="generate-all-btn" class="button button-primary button-large">
                    🗺️ 모든 Places 정적지도 생성
                </button>
                
                <button id="refresh-failed-btn" class="button button-secondary">
                    🔄 실패한 지도 재생성
                </button>
                
                <button id="check-status-btn" class="button button-secondary">
                    📊 상태 확인
                </button>
            </div>
        </div>
        
        <!-- Places 목록 -->
        <div class="card">
            <h2>📍 Places 목록 (<?php echo $places_query->found_posts; ?>개)</h2>
            
            <?php if ($places_query->have_posts()): ?>
            <table class="static-map-table">
                <thead>
                    <tr>
                        <th class="col-id">ID</th>
                        <th class="col-place">장소명</th>
                        <th class="col-coords">좌표</th>
                        <th class="col-status">정적지도</th>
                        <th class="col-actions">작업</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($places_query->have_posts()): $places_query->the_post(); 
                        $place_id = get_the_ID();
                        $latitude = get_post_meta($place_id, 'latitude', true);
                        $longitude = get_post_meta($place_id, 'longitude', true);
                        $static_map_image_id = get_post_meta($place_id, 'static_map_image_id', true);
                        $has_static_map = $static_map_image_id && wp_attachment_is_image($static_map_image_id);
                        $address = get_post_meta($place_id, 'address', true);
                    ?>
                    <tr id="place-row-<?php echo $place_id; ?>">
                        <td class="col-id"><strong><?php echo $place_id; ?></strong></td>
                        <td class="col-place">
                            <div class="place-title"><?php echo esc_html(get_the_title()); ?></div>
                            <?php if (!empty($address)): ?>
                            <div class="place-address"><?php echo esc_html($address); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="col-coords">
                            <?php if (!empty($latitude) && !empty($longitude)): ?>
                            <span class="coord-item">위도: <?php echo number_format($latitude, 4); ?></span>
                            <span class="coord-item">경도: <?php echo number_format($longitude, 4); ?></span>
                            <?php else: ?>
                            <span style="color: #dc3232;">좌표 없음</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-status">
                            <?php if ($has_static_map): ?>
                                <span class="status-success">✅ 있음</span>
                                <div class="status-detail">ID: <?php echo $static_map_image_id; ?></div>
                            <?php else: ?>
                                <span class="status-error">❌ 없음</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-actions">
                            <div class="action-buttons">
                                <button class="button button-small generate-single-btn" 
                                        data-place-id="<?php echo $place_id; ?>"
                                        data-place-name="<?php echo esc_attr(get_the_title()); ?>">
                                    🗺️ 생성
                                </button>
                                
                                <?php if ($has_static_map): ?>
                                <a href="<?php echo wp_get_attachment_url($static_map_image_id); ?>" 
                                   target="_blank" class="button button-small">
                                    👁️ 보기
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>좌표 정보가 있는 Places가 없습니다.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script>
    jQuery(document).ready(function($) {
        // WordPress AJAX URL 설정
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        
        // 상태 메시지 표시 함수
        function showStatus(message, type = 'info') {
            const colors = {
                'success': '#46b450',
                'error': '#dc3232',
                'warning': '#ffb900',
                'info': '#0073aa'
            };
            
            $('#status-messages').html(`
                <div style="background: ${colors[type]}; color: white; padding: 12px; border-radius: 5px; margin: 5px 0;">
                    ${message}
                </div>
            `);
        }
        
        // 진행률 업데이트
        function updateProgress(percent, text) {
            $('#progress-container').show();
            $('#progress-bar').css('width', percent + '%');
            $('#progress-text').text(text);
            
            if (percent >= 100) {
                setTimeout(() => {
                    $('#progress-container').hide();
                }, 2000);
            }
        }
        
        // AJAX 요청 함수
        function makeAjaxRequest(action, data = {}) {
            return $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'sungsuya_static_map_admin',
                    map_action: action,
                    nonce: '<?php echo wp_create_nonce('static_map_admin'); ?>',
                    ...data
                }
            });
        }
        
        // 개별 지도 생성
        $('.generate-single-btn').click(function() {
            const btn = $(this);
            const placeId = btn.data('place-id');
            const placeName = btn.data('place-name');
            
            btn.prop('disabled', true).text('생성 중...');
            showStatus(`"${placeName}" 정적지도 생성 중...`, 'info');
            
            makeAjaxRequest('generate_single', { place_id: placeId })
                .done(function(response) {
                    console.log('Response:', response);
                    if (response.success) {
                        showStatus(`✅ "${placeName}" 정적지도 생성 완료!`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showStatus(`❌ "${placeName}" 생성 실패: ${response.data || response.message}`, 'error');
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    showStatus(`❌ "${placeName}" 생성 중 오류 발생: ${error}`, 'error');
                })
                .always(function() {
                    btn.prop('disabled', false).text('🗺️ 생성');
                });
        });
        
        // 전체 지도 생성
        $('#generate-all-btn').click(function() {
            if (!confirm('모든 Places의 정적지도를 생성하시겠습니까?')) return;
            
            const btn = $(this);
            btn.prop('disabled', true).text('생성 중...');
            showStatus('모든 Places 정적지도 생성을 시작합니다...', 'info');
            updateProgress(0, '시작 중...');
            
            makeAjaxRequest('generate_all')
                .done(function(response) {
                    console.log('Generate All Response:', response);
                    if (response.success) {
                        const data = response.data || response;
                        showStatus(`✅ 전체 생성 완료! 성공: ${data.success_count || 0}, 실패: ${data.fail_count || 0}`, 'success');
                        updateProgress(100, '완료!');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showStatus(`❌ 전체 생성 실패: ${response.data || response.message}`, 'error');
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Generate All Error:', xhr.responseText);
                    showStatus('❌ 전체 생성 중 오류 발생', 'error');
                })
                .always(function() {
                    btn.prop('disabled', false).text('🗺️ 모든 Places 정적지도 생성');
                });
        });
        
        // 실패한 지도 재생성
        $('#refresh-failed-btn').click(function() {
            const btn = $(this);
            btn.prop('disabled', true).text('재생성 중...');
            showStatus('실패한 정적지도 재생성 중...', 'info');
            
            makeAjaxRequest('refresh_failed')
                .done(function(response) {
                    console.log('Refresh Failed Response:', response);
                    if (response.success) {
                        const data = response.data || response;
                        showStatus(`✅ 재생성 완료! 처리: ${data.processed_count || 0}개`, 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showStatus(`❌ 재생성 실패: ${response.data || response.message}`, 'error');
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Refresh Failed Error:', xhr.responseText);
                    showStatus('❌ 재생성 중 오류 발생', 'error');
                })
                .always(function() {
                    btn.prop('disabled', false).text('🔄 실패한 지도 재생성');
                });
        });
        
        // 상태 확인
        $('#check-status-btn').click(function() {
            const btn = $(this);
            btn.prop('disabled', true).text('확인 중...');
            
            makeAjaxRequest('check_status')
                .done(function(response) {
                    console.log('Check Status Response:', response);
                    if (response.success) {
                        const data = response.data || response;
                        showStatus(`📊 상태: 전체 ${data.total || 0}개 중 정적지도 있음 ${data.has_map || 0}개, 없음 ${data.no_map || 0}개`, 'info');
                    } else {
                        showStatus(`❌ 상태 확인 실패: ${response.data || response.message}`, 'error');
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Check Status Error:', xhr.responseText);
                    showStatus('❌ 상태 확인 중 오류 발생', 'error');
                })
                .always(function() {
                    btn.prop('disabled', false).text('📊 상태 확인');
                });
        });
        
        // 초기 상태 메시지
        showStatus('정적지도 관리 시스템이 준비되었습니다.', 'info');
    });
    </script>
    
    <?php
    wp_reset_postdata();
}

/**
 * AJAX 요청 처리
 */
function handle_static_map_ajax_request() {
    // 권한 확인
    if (!current_user_can('manage_options')) {
        wp_send_json_error('권한이 없습니다.');
        return;
    }
    
    // Nonce 검증
    if (!wp_verify_nonce($_POST['nonce'], 'static_map_admin')) {
        wp_send_json_error('Nonce 검증 실패: 권한이 없습니다.');
        return;
    }
    
    $action = sanitize_text_field($_POST['map_action']);
    
    // 디버그 로깅
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('정적지도 AJAX 액션: ' . $action);
    }
    
    switch ($action) {
        case 'generate_single':
            handle_generate_single_map();
            break;
            
        case 'generate_all':
            handle_generate_all_maps();
            break;
            
        case 'refresh_failed':
            handle_refresh_failed_maps();
            break;
            
        case 'check_status':
            handle_check_status();
            break;
            
        default:
            wp_send_json_error('알 수 없는 작업입니다: ' . $action);
    }
}

/**
 * 개별 정적지도 생성
 */
function handle_generate_single_map() {
    $place_id = intval($_POST['place_id']);
    
    if (!$place_id) {
        wp_send_json_error('유효하지 않은 Place ID입니다.');
        return;
    }
    
    // Places 포스트인지 확인
    if (get_post_type($place_id) !== 'places') {
        wp_send_json_error('Places 포스트가 아닙니다.');
        return;
    }
    
    // 좌표 정보 확인
    $latitude = get_post_meta($place_id, 'latitude', true);
    $longitude = get_post_meta($place_id, 'longitude', true);
    
    if (empty($latitude) || empty($longitude)) {
        wp_send_json_error('좌표 정보가 없습니다.');
        return;
    }
    
    // 정상 작동하는 NaverStaticMapGeneratorMapsFixed 클래스 사용
    if (class_exists('NaverStaticMapGeneratorMapsFixed')) {
        try {
            $generator = new NaverStaticMapGeneratorMapsFixed();
            $place_name = get_the_title($place_id);
            $address = get_post_meta($place_id, 'address', true); // 주소 정보 추가
            $result = $generator->generatePlaceMap($place_id, $latitude, $longitude, $place_name, $address);
            
            if ($result['success']) {
                wp_send_json_success($result['message']);
            } else {
                wp_send_json_error($result['error']);
            }
        } catch (Exception $e) {
            wp_send_json_error('지도 생성 오류: ' . $e->getMessage());
        }
    } else {
        wp_send_json_error('정상 작동하는 정적지도 생성 시스템을 찾을 수 없습니다.');
    }
}

/**
 * 모든 정적지도 생성
 */
function handle_generate_all_maps() {
    // 좌표가 있는 모든 Places 가져오기
    $places_query = new WP_Query([
        'post_type' => 'places',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'latitude',
                'compare' => 'EXISTS'
            ],
            [
                'key' => 'longitude',
                'compare' => 'EXISTS'
            ]
        ]
    ]);
    
    $success_count = 0;
    $fail_count = 0;
    
    if ($places_query->have_posts()) {
        // 정상 작동하는 NaverStaticMapGeneratorMapsFixed 클래스 사용
        $use_generator_class = class_exists('NaverStaticMapGeneratorMapsFixed');
        $generator = $use_generator_class ? new NaverStaticMapGeneratorMapsFixed() : null;
        
        while ($places_query->have_posts()) {
            $places_query->the_post();
            $place_id = get_the_ID();
            
            try {
                $result = false;
                
                if ($use_generator_class) {
                    $latitude = get_post_meta($place_id, 'latitude', true);
                    $longitude = get_post_meta($place_id, 'longitude', true);
                    $place_name = get_the_title($place_id);
                    $address = get_post_meta($place_id, 'address', true); // 주소 정보 추가
                    
                    $map_result = $generator->generatePlaceMap($place_id, $latitude, $longitude, $place_name, $address);
                    $result = $map_result['success'];
                }
                
                if ($result) {
                    $success_count++;
                } else {
                    $fail_count++;
                }
            } catch (Exception $e) {
                $fail_count++;
                error_log('지도 생성 오류 (Place ID: ' . $place_id . '): ' . $e->getMessage());
            }
        }
    }
    
    wp_reset_postdata();
    
    wp_send_json_success([
        'success_count' => $success_count,
        'fail_count' => $fail_count,
        'message' => "전체 생성 완료: 성공 {$success_count}개, 실패 {$fail_count}개"
    ]);
}

/**
 * 실패한 정적지도 재생성
 */
function handle_refresh_failed_maps() {
    // 정적지도가 없는 Places 찾기
    $places_query = new WP_Query([
        'post_type' => 'places',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'latitude',
                'compare' => 'EXISTS'
            ],
            [
                'key' => 'longitude',
                'compare' => 'EXISTS'
            ],
            [
                'key' => 'static_map_image_id',
                'compare' => 'NOT EXISTS'
            ]
        ]
    ]);
    
    $processed_count = 0;
    
    if ($places_query->have_posts()) {
        while ($places_query->have_posts()) {
            $places_query->the_post();
            $place_id = get_the_ID();
            
            if (class_exists('NaverStaticMapGeneratorMapsFixed')) {
                try {
                    $generator = new NaverStaticMapGeneratorMapsFixed();
                    $latitude = get_post_meta($place_id, 'latitude', true);
                    $longitude = get_post_meta($place_id, 'longitude', true);
                    $place_name = get_the_title($place_id);
                    $address = get_post_meta($place_id, 'address', true); // 주소 정보 추가
                    
                    $result = $generator->generatePlaceMap($place_id, $latitude, $longitude, $place_name, $address);
                    if ($result['success']) {
                        $processed_count++;
                    }
                } catch (Exception $e) {
                    error_log('정적지도 재생성 오류 (Place ID: ' . $place_id . '): ' . $e->getMessage());
                }
            }
        }
    }
    
    wp_reset_postdata();
    
    wp_send_json_success([
        'processed_count' => $processed_count,
        'message' => "재생성 완료: {$processed_count}개 처리됨"
    ]);
}

/**
 * 상태 확인
 */
function handle_check_status() {
    // 전체 Places 수
    $total_places = wp_count_posts('places')->publish;
    
    // 정적지도가 있는 Places 수
    $has_map_query = new WP_Query([
        'post_type' => 'places',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'static_map_image_id',
                'compare' => 'EXISTS'
            ]
        ],
        'fields' => 'ids'
    ]);
    
    $has_map_count = $has_map_query->found_posts;
    $no_map_count = $total_places - $has_map_count;
    
    wp_send_json_success([
        'total' => $total_places,
        'has_map' => $has_map_count,
        'no_map' => $no_map_count
    ]);
}

/**
 * AJAX 핸들러 등록
 */
add_action('wp_ajax_sungsuya_static_map_admin', 'handle_static_map_ajax_request');
?>