<?php
/**
 * 🗺️ 통합 지도생성 시스템 - 핵심 클래스
 * 
 * 중앙집중식 지도 처리 시스템
 * - 배치 지오코딩 처리
 * - 실시간 진행상황 모니터링
 * - 관리자 인터페이스 통합
 * 
 * @package SungsuyaV2
 * @version 1.0.0
 * @since 2025-06-25
 */

if (!defined('ABSPATH')) {
    exit;
}

// 배치 지오코딩 프로세서 로드
require_once get_template_directory() . '/inc/admin/batch-geocoding-processor.php';

class IntegratedMapGenerationSystem {
    
    const VERSION = '1.0.0';
    const MENU_SLUG = 'integrated-map-generation';
    const PROGRESS_OPTION_KEY = 'integrated_map_generation_progress';
    const SESSION_OPTION_KEY = 'integrated_map_generation_session';
    
    private $batch_processor;
    
    /**
     * 생성자
     */
    public function __construct() {
        // 관리자 전용 기능으로 제한
        if (!is_admin()) {
            return;
        }
        
        $this->batch_processor = new BatchGeocodingProcessor();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX 핸들러들
        add_action('wp_ajax_integrated_map_get_initial_data', array($this, 'ajax_get_initial_data'));
        add_action('wp_ajax_integrated_map_start_batch_processing', array($this, 'ajax_start_batch_processing'));
        add_action('wp_ajax_integrated_map_check_progress', array($this, 'ajax_check_progress'));
        add_action('wp_ajax_integrated_map_stop_processing', array($this, 'ajax_stop_processing'));
        add_action('wp_ajax_integrated_map_fix_individual_place', array($this, 'ajax_fix_individual_place'));
        add_action('wp_ajax_integrated_map_regenerate_map', array($this, 'ajax_regenerate_map'));
        add_action('wp_ajax_integrated_map_test_api', array($this, 'ajax_test_api'));
        
        $this->init_debug_logging();
    }
    
    /**
     * 관리자 메뉴 추가
     * 주의: reorganized-admin-menu.php와 places-menu-map-generation.php에서 이미 메뉴를 추가하므로 여기서는 비활성화
     */
    public function add_admin_menu() {
        // 중복 메뉴 방지를 위해 주석 처리
        // add_submenu_page(
        //     'edit.php?post_type=places',
        //     '통합 지도생성 시스템',
        //     '🗺️ 지도 생성 시스템',
        //     'manage_options',
        //     self::MENU_SLUG,
        //     array($this, 'render_admin_page')
        // );
    }
    
