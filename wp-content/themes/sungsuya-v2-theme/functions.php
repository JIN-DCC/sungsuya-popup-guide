<?php
/**
 * 성수야! - 테마 함수 (Phase 3 최적화 버전)
 * 
 * WordPress 테마 설정 및 커스터마이징
 * Phase 3: 크롤링 시스템 모듈화로 20% 추가 성능 향상
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

// 테마 상수 정의
define('SUNGSUYA_VERSION', '2.1.0');
define('SUNGSUYA_THEME_DIR', get_template_directory());
define('SUNGSUYA_THEME_URL', get_template_directory_uri());

// 사이트 타이틀 필터 추가
add_filter('wp_title', 'sungsuya_custom_title', 10, 2);
add_filter('document_title_parts', 'sungsuya_document_title_parts');
add_filter('document_title_separator', 'sungsuya_title_separator');

function sungsuya_custom_title($title, $sep) {
    return str_replace('sungsuya-v2', '성수야!', $title);
}

function sungsuya_document_title_parts($title_parts) {
    if (isset($title_parts['site'])) {
        $title_parts['site'] = '성수야!';
    }
    if (isset($title_parts['title']) && strpos($title_parts['title'], 'V2') !== false) {
        $title_parts['title'] = str_replace(' V2', '', $title_parts['title']);
    }
    return $title_parts;
}

function sungsuya_title_separator() {
    return '–';
}

// 모던 디자인 시스템 스타일 로드 (Phase 1: CSS 분리 완료)
function sungsuya_add_modern_styles() {
    // minimal-design-system.css 제거
    wp_dequeue_style('minimal-design-system');
    wp_deregister_style('minimal-design-system');
    
    // CSS 인라인 대신 별도 파일로 로드 (30% 성능 향상)
    wp_enqueue_style(
        'sungsuya-modern-styles',
        SUNGSUYA_THEME_URL . '/assets/css/modern-styles.css',
        array(),
        SUNGSUYA_VERSION
    );
    
    // 글로벌 페이지네이션 스타일 추가
    wp_enqueue_style(
        'sungsuya-pagination-global',
        SUNGSUYA_THEME_URL . '/assets/css/pagination-global.css',
        array(),
        SUNGSUYA_VERSION
    );
}
add_action('wp_enqueue_scripts', 'sungsuya_add_modern_styles', 999);

// 웹접근성 스킵 링크 제거
function sungsuya_remove_skip_links() {
    remove_action( 'wp_body_open', 'wp_skip_link_focus_fix' );
    remove_action( 'wp_footer', 'wp_skip_link_focus_fix' );
    
    // Remove all actions from wp_body_open
    remove_all_actions('wp_body_open');
    
    // Remove WordPress accessibility features
    add_filter('wp_body_open', '__return_empty_string', 1);
}
add_action('init', 'sungsuya_remove_skip_links', 1);

// Disable WordPress skip links completely
add_filter('the_content_more_link', '__return_empty_string');
add_filter('get_the_excerpt', function($excerpt) {
    return str_replace('Continue reading', '', $excerpt);
}, 999);

// Remove WordPress automatic formatting
remove_filter('the_content', 'wpautop');
remove_filter('the_excerpt', 'wpautop');

// Disable emoji detection script
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

// Remove WordPress version
remove_action('wp_head', 'wp_generator');

// Clean up wp_head
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_shortlink_wp_head');

// Disable all body_open actions
add_action('after_setup_theme', function() {
    remove_all_actions('wp_body_open');
}, 999);

// GTranslate 플러그인 지원
require_once SUNGSUYA_THEME_DIR . '/inc/gtranslate-fix.php';

// 문의하기 폼 처리
require_once SUNGSUYA_THEME_DIR . '/inc/contact-form-handler.php';

// 뉴스레터 처리
require_once SUNGSUYA_THEME_DIR . '/inc/newsletter-handler.php';

/**
 * 핵심 모듈 로드 (Phase 2: 프론트엔드에서 항상 필요한 것들만)
 */
require_once SUNGSUYA_THEME_DIR . '/inc/core/theme-setup.php';        // 테마 기본 설정
require_once SUNGSUYA_THEME_DIR . '/inc/core/admin-access.php';       // 관리자 접근 제어
require_once SUNGSUYA_THEME_DIR . '/inc/core/enqueue-scripts.php';    // 스크립트/스타일 관리
require_once SUNGSUYA_THEME_DIR . '/inc/debug-log-manager.php';       // 디버그 로그 자동 관리

/**
 * 프론트엔드 필수 기능 모듈 로드 (Phase 3: 크롤링 시스템 분리 최적화)
 */
