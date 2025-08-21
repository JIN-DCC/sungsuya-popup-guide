<?php
/**
 * 성수야! V2 - REST API 엔드포인트
 * 
 * 프론트엔드에서 사용할 REST API 정의
 * 
 * @package SungsuyaV2
 * @version 2.0.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API 엔드포인트 등록
 */
function sungsuya_register_rest_routes() {
    // API 네임스페이스
    $namespace = 'sungsuya/v2';
    $namespace_v1 = 'sungsuya/v1';  // v1 네임스페이스 추가
    
    // 스토어 목록 조회
    register_rest_route($namespace, '/stores', array(
        'methods' => 'GET',
        'callback' => 'sungsuya_get_stores',
        'permission_callback' => '__return_true',
        'args' => array(
            'featured' => array(
                'description' => '인기 스토어만 조회',
                'type' => 'boolean',
                'default' => false,
            ),
            'limit' => array(
                'description' => '조회할 스토어 수',
                'type' => 'integer',
                'default' => -1,
            ),
            'category' => array(
                'description' => '카테고리 필터',
                'type' => 'string',
                'default' => '',
            ),
        ),
    ));
    
    // 개별 스토어 조회
    register_rest_route($namespace, '/stores/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'sungsuya_get_store',
        'permission_callback' => '__return_true',
        'args' => array(
            'id' => array(
                'description' => '스토어 ID',
                'type' => 'integer',
                'required' => true,
            ),
        ),
    ));
    
    // 투어 플래너 경로 최적화 (통합 장소 지원)
    register_rest_route($namespace, '/planner/optimize', array(
        'methods' => 'POST',
        'callback' => 'sungsuya_optimize_route',
        'permission_callback' => '__return_true',
        'args' => array(
            'places' => array(
                'description' => '최적화할 장소 목록 (팝업스토어 + Places 통합)',
                'type' => 'array',
                'required' => true,
            ),
            // 하위 호환성을 위해 stores도 지원
            'stores' => array(
                'description' => '최적화할 스토어 목록 (레거시)',
                'type' => 'array',
                'required' => false,
            ),
        ),
    ));
    
    // 🆕 Phase 3: 통합 장소 목록 조회 (팝업스토어 + Places)
    register_rest_route($namespace, '/all-places', array(
        'methods' => 'GET',
        'callback' => 'sungsuya_get_all_places',
        'permission_callback' => '__return_true',
        'args' => array(
            'type' => array(
                'description' => '장소 유형 필터 (popup_store, restaurant, retail_store, facility)',
                'type' => 'string',
                'default' => 'all',
            ),
            'category' => array(
                'description' => '카테고리 필터',
                'type' => 'string',
                'default' => '',
            ),
            'limit' => array(
                'description' => '조회할 장소 수',
                'type' => 'integer',
                'default' => -1,
            ),
            'featured' => array(
                'description' => '추천 장소만 조회',
                'type' => 'boolean',
                'default' => false,
            ),
        ),
    ));
    
    // 🆕 Phase 3: 통합 장소 상세 조회
    register_rest_route($namespace, '/all-places/(?P<type>\w+)/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'sungsuya_get_unified_place',
        'permission_callback' => '__return_true',
        'args' => array(
            'type' => array(
                'description' => '장소 유형 (popup_store, places)',
                'type' => 'string',
                'required' => true,
            ),
            'id' => array(
                'description' => '장소 ID',
                'type' => 'integer',
                'required' => true,
            ),
        ),
    ));
    
    // 🆕 Phase 3: 통합 검색 엔드포인트
    register_rest_route($namespace, '/search-places', array(
        'methods' => 'GET',
        'callback' => 'sungsuya_search_all_places',
        'permission_callback' => '__return_true',
        'args' => array(
            'query' => array(
                'description' => '검색어',
                'type' => 'string',
                'required' => true,
            ),
            'type' => array(
                'description' => '검색할 장소 유형',
                'type' => 'string',
                'default' => 'all',
            ),
            'limit' => array(
                'description' => '결과 수 제한',
                'type' => 'integer',
                'default' => 10,
            ),
        ),
    ));
    
    // 🆕 장소 메타데이터 조회 (v1 호환성)
    register_rest_route($namespace_v1, '/places/(?P<id>\d+)/meta', array(
        'methods' => 'GET',
        'callback' => 'sungsuya_rest_get_place_meta',
        'permission_callback' => '__return_true',
        'args' => array(
            'id' => array(
                'description' => '장소 ID',
                'type' => 'integer',
                'required' => true,
            ),
        ),
    ));
}
add_action('rest_api_init', 'sungsuya_register_rest_routes');

