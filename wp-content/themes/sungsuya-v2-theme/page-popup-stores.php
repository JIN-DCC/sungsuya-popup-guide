<?php
/**
 * Template Name: 팝업스토어 목록
 * 
 * 팝업스토어 전용 페이지 템플릿
 * 
 * @package SungsuyaV2
 */

get_header(); 

// 팝업스토어만 쿼리
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$popup_args = array(
    'post_type' => 'places',
    'posts_per_page' => 12,
    'paged' => $paged,
    'tax_query' => array(
        array(
            'taxonomy' => 'place_type',
            'field' => 'slug',
            'terms' => array('popup-store', 'popup_store', 'popupstore', '팝업스토어'), // 모든 가능한 변형 포함
            'operator' => 'IN'
        )
    ),
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => 'popup_end_date',
            'value' => date('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE'
        ),
        array(
            'key' => 'popup_end_date',
            'compare' => 'NOT EXISTS'
        )
    ),
    'orderby' => 'date',
    'order' => 'DESC'
);

$popup_query = new WP_Query($popup_args);

// 디버깅 정보
$debug_info = array(
    'query_args' => $popup_args,
    'found_posts' => $popup_query->found_posts,
    'post_count' => $popup_query->post_count
);
?>

<!-- 디버깅 정보 (개발 모드에서만 표시) -->
<?php if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) : ?>
<div style="background: #f0f0f0; padding: 20px; margin: 20px; border: 1px solid #ccc;">
    <h3>디버깅 정보</h3>
    <pre><?php print_r($debug_info); ?></pre>
    <p>전체 팝업스토어 수: <?php echo $popup_query->found_posts; ?>개</p>
