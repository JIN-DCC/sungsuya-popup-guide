<?php
/**
 * SEO 메타 태그 관리 시스템
 * 
 * @package SungsuyaV2
 * @since 1.0.0
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SEO 메타 태그 관리 클래스
 */
class Sungsuya_SEO_Manager {
    
    /**
     * 생성자
     */
    public function __construct() {
        // 메타 태그 출력
        add_action('wp_head', array($this, 'output_meta_tags'), 1);
        
        // 관리자 메타박스
        add_action('add_meta_boxes', array($this, 'add_seo_metabox'));
        add_action('save_post', array($this, 'save_seo_meta'));
        
        // JSON-LD 구조화된 데이터
        add_action('wp_head', array($this, 'output_structured_data'), 5);
        
        // Open Graph 태그
        add_action('wp_head', array($this, 'output_open_graph_tags'), 2);
        
        // Twitter Card 태그
        add_action('wp_head', array($this, 'output_twitter_card_tags'), 3);
        
        // Canonical URL
        remove_action('wp_head', 'rel_canonical');
        add_action('wp_head', array($this, 'output_canonical_url'), 4);
    }
    
    /**
     * 기본 메타 태그 출력
     */
    public function output_meta_tags() {
        // 기본 메타 태그
        echo '<meta charset="' . get_bloginfo('charset') . '">' . "\n";
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        
        // 설명 메타 태그
        $description = $this->get_meta_description();
        if ($description) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
        
        // 키워드 메타 태그
        $keywords = $this->get_meta_keywords();
        if ($keywords) {
            echo '<meta name="keywords" content="' . esc_attr($keywords) . '">' . "\n";
        }
        
        // 로봇 메타 태그
        $robots = $this->get_robots_meta();
        echo '<meta name="robots" content="' . esc_attr($robots) . '">' . "\n";
        
        // 작성자
        if (is_singular()) {
            $author = get_the_author_meta('display_name');
            echo '<meta name="author" content="' . esc_attr($author) . '">' . "\n";
        }
        
        // 언어
        echo '<meta name="language" content="' . esc_attr(get_locale()) . '">' . "\n";
        
        // 발행자
        echo '<meta name="publisher" content="성수야!">' . "\n";
        
        // 지역 정보
        echo '<meta name="geo.region" content="KR-11">' . "\n";
        echo '<meta name="geo.placename" content="Seoul">' . "\n";
        echo '<meta name="geo.position" content="37.5444;127.0548">' . "\n";
    }
    
    /**
     * Open Graph 태그 출력
     */
    public function output_open_graph_tags() {
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '">' . "\n";
        
        if (is_front_page()) {
            echo '<meta property="og:type" content="website">' . "\n";
            echo '<meta property="og:title" content="성수야! - 성수동 로컬 가이드">' . "\n";
            echo '<meta property="og:description" content="성수동의 모든 것을 한눈에! 카페, 레스토랑, 팝업스토어 정보와 AI 투어플래너">' . "\n";
            echo '<meta property="og:url" content="' . esc_url(home_url('/')) . '">' . "\n";
        } elseif (is_singular()) {
            echo '<meta property="og:type" content="article">' . "\n";
            echo '<meta property="og:title" content="' . esc_attr(get_the_title()) . ' - 성수야!">' . "\n";
            echo '<meta property="og:description" content="' . esc_attr($this->get_meta_description()) . '">' . "\n";
            echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";
            
            // 발행 시간
            echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c')) . '">' . "\n";
            echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c')) . '">' . "\n";
            
            // 작성자
            echo '<meta property="article:author" content="' . esc_attr(get_the_author()) . '">' . "\n";
            
            // 카테고리
            $categories = get_the_category();
            if ($categories) {
                echo '<meta property="article:section" content="' . esc_attr($categories[0]->name) . '">' . "\n";
            }
        }
        
        // 이미지
        $og_image = $this->get_og_image();
        if ($og_image) {
            echo '<meta property="og:image" content="' . esc_url($og_image['url']) . '">' . "\n";
            echo '<meta property="og:image:width" content="' . esc_attr($og_image['width']) . '">' . "\n";
            echo '<meta property="og:image:height" content="' . esc_attr($og_image['height']) . '">' . "\n";
            echo '<meta property="og:image:type" content="' . esc_attr($og_image['type']) . '">' . "\n";
            echo '<meta property="og:image:alt" content="' . esc_attr($og_image['alt']) . '">' . "\n";
        }
    }
    
