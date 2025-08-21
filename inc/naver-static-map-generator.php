<?php
/**
 * 네이버 정적 지도 생성기 클래스
 * 
 * API 사용량 최적화를 위한 정적 지도 이미지 생성
 * 
 * @package SungsuyaV2
 * @since 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

class NaverStaticMapGenerator {
    
    private $client_id;
    private $client_secret;
    private $base_url = 'https://maps.apigw.ntruss.com/map-static/v2/raster';
    private $upload_dir;
    
    public function __construct() {
        // WordPress 옵션에서 NCP Maps API 키 확인 (여러 옵션 키 시도)
        $this->client_id = get_option('naver_client_id');
        if (!$this->client_id) {
            $this->client_id = get_option('naver_api_client_id');
        }
        if (!$this->client_id) {
            $this->client_id = get_option('sungsuya_naver_client_id');
        }
        if (!$this->client_id) {
            $this->client_id = 'qosb7em5i9'; // 기본값
        }
        
        $this->client_secret = get_option('naver_client_secret');
        if (!$this->client_secret) {
            $this->client_secret = get_option('naver_api_client_secret');
        }
        if (!$this->client_secret) {
            $this->client_secret = get_option('sungsuya_naver_client_secret');
        }
        if (!$this->client_secret) {
            $this->client_secret = 'K0AcIQ4Q9nyLdfwNeICJQ3CUSvvWMuU3D8rebuHc'; // 기본값
        }
        
        // 빈 키 체크
        if (empty($this->client_id) || empty($this->client_secret)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('🗺️ [WARNING] NCP Maps API 키가 설정되지 않았습니다.');
            }
        }
        
        // 디버그 로깅
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🗺️ [Constructor] Using API Key: ' . substr($this->client_id, 0, 8) . '... (Static Map)');
            error_log('🗺️ [Constructor] Secret Key: ' . substr($this->client_secret, 0, 8) . '...');
        }
        
        // WordPress 업로드 디렉토리 설정
        $upload_info = wp_upload_dir();
        $this->upload_dir = $upload_info['basedir'] . '/static-maps/';
        
        // 디렉토리 생성
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
        }
    }
    
    /**
     * 장소별 정적 지도 이미지 생성 (폴백 모드 지원)
     */
    public function generatePlaceMap($place_id, $latitude, $longitude, $place_name, $address = '', $zoom_level = 14, $width = 600, $height = 400) {
        try {
            // 1. 정적 지도 URL 생성
            $map_url = $this->buildStaticMapUrl($latitude, $longitude, $place_name, $zoom_level, $width, $height);
            
            // 2. 이미지 다운로드 시도
            $image_data = $this->downloadMapImage($map_url);
            
            if (!$image_data) {
                throw new Exception('지도 이미지 다운로드 실패');
            }
            
            // 3. 로컬 파일로 저장
            $filename = 'place-map-' . $place_id . '-z' . $zoom_level . '.png';
            $file_path = $this->upload_dir . $filename;
            
            if (file_put_contents($file_path, $image_data) === false) {
                throw new Exception('파일 저장 실패');
            }
            
            // 4. WordPress 미디어 라이브러리에 등록
            $attachment_id = $this->attachToMediaLibrary($file_path, $filename, $place_name);
            
            // 5. Places 메타필드에 이미지 ID 저장
            update_post_meta($place_id, 'static_map_image_id', $attachment_id);
            update_post_meta($place_id, 'static_map_generated', current_time('mysql'));
            update_post_meta($place_id, 'static_map_zoom_level', $zoom_level);
            update_post_meta($place_id, 'static_map_size', $width . 'x' . $height);
            
            // 다양한 줌 레벨 지도도 함께 생성 (선택사항) - 현재 비활성화
            // $this->generateMultipleZoomLevels($place_id, $latitude, $longitude, $place_name);
            
            return [
                'success' => true,
                'attachment_id' => $attachment_id,
                'file_path' => $file_path,
                'url' => wp_get_attachment_url($attachment_id),
                'message' => $place_name . ' 정적 지도 생성 완료 (줌 레벨: ' . $zoom_level . ')',
                'zoom_level' => $zoom_level
            ];
            
        } catch (Exception $e) {
            // 폴백 모드: 정적지도 생성 실패 시 대안 제공
            $fallback_result = $this->createFallbackMap($place_id, $latitude, $longitude, $place_name);
            
            if ($fallback_result['success']) {
                return $fallback_result;
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'place_id' => $place_id,
                'fallback_attempted' => true
            ];
        }
    }
    
    /**
     * 다양한 줌 레벨의 지도 생성
     */
    private function generateMultipleZoomLevels($place_id, $latitude, $longitude, $place_name) {
        // 여러 줌 레벨의 지도 생성 (선택사항)
        $zoom_levels = [12, 14, 16]; // 넓은 지역, 중간, 상세
        $generated_maps = [];
        
        foreach ($zoom_levels as $level) {
            try {
                // 이미 생성된 것이 있는지 확인
                $existing_id = get_post_meta($place_id, 'static_map_zoom_' . $level, true);
                if ($existing_id && wp_attachment_is_image($existing_id)) {
                    $generated_maps['zoom_' . $level] = $existing_id;
                    continue;
                }
                
                $map_url = $this->buildStaticMapUrl($latitude, $longitude, $place_name, $level, 600, 400);
                $image_data = $this->downloadMapImage($map_url);
                
                if ($image_data) {
                    $filename = 'place-map-' . $place_id . '-zoom' . $level . '.png';
                    $file_path = $this->upload_dir . $filename;
                    
                    if (file_put_contents($file_path, $image_data) !== false) {
                        $attachment_id = $this->attachToMediaLibrary($file_path, $filename, $place_name . ' (줌 ' . $level . ')');
                        $generated_maps['zoom_' . $level] = $attachment_id;
                        
                        // 메타데이터로 저장
                        update_post_meta($place_id, 'static_map_zoom_' . $level, $attachment_id);
                    }
                }
                
                // API 레이트 리미트 방지
                sleep(1);
                
            } catch (Exception $e) {
                // 개별 줌 레벨 실패는 로그만 남기고 계속 진행
                error_log('줌 레벨 ' . $level . ' 생성 실패: ' . $e->getMessage());
                continue;
            }
        }
        
        if (!empty($generated_maps)) {
            update_post_meta($place_id, 'static_map_zoom_levels', $generated_maps);
        }
    }
    
    /**
     * 폴백 모드: 진짜 지도 느낌의 이미지 생성
     */
    private function createFallbackMap($place_id, $latitude, $longitude, $place_name) {
        try {
            // 더 큰 기본 지도 이미지 생성
            $canvas_width = 400;
            $canvas_height = 300;
            
            // GD 라이브러리로 지도 느낌 이미지 생성
            $image = imagecreatetruecolor($canvas_width, $canvas_height);
            
            // 지도 느낌의 색상 정의
            $bg_color = imagecolorallocate($image, 242, 244, 246); // 연한 회색 배경
            $road_color = imagecolorallocate($image, 255, 255, 255); // 도로 색
            $water_color = imagecolorallocate($image, 173, 216, 255); // 물 색
            $park_color = imagecolorallocate($image, 198, 230, 147); // 공원 색
            $border_color = imagecolorallocate($image, 200, 200, 200);
            $text_color = imagecolorallocate($image, 60, 60, 60);
            $marker_color = imagecolorallocate($image, 239, 68, 68); // 빨간 마커
            $marker_border = imagecolorallocate($image, 220, 50, 50);
            
            // 배경 채우기
            imagefill($image, 0, 0, $bg_color);
            
            // 가짜 도로 그리기 (지도 느낌)
            // 수평 도로
            imagefilledrectangle($image, 0, 120, $canvas_width, 135, $road_color);
            imagefilledrectangle($image, 0, 180, $canvas_width, 195, $road_color);
            // 수직 도로
            imagefilledrectangle($image, 150, 0, 165, $canvas_height, $road_color);
            imagefilledrectangle($image, 220, 0, 235, $canvas_height, $road_color);
            
            // 가짜 공원 영역
            imagefilledellipse($image, 100, 80, 60, 40, $park_color);
            imagefilledellipse($image, 320, 220, 80, 50, $park_color);
            
            // 가짜 강 또는 물
            imagefilledrectangle($image, 0, 260, $canvas_width, 280, $water_color);
            
            // 테두리 그리기
            imagerectangle($image, 0, 0, $canvas_width-1, $canvas_height-1, $border_color);
            
            // 중앙에 지도 핀 마커 그리기
            $center_x = $canvas_width / 2;
            $center_y = $canvas_height / 2;
            $marker_radius = 12;
            
            // 마커 그림자
            $shadow_color = imagecolorallocate($image, 100, 100, 100);
            imagefilledellipse($image, $center_x + 2, $center_y + 2, $marker_radius * 2, $marker_radius * 2, $shadow_color);
            
            // 마커 본체 (지도 핀 모양)
            imagefilledellipse($image, $center_x, $center_y, $marker_radius * 2, $marker_radius * 2, $marker_color);
            imagefilledellipse($image, $center_x, $center_y, $marker_radius, $marker_radius, $marker_border);
            
            // 마커 중앙에 작은 점
            imagefilledellipse($image, $center_x, $center_y, 4, 4, imagecolorallocate($image, 255, 255, 255));
            
            // 장소명 텍스트 (더 예쁘게)
            // 텍스트 배경 색상
            $text_bg = imagecolorallocate($image, 255, 255, 255);
            $clean_name = substr($place_name, 0, 15); // 길이 제한
            $text_width = strlen($clean_name) * 8;
            $text_x = max(5, $center_x - $text_width / 2);
            $text_y = $center_y + $marker_radius + 20;
            
            // 텍스트 배경 박스
            imagefilledrectangle($image, $text_x - 5, $text_y - 3, $text_x + $text_width + 5, $text_y + 15, $text_bg);
            imagerectangle($image, $text_x - 5, $text_y - 3, $text_x + $text_width + 5, $text_y + 15, $border_color);
            
            // 장소명 텍스트
            imagestring($image, 4, $text_x, $text_y, $clean_name, $text_color);
            
            // 좌표 정보 표시 (더 작게)
            $coord_text = "({$latitude}, {$longitude})";
            $coord_x = 8;
            $coord_y = $canvas_height - 15;
            imagestring($image, 2, $coord_x, $coord_y, $coord_text, imagecolorallocate($image, 120, 120, 120));
            
            // 우측 하단에 "성수동" 표시
            $area_text = "성수동";
            $area_x = $canvas_width - 50;
            $area_y = $canvas_height - 15;
            imagestring($image, 3, $area_x, $area_y, $area_text, $text_color);
            
            // 파일로 저장
            $filename = 'enhanced-map-' . $place_id . '.png';
            $file_path = $this->upload_dir . $filename;
            
            if (!imagepng($image, $file_path)) {
                throw new Exception('향상된 이미지 저장 실패');
            }
            
            imagedestroy($image);
            
            // WordPress 미디어 라이브러리에 등록
            $attachment_id = $this->attachToMediaLibrary($file_path, $filename, $place_name . ' (성수동 지도)');
            
            // 메타필드 업데이트
            update_post_meta($place_id, 'static_map_image_id', $attachment_id);
            update_post_meta($place_id, 'static_map_generated', current_time('mysql'));
            update_post_meta($place_id, 'static_map_enhanced', true);
            
            return [
                'success' => true,
                'attachment_id' => $attachment_id,
                'file_path' => $file_path,
                'url' => wp_get_attachment_url($attachment_id),
                'message' => $place_name . ' 성수동 지도 생성 완료 (향상된 모드)',
                'enhanced' => true
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => '향상된 모드 실패: ' . $e->getMessage(),
                'place_id' => $place_id
            ];
        }
    }
    
    /**
     * GD 라이브러리 사용 가능 확인
     */
    private function isGDAvailable() {
        return extension_loaded('gd') && function_exists('imagecreatetruecolor');
    }
    
    /**
     * 정적 지도 URL 생성 (네이버 클라우드 API 스펙 호환)
     */
    private function buildStaticMapUrl($lat, $lng, $place_name, $zoom_level = 14, $width = 600, $height = 400) {
        // 마커 라벨 최적화 (한글 지원 개선)
        $label = substr($place_name, 0, 8); // 라벨 길이 제한
        $label = preg_replace('/[^가-힣A-Za-z0-9\s]/', '', $label); // 특수문자 제거
        
        // 줌 레벨 범위 제한 (10-18)
        $zoom_level = max(10, min(18, $zoom_level));
        
        $params = [
            'w' => $width,                   // 너비 (사용자 지정 가능)
            'h' => $height,                  // 높이 (사용자 지정 가능)
            'center' => $lng . ',' . $lat,   // 중심점 (경도,위도)
            'level' => $zoom_level,          // 줌 레벨 (14로 낮춰서 더 넓은 지역 표시)
            'maptype' => 'basic',            // 지도 타입
            'format' => 'png',               // 이미지 형식
            'scale' => 2,                    // 고해상도 (Retina 지원)
            'markers' => sprintf(
                'type:t|size:mid|pos:%s%%20%s|label:%s|color:red',
                $lng,
                $lat, 
                urlencode($label)
            )
        ];
        
        $final_url = $this->base_url . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        
        // 디버그 로깅
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🗺️ [URL 생성] 줌레벨: ' . $zoom_level . ', 크기: ' . $width . 'x' . $height);
            error_log('🗺️ [URL] ' . $final_url);
        }
        
        return $final_url;
    }
    
    /**
     * 지도 이미지 다운로드 (네이버 클라우드 API 호환)
     */
    private function downloadMapImage($url) {
        // API 키 재확인
        $client_id = $this->client_id;
        $client_secret = $this->client_secret;
        
        // 기본값 사용 (테스트용)
        if (empty($client_id)) {
            $client_id = 'qosb7em5i9';
        }
        if (empty($client_secret)) {
            $client_secret = 'K0AcIQ4Q9nyLdfwNeICJQ3CUSvvWMuU3D8rebuHc';
        }
        
        // 네이버 클라우드 플랫폼 API 헤더 설정
        $headers = [
            'X-NCP-APIGW-API-KEY-ID: ' . $client_id,
            'X-NCP-APIGW-API-KEY: ' . $client_secret
        ];
        
        // 디버그 로깅
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🗺️ [Static Map] API 요청 시작');
            error_log('URL: ' . $url);
            error_log('Client ID: ' . substr($client_id, 0, 8) . '...');
            error_log('Headers: ' . print_r($headers, true));
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,  // SSL 검증 비활성화 (테스트용)
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        // 디버그 정보
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('HTTP 상태: ' . $http_code);
            error_log('응답 크기: ' . strlen($response) . ' bytes');
            if ($error) {
                error_log('cURL 오류: ' . $error);
            }
        }
        
        if ($error) {
            throw new Exception('cURL 오류: ' . $error);
        }
        
        if ($http_code !== 200) {
            // 오류 응답 분석
            $error_data = '';
            if (is_string($response)) {
                $error_data = substr($response, 0, 500);
            }
            
            throw new Exception("HTTP 오류 {$http_code}: {$error_data}");
        }
        
        // 이미지 데이터 검증
        if (empty($response) || strlen($response) < 100) {
            throw new Exception('유효하지 않은 이미지 데이터');
        }
        
        // PNG 헤더 검증
        if (substr($response, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            throw new Exception('PNG 이미지가 아닙니다: ' . substr($response, 0, 50));
        }
        
        return $response;
    }
    
    /**
     * WordPress 미디어 라이브러리에 첨부
     */
    private function attachToMediaLibrary($file_path, $filename, $place_name) {
        $upload_info = wp_upload_dir();
        $relative_path = str_replace($upload_info['basedir'], '', $file_path);
        $file_url = $upload_info['baseurl'] . $relative_path;
        
        $attachment = [
            'guid' => $file_url,
            'post_mime_type' => 'image/png',
            'post_title' => $place_name . ' 정적 지도',
            'post_content' => '',
            'post_status' => 'inherit'
        ];
        
        $attachment_id = wp_insert_attachment($attachment, $file_path);
        
        if (is_wp_error($attachment_id)) {
            throw new Exception('WordPress 첨부 파일 생성 실패: ' . $attachment_id->get_error_message());
        }
        
        // 메타데이터 생성
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_metadata);
        
        return $attachment_id;
    }
    
    /**
     * 줌 레벨 변경 전용 메서드 (주소 검증 없음)
     */
    public function generatePlaceMapWithZoom($place_id, $latitude, $longitude, $place_name, $zoom_level = 14, $width = 600, $height = 400) {
        try {
            // 1. 정적지도 URL 생성
            $map_url = $this->buildStaticMapUrl($latitude, $longitude, $place_name, $zoom_level, $width, $height);
            
            // 2. 이미지 다운로드 시도
            $image_data = $this->downloadMapImage($map_url);
            
            if (!$image_data) {
                throw new Exception('지도 이미지 다운로드 실패');
            }
            
            // 3. 로컬 파일로 저장
            $filename = 'place-map-' . $place_id . '-z' . $zoom_level . '.png';
            $file_path = $this->upload_dir . $filename;
            
            if (file_put_contents($file_path, $image_data) === false) {
                throw new Exception('파일 저장 실패');
            }
            
            // 4. WordPress 미디어 라이브러리에 등록
            $attachment_id = $this->attachToMediaLibrary($file_path, $filename, $place_name);
            
            // 5. 줌 레벨별 메타필드에 저장
            update_post_meta($place_id, 'static_map_zoom_' . $zoom_level, $attachment_id);
            
            return [
                'success' => true,
                'attachment_id' => $attachment_id,
                'file_path' => $file_path,
                'url' => wp_get_attachment_url($attachment_id),
                'message' => $place_name . ' 정적 지도 생성 완료 (줌 레벨: ' . $zoom_level . ')',
                'zoom_level' => $zoom_level
            ];
            
        } catch (Exception $e) {
            // 폴백 모드
            $fallback_result = $this->createFallbackMap($place_id, $latitude, $longitude, $place_name);
            
            if ($fallback_result['success']) {
                // 줌 레벨별로 저장
                update_post_meta($place_id, 'static_map_zoom_' . $zoom_level, $fallback_result['attachment_id']);
                return $fallback_result;
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'place_id' => $place_id,
                'fallback_attempted' => true
            ];
        }
    }
    public function hasStaticMap($place_id) {
        $image_id = get_post_meta($place_id, 'static_map_image_id', true);
        return !empty($image_id) && wp_attachment_is_image($image_id);
    }
    
    /**
     * 정적 지도 URL 반환
     */
    public function getStaticMapUrl($place_id) {
        $image_id = get_post_meta($place_id, 'static_map_image_id', true);
        return $image_id ? wp_get_attachment_url($image_id) : false;
    }
}

