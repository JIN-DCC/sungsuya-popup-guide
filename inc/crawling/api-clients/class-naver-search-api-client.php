<?php
/**
 * 네이버 검색 API (지역) 클라이언트
 * 
 * 네이버 검색 API를 사용하여 장소 상세정보를 가져옵니다
 * 
 * @package SungsuyaV2
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Naver_Search_API_Client {
    
    private $client_id;
    private $client_secret;
    private $base_url = 'https://openapi.naver.com/v1/search/local.json';
    
    public function __construct() {
        // 네이버 검색 API 키 (NCP API와는 별도)
        $this->client_id = get_option('naver_search_client_id');
        $this->client_secret = get_option('naver_search_client_secret');
    }
    
    /**
     * 지역 검색 - 장소명으로 검색
     */
    public function search_place($query, $display = 5) {
        if (!$this->client_id || !$this->client_secret) {
            return new WP_Error('no_api_key', '네이버 검색 API 키가 설정되지 않았습니다.');
        }
        
        $params = array(
            'query' => $query,
            'display' => min($display, 5), // 최대 5개
            'start' => 1,
            'sort' => 'sim' // 정확도순
        );
        
        $url = $this->base_url . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'X-Naver-Client-Id' => $this->client_id,
                'X-Naver-Client-Secret' => $this->client_secret
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
     * 성수동 지역 한정 검색
     */
    public function search_seongsu_place($keyword) {
        // "성수동 {키워드}" 형식으로 검색
        $query = '성수동 ' . $keyword;
        return $this->search_place($query);
    }
    
    /**
     * 네이버 검색 결과를 WordPress 메타필드로 변환
     */
    public function convert_to_meta_fields($naver_place) {
        $meta_fields = array();
        
        // 기본 정보
        if (!empty($naver_place['title'])) {
            // HTML 태그 제거
            $meta_fields['naver_title'] = strip_tags($naver_place['title']);
        }
        
        if (!empty($naver_place['link'])) {
            $meta_fields['naver_place_url'] = $naver_place['link'];
        }
        
        if (!empty($naver_place['category'])) {
            $meta_fields['naver_category'] = $naver_place['category'];
            
            // 카테고리 기반 메타필드 추론
            $category_meta = $this->parse_naver_category($naver_place['category']);
            $meta_fields = array_merge($meta_fields, $category_meta);
        }
        
        if (!empty($naver_place['description'])) {
            $meta_fields['description'] = $naver_place['description'];
        }
        
        if (!empty($naver_place['telephone'])) {
            $meta_fields['phone'] = $naver_place['telephone'];
        }
        
        if (!empty($naver_place['address'])) {
            $meta_fields['address'] = $naver_place['address'];
        }
        
        if (!empty($naver_place['roadAddress'])) {
            $meta_fields['road_address'] = $naver_place['roadAddress'];
        }
        
        // 좌표 (mapx, mapy는 카텍 좌표계이므로 변환 필요)
        if (!empty($naver_place['mapx']) && !empty($naver_place['mapy'])) {
            // 카텍 좌표를 WGS84로 변환
            $coords = $this->convert_katec_to_wgs84(
                $naver_place['mapx'],
                $naver_place['mapy']
            );
            
            if ($coords) {
                $meta_fields['longitude'] = $coords['lng'];
                $meta_fields['latitude'] = $coords['lat'];
            }
        }
        
        return $meta_fields;
    }
    
    /**
     * 네이버 카테고리 파싱
     */
    private function parse_naver_category($category) {
        $meta = array();
        
        // 카테고리 매핑
        $category_mappings = array(
            '한식' => array('cuisine_type' => 'korean', 'price_range' => 'medium'),
            '중식' => array('cuisine_type' => 'chinese', 'price_range' => 'medium'),
            '일식' => array('cuisine_type' => 'japanese', 'price_range' => 'high'),
            '양식' => array('cuisine_type' => 'western', 'price_range' => 'high'),
            '카페' => array('cuisine_type' => 'cafe', 'price_range' => 'low'),
            '디저트' => array('cuisine_type' => 'bakery', 'price_range' => 'low'),
            '술집' => array('cuisine_type' => 'bar', 'price_range' => 'medium'),
            '패스트푸드' => array('cuisine_type' => 'fastfood', 'price_range' => 'low')
        );
        
        foreach ($category_mappings as $key => $mapping) {
            if (strpos($category, $key) !== false) {
                $meta = array_merge($meta, $mapping);
                break;
            }
        }
        
        return $meta;
    }
    
    /**
     * 카텍 좌표계를 WGS84로 변환
     * 네이버 API는 카텍(TM128) 좌표를 반환하므로 변환 필요
     */
    private function convert_katec_to_wgs84($x, $y) {
        // 간단한 변환 공식 (정확도는 낮지만 대략적인 위치 파악 가능)
        // 실제로는 proj4 라이브러리나 변환 API 사용 권장
        
        // TM128 원점
        $tm_lat = 38.0;
        $tm_lon = 128.0;
        
        // 대략적인 변환 (미터 단위)
        $lat = $tm_lat + ($y - 500000) / 111000.0;
        $lng = $tm_lon + ($x - 200000) / 88000.0;
        
        return array(
            'lat' => $lat,
            'lng' => $lng
        );
    }
    
    /**
     * 장소 매칭 - 카카오 데이터와 네이버 데이터 매칭
     */
    public function match_with_kakao_place($kakao_place, $naver_results) {
        if (empty($naver_results['items'])) {
            return null;
        }
        
        $best_match = null;
        $best_score = 0;
        
        foreach ($naver_results['items'] as $naver_place) {
            $score = 0;
            
            // 이름 유사도 체크
            $kakao_name = $kakao_place['place_name'];
            $naver_name = strip_tags($naver_place['title']);
            
            similar_text($kakao_name, $naver_name, $percent);
            $score += $percent;
            
            // 전화번호 일치 체크 (가장 신뢰할 수 있는 지표)
            if (!empty($kakao_place['phone']) && !empty($naver_place['telephone'])) {
                $kakao_phone = preg_replace('/[^0-9]/', '', $kakao_place['phone']);
                $naver_phone = preg_replace('/[^0-9]/', '', $naver_place['telephone']);
                
                if ($kakao_phone === $naver_phone) {
                    $score += 50; // 전화번호 일치 시 높은 점수
                }
            }
            
            // 주소 유사도 체크
            if (!empty($kakao_place['address_name']) && !empty($naver_place['address'])) {
                similar_text($kakao_place['address_name'], $naver_place['address'], $percent);
                $score += ($percent * 0.3); // 주소는 가중치 낮게
            }
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_match = $naver_place;
            }
        }
        
        // 매칭 신뢰도 60% 이상인 경우만 반환
        if ($best_score >= 60) {
            return $best_match;
        }
        
        return null;
    }
    
    /**
     * 카카오 + 네이버 데이터 병합
     */
    public function merge_with_kakao_data($kakao_place) {
        // 네이버에서 동일 장소 검색
        $search_query = $kakao_place['place_name'];
        if (!empty($kakao_place['address_name'])) {
            // 주소의 동 정보 추가하여 정확도 향상
            if (preg_match('/(성수동\d가)/', $kakao_place['address_name'], $matches)) {
                $search_query = $matches[1] . ' ' . $search_query;
            }
        }
        
        $naver_results = $this->search_place($search_query);
        
        if (is_wp_error($naver_results)) {
            return $kakao_place; // 네이버 검색 실패 시 원본 반환
        }
        
        $matched_place = $this->match_with_kakao_place($kakao_place, $naver_results);
        
        if ($matched_place) {
            // 네이버 데이터를 메타필드로 변환
            $naver_meta = $this->convert_to_meta_fields($matched_place);
            
            // 카카오 데이터와 병합 (카카오 데이터 우선, 네이버로 보완)
            foreach ($naver_meta as $key => $value) {
                if (empty($kakao_place[$key]) && !empty($value)) {
                    $kakao_place[$key] = $value;
                }
            }
            
            // 네이버 매칭 성공 플래그
            $kakao_place['naver_matched'] = true;
            $kakao_place['naver_data'] = $matched_place;
        }
        
        return $kakao_place;
    }
}

