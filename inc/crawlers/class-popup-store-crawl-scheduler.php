<?php
/**
 * 팝업스토어 크롤링 스케줄러
 * 
 * 매일 00:30에 자동으로 팝업스토어 정보를 수집합니다.
 * 
 * @package SungsuyaV2
 * @since 2.0.0
 */

class PopupStoreCrawlScheduler {
    
    /**
     * 크롤링 상태 옵션 키
     */
    const CRAWL_STATUS_OPTION = 'sungsuya_popup_crawl_status';
    const CRAWL_RESULTS_OPTION = 'sungsuya_popup_crawl_results';
    const CRAWL_LOG_OPTION = 'sungsuya_popup_crawl_log';
    
    /**
     * 생성자
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * 초기화
     */
    public function init() {
        // 크롤링 스케줄 등록
        add_action('init', array($this, 'register_crawl_schedule'));
        add_action('sungsuya_popup_crawl_event', array($this, 'execute_crawl'));
        
        // 관리자 메뉴 추가
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // AJAX 핸들러
        add_action('wp_ajax_popup_crawl_test', array($this, 'ajax_test_crawl'));
        add_action('wp_ajax_popup_crawl_get_log', array($this, 'ajax_get_log'));
        add_action('wp_ajax_popup_crawl_download_csv', array($this, 'ajax_download_csv'));
    }
    
    /**
     * 크롤링 스케줄 등록
     */
    public function register_crawl_schedule() {
        if (!wp_next_scheduled('sungsuya_popup_crawl_event')) {
            // 한국 시간 기준 00:30 설정
            $timestamp = strtotime('today 00:30:00 Asia/Seoul');
            if ($timestamp < time()) {
                $timestamp = strtotime('tomorrow 00:30:00 Asia/Seoul');
            }
            wp_schedule_event($timestamp, 'daily', 'sungsuya_popup_crawl_event');
        }
    }
    
