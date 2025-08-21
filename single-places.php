<?php
/**
 * 성수야! - Places 상세페이지 (개선된 동적 필드 표시)
 * 
 * 개선사항:
 * - PlaceMetaFields 기반 동적 필드 표시
 * - 팝업스토어 전용 섹션 (41개 필드)
 * - 값이 있는 필드만 표시
 * 
 * @package SungsuyaV2
 * @version 10.0.0 - Dynamic Field Display
 */

get_header(); 

// PlaceMetaFields 클래스 로드 확인
if (!class_exists('PlaceMetaFields')) {
    require_once get_template_directory() . '/inc/places/place-meta-fields.php';
}

// 디버깅용 - 메타데이터 확인
$post_id = get_the_ID();
echo '<!-- DEBUG: Post ID: ' . $post_id . ' -->';
$all_meta = get_post_meta($post_id);
echo '<!-- DEBUG: All Meta: ' . print_r($all_meta, true) . ' -->';
?>

<style>
/* Industrial Heritage 디자인 시스템 */
:root {
    --warm-white: #FAFAF8;
    --charcoal: #2C2C2C;
    --brick-red: #B85450;
    --industrial-green: #4A6741;
    --concrete-grey: #E8E6E1;
    --cafe-latte: #D4A574;
    --steel-blue: #5B7C99;
    --muted-text: #666666;
}

/* 기본 배경색 설정 */
body.single-places {
    background: var(--warm-white) !important;
    color: var(--charcoal) !important;
}

/* 장소 상세페이지에서 헤더 스타일 보정 */
body.single-places .site-header {
    background: rgba(255, 255, 255, 0.95) !important;
    border-bottom: 1px solid var(--concrete-grey) !important;
}

body.single-places .site-header .nav-link {
    color: var(--charcoal) !important;
}

body.single-places .site-header .nav-link span {
    color: var(--charcoal) !important;
}

body.single-places .site-header .nav-link:hover {
    color: var(--brick-red) !important;
}

body.single-places .site-header .nav-link.special {
    background: var(--brick-red) !important;
    color: white !important;
}

body.single-places .site-header .nav-link.special span {
    color: white !important;
}

/* GTranslate 버튼 스타일 - 장소 상세페이지 */
body.single-places .site-header .gtranslate_wrapper select,
body.single-places .site-header .gtranslate_wrapper .gt_switcher,
body.single-places .site-header .gtranslate_wrapper a {
    background: rgba(44, 44, 44, 0.1) !important;
    border: 2px solid var(--charcoal) !important;
    color: var(--charcoal) !important;
}

body.single-places .site-header .gtranslate_wrapper select:hover,
body.single-places .site-header .gtranslate_wrapper .gt_switcher:hover,
body.single-places .site-header .gtranslate_wrapper a:hover {
    background: var(--charcoal) !important;
    color: white !important;
}

/* 로그인 버튼 스타일 - 장소 상세페이지 */
body.single-places .site-header .nav-link.login-btn {
    background: var(--charcoal) !important;
    border: 2px solid var(--charcoal) !important;
    color: white !important;
}

body.single-places .site-header .nav-link.login-btn span {
    color: white !important;
}

body.single-places .site-header .nav-link.login-btn:hover {
    background: transparent !important;
    border-color: var(--charcoal) !important;
    color: var(--charcoal) !important;
}

body.single-places .site-header .nav-link.login-btn:hover span {
    color: var(--charcoal) !important;
}

/* 컨테이너 */
.container { 
    max-width: 1200px; 
    margin: 0 auto; 
    padding: 0 20px; 
}