/**
 * 🆕 장소 메타데이터 조회 API
 */
function sungsuya_rest_get_place_meta($request) {
    $place_id = $request->get_param('id');
    
    $post = get_post($place_id);
    
    if (!$post || $post->post_type !== 'places') {
        return new WP_Error('place_not_found', '장소를 찾을 수 없습니다.', array('status' => 404));
    }
    
    // 모든 메타데이터 가져오기
    $meta = get_post_meta($post->ID);
    
    // 정리된 메타데이터 반환
    $clean_meta = array();
    foreach ($meta as $key => $value) {
        // 언더스코어로 시작하는 내부 메타는 제외
        if (strpos($key, '_') !== 0) {
            $clean_meta[$key] = is_array($value) && count($value) === 1 ? $value[0] : $value;
        }
    }
    
    // 특별히 필요한 메타데이터 추가
    $clean_meta['latitude'] = isset($meta['latitude'][0]) ? $meta['latitude'][0] : '';
    $clean_meta['longitude'] = isset($meta['longitude'][0]) ? $meta['longitude'][0] : '';
    $clean_meta['address'] = isset($meta['address'][0]) ? $meta['address'][0] : '';
    $clean_meta['phone'] = isset($meta['phone'][0]) ? $meta['phone'][0] : '';
    $clean_meta['website'] = isset($meta['website'][0]) ? $meta['website'][0] : '';
    $clean_meta['opening_hours'] = isset($meta['opening_hours'][0]) ? $meta['opening_hours'][0] : '';
    
    return $clean_meta;
}

/**
 * 스토어 목록 조회 API
 */
function sungsuya_get_stores($request) {
    $featured = $request->get_param('featured');
    $limit = $request->get_param('limit');
    $category = $request->get_param('category');
    
    $args = array(
        'post_type' => 'popup_store',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    
    // 인기 스토어 필터
    if ($featured) {
        $args['meta_query'] = array(
            array(
                'key' => '_featured',
                'value' => '1',
                'compare' => '='
            )
        );
    }
    
    // 카테고리 필터
    if (!empty($category)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'store_category',
                'field' => 'slug',
                'terms' => $category,
            )
        );
    }
    
    $query = new WP_Query($args);
    $stores = array();
    
    foreach ($query->posts as $post) {
        $stores[] = sungsuya_format_store_data($post);
    }
    
    wp_reset_postdata();
    
    return array(
        'stores' => $stores,
        'total' => $query->found_posts,
    );
}

/**
 * 개별 스토어 조회 API
 */
function sungsuya_get_store($request) {
    $store_id = $request->get_param('id');
    
    $post = get_post($store_id);
    
    if (!$post || $post->post_type !== 'popup_store') {
        return new WP_Error('store_not_found', '스토어를 찾을 수 없습니다.', array('status' => 404));
    }
    
    return array(
        'store' => sungsuya_format_store_data($post)
    );
}

/**
 * 스토어 데이터 포맷팅
 */
