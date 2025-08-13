<?php
/**
 * Industrial Heritage Footer Design
 */
?>

<style>
/* Industrial Heritage 푸터 스타일 */
.site-footer {
    background: white;
    border-top: 1px solid var(--concrete-grey, #E8E6E1);
    color: var(--charcoal, #2C2C2C);
    padding: 60px 0 40px;
    margin-top: 80px;
    font-family: 'Pretendard', sans-serif;
}

.footer-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
}

.footer-top {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr;
    gap: 60px;
    margin-bottom: 40px;
}

.footer-brand {
    max-width: 320px;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.logo-icon {
    width: 48px;
    height: 48px;
    background: var(--brick-red, #B85450);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: 800;
}

.logo-icon::before {
    content: "S";
}

.logo-text {
    font-size: 24px;
    font-weight: 800;
    color: var(--charcoal, #2C2C2C);
    letter-spacing: -0.5px;
}

.logo-tagline {
    font-size: 14px;
    color: var(--muted-text, #666666);
    font-weight: 500;
}

.footer-description {
    font-size: 15px;
    line-height: 1.7;
    color: var(--muted-text, #666666);
    margin-bottom: 24px;
}

.social-links {
    display: flex;
    gap: 12px;
}

.social-link {
    width: 40px;
    height: 40px;
    background: var(--warm-white, #FAFAF8);
    border: 1px solid var(--concrete-grey, #E8E6E1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--charcoal, #2C2C2C);
    transition: all 0.3s ease;
}

.social-link:hover {
    background: var(--brick-red, #B85450);
    color: white;
    border-color: var(--brick-red, #B85450);
    transform: translateY(-2px);
}

.footer-links {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
}

.footer-column h3 {
    font-size: 16px;
    font-weight: 700;
    color: var(--charcoal, #2C2C2C);
    margin-bottom: 16px;
}

.footer-nav {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-nav li {
    margin-bottom: 12px;
}

.footer-nav a {
    color: var(--muted-text, #666666);
    text-decoration: none;
    font-size: 15px;
    transition: all 0.3s ease;
    display: inline-block;
}

.footer-nav a:hover {
    color: var(--brick-red, #B85450);
    transform: translateX(4px);
}

.footer-app {
    text-align: right;
}

.footer-app h3 {
    font-size: 16px;
    font-weight: 700;
    color: var(--charcoal, #2C2C2C);
    margin-bottom: 16px;
}

.app-buttons {
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-items: flex-end;
}

.app-button {
    background: var(--charcoal, #2C2C2C);
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.app-button:hover {
    background: var(--brick-red, #B85450);
    transform: translateY(-2px);
}

.footer-bottom {
    padding-top: 32px;
    border-top: 1px solid var(--concrete-grey, #E8E6E1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.copyright {
    color: var(--muted-text, #666666);
    font-size: 14px;
}

.footer-links-bottom {
    display: flex;
    gap: 24px;
}

.footer-links-bottom a {
    color: var(--muted-text, #666666);
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s ease;
}

.footer-links-bottom a:hover {
    color: var(--brick-red, #B85450);
}

/* 반응형 */
@media (max-width: 1024px) {
    .footer-top {
        grid-template-columns: 1fr 1fr;
        gap: 40px;
    }
    
    .footer-app {
        grid-column: 1 / -1;
        text-align: left;
    }
    
    .app-buttons {
        flex-direction: row;
        align-items: center;
    }
}

@media (max-width: 768px) {
    .site-footer {
        padding: 40px 0 30px;
        margin-top: 60px;
    }
    
    .footer-top {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .footer-links {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .footer-bottom {
        flex-direction: column;
        text-align: center;
    }
    
    .footer-links-bottom {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<footer class="site-footer">
    <div class="footer-container">
        <!-- Footer Top -->
        <div class="footer-top">
            <div class="footer-brand">
                <div class="footer-logo">
                    <div class="logo-icon"></div>
                    <div>
                        <div class="logo-text">성수야!</div>
                        <div class="logo-tagline">성수동의 모든 것</div>
                    </div>
                </div>
                <p class="footer-description">
                    옛 공장과 창고가 문화공간으로 재탄생한<br>
                    성수동의 특별한 이야기를 전합니다.
                </p>
                
                <!-- Social Links -->
                <div class="social-links">
                    <a href="#" class="social-link" aria-label="Instagram">
                        <i data-feather="instagram"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="Facebook">
                        <i data-feather="facebook"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="Twitter">
                        <i data-feather="twitter"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="YouTube">
                        <i data-feather="youtube"></i>
                    </a>
                </div>
            </div>
            
            <!-- Footer Links -->
            <div class="footer-links">
                <div class="footer-column">
                    <h3 class="footer-title">서비스</h3>
                    <ul class="footer-nav">
                        <li><a href="<?php echo home_url('/places'); ?>">장소 탐색</a></li>
                        <li><a href="<?php echo home_url('/tour-v2'); ?>">투어 플래너</a></li>
                        <li><a href="<?php echo home_url('/places?type=popup_store'); ?>">팝업스토어</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3 class="footer-title">정보</h3>
                    <ul class="footer-nav">
                        <li><a href="<?php echo home_url('/about'); ?>">서비스 소개</a></li>
                        <li><a href="<?php echo home_url('/contact'); ?>">문의하기</a></li>
                        <li><a href="<?php echo home_url('/privacy'); ?>">개인정보처리방침</a></li>
                        <li><a href="<?php echo home_url('/terms'); ?>">이용약관</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3 class="footer-title">연락처</h3>
                    <ul class="footer-nav">
                        <li><a href="mailto:dcclab2022@gmail.com">dcclab2022@gmail.com</a></li>
                    </ul>
                    <h3 class="footer-title" style="margin-top: 24px;">뉴스레터</h3>
                    <p class="newsletter-text">
                        성수동의 최신 소식을 받아보세요
                    </p>
                    <form class="newsletter-form" id="newsletter-form">
                        <input type="email" placeholder="이메일 주소" class="newsletter-input" id="newsletter-email" required>
                        <button type="submit" class="newsletter-btn">
                            <i data-feather="arrow-right"></i>
                        </button>
                    </form>
                    <div id="newsletter-message" style="margin-top: 10px; font-size: 14px; display: none;"></div>
                </div>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p class="copyright">
                &copy; <?php echo date('Y'); ?> SUNGSUYA! All rights reserved.
            </p>
            <div class="footer-badges">
                <span class="badge">Made with ❤️ in Seongsu</span>
                <span class="badge">PWA Ready</span>
            </div>
        </div>
    </div>
    
    <!-- Background Animation -->
    <div class="footer-bg">
        <div class="gradient-orb orb-1"></div>
        <div class="gradient-orb orb-2"></div>
        <div class="gradient-orb orb-3"></div>
    </div>
</footer>

<style>
/* Footer Styles */
.site-footer {
    position: relative;
    background: var(--dark-surface);
    border-top: 1px solid var(--dark-border);
    margin-top: 100px;
    overflow: hidden;
}

.footer-container {
    position: relative;
    max-width: 1400px;
    margin: 0 auto;
    padding: 80px 40px 40px;
    z-index: 2;
}

/* Footer Top */
.footer-top {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 80px;
    margin-bottom: 60px;
}

.footer-brand {
    max-width: 400px;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
}

.footer-logo .logo-icon {
    width: 48px;
    height: 48px;
    background: var(--primary-gradient);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    font-weight: 900;
    color: white;
}

.footer-logo .logo-icon::before {
    content: 'S';
}

.footer-logo .logo-text {
    font-family: var(--font-display);
    font-size: 28px;
    letter-spacing: 1px;
    background: linear-gradient(135deg, #fff 0%, #e0e0e0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.footer-logo .logo-tagline {
    font-size: 12px;
    font-weight: 500;
    color: var(--muted-text);
    letter-spacing: 1px;
}

.footer-description {
    color: #FFFFFF;
    opacity: 0.7;
    line-height: 1.8;
    margin-bottom: 32px;
}

/* Social Links */
.social-links {
    display: flex;
    gap: 12px;
}

.social-link {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--dark-bg);
    border: 1px solid var(--dark-border);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.social-link:hover {
    background: var(--primary-gradient);
    border-color: transparent;
    transform: translateY(-2px);
}

.social-link svg {
    width: 20px;
    height: 20px;
    stroke: var(--light-text);
}

/* Footer Links */
.footer-links {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
}

.footer-column {
    min-width: 0;
}

.footer-title {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 24px;
    text-transform: uppercase;
    letter-spacing: 1px;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.footer-nav {
    list-style: none;
}

.footer-nav li {
    margin-bottom: 16px;
}

.footer-nav a {
    color: #FFFFFF;
    opacity: 0.7;
    font-size: 15px;
    transition: all 0.3s ease;
    position: relative;
    padding-left: 0;
}

.footer-nav a:hover {
    color: #FFFFFF;
    opacity: 1;
    padding-left: 8px;
}

/* Newsletter */
.newsletter-text {
    color: var(--muted-text);
    margin-bottom: 16px;
    font-size: 15px;
}

.newsletter-form {
    display: flex;
    gap: 8px;
    max-width: 300px;
}

.newsletter-input {
    flex: 1;
    background: var(--dark-bg);
    border: 1px solid var(--dark-border);
    border-radius: 12px;
    padding: 12px 16px;
    color: var(--light-text);
    font-size: 14px;
    transition: all 0.3s ease;
}

.newsletter-input::placeholder {
    color: var(--muted-text);
}

.newsletter-input:focus {
    outline: none;
    border-color: var(--neon-blue);
    box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.1);
}

.newsletter-btn {
    width: 48px;
    height: 48px;
    background: var(--primary-gradient);
    border: none;
    border-radius: 12px;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.newsletter-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.newsletter-btn svg {
    width: 20px;
    height: 20px;
}

/* Footer Bottom */
.footer-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 40px;
    border-top: 1px solid var(--dark-border);
}

.copyright {
    color: var(--muted-text);
    font-size: 14px;
}

.footer-badges {
    display: flex;
    gap: 16px;
}

.badge {
    padding: 6px 16px;
    background: var(--dark-bg);
    border: 1px solid var(--dark-border);
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: var(--muted-text);
}

/* Background Animation */
.footer-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 1;
    opacity: 0.5;
}

.gradient-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    animation: float 20s infinite ease-in-out;
}

.orb-1 {
    width: 400px;
    height: 400px;
    background: var(--neon-purple);
    top: -200px;
    left: -100px;
    animation-delay: 0s;
}

.orb-2 {
    width: 300px;
    height: 300px;
    background: var(--neon-blue);
    bottom: -150px;
    right: -100px;
    animation-delay: 7s;
}

.orb-3 {
    width: 350px;
    height: 350px;
    background: var(--neon-pink);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation-delay: 14s;
}

@keyframes float {
    0%, 100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(30px, -30px) scale(1.1);
    }
    66% {
        transform: translate(-30px, 30px) scale(0.9);
    }
}

/* Responsive */
@media (max-width: 1024px) {
    .footer-top {
        grid-template-columns: 1fr;
        gap: 60px;
    }
    
    .footer-links {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .footer-container {
        padding: 60px 20px 30px;
    }
    
    .footer-links {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .footer-bottom {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .newsletter-form {
        max-width: 100%;
    }
}
</style>

<script>
// Newsletter form submission
document.addEventListener('DOMContentLoaded', function() {
    const newsletterForm = document.getElementById('newsletter-form');
    const emailInput = document.getElementById('newsletter-email');
    const messageDiv = document.getElementById('newsletter-message');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = emailInput.value;
            const formData = new FormData();
            formData.append('action', 'subscribe_newsletter');
            formData.append('email', email);
            
            // Show loading
            messageDiv.style.display = 'block';
            messageDiv.style.color = '#666';
            messageDiv.textContent = '구독 처리 중...';
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.style.color = '#4CAF50';
                    messageDiv.textContent = data.data;
                    emailInput.value = '';
                } else {
                    messageDiv.style.color = '#f44336';
                    messageDiv.textContent = data.data;
                }
            })
            .catch(error => {
                messageDiv.style.color = '#f44336';
                messageDiv.textContent = '오류가 발생했습니다. 다시 시도해주세요.';
            });
        });
    }
});
</script>

<?php 
// PWA 설치 안내 - 모든 페이지에 표시 (단, 이미 설치된 경우 자동으로 숨김)
get_template_part('template-parts/pwa-install-simple');
?>

<?php wp_footer(); ?>
</body>
</html>