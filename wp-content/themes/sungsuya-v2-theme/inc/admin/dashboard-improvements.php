<?php
/**
 * 대시보드 개선 - 통계 및 시각화
 * 
 * @package SungsuyaV2
 * @since 2025.06.27
 */

// 메인 대시보드 페이지 개선
add_action('admin_init', 'sungsuya_improve_main_dashboard');

function sungsuya_improve_main_dashboard() {
    if (isset($_GET['page']) && $_GET['page'] == 'sungsuya-admin') {
        add_action('admin_head', 'sungsuya_dashboard_improvements');
    }
}

function sungsuya_dashboard_improvements() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // 기존 대시보드 내용 개선
        var dashboardContent = $('.wrap').html();
        
        // 새로운 대시보드 레이아웃
        var newDashboard = `
        <div class="sungsuya-dashboard">
            <h1>🚀 성수야! 대시보드</h1>
            
            <!-- 주요 통계 -->
            <div class="dashboard-stats">
                <div class="stat-card" data-link="<?php echo admin_url('edit.php?post_type=places'); ?>">
                    <div class="stat-icon">📍</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo wp_count_posts('places')->publish; ?></div>
                        <div class="stat-label">전체 장소</div>
                    </div>
                </div>
                
                <div class="stat-card" data-link="<?php echo admin_url('edit.php?post_type=places&place_type=popup_store'); ?>">
                    <div class="stat-icon">🎪</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php 
                            $popup_stores = get_posts(array(
                                'post_type' => 'places',
                                'meta_key' => 'place_type',
                                'meta_value' => 'popup_store',
                                'posts_per_page' => -1
                            ));
                            echo count($popup_stores);
                        ?></div>
                        <div class="stat-label">팝업스토어</div>
                    </div>
                </div>
                
                <div class="stat-card" data-link="<?php echo admin_url('admin.php?page=image-crawling'); ?>">
                    <div class="stat-icon">📸</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php 
                            $places_with_images = get_posts(array(
                                'post_type' => 'places',
                                'meta_key' => '_thumbnail_id',
                                'meta_compare' => 'EXISTS',
                                'posts_per_page' => -1
                            ));
                            echo count($places_with_images);
                        ?></div>
                        <div class="stat-label">이미지 보유</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🗺️</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php 
                            $places_with_coords = get_posts(array(
                                'post_type' => 'places',
                                'meta_key' => 'latitude',
                                'meta_compare' => 'EXISTS',
                                'posts_per_page' => -1
                            ));
                            echo count($places_with_coords);
                        ?></div>
                        <div class="stat-label">좌표 생성</div>
                    </div>
                </div>
            </div>
            
            <!-- 진행률 차트 -->
            <div class="dashboard-row">
                <div class="dashboard-card">
                    <h3>데이터 완성도</h3>
                    <div class="progress-charts">
                        <?php 
                        $total_places = wp_count_posts('places')->publish;
                        $image_percent = $total_places > 0 ? round((count($places_with_images) / $total_places) * 100) : 0;
                        $coord_percent = $total_places > 0 ? round((count($places_with_coords) / $total_places) * 100) : 0;
                        ?>
                        
                        <div class="progress-item">
                            <div class="progress-label">
                                <span>이미지 크롤링</span>
                                <span><?php echo $image_percent; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $image_percent; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="progress-item">
                            <div class="progress-label">
                                <span>좌표 생성</span>
                                <span><?php echo $coord_percent; ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $coord_percent; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <h3>빠른 작업</h3>
                    <div class="quick-actions">
                        <a href="<?php echo admin_url('edit.php?post_type=places&page=bulk-crawling-system'); ?>" class="action-button">
                            <span class="action-icon">🕷️</span>
                            <span class="action-text">대량 크롤링 시작</span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=image-crawling'); ?>" class="action-button">
                            <span class="action-icon">📸</span>
                            <span class="action-text">이미지 크롤링</span>
                        </a>
                        <a href="<?php echo admin_url('edit.php?post_type=places&page=integrated-map-generation'); ?>" class="action-button">
                            <span class="action-icon">🗺️</span>
                            <span class="action-text">지도 생성</span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=popup-csv-upload'); ?>" class="action-button">
                            <span class="action-icon">📄</span>
                            <span class="action-text">CSV 업로드</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- 최근 활동 -->
            <div class="dashboard-card">
                <h3>최근 활동</h3>
                <div class="activity-list">
                    <?php
                    $recent_places = get_posts(array(
                        'post_type' => 'places',
                        'posts_per_page' => 10,
                        'orderby' => 'date',
                        'order' => 'DESC'
                    ));
                    
                    foreach ($recent_places as $place) {
                        $time_diff = human_time_diff(get_post_time('U', true, $place), current_time('timestamp'));
                        ?>
                        <div class="activity-item">
                            <span class="activity-icon">📍</span>
                            <span class="activity-content">
                                <a href="<?php echo get_edit_post_link($place->ID); ?>"><?php echo esc_html($place->post_title); ?></a>
                                <span class="activity-time"><?php echo $time_diff; ?> 전</span>
                            </span>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        `;
        
        // 대시보드 내용 교체
        $('.wrap').html(newDashboard);
        
        // 통계 카드 클릭 이벤트
        $('.stat-card[data-link]').on('click', function() {
            window.location.href = $(this).data('link');
        });
    });
    </script>
    
    <style>
    .sungsuya-dashboard {
        max-width: 1200px;
        margin: 20px auto;
    }
    
    /* 통계 카드 */
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        align-items: center;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border-color: #2271b1;
    }
    
    .stat-icon {
        font-size: 40px;
        margin-right: 20px;
    }
    
    .stat-content {
        flex: 1;
    }
    
    .stat-number {
        font-size: 32px;
        font-weight: 600;
        color: #2271b1;
        line-height: 1;
    }
    
    .stat-label {
        font-size: 14px;
        color: #666;
        margin-top: 5px;
    }
    
    /* 대시보드 행 */
    .dashboard-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    /* 대시보드 카드 */
    .dashboard-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
    }
    
    .dashboard-card h3 {
        margin-top: 0;
        margin-bottom: 20px;
        font-size: 16px;
        font-weight: 600;
        color: #23282d;
    }
    
    /* 진행률 차트 */
    .progress-item {
        margin-bottom: 20px;
    }
    
    .progress-item:last-child {
        margin-bottom: 0;
    }
    
    .progress-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .progress-bar {
        height: 10px;
        background: #f0f0f0;
        border-radius: 5px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: #2271b1;
        transition: width 0.5s ease;
    }
    
    /* 빠른 작업 */
    .quick-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    
    .action-button {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        background: #f8f9fa;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        text-decoration: none;
        color: #23282d;
        transition: all 0.2s;
    }
    
    .action-button:hover {
        background: #2271b1;
        color: #fff;
        border-color: #2271b1;
    }
    
    .action-icon {
        font-size: 20px;
        margin-right: 8px;
    }
    
    .action-text {
        font-size: 14px;
        font-weight: 500;
    }
    
    /* 최근 활동 */
    .activity-list {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .activity-item {
        display: flex;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-icon {
        font-size: 16px;
        margin-right: 10px;
    }
    
    .activity-content {
        flex: 1;
    }
    
    .activity-content a {
        text-decoration: none;
        color: #2271b1;
    }
    
    .activity-content a:hover {
        text-decoration: underline;
    }
    
    .activity-time {
        font-size: 12px;
        color: #999;
        margin-left: 10px;
    }
    
    /* 반응형 */
    @media screen and (max-width: 1024px) {
        .dashboard-row {
            grid-template-columns: 1fr;
        }
    }
    
    @media screen and (max-width: 768px) {
        .dashboard-stats {
            grid-template-columns: 1fr;
        }
        
        .quick-actions {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <?php
}
