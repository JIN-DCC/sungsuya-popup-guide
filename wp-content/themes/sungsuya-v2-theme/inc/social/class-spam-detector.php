<?php
/**
 * 스팸 및 광고 감지 시스템
 */

class Sungsuya_Spam_Detector {
    
    private static $instance = null;
    
    // 스팸 키워드 목록
    private $spam_keywords = array(
        // 광고성 키워드
        '광고', '홍보', '마케팅', '할인', '이벤트', '무료', '공짜', '특가',
        '최저가', '파격', '득템', '한정', '선착순', '쿠폰', '프로모션',
        
        // URL 패턴
        'http://', 'https://', 'www.', '.com', '.kr', 'bit.ly', 'goo.gl',
        
        // 연락처 패턴
        '010-', '011-', '016-', '017-', '018-', '019-',
        '카톡', '카카오톡', 'kakao', '텔레그램', 'telegram',
        
        // 도박/성인
        '카지노', '바카라', '토토', '베팅', '도박', '성인', '19금',
        
        // 의료/다이어트
        '다이어트', '살빼기', '지방흡입', '성형', '시술', '병원',
        
        // 대출/금융
        '대출', '대부', '금융', '투자', '주식', '코인', '비트코인',
        
        // 중복 문자
        '!!!!!', '?????', '~~~~~', '.....',
        
        // 영어 스팸
        'click here', 'buy now', 'limited offer', 'act now', 'call now',
        'guaranteed', 'risk free', 'viagra', 'pills', 'weight loss'
    );
    
    // 허용된 도메인 (공식 SNS 등)
    private $allowed_domains = array(
        'naver.com', 'kakao.com', 'instagram.com', 'facebook.com',
        'youtube.com', 'google.com'
    );
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 리뷰가 스팸인지 검사
     */
    public function is_spam($review_text, $user_name = '', $user_email = '') {
        $score = 0;
        $reasons = array();
        
        // 텍스트 정규화
        $normalized_text = $this->normalize_text($review_text . ' ' . $user_name);
        
        // 1. 스팸 키워드 검사
        foreach ($this->spam_keywords as $keyword) {
            if (stripos($normalized_text, $keyword) !== false) {
                $score += 2;
                $reasons[] = "스팸 키워드 발견: {$keyword}";
            }
        }
        
        // 2. URL 개수 검사
        $url_count = preg_match_all('/https?:\/\/[^\s]+/i', $review_text, $matches);
        if ($url_count > 2) {
            $score += 5;
            $reasons[] = "과도한 URL 포함 ({$url_count}개)";
        }
        
        // 3. 전화번호 패턴 검사
        if (preg_match('/\d{3,4}[-\s]?\d{3,4}[-\s]?\d{4}/', $normalized_text)) {
            $score += 5;
            $reasons[] = "전화번호 패턴 감지";
        }
        
        // 4. 이메일 패턴 검사 (리뷰 내용에 이메일이 있는 경우)
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $review_text)) {
            $score += 3;
            $reasons[] = "이메일 주소 포함";
        }
        
        // 5. 중복 문자 검사
        if (preg_match('/(.)\1{4,}/', $normalized_text)) {
            $score += 3;
            $reasons[] = "과도한 중복 문자";
        }
        
        // 6. 대문자 비율 검사
        $uppercase_ratio = $this->get_uppercase_ratio($review_text);
        if ($uppercase_ratio > 0.5 && mb_strlen($review_text) > 20) {
            $score += 2;
            $reasons[] = "과도한 대문자 사용";
        }
        
        // 7. 특수문자 비율 검사
        $special_char_ratio = $this->get_special_char_ratio($review_text);
        if ($special_char_ratio > 0.3) {
            $score += 2;
            $reasons[] = "과도한 특수문자 사용";
        }
        
        // 8. 짧은 리뷰에 URL이 있는 경우
        if (mb_strlen($review_text) < 50 && $url_count > 0) {
            $score += 3;
            $reasons[] = "짧은 리뷰에 URL 포함";
        }
        
        // 9. 의심스러운 이메일 도메인
        if ($user_email) {
            $email_domain = substr(strrchr($user_email, "@"), 1);
            $suspicious_domains = array('tempmail', 'guerrillamail', '10minutemail', 'mailinator');
            foreach ($suspicious_domains as $domain) {
                if (stripos($email_domain, $domain) !== false) {
                    $score += 5;
                    $reasons[] = "임시 이메일 서비스 사용";
                    break;
                }
            }
        }
        
        // 10. 한글이 전혀 없는 경우 (한국 서비스인 경우)
        if (!preg_match('/[가-힣]/', $review_text) && mb_strlen($review_text) > 10) {
            $score += 1;
            $reasons[] = "한글 미포함";
        }
        
        return array(
            'is_spam' => $score >= 5,
            'score' => $score,
            'reasons' => $reasons,
            'confidence' => min($score / 10, 1) // 0~1 사이의 신뢰도
        );
    }
    
    /**
     * 텍스트 정규화
     */
    private function normalize_text($text) {
        // 소문자 변환
        $text = mb_strtolower($text);
        
        // 공백 정규화
        $text = preg_replace('/\s+/', ' ', $text);
        
        // 특수문자 제거 (URL 검사용은 제외)
        // $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        
        return trim($text);
    }
    
    /**
     * 대문자 비율 계산
     */
    private function get_uppercase_ratio($text) {
        // 알파벳만 추출
        preg_match_all('/[a-zA-Z]/', $text, $matches);
        $letters = implode('', $matches[0]);
        
        if (strlen($letters) == 0) {
            return 0;
        }
        
        $uppercase_count = preg_match_all('/[A-Z]/', $letters);
        return $uppercase_count / strlen($letters);
    }
    
    /**
     * 특수문자 비율 계산
     */
    private function get_special_char_ratio($text) {
        $total_length = mb_strlen($text);
        if ($total_length == 0) {
            return 0;
        }
        
        // 한글, 영문, 숫자, 공백을 제외한 문자 수
        $special_chars = preg_replace('/[\p{L}\p{N}\s]/u', '', $text);
        $special_count = mb_strlen($special_chars);
        
        return $special_count / $total_length;
    }
    
    /**
     * 일괄 스팸 검사
     */
    public function bulk_check_spam($limit = 100) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'place_reviews';
        
        // 아직 검사하지 않은 리뷰 가져오기
        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE spam_checked = 0 OR spam_checked IS NULL 
            ORDER BY created_at DESC 
            LIMIT %d",
            $limit
        ));
        
        $spam_count = 0;
        
        foreach ($reviews as $review) {
            $result = $this->is_spam($review->review_text, $review->user_name, $review->user_email);
            
            // 스팸 점수 업데이트
            $wpdb->update(
                $table_name,
                array(
                    'spam_score' => $result['score'],
                    'spam_checked' => 1,
                    'is_spam' => $result['is_spam'] ? 1 : 0,
                    'spam_reasons' => json_encode($result['reasons'])
                ),
                array('id' => $review->id)
            );
            
            if ($result['is_spam']) {
                $spam_count++;
                
                // 자동 숨김 처리 (높은 점수인 경우)
                if ($result['score'] >= 8) {
                    $wpdb->update(
                        $table_name,
                        array('is_hidden' => 1),
                        array('id' => $review->id)
                    );
                }
            }
        }
        
        return array(
            'checked' => count($reviews),
            'spam_found' => $spam_count
        );
    }
    
    /**
     * 금지어 목록 관리
     */
    public function add_spam_keyword($keyword) {
        $keywords = get_option('sungsuya_spam_keywords', array());
        if (!in_array($keyword, $keywords)) {
            $keywords[] = $keyword;
            update_option('sungsuya_spam_keywords', $keywords);
        }
    }
    
    public function remove_spam_keyword($keyword) {
        $keywords = get_option('sungsuya_spam_keywords', array());
        $keywords = array_diff($keywords, array($keyword));
        update_option('sungsuya_spam_keywords', array_values($keywords));
    }
    
    public function get_custom_keywords() {
        return get_option('sungsuya_spam_keywords', array());
    }
}

