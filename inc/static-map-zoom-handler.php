<?php
/**
 * 정적지도 줌 레벨 AJAX 핸들러
 * 
 * @package SungsuyaV2
 * @since 2.2.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX: 정적지도 줌 레벨 변경
 */
function ajax_generate_static_map_with_zoom() {
    // 디버깅 로그
    error_log('=== 정적지도 줌 변경 요청 ===');
    error_log('POST 데이터: ' . print_r($_POST, true));
    
    // Nonce 확인
    check_ajax_referer('static_map_zoom', 'nonce');
    
    // 권한 확인
    if (!current_user_can('read')) {
        wp_send_json_error('권한이 없습니다.');
        return;
    }
    
    // 파라미터 받기
    $place_id = intval($_POST['place_id']);
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);
    $zoom_level = intval($_POST['zoom_level']);
    
    error_log("Place ID: {$place_id}, Lat: {$latitude}, Lng: {$longitude}, Zoom: {$zoom_level}");
    
    // 유효성 검증
    if (!$place_id || !$latitude || !$longitude) {
        wp_send_json_error('잘못된 요청입니다. (place_id: ' . $place_id . ', lat: ' . $latitude . ', lng: ' . $longitude . ')');
        return;
    }
    
    // 줌 레벨 범위 제한
    $zoom_level = max(10, min(18, $zoom_level));
    
    // 장소 정보 가져오기
    $place = get_post($place_id);
    if (!$place || $place->post_type !== 'places') {
        wp_send_json_error('유효하지 않은 장소입니다.');
        return;
    }
    
    // 기존 줌 레벨과 같은 경우 캐시된 이미지 반환
    $cached_map_id = get_post_meta($place_id, 'static_map_zoom_' . $zoom_level, true);
    if ($cached_map_id && wp_attachment_is_image($cached_map_id)) {
        $cached_url = wp_get_attachment_url($cached_map_id);
        if ($cached_url) {
            error_log('캐시된 이미지 반환: ' . $cached_url);
            wp_send_json_success([
                'url' => $cached_url,
                'zoom_level' => $zoom_level,
                'cached' => true,
                'message' => '캐시된 지도를 불러왔습니다.',
                'debug' => [
                    'attachment_id' => $cached_map_id,
                    'place_id' => $place_id,
                    'zoom' => $zoom_level
                ]
            ]);
            return;
        }
    }
    
    // 기본 정적지도가 현재 줌 레벨과 같은 경우
    $current_zoom = get_post_meta($place_id, 'static_map_zoom_level', true);
    if ($current_zoom == $zoom_level) {
        $default_map_id = get_post_meta($place_id, 'static_map_image_id', true);
        if ($default_map_id && wp_attachment_is_image($default_map_id)) {
            $default_url = wp_get_attachment_url($default_map_id);
            if ($default_url) {
                error_log('기본 정적지도 반환 (동일 줌 레벨): ' . $default_url);
                wp_send_json_success([
                    'url' => $default_url,
                    'zoom_level' => $zoom_level,
                    'cached' => true,
                    'message' => '현재 지도를 사용합니다.',
                    'debug' => [
                        'attachment_id' => $default_map_id,
                        'place_id' => $place_id,
                        'zoom' => $zoom_level,
                        'is_default' => true
                    ]
                ]);
                return;
            }
        }
    }
    
    
    try {
        // 지도 크기 설정
        $width = isset($_POST['width']) ? intval($_POST['width']) : 600;
        $height = isset($_POST['height']) ? intval($_POST['height']) : 400;
        
        error_log("지도 생성 시작 - 크기: {$width}x{$height}");
        
        // 직접 정적지도 URL 생성 - API 키 확인
        $client_id = get_option('sungsuya_naver_client_id');
        $client_secret = get_option('sungsuya_naver_client_secret');
        
        // 기존 키 이름으로 재시도
        if (empty($client_id)) {
            $client_id = get_option('naver_client_id');
        }
        if (empty($client_secret)) {
            $client_secret = get_option('naver_client_secret');
        }
        
        // API 키가 없으면 에러 반환
        if (empty($client_id) || empty($client_secret)) {
            error_log('네이버 API 키가 설정되지 않았습니다.');
            wp_send_json_error('API 키가 설정되지 않았습니다. 관리자에게 문의하세요.');
            return;
        }
        
        error_log('사용할 API 키: ' . substr($client_id, 0, 5) . '...');
        
        $static_map_url = 'https://maps.apigw.ntruss.com/map-static/v2/raster';
        $params = [
            'w' => $width,
            'h' => $height,
            'center' => $longitude . ',' . $latitude,
            'level' => $zoom_level,
            'maptype' => 'basic',
            'format' => 'png',
            'scale' => 2,
            'markers' => sprintf('type:t|size:mid|pos:%s%%20%s|label:%s|color:red', 
                $longitude, 
                $latitude, 
                urlencode(substr($place->post_title, 0, 8))
            )
        ];
        
        $url = $static_map_url . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        
        error_log("정적지도 URL: " . $url);
        
        // API 호출
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-NCP-APIGW-API-KEY-ID: ' . $client_id,
                'X-NCP-APIGW-API-KEY: ' . $client_secret
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL 오류: ' . $error);
        }
        
        if ($http_code !== 200) {
            throw new Exception('API 오류 (HTTP ' . $http_code . ')');
        }
        
        // 이미지 저장
        $upload_dir = wp_upload_dir();
        $filename = 'place-map-' . $place_id . '-z' . $zoom_level . '.png';
        $file_path = $upload_dir['basedir'] . '/static-maps/' . $filename;
        
        if (!file_exists(dirname($file_path))) {
            wp_mkdir_p(dirname($file_path));
        }
        
        if (file_put_contents($file_path, $response) === false) {
            throw new Exception('파일 저장 실패');
        }
        
        // WordPress 미디어 라이브러리에 등록
        $attachment = [
            'guid' => $upload_dir['baseurl'] . '/static-maps/' . $filename,
            'post_mime_type' => 'image/png',
            'post_title' => $place->post_title . ' 지도 (줌 ' . $zoom_level . ')',
            'post_content' => '',
            'post_status' => 'inherit'
        ];
        
        $attachment_id = wp_insert_attachment($attachment, $file_path);
        
        if (!is_wp_error($attachment_id)) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $attachment_metadata);
            
            // 줌 레벨별 메타 저장
            update_post_meta($place_id, 'static_map_zoom_' . $zoom_level, $attachment_id);
            
            $attachment_url = wp_get_attachment_url($attachment_id);
            
            wp_send_json_success([
                'url' => $attachment_url,
                'zoom_level' => $zoom_level,
                'attachment_id' => $attachment_id,
                'file_path' => $file_path,
                'message' => '지도가 업데이트되었습니다.',
                'is_fallback' => false,
                'debug' => [
                    'place_id' => $place_id,
                    'lat' => $latitude,
                    'lng' => $longitude,
                    'requested_zoom' => $zoom_level,
                    'size' => $width . 'x' . $height
                ]
            ]);
        } else {
            throw new Exception('미디어 라이브러리 등록 실패');
        }
        
    } catch (Exception $e) {
        error_log('예외 발생: ' . $e->getMessage());
        wp_send_json_error('지도 생성 중 오류가 발생했습니다: ' . $e->getMessage());
    }
}
add_action('wp_ajax_generate_static_map_with_zoom', 'ajax_generate_static_map_with_zoom');
add_action('wp_ajax_nopriv_generate_static_map_with_zoom', 'ajax_generate_static_map_with_zoom');

