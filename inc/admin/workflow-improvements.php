<?php
/**
 * 워크플로우 개선 - 관련 기능 연결 및 다음 단계 안내
 * 
 * @package SungsuyaV2
 * @since 2025.06.27
 */

// 각 페이지 하단에 관련 기능 링크 추가
add_action('admin_footer', 'sungsuya_add_workflow_guides');

function sungsuya_add_workflow_guides() {
    $screen = get_current_screen();
    if (!$screen) return;
    
    // 현재 페이지에 따른 워크플로우 가이드
    $workflows = array(
        // 대량크롤링 완료 후
        'places_page_bulk-crawling-system' => array(
            'title' => '다음 단계',
            'steps' => array(
                array('url' => admin_url('admin.php?page=image-crawling'), 'text' => '이미지 크롤링', 'desc' => '크롤링된 장소의 이미지 수집'),
                array('url' => admin_url('edit.php?post_type=places&page=integrated-map-generation'), 'text' => '지도 생성', 'desc' => '좌표 생성 및 정적지도 생성'),
                array('url' => admin_url('edit.php?post_type=places'), 'text' => '장소 목록 확인', 'desc' => '크롤링 결과 확인'),
            )
        ),
        
        // 이미지 크롤링 완료 후
        'toplevel_page_image-crawling' => array(
            'title' => '다음 단계',
            'steps' => array(
                array('url' => admin_url('admin.php?page=thumbnail-manager'), 'text' => '썸네일 관리', 'desc' => '대표 이미지 설정'),
                array('url' => admin_url('edit.php?post_type=places'), 'text' => '장소 목록', 'desc' => '이미지가 적용된 장소 확인'),
            )
        ),
        
        // CSV 업로드 완료 후
        'toplevel_page_popup-csv-upload' => array(
            'title' => '업로드 완료 후',
            'steps' => array(
                array('url' => admin_url('edit.php?post_type=places&place_type=popup_store'), 'text' => '팝업스토어 목록', 'desc' => '업로드된 팝업 확인'),
                array('url' => admin_url('admin.php?page=popup-period-manager'), 'text' => '기간 관리', 'desc' => '팝업 기간 설정'),
                array('url' => admin_url('admin.php?page=image-crawling'), 'text' => '이미지 크롤링', 'desc' => '팝업 이미지 수집'),
            )
        ),
        
        // 지도 생성 완료 후
        'places_page_integrated-map-generation' => array(
            'title' => '지도 생성 후',
            'steps' => array(
                array('url' => admin_url('edit.php?post_type=places&page=static-map-manager'), 'text' => '정적지도 관리', 'desc' => '생성된 지도 확인'),
                array('url' => admin_url('edit.php?post_type=places'), 'text' => '장소 목록', 'desc' => '좌표가 생성된 장소 확인'),
            )
        ),
    );
    
    $current_workflow = isset($workflows[$screen->id]) ? $workflows[$screen->id] : null;
    
    if ($current_workflow) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // 워크플로우 가이드 HTML 생성
            var workflowHtml = '<div class="workflow-guide">';
            workflowHtml += '<h3><?php echo esc_js($current_workflow['title']); ?></h3>';
            workflowHtml += '<div class="workflow-steps">';
            
            <?php foreach ($current_workflow['steps'] as $index => $step): ?>
            workflowHtml += '<div class="workflow-step">';
            workflowHtml += '<span class="step-number"><?php echo $index + 1; ?></span>';
            workflowHtml += '<div class="step-content">';
            workflowHtml += '<a href="<?php echo esc_js($step['url']); ?>" class="step-link"><?php echo esc_js($step['text']); ?></a>';
            workflowHtml += '<span class="step-desc"><?php echo esc_js($step['desc']); ?></span>';
            workflowHtml += '</div>';
            workflowHtml += '</div>';
            <?php endforeach; ?>
            
            workflowHtml += '</div>';
            workflowHtml += '</div>';
            
            // 페이지 하단에 추가
            $('.wrap').append(workflowHtml);
        });
        </script>
        
        <style>
        .workflow-guide {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin-top: 30px;
            padding: 20px;
            border-radius: 4px;
        }
        
        .workflow-guide h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #23282d;
            font-size: 16px;
        }
        
        .workflow-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .workflow-step {
            display: flex;
            align-items: flex-start;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .workflow-step:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            background: #2271b1;
            color: #fff;
            border-radius: 50%;
            margin-right: 12px;
            flex-shrink: 0;
            font-weight: 600;
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-link {
            display: block;
            font-weight: 600;
            color: #2271b1;
            text-decoration: none;
            margin-bottom: 4px;
        }
        
        .step-link:hover {
            color: #135e96;
            text-decoration: underline;
        }
        
        .step-desc {
            display: block;
            font-size: 13px;
            color: #666;
        }
        </style>
        <?php
    }
}

// 브레드크럼 네비게이션 추가
add_action('admin_notices', 'sungsuya_add_breadcrumb_navigation', 5);

