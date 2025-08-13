<?php
/**
 * 투어 저장/공유 시스템
 * 성수야! V2 - 데이터베이스 스키마 및 API
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 투어 저장 테이블 생성
 */
function create_saved_tours_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'sungsuya_saved_tours';
    
    // 테이블이 이미 존재하는지 확인
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name;
    
    if ($table_exists) {
        // 테이블이 이미 존재함을 로그에 기록 (에러가 아님)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("✅ [tour-saver.php] Table '{$table_name}' already exists - skipping creation");
        }
        return; // 이미 존재하면 생성하지 않음
    }
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        tour_id varchar(20) NOT NULL,
        title varchar(255) NOT NULL,
        description text,
        language varchar(10) NOT NULL DEFAULT 'ko',
        places_data longtext NOT NULL,
        tour_config longtext,
        creator_ip varchar(45),
        creator_user_id bigint(20),
        view_count bigint(20) DEFAULT 0,
        share_count bigint(20) DEFAULT 0,
        is_public tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY tour_id (tour_id),
        KEY language (language),
        KEY creator_user_id (creator_user_id),
        KEY created_at (created_at),
        KEY is_public (is_public)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * 투어 저장 클래스
 */
class SungsuyaTourSaver {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sungsuya_saved_tours';
    }
    
    /**
     * 투어 저장
     */
    public function save_tour($tour_data) {
        global $wpdb;
        
        // 고유 ID 생성
        $tour_id = $this->generate_tour_id();
        
        // 데이터 검증
        $validated_data = $this->validate_tour_data($tour_data);
        if (is_wp_error($validated_data)) {
            return $validated_data;
        }
        
        // 데이터베이스 저장
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'tour_id' => $tour_id,
                'title' => sanitize_text_field($tour_data['title']),
                'description' => sanitize_textarea_field($tour_data['description']),
                'language' => sanitize_text_field($tour_data['language']),
                'places_data' => wp_json_encode($tour_data['places']),
                'tour_config' => wp_json_encode($tour_data['config']),
                'creator_ip' => $this->get_client_ip(),
                'creator_user_id' => get_current_user_id() ?: null,
                'is_public' => isset($tour_data['is_public']) ? (bool)$tour_data['is_public'] : true
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d')
        );
        
        if ($result === false) {
            return new WP_Error('save_failed', '투어 저장에 실패했습니다.');
        }
        
        return array(
            'success' => true,
            'tour_id' => $tour_id,
            'url' => home_url("/tour/{$tour_id}"),
            'share_url' => home_url("/tour/{$tour_id}?ref=share")
        );
    }
    
    /**
     * 투어 불러오기
     */
    public function get_tour($tour_id) {
        global $wpdb;
        
        $tour = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE tour_id = %s AND is_public = 1",
                $tour_id
            ),
            ARRAY_A
        );
        
        if (!$tour) {
            return new WP_Error('tour_not_found', '투어를 찾을 수 없습니다.');
        }
        
        // 조회수 증가
        $wpdb->update(
            $this->table_name,
            array('view_count' => $tour['view_count'] + 1),
            array('tour_id' => $tour_id),
            array('%d'),
            array('%s')
        );
        
        // JSON 데이터 파싱
        $tour['places_data'] = json_decode($tour['places_data'], true);
        $tour['tour_config'] = json_decode($tour['tour_config'], true);
        
        return $tour;
    }
    
    /**
     * 공유 카운트 증가
     */
    public function increment_share_count($tour_id) {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table_name} SET share_count = share_count + 1 WHERE tour_id = %s",
                $tour_id
            )
        );
    }
    
    /**
     * 투어 ID 생성
     */
    private function generate_tour_id() {
        // 더 읽기 쉬운 ID 생성
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $tour_id = '';
        
        for ($i = 0; $i < 8; $i++) {
            $tour_id .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // 중복 확인
        global $wpdb;
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE tour_id = %s",
                $tour_id
            )
        );
        
        if ($exists > 0) {
            return $this->generate_tour_id(); // 재귀 호출
        }
        
        return $tour_id;
    }
    
    /**
     * 투어 데이터 검증
     */
    private function validate_tour_data($data) {
        if (empty($data['title'])) {
            return new WP_Error('missing_title', '투어 제목이 필요합니다.');
        }
        
        if (empty($data['places']) || !is_array($data['places']) || count($data['places']) < 1) {
            return new WP_Error('invalid_places', '최소 1개 이상의 장소가 필요합니다.');
        }
        
        if (count($data['places']) > 8) {
            return new WP_Error('too_many_places', '최대 8개까지 장소를 선택할 수 있습니다.');
        }
        
        return true;
    }
    
    /**
     * 사용자의 투어 목록 가져오기
     */
    public function get_user_tours($user_id, $limit = 20, $offset = 0) {
        global $wpdb;
        
        $tours = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE creator_user_id = %d 
                ORDER BY created_at DESC 
                LIMIT %d OFFSET %d",
                $user_id,
                $limit,
                $offset
            ),
            ARRAY_A
        );
        
        // JSON 데이터 파싱
        foreach ($tours as &$tour) {
            $tour['places_data'] = json_decode($tour['places_data'], true);
            $tour['tour_config'] = json_decode($tour['tour_config'], true);
        }
        
        return $tours;
    }
    
    /**
     * 사용자의 투어 개수 가져오기
     */
    public function get_user_tour_count($user_id) {
        global $wpdb;
        
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE creator_user_id = %d",
                $user_id
            )
        );
    }
    
    /**
     * 클라이언트 IP 가져오기
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

/**
 * REST API 엔드포인트 등록
 */
