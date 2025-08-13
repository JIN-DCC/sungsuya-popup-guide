<?php
/**
 * 통합 이미지 크롤러 팩토리
 * 
 * 3가지 크롤링 모드를 통합 관리
 * 
 * @package SungsuyaV2
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// 기존 크롤러들 로드
require_once SUNGSUYA_THEME_DIR . '/inc/image-crawling-system.php';
require_once SUNGSUYA_THEME_DIR . '/inc/accurate-image-crawler.php';
require_once SUNGSUYA_THEME_DIR . '/inc/copyright-safe-image-crawler.php';

// 저작권 필터 로드
require_once dirname(__FILE__) . '/class-copyright-safe-filter.php';

/**
 * 통합 이미지 크롤러 팩토리
 */
class Integrated_Image_Crawler_Factory {
    
    /**
     * 싱글톤 인스턴스
     */
    private static $instance = null;
    
    /**
     * 크롤링 모드 상수
     */
    const MODE_BASIC = 'basic';
    const MODE_ACCURATE = 'accurate';
    const MODE_PREMIUM = 'premium';
    
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
     * 생성자
     */
    private function __construct() {
        // 싱글톤 패턴
    }
    
    /**
     * 크롤러 인스턴스 생성
     * 
     * @param string $mode 크롤링 모드
     * @return object 크롤러 인스턴스
     */
    public function create_crawler($mode = self::MODE_ACCURATE) {
        switch ($mode) {
            case self::MODE_BASIC:
                return Image_Crawling_System::get_instance();
                
            case self::MODE_ACCURATE:
                return Accurate_Image_Crawler::get_instance();
                
            case self::MODE_PREMIUM:
                return Copyright_Safe_Image_Crawler::get_instance();
                
            default:
                return Accurate_Image_Crawler::get_instance();
        }
    }
    
    /**
     * 통합 크롤링 실행
     * 
     * @param int $post_id 장소 ID
     * @param array $options 크롤링 옵션
     * @return array 크롤링 결과
     */
    public function crawl($post_id, $options = array()) {
        $default_options = array(
            'mode' => self::MODE_ACCURATE,
            'max_images' => 5,
            'min_width' => 400,
            'min_height' => 300,
            'use_cache' => true,
            'save_to_media' => true,
            'set_featured' => true,
            'copyright_filter' => false  // 저작권 필터 옵션 추가
        );
        
        $options = wp_parse_args($options, $default_options);
        
        // 크롤러 인스턴스 생성
        $crawler = $this->create_crawler($options['mode']);
        
        // 모드별 크롤링 실행
        switch ($options['mode']) {
            case self::MODE_BASIC:
                return $this->crawl_basic($crawler, $post_id, $options);
                
            case self::MODE_ACCURATE:
                return $this->crawl_accurate($crawler, $post_id, $options);
                
            case self::MODE_PREMIUM:
                return $this->crawl_premium($crawler, $post_id, $options);
                
            default:
                return array('success' => false, 'message' => '알 수 없는 크롤링 모드');
        }
    }
    
    /**
     * 기본 모드 크롤링
     */
    private function crawl_basic($crawler, $post_id, $options) {
        try {
            $post = get_post($post_id);
            if (!$post) {
                return array('success' => false, 'message' => '장소를 찾을 수 없습니다.');
            }
            
            // 기본 크롤러는 메서드가 다름
            $result = $crawler->crawl_and_save_images(
                $post->post_title,
                $post_id,
                $options['max_images']
            );
            
            // 결과가 배열이 아니면 빈 배열로 처리
            if (!is_array($result)) {
                $result = array();
            }
            
            return array(
                'success' => !empty($result),
                'images' => $result,
                'mode' => 'basic',
                'message' => !empty($result) ? count($result) . '개 이미지 수집 완료' : '이미지를 찾을 수 없습니다.'
            );
        } catch (Exception $e) {
            error_log('Basic crawl error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => '기본 모드 크롤링 중 오류: ' . $e->getMessage(),
                'mode' => 'basic'
            );
        }
    }
    
