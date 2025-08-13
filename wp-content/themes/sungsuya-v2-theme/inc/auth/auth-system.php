<?php
/**
 * 성수야 PWA 인증 시스템
 * 
 * 심플하고 안정적인 JWT 기반 인증 시스템
 * Guest Mode + Social Login + Progressive Profiling
 * 
 * @package SungsuyaV2
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sungsuya_Auth_System {
    
    private static $instance = null;
    private $jwt_secret;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->jwt_secret = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : wp_salt('auth');
        $this->init();
    }
    
    private function init() {
        // REST API 엔드포인트 등록
        add_action('rest_api_init', array($this, 'register_endpoints'));
        
        // CORS 헤더 추가 (PWA용)
        add_action('rest_api_init', array($this, 'add_cors_headers'));
        
        // 게스트 세션 관리
        add_action('init', array($this, 'init_guest_session'));
        
        // JWT 토큰 검증 필터
        add_filter('determine_current_user', array($this, 'validate_jwt_token'), 20);
    }
    
    /**
     * 이메일 로그인
     */
    public function email_login($request) {
        $email = sanitize_email($request->get_param('email'));
        $password = $request->get_param('password');
        
        if (!$email || !$password) {
            return new WP_Error('missing_fields', '이메일과 비밀번호를 입력해주세요.', array('status' => 400));
        }
        
        // 사용자 인증
        $user = wp_authenticate($email, $password);
        
        if (is_wp_error($user)) {
            return new WP_Error('invalid_credentials', '이메일 또는 비밀번호가 올바르지 않습니다.', array('status' => 401));
        }
        
        // JWT 토큰 생성
        $jwt_token = $this->generate_jwt(array(
            'user_id' => $user->ID,
            'type' => 'user',
            'provider' => 'email',
            'exp' => time() + (7 * DAY_IN_SECONDS)
        ));
        
        // 마지막 로그인 시간 기록
        update_user_meta($user->ID, 'last_login', time());
        
        return new WP_REST_Response(array(
            'success' => true,
            'user' => array(
                'id' => $user->ID,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'avatar' => get_avatar_url($user->ID)
            ),
            'token' => $jwt_token,
            'expires_in' => 7 * DAY_IN_SECONDS
        ), 200);
    }
    
    /**
     * REST API 엔드포인트 등록
     */
    public function register_endpoints() {
        // 게스트 세션 시작
        register_rest_route('sungsuya/v1', '/auth/guest', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_guest_session'),
            'permission_callback' => '__return_true'
        ));
        
        // 소셜 로그인
        register_rest_route('sungsuya/v1', '/auth/social', array(
            'methods' => 'POST',
            'callback' => array($this, 'social_login'),
            'permission_callback' => '__return_true'
        ));
        
        // 이메일 로그인
        register_rest_route('sungsuya/v1', '/auth/login', array(
            'methods' => 'POST',
            'callback' => array($this, 'email_login'),
            'permission_callback' => '__return_true'
        ));
        
        // 이메일 가입
        register_rest_route('sungsuya/v1', '/auth/register', array(
            'methods' => 'POST',
            'callback' => array($this, 'email_register'),
            'permission_callback' => '__return_true'
        ));
        
        // 로그인 상태 확인
        register_rest_route('sungsuya/v1', '/auth/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'check_auth_status'),
            'permission_callback' => '__return_true'
        ));
        
        // 프로필 업데이트
        register_rest_route('sungsuya/v1', '/auth/profile', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_profile'),
            'permission_callback' => array($this, 'is_authenticated')
        ));
    }
    
    /**
     * CORS 헤더 추가
     */
    public function add_cors_headers() {
        remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
        add_filter('rest_pre_serve_request', function($value) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Allow-Credentials: true');
            return $value;
        });
    }
    
    /**
     * 게스트 세션 초기화
     */
    public function init_guest_session() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }
    
    /**
     * 게스트 세션 생성
     */
    public function create_guest_session($request) {
        $guest_id = 'guest_' . uniqid();
        $guest_token = $this->generate_jwt(array(
            'user_id' => 0,
            'guest_id' => $guest_id,
            'type' => 'guest',
            'exp' => time() + (30 * DAY_IN_SECONDS)
        ));
        
        return new WP_REST_Response(array(
            'success' => true,
            'guest_id' => $guest_id,
            'token' => $guest_token,
            'expires_in' => 30 * DAY_IN_SECONDS
        ), 200);
    }
    
    /**
     * 소셜 로그인 처리
     */
    public function social_login($request) {
        $provider = $request->get_param('provider');
        $token = $request->get_param('token');
        $user_data = $request->get_param('user_data');
        $user_country = $request->get_param('country'); // 클라이언트에서 전송
        $user_language = $request->get_param('language'); // 클라이언트에서 전송
        
        if (!$provider || !$token || !$user_data) {
            return new WP_Error('missing_params', '필수 파라미터가 누락되었습니다.', array('status' => 400));
        }
        
        // 프로바이더별 토큰 검증
        $verified_data = $this->verify_social_token($provider, $token, $user_data);
        if (is_wp_error($verified_data)) {
            return $verified_data;
        }
        
        // 사용자 조회 또는 생성
        $user = $this->get_or_create_social_user($provider, $verified_data);
        if (is_wp_error($user)) {
            return $user;
        }
        
        // 국가/언어 정보 저장 (첫 로그인 시에만)
        if ($user_country || $user_language) {
            $existing_country = get_user_meta($user->ID, 'user_country', true);
            if (!$existing_country && $user_country) {
                update_user_meta($user->ID, 'user_country', sanitize_text_field($user_country));
            }
            if ($user_language) {
                update_user_meta($user->ID, 'preferred_language', sanitize_text_field($user_language));
            }
        }
        
        // JWT 토큰 생성
        $jwt_token = $this->generate_jwt(array(
            'user_id' => $user->ID,
            'type' => 'user',
            'provider' => $provider,
            'exp' => time() + (7 * DAY_IN_SECONDS)
        ));
        
        // 마지막 로그인 시간 기록
        update_user_meta($user->ID, 'last_login', time());
        
        return new WP_REST_Response(array(
            'success' => true,
            'user' => array(
                'id' => $user->ID,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'avatar' => get_avatar_url($user->ID)
            ),
            'token' => $jwt_token,
            'expires_in' => 7 * DAY_IN_SECONDS
        ), 200);
    }
    
    /**
     * 이메일 회원가입
     */
    public function email_register($request) {
        $email = sanitize_email($request->get_param('email'));
        $password = $request->get_param('password');
        $display_name = sanitize_text_field($request->get_param('display_name'));
        
        if (!$email || !$password) {
            return new WP_Error('missing_fields', '이메일과 비밀번호는 필수입니다.', array('status' => 400));
        }
        
        // 이메일 중복 확인
        if (email_exists($email)) {
            return new WP_Error('email_exists', '이미 사용중인 이메일입니다.', array('status' => 400));
        }
        
        // 사용자 생성
        $user_id = wp_create_user($email, $password, $email);
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // 프로필 업데이트
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $display_name ?: $email,
            'nickname' => $display_name ?: $email
        ));
        
        // 메타 정보 저장
        update_user_meta($user_id, 'social_provider', 'email');
        update_user_meta($user_id, 'last_login', time());
        
        // JWT 토큰 생성
        $jwt_token = $this->generate_jwt(array(
            'user_id' => $user_id,
            'type' => 'user',
            'provider' => 'email',
            'exp' => time() + (7 * DAY_IN_SECONDS)
        ));
        
        return new WP_REST_Response(array(
            'success' => true,
            'user' => array(
                'id' => $user_id,
                'email' => $email,
                'display_name' => $display_name ?: $email
            ),
            'token' => $jwt_token,
            'expires_in' => 7 * DAY_IN_SECONDS
        ), 201);
    }
    
    /**
     * 인증 상태 확인
     */
    public function check_auth_status($request) {
        $user_id = get_current_user_id();
        
        if ($user_id) {
            $user = get_userdata($user_id);
            return new WP_REST_Response(array(
                'authenticated' => true,
                'user' => array(
                    'id' => $user->ID,
                    'email' => $user->user_email,
                    'display_name' => $user->display_name,
                    'avatar' => get_avatar_url($user->ID)
                )
            ), 200);
        }
        
        // 게스트 확인
        $auth_header = $request->get_header('Authorization');
        if ($auth_header && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token_data = $this->decode_jwt($matches[1]);
            if ($token_data && isset($token_data->guest_id)) {
                return new WP_REST_Response(array(
                    'authenticated' => false,
                    'guest' => true,
                    'guest_id' => $token_data->guest_id
                ), 200);
            }
        }
        
        return new WP_REST_Response(array(
            'authenticated' => false,
            'guest' => false
        ), 200);
    }
    
    /**
     * 프로필 업데이트 (Progressive Profiling)
     */
    public function update_profile($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('not_authenticated', '로그인이 필요합니다.', array('status' => 401));
        }
        
        $fields = array('display_name', 'description', 'favorite_places', 'interests');
        $update_data = array('ID' => $user_id);
        $meta_updates = array();
        
        foreach ($fields as $field) {
            if ($request->has_param($field)) {
                if (in_array($field, array('display_name', 'description'))) {
                    $update_data[$field] = sanitize_text_field($request->get_param($field));
                } else {
                    $meta_updates[$field] = $request->get_param($field);
                }
            }
        }
        
        // 사용자 정보 업데이트
        if (count($update_data) > 1) {
            wp_update_user($update_data);
        }
        
        // 메타 정보 업데이트
        foreach ($meta_updates as $key => $value) {
            update_user_meta($user_id, 'sungsuya_' . $key, $value);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => '프로필이 업데이트되었습니다.'
        ), 200);
    }
    
    /**
     * JWT 토큰 생성
     */
    private function generate_jwt($payload) {
        $header = json_encode(array('typ' => 'JWT', 'alg' => 'HS256'));
        $payload = json_encode($payload);
        
        $base64_header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64_payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64_header . '.' . $base64_payload, $this->jwt_secret, true);
        $base64_signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64_header . '.' . $base64_payload . '.' . $base64_signature;
    }
    
    /**
     * JWT 토큰 디코드
     */
    private function decode_jwt($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])));
        
        // 서명 검증
        $signature = hash_hmac('sha256', $parts[0] . '.' . $parts[1], $this->jwt_secret, true);
        $base64_signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if ($base64_signature !== $parts[2]) {
            return false;
        }
        
        // 만료 시간 확인
        if (isset($payload->exp) && $payload->exp < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * JWT 토큰으로 사용자 인증
     */
    public function validate_jwt_token($user_id) {
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token_data = $this->decode_jwt($matches[1]);
            
            if ($token_data && isset($token_data->user_id) && $token_data->user_id > 0) {
                return $token_data->user_id;
            }
        }
        
        return $user_id;
    }
    
    /**
     * 소셜 토큰 검증
     */
    private function verify_social_token($provider, $token, $user_data) {
        switch ($provider) {
            case 'google':
                // Google 토큰 검증 (실제 환경에서는 Google API로 검증)
                if (!isset($user_data['email']) || !isset($user_data['name'])) {
                    return new WP_Error('invalid_data', 'Google 사용자 데이터가 올바르지 않습니다.');
                }
                return $user_data;
                
            case 'kakao':
                // 카카오 토큰 검증 (실제 환경에서는 카카오 API로 검증)
                if (!isset($user_data['id']) || !isset($user_data['properties'])) {
                    return new WP_Error('invalid_data', '카카오 사용자 데이터가 올바르지 않습니다.');
                }
                return array(
                    'id' => $user_data['id'],
                    'email' => $user_data['kakao_account']['email'] ?? null,
                    'name' => $user_data['properties']['nickname'] ?? '사용자'
                );
                
            case 'apple':
                // Apple 토큰 검증
                if (!isset($user_data['email'])) {
                    return new WP_Error('invalid_data', 'Apple 사용자 데이터가 올바르지 않습니다.');
                }
                return $user_data;
                
            default:
                return new WP_Error('unsupported_provider', '지원하지 않는 로그인 방식입니다.');
        }
    }
    
    /**
     * 소셜 사용자 조회 또는 생성
     */
    private function get_or_create_social_user($provider, $user_data) {
        $social_id = $provider . '_' . ($user_data['id'] ?? $user_data['email']);
        
        // 기존 사용자 조회
        $users = get_users(array(
            'meta_key' => 'social_login_id',
            'meta_value' => $social_id,
            'number' => 1
        ));
        
        if (!empty($users)) {
            return $users[0];
        }
        
        // 이메일로 기존 사용자 확인
        $email = $user_data['email'] ?? $social_id . '@sungsuya.local';
        if (email_exists($email)) {
            $user = get_user_by('email', $email);
            update_user_meta($user->ID, 'social_login_id', $social_id);
            update_user_meta($user->ID, 'social_provider', $provider);
            return $user;
        }
        
        // 새 사용자 생성
        $user_id = wp_create_user(
            $email,
            wp_generate_password(),
            $email
        );
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // 사용자 정보 업데이트
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $user_data['name'] ?? '사용자',
            'nickname' => $user_data['name'] ?? '사용자'
        ));
        
        // 소셜 로그인 메타 저장
        update_user_meta($user_id, 'social_login_id', $social_id);
        update_user_meta($user_id, 'social_provider', $provider);
        
        return get_userdata($user_id);
    }
    
    /**
     * 인증 확인
     */
    public function is_authenticated() {
        return is_user_logged_in();
    }
}

// 인스턴스 초기화
Sungsuya_Auth_System::get_instance();
