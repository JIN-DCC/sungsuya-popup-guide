<?php
/**
 * 지오코딩 모듈 (Phase 3 최적화)
 * 
 * 좌표 생성 및 지오코딩 관련 기능들을 스마트하게 로딩
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 지오코딩 관련 모듈 로드
 * 프론트엔드에서도 필요한 기능은 조건부로 로딩
 */
function sungsuya_load_geocoding_modules() {
    // 관리자에서만 로딩할 모듈들
    if (is_admin()) {
        // 지오코딩 매니저 (관리자 전용)
        if (file_exists(SUNGSUYA_THEME_DIR . '/inc/geocoding-manager.php')) {
            require_once SUNGSUYA_THEME_DIR . '/inc/geocoding-manager.php';
        }
        
        // 네이버 고급 메타박스 (관리자 전용)
        if (file_exists(SUNGSUYA_THEME_DIR . '/inc/naver-enhanced-metabox.php')) {
            require_once SUNGSUYA_THEME_DIR . '/inc/naver-enhanced-metabox.php';
        }
    }
    
    // 프론트엔드에서도 필요한 기능들 (조건부)
    if (is_singular('places') || is_post_type_archive('places') || wp_doing_ajax()) {
        // 네이버 지도 설정 (지도 표시용)
        if (file_exists(SUNGSUYA_THEME_DIR . '/inc/naver-maps-settings.php')) {
            require_once SUNGSUYA_THEME_DIR . '/inc/naver-maps-settings.php';
        }
        
        // 정적 지도 생성기 (지도 이미지 생성용)
        if (file_exists(SUNGSUYA_THEME_DIR . '/inc/naver-static-map-generator.php')) {
            require_once SUNGSUYA_THEME_DIR . '/inc/naver-static-map-generator.php';
        }
        
        // 정적 지도 줌 핸들러 (지도 상호작용용)
        if (file_exists(SUNGSUYA_THEME_DIR . '/inc/static-map-zoom-handler.php')) {
            require_once SUNGSUYA_THEME_DIR . '/inc/static-map-zoom-handler.php';
        }
    }
}

// 지오코딩 모듈 로드
sungsuya_load_geocoding_modules();
