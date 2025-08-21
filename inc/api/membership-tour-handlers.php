<?php
/**
 * 멤버십 기반 투어 시스템 API 핸들러
 * 
 * @package SungsuyaV2
 * @subpackage API
 * @since 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 멤버십 상태 확인 AJAX 핸들러
 */
function sungsuya_check_membership_status() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
        return;
    }
    
    $user_id = get_current_user_id();
    $current_month = date('Y-m');
    
    // 이번 달 API 사용량 가져오기
    $usage_key = "naver_api_usage_{$current_month}";
    $current_usage = (int) get_user_meta($user_id, $usage_key, true);
    
    wp_send_json_success([
        'current_usage' => $current_usage,
        'api_limit' => 10, // 월 10회 제한
        'can_use_gps' => $current_usage < 10,
        'user_id' => $user_id
    ]);
}
add_action('wp_ajax_check_membership_status', 'sungsuya_check_membership_status');

/**
 * API 사용량 기록
 */
function sungsuya_record_api_usage($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $current_month = date('Y-m');
    $usage_key = "naver_api_usage_{$current_month}";
    
    $current_usage = (int) get_user_meta($user_id, $usage_key, true);
    $new_usage = $current_usage + 1;
    
    update_user_meta($user_id, $usage_key, $new_usage);
    
    // 로그 기록 (개발환경에서만)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("User {$user_id} API usage recorded: {$new_usage}/10 for {$current_month}");
    }
    
    return $new_usage;
}

/**
 * 회원 API 사용량 조회
 */
function sungsuya_get_user_api_usage($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return 0;
    }
    
    $current_month = date('Y-m');
    $usage_key = "naver_api_usage_{$current_month}";
    
    return (int) get_user_meta($user_id, $usage_key, true);
}

/**
 * 투어 데이터 요청 AJAX 핸들러
 */
function sungsuya_get_tour_map_data() {
    check_ajax_referer('wp_rest', 'nonce');
    
    $places_ids = json_decode(stripslashes($_POST['places']), true);
    
    if (empty($places_ids) || !is_array($places_ids)) {
        wp_send_json_error('장소 ID가 제공되지 않았습니다');
        return;
    }
    
    $user_id = get_current_user_id();
    $is_logged_in = is_user_logged_in();
    $can_use_gps = false;
    
    if ($is_logged_in) {
        $current_usage = sungsuya_get_user_api_usage($user_id);
        $can_use_gps = $current_usage < 10;
    }
    
    // 투어 데이터 생성
    $tour_data = [
        'type' => $can_use_gps ? 'dynamic' : 'static',
        'api_limit_exceeded' => $is_logged_in && !$can_use_gps,
        'places' => [],
        'total_time' => '0분',
        'total_distance' => '0km'
    ];
    
    // 장소 데이터 수집
    $total_distance = 0;
    $total_time = 0;
    
    foreach ($places_ids as $index => $place_id) {
        $place_data = sungsuya_prepare_place_tour_data($place_id, $index, $tour_data, $total_distance, $total_time, $can_use_gps);
        if ($place_data) {
            $tour_data['places'][] = $place_data;
        }
    }
    
    // 총 시간과 거리 계산
    $tour_data['total_time'] = "{$total_time}분";
    $tour_data['total_distance'] = number_format($total_distance, 1) . "km";
    
    // API 사용량 기록 (동적 모드일 때만)
    if ($can_use_gps) {
        sungsuya_record_api_usage($user_id);
    }
    
    wp_send_json_success($tour_data);
}
add_action('wp_ajax_get_tour_map_data', 'sungsuya_get_tour_map_data');
add_action('wp_ajax_nopriv_get_tour_map_data', 'sungsuya_get_tour_map_data');

/**
 * 장소별 투어 데이터 준비
 */