class SungsuyaTourAPI {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        // 투어 저장
        register_rest_route('sungsuya/v2', '/tours', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_tour'),
            'permission_callback' => '__return_true'
        ));
        
        // 투어 불러오기
        register_rest_route('sungsuya/v2', '/tours/(?P<tour_id>[A-Z0-9]{8})', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_tour'),
            'permission_callback' => '__return_true'
        ));
        
        // 투어 공유
        register_rest_route('sungsuya/v2', '/tours/(?P<tour_id>[A-Z0-9]{8})/share', array(
            'methods' => 'POST',
            'callback' => array($this, 'share_tour'),
            'permission_callback' => '__return_true'
        ));
        
        // 사용자 투어 목록
        register_rest_route('sungsuya/v2', '/my-tours', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_my_tours'),
            'permission_callback' => array($this, 'check_auth')
        ));
    }
    
    public function save_tour($request) {
        $tour_saver = new SungsuyaTourSaver();
        
        $tour_data = array(
            'title' => $request->get_param('title'),
            'description' => $request->get_param('description'),
            'language' => $request->get_param('language') ?: 'ko',
            'places' => $request->get_param('places'),
            'config' => $request->get_param('config'),
            'is_public' => $request->get_param('is_public')
        );
        
        $result = $tour_saver->save_tour($tour_data);
        
        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message()
            ), 400);
        }
        
        return new WP_REST_Response($result, 201);
    }
    
    public function get_tour($request) {
        $tour_saver = new SungsuyaTourSaver();
        $tour_id = $request->get_param('tour_id');
        
        $result = $tour_saver->get_tour($tour_id);
        
        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message()
            ), 404);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'tour' => $result
        ), 200);
    }
    
    public function share_tour($request) {
        $tour_saver = new SungsuyaTourSaver();
        $tour_id = $request->get_param('tour_id');
        
        $tour_saver->increment_share_count($tour_id);
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => '공유되었습니다.'
        ), 200);
    }
    
    public function get_my_tours($request) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ), 401);
        }
        
        $tour_saver = new SungsuyaTourSaver();
        $limit = $request->get_param('limit') ?: 20;
        $offset = $request->get_param('offset') ?: 0;
        
        $tours = $tour_saver->get_user_tours($user_id, $limit, $offset);
        $total = $tour_saver->get_user_tour_count($user_id);
        
        return new WP_REST_Response(array(
            'success' => true,
            'tours' => $tours,
            'total' => $total
        ), 200);
    }
    
    public function check_auth() {
        return is_user_logged_in();
    }
}

// 활성화 시 테이블 생성
register_activation_hook(__FILE__, 'create_saved_tours_table');

// 테마 로드 시 테이블 확인
add_action('after_setup_theme', function() {
    create_saved_tours_table();
});

// API 클래스 초기화
new SungsuyaTourAPI();
