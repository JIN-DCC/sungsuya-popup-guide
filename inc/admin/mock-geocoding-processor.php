<?php
/**
 * 임시 Mock 지오코딩 시스템
 * 
 * NCP API 구독 문제 해결 전까지 사용할 임시 솔루션
 */

class MockGeocodingProcessor {
    
    private $mock_coordinates = array(
        '서울 성동구 아차산로 45' => array('latitude' => 37.5506, 'longitude' => 127.0676),
        '서울 성동구 성수일로 96' => array('latitude' => 37.5444, 'longitude' => 127.0548),
        '서울 성동구 왕십리로 92' => array('latitude' => 37.5615, 'longitude' => 127.0370),
        '서울 성동구 성수일로4길 24' => array('latitude' => 37.5450, 'longitude' => 127.0520),
    );
    
    public function __construct() {
        error_log('[Mock지오코딩] 임시 Mock 지오코딩 시스템 활성화');
    }
    
    public function is_api_configured() {
        return true; // Mock 시스템은 항상 준비됨
    }
    
    public function get_pending_places() {
        // 기존 메서드와 동일
        $query = new WP_Query(array(
            'post_type' => 'places',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'latitude',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => 'latitude',
                    'value' => array('', '0', '37.548'),
                    'compare' => 'IN'
                )
            )
        ));
        
        $places = array();
        while ($query->have_posts()) {
            $query->the_post();
            $place_id = get_the_ID();
            $address = get_post_meta($place_id, 'address', true);
            
            if (!empty($address)) {
                $place = (object) array(
                    'ID' => $place_id,
                    'post_title' => get_the_title(),
                    'address' => $address,
                    'latitude' => get_post_meta($place_id, 'latitude', true),
                    'longitude' => get_post_meta($place_id, 'longitude', true)
                );
                $places[] = $place;
            }
        }
        wp_reset_postdata();
        
        return $places;
    }
    
    public function process_batch($places, $progress_callback = null) {
        $results = array(
            'success' => true,
            'total_count' => count($places),
            'success_count' => 0,
            'error_count' => 0,
            'errors' => array()
        );
        
        error_log('[Mock지오코딩] 배치 처리 시작: ' . count($places) . '개 Places');
        
        foreach ($places as $index => $place) {
            $coordinates = $this->mock_geocode($place->address);
            
            if ($coordinates) {
                // 좌표 업데이트
                update_post_meta($place->ID, 'latitude', $coordinates['latitude']);
                update_post_meta($place->ID, 'longitude', $coordinates['longitude']);
                update_post_meta($place->ID, 'geocoded_at', current_time('mysql'));
                update_post_meta($place->ID, 'geocoding_method', 'mock'); // Mock 표시
                
                $results['success_count']++;
                error_log("[Mock지오코딩] 성공: {$place->post_title} -> {$coordinates['latitude']}, {$coordinates['longitude']}");
                
                // 정적지도 생성 (선택적)
                $this->generate_static_map($place->ID, $coordinates['latitude'], $coordinates['longitude'], $place->post_title);
                
            } else {
                $results['error_count']++;
                $results['errors'][] = "Mock 좌표 없음: {$place->post_title}";
                error_log("[Mock지오코딩] 실패: {$place->post_title} - Mock 좌표 없음");
            }
            
            // 진행상황 콜백 호출
            if ($progress_callback) {
                $progress_callback(array(
                    'processed' => $index + 1,
                    'current_place' => $place->post_title,
                    'success_count' => $results['success_count'],
                    'error_count' => $results['error_count']
                ));
            }
            
            // 실제 처리 시뮬레이션 (0.1초 대기)
            usleep(100000);
        }
        
        error_log('[Mock지오코딩] 배치 처리 완료 - 성공: ' . $results['success_count'] . ', 실패: ' . $results['error_count']);
        
        return $results;
    }
    
    private function mock_geocode($address) {
        // 정확한 주소 매칭
        if (isset($this->mock_coordinates[$address])) {
            return $this->mock_coordinates[$address];
        }
        
        // 부분 매칭 (유연한 처리)
        foreach ($this->mock_coordinates as $mock_address => $coords) {
            if (strpos($address, '성동구') !== false) {
                // 성동구 주소면 임의의 성수동 좌표 반환
                return array(
                    'latitude' => 37.5444 + (rand(-50, 50) / 10000), // 약간의 변화
                    'longitude' => 127.0548 + (rand(-50, 50) / 10000)
                );
            }
        }
        
        return false; // Mock 좌표 없음
    }
    
    private function generate_static_map($place_id, $latitude, $longitude, $title) {
        // 정적지도 생성은 선택적으로 구현
        error_log("[Mock지오코딩] 정적지도 생성 요청: {$title}");
    }
    
    public function test_geocoding() {
        return array(
            'success' => true,
            'message' => 'Mock 지오코딩 시스템 정상 작동 (API 구독 문제 해결 전까지 임시 사용)'
        );
    }
    
    public function get_process_logs() {
        return array(
            '[' . current_time('H:i:s') . '] Mock 지오코딩 시스템 사용 중',
            '[' . current_time('H:i:s') . '] NCP API 구독 활성화 후 실제 API로 전환 예정'
        );
    }
}

// 임시 Mock 시스템 활성화
if (!function_exists('enable_mock_geocoding')) {
    function enable_mock_geocoding() {
        // BatchGeocodingProcessor 클래스를 Mock으로 교체
        if (!class_exists('BatchGeocodingProcessor_Original')) {
            class_alias('BatchGeocodingProcessor', 'BatchGeocodingProcessor_Original');
        }
        
        // Mock 클래스로 교체 (주의: 이 방법은 임시용)
        // 실제로는 BatchGeocodingProcessor 내부에서 Mock 모드를 활성화하는 것이 좋음
    }
}

// 관리자 알림 추가
function mock_geocoding_admin_notice() {
    if (is_admin() && get_current_screen()->post_type === 'places') {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>⚠️ 임시 Mock 지오코딩 모드:</strong> ';
        echo 'NCP API 구독 문제로 인해 Mock 좌표를 사용 중입니다. ';
        echo 'NCP 구독 활성화 후 실제 API로 전환하세요.</p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'mock_geocoding_admin_notice');

?>