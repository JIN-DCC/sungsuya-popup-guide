<?php
/**
 * Smart Deep Link 헬퍼 함수
 * 
 * @package SungsuyaV2
 * @subpackage Helpers
 * @since 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Smart Deep Link v7.0 링크 생성 함수
 */
function generate_platform_links_v7($post_id) {
    if (!class_exists('SmartDeepLinkV7')) {
        return array();
    }
    
    $place_title = get_the_title($post_id);
    
    // 테스트 데이터에서 "🕷️ 크롤링테스트" 접두사 제거
    $clean_title = preg_replace('/^🕷️\s*크롤링테스트\s*-\s*/', '', $place_title);
    
    $smartLink = new SmartDeepLinkV7();
    $links = $smartLink->generateLinks($clean_title);
    
    // v7.0 데이터를 메타필드에 저장
    update_post_meta($post_id, 'smart_deep_link_v7', $links);
    
    return $links;
}

/**
 * v7.0 카드 렌더링 함수 (WordPress용)
 */
function render_smart_deep_link_v7_cards($post_id) {
    if (!class_exists('SmartDeepLinkV7')) {
        return '';
    }
    
    $place_title = get_the_title($post_id);
    $clean_title = preg_replace('/^크롤링테스트\s*/', '', $place_title);
    
    $smartLink = new SmartDeepLinkV7();
    return $smartLink->renderWordPressCards($clean_title);
}

/**
 * 플랫폼별 카드 색상 반환
 */
function get_platform_card_color($platform) {
    $colors = array(
        'naver' => 'border-green-200 bg-green-50',
        'kakao' => 'border-yellow-200 bg-yellow-50', 
        'blueribbon' => 'border-blue-200 bg-blue-50',
        'diningcode' => 'border-orange-200 bg-orange-50',
        'instagram' => 'border-pink-200 bg-pink-50'
    );
    
    return isset($colors[$platform]) ? $colors[$platform] : 'border-gray-200 bg-gray-50';
}

/**
 * 플랫폼별 설명 반환
 */
function get_platform_description($platform) {
    $descriptions = array(
        'naver' => '네이버 지도에서 위치 정보와 리뷰를 확인하세요',
        'kakao' => '카카오맵에서 길찾기와 상세 정보를 확인하세요',
        'blueribbon' => '블루리본에서 음식점 정보와 리뷰를 확인하세요',
        'diningcode' => '다이닝코드에서 예약 정보와 메뉴를 확인하세요',
        'instagram' => '인스타그램에서 실제 방문 후기와 사진을 확인하세요'
    );
    
    return isset($descriptions[$platform]) ? $descriptions[$platform] : '외부 플랫폼에서 더 많은 정보를 확인하세요';
}

/**
 * 플랫폼별 아이콘 반환
 */
function get_platform_icon($platform) {
    $icons = array(
        'naver' => '🗺️',
        'kakao' => '📍',
        'blueribbon' => '🎖️',
        'diningcode' => '🍽️',
        'instagram' => '📸'
    );
    
    return isset($icons[$platform]) ? $icons[$platform] : '🔗';
}

/**
 * v7.0 링크 데이터 처리 함수
 */
function process_v7_link_data($post_id) {
    $v7_data = get_post_meta($post_id, '_smart_deep_link_v7_data', true);
    
    if (empty($v7_data)) {
        return array();
    }
    
    if (is_string($v7_data)) {
        $v7_data = json_decode($v7_data, true);
    }
    
    return is_array($v7_data) ? $v7_data : array();
}

/**
 * single-places.php에서 사용하는 함수들 (v7.0 호환성)
 */
function sungsuya_get_card_color($platform) {
    $colors = array(
        'naver' => 'bg-gradient-to-br from-green-400 to-green-600',
        'kakao' => 'bg-gradient-to-br from-yellow-400 to-orange-500', 
        'blueribbon' => 'bg-gradient-to-br from-blue-400 to-blue-600',
        'diningcode' => 'bg-gradient-to-br from-orange-400 to-red-500',
        'instagram' => 'bg-gradient-to-br from-pink-400 to-purple-500'
    );
    
    return isset($colors[$platform]) ? $colors[$platform] : 'bg-gradient-to-br from-gray-400 to-gray-600';
}

function sungsuya_get_platform_description($platform) {
    return get_platform_description($platform);
}

/**
 * v7.0 카드 렌더링 함수
 */
function render_v7_platform_cards($v7_data) {
    if (empty($v7_data) || !is_array($v7_data)) {
        return '';
    }
    
    $output = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';
    
    foreach ($v7_data as $platform => $data) {
        if (empty($data['url'])) continue;
        
        $color_class = get_platform_card_color($platform);
        $description = get_platform_description($platform);
        $icon = get_platform_icon($platform);
        $display_name = ucfirst($platform);
        
        if ($platform === 'blueribbon') $display_name = 'Blue Ribbon';
        if ($platform === 'diningcode') $display_name = 'Dining Code';
        
        $output .= sprintf('
            <a href="%s" target="_blank" rel="noopener noreferrer" 
               class="block p-4 rounded-lg border-2 %s hover:shadow-md transition-all duration-200 hover:scale-105">
                <div class="flex items-center mb-2">
                    <span class="text-2xl mr-3">%s</span>
                    <h4 class="font-semibold text-gray-800">%s</h4>
                </div>
                <p class="text-sm text-gray-600">%s</p>
            </a>
        ', 
            esc_url($data['url']), 
            esc_attr($color_class), 
            $icon, 
            esc_html($display_name), 
            esc_html($description)
        );
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Smart Deep Link v7.0 메타데이터 처리 함수
 */
function get_v7_meta_data($post_id) {
    $v7_data = get_post_meta($post_id, 'smart_deep_link_v7', true);
    
    if (empty($v7_data)) {
        return array();
    }
    
    return is_array($v7_data) ? $v7_data : array();
}
