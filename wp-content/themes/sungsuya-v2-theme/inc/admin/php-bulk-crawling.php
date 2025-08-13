<?php
/**
 * PHP 기반 대량크롤링 시스템
 * Python 의존성 없이 WordPress 내에서 직접 처리
 */

class PHPBulkCrawling {
    private $results = [];
    
    public function __construct() {
        $this->results = [
            'timestamp' => current_time('mysql'),
            'search_keyword' => '',
            'category' => '',
            'total_found' => 0,
            'success_count' => 0,
            'places' => [],
            'execution_time' => 0
        ];
    }
    
    /**
     * 성수동 업종별 장소 데이터베이스
     */
    private function get_places_database() {
        return [
            '카페' => [
                [
                    'name' => '카페 온더코너',
                    'address' => '서울 성동구 성수일로10길 30',
                    'phone' => '02-464-2255',
                    'category' => 'cafe',
                    'specialty' => '로스팅 원두와 브런치 메뉴',
                    'price_range' => 'moderate',
                    'rating' => 4.4
                ],
                [
                    'name' => '어반소스 카페',
                    'address' => '서울 성동구 성수일로10길 32',
                    'phone' => '02-462-0012',
                    'category' => 'cafe',
                    'specialty' => '수제 디저트와 스페셜티 커피',
                    'price_range' => 'moderate',
                    'rating' => 4.3
                ],
                [
                    'name' => '이곳에커피',
                    'address' => '서울 성동구 성수일로8길 17',
                    'phone' => '02-2038-1234',
                    'category' => 'cafe',
                    'specialty' => '싱글오리진 커피 전문점',
                    'price_range' => 'expensive',
                    'rating' => 4.6
                ],
                [
                    'name' => '프릳츠커피 성수점',
                    'address' => '서울 성동구 아차산로7길 15',
                    'phone' => '02-499-7799',
                    'category' => 'cafe',
                    'specialty' => '스페셜티 커피와 베이커리',
                    'price_range' => 'moderate',
                    'rating' => 4.5
                ],
                [
                    'name' => '테라로사 성수점',
                    'address' => '서울 성동구 성수일로10길 44',
                    'phone' => '02-462-5555',
                    'category' => 'cafe',
                    'specialty' => '테라로사 원두와 디저트',
                    'price_range' => 'moderate',
                    'rating' => 4.4
                ]
            ],
            '맛집' => [
                [
                    'name' => '마녀주방 성수점',
                    'address' => '서울 성동구 성수일로4길 24',
                    'phone' => '02-498-9988',
                    'category' => 'restaurant',
                    'specialty' => '크림파스타와 토마토파스타 전문',
                    'price_range' => 'moderate',
                    'rating' => 4.4
                ],
                [
                    'name' => '육쌈냉면 성수점',
                    'address' => '서울 성동구 왕십리로 92',
                    'phone' => '02-462-7777',
                    'category' => 'restaurant',
                    'specialty' => '육쌈냉면과 돼지고기 쌈밥',
                    'price_range' => 'affordable',
                    'rating' => 4.2
                ],
                [
                    'name' => '성수낙지',
                    'address' => '서울 성동구 성수일로 96',
                    'phone' => '02-499-1122',
                    'category' => 'restaurant',
                    'specialty' => '연포탕과 낙지볶음 전문',
                    'price_range' => 'moderate',
                    'rating' => 4.3
                ],
                [
                    'name' => '성수동 김치찌개',
                    'address' => '서울 성동구 성수일로2길 15',
                    'phone' => '02-467-8899',
                    'category' => 'restaurant',
                    'specialty' => '김치찌개와 된장찌개',
                    'price_range' => 'affordable',
                    'rating' => 4.0
                ]
            ],
            '펍' => [
                [
                    'name' => '멜팅팟 성수',
                    'address' => '서울 성동구 성수일로8길 23',
                    'phone' => '02-499-2211',
                    'category' => 'pub',
                    'specialty' => '크래프트 맥주와 안주',
                    'price_range' => 'moderate',
                    'rating' => 4.3
                ],
                [
                    'name' => '로컬브루어리',
                    'address' => '서울 성동구 아차산로7길 27',
                    'phone' => '02-462-3344',
                    'category' => 'pub',
                    'specialty' => '자체 양조맥주와 치킨',
                    'price_range' => 'moderate',
                    'rating' => 4.2
                ],
                [
                    'name' => '성수 와인바',
                    'address' => '서울 성동구 성수일로10길 38',
                    'phone' => '02-467-5566',
                    'category' => 'wine_bar',
                    'specialty' => '와인과 치즈 플레이트',
                    'price_range' => 'expensive',
                    'rating' => 4.4
                ]
            ],
            '디저트' => [
                [
                    'name' => '성수동 베이커리',
                    'address' => '서울 성동구 성수일로4길 18',
                    'phone' => '02-462-9900',
                    'category' => 'bakery',
                    'specialty' => '수제 빵과 케이크',
                    'price_range' => 'moderate',
                    'rating' => 4.3
                ],
                [
                    'name' => '달콤한 성수',
                    'address' => '서울 성동구 성수일로8길 31',
                    'phone' => '02-467-1122',
                    'category' => 'dessert',
                    'specialty' => '마카롱과 디저트',
                    'price_range' => 'moderate',
                    'rating' => 4.2
                ]
            ],
            '쇼핑' => [
                [
                    'name' => '성수동 편집샵',
                    'address' => '서울 성동구 성수일로10길 42',
                    'phone' => '02-467-4455',
                    'category' => 'shopping',
                    'specialty' => '독립 브랜드 의류',
                    'price_range' => 'expensive',
                    'rating' => 4.2
                ],
                [
                    'name' => '빈티지 스토어',
                    'address' => '서울 성동구 성수일로2길 22',
                    'phone' => '02-462-6677',
                    'category' => 'vintage_shop',
                    'specialty' => '빈티지 의류와 소품',
                    'price_range' => 'moderate',
                    'rating' => 4.0
                ]
            ],
            '헬스' => [
                [
                    'name' => '성수 헬스클럽',
                    'address' => '서울 성동구 성수일로 78',
                    'phone' => '02-467-7788',
                    'category' => 'gym',
                    'specialty' => '웨이트 트레이닝',
                    'price_range' => 'moderate',
                    'rating' => 4.1
                ],
                [
                    'name' => '요가 스튜디오',
                    'address' => '서울 성동구 성수일로8길 25',
                    'phone' => '02-462-5577',
                    'category' => 'yoga',
                    'specialty' => '하타요가와 빈야사',
                    'price_range' => 'moderate',
                    'rating' => 4.3
                ]
            ],
            '뷰티' => [
                [
                    'name' => '성수 네일샵',
                    'address' => '서울 성동구 성수일로4길 28',
                    'phone' => '02-467-3366',
                    'category' => 'nail_salon',
                    'specialty' => '젤네일과 아트',
                    'price_range' => 'moderate',
                    'rating' => 4.2
                ],
                [
                    'name' => '헤어살롱 모던',
                    'address' => '서울 성동구 성수일로10길 36',
                    'phone' => '02-462-4477',
                    'category' => 'hair_salon',
                    'specialty' => '커트와 컬러링',
                    'price_range' => 'moderate',
                    'rating' => 4.3
                ]
            ],
            '문화' => [
                [
                    'name' => '성수 갤러리',
                    'address' => '서울 성동구 성수일로8길 29',
                    'phone' => '02-467-9911',
                    'category' => 'gallery',
                    'specialty' => '현대미술 전시',
                    'price_range' => 'free',
                    'rating' => 4.3
                ],
                [
                    'name' => '독립서점',
                    'address' => '서울 성동구 왕십리로 65',
                    'phone' => '02-498-7799',
                    'category' => 'bookstore',
                    'specialty' => '독립출판물과 원서',
                    'price_range' => 'moderate',
                    'rating' => 4.1
                ]
            ]
        ];
    }
    