/* 히어로 섹션 - 벽돌색 그라데이션 */
.hero-section { 
    background: linear-gradient(135deg, var(--brick-red) 0%, var(--cafe-latte) 100%); 
    color: white; 
    padding: 80px 0 60px;
    margin-bottom: 40px;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

.hero-content {
    position: relative;
    z-index: 1;
}

/* 정보 섹션 */
.info-section { 
    background: var(--warm-white); 
    padding: 60px 0; 
}

/* 카드 스타일 */
.card { 
    background: white; 
    border-radius: 16px; 
    box-shadow: 0 2px 12px rgba(0,0,0,0.08); 
    padding: 32px; 
    margin-bottom: 24px;
    border: 1px solid var(--concrete-grey);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

/* 그리드 시스템 */
.grid { 
    display: grid; 
    gap: 24px; 
}
.grid-2 { 
    grid-template-columns: 1fr 1fr; 
}
.grid-3 { 
    grid-template-columns: repeat(3, 1fr); 
}

/* 제목 스타일 */
h1 {
    font-family: 'Pretendard', sans-serif;
    font-weight: 800;
    font-size: 48px;
    line-height: 1.2;
    margin-bottom: 16px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

h2 {
    font-family: 'Pretendard', sans-serif;
    font-weight: 700;
    font-size: 28px;
    color: var(--charcoal);
    margin-bottom: 20px;
}

h3 {
    font-family: 'Pretendard', sans-serif;
    font-weight: 600;
    font-size: 20px;
    color: var(--charcoal);
    margin-bottom: 16px;
}

/* 버튼 스타일 - 벽돌색 */
.btn { 
    background: var(--brick-red); 
    color: white; 
    padding: 14px 28px; 
    border-radius: 28px; 
    text-decoration: none; 
    display: inline-block; 
    transition: all 0.3s;
    border: none;
    cursor: pointer;
    font-weight: 600;
    text-align: center;
    font-size: 16px;
}

.btn:hover { 
    background: #A04A46; 
    transform: translateY(-2px); 
    box-shadow: 0 4px 12px rgba(184, 84, 80, 0.3);
}

.btn:active {
    transform: translateY(0);
}

/* 정적지도 스타일 */
.map-container { 
    width: 100%; 
    height: 400px; 
    border-radius: 16px; 
    overflow: hidden; 
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    background: var(--concrete-grey);
    position: relative;
    margin-bottom: 24px;
}

.static-map-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* 썸네일 이미지 */
.thumbnail { 
    width: 100%; 
    height: 300px; 
    object-fit: cover; 
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.placeholder { 
    background: linear-gradient(135deg, var(--brick-red) 0%, var(--cafe-latte) 100%); 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: white; 
    font-size: 48px;
    height: 300px;
    border-radius: 16px;
}

/* 주소 표시 */
.address-display { 
    background: white; 
    padding: 16px 20px; 
    border-radius: 12px; 
    text-align: center; 
    font-weight: 600; 
    color: var(--charcoal); 
    margin-top: 16px;
    border: 1px solid var(--concrete-grey);
    font-size: 16px;
}

/* 정보 아이템 */
.info-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item .label {
    flex: 0 0 40%;
    color: var(--muted-text);
    font-weight: 500;
}

.info-item .value {
    flex: 1;
    color: var(--charcoal);
    font-weight: 600;
    text-align: right;
}

/* 플랫폼 카드 */
.platform-card {
    background: white;
    border: 2px solid var(--concrete-grey);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    text-decoration: none;
    display: block;
    color: var(--charcoal);
}

.platform-card:hover {
    border-color: var(--brick-red);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

/* 섹션 제목 */
.section-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--charcoal);
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--concrete-grey);
}

/* 팝업스토어 그룹 카드 */
.popup-group-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    padding: 0;
    margin-bottom: 24px;
    border: 1px solid var(--concrete-grey);
    overflow: hidden;
    transition: all 0.3s ease;
}

.popup-group-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.popup-group-header {
    background: linear-gradient(135deg, var(--brick-red) 0%, var(--cafe-latte) 100%);
    color: white;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.popup-group-header .icon {
    font-size: 28px;
}

.popup-group-header h4 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.popup-group-content {
    padding: 24px;
}

/* 체크박스 다중 선택 표시 */
.checkbox-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.checkbox-item {
    background: var(--concrete-grey);
    color: var(--charcoal);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
}

/* 모바일 반응형 */
@media (max-width: 768px) { 
    .grid-2, .grid-3 { 
        grid-template-columns: 1fr; 
    }
    
    .hero-section { 
        padding: 60px 0 40px; 
    }
    
    h1 {
        font-size: 32px;
    }
    
    h2 {
        font-size: 24px;
    }
    
    .card {
        padding: 24px;
    }
    
    .map-container {
        height: 300px;
    }
    
    .btn { 
        padding: 16px 32px !important; 
        font-size: 16px !important;
        min-height: 48px;
        width: 100%;
    }
    
    .info-item .label {
        flex: 0 0 50%;
    }
}
</style>

<?php while (have_posts()) : the_post(); 
    $post_id = get_the_ID();
    $place_types = wp_get_post_terms($post_id, 'place_type');
    
    // 장소유형 처리
    $place_type = 'restaurant'; // 기본값
    $place_type_name = '맛집'; // 기본 이름
    
    if (!empty($place_types) && !is_wp_error($place_types)) {
        $term = $place_types[0];
        $place_type_name = $term->name;
        $place_type = $term->slug;
        
        // slug 정규화 (언더스코어를 하이픈으로 변환)
        $place_type = str_replace('_', '-', $place_type);
    }
    
    // PlaceMetaFields 기반 모든 필드 가져오기
    $all_fields = PlaceMetaFields::get_fields_for_type($place_type);
    
    // 값이 있는 필드만 필터링
    $filled_fields = array();
    foreach ($all_fields as $field_key => $field_config) {
        $value = get_post_meta($post_id, $field_key, true);
        
        // 빈 값 체크 (배열, 문자열 등 타입별로)
        if (!empty($value)) {
            $filled_fields[$field_key] = array(
                'config' => $field_config,
                'value' => $value
            );
        }
    }
    
    // 기본 필드들 개별 추출 (히어로 섹션용)
    $latitude = get_post_meta($post_id, 'latitude', true);
    $longitude = get_post_meta($post_id, 'longitude', true);
    $address = get_post_meta($post_id, 'address', true);
    $phone = get_post_meta($post_id, 'phone', true);
    $opening_hours = get_post_meta($post_id, 'opening_hours', true);
    
    // 정적지도 시스템
    $static_map_image_id = get_post_meta($post_id, 'static_map_image_id', true);
    $static_map_url = '';
    $has_static_map = false;
    
    if ($static_map_image_id && wp_attachment_is_image($static_map_image_id)) {
        $static_map_url = wp_get_attachment_url($static_map_image_id);
        $has_static_map = true;
    } else {
        // 정적지도가 없으면 자동 생성 시도
        if (!empty($latitude) && !empty($longitude) && function_exists('generate_static_map_for_place')) {
            generate_static_map_for_place($post_id);
            
            // 생성 후 다시 확인
            $static_map_image_id = get_post_meta($post_id, 'static_map_image_id', true);
            if ($static_map_image_id && wp_attachment_is_image($static_map_image_id)) {
                $static_map_url = wp_get_attachment_url($static_map_image_id);
                $has_static_map = true;
            }
        }
    }
    
    // 장소 유형별 아이콘과 색상
    $type_configs = array(
        'restaurant' => array('icon' => '🍽️', 'label' => '맛집', 'color' => '#ff6b6b'),
        'cafe' => array('icon' => '☕', 'label' => '카페', 'color' => '#4ecdc4'),
        'popup-store' => array('icon' => '🎪', 'label' => '팝업스토어', 'color' => '#45b7d1'),
        'popup_store' => array('icon' => '🎪', 'label' => '팝업스토어', 'color' => '#45b7d1'),
        'retail-store' => array('icon' => '🏬', 'label' => '상설매장', 'color' => '#96ceb4'),
        'retail_store' => array('icon' => '🏬', 'label' => '상설매장', 'color' => '#96ceb4'),
        'facility' => array('icon' => '🏢', 'label' => '편의시설', 'color' => '#ffeaa7')
    );
    
    $current_type = isset($type_configs[$place_type]) ? $type_configs[$place_type] : $type_configs['restaurant'];
?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="grid grid-2" style="align-items: center;">
            <div>
                <div style="display: inline-flex; align-items: center; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; margin-bottom: 20px;">
                    <span style="font-size: 20px; margin-right: 8px;"><?php echo $current_type['icon']; ?></span>
                    <span style="font-weight: 600;"><?php echo esc_html($place_type_name); ?></span>
                </div>
                
                <h1 style="font-size: 48px; font-weight: bold; margin-bottom: 20px; line-height: 1.2;">
                    <?php the_title(); ?>
                </h1>
                
                <?php if (!empty($address)): ?>
                <div style="display: flex; align-items: center; margin-bottom: 30px; font-size: 18px; opacity: 0.9;">
                    <span style="margin-right: 10px;">📍</span>
                    <?php echo esc_html($address); ?>
                </div>
                <?php endif; ?>
                
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button onclick="addToTour(<?php echo $post_id; ?>)" class="btn">
                        🗺️ 투어 추가
                    </button>
                    <?php if (!empty($latitude) && !empty($longitude)): ?>
                    <a href="https://map.naver.com/v5/directions/-/-/<?php echo $latitude; ?>,<?php echo $longitude; ?>" target="_blank" class="btn">
                        📍 길찾기
                    </a>
                    <?php endif; ?>
                    <button onclick="sharePlace()" class="btn">📤 공유</button>
                </div>
                
                <!-- 공유하기 모달 컨테이너 -->
                <div id="share-modal-container"></div>
            </div>
            
            <div>
                <?php if (has_post_thumbnail()): ?>
                    <?php the_post_thumbnail('large', array('class' => 'thumbnail')); ?>
                <?php else: ?>
                    <div class="thumbnail placeholder">
                        <span><?php echo $current_type['icon']; ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="info-section">
    <div class="container">
        <?php if ($place_type === 'popup-store' || $place_type === 'popup_store'): ?>
            <!-- 팝업스토어 전용 레이아웃 -->
            <?php include(get_template_directory() . '/template-parts/popup-store-detail.php'); ?>
        <?php else: ?>
            <!-- 일반 장소 레이아웃 -->
            <div class="grid grid-2">
                <!-- Left: Content -->
                <div>
                    <!-- Description -->
                    <div class="card">
                        <h2 style="font-size: 28px; font-weight: bold; margin-bottom: 20px; display: flex; align-items: center;">
                            <span style="font-size: 32px; margin-right: 12px;">📝</span>
                            소개
                        </h2>
                        <?php if (get_the_content()): ?>
                            <div style="font-size: 16px; line-height: 1.6; color: #666;">
                                <?php the_content(); ?>
                            </div>
                        <?php else: ?>
                            <p style="font-size: 18px; line-height: 1.6; color: #666;">
                                성수동에 위치한 <?php echo esc_html($place_type_name); ?>입니다. 
                                독특한 경험과 특별한 추억을 만들어보세요.
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Dynamic Fields Display -->
                    <?php if (!empty($filled_fields)): ?>
                        <?php
                        // 공통 필드와 특화 필드 분리
                        $common_fields = PlaceMetaFields::get_common_fields();
                        $specific_fields = array();
                        
                        foreach ($filled_fields as $key => $field_data) {
                            if (!isset($common_fields[$key])) {
                                $specific_fields[$key] = $field_data;
                            }
                        }
                        
                        // 특화 필드가 있으면 표시
                        if (!empty($specific_fields)):
                        ?>
                        <div class="card">
                            <h3 style="font-size: 24px; font-weight: bold; margin-bottom: 20px; display: flex; align-items: center;">
                                <span style="font-size: 28px; margin-right: 12px;">🎯</span>
                                상세 정보
                            </h3>
                            
                            <div class="info-items">
                                <?php foreach ($specific_fields as $key => $field_data): 
                                    $config = $field_data['config'];
                                    $value = $field_data['value'];
                                    
                                    // 타입별 포맷팅
                                    $formatted_value = sungsuya_get_formatted_meta_value($post_id, $key, $config);
                                ?>
                                <div class="info-item">
                                    <div class="label"><?php echo esc_html($config['label']); ?></div>
                                    <div class="value"><?php echo $formatted_value; ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Right: Info & Map -->
                <div>
                    <!-- Basic Info -->
                    <div class="card">
                        <h3 style="font-size: 24px; font-weight: bold; margin-bottom: 20px; display: flex; align-items: center;">
                            <span style="font-size: 28px; margin-right: 12px;">ℹ️</span>
                            기본 정보
                        </h3>
                        
                        <div class="info-items">
                            <?php if (!empty($opening_hours)): ?>
                            <div class="info-item">
                                <div class="label">🕒 운영시간</div>
                                <div class="value"><?php echo nl2br(esc_html($opening_hours)); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($phone)): ?>
                            <div class="info-item">
                                <div class="label">📞 전화번호</div>
                                <div class="value">
                                    <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $phone); ?>" style="color: #667eea; text-decoration: none;">
                                        <?php echo esc_html($phone); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($address)): ?>
                            <div class="info-item">
                                <div class="label">📍 주소</div>
                                <div class="value"><?php echo esc_html($address); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php
                            // 추가 공통 필드 표시
                            $basic_display_fields = array('website', 'instagram', 'nearest_subway', 'subway_distance');
                            foreach ($basic_display_fields as $field_key) {
                                if (isset($filled_fields[$field_key])) {
                                    $field_data = $filled_fields[$field_key];
                                    $config = $field_data['config'];
                                    $value = $field_data['value'];
                                    $formatted_value = sungsuya_get_formatted_meta_value($post_id, $field_key, $config);
                                    
                                    // 아이콘 매핑
                                    $icons = array(
                                        'website' => '🌐',
                                        'instagram' => '📷',
                                        'nearest_subway' => '🚇',
                                        'subway_distance' => '🚶'
                                    );
                                    
                                    $icon = isset($icons[$field_key]) ? $icons[$field_key] : '📌';
                            ?>
                            <div class="info-item">
                                <div class="label"><?php echo $icon . ' ' . esc_html($config['label']); ?></div>
                                <div class="value"><?php echo $formatted_value; ?></div>
                            </div>
                            <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Map -->
                    <div class="card">
                        <h3 style="font-size: 24px; font-weight: bold; margin-bottom: 20px; display: flex; align-items: center;">
                            <span style="font-size: 28px; margin-right: 12px;">🗺️</span>
                            위치
                        </h3>
                        
                        <?php if ($has_static_map && !empty($static_map_url)): ?>
                            <div class="map-container">
                                <img src="<?php echo esc_url($static_map_url); ?>" 
                                     alt="<?php echo esc_attr(get_the_title()); ?> 위치" 
                                     class="static-map-image" />
                            </div>
                        <?php elseif (!empty($latitude) && !empty($longitude)): ?>
                            <div class="map-container" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); display: flex; align-items: center; justify-content: center;">
                                <div style="text-align: center; color: #495057; max-width: 300px;">
                                    <div style="font-size: 48px; margin-bottom: 15px; opacity: 0.8;">📍</div>
                                    <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 8px; color: #343a40;">지도를 생성하는 중입니다</h3>
                                    <p style="font-size: 14px; opacity: 0.8; margin-bottom: 20px; line-height: 1.4;">잠시 후 다시 확인해주세요</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="map-container" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); display: flex; align-items: center; justify-content: center;">
                                <div style="text-align: center; color: #6c757d;">
                                    <div style="font-size: 48px; margin-bottom: 15px; opacity: 0.6;">🗺️</div>
                                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 5px;">위치 정보 준비 중</h3>
                                    <p style="font-size: 14px; opacity: 0.8;">곧 정확한 위치를 제공할 예정입니다</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div style="text-align: center; margin-top: 15px;">
                            <button onclick="openNaverMap()" class="btn" style="background: #03C75A; font-size: 16px; font-weight: 600;">
                                🗺️ 네이버 지도에서 보기
                            </button>
                        </div>
                        
                        <?php if (!empty($address)): ?>
                        <div class="address-display">
                            📍 <?php echo esc_html($address); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- External Links -->
