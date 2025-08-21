<?php
/**
 * 팝업스토어 교통정보 섹션
 * 지하철역, 버스정류장, 주차 정보 등
 */

$store_id = get_the_ID();

// 위치 정보
$latitude = get_post_meta($store_id, 'latitude', true) ?: 
           get_post_meta($store_id, '_store_latitude', true) ?: 
           get_post_meta($store_id, '_latitude', true);
           
$longitude = get_post_meta($store_id, 'longitude', true) ?: 
            get_post_meta($store_id, '_store_longitude', true) ?: 
            get_post_meta($store_id, '_longitude', true);

// 지하철역 정보 (직접 입력된 것이 있으면 우선 사용)
$nearest_subway = get_post_meta($store_id, 'nearest_subway', true) ?: 
                 get_post_meta($store_id, '_nearest_subway', true);

// 교통 정보
$parking_info = get_post_meta($store_id, 'parking_info', true) ?: 
               get_post_meta($store_id, '_parking_info', true);
               
$bus_info = get_post_meta($store_id, 'bus_info', true) ?: 
           get_post_meta($store_id, '_bus_info', true);

// 성수동 주요 지하철역 정보 (위치 기반 거리 계산용)
$subway_stations = [
    ['name' => '성수역', 'line' => '2호선', 'lat' => 37.5447, 'lng' => 127.0558, 'exits' => '1, 2번 출구'],
    ['name' => '뚝섬역', 'line' => '2호선', 'lat' => 37.5472, 'lng' => 127.0472, 'exits' => '1, 2번 출구'],
    ['name' => '건대입구역', 'line' => '2/7호선', 'lat' => 37.5402, 'lng' => 127.0695, 'exits' => '1, 2, 3번 출구'],
    ['name' => '왕십리역', 'line' => '2/5호선', 'lat' => 37.5618, 'lng' => 127.0378, 'exits' => '1, 2번 출구'],
    ['name' => '서울숲역', 'line' => '분당선', 'lat' => 37.5440, 'lng' => 127.0431, 'exits' => '1, 2번 출구']
];

// 가장 가까운 지하철역 계산
$nearest_station = null;
$min_distance = PHP_INT_MAX;

if ($latitude && $longitude) {
    foreach ($subway_stations as $station) {
        $distance = calculateDistance($latitude, $longitude, $station['lat'], $station['lng']);
        if ($distance < $min_distance) {
            $min_distance = $distance;
            $nearest_station = $station;
            $nearest_station['distance'] = $distance;
            $nearest_station['walk_time'] = round($distance / 80); // 시속 4.8km 기준
        }
    }
}

// 거리 계산 함수 (하버사인 공식)
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000; // 지구 반지름 (미터)
    
    $lat1_rad = deg2rad($lat1);
    $lat2_rad = deg2rad($lat2);
    $delta_lat = deg2rad($lat2 - $lat1);
    $delta_lon = deg2rad($lon2 - $lon1);
    
    $a = sin($delta_lat/2) * sin($delta_lat/2) +
         cos($lat1_rad) * cos($lat2_rad) *
         sin($delta_lon/2) * sin($delta_lon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earth_radius * $c;
}
?>

<?php if ($nearest_station || $nearest_subway || $parking_info || $bus_info) : ?>
<div class="container">
    <div class="section-header">
        <h2 class="section-title">
            <span class="section-icon">🚇</span>
            교통 정보
        </h2>
    </div>
    
    <div class="transport-info-grid">
        
        <!-- 지하철 정보 -->
        <?php if ($nearest_station || $nearest_subway) : ?>
        <div class="transport-item subway-info">
            <div class="transport-icon">🚇</div>
            <div class="transport-content">
                <h3 class="transport-title">지하철</h3>
                
                <?php if ($nearest_station) : ?>
                    <div class="subway-station primary-station">
                        <div class="station-main">
                            <span class="station-name"><?php echo esc_html($nearest_station['name']); ?></span>
                            <span class="station-line"><?php echo esc_html($nearest_station['line']); ?></span>
                        </div>
                        <div class="station-details">
                            <span class="station-distance">
                                도보 <?php echo esc_html($nearest_station['walk_time']); ?>분 
                                (<?php echo esc_html(round($nearest_station['distance'])); ?>m)
                            </span>
                            <span class="station-exits"><?php echo esc_html($nearest_station['exits']); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($nearest_subway && $nearest_subway !== ($nearest_station['name'] ?? '')) : ?>
                    <div class="subway-station manual-station">
                        <div class="station-main">
                            <span class="station-name"><?php echo esc_html($nearest_subway); ?></span>
                            <span class="station-note">관리자 등록 정보</span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- 다른 이용 가능한 역들 -->
                <?php if ($nearest_station && count($subway_stations) > 1) : ?>
                    <div class="alternative-stations">
                        <h4 class="alt-title">다른 이용 가능한 역</h4>
                        <?php 
                        $alternative_count = 0;
                        foreach ($subway_stations as $station) : 
                            if ($station['name'] === $nearest_station['name'] || $alternative_count >= 2) continue;
                            $distance = calculateDistance($latitude, $longitude, $station['lat'], $station['lng']);
                            $walk_time = round($distance / 80);
                            if ($walk_time <= 15) : // 15분 이내만 표시
                                $alternative_count++;
                        ?>
                            <div class="alt-station">
                                <span class="alt-name"><?php echo esc_html($station['name']); ?></span>
                                <span class="alt-line"><?php echo esc_html($station['line']); ?></span>
                                <span class="alt-time">도보 <?php echo esc_html($walk_time); ?>분</span>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 버스 정보 -->
        <?php if ($bus_info) : ?>
        <div class="transport-item bus-info">
            <div class="transport-icon">🚌</div>
            <div class="transport-content">
                <h3 class="transport-title">버스</h3>
                <div class="bus-content">
                    <?php echo wp_kses_post(wpautop($bus_info)); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 주차 정보 -->
        <?php if ($parking_info) : ?>
        <div class="transport-item parking-info">
            <div class="transport-icon">🚗</div>
            <div class="transport-content">
                <h3 class="transport-title">주차</h3>
                <div class="parking-content">
                    <?php echo wp_kses_post(wpautop($parking_info)); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 성수동 교통 팁 -->
        <div class="transport-item transport-tips">
            <div class="transport-icon">💡</div>
            <div class="transport-content">
                <h3 class="transport-title">교통 팁</h3>
                <div class="tips-content">
                    <ul class="tips-list">
                        <li>성수역에서 도보로 성수동 카페거리 접근 가능</li>
                        <li>주말에는 주차 공간이 부족할 수 있으니 대중교통 이용 권장</li>
                        <li>한강공원과 서울숲 근접으로 산책 코스 연계 가능</li>
                    </ul>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- 교통 정보 없는 경우 기본 안내 -->
    <?php if (!$nearest_station && !$nearest_subway && !$parking_info && !$bus_info) : ?>
    <div class="no-transport-info">
        <div class="no-transport-icon">🚇</div>
        <h3>교통 정보 준비중</h3>
        <p>자세한 교통 정보는 곧 업데이트될 예정입니다.</p>
        <div class="basic-transport-info">
            <p><strong>성수동 주요 지하철역:</strong> 성수역(2호선), 뚝섬역(2호선), 서울숲역(분당선)</p>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
