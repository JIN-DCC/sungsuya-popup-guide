<?php
/**
 * 저작권 안전 이미지 필터
 * 
 * 저작권 문제가 없는 이미지만 필터링하는 클래스
 * - 건물 외관, 간판, 메뉴판 등 공공장소 이미지 우선
 * - 인물/예술작품 제외
 * - 공식 채널 이미지 우선
 * 
 * @package SungsuyaV2
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Copyright_Safe_Image_Filter {
    
    /**
     * 싱글톤 인스턴스
     */
    private static $instance = null;
    
    /**
     * 안전한 이미지 키워드
     */
    private $safe_keywords = array(
        // 건물/외관
        'exterior', '외관', 'building', '건물', 'facade', 'storefront', '매장',
        'entrance', '입구', 'signage', '간판', 'shopfront', '전경',
        
        // 인테리어 (일반적인 것)
        'interior', '내부', 'dining', 'hall', '홀', 'counter', '카운터',
        'table', '테이블', 'chair', '의자', 'decor', '인테리어',
        
        // 메뉴/음식
        'menu', '메뉴', 'food', '음식', 'dish', '요리', 'cuisine',
        'coffee', '커피', 'beverage', '음료', 'dessert', '디저트',
        
        // 공식/비즈니스
        'official', '공식', 'business', 'store', 'shop', 'cafe', 'restaurant',
        'place', 'venue', '매장사진', '가게', '카페사진', '레스토랑사진'
    );
    
    /**
     * 위험한 이미지 키워드
     */
    private $risky_keywords = array(
        // 인물
        'selfie', '셀피', 'portrait', '인물', 'person', 'people', '사람',
        'face', '얼굴', 'model', '모델', 'staff', '직원', 'owner', '사장님',
        
        // 예술작품/창작물
        'art', '예술', 'artwork', '작품', 'painting', '그림', 'illustration',
        'drawing', 'design', '디자인', 'pattern', '패턴', 'mural', '벽화',
        
        // 브랜드/로고
        'logo', '로고', 'brand', '브랜드', 'trademark', 'copyright',
        'watermark', '워터마크', 'signature', '서명',
        
        // 개인 콘텐츠
        'instagram', 'blog', 'personal', '개인', 'private', 'review', '리뷰'
    );
    
    /**
     * 공식 도메인 목록
     */
    private $official_domains = array(
        // 플랫폼 공식
        'naver.com', 'store.naver.com', 'place.naver.com',
        'kakao.com', 'place.map.kakao.com',
        'google.com', 'maps.google.com', 'business.google.com',
        
        // CDN (공식적으로 호스팅되는 이미지)
        'pstatic.net', 'daumcdn.net', 'kakaocdn.net',
        'googleusercontent.com', 'ggpht.com',
        
        // 공공 이미지 저장소
        'wikimedia.org', 'wikipedia.org'
    );
    
    /**
     * 생성자
     */
    private function __construct() {}
    
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
     * 이미지 안전성 점수 계산
     * 
     * @param array $image_data 이미지 정보
     * @return int 안전성 점수 (0-100)
     */
    public function calculate_safety_score($image_data) {
        $score = 50; // 기본 점수
        
        // URL 분석
        $url = isset($image_data['url']) ? $image_data['url'] : '';
        $url_lower = strtolower($url);
        
        // 1. 공식 도메인 체크 (+30점)
        if ($this->is_official_domain($url)) {
            $score += 30;
        }
        
        // 2. 안전 키워드 체크 (각 +5점, 최대 +25점)
        $safe_count = 0;
        foreach ($this->safe_keywords as $keyword) {
            if (stripos($url_lower, $keyword) !== false || 
                (isset($image_data['title']) && stripos($image_data['title'], $keyword) !== false) ||
                (isset($image_data['alt']) && stripos($image_data['alt'], $keyword) !== false)) {
                $safe_count++;
                if ($safe_count >= 5) break;
            }
        }
        $score += $safe_count * 5;
        
        // 3. 위험 키워드 체크 (각 -10점)
        foreach ($this->risky_keywords as $keyword) {
            if (stripos($url_lower, $keyword) !== false || 
                (isset($image_data['title']) && stripos($image_data['title'], $keyword) !== false) ||
                (isset($image_data['alt']) && stripos($image_data['alt'], $keyword) !== false)) {
                $score -= 10;
            }
        }
        
        // 4. 출처별 보정
        if (isset($image_data['source'])) {
            switch ($image_data['source']) {
                case 'google_place':
                case 'naver_place':
                    $score += 20; // 공식 플레이스 이미지
                    break;
                case 'official_instagram':
                    $score += 10; // 공식 인스타그램
                    break;
                case 'recent_blog':
                case 'verified_review':
                    $score -= 5; // 개인 콘텐츠 가능성
                    break;
            }
        }
        
        // 5. 이미지 컨텍스트 분석
        if (isset($image_data['context'])) {
            $context = strtolower($image_data['context']);
            
            // 메뉴/음식 사진은 일반적으로 안전
            if (strpos($context, 'menu') !== false || strpos($context, 'food') !== false) {
                $score += 15;
            }
            
            // 외관/간판 사진은 매우 안전
            if (strpos($context, 'exterior') !== false || strpos($context, 'signage') !== false) {
                $score += 20;
            }
        }
        
        // 점수 범위 제한 (0-100)
        return max(0, min(100, $score));
    }
    
    /**
     * 이미지 필터링
     * 
     * @param array $images 이미지 배열
     * @param int $min_safety_score 최소 안전성 점수
     * @return array 필터링된 이미지
     */
    public function filter_safe_images($images, $min_safety_score = 60) {
        $filtered = array();
        
        foreach ($images as $image) {
            $safety_score = $this->calculate_safety_score($image);
            
            if ($safety_score >= $min_safety_score) {
                $image['safety_score'] = $safety_score;
                $filtered[] = $image;
            } else {
                error_log("이미지 제외됨 (안전성 점수: {$safety_score}): " . $image['url']);
            }
        }
        
        // 안전성 점수순으로 정렬
        usort($filtered, function($a, $b) {
            return $b['safety_score'] - $a['safety_score'];
        });
        
        return $filtered;
    }
    
    /**
     * 공식 도메인 확인
     */
    private function is_official_domain($url) {
        $parsed = parse_url($url);
        if (!isset($parsed['host'])) {
            return false;
        }
        
        $host = $parsed['host'];
        
        foreach ($this->official_domains as $domain) {
            if (strpos($host, $domain) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 이미지 타입별 우선순위 결정
     */
    public function prioritize_by_type($images, $place_type) {
        $type_priorities = array(
            'cafe' => array('menu', 'interior', 'exterior', 'coffee', 'dessert'),
            'restaurant' => array('food', 'menu', 'interior', 'exterior', 'dish'),
            'shop' => array('exterior', 'product', 'interior', 'display'),
            'gallery' => array('exterior', 'space', 'exhibition'),
            'bar' => array('interior', 'menu', 'cocktail', 'exterior')
        );
        
        $priorities = isset($type_priorities[$place_type]) ? $type_priorities[$place_type] : array('exterior', 'interior');
        
        // 우선순위에 따라 점수 부여
        foreach ($images as &$image) {
            $priority_score = 0;
            $url_lower = strtolower($image['url']);
            
            foreach ($priorities as $index => $keyword) {
                if (stripos($url_lower, $keyword) !== false || 
                    (isset($image['title']) && stripos($image['title'], $keyword) !== false)) {
                    $priority_score = count($priorities) - $index;
                    break;
                }
            }
            
            $image['priority_score'] = $priority_score;
        }
        
        // 우선순위 점수순으로 정렬
        usort($images, function($a, $b) {
            if ($a['priority_score'] == $b['priority_score']) {
                return $b['safety_score'] - $a['safety_score'];
            }
            return $b['priority_score'] - $a['priority_score'];
        });
        
        return $images;
    }
    
    /**
     * 이미지 메타데이터로 안전성 검증
     */
    public function verify_image_metadata($image_url) {
        // 이미지 헤더 정보 가져오기
        $headers = @get_headers($image_url, 1);
        
        if ($headers === false) {
            return array('safe' => false, 'reason' => 'URL 접근 불가');
        }
        
        // Content-Type 확인
        $content_type = isset($headers['Content-Type']) ? $headers['Content-Type'] : '';
        if (!preg_match('/image\/(jpeg|jpg|png|webp|gif)/i', $content_type)) {
            return array('safe' => false, 'reason' => '이미지 파일이 아님');
        }
        
        // 파일 크기 확인 (너무 작거나 큰 파일 제외)
        $content_length = isset($headers['Content-Length']) ? intval($headers['Content-Length']) : 0;
        if ($content_length < 10000) { // 10KB 미만
            return array('safe' => false, 'reason' => '파일 크기가 너무 작음');
        }
        if ($content_length > 10485760) { // 10MB 초과
            return array('safe' => false, 'reason' => '파일 크기가 너무 큼');
        }
        
        return array('safe' => true, 'size' => $content_length, 'type' => $content_type);
    }
    
    /**
     * 최종 이미지 선택 (안전성 + 품질 고려)
     */
    public function select_best_images($images, $options = array()) {
        $options = wp_parse_args($options, array(
            'max_images' => 5,
            'min_safety_score' => 60,
            'verify_metadata' => true,
            'place_type' => 'general'
        ));
        
        // 1단계: 안전성 필터링
        $safe_images = $this->filter_safe_images($images, $options['min_safety_score']);
        
        // 2단계: 장소 타입별 우선순위 적용
        $prioritized = $this->prioritize_by_type($safe_images, $options['place_type']);
        
        // 3단계: 메타데이터 검증 (선택사항)
        if ($options['verify_metadata']) {
            $verified = array();
            foreach ($prioritized as $image) {
                $metadata = $this->verify_image_metadata($image['url']);
                if ($metadata['safe']) {
                    $image['metadata'] = $metadata;
                    $verified[] = $image;
                }
                
                // 충분한 이미지를 찾았으면 중단
                if (count($verified) >= $options['max_images']) {
                    break;
                }
            }
            $prioritized = $verified;
        }
        
        // 4단계: 최종 선택
        return array_slice($prioritized, 0, $options['max_images']);
    }
}

// 전역 함수
function copyright_safe_filter() {
    return Copyright_Safe_Image_Filter::get_instance();
}
