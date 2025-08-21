<?php
/**
 * PWA 메타 태그 및 최적화
 * 
 * @package SungsuyaV2
 * @since 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PWA 관련 메타 태그 추가 (최적화 버전)
 */
function sungsuya_pwa_enhanced_meta_tags() {
    ?>
    <!-- PWA 메타 태그 -->
    <meta name="theme-color" content="#667eea">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="성수야!">
    
    <!-- iOS 메타 태그 -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="성수야!">
    
    <!-- iOS 아이콘 -->
    <link rel="apple-touch-icon" sizes="72x72" href="<?php echo get_template_directory_uri(); ?>/assets/images/icon-72.png">
    <link rel="apple-touch-icon" sizes="96x96" href="<?php echo get_template_directory_uri(); ?>/assets/images/icon-96.png">
    <link rel="apple-touch-icon" sizes="128x128" href="<?php echo get_template_directory_uri(); ?>/assets/images/icon-128.png">
    <link rel="apple-touch-icon" sizes="144x144" href="<?php echo get_template_directory_uri(); ?>/assets/images/icon-144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo get_template_directory_uri(); ?>/assets/images/icon-152.png">
    <link rel="apple-touch-icon" sizes="192x192" href="<?php echo get_template_directory_uri(); ?>/assets/images/icon-192.png">
    <link rel="apple-touch-icon" sizes="384x384" href="<?php echo get_template_directory_uri(); ?>/assets/images/icon-384.png">
    <link rel="apple-touch-icon" sizes="512x512" href="<?php echo get_template_directory_uri(); ?>/assets/images/icon-512.png">
    
    <!-- iOS 스플래시 스크린 -->
    <link rel="apple-touch-startup-image" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)" href="<?php echo get_template_directory_uri(); ?>/assets/images/splash-1125x2436.png">
    <link rel="apple-touch-startup-image" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2)" href="<?php echo get_template_directory_uri(); ?>/assets/images/splash-828x1792.png">
    <link rel="apple-touch-startup-image" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2)" href="<?php echo get_template_directory_uri(); ?>/assets/images/splash-1536x2048.png">
    
    <!-- Windows 타일 -->
    <meta name="msapplication-TileColor" content="#667eea">
    <meta name="msapplication-TileImage" content="<?php echo get_template_directory_uri(); ?>/assets/images/icon-144.png">
    
    <!-- Manifest -->
    <link rel="manifest" href="<?php echo get_template_directory_uri(); ?>/pwa/manifest.json">
    
    <!-- 기타 아이콘 -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo get_template_directory_uri(); ?>/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo get_template_directory_uri(); ?>/assets/images/favicon-16x16.png">
    <?php
}
add_action('wp_head', 'sungsuya_pwa_enhanced_meta_tags', 1);

/**
 * Service Worker 등록 스크립트 (최적화 버전)
 */
function sungsuya_register_enhanced_service_worker() {
    ?>
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw-v2.js')
                .then(registration => {
                    console.log('Service Worker 등록 성공:', registration.scope);
                    
                    // 업데이트 확인
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                // 새 버전이 설치됨
                                if (confirm('새로운 버전이 있습니다. 업데이트하시겠습니까?')) {
                                    newWorker.postMessage({ type: 'SKIP_WAITING' });
                                    window.location.reload();
                                }
                            }
                        });
                    });
                })
                .catch(error => {
                    console.error('Service Worker 등록 실패:', error);
                });
        });
        
        // iOS PWA 감지 및 특별 처리
        if (window.navigator.standalone === true) {
            document.documentElement.classList.add('ios-pwa');
        }
        
        // 설치 프롬프트 처리
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // 설치 버튼 표시 (필요한 경우)
            const installButton = document.getElementById('pwa-install-button');
            if (installButton) {
                installButton.style.display = 'block';
                installButton.addEventListener('click', () => {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('PWA 설치됨');
                        }
                        deferredPrompt = null;
                    });
                });
            }
        });
        
        // 온라인/오프라인 상태 처리
        window.addEventListener('online', () => {
            document.body.classList.remove('offline');
        });
        
        window.addEventListener('offline', () => {
            document.body.classList.add('offline');
        });
    }
    </script>
    <?php
}
add_action('wp_footer', 'sungsuya_register_enhanced_service_worker', 99);

/**
 * PWA 설치 버튼 숏코드
 */
function sungsuya_pwa_install_button($atts) {
    $atts = shortcode_atts(array(
        'text' => '앱으로 설치하기',
        'class' => 'pwa-install-button'
    ), $atts);
    
    return sprintf(
        '<button id="pwa-install-button" class="%s" style="display:none;">%s</button>',
        esc_attr($atts['class']),
        esc_html($atts['text'])
    );
}
add_shortcode('pwa_install', 'sungsuya_pwa_install_button');

/**
 * iOS Safe Area 대응 CSS
 */
function sungsuya_safe_area_css() {
    ?>
    <style>
    /* iOS Safe Area 대응 */
    .ios-pwa {
        padding-top: env(safe-area-inset-top);
        padding-bottom: env(safe-area-inset-bottom);
    }
    
    .ios-pwa .site-header {
        top: env(safe-area-inset-top);
    }
    
    .ios-pwa .bottom-nav {
        padding-bottom: env(safe-area-inset-bottom);
    }
    
    /* 오프라인 인디케이터 */
    body.offline::before {
        content: '오프라인 모드';
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: #e53e3e;
        color: white;
        text-align: center;
        padding: 8px;
        font-size: 14px;
        z-index: 9999;
    }
    
    /* PWA 설치 버튼 스타일 */
    .pwa-install-button {
        background: #667eea;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .pwa-install-button:hover {
        background: #5a67d8;
        transform: translateY(-2px);
    }
    </style>
    <?php
}
add_action('wp_head', 'sungsuya_safe_area_css', 100);

/**
 * 오프라인 페이지 리다이렉트
 */
function sungsuya_offline_redirect() {
    ?>
    <script>
    // 오프라인 상태에서 특정 페이지로 리다이렉트
    if (!navigator.onLine && !window.location.pathname.includes('/offline')) {
        // Service Worker가 처리하므로 여기서는 추가 작업 불필요
    }
    </script>
    <?php
}
add_action('wp_footer', 'sungsuya_offline_redirect');
