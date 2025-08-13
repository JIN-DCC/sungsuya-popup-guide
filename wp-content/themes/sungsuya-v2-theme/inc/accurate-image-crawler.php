<?php
/**
 * 정확도 개선된 이미지 크롤링 시스템 V2
 * 
 * 주요 개선사항:
 * 1. 3단계 검색 전략 (정확도 우선)
 * 2. 강화된 이미지 필터링
 * 3. 신뢰도 점수 시스템
 * 4. 중복 제거 알고리즘
 * 
 * @package SungsuyaV2
 * @version 3.0.0
 * @since 2025-06-30
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 정확도 개선된 이미지 크롤링 클래스
 */
class Accurate_Image_Crawler {
    
    /**
     * 싱글톤 인스턴스
     */
    private static $instance = null;
    
    /**
     * 신뢰할 수 있는 도메인 목록
     */
    private $trusted_domains = array(
        'pstatic.net',          // 네이버
        'kakaocdn.net',         // 카카오
        'instagram.com',        // 인스타그램
        'googleusercontent.com', // 구글
        'k.kakaocdn.net',       // 카카오 CDN
        'postfiles.pstatic.net' // 네이버 포스트
    );
    
    /**
     * 제외할 URL 패턴
     */
    private $exclude_patterns = array(
        '/thumb/',
        '/thumbnail/',
        '/icon/',
        '/logo/',
        '/banner/',
        'placeholder',
        'default',
        'noimage',
        'sample',
        '_s.jpg',          // 작은 이미지
        '_t.jpg',          // 썸네일
        'profile_image'     // 프로필 이미지
    );
    
    /**
     * 출처별 신뢰도 점수
     */
    private $source_scores = array(
        'naver_place' => 10,        // 네이버 플레이스
        'google_place' => 10,       // 구글 플레이스
        'official_instagram' => 9,   // 공식 인스타그램
        'recent_blog' => 7,         // 최근 블로그 (1개월 이내)
        'location_tagged' => 6,     // 위치 태그된 이미지
        'verified_review' => 5,     // 검증된 리뷰
        'general_search' => 3       // 일반 검색
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
        add_action('wp_ajax_accurate_crawl_images', array($this, 'ajax_crawl_images'));
        add_action('wp_ajax_test_image_accuracy', array($this, 'ajax_test_accuracy'));
    }
    
    /**
     * 메인 크롤링 함수 - 3단계 전략
     */
    public function crawl_place_images($post_id, $options = array()) {
        $place_name = get_the_title($post_id);
        $address = get_post_meta($post_id, 'address', true);
        $place_type = $this->get_place_type($post_id);
        
        // 옵션 기본값 설정
        $options = wp_parse_args($options, array(
            'max_images' => 5,
            'min_score' => 5,
            'use_cache' => true
        ));
        
        error_log("=== 정확도 개선 이미지 크롤링 시작 ===");
        error_log("장소: {$place_name}, 주소: {$address}");
        
        // 캐시 확인
        if ($options['use_cache']) {
            $cached = get_post_meta($post_id, '_accurate_images_cache', true);
            if ($cached && !empty($cached['images']) && $cached['timestamp'] > time() - DAY_IN_SECONDS) {
                error_log("캐시된 이미지 사용");
                return $cached['images'];
            }
        }
        
        $all_images = array();
        
        // 1단계: 정확한 매칭 (최우선)
        $exact_matches = $this->search_exact_matches($place_name, $address);
        foreach ($exact_matches as $img) {
            $img['stage'] = 1;
            $all_images[] = $img;
        }
        
        // 2단계: 공식 채널 검색
        if (count($all_images) < $options['max_images']) {
            $official_images = $this->search_official_channels($place_name, $place_type);
            foreach ($official_images as $img) {
                $img['stage'] = 2;
                $all_images[] = $img;
            }
        }
        
        // 3단계: 검증된 리뷰/블로그
        if (count($all_images) < $options['max_images']) {
            $review_images = $this->search_verified_reviews($place_name, $address);
            foreach ($review_images as $img) {
                $img['stage'] = 3;
                $all_images[] = $img;
            }
        }
        
        // 이미지 검증 및 점수 계산
        $scored_images = $this->score_and_filter_images($all_images, $place_name, $options['min_score']);
        
        // 중복 제거
        $unique_images = $this->remove_duplicates($scored_images);
        
        // 상위 N개 선택
        $final_images = array_slice($unique_images, 0, $options['max_images']);
        
        // 캐시 저장
        if ($options['use_cache'] && !empty($final_images)) {
            update_post_meta($post_id, '_accurate_images_cache', array(
                'images' => $final_images,
                'timestamp' => time()
            ));
        }
        
        error_log("최종 선택된 이미지: " . count($final_images) . "개");
        
        return $final_images;
    }
    
