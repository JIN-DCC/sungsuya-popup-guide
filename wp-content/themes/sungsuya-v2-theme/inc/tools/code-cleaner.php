<?php
/**
 * 프로덕션 배포를 위한 코드 정리 스크립트
 * 
 * console.log, 주석, 테스트 코드 등을 제거
 * 
 * @package SungsuyaV2
 * @version 1.0.0
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 코드 정리 클래스
 */
class Sungsuya_Code_Cleaner {
    
    /**
     * 정리할 파일 확장자
     */
    private $extensions = array('js', 'php', 'css');
    
    /**
     * 제외할 디렉토리
     */
    private $exclude_dirs = array(
        'node_modules',
        'vendor',
        '.git',
        'backups'
    );
    
    /**
     * 정리 통계
     */
    private $stats = array(
        'files_processed' => 0,
        'console_logs_removed' => 0,
        'comments_removed' => 0,
        'debug_code_removed' => 0,
        'files_cleaned' => array()
    );
    
    /**
     * 코드 정리 실행
     */
    public function clean($directory) {
        $this->process_directory($directory);
        return $this->stats;
    }
    
    /**
     * 디렉토리 재귀 처리
     */
    private function process_directory($dir) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isFile() && $this->should_process($file)) {
                $this->process_file($file->getPathname());
            }
        }
    }
    
    /**
     * 파일 처리 여부 확인
     */
    private function should_process($file) {
        $path = $file->getPathname();
        $extension = $file->getExtension();
        
        // 제외 디렉토리 확인
        foreach ($this->exclude_dirs as $exclude) {
            if (strpos($path, $exclude) !== false) {
                return false;
            }
        }
        
        // 확장자 확인
        return in_array($extension, $this->extensions);
    }
    
    /**
     * 파일 정리
     */
    private function process_file($filepath) {
        $content = file_get_contents($filepath);
        $original_content = $content;
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'js':
                $content = $this->clean_javascript($content);
                break;
            case 'php':
                $content = $this->clean_php($content);
                break;
            case 'css':
                $content = $this->clean_css($content);
                break;
        }
        
        // 변경사항이 있으면 파일 저장
        if ($content !== $original_content) {
            file_put_contents($filepath, $content);
            $this->stats['files_cleaned'][] = $filepath;
        }
        
        $this->stats['files_processed']++;
    }
    
    /**
     * JavaScript 정리
     */
    private function clean_javascript($content) {
        // console.log 제거
        $pattern = '/console\.(log|error|warn|info|debug|trace)\([^;]*\);?/';
        $matches = preg_match_all($pattern, $content);
        if ($matches) {
            $this->stats['console_logs_removed'] += $matches;
            $content = preg_replace($pattern, '', $content);
        }
        
        // 디버그 코드 제거
        $content = preg_replace('/\/\/\s*DEBUG:.*$/m', '', $content);
        $content = preg_replace('/\/\/\s*TODO:.*$/m', '', $content);
        $content = preg_replace('/\/\/\s*FIXME:.*$/m', '', $content);
        
        // 멀티라인 주석 제거 (JSDoc 제외)
        $content = preg_replace('/\/\*(?!\*)[^*]*\*+(?:[^/*][^*]*\*+)*\//', '', $content);
        
        // 빈 줄 정리
        $content = preg_replace('/^\s*[\r\n]+/m', "\n", $content);
        
        return $content;
    }
    
    /**
     * PHP 정리
     */
    private function clean_php($content) {
        // error_log 제거
        $pattern = '/error_log\([^;]*\);?/';
        $matches = preg_match_all($pattern, $content);
        if ($matches) {
            $this->stats['debug_code_removed'] += $matches;
            $content = preg_replace($pattern, '', $content);
        }
        
        // var_dump, print_r 제거
        $content = preg_replace('/var_dump\([^;]*\);?/', '', $content);
        $content = preg_replace('/print_r\([^;]*\);?/', '', $content);
        
        // 디버그 주석 제거
        $content = preg_replace('/\/\/\s*DEBUG:.*$/m', '', $content);
        $content = preg_replace('/\/\/\s*TODO:.*$/m', '', $content);
        $content = preg_replace('/\/\/\s*FIXME:.*$/m', '', $content);
        
        // 테스트 코드 블록 제거
        $content = preg_replace('/if\s*\(\s*WP_DEBUG\s*\)\s*\{[^}]*\}/s', '', $content);
        
        return $content;
    }
    
    /**
     * CSS 정리
     */
    private function clean_css($content) {
        // CSS 주석 제거
        $content = preg_replace('/\/\*[^*]*\*+(?:[^/*][^*]*\*+)*\//', '', $content);
        
        // 빈 줄 정리
        $content = preg_replace('/^\s*[\r\n]+/m', "\n", $content);
        
        // 연속된 공백 정리
        $content = preg_replace('/\s+/', ' ', $content);
        
        return $content;
    }
    
    /**
     * 백업 생성
     */
    public function create_backup($directory) {
        $backup_dir = $directory . '/backups/pre-production-' . date('Y-m-d-His');
        
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        // 중요 파일 백업
        $important_files = array(
            'functions.php',
            'style.css',
            'assets/js/app.js',
            'assets/css/style.css'
        );
        
        foreach ($important_files as $file) {
            $source = $directory . '/' . $file;
            if (file_exists($source)) {
                $dest = $backup_dir . '/' . $file;
                $dest_dir = dirname($dest);
                if (!file_exists($dest_dir)) {
                    mkdir($dest_dir, 0755, true);
                }
                copy($source, $dest);
            }
        }
        
        return $backup_dir;
    }
}

