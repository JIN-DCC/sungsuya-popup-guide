<?php
/**
 * 이미지 크롤링 모듈 (Phase 3 최적화)
 * 
 * 통합 이미지 크롤러 및 관련 기능들을 관리자에서만 로딩
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 이미지 크롤링 관련 모듈 로드
 */
function sungsuya_load_image_crawling_modules() {
    // 통합 이미지 크롤러 관리자
    if (file_exists(SUNGSUYA_THEME_DIR . '/inc/integrated-image-crawler/admin/integrated-image-crawler-admin.php')) {
        require_once SUNGSUYA_THEME_DIR . '/inc/integrated-image-crawler/admin/integrated-image-crawler-admin.php';
    }
    
    // 이미지 크롤링 시스템 (있다면)
    if (file_exists(SUNGSUYA_THEME_DIR . '/inc/image-crawling-system.php')) {
        require_once SUNGSUYA_THEME_DIR . '/inc/image-crawling-system.php';
    }
    
    // 정적지도 자동 생성 (관리자에서만 필요)
    if (file_exists(SUNGSUYA_THEME_DIR . '/inc/helpers/static-map-auto-generator.php')) {
        require_once SUNGSUYA_THEME_DIR . '/inc/helpers/static-map-auto-generator.php';
    }
}

// 이미지 크롤링 모듈 로드
sungsuya_load_image_crawling_modules();