<?php
// Smart Deep Link 데이터 가져오기
$smartLinkData = get_post_meta($post_id, 'smart_deep_links', true);
if (empty($smartLinkData)) {
    $smartLinkData = get_post_meta($post_id, 'smart_deep_link_v7', true);
}
if (empty($smartLinkData)) {
    $smartLinkData = get_post_meta($post_id, 'external_links', true);
}

// 플랫폼 아이콘 헬퍼 함수
function getPlatformIcon($platform) {
    $icons = array(
        'naver' => '🗺️',
        'naver_map' => '🗺️',
        'kakao' => '💬',
        'kakao_map' => '🗺️',
        'instagram' => '📷',
        'blueribbon' => '🎖️',
        'blue_ribbon' => '🎖️',
        'diningcode' => '🍽️',
        'dining_code' => '🍽️',
        'facebook' => '👥',
        'youtube' => '📺',
        'website' => '🌐'
    );
    
    return isset($icons[strtolower($platform)]) ? $icons[strtolower($platform)] : '🔗';
}

// 플랫폼 이름 헬퍼 함수
function getPlatformName($platform) {
    $names = array(
        'naver' => '네이버 지도',
        'kakao' => '카카오맵',
        'instagram' => '인스타그램',
        'blueribbon' => '블루리본',
        'diningcode' => '다이닝코드'
    );
    
    return isset($names[strtolower($platform)]) ? $names[strtolower($platform)] : ucfirst(str_replace('_', ' ', $platform));
}

