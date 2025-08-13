<?php
/**
 * 팝업스토어 통합 대시보드
 * 
 * 팝업스토어 관리의 모든 기능을 한 곳에서 관리할 수 있는 통합 대시보드
 * 
 * @package SungsuyaV2
 * @since 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 팝업스토어 대시보드 클래스
 */
class Popup_Store_Dashboard {
    
    /**
     * 싱글톤 인스턴스
     */
    private static $instance = null;
    
    /**
     * 생성자
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_dashboard_menu'), 5);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX 핸들러
        add_action('wp_ajax_popup_dashboard_run_crawl', array($this, 'ajax_run_crawl'));
        add_action('wp_ajax_popup_dashboard_download_excel', array($this, 'ajax_download_excel'));
        add_action('wp_ajax_popup_dashboard_get_stats', array($this, 'ajax_get_stats'));
    }
    
    /**
     * 인스턴스 반환
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 대시보드 메뉴 추가
     */
    public function add_dashboard_menu() {
        // 팝업스토어 관리 메뉴 하위에 추가
        add_submenu_page(
            'admin.php?page=sungsuya-popup',
            '팝업스토어 종합대시보드',
            '📊 종합 대시보드',
            'manage_options',
            'popup-store-dashboard',
            array($this, 'render_dashboard_page'),
            1 // 최상단에 위치
        );
    }
    
