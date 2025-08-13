<?php
/**
 * WordPress 관리자에서 API 키 즉시 업데이트
 */

// 관리자 메뉴에 임시 업데이트 페이지 추가
function add_api_key_update_menu() {
    add_submenu_page(
        'edit.php?post_type=places',
        'API 키 업데이트',
        '🔑 API 키 수정',
        'manage_options',
        'api-key-update',
        'render_api_key_update_page'
    );
}
add_action('admin_menu', 'add_api_key_update_menu');

function render_api_key_update_page() {
    if (!current_user_can('manage_options')) {
        wp_die('권한이 없습니다.');
    }
    
    ?>
    <div class="wrap">
        <h1>🔑 네이버 API 키 업데이트</h1>
        
        <?php
        // 자동 업데이트 실행
        if (isset($_GET['auto_update']) && $_GET['auto_update'] === '1') {
            $correct_client_id = 'qosb7em5i9';
            $correct_client_secret = 'K0AcIQ4Q9nyLdfwNeICJQ3CUSvvWMuU3D8rebuHc';
            
            $id_updated = update_option('sungsuya_naver_client_id', $correct_client_id);
            $secret_updated = update_option('sungsuya_naver_client_secret', $correct_client_secret);
            
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>✅ API 키가 올바른 값으로 업데이트되었습니다!</strong></p>';
            echo '</div>';
            
            // 즉시 API 테스트
            echo '<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f9f9f9;">';
            echo '<h3>🧪 즉시 API 테스트</h3>';
            
            $test_result = test_geocoding_api($correct_client_id, $correct_client_secret);
            echo $test_result;
            echo '</div>';
        }
        
        // 현재 설정 확인
        $current_client_id = get_option('sungsuya_naver_client_id', '');
        $current_client_secret = get_option('sungsuya_naver_client_secret', '');
        
        echo '<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;">';
        echo '<h3>📋 현재 API 키 설정</h3>';
        echo '<table style="border-collapse: collapse; width: 100%;">';
        echo '<tr><td style="border: 1px solid #ddd; padding: 5px; background: #f9f9f9;"><strong>Client ID</strong></td>';
        echo '<td style="border: 1px solid #ddd; padding: 5px;">' . ($current_client_id ?: '❌ 미설정') . '</td></tr>';
        echo '<tr><td style="border: 1px solid #ddd; padding: 5px; background: #f9f9f9;"><strong>Client Secret</strong></td>';
        echo '<td style="border: 1px solid #ddd; padding: 5px;">' . ($current_client_secret ? substr($current_client_secret, 0, 10) . '...' : '❌ 미설정') . '</td></tr>';
        echo '</table>';
        echo '</div>';
        
        echo '<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #e6f3ff;">';
        echo '<h3>🔧 올바른 API 키 값</h3>';
        echo '<table style="border-collapse: collapse; width: 100%;">';
        echo '<tr><td style="border: 1px solid #ddd; padding: 5px; background: #f9f9f9;"><strong>Client ID</strong></td>';
        echo '<td style="border: 1px solid #ddd; padding: 5px; font-family: monospace;">qosb7em5i9</td></tr>';
        echo '<tr><td style="border: 1px solid #ddd; padding: 5px; background: #f9f9f9;"><strong>Client Secret</strong></td>';
        echo '<td style="border: 1px solid #ddd; padding: 5px; font-family: monospace;">K0AcIQ4Q9nyLdfwNeICJQ3CUSvvWMuU3D8rebuHc</td></tr>';
        echo '</table>';
        echo '</div>';
        
        // 비교 분석
        $correct_client_id = 'qosb7em5i9';
        $is_correct = ($current_client_id === $correct_client_id);
        
        echo '<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: ' . ($is_correct ? '#d4edda' : '#f8d7da') . ';">';
        echo '<h3>🔍 키 비교 분석</h3>';
        if ($is_correct) {
            echo '<p style="color: green;"><strong>✅ Client ID가 올바르게 설정되어 있습니다!</strong></p>';
        } else {
            echo '<p style="color: red;"><strong>❌ Client ID가 다릅니다!</strong></p>';
            echo '<p>현재: <code>' . $current_client_id . '</code></p>';
            echo '<p>정상: <code>qosb7em5i9</code></p>';
        }
        echo '</div>';
        
        // 자동 업데이트 버튼
        if (!$is_correct) {
            echo '<p>';
            echo '<a href="' . admin_url('edit.php?post_type=places&page=api-key-update&auto_update=1') . '" ';
            echo 'class="button button-primary" style="background: #d63384; border-color: #d63384;">';
            echo '🔄 올바른 API 키로 즉시 업데이트</a>';
            echo '</p>';
        }
        ?>
        
        <p><a href="<?php echo admin_url('edit.php?post_type=places&page=integrated-map-generation'); ?>" class="button">← 통합 지도생성 시스템으로 돌아가기</a></p>
    </div>
    <?php
}

