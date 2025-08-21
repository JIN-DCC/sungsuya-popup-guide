<?php
/**
 * Places 목록 개선 - 컬럼 정리 및 기본값 설정
 * 
 * @package SungsuyaV2
 * @since 2025.06.27
 */

// Places 목록 컬럼 정리
add_filter('manage_places_posts_columns', 'sungsuya_clean_places_columns', 100);

function sungsuya_clean_places_columns($columns) {
    // 기존 컬럼 제거
    unset($columns['place_type_col']); // 중복된 장소 유형 컬럼 제거
    unset($columns['popup_status']); // 사용하지 않는 팝업 상태 컬럼 제거
    
    // 컬럼 순서 재정리
    $new_columns = array();
    
    // 필수 컬럼 먼저
    if (isset($columns['cb'])) $new_columns['cb'] = $columns['cb'];
    if (isset($columns['title'])) $new_columns['title'] = $columns['title'];
    if (isset($columns['featured_image'])) $new_columns['featured_image'] = '이미지';
    
    // 주요 정보
    $new_columns['address'] = '주소';
    $new_columns['place_type'] = '장소 유형';
    $new_columns['operating_status'] = '운영 상태';
    
    // 분류
    if (isset($columns['taxonomy-place_type'])) {
        $new_columns['taxonomy-place_type'] = '카테고리';
    }
    
    // 날짜
    if (isset($columns['date'])) $new_columns['date'] = $columns['date'];
    
    return $new_columns;
}

// Places 저장 시 기본값 설정
add_action('save_post_places', 'sungsuya_set_default_place_values', 10, 3);

function sungsuya_set_default_place_values($post_id, $post, $update) {
    // 자동 저장이면 무시
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    // 권한 확인
    if (!current_user_can('edit_post', $post_id)) return;
    
    // 장소 유형 기본값 설정
    $place_type = get_post_meta($post_id, 'place_type', true);
    if (empty($place_type)) {
        update_post_meta($post_id, 'place_type', 'restaurant'); // 기본값: 맛집
    }
    
    // 운영 상태 기본값 설정
    $operating_status = get_post_meta($post_id, 'operating_status', true);
    if (empty($operating_status)) {
        update_post_meta($post_id, 'operating_status', 'operating'); // 기본값: 운영중
    }
    
    // 인기도 기본값 설정
    $popularity = get_post_meta($post_id, 'popularity', true);
    if (empty($popularity)) {
        update_post_meta($post_id, 'popularity', 'normal'); // 기본값: 보통
    }
}

// 컬럼 내용 표시 개선
add_action('manage_places_posts_custom_column', 'sungsuya_display_places_columns', 10, 2);

function sungsuya_display_places_columns($column, $post_id) {
    switch ($column) {
        case 'address':
            // 메타필드에서 주소 가져오기
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
            // 메타필드에서 운영 상태 가져오기
            $status = get_post_meta($post_id, 'operating_status', true);
            if (empty($status)) {
                $status = get_post_meta($post_id, 'operation_status', true);
            }
            
            $status_labels = array(
                'operating' => '<span style="color: #46b450;">🟢 운영중</span>',
                'open' => '<span style="color: #46b450;">🟢 운영중</span>',
                'closed' => '<span style="color: #dc3232;">🔴 영업종료</span>',
                'preparing' => '<span style="color: #ffb900;">🟡 오픈 예정</span>',
                'coming_soon' => '<span style="color: #ffb900;">🟡 오픈 예정</span>',
                'upcoming' => '<span style="color: #ffb900;">🟡 오픈 예정</span>'
            );
            
            if (!empty($status) && isset($status_labels[$status])) {
                echo $status_labels[$status];
            } else {
                echo '<span style="color: #999;">미설정</span>';
            }
            break;
            
        case 'place_type':
            // 택소노미에서 장소 유형 가져오기
            $place_types = wp_get_post_terms($post_id, 'place_type');
            if (!empty($place_types) && !is_wp_error($place_types)) {
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
                    echo '📍 ' . esc_html($term->name);
                }
            } else {
                // 메타필드에서도 확인
                $type = get_post_meta($post_id, 'place_type', true);
                $type_labels = array(
                    'restaurant' => '🍽️ 맛집',
                    'cafe' => '☕ 카페',
                    'shop' => '🏪 상점',
                    'popup_store' => '🏪 팝업스토어',
                    'facility' => '🚻 편의시설'
                );
                
                if (!empty($type) && isset($type_labels[$type])) {
                    echo $type_labels[$type];
                } else {
                    echo '<span style="color: #999;">미설정</span>';
                }
            }
            break;
    }
}

// 빠른 편집에서도 기본값 적용
add_action('wp_ajax_inline-save', 'sungsuya_quick_edit_save', 0);

function sungsuya_quick_edit_save() {
    if (isset($_POST['post_type']) && $_POST['post_type'] == 'places') {
        $post_id = $_POST['post_ID'];
        
        // 빠른 편집 시에도 기본값 체크
        if (empty($_POST['place_type'])) {
            update_post_meta($post_id, 'place_type', 'restaurant');
        }
        
        if (empty($_POST['operating_status'])) {
            update_post_meta($post_id, 'operating_status', 'operating');
        }
    }
}

// 일괄 작업 메뉴 정리
add_filter('bulk_actions-edit-places', 'sungsuya_places_bulk_actions');

function sungsuya_places_bulk_actions($bulk_actions) {
    // 불필요한 일괄 작업 제거
    unset($bulk_actions['이미지 크롤링']); // 개별 크롤링으로 충분
    
    // 유용한 일괄 작업 추가
    $bulk_actions['set_operating'] = '운영중으로 변경';
    $bulk_actions['set_closed'] = '영업종료로 변경';
    
    return $bulk_actions;
}
