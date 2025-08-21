<?php
/**
 * Service Introduction Banner - DISABLED
 * 
 * 이 파일은 비활성화되었습니다.
 * PWA 설치 안내는 template-parts/pwa-install-simple.php를 사용하세요.
 * 
 * @package SungsuyaV2
 * @deprecated 2025-07-05
 */

// 아무것도 출력하지 않음
return;
?>

<style>
/* Service Intro Banner Styles */
.service-intro-banner {
    background: linear-gradient(135deg, #B85450 0%, #A0433F 100%);
    padding: 20px 0;
    text-align: center;
    margin-bottom: 0;
    position: relative;
    z-index: 10;
    box-shadow: 0 2px 10px rgba(184, 84, 80, 0.2);
}

.service-intro-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.service-intro-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.service-intro-text {
    color: #FFFFFF;
    font-size: 16px;
    font-weight: 500;
    margin: 0;
}

.service-intro-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 14px;
    margin: 0;
}

.service-intro-buttons {
    display: flex;
    gap: 12px;
}

.service-intro-btn {
    padding: 10px 24px;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.service-intro-btn.primary {
    background: #FFFFFF;
    color: #B85450;
}

.service-intro-btn.primary:hover {
    background: #f5f5f5;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.service-intro-btn.secondary {
    background: transparent;
    color: #FFFFFF;
    border: 2px solid #FFFFFF;
}

.service-intro-btn.secondary:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Close button */
.service-intro-close {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #FFFFFF;
    font-size: 24px;
    cursor: pointer;
    padding: 4px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.service-intro-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Hide when closed */
.service-intro-banner.hidden {
    display: none;
}

/* Responsive */
@media (max-width: 768px) {
    .service-intro-content {
        flex-direction: column;
        gap: 16px;
    }
    
    .service-intro-buttons {
        flex-direction: column;
        width: 100%;
        gap: 8px;
    }
    
    .service-intro-btn {
        width: 100%;
        justify-content: center;
    }
    
    .service-intro-close {
        position: static;
        transform: none;
        margin-top: 10px;
    }
}
</style>

<div class="service-intro-banner" id="service-intro-banner">
    <div class="service-intro-container">
        <div class="service-intro-content">
            <div>
                <h3 class="service-intro-text">🚀 성수야! 앱으로 더 편리하게 이용하세요!</h3>
                <p class="service-intro-subtitle">홈 화면에 추가하고 오프라인에서도 사용 가능합니다</p>
            </div>
            <div class="service-intro-buttons">
                <button id="pwa-install-btn" class="service-intro-btn primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2L2 7V12C2 16.5 4.23 20.68 7.5 23.5L12 24L16.5 23.5C19.77 20.68 22 16.5 22 12V7L12 2Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M12 8V16M12 16L9 13M12 16L15 13" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    앱 설치하기
                </button>
                <a href="<?php echo home_url('/tour-v2'); ?>" class="service-intro-btn secondary">
                    투어 시작하기
                </a>
            </div>
        </div>
        <button class="service-intro-close" id="close-service-intro" aria-label="배너 닫기">
            &times;
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const banner = document.getElementById('service-intro-banner');
    const closeBtn = document.getElementById('close-service-intro');
    const installBtn = document.getElementById('pwa-install-btn');
    
    // Check if already installed
    const isInstalled = window.matchMedia('(display-mode: standalone)').matches || 
                       window.navigator.standalone || 
                       document.referrer.includes('android-app://');
    
    // Check if banner was previously closed
    const bannerClosed = localStorage.getItem('pwaInstallBannerClosed');
    const closedTime = localStorage.getItem('pwaInstallBannerClosedTime');
    
    // Show again after 7 days
    let shouldShow = true;
    if (bannerClosed === 'true' && closedTime) {
        const daysSinceClosed = (Date.now() - parseInt(closedTime)) / (1000 * 60 * 60 * 24);
        if (daysSinceClosed < 7) {
            shouldShow = false;
        }
    }
    
    // Hide if already installed or recently closed
    if (isInstalled || !shouldShow) {
        if (banner) banner.classList.add('hidden');
        return;
    }
    
    // Check if iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    
    let deferredPrompt;
    
    // Handle iOS devices
    if (isIOS) {
        if (installBtn) {
            installBtn.addEventListener('click', () => {
                // Show iOS installation modal
                const modal = document.createElement('div');
                modal.innerHTML = `
                    <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px;">
                        <div style="background: white; border-radius: 20px; padding: 30px; max-width: 400px; width: 100%;">
                            <h3 style="color: #333; font-size: 20px; margin: 0 0 20px 0; text-align: center;">성수야! 홈 화면에 추가하기</h3>
                            <div style="color: #666; font-size: 16px; line-height: 1.6;">
                                <p style="margin-bottom: 15px;">1. 하단의 <strong>공유</strong> 버튼을 탭하세요</p>
                                <p style="margin-bottom: 15px;">2. <strong>"홈 화면에 추가"</strong>를 선택하세요</p>
                                <p style="margin-bottom: 20px;">3. 우측 상단의 <strong>"추가"</strong>를 탭하세요</p>
                            </div>
                            <button onclick="this.parentElement.parentElement.remove()" style="width: 100%; background: #4CAF50; color: white; border: none; padding: 12px; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer;">확인</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            });
        }
    } else {
        // Handle Android/Desktop
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
        });
        
        if (installBtn) {
            installBtn.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const result = await deferredPrompt.userChoice;
                    
                    if (result.outcome === 'accepted') {
                        console.log('PWA installed');
                        if (banner) banner.classList.add('hidden');
                    }
                    
                    deferredPrompt = null;
                } else {
                    // Fallback for when prompt is not available
                    alert('앱 설치는 Chrome, Edge, Samsung Internet 브라우저에서 가능합니다.');
                }
            });
        }
    }
    
    // Handle close button
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            if (banner) {
                banner.classList.add('hidden');
                localStorage.setItem('pwaInstallBannerClosed', 'true');
                localStorage.setItem('pwaInstallBannerClosedTime', Date.now().toString());
            }
        });
    }
    
    // Track app installed
    window.addEventListener('appinstalled', () => {
        console.log('PWA was installed');
        if (banner) banner.classList.add('hidden');
    });
});
</script>