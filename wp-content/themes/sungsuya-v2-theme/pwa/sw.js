/**
 * 성수야! V2 - Service Worker
 * Phase 3 Task 3.3 - 오프라인 지원 개선
 * Version: 3.0.0
 */

const CACHE_VERSION = 'v3.0.0';
const CACHE_NAME = `sungsuya-${CACHE_VERSION}`;
const API_CACHE = `sungsuya-api-${CACHE_VERSION}`;
const IMAGE_CACHE = `sungsuya-images-${CACHE_VERSION}`;
const THEME_URL = '/wp-content/themes/sungsuya-v2-theme';

// 캐시할 기본 리소스
const STATIC_CACHE_URLS = [
    '/',
    '/places',
    '/planner',
    THEME_URL + '/assets/css/variables.css',
    THEME_URL + '/assets/css/components.css',
    THEME_URL + '/assets/css/main.css',
    THEME_URL + '/assets/css/ui-simplify.css',
    THEME_URL + '/assets/css/section-simplify.css',
    THEME_URL + '/assets/css/performance-optimize.css',
    THEME_URL + '/assets/js/utils.js',
    THEME_URL + '/assets/js/app.js',
    THEME_URL + '/assets/js/performance.js',
    THEME_URL + '/assets/js/touch-gestures.js'
];

// 오프라인 큐 관리
const OFFLINE_QUEUE = [];
let syncInProgress = false;

// 서비스 워커 설치
self.addEventListener('install', (event) => {
    console.log('🔧 성수야! 서비스 워커 v3.0.0 설치 중...');
    
    event.waitUntil(
        Promise.all([
            caches.open(CACHE_NAME).then((cache) => {
                console.log('📦 정적 리소스 캐싱...');
                return cache.addAll(STATIC_CACHE_URLS).catch(err => {
                    console.error('일부 리소스 캐싱 실패:', err);
                    // 실패한 리소스는 개별적으로 처리
                    return Promise.all(
                        STATIC_CACHE_URLS.map(url => 
                            cache.add(url).catch(e => console.log(`캐싱 실패: ${url}`))
                        )
                    );
                });
            }),
            caches.open(API_CACHE),
            caches.open(IMAGE_CACHE)
        ]).then(() => {
            console.log('✅ 서비스 워커 설치 완료');
            return self.skipWaiting();
        })
    );
});

// 서비스 워커 활성화
self.addEventListener('activate', (event) => {
    console.log('⚡ 성수야! 서비스 워커 v3.0.0 활성화 중...');
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    // 현재 버전이 아닌 캐시는 모두 삭제
                    if (!cacheName.includes(CACHE_VERSION)) {
                        console.log('🗑️ 이전 캐시 삭제:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            console.log('✅ 서비스 워커 활성화 완료');
            return self.clients.claim();
        })
    );
});

// 네트워크 요청 가로채기
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const { url, method } = request;
    
    // GET 요청만 캐시 처리
    if (method !== 'GET') {
        return;
    }
    
    // API 요청 처리
    if (url.includes('/wp-json/sungsuya/v2/')) {
        event.respondWith(handleApiRequest(request));
        return;
    }
    
    // 이미지 요청 처리
    if (url.match(/\.(jpg|jpeg|png|webp|svg|gif)$/i)) {
        event.respondWith(handleImageRequest(request));
        return;
    }
    
    // CSS/JS 정적 리소스 처리
    if (url.includes(THEME_URL) || url.match(/\.(css|js)$/)) {
        event.respondWith(handleStaticRequest(request));
        return;
    }
    
    // 페이지 요청 처리 (SPA)
    if (request.mode === 'navigate') {
        event.respondWith(handleNavigationRequest(request));
        return;
    }
});

