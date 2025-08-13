<?php
/**
 * 팝업스토어 CSV 업로드 처리 클래스
 * 
 * @package SungsuyaV2
 * @since 2.0.0
 */

class PopupStoreCSVImporter {
    
    /**
     * 필수 컬럼 정의
     */
    private $required_columns = [
        '브랜드명', 
        '스토어명',
        '주소', 
        '시작일', 
        '종료일',
        '카테고리',
        '운영상태'
    ];
    
    /**
     * 선택 컬럼 정의
     */
    private $optional_columns = [
        '운영시간',
        '전화번호',
        '인스타그램',
        '웹사이트',
        '예약필수',
        '입장료',
        '주차가능',
        '콜라보',
        '설명',
        '체류시간',
        '포토스팟',
        '방문팁',
        '이메일',
        '해시태그',
        '예약링크',
        '타겟층',
        '가격대',
        '연령제한',
        '평일혼잡도',
        '주말혼잡도',
        '추천시간'
    ];
    
    /**
     * CSV 컬럼 → WordPress 메타키 매핑
     */
    private $column_to_meta_mapping = [
        // 기본 정보
        '브랜드명' => 'brand_name',
        '스토어명' => 'store_name',
        '주소' => 'address',
        '설명' => 'store_description',
        '카테고리' => 'popup_category',
        '콜라보' => 'collaboration',
        '타겟층' => 'target_audience',
        '가격대' => 'price_range',
        
        // 운영 정보
        '시작일' => 'operation_start',
        '종료일' => 'operation_end',
        '운영시간' => 'opening_hours',
        '운영상태' => 'operation_status',
        '예약필수' => 'reservation_required',
        '입장료' => 'entry_fee',
        '주차가능' => 'parking_info',
        '연령제한' => 'age_restriction',
        
        // 연락처
        '전화번호' => 'phone_number',
        '이메일' => 'email',
        '인스타그램' => 'instagram_url',
        '웹사이트' => 'website_url',
        '해시태그' => 'hashtags',
        '예약링크' => 'booking_url',
        
        // 투어 정보
        '체류시간' => 'recommended_visit_duration',
        '추천시간' => 'best_visit_time',
        '평일혼잡도' => 'crowd_level_weekday',
        '주말혼잡도' => 'crowd_level_weekend',
        '포토스팟' => 'photo_spots',
        '방문팁' => 'visit_tips'
    ];
    
    /**
     * 카테고리 매핑
     */
    private $category_mapping = [
        '패션' => 'popup_fashion',
        '뷰티' => 'popup_beauty',
        '라이프스타일' => 'popup_lifestyle',
        '푸드' => 'popup_food',
        '아트' => 'popup_art',
        '테크' => 'popup_tech',
        '스포츠' => 'popup_sports'
    ];
    
    /**
     * 운영 상태 매핑
     */
    private $status_mapping = [
        'open' => 'open',
        'coming_soon' => 'upcoming',
        'closed' => 'closed',
        '운영중' => 'open',
        '오픈예정' => 'upcoming',
        '종료' => 'closed'
    ];
    
    /**
     * CSV 파일 검증
     */
    public function validate_csv($file_path) {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'data' => []
        ];
        
