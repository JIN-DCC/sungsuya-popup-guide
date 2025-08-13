<?php
/**
 * 크롤링 메타필드 자동 채우기 시스템
 * 
 * 카카오/네이버 API 데이터를 분석하여 메타필드를 자동으로 채웁니다
 * 
 * @package SungsuyaV2
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crawling_Meta_Field_Enhancer {
    
    /**
     * 카테고리별 메타필드 매핑 테이블
     */
    private static $category_mappings = array(
        // 음식점 카테고리
        '한식' => array(
            'metafield_type' => 'food',
            'cuisine_type' => 'korean',
            'price_range' => 'medium',
            'reservation' => true,
            'delivery' => true,
            'takeout' => true
        ),
        '일식' => array(
            'metafield_type' => 'food',
            'cuisine_type' => 'japanese',
            'price_range' => 'high',
            'reservation' => true,
            'delivery' => false,
            'takeout' => true
        ),
        '중식' => array(
            'metafield_type' => 'food',
            'cuisine_type' => 'chinese',
            'price_range' => 'medium',
            'reservation' => true,
            'delivery' => true,
            'takeout' => true
        ),
        '양식' => array(
            'metafield_type' => 'food',
            'cuisine_type' => 'western',
            'price_range' => 'high',
            'reservation' => true,
            'delivery' => false,
            'takeout' => false
        ),
        '카페' => array(
            'metafield_type' => 'food',
            'cuisine_type' => 'cafe',
            'price_range' => 'low',
            'reservation' => false,
            'delivery' => false,
            'takeout' => true
        ),
        '베이커리' => array(
            'metafield_type' => 'food',
            'cuisine_type' => 'bakery',
            'price_range' => 'low',
            'reservation' => false,
            'delivery' => false,
            'takeout' => true
        ),
        '주점' => array(
            'metafield_type' => 'food',
            'cuisine_type' => 'bar',
            'price_range' => 'medium',
            'reservation' => true,
            'delivery' => false,
            'takeout' => false
        ),
        
        // 매장 카테고리
        '의류' => array(
            'metafield_type' => 'shop',
            'product_category' => '의류',
            'price_level' => '3-5',
            'parking' => false,
            'payment_methods' => array('cash', 'card', 'easy_pay')
        ),
        '화장품' => array(
            'metafield_type' => 'shop',
            'product_category' => '화장품',
            'price_level' => '1-3',
            'parking' => false,
            'payment_methods' => array('cash', 'card', 'easy_pay')
        ),
        '액세서리' => array(
            'metafield_type' => 'shop',
            'product_category' => '액세서리',
            'price_level' => '1-3',
            'parking' => false,
            'payment_methods' => array('cash', 'card')
        ),
        '편의점' => array(
            'metafield_type' => 'shop',
            'product_category' => '생활용품',
            'price_level' => '1-3',
            'parking' => false,
            'payment_methods' => array('cash', 'card', 'easy_pay')
        )
    );
    
    /**
     * 장소유형별 기본값
     */
    private static $type_defaults = array(
        'food' => array(
            'opening_hours' => "평일: 11:00-22:00\n주말: 11:00-23:00",
            'break_time' => '',
            'last_order' => '21:30',
            'operating_status' => 'open'
        ),
        'shop' => array(
            'opening_hours' => "평일: 11:00-21:00\n주말: 11:00-22:00",
            'operating_status' => 'open',
            'special_services' => '',
            'online_shop' => ''
        ),
        'popup-store' => array(
            'operation_status' => 'open',
            'reservation_required' => 'no',
            'entry_fee' => '무료',
            'parking_info' => 'unavailable',
            'photography_policy' => 'free',
            'recommended_visit_duration' => '30-60',
            'best_visit_time' => 'afternoon'
        )
    );
    
    /**
     * 성수동 지역 지하철역 데이터
     */
    private static $subway_stations = array(
        array(
            'name' => '성수역',
            'line' => '2호선',
            'lat' => 37.544581,
            'lng' => 127.055961,
            'exit_info' => '1번 출구'
        ),
        array(
            'name' => '뚝섬역',
            'line' => '2호선',
            'lat' => 37.547184,
            'lng' => 127.047367,
            'exit_info' => '3번 출구'
        ),
        array(
            'name' => '서울숲역',
            'line' => '분당선',
            'lat' => 37.543617,
            'lng' => 127.044707,
            'exit_info' => '1번 출구'
        ),
        array(
            'name' => '한양대역',
            'line' => '2호선',
            'lat' => 37.555273,
            'lng' => 127.043593,
            'exit_info' => '2번 출구'
        ),
        array(
            'name' => '왕십리역',
            'line' => '2호선/5호선/경의중앙선/분당선',
            'lat' => 37.561533,
            'lng' => 127.037732,
            'exit_info' => '7번 출구'
        )
    );
    
    /**
     * 카카오 카테고리 파싱 및 메타필드 생성
     */
    public static function parse_kakao_category($category_name) {
        // "음식점 > 카페 > 테마카페" 형식 파싱
        $categories = array_map('trim', explode('>', $category_name));
        $meta_data = array();
        
        // 메인 카테고리
        $main_category = isset($categories[0]) ? $categories[0] : '';
        // 서브 카테고리
        $sub_category = isset($categories[1]) ? $categories[1] : '';
        // 세부 카테고리
        $detail_category = isset($categories[2]) ? $categories[2] : '';
        
        // 매핑 테이블에서 찾기 (서브 카테고리 우선, 없으면 메인 카테고리)
        $mapping_key = $sub_category ?: $main_category;
        
        foreach (self::$category_mappings as $key => $mapping) {
            if (strpos($mapping_key, $key) !== false) {
                $meta_data = array_merge($meta_data, $mapping);
                break;
            }
        }
        
        // 팝업스토어 감지
        if (self::detect_popup_store($category_name)) {
            $meta_data['metafield_type'] = 'popup-store';
            $meta_data = array_merge($meta_data, self::$type_defaults['popup-store']);
        }
        
        // 세부 카테고리 정보 추가
        if ($detail_category) {
            $meta_data['detail_category'] = $detail_category;
        }
        
        return $meta_data;
    }
    
    /**
     * 장소 이름과 설명에서 팝업스토어 감지
     */
    public static function detect_popup_store($text) {
        $popup_keywords = array(
            '팝업', 'popup', 'pop-up', 'POP-UP',
            '기간한정', '한정', '한시적',
            '이벤트', 'event',
            '오픈런', 'openrun',
            '~까지', '부터~', '기간:',
            '플래그십', 'flagship',
            '전시', 'exhibition',
            '체험', 'experience'
        );
        
        $text_lower = mb_strtolower($text);
        
        foreach ($popup_keywords as $keyword) {
            if (strpos($text_lower, mb_strtolower($keyword)) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 가장 가까운 지하철역 계산
     */
    public static function find_nearest_subway($lat, $lng) {
        $min_distance = PHP_INT_MAX;
        $nearest_station = null;
        
        foreach (self::$subway_stations as $station) {
            $distance = self::calculate_distance(
                $lat, $lng,
                $station['lat'], $station['lng']
            );
            
            if ($distance < $min_distance) {
                $min_distance = $distance;
                $nearest_station = $station;
            }
        }
        
        if ($nearest_station) {
            // 도보시간 계산 (평균 도보속도 4km/h)
            $walking_time = round(($min_distance * 1000) / 66.67); // 미터/분
            
            return array(
                'nearest_subway' => $nearest_station['name'] . ' ' . $nearest_station['exit_info'],
                'subway_distance' => $walking_time . '분',
                'subway_line' => $nearest_station['line'],
                'distance_meters' => round($min_distance * 1000)
            );
        }
        
        return null;
    }
    
    /**
     * Haversine 공식으로 두 지점 간 거리 계산 (km)
     */
    private static function calculate_distance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371; // 지구 반지름 (km)
        
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
    
    /**
     * 크롤링 데이터에 메타필드 추가
     */
    public static function enhance_crawling_data($place_data) {
        $enhanced_data = $place_data;
        
        // 1. 카테고리 파싱
        if (!empty($place_data['category_name'])) {
            $category_meta = self::parse_kakao_category($place_data['category_name']);
            $enhanced_data = array_merge($enhanced_data, $category_meta);
        }
        
        // 2. 팝업스토어 추가 감지 (이름 기반)
        if (!empty($place_data['place_name']) && self::detect_popup_store($place_data['place_name'])) {
            $enhanced_data['metafield_type'] = 'popup-store';
            $enhanced_data = array_merge($enhanced_data, self::$type_defaults['popup-store']);
            
            // 기간 정보 추출 시도
            $period_info = self::extract_period_info($place_data['place_name']);
            if ($period_info) {
                $enhanced_data = array_merge($enhanced_data, $period_info);
            }
        }
        
        // 3. 기본값 적용
        if (!empty($enhanced_data['metafield_type'])) {
            $type = $enhanced_data['metafield_type'];
            if (isset(self::$type_defaults[$type])) {
                // 기존 값이 없는 경우에만 기본값 적용
                foreach (self::$type_defaults[$type] as $key => $default_value) {
                    if (empty($enhanced_data[$key])) {
                        $enhanced_data[$key] = $default_value;
                    }
                }
            }
        }
        
        // 4. 지하철역 정보 추가
        if (!empty($place_data['y']) && !empty($place_data['x'])) {
            $subway_info = self::find_nearest_subway(
                floatval($place_data['y']), 
                floatval($place_data['x'])
            );
            
            if ($subway_info) {
                $enhanced_data = array_merge($enhanced_data, $subway_info);
            }
        }
        
        // 5. 브랜드명 추출 (팝업스토어인 경우)
        if (isset($enhanced_data['metafield_type']) && $enhanced_data['metafield_type'] === 'popup-store') {
            $brand_name = self::extract_brand_name($place_data['place_name']);
            if ($brand_name) {
                $enhanced_data['brand_name'] = $brand_name;
            }
        }
        
        return $enhanced_data;
    }
    
    /**
     * 텍스트에서 기간 정보 추출
     */
    public static function extract_period_info($text) {
        $period_info = array();
        
        // 패턴 1: 12/1~12/31, 12.1-12.31
        if (preg_match('/(\d{1,2})[\/\.](\d{1,2})\s*[-~]\s*(\d{1,2})[\/\.](\d{1,2})/', $text, $matches)) {
            $current_year = date('Y');
            $start_month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $start_day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $end_month = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            $end_day = str_pad($matches[4], 2, '0', STR_PAD_LEFT);
            
            $period_info['operation_start'] = "{$current_year}-{$start_month}-{$start_day}";
            $period_info['operation_end'] = "{$current_year}-{$end_month}-{$end_day}";
        }
        // 패턴 2: 2025.6.1~6.30
        elseif (preg_match('/(\d{4})[\/\.](\d{1,2})[\/\.](\d{1,2})\s*[-~]\s*(\d{1,2})[\/\.](\d{1,2})/', $text, $matches)) {
            $year = $matches[1];
            $start_month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $start_day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            $end_month = str_pad($matches[4], 2, '0', STR_PAD_LEFT);
            $end_day = str_pad($matches[5], 2, '0', STR_PAD_LEFT);
            
            $period_info['operation_start'] = "{$year}-{$start_month}-{$start_day}";
            $period_info['operation_end'] = "{$year}-{$end_month}-{$end_day}";
        }
        
        return $period_info;
    }
    
    /**
     * 브랜드명 추출
     */
    public static function extract_brand_name($text) {
        // 주요 브랜드 데이터베이스
        $brands = array(
            // 패션
            '나이키', 'Nike', 'NIKE',
            '아디다스', 'Adidas', 'ADIDAS',
            '뉴발란스', 'New Balance', 'NEW BALANCE',
            '컨버스', 'Converse', 'CONVERSE',
            '반스', 'Vans', 'VANS',
            '자라', 'Zara', 'ZARA',
            '유니클로', 'Uniqlo', 'UNIQLO',
            'H&M', 'COS', '무인양품', 'MUJI',
            
            // 럭셔리
            '구찌', 'Gucci', 'GUCCI',
            '샤넬', 'Chanel', 'CHANEL',
            '루이비통', 'Louis Vuitton', 'LV',
            '에르메스', 'Hermes', 'HERMES',
            '프라다', 'Prada', 'PRADA',
            
            // 화장품
            '올리브영', '이니스프리', '에뛰드',
            'MAC', '맥', 'NARS', '나스',
            '아모레퍼시픽', '설화수', '라네즈',
            
            // 테크
            '삼성', 'Samsung', 'SAMSUNG',
            '애플', 'Apple', 'APPLE',
            'LG', '소니', 'Sony', 'SONY',
            
            // 음료/식품
            '스타벅스', 'Starbucks', 'STARBUCKS',
            '블루보틀', 'Blue Bottle',
            '맥도날드', "McDonald's",
            '버거킹', 'Burger King'
        );
        
        foreach ($brands as $brand) {
            if (stripos($text, $brand) !== false) {
                return $brand;
            }
        }
        
        // 브랜드명이 없으면 첫 단어를 브랜드로 추정
        $words = explode(' ', $text);
        if (count($words) > 2 && strpos($text, '팝업') !== false) {
            return $words[0];
        }
        
        return '';
    }
    
    /**
     * WordPress 포스트 메타데이터로 변환
     */
    public static function convert_to_post_meta($enhanced_data) {
        $meta_input = array();
        
        // 직접 매핑되는 필드들
        $direct_mappings = array(
            'address' => 'address_name',
            'road_address' => 'road_address_name',
            'latitude' => 'y',
            'longitude' => 'x',
            'phone' => 'phone',
            'place_url' => 'place_url',
            'category_name' => 'category_name',
            'kakao_id' => 'id',
            'distance' => 'distance'
        );
        
        foreach ($direct_mappings as $meta_key => $data_key) {
            if (isset($enhanced_data[$data_key])) {
                $meta_input[$meta_key] = $enhanced_data[$data_key];
            }
        }
        
        // 향상된 메타필드들
        $enhanced_fields = array(
            'metafield_type', 'cuisine_type', 'price_range', 'reservation',
            'delivery', 'takeout', 'product_category', 'price_level',
            'parking', 'payment_methods', 'opening_hours', 'break_time',
            'last_order', 'operating_status', 'operation_status',
            'operation_start', 'operation_end', 'brand_name',
            'nearest_subway', 'subway_distance', 'subway_line'
        );
        
        foreach ($enhanced_fields as $field) {
            if (isset($enhanced_data[$field])) {
                $meta_input[$field] = $enhanced_data[$field];
            }
        }
        
        return $meta_input;
    }
}

/**
 * 헬퍼 함수: 크롤링 데이터 향상
 */
function enhance_crawling_data($place_data) {
    return Crawling_Meta_Field_Enhancer::enhance_crawling_data($place_data);
}

/**
 * 헬퍼 함수: 가장 가까운 지하철역 찾기
 */
function find_nearest_subway($lat, $lng) {
    return Crawling_Meta_Field_Enhancer::find_nearest_subway($lat, $lng);
}
