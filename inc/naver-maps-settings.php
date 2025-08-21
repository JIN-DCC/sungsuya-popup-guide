<?php
/**
 * 네이버 지도 API 설정 페이지 - V2
 * 
 * @package SungsuyaV2
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * 네이버 지도 API 설정 메뉴 추가
 */
function sungsuya_add_naver_maps_admin_menu() {
    add_options_page(
        '네이버 지도 API 설정',
        '네이버 지도 설정',
        'manage_options',
        'naver-maps-settings',
        'sungsuya_naver_maps_settings_page'
    );
}
add_action('admin_menu', 'sungsuya_add_naver_maps_admin_menu');

/**
 * 설정 등록
 */
function sungsuya_register_naver_maps_settings() {
    register_setting('naver_maps_settings', 'sungsuya_naver_client_id');
    register_setting('naver_maps_settings', 'sungsuya_naver_client_secret');
    
    add_settings_section(
        'naver_maps_main',
        '네이버 지도 API 설정',
        'sungsuya_naver_maps_section_callback',
        'naver-maps-settings'
    );
    
    add_settings_field(
        'sungsuya_naver_client_id',
        'NCP Client ID',
        'sungsuya_client_id_callback',
        'naver-maps-settings',
        'naver_maps_main'
    );
    
    add_settings_field(
        'sungsuya_naver_client_secret',
        'NCP Client Secret',
        'sungsuya_client_secret_callback',
        'naver-maps-settings',
        'naver_maps_main'
    );
}
add_action('admin_init', 'sungsuya_register_naver_maps_settings');

/**
 * 섹션 설명
 */
function sungsuya_naver_maps_section_callback() {
    echo '<div class="notice notice-success">';
    echo '<h4>✅ NCP Maps API (최신)</h4>';
    echo '<p>현재 최신 <strong>NCP Maps API</strong>를 사용하고 있습니다.</p>';
    echo '<p><strong>중요:</strong> API URL은 <code>https://oapi.map.naver.com</code>이고 파라미터는 <code>ncpKeyId</code>를 사용합니다.</p>';
    echo '<p><a href="https://www.ncloud.com/" target="_blank">네이버 클라우드 플랫폼</a>에서 Maps API 키를 발급받아 입력하세요.</p>';
    echo '</div>';
}

/**
 * Client ID 입력 필드
 */
function sungsuya_client_id_callback() {
    $client_id = get_option('sungsuya_naver_client_id', '');
    echo '<input type="text" id="sungsuya_naver_client_id" name="sungsuya_naver_client_id" value="' . esc_attr($client_id) . '" class="regular-text" placeholder="예: qosb7em5i9" />';
    echo '<p class="description">네이버 클라우드 플랫폼에서 발급받은 Client ID를 입력하세요. (NCP Maps API용)</p>';
    echo '<p class="description"><strong>참고:</strong> URL에는 <code>ncpKeyId</code> 파라미터로 사용됩니다.</p>';
}

/**
 * Client Secret 입력 필드
 */
function sungsuya_client_secret_callback() {
    $client_secret = get_option('sungsuya_naver_client_secret', '');
    echo '<input type="password" id="sungsuya_naver_client_secret" name="sungsuya_naver_client_secret" value="' . esc_attr($client_secret) . '" class="regular-text" placeholder="예: ABCDEFGHIJ" />';
    echo '<button type="button" id="toggle_secret" class="button" style="margin-left: 10px;">보기/숨기기</button>';
    echo '<p class="description">네이버 클라우드 플랫폼에서 발급받은 Client Secret을 입력하세요. (서버사이드 API용)</p>';
}

/**
 * 설정 페이지 HTML
 */
function sungsuya_naver_maps_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    if (isset($_GET['settings-updated'])) {
        add_settings_error('naver_maps_messages', 'naver_maps_message', __('설정이 저장되었습니다.'), 'updated');
    }
    
    settings_errors('naver_maps_messages');
    ?>
    
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <!-- API 키 상태 확인 -->
        <div class="notice notice-info">
            <h3>📊 현재 API 설정 상태</h3>
            <?php
            $client_id = get_option('sungsuya_naver_client_id', '');
            $client_secret = get_option('sungsuya_naver_client_secret', '');
            
            echo '<ul>';
            if (empty($client_id)) {
                echo '<li>❌ <strong>Client ID:</strong> 설정되지 않음</li>';
            } else {
                echo '<li>✅ <strong>Client ID:</strong> ' . esc_html(substr($client_id, 0, 8)) . '••••••••</li>';
            }
            
            if (empty($client_secret)) {
                echo '<li>❌ <strong>Client Secret:</strong> 설정되지 않음</li>';
            } else {
                echo '<li>✅ <strong>Client Secret:</strong> ••••••••••••</li>';
            }
            echo '</ul>';
            
            if (empty($client_id) || empty($client_secret)) {
                echo '<p><strong>⚠️ 경고:</strong> 네이버 지도 기능이 제대로 작동하지 않을 수 있습니다.</p>';
            } else {
                echo '<p><strong>✅ 완료:</strong> 네이버 지도 API 설정이 완료되었습니다!</p>';
            }
            ?>
        </div>
        
        <form action="options.php" method="post">
            <?php
            settings_fields('naver_maps_settings');
            do_settings_sections('naver-maps-settings');
            submit_button('설정 저장');
            ?>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#toggle_secret').click(function() {
            var secretField = $('#sungsuya_naver_client_secret');
            if (secretField.attr('type') === 'password') {
                secretField.attr('type', 'text');
                $(this).text('숨기기');
            } else {
                secretField.attr('type', 'password');
                $(this).text('보기');
            }
        });
    });
    </script>
    <?php
}

/**
 * functions.php에서 사용할 함수들
 */
function sungsuya_get_naver_maps_client_id() {
    return get_option('sungsuya_naver_client_id', '');
}

function sungsuya_get_naver_maps_client_secret() {
    return get_option('sungsuya_naver_client_secret', '');
}

function sungsuya_is_naver_maps_configured() {
    $client_id = get_option('sungsuya_naver_client_id', '');
    $client_secret = get_option('sungsuya_naver_client_secret', '');
    
    return !empty($client_id) && !empty($client_secret);
}