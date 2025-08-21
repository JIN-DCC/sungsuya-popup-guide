/**
 * 성수야! V2 - Service Worker (개선 버전)
 * 다국어 오프라인 지원 포함
 * Version: 3.1.0
 * Updated: 2025-07-05
 */

const CACHE_VERSION = 'v3.1.0';
const CACHE_NAME = `sungsuya-${CACHE_VERSION}`;
const API_CACHE = `sungsuya-api-${CACHE_VERSION}`;
const IMAGE_CACHE = `sungsuya-images-${CACHE_VERSION}`;
const TRANSLATION_CACHE = `sungsuya-translations-${CACHE_VERSION}`;
const THEME_URL = '/wp-content/themes/sungsuya-v2-theme';

// 지원 언어
const SUPPORTED_LANGUAGES = ['ko', 'en', 'ja', 'zh-CN', 'zh-TW'];

// 캐시할 기본 페이지들 (모든 언어 버전)
const PAGES_TO_CACHE = [
    '/',
    '/places',
    '/tour-v2',
    '/about',
    '/contact',
    '/faq',
    '/privacy-policy',
    '/terms'
];

// 정적 리소스
const STATIC_RESOURCES = [
    THEME_URL + '/assets/css/variables.css',
    THEME_URL + '/assets/css/components.css',
    THEME_URL + '/assets/css/main.css',
    THEME_URL + '/assets/css/ui-simplify.css',
    THEME_URL + '/assets/css/section-simplify.css',
    THEME_URL + '/assets/css/performance-optimize.css',
    THEME_URL + '/assets/css/z-index-system.css',
    THEME_URL + '/assets/js/utils.js',
    THEME_URL + '/assets/js/app.js',
    THEME_URL + '/assets/js/performance.js',
    THEME_URL + '/assets/js/touch-gestures.js',
    THEME_URL + '/assets/js/auth-system.js',
    THEME_URL + '/assets/js/tour-planner-v2.js'
];

// 모든 언어 버전의 페이지 URL 생성
function generateMultilingualUrls() {
    const urls = [...STATIC_RESOURCES];
    
    // 기본 한국어 페이지
    urls.push(...PAGES_TO_CACHE);
    
    // 다른 언어 버전 추가
    SUPPORTED_LANGUAGES.forEach(lang => {
        if (lang !== 'ko') {
            PAGES_TO_CACHE.forEach(page => {
                urls.push(`/${lang}${page}`);
            });
        }
    });
    
    return urls;
}

// 중요 번역 키 (오프라인에서도 필요한 UI 텍스트)
const ESSENTIAL_TRANSLATIONS = {
    'en': {
        'offline_title': 'Offline Mode',
        'offline_message': 'You are currently offline. Some features may be limited.',
        'retry': 'Retry',
        'cached_data': 'Showing cached data',
        'last_updated': 'Last updated',
        'realtime_unavailable': 'Real-time data is not available offline',
        'recently_visited': 'Recently Visited',
        'no_cached_places': 'No cached places available'
    },
    'ja': {
        'offline_title': 'オフラインモード',
        'offline_message': '現在オフラインです。一部の機能が制限される場合があります。',
        'retry': '再試行',
        'cached_data': 'キャッシュされたデータを表示',
        'last_updated': '最終更新',
        'realtime_unavailable': 'リアルタイムデータはオフラインでは利用できません',
        'recently_visited': '最近訪問した場所',
        'no_cached_places': 'キャッシュされた場所がありません'
    },
    'zh-CN': {
        'offline_title': '离线模式',
        'offline_message': '您当前处于离线状态。某些功能可能受限。',
        'retry': '重试',
        'cached_data': '显示缓存数据',
        'last_updated': '最后更新',
        'realtime_unavailable': '实时数据在离线状态下不可用',
        'recently_visited': '最近访问',
        'no_cached_places': '没有缓存的地点'
    },
    'zh-TW': {
        'offline_title': '離線模式',
        'offline_message': '您當前處於離線狀態。某些功能可能受限。',
        'retry': '重試',
        'cached_data': '顯示緩存數據',
        'last_updated': '最後更新',
        'realtime_unavailable': '實時數據在離線狀態下不可用',
        'recently_visited': '最近訪問',
        'no_cached_places': '沒有緩存的地點'
    }
};

