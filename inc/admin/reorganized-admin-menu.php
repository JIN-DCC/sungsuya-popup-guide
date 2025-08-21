<?php
/**
 * 정리된 관리자 메뉴 시스템
 * 
 * 중복 제거 및 논리적 그룹핑
 * 
 * @package SungsuyaV2
 * @since 2.0.0
 */

// 기존 메뉴 제거 및 재구성
add_action('admin_menu', 'reorganize_sungsuya_admin_menu', 999);
function reorganize_sungsuya_admin_menu() {
    // 기존 중복 메뉴 제거
    remove_submenu_page('edit.php?post_type=places', 'popup-csv-upload'); // CSV 업로드는 성수야 관리에만
    
    // 메인 메뉴 추가
    add_menu_page(
        '성수야! 관리',
        '🚀 성수야! 관리',
        'manage_options',
        'sungsuya-admin',
        'sungsuya_admin_dashboard',
        'dashicons-location-alt',
        3
    );
    
    // 대시보드
    add_submenu_page(
        'sungsuya-admin',
        '대시보드',
        '📊 대시보드',
        'manage_options',
        'sungsuya-admin',
        'sungsuya_admin_dashboard'
    );
    
    // 팝업스토어 관리
    add_submenu_page(
        'sungsuya-admin',
        '팝업스토어 관리',
        '🎪 팝업스토어 관리',
        'manage_options',
        'sungsuya-popup',
        'sungsuya_popup_submenu_redirect'
    );
    
    add_submenu_page(
        'sungsuya-admin',
        '팝업 크롤링',
        '&nbsp;&nbsp;├ 팝업 크롤링',
        'manage_options',
        'popup-crawling',
        'sungsuya_popup_crawling_page'
    );
    
    add_submenu_page(
        'sungsuya-admin',
        'CSV 업로드',
        '&nbsp;&nbsp;├ CSV 업로드',
        'manage_options',
        'popup-csv-upload',
        'sungsuya_popup_csv_upload_page'
    );
    
    add_submenu_page(
        'sungsuya-admin',
        '기간 관리',
        '&nbsp;&nbsp;└ 기간 관리',
        'manage_options',
        'popup-period-manager',
        'sungsuya_popup_period_manager_page'
    );
    
    // 콘텐츠 관리
    add_submenu_page(
        'sungsuya-admin',
        '이미지 크롤링',
        '🖼️ 이미지 크롤링',
        'manage_options',
        'integrated-image-crawler',
        'sungsuya_integrated_image_crawler_page'
    );
    
    add_submenu_page(
        'sungsuya-admin',
        '썸네일 관리',
        '📷 썸네일 관리',
        'manage_options',
        'thumbnail-manager',
        'sungsuya_thumbnail_manager_page'
    );
    
    add_submenu_page(
        'sungsuya-admin',
        '상세정보 자동수집',
        '🤖 상세정보 자동수집',
        'manage_options',
        'place-detail-auto-collector',
        'sungsuya_place_detail_collector_page'
    );
    
    // 시스템 설정
    add_submenu_page(
        'sungsuya-admin',
        'API 설정',
        '⚙️ API 설정',
        'manage_options',
        'api-settings',
        'sungsuya_api_settings_page'
    );
}

