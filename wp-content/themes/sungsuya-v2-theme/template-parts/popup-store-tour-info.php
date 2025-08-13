<?php
/**
 * 팝업스토어 투어 정보 템플릿 파트
 * 
 * @package SungsuyaV2
 */

$post_id = get_the_ID();

// 투어 관련 메타 데이터
$recommended_visit_duration = get_post_meta($post_id, 'recommended_visit_duration', true);
$best_visit_time = get_post_meta($post_id, 'best_visit_time', true);
$crowd_level_weekday = get_post_meta($post_id, 'crowd_level_weekday', true);
$crowd_level_weekend = get_post_meta($post_id, 'crowd_level_weekend', true);
$nearby_recommendations = get_post_meta($post_id, 'nearby_recommendations', true);
$combination_tips = get_post_meta($post_id, 'combination_tips', true);
$accessibility_features = get_post_meta($post_id, 'accessibility_features', false);
$special_options = get_post_meta($post_id, 'special_options', false);

// 운영 정보
$reservation_required = get_post_meta($post_id, 'reservation_required', true);
$special_notes = get_post_meta($post_id, 'special_notes', true);

// 투어 호환성 점수
$tour_compatibility_score = get_post_meta($post_id, 'tour_compatibility_score', true);

// 투어 정보가 하나라도 있으면 섹션 표시
$has_tour_info = $recommended_visit_duration || $best_visit_time || $crowd_level_weekday || 
                 $crowd_level_weekend || $nearby_recommendations || $combination_tips || 
                 !empty($accessibility_features) || $special_notes || !empty($special_options);

if (!$has_tour_info) {
    return;
}
?>