// 서비스 워커 설치
self.addEventListener('install', (event) => {
    console.log('🔧 성수야! 서비스 워커 v3.1.0 설치 중...');
    
    event.waitUntil(
        Promise.all([
            // 정적 리소스 및 다국어 페이지 캐싱
            caches.open(CACHE_NAME).then((cache) => {
                console.log('📦 정적 리소스 및 다국어 페이지 캐싱...');
                const allUrls = generateMultilingualUrls();
                
                return Promise.allSettled(
                    allUrls.map(url => 
                        cache.add(url).catch(e => console.log(`캐싱 실패: ${url}`, e))
                    )
                );
            }),
            // API 캐시 생성
            caches.open(API_CACHE),
            // 이미지 캐시 생성
            caches.open(IMAGE_CACHE),
            // 번역 캐시 생성 및 기본 번역 저장
            caches.open(TRANSLATION_CACHE).then((cache) => {
                console.log('🌐 기본 번역 데이터 캐싱...');
                const translationResponses = Object.entries(ESSENTIAL_TRANSLATIONS).map(([lang, translations]) => {
                    return cache.put(
                        `/translations/${lang}`,
                        new Response(JSON.stringify(translations), {
                            headers: { 'Content-Type': 'application/json' }
                        })
                    );
                });
                return Promise.all(translationResponses);
            })
        ]).then(() => {
            console.log('✅ 서비스 워커 설치 완료');
            return self.skipWaiting();
        })
    );
});

