<?php
/**
 * 일괄 이미지 크롤링 기능
 * 이미지가 없는 모든 장소를 한번에 크롤링
 * 
 * @package SungsuyaV2
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 일괄 크롤링 페이지 렌더링
 */
function render_bulk_crawling_section() {
    ?>
    <div class="crawling-step" id="step-bulk" style="margin-top: 30px;">
        <h2>
            <span class="dashicons dashicons-images-alt2" style="font-size: 25px;"></span>
            일괄 이미지 크롤링
        </h2>
        
        <div class="step-content">
            <div class="bulk-crawling-info">
                <div class="notice notice-info">
                    <p>
                        <strong>💡 일괄 크롤링 기능</strong><br>
                        이미지가 없는 모든 장소를 자동으로 찾아서 한번에 크롤링합니다.<br>
                        시간이 오래 걸릴 수 있으므로 잠시 기다려주세요.
                    </p>
                </div>
                
                <div id="bulk-status" style="margin: 20px 0;">
                    <!-- 상태가 여기에 표시됩니다 -->
                </div>
                
                <div class="bulk-options">
                    <h3>크롤링 옵션</h3>
                    <table class="form-table">
                        <tr>
                            <th>크롤링 모드</th>
                            <td>
                                <select id="bulk-mode" class="regular-text">
                                    <option value="accurate" selected>🎯 정확 모드 (권장)</option>
                                    <option value="basic">⚡ 기본 모드 (빠름)</option>
                                    <?php if (get_option('google_places_api_key')): ?>
                                    <option value="premium">💎 프리미엄 모드</option>
                                    <?php endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>이미지 개수</th>
                            <td>
                                <select id="bulk-max-images" class="regular-text">
                                    <option value="3">3개</option>
                                    <option value="5" selected>5개</option>
                                    <option value="10">10개</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>동시 처리 개수</th>
                            <td>
                                <select id="bulk-batch-size" class="regular-text">
                                    <option value="1">1개씩 (안정적)</option>
                                    <option value="3" selected>3개씩 (권장)</option>
                                    <option value="5">5개씩 (빠름)</option>
                                </select>
                                <p class="description">동시에 처리할 장소 개수입니다. 서버 성능에 따라 조절하세요.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>저작권 안전 필터</th>
                            <td>
                                <label>
                                    <input type="checkbox" id="bulk-copyright-filter" checked>
                                    저작권 안전 이미지만 수집
                                </label>
                                <p class="description">건물 외관, 간판, 메뉴판 등 일반적으로 사용 가능한 이미지만 수집합니다.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="bulk-actions" style="margin-top: 20px;">
                    <button type="button" id="check-places" class="button">
                        🔍 이미지 없는 장소 확인
                    </button>
                    <button type="button" id="start-bulk-crawl" class="button button-primary button-hero" style="display: none;">
                        🚀 일괄 크롤링 시작
                    </button>
                    <button type="button" id="stop-bulk-crawl" class="button button-secondary" style="display: none;">
                        ⏹️ 중지
                    </button>
                </div>
            </div>
            
            <!-- 진행률 표시 -->
            <div id="bulk-progress" style="display: none; margin-top: 30px;">
                <h3>진행 상황</h3>
                <div class="progress-bar-wrapper" style="background: #f0f0f0; height: 30px; border-radius: 15px; overflow: hidden;">
                    <div id="progress-bar" style="background: #0073aa; height: 100%; width: 0%; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                        0%
                    </div>
                </div>
                <div id="progress-info" style="margin-top: 10px; text-align: center;">
                    <span id="progress-current">0</span> / <span id="progress-total">0</span> 완료
                </div>
            </div>
            
            <!-- 결과 표시 -->
            <div id="bulk-results" style="display: none; margin-top: 30px;">
                <h3>크롤링 결과</h3>
                <div id="bulk-results-content"></div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        let bulkCrawling = {
            isRunning: false,
            places: [],
            currentIndex: 0,
            results: {
                success: 0,
                failed: 0,
                details: []
            },
            abortController: null
        };
        
        // 이미지 없는 장소 확인
        $('#check-places').on('click', function() {
            const $button = $(this);
            $button.prop('disabled', true).text('확인 중...');
            
            console.log('Checking places without images...');
            console.log('AJAX URL:', ajaxurl);
            console.log('Nonce:', '<?php echo wp_create_nonce('integrated_image_crawling'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'check_places_without_images',
                    _ajax_nonce: '<?php echo wp_create_nonce('integrated_image_crawling'); ?>'
                },
                success: function(response) {
                    console.log('AJAX Success:', response);
                    if (response.success) {
                        bulkCrawling.places = response.data.places;
                        
                        let statusHtml = '<div class="notice notice-warning">';
                        statusHtml += '<p><strong>이미지가 없는 장소: ' + response.data.count + '개</strong></p>';
                        
                        if (response.data.count > 0) {
                            statusHtml += '<ul style="margin: 10px 0; max-height: 200px; overflow-y: auto;">';
                            response.data.places.forEach(function(place) {
                                statusHtml += '<li>' + place.title + ' (' + place.type + ')</li>';
                            });
                            statusHtml += '</ul>';
                            
                            $('#start-bulk-crawl').show();
                        } else {
                            statusHtml += '<p>모든 장소에 이미지가 있습니다!</p>';
                        }
                        
                        statusHtml += '</div>';
                        $('#bulk-status').html(statusHtml);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        responseJSON: xhr.responseJSON
                    });
                    alert('오류가 발생했습니다: ' + error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('🔍 이미지 없는 장소 확인');
                }
            });
        });
        
        // 일괄 크롤링 시작
        $('#start-bulk-crawl').on('click', function() {
            if (!confirm('총 ' + bulkCrawling.places.length + '개 장소의 이미지를 크롤링합니다.\n시작하시겠습니까?')) {
                return;
            }
            
            bulkCrawling.isRunning = true;
            bulkCrawling.currentIndex = 0;
            bulkCrawling.results = {
                success: 0,
                failed: 0,
                details: []
            };
            
            // UI 업데이트
            $(this).hide();
            $('#stop-bulk-crawl').show();
            $('#bulk-progress').show();
            $('#progress-total').text(bulkCrawling.places.length);
            
            // 크롤링 시작
            const batchSize = parseInt($('#bulk-batch-size').val());
            processBatch(batchSize);
        });
        
        // 중지 버튼
        $('#stop-bulk-crawl').on('click', function() {
            if (confirm('크롤링을 중지하시겠습니까?')) {
                bulkCrawling.isRunning = false;
                $(this).hide();
                $('#start-bulk-crawl').show();
                showBulkResults();
            }
        });
        
        // 배치 처리 함수
        function processBatch(batchSize) {
            if (!bulkCrawling.isRunning || bulkCrawling.currentIndex >= bulkCrawling.places.length) {
                // 완료
                bulkCrawling.isRunning = false;
                $('#stop-bulk-crawl').hide();
                $('#start-bulk-crawl').show();
                showBulkResults();
                return;
            }
            
            const promises = [];
            const endIndex = Math.min(bulkCrawling.currentIndex + batchSize, bulkCrawling.places.length);
            
            for (let i = bulkCrawling.currentIndex; i < endIndex; i++) {
                const place = bulkCrawling.places[i];
                promises.push(crawlSinglePlace(place));
            }
            
            Promise.all(promises).then(() => {
                bulkCrawling.currentIndex = endIndex;
                updateProgress();
                
                // 다음 배치 처리
                setTimeout(() => processBatch(batchSize), 1000); // 1초 대기
            });
        }
        
        // 단일 장소 크롤링
        function crawlSinglePlace(place) {
            return new Promise((resolve) => {
                const options = {
                    mode: $('#bulk-mode').val(),
                    max_images: $('#bulk-max-images').val(),
                    copyright_filter: $('#bulk-copyright-filter').is(':checked'),
                    save_to_media: true,
                    set_featured: true
                };
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'start_image_crawling',
                        post_id: place.id,
                        options: options,
                        _ajax_nonce: '<?php echo wp_create_nonce('integrated_image_crawling'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            bulkCrawling.results.success++;
                            bulkCrawling.results.details.push({
                                place: place.title,
                                status: 'success',
                                message: response.data.message,
                                images: response.data.images ? response.data.images.length : 0
                            });
                        } else {
                            bulkCrawling.results.failed++;
                            bulkCrawling.results.details.push({
                                place: place.title,
                                status: 'failed',
                                message: response.data.message || '알 수 없는 오류'
                            });
                        }
                    },
                    error: function() {
                        bulkCrawling.results.failed++;
                        bulkCrawling.results.details.push({
                            place: place.title,
                            status: 'failed',
                            message: '네트워크 오류'
                        });
                    },
                    complete: function() {
                        resolve();
                    }
                });
            });
        }
        
        // 진행률 업데이트
        function updateProgress() {
            const percent = Math.round((bulkCrawling.currentIndex / bulkCrawling.places.length) * 100);
            $('#progress-bar').css('width', percent + '%').text(percent + '%');
            $('#progress-current').text(bulkCrawling.currentIndex);
        }
        
        // 결과 표시
        function showBulkResults() {
            let html = '<div class="notice notice-info">';
            html += '<p><strong>크롤링 완료</strong></p>';
            html += '<p>성공: ' + bulkCrawling.results.success + '개 / 실패: ' + bulkCrawling.results.failed + '개</p>';
            html += '</div>';
            
            if (bulkCrawling.results.details.length > 0) {
                html += '<table class="widefat striped" style="margin-top: 20px;">';
                html += '<thead><tr><th>장소</th><th>상태</th><th>결과</th></tr></thead>';
                html += '<tbody>';
                
                bulkCrawling.results.details.forEach(function(detail) {
                    const statusClass = detail.status === 'success' ? 'color: #46b450;' : 'color: #dc3232;';
                    html += '<tr>';
                    html += '<td>' + detail.place + '</td>';
                    html += '<td style="' + statusClass + '">' + (detail.status === 'success' ? '✅ 성공' : '❌ 실패') + '</td>';
                    html += '<td>' + detail.message;
                    if (detail.images) {
                        html += ' (' + detail.images + '개 이미지)';
                    }
                    html += '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
            }
            
            $('#bulk-results-content').html(html);
            $('#bulk-results').show();
        }
    });
    </script>

    <style>
    #bulk-status ul {
        list-style: none;
        padding-left: 20px;
    }
    
    #bulk-status li {
        padding: 2px 0;
    }
    
    #bulk-status li:before {
        content: "• ";
        color: #666;
    }
    
    .bulk-crawling-info .form-table th {
        width: 150px;
    }
    
    .progress-bar-wrapper {
        margin-top: 20px;
        position: relative;
    }
    
    #bulk-results table {
        font-size: 14px;
    }
    
    #bulk-results tbody tr:hover {
        background: #f5f5f5;
    }
    </style>
    <?php
}

