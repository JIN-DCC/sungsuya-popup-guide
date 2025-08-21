<?php
/**
 * 크롤링 AJAX 핸들러
 * 
 * Enhanced 크롤링 시스템의 AJAX 처리
 * 
 * @package SungsuyaV2
 * @subpackage Crawling
 * @version 2.0.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 크롤링 AJAX 핸들러 등록
 */
add_action('wp_ajax_start_bulk_discovery', 'sungsuya_ajax_start_bulk_discovery');
add_action('wp_ajax_check_bulk_progress', 'sungsuya_ajax_check_bulk_progress');
add_action('wp_ajax_stop_bulk_crawling', 'sungsuya_ajax_stop_bulk_crawling');
add_action('wp_ajax_pause_bulk_crawling', 'sungsuya_ajax_pause_bulk_crawling');
add_action('wp_ajax_resume_bulk_crawling', 'sungsuya_ajax_resume_bulk_crawling');
add_action('wp_ajax_fix_bulk_crawled_coordinates', 'sungsuya_ajax_fix_bulk_crawled_coordinates');
add_action('wp_ajax_nopriv_process_crawling_background', 'sungsuya_process_crawling_background');
add_action('wp_ajax_process_crawling_background', 'sungsuya_process_crawling_background');

/**
 * 백그라운드 크롤링 처리
 */
function sungsuya_process_crawling_background() {
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    if ($session_id) {
        sungsuya_process_bulk_crawling_handler($session_id);
    }
    wp_die();
}

/**
 * 대량 크롤링 시작
 */
function sungsuya_ajax_start_bulk_discovery() {
    // 권한 확인
    if (!current_user_can('manage_options')) {
        wp_send_json_error('권한이 없습니다.');
    }
    
    // Nonce 확인
    check_ajax_referer('enhanced_crawling_nonce', 'nonce');
    
    // 파라미터 수집
    $categories = isset($_POST['categories']) ? (array)$_POST['categories'] : array('맛집');
    $region = isset($_POST['region']) ? sanitize_text_field($_POST['region']) : '성수동';
    $strategy = isset($_POST['strategy']) ? sanitize_text_field($_POST['strategy']) : 'balanced';
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
    
    // 세션 ID 생성
    $session_id = 'crawl_' . uniqid();
    
    // 크롤링 세션 데이터 초기화
    $session_data = array(
        'session_id' => $session_id,
        'status' => 'running',
        'categories' => $categories,
        'region' => $region,
        'strategy' => $strategy,
        'limit' => $limit,
        'source' => isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'naver', // API 소스
        'start_time' => time(),
        'discovered_count' => 0,
        'created_posts' => array(),
        'current_step' => '크롤링 준비 중...',
        'overall_progress' => 0,
        'log' => ''
    );
    
    // 세션 데이터 저장
    set_transient('crawling_session_' . $session_id, $session_data, HOUR_IN_SECONDS);
    
    // 비동기로 크롤링 처리
    wp_remote_post(admin_url('admin-ajax.php'), array(
        'timeout' => 0.01,
        'blocking' => false,
        'body' => array(
            'action' => 'process_crawling_background',
            'session_id' => $session_id
        )
    ));
    
    wp_send_json_success(array(
        'session_id' => $session_id,
        'message' => '크롤링이 시작되었습니다.'
    ));
}

/**
 * 크롤링 진행상황 확인
 */
function sungsuya_ajax_check_bulk_progress() {
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    
    if (empty($session_id)) {
        wp_send_json_error('세션 ID가 필요합니다.');
    }
    
    $session_data = get_transient('crawling_session_' . $session_id);
    
    if (!$session_data) {
        wp_send_json_error('세션을 찾을 수 없습니다.');
    }
    
    wp_send_json_success($session_data);
}

/**
 * 크롤링 중지
 */
function sungsuya_ajax_stop_bulk_crawling() {
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
    
    if (empty($session_id)) {
        wp_send_json_error('세션 ID가 필요합니다.');
    }
    
    $session_data = get_transient('crawling_session_' . $session_id);
    
    if ($session_data) {
        $session_data['status'] = 'stopped';
        set_transient('crawling_session_' . $session_id, $session_data, HOUR_IN_SECONDS);
    }
    
    wp_send_json_success(array(
        'message' => '크롤링이 중지되었습니다.'
    ));
}

/**
 * 실제 크롤링 처리 (백그라운드)
 */