</div>
<?php endif; ?>

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
    
    .popup-hero {
        background: linear-gradient(135deg, var(--steel-blue) 0%, var(--industrial-green) 100%);
        padding: 80px 0;
        margin-bottom: 60px;
        position: relative;
        overflow: hidden;
    }
    
    .popup-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    
    .popup-hero-content {
        position: relative;
        z-index: 1;
        text-align: center;
        color: white;
    }
    
    .popup-title {
        font-family: 'Pretendard', sans-serif;
        font-size: 48px;
        font-weight: 800;
        margin-bottom: 16px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }
    
    .popup-subtitle {
        font-size: 20px;
        font-weight: 400;
        opacity: 0.95;
    }
    
    /* 팝업 상태 탭 */
    .popup-tabs {
        background: white;
        padding: 24px;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-bottom: 40px;
        border: 1px solid var(--concrete-grey);
    }
    
    .tab-buttons {
        display: flex;
        gap: 12px;
        justify-content: center;
    }
    
    .tab-btn {
        padding: 12px 24px;
        border-radius: 24px;
        border: 2px solid var(--concrete-grey);
        background: white;
        color: var(--charcoal);
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .tab-btn:hover {
        border-color: var(--steel-blue);
        color: var(--steel-blue);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(91, 124, 153, 0.2);
    }
    
    .tab-btn.active {
        background: var(--steel-blue);
        color: white;
        border-color: var(--steel-blue);
    }
    
    /* 팝업스토어 그리드 */
    .popup-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
        margin-bottom: 60px;
    }
    
    /* 팝업스토어 카드 */
    .popup-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border: 1px solid var(--concrete-grey);
        position: relative;
    }
    
    .popup-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.12);
    }
    
    /* D-Day 배지 */
    .popup-dday {
        position: absolute;
        top: 16px;
        left: 16px;
        background: var(--brick-red);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 14px;
        z-index: 10;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    
    .popup-dday.ending-soon {
        background: #FF6B6B;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .popup-card-image {
        position: relative;
        padding-top: 66.67%;
        overflow: hidden;
        background: var(--concrete-grey);
    }
    
    .popup-card-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .popup-card:hover .popup-card-image img {
        transform: scale(1.05);
    }
    
    .popup-card-content {
        padding: 24px;
    }
    
    .popup-card-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--charcoal);
        margin: 0 0 12px 0;
        line-height: 1.3;
    }
    
    .popup-period {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--steel-blue);
        font-weight: 600;
        margin-bottom: 12px;
    }
    
    .popup-info {
        color: var(--muted-text);
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 16px;
    }
    
    .popup-card-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--steel-blue);
        font-weight: 600;
        font-size: 15px;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .popup-card-link:hover {
        gap: 12px;
        color: var(--steel-blue);
    }
    
    /* 빈 상태 */
    .no-popups {
        text-align: center;
        padding: 80px 20px;
        color: var(--muted-text);
    }
    
    .no-popups-icon {
        font-size: 64px;
        margin-bottom: 16px;
        opacity: 0.5;
    }
    
    .no-popups-text {
        font-size: 18px;
    }
    
    /* 반응형 */
    @media (max-width: 768px) {
        .popup-hero {
            padding: 60px 0;
        }
        
        .popup-title {
            font-size: 32px;
        }
        
        .popup-subtitle {
            font-size: 16px;
        }
        
        .popup-tabs {
            padding: 16px;
        }
        
        .tab-buttons {
            gap: 8px;
        }
        
        .tab-btn {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .popup-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
    }
</style>

<!-- 히어로 섹션 -->
<div class="popup-hero">
    <div class="container mx-auto px-4">
        <div class="popup-hero-content">
            <h1 class="popup-title">성수동 팝업스토어</h1>
            <p class="popup-subtitle">기간 한정! 특별한 경험을 놓치지 마세요</p>
        </div>
    </div>
</div>

<div class="container mx-auto px-4">
    <!-- 상태별 탭 -->
    <div class="popup-tabs">
        <div class="tab-buttons">
            <button onclick="filterPopups('all')" class="tab-btn active">
                전체 팝업
            </button>
            <button onclick="filterPopups('ongoing')" class="tab-btn">
                진행중
            </button>
            <button onclick="filterPopups('upcoming')" class="tab-btn">
                오픈예정
            </button>
            <button onclick="filterPopups('ending-soon')" class="tab-btn">
                곧 종료
            </button>
        </div>
    </div>
    
    <!-- 팝업스토어 그리드 -->
    <div class="popup-grid" id="popup-grid">
        <?php if ($popup_query->have_posts()) : ?>
            <?php while ($popup_query->have_posts()) : $popup_query->the_post(); 
                // 팝업스토어 메타 정보
                $start_date = get_post_meta(get_the_ID(), 'popup_start_date', true);
                $end_date = get_post_meta(get_the_ID(), 'popup_end_date', true);
                $address = get_post_meta(get_the_ID(), 'address', true);
                
                // D-Day 계산
                $today = new DateTime();
                $status = 'ongoing';
                $status_text = '';
                
                if ($end_date) {
                    $end = new DateTime($end_date);
                    $diff = $today->diff($end);
                    $days_left = $diff->invert ? -1 : $diff->days;
                    
                    if ($start_date) {
                        $start = new DateTime($start_date);
                        if ($today < $start) {
                            $status = 'upcoming';
                            $status_text = 'D-' . $today->diff($start)->days;
                        } elseif ($days_left >= 0 && $days_left <= 7) {
                            $status = 'ending-soon';
                            $status_text = 'D-' . $days_left;
                        } elseif ($days_left > 0) {
                            $status = 'ongoing';
                            $status_text = 'D-' . $days_left;
                        } else {
                            continue; // 종료된 팝업은 표시하지 않음
                        }
                    } else {
                        if ($days_left >= 0) {
                            $status_text = 'D-' . $days_left;
                            if ($days_left <= 7) {
                                $status = 'ending-soon';
                            }
                        }
                    }
                } else {
                    $status_text = '진행중';
                }
            ?>
            
            <article class="popup-card" data-status="<?php echo esc_attr($status); ?>">
                <?php if (!empty($status_text)) : ?>
                <!-- D-Day 배지 -->
                <div class="popup-dday <?php echo $status === 'ending-soon' ? 'ending-soon' : ''; ?>">
                    <?php echo esc_html($status_text); ?>
                </div>
                <?php endif; ?>
                
                <!-- 이미지 -->
                <a href="<?php the_permalink(); ?>" class="popup-card-image">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('medium_large', array('loading' => 'lazy')); ?>
                    <?php else : ?>
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 48px; opacity: 0.3;">
                            🏪
                        </div>
                    <?php endif; ?>
                </a>
                
                <!-- 콘텐츠 -->
                <div class="popup-card-content">
                    <h3 class="popup-card-title">
                        <a href="<?php the_permalink(); ?>" style="color: var(--charcoal); text-decoration: none;">
                            <?php the_title(); ?>
                        </a>
                    </h3>
                    
                    <?php if ($start_date || $end_date) : ?>
                    <div class="popup-period">
                        <span>📅</span>
                        <span>
                            <?php 
                            if ($start_date && $end_date) {
                                echo date('n.j', strtotime($start_date)) . ' - ' . date('n.j', strtotime($end_date));
                            } elseif ($end_date) {
                                echo '~ ' . date('n.j', strtotime($end_date));
                            }
                            ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="popup-info">
                        <?php if (!empty($address)) : ?>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <span style="opacity: 0.7;">📍</span>
                                <span><?php echo esc_html($address); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php 
                        $hours = get_post_meta(get_the_ID(), 'opening_hours', true);
                        if (!empty($hours)) : 
                        ?>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="opacity: 0.7;">⏰</span>
                                <span><?php echo esc_html($hours); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <a href="<?php the_permalink(); ?>" class="popup-card-link">
                        자세히 보기 →
                    </a>
                </div>
            </article>
            
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <div class="no-popups" style="grid-column: 1/-1;">
                <div class="no-popups-icon">🏪</div>
                <h3 class="no-popups-text">현재 진행 중인 팝업스토어가 없습니다</h3>
                <p>새로운 팝업스토어 정보를 준비 중입니다.</p>
                
                <?php if (current_user_can('manage_options')) : ?>
                <div style="margin-top: 20px; padding: 20px; background: #fff3cd; border-radius: 8px;">
                    <p><strong>관리자 정보:</strong></p>
                    <ul style="text-align: left; max-width: 400px; margin: 10px auto;">
                        <li>팝업스토어로 분류된 Places가 없습니다.</li>
                        <li>Places 편집 화면에서 '장소 유형'을 '팝업스토어'로 설정하세요.</li>
                        <li>또는 <a href="<?php echo admin_url('edit.php?post_type=places&page=popup-store-taxonomy'); ?>">팝업스토어 분류 관리</a>에서 확인하세요.</li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 페이지네이션 -->
    <?php 
    $big = 999999999;
    $pagination = paginate_links(array(
        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
        'format' => '?paged=%#%',
        'current' => max(1, get_query_var('paged')),
        'total' => $popup_query->max_num_pages,
        'prev_text' => '← 이전',
        'next_text' => '다음 →'
    ));
    
    if ($pagination) : ?>
        <div class="pagination-wrapper">
            <div class="wp-pagenavi">
                <?php echo $pagination; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* 페이지네이션 스타일 */
.pagination-wrapper {
    margin: 60px 0;
    text-align: center;
}

.wp-pagenavi {
    display: inline-flex;
    gap: 8px;
    align-items: center;
}

.wp-pagenavi a,
.wp-pagenavi span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    background: white;
    border: 1px solid var(--concrete-grey);
    border-radius: 8px;
    color: var(--charcoal);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.wp-pagenavi a:hover {
    background: var(--steel-blue);
    color: white;
    border-color: var(--steel-blue);
}

.wp-pagenavi .current {
    background: var(--steel-blue);
    color: white;
    border-color: var(--steel-blue);
}
</style>

<script>
// 팝업 필터 기능
function filterPopups(status) {
    const cards = document.querySelectorAll('.popup-card');
    const buttons = document.querySelectorAll('.tab-btn');
    
    // 버튼 스타일 업데이트
    buttons.forEach(btn => {
        btn.classList.remove('active');
    });
    
    event.target.classList.add('active');
    
    // 카드 필터링
    cards.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
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
    
    // 결과가 없는 경우 처리
    const visibleCards = document.querySelectorAll('.popup-card:not([style*="display: none"])');
    const noResults = document.querySelector('.no-popups');
    
    if (visibleCards.length === 0 && !noResults) {
        const grid = document.getElementById('popup-grid');
        grid.innerHTML += `
            <div class="no-popups" style="grid-column: 1/-1;">
                <div class="no-popups-icon">🔍</div>
                <h3 class="no-popups-text">해당 상태의 팝업스토어가 없습니다</h3>
                <p>다른 탭을 선택해보세요.</p>
            </div>
        `;
    } else if (visibleCards.length > 0 && noResults) {
        noResults.remove();
    }
}

// 페이지 로드 시 애니메이션
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.popup-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.animationDelay = `${index * 0.1}s`;
    });
});
</script>

<?php get_footer(); ?>
