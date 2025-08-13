<?php
/**
 * 404 Error Page
 * 
 * 성수야! 404 에러 페이지 - Industrial Heritage 디자인
 */

get_header();
?>

<style>
/* Industrial Heritage 디자인 시스템 */
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
}

.error-404-container {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    background: var(--warm-white);
}

.error-content {
    text-align: center;
    max-width: 600px;
}

.error-icon {
    font-size: 120px;
    color: var(--brick-red);
    margin-bottom: 30px;
    opacity: 0.8;
}

.error-number {
    font-family: 'Pretendard', sans-serif;
    font-size: 120px;
    font-weight: 800;
    color: var(--charcoal);
    margin: 0;
    line-height: 1;
}

.error-message {
    font-family: 'Pretendard', sans-serif;
    font-size: 32px;
    color: var(--charcoal);
    margin: 20px 0;
    font-weight: 700;
}

.error-description {
    font-size: 18px;
    color: var(--muted-text);
    margin-bottom: 40px;
    line-height: 1.6;
}

.error-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-home {
    background-color: var(--brick-red);
    color: white;
    padding: 14px 32px;
    border-radius: 28px;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-home:hover {
    background-color: #A04A46;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(184, 84, 80, 0.3);
}

.btn-secondary {
    background-color: white;
    color: var(--brick-red);
    padding: 14px 32px;
    border-radius: 28px;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
    border: 2px solid var(--brick-red);
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-secondary:hover {
    background-color: var(--brick-red);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(184, 84, 80, 0.3);
}

.suggestions {
    margin-top: 60px;
}

.suggestions h3 {
    font-family: 'Pretendard', sans-serif;
    font-size: 20px;
    color: var(--charcoal);
    margin-bottom: 20px;
    font-weight: 600;
}

.tag-cloud {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.tag-item {
    background: white;
    color: var(--charcoal);
    padding: 8px 20px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 14px;
    border: 1px solid var(--concrete-grey);
    transition: all 0.3s;
    font-weight: 500;
}

.tag-item:hover {
    background: var(--brick-red);
    color: white;
    border-color: var(--brick-red);
    transform: translateY(-2px);
}

/* 애니메이션 */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.error-content > * {
    animation: fadeInUp 0.6s ease forwards;
}

.error-icon { animation-delay: 0.1s; }
.error-number { animation-delay: 0.2s; }
.error-message { animation-delay: 0.3s; }
.error-description { animation-delay: 0.4s; }
.error-actions { animation-delay: 0.5s; }
.suggestions { animation-delay: 0.6s; }
</style>

<div class="error-404-container">
    <div class="error-content">
        <!-- 404 아이콘 -->
        <div class="error-icon">
            🗺️
        </div>
        
        <!-- 에러 번호 -->
        <h1 class="error-number">404</h1>
        
        <!-- 에러 메시지 -->
        <h2 class="error-message">길을 잃으셨나요?</h2>
        
        <p class="error-description">
            요청하신 페이지를 찾을 수 없습니다.<br>
            성수동의 숨은 명소를 찾는 것처럼,<br>
            이 페이지도 아직 발견되지 않았나봐요!
        </p>
        
        <!-- 액션 버튼들 -->
        <div class="error-actions">
            <a href="<?php echo home_url(); ?>" class="btn-home">
                <span>🏠</span> 홈으로 가기
            </a>
            <a href="<?php echo home_url('/places'); ?>" class="btn-secondary">
                <span>📍</span> 장소 둘러보기
            </a>
            <a href="<?php echo home_url('/tour-v2'); ?>" class="btn-secondary">
                <span>🗺️</span> 투어 계획하기
            </a>
        </div>
        
        <!-- 추천 검색어 -->
        <div class="suggestions">
            <h3>인기 검색어</h3>
            <div class="tag-cloud">
                <?php
                $popular_tags = array('카페', '맛집', '팝업스토어', '서울숲', '브런치', '루프탑', '데이트', '인스타');
                foreach ($popular_tags as $tag) :
                ?>
                <a href="<?php echo home_url('/places?search=' . urlencode($tag)); ?>" class="tag-item">
                    #<?php echo $tag; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
