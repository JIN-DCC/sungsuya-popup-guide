<?php
/**
 * SUNGSUYA! - Modern Trendy Homepage
 * 
 * @package Sungsuya
 * @version 8.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); 

// Get real data
$total_places = wp_count_posts('places')->publish;
$recent_places = get_posts([
    'post_type' => 'places',
    'posts_per_page' => 8,
    'orderby' => 'date',
    'order' => 'DESC'
]);

// Get popup stores
$popup_stores = get_posts([
    'post_type' => 'places',
    'posts_per_page' => 4,
    'meta_key' => 'place_type',
    'meta_value' => 'popup_store',
    'orderby' => 'date',
    'order' => 'DESC'
]);

// PWA install guide is already included in footer.php
?>

<style>
/* Hero Section */
.hero {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: var(--dark-bg);
}

/* Force all button text to be white */
.btn,
.btn *,
.btn span,
.btn i,
.btn svg {
    color: #FFFFFF !important;
}

.btn-primary {
    background: var(--primary-gradient) !important;
    color: #FFFFFF !important;
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1) !important;
    color: #FFFFFF !important;
    border: 2px solid rgba(255, 255, 255, 0.3) !important;
}

/* Section labels force white */
.section-label {
    display: inline-block;
    padding: 8px 20px;
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    color: #FFFFFF !important;
    margin-bottom: 16px;
    letter-spacing: 1px;
}

.hero-bg {
    position: absolute;
    inset: 0;
    z-index: 1;
}

.hero-animation {
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.floating-shape {
    position: absolute;
    border-radius: 50%;
    filter: blur(40px);
    opacity: 0.5;
    animation: float 20s infinite ease-in-out;
}

.shape-1 {
    width: 400px;
    height: 400px;
    background: var(--neon-purple);
    top: -200px;
    left: -100px;
    animation-delay: 0s;
}

.shape-2 {
    width: 300px;
    height: 300px;
    background: var(--neon-blue);
    bottom: -150px;
    right: -100px;
    animation-delay: 5s;
}

.shape-3 {
    width: 350px;
    height: 350px;
    background: var(--neon-pink);
    top: 50%;
    left: 70%;
    animation-delay: 10s;
}

.shape-4 {
    width: 250px;
    height: 250px;
    background: var(--neon-yellow);
    top: 20%;
    right: 30%;
    animation-delay: 15s;
}

@keyframes float {
    0%, 100% {
        transform: translate(0, 0) scale(1) rotate(0deg);
    }
    25% {
        transform: translate(50px, -50px) scale(1.1) rotate(90deg);
    }
    50% {
        transform: translate(-30px, 60px) scale(0.9) rotate(180deg);
    }
    75% {
        transform: translate(40px, 20px) scale(1.05) rotate(270deg);
    }
}

.hero-video {
    position: absolute;
    top: 50%;
    left: 50%;
    min-width: 100%;
    min-height: 100%;
    width: auto;
    height: auto;
    transform: translate(-50%, -50%);
    object-fit: cover;
    opacity: 0.3;
}

.hero-gradient {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 20% 80%, var(--neon-purple) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, var(--neon-blue) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, var(--neon-pink) 0%, transparent 50%);
    opacity: 0.2;
    animation: gradientShift 20s ease infinite;
}

@keyframes gradientShift {
    0%, 100% { transform: scale(1) rotate(0deg); }
    33% { transform: scale(1.1) rotate(120deg); }
    66% { transform: scale(0.9) rotate(240deg); }
}

.hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    padding: 0 20px;
    max-width: 1000px;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 30px;
    font-size: 14px;
    font-weight: 700;
    color: #FFFFFF;
    margin-bottom: 32px;
    animation: fadeInUp 0.8s ease;
    letter-spacing: 0.5px;
}

.hero-badge .badge-icon {
    width: 16px;
    height: 16px;
    background: var(--neon-yellow);
    border-radius: 50%;
    animation: pulse 2s ease infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.2); opacity: 0.8; }
}

