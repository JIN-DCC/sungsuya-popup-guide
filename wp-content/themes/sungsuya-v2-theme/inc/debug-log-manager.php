<?php
/**
 * 디버그 로그 자동 관리 시스템
 * 
 * 로그 파일이 일정 크기를 넘어가면 자동으로 정리
 * 
 * @package SungsuyaV2
 * @since 2.2.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 디버그 로그 매니저 클래스
 */
class Sungsuya_Debug_Log_Manager {
    
    /**
     * 최대 로그 파일 크기 (5MB)
     */
    const MAX_LOG_SIZE = 5 * 1024 * 1024; // 5MB
    
    /**
     * 보관할 로그 라인 수
     */
    const KEEP_LINES = 1000;
    
    /**
     * 싱글톤 인스턴스
     */
    private static $instance = null;
    
    /**
     * 디버그 로그 파일 경로
     */
    private $log_file;
    
    /**
     * 생성자
     */
    private function __construct() {
        $this->log_file = WP_CONTENT_DIR . '/debug.log';
        
        // 페이지 로드 시 로그 크기 체크
        add_action('init', array($this, 'check_log_size'), 1);
        
        // 관리자 알림
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // AJAX 핸들러
        add_action('wp_ajax_clear_debug_log', array($this, 'ajax_clear_log'));
        add_action('wp_ajax_truncate_debug_log', array($this, 'ajax_truncate_log'));
    }
    
    /**
     * 싱글톤 인스턴스 반환
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 로그 파일 크기 체크 및 자동 정리
     */
    public function check_log_size() {
        if (!file_exists($this->log_file)) {
            return;
        }
        
        $size = filesize($this->log_file);
        
        // 5MB 초과 시 자동 정리
        if ($size > self::MAX_LOG_SIZE) {
            $this->truncate_log();
        }
    }
    
    /**
     * 로그 파일 일부만 남기고 정리
     */
    public function truncate_log() {
        if (!file_exists($this->log_file)) {
            return false;
        }
        
        // 파일을 역순으로 읽어 최근 로그만 보관
        $lines = file($this->log_file);
        $total_lines = count($lines);
        
        if ($total_lines > self::KEEP_LINES) {
            // 최근 1000줄만 보관
            $keep_lines = array_slice($lines, -self::KEEP_LINES);
            
            // 정리 알림 추가
            array_unshift($keep_lines, 
                "[" . date('Y-m-d H:i:s') . "] === 디버그 로그 자동 정리됨 (이전 " . ($total_lines - self::KEEP_LINES) . "줄 삭제) ===\n"
            );
            
            // 파일 다시 쓰기
            file_put_contents($this->log_file, implode('', $keep_lines));
            
            // 정리 기록 저장
            update_option('sungsuya_last_log_cleanup', array(
                'time' => current_time('timestamp'),
                'removed_lines' => $total_lines - self::KEEP_LINES,
                'new_size' => filesize($this->log_file)
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 로그 파일 완전 삭제
     */
    public function clear_log() {
        if (file_exists($this->log_file)) {
            // 빈 파일로 만들기
            file_put_contents($this->log_file, '');
            
            // 초기 로그 메시지
            error_log('[' . date('Y-m-d H:i:s') . '] === 디버그 로그 초기화됨 ===');
            
            update_option('sungsuya_last_log_clear', current_time('timestamp'));
            return true;
        }
        return false;
    }
    
    /**
     * 관리자 알림 표시
     */
    public function admin_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // 로그 파일 크기 확인
        if (file_exists($this->log_file)) {
            $size = filesize($this->log_file);
            $size_mb = round($size / (1024 * 1024), 2);
            
            // 3MB 이상이면 경고
            if ($size > 3 * 1024 * 1024) {
                ?>
                <div class="notice notice-warning is-dismissible" id="debug-log-notice">
                    <p>
                        <strong>디버그 로그 크기 경고:</strong> 
                        현재 디버그 로그 파일이 <?php echo $size_mb; ?>MB입니다.
                        <button class="button button-small" onclick="truncateDebugLog()">최근 1000줄만 남기기</button>
                        <button class="button button-small" onclick="clearDebugLog()">완전히 비우기</button>
                    </p>
                </div>
                <script>
                function truncateDebugLog() {
                    if (confirm('디버그 로그를 최근 1000줄만 남기고 정리하시겠습니까?')) {
                        jQuery.post(ajaxurl, {
                            action: 'truncate_debug_log',
                            nonce: '<?php echo wp_create_nonce('debug_log_action'); ?>'
                        }, function(response) {
                            if (response.success) {
                                alert('로그가 정리되었습니다.');
                                location.reload();
                            }
                        });
                    }
                }
                
                function clearDebugLog() {
                    if (confirm('디버그 로그를 완전히 비우시겠습니까?')) {
                        jQuery.post(ajaxurl, {
                            action: 'clear_debug_log',
                            nonce: '<?php echo wp_create_nonce('debug_log_action'); ?>'
                        }, function(response) {
                            if (response.success) {
                                alert('로그가 초기화되었습니다.');
                                location.reload();
                            }
                        });
                    }
                }
                </script>
                <?php
            }
        }
    }
    
    /**
     * AJAX: 로그 정리
     */
    public function ajax_truncate_log() {
        check_ajax_referer('debug_log_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $result = $this->truncate_log();
        wp_send_json_success(array('truncated' => $result));
    }
    
    /**
     * AJAX: 로그 삭제
     */
    public function ajax_clear_log() {
        check_ajax_referer('debug_log_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $result = $this->clear_log();
        wp_send_json_success(array('cleared' => $result));
    }
    
    /**
     * 로그 파일 정보 가져오기
     */
    public function get_log_info() {
        if (!file_exists($this->log_file)) {
            return array(
                'exists' => false,
                'size' => 0,
                'size_human' => '0 B',
                'lines' => 0,
                'last_modified' => null
            );
        }
        
        $size = filesize($this->log_file);
        $lines = count(file($this->log_file));
        
        return array(
            'exists' => true,
            'size' => $size,
            'size_human' => size_format($size),
            'lines' => $lines,
            'last_modified' => filemtime($this->log_file),
            'last_cleanup' => get_option('sungsuya_last_log_cleanup'),
            'last_clear' => get_option('sungsuya_last_log_clear')
        );
    }
}

// 디버그 로그 매니저 초기화
Sungsuya_Debug_Log_Manager::get_instance();

/**
 * 헬퍼 함수: 안전한 디버그 로깅
 */
if (!function_exists('sungsuya_debug_log')) {
    function sungsuya_debug_log($message, $context = '') {
        if (WP_DEBUG && WP_DEBUG_LOG) {
            $prefix = $context ? "[{$context}] " : '';
            error_log($prefix . print_r($message, true));
        }
    }
}
