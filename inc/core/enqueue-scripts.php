<?php
/**
 * 스타일과 스크립트 관리
 * 
 * @package SungsuyaV2
 * @subpackage Core
 * @since 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 스타일과 스크립트 큐잉
 */
function sungsuya_enqueue_scripts() {
    // Tailwind CSS CDN 추가 (single-places.php 호환성을 위해)
    wp_enqueue_style(
        'tailwind-css',
        'https://cdn.tailwindcss.com',
        array(),
        null
    );
    
    // 핵심 디자인 시스템 CSS (전체 사이트 공통)
    wp_enqueue_style(
        'sungsuya-design-system', 
        SUNGSUYA_THEME_URL . '/assets/css/design-system.css', 
        array('tailwind-css'), 
        SUNGSUYA_VERSION
    );
    
    // 로컬 폰트 설정 (Google Fonts 대체)
    wp_enqueue_style(
        'sungsuya-local-fonts', 
        SUNGSUYA_THEME_URL . '/assets/css/local-fonts.css', 
        array(), 
        SUNGSUYA_VERSION
    );
    
    // 기존 CSS 파일들 (디자인 시스템 위에 로드)
    wp_enqueue_style(
        'sungsuya-variables', 
        SUNGSUYA_THEME_URL . '/assets/css/variables.css', 
        array('sungsuya-design-system'), 
        SUNGSUYA_VERSION
    );
    
    wp_enqueue_style(
        'sungsuya-components', 
        SUNGSUYA_THEME_URL . '/assets/css/components.css', 
        array('sungsuya-variables'), 
        SUNGSUYA_VERSION
    );
    
    wp_enqueue_style(
        'sungsuya-main', 
        SUNGSUYA_THEME_URL . '/assets/css/main.css', 
        array('sungsuya-components'), 
        SUNGSUYA_VERSION
    );
    
    // UI 간소화 오버라이드 (Phase 2)
    wp_enqueue_style(
        'sungsuya-ui-simplify', 
        SUNGSUYA_THEME_URL . '/assets/css/ui-simplify.css', 
        array('sungsuya-main', 'sungsuya-home', 'sungsuya-stores'), 
        SUNGSUYA_VERSION
    );
    
    // 섹션 간소화 오버라이드 (Phase 2 Task 2.3)
    wp_enqueue_style(
        'sungsuya-section-simplify', 
        SUNGSUYA_THEME_URL . '/assets/css/section-simplify.css', 
        array('sungsuya-ui-simplify'), 
        SUNGSUYA_VERSION
    );
    
    // 성능 최적화 CSS (Phase 3 Task 3.1)
    wp_enqueue_style(
        'sungsuya-performance-optimize', 
        SUNGSUYA_THEME_URL . '/assets/css/performance-optimize.css', 
        array('sungsuya-section-simplify'), 
        SUNGSUYA_VERSION
    );
    
    // 모바일 최적화 CSS (2025-07-02)
    if (wp_is_mobile()) {
        wp_enqueue_style(
            'sungsuya-mobile-optimize', 
            SUNGSUYA_THEME_URL . '/assets/css/mobile-optimize.css', 
            array('sungsuya-performance-optimize'), 
            SUNGSUYA_VERSION
        );
        
        // 모바일 스크롤 버그 수정 CSS (2025-07-04)
        wp_enqueue_style(
            'sungsuya-mobile-scroll-fix', 
            SUNGSUYA_THEME_URL . '/assets/css/mobile-scroll-fix.css', 
            array('sungsuya-mobile-optimize'), 
            SUNGSUYA_VERSION
        );
    }
    
    // 페이지별 특화 CSS
    sungsuya_enqueue_page_specific_styles();
    
    // Places 및 팝업스토어 상세페이지 전용 스타일과 스크립트
    sungsuya_enqueue_single_scripts();
    
    // 기본 JavaScript 파일들 (전체 사이트에서 사용)
    sungsuya_enqueue_global_scripts();
    
    // 투어플래너 페이지 전용
    sungsuya_enqueue_tour_planner_scripts();
    
    // PWA 바텀시트 투어플래너 전용
    sungsuya_enqueue_pwa_scripts();
}
add_action('wp_enqueue_scripts', 'sungsuya_enqueue_scripts');

/**
 * 페이지별 특화 CSS 로드
 */
function sungsuya_enqueue_page_specific_styles() {
    if (is_front_page() || is_home()) {
        wp_enqueue_style(
            'sungsuya-home',
            SUNGSUYA_THEME_URL . '/assets/css/home.css',
            array('sungsuya-main'),
            SUNGSUYA_VERSION
        );
    }
    
    if (is_post_type_archive('popup_store') || is_page_template('page-stores.php') || is_singular('popup_store')) {
        wp_enqueue_style(
            'sungsuya-stores',
            SUNGSUYA_THEME_URL . '/assets/css/stores.css',
            array('sungsuya-main'),
            SUNGSUYA_VERSION
        );
    }
}