.hero-title {
    font-family: var(--font-display);
    font-size: clamp(48px, 8vw, 120px);
    line-height: 1;
    letter-spacing: -2px;
    margin-bottom: 24px;
    animation: fadeInUp 0.8s ease 0.2s both;
}

.hero-title .gradient-text {
    background: linear-gradient(135deg, var(--neon-pink) 0%, var(--neon-blue) 50%, var(--neon-purple) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    background-size: 200% 200%;
    animation: gradientText 3s ease infinite;
}

@keyframes gradientText {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.hero-subtitle {
    font-size: clamp(18px, 2vw, 24px);
    color: #FFFFFF;
    opacity: 0.9;
    margin-bottom: 48px;
    line-height: 1.6;
    animation: fadeInUp 0.8s ease 0.4s both;
    font-weight: 400;
}

.hero-actions {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
    animation: fadeInUp 0.8s ease 0.6s both;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 16px 32px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 30px;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary-gradient);
    color: white;
    position: relative;
    overflow: hidden;
}

.btn-primary::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.6s ease, height 0.6s ease;
}

.btn-primary:hover::before {
    width: 300px;
    height: 300px;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: #FFFFFF;
    border: 2px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
    font-weight: 600;
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(255, 255, 255, 0.1);
}

.hero-scroll {
    position: absolute;
    bottom: 40px;
    left: 50%;
    transform: translateX(-50%);
    animation: bounce 2s ease infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateX(-50%) translateY(0); }
    50% { transform: translateX(-50%) translateY(-10px); }
}

/* Features Section */
.features {
    padding: 120px 0;
    background: var(--dark-surface);
    position: relative;
    overflow: hidden;
}

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
}

.section-header {
    text-align: center;
    margin-bottom: 80px;
}

.section-label {
    display: inline-block;
    padding: 8px 20px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    color: #FFFFFF;
    margin-bottom: 16px;
    letter-spacing: 1px;
}

.section-title {
    font-family: var(--font-display);
    font-size: clamp(36px, 5vw, 56px);
    margin-bottom: 16px;
    letter-spacing: -1px;
    color: #FFFFFF;
}

.section-subtitle {
    font-size: 18px;
    color: #FFFFFF;
    opacity: 0.7;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
}

.feature-card {
    background: var(--dark-bg);
    border: 1px solid var(--dark-border);
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--primary-gradient);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
    border-color: transparent;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.feature-card:hover::before {
    transform: scaleX(1);
}

.feature-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.feature-card:hover .feature-icon {
    background: var(--primary-gradient);
    border-color: transparent;
}

.feature-icon svg {
    width: 40px;
    height: 40px;
    stroke: #FFFFFF;
    opacity: 0.7;
    transition: all 0.3s ease;
}

.feature-card:hover .feature-icon svg {
    stroke: white;
    opacity: 1;
}

.feature-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 16px;
    color: #FFFFFF;
}

.feature-description {
    color: #FFFFFF;
    opacity: 0.7;
    line-height: 1.6;
    font-size: 15px;
}

/* Places Section */
.places {
    padding: 120px 0;
    background: var(--dark-bg);
}

.places-tabs {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 60px;
}

.tab-btn {
    padding: 12px 24px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 25px;
    font-size: 15px;
    font-weight: 600;
    color: #FFFFFF;
    opacity: 0.7;
    cursor: pointer;
    transition: all 0.3s ease;
}

.tab-btn.active {
    background: var(--primary-gradient);
    border-color: transparent;
    color: white;
    opacity: 1;
}

.tab-btn:hover:not(.active) {
    border-color: rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.1);
    color: #FFFFFF;
    opacity: 1;
}

.places-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
}

.place-card {
    background: var(--dark-surface);
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
}

.place-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.place-image {
    position: relative;
    height: 240px;
    overflow: hidden;
}

.place-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.place-card:hover .place-image img {
    transform: scale(1.05);
}

