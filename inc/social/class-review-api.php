<?php
/**
 * 리뷰 시스템 REST API
 * 
 * @package Sungsuya
 * @subpackage Social_Features
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sungsuya_Review_API {
    
    /**
     * API 네임스페이스
     */
    private $namespace = 'sungsuya/v1';
    
    /**
     * 리뷰 시스템 인스턴스
     */
    private $review_system;
    
    /**
     * 생성자
     */
    public function __construct() {
        $this->review_system = Sungsuya_Review_System::get_instance();
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * REST API 라우트 등록
     */
    public function register_routes() {
        // 리뷰 목록 조회
        register_rest_route($this->namespace, '/reviews/(?P<place_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_reviews'),
            'permission_callback' => '__return_true',
            'args' => array(
                'place_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'language' => array(
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'page' => array(
                    'default' => 1,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'per_page' => array(
                    'default' => 10,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 50;
                    }
                )
            )
        ));
        
        // 리뷰 작성
        register_rest_route($this->namespace, '/reviews', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_review'),
            'permission_callback' => array($this, 'check_create_permission'),
            'args' => array(
                'place_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && get_post($param);
                    }
                ),
                'user_name' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'user_email' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_email'
                ),
                'rating' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param >= 1 && $param <= 5;
                    }
                ),
                'review_text' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
                'recaptcha_token' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'images' => array(
                    'required' => false,
                    'type' => 'array',
                    'items' => array(
                        'type' => 'integer'
                    )
                )
            )
        ));
        
        // 리뷰 좋아요
        register_rest_route($this->namespace, '/reviews/(?P<review_id>\d+)/like', array(
            'methods' => 'POST',
            'callback' => array($this, 'like_review'),
            'permission_callback' => '__return_true',
            'args' => array(
                'review_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // 리뷰 신고
        register_rest_route($this->namespace, '/reviews/(?P<review_id>\d+)/report', array(
            'methods' => 'POST',
            'callback' => array($this, 'report_review'),
            'permission_callback' => '__return_true',
            'args' => array(
                'review_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // 번역 (임시 - 실제로는 GTranslate 사용)
        register_rest_route($this->namespace, '/reviews/translate', array(
            'methods' => 'POST',
            'callback' => array($this, 'translate_text'),
            'permission_callback' => '__return_true',
            'args' => array(
                'text' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
                'target' => array(
                    'required' => false,
                    'default' => 'en',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        // 평점 통계
        register_rest_route($this->namespace, '/reviews/stats/(?P<place_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_rating_stats'),
            'permission_callback' => '__return_true',
            'args' => array(
                'place_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // 이미지 업로드
        register_rest_route($this->namespace, '/reviews/upload-image', array(
            'methods' => 'POST',
            'callback' => array($this, 'upload_image'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * 리뷰 목록 조회 콜백
     */
    public function get_reviews($request) {
        $place_id = $request['place_id'];
        $language = $request['language'];
        $page = $request['page'];
        $per_page = $request['per_page'];
        
        $args = array(
            'language' => $language,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page
        );
        
        $reviews = $this->review_system->get_reviews($place_id, $args);
        
        // 현재 사용자의 좋아요 정보 추가
        $user_identifier = $this->get_user_identifier();
        foreach ($reviews as &$review) {
            $review->user_liked = $this->check_user_liked($review->id, $user_identifier);
            $review->can_report = $this->check_can_report($review->id, $user_identifier);
        }
        
        return new WP_REST_Response($reviews, 200);
    }
    
    /**
     * 리뷰 작성 콜백
     */
    public function create_review($request) {
        // reCAPTCHA 검증 (프로덕션에서만)
        if (!$this->verify_recaptcha($request['recaptcha_token'])) {
            return new WP_Error('recaptcha_failed', '보안 검증에 실패했습니다.', array('status' => 400));
        }
        
        // 속도 제한 확인
        if (!$this->check_rate_limit()) {
            return new WP_Error('rate_limit', '너무 많은 요청입니다. 잠시 후 다시 시도해주세요.', array('status' => 429));
        }
        
        $data = array(
            'place_id' => $request['place_id'],
            'user_name' => $request['user_name'],
            'user_email' => $request['user_email'],
            'rating' => $request['rating'],
            'review_text' => $request['review_text']
        );
        
        $review_id = $this->review_system->create_review($data);
        
        if ($review_id) {
            // 이미지 연결
            if (!empty($request['images']) && is_array($request['images'])) {
                global $wpdb;
                $table_images = $wpdb->prefix . 'review_images';
                
                foreach ($request['images'] as $image_id) {
                    if (wp_attachment_is_image($image_id)) {
                        $image_url = wp_get_attachment_url($image_id);
                        $wpdb->insert(
                            $table_images,
                            array(
                                'review_id' => $review_id,
                                'image_url' => $image_url
                            ),
                            array('%d', '%s')
                        );
                    }
                }
            }
            
            return new WP_REST_Response(array(
                'success' => true,
                'review_id' => $review_id,
                'message' => '리뷰가 등록되었습니다.'
            ), 201);
        }
        
        return new WP_Error('create_failed', '리뷰 등록에 실패했습니다.', array('status' => 500));
    }
    
    /**
     * 리뷰 좋아요 콜백
     */
    public function like_review($request) {
        $review_id = $request['review_id'];
        $user_identifier = $this->get_user_identifier();
        
        $result = $this->review_system->add_like($review_id, $user_identifier);
        
        if ($result) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => '좋아요를 추가했습니다.'
            ), 200);
        }
        
        return new WP_Error('like_failed', '이미 좋아요를 누르셨습니다.', array('status' => 400));
    }
    
    /**
     * 리뷰 신고 콜백
     */
    public function report_review($request) {
        $review_id = $request['review_id'];
        $user_identifier = $this->get_user_identifier();
        
        $result = $this->review_system->report_review($review_id, $user_identifier);
        
        if ($result) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => '신고가 접수되었습니다.'
            ), 200);
        }
        
        return new WP_Error('report_failed', '이미 신고하셨습니다.', array('status' => 400));
    }
    
    /**
     * 텍스트 번역 콜백 (임시)
     */
    public function translate_text($request) {
        // 실제로는 GTranslate가 페이지 전체를 번역하므로
        // 개별 번역은 필요시에만 구현
        return new WP_REST_Response(array(
            'translated' => $request['text'],
            'note' => 'GTranslate가 페이지 전체를 번역합니다.'
        ), 200);
    }
    
    /**
     * 평점 통계 콜백
     */
    public function get_rating_stats($request) {
        $place_id = $request['place_id'];
        $stats = $this->review_system->get_rating_stats($place_id);
        
        return new WP_REST_Response($stats, 200);
    }
    
    /**
     * 리뷰 작성 권한 확인
     */
    public function check_create_permission() {
        // 기본적으로 모든 사용자 허용
        // 필요시 로그인 체크 추가
        return true;
    }
    
    /**
     * reCAPTCHA 검증
     */
    private function verify_recaptcha($token) {
        // 개발 환경에서는 스킵
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return true;
        }
        
        $secret = get_option('sungsuya_recaptcha_secret');
        if (empty($secret)) {
            return true; // reCAPTCHA 설정 안됨
        }
        
        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
            'body' => array(
                'secret' => $secret,
                'response' => $token
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        return isset($result['success']) && $result['success'] && $result['score'] >= 0.5;
    }
    
    /**
     * 속도 제한 확인
     */
    private function check_rate_limit() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = 'review_rate_limit_' . md5($ip);
        $attempts = get_transient($key);
        
        if ($attempts >= 3) {
            return false;
        }
        
        set_transient($key, $attempts + 1, 5 * MINUTE_IN_SECONDS);
        return true;
    }
    
    /**
     * 사용자 식별자 생성
     */
    private function get_user_identifier() {
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }
        
        // 비로그인 사용자는 IP + User Agent 해시
        return 'guest_' . md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
    }
    
    /**
     * 사용자 좋아요 여부 확인
     */
    private function check_user_liked($review_id, $user_identifier) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'review_reactions';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
            WHERE review_id = %d AND reaction_type = 'like' AND user_identifier = %s",
            $review_id, $user_identifier
        ));
        
        return $count > 0;
    }
    
    /**
     * 신고 가능 여부 확인
     */
    private function check_can_report($review_id, $user_identifier) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'review_reactions';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
            WHERE review_id = %d AND reaction_type = 'report' AND user_identifier = %s",
            $review_id, $user_identifier
        ));
        
        return $count == 0;
    }
    
    /**
     * 이미지 업로드 콜백
     */
    public function upload_image($request) {
        // 파일 확인
        if (empty($_FILES['image'])) {
            return new WP_Error('no_file', '업로드할 파일이 없습니다.', array('status' => 400));
        }
        
        // WordPress 미디어 업로드 처리
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        // 파일 크기 제한 (5MB)
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            return new WP_Error('file_too_large', '파일 크기는 5MB를 초과할 수 없습니다.', array('status' => 400));
        }
        
        // 허용된 파일 타입
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            return new WP_Error('invalid_type', '허용되지 않은 파일 형식입니다.', array('status' => 400));
        }
        
        // 업로드 처리
        $attachment_id = media_handle_upload('image', 0);
        
        if (is_wp_error($attachment_id)) {
            return new WP_Error('upload_failed', '업로드에 실패했습니다.', array('status' => 500));
        }
        
        // 섬네일 생성
        $thumbnail = wp_get_attachment_image_src($attachment_id, 'thumbnail');
        $medium = wp_get_attachment_image_src($attachment_id, 'medium');
        $full = wp_get_attachment_image_src($attachment_id, 'full');
        
        return new WP_REST_Response(array(
            'success' => true,
            'attachment_id' => $attachment_id,
            'urls' => array(
                'thumbnail' => $thumbnail[0],
                'medium' => $medium[0],
                'full' => $full[0]
            )
        ), 200);
    }
}

// API 초기화
new Sungsuya_Review_API();
