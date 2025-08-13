<?php
/**
 * Places 메타데이터 REST API 엔드포인트
 * 투어플래너 V2용
 */

// Places 메타데이터 조회 API
add_action('rest_api_init', function() {
    // 단일 장소 메타데이터
    register_rest_route('sungsuya/v1', '/places/(?P<id>\d+)/meta', array(
        'methods' => 'GET',
        'callback' => 'sungsuya_get_place_meta_v2',
        'permission_callback' => '__return_true',
        'args' => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
});

function sungsuya_get_place_meta_v2($request) {
    $place_id = $request['id'];
    
    // 장소 존재 확인
    $place = get_post($place_id);
    if (!$place || $place->post_type !== 'places') {
        return new WP_Error('not_found', '장소를 찾을 수 없습니다', array('status' => 404));
    }
    
    // 메타데이터 수집
    $meta = array(
        'address' => get_post_meta($place_id, 'address', true) ?: '',
        'latitude' => get_post_meta($place_id, 'latitude', true) ?: '37.5444',
        'longitude' => get_post_meta($place_id, 'longitude', true) ?: '127.0557',
        'phone' => get_post_meta($place_id, 'phone', true) ?: '',
        'hours' => get_post_meta($place_id, 'hours', true) ?: '',
        'category' => get_post_meta($place_id, 'category', true) ?: '',
        'features' => get_post_meta($place_id, 'features', true) ?: '',
        'static_map_url' => get_post_meta($place_id, 'static_map_url', true) ?: '',
        'smart_deep_links' => get_post_meta($place_id, 'smart_deep_links', true) ?: array(),
    );
    
    return rest_ensure_response($meta);
}
