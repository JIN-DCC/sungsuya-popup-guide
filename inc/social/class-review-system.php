<?php
/**
 * 리뷰 시스템 핵심 클래스
 * 
 * @package Sungsuya
 * @subpackage Social_Features
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sungsuya_Review_System {
    
    /**
     * 싱글톤 인스턴스
     */
    private static $instance = null;
    
    /**
     * 테이블 이름들
     */
    private $table_reviews;
    private $table_reactions;
    private $table_images;
    
    /**
     * 생성자
     */
    private function __construct() {
        global $wpdb;
        
        $this->table_reviews = $wpdb->prefix . 'place_reviews';
        $this->table_reactions = $wpdb->prefix . 'review_reactions';
        $this->table_images = $wpdb->prefix . 'review_images';
        
        // 훅 등록
        add_action('init', array($this, 'init'));
        add_action('after_switch_theme', array($this, 'create_tables'));
    }
    
    /**
     * 싱글톤 인스턴스 반환
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
    public function init() {
        // GTranslate 통합
        $this->integrate_gtranslate();
    }
    
    /**
     * 데이터베이스 테이블 생성
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // 리뷰 테이블
        $sql_reviews = "CREATE TABLE IF NOT EXISTS {$this->table_reviews} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            place_id bigint(20) NOT NULL,
            user_name varchar(100) NOT NULL,
            user_email varchar(255) DEFAULT NULL,
            rating int(1) NOT NULL,
            review_text text,
            language varchar(10) DEFAULT 'ko',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ip_address varchar(45) DEFAULT NULL,
            is_verified tinyint(1) DEFAULT 0,
            is_hidden tinyint(1) DEFAULT 0,
            likes_count int(11) DEFAULT 0,
            reports_count int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY place_id (place_id),
            KEY created_at (created_at),
            KEY language (language),
            KEY is_hidden (is_hidden)
        ) $charset_collate;";
        
        // 리뷰 반응 테이블
        $sql_reactions = "CREATE TABLE IF NOT EXISTS {$this->table_reactions} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            review_id bigint(20) NOT NULL,
            reaction_type varchar(50) NOT NULL,
            user_identifier varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY review_id (review_id),
            KEY user_identifier (user_identifier),
            UNIQUE KEY unique_reaction (review_id, reaction_type, user_identifier)
        ) $charset_collate;";
        
        // 리뷰 이미지 테이블
        $sql_images = "CREATE TABLE IF NOT EXISTS {$this->table_images} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            review_id bigint(20) NOT NULL,
            image_url varchar(500) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY review_id (review_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_reviews);
        dbDelta($sql_reactions);
        dbDelta($sql_images);
    }
    
    /**
     * GTranslate 통합
     */
    private function integrate_gtranslate() {
        // 현재 언어 감지
        add_filter('sungsuya_current_language', array($this, 'get_current_language'));
    }
    
    /**
     * 현재 언어 반환
     */
    public function get_current_language() {
        // GTranslate 플러그인 확인
        if (function_exists('GTranslate')) {
            // GTranslate에서 현재 언어 가져오기
            $lang = isset($_GET['lang']) ? sanitize_text_field($_GET['lang']) : '';
            if (empty($lang) && isset($_COOKIE['googtrans'])) {
                // 쿠키에서 언어 확인
                $cookie_lang = $_COOKIE['googtrans'];
                if (preg_match('/\/([a-z]{2})$/', $cookie_lang, $matches)) {
                    $lang = $matches[1];
                }
            }
            return !empty($lang) ? $lang : 'ko';
        }
        
        // 기본값
        return 'ko';
    }
    
    /**
     * 리뷰 생성
     */
    public function create_review($data) {
        global $wpdb;
        
        $insert_data = array(
            'place_id' => absint($data['place_id']),
            'user_name' => sanitize_text_field($data['user_name']),
            'user_email' => sanitize_email($data['user_email']),
            'rating' => absint($data['rating']),
            'review_text' => sanitize_textarea_field($data['review_text']),
            'language' => $this->get_current_language(),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        );
        
        $result = $wpdb->insert($this->table_reviews, $insert_data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * 리뷰 목록 조회
     */
    public function get_reviews($place_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'language' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 10,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array("place_id = %d", "is_hidden = 0");
        $values = array($place_id);
        
        if (!empty($args['language'])) {
            $where[] = "language = %s";
            $values[] = $args['language'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_reviews} 
            WHERE {$where_clause}
            ORDER BY {$args['orderby']} {$args['order']}
            LIMIT %d OFFSET %d",
            array_merge($values, array($args['limit'], $args['offset']))
        );
        
        $reviews = $wpdb->get_results($query);
        
        // 이미지 정보 추가
        foreach ($reviews as &$review) {
            $review->images = $this->get_review_images($review->id);
        }
        
        return $reviews;
    }
    
    /**
     * 리뷰 이미지 조회
     */
    private function get_review_images($review_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_images} WHERE review_id = %d",
            $review_id
        ));
    }
    
    /**
     * 리뷰 좋아요 추가
     */
    public function add_like($review_id, $user_identifier) {
        global $wpdb;
        
        // 중복 확인은 UNIQUE KEY로 처리
        $result = $wpdb->insert(
            $this->table_reactions,
            array(
                'review_id' => $review_id,
                'reaction_type' => 'like',
                'user_identifier' => $user_identifier
            ),
            array('%d', '%s', '%s')
        );
        
        if ($result) {
            // 좋아요 카운트 업데이트
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->table_reviews} 
                SET likes_count = likes_count + 1 
                WHERE id = %d",
                $review_id
            ));
            return true;
        }
        
        return false;
    }
    
    /**
     * 리뷰 신고
     */
    public function report_review($review_id, $user_identifier) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_reactions,
            array(
                'review_id' => $review_id,
                'reaction_type' => 'report',
                'user_identifier' => $user_identifier
            ),
            array('%d', '%s', '%s')
        );
        
        if ($result) {
            // 신고 카운트 업데이트
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->table_reviews} 
                SET reports_count = reports_count + 1 
                WHERE id = %d",
                $review_id
            ));
            
            // 신고가 5개 이상이면 자동 숨김
            $this->check_and_hide_reported_review($review_id);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 신고된 리뷰 자동 숨김 체크
     */
    private function check_and_hide_reported_review($review_id) {
        global $wpdb;
        
        $review = $wpdb->get_row($wpdb->prepare(
            "SELECT reports_count FROM {$this->table_reviews} WHERE id = %d",
            $review_id
        ));
        
        if ($review && $review->reports_count >= 5) {
            $wpdb->update(
                $this->table_reviews,
                array('is_hidden' => 1),
                array('id' => $review_id),
                array('%d'),
                array('%d')
            );
        }
    }
    
    /**
     * 평점 통계 조회
     */
    public function get_rating_stats($place_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_stars,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_stars,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_stars,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_stars,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM {$this->table_reviews}
            WHERE place_id = %d AND is_hidden = 0",
            $place_id
        ));
        
        return $stats;
    }
}

// 싱글톤 인스턴스 초기화
Sungsuya_Review_System::get_instance();