    /**
     * 1단계: 정확한 매칭 검색
     */
    private function search_exact_matches($place_name, $address) {
        $images = array();
        
        // 주소에서 도로명 추출
        $street = $this->extract_street_name($address);
        
        // 정확한 검색 쿼리 구성 (저작권 안전 키워드 포함)
        $exact_queries = array(
            '"' . $place_name . '" 성수동 외관 -이벤트 -세일',         // 외관 사진 (이벤트 제외)
            '"' . $place_name . '" 성수동 간판 -포스터',              // 간판 사진 (포스터 제외)
            '"' . $place_name . '" 성수동 메뉴판',                    // 메뉴판 사진
            '"' . $place_name . '" 성수동 내부 전경',                 // 내부 전경
            '"' . $place_name . '" 성수동 실제 방문',                 // 실제 방문 사진
            '"' . $place_name . '" 성수동 ' . $street . ' -광고',     // 정확한 주소 (광고 제외)
            'site:naver.com "' . $place_name . '" 성수동',           // 네이버 공식
            '"' . $place_name . '" "' . $address . '" 매장'          // 전체 주소 + 매장
        );
        
        foreach ($exact_queries as $query) {
            // 카카오 검색
            $kakao_results = $this->search_kakao_images($query, array(
                'size' => 5,
                'sort' => 'accuracy'  // 정확도순
            ));
            
            foreach ($kakao_results as $img_url) {
                if ($this->is_valid_image_url($img_url)) {
                    $images[] = array(
                        'url' => $img_url,
                        'source' => 'kakao_exact',
                        'query' => $query,
                        'base_score' => $this->source_scores['naver_place'],
                        'type' => $this->detect_image_type($query)  // 이미지 타입 추가
                    );
                }
            }
            
            // 충분한 이미지를 찾았으면 중단
            if (count($images) >= 3) {
                break;
            }
        }
        
        return $images;
    }
    
    /**
     * 2단계: 공식 채널 검색
     */
    private function search_official_channels($place_name, $place_type) {
        $images = array();
        
        // 인스타그램 스타일 검색
        $instagram_queries = array(
            'site:instagram.com "' . $place_name . '" 성수',
            '@' . $this->generate_instagram_handle($place_name),
            '#' . str_replace(' ', '', $place_name) . '성수'
        );
        
        // 네이버 플레이스 검색
        $naver_queries = array(
            '네이버플레이스 "' . $place_name . '" 성수동',
            'site:store.naver.com "' . $place_name . '"'
        );
        
        // 인스타그램 검색
        foreach ($instagram_queries as $query) {
            $results = $this->search_integrated($query, 3);
            foreach ($results as $img_url) {
                if ($this->is_valid_image_url($img_url) && $this->is_instagram_image($img_url)) {
                    $images[] = array(
                        'url' => $img_url,
                        'source' => 'official_instagram',
                        'query' => $query,
                        'base_score' => $this->source_scores['official_instagram']
                    );
                }
            }
        }
        
        // 네이버 플레이스
        foreach ($naver_queries as $query) {
            $results = $this->search_naver_images($query, 3);
            foreach ($results as $img_url) {
                if ($this->is_valid_image_url($img_url)) {
                    $images[] = array(
                        'url' => $img_url,
                        'source' => 'naver_place',
                        'query' => $query,
                        'base_score' => $this->source_scores['naver_place']
                    );
                }
            }
        }
        
        return array_slice($images, 0, 5);
    }
    
