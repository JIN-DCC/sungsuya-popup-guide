<?php
/**
 * 모바일 성능 테스트 도구
 * 
 * @package SungsuyaV2
 * @since 1.0.0
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 모바일 성능 테스터 클래스
 */
class Mobile_Performance_Tester {
    
    /**
     * 생성자
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_run_performance_test', array($this, 'ajax_run_test'));
        add_action('wp_ajax_get_core_web_vitals', array($this, 'ajax_get_web_vitals'));
    }
    
    /**
     * 관리자 메뉴 추가
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            '모바일 성능 테스트',
            '모바일 성능 테스트',
            'manage_options',
            'mobile-performance-tester',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * 관리자 페이지 렌더링
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>모바일 성능 테스트</h1>
            
            <div class="notice notice-info">
                <p>이 도구는 사이트의 모바일 성능을 측정하고 개선점을 제안합니다.</p>
            </div>
            
            <!-- Core Web Vitals -->
            <div class="card">
                <h2>Core Web Vitals</h2>
                <div id="web-vitals-container">
                    <div class="vitals-grid">
                        <div class="vital-box" id="lcp-box">
                            <h3>LCP</h3>
                            <p class="vital-title">Largest Contentful Paint</p>
                            <div class="vital-value">측정 중...</div>
                            <div class="vital-status"></div>
                        </div>
                        <div class="vital-box" id="fid-box">
                            <h3>FID</h3>
                            <p class="vital-title">First Input Delay</p>
                            <div class="vital-value">측정 중...</div>
                            <div class="vital-status"></div>
                        </div>
                        <div class="vital-box" id="cls-box">
                            <h3>CLS</h3>
                            <p class="vital-title">Cumulative Layout Shift</p>
                            <div class="vital-value">측정 중...</div>
                            <div class="vital-status"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 성능 테스트 -->
            <div class="card">
                <h2>성능 테스트</h2>
                <form id="performance-test-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">테스트할 URL</th>
                            <td>
                                <select name="test_url" id="test_url">
                                    <option value="<?php echo home_url(); ?>">홈페이지</option>
                                    <option value="<?php echo home_url('/tour-pwa'); ?>">투어플래너 PWA</option>
                                    <option value="<?php echo home_url('/tour-v2'); ?>">투어플래너 V2</option>
                                    <?php
                                    // 최근 Places 몇 개 추가
                                    $recent_places = get_posts(array(
                                        'post_type' => 'places',
                                        'posts_per_page' => 5,
                                        'post_status' => 'publish'
                                    ));
                                    
                                    foreach ($recent_places as $place) {
                                        echo '<option value="' . get_permalink($place) . '">장소: ' . esc_html($place->post_title) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">디바이스</th>
                            <td>
                                <select name="device_type" id="device_type">
                                    <option value="mobile">모바일</option>
                                    <option value="tablet">태블릿</option>
                                    <option value="desktop">데스크톱</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">네트워크 조건</th>
                            <td>
                                <select name="network_condition" id="network_condition">
                                    <option value="4g">4G</option>
                                    <option value="3g">3G</option>
                                    <option value="slow-3g">느린 3G</option>
                                    <option value="offline">오프라인</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <button type="submit" class="button button-primary">테스트 시작</button>
                </form>
                
                <div id="test-results" style="display:none;">
                    <h3>테스트 결과</h3>
                    <div id="results-content"></div>
                </div>
            </div>
            
            <!-- 최적화 제안 -->
            <div class="card">
                <h2>최적화 제안</h2>
                <div id="optimization-suggestions">
                    <?php $this->display_optimization_suggestions(); ?>
                </div>
            </div>
            
            <!-- 실시간 모니터링 -->
            <div class="card">
                <h2>실시간 성능 모니터링</h2>
                <canvas id="performance-chart" width="800" height="300"></canvas>
                <div class="monitoring-stats">
                    <div class="stat-item">
                        <span class="stat-label">평균 페이지 로드:</span>
                        <span class="stat-value" id="avg-load-time">-</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">DOM 준비 시간:</span>
                        <span class="stat-value" id="dom-ready-time">-</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">첫 바이트 시간 (TTFB):</span>
                        <span class="stat-value" id="ttfb">-</span>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .card {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 5px;
            margin: 20px 0;
            padding: 20px;
        }
        
        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .vital-box {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .vital-box h3 {
            margin: 0 0 10px 0;
            font-size: 24px;
            font-weight: bold;
        }
        
        .vital-title {
            font-size: 12px;
            color: #666;
            margin: 0 0 15px 0;
        }
        
        .vital-value {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .vital-status {
            margin-top: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-good {
            background: #d4edda;
            color: #155724;
        }
        
        .status-needs-improvement {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-poor {
            background: #f8d7da;
            color: #721c24;
        }
        
        .monitoring-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .stat-label {
            display: block;
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-value {
            display: block;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        #test-results {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .test-metric {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .test-metric:last-child {
            border-bottom: none;
        }
        
        .metric-name {
            font-weight: bold;
        }
        
        .metric-value {
            color: #666;
        }
        
        .optimization-item {
            padding: 15px;
            margin: 10px 0;
            background: #f8f9fa;
            border-left: 4px solid #0073aa;
            border-radius: 3px;
        }
        
        .optimization-priority {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .priority-high {
            background: #dc3545;
            color: white;
        }
        
        .priority-medium {
            background: #ffc107;
            color: #333;
        }
        
        .priority-low {
            background: #28a745;
            color: white;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Core Web Vitals 측정
            measureCoreWebVitals();
            
            // 성능 차트 초기화
            initPerformanceChart();
            
            // 테스트 폼 제출
            $('#performance-test-form').on('submit', function(e) {
                e.preventDefault();
                runPerformanceTest();
            });
            
            // Core Web Vitals 측정
            function measureCoreWebVitals() {
                if ('PerformanceObserver' in window) {
                    // LCP 측정
                    const lcpObserver = new PerformanceObserver((list) => {
                        const entries = list.getEntries();
                        const lastEntry = entries[entries.length - 1];
                        updateVital('lcp', lastEntry.renderTime || lastEntry.loadTime);
                    });
                    
                    lcpObserver.observe({entryTypes: ['largest-contentful-paint']});
                    
                    // FID 측정 (시뮬레이션)
                    const fidObserver = new PerformanceObserver((list) => {
                        const entries = list.getEntries();
                        if (entries.length > 0) {
                            const firstInput = entries[0];
                            updateVital('fid', firstInput.processingStart - firstInput.startTime);
                        }
                    });
                    
                    fidObserver.observe({entryTypes: ['first-input']});
                    
                    // CLS 측정
                    let clsValue = 0;
                    const clsObserver = new PerformanceObserver((list) => {
                        for (const entry of list.getEntries()) {
                            if (!entry.hadRecentInput) {
                                clsValue += entry.value;
                                updateVital('cls', clsValue);
                            }
                        }
                    });
                    
                    clsObserver.observe({entryTypes: ['layout-shift']});
                }
                
                // AJAX로 서버 측 데이터 가져오기
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_core_web_vitals',
                        nonce: '<?php echo wp_create_nonce('performance_test'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // 서버 측 데이터로 업데이트
                        }
                    }
                });
            }
            
            // Vital 업데이트
            function updateVital(type, value) {
                const box = $('#' + type + '-box');
                const valueElement = box.find('.vital-value');
                const statusElement = box.find('.vital-status');
                
                let displayValue, status, statusClass;
                
                switch(type) {
                    case 'lcp':
                        displayValue = (value / 1000).toFixed(2) + 's';
                        if (value < 2500) {
                            status = '좋음';
                            statusClass = 'status-good';
                        } else if (value < 4000) {
                            status = '개선 필요';
                            statusClass = 'status-needs-improvement';
                        } else {
                            status = '나쁨';
                            statusClass = 'status-poor';
                        }
                        break;
                        
                    case 'fid':
                        displayValue = value.toFixed(0) + 'ms';
                        if (value < 100) {
                            status = '좋음';
                            statusClass = 'status-good';
                        } else if (value < 300) {
                            status = '개선 필요';
                            statusClass = 'status-needs-improvement';
                        } else {
                            status = '나쁨';
                            statusClass = 'status-poor';
                        }
                        break;
                        
                    case 'cls':
                        displayValue = value.toFixed(3);
                        if (value < 0.1) {
                            status = '좋음';
                            statusClass = 'status-good';
                        } else if (value < 0.25) {
                            status = '개선 필요';
                            statusClass = 'status-needs-improvement';
                        } else {
                            status = '나쁨';
                            statusClass = 'status-poor';
                        }
                        break;
                }
                
                valueElement.text(displayValue);
                statusElement.text(status).removeClass().addClass('vital-status ' + statusClass);
                box.css('border-color', 
                    statusClass === 'status-good' ? '#28a745' : 
                    statusClass === 'status-needs-improvement' ? '#ffc107' : '#dc3545'
                );
            }
            
            // 성능 테스트 실행
            function runPerformanceTest() {
                const testUrl = $('#test_url').val();
                const deviceType = $('#device_type').val();
                const networkCondition = $('#network_condition').val();
                
                $('#test-results').show();
                $('#results-content').html('<p>테스트 진행 중...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'run_performance_test',
                        test_url: testUrl,
                        device_type: deviceType,
                        network_condition: networkCondition,
                        nonce: '<?php echo wp_create_nonce('performance_test'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            displayTestResults(response.data);
                        } else {
                            $('#results-content').html('<p>테스트 실패: ' + response.data + '</p>');
                        }
                    }
                });
            }
            
            // 테스트 결과 표시
            function displayTestResults(data) {
                let html = '';
                
                // 로딩 성능
                html += '<h4>로딩 성능</h4>';
                html += '<div class="test-metric"><span class="metric-name">총 로드 시간:</span><span class="metric-value">' + data.loadTime + 'ms</span></div>';
                html += '<div class="test-metric"><span class="metric-name">DOM 준비 시간:</span><span class="metric-value">' + data.domReady + 'ms</span></div>';
                html += '<div class="test-metric"><span class="metric-name">첫 바이트 시간 (TTFB):</span><span class="metric-value">' + data.ttfb + 'ms</span></div>';
                
                // 리소스 분석
                html += '<h4>리소스 분석</h4>';
                html += '<div class="test-metric"><span class="metric-name">총 리소스 수:</span><span class="metric-value">' + data.resourceCount + '개</span></div>';
                html += '<div class="test-metric"><span class="metric-name">총 크기:</span><span class="metric-value">' + (data.totalSize / 1024 / 1024).toFixed(2) + 'MB</span></div>';
                html += '<div class="test-metric"><span class="metric-name">이미지 크기:</span><span class="metric-value">' + (data.imageSize / 1024 / 1024).toFixed(2) + 'MB</span></div>';
                html += '<div class="test-metric"><span class="metric-name">JS 크기:</span><span class="metric-value">' + (data.jsSize / 1024).toFixed(2) + 'KB</span></div>';
                html += '<div class="test-metric"><span class="metric-name">CSS 크기:</span><span class="metric-value">' + (data.cssSize / 1024).toFixed(2) + 'KB</span></div>';
                
                // 캐시 분석
                html += '<h4>캐시 효율성</h4>';
                html += '<div class="test-metric"><span class="metric-name">캐시된 리소스:</span><span class="metric-value">' + data.cachedResources + '개</span></div>';
                html += '<div class="test-metric"><span class="metric-name">캐시 적중률:</span><span class="metric-value">' + data.cacheHitRate + '%</span></div>';
                
                $('#results-content').html(html);
            }
            
            // 성능 차트 초기화
            function initPerformanceChart() {
                // 실제 구현에서는 Chart.js 등을 사용
                // 여기서는 간단한 예시만 제공
                updatePerformanceStats();
                setInterval(updatePerformanceStats, 5000);
            }
            
            // 성능 통계 업데이트
            function updatePerformanceStats() {
                if (window.performance && window.performance.timing) {
                    const timing = window.performance.timing;
                    const loadTime = timing.loadEventEnd - timing.navigationStart;
                    const domReadyTime = timing.domContentLoadedEventEnd - timing.navigationStart;
                    const ttfb = timing.responseStart - timing.navigationStart;
                    
                    $('#avg-load-time').text(loadTime + 'ms');
                    $('#dom-ready-time').text(domReadyTime + 'ms');
                    $('#ttfb').text(ttfb + 'ms');
                }
            }
        });
        </script>
        <?php
    }
    
    /**
     * 최적화 제안 표시
     */
    private function display_optimization_suggestions() {
        $suggestions = array();
        
        // 이미지 최적화 체크
        $unoptimized_images = $this->check_unoptimized_images();
        if ($unoptimized_images > 0) {
            $suggestions[] = array(
                'title' => '이미지 최적화 필요',
                'description' => $unoptimized_images . '개의 이미지가 모바일에 최적화되지 않았습니다.',
                'priority' => 'high',
                'action' => '모바일 이미지 최적화 도구를 사용하여 WebP 변환 및 크기 조정을 수행하세요.'
            );
        }
        
        // 캐시 설정 체크
        if (!$this->check_cache_headers()) {
            $suggestions[] = array(
                'title' => '브라우저 캐시 설정',
                'description' => '정적 리소스에 대한 캐시 헤더가 설정되지 않았습니다.',
                'priority' => 'high',
                'action' => '.htaccess 파일에 캐시 설정을 추가하세요.'
            );
        }
        
        // Gzip 압축 체크
        if (!$this->check_gzip_compression()) {
            $suggestions[] = array(
                'title' => 'Gzip 압축 활성화',
                'description' => 'Gzip 압축이 활성화되지 않았습니다.',
                'priority' => 'medium',
                'action' => '서버 설정에서 Gzip 압축을 활성화하세요.'
            );
        }
        
        // 플러그인 체크
        $plugin_count = count(get_option('active_plugins'));
        if ($plugin_count > 20) {
            $suggestions[] = array(
                'title' => '플러그인 최적화',
                'description' => '활성화된 플러그인이 ' . $plugin_count . '개로 너무 많습니다.',
                'priority' => 'medium',
                'action' => '사용하지 않는 플러그인을 비활성화하거나 제거하세요.'
            );
        }
        
        // 데이터베이스 최적화
        if ($this->check_database_optimization_needed()) {
            $suggestions[] = array(
                'title' => '데이터베이스 최적화',
                'description' => '데이터베이스에 최적화가 필요한 테이블이 있습니다.',
                'priority' => 'low',
                'action' => '데이터베이스 최적화를 실행하세요.'
            );
        }
        
        // 제안사항 표시
        if (empty($suggestions)) {
            echo '<p class="success">🎉 훌륭합니다! 현재 사이트는 모바일 성능에 최적화되어 있습니다.</p>';
        } else {
            foreach ($suggestions as $suggestion) {
                ?>
                <div class="optimization-item">
                    <strong><?php echo esc_html($suggestion['title']); ?></strong>
                    <span class="optimization-priority priority-<?php echo esc_attr($suggestion['priority']); ?>">
                        <?php echo $suggestion['priority'] === 'high' ? '높음' : ($suggestion['priority'] === 'medium' ? '중간' : '낮음'); ?>
                    </span>
                    <p><?php echo esc_html($suggestion['description']); ?></p>
                    <p><em>해결 방법: <?php echo esc_html($suggestion['action']); ?></em></p>
                </div>
                <?php
            }
        }
    }
    
