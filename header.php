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
</head>
<body <?php body_class(); ?>>

<!-- Global Header -->

<header class="site-header" id="header">
    <div class="header-container">
        <!-- Logo -->
        <a href="<?php echo esc_url(home_url('/')); ?>" class="logo">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo-sungsuya-v6.svg" alt="성수야! SUNGSUYA" width="300" height="70">
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
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo-sungsuya-v6.svg" alt="성수야! SUNGSUYA" width="300" height="70">
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
