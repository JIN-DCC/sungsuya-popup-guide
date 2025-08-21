<?php
/**
 * 팝업스토어 수동 입력 UI 탭 시스템
 * 
 * 긴 폼을 탭으로 분리하여 사용성 향상
 * 
 * @package SungsuyaV2
 * @since 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 팝업스토어 UI 탭 클래스
 */
class Popup_Store_UI_Tabs {
    
    /**
     * 싱글톤 인스턴스
     */
    private static $instance = null;
    
    /**
     * 생성자
     */
    private function __construct() {
        // 팝업스토어 타입 선택시에만 스크립트 로드
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // 메타박스 내용을 탭으로 재구성
        add_action('admin_footer-post.php', array($this, 'inject_tab_structure'));
        add_action('admin_footer-post-new.php', array($this, 'inject_tab_structure'));
        
        // AJAX 핸들러
        add_action('wp_ajax_validate_popup_fields', array($this, 'ajax_validate_fields'));
    }
    
    /**
     * 인스턴스 반환
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 스크립트 및 스타일 등록
     */
    public function enqueue_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        global $post;
        if (!$post || $post->post_type !== 'places') {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'popup-store-tabs',
            get_template_directory_uri() . '/assets/css/popup-store-tabs.css',
            array(),
            '1.0.0'
        );
        
        // JavaScript
        wp_enqueue_script(
            'popup-store-tabs',
            get_template_directory_uri() . '/assets/js/popup-store-tabs.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('popup-store-tabs', 'popupStoreTabs', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('popup_store_tabs'),
            'strings' => array(
                'required' => '필수 항목입니다',
                'invalidDate' => '올바른 날짜 형식이 아닙니다',
                'endBeforeStart' => '종료일이 시작일보다 빠릅니다',
                'validationError' => '입력 오류가 있습니다'
            )
        ));
    }
    
    /**
     * 탭 구조 주입
     */
    public function inject_tab_structure() {
        global $post;
        if (!$post || $post->post_type !== 'places') {
            return;
        }
        ?>
        <script type="text/html" id="popup-store-tabs-template">
            <div class="popup-store-tabs-container">
                <div class="tabs-header">
                    <ul class="tab-nav">
                        <li class="tab-nav-item active" data-tab="basic">
                            <span class="tab-icon">📍</span>
                            <span class="tab-label">기본정보</span>
                            <span class="tab-status"></span>
                        </li>
                        <li class="tab-nav-item" data-tab="operation">
                            <span class="tab-icon">⏰</span>
                            <span class="tab-label">운영정보</span>
                            <span class="tab-status"></span>
                        </li>
                        <li class="tab-nav-item" data-tab="social">
                            <span class="tab-icon">📱</span>
                            <span class="tab-label">소셜/연락처</span>
                            <span class="tab-status"></span>
                        </li>
                        <li class="tab-nav-item" data-tab="tour">
                            <span class="tab-icon">🗺️</span>
                            <span class="tab-label">투어정보</span>
                            <span class="tab-status"></span>
                        </li>
                    </ul>
                    <div class="tabs-progress">
                        <div class="progress-bar"></div>
                        <span class="progress-text">0% 완료</span>
                    </div>
                </div>
                
                <div class="tabs-content">
                    <!-- 탭 내용은 JavaScript로 동적 생성 -->
                </div>
                
                <div class="tabs-footer">
                    <div class="validation-summary" style="display: none;">
                        <h4>⚠️ 입력 검증 결과</h4>
                        <ul class="validation-errors"></ul>
                    </div>
                    
                    <div class="tab-navigation">
                        <button type="button" class="button prev-tab" disabled>
                            ← 이전
                        </button>
                        <button type="button" class="button button-primary next-tab">
                            다음 →
                        </button>
                        <button type="button" class="button button-primary validate-all" style="display: none;">
                            전체 검증
                        </button>
                    </div>
                </div>
            </div>
        </script>
        
        <style>
            /* 팝업스토어 선택시에만 탭 표시 */
            .place-type-fields {
                position: relative;
            }
            
            .popup-store-tabs-container {
                display: none;
            }
            
            /* 팝업스토어가 선택된 경우 탭 표시 */
            .dynamic-field-group[data-type="popup_store"] .popup-store-tabs-container {
                display: block !important;
            }
            
            /* 원본 필드 숨기기 - 탭 시스템이 활성화되면 */
            .popup-store-fields-container.tabs-initialized .popup-store-field-group {
                display: none !important;
            }
        </style>
        <?php
    }
    
    /**
     * AJAX: 필드 검증
     */
    public function ajax_validate_fields() {
        check_ajax_referer('popup_store_tabs', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $fields = $_POST['fields'];
        
        $errors = array();
        
        // 필수 필드 검증
        $required_fields = array(
            'basic' => array('store_name', 'brand_name', 'popup_category'),
            'operation' => array('start_date', 'end_date', 'operation_status'),
            'social' => array(),
            'tour' => array()
        );
        
        foreach ($required_fields as $tab => $fields_list) {
            foreach ($fields_list as $field_name) {
                if (empty($fields[$field_name])) {
                    $errors[] = array(
                        'tab' => $tab,
                        'field' => $field_name,
                        'message' => $this->get_field_label($field_name) . '은(는) 필수 항목입니다.'
                    );
                }
            }
        }
        
        // 날짜 검증
        if (!empty($fields['start_date']) && !empty($fields['end_date'])) {
            $start = strtotime($fields['start_date']);
            $end = strtotime($fields['end_date']);
            
            if ($start > $end) {
                $errors[] = array(
                    'tab' => 'operation',
                    'field' => 'end_date',
                    'message' => '종료일이 시작일보다 빠릅니다.'
                );
            }
        }
        
        // 이메일 형식 검증
        if (!empty($fields['email']) && !is_email($fields['email'])) {
            $errors[] = array(
                'tab' => 'social',
                'field' => 'email',
                'message' => '올바른 이메일 형식이 아닙니다.'
            );
        }
        
        // URL 형식 검증
        $url_fields = array('website', 'instagram', 'facebook', 'youtube', 'reservation_link');
        foreach ($url_fields as $field) {
            if (!empty($fields[$field]) && !filter_var($fields[$field], FILTER_VALIDATE_URL)) {
                $errors[] = array(
                    'tab' => 'social',
                    'field' => $field,
                    'message' => '올바른 URL 형식이 아닙니다.'
                );
            }
        }
        
        if (empty($errors)) {
            wp_send_json_success(array(
                'valid' => true,
                'message' => '모든 필드가 올바르게 입력되었습니다.'
            ));
        } else {
            wp_send_json_error(array(
                'valid' => false,
                'errors' => $errors
            ));
        }
    }
    
    /**
     * 필드 라벨 가져오기
     */
    private function get_field_label($field_name) {
        $labels = array(
            'store_name' => '스토어명',
            'brand_name' => '브랜드명',
            'popup_category' => '팝업 카테고리',
            'start_date' => '시작일',
            'end_date' => '종료일',
            'operation_status' => '운영 상태',
            'email' => '이메일',
            'website' => '웹사이트',
            'instagram' => '인스타그램',
            'facebook' => '페이스북',
            'youtube' => '유튜브',
            'reservation_link' => '예약 링크'
        );
        
        return isset($labels[$field_name]) ? $labels[$field_name] : $field_name;
    }
}

