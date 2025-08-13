<?php
/**
 * 성수야! V2 - Places Custom Post Type
 * 
 * 다양한 장소 유형을 지원하는 통합 Custom Post Type
 * - 팝업스토어, 맛집, 상설매장, 편의시설
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Places 커스텀 포스트 타입 등록
 */
function sungsuya_register_places_post_type() {
    $labels = array(
        'name'                  => '장소 관리',
        'singular_name'         => '장소',
        'menu_name'             => '장소',
        'name_admin_bar'        => '장소',
        'archives'              => '장소 아카이브',
        'attributes'            => '장소 속성',
        'parent_item_colon'     => '상위 장소:',
        'all_items'             => '모든 장소',
        'add_new_item'          => '새 장소 추가',
        'add_new'               => '새로 추가',
        'new_item'              => '새 장소',
        'edit_item'             => '장소 편집',
        'update_item'           => '장소 업데이트',
        'view_item'             => '장소 보기',
        'view_items'            => '장소들 보기',
        'search_items'          => '장소 검색',
        'not_found'             => '장소를 찾을 수 없습니다',
        'not_found_in_trash'    => '휴지통에서 장소를 찾을 수 없습니다',
        'featured_image'        => '대표 이미지',
        'set_featured_image'    => '대표 이미지 설정',
        'remove_featured_image' => '대표 이미지 제거',
        'use_featured_image'    => '대표 이미지로 사용',
        'insert_into_item'      => '장소에 삽입',
        'uploaded_to_this_item' => '이 장소에 업로드됨',
        'items_list'            => '장소 목록',
        'items_list_navigation' => '장소 목록 네비게이션',
        'filter_items_list'     => '장소 목록 필터',
    );

    $args = array(
        'label'                 => '장소',
        'description'           => '성수동 다양한 장소 정보 (팝업스토어, 맛집, 상설매장, 편의시설)',
        'labels'                => $labels,
        'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-location-alt',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
        'rest_base'             => 'places',
        'rewrite'               => array(
            'slug'       => 'places',
            'with_front' => false,
        ),
    );

    register_post_type('places', $args);
}
add_action('init', 'sungsuya_register_places_post_type', 0);

/**
 * Places 목록에 커스텀 컬럼 추가 (비활성화됨 - places-list-improvements.php에서 관리)
 */
/*
function sungsuya_places_admin_columns($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        if ($key === 'title') {
            $new_columns['place_type'] = '장소 유형';
            $new_columns['place_address'] = '주소';
            $new_columns['operating_status'] = '운영 상태';
            $new_columns['featured'] = '인기';
        }
    }
    
    return $new_columns;
}
add_filter('manage_places_posts_columns', 'sungsuya_places_admin_columns');
*/

/**
 * 커스텀 컬럼 내용 표시 (비활성화됨 - places-list-improvements.php에서 관리)
 */
/*
function sungsuya_places_admin_column_content($column, $post_id) {
    switch ($column) {
        case 'place_type':
            $place_types = wp_get_post_terms($post_id, 'place_type');
            if (!empty($place_types) && !is_wp_error($place_types)) {
                // 첫 번째 타입만 사용하여 중복 방지
                $term = $place_types[0];
                $type_labels = array(
                    'popup_store' => '🏪 팝업스토어',
                    'popup-store' => '🏪 팝업스토어',
                    'restaurant' => '🍽️ 맛집',
                    'retail_store' => '🏬 상설매장',
                    'facility' => '🚻 편의시설',
                    'prop_shop' => '🛍️ 소품샵'
                );
                
                if (isset($type_labels[$term->slug])) {
                    echo $type_labels[$term->slug];
                } else {
                    // 기본 아이콘 + 택소노미 이름 사용
                    echo '📍 ' . esc_html($term->name);
                }
            } else {
                echo '<span style="color: #999;">미설정</span>';
            }
            break;
            
        case 'place_address':
            // 메타필드 우선순위: address > place_address > _address
            $address = get_post_meta($post_id, 'address', true);
            if (empty($address)) {
                $address = get_post_meta($post_id, 'place_address', true);
            }
            if (empty($address)) {
                $address = get_post_meta($post_id, '_address', true);
            }
            
            if (!empty($address)) {
                echo esc_html($address);
            } else {
                echo '<span style="color: #999;">주소 없음</span>';
            }
            break;
            
        case 'operating_status':
            // 메타필드 우선순위: operating_status > operation_status > _operating_status
            $status = get_post_meta($post_id, 'operating_status', true);
            if (empty($status)) {
                $status = get_post_meta($post_id, 'operation_status', true);
            }
            if (empty($status)) {
                $status = get_post_meta($post_id, '_operating_status', true);
            }
            
            $status_labels = array(
                'open' => '🟢 운영중',
                'closed' => '🔴 영업종료',
                'coming_soon' => '🟡 오픈 예정',
                'upcoming' => '🟡 오픈 예정',
                'closing_soon' => '⏰ 곧 종료',
                'temporary_closed' => '⏸️ 임시 휴업'
            );
            
            if (!empty($status) && isset($status_labels[$status])) {
                echo $status_labels[$status];
            } else {
                echo '<span style="color: #999;">미설정</span>';
            }
            break;
            
        case 'featured':
            $featured = get_post_meta($post_id, 'featured', true);
            echo $featured === '1' ? '⭐ 인기' : '';
            break;
    }
}
add_action('manage_places_posts_custom_column', 'sungsuya_places_admin_column_content', 10, 2);
*/