// 서비스 워커 활성화
self.addEventListener('activate', (event) => {
    console.log('⚡ 성수야! 서비스 워커 v3.1.0 활성화 중...');
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
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
    const urlObj = new URL(url);
    
    // GET 요청만 캐시 처리
    if (method !== 'GET') {
        return;
    }
    
    // GTranslate API 요청 캐싱
    if (url.includes('translate.googleapis.com') || url.includes('gtranslate.io')) {
        event.respondWith(handleTranslationRequest(request));
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
    
    // 페이지 요청 처리 (다국어 지원)
    if (request.mode === 'navigate') {
        event.respondWith(handleNavigationRequest(request));
        return;
    }
    
    // 기타 요청은 네트워크 우선
    event.respondWith(
        fetch(request).catch(() => {
            return caches.match(request);
        })
    );
});

// 번역 요청 처리 (캐시 우선)
async function handleTranslationRequest(request) {
    const translationCache = await caches.open(TRANSLATION_CACHE);
    const cachedResponse = await translationCache.match(request);
    
    if (cachedResponse) {
        // 백그라운드에서 업데이트 시도
        fetch(request).then(response => {
            if (response.ok) {
                translationCache.put(request, response);
            }
        }).catch(() => {});
        
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // 번역 결과 캐싱
            translationCache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('🌐 번역 로드 실패, 기본 언어로 대체');
        
        // 오프라인 시 기본 번역 반환
        const lang = detectLanguageFromUrl(request.url);
        const cachedTranslations = await translationCache.match(`/translations/${lang}`);
        
        if (cachedTranslations) {
            return cachedTranslations;
        }
        
        // 기본 한국어 반환
        return new Response(JSON.stringify({ message: '오프라인 상태입니다' }), {
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// API 요청 처리 (다국어 지원 개선)
async function handleApiRequest(request) {
    const apiCache = await caches.open(API_CACHE);
    const lang = detectLanguageFromRequest(request);
    const url = new URL(request.url);
    
    // 실시간 업데이트가 필요한 엔드포인트 체크
    const isRealtimeContent = url.pathname.includes('/realtime') || 
                             url.pathname.includes('/live') ||
                             url.pathname.includes('/current');
    
    try {
        const networkResponse = await Promise.race([
            fetch(request),
            new Promise((_, reject) => 
                setTimeout(() => reject(new Error('Network timeout')), 5000)
            )
        ]);
        
        if (networkResponse.ok) {
            // 실시간 콘텐츠가 아닌 경우만 캐싱
            if (!isRealtimeContent) {
                apiCache.put(request, networkResponse.clone());
            }
            
            const responseData = await networkResponse.json();
            return new Response(
                JSON.stringify({
                    ...responseData,
                    offline: false,
                    language: lang,
                    fetchedAt: new Date().toISOString(),
                    isRealtime: isRealtimeContent
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
        
        // 실시간 콘텐츠는 캐시 사용 안함
        if (isRealtimeContent) {
            return new Response(
                JSON.stringify({ 
                    error: ESSENTIAL_TRANSLATIONS[lang]?.realtime_unavailable || '실시간 데이터는 오프라인에서 사용할 수 없습니다',
                    offline: true,
                    language: lang,
                    isRealtime: true
                }),
                {
                    status: 503,
                    headers: { 'Content-Type': 'application/json' }
                }
            );
        }
        
        const cachedResponse = await apiCache.match(request);
        if (cachedResponse) {
            const cachedData = await cachedResponse.json();
            const offlineMessage = ESSENTIAL_TRANSLATIONS[lang]?.offline_message || '오프라인 모드 - 캐시된 데이터';
            const lastUpdated = ESSENTIAL_TRANSLATIONS[lang]?.last_updated || '최종 업데이트';
            
            return new Response(
                JSON.stringify({
                    ...cachedData,
                    offline: true,
                    language: lang,
                    cachedAt: cachedResponse.headers.get('date'),
                    message: `${offlineMessage} (${lastUpdated}: ${new Date(cachedResponse.headers.get('date')).toLocaleString(lang)})`
                }),
                {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' }
                }
            );
        }
        
        // 캐시에도 없으면 오프라인 응답
        const offlineError = ESSENTIAL_TRANSLATIONS[lang]?.offline_title || '오프라인 상태입니다';
        
        return new Response(
            JSON.stringify({ 
                error: offlineError,
                offline: true,
                language: lang,
                places: [],
                stores: [],
                message: ESSENTIAL_TRANSLATIONS[lang]?.offline_message || '인터넷 연결을 확인해주세요'
            }),
            {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

// 페이지 네비게이션 처리 (다국어 지원)
async function handleNavigationRequest(request) {
    const urlObj = new URL(request.url);
    const pathname = urlObj.pathname;
    const lang = detectLanguageFromPath(pathname);
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // 페이지 캐싱
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
            
            // 개별 장소 페이지인 경우 추가 처리
            if (pathname.includes('/places/') && !pathname.endsWith('/places/')) {
                console.log('📍 장소 상세 페이지 캐싱:', pathname);
                // 최근 방문한 장소 목록 업데이트
                updateRecentlyVisitedPlaces(pathname);
            }
        }
        
        return networkResponse;
    } catch (error) {
        console.log('🌐 페이지 로드 실패, 캐시 확인:', error);
        
        // 캐시된 페이지 확인
        const cache = await caches.open(CACHE_NAME);
        const cachedResponse = await cache.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // 언어별 캐시 페이지 확인
        const basePath = pathname.replace(/^\/(en|ja|zh-CN|zh-TW)/, '');
        const cachedBasePage = await cache.match(basePath);
        
        if (cachedBasePage) {
            // 캐시된 기본 페이지에 번역 적용 시도
            return cachedBasePage;
        }
        
        // 개별 장소 페이지 오프라인 처리
        if (pathname.includes('/places/') && !pathname.endsWith('/places/')) {
            return generateOfflinePlaceDetailPage(lang);
        }
        
        // 오프라인 페이지 반환
        return generateOfflinePage(lang);
    }
}

// 최근 방문한 장소 업데이트 (최대 20개)
async function updateRecentlyVisitedPlaces(pathname) {
    try {
        const cache = await caches.open(CACHE_NAME);
        const recentPlacesKey = '/recently-visited-places';
        
        // 기존 목록 가져오기
        const cachedList = await cache.match(recentPlacesKey);
        let recentPlaces = [];
        
        if (cachedList) {
            recentPlaces = await cachedList.json();
        }
        
        // 새 장소 추가 (중복 제거)
        recentPlaces = recentPlaces.filter(place => place !== pathname);
        recentPlaces.unshift(pathname);
        
        // 최대 20개만 유지
        if (recentPlaces.length > 20) {
            recentPlaces = recentPlaces.slice(0, 20);
        }
        
        // 목록 저장
        await cache.put(recentPlacesKey, new Response(
            JSON.stringify(recentPlaces),
            { headers: { 'Content-Type': 'application/json' } }
        ));
        
        console.log('📍 최근 방문 장소 업데이트:', recentPlaces.length + '개');
    } catch (error) {
        console.error('최근 방문 장소 업데이트 실패:', error);
    }
}

// 개별 장소 오프라인 페이지 생성
function generateOfflinePlaceDetailPage(lang = 'ko') {
    const translations = ESSENTIAL_TRANSLATIONS[lang] || ESSENTIAL_TRANSLATIONS.en;
    const isKorean = lang === 'ko';
    
    return new Response(`
        <!DOCTYPE html>
        <html lang="${lang}">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>${isKorean ? '오프라인' : translations.offline_title} - 성수야!</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    margin: 0; padding: 0;
                    background: #f5f5f5;
                    color: #333;
                }
                .container {
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .offline-notice {
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    border-radius: 8px;
                    padding: 20px;
                    margin-bottom: 30px;
                    text-align: center;
                }
                .offline-notice h2 {
                    color: #856404;
                    margin: 0 0 10px 0;
                }
                .recent-places {
                    background: white;
                    border-radius: 8px;
                    padding: 20px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .recent-places h3 {
                    margin: 0 0 20px 0;
                }
                .place-list {
                    list-style: none;
                    padding: 0;
                    margin: 0;
                }
                .place-item {
                    padding: 10px 0;
                    border-bottom: 1px solid #eee;
                }
                .place-item:last-child {
                    border-bottom: none;
                }
                .place-item a {
                    color: #667eea;
                    text-decoration: none;
                }
                .place-item a:hover {
                    text-decoration: underline;
                }
                .btn-back {
                    display: inline-block;
                    padding: 10px 20px;
                    background: #667eea;
                    color: white;
                    text-decoration: none;
                    border-radius: 6px;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="offline-notice">
                    <h2>📶 ${isKorean ? '오프라인 상태' : translations.offline_title}</h2>
                    <p>${isKorean ? '이 장소의 상세 정보는 오프라인에서 볼 수 없습니다.' : 'Place details are not available offline.'}</p>
                    <p>${isKorean ? '인터넷 연결 후 다시 시도해주세요.' : 'Please connect to the internet and try again.'}</p>
                </div>
                
                <div class="recent-places">
                    <h3>${isKorean ? '최근 방문한 장소' : 'Recently Visited Places'}</h3>
                    <p>${isKorean ? '아래 장소들은 캐시되어 오프라인에서도 볼 수 있습니다:' : 'These places are cached and available offline:'}</p>
                    <ul class="place-list" id="recent-places-list">
                        <li class="place-item">${isKorean ? '캐시된 장소를 불러오는 중...' : 'Loading cached places...'}</li>
                    </ul>
                </div>
                
                <a href="/places" class="btn-back">${isKorean ? '장소 목록으로' : 'Back to Places'}</a>
            </div>
            
            <script>
                // 최근 방문한 장소 목록 로드
                async function loadRecentPlaces() {
                    try {
                        const cache = await caches.open('sungsuya-v3.1.0');
                        const response = await cache.match('/recently-visited-places');
                        
                        if (response) {
                            const places = await response.json();
                            const listEl = document.getElementById('recent-places-list');
                            
                            if (places.length > 0) {
                                listEl.innerHTML = places.map(place => {
                                    const placeName = place.split('/').pop().replace(/-/g, ' ');
                                    return '<li class="place-item"><a href="' + place + '">' + placeName + '</a></li>';
                                }).join('');
                            } else {
                                listEl.innerHTML = '<li class="place-item">${isKorean ? '최근 방문한 장소가 없습니다.' : 'No recently visited places.'}</li>';
                            }
                        }
                    } catch (error) {
                        console.error('Failed to load recent places:', error);
                    }
                }
                
                loadRecentPlaces();
                
                // 온라인 복귀 시 새로고침
                window.addEventListener('online', () => {
                    setTimeout(() => window.location.reload(), 1000);
                });
            </script>
        </body>
        </html>
    `, {
        headers: { 
            'Content-Type': 'text/html; charset=utf-8',
            'Content-Language': lang
        }
    });
}

// 언어 감지 헬퍼 함수들
function detectLanguageFromRequest(request) {
    const url = new URL(request.url);
    const lang = detectLanguageFromPath(url.pathname);
    
    if (lang) return lang;
    
    // Accept-Language 헤더에서 감지
    const acceptLang = request.headers.get('Accept-Language');
    if (acceptLang) {
        const primaryLang = acceptLang.split(',')[0].split('-')[0];
        if (SUPPORTED_LANGUAGES.includes(primaryLang)) {
            return primaryLang;
        }
    }
    
    return 'ko'; // 기본값
}

function detectLanguageFromPath(pathname) {
    const match = pathname.match(/^\/(en|ja|zh-CN|zh-TW)/);
    return match ? match[1] : 'ko';
}

function detectLanguageFromUrl(url) {
    const urlObj = new URL(url);
    const langParam = urlObj.searchParams.get('lang') || urlObj.searchParams.get('language');
    
    if (langParam && SUPPORTED_LANGUAGES.includes(langParam)) {
        return langParam;
    }
    
    return detectLanguageFromPath(urlObj.pathname);
}

// 오프라인 페이지 생성 (다국어 지원)
function generateOfflinePage(lang = 'ko') {
    const translations = ESSENTIAL_TRANSLATIONS[lang] || ESSENTIAL_TRANSLATIONS.en;
    const isKorean = lang === 'ko';
    
    return new Response(`
        <!DOCTYPE html>
        <html lang="${lang}">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>${isKorean ? '오프라인' : translations.offline_title} - 성수야!</title>
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
                .cached-notice {
                    margin-top: 2rem; padding: 1rem;
                    background: rgba(255,255,255,0.1);
                    border-radius: 8px; font-size: 0.9rem;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>📶 ${isKorean ? '오프라인 상태' : translations.offline_title}</h1>
                <p>${isKorean ? '인터넷 연결을 확인해주세요.<br>연결이 복구되면 자동으로 다시 시도합니다.' : translations.offline_message}</p>
                <a href="/" class="btn">${isKorean ? '다시 시도' : translations.retry}</a>
                <div class="cached-notice">
                    ${isKorean ? '캐시된 페이지를 보실 수 있습니다' : translations.cached_data}
                </div>
            </div>
            <script>
                // 온라인 상태 복구 시 자동 새로고침
                window.addEventListener('online', () => {
                    setTimeout(() => window.location.reload(), 1000);
                });
                
                // 언어 설정 유지
                localStorage.setItem('preferred_language', '${lang}');
            </script>
        </body>
        </html>
    `, {
        headers: { 
            'Content-Type': 'text/html; charset=utf-8',
            'Content-Language': lang
        }
    });
}

// 정적 리소스 처리 (기존과 동일)
async function handleStaticRequest(request) {
    const staticCache = await caches.open(CACHE_NAME);
    const cachedResponse = await staticCache.match(request);
    
    if (cachedResponse) {
        fetch(request).then(response => {
            if (response.ok) {
                staticCache.put(request, response);
            }
        }).catch(() => {});
        
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            staticCache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('📄 정적 리소스 로드 실패:', request.url);
        return new Response('리소스를 찾을 수 없습니다', {
            status: 404,
            headers: { 'Content-Type': 'text/plain; charset=utf-8' }
        });
    }
}

// 이미지 요청 처리 (다국어 플레이스홀더)
async function handleImageRequest(request) {
    const imageCache = await caches.open(IMAGE_CACHE);
    const cachedResponse = await imageCache.match(request);
    
    if (cachedResponse) {
        fetch(request).then(response => {
            if (response.ok && response.headers.get('content-length') < 1000000) {
                imageCache.put(request, response);
            }
        }).catch(() => {});
        
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const contentLength = networkResponse.headers.get('content-length');
            if (!contentLength || parseInt(contentLength) < 1000000) { // 1MB 이하만 캐싱
                imageCache.put(request, networkResponse.clone());
            }
        }
        
        return networkResponse;
    } catch (error) {
        console.log('🖼️ 이미지 로드 실패:', request.url);
        
        const lang = detectLanguageFromRequest(request);
        const message = lang === 'ko' ? '이미지를 불러올 수 없습니다' : 'Image unavailable';
        const submessage = ESSENTIAL_TRANSLATIONS[lang]?.offline_title || '오프라인 상태';
        
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
                        <tspan x="0" dy="-10">${message}</tspan>
                        <tspan x="0" dy="20" font-size="12">${submessage}</tspan>
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

// 백그라운드 동기화 및 기타 이벤트 핸들러는 기존과 동일...

// 메시지 핸들러 추가
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'CACHE_LANGUAGE_CONTENT') {
        // 특정 언어의 콘텐츠를 추가로 캐싱
        const { language, urls } = event.data;
        cacheLanguageContent(language, urls);
    }
});

// 특정 언어 콘텐츠 캐싱
async function cacheLanguageContent(language, urls) {
    const cache = await caches.open(CACHE_NAME);
    const translationCache = await caches.open(TRANSLATION_CACHE);
    
    console.log(`🌐 ${language} 언어 콘텐츠 캐싱 시작...`);
    
    const cachePromises = urls.map(url => {
        const langUrl = language === 'ko' ? url : `/${language}${url}`;
        return cache.add(langUrl).catch(e => console.log(`캐싱 실패: ${langUrl}`, e));
    });
    
    await Promise.allSettled(cachePromises);
    console.log(`✅ ${language} 언어 콘텐츠 캐싱 완료`);
}

console.log('🚀 성수야! 서비스 워커 v3.1.0 로드됨 - 다국어 오프라인 지원 포함');