/**
 * 네이버 검색 API 설정 추가
 */
function register_naver_search_api_settings() {
    register_setting('api_settings', 'naver_search_client_id');
    register_setting('api_settings', 'naver_search_client_secret');
    
    add_settings_section(
        'naver_search_api_section',
        '네이버 검색 API 설정',
        'naver_search_api_section_callback',
        'api-settings'
    );
    
    add_settings_field(
        'naver_search_client_id',
        '검색 API Client ID',
        'naver_search_client_id_callback',
        'api-settings',
        'naver_search_api_section'
    );
    
    add_settings_field(
        'naver_search_client_secret',
        '검색 API Client Secret',
        'naver_search_client_secret_callback',
        'api-settings',
        'naver_search_api_section'
    );
}
add_action('admin_init', 'register_naver_search_api_settings', 20);

function naver_search_api_section_callback() {
    echo '<p>네이버 개발자센터에서 "검색" API를 신청하여 발급받은 키를 입력하세요.</p>';
    echo '<p><a href="https://developers.naver.com/apps/#/register" target="_blank">네이버 개발자센터 바로가기</a></p>';
    echo '<p class="description">주의: 이는 NCP Maps API와는 별개의 API입니다.</p>';
}

function naver_search_client_id_callback() {
    $value = get_option('naver_search_client_id');
    echo '<input type="text" name="naver_search_client_id" value="' . esc_attr($value) . '" class="regular-text" />';
}

function naver_search_client_secret_callback() {
    $value = get_option('naver_search_client_secret');
    echo '<input type="password" name="naver_search_client_secret" value="' . esc_attr($value) . '" class="regular-text" />';
}
