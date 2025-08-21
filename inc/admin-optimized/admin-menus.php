<?php
/**
 * 관리자 메뉴 및 페이지 관리
 * Phase 2: 관리자 기능 조건부 로딩
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 관리자 메뉴 등록
 */
function sungsuya_admin_menu() {
    // Enhanced 크롤링 메뉴는 작동하지 않으므로 제거됨
}
add_action('admin_menu', 'sungsuya_admin_menu');

/**
 * 정적지도 관리 메뉴 (지도 생성 다음에 추가)
 */
function sungsuya_add_static_map_menu() {
    add_submenu_page(
        'edit.php?post_type=places',
        '정적지도 관리',
        '🗺️ 정적지도 관리',
        'manage_options',
        'static-map-manager',
        'sungsuya_static_map_admin_page'
    );
}
add_action('admin_menu', 'sungsuya_add_static_map_menu', 25);

/**
 * API 설정 메뉴 (통합 관리)
 */
function sungsuya_add_api_settings_menu() {
    // 소셜 로그인 API 설정만 Places 메뉴에 유지
    add_submenu_page(
        'edit.php?post_type=places',
        '소셜 로그인 API',
        '🔐 소셜 로그인 API',
        'manage_options',
        'auth-settings',
        'sungsuya_auth_settings_page'
    );
}
add_action('admin_menu', 'sungsuya_add_api_settings_menu', 100);

// 관리자 메뉴에 리뷰 관리 추가
function sungsuya_add_review_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=places',
        '리뷰 관리',
        '💬 리뷰 관리',
        'manage_options',
        'place-reviews',
        'sungsuya_review_admin_page'
    );
    
    add_submenu_page(
        'edit.php?post_type=places',
        '스팸 관리',
        '🚫 스팸 관리',
        'manage_options',
        'spam-management',
        'sungsuya_spam_management_page'
    );
    
    // 회원 관리 메뉴 추가
    add_submenu_page(
        'edit.php?post_type=places',
        '회원 관리',
        '👥 회원 관리',
        'manage_options',
        'member-management',
        'sungsuya_member_management_page'
    );
}
add_action('admin_menu', 'sungsuya_add_review_admin_menu', 30);

// API 설정 페이지 콜백
function sungsuya_crawling_api_settings_page() {
    require_once SUNGSUYA_THEME_DIR . '/admin/crawling-api-settings.php';
}

// 인증 설정 페이지 콜백
function sungsuya_auth_settings_page() {
    require_once SUNGSUYA_THEME_DIR . '/admin/auth-settings.php';
}

// 팝업스토어 CSV 업로드 페이지 콜백
function sungsuya_popup_csv_upload_page() {
    require_once SUNGSUYA_THEME_DIR . '/admin/popup-store-csv-upload.php';
}

// 리뷰 관리 페이지 콜백
function sungsuya_review_admin_page() {
    require_once SUNGSUYA_THEME_DIR . '/admin/review-management.php';
}

// 스팸 관리 페이지 콜백
function sungsuya_spam_management_page() {
    require_once SUNGSUYA_THEME_DIR . '/admin/spam-management.php';
}

// 회원 관리 페이지 콜백
function sungsuya_member_management_page() {
    require_once SUNGSUYA_THEME_DIR . '/admin/member-management.php';
}
