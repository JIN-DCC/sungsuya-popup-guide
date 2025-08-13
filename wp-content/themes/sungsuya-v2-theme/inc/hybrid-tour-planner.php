<?php
/**
 * 성수야! V2 - 혁신적 혼합 시스템 통합 투어 플래너
 * 
 * 팝업스토어(기존) + Places(신규) 데이터를 통합하여
 * 4가지 장소 유형 자유 조합 투어 계획 시스템
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 혁신적 혼합 투어 플래너 클래스
 */
class SungsuyaHybridTourPlanner {
    
    /**
     * 지원되는 장소 유형 정의
     */
    private const PLACE_TYPES = [
        'popup_store' => [
            'label' => '🏪 팝업스토어',
            'source' => 'popup_store',  // Custom Post Type
            'color' => '#e11d48',        // 빨간색 계열
            'icon' => 'store'
        ],
        'restaurant' => [
            'label' => '🍽️ 맛집',
            'source' => 'places',       // Places 시스템
            'color' => '#f59e0b',        // 주황색 계열
            'icon' => 'restaurant'
        ],
        'retail_store' => [
            'label' => '🏬 상설매장',
            'source' => 'places',       // Places 시스템
            'color' => '#3b82f6',        // 파란색 계열
            'icon' => 'shopping_bag'
        ],
        'facility' => [
            'label' => '🚻 편의시설',
            'source' => 'places',       // Places 시스템
            'color' => '#10b981',        // 녹색 계열
            'icon' => 'location_on'
        ]
    ];
    
    /**
     * 싱글톤 인스턴스
     */
    private static $instance = null;
    
    /**
     * 싱글톤 패턴
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 생성자
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * WordPress 훅 초기화
     */
    private function init_hooks() {
        // REST API 엔드포인트 등록
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // 관리자 메뉴 추가
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // 투어 플래너 페이지 템플릿 필터
        add_filter('template_include', [$this, 'tour_planner_template']);
        
        // 쇼트코드 등록
        add_shortcode('hybrid_tour_planner', [$this, 'tour_planner_shortcode']);
    }
    