// 싱글톤 인스턴스 생성
Popup_Store_UI_Tabs::get_instance();

/**
 * 헬퍼 함수: 탭 시스템 활성화 여부
 */
function is_popup_store_tabs_enabled() {
    return get_option('popup_store_tabs_enabled', true);
}

/**
 * 헬퍼 함수: 탭별 필드 그룹
 */
function get_popup_store_tab_fields() {
    return array(
        'basic' => array(
            'store_name',
            'brand_name',
            'store_description',
            'popup_category',
            'collaboration',
            'special_options',
            'target_age',
            'price_range'
        ),
        'operation' => array(
            'start_date',
            'end_date',
            'operating_hours',
            'operation_status',
            'reservation_required',
            'admission_fee',
            'parking_info',
            'age_restriction'
        ),
        'social' => array(
            'phone',
            'email',
            'instagram',
            'facebook',
            'youtube',
            'website',
            'hashtags',
            'reservation_link',
            'event_link',
            'press_contact'
        ),
        'tour' => array(
            'recommended_duration',
            'best_time_to_visit',
            'weekday_crowd',
            'weekend_crowd',
            'accessibility',
            'photo_policy',
            'nearby_places',
            'combo_tips',
            'must_see_items',
            'photo_spots',
            'visit_tips',
            'queue_info',
            'seasonal_info',
            'weather_consideration',
            'group_visit_info'
        )
    );
}