/**
 * 자동화 및 복구 시스템 기능 추가
 */
class NaverStaticMapAutomation {
    
    /**
     * 실패한 정적지도 자동 재시도
     */
    public static function scheduleRetryFailedMaps() {
        // WordPress Cron 이벤트 등록
        if (!wp_next_scheduled('sungsuya_retry_failed_static_maps')) {
            wp_schedule_event(time(), 'hourly', 'sungsuya_retry_failed_static_maps');
        }
    }
    
    /**
     * 실패한 지도 재생성 실행
     */
    public static function retryFailedMaps() {
        $failed_places = new WP_Query([
            'post_type' => 'places',
            'post_status' => 'publish',
            'posts_per_page' => 10, // 한 번에 10개씩 처리
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'latitude',
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => 'longitude',
                    'compare' => 'EXISTS'
                ],
                [
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
            ]
        ]);
        
        $success_count = 0;
        $error_count = 0;
        
        if ($failed_places->have_posts()) {
            $generator = new NaverStaticMapGenerator();
            
            while ($failed_places->have_posts()) {
                $failed_places->the_post();
                $place_id = get_the_ID();
                
                $latitude = get_post_meta($place_id, 'latitude', true);
                $longitude = get_post_meta($place_id, 'longitude', true);
                $place_name = get_the_title();
                
                try {
                    $result = $generator->generatePlaceMap($place_id, $latitude, $longitude, $place_name);
                    
                    if ($result['success']) {
                        $success_count++;
                        self::logMapGeneration($place_id, 'success', $result['message']);
                    } else {
                        $error_count++;
                        self::logMapGeneration($place_id, 'error', $result['error']);
                    }
                    
                } catch (Exception $e) {
                    $error_count++;
                    self::logMapGeneration($place_id, 'exception', $e->getMessage());
                }
                
                // API 레이트 리미트 방지를 위해 지연
                sleep(1);
            }
        }
        
