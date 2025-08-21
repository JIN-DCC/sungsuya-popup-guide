/**
 * 성수야! V2 - 터치 제스처 최적화
 * Phase 3: Task 3.2 - 모바일 터치 UX 개선
 * 
 * 목적:
 * 1. 스와이프 제스처 구현
 * 2. 탭 반응 속도 향상
 * 3. 스크롤 부드럽게
 * 4. 터치 피드백 개선
 */

(function() {
    'use strict';

    // ==========================================
    // 1. 터치 이벤트 매니저
    // ==========================================
    
    const TouchManager = {
        touchStartX: 0,
        touchStartY: 0,
        touchEndX: 0,
        touchEndY: 0,
        touchStartTime: 0,
        touchEndTime: 0,
        isScrolling: false,
        
        // 터치 임계값
        SWIPE_THRESHOLD: 50,
        TAP_THRESHOLD: 10,
        TAP_TIME_THRESHOLD: 300,
        DOUBLE_TAP_THRESHOLD: 500,
        
        init() {
            this.attachGlobalListeners();
            this.optimizeTouchResponsiveness();
            this.setupSwipeGestures();
        },
        
        attachGlobalListeners() {
            // Passive 리스너로 성능 향상
            document.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
            document.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: true }); // passive: true로 변경
            document.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
        },
        
        handleTouchStart(e) {
            this.touchStartX = e.touches[0].clientX;
            this.touchStartY = e.touches[0].clientY;
            this.touchStartTime = Date.now();
            this.isScrolling = false;
            
            // 터치 시작 피드백
            const target = e.target.closest('.touchable, button, a, .card, .tour-item');
            if (target) {
                target.classList.add('touch-active');
            }
        },
        
        handleTouchMove(e) {
            const touchX = e.touches[0].clientX;
            const touchY = e.touches[0].clientY;
            
            const deltaX = Math.abs(touchX - this.touchStartX);
            const deltaY = Math.abs(touchY - this.touchStartY);
            
            // 수직 스크롤 감지
            if (deltaY > deltaX && deltaY > this.TAP_THRESHOLD) {
                this.isScrolling = true;
            }
            
            // 수평 스와이프 중 수직 스크롤 방지 - 주석 처리하여 스크롤 허용
            // if (deltaX > deltaY && deltaX > this.TAP_THRESHOLD) {
            //     const swipeTarget = e.target.closest('.swipeable');
            //     if (swipeTarget) {
            //         e.preventDefault();
            //     }
            // }
        },
        
        handleTouchEnd(e) {
            this.touchEndX = e.changedTouches[0].clientX;
            this.touchEndY = e.changedTouches[0].clientY;
            this.touchEndTime = Date.now();
            
            // 터치 종료 피드백
            const target = e.target.closest('.touchable, button, a, .card, .tour-item');
            if (target) {
                target.classList.remove('touch-active');
            }
            
            // 제스처 분석
            if (!this.isScrolling) {
                this.analyzeGesture(e);
            }
        },
        
        analyzeGesture(e) {
            const deltaX = this.touchEndX - this.touchStartX;
            const deltaY = this.touchEndY - this.touchStartY;
            const deltaTime = this.touchEndTime - this.touchStartTime;
            
            // 탭 감지
            if (Math.abs(deltaX) < this.TAP_THRESHOLD && 
                Math.abs(deltaY) < this.TAP_THRESHOLD &&
                deltaTime < this.TAP_TIME_THRESHOLD) {
                this.handleTap(e);
                return;
            }
            
            // 스와이프 감지
            if (Math.abs(deltaX) > this.SWIPE_THRESHOLD) {
                if (deltaX > 0) {
                    this.handleSwipeRight(e);
                } else {
                    this.handleSwipeLeft(e);
                }
            }
        },
        
        handleTap(e) {
            const target = e.target;
            
            // 더블탭 감지
            if (this.lastTapTime && (this.touchEndTime - this.lastTapTime) < this.DOUBLE_TAP_THRESHOLD) {
                this.handleDoubleTap(e);
                this.lastTapTime = null;
            } else {
                this.lastTapTime = this.touchEndTime;
                
                // 탭 이벤트 발생
                const tapEvent = new CustomEvent('tap', {
                    detail: { target: target },
                    bubbles: true
                });
                target.dispatchEvent(tapEvent);
            }
        },
        
        handleDoubleTap(e) {
            const target = e.target;
            
            // 더블탭 이벤트 발생
            const doubleTapEvent = new CustomEvent('doubletap', {
                detail: { target: target },
                bubbles: true
            });
            target.dispatchEvent(doubleTapEvent);
            
            // 이미지 확대/축소
            if (target.tagName === 'IMG') {
                this.toggleImageZoom(target);
            }
        },
        
        handleSwipeLeft(e) {
            const swipeTarget = e.target.closest('.swipeable');
            if (swipeTarget) {
                const swipeEvent = new CustomEvent('swipeleft', {
                    detail: { target: swipeTarget },
                    bubbles: true
                });
                swipeTarget.dispatchEvent(swipeEvent);
            }
        },
        
        handleSwipeRight(e) {
            const swipeTarget = e.target.closest('.swipeable');
            if (swipeTarget) {
                const swipeEvent = new CustomEvent('swiperight', {
                    detail: { target: swipeTarget },
                    bubbles: true
                });
                swipeTarget.dispatchEvent(swipeEvent);
            }
        },
        
        optimizeTouchResponsiveness() {
            // 터치 지연 제거
            const style = document.createElement('style');
            style.textContent = `
                * {
                    -webkit-tap-highlight-color: transparent;
                }
                
                a, button, .touchable {
                    -webkit-touch-callout: none;
                    -webkit-user-select: none;
                    user-select: none;
                    touch-action: manipulation;
                }
                
                .scrollable {
                    -webkit-overflow-scrolling: touch;
                    overflow-scrolling: touch;
                }
                
                .touch-active {
                    opacity: 0.7 !important;
                    transform: scale(0.98) !important;
                    transition: all 0.1s !important;
                }
            `;
            document.head.appendChild(style);
        },
        
        setupSwipeGestures() {
            // 이미지 갤러리 스와이프
            this.setupGallerySwipe();
            
            // 탭 네비게이션 스와이프
            this.setupTabSwipe();
            
            // 카드 스와이프 삭제
            this.setupCardSwipe();
        },
        
        setupGallerySwipe() {
            document.querySelectorAll('.image-gallery').forEach(gallery => {
                let currentIndex = 0;
                const images = gallery.querySelectorAll('.gallery-image');
                
                gallery.classList.add('swipeable');
                
                gallery.addEventListener('swipeleft', () => {
                    if (currentIndex < images.length - 1) {
                        currentIndex++;
                        this.updateGallery(gallery, currentIndex);
                    }
                });
                
                gallery.addEventListener('swiperight', () => {
                    if (currentIndex > 0) {
                        currentIndex--;
                        this.updateGallery(gallery, currentIndex);
                    }
                });
            });
        },
        
        updateGallery(gallery, index) {
            const container = gallery.querySelector('.gallery-container');
            const imageWidth = gallery.offsetWidth;
            
            container.style.transform = `translateX(-${index * imageWidth}px)`;
            
            // 인디케이터 업데이트
            const indicators = gallery.querySelectorAll('.gallery-indicator');
            indicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === index);
            });
        },
        
        setupTabSwipe() {
            const tabContainer = document.querySelector('.tab-navigation');
            if (!tabContainer) return;
            
            tabContainer.classList.add('swipeable');
            
            let currentTab = 0;
            const tabs = tabContainer.querySelectorAll('.tab-item');
            
            tabContainer.addEventListener('swipeleft', () => {
                if (currentTab < tabs.length - 1) {
                    currentTab++;
                    tabs[currentTab].click();
                }
            });
            
            tabContainer.addEventListener('swiperight', () => {
                if (currentTab > 0) {
                    currentTab--;
                    tabs[currentTab].click();
                }
            });
        },
        
        setupCardSwipe() {
            document.querySelectorAll('.swipe-to-delete').forEach(card => {
                let startX = 0;
                let currentX = 0;
                let isDragging = false;
                
                card.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].clientX;
                    isDragging = true;
                    card.style.transition = 'none';
                });
                
                card.addEventListener('touchmove', (e) => {
                    if (!isDragging) return;
                    
                    currentX = e.touches[0].clientX;
                    const deltaX = currentX - startX;
                    
                    // 왼쪽 스와이프만 허용
                    if (deltaX < 0) {
                        card.style.transform = `translateX(${deltaX}px)`;
                        
                        // 삭제 버튼 표시
                        const deleteBtn = card.querySelector('.delete-action');
                        if (deleteBtn) {
                            deleteBtn.style.opacity = Math.min(1, Math.abs(deltaX) / 100);
                        }
                    }
                });
                
                card.addEventListener('touchend', () => {
                    isDragging = false;
                    card.style.transition = 'transform 0.3s';
                    
                    const deltaX = currentX - startX;
                    
                    // 충분히 스와이프했으면 삭제
                    if (Math.abs(deltaX) > card.offsetWidth * 0.3) {
                        card.style.transform = `translateX(-100%)`;
                        setTimeout(() => {
                            card.remove();
                        }, 300);
                    } else {
                        card.style.transform = 'translateX(0)';
                    }
                });
            });
        },
        
        toggleImageZoom(img) {
            if (img.classList.contains('zoomed')) {
                img.classList.remove('zoomed');
                img.style.transform = 'scale(1)';
            } else {
                img.classList.add('zoomed');
                img.style.transform = 'scale(2)';
            }
        }
    };
    
    // ==========================================
    // 2. 스크롤 최적화
    // ==========================================
    
    const ScrollOptimizer = {
        init() {
            this.setupSmoothScroll();
            this.setupScrollSnap();
            this.setupPullToRefresh();
        },
        
        setupSmoothScroll() {
            // CSS로 처리 (performance-optimize.css에 정의)
            document.documentElement.style.scrollBehavior = 'smooth';
            
            // iOS 바운스 스크롤 활성화
            document.body.style.webkitOverflowScrolling = 'touch';
        },
        
        setupScrollSnap() {
            // 수평 스크롤 스냅
            document.querySelectorAll('.scroll-snap-x').forEach(container => {
                container.style.scrollSnapType = 'x mandatory';
                container.style.overflowX = 'auto';
                container.style.webkitOverflowScrolling = 'touch';
                
                container.querySelectorAll('.snap-item').forEach(item => {
                    item.style.scrollSnapAlign = 'start';
                });
            });
            
            // 수직 스크롤 스냅
            document.querySelectorAll('.scroll-snap-y').forEach(container => {
                container.style.scrollSnapType = 'y proximity';
                container.style.overflowY = 'auto';
                container.style.webkitOverflowScrolling = 'touch';
                
                container.querySelectorAll('.snap-item').forEach(item => {
                    item.style.scrollSnapAlign = 'start';
                });
            });
        },
        
        setupPullToRefresh() {
            let startY = 0;
            let isPulling = false;
            const threshold = 100;
            
            const refreshIndicator = document.createElement('div');
            refreshIndicator.className = 'refresh-indicator';
            refreshIndicator.innerHTML = '<span class="refresh-icon">🔄</span>';
            document.body.prepend(refreshIndicator);
            
            document.addEventListener('touchstart', (e) => {
                if (window.scrollY === 0) {
                    startY = e.touches[0].clientY;
                    isPulling = true;
                }
            });
            
            document.addEventListener('touchmove', (e) => {
                if (!isPulling) return;
                
                const currentY = e.touches[0].clientY;
                const pullDistance = currentY - startY;
                
                if (pullDistance > 0 && pullDistance < threshold * 2) {
                    // pull to refresh를 위한 preventDefault 주석 처리
                    // e.preventDefault();
                    
                    refreshIndicator.style.transform = `translateY(${pullDistance}px)`;
                    refreshIndicator.style.opacity = pullDistance / threshold;
                    
                    if (pullDistance > threshold) {
                        refreshIndicator.classList.add('ready');
                    } else {
                        refreshIndicator.classList.remove('ready');
                    }
                }
            });
            
            document.addEventListener('touchend', () => {
                if (!isPulling) return;
                
                isPulling = false;
                
                if (refreshIndicator.classList.contains('ready')) {
                    refreshIndicator.classList.add('refreshing');
                    
                    // 페이지 새로고침
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    refreshIndicator.style.transform = 'translateY(0)';
                    refreshIndicator.style.opacity = '0';
                }
            });
        }
    };
    
    // ==========================================
    // 3. 터치 피드백 향상
    // ==========================================
    
    const TouchFeedback = {
        init() {
            this.setupRippleEffect();
            this.setupHapticFeedback();
        },
        
        setupRippleEffect() {
            document.addEventListener('click', (e) => {
                const target = e.target.closest('.ripple, button, .card');
                if (!target) return;
                
                const ripple = document.createElement('span');
                ripple.className = 'ripple-effect';
                
                const rect = target.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                
                target.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        },
        
        setupHapticFeedback() {
            // 진동 API 지원 확인
            if ('vibrate' in navigator) {
                // 버튼 클릭 시 짧은 진동
                document.addEventListener('click', (e) => {
                    if (e.target.closest('button, .touchable')) {
                        navigator.vibrate(10);
                    }
                });
                
                // 스와이프 완료 시 진동
                document.addEventListener('swipeleft', () => {
                    navigator.vibrate([10, 10, 10]);
                });
                
                document.addEventListener('swiperight', () => {
                    navigator.vibrate([10, 10, 10]);
                });
                
                // 롱프레스 시 진동
                let longPressTimer;
                
                document.addEventListener('touchstart', (e) => {
                    const target = e.target.closest('.long-press');
                    if (!target) return;
                    
                    longPressTimer = setTimeout(() => {
                        navigator.vibrate(50);
                        target.classList.add('long-pressed');
                    }, 500);
                });
                
                document.addEventListener('touchend', () => {
                    clearTimeout(longPressTimer);
                });
            }
        }
    };
    
    // ==========================================
    // 4. 초기화
    // ==========================================
    
    document.addEventListener('DOMContentLoaded', () => {
        // 모바일 디바이스에서만 실행
        if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
            TouchManager.init();
            ScrollOptimizer.init();
            TouchFeedback.init();
            
            // 터치 최적화 완료 표시
            document.documentElement.classList.add('touch-optimized');
            
            console.log('Touch gesture optimizations loaded');
        }
    });
    
    // ==========================================
    // 5. CSS 스타일 추가
    // ==========================================
    
    const style = document.createElement('style');
    style.textContent = `
        /* 리플 효과 */
        .ripple, button, .card {
            position: relative;
            overflow: hidden;
        }
        
        .ripple-effect {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        /* Pull to Refresh */
        .refresh-indicator {
            position: fixed;
            top: -50px;
            left: 50%;
            transform: translateX(-50%) translateY(0);
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 1000;
        }
        
        .refresh-indicator.ready .refresh-icon {
            transform: rotate(180deg);
        }
        
        .refresh-indicator.refreshing .refresh-icon {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* 스와이프 삭제 */
        .swipe-to-delete {
            position: relative;
            overflow: hidden;
        }
        
        .delete-action {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 80px;
            background: #ef4444;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        /* 이미지 줌 */
        img.zoomed {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(2);
            z-index: 1000;
            max-width: none;
        }
        
        /* 롱프레스 피드백 */
        .long-pressed {
            animation: pulse 0.3s ease-out;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(0.95); }
            100% { transform: scale(1); }
        }
    `;
    document.head.appendChild(style);
    
})();
