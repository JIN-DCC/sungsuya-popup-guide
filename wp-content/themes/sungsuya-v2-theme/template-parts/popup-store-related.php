<?php
/**
 * 팝업스토어 관련 스토어 섹션
 * 같은 카테고리나 유사한 스토어 추천
 */

$current_post_id = get_the_ID();

// 현재 스토어의 카테고리 가져오기
$current_categories = get_the_terms($current_post_id, 'store_category');
$category_ids = [];

if ($current_categories && !is_wp_error($current_categories)) {
    foreach ($current_categories as $category) {
        $category_ids[] = $category->term_id;
    }
}

// 관련 스토어 쿼리
$related_args = [
    'post_type' => 'popup_store',
    'posts_per_page' => 6,
    'post__not_in' => [$current_post_id],
    'post_status' => 'publish',
    'meta_key' => '_thumbnail_id' // 썸네일이 있는 것만
];

// 카테고리가 있으면 같은 카테고리 우선
if (!empty($category_ids)) {
    $related_args['tax_query'] = [
        [
            'taxonomy' => 'store_category',
            'field' => 'term_id',
            'terms' => $category_ids,
            'operator' => 'IN'
        ]
    ];
}

$related_query = new WP_Query($related_args);

// 같은 카테고리 스토어가 부족하면 추가로 최신 스토어 가져오기
if ($related_query->found_posts < 4) {
    $additional_args = [
        'post_type' => 'popup_store',
        'posts_per_page' => 6,
        'post__not_in' => [$current_post_id],
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    ];
    
    // 이미 가져온 스토어들 제외
    if ($related_query->have_posts()) {
        $existing_ids = [$current_post_id];
        while ($related_query->have_posts()) {
            $related_query->the_post();
            $existing_ids[] = get_the_ID();
        }
        $additional_args['post__not_in'] = $existing_ids;
        wp_reset_postdata();
    }
    
    $additional_query = new WP_Query($additional_args);
    
    // 두 쿼리 결과 합치기
    if ($additional_query->have_posts()) {
        $all_posts = [];
        
        // 관련 스토어 먼저 추가
        if ($related_query->have_posts()) {
            $related_query->rewind_posts();
            while ($related_query->have_posts()) {
                $related_query->the_post();
                $all_posts[] = $related_query->post;
            }
        }
        
        // 추가 스토어 추가
        while ($additional_query->have_posts() && count($all_posts) < 6) {
            $additional_query->the_post();
            $all_posts[] = $additional_query->post;
        }
        
        // 통합된 쿼리 객체 생성
        $related_query = new WP_Query();
        $related_query->posts = $all_posts;
        $related_query->post_count = count($all_posts);
    }
    
    wp_reset_postdata();
}

?>