if (!empty($smartLinkData) && is_array($smartLinkData)):
?>
<div style="background: #f8f9fa; padding: 40px 0;">
    <div class="container">
        <h2 style="text-align: center; font-size: 32px; font-weight: bold; margin-bottom: 40px;">
            다른 플랫폼에서 더 보기
        </h2>
        
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <?php foreach ($smartLinkData as $platform => $data): 
                // 데이터 구조 확인 및 정규화
                if (is_string($data)) {
                    $url = $data;
                    $name = getPlatformName($platform);
                    $icon = getPlatformIcon($platform);
                } elseif (is_array($data)) {
                    $url = isset($data['url']) ? $data['url'] : (isset($data['link']) ? $data['link'] : '');
                    $name = isset($data['name']) ? $data['name'] : getPlatformName($platform);
                    $icon = isset($data['icon']) ? $data['icon'] : getPlatformIcon($platform);
                } else {
                    continue;
                }
                
                if (empty($url)) continue;
            ?>
            <div class="platform-card" onclick="window.open('<?php echo esc_url($url); ?>', '_blank')" 
                 style="background: white; border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; box-shadow: 0 4px 20px rgba(0,0,0,0.1); transition: transform 0.3s;">
                <div style="font-size: 40px; margin-bottom: 15px;"><?php echo $icon; ?></div>
                <h3 style="font-size: 18px; font-weight: bold; margin-bottom: 10px;"><?php echo esc_html($name); ?></h3>
                <div style="background: #667eea; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px;">
                    바로가기 →
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.platform-card:hover {
    transform: translateY(-5px) !important;
}
</style>
<?php endif; ?>