    /**
     * 정확 모드 크롤링
     */
    private function crawl_accurate($crawler, $post_id, $options) {
        try {
            error_log('Accurate crawl start - Post ID: ' . $post_id);
            
            $result = $crawler->crawl_place_images($post_id, array(
                'max_images' => $options['max_images'],
                'min_score' => 5,
                'use_cache' => $options['use_cache']
            ));
            
            error_log('Accurate crawl result count: ' . (is_array($result) ? count($result) : 'not array'));
            
            // 저작권 필터 적용
            if ($options['copyright_filter'] && !empty($result)) {
                $copyright_filter = copyright_safe_filter();
                
                // 장소 타입 가져오기
                $place_type = 'general';
                $terms = wp_get_post_terms($post_id, 'place_type');
                if (!empty($terms) && !is_wp_error($terms)) {
                    $place_type = $terms[0]->slug;
                }
                
                // 저작권 안전 필터링
                $result = $copyright_filter->select_best_images($result, array(
                    'max_images' => $options['max_images'],
                    'min_safety_score' => 60,
                    'verify_metadata' => false,  // 속도를 위해 메타데이터 검증은 생략
                    'place_type' => $place_type
                ));
                
                error_log('After copyright filter: ' . count($result) . ' images');
            }
            
            if (!empty($result) && $options['save_to_media']) {
                $saved_images = array();
                foreach ($result as $index => $image) {
                    try {
                        // 이미지 다운로드 및 저장
                        $attachment_id = $this->download_and_save_image(
                            $image['url'],
                            $post_id,
                            $image['title'] ?? ''
                        );
                        
                        if ($attachment_id && $index === 0 && $options['set_featured']) {
                            set_post_thumbnail($post_id, $attachment_id);
                        }
                        
                        if ($attachment_id) {
                            $saved_images[] = array(
                                'id' => $attachment_id,
                                'url' => wp_get_attachment_url($attachment_id),
                                'title' => $image['title'] ?? '',
                                'score' => $image['score'] ?? 0,
                                'safety_score' => $image['safety_score'] ?? 0
                            );
                        }
                    } catch (Exception $e) {
                        error_log('Error saving image: ' . $e->getMessage());
                    }
                }
                
                return array(
                    'success' => !empty($saved_images),
                    'images' => $saved_images,
                    'mode' => 'accurate',
                    'message' => count($saved_images) . '개 이미지 저장 완료 (정확도 우선)'
                );
            }
            
            return array(
                'success' => !empty($result),
                'images' => $result,
                'mode' => 'accurate',
                'message' => !empty($result) ? count($result) . '개 이미지 발견' : '이미지를 찾을 수 없습니다.'
            );
        } catch (Exception $e) {
            error_log('Accurate crawl error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return array(
                'success' => false,
                'message' => '정확 모드 크롤링 중 오류: ' . $e->getMessage(),
                'mode' => 'accurate'
            );
        }
    }
    
