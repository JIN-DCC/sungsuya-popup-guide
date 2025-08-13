<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0A0A0A">
    
    <!-- PWA Support -->
    <link rel="manifest" href="<?php echo get_template_directory_uri(); ?>/pwa/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="성수야!">
    <link rel="apple-touch-icon" href="<?php echo get_template_directory_uri(); ?>/assets/images/icon-192.png">
    
    <!-- 폰트 Preload (로컬 폰트 사용) -->
    <link rel="preload" href="<?php echo get_template_directory_uri(); ?>/assets/fonts/pretendard-regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?php echo get_template_directory_uri(); ?>/assets/fonts/pretendard-bold.woff2" as="font" type="font/woff2" crossorigin>
    
    <!-- 비동기 CSS 로딩을 위한 스크립트 -->
    <script>
    !function(e){"use strict";e.loadCSS||(e.loadCSS=function(){});var t=loadCSS.relpreload={};if(t.support=function(){var t;try{t=e.document.createElement("link").relList.supports("preload")}catch(e){t=!1}return function(){return t}}(),t.bindMediaToggle=function(e){function t(){e.media=a}var a=e.media||"all";e.addEventListener?e.addEventListener("load",t):e.attachEvent&&e.attachEvent("onload",t),setTimeout(function(){e.rel="stylesheet",e.media="only x"}),setTimeout(t,3e3)},!t.support()){var a=e.document.getElementsByTagName("link");for(var n=0;n<a.length;n++){var r=a[n];"preload"!==r.rel||"style"!==r.getAttribute("as")||r.getAttribute("data-loadcss")||(r.setAttribute("data-loadcss",!0),t.bindMediaToggle(r))}}}(window);
    </script>
    
    <?php wp_head(); ?>

    <!-- 모바일 스크롤 긴급 수정 -->
<style>
@media (max-width: 1024px) {
    html {
        overflow-y: scroll !important;
        overflow-x: hidden !important;
        height: 100% !important;
        position: static !important;
    }
    
    body {
        overflow: visible !important;
        height: auto !important;
        min-height: 100% !important;
        position: relative !important;
        -webkit-overflow-scrolling: touch !important;
    }
    
    #page, .site, main, .site-main, .content-area {
        overflow: visible !important;
        height: auto !important;
        position: relative !important;
    }
    
    * {
        transform: none !important;
    }
}
</style>

