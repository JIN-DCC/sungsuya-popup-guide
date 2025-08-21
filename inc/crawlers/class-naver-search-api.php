<?php
/**
 * 네이버 검색 API 클라이언트
 * 
 * 네이버 검색 API를 통해 장소 정보를 수집하는 클래스
 * 
 * @package SungsuyaV2
 * @subpackage Crawling
 * @version 1.0.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

class NaverSearchAPIClient {
    
    /**
     * 네이버 API 설정
     */
    private $client_id;
    private $client_secret;
    private $api_base_url = 'https://openapi.naver.com/v1/search/';
    private $geocode_api_url = 'https://naveropenapi.apigw.ntruss.com/map-geocode/v2/geocode';
    
    /**
     * 생성자
     */
    public function __construct() {
        $this->client_id = get_option('sungsuya_naver_client_id', '');
        $this->client_secret = get_option('sungsuya_naver_client_secret', '');
    }
    
    /**
     * API 키 설정 확인
     */
    public function is_configured() {
        return !empty($this->client_id) && !empty($this->client_secret);
    }
    
    /**
     * 지역 검색 (장소 검색)
     * 
     * @param string $query 검색어
     * @param array $options 추가 옵션
     * @return array|WP_Error
     */
    public function search_local($query, $options = array()) {
        if (!$this->is_configured()) {
            return new WP_Error('api_not_configured', '네이버 API 키가 설정되지 않았습니다.');
        }
        
        // 기본 옵션
        $defaults = array(
            'display' => 50,        // 최대 50개
            'start' => 1,           // 시작 위치
            'sort' => 'random'      // 정렬 (random, comment)
        );
        
        $params = wp_parse_args($options, $defaults);
        $params['query'] = $query;
        
        // API 호출
        $response = $this->call_api('local.json', $params);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // 결과 파싱 및 변환
        return $this->parse_local_results($response);
    }
    
    /**
     * 이미지 검색
     * 
     * @param string $query 검색어
     * @param array $options 추가 옵션
     * @return array|WP_Error
     */
    public function search_images($query, $options = array()) {
        if (!$this->is_configured()) {
            return new WP_Error('api_not_configured', '네이버 API 키가 설정되지 않았습니다.');
        }
        
        $defaults = array(
            'display' => 10,
            'start' => 1,
            'sort' => 'sim',        // sim (유사도), date (날짜)
            'filter' => 'all'       // all, large, medium, small
        );
        
        $params = wp_parse_args($options, $defaults);
        $params['query'] = $query;
        
        return $this->call_api('image', $params);
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
                'X-Naver-Client-Id' => $this->client_id,
                'X-Naver-Client-Secret' => $this->client_secret,
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
                sprintf('네이버 API 오류: HTTP %d', $response_code),
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
     * 지역 검색 결과 파싱
     * 
     * @param array $response API 응답
     * @return array
     */
    private function parse_local_results($response) {
        $results = array(
            'total' => isset($response['total']) ? $response['total'] : 0,
            'start' => isset($response['start']) ? $response['start'] : 1,
            'display' => isset($response['display']) ? $response['display'] : 0,
            'items' => array()
        );
        
        if (!isset($response['items']) || !is_array($response['items'])) {
            return $results;
        }
        
        foreach ($response['items'] as $item) {
            $parsed_item = $this->parse_local_item($item);
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
     * @return array|null
     */
    private function parse_local_item($item) {
        // 기본 정보 추출
        $parsed = array(
            'name' => $this->clean_html($item['title']),
            'address' => isset($item['address']) ? $item['address'] : '',
            'road_address' => isset($item['roadAddress']) ? $item['roadAddress'] : '',
            'phone' => isset($item['telephone']) ? $item['telephone'] : '',
            'category' => isset($item['category']) ? $item['category'] : '',
            'link' => isset($item['link']) ? $item['link'] : '',
            'description' => isset($item['description']) ? $this->clean_html($item['description']) : '',
            
            // 좌표 정보 (카텍 좌표계)
            'mapx' => isset($item['mapx']) ? $item['mapx'] : '',
            'mapy' => isset($item['mapy']) ? $item['mapy'] : '',
            
            // 원본 데이터 보존
            'raw_data' => $item
        );
        
        // 좌표 변환 (카텍 → WGS84)
        if (!empty($parsed['mapx']) && !empty($parsed['mapy'])) {
            $coords = $this->convert_katec_to_wgs84($parsed['mapx'], $parsed['mapy']);
            $parsed['longitude'] = $coords['lng'];
            $parsed['latitude'] = $coords['lat'];
        }
        
        // 장소 유형 추론
        $parsed['place_type'] = $this->infer_place_type($parsed);
        
        // 카테고리 정리
        $parsed['clean_category'] = $this->clean_category($parsed['category']);
        
        return $parsed;
    }
    
    /**
     * HTML 태그 제거 및 정리
     * 
     * @param string $text
     * @return string
     */
    private function clean_html($text) {
        // <b> 태그 등 제거
        $text = strip_tags($text);
        // HTML 엔티티 디코드
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // 공백 정리
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * 카텍 좌표계를 WGS84로 변환
     * 
     * @param float $x 카텍 X 좌표
     * @param float $y 카텍 Y 좌표
     * @return array
     */
    private function convert_katec_to_wgs84($x, $y) {
        // 네이버 API는 이미 WGS84 좌표를 반환하는 것으로 보임
        // 하지만 10진수가 아닌 정수로 제공되므로 변환 필요
        
        $lng = floatval($x) / 10000000;
        $lat = floatval($y) / 10000000;
        
        return array(
            'lng' => $lng,
            'lat' => $lat
        );
    }
    
    /**
     * 장소 유형 추론
     * 
     * @param array $item 파싱된 아이템
     * @return string
     */
    private function infer_place_type($item) {
        $name = $item['name'];
        $category = $item['category'];
        $description = $item['description'];
        
        // 팝업스토어 키워드 체크
        $popup_keywords = array('팝업', '기간한정', '이벤트', '한정', '~까지', 'POP-UP', 'POPUP');
        foreach ($popup_keywords as $keyword) {
            if (stripos($name, $keyword) !== false || stripos($description, $keyword) !== false) {
                return 'popup_store';
            }
        }
        
        // 카테고리 기반 분류
        if (strpos($category, '음식점') !== false || strpos($category, '카페') !== false) {
            return 'restaurant';
        }
        
        if (strpos($category, '쇼핑') !== false || strpos($category, '판매') !== false) {
            return 'retail_store';
        }
        
        if (strpos($category, '편의시설') !== false || strpos($category, '주차장') !== false) {
            return 'facility';
        }
        
        // 기본값
        return 'retail_store';
    }
    
    /**
     * 카테고리 정리
     * 
     * @param string $category
     * @return string
     */
    private function clean_category($category) {
        // "음식점>카페" → "카페"
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
            'website' => $item['link'],
            'operating_status' => 'open',
            
            // 장소 유형별 추가 필드
            '_place_source' => 'naver_api',
            '_place_source_id' => md5($item['name'] . $item['address']),
            '_place_source_data' => json_encode($item['raw_data'])
        );
        
        // 장소 유형별 메타필드 추가
        switch ($item['place_type']) {
            case 'popup_store':
                $meta['store_name'] = $item['name'];
                $meta['store_description'] = $item['description'];
                $meta['operation_status'] = 'upcoming';
                break;
                
            case 'restaurant':
                $meta['speciality'] = $item['clean_category'];
                $meta['cuisine_type'] = $this->map_cuisine_type($item['category']);
                break;
                
            case 'retail_store':
                $meta['store_type'] = $this->map_store_type($item['category']);
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
            '바' => 'bar',
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
            '패션' => 'fashion',
            '의류' => 'fashion',
            '화장품' => 'cosmetics',
            '뷰티' => 'cosmetics',
            '가구' => 'home_living',
            '인테리어' => 'home_living',
            '서점' => 'bookstore',
            '전자' => 'electronics'
        );
        
        foreach ($mappings as $keyword => $type) {
            if (strpos($category, $keyword) !== false) {
                return $type;
            }
        }
        
        return 'fashion';
    }
}