        wp_reset_postdata();
        
        // 결과 로그
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("🗺️ [Auto Retry] 성공: {$success_count}, 실패: {$error_count}");
        }
        
        return [
            'success_count' => $success_count,
            'error_count' => $error_count,
            'processed' => $success_count + $error_count
        ];
    }
    
    /**
     * 지도 생성 로그 기록
     */
    private static function logMapGeneration($place_id, $status, $message) {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'place_id' => $place_id,
            'status' => $status,
            'message' => $message
        ];
        
        // 로그 데이터 저장 (최대 100개 유지)
        $logs = get_option('sungsuya_static_map_logs', []);
        array_unshift($logs, $log_entry);
        $logs = array_slice($logs, 0, 100);
        update_option('sungsuya_static_map_logs', $logs);
        
        // 개발 환경에서 디버그 로그
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("🗺️ [Map Generation] Place {$place_id}: {$status} - {$message}");
        }
    }
    
    /**
     * API 사용량 모니터링
     */
    public static function trackApiUsage($api_type = 'static_map') {
        $today = date('Y-m-d');
        $usage_key = "sungsuya_api_usage_{$today}";
        $current_usage = (int) get_option($usage_key, 0);
        
        update_option($usage_key, $current_usage + 1);
        
        // 월리 사용량도 추적
        $month_key = "sungsuya_api_usage_" . date('Y-m');
        $monthly_usage = (int) get_option($month_key, 0);
        update_option($month_key, $monthly_usage + 1);
        
        return $current_usage + 1;
    }
    
    /**
     * 에러 알림 시스템
     */
    public static function sendErrorNotification($error_message, $place_id = null) {
        // 에러 횟수 추적
        $error_count_key = 'sungsuya_static_map_errors_' . date('Y-m-d');
        $daily_errors = (int) get_option($error_count_key, 0);
        update_option($error_count_key, $daily_errors + 1);
        
        // 임계치 초과 시 관리자에게 알림
        if ($daily_errors > 50) {
            $admin_email = get_option('admin_email');
            $subject = '성수야! 정적지도 시스템 오류 알림';
            $message = "
오늘 정적지도 생성 중 {$daily_errors}개의 오류가 발생했습니다.

최신 오류: {$error_message}
Place ID: {$place_id}

시스템을 확인해주세요.
            ";
            wp_mail($admin_email, $subject, $message);
        }
    }
    
    /**
     * 사용량 통계 조회
     */
    public static function getUsageStats() {
        $today = date('Y-m-d');
        $month = date('Y-m');
        
        return [
            'daily_usage' => (int) get_option("sungsuya_api_usage_{$today}", 0),
            'monthly_usage' => (int) get_option("sungsuya_api_usage_{$month}", 0),
            'daily_errors' => (int) get_option("sungsuya_static_map_errors_{$today}", 0),
            'total_maps' => self::getTotalMapsCount(),
            'success_rate' => self::getSuccessRate()
        ];
    }
    
    private static function getTotalMapsCount() {
        $maps_query = new WP_Query([
            'post_type' => 'places',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'static_map_image_id',
                    'compare' => 'EXISTS'
                ]
            ],
            'fields' => 'ids'
        ]);
        
        return $maps_query->found_posts;
    }
    
    private static function getSuccessRate() {
        $total_places = wp_count_posts('places')->publish;
        $maps_count = self::getTotalMapsCount();
        
        if ($total_places == 0) return 0;
        
        return round(($maps_count / $total_places) * 100, 1);
    }
    
    /**
     * 정리 작업: 오래된 로그 삭제
     */
    public static function cleanupOldLogs() {
        $cutoff_date = date('Y-m-d', strtotime('-30 days'));
        
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'sungsuya_api_usage_%%' AND option_name < %s",
            "sungsuya_api_usage_{$cutoff_date}"
        ));
        
        // 로그 배열도 정리
        $logs = get_option('sungsuya_static_map_logs', []);
        $cleaned_logs = array_filter($logs, function($log) use ($cutoff_date) {
            return $log['timestamp'] >= $cutoff_date;
        });
        update_option('sungsuya_static_map_logs', array_values($cleaned_logs));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🗺️ [Cleanup] 오래된 로그 정리 완료');
        }
    }
}

// WordPress Cron 이벤트 등록
add_action('sungsuya_retry_failed_static_maps', ['NaverStaticMapAutomation', 'retryFailedMaps']);
add_action('wp_loaded', ['NaverStaticMapAutomation', 'scheduleRetryFailedMaps']);

// 일일 정리 작업 스케줄링
if (!wp_next_scheduled('sungsuya_cleanup_old_logs')) {
    wp_schedule_event(time(), 'daily', 'sungsuya_cleanup_old_logs');
}
add_action('sungsuya_cleanup_old_logs', ['NaverStaticMapAutomation', 'cleanupOldLogs']);
