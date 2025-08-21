    /**
     * 대량 크롤링 로그에서 진행 상황 파싱 (WordPress 포스트 생성 추가)
     */
    private function parse_bulk_progress($log_content, $session_data) {
        $progress = array(
            'status' => 'running',
            'log' => $log_content,
            'overall_progress' => 0,
            'current_step' => '초기화 중',
            'discovered_count' => 0,
            'estimated_time' => '계산 중...'
        );
        
        // 로그 내용을 기반으로 진행 상황 업데이트 (task2.25 출력에 맞춰 수정)
        if (strpos($log_content, '검색 시작') !== false) {
            $progress['current_step'] = '장소 검색 중';
            $progress['overall_progress'] = 20;
        }
        
        if (strpos($log_content, '품질 필터링 시작') !== false) {
            $progress['current_step'] = '품질 필터링 중';
            $progress['overall_progress'] = 40;
        }
        
        if (strpos($log_content, '중복 제거 시작') !== false) {
            $progress['current_step'] = '중복 제거 중';
            $progress['overall_progress'] = 60;
        }
        
        if (strpos($log_content, 'WordPress 포스팅 시작') !== false) {
            $progress['current_step'] = 'WordPress 포스팅 중';
            $progress['overall_progress'] = 80;
        }
        
        // 발견된 장소 수 파싱
        if (strpos($log_content, '발견된 장소') !== false) {
            preg_match('/발견된 장소: (\d+)개/', $log_content, $matches);
            if (isset($matches[1])) {
                $progress['discovered_count'] = intval($matches[1]);
            }
        }
        
        // 크롤링 완료 확인 및 실제 WordPress 포스트 생성
        if (strpos($log_content, '완료') !== false) {
            $progress['current_step'] = '크롤링 완료 - WordPress 포스트 생성 중';
            $progress['overall_progress'] = 90;
            
            // JSON 결과 파일에서 실제 WordPress 포스트 생성
            $json_pattern = ABSPATH . "bulk_crawling_results_*.json";
            $json_files = glob($json_pattern);
            
            if (!empty($json_files)) {
                $latest_json = array_reduce($json_files, function($latest, $file) {
                    return !$latest || filemtime($file) > filemtime($latest) ? $file : $latest;
                });
                
                if ($latest_json) {
                    $bulk_result = json_decode(file_get_contents($latest_json), true);
                    
                    if ($bulk_result && isset($bulk_result['places'])) {
                        // 실제 WordPress 포스트 생성
                        $created_posts = $this->create_wordpress_posts_from_bulk_result($bulk_result);
                        $progress['created_posts'] = $created_posts;
                        $progress['current_step'] = '완료 - ' . count($created_posts) . '개 포스트 생성됨';
                        $progress['overall_progress'] = 100;
                    }
                }
            }
        }
        
        return $progress;
    }
    
    /**
     * 대량 크롤링 결과에서 실제 WordPress 포스트 생성
     */
    private function create_wordpress_posts_from_bulk_result($bulk_result) {
        $created_posts = array();
        
        if (!isset($bulk_result['places']) || !is_array($bulk_result['places'])) {
            return $created_posts;
        }
        
        foreach ($bulk_result['places'] as $place) {
            try {
                // 중복 포스트 확인
                $existing_posts = get_posts(array(
                    'title' => $place['name'],
                    'post_type' => 'places',
                    'post_status' => 'any',
                    'numberposts' => 1
                ));
                
                if (!empty($existing_posts)) {
                    // 기존 포스트가 있으면 업데이트
                    $post_id = $existing_posts[0]->ID;
                    $this->update_existing_place_post($post_id, $place);
                    $created_posts[] = array(
                        'action' => 'updated',
                        'post_id' => $post_id,
                        'name' => $place['name']
                    );
                } else {
                    // 새 포스트 생성
                    $post_id = $this->create_new_place_post($place);
                    if ($post_id && !is_wp_error($post_id)) {
                        $created_posts[] = array(
                            'action' => 'created',
                            'post_id' => $post_id,
                            'name' => $place['name']
                        );
                    }
                }
                
            } catch (Exception $e) {
                error_log('[BULK_CRAWLING] 포스트 생성 오류: ' . $e->getMessage());
            }
        }
        
        return $created_posts;
    }
    
    /**
     * 새로운 Places 포스트 생성
     */
    private function create_new_place_post($place) {
        // 포스트 내용 생성
        $content = $this->generate_place_post_content($place);
        
        // 포스트 데이터
        $post_data = array(
            'post_title' => $place['name'],
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'places',
            'post_author' => get_current_user_id()
        );
        
        // 포스트 생성
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            // 메타 데이터 저장
            $this->save_place_meta_data($post_id, $place);
            return $post_id;
        }
        
        return false;
    }
    
    /**
     * 기존 Places 포스트 업데이트
     */
    private function update_existing_place_post($post_id, $place) {
        // 포스트 내용 업데이트
        $content = $this->generate_place_post_content($place);
        
        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $content
        ));
        
        // 메타 데이터 업데이트
        $this->save_place_meta_data($post_id, $place);
        
        return $post_id;
    }
    
    /**
     * Places 포스트 내용 생성
     */
    private function generate_place_post_content($place) {
        $content = "<div class='bulk-crawled-place'>\n";
        $content .= "<h3>📍 " . esc_html($place['name']) . "</h3>\n\n";
        
        $content .= "<div class='place-details'>\n";
        $content .= "<p><strong>📍 주소:</strong> " . esc_html($place['address']) . "</p>\n";
        
        if (!empty($place['phone'])) {
            $content .= "<p><strong>📞 전화:</strong> " . esc_html($place['phone']) . "</p>\n";
        }
        
        if (!empty($place['specialty'])) {
            $content .= "<p><strong>🍽️ 특징:</strong> " . esc_html($place['specialty']) . "</p>\n";
        }
        
        if (!empty($place['category'])) {
            $category_emoji = ($place['category'] === 'cafe') ? '☕' : '🍴';
            $category_name = ($place['category'] === 'cafe') ? '카페' : '맛집';
            $content .= "<p><strong>{$category_emoji} 카테고리:</strong> {$category_name}</p>\n";
        }
        
        if (!empty($place['price_range'])) {
            $price_display = array(
                'affordable' => '💰 저렴',
                'moderate' => '💰💰 보통',
                'expensive' => '💰💰💰 비쌈'
            );
            $price_text = $price_display[$place['price_range']] ?? $place['price_range'];
            $content .= "<p><strong>💰 가격대:</strong> {$price_text}</p>\n";
        }
        
        if (!empty($place['rating'])) {
            $stars = str_repeat('⭐', floor($place['rating']));
            $content .= "<p><strong>⭐ 평점:</strong> {$stars} " . $place['rating'] . "/5.0</p>\n";
        }
        
        if (!empty($place['quality_score'])) {
            $quality_percent = round($place['quality_score'] * 100);
            $content .= "<p><strong>🏆 품질점수:</strong> {$quality_percent}%</p>\n";
        }
        
        $content .= "</div>\n\n";
        
        $content .= "<div class='crawling-info'>\n";
        $content .= "<p><small>🤖 이 정보는 Enhanced 크롤링 시스템 v2.0으로 자동 수집되었습니다.</small></p>\n";
        $content .= "<p><small>📅 수집일시: " . current_time('Y-m-d H:i:s') . "</small></p>\n";
        $content .= "</div>\n";
        
        $content .= "</div>";
        
        return $content;
    }
    
    /**
     * Places 메타 데이터 저장
     */
    private function save_place_meta_data($post_id, $place) {
        // 기본 정보
        update_post_meta($post_id, '_place_name', $place['name']);
        update_post_meta($post_id, '_place_address', $place['address']);
        
        // 위치 정보
        if (!empty($place['latitude'])) {
            update_post_meta($post_id, '_place_latitude', $place['latitude']);
        }
        if (!empty($place['longitude'])) {
            update_post_meta($post_id, '_place_longitude', $place['longitude']);
        }
        
        // 연락처 정보
        if (!empty($place['phone'])) {
            update_post_meta($post_id, '_place_phone', $place['phone']);
        }
        
        // 카테고리 및 특징
        if (!empty($place['category'])) {
            update_post_meta($post_id, '_place_category', $place['category']);
        }
        if (!empty($place['specialty'])) {
            update_post_meta($post_id, '_place_specialty', $place['specialty']);
        }
        
        // 가격 및 평점
        if (!empty($place['price_range'])) {
            update_post_meta($post_id, '_place_price_range', $place['price_range']);
        }
        if (!empty($place['rating'])) {
            update_post_meta($post_id, '_place_rating', $place['rating']);
        }
        if (!empty($place['quality_score'])) {
            update_post_meta($post_id, '_place_quality_score', $place['quality_score']);
        }
        
        // 크롤링 메타데이터
        update_post_meta($post_id, '_bulk_crawled', true);
        update_post_meta($post_id, '_crawled_at', current_time('mysql'));
        update_post_meta($post_id, '_enhanced_v2_enabled', true);
        
        // 전체 크롤링 데이터를 JSON으로 저장 (백업용)
        update_post_meta($post_id, '_crawling_raw_data', json_encode($place, JSON_UNESCAPED_UNICODE));
    }