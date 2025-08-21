<?php
/**
 * AJAX 핸들러
 * 
 * @package SungsuyaV2
 * @subpackage API
 * @since 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX 핸들러 등록
 */
function sungsuya_ajax_handlers() {
    // 북마크 토글
    add_action('wp_ajax_toggle_bookmark', 'sungsuya_ajax_toggle_bookmark');
    add_action('wp_ajax_nopriv_toggle_bookmark', 'sungsuya_ajax_toggle_bookmark');
    
    // 스토어 뷰 카운트
    add_action('wp_ajax_increment_store_views', 'sungsuya_ajax_increment_store_views');
    add_action('wp_ajax_nopriv_increment_store_views', 'sungsuya_ajax_increment_store_views');
}
add_action('init', 'sungsuya_ajax_handlers');

/**
 * 북마크 토글 AJAX 핸들러
 */
function sungsuya_ajax_toggle_bookmark() {
    check_ajax_referer('wp_rest', 'nonce');
    
    $store_id = intval($_POST['store_id']);
    if (!$store_id) {
        wp_die('Invalid store ID');
    }
    
    // 사용자별 북마크는 향후 구현
    wp_send_json_success(array(
        'message' => '북마크 기능은 곧 업데이트될 예정입니다.',
        'bookmarked' => false
    ));
}

/**
 * 스토어 조회수 증가 AJAX 핸들러
 */
function sungsuya_ajax_increment_store_views() {
    check_ajax_referer('wp_rest', 'nonce');
    
    $store_id = intval($_POST['store_id']);
    if (!$store_id) {
        wp_die('Invalid store ID');
    }
    
    $views = get_post_meta($store_id, '_store_views', true);
    $views = $views ? intval($views) + 1 : 1;
    update_post_meta($store_id, '_store_views', $views);
    
    wp_send_json_success(array(
        'views' => $views
    ));
}

/**
 * Places 시스템용 AJAX 핸들러
 */
function sungsuya_places_ajax_handlers() {
    // Places 시스템은 geocoding-manager.php의 핸들러를 재사용
}
add_action('init', 'sungsuya_places_ajax_handlers');