add_action('sungsuya_process_bulk_crawling', 'sungsuya_process_bulk_crawling_handler');
function sungsuya_process_bulk_crawling_handler($session_id) {
    $session_data = get_transient('crawling_session_' . $session_id);
    
    if (!$session_data || $session_data['status'] !== 'running') {
        return;
    }
    
    // API 클라이언트 로드
    $api_client = null;
    
    if ($session_data['source'] === 'kakao') {
        // 카카오 API 클라이언트
        require_once get_template_directory() . '/inc/crawlers/class-kakao-local-api.php';
        $api_client = new KakaoLocalAPIClient();
    } else {
        // 네이버 API 클라이언트 (기본)
        require_once get_template_directory() . '/inc/crawlers/class-naver-search-api.php';
        $api_client = new NaverSearchAPIClient();
    }
    
    // API 설정 확인
    if (!$api_client->is_configured()) {
        $session_data['status'] = 'error';
        $session_data['log'] .= sprintf("[ERROR] %s API가 설정되지 않았습니다.\n", ucfirst($session_data['source']));
        set_transient('crawling_session_' . $session_id, $session_data, HOUR_IN_SECONDS);
        return;
    }
    
    $discovered_items = array();
    $total_limit = $session_data['limit'];
    $per_category_limit = ceil($total_limit / count($session_data['categories']));
    
    // 각 카테고리별로 검색
    foreach ($session_data['categories'] as $category) {
        $session_data['current_step'] = sprintf('%s 카테고리 검색 중...', $category);
        $session_data['log'] .= sprintf("[INFO] %s %s 검색 시작\n", $session_data['region'], $category);
        
        // 검색어 조합
        $query = $session_data['region'] . ' ' . $category;
        
        // API 호출
        if ($session_data['source'] === 'kakao') {
            // 카카오 API 호출
            $search_result = $api_client->search_keyword($query, array(
                'size' => min($per_category_limit, 45),
                'sort' => $session_data['strategy'] === 'quality' ? 'accuracy' : 'accuracy'
            ));
        } else {
            // 네이버 API 호출
            $search_result = $api_client->search_local($query, array(
                'display' => min($per_category_limit, 50),
                'sort' => $session_data['strategy'] === 'quality' ? 'comment' : 'random'
            ));
        }
        
        if (is_wp_error($search_result)) {
            $session_data['log'] .= sprintf("[ERROR] %s: %s\n", $category, $search_result->get_error_message());
            continue;
        }
        
        if (!empty($search_result['items'])) {
            $discovered_items = array_merge($discovered_items, $search_result['items']);
            $session_data['discovered_count'] += count($search_result['items']);
            $session_data['log'] .= sprintf("[SUCCESS] %s: %d개 발견\n", $category, count($search_result['items']));
        }
        
        // 진행률 업데이트
        $session_data['overall_progress'] = (count($discovered_items) / $total_limit) * 50; // 발견 50%
        set_transient('crawling_session_' . $session_id, $session_data, HOUR_IN_SECONDS);
        
        // 잠시 대기 (API 제한 방지)
        sleep(1);
    }
    
    // WordPress 포스트 생성
    $session_data['current_step'] = 'WordPress 포스트 생성 중...';
    $session_data['log'] .= "\n[INFO] 포스트 생성 시작\n";
    
    $created_count = 0;
    $duplicate_check = get_option('sungsuya_duplicate_check', 'yes') === 'yes';
    $auto_publish = get_option('sungsuya_auto_publish', 'yes') === 'yes';
    
    foreach ($discovered_items as $index => $item) {
        // 중복 체크
        if ($duplicate_check) {
            $existing_id = sungsuya_check_duplicate_place($item['name'], $item['address']);
            if ($existing_id) {
                $session_data['log'] .= sprintf("[SKIP] %s: 이미 존재함 (ID: %d)\n", $item['name'], $existing_id);
                continue;
            }
        }
        
        // 메타필드 변환
        $meta_data = $api_client->convert_to_wordpress_meta($item);
        
        // 포스트 생성
        $post_data = array(
            'post_title' => $item['name'],
            'post_type' => 'places',
            'post_status' => $auto_publish ? 'publish' : 'draft',
            'post_content' => $item['description'],
            'meta_input' => $meta_data
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (!is_wp_error($post_id)) {
            // 장소 유형 설정
            wp_set_object_terms($post_id, $item['place_type'], 'place_type');
            
            // 카테고리 설정
            if (!empty($item['clean_category'])) {
                wp_set_object_terms($post_id, $item['clean_category'], 'place_category');
            }
            
            $created_count++;
            $session_data['created_posts'][] = array(
                'post_id' => $post_id,
                'name' => $item['name'],
                'address' => $meta_data['address'],
                'action' => 'created'
            );
            
            $session_data['log'] .= sprintf("[SUCCESS] %s: 포스트 생성됨 (ID: %d)\n", $item['name'], $post_id);
        } else {
            $session_data['log'] .= sprintf("[ERROR] %s: 포스트 생성 실패\n", $item['name']);
        }
        
        // 진행률 업데이트
        $session_data['overall_progress'] = 50 + (($index + 1) / count($discovered_items)) * 40; // 생성 40%
        set_transient('crawling_session_' . $session_id, $session_data, HOUR_IN_SECONDS);
    }
    
    // 자동 주소검색 트리거
    if ($created_count > 0) {
        $session_data['current_step'] = '주소 검색 및 좌표 변환 중...';
        $session_data['log'] .= "\n[INFO] 자동 주소검색 시작\n";
        
        $address_search_results = sungsuya_trigger_bulk_address_search($session_data['created_posts']);
        $session_data['address_search_results'] = $address_search_results;
        $session_data['auto_address_search_completed'] = true;
        
        $session_data['log'] .= sprintf("[SUCCESS] 주소검색 완료: %d/%d 성공\n", 
            $address_search_results['success_count'], 
            $address_search_results['total_count']
        );
    }
    
    // 완료
    $session_data['status'] = 'completed';
    $session_data['current_step'] = '크롤링 완료!';
    $session_data['overall_progress'] = 100;
    $session_data['end_time'] = time();
    
    $session_data['log'] .= sprintf("\n[COMPLETE] 총 %d개 발견, %d개 생성\n", 
        $session_data['discovered_count'], 
        $created_count
    );
    
    set_transient('crawling_session_' . $session_id, $session_data, HOUR_IN_SECONDS);
}

/**
 * 중복 장소 체크
 */
function sungsuya_check_duplicate_place($name, $address) {
    global $wpdb;
    
    $query = $wpdb->prepare(
        "SELECT p.ID 
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'places' 
        AND p.post_status IN ('publish', 'draft')
        AND p.post_title = %s
        AND pm.meta_key = 'address' 
        AND pm.meta_value = %s
        LIMIT 1",
        $name, 
        $address
    );
    
    return $wpdb->get_var($query);
}

/**
 * 대량 주소검색 트리거
 */
function sungsuya_trigger_bulk_address_search($created_posts) {
    $results = array(
        'total_count' => count($created_posts),
        'success_count' => 0,
        'results' => array()
    );
    
    // 지오코딩 매니저 로드
    if (function_exists('handle_address_search_ajax')) {
        foreach ($created_posts as $post_data) {
            // 주소검색 실행 (geocoding-manager.php의 함수 활용)
            $_POST['post_id'] = $post_data['post_id'];
            $_POST['address'] = $post_data['address'];
            
            // 직접 지오코딩 실행
            $geocoding_result = sungsuya_geocode_address($post_data['address']);
            
            if ($geocoding_result && !is_wp_error($geocoding_result)) {
                // 좌표 저장
                update_post_meta($post_data['post_id'], 'latitude', $geocoding_result['lat']);
                update_post_meta($post_data['post_id'], 'longitude', $geocoding_result['lng']);
                
                // 정적지도 생성
                $static_map = sungsuya_generate_static_map_url($geocoding_result['lat'], $geocoding_result['lng']);
                update_post_meta($post_data['post_id'], 'static_map_url', $static_map);
                
                $results['success_count']++;
                $results['results'][] = array(
                    'post_id' => $post_data['post_id'],
                    'name' => $post_data['name'],
                    'status' => 'success',
                    'geocoding' => $geocoding_result,
                    'static_map' => $static_map ? '생성됨' : '실패'
                );
            } else {
                $results['results'][] = array(
                    'post_id' => $post_data['post_id'],
                    'name' => $post_data['name'],
                    'status' => 'failed',
                    'error' => '좌표 변환 실패'
                );
            }
        }
    }
    
    $results['success_rate'] = round(($results['success_count'] / $results['total_count']) * 100);
    
    return $results;
}

/**
 * 지오코딩 실행 (간단 버전)
 */
function sungsuya_geocode_address($address) {
    $naver_client_id = get_option('sungsuya_naver_client_id');
    $naver_client_secret = get_option('sungsuya_naver_client_secret');
    
    if (empty($naver_client_id) || empty($naver_client_secret)) {
        return false;
    }
    
    $url = 'https://naveropenapi.apigw.ntruss.com/map-geocode/v2/geocode';
    $url .= '?query=' . urlencode($address);
    
    $response = wp_remote_get($url, array(
        'headers' => array(
            'X-NCP-APIGW-API-KEY-ID' => $naver_client_id,
            'X-NCP-APIGW-API-KEY' => $naver_client_secret
        )
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!empty($data['addresses'][0])) {
        return array(
            'lat' => $data['addresses'][0]['y'],
            'lng' => $data['addresses'][0]['x']
        );
    }
    
    return false;
}

/**
 * 정적지도 URL 생성
 */
function sungsuya_generate_static_map_url($lat, $lng) {
    $naver_client_id = get_option('sungsuya_naver_client_id');
    $naver_client_secret = get_option('sungsuya_naver_client_secret');
    
    if (empty($naver_client_id) || empty($naver_client_secret)) {
        return false;
    }
    
    $params = array(
        'center' => $lng . ',' . $lat,
        'level' => 16,
        'w' => 600,
        'h' => 400,
        'markers' => 'type:d|size:mid|pos:' . $lng . '%20' . $lat
    );
    
    return 'https://naveropenapi.apigw.ntruss.com/map-static/v2/raster?' . http_build_query($params);
}