// 대시보드 페이지
function sungsuya_admin_dashboard() {
    ?>
    <div class="wrap">
        <h1>🚀 성수야! 대시보드</h1>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
            <!-- 시스템 상태 -->
            <div class="card">
                <h2>📊 시스템 상태</h2>
                <?php
                $total_places = wp_count_posts('places')->publish;
                $popup_stores = get_posts(array(
                    'post_type' => 'places',
                    'meta_key' => 'place_type',
                    'meta_value' => 'popup_store',
                    'posts_per_page' => -1,
                    'fields' => 'ids'
                ));
                ?>
                <p>전체 장소: <strong><?php echo number_format($total_places); ?>개</strong></p>
                <p>팝업스토어: <strong><?php echo number_format(count($popup_stores)); ?>개</strong></p>
                <p>시스템 버전: <strong>2.1.0</strong></p>
            </div>
            
            <!-- 빠른 작업 -->
            <div class="card">
                <h2>⚡ 빠른 작업</h2>
                <p><a href="<?php echo admin_url('admin.php?page=enhanced-bulk-crawling'); ?>" class="button">일반 크롤링 시작</a></p>
                <p><a href="<?php echo admin_url('admin.php?page=popup-csv-upload'); ?>" class="button">CSV 업로드</a></p>
                <p><a href="<?php echo admin_url('admin.php?page=image-crawling'); ?>" class="button">이미지 크롤링</a></p>
                <p><a href="<?php echo home_url('/collect-real-data.php'); ?>" class="button" target="_blank">실제 데이터 수집</a></p>
            </div>
            
            <!-- 최근 활동 -->
            <div class="card">
                <h2>📅 최근 활동</h2>
                <?php
                $recent_places = get_posts(array(
                    'post_type' => 'places',
                    'posts_per_page' => 5,
                    'orderby' => 'date',
                    'order' => 'DESC'
                ));
                
                if ($recent_places) {
                    echo '<ul>';
                    foreach ($recent_places as $place) {
                        echo '<li><a href="' . get_edit_post_link($place->ID) . '">' . esc_html($place->post_title) . '</a> (' . human_time_diff(get_post_time('U', true, $place)) . ' 전)</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p>최근 추가된 장소가 없습니다.</p>';
                }
                ?>
            </div>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #f0f7ff; border-radius: 8px;">
            <h2>🔄 개발 현황</h2>
            <ul>
                <li>✅ 팝업 기간 관리 시스템 구현 완료</li>
                <li>✅ 이미지 크롤링 시스템 구현 완료</li>
                <li>✅ 관리자 메뉴 재구성 완료</li>
                <li>🔄 실제 데이터 수집 진행 중</li>
            </ul>
        </div>
    </div>
    <?php
}

// 서브메뉴 리다이렉트 함수들
function sungsuya_popup_submenu_redirect() {
    wp_redirect(admin_url('admin.php?page=popup-crawling'));
    exit;
}

function sungsuya_content_submenu_redirect() {
    wp_redirect(admin_url('admin.php?page=image-crawling'));
    exit;
}

// 팝업 크롤링 페이지
function sungsuya_popup_crawling_page() {
    // 팝업 크롤링 결과 관리 페이지 include
    $page_file = SUNGSUYA_THEME_DIR . '/admin/popup-crawl-results-page.php';
    if (file_exists($page_file)) {
        include $page_file;
    } else {
        ?>
        <div class="wrap">
            <h1>팝업스토어 크롤링</h1>
            <p>이 페이지는 팝업스토어 전용 크롤링 시스템입니다.</p>
            <p>크롤링 스케줄러가 매일 00:30에 자동으로 실행됩니다.</p>
            
            <div class="card">
                <h2>수동 크롤링 실행</h2>
                <p><a href="<?php echo home_url('/test-multi-crawling.php'); ?>" class="button button-primary" target="_blank">멀티소스 크롤링 테스트</a></p>
                <p><a href="<?php echo home_url('/test-integration.php'); ?>" class="button" target="_blank">통합 테스트</a></p>
            </div>
        </div>
        <?php
    }
}

// API 설정 페이지 (중앙화)
function sungsuya_api_settings_page() {
    // 새로운 크롤링 API 설정과 기존 레거시 설정 모두 관리
    if (isset($_POST['save_settings'])) {
        // 레거시 설정 (대량크롤링에서 사용)
        update_option('kakao_api_key', sanitize_text_field($_POST['kakao_api_key']));
        
        // 새로운 설정 (향후 마이그레이션용)
        update_option('sungsuya_naver_client_id', sanitize_text_field($_POST['naver_client_id']));
        update_option('sungsuya_naver_client_secret', sanitize_text_field($_POST['naver_client_secret']));
        update_option('google_places_api_key', sanitize_text_field($_POST['google_places_api_key']));
        
        echo '<div class="notice notice-success"><p>설정이 저장되었습니다.</p></div>';
    }
    
    // 레거시 옵션
    $kakao_api_key = get_option('kakao_api_key');
    
    // 새로운 옵션
    $naver_client_id = get_option('sungsuya_naver_client_id');
    $naver_client_secret = get_option('sungsuya_naver_client_secret');
    $google_places_api_key = get_option('google_places_api_key');
    ?>
    
    <div class="wrap">
        <h1>⚙️ 크롤링 API 통합 설정</h1>
        
        <div class="notice notice-info">
            <p><strong>📌 중요:</strong> 이 페이지는 대량크롤링과 이미지 크롤링에 사용되는 모든 API를 관리합니다.</p>
        </div>
        
        <form method="post">
            <h2>네이버 API 설정</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">네이버 Client ID</th>
                    <td>
                        <input type="text" name="naver_client_id" value="<?php echo esc_attr($naver_client_id); ?>" class="regular-text" />
                        <p class="description">네이버 검색 API Client ID (장소 크롤링용)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">네이버 Client Secret</th>
                    <td>
                        <input type="password" name="naver_client_secret" value="<?php echo esc_attr($naver_client_secret); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>
            
            <h2>카카오 API 설정</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">카카오 REST API Key</th>
                    <td>
                        <input type="text" name="kakao_api_key" value="<?php echo esc_attr($kakao_api_key); ?>" class="regular-text" />
                        <p class="description">카카오 로컬 API Key (대량크롤링에서 사용 중)</p>
                    </td>
                </tr>
            </table>
            
            <h2>구글 API 설정</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Google Places API Key</th>
                    <td>
                        <input type="text" name="google_places_api_key" value="<?php echo esc_attr($google_places_api_key); ?>" class="regular-text" />
                        <p class="description">이미지 크롤링 고도화용 (예: AIzaSyA3W88DVlTYKduynfFyThm8gOGdT_82ZzQ)</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="save_settings" class="button-primary" value="설정 저장" />
            </p>
        </form>
        
        <div class="card">
            <h2>API 연결 상태</h2>
            <?php
            // 카카오 API 테스트
            if (!empty($kakao_api_key)) {
                echo '<p>✅ 카카오 API Key 설정됨</p>';
            } else {
                echo '<p>❌ 카카오 API Key 미설정 - 대량크롤링이 작동하지 않습니다!</p>';
            }
            
            // 네이버 API 테스트
            if (!empty($naver_client_id) && !empty($naver_client_secret)) {
                echo '<p>✅ 네이버 API 설정됨</p>';
            } else {
                echo '<p>⚠️ 네이버 API 미설정 - 네이버 검색 기능 사용 불가</p>';
            }
            
            // 구글 API 테스트
            if (!empty($google_places_api_key)) {
                echo '<p>✅ Google Places API Key 설정됨</p>';
            } else {
                echo '<p>⚠️ Google Places API 미설정 - 이미지 크롤링 고도화 사용 불가</p>';
            }
            ?>
        </div>
    </div>
    
    <style>
    .card {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 20px;
        margin-top: 20px;
    }
    </style>
    <?php
}

// 썸네일 관리자 페이지
function sungsuya_thumbnail_manager_page() {
    // thumbnail-manager.php 파일이 이미 로드되어 있고 클래스가 초기화되어 있으므로
    // 여기서는 리다이렉트만 하면 됩니다
    ?>
    <script>
        window.location.href = '<?php echo admin_url('edit.php?post_type=places&page=places-thumbnail-manager'); ?>';
    </script>
    <?php
}

// 팝업 기간 관리 페이지
function sungsuya_popup_period_manager_page() {
    ?>
    <div class="wrap">
        <h1>🎪 팝업스토어 기간 관리</h1>
        
        <!-- 기능 설명 -->
        <div class="feature-explanation" style="background: #e7f5ff; border: 2px solid #0073aa; padding: 20px; margin-bottom: 30px; border-radius: 5px;">
            <h2 style="margin-top: 0;">📖 이 기능은 무엇인가요?</h2>
            <p style="font-size: 15px; line-height: 1.8;">
                <strong>팝업스토어 기간 관리</strong>는 <strong>시스템이 자동으로</strong> 팝업스토어의 운영 상태를 관리하는 기능입니다.
            </p>
            <ul style="font-size: 14px;">
                <li><strong>매일 자동 실행</strong>: 시스템이 매일 새벽에 모든 팝업스토어를 체크합니다</li>
                <li><strong>상태 자동 변경</strong>: 날짜에 따라 '오픈예정', '운영중', '곧종료', '종료' 상태를 자동으로 변경합니다</li>
                <li><strong>관리자 개입 불필요</strong>: 한 번 설정하면 자동으로 작동합니다</li>
            </ul>
            <p style="margin-bottom: 0; padding: 10px; background: #fff; border-left: 4px solid #0073aa;">
                💡 <strong>요약</strong>: 관리자님은 특별히 할 일이 없습니다! 시스템이 알아서 처리합니다.
            </p>
        </div>
        
        <!-- 자동 관리 상태 -->
        <div class="card" style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 5px; margin-bottom: 20px;">
            <h2>⚙️ 자동 관리 시스템 상태</h2>
            
            <?php
            $timestamp = wp_next_scheduled('check_popup_store_expiration');
            $is_active = $timestamp !== false;
            ?>
            
            <table class="widefat" style="margin-top: 15px;">
                <tr>
                    <th style="width: 200px;">시스템 상태</th>
                    <td>
                        <?php if ($is_active): ?>
                            <span style="color: #46b450; font-weight: bold;">✅ 활성화됨 (정상 작동 중)</span>
                        <?php else: ?>
                            <span style="color: #dc3232; font-weight: bold;">❌ 비활성화됨</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>다음 실행 예정</th>
                    <td>
                        <?php 
                        if ($timestamp) {
                            echo date('Y년 m월 d일 H:i:s', $timestamp);
                            echo ' <span style="color: #666;">(' . human_time_diff($timestamp, current_time('timestamp')) . ' 후)</span>';
                        } else {
                            echo '예약되지 않음';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>실행 주기</th>
                    <td>매일 00:30 (자정 30분)</td>
                </tr>
            </table>
            
            <div style="margin-top: 20px;">
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=popup-period-manager&run_popup_check=1'), 'run_popup_check'); ?>" 
                   class="button button-primary">
                    🔄 지금 수동으로 실행
                </a>
                <span class="description" style="margin-left: 10px;">테스트용으로 즉시 실행할 수 있습니다</span>
            </div>
        </div>
        
        <!-- 상태 변경 규칙 설명 -->
        <div class="card" style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 5px; margin-bottom: 20px;">
            <h2>📋 자동 상태 변경 규칙</h2>
            <p>시스템이 다음 규칙에 따라 자동으로 상태를 변경합니다:</p>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th>조건</th>
                        <th>변경되는 상태</th>
                        <th>설명</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>시작일 전</td>
                        <td><span style="color: #3498db; font-weight: bold;">🔵 오픈예정</span></td>
                        <td>아직 시작하지 않은 팝업</td>
                    </tr>
                    <tr>
                        <td>시작일 ~ 종료일 3일 전</td>
                        <td><span style="color: #27ae60; font-weight: bold;">🟢 운영중</span></td>
                        <td>정상 운영 중인 팝업</td>
                    </tr>
                    <tr>
                        <td>종료일 3일 이내</td>
                        <td><span style="color: #f39c12; font-weight: bold;">🟡 곧종료</span></td>
                        <td>종료가 임박한 팝업 (D-3)</td>
                    </tr>
                    <tr>
                        <td>종료일 이후</td>
                        <td><span style="color: #e74c3c; font-weight: bold;">🔴 종료됨</span></td>
                        <td>운영이 끝난 팝업</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php
        // 수동 실행 처리
        if (isset($_GET['run_popup_check']) && wp_verify_nonce($_GET['_wpnonce'], 'run_popup_check')) {
            handle_popup_store_expiration();
            echo '<div class="notice notice-success is-dismissible"><p>✅ 팝업스토어 기간 체크가 실행되었습니다.</p></div>';
        }
        
        // 상태별 팝업 표시
        global $wpdb;
        $statuses = array(
            'upcoming' => array('label' => '오픈예정', 'color' => '#3498db', 'icon' => '🔵'),
            'open' => array('label' => '운영중', 'color' => '#27ae60', 'icon' => '🟢'),
            'closing_soon' => array('label' => '곧종료', 'color' => '#f39c12', 'icon' => '🟡'),
            'closed' => array('label' => '종료됨', 'color' => '#e74c3c', 'icon' => '🔴')
        );
        
        echo '<h2 style="margin-top: 40px;">📊 현재 팝업스토어 상태</h2>';
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">';
        
        foreach ($statuses as $status_key => $status_info) {
            $popups = get_posts(array(
                'post_type' => 'places',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'place_type',
                        'value' => 'popup_store'
                    ),
                    array(
                        'key' => 'operation_status',
                        'value' => $status_key
                    )
                )
            ));
            
            ?>
            <div class="card" style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 5px;">
                <h3 style="margin-top: 0; color: <?php echo $status_info['color']; ?>;">
                    <?php echo $status_info['icon'] . ' ' . $status_info['label']; ?> 
                    <span style="float: right; font-size: 24px;"><?php echo count($popups); ?></span>
                </h3>
                
                <?php if (!empty($popups)): ?>
                    <div style="max-height: 200px; overflow-y: auto;">
                        <ul style="margin: 0;">
                            <?php 
                            foreach (array_slice($popups, 0, 10) as $popup):
                                $end_date = get_post_meta($popup->ID, 'operation_end', true);
                                $start_date = get_post_meta($popup->ID, 'operation_start', true);
                                ?>
                                <li style="margin-bottom: 8px;">
                                    <a href="<?php echo get_edit_post_link($popup->ID); ?>" style="text-decoration: none;">
                                        <?php echo esc_html($popup->post_title); ?>
                                    </a>
                                    <?php if ($status_key === 'upcoming' && $start_date): ?>
                                        <br><small style="color: #666;">시작: <?php echo $start_date; ?></small>
                                    <?php elseif ($end_date): ?>
                                        <br><small style="color: #666;">종료: <?php echo $end_date; ?></small>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                            <?php if (count($popups) > 10): ?>
                                <li style="color: #666;">... 외 <?php echo count($popups) - 10; ?>개</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <p style="color: #666; margin: 0;">해당 상태의 팝업이 없습니다.</p>
                <?php endif; ?>
            </div>
            <?php
        }
        
        echo '</div>';
        ?>
        
        <!-- FAQ -->
        <div class="card" style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 5px; margin-top: 30px;">
            <h2>❓ 자주 묻는 질문</h2>
            
            <div style="margin-bottom: 20px;">
                <h4>Q: 제가 직접 상태를 변경해야 하나요?</h4>
                <p>A: 아니요. 시스템이 자동으로 처리합니다. 날짜만 정확히 입력하면 됩니다.</p>
            </div>
            
            <div style="margin-bottom: 20px;">
                <h4>Q: 자동 실행이 작동하지 않는 것 같아요.</h4>
                <p>A: WordPress의 wp-cron은 사이트 방문이 있을 때 작동합니다. 트래픽이 적으면 정확한 시간에 실행되지 않을 수 있습니다.</p>
            </div>
            
            <div style="margin-bottom: 20px;">
                <h4>Q: 종료된 팝업은 어떻게 되나요?</h4>
                <p>A: 상태만 '종료됨'으로 변경되고, 데이터는 그대로 보존됩니다. 나중에 참고할 수 있습니다.</p>
            </div>
            
            <div>
                <h4>Q: 수동으로 상태를 변경할 수 있나요?</h4>
                <p>A: 네, 각 팝업스토어 편집 페이지에서 '운영 상태' 필드를 직접 변경할 수 있습니다.</p>
            </div>
        </div>
    </div>
    <?php
}