/**
 * 상세페이지 전용 스크립트 로드
 */
function sungsuya_enqueue_single_scripts() {
    if (!is_singular('popup_store') && !is_singular('places')) {
        return;
    }
    
    // 상세페이지 전용 CSS
    wp_enqueue_style(
        'sungsuya-popup-store-detail',
        SUNGSUYA_THEME_URL . '/assets/css/popup-store-detail.css',
        array('sungsuya-main'),
        SUNGSUYA_VERSION
    );
    
    wp_enqueue_style(
        'sungsuya-popup-store-detail-enhanced',
        SUNGSUYA_THEME_URL . '/assets/css/popup-store-detail-enhanced.css',
        array('sungsuya-popup-store-detail'),
        SUNGSUYA_VERSION
    );
    
    // 네이버 지도 API 로드
    sungsuya_enqueue_naver_maps_api();
    
    // 팝업스토어 상세페이지 전용 JavaScript
    wp_enqueue_script(
        'sungsuya-popup-store-detail',
        SUNGSUYA_THEME_URL . '/assets/js/popup-store-detail.js',
        array('naver-maps-api'),
        SUNGSUYA_VERSION,
        true
    );
    
    // 상세페이지 전용 설정 지역화
    wp_localize_script('sungsuya-popup-store-detail', 'sungsuyaStoreDetail', array(
        'nonce' => wp_create_nonce('wp_rest'),
        'endpoint' => home_url('/wp-json/sungsuya/v2/'),
        'storeId' => get_the_ID(),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'theme_url' => SUNGSUYA_THEME_URL
    ));
}

/**
 * 전역 스크립트 로드
 */
