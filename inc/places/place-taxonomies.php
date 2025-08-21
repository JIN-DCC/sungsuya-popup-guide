<?php
/**
 * 성수야! V2 - Places Taxonomies
 * 
 * 장소 유형 및 카테고리 택소노미 정의
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 장소 유형 택소노미 등록 (popup_store, restaurant, retail_store, facility)
 */
function sungsuya_register_place_type_taxonomy() {
    $labels = array(
        'name'                       => '장소 유형',
        'singular_name'              => '장소 유형',
        'menu_name'                  => '장소 유형',
        'all_items'                  => '모든 장소 유형',
        'parent_item'                => '상위 유형',
        'parent_item_colon'          => '상위 유형:',
        'new_item_name'              => '새 유형명',
        'add_new_item'               => '새 유형 추가',
        'edit_item'                  => '유형 편집',
        'update_item'                => '유형 업데이트',
        'view_item'                  => '유형 보기',
        'separate_items_with_commas' => '유형을 쉼표로 구분',
        'add_or_remove_items'        => '유형 추가 또는 제거',
        'choose_from_most_used'      => '가장 많이 사용된 유형에서 선택',
        'popular_items'              => '인기 유형',
        'search_items'               => '유형 검색',
        'not_found'                  => '유형을 찾을 수 없습니다',
        'no_terms'                   => '유형 없음',
        'items_list'                 => '유형 목록',
        'items_list_navigation'      => '유형 목록 네비게이션',
    );

    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => false, // 태그 방식
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => false,
        'show_in_rest'               => true,
        'rest_base'                  => 'place-types',
        'rewrite'                    => array(
            'slug' => 'type',
            'with_front' => false,
        ),
    );

    register_taxonomy('place_type', array('places'), $args);
}
add_action('init', 'sungsuya_register_place_type_taxonomy', 0);

/**
 * 장소 카테고리 택소노미 등록 (계층형 카테고리)
 */
function sungsuya_register_place_category_taxonomy() {
    $labels = array(
        'name'                       => '장소 카테고리',
        'singular_name'              => '장소 카테고리',
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
        'hierarchical'               => true, // 카테고리 방식
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => true,
        'show_in_rest'               => true,
        'rest_base'                  => 'place-categories',
        'rewrite'                    => array(
            'slug' => 'category',
            'with_front' => false,
        ),
    );

    register_taxonomy('place_category', array('places'), $args);
}
add_action('init', 'sungsuya_register_place_category_taxonomy', 0);

/**
 * 기본 장소 유형 생성
 */
function sungsuya_create_default_place_types() {
    $default_types = array(
        'popup_store' => array(
            'name' => '팝업스토어',
            'description' => '기간 한정 운영 브랜드 스토어',
            'icon' => '🏪',
            'color' => '#e74c3c'
        ),
        'restaurant' => array(
            'name' => '맛집',
            'description' => '카페, 레스토랑, 주점 등 음식점',
            'icon' => '🍽️',
            'color' => '#f39c12'
        ),
        'retail_store' => array(
            'name' => '상설매장',
            'description' => '상시 운영 브랜드 매장',
            'icon' => '🏬',
            'color' => '#3498db'
        ),
        'facility' => array(
            'name' => '편의시설',
            'description' => '화장실, 주차장, ATM 등 편의시설',
            'icon' => '🚻',
            'color' => '#27ae60'
        )
    );

    foreach ($default_types as $slug => $type_data) {
        if (!term_exists($slug, 'place_type')) {
            $term = wp_insert_term($type_data['name'], 'place_type', array(
                'description' => $type_data['description'],
                'slug' => $slug
            ));
            
            if (!is_wp_error($term)) {
                // 커스텀 메타 데이터 저장 (아이콘, 색상)
                add_term_meta($term['term_id'], 'icon', $type_data['icon']);
                add_term_meta($term['term_id'], 'color', $type_data['color']);
            }
        }
    }
}
add_action('init', 'sungsuya_create_default_place_types');

/**
 * 기본 장소 카테고리 생성 (유형별로 분류)
 */
