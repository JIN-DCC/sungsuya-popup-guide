<?php
/**
 * GTranslate 통합 수정 스크립트
 * GTranslate 플러그인이 제대로 작동하도록 도와주는 헬퍼
 */

// GTranslate 설정 확인 및 기본값 설정
add_action('init', 'ensure_gtranslate_settings');
function ensure_gtranslate_settings() {
    // GTranslate 설정이 없으면 기본값 추가
    $gtranslate_settings = get_option('GTranslate');
    if (empty($gtranslate_settings)) {
        $default_settings = array(
            'default_language' => 'ko',
            'languages' => array('ko', 'en', 'zh-CN', 'ja'),
            'widget_look' => 'dropdown_with_flags',
            'flag_size' => 16,
            'add_new_line' => 0,
            'select_language_label' => '',
        );
        update_option('GTranslate', $default_settings);
    }
}

// 리뷰 시스템에서 불필요한 언어 선택기 제거를 위한 CSS
add_action('wp_head', 'fix_review_system_styles');
function fix_review_system_styles() {
    ?>
    <style>
    /* GTranslate 위젯 스타일 개선 */
    .gtranslate_wrapper {
        display: inline-block !important;
        min-width: 120px;
    }
    
    /* GTranslate 드롭다운 스타일 - 흰색 배경으로 변경하여 가독성 향상 */
    .goog-te-combo,
    .gtranslate_wrapper select {
        background: rgba(255, 255, 255, 0.95) !important;
        color: #333 !important;
        border: 1px solid #ddd !important;
        padding: 8px 32px 8px 12px !important;
        font-size: 14px !important;
        cursor: pointer !important;
        border-radius: 8px !important;
    }
    
    .goog-te-combo:hover,
    .gtranslate_wrapper select:hover {
        background: rgba(255, 255, 255, 1) !important;
        border-color: #667eea !important;
    }
    
    /* 헤더가 투명할 때 (스크롤 전) */
    .site-header:not(.scrolled) .gtranslate_wrapper select,
    .site-header:not(.scrolled) .goog-te-combo {
        background: rgba(255, 255, 255, 0.1) !important;
        color: #FFFFFF !important;
        border: 2px solid rgba(255, 255, 255, 0.3) !important;
    }
    
    .site-header:not(.scrolled) .gtranslate_wrapper select:hover,
    .site-header:not(.scrolled) .goog-te-combo:hover {
        background: rgba(255, 255, 255, 0.2) !important;
        border-color: rgba(255, 255, 255, 0.5) !important;
    }
    
    /* 스크롤 시 스타일 */
    .site-header.scrolled .gtranslate_wrapper select,
    .site-header.scrolled .goog-te-combo {
        background: rgba(255, 255, 255, 0.95) !important;
        color: #2C2C2C !important;
        border: 1px solid #ddd !important;
    }
    
    .site-header.scrolled .gtranslate_wrapper select:hover,
    .site-header.scrolled .goog-te-combo:hover {
        background: #FFFFFF !important;
        border-color: #667eea !important;
    }
    
    /* 구글 번역 바 숨기기 */
    .goog-te-banner-frame,
    #goog-gt-tt,
    .goog-te-balloon-frame {
        display: none !important;
    }
    
    .goog-te-banner-frame.skiptranslate {
        display: none !important;
    }
    
    body {
        top: 0 !important;
        position: static !important;
    }
    
    /* 번역된 페이지에서도 스타일 유지 */
    .translated-ltr .gtranslate_wrapper {
        display: inline-block !important;
    }
    
    /* GTranslate 메뉴 스타일 */
    .goog-te-menu-value span {
        color: #333 !important;
    }
    
    .goog-te-menu-frame {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        border: 1px solid #ddd !important;
    }
    </style>
    <?php
}