    /**
     * 크롤링 실행
     */
    public function execute_crawl($is_test = false) {
        // 크롤링 시작
        $this->update_status('running', '크롤링 시작');
        $this->log('🚀 팝업스토어 크롤링 시작', 'info');
        
        $results = array(
            'timestamp' => current_time('mysql'),
            'items' => array(),
            'stats' => array(
                'total' => 0,
                'new' => 0,
                'existing' => 0,
                'error' => 0
            )
        );
        
        try {
            // 멀티소스 크롤러 사용
            require_once get_template_directory() . '/inc/crawling/multi-source-crawler.php';
            $crawler = new MultiSourceCrawler();
            
            // 크롤링 실행
            $all_results = $crawler->crawl_all('성수동 팝업스토어');
            
            // 크롤러 로그 가져오기
            foreach ($crawler->get_log() as $log_entry) {
                $this->log($log_entry['message'], 'info');
            }
            
            // 결과 변환 (멀티소스 크롤러 형식 → 스케줄러 형식)
            foreach ($all_results as $item) {
                $converted = array(
                    'source' => $item['source'],
                    'brand_name' => $this->extract_brand_name($item['name']),
                    'store_name' => $item['name'],
                    'address' => $item['address'],
                    'start_date' => isset($item['start_date']) ? $item['start_date'] : '',
                    'end_date' => isset($item['end_date']) ? $item['end_date'] : '',
                    'description' => isset($item['raw_data']['description']) ? $item['raw_data']['description'] : '',
                    'confidence_score' => $item['confidence'],
                    'source_url' => isset($item['source_url']) ? $item['source_url'] : '',
                    'found_at' => current_time('mysql')
                );
                
                // 인스타그램 계정 추가
                if ($item['source'] === 'instagram' && isset($item['raw_data']['hashtags'])) {
                    $converted['instagram_account'] = '@' . $item['source'];
                }
                
                $results['items'][] = $converted;
            }
            
            // 3. 중복 제거 및 통계
            $results['items'] = $this->remove_duplicates($results['items']);
            $results['stats']['total'] = count($results['items']);
            
            // 4. 신규/기존 분류
            foreach ($results['items'] as &$item) {
                if ($this->is_existing_popup($item)) {
                    $item['status'] = 'existing';
                    $results['stats']['existing']++;
                } else {
                    $item['status'] = 'new';
                    $results['stats']['new']++;
                }
            }
            
            // 5. 결과 저장
            update_option(self::CRAWL_RESULTS_OPTION, $results);
            
            // 6. Excel/CSV 파일 생성
            $this->generate_report($results);
            
            $this->log('📊 크롤링 완료: 총 ' . $results['stats']['total'] . '개 (신규 ' . $results['stats']['new'] . '개)', 'success');
            $this->update_status('completed', '크롤링 완료');
            
        } catch (Exception $e) {
            $this->log('❌ 크롤링 오류: ' . $e->getMessage(), 'error');
            $this->update_status('error', $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * 브랜드명 추출
     */
    private function extract_brand_name($store_name) {
        // "브랜드명 팝업스토어" 형식에서 브랜드명 추출
        $patterns = array(
            '/^(.+?)\s*팝업스토어?$/u',
            '/^(.+?)\s*pop.?up/i',
            '/^(.+?)\s*플래그십/u',
            '/^(.+?)\s*체험/u',
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $store_name, $matches)) {
                return trim($matches[1]);
            }
        }
        
        // 패턴 매칭 실패 시 전체 이름 반환
        return $store_name;
    }
    
    /**
     * 네이버 검색 크롤링 (시뮬레이션)
     */
    private function crawl_naver_search() {
        // 실제 구현 시에는 Puppeteer/Playwright 사용
        // 현재는 시뮬레이션 데이터
        $search_results = array(
            array(
                'source' => 'naver',
                'brand_name' => '아디다스',
                'store_name' => '아디다스 오리지널스 팝업',
                'address' => '서울 성동구 연무장길 5',
                'start_date' => '2025-06-20',
                'end_date' => '2025-07-20',
                'description' => '여름 컬렉션 한정 팝업스토어',
                'confidence_score' => 4,
                'source_url' => 'https://blog.naver.com/example1',
                'found_at' => current_time('mysql')
            ),
            array(
                'source' => 'naver',
                'brand_name' => '삼성전자',
                'store_name' => '갤럭시 체험존',
                'address' => '서울 성동구 성수일로8길 10',
                'start_date' => '2025-06-25',
                'end_date' => '2025-07-10',
                'description' => '갤럭시 신제품 체험 공간',
                'confidence_score' => 5,
                'source_url' => 'https://blog.naver.com/example2',
                'found_at' => current_time('mysql')
            ),
            array(
                'source' => 'naver',
                'brand_name' => '이솝',
                'store_name' => '이솝 성수 팝업',
                'address' => '성수동 연무장15길 20',
                'start_date' => '2025-07-01',
                'end_date' => '2025-07-31',
                'description' => '신제품 론칭 팝업스토어',
                'confidence_score' => 3,
                'source_url' => 'https://blog.naver.com/example3',
                'found_at' => current_time('mysql')
            )
        );
        
        return $search_results;
    }
    
    /**
     * 인스타그램 크롤링 (시뮬레이션)
     */
    private function crawl_instagram() {
        // 실제 구현 시에는 Instagram API 또는 크롤링
        // 현재는 시뮬레이션 데이터
        $instagram_results = array(
            array(
                'source' => 'instagram',
                'brand_name' => '토리버치',
                'store_name' => '토리버치 서머 팝업',
                'address' => '성수동 연무장길',
                'start_date' => '2025-06-15',
                'end_date' => '2025-07-15',
                'description' => '#토리버치 #성수동팝업 #여름컬렉션',
                'confidence_score' => 3,
                'source_url' => 'https://instagram.com/p/example1',
                'instagram_account' => '@toryburch_kr',
                'found_at' => current_time('mysql')
            ),
            array(
                'source' => 'instagram',
                'brand_name' => '무인양품',
                'store_name' => 'MUJI to GO 팝업',
                'address' => '성수일로4길',
                'start_date' => '2025-06-28',
                'end_date' => '2025-07-28',
                'description' => '여행 특화 팝업스토어 #무지 #성수동',
                'confidence_score' => 4,
                'source_url' => 'https://instagram.com/p/example2',
                'instagram_account' => '@mujikorea',
                'found_at' => current_time('mysql')
            )
        );
        
        return $instagram_results;
    }
    
    /**
     * 중복 제거
     */
    private function remove_duplicates($items) {
        $unique_items = array();
        $seen = array();
        
        foreach ($items as $item) {
            $key = $item['brand_name'] . '_' . $item['address'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique_items[] = $item;
            }
        }
        
        return $unique_items;
    }
    
    /**
     * 기존 팝업스토어 확인
     */
    private function is_existing_popup($item) {
        global $wpdb;
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'places' 
            AND p.post_status IN ('publish', 'draft')
            AND pm.meta_key = 'brand_name'
            AND pm.meta_value = %s",
            $item['brand_name']
        ));
        