<!-- 리뷰 시스템 컨테이너 -->
<div style="background: #f8f9fa; padding: 40px 0;">
    <div class="container">
        <div id="place-review-system" data-place-id="<?php echo $post_id; ?>"></div>
    </div>
</div>

<!-- JavaScript -->
<script>
// 기본 기능들
function addToTour(placeId) {
    const tourData = JSON.parse(localStorage.getItem('sungsuya_tour') || '[]');
    const placeData = {
        id: placeId,
        title: '<?php echo esc_js(get_the_title()); ?>',
        type: '<?php echo esc_js($place_type); ?>'
    };
    
    if (!tourData.find(place => place.id === placeId)) {
        tourData.push(placeData);
        localStorage.setItem('sungsuya_tour', JSON.stringify(tourData));
        showNotification('🗺️ 투어에 추가되었습니다!');
    } else {
        showNotification('이미 투어에 추가된 장소입니다.');
    }
}

// ShareButtons 컴포넌트를 모달로 표시
function sharePlace() {
    const placeData = {
        id: <?php echo $post_id; ?>,
        title: '<?php echo esc_js(get_the_title()); ?>',
        url: '<?php echo esc_js(get_permalink()); ?>',
        address: '<?php echo esc_js($address); ?>',
        image: '<?php echo esc_js(get_the_post_thumbnail_url($post_id, 'large')); ?>'
    };
    
    // 모달 HTML 생성
    const modalHTML = `
        <div class="share-modal-overlay" style="
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease-out;
        " onclick="if (event.target === this) closeShareModal()">
            <div class="share-modal-content" style="
                background: white;
                border-radius: 1rem;
                padding: 0;
                max-width: 500px;
                width: 90%;
                max-height: 80vh;
                overflow: hidden;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                animation: modalSlideUp 0.3s ease-out;
            ">
                <div class="share-modal-header" style="
                    padding: 1.5rem;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                ">
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600;">
                        📤 이 장소를 공유하세요
                    </h3>
                    <button onclick="closeShareModal()" style="
                        background: none;
                        border: none;
                        color: white;
                        font-size: 1.5rem;
                        cursor: pointer;
                        padding: 0;
                        width: 30px;
                        height: 30px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 50%;
                        transition: background 0.2s;
                    " onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='none'">
                        ✕
                    </button>
                </div>
                <div id="share-buttons-root" style="padding: 1.5rem;">
                    <!-- ShareButtons 컴포넌트가 렌더링될 위치 -->
                </div>
            </div>
        </div>
        
        <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes modalSlideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        </style>
    `;
    
    // 모달을 DOM에 추가
    const container = document.getElementById('share-modal-container');
    container.innerHTML = modalHTML;
    
    // React 컴포넌트 렌더링
    if (window.React && window.ReactDOM && window.ShareButtons) {
        ReactDOM.render(
            React.createElement(ShareButtons, { placeData: placeData }),
            document.getElementById('share-buttons-root')
        );
    } else {
        // React가 로드되지 않은 경우 폴백
        document.getElementById('share-buttons-root').innerHTML = `
            <div style="text-align: center; color: #666;">
                <p>공유 기능을 불러오는 중...</p>
                <button onclick="navigator.clipboard.writeText('${placeData.url}').then(() => { showNotification('링크가 복사되었습니다!'); closeShareModal(); })" 
                        style="margin-top: 1rem; padding: 0.75rem 1.5rem; background: #3b82f6; color: white; border: none; border-radius: 0.5rem; cursor: pointer;">
                    링크 복사하기
                </button>
            </div>
        `;
    }
}

