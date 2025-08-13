<?php
/**
 * 테마 기본 설정
 * 
 * @package SungsuyaV2
 * @subpackage Core
 * @since 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 테마 기본 설정
 */
function sungsuya_setup() {
    // 테마 지원 기능 추가
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption'
    ));
    
    // PWA 관련 메타 태그 지원
    add_theme_support('custom-logo', array(
        'height'      => 512,
        'width'       => 512,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    
    // 이미지 크기 등록
    add_image_size('store-card', 300, 200, true);
    add_image_size('store-hero', 600, 400, true);
}
add_action('after_setup_theme', 'sungsuya_setup');

/**
 * 성능 최적화 및 정리
 */
function sungsuya_cleanup() {
    // 불필요한 헤더 정보 제거
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    
    // 이모지 스크립트 제거 (번들 크기 최적화)
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    
    // REST API 링크 제거 (필요한 경우만 유지)
    remove_action('wp_head', 'rest_output_link_wp_head');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
}
add_action('init', 'sungsuya_cleanup');

/**
 * 워드프레스 관리자 바 숨김 (프론트엔드에서)
 */
if (!is_admin()) {
    add_filter('show_admin_bar', '__return_false');
}

/**
 * 페이지별 body 클래스 추가
 */
function sungsuya_body_classes($classes) {
    if (is_singular('popup_store')) {
        $classes[] = 'popup-store-detail-page';
        $classes[] = 'has-enhanced-detail';
    }
    
    // PWA 바텀시트 투어플래너 클래스 추가
    if (is_page_template('page-tour-planner-pwa.php')) {
        $classes[] = 'pwa-tour-planner';
        $classes[] = 'fullscreen-mode';
    }
    
    return $classes;
}
add_filter('body_class', 'sungsuya_body_classes');

/**
 * 이미지 최적화
 */
function sungsuya_add_image_attributes($attr, $attachment) {
    // 지연 로딩 추가
    if (!is_admin()) {
        $attr['loading'] = 'lazy';
    }
    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'sungsuya_add_image_attributes', 10, 2);

/**
 * Permalink 규칙 재설정 (필요 시)
 */
function sungsuya_force_flush_rewrites() {
    if (isset($_GET['flush_rewrites']) && current_user_can('manage_options')) {
        flush_rewrite_rules();
        wp_redirect(admin_url('edit.php?post_type=popup_store&flushed=1'));
        exit;
    }
}
add_action('admin_init', 'sungsuya_force_flush_rewrites');

/**
 * 캐시 최적화 (개발 시에는 비활성화)
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    function sungsuya_add_no_cache_headers() {
        if (!is_admin()) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
    add_action('send_headers', 'sungsuya_add_no_cache_headers');
}

/**
 * 에러 로깅 (개발 시)
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    function sungsuya_log_errors($errno, $errstr, $errfile, $errline) {
        if (error_reporting() & $errno) {
            error_log("Error: [$errno] $errstr in $errfile on line $errline");
        }
    }
    set_error_handler('sungsuya_log_errors');
}
