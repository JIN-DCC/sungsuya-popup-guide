<?php
/**
 * Template Name: 회사소개
 * 
 * 성수야! 회사소개 페이지
 */

get_header();
?>

<div class="about-container" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
    
    <!-- 히어로 섹션 -->
    <div class="hero-section" style="text-align: center; padding: 60px 0; background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); color: white; border-radius: 20px; margin-bottom: 60px;">
        <h1 style="font-size: 48px; margin-bottom: 20px; font-weight: bold;">성수야!</h1>
        <p style="font-size: 24px; opacity: 0.9;">성수동의 모든 것을 담은 로컬 가이드 플랫폼</p>
    </div>
    
    <!-- 소개 섹션 -->
    <div class="intro-section" style="text-align: center; margin-bottom: 80px;">
        <h2 style="font-size: 36px; color: #333; margin-bottom: 30px;">우리가 만드는 성수동 이야기</h2>
        <p style="font-size: 18px; line-height: 1.8; color: #666; max-width: 800px; margin: 0 auto;">
            성수야!는 서울의 핫플레이스 성수동을 더 쉽고 재미있게 탐험할 수 있도록 돕는 플랫폼입니다.<br>
            카페, 맛집, 팝업스토어, 문화공간 등 성수동의 모든 정보를 한곳에서 만나보세요.<br>
            AI 기반 투어플래너로 나만의 성수동 여행 코스를 계획해보세요.
        </p>
    </div>
    
    <!-- 미션 & 비전 -->
    <div class="mission-vision" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px; margin-bottom: 80px;">
        <div class="mission-box" style="background: #f8f9fa; padding: 40px; border-radius: 15px; text-align: center;">
            <div style="font-size: 48px; color: #4CAF50; margin-bottom: 20px;">
                <i class="fas fa-bullseye"></i>
            </div>
            <h3 style="font-size: 24px; margin-bottom: 15px; color: #333;">미션</h3>
            <p style="font-size: 16px; line-height: 1.6; color: #666;">
                성수동의 숨은 매력을 발견하고,<br>
                방문객들에게 최고의 경험을 제공하여<br>
                지역 상권과 문화를 활성화합니다.
            </p>
        </div>
        
        <div class="vision-box" style="background: #f8f9fa; padding: 40px; border-radius: 15px; text-align: center;">
            <div style="font-size: 48px; color: #4CAF50; margin-bottom: 20px;">
                <i class="fas fa-eye"></i>
            </div>
            <h3 style="font-size: 24px; margin-bottom: 15px; color: #333;">비전</h3>
            <p style="font-size: 16px; line-height: 1.6; color: #666;">
                대한민국 모든 지역의 특색을<br>
                가장 잘 전달하는 로컬 가이드<br>
                플랫폼으로 성장합니다.
            </p>
        </div>
    </div>
    
    <!-- 핵심 가치 -->
    <div class="core-values" style="margin-bottom: 80px;">
        <h2 style="font-size: 36px; text-align: center; color: #333; margin-bottom: 50px;">핵심 가치</h2>
        <div class="values-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
            
            <div class="value-item" style="text-align: center;">
                <div style="width: 100px; height: 100px; background: #4CAF50; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-search-location" style="font-size: 40px; color: white;"></i>
                </div>
                <h4 style="font-size: 20px; margin-bottom: 10px;">정확한 정보</h4>
                <p style="font-size: 14px; color: #666;">최신 정보를 실시간으로 업데이트하여<br>항상 정확한 정보를 제공합니다.</p>
            </div>
            
            <div class="value-item" style="text-align: center;">
                <div style="width: 100px; height: 100px; background: #4CAF50; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-heart" style="font-size: 40px; color: white;"></i>
                </div>
                <h4 style="font-size: 20px; margin-bottom: 10px;">사용자 중심</h4>
                <p style="font-size: 14px; color: #666;">방문객의 니즈를 최우선으로<br>편리한 서비스를 제공합니다.</p>
            </div>
            
            <div class="value-item" style="text-align: center;">
                <div style="width: 100px; height: 100px; background: #4CAF50; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-handshake" style="font-size: 40px; color: white;"></i>
                </div>
                <h4 style="font-size: 20px; margin-bottom: 10px;">상생 발전</h4>
                <p style="font-size: 14px; color: #666;">지역 상인들과 함께 성장하며<br>성수동의 발전에 기여합니다.</p>
            </div>
            
            <div class="value-item" style="text-align: center;">
                <div style="width: 100px; height: 100px; background: #4CAF50; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-lightbulb" style="font-size: 40px; color: white;"></i>
                </div>
                <h4 style="font-size: 20px; margin-bottom: 10px;">혁신 기술</h4>
                <p style="font-size: 14px; color: #666;">AI와 최신 기술을 활용하여<br>새로운 경험을 제공합니다.</p>
            </div>
        </div>
    </div>
    
    <!-- 주요 기능 -->
    <div class="features-section" style="background: #f8f9fa; padding: 60px; border-radius: 20px; margin-bottom: 80px;">
        <h2 style="font-size: 36px; text-align: center; color: #333; margin-bottom: 50px;">주요 서비스</h2>
        
        <div class="features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px;">
            
            <div class="feature-card" style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                <div style="font-size: 36px; color: #4CAF50; margin-bottom: 20px;">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h3 style="font-size: 22px; margin-bottom: 15px;">스마트 투어플래너</h3>
                <p style="font-size: 16px; line-height: 1.6; color: #666;">
                    AI 기반 추천 시스템으로 개인 맞춤형 투어 코스를 제안합니다. 
                    드래그 앤 드롭으로 쉽게 일정을 조정하고 친구들과 공유하세요.
                </p>
            </div>
            
            <div class="feature-card" style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                <div style="font-size: 36px; color: #4CAF50; margin-bottom: 20px;">
                    <i class="fas fa-store"></i>
                </div>
                <h3 style="font-size: 22px; margin-bottom: 15px;">실시간 장소 정보</h3>
                <p style="font-size: 16px; line-height: 1.6; color: #666;">
                    영업시간, 메뉴, 가격 등 최신 정보를 실시간으로 업데이트합니다. 
                    팝업스토어와 이벤트 정보도 놓치지 마세요.
                </p>
            </div>
            
            <div class="feature-card" style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                <div style="font-size: 36px; color: #4CAF50; margin-bottom: 20px;">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3 style="font-size: 22px; margin-bottom: 15px;">오프라인 지원</h3>
                <p style="font-size: 16px; line-height: 1.6; color: #666;">
                    PWA 기술로 오프라인에서도 사용 가능합니다. 
                    해외 방문객도 데이터 걱정 없이 성수동을 탐험하세요.
                </p>
            </div>
            
        </div>
    </div>
    
    <!-- CTA 섹션 -->
    <div class="cta-section" style="background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); padding: 60px; border-radius: 20px; text-align: center; color: white;">
        <h2 style="font-size: 36px; margin-bottom: 20px;">성수동 탐험을 시작하세요!</h2>
        <p style="font-size: 18px; margin-bottom: 30px; opacity: 0.9;">
            지금 바로 나만의 성수동 투어를 계획해보세요
        </p>
        <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo home_url('/tour-v2'); ?>" style="background: white; color: #4CAF50; padding: 15px 40px; border-radius: 30px; text-decoration: none; font-size: 18px; font-weight: bold; transition: all 0.3s;">
                투어 시작하기
            </a>
            <a href="<?php echo home_url('/contact'); ?>" style="background: transparent; color: white; padding: 15px 40px; border-radius: 30px; text-decoration: none; font-size: 18px; font-weight: bold; border: 2px solid white; transition: all 0.3s;">
                문의하기
            </a>
        </div>
    </div>
    
</div>

<style>
/* 애니메이션 */
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

.hero-section, .intro-section, .mission-vision, .core-values, .features-section, .cta-section {
    animation: fadeInUp 0.8s ease-out;
}

/* 호버 효과 */
.value-item:hover > div:first-child {
    transform: scale(1.1);
    transition: transform 0.3s;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    transition: all 0.3s;
}

.cta-section a:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

/* 반응형 */
@media (max-width: 768px) {
    .hero-section h1 {
        font-size: 36px !important;
    }
    
    .hero-section p {
        font-size: 18px !important;
    }
    
    h2 {
        font-size: 28px !important;
    }
    
    .features-section {
        padding: 40px 20px !important;
    }
}
</style>

<?php
get_footer();
?>
