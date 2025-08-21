<?php
/**
 * 관리자 접근 제어 및 리디렉션
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
 * WordPress 기본 관리자 리디렉션 강제 복구
 */
function sungsuya_force_admin_redirect() {
    // URL이 /wp-admin/로 시작하고 로그인되지 않은 경우
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    
    if (strpos($request_uri, '/wp-admin/') === 0 && !is_user_logged_in() && !wp_doing_ajax()) {
        // AJAX나 특수 요청 제외
        if (strpos($request_uri, 'admin-ajax.php') !== false || 
            strpos($request_uri, 'admin-post.php') !== false) {
            return;
        }
        
        // 현재 요청 URL을 redirect_to 파라미터로 설정
        $redirect_to = home_url($request_uri);
        $login_url = wp_login_url($redirect_to);
        
        // 리디렉션 실행
        wp_redirect($login_url, 302);
        exit;
    }
}
add_action('init', 'sungsuya_force_admin_redirect', 1);

/**
 * 관리자 페이지 자동 리디렉션 수정
 */
function sungsuya_fix_admin_redirect() {
    // 비로그인 사용자가 관리자 페이지에 접근할 때 로그인 페이지로 리디렉션
    if (is_admin() && !is_user_logged_in() && !wp_doing_ajax()) {
        // 현재 URL 가져오기
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        // 로그인 후 돌아갈 URL 설정
        $redirect_to = admin_url();
        if (isset($_GET['page'])) {
            $redirect_to = admin_url('admin.php?page=' . sanitize_text_field($_GET['page']));
        } elseif (strpos($_SERVER['REQUEST_URI'], 'post.php') !== false) {
            $redirect_to = $current_url;
        }
        
        // 로그인 페이지로 리디렉션
        $login_url = wp_login_url($redirect_to);
        wp_redirect($login_url);
        exit;
    }
}
add_action('admin_init', 'sungsuya_fix_admin_redirect');

/**
 * 관리자 바 비로그인 사용자 숨김 설정 수정
 */
function sungsuya_show_admin_bar_for_logged_in_users($show_admin_bar) {
    // 로그인된 사용자에게만 관리자 바 표시
    return is_user_logged_in();
}
add_filter('show_admin_bar', 'sungsuya_show_admin_bar_for_logged_in_users');

/**
 * 관리자 로그인 편의 링크 추가 (개발용) - 임시 비활성화
 */
function sungsuya_add_admin_login_link() {
    // 사용자 요청으로 임시 비활성화
    // 필요시 아래 주석을 해제하고 WP_DEBUG가 true일 때만 표시
    /*
    if (defined('WP_DEBUG') && WP_DEBUG && !is_user_logged_in() && !is_admin()) {
        echo '<div style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">';
        echo '<a href="' . wp_login_url(admin_url()) . '" style="';
        echo 'background: #0073aa; color: white; padding: 10px 15px; border-radius: 5px; ';
        echo 'text-decoration: none; font-weight: bold; box-shadow: 0 2px 10px rgba(0,0,0,0.3);';
        echo '" title="관리자 로그인">🔧 관리자</a>';
        echo '</div>';
    }
    */
}
add_action('wp_footer', 'sungsuya_add_admin_login_link');

/**
 * 관리자 영역에서만 필요한 스크립트 제한
 */
function sungsuya_dequeue_admin_scripts() {
    if (!is_admin()) {
        wp_dequeue_style('dashicons');
    }
}
add_action('wp_enqueue_scripts', 'sungsuya_dequeue_admin_scripts');