// GTranslate JavaScript 보완
add_action('wp_footer', 'enhance_gtranslate_js', 100);
function enhance_gtranslate_js() {
    ?>
    <script>
    // GTranslate 초기화 확인 및 디버깅
    document.addEventListener('DOMContentLoaded', function() {
        if (window.debugLog) {
            window.debugLog('GTranslate 통합 스크립트 로드됨...');
        }
        
        // GTranslate 위젯 존재 확인
        const gtWidget = document.querySelector('.gtranslate_wrapper');
        if (gtWidget) {
            if (window.debugLog) {
                window.debugLog('✓ GTranslate 위젯 발견');
            }
            
            // 위젯 내용 확인
            const widgetContent = gtWidget.innerHTML.trim();
            if (widgetContent === '' || widgetContent === '<?php echo do_shortcode('[gtranslate]'); ?>') {
                if (window.debugLog) {
                    window.debugLog('⚠ GTranslate 위젯이 비어있습니다.');
                }
                
                // 수동으로 간단한 언어 선택기 추가 (임시)
                const languages = {
                    'ko|ko': '🇰🇷 한국어',
                    'ko|en': '🇺🇸 English',
                    'ko|zh-CN': '🇨🇳 中文',
                    'ko|ja': '🇯🇵 日本語'
                };
                
                let selectHtml = '<select onchange="changeLanguage(this.value)" style="padding:8px 12px;border:1px solid #ddd;border-radius:8px;background:white;color:#333;cursor:pointer;">';
                selectHtml += '<option value="">언어 선택 / Language</option>';
                for (let code in languages) {
                    selectHtml += `<option value="${code}">${languages[code]}</option>`;
                }
                selectHtml += '</select>';
                
                gtWidget.innerHTML = selectHtml;
                if (window.debugLog) {
                    window.debugLog('✓ 임시 언어 선택기 추가됨');
                }
            }
        } else {
            if (window.debugLog) {
                window.debugLog('✗ GTranslate 위젯을 찾을 수 없음');
            }
        }
        
        // Google Translate 요소 확인
        setTimeout(function() {
            if (typeof google !== 'undefined' && google.translate) {
                if (window.debugLog) {
                    window.debugLog('✓ Google Translate API 로드됨');
                }
            } else {
                if (window.debugLog) {
                    window.debugLog('⚠ Google Translate API가 아직 로드되지 않음');
                }
            }
        }, 1000);
    });
    
    // 언어 변경 함수
    window.changeLanguage = function(langPair) {
        if (!langPair) return;
        
        // doGTranslate 함수가 있으면 사용
        if (typeof doGTranslate === 'function') {
            doGTranslate(langPair);
        } else {
            // 없으면 직접 구현
            const langs = langPair.split('|');
            const from = langs[0];
            const to = langs[1];
            
            if (window.debugLog) {
                window.debugLog(`언어 변경: ${from} → ${to}`);
            }
            
            // Google Translate 쿠키 설정
            document.cookie = `googtrans=/auto/${to}; path=/`;
            document.cookie = `googtrans=/auto/${to}; path=/; domain=.${location.hostname}`;
            
            // 페이지 새로고침
            location.reload();
        }
    };
    </script>
    <?php
}

// 리뷰 시스템과 GTranslate 통합 개선
add_filter('script_loader_tag', 'defer_review_system_script', 10, 3);
function defer_review_system_script($tag, $handle, $src) {
    // 리뷰 시스템 스크립트를 GTranslate 후에 로드
    if ('sungsuya-review-system' === $handle) {
        return str_replace(' src', ' defer src', $tag);
    }
    return $tag;
}

// GTranslate 플러그인 활성화 체크
add_action('admin_notices', 'check_gtranslate_plugin');
function check_gtranslate_plugin() {
    if (!is_plugin_active('gtranslate/gtranslate.php')) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><strong>성수야 V2:</strong> GTranslate 플러그인이 비활성화되어 있습니다. 다국어 지원을 위해 GTranslate 플러그인을 활성화해주세요.</p>
        </div>
        <?php
    }
}