// API 요청 처리 (네트워크 우선, 캐시 대비)
async function handleApiRequest(request) {
    const apiCache = await caches.open(API_CACHE);
    
    try {
        // 네트워크 요청 시도 (5초 타임아웃)
        const networkResponse = await Promise.race([
            fetch(request),
            new Promise((_, reject) => 
                setTimeout(() => reject(new Error('Network timeout')), 5000)
            )
        ]);
        
        if (networkResponse.ok) {
            // 성공적인 응답은 캐시에 저장
            apiCache.put(request, networkResponse.clone());
            
            // 응답에 네트워크 상태 추가
            const responseData = await networkResponse.json();
            return new Response(
                JSON.stringify({
                    ...responseData,
                    offline: false,
                    fetchedAt: new Date().toISOString()
                }),
                {
                    status: networkResponse.status,
                    headers: networkResponse.headers
                }
            );
        }
        
        throw new Error('Network response not ok');
    } catch (error) {
        console.log('📶 API 네트워크 오류, 캐시 확인:', error.message);
        
        const cachedResponse = await apiCache.match(request);
        if (cachedResponse) {
            const cachedData = await cachedResponse.json();
            return new Response(
                JSON.stringify({
                    ...cachedData,
                    offline: true,
                    cachedAt: cachedResponse.headers.get('date'),
                    message: '오프라인 모드 - 캐시된 데이터'
                }),
                {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' }
                }
            );
        }
        
        // 캐시에도 없으면 오프라인 응답
        return new Response(
            JSON.stringify({ 
                error: '오프라인 상태입니다',
                offline: true,
                places: [],
                stores: [],
                message: '인터넷 연결을 확인해주세요'
            }),
            {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

// 이미지 요청 처리 (캐시 우선, 네트워크 대비)
async function handleImageRequest(request) {
    const imageCache = await caches.open(IMAGE_CACHE);
    const cachedResponse = await imageCache.match(request);
    
    if (cachedResponse) {
        // 백그라운드에서 업데이트 시도
        fetch(request).then(response => {
            if (response.ok) {
                imageCache.put(request, response);
            }
        }).catch(() => {});
        
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // 이미지는 용량이 크므로 선택적으로 캐싱
            const contentLength = networkResponse.headers.get('content-length');
            if (!contentLength || parseInt(contentLength) < 500000) { // 500KB 이하만 캐싱
                imageCache.put(request, networkResponse.clone());
            }
        }
        
        return networkResponse;
    } catch (error) {
        console.log('🖼️ 이미지 로드 실패:', request.url);
        
        // 개선된 플레이스홀더 SVG
        const placeholderSvg = `
            <svg width="400" height="300" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#f3f4f6;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#e5e7eb;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <rect width="100%" height="100%" fill="url(#grad)"/>
                <g transform="translate(200,150)">
                    <text text-anchor="middle" font-family="system-ui" font-size="14" fill="#9ca3af">
                        <tspan x="0" dy="-10">이미지를 불러올 수 없습니다</tspan>
                        <tspan x="0" dy="20" font-size="12">오프라인 상태</tspan>
                    </text>
                </g>
            </svg>
        `;
        
        return new Response(placeholderSvg, {
            headers: { 
                'Content-Type': 'image/svg+xml',
                'Cache-Control': 'no-store'
            }
        });
    }
}

// 정적 리소스 처리 (캐시 우선)
async function handleStaticRequest(request) {
    const staticCache = await caches.open(CACHE_NAME);
    const cachedResponse = await staticCache.match(request);
    
    if (cachedResponse) {
        // 백그라운드에서 업데이트 체크
        fetch(request).then(response => {
            if (response.ok) {
                const cacheControl = response.headers.get('cache-control');
                // 캐시 컨트롤이 no-cache가 아닌 경우만 업데이트
                if (!cacheControl || !cacheControl.includes('no-cache')) {
                    staticCache.put(request, response);
                }
            }
        }).catch(() => {});
        
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // 정적 리소스는 항상 캐싱
            staticCache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('📄 정적 리소스 로드 실패:', request.url);
        
        // 오프라인 페이지로 리다이렉트
        return caches.match('/offline').then(response => {
            if (response) return response;
            
            return new Response('오프라인 상태입니다', {
                status: 503,
                headers: { 'Content-Type': 'text/plain; charset=utf-8' }
            });
        });
    }
}

// 페이지 네비게이션 처리 (SPA)
async function handleNavigationRequest(request) {
    try {
        const networkResponse = await fetch(request);
        return networkResponse;
    } catch (error) {
        console.log('🌐 페이지 로드 실패, 오프라인 페이지 제공:', error);
        
        // 캐시된 메인 페이지 또는 오프라인 페이지 반환
        const cache = await caches.open(CACHE_NAME);
        const cachedMain = await cache.match('/');
        
        if (cachedMain) {
            return cachedMain;
        }
        
        // 오프라인 페이지 반환
        return new Response(`
            <!DOCTYPE html>
            <html lang="ko">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>오프라인 - 성수야!</title>
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        margin: 0; padding: 0;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white; height: 100vh;
                        display: flex; align-items: center; justify-content: center;
                        text-align: center;
                    }
                    .container { max-width: 400px; padding: 2rem; }
                    h1 { font-size: 2rem; margin-bottom: 1rem; }
                    p { font-size: 1.1rem; line-height: 1.6; margin-bottom: 2rem; }
                    .btn {
                        display: inline-block; padding: 12px 24px;
                        background: rgba(255,255,255,0.2); color: white;
                        text-decoration: none; border-radius: 8px;
                        transition: background 0.3s;
                    }
                    .btn:hover { background: rgba(255,255,255,0.3); }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>📶 오프라인 상태</h1>
                    <p>인터넷 연결을 확인해주세요.<br>연결이 복구되면 자동으로 다시 시도합니다.</p>
                    <a href="/" class="btn">다시 시도</a>
                </div>
                <script>
                    window.addEventListener('online', () => {
                        window.location.reload();
                    });
                </script>
            </body>
            </html>
        `, {
            headers: { 'Content-Type': 'text/html' }
        });
    }
}

// 백그라운드 동기화
self.addEventListener('sync', (event) => {
    console.log('🔄 백그라운드 동기화 이벤트:', event.tag);
    
    if (event.tag === 'sync-offline-data') {
        event.waitUntil(syncOfflineData());
    } else if (event.tag === 'update-caches') {
        event.waitUntil(updateCaches());
    }
});

// 오프라인 데이터 동기화
async function syncOfflineData() {
    console.log('📤 오프라인 데이터 동기화 시작...');
    
    // IndexedDB에서 오프라인 큐 가져오기
    const db = await openDB();
    const tx = db.transaction('offline-queue', 'readwrite');
    const store = tx.objectStore('offline-queue');
    const requests = await store.getAll();
    
    for (const request of requests) {
        try {
            const response = await fetch(request.url, {
                method: request.method,
                headers: request.headers,
                body: request.body
            });
            
            if (response.ok) {
                // 성공하면 큐에서 제거
                await store.delete(request.id);
                console.log('✅ 동기화 성공:', request.url);
                
                // 클라이언트에 알림
                self.clients.matchAll().then(clients => {
                    clients.forEach(client => {
                        client.postMessage({
                            type: 'sync-complete',
                            url: request.url
                        });
                    });
                });
            }
        } catch (error) {
            console.log('❌ 동기화 실패:', request.url, error);
        }
    }
}

// 캐시 업데이트
async function updateCaches() {
    console.log('🔄 캐시 업데이트 시작...');
    
    const cache = await caches.open(CACHE_NAME);
    const requests = await cache.keys();
    
    // 정적 리소스 업데이트
    for (const request of requests) {
        if (request.url.includes('/assets/')) {
            try {
                const response = await fetch(request);
                if (response.ok) {
                    await cache.put(request, response);
                }
            } catch (error) {
                console.log('캐시 업데이트 실패:', request.url);
            }
        }
    }
}

// IndexedDB 헬퍼
async function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('sungsuya-offline', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('offline-queue')) {
                db.createObjectStore('offline-queue', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

// 주기적 캐시 정리
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CLEAN_OLD_CACHES') {
        cleanOldCaches();
    }
    
    if (event.data && event.data.type === 'GET_CACHE_SIZE') {
        getCacheSize().then(size => {
            event.ports[0].postMessage({ cacheSize: size });
        });
    }
});

// 오래된 캐시 정리
async function cleanOldCaches() {
    const cacheWhitelist = [CACHE_NAME, API_CACHE, IMAGE_CACHE];
    const cacheNames = await caches.keys();
    
    const deletePromises = cacheNames.map(cacheName => {
        if (!cacheWhitelist.includes(cacheName)) {
            console.log('🗑️ 오래된 캐시 삭제:', cacheName);
            return caches.delete(cacheName);
        }
    });
    
    await Promise.all(deletePromises);
    
    // 일주일 이상 된 캐시 항목 정리
    for (const cacheName of cacheWhitelist) {
        const cache = await caches.open(cacheName);
        const requests = await cache.keys();
        const oneWeekAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
        
        for (const request of requests) {
            const response = await cache.match(request);
            const dateHeader = response.headers.get('date');
            
            if (dateHeader && new Date(dateHeader).getTime() < oneWeekAgo) {
                await cache.delete(request);
                console.log('🗑️ 오래된 캐시 항목 삭제:', request.url);
            }
        }
    }
}

// 캐시 크기 계산
async function getCacheSize() {
    if ('estimate' in navigator.storage) {
        const estimate = await navigator.storage.estimate();
        return estimate.usage || 0;
    }
    return 0;
}

// 푸시 알림 지원
self.addEventListener('push', (event) => {
    const options = {
        body: event.data ? event.data.text() : '새로운 업데이트가 있습니다',
        icon: '/wp-content/themes/sungsuya-v2-theme/assets/images/icon-192.png',
        badge: '/wp-content/themes/sungsuya-v2-theme/assets/images/icon-72.png',
        vibrate: [200, 100, 200],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        }
    };
    
    event.waitUntil(
        self.registration.showNotification('성수야!', options)
    );
});

// 알림 클릭 핸들러
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    event.waitUntil(
        clients.openWindow('/')
    );
});
