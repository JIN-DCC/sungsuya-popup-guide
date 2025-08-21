<?php
/**
 * WordPress 관리자 메뉴에 API 테스트 페이지 추가
 */

// 관리자 메뉴에 테스트 페이지 추가
function add_naver_api_test_menu() {
    add_submenu_page(
        'edit.php?post_type=places',
        'API 테스트',
        '🧪 API 테스트',
        'manage_options',
        'naver-api-test',
        'render_naver_api_test_page'
    );
}
add_action('admin_menu', 'add_naver_api_test_menu');

// 테스트 페이지 렌더링
function render_naver_api_test_page() {
    ?>
    <div class="wrap">
        <h1>🧪 네이버 지오코딩 API 다중 엔드포인트 테스트</h1>
        
        <?php
        $client_id = get_option('sungsuya_naver_client_id', '');
        $client_secret = get_option('sungsuya_naver_client_secret', '');
        
        if (empty($client_id) || empty($client_secret)) {
            echo '<div class="notice notice-error"><p>API 키가 설정되지 않았습니다.</p></div>';
            return;
        }
        
        $test_address = '서울 성동구 성수일로 96';
        $encoded_address = urlencode($test_address);
        
        echo "<p><strong>테스트 주소:</strong> {$test_address}</p>";
        echo "<p><strong>Client ID:</strong> " . substr($client_id, 0, 8) . "...</p>";
        echo "<hr>";
        
        // 다양한 API 엔드포인트 테스트
        $endpoints = array(
            'ncp_geocoding_v2' => array(
                'url' => 'https://naveropenapi.apigw.ntruss.com/map-geocode/v2/geocode?query=' . $encoded_address,
                'headers' => array(
                    'X-NCP-APIGW-API-KEY-ID' => $client_id,
                    'X-NCP-APIGW-API-KEY' => $client_secret,
                    'Accept' => 'application/json'
                ),
                'description' => 'NCP Geocoding API v2 (현재 사용 중)'
            ),
            
            'ncp_directions' => array(
                'url' => 'https://naveropenapi.apigw.ntruss.com/map-direction/v1/driving?start=127.0548,37.5444&goal=127.05,37.54',
                'headers' => array(
                    'X-NCP-APIGW-API-KEY-ID' => $client_id,
                    'X-NCP-APIGW-API-KEY' => $client_secret,
                    'Accept' => 'application/json'
                ),
                'description' => 'NCP Directions API (연결 테스트)'
            ),
            
            'ncp_reverse_geocoding' => array(
                'url' => 'https://naveropenapi.apigw.ntruss.com/map-reversegeocode/v2/gc?coords=127.0548,37.5444&output=json',
                'headers' => array(
                    'X-NCP-APIGW-API-KEY-ID' => $client_id,
                    'X-NCP-APIGW-API-KEY' => $client_secret,
                    'Accept' => 'application/json'
                ),
                'description' => 'NCP Reverse Geocoding (역지오코딩)'
            ),
            
            'legacy_naver' => array(
                'url' => 'https://openapi.naver.com/v1/map/geocode?query=' . $encoded_address,
                'headers' => array(
                    'X-Naver-Client-Id' => $client_id,
                    'X-Naver-Client-Secret' => $client_secret,
                    'Accept' => 'application/json'
                ),
                'description' => '레거시 네이버 오픈 API (구버전)'
            )
        );
        
        foreach ($endpoints as $key => $config) {
            echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h3>🧪 {$config['description']}</h3>";
            echo "<p><strong>URL:</strong> <code style='font-size: 11px;'>" . $config['url'] . "</code></p>";
            
            $args = array(
                'headers' => $config['headers'],
                'timeout' => 15,
                'sslverify' => true,
                'user-agent' => 'WordPress/' . get_bloginfo('version') . ' (API-Test)',
            );
            
            $response = wp_remote_get($config['url'], $args);
            
            if (is_wp_error($response)) {
                echo "<p style='color: red; background: #ffe6e6; padding: 10px; border-radius: 3px;'>";
                echo "<strong>❌ WordPress HTTP 오류:</strong> " . $response->get_error_message();
                echo "</p>";
            } else {
                $response_code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                
                $status_color = $response_code == 200 ? 'green' : 'red';
                $bg_color = $response_code == 200 ? '#e6ffe6' : '#ffe6e6';
                
                echo "<p style='color: {$status_color}; background: {$bg_color}; padding: 10px; border-radius: 3px;'>";
                echo "<strong>응답 코드:</strong> {$response_code}";
                echo "</p>";
                
                if ($response_code == 200) {
                    $data = json_decode($body, true);
                    if ($data) {
                        echo "<p style='color: green;'><strong>✅ API 호출 성공!</strong></p>";
                        
                        // 응답 형식별 처리
                        if (isset($data['addresses']) && !empty($data['addresses'])) {
                            // NCP Geocoding 형식
                            $addr = $data['addresses'][0];
                            echo "<table style='border-collapse: collapse; width: 100%;'>";
                            echo "<tr><td style='border: 1px solid #ddd; padding: 5px;'><strong>좌표</strong></td>";
                            echo "<td style='border: 1px solid #ddd; padding: 5px;'>{$addr['y']}, {$addr['x']}</td></tr>";
                            echo "<tr><td style='border: 1px solid #ddd; padding: 5px;'><strong>도로명주소</strong></td>";
                            echo "<td style='border: 1px solid #ddd; padding: 5px;'>" . (isset($addr['roadAddress']) ? $addr['roadAddress'] : '없음') . "</td></tr>";
                            echo "<tr><td style='border: 1px solid #ddd; padding: 5px;'><strong>지번주소</strong></td>";
                            echo "<td style='border: 1px solid #ddd; padding: 5px;'>" . (isset($addr['jibunAddress']) ? $addr['jibunAddress'] : '없음') . "</td></tr>";
                            echo "</table>";
                        } elseif (isset($data['route'])) {
                            echo "<p><strong>길찾기 결과:</strong> 정상 응답</p>";
                        } elseif (isset($data['results'])) {
                            echo "<p><strong>역지오코딩 결과:</strong> 정상 응답</p>";
                        } else {
                            echo "<details><summary><strong>전체 응답 보기</strong></summary>";
                            echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow: auto; font-size: 11px;'>";
                            echo htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                            echo "</pre></details>";
                        }
                    } else {
                        echo "<p style='color: orange;'><strong>⚠️ JSON 파싱 실패</strong></p>";
                        echo "<p>응답 내용: " . htmlspecialchars(substr($body, 0, 200)) . "...</p>";
                    }
                } else {
                    // 에러 응답 분석
                    $data = json_decode($body, true);
                    if ($data && isset($data['error'])) {
                        echo "<table style='border-collapse: collapse; width: 100%;'>";
                        echo "<tr><td style='border: 1px solid #ddd; padding: 5px;'><strong>에러 코드</strong></td>";
                        echo "<td style='border: 1px solid #ddd; padding: 5px;'>" . (isset($data['error']['errorCode']) ? $data['error']['errorCode'] : '없음') . "</td></tr>";
                        echo "<tr><td style='border: 1px solid #ddd; padding: 5px;'><strong>에러 메시지</strong></td>";
                        echo "<td style='border: 1px solid #ddd; padding: 5px;'>" . (isset($data['error']['message']) ? $data['error']['message'] : '없음') . "</td></tr>";
                        echo "<tr><td style='border: 1px solid #ddd; padding: 5px;'><strong>세부사항</strong></td>";
                        echo "<td style='border: 1px solid #ddd; padding: 5px;'>" . (isset($data['error']['details']) ? $data['error']['details'] : '없음') . "</td></tr>";
                        echo "</table>";
                    } else {
                        echo "<p>원시 응답: " . htmlspecialchars(substr($body, 0, 300)) . "...</p>";
                    }
                }
            }
            
            echo "</div>";
        }
        
        // 시스템 정보
        echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f9f9f9;'>";
        echo "<h3>🛠️ 시스템 정보</h3>";
        echo "<table style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><td style='border: 1px solid #ddd; padding: 5px;'><strong>WordPress 버전</strong></td><td style='border: 1px solid #ddd; padding: 5px;'>" . get_bloginfo('version') . "</td></tr>";
        echo "<tr><td style='border: 1px solid #ddd; padding: 5px;'><strong>PHP 버전</strong></td><td style='border: 1px solid #ddd; padding: 5px;'>" . phpversion() . "</td></tr>";
        echo "<tr><td style='border: 1px solid #ddd; padding: 5px;'><strong>사이트 URL</strong></td><td style='border: 1px solid #ddd; padding: 5px;'>" . home_url() . "</td></tr>";
        echo "<tr><td style='border: 1px solid #ddd; padding: 5px;'><strong>현재 시간</strong></td><td style='border: 1px solid #ddd; padding: 5px;'>" . current_time('Y-m-d H:i:s') . "</td></tr>";
        echo "<tr><td style='border: 1px solid #ddd; padding: 5px;'><strong>cURL 버전</strong></td><td style='border: 1px solid #ddd; padding: 5px;'>" . (function_exists('curl_version') ? curl_version()['version'] : '없음') . "</td></tr>";
        echo "</table>";
        echo "</div>";
        
        // 네트워크 연결 테스트
        echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f9f9f9;'>";
        echo "<h3>🌐 네트워크 연결 테스트</h3>";
        
        $test_sites = array(
            'Google' => 'https://www.google.com',
            'Naver' => 'https://www.naver.com',
            'NCP API Gateway' => 'https://naveropenapi.apigw.ntruss.com',
            'OpenAPI Naver' => 'https://openapi.naver.com'
        );
        
        echo "<table style='border-collapse: collapse; width: 100%;'>";
        foreach ($test_sites as $name => $url) {
            $response = wp_remote_get($url, array('timeout' => 10, 'sslverify' => true));
            if (is_wp_error($response)) {
                $status = '❌ 실패: ' . $response->get_error_message();
                $color = 'red';
            } else {
                $code = wp_remote_retrieve_response_code($response);
                $status = "✅ 성공 ({$code})";
                $color = 'green';
            }
            echo "<tr><td style='border: 1px solid #ddd; padding: 5px;'><strong>{$name}</strong></td>";
            echo "<td style='border: 1px solid #ddd; padding: 5px; color: {$color};'>{$status}</td></tr>";
        }
        echo "</table>";
        echo "</div>";
        ?>
        
        <p><a href="<?php echo admin_url('edit.php?post_type=places&page=integrated-map-generation'); ?>" class="button">← 통합 지도생성 시스템으로 돌아가기</a></p>
    </div>
    <?php
}
?>