    /**
     * REST API 경로 등록
     */
    public function register_rest_routes() {
        $namespace = 'sungsuya/v2';
        
        // 통합 장소 목록 조회
        register_rest_route($namespace, '/hybrid-places', [
            'methods' => 'GET',
            'callback' => [$this, 'get_all_places'],
            'permission_callback' => '__return_true',
            'args' => [
                'types' => [
                    'description' => '조회할 장소 유형 (쉼표로 구분)',
                    'type' => 'string',
                    'default' => ''
                ],
                'limit' => [
                    'description' => '조회할 최대 개수',
                    'type' => 'integer',
                    'default' => 50
                ],
                'search' => [
                    'description' => '검색 키워드',
                    'type' => 'string',
                    'default' => ''
                ]
            ]
        ]);
        
        // 최적 투어 경로 계산
        register_rest_route($namespace, '/tour-optimize', [
            'methods' => 'POST',
            'callback' => [$this, 'optimize_tour_route'],
            'permission_callback' => '__return_true',
            'args' => [
                'place_ids' => [
                    'description' => '투어에 포함할 장소 ID 배열',
                    'type' => 'array',
                    'required' => true
                ],
                'start_point' => [
                    'description' => '출발점 좌표 (lat,lng)',
                    'type' => 'string',
                    'default' => '37.5444,127.0548' // 성수역 기본값
                ]
            ]
        ]);
        
        // 혼합 시스템 통계
        register_rest_route($namespace, '/hybrid-stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_hybrid_stats'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    /**
     * 모든 장소 데이터 통합 조회 (핵심 기능)
     */
    public function get_all_places($request) {
        $types = $request->get_param('types');
        $limit = $request->get_param('limit');
        $search = $request->get_param('search');
        
        $selected_types = empty($types) ? array_keys(self::PLACE_TYPES) : explode(',', $types);
        $all_places = [];
        
        try {
            // 1. 팝업스토어 데이터 조회 (기존 시스템)
            if (in_array('popup_store', $selected_types)) {
                $popup_stores = $this->get_popup_stores($search, $limit);
                $all_places = array_merge($all_places, $popup_stores);
            }
            
            // 2. Places 시스템 데이터 조회 (신규 장소들)
            $places_types = array_intersect($selected_types, ['restaurant', 'retail_store', 'facility']);
            if (!empty($places_types)) {
                $places_data = $this->get_places_data($places_types, $search, $limit);
                $all_places = array_merge($all_places, $places_data);
            }
            
            // 3. 데이터 표준화 및 정렬
            $standardized_places = $this->standardize_places_data($all_places);
            
            // 4. 제한 개수 적용
            if ($limit > 0) {
                $standardized_places = array_slice($standardized_places, 0, $limit);
            }
            
            return new WP_REST_Response([
                'success' => true,
                'data' => $standardized_places,
                'total' => count($standardized_places),
                'types_included' => $selected_types,
                'timestamp' => current_time('c')
            ], 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'error' => '데이터 조회 중 오류가 발생했습니다.',
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * 팝업스토어 데이터 조회 (기존 시스템)
     */
    private function get_popup_stores($search = '', $limit = 50) {
        $args = [
            'post_type' => 'popup_store',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => 'operating_status',
                    'value' => 'closed',
                    'compare' => '!='
                ]
            ]
        ];
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $popup_stores = get_posts($args);
        $result = [];
        
        foreach ($popup_stores as $store) {
            $meta = get_post_meta($store->ID);
            
            $result[] = [
                'id' => $store->ID,
                'source' => 'popup_store',
                'type' => 'popup_store',
                'title' => $store->post_title,
                'description' => $store->post_excerpt ?: wp_trim_words($store->post_content, 20),
                'address' => $meta['address'][0] ?? '',
                'phone' => $meta['phone'][0] ?? '',
                'operating_status' => $meta['operating_status'][0] ?? 'open',
                'latitude' => floatval($meta['latitude'][0] ?? 0),
                'longitude' => floatval($meta['longitude'][0] ?? 0),
                'featured_image' => get_the_post_thumbnail_url($store->ID, 'medium'),
                'permalink' => get_permalink($store->ID),
                // 팝업스토어 특화 데이터
                'popup_duration' => $meta['popup_duration'][0] ?? '',
                'popup_concept' => $meta['popup_concept'][0] ?? '',
                'brand_name' => $meta['brand_name'][0] ?? '',
                'nearest_station' => $meta['nearest_station'][0] ?? ''
            ];
        }
        
        return $result;
    }
    
    /**
     * Places 데이터 조회 (신규 시스템)
     */
    private function get_places_data($types, $search = '', $limit = 50) {
        $args = [
            'post_type' => 'places',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'tax_query' => [
                [
                    'taxonomy' => 'place_type',
                    'field' => 'slug',
                    'terms' => $types,
                    'operator' => 'IN'
                ]
            ],
            'meta_query' => [
                [
                    'key' => 'operating_status',
                    'value' => 'closed',
                    'compare' => '!='
                ]
            ]
        ];
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $places = get_posts($args);
        $result = [];
        
        foreach ($places as $place) {
            $meta = get_post_meta($place->ID);
            $place_types = wp_get_post_terms($place->ID, 'place_type');
            $place_type = !empty($place_types) ? $place_types[0]->slug : 'facility';
            
            $place_data = [
                'id' => $place->ID,
                'source' => 'places',
                'type' => $place_type,
                'title' => $place->post_title,
                'description' => $place->post_excerpt ?: wp_trim_words($place->post_content, 20),
                'address' => $meta['address'][0] ?? '',
                'phone' => $meta['phone'][0] ?? '',
                'operating_status' => $meta['operating_status'][0] ?? 'open',
                'latitude' => floatval($meta['latitude'][0] ?? 0),
                'longitude' => floatval($meta['longitude'][0] ?? 0),
                'featured_image' => get_the_post_thumbnail_url($place->ID, 'medium'),
                'permalink' => get_permalink($place->ID),
                'featured' => $meta['featured'][0] ?? '0'
            ];
            
            // 유형별 특화 데이터 추가
            switch ($place_type) {
                case 'restaurant':
                    $place_data['cuisine_type'] = $meta['cuisine_type'][0] ?? '';
                    $place_data['price_range'] = $meta['price_range'][0] ?? '';
                    $place_data['signature_menu'] = $meta['signature_menu'][0] ?? '';
                    break;
                    
                case 'retail_store':
                    $place_data['store_category'] = $meta['store_category'][0] ?? '';
                    $place_data['brand_info'] = $meta['brand_info'][0] ?? '';
                    $place_data['price_range'] = $meta['price_range'][0] ?? '';
                    break;
                    
                case 'facility':
                    $place_data['facility_type'] = $meta['facility_type'][0] ?? '';
                    $place_data['accessibility'] = $meta['accessibility'][0] ?? '';
                    $place_data['operating_hours'] = $meta['operating_hours'][0] ?? '';
                    break;
            }
            
            $result[] = $place_data;
        }
        
        return $result;
    }
    
    /**
     * 데이터 표준화 (핵심: 두 시스템 데이터 통합)
     */
    private function standardize_places_data($places) {
        $standardized = [];
        
        foreach ($places as $place) {
            $type_config = self::PLACE_TYPES[$place['type']] ?? self::PLACE_TYPES['facility'];
            
            $standardized[] = [
                'id' => $place['id'],
                'source' => $place['source'],
                'type' => $place['type'],
                'type_label' => $type_config['label'],
                'type_color' => $type_config['color'],
                'type_icon' => $type_config['icon'],
                'title' => $place['title'],
                'description' => $place['description'],
                'address' => $place['address'],
                'phone' => $place['phone'],
                'operating_status' => $place['operating_status'],
                'coordinates' => [
                    'lat' => $place['latitude'],
                    'lng' => $place['longitude']
                ],
                'featured_image' => $place['featured_image'],
                'permalink' => $place['permalink'],
                'featured' => $place['featured'] ?? '0',
                'raw_data' => $place // 원본 데이터 보존
            ];
        }
        
        // 인기 장소 우선 정렬
        usort($standardized, function($a, $b) {
            $a_featured = intval($a['featured']);
            $b_featured = intval($b['featured']);
            
            if ($a_featured !== $b_featured) {
                return $b_featured <=> $a_featured; // 인기 장소 먼저
            }
            
            return strcmp($a['title'], $b['title']); // 제목 알파벳순
        });
        
        return $standardized;
    }
    
    /**
     * 투어 경로 최적화 (TSP 알고리즘 적용)
     */
    public function optimize_tour_route($request) {
        $place_ids = $request->get_param('place_ids');
        $start_point = $request->get_param('start_point');
        
        if (empty($place_ids) || !is_array($place_ids)) {
            return new WP_REST_Response([
                'success' => false,
                'error' => '유효한 장소 ID가 필요합니다.'
            ], 400);
        }
        
        try {
            // 시작점 좌표 파싱
            $start_coords = explode(',', $start_point);
            $start_lat = floatval($start_coords[0]);
            $start_lng = floatval($start_coords[1]);
            
            // 선택된 장소들의 정보 조회
            $places_info = $this->get_places_by_ids($place_ids);
            
            if (empty($places_info)) {
                return new WP_REST_Response([
                    'success' => false,
                    'error' => '선택된 장소 정보를 찾을 수 없습니다.'
                ], 404);
            }
            
            // TSP 알고리즘으로 최적 경로 계산
            $optimized_route = $this->calculate_optimal_route($places_info, $start_lat, $start_lng);
            
            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'start_point' => [
                        'lat' => $start_lat,
                        'lng' => $start_lng
                    ],
                    'optimized_route' => $optimized_route,
                    'total_places' => count($optimized_route),
                    'estimated_duration' => $this->estimate_tour_duration($optimized_route),
                    'route_summary' => $this->generate_route_summary($optimized_route)
                ]
            ], 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'error' => '경로 최적화 중 오류가 발생했습니다.',
                'debug' => WP_DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * ID로 장소 정보 조회 (혼합 시스템)
     */
    private function get_places_by_ids($place_ids) {
        $places_info = [];
        
        foreach ($place_ids as $place_id) {
            $place_data = $this->get_single_place_data($place_id);
            if ($place_data) {
                $places_info[] = $place_data;
            }
        }
        
        return $places_info;
    }
    
    /**
     * 단일 장소 데이터 조회 (혼합 시스템 대응)
     */
    private function get_single_place_data($place_id) {
        // 먼저 팝업스토어인지 확인
        $post = get_post($place_id);
        if (!$post) {
            return null;
        }
        
        $meta = get_post_meta($place_id);
        
        if ($post->post_type === 'popup_store') {
            // 팝업스토어 데이터
            return [
                'id' => $place_id,
                'source' => 'popup_store',
                'type' => 'popup_store',
                'title' => $post->post_title,
                'coordinates' => [
                    'lat' => floatval($meta['latitude'][0] ?? 0),
                    'lng' => floatval($meta['longitude'][0] ?? 0)
                ],
                'address' => $meta['address'][0] ?? '',
                'type_label' => self::PLACE_TYPES['popup_store']['label'],
                'type_color' => self::PLACE_TYPES['popup_store']['color']
            ];
        } elseif ($post->post_type === 'places') {
            // Places 데이터
            $place_types = wp_get_post_terms($place_id, 'place_type');
            $place_type = !empty($place_types) ? $place_types[0]->slug : 'facility';
            
            return [
                'id' => $place_id,
                'source' => 'places',
                'type' => $place_type,
                'title' => $post->post_title,
                'coordinates' => [
                    'lat' => floatval($meta['latitude'][0] ?? 0),
                    'lng' => floatval($meta['longitude'][0] ?? 0)
                ],
                'address' => $meta['address'][0] ?? '',
                'type_label' => self::PLACE_TYPES[$place_type]['label'] ?? '장소',
                'type_color' => self::PLACE_TYPES[$place_type]['color'] ?? '#6b7280'
            ];
        }
        
        return null;
    }
    
    /**
     * TSP 기반 최적 경로 계산
     */
    private function calculate_optimal_route($places, $start_lat, $start_lng) {
        if (count($places) <= 1) {
            return $places;
        }
        
        // 거리 매트릭스 계산
        $distances = $this->calculate_distance_matrix($places, $start_lat, $start_lng);
        
        // 가장 가까운 이웃 알고리즘 (Nearest Neighbor)
        $optimized_route = $this->nearest_neighbor_tsp($places, $distances);
        
        return $optimized_route;
    }
    
    /**
     * 거리 매트릭스 계산
     */
    private function calculate_distance_matrix($places, $start_lat, $start_lng) {
        $distances = [];
        $all_points = [['lat' => $start_lat, 'lng' => $start_lng]]; // 시작점 추가
        
        foreach ($places as $place) {
            $all_points[] = $place['coordinates'];
        }
        
        for ($i = 0; $i < count($all_points); $i++) {
            for ($j = 0; $j < count($all_points); $j++) {
                if ($i === $j) {
                    $distances[$i][$j] = 0;
                } else {
                    $distances[$i][$j] = $this->haversine_distance(
                        $all_points[$i]['lat'], $all_points[$i]['lng'],
                        $all_points[$j]['lat'], $all_points[$j]['lng']
                    );
                }
            }
        }
        
        return $distances;
    }
    
    /**
     * Haversine 공식으로 두 지점 간 거리 계산 (km)
     */
    private function haversine_distance($lat1, $lng1, $lat2, $lng2) {
        $earth_radius = 6371; // km
        
        $dlat = deg2rad($lat2 - $lat1);
        $dlng = deg2rad($lng2 - $lng1);
        
        $a = sin($dlat/2) * sin($dlat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dlng/2) * sin($dlng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earth_radius * $c;
    }
    
    /**
     * 가장 가까운 이웃 TSP 알고리즘
     */
    private function nearest_neighbor_tsp($places, $distances) {
        $n = count($places);
        $visited = array_fill(0, $n + 1, false); // +1은 시작점
        $route = [];
        $current = 0; // 시작점에서 시작
        $visited[0] = true;
        
        for ($i = 0; $i < $n; $i++) {
            $nearest = -1;
            $min_distance = PHP_FLOAT_MAX;
            
            // 가장 가까운 미방문 지점 찾기
            for ($j = 1; $j <= $n; $j++) { // 1부터 시작 (0은 시작점)
                if (!$visited[$j] && $distances[$current][$j] < $min_distance) {
                    $min_distance = $distances[$current][$j];
                    $nearest = $j;
                }
            }
            
            if ($nearest !== -1) {
                $visited[$nearest] = true;
                $route[] = [
                    'place' => $places[$nearest - 1], // 인덱스 조정
                    'distance_from_previous' => $min_distance,
                    'order' => count($route) + 1
                ];
                $current = $nearest;
            }
        }
        
        return $route;
    }
    
    /**
     * 투어 소요 시간 추정
     */
    private function estimate_tour_duration($route) {
        $total_travel_time = 0;
        $total_visit_time = count($route) * 45; // 장소당 평균 45분 방문
        
        foreach ($route as $stop) {
            // 이동 시간 (km당 5분으로 가정)
            $total_travel_time += $stop['distance_from_previous'] * 5;
        }
        
        return [
            'total_minutes' => ceil($total_travel_time + $total_visit_time),
            'travel_time' => ceil($total_travel_time),
            'visit_time' => $total_visit_time,
            'formatted' => $this->format_duration(ceil($total_travel_time + $total_visit_time))
        ];
    }
    
    /**
     * 시간 포맷팅
     */
    private function format_duration($minutes) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0) {
            return sprintf('%d시간 %d분', $hours, $mins);
        } else {
            return sprintf('%d분', $mins);
        }
    }
    
    /**
     * 경로 요약 생성
     */
    private function generate_route_summary($route) {
        $type_counts = [];
        $highlights = [];
        
        foreach ($route as $stop) {
            $type = $stop['place']['type'];
            $type_counts[$type] = ($type_counts[$type] ?? 0) + 1;
            
            if ($stop['place']['featured'] ?? false) {
                $highlights[] = $stop['place']['title'];
            }
        }
        
        $summary_parts = [];
        foreach ($type_counts as $type => $count) {
            $label = self::PLACE_TYPES[$type]['label'] ?? $type;
            $summary_parts[] = "{$label} {$count}곳";
        }
        
        return [
            'type_summary' => implode(', ', $summary_parts),
            'highlights' => array_slice($highlights, 0, 3), // 최대 3개
            'route_length' => count($route) . '곳 코스'
        ];
    }
    
    /**
     * 혼합 시스템 통계 조회
     */
    public function get_hybrid_stats($request) {
        try {
            $stats = [
                'popup_stores' => [
                    'count' => wp_count_posts('popup_store')->publish,
                    'label' => '팝업스토어',
                    'color' => self::PLACE_TYPES['popup_store']['color']
                ],
                'restaurants' => [
                    'count' => $this->count_places_by_type('restaurant'),
                    'label' => '맛집',
                    'color' => self::PLACE_TYPES['restaurant']['color']
                ],
                'retail_stores' => [
                    'count' => $this->count_places_by_type('retail_store'),
                    'label' => '상설매장',
                    'color' => self::PLACE_TYPES['retail_store']['color']
                ],
                'facilities' => [
                    'count' => $this->count_places_by_type('facility'),
                    'label' => '편의시설',
                    'color' => self::PLACE_TYPES['facility']['color']
                ]
            ];
            
            $total_count = array_sum(array_column($stats, 'count'));
            
            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'total_places' => $total_count,
                    'by_type' => $stats,
                    'last_updated' => current_time('c')
                ]
            ], 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'error' => '통계 조회 중 오류가 발생했습니다.'
            ], 500);
        }
    }
    
    /**
     * Places 유형별 개수 조회
     */
    private function count_places_by_type($type) {
        $term = get_term_by('slug', $type, 'place_type');
        return $term ? $term->count : 0;
    }
    
    /**
     * 관리자 메뉴 추가
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=popup_store',
            '혼합 투어 플래너',
            '🗺️ 투어 플래너',
            'manage_options',
            'hybrid-tour-planner',
            [$this, 'admin_page']
        );
    }
    
    /**
     * 관리자 페이지
     */
    public function admin_page() {
        include SUNGSUYA_THEME_DIR . '/template-parts/admin/hybrid-tour-planner.php';
    }
    
    /**
     * 투어 플래너 쇼트코드
     */
    public function tour_planner_shortcode($atts) {
        $atts = shortcode_atts([
            'types' => 'popup_store,restaurant,retail_store,facility',
            'limit' => 20,
            'height' => '600px'
        ], $atts);
        
        wp_enqueue_script('sungsuya-hybrid-tour-planner');
        wp_enqueue_style('sungsuya-hybrid-tour-planner');
        
        ob_start();
        include SUNGSUYA_THEME_DIR . '/template-parts/tour-planner/hybrid-shortcode.php';
        return ob_get_clean();
    }
    
    /**
     * 투어 플래너 템플릿 필터
     */
    public function tour_planner_template($template) {
        if (is_page('tour-planner')) {
            $new_template = locate_template(['page-tour-planner.php']);
            if ($new_template) {
                return $new_template;
            }
        }
        return $template;
    }
}

// 혼합 투어 플래너 초기화
function sungsuya_init_hybrid_tour_planner() {
    return SungsuyaHybridTourPlanner::get_instance();
}

// WordPress 로드 후 초기화
add_action('plugins_loaded', 'sungsuya_init_hybrid_tour_planner');
