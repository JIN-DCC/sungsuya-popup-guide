<?php
/**
 * 인증 시스템 설정 페이지
 * 
 * Google, 카카오 등 소셜 로그인 API 키 관리
 */

if (!defined('ABSPATH')) {
    exit;
}

// 설정 저장
if (isset($_POST['submit']) && check_admin_referer('sungsuya_auth_settings')) {
    update_option('google_client_id', sanitize_text_field($_POST['google_client_id']));
    update_option('google_client_secret', sanitize_text_field($_POST['google_client_secret']));
    update_option('kakao_javascript_key', sanitize_text_field($_POST['kakao_javascript_key']));
    update_option('kakao_rest_api_key', sanitize_text_field($_POST['kakao_rest_api_key']));
    
    echo '<div class="notice notice-success"><p>설정이 저장되었습니다.</p></div>';
}

// 현재 설정값 가져오기
$google_client_id = get_option('google_client_id', '');
$google_client_secret = get_option('google_client_secret', '');
$kakao_javascript_key = get_option('kakao_javascript_key', '');
$kakao_rest_api_key = get_option('kakao_rest_api_key', '');
?>

<div class="wrap">
    <h1>🔐 소셜 로그인 API 설정</h1>
    
    <div class="card">
        <h2>소셜 로그인 API 설정</h2>
        <p>PWA 앱에서 구글, 카카오 등 소셜 로그인을 사용하기 위한 API 키를 설정합니다.</p>
        <div class="notice notice-info">
            <p><strong>📌 주의:</strong> 이 설정은 사용자 로그인 전용입니다. 데이터 크롤링용 API는 "외부 데이터 수집 API" 메뉴에서 설정하세요.</p>
        </div>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('sungsuya_auth_settings'); ?>
        
        <h2>Google 로그인</h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="google_client_id">Google Client ID</label></th>
                <td>
                    <input type="text" id="google_client_id" name="google_client_id" 
                           value="<?php echo esc_attr($google_client_id); ?>" class="regular-text" />
                    <p class="description">
                        <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>에서 
                        OAuth 2.0 클라이언트 ID를 생성하세요.
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="google_client_secret">Google Client Secret</label></th>
                <td>
                    <input type="password" id="google_client_secret" name="google_client_secret" 
                           value="<?php echo esc_attr($google_client_secret); ?>" class="regular-text" />
                    <p class="description">보안을 위해 입력 후 별표로 표시됩니다.</p>
                </td>
            </tr>
        </table>
        
        <h2>카카오 로그인</h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="kakao_javascript_key">JavaScript 키</label></th>
                <td>
                    <input type="text" id="kakao_javascript_key" name="kakao_javascript_key" 
                           value="<?php echo esc_attr($kakao_javascript_key); ?>" class="regular-text" />
                    <p class="description">
                        <a href="https://developers.kakao.com/console/app" target="_blank">카카오 개발자</a>에서 
                        JavaScript 키를 확인하세요.
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="kakao_rest_api_key">REST API 키</label></th>
                <td>
                    <input type="text" id="kakao_rest_api_key" name="kakao_rest_api_key" 
                           value="<?php echo esc_attr($kakao_rest_api_key); ?>" class="regular-text" />
                    <p class="description">서버 사이드 검증용 REST API 키입니다.</p>
                </td>
            </tr>
        </table>
        
        <h2>리디렉션 URI 설정</h2>
        <div class="card">
            <p>각 플랫폼에 다음 리디렉션 URI를 등록해주세요:</p>
            <ul>
                <li><strong>Google (로컬):</strong> <code>http://localhost:10008/wp-json/sungsuya/v1/auth/google/callback</code></li>
                <li><strong>Google (운영):</strong> <code>https://www.sungsuya.com/wp-json/sungsuya/v1/auth/google/callback</code></li>
                <li><strong>카카오:</strong> <code><?php echo home_url('/wp-json/sungsuya/v1/auth/kakao/callback'); ?></code></li>
            </ul>
            <div class="notice notice-warning" style="margin-top: 10px;">
                <p><strong>⚠️ 중요:</strong> Google OAuth는 보안상 http://는 localhost만 허용합니다. 로컬 테스트 시 localhost:10008을 사용하세요.</p>
            </div>
        </div>
        
        <?php submit_button('설정 저장'); ?>
    </form>
    
    <div class="card" style="margin-top: 30px;">
        <h2>🧪 테스트</h2>
        <p>설정이 올바른지 테스트해보세요:</p>
        <button type="button" class="button" onclick="testGoogleAuth()">Google 로그인 테스트</button>
        <button type="button" class="button" onclick="testKakaoAuth()">카카오 로그인 테스트</button>
        <div id="test-result" style="margin-top: 10px;"></div>
    </div>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}
</style>

<script>
function testGoogleAuth() {
    const clientId = document.getElementById('google_client_id').value;
    if (!clientId) {
        document.getElementById('test-result').innerHTML = '<p style="color: red;">Google Client ID를 입력해주세요.</p>';
        return;
    }
    document.getElementById('test-result').innerHTML = '<p style="color: green;">✓ Google Client ID가 설정되었습니다.</p>';
}

function testKakaoAuth() {
    const jsKey = document.getElementById('kakao_javascript_key').value;
    if (!jsKey) {
        document.getElementById('test-result').innerHTML = '<p style="color: red;">카카오 JavaScript 키를 입력해주세요.</p>';
        return;
    }
    document.getElementById('test-result').innerHTML = '<p style="color: green;">✓ 카카오 JavaScript 키가 설정되었습니다.</p>';
}
</script>
