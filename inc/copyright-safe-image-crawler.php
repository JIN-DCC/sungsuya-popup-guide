<?php
/**
 * 저작권 안전 이미지 크롤링 시스템
 * 
 * 주요 기능:
 * 1. 공식 이미지 우선 수집 (네이버 플레이스, 구글 비즈니스)
 * 2. 저작권 안전 이미지만 수집 (건물 외관, 간판, 메뉴판)
 * 3. 창작물/인물 사진 자동 필터링
 * 4. 이미지 타입 자동 분류
 * 
 * @package SungsuyaV2
 * @version 1.0.0
 * @since 2025-07-02
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 저작권 안전 이미지 크롤링 클래스
 */
class Copyright_Safe_Image_Crawler {
    
    /**
     * 싱글톤 인스턴스
     */
    private static $instance = null;
    
    /**
     * 이미지 소스 우선순위 및 안전도
     */
    private $safe_sources = array(
        'naver_place_official' => array(
            'priority' => 10,
            'safety' => 100,
            'description' => '네이버 플레이스 공식 이미지'
        ),
        'google_business_profile' => array(
            'priority' => 9,
            'safety' => 100,
            'description' => '구글 비즈니스 프로필 이미지'
        ),
        'official_website' => array(
            'priority' => 8,
            'safety' => 100,
            'description' => '공식 웹사이트 이미지'
        ),
        'official_instagram' => array(
            'priority' => 8,
            'safety' => 95,
            'description' => '공식 인스타그램 계정'
        ),
        'building_exterior' => array(
            'priority' => 7,
            'safety' => 90,
            'description' => '건물 외관 사진'
        ),
        'signage' => array(
            'priority' => 6,
            'safety' => 90,
            'description' => '간판/로고 사진'
        ),
        'menu_board' => array(
            'priority' => 5,
            'safety' => 85,
            'description' => '메뉴판/가격표'
        ),
        'business_hours' => array(
            'priority' => 4,
            'safety' => 85,
            'description' => '영업시간 안내'
        )
    );
    
    /**
     * 안전하지 않은 이미지 패턴
     */
    private $unsafe_patterns = array(
        // 인물 관련
        'person', 'people', 'selfie', 'portrait', 'face',
        '셀피', '얼굴', '인물', '초상',
        
        // 창작물
        'food_closeup', 'plating', 'interior_design', 'artwork',
        '음식클로즈업', '플레이팅', '인테리어', '작품',
        
        // 저작권 표시
        'copyright', 'watermark', '©', '®', '™',
        '저작권', '워터마크', '무단전재', '복사금지',
        
        // 개인 콘텐츠
        'blog_', 'review_', 'personal_', 'private_',
        '개인', '리뷰', '후기'
    );
    
    /**
     * 안전한 이미지 키워드
     */
    private $safe_keywords = array(
        // 건물/외관
        'exterior', 'building', 'facade', 'entrance', 'storefront',
        '외관', '건물', '입구', '매장전경', '전경',
        
        // 간판/사인
        'sign', 'signage', 'logo', 'nameplate',
        '간판', '로고', '상호', '사인보드',
        
        // 정보성 콘텐츠
        'menu', 'price', 'hours', 'information',
        '메뉴', '가격', '영업시간', '안내'
    );
    
    /**
     * 구글 Places API 설정
     */
    private $google_places_fields = array(
        'photos',
        'name',
        'formatted_address',
        'business_status'
    );
    
    /**
     * 생성자
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * 싱글톤 인스턴스 반환
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 초기화
     */
    private function init() {
        // AJAX 핸들러 등록
        add_action('wp_ajax_copyright_safe_crawl', array($this, 'ajax_crawl_images'));
        add_action('wp_ajax_test_copyright_safety', array($this, 'ajax_test_safety'));
        add_action('wp_ajax_analyze_image_type', array($this, 'ajax_analyze_image'));
    }
    