<script>
if (window.innerWidth <= 1024) {
    document.body.style.overflow = 'visible';
    document.body.style.height = 'auto';
    document.body.style.position = 'relative';
    document.documentElement.style.overflowY = 'scroll';
}
</script>
    
    <!-- Service Worker Registration -->
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('<?php echo get_template_directory_uri(); ?>/pwa/sw.js')
                .then(function(registration) {
                    if (window.debugLog) {
                        window.debugLog('ServiceWorker registration successful');
                    }
                })
                .catch(function(err) {
                    console.error('ServiceWorker registration failed: ', err);
                });
        });
    }
    </script>
    
    <!-- Modern Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <!-- Pretendard는 로컬 폰트 사용 -->
    
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
    
    <style>
        :root {
            /* Modern Color Palette */
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --neon-pink: #FF006E;
            --neon-blue: #3A86FF;
            --neon-purple: #8338EC;
            --neon-yellow: #FFBE0B;
            --dark-bg: #0A0A0A;
            --dark-surface: #1A1A1A;
            --dark-border: #2A2A2A;
            --light-text: #FFFFFF;
            --muted-text: #A0A0A0;
            
            /* Typography */
            --font-display: 'Bebas Neue', sans-serif;
            --font-body: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif;
            
            /* Spacing */
            --header-height: 80px;
            --header-height-mobile: 60px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-body);
            background: var(--dark-bg);
            color: var(--light-text);
            overflow-x: hidden;
        }
        
        a {
            color: inherit;
            text-decoration: none;
        }
        
        button {
            background: none;
            border: none;
            cursor: pointer;
            color: inherit;
        }
        
        /* Remove only specific unwanted elements */
        .high-contrast-toggle,
        .accessibility-toggle {
            display: none !important;
        }
        

        
        /* Header Styles */
        .site-header {
            position: relative; /* Changed from fixed to relative */
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: rgba(10, 10, 10, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1000;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .site-header.scrolled {
            background: rgba(10, 10, 10, 0.98);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        
        .header-container {
            max-width: 1400px;
            height: 100%;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        /* Logo Design */
        .logo {
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .logo:hover {
            opacity: 0.8;
            transform: translateY(-1px);
        }
        
        .logo img {
            height: 55px;
            width: auto;
            max-width: 300px;
            display: block;
        }
        
        /* Mobile Logo Adjustment */
        .mobile-menu-header .logo img {
            height: 35px;
        }
        
        /* Navigation */
        .main-nav {
            display: flex;
            align-items: center;
            gap: 40px;
        }
        
        .nav-link {
            font-size: 16px !important;
            font-weight: 600 !important;
            color: #FFFFFF !important;
            position: relative;
            transition: all 0.3s ease;
            padding: 8px 0;
        }
        
        .nav-link span {
            color: #FFFFFF !important;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-gradient);
            transition: width 0.3s ease;
        }
        
        .nav-link:hover {
            color: #FFFFFF;
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        .nav-link.special {
            background: var(--primary-gradient);
            color: white;
            padding: 10px 24px;
            border-radius: 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .nav-link.special:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .nav-link.special::after {
            display: none;
        }
        
        /* Login Button */
        .nav-link.login-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px;
            border-radius: 24px;
            transition: all 0.3s ease;
            color: #2C2C2C !important; /* 검은색 텍스트 */
        }
        
        .nav-link.login-btn span {
            color: #2C2C2C !important; /* 검은색 텍스트 */
        }
        
        .nav-link.login-btn i,
        .nav-link.login-btn svg {
            stroke: #2C2C2C !important; /* 검은색 아이콘 */
            color: #2C2C2C !important;
        }
        
        .nav-link.login-btn:hover {
            background: rgba(255, 255, 255, 1);
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .nav-link.login-btn::after {
            display: none;
        }
        
        /* User Menu */
        .user-menu {
            position: relative;
        }
        
        .user-menu-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 24px;
            font-size: 14px;
            font-weight: 600;
            color: #FFFFFF;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .user-menu-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: var(--dark-surface);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            z-index: 100;
        }
        
        .user-menu.active .user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #FFFFFF;
            transition: all 0.3s ease;
        }
        
        .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.1);
            padding-left: 24px;
        }
        
        .dropdown-item:first-child {
            border-radius: 12px 12px 0 0;
        }
        
        .dropdown-item:last-child {
            border-radius: 0 0 12px 12px;
        }
        
        .dropdown-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 8px 0;
        }
        
        /* GTranslate Wrapper */
        .gtranslate-wrapper {
            margin-left: 20px;
        }
        
        /* GTranslate Custom Styling */
        .gtranslate_wrapper {
            display: inline-block !important;
        }
        
        /* GTranslate 기본 스타일 - 헤더가 투명할 때 */
        .gtranslate_wrapper select,
        .gtranslate_wrapper .gt_switcher {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 2px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 25px !important;
            color: #FFFFFF !important;
            padding: 8px 20px !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
        }
        
        .gtranslate_wrapper select:hover,
        .gtranslate_wrapper .gt_switcher:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.5) !important;
        }
        
        /* 스크롤 시 헤더가 불투명해질 때 */
        .site-header.scrolled .gtranslate_wrapper select,
        .site-header.scrolled .gtranslate_wrapper .gt_switcher {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 2px solid rgba(255, 255, 255, 0.5) !important;
            color: #2C2C2C !important; /* 검정색 텍스트 */
        }
        
        .site-header.scrolled .gtranslate_wrapper select:hover,
        .site-header.scrolled .gtranslate_wrapper .gt_switcher:hover {
            background: #FFFFFF !important;
            border-color: #667eea !important;
        }
        
        /* 드롭다운 옵션 스타일 */
        .gtranslate_wrapper select option {
            background: #FFFFFF !important;
            color: #2C2C2C !important;
        }
        
        /* Style GTranslate flags/text */
        .gtranslate_wrapper img {
            margin-right: 5px;
            vertical-align: middle;
        }
        
        .gtranslate_wrapper a {
            color: #FFFFFF !important;
            text-decoration: none !important;
            padding: 8px 16px;
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .gtranslate_wrapper a:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        /* 스크롤 시 링크 스타일 */
        .site-header.scrolled .gtranslate_wrapper a {
            background: rgba(255, 255, 255, 0.95);
            color: #2C2C2C !important;
        }
        
        .site-header.scrolled .gtranslate_wrapper a:hover {
            background: #FFFFFF;
            border-color: #667eea;
        }
        
        /* Additional GTranslate styles for different widget types */
        .gt_switcher_wrapper {
            display: inline-block !important;
        }
        
        .gt_float_switcher {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 2px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 25px !important;
            padding: 8px 16px !important;
        }
        
        .gt_float_switcher:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.5) !important;
        }
        
        /* 스크롤 시 플로팅 스위처 스타일 */
        .site-header.scrolled .gt_float_switcher {
            background: rgba(255, 255, 255, 0.95) !important;
            color: #2C2C2C !important;
        }
        
        .site-header.scrolled .gt_float_switcher:hover {
            background: #FFFFFF !important;
            border-color: #667eea !important;
        }
        
        /* GTranslate inline styles */
        .gtranslate_wrapper .gt-current-lang {
            color: #FFFFFF !important;
        }
        
        .site-header.scrolled .gtranslate_wrapper .gt-current-lang {
            color: #2C2C2C !important;
        }
        
        /* If GTranslate uses a simple div structure */
        .gtranslate-wrapper > div {
            display: inline-block;
        }
        
        /* Override inline styles */
        [class*="gtranslate"] select {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 2px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 25px !important;
            color: #FFFFFF !important;
            padding: 8px 20px !important;
        }
        
        /* 스크롤 시 모든 gtranslate 요소 */
        .site-header.scrolled [class*="gtranslate"] select {
            background: rgba(255, 255, 255, 0.95) !important;
            color: #2C2C2C !important;
        }
        
        .site-header.scrolled [class*="gtranslate"] select:hover {
            background: #FFFFFF !important;
            border-color: #667eea !important;
        }
        
        /* GTranslate 드롭다운 화살표 아이콘 */
        .gtranslate_wrapper select,
        [class*="gtranslate"] select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            padding-right: 40px !important;
        }
        
        /* 스크롤 시 화살표 색상 변경 */
        .site-header.scrolled .gtranslate_wrapper select,
        .site-header.scrolled [class*="gtranslate"] select {
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232C2C2C' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        }
        
        /* Mobile Menu Button */
        .menu-btn {
            display: none;
            width: 48px;
            height: 48px;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            flex-direction: column;
        }
        
        /* 햄버거 메뉴 3개 라인 */
        .menu-btn span,
        .menu-btn span::before,
        .menu-btn span::after {
            display: block;
            position: absolute;
            width: 24px;
            height: 3px;
            background: #2C2C2C;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .menu-btn span {
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }
        
        .menu-btn span::before {
            content: '';
            position: absolute;
            top: -8px;
            left: 0;
        }
        
        .menu-btn span::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
        }
        
        .menu-btn:hover {
            background: #FFFFFF;
            transform: scale(1.05);
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .menu-btn:hover span::before {
            transform: translateY(-2px);
        }
        
        .menu-btn:hover span::after {
            transform: translateY(2px);
        }
        
        /* 기존 SVG 아이콘 숨기기 */
        .menu-btn svg,
        .menu-btn i {
            display: none !important;
        }
        
        /* Mobile Menu */
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 100%;
            max-width: 400px;
            height: 100vh;
            background: var(--dark-surface);
            z-index: 1001;
            transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
        }
        
        .mobile-menu.active {
            right: 0;
        }
        
        .mobile-menu-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .mobile-menu-close {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .mobile-menu-close:hover {
            background: var(--neon-pink);
            border-color: var(--neon-pink);
        }
        
        .mobile-nav {
            padding: 20px;
        }
        
        .mobile-nav-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 0;
            font-size: 18px;
            font-weight: 500;
            color: #FFFFFF;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .mobile-nav-link:hover {
            color: var(--neon-blue);
            padding-left: 10px;
        }
        
        .mobile-nav-link.active {
            color: var(--neon-blue);
            background: rgba(58, 134, 255, 0.1);
            border-left: 3px solid var(--neon-blue);
            padding-left: 17px;
        }
        
        .mobile-nav-link svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            transition: transform 0.3s ease;
        }
        
        .mobile-nav-link:hover svg {
            transform: translateX(5px);
        }
        
        /* Mobile GTranslate */
        .mobile-gtranslate {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .mobile-gtranslate .gtranslate_wrapper {
            display: block !important;
            text-align: center;
        }
        
        .mobile-gtranslate .gtranslate_wrapper a,
        .mobile-gtranslate .gtranslate_wrapper select {
            width: 100% !important;
            max-width: 200px !important;
            margin: 0 auto !important;
        }
        
        /* Mobile User Section */
        .mobile-user-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .mobile-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 0;
            color: #FFFFFF;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .mobile-nav-link.login,
        .mobile-nav-link.logout {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 20px;
            padding-top: 20px;
        }
        
        /* 모바일 로그인 버튼 스타일 */
        button.mobile-nav-link.login {
            width: 100%;
            text-align: left;
            background: rgba(255, 255, 255, 0.9);
            color: #2C2C2C !important;
            margin: 20px 0;
            border-radius: 12px;
            padding: 15px 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            font-size: 16px;
            font-weight: 600;
        }
        
        button.mobile-nav-link.login:hover {
            background: #FFFFFF;
            border-color: #667eea;
        }
        
        button.mobile-nav-link.login span {
            color: #2C2C2C !important;
        }
        
        button.mobile-nav-link.login i,
        button.mobile-nav-link.login svg {
            stroke: #2C2C2C !important;
            color: #2C2C2C !important;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .site-header {
                height: var(--header-height-mobile);
            }
            
            .header-container {
                padding: 0 20px;
            }
            
            .logo {
                font-size: 24px;
            }
            
            .main-nav,
            .gtranslate-wrapper,
            .user-menu,
            .nav-link.login-btn {
                display: none;
            }
            
            .menu-btn {
                display: flex;
            }
            
            body {
                padding-top: var(--header-height-mobile);
            }
        }
        
        /* Body padding for fixed header */
        /* Remove body padding as it was causing issues */
        /*body {
            padding-top: var(--header-height);
        }*/
        
        /* Overlay */
        .menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
        }
        
        .menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>
