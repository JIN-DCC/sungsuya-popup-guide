<?php
/**
 * 배치 지오코딩 프로세서 (API 호출 방식 수정 버전)
 * 
 * 네이버 지오코딩 API를 올바른 방식으로 호출하여 Places의 주소를 좌표로 변환
 * 
 * 수정사항:
 * - SSL 검증 활성화
 * - User-Agent 개선
 * - Content-Type 헤더 추가
 * - Referer 헤더 추가
 * - 에러 처리 개선
 * 
 * @package SungsuyaV2
 * @version 1.2.0
 * @since 2025-06-25
 */

if (!defined('ABSPATH')) {
    exit;
}

class BatchGeocodingProcessor {
    
    private $naver_client_id;
    private $naver_client_secret;
    private $api_base_url = 'https://maps.apigw.ntruss.com/map-geocode/v2/geocode';
    private $process_logs = array();
    private $debug_mode = true;
    private $mock_mode = false; // Mock 모드 비활성화
    
    // 🆕 Mock 좌표 데이터
    private $mock_coordinates = array(
        '서울 성동구 아차산로 45' => array('latitude' => 37.5506, 'longitude' => 127.0676),
        '서울 성동구 성수일로 96' => array('latitude' => 37.5444, 'longitude' => 127.0548),
        '서울 성동구 왕십리로 92' => array('latitude' => 37.5615, 'longitude' => 127.0370),
        '서울 성동구 성수일로4길 24' => array('latitude' => 37.5450, 'longitude' => 127.0520),
    );
    
    public function __construct() {
        $this->naver_client_id = get_option('sungsuya_naver_client_id', '');
        $this->naver_client_secret = get_option('sungsuya_naver_client_secret', '');
        
        // 디버그 모드 설정
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        
        // 초기 API 설정 로그
        $this->log("🔧 배치 지오코딩 처리기 초기화 (수정된 API 호출 방식)");
        $this->log("📍 API 엔드포인트: " . $this->api_base_url);
        $this->log("🔑 Client ID 설정: " . (!empty($this->naver_client_id) ? '✅ 설정됨 (' . substr($this->naver_client_id, 0, 8) . '...)' : '❌ 미설정'));
        $this->log("🔐 Client Secret 설정: " . (!empty($this->naver_client_secret) ? '✅ 설정됨' : '❌ 미설정'));
        $this->log("🌐 사이트 URL: " . home_url());
    }
    
    /**
     * API 설정 상태 확인
     */
    public function is_api_configured() {
        $configured = !empty($this->naver_client_id) && !empty($this->naver_client_secret);
        $this->log("🔍 API 설정 상태 확인: " . ($configured ? '✅ 완료' : '❌ 미완료'));
        return $configured;
    }
    
