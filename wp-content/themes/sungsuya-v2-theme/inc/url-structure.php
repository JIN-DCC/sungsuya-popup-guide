<?php
/**
 * 성수야! V2 - URL 구조 최적화
 * 
 * SEO 친화적이고 사용자 친화적인 URL 구조 구현
 * 
 * @package SungsuyaV2
 * @version 2.2.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SEO 친화적 URL 구조를 위한 추가 rewrite rules
 */
function sungsuya_add_seo_rewrite_rules() {
    // 1. 카테고리별 URL 구조: /places/restaurant/, /places/retail-store/ 등
    add_rewrite_rule(
        '^places/(restaurant|retail-store|facility)/?$',
        'index.php?post_type=places&place_type=$matches[1]',
        'top'
    );
    
    // 2. 팝업스토어 통합 URL: /places/popup-store/
    add_rewrite_rule(
        '^places/popup-store/?$',
        'index.php?post_type=popup_store',
        'top'
    );
    
    // 3. 검색 URL 구조: /places/search/{query}/
    add_rewrite_rule(
        '^places/search/([^/]+)/?$',
        'index.php?post_type=places&search_query=$matches[1]',
        'top'
    );
    
    // 4. 지역별 URL 구조: /places/area/sungsu-dong/
    add_rewrite_rule(
        '^places/area/([^/]+)/?$',
        'index.php?post_type=places&area=$matches[1]',
        'top'
    );
    
    // 5. 통합 검색 URL: /search/{query}/
    add_rewrite_rule(
        '^search/([^/]+)/?$',
        'index.php?search_query=$matches[1]&search_type=all',
        'top'
    );
    
    // 6. 투어플래너 공유 URL: /tour/{id}/
    add_rewrite_rule(
        '^tour/([^/]+)/?$',
        'index.php?pagename=planner&tour_id=$matches[1]',
        'top'
    );
}
add_action('init', 'sungsuya_add_seo_rewrite_rules');

/**
 * 커스텀 쿼리 변수 등록
 */
function sungsuya_add_custom_query_vars($vars) {
    $vars[] = 'place_type';
    $vars[] = 'search_query';
    $vars[] = 'search_type';
    $vars[] = 'area';
    $vars[] = 'tour_id';
    return $vars;
}
add_filter('query_vars', 'sungsuya_add_custom_query_vars');

/**
 * 카테고리별 페이지 제목 개선
 */
function sungsuya_custom_archive_title($title) {
    if (is_post_type_archive('places')) {
        $place_type = get_query_var('place_type');
        
        $type_titles = array(
            'restaurant' => '성수동 맛집',
            'retail-store' => '성수동 상설매장',
            'facility' => '성수동 편의시설'
        );
        
        if (!empty($place_type) && isset($type_titles[$place_type])) {
            return $type_titles[$place_type];
        }
        
        return '성수동 장소 가이드';
    }
    
    if (is_post_type_archive('popup_store')) {
        return '성수동 팝업스토어';
    }
    
    return $title;
}
add_filter('get_the_archive_title', 'sungsuya_custom_archive_title');

/**
 * 카테고리별 페이지 설명 추가
 */
function sungsuya_custom_archive_description($description) {
    if (is_post_type_archive('places')) {
        $place_type = get_query_var('place_type');
        
        $type_descriptions = array(
            'restaurant' => '성수동의 숨은 맛집들을 발견해보세요. 현지인이 추천하는 진짜 맛집 정보를 확인하세요.',
            'retail-store' => '성수동의 개성 있는 상설매장들을 둘러보세요. 독특한 쇼핑 경험을 만나보세요.',
            'facility' => '성수동 방문 시 알아두면 유용한 편의시설들을 확인하세요.'
        );
        
        if (!empty($place_type) && isset($type_descriptions[$place_type])) {
            return $type_descriptions[$place_type];
        }
        
        return '성수동의 모든 장소 정보를 한눈에 확인하세요. 맛집, 상설매장, 편의시설까지 다양한 장소를 둘러보세요.';
    }
    
    if (is_post_type_archive('popup_store')) {
        return '성수동에서 진행되는 모든 팝업스토어 정보를 확인하세요. 기간 한정 특별한 쇼핑과 체험을 만나보세요.';
    }
    
    return $description;
}
add_filter('get_the_archive_description', 'sungsuya_custom_archive_description');

/**
 * 페이지별 메타 태그 최적화
 */
function sungsuya_add_custom_meta_tags() {
    if (is_post_type_archive('places')) {
        $place_type = get_query_var('place_type');
        
        $meta_keywords = array(
            'restaurant' => '성수동 맛집, 성수 음식점, 성수동 카페, 성수 맛집 추천',
            'retail-store' => '성수동 쇼핑, 성수 편집샵, 성수동 상점, 성수 브랜드',
            'facility' => '성수동 편의시설, 성수 화장실, 성수동 주차장, 성수 시설'
        );
        
        $keywords = isset($meta_keywords[$place_type]) ? 
            $meta_keywords[$place_type] : 
            '성수동, 성수 여행, 성수동 관광, 성수 핫플레이스';
        
        echo '<meta name="keywords" content="' . esc_attr($keywords) . '">' . "\n";
        
        // Open Graph 태그
        $og_title = sungsuya_custom_archive_title('');
        $og_description = sungsuya_custom_archive_description('');
        
        echo '<meta property="og:title" content="' . esc_attr($og_title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($og_description) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_pagenum_link()) . '">' . "\n";
    }
}
add_action('wp_head', 'sungsuya_add_custom_meta_tags');

/**
 * 사이트맵 최적화를 위한 URL 구조 등록
 */