    /**
     * 메인 크롤링 함수
     */
    public function crawl_safe_images($post_id, $options = array()) {
        $place_name = get_the_title($post_id);
        $address = get_post_meta($post_id, 'address', true);
        $place_type = $this->get_place_type($post_id);
        
        // 옵션 기본값 설정
        $options = wp_parse_args($options, array(
            'max_images' => 5,
            'min_safety' => 85,
            'official_only' => false,
            'use_cache' => true
        ));
        
        error_log("=== 저작권 안전 이미지 크롤링 시작 ===");
        error_log("장소: {$place_name}, 주소: {$address}");
        error_log("최소 안전도: {$options['min_safety']}%");
        
        // 캐시 확인
        if ($options['use_cache']) {
            $cached = get_post_meta($post_id, '_copyright_safe_images_cache', true);
            if ($cached && !empty($cached['images']) && $cached['timestamp'] > time() - DAY_IN_SECONDS) {
                error_log("캐시된 안전 이미지 사용");
                return $cached['images'];
            }
        }
        
        $all_images = array();
        
        // 1순위: 공식 소스에서 이미지 수집
        $official_images = $this->collect_official_images($post_id, $place_name, $address);
        $all_images = array_merge($all_images, $official_images);
        
        // 2순위: 팩트 정보 이미지 수집 (공식 이미지가 부족한 경우)
        if (!$options['official_only'] && count($all_images) < $options['max_images']) {
            $fact_images = $this->collect_fact_images($place_name, $address, $place_type);
            $all_images = array_merge($all_images, $fact_images);
        }
        
        // 안전도 평가 및 필터링
        $safe_images = $this->evaluate_copyright_safety($all_images, $options['min_safety']);
        
        // 중복 제거
        $unique_images = $this->remove_duplicates($safe_images);
        
        // 우선순위 정렬
        usort($unique_images, function($a, $b) {
            return $b['safety_score'] - $a['safety_score'];
        });
        
        // 상위 N개 선택
        $final_images = array_slice($unique_images, 0, $options['max_images']);
        
        // 캐시 저장
        if ($options['use_cache'] && !empty($final_images)) {
            update_post_meta($post_id, '_copyright_safe_images_cache', array(
                'images' => $final_images,
                'timestamp' => time()
            ));
        }
        
        error_log("최종 선택된 안전 이미지: " . count($final_images) . "개");
        
        return $final_images;
    }
    
    /**
     * 공식 소스에서 이미지 수집
     */
    private function collect_official_images($post_id, $place_name, $address) {
        $images = array();
        
        // 1. 네이버 플레이스 공식 이미지
        $naver_images = $this->get_naver_place_official_images($place_name, $address);
        foreach ($naver_images as $img) {
            $img['source_type'] = 'naver_place_official';
            $images[] = $img;
        }
        
        // 2. 구글 비즈니스 프로필 이미지
        $google_images = $this->get_google_business_images($place_name, $address);
        foreach ($google_images as $img) {
            $img['source_type'] = 'google_business_profile';
            $images[] = $img;
        }
        
        // 3. 공식 웹사이트 이미지
        $website_url = get_post_meta($post_id, 'website', true);
        if (!empty($website_url)) {
            $website_images = $this->get_official_website_images($website_url, $place_name);
            foreach ($website_images as $img) {
                $img['source_type'] = 'official_website';
                $images[] = $img;
            }
        }
        
        // 4. 공식 인스타그램
        $instagram_id = get_post_meta($post_id, 'instagram_id', true);
        if (!empty($instagram_id)) {
            $instagram_images = $this->get_official_instagram_images($instagram_id);
            foreach ($instagram_images as $img) {
                $img['source_type'] = 'official_instagram';
                $images[] = $img;
            }
        }
        
        return $images;
    }
    