<section class="store-tour-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-icon">🗺️</span>
                투어 정보
            </h2>
            <?php if ($tour_compatibility_score) : ?>
            <div class="tour-score-badge">
                <span class="score-icon">🎯</span>
                <span class="score-text">투어 적합도 <?php echo esc_html($tour_compatibility_score); ?>%</span>
            </div>
            <?php endif; ?>
        </div>

        <div class="tour-content">

            <!-- 방문 계획 정보 -->
            <?php if ($recommended_visit_duration || $best_visit_time || $reservation_required) : ?>
            <div class="visit-planning-group">
                <h3 class="subsection-title">
                    <span class="subsection-icon">⏱️</span>
                    방문 계획
                </h3>
                <div class="visit-info-grid">
                    
                    <?php if ($recommended_visit_duration) : ?>
                    <div class="visit-info-item duration-item">
                        <div class="info-icon">⏰</div>
                        <div class="info-content">
                            <div class="info-label">권장 체류시간</div>
                            <div class="info-value">
                                <?php
                                $duration_labels = array(
                                    '15-30' => '15-30분',
                                    '30-60' => '30분-1시간',
                                    '60-90' => '1-1.5시간',
                                    '90-120' => '1.5-2시간',
                                    '120+' => '2시간 이상'
                                );
                                echo esc_html($duration_labels[$recommended_visit_duration] ?? $recommended_visit_duration);
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($best_visit_time) : ?>
                    <div class="visit-info-item time-item">
                        <div class="info-icon">
                            <?php
                            $time_icons = array(
                                'morning' => '🌅',
                                'lunch' => '🍽️',
                                'afternoon' => '☀️',
                                'evening' => '🌆',
                                'night' => '🌙',
                                'anytime' => '🕐'
                            );
                            echo $time_icons[$best_visit_time] ?? '🕐';
                            ?>
                        </div>
                        <div class="info-content">
                            <div class="info-label">권장 방문시간</div>
                            <div class="info-value">
                                <?php
                                $time_labels = array(
                                    'morning' => '오전 (10:00-12:00)',
                                    'lunch' => '점심 (12:00-14:00)',
                                    'afternoon' => '오후 (14:00-17:00)',
                                    'evening' => '저녁 (17:00-20:00)',
                                    'night' => '밤 (20:00 이후)',
                                    'anytime' => '언제든지'
                                );
                                echo esc_html($time_labels[$best_visit_time] ?? $best_visit_time);
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($reservation_required) : ?>
                    <div class="visit-info-item reservation-item">
                        <div class="info-icon">
                            <?php
                            $reservation_icons = array(
                                'no' => '🚫',
                                'recommended' => '⭐',
                                'required' => '✅'
                            );
                            echo $reservation_icons[$reservation_required] ?? '📝';
                            ?>
                        </div>
                        <div class="info-content">
                            <div class="info-label">예약 정보</div>
                            <div class="info-value">
                                <?php
                                $reservation_labels = array(
                                    'no' => '예약 불필요',
                                    'recommended' => '예약 권장',
                                    'required' => '예약 필수'
                                );
                                echo esc_html($reservation_labels[$reservation_required] ?? $reservation_required);
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
            <?php endif; ?>

            <!-- 혼잡도 정보 -->
            <?php if ($crowd_level_weekday || $crowd_level_weekend) : ?>
            <div class="crowd-info-group">
                <h3 class="subsection-title">
                    <span class="subsection-icon">👥</span>
                    혼잡도 정보
                </h3>
                <div class="crowd-info-grid">
                    
                    <?php if ($crowd_level_weekday) : ?>
                    <div class="crowd-info-item weekday-item">
                        <div class="crowd-day">평일</div>
                        <div class="crowd-level <?php echo esc_attr($crowd_level_weekday); ?>">
                            <?php
                            $crowd_data = array(
                                'low' => array('icon' => '🟢', 'text' => '여유로움', 'desc' => '대기 없음'),
                                'medium' => array('icon' => '🟡', 'text' => '보통', 'desc' => '짧은 대기'),
                                'high' => array('icon' => '🟠', 'text' => '혼잡함', 'desc' => '대기 예상'),
                                'very_high' => array('icon' => '🔴', 'text' => '매우 혼잡', 'desc' => '긴 대기')
                            );
                            $weekday_data = $crowd_data[$crowd_level_weekday] ?? array('icon' => '⚪', 'text' => '정보 없음', 'desc' => '');
                            ?>
                            <span class="crowd-icon"><?php echo $weekday_data['icon']; ?></span>
                            <span class="crowd-text"><?php echo esc_html($weekday_data['text']); ?></span>
                            <span class="crowd-desc"><?php echo esc_html($weekday_data['desc']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($crowd_level_weekend) : ?>
                    <div class="crowd-info-item weekend-item">
                        <div class="crowd-day">주말</div>
                        <div class="crowd-level <?php echo esc_attr($crowd_level_weekend); ?>">
                            <?php
                            $weekend_data = $crowd_data[$crowd_level_weekend] ?? array('icon' => '⚪', 'text' => '정보 없음', 'desc' => '');
                            ?>
                            <span class="crowd-icon"><?php echo $weekend_data['icon']; ?></span>
                            <span class="crowd-text"><?php echo esc_html($weekend_data['text']); ?></span>
                            <span class="crowd-desc"><?php echo esc_html($weekend_data['desc']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
            <?php endif; ?>

            <!-- 특별 옵션 및 접근성 -->
            <?php if (!empty($special_options) || !empty($accessibility_features)) : ?>
            <div class="features-group">
                <h3 class="subsection-title">
                    <span class="subsection-icon">✨</span>
                    특별 정보
                </h3>
                
                <?php if (!empty($special_options)) : ?>
                <div class="special-options">
                    <h4 class="feature-title">🌟 특별 옵션</h4>
                    <div class="feature-badges">
                        <?php
                        $special_labels = array(
                            'limited_edition' => '🌟 한정판매',
                            'collaboration' => '🤝 콜라보레이션',
                            'first_store' => '🎉 첫 매장',
                            'exclusive' => '💎 독점 상품',
                            'experience' => '🎭 체험형 스토어',
                            'photo_zone' => '📸 포토존 운영',
                            'celebrity' => '⭐ 셀럽 관련',
                            'sustainable' => '🌱 친환경'
                        );
                        
                        foreach ($special_options as $option) {
                            if (isset($special_labels[$option])) {
                                echo '<span class="feature-badge special-badge">' . esc_html($special_labels[$option]) . '</span>';
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($accessibility_features)) : ?>
                <div class="accessibility-options">
                    <h4 class="feature-title">♿ 접근성</h4>
                    <div class="feature-badges">
                        <?php
                        $accessibility_labels = array(
                            'wheelchair_accessible' => '♿ 휠체어 접근 가능',
                            'elevator_available' => '🛗 엘리베이터 이용 가능',
                            'parking_available' => '🅿️ 주차 가능',
                            'stroller_friendly' => '🍼 유모차 접근 가능',
                            'guide_dog_allowed' => '🦮 안내견 출입 가능',
                            'audio_guide' => '🎧 음성 가이드 제공',
                            'sign_language' => '🤟 수어 서비스',
                            'senior_friendly' => '👴 고령자 친화'
                        );
                        
                        foreach ($accessibility_features as $feature) {
                            if (isset($accessibility_labels[$feature])) {
                                echo '<span class="feature-badge accessibility-badge">' . esc_html($accessibility_labels[$feature]) . '</span>';
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            <?php endif; ?>

            <!-- 연계 장소 추천 -->
            <?php if ($nearby_recommendations || $combination_tips) : ?>
            <div class="recommendations-group">
                <h3 class="subsection-title">
                    <span class="subsection-icon">🔗</span>
                    투어 연계 정보
                </h3>
                
                <?php if ($nearby_recommendations) : ?>
                <div class="nearby-recommendations">
                    <h4 class="recommendation-title">📍 주변 추천 장소</h4>
                    <div class="recommendation-content">
                        <?php echo wp_kses_post(wpautop($nearby_recommendations)); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($combination_tips) : ?>
                <div class="combination-tips">
                    <h4 class="recommendation-title">💡 조합 팁</h4>
                    <div class="recommendation-content tips-content">
                        <?php echo wp_kses_post(wpautop($combination_tips)); ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            <?php endif; ?>

            <!-- 특별 안내사항 -->
            <?php if ($special_notes) : ?>
            <div class="special-notes-group">
                <h3 class="subsection-title">
                    <span class="subsection-icon">⚠️</span>
                    특별 안내사항
                </h3>
                <div class="special-notes-content">
                    <?php echo wp_kses_post(wpautop($special_notes)); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 투어 플래너 추가 버튼 -->
            <div class="tour-actions">
                <button type="button" 
                        class="btn btn-primary add-to-tour-btn" 
                        onclick="addToTourPlanner(<?php echo esc_js($post_id); ?>)">
                    🗺️ 투어 플래너에 추가
                </button>
                <button type="button" 
                        class="btn btn-secondary similar-stores-btn" 
                        onclick="findSimilarStores('<?php echo esc_js(get_post_meta($post_id, 'category', true)); ?>')">
                    🔍 비슷한 스토어 찾기
                </button>
            </div>

        </div>
    </div>
</section>

<script>
// 투어 플래너에 추가
function addToTourPlanner(storeId) {
    // 로컬 스토리지에 투어 목록 저장
    let tourList = JSON.parse(localStorage.getItem('sungsuya_tour_list') || '[]');
    
    if (!tourList.includes(storeId)) {
        tourList.push(storeId);
        localStorage.setItem('sungsuya_tour_list', JSON.stringify(tourList));
        showToast('투어 플래너에 추가되었습니다', 'success');
        
        // 버튼 상태 변경
        const btn = document.querySelector('.add-to-tour-btn');
        btn.innerHTML = '✅ 투어에 추가됨';
        btn.classList.add('added');
        btn.disabled = true;
    } else {
        showToast('이미 투어 플래너에 추가된 스토어입니다', 'info');
    }
}

// 비슷한 스토어 찾기
function findSimilarStores(category) {
    if (category) {
        // 카테고리 기반 필터링 페이지로 이동
        window.location.href = `<?php echo home_url('/stores/'); ?>?category=${encodeURIComponent(category)}`;
    } else {
        showToast('카테고리 정보가 없습니다', 'warning');
    }
}

// 페이지 로드 시 투어 목록에 있는지 확인
document.addEventListener('DOMContentLoaded', function() {
    const storeId = <?php echo esc_js($post_id); ?>;
    const tourList = JSON.parse(localStorage.getItem('sungsuya_tour_list') || '[]');
    const addBtn = document.querySelector('.add-to-tour-btn');
    
    if (tourList.includes(storeId) && addBtn) {
        addBtn.innerHTML = '✅ 투어에 추가됨';
        addBtn.classList.add('added');
        addBtn.disabled = true;
    }
});
</script>
