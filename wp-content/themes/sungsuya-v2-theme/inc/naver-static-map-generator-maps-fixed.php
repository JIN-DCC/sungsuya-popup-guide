<?php
/**
 * 네이버 정적 지도 생성기 - 완전 수정된 버전
 * 
 * 올바른 네이버 클라우드 플랫폼 Maps API 엔드포인트 사용
 * 
 * @package SungsuyaV2
 * @version Fixed with Correct NCP Maps API
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

class NaverStaticMapGeneratorMapsFixed {
    
    private $client_id;
    private $client_secret;
    private $upload_dir;
    
    // 올바른 네이버 클라우드 플랫폼 Maps API 정적지도 엔드포인트
    private $static_map_url = 'https://maps.apigw.ntruss.com/map-static/v2/raster';
    
    public function __construct() {
        // WordPress 옵션에서 키 확인
        $this->client_id = get_option('sungsuya_naver_client_id') ?: get_option('naver_maps_client_id');
        $this->client_secret = get_option('sungsuya_naver_client_secret') ?: get_option('naver_maps_client_secret');
        
        // 디버그 로깅
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🗺️ [Fixed Static Map] Using Client ID: ' . substr($this->client_id, 0, 8) . '...');
            error_log('🗺️ [Fixed Static Map] Correct Endpoint: ' . $this->static_map_url);
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
     * 장소별 정적 지도 이미지 생성 (주소 검증 포함)
     */
    public function generatePlaceMap($place_id, $latitude, $longitude, $place_name, $address = '') {
        // 주소 검증: 세부주소가 없으면 정적지도 생성하지 않음
        if (!$this->hasDetailedAddress($place_id, $address)) {
            return [
                'success' => false,
                'error' => '세부주소가 없어 정적지도를 생성하지 않습니다. 텍스트 폴백을 사용합니다.',
                'place_id' => $place_id,
                'api_type' => 'address_validation_failed',
                'requires_text_fallback' => true
            ];
        }
        
        try {
            // 1. 올바른 정적지도 URL 생성
            $map_url = $this->buildStaticMapUrl($latitude, $longitude, $place_name);
            
            // 2. 올바른 헤더 인증 방식으로 이미지 다운로드
            $image_data = $this->downloadMapImageWithHeaders($map_url);
            
            if (!$image_data) {
                throw new Exception('정적지도 이미지 다운로드 실패');
            }
            
            // 3. 로컬 파일로 저장
            $filename = 'static-map-fixed-' . $place_id . '.png';
            $file_path = $this->upload_dir . $filename;
            
            if (file_put_contents($file_path, $image_data) === false) {
                throw new Exception('파일 저장 실패');
            }
            
            // 4. WordPress 미디어 라이브러리에 등록
            $attachment_id = $this->attachToMediaLibrary($file_path, $filename, $place_name);
            
            // 5. Places 메타필드에 이미지 ID 저장
            update_post_meta($place_id, 'static_map_image_id', $attachment_id);
            update_post_meta($place_id, 'static_map_generated', current_time('mysql'));
            update_post_meta($place_id, 'static_map_api_type', 'ncp_maps_api');
            update_post_meta($place_id, 'static_map_url_used', $map_url);
            
            return [
                'success' => true,
                'attachment_id' => $attachment_id,
                'file_path' => $file_path,
                'url' => wp_get_attachment_url($attachment_id),
                'message' => $place_name . ' 정적지도 생성 완료 (NCP Maps API)',
                'api_type' => 'ncp_maps_api',
                'api_url' => $map_url
            ];
            
        } catch (Exception $e) {
            // 폴백 모드: 정적지도 생성 실패 시 GD 라이브러리 사용
            $fallback_result = $this->createFallbackMap($place_id, $latitude, $longitude, $place_name);
            
            if ($fallback_result['success']) {
                return $fallback_result;
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'place_id' => $place_id,
                'api_type' => 'ncp_maps_api',
                'fallback_attempted' => true,
                'debug_info' => [
                    'client_id' => substr($this->client_id, 0, 8) . '...',
                    'has_secret' => !empty($this->client_secret),
                    'api_url' => isset($map_url) ? $map_url : 'URL 생성 실패',
                    'endpoint' => $this->static_map_url
                ]
            ];
        }
    }
    
    /**
     * 올바른 네이버 클라우드 플랫폼 정적지도 URL 생성 (한글 장소명 유지)
     */
    private function buildStaticMapUrl($lat, $lng, $place_name) {
        // 기본 파라미터 설정
        $params = [
            'w' => 400,              // 너비
            'h' => 300,              // 높이
            'center' => $lng . ',' . $lat,  // 중심점 (경도,위도)
            'level' => 16,           // 줌 레벨
            'maptype' => 'basic',    // 지도 타입
            'format' => 'png',       // 이미지 포맷
            'scale' => 2             // 해상도
        ];
        
        // 마커 정보 생성 - 빨간색 기본 마커 (라벨 없음)
        $marker_info = sprintf(
            'type:d|size:mid|color:red|pos:%s %s',
            $lng,
            $lat
        );
        
        // 마커 파라미터 추가
        $params['markers'] = $marker_info;
        
        // 최종 URL 생성
        $final_url = $this->static_map_url . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        
        // 디버그 로깅
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🗺️ [Fixed Static Map URL] ' . $final_url);
            error_log('🗺️ [Fixed Static Map] 빨간색 기본 마커 - 장소명은 오버레이로 표시');
        }
        
        return $final_url;
    }
    
    /**
     * 세부주소 유무 검증
     */
    private function hasDetailedAddress($place_id, $address = '') {
        // 주소 정보 가져오기
        if (empty($address)) {
            $address = get_post_meta($place_id, 'address', true);
        }
        
        if (empty($address)) {
            return false;
        }
        
        // 주소 정리
        $clean_address = trim($address);
        
        // 빈 주소 체크
        if (empty($clean_address)) {
            return false;
        }
        
        // 너무 짧은 주소 체크 - 더 엄격하게 처리
        $insufficient_patterns = [
            // 정확한 매칭 (대소문자 무시)
            '/^성수동$/i',
            '/^서울 성동구 성수동$/i',
            '/^서울시 성동구 성수동$/i',
            '/^서울특별시 성동구 성수동$/i',
            '/^성동구 성수동$/i',
            // 추가: 다양한 형태의 기본 주소
            '/^성수동 ?대로$/i',
            '/^성수동 ?일대$/i',
            '/^성수동 ?지역$/i',
            '/^성수동 ?[1-9]가$/i',  // 성수동1가, 성수동2가 등
            '/^서울 성동구$/i',
            '/^서울시 성동구$/i'
        ];
        
        foreach ($insufficient_patterns as $pattern) {
            if (preg_match($pattern, trim($clean_address))) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('🗺️ [Address Validation REJECTED] Place ID: ' . $place_id . ', Address: "' . $clean_address . '" - 세부주소 없는 기본 주소');
                }
                return false;
            }
        }
        
        // 세부주소 요소 체크 - 강화된 검증
        $detailed_indicators = [
            // 도로명 체크
            '/\d+길/',        // 숫자 + 길 (예: 6길, 18길)
            '/\d+번길/',     // 숫자 + 번길
            '/[\w가-힣]+로\d*/',  // 이름 + 로 (예: 성수이로, 동일로)
            '/[\w가-힣]+길\d*/',  // 이름 + 길 (예: 연무장길)
            
            // 건물 번호
            '/\d+-\d+/',     // 번지 (예: 31-15, 123-45)
            '/\d+번지/',     // 번지 표기
            
            // 빌딩/시설
            '/빌딩/',
            '/빌/',
            '/타워/',
            '/센터/',
            '/플라자/',
            '/몰세리/',
            '/오피스/',
            '/또아이옥/',
            
            // 상세 주소
            '/\d+층/',       // 층 수
            '/\d+호/',       // 호 수
            '/이동 ?\d+/',    // 이동 번호
            '/동 ?\d+/',     // 동 번호
            
            // 특수 상세 주소
            '/지하/',
            '/루프탑/',
            '/1층/',
            '/지하 ?[1-9]/',
            '/B\d+/',        // B1, B2 등
            '/상가/',
            '/상점/',
        ];
        
        foreach ($detailed_indicators as $pattern) {
            if (preg_match($pattern, $clean_address)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('🗺️ [Address Validation PASSED] Place ID: ' . $place_id . ', Address: "' . $clean_address . '" - 세부주소 있음 (Pattern: ' . $pattern . ')');
                }
                return true;
            }
        }
        
        // 길이 기반 검증 (너무 짧으면 대개 기본 주소)
        if (mb_strlen($clean_address, 'UTF-8') < 15) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('🗺️ [Address Validation REJECTED] Place ID: ' . $place_id . ', Address: "' . $clean_address . '" - 주소가 너무 짧음 (' . mb_strlen($clean_address, 'UTF-8') . '자)');
            }
            return false;
        }
        
        // 디버그 로깅
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🗺️ [Address Validation REJECTED] Place ID: ' . $place_id . ', Address: "' . $clean_address . '" - 세부주소 요소 없음');
        }
        
        return false;
    }
    
    /**
     * 장소명 정리 (더 이상 API 마커 라벨에 사용하지 않음)
     * @deprecated 이 함수는 더 이상 사용되지 않음 - 오버레이 텍스트로 대체
     */
    private function sanitizePlaceName($place_name) {
        // 더 이상 마커 라벨에 사용하지 않으므로 기본값만 반환
        return 'Place';
    }
    
    /**
     * 한글 장소명을 영문으로 변환 (마커 라벨용)
     */
    private function convertToEnglish($korean_name) {
        // 한글 → 영문 변환 맵핑 (확장된 사전)
        $korean_to_english = [
            // 성수동 주요 장소명
            '오늘애김밥' => 'Oneul',
            '디시시' => 'DCC',
            '어니언' => 'Onion',
            '성수연방' => 'Union',
            '블루보틀' => 'Blue',
            '디시시랩' => 'DCC Lab',
            '팩토리얼' => 'Factory',
            '브루어리' => 'Brewery',
            '이카이어' => 'IKEA',
            '대림창고' => 'Daelim',
            '고사리' => 'Gosari',
            '팩토리 5' => 'F5',
            '어반비어' => 'Urban',
            
            // 일반 단어
            '카페' => 'Cafe',
            '맛집' => 'Rest',
            '스토어' => 'Store',
            '샵' => 'Shop',
            '점' => '',
            '복합' => 'Complex',
            '빌딩' => 'Bldg',
            '센터' => 'Center',
            '라보' => 'Lab',
            '테스트' => 'Test',
            '성수동' => 'SS',
            '성수역' => 'SSS',
            '역점' => 'St',
            '지점' => 'Br',
            '본점' => 'Main'
        ];
        
        $english_name = $korean_name;
        
        // 전체 매칭 먼저 시도
        foreach ($korean_to_english as $korean => $english) {
            if (strpos($english_name, $korean) !== false) {
                $english_name = str_replace($korean, $english, $english_name);
            }
        }
        
        // 남은 한글 제거
        $english_name = preg_replace('/[가-힣]/', '', $english_name);
        
        // 연속된 공백 제거
        $english_name = preg_replace('/\s+/', ' ', $english_name);
        $english_name = trim($english_name);
        
        // 빈 문자열이거나 너무 짧으면 기본값
        if (empty($english_name) || strlen($english_name) < 2) {
            return '';
        }
        
        // 길이 제한 (8자 이내)
        if (strlen($english_name) > 8) {
            $english_name = substr($english_name, 0, 8);
        }
        
        return $english_name;
    }
    
    /**
     * 올바른 헤더 인증 방식으로 이미지 다운로드
     */
    private function downloadMapImageWithHeaders($url) {
        // 네이버 클라우드 플랫폼 Maps API 헤더 인증
        $headers = [
            'X-NCP-APIGW-API-KEY-ID: ' . $this->client_id,
            'X-NCP-APIGW-API-KEY: ' . $this->client_secret,
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept: image/png,image/*,*/*',
            'Accept-Language: ko-KR,ko;q=0.9,en;q=0.8'
        ];
        
        // 디버그 로깅
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🗺️ [Fixed Static Map 요청] URL: ' . $url);
            error_log('🗺️ [Fixed Static Map 요청] Headers: ' . json_encode($headers));
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        // 디버그 정보
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🗺️ [Fixed Static Map 응답] HTTP 상태: ' . $http_code);
            error_log('🗺️ [Fixed Static Map 응답] Content-Type: ' . $content_type);
            error_log('🗺️ [Fixed Static Map 응답] 응답 크기: ' . strlen($response) . ' bytes');
            
            if ($error) {
                error_log('🗺️ [Fixed Static Map 응답] cURL 오류: ' . $error);
            }
            
            // 성공 시 응답 내용 일부 로깅
            if ($http_code === 200) {
                error_log('🗺️ [Fixed Static Map 성공] 실제 네이버 정적지도 이미지 다운로드 완료!');
            } else {
                // 오류 응답의 첫 부분 로깅
                $error_preview = substr($response, 0, 500);
                error_log('🗺️ [Fixed Static Map 오류 응답] ' . $error_preview);
            }
        }
        
        if ($error) {
            throw new Exception('cURL 오류: ' . $error);
        }
        
        if ($http_code !== 200) {
            // 오류 응답 분석
            $error_data = substr($response, 0, 500);
            
            $error_message = "NCP Maps API HTTP 오류 {$http_code}";
            switch ($http_code) {
                case 401:
                    $error_message .= " - API 키 인증 실패. Client ID/Secret 확인 필요: " . $error_data;
                    break;
                case 403:
                    $error_message .= " - 접근 권한 없음. Maps API 서비스 활성화 확인 필요: " . $error_data;
                    break;
                case 404:
                    $error_message .= " - 엔드포인트를 찾을 수 없음. URL 확인 필요: " . $error_data;
                    break;
                case 429:
                    $error_message .= " - API 호출 한도 초과. 잠시 후 재시도 필요: " . $error_data;
                    break;
                default:
                    $error_message .= " - " . $error_data;
            }
            
            throw new Exception($error_message);
        }
        
        // 이미지 데이터 검증
        if (empty($response) || strlen($response) < 100) {
            throw new Exception('유효하지 않은 이미지 데이터 (크기: ' . strlen($response) . ' bytes)');
        }
        
        // Content-Type 검증
        if (strpos($content_type, 'image/') === false) {
            if (strpos($response, '<html') !== false || strpos($response, '<!DOCTYPE') !== false) {
                throw new Exception('HTML 에러 페이지 반환 - NCP Maps API 설정 확인 필요');
            }
            throw new Exception('이미지가 아닌 응답 받음: ' . $content_type . ' (올바른 API 엔드포인트 확인 필요)');
        }
        
        return $response;
    }
    
    /**
     * 폴백 모드: GD 라이브러리로 지도 이미지 생성
     */
    private function createFallbackMap($place_id, $latitude, $longitude, $place_name) {
        try {
            if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
                throw new Exception('GD 라이브러리가 설치되지 않았습니다');
            }
            
            $canvas_width = 400;
            $canvas_height = 300;
            
            $image = imagecreatetruecolor($canvas_width, $canvas_height);
            
            // 색상 정의
            $bg_color = imagecolorallocate($image, 242, 244, 246);
            $road_color = imagecolorallocate($image, 255, 255, 255);
            $water_color = imagecolorallocate($image, 173, 216, 255);
            $park_color = imagecolorallocate($image, 198, 230, 147);
            $border_color = imagecolorallocate($image, 200, 200, 200);
            $text_color = imagecolorallocate($image, 60, 60, 60);
            $marker_color = imagecolorallocate($image, 239, 68, 68);
            $marker_border = imagecolorallocate($image, 220, 50, 50);
            
            // 배경 및 지도 요소 그리기
            imagefill($image, 0, 0, $bg_color);
            
            // 도로
            imagefilledrectangle($image, 0, 120, $canvas_width, 135, $road_color);
            imagefilledrectangle($image, 0, 180, $canvas_width, 195, $road_color);
            imagefilledrectangle($image, 150, 0, 165, $canvas_height, $road_color);
            imagefilledrectangle($image, 220, 0, 235, $canvas_height, $road_color);
            
            // 공원
            imagefilledellipse($image, 100, 80, 60, 40, $park_color);
            imagefilledellipse($image, 320, 220, 80, 50, $park_color);
            
            // 물
            imagefilledrectangle($image, 0, 260, $canvas_width, 280, $water_color);
            
            // 테두리
            imagerectangle($image, 0, 0, $canvas_width-1, $canvas_height-1, $border_color);
            
            // 마커
            $center_x = $canvas_width / 2;
            $center_y = $canvas_height / 2;
            $marker_radius = 12;
            
            // 그림자 효과를 위한 색상 (알파 채널 없이)
            $shadow_color = imagecolorallocate($image, 100, 100, 100);
            imagefilledellipse($image, $center_x + 2, $center_y + 2, $marker_radius * 2, $marker_radius * 2, $shadow_color);
            imagefilledellipse($image, $center_x, $center_y, $marker_radius * 2, $marker_radius * 2, $marker_color);
            imagefilledellipse($image, $center_x, $center_y, $marker_radius, $marker_radius, $marker_border);
            imagefilledellipse($image, $center_x, $center_y, 4, 4, imagecolorallocate($image, 255, 255, 255));
            
            // 텍스트
            $clean_name = substr($place_name, 0, 15);
            $text_width = strlen($clean_name) * 8;
            $text_x = max(5, $center_x - $text_width / 2);
            $text_y = $center_y + $marker_radius + 20;
            
            $text_bg = imagecolorallocate($image, 255, 255, 255);
            imagefilledrectangle($image, $text_x - 5, $text_y - 3, $text_x + $text_width + 5, $text_y + 15, $text_bg);
            imagerectangle($image, $text_x - 5, $text_y - 3, $text_x + $text_width + 5, $text_y + 15, $border_color);
            imagestring($image, 4, $text_x, $text_y, $clean_name, $text_color);
            
            // 좌표 정보
            $coord_text = "({$latitude}, {$longitude})";
            imagestring($image, 2, 8, $canvas_height - 15, $coord_text, imagecolorallocate($image, 120, 120, 120));
            
            // 지역명
            imagestring($image, 3, $canvas_width - 50, $canvas_height - 15, "성수동", $text_color);
            
            // "NCP 폴백 모드" 표시
            imagestring($image, 2, $canvas_width - 120, 8, "NCP Fallback Mode", imagecolorallocate($image, 120, 120, 120));
            
            // 파일 저장
            $filename = 'static-fallback-fixed-' . $place_id . '.png';
            $file_path = $this->upload_dir . $filename;
            
            if (!imagepng($image, $file_path)) {
                throw new Exception('폴백 이미지 저장 실패');
            }
            
            imagedestroy($image);
            
            // WordPress 미디어 라이브러리에 등록
            $attachment_id = $this->attachToMediaLibrary($file_path, $filename, $place_name . ' (NCP 폴백)');
            
            // 메타필드 업데이트
            update_post_meta($place_id, 'static_map_image_id', $attachment_id);
            update_post_meta($place_id, 'static_map_generated', current_time('mysql'));
            update_post_meta($place_id, 'static_map_api_type', 'ncp_gd_fallback');
            
            return [
                'success' => true,
                'attachment_id' => $attachment_id,
                'file_path' => $file_path,
                'url' => wp_get_attachment_url($attachment_id),
                'message' => $place_name . ' NCP 폴백 지도 생성 완료',
                'fallback' => true,
                'api_type' => 'ncp_gd_fallback'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'NCP 폴백 모드 실패: ' . $e->getMessage(),
                'place_id' => $place_id
            ];
        }
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
            'post_title' => $place_name . ' 정적 지도 (NCP Maps API)',
            'post_content' => '',
            'post_status' => 'inherit'
        ];
        
        $attachment_id = wp_insert_attachment($attachment, $file_path);
        
        if (is_wp_error($attachment_id)) {
            throw new Exception('WordPress 첨부 파일 생성 실패: ' . $attachment_id->get_error_message());
        }
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_metadata);
        
        return $attachment_id;
    }
    
    /**
     * API 연결 테스트
     */
    public function testApiConnection() {
        $test_lat = 37.5444;
        $test_lng = 127.0548;
        $test_name = 'NCP API 테스트';
        
        try {
            $map_url = $this->buildStaticMapUrl($test_lat, $test_lng, $test_name);
            
            // 실제 NCP Maps API 호출 테스트
            $image_data = $this->downloadMapImageWithHeaders($map_url);
            
            if ($image_data && strlen($image_data) > 100) {
                return [
                    'success' => true,
                    'message' => '네이버 클라우드 플랫폼 Maps API 연결 성공 - 실제 이미지 다운로드 완료',
                    'api_type' => 'ncp_maps_api',
                    'image_size' => strlen($image_data) . ' bytes',
                    'test_url' => $map_url,
                    'endpoint' => $this->static_map_url
                ];
            } else {
                throw new Exception('이미지 데이터가 유효하지 않음');
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'api_type' => 'ncp_maps_api',
                'test_url' => isset($map_url) ? $map_url : 'URL 생성 실패',
                'debug_info' => [
                    'client_id' => substr($this->client_id, 0, 8) . '...',
                    'endpoint' => $this->static_map_url,
                    'headers_info' => 'X-NCP-APIGW-API-KEY-ID + X-NCP-APIGW-API-KEY'
                ]
            ];
        }
    }
    
    /**
     * 기존 Places 정적 지도 확인
     */
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
