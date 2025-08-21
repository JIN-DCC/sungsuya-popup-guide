<?php
/**
 * 접근성 개선 도구
 * 
 * @package SungsuyaV2
 * @since 1.0.0
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 접근성 개선 클래스
 */
class Sungsuya_Accessibility_Manager {
    
    /**
     * 생성자
     */
    public function __construct() {
        // 관리자 메뉴
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // 접근성 개선
        add_action('wp_head', array($this, 'add_skip_links'), 1);
        add_filter('wp_get_attachment_image_attributes', array($this, 'ensure_alt_text'), 10, 3);
        add_filter('the_content', array($this, 'improve_content_accessibility'));
        add_filter('widget_text', array($this, 'improve_widget_accessibility'));
        add_filter('nav_menu_link_attributes', array($this, 'add_aria_labels'), 10, 4);
        
        // 스크립트 및 스타일
        add_action('wp_enqueue_scripts', array($this, 'enqueue_accessibility_assets'));
        
        // AJAX 핸들러
        add_action('wp_ajax_test_accessibility', array($this, 'ajax_test_accessibility'));
    }
    
    /**
     * 관리자 메뉴 추가
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            '접근성 관리',
            '접근성 관리',
            'manage_options',
            'accessibility-manager',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Skip Links 추가
     */
    public function add_skip_links() {
        ?>
        <div class="skip-links screen-reader-text">
            <a href="#main" class="skip-link"><?php esc_html_e('본문으로 건너뛰기', 'sungsuya'); ?></a>
            <a href="#nav" class="skip-link"><?php esc_html_e('주 메뉴로 건너뛰기', 'sungsuya'); ?></a>
            <a href="#search" class="skip-link"><?php esc_html_e('검색으로 건너뛰기', 'sungsuya'); ?></a>
        </div>
        <?php
    }
    
    /**
     * 이미지 Alt 텍스트 확인
     */
    public function ensure_alt_text($attributes, $attachment, $size) {
        if (empty($attributes['alt'])) {
            $attributes['alt'] = get_the_title($attachment->ID);
            
            if (empty($attributes['alt'])) {
                $attributes['alt'] = '이미지';
            }
        }
        
        return $attributes;
    }
    
    /**
     * 콘텐츠 접근성 개선
     */
    public function improve_content_accessibility($content) {
        // 테이블에 scope 추가
        $content = preg_replace_callback('/<th>(.*?)<\/th>/is', function($matches) {
            return '<th scope="col">' . $matches[1] . '</th>';
        }, $content);
        
        // 빈 링크 텍스트 확인
        $content = preg_replace_callback('/<a([^>]*)>(\s*)<\/a>/is', function($matches) {
            return '<a' . $matches[1] . ' aria-label="링크">' . $matches[2] . '</a>';
        }, $content);
        
        // 폼 레이블 확인
        $content = preg_replace_callback('/<input([^>]*?)(?!aria-label)([^>]*?)>/is', function($matches) {
            if (strpos($matches[0], 'type="submit"') === false && strpos($matches[0], 'type="button"') === false) {
                return '<input' . $matches[1] . ' aria-label="입력 필드"' . $matches[2] . '>';
            }
            return $matches[0];
        }, $content);
        
        return $content;
    }
    
    /**
     * 위젯 접근성 개선
     */
    public function improve_widget_accessibility($content) {
        return $this->improve_content_accessibility($content);
    }
    
    /**
     * 메뉴 ARIA 레이블 추가
     */
    public function add_aria_labels($atts, $item, $args, $depth) {
        // 서브메뉴가 있는 경우
        if (in_array('menu-item-has-children', $item->classes)) {
            $atts['aria-haspopup'] = 'true';
            $atts['aria-expanded'] = 'false';
        }
        
        // 현재 페이지
        if ($item->current) {
            $atts['aria-current'] = 'page';
        }
        
        return $atts;
    }
    
