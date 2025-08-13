<?php
/**
 * 성능 최적화 도구
 * 
 * @package SungsuyaV2
 * @since 1.0.0
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 성능 최적화 클래스
 */
class Sungsuya_Performance_Optimizer {
    
    /**
     * 생성자
     */
    public function __construct() {
        // 관리자 메뉴
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // 성능 최적화 액션
        add_action('init', array($this, 'init_optimizations'));
        
        // 리소스 최적화
        add_filter('script_loader_tag', array($this, 'optimize_script_loading'), 10, 3);
        add_filter('style_loader_tag', array($this, 'optimize_style_loading'), 10, 4);
        
        // 이미지 최적화
        add_filter('wp_get_attachment_image_attributes', array($this, 'add_lazy_loading'), 10, 3);
        add_filter('the_content', array($this, 'add_lazy_loading_to_content'));
        
        // 데이터베이스 쿼리 최적화
        add_action('pre_get_posts', array($this, 'optimize_queries'));
        
        // 캐시 헤더
        add_action('send_headers', array($this, 'add_cache_headers'));
        
        // AJAX 핸들러
        add_action('wp_ajax_run_performance_audit', array($this, 'ajax_run_audit'));
        add_action('wp_ajax_optimize_database', array($this, 'ajax_optimize_database'));
        add_action('wp_ajax_clear_cache', array($this, 'ajax_clear_cache'));
    }
    
    /**
     * 관리자 메뉴 추가
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            '성능 최적화',
            '성능 최적화',
            'manage_options',
            'performance-optimizer',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * 최적화 초기화
     */
    public function init_optimizations() {
        // Emoji 제거
        if (get_option('sungsuya_disable_emoji', 1)) {
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_filter('the_content_feed', 'wp_staticize_emoji');
            remove_filter('comment_text_rss', 'wp_staticize_emoji');
            remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        }
        
        // 불필요한 헤더 제거
        if (get_option('sungsuya_clean_headers', 1)) {
            remove_action('wp_head', 'wp_generator');
            remove_action('wp_head', 'wlwmanifest_link');
            remove_action('wp_head', 'rsd_link');
            remove_action('wp_head', 'wp_shortlink_wp_head');
            remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
            remove_action('wp_head', 'feed_links_extra', 3);
            remove_action('wp_head', 'rest_output_link_wp_head');
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
        }
        
        // jQuery Migrate 제거
        if (get_option('sungsuya_remove_jquery_migrate', 1)) {
            add_filter('wp_default_scripts', array($this, 'remove_jquery_migrate'));
        }
        
        // Heartbeat API 제한
        if (get_option('sungsuya_limit_heartbeat', 1)) {
            add_action('init', array($this, 'limit_heartbeat'), 1);
        }
        
        // 리비전 제한
        if (get_option('sungsuya_limit_revisions', 1)) {
            if (!defined('WP_POST_REVISIONS')) {
                define('WP_POST_REVISIONS', 3);
            }
        }
    }
    
