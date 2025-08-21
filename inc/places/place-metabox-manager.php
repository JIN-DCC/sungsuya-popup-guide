<?php
/**
 * 성수야! V2 - Places 동적 메타박스 관리자
 * 
 * 장소 유형별 동적 필드 관리 시스템
 * - 기존 네이버 지오코딩 시스템 재활용
 * - 깔끔한 UI/UX로 유형별 필드 토글
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

// 메모리 한도 증가 (Places 시스템용)
ini_set('memory_limit', '512M');

class PlacesMetaboxManager {
    
    /**
     * 생성자
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_metaboxes'));
        add_action('save_post', array($this, 'save_meta'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX 핸들러 추가 (Places용) - 통합 지도생성 시스템 도입으로 주소검색 비활성화
        // add_action('wp_ajax_places_search_address', array($this, 'ajax_places_search_address'));
        add_action('wp_ajax_places_get_subway_info', array($this, 'ajax_get_subway_info'));
        add_action('wp_ajax_places_integrated_geocoding', array($this, 'ajax_integrated_geocoding'));
        
        // 정적 지도 자동 생성 훅 추가 (Places 저장시)
        add_action('save_post', array($this, 'auto_generate_static_map'), 20, 2);
    }
    
    /**
     * 통합 지도생성 시스템 연동 AJAX 핸들러
     */
    public function ajax_integrated_geocoding() {
        // 보안 검증
        if (!check_ajax_referer('places_ajax_nonce', 'nonce', false)) {
            wp_send_json_error('보안 토큰이 유효하지 않습니다.');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('권한이 없습니다.');
        }
        
        $address = sanitize_text_field($_POST['address'] ?? '');
        if (empty($address)) {
            wp_send_json_error('주소를 입력해주세요.');
        }
        
        // 네이버 지오코딩 API 호출
        $api_client_id = get_option('sungsuya_naver_client_id', '');
        $api_client_secret = get_option('sungsuya_naver_client_secret', '');
        
        if (empty($api_client_id) || empty($api_client_secret)) {
            // API 키가 없으면 서울 성수동 기본 좌표 반환
            wp_send_json_success(array(
                'latitude' => '37.5445',
                'longitude' => '127.0557',
                'address' => $address,
                'note' => 'API 키가 설정되지 않아 기본 좌표를 사용합니다.'
            ));
            return;
        }
        
        // 네이버 지오코딩 API 요청
        $geocoding_url = 'https://naveropenapi.apigw.ntruss.com/map-geocode/v2/geocode';
        $params = array(
            'query' => $address,
            'coordinate' => 'latlng'
        );
        
        $response = wp_remote_get($geocoding_url . '?' . http_build_query($params), array(
            'headers' => array(
                'X-NCP-APIGW-API-KEY-ID' => $api_client_id,
                'X-NCP-APIGW-API-KEY' => $api_client_secret
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('지오코딩 API 요청 실패: ' . $response->get_error_message());
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['addresses']) && !empty($data['addresses'])) {
            $result = $data['addresses'][0];
            
            wp_send_json_success(array(
                'latitude' => $result['y'],
                'longitude' => $result['x'],
                'address' => $result['roadAddress'] ?: $result['jibunAddress'],
                'roadAddress' => $result['roadAddress'],
                'jibunAddress' => $result['jibunAddress']
            ));
        } else {
            wp_send_json_error('주소를 찾을 수 없습니다. 더 정확한 주소를 입력해주세요.');
        }
    }
    
    /**
     * 메타박스 등록
     */
    public function add_metaboxes() {
        add_meta_box(
            'places_dynamic_fields',
            '장소 정보 입력',
            array($this, 'render_metabox'),
            'places',
            'normal',
            'high'
        );
    }
    
    /**
     * 스크립트 및 스타일 로드
     */
    public function enqueue_scripts($hook) {
        if ($hook === 'post-new.php' || $hook === 'post.php') {
            global $post_type;
            if ($post_type === 'places') {
                // CSS
                wp_enqueue_style(
                    'places-metabox-style',
                    get_template_directory_uri() . '/assets/css/admin/places-metabox.css',
                    array(),
                    SUNGSUYA_VERSION
                );
                
                // JavaScript
                wp_enqueue_script(
                    'places-metabox-script',
                    get_template_directory_uri() . '/assets/js/admin/places-metabox.js',
                    array('jquery'),
                    SUNGSUYA_VERSION,
                    true
                );
                
                // 네이버 지도 API (functions.php의 충돌 방지를 위해 완전히 새로운 방식)
                $naver_client_id = get_option('sungsuya_naver_client_id', '');
                $maps_configured = !empty($naver_client_id);
                
                if ($maps_configured) {
                    // ✅ 팝업스토어와 100% 동일한 단일 API 로딩 방식
                    wp_enqueue_script(
                        'naver-maps-api-places-unified',
                        "https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId={$naver_client_id}&submodules=geocoder&callback=naverMapsPlacesUnifiedReady",
                        array(),
                        null,
                        false // header에 로드
                    );
                    
                    error_log('✅ [Places 메타박스] 통합 API 로딩 방식 적용: ' . $naver_client_id);
                } else {
                    error_log('❌ [Places 메타박스] 네이버 API 키 없음 - 더미 데이터 모드');
                }
                
                // AJAX 설정 지역화 (디버깅 정보 추가)
                wp_localize_script('places-metabox-script', 'placesMetabox', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('places_ajax_nonce'), // JavaScript에서 사용하는 nonce 이름과 일치
                    'debug' => defined('WP_DEBUG') && WP_DEBUG,
                    'naverMapsConfigured' => $maps_configured,
                    'naverClientId' => $naver_client_id,
                    'strings' => array(
                        'selectType' => '장소 유형을 선택해주세요',
                        'addressRequired' => '주소를 입력해주세요',
                        'geocodingError' => '주소를 찾을 수 없습니다. 정확한 주소를 입력해주세요.',
                        'geocodingSuccess' => '주소가 확인되었습니다!',
                        'integratedSystemNotice' => '좌표는 통합 지도생성 시스템에서 자동 처리됩니다.'
                    )
                ));
            }
        }
    }
    
    /**
     * 메타박스 렌더링
     */
    public function render_metabox($post) {
        // Nonce 필드 추가
        wp_nonce_field('places_metabox_save', 'places_metabox_nonce');
        
        // 현재 저장된 값들 가져오기
        $current_type = $this->get_current_place_type($post->ID);
        $meta_data = $this->get_meta_data($post->ID);
        
        ?>
        <div id="places-metabox-container">
            
            <!-- 제목 입력 도우미 -->
            <div class="places-field-group title-helper-group">
                <h3>🏷️ 장소명 (제목)</h3>
                <?php $this->render_title_helper($post); ?>
            </div>
            
            <!-- 장소 유형 선택 -->
            <div class="places-field-group">
                <h3>📍 장소 유형 선택</h3>
                <div class="place-type-selector">
                    <?php $this->render_type_selector($current_type); ?>
                </div>
            </div>
            
            <!-- 기본 정보와 지도를 나란히 배치 -->
            <div class="places-field-group address-and-map">
                <div class="address-fields-container">
                    <h3>🏢 기본 정보</h3>
                    <?php $this->render_common_fields($meta_data); ?>
                </div>
                
                <div class="map-container">
                    <h4>🗺️ 위치 미리보기</h4>
                    <div id="places-map-preview">
                        <div id="map-placeholder">
                        📍 주소 입력 시<br>위치가 표시됩니다
                        </div>
                    </div>
                    
                    <!-- 지도 정보 패널 -->
                    <div id="map-info-panel" class="map-info-panel" style="display: none;">
                        <div class="map-info-item">
                            <span class="map-info-label">📍 좌표</span>
                            <span class="map-info-value" id="coordinate-display">위치 확인됨</span>
                        </div>
                        <div class="map-info-item subway-info" id="subway-info" style="display: none;">
                            <span class="map-info-label">🚇 지하철</span>
                            <span class="map-info-value" id="subway-display">-</span>
                        </div>
                        <div class="map-info-item">
                            <span class="map-info-label">⏱️ 도보시간</span>
                            <span class="map-info-value" id="walking-time-display">-</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 동적 필드 (유형별로 표시/숨김) -->
            <div id="dynamic-fields-container">
                <?php $this->render_dynamic_fields($current_type, $meta_data); ?>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * 🆆 제목 입력 도우미 렌더링 (R3 개선사항)
     */
    private function render_title_helper($post) {
        $current_title = $post->post_title;
        ?>
        <div class="title-helper-container">
            <div class="form-group">
                <label for="place_title_helper">장소명</label>
                <input type="text" 
                       id="place_title_helper" 
                       name="place_title_helper" 
                       value="<?php echo esc_attr($current_title); ?>" 
                       class="form-control title-helper-input"
                       placeholder="예: 젠틀몸스터 성수점, 카페 온더코너, 성수역">
                <small class="form-text text-muted">이 값은 자동으로 포스트 제목에 반영됩니다.</small>
            </div>
            
            <div class="title-sync-status" id="title-sync-status">
                <span class="sync-indicator" id="sync-indicator">✅ 제목이 동기화되었습니다</span>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const titleHelper = document.getElementById('place_title_helper');
            const syncIndicator = document.getElementById('sync-indicator');
            
            if (titleHelper) {
                titleHelper.addEventListener('input', function(e) {
                    const titleValue = e.target.value.trim();
                    
                    // 블록 에디터 제목 요소 찾기 및 업데이트
                    updateBlockEditorTitle(titleValue);
                    
                    // 동기화 상태 업데이트
                    if (titleValue) {
                        syncIndicator.textContent = '✅ 제목이 동기화되었습니다';
                        syncIndicator.style.color = '#28a745';
                    } else {
                        syncIndicator.textContent = '⚠️ 제목을 입력해주세요';
                        syncIndicator.style.color = '#ffc107';
                    }
                    
                    console.log('📝 제목 업데이트:', titleValue);
                });
                
                // 초기 로드 시 블록 에디터에 제목 설정
                if (titleHelper.value) {
                    setTimeout(() => {
                        updateBlockEditorTitle(titleHelper.value);
                    }, 1000);
                }
            }
            
            function updateBlockEditorTitle(titleValue) {
                // 방법 1: 데이터 속성으로 제목 요소 찾기
                let titleElement = document.querySelector('[data-rich-text-placeholder*="제목"], [data-rich-text-placeholder*="title"]');
                
                // 방법 2: 블록 에디터 제목 클래스로 찾기
                if (!titleElement) {
                    titleElement = document.querySelector('.wp-block-post-title, .editor-post-title__input, [aria-label*="제목"]');
                }
                
                // 방법 3: contenteditable 제목 요소 찾기
                if (!titleElement) {
                    const editableElements = document.querySelectorAll('[contenteditable="true"]');
                    editableElements.forEach(el => {
                        const placeholder = el.getAttribute('data-rich-text-placeholder') || el.getAttribute('placeholder') || '';
                        if (placeholder.includes('제목') || placeholder.includes('title') || 
                            el.classList.contains('editor-post-title__input') ||
                            el.closest('.editor-post-title')) {
                            titleElement = el;
                        }
                    });
                }
                
                if (titleElement) {
                    // 제목 업데이트
                    if (titleElement.contentEditable === 'true') {
                        titleElement.textContent = titleValue;
                        titleElement.dispatchEvent(new Event('input', { bubbles: true }));
                        titleElement.dispatchEvent(new Event('change', { bubbles: true }));
                    } else if (titleElement.tagName === 'INPUT' || titleElement.tagName === 'TEXTAREA') {
                        titleElement.value = titleValue;
                        titleElement.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    
                    console.log('✅ 블록 에디터 제목 업데이트 성공');
                    return true;
                } else {
                    console.log('⚠️ 블록 에디터 제목 요소를 찾을 수 없습니다');
                    return false;
                }
            }
        });
        </script>
        
        <style>
        .title-helper-container {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .title-helper-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
        }
        
        .title-helper-input:focus {
            border-color: #007cba;
            box-shadow: 0 0 0 1px #007cba;
            outline: none;
        }
        
        .title-sync-status {
            margin-top: 8px;
            font-size: 12px;
        }
        
        .sync-indicator {
            font-weight: 500;
        }
        
        .form-text {
            font-size: 11px;
            color: #6c757d;
            margin-top: 4px;
        }
        </style>
        <?php
    }
    
    /**
     * 장소 유형 선택기 렌더링
     */
    private function render_type_selector($current_type) {
        // place_type taxonomy에서 동적으로 가져오기
        $place_types_terms = get_terms(array(
            'taxonomy' => 'place_type',
            'hide_empty' => false
        ));
        
        if (is_wp_error($place_types_terms) || empty($place_types_terms)) {
            echo '<p style="color: red;">장소유형을 불러올 수 없습니다. 장소유형을 먼저 추가해주세요.</p>';
            return;
        }
        
        // 동적으로 place_types 배열 생성
        $place_types = array();
        
        foreach ($place_types_terms as $term) {
            // 팝업스토어는 CSV 프로세스로 별도 관리하므로 제외하지 않음 (메타박스에서는 선택 가능)
            $metafield_type = get_term_meta($term->term_id, 'metafield_type', true);
            
            // 아이콘 설정
            $icon = '📍'; // 기본 아이콘
            if ($metafield_type === 'food') {
                $icon = '🍽️';
            } elseif ($metafield_type === 'shop') {
                $icon = '🛍️';
            } elseif ($term->slug === 'popup-store' || $term->name === '팝업스토어') {
                $icon = '🎪';
            }
            
            // 설명 설정
            $description = $term->description ?: '장소 정보를 입력해주세요';
            
            $place_types[$term->slug] = array(
                'label' => $icon . ' ' . $term->name,
                'description' => $description,
                'term_id' => $term->term_id,
                'metafield_type' => $metafield_type
            );
        }
        
        echo '<div class="place-type-buttons">';
        foreach ($place_types as $type => $info) {
            $active_class = ($current_type === $type) ? 'active' : '';
            printf(
                '<button type="button" class="place-type-btn %s" data-type="%s">
                    <span class="type-label">%s</span>
                    <span class="type-description">%s</span>
                </button>',
                $active_class,
                esc_attr($type),
                esc_html($info['label']),
                esc_html($info['description'])
            );
        }
        echo '</div>';
        
        // 숨겨진 필드로 선택된 타입 저장
        printf('<input type="hidden" id="selected_place_type" name="place_type" value="%s">', esc_attr($current_type));
        
        // 디버깅 정보 출력 (개발 모드에서만)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<div style="background: #f0f6fc; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 4px; font-size: 12px;">';
            echo '<strong>디버깅 정보:</strong><br>';
            echo '현재 선택된 타입: ' . esc_html($current_type) . '<br>';
            echo '네이버 API 키: ' . esc_html(get_option('sungsuya_naver_client_id', '없음')) . '<br>';
            echo 'AJAX URL: ' . esc_html(admin_url('admin-ajax.php')) . '<br>';
            echo '</div>';
        }
    }
    
    /**
     * 공통 필드 렌더링
     */
    private function render_common_fields($meta_data) {
        $common_fields = PlaceMetaFields::get_common_fields();
        
        foreach ($common_fields as $field_key => $field_config) {
            $value = isset($meta_data[$field_key]) ? $meta_data[$field_key] : '';
            $this->render_field($field_key, $field_config, $value);
        }
    }
    
    /**
     * 동적 필드 렌더링 (유형별) - 개선된 버전
     */
    private function render_dynamic_fields($current_type, $meta_data) {
        // 현재 선택된 장소유형의 메타필드 타입 가져오기
        $current_term = get_term_by('slug', $current_type, 'place_type');
        $current_metafield_type = '';
        
        if ($current_term && !is_wp_error($current_term)) {
            $current_metafield_type = get_term_meta($current_term->term_id, 'metafield_type', true);
        }
        
        // 모든 장소유형 가져오기
        $place_types_terms = get_terms(array(
            'taxonomy' => 'place_type',
            'hide_empty' => false
        ));
        
        if (!is_wp_error($place_types_terms)) {
            foreach ($place_types_terms as $term) {
                $metafield_type = get_term_meta($term->term_id, 'metafield_type', true);
                $display_style = ($current_type === $term->slug) ? 'block' : 'none';
                
                echo "<div class='dynamic-field-group' data-type='{$term->slug}' style='display: {$display_style};'>";
                
                // 팝업스토어인 경우 특별 처리
                if ($term->slug === 'popup-store' || $term->name === '팝업스토어') {
                    $this->render_popup_store_fields($meta_data);
                } elseif ($metafield_type) {
                    // 메타필드 타입에 따른 필드 표시
                    echo "<h3>" . $this->get_type_title_by_metafield($metafield_type, $term->name) . "</h3>";
                    
                    $type_fields = PlaceMetaFields::get_type_specific_fields();
                    if (isset($type_fields[$metafield_type])) {
                        foreach ($type_fields[$metafield_type] as $field_key => $field_config) {
                            $value = isset($meta_data[$field_key]) ? $meta_data[$field_key] : '';
                            $this->render_field($field_key, $field_config, $value);
                        }
                    } else {
                        echo "<p>이 장소유형에 대한 추가 필드가 없습니다.</p>";
                    }
                } else {
                    echo "<h3>{$term->name} 정보</h3>";
                    echo "<p>이 장소유형에 메타필드 타입이 설정되지 않았습니다.</p>";
                }
                
                echo "</div>";
            }
        }
        
        // 기존 하드코딩된 타입들도 처리 (호환성) - 이미 처리된 타입은 제외
        $type_fields = PlaceMetaFields::get_type_specific_fields();
        $legacy_types = array('popup_store', 'restaurant', 'retail_store', 'facility');
        
        // 이미 처리된 타입들 추적
        $processed_types = array();
        if (!is_wp_error($place_types_terms)) {
            foreach ($place_types_terms as $term) {
                $processed_types[] = $term->slug;
                // popup-store와 popup_store 둘 다 처리된 것으로 표시
                if ($term->slug === 'popup-store' || $term->name === '팝업스토어') {
                    $processed_types[] = 'popup_store';
                }
            }
        }
        
        foreach ($legacy_types as $type) {
            // 이미 처리된 타입은 건너뛰기
            if (in_array($type, $processed_types)) {
                continue;
            }
            
            if (isset($type_fields[$type])) {
                $display_style = ($current_type === $type) ? 'block' : 'none';
                
                echo "<div class='dynamic-field-group' data-type='{$type}' style='display: {$display_style};'>";
                
                if ($type === 'popup_store') {
                    $this->render_popup_store_fields($meta_data);
                } else {
                    echo "<h3>" . $this->get_type_title($type) . "</h3>";
                    
                    foreach ($type_fields[$type] as $field_key => $field_config) {
                        $value = isset($meta_data[$field_key]) ? $meta_data[$field_key] : '';
                        $this->render_field($field_key, $field_config, $value);
                    }
                }
                
                echo "</div>";
            }
        }
    }
    
    /**
     * 개별 필드 렌더링
     */
    private function render_field($field_key, $field_config, $value) {
        $field_id = "places_field_{$field_key}";
        $required_attr = isset($field_config['required']) && $field_config['required'] ? 'required' : '';
        $readonly_attr = isset($field_config['readonly']) && $field_config['readonly'] ? 'readonly' : '';
        
        echo "<div class='places-field'>";
        echo "<label for='{$field_id}'>{$field_config['label']}";
        if (!empty($required_attr)) echo " <span class='required'>*</span>";
        echo "</label>";
        
        // 주소 필드 - 주소 확인 버튼 추가 (2025-07-06)
        if ($field_key === 'address') {
            echo '<div class="address-input-wrapper">';
            printf(
                '<input type="%s" id="%s" name="%s" value="%s" placeholder="%s" %s %s>',
                esc_attr($field_config['type']),
                esc_attr($field_id),
                esc_attr($field_key),
                esc_attr($value),
                esc_attr($field_config['placeholder'] ?? ''),
                $required_attr,
                $readonly_attr
            );
            echo '<button type="button" id="places-integrated-geocoding-btn" class="button button-primary" style="margin-left: 10px;">🗺️ 좌표 생성</button>';
            echo '</div>';
            echo '<div class="address-notice" style="margin-top: 8px;">';
            echo '<div class="notice notice-info inline" style="margin: 0; padding: 8px;">';
            echo '<p style="margin: 0;"><strong>💡 좌표 자동 생성:</strong></p>';
            echo '<ul style="margin: 5px 0 0 20px; font-size: 12px;">';
            echo '<li>주소 입력 후 "좌표 생성" 버튼을 클릭하세요</li>';
            echo '<li>좌표는 <strong>통합 지도생성 시스템</strong>에서 자동 처리됩니다</li>';
            echo '<li>생성된 좌표는 하단의 "위치 미리보기"에서 확인할 수 있습니다</li>';
            echo '</ul></div>';
            echo '</div>';
        } else {
            switch ($field_config['type']) {
            case 'text':
            case 'tel':
            case 'url':
            case 'email':
            case 'date':
                // 좌표 필드는 통합 시스템에서 관리하므로 readonly 처리
                $coordinate_readonly = '';
                $coordinate_class = '';
                if (in_array($field_key, ['latitude', 'longitude'])) {
                    $coordinate_readonly = 'readonly';
                    $coordinate_class = 'coordinate-field';
                }
                
                printf(
                    '<input type="%s" id="%s" name="%s" value="%s" placeholder="%s" class="%s" %s %s %s>',
                    esc_attr($field_config['type']),
                    esc_attr($field_id),
                    esc_attr($field_key),
                    esc_attr($value),
                    esc_attr($field_key === 'latitude' ? '위도 (예: 37.5445)' : ($field_key === 'longitude' ? '경도 (예: 127.0557)' : $field_config['placeholder'] ?? '')),
                    $coordinate_class,
                    $required_attr,
                    $readonly_attr,
                    $coordinate_readonly
                );
                
                // 좌표 필드 전용 안내
                if (in_array($field_key, ['latitude', 'longitude'])) {
                    echo '<div class="coordinate-help" style="margin-top: 5px;">';
                    echo '<small style="color: #0073aa;">';
                    echo '<span class="dashicons dashicons-info" style="font-size: 12px;"></span> ';
                    echo '이 값은 주소 입력 후 자동으로 생성됩니다';
                    echo '</small>';
                    echo '</div>';
                }
                break;
                
            case 'number':
                // 좌표 필드는 통합 시스템에서 관리하므로 readonly 처리
                $coordinate_readonly = '';
                $coordinate_class = '';
                if (in_array($field_key, ['latitude', 'longitude'])) {
                    $coordinate_readonly = 'readonly';
                    $coordinate_class = 'coordinate-field';
                }
                
                printf(
                    '<input type="number" id="%s" name="%s" value="%s" step="%s" class="%s" %s %s %s>',
                    esc_attr($field_id),
                    esc_attr($field_key),
                    esc_attr($value),
                    esc_attr($field_config['step'] ?? '1'),
                    $coordinate_class,
                    $required_attr,
                    $readonly_attr,
                    $coordinate_readonly
                );
                
                // 좌표 필드 전용 안내
                if (in_array($field_key, ['latitude', 'longitude'])) {
                    echo '<div class="coordinate-help" style="margin-top: 5px;">';
                    echo '<small style="color: #0073aa;">';
                    echo '<span class="dashicons dashicons-info" style="font-size: 12px;"></span> ';
                    echo '이 값은 주소 입력 후 자동으로 생성됩니다';
                    echo '</small>';
                    echo '</div>';
                }
                break;
                
            case 'textarea':
                printf(
                    '<textarea id="%s" name="%s" rows="%s" placeholder="%s" %s %s>%s</textarea>',
                    esc_attr($field_id),
                    esc_attr($field_key),
                    esc_attr($field_config['rows'] ?? 3),
                    esc_attr($field_config['placeholder'] ?? ''),
                    $required_attr,
                    $readonly_attr,
                    esc_textarea($value)
                );
                break;
                
            case 'select':
                echo "<select id='{$field_id}' name='{$field_key}' {$required_attr}>";
                echo "<option value=''>선택해주세요</option>";
                foreach ($field_config['options'] as $option_value => $option_label) {
                    $selected = selected($value, $option_value, false);
                    echo "<option value='{$option_value}' {$selected}>{$option_label}</option>";
                }
                echo "</select>";
                break;
                
            case 'checkbox':
            $checked = checked($value, '1', false);
            printf(
            '<input type="checkbox" id="%s" name="%s" value="1" %s>',
            esc_attr($field_id),
            esc_attr($field_key),
            $checked
            );
            break;
                    
                case 'checkbox_multiple':
                    $selected_values = is_array($value) ? $value : array();
                    echo '<div class="checkbox-multiple-container">';
                    foreach ($field_config['options'] as $option_value => $option_label) {
                        $checked = checked(in_array($option_value, $selected_values), true, false);
                        printf(
                            '<label class="checkbox-multiple-item">' .
                            '<input type="checkbox" name="%s[]" value="%s" %s>' .
                            '<span class="checkbox-label">%s</span>' .
                            '</label>',
                            esc_attr($field_key),
                            esc_attr($option_value),
                            $checked,
                            esc_html($option_label)
                        );
                    }
                    echo '</div>';
                    break;
            }
        }
        
        if (!empty($field_config['description'])) {
            echo "<p class='field-description'>{$field_config['description']}</p>";
        }
        
        // 좌표 필드에 대한 추가 안내
        if (in_array($field_key, ['latitude', 'longitude'])) {
            echo '<p class="coordinate-notice" style="display:none;">좌표 자동 설정</p>';
        }
        
        echo "</div>";
    }
    
    /**
     * 현재 장소 유형 가져오기
     */
    private function get_current_place_type($post_id) {
        $place_types = wp_get_post_terms($post_id, 'place_type');
        return !empty($place_types) ? $place_types[0]->slug : '';
    }
    
    /**
     * 메타 데이터 가져오기 - 개선된 버전
     */
    private function get_meta_data($post_id) {
        $meta_data = array();
        
        // 1. 공통 필드 가져오기
        $common_fields = PlaceMetaFields::get_common_fields();
        foreach ($common_fields as $field_key => $field_config) {
            $meta_data[$field_key] = get_post_meta($post_id, $field_key, true);
        }
        
        // 2. 현재 장소유형 확인
        $place_types = wp_get_post_terms($post_id, 'place_type');
        if (!empty($place_types)) {
            $current_type = $place_types[0];
            $metafield_type = get_term_meta($current_type->term_id, 'metafield_type', true);
            
            // 3. 메타필드 타입에 따른 필드 가져오기
            $type_fields = PlaceMetaFields::get_type_specific_fields();
            
            if ($metafield_type && isset($type_fields[$metafield_type])) {
                foreach ($type_fields[$metafield_type] as $field_key => $field_config) {
                    $meta_data[$field_key] = get_post_meta($post_id, $field_key, true);
                }
            }
            
            // 4. 레거시 타입 호환성 (popup_store, restaurant 등)
            if (isset($type_fields[$current_type->slug])) {
                foreach ($type_fields[$current_type->slug] as $field_key => $field_config) {
                    $meta_data[$field_key] = get_post_meta($post_id, $field_key, true);
                }
            }
        }
        
        // 5. 모든 타입의 필드 체크 (호환성)
        $all_type_fields = PlaceMetaFields::get_type_specific_fields();
        foreach ($all_type_fields as $type => $fields) {
            foreach ($fields as $field_key => $field_config) {
                if (!isset($meta_data[$field_key])) {
                    $value = get_post_meta($post_id, $field_key, true);
                    if ($value) {
                        $meta_data[$field_key] = $value;
                    }
                }
            }
        }
        
        return $meta_data;
    }
    
    /**
     * 팝업스토어 필드 그룹별 렌더링
     */
    private function render_popup_store_fields($meta_data) {
        $field_groups = PlaceMetaFields::get_popup_store_field_groups();
        
        echo '<div class="popup-store-fields-container">';
        
        foreach ($field_groups as $group_id => $group_info) {
            echo '<div class="popup-store-field-group" data-group="' . $group_id . '">';
            echo '<h3>' . $group_info['icon'] . ' ' . $group_info['title'] . '</h3>';
            echo '<p class="group-description">' . $group_info['description'] . '</p>';
            
            // 'popup-store' (대시) 형태로 변경하여 필드 가져오기
            $group_fields = PlaceMetaFields::get_fields_by_group('popup-store', $group_id);
            
            // 디버깅: 필드가 없을 경우 알림
            if (empty($group_fields)) {
                error_log("팝업스토어 필드 없음 - 그룹: {$group_id}");
                echo '<p style="color: red;">필드를 불러올 수 없습니다. 시스템 관리자에게 문의하세요.</p>';
            } else {
                foreach ($group_fields as $field_key => $field_config) {
                    $value = isset($meta_data[$field_key]) ? $meta_data[$field_key] : '';
                    $this->render_field($field_key, $field_config, $value);
                }
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * 유형별 제목 가져오기
     */
    private function get_type_title($type) {
        $titles = array(
            'popup_store' => '🎪 팝업스토어 정보',
            'restaurant' => '🍽️ 맛집 정보',
            'retail_store' => '🏬 매장 정보',
            'facility' => '🚻 시설 정보'
        );
        
        return $titles[$type] ?? '기타 정보';
    }
    
    /**
     * 메타필드 타입별 제목 가져오기
     */
    private function get_type_title_by_metafield($metafield_type, $term_name) {
        if ($metafield_type === 'food') {
            return '🍽️ ' . $term_name . ' 정보';
        } elseif ($metafield_type === 'shop') {
            return '🛍️ ' . $term_name . ' 정보';
        }
        
        return '📍 ' . $term_name . ' 정보';
    }
    
    /**
     * 디버그 정보 출력 헬퍼
     */
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Places 메타박스 마스터] ' . $message);
        }
    }
    
    /**
     * 메타 데이터 저장 - 마스터 시스템 (협력 구조의 핵심)
     */
    public function save_meta($post_id, $post) {
        // 🔒 무한 루프 방지
        static $saving = false;
        if ($saving) {
            return;
        }
        $saving = true;
        
        // 🔒 저장 마스터 시스템 플래그 설정 (중복 방지)
        if (!defined('PLACES_MASTER_SAVE_SYSTEM')) {
            define('PLACES_MASTER_SAVE_SYSTEM', true);
        }
        
        // Places 포스트 타입만 처리
        if ($post->post_type !== 'places') {
            $saving = false;
            return;
        }
        
        // 자동 저장 건너뛰기
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            $saving = false;
            return;
        }
        
        // 권한 확인
        if (!current_user_can('edit_post', $post_id)) {
            $saving = false;
            return;
        }
        
        // Nonce 확인
        if (!isset($_POST['places_metabox_nonce']) || !wp_verify_nonce($_POST['places_metabox_nonce'], 'places_metabox_save')) {
            $saving = false;
            return;
        }
        
        // 🆆 제목 도우미 처리 (무한 루프 방지)
        if (isset($_POST['place_title_helper']) && !empty($_POST['place_title_helper'])) {
            $new_title = sanitize_text_field($_POST['place_title_helper']);
            
            // 무한 루프 방지: 제목이 다를 때만 업데이트
            if ($post->post_title !== $new_title) {
                // save_post 훅 임시 제거
                remove_action('save_post', array($this, 'save_meta'), 10, 2);
                
                $post_data = array(
                    'ID' => $post_id,
                    'post_title' => $new_title
                );
                wp_update_post($post_data);
                
                // save_post 훅 다시 추가
                add_action('save_post', array($this, 'save_meta'), 10, 2);
                
                $this->debug_log('제목 도우미로 제목 업데이트: ' . $new_title);
            }
        }
        
        // 장소 유형 택소노미 저장
        if (isset($_POST['place_type']) && !empty($_POST['place_type'])) {
            $term_result = wp_set_post_terms($post_id, $_POST['place_type'], 'place_type');
            $this->debug_log('장소 유형 저장: ' . $_POST['place_type'] . ' -> ' . (is_wp_error($term_result) ? 'ERROR' : 'SUCCESS'));
        }
        
        // 메타 필드 저장
        $current_type = $_POST['place_type'] ?? '';
        $all_common_fields = PlaceMetaFields::get_common_fields();
        $all_type_fields = PlaceMetaFields::get_type_specific_fields();
        
        // 공통 필드 저장
        foreach ($all_common_fields as $field_key => $field_config) {
            if (isset($_POST[$field_key])) {
                $value = $_POST[$field_key];
                
                // 필드 타입별 처리
                if ($field_config['type'] === 'checkbox') {
                    $value = '1';
                } elseif ($field_config['type'] === 'checkbox_multiple') {
                    $value = is_array($value) ? array_map('sanitize_text_field', $value) : array();
                } elseif ($field_config['type'] === 'textarea') {
                    $value = sanitize_textarea_field($value);
                } elseif ($field_config['type'] === 'url') {
                    $value = esc_url_raw($value);
                } elseif ($field_config['type'] === 'email') {
                    $value = sanitize_email($value);
                } else {
                    $value = sanitize_text_field($value);
                }
                
                update_post_meta($post_id, $field_key, $value);
                $this->debug_log("공통 필드 저장: {$field_key} = {$value}");
            } else {
                if ($field_config['type'] === 'checkbox' || $field_config['type'] === 'checkbox_multiple') {
                    delete_post_meta($post_id, $field_key);
                    $this->debug_log("체크박스 필드 삭제: {$field_key}");
                }
            }
        }
        
        // 타입별 특화 필드 저장 - 개선된 버전
        if (!empty($current_type)) {
            // 현재 장소유형의 메타필드 타입 확인
            $current_term = get_term_by('slug', $current_type, 'place_type');
            $metafield_type = '';
            
            if ($current_term && !is_wp_error($current_term)) {
                $metafield_type = get_term_meta($current_term->term_id, 'metafield_type', true);
            }
            
            // 메타필드 타입에 따른 필드 저장
            if ($metafield_type && isset($all_type_fields[$metafield_type])) {
                foreach ($all_type_fields[$metafield_type] as $field_key => $field_config) {
                    if (isset($_POST[$field_key])) {
                        $value = $_POST[$field_key];
                        
                        // 필드 타입별 처리
                        if ($field_config['type'] === 'checkbox') {
                            $value = '1';
                        } elseif ($field_config['type'] === 'checkbox_multiple') {
                            $value = is_array($value) ? array_map('sanitize_text_field', $value) : array();
                        } elseif ($field_config['type'] === 'textarea') {
                            $value = sanitize_textarea_field($value);
                        } elseif ($field_config['type'] === 'url') {
                            $value = esc_url_raw($value);
                        } elseif ($field_config['type'] === 'email') {
                            $value = sanitize_email($value);
                        } else {
                            $value = sanitize_text_field($value);
                        }
                        
                        update_post_meta($post_id, $field_key, $value);
                        $this->debug_log("메타필드 타입별 필드 저장: {$field_key} = {$value}");
                    } else {
                        if ($field_config['type'] === 'checkbox' || $field_config['type'] === 'checkbox_multiple') {
                            delete_post_meta($post_id, $field_key);
                            $this->debug_log("메타필드 타입별 체크박스 필드 삭제: {$field_key}");
                        }
                    }
                }
            }
            
            // 레거시 타입 호환성 (popup_store 등)
            if (isset($all_type_fields[$current_type])) {
                foreach ($all_type_fields[$current_type] as $field_key => $field_config) {
                    if (isset($_POST[$field_key])) {
                        $value = $_POST[$field_key];
                        
                        // 필드 타입별 처리
                        if ($field_config['type'] === 'checkbox') {
                            $value = '1';
                        } elseif ($field_config['type'] === 'checkbox_multiple') {
                            $value = is_array($value) ? array_map('sanitize_text_field', $value) : array();
                        } elseif ($field_config['type'] === 'textarea') {
                            $value = sanitize_textarea_field($value);
                        } elseif ($field_config['type'] === 'url') {
                            $value = esc_url_raw($value);
                        } elseif ($field_config['type'] === 'email') {
                            $value = sanitize_email($value);
                        } else {
                            $value = sanitize_text_field($value);
                        }
                        
                        update_post_meta($post_id, $field_key, $value);
                        $this->debug_log("레거시 타입별 필드 저장: {$field_key} = {$value}");
                    } else {
                        if ($field_config['type'] === 'checkbox' || $field_config['type'] === 'checkbox_multiple') {
                            delete_post_meta($post_id, $field_key);
                            $this->debug_log("레거시 타입별 체크박스 필드 삭제: {$field_key}");
                        }
                    }
                }
            }
        }
        
        // Smart Deep Link 자동 생성 (PlacesMetaboxManager 클래스 내부에 추가)
        $place_name = get_the_title($post_id);
        if (!empty($place_name)) {
            // Smart Deep Link System 클래스 로드
            if (!class_exists('SmartDeepLinkV7')) {
                require_once get_template_directory() . '/inc/helpers/smart-deep-link-system-v7.php';
            }
            
            $smart_link_system = new SmartDeepLinkV7();
            $smart_links = $smart_link_system->generateLinks($place_name);
            
            if (!empty($smart_links)) {
                update_post_meta($post_id, 'smart_deep_links', $smart_links);
                update_post_meta($post_id, 'smart_deep_link_v7', $smart_links); // 호환성
                $this->debug_log('Smart Deep Links 생성 성공: ' . $place_name);
            }
        }
        
        $this->debug_log("🎯 마스터 저장 시스템 완료 - Post ID: {$post_id}");
        
        // 저장 완료 후 플래그 해제
        $saving = false;
    }
    
    /**
     * Places 전용 주소 검색 AJAX 핸들러 (팝업스토어와 동일한 방식)
     * ⚠️ 통합 지도생성 시스템 도입으로 비활성화됨
     */
    public function ajax_places_search_address() {
        // 보안 검증
        if (!check_ajax_referer('places_ajax_nonce', 'nonce', false)) {
            wp_send_json_error('보안 토큰이 유효하지 않습니다.');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('권한이 없습니다.');
        }
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        if (empty($query)) {
            wp_send_json_error('검색어를 입력해주세요.');
        }
        
        // 네이버 지오코딩 API 직접 호출 (설정 페이지와 동일한 옵션명)
        $api_client_id = get_option('sungsuya_naver_client_id', '');
        $api_client_secret = get_option('sungsuya_naver_client_secret', '');
        
        if (empty($api_client_id) || empty($api_client_secret)) {
            // API 키가 없으면 더미 데이터 반환
            $this->debug_log('Places AJAX: API 키 없음 - 더미 데이터 반환');
            wp_send_json_success($this->get_dummy_geocoding_data($query));
            return;
        }
        
        // 네이버 지오코딩 API 호출
        $api_url = 'https://naveropenapi.apigw.ntruss.com/map-geocode/v2/geocode';
        $response = wp_remote_get($api_url . '?' . http_build_query(array(
            'query' => $query,
            'count' => 5
        )), array(
            'headers' => array(
                'X-NCP-APIGW-API-KEY-ID' => $api_client_id,
                'X-NCP-APIGW-API-KEY' => $api_client_secret
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            $this->debug_log('Places AJAX: API 오류 - ' . $response->get_error_message());
            wp_send_json_error('주소 검색 중 오류가 발생했습니다.');
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['addresses']) && !empty($data['addresses'])) {
            $results = array();
            foreach ($data['addresses'] as $address) {
                $results[] = array(
                    'roadAddress' => $address['roadAddress'] ?? '',
                    'jibunAddress' => $address['jibunAddress'] ?? '',
                    'y' => $address['y'] ?? '',
                    'x' => $address['x'] ?? '',
                    'latitude' => $address['y'] ?? '',
                    'longitude' => $address['x'] ?? ''
                );
            }
            
            $this->debug_log('Places AJAX: 지오코딩 성공 - ' . count($results) . '개 결과');
            wp_send_json_success($results);
        } else {
            $this->debug_log('Places AJAX: 지오코딩 결과 없음');
            wp_send_json_error('해당 주소를 찾을 수 없습니다.');
        }
    }
    
    /**
     * 더미 지오코딩 데이터 생성 (API 키 없을 때)
     */
    private function get_dummy_geocoding_data($query) {
        if (strpos($query, '성수') !== false || strpos($query, '성동구') !== false) {
            return array(
                array(
                    'roadAddress' => '서울특별시 성동구 성수일로8길 17',
                    'jibunAddress' => '서울특별시 성동구 성수동1가 13-30',
                    'y' => '37.5447',
                    'x' => '127.0557',
                    'latitude' => '37.5447',
                    'longitude' => '127.0557'
                )
            );
        }
        
        return array(
            array(
                'roadAddress' => '서울특별시 성동구 성수일로8길 17 (추정 위치)',
                'jibunAddress' => '서울특별시 성동구 성수동1가 13-30',
                'y' => '37.5447',
                'x' => '127.0557',
                'latitude' => '37.5447',
                'longitude' => '127.0557'
            )
        );
    }
    
    /**
     * 지하철역 정보 조회 AJAX 핸들러
     */
    public function ajax_get_subway_info() {
        check_ajax_referer('places_ajax_nonce', 'nonce');
        
        $lat = floatval($_POST['latitude'] ?? 0);
        $lng = floatval($_POST['longitude'] ?? 0);
        
        if (!$lat || !$lng) {
            wp_send_json_error('좌표 정보가 없습니다.');
        }
        
        $subway_info = $this->get_nearest_subway_station($lat, $lng);
        
        if ($subway_info) {
            wp_send_json_success($subway_info);
        } else {
            wp_send_json_error('지하철역 정보를 찾을 수 없습니다.');
        }
    }
    
    /**
     * 가장 가까운 지하철역 찾기 (확장된 서울 지하철역 데이터)
     */
    private function get_nearest_subway_station($lat, $lng) {
        // 확장된 서울 지하철역 데이터 (성수동 근처 및 주요 역들)
        $subway_stations = array(
            // 2호선
            array('name' => '성수역', 'line' => '2호선', 'lat' => 37.5445, 'lng' => 127.0557),
            array('name' => '건대입구역', 'line' => '2호선', 'lat' => 37.5406, 'lng' => 127.0698),
            array('name' => '뚝섬역', 'line' => '2호선', 'lat' => 37.5472, 'lng' => 127.0474),
            array('name' => '서울숲역', 'line' => '수인분당선', 'lat' => 37.5443, 'lng' => 127.0436),
            array('name' => '압구정로데오역', 'line' => '분당선', 'lat' => 37.5273, 'lng' => 127.0407),
            array('name' => '강남역', 'line' => '2호선', 'lat' => 37.4979, 'lng' => 127.0276),
            array('name' => '홍대입구역', 'line' => '2호선', 'lat' => 37.5573, 'lng' => 126.9246),
            array('name' => '합정역', 'line' => '2호선', 'lat' => 37.5497, 'lng' => 126.9142),
            
            // 성동구 주변 추가 역들
            array('name' => '왕십리역', 'line' => '2호선', 'lat' => 37.5617, 'lng' => 127.0375),
            array('name' => '한양대역', 'line' => '2호선', 'lat' => 37.5556, 'lng' => 127.0440),
            array('name' => '상왕십리역', 'line' => '2호선', 'lat' => 37.5649, 'lng' => 127.0287),
            array('name' => '신답역', 'line' => '2호선', 'lat' => 37.5709, 'lng' => 127.0477),
            array('name' => '용답역', 'line' => '2호선', 'lat' => 37.5661, 'lng' => 127.0550),
            array('name' => '신설동역', 'line' => '1호선', 'lat' => 37.5755, 'lng' => 127.0250),
            
            // 강남구 주요 역들
            array('name' => '삼성역', 'line' => '2호선', 'lat' => 37.5088, 'lng' => 127.0633),
            array('name' => '선릉역', 'line' => '2호선', 'lat' => 37.5044, 'lng' => 127.0491),
            array('name' => '역삼역', 'line' => '2호선', 'lat' => 37.4996, 'lng' => 127.0364),
            array('name' => '교대역', 'line' => '2호선', 'lat' => 37.4935, 'lng' => 127.0142),
            array('name' => '서초역', 'line' => '2호선', 'lat' => 37.4838, 'lng' => 127.0057),
            
            // 종로구, 중구 주요 역들
            array('name' => '종각역', 'line' => '1호선', 'lat' => 37.5703, 'lng' => 126.9825),
            array('name' => '명동역', 'line' => '4호선', 'lat' => 37.5633, 'lng' => 126.9859),
            array('name' => '동대문역', 'line' => '1호선', 'lat' => 37.5714, 'lng' => 127.0095),
            array('name' => '동대문역사문화공원역', 'line' => '2호선', 'lat' => 37.5655, 'lng' => 127.0072),
            
            // 마포구 주요 역들
            array('name' => '홍익대역', 'line' => '2호선', 'lat' => 37.5486, 'lng' => 126.9279),
            array('name' => '상수역', 'line' => '6호선', 'lat' => 37.5477, 'lng' => 126.9227),
            array('name' => '망원역', 'line' => '6호선', 'lat' => 37.5557, 'lng' => 126.9105),
            
            // 영등포구, 강서구 주요 역들
            array('name' => '영등포구청역', 'line' => '2호선', 'lat' => 37.5244, 'lng' => 126.8956),
            array('name' => '문래역', 'line' => '2호선', 'lat' => 37.5186, 'lng' => 126.8950),
            array('name' => '신도림역', 'line' => '1호선', 'lat' => 37.5088, 'lng' => 126.8912),
            
            // 서대문구, 은평구 주요 역들
            array('name' => '신촌역', 'line' => '2호선', 'lat' => 37.5556, 'lng' => 126.9368),
            array('name' => '이대역', 'line' => '2호선', 'lat' => 37.5565, 'lng' => 126.9459),
            array('name' => '아현역', 'line' => '2호선', 'lat' => 37.5578, 'lng' => 126.9558),
            
            // 동작구, 관악구 주요 역들
            array('name' => '사당역', 'line' => '2호선', 'lat' => 37.4766, 'lng' => 126.9814),
            array('name' => '방배역', 'line' => '2호선', 'lat' => 37.4813, 'lng' => 127.0013),
            array('name' => '신림역', 'line' => '2호선', 'lat' => 37.4844, 'lng' => 126.9297)
        );
        
        $nearest_station = null;
        $shortest_distance = PHP_FLOAT_MAX;
        
        foreach ($subway_stations as $station) {
            $distance = $this->calculate_distance($lat, $lng, $station['lat'], $station['lng']);
            
            if ($distance < $shortest_distance) {
                $shortest_distance = $distance;
                $nearest_station = $station;
            }
        }
        
        if ($nearest_station) {
            // 도보 시간 계산 (평균 도보 속도 4km/h 기준)
            $walking_time = round(($shortest_distance * 1000) / 67); // 67m/min
            
            // 디버깅 로그 추가
            error_log(sprintf('[지하철 검색 완료] 입력좌표: %.6f,%.6f | 결과: %s (%s) %.2fkm %d분',
                $lat, $lng, $nearest_station['name'], $nearest_station['line'], $shortest_distance, max(1, $walking_time)
            ));
            
            return array(
                'station_name' => $nearest_station['name'],
                'line' => $nearest_station['line'],
                'distance' => round($shortest_distance, 2),
                'walking_time' => max(1, $walking_time)
            );
        }
        
        error_log(sprintf('[지하철 검색 실패] 입력좌표: %.6f,%.6f | 가까운 역 없음', $lat, $lng));
        return null;
    }
    
    /**
     * 두 좌표 간 거리 계산 (Haversine 공식)
     */
    private function calculate_distance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earth_radius * $c;
    }
    
    /**
     * 정적 지도 자동 생성 (새 장소 저장시)
     */
    public function auto_generate_static_map($post_id, $post) {
        // Places 포스트 타입만 처리
        if ($post->post_type !== 'places') {
            return;
        }
        
        // 자동 저장 및 기본 검증 건너뛰기
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // 좌표 정보 가져오기
        $latitude = get_post_meta($post_id, 'latitude', true);
        $longitude = get_post_meta($post_id, 'longitude', true);
        
        if (empty($latitude) || empty($longitude)) {
            $this->debug_log('정적지도 자동생성 실패: 좌표 없음 - Post ID: ' . $post_id);
            return;
        }
        
        // 네이버 정적지도 API 키 확인 (설정 페이지와 동일한 옵션명)
        $naver_client_id = get_option('sungsuya_naver_client_id', '');
        if (empty($naver_client_id)) {
            $this->debug_log('정적지도 자동생성 실패: 네이버 API 키 없음');
            return;
        }
        
        // 정적 지도 생성
        $static_map_url = $this->generate_static_map_url($latitude, $longitude, $naver_client_id);
        
        if ($static_map_url) {
            // 메타 필드에 정적지도 URL 저장
            update_post_meta($post_id, 'static_map_url', $static_map_url);
            update_post_meta($post_id, 'static_map_generated_at', current_time('mysql'));
            
            $this->debug_log('정적지도 자동생성 성공: Post ID ' . $post_id . ' -> ' . $static_map_url);
        }
    }
    
    /**
     * 정적 지도 URL 생성
     */
    private function generate_static_map_url($latitude, $longitude, $client_id) {
        $base_url = 'https://naveropenapi.apigw.ntruss.com/map-static/v2/raster';
        
        $params = array(
            'w' => 400,              // 너비
            'h' => 300,              // 높이
            'center' => $longitude . ',' . $latitude, // 중심점 (경도,위도)
            'level' => 16,           // 확대 레벨
            'maptype' => 'basic',    // 지도 타입
            'format' => 'png',       // 이미지 포맷
            'markers' => 'type:d|size:mid|pos:' . $longitude . ' ' . $latitude // 마커
        );
        
        return $base_url . '?' . http_build_query($params) . '&X-NCP-APIGW-API-KEY-ID=' . $client_id;
    }
}

// 클래스 인스턴스 생성
new PlacesMetaboxManager();
