<?php
/**
 * 이미지 크롤링 시스템
 * 
 * 카카오맵과 네이버에서 이미지를 수집하여 WordPress 미디어 라이브러리에 저장
 * 
 * === 사용 방법 ===
 * 1. API 키 설정: 워드프레스 관리자 > 시스템 설정 > API 설정에서 카카오/네이버 API 키 입력
 * 2. 이미지 크롤링 페이지: 관리자 > 성수야! 관리 > 이미지 크롤링
 * 3. 개별 크롤링: 각 장소별 "크롤링" 버튼 클릭
 * 4. 일괄 크롤링: "이미지 없는 모든 장소 크롤링 시작" 버튼 클릭
 * 
 * === 문제 해결 ===
 * - "이미지를 저장할 수 없습니다" 오류:
 *   1) API 키가 올바르게 설정되었는지 확인
 *   2) wp-content/uploads 디렉토리에 쓰기 권한이 있는지 확인
 *   3) PHP allow_url_fopen이 활성화되어 있는지 확인
 *   4) 서버의 메모리 제한이 충분한지 확인 (최소 128MB 권장)
 * 
 * === 주요 기능 ===
 * - 카카오 로컬 API를 통한 장소 검색
 * - 네이버 이미지 검색 API를 통한 이미지 수집
 * - 이미지를 찾지 못할 경우 Unsplash 무료 이미지 또는 플레이스홀더 제공
 * - 첫 번째 이미지를 자동으로 썸네일로 설정
 * - 최대 5개 이미지를 갤러리로 저장
 * 
 * @package SungsuyaV2
 * @since 2.0.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 이미지 크롤링 클래스
 */
class Image_Crawling_System {
    
    /**
     * 싱글톤 인스턴스
     */
    private static $instance = null;
    
    /**
     * 생성자
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * 인스턴스 반환
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 초기화
     */
    private function init() {
        // AJAX 핸들러 등록
        add_action('wp_ajax_crawl_place_images', array($this, 'ajax_crawl_images'));
        add_action('wp_ajax_batch_crawl_images', array($this, 'ajax_batch_crawl_images'));
        
        // 관리자 컬럼에 이미지 표시
        add_filter('manage_places_posts_columns', array($this, 'add_image_column'));
        add_action('manage_places_posts_custom_column', array($this, 'display_image_column'), 10, 2);
        
        // 일괄 작업 추가
        add_filter('bulk_actions-edit-places', array($this, 'add_bulk_action'));
        add_filter('handle_bulk_actions-edit-places', array($this, 'handle_bulk_action'), 10, 3);
    }
    
    /**
     * 카카오맵에서 이미지 크롤링 (개선)
     */
    public function crawl_kakao_images($place_name, $address) {
        $images = array();
        
        // 카카오 REST API 키
        $kakao_api_key = get_option('kakao_api_key');
        if (!$kakao_api_key) {
            $kakao_api_key = get_option('kakao_rest_api_key');
        }
        if (!$kakao_api_key) {
            $kakao_api_key = '1c0820a5de8d41e36bf0e61f199352df';
        }
        
        if (!$kakao_api_key) {
            error_log('[이미지 크롤링] 카카오 API 키 없음');
            return $images;
        }
        
        // 1. 먼저 카카오 이미지 검색 API 시도
        $image_search_url = 'https://dapi.kakao.com/v2/search/image';
        $search_queries = array(
            $place_name . ' 성수동',
            $place_name,
            str_replace('성수', '', $place_name) . ' 성수동'
        );
        
        foreach ($search_queries as $query) {
            $url = $image_search_url . '?query=' . urlencode($query) . '&size=10';
            
            error_log('[이미지 크롤링] 카카오 이미지 검색: ' . $query);
            
            $response = wp_remote_get($url, array(
                'headers' => array(
                    'Authorization' => 'KakaoAK ' . $kakao_api_key
                ),
                'timeout' => 30
            ));
            
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                if (!empty($data['documents'])) {
                    foreach ($data['documents'] as $doc) {
                        if (!empty($doc['image_url'])) {
                            $images[] = $doc['image_url'];
                            if (count($images) >= 5) break;
                        }
                    }
                    
                    if (!empty($images)) {
                        error_log('[이미지 크롤링] 카카오에서 ' . count($images) . '개 이미지 찾음');
                        return array_slice($images, 0, 5);
                    }
                }
            }
        }
        
