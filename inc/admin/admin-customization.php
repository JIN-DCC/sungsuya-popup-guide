<?php
/**
 * 관리자 UI 커스터마이징
 * 
 * @package SungsuyaV2
 * @subpackage Admin
 * @since 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 관리자 메뉴 정리 및 불필요한 메뉴 숨김
 */
function sungsuya_remove_admin_menus() {
    // 관리자가 아닌 사용자들에게는 더 많은 메뉴 숨김
    if (!current_user_can('administrator')) {
        remove_menu_page('edit-comments.php');        // 댓글
        remove_menu_page('themes.php');               // 외모
        remove_menu_page('plugins.php');              // 플러그인
        remove_menu_page('users.php');                // 사용자
        remove_menu_page('tools.php');                // 도구
        remove_menu_page('options-general.php');      // 설정
    }
    
    // 모든 사용자에게 불필요한 메뉴 숨김
    remove_menu_page('edit-comments.php');           // 댓글 (사용하지 않음)
    
    // 서브메뉴도 정리
    remove_submenu_page('themes.php', 'theme-editor.php');  // 테마 편집기
    remove_submenu_page('plugins.php', 'plugin-editor.php'); // 플러그인 편집기
    
    // 불필요한 도구 메뉴들
    remove_submenu_page('tools.php', 'import.php');
    remove_submenu_page('tools.php', 'export.php');
    
    // Places만 주로 사용하므로 기본 포스트 메뉴 정리
    if (current_user_can('administrator')) {
        // 관리자에게는 표시하되 이름 변경
        global $menu;
        foreach ($menu as $key => $value) {
            if ($value[2] == 'edit.php') {
                $menu[$key][0] = '📝 일반 포스트 (사용안함)';
                break;
            }
        }
    } else {
        // 일반 사용자에게는 숨김
        remove_menu_page('edit.php');                // 포스트
    }
    
    // 페이지 메뉴도 정리
    global $menu;
    foreach ($menu as $key => $value) {
        if (isset($value[2]) && $value[2] == 'edit.php?post_type=page') {
            $menu[$key][0] = '📄 페이지';
            break;
        }
    }
}
add_action('admin_menu', 'sungsuya_remove_admin_menus', 999);

/**
 * 관리자 바 정리
 */
function sungsuya_remove_admin_bar_menus() {
    global $wp_admin_bar;
    
    // 불필요한 관리자 바 메뉴 제거
    $wp_admin_bar->remove_menu('comments');          // 댓글
    $wp_admin_bar->remove_menu('new-content');       // 새로 추가
    $wp_admin_bar->remove_menu('wp-logo');           // WordPress 로고
    $wp_admin_bar->remove_menu('about');             // WordPress 정보
    $wp_admin_bar->remove_menu('wporg');             // WordPress.org
    $wp_admin_bar->remove_menu('documentation');     // 문서
    $wp_admin_bar->remove_menu('support-forums');    // 지원 포럼
    $wp_admin_bar->remove_menu('feedback');          // 피드백
    
    // 유용한 메뉴는 이름 변경
    $wp_admin_bar->add_menu(array(
        'id' => 'places-quick',
        'title' => '🗺️ Places 관리',
        'href' => admin_url('edit.php?post_type=places'),
        'meta' => array('title' => 'Places 목록으로 빠르게 이동')
    ));
    
    $wp_admin_bar->add_menu(array(
        'id' => 'map-system-quick',
        'title' => '🚀 지도 생성',
        'href' => admin_url('edit.php?post_type=places&page=integrated-map-generation'),
        'meta' => array('title' => '통합 지도생성 시스템으로 빠르게 이동')
    ));
}
add_action('wp_before_admin_bar_render', 'sungsuya_remove_admin_bar_menus');

/**
 * 대시보드 위젯 정리
 */
function sungsuya_remove_dashboard_widgets() {
    // 불필요한 대시보드 위젯 제거
    remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
    remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_secondary', 'dashboard', 'normal');
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');
    
    // 성수야 전용 대시보드 위젯 추가
    wp_add_dashboard_widget(
        'sungsuya_dashboard_widget',
        '🗺️ 성수야! 관리 대시보드',
        'sungsuya_dashboard_widget_content'
    );
}
add_action('wp_dashboard_setup', 'sungsuya_remove_dashboard_widgets');

/**
 * 성수야 전용 대시보드 위젯 내용
 */
function sungsuya_dashboard_widget_content() {
    // Places 통계
    $places_count = wp_count_posts('places');
    $published_places = $places_count->publish ?? 0;
    
    // 좌표 미확정 Places 개수
    $places_without_coords = new WP_Query([
        'post_type' => 'places',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => 'latitude',
                'compare' => 'NOT EXISTS'
            ],
            [
                'key' => 'latitude',
                'value' => '',
                'compare' => '='
            ]
        ]
    ]);
    
    echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">';
    
    // Places 현황
    echo '<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #2563eb;">';
    echo '<h4 style="margin: 0 0 10px 0; color: #2563eb;">📍 Places 현황</h4>';
    echo '<p style="margin: 5px 0;"><strong>전체:</strong> ' . $published_places . '개</p>';
    echo '<p style="margin: 5px 0;"><strong>좌표 미확정:</strong> ' . $places_without_coords->found_posts . '개</p>';
    echo '<a href="' . admin_url('edit.php?post_type=places') . '" class="button button-primary" style="margin-top: 10px;">Places 관리</a>';
    echo '</div>';
    
    // 통합 지도생성 시스템
    echo '<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #16a34a;">';
    echo '<h4 style="margin: 0 0 10px 0; color: #16a34a;">🚀 지도 생성 시스템</h4>';
    if ($places_without_coords->found_posts > 0) {
        echo '<p style="color: #dc2626;">좌표 생성이 필요한 Places가 있습니다.</p>';
        echo '<a href="' . admin_url('edit.php?post_type=places&page=integrated-map-generation') . '" class="button button-secondary">지도 생성하기</a>';
    } else {
        echo '<p style="color: #16a34a;">모든 Places의 좌표가 생성되었습니다!</p>';
        echo '<a href="' . admin_url('edit.php?post_type=places&page=integrated-map-generation') . '" class="button button-secondary">시스템 확인</a>';
    }
    echo '</div>';
    
    echo '</div>';
}

/**
 * 관리자 푸터 텍스트 커스터마이징
 */
function sungsuya_admin_footer_text() {
    return '<span style="color: #2563eb;">🗺️ <strong>성수야!</strong> 관리자 대시보드 | 통합 지도생성 시스템 운영 중</span>';
}
add_filter('admin_footer_text', 'sungsuya_admin_footer_text');

/**
 * 관리자 헤더 정리
 */
function sungsuya_admin_head() {
    echo '<style>
        /* 관리자 스타일 개선 */
        #wpadminbar .ab-top-menu > li.hover > .ab-item {
            background: #2563eb !important;
        }
        
        .wrap h1 {
            color: #2563eb;
        }
        
        /* Places 관련 메뉴 강조 */
        #menu-posts-places .wp-menu-name {
            color: #2563eb !important;
            font-weight: bold;
        }
        
        /* 불필요한 메뉴 흐리게 */
        #menu-posts .wp-menu-name:after {
            content: " (사용안함)";
            color: #999;
            font-size: 11px;
        }
        
        /* 성수야 대시보드 위젯 스타일링 */
        #sungsuya_dashboard_widget .inside {
            margin: 0;
            padding: 0;
        }
    </style>';
}
add_action('admin_head', 'sungsuya_admin_head');