function sungsuya_add_sitemap_urls($urlset) {
    // 카테고리별 페이지를 사이트맵에 추가
    $category_urls = array(
        home_url('/places/restaurant/'),
        home_url('/places/retail-store/'),
        home_url('/places/facility/'),
        home_url('/places/popup-store/')
    );
    
    foreach ($category_urls as $url) {
        $urlset .= '<url>';
        $urlset .= '<loc>' . esc_url($url) . '</loc>';
        $urlset .= '<changefreq>weekly</changefreq>';
        $urlset .= '<priority>0.8</priority>';
        $urlset .= '</url>';
    }
    
    return $urlset;
}
add_filter('wp_sitemaps_posts_query_args', 'sungsuya_add_sitemap_urls');

/**
 * 브레드크럼 네비게이션 지원
 */
function sungsuya_get_breadcrumb() {
    if (is_front_page()) {
        return array(
            array('title' => '홈', 'url' => home_url('/'))
        );
    }
    
    $breadcrumb = array(
        array('title' => '홈', 'url' => home_url('/'))
    );
    
    if (is_post_type_archive('places')) {
        $breadcrumb[] = array('title' => '장소', 'url' => get_post_type_archive_link('places'));
        
        $place_type = get_query_var('place_type');
        if (!empty($place_type)) {
            $type_titles = array(
                'restaurant' => '맛집',
                'retail-store' => '상설매장',
                'facility' => '편의시설'
            );
            
            if (isset($type_titles[$place_type])) {
                $breadcrumb[] = array(
                    'title' => $type_titles[$place_type], 
                    'url' => home_url('/places/' . $place_type . '/')
                );
            }
        }
    }
    
    if (is_post_type_archive('popup_store')) {
        $breadcrumb[] = array('title' => '팝업스토어', 'url' => home_url('/stores/'));
    }
    
    if (is_singular('places')) {
        $breadcrumb[] = array('title' => '장소', 'url' => get_post_type_archive_link('places'));
        $breadcrumb[] = array('title' => get_the_title(), 'url' => '');
    }
    
    if (is_singular('popup_store')) {
        $breadcrumb[] = array('title' => '팝업스토어', 'url' => home_url('/stores/'));
        $breadcrumb[] = array('title' => get_the_title(), 'url' => '');
    }
    
    return $breadcrumb;
}

/**
 * JSON-LD 구조화된 데이터 추가
 */
function sungsuya_add_structured_data() {
    if (is_singular('places') || is_singular('popup_store')) {
        $post_id = get_the_ID();
        $post_type = get_post_type();
        
        // 위치 정보 가져오기
        if ($post_type === 'places') {
            $latitude = get_post_meta($post_id, 'latitude', true);
            $longitude = get_post_meta($post_id, 'longitude', true);
            $address = get_post_meta($post_id, 'address', true);
            $phone = get_post_meta($post_id, 'phone', true);
            $website = get_post_meta($post_id, 'website', true);
        } else {
            $latitude = get_post_meta($post_id, '_store_latitude', true);
            $longitude = get_post_meta($post_id, '_store_longitude', true);
            $address = get_post_meta($post_id, '_store_address', true);
            $phone = get_post_meta($post_id, '_contact_info', true);
            $website = get_post_meta($post_id, '_website', true);
        }
        
        $structured_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => get_the_title(),
            'description' => wp_trim_words(get_the_excerpt(), 30),
            'url' => get_permalink(),
            'image' => get_the_post_thumbnail_url($post_id, 'large')
        );
        
        // 주소 정보 추가
        if (!empty($address)) {
            $structured_data['address'] = array(
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressLocality' => '성수동',
                'addressRegion' => '서울특별시',
                'addressCountry' => 'KR'
            );
        }
        
        // 좌표 정보 추가
        if (!empty($latitude) && !empty($longitude)) {
            $structured_data['geo'] = array(
                '@type' => 'GeoCoordinates',
                'latitude' => (float)$latitude,
                'longitude' => (float)$longitude
            );
        }
        
        // 연락처 정보 추가
        if (!empty($phone)) {
            $structured_data['telephone'] = $phone;
        }
        
        if (!empty($website)) {
            $structured_data['url'] = $website;
        }
        
        echo '<script type="application/ld+json">' . json_encode($structured_data, JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
}
add_action('wp_head', 'sungsuya_add_structured_data');

/**
 * 정규 URL 설정 (중복 콘텐츠 방지)
 */
function sungsuya_add_canonical_urls() {
    if (is_post_type_archive('places')) {
        $place_type = get_query_var('place_type');
        
        if (!empty($place_type)) {
            $canonical_url = home_url('/places/' . $place_type . '/');
        } else {
            $canonical_url = get_post_type_archive_link('places');
        }
        
        echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
    }
}
add_action('wp_head', 'sungsuya_add_canonical_urls');

/**
 * Permalink 플러시 (URL 구조 변경 시)
 */
function sungsuya_flush_rewrite_rules_on_activation() {
    sungsuya_add_seo_rewrite_rules();
    flush_rewrite_rules();
}

// 테마 활성화 시 실행
add_action('after_switch_theme', 'sungsuya_flush_rewrite_rules_on_activation');

/**
 * URL 구조 디버깅 (개발 시에만 사용)
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    function sungsuya_debug_rewrite_rules() {
        if (isset($_GET['debug_rewrites']) && current_user_can('manage_options')) {
            global $wp_rewrite;
            echo '<pre>';
            print_r($wp_rewrite->rules);
            echo '</pre>';
            exit;
        }
    }
    add_action('init', 'sungsuya_debug_rewrite_rules');
}
