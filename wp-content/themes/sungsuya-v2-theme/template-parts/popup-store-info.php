<?php
/**
 * 팝업스토어 정보 섹션 (확장 버전)
 * 운영시간, 기간, 연락처 등 기본 정보 + 새로운 메타 필드들 표시
 */

$store_id = get_the_ID();

// 기존 메타 데이터 (호환성 유지)
$opening_hours = get_post_meta($store_id, 'opening_hours', true) ?: get_post_meta($store_id, '_opening_hours', true);
$start_date = get_post_meta($store_id, 'start_date', true) ?: get_post_meta($store_id, '_start_date', true);
$end_date = get_post_meta($store_id, 'end_date', true) ?: get_post_meta($store_id, '_end_date', true);
$website = get_post_meta($store_id, 'website', true) ?: get_post_meta($store_id, '_website', true);
$instagram = get_post_meta($store_id, 'instagram', true) ?: get_post_meta($store_id, '_instagram', true);
$contact_info = get_post_meta($store_id, 'contact_info', true) ?: get_post_meta($store_id, '_contact_info', true);
$price_range = get_post_meta($store_id, 'price_range', true) ?: get_post_meta($store_id, '_price_range', true);

// 새로운 메타 데이터 (혁신적 관리자 시스템)
$store_name = get_post_meta($store_id, 'store_name', true);
$store_description = get_post_meta($store_id, 'store_description', true);
$category = get_post_meta($store_id, 'category', true);
$operation_status = get_post_meta($store_id, 'operation_status', true);
$phone_number = get_post_meta($store_id, 'phone_number', true);
$email = get_post_meta($store_id, 'email', true);
$website_url = get_post_meta($store_id, 'website_url', true);

// 카테고리 정보 (택소노미 + 메타 필드)
$categories = get_the_terms($store_id, 'store_category');
?>