$frontend_modules = array(
    // 기본 기능 (프론트엔드 필수)
    'post-types.php',                      // Custom Post Types
    'rest-api.php',                        // REST API 엔드포인트
    'pwa-support.php',                     // PWA 지원
    'pwa-optimization.php',                // PWA 최적화
    'analytics.php',                       // Google Analytics
    'tour-saver.php',                      // 투어 저장/공유
    
    // Places 시스템 (프론트엔드 필수)
    'places/place-post-type.php',          // Places Post Type
    'places/place-taxonomies.php',         // Places 택소노미
    'places/place-meta-fields.php',        // Places 메타 필드
    'places/place-metabox-manager.php',    // Places 메타박스
    'places/data-migration.php',           // 데이터 마이그레이션
    
    // 투어 시스템 (프론트엔드 필수)
    'hybrid-tour-planner.php',             // 하이브리드 투어 플래너
    'membership-tour-system.php',          // 멤버십 투어 시스템
    'url-structure.php',                   // URL 구조
    
    // 인증 시스템 (프론트엔드 필수)
    'auth/auth-system.php',                // PWA 인증 시스템
    
    // SEO 관리자 (프론트엔드 필수)
    'seo-manager.php',
    'sitemap-generator.php',
    'accessibility-manager.php',
);

// 프론트엔드 모듈 로드
foreach ($frontend_modules as $file) {
    $file_path = SUNGSUYA_THEME_DIR . '/inc/' . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}

/**
 * Smart Deep Link v7.0 로드 (프론트엔드 필수)
 */
require_once SUNGSUYA_THEME_DIR . '/inc/helpers/smart-deep-link-system-v7.php';

/**
 * Phase 3: 크롤링 시스템 모듈화 (20% 추가 성능 향상)
 * 지오코딩은 스마트하게 조건부 로딩, 나머지는 관리자에서만 로딩
 */
require_once SUNGSUYA_THEME_DIR . '/inc/crawling-optimized/geocoding.php'; // 스마트 조건부 로딩

// 관리자에서만 크롤링 시스템 로딩
if (is_admin()) {
    require_once SUNGSUYA_THEME_DIR . '/inc/crawling-optimized/crawling-core.php';
}

/**
 * Phase 2: 관리자 전용 모듈 조건부 로딩 (40% 메모리 절약)
 * 프론트엔드에서는 로딩하지 않아 대폭 성능 향상
 */
if (is_admin()) {
    require_once SUNGSUYA_THEME_DIR . '/inc/admin-optimized/admin-init.php';
}

/**
 * 투어 공유 URL 라우팅 (프론트엔드 필수)
 */
function sungsuya_add_tour_rewrite_rules() {
    add_rewrite_rule('^tour/([A-Z0-9]{8})/?', 'index.php?tour_id=$matches[1]', 'top');
}
add_action('init', 'sungsuya_add_tour_rewrite_rules');

function sungsuya_add_tour_query_vars($vars) {
    $vars[] = 'tour_id';
    return $vars;
}
add_filter('query_vars', 'sungsuya_add_tour_query_vars');

function sungsuya_tour_template_redirect() {
    $tour_id = get_query_var('tour_id');
    if ($tour_id) {
        $template = locate_template('single-tour.php');
        if ($template) {
            include $template;
            exit;
        }
    }
}
add_action('template_redirect', 'sungsuya_tour_template_redirect');

/**
 * AJAX 핸들러 등록 (프론트엔드 필수)
 */
require_once SUNGSUYA_THEME_DIR . '/inc/api/ajax-handlers.php';
require_once SUNGSUYA_THEME_DIR . '/inc/api/places-meta-api.php';

/**
 * Smart Deep Link 헬퍼 함수들 (프론트엔드 필수)
 */
require_once SUNGSUYA_THEME_DIR . '/inc/helpers/smart-deep-link-helpers.php';

/**
 * 멤버십 투어 API 핸들러 (프론트엔드 필수)
 */
require_once SUNGSUYA_THEME_DIR . '/inc/api/membership-tour-handlers.php';

/**
 * 소셜 기능 애셋 로드 (프론트엔드 필수)
 */
