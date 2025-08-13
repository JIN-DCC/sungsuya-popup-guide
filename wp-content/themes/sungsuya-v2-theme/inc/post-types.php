<?php
/**
 * 성수야! V2 - Custom Post Types (정리된 버전)
 * 
 * 팝업스토어 커스텀 포스트 타입 정의 (카카오 메타박스와 충돌 방지)
 * 
 * @package SungsuyaV2
 * @version 2.0.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 팝업스토어 커스텀 포스트 타입 등록
 */
function sungsuya_register_popup_store_post_type() {
    $labels = array(
        'name'                  => '팝업스토어',
        'singular_name'         => '팝업스토어',
        'menu_name'             => '팝업스토어',
        'name_admin_bar'        => '팝업스토어',
        'archives'              => '팝업스토어 아카이브',
        'attributes'            => '팝업스토어 속성',
        'parent_item_colon'     => '상위 팝업스토어:',
        'all_items'             => '모든 팝업스토어',
        'add_new_item'          => '새 팝업스토어 추가',
        'add_new'               => '새로 추가',
        'new_item'              => '새 팝업스토어',
        'edit_item'             => '팝업스토어 편집',
        'update_item'           => '팝업스토어 업데이트',
        'view_item'             => '팝업스토어 보기',
        'view_items'            => '팝업스토어들 보기',
        'search_items'          => '팝업스토어 검색',
        'not_found'             => '팝업스토어를 찾을 수 없습니다',
        'not_found_in_trash'    => '휴지통에서 팝업스토어를 찾을 수 없습니다',
        'featured_image'        => '대표 이미지',
        'set_featured_image'    => '대표 이미지 설정',
        'remove_featured_image' => '대표 이미지 제거',
        'use_featured_image'    => '대표 이미지로 사용',
        'insert_into_item'      => '팝업스토어에 삽입',
        'uploaded_to_this_item' => '이 팝업스토어에 업로드됨',
        'items_list'            => '팝업스토어 목록',
        'items_list_navigation' => '팝업스토어 목록 네비게이션',
        'filter_items_list'     => '팝업스토어 목록 필터',
    );

    $args = array(
        'label'                 => '팝업스토어',
        'description'           => '성수동 팝업스토어 정보',
        'labels'                => $labels,
        'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-store',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
        'rest_base'             => 'popup-stores',
        'rewrite'               => array(
            'slug'       => 'store',
            'with_front' => false,
        ),
    );

    register_post_type('popup_store', $args);
}
add_action('init', 'sungsuya_register_popup_store_post_type', 0);

/**
 * 팝업스토어 카테고리 택소노미 등록
 */
function sungsuya_register_store_category_taxonomy() {
    $labels = array(
        'name'                       => '스토어 카테고리',
        'singular_name'              => '스토어 카테고리',
        'menu_name'                  => '카테고리',
        'all_items'                  => '모든 카테고리',
        'parent_item'                => '상위 카테고리',
        'parent_item_colon'          => '상위 카테고리:',
        'new_item_name'              => '새 카테고리명',
        'add_new_item'               => '새 카테고리 추가',
        'edit_item'                  => '카테고리 편집',
        'update_item'                => '카테고리 업데이트',
        'view_item'                  => '카테고리 보기',
        'separate_items_with_commas' => '카테고리를 쉼표로 구분',
        'add_or_remove_items'        => '카테고리 추가 또는 제거',
        'choose_from_most_used'      => '가장 많이 사용된 카테고리에서 선택',
        'popular_items'              => '인기 카테고리',
        'search_items'               => '카테고리 검색',
        'not_found'                  => '카테고리를 찾을 수 없습니다',
        'no_terms'                   => '카테고리 없음',
        'items_list'                 => '카테고리 목록',
        'items_list_navigation'      => '카테고리 목록 네비게이션',
    );

    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => true,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => true,
        'show_in_rest'               => true,
        'rest_base'                  => 'store-categories',
    );

    register_taxonomy('store_category', array('popup_store'), $args);
}
add_action('init', 'sungsuya_register_store_category_taxonomy', 0);

/**
 * 기본 카테고리 생성
 */