function sungsuya_prepare_place_tour_data($place_id, $index, &$tour_data, &$total_distance, &$total_time, $can_use_gps) {
    $place = get_post($place_id);
    if (!$place || $place->post_type !== 'places') {
        return null;
    }
    
    $latitude = get_post_meta($place_id, 'latitude', true);
    $longitude = get_post_meta($place_id, 'longitude', true);
    $address = get_post_meta($place_id, 'address', true);
    $place_type = get_post_meta($place_id, 'place_type', true);
    $phone = get_post_meta($place_id, 'phone', true);
    $opening_hours = get_post_meta($place_id, 'opening_hours', true);
    
    // 정적 지도 URL
    $static_map_url = '';
    if ($latitude && $longitude) {
        $static_map_id = get_post_meta($place_id, 'static_map_image_id', true);
        if ($static_map_id) {
            $static_map_url = wp_get_attachment_url($static_map_id);
        }
    }
    
    // 도보 시간 계산
    $walking_time = '';
    $directions_text = '';
    
    if ($index > 0 && isset($tour_data['places'][$index - 1])) {
        $prev_place = $tour_data['places'][$index - 1];
        if (isset($prev_place['lat']) && isset($prev_place['lng'])) {
            $distance = sungsuya_calculate_distance(
                $prev_place['lat'], $prev_place['lng'],
                (float)$latitude, (float)$longitude
            );
            
            $walk_time = ceil($distance * 12); // 1km당 12분
            $walking_time = "{$walk_time}분 도보";
            $directions_text = "이전 장소에서 " . number_format($distance, 1) . "km";
            
            $total_distance += $distance;
            $total_time += $walk_time;
        }
    }
    
    // 장소당 체류 시간 (30분)
    $total_time += 30;
    
    $place_data = [
        'id' => $place_id,
        'title' => $place->post_title,
        'type' => $place_type ?: 'restaurant',
        'address' => $address ?: '',
        'lat' => (float)$latitude,
        'lng' => (float)$longitude,
        'order' => $index + 1,
        'static_map_url' => $static_map_url,
        'walking_time' => $walking_time,
        'directions_text' => $directions_text,
        'features' => [
            'subway_info' => "성수역 3번 출구",
            'opening_hours' => $opening_hours ?: '영업시간 미제공',
            'phone' => $phone ?: ''
        ]
    ];
    
    // GPS 네비게이션 URL (회원이고 API 사용량 남은 경우)
    if ($can_use_gps && $latitude && $longitude) {
        $place_data['navigation_url'] = "https://map.naver.com/v5/directions/-/-/{$latitude},{$longitude}";
    }
    
    return $place_data;
}

/**
 * 멤버십 투어 가이드 요청 AJAX 핸들러
 */
function sungsuya_get_membership_tour_guide() {
    check_ajax_referer('wp_rest', 'nonce');
    
    $places_ids = json_decode(stripslashes($_POST['places']), true);
    $user_id = get_current_user_id();
    $is_logged_in = is_user_logged_in();
    
    if (empty($places_ids) || !is_array($places_ids)) {
        wp_send_json_error('장소 정보가 필요합니다');
        return;
    }
    
    // 멤버십 상태 확인
    $can_use_gps = false;
    if ($is_logged_in) {
        $current_usage = sungsuya_get_user_api_usage($user_id);
        $can_use_gps = $current_usage < 10;
    }
    
    // 투어 가이드 생성
    $guide_data = [
        'membership_type' => $is_logged_in ? 'premium' : 'basic',
        'api_usage' => $is_logged_in ? sungsuya_get_user_api_usage($user_id) : 0,
        'api_limit' => 10,
        'can_use_gps' => $can_use_gps,
        'tour_type' => $can_use_gps ? 'GPS 네비게이션' : '스마트 투어 카드',
        'places' => [],
        'summary' => [
            'total_places' => count($places_ids),
            'estimated_time' => count($places_ids) * 35, // 30분 체류 + 5분 이동
            'tour_mode' => $can_use_gps ? 'premium' : 'basic'
        ]
    ];
    
    // 장소별 상세 정보 생성
    foreach ($places_ids as $index => $place_id) {
        $place = get_post($place_id);
        if (!$place || $place->post_type !== 'places') {
            continue;
        }
        
        $place_info = [
            'id' => $place_id,
            'title' => $place->post_title,
            'order' => $index + 1,
            'estimated_time' => '30분 체류',
            'features' => [
                'static_info' => '기본 정보 제공',
                'directions' => $can_use_gps ? 'GPS 네비게이션' : '텍스트 방향안내'
            ]
        ];
        
        // 위치 정보
        $latitude = get_post_meta($place_id, 'latitude', true);
        $longitude = get_post_meta($place_id, 'longitude', true);
        
        if ($latitude && $longitude) {
            $place_info['location'] = [
                'lat' => (float)$latitude,
                'lng' => (float)$longitude,
                'address' => get_post_meta($place_id, 'address', true)
            ];
        }
        
        $guide_data['places'][] = $place_info;
    }
    
    // API 사용량 기록
    if ($can_use_gps && count($places_ids) > 0) {
        sungsuya_record_api_usage($user_id);
    }
    
    wp_send_json_success($guide_data);
}
add_action('wp_ajax_get_membership_tour_guide', 'sungsuya_get_membership_tour_guide');
add_action('wp_ajax_nopriv_get_membership_tour_guide', 'sungsuya_get_membership_tour_guide');