function sungsuya_create_default_place_categories() {
    $default_categories = array(
        // 팝업스토어 카테고리
        'popup_fashion' => array(
            'name' => '패션 팝업',
            'description' => '의류, 액세서리, 신발 브랜드 팝업스토어',
            'parent' => null
        ),
        'popup_beauty' => array(
            'name' => '뷰티 팝업',
            'description' => '화장품, 스킨케어, 향수 브랜드 팝업스토어',
            'parent' => null
        ),
        'popup_lifestyle' => array(
            'name' => '라이프스타일 팝업',
            'description' => '인테리어, 생활용품, 취미 관련 팝업스토어',
            'parent' => null
        ),
        
        // 맛집 카테고리
        'korean_food' => array(
            'name' => '한식',
            'description' => '한국 전통 음식',
            'parent' => null
        ),
        'western_food' => array(
            'name' => '양식',
            'description' => '서양 음식',
            'parent' => null
        ),
        'japanese_food' => array(
            'name' => '일식',
            'description' => '일본 음식',
            'parent' => null
        ),
        'cafe_dessert' => array(
            'name' => '카페/디저트',
            'description' => '커피, 차, 디저트 전문점',
            'parent' => null
        ),
        
        // 상설매장 카테고리
        'fashion_retail' => array(
            'name' => '패션 매장',
            'description' => '의류, 신발, 액세서리 매장',
            'parent' => null
        ),
        'home_living' => array(
            'name' => '홈&리빙',
            'description' => '인테리어, 생활용품 매장',
            'parent' => null
        ),
        'bookstore' => array(
            'name' => '서점',
            'description' => '도서, 문구 관련 매장',
            'parent' => null
        ),
        
        // 편의시설 카테고리
        'restroom' => array(
            'name' => '화장실',
            'description' => '공중 화장실',
            'parent' => null
        ),
        'parking' => array(
            'name' => '주차장',
            'description' => '주차 시설',
            'parent' => null
        ),
        'atm' => array(
            'name' => 'ATM',
            'description' => '현금인출기',
            'parent' => null
        )
    );

    foreach ($default_categories as $slug => $category_data) {
        if (!term_exists($slug, 'place_category')) {
            $args = array(
                'description' => $category_data['description'],
                'slug' => $slug
            );
            
            if ($category_data['parent']) {
                $parent_term = get_term_by('slug', $category_data['parent'], 'place_category');
                if ($parent_term) {
                    $args['parent'] = $parent_term->term_id;
                }
            }
            
            wp_insert_term($category_data['name'], 'place_category', $args);
        }
    }
}
add_action('init', 'sungsuya_create_default_place_categories');

/**
 * 장소 유형별 아이콘 및 색상 관리 함수
 */
function sungsuya_get_place_type_data($type_slug) {
    $type_data = array(
        'popup_store' => array(
            'name' => '팝업스토어',
            'icon' => '🏪',
            'color' => '#e74c3c',
            'css_class' => 'place-type-popup-store'
        ),
        'restaurant' => array(
            'name' => '맛집',
            'icon' => '🍽️',
            'color' => '#f39c12',
            'css_class' => 'place-type-restaurant'
        ),
        'retail_store' => array(
            'name' => '상설매장',
            'icon' => '🏬',
            'color' => '#3498db',
            'css_class' => 'place-type-retail-store'
        ),
        'facility' => array(
            'name' => '편의시설',
            'icon' => '🚻',
            'color' => '#27ae60',
            'css_class' => 'place-type-facility'
        )
    );
    
    return isset($type_data[$type_slug]) ? $type_data[$type_slug] : array(
        'name' => '기타',
        'icon' => '📍',
        'color' => '#6c757d',
        'css_class' => 'place-type-other'
    );
}

/**
 * 장소 유형별 색상 CSS 변수 출력
 */
function sungsuya_place_type_css_variables() {
    $type_data = array(
        'popup_store' => '#e74c3c',
        'restaurant' => '#f39c12',
        'retail_store' => '#3498db',
        'facility' => '#27ae60'
    );
    
    $css = '<style id="place-type-variables">:root {';
    foreach ($type_data as $type => $color) {
        $css .= sprintf('--place-type-%s-color: %s;', str_replace('_', '-', $type), $color);
    }
    $css .= '}</style>';
    
    echo $css;
}
add_action('wp_head', 'sungsuya_place_type_css_variables');
add_action('admin_head', 'sungsuya_place_type_css_variables');

/**
 * 장소 유형별 필터링을 위한 쿼리 변수 추가
 */
function sungsuya_add_place_query_vars($vars) {
    $vars[] = 'place_type';
    $vars[] = 'place_category';
    return $vars;
}
add_filter('query_vars', 'sungsuya_add_place_query_vars');

/**
 * 장소 유형 헬퍼 함수들
 */

// 특정 장소의 유형 정보 가져오기
function sungsuya_get_place_type_info($post_id) {
    $place_types = wp_get_post_terms($post_id, 'place_type');
    if (!empty($place_types)) {
        $type_slug = $place_types[0]->slug;
        return sungsuya_get_place_type_data($type_slug);
    }
    return sungsuya_get_place_type_data('other');
}

// 특정 유형의 장소 개수 가져오기
function sungsuya_get_place_type_count($type_slug) {
    $term = get_term_by('slug', $type_slug, 'place_type');
    return $term ? $term->count : 0;
}

// 모든 장소 유형의 통계 가져오기
function sungsuya_get_place_type_stats() {
    $types = get_terms(array(
        'taxonomy' => 'place_type',
        'hide_empty' => false
    ));
    
    $stats = array();
    foreach ($types as $type) {
        $type_data = sungsuya_get_place_type_data($type->slug);
        $stats[$type->slug] = array(
            'name' => $type_data['name'],
            'icon' => $type_data['icon'],
            'color' => $type_data['color'],
            'count' => $type->count
        );
    }
    
    return $stats;
}
