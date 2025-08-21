<?php
/**
 * Simple PWA Install Guide
 * 
 * @package Sungsuya
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Simple PWA Install Guide -->
<?php 
// 중복 방지를 위한 체크
if (!defined('PWA_INSTALL_GUIDE_LOADED')) {
    define('PWA_INSTALL_GUIDE_LOADED', true);
?>
<div class="pwa-install-guide" id="pwa-install-guide-main">
    <div class="pwa-install-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L2 7V12C2 16.5 4.23 20.68 7.5 23.5L12 24L16.5 23.5C19.77 20.68 22 16.5 22 12V7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 8V16M12 16L9 13M12 16L15 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    <div class="pwa-install-text">
        <strong>성수야! 앱으로 더 편리하게!</strong>
        <span>홈 화면에 추가하여 오프라인에서도 사용하세요</span>
    </div>
    <div class="pwa-install-buttons">
        <button class="pwa-install-button pwa-install-android" onclick="installPWAAndroid()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.6 11.48 19.44 8.3a.63.63 0 0 0-1.09-.63l-1.88 3.24a11.43 11.43 0 0 0-8.94 0L5.65 7.67a.63.63 0 0 0-1.09.63l1.84 3.18a10.81 10.81 0 0 0-5.9 10.02h22a10.81 10.81 0 0 0-5.9-10.02M7 17.25A1.25 1.25 0 1 1 8.25 16 1.25 1.25 0 0 1 7 17.25m10 0A1.25 1.25 0 1 1 18.25 16 1.25 1.25 0 0 1 17 17.25"/>
            </svg>
            Android 설치
        </button>
        <button class="pwa-install-button pwa-install-ios" onclick="installPWAiOS()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
            </svg>
            iOS 설치
        </button>
        <button class="pwa-install-button pwa-install-manual" onclick="showPWAInstallInstructions()">
            설치 방법 보기
        </button>
    </div>
</div>

<style>
/* Simple PWA Install Guide */
.pwa-install-guide {
    background: var(--warm-white, #FAFAF8);
    border: 1px solid var(--concrete-grey, #E8E6E1);
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 20px 0;
}

/* Hero 섹션 내부용 스타일 */
.hero-content .pwa-install-guide {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    max-width: 600px;
    margin: 24px auto 32px;
    animation: fadeInUp 0.8s ease 0.5s both;
}

.hero-content .pwa-install-text strong,
.hero-content .pwa-install-text span {
    color: #FFFFFF !important;
}

.hero-content .pwa-install-icon {
    color: #FFFFFF;
}

.pwa-install-icon {
    color: var(--brick-red, #B85450);
    flex-shrink: 0;
}

.pwa-install-text {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.pwa-install-text strong {
    font-size: 14px;
    color: var(--charcoal, #2C2C2C);
}

.pwa-install-text span {
    font-size: 12px;
    color: var(--muted-text, #666);
}

.pwa-install-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.pwa-install-button {
    background: var(--brick-red, #B85450);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.pwa-install-button:hover {
    background: var(--dark-red, #A04441);
    transform: translateY(-1px);
}

.pwa-install-android {
    background: #3DDC84;
    display: none; /* 초기에 숨김, JS에서 조건부 표시 */
}

.pwa-install-android:hover {
    background: #2DC972;
}

.pwa-install-ios {
    background: #000000;
    display: none; /* 초기에 숨김, JS에서 조건부 표시 */
}

.pwa-install-ios:hover {
    background: #333333;
}

.pwa-install-manual {
    background: transparent;
    color: var(--brick-red, #B85450);
    border: 1px solid var(--brick-red, #B85450);
}

.pwa-install-manual:hover {
    background: var(--brick-red, #B85450);
    color: white;
}

/* PWA Install Instructions Modal */
.pwa-instructions-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.8); /* 불투명 배경 */
    z-index: 10000; /* 최상위 z-index */
    padding: 20px;
    overflow-y: auto;
}

.pwa-instructions-content {
    background: white;
    border-radius: 20px;
    max-width: 500px;
    margin: 40px auto;
    padding: 32px;
    position: relative;
}

.pwa-instructions-close {
    position: absolute;
    top: 20px;
    right: 20px;
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
    color: var(--muted-text);
}

.pwa-instructions-close:hover {
    color: var(--charcoal);
}

.pwa-instructions-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 24px;
    text-align: center;
    color: var(--charcoal);
}

.pwa-device-section {
    margin-bottom: 32px;
}

.pwa-device-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 16px;
    color: var(--charcoal);
    display: flex;
    align-items: center;
    gap: 8px;
}

.pwa-steps {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.pwa-step {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: var(--warm-white);
    border-radius: 8px;
}

.pwa-step-number {
    width: 24px;
    height: 24px;
    background: var(--brick-red);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    flex-shrink: 0;
}

.pwa-step-text {
    font-size: 14px;
    color: var(--charcoal);
    line-height: 1.5;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .pwa-install-guide {
        flex-direction: column;
        text-align: center;
    }
    
    .pwa-install-buttons {
        width: 100%;
        justify-content: center;
    }
    
    .pwa-install-button {
        flex: 1;
        min-width: 120px;
    }
    
    .pwa-instructions-content {
        margin: 20px auto;
        padding: 24px;
    }
}
</style>

<!-- PWA Install Instructions Modal -->
<div class="pwa-instructions-modal" id="pwa-instructions-modal">
    <div class="pwa-instructions-content">
        <button class="pwa-instructions-close" onclick="closePWAInstructions()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        
        <h3 class="pwa-instructions-title">성수야! 앱 설치 방법</h3>
        
        <!-- Android/Chrome -->
        <div class="pwa-device-section">
            <h4 class="pwa-device-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.6 11.48 19.44 8.3a.63.63 0 0 0-1.09-.63l-1.88 3.24a11.43 11.43 0 0 0-8.94 0L5.65 7.67a.63.63 0 0 0-1.09.63l1.84 3.18a10.81 10.81 0 0 0-5.9 10.02h22a10.81 10.81 0 0 0-5.9-10.02M7 17.25A1.25 1.25 0 1 1 8.25 16 1.25 1.25 0 0 1 7 17.25m10 0A1.25 1.25 0 1 1 18.25 16 1.25 1.25 0 0 1 17 17.25"/>
                </svg>
                Android (Chrome)
            </h4>
            <div class="pwa-steps">
                <div class="pwa-step">
                    <div class="pwa-step-number">1</div>
                    <div class="pwa-step-text">Chrome 브라우저 우측 상단의 점 3개 메뉴를 탭하세요</div>
                </div>
                <div class="pwa-step">
                    <div class="pwa-step-number">2</div>
                    <div class="pwa-step-text">"홈 화면에 추가" 또는 "앱 설치"를 선택하세요</div>
                </div>
                <div class="pwa-step">
                    <div class="pwa-step-number">3</div>
                    <div class="pwa-step-text">"추가" 또는 "설치"를 탭하세요</div>
                </div>
            </div>
        </div>
        
        <!-- iOS/Safari -->
        <div class="pwa-device-section">
            <h4 class="pwa-device-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                </svg>
                iPhone/iPad (Safari)
            </h4>
            <div class="pwa-steps">
                <div class="pwa-step">
                    <div class="pwa-step-number">1</div>
                    <div class="pwa-step-text">Safari 브라우저 하단의 공유 버튼을 탭하세요</div>
                </div>
                <div class="pwa-step">
                    <div class="pwa-step-number">2</div>
                    <div class="pwa-step-text">"홈 화면에 추가"를 선택하세요</div>
                </div>
                <div class="pwa-step">
                    <div class="pwa-step-number">3</div>
                    <div class="pwa-step-text">우측 상단의 "추가"를 탭하세요</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// PWA Install 관련 전역 변수
let deferredPrompt = null;
let isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
let isAndroid = /Android/.test(navigator.userAgent);

// beforeinstallprompt 이벤트 리스너를 가능한 한 빨리 등록
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    window.deferredPrompt = e;
    
    // setupPlatformButtons가 존재하면 호출
    if (typeof setupPlatformButtons === 'function') {
        setupPlatformButtons();
    }
});

// Simple PWA Install Instructions
function showPWAInstallInstructions() {
    const modal = document.getElementById('pwa-instructions-modal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closePWAInstructions() {
    const modal = document.getElementById('pwa-instructions-modal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Android PWA 설치
async function installPWAAndroid() {
    if (deferredPrompt) {
        // Chrome의 설치 프롬프트 표시
        deferredPrompt.prompt();
        
        // 사용자의 선택 대기
        const { outcome } = await deferredPrompt.userChoice;
        
        if (outcome === 'accepted') {
            // 설치 후 UI 업데이트
            hidePWAInstallGuide();
        }
        
        // 프롬프트는 한 번만 사용 가능
        deferredPrompt = null;
    } else {
        // 설치 프롬프트가 없는 경우 설명 표시
        showPWAInstallInstructions();
    }
}

// iOS PWA 설치
function installPWAiOS() {
    // iOS는 프로그래밍 방식으로 설치할 수 없으므로 안내 모달 표시
    showPWAInstallInstructions();
}

// PWA 설치 가이드 숨기기
function hidePWAInstallGuide() {
    const guide = document.querySelector('.pwa-install-guide');
    if (guide) {
        guide.style.display = 'none';
    }
}

// 플랫폼별 버튼 표시/숨김
function setupPlatformButtons() {
    // 모든 PWA 설치 가이드의 버튼을 찾음 (여러 개가 있을 수 있음)
    const allAndroidBtns = document.querySelectorAll('.pwa-install-android');
    const allIosBtns = document.querySelectorAll('.pwa-install-ios');
    const allManualBtns = document.querySelectorAll('.pwa-install-manual');
    
    // 모든 버튼 초기화 (숨김)
    allAndroidBtns.forEach(btn => btn.style.display = 'none');
    allIosBtns.forEach(btn => btn.style.display = 'none');
    allManualBtns.forEach(btn => btn.style.display = 'none');
    
    if (isAndroid) {
        allAndroidBtns.forEach(btn => {
            btn.style.display = 'flex';
        });
        // Android에서는 deferredPrompt가 없을 때만 수동 설치 버튼 표시
        if (!deferredPrompt) {
            allManualBtns.forEach(btn => {
                btn.style.display = 'flex';
            });
        }
    } else if (isIOS) {
        allIosBtns.forEach(btn => {
            btn.style.display = 'flex';
        });
        allManualBtns.forEach(btn => {
            btn.style.display = 'flex';
        });
    } else {
        // 데스크톱이나 기타 플랫폼
        allManualBtns.forEach(btn => {
            btn.style.display = 'flex';
        });
    }
}

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 이미 설치된 경우 가이드 숨김
    if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
        hidePWAInstallGuide();
        return;
    }
    
    // 초기 플랫폼별 버튼 설정
    setupPlatformButtons();
    
    // deferredPrompt가 이미 있으면 버튼 업데이트
    if (deferredPrompt) {
        setupPlatformButtons();
    }
    
    // 앱 설치 완료 감지
    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        hidePWAInstallGuide();
    });
});

// Close modal when clicking outside
document.getElementById('pwa-instructions-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePWAInstructions();
    }
});
</script>
<?php } // endif PWA_INSTALL_GUIDE_LOADED ?>
