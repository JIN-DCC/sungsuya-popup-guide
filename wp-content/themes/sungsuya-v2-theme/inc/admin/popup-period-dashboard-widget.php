<?php
/**
 * 팝업스토어 대시보드 위젯 확장
 * 
 * @package SungsuyaV2
 * @since 2.0.0
 */

// 대시보드 위젯 스타일 추가
add_action('admin_head-index.php', 'popup_dashboard_widget_styles');
function popup_dashboard_widget_styles() {
    ?>
    <style>
        #popup_store_status .popup-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        #popup_store_status .stat-card {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            background: #f5f5f5;
            transition: transform 0.2s;
        }
        
        #popup_store_status .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        #popup_store_status .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        #popup_store_status .stat-label {
            font-size: 14px;
            color: #666;
        }
        
        #popup_store_status .upcoming { background: #e3f2fd; color: #1976d2; }
        #popup_store_status .open { background: #e8f5e9; color: #388e3c; }
        #popup_store_status .closing { background: #fff3e0; color: #f57c00; }
        #popup_store_status .closed { background: #ffebee; color: #d32f2f; }
        
        #popup_store_status .popup-list {
            margin-top: 20px;
            border-top: 1px solid #e0e0e0;
            padding-top: 15px;
        }
        
        #popup_store_status .popup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        #popup_store_status .popup-item:last-child {
            border-bottom: none;
        }
        
        #popup_store_status .popup-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        #popup_store_status .notice {
            padding: 10px;
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            margin-bottom: 15px;
        }
    </style>
    <?php
}

// 향상된 대시보드 위젯 렌더링
add_filter('wp_dashboard_widgets', 'enhance_popup_dashboard_widget', 99);
function enhance_popup_dashboard_widget($widgets) {
    global $wp_meta_boxes;
    
    // 기존 위젯 제거하고 새로운 것으로 교체
    remove_meta_box('popup_store_status', 'dashboard', 'normal');
    
    wp_add_dashboard_widget(
        'popup_store_status_enhanced',
        '🎪 팝업스토어 현황 (Enhanced)',
        'render_enhanced_popup_dashboard_widget'
    );
    
    return $widgets;
}