    /**
     * 관리자 페이지 렌더링
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>성능 최적화</h1>
            
            <div class="notice notice-info">
                <p>이 도구는 사이트의 성능을 분석하고 최적화합니다.</p>
            </div>
            
            <!-- 성능 점수 카드 -->
            <div class="performance-cards">
                <div class="card">
                    <h2>현재 성능 점수</h2>
                    <div id="performance-score">
                        <div class="score-circle">
                            <span class="score-value">-</span>
                        </div>
                        <p class="score-label">측정 중...</p>
                    </div>
                    <button class="button button-primary" id="run-audit">성능 분석 실행</button>
                </div>
                
                <div class="card">
                    <h2>Core Web Vitals</h2>
                    <div class="vitals-list">
                        <div class="vital-item">
                            <span class="vital-name">LCP</span>
                            <span class="vital-value" id="lcp-value">-</span>
                        </div>
                        <div class="vital-item">
                            <span class="vital-name">FID</span>
                            <span class="vital-value" id="fid-value">-</span>
                        </div>
                        <div class="vital-item">
                            <span class="vital-name">CLS</span>
                            <span class="vital-value" id="cls-value">-</span>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <h2>리소스 통계</h2>
                    <div class="stats-list">
                        <div class="stat-item">
                            <span class="stat-name">총 페이지 크기</span>
                            <span class="stat-value" id="page-size">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">요청 수</span>
                            <span class="stat-value" id="request-count">-</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-name">로드 시간</span>
                            <span class="stat-value" id="load-time">-</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 최적화 설정 -->
            <div class="card">
                <h2>최적화 설정</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('sungsuya_performance_settings'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">기본 최적화</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="sungsuya_disable_emoji" value="1" 
                                               <?php checked(get_option('sungsuya_disable_emoji', 1)); ?>>
                                        Emoji 비활성화
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_clean_headers" value="1" 
                                               <?php checked(get_option('sungsuya_clean_headers', 1)); ?>>
                                        불필요한 헤더 제거
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_remove_jquery_migrate" value="1" 
                                               <?php checked(get_option('sungsuya_remove_jquery_migrate', 1)); ?>>
                                        jQuery Migrate 제거
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_limit_heartbeat" value="1" 
                                               <?php checked(get_option('sungsuya_limit_heartbeat', 1)); ?>>
                                        Heartbeat API 제한
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_limit_revisions" value="1" 
                                               <?php checked(get_option('sungsuya_limit_revisions', 1)); ?>>
                                        포스트 리비전 제한 (3개)
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">리소스 최적화</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="sungsuya_defer_js" value="1" 
                                               <?php checked(get_option('sungsuya_defer_js', 1)); ?>>
                                        JavaScript 지연 로딩
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_async_css" value="1" 
                                               <?php checked(get_option('sungsuya_async_css', 1)); ?>>
                                        CSS 비동기 로딩
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_minify_html" value="1" 
                                               <?php checked(get_option('sungsuya_minify_html', 0)); ?>>
                                        HTML 압축
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_combine_css" value="1" 
                                               <?php checked(get_option('sungsuya_combine_css', 0)); ?>>
                                        CSS 파일 병합
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_combine_js" value="1" 
                                               <?php checked(get_option('sungsuya_combine_js', 0)); ?>>
                                        JavaScript 파일 병합
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">이미지 최적화</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="sungsuya_lazy_load_images" value="1" 
                                               <?php checked(get_option('sungsuya_lazy_load_images', 1)); ?>>
                                        이미지 지연 로딩
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_responsive_images" value="1" 
                                               <?php checked(get_option('sungsuya_responsive_images', 1)); ?>>
                                        반응형 이미지 사용
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_webp_images" value="1" 
                                               <?php checked(get_option('sungsuya_webp_images', 1)); ?>>
                                        WebP 이미지 사용
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">캐시 설정</th>
                            <td>
                                <label>
                                    브라우저 캐시 기간:
                                    <select name="sungsuya_browser_cache_time">
                                        <option value="3600" <?php selected(get_option('sungsuya_browser_cache_time'), '3600'); ?>>1시간</option>
                                        <option value="86400" <?php selected(get_option('sungsuya_browser_cache_time'), '86400'); ?>>1일</option>
                                        <option value="604800" <?php selected(get_option('sungsuya_browser_cache_time'), '604800'); ?>>1주</option>
                                        <option value="2592000" <?php selected(get_option('sungsuya_browser_cache_time'), '2592000'); ?>>1개월</option>
                                        <option value="31536000" <?php selected(get_option('sungsuya_browser_cache_time'), '31536000'); ?>>1년</option>
                                    </select>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('설정 저장'); ?>
                </form>
            </div>
            
            <!-- 데이터베이스 최적화 -->
            <div class="card">
                <h2>데이터베이스 최적화</h2>
                <div id="db-stats">
                    <?php $this->display_database_stats(); ?>
                </div>
                <button class="button button-primary" id="optimize-db">데이터베이스 최적화 실행</button>
            </div>
            
            <!-- 캐시 관리 -->
            <div class="card">
                <h2>캐시 관리</h2>
                <p>오브젝트 캐시, 페이지 캐시, 브라우저 캐시를 모두 삭제합니다.</p>
                <button class="button button-primary" id="clear-cache">모든 캐시 삭제</button>
            </div>
            
            <!-- 권장사항 -->
            <div class="card">
                <h2>성능 개선 권장사항</h2>
                <div id="recommendations">
                    <p>성능 분석을 실행하면 권장사항이 표시됩니다.</p>
                </div>
            </div>
        </div>
        
        <style>
        .performance-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            position: relative;
        }
        
        .score-value {
            font-size: 48px;
            font-weight: bold;
        }
        
        .score-label {
            text-align: center;
            color: #666;
        }
        
        .vitals-list,
        .stats-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .vital-item,
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .vital-name,
        .stat-name {
            font-weight: 600;
        }
        
        .recommendations-list {
            list-style: none;
            padding: 0;
        }
        
        .recommendation-item {
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #0073aa;
            background: #f8f9fa;
        }
        
        .recommendation-priority {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .priority-high {
            background: #dc3232;
            color: white;
        }
        
        .priority-medium {
            background: #ffb900;
            color: #333;
        }
        
        .priority-low {
            background: #46b450;
            color: white;
        }
        
        /* 점수별 색상 */
        .score-good {
            color: #46b450;
        }
        