    /**
     * 3단계: 검증된 리뷰/블로그 검색
     */
    private function search_verified_reviews($place_name, $address) {
        $images = array();
        
        // 최근 날짜 포함 검색
        $current_year = date('Y');
        $last_year = date('Y', strtotime('-1 year'));
        
        $review_queries = array(
            '"' . $place_name . '" 성수동 리뷰 ' . $current_year,
            '"' . $place_name . '" 성수동 방문 ' . $current_year,
            '"' . $place_name . '" 성수동 후기 ' . $last_year
        );
        
        foreach ($review_queries as $query) {
            $results = $this->search_integrated($query, 5);
            foreach ($results as $img_url) {
                if ($this->is_valid_image_url($img_url) && $this->is_recent_image($img_url)) {
                    $images[] = array(
                        'url' => $img_url,
                        'source' => 'recent_blog',
                        'query' => $query,
                        'base_score' => $this->source_scores['recent_blog']
                    );
                }
            }
        }
        
        return array_slice($images, 0, 10);
    }
    
    /**
     * 이미지 점수 계산 및 필터링
     */
    private function score_and_filter_images($images, $place_name, $min_score) {
        $scored_images = array();
        
        foreach ($images as $image) {
            $score = $image['base_score'];
            
            // URL 분석으로 추가 점수
            $url_lower = strtolower($image['url']);
            
            // 최신 이미지 보너스
            if ($this->is_recent_image($image['url'])) {
                $score += 3;
            }
            
            // 고해상도 이미지 보너스
            if (strpos($url_lower, 'original') !== false || 
                strpos($url_lower, 'large') !== false) {
                $score += 2;
            }
            
            // 장소명 포함 보너스
            $place_name_clean = str_replace(' ', '', strtolower($place_name));
            if (strpos($url_lower, $place_name_clean) !== false) {
                $score += 5;
            }
            
            // 검색 단계별 보너스
            $score += (4 - $image['stage']) * 2;  // 1단계: +6, 2단계: +4, 3단계: +2
            
            // === 추가된 정확도 향상 로직 ===
            
            // 1. 관련 없는 키워드 감점
            $irrelevant_keywords = array(
                'sale', '세일', 'event', '이벤트', 'banner', '배너',
                'promotion', '프로모션', 'ad', '광고', 'flyer', '전단지',
                'poster', '포스터', 'sns', 'facebook', 'instagram_story'
            );
            
            foreach ($irrelevant_keywords as $keyword) {
                if (stripos($url_lower, $keyword) !== false) {
                    $score -= 5;
                }
            }
            
            // 2. 성수동 관련 키워드 추가 점수
            $seongsu_keywords = array(
                '성수', 'seongsu', '성수동', 'seongsu-dong',
                '성동구', 'seongdong', '서울숲', 'seoul-forest'
            );
            
            foreach ($seongsu_keywords as $keyword) {
                if (stripos($url_lower, $keyword) !== false || 
                    (isset($image['title']) && stripos($image['title'], $keyword) !== false)) {
                    $score += 3;
                }
            }
            
            // 3. 이미지 타입별 가중치
            if (isset($image['type'])) {
                switch ($image['type']) {
                    case 'exterior':
                    case 'signage':
                        $score += 5;  // 외관/간판은 높은 점수
                        break;
                    case 'menu':
                    case 'interior':
                        $score += 3;  // 메뉴/내부는 중간 점수
                        break;
                    case 'food':
                        $score += 2;  // 음식은 낮은 점수
                        break;
                }
            }
            
            // 4. 소스별 신뢰도 조정
            if ($image['source'] === 'kakao_exact' && $image['stage'] === 1) {
                $score += 3;  // 정확한 검색의 카카오 결과 우대
            }
            
            // 최소 점수 이상만 포함
            if ($score >= $min_score) {
                $image['final_score'] = $score;
                $scored_images[] = $image;
            }
        }
        
        // 점수순 정렬
        usort($scored_images, function($a, $b) {
            return $b['final_score'] - $a['final_score'];
        });
        
        return $scored_images;
    }
    