function sungsuya_create_default_categories() {
    if (!term_exists('패션', 'store_category')) {
        wp_insert_term('패션', 'store_category', array(
            'description' => '의류, 액세서리, 신발 등',
            'slug' => 'fashion'
        ));
    }
    
    if (!term_exists('뷰티', 'store_category')) {
        wp_insert_term('뷰티', 'store_category', array(
            'description' => '화장품, 스킨케어, 향수 등',
            'slug' => 'beauty'
        ));
    }
    
    if (!term_exists('푸드', 'store_category')) {
        wp_insert_term('푸드', 'store_category', array(
            'description' => '음식, 음료, 디저트 등',
            'slug' => 'food'
        ));
    }
    
    if (!term_exists('라이프스타일', 'store_category')) {
        wp_insert_term('라이프스타일', 'store_category', array(
            'description' => '인테리어, 생활용품, 취미 등',
            'slug' => 'lifestyle'
        ));
    }
    
    if (!term_exists('아트', 'store_category')) {
        wp_insert_term('아트', 'store_category', array(
            'description' => '예술작품, 공예품, 디자인 등',
            'slug' => 'art'
        ));
    }
}
add_action('init', 'sungsuya_create_default_categories');

/**
 * 팝업스토어 목록에 커스텀 컬럼 추가
 */
function sungsuya_popup_store_admin_columns($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        if ($key === 'title') {
            $new_columns['store_address'] = '주소';
            $new_columns['opening_hours'] = '운영시간';
            $new_columns['store_period'] = '운영기간';
            $new_columns['featured'] = '인기';
        }
    }
    
    return $new_columns;
}
add_filter('manage_popup_store_posts_columns', 'sungsuya_popup_store_admin_columns');

/**
 * 커스텀 컬럼 내용 표시
 */
function sungsuya_popup_store_admin_column_content($column, $post_id) {
    switch ($column) {
        case 'store_address':
            echo get_post_meta($post_id, 'address', true);
            break;
            
        case 'opening_hours':
            echo get_post_meta($post_id, 'opening_hours', true);
            break;
            
        case 'store_period':
            $start = get_post_meta($post_id, 'start_date', true);
            $end = get_post_meta($post_id, 'end_date', true);
            if ($start && $end) {
                echo date('m/d', strtotime($start)) . ' ~ ' . date('m/d', strtotime($end));
            }
            break;
            
        case 'featured':
            $featured = get_post_meta($post_id, 'featured', true);
            echo $featured === '1' ? '⭐ 인기' : '';
            break;
    }
}
add_action('manage_popup_store_posts_custom_column', 'sungsuya_popup_store_admin_column_content', 10, 2);

/**
 * Permalink 규칙 재설정 함수
 */
function sungsuya_flush_rewrite_rules() {
    // 팝업스토어 포스트 타입 등록
    sungsuya_register_popup_store_post_type();
    
    // Permalink 규칙 재설정
    flush_rewrite_rules();
}

// 테마 활성화 시 실행
register_activation_hook(__FILE__, 'sungsuya_flush_rewrite_rules');

// 테마 비활성화 시 정리
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

/**
 * 테마 초기화 시 permalink 규칙 재설정
 */
function sungsuya_theme_activation() {
    sungsuya_flush_rewrite_rules();
}
add_action('after_switch_theme', 'sungsuya_theme_activation');

/**
 * 수동으로 rewrite rules 플러시하는 함수 (관리자 전용)
 */
function sungsuya_manual_flush_rewrites() {
    if (current_user_can('manage_options') && isset($_GET['sungsuya_flush_rewrites'])) {
        sungsuya_register_popup_store_post_type();
        sungsuya_register_store_category_taxonomy();
        flush_rewrite_rules();
        
        // 성공 메시지와 함께 리다이렉트
        wp_redirect(admin_url('edit.php?post_type=popup_store&flushed=1'));
        exit;
    }
}
add_action('admin_init', 'sungsuya_manual_flush_rewrites');

/**
 * 관리자 메뉴에 rewrite flush 링크 추가
 */
function sungsuya_add_flush_rewrites_notice() {
    if (isset($_GET['flushed']) && $_GET['flushed'] == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Permalink 규칙이 재설정되었습니다!</p></div>';
    }
    
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'popup_store') {
        echo '<div class="notice notice-info">';
        echo '<p>스토어 목록 페이지가 작동하지 않는다면: ';
        echo '<a href="' . admin_url('edit.php?post_type=popup_store&sungsuya_flush_rewrites=1') . '" class="button">Permalink 규칙 재설정</a></p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'sungsuya_add_flush_rewrites_notice');