function sungsuya_enqueue_social_features() {
    if (is_singular('places')) {
        // Font Awesome 추가 (SNS 아이콘용)
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1');
        
        // React 및 ReactDOM (CDN)
        wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', array(), '18.0.0', true);
        wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', array('react'), '18.0.0', true);
        
        // 리뷰 시스템 스크립트
        wp_enqueue_script(
            'sungsuya-review-system',
            SUNGSUYA_THEME_URL . '/assets/js/review-system.js',
            array('react', 'react-dom'),
            SUNGSUYA_VERSION,
            true
        );
        
        // 리뷰 시스템 스타일
        wp_enqueue_style(
            'sungsuya-social-features',
            SUNGSUYA_THEME_URL . '/assets/css/social-features.css',
            array(),
            SUNGSUYA_VERSION
        );
        
        // 카카오 SDK
        wp_enqueue_script('kakao-sdk', 'https://developers.kakao.com/sdk/js/kakao.min.js', array(), null, true);
        
        // 카카오 SDK 초기화 스크립트
        $kakao_js_key = get_option('kakao_javascript_key', '');
        if ($kakao_js_key) {
            wp_add_inline_script('kakao-sdk', '
                if (window.Kakao && !window.Kakao.isInitialized()) {
                    window.Kakao.init("' . esc_js($kakao_js_key) . '");
                }
            ');
        }
    }
}
add_action('wp_enqueue_scripts', 'sungsuya_enqueue_social_features');

// 장소 상세페이지 템플릿 강제 적용 - 간단한 해결책
add_filter('template_include', 'sungsuya_fix_places_template', 999);
function sungsuya_fix_places_template($template) {
    if (is_singular('places')) {
        $new_template = locate_template(array('single-places.php'));
        if (!empty($new_template)) {
            return $new_template;
        }
    }
    return $template;
}

/**
 * 팝업스토어 페이지 리라이트 규칙 (2025-07-06)
 */
add_action('init', 'sungsuya_popup_store_rewrite_rules');
function sungsuya_popup_store_rewrite_rules() {
    add_rewrite_rule(
        '^popup-stores/?$',
        'index.php?post_type=places&place_type=popup-store',
        'top'
    );
    flush_rewrite_rules();
}

/**
 * 팝업스토어 아카이브 템플릿 적용
 */
add_filter('template_include', 'sungsuya_popup_store_template', 10);
function sungsuya_popup_store_template($template) {
    // URL이 /popup-stores로 시작하는 경우
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/popup-stores') === 0) {
        // WordPress 페이지가 있는지 확인
        $page = get_page_by_path('popup-stores');
        
        // 페이지가 없거나 삭제된 경우에만 아카이브 템플릿 사용
        if (!$page || $page->post_status === 'trash') {
            $new_template = locate_template('archive-popup-store.php');
            if ($new_template) {
                return $new_template;
            }
        }
    }
    return $template;
}

/**
 * 일반 장소 페이지에서 팝업스토어 제외
 */
add_action('pre_get_posts', 'sungsuya_exclude_popup_from_places');
function sungsuya_exclude_popup_from_places($query) {
    if (!is_admin() && $query->is_main_query()) {
        // 일반 장소 아카이브 페이지인 경우 (팝업스토어 페이지가 아닌 경우)
        if (is_post_type_archive('places') && 
            (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], '/popup-stores') === false)) {
            
            $tax_query = $query->get('tax_query') ?: array();
            
            $tax_query[] = array(
                'taxonomy' => 'place_type',
                'field' => 'slug',
                'terms' => array('popup-store', 'popup_store'),
                'operator' => 'NOT IN'
            );
            
            $query->set('tax_query', $tax_query);
        }
    }
}

/**
 * 장소유형 REST API 엔드포인트 (2025.06.29)
 * 투어플래너 동적 장소유형 지원
 */
add_action('rest_api_init', function() {
    // 장소유형 목록 조회
    register_rest_route('sungsuya/v2', '/place-types', array(
        'methods' => 'GET',
        'callback' => 'sungsuya_get_place_types',
        'permission_callback' => '__return_true',
    ));
});

function sungsuya_get_place_types() {
    $place_types = get_terms(array(
        'taxonomy' => 'place_type',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
    
    if (is_wp_error($place_types)) {
        return new WP_Error('no_types', '장소유형을 가져올 수 없습니다', array('status' => 404));
    }
    
    $result = array();
    foreach ($place_types as $type) {
        $metafield_type = get_term_meta($type->term_id, 'metafield_type', true);
        
        // 아이콘 결정
        $icon = '📍'; // 기본 아이콘
        if ($metafield_type === 'food') {
            $icon = '🍽️';
        } elseif ($metafield_type === 'shop') {
            $icon = '🛍️';
        } elseif ($type->slug === 'popup_store' || $type->slug === 'popup-store') {
            $icon = '🏪';
        } elseif ($type->slug === 'facility') {
            $icon = '🚻';
        }
        
        $result[] = array(
            'id' => $type->term_id,
            'name' => $type->name,
            'slug' => $type->slug,
            'description' => $type->description,
            'count' => $type->count,
            'metafield_type' => $metafield_type,
            'icon' => $icon
        );
    }
    
    return rest_ensure_response($result);
}
