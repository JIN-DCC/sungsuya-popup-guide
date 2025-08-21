<?php
/**
 * 멤버십 기반 하이브리드 투어 시스템
 * 
 * API 사용량 최적화: 비회원 0회, 회원 월 10회 제한
 * 
 * @package SungsuyaV2
 * @since 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

class SungsuyaMembershipTourSystem {
    
    private $api_usage_limit = 10; // 월 API 사용 제한
    
    public function __construct() {
        add_action('init', array($this, 'init_membership_system'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_tour_scripts'));
        add_action('wp_ajax_get_tour_map_data', array($this, 'ajax_get_tour_map_data'));
        add_action('wp_ajax_nopriv_get_tour_map_data', array($this, 'ajax_get_tour_map_data'));
        add_action('wp_ajax_check_membership_status', array($this, 'ajax_check_membership_status'));
        add_action('wp_ajax_nopriv_check_membership_status', array($this, 'ajax_check_membership_status'));
    }
    
    /**
     * 멤버십 시스템 초기화
     */
    public function init_membership_system() {
        // WordPress 기본 회원가입 활성화
        if (!get_option('users_can_register')) {
            update_option('users_can_register', 1);
        }
        
        // 기본 사용자 역할 설정
        if (!get_option('default_role')) {
            update_option('default_role', 'subscriber');
        }
        
        // 회원가입 페이지 리다이렉트 설정
        add_filter('registration_redirect', array($this, 'custom_registration_redirect'));
        add_filter('login_redirect', array($this, 'custom_login_redirect'), 10, 3);
    }
    
    /**
     * 투어 관련 스크립트 로드
     */
    public function enqueue_tour_scripts() {
        if (is_page('planner') || strpos($_SERVER['REQUEST_URI'], 'planner') !== false) {
            wp_enqueue_script(
                'sungsuya-membership-tour',
                get_template_directory_uri() . '/assets/js/membership-tour.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            wp_localize_script('sungsuya-membership-tour', 'SungsuyaTour', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sungsuya_tour_nonce'),
                'isLoggedIn' => is_user_logged_in(),
                'userId' => get_current_user_id(),
                'apiLimit' => $this->api_usage_limit,
                'currentUsage' => $this->get_user_api_usage(get_current_user_id())
            ));
        }
    }
    
    /**
     * 사용자 권한 확인
     */
    public function get_user_tour_capabilities($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            // 비회원 사용자
            return array(
                'type' => 'guest',
                'can_use_gps' => false,
                'can_use_navigation' => false,
                'monthly_api_limit' => 0,
                'current_usage' => 0,
                'features' => array(
                    'static_tour_cards' => true,
                    'text_directions' => true,
                    'place_information' => true,
                    'tour_planning' => true
                )
            );
        } else {
            // 회원 사용자
            $current_usage = $this->get_user_api_usage($user_id);
            
            return array(
                'type' => 'member',
                'can_use_gps' => true,
                'can_use_navigation' => ($current_usage < $this->api_usage_limit),
                'monthly_api_limit' => $this->api_usage_limit,
                'current_usage' => $current_usage,
                'features' => array(
                    'static_tour_cards' => true,
                    'text_directions' => true,
                    'place_information' => true,
                    'tour_planning' => true,
                    'gps_navigation' => true,
                    'personalized_tours' => true,
                    'tour_history' => true,
                    'favorite_places' => true
                )
            );
        }
    }
    
    /**
     * 월간 API 사용량 조회
     */
    public function get_user_api_usage($user_id) {
        if (!$user_id) return 0;
        
        $current_month = date('Y-m');
        $usage_key = 'naver_map_api_usage_' . $current_month;
        
        return intval(get_user_meta($user_id, $usage_key, true));
    }
    
    /**
     * API 사용량 증가
     */
    public function increment_user_api_usage($user_id) {
        if (!$user_id) return false;
        
        $current_month = date('Y-m');
        $usage_key = 'naver_map_api_usage_' . $current_month;
        $current_usage = $this->get_user_api_usage($user_id);
        
        update_user_meta($user_id, $usage_key, $current_usage + 1);
        
        return true;
    }
    
    /**
     * API 사용량 확인 및 제한
     */
    public function can_use_api($user_id) {
        if (!$user_id) return false; // 비회원은 API 사용 불가
        
        $current_usage = $this->get_user_api_usage($user_id);
        return ($current_usage < $this->api_usage_limit);
    }
    
    /**
     * AJAX: 투어 지도 데이터 요청
     */
    public function ajax_get_tour_map_data() {
        check_ajax_referer('sungsuya_tour_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $capabilities = $this->get_user_tour_capabilities($user_id);
        
        $places = isset($_POST['places']) ? json_decode(stripslashes($_POST['places']), true) : array();
        
        if (empty($places)) {
            wp_send_json_error('선택된 장소가 없습니다.');
        }
        
        // 비회원: 정적 투어 카드 데이터만 제공
        if ($capabilities['type'] === 'guest') {
            $response = $this->generate_static_tour_data($places);
            wp_send_json_success($response);
        }
        
        // 회원: API 사용량 확인 후 동적 데이터 제공
        if ($capabilities['can_use_navigation']) {
            $this->increment_user_api_usage($user_id);
            $response = $this->generate_dynamic_tour_data($places);
            wp_send_json_success($response);
        } else {
            // API 한도 초과시 정적 데이터로 fallback
            $response = $this->generate_static_tour_data($places);
            $response['api_limit_exceeded'] = true;
            wp_send_json_success($response);
        }
    }
    
    /**
     * 정적 투어 카드 데이터 생성 (API 사용량 0)
     */
    private function generate_static_tour_data($places) {
        $tour_data = array();
        
        foreach ($places as $index => $place_id) {
            $place_post = get_post($place_id);
            if (!$place_post) continue;
            
            $place_meta = sungsuya_get_place_meta($place_id);
            
            $tour_data[] = array(
                'id' => $place_id,
                'title' => $place_post->post_title,
                'address' => $place_meta['common']['address'] ?? '',
                'type' => get_post_meta($place_id, 'place_type', true) ?: 'restaurant',
                'order' => $index + 1,
                'coordinates' => array(
                    'lat' => floatval($place_meta['common']['latitude'] ?? 0),
                    'lng' => floatval($place_meta['common']['longitude'] ?? 0)
                ),
                'static_map_url' => $this->get_static_mini_map_url($place_id),
                'walking_time' => $this->estimate_walking_time($index, $places),
                'directions_text' => $this->generate_text_directions($index, $places),
                'features' => array(
                    'subway_info' => $place_meta['common']['nearest_subway'] ?? '',
                    'opening_hours' => $place_meta['common']['opening_hours'] ?? '',
                    'phone' => $place_meta['common']['phone'] ?? ''
                )
            );
        }
        
        return array(
            'type' => 'static',
            'places' => $tour_data,
            'total_time' => $this->calculate_total_tour_time($tour_data),
            'total_distance' => $this->estimate_total_distance($tour_data),
            'api_usage' => 0
        );
    }
    
    /**
     * 동적 투어 데이터 생성 (API 사용량 1회)
     */
    private function generate_dynamic_tour_data($places) {
        // 기본 정적 데이터 생성
        $static_data = $this->generate_static_tour_data($places);
        
        // 동적 기능 추가
        $static_data['type'] = 'dynamic';
        $static_data['api_usage'] = 1;
        $static_data['navigation_enabled'] = true;
        $static_data['gps_tracking'] = true;
        
        // 실시간 네비게이션 설정 추가
        foreach ($static_data['places'] as &$place) {
            $place['navigation_url'] = $this->generate_navigation_url($place['coordinates']);
            $place['real_time_info'] = array(
                'traffic_status' => 'normal',
                'estimated_arrival' => $this->calculate_arrival_time($place['order'])
            );
        }
        
        return $static_data;
    }
    
    /**
     * 정적 미니 지도 URL 생성
     */
    private function get_static_mini_map_url($place_id) {
        if (!class_exists('NaverStaticMapGenerator')) {
            require_once get_template_directory() . '/inc/naver-static-map-generator.php';
        }
        
        $generator = new NaverStaticMapGenerator();
        return $generator->getStaticMapUrl($place_id) ?: get_template_directory_uri() . '/assets/images/placeholder-map.png';
    }
    
    /**
     * 도보 시간 추정
     */
    private function estimate_walking_time($current_index, $places) {
        if ($current_index === 0) return '시작점';
        
        // 간단한 추정 (실제로는 거리 계산 필요)
        $estimated_minutes = rand(3, 8);
        return $estimated_minutes . '분 도보';
    }
    
    /**
     * 텍스트 방향 안내 생성
     */
    private function generate_text_directions($current_index, $places) {
        if ($current_index === 0) {
            return '투어 시작점입니다.';
        }
        
        $directions = array(
            '직진 후 우회전하여 목적지로 이동하세요.',
            '좌회전 후 성수이로를 따라 이동하세요.',
            '성수대교 방향으로 직진하세요.',
            '뚝섬로를 따라 남쪽으로 이동하세요.',
            '성수동 문화센터 방향으로 이동하세요.'
        );
        
        return $directions[array_rand($directions)];
    }
    
    /**
     * 총 투어 시간 계산
     */
    private function calculate_total_tour_time($tour_data) {
        $total_minutes = count($tour_data) * 20; // 장소당 평균 20분
        
        $hours = floor($total_minutes / 60);
        $minutes = $total_minutes % 60;
        
        if ($hours > 0) {
            return $hours . '시간 ' . $minutes . '분';
        } else {
            return $minutes . '분';
        }
    }
    
    /**
     * 총 거리 추정
     */
    private function estimate_total_distance($tour_data) {
        $total_meters = (count($tour_data) - 1) * 350; // 장소간 평균 350m
        
        if ($total_meters >= 1000) {
            return round($total_meters / 1000, 1) . 'km';
        } else {
            return $total_meters . 'm';
        }
    }
    
    /**
     * 네비게이션 URL 생성
     */
    private function generate_navigation_url($coordinates) {
        return "https://map.naver.com/v5/directions/-/-/{$coordinates['lat']},{$coordinates['lng']}";
    }
    
    /**
     * 도착 시간 계산
     */
    private function calculate_arrival_time($order) {
        $estimated_minutes = $order * 25; // 장소당 25분 소요
        return date('H:i', strtotime("+{$estimated_minutes} minutes"));
    }
    
    /**
     * AJAX: 멤버십 상태 확인
     */
    public function ajax_check_membership_status() {
        check_ajax_referer('sungsuya_tour_nonce', 'nonce');
        
        $capabilities = $this->get_user_tour_capabilities();
        wp_send_json_success($capabilities);
    }
    
    /**
     * 회원가입 후 리다이렉트
     */
    public function custom_registration_redirect($redirect_to) {
        return home_url('/planner/?welcome=1');
    }
    
    /**
     * 로그인 후 리다이렉트
     */
    public function custom_login_redirect($redirect_to, $request, $user) {
        if (isset($_GET['redirect_to']) && $_GET['redirect_to'] === 'planner') {
            return home_url('/planner/?login=1');
        }
        return $redirect_to;
    }
}

// 시스템 초기화
new SungsuyaMembershipTourSystem();