/**
 * 멤버십 API 사용량 실시간 조회
 */
function sungsuya_get_api_usage_status() {
    if (!is_user_logged_in()) {
        wp_send_json_success([
            'is_logged_in' => false,
            'current_usage' => 0,
            'api_limit' => 10,
            'can_use_gps' => false,
            'message' => '회원가입하면 월 10회 GPS 네비게이션을 이용할 수 있습니다'
        ]);
        return;
    }
    
    $user_id = get_current_user_id();
    $current_usage = sungsuya_get_user_api_usage($user_id);
    $can_use_gps = $current_usage < 10;
    
    wp_send_json_success([
        'is_logged_in' => true,
        'user_id' => $user_id,
        'current_usage' => $current_usage,
        'api_limit' => 10,
        'can_use_gps' => $can_use_gps,
        'remaining_usage' => max(0, 10 - $current_usage),
        'usage_percentage' => min(100, ($current_usage / 10) * 100),
        'message' => $can_use_gps ? 
            "이번 달 {$current_usage}/10회 사용" : 
            '이번 달 GPS 네비게이션 한도를 모두 사용했습니다'
    ]);
}
add_action('wp_ajax_get_api_usage_status', 'sungsuya_get_api_usage_status');

/**
 * 투어 시작 시 멤버십 혜택 적용
 */
function sungsuya_start_membership_tour() {
    check_ajax_referer('wp_rest', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('로그인이 필요합니다');
        return;
    }
    
    $user_id = get_current_user_id();
    $current_usage = sungsuya_get_user_api_usage($user_id);
    
    if ($current_usage >= 10) {
        wp_send_json_error('이번 달 API 사용 한도를 초과했습니다');
        return;
    }
    
    // 투어 시작 로그 기록
    $new_usage = sungsuya_record_api_usage($user_id);
    
    wp_send_json_success([
        'message' => 'GPS 네비게이션 투어가 시작되었습니다',
        'new_usage' => $new_usage,
        'remaining' => max(0, 10 - $new_usage),
        'tour_mode' => 'premium'
    ]);
}
add_action('wp_ajax_start_membership_tour', 'sungsuya_start_membership_tour');

/**
 * 두 지점 간 거리 계산 (하버사인 공식)
 */
if (!function_exists('sungsuya_calculate_distance')) {
    function sungsuya_calculate_distance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371; // km
        
        $lat1_rad = deg2rad($lat1);
        $lat2_rad = deg2rad($lat2);
        $delta_lat = deg2rad($lat2 - $lat1);
        $delta_lon = deg2rad($lon2 - $lon1);
        
        $a = sin($delta_lat/2) * sin($delta_lat/2) +
             cos($lat1_rad) * cos($lat2_rad) *
             sin($delta_lon/2) * sin($delta_lon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earth_radius * $c;
    }
}