function sungsuya_enqueue_global_scripts() {
    // 디버그 유틸리티 - 가장 먼저 로드
    wp_enqueue_script(
        'sungsuya-debug-utils',
        SUNGSUYA_THEME_URL . '/assets/js/debug-utils.js',
        array(),
        SUNGSUYA_VERSION,
        false // 헤더에 로드
    );
    
    // React 라이브러리 (ShareButtons 컴포넌트용) - 프로덕션 빌드
    wp_enqueue_script(
        'react',
        'https://unpkg.com/react@17/umd/react.production.min.js',
        array(),
        '17.0.2',
        true
    );
    
    wp_enqueue_script(
        'react-dom',
        'https://unpkg.com/react-dom@17/umd/react-dom.production.min.js',
        array('react'),
        '17.0.2',
        true
    );
    
    // 성능 최적화 스크립트 (Phase 3 Task 3.1) - 최우선 로드
    wp_enqueue_script(
        'sungsuya-performance',
        SUNGSUYA_THEME_URL . '/assets/js/performance.js',
        array(),
        SUNGSUYA_VERSION,
        false // 헤더에 로드하여 빠르게 실행
    );
    
    // 터치 제스처 최적화 (Phase 3 Task 3.2) - 모바일 전용
    if (wp_is_mobile()) {
        wp_enqueue_script(
            'sungsuya-touch-gestures',
            SUNGSUYA_THEME_URL . '/assets/js/touch-gestures.js',
            array(),
            SUNGSUYA_VERSION,
            true
        );
        
        // 모바일 최적화 스크립트 (2025-07-02)
        wp_enqueue_script(
            'sungsuya-mobile-optimize',
            SUNGSUYA_THEME_URL . '/assets/js/mobile-optimize.js',
            array('sungsuya-performance'),
            SUNGSUYA_VERSION,
            true
        );
        
        // 모바일 스크롤 버그 수정 스크립트 (2025-07-04)
        wp_enqueue_script(
            'sungsuya-mobile-scroll-fix',
            SUNGSUYA_THEME_URL . '/assets/js/mobile-scroll-fix.js',
            array(),
            SUNGSUYA_VERSION,
            true
        );
    }
    
    wp_enqueue_script(
        'sungsuya-utils',
        SUNGSUYA_THEME_URL . '/assets/js/utils.js',
        array(),
        SUNGSUYA_VERSION,
        true
    );
    
    wp_enqueue_script(
        'sungsuya-app',
        SUNGSUYA_THEME_URL . '/assets/js/app.js',
        array('sungsuya-utils'),
        SUNGSUYA_VERSION,
        true
    );
    
    // ShareButtons 컴포넌트 (React 기반)
    wp_enqueue_script(
        'sungsuya-share-buttons',
        SUNGSUYA_THEME_URL . '/assets/js/components/ShareButtons.js',
        array('react', 'react-dom'),
        SUNGSUYA_VERSION,
        true
    );
    
    // 카카오 SDK 로드
    $kakao_js_key = get_option('kakao_javascript_key', '');
    if ($kakao_js_key) {
        wp_enqueue_script('kakao-sdk', 'https://developers.kakao.com/sdk/js/kakao.min.js', array(), null, true);
        wp_add_inline_script('kakao-sdk', '
            document.addEventListener("DOMContentLoaded", function() {
                if (window.Kakao && !window.Kakao.isInitialized()) {
                    window.Kakao.init("' . esc_js($kakao_js_key) . '");
                    if (window.debugLog) {
                        window.debugLog("카카오 SDK 초기화 완료");
                    }
                }
            });
        ');
    }
    
    // 성능 스크립트 설정 지역화
    wp_localize_script('sungsuya-performance', 'SUNGSUYA_CONFIG', array(
        'themeUrl' => SUNGSUYA_THEME_URL,
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wp_rest')
    ));
    
    // 전역 API 설정 지역화
    wp_localize_script('sungsuya-app', 'sungsuyaAPI', array(
        'endpoint' => home_url('/wp-json/sungsuya/v2/'),
        'nonce' => wp_create_nonce('wp_rest'),
        'theme_url' => SUNGSUYA_THEME_URL,
        'home_url' => home_url(),
        'is_admin' => is_admin(),
        'current_user_id' => get_current_user_id()
    ));
    
    // 인증 시스템 스크립트 (전체 사이트) - 최적화 버전
    wp_enqueue_script(
        'sungsuya-auth-system',
        SUNGSUYA_THEME_URL . '/assets/js/auth-system-optimized.js',
        array(),
        SUNGSUYA_VERSION . '.1',
        true
    );
    
    // 인증 시스템 스타일 (전체 사이트) - 최적화 버전
    wp_enqueue_style(
        'sungsuya-auth-system',
        SUNGSUYA_THEME_URL . '/assets/css/auth-system-optimized.css',
        array('sungsuya-main'),
        SUNGSUYA_VERSION . '.1'
    );
    
    // 인증 시스템 설정
    wp_localize_script('sungsuya-auth-system', 'authConfig', array(
        'apiUrl' => home_url('/wp-json/sungsuya/v2/auth/'),
        'nonce' => wp_create_nonce('wp_rest'),
        'googleClientId' => get_option('google_client_id', ''),
        'kakaoJsKey' => get_option('kakao_javascript_key', '')
    ));
    
    // Google Sign-In (구글 로그인용)
    $google_client_id = get_option('google_client_id', '');
    if ($google_client_id) {
        wp_enqueue_script('google-signin', 'https://accounts.google.com/gsi/client', array(), null, true);
    }
    
    // 투어플래너용 설정 추가
    wp_localize_script('sungsuya-app', 'SUNGSUYA_CONFIG', array(
        'apiUrl' => home_url('/wp-json/sungsuya/v2/'),
        'nonce' => wp_create_nonce('wp_rest'),
        'naverClientId' => function_exists('sungsuya_get_naver_maps_client_id') ? sungsuya_get_naver_maps_client_id() : '',
        'maxStores' => 8,
        'mapCenter' => array(
            'lat' => 37.5444,
            'lng' => 127.0548
        )
    ));
}

/**
 * 네이버 지도 API 로드
 */
function sungsuya_enqueue_naver_maps_api() {
    global $pagenow, $post_type;
    $is_places_admin = (is_admin() && ($pagenow === 'post-new.php' || $pagenow === 'post.php') && $post_type === 'places');
    
    if ($is_places_admin) {
        return; // Places 관리자에서는 로드하지 않음
    }
    
    if (!function_exists('sungsuya_get_naver_maps_client_id') || !function_exists('sungsuya_is_naver_maps_configured')) {
        return;
    }
    
    // 옵션 이름 통일
    $naver_client_id = get_option('sungsuya_naver_client_id');
    if (!$naver_client_id) {
        $naver_client_id = get_option('naver_maps_client_id'); // 이전 옵션명 호환
    }
    
    $maps_configured = sungsuya_is_naver_maps_configured();
    
    if ($maps_configured && !empty($naver_client_id)) {
        wp_enqueue_script(
            'naver-maps-api-with-geocoder',
            "https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId={$naver_client_id}&submodules=geocoder&callback=naverMapReady",
            array(),
            null,
            false
        );
    } else {
        // 개발 환경 폴백
        wp_add_inline_script('jquery', '
            window.naverMapReady = function() { 
                console.log("Naver Maps API callback called"); 
            };
            console.log("Naver Maps API not configured - using fallback");
        ');
    }
}

/**
 * 투어플래너 스크립트 로드
 */
function sungsuya_enqueue_tour_planner_scripts() {
    // 기존 투어플래너 페이지
    if (is_page('planner') || strpos($_SERVER['REQUEST_URI'], 'planner') !== false) {
        wp_enqueue_script(
            'tour-planner-map',
            SUNGSUYA_THEME_URL . '/assets/js/tour-planner-map.js',
            array('sungsuya-app'),
            SUNGSUYA_VERSION,
            true
        );
    }
    
    // 투어플래너 V2 페이지
    if (is_page('투어플래너-v2') || is_page_template('page-tour-v2.php') || is_page('tour-v2')) {
        // 네이버 지도 API는 페이지 템플릿에서 직접 로드함
        // sungsuya_enqueue_naver_maps_api(); // 중복 로드 방지
        
        // 투어플래너 V2 전용 스크립트는 이미 페이지 템플릿에서 로드됨
    }
}

/**
 * PWA 스크립트 로드
 */
function sungsuya_enqueue_pwa_scripts() {
    if (!is_page_template('page-tour-planner-pwa.php')) {
        return;
    }
    
    // 네이버 지도 API (PWA용)
    if (function_exists('sungsuya_get_naver_maps_client_id') && function_exists('sungsuya_is_naver_maps_configured')) {
        $naver_client_id = sungsuya_get_naver_maps_client_id();
        $maps_configured = sungsuya_is_naver_maps_configured();
        
        if ($maps_configured && !empty($naver_client_id)) {
            wp_enqueue_script(
                'naver-maps-api-pwa',
                "https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId={$naver_client_id}&submodules=geocoder",
                array(),
                null,
                false
            );
        }
    }
    
    // PWA 투어플래너 전용 스타일
    wp_add_inline_style('sungsuya-main', '
        body.page-template-page-tour-planner-pwa {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        body.page-template-page-tour-planner-pwa .site-footer {
            display: none;
        }
    ');
}

/**
 * 스크립트 로드 최적화
 */
function sungsuya_script_loader_tag($tag, $handle, $src) {
    // 네이버 지도 API는 동기 로딩 필요
    if (in_array($handle, array('sungsuya-app', 'sungsuya-popup-store-detail'))) {
        return str_replace(' src', ' defer src', $tag);
    }
    
    return $tag;
}
add_filter('script_loader_tag', 'sungsuya_script_loader_tag', 10, 3);

/**
 * PWA 관련 헤더 메타 태그 추가
 */
function sungsuya_pwa_meta_tags() {
    ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="성수야!">
    
    <!-- PWA 매니페스트 -->
    <link rel="manifest" href="<?php echo esc_url(SUNGSUYA_THEME_URL . '/pwa/manifest.json'); ?>">
    
    <!-- 파비콘 -->
    <link rel="icon" type="image/svg+xml" href="<?php echo esc_url(SUNGSUYA_THEME_URL . '/assets/images/icon.svg'); ?>">
    <link rel="apple-touch-icon" href="<?php echo esc_url(SUNGSUYA_THEME_URL . '/assets/images/icon.svg'); ?>">
    
    <!-- 성능 최적화 -->
    <?php if (is_singular('popup_store')) : ?>
    <link rel="dns-prefetch" href="//oapi.map.naver.com">
    <link rel="preconnect" href="https://oapi.map.naver.com" crossorigin>
    <?php endif; ?>
    
    <!-- SEO 메타 태그 -->
    <?php sungsuya_seo_meta_tags(); ?>
    <?php
}
add_action('wp_head', 'sungsuya_pwa_meta_tags');

/**
 * SEO 메타 태그
 */
function sungsuya_seo_meta_tags() {
    if (!is_singular('popup_store')) {
        return;
    }
    ?>
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?php echo esc_attr(get_the_title()); ?> - 성수야!">
    <meta property="og:description" content="<?php echo esc_attr(wp_trim_words(get_the_excerpt(), 20)); ?>">
    <meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>">
    <meta property="og:site_name" content="성수야!">
    <?php if (has_post_thumbnail()) : ?>
    <meta property="og:image" content="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>">
    <?php endif; ?>
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr(get_the_title()); ?> - 성수야!">
    <meta name="twitter:description" content="<?php echo esc_attr(wp_trim_words(get_the_excerpt(), 20)); ?>">
    <?php if (has_post_thumbnail()) : ?>
    <meta name="twitter:image" content="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>">
    <?php endif; ?>
    <?php
}
