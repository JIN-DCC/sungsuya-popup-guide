/**
 * 모바일 최적화 JavaScript
 * 모바일 환경에서의 성능과 UX 개선
 * 
 * @since 2025-07-02
 */

(function() {
    'use strict';

    // 모바일 감지
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    /**
     * 모바일 최적화 매니저
     */
    const MobileOptimizer = {
        init() {
            if (!isMobile && !isTouch) return;

            this.optimizeViewport();
            this.preventDoubleTap();
            this.optimizeScrolling();
            this.setupLazyLoading();
            this.optimizeForms();
            this.setupOfflineSupport();
            this.optimizeAnimations();
            this.setupTouchFeedback();
        },

        /**
         * 뷰포트 최적화
         */
        optimizeViewport() {
            // 뷰포트 메타 태그 동적 조정
            const viewport = document.querySelector('meta[name="viewport"]');
            if (viewport) {
                // user-scalable=yes로 변경하여 확대/축소 허용
                viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes';
            }

            // iOS 바운스 스크롤 방지 - 주석 처리하여 스크롤 허용
            // document.body.addEventListener('touchmove', (e) => {
            //     if (e.target.closest('.scrollable')) return;
            //     if (e.touches.length > 1) return;
            //     e.preventDefault();
            // }, { passive: false });
        },

        /**
         * 더블탭 방지
         */
        preventDoubleTap() {
            // 더블탭 줌 방지를 제거하여 자연스러운 동작 허용
            // let lastTap = 0;
            // document.addEventListener('touchend', (e) => {
            //     const currentTime = new Date().getTime();
            //     const tapLength = currentTime - lastTap;
            //     if (tapLength < 500 && tapLength > 0) {
            //         e.preventDefault();
            //     }
            //     lastTap = currentTime;
            // });
        },

        /**
         * 스크롤 최적화
         */
        optimizeScrolling() {
            // 스크롤 이벤트 쓰로틀링
            let scrollTimeout;
            let isScrolling = false;

            window.addEventListener('scroll', () => {
                if (!isScrolling) {
                    window.requestAnimationFrame(() => {
                        document.body.classList.add('is-scrolling');
                        isScrolling = false;
                    });
                    isScrolling = true;
                }

                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    document.body.classList.remove('is-scrolling');
                }, 100);
            }, { passive: true });

            // 모멘텀 스크롤 개선
            document.querySelectorAll('.scrollable').forEach(element => {
                element.style.webkitOverflowScrolling = 'touch';
                element.style.overscrollBehavior = 'contain';
            });
        },

        /**
         * 이미지 지연 로딩
         */
        setupLazyLoading() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            
                            // data-src에서 src로 이동
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                            }
                            
                            // srcset 처리
                            if (img.dataset.srcset) {
                                img.srcset = img.dataset.srcset;
                                img.removeAttribute('data-srcset');
                            }
                            
                            img.classList.add('loaded');
                            observer.unobserve(img);
                        }
                    });
                }, {
                    rootMargin: '50px 0px',
                    threshold: 0.01
                });

                // 모든 지연 로딩 이미지 관찰
                document.querySelectorAll('img[data-src], img[data-srcset]').forEach(img => {
                    imageObserver.observe(img);
                });
            } else {
                // 폴백: IntersectionObserver 미지원 브라우저
                document.querySelectorAll('img[data-src]').forEach(img => {
                    img.src = img.dataset.src;
                });
            }
        },

        /**
         * 폼 최적화
         */
        optimizeForms() {
            // 자동 대문자 비활성화
            document.querySelectorAll('input[type="text"], input[type="email"], textarea').forEach(input => {
                input.setAttribute('autocapitalize', 'off');
            });

            // 숫자 입력 필드 최적화
            document.querySelectorAll('input[type="number"]').forEach(input => {
                input.setAttribute('pattern', '[0-9]*');
                input.setAttribute('inputmode', 'numeric');
            });

            // 전화번호 입력 필드
            document.querySelectorAll('input[type="tel"]').forEach(input => {
                input.setAttribute('inputmode', 'tel');
            });

            // 이메일 입력 필드
            document.querySelectorAll('input[type="email"]').forEach(input => {
                input.setAttribute('inputmode', 'email');
            });
        },

        /**
         * 오프라인 지원
         */
        setupOfflineSupport() {
            // 네트워크 상태 감지
            window.addEventListener('online', () => {
                document.body.classList.remove('offline');
                this.showNotification('인터넷 연결이 복구되었습니다.');
            });

            window.addEventListener('offline', () => {
                document.body.classList.add('offline');
                this.showNotification('오프라인 상태입니다.');
            });

            // 초기 상태 확인
            if (!navigator.onLine) {
                document.body.classList.add('offline');
            }
        },

        /**
         * 애니메이션 최적화
         */
        optimizeAnimations() {
            // Reduced Motion 확인
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            
            if (prefersReducedMotion) {
                document.body.classList.add('reduce-motion');
            }

            // 애니메이션 성능 모니터링
            if ('PerformanceObserver' in window) {
                const perfObserver = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        if (entry.duration > 16) { // 60fps 기준
                            console.warn('Slow animation detected:', entry.name);
                        }
                    }
                });

                perfObserver.observe({ entryTypes: ['measure'] });
            }
        },

        /**
         * 터치 피드백
         */
        setupTouchFeedback() {
            // 터치 시작 피드백
            document.addEventListener('touchstart', (e) => {
                const target = e.target.closest('button, a, .clickable');
                if (target) {
                    target.classList.add('touch-active');
                }
            }, { passive: true });

            // 터치 종료 피드백
            document.addEventListener('touchend', (e) => {
                const target = e.target.closest('button, a, .clickable');
                if (target) {
                    setTimeout(() => {
                        target.classList.remove('touch-active');
                    }, 100);
                }
            }, { passive: true });
        },

        /**
         * 알림 표시
         */
        showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'mobile-notification';
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // 애니메이션
            requestAnimationFrame(() => {
                notification.classList.add('show');
            });
            
            // 3초 후 제거
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    };

    /**
     * 모바일 제스처 헬퍼
     */
    const GestureHelper = {
        /**
         * 스와이프 감지
         */
        detectSwipe(element, callbacks) {
            let touchStartX = 0;
            let touchStartY = 0;
            let touchEndX = 0;
            let touchEndY = 0;

            element.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
                touchStartY = e.changedTouches[0].screenY;
            }, { passive: true });

            element.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                touchEndY = e.changedTouches[0].screenY;
                this.handleSwipe(touchStartX, touchStartY, touchEndX, touchEndY, callbacks);
            }, { passive: true });
        },

        handleSwipe(startX, startY, endX, endY, callbacks) {
            const diffX = endX - startX;
            const diffY = endY - startY;
            const minSwipeDistance = 50;

            if (Math.abs(diffX) > Math.abs(diffY)) {
                // 수평 스와이프
                if (Math.abs(diffX) > minSwipeDistance) {
                    if (diffX > 0 && callbacks.onSwipeRight) {
                        callbacks.onSwipeRight();
                    } else if (diffX < 0 && callbacks.onSwipeLeft) {
                        callbacks.onSwipeLeft();
                    }
                }
            } else {
                // 수직 스와이프
                if (Math.abs(diffY) > minSwipeDistance) {
                    if (diffY > 0 && callbacks.onSwipeDown) {
                        callbacks.onSwipeDown();
                    } else if (diffY < 0 && callbacks.onSwipeUp) {
                        callbacks.onSwipeUp();
                    }
                }
            }
        }
    };

    /**
     * 모바일 유틸리티
     */
    window.MobileUtils = {
        isMobile,
        isTouch,
        
        /**
         * 바텀시트 열기
         */
        openBottomSheet(content) {
            const sheet = document.createElement('div');
            sheet.className = 'bottom-sheet';
            sheet.innerHTML = content;
            
            document.body.appendChild(sheet);
            
            requestAnimationFrame(() => {
                sheet.classList.add('active');
            });
            
            // 배경 클릭 시 닫기
            sheet.addEventListener('click', (e) => {
                if (e.target === sheet) {
                    this.closeBottomSheet(sheet);
                }
            });
            
            return sheet;
        },
        
        /**
         * 바텀시트 닫기
         */
        closeBottomSheet(sheet) {
            sheet.classList.remove('active');
            setTimeout(() => {
                sheet.remove();
            }, 300);
        },
        
        /**
         * 진동 피드백
         */
        vibrate(pattern = 50) {
            if ('vibrate' in navigator) {
                navigator.vibrate(pattern);
            }
        }
    };

    // DOM 준비 시 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            MobileOptimizer.init();
        });
    } else {
        MobileOptimizer.init();
    }

    // 전역 노출
    window.MobileOptimizer = MobileOptimizer;
    window.GestureHelper = GestureHelper;

})();