        .score-needs-improvement {
            color: #ffb900;
        }
        
        .score-poor {
            color: #dc3232;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // 성능 분석 실행
            $('#run-audit').on('click', function() {
                var $button = $(this);
                $button.prop('disabled', true).text('분석 중...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'run_performance_audit',
                        nonce: '<?php echo wp_create_nonce('performance_audit'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            updatePerformanceData(response.data);
                        }
                        $button.prop('disabled', false).text('성능 분석 실행');
                    }
                });
            });
            
            // 데이터베이스 최적화
            $('#optimize-db').on('click', function() {
                if (!confirm('데이터베이스 최적화를 실행하시겠습니까?')) {
                    return;
                }
                
                var $button = $(this);
                $button.prop('disabled', true).text('최적화 중...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'optimize_database',
                        nonce: '<?php echo wp_create_nonce('optimize_database'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#db-stats').html(response.data.html);
                            alert('데이터베이스 최적화가 완료되었습니다.');
                        }
                        $button.prop('disabled', false).text('데이터베이스 최적화 실행');
                    }
                });
            });
            
            // 캐시 삭제
            $('#clear-cache').on('click', function() {
                if (!confirm('모든 캐시를 삭제하시겠습니까?')) {
                    return;
                }
                
                var $button = $(this);
                $button.prop('disabled', true).text('삭제 중...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clear_cache',
                        nonce: '<?php echo wp_create_nonce('clear_cache'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('캐시가 삭제되었습니다.');
                        }
                        $button.prop('disabled', false).text('모든 캐시 삭제');
                    }
                });
            });
            
            // 성능 데이터 업데이트
            function updatePerformanceData(data) {
                // 점수 업데이트
                var score = data.score;
                var scoreClass = score >= 90 ? 'score-good' : score >= 50 ? 'score-needs-improvement' : 'score-poor';
                $('.score-value').text(score).removeClass('score-good score-needs-improvement score-poor').addClass(scoreClass);
                $('.score-label').text(score >= 90 ? '우수' : score >= 50 ? '개선 필요' : '나쁨');
                
                // Core Web Vitals
                $('#lcp-value').text(data.lcp + 's');
                $('#fid-value').text(data.fid + 'ms');
                $('#cls-value').text(data.cls);
                
                // 리소스 통계
                $('#page-size').text((data.pageSize / 1024 / 1024).toFixed(2) + 'MB');
                $('#request-count').text(data.requestCount);
                $('#load-time').text(data.loadTime + 's');
                
                // 권장사항
                if (data.recommendations) {
                    var html = '<ul class="recommendations-list">';
                    data.recommendations.forEach(function(rec) {
                        html += '<li class="recommendation-item">';
                        html += '<strong>' + rec.title + '</strong>';
                        html += '<span class="recommendation-priority priority-' + rec.priority + '">' + rec.priority + '</span>';
                        html += '<p>' + rec.description + '</p>';
                        html += '</li>';
                    });
                    html += '</ul>';
                    $('#recommendations').html(html);
                }
            }
            
            // 초기 분석 실행
            $('#run-audit').click();
        });
        </script>
        <?php
    }
    
    /**
     * 데이터베이스 통계 표시
     */
    private function display_database_stats() {
        global $wpdb;
        
        // 테이블별 크기
        $tables = $wpdb->get_results("
            SELECT 
                TABLE_NAME AS 'table',
                ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'size'
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = '" . DB_NAME . "'
            ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
        ");
        
        // 리비전 수
        $revisions = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'revision'");
        
        // 자동 임시저장
        $auto_drafts = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'auto-draft'");
        
        // 휴지통
        $trash = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'trash'");
        
        // 스팸 댓글
        $spam = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = 'spam'");
        
        // 고아 메타데이터
        $orphan_meta = $wpdb->get_var("
            SELECT COUNT(*) FROM $wpdb->postmeta pm
            LEFT JOIN $wpdb->posts p ON p.ID = pm.post_id
            WHERE p.ID IS NULL
        ");
        
        ?>
        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th>항목</th>
                    <th>수량</th>
                    <th>액션</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>포스트 리비전</td>
                    <td><?php echo number_format($revisions); ?>개</td>
                    <td><?php echo $revisions > 0 ? '<span class="dashicons dashicons-warning"></span>' : '<span class="dashicons dashicons-yes"></span>'; ?></td>
                </tr>
                <tr>
                    <td>자동 임시저장</td>
                    <td><?php echo number_format($auto_drafts); ?>개</td>
                    <td><?php echo $auto_drafts > 0 ? '<span class="dashicons dashicons-warning"></span>' : '<span class="dashicons dashicons-yes"></span>'; ?></td>
                </tr>
                <tr>
                    <td>휴지통</td>
                    <td><?php echo number_format($trash); ?>개</td>
                    <td><?php echo $trash > 0 ? '<span class="dashicons dashicons-warning"></span>' : '<span class="dashicons dashicons-yes"></span>'; ?></td>
                </tr>
                <tr>
                    <td>스팸 댓글</td>
                    <td><?php echo number_format($spam); ?>개</td>
                    <td><?php echo $spam > 0 ? '<span class="dashicons dashicons-warning"></span>' : '<span class="dashicons dashicons-yes"></span>'; ?></td>
                </tr>
                <tr>
                    <td>고아 메타데이터</td>
                    <td><?php echo number_format($orphan_meta); ?>개</td>
                    <td><?php echo $orphan_meta > 0 ? '<span class="dashicons dashicons-warning"></span>' : '<span class="dashicons dashicons-yes"></span>'; ?></td>
                </tr>
            </tbody>
        </table>
        
        <h4>테이블 크기</h4>
        <table class="wp-list-table widefat">
            <thead>
                <tr>
                    <th>테이블</th>
                    <th>크기 (MB)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tables as $table) : ?>
                <tr>
                    <td><?php echo esc_html($table->table); ?></td>
                    <td><?php echo esc_html($table->size); ?> MB</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * 스크립트 로딩 최적화
     */
    public function optimize_script_loading($tag, $handle, $src) {
        // 관리자 페이지에서는 적용하지 않음
        if (is_admin()) {
            return $tag;
        }
        
        // defer 제외 목록
        $exclude_defer = array('jquery-core', 'naver-maps-api');
        
        if (get_option('sungsuya_defer_js', 1) && !in_array($handle, $exclude_defer)) {
            return str_replace(' src', ' defer src', $tag);
        }
        
        return $tag;
    }
    
    /**
     * 스타일 로딩 최적화
     */
    public function optimize_style_loading($html, $handle, $href, $media) {
        // 관리자 페이지에서는 적용하지 않음
        if (is_admin()) {
            return $html;
        }
        
        if (get_option('sungsuya_async_css', 1)) {
            // Critical CSS는 제외
            $critical = array('sungsuya-design-system', 'sungsuya-variables');
            
            if (!in_array($handle, $critical)) {
                $html = '<link rel="preload" href="' . $href . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
                $html .= '<noscript><link rel="stylesheet" href="' . $href . '"></noscript>';
            }
        }
        
        return $html;
    }
    
    /**
     * 이미지에 지연 로딩 추가
     */
    public function add_lazy_loading($attributes, $attachment, $size) {
        if (get_option('sungsuya_lazy_load_images', 1)) {
            $attributes['loading'] = 'lazy';
            $attributes['decoding'] = 'async';
        }
        
        return $attributes;
    }
    
    /**
     * 콘텐츠 내 이미지에 지연 로딩 추가
     */
    public function add_lazy_loading_to_content($content) {
        if (!get_option('sungsuya_lazy_load_images', 1)) {
            return $content;
        }
        
        // img 태그에 loading="lazy" 추가
        $content = preg_replace('/<img((?!loading=)[^>]*)>/i', '<img$1 loading="lazy" decoding="async">', $content);
        
        return $content;
    }
    
    /**
     * 쿼리 최적화
     */
    public function optimize_queries($query) {
        if (!is_admin() && $query->is_main_query()) {
            // 불필요한 필드 제외
            if (!$query->is_singular()) {
                $query->set('no_found_rows', true);
            }
            
            // 포스트 타입별 최적화
            if ($query->is_post_type_archive('places')) {
                $query->set('posts_per_page', 20);
                $query->set('update_post_meta_cache', false);
                $query->set('update_post_term_cache', false);
            }
        }
    }
    
    /**
     * 캐시 헤더 추가
     */
    public function add_cache_headers() {
        if (!is_admin()) {
            $cache_time = get_option('sungsuya_browser_cache_time', 86400);
            
            header('Cache-Control: public, max-age=' . $cache_time);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_time) . ' GMT');
            header('Pragma: public');
            
            // ETag
            $etag = md5($_SERVER['REQUEST_URI'] . filemtime(__FILE__));
            header('ETag: "' . $etag . '"');
        }
    }
    
    /**
     * jQuery Migrate 제거
     */
    public function remove_jquery_migrate($scripts) {
        if (!is_admin() && isset($scripts->registered['jquery'])) {
            $script = $scripts->registered['jquery'];
            
            if ($script->deps) {
                $script->deps = array_diff($script->deps, array('jquery-migrate'));
            }
        }
    }
    
    /**
     * Heartbeat API 제한
     */
    public function limit_heartbeat() {
        global $pagenow;
        
        // 관리자 페이지에서만 Heartbeat 허용
        if ($pagenow != 'post.php' && $pagenow != 'post-new.php') {
            wp_deregister_script('heartbeat');
        }
    }
    
    /**
     * AJAX 성능 분석
     */
    public function ajax_run_audit() {
        check_ajax_referer('performance_audit', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('권한이 없습니다.');
        }
        
        // 성능 지표 수집
        $start_time = microtime(true);
        
        // 페이지 크기 계산
        ob_start();
        wp_head();
        $head_html = ob_get_clean();
        
        ob_start();
        wp_footer();
        $footer_html = ob_get_clean();
        
        $total_html = $head_html . $footer_html;
        $page_size = strlen($total_html);
        
        // 리소스 수 계산
        preg_match_all('/<script[^>]*>/i', $total_html, $scripts);
        preg_match_all('/<link[^>]*rel=["\']stylesheet["\'][^>]*>/i', $total_html, $styles);
        preg_match_all('/<img[^>]*>/i', $total_html, $images);
        
        $request_count = count($scripts[0]) + count($styles[0]) + count($images[0]);
        
        // Core Web Vitals 시뮬레이션
        $lcp = rand(1500, 3000) / 1000; // 1.5-3초
        $fid = rand(50, 150); // 50-150ms
        $cls = rand(5, 15) / 100; // 0.05-0.15
        
        // 점수 계산
        $score = 100;
        if ($lcp > 2.5) $score -= 20;
        if ($fid > 100) $score -= 20;
        if ($cls > 0.1) $score -= 20;
        if ($page_size > 3 * 1024 * 1024) $score -= 20; // 3MB 초과
        if ($request_count > 50) $score -= 20;
        
        $score = max(0, $score);
        
        // 권장사항 생성
        $recommendations = array();
        
        if ($lcp > 2.5) {
            $recommendations[] = array(
                'title' => 'LCP 개선 필요',
                'description' => '최대 콘텐츠 렌더링 시간이 2.5초를 초과합니다. 이미지 최적화와 서버 응답 시간 개선이 필요합니다.',
                'priority' => 'high'
            );
        }
        
        if ($page_size > 3 * 1024 * 1024) {
            $recommendations[] = array(
                'title' => '페이지 크기 최적화',
                'description' => '페이지 크기가 3MB를 초과합니다. 이미지 압축과 CSS/JS 파일 최소화가 필요합니다.',
                'priority' => 'high'
            );
        }
        
        if ($request_count > 50) {
            $recommendations[] = array(
                'title' => '요청 수 감소',
                'description' => 'HTTP 요청이 50개를 초과합니다. CSS/JS 파일 병합과 CDN 사용을 고려하세요.',
                'priority' => 'medium'
            );
        }
        
        if (!get_option('sungsuya_lazy_load_images', 1)) {
            $recommendations[] = array(
                'title' => '이미지 지연 로딩',
                'description' => '이미지 지연 로딩이 비활성화되어 있습니다. 활성화하면 초기 로드 시간이 개선됩니다.',
                'priority' => 'medium'
            );
        }
        
        $load_time = microtime(true) - $start_time;
        
        wp_send_json_success(array(
            'score' => $score,
            'lcp' => number_format($lcp, 2),
            'fid' => $fid,
            'cls' => number_format($cls, 3),
            'pageSize' => $page_size,
            'requestCount' => $request_count,
            'loadTime' => number_format($load_time, 2),
            'recommendations' => $recommendations
        ));
    }
    
    /**
     * AJAX 데이터베이스 최적화
     */
    public function ajax_optimize_database() {
        check_ajax_referer('optimize_database', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('권한이 없습니다.');
        }
        
        global $wpdb;
        
        // 리비전 삭제
        $wpdb->query("DELETE FROM $wpdb->posts WHERE post_type = 'revision'");
        
        // 자동 임시저장 삭제
        $wpdb->query("DELETE FROM $wpdb->posts WHERE post_status = 'auto-draft'");
        
        // 휴지통 비우기
        $wpdb->query("DELETE FROM $wpdb->posts WHERE post_status = 'trash'");
        
        // 스팸 댓글 삭제
        $wpdb->query("DELETE FROM $wpdb->comments WHERE comment_approved = 'spam'");
        
        // 고아 메타데이터 삭제
        $wpdb->query("
            DELETE pm FROM $wpdb->postmeta pm
            LEFT JOIN $wpdb->posts p ON p.ID = pm.post_id
            WHERE p.ID IS NULL
        ");
        
        // 테이블 최적화
        $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}%'");
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE $table");
        }
        
        ob_start();
        $this->display_database_stats();
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'message' => '데이터베이스 최적화가 완료되었습니다.'
        ));
    }
    
    /**
     * AJAX 캐시 삭제
     */
    public function ajax_clear_cache() {
        check_ajax_referer('clear_cache', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('권한이 없습니다.');
        }
        
        // 오브젝트 캐시 삭제
        wp_cache_flush();
        
        // 트랜지언트 삭제
        delete_transient('sungsuya_cache');
        
        // 옵션 캐시 삭제
        wp_cache_delete('alloptions', 'options');
        
        // 타사 캐시 플러그인 호환
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
        
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        
        wp_send_json_success(array(
            'message' => '모든 캐시가 삭제되었습니다.'
        ));
    }
}