// 상세정보 자동수집 페이지
function sungsuya_place_detail_collector_page() {
    $collector_admin = new Place_Detail_Auto_Collector_Admin();
    $collector_admin->render_admin_page();
}

// 통합 이미지 크롤링 페이지
function sungsuya_integrated_image_crawler_page() {
    // 통합 이미지 크롤러 관리자 페이지로 리다이렉트
    render_integrated_image_crawling_page();
}

// 관리자 스타일 추가
add_action('admin_head', 'sungsuya_admin_styles');
function sungsuya_admin_styles() {
    ?>
    <style>
        .sungsuya-admin-wrap .card {
            max-width: none;
            margin-bottom: 20px;
        }
        
        .sungsuya-admin-wrap .card h2 {
            border-bottom: 1px solid #ccd0d4;
            padding-bottom: 10px;
        }
        
        /* 서브메뉴 들여쓰기 스타일 */
        #adminmenu .wp-submenu a[href*="enhanced-bulk-crawling"],
        #adminmenu .wp-submenu a[href*="popup-crawling"],
        #adminmenu .wp-submenu a[href*="popup-csv-upload"],
        #adminmenu .wp-submenu a[href*="map-generation"],
        #adminmenu .wp-submenu a[href*="static-map-manager"],
        #adminmenu .wp-submenu a[href*="api-settings"],
        #adminmenu .wp-submenu a[href*="thumbnail-manager"],
        #adminmenu .wp-submenu a[href*="popup-period-manager"],
        #adminmenu .wp-submenu a[href*="image-crawling"],
        #adminmenu .wp-submenu a[href*="data-migration"] {
            padding-left: 30px !important;
            font-size: 13px;
        }
    </style>
    <?php
}
