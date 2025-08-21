<?php
/**
 * 크롤링 시스템 핵심 모듈 (Phase 3 최적화)
 * 
 * 관리자에서만 필요한 크롤링 기능들을 조건부 로딩
 * 프론트엔드 성능 향상을 위한 모듈화
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 크롤링 시스템 초기화
 * 관리자이면서 필요한 권한이 있을 때만 실행
 */
function sungsuya_init_crawling_system() {
    // 관리자가 아니거나 권한이 없으면 로딩하지 않음
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }
    
    // 크롤링 관련 페이지에서만 로딩 (추가 최적화)
    $current_page = isset($_GET['page']) ? $_GET['page'] : '';
    $crawling_pages = array('crawling', 'bulk-crawling', 'image-crawling', 'geocoding');
    
    $should_load = false;
    foreach ($crawling_pages as $page) {
        if (strpos($current_page, $page) !== false) {
            $should_load = true;
            break;
        }
    }
    
    // 크롤링 관련 페이지이거나 AJAX 요청일 때만 로딩
    if ($should_load || wp_doing_ajax()) {
        // 네이버 크롤링 모듈
        require_once __DIR__ . '/naver-crawling.php';
        
        // 이미지 크롤링 모듈
        require_once __DIR__ . '/image-crawling.php';
        
        // 지오코딩 모듈
        require_once __DIR__ . '/geocoding.php';
        
        // 통합 크롤링 관리
        require_once __DIR__ . '/crawling-admin.php';
    }
}

// 크롤링 시스템 초기화
add_action('admin_init', 'sungsuya_init_crawling_system');
add_action('wp_ajax_sungsuya_crawling', 'sungsuya_init_crawling_system');
add_action('wp_ajax_nopriv_sungsuya_crawling', 'sungsuya_init_crawling_system');
