<?php
/**
 * 장소유형 메타필드 시스템
 * 
 * 장소유형(place_type)에 메타필드 타입을 추가하여
 * 각 유형별로 적절한 메타필드 세트를 자동으로 매핑
 * 
 * @package SungsuyaV2
 * @since 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 장소유형 추가 폼에 메타필드 타입 선택 필드 추가
 */
function sungsuya_add_metafield_type_field($taxonomy) {
    ?>
    <div class="form-field">
        <label for="metafield_type">메타필드 타입</label>
        <select name="metafield_type" id="metafield_type">
            <option value="">선택하세요</option>
            <option value="food">음식점 타입 (카페, 맛집, 바 등)</option>
            <option value="shop">매장 타입 (편집샵, 상설매장, 편의시설 등)</option>
        </select>
        <p>이 장소유형에서 사용할 메타필드 세트를 선택하세요.</p>
    </div>
    <?php
}
add_action('place_type_add_form_fields', 'sungsuya_add_metafield_type_field');

/**
 * 장소유형 편집 폼에 메타필드 타입 표시/수정 필드 추가
 */
function sungsuya_edit_metafield_type_field($term) {
    $metafield_type = get_term_meta($term->term_id, 'metafield_type', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="metafield_type">메타필드 타입</label></th>
        <td>
            <select name="metafield_type" id="metafield_type">
                <option value="">선택하세요</option>
                <option value="food" <?php selected($metafield_type, 'food'); ?>>
                    음식점 타입 (카페, 맛집, 바 등)
                </option>
                <option value="shop" <?php selected($metafield_type, 'shop'); ?>>
                    매장 타입 (편집샵, 상설매장, 편의시설 등)
                </option>
            </select>
            <p class="description">이 장소유형에서 사용할 메타필드 세트를 선택하세요.</p>
        </td>
    </tr>
    <?php
}
add_action('place_type_edit_form_fields', 'sungsuya_edit_metafield_type_field');

/**
 * 장소유형 저장 시 메타필드 타입 저장
 */
function sungsuya_save_metafield_type($term_id) {
    if (isset($_POST['metafield_type'])) {
        update_term_meta($term_id, 'metafield_type', sanitize_text_field($_POST['metafield_type']));
    }
}
add_action('created_place_type', 'sungsuya_save_metafield_type');
add_action('edited_place_type', 'sungsuya_save_metafield_type');

/**
 * 음식점 타입 메타필드 정의
 */
function sungsuya_get_food_metafields() {
    return array(
        '_menu_items' => array(
            'label' => '대표메뉴',
            'type' => 'textarea',
            'placeholder' => '예: 아메리카노 5,000원, 카페라떼 5,500원',
            'description' => '대표 메뉴와 가격을 입력하세요.'
        ),
        '_price_range' => array(
            'label' => '가격대',
            'type' => 'select',
            'options' => array(
                '' => '선택하세요',
                'low' => '저렴 (1만원 이하)',
                'medium' => '보통 (1-3만원)',
                'high' => '비싼 (3-5만원)',
                'premium' => '매우비싼 (5만원 이상)'
            ),
            'description' => '평균 가격대를 선택하세요.'
        ),
        '_cuisine_type' => array(
            'label' => '음식종류',
            'type' => 'text',
            'placeholder' => '예: 한식, 양식, 일식, 카페',
            'description' => '음식 카테고리를 입력하세요.'
        ),
        '_break_time' => array(
            'label' => '브레이크타임',
            'type' => 'text',
            'placeholder' => '예: 15:00-17:00',
            'description' => '브레이크타임이 있다면 입력하세요.'
        ),
        '_last_order' => array(
            'label' => '라스트오더',
            'type' => 'text',
            'placeholder' => '예: 21:30',
            'description' => '라스트오더 시간을 입력하세요.'
        ),
        '_reservation' => array(
            'label' => '예약 가능 여부',
            'type' => 'checkbox',
            'description' => '예약이 가능한 경우 체크하세요.'
        )
    );
}

/**
 * 매장 타입 메타필드 정의
 */
function sungsuya_get_shop_metafields() {
    return array(
        '_brands' => array(
            'label' => '취급브랜드',
            'type' => 'textarea',
            'placeholder' => '예: 나이키, 아디다스, 컨버스',
            'description' => '취급하는 브랜드를 입력하세요.'
        ),
        '_product_category' => array(
            'label' => '상품종류',
            'type' => 'text',
            'placeholder' => '예: 의류, 액세서리, 생활용품',
            'description' => '판매하는 상품 종류를 입력하세요.'
        ),
        '_price_level' => array(
            'label' => '가격대',
            'type' => 'select',
            'options' => array(
                '' => '선택하세요',
                '1-3' => '1-3만원대',
                '3-5' => '3-5만원대',
                '5-10' => '5-10만원대',
                '10+' => '10만원 이상'
            ),
            'description' => '주요 상품의 가격대를 선택하세요.'
        ),
        '_special_services' => array(
            'label' => '특별서비스',
            'type' => 'textarea',
            'placeholder' => '예: 무료 배송, 교환/환불, 맞춤 제작',
            'description' => '제공하는 특별한 서비스가 있다면 입력하세요.'
        ),
        '_payment_methods' => array(
            'label' => '결제방법',
            'type' => 'checkbox_multiple',
            'options' => array(
                'cash' => '현금',
                'card' => '카드',
                'transfer' => '계좌이체',
                'easy_pay' => '간편결제 (네이버페이, 카카오페이 등)'
            ),
            'description' => '가능한 결제 방법을 모두 선택하세요.'
        ),
        '_online_shop' => array(
            'label' => '온라인샵 URL',
            'type' => 'url',
            'placeholder' => 'https://example.com',
            'description' => '온라인샵이 있다면 URL을 입력하세요.'
        )
    );
}

/**
 * 장소유형별 메타필드 가져오기
 */
function sungsuya_get_metafields_by_place_type($place_type_id) {
    if (!$place_type_id) {
        return array();
    }
    
    $metafield_type = get_term_meta($place_type_id, 'metafield_type', true);
    
    if ($metafield_type === 'food') {
        return sungsuya_get_food_metafields();
    } elseif ($metafield_type === 'shop') {
        return sungsuya_get_shop_metafields();
    }
    
    return array();
}

/**
 * AJAX 핸들러: 장소유형 목록 가져오기 (메타필드 타입 포함)
 */
function sungsuya_get_place_types_with_meta() {
    check_ajax_referer('places_nonce', 'nonce');
    
    $types = get_terms(array(
        'taxonomy' => 'place_type',
        'hide_empty' => false
    ));
    
    if (is_wp_error($types)) {
        wp_send_json_error('장소유형을 가져올 수 없습니다.');
        return;
    }
    
    $result = array();
    foreach ($types as $type) {
        // 팝업스토어는 제외 (CSV 프로세스로 별도 관리)
        if ($type->slug === 'popup-store' || $type->name === '팝업스토어') {
            continue;
        }
        
        $result[] = array(
            'term_id' => $type->term_id,
            'name' => $type->name,
            'slug' => $type->slug,
            'description' => $type->description,
            'metafield_type' => get_term_meta($type->term_id, 'metafield_type', true),
            'count' => $type->count
        );
    }
    
    wp_send_json_success($result);
}
add_action('wp_ajax_get_place_types_with_meta', 'sungsuya_get_place_types_with_meta');

/**
 * 관리자 컬럼에 메타필드 타입 표시
 */
function sungsuya_add_place_type_columns($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        // 이름 컬럼 다음에 메타필드 타입 추가
        if ($key === 'name') {
            $new_columns['metafield_type'] = '메타필드 타입';
        }
    }
    
    return $new_columns;
}
add_filter('manage_edit-place_type_columns', 'sungsuya_add_place_type_columns');