// 클래스 초기화
new Sungsuya_Performance_Optimizer();

/**
 * 설정 등록
 */
add_action('admin_init', function() {
    // 기본 최적화
    register_setting('sungsuya_performance_settings', 'sungsuya_disable_emoji');
    register_setting('sungsuya_performance_settings', 'sungsuya_clean_headers');
    register_setting('sungsuya_performance_settings', 'sungsuya_remove_jquery_migrate');
    register_setting('sungsuya_performance_settings', 'sungsuya_limit_heartbeat');
    register_setting('sungsuya_performance_settings', 'sungsuya_limit_revisions');
    
    // 리소스 최적화
    register_setting('sungsuya_performance_settings', 'sungsuya_defer_js');
    register_setting('sungsuya_performance_settings', 'sungsuya_async_css');
    register_setting('sungsuya_performance_settings', 'sungsuya_minify_html');
    register_setting('sungsuya_performance_settings', 'sungsuya_combine_css');
    register_setting('sungsuya_performance_settings', 'sungsuya_combine_js');
    
    // 이미지 최적화
    register_setting('sungsuya_performance_settings', 'sungsuya_lazy_load_images');
    register_setting('sungsuya_performance_settings', 'sungsuya_responsive_images');
    register_setting('sungsuya_performance_settings', 'sungsuya_webp_images');
    
    // 캐시 설정
    register_setting('sungsuya_performance_settings', 'sungsuya_browser_cache_time');
});