function render_enhanced_popup_dashboard_widget() {
    global $wpdb;
    
    // 오늘 날짜
    $today = current_time('Y-m-d');
    
    // 상태별 개수 계산
    $stats = $wpdb->get_results("
        SELECT pm.meta_value as status, COUNT(*) as count
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'operating_status'
        INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'place_type'
        WHERE p.post_type = 'places' 
        AND p.post_status = 'publish'
        AND pm2.meta_value = 'popup_store'
        GROUP BY pm.meta_value
    ");
    
    // 오늘 시작하는 팝업
    $starting_today = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'start_date'
        INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'place_type'
        WHERE p.post_type = 'places' 
        AND p.post_status = 'publish'
        AND pm2.meta_value = 'popup_store'
        AND pm1.meta_value = %s
    ", $today));
    
    // 오늘 종료되는 팝업
    $ending_today = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'end_date'
        INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'place_type'
        WHERE p.post_type = 'places' 
        AND p.post_status = 'publish'
        AND pm2.meta_value = 'popup_store'
        AND pm1.meta_value = %s
    ", $today));
    
    // 알림 표시
    if ($starting_today > 0 || $ending_today > 0) {
        echo '<div class="notice">';
        if ($starting_today > 0) {
            echo '<strong>📢 오늘 ' . $starting_today . '개의 팝업스토어가 오픈합니다!</strong><br>';
        }
        if ($ending_today > 0) {
            echo '<strong>⚠️ 오늘 ' . $ending_today . '개의 팝업스토어가 종료됩니다!</strong>';
        }
        echo '</div>';
    }
    
    // 통계 표시
    echo '<div class="popup-stats">';
    
    $status_map = array(
        'upcoming' => array('label' => '오픈예정', 'class' => 'upcoming'),
        'open' => array('label' => '운영중', 'class' => 'open'),
        'closing_soon' => array('label' => '곧종료', 'class' => 'closing'),
        'closed' => array('label' => '종료됨', 'class' => 'closed')
    );
    
    foreach ($status_map as $key => $info) {
        $count = 0;
        foreach ($stats as $stat) {
            if ($stat->status === $key) {
                $count = $stat->count;
                break;
            }
        }
        
        echo '<div class="stat-card ' . $info['class'] . '">';
        echo '<div class="stat-number">' . $count . '</div>';
        echo '<div class="stat-label">' . $info['label'] . '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    
    // 곧 종료될 팝업 (7일 이내)
    $ending_soon = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID, p.post_title, pm1.meta_value as end_date,
               DATEDIFF(pm1.meta_value, %s) as days_left
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'end_date'
        INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'place_type'
        INNER JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = 'operating_status'
        WHERE p.post_type = 'places' 
        AND p.post_status = 'publish'
        AND pm2.meta_value = 'popup_store'
        AND pm3.meta_value IN ('open', 'closing_soon')
        AND pm1.meta_value BETWEEN %s AND DATE_ADD(%s, INTERVAL 7 DAY)
        ORDER BY pm1.meta_value ASC
        LIMIT 5
    ", $today, $today, $today));
    
    if ($ending_soon) {
        echo '<div class="popup-list">';
        echo '<h4>🚨 곧 종료 예정 (7일 이내)</h4>';
        foreach ($ending_soon as $popup) {
            echo '<div class="popup-item">';
            echo '<div>';
            echo '<a href="' . get_edit_post_link($popup->ID) . '">' . esc_html($popup->post_title) . '</a>';
            echo '</div>';
            echo '<div>';
            if ($popup->days_left == 0) {
                echo '<span style="color: #d32f2f; font-weight: bold;">오늘 종료</span>';
            } elseif ($popup->days_left == 1) {
                echo '<span style="color: #f57c00;">내일 종료</span>';
            } else {
                echo '<span style="color: #666;">' . $popup->days_left . '일 남음</span>';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    // 액션 버튼들
    echo '<div class="popup-actions">';
    echo '<a href="edit.php?post_type=places&place_type=popup_store" class="button button-primary">모든 팝업스토어 보기</a>';
    echo '<a href="admin.php?page=popup-csv-upload" class="button">CSV 업로드</a>';
    echo '<a href="' . admin_url('index.php?run_popup_check=1') . '" class="button">상태 수동 체크</a>';
    echo '</div>';
}

// 팝업스토어 필터 추가
add_action('restrict_manage_posts', 'add_popup_store_filters');
function add_popup_store_filters() {
    global $typenow;
    
    if ($typenow != 'places') {
        return;
    }
    
    // 장소 타입이 팝업스토어인 경우만
    if (isset($_GET['place_type']) && $_GET['place_type'] == 'popup_store') {
        // 운영 상태 필터
        $selected = isset($_GET['operating_status']) ? $_GET['operating_status'] : '';
        ?>
        <select name="operating_status">
            <option value="">모든 상태</option>
            <option value="upcoming" <?php selected($selected, 'upcoming'); ?>>오픈예정</option>
            <option value="open" <?php selected($selected, 'open'); ?>>운영중</option>
            <option value="closing_soon" <?php selected($selected, 'closing_soon'); ?>>곧종료</option>
            <option value="closed" <?php selected($selected, 'closed'); ?>>종료됨</option>
        </select>
        <?php
    }
}

// 필터 쿼리 수정
add_filter('parse_query', 'filter_popup_stores_by_status');
function filter_popup_stores_by_status($query) {
    global $pagenow, $typenow;
    
    if ($pagenow == 'edit.php' && $typenow == 'places' && isset($_GET['operating_status']) && $_GET['operating_status'] != '') {
        $query->set('meta_key', 'operating_status');
        $query->set('meta_value', $_GET['operating_status']);
    }
}