/**
 * AJAX: 정적지도 사전 생성 (여러 줌 레벨)
 */
function ajax_pregenerate_static_maps() {
    // 관리자만 실행 가능
    if (!current_user_can('manage_options')) {
        wp_send_json_error('권한이 없습니다.');
        return;
    }
    
    $place_id = intval($_POST['place_id']);
    if (!$place_id) {
        wp_send_json_error('잘못된 요청입니다.');
        return;
    }
    
    $latitude = get_post_meta($place_id, 'latitude', true);
    $longitude = get_post_meta($place_id, 'longitude', true);
    
    if (!$latitude || !$longitude) {
        wp_send_json_error('좌표 정보가 없습니다.');
        return;
    }
    
    $place_name = get_the_title($place_id);
    $generator = new NaverStaticMapGenerator();
    
    // 여러 줌 레벨 생성
    $zoom_levels = [12, 14, 16];
    $generated = [];
    $errors = [];
    
    foreach ($zoom_levels as $level) {
        try {
            $result = $generator->generatePlaceMap(
                $place_id, 
                $latitude, 
                $longitude, 
                $place_name, 
                '', 
                $level
            );
            
            if ($result['success']) {
                $generated[] = $level;
            } else {
                $errors[] = "줌 {$level}: " . $result['error'];
            }
            
            // API 레이트 리미트 방지
            sleep(1);
            
        } catch (Exception $e) {
            $errors[] = "줌 {$level}: " . $e->getMessage();
        }
    }
    
    if (!empty($generated)) {
        wp_send_json_success([
            'generated' => $generated,
            'errors' => $errors,
            'message' => count($generated) . '개의 줌 레벨 지도가 생성되었습니다.'
        ]);
    } else {
        wp_send_json_error('지도 생성에 실패했습니다.');
    }
}
add_action('wp_ajax_pregenerate_static_maps', 'ajax_pregenerate_static_maps');

/**
 * 테스트용 간단한 핸들러
 */
function ajax_test_static_map() {
    wp_send_json_success(['message' => 'AJAX 작동 확인', 'time' => current_time('mysql')]);
}
add_action('wp_ajax_test_static_map', 'ajax_test_static_map');
add_action('wp_ajax_nopriv_test_static_map', 'ajax_test_static_map');

/**
 * 프론트엔드 스크립트에 필요한 데이터 전달
 */
function localize_static_map_data() {
    if (is_singular('places')) {
        // jQuery가 로드되지 않을 수 있으므로 wp-util 사용
        wp_enqueue_script('wp-util');
        
        // 인라인 스크립트로 데이터 전달
        wp_add_inline_script('wp-util', '
            window.staticMapData = {
                ajaxUrl: "' . admin_url('admin-ajax.php') . '",
                nonce: "' . wp_create_nonce('static_map_zoom') . '",
                defaultZoom: 14,
                minZoom: 10,
                maxZoom: 18
            };
        ', 'before');
    }
}
add_action('wp_enqueue_scripts', 'localize_static_map_data');
