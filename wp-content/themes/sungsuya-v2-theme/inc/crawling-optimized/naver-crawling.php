<?php
/**
 * 네이버 크롤링 모듈 (Phase 3 최적화)
 * 
 * 네이버 API 관련 크롤링 기능들을 관리자에서만 로딩
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 네이버 크롤링 관련 모듈 로드
 */
function sungsuya_load_naver_crawling_modules() {
    // 네이버 지도 설정
    if (file_exists(SUNGSUYA_THEME_DIR . '/inc/naver-maps-settings.php')) {
        require_once SUNGSUYA_THEME_DIR . '/inc/naver-maps-settings.php';
    }
    
    // 지오코딩 매니저
    if (file_exists(SUNGSUYA_THEME_DIR . '/inc/geocoding-manager.php')) {
        require_once SUNGSUYA_THEME_DIR . '/inc/geocoding-manager.php';
    }
    
    // 네이버 정적 지도 생성기
    if (file_exists(SUNGSUYA_THEME_DIR . '/inc/naver-static-map-generator.php')) {
        require_once SUNGSUYA_THEME_DIR . '/inc/naver-static-map-generator.php';
    }
    
    // 정적 지도 줌 핸들러
    if (file_exists(SUNGSUYA_THEME_DIR . '/inc/static-map-zoom-handler.php')) {
        require_once SUNGSUYA_THEME_DIR . '/inc/static-map-zoom-handler.php';
    }
    
    // 네이버 검색 API 클라이언트
    if (file_exists(SUNGSUYA_THEME_DIR . '/inc/crawling/api-clients/class-naver-search-api-client.php')) {
        require_once SUNGSUYA_THEME_DIR . '/inc/crawling/api-clients/class-naver-search-api-client.php';
    }
}

// 네이버 크롤링 모듈 로드
sungsuya_load_naver_crawling_modules();
