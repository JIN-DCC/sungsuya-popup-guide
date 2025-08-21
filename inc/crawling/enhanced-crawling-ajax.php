<?php
/**
 * Enhanced 크롤링 AJAX 핸들러
 * 
 * 실제 데이터 크롤링을 처리하는 AJAX 핸들러
 * 
 * @package SungsuyaV2
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// 카카오 API 클라이언트 로드
require_once get_template_directory() . '/inc/crawling/api-clients/class-kakao-local-api-client.php';

// AJAX 액션 등록
add_action('wp_ajax_start_bulk_discovery', 'handle_start_bulk_discovery');
add_action('wp_ajax_check_bulk_progress', 'handle_check_bulk_progress');

/**
 * 대량 크롤링 시작
 */
function handle_start_bulk_discovery() {
    // 권한 확인
    if (!current_user_can('manage_options')) {
        wp_die('권한이 없습니다.');
    }
    
    // nonce 확인
    check_ajax_referer('enhanced_crawling_nonce', 'nonce');
    
    $source = sanitize_text_field($_POST['source'] ?? 'kakao');
    $categories = isset($_POST['categories']) ? array_map('sanitize_text_field', $_POST['categories']) : array('맛집');
    $place_type_id = intval($_POST['place_type_id'] ?? 0);
    $metafield_type = sanitize_text_field($_POST['metafield_type'] ?? '');
    $region = sanitize_text_field($_POST['region'] ?? '성수동');
    $limit = intval($_POST['limit'] ?? 20);
    
    // 세션 ID 생성
    $session_id = 'crawl_' . uniqid();
    
    // 진행 상황 저장을 위한 transient
    set_transient($session_id . '_status', array(
        'status' => 'running',
        'current_step' => '크롤링 시작 중...',
        'discovered_count' => 0,
        'created_posts' => array(),
        'log' => ''
    ), HOUR_IN_SECONDS);
    
    // 백그라운드 처리 시작 (즉시 실행으로 변경)
    // wp_schedule_single_event(time(), 'run_bulk_crawling', array($session_id, $source, $categories, $region, $limit));
    
    // 직접 실행 (장소유형 정보 추가)
    execute_bulk_crawling($session_id, $source, $categories, $region, $limit, $place_type_id, $metafield_type);
    
    wp_send_json_success(array(
        'session_id' => $session_id,
        'message' => '크롤링이 시작되었습니다.'
    ));
}

/**
 * 진행 상황 확인
 */
function handle_check_bulk_progress() {
    $session_id = sanitize_text_field($_POST['session_id'] ?? '');
    
    if (empty($session_id)) {
        wp_send_json_error('세션 ID가 없습니다.');
    }
    
    $status = get_transient($session_id . '_status');
    
    if (!$status) {
        wp_send_json_error('세션을 찾을 수 없습니다.');
    }
    
    wp_send_json_success($status);
}

/**
 * 실제 크롤링 실행 (백그라운드)
 */
