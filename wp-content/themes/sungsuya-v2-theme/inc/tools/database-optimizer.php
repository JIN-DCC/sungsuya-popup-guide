<?php
/**
 * 데이터베이스 최적화 도구
 * 
 * 프로덕션 배포 전 데이터베이스 정리 및 최적화
 * 
 * @package SungsuyaV2
 * @version 1.0.0
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 데이터베이스 최적화 클래스
 */
class Sungsuya_Database_Optimizer {
    
    /**
     * 최적화 통계
     */
    private $stats = array(
        'revisions_deleted' => 0,
        'auto_drafts_deleted' => 0,
        'spam_comments_deleted' => 0,
        'trashed_posts_deleted' => 0,
        'orphaned_postmeta_deleted' => 0,
        'transients_deleted' => 0,
        'tables_optimized' => 0,
        'space_saved' => 0
    );
    
    /**
     * 데이터베이스 최적화 실행
     */
    public function optimize() {
        global $wpdb;
        
        // 시작 전 데이터베이스 크기
        $initial_size = $this->get_database_size();
        
        // 1. 리비전 정리
        $this->clean_revisions();
        
        // 2. 자동 임시저장 정리
        $this->clean_auto_drafts();
        
        // 3. 스팸 댓글 정리
        $this->clean_spam_comments();
        
        // 4. 휴지통 비우기
        $this->empty_trash();
        
        // 5. 고아 메타데이터 정리
        $this->clean_orphaned_metadata();
        
        // 6. 만료된 트랜지언트 정리
        $this->clean_transients();
        
        // 7. 테이블 최적화
        $this->optimize_tables();
        
        // 최적화 후 데이터베이스 크기
        $final_size = $this->get_database_size();
        $this->stats['space_saved'] = $initial_size - $final_size;
        
        return $this->stats;
    }
    
    /**
     * 리비전 정리
     */
    private function clean_revisions() {
        global $wpdb;
        
        // 최근 5개를 제외한 모든 리비전 삭제
        $query = "
            DELETE FROM {$wpdb->posts} 
            WHERE post_type = 'revision' 
            AND ID NOT IN (
                SELECT * FROM (
                    SELECT ID FROM {$wpdb->posts} p2 
                    WHERE p2.post_type = 'revision' 
                    AND p2.post_parent = {$wpdb->posts}.post_parent 
                    ORDER BY p2.post_modified DESC 
                    LIMIT 5
                ) AS recent_revisions
            )
        ";
        
        $deleted = $wpdb->query($query);
        $this->stats['revisions_deleted'] = $deleted;
    }
    
    /**
     * 자동 임시저장 정리
     */
    private function clean_auto_drafts() {
        global $wpdb;
        
        $deleted = $wpdb->delete(
            $wpdb->posts,
            array('post_status' => 'auto-draft'),
            array('%s')
        );
        
        $this->stats['auto_drafts_deleted'] = $deleted;
    }
    
    /**
     * 스팸 댓글 정리
     */
    private function clean_spam_comments() {
        global $wpdb;
        
        $deleted = $wpdb->delete(
            $wpdb->comments,
            array('comment_approved' => 'spam'),
            array('%s')
        );
        
        $this->stats['spam_comments_deleted'] = $deleted;
    }
    
    /**
     * 휴지통 비우기
     */
    private function empty_trash() {
        global $wpdb;
        
        $deleted = $wpdb->delete(
            $wpdb->posts,
            array('post_status' => 'trash'),
            array('%s')
        );
        
        $this->stats['trashed_posts_deleted'] = $deleted;
    }
    
    /**
     * 고아 메타데이터 정리
     */
    private function clean_orphaned_metadata() {
        global $wpdb;
        
        // 존재하지 않는 포스트의 메타데이터 삭제
        $query = "
            DELETE pm
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.ID IS NULL
        ";
        
        $deleted = $wpdb->query($query);
        $this->stats['orphaned_postmeta_deleted'] = $deleted;
    }
    