    /**
     * 중복 이미지 제거
     */
    private function remove_duplicates($images) {
        $unique_images = array();
        $seen_hashes = array();
        
        foreach ($images as $image) {
            $url_hash = $this->generate_image_hash($image['url']);
            
            if (!in_array($url_hash, $seen_hashes)) {
                $seen_hashes[] = $url_hash;
                $unique_images[] = $image;
            }
        }
        
        return $unique_images;
    }
    
    /**
     * 이미지 URL 유효성 검사
     */
    private function is_valid_image_url($url) {
        // 기본 검사
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        $url_lower = strtolower($url);
        
        // 제외 패턴 확인
        foreach ($this->exclude_patterns as $pattern) {
            if (strpos($url_lower, $pattern) !== false) {
                return false;
            }
        }
        
        // === 추가된 제외 패턴 ===
        $additional_exclude_patterns = array(
            'event',          // 이벤트 이미지
            'promotion',      // 프로모션 이미지
            'sale',           // 세일 이미지
            'coupon',         // 쿠폰 이미지
            'qr',             // QR 코드
            'barcode',        // 바코드
            'map',            // 지도 이미지 (약도)
            'direction',      // 약도
            'flyer',          // 전단지
            'brochure',       // 브로셔
            'template',       // 템플릿
            'stock',          // 스톡 이미지
            'shutterstock',   // 스톡 사이트
            'gettyimages',    // 스톡 사이트
            'pixabay',        // 스톡 사이트
            'unsplash'        // 스톡 사이트
        );
        
        foreach ($additional_exclude_patterns as $pattern) {
            if (strpos($url_lower, $pattern) !== false) {
                error_log("이미지 제외됨 (패턴: {$pattern}): {$url}");
                return false;
            }
        }
        
        // 이미지 확장자 확인
        $valid_extensions = array('jpg', 'jpeg', 'png', 'webp');
        $extension = pathinfo($url_lower, PATHINFO_EXTENSION);
        
        if (!in_array($extension, $valid_extensions)) {
            // URL에 확장자가 없을 수도 있음 (동적 URL)
            if (!preg_match('/\.(jpg|jpeg|png|webp)/i', $url_lower)) {
                // 이미지 관련 키워드가 있는지 확인
                if (!preg_match('/(image|img|photo|picture)/i', $url_lower)) {
                    return false;
                }
            }
        }
        
        // 이미지 크기 체크 (너무 작은 이미지 제외)
        if (preg_match('/(\d+)x(\d+)/', $url, $matches)) {
            $width = intval($matches[1]);
            $height = intval($matches[2]);
            
            // 300x300 이하 이미지 제외
            if ($width < 300 || $height < 300) {
                error_log("이미지 제외됨 (크기 작음): {$width}x{$height} - {$url}");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 최신 이미지인지 확인
     */
    private function is_recent_image($url) {
        $current_year = date('Y');
        $last_year = date('Y', strtotime('-1 year'));
        
        // URL에 연도가 포함되어 있는지 확인
        if (preg_match('/20(2[3-5])/', $url, $matches) ||
            strpos($url, $current_year) !== false ||
            strpos($url, $last_year) !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 인스타그램 이미지인지 확인
     */
    private function is_instagram_image($url) {
        $instagram_domains = array(
            'instagram.com',
            'cdninstagram.com',
            'fbcdn.net'  // 페이스북 CDN (인스타그램 소유)
        );
        
        foreach ($instagram_domains as $domain) {
            if (strpos($url, $domain) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 이미지 해시 생성
     */
    private function generate_image_hash($url) {
        // URL 정규화
        $normalized_url = $this->normalize_url($url);
        
        // 파일명과 주요 파라미터만으로 해시 생성
        $parsed = parse_url($normalized_url);
        $path = isset($parsed['path']) ? $parsed['path'] : '';
        $filename = basename($path);
        
        // 크기 파라미터 제거
        $filename = preg_replace('/_\d+x\d+/', '', $filename);
        $filename = preg_replace('/-(scaled|thumb|small|medium|large)/', '', $filename);
        
        return md5($filename);
    }
    
    /**
     * URL 정규화
     */
    private function normalize_url($url) {
        // 프로토콜 통일
        $url = str_replace('http://', 'https://', $url);
        
        // 추적 파라미터 제거
        $url = preg_replace('/[?&](utm_[^&]+|fbclid=[^&]+)(&|$)/', '', $url);
        
        // 말단 슬래시 제거
        $url = rtrim($url, '/');
        
        return $url;
    }
    
    /**
     * 이미지 타입 감지
     */
    private function detect_image_type($query) {
        $query_lower = strtolower($query);
        
        if (strpos($query_lower, '외관') !== false || strpos($query_lower, 'exterior') !== false) {
            return 'exterior';
        }
        if (strpos($query_lower, '간판') !== false || strpos($query_lower, 'signage') !== false) {
            return 'signage';
        }
        if (strpos($query_lower, '메뉴') !== false || strpos($query_lower, 'menu') !== false) {
            return 'menu';
        }
        if (strpos($query_lower, '내부') !== false || strpos($query_lower, 'interior') !== false) {
            return 'interior';
        }
        if (strpos($query_lower, '음식') !== false || strpos($query_lower, 'food') !== false) {
            return 'food';
        }
        
        return 'general';
    }
    
    /**
     * 주소에서 도로명 추출
     */
    private function extract_street_name($address) {
        if (preg_match('/([가-힣]+로\d*길?)/', $address, $matches)) {
            return $matches[1];
        }
        return '';
    }
    
    /**
     * 인스타그램 핸들 생성
     */
    private function generate_instagram_handle($place_name) {
        // 공백 제거, 소문자 변환
        $handle = strtolower(str_replace(' ', '', $place_name));
        
        // 한글을 영문으로 간단히 변환 (실제로는 더 정교한 변환 필요)
        $handle = preg_replace('/[^a-z0-9_]/', '', $handle);
        
        return $handle . '_seongsu';
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
     * 카카오 이미지 검색
     */
    private function search_kakao_images($query, $options = array()) {
        $options = wp_parse_args($options, array(
            'size' => 10,
            'sort' => 'accuracy'
        ));
        
        $kakao_api_key = get_option('kakao_api_key', '');
        if (empty($kakao_api_key)) {
            return array();
        }
        
        $url = 'https://dapi.kakao.com/v2/search/image';
        $url .= '?query=' . urlencode($query);
        $url .= '&size=' . $options['size'];
        $url .= '&sort=' . $options['sort'];
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'KakaoAK ' . $kakao_api_key
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            error_log('카카오 이미지 검색 오류: ' . $response->get_error_message());
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        $images = array();
        if (!empty($data['documents'])) {
            foreach ($data['documents'] as $doc) {
                if (!empty($doc['image_url'])) {
                    $images[] = $doc['image_url'];
                }
            }
        }
        
        return $images;
    }
    
    /**
     * 네이버 이미지 검색
     */
    private function search_naver_images($query, $display = 10) {
        $client_id = get_option('sungsuya_naver_client_id', '');
        $client_secret = get_option('sungsuya_naver_client_secret', '');
        
        if (empty($client_id) || empty($client_secret)) {
            return array();
        }
        
        $url = 'https://openapi.naver.com/v1/search/image';
        $url .= '?query=' . urlencode($query);
        $url .= '&display=' . $display;
        $url .= '&sort=sim';  // 관련도순
        $url .= '&filter=large';  // 큰 이미지만
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'X-Naver-Client-Id' => $client_id,
                'X-Naver-Client-Secret' => $client_secret
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            error_log('네이버 이미지 검색 오류: ' . $response->get_error_message());
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        $images = array();
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                if (!empty($item['link'])) {
                    $images[] = $item['link'];
                }
            }
        }
        
        return $images;
    }
    
    /**
     * 통합 검색 (카카오 + 네이버)
     */
    private function search_integrated($query, $max_results = 10) {
        $kakao_results = $this->search_kakao_images($query, array('size' => ceil($max_results / 2)));
        $naver_results = $this->search_naver_images($query, ceil($max_results / 2));
        
        // 결과 병합 (교차 배치)
        $integrated = array();
        $max_count = max(count($kakao_results), count($naver_results));
        
        for ($i = 0; $i < $max_count; $i++) {
            if (isset($kakao_results[$i])) {
                $integrated[] = $kakao_results[$i];
            }
            if (isset($naver_results[$i])) {
                $integrated[] = $naver_results[$i];
            }
        }
        
        return array_slice($integrated, 0, $max_results);
    }
    
    /**
     * AJAX: 정확한 이미지 크롤링
     */
    public function ajax_crawl_images() {
        check_ajax_referer('accurate_image_crawl', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('권한이 없습니다.');
        }
        
        $post_id = intval($_POST['post_id']);
        if (!$post_id) {
            wp_send_json_error('잘못된 요청입니다.');
        }
        
        // 크롤링 실행
        $images = $this->crawl_place_images($post_id);
        
        if (empty($images)) {
            wp_send_json_error('관련 이미지를 찾을 수 없습니다.');
        }
        
        // 이미지 저장 로직 (기존 시스템 활용)
        $saved_images = array();
        foreach ($images as $image_data) {
            $image_url = is_array($image_data) ? $image_data['url'] : $image_data;
            
            // 이미지 다운로드 및 저장
            $attachment_id = $this->save_image_to_media_library($image_url, $post_id);
            if ($attachment_id) {
                $saved_images[] = array(
                    'id' => $attachment_id,
                    'url' => wp_get_attachment_url($attachment_id),
                    'score' => isset($image_data['final_score']) ? $image_data['final_score'] : 0
                );
            }
        }
        
        if (!empty($saved_images)) {
            // 첫 번째 이미지를 썸네일로 설정
            set_post_thumbnail($post_id, $saved_images[0]['id']);
            
            wp_send_json_success(array(
                'message' => count($saved_images) . '개의 정확한 이미지를 저장했습니다.',
                'images' => $saved_images
            ));
        } else {
            wp_send_json_error('이미지 저장에 실패했습니다.');
        }
    }
    
    /**
     * 이미지를 미디어 라이브러리에 저장
     */
    private function save_image_to_media_library($image_url, $post_id) {
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
        
        // 파일명에 장소명 포함
        $place_name = get_the_title($post_id);
        $place_name_clean = sanitize_title($place_name);
        $extension = pathinfo($file_array['name'], PATHINFO_EXTENSION);
        $file_array['name'] = $place_name_clean . '-' . time() . '.' . $extension;
        
        // 미디어 라이브러리에 저장
        $attachment_id = media_handle_sideload($file_array, $post_id);
        
        // 임시 파일 삭제
        @unlink($tmp);
        
        if (is_wp_error($attachment_id)) {
            error_log('이미지 저장 실패: ' . $attachment_id->get_error_message());
            return false;
        }
        
        return $attachment_id;
    }
    
    /**
     * 테스트용: 정확도 테스트
     */
    public function ajax_test_accuracy() {
        check_ajax_referer('test_image_accuracy', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('권한이 없습니다.');
        }
        
        $post_id = intval($_POST['post_id']);
        
        // 크롤링 실행 (캐시 사용 안함)
        $images = $this->crawl_place_images($post_id, array(
            'use_cache' => false,
            'max_images' => 10  // 더 많은 결과 확인
        ));
        
        // 상세 결과 반환
        $detailed_results = array();
        foreach ($images as $image) {
            $detailed_results[] = array(
                'url' => $image['url'],
                'source' => $image['source'],
                'stage' => $image['stage'],
                'score' => $image['final_score'],
                'query' => $image['query']
            );
        }
        
        wp_send_json_success(array(
            'total' => count($images),
            'results' => $detailed_results
        ));
    }
}

// 싱글톤 인스턴스 생성
Accurate_Image_Crawler::get_instance();