<body <?php body_class(); ?>>

<!-- Global Header -->

<header class="site-header" id="header">
    <div class="header-container">
        <!-- Logo -->
        <a href="<?php echo esc_url(home_url('/')); ?>" class="logo">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo-sungsuya-v5.svg" alt="성수야! SUNGSUYA" width="300" height="70">
        </a>
        
        <!-- Navigation -->
        <nav class="main-nav">
            <?php 
            // 현재 페이지 URL 및 타입 확인
            $current_url = $_SERVER['REQUEST_URI'];
            $is_popup_store = strpos($current_url, '/popup-stores') !== false;
            $is_places = is_post_type_archive('places') || is_singular('places');
            $is_tour = strpos($current_url, '/tour-v2') !== false || strpos($current_url, '/tour-pwa') !== false;
            ?>
            <a href="<?php echo esc_url(home_url('/popup-stores')); ?>" class="nav-link <?php echo $is_popup_store ? 'special' : ''; ?>">
                <span>팝업스토어</span>
            </a>
            <a href="<?php echo esc_url(home_url('/places')); ?>" class="nav-link <?php echo ($is_places && !$is_popup_store) ? 'special' : ''; ?>">
                <span>장소</span>
            </a>
            <a href="<?php echo esc_url(home_url('/tour-v2')); ?>" class="nav-link <?php echo $is_tour ? 'special' : ''; ?>">
                <span>투어플래너</span>
            </a>
            
            <!-- Login Button -->
            <?php if (is_user_logged_in()) : ?>
                <?php $current_user = wp_get_current_user(); ?>
                <div class="user-menu">
                    <button class="user-menu-toggle">
                        <i data-feather="user"></i>
                        <span><?php echo esc_html($current_user->display_name); ?></span>
                        <i data-feather="chevron-down"></i>
                    </button>
                    <div class="user-dropdown">
                        <a href="<?php echo esc_url(home_url('/my-account')); ?>" class="dropdown-item">
                            <i data-feather="user"></i>
                            <span>내 정보</span>
                        </a>
                        <a href="<?php echo esc_url(home_url('/my-tours')); ?>" class="dropdown-item">
                            <i data-feather="map"></i>
                            <span>내 투어</span>
                        </a>
                        <a href="<?php echo esc_url(home_url('/my-reviews')); ?>" class="dropdown-item">
                            <i data-feather="star"></i>
                            <span>내 리뷰</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="dropdown-item">
                            <i data-feather="log-out"></i>
                            <span>로그아웃</span>
                        </a>
                    </div>
                </div>
            <?php else : ?>
                <button class="nav-link login-btn" onclick="if(window.sungsuyaAuth) { window.sungsuyaAuth.showAuthModal(); } else { alert('로그인 시스템을 준비중입니다.'); }">
                    <i data-feather="user"></i>
                    <span>로그인</span>
                </button>
            <?php endif; ?>
        </nav>
        
        <!-- GTranslate Widget -->
        <?php 
        // GTranslate 플러그인이 작동하지 않을 경우 수동 위젯 사용
        $gtranslate_output = do_shortcode('[gtranslate]');
        if (empty(trim($gtranslate_output))) {
            // 수동 위젯 포함
            include_once(get_template_directory() . '/inc/gtranslate-manual.php');
        } else {
            echo '<div class="gtranslate-wrapper">' . $gtranslate_output . '</div>';
        }
        ?>
        
        <!-- Mobile Menu Button -->
        <button class="menu-btn" aria-label="Menu">
            <span></span>
        </button>
    </div>
