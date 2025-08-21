<?php
/**
 * 성수야! V2 - PWA 지원 함수
 * 
 * Progressive Web App 관련 기능
 * 
 * @package SungsuyaV2
 * @version 2.0.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Service Worker 등록
 */
function sungsuya_register_service_worker() {
    if (!is_admin()) {
        ?>
        <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?php echo esc_url(get_template_directory_uri() . '/pwa/sw.js'); ?>')
                    .then(function(registration) {
                        console.log('✅ Service Worker 등록 성공:', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('❌ Service Worker 등록 실패:', error);
                    });
            });
        }
        </script>
        <?php
    }
}
add_action('wp_footer', 'sungsuya_register_service_worker');

/**
 * 앱 설정 전역 변수 추가
 */
function sungsuya_add_app_config() {
    if (!is_admin()) {
        $config = array(
            'apiUrl' => home_url('/wp-json/sungsuya/v2/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'maxStores' => 5,
            'version' => SUNGSUYA_VERSION,
            'isOffline' => false,
        );
        
        ?>
        <script>
        window.SUNGSUYA_CONFIG = <?php echo json_encode($config); ?>;
        </script>
        <?php
    }
}
add_action('wp_head', 'sungsuya_add_app_config');

/**
 * 오프라인 페이지 생성
 */
function sungsuya_create_offline_page() {
    $offline_page = get_page_by_path('offline');
    
    if (!$offline_page) {
        $page_data = array(
            'post_title' => '오프라인',
            'post_content' => '인터넷 연결을 확인해주세요. 오프라인에서도 이전에 방문한 페이지는 볼 수 있습니다.',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => 'offline'
        );
        
        wp_insert_post($page_data);
    }
}
add_action('after_switch_theme', 'sungsuya_create_offline_page');

/**
 * 매니페스트 JSON 동적 생성
 */
function sungsuya_generate_manifest() {
    if (isset($_GET['manifest'])) {
        header('Content-Type: application/json');
        
        $manifest = array(
            'name' => '성수야! - 성수동 팝업스토어 가이드',
            'short_name' => '성수야!',
            'description' => '성수동의 모든 팝업스토어 정보와 투어 플래너',
            'start_url' => home_url('/'),
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#667eea',
            'orientation' => 'portrait-primary',
            'scope' => home_url('/'),
            'icons' => array(
                array(
                    'src' => get_template_directory_uri() . '/assets/images/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ),
                array(
                    'src' => get_template_directory_uri() . '/assets/images/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                )
            ),
            'categories' => array('travel', 'shopping', 'lifestyle'),
            'lang' => 'ko-KR',
            'dir' => 'ltr'
        );
        
        echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
add_action('init', 'sungsuya_generate_manifest');