    /**
     * 트랜지언트 정리
     */
    private function clean_transients() {
        global $wpdb;
        
        // 만료된 트랜지언트 삭제
        $deleted = $wpdb->query("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_timeout_%'
            AND option_value < UNIX_TIMESTAMP()
        ");
        
        // 연관된 트랜지언트 데이터 삭제
        $wpdb->query("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_%'
            AND option_name NOT IN (
                SELECT CONCAT('_transient_', SUBSTRING(option_name, 19))
                FROM (
                    SELECT option_name
                    FROM {$wpdb->options}
                    WHERE option_name LIKE '_transient_timeout_%'
                ) AS t
            )
        ");
        
        $this->stats['transients_deleted'] = $deleted;
    }
    
    /**
     * 테이블 최적화
     */
    private function optimize_tables() {
        global $wpdb;
        
        $tables = $wpdb->get_col("SHOW TABLES");
        $optimized = 0;
        
        foreach ($tables as $table) {
            if (strpos($table, $wpdb->prefix) === 0) {
                $wpdb->query("OPTIMIZE TABLE $table");
                $optimized++;
            }
        }
        
        $this->stats['tables_optimized'] = $optimized;
    }
    
    /**
     * 데이터베이스 크기 계산
     */
    private function get_database_size() {
        global $wpdb;
        
        $result = $wpdb->get_row("
            SELECT 
                SUM(data_length + index_length) AS size
            FROM information_schema.TABLES
            WHERE table_schema = '" . DB_NAME . "'
        ");
        
        return $result ? $result->size : 0;
    }
    
    /**
     * 백업 테이블 생성
     */
    public function create_backup_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->options
        );
        
        $backup_suffix = '_backup_' . date('Ymd_His');
        
        foreach ($tables as $table) {
            $wpdb->query("CREATE TABLE {$table}{$backup_suffix} LIKE {$table}");
            $wpdb->query("INSERT INTO {$table}{$backup_suffix} SELECT * FROM {$table}");
        }
        
        return $backup_suffix;
    }
}

// 관리자 페이지 추가
if (is_admin()) {
    add_action('admin_menu', function() {
        add_submenu_page(
            'tools.php',
            '데이터베이스 최적화',
            'DB 최적화',
            'manage_options',
            'db-optimizer',
            'sungsuya_db_optimizer_page'
        );
    });
}

function sungsuya_db_optimizer_page() {
    ?>
    <div class="wrap">
        <h1>데이터베이스 최적화 도구</h1>
        
        <?php
        if (isset($_POST['optimize_db']) && wp_verify_nonce($_POST['_wpnonce'], 'optimize_db')) {
            $optimizer = new Sungsuya_Database_Optimizer();
            
            // 백업 생성 (선택사항)
            if (isset($_POST['create_backup'])) {
                $backup_suffix = $optimizer->create_backup_tables();
                echo '<div class="notice notice-info"><p>백업 테이블 생성됨: *' . $backup_suffix . '</p></div>';
            }
            
            // 최적화 실행
            $stats = $optimizer->optimize();
            
            ?>
            <div class="notice notice-success">
                <h2>최적화 완료!</h2>
                <ul>
                    <li>삭제된 리비전: <?php echo number_format($stats['revisions_deleted']); ?>개</li>
                    <li>삭제된 자동 임시저장: <?php echo number_format($stats['auto_drafts_deleted']); ?>개</li>
                    <li>삭제된 스팸 댓글: <?php echo number_format($stats['spam_comments_deleted']); ?>개</li>
                    <li>삭제된 휴지통 포스트: <?php echo number_format($stats['trashed_posts_deleted']); ?>개</li>
                    <li>삭제된 고아 메타데이터: <?php echo number_format($stats['orphaned_postmeta_deleted']); ?>개</li>
                    <li>삭제된 트랜지언트: <?php echo number_format($stats['transients_deleted']); ?>개</li>
                    <li>최적화된 테이블: <?php echo $stats['tables_optimized']; ?>개</li>
                    <li>절약된 공간: <?php echo size_format($stats['space_saved']); ?></li>
                </ul>
            </div>
            <?php
        }
        
        // 현재 데이터베이스 상태
        global $wpdb;
        
        $revision_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'");
        $auto_draft_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'");
        $spam_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'");
        $trash_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'");
        ?>
        
        <div class="card">
            <h2>현재 데이터베이스 상태</h2>
            <table class="widefat">
                <tr>
                    <th>항목</th>
                    <th>개수</th>
                </tr>
                <tr>
                    <td>포스트 리비전</td>
                    <td><?php echo number_format($revision_count); ?>개</td>
                </tr>
                <tr>
                    <td>자동 임시저장</td>
                    <td><?php echo number_format($auto_draft_count); ?>개</td>
                </tr>
                <tr>
                    <td>스팸 댓글</td>
                    <td><?php echo number_format($spam_count); ?>개</td>
                </tr>
                <tr>
                    <td>휴지통 포스트</td>
                    <td><?php echo number_format($trash_count); ?>개</td>
                </tr>
            </table>
        </div>
        
        <form method="post">
            <?php wp_nonce_field('optimize_db'); ?>
            
            <div class="card">
                <h2>최적화 옵션</h2>
                
                <p>
                    <label>
                        <input type="checkbox" name="create_backup" value="1" checked>
                        최적화 전 주요 테이블 백업 생성
                    </label>
                </p>
                
                <p class="description">
                    이 도구는 다음 작업을 수행합니다:
                </p>
                <ul>
                    <li>• 오래된 리비전 삭제 (최근 5개만 유지)</li>
                    <li>• 자동 임시저장 삭제</li>
                    <li>• 스팸 댓글 삭제</li>
                    <li>• 휴지통 비우기</li>
                    <li>• 고아 메타데이터 정리</li>
                    <li>• 만료된 트랜지언트 삭제</li>
                    <li>• 모든 테이블 최적화</li>
                </ul>
                
                <p class="submit">
                    <input type="submit" name="optimize_db" class="button button-primary" 
                           value="데이터베이스 최적화 시작" 
                           onclick="return confirm('데이터베이스 최적화를 시작하시겠습니까?');">
                </p>
            </div>
        </form>
    </div>
    
    <style>
    .card {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 20px;
        margin-top: 20px;
    }
    .card table {
        margin-top: 10px;
    }
    </style>
    <?php
}