// 모달 닫기 함수
function closeShareModal() {
    const modal = document.querySelector('.share-modal-overlay');
    if (modal) {
        modal.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            document.getElementById('share-modal-container').innerHTML = '';
        }, 300);
    }
}

// 네이버 지도에서 보기
function openNaverMap() {
    const placeName = '<?php echo esc_js(get_the_title()); ?>';
    <?php if (!empty($latitude) && !empty($longitude)): ?>
    const lat = <?php echo $latitude; ?>;
    const lng = <?php echo $longitude; ?>;
    <?php endif; ?>
    
    let naverMapUrl;
    
    <?php if (!empty($latitude) && !empty($longitude)): ?>
    const searchQuery = encodeURIComponent(placeName);
    naverMapUrl = `https://map.naver.com/v5/search/${searchQuery}`;
    showNotification(`🔍 ${placeName}을(를) 네이버 지도에서 검색합니다.`);
    <?php else: ?>
    const searchQuery = encodeURIComponent(placeName + ' 성수동');
    naverMapUrl = `https://map.naver.com/v5/search/${searchQuery}`;
    showNotification(`🔍 ${placeName}을(를) 성수동 지역에서 검색합니다.`);
    <?php endif; ?>
    
    window.open(naverMapUrl, '_blank');
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 1000;
        background: #667eea; color: white; padding: 15px 20px;
        border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        transform: translateX(100%); transition: transform 0.3s;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    setTimeout(() => notification.style.transform = 'translateX(0)', 100);
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => document.body.removeChild(notification), 300);
    }, 3000);
}
</script>

<?php endwhile; ?>

<?php get_footer(); ?>