function sungsuya_add_breadcrumb_navigation() {
    $screen = get_current_screen();
    if (!$screen) return;
    
    // 브레드크럼 정의
    $breadcrumbs = array();
    
    // 성수야! 관리 관련 페이지
    if (strpos($screen->id, 'page_image-crawling') !== false || 
        strpos($screen->id, 'page_sungsuya-') !== false ||
        strpos($screen->id, 'page_popup-') !== false) {
        
        $breadcrumbs[] = array('text' => '성수야! 관리', 'url' => admin_url('admin.php?page=sungsuya-admin'));
        
        // 현재 페이지 추가
        switch ($screen->id) {
            case 'toplevel_page_image-crawling':
                $breadcrumbs[] = array('text' => '이미지 크롤링', 'url' => '');
                break;
            case 'sungsuya-관리_page_popup-csv-upload':
                $breadcrumbs[] = array('text' => '팝업스토어', 'url' => admin_url('admin.php?page=popup-store-dashboard'));
                $breadcrumbs[] = array('text' => 'CSV 업로드', 'url' => '');
                break;
        }
    }
    
    // 장소 관련 페이지
    elseif ($screen->post_type === 'places') {
        $breadcrumbs[] = array('text' => '장소', 'url' => admin_url('edit.php?post_type=places'));
        
        switch ($screen->id) {
            case 'places_page_bulk-crawling-system':
                $breadcrumbs[] = array('text' => '대량크롤링', 'url' => '');
                break;
            case 'places_page_integrated-map-generation':
                $breadcrumbs[] = array('text' => '지도 생성', 'url' => '');
                break;
        }
    }
    
    // 브레드크럼 출력
    if (!empty($breadcrumbs)) {
        ?>
        <div class="sungsuya-breadcrumb">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index > 0): ?>
                    <span class="breadcrumb-separator">›</span>
                <?php endif; ?>
                
                <?php if (!empty($crumb['url'])): ?>
                    <a href="<?php echo esc_url($crumb['url']); ?>" class="breadcrumb-link">
                        <?php echo esc_html($crumb['text']); ?>
                    </a>
                <?php else: ?>
                    <span class="breadcrumb-current"><?php echo esc_html($crumb['text']); ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <style>
        .sungsuya-breadcrumb {
            margin: 15px 0;
            padding: 10px 0;
            font-size: 13px;
        }
        
        .breadcrumb-link {
            color: #2271b1;
            text-decoration: none;
        }
        
        .breadcrumb-link:hover {
            color: #135e96;
            text-decoration: underline;
        }
        
        .breadcrumb-current {
            color: #666;
            font-weight: 600;
        }
        
        .breadcrumb-separator {
            margin: 0 8px;
            color: #999;
        }
        </style>
        <?php
    }
}

// 관련 기능 빠른 링크 추가 (사이드바) - 사용자 요청으로 비활성화
// add_action('admin_footer', 'sungsuya_add_quick_links_sidebar');

function sungsuya_add_quick_links_sidebar() {
    $screen = get_current_screen();
    if (!$screen || $screen->id === 'dashboard') return;
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        // 빠른 링크 패널 생성
        var quickLinksHtml = '<div class="quick-links-panel">';
        quickLinksHtml += '<h4>빠른 링크</h4>';
        quickLinksHtml += '<ul>';
        quickLinksHtml += '<li><a href="<?php echo admin_url('edit.php?post_type=places&page=bulk-crawling-system'); ?>">🕷️ 대량크롤링</a></li>';
        quickLinksHtml += '<li><a href="<?php echo admin_url('admin.php?page=image-crawling'); ?>">📸 이미지 크롤링</a></li>';
        quickLinksHtml += '<li><a href="<?php echo admin_url('edit.php?post_type=places&page=integrated-map-generation'); ?>">🗺️ 지도 생성</a></li>';
        quickLinksHtml += '<li><a href="<?php echo admin_url('admin.php?page=popup-csv-upload'); ?>">📄 CSV 업로드</a></li>';
        quickLinksHtml += '<li><a href="<?php echo admin_url('admin.php?page=api-settings'); ?>">⚙️ API 설정</a></li>';
        quickLinksHtml += '</ul>';
        quickLinksHtml += '</div>';
        
        // 고정 위치에 추가
        $('body').append(quickLinksHtml);
    });
    </script>
    
    <style>
    .quick-links-panel {
        position: fixed;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 15px;
        width: 150px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        z-index: 100;
    }
    
    .quick-links-panel h4 {
        margin: 0 0 10px 0;
        font-size: 14px;
        font-weight: 600;
        color: #23282d;
    }
    
    .quick-links-panel ul {
        margin: 0;
        padding: 0;
        list-style: none;
    }
    
    .quick-links-panel li {
        margin-bottom: 8px;
    }
    
    .quick-links-panel a {
        display: block;
        padding: 5px 8px;
        color: #2271b1;
        text-decoration: none;
        font-size: 13px;
        border-radius: 3px;
        transition: all 0.2s;
    }
    
    .quick-links-panel a:hover {
        background: #f0f0f1;
        color: #135e96;
    }
    
    @media screen and (max-width: 1200px) {
        .quick-links-panel {
            display: none;
        }
    }
    </style>
    <?php
}