    /**
     * 팩트 정보 이미지 수집
     */
    private function collect_fact_images($place_name, $address, $place_type) {
        $images = array();
        
        // 안전한 키워드를 포함한 검색
        $safe_queries = array(
            $place_name . ' 성수동 건물 외관',
            $place_name . ' 성수동 간판',
            $place_name . ' 성수동 입구',
            $place_name . ' 성수동 전경'
        );
        
        // 업종별 추가 쿼리
        if ($place_type === 'cafe' || $place_type === 'restaurant') {
            $safe_queries[] = $place_name . ' 성수동 메뉴판';
            $safe_queries[] = $place_name . ' 성수동 가격표';
        }
        
        foreach ($safe_queries as $query) {
            // 카카오 이미지 검색
            $results = $this->search_images_with_safety_filter($query, 'kakao');
            
            foreach ($results as $img_url) {
                // 이미지 타입 추론
                $image_type = $this->infer_image_type_from_context($img_url, $query);
                
                $images[] = array(
                    'url' => $img_url,
                    'source_type' => $image_type,
                    'query' => $query,
                    'is_fact_image' => true
                );
            }
        }
        
        return $images;
    }
    
    /**
     * 저작권 안전도 평가
     */
    private function evaluate_copyright_safety($images, $min_safety) {
        $safe_images = array();
        
        foreach ($images as $image) {
            $safety_score = 0;
            
            // 1. 소스 타입별 기본 안전도
            if (isset($this->safe_sources[$image['source_type']])) {
                $safety_score = $this->safe_sources[$image['source_type']]['safety'];
            } else {
                $safety_score = 50; // 기본값
            }
            
            // 2. URL 패턴 분석
            $url_safety = $this->analyze_url_safety($image['url']);
            $safety_score = ($safety_score * 0.7) + ($url_safety * 0.3);
            
            // 3. 안전하지 않은 패턴 체크
            if ($this->contains_unsafe_patterns($image['url'])) {
                $safety_score *= 0.5; // 50% 감점
            }
            
            // 4. 안전한 키워드 보너스
            if ($this->contains_safe_keywords($image['url'], $image['query'] ?? '')) {
                $safety_score = min(100, $safety_score * 1.1); // 10% 보너스
            }
            
            // 최소 안전도 이상만 포함
            if ($safety_score >= $min_safety) {
                $image['safety_score'] = round($safety_score);
                $image['safety_level'] = $this->get_safety_level($safety_score);
                $safe_images[] = $image;
            }
        }
        
        return $safe_images;
    }
    
