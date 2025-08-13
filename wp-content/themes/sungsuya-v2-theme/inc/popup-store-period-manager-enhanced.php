<?php
/**
 * 팝업스토어 기간 관리 자동화 시스템
 * 
 * wp-cron을 사용하여 매일 팝업스토어 상태를 자동으로 업데이트
 * 
 * @package SungsuyaV2
 * @since 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 팝업스토어 기간 관리 클래스
 */
class Popup_Store_Period_Manager {
    
    /**
     * 싱글톤 인스턴스
     */
    private static $instance = null;
    
    /**
     * 생성자
     */
    private function __construct() {
        $this->init();
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
     * 초기화
     */
    private function init() {
        // wp-cron 이벤트 등록
        add_action('init', array($this, 'schedule_status_check'));
        add_action('popup_store_daily_status_check', array($this, 'check_popup_store_status'));
        
        // 관리자 페이지 추가
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // AJAX 핸들러
        add_action('wp_ajax_check_popup_status_now', array($this, 'ajax_check_status_now'));
        
        // 대시보드 위젯
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        
        // 관리자 알림
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }
    
    /**
     * 일일 체크 스케줄 등록
     */
    public function schedule_status_check() {
        if (!wp_next_scheduled('popup_store_daily_status_check')) {
            wp_schedule_event(time(), 'daily', 'popup_store_daily_status_check');
        }
    }
    
    /**
     * 팝업스토어 상태 체크 및 업데이트
     */
    public function check_popup_store_status() {
        $today = current_time('Y-m-d');
        $updated_count = 0;
        
        error_log('[팝업 기간 관리] 상태 체크 시작: ' . $today);
        
        // 1. 오픈 예정 → 운영중
        $coming_soon = get_posts(array(
            'post_type' => 'places',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'place_type',
                    'value' => 'popup_store'
                ),
                array(
                    'key' => 'operation_status',
                    'value' => 'coming_soon'
                ),
                array(
                    'key' => 'start_date',
                    'value' => $today,
                    'compare' => '<=',
                    'type' => 'DATE'
                )
            )
        ));
        
        foreach ($coming_soon as $popup) {
            update_post_meta($popup->ID, 'operation_status', 'open');
            $updated_count++;
            error_log('[팝업 기간 관리] 오픈됨: ' . $popup->post_title);
        }
        
