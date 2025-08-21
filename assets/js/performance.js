/**
 * 성수야! V2 - 성능 최적화 JavaScript
 * Phase 3: Task 3.1 - 로딩 속도 개선
 * 
 * 목적:
 * 1. 이미지 지연 로딩
 * 2. 리소스 프리로딩
 * 3. 코드 스플리팅
 * 4. 캐싱 전략
 */

(function() {
    'use strict';

    // ==========================================
    // 1. 이미지 지연 로딩 강화
    // ==========================================
    
    const lazyImageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                
                // data-src가 있으면 src로 이동
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
                
                // srcset 처리
                if (img.dataset.srcset) {
                    img.srcset = img.dataset.srcset;
                    img.removeAttribute('data-srcset');
                }
                
                // 로딩 완료 후 클래스 추가
                img.addEventListener('load', () => {
                    img.classList.add('loaded');
                });
                
                // 관찰 중지
                lazyImageObserver.unobserve(img);
            }
        });
    }, {
        rootMargin: '50px 0px',
        threshold: 0.01
    });
    
    // 모든 지연 로딩 이미지 관찰
    document.addEventListener('DOMContentLoaded', () => {
        const lazyImages = document.querySelectorAll('img[loading="lazy"], img[data-src]');
        lazyImages.forEach(img => lazyImageObserver.observe(img));
    });
    
    // ==========================================
    // 2. 리소스 프리로딩/프리커넥트
    // ==========================================
    
    function addResourceHints() {
        const head = document.head;
        
        // DNS 프리페치
        const dnsPrefetch = [
            'https://maps.googleapis.com',
            'https://naveropenapi.apigw.ntruss.com',
            'https://maps.apigw.ntruss.com'
        ];
        
        dnsPrefetch.forEach(domain => {
            const link = document.createElement('link');
            link.rel = 'dns-prefetch';
            link.href = domain;
            head.appendChild(link);
        });
        
        // 프리커넥트 (중요 도메인)
        const preconnect = [
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com'
        ];
        
        preconnect.forEach(domain => {
            const link = document.createElement('link');
            link.rel = 'preconnect';
            link.href = domain;
            link.crossOrigin = 'anonymous';
            head.appendChild(link);
        });
    }
    
    // 페이지 로드 후 실행
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addResourceHints);
    } else {
        addResourceHints();
    }
    
    // ==========================================
    // 3. 동적 컴포넌트 로딩
    // ==========================================
    
    // 투어 플래너 지연 로딩
    function loadTourPlanner() {
        if (!window.tourPlannerLoaded && document.querySelector('.tour-planner-container')) {
            const script = document.createElement('script');
            script.src = SUNGSUYA_CONFIG.themeUrl + '/assets/js/tour-planner.js';
            script.async = true;
            document.body.appendChild(script);
            window.tourPlannerLoaded = true;
        }
    }
    
    // 스크롤 시 또는 특정 조건에서 로드
    const tourPlannerObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadTourPlanner();
                tourPlannerObserver.disconnect();
            }
        });
    });
    
    const tourPlannerTrigger = document.querySelector('.tour-planner-trigger');
    if (tourPlannerTrigger) {
        tourPlannerObserver.observe(tourPlannerTrigger);
    }
    
    // ==========================================
    // 4. 요청 최적화 (Request Batching)
    // ==========================================
    
    const RequestBatcher = {
        queue: [],
        timeout: null,
        
        add(request) {
            this.queue.push(request);
            this.schedule();
        },
        
        schedule() {
            if (this.timeout) return;
            
            this.timeout = setTimeout(() => {
                this.flush();
            }, 50);
        },
        
        async flush() {
            if (this.queue.length === 0) return;
            
            const batch = this.queue.splice(0, this.queue.length);
            this.timeout = null;
            
            try {
                const responses = await Promise.all(
                    batch.map(req => fetch(req.url, req.options))
                );
                
                batch.forEach((req, index) => {
                    if (req.callback) {
                        req.callback(responses[index]);
                    }
                });
            } catch (error) {
                console.error('Batch request failed:', error);
            }
        }
    };
    
    // 전역으로 노출
    window.RequestBatcher = RequestBatcher;
    
    // ==========================================
    // 5. 스크롤 성능 최적화
    // ==========================================
    
    let scrollTimeout;
    let isScrolling = false;
    
    function optimizeScroll() {
        if (!isScrolling) {
            document.body.classList.add('is-scrolling');
            isScrolling = true;
        }
        
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            document.body.classList.remove('is-scrolling');
            isScrolling = false;
        }, 150);
    }
    
    // Passive 리스너로 성능 향상
    window.addEventListener('scroll', optimizeScroll, { passive: true });
    
    // ==========================================
    // 6. 로컬 스토리지 캐싱
    // ==========================================
    
    const CacheManager = {
        prefix: 'sungsuya_',
        ttl: 3600000, // 1시간
        
        set(key, value, customTTL) {
            const item = {
                value: value,
                timestamp: Date.now(),
                ttl: customTTL || this.ttl
            };
            
            try {
                localStorage.setItem(this.prefix + key, JSON.stringify(item));
            } catch (e) {
                // 스토리지 가득 찬 경우 오래된 항목 삭제
                this.cleanup();
                try {
                    localStorage.setItem(this.prefix + key, JSON.stringify(item));
                } catch (e2) {
                    console.warn('Failed to cache item:', key);
                }
            }
        },
        
        get(key) {
            try {
                const item = JSON.parse(localStorage.getItem(this.prefix + key));
                if (!item) return null;
                
                if (Date.now() - item.timestamp > item.ttl) {
                    localStorage.removeItem(this.prefix + key);
                    return null;
                }
                
                return item.value;
            } catch (e) {
                return null;
            }
        },
        
        cleanup() {
            const keys = Object.keys(localStorage);
            const now = Date.now();
            
            keys.forEach(key => {
                if (key.startsWith(this.prefix)) {
                    try {
                        const item = JSON.parse(localStorage.getItem(key));
                        if (now - item.timestamp > item.ttl) {
                            localStorage.removeItem(key);
                        }
                    } catch (e) {
                        localStorage.removeItem(key);
                    }
                }
            });
        }
    };
    
    // 전역으로 노출
    window.CacheManager = CacheManager;
    
    // ==========================================
    // 7. 웹 워커를 활용한 무거운 작업 처리 (지연 초기화)
    // ==========================================
    
    let worker = null;
    
    function initWorker() {
        if (worker) return worker;
        
        // 무거운 계산을 웹 워커로 이동 (예: 거리 계산)
        const workerCode = `
            self.addEventListener('message', function(e) {
                const { type, data } = e.data;
                
                if (type === 'calculateDistance') {
                    const { lat1, lon1, lat2, lon2 } = data;
                    const R = 6371;
                    const dLat = (lat2 - lat1) * Math.PI / 180;
                    const dLon = (lon2 - lon1) * Math.PI / 180;
                    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                        Math.sin(dLon/2) * Math.sin(dLon/2);
                    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                    const distance = R * c;
                    
                    self.postMessage({ type: 'distanceResult', result: distance });
                }
            });
        `;
        
        const blob = new Blob([workerCode], { type: 'application/javascript' });
        const workerUrl = URL.createObjectURL(blob);
        worker = new Worker(workerUrl);
        
        return worker;
    }
    
    window.calculateDistanceAsync = function(lat1, lon1, lat2, lon2) {
        return new Promise((resolve) => {
            const workerInstance = initWorker();
            
            workerInstance.postMessage({
                type: 'calculateDistance',
                data: { lat1, lon1, lat2, lon2 }
            });
            
            workerInstance.addEventListener('message', function handler(e) {
                if (e.data.type === 'distanceResult') {
                    resolve(e.data.result);
                    workerInstance.removeEventListener('message', handler);
                }
            });
        });
    };
    
    // ==========================================
    // 8. 성능 모니터링 (프로덕션에서는 비활성화)
    // ==========================================
    
    // 실제 사용자 성능 측정 - 개발 환경에서만 활성화
    if ('PerformanceObserver' in window && window.location.hostname === 'localhost') {
        // LCP (Largest Contentful Paint) 측정
        const lcpObserver = new PerformanceObserver((list) => {
            const entries = list.getEntries();
            const lastEntry = entries[entries.length - 1];
            // console.log('LCP:', lastEntry.renderTime || lastEntry.loadTime);
        });
        
        lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
        
        // FID (First Input Delay) 측정
        const fidObserver = new PerformanceObserver((list) => {
            const entries = list.getEntries();
            entries.forEach((entry) => {
                // console.log('FID:', entry.processingStart - entry.startTime);
            });
        });
        
        fidObserver.observe({ entryTypes: ['first-input'] });
    }
    
    // ==========================================
    // 9. 메모리 관리
    // ==========================================
    
    // 메모리 누수 방지를 위한 이벤트 리스너 관리
    const EventManager = {
        listeners: new Map(),
        
        add(element, event, handler, options) {
            if (!this.listeners.has(element)) {
                this.listeners.set(element, new Map());
            }
            
            const elementListeners = this.listeners.get(element);
            elementListeners.set(event, { handler, options });
            
            element.addEventListener(event, handler, options);
        },
        
        remove(element, event) {
            const elementListeners = this.listeners.get(element);
            if (!elementListeners) return;
            
            const listener = elementListeners.get(event);
            if (listener) {
                element.removeEventListener(event, listener.handler, listener.options);
                elementListeners.delete(event);
            }
            
            if (elementListeners.size === 0) {
                this.listeners.delete(element);
            }
        },
        
        removeAll(element) {
            const elementListeners = this.listeners.get(element);
            if (!elementListeners) return;
            
            elementListeners.forEach((listener, event) => {
                element.removeEventListener(event, listener.handler, listener.options);
            });
            
            this.listeners.delete(element);
        }
    };
    
    window.EventManager = EventManager;
    
    // ==========================================
    // 10. 초기화
    // ==========================================
    
    // DOM 준비 완료 시 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        // 캐시 정리
        CacheManager.cleanup();
        
        // 성능 최적화 클래스 추가
        document.documentElement.classList.add('performance-optimized');
        
        // console.log('Performance optimizations loaded');
    }
    
})();