    /**
     * 스크립트 및 스타일 등록
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'sungsuya-admin_page_popup-store-dashboard') {
            return;
        }
        
        wp_enqueue_style(
            'popup-dashboard-style',
            get_template_directory_uri() . '/assets/css/popup-dashboard.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'popup-dashboard-script',
            get_template_directory_uri() . '/assets/js/popup-dashboard.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('popup-dashboard-script', 'popupDashboard', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('popup_dashboard')
        ));
    }
    
    /**
     * 대시보드 페이지 렌더링
     */
    public function render_dashboard_page() {
        ?>
        <div class="wrap popup-store-dashboard">
            <h1>🎪 팝업스토어 종합 대시보드</h1>
            
            <!-- 상태 요약 카드 -->
            <div class="dashboard-summary">
                <?php $this->render_status_cards(); ?>
            </div>
            
            <!-- 메인 대시보드 그리드 -->
            <div class="dashboard-grid">
                <!-- 워크플로우 가이드 -->
                <div class="dashboard-widget workflow-widget">
                    <?php $this->render_workflow_guide(); ?>
                </div>
                
                <!-- 빠른 작업 -->
                <div class="dashboard-widget quick-actions-widget">
                    <?php $this->render_quick_actions(); ?>
                </div>
                
                <!-- 최근 크롤링 결과 -->
                <div class="dashboard-widget recent-crawl-widget">
                    <?php $this->render_recent_crawl_results(); ?>
                </div>
                
                <!-- 곧 종료되는 팝업 -->
                <div class="dashboard-widget ending-soon-widget">
                    <?php $this->render_ending_soon_popups(); ?>
                </div>
            </div>
            
            <!-- 활동 로그 -->
            <div class="dashboard-activity-log">
                <?php $this->render_activity_log(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * 상태 카드 렌더링
     */
    private function render_status_cards() {
        $stats = $this->get_popup_stats();
        ?>
        <div class="status-cards">
            <div class="status-card coming-soon">
                <div class="card-icon">🔜</div>
                <div class="card-content">
                    <h3>오픈 예정</h3>
                    <div class="card-number"><?php echo $stats['coming_soon']; ?></div>
                </div>
            </div>
            
            <div class="status-card open">
                <div class="card-icon">✅</div>
                <div class="card-content">
                    <h3>운영중</h3>
                    <div class="card-number"><?php echo $stats['open']; ?></div>
                </div>
            </div>
            
            <div class="status-card ending-soon">
                <div class="card-icon">⏰</div>
                <div class="card-content">
                    <h3>곧 종료</h3>
                    <div class="card-number"><?php echo $stats['ending_soon']; ?></div>
                </div>
            </div>
            
            <div class="status-card closed">
                <div class="card-icon">❌</div>
                <div class="card-content">
                    <h3>종료됨</h3>
                    <div class="card-number"><?php echo $stats['closed']; ?></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * 워크플로우 가이드 렌더링
     */
    private function render_workflow_guide() {
        ?>
        <h3>📋 팝업스토어 관리 워크플로우</h3>
        <div class="workflow-steps">
            <div class="workflow-step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h4>자동 크롤링</h4>
                    <p>매일 00:30 자동 실행</p>
                    <button class="button button-secondary" id="check-crawl-status">
                        상태 확인
                    </button>
                </div>
            </div>
            
            <div class="workflow-arrow">→</div>
            
            <div class="workflow-step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h4>데이터 검증</h4>
                    <p>Excel 다운로드 및 확인</p>
                    <button class="button button-secondary" id="download-excel">
                        📥 다운로드
                    </button>
                </div>
            </div>
            
            <div class="workflow-arrow">→</div>
            
            <div class="workflow-step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h4>AI 정리</h4>
                    <p>프롬프트로 CSV 생성</p>
                    <button class="button button-secondary" id="copy-ai-prompt">
                        📋 프롬프트 복사
                    </button>
                </div>
            </div>
            
            <div class="workflow-arrow">→</div>
            
            <div class="workflow-step" data-step="4">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h4>업로드</h4>
                    <p>CSV 일괄 등록</p>
                    <a href="<?php echo admin_url('admin.php?page=popup-csv-upload'); ?>" 
                       class="button button-primary">
                        📤 업로드
                    </a>
                </div>
            </div>
        </div>
        
        <div class="workflow-progress">
            <div class="progress-bar" style="width: 25%;"></div>
        </div>
        <?php
    }
    
    /**
     * 빠른 작업 렌더링
     */
    private function render_quick_actions() {
        ?>
        <h3>⚡ 빠른 작업</h3>
        <div class="quick-action-buttons">
            <button class="quick-action-btn" id="run-crawl-now">
                <span class="icon">🔍</span>
                <span class="label">지금 크롤링</span>
            </button>
            
            <a href="<?php echo admin_url('admin.php?page=popup-csv-upload'); ?>" 
               class="quick-action-btn">
                <span class="icon">📤</span>
                <span class="label">CSV 업로드</span>
            </a>
            
            <a href="<?php echo admin_url('post-new.php?post_type=places'); ?>" 
               class="quick-action-btn">
                <span class="icon">➕</span>
                <span class="label">수동 추가</span>
            </a>
            
            <button class="quick-action-btn" id="batch-image-crawl">
                <span class="icon">🖼️</span>
                <span class="label">이미지 수집</span>
            </button>
            
            <a href="<?php echo admin_url('admin.php?page=popup-period-manager'); ?>" 
               class="quick-action-btn">
                <span class="icon">📅</span>
                <span class="label">기간 관리</span>
            </a>
            
            <a href="<?php echo admin_url('edit.php?post_type=places&place_type=popup_store'); ?>" 
               class="quick-action-btn">
                <span class="icon">📋</span>
                <span class="label">전체 목록</span>
            </a>
        </div>
        <?php
    }
    
    /**
     * 최근 크롤링 결과 렌더링
     */
    private function render_recent_crawl_results() {
        $last_crawl = get_option('popup_crawl_last_result', array());
        ?>
        <h3>📊 최근 크롤링 결과</h3>
        
        <?php if (!empty($last_crawl)) : ?>
            <div class="crawl-result-info">
                <p><strong>실행 시간:</strong> <?php echo $last_crawl['time']; ?></p>
                <p><strong>발견 항목:</strong> 총 <?php echo $last_crawl['total']; ?>개</p>
                <p><strong>신규:</strong> <?php echo $last_crawl['new']; ?>개 | 
                   <strong>기존:</strong> <?php echo $last_crawl['existing']; ?>개</p>
                
                <?php if (!empty($last_crawl['csv_url'])) : ?>
                    <a href="<?php echo $last_crawl['csv_url']; ?>" 
                       class="button button-secondary" download>
                        📥 CSV 다운로드
                    </a>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <p class="no-data">아직 크롤링 결과가 없습니다.</p>
        <?php endif; ?>
        
        <div class="crawl-log-preview">
            <h4>최근 로그</h4>
            <div class="log-entries">
                <?php $this->show_recent_logs(5); ?>
            </div>
            <a href="<?php echo admin_url('edit.php?post_type=places&page=popup-crawling'); ?>">
                전체 로그 보기 →
            </a>
        </div>
        <?php
    }
    
    /**
     * 곧 종료되는 팝업 렌더링
     */
    private function render_ending_soon_popups() {
        $ending_soon = get_posts(array(
            'post_type' => 'places',
            'posts_per_page' => 5,
            'meta_query' => array(
                array(
                    'key' => 'place_type',
                    'value' => 'popup_store'
                ),
                array(
                    'key' => 'operation_status',
                    'value' => 'ending_soon'
                )
            ),
            'orderby' => 'meta_value',
            'meta_key' => 'end_date',
            'order' => 'ASC'
        ));
        ?>
        <h3>⏰ 곧 종료되는 팝업스토어</h3>
        
        <?php if (!empty($ending_soon)) : ?>
            <ul class="ending-soon-list">
                <?php foreach ($ending_soon as $popup) : ?>
                    <?php
                    $end_date = get_post_meta($popup->ID, 'end_date', true);
                    $days_left = $this->calculate_days_left($end_date);
                    ?>
                    <li>
                        <a href="<?php echo get_edit_post_link($popup->ID); ?>">
                            <?php echo esc_html($popup->post_title); ?>
                        </a>
                        <span class="days-left <?php echo $days_left <= 1 ? 'urgent' : ''; ?>">
                            <?php echo $days_left; ?>일 남음
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="no-data">곧 종료되는 팝업스토어가 없습니다.</p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * 활동 로그 렌더링
     */
    private function render_activity_log() {
        ?>
        <h3>📝 최근 활동</h3>
        <div class="activity-log-table">
            <table class="widefat">
                <thead>
                    <tr>
                        <th>시간</th>
                        <th>활동</th>
                        <th>항목</th>
                        <th>사용자</th>
                        <th>상태</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $this->show_activity_log_entries(); ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * 팝업 통계 가져오기
     */
    private function get_popup_stats() {
        $stats = array(
            'coming_soon' => 0,
            'open' => 0,
            'ending_soon' => 0,
            'closed' => 0
        );
        
        $statuses = array_keys($stats);
        
        foreach ($statuses as $status) {
            $count = get_posts(array(
                'post_type' => 'places',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => array(
                    array(
                        'key' => 'place_type',
                        'value' => 'popup_store'
                    ),
                    array(
                        'key' => 'operation_status',
                        'value' => $status
                    )
                )
            ));
            
            $stats[$status] = count($count);
        }
        
        return $stats;
    }
    
    /**
     * 날짜 계산 헬퍼
     */
    private function calculate_days_left($end_date) {
        $today = new DateTime(current_time('Y-m-d'));
        $end = new DateTime($end_date);
        $diff = $today->diff($end);
        return $diff->days;
    }
    
    /**
     * 최근 로그 표시
     */
    private function show_recent_logs($limit = 5) {
        // 실제 로그 시스템과 연동 필요
        // 임시 데이터
        $logs = array(
            array('time' => '10분 전', 'message' => '크롤링 완료: 5개 팝업 발견'),
            array('time' => '1시간 전', 'message' => 'CSV 업로드: 3개 팝업 등록'),
            array('time' => '3시간 전', 'message' => '상태 업데이트: 2개 팝업 종료됨'),
        );
        
        foreach ($logs as $log) {
            echo '<div class="log-entry">';
            echo '<span class="log-time">' . $log['time'] . '</span> ';
            echo '<span class="log-message">' . $log['message'] . '</span>';
            echo '</div>';
        }
    }
    
    /**
     * 활동 로그 항목 표시
     */
    private function show_activity_log_entries() {
        // 실제 활동 로그 시스템과 연동 필요
        // 임시 데이터
        ?>
        <tr>
            <td>2025-06-27 08:30</td>
            <td>크롤링 실행</td>
            <td>전체 소스</td>
            <td>시스템</td>
            <td><span class="status-badge success">성공</span></td>
        </tr>
        <tr>
            <td>2025-06-27 08:00</td>
            <td>CSV 업로드</td>
            <td>3개 팝업</td>
            <td>관리자</td>
            <td><span class="status-badge success">성공</span></td>
        </tr>
        <tr>
            <td>2025-06-27 00:30</td>
            <td>자동 상태 체크</td>
            <td>전체 팝업</td>
            <td>시스템</td>
            <td><span class="status-badge success">2개 업데이트</span></td>
        </tr>
        <?php
    }
    
    /**
     * AJAX: 크롤링 실행
     */
    public function ajax_run_crawl() {
        check_ajax_referer('popup_dashboard', 'nonce');
        
        // 크롤링 시스템 호출
        // 실제 구현 필요
        
        wp_send_json_success(array(
            'message' => '크롤링이 시작되었습니다.'
        ));
    }
    
    /**
     * AJAX: Excel 다운로드
     */
    public function ajax_download_excel() {
        check_ajax_referer('popup_dashboard', 'nonce');
        
        // Excel 생성 및 다운로드
        // 실제 구현 필요
        
        wp_send_json_success(array(
            'url' => '/path/to/excel'
        ));
    }
    
    /**
     * AJAX: 통계 업데이트
     */
    public function ajax_get_stats() {
        check_ajax_referer('popup_dashboard', 'nonce');
        
        $stats = $this->get_popup_stats();
        
        wp_send_json_success($stats);
    }
}

// 싱글톤 인스턴스 생성
Popup_Store_Dashboard::get_instance();