    /**
     * 네이버 플레이스 공식 이미지 가져오기
     */
    private function get_naver_place_official_images($place_name, $address) {
        $images = array();
        
        // 네이버 플레이스 검색 API 사용
        $client_id = get_option('sungsuya_naver_client_id', '');
        $client_secret = get_option('sungsuya_naver_client_secret', '');
        
        if (empty($client_id) || empty($client_secret)) {
            return $images;
        }
        
        // 장소 검색
        $search_query = $place_name . ' ' . $address;
        $url = 'https://openapi.naver.com/v1/search/local.json';
        $url .= '?query=' . urlencode($search_query);
        $url .= '&display=5';
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'X-Naver-Client-Id' => $client_id,
                'X-Naver-Client-Secret' => $client_secret
            ),
            'timeout' => 10
        ));
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    // 장소명 매칭 확인
                    $similarity = similar_text($place_name, $item['title'], $percent);
                    if ($percent > 80) {
                        // 네이버 플레이스 상세 페이지에서 이미지 추출
                        $place_images = $this->extract_naver_place_images($item);
                        $images = array_merge($images, $place_images);
                    }
                }
            }
        }
        
        return $images;
    }
    
    /**
     * 구글 비즈니스 프로필 이미지 가져오기
     */
    private function get_google_business_images($place_name, $address) {
        $images = array();
        
        $google_api_key = get_option('google_places_api_key', '');
        if (empty($google_api_key)) {
            return $images;
        }
        
        // Place Search API로 장소 찾기
        $search_url = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json';
        $search_url .= '?input=' . urlencode($place_name . ' ' . $address);
        $search_url .= '&inputtype=textquery';
        $search_url .= '&fields=place_id,name';
        $search_url .= '&key=' . $google_api_key;
        
        $response = wp_remote_get($search_url, array('timeout' => 10));
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!empty($data['candidates'][0]['place_id'])) {
                $place_id = $data['candidates'][0]['place_id'];
                
                // Place Details API로 사진 가져오기
                $details_url = 'https://maps.googleapis.com/maps/api/place/details/json';
                $details_url .= '?place_id=' . $place_id;
                $details_url .= '&fields=' . implode(',', $this->google_places_fields);
                $details_url .= '&key=' . $google_api_key;
                
                $details_response = wp_remote_get($details_url, array('timeout' => 10));
                
                if (!is_wp_error($details_response)) {
                    $details_body = wp_remote_retrieve_body($details_response);
                    $details_data = json_decode($details_body, true);
                    
                    if (!empty($details_data['result']['photos'])) {
                        foreach ($details_data['result']['photos'] as $photo) {
                            // 구글 플레이스 사진 URL 생성
                            $photo_url = 'https://maps.googleapis.com/maps/api/place/photo';
                            $photo_url .= '?maxwidth=1600';
                            $photo_url .= '&photoreference=' . $photo['photo_reference'];
                            $photo_url .= '&key=' . $google_api_key;
                            
                            $images[] = array(
                                'url' => $photo_url,
                                'attribution' => $photo['html_attributions'] ?? [],
                                'is_official' => true
                            );
                        }
                    }
                }
            }
        }
        
        return $images;
    }
    
    /**
     * 공식 웹사이트에서 이미지 추출
     */
    private function get_official_website_images($website_url, $place_name) {
        $images = array();
        
        // robots.txt 확인
        if (!$this->check_robots_txt($website_url)) {
            return $images;
        }
        
        // 웹페이지 가져오기
        $response = wp_remote_get($website_url, array(
            'timeout' => 10,
            'user-agent' => 'Mozilla/5.0 (compatible; SungsuyaBot/1.0; +http://sungsuya.com/bot)'
        ));
        
        if (!is_wp_error($response)) {
            $html = wp_remote_retrieve_body($response);
            
            // Open Graph 이미지 추출
            if (preg_match_all('/<meta property="og:image" content="([^"]+)"/', $html, $matches)) {
                foreach ($matches[1] as $img_url) {
                    $images[] = array(
                        'url' => $this->make_absolute_url($img_url, $website_url),
                        'is_official' => true,
                        'type' => 'og_image'
                    );
                }
            }
            
            // 구조화된 데이터에서 이미지 추출
            if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $match)) {
                $json_ld = json_decode($match[1], true);
                if (!empty($json_ld['image'])) {
                    $img_urls = is_array($json_ld['image']) ? $json_ld['image'] : array($json_ld['image']);
                    foreach ($img_urls as $img_url) {
                        $images[] = array(
                            'url' => $this->make_absolute_url($img_url, $website_url),
                            'is_official' => true,
                            'type' => 'structured_data'
                        );
                    }
                }
            }
        }
        
        return array_slice($images, 0, 3); // 최대 3개
    }
    
    /**
     * URL 안전도 분석
     */
    private function analyze_url_safety($url) {
        $safety_score = 70; // 기본값
        
        $url_lower = strtolower($url);
        
        // 공식 도메인 체크
        $official_domains = array(
            'naver.com', 'pstatic.net', 'google.com', 'googleusercontent.com',
            'instagram.com', 'cdninstagram.com', 'fbcdn.net'
        );
        
        foreach ($official_domains as $domain) {
            if (strpos($url_lower, $domain) !== false) {
                $safety_score += 20;
                break;
            }
        }
        
        // 안전하지 않은 도메인 체크
        $unsafe_domains = array(
            'tistory.com', 'blogspot.com', 'wordpress.com',
            'daumcdn.net' // 개인 블로그 이미지
        );
        
        foreach ($unsafe_domains as $domain) {
            if (strpos($url_lower, $domain) !== false) {
                $safety_score -= 20;
                break;
            }
        }
        
        // URL 구조 분석
        if (preg_match('/\/(exterior|building|storefront|signage|menu|hours)/', $url_lower)) {
            $safety_score += 10;
        }
        
        if (preg_match('/\/(review|blog|personal|private)/', $url_lower)) {
            $safety_score -= 15;
        }
        
        return max(0, min(100, $safety_score));
    }
    
    /**
     * 안전하지 않은 패턴 포함 여부 확인
     */
    private function contains_unsafe_patterns($url) {
        $url_lower = strtolower($url);
        
        foreach ($this->unsafe_patterns as $pattern) {
            if (strpos($url_lower, strtolower($pattern)) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 안전한 키워드 포함 여부 확인
     */
    private function contains_safe_keywords($url, $query = '') {
        $text = strtolower($url . ' ' . $query);
        
        foreach ($this->safe_keywords as $keyword) {
            if (strpos($text, strtolower($keyword)) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 안전도 레벨 반환
     */
    private function get_safety_level($score) {
        if ($score >= 95) return '매우 안전';
        if ($score >= 85) return '안전';
        if ($score >= 70) return '보통';
        if ($score >= 50) return '주의';
        return '위험';
    }
    
    /**
     * 이미지 타입 추론
     */
    private function infer_image_type_from_context($url, $query) {
        $url_lower = strtolower($url);
        $query_lower = strtolower($query);
        
        if (strpos($query_lower, '외관') !== false || strpos($query_lower, '건물') !== false) {
            return 'building_exterior';
        }
        
        if (strpos($query_lower, '간판') !== false || strpos($query_lower, '로고') !== false) {
            return 'signage';
        }
        
        if (strpos($query_lower, '메뉴') !== false || strpos($query_lower, '가격') !== false) {
            return 'menu_board';
        }
        
        if (strpos($query_lower, '영업시간') !== false) {
            return 'business_hours';
        }
        
        // URL 패턴으로 추론
        if (preg_match('/exterior|building|facade/', $url_lower)) {
            return 'building_exterior';
        }
        
        if (preg_match('/sign|logo/', $url_lower)) {
            return 'signage';
        }
        
        return 'general';
    }
    
    /**
     * 중복 이미지 제거
     */
    private function remove_duplicates($images) {
        $unique_images = array();
        $seen_hashes = array();
        
        foreach ($images as $image) {
            $url_hash = $this->generate_url_hash($image['url']);
            
            if (!in_array($url_hash, $seen_hashes)) {
                $seen_hashes[] = $url_hash;
                $unique_images[] = $image;
            }
        }
        
        return $unique_images;
    }
    
    /**
     * URL 해시 생성
     */
    private function generate_url_hash($url) {
        // URL 정규화
        $normalized = preg_replace('/[?&](utm_[^&]+|fbclid=[^&]+)(&|$)/', '', $url);
        $normalized = preg_replace('/_\d+x\d+/', '', $normalized);
        
        return md5($normalized);
    }
    
    /**
     * 절대 URL 만들기
     */
    private function make_absolute_url($url, $base_url) {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        
        $parsed = parse_url($base_url);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        
        if (strpos($url, '//') === 0) {
            return $scheme . ':' . $url;
        }
        
        if (strpos($url, '/') === 0) {
            return $scheme . '://' . $host . $url;
        }
        
        return $base_url . '/' . $url;
    }
    
    /**
     * robots.txt 확인
     */
    private function check_robots_txt($website_url) {
        $parsed = parse_url($website_url);
        $robots_url = $parsed['scheme'] . '://' . $parsed['host'] . '/robots.txt';
        
        $response = wp_remote_get($robots_url, array('timeout' => 5));
        
        if (!is_wp_error($response)) {
            $robots_txt = wp_remote_retrieve_body($response);
            
            // 간단한 User-agent 체크
            if (stripos($robots_txt, 'User-agent: *') !== false && 
                stripos($robots_txt, 'Disallow: /') !== false) {
                return false;
            }
        }
        
        return true; // 기본적으로 허용
    }
    
    /**
     * 안전 필터를 적용한 이미지 검색
     */
    private function search_images_with_safety_filter($query, $source = 'kakao') {
        $images = array();
        
        if ($source === 'kakao') {
            $kakao_api_key = get_option('sungsuya_kakao_rest_api_key', '');
            if (empty($kakao_api_key)) {
                return $images;
            }
            
            $url = 'https://dapi.kakao.com/v2/search/image';
            $url .= '?query=' . urlencode($query);
            $url .= '&size=10';
            $url .= '&sort=accuracy';
            
            $response = wp_remote_get($url, array(
                'headers' => array(
                    'Authorization' => 'KakaoAK ' . $kakao_api_key
                ),
                'timeout' => 10
            ));
            
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                if (!empty($data['documents'])) {
                    foreach ($data['documents'] as $doc) {
                        // 기본 안전 필터 적용
                        if (!$this->contains_unsafe_patterns($doc['image_url']) && 
                            $this->is_appropriate_size($doc['width'], $doc['height'])) {
                            $images[] = $doc['image_url'];
                        }
                    }
                }
            }
        }
        
        return array_slice($images, 0, 5);
    }
    
    /**
     * 적절한 이미지 크기인지 확인
     */
    private function is_appropriate_size($width, $height) {
        // 너무 작거나 큰 이미지 제외
        if ($width < 400 || $height < 300) return false;
        if ($width > 4000 || $height > 4000) return false;
        
        // 극단적인 비율 제외
        $ratio = $width / $height;
        if ($ratio < 0.5 || $ratio > 2.5) return false;
        
        return true;
    }
    
    /**
     * 네이버 플레이스에서 이미지 추출
     */
    private function extract_naver_place_images($place_item) {
        $images = array();
        
        // 네이버 플레이스 URL에서 ID 추출
        if (!empty($place_item['link'])) {
            // 네이버 플레이스 페이지를 스크래핑하는 대신
            // 네이버 검색 API의 이미지 검색 활용
            $place_name = strip_tags($place_item['title']);
            $query = '"' . $place_name . '" site:store.naver.com';
            
            $client_id = get_option('sungsuya_naver_client_id', '');
            $client_secret = get_option('sungsuya_naver_client_secret', '');
            
            $url = 'https://openapi.naver.com/v1/search/image';
            $url .= '?query=' . urlencode($query);
            $url .= '&display=5';
            $url .= '&sort=sim';
            $url .= '&filter=large';
            
            $response = wp_remote_get($url, array(
                'headers' => array(
                    'X-Naver-Client-Id' => $client_id,
                    'X-Naver-Client-Secret' => $client_secret
                ),
                'timeout' => 10
            ));
            
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                if (!empty($data['items'])) {
                    foreach ($data['items'] as $item) {
                        if ($this->is_naver_official_image($item['link'])) {
                            $images[] = array(
                                'url' => $item['link'],
                                'is_official' => true
                            );
                        }
                    }
                }
            }
        }
        
        return $images;
    }
    
    /**
     * 네이버 공식 이미지인지 확인
     */
    private function is_naver_official_image($url) {
        $official_domains = array(
            'pstatic.net',
            'naver.com',
            'naver.net'
        );
        
        foreach ($official_domains as $domain) {
            if (strpos($url, $domain) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 공식 인스타그램 이미지 가져오기
     */
    private function get_official_instagram_images($instagram_id) {
        $images = array();
        
        // 인스타그램 API는 제한적이므로
        // 대안으로 인스타그램 관련 검색 수행
        $query = '@' . $instagram_id . ' 성수';
        
        $kakao_api_key = get_option('sungsuya_kakao_rest_api_key', '');
        if (!empty($kakao_api_key)) {
            $url = 'https://dapi.kakao.com/v2/search/web';
            $url .= '?query=' . urlencode($query);
            $url .= '&size=10';
            
            $response = wp_remote_get($url, array(
                'headers' => array(
                    'Authorization' => 'KakaoAK ' . $kakao_api_key
                ),
                'timeout' => 10
            ));
            
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                if (!empty($data['documents'])) {
                    foreach ($data['documents'] as $doc) {
                        if (strpos($doc['url'], 'instagram.com') !== false) {
                            // 인스타그램 포스트에서 이미지 추출
                            $post_images = $this->extract_instagram_images($doc['url']);
                            $images = array_merge($images, $post_images);
                        }
                    }
                }
            }
        }
        
        return array_slice($images, 0, 3);
    }
    
    /**
     * 인스타그램 포스트에서 이미지 추출
     */
    private function extract_instagram_images($post_url) {
        $images = array();
        
        // 인스타그램 embed를 통한 이미지 추출
        $oembed_url = 'https://api.instagram.com/oembed?url=' . urlencode($post_url);
        
        $response = wp_remote_get($oembed_url, array('timeout' => 10));
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!empty($data['thumbnail_url'])) {
                $images[] = array(
                    'url' => $data['thumbnail_url'],
                    'is_official' => true
                );
            }
        }
        
        return $images;
    }
    
    /**
     * 장소 유형 가져오기
     */
    private function get_place_type($post_id) {
        $terms = wp_get_post_terms($post_id, 'place_type');
        if (!empty($terms) && !is_wp_error($terms)) {
            return $terms[0]->slug;
        }
        return 'general';
    }
    
    /**
     * AJAX: 저작권 안전 이미지 크롤링
     */
    public function ajax_crawl_images() {
        check_ajax_referer('copyright_safe_crawl', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('권한이 없습니다.');
        }
        
        $post_id = intval($_POST['post_id']);
        $official_only = isset($_POST['official_only']) && $_POST['official_only'] === 'true';
        
        if (!$post_id) {
            wp_send_json_error('잘못된 요청입니다.');
        }
        
        // 크롤링 옵션
        $options = array(
            'official_only' => $official_only,
            'min_safety' => $official_only ? 95 : 85,
            'use_cache' => false // 강제 새로고침
        );
        
        // 크롤링 실행
        $images = $this->crawl_safe_images($post_id, $options);
        
        if (empty($images)) {
            wp_send_json_error('안전한 이미지를 찾을 수 없습니다.');
        }
        
        // 이미지 저장
        $saved_images = array();
        foreach ($images as $image_data) {
            $attachment_id = $this->save_image_to_media_library(
                $image_data['url'], 
                $post_id,
                $image_data
            );
            
            if ($attachment_id) {
                $saved_images[] = array(
                    'id' => $attachment_id,
                    'url' => wp_get_attachment_url($attachment_id),
                    'safety_score' => $image_data['safety_score'],
                    'safety_level' => $image_data['safety_level'],
                    'source' => $this->safe_sources[$image_data['source_type']]['description'] ?? '기타'
                );
            }
        }
        
        if (!empty($saved_images)) {
            // 첫 번째 이미지를 썸네일로 설정
            set_post_thumbnail($post_id, $saved_images[0]['id']);
            
            // 이미지 메타데이터 저장
            update_post_meta($post_id, '_copyright_safe_images', $saved_images);
            
            wp_send_json_success(array(
                'message' => sprintf(
                    '%d개의 저작권 안전 이미지를 저장했습니다.',
                    count($saved_images)
                ),
                'images' => $saved_images
            ));
        } else {
            wp_send_json_error('이미지 저장에 실패했습니다.');
        }
    }
    
    /**
     * AJAX: 저작권 안전도 테스트
     */
    public function ajax_test_safety() {
        check_ajax_referer('test_copyright_safety', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('권한이 없습니다.');
        }
        
        $post_id = intval($_POST['post_id']);
        
        // 테스트 크롤링 실행
        $images = $this->crawl_safe_images($post_id, array(
            'use_cache' => false,
            'max_images' => 10,
            'min_safety' => 0 // 모든 이미지 포함
        ));
        
        // 상세 결과 반환
        $detailed_results = array();
        foreach ($images as $image) {
            $detailed_results[] = array(
                'url' => $image['url'],
                'source_type' => $image['source_type'],
                'source_desc' => $this->safe_sources[$image['source_type']]['description'] ?? '기타',
                'safety_score' => $image['safety_score'],
                'safety_level' => $image['safety_level'],
                'is_official' => $image['is_official'] ?? false
            );
        }
        
        // 안전도별 통계
        $stats = array(
            'total' => count($images),
            'very_safe' => 0,
            'safe' => 0,
            'moderate' => 0,
            'caution' => 0,
            'danger' => 0
        );
        
        foreach ($images as $img) {
            $score = $img['safety_score'];
            if ($score >= 95) $stats['very_safe']++;
            elseif ($score >= 85) $stats['safe']++;
            elseif ($score >= 70) $stats['moderate']++;
            elseif ($score >= 50) $stats['caution']++;
            else $stats['danger']++;
        }
        
        wp_send_json_success(array(
            'stats' => $stats,
            'results' => $detailed_results
        ));
    }
    
    /**
     * AJAX: 이미지 타입 분석
     */
    public function ajax_analyze_image() {
        check_ajax_referer('analyze_image_type', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('권한이 없습니다.');
        }
        
        $image_url = esc_url_raw($_POST['image_url']);
        if (empty($image_url)) {
            wp_send_json_error('이미지 URL이 필요합니다.');
        }
        
        // URL 안전도 분석
        $url_safety = $this->analyze_url_safety($image_url);
        
        // 안전하지 않은 패턴 체크
        $has_unsafe = $this->contains_unsafe_patterns($image_url);
        
        // 안전한 키워드 체크
        $has_safe = $this->contains_safe_keywords($image_url);
        
        // 이미지 타입 추론
        $inferred_type = $this->infer_image_type_from_context($image_url, '');
        
        $analysis = array(
            'url_safety_score' => $url_safety,
            'has_unsafe_patterns' => $has_unsafe,
            'has_safe_keywords' => $has_safe,
            'inferred_type' => $inferred_type,
            'type_description' => $this->safe_sources[$inferred_type]['description'] ?? '일반',
            'recommendation' => $url_safety >= 70 && !$has_unsafe ? '사용 가능' : '사용 주의'
        );
        
        wp_send_json_success($analysis);
    }
    
    /**
     * 이미지를 미디어 라이브러리에 저장
     */
    private function save_image_to_media_library($image_url, $post_id, $metadata = array()) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // 임시 파일로 다운로드
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            error_log('이미지 다운로드 실패: ' . $tmp->get_error_message());
            return false;
        }
        
        // 파일 정보 준비
        $file_array = array(
            'name' => basename($image_url),
            'tmp_name' => $tmp
        );
        
        // 파일명에 장소명과 타입 포함
        $place_name = get_the_title($post_id);
        $place_name_clean = sanitize_title($place_name);
        $image_type = $metadata['source_type'] ?? 'general';
        $extension = pathinfo($file_array['name'], PATHINFO_EXTENSION);
        
        if (empty($extension)) {
            $extension = 'jpg'; // 기본값
        }
        
        $file_array['name'] = sprintf(
            '%s-%s-%s.%s',
            $place_name_clean,
            $image_type,
            time(),
            $extension
        );
        
        // 미디어 라이브러리에 저장
        $attachment_id = media_handle_sideload($file_array, $post_id);
        
        // 임시 파일 삭제
        @unlink($tmp);
        
        if (is_wp_error($attachment_id)) {
            error_log('이미지 저장 실패: ' . $attachment_id->get_error_message());
            return false;
        }
        
        // 이미지 메타데이터 저장
        if (!empty($metadata)) {
            update_post_meta($attachment_id, '_copyright_safe_metadata', array(
                'source_type' => $metadata['source_type'] ?? '',
                'safety_score' => $metadata['safety_score'] ?? 0,
                'safety_level' => $metadata['safety_level'] ?? '',
                'crawled_date' => current_time('mysql'),
                'original_url' => $image_url
            ));
        }
        
        return $attachment_id;
    }
}

// 싱글톤 인스턴스 생성
Copyright_Safe_Image_Crawler::get_instance();