<?php
/**
 * 관리자 메뉴 정리 - 중복 제거
 * 
 * @package SungsuyaV2
 * @since 2025.06.27
 */

// 메뉴 중복 제거 및 정리
add_action('admin_menu', 'sungsuya_cleanup_admin_menus', 999);

function sungsuya_cleanup_admin_menus() {
    // 1. 장소(Places) 메뉴에서 중복 제거
    remove_submenu_page('edit.php?post_type=places', 'places-thumbnail-manager');
    remove_submenu_page('edit.php?post_type=places', 'crawling-api-settings');
    // remove_submenu_page('edit.php?post_type=places', 'bulk-crawling-system'); // 구버전 제거 -> 이제 사용함
    
    // 2. 팝업스토어 post type 메뉴 완전 제거 (성수야! 관리로 통합)
    remove_menu_page('edit.php?post_type=popup_store');
    
    // 3. 사용하지 않는 일반 포스트 메뉴 제거
    remove_menu_page('edit.php');
    
    // 4. 콘텐츠 관리 서브메뉴 정리 (중복 제거)
    global $submenu;
    if (isset($submenu['admin.php?page=sungsuya-content'])) {
        foreach ($submenu['admin.php?page=sungsuya-content'] as $key => $item) {
            if ($item[2] == 'admin.php?page=image-crawling') {
                unset($submenu['admin.php?page=sungsuya-content'][$key]);
            }
        }
    }
    
    // 5. 팝업 크롤링 메뉴를 제거하고 대량크롤링을 그 위치로 이동
    remove_submenu_page('edit.php?post_type=places', 'popup-crawling');
    
    // 6. 메뉴 순서 재정렬
    global $submenu;
    if (isset($submenu['edit.php?post_type=places'])) {
        // 대량크롤링 메뉴를 찾아서 위로 이동
        $bulk_crawling_menu = null;
        $bulk_crawling_key = null;
        
        foreach ($submenu['edit.php?post_type=places'] as $key => $item) {
            if ($item[2] == 'bulk-crawling-system') {
                $bulk_crawling_menu = $item;
                $bulk_crawling_key = $key;
                break;
            }
        }
        
        if ($bulk_crawling_menu && $bulk_crawling_key) {
            // 기존 위치에서 제거
            unset($submenu['edit.php?post_type=places'][$bulk_crawling_key]);
            
            // 새 위치에 삽입 (데이터 마이그레이션 다음)
            $new_submenu = array();
            foreach ($submenu['edit.php?post_type=places'] as $key => $item) {
                $new_submenu[] = $item;
                if ($item[2] == 'sungsuya-migration') {
                    $new_submenu[] = $bulk_crawling_menu;
                }
            }
            $submenu['edit.php?post_type=places'] = $new_submenu;
        }
    }
}

// 메뉴 항상 열려있게 유지하는 JavaScript 추가
add_action('admin_footer', 'sungsuya_keep_menus_open');