// AJAX 핸들러 - 이미지 없는 장소 확인
add_action('wp_ajax_check_places_without_images', 'handle_check_places_without_images');
add_action('wp_ajax_nopriv_check_places_without_images', 'handle_check_places_without_images'); // 로그아웃 사용자용 (필요시)

// 디버깅: 액션이 제대로 등록되었는지 확인
add_action('init', function() {
    error_log('=== Bulk crawling feature init ===');
    error_log('Current user can edit posts: ' . (current_user_can('edit_posts') ? 'YES' : 'NO'));
});

function handle_check_places_without_images() {
    error_log('=== check_places_without_images AJAX handler called ===');
    
    // Nonce 검증
    if (!check_ajax_referer('integrated_image_crawling', '_ajax_nonce', false)) {
        error_log('Nonce verification failed');
        wp_send_json_error('보안 검증 실패');
        return;
    }
    
    error_log('Nonce verification passed');
    
    // 이미지가 없는 장소 조회
    $args = array(
        'post_type' => 'places',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_thumbnail_id',
                'compare' => 'NOT EXISTS'
            )
        ),
        'orderby' => 'title',
        'order' => 'ASC'
    );
    
    error_log('Query args: ' . print_r($args, true));
    
    $places = get_posts($args);
    $places_data = array();
    
    error_log('Found ' . count($places) . ' places total');
    
    foreach ($places as $place) {
        // 갤러리 이미지도 확인
        $gallery = get_post_meta($place->ID, 'place_images', true);
        if (empty($gallery) || !is_array($gallery) || count($gallery) == 0) {
            $place_type = wp_get_post_terms($place->ID, 'place_type', array('fields' => 'names'));
            $type_label = !empty($place_type) ? $place_type[0] : '미분류';
            
            $places_data[] = array(
                'id' => $place->ID,
                'title' => $place->post_title,
                'type' => $type_label
            );
        }
    }
    
    error_log('Places without images: ' . count($places_data));
    
    wp_send_json_success(array(
        'count' => count($places_data),
        'places' => $places_data
    ));
}
