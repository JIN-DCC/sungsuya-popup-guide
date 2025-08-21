<?php
/**
 * Sitemap 생성기
 * 
 * @package SungsuyaV2
 * @since 1.0.0
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sitemap 생성 클래스
 */
class Sungsuya_Sitemap_Generator {
    
    /**
     * 생성자
     */
    public function __construct() {
        // sitemap 리라이트 규칙
        add_action('init', array($this, 'add_sitemap_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_sitemap_query_vars'));
        add_action('template_redirect', array($this, 'handle_sitemap_request'));
        
        // 자동 갱신
        add_action('save_post', array($this, 'schedule_sitemap_regeneration'));
        add_action('delete_post', array($this, 'schedule_sitemap_regeneration'));
        add_action('sungsuya_regenerate_sitemap', array($this, 'generate_sitemap'));
        
        // 관리자 메뉴
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * 리라이트 규칙 추가
     */
    public function add_sitemap_rewrite_rules() {
        add_rewrite_rule('^sitemap\.xml$', 'index.php?sungsuya_sitemap=index', 'top');
        add_rewrite_rule('^sitemap-([^/]+)\.xml$', 'index.php?sungsuya_sitemap=$matches[1]', 'top');
    }
    
    /**
     * 쿼리 변수 추가
     */
    public function add_sitemap_query_vars($vars) {
        $vars[] = 'sungsuya_sitemap';
        return $vars;
    }
    
    /**
     * Sitemap 요청 처리
     */
    public function handle_sitemap_request() {
        $sitemap_type = get_query_var('sungsuya_sitemap');
        
        if (!$sitemap_type) {
            return;
        }
        
        // 캐시 확인
        $cache_key = 'sungsuya_sitemap_' . $sitemap_type;
        $sitemap_content = get_transient($cache_key);
        
        if (!$sitemap_content) {
            // 새로 생성
            switch ($sitemap_type) {
                case 'index':
                    $sitemap_content = $this->generate_sitemap_index();
                    break;
                case 'pages':
                    $sitemap_content = $this->generate_pages_sitemap();
                    break;
                case 'posts':
                    $sitemap_content = $this->generate_posts_sitemap();
                    break;
                case 'places':
                    $sitemap_content = $this->generate_places_sitemap();
                    break;
                case 'categories':
                    $sitemap_content = $this->generate_categories_sitemap();
                    break;
                case 'tags':
                    $sitemap_content = $this->generate_tags_sitemap();
                    break;
                default:
                    wp_die('Invalid sitemap type');
            }
            
            // 캐시 저장 (1일)
            set_transient($cache_key, $sitemap_content, DAY_IN_SECONDS);
        }
        
        // XML 헤더 전송
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex, follow');
        
        echo $sitemap_content;
        exit;
    }
    
    /**
     * Sitemap 인덱스 생성
     */
    private function generate_sitemap_index() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<?xml-stylesheet type="text/xsl" href="' . SUNGSUYA_THEME_URL . '/assets/xsl/sitemap.xsl"?>';
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        // 각 sitemap 추가
        $sitemaps = array(
            'pages' => '페이지',
            'posts' => '포스트',
            'places' => '장소',
            'categories' => '카테고리',
            'tags' => '태그'
        );
        
        foreach ($sitemaps as $type => $name) {
            $count = $this->get_sitemap_count($type);
            if ($count > 0) {
                $xml .= '<sitemap>';
                $xml .= '<loc>' . home_url('/sitemap-' . $type . '.xml') . '</loc>';
                $xml .= '<lastmod>' . date('c') . '</lastmod>';
                $xml .= '</sitemap>';
            }
        }
        
        $xml .= '</sitemapindex>';
        
        return $xml;
    }
    
    /**
     * 페이지 Sitemap 생성
     */
    private function generate_pages_sitemap() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<?xml-stylesheet type="text/xsl" href="' . SUNGSUYA_THEME_URL . '/assets/xsl/sitemap.xsl"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $xml .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
        
        // 홈페이지
        $xml .= '<url>';
        $xml .= '<loc>' . home_url('/') . '</loc>';
        $xml .= '<lastmod>' . date('c') . '</lastmod>';
        $xml .= '<changefreq>daily</changefreq>';
        $xml .= '<priority>1.0</priority>';
        $xml .= '</url>';
        