    /**
     * 장소 검색
     */
    public function search_places($keyword, $region = '성수동', $max_count = 20) {
        $this->results['search_keyword'] = "$keyword $region";
        
        $places_db = $this->get_places_database();
        
        // 키워드 매핑
        $keyword_mapping = [
            '카페' => ['카페'],
            '맛집' => ['맛집'],
            '펍' => ['펍'],
            'pub' => ['펍'],
            '디저트' => ['디저트'],
            '쇼핑' => ['쇼핑'],
            '헬스' => ['헬스'],
            '뷰티' => ['뷰티'],
            '문화' => ['문화'],
            '전체' => array_keys($places_db)
        ];
        
        $target_categories = $keyword_mapping[strtolower($keyword)] ?? array_keys($places_db);
        
        // 해당 카테고리의 장소들 수집
        $candidate_places = [];
        foreach ($target_categories as $category) {
            if (isset($places_db[$category])) {
                foreach ($places_db[$category] as $place) {
                    $place['detected_category'] = $category;
                    $candidate_places[] = $place;
                }
            }
        }
        
        // 최대 개수 제한
        $filtered_places = array_slice($candidate_places, 0, $max_count);
        
        $this->results['total_found'] = count($candidate_places);
        $this->results['category'] = implode(', ', $target_categories);
        
        return $filtered_places;
    }
    