function sungsuya_format_store_data($post) {
    $meta = get_post_meta($post->ID);
    $terms = wp_get_post_terms($post->ID, 'store_category');
    
    // 위치 정보
    $latitude = isset($meta['_store_latitude'][0]) ? floatval($meta['_store_latitude'][0]) : null;
    $longitude = isset($meta['_store_longitude'][0]) ? floatval($meta['_store_longitude'][0]) : null;
    
    $location = null;
    if ($latitude && $longitude) {
        $location = array(
            'lat' => $latitude,
            'lng' => $longitude,
            'address' => isset($meta['_store_address'][0]) ? $meta['_store_address'][0] : '',
        );
    }
    
    // 대표 이미지
    $featured_image = get_the_post_thumbnail_url($post->ID, 'large');
    
    // 카테고리
    $category = '';
    if (!empty($terms)) {
        $category = $terms[0]->name;
    }
    
    return array(
        'id' => $post->ID,
        'title' => $post->post_title,
        'content' => apply_filters('the_content', $post->post_content),
        'excerpt' => $post->post_excerpt ?: wp_trim_words($post->post_content, 30),
        'image' => $featured_image,
        'category' => $category,
        'location' => $location,
        'opening_hours' => isset($meta['_opening_hours'][0]) ? $meta['_opening_hours'][0] : '',
        'price_range' => isset($meta['_price_range'][0]) ? $meta['_price_range'][0] : '',
        'contact_info' => isset($meta['_contact_info'][0]) ? $meta['_contact_info'][0] : '',
        'start_date' => isset($meta['_start_date'][0]) ? $meta['_start_date'][0] : '',
        'end_date' => isset($meta['_end_date'][0]) ? $meta['_end_date'][0] : '',
        'featured' => isset($meta['_featured'][0]) ? $meta['_featured'][0] === '1' : false,
        'permalink' => get_permalink($post->ID),
        'created_date' => $post->post_date,
    );
}

/**
 * 투어 경로 최적화 API (통합 장소 지원)
 * 팝업스토어 + Places 모든 유형의 장소를 지원하는 TSP 알고리즘
 */
function sungsuya_optimize_route($request) {
    // 통합 파라미터 처리 (places 우선, 하위 호환성으로 stores도 지원)
    $places = $request->get_param('places') ?: $request->get_param('stores');
    
    if (!$places || count($places) < 2) {
        return new WP_Error('insufficient_places', '최소 2개 이상의 장소가 필요합니다.', array('status' => 400));
    }
    
    // 좌표 데이터 검증 및 정규화
    $valid_places = array();
    foreach ($places as $index => $place) {
        $normalized_place = sungsuya_normalize_place_coordinates($place, $index);
        if ($normalized_place) {
            $valid_places[] = $normalized_place;
        }
    }
    
    if (count($valid_places) < 2) {
        return new WP_Error('insufficient_coordinates', '유효한 좌표를 가진 장소가 2개 미만입니다.', array('status' => 400));
    }
    
    // 간단한 최근접 이웃 알고리즘으로 경로 최적화
    $optimized_order = sungsuya_nearest_neighbor_tsp($valid_places);
    
    // 총 거리 계산
    $total_distance = sungsuya_calculate_total_distance($valid_places, $optimized_order);
    
    // 예상 시간 계산 (도보 기준, km당 12분)
    $estimated_time = round($total_distance * 12);
    
    // 장소 유형별 통계
    $place_stats = sungsuya_calculate_place_statistics($valid_places, $optimized_order);
    
    return array(
        'optimized_route' => $optimized_order,
        'total_distance' => round($total_distance, 2),
        'estimated_time' => $estimated_time,
        'estimated_time_formatted' => sungsuya_format_duration($estimated_time),
        'places_count' => count($valid_places),
        'place_types' => $place_stats['types'],
        'place_type_counts' => $place_stats['type_counts'],
        'route_summary' => sungsuya_generate_route_summary($valid_places, $optimized_order),
    );
}

/**
 * 🆕 장소 좌표 데이터 정규화 (팝업스토어 + Places 통합 지원)
 * @param array $place 장소 데이터
 * @param int $index 원본 인덱스
 * @return array|null 정규화된 장소 데이터
 */
