<?php
/**
 * 크롤링 API 설정 페이지
 * 
 * 외부 API 키 관리 및 크롤링 설정
 * 
 * @package SungsuyaV2
 * @subpackage Admin
 * @version 1.0.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

// 관리자 권한 확인
if (!current_user_can('manage_options')) {
    wp_die('권한이 없습니다.');
}

// 설정 저장 처리
if (isset($_POST['save_api_settings']) && wp_verify_nonce($_POST['api_settings_nonce'], 'sungsuya_api_settings')) {
    
    // 네이버 API 설정
    update_option('sungsuya_naver_client_id', sanitize_text_field($_POST['naver_client_id']));
    update_option('sungsuya_naver_client_secret', sanitize_text_field($_POST['naver_client_secret']));
    
    // 카카오 API 설정
    update_option('sungsuya_kakao_rest_api_key', sanitize_text_field($_POST['kakao_rest_api_key']));
    
    // 구글 API 설정
    update_option('google_places_api_key', sanitize_text_field($_POST['google_places_api_key']));
    
    // 인스타그램 설정 (추후 구현)
    update_option('sungsuya_instagram_access_token', sanitize_text_field($_POST['instagram_access_token']));
    
    // 크롤링 기본 설정
    update_option('sungsuya_default_region', sanitize_text_field($_POST['default_region']));
    update_option('sungsuya_auto_publish', isset($_POST['auto_publish']) ? 'yes' : 'no');
    update_option('sungsuya_duplicate_check', isset($_POST['duplicate_check']) ? 'yes' : 'no');
    
    echo '<div class="notice notice-success"><p>설정이 저장되었습니다.</p></div>';
}

// 현재 설정값 가져오기
$naver_client_id = get_option('sungsuya_naver_client_id', '');
$naver_client_secret = get_option('sungsuya_naver_client_secret', '');
$kakao_rest_api_key = get_option('sungsuya_kakao_rest_api_key', '1c0820a5de8d41e36bf0e61f199352df');
$google_places_api_key = get_option('google_places_api_key', '');
$instagram_access_token = get_option('sungsuya_instagram_access_token', '');
$default_region = get_option('sungsuya_default_region', '성수동');
$auto_publish = get_option('sungsuya_auto_publish', 'yes');
$duplicate_check = get_option('sungsuya_duplicate_check', 'yes');

?>
<div class="wrap">
    <h1>🔍 외부 데이터 수집 API 설정</h1>
    <p>네이버, 카카오, 구글 등 외부 플랫폼에서 장소 정보를 수집하기 위한 API 키를 설정합니다.</p>
    <div class="notice notice-info">
        <p><strong>📌 주의:</strong> 이 설정은 데이터 크롤링 전용입니다. 소셜 로그인용 API는 "소셜 로그인 API" 메뉴에서 설정하세요.</p>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('sungsuya_api_settings', 'api_settings_nonce'); ?>
        
        <h2>네이버 API 설정</h2>
        <p>네이버 개발자센터에서 발급받은 API 키를 입력하세요. <a href="https://developers.naver.com/apps/#/register" target="_blank">API 키 발급하기 →</a></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">Client ID</th>
                <td>
                    <input type="text" name="naver_client_id" value="<?php echo esc_attr($naver_client_id); ?>" class="regular-text" />
                    <p class="description">네이버 개발자센터에서 발급받은 Client ID</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Client Secret</th>
                <td>
                    <input type="password" name="naver_client_secret" value="<?php echo esc_attr($naver_client_secret); ?>" class="regular-text" />
                    <p class="description">네이버 개발자센터에서 발급받은 Client Secret</p>
                </td>
            </tr>
            <tr>
                <th scope="row">API 상태</th>
                <td>
                    <?php
                    if (!empty($naver_client_id) && !empty($naver_client_secret)) {
                        // API 테스트
                        require_once get_template_directory() . '/inc/crawlers/class-naver-search-api.php';
                        $naver_api = new NaverSearchAPIClient();
                        $test_result = $naver_api->search_local('테스트', array('display' => 1));
                        
                        if (!is_wp_error($test_result)) {
                            echo '<span style="color: #46b450;">✓ 연결됨</span>';
                        } else {
                            echo '<span style="color: #dc3232;">✗ 연결 실패: ' . esc_html($test_result->get_error_message()) . '</span>';
                        }
                    } else {
                        echo '<span style="color: #666;">API 키를 입력해주세요</span>';
                    }
                    ?>
                </td>
            </tr>
        </table>
        
        <hr />
        
        <h2>카카오 API 설정</h2>
        <p>카카오 개발자센터에서 발급받은 REST API 키를 입력하세요. <a href="https://developers.kakao.com/console/app" target="_blank">API 키 발급하기 →</a></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">REST API 키</th>
                <td>
                    <input type="text" name="kakao_rest_api_key" value="<?php echo esc_attr($kakao_rest_api_key); ?>" class="regular-text" />
                    <p class="description">카카오 개발자센터에서 발급받은 REST API 키</p>
                </td>
            </tr>
            <tr>
                <th scope="row">API 상태</th>
                <td>
                    <?php
                    if (!empty($kakao_rest_api_key)) {
                        echo '<span style="color: #46b450;">✓ 키 입력됨</span>';
                    } else {
                        echo '<span style="color: #666;">API 키를 입력해주세요</span>';
                    }
                    ?>
                </td>
            </tr>
        </table>
        
        <hr />
        
        <h2>구글 API 설정</h2>
        <p>구글 클라우드 콘솔에서 발급받은 Places API 키를 입력하세요. <a href="https://console.cloud.google.com/" target="_blank">API 키 발급하기 →</a></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">Places API 키</th>
                <td>
                    <input type="text" name="google_places_api_key" value="<?php echo esc_attr($google_places_api_key); ?>" class="regular-text" />
                    <p class="description">구글 클라우드 콘솔에서 발급받은 Places API 키</p>
                </td>
            </tr>
            <tr>
                <th scope="row">API 상태</th>
                <td>
                    <?php
                    if (!empty($google_places_api_key)) {
                        // 간단한 API 테스트
                        $test_url = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json';
                        $test_url .= '?input=' . urlencode('성수동 카페');
                        $test_url .= '&inputtype=textquery';
                        $test_url .= '&fields=place_id,name';
                        $test_url .= '&key=' . $google_places_api_key;
                        
                        $response = wp_remote_get($test_url, array('timeout' => 5));
                        
                        if (!is_wp_error($response)) {
                            $body = wp_remote_retrieve_body($response);
                            $data = json_decode($body, true);
                            
                            if (isset($data['status']) && $data['status'] === 'OK') {
                                echo '<span style="color: #46b450;">✓ 연결됨</span>';
                            } elseif (isset($data['error_message'])) {
                                echo '<span style="color: #dc3232;">✗ 연결 실패: ' . esc_html($data['error_message']) . '</span>';
                            } else {
                                echo '<span style="color: #dc3232;">✗ 연결 실패: ' . esc_html($data['status'] ?? '알 수 없는 오류') . '</span>';
                            }
                        } else {
                            echo '<span style="color: #dc3232;">✗ 네트워크 오류</span>';
                        }
                    } else {
                        echo '<span style="color: #666;">API 키를 입력해주세요</span>';
                    }
                    ?>
                </td>
            </tr>
        </table>
        
        <hr />
        
        <h2>인스타그램 설정 (준비 중)</h2>
        <p>인스타그램 Basic Display API 또는 크롤링 설정 (추후 구현 예정)</p>
        
        <table class="form-table">
            <tr>
                <th scope="row">Access Token</th>
                <td>
                    <input type="text" name="instagram_access_token" value="<?php echo esc_attr($instagram_access_token); ?>" class="regular-text" disabled />
                    <p class="description">추후 구현 예정입니다</p>
                </td>
            </tr>
        </table>
        
        <hr />
        
        <h2>크롤링 기본 설정</h2>
        
        <table class="form-table">
            <tr>
                <th scope="row">기본 지역</th>
                <td>
                    <select name="default_region">
                        <option value="성수동" <?php selected($default_region, '성수동'); ?>>성수동</option>
                        <option value="연남동" <?php selected($default_region, '연남동'); ?>>연남동</option>
                        <option value="홍대" <?php selected($default_region, '홍대'); ?>>홍대</option>
                        <option value="강남" <?php selected($default_region, '강남'); ?>>강남</option>
                        <option value="건대" <?php selected($default_region, '건대'); ?>>건대</option>
                    </select>
                    <p class="description">크롤링 시 기본으로 사용할 지역</p>
                </td>
            </tr>
            <tr>
                <th scope="row">자동 발행</th>
                <td>
                    <label>
                        <input type="checkbox" name="auto_publish" value="yes" <?php checked($auto_publish, 'yes'); ?> />
                        크롤링된 데이터를 즉시 발행
                    </label>
                    <p class="description">체크 해제 시 임시글로 저장됩니다</p>
                </td>
            </tr>
            <tr>
                <th scope="row">중복 체크</th>
                <td>
                    <label>
                        <input type="checkbox" name="duplicate_check" value="yes" <?php checked($duplicate_check, 'yes'); ?> />
                        이름과 주소가 같은 장소 중복 체크
                    </label>
                    <p class="description">기존 포스트가 있으면 업데이트합니다</p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="save_api_settings" class="button button-primary" value="설정 저장" />
        </p>
    </form>
    
    <hr />
    
    <h2>API 사용 가이드</h2>
    
    <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-top: 20px;">
        <h3>네이버 API 설정 방법</h3>
        <ol>
            <li><a href="https://developers.naver.com/apps/#/register" target="_blank">네이버 개발자센터</a>에 접속하여 애플리케이션을 등록합니다.</li>
            <li>사용 API에서 "검색" → "지역"을 선택합니다.</li>
            <li>웹 서비스 URL에 <code><?php echo home_url(); ?></code>를 입력합니다.</li>
            <li>발급받은 Client ID와 Client Secret을 위에 입력합니다.</li>
        </ol>
        
        <h3>카카오 API 설정 방법</h3>
        <ol>
            <li><a href="https://developers.kakao.com/console/app" target="_blank">카카오 개발자센터</a>에 접속하여 애플리케이션을 생성합니다.</li>
            <li>플랫폼 설정에서 Web 플랫폼을 추가하고 사이트 도메인에 <code><?php echo home_url(); ?></code>를 입력합니다.</li>
            <li>앱 키에서 REST API 키를 복사하여 위에 입력합니다.</li>
        </ol>
        
        <h3>일일 사용 한도</h3>
        <ul>
            <li><strong>네이버 검색 API</strong>: 일 25,000회</li>
            <li><strong>카카오 로컬 API</strong>: 일 300,000회</li>
            <li><strong>구글 Places API</strong>: 월 $200 무료 크레딧 (약 28,500회)</li>
        </ul>
        
        <h3>구글 API 설정 방법</h3>
        <ol>
            <li><a href="https://console.cloud.google.com/" target="_blank">구글 클라우드 콘솔</a>에 접속하여 프로젝트를 생성합니다.</li>
            <li>API 및 서비스에서 "Places API"를 활성화합니다.</li>
            <li>사용자 인증 정보에서 API 키를 생성합니다.</li>
            <li>API 키 제한사항에서 HTTP 리퍼러를 <code><?php echo home_url(); ?>/*</code>로 설정합니다.</li>
            <li>발급받은 API 키를 위에 입력합니다.</li>
        </ol>
    </div>
</div>

<style>
.form-table th {
    width: 200px;
}
.regular-text {
    width: 400px;
}
</style>