function test_geocoding_api($client_id, $client_secret) {
    $test_address = '서울 성동구 성수일로 96';
    $encoded_address = urlencode($test_address);
    $url = 'https://naveropenapi.apigw.ntruss.com/map-geocode/v2/geocode?query=' . $encoded_address;
    
    $args = array(
        'headers' => array(
            'X-NCP-APIGW-API-KEY-ID' => $client_id,
            'X-NCP-APIGW-API-KEY' => $client_secret,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ),
        'timeout' => 15,
        'sslverify' => true
    );
    
    $response = wp_remote_get($url, $args);
    
    if (is_wp_error($response)) {
        return '<p style="color: red;"><strong>❌ 네트워크 오류:</strong> ' . $response->get_error_message() . '</p>';
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    $result = '<table style="border-collapse: collapse; width: 100%;">';
    $result .= '<tr><td style="border: 1px solid #ddd; padding: 5px;"><strong>테스트 주소</strong></td><td style="border: 1px solid #ddd; padding: 5px;">' . $test_address . '</td></tr>';
    $result .= '<tr><td style="border: 1px solid #ddd; padding: 5px;"><strong>HTTP 코드</strong></td>';
    
    if ($response_code === 200) {
        $result .= '<td style="border: 1px solid #ddd; padding: 5px; color: green;"><strong>' . $response_code . ' (성공!)</strong></td></tr>';
        
        $data = json_decode($body, true);
        if ($data && isset($data['addresses']) && !empty($data['addresses'])) {
            $addr = $data['addresses'][0];
            $result .= '<tr><td style="border: 1px solid #ddd; padding: 5px;"><strong>좌표</strong></td><td style="border: 1px solid #ddd; padding: 5px;">' . $addr['y'] . ', ' . $addr['x'] . '</td></tr>';
            $result .= '<tr><td style="border: 1px solid #ddd; padding: 5px;"><strong>도로명주소</strong></td><td style="border: 1px solid #ddd; padding: 5px;">' . (isset($addr['roadAddress']) ? $addr['roadAddress'] : '없음') . '</td></tr>';
        } else {
            $result .= '<tr><td style="border: 1px solid #ddd; padding: 5px;"><strong>결과</strong></td><td style="border: 1px solid #ddd; padding: 5px; color: orange;">응답은 받았지만 주소 결과 없음</td></tr>';
        }
    } else {
        $result .= '<td style="border: 1px solid #ddd; padding: 5px; color: red;"><strong>' . $response_code . ' (실패)</strong></td></tr>';
        $result .= '<tr><td style="border: 1px solid #ddd; padding: 5px;"><strong>오류 응답</strong></td><td style="border: 1px solid #ddd; padding: 5px;">' . htmlspecialchars(substr($body, 0, 200)) . '...</td></tr>';
    }
    
    $result .= '</table>';
    
    if ($response_code === 200) {
        $result = '<p style="color: green;"><strong>🎉 API 테스트 성공!</strong></p>' . $result;
    } else {
        $result = '<p style="color: red;"><strong>❌ API 테스트 실패</strong></p>' . $result;
    }
    
    return $result;
}
?>