<div class="container">
    <div class="section-header">
        <h2 class="section-title">
            <span class="section-icon">ℹ️</span>
            스토어 정보
        </h2>
    </div>
    
    <div class="store-info-grid">
        
        <!-- 스토어명 (새로운 필드) -->
        <?php if ($store_name && $store_name !== get_the_title()) : ?>
        <div class="info-item">
            <div class="info-icon">🏪</div>
            <div class="info-content">
                <h3 class="info-title">공식 스토어명</h3>
                <p class="info-value"><?php echo esc_html($store_name); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 카테고리 (택소노미 + 새로운 메타 필드) -->
        <?php if (($categories && !is_wp_error($categories)) || $category) : ?>
        <div class="info-item">
            <div class="info-icon">🏷️</div>
            <div class="info-content">
                <h3 class="info-title">카테고리</h3>
                <div class="info-value">
                    <?php if ($categories && !is_wp_error($categories)) : ?>
                        <?php foreach ($categories as $cat) : ?>
                            <span class="category-tag"><?php echo esc_html($cat->name); ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if ($category) : ?>
                        <?php
                        $category_labels = array(
                            'fashion' => '👗 패션 & 의류',
                            'beauty' => '💄 뷰티 & 코스메틱',
                            'lifestyle' => '🏠 라이프스타일',
                            'food' => '🍽️ 푸드 & 음료',
                            'art' => '🎨 아트 & 디자인',
                            'tech' => '📱 테크 & 가젯',
                            'sports' => '⚽ 스포츠 & 아웃도어',
                            'music' => '🎵 음악 & 엔터테인먼트',
                            'book' => '📚 북 & 문구',
                            'kids' => '🧸 키즈 & 토이',
                            'pet' => '🐕 펫 & 용품',
                            'other' => '🎁 기타'
                        );
                        ?>
                        <span class="category-tag enhanced"><?php echo esc_html($category_labels[$category] ?? $category); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 운영 상태 (새로운 필드) -->
        <?php if ($operation_status) : ?>
        <div class="info-item">
            <div class="info-icon">
                <?php
                $status_icons = array(
                    'upcoming' => '🔜',
                    'open' => '✅',
                    'closing_soon' => '⏰',
                    'closed' => '❌',
                    'temporary_closed' => '⏸️'
                );
                echo $status_icons[$operation_status] ?? '📋';
                ?>
            </div>
            <div class="info-content">
                <h3 class="info-title">운영 상태</h3>
                <p class="info-value status-<?php echo esc_attr($operation_status); ?>">
                    <?php
                    $status_labels = array(
                        'upcoming' => '오픈 예정',
                        'open' => '운영 중',
                        'closing_soon' => '곧 종료',
                        'closed' => '종료됨',
                        'temporary_closed' => '임시 휴업'
                    );
                    echo esc_html($status_labels[$operation_status] ?? $operation_status);
                    ?>
                </p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 운영 시간 -->
        <?php if ($opening_hours) : ?>
        <div class="info-item">
            <div class="info-icon">🕐</div>
            <div class="info-content">
                <h3 class="info-title">운영시간</h3>
                <p class="info-value"><?php echo esc_html($opening_hours); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- 운영 기간 -->
        <?php if ($start_date && $end_date) : ?>
        <div class="info-item">
            <div class="info-icon">📅</div>
            <div class="info-content">
                <h3 class="info-title">운영기간</h3>
                <p class="info-value">
                    <?php echo date('Y.m.d', strtotime($start_date)); ?> - 
                    <?php echo date('Y.m.d', strtotime($end_date)); ?>
                </p>
                
                <!-- 남은 일수 계산 -->
                <?php
                $today = new DateTime();
                $end_date_obj = new DateTime($end_date);
                if ($end_date_obj > $today) {
                    $diff = $today->diff($end_date_obj);
                    echo '<p class="info-extra">D-' . $diff->days . '</p>';
                } else {
                    echo '<p class="info-extra status-closed">운영종료</p>';
                }
                ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 가격대 -->
        <?php if ($price_range) : ?>
        <div class="info-item">
            <div class="info-icon">💰</div>
            <div class="info-content">
                <h3 class="info-title">가격대</h3>
                <p class="info-value"><?php echo esc_html($price_range); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- 연락처 (기존 + 새로운 필드) -->
        <?php if ($contact_info || $phone_number || $email) : ?>
        <div class="info-item">
            <div class="info-icon">📞</div>
            <div class="info-content">
                <h3 class="info-title">연락처</h3>
                <div class="info-value">
                    
                    <!-- 새로운 전화번호 필드 -->
                    <?php if ($phone_number) : ?>
                    <p class="contact-item">
                        <a href="tel:<?php echo esc_attr(preg_replace('/[^\d]/', '', $phone_number)); ?>" class="info-link">
                            📞 <?php echo esc_html($phone_number); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <!-- 새로운 이메일 필드 -->
                    <?php if ($email) : ?>
                    <p class="contact-item">
                        <a href="mailto:<?php echo esc_attr($email); ?>" class="info-link">
                            📧 <?php echo esc_html($email); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <!-- 기존 연락처 정보 (호환성 유지) -->
                    <?php if ($contact_info && !$phone_number && !$email) : ?>
                    <p class="contact-item">
                        <?php if (strpos($contact_info, '@') !== false) : ?>
                            <a href="mailto:<?php echo esc_attr($contact_info); ?>" class="info-link">
                                📧 <?php echo esc_html($contact_info); ?>
                            </a>
                        <?php elseif (preg_match('/[\d\-\s()]+/', $contact_info)) : ?>
                            <a href="tel:<?php echo esc_attr(preg_replace('/[^\d]/', '', $contact_info)); ?>" class="info-link">
                                📞 <?php echo esc_html($contact_info); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html($contact_info); ?>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 웹사이트 (기존 + 새로운 필드) -->
        <?php $website_display = $website_url ?: $website; ?>
        <?php if ($website_display) : ?>
        <div class="info-item">
            <div class="info-icon">🌐</div>
            <div class="info-content">
                <h3 class="info-title">웹사이트</h3>
                <p class="info-value">
                    <a href="<?php echo esc_url($website_display); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="info-link">
                        <?php 
                        // URL에서 도메인만 표시
                        $parsed_url = parse_url($website_display);
                        echo esc_html($parsed_url['host'] ?? $website_display);
                        ?>
                        <span class="external-icon">↗</span>
                    </a>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- 인스타그램 (기존 필드, 호환성 유지) -->
        <?php if ($instagram) : ?>
        <div class="info-item">
            <div class="info-icon">📸</div>
            <div class="info-content">
                <h3 class="info-title">인스타그램</h3>
                <p class="info-value">
                    <a href="https://instagram.com/<?php echo esc_attr(ltrim($instagram, '@')); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="info-link">
                        <?php echo esc_html($instagram); ?>
                        <span class="external-icon">↗</span>
                    </a>
                </p>
            </div>
        </div>
        <?php endif; ?>

    </div>
    
    <!-- 스토어 설명 (새로운 필드) -->
    <?php if ($store_description) : ?>
    <div class="store-description-section">
        <h3 class="subsection-title">
            <span class="subsection-icon">📝</span>
            스토어 소개
        </h3>
        <div class="store-description-content">
            <?php echo wp_kses_post(wpautop($store_description)); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- 정보가 없는 경우 안내 메시지 -->
    <?php
    $has_any_info = $opening_hours || $start_date || $end_date || $website || $website_url || 
                    $instagram || $contact_info || $phone_number || $email || $price_range || 
                    $store_name || $store_description || $category || $operation_status;
    ?>
    <?php if (!$has_any_info) : ?>
    <div class="no-info-message">
        <div class="no-info-icon">📝</div>
        <h3>추가 정보가 준비중입니다</h3>
        <p>스토어 상세 정보는 곧 업데이트될 예정입니다.</p>
    </div>
    <?php endif; ?>
</div>
