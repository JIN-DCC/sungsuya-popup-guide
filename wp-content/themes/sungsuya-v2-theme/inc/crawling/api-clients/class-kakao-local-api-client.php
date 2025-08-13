<?php
/**
 * 카카오 로컬 API 클라이언트
 * 
 * 실제 장소 데이터를 카카오 API에서 가져오는 클라이언트
 * 
 * @package SungsuyaV2
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kakao_Local_API_Client {
    
    public $api_key;  // public으로 변경하여 외부에서 확인 가능
    private $base_url = 'https://dapi.kakao.com/v2/local';
    
    public function __construct() {
        $this->api_key = get_option('kakao_api_key');
    }
    
    /**
     * 키워드로 장소 검색
     */
    public function search_places($query, $params = array()) {
        if (!$this->api_key) {
            return new WP_Error('no_api_key', '카카오 API 키가 설정되지 않았습니다.');
        }
        
        $default_params = array(
            'query' => $query,
            'size' => 15,
            'page' => 1
        );
        
        $params = wp_parse_args($params, $default_params);
        
        $url = $this->base_url . '/search/keyword.json?' . http_build_query($params);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'KakaoAK ' . $this->api_key
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data)) {
            return new WP_Error('invalid_response', '유효하지 않은 응답');
        }
        
        return $data;
    }
    
    /**
     * 카테고리로 장소 검색
     */
    public function search_by_category($category_code, $x, $y, $radius = 500) {
        if (!$this->api_key) {
            return new WP_Error('no_api_key', '카카오 API 키가 설정되지 않았습니다.');
        }
        
        $params = array(
            'category_group_code' => $category_code,
            'x' => $x,
            'y' => $y,
            'radius' => $radius,
            'size' => 15
        );
        
        $url = $this->base_url . '/search/category.json?' . http_build_query($params);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'KakaoAK ' . $this->api_key
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * 주소로 좌표 검색
     */
    public function search_address($address) {
        if (!$this->api_key) {
            return new WP_Error('no_api_key', '카카오 API 키가 설정되지 않았습니다.');
        }
        
        $url = $this->base_url . '/search/address.json?query=' . urlencode($address);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'KakaoAK ' . $this->api_key
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * 성수동 맛집 검색
     */
    public function search_seongsu_restaurants() {
        $queries = array(
            '성수동 맛집',
            '성수동 카페',
            '성수동 브런치',
            '성수역 맛집',
            '뚝섬역 맛집'
        );
        
        $all_results = array();
        
        foreach ($queries as $query) {
            $results = $this->search_places($query, array('size' => 15));
            
            if (!is_wp_error($results) && !empty($results['documents'])) {
                foreach ($results['documents'] as $place) {
                    // 성수동 체크
                    if ($this->is_seongsu($place)) {
                        $all_results[$place['id']] = $place;
                    }
                }
            }
        }
        
        return array_values($all_results);
    }
    
    /**
     * 성수동 카페 검색
     */
    public function search_seongsu_cafes() {
        // 성수동 중심 좌표
        $center_x = 127.0556;
        $center_y = 37.5447;
        
        // 카테고리 검색 (CE7 = 카페)
        $results = $this->search_by_category('CE7', $center_x, $center_y, 2000);
        
        if (is_wp_error($results)) {
            // 대체 방법: 키워드 검색
            return $this->search_places('성수동 카페', array('size' => 30));
        }
        
        return $results;
    }
    
    /**
     * 성수동 팝업스토어 검색
     */
    public function search_seongsu_popups() {
        $queries = array(
            '성수동 팝업스토어',
            '성수동 팝업',
            '성수 팝업스토어',
            '성수동 전시',
            '성수동 체험'
        );
        
        $all_results = array();
        
        foreach ($queries as $query) {
            $results = $this->search_places($query, array('size' => 10));
            
            if (!is_wp_error($results) && !empty($results['documents'])) {
                foreach ($results['documents'] as $place) {
                    if ($this->is_popup_store($place)) {
                        $all_results[$place['id']] = $place;
                    }
                }
            }
        }
        
        return array_values($all_results);
    }
    
    /**
     * 성수동 체크
     */
    private function is_seongsu($place) {
        $address = $place['address_name'] ?? '';
        $road_address = $place['road_address_name'] ?? '';
        
        $seongsu_keywords = array('성수동', '성수1가', '성수2가', '성동구');
        
        foreach ($seongsu_keywords as $keyword) {
            if (strpos($address, $keyword) !== false || strpos($road_address, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 팝업스토어 체크
     */
    private function is_popup_store($place) {
        $name = $place['place_name'] ?? '';
        $category = $place['category_name'] ?? '';
        
        $popup_keywords = array('팝업', 'pop-up', 'popup', '전시', '체험', '플래그십');
        
        foreach ($popup_keywords as $keyword) {
            if (stripos($name, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * API 응답을 WordPress 포스트 데이터로 변환
     */
    public function convert_to_post_data($place) {
        // 카테고리에 따른 place_type 결정
        $place_type = 'restaurant';
        $category_name = $place['category_name'] ?? '';
        
        if (strpos($category_name, '카페') !== false) {
            $place_type = 'cafe';
        } elseif ($this->is_popup_store($place)) {
            $place_type = 'popup_store';
        } elseif (strpos($category_name, '쇼핑') !== false) {
            $place_type = 'permanent_store';
        }
        
        return array(
            'post_title' => $place['place_name'],
            'post_type' => 'places',
            'post_status' => 'publish',
            'meta_input' => array(
                'place_type' => $place_type,
                'address' => $place['address_name'],
                'road_address' => $place['road_address_name'],
                'latitude' => $place['y'],
                'longitude' => $place['x'],
                'phone' => $place['phone'] ?? '',
                'place_url' => $place['place_url'],
                'category_name' => $category_name,
                'kakao_id' => $place['id'],
                'distance' => $place['distance'] ?? '',
                
                // 팝업스토어인 경우 추가 정보
                'operating_status' => $place_type === 'popup_store' ? 'open' : '',
                'start_date' => $place_type === 'popup_store' ? date('Y-m-d') : '',
                'end_date' => $place_type === 'popup_store' ? date('Y-m-d', strtotime('+3 months')) : ''
            )
        );
    }
}