        // 페이지들
        $pages = get_posts(array(
            'post_type' => 'page',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
        
        foreach ($pages as $page) {
            $xml .= '<url>';
            $xml .= '<loc>' . get_permalink($page) . '</loc>';
            $xml .= '<lastmod>' . get_the_modified_date('c', $page) . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.8</priority>';
            
            // 이미지 추가
            if (has_post_thumbnail($page)) {
                $xml .= $this->add_image_to_sitemap($page);
            }
            
            $xml .= '</url>';
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    /**
     * 포스트 Sitemap 생성
     */
    private function generate_posts_sitemap() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<?xml-stylesheet type="text/xsl" href="' . SUNGSUYA_THEME_URL . '/assets/xsl/sitemap.xsl"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $xml .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
        
        $posts = get_posts(array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        foreach ($posts as $post) {
            $xml .= '<url>';
            $xml .= '<loc>' . get_permalink($post) . '</loc>';
            $xml .= '<lastmod>' . get_the_modified_date('c', $post) . '</lastmod>';
            $xml .= '<changefreq>monthly</changefreq>';
            $xml .= '<priority>0.7</priority>';
            
            // 이미지 추가
            if (has_post_thumbnail($post)) {
                $xml .= $this->add_image_to_sitemap($post);
            }
            
            $xml .= '</url>';
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    /**
     * Places Sitemap 생성
     */
    private function generate_places_sitemap() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<?xml-stylesheet type="text/xsl" href="' . SUNGSUYA_THEME_URL . '/assets/xsl/sitemap.xsl"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $xml .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
        $xml .= ' xmlns:geo="http://www.google.com/geo/schemas/sitemap/1.0">';
        
        $places = get_posts(array(
            'post_type' => 'places',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC'
        ));
        
        foreach ($places as $place) {
            $xml .= '<url>';
            $xml .= '<loc>' . get_permalink($place) . '</loc>';
            $xml .= '<lastmod>' . get_the_modified_date('c', $place) . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.9</priority>';
            
            // 이미지 추가
            if (has_post_thumbnail($place)) {
                $xml .= $this->add_image_to_sitemap($place);
            }
            
            // 갤러리 이미지
            $gallery = get_post_meta($place->ID, 'place_gallery', true);
            if ($gallery) {
                $gallery_ids = explode(',', $gallery);
                foreach ($gallery_ids as $image_id) {
                    $xml .= $this->add_image_to_sitemap($place, $image_id);
                }
            }
            
            // 지역 정보
            $lat = get_post_meta($place->ID, 'place_lat', true);
            $lng = get_post_meta($place->ID, 'place_lng', true);
            if ($lat && $lng) {
                $xml .= '<geo:geo>';
                $xml .= '<geo:format>kml</geo:format>';
                $xml .= '</geo:geo>';
            }
            
            $xml .= '</url>';
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    /**
     * 카테고리 Sitemap 생성
     */
    private function generate_categories_sitemap() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<?xml-stylesheet type="text/xsl" href="' . SUNGSUYA_THEME_URL . '/assets/xsl/sitemap.xsl"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        $categories = get_terms(array(
            'taxonomy' => array('category', 'place_type'),
            'hide_empty' => true
        ));
        
        foreach ($categories as $category) {
            $xml .= '<url>';
            $xml .= '<loc>' . get_term_link($category) . '</loc>';
            $xml .= '<lastmod>' . date('c') . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.6</priority>';
            $xml .= '</url>';
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    /**
     * 태그 Sitemap 생성
     */
    private function generate_tags_sitemap() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<?xml-stylesheet type="text/xsl" href="' . SUNGSUYA_THEME_URL . '/assets/xsl/sitemap.xsl"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        $tags = get_terms(array(
            'taxonomy' => 'post_tag',
            'hide_empty' => true
        ));
        
        foreach ($tags as $tag) {
            $xml .= '<url>';
            $xml .= '<loc>' . get_term_link($tag) . '</loc>';
            $xml .= '<lastmod>' . date('c') . '</lastmod>';
            $xml .= '<changefreq>monthly</changefreq>';
            $xml .= '<priority>0.5</priority>';
            $xml .= '</url>';
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    /**
     * 이미지를 sitemap에 추가
     */
    private function add_image_to_sitemap($post, $image_id = null) {
        if (!$image_id) {
            $image_id = get_post_thumbnail_id($post->ID);
        }
        
        if (!$image_id) {
            return '';
        }
        
        $image_url = wp_get_attachment_url($image_id);
        $image_title = get_the_title($image_id);
        $image_caption = wp_get_attachment_caption($image_id);
        $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        
        $xml = '<image:image>';
        $xml .= '<image:loc>' . esc_url($image_url) . '</image:loc>';
        
        if ($image_title) {
            $xml .= '<image:title>' . esc_xml($image_title) . '</image:title>';
        }
        
        if ($image_caption) {
            $xml .= '<image:caption>' . esc_xml($image_caption) . '</image:caption>';
        }
        
        $xml .= '</image:image>';
        
        return $xml;
    }
    
    /**
     * Sitemap 항목 수 가져오기
     */
    private function get_sitemap_count($type) {
        switch ($type) {
            case 'pages':
                return wp_count_posts('page')->publish;
            case 'posts':
                return wp_count_posts('post')->publish;
            case 'places':
                return wp_count_posts('places')->publish;
            case 'categories':
                return wp_count_terms(array('category', 'place_type'));
            case 'tags':
                return wp_count_terms('post_tag');
            default:
                return 0;
        }
    }
    
    /**
     * Sitemap 재생성 예약
     */
    public function schedule_sitemap_regeneration() {
        if (!wp_next_scheduled('sungsuya_regenerate_sitemap')) {
            wp_schedule_single_event(time() + 300, 'sungsuya_regenerate_sitemap');
        }
    }
    
    /**
     * Sitemap 생성
     */
    public function generate_sitemap() {
        // 모든 캐시 삭제
        $types = array('index', 'pages', 'posts', 'places', 'categories', 'tags');
        foreach ($types as $type) {
            delete_transient('sungsuya_sitemap_' . $type);
        }
        
        // 검색엔진에 핑
        $this->ping_search_engines();
    }
    
    /**
     * 검색엔진에 핑
     */
    private function ping_search_engines() {
        $sitemap_url = home_url('/sitemap.xml');
        
        // Google
        wp_remote_get('https://www.google.com/ping?sitemap=' . urlencode($sitemap_url));
        
        // Bing
        wp_remote_get('https://www.bing.com/ping?sitemap=' . urlencode($sitemap_url));
    }
    
    /**
     * 관리자 메뉴 추가
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'Sitemap 관리',
            'Sitemap 관리',
            'manage_options',
            'sitemap-manager',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * 관리자 페이지 렌더링
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>Sitemap 관리</h1>
            
            <div class="card">
                <h2>Sitemap 상태</h2>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th>Sitemap 유형</th>
                            <th>항목 수</th>
                            <th>URL</th>
                            <th>캐시 상태</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $types = array(
                            'index' => '인덱스',
                            'pages' => '페이지',
                            'posts' => '포스트',
                            'places' => '장소',
                            'categories' => '카테고리',
                            'tags' => '태그'
                        );
                        
                        foreach ($types as $type => $name) :
                            $count = $this->get_sitemap_count($type);
                            $cache_exists = get_transient('sungsuya_sitemap_' . $type) !== false;
                            $url = $type === 'index' ? home_url('/sitemap.xml') : home_url('/sitemap-' . $type . '.xml');
                        ?>
                        <tr>
                            <td><?php echo esc_html($name); ?></td>
                            <td><?php echo number_format($count); ?></td>
                            <td><a href="<?php echo esc_url($url); ?>" target="_blank"><?php echo esc_html($url); ?></a></td>
                            <td>
                                <?php if ($cache_exists) : ?>
                                    <span style="color: green;">✓ 캐시됨</span>
                                <?php else : ?>
                                    <span style="color: red;">✗ 캐시 없음</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>작업</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('regenerate_sitemap'); ?>
                    <p>
                        <input type="submit" name="regenerate_sitemap" class="button button-primary" value="Sitemap 재생성">
                        <span class="description">모든 sitemap을 재생성하고 검색엔진에 핑을 보냅니다.</span>
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2>검색엔진 제출</h2>
                <p>아래 URL을 검색엔진에 제출하세요:</p>
                <ul>
                    <li><strong>Google Search Console:</strong> <code><?php echo home_url('/sitemap.xml'); ?></code></li>
                    <li><strong>Bing Webmaster Tools:</strong> <code><?php echo home_url('/sitemap.xml'); ?></code></li>
                    <li><strong>Naver Webmaster Tools:</strong> <code><?php echo home_url('/sitemap.xml'); ?></code></li>
                </ul>
            </div>
        </div>
        <?php
        
        // 재생성 요청 처리
        if (isset($_POST['regenerate_sitemap']) && wp_verify_nonce($_POST['_wpnonce'], 'regenerate_sitemap')) {
            $this->generate_sitemap();
            echo '<div class="notice notice-success"><p>Sitemap이 재생성되었습니다.</p></div>';
        }
    }
}

// 클래스 초기화
new Sungsuya_Sitemap_Generator();