// 데이터베이스 테이블에 스팸 관련 컬럼 추가
function sungsuya_add_spam_columns() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'place_reviews';
    
    // 컬럼이 없으면 추가
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
    $column_names = array_column($columns, 'Field');
    
    if (!in_array('spam_score', $column_names)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN spam_score INT DEFAULT 0");
    }
    
    if (!in_array('spam_checked', $column_names)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN spam_checked TINYINT(1) DEFAULT 0");
    }
    
    if (!in_array('is_spam', $column_names)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN is_spam TINYINT(1) DEFAULT 0");
    }
    
    if (!in_array('spam_reasons', $column_names)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN spam_reasons TEXT");
    }
}

// 플러그인 활성화 시 실행
add_action('after_switch_theme', 'sungsuya_add_spam_columns');

// AJAX 핸들러: 스팸 검사
add_action('wp_ajax_check_review_spam', 'handle_check_review_spam');
function handle_check_review_spam() {
    if (!current_user_can('manage_options')) {
        wp_die('권한이 없습니다.');
    }
    
    $detector = Sungsuya_Spam_Detector::get_instance();
    $result = $detector->bulk_check_spam();
    
    wp_send_json_success($result);
}

// 새 리뷰 작성 시 자동 스팸 검사
add_filter('sungsuya_before_insert_review', 'check_spam_before_insert', 10, 1);
function check_spam_before_insert($review_data) {
    $detector = Sungsuya_Spam_Detector::get_instance();
    $result = $detector->is_spam(
        $review_data['review_text'] ?? '',
        $review_data['user_name'] ?? '',
        $review_data['user_email'] ?? ''
    );
    
    $review_data['spam_score'] = $result['score'];
    $review_data['spam_checked'] = 1;
    $review_data['is_spam'] = $result['is_spam'] ? 1 : 0;
    $review_data['spam_reasons'] = json_encode($result['reasons']);
    
    // 높은 스팸 점수인 경우 자동 차단
    if ($result['score'] >= 10) {
        $review_data['is_hidden'] = 1;
    }
    
    return $review_data;
}
