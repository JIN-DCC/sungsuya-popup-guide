<?php
/**
 * 성수야! - Places 아카이브 페이지
 * 
 * 장소 목록 표시 페이지 (places + popup_store 통합 표시)
 * 
 * @package SungsuyaV2
 * @version 2.2.1
 * @updated 2025-08-12 (글로벌 페이지네이션 스타일 적용)
 */

get_header();

// 통합 쿼리: places와 popup_store 두 포스트 타입 모두 가져오기
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$combined_query = new WP_Query(array(
    'post_type' => array('places', 'popup_store'),
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'paged' => $paged,
    'meta_query' => array(
        'relation' => 'AND',
    ),
    'orderby' => 'date',
    'order' => 'DESC'
));
?>

<style>
    /* Industrial Heritage 디자인 시스템 적용 */
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
    
    body {
        background: var(--warm-white) !important;
        color: var(--charcoal) !important;
    }
    
    .places-hero {
        background: linear-gradient(135deg, var(--brick-red) 0%, var(--cafe-latte) 100%);
        padding: 80px 0;
        margin-bottom: 60px;
        position: relative;
        overflow: hidden;
    }
    
    .places-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    
    .places-hero-content {
        position: relative;
        z-index: 1;
        text-align: center;
        color: white;
    }
    
    .places-title {
        font-family: 'Pretendard', sans-serif;
        font-size: 48px;
        font-weight: 800;
        margin-bottom: 16px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }
    
    .places-subtitle {
        font-size: 20px;
        font-weight: 400;
        opacity: 0.95;
    }
    
    /* 필터 섹션 */
    .filter-section {
        background: white;
        padding: 24px;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-bottom: 40px;
        border: 1px solid var(--concrete-grey);
    }
    
    .filter-buttons {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .filter-btn {
        padding: 12px 24px;
        border-radius: 24px;
        border: 2px solid var(--concrete-grey);
        background: white;
        color: var(--charcoal);
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .filter-btn:hover {
        border-color: var(--brick-red);
        color: var(--brick-red);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(184, 84, 80, 0.2);
    }
    
    .filter-btn.active {
        background: var(--brick-red);
        color: white;
        border-color: var(--brick-red);
    }
    
    /* 장소 그리드 */
    .places-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
        margin-bottom: 60px;
    }
    
    /* 장소 카드 */
    .place-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border: 1px solid var(--concrete-grey);
    }
    
    .place-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.12);
    }
    
    .place-card-image {
        position: relative;
        padding-top: 66.67%;
        overflow: hidden;
        background: var(--concrete-grey);
    }
    
    .place-card-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .place-card:hover .place-card-image img {
        transform: scale(1.05);
    }
    
    .place-card-content {
        padding: 24px;
    }
    
    .place-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }
    
    .place-card-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--charcoal);
        margin: 0;
        line-height: 1.3;
    }
    
    .place-card-type {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        background: var(--concrete-grey);
        color: var(--charcoal);
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
    }
    
    .place-card-info {
        color: var(--muted-text);
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 16px;
    }
    
    .place-card-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--brick-red);
        font-weight: 600;
        font-size: 15px;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .place-card-link:hover {
        gap: 12px;
        color: var(--brick-red);
    }
    
    /* 빈 상태 */
    .no-results {
        text-align: center;
        padding: 80px 20px;
        color: var(--muted-text);
    }
    
    .no-results-icon {
        font-size: 64px;
        margin-bottom: 16px;
        opacity: 0.5;
    }
    
    .no-results-text {
        font-size: 18px;
    }
    
    /* 로딩 애니메이션 */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .place-card {
        animation: fadeIn 0.5s ease forwards;
    }

    .place-card:nth-child(1) { animation-delay: 0.1s; }
    .place-card:nth-child(2) { animation-delay: 0.2s; }
    .place-card:nth-child(3) { animation-delay: 0.3s; }
    .place-card:nth-child(4) { animation-delay: 0.4s; }
    .place-card:nth-child(5) { animation-delay: 0.5s; }
    .place-card:nth-child(6) { animation-delay: 0.6s; }
    
    /* 반응형 */
    @media (max-width: 768px) {
        .places-hero {
            padding: 60px 0;
        }
        
        .places-title {
            font-size: 32px;
        }
        
        .places-subtitle {
            font-size: 16px;
        }
        
        .filter-section {
            padding: 16px;
        }
        
        .filter-buttons {
            gap: 8px;
        }
        
        .filter-btn {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .places-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
    }
</style>

<!-- 히어로 섹션 -->
<div class="places-hero">
    <div class="container mx-auto px-4">
        <div class="places-hero-content">
            <h1 class="places-title">성수동 장소 가이드</h1>
            <p class="places-subtitle">성수동의 다양한 공간들을 만나보세요</p>
        </div>
    </div>
</div>

<div class="container mx-auto px-4">
    <!-- 필터 섹션 -->
    <div class="filter-section">
        <div class="filter-buttons">
            <button onclick="filterPlaces('all')" 
                    class="filter-btn active">
                <span>🏛️</span>
                <span>전체</span>
            </button>
            <?php 
            // 동적으로 장소유형 로드
            $place_types = get_terms(array(
                'taxonomy' => 'place_type',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC'
            ));
            
            foreach ($place_types as $type) :
                $metafield_type = get_term_meta($type->term_id, 'metafield_type', true);
                
                // 아이콘 결정
                $icon = '📍'; // 기본 아이콘
                if ($metafield_type === 'food') {
                    $icon = '🍽️';
                } elseif ($metafield_type === 'shop') {
                    $icon = '🛍️';
                } elseif ($type->slug === 'popup_store' || $type->slug === 'popup-store') {
                    $icon = '🏪';
                } elseif ($type->slug === 'facility') {
                    $icon = '🚻';
                }
            ?>
            <button onclick="filterPlaces('<?php echo esc_attr($type->slug); ?>')" 
                    class="filter-btn">
                <span><?php echo $icon; ?></span>
                <span><?php echo esc_html($type->name); ?></span>
            </button>
            <?php endforeach; ?>
            
            <!-- 팝업스토어 필터 추가 (popup_store 포스트 타입을 위해) -->
            <button onclick="filterPlaces('popup-store')" 
                    class="filter-btn">
                <span>🏪</span>
                <span>팝업스토어</span>
            </button>
        </div>
    </div>
    
    <!-- 장소 그리드 -->
    <div class="places-grid" id="places-grid">
        <?php if ($combined_query->have_posts()) : ?>
            <?php while ($combined_query->have_posts()) : $combined_query->the_post(); 
                $place_meta = sungsuya_get_place_meta(get_the_ID());
                
                // 포스트 타입에 따른 장소 유형 처리
                $current_post_type = get_post_type();
                
                if ($current_post_type === 'popup_store') {
                    // popup_store 포스트 타입의 경우
                    $place_type = (object) array(
                        'slug' => 'popup-store',
                        'name' => '팝업스토어',
                        'term_id' => null
                    );
                    $place_type_slug = 'popup-store';
                } else {
                    // places 포스트 타입의 경우
                    $place_types = wp_get_post_terms(get_the_ID(), 'place_type');
                    $place_type = !empty($place_types) ? $place_types[0] : null;
                    $place_type_slug = $place_type ? $place_type->slug : 'place';
                }
                
                // 타입별 아이콘 결정
                $icon = '📍';
                if ($place_type) {
                    if ($current_post_type === 'popup_store' || $place_type->slug === 'popup_store' || $place_type->slug === 'popup-store') {
                        $icon = '🏪'; // 팝업스토어
                    } elseif ($place_type->term_id) {
                        $metafield_type = get_term_meta($place_type->term_id, 'metafield_type', true);
                        if ($metafield_type === 'food') {
                            $icon = '🍽️';
                        } elseif ($metafield_type === 'shop') {
                            $icon = '🛍️';
                        } elseif ($place_type->slug === 'facility') {
                            $icon = '🚻';
                        }
                    }
                }
            ?>
            
            <article class="place-card" data-type="<?php echo esc_attr($place_type_slug); ?>">
                <!-- 이미지 -->
                <a href="<?php the_permalink(); ?>" class="place-card-image">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('medium_large', array('loading' => 'lazy')); ?>
                    <?php else : ?>
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 48px; opacity: 0.3;">
                            <?php echo $icon; ?>
                        </div>
                    <?php endif; ?>
                </a>
                
                <!-- 콘텐츠 -->
                <div class="place-card-content">
                    <div class="place-card-header">
                        <h3 class="place-card-title">
                            <a href="<?php the_permalink(); ?>" style="color: var(--charcoal); text-decoration: none;">
                                <?php the_title(); ?>
                            </a>
                        </h3>
                        <?php if ($place_type) : ?>
                        <span class="place-card-type">
                            <?php echo $icon; ?> <?php echo esc_html($place_type->name); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="place-card-info">
                        <?php if (!empty($place_meta['common']['address'])) : ?>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <span style="opacity: 0.7;">📍</span>
                                <span><?php echo esc_html($place_meta['common']['address']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($place_meta['common']['hours'])) : ?>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="opacity: 0.7;">⏰</span>
                                <span><?php echo esc_html($place_meta['common']['hours']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($place_meta['common']['phone'])) : ?>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="opacity: 0.7;">📞</span>
                                <span><?php echo esc_html($place_meta['common']['phone']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <a href="<?php the_permalink(); ?>" class="place-card-link">
                        자세히 보기 →
                    </a>
                </div>
            </article>
            
            <?php endwhile; ?>
            <?php wp_reset_postdata(); // 커스텀 쿼리 후 전역 변수 초기화 ?>
        <?php else : ?>
            <div class="no-results">
                <div class="no-results-icon">🏢</div>
                <h3 class="no-results-text">등록된 장소가 없습니다</h3>
                <p>새로운 장소가 곧 추가될 예정입니다.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 페이지네이션 (글로벌 스타일 사용) -->
    <?php if (function_exists('wp_pagenavi')) : ?>
        <div class="pagination-wrapper">
            <?php wp_pagenavi(array('query' => $combined_query)); ?>
        </div>
    <?php else : ?>
        <div class="pagination-wrapper">
            <div class="pagination-nav">
                <?php 
                echo paginate_links(array(
                    'total' => $combined_query->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                    'prev_text' => '← 이전 페이지',
                    'next_text' => '다음 페이지 →',
                    'type' => 'list'
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// 필터 기능 (팝업스토어 타입 매칭 개선)
function filterPlaces(type) {
    const cards = document.querySelectorAll('.place-card');
    const buttons = document.querySelectorAll('.filter-btn');
    
    // 버튼 스타일 업데이트
    buttons.forEach(btn => {
        btn.classList.remove('active');
    });
    
    event.target.classList.add('active');
    
    // 카드 필터링 (팝업스토어 타입 통합 처리)
    cards.forEach(card => {
        const cardType = card.dataset.type;
        let shouldShow = false;
        
        if (type === 'all') {
            shouldShow = true;
        } else if (type === 'popup-store' || type === 'popup_store') {
            // 팝업스토어 관련 타입은 모두 매칭
            shouldShow = (cardType === 'popup-store' || cardType === 'popup_store');
        } else {
            shouldShow = (cardType === type);
        }
        
        if (shouldShow) {
            card.style.display = 'block';
            // 애니메이션 재실행
            card.style.animation = 'none';
            setTimeout(() => {
                card.style.animation = 'fadeIn 0.5s ease forwards';
            }, 10);
        } else {
            card.style.display = 'none';
        }
    });
    
    // 결과가 없는 경우 메시지 표시
    const visibleCards = document.querySelectorAll('.place-card[style="display: block;"]');
    const noResults = document.querySelector('.no-results');
    
    if (visibleCards.length === 0 && !noResults) {
        const grid = document.getElementById('places-grid');
        grid.innerHTML += `
            <div class="no-results">
                <div class="no-results-icon">🔍</div>
                <h3 class="no-results-text">해당 카테고리에 장소가 없습니다</h3>
                <p>다른 카테고리를 선택해보세요.</p>
            </div>
        `;
    } else if (visibleCards.length > 0 && noResults) {
        noResults.remove();
    }
}

// 페이지 로드 시 애니메이션 초기화
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.place-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.animationDelay = `${index * 0.1}s`;
    });
});
</script>

<?php get_footer(); ?>