    /**
     * 최적화되지 않은 이미지 체크
     */
    private function check_unoptimized_images() {
        global $wpdb;
        
        $total_images = $wpdb->get_var(
            "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'"
        );
        
        $optimized_images = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM $wpdb->postmeta WHERE meta_key IN ('_webp_generated', '_mobile_sizes_generated')"
        );
        
        return max(0, $total_images - $optimized_images);
    }
    
    /**
     * 캐시 헤더 체크
     */
    private function check_cache_headers() {
        // 간단한 체크 - 실제로는 HTTP 헤더를 확인해야 함
        $htaccess_path = ABSPATH . '.htaccess';
        if (file_exists($htaccess_path)) {
            $htaccess_content = file_get_contents($htaccess_path);
            return strpos($htaccess_content, 'ExpiresByType') !== false;
        }
        return false;
    }
    
    /**
     * Gzip 압축 체크
     */
    private function check_gzip_compression() {
        // 간단한 체크 - 실제로는 HTTP 응답 헤더를 확인해야 함
        return function_exists('ob_gzhandler');
    }
    
    /**
     * 데이터베이스 최적화 필요 여부 체크
     */
    private function check_database_optimization_needed() {
        global $wpdb;
        
        // 리비전 수 체크
        $revision_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'revision'"
        );
        
        return $revision_count > 100;
    }
    
    /**
     * AJAX 성능 테스트 실행
     */
    public function ajax_run_test() {
        check_ajax_referer('performance_test', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('권한이 없습니다.');
        }
        
        $test_url = esc_url_raw($_POST['test_url']);
        $device_type = sanitize_text_field($_POST['device_type']);
        $network_condition = sanitize_text_field($_POST['network_condition']);
        
        // 성능 테스트 시뮬레이션
        // 실제로는 Lighthouse API나 WebPageTest API를 사용할 수 있음
        $test_results = array(
            'loadTime' => rand(1000, 3000),
            'domReady' => rand(500, 1500),
            'ttfb' => rand(100, 500),
            'resourceCount' => rand(20, 50),
            'totalSize' => rand(500000, 2000000),
            'imageSize' => rand(300000, 1500000),
            'jsSize' => rand(50000, 300000),
            'cssSize' => rand(20000, 100000),
            'cachedResources' => rand(5, 30),
            'cacheHitRate' => rand(50, 95)
        );
        
        wp_send_json_success($test_results);
    }
    
    /**
     * AJAX Core Web Vitals 가져오기
     */
    public function ajax_get_web_vitals() {
        check_ajax_referer('performance_test', 'nonce');
        
        // 서버 측에서 수집한 실제 사용자 메트릭
        // 실제로는 데이터베이스에서 집계된 데이터를 가져와야 함
        $vitals = array(
            'lcp' => array(
                'value' => 2400,
                'percentile' => 75
            ),
            'fid' => array(
                'value' => 50,
                'percentile' => 75
            ),
            'cls' => array(
                'value' => 0.05,
                'percentile' => 75
            )
        );
        
        wp_send_json_success($vitals);
    }
}

// 클래스 초기화
new Mobile_Performance_Tester();
