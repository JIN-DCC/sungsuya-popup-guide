<?php
/**
 * 정적지도 자동 생성 시스템
 * 
 * @package SungsuyaV2
 * @subpackage Helpers
 * @since 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

// NaverStaticMapGeneratorMapsFixed 클래스 로드
if (!class_exists('NaverStaticMapGeneratorMapsFixed')) {
    require_once SUNGSUYA_THEME_DIR . '/inc/naver-static-map-generator-maps-fixed.php';
}

/**
 * 정적지도 생성 함수
 */
function generate_static_map_for_place($post_id) {
    // 좌표 확인
    $latitude = get_post_meta($post_id, 'latitude', true);
    $longitude = get_post_meta($post_id, 'longitude', true);
    
    if (empty($latitude) || empty($longitude)) {
        return false;
    }
    
    // 네이버 API 설정 확인
    $naver_client_id = get_option('sungsuya_naver_client_id') ?: get_option('naver_maps_client_id');
    
    if (empty($naver_client_id)) {
        return false;
    }
    
    // 정적지도 URL 생성
    $map_url = "https://naveropenapi.apigw.ntruss.com/map-static/v2/raster?" . http_build_query([
        'w' => 400,
        'h' => 300,
        'center' => "{$longitude},{$latitude}",
        'level' => 16,
        'format' => 'png',
        'markers' => "type:t|size:mid|pos:{$longitude} {$latitude}"
    ]);
    
    // 메타필드에 저장
    update_post_meta($post_id, 'static_map_url', $map_url);
    
    return true;
}

/**
 * Places 저장시 자동 정적지도 생성
 */
function sungsuya_auto_generate_static_map($post_id, $post, $update) {
    // Places 포스트 타입만 처리
    if ($post->post_type !== 'places') {
        return;
    }
    
    // 자동 저장이나 리비전 무시
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }
    
    // 좌표가 있는 경우에만 처리
    $latitude = get_post_meta($post_id, 'latitude', true);
    $longitude = get_post_meta($post_id, 'longitude', true);
    
    if (empty($latitude) || empty($longitude)) {
        return;
    }
    
    // 이미 정적지도가 생성된 경우 건너뛰기
    $existing_map = get_post_meta($post_id, 'static_map_image_id', true);
    if (!empty($existing_map) && wp_attachment_is_image($existing_map)) {
        return;
    }
    
    // 정적지도 생성 실행
    try {
        if (class_exists('NaverStaticMapGeneratorMapsFixed')) {
            $generator = new NaverStaticMapGeneratorMapsFixed();
            $result = $generator->generatePlaceMap(
                $post_id, 
                $latitude, 
                $longitude, 
                $post->post_title
            );
            
            // 로그 기록 (개발환경에서만)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                if ($result['success']) {
                    error_log("Auto-generated static map for Places ID: {$post_id} - " . $result['message']);
                } else {
                    error_log("Failed to auto-generate static map for Places ID: {$post_id} - " . $result['error']);
                }
            }
        }
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Exception in auto static map generation for Places ID: {$post_id} - " . $e->getMessage());
        }
    }
}
add_action('save_post', 'sungsuya_auto_generate_static_map', 20, 3);

/**
 * 관리자 알림: 정적지도 생성 상태
 */
function sungsuya_static_map_admin_notice() {
    $screen = get_current_screen();
    
    if ($screen->post_type === 'places') {
        // 정적지도 미생성 Places 개수 확인
        $places_without_map = new WP_Query([
            'post_type' => 'places',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'static_map_image_id',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => 'static_map_image_id',
                    'value' => '',
                    'compare' => '='
                ]
            ]
        ]);
        
        if ($places_without_map->found_posts > 0) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>📍 정적지도 알림:</strong> ';
            echo $places_without_map->found_posts . '개 Places에 정적지도가 없습니다. ';
            echo '<a href="' . admin_url('edit.php?post_type=places&page=integrated-map-generation') . '" class="button button-small">지도 생성하기</a>';
            echo '</p>';
            echo '</div>';
        }
    }
}
add_action('admin_notices', 'sungsuya_static_map_admin_notice');