    /**
     * 관리자 스크립트 및 스타일 로드
     */
    public function enqueue_admin_scripts($hook_suffix) {
        if (strpos($hook_suffix, self::MENU_SLUG) === false) {
            return;
        }
        
        // CSS 파일
        wp_enqueue_style(
            'integrated-map-generation-css',
            get_template_directory_uri() . '/admin/assets/integrated-map-generation.css',
            array(),
            self::VERSION
        );
        
        // JavaScript 파일
        wp_enqueue_script(
            'integrated-map-generation-js',
            get_template_directory_uri() . '/admin/assets/integrated-map-generation.js',
            array('jquery'),
            self::VERSION,
            true
        );
        
        // AJAX 설정 지역화
        wp_localize_script('integrated-map-generation-js', 'integrated_map_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('integrated_map_nonce'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'strings' => array(
                'confirm_start' => '배치 지오코딩을 시작하시겠습니까?',
                'confirm_stop' => '진행 중인 작업을 중단하시겠습니까?',
                'processing' => '처리 중...',
                'completed' => '완료',
                'error' => '오류',
                'success' => '성공'
            )
        ));
    }
    
    /**
     * 관리자 페이지 렌더링
     */
    public function render_admin_page() {
        // 권한 확인
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // 페이지 템플릿 로드
        $template_path = get_template_directory() . '/admin/integrated-map-generation-page.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->render_fallback_page();
        }
    }
    
    /**
     * 폴백 페이지 렌더링
     */
    private function render_fallback_page() {
        ?>
        <div class="wrap integrated-map-system">
            <h1>🗺️ 통합 지도생성 시스템</h1>
            <div class="notice notice-error">
                <p><strong>오류:</strong> 관리자 페이지 템플릿 파일을 찾을 수 없습니다.</p>
                <p>파일 위치: <code>/admin/integrated-map-generation-page.php</code></p>
            </div>
            
            <div class="api-test-section">
                <h2>API 연결 테스트</h2>
                <button type="button" id="test-api-connection" class="button button-primary">
                    🔍 API 연결 테스트
                </button>
                <div id="api-test-result" style="margin-top: 10px;"></div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#test-api-connection').on('click', function() {
                    const button = $(this);
                    const resultDiv = $('#api-test-result');
                    
                    button.prop('disabled', true).text('테스트 중...');
                    
                    $.post(ajaxurl, {
                        action: 'integrated_map_test_api',
                        nonce: '<?php echo wp_create_nonce('integrated_map_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            resultDiv.html('<div class="notice notice-success"><p>✅ API 연결 성공!</p></div>');
                        } else {
                            resultDiv.html('<div class="notice notice-error"><p>❌ API 연결 실패: ' + response.data + '</p></div>');
                        }
                    }).fail(function() {
                        resultDiv.html('<div class="notice notice-error"><p>❌ 요청 실패</p></div>');
                    }).always(function() {
                        button.prop('disabled', false).text('🔍 API 연결 테스트');
                    });
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * 초기 데이터 조회 AJAX 핸들러
     */
    public function ajax_get_initial_data() {
        check_ajax_referer('integrated_map_nonce', 'nonce');
        
        try {
            // 좌표 미확정 Places 수집
            $pending_places = $this->batch_processor->get_pending_places();
            
            // 상태 요약 생성
            $total_places = $this->get_total_places_count();
            $pending_count = count($pending_places);
            $completed_count = $total_places - $pending_count;
            
            // Places 목록 데이터 구성
            $places_data = array();
            foreach ($pending_places as $place) {
                $places_data[] = array(
                    'id' => $place->ID,
                    'name' => $place->post_title,
                    'address' => $place->address ?: '주소 없음',
                    'category' => $this->get_place_category($place->ID),
                    'coordinate_status' => $this->get_coordinate_status($place),
                    'last_updated' => get_post_meta($place->ID, 'geocoded_at', true) ?: '처리 안됨'
                );
            }
            
            // API 상태 확인
            $api_status = $this->check_api_status();
            
            wp_send_json_success(array(
                'summary' => array(
                    'pending_count' => $pending_count,
                    'completed_count' => $completed_count,
                    'total_count' => $total_places
                ),
                'places' => $places_data,
                'api_status' => $api_status,
                'last_batch' => $this->get_last_batch_info()
            ));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * 배치 처리 시작 AJAX 핸들러
     */
    public function ajax_start_batch_processing() {
        check_ajax_referer('integrated_map_nonce', 'nonce');
        
        try {
            // API 설정 확인
            if (!$this->batch_processor->is_api_configured()) {
                wp_send_json_error('네이버 API 키가 설정되지 않았습니다. 설정 페이지에서 API 키를 설정해주세요.');
                return;
            }
            
            // 기존 진행상황 초기화
            delete_option(self::PROGRESS_OPTION_KEY);
            delete_option(self::SESSION_OPTION_KEY);
            
            // 처리할 Places 수집
            $pending_places = $this->batch_processor->get_pending_places();
            
            if (empty($pending_places)) {
                wp_send_json_success(array(
                    'message' => '처리할 Places가 없습니다.',
                    'total_count' => 0,
                    'total_batches' => 0
                ));
                return;
            }
            
            // 세션 정보 저장
            $session_data = array(
                'started_at' => current_time('mysql'),
                'total_count' => count($pending_places),
                'status' => 'running',
                'processed' => 0,
                'success_count' => 0,
                'error_count' => 0,
                'current_place' => '',
                'logs' => array()
            );
            
            update_option(self::SESSION_OPTION_KEY, $session_data);
            
            // 백그라운드에서 배치 처리 시작
            $this->start_background_processing($pending_places);
            
            wp_send_json_success(array(
                'message' => '배치 처리가 시작되었습니다.',
                'total_count' => count($pending_places),
                'total_batches' => 1 // 단일 배치로 처리
            ));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * 백그라운드 배치 처리 시작
     */
    private function start_background_processing($places) {
        // WordPress 백그라운드 처리 또는 즉시 처리
        // 간단한 구현을 위해 즉시 처리 (실제 환경에서는 WP Cron 등 사용 권장)
        
        $callback = function($progress_data) {
            // 진행상황 업데이트
            $session_data = get_option(self::SESSION_OPTION_KEY, array());
            $session_data = array_merge($session_data, $progress_data);
            $session_data['last_updated'] = current_time('mysql');
            
            update_option(self::SESSION_OPTION_KEY, $session_data);
        };
        
        // 배치 처리 실행
        $result = $this->batch_processor->process_batch($places, $callback);
        
        // 최종 결과 저장
        $final_session = get_option(self::SESSION_OPTION_KEY, array());
        $final_session['status'] = 'completed';
        $final_session['completed_at'] = current_time('mysql');
        $final_session['result'] = $result;
        $final_session['logs'] = $this->batch_processor->get_process_logs();
        
        update_option(self::SESSION_OPTION_KEY, $final_session);
        
        // 진행상황 옵션에도 저장
        update_option(self::PROGRESS_OPTION_KEY, $final_session);
    }
    
    /**
     * 진행상황 확인 AJAX 핸들러
     */
    public function ajax_check_progress() {
        check_ajax_referer('integrated_map_nonce', 'nonce');
        
        $session_data = get_option(self::SESSION_OPTION_KEY, array());
        
        if (empty($session_data)) {
            wp_send_json_error('진행 중인 세션이 없습니다.');
            return;
        }
        
        $progress_data = array(
            'status' => $session_data['status'] ?? 'unknown',
            'processed' => $session_data['processed'] ?? 0,
            'total' => $session_data['total_count'] ?? 0,
            'success_count' => $session_data['success_count'] ?? 0,
            'error_count' => $session_data['error_count'] ?? 0,
            'current_place' => $session_data['current_place'] ?? '',
            'current_batch' => 1,
            'total_batches' => 1,
            'completed' => ($session_data['status'] ?? '') === 'completed',
            'logs' => array_slice($session_data['logs'] ?? array(), -10), // 최근 10개 로그만
            'last_updated' => $session_data['last_updated'] ?? ''
        );
        
        // 에러가 있으면 포함
        if (isset($session_data['errors'])) {
            $progress_data['errors'] = $session_data['errors'];
        }
        
        wp_send_json_success($progress_data);
    }
    
    /**
     * 처리 중단 AJAX 핸들러
     */
    public function ajax_stop_processing() {
        check_ajax_referer('integrated_map_nonce', 'nonce');
        
        // 세션 상태를 중단으로 변경
        $session_data = get_option(self::SESSION_OPTION_KEY, array());
        $session_data['status'] = 'stopped';
        $session_data['stopped_at'] = current_time('mysql');
        
        update_option(self::SESSION_OPTION_KEY, $session_data);
        
        wp_send_json_success(array(
            'message' => '처리가 중단되었습니다.'
        ));
    }
    
    /**
     * 개별 Place 수정 AJAX 핸들러
     */
    public function ajax_fix_individual_place() {
        check_ajax_referer('integrated_map_nonce', 'nonce');
        
        $place_id = intval($_POST['place_id']);
        
        if (!$place_id) {
            wp_send_json_error('유효하지 않은 Place ID입니다.');
            return;
        }
        
        try {
            $place = get_post($place_id);
            if (!$place || $place->post_type !== 'places') {
                wp_send_json_error('Places를 찾을 수 없습니다.');
                return;
            }
            
            // 주소 확인
            $address = get_post_meta($place_id, 'address', true);
            if (empty($address)) {
                wp_send_json_error('주소가 설정되지 않았습니다.');
                return;
            }
            
            // 개별 지오코딩 실행
            $place_obj = (object) array(
                'ID' => $place_id,
                'post_title' => $place->post_title,
                'address' => $address
            );
            
            $result = $this->batch_processor->process_batch(array($place_obj));
            
            if ($result['success'] && $result['success_count'] > 0) {
                // 업데이트된 Place 데이터 반환
                $latitude = get_post_meta($place_id, 'latitude', true);
                $longitude = get_post_meta($place_id, 'longitude', true);
                
                wp_send_json_success(array(
                    'message' => '좌표가 성공적으로 업데이트되었습니다.',
                    'place' => array(
                        'id' => $place_id,
                        'name' => $place->post_title,
                        'address' => $address,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'coordinate_status' => 'completed'
                    )
                ));
            } else {
                wp_send_json_error('좌표 업데이트에 실패했습니다.');
            }
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * 지도 재생성 AJAX 핸들러
     */
    public function ajax_regenerate_map() {
        check_ajax_referer('integrated_map_nonce', 'nonce');
        
        $place_id = intval($_POST['place_id']);
        
        if (!$place_id) {
            wp_send_json_error('유효하지 않은 Place ID입니다.');
            return;
        }
        
        try {
            $latitude = get_post_meta($place_id, 'latitude', true);
            $longitude = get_post_meta($place_id, 'longitude', true);
            $title = get_the_title($place_id);
            
            if (empty($latitude) || empty($longitude)) {
                wp_send_json_error('좌표 정보가 없습니다.');
                return;
            }
            
            // 정적지도 재생성
            if (class_exists('NaverStaticMapGeneratorMapsFixed')) {
                $generator = new NaverStaticMapGeneratorMapsFixed();
                $result = $generator->generatePlaceMap($place_id, $latitude, $longitude, $title);
                
                if ($result['success']) {
                    wp_send_json_success(array(
                        'message' => '지도가 성공적으로 재생성되었습니다.'
                    ));
                } else {
                    wp_send_json_error('지도 재생성에 실패했습니다: ' . $result['error']);
                }
            } else {
                wp_send_json_error('정적지도 생성기를 찾을 수 없습니다.');
            }
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * API 연결 테스트 AJAX 핸들러
     */
    public function ajax_test_api() {
        check_ajax_referer('integrated_map_nonce', 'nonce');
        
        try {
            $test_result = $this->batch_processor->test_geocoding();
            
            if ($test_result['success']) {
                wp_send_json_success($test_result['message']);
            } else {
                wp_send_json_error($test_result['error']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * 헬퍼 메서드들
     */
    
    /**
     * 전체 Places 개수 조회
     */
    private function get_total_places_count() {
        $count_query = new WP_Query(array(
            'post_type' => 'places',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        return $count_query->found_posts;
    }
    
    /**
     * Place 카테고리 조회
     */
    private function get_place_category($place_id) {
        $terms = wp_get_post_terms($place_id, 'place_type');
        
        if (!empty($terms) && !is_wp_error($terms)) {
            return $terms[0]->name;
        }
        
        return '미분류';
    }
    
    /**
     * 좌표 상태 확인
     */
    private function get_coordinate_status($place) {
        if (empty($place->latitude) || empty($place->longitude) || 
            $place->latitude == '0' || $place->longitude == '0' ||
            $place->latitude == '37.548' || $place->longitude == '127.06') {
            return 'pending';
        }
        
        return 'completed';
    }
    
    /**
     * 마지막 배치 정보 조회
     */
    private function get_last_batch_info() {
        $progress = get_option(self::PROGRESS_OPTION_KEY, array());
        
        if (isset($progress['completed_at'])) {
            return array(
                'completed_at' => $progress['completed_at'],
                'result' => $progress['result'] ?? array()
            );
        }
        
        return null;
    }
    
    /**
     * API 상태 확인
     */
    private function check_api_status() {
        $naver_client_id = get_option('sungsuya_naver_client_id', '');
        $naver_client_secret = get_option('sungsuya_naver_client_secret', '');
        
        return array(
            'naver_configured' => !empty($naver_client_id) && !empty($naver_client_secret),
            'client_id' => !empty($naver_client_id) ? '설정됨' : '미설정',
            'client_secret' => !empty($naver_client_secret) ? '설정됨' : '미설정'
        );
    }
    
    /**
     * 디버그 로깅 초기화
     */
    public function init_debug_logging() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $this->debug_log("통합 지도생성 시스템 v" . self::VERSION . " 초기화 완료");
    }
    
    /**
     * 디버그 로그 헬퍼
     */
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[통합 지도생성 시스템] ' . $message);
        }
    }
}

// 클래스 인스턴스 생성
new IntegratedMapGenerationSystem();