/**
 * 관리자 컬럼에 메타필드 타입 데이터 표시
 */
function sungsuya_place_type_column_content($content, $column_name, $term_id) {
    if ($column_name === 'metafield_type') {
        $metafield_type = get_term_meta($term_id, 'metafield_type', true);
        
        if ($metafield_type === 'food') {
            $content = '<span style="color: #d63638;">🍽️ 음식점 타입</span>';
        } elseif ($metafield_type === 'shop') {
            $content = '<span style="color: #00a0d2;">🛍️ 매장 타입</span>';
        } else {
            $content = '<span style="color: #999;">미설정</span>';
        }
    }
    
    return $content;
}
add_filter('manage_place_type_custom_column', 'sungsuya_place_type_column_content', 10, 3);

/**
 * 기존 장소유형에 기본값 설정 (최초 1회 실행)
 */
function sungsuya_set_default_metafield_types() {
    // 이미 실행했는지 확인
    if (get_option('sungsuya_metafield_types_initialized')) {
        return;
    }
    
    // 기본값 매핑
    $default_mappings = array(
        '맛집' => 'food',
        '카페' => 'food',
        '바' => 'food',
        '상설매장' => 'shop',
        '편의시설' => 'shop',
        '편집샵' => 'shop',
        '소품샵' => 'shop'
    );
    
    // 모든 장소유형 가져오기
    $terms = get_terms(array(
        'taxonomy' => 'place_type',
        'hide_empty' => false
    ));
    
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            // 팝업스토어는 제외
            if ($term->slug === 'popup-store' || $term->name === '팝업스토어') {
                continue;
            }
            
            // 기본값 설정
            if (isset($default_mappings[$term->name])) {
                update_term_meta($term->term_id, 'metafield_type', $default_mappings[$term->name]);
            }
        }
    }
    
    // 실행 완료 표시
    update_option('sungsuya_metafield_types_initialized', true);
}
add_action('init', 'sungsuya_set_default_metafield_types');

/**
 * 디버그 정보 출력 (개발 중에만 사용)
 */
function sungsuya_debug_place_types() {
    if (!current_user_can('manage_options') || !isset($_GET['debug_place_types'])) {
        return;
    }
    
    $terms = get_terms(array(
        'taxonomy' => 'place_type',
        'hide_empty' => false
    ));
    
    echo '<pre>';
    echo '=== 장소유형 메타필드 타입 현황 ===' . PHP_EOL;
    foreach ($terms as $term) {
        $metafield_type = get_term_meta($term->term_id, 'metafield_type', true);
        echo sprintf(
            'ID: %d | 이름: %s | Slug: %s | 메타필드 타입: %s' . PHP_EOL,
            $term->term_id,
            $term->name,
            $term->slug,
            $metafield_type ?: '미설정'
        );
    }
    echo '</pre>';
    exit;
}
add_action('admin_init', 'sungsuya_debug_place_types');