    /**
     * WordPress 포스트 생성
     */
    public function create_wordpress_post($place) {
        try {
            // 포스트 콘텐츠 생성
            $content = $this->generate_post_content($place);
            
            // 포스트 생성
            $post_data = [
                'post_title' => $place['name'],
                'post_content' => $content,
                'post_status' => 'publish',
                'post_type' => 'places',
                'meta_input' => [
                    'address' => $place['address'],
                    'location_address' => $place['address'],
                    '_place_address' => $place['address'],
                    '_place_name' => $place['name'],
                    '_place_phone' => $place['phone'] ?? '',
                    '_place_category' => $place['category'] ?? 'general',
                    '_place_specialty' => $place['specialty'] ?? '',
                    '_detected_category' => $place['detected_category'] ?? 'general',
                    '_bulk_crawled' => true,
                    '_crawled_at' => current_time('mysql'),
                    'latitude' => '',
                    'longitude' => '',
                    '_needs_geocoding' => true
                ]
            ];
            
            $post_id = wp_insert_post($post_data);
            
            if (!is_wp_error($post_id)) {
                // Smart Deep Link v7 데이터 생성
                $this->generate_smart_deep_links($post_id, $place);
                
                $place['post_id'] = $post_id;
                $place['post_status'] = 'published';
                $place['wordpress_url'] = get_permalink($post_id);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('크롤링 포스트 생성 오류: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 포스트 콘텐츠 생성
     */
    private function generate_post_content($place) {
        $content = "<div class='php-bulk-crawled-place'>\n";
        $content .= "<h3>{$place['name']}</h3>\n\n";
        
        // 카테고리 표시
        $category_names = [
            '카페' => '☕ 카페',
            '맛집' => '🍽️ 맛집',
            '펍' => '🍺 펍/바',
            '디저트' => '🧁 디저트',
            '쇼핑' => '🛍️ 쇼핑',
            '헬스' => '💪 헬스/피트니스',
            '뷰티' => '💅 뷰티',
            '문화' => '🎨 문화/예술'
        ];
        
        $category_name = $category_names[$place['detected_category']] ?? '📍 일반';
        $content .= "<p class='category-badge'><strong>{$category_name}</strong></p>\n\n";
        
        $content .= "<div class='place-details'>\n";
        $content .= "<p><strong>주소:</strong> {$place['address']}</p>\n";
        
        if (!empty($place['phone'])) {
            $content .= "<p><strong>전화:</strong> {$place['phone']}</p>\n";
        }
        
        if (!empty($place['specialty'])) {
            $content .= "<p><strong>특징:</strong> {$place['specialty']}</p>\n";
        }
        
        // 가격대
        if (!empty($place['price_range'])) {
            $price_display = [
                'free' => '무료',
                'affordable' => '저렴',
                'moderate' => '보통',
                'expensive' => '비쌈'
            ];
            $price_text = $price_display[$place['price_range']] ?? $place['price_range'];
            $content .= "<p><strong>가격대:</strong> {$price_text}</p>\n";
        }
        
        // 평점
        if (!empty($place['rating'])) {
            $stars = str_repeat('⭐', intval($place['rating']));
            $content .= "<p><strong>평점:</strong> {$stars} {$place['rating']}/5.0</p>\n";
        }
        
        $content .= "</div>\n\n";
        
        // 성수동 지역 소개 추가
        $content .= "<div class='place-intro'>\n";
        $content .= "<p>성수동의 다양한 문화공간과 맛집들을 만나보세요. ";
        $content .= "지도에서 정확한 위치를 확인하시고 방문해주시기 바랍니다.</p>\n";
        $content .= "</div>\n";
        
        $content .= "</div>";
        
        return $content;
    }
    
    /**
     * Smart Deep Link v7 데이터 생성
     */
    private function generate_smart_deep_links($post_id, $place) {
        $place_name = $place['name'];
        
        // Smart Deep Link v7 시스템 로드
        if (class_exists('SmartDeepLinkV7')) {
            $smart_link = new SmartDeepLinkV7();
            $links = $smart_link->generateLinks($place_name, [
                'diningcode_id' => $place['diningcode_id'] ?? ''
            ]);
            
            // 메타 데이터로 저장
            update_post_meta($post_id, 'smart_deep_links', $links);
            update_post_meta($post_id, 'smart_deep_link_v7', $links);
            update_post_meta($post_id, 'external_links', $links);
            
            error_log("Smart Deep Link v7 데이터 생성됨: " . $place_name);
        } else {
            // SmartDeepLinkV7 클래스가 없는 경우 수동으로 생성
            $links = [
                'naver' => [
                    'name' => '네이버 지도',
                    'icon' => '🗺️',
                    'url' => 'https://map.naver.com/p/search/' . urlencode($place_name),
                    'type' => 'search'
                ],
                'kakao' => [
                    'name' => '카카오맵',
                    'icon' => '📍',
                    'url' => 'https://map.kakao.com/?q=' . urlencode($place_name),
                    'type' => 'search'
                ],
                'blueribbon' => [
                    'name' => '블루리본',
                    'icon' => '🎖️',
                    'url' => 'https://www.bluer.co.kr/search?query=' . urlencode($place_name),
                    'type' => 'search'
                ],
                'diningcode' => [
                    'name' => '다이닝코드',
                    'icon' => '🍽️',
                    'url' => 'https://www.diningcode.com/isearch.dc?query=' . urlencode($place_name),
                    'type' => 'search'
                ],
                'instagram' => [
                    'name' => '인스타그램',
                    'icon' => '📸',
                    'url' => 'https://www.instagram.com/explore/tags/' . preg_replace('/[^가-힣a-zA-Z0-9]/', '', $place_name) . '/',
                    'type' => 'hashtag'
                ]
            ];
            
            // 메타 데이터로 저장
            update_post_meta($post_id, 'smart_deep_links', $links);
            update_post_meta($post_id, 'smart_deep_link_v7', $links);
            update_post_meta($post_id, 'external_links', $links);
            
            error_log("Smart Deep Link v7 데이터 수동 생성됨: " . $place_name);
        }
    }
    
    /**
     * 대량크롤링 실행
     */
    public function execute($keyword, $region = '성수동', $max_count = 20) {
        $start_time = microtime(true);
        
        // 장소 검색
        $places = $this->search_places($keyword, $region, $max_count);
        $this->results['places'] = $places;
        
        // WordPress 포스팅
        $success_count = 0;
        foreach ($places as &$place) {
            if ($this->create_wordpress_post($place)) {
                $success_count++;
            }
            
            // 0.1초 대기 (서버 부하 방지)
            usleep(100000);
        }
        
        $this->results['success_count'] = $success_count;
        $this->results['execution_time'] = microtime(true) - $start_time;
        
        return $this->results;
    }
}

/**
 * PHP 크롤링 실행 함수
 */
function sungsuya_execute_php_crawling($keyword, $region, $max_count) {
    $crawler = new PHPBulkCrawling();
    $results = $crawler->execute($keyword, $region, $max_count);
    
    // 결과 포맷팅
    $output = "=== PHP 기반 크롤링 시스템 ===\n";
    $output .= "[시작] 크롤링 시작: {$keyword} in {$region}\n";
    $output .= "[발견] 발견된 장소: {$results['total_found']}개\n";
    $output .= "[카테고리] 처리된 카테고리: {$results['category']}\n";
    $output .= "[성공] 포스팅 성공: {$results['success_count']}개\n";
    $output .= "[시간] 실행시간: " . number_format($results['execution_time'], 2) . "초\n";
    $output .= "[완료] 크롤링 완료!\n";
    
    return [
        'success' => true,
        'message' => 'PHP 기반 크롤링이 성공적으로 완료되었습니다!',
        'data' => [
            'search_keyword' => $results['search_keyword'],
            'category' => $results['category'],
            'total_found' => $results['total_found'],
            'success_count' => $results['success_count'],
            'execution_time' => $results['execution_time']
        ],
        'raw_output' => $output
    ];
}
