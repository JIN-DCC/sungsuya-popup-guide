<?php
/**
 * WordPress REST API 메타필드 등록 - 대량크롤링 메타필드 문제 해결
 * Python에서 전달한 메타필드가 WordPress REST API에서 접근 가능하도록 등록
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 🔥 핵심: WordPress REST API에 Places 메타필드 등록
 * 
 * Python에서 전송한 메타필드가 WordPress에 저장되도록 하려면
 * REST API에 해당 메타필드를 등록해야 함
 */
function register_places_meta_fields_for_rest() {
    
    // 주소 관련 메타필드들 (핵심)
    register_post_meta('places', 'address', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => '장소 주소',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', 'location_address', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => '위치 주소',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', 'full_address', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => '전체 주소',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_place_address', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => 'Places 전용 주소 필드',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    // 기본 정보 메타필드들
    register_post_meta('places', '_place_name', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => '장소명',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_place_phone', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => '전화번호',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_place_category', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => '카테고리',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_place_specialty', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => '특징',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_place_price_range', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => '가격대',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_place_rating', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'number',
        'description' => '평점',
        'sanitize_callback' => 'floatval',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_place_quality_score', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'number',
        'description' => '품질 점수',
        'sanitize_callback' => 'floatval',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    // 크롤링 정보 메타필드들
    register_post_meta('places', '_bulk_crawled', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'boolean',
        'description' => '대량크롤링 여부',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_bulk_crawled_v2', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'boolean',
        'description' => '대량크롤링 v2 여부',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_crawled_at', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => '크롤링 일시',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_enhanced_v2_enabled', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'boolean',
        'description' => 'Enhanced v2 활성화',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    // 운영 정보 메타필드들
    register_post_meta('places', 'operating_status', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => '운영 상태',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', 'place_type', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => '장소 타입',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    // 좌표 메타필드들
    register_post_meta('places', 'latitude', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => '위도',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', 'longitude', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => '경도',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_place_latitude', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => 'Places 전용 위도',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_place_longitude', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => 'Places 전용 경도',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_needs_geocoding', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'boolean',
        'description' => '지오코딩 필요 여부',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    // Smart Deep Link v7.0 메타필드들
    register_post_meta('places', '_smart_deep_links_data', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => 'Smart Deep Link 데이터',
        'sanitize_callback' => 'sanitize_textarea_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_smart_deep_links_enabled', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'boolean',
        'description' => 'Smart Deep Link 활성화',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_smart_deep_links_version', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => 'Smart Deep Link 버전',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    register_post_meta('places', '_smart_deep_links_auto_generated', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'boolean',
        'description' => 'Smart Deep Link 자동 생성 여부',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
    
    // 로그 기록
    error_log('[META_FIELDS_REST] Places 메타필드 REST API 등록 완료');
}

// REST API 초기화 시 메타필드 등록
add_action('rest_api_init', 'register_places_meta_fields_for_rest');

/**
 * 메타필드 저장 확인용 디버그 함수
 */
function debug_places_meta_save($post_id, $post, $update) {
    // Places 포스트 타입만 확인
    if ($post->post_type !== 'places') {
        return;
    }
    
    // 자동 저장이나 리비전 무시
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }
    
    // 주소 메타필드 확인
    $address = get_post_meta($post_id, 'address', true);
    $place_address = get_post_meta($post_id, '_place_address', true);
    $bulk_crawled = get_post_meta($post_id, '_bulk_crawled', true);
    
    error_log("[META_SAVE_DEBUG] Post ID: {$post_id}");
    error_log("[META_SAVE_DEBUG] Title: {$post->post_title}");
    error_log("[META_SAVE_DEBUG] address: '{$address}'");
    error_log("[META_SAVE_DEBUG] _place_address: '{$place_address}'");
    error_log("[META_SAVE_DEBUG] _bulk_crawled: " . ($bulk_crawled ? 'true' : 'false'));
    
    // 크롤링 데이터인지 확인
    if ($bulk_crawled && !empty($address)) {
        error_log("[META_SAVE_DEBUG] ✅ 대량크롤링 데이터 저장 성공: {$post->post_title}");
        
        // 주소검색 트리거 필요 표시
        update_post_meta($post_id, '_needs_address_search', true);
        
    } elseif ($bulk_crawled && empty($address)) {
        error_log("[META_SAVE_DEBUG] ⚠️  대량크롤링 데이터이지만 주소 없음: {$post->post_title}");
        
    } else {
        error_log("[META_SAVE_DEBUG] ℹ️  일반 Places 데이터: {$post->post_title}");
    }
}

// 개발환경에서만 디버그 활성화
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('save_post', 'debug_places_meta_save', 10, 3);
}

/**
 * 🔧 메타필드 값 검증 함수
 */
function validate_places_meta_field($value, $object_id, $meta_key) {
    // 주소 필드 검증
    if (in_array($meta_key, ['address', '_place_address', 'location_address', 'full_address'])) {
        if (empty($value)) {
            return new WP_Error('invalid_address', '주소는 필수 입력값입니다.');
        }
        
        // 최소 길이 검증
        if (strlen($value) < 5) {
            return new WP_Error('address_too_short', '주소가 너무 짧습니다.');
        }
    }
    
    // 평점 검증 (0-5 사이)
    if ($meta_key === '_place_rating') {
        $rating = floatval($value);
        if ($rating < 0 || $rating > 5) {
            return new WP_Error('invalid_rating', '평점은 0-5 사이의 값이어야 합니다.');
        }
    }
    
    // 품질 점수 검증 (0-1 사이)
    if ($meta_key === '_place_quality_score') {
        $score = floatval($value);
        if ($score < 0 || $score > 1) {
            return new WP_Error('invalid_quality_score', '품질 점수는 0-1 사이의 값이어야 합니다.');
        }
    }
    
    return $value;
}

// 메타필드 저장 전 검증 (선택사항)
// add_filter('sanitize_post_meta_address', 'validate_places_meta_field', 10, 3);

/**
 * 🚀 REST API 응답에 메타필드 포함 확인
 */
function ensure_meta_in_rest_response($response, $post, $request) {
    if ($post->post_type !== 'places') {
        return $response;
    }
    
    // 중요한 메타필드들이 응답에 포함되었는지 확인
    $important_fields = ['address', '_place_address', '_bulk_crawled'];
    $meta_data = $response->get_data()['meta'] ?? array();
    
    foreach ($important_fields as $field) {
        if (!isset($meta_data[$field])) {
            $value = get_post_meta($post->ID, $field, true);
            if ($value !== '') {
                $response->data['meta'][$field] = $value;
            }
        }
    }
    
    return $response;
}

// REST API 응답 수정 (필요시)
// add_filter('rest_prepare_places', 'ensure_meta_in_rest_response', 10, 3);

/**
 * 대량크롤링 완료 후 자동 주소검색 트리거 시스템
 */
function trigger_address_search_for_bulk_crawled() {
    // 주소검색이 필요한 Places 검색
    $places_needing_geocoding = new WP_Query(array(
        'post_type' => 'places',
        'post_status' => 'publish',
        'posts_per_page' => 20, // 한번에 20개씩 처리
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_bulk_crawled',
                'value' => true,
                'compare' => '='
            ),
            array(
                'key' => '_needs_address_search',
                'value' => true,
                'compare' => '='
            ),
            array(
                'key' => 'address',
                'value' => '',
                'compare' => '!='
            )
        )
    ));
    
    if ($places_needing_geocoding->have_posts()) {
        foreach ($places_needing_geocoding->posts as $place) {
            $address = get_post_meta($place->ID, 'address', true);
            
            if (!empty($address)) {
                // 주소검색 실행 (실제 주소검색 함수 호출)
                if (function_exists('sungsuya_geocode_address')) {
                    $result = sungsuya_geocode_address($address);
                    
                    if ($result && !empty($result['latitude']) && !empty($result['longitude'])) {
                        // 좌표 업데이트
                        update_post_meta($place->ID, 'latitude', $result['latitude']);
                        update_post_meta($place->ID, 'longitude', $result['longitude']);
                        update_post_meta($place->ID, '_place_latitude', $result['latitude']);
                        update_post_meta($place->ID, '_place_longitude', $result['longitude']);
                        
                        // 주소검색 완료 표시
                        delete_post_meta($place->ID, '_needs_address_search');
                        update_post_meta($place->ID, '_geocoded_bulk', true);
                        
                        error_log("[AUTO_GEOCODING] 성공: {$place->post_title} -> {$result['latitude']}, {$result['longitude']}");
                    }
                }
            }
        }
    }
}

// 정기적으로 실행 (WordPress cron 사용)
if (!wp_next_scheduled('trigger_bulk_address_search')) {
    wp_schedule_event(time(), 'hourly', 'trigger_bulk_address_search');
}
add_action('trigger_bulk_address_search', 'trigger_address_search_for_bulk_crawled');