</header>

<!-- Menu Overlay -->
<div class="menu-overlay"></div>

<!-- Mobile Menu -->
<div class="mobile-menu">
    <div class="mobile-menu-header">
        <div class="logo">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo-sungsuya-v5.svg" alt="성수야! SUNGSUYA" width="300" height="70">
        </div>
        <button class="mobile-menu-close">
            <i data-feather="x"></i>
        </button>
    </div>
    
    <nav class="mobile-nav">
        <a href="<?php echo esc_url(home_url('/popup-stores')); ?>" class="mobile-nav-link <?php echo $is_popup_store ? 'active' : ''; ?>">
            <span>팝업스토어</span>
            <i data-feather="arrow-right"></i>
        </a>
        <a href="<?php echo esc_url(home_url('/places')); ?>" class="mobile-nav-link <?php echo ($is_places && !$is_popup_store) ? 'active' : ''; ?>">
            <span>장소</span>
            <i data-feather="arrow-right"></i>
        </a>
        <a href="<?php echo esc_url(home_url('/tour-v2')); ?>" class="mobile-nav-link <?php echo $is_tour ? 'active' : ''; ?>">
            <span>투어플래너</span>
            <i data-feather="arrow-right"></i>
        </a>
        
        <!-- Login/User Menu in Mobile -->
        <?php if (is_user_logged_in()) : ?>
            <?php $current_user = wp_get_current_user(); ?>
            <div class="mobile-user-section">
                <div class="mobile-user-info">
                    <i data-feather="user"></i>
                    <span><?php echo esc_html($current_user->display_name); ?></span>
                </div>
                <a href="<?php echo esc_url(home_url('/my-account')); ?>" class="mobile-nav-link">
                    <span>내 정보</span>
                    <i data-feather="arrow-right"></i>
                </a>
                <a href="<?php echo esc_url(home_url('/my-tours')); ?>" class="mobile-nav-link">
                    <span>내 투어</span>
                    <i data-feather="arrow-right"></i>
                </a>
                <a href="<?php echo esc_url(home_url('/my-reviews')); ?>" class="mobile-nav-link">
                    <span>내 리뷰</span>
                    <i data-feather="arrow-right"></i>
                </a>
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="mobile-nav-link logout">
                    <span>로그아웃</span>
                    <i data-feather="log-out"></i>
                </a>
            </div>
        <?php else : ?>
            <button class="mobile-nav-link login" onclick="if(window.sungsuyaAuth) { window.sungsuyaAuth.showAuthModal(); } else { alert('로그인 시스템을 준비중입니다.'); }">
                <span>로그인</span>
                <i data-feather="user"></i>
            </button>
        <?php endif; ?>
        
        <!-- GTranslate in Mobile -->
        <?php 
        $gtranslate_output_mobile = do_shortcode('[gtranslate]');
        if (empty(trim($gtranslate_output_mobile))) {
            echo '<div class="mobile-gtranslate">';
            include_once(get_template_directory() . '/inc/gtranslate-manual.php');
            echo '</div>';
        } else {
            echo '<div class="mobile-gtranslate">' . $gtranslate_output_mobile . '</div>';
        }
        ?>
    </nav>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Feather icons with debounce
    let featherTimeout;
    function initFeather() {
        clearTimeout(featherTimeout);
        featherTimeout = setTimeout(() => {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }, 100);
    }
    initFeather();
    
    const header = document.querySelector('.site-header');
    const menuBtn = document.querySelector('.menu-btn');
    const mobileMenu = document.querySelector('.mobile-menu');
    const menuClose = document.querySelector('.mobile-menu-close');
    const overlay = document.querySelector('.menu-overlay');
    
    // Header scroll effect
    let lastScroll = 0;
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
    });
    
    // Mobile menu toggle
    function toggleMenu() {
        mobileMenu.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
    }
    
    menuBtn.addEventListener('click', toggleMenu);
    menuClose.addEventListener('click', toggleMenu);
    overlay.addEventListener('click', toggleMenu);
    
    // Close mobile menu on link click
    const mobileLinks = mobileMenu.querySelectorAll('.mobile-nav-link');
    mobileLinks.forEach(link => {
        link.addEventListener('click', toggleMenu);
    });
    
    // User menu toggle
    const userMenu = document.querySelector('.user-menu');
    const userMenuToggle = document.querySelector('.user-menu-toggle');
    
    if (userMenuToggle) {
        userMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('active');
        });
        
        // Close user menu when clicking outside
        document.addEventListener('click', function() {
            if (userMenu) {
                userMenu.classList.remove('active');
            }
        });
    }
});
</script>