// 실행 (관리자 페이지에서만)
if (is_admin() && current_user_can('manage_options')) {
    add_action('admin_menu', function() {
        add_submenu_page(
            'tools.php',
            '코드 정리 도구',
            '코드 정리',
            'manage_options',
            'code-cleaner',
            'sungsuya_code_cleaner_page'
        );
    });
}

function sungsuya_code_cleaner_page() {
    ?>
    <div class="wrap">
        <h1>프로덕션 배포를 위한 코드 정리</h1>
        
        <?php
        if (isset($_POST['clean_code']) && wp_verify_nonce($_POST['_wpnonce'], 'clean_code')) {
            $cleaner = new Sungsuya_Code_Cleaner();
            
            // 백업 생성
            $backup_dir = $cleaner->create_backup(get_template_directory());
            echo '<div class="notice notice-info"><p>백업 생성됨: ' . $backup_dir . '</p></div>';
            
            // 코드 정리 실행
            $stats = $cleaner->clean(get_template_directory());
            
            ?>
            <div class="notice notice-success">
                <h2>정리 완료!</h2>
                <ul>
                    <li>처리된 파일: <?php echo $stats['files_processed']; ?>개</li>
                    <li>제거된 console.log: <?php echo $stats['console_logs_removed']; ?>개</li>
                    <li>제거된 디버그 코드: <?php echo $stats['debug_code_removed']; ?>개</li>
                    <li>정리된 파일: <?php echo count($stats['files_cleaned']); ?>개</li>
                </ul>
                
                <?php if (!empty($stats['files_cleaned'])): ?>
                <h3>정리된 파일 목록:</h3>
                <ul>
                    <?php foreach ($stats['files_cleaned'] as $file): ?>
                        <li><?php echo str_replace(get_template_directory(), '', $file); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php
        }
        ?>
        
        <form method="post">
            <?php wp_nonce_field('clean_code'); ?>
            
            <div class="card">
                <h2>코드 정리 기능</h2>
                <p>이 도구는 프로덕션 배포를 위해 다음 작업을 수행합니다:</p>
                <ul>
                    <li>✓ console.log, error_log 제거</li>
                    <li>✓ var_dump, print_r 제거</li>
                    <li>✓ DEBUG, TODO, FIXME 주석 제거</li>
                    <li>✓ 불필요한 주석 및 공백 정리</li>
                    <li>✓ WP_DEBUG 조건부 코드 제거</li>
                </ul>
                
                <p><strong>⚠️ 주의:</strong> 실행 전 백업이 자동으로 생성되지만, 전체 백업을 권장합니다.</p>
                
                <p class="submit">
                    <input type="submit" name="clean_code" class="button button-primary" 
                           value="코드 정리 시작" 
                           onclick="return confirm('코드 정리를 시작하시겠습니까? 백업이 자동으로 생성됩니다.');">
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
    </style>
    <?php
}
