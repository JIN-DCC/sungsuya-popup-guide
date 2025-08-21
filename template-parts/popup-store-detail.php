<?php
/**
 * 팝업스토어 상세정보 표시 템플릿
 * 
 * 41개 필드를 4개 그룹으로 나누어 표시
 * - 기본 정보 (8개)
 * - 운영 정보 (8개)
 * - 소셜 & 연락처 (10개)
 * - 투어 최적화 (15개)
 * 
 * @package SungsuyaV2
 */

// 팝업스토어 그룹 정의
$popup_groups = PlaceMetaFields::get_popup_store_field_groups();

// 그룹별로 필드 표시
$has_any_group = false;

// 먼저 표시할 그룹이 있는지 확인
foreach ($popup_groups as $group_key => $group_config) {
    $group_fields = PlaceMetaFields::get_fields_by_group('popup-store', $group_key);
    
    foreach ($group_fields as $field_key => $field_config) {
        $value = get_post_meta($post_id, $field_key, true);
        if (!empty($value)) {
            $has_any_group = true;
            break 2;
        }
    }
}
?>

<div class="popup-store-layout">
    <!-- 상단: 소개 카드 -->
    <div class="card" style="margin-bottom: 40px;">
        <h2 style="font-size: 28px; font-weight: bold; margin-bottom: 20px; display: flex; align-items: center;">
            <span style="font-size: 32px; margin-right: 12px;">🎪</span>
            팝업스토어 소개
        </h2>
        <?php if (get_the_content()): ?>
            <div style="font-size: 16px; line-height: 1.6; color: #666;">
                <?php the_content(); ?>
            </div>
        <?php else: ?>
            <p style="font-size: 18px; line-height: 1.6; color: #666;">
                성수동에서 만나는 특별한 팝업스토어입니다. 
                한정된 기간 동안만 경험할 수 있는 특별한 공간과 제품을 만나보세요.
            </p>
        <?php endif; ?>
    </div>

    <!-- 그룹별 카드 표시 -->
    <?php if ($has_any_group): ?>
        <div class="popup-groups-container">
            <?php foreach ($popup_groups as $group_key => $group_config): 
                $group_fields = PlaceMetaFields::get_fields_by_group('popup-store', $group_key);
                $has_group_data = false;
                $group_values = array();
                
                // 그룹에 데이터가 있는지 확인
                foreach ($group_fields as $field_key => $field_config) {
                    $value = get_post_meta($post_id, $field_key, true);
                    if (!empty($value)) {
                        $has_group_data = true;
                        $group_values[$field_key] = array(
                            'config' => $field_config,
                            'value' => $value
                        );
                    }
                }
                
                // 데이터가 있는 그룹만 표시
                if ($has_group_data):
            ?>
            <div class="popup-group-card">
                <div class="popup-group-header">
                    <span class="icon"><?php echo $group_config['icon']; ?></span>
                    <h4><?php echo esc_html($group_config['title']); ?></h4>
                </div>
                <div class="popup-group-content">
                    <?php if (!empty($group_config['description'])): ?>
                    <p style="color: #666; margin-bottom: 20px; font-size: 14px;">
                        <?php echo esc_html($group_config['description']); ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="info-items">
                        <?php foreach ($group_values as $field_key => $field_data): 
                            $config = $field_data['config'];
                            $value = $field_data['value'];
                            
                            // 특별한 필드 타입 처리
                            if ($config['type'] === 'checkbox_multiple' && is_array($value)) {
                                ?>
                                <div class="info-item" style="flex-direction: column; align-items: flex-start;">
                                    <div class="label" style="margin-bottom: 8px;"><?php echo esc_html($config['label']); ?></div>
                                    <div class="checkbox-list">
                                        <?php foreach ($value as $option_key): 
                                            if (isset($config['options'][$option_key])): ?>
                                        <span class="checkbox-item"><?php echo esc_html($config['options'][$option_key]); ?></span>
                                        <?php endif; 
                                        endforeach; ?>
                                    </div>
                                </div>
                                <?php
                            } elseif ($config['type'] === 'textarea') {
                                ?>
                                <div class="info-item" style="flex-direction: column; align-items: flex-start;">
                                    <div class="label" style="margin-bottom: 8px;"><?php echo esc_html($config['label']); ?></div>
                                    <div class="value" style="text-align: left; font-weight: 400; line-height: 1.6;">
                                        <?php echo nl2br(esc_html($value)); ?>
                                    </div>
                                </div>
                                <?php
                            } else {
                                // 일반 필드
                                $formatted_value = sungsuya_get_formatted_meta_value($post_id, $field_key, $config);
                                ?>
                                <div class="info-item">
                                    <div class="label"><?php echo esc_html($config['label']); ?></div>
                                    <div class="value"><?php echo $formatted_value; ?></div>
                                </div>
                                <?php
                            }
                        endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; 
            endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- 하단: 지도와 기본 정보 -->
    <div class="grid grid-2" style="margin-top: 40px;">
        <!-- 기본 정보 카드 -->
        <div class="card">
            <h3 style="font-size: 24px; font-weight: bold; margin-bottom: 20px; display: flex; align-items: center;">
                <span style="font-size: 28px; margin-right: 12px;">ℹ️</span>
                기본 정보
            </h3>
            
            <div class="info-items">
                <?php if (!empty($address)): ?>
                <div class="info-item">
                    <div class="label">📍 주소</div>
                    <div class="value"><?php echo esc_html($address); ?></div>
                </div>
                <?php endif; ?>
                
                <?php 
                // 기본 공통 필드 표시
                $basic_fields = array('phone', 'website', 'instagram', 'nearest_subway', 'subway_distance');
                foreach ($basic_fields as $field_key) {
                    $value = get_post_meta($post_id, $field_key, true);
                    if (!empty($value) && isset($all_fields[$field_key])) {
                        $config = $all_fields[$field_key];
                        $formatted_value = sungsuya_get_formatted_meta_value($post_id, $field_key, $config);
                        
                        // 아이콘 매핑
                        $icons = array(
                            'phone' => '📞',
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

        <!-- 지도 카드 -->
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

<style>
/* 팝업스토어 전용 스타일 */
.popup-store-layout {
    max-width: 1200px;
    margin: 0 auto;
}

.popup-groups-container {
    display: grid;
    gap: 24px;
    margin-bottom: 40px;
}

@media (max-width: 768px) {
    .popup-group-header {
        padding: 16px 20px !important;
    }
    
    .popup-group-header .icon {
        font-size: 24px !important;
    }
    
    .popup-group-header h4 {
        font-size: 18px !important;
    }
    
    .popup-group-content {
        padding: 20px !important;
    }
}
</style>