function sungsuya_normalize_place_coordinates($place, $index) {
    $normalized = array(
        'original_index' => $index,
        'id' => isset($place['id']) ? $place['id'] : $index,
        'title' => isset($place['title']) ? $place['title'] : "장소 {$index}",
        'place_type' => isset($place['place_type']) ? $place['place_type'] : 'unknown',
        'type_label' => isset($place['type_label']) ? $place['type_label'] : '장소',
        'source' => isset($place['source']) ? $place['source'] : 'unknown'
    );
    
    // 좌표 추출 (다양한 형태 지원)
    $lat = null;
    $lng = null;
    
    // 직접 lat, lng 키가 있는 경우
    if (isset($place['lat']) && isset($place['lng'])) {
        $lat = floatval($place['lat']);
        $lng = floatval($place['lng']);
    }
    // location 객체가 있는 경우
    elseif (isset($place['location']) && is_array($place['location'])) {
        if (isset($place['location']['lat']) && isset($place['location']['lng'])) {
            $lat = floatval($place['location']['lat']);
            $lng = floatval($place['location']['lng']);
        }
    }
    // 레거시 스토어 형태인 경우
    elseif (isset($place['location']) && is_object($place['location'])) {
        $location = (array) $place['location'];
        if (isset($location['lat']) && isset($location['lng'])) {
            $lat = floatval($location['lat']);
            $lng = floatval($location['lng']);
        }
    }
    
    // 유효한 좌표인지 확인 (대한민국 범위)
    if ($lat && $lng && $lat >= 33 && $lat <= 39 && $lng >= 124 && $lng <= 132) {
        $normalized['lat'] = $lat;
        $normalized['lng'] = $lng;
        return $normalized;
    }
    
    // 좌표가 없거나 유효하지 않은 경우 로그
    error_log("성수야 V2: 장소 '{$normalized['title']}'의 좌표가 유효하지 않습니다. lat: {$lat}, lng: {$lng}");
    return null;
}

/**
 * 🆕 장소 유형별 통계 계산
 * @param array $places 장소 목록
 * @param array $order 최적화된 순서
 * @return array 통계 데이터
 */
function sungsuya_calculate_place_statistics($places, $order) {
    $types = array();
    $type_counts = array();
    
    foreach ($order as $index) {
        $place = $places[$index];
        $place_type = $place['place_type'];
        
        if (!in_array($place_type, $types)) {
            $types[] = $place_type;
        }
        
        if (!isset($type_counts[$place_type])) {
            $type_counts[$place_type] = 0;
        }
        $type_counts[$place_type]++;
    }
    
    return array(
        'types' => $types,
        'type_counts' => $type_counts
    );
}

/**
 * 🆕 투어 경로 요약 생성
 * @param array $places 장소 목록
 * @param array $order 최적화된 순서
 * @return array 경로 요약
 */
function sungsuya_generate_route_summary($places, $order) {
    $summary = array();
    
    foreach ($order as $step => $index) {
        $place = $places[$index];
        $summary[] = array(
            'step' => $step + 1,
            'place_id' => $place['id'],
            'title' => $place['title'],
            'place_type' => $place['place_type'],
            'type_label' => $place['type_label'],
            'source' => $place['source'],
            'coordinates' => array(
                'lat' => $place['lat'],
                'lng' => $place['lng']
            )
        );
    }
    
    return $summary;
}

/**
 * 최근접 이웃 TSP 알고리즘 (통합 데이터 지원)
 */
