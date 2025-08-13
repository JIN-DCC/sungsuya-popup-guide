<?php
/**
 * 크롤링 관리자 모듈 (Phase 3 최적화)
 * 
 * 관리자 인터페이스에서만 필요한 크롤링 관련 기능들
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 크롤링 관리자 기능 로드
 * admin-optimized에서 이미 로딩되는 것들과 중복되지 않도록 주의
 */
function sungsuya_load_crawling_admin_modules() {
    // 이미 admin-optimized에서 로딩되는지 확인
    if (function_exists('sungsuya_admin_crawling_loaded')) {
        return; // 이미 로딩됨
    }
    
    // 크롤링 관련 AJAX 핸들러들이 포함된 모듈들
    $crawling_admin_files = array(
        // 네이버 검색 API 클라이언트 (AJAX 핸들러 포함)
        'crawling/api-clients/class-naver-search-api-client.php',
        
        // 메타필드 REST API 등록
        'fix-bulk-crawling-meta/register-meta-fields-for-rest.php',
    );
    
    foreach ($crawling_admin_files as $file) {
        $file_path = SUNGSUYA_THEME_DIR . '/inc/' . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
    
    // 크롤링 로딩 완료 표시
    function sungsuya_admin_crawling_loaded() {
        return true;
    }
}

// 크롤링 관리자 모듈 로드
sungsuya_load_crawling_admin_modules();