/**
 * Places 포스트 타입에 대한 필터링 추가
 */
function sungsuya_places_admin_filters() {
    global $typenow;
    
    if ($typenow === 'places') {
        // 장소 유형 필터
        $place_types = get_terms(array(
            'taxonomy' => 'place_type',
            'hide_empty' => false
        ));
        
        if (!empty($place_types)) {
            echo '<select name="place_type_filter">';
            echo '<option value="">모든 장소 유형</option>';
            
            $current = isset($_GET['place_type_filter']) ? $_GET['place_type_filter'] : '';
            
            foreach ($place_types as $type) {
                $type_labels = array(
                    'popup_store' => '팝업스토어',
                    'restaurant' => '맛집',
                    'retail_store' => '상설매장',
                    'facility' => '편의시설'
                );
                $label = isset($type_labels[$type->slug]) ? $type_labels[$type->slug] : $type->name;
                
                printf(
                    '<option value="%s" %s>%s (%d)</option>',
                    $type->slug,
                    selected($current, $type->slug, false),
                    $label,
                    $type->count
                );
            }
            echo '</select>';
        }
        
        // 운영 상태 필터
        echo '<select name="operating_status_filter">';
        echo '<option value="">모든 운영 상태</option>';
        
        $current_status = isset($_GET['operating_status_filter']) ? $_GET['operating_status_filter'] : '';
        $status_options = array(
            'open' => '운영중',
            'closed' => '영업종료',
            'coming_soon' => '오픈 예정'
        );
        
        foreach ($status_options as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                $value,
                selected($current_status, $value, false),
                $label
            );
        }
        echo '</select>';
    }
}
add_action('restrict_manage_posts', 'sungsuya_places_admin_filters');

/**
 * 필터 쿼리 적용
 */
function sungsuya_places_admin_filter_query($query) {
    global $pagenow, $typenow;
    
    if ($pagenow === 'edit.php' && $typenow === 'places') {
        // 장소 유형 필터 적용
        if (!empty($_GET['place_type_filter'])) {
            $query->set('tax_query', array(
                array(
                    'taxonomy' => 'place_type',
                    'field' => 'slug',
                    'terms' => $_GET['place_type_filter']
                )
            ));
        }
        
        // 운영 상태 필터 적용
        if (!empty($_GET['operating_status_filter'])) {
            $query->set('meta_key', 'operating_status');
            $query->set('meta_value', $_GET['operating_status_filter']);
        }
    }
}
add_filter('parse_query', 'sungsuya_places_admin_filter_query');

/**
 * Permalink 규칙 재설정 함수
 */
function sungsuya_places_flush_rewrite_rules() {
    // Places 포스트 타입 등록
    sungsuya_register_places_post_type();
    
    // Permalink 규칙 재설정
    flush_rewrite_rules();
}

/**
 * 수동으로 rewrite rules 플러시하는 함수 (관리자 전용)
 */
function sungsuya_places_manual_flush_rewrites() {
    if (current_user_can('manage_options') && isset($_GET['sungsuya_places_flush_rewrites'])) {
        sungsuya_register_places_post_type();
        flush_rewrite_rules();
        
        // 성공 메시지와 함께 리다이렉트
        wp_redirect(admin_url('edit.php?post_type=places&flushed=1'));
        exit;
    }
}
add_action('admin_init', 'sungsuya_places_manual_flush_rewrites');

/**
 * 관리자 메뉴에 rewrite flush 링크 추가
 */
function sungsuya_places_add_flush_rewrites_notice() {
    if (isset($_GET['flushed']) && $_GET['flushed'] == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Places Permalink 규칙이 재설정되었습니다!</p></div>';
    }
    
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'places') {
        echo '<div class="notice notice-info">';
        echo '<p>장소 목록 페이지가 작동하지 않는다면: ';
        echo '<a href="' . admin_url('edit.php?post_type=places&sungsuya_places_flush_rewrites=1') . '" class="button">Permalink 규칙 재설정</a></p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'sungsuya_places_add_flush_rewrites_notice');