.place-badge {
    position: absolute;
    top: 16px;
    left: 16px;
    padding: 6px 16px;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.place-content {
    padding: 24px;
}

.place-name {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 8px;
    color: #FFFFFF;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.place-info {
    display: flex;
    align-items: center;
    gap: 16px;
    color: #FFFFFF;
    opacity: 0.7;
    font-size: 14px;
}

.place-info-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.place-info-item svg {
    width: 16px;
    height: 16px;
}

/* CTA Section */
.cta {
    padding: 120px 0;
    background: var(--dark-surface);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.cta::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, var(--neon-purple) 0%, transparent 70%);
    opacity: 0.1;
    animation: rotate 30s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.cta-content {
    position: relative;
    z-index: 2;
}

.cta-title {
    font-family: var(--font-display);
    font-size: clamp(40px, 5vw, 64px);
    margin-bottom: 24px;
    color: #FFFFFF;
}

.cta-description {
    font-size: 20px;
    color: #FFFFFF;
    opacity: 0.8;
    margin-bottom: 48px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

/* Responsive */
@media (max-width: 1024px) {
    .features-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .places-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .container {
        padding: 0 20px;
    }
    
    .hero-actions {
        flex-direction: column;
        align-items: center;
        width: 100%;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .places-grid {
        grid-template-columns: 1fr;
    }
    
    .places-tabs {
        flex-wrap: wrap;
    }
    
    .features {
        padding: 80px 0;
    }
    
    .places {
        padding: 80px 0;
    }
    
    .cta {
        padding: 80px 0;
    }
}
</style>

<main class="homepage">

<!-- Hero Section -->
<section class="hero">
    <div class="hero-bg">
        <div class="hero-gradient"></div>
        <!-- Animated background instead of video -->
        <div class="hero-animation">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
            <div class="floating-shape shape-4"></div>
        </div>
    </div>
    
    <div class="hero-content">
        <div class="hero-badge">
            <span class="badge-icon"></span>
            <span>서울에서 가장 핫한 동네</span>
        </div>
        
        <h1 class="hero-title">
            <span class="gradient-text">SEONGSU</span><br>
            GUIDE FOR YOU
        </h1>
        
        <p class="hero-subtitle">
            카페, 레스토랑, 팝업스토어까지<br>
            성수동의 모든 트렌디한 공간을 한눈에
        </p>
        
        <div class="hero-actions">
            <a href="<?php echo home_url('/tour-v2'); ?>" class="btn btn-primary">
                <i data-feather="map" style="color: #FFFFFF !important;"></i>
                <span style="color: #FFFFFF !important;">투어 시작하기</span>
            </a>
            <a href="<?php echo home_url('/places'); ?>" class="btn btn-secondary">
                <i data-feather="compass" style="color: #FFFFFF !important;"></i>
                <span style="color: #FFFFFF !important;">장소 둘러보기</span>
            </a>
        </div>
    </div>
    
    <div class="hero-scroll">
        <i data-feather="chevron-down" style="width: 32px; height: 32px; color: var(--muted-text);"></i>
    </div>
</section>

<!-- Features Section -->
<section class="features">
    <div class="container">
        <div class="section-header">
            <span class="section-label">FEATURES</span>
            <h2 class="section-title">성수야!만의 특별함</h2>
            <p class="section-subtitle">
                성수동을 더 스마트하게 즐기는 방법
            </p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i data-feather="map-pin"></i>
                </div>
                <h3 class="feature-title">AI 추천 투어</h3>
                <p class="feature-description">
                    당신의 취향에 맞는 최적의 투어 코스를 AI가 추천해드립니다
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i data-feather="refresh-cw"></i>
                </div>
                <h3 class="feature-title">실시간 업데이트</h3>
                <p class="feature-description">
                    매일 새롭게 업데이트되는 성수동의 최신 정보를 확인하세요
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i data-feather="share-2"></i>
                </div>
                <h3 class="feature-title">간편한 공유</h3>
                <p class="feature-description">
                    친구들과 함께 가고 싶은 장소를 쉽게 공유해보세요
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Places Section -->
<?php if (!empty($recent_places)): ?>
<section class="places">
    <div class="container">
        <div class="section-header">
            <span class="section-label">PLACES</span>
            <h2 class="section-title">최근 추가된 장소</h2>
            <p class="section-subtitle">
                <?php echo $total_places; ?>개의 트렌디한 공간이 당신을 기다립니다
            </p>
        </div>
        
        <div class="places-tabs">
            <button class="tab-btn active" data-type="all">전체</button>
            <button class="tab-btn" data-type="cafe">카페</button>
            <button class="tab-btn" data-type="restaurant">레스토랑</button>
            <button class="tab-btn" data-type="popup-store">팝업스토어</button>
        </div>
        
        <div class="places-grid">
            <?php foreach ($recent_places as $place): 
                // Taxonomy에서 place_type 가져오기 (single-places.php와 동일한 방식)
                $place_types = wp_get_post_terms($place->ID, 'place_type');
                $place_type = 'restaurant'; // 기본값
                $place_type_name = '맛집'; // 기본값
                
                if (!empty($place_types) && !is_wp_error($place_types)) {
                    $term = $place_types[0];
                    $place_type_name = $term->name;
                    $place_type = $term->slug;
                    
                    // slug 정규화 (single-places.php와 동일)
                    $place_type = str_replace('_', '-', $place_type);
                }
                
                $address = get_post_meta($place->ID, 'address', true);
                $image_url = get_the_post_thumbnail_url($place->ID, 'medium');
                
                // 수정된 타입 라벨 매핑 (taxonomy term names 사용)
                $type_labels = [
                    'cafe' => '카페',
                    'restaurant' => '맛집',
                    'popup-store' => '팝업스토어',
                    'popup_store' => '팝업스토어',
                    'gallery' => '갤러리',
                    'shop' => '샵',
                    'bar' => '바'
                ];
                
                // 실제 taxonomy term name이 있으면 그것을 사용, 없으면 매핑 테이블 사용
                $type_label = !empty($place_type_name) ? $place_type_name : ($type_labels[$place_type] ?? '장소');
            ?>
            <a href="<?php echo get_permalink($place->ID); ?>" class="place-card" data-type="<?php echo esc_attr($place_type); ?>">
                <div class="place-image">
                    <?php if ($image_url): ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($place->post_title); ?>">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; background: var(--dark-border); display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 48px; color: var(--muted-text); opacity: 0.3;">S</span>
                        </div>
                    <?php endif; ?>
                    <span class="place-badge"><?php echo esc_html($type_label); ?></span>
                </div>
                <div class="place-content">
                    <h3 class="place-name"><?php echo esc_html($place->post_title); ?></h3>
                    <div class="place-info">
                        <?php if ($address): ?>
                        <div class="place-info-item">
                            <i data-feather="map-pin"></i>
                            <span><?php echo esc_html($address); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 60px;">
            <a href="<?php echo home_url('/places'); ?>" class="btn btn-secondary">
                <span style="color: #FFFFFF !important;">모든 장소 보기</span>
                <i data-feather="arrow-right" style="color: #FFFFFF !important;"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<section class="cta">
    <div class="cta-content">
        <h2 class="cta-title">
            지금 바로 <span class="gradient-text">성수동 여행</span>을<br>
            시작해보세요
        </h2>
        <p class="cta-description">
            나만의 성수동 투어 코스를 만들고 친구들과 공유해보세요
        </p>
        <a href="<?php echo home_url('/tour-v2'); ?>" class="btn btn-primary">
            <i data-feather="play-circle" style="color: #FFFFFF !important;"></i>
            <span style="color: #FFFFFF !important;">투어플래너 시작하기</span>
        </a>
    </div>
</section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Feather icons
    feather.replace();
    
    // Tab functionality
    const tabs = document.querySelectorAll('.tab-btn');
    const places = document.querySelectorAll('.place-card');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const type = tab.dataset.type;
            
            // Update active tab
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            // Filter places
            places.forEach(place => {
                if (type === 'all' || place.dataset.type === type) {
                    place.style.display = 'block';
                } else {
                    place.style.display = 'none';
                }
            });
        });
    });
    
    // Smooth scroll
    document.querySelector('.hero-scroll')?.addEventListener('click', () => {
        document.querySelector('.features').scrollIntoView({ behavior: 'smooth' });
    });
});
</script>

<?php get_footer(); ?>