    /**
     * 좌표 미확정 Places 조회
     */
    public function get_pending_places() {
        $this->log("📊 좌표 미확정 Places 조회 시작");
        
        $query = new WP_Query(array(
            'post_type' => 'places',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'latitude',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => 'latitude',
                    'value' => array('', '0', '37.5466'), // 🔧 37.548을 37.5466으로 수정
                    'compare' => 'IN'
                ),
                array(
                    'key' => 'longitude',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => 'longitude',
                    'value' => array('', '0', '127.0529'), // 🔧 특정 경도값 추가
                    'compare' => 'IN'
                )
            )
        ));
        
        $places = array();
        while ($query->have_posts()) {
            $query->the_post();
            $place_id = get_the_ID();
            $address = get_post_meta($place_id, 'address', true);
            $latitude = get_post_meta($place_id, 'latitude', true);
            $longitude = get_post_meta($place_id, 'longitude', true);
            
            // 🆕 좌표 상태 디버그 로그
            $coord_status = "lat={$latitude}, lon={$longitude}";
            
            if (!empty($address)) {
                $place = (object) array(
                    'ID' => $place_id,
                    'post_title' => get_the_title(),
                    'address' => $address,
                    'latitude' => $latitude,
                    'longitude' => $longitude
                );
                $places[] = $place;
                
                $this->log("📍 발견: [{$place_id}] {$place->post_title} - {$address} ({$coord_status})");
            } else {
                $this->log("⚠️ 주소 없음: [{$place_id}] " . get_the_title() . " ({$coord_status})");
            }
        }
        wp_reset_postdata();
        
        $this->log("📊 조회 완료: 총 " . count($places) . "개 Places 발견");
        return $places;
    }
    
    /**
     * 지오코딩 테스트 (개선된 버전)
     */
    public function test_geocoding() {
        $this->log("🧪 지오코딩 테스트 시작 (개선된 API 호출 방식)");
        
        if (!$this->is_api_configured()) {
            return array(
                'success' => false,
                'error' => 'API 키가 설정되지 않았습니다.'
            );
        }
        
        $test_address = '서울 성동구 성수일로 96';
        $this->log("🧪 테스트 주소: {$test_address}");
        
        // 🔧 수정된 API 호출 방식 테스트
        $result = $this->geocode_address($test_address, true);
        
        if ($result) {
            return array(
                'success' => true,
                'message' => "API 연결 성공! 테스트 좌표: {$result['latitude']}, {$result['longitude']}"
            );
        } else {
            return array(
                'success' => false,
                'error' => 'API 연결 실패 또는 응답 오류'
            );
        }
    }
    
    /**
     * 배치 처리 실행
     */
    public function process_batch($places, $progress_callback = null) {
        $this->process_logs = array();
        $total_places = count($places);
        
        $results = array(
            'success' => true,
            'total_count' => $total_places,
            'success_count' => 0,
            'error_count' => 0,
            'errors' => array()
        );
        
        $this->log("🚀 배치 처리 시작: " . $total_places . "개 Places");
        $this->log("⏱️ 예상 소요 시간: " . ceil($total_places * 0.3) . "초 (API 제한 고려)");
        
        // API 설정 사전 검증
        if (!$this->is_api_configured()) {
            $error_msg = "❌ API 설정 오류: 네이버 Client ID 또는 Client Secret이 설정되지 않았습니다.";
            $this->log($error_msg);
            $results['success'] = false;
            $results['errors'][] = $error_msg;
            return $results;
        }
        
        // API 연결 테스트
        $this->log("🔍 API 연결 테스트 시작");
        $test_result = $this->test_geocoding();
        if (!$test_result['success']) {
            $error_msg = "❌ API 연결 실패: " . $test_result['error'];
            $this->log($error_msg);
            $results['success'] = false;
            $results['errors'][] = $error_msg;
            return $results;
        }
        $this->log("✅ API 연결 테스트 성공");
        
        foreach ($places as $index => $place) {
            try {
                $current_num = $index + 1;
                $this->log("🔄 [{$current_num}/{$total_places}] 처리 중: {$place->post_title}");
                $this->log("   📍 주소: {$place->address}");
                
                // 지오코딩 실행
                $coordinates = $this->geocode_address($place->address);
                
                if ($coordinates) {
                    // 🆕 좌표 업데이트 전 로그
                    $this->log("   📍 좌표 업데이트 시작: ID={$place->ID}, lat={$coordinates['latitude']}, lon={$coordinates['longitude']}");
                    
                    // 좌표 업데이트
                    $lat_result = update_post_meta($place->ID, 'latitude', (string)$coordinates['latitude']);
                    $lon_result = update_post_meta($place->ID, 'longitude', (string)$coordinates['longitude']);
                    $time_result = update_post_meta($place->ID, 'geocoded_at', current_time('mysql'));
                    
                    // 🆕 업데이트 결과 확인
                    $saved_lat = get_post_meta($place->ID, 'latitude', true);
                    $saved_lon = get_post_meta($place->ID, 'longitude', true);
                    
                    $this->log("   ✅ 성공: {$coordinates['latitude']}, {$coordinates['longitude']}");
                    $this->log("   💾 저장 확인: lat={$saved_lat}, lon={$saved_lon}");
                    
                    if ($saved_lat != $coordinates['latitude'] || $saved_lon != $coordinates['longitude']) {
                        $this->log("   ⚠️ 경고: 저장된 좌표가 다릅니다!");
                    }
                    
                    $results['success_count']++;
                    
                    // 정적지도 생성
                    $this->generate_static_map($place->ID, $coordinates['latitude'], $coordinates['longitude'], $place->post_title);
                    
                } else {
                    $error_msg = "지오코딩 실패: {$place->post_title}";
                    $this->log("   ❌ {$error_msg}");
                    $results['errors'][] = $error_msg;
                    $results['error_count']++;
                }
                
                // 진행상황 콜백 호출
                if ($progress_callback) {
                    $progress_callback(array(
                        'processed' => $current_num,
                        'current_place' => $place->post_title,
                        'success_count' => $results['success_count'],
                        'error_count' => $results['error_count']
                    ));
                }
                
                // API 제한 준수 (0.2초 대기)
                usleep(200000);
                
            } catch (Exception $e) {
                $error_msg = "오류 발생: {$place->post_title} - {$e->getMessage()}";
                $this->log("   ❌ 예외: {$error_msg}");
                $results['errors'][] = $error_msg;
                $results['error_count']++;
            }
        }
        
        $success_rate = $total_places > 0 ? round(($results['success_count'] / $total_places) * 100, 1) : 0;
        
        $this->log("🏁 배치 처리 완료");
        $this->log("   ✅ 성공: {$results['success_count']}개");
        $this->log("   ❌ 실패: {$results['error_count']}개");
        $this->log("   📊 성공률: {$success_rate}%");
        
        return $results;
    }
    
    /**
     * 정적지도 생성
     */
    private function generate_static_map($place_id, $latitude, $longitude, $title) {
        try {
            if (class_exists('NaverStaticMapGeneratorMapsFixed')) {
                $generator = new NaverStaticMapGeneratorMapsFixed();
                $result = $generator->generatePlaceMap($place_id, $latitude, $longitude, $title);
                
                if ($result['success']) {
                    $this->log("   🗺️ 정적지도 생성 완료: {$title}");
                } else {
                    $this->log("   ⚠️ 정적지도 생성 실패: " . $result['error']);
                }
            } else {
                $this->log("   ⚠️ 정적지도 생성기 클래스를 찾을 수 없음");
            }
        } catch (Exception $e) {
            $this->log("   ❌ 정적지도 생성 오류: " . $e->getMessage());
        }
    }
    
    /**
     * 🔧 수정된 지오코딩 API 호출 (올바른 방식)
     */
    private function geocode_address($address, $is_test = false) {
        if (empty($address)) {
            $this->log("   ❌ 빈 주소");
            return false;
        }
        
        // 🆕 Mock 모드 체크
        if ($this->mock_mode) {
            $this->log("   ⚠️ Mock 모드 활성화됨 - 실제 API 호출하지 않음");
            // Mock 좌표 반환
            $default_mock = array('latitude' => 37.5466, 'longitude' => 127.0529);
            foreach ($this->mock_coordinates as $mock_address => $coords) {
                if (strpos($address, $mock_address) !== false) {
                    $this->log("   🎭 Mock 좌표 사용: {$coords['latitude']}, {$coords['longitude']}");
                    return $coords;
                }
            }
            $this->log("   🎭 기본 Mock 좌표 사용: {$default_mock['latitude']}, {$default_mock['longitude']}");
            return $default_mock;
        }
        
        // 🆕 실제 API 호출 시작 로그
        $this->log("   🌐 실제 네이버 지오코딩 API 호출 시작");
        $this->log("   📍 요청 주소: {$address}");
        
        $encoded_address = urlencode($address);
        $url = $this->api_base_url . '?query=' . $encoded_address;
        
        if ($is_test) {
            $this->log("🌐 API URL: {$url}");
        }
        
        // 🔧 수정된 헤더 (공식 문서 기준)
        $headers = array(
            'x-ncp-apigw-api-key-id' => $this->naver_client_id,
            'x-ncp-apigw-api-key' => $this->naver_client_secret,
            'Accept' => 'application/json'
        );
        
        // 🔧 수정된 요청 옵션
        $args = array(
            'headers' => $headers,
            'timeout' => 30
        );
        
        if ($is_test) {
            $this->log("📤 요청 헤더:");
            $this->log("   x-ncp-apigw-api-key-id: " . substr($this->naver_client_id, 0, 8) . "...");
            $this->log("   User-Agent: " . $args['user-agent']);
            $this->log("   SSL 검증: 활성화");
            $this->log("   사이트 URL: " . home_url());
        }
        
        // 🔧 요청 실행
        $response = wp_remote_get($url, $args);
        
        // 🔧 에러 처리 개선
        if (is_wp_error($response)) {
            $error_msg = "API 요청 오류: " . $response->get_error_message();
            $this->log("   ❌ {$error_msg}");
            
            // 🔧 cURL 대안 시도 (wp_remote_get 실패시)
            if ($is_test && function_exists('curl_init')) {
                $this->log("🔄 cURL 대안 시도...");
                return $this->geocode_with_curl($address, $is_test);
            }
            
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($is_test) {
            $this->log("📥 응답 코드: {$response_code}");
            $this->log("📥 응답 본문 (처음 200자): " . substr($body, 0, 200) . "...");
        }
        
        // 🔧 HTTP 상태 코드 처리 개선
        if ($response_code !== 200) {
            $error_detail = $this->get_http_error_detail($response_code);
            $error_msg = "HTTP 오류 {$response_code}: {$error_detail}";
            $this->log("   ❌ {$error_msg}");
            
            if ($is_test) {
                $this->log("   응답 본문: " . substr($body, 0, 500));
            }
            
            return false;
        }
        
        $data = json_decode($body, true);
        
        if (!$data) {
            $this->log("   ❌ JSON 파싱 실패");
            if ($is_test) {
                $this->log("   응답 본문: " . substr($body, 0, 500));
            }
            return false;
        }
        
        if ($is_test) {
            $status = isset($data['status']) ? $data['status'] : 'unknown';
            $address_count = isset($data['addresses']) ? count($data['addresses']) : 0;
            $this->log("📊 응답 상태: {$status}");
            $this->log("📊 결과 개수: {$address_count}");
        }
        
        if (!isset($data['status']) || $data['status'] !== 'OK') {
            $status = isset($data['status']) ? $data['status'] : 'unknown';
            $error_msg = "지오코딩 상태 오류: {$status}";
            if (isset($data['errorMessage'])) {
                $error_msg .= " - " . $data['errorMessage'];
            }
            $this->log("   ❌ {$error_msg}");
            return false;
        }
        
        if (empty($data['addresses'])) {
            $this->log("   ❌ 주소를 찾을 수 없음: {$address}");
            return false;
        }
        
        $address_info = $data['addresses'][0];
        $latitude = $address_info['y']; // 문자열로 유지
        $longitude = $address_info['x']; // 문자열로 유지
        
        // 🆕 좌표 유효성 검증 로그
        $this->log("   📍 API 응답 좌표: 위도={$latitude}, 경도={$longitude}");
        
        if ($is_test) {
            $this->log("📍 좌표: {$latitude}, {$longitude}");
            $road_addr = isset($address_info['roadAddress']) ? $address_info['roadAddress'] : '없음';
            $jibun_addr = isset($address_info['jibunAddress']) ? $address_info['jibunAddress'] : '없음';
            $this->log("🏢 도로명주소: {$road_addr}");
            $this->log("🏠 지번주소: {$jibun_addr}");
        }
        
        // 서울 범위 검증
        $lat_float = floatval($latitude);
        $lon_float = floatval($longitude);
        if ($lat_float < 37.4 || $lat_float > 37.7 || $lon_float < 126.8 || $lon_float > 127.2) {
            $this->log("   ⚠️ 좌표 범위 벗어남: {$latitude}, {$longitude} (서울 범위 아님)");
            // 범위 벗어나도 일단 저장 (경고만 표시)
        }
        
        return array(
            'latitude' => $latitude,
            'longitude' => $longitude,
            'road_address' => isset($address_info['roadAddress']) ? $address_info['roadAddress'] : '',
            'jibun_address' => isset($address_info['jibunAddress']) ? $address_info['jibunAddress'] : ''
        );
    }
    
    /**
     * 🔧 cURL 대안 방식 (wp_remote_get 실패시 사용)
     */
    private function geocode_with_curl($address, $is_test = false) {
        if (!function_exists('curl_init')) {
            $this->log("   ❌ cURL이 지원되지 않습니다.");
            return false;
        }
        
        $encoded_address = urlencode($address);
        $url = $this->api_base_url . '?query=' . $encoded_address;
        
        if ($is_test) {
            $this->log("🌐 cURL URL: {$url}");
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'WordPress/' . get_bloginfo('version') . ' (cURL-Geocoding)',
            CURLOPT_HTTPHEADER => array(
                'x-ncp-apigw-api-key-id: ' . $this->naver_client_id,
                'x-ncp-apigw-api-key: ' . $this->naver_client_secret,
                'Accept: application/json',
                'Content-Type: application/json',
                'Accept-Language: ko-KR,ko;q=0.9'
            ),
            CURLOPT_REFERER => home_url(),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5
        ));
        
        $response_body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            $this->log("   ❌ cURL 오류: {$curl_error}");
            return false;
        }
        
        if ($is_test) {
            $this->log("📥 cURL 응답 코드: {$http_code}");
            $this->log("📥 cURL 응답 본문 (처음 200자): " . substr($response_body, 0, 200) . "...");
        }
        
        if ($http_code !== 200) {
            $error_detail = $this->get_http_error_detail($http_code);
            $this->log("   ❌ cURL HTTP 오류 {$http_code}: {$error_detail}");
            return false;
        }
        
        $data = json_decode($response_body, true);
        
        if (!$data || !isset($data['status']) || $data['status'] !== 'OK' || empty($data['addresses'])) {
            $this->log("   ❌ cURL 응답 파싱 실패 또는 주소 없음");
            return false;
        }
        
        $address_info = $data['addresses'][0];
        $latitude = floatval($address_info['y']);
        $longitude = floatval($address_info['x']);
        
        if ($is_test) {
            $this->log("✅ cURL 성공 - 좌표: {$latitude}, {$longitude}");
        }
        
        return array(
            'latitude' => $latitude,
            'longitude' => $longitude,
            'road_address' => isset($address_info['roadAddress']) ? $address_info['roadAddress'] : '',
            'jibun_address' => isset($address_info['jibunAddress']) ? $address_info['jibunAddress'] : ''
        );
    }
    
    /**
     * HTTP 에러 상세 설명
     */
    private function get_http_error_detail($code) {
        $errors = array(
            400 => '잘못된 요청 - 파라미터 확인 필요',
            401 => '인증 실패 - API 키 확인 필요',
            403 => '권한 없음 - 지오코딩 서비스 활성화 또는 도메인 제한 확인 필요',
            404 => '요청한 리소스를 찾을 수 없음',
            429 => '요청 한도 초과 - 잠시 후 다시 시도',
            500 => '서버 내부 오류',
            502 => '게이트웨이 오류',
            503 => '서비스 사용 불가'
        );
        
        return isset($errors[$code]) ? $errors[$code] : '알 수 없는 HTTP 오류';
    }
    
    /**
     * 로그 기록 (향상된 버전)
     */
    private function log($message) {
        $timestamp = current_time('H:i:s');
        $log_entry = "[{$timestamp}] {$message}";
        $this->process_logs[] = $log_entry;
        
        // WordPress 디버그 로그에도 기록
        if ($this->debug_mode) {
            error_log('[배치지오코딩-수정] ' . $message);
        }
        
        // 콘솔 출력 (AJAX 호출시에는 표시되지 않음)
        if (!wp_doing_ajax() && $this->debug_mode && (defined('WP_CLI') && WP_CLI)) {
            echo $log_entry . "\n";
        }
    }
    
    /**
     * 프로세스 로그 조회
     */
    public function get_process_logs() {
        return $this->process_logs;
    }
}

// 🧪 즉시 테스트 실행 (관리자 페이지에서만)
if (is_admin() && isset($_GET['test_fixed_geocoding'])) {
    echo "<h2>🔧 수정된 지오코딩 API 테스트</h2>";
    
    $processor = new BatchGeocodingProcessor();
    $result = $processor->test_geocoding();
    
    if ($result['success']) {
        echo "<p style='color: green;'><strong>✅ " . $result['message'] . "</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>❌ " . $result['error'] . "</strong></p>";
    }
    
    echo "<h3>📝 처리 로그:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    foreach ($processor->get_process_logs() as $log) {
        echo htmlspecialchars($log) . "\n";
    }
    echo "</pre>";
    
    echo "<p><a href='" . remove_query_arg('test_fixed_geocoding') . "'>← 돌아가기</a></p>";
}
?>