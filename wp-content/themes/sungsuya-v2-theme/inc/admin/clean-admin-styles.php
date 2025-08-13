<?php
/**
 * 관리자 스타일 개선
 * 
 * @package SungsuyaV2
 * @since 2025.06.27
 */

// 관리자 CSS 추가
add_action('admin_head', 'sungsuya_admin_clean_styles');

function sungsuya_admin_clean_styles() {
    ?>
    <style>
    /* 전체적인 스타일 정리 */
    
    /* 이모지 크기 조절 및 간격 - GTranslate 플러그인 제외 */
    .wp-menu-name img,
    .wp-submenu img,
    h1 img,
    h2 img,
    h3 img {
        width: 16px !important;
        height: 16px !important;
        margin-right: 5px;
        vertical-align: middle;
    }
    
    /* GTranslate 플러그인의 국기 이미지는 원래 크기 유지 */
    #alternate_flags img,
    .postbox-container img[src*="flag"],
    .postbox-container img[src*="gtranslate"] {
        width: auto !important;
        height: auto !important;
    }
    
    /* 테이블 가독성 개선 */
    .wp-list-table td,
    .wp-list-table th {
        padding: 10px;
        vertical-align: middle;
    }
    
    .wp-list-table .column-featured_image {
        width: 60px;
    }
    
    .wp-list-table .column-featured_image img {
        max-width: 50px;
        height: auto;
        border-radius: 4px;
    }
    
    /* 상태 표시 개선 */
    .status-operating {
        color: #46b450;
        font-weight: 500;
    }
    
    .status-closed {
        color: #dc3232;
        font-weight: 500;
    }
    
    .status-preparing {
        color: #ffb900;
        font-weight: 500;
    }
    
    /* 버튼 스타일 통일 */
    .button,
    .button-primary,
    .button-secondary {
        border-radius: 3px;
        padding: 6px 12px;
        font-size: 13px;
        line-height: 1.5;
    }
    
    /* 대시보드 카드 스타일 */
    .dashboard-card {
        background: #fff;
        border: 1px solid #e1e1e1;
        border-radius: 4px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    
    .dashboard-card h3 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 16px;
        font-weight: 600;
        color: #23282d;
    }
    
    /* 통계 박스 */
    .stat-box {
        text-align: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 4px;
        margin: 10px 0;
    }
    
    .stat-box .stat-number {
        font-size: 32px;
        font-weight: 600;
        color: #2271b1;
        display: block;
        margin-bottom: 5px;
    }
    
    .stat-box .stat-label {
        font-size: 14px;
        color: #666;
    }
    
    /* 진행률 바 */
    .progress-bar {
        width: 100%;
        height: 8px;
        background: #e0e0e0;
        border-radius: 4px;
        overflow: hidden;
        margin: 10px 0;
    }
    
    .progress-bar .progress {
        height: 100%;
        background: #2271b1;
        transition: width 0.3s ease;
    }
    
    /* 리스트 정리 */
    .sungsuya-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .sungsuya-list li {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
    
    .sungsuya-list li:last-child {
        border-bottom: none;
    }
    
    /* 빈 상태 메시지 */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #666;
    }
    
    .empty-state .dashicons {
        font-size: 48px;
        width: 48px;
        height: 48px;
        margin-bottom: 10px;
        color: #ddd;
    }
    
    /* 섹션 구분선 */
    .section-divider {
        border-top: 1px solid #e1e1e1;
        margin: 30px 0;
        padding-top: 30px;
    }
    
    /* 액션 링크 정리 */
    .row-actions {
        font-size: 12px;
    }
    
    .row-actions .trash a {
        color: #a00;
    }
    
    .row-actions .trash a:hover {
        color: #dc3232;
    }
    
    /* 폼 요소 정리 */
    input[type="text"],
    input[type="email"],
    input[type="url"],
    input[type="search"],
    textarea,
    select {
        border-radius: 3px;
        border-color: #8c8f94;
    }
    
    /* 탭 스타일 */
    .nav-tab-wrapper {
        margin-bottom: 20px;
        border-bottom: 1px solid #ccc;
    }
    
    .nav-tab {
        margin-bottom: -1px;
        background: #f1f1f1;
        border-color: #ccc;
    }
    
    .nav-tab-active {
        background: #fff;
        border-bottom-color: #fff;
    }
    
    /* 도움말 텍스트 */
    .description,
    .help-text {
        font-size: 13px;
        font-style: normal;
        color: #666;
        margin-top: 5px;
    }
    
    /* 반응형 그리드 */
    .admin-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    
    /* 작은 화면 대응 */
    @media screen and (max-width: 782px) {
        .admin-grid {
            grid-template-columns: 1fr;
        }
        
        .stat-box {
            padding: 15px;
        }
        
        .stat-box .stat-number {
            font-size: 24px;
        }
    }
    
    /* 메뉴 아이콘 대체 (이모지 대신 Dashicons 사용) */
    #adminmenu .dashicons-before:before {
        font-size: 18px;
        margin-right: 6px;
    }
    
    /* 로딩 스피너 */
    .sungsuya-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #2271b1;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-left: 5px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>
    <?php
}

// Dashicons 아이콘으로 이모지 대체
add_action('admin_menu', 'sungsuya_replace_emoji_with_dashicons', 1001);

function sungsuya_replace_emoji_with_dashicons() {
    global $menu, $submenu;
    
    // 메인 메뉴 아이콘 매핑
    $icon_mapping = array(
        '🚀 성수야! 관리' => 'dashicons-location',
        '📄 페이지' => 'dashicons-admin-page',
        '장소' => 'dashicons-location-alt',
    );
    
    foreach ($menu as $key => $item) {
        foreach ($icon_mapping as $text => $icon) {
            if (strpos($item[0], $text) !== false) {
                $menu[$key][0] = str_replace(
                    array('🚀', '📄', '🗺️', '📝', '📊', '🎪', '📸', '⚙️'),
                    '',
                    $item[0]
                );
                $menu[$key][0] = trim($menu[$key][0]);
                if (empty($item[6])) {
                    $menu[$key][6] = $icon;
                }
            }
        }
    }
}