function sungsuya_nearest_neighbor_tsp($places) {
    $n = count($places);
    if ($n <= 2) {
        return array_keys($places);
    }
    
    $visited = array_fill(0, $n, false);
    $route = array();
    
    // 첫 번째 장소부터 시작
    $current = 0;
    $visited[$current] = true;
    $route[] = $current;
    
    for ($i = 1; $i < $n; $i++) {
        $nearest = -1;
        $min_distance = PHP_FLOAT_MAX;
        
        for ($j = 0; $j < $n; $j++) {
            if (!$visited[$j]) {
                $distance = sungsuya_calculate_distance(
                    $places[$current]['lat'], $places[$current]['lng'],
                    $places[$j]['lat'], $places[$j]['lng']
                );
                
                if ($distance < $min_distance) {
                    $min_distance = $distance;
                    $nearest = $j;
                }
            }
        }
        
        if ($nearest !== -1) {
            $visited[$nearest] = true;
            $route[] = $nearest;
            $current = $nearest;
        }
    }
    
    return $route;
}

/**
 * 전체 경로의 총 거리 계산 (통합 데이터 지원)
 */
function sungsuya_calculate_total_distance($places, $order) {
    $total = 0;
    
    for ($i = 0; $i < count($order) - 1; $i++) {
        $current = $places[$order[$i]];
        $next = $places[$order[$i + 1]];
        
        $total += sungsuya_calculate_distance(
            $current['lat'], $current['lng'],
            $next['lat'], $next['lng']
        );
    }
    
    return $total;
}

/**
 * 두 좌표 간의 거리 계산 (Haversine formula)
 */
function sungsuya_calculate_distance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371; // km
    
    $lat_diff = deg2rad($lat2 - $lat1);
    $lng_diff = deg2rad($lng2 - $lng1);
    
    $a = sin($lat_diff / 2) * sin($lat_diff / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lng_diff / 2) * sin($lng_diff / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earth_radius * $c;
}

/**
 * 시간 포맷팅
 */
function sungsuya_format_duration($minutes) {
    if ($minutes < 60) {
        return $minutes . '분';
    }
    
    $hours = floor($minutes / 60);
    $remaining_minutes = $minutes % 60;
    
    if ($remaining_minutes > 0) {
        return $hours . '시간 ' . $remaining_minutes . '분';
    }
    
    return $hours . '시간';
}

/**
 * 🆕 Phase 3: 통합 장소 목록 조회 API (팝업스토어 + Places)
 */