        // 2. 운영중 → 곧 종료 (3일 전)
        $three_days_later = date('Y-m-d', strtotime('+3 days'));
        $ending_soon = get_posts(array(
            'post_type' => 'places',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'place_type',
                    'value' => 'popup_store'
                ),
                array(
                    'key' => 'operation_status',
                    'value' => 'open'
                ),
                array(
                    'key' => 'end_date',
                    'value' => $three_days_later,
                    'compare' => '<=',
                    'type' => 'DATE'
                ),
                array(
                    'key' => 'end_date',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            )
        ));
        
        foreach ($ending_soon as $popup) {
            update_post_meta($popup->ID, 'operation_status', 'ending_soon');
            $updated_count++;
            error_log('[팝업 기간 관리] 곧 종료: ' . $popup->post_title);
        }
        
        // 3. 운영중/곧종료 → 종료됨
        $closed_statuses = array('open', 'ending_soon');
        foreach ($closed_statuses as $status) {
            $to_close = get_posts(array(
                'post_type' => 'places',
                'posts_per_page' => -1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'place_type',
                        'value' => 'popup_store'
                    ),
                    array(
                        'key' => 'operation_status',
                        'value' => $status
                    ),
                    array(
                        'key' => 'end_date',
                        'value' => $today,
                        'compare' => '<',
                        'type' => 'DATE'
                    )
                )
            ));
            
            foreach ($to_close as $popup) {
                update_post_meta($popup->ID, 'operation_status', 'closed');
                $updated_count++;
                error_log('[팝업 기간 관리] 종료됨: ' . $popup->post_title);
            }
        }
        
        // 결과 저장
        update_option('popup_store_last_check', array(
            'date' => $today,
            'time' => current_time('H:i:s'),
            'updated' => $updated_count
        ));
        
        error_log('[팝업 기간 관리] 상태 체크 완료: ' . $updated_count . '개 업데이트됨');
        
        return $updated_count;
    }
    
    /**
     * 관리자 메뉴 추가
     */
    public function add_admin_menu() {
        add_submenu_page(
            'admin.php?page=sungsuya-popup',
            '팝업스토어 기간 관리',
            '└ 기간 관리',
            'manage_options',
            'popup-period-manager',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * 관리자 페이지 렌더링
     */
    public function render_admin_page() {
        $last_check = get_option('popup_store_last_check', array());
        ?>
        <div class="wrap">
            <h1>🕐 팝업스토어 기간 관리</h1>
            
            <div class="card">
                <h2>⚙️ 자동 상태 업데이트 설정</h2>
                <p>매일 자동으로 팝업스토어의 운영 상태를 확인하고 업데이트합니다.</p>
                
                <table class="form-table">
                    <tr>
                        <th>마지막 체크</th>
                        <td>
                            <?php if (!empty($last_check['date'])) : ?>
                                <?php echo $last_check['date'] . ' ' . $last_check['time']; ?>
                                (<?php echo $last_check['updated']; ?>개 업데이트)
                            <?php else : ?>
                                <em>아직 실행되지 않음</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>다음 실행 예정</th>
                        <td>
                            <?php
                            $next = wp_next_scheduled('popup_store_daily_status_check');
                            if ($next) {
                                echo date('Y-m-d H:i:s', $next);
                            } else {
                                echo '<em>예약되지 않음</em>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                
                <p>
                    <button class="button button-primary" id="check-status-now">
                        지금 상태 체크 실행
                    </button>
                    <span class="spinner" style="float: none;"></span>
                </p>
                
                <div id="check-result" style="margin-top: 10px;"></div>
            </div>
            
            <div class="card">
                <h2>📊 현재 팝업스토어 현황</h2>
                <?php $this->show_popup_store_stats(); ?>
            </div>
            
            <div class="card">
                <h2>🔔 상태 변경 규칙</h2>
                <ul>
                    <li><strong>오픈 예정 → 운영중:</strong> 시작일이 오늘이거나 지난 경우</li>
                    <li><strong>운영중 → 곧 종료:</strong> 종료일이 3일 이내인 경우</li>
                    <li><strong>운영중/곧종료 → 종료됨:</strong> 종료일이 지난 경우</li>
                </ul>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#check-status-now').on('click', function() {
                    var $button = $(this);
                    var $spinner = $button.next('.spinner');
                    var $result = $('#check-result');
                    
                    $button.prop('disabled', true);
                    $spinner.addClass('is-active');
                    $result.empty();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'check_popup_status_now',
                            nonce: '<?php echo wp_create_nonce('check_popup_status'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $result.html(
                                    '<div class="notice notice-success">' +
                                    '<p><strong>✅ 체크 완료!</strong> ' + 
                                    response.data.updated + '개 팝업스토어가 업데이트되었습니다.</p>' +
                                    '</div>'
                                );
                                
                                // 페이지 새로고침
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                $result.html(
                                    '<div class="notice notice-error">' +
                                    '<p><strong>❌ 오류:</strong> ' + response.data + '</p>' +
                                    '</div>'
                                );
                            }
                        },
                        complete: function() {
                            $button.prop('disabled', false);
                            $spinner.removeClass('is-active');
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * 팝업스토어 통계 표시
     */
    private function show_popup_store_stats() {
        $stats = $this->get_popup_store_stats();
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th>상태</th>
                    <th>개수</th>
                    <th>팝업스토어</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>🔜 오픈 예정</td>
                    <td><?php echo $stats['coming_soon']['count']; ?>개</td>
                    <td>
                        <?php
                        if (!empty($stats['coming_soon']['posts'])) {
                            foreach ($stats['coming_soon']['posts'] as $post) {
                                $start_date = get_post_meta($post->ID, 'start_date', true);
                                echo '<a href="' . get_edit_post_link($post->ID) . '">';
                                echo esc_html($post->post_title);
                                echo '</a> (시작: ' . $start_date . ')<br>';
                            }
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>✅ 운영중</td>
                    <td><?php echo $stats['open']['count']; ?>개</td>
                    <td>
                        <?php
                        if (!empty($stats['open']['posts'])) {
                            foreach ($stats['open']['posts'] as $post) {
                                $end_date = get_post_meta($post->ID, 'end_date', true);
                                echo '<a href="' . get_edit_post_link($post->ID) . '">';
                                echo esc_html($post->post_title);
                                echo '</a> (종료: ' . $end_date . ')<br>';
                            }
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>⏰ 곧 종료</td>
                    <td><?php echo $stats['ending_soon']['count']; ?>개</td>
                    <td>
                        <?php
                        if (!empty($stats['ending_soon']['posts'])) {
                            foreach ($stats['ending_soon']['posts'] as $post) {
                                $end_date = get_post_meta($post->ID, 'end_date', true);
                                $days_left = $this->get_days_until($end_date);
                                echo '<a href="' . get_edit_post_link($post->ID) . '">';
                                echo esc_html($post->post_title);
                                echo '</a> <strong>(' . $days_left . '일 남음)</strong><br>';
                            }
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>❌ 종료됨</td>
                    <td><?php echo $stats['closed']['count']; ?>개</td>
                    <td>
                        <?php
                        if (!empty($stats['closed']['posts'])) {
                            $shown = 0;
                            foreach ($stats['closed']['posts'] as $post) {
                                if ($shown >= 5) {
                                    echo '...외 ' . ($stats['closed']['count'] - 5) . '개';
                                    break;
                                }
                                echo '<a href="' . get_edit_post_link($post->ID) . '">';
                                echo esc_html($post->post_title);
                                echo '</a><br>';
                                $shown++;
                            }
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * 팝업스토어 통계 가져오기
     */
    public function get_popup_store_stats() {
        $statuses = array('coming_soon', 'open', 'ending_soon', 'closed');
        $stats = array();
        
        foreach ($statuses as $status) {
            $posts = get_posts(array(
                'post_type' => 'places',
                'posts_per_page' => -1,
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
            
            $stats[$status] = array(
                'count' => count($posts),
                'posts' => $posts
            );
        }
        
        return $stats;
    }
    
    /**
     * 날짜까지 남은 일수 계산
     */
    private function get_days_until($date) {
        $today = new DateTime(current_time('Y-m-d'));
        $target = new DateTime($date);
        $diff = $today->diff($target);
        return $diff->days;
    }
    
    /**
     * AJAX: 즉시 상태 체크
     */
    public function ajax_check_status_now() {
        check_ajax_referer('check_popup_status', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('권한이 없습니다.');
        }
        
        $updated = $this->check_popup_store_status();
        
        wp_send_json_success(array(
            'updated' => $updated
        ));
    }
    
    /**
     * 대시보드 위젯 추가
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'popup_store_period_widget',
            '🎪 팝업스토어 기간 관리',
            array($this, 'render_dashboard_widget')
        );
    }
    
    /**
     * 대시보드 위젯 렌더링
     */
    public function render_dashboard_widget() {
        $stats = $this->get_popup_store_stats();
        ?>
        <div class="popup-store-widget">
            <p>
                <strong>오픈예정:</strong> <?php echo $stats['coming_soon']['count']; ?>개 | 
                <strong>운영중:</strong> <?php echo $stats['open']['count']; ?>개 | 
                <strong>곧종료:</strong> <?php echo $stats['ending_soon']['count']; ?>개
            </p>
            
            <?php if (!empty($stats['ending_soon']['posts'])) : ?>
                <h4 style="color: #ff6b6b;">⏰ 곧 종료되는 팝업</h4>
                <ul>
                    <?php foreach ($stats['ending_soon']['posts'] as $post) : ?>
                        <?php
                        $end_date = get_post_meta($post->ID, 'end_date', true);
                        $days_left = $this->get_days_until($end_date);
                        ?>
                        <li>
                            <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                <?php echo esc_html($post->post_title); ?>
                            </a>
                            - <strong><?php echo $days_left; ?>일 남음</strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <p>
                <a href="<?php echo admin_url('admin.php?page=popup-period-manager'); ?>" 
                   class="button button-primary">
                    기간 관리 설정
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * 관리자 알림 표시
     */
    public function show_admin_notices() {
        // 곧 종료되는 팝업이 있는 경우 알림
        $stats = $this->get_popup_store_stats();
        
        if (!empty($stats['ending_soon']['posts']) && current_user_can('manage_options')) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>⏰ 곧 종료되는 팝업스토어가 <?php echo $stats['ending_soon']['count']; ?>개 있습니다.</strong>
                    <a href="<?php echo admin_url('edit.php?post_type=places&place_type=popup_store&operation_status=ending_soon'); ?>">
                        확인하기
                    </a>
                </p>
            </div>
            <?php
        }
    }
}

// 싱글톤 인스턴스 생성
Popup_Store_Period_Manager::get_instance();

/**
 * 플러그인 비활성화 시 스케줄 제거
 */
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('popup_store_daily_status_check');
});
