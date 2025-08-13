/**
 * 성수야! V2 - 유틸리티 함수
 * 
 * 공통으로 사용되는 유틸리티 함수들
 * 
 * @package SungsuyaV2
 * @version 2.0.0
 */

// 전역 유틸리티 네임스페이스
window.SungsuyaUtils = (function() {
    'use strict';

    /**
     * API 호출 유틸리티
     */
    const api = {
        /**
         * GET 요청
         * @param {string} endpoint - API 엔드포인트
         * @param {Object} options - 추가 옵션
         * @returns {Promise<Object>}
         */
        async get(endpoint, options = {}) {
            try {
                const url = new URL(endpoint, window.SUNGSUYA_CONFIG.apiUrl);
                
                if (options.params) {
                    Object.keys(options.params).forEach(key => 
                        url.searchParams.append(key, options.params[key])
                    );
                }
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.SUNGSUYA_CONFIG.nonce,
                        ...options.headers
                    },
                    ...options
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return await response.json();
            } catch (error) {
                console.error('API GET error:', error);
                throw error;
            }
        },

        /**
         * POST 요청
         * @param {string} endpoint - API 엔드포인트
         * @param {Object} data - 전송할 데이터
         * @param {Object} options - 추가 옵션
         * @returns {Promise<Object>}
         */
        async post(endpoint, data = {}, options = {}) {
            try {
                const url = new URL(endpoint, window.SUNGSUYA_CONFIG.apiUrl);
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.SUNGSUYA_CONFIG.nonce,
                        ...options.headers
                    },
                    body: JSON.stringify(data),
                    ...options
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return await response.json();
            } catch (error) {
                console.error('API POST error:', error);
                throw error;
            }
        }
    };

    /**
     * DOM 유틸리티
     */
    const dom = {
        /**
         * 요소 선택
         * @param {string} selector - CSS 선택자
         * @param {Element} context - 검색 범위 (기본: document)
         * @returns {Element|null}
         */
        $(selector, context = document) {
            return context.querySelector(selector);
        },

        /**
         * 여러 요소 선택
         * @param {string} selector - CSS 선택자
         * @param {Element} context - 검색 범위 (기본: document)
         * @returns {NodeList}
         */
        $$(selector, context = document) {
            return context.querySelectorAll(selector);
        },

        /**
         * 요소 생성
         * @param {string} tag - 태그명
         * @param {Object} attributes - 속성 객체
         * @param {string} content - 내용
         * @returns {Element}
         */
        create(tag, attributes = {}, content = '') {
            const element = document.createElement(tag);
            
            Object.keys(attributes).forEach(key => {
                if (key === 'className') {
                    element.className = attributes[key];
                } else if (key === 'dataset') {
                    Object.keys(attributes[key]).forEach(dataKey => {
                        element.dataset[dataKey] = attributes[key][dataKey];
                    });
                } else {
                    element.setAttribute(key, attributes[key]);
                }
            });
            
            if (content) {
                element.innerHTML = content;
            }
            
            return element;
        },

        /**
         * 클래스 토글
         * @param {Element} element - 대상 요소
         * @param {string} className - 클래스명
         * @param {boolean} force - 강제 설정
         */
        toggleClass(element, className, force) {
            return element.classList.toggle(className, force);
        },

        /**
         * 애니메이션과 함께 요소 표시
         * @param {Element} element - 대상 요소
         * @param {string} animation - 애니메이션 클래스
         */
        show(element, animation = 'fadeIn') {
            element.style.display = '';
            element.classList.add(animation);
        },

        /**
         * 애니메이션과 함께 요소 숨김
         * @param {Element} element - 대상 요소
         * @param {string} animation - 애니메이션 클래스
         */
        hide(element, animation = 'fadeOut') {
            element.classList.add(animation);
            setTimeout(() => {
                element.style.display = 'none';
                element.classList.remove(animation);
            }, 300);
        }
    };

    /**
     * 로컬 스토리지 유틸리티
     */
    const storage = {
        /**
         * 데이터 저장
         * @param {string} key - 키
         * @param {*} value - 값
         */
        set(key, value) {
            try {
                localStorage.setItem(`sungsuya_${key}`, JSON.stringify(value));
            } catch (error) {
                console.warn('LocalStorage set error:', error);
            }
        },

        /**
         * 데이터 가져오기
         * @param {string} key - 키
         * @param {*} defaultValue - 기본값
         * @returns {*}
         */
        get(key, defaultValue = null) {
            try {
                const item = localStorage.getItem(`sungsuya_${key}`);
                return item ? JSON.parse(item) : defaultValue;
            } catch (error) {
                console.warn('LocalStorage get error:', error);
                return defaultValue;
            }
        },

        /**
         * 데이터 삭제
         * @param {string} key - 키
         */
        remove(key) {
            try {
                localStorage.removeItem(`sungsuya_${key}`);
            } catch (error) {
                console.warn('LocalStorage remove error:', error);
            }
        },

        /**
         * 모든 앱 데이터 삭제
         */
        clear() {
            try {
                Object.keys(localStorage)
                    .filter(key => key.startsWith('sungsuya_'))
                    .forEach(key => localStorage.removeItem(key));
            } catch (error) {
                console.warn('LocalStorage clear error:', error);
            }
        }
    };

    /**
     * 포맷팅 유틸리티
     */
    const format = {
        /**
         * 날짜 포맷팅
         * @param {Date|string} date - 날짜
         * @param {Object} options - 포맷 옵션
         * @returns {string}
         */
        date(date, options = {}) {
            const defaultOptions = {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                ...options
            };
            
            const dateObj = typeof date === 'string' ? new Date(date) : date;
            return dateObj.toLocaleDateString('ko-KR', defaultOptions);
        },

        /**
         * 시간 포맷팅
         * @param {Date|string} time - 시간
         * @param {Object} options - 포맷 옵션
         * @returns {string}
         */
        time(time, options = {}) {
            const defaultOptions = {
                hour: '2-digit',
                minute: '2-digit',
                ...options
            };
            
            const timeObj = typeof time === 'string' ? new Date(time) : time;
            return timeObj.toLocaleTimeString('ko-KR', defaultOptions);
        },

        /**
         * 거리 포맷팅
         * @param {number} meters - 미터 단위 거리
         * @returns {string}
         */
        distance(meters) {
            if (meters < 1000) {
                return `${Math.round(meters)}m`;
            }
            return `${(meters / 1000).toFixed(1)}km`;
        },

        /**
         * 시간 포맷팅 (분 단위)
         * @param {number} minutes - 분
         * @returns {string}
         */
        duration(minutes) {
            if (minutes < 60) {
                return `${minutes}분`;
            }
            const hours = Math.floor(minutes / 60);
            const remainingMinutes = minutes % 60;
            return remainingMinutes > 0 ? `${hours}시간 ${remainingMinutes}분` : `${hours}시간`;
        }
    };

    /**
     * 유효성 검사 유틸리티
     */
    const validate = {
        /**
         * 이메일 유효성 검사
         * @param {string} email - 이메일 주소
         * @returns {boolean}
         */
        email(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },

        /**
         * URL 유효성 검사
         * @param {string} url - URL
         * @returns {boolean}
         */
        url(url) {
            try {
                new URL(url);
                return true;
            } catch {
                return false;
            }
        },

        /**
         * 좌표 유효성 검사
         * @param {number} lat - 위도
         * @param {number} lng - 경도
         * @returns {boolean}
         */
        coordinates(lat, lng) {
            return typeof lat === 'number' && typeof lng === 'number' &&
                   lat >= -90 && lat <= 90 &&
                   lng >= -180 && lng <= 180;
        }
    };

    /**
     * 디바운스 함수
     * @param {Function} func - 실행할 함수
     * @param {number} wait - 대기 시간 (ms)
     * @param {boolean} immediate - 즉시 실행 여부
     * @returns {Function}
     */
    function debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func(...args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func(...args);
        };
    }

    /**
     * 스로틀 함수
     * @param {Function} func - 실행할 함수
     * @param {number} limit - 제한 시간 (ms)
     * @returns {Function}
     */
    function throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * 이미지 지연 로딩
     * @param {string} selector - 이미지 선택자
     * @param {Object} options - IntersectionObserver 옵션
     */
    function lazyLoadImages(selector = 'img[data-src]', options = {}) {
        const defaultOptions = {
            rootMargin: '50px',
            threshold: 0.1,
            ...options
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('skeleton');
                    observer.unobserve(img);
                }
            });
        }, defaultOptions);

        dom.$$(selector).forEach(img => observer.observe(img));
    }

    /**
     * 토스트 알림 표시
     * @param {string} message - 메시지
     * @param {string} type - 타입 (success, error, warning, info)
     * @param {number} duration - 표시 시간 (ms)
     */
    function showToast(message, type = 'info', duration = 3000) {
        const toast = dom.create('div', {
            className: `toast toast-${type}`,
            style: 'position: fixed; top: 20px; right: 20px; z-index: 1080; padding: 12px 20px; border-radius: 8px; color: white; font-weight: 500; transform: translateX(100%); transition: transform 0.3s ease;'
        }, message);

        // 타입별 배경색 설정
        const colors = {
            success: '#48bb78',
            error: '#f56565',
            warning: '#ed8936',
            info: '#4299e1'
        };
        toast.style.backgroundColor = colors[type] || colors.info;

        document.body.appendChild(toast);

        // 애니메이션
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);

        // 자동 제거
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, duration);
    }

    /**
     * 현재 위치 가져오기
     * @param {Object} options - Geolocation 옵션
     * @returns {Promise<{lat: number, lng: number}>}
     */
    function getCurrentLocation(options = {}) {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation is not supported'));
                return;
            }

            const defaultOptions = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000, // 5분
                ...options
            };

            navigator.geolocation.getCurrentPosition(
                position => {
                    resolve({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    });
                },
                error => {
                    reject(error);
                },
                defaultOptions
            );
        });
    }

    /**
     * 오프라인 상태 모니터링
     */
    function initOfflineMonitor() {
        const offlineIndicator = dom.$('#offline-indicator');
        
        function updateOnlineStatus() {
            const isOnline = navigator.onLine;
            window.SUNGSUYA_CONFIG.isOffline = !isOnline;
            
            if (isOnline) {
                if (offlineIndicator) {
                    dom.hide(offlineIndicator);
                }
                showToast('인터넷에 연결되었습니다', 'success', 2000);
            } else {
                if (offlineIndicator) {
                    dom.show(offlineIndicator);
                }
                showToast('오프라인 모드입니다', 'warning', 3000);
            }
        }

        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        
        // 초기 상태 확인
        updateOnlineStatus();
    }

    /**
     * PWA 설치 프롬프트
     */
    function initPWAInstaller() {
        let deferredPrompt;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // 설치 버튼 표시 로직
            showInstallButton();
        });

        function showInstallButton() {
            const installBtn = dom.create('button', {
                className: 'btn btn-primary install-btn',
                style: 'position: fixed; bottom: 20px; right: 20px; z-index: 1070;'
            }, '📱 앱 설치');

            installBtn.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    
                    if (outcome === 'accepted') {
                        showToast('앱이 설치되었습니다!', 'success');
                    }
                    
                    deferredPrompt = null;
                    installBtn.remove();
                }
            });

            document.body.appendChild(installBtn);

            // 5초 후 자동 숨김
            setTimeout(() => {
                if (installBtn.parentNode) {
                    installBtn.remove();
                }
            }, 5000);
        }
    }

    // 공개 API
    return {
        api,
        dom,
        storage,
        format,
        validate,
        debounce,
        throttle,
        lazyLoadImages,
        showToast,
        getCurrentLocation,
        initOfflineMonitor,
        initPWAInstaller
    };
})();

// DOM 로드 완료 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    SungsuyaUtils.initOfflineMonitor();
    SungsuyaUtils.initPWAInstaller();
    SungsuyaUtils.lazyLoadImages();
});