        return $existing > 0;
    }
    
    /**
     * 보고서 생성 (Excel 우선, 실패시 CSV)
     */
    private function generate_report($results) {
        // Excel Reporter 로드
        require_once get_template_directory() . '/inc/crawlers/class-popup-store-excel-reporter.php';
        
        $reporter = new PopupStoreExcelReporter();
        
        // Excel 생성 시도
        $this->log('📑 Excel 보고서 생성 중...', 'info');
        $report = $reporter->generate($results);
        
        if ($report) {
            $this->log('✅ 보고서 생성 완료: ' . $report['filename'], 'success');
            
            // 최근 파일 정보 저장
            update_option('sungsuya_latest_crawl_report', array(
                'filename' => $report['filename'],
                'filepath' => $report['filepath'],
                'url' => $report['url'],
                'created' => current_time('mysql'),
                'type' => strpos($report['filename'], '.xlsx') !== false ? 'excel' : 'csv'
            ));
        } else {
            $this->log('❌ 보고서 생성 실패', 'error');
        }
        
        return $report;
    }
    
    /**
     * CSV 파일 생성 (레거시 메서드)
     */
    private function generate_csv($results) {
        $upload_dir = wp_upload_dir();
        $csv_dir = $upload_dir['basedir'] . '/popup-crawl-results';
        
        // 디렉토리 생성
        if (!file_exists($csv_dir)) {
            wp_mkdir_p($csv_dir);
        }
        
        // CSV 파일명
        $filename = 'popup_crawl_' . date('Ymd_Hi') . '.csv';
        $filepath = $csv_dir . '/' . $filename;
        
        // CSV 생성
        $handle = fopen($filepath, 'w');
        
        // BOM 추가 (Excel 한글 깨짐 방지)
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // 헤더
        $headers = array('발견일시', '출처', '브랜드명', '스토어명', '주소', '시작일', '종료일', '설명', '신뢰도', '상태', '참고URL', '인스타그램');
        fputcsv($handle, $headers);
        
        // 데이터
        foreach ($results['items'] as $item) {
            $row = array(
                $item['found_at'],
                $item['source'],
                $item['brand_name'],
                $item['store_name'],
                $item['address'],
                $item['start_date'],
                $item['end_date'],
                $item['description'],
                $this->get_confidence_stars($item['confidence_score']),
                $item['status'] === 'new' ? '신규' : '기존',
                $item['source_url'],
                isset($item['instagram_account']) ? $item['instagram_account'] : ''
            );
            fputcsv($handle, $row);
        }
        
        fclose($handle);
        
        // 최근 파일 정보 저장
        update_option('sungsuya_latest_crawl_csv', array(
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => $upload_dir['baseurl'] . '/popup-crawl-results/' . $filename,
            'created' => current_time('mysql')
        ));
        
        return $filepath;
    }
    
    /**
     * 신뢰도 점수를 별로 변환
     */
    private function get_confidence_stars($score) {
        $stars = str_repeat('⭐', $score);
        return $stars . ' (' . $score . '점)';
    }
    
    /**
     * 상태 업데이트
     */
    private function update_status($status, $message = '') {
        update_option(self::CRAWL_STATUS_OPTION, array(
            'status' => $status,
            'message' => $message,
            'last_update' => current_time('mysql')
        ));
    }
    
    /**
     * 로그 기록
     */
    private function log($message, $type = 'info') {
        $logs = get_option(self::CRAWL_LOG_OPTION, array());
        
        $logs[] = array(
            'timestamp' => current_time('mysql'),
            'type' => $type,
            'message' => $message
        );
        
        // 최근 100개만 유지
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option(self::CRAWL_LOG_OPTION, $logs);
    }
    
    /**
     * 관리자 메뉴 추가
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=places',
            '팝업스토어 크롤링',
            '🔍 팝업 크롤링',
            'manage_options',
            'popup-crawling',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * 관리자 페이지 렌더링
     */
    public function render_admin_page() {
        $status = get_option(self::CRAWL_STATUS_OPTION, array());
        $results = get_option(self::CRAWL_RESULTS_OPTION, array());
        $latest_csv = get_option('sungsuya_latest_crawl_csv', array());
        $latest_report = get_option('sungsuya_latest_crawl_report', array());
        
        ?>
        <div class="wrap">
            <h1>🔍 팝업스토어 크롤링</h1>
            
            <!-- 상태 표시 -->
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h2>크롤링 상태</h2>
                <p><strong>현재 상태:</strong> <?php echo $this->get_status_display($status); ?></p>
                <p><strong>마지막 업데이트:</strong> <?php echo isset($status['last_update']) ? $status['last_update'] : '없음'; ?></p>
                <?php if (!empty($status['message'])): ?>
                    <p><strong>메시지:</strong> <?php echo esc_html($status['message']); ?></p>
                <?php endif; ?>
                
                <p>
                    <button class="button button-primary" onclick="testCrawl()">지금 크롤링 실행</button>
                    <button class="button" onclick="refreshLog()">로그 새로고침</button>
                    <a href="/test-multi-crawling.php" target="_blank" class="button" style="background: #ff6b6b;">멀티소스 테스트</a>
                </p>
            </div>
            
            <!-- 최근 결과 -->
            <?php if (!empty($results)): ?>
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h2>최근 크롤링 결과</h2>
                <p><strong>실행 시간:</strong> <?php echo $results['timestamp']; ?></p>
                <p><strong>발견 항목:</strong> 총 <?php echo $results['stats']['total']; ?>개 
                    (신규 <?php echo $results['stats']['new']; ?>개, 기존 <?php echo $results['stats']['existing']; ?>개)</p>
                
                <?php 
                // 최신 보고서 우선 표시
                $download_file = !empty($latest_report) ? $latest_report : $latest_csv;
                if (!empty($download_file)): 
                ?>
                    <p>
                        <a href="<?php echo esc_url($download_file['url']); ?>" class="button" download>
                            <?php echo $download_file['type'] === 'excel' ? '📊' : '📥'; ?> 
                            <?php echo $download_file['type'] === 'excel' ? 'Excel' : 'CSV'; ?> 다운로드
                        </a>
                        <span style="color: #666; margin-left: 10px;">
                            파일명: <?php echo esc_html($download_file['filename']); ?>
                        </span>
                    </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- 실시간 로그 -->
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h2>크롤링 로그</h2>
                <div id="crawl-log" style="background: #f5f5f5; padding: 10px; height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                    로그 로딩 중...
                </div>
            </div>
            
            <!-- 스케줄 정보 -->
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h2>스케줄 정보</h2>
                <?php
                $next_scheduled = wp_next_scheduled('sungsuya_popup_crawl_event');
                if ($next_scheduled) {
                    echo '<p><strong>다음 실행 예정:</strong> ' . date('Y-m-d H:i:s', $next_scheduled) . '</p>';
                } else {
                    echo '<p>스케줄이 설정되지 않았습니다.</p>';
                }
                ?>
                <p>크롤링은 매일 00:30에 자동으로 실행됩니다.</p>
            </div>
        </div>
        
        <script>
        function testCrawl() {
            if (!confirm('크롤링을 실행하시겠습니까?')) return;
            
            jQuery.post(ajaxurl, {
                action: 'popup_crawl_test',
                _ajax_nonce: '<?php echo wp_create_nonce('popup_crawl_test'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('크롤링이 시작되었습니다. 로그를 확인하세요.');
                    refreshLog();
                } else {
                    alert('오류: ' + response.data);
                }
            });
        }
        
        function refreshLog() {
            jQuery.post(ajaxurl, {
                action: 'popup_crawl_get_log',
                _ajax_nonce: '<?php echo wp_create_nonce('popup_crawl_get_log'); ?>'
            }, function(response) {
                if (response.success) {
                    jQuery('#crawl-log').html(response.data);
                    jQuery('#crawl-log').scrollTop(jQuery('#crawl-log')[0].scrollHeight);
                }
            });
        }
        
        // 페이지 로드 시 로그 불러오기
        jQuery(document).ready(function() {
            refreshLog();
        });
        </script>
        <?php
    }
    
    /**
     * 상태 표시 텍스트
     */
    private function get_status_display($status) {
        if (empty($status['status'])) {
            return '⏸️ 대기 중';
        }
        
        switch ($status['status']) {
            case 'running':
                return '🔄 실행 중';
            case 'completed':
                return '✅ 완료';
            case 'error':
                return '❌ 오류';
            default:
                return '❓ 알 수 없음';
        }
    }
    
    /**
     * AJAX: 테스트 크롤링
     */
    public function ajax_test_crawl() {
        check_ajax_referer('popup_crawl_test');
        
        if (!current_user_can('manage_options')) {
            wp_die('권한이 없습니다.');
        }
        
        // 백그라운드로 크롤링 실행
        $this->execute_crawl(true);
        
        wp_send_json_success('크롤링이 시작되었습니다.');
    }
    
    /**
     * AJAX: 로그 가져오기
     */
    public function ajax_get_log() {
        check_ajax_referer('popup_crawl_get_log');
        
        if (!current_user_can('manage_options')) {
            wp_die('권한이 없습니다.');
        }
        
        $logs = get_option(self::CRAWL_LOG_OPTION, array());
        $html = '';
        
        if (empty($logs)) {
            $html = '로그가 없습니다.';
        } else {
            foreach ($logs as $log) {
                $type_class = '';
                switch ($log['type']) {
                    case 'success':
                        $type_class = 'color: green;';
                        break;
                    case 'error':
                        $type_class = 'color: red;';
                        break;
                    case 'info':
                        $type_class = 'color: blue;';
                        break;
                }
                
                $html .= sprintf(
                    '<div style="%s">[%s] %s</div>',
                    $type_class,
                    $log['timestamp'],
                    esc_html($log['message'])
                );
            }
        }
        
        wp_send_json_success($html);
    }
}

// 인스턴스 생성
new PopupStoreCrawlScheduler();
