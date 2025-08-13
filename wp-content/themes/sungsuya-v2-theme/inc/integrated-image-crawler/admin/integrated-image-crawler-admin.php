<?php
/**
 * 통합 이미지 크롤링 관리자 페이지
 * 
 * @package SungsuyaV2
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// 통합 크롤러 로드
require_once SUNGSUYA_THEME_DIR . '/inc/integrated-image-crawler/class-image-crawler-factory.php';

// 일괄 크롤링 기능 로드 (AJAX 핸들러 포함) - 반드시 여기서 로드해야 함
require_once dirname(__FILE__) . '/bulk-crawling-feature.php';

// 메뉴 추가 - reorganized-admin-menu.php에서 통합 관리
// add_action('admin_menu', 'integrated_image_crawling_menu', 25);

function integrated_image_crawling_menu() {
    add_submenu_page(
        'edit.php?post_type=places',
        '통합 이미지 크롤링',
        '🖼️ 통합 이미지 크롤링',
        'manage_options',
        'integrated-image-crawling',
        'render_integrated_image_crawling_page'
    );
}

/**
 * 관리자 페이지 렌더링
 */
function render_integrated_image_crawling_page() {
    $crawler = integrated_image_crawler();
    $modes = $crawler->get_mode_info();
    
    // Google API 키 확인
    $google_api_key = get_option('google_places_api_key');
    $has_google_api = !empty($google_api_key);
    ?>
    <div class="wrap integrated-image-crawling">
        <h1>
            <span class="dashicons dashicons-format-gallery" style="font-size: 30px; margin-right: 10px;"></span>
            통합 이미지 크롤링 시스템
        </h1>
        
        <div class="notice notice-info">
            <p>
                <strong>🎉 통합 버전</strong><br>
                3가지 크롤링 모드를 한 곳에서 사용할 수 있습니다. 상황에 맞는 모드를 선택하세요.
            </p>
        </div>

        <!-- Step 1: 장소 선택 -->
        <div class="crawling-step" id="step-place">
            <h2>1단계: 장소 선택</h2>
            <div class="step-content">
                <select id="place-select" class="widefat" style="max-width: 600px;">
                    <option value="">장소를 선택하세요...</option>
                    <?php
                    $places = get_posts(array(
                        'post_type' => 'places',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC',
                        'post_status' => 'publish'
                    ));
                    
                    foreach ($places as $place) {
                        $has_thumbnail = has_post_thumbnail($place->ID);
                        $place_type = wp_get_post_terms($place->ID, 'place_type', array('fields' => 'names'));
                        $type_label = !empty($place_type) ? ' (' . implode(', ', $place_type) . ')' : '';
                        ?>
                        <option value="<?php echo $place->ID; ?>" 
                                data-has-thumbnail="<?php echo $has_thumbnail ? '1' : '0'; ?>">
                            <?php echo esc_html($place->post_title . $type_label); ?>
                            <?php echo $has_thumbnail ? ' ✓' : ''; ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
                
                <!-- 디버깅 버튼 추가 -->
                <div style="margin-top: 10px;">
                    <button type="button" onclick="debugPlaceSelect()" class="button">
                        🐛 디버그: 다음 단계 강제 표시
                    </button>
                </div>
                
                <div id="place-info" style="margin-top: 20px; display: none;">
                    <div class="card">
                        <h3 id="place-title"></h3>
                        <p id="place-address"></p>
                        <div id="current-images"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: 모드 선택 -->
        <div class="crawling-step" id="step-mode" style="display: none;">
            <h2>2단계: 크롤링 모드 선택</h2>
            <div class="step-content">
                <div class="mode-selector">
                    <?php foreach ($modes as $mode_key => $mode_info): ?>
                    <div class="mode-card <?php echo isset($mode_info['recommended']) ? 'recommended' : ''; ?> 
                                          <?php echo (isset($mode_info['requires_api']) && !$has_google_api) ? 'disabled' : ''; ?>"
                         data-mode="<?php echo esc_attr($mode_key); ?>">
                        
                        <?php if (isset($mode_info['recommended'])): ?>
                        <div class="recommended-badge">추천</div>
                        <?php endif; ?>
                        
                        <h3>
                            <span class="mode-icon"><?php echo $mode_info['icon']; ?></span>
                            <?php echo esc_html($mode_info['name']); ?>
                        </h3>
                        
                        <p class="mode-description">
                            <?php echo esc_html($mode_info['description']); ?>
                        </p>
                        
                        <div class="mode-pros-cons">
                            <div class="pros">
                                <strong>장점:</strong>
                                <ul>
                                    <?php foreach ($mode_info['pros'] as $pro): ?>
                                    <li>✓ <?php echo esc_html($pro); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php if (!empty($mode_info['cons'])): ?>
                            <div class="cons">
                                <strong>단점:</strong>
                                <ul>
                                    <?php foreach ($mode_info['cons'] as $con): ?>
                                    <li>- <?php echo esc_html($con); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($mode_info['requires_api']) && !$has_google_api): ?>
                        <div class="api-required">
                            <p>⚠️ Google Places API 키 필요</p>
                            <a href="<?php echo admin_url('admin.php?page=api-settings'); ?>" class="button button-small">
                                API 설정하기
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <button class="button select-mode-btn" 
                                data-mode="<?php echo esc_attr($mode_key); ?>"
                                <?php echo (isset($mode_info['requires_api']) && !$has_google_api) ? 'disabled' : ''; ?>>
                            이 모드 선택
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Step 3: 옵션 설정 -->
        <div class="crawling-step" id="step-options" style="display: none;">
            <h2>3단계: 크롤링 옵션</h2>
            <div class="step-content">
                <form id="crawling-options">
                    <table class="form-table">
                        <tr>
                            <th>이미지 개수</th>
                            <td>
                                <select name="max_images" id="max-images">
                                    <option value="3">3개</option>
                                    <option value="5" selected>5개</option>
                                    <option value="10">10개</option>
                                    <option value="15">15개</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>최소 이미지 크기</th>
                            <td>
                                너비 <input type="number" name="min_width" value="400" style="width: 80px;"> px
                                × 
                                높이 <input type="number" name="min_height" value="300" style="width: 80px;"> px
                            </td>
                        </tr>
                        <tr>
                            <th>추가 옵션</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="use_cache" value="1" checked>
                                    캐시 사용 (빠른 결과)
                                </label><br>
                                <label>
                                    <input type="checkbox" name="save_to_media" value="1" checked>
                                    미디어 라이브러리에 저장
                                </label><br>
                                <label>
                                    <input type="checkbox" name="set_featured" value="1" checked>
                                    첫 번째 이미지를 대표 이미지로 설정
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="crawling-actions">
                        <button type="button" id="test-crawl" class="button">
                            🔍 미리보기
                        </button>
                        <button type="button" id="start-crawl" class="button button-primary button-hero">
                            🚀 크롤링 시작
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 결과 영역 -->
        <div id="crawling-results" style="display: none;">
            <h2>크롤링 결과</h2>
            <div id="results-content"></div>
        </div>

        <!-- 에러 로그 영역 -->
        <div id="error-log-container" style="display: none; margin-top: 20px;">
            <div class="notice notice-error">
                <h3>오류 상세 정보</h3>
                <pre id="error-log" style="background: #f1f1f1; padding: 10px; overflow-x: auto;"></pre>
            </div>
        </div>

        <!-- 로딩 오버레이 -->
        <div id="crawling-loading" style="display: none;">
            <div class="loading-content">
                <div class="spinner is-active"></div>
                <p>이미지를 크롤링하는 중입니다...</p>
                <p class="loading-status"></p>
            </div>
        </div>
        
        <?php 
        // 일괄 크롤링 섹션 포함
        require_once dirname(__FILE__) . '/bulk-crawling-feature.php';
        render_bulk_crawling_section();
        ?>
    </div>

    <style>
    .integrated-image-crawling .crawling-step {
        background: white;
        padding: 20px;
        margin: 20px 0;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .integrated-image-crawling .step-content {
        margin-top: 20px;
    }
    
    .integrated-image-crawling .card {
        background: #f5f5f5;
        padding: 15px;
        border-radius: 5px;
    }
    
    /* 모드 선택 카드 */
    .mode-selector {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .mode-card {
        background: white;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        position: relative;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .mode-card:hover {
        border-color: #0073aa;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .mode-card.recommended {
        border-color: #46b450;
    }
    
    .mode-card.disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .mode-card.disabled:hover {
        transform: none;
        border-color: #e0e0e0;
    }
    
    .recommended-badge {
        position: absolute;
        top: -10px;
        right: 20px;
        background: #46b450;
        color: white;
        padding: 5px 15px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .mode-icon {
        font-size: 30px;
        margin-right: 10px;
    }
    
    .mode-description {
        color: #666;
        margin: 10px 0;
    }
    
    .mode-pros-cons {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin: 15px 0;
        font-size: 13px;
    }
    
    .mode-pros-cons ul {
        margin: 5px 0;
        padding-left: 0;
        list-style: none;
    }
    
    .mode-pros-cons li {
        margin: 3px 0;
    }
    
    .pros {
        color: #46b450;
    }
    
    .cons {
        color: #dc3232;
    }
    
    .select-mode-btn {
        width: 100%;
        margin-top: 15px;
    }
    
    .api-required {
        background: #fef7e7;
        border: 1px solid #f0c33c;
        padding: 10px;
        border-radius: 5px;
        margin: 15px 0;
        text-align: center;
    }
    
    /* 크롤링 액션 */
    .crawling-actions {
        margin-top: 20px;
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    /* 로딩 오버레이 */
    #crawling-loading {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.8);
        z-index: 100000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .loading-content {
        background: white;
        padding: 40px;
        border-radius: 8px;
        text-align: center;
    }
    
    .loading-content .spinner {
        float: none;
        margin: 0 auto 20px;
    }
    
    /* 결과 그리드 */
    .image-results-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .image-result-item {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .image-result-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .image-result-item img {
        width: 100%;
        height: 150px;
        object-fit: cover;
    }
    
    .image-result-info {
        padding: 10px;
    }
    
    .image-result-title {
        font-size: 13px;
        margin: 0 0 5px 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .image-result-meta {
        font-size: 11px;
        color: #666;
    }
    
    .image-score {
        display: inline-block;
        background: #0073aa;
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
    }
    
    .image-source {
        float: right;
        color: #999;
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // ajaxurl 확인
        if (typeof ajaxurl === 'undefined') {
            console.error('ajaxurl is not defined!');
            ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        }
        console.log('ajaxurl:', ajaxurl);
        
        let selectedPlace = null;
        let selectedMode = null;
        
        // 1. 장소 선택
        $('#place-select').on('change', function() {
            console.log('Place select changed');
            const placeId = $(this).val();
            console.log('Selected place ID:', placeId);
            
            if (!placeId) {
                $('#place-info').hide();
                $('#step-mode').hide();
                return;
            }
            
            selectedPlace = placeId;
            
            // 장소 정보 로드
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_place_info',
                    post_id: placeId,
                    _ajax_nonce: '<?php echo wp_create_nonce('integrated_image_crawling'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#place-title').text(response.data.title);
                        $('#place-address').text(response.data.address);
                        
                        if (response.data.images.length > 0) {
                            let imagesHtml = '<p><strong>현재 이미지:</strong></p><div style="display: flex; gap: 10px; flex-wrap: wrap;">';
                            response.data.images.forEach(function(img) {
                                imagesHtml += '<img src="' + img.url + '" style="width: 100px; height: 100px; object-fit: cover; border-radius: 5px;">';
                            });
                            imagesHtml += '</div>';
                            $('#current-images').html(imagesHtml);
                        } else {
                            $('#current-images').html('<p style="color: #999;">현재 이미지가 없습니다.</p>');
                        }
                        
                        $('#place-info').show();
                        $('#step-mode').show();
                    } else {
                        console.error('Place info error:', response);
                        alert('장소 정보를 가져오는데 실패했습니다: ' + (response.data || '알 수 없는 오류'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    alert('장소 정보를 가져오는 중 오류가 발생했습니다.');
                    $('#place-info').hide();
                    $('#step-mode').hide();
                }
            });
        });
        
        // 2. 모드 선택
        $('.select-mode-btn:not(:disabled)').on('click', function() {
            selectedMode = $(this).data('mode');
            $('.mode-card').removeClass('selected');
            $(this).closest('.mode-card').addClass('selected');
            $('#step-options').show();
            
            // 모드별 옵션 조정
            if (selectedMode === 'premium') {
                $('#max-images option[value="15"]').remove();
                $('#max-images').val('5');
            }
        });
        
        // 3. 미리보기
        $('#test-crawl').on('click', function() {
            if (!selectedPlace || !selectedMode) {
                alert('장소와 모드를 먼저 선택해주세요.');
                return;
            }
            
            $('#crawling-loading').show();
            $('.loading-status').text('미리보기 생성 중...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'test_image_crawling',
                    post_id: selectedPlace,
                    mode: selectedMode,
                    _ajax_nonce: '<?php echo wp_create_nonce('integrated_image_crawling'); ?>'
                },
                success: function(response) {
                    $('#crawling-loading').hide();
                    
                    if (response.success) {
                        showResults(response.data, true);
                    } else {
                        showError(response.data || '미리보기 생성에 실패했습니다.');
                    }
                },
                error: function(xhr, status, error) {
                    $('#crawling-loading').hide();
                    console.error('Test Crawl AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    alert('미리보기 오류가 발생했습니다.\n\n' + 
                          '상태: ' + status + '\n' +
                          '오류: ' + error + '\n\n' +
                          '자세한 내용은 브라우저 콘솔(F12)을 확인하세요.');
                }
            });
        });
        
        // 4. 크롤링 시작
        $('#start-crawl').on('click', function() {
            if (!selectedPlace || !selectedMode) {
                alert('장소와 모드를 먼저 선택해주세요.');
                return;
            }
            
            if (!confirm('이미지 크롤링을 시작하시겠습니까?')) {
                return;
            }
            
            const options = {
                mode: selectedMode,
                max_images: $('#max-images').val(),
                min_width: $('input[name="min_width"]').val(),
                min_height: $('input[name="min_height"]').val(),
                use_cache: $('input[name="use_cache"]').is(':checked') ? 1 : 0,
                save_to_media: $('input[name="save_to_media"]').is(':checked') ? 1 : 0,
                set_featured: $('input[name="set_featured"]').is(':checked') ? 1 : 0
            };
            
            $('#crawling-loading').show();
            $('.loading-status').text('이미지 크롤링 중...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'start_image_crawling',
                    post_id: selectedPlace,
                    options: options,
                    _ajax_nonce: '<?php echo wp_create_nonce('integrated_image_crawling'); ?>'
                },
                success: function(response) {
                    $('#crawling-loading').hide();
                    
                    if (response.success) {
                        showResults(response.data, false);
                        
                        // 장소 선택 새로고침
                        $('#place-select').trigger('change');
                    } else {
                        const errorMsg = response.data.message || response.data || '크롤링에 실패했습니다.';
                        showError(errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    $('#crawling-loading').hide();
                    console.error('Start Crawl AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    alert('크롤링 오류가 발생했습니다.\n\n' + 
                          '상태: ' + status + '\n' +
                          '오류: ' + error + '\n\n' +
                          '자세한 내용은 브라우저 콘솔(F12)을 확인하세요.');
                }
            });
        });
        
        // 결과 표시
        function showResults(data, isPreview) {
            let html = '<div class="notice notice-' + (data.success ? 'success' : 'error') + '">';
            html += '<p>' + data.message + '</p>';
            html += '</div>';
            
            if (data.images && data.images.length > 0) {
                html += '<div class="image-results-grid">';
                
                data.images.forEach(function(img) {
                    html += '<div class="image-result-item">';
                    html += '<img src="' + img.url + '" alt="' + (img.title || '') + '">';
                    html += '<div class="image-result-info">';
                    html += '<p class="image-result-title">' + (img.title || '제목 없음') + '</p>';
                    html += '<div class="image-result-meta">';
                    
                    if (img.score) {
                        html += '<span class="image-score">점수: ' + img.score + '</span> ';
                    }
                    
                    if (img.source) {
                        html += '<span class="image-source">' + img.source + '</span>';
                    }
                    
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                });
                
                html += '</div>';
                
                if (isPreview) {
                    html += '<div class="notice notice-info" style="margin-top: 20px;">';
                    html += '<p>이것은 미리보기입니다. 실제 크롤링을 시작하려면 "크롤링 시작" 버튼을 클릭하세요.</p>';
                    html += '</div>';
                }
            }
            
            $('#results-content').html(html);
            $('#crawling-results').show();
            
            // 결과로 스크롤
            $('html, body').animate({
                scrollTop: $('#crawling-results').offset().top - 100
            }, 500);
        }
        
        // 에러 표시 함수
        function showError(message) {
            const errorHtml = '<div class="notice notice-error"><p>' + message + '</p></div>';
            $('#results-content').html(errorHtml);
            $('#crawling-results').show();
            
            // 콘솔 에러도 에러 로그 영역에 표시
            if (window.console && console.error) {
                $('#error-log').text(message);
                $('#error-log-container').show();
            }
        }
    });
    
    // 디버그 함수 (전역 스코프)
    function debugPlaceSelect() {
        console.log('Debug: Forcing step 2 to show');
        const placeId = jQuery('#place-select').val();
        
        if (!placeId) {
            alert('먼저 장소를 선택해주세요.');
            return;
        }
        
        // 장소 정보 수동 설정
        jQuery('#place-title').text('테스트 장소');
        jQuery('#place-address').text('테스트 주소');
        jQuery('#current-images').html('<p style="color: #999;">현재 이미지가 없습니다.</p>');
        
        // 단계 표시
        jQuery('#place-info').show();
        jQuery('#step-mode').show();
        
        console.log('Debug: Steps should be visible now');
    }
    </script>
    <?php
}

// AJAX 핸들러 - 장소 정보 가져오기
add_action('wp_ajax_get_place_info', 'handle_get_place_info');
function handle_get_place_info() {
    try {
        // Nonce 검증
        if (!check_ajax_referer('integrated_image_crawling', '_ajax_nonce', false)) {
            wp_send_json_error('보안 검증 실패');
            return;
        }
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error('장소를 찾을 수 없습니다.');
            return;
        }
        
        // 주소 가져오기 - 여러 필드 확인
        $address = get_post_meta($post_id, 'formatted_address', true);
        if (empty($address)) {
            $address = get_post_meta($post_id, 'place_address', true);
        }
        if (empty($address)) {
            $address = get_post_meta($post_id, 'address', true);
        }
        if (empty($address)) {
            $address = '주소 정보 없음';
        }
        
        // 현재 이미지들
        $images = array();
        
        // 대표 이미지
        if (has_post_thumbnail($post_id)) {
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium');
            if ($thumbnail_url) {
                $images[] = array(
                    'id' => get_post_thumbnail_id($post_id),
                    'url' => $thumbnail_url
                );
            }
        }
        
        // 갤러리 이미지
        $gallery = get_post_meta($post_id, 'place_images', true);
        if (!empty($gallery) && is_array($gallery)) {
            foreach ($gallery as $img_id) {
                if ($img_id != get_post_thumbnail_id($post_id)) {
                    $img_url = wp_get_attachment_image_url($img_id, 'medium');
                    if ($img_url) {
                        $images[] = array(
                            'id' => $img_id,
                            'url' => $img_url
                        );
                    }
                }
            }
        }
        
        wp_send_json_success(array(
            'title' => $post->post_title,
            'address' => $address,
            'images' => $images
        ));
        
    } catch (Exception $e) {
        error_log('Get place info error: ' . $e->getMessage());
        wp_send_json_error('서버 오류: ' . $e->getMessage());
    }
}

// AJAX 핸들러 - 테스트 크롤링
add_action('wp_ajax_test_image_crawling', 'handle_test_image_crawling');
function handle_test_image_crawling() {
    try {
        // Nonce 검증
        if (!check_ajax_referer('integrated_image_crawling', '_ajax_nonce', false)) {
            wp_send_json_error('보안 검증 실패');
            return;
        }
        
        $post_id = intval($_POST['post_id']);
        $mode = sanitize_text_field($_POST['mode']);
        
        if (!$post_id) {
            wp_send_json_error('유효하지 않은 장소 ID');
            return;
        }
        
        if (!in_array($mode, array('basic', 'accurate', 'premium'))) {
            wp_send_json_error('유효하지 않은 크롤링 모드: ' . $mode);
            return;
        }
        
        $crawler = integrated_image_crawler();
        
        // 디버깅용 로그
        error_log('Test crawling - Post ID: ' . $post_id . ', Mode: ' . $mode);
        
        $results = $crawler->test_crawl($post_id, $mode);
        
        if (!empty($results)) {
            wp_send_json_success(array(
                'success' => true,
                'message' => count($results) . '개의 이미지를 찾았습니다. (미리보기)',
                'images' => $results,
                'mode' => $mode
            ));
        } else {
            wp_send_json_error('이미지를 찾을 수 없습니다.');
        }
    } catch (Exception $e) {
        error_log('Test crawling error: ' . $e->getMessage());
        wp_send_json_error('서버 오류: ' . $e->getMessage());
    }
}

// AJAX 핸들러 - 실제 크롤링
add_action('wp_ajax_start_image_crawling', 'handle_start_image_crawling');
function handle_start_image_crawling() {
    try {
        // Nonce 검증
        if (!check_ajax_referer('integrated_image_crawling', '_ajax_nonce', false)) {
            wp_send_json_error('보안 검증 실패');
            return;
        }
        
        $post_id = intval($_POST['post_id']);
        $options = $_POST['options'];
        
        if (!$post_id) {
            wp_send_json_error('유효하지 않은 장소 ID');
            return;
        }
        
        // 옵션 정리
        $clean_options = array(
            'mode' => sanitize_text_field($options['mode']),
            'max_images' => intval($options['max_images']),
            'min_width' => intval($options['min_width']),
            'min_height' => intval($options['min_height']),
            'use_cache' => (bool) $options['use_cache'],
            'save_to_media' => (bool) $options['save_to_media'],
            'set_featured' => (bool) $options['set_featured']
        );
        
        // 디버깅용 로그
        error_log('Start crawling - Post ID: ' . $post_id . ', Options: ' . print_r($clean_options, true));
        
        $crawler = integrated_image_crawler();
        $result = $crawler->crawl($post_id, $clean_options);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    } catch (Exception $e) {
        error_log('Start crawling error: ' . $e->getMessage());
        wp_send_json_error(array(
            'message' => '서버 오류: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        ));
    }
}