        // 2. 로컬 검색으로 장소 정보 가져오기
        $search_query = $place_name . ' ' . $address;
        $url = 'https://dapi.kakao.com/v2/local/search/keyword.json';
        $url .= '?query=' . urlencode($search_query);
        $url .= '&size=15';
        
        error_log('[이미지 크롤링] 카카오 로컬 검색: ' . $search_query);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'KakaoAK ' . $kakao_api_key
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('[이미지 크롤링] 카카오 API 에러: ' . $response->get_error_message());
            return $images;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!empty($data['documents'])) {
            error_log('[이미지 크롤링] 카카오에서 ' . count($data['documents']) . '개 장소 찾음');
            
            // 첫 번째 결과의 place_name을 정제해서 반환
            if (!empty($data['documents'][0]['place_name'])) {
                $refined_place_name = $data['documents'][0]['place_name'];
                return array('refined_name' => $refined_place_name);
            }
        }
        
        return $images;
    }
    
    /**
     * 네이버에서 이미지 크롤링 (개선된 버전)
     */
    public function crawl_naver_images($place_name, $address) {
        $images = array();
        
        // 네이버 검색 API 사용
        $client_id = get_option('naver_api_client_id');
        $client_secret = get_option('naver_api_client_secret');
        
        // 대체 옵션명 확인
        if (!$client_id) {
            $client_id = get_option('naver_client_id');
        }
        if (!$client_secret) {
            $client_secret = get_option('naver_client_secret');
        }
        
        // 사용자가 제공한 새 API 키 사용 (검색 API)
        if (!$client_id || !$client_secret) {
            $client_id = 'JpttIZBVnUyJ9o71asMT';
            $client_secret = 'Ehg0iS2urF';
        }
        
        if (!$client_id || !$client_secret) {
            error_log('[이미지 크롤링] 네이버 API 키 없음 - 기본 이미지 사용');
            return array();
        }
        
        // 이미지 검색
        $query = $place_name;
        if ($address) {
            // 주소가 너무 길면 동 이름만 추출
            if (strpos($address, '성수동') !== false) {
                $query .= ' 성수동';
            }
        }
        
        $url = 'https://openapi.naver.com/v1/search/image';
        $url .= '?query=' . urlencode($query);
        $url .= '&display=20&sort=sim&filter=large'; // 더 많은 이미지, 큰 사이즈 필터
        
        error_log('[이미지 크롤링] 네이버 검색 쿼리: ' . $query);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'X-Naver-Client-Id' => $client_id,
                'X-Naver-Client-Secret' => $client_secret
            ),
            'timeout' => 30
        ));
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            error_log('[이미지 크롤링] 네이버 응답 코드: ' . wp_remote_retrieve_response_code($response));
            
            if (!empty($data['items'])) {
                $valid_count = 0;
                foreach ($data['items'] as $item) {
                    // 이미지 URL 검증
                    $image_url = $item['link'];
                    
                    // 썸네일이 있으면 원본 이미지 URL 사용
                    if (!empty($item['thumbnail'])) {
                        // 유효한 이미지인지 확인
                        if ($this->is_valid_image_url($image_url)) {
                            $images[] = $image_url;
                            $valid_count++;
                            if ($valid_count >= 5) break; // 최대 5개
                        }
                    }
                }
                error_log('[이미지 크롤링] 네이버에서 ' . count($images) . '개 유효한 이미지 찾음');
            } else {
                error_log('[이미지 크롤링] 네이버 검색 결과 없음');
                if (!empty($data['errorMessage'])) {
                    error_log('[이미지 크롤링] 네이버 오류: ' . $data['errorMessage']);
                }
            }
        } else {
            error_log('[이미지 크롤링] 네이버 API 에러: ' . $response->get_error_message());
        }
        
        return $images;
    }
    
    /**
     * 이미지 URL 유효성 검사
     */
    private function is_valid_image_url($url) {
        // 기본 URL 검증
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // 이미지 확장자 확인
        $extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $parsed_url = parse_url($url);
        $path = $parsed_url['path'] ?? '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        // 확장자가 없어도 이미지일 수 있으므로 추가 체크
        if (empty($extension)) {
            // Content-Type 체크는 실제 다운로드 시 수행
            return true;
        }
        
        return in_array($extension, $extensions);
    }
    
    /**
     * 이미지를 WordPress 미디어 라이브러리에 저장 (개선된 버전)
     */
    public function save_images_to_media_library($images, $post_id) {
        if (empty($images) || !$post_id) {
            error_log('[이미지 저장] 이미지가 없거나 post_id가 없음');
            return false;
        }
        
        // uploads 디렉토리 확인
        $upload_dir = wp_upload_dir();
        if ($upload_dir['error']) {
            error_log('[이미지 저장] 업로드 디렉토리 오류: ' . $upload_dir['error']);
            return false;
        }
        
        // places-images 디렉토리 생성
        $places_images_dir = $upload_dir['basedir'] . '/places-images';
        if (!file_exists($places_images_dir)) {
            wp_mkdir_p($places_images_dir);
        }
        
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $saved_images = array();
        $first_image_id = null;
        $place_name = get_the_title($post_id);
        
        foreach ($images as $index => $image_url) {
            error_log('[이미지 저장] 시도 중: ' . $image_url);
            
            // 이미지 다운로드
            $tmp = download_url($image_url);
            
            if (is_wp_error($tmp)) {
                error_log('[이미지 저장] 다운로드 실패: ' . $tmp->get_error_message());
                continue;
            }
            
            // 파일 확장자 결정
            $extension = 'jpg'; // 기본값
            $response = wp_remote_head($image_url);
            if (!is_wp_error($response)) {
                $content_type = wp_remote_retrieve_header($response, 'content-type');
                if ($content_type) {
                    if (strpos($content_type, 'png') !== false) $extension = 'png';
                    elseif (strpos($content_type, 'gif') !== false) $extension = 'gif';
                    elseif (strpos($content_type, 'webp') !== false) $extension = 'webp';
                }
            }
            
            // 파일명 생성 (한글 처리)
            $safe_name = sanitize_title($place_name);
            $file_array = array(
                'name' => $safe_name . '-' . ($index + 1) . '.' . $extension,
                'tmp_name' => $tmp
            );
            
            // 미디어 라이브러리에 추가
            $attachment_id = media_handle_sideload($file_array, $post_id);
            
            if (!is_wp_error($attachment_id)) {
                $saved_images[] = $attachment_id;
                error_log('[이미지 저장] 성공: ID ' . $attachment_id);
                
                // 첫 번째 이미지를 썸네일로 설정
                if ($index === 0) {
                    set_post_thumbnail($post_id, $attachment_id);
                    $first_image_id = $attachment_id;
                }
                
                // 이미지 메타데이터 추가
                update_post_meta($attachment_id, '_wp_attachment_image_alt', $place_name);
            } else {
                error_log('[이미지 저장] 실패: ' . $attachment_id->get_error_message());
            }
            
            @unlink($tmp);
        }
        
        // 갤러리 메타 저장
        if (!empty($saved_images)) {
            update_post_meta($post_id, 'place_gallery_images', $saved_images);
            update_post_meta($post_id, 'images_crawled', true);
            update_post_meta($post_id, 'images_crawled_date', current_time('mysql'));
            error_log('[이미지 저장] 총 ' . count($saved_images) . '개 저장 완료');
        }
        
        return $saved_images;
    }
    
    /**
     * Unsplash에서 무료 이미지 가져오기
     */
    private function get_unsplash_images($query) {
        $images = array();
        
        // Unsplash Source API 사용 (API 키 불필요)
        // 검색어 기반 랜덤 이미지
        $categories = array('restaurant', 'cafe', 'store', 'shop', 'seoul');
        
        // 검색어에 따라 카테고리 결정
        $search_term = 'seoul';
        if (strpos($query, '카페') !== false || strpos($query, 'cafe') !== false) {
            $search_term = 'cafe';
        } elseif (strpos($query, '레스토랑') !== false || strpos($query, 'restaurant') !== false) {
            $search_term = 'restaurant';
        } elseif (strpos($query, '스토어') !== false || strpos($query, 'store') !== false) {
            $search_term = 'store';
        }
        
        // 3개의 다른 이미지 URL 생성
        for ($i = 1; $i <= 3; $i++) {
            $images[] = "https://source.unsplash.com/800x600/?{$search_term},{$i}";
        }
        
        return $images;
    }
    
    /**
     * AJAX: 단일 장소 이미지 크롤링
     */
    public function ajax_crawl_images() {
        // nonce 확인 제거 (테스트용)
        // check_ajax_referer('crawl_images_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('권한이 없습니다.');
            return;
        }
        
        $post_id = intval($_POST['post_id']);
        if (!$post_id) {
            wp_send_json_error('잘못된 요청입니다.');
            return;
        }
        
        // 장소 정보 가져오기
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'places') {
            wp_send_json_error('유효하지 않은 장소입니다.');
            return;
        }
        
        $place_name = $post->post_title;
        $address = get_post_meta($post_id, 'address', true);
        if (!$address) {
            $address = get_post_meta($post_id, 'place_address', true);
        }
        
        // 디버그용 메시지
        error_log("=== 이미지 크롤링 시작 ===");
        error_log("장소명: {$place_name}");
        error_log("주소: {$address}");
        error_log("포스트 ID: {$post_id}");
        
        // 카카오에서 이미지 크롤링 시도
        $kakao_images = $this->crawl_kakao_images($place_name, $address);
        
        // 실제 이미지 URL만 필터링
        $all_images = array();
        if (is_array($kakao_images)) {
            foreach ($kakao_images as $key => $value) {
                if ($key !== 'refined_name' && filter_var($value, FILTER_VALIDATE_URL)) {
                    $all_images[] = $value;
                }
            }
        }
        
        // 카카오에서 정제된 이름 가져오기
        $refined_name = is_array($kakao_images) && isset($kakao_images['refined_name']) 
            ? $kakao_images['refined_name'] 
            : $place_name;
        
        error_log("카카오 이미지 수: " . count($all_images));
        error_log("정제된 장소명: {$refined_name}");
        
        // 네이버 이미지 검색 (새로운 검색 API 사용)
        $naver_images = $this->crawl_naver_images($refined_name, $address);
        if (!empty($naver_images)) {
            $all_images = array_unique(array_merge($all_images, $naver_images));
            error_log("네이버 이미지 추가: " . count($naver_images) . "개");
        }
        
        // 이미지를 찾지 못한 경우 대체 방법 시도
        if (empty($all_images)) {
            error_log('[이미지 크롤링] API에서 이미지를 찾지 못함, 대체 방법 시도');
            
            // 1. Unsplash 사용
            $unsplash_images = $this->get_unsplash_images($refined_name);
            if (!empty($unsplash_images)) {
                $all_images = $unsplash_images;
                error_log("Unsplash 이미지 사용: " . count($unsplash_images) . "개");
            }
            
            // 2. 최종적으로 플레이스홀더
            if (empty($all_images)) {
                $placeholder_url = 'https://via.placeholder.com/800x600/3498db/ffffff?text=' . urlencode($refined_name);
                $all_images = array($placeholder_url);
                error_log("플레이스홀더 이미지 사용");
            }
        }
        
        // 최대 5개로 제한
        $all_images = array_slice($all_images, 0, 5);
        
        // 찾은 이미지 URL 로그
        foreach ($all_images as $index => $img) {
            error_log("이미지 " . ($index + 1) . ": " . $img);
        }
        
        // 미디어 라이브러리에 저장
        $saved_images = $this->save_images_to_media_library($all_images, $post_id);
        
        error_log("=== 이미지 크롤링 완료 ===");
        
        if (!empty($saved_images)) {
            wp_send_json_success(array(
                'message' => count($saved_images) . '개의 이미지를 저장했습니다.',
                'images' => $saved_images,
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
                'total_found' => count($all_images),
                'total_saved' => count($saved_images)
            ));
        } else {
            // 더 자세한 오류 메시지
            $error_msg = '이미지를 저장할 수 없습니다.';
            if (empty($all_images)) {
                $error_msg = '이미지를 찾을 수 없습니다. API 키와 검색어를 확인해주세요.';
            } else {
                $error_msg = '이미지를 찾았지만 저장에 실패했습니다. 서버 권한을 확인해주세요.';
            }
            wp_send_json_error($error_msg);
        }
    }
    
    /**
     * AJAX: 일괄 이미지 크롤링
     */
    public function ajax_batch_crawl_images() {
        // nonce 확인 제거 (테스트용)
        // check_ajax_referer('crawl_images_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('권한이 없습니다.');
            return;
        }
        
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
        $offset = intval($_POST['offset'] ?? 0);
        $batch_size = 5; // 한 번에 처리할 개수
        
        error_log("일괄 크롤링 시작: offset={$offset}, batch_size={$batch_size}");
        
        if (empty($post_ids)) {
            // 이미지가 없는 모든 장소 가져오기
            $args = array(
                'post_type' => 'places',
                'posts_per_page' => $batch_size,
                'offset' => $offset,
                'meta_query' => array(
                    array(
                        'key' => '_thumbnail_id',
                        'compare' => 'NOT EXISTS'
                    )
                )
            );
            
            $query = new WP_Query($args);
            $posts = $query->posts;
            $total = $query->found_posts;
            
            error_log("이미지 없는 장소: 전체 {$total}개, 현재 배치 " . count($posts) . "개");
        } else {
            // 선택된 포스트만 처리
            $posts = array_slice($post_ids, $offset, $batch_size);
            $total = count($post_ids);
        }
        
        $processed = 0;
        $success = 0;
        
        foreach ($posts as $post) {
            $post_id = is_object($post) ? $post->ID : $post;
            
            $place_name = get_the_title($post_id);
            $address = get_post_meta($post_id, 'address', true);
            
            error_log("크롤링 중: {$place_name} (ID: {$post_id})");
            
            // 이미지 크롤링
            $kakao_images = $this->crawl_kakao_images($place_name, $address);
            $naver_images = $this->crawl_naver_images($place_name, $address);
            $all_images = array_unique(array_merge($kakao_images, $naver_images));
            
            // 테스트용 이미지 추가
            if (empty($all_images)) {
                $all_images = array(
                    'https://via.placeholder.com/600x400.png?text=' . urlencode($place_name)
                );
            }
            
            if (!empty($all_images)) {
                $saved = $this->save_images_to_media_library($all_images, $post_id);
                if (!empty($saved)) {
                    $success++;
                    error_log("성공: {$place_name} - " . count($saved) . "개 이미지 저장");
                }
            }
            
            $processed++;
        }
        
        $next_offset = $offset + $batch_size;
        $has_more = $next_offset < $total;
        $progress = $total > 0 ? round(($next_offset / $total) * 100) : 100;
        
        wp_send_json_success(array(
            'processed' => $processed,
            'success' => $success,
            'offset' => $next_offset,
            'total' => $total,
            'has_more' => $has_more,
            'progress' => min($progress, 100)
        ));
    }
    
    /**
     * 관리자 목록에 이미지 컬럼 추가
     */
    public function add_image_column($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['place_image'] = '이미지';
            }
        }
        
        return $new_columns;
    }
    
    /**
     * 이미지 컬럼 표시
     */
    public function display_image_column($column, $post_id) {
        if ($column === 'place_image') {
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, array(60, 60));
            } else {
                echo '<span class="no-image" style="color: #999;">이미지 없음</span>';
                echo '<br><a href="#" class="crawl-single-image" data-post-id="' . $post_id . '" style="font-size: 12px;">이미지 크롤링</a>';
            }
        }
    }
    
    /**
     * 일괄 작업 추가
     */
    public function add_bulk_action($actions) {
        $actions['crawl_images'] = '이미지 크롤링';
        return $actions;
    }
    
    /**
     * 일괄 작업 처리
     */
    public function handle_bulk_action($redirect_to, $action, $post_ids) {
        if ($action !== 'crawl_images') {
            return $redirect_to;
        }
        
        // 일괄 크롤링 페이지로 리다이렉트
        $redirect_to = add_query_arg(array(
            'page' => 'image-crawling',
            'action' => 'batch',
            'post_ids' => implode(',', $post_ids)
        ), admin_url('admin.php'));
        
        return $redirect_to;
    }
}

// 싱글톤 인스턴스 생성
Image_Crawling_System::get_instance();