<?php if ($related_query->have_posts()) : ?>
<div class="container">
    <div class="section-header">
        <h2 class="section-title">
            <span class="section-icon">🎯</span>
            추천 스토어
        </h2>
        <?php if (!empty($category_ids)) : ?>
            <p class="section-subtitle">
                같은 카테고리의 다른 스토어들을 만나보세요
            </p>
        <?php endif; ?>
    </div>
    
    <div class="related-stores-grid">
        <?php 
        $post_count = 0;
        foreach ($related_query->posts as $post) : 
            setup_postdata($post);
            if ($post_count >= 6) break;
            $post_count++;
            
            $store_id = get_the_ID();
            $categories = get_the_terms($store_id, 'store_category');
            $start_date = get_post_meta($store_id, 'start_date', true) ?: get_post_meta($store_id, '_start_date', true);
            $end_date = get_post_meta($store_id, 'end_date', true) ?: get_post_meta($store_id, '_end_date', true);
            $featured = get_post_meta($store_id, 'featured', true) ?: get_post_meta($store_id, '_featured', true);
            
            // 운영 상태 확인
            $current_date = date('Y-m-d');
            $is_active = false;
            $status_text = '';
            
            if ($start_date && $end_date) {
                $is_active = ($current_date >= $start_date && $current_date <= $end_date);
                if ($is_active) {
                    $end_date_obj = new DateTime($end_date);
                    $today_obj = new DateTime();
                    $diff = $today_obj->diff($end_date_obj);
                    $status_text = 'D-' . $diff->days;
                } else if ($current_date < $start_date) {
                    $status_text = '오픈 예정';
                } else {
                    $status_text = '운영 종료';
                }
            }
        ?>
            <article class="related-store-card">
                <a href="<?php the_permalink(); ?>" class="store-card-link">
                    
                    <!-- 스토어 이미지 -->
                    <div class="store-card-image">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('medium', ['class' => 'card-thumbnail']); ?>
                        <?php else : ?>
                            <div class="card-placeholder">
                                <span class="placeholder-icon">🏪</span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- 상태 배지 -->
                        <?php if ($status_text) : ?>
                            <div class="status-badge <?php echo $is_active ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo esc_html($status_text); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- 인기 배지 -->
                        <?php if ($featured) : ?>
                            <div class="featured-badge">
                                <span class="featured-icon">⭐</span>
                                <span class="featured-text">인기</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 스토어 정보 -->
                    <div class="store-card-content">
                        <h3 class="store-card-title"><?php the_title(); ?></h3>
                        
                        <!-- 카테고리 -->
                        <?php if ($categories && !is_wp_error($categories)) : ?>
                            <div class="store-card-categories">
                                <?php foreach (array_slice($categories, 0, 2) as $category) : ?>
                                    <span class="category-tag"><?php echo esc_html($category->name); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- 요약 정보 -->
                        <div class="store-card-meta">
                            <?php 
                            $excerpt = get_the_excerpt();
                            if ($excerpt) {
                                echo '<p class="store-excerpt">' . esc_html(wp_trim_words($excerpt, 15)) . '</p>';
                            }
                            ?>
                            
                            <!-- 운영 기간 -->
                            <?php if ($start_date && $end_date) : ?>
                                <p class="store-period">
                                    <span class="period-icon">📅</span>
                                    <?php echo date('m.d', strtotime($start_date)); ?> - 
                                    <?php echo date('m.d', strtotime($end_date)); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 호버 오버레이 -->
                    <div class="card-hover-overlay">
                        <span class="view-details">자세히 보기</span>
                    </div>
                    
                </a>
            </article>
        <?php endforeach; ?>
    </div>
    
    <!-- 더 많은 스토어 보기 -->
    <div class="related-stores-footer">
        <a href="<?php echo home_url('/stores/'); ?>" class="view-all-stores-btn">
            <span class="btn-icon">🏪</span>
            <span class="btn-text">모든 스토어 보기</span>
            <span class="btn-arrow">→</span>
        </a>
    </div>
</div>

<?php wp_reset_postdata(); ?>

<?php else : ?>

<!-- 관련 스토어가 없는 경우 -->
<div class="container">
    <div class="section-header">
        <h2 class="section-title">
            <span class="section-icon">🎯</span>
            추천 스토어
        </h2>
    </div>
    
    <div class="no-related-stores">
        <div class="no-related-icon">🏪</div>
        <h3>추천 스토어 준비중</h3>
        <p>더 많은 멋진 스토어들이 곧 추가될 예정입니다.</p>
        
        <div class="explore-options">
            <a href="<?php echo home_url('/stores/'); ?>" class="explore-link">
                <span class="explore-icon">🔍</span>
                <span class="explore-text">모든 스토어 둘러보기</span>
            </a>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- 관련 스토어 카드 인터랙션 스크립트 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 관련 스토어 카드 호버 효과
    const storeCards = document.querySelectorAll('.related-store-card');
    
    storeCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
            this.style.boxShadow = '0 20px 40px rgba(0,0,0,0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
        });
    });
    
    // 레이지 로딩 (Intersection Observer)
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                }
            });
        });
        
        const lazyImages = document.querySelectorAll('img.lazy');
        lazyImages.forEach(img => imageObserver.observe(img));
    }
    
    // 카드 클릭 시 로딩 표시
    const cardLinks = document.querySelectorAll('.store-card-link');
    cardLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // 새 탭이 아닌 경우에만 로딩 표시
            if (!e.ctrlKey && !e.metaKey && !e.shiftKey) {
                const overlay = this.querySelector('.card-hover-overlay');
                if (overlay) {
                    overlay.innerHTML = '<span class="loading-text">로딩중...</span>';
                    overlay.style.opacity = '1';
                }
            }
        });
    });
});
</script>
