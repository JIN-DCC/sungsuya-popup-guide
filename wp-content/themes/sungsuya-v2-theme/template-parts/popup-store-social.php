<?php
/**
 * 팝업스토어 소셜 & 연락처 정보 템플릿 파트
 * 
 * @package SungsuyaV2
 */

$post_id = get_the_ID();

// 소셜 미디어 링크들
$instagram_url = get_post_meta($post_id, 'instagram_url', true);
$facebook_url = get_post_meta($post_id, 'facebook_url', true);
$youtube_url = get_post_meta($post_id, 'youtube_url', true);
$website_url = get_post_meta($post_id, 'website_url', true);

// 연락처 정보
$phone_number = get_post_meta($post_id, 'phone_number', true);
$email = get_post_meta($post_id, 'email', true);

// 예약 링크들
$booking_url = get_post_meta($post_id, 'booking_url', true);
$event_url = get_post_meta($post_id, 'event_url', true);

// 해시태그
$hashtags = get_post_meta($post_id, 'hashtags', true);

// 소셜 미디어나 연락처가 하나라도 있으면 섹션 표시
$has_social_info = $instagram_url || $facebook_url || $youtube_url || $website_url || 
                   $phone_number || $email || $booking_url || $event_url || $hashtags;

if (!$has_social_info) {
    return;
}
?>

<section class="store-social-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-icon">📱</span>
                소셜 & 연락처
            </h2>
        </div>

        <div class="social-content">
            
            <!-- 소셜 미디어 링크들 -->
            <?php if ($instagram_url || $facebook_url || $youtube_url || $website_url) : ?>
            <div class="social-links-group">
                <h3 class="subsection-title">
                    <span class="subsection-icon">🌐</span>
                    소셜 미디어
                </h3>
                <div class="social-links">
                    <?php if ($instagram_url) : ?>
                    <a href="<?php echo esc_url($instagram_url); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="social-link instagram-link">
                        <span class="social-icon">📷</span>
                        <span class="social-name">Instagram</span>
                        <span class="social-action">팔로우하기</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($facebook_url) : ?>
                    <a href="<?php echo esc_url($facebook_url); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="social-link facebook-link">
                        <span class="social-icon">👥</span>
                        <span class="social-name">Facebook</span>
                        <span class="social-action">좋아요</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($youtube_url) : ?>
                    <a href="<?php echo esc_url($youtube_url); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="social-link youtube-link">
                        <span class="social-icon">🎥</span>
                        <span class="social-name">YouTube</span>
                        <span class="social-action">구독하기</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($website_url) : ?>
                    <a href="<?php echo esc_url($website_url); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="social-link website-link">
                        <span class="social-icon">🌍</span>
                        <span class="social-name">공식 웹사이트</span>
                        <span class="social-action">방문하기</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 연락처 정보 -->
            <?php if ($phone_number || $email) : ?>
            <div class="contact-info-group">
                <h3 class="subsection-title">
                    <span class="subsection-icon">📞</span>
                    연락처 정보
                </h3>
                <div class="contact-info">
                    <?php if ($phone_number) : ?>
                    <div class="contact-item phone-item">
                        <span class="contact-icon">📞</span>
                        <span class="contact-label">전화번호</span>
                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $phone_number)); ?>" 
                           class="contact-value phone-link">
                           <?php echo esc_html($phone_number); ?>
                        </a>
                        <button type="button" 
                                class="copy-btn" 
                                onclick="copyToClipboard('<?php echo esc_js($phone_number); ?>', '전화번호가 복사되었습니다')">
                            📋
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($email) : ?>
                    <div class="contact-item email-item">
                        <span class="contact-icon">📧</span>
                        <span class="contact-label">이메일</span>
                        <a href="mailto:<?php echo esc_attr($email); ?>" class="contact-value email-link">
                            <?php echo esc_html($email); ?>
                        </a>
                        <button type="button" 
                                class="copy-btn" 
                                onclick="copyToClipboard('<?php echo esc_js($email); ?>', '이메일이 복사되었습니다')">
                            📋
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 예약 및 참여 링크 -->
            <?php if ($booking_url || $event_url) : ?>
            <div class="booking-links-group">
                <h3 class="subsection-title">
                    <span class="subsection-icon">📝</span>
                    예약 & 참여
                </h3>
                <div class="booking-links">
                    <?php if ($booking_url) : ?>
                    <a href="<?php echo esc_url($booking_url); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="booking-link reservation-link">
                        <span class="booking-icon">📅</span>
                        <span class="booking-title">사전 예약하기</span>
                        <span class="booking-description">방문 전 미리 예약해보세요</span>
                        <span class="external-indicator">↗</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($event_url) : ?>
                    <a href="<?php echo esc_url($event_url); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="booking-link event-link">
                        <span class="booking-icon">🎉</span>
                        <span class="booking-title">이벤트 참여하기</span>
                        <span class="booking-description">특별 이벤트에 참여해보세요</span>
                        <span class="external-indicator">↗</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 해시태그 -->
            <?php if ($hashtags) : ?>
            <div class="hashtags-group">
                <h3 class="subsection-title">
                    <span class="subsection-icon">#️⃣</span>
                    관련 해시태그
                </h3>
                <div class="hashtags-container">
                    <?php
                    // 해시태그를 쉼표나 공백으로 분리
                    $hashtag_array = preg_split('/[,\s]+/', $hashtags);
                    $hashtag_array = array_filter($hashtag_array); // 빈 값 제거
                    
                    foreach ($hashtag_array as $hashtag) {
                        $hashtag = trim($hashtag);
                        if (!empty($hashtag)) {
                            // # 없으면 추가
                            if (strpos($hashtag, '#') !== 0) {
                                $hashtag = '#' . $hashtag;
                            }
                            echo '<span class="hashtag" onclick="searchHashtag(\'' . esc_js($hashtag) . '\')">';
                            echo esc_html($hashtag);
                            echo '</span>';
                        }
                    }
                    ?>
                </div>
                <div class="hashtags-hint">
                    해시태그를 클릭하면 관련 콘텐츠를 검색할 수 있습니다
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<script>
// 복사 기능
function copyToClipboard(text, message) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast(message, 'success');
        }).catch(() => {
            fallbackCopy(text, message);
        });
    } else {
        fallbackCopy(text, message);
    }
}

function fallbackCopy(text, message) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    showToast(message, 'success');
}

// 해시태그 검색
function searchHashtag(hashtag) {
    // 인스타그램 해시태그 검색으로 연결
    const instagramUrl = `https://www.instagram.com/explore/tags/${hashtag.replace('#', '')}/`;
    window.open(instagramUrl, '_blank');
}

// 소셜 링크 클릭 추적 (선택사항)
document.addEventListener('DOMContentLoaded', function() {
    const socialLinks = document.querySelectorAll('.social-link, .booking-link');
    socialLinks.forEach(link => {
        link.addEventListener('click', function() {
            // 분석 추적 코드 (Google Analytics 등)
            const linkType = this.classList.contains('social-link') ? 'social' : 'booking';
            const linkName = this.querySelector('.social-name, .booking-title')?.textContent || 'unknown';
            
            // gtag('event', 'click', {
            //     event_category: linkType,
            //     event_label: linkName
            // });
        });
    });
});
</script>