        // 파일 열기
        if (($handle = fopen($file_path, "r")) !== FALSE) {
            // BOM 제거
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            
            // 헤더 읽기
            $headers = fgetcsv($handle, 0, ",");
            if (!$headers) {
                $results['valid'] = false;
                $results['errors'][] = "CSV 파일 헤더를 읽을 수 없습니다.";
                return $results;
            }
            
            // 헤더 검증
            $header_validation = $this->validate_headers($headers);
            if (!$header_validation['valid']) {
                $results['valid'] = false;
                $results['errors'][] = $header_validation['error'];
                return $results;
            }
            
            // 데이터 검증
            $row_number = 2;
            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                // 빈 행 건너뛰기
                if (empty(array_filter($data))) {
                    continue;
                }
                
                $row_data = array_combine($headers, $data);
                $validation = $this->validate_row($row_data, $row_number);
                
                if (!empty($validation['errors'])) {
                    $results['errors'] = array_merge($results['errors'], $validation['errors']);
                    $results['valid'] = false;
                }
                
                if (!empty($validation['warnings'])) {
                    $results['warnings'] = array_merge($results['warnings'], $validation['warnings']);
                }
                
                // 추가 정보 계산
                $row_data['_validation_status'] = empty($validation['errors']) ? 'valid' : 'error';
                $row_data['_is_duplicate'] = $this->check_duplicate($row_data);
                $row_data['_days_remaining'] = $this->calculate_days_remaining($row_data['종료일']);
                $row_data['_mapped_category'] = $this->map_category($row_data['카테고리']);
                $row_data['_mapped_status'] = $this->map_status($row_data['운영상태']);
                
                $results['data'][] = $row_data;
                $row_number++;
            }
            fclose($handle);
        } else {
            $results['valid'] = false;
            $results['errors'][] = "CSV 파일을 열 수 없습니다.";
        }
        
        return $results;
    }
    
    /**
     * 헤더 검증
     */
    private function validate_headers($headers) {
        $missing_columns = [];
        
        foreach ($this->required_columns as $required) {
            if (!in_array($required, $headers)) {
                $missing_columns[] = $required;
            }
        }
        
        if (!empty($missing_columns)) {
            return [
                'valid' => false,
                'error' => "필수 컬럼이 누락되었습니다: " . implode(', ', $missing_columns)
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * 행 검증
     */
    private function validate_row($data, $row_number) {
        $errors = [];
        $warnings = [];
        
        // 필수 필드 검증
        foreach ($this->required_columns as $column) {
            if (empty($data[$column])) {
                $errors[] = "행 {$row_number}: {$column}은(는) 필수입니다.";
            }
        }
        
        // 날짜 형식 검증
        if (!empty($data['시작일']) && !$this->validate_date($data['시작일'])) {
            $errors[] = "행 {$row_number}: 시작일 형식이 올바르지 않습니다. (YYYY-MM-DD)";
        }
        
        if (!empty($data['종료일']) && !$this->validate_date($data['종료일'])) {
            $errors[] = "행 {$row_number}: 종료일 형식이 올바르지 않습니다. (YYYY-MM-DD)";
        }
        
        // 날짜 논리 검증
        if (!empty($data['시작일']) && !empty($data['종료일'])) {
            $start = strtotime($data['시작일']);
            $end = strtotime($data['종료일']);
            
            if ($start > $end) {
                $errors[] = "행 {$row_number}: 시작일이 종료일보다 늦습니다.";
            }
            
            if ($end < strtotime('today')) {
                $warnings[] = "행 {$row_number}: 이미 종료된 팝업스토어입니다.";
            }
        }
        
        // 주소 검증
        if (!empty($data['주소'])) {
            if (strpos($data['주소'], '성수') === false && strpos($data['주소'], '성동구') === false) {
                $warnings[] = "행 {$row_number}: 주소가 성수동이 아닐 수 있습니다.";
            }
        }
        
        // 카테고리 검증
        if (!empty($data['카테고리']) && !isset($this->category_mapping[$data['카테고리']])) {
            $warnings[] = "행 {$row_number}: 인식할 수 없는 카테고리입니다. 기본값으로 설정됩니다.";
        }
        
        // Y/N 필드 검증
        $yn_fields = ['예약필수', '주차가능'];
        foreach ($yn_fields as $field) {
            if (!empty($data[$field]) && !in_array(strtoupper($data[$field]), ['Y', 'N'])) {
                $warnings[] = "행 {$row_number}: {$field}는 Y 또는 N으로 입력해주세요.";
            }
        }
        
        return ['errors' => $errors, 'warnings' => $warnings];
    }
    
    /**
     * 날짜 형식 검증
     */
    private function validate_date($date) {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date) !== false;
    }
    
    /**
     * 중복 체크
     */
    private function check_duplicate($data) {
        global $wpdb;
        
        // 브랜드명 + 주소로 중복 확인
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = 'brand_name'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'address'
            WHERE p.post_type = 'places' 
            AND p.post_status IN ('publish', 'draft')
            AND pm1.meta_value = %s
            AND pm2.meta_value = %s",
            $data['브랜드명'],
            $data['주소']
        ));
        
        return $existing > 0;
    }
    
    /**
     * 남은 일수 계산
     */
    private function calculate_days_remaining($end_date) {
        if (empty($end_date)) return null;
        
        $end = strtotime($end_date);
        $today = strtotime('today');
        $diff = $end - $today;
        
        return $diff > 0 ? ceil($diff / 86400) : 0;
    }
    
    /**
     * 카테고리 매핑
     */
    private function map_category($category) {
        return isset($this->category_mapping[$category]) 
            ? $this->category_mapping[$category] 
            : 'popup_lifestyle'; // 기본값
    }
    
    /**
     * 상태 매핑
     */
    private function map_status($status) {
        return isset($this->status_mapping[$status]) 
            ? $this->status_mapping[$status] 
            : 'upcoming'; // 기본값
    }
    
    /**
     * CSV 데이터 일괄 등록
     */
    public function import($data, $options = []) {
        $results = [
            'success' => 0,
            'skipped' => 0,
            'failed' => 0,
            'created_posts' => [],
            'errors' => [],
            'log' => []
        ];
        
        foreach ($data as $index => $row) {
            $row_num = $index + 2; // 헤더 포함 행 번호
            
            // 중복 처리
            if ($row['_is_duplicate'] && $options['duplicate_action'] === 'skip') {
                $results['log'][] = "행 {$row_num}: {$row['브랜드명']} - 중복으로 건너뜀";
                $results['skipped']++;
                continue;
            }
            
            // WordPress 포스트 생성
            try {
                $post_data = [
                    'post_title' => $row['스토어명'],
                    'post_type' => 'places',
                    'post_status' => $options['auto_publish'] ? 'publish' : 'draft',
                    'post_content' => $row['설명'] ?? ''
                ];
                
                $post_id = wp_insert_post($post_data);
                
                if (is_wp_error($post_id)) {
                    throw new Exception($post_id->get_error_message());
                }
                
                // 메타데이터 저장
                $this->save_meta_data($post_id, $row);
                
                // 장소 유형 설정 (항상 popup_store)
                wp_set_object_terms($post_id, 'popup_store', 'place_type');
                
                // 카테고리 설정
                if (!empty($row['_mapped_category'])) {
                    wp_set_object_terms($post_id, $row['_mapped_category'], 'place_category');
                }
                
                // 좌표 변환 트리거
                if (!empty($row['주소'])) {
                    do_action('sungsuya_trigger_geocoding', $post_id, $row['주소']);
                }
                
                $results['created_posts'][] = $post_id;
                $results['log'][] = "행 {$row_num}: {$row['스토어명']} - 등록 성공 (ID: {$post_id})";
                $results['success']++;
                
            } catch (Exception $e) {
                $results['errors'][] = "행 {$row_num}: 등록 실패 - " . $e->getMessage();
                $results['log'][] = "행 {$row_num}: {$row['브랜드명']} - 등록 실패: " . $e->getMessage();
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * 메타데이터 저장
     */
    private function save_meta_data($post_id, $row) {
        // 공통 메타데이터
        foreach ($this->column_to_meta_mapping as $csv_column => $meta_key) {
            if (!empty($row[$csv_column])) {
                $value = $row[$csv_column];
                
                // 특별 처리가 필요한 필드
                if (in_array($csv_column, ['예약필수', '주차가능'])) {
                    $value = $this->convert_yn_to_option($csv_column, $value);
                } elseif ($csv_column === '인스타그램') {
                    $value = $this->normalize_instagram($value);
                } elseif (in_array($csv_column, ['체류시간', '추천시간', '평일혼잡도', '주말혼잡도'])) {
                    $value = $this->map_select_value($csv_column, $value);
                }
                
                update_post_meta($post_id, $meta_key, sanitize_text_field($value));
            }
        }
        
        // 운영 상태 특별 처리
        $status = $row['_mapped_status'] ?? 'upcoming';
        update_post_meta($post_id, 'operation_status', $status);
        
        // 시스템 메타데이터
        update_post_meta($post_id, '_import_source', 'csv');
        update_post_meta($post_id, '_import_date', current_time('mysql'));
        if (!empty($row['_days_remaining'])) {
            update_post_meta($post_id, '_days_remaining', $row['_days_remaining']);
        }
    }
    
    /**
     * Y/N을 옵션 값으로 변환
     */
    private function convert_yn_to_option($field, $value) {
        $value = strtoupper($value);
        
        if ($field === '예약필수') {
            return $value === 'Y' ? 'required' : 'no';
        } elseif ($field === '주차가능') {
            return $value === 'Y' ? 'available' : 'unavailable';
        }
        
        return $value;
    }
    
    /**
     * 인스타그램 정규화
     */
    private function normalize_instagram($value) {
        // @로 시작하지 않으면 추가
        if (strpos($value, '@') !== 0 && strpos($value, 'http') !== 0) {
            return '@' . $value;
        }
        return $value;
    }
    
    /**
     * 선택 필드 값 매핑
     */
    private function map_select_value($field, $value) {
        $mappings = [
            '체류시간' => [
                '15-30분' => '15-30',
                '30-60분' => '30-60',
                '30분-1시간' => '30-60',
                '60-90분' => '60-90',
                '1-1.5시간' => '60-90',
                '90-120분' => '90-120',
                '1.5-2시간' => '90-120',
                '2시간 이상' => '120+'
            ],
            '추천시간' => [
                '오전' => 'morning',
                '점심' => 'lunch',
                '오후' => 'afternoon',
                '저녁' => 'evening',
                '밤' => 'night',
                '언제든지' => 'anytime'
            ],
            '평일혼잡도' => [
                '여유' => 'low',
                '여유로움' => 'low',
                '보통' => 'medium',
                '혼잡' => 'high',
                '혼잡함' => 'high',
                '매우혼잡' => 'very_high'
            ],
            '주말혼잡도' => [
                '여유' => 'low',
                '여유로움' => 'low',
                '보통' => 'medium',
                '혼잡' => 'high',
                '혼잡함' => 'high',
                '매우혼잡' => 'very_high'
            ]
        ];
        
        if (isset($mappings[$field][$value])) {
            return $mappings[$field][$value];
        }
        
        // 기본값 반환
        return strtolower($value);
    }
    
    /**
     * CSV 템플릿 생성
     */
    public function generate_template() {
        $headers = array_merge($this->required_columns, $this->optional_columns);
        
        // 샘플 데이터
        $sample_data = [
            '나이키',
            '나이키 에어맥스 팝업',
            '서울 성동구 연무장길 7',
            date('Y-m-d'),
            date('Y-m-d', strtotime('+14 days')),
            '패션',
            'open',
            '11:00-20:00',
            '02-1234-5678',
            '@nike_seoul',
            'https://nike.com',
            'N',
            '무료',
            'Y',
            '카시나',
            '에어맥스 신제품 체험 공간',
            '30-60분',
            '입구 네온사인',
            '주말 오전 방문 추천',
            'contact@nike.com',
            '#나이키성수 #에어맥스',
            'https://booking.nike.com',
            '20대',
            '중간',
            '전연령',
            '보통',
            '혼잡',
            '오후'
        ];
        
        // CSV 생성
        $output = fopen('php://temp', 'r+');
        
        // BOM 추가 (Excel 한글 깨짐 방지)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // 헤더 쓰기
        fputcsv($output, $headers);
        
        // 샘플 데이터 쓰기
        fputcsv($output, $sample_data);
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