add_action('run_bulk_crawling', 'execute_bulk_crawling', 10, 7);
function execute_bulk_crawling($session_id, $source, $categories, $region, $limit, $place_type_id = 0, $metafield_type = '') {
    $status = get_transient($session_id . '_status');
    $log = array();
    $created_posts = array();
    $discovered_count = 0;
    
    // 로그 함수
    $add_log = function($message) use (&$log, $session_id, &$status) {
        $log[] = '[' . current_time('H:i:s') . '] ' . $message;
        $status['log'] = implode("\n", $log);
        set_transient($session_id . '_status', $status, HOUR_IN_SECONDS);
    };
    
    $add_log("크롤링 시작: {$region} 지역, 카테고리: " . implode(', ', $categories));
    
    if ($source === 'kakao') {
        // 카카오 API 사용
        $client = new Kakao_Local_API_Client();
        
        foreach ($categories as $category) {
            $query = $region . ' ' . $category;
            $add_log("검색 중: {$query}");
            
            // 디버깅: API 키 확인
            if (!$client->api_key) {
                $add_log("⚠️ 경고: 카카오 API 키가 설정되지 않았습니다.");
            }
            
            $status['current_step'] = "'{$query}' 검색 중...";
            set_transient($session_id . '_status', $status, HOUR_IN_SECONDS);
            
            // API 호출
            $results = $client->search_places($query, array(
                'size' => min($limit, 15) // 카카오 API 최대 15개
            ));
            
            if (is_wp_error($results)) {
                $add_log("오류: " . $results->get_error_message());
                continue;
            }
            
            // 디버깅: API 응답 확인
            $add_log("🔍 API 응답 상태: " . (!empty($results) ? '성공' : '실패'));
            
            if (!empty($results['documents'])) {
                $discovered_count += count($results['documents']);
                $add_log(count($results['documents']) . "개 장소 발견");
                
                // 디버깅: 처음 3개 결과 표시
                $sample_names = array_slice(array_column($results['documents'], 'place_name'), 0, 3);
                $add_log("📦 예시: " . implode(', ', $sample_names));
                
                foreach ($results['documents'] as $place) {
                    // 중복 체크 (카카오 ID 또는 장소명으로 체크)
                    $existing = get_posts(array(
                        'post_type' => 'places',
                        'post_status' => array('publish', 'draft'),
                        'meta_query' => array(
                            'relation' => 'OR',
                            array(
                                'key' => 'kakao_id',
                                'value' => $place['id']
                            )
                        ),
                        'posts_per_page' => 1
                    ));
                    
                    // 장소명으로도 중복 체크
                    if (empty($existing)) {
                        $existing = get_posts(array(
                            'post_type' => 'places',
                            'post_status' => array('publish', 'draft'),
                            'title' => $place['place_name'],
                            'posts_per_page' => 1
                        ));
                    }
                    
                    if (!empty($existing)) {
                        $add_log("중복: " . $place['place_name']);
                        continue;
                    }
                    
                    // 포스트 데이터 변환
                    $post_data = $client->convert_to_post_data($place);
                    
                    // 포스트 생성
                    $post_id = wp_insert_post($post_data);
                    
                    if ($post_id && !is_wp_error($post_id)) {
                        $add_log("✅ 생성: " . $place['place_name']);
                        
                        // 장소유형 설정
                        if ($place_type_id) {
                            // popup_store를 popup-store로 자동 변환
                            $term = get_term($place_type_id, 'place_type');
                            if ($term && $term->slug === 'popup_store') {
                                $correct_term = get_term_by('slug', 'popup-store', 'place_type');
                                if ($correct_term) {
                                    $place_type_id = $correct_term->term_id;
                                    $add_log("🔄 popup_store → popup-store로 변환");
                                }
                            }
                            wp_set_post_terms($post_id, array($place_type_id), 'place_type');
                            $add_log("🎯 장소유형 설정: " . $categories[0]);
                        } else {
                            // 기본 장소유형 설정 (카테고리로 자동 판단)
                            $default_type = determine_place_type_by_category($category);
                            if ($default_type) {
                                $term = get_term_by('slug', $default_type, 'place_type');
                                if ($term) {
                                    wp_set_post_terms($post_id, array($term->term_id), 'place_type');
                                    $add_log("🎯 자동 장소유형 설정: " . $default_type);
                                }
                            }
                        }
                        
                        // 메타필드 타입에 따른 추가 크롤링 (나중에 구현)
                        if ($metafield_type === 'food') {
                            // TODO: 음식점 메타필드 크롤링
                            $add_log("🍽️ 음식점 타입 메타필드 크롤링 준비중");
                        } elseif ($metafield_type === 'shop') {
                            // TODO: 매장 메타필드 크롤링
                            $add_log("🛍️ 매장 타입 메타필드 크롤링 준비중");
                        }
                        
                        // 정적지도 생성
                        if (function_exists('generate_and_save_static_map')) {
                            generate_and_save_static_map($post_id);
                        }
                        
                        $created_posts[] = array(
                            'post_id' => $post_id,
                            'name' => $place['place_name']
                        );
                    } else {
                        $add_log("❌ 생성 실패: " . $place['place_name']);
                    }
                }
            }
            
            // API 제한을 위한 대기
            sleep(1);
        }
    } else {
        // 네이버 API 사용 (구현 필요)
        $add_log("네이버 API는 아직 구현되지 않았습니다.");
    }
    
    // 최종 결과 저장
    $status['status'] = 'completed';
    $status['current_step'] = '크롤링 완료';
    $status['discovered_count'] = $discovered_count;
    $status['created_posts'] = $created_posts;
    $add_log("크롤링 완료: 발견 {$discovered_count}개, 생성 " . count($created_posts) . "개");
    
    set_transient($session_id . '_status', $status, HOUR_IN_SECONDS);
}

/**
 * 카테고리명으로 장소유형 자동 판단
 */
function determine_place_type_by_category($category) {
    $category_lower = strtolower($category);
    
    if (strpos($category_lower, '맛집') !== false || strpos($category_lower, '식당') !== false || strpos($category_lower, '음식') !== false) {
        return 'restaurant';
    } elseif (strpos($category_lower, '카페') !== false || strpos($category_lower, '커피') !== false) {
        return 'cafe';
    } elseif (strpos($category_lower, '팝업') !== false || strpos($category_lower, '전시') !== false) {
        return 'popup_store';
    } elseif (strpos($category_lower, '쇼핑') !== false || strpos($category_lower, '매장') !== false) {
        return 'retail_store';
    } else {
        return 'facility'; // 기타
    }
}

/**
 * 개별 장소 크롤링 (단일)
 */
add_action('wp_ajax_crawl_single_place', 'handle_crawl_single_place');
function handle_crawl_single_place() {
    if (!current_user_can('edit_posts')) {
        wp_die('권한이 없습니다.');
    }
    
    $place_name = sanitize_text_field($_POST['place_name'] ?? '');
    $address = sanitize_text_field($_POST['address'] ?? '');
    
    if (empty($place_name)) {
        wp_send_json_error('장소명이 필요합니다.');
    }
    
    $client = new Kakao_Local_API_Client();
    $query = $place_name . ' ' . $address;
    
    $results = $client->search_places($query, array('size' => 1));
    
    if (is_wp_error($results)) {
        wp_send_json_error($results->get_error_message());
    }
    
    if (empty($results['documents'])) {
        wp_send_json_error('검색 결과가 없습니다.');
    }
    
    $place = $results['documents'][0];
    $post_data = $client->convert_to_post_data($place);
    
    $post_id = wp_insert_post($post_data);
    
    if ($post_id && !is_wp_error($post_id)) {
        // 정적지도 생성
        if (function_exists('generate_and_save_static_map')) {
            generate_and_save_static_map($post_id);
        }
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'message' => '장소가 생성되었습니다.',
            'edit_link' => get_edit_post_link($post_id)
        ));
    } else {
        wp_send_json_error('포스트 생성 실패');
    }
}
