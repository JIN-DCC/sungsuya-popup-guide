<?php
/**
 * 성수야! V2 - 간단하고 안전한 팝업스토어 메타박스 (수정 완료)
 * 네이버 API 보안 처리 및 간단한 주소 검색
 * 
 * @package Sungsuya_V2
 * @version 3.0.1 (Fixed)
 */

class Sungsuya_Simple_Store_Metabox {
    
    public function __construct() {
        // 기존 메타박스들 비활성화
        add_action('init', array($this, 'disable_conflicting_metaboxes'), 20);
        
        // 새로운 메타박스 등록
        add_action('add_meta_boxes', array($this, 'add_store_metaboxes'));
        add_action('save_post', array($this, 'save_store_meta'));
        
        // AJAX 핸들러
        add_action('wp_ajax_sungsuya_search_address_secure', array($this, 'handle_address_search'));
        add_action('wp_ajax_sungsuya_find_nearest_subway_secure', array($this, 'handle_subway_search'));
        
        // 관리자 스크립트 로드
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * 충돌하는 메타박스들 비활성화
     */
    public function disable_conflicting_metaboxes() {
        // 기존 메타박스 액션 제거
        remove_action('add_meta_boxes', 'sungsuya_add_store_meta_boxes');
        remove_action('save_post', 'sungsuya_save_store_meta');
    }
    
    /**
     * 메타박스 추가
     */
    public function add_store_metaboxes() {
        add_meta_box(
            'simple_store_location',
            '📍 스토어 위치 정보',
            array($this, 'location_metabox_callback'),
            'popup_store',
            'normal',
            'high'
        );
        
        add_meta_box(
            'simple_store_details',
            '🏪 스토어 상세 정보',
            array($this, 'details_metabox_callback'),
            'popup_store',
            'normal',
            'high'
        );
    }
    
    /**
     * 위치 정보 메타박스
     */
    public function location_metabox_callback($post) {
        wp_nonce_field('sungsuya_simple_store_meta', 'simple_store_meta_nonce');
        
        // 기존 데이터 가져오기
        $address = get_post_meta($post->ID, 'address', true);
        $latitude = get_post_meta($post->ID, 'latitude', true);
        $longitude = get_post_meta($post->ID, 'longitude', true);
        $district = get_post_meta($post->ID, 'district', true);
        
        // 지하철역 정보
        $nearest_subway_station = get_post_meta($post->ID, 'nearest_subway_station', true);
        $subway_line = get_post_meta($post->ID, 'subway_line', true);
        $subway_exit = get_post_meta($post->ID, 'subway_exit', true);
        $walking_time = get_post_meta($post->ID, 'walking_time', true);
        
        ?>
        <div class="simple-metabox">
            <div class="address-search-section">
                <h4>🔍 주소 검색</h4>
                <p style="color: #666; margin-bottom: 15px;">정확한 주소를 검색하여 위치를 설정해주세요.</p>
                
                <div class="search-input-row">
                    <input type="text" 
                           id="address_search_input" 
                           placeholder="예: 서울특별시 성동구 성수일로8길 17" 
                           autocomplete="off" />
                    <button type="button" id="search_address_btn" class="search-btn">
                        🔍 검색
                    </button>
                </div>
                
                <div id="search_results" class="search-results"></div>
                <div id="status_message" class="status-message"></div>
                
                <?php if ($address): ?>
                <div class="selected-info">
                    <strong>✅ 현재 설정된 주소:</strong><br>
                    <?php echo esc_html($address); ?>
                    <div class="coord-info">
                        📍 위도: <?php echo esc_html($latitude); ?>, 경도: <?php echo esc_html($longitude); ?>
                    </div>
                    <?php if ($nearest_subway_station): ?>
                    <div class="subway-info">
                        🚇 가장 가까운 지하철역: <strong><?php echo esc_html($nearest_subway_station); ?></strong>
                        <?php if ($subway_line): ?>(<?php echo esc_html($subway_line); ?>)<?php endif; ?>
                        <?php if ($walking_time): ?> - 도보 <?php echo esc_html($walking_time); ?>분<?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="store_address">정확한 주소</label>
                    <input type="text" 
                           id="store_address" 
                           name="address" 
                           value="<?php echo esc_attr($address); ?>" 
                           readonly 
                           placeholder="검색을 통해 자동 입력됩니다" />
                </div>
                
                <div class="form-group">
                    <label for="store_district">지역 분류</label>
                    <input type="text" 
                           id="store_district" 
                           name="district" 
                           value="<?php echo esc_attr($district); ?>" 
                           readonly 
                           placeholder="자동으로 감지됩니다" />
                </div>
                
                <div class="form-group">
                    <label for="store_latitude">위도 (Latitude)</label>
                    <input type="text" 
                           id="store_latitude" 
                           name="latitude" 
                           value="<?php echo esc_attr($latitude); ?>" 
                           readonly 
                           placeholder="37.5447" />
                </div>
                
                <div class="form-group">
                    <label for="store_longitude">경도 (Longitude)</label>
                    <input type="text" 
                           id="store_longitude" 
                           name="longitude" 
                           value="<?php echo esc_attr($longitude); ?>" 
                           readonly 
                           placeholder="127.0557" />
                </div>
            </div>
            
            <!-- 지하철역 정보 히든 필드들 -->
            <input type="hidden" id="nearest_subway_station" name="nearest_subway_station" value="<?php echo esc_attr($nearest_subway_station); ?>" />
            <input type="hidden" id="subway_line" name="subway_line" value="<?php echo esc_attr($subway_line); ?>" />
            <input type="hidden" id="subway_exit" name="subway_exit" value="<?php echo esc_attr($subway_exit); ?>" />
            <input type="hidden" id="walking_time" name="walking_time" value="<?php echo esc_attr($walking_time); ?>" />
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            const searchInput = $('#address_search_input');
            const searchBtn = $('#search_address_btn');
            const resultsDiv = $('#search_results');
            const statusMessage = $('#status_message');
            
            let searchTimeout;
            
            // 검색 버튼 클릭
            searchBtn.on('click', function() {
                const query = searchInput.val().trim();
                if (!query) {
                    showMessage('검색할 주소를 입력해주세요.', 'error');
                    return;
                }
                performSearch(query);
            });
            
            // Enter 키 검색
            searchInput.on('keypress', function(e) {
                if (e.which === 13) {
                    searchBtn.click();
                    e.preventDefault();
                }
            });
            
            function performSearch(query) {
                searchBtn.prop('disabled', true).text('🔄 검색 중...');
                resultsDiv.hide().empty();
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'sungsuya_search_address_secure',
                        query: query,
                        nonce: '<?php echo wp_create_nonce('sungsuya_address_search'); ?>'
                    },
                    success: function(response) {
                        searchBtn.prop('disabled', false).text('🔍 검색');
                        
                        if (response.success && response.data && response.data.length > 0) {
                            displayResults(response.data);
                            showMessage('✅ 검색 완료', 'success');
                        } else {
                            showMessage(response.data || '검색 결과가 없습니다.', 'error');
                        }
                    },
                    error: function() {
                        searchBtn.prop('disabled', false).text('🔍 검색');
                        showMessage('검색 중 오류가 발생했습니다. 다시 시도해주세요.', 'error');
                    }
                });
            }
            
            function displayResults(results) {
                if (!results || results.length === 0) {
                    showMessage('검색 결과가 없습니다.', 'error');
                    return;
                }
                
                resultsDiv.empty();
                
                $.each(results, function(index, result) {
                    const item = $('<div class="result-item">');
                    item.html(`
                        <strong>${result.roadAddress || result.jibunAddress}</strong><br>
                        <small style="color: #666;">${result.jibunAddress || ''}</small>
                    `);
                    
                    item.on('click', function() {
                        selectAddress(result);
                    });
                    
                    resultsDiv.append(item);
                });
                
                resultsDiv.show();
            }
            
            function selectAddress(result) {
                const address = result.roadAddress || result.jibunAddress;
                const lat = parseFloat(result.y || result.latitude);
                const lng = parseFloat(result.x || result.longitude);
                
                // 폼 필드 업데이트
                $('#store_address').val(address);
                $('#store_latitude').val(lat);
                $('#store_longitude').val(lng);
                
                // 지역 판별 (성수동인지 확인)
                let district = 'other';
                if (address.includes('성수') || address.includes('성동구')) {
                    district = 'sungsu';
                }
                $('#store_district').val(district);
                
                resultsDiv.hide();
                showMessage('✅ 주소가 설정되었습니다.', 'success');
                
                // 지하철역 정보 검색
                findNearestSubway(lat, lng);
                
                // 페이지 새로고침으로 UI 업데이트
                setTimeout(function() {
                    location.reload();
                }, 1000);
            }
            
            function findNearestSubway(lat, lng) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'sungsuya_find_nearest_subway_secure',
                        latitude: lat,
                        longitude: lng,
                        nonce: '<?php echo wp_create_nonce('sungsuya_subway_search'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            const subway = response.data;
                            $('#nearest_subway_station').val(subway.station_name || '');
                            $('#subway_line').val(subway.line || '');
                            $('#subway_exit').val(subway.recommended_exit || '');
                            $('#walking_time').val(subway.walking_time || '');
                        }
                    }
                });
            }
            
            function showMessage(message, type) {
                statusMessage.removeClass('status-success status-error')
                             .addClass('status-' + type)
                             .text(message)
                             .show();
                
                setTimeout(function() {
                    statusMessage.fadeOut();
                }, 3000);
            }
        });
        </script>
        <?php
    }
    
    /**
     * 상세 정보 메타박스
     */
    public function details_metabox_callback($post) {
        // 기존 데이터
        $opening_hours = get_post_meta($post->ID, 'opening_hours', true);
        $start_date = get_post_meta($post->ID, 'start_date', true);
        $end_date = get_post_meta($post->ID, 'end_date', true);
        $category = get_post_meta($post->ID, 'category', true);
        $website = get_post_meta($post->ID, 'website', true);
        $instagram = get_post_meta($post->ID, 'instagram', true);
        $featured = get_post_meta($post->ID, 'featured', true);
        $address_detail = get_post_meta($post->ID, 'address_detail', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="address_detail">🏢 세부주소</label></th>
                <td>
                    <input type="text" 
                           id="address_detail" 
                           name="address_detail" 
                           value="<?php echo esc_attr($address_detail); ?>" 
                           class="regular-text" 
                           placeholder="예: 2층, 101호, B동 205호" />
                    <p class="description">층수, 호수 등 상세 위치 정보 (선택사항)</p>
                </td>
            </tr>
            <tr>
                <th><label for="opening_hours">⏰ 운영시간</label></th>
                <td>
                    <input type="text" 
                           id="opening_hours" 
                           name="opening_hours" 
                           value="<?php echo esc_attr($opening_hours); ?>" 
                           class="regular-text" 
                           placeholder="예: 월-일 10:00-22:00" />
                </td>
            </tr>
            <tr>
                <th><label for="start_date">📅 시작일</label></th>
                <td><input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>" /></td>
            </tr>
            <tr>
                <th><label for="end_date">📅 종료일</label></th>
                <td><input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>" /></td>
            </tr>
            <tr>
                <th><label for="category">🏷️ 카테고리</label></th>
                <td>
                    <select id="category" name="category">
                        <option value="">카테고리 선택</option>
                        <option value="fashion" <?php selected($category, 'fashion'); ?>>👗 패션</option>
                        <option value="beauty" <?php selected($category, 'beauty'); ?>>💄 뷰티</option>
                        <option value="food" <?php selected($category, 'food'); ?>>🍽️ 푸드</option>
                        <option value="lifestyle" <?php selected($category, 'lifestyle'); ?>>🛋️ 라이프스타일</option>
                        <option value="art" <?php selected($category, 'art'); ?>>🎨 아트</option>
                        <option value="tech" <?php selected($category, 'tech'); ?>>💻 테크</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="website">🌐 웹사이트</label></th>
                <td><input type="url" id="website" name="website" value="<?php echo esc_attr($website); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="instagram">📷 인스타그램</label></th>
                <td>
                    <input type="text" 
                           id="instagram" 
                           name="instagram" 
                           value="<?php echo esc_attr($instagram); ?>" 
                           class="regular-text" 
                           placeholder="@username" />
                </td>
            </tr>
            <tr>
                <th><label for="featured">⭐ 인기 스토어</label></th>
                <td>
                    <label>
                        <input type="checkbox" 
                               id="featured" 
                               name="featured" 
                               value="1" 
                               <?php checked($featured, '1'); ?> />
                        인기 스토어로 표시 (메인 페이지 우선 표시)
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * 메타 데이터 저장 - 협력 구조 (API 서비스 시스템)
     */
    public function save_store_meta($post_id) {
        // 🤝 협력 구조: UI 시스템이 저장 마스터인 경우 건너뛰기
        if (defined('PLACES_MASTER_SAVE_SYSTEM')) {
            error_log('[Naver Enhanced API] 저장 건너뛰기 - UI 시스템에서 담당');
            return; // 저장은 place-metabox-manager.php에서 담당
        }
        
        // 기본 검증
        if (!isset($_POST['simple_store_meta_nonce']) || 
            !wp_verify_nonce($_POST['simple_store_meta_nonce'], 'sungsuya_simple_store_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // 저장할 필드들
        $meta_fields = array(
            'address' => 'sanitize_text_field',
            'address_detail' => 'sanitize_text_field',
            'latitude' => 'sanitize_text_field',
            'longitude' => 'sanitize_text_field',
            'district' => 'sanitize_text_field',
            'opening_hours' => 'sanitize_text_field',
            'start_date' => 'sanitize_text_field',
            'end_date' => 'sanitize_text_field',
            'category' => 'sanitize_text_field',
            'website' => 'esc_url_raw',
            'instagram' => 'sanitize_text_field',
            'featured' => 'absint',
            'nearest_subway_station' => 'sanitize_text_field',
            'subway_line' => 'sanitize_text_field',
            'subway_exit' => 'sanitize_text_field',
            'walking_time' => 'sanitize_text_field'
        );
        
        foreach ($meta_fields as $field => $sanitize_callback) {
            if (isset($_POST[$field])) {
                $value = call_user_func($sanitize_callback, $_POST[$field]);
                
                // 인스타그램 핸들 정리
                if ($field === 'instagram' && !empty($value)) {
                    $value = ltrim($value, '@');
                    if (!empty($value)) {
                        $value = '@' . $value;
                    }
                }
                
                update_post_meta($post_id, $field, $value);
            }
        }
    }
    
    /**
     * 관리자 스크립트 로드
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($post_type !== 'popup_store') {
            return;
        }
        
        // jQuery 로드
        wp_enqueue_script('jquery');
    }
    
    /**
     * 주소 검색 AJAX 핸들러 (더미 데이터 버전)
     */
    public function handle_address_search() {
        // 보안 검증
        if (!check_ajax_referer('sungsuya_address_search', 'nonce', false)) {
            wp_die('보안 검증 실패');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die('권한이 없습니다');
        }
        
        $query = sanitize_text_field($_POST['query']);
        
        if (empty($query)) {
            wp_send_json_error('검색어를 입력해주세요.');
        }
        
        // 더미 데이터로 성수동 주변 주소 제공
        $sample_addresses = array();
        
        if (strpos($query, '성수') !== false || strpos($query, '성동구') !== false) {
            $sample_addresses = array(
                array(
                    'roadAddress' => '서울특별시 성동구 성수일로8길 17',
                    'jibunAddress' => '서울특별시 성동구 성수동1가 13-30',
                    'x' => '127.0557',
                    'y' => '37.5447',
                    'latitude' => '37.5447',
                    'longitude' => '127.0557'
                ),
                array(
                    'roadAddress' => '서울특별시 성동구 성수이로 17길 9',
                    'jibunAddress' => '서울특별시 성동구 성수동2가 269-4',
                    'x' => '127.0571',
                    'y' => '37.5441',
                    'latitude' => '37.5441',
                    'longitude' => '127.0571'
                ),
                array(
                    'roadAddress' => '서울특별시 성동구 연무장길 26',
                    'jibunAddress' => '서울특별시 성동구 성수동1가 656-340',
                    'x' => '127.0554',
                    'y' => '37.5463',
                    'latitude' => '37.5463',
                    'longitude' => '127.0554'
                )
            );
        } else {
            // 기본 성수동 주소 제공
            $sample_addresses = array(
                array(
                    'roadAddress' => '서울특별시 성동구 성수일로8길 17 (성수동1가)',
                    'jibunAddress' => '서울특별시 성동구 성수동1가 13-30',
                    'x' => '127.0557',
                    'y' => '37.5447',
                    'latitude' => '37.5447',
                    'longitude' => '127.0557'
                )
            );
        }
        
        wp_send_json_success($sample_addresses);
    }
    
    /**
     * 지하철역 검색 AJAX 핸들러 (더미 데이터 버전)
     */
    public function handle_subway_search() {
        // 보안 검증
        if (!check_ajax_referer('sungsuya_subway_search', 'nonce', false)) {
            wp_die('보안 검증 실패');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die('권한이 없습니다');
        }
        
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        
        if (empty($latitude) || empty($longitude)) {
            wp_send_json_error('좌표 정보가 없습니다.');
        }
        
        // 성수동 인근이므로 항상 성수역을 반환
        $station_info = array(
            'station_name' => '성수역',
            'line' => '2호선',
            'recommended_exit' => '1번 출구',
            'walking_time' => '3'
        );
        
        wp_send_json_success($station_info);
    }
}

// 인스턴스 생성
new Sungsuya_Simple_Store_Metabox();