    /**
     * 접근성 관련 스크립트/스타일 로드
     */
    public function enqueue_accessibility_assets() {
        // 접근성 스타일
        wp_enqueue_style(
            'sungsuya-accessibility',
            SUNGSUYA_THEME_URL . '/assets/css/accessibility.css',
            array(),
            SUNGSUYA_VERSION
        );
        
        // 접근성 스크립트
        wp_enqueue_script(
            'sungsuya-accessibility',
            SUNGSUYA_THEME_URL . '/assets/js/accessibility.js',
            array(),
            SUNGSUYA_VERSION,
            true
        );
        
        // 설정 전달
        wp_localize_script('sungsuya-accessibility', 'accessibilitySettings', array(
            'focusOutlineEnabled' => get_option('sungsuya_focus_outline', 1),
            'keyboardNavEnabled' => get_option('sungsuya_keyboard_nav', 1),
            'ariaLiveEnabled' => get_option('sungsuya_aria_live', 1),
            'highContrastEnabled' => get_option('sungsuya_high_contrast', 0)
        ));
    }
    
    /**
     * 관리자 페이지 렌더링
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>접근성 관리</h1>
            
            <div class="notice notice-info">
                <p>웹 접근성(WCAG 2.1) 준수를 위한 설정과 테스트 도구입니다.</p>
            </div>
            
            <!-- 접근성 점수 -->
            <div class="card">
                <h2>접근성 점수</h2>
                <div id="accessibility-score">
                    <div class="score-display">
                        <span class="score-value">-</span>
                        <span class="score-label">측정 중...</span>
                    </div>
                    <button class="button button-primary" id="run-accessibility-test">접근성 테스트 실행</button>
                </div>
            </div>
            
            <!-- 접근성 설정 -->
            <div class="card">
                <h2>접근성 설정</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('sungsuya_accessibility_settings'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">시각적 개선</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="sungsuya_focus_outline" value="1" 
                                               <?php checked(get_option('sungsuya_focus_outline', 1)); ?>>
                                        포커스 아웃라인 강화
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_high_contrast" value="1" 
                                               <?php checked(get_option('sungsuya_high_contrast', 0)); ?>>
                                        고대비 모드 지원
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_text_resize" value="1" 
                                               <?php checked(get_option('sungsuya_text_resize', 1)); ?>>
                                        텍스트 크기 조절 버튼
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_color_blind" value="1" 
                                               <?php checked(get_option('sungsuya_color_blind', 0)); ?>>
                                        색맹 친화적 색상
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">키보드 네비게이션</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="sungsuya_keyboard_nav" value="1" 
                                               <?php checked(get_option('sungsuya_keyboard_nav', 1)); ?>>
                                        키보드 네비게이션 개선
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_skip_links" value="1" 
                                               <?php checked(get_option('sungsuya_skip_links', 1)); ?>>
                                        건너뛰기 링크 표시
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_tab_order" value="1" 
                                               <?php checked(get_option('sungsuya_tab_order', 1)); ?>>
                                        논리적 탭 순서
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">스크린 리더</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="sungsuya_aria_labels" value="1" 
                                               <?php checked(get_option('sungsuya_aria_labels', 1)); ?>>
                                        ARIA 레이블 자동 추가
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_aria_live" value="1" 
                                               <?php checked(get_option('sungsuya_aria_live', 1)); ?>>
                                        실시간 영역 알림
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="sungsuya_screen_reader_text" value="1" 
                                               <?php checked(get_option('sungsuya_screen_reader_text', 1)); ?>>
                                        스크린 리더 전용 텍스트
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">언어 설정</th>
                            <td>
                                <label>
                                    기본 언어:
                                    <select name="sungsuya_default_lang">
                                        <option value="ko" <?php selected(get_option('sungsuya_default_lang'), 'ko'); ?>>한국어</option>
                                        <option value="en" <?php selected(get_option('sungsuya_default_lang'), 'en'); ?>>English</option>
                                        <option value="zh" <?php selected(get_option('sungsuya_default_lang'), 'zh'); ?>>中文</option>
                                        <option value="ja" <?php selected(get_option('sungsuya_default_lang'), 'ja'); ?>>日本語</option>
                                    </select>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('설정 저장'); ?>
                </form>
            </div>
            
            <!-- 접근성 이슈 -->
            <div class="card">
                <h2>발견된 접근성 이슈</h2>
                <div id="accessibility-issues">
                    <p>접근성 테스트를 실행하면 이슈가 표시됩니다.</p>
                </div>
            </div>
            
            <!-- 색상 대비 검사기 -->
            <div class="card">
                <h2>색상 대비 검사기</h2>
                <div class="color-contrast-checker">
                    <div class="color-inputs">
                        <label>
                            전경색 (텍스트):
                            <input type="color" id="foreground-color" value="#000000">
                            <input type="text" id="foreground-hex" value="#000000" pattern="^#[0-9A-Fa-f]{6}$">
                        </label>
                        <label>
                            배경색:
                            <input type="color" id="background-color" value="#ffffff">
                            <input type="text" id="background-hex" value="#ffffff" pattern="^#[0-9A-Fa-f]{6}$">
                        </label>
                    </div>
                    <div class="contrast-results">
                        <div class="contrast-ratio">
                            <strong>대비율:</strong> <span id="contrast-ratio">21:1</span>
                        </div>
                        <div class="contrast-grades">
                            <div class="grade-item">
                                <span>일반 텍스트 (AA):</span>
                                <span id="aa-normal" class="grade pass">✓ 통과</span>
                            </div>
                            <div class="grade-item">
                                <span>일반 텍스트 (AAA):</span>
                                <span id="aaa-normal" class="grade pass">✓ 통과</span>
                            </div>
                            <div class="grade-item">
                                <span>큰 텍스트 (AA):</span>
                                <span id="aa-large" class="grade pass">✓ 통과</span>
                            </div>
                            <div class="grade-item">
                                <span>큰 텍스트 (AAA):</span>
                                <span id="aaa-large" class="grade pass">✓ 통과</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .score-display {
            text-align: center;
            margin: 20px 0;
        }
        
        .score-value {
            display: block;
            font-size: 72px;
            font-weight: bold;
            line-height: 1;
        }
        
        .score-label {
            display: block;
            margin-top: 10px;
            color: #666;
        }
        
        .accessibility-issue {
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #dc3545;
            background: #f8f9fa;
        }
        
        .issue-severity {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .severity-error {
            background: #dc3545;
            color: white;
        }
        
        .severity-warning {
            background: #ffc107;
            color: #333;
        }
        
        .severity-notice {
            background: #17a2b8;
            color: white;
        }
        
        .color-contrast-checker {
            max-width: 600px;
        }
        
        .color-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .color-inputs label {
            display: block;
        }
        
        .color-inputs input[type="color"] {
            width: 50px;
            height: 50px;
            vertical-align: middle;
            margin-right: 10px;
        }
        
        .color-inputs input[type="text"] {
            width: 100px;
            padding: 5px;
        }
        
        .contrast-results {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .contrast-ratio {
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .contrast-grades {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .grade-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: white;
            border-radius: 4px;
        }
        
        .grade {
            font-weight: bold;
        }
        
        .grade.pass {
            color: #28a745;
        }
        
        .grade.fail {
            color: #dc3545;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // 접근성 테스트 실행
            $('#run-accessibility-test').on('click', function() {
                var $button = $(this);
                $button.prop('disabled', true).text('테스트 중...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_accessibility',
                        nonce: '<?php echo wp_create_nonce('accessibility_test'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            updateAccessibilityResults(response.data);
                        }
                        $button.prop('disabled', false).text('접근성 테스트 실행');
                    }
                });
            });
            
            // 접근성 결과 업데이트
            function updateAccessibilityResults(data) {
                // 점수 업데이트
                $('.score-value').text(data.score);
                $('.score-label').text(data.score >= 90 ? '우수' : data.score >= 70 ? '양호' : '개선 필요');
                
                // 이슈 표시
                if (data.issues && data.issues.length > 0) {
                    var issuesHtml = '';
                    data.issues.forEach(function(issue) {
                        issuesHtml += '<div class="accessibility-issue">';
                        issuesHtml += '<strong>' + issue.title + '</strong>';
                        issuesHtml += '<span class="issue-severity severity-' + issue.severity + '">' + issue.severity + '</span>';
                        issuesHtml += '<p>' + issue.description + '</p>';
                        issuesHtml += '<p><em>해결 방법: ' + issue.solution + '</em></p>';
                        issuesHtml += '</div>';
                    });
                    $('#accessibility-issues').html(issuesHtml);
                } else {
                    $('#accessibility-issues').html('<p style="color: green;">✓ 접근성 이슈가 발견되지 않았습니다.</p>');
                }
            }
            
            // 색상 대비 계산기
            function calculateContrast() {
                var fg = $('#foreground-hex').val();
                var bg = $('#background-hex').val();
                
                var ratio = getContrastRatio(fg, bg);
                $('#contrast-ratio').text(ratio.toFixed(2) + ':1');
                
                // WCAG 기준 확인
                $('#aa-normal').removeClass('pass fail').addClass(ratio >= 4.5 ? 'pass' : 'fail')
                    .text(ratio >= 4.5 ? '✓ 통과' : '✗ 실패');
                $('#aaa-normal').removeClass('pass fail').addClass(ratio >= 7 ? 'pass' : 'fail')
                    .text(ratio >= 7 ? '✓ 통과' : '✗ 실패');
                $('#aa-large').removeClass('pass fail').addClass(ratio >= 3 ? 'pass' : 'fail')
                    .text(ratio >= 3 ? '✓ 통과' : '✗ 실패');
                $('#aaa-large').removeClass('pass fail').addClass(ratio >= 4.5 ? 'pass' : 'fail')
                    .text(ratio >= 4.5 ? '✓ 통과' : '✗ 실패');
            }
            
            // 색상 대비율 계산
            function getContrastRatio(fg, bg) {
                var l1 = getLuminance(fg);
                var l2 = getLuminance(bg);
                var lighter = Math.max(l1, l2);
                var darker = Math.min(l1, l2);
                return (lighter + 0.05) / (darker + 0.05);
            }
            
            // 상대 휘도 계산
            function getLuminance(hex) {
                var rgb = hexToRgb(hex);
                var r = rgb.r / 255;
                var g = rgb.g / 255;
                var b = rgb.b / 255;
                
                r = r <= 0.03928 ? r / 12.92 : Math.pow((r + 0.055) / 1.055, 2.4);
                g = g <= 0.03928 ? g / 12.92 : Math.pow((g + 0.055) / 1.055, 2.4);
                b = b <= 0.03928 ? b / 12.92 : Math.pow((b + 0.055) / 1.055, 2.4);
                
                return 0.2126 * r + 0.7152 * g + 0.0722 * b;
            }
            
            // HEX to RGB 변환
            function hexToRgb(hex) {
                var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
                return result ? {
                    r: parseInt(result[1], 16),
                    g: parseInt(result[2], 16),
                    b: parseInt(result[3], 16)
                } : null;
            }
            
            // 색상 입력 이벤트
            $('#foreground-color, #background-color').on('input', function() {
                var id = $(this).attr('id');
                var hex = $(this).val();
                $('#' + id.replace('-color', '-hex')).val(hex);
                calculateContrast();
            });
            
            $('#foreground-hex, #background-hex').on('input', function() {
                var id = $(this).attr('id');
                var hex = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
                    $('#' + id.replace('-hex', '-color')).val(hex);
                    calculateContrast();
                }
            });
            
            // 초기 계산
            calculateContrast();
            
            // 초기 테스트 실행
            $('#run-accessibility-test').click();
        });
        </script>
        <?php
    }
    
    /**
     * AJAX 접근성 테스트
     */
    public function ajax_test_accessibility() {
        check_ajax_referer('accessibility_test', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('권한이 없습니다.');
        }
        
        $issues = array();
        $score = 100;
        
        // 이미지 Alt 텍스트 검사
        global $wpdb;
        $images_without_alt = $wpdb->get_var("
            SELECT COUNT(*) FROM $wpdb->posts p
            LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
            WHERE p.post_type = 'attachment'
            AND p.post_mime_type LIKE 'image/%'
            AND (pm.meta_value IS NULL OR pm.meta_value = '')
        ");
        
        if ($images_without_alt > 0) {
            $issues[] = array(
                'title' => 'Alt 텍스트 누락',
                'description' => $images_without_alt . '개의 이미지에 대체 텍스트가 없습니다.',
                'severity' => 'error',
                'solution' => '미디어 라이브러리에서 각 이미지에 대체 텍스트를 추가하세요.'
            );
            $score -= 20;
        }
        
        // 헤딩 구조 검사
        $pages = get_posts(array(
            'post_type' => array('page', 'post', 'places'),
            'posts_per_page' => 10,
            'post_status' => 'publish'
        ));
        
        $heading_issues = 0;
        foreach ($pages as $page) {
            $content = $page->post_content;
            
            // H1이 여러 개인지 확인
            if (substr_count($content, '<h1') > 1) {
                $heading_issues++;
            }
            
            // 헤딩 레벨 건너뛰기 확인
            preg_match_all('/<h([1-6])/i', $content, $matches);
            if (!empty($matches[1])) {
                $levels = array_map('intval', $matches[1]);
                for ($i = 1; $i < count($levels); $i++) {
                    if ($levels[$i] - $levels[$i-1] > 1) {
                        $heading_issues++;
                        break;
                    }
                }
            }
        }
        
        if ($heading_issues > 0) {
            $issues[] = array(
                'title' => '헤딩 구조 문제',
                'description' => $heading_issues . '개의 페이지에서 헤딩 구조 문제가 발견되었습니다.',
                'severity' => 'warning',
                'solution' => '논리적인 헤딩 계층 구조를 유지하고 레벨을 건너뛰지 마세요.'
            );
            $score -= 10;
        }
        
        // 링크 텍스트 검사
        $generic_link_text = array('click here', 'here', 'read more', '더보기', '여기', '클릭');
        $generic_links = 0;
        
        foreach ($pages as $page) {
            foreach ($generic_link_text as $text) {
                if (stripos($page->post_content, '>' . $text . '</a>') !== false) {
                    $generic_links++;
                }
            }
        }
        
        if ($generic_links > 0) {
            $issues[] = array(
                'title' => '불명확한 링크 텍스트',
                'description' => $generic_links . '개의 링크가 명확하지 않은 텍스트를 사용합니다.',
                'severity' => 'warning',
                'solution' => '링크 텍스트는 목적지를 명확히 설명해야 합니다.'
            );
            $score -= 5;
        }
        
        // 언어 속성 확인
        $lang_attr = get_bloginfo('language');
        if (empty($lang_attr)) {
            $issues[] = array(
                'title' => '언어 속성 누락',
                'description' => 'HTML lang 속성이 설정되지 않았습니다.',
                'severity' => 'error',
                'solution' => '사이트 언어를 설정하세요.'
            );
            $score -= 10;
        }
        
        // 폼 레이블 검사
        $forms_without_labels = 0;
        foreach ($pages as $page) {
            if (preg_match_all('/<input[^>]*type=["\'](?!submit|button|hidden)[^"\']*["\'][^>]*>/i', $page->post_content, $inputs)) {
                foreach ($inputs[0] as $input) {
                    if (!preg_match('/(?:id=["\']([^"\']+)["\']|aria-label=|aria-labelledby=)/i', $input)) {
                        $forms_without_labels++;
                    }
                }
            }
        }
        
        if ($forms_without_labels > 0) {
            $issues[] = array(
                'title' => '폼 레이블 누락',
                'description' => $forms_without_labels . '개의 입력 필드에 레이블이 없습니다.',
                'severity' => 'error',
                'solution' => '모든 폼 입력 필드에 적절한 레이블을 추가하세요.'
            );
            $score -= 15;
        }
        
        $score = max(0, $score);
        
        wp_send_json_success(array(
            'score' => $score,
            'issues' => $issues
        ));
    }
}

// 클래스 초기화
new Sungsuya_Accessibility_Manager();

/**
 * 설정 등록
 */
add_action('admin_init', function() {
    // 시각적 개선
    register_setting('sungsuya_accessibility_settings', 'sungsuya_focus_outline');
    register_setting('sungsuya_accessibility_settings', 'sungsuya_high_contrast');
    register_setting('sungsuya_accessibility_settings', 'sungsuya_text_resize');
    register_setting('sungsuya_accessibility_settings', 'sungsuya_color_blind');
    
    // 키보드 네비게이션
    register_setting('sungsuya_accessibility_settings', 'sungsuya_keyboard_nav');
    register_setting('sungsuya_accessibility_settings', 'sungsuya_skip_links');
    register_setting('sungsuya_accessibility_settings', 'sungsuya_tab_order');
    
    // 스크린 리더
    register_setting('sungsuya_accessibility_settings', 'sungsuya_aria_labels');
    register_setting('sungsuya_accessibility_settings', 'sungsuya_aria_live');
    register_setting('sungsuya_accessibility_settings', 'sungsuya_screen_reader_text');
    
    // 언어
    register_setting('sungsuya_accessibility_settings', 'sungsuya_default_lang');
});
