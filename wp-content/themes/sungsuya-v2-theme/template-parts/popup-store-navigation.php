<?php
/**
 * 팝업스토어 네비게이션 섹션
 * 이전/다음 스토어 이동 네비게이션
 */

// 현재 스토어의 이전/다음 스토어 찾기
$prev_post = get_previous_post(false, '', 'store_category');
$next_post = get_next_post(false, '', 'store_category');

// 전체 스토어 개수
$total_stores = wp_count_posts('popup_store')->publish;
?>

<div class="store-navigation">
    <div class="container">
        <div class="nav-content">
            
            <!-- 이전 스토어 -->
            <div class="nav-item nav-prev">
                <?php if ($prev_post) : ?>
                    <a href="<?php echo get_permalink($prev_post->ID); ?>" class="nav-link">
                        <span class="nav-direction">
                            <span class="nav-arrow">←</span>
                            <span class="nav-label">이전 스토어</span>
                        </span>
                        <div class="nav-store-info">
                            <?php if (has_post_thumbnail($prev_post->ID)) : ?>
                                <div class="nav-thumbnail">
                                    <?php echo get_the_post_thumbnail($prev_post->ID, 'thumbnail', ['class' => 'nav-image']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="nav-details">
                                <h3 class="nav-title"><?php echo esc_html($prev_post->post_title); ?></h3>
                                <?php
                                $prev_category = get_the_terms($prev_post->ID, 'store_category');
                                if ($prev_category && !is_wp_error($prev_category)) :
                                ?>
                                    <span class="nav-category"><?php echo esc_html($prev_category[0]->name); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php else : ?>
                    <div class="nav-link nav-disabled">
                        <span class="nav-direction">
                            <span class="nav-arrow">←</span>
                            <span class="nav-label">첫 번째 스토어</span>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- 중앙 정보 -->
            <div class="nav-center">
                <div class="nav-home">
                    <a href="<?php echo home_url('/stores/'); ?>" class="nav-home-link">
                        <span class="home-icon">🏠</span>
                        <span class="home-text">전체 스토어</span>
                    </a>
                </div>
                <div class="nav-counter">
                    <span class="counter-text">총 <?php echo number_format($total_stores); ?>개 스토어</span>
                </div>
            </div>
            
            <!-- 다음 스토어 -->
            <div class="nav-item nav-next">
                <?php if ($next_post) : ?>
                    <a href="<?php echo get_permalink($next_post->ID); ?>" class="nav-link">
                        <span class="nav-direction">
                            <span class="nav-label">다음 스토어</span>
                            <span class="nav-arrow">→</span>
                        </span>
                        <div class="nav-store-info">
                            <div class="nav-details">
                                <h3 class="nav-title"><?php echo esc_html($next_post->post_title); ?></h3>
                                <?php
                                $next_category = get_the_terms($next_post->ID, 'store_category');
                                if ($next_category && !is_wp_error($next_category)) :
                                ?>
                                    <span class="nav-category"><?php echo esc_html($next_category[0]->name); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (has_post_thumbnail($next_post->ID)) : ?>
                                <div class="nav-thumbnail">
                                    <?php echo get_the_post_thumbnail($next_post->ID, 'thumbnail', ['class' => 'nav-image']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php else : ?>
                    <div class="nav-link nav-disabled">
                        <span class="nav-direction">
                            <span class="nav-label">마지막 스토어</span>
                            <span class="nav-arrow">→</span>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>

<!-- 키보드 네비게이션 스크립트 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 키보드 단축키 지원
    document.addEventListener('keydown', function(e) {
        // 입력 필드에서 키를 누른 경우 무시
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return;
        }
        
        switch(e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                <?php if ($prev_post) : ?>
                    window.location.href = '<?php echo get_permalink($prev_post->ID); ?>';
                <?php endif; ?>
                break;
                
            case 'ArrowRight':
                e.preventDefault();
                <?php if ($next_post) : ?>
                    window.location.href = '<?php echo get_permalink($next_post->ID); ?>';
                <?php endif; ?>
                break;
                
            case 'Escape':
                e.preventDefault();
                window.location.href = '<?php echo home_url('/stores/'); ?>';
                break;
        }
    });
    
    // 터치 스와이프 지원 (모바일)
    let touchStartX = 0;
    let touchEndX = 0;
    
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    });
    
    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });
    
    function handleSwipe() {
        const swipeThreshold = 50;
        const swipeDistance = touchEndX - touchStartX;
        
        if (Math.abs(swipeDistance) > swipeThreshold) {
            if (swipeDistance > 0) {
                // 오른쪽 스와이프 - 이전 스토어
                <?php if ($prev_post) : ?>
                    window.location.href = '<?php echo get_permalink($prev_post->ID); ?>';
                <?php endif; ?>
            } else {
                // 왼쪽 스와이프 - 다음 스토어
                <?php if ($next_post) : ?>
                    window.location.href = '<?php echo get_permalink($next_post->ID); ?>';
                <?php endif; ?>
            }
        }
    }
    
    // 네비게이션 링크 호버 효과
    const navLinks = document.querySelectorAll('.nav-link:not(.nav-disabled)');
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>