function sungsuya_get_all_places($request) {
    $type = $request->get_param('type');
    $category = $request->get_param('category');
    $limit = $request->get_param('limit');
    $featured = $request->get_param('featured');
    
    $all_places = array();
    $total_count = 0;
    
    // 1. 팝업스토어 데이터 조회
    if ($type === 'all' || $type === 'popup_store') {
        $popup_args = array(
            'post_type' => 'popup_store',
            'post_status' => 'publish',
            'posts_per_page' => ($limit > 0 && $type === 'popup_store') ? $limit : -1,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        if ($featured) {
            $popup_args['meta_query'] = array(
                array(
                    'key' => '_featured',
                    'value' => '1',
                    'compare' => '='
                )
            );
        }
        
        if (!empty($category)) {
            $popup_args['tax_query'] = array(
                array(
                    'taxonomy' => 'store_category',
                    'field' => 'slug',
                    'terms' => $category,
                )
            );
        }
        
        $popup_query = new WP_Query($popup_args);
        foreach ($popup_query->posts as $post) {
            $place_data = sungsuya_format_store_data($post);
            $place_data['place_type'] = 'popup_store';
            $place_data['type_label'] = '팝업스토어';
            $place_data['source'] = 'popup_store';
            $all_places[] = $place_data;
        }
        $total_count += $popup_query->found_posts;
        wp_reset_postdata();
    }
    
    // 2. Places 데이터 조회
    if ($type === 'all' || in_array($type, array('restaurant', 'retail_store', 'facility'))) {
        $places_args = array(
            'post_type' => 'places',
            'post_status' => 'publish',
            'posts_per_page' => ($limit > 0 && $type !== 'all') ? $limit : -1,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        // 택소노미 기반 유형 필터링
        $tax_query = array();
        
        if ($type !== 'all') {
            $tax_query[] = array(
                'taxonomy' => 'place_type',
                'field' => 'slug',
                'terms' => $type,
            );
        }
        
        if (!empty($category)) {
            $tax_query[] = array(
                'taxonomy' => 'place_category',
                'field' => 'slug',
                'terms' => $category,
            );
        }
        
        if (!empty($tax_query)) {
            $places_args['tax_query'] = $tax_query;
            if (count($tax_query) > 1) {
                $places_args['tax_query']['relation'] = 'AND';
            }
        }
        
        // featured 메타 필드 처리
        if ($featured) {
            $places_args['meta_query'] = array(
                array(
                    'key' => 'featured',
                    'value' => '1',
                    'compare' => '='
                )
            );
        }
        
        $places_query = new WP_Query($places_args);
        foreach ($places_query->posts as $post) {
            $place_data = sungsuya_format_places_data($post);
            $all_places[] = $place_data;
        }
        $total_count += $places_query->found_posts;
        wp_reset_postdata();
    }
    
    // 3. 결과 정렬 및 제한
    if ($limit > 0 && $type === 'all') {
        // 최신 날짜 순으로 정렬
        usort($all_places, function($a, $b) {
            return strtotime($b['created_date']) - strtotime($a['created_date']);
        });
        $all_places = array_slice($all_places, 0, $limit);
    }
    
    return array(
        'places' => $all_places,
        'total' => $total_count,
        'types_available' => array('popup_store', 'restaurant', 'retail_store', 'facility'),
        'query_params' => array(
            'type' => $type,
            'category' => $category,
            'limit' => $limit,
            'featured' => $featured
        )
    );
}

/**
 * 🆕 Phase 3: Places 데이터 포매팅 함수
 */
function sungsuya_format_places_data($post) {
    $meta = get_post_meta($post->ID);
    
    // taxonomy로부터 place_type 가져오기
    $place_types = wp_get_post_terms($post->ID, 'place_type');
    $place_type = !empty($place_types) ? $place_types[0]->slug : 'restaurant';
    
    // 위치 정보
    $latitude = isset($meta['latitude'][0]) ? floatval($meta['latitude'][0]) : null;
    $longitude = isset($meta['longitude'][0]) ? floatval($meta['longitude'][0]) : null;
    
    $location = null;
    if ($latitude && $longitude) {
        $location = array(
            'lat' => $latitude,
            'lng' => $longitude,
            'address' => isset($meta['address'][0]) ? $meta['address'][0] : '',
        );
    }
    
    // 대표 이미지
    $featured_image = get_the_post_thumbnail_url($post->ID, 'large');
    
    // 카테고리
    $category_terms = wp_get_post_terms($post->ID, 'place_category');
    $category = '';
    if (!empty($category_terms)) {
        $category = $category_terms[0]->name;
    }
    
    // 유형별 라벨
    $type_labels = array(
        'restaurant' => '맛집',
        'retail_store' => '상설매장',
        'facility' => '편의시설'
    );
    
    return array(
        'id' => $post->ID,
        'title' => $post->post_title,
        'content' => apply_filters('the_content', $post->post_content),
        'excerpt' => $post->post_excerpt ?: wp_trim_words($post->post_content, 30),
        'image' => $featured_image,
        'category' => $category,
        'location' => $location,
        'place_type' => $place_type,
        'type_label' => isset($type_labels[$place_type]) ? $type_labels[$place_type] : $place_type,
        'source' => 'places',
        'opening_hours' => isset($meta['opening_hours'][0]) ? $meta['opening_hours'][0] : '',
        'price_range' => isset($meta['price_range'][0]) ? $meta['price_range'][0] : '',
        'contact_info' => isset($meta['phone'][0]) ? $meta['phone'][0] : '',
        'featured' => isset($meta['featured'][0]) ? $meta['featured'][0] === '1' : false,
        'permalink' => get_permalink($post->ID),
        'created_date' => $post->post_date,
        'website' => isset($meta['website'][0]) ? $meta['website'][0] : '',
        'specialty' => isset($meta['speciality'][0]) ? $meta['speciality'][0] : '',
    );
}

/**
 * 🆕 Phase 3: 통합 장소 상세 조회 API
 */
function sungsuya_get_unified_place($request) {
    $type = $request->get_param('type');
    $place_id = $request->get_param('id');
    
    if ($type === 'popup_store') {
        $post = get_post($place_id);
        if (!$post || $post->post_type !== 'popup_store') {
            return new WP_Error('place_not_found', '장소를 찾을 수 없습니다.', array('status' => 404));
        }
        
        $place_data = sungsuya_format_store_data($post);
        $place_data['place_type'] = 'popup_store';
        $place_data['type_label'] = '팝업스토어';
        $place_data['source'] = 'popup_store';
        
        return array('place' => $place_data);
        
    } elseif ($type === 'places') {
        $post = get_post($place_id);
        if (!$post || $post->post_type !== 'places') {
            return new WP_Error('place_not_found', '장소를 찾을 수 없습니다.', array('status' => 404));
        }
        
        return array('place' => sungsuya_format_places_data($post));
        
    } else {
        return new WP_Error('invalid_type', '잘못된 장소 유형입니다.', array('status' => 400));
    }
}

/**
 * 🆕 Phase 3: 통합 검색 API
 */
function sungsuya_search_all_places($request) {
    $query = $request->get_param('query');
    $type = $request->get_param('type');
    $limit = $request->get_param('limit');
    
    if (empty($query)) {
        return new WP_Error('missing_query', '검색어를 입력해주세요.', array('status' => 400));
    }
    
    $results = array();
    
    // 1. 팝업스토어 검색
    if ($type === 'all' || $type === 'popup_store') {
        $popup_args = array(
            'post_type' => 'popup_store',
            'post_status' => 'publish',
            's' => $query,
            'posts_per_page' => $limit,
        );
        
        $popup_query = new WP_Query($popup_args);
        foreach ($popup_query->posts as $post) {
            $place_data = sungsuya_format_store_data($post);
            $place_data['place_type'] = 'popup_store';
            $place_data['type_label'] = '팝업스토어';
            $place_data['source'] = 'popup_store';
            $results[] = $place_data;
        }
        wp_reset_postdata();
    }
    
    // 2. Places 검색
    if ($type === 'all' || in_array($type, array('restaurant', 'retail_store', 'facility'))) {
        $places_args = array(
            'post_type' => 'places',
            'post_status' => 'publish',
            's' => $query,
            'posts_per_page' => $limit,
        );
        
        if ($type !== 'all') {
            $places_args['tax_query'] = array(
                array(
                    'taxonomy' => 'place_type',
                    'field' => 'slug',
                    'terms' => $type,
                )
            );
        }
        
        $places_query = new WP_Query($places_args);
        foreach ($places_query->posts as $post) {
            $results[] = sungsuya_format_places_data($post);
        }
        wp_reset_postdata();
    }
    
    // 결과 수 제한
    if (count($results) > $limit) {
        $results = array_slice($results, 0, $limit);
    }
    
    return array(
        'results' => $results,
        'total' => count($results),
        'query' => $query,
        'search_types' => $type === 'all' ? array('popup_store', 'places') : array($type)
    );
}

/**
 * CORS 헤더 추가
 */
function sungsuya_add_cors_headers() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
}
add_action('rest_api_init', 'sungsuya_add_cors_headers');

/**
 * API 응답에 메타데이터 추가
 */
function sungsuya_add_api_metadata($response, $post, $request) {
    if ($post->post_type === 'popup_store') {
        $data = $response->get_data();
        
        // 추가 메타데이터
        $data['sungsuya_meta'] = array(
            'api_version' => '2.0.0',
            'generated_at' => current_time('mysql'),
        );
        
        $response->set_data($data);
    }
    
    return $response;
}
add_filter('rest_prepare_popup_store', 'sungsuya_add_api_metadata', 10, 3);
