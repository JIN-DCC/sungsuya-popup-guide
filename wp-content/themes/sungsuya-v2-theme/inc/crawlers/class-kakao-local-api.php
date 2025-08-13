<?php
/**
 * 카카오 로컬 API 클라이언트
 * 
 * 카카오 로컬 API를 통해 장소 정보를 수집하는 클래스
 * 
 * @package SungsuyaV2
 * @subpackage Crawling
 * @version 1.0.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

class KakaoLocalAPIClient {
    
    /**
     * 카카오 API 설정
     */
    private $rest_api_key;
    private $api_base_url = 'https://dapi.kakao.com/v2/local/';
    
    /**
     * 생성자
     */
    public function __construct() {
        $this->rest_api_key = get_option('sungsuya_kakao_rest_api_key', '');
    }
    
    /**
     * API 키 설정 확인
     */
    public function is_configured() {
        return !empty($this->rest_api_key);
    }
    
    /**
     * 키워드로 장소 검색
     * 
     * @param string $query 검색어
     * @param array $options 추가 옵션
     * @return array|WP_Error
     */
    public function search_keyword($query, $options = array()) {
        if (!$this->is_configured()) {
            return new WP_Error('api_not_configured', '카카오 API 키가 설정되지 않았습니다.');
        }
        
        // 기본 옵션
        $defaults = array(
            'page' => 1,
            'size' => 15,           // 최대 45
            'sort' => 'accuracy',   // accuracy(정확도순) 또는 distance(거리순)
            'x' => '',              // 중심 좌표 x (longitude)
            'y' => '',              // 중심 좌표 y (latitude)
            'radius' => 20000       // 반경(미터), 최대 20000
        );
        
        $params = wp_parse_args($options, $defaults);
        $params['query'] = $query;
        
        // 빈 값 제거
        $params = array_filter($params);
        
        // API 호출
        $response = $this->call_api('search/keyword.json', $params);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // 결과 파싱
        return $this->parse_search_results($response);
    }
    
    /**
     * 카테고리로 장소 검색
     * 
     * @param string $category_code 카테고리 코드
     * @param float $x 중심 좌표 x
     * @param float $y 중심 좌표 y
     * @param array $options 추가 옵션
     * @return array|WP_Error
     */
    public function search_category($category_code, $x, $y, $options = array()) {
        if (!$this->is_configured()) {
            return new WP_Error('api_not_configured', '카카오 API 키가 설정되지 않았습니다.');
        }
        
        $defaults = array(
            'page' => 1,
            'size' => 15,
            'sort' => 'distance',
            'radius' => 20000
        );
        
        $params = wp_parse_args($options, $defaults);
        $params['category_group_code'] = $category_code;
        $params['x'] = $x;
        $params['y'] = $y;
        
        return $this->call_api('search/category.json', $params);
    }
    
    /**
     * API 호출 실행
     * 
     * @param string $endpoint 엔드포인트
     * @param array $params 파라미터
     * @return array|WP_Error
     */
    private function call_api($endpoint, $params) {
        $url = $this->api_base_url . $endpoint;
        
        // URL 파라미터 추가
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        // API 호출
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'KakaoAK ' . $this->rest_api_key,
                'Content-Type' => 'application/json; charset=utf-8'
            ),
            'timeout' => 30
        ));
        
        // 에러 체크
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            return new WP_Error(
                'api_error',
                sprintf('카카오 API 오류: HTTP %d', $response_code),
                array('body' => $response_body)
            );
        }
        
        // JSON 파싱
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'JSON 파싱 오류');
        }
        
        return $data;
    }
    
    /**
     * 검색 결과 파싱
     * 
     * @param array $response API 응답
     * @return array
     */
    private function parse_search_results($response) {
        $results = array(
            'total_count' => isset($response['meta']['total_count']) ? $response['meta']['total_count'] : 0,
            'pageable_count' => isset($response['meta']['pageable_count']) ? $response['meta']['pageable_count'] : 0,
            'is_end' => isset($response['meta']['is_end']) ? $response['meta']['is_end'] : true,
            'items' => array()
        );
        
        if (!isset($response['documents']) || !is_array($response['documents'])) {
            return $results;
        }
        
        foreach ($response['documents'] as $doc) {
            $parsed_item = $this->parse_place_item($doc);
            if ($parsed_item) {
                $results['items'][] = $parsed_item;
            }
        }
        
        return $results;
    }
    
    /**
     * 개별 장소 정보 파싱
     * 
     * @param array $item API 아이템
     * @return array
     */
    private function parse_place_item($item) {
        // 기본 정보 추출
        $parsed = array(
            'id' => isset($item['id']) ? $item['id'] : '',
            'name' => isset($item['place_name']) ? $item['place_name'] : '',
            'category_name' => isset($item['category_name']) ? $item['category_name'] : '',
            'category_group_code' => isset($item['category_group_code']) ? $item['category_group_code'] : '',
            'category_group_name' => isset($item['category_group_name']) ? $item['category_group_name'] : '',
            'phone' => isset($item['phone']) ? $item['phone'] : '',
            'address' => isset($item['address_name']) ? $item['address_name'] : '',
            'road_address' => isset($item['road_address_name']) ? $item['road_address_name'] : '',
            'longitude' => isset($item['x']) ? floatval($item['x']) : 0,
            'latitude' => isset($item['y']) ? floatval($item['y']) : 0,
            'place_url' => isset($item['place_url']) ? $item['place_url'] : '',
            'distance' => isset($item['distance']) ? $item['distance'] : '',
            
            // 원본 데이터 보존
            'raw_data' => $item
        );
        
        // 장소 유형 추론
        $parsed['place_type'] = $this->infer_place_type($parsed);
        
        // 카테고리 정리
        $parsed['clean_category'] = $this->clean_category($parsed['category_name']);
        
        return $parsed;
    }
    
    /**
     * 장소 유형 추론
     * 
     * @param array $item 파싱된 아이템
     * @return string
     */
    private function infer_place_type($item) {
        $name = $item['name'];
        $category = $item['category_name'];
        $group_code = $item['category_group_code'];
        
        // 팝업스토어 키워드 체크
        $popup_keywords = array('팝업', '기간한정', '이벤트', '한정', '~까지', 'POP-UP', 'POPUP');
        foreach ($popup_keywords as $keyword) {
            if (stripos($name, $keyword) !== false) {
                return 'popup_store';
            }
        }
        
        // 카테고리 그룹 코드 기반 분류
        switch ($group_code) {
            case 'FD6':  // 음식점
            case 'CE7':  // 카페
                return 'restaurant';
            case 'CS2':  // 편의점
            case 'MT1':  // 대형마트
            case 'SW8':  // 쇼핑
                return 'retail_store';
            case 'PK6':  // 주차장
            case 'BK9':  // 은행
            case 'PO3':  // 공공기관
                return 'facility';
        }
        
        // 카테고리명 기반 분류
        if (strpos($category, '음식점') !== false || strpos($category, '카페') !== false) {
            return 'restaurant';
        }
        
        return 'retail_store';
    }
    
    /**
     * 카테고리 정리
     * 
     * @param string $category
     * @return string
     */
    private function clean_category($category) {
        // "음식점 > 카페 > 테마카페" → "테마카페"
        $parts = explode('>', $category);
        return trim(end($parts));
    }
    
    /**
     * WordPress 메타필드로 변환
     * 
     * @param array $item 파싱된 아이템
     * @return array
     */
    public function convert_to_wordpress_meta($item) {
        $meta = array(
            // 공통 필드
            'address' => !empty($item['road_address']) ? $item['road_address'] : $item['address'],
            'latitude' => $item['latitude'],
            'longitude' => $item['longitude'],
            'phone' => $item['phone'],
            'website' => $item['place_url'],
            'operating_status' => 'open',
            
            // 추가 정보
            '_place_source' => 'kakao_api',
            '_place_source_id' => $item['id'],
            '_place_source_data' => json_encode($item['raw_data'])
        );
        
        // 장소 유형별 메타필드 추가
        switch ($item['place_type']) {
            case 'popup_store':
                $meta['store_name'] = $item['name'];
                $meta['operation_status'] = 'upcoming';
                break;
                
            case 'restaurant':
                $meta['speciality'] = $item['clean_category'];
                $meta['cuisine_type'] = $this->map_cuisine_type($item['category_name']);
                break;
                
            case 'retail_store':
                $meta['store_type'] = $this->map_store_type($item['category_name']);
                break;
        }
        
        return $meta;
    }
    
    /**
     * 음식 종류 매핑
     * 
     * @param string $category
     * @return string
     */
    private function map_cuisine_type($category) {
        $mappings = array(
            '한식' => 'korean',
            '일식' => 'japanese',
            '중식' => 'chinese',
            '양식' => 'western',
            '카페' => 'cafe',
            '베이커리' => 'bakery',
            '술집' => 'bar'
        );
        
        foreach ($mappings as $keyword => $type) {
            if (strpos($category, $keyword) !== false) {
                return $type;
            }
        }
        
        return 'fusion';
    }
    
    /**
     * 매장 유형 매핑
     * 
     * @param string $category
     * @return string
     */
    private function map_store_type($category) {
        $mappings = array(
            '의류' => 'fashion',
            '패션' => 'fashion',
            '화장품' => 'cosmetics',
            '뷰티' => 'cosmetics',
            '가구' => 'home_living',
            '서점' => 'bookstore',
            '편의점' => 'convenience'
        );
        
        foreach ($mappings as $keyword => $type) {
            if (strpos($category, $keyword) !== false) {
                return $type;
            }
        }
        
        return 'retail';
    }
}

/**
 * 카카오 카테고리 그룹 코드
 * 
 * MT1 대형마트
 * CS2 편의점
 * PK6 주차장
 * OL7 주유소, 충전소
 * BK9 은행
 * CT1 문화시설
 * FD6 음식점
 * CE7 카페
 * HP8 병원
 * PM9 약국
 * PO3 공공기관
 * SW8 쇼핑
 * SC4 학교
 * AC5 학원
 * PS3 어린이집, 유치원
 * AT4 관광명소
 * AD5 숙박
 * AG2 부동산중개업
 * PK5 주차장
 */