    /**
     * 이미지 다운로드 및 저장
     */
    private function download_and_save_image($image_url, $post_id, $title = '') {
        try {
            // WordPress 함수 로드
            if (!function_exists('media_sideload_image')) {
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
            }
            
            // 이미지 다운로드
            $attachment_id = media_sideload_image($image_url, $post_id, $title, 'id');
            
            if (is_wp_error($attachment_id)) {
                error_log('Image download error: ' . $attachment_id->get_error_message());
                return false;
            }
            
            return $attachment_id;
        } catch (Exception $e) {
            error_log('Download image error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 프리미엄 모드 크롤링
     */
    private function crawl_premium($crawler, $post_id, $options) {
        // Google Places API 키 확인
        $api_key = get_option('google_places_api_key');
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => 'Google Places API 키가 설정되지 않았습니다.',
                'mode' => 'premium'
            );
        }
        
        $result = $crawler->crawl_safe_images($post_id, array(
            'max_images' => $options['max_images'],
            'types' => array('exterior', 'interior', 'menu', 'signage'),
            'save_to_media' => $options['save_to_media'],
            'set_featured' => $options['set_featured']
        ));
        
        if ($result['success']) {
            return array(
                'success' => true,
                'images' => $result['images'],
                'mode' => 'premium',
                'message' => $result['message'],
                'place_id' => $result['place_id'] ?? null
            );
        }
        
        return array(
            'success' => false,
            'message' => $result['message'],
            'mode' => 'premium'
        );
    }
    
    /**
     * 크롤링 모드 정보 반환
     */
    public function get_mode_info($mode = null) {
        $modes = array(
            self::MODE_BASIC => array(
                'name' => '기본 모드',
                'icon' => '⚡',
                'description' => '구글 이미지 검색을 통한 빠른 크롤링',
                'pros' => array(
                    '빠른 속도',
                    '다양한 이미지',
                    'API 키 불필요'
                ),
                'cons' => array(
                    '정확도 낮음',
                    '저작권 주의 필요',
                    '관련 없는 이미지 포함 가능'
                )
            ),
            self::MODE_ACCURATE => array(
                'name' => '정확 모드',
                'icon' => '🎯',
                'description' => '3단계 검색 전략으로 정확한 이미지만 수집',
                'pros' => array(
                    '높은 정확도',
                    '주소 기반 매칭',
                    '공식 채널 우선'
                ),
                'cons' => array(
                    '속도 보통',
                    '이미지 수 제한적'
                ),
                'recommended' => true
            ),
            self::MODE_PREMIUM => array(
                'name' => '프리미엄 모드',
                'icon' => '💎',
                'description' => 'Google Places API를 통한 공식 이미지 수집',
                'pros' => array(
                    '100% 공식 이미지',
                    '저작권 안전',
                    '고품질 이미지',
                    '메타데이터 포함'
                ),
                'cons' => array(
                    'API 키 필요',
                    '사용량 제한',
                    '비용 발생 가능'
                ),
                'requires_api' => true
            )
        );
        
        if ($mode) {
            return isset($modes[$mode]) ? $modes[$mode] : null;
        }
        
        return $modes;
    }
    
    /**
     * 테스트 크롤링 (미리보기)
     */
    public function test_crawl($post_id, $mode = self::MODE_ACCURATE) {
        try {
            $crawler = $this->create_crawler($mode);
            
            switch ($mode) {
                case self::MODE_BASIC:
                    $post = get_post($post_id);
                    if (!$post) {
                        error_log('Test crawl: Post not found - ID: ' . $post_id);
                        return array();
                    }
                    
                    // 기본 크롤러는 테스트 메서드가 없으므로 실제 크롤링 후 결과만 반환
                    $images = $crawler->search_images($post->post_title . ' 성수동', 5);
                    return array_map(function($img) {
                        return array(
                            'url' => $img,
                            'title' => '',
                            'score' => 0,
                            'source' => 'Google Images'
                        );
                    }, $images);
                    
                case self::MODE_ACCURATE:
                    // 정확 모드는 캐시 없이 테스트
                    $result = $crawler->crawl_place_images($post_id, array(
                        'max_images' => 3,
                        'use_cache' => false
                    ));
                    error_log('Accurate mode test result: ' . print_r($result, true));
                    return $result;
                    
                case self::MODE_PREMIUM:
                    // 프리미엄 모드는 API 호출 없이 시뮬레이션
                    if (method_exists($crawler, 'preview_safe_images')) {
                        return $crawler->preview_safe_images($post_id);
                    } else {
                        error_log('Premium crawler missing preview_safe_images method');
                        // 메서드가 없으면 기본 정보 반환
                        return array(
                            array(
                                'url' => 'https://via.placeholder.com/400x300?text=Premium+Mode',
                                'title' => '프리미엄 모드 미리보기',
                                'score' => 10,
                                'source' => 'Google Places API'
                            )
                        );
                    }
                    
                default:
                    error_log('Unknown crawling mode: ' . $mode);
                    return array();
            }
        } catch (Exception $e) {
            error_log('Test crawl error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return array();
        }
    }
}

// 전역 함수 제공
function integrated_image_crawler() {
    return Integrated_Image_Crawler_Factory::get_instance();
}
