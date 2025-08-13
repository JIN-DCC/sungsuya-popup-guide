<?php
/**
 * Template Name: My Tours
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

// 투어 데이터 가져오기
$tour_saver = new SungsuyaTourSaver();
$user_tours = $tour_saver->get_user_tours($current_user->ID);
$tour_count = $tour_saver->get_user_tour_count($current_user->ID);
?>

<style>
.my-tours-page {
    padding: 100px 0;
    min-height: calc(100vh - 200px);
    background: var(--dark-bg);
}

.tours-container {
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

.tours-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-top: 40px;
}

.tour-card {
    background: var(--dark-surface);
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.tour-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.tour-header {
    padding: 24px;
    border-bottom: 1px solid var(--dark-border);
}

.tour-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 8px;
    color: #FFFFFF;
}

.tour-date {
    color: var(--muted-text);
    font-size: 14px;
}

.tour-places {
    padding: 24px;
}

.place-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.place-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    color: #FFFFFF;
    opacity: 0.8;
}

.place-number {
    width: 24px;
    height: 24px;
    background: var(--primary-gradient);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    color: white;
    flex-shrink: 0;
}

.place-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.tour-stats {
    padding: 16px 24px;
    background: rgba(255, 255, 255, 0.05);
    display: flex;
    gap: 24px;
    font-size: 14px;
    color: var(--muted-text);
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.stat-item svg {
    width: 16px;
    height: 16px;
}

.tour-actions {
    padding: 24px;
    border-top: 1px solid var(--dark-border);
    display: flex;
    gap: 12px;
}

.tour-action {
    flex: 1;
    padding: 10px;
    text-align: center;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    color: #FFFFFF;
}

.tour-action:hover {
    background: var(--primary-gradient);
    border-color: transparent;
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

.create-tour-btn {
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

.create-tour-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

.delete-btn {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 32px;
    height: 32px;
    background: rgba(255, 0, 0, 0.1);
    border: 1px solid rgba(255, 0, 0, 0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: all 0.3s ease;
}

.tour-card:hover .delete-btn {
    opacity: 1;
}

.delete-btn:hover {
    background: rgba(255, 0, 0, 0.2);
    border-color: rgba(255, 0, 0, 0.5);
}

.delete-btn svg {
    width: 16px;
    height: 16px;
    stroke: #ff4444;
}

@media (max-width: 1024px) {
    .tours-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .tours-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<main class="my-tours-page">
    <div class="tours-container">
        <div class="page-header">
            <h1 class="page-title">내 투어</h1>
            <p class="page-subtitle">저장한 투어 코스를 관리하고 공유하세요</p>
        </div>
        
        <?php if (!empty($user_tours)): ?>
            <div class="tours-grid">
                <?php foreach ($user_tours as $tour): ?>
                    <article class="tour-card" data-tour-id="<?php echo esc_attr($tour['tour_id']); ?>">
                        <button class="delete-btn" data-tour-id="<?php echo esc_attr($tour['tour_id']); ?>" title="삭제">
                            <i data-feather="trash-2"></i>
                        </button>
                        
                        <div class="tour-header">
                            <h3 class="tour-title"><?php echo esc_html($tour['title']); ?></h3>
                            <p class="tour-date"><?php echo date('Y년 m월 d일', strtotime($tour['created_at'])); ?> 저장</p>
                        </div>
                        
                        <?php if (!empty($tour['places_data'])): ?>
                            <div class="tour-places">
                                <ul class="place-list">
                                    <?php 
                                    $places = array_slice($tour['places_data'], 0, 3);
                                    foreach ($places as $index => $place): 
                                    ?>
                                        <li class="place-item">
                                            <span class="place-number"><?php echo $index + 1; ?></span>
                                            <span class="place-name"><?php echo esc_html($place['name']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (count($tour['places_data']) > 3): ?>
                                        <li class="place-item">
                                            <span class="place-number">...</span>
                                            <span class="place-name">외 <?php echo count($tour['places_data']) - 3; ?>곳</span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="tour-stats">
                            <div class="stat-item">
                                <i data-feather="eye"></i>
                                <span>조회 <?php echo number_format($tour['view_count']); ?></span>
                            </div>
                            <div class="stat-item">
                                <i data-feather="share-2"></i>
                                <span>공유 <?php echo number_format($tour['share_count']); ?></span>
                            </div>
                        </div>
                        
                        <div class="tour-actions">
                            <a href="<?php echo home_url('/tour/' . $tour['tour_id']); ?>" class="tour-action">보기</a>
                            <button class="tour-action share-btn" data-tour-id="<?php echo esc_attr($tour['tour_id']); ?>">공유</button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i data-feather="map"></i>
                </div>
                <h2 class="empty-title">아직 저장된 투어가 없습니다</h2>
                <p class="empty-description">
                    성수동의 멋진 장소들로 나만의 투어 코스를 만들어보세요
                </p>
                <a href="<?php echo home_url('/tour-v2'); ?>" class="create-tour-btn">
                    <i data-feather="plus-circle"></i>
                    <span>투어 만들기</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    feather.replace();
    
    // 공유 버튼 처리
    const shareButtons = document.querySelectorAll('.share-btn');
    shareButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const tourId = this.dataset.tourId;
            const shareUrl = `${window.location.origin}/tour/${tourId}`;
            
            if (navigator.share) {
                navigator.share({
                    title: '성수야! 투어 공유',
                    text: '나만의 성수동 투어 코스를 확인해보세요!',
                    url: shareUrl
                }).catch(err => console.log('공유 취소'));
            } else {
                // 클립보드에 복사
                navigator.clipboard.writeText(shareUrl).then(() => {
                    alert('링크가 복사되었습니다!');
                });
            }
            
            // 공유 카운트 증가 API 호출
            fetch(`/wp-json/sungsuya/v2/tours/${tourId}/share`, {
                method: 'POST'
            });
        });
    });
    
    // 삭제 버튼 처리 (옵션)
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            if (confirm('이 투어를 삭제하시겠습니까?')) {
                const tourId = this.dataset.tourId;
                // 삭제 API 구현 필요
                alert('삭제 기능은 준비 중입니다.');
            }
        });
    });
    
    // 카드 클릭 시 투어 페이지로 이동
    const tourCards = document.querySelectorAll('.tour-card');
    tourCards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.closest('.delete-btn') && !e.target.closest('.share-btn')) {
                const tourId = this.dataset.tourId;
                window.location.href = `/tour/${tourId}`;
            }
        });
    });
});
</script>

<?php get_footer(); ?>