    /**
     * Twitter Card 태그 출력
     */
    public function output_twitter_card_tags() {
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:site" content="@sungsuya">' . "\n";
        
        if (is_singular()) {
            echo '<meta name="twitter:title" content="' . esc_attr(get_the_title()) . ' - 성수야!">' . "\n";
            echo '<meta name="twitter:description" content="' . esc_attr($this->get_meta_description()) . '">' . "\n";
            
            $twitter_image = $this->get_og_image();
            if ($twitter_image) {
                echo '<meta name="twitter:image" content="' . esc_url($twitter_image['url']) . '">' . "\n";
                echo '<meta name="twitter:image:alt" content="' . esc_attr($twitter_image['alt']) . '">' . "\n";
            }
        }
    }
    
    /**
     * Canonical URL 출력
     */
    public function output_canonical_url() {
        $canonical = '';
        
        if (is_singular()) {
            $canonical = get_permalink();
        } elseif (is_home()) {
            $canonical = home_url('/');
        } elseif (is_category() || is_tag() || is_tax()) {
            $canonical = get_term_link(get_queried_object());
        } elseif (is_post_type_archive()) {
            $canonical = get_post_type_archive_link(get_post_type());
        }
        
        if ($canonical) {
            echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
        }
    }
    
    /**
     * 구조화된 데이터 출력
     */
    public function output_structured_data() {
        $data = array();
        
        // 웹사이트 기본 정보
        if (is_front_page()) {
            $data[] = $this->get_website_schema();
            $data[] = $this->get_organization_schema();
        }
        
        // 장소 정보
        if (is_singular('places')) {
            $data[] = $this->get_place_schema();
        }
        
        // 브레드크럼
        if (!is_front_page()) {
            $data[] = $this->get_breadcrumb_schema();
        }
        
        // 검색 상자
        if (is_front_page()) {
            $data[] = $this->get_search_action_schema();
        }
        
        if (!empty($data)) {
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            echo "\n" . '</script>' . "\n";
        }
    }
    
    /**
     * 웹사이트 스키마
     */
    private function get_website_schema() {
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => '성수야!',
            'alternateName' => 'Sungsuya',
            'url' => home_url('/'),
            'description' => '성수동 로컬 가이드 - 카페, 레스토랑, 팝업스토어 정보',
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => array(
                    '@type' => 'EntryPoint',
                    'urlTemplate' => home_url('/?s={search_term_string}')
                ),
                'query-input' => 'required name=search_term_string'
            ),
            'inLanguage' => array('ko-KR', 'en-US', 'zh-CN', 'ja-JP')
        );
    }
    
    /**
     * 조직 스키마
     */
    private function get_organization_schema() {
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => '성수야!',
            'url' => home_url('/'),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => SUNGSUYA_THEME_URL . '/assets/images/logo.png',
                'width' => 512,
                'height' => 512
            ),
            'sameAs' => array(
                'https://www.instagram.com/sungsuya',
                'https://www.facebook.com/sungsuya'
            ),
            'address' => array(
                '@type' => 'PostalAddress',
                'addressLocality' => '서울특별시',
                'addressRegion' => '성동구',
                'addressCountry' => 'KR'
            )
        );
    }
    
    /**
     * 장소 스키마
     */
    private function get_place_schema() {
        global $post;
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => get_the_title(),
            'description' => $this->get_meta_description(),
            'url' => get_permalink(),
            'image' => $this->get_place_images_for_schema()
        );
        
        // 주소
        $address = get_post_meta($post->ID, 'place_address', true);
        if ($address) {
            $schema['address'] = array(
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressLocality' => '서울특별시 성동구',
                'addressCountry' => 'KR'
            );
        }
        
        // 좌표
        $lat = get_post_meta($post->ID, 'place_lat', true);
        $lng = get_post_meta($post->ID, 'place_lng', true);
        if ($lat && $lng) {
            $schema['geo'] = array(
                '@type' => 'GeoCoordinates',
                'latitude' => $lat,
                'longitude' => $lng
            );
        }
        
        // 영업시간
        $opening_hours = get_post_meta($post->ID, 'opening_hours', true);
        if ($opening_hours) {
            $schema['openingHours'] = $this->parse_opening_hours($opening_hours);
        }
        
        // 전화번호
        $phone = get_post_meta($post->ID, 'place_phone', true);
        if ($phone) {
            $schema['telephone'] = $phone;
        }
        
        // 가격대
        $price_range = get_post_meta($post->ID, 'price_range', true);
        if ($price_range) {
            $schema['priceRange'] = $price_range;
        }
        
        // 장소 유형별 추가 정보
        $place_types = wp_get_post_terms($post->ID, 'place_type');
        if (!empty($place_types)) {
            $place_type = $place_types[0];
            
            if ($place_type->slug === 'cafe' || $place_type->slug === 'restaurant') {
                $schema['@type'] = 'Restaurant';
                $schema['servesCuisine'] = get_post_meta($post->ID, 'cuisine_type', true) ?: '한식';
            } elseif ($place_type->slug === 'popup-store') {
                $schema['@type'] = 'Store';
                $start_date = get_post_meta($post->ID, 'popup_start_date', true);
                $end_date = get_post_meta($post->ID, 'popup_end_date', true);
                
                if ($start_date && $end_date) {
                    $schema['event'] = array(
                        '@type' => 'Event',
                        'name' => get_the_title() . ' 팝업스토어',
                        'startDate' => $start_date,
                        'endDate' => $end_date,
                        'location' => array(
                            '@type' => 'Place',
                            'address' => $schema['address']
                        )
                    );
                }
            }
        }
        
        return $schema;
    }
    
    /**
     * 브레드크럼 스키마
     */
    private function get_breadcrumb_schema() {
        $items = array();
        $position = 1;
        
        // 홈
        $items[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => '홈',
            'item' => home_url('/')
        );
        
        // 카테고리/택소노미
        if (is_singular('places')) {
            $place_types = wp_get_post_terms(get_the_ID(), 'place_type');
            if (!empty($place_types)) {
                $items[] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $place_types[0]->name,
                    'item' => get_term_link($place_types[0])
                );
            }
            
            // 현재 페이지
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => get_the_title(),
                'item' => get_permalink()
            );
        }
        
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        );
    }
    
    /**
     * 검색 액션 스키마
     */
    private function get_search_action_schema() {
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'url' => home_url('/'),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => home_url('/?s={search_term_string}'),
                'query-input' => 'required name=search_term_string'
            )
        );
    }
    
    /**
     * 메타 설명 가져오기
     */
    private function get_meta_description() {
        $description = '';
        
        if (is_singular()) {
            // 커스텀 메타 설명
            $custom_desc = get_post_meta(get_the_ID(), '_seo_description', true);
            if ($custom_desc) {
                $description = $custom_desc;
            } else {
                // 요약 사용
                $description = get_the_excerpt();
                if (!$description) {
                    // 콘텐츠에서 추출
                    $content = get_the_content();
                    $content = strip_shortcodes($content);
                    $content = wp_strip_all_tags($content);
                    $description = wp_trim_words($content, 30);
                }
            }
        } elseif (is_category() || is_tag() || is_tax()) {
            $description = term_description();
        } elseif (is_home() || is_front_page()) {
            $description = '성수동의 모든 것을 한눈에! 카페, 레스토랑, 팝업스토어 정보와 AI 투어플래너로 나만의 성수동 여행을 계획하세요.';
        }
        
        return $description;
    }
    
    /**
     * 메타 키워드 가져오기
     */
    private function get_meta_keywords() {
        $keywords = '성수동, 성수역, 뚝섬역, 서울숲, 카페, 레스토랑, 팝업스토어, 맛집, 관광, 여행';
        
        if (is_singular()) {
            $custom_keywords = get_post_meta(get_the_ID(), '_seo_keywords', true);
            if ($custom_keywords) {
                $keywords = $custom_keywords;
            } else {
                // 태그 사용
                $tags = get_the_tags();
                if ($tags) {
                    $tag_names = wp_list_pluck($tags, 'name');
                    $keywords .= ', ' . implode(', ', $tag_names);
                }
            }
        }
        
        return $keywords;
    }
    
    /**
     * 로봇 메타 가져오기
     */
    private function get_robots_meta() {
        $robots = 'index, follow';
        
        if (is_singular()) {
            $custom_robots = get_post_meta(get_the_ID(), '_seo_robots', true);
            if ($custom_robots) {
                $robots = $custom_robots;
            }
        } elseif (is_search()) {
            $robots = 'noindex, follow';
        } elseif (is_404()) {
            $robots = 'noindex, nofollow';
        }
        
        return $robots;
    }
    
    /**
     * OG 이미지 가져오기
     */
    private function get_og_image() {
        $image = array();
        
        if (is_singular() && has_post_thumbnail()) {
            $thumbnail_id = get_post_thumbnail_id();
            $thumbnail = wp_get_attachment_image_src($thumbnail_id, 'large');
            $thumbnail_meta = wp_get_attachment_metadata($thumbnail_id);
            
            if ($thumbnail) {
                $image = array(
                    'url' => $thumbnail[0],
                    'width' => $thumbnail[1],
                    'height' => $thumbnail[2],
                    'type' => get_post_mime_type($thumbnail_id),
                    'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) ?: get_the_title()
                );
            }
        } else {
            // 기본 이미지
            $image = array(
                'url' => SUNGSUYA_THEME_URL . '/assets/images/og-default.jpg',
                'width' => 1200,
                'height' => 630,
                'type' => 'image/jpeg',
                'alt' => '성수야! - 성수동 로컬 가이드'
            );
        }
        
        return $image;
    }
    
    /**
     * 장소 이미지 스키마용
     */
    private function get_place_images_for_schema() {
        $images = array();
        
        if (has_post_thumbnail()) {
            $thumbnail_id = get_post_thumbnail_id();
            $thumbnail = wp_get_attachment_image_src($thumbnail_id, 'full');
            if ($thumbnail) {
                $images[] = $thumbnail[0];
            }
        }
        
        // 갤러리 이미지
        $gallery = get_post_meta(get_the_ID(), 'place_gallery', true);
        if ($gallery) {
            $gallery_ids = explode(',', $gallery);
            foreach ($gallery_ids as $id) {
                $image = wp_get_attachment_image_src($id, 'full');
                if ($image) {
                    $images[] = $image[0];
                }
            }
        }
        
        return $images;
    }
    
    /**
     * 영업시간 파싱
     */
    private function parse_opening_hours($hours_text) {
        // 간단한 예시 - 실제로는 더 복잡한 파싱 필요
        $hours = array();
        
        if (strpos($hours_text, '매일') !== false) {
            preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $hours_text, $matches);
            if ($matches) {
                $hours = array(
                    'Mo-Su ' . $matches[1] . '-' . $matches[2]
                );
            }
        }
        
        return $hours;
    }
    
    /**
     * SEO 메타박스 추가
     */
    public function add_seo_metabox() {
        $screens = array('post', 'page', 'places');
        
        foreach ($screens as $screen) {
            add_meta_box(
                'sungsuya_seo_meta',
                'SEO 설정',
                array($this, 'render_seo_metabox'),
                $screen,
                'normal',
                'high'
            );
        }
    }
    
    /**
     * SEO 메타박스 렌더링
     */
    public function render_seo_metabox($post) {
        wp_nonce_field('sungsuya_seo_meta', 'sungsuya_seo_nonce');
        
        $description = get_post_meta($post->ID, '_seo_description', true);
        $keywords = get_post_meta($post->ID, '_seo_keywords', true);
        $robots = get_post_meta($post->ID, '_seo_robots', true);
        ?>
        <div class="sungsuya-seo-meta">
            <p>
                <label for="seo_description"><strong>메타 설명</strong></label><br>
                <textarea name="seo_description" id="seo_description" rows="3" style="width:100%;"><?php echo esc_textarea($description); ?></textarea>
                <span class="description">검색 결과에 표시될 설명입니다. 160자 이내로 작성하세요.</span>
            </p>
            
            <p>
                <label for="seo_keywords"><strong>키워드</strong></label><br>
                <input type="text" name="seo_keywords" id="seo_keywords" value="<?php echo esc_attr($keywords); ?>" style="width:100%;">
                <span class="description">쉼표로 구분하여 입력하세요.</span>
            </p>
            
            <p>
                <label for="seo_robots"><strong>로봇 메타</strong></label><br>
                <select name="seo_robots" id="seo_robots">
                    <option value="">기본값 (index, follow)</option>
                    <option value="index, follow" <?php selected($robots, 'index, follow'); ?>>인덱스 허용, 링크 추적</option>
                    <option value="index, nofollow" <?php selected($robots, 'index, nofollow'); ?>>인덱스 허용, 링크 추적 안함</option>
                    <option value="noindex, follow" <?php selected($robots, 'noindex, follow'); ?>>인덱스 차단, 링크 추적</option>
                    <option value="noindex, nofollow" <?php selected($robots, 'noindex, nofollow'); ?>>인덱스 차단, 링크 추적 안함</option>
                </select>
            </p>
            
            <div class="seo-preview">
                <h4>검색 결과 미리보기</h4>
                <div class="google-preview" style="font-family: arial, sans-serif; max-width: 600px;">
                    <div style="color: #1a0dab; font-size: 18px; line-height: 1.2;">
                        <span id="preview-title"><?php echo esc_html(get_the_title()); ?></span> - 성수야!
                    </div>
                    <div style="color: #006621; font-size: 14px; line-height: 1.4;">
                        <?php echo esc_url(get_permalink()); ?>
                    </div>
                    <div style="color: #545454; font-size: 13px; line-height: 1.4;">
                        <span id="preview-description"><?php echo esc_html($description ?: wp_trim_words(get_the_content(), 20)); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // 실시간 미리보기
            $('#seo_description').on('input', function() {
                var desc = $(this).val() || '<?php echo esc_js(wp_trim_words(get_the_content(), 20)); ?>';
                $('#preview-description').text(desc);
            });
            
            $('#post_title, #title').on('input', function() {
                $('#preview-title').text($(this).val());
            });
        });
        </script>
        
        <style>
        .sungsuya-seo-meta p {
            margin-bottom: 20px;
        }
        .sungsuya-seo-meta .description {
            display: block;
            margin-top: 5px;
            color: #666;
            font-style: italic;
        }
        .seo-preview {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .seo-preview h4 {
            margin-top: 0;
        }
        </style>
        <?php
    }
    
    /**
     * SEO 메타 저장
     */
    public function save_seo_meta($post_id) {
        // 보안 체크
        if (!isset($_POST['sungsuya_seo_nonce']) || !wp_verify_nonce($_POST['sungsuya_seo_nonce'], 'sungsuya_seo_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // 메타 데이터 저장
        if (isset($_POST['seo_description'])) {
            update_post_meta($post_id, '_seo_description', sanitize_textarea_field($_POST['seo_description']));
        }
        
        if (isset($_POST['seo_keywords'])) {
            update_post_meta($post_id, '_seo_keywords', sanitize_text_field($_POST['seo_keywords']));
        }
        
        if (isset($_POST['seo_robots'])) {
            update_post_meta($post_id, '_seo_robots', sanitize_text_field($_POST['seo_robots']));
        }
    }
}

// 클래스 초기화
new Sungsuya_SEO_Manager();

/**
 * Yoast SEO 플러그인 충돌 방지
 */
add_filter('wpseo_robots', '__return_false');
add_filter('wpseo_canonical', '__return_false');
add_filter('wpseo_metadesc', '__return_false');
add_filter('wpseo_opengraph_url', '__return_false');