function sungsuya_keep_menus_open() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // WordPress 기본 메뉴 동작 방지 및 커스텀 동작 추가
        
        // 1. 모든 메뉴 토글 동작 방지
        $('#adminmenu .wp-has-submenu').off('click.wp-responsive');
        
        // 2. 성수야! 관리와 장소 메뉴 항상 열기
        function keepMenusOpen() {
            $('#toplevel_page_admin-page-image-crawling').addClass('wp-has-current-submenu wp-menu-open').removeClass('wp-not-current-submenu');
            $('#toplevel_page_admin-page-image-crawling .wp-submenu').show().css('display', 'block');
            
            $('#menu-posts-places').addClass('wp-menu-open');
            $('#menu-posts-places .wp-submenu').show().css('display', 'block');
        }
        
        // 3. 초기 실행
        keepMenusOpen();
        
        // 4. 메뉴 클릭 시 다시 열기
        $('#adminmenu > li.menu-top').on('click', function(e) {
            // 기본 동작 방지
            e.stopPropagation();
            
            var $this = $(this);
            var $submenu = $this.find('.wp-submenu');
            
            // 현재 메뉴 토글
            if ($submenu.length) {
                if ($this.hasClass('wp-menu-open')) {
                    // 이미 열려있으면 닫기 (선택사항)
                    // $this.removeClass('wp-menu-open');
                    // $submenu.hide();
                } else {
                    // 닫혀있으면 열기
                    $this.addClass('wp-menu-open');
                    $submenu.show();
                }
            }
            
            // 성수야! 관리와 장소 메뉴는 항상 열려있게
            setTimeout(keepMenusOpen, 10);
            
            // 메뉴 링크가 있으면 이동
            var $link = $this.find('> a');
            if ($link.length && $link.attr('href') && $link.attr('href') !== '#') {
                window.location.href = $link.attr('href');
            }
        });
        
        // 5. 서브메뉴 클릭은 정상 동작
        $('#adminmenu .wp-submenu a').on('click', function(e) {
            e.stopPropagation();
        });
        
        // 6. 페이지 로드 시 현재 페이지에 따라 메뉴 활성화
        var currentUrl = window.location.href;
        
        // 성수야! 관리 메뉴 활성화
        if (currentUrl.indexOf('page=image-crawling') > -1 || 
            currentUrl.indexOf('page=sungsuya-') > -1 ||
            currentUrl.indexOf('page=popup-') > -1 ||
            currentUrl.indexOf('page=api-settings') > -1 ||
            currentUrl.indexOf('page=thumbnail-manager') > -1) {
            
            $('#toplevel_page_admin-page-image-crawling').addClass('wp-has-current-submenu');
        }
        
        // 7. 반응형 모드에서도 메뉴 유지
        $(window).on('wp-responsive-activate wp-responsive-deactivate', function() {
            setTimeout(keepMenusOpen, 100);
        });
        
        // 8. WordPress 메뉴 토글 함수 오버라이드
        if (typeof window.wpMenuToggle !== 'undefined') {
            var originalToggle = window.wpMenuToggle;
            window.wpMenuToggle = function(el) {
                // 원래 함수 실행
                originalToggle.call(this, el);
                // 우리 메뉴는 다시 열기
                setTimeout(keepMenusOpen, 50);
            };
        }
    });
    </script>
    
    <style>
    /* 메뉴가 항상 보이도록 CSS 추가 */
    #toplevel_page_admin-page-image-crawling.wp-menu-open .wp-submenu,
    #menu-posts-places.wp-menu-open .wp-submenu {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* 호버 시에도 다른 메뉴가 닫히지 않도록 */
    #adminmenu li.wp-menu-open .wp-submenu {
        display: block !important;
    }
    </style>
    <?php
}

// 메뉴 이름 개선
add_action('admin_menu', 'sungsuya_rename_menu_items', 1000);

function sungsuya_rename_menu_items() {
    global $menu, $submenu;
    
    // 페이지 메뉴 이름 변경 (이모지 제거)
    foreach ($menu as $key => $item) {
        if ($item[2] == 'edit.php?post_type=page') {
            $menu[$key][0] = '페이지';
        }
    }
}

// 팝업스토어 관리 빈 페이지 리다이렉트
add_action('admin_init', 'sungsuya_redirect_empty_pages');

function sungsuya_redirect_empty_pages() {
    if (isset($_GET['page']) && $_GET['page'] == 'sungsuya-popup') {
        // 팝업스토어 대시보드로 리다이렉트
        wp_redirect(admin_url('admin.php?page=popup-store-dashboard'));
        exit;
    }
}

// 팝업스토어 대시보드 페이지 생성 (없을 경우)
add_action('admin_menu', 'sungsuya_create_popup_dashboard', 5);

function sungsuya_create_popup_dashboard() {
    // 팝업스토어 대시보드가 없으면 생성
    add_submenu_page(
        'admin.php?page=sungsuya-popup',
        '팝업스토어 대시보드',
        '팝업스토어 대시보드',
        'manage_options',
        'popup-store-dashboard',
        'sungsuya_popup_dashboard_page'
    );
}

function sungsuya_popup_dashboard_page() {
    ?>
    <div class="wrap">
        <h1>팝업스토어 관리</h1>
        
        <div class="sungsuya-dashboard-grid">
            <div class="dashboard-card">
                <h3>팝업스토어 현황</h3>
                <?php
                $popup_count = wp_count_posts('places')->publish;
                $popup_stores = get_posts([
                    'post_type' => 'places',
                    'meta_key' => 'place_type',
                    'meta_value' => 'popup_store',
                    'posts_per_page' => -1
                ]);
                ?>
                <p>전체 팝업스토어: <strong><?php echo count($popup_stores); ?>개</strong></p>
            </div>
            
            <div class="dashboard-card">
                <h3>빠른 작업</h3>
                <p><a href="<?php echo admin_url('admin.php?page=popup-crawling'); ?>" class="button">팝업 크롤링</a></p>
                <p><a href="<?php echo admin_url('admin.php?page=popup-csv-upload'); ?>" class="button">CSV 업로드</a></p>
                <p><a href="<?php echo admin_url('admin.php?page=popup-period-manager'); ?>" class="button">기간 관리</a></p>
            </div>
        </div>
    </div>
    
    <style>
    .sungsuya-dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .dashboard-card {
        background: #fff;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        padding: 20px;
    }
    .dashboard-card h3 {
        margin-top: 0;
    }
    </style>
    <?php
}
