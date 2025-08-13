<?php
/**
 * Template Name: My Reviews
 * 
 * @package SungsuyaV2
 */

get_header(); 

// 로그인 체크
if (!is_user_logged_in()) {
    wp_redirect(home_url('/tour-login'));
    exit;
}

$current_user = wp_get_current_user();
?>

<style>
.my-reviews-page {
    padding: 100px 0;
    min-height: calc(100vh - 200px);
    background: var(--dark-bg);
}

.reviews-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 60px;
}

.page-title {
    font-family: var(--font-display);
    font-size: 48px;
    margin-bottom: 16px;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-subtitle {
    color: var(--muted-text);
    font-size: 18px;
}

.reviews-list {
    max-width: 800px;
    margin: 0 auto;
}

.review-card {
    background: var(--dark-surface);
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 24px;
    transition: all 0.3s ease;
}

.review-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 20px;
}

.place-info {
    flex: 1;
}

.place-name {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 8px;
    color: #FFFFFF;
}

.review-date {
    color: var(--muted-text);
    font-size: 14px;
}

.review-rating {
    display: flex;
    gap: 4px;
}

.star {
    width: 20px;
    height: 20px;
    color: #FFD700;
}

.star.empty {
    color: var(--dark-border);
}

.review-content {
    color: #FFFFFF;
    line-height: 1.6;
    margin-bottom: 20px;
}

.review-images {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.review-image {
    width: 100px;
    height: 100px;
    border-radius: 8px;
    object-fit: cover;
}

.review-actions {
    display: flex;
    gap: 16px;
    padding-top: 20px;
    border-top: 1px solid var(--dark-border);
}

.review-action {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.review-action:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
}

.review-action svg {
    width: 16px;
    height: 16px;
}

.empty-state {
    text-align: center;
    padding: 100px 20px;
}

.empty-icon {
    width: 120px;
    height: 120px;
    margin: 0 auto 24px;
    background: var(--dark-surface);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-icon svg {
    width: 60px;
    height: 60px;
    stroke: var(--muted-text);
}

.empty-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 12px;
}

.empty-description {
    color: var(--muted-text);
    margin-bottom: 32px;
}

.write-review-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 16px 32px;
    background: var(--primary-gradient);
    color: white;
    border-radius: 30px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.write-review-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

@media (max-width: 768px) {
    .review-card {
        padding: 20px;
    }
    
    .review-header {
        flex-direction: column;
        gap: 12px;
    }
    
    .review-images {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}
</style>

<main class="my-reviews-page">
    <div class="reviews-container">
        <div class="page-header">
            <h1 class="page-title">내 리뷰</h1>
            <p class="page-subtitle">작성한 리뷰를 관리하고 수정하세요</p>
        </div>
        
        <?php
        // 여기에 실제 리뷰 데이터를 가져오는 로직이 필요합니다
        // 현재는 예시 데이터를 표시합니다
        $has_reviews = false;
        ?>
        
        <?php if ($has_reviews): ?>
            <div class="reviews-list">
                <!-- 리뷰 카드 예시 -->
                <article class="review-card">
                    <div class="review-header">
                        <div class="place-info">
                            <h3 class="place-name">블루보틀 성수</h3>
                            <p class="review-date">2025년 1월 2일 작성</p>
                        </div>
                        <div class="review-rating">
                            <svg class="star" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="star" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="star" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="star" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <svg class="star empty" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="review-content">
                        성수동의 블루보틀은 정말 특별해요. 넓은 공간과 높은 천장이 인상적이고,
                        커피 맛도 훌륭합니다. 주말에는 웨이팅이 있을 수 있으니 평일 방문을 추천해요.
                    </div>
                    
                    <div class="review-actions">
                        <button class="review-action">
                            <i data-feather="edit-2"></i>
                            <span>수정</span>
                        </button>
                        <button class="review-action">
                            <i data-feather="trash-2"></i>
                            <span>삭제</span>
                        </button>
                    </div>
                </article>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i data-feather="star"></i>
                </div>
                <h2 class="empty-title">아직 작성한 리뷰가 없습니다</h2>
                <p class="empty-description">
                    방문한 장소에 대한 생생한 후기를 남겨주세요
                </p>
                <a href="<?php echo home_url('/places'); ?>" class="write-review-btn">
                    <i data-feather="edit"></i>
                    <span>장소 둘러보기</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    feather.replace();
});
</script>

<?php get_footer(); ?>