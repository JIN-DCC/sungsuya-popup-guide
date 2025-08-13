/**
 * 성수야! V2 - 메인 애플리케이션
 * 
 * SPA 라우팅 및 앱 전체 로직 관리
 * 
 * @package SungsuyaV2
 * @version 2.0.0
 */

window.SungsuyaApp = (function() {
    'use strict';

    // 유틸리티 단축 참조
    const { api, dom, storage, format, showToast } = window.SungsuyaUtils;

    // 앱 상태
    const state = {
        currentRoute: '',
        stores: [],
        places: [],
        allPlaces: [], // 🆕 통합 장소 데이터 (팝업스토어 + Places)
        selectedStores: [],
        isLoading: false,
        darkMode: false,
        currentFilter: 'all' // 🆕 현재 필터 상태
    };

    // 라우트 정의
    const routes = {
        '/': renderHomePage,
        '/stores': renderStoresPage,
        '/stores/:id': renderStoreDetailPage,
        // Places 관련 라우트는 WordPress 템플릿에서 처리
        // '/planner': renderPlannerPage // 🔥 비활성화: 새 워드프레스 템플릿 사용
    };

    /**
     * 앱 초기화
     */
    function init() {
        debugLog('🚀 성수야! V2 앱 시작');
        
        // 초기 상태 로드
        loadInitialState();
        
        // 라우터 초기화
        initRouter();
        
        // 이벤트 리스너 등록
        bindEvents();
        
        // 다크모드 초기화
        initDarkMode();
        
        // 초기 페이지 렌더링
        handleRoute();
        
        // 로딩 화면 숨김
        hideLoadingScreen();
    }

    /**
     * 초기 상태 로드
     */
    function loadInitialState() {
        // 로컬 스토리지에서 선택된 스토어 복원
        state.selectedStores = storage.get('selectedStores', []);
        state.darkMode = storage.get('darkMode', false);
        
        debugLog('📊 초기 상태 로드 완료:', state);
    }

    /**
     * 라우터 초기화
     */
    function initRouter() {
        // 브라우저 뒤로가기/앞으로가기 처리
        window.addEventListener('popstate', handleRoute);
        
        // 링크 클릭 이벤트 위임
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href^="/"]');
            if (link && !link.hasAttribute('download')) {
                const href = link.getAttribute('href');
                
                // Places 관련 URL은 SPA 라우팅 제외 (WordPress 템플릿 사용)
                if (href.startsWith('/places')) {
                    return; // 기본 링크 동작 허용
                }
                
                e.preventDefault();
                navigate(href);
            }
        });
    }

    /**
     * 페이지 네비게이션
     * @param {string} path - 이동할 경로
     * @param {boolean} replace - history 교체 여부
     */
    function navigate(path, replace = false) {
        if (replace) {
            history.replaceState(null, '', path);
        } else {
            history.pushState(null, '', path);
        }
        handleRoute();
    }

    /**
     * 현재 라우트 처리
     */
    function handleRoute() {
        const path = window.location.pathname;
        state.currentRoute = path;
        
        debugLog('🔄 라우트 변경:', path);
        
        // 라우트 매칭
        let handler = null;
        let params = {};
        
        for (const [pattern, routeHandler] of Object.entries(routes)) {
            const match = matchRoute(pattern, path);
            if (match) {
                handler = routeHandler;
                params = match.params;
                break;
            }
        }
        
        if (handler) {
            handler(params);
        } else {
            render404Page();
        }
        
        // 네비게이션 업데이트
        updateNavigation();
    }

    /**
     * 라우트 패턴 매칭
     * @param {string} pattern - 라우트 패턴
     * @param {string} path - 현재 경로
     * @returns {Object|null}
     */
    function matchRoute(pattern, path) {
        const patternParts = pattern.split('/');
        const pathParts = path.split('/');
        
        if (patternParts.length !== pathParts.length) {
            return null;
        }
        
        const params = {};
        
        for (let i = 0; i < patternParts.length; i++) {
            const patternPart = patternParts[i];
            const pathPart = pathParts[i];
            
            if (patternPart.startsWith(':')) {
                const paramName = patternPart.slice(1);
                params[paramName] = pathPart;
            } else if (patternPart !== pathPart) {
                return null;
            }
        }
        
        return { params };
    }

    /**
     * 로딩 화면 숨김
     */
    function hideLoadingScreen() {
        const loadingEl = dom.$('#app-loading');
        const mainEl = dom.$('#app-main');
        
        if (loadingEl && mainEl) {
            setTimeout(() => {
                dom.hide(loadingEl);
                mainEl.style.display = 'block';
            }, 500);
        }
    }

    /**
     * 홈페이지 렌더링
     */
    function renderHomePage() {
        const content = `
            <div class="hero">
                <div class="container">
                    <div class="hero-content">
                        <h1 class="hero-title">성수야! V2</h1>
                        <p class="hero-subtitle">
                            성수동의 모든 팝업스토어를 한눈에! 
                            나만의 투어 코스를 만들어보세요.
                        </p>
                        <div class="hero-buttons">
                            <a href="/places?place_type=popup_store" class="btn btn-primary btn-lg">
                                🏪 팝업스토어 둘러보기
                            </a>
                            <a href="/planner" class="btn btn-outline btn-lg">
                                🗺️ 투어 계획하기
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="container">
                <div class="page">
                    <div class="page-header">
                        <h2 class="page-title">인기 팝업스토어</h2>
                        <p class="page-subtitle">지금 성수동에서 가장 핫한 팝업스토어들을 만나보세요</p>
                    </div>
                    
                    <div id="featured-stores" class="stores-grid">
                        <!-- 인기 스토어 로딩 중 -->
                        ${renderStoresSkeleton(6)}
                    </div>
                    
                    <div class="text-center" style="margin-top: 3rem;">
                        <a href="/places?place_type=popup_store" class="btn btn-secondary">모든 팝업스토어 보기</a>
                    </div>
                </div>
                
                <!-- Places 섹션 추가 -->
                <div class="page" style="margin-top: 4rem;">
                    <div class="page-header">
                        <h2 class="page-title">성수동 장소</h2>
                        <p class="page-subtitle">맛집, 상설매장, 편의시설까지 성수동의 모든 장소를 둘러보세요</p>
                    </div>
                    
                    <!-- Places 카테고리 그리드 -->
                    <div class="places-categories" style="margin-bottom: 3rem;">
                        <div class="category-grid">
                            <a href="/places/restaurant/" class="category-card">
                                <div class="category-icon">🍽️</div>
                                <h3 class="category-title">맛집</h3>
                                <p class="category-description">성수동의 숨은 맛집들을 발견해보세요</p>
                            </a>
                            <a href="/places/retail-store/" class="category-card">
                                <div class="category-icon">🏬</div>
                                <h3 class="category-title">상설매장</h3>
                                <p class="category-description">특별한 쇼핑 경험을 만나보세요</p>
                            </a>
                            <a href="/places/facility/" class="category-card">
                                <div class="category-icon">🚻</div>
                                <h3 class="category-title">편의시설</h3>
                                <p class="category-description">성수동 방문 시 유용한 시설들</p>
                            </a>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="/places" class="btn btn-secondary">모든 장소 보기</a>
                    </div>
                </div>
            </div>
        `;
        
        renderPage(content);
        loadFeaturedStores();
    }

    /**
     * 스토어 목록 페이지 렌더링
     */
    function renderStoresPage() {
        const content = `
            <div class="container">
                <div class="page">
                    <div class="page-header">
                        <h1 class="page-title">팝업스토어</h1>
                        <p class="page-subtitle">성수동의 모든 팝업스토어를 한눈에 확인하세요</p>
                    </div>
                    
                    <!-- 필터 -->
                    <div class="filters" style="margin-bottom: 2rem;">
                        <div class="flex justify-center gap-4 flex-wrap">
                            <button class="btn btn-outline filter-btn active" data-category="all">전체</button>
                            <button class="btn btn-outline filter-btn" data-category="fashion">👗 패션</button>
                            <button class="btn btn-outline filter-btn" data-category="beauty">💄 뷰티</button>
                            <button class="btn btn-outline filter-btn" data-category="food">🍽️ 푸드</button>
                            <button class="btn btn-outline filter-btn" data-category="lifestyle">🏠 라이프스타일</button>
                            <button class="btn btn-outline filter-btn" data-category="art">🎨 아트</button>
                            <button class="btn btn-outline filter-btn" data-category="tech">📱 테크</button>
                            <button class="btn btn-outline filter-btn" data-category="sports">⚽ 스포츠</button>
                            <button class="btn btn-outline filter-btn" data-category="music">🎵 음악</button>
                            <button class="btn btn-outline filter-btn" data-category="book">📚 북</button>
                            <button class="btn btn-outline filter-btn" data-category="kids">🧸 키즈</button>
                            <button class="btn btn-outline filter-btn" data-category="pet">🐕 펫</button>
                            <button class="btn btn-outline filter-btn" data-category="other">🎁 기타</button>
                        </div>
                    </div>
                    
                    <!-- 스토어 목록 -->
                    <div id="stores-container" class="stores-grid">
                        ${renderStoresSkeleton(12)}
                    </div>
                </div>
            </div>
        `;
        
        renderPage(content);
        loadStores();
        bindFilterEvents();
    }

    /**
     * 스토어 상세 페이지 렌더링
     */
    function renderStoreDetailPage(params) {
        const storeId = params.id;
        
        const content = `
            <div class="container">
                <div class="store-detail">
                    <div id="store-detail-content">
                        <!-- 로딩 중 -->
                        <div class="skeleton skeleton-card" style="height: 400px; margin-bottom: 2rem;"></div>
                        <div class="skeleton skeleton-text" style="height: 3rem; margin-bottom: 1rem;"></div>
                        <div class="skeleton skeleton-text medium" style="margin-bottom: 2rem;"></div>
                        <div class="skeleton skeleton-card" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
        `;
        
        renderPage(content);
        loadStoreDetail(storeId);
    }

    /**
     * 🆕 Places 목록 페이지 렌더링
     */
    function renderPlacesPage() {
        // URL 쿼리 파라미터에서 place_type 추출
        const urlParams = new URLSearchParams(window.location.search);
        const placeType = urlParams.get('place_type') || 'all';
        
        const content = `
            <div class="container">
                <div class="page">
                    <div class="page-header">
                        <h1 class="page-title">성수동 장소</h1>
                        <p class="page-subtitle">맛집, 상설매장, 편의시설까지 성수동의 모든 장소를 둘러보세요</p>
                    </div>
                    
                    <!-- 장소 유형 필터 -->
                    <div class="filters" style="margin-bottom: 2rem;">
                        <div class="flex justify-center gap-4 flex-wrap">
                            <button class="btn btn-outline filter-btn ${placeType === 'all' ? 'active' : ''}" data-type="all">전체</button>
                            <button class="btn btn-outline filter-btn ${placeType === 'popup_store' ? 'active' : ''}" data-type="popup_store">🏪 팝업스토어</button>
                            <button class="btn btn-outline filter-btn ${placeType === 'restaurant' ? 'active' : ''}" data-type="restaurant">🍽️ 맛집</button>
                            <button class="btn btn-outline filter-btn ${placeType === 'retail_store' ? 'active' : ''}" data-type="retail_store">🏬 상설매장</button>
                            <button class="btn btn-outline filter-btn ${placeType === 'facility' ? 'active' : ''}" data-type="facility">🚻 편의시설</button>
                        </div>
                    </div>
                    
                    <!-- 검색 바 -->
                    <div class="search-section" style="margin-bottom: 2rem;">
                        <div class="search-input-group">
                            <input type="text" id="search-input" class="form-input" placeholder="장소명이나 키워드를 검색해보세요..." style="flex: 1; margin-right: 1rem;">
                            <button class="btn btn-primary" id="search-btn">🔍 검색</button>
                        </div>
                    </div>
                    
                    <!-- 장소 목록 -->
                    <div id="places-container" class="stores-grid">
                        ${renderPlacesSkeleton(12)}
                    </div>
                    
                    <!-- 검색 결과 영역 -->
                    <div id="search-results" class="search-results" style="display: none;">
                        <h3>검색 결과</h3>
                        <div id="search-results-container" class="stores-grid"></div>
                        <button class="btn btn-outline" id="clear-search" style="margin-top: 1rem;">검색 초기화</button>
                    </div>
                </div>
            </div>
        `;
        
        renderPage(content);
        loadPlaces(placeType);
        bindPlacesFilterEvents();
        bindSearchEvents();
    }

    /**
     * 🆕 Places 상세 페이지 렌더링
     */
    function renderPlaceDetailPage(params) {
        const placeId = params.id;
        
        const content = `
            <div class="container">
                <div class="store-detail">
                    <div id="place-detail-content">
                        <!-- 로딩 중 -->
                        <div class="skeleton skeleton-card" style="height: 400px; margin-bottom: 2rem;"></div>
                        <div class="skeleton skeleton-text" style="height: 3rem; margin-bottom: 1rem;"></div>
                        <div class="skeleton skeleton-text medium" style="margin-bottom: 2rem;"></div>
                        <div class="skeleton skeleton-card" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
        `;
        
        renderPage(content);
        loadPlaceDetail(placeId);
    }

    /**
     * 플래너 페이지 렌더링
     */
    function renderPlannerPage() {
        const content = `
            <div class="container">
                <div class="page">
                    <div class="page-header">
                        <h1 class="page-title">투어 플래너</h1>
                        <p class="page-subtitle">방문하고 싶은 장소를 선택하고 최적의 경로를 만들어보세요</p>
                    </div>
                    
                    <div class="planner-container">
                        <!-- 지도 영역 -->
                        <div class="planner-map">
                            <div id="map" style="width: 100%; height: 100%; border-radius: 1rem; overflow: hidden;">
                                <div class="flex items-center justify-center" style="height: 100%; background: var(--bg-muted);">
                                    <p style="color: var(--text-muted);">지도 로딩 중...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 사이드바 -->
                        <div class="planner-sidebar">
                            <h3 class="planner-title">내 투어 코스</h3>
                            
                            <div class="selected-stores">
                                <div id="selected-stores-list">
                                    ${renderSelectedStores()}
                                </div>
                                
                                <div class="text-center" style="margin: 1rem 0;">
                                    <small style="color: var(--text-muted);">
                                        ${state.selectedStores.length}/${window.SUNGSUYA_CONFIG.maxStores} 선택됨
                                    </small>
                                </div>
                            </div>
                            
                            ${state.selectedStores.length > 1 ? `
                                <div class="planner-summary" id="planner-summary">
                                    <h4 style="margin-bottom: 1rem;">투어 정보</h4>
                                    <div class="summary-item">
                                        <span>총 장소</span>
                                        <span id="total-places">${state.selectedStores.length}개</span>
                                    </div>
                                    <div class="summary-item">
                                        <span>장소 유형</span>
                                        <span id="place-types">${getSelectedPlaceTypes()}</span>
                                    </div>
                                    <div class="summary-item">
                                        <span>총 거리</span>
                                        <span id="total-distance">계산 중...</span>
                                    </div>
                                    <div class="summary-item">
                                        <span>예상 시간</span>
                                        <span id="total-time">계산 중...</span>
                                    </div>
                                </div>
                                
                                <button class="btn btn-primary w-full" id="optimize-route">
                                    ⚡ 경로 최적화
                                </button>
                            ` : `
                                <div class="text-center" style="padding: 2rem; color: var(--text-muted);">
                                    <p>장소를 2개 이상 선택하면<br>최적 경로를 계산할 수 있습니다.</p>
                                </div>
                            `}
                            
                            <!-- 장소 추가 섹션 -->
                            <div class="add-places-section" style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 2rem;">
                                <h4 style="margin-bottom: 1rem;">장소 추가하기</h4>
                                
                                <!-- 빠른 장소 선택 -->
                                <div class="quick-add-places" style="margin-bottom: 1rem;">
                                    <div class="flex gap-2 flex-wrap">
                                        <button class="btn btn-outline btn-sm" data-action="add-places" data-type="popup_store">
                                            🏪 팝업스토어
                                        </button>
                                        <button class="btn btn-outline btn-sm" data-action="add-places" data-type="restaurant">
                                            🍽️ 맛집
                                        </button>
                                        <button class="btn btn-outline btn-sm" data-action="add-places" data-type="retail_store">
                                            🏬 상설매장
                                        </button>
                                        <button class="btn btn-outline btn-sm" data-action="add-places" data-type="facility">
                                            🚻 편의시설
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <a href="/places" class="btn btn-outline btn-sm">모든 장소 보기</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        renderPage(content);
        initPlanner();
    }

    /**
     * 404 페이지 렌더링
     */
    function render404Page() {
        const content = `
            <div class="container">
                <div class="page text-center">
                    <h1 style="font-size: 4rem; margin-bottom: 1rem;">404</h1>
                    <h2 style="margin-bottom: 1rem;">페이지를 찾을 수 없습니다</h2>
                    <p style="margin-bottom: 2rem; color: var(--text-muted);">
                        요청하신 페이지가 존재하지 않거나 이동되었습니다.
                    </p>
                    <a href="/" class="btn btn-primary">홈으로 돌아가기</a>
                </div>
            </div>
        `;
        
        renderPage(content);
    }

    /**
     * 페이지 렌더링
     * @param {string} content - 렌더링할 HTML 콘텐츠
     */
    function renderPage(content) {
        const mainEl = dom.$('#app-main');
        if (mainEl) {
            mainEl.innerHTML = content;
        }
    }

    /**
     * 스토어 스켈레톤 UI 렌더링
     * @param {number} count - 스켈레톤 개수
     * @returns {string}
     */
    function renderStoresSkeleton(count = 6) {
        return Array(count).fill(0).map(() => `
            <div class="card">
                <div class="skeleton skeleton-card" style="height: 200px;"></div>
                <div class="card-content">
                    <div class="skeleton skeleton-text" style="height: 1.5rem; margin-bottom: 0.5rem;"></div>
                    <div class="skeleton skeleton-text medium" style="height: 1rem; margin-bottom: 1rem;"></div>
                    <div class="skeleton skeleton-text short" style="height: 1rem;"></div>
                </div>
            </div>
        `).join('');
    }

    /**
     * 🆕 Places 스켈레톤 UI 렌더링
     * @param {number} count - 스켈레톤 개수
     * @returns {string}
     */
    function renderPlacesSkeleton(count = 6) {
        return Array(count).fill(0).map(() => `
            <div class="card">
                <div class="skeleton skeleton-card" style="height: 200px;"></div>
                <div class="card-content">
                    <div class="skeleton skeleton-text" style="height: 1.5rem; margin-bottom: 0.5rem;"></div>
                    <div class="skeleton skeleton-text medium" style="height: 1rem; margin-bottom: 1rem;"></div>
                    <div class="skeleton skeleton-text short" style="height: 1rem;"></div>
                </div>
            </div>
        `).join('');
    }

    /**
     * 선택된 스토어 목록 렌더링
     * @returns {string}
     */
    function renderSelectedStores() {
        if (state.selectedStores.length === 0) {
            return `
                <div class="text-center" style="padding: 2rem; color: var(--text-muted);">
                    <p>아직 선택된 장소가 없습니다.</p>
                    <a href="/places" class="btn btn-outline btn-sm" style="margin-top: 1rem;">
                        장소 선택하기
                    </a>
                </div>
            `;
        }
        
        const typeIcons = {
            'popup_store': '🏪',
            'restaurant': '🍽️',
            'retail_store': '🏬',
            'facility': '🚻'
        };
        
        return state.selectedStores.map((place, index) => {
            const placeIcon = typeIcons[place.place_type] || '📍';
            const typeLabel = place.type_label || place.category || '장소';
            
            return `
                <div class="selected-store" data-store-id="${place.id}">
                    <div class="store-number">${index + 1}</div>
                    <div class="selected-store-info">
                        <div class="selected-store-name">${place.title}</div>
                        <div class="selected-store-type">
                            <span class="place-type-icon">${placeIcon}</span>
                            <span class="place-type-label">${typeLabel}</span>
                        </div>
                    </div>
                    <button class="remove-store" data-store-id="${place.id}" title="투어에서 제거">
                        ✕
                    </button>
                </div>
            `;
        }).join('');
    }

    /**
     * 네비게이션 업데이트
     */
    function updateNavigation() {
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            const isActive = (href === '/' && state.currentRoute === '/') || 
                           (href !== '/' && state.currentRoute.startsWith(href));
            dom.toggleClass(link, 'active', isActive);
        });
    }

    /**
     * 이벤트 바인딩
     */
    function bindEvents() {
        // 스토어 카드 클릭 (이벤트 위임)
        document.addEventListener('click', (e) => {
            const storeCard = e.target.closest('.store-card');
            if (storeCard) {
                const storeId = storeCard.dataset.storeId;
                if (storeId) {
                    navigate(`/stores/${storeId}`);
                }
            }
        });
        
        // 투어 플래너 추가 버튼
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-planner')) {
                e.preventDefault();
                e.stopPropagation();
                const storeId = e.target.dataset.storeId;
                addToPlanner(storeId);
            }
        });
        
        // 스토어 제거 버튼
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-store')) {
                e.preventDefault();
                e.stopPropagation();
                const storeId = e.target.dataset.storeId;
                removeFromPlanner(storeId);
            }
        });
    }

    /**
     * 필터 이벤트 바인딩
     */
    function bindFilterEvents() {
        const filterBtns = document.querySelectorAll('.filter-btn');
        filterBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                // 활성 상태 토글
                filterBtns.forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                
                // 필터 적용
                const category = e.target.dataset.category;
                filterStores(category);
            });
        });
    }

    /**
     * 🆕 Places 필터 이벤트 바인딩
     */
    function bindPlacesFilterEvents() {
        const filterBtns = document.querySelectorAll('.filter-btn');
        filterBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                // 활성 상태 토글
                filterBtns.forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                
                // 필터 적용
                const placeType = e.target.dataset.type;
                filterPlaces(placeType);
                
                // URL 업데이트 (히스토리 변경 없이)
                const newUrl = placeType === 'all' ? '/places' : `/places?place_type=${placeType}`;
                history.replaceState(null, '', newUrl);
            });
        });
    }

    /**
     * 🆕 검색 이벤트 바인딩
     */
    function bindSearchEvents() {
        const searchInput = dom.$('#search-input');
        const searchBtn = dom.$('#search-btn');
        const clearBtn = dom.$('#clear-search');
        
        if (searchBtn) {
            searchBtn.addEventListener('click', performSearch);
        }
        
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
        }
        
        if (clearBtn) {
            clearBtn.addEventListener('click', clearSearch);
        }
    }

    /**
     * 🆕 검색 수행
     */
    async function performSearch() {
        const searchInput = dom.$('#search-input');
        const query = searchInput?.value.trim();
        
        if (!query) {
            showToast('검색어를 입력해주세요', 'warning');
            return;
        }
        
        try {
            const results = await searchAllPlaces(query, state.currentFilter);
            
            const resultsContainer = dom.$('#search-results-container');
            const resultsSection = dom.$('#search-results');
            const placesContainer = dom.$('#places-container');
            
            if (resultsContainer && resultsSection) {
                resultsContainer.innerHTML = results.map(renderPlaceCard).join('');
                resultsSection.style.display = 'block';
                
                if (placesContainer) {
                    placesContainer.style.display = 'none';
                }
                
                showToast(`${results.length}개의 검색 결과를 찾았습니다`, 'success');
            }
        } catch (error) {
            console.error('검색 실패:', error);
            showToast('검색 중 오류가 발생했습니다', 'error');
        }
    }

    /**
     * 🆕 검색 초기화
     */
    function clearSearch() {
        const searchInput = dom.$('#search-input');
        const resultsSection = dom.$('#search-results');
        const placesContainer = dom.$('#places-container');
        
        if (searchInput) {
            searchInput.value = '';
        }
        
        if (resultsSection) {
            resultsSection.style.display = 'none';
        }
        
        if (placesContainer) {
            placesContainer.style.display = 'grid';
        }
    }

    /**
     * 다크모드 초기화
     */
    function initDarkMode() {
        if (state.darkMode) {
            document.body.classList.add('dark');
        }
    }

    /**
     * 🆕 인기 장소 로드 (통합 API 사용)
     */
    async function loadFeaturedStores() {
        try {
            // 통합 API로 인기 팝업스토어 조회
            const response = await api.get('all-places', {
                params: { type: 'popup_store', featured: true, limit: 6 }
            });
            
            const container = dom.$('#featured-stores');
            if (container && response.places) {
                container.innerHTML = response.places.map(renderPlaceCard).join('');
            }
        } catch (error) {
            console.error('인기 장소 로드 실패:', error);
            showToast('데이터를 불러오는데 실패했습니다', 'error');
        }
    }

    /**
     * 🆕 모든 스토어 로드 (통합 API 사용)
     */
    async function loadStores() {
        try {
            state.isLoading = true;
            // 팝업스토어만 조회
            const response = await api.get('all-places', {
                params: { type: 'popup_store' }
            });
            
            state.stores = response.places || [];
            
            const container = dom.$('#stores-container');
            if (container) {
                container.innerHTML = state.stores.map(renderPlaceCard).join('');
            }
        } catch (error) {
            console.error('스토어 로드 실패:', error);
            showToast('데이터를 불러오는데 실패했습니다', 'error');
        } finally {
            state.isLoading = false;
        }
    }

    /**
     * 🆕 Places 로드 (신규 장소 유형)
     */
    async function loadPlaces(placeType = 'all') {
        try {
            state.isLoading = true;
            
            // Places 시스템 데이터 조회
            const response = await api.get('all-places', {
                params: { 
                    type: placeType === 'all' ? 'all' : placeType
                }
            });
            
            state.allPlaces = response.places || [];
            
            const container = dom.$('#places-container');
            if (container) {
                container.innerHTML = state.allPlaces.map(renderPlaceCard).join('');
            }
            
            // 필터 상태 업데이트
            state.currentFilter = placeType;
            
        } catch (error) {
            console.error('Places 로드 실패:', error);
            showToast('데이터를 불러오는데 실패했습니다', 'error');
        } finally {
            state.isLoading = false;
        }
    }

    /**
     * 🆕 통합 검색 기능
     */
    async function searchAllPlaces(query, type = 'all') {
        try {
            if (!query.trim()) {
                showToast('검색어를 입력해주세요', 'warning');
                return;
            }
            
            state.isLoading = true;
            
            const response = await api.get('search-places', {
                params: { 
                    query: query,
                    type: type,
                    limit: 20
                }
            });
            
            return response.results || [];
            
        } catch (error) {
            console.error('통합 검색 실패:', error);
            showToast('검색에 실패했습니다', 'error');
            return [];
        } finally {
            state.isLoading = false;
        }
    }

    /**
     * 🆕 통합 장소 카드 렌더링 (팝업스토어 + Places)
     * @param {Object} place - 장소 데이터
     * @returns {string}
     */
    function renderPlaceCard(place) {
        const isSelected = state.selectedStores.some(s => s.id == place.id);
        const canAdd = state.selectedStores.length < window.SUNGSUYA_CONFIG.maxStores;
        
        // 유형별 아이콘 및 색상
        const typeIcons = {
            'popup_store': '🏪',
            'restaurant': '🍽️',
            'retail_store': '🏬',
            'facility': '🚻'
        };
        
        const typeColors = {
            'popup_store': 'badge-primary',
            'restaurant': 'badge-success',
            'retail_store': 'badge-info',
            'facility': 'badge-warning'
        };
        
        const placeIcon = typeIcons[place.place_type] || '📍';
        const badgeColor = typeColors[place.place_type] || 'badge-secondary';
        
        // 날짜 표시 (팝업스토어만)
        const dateInfo = place.place_type === 'popup_store' && place.end_date ? 
            `<span class="store-date">${format.date(place.end_date)}</span>` : '';
        
        // 링크 설정
        const detailLink = place.source === 'popup_store' ? 
            `/stores/${place.id}` : `/places/${place.id}`;
        
        return `
            <div class="card store-card" data-store-id="${place.id}" data-place-type="${place.place_type}">
                <img class="card-image" 
                     src="${place.image || '/wp-content/themes/sungsuya-v2-theme/assets/images/placeholder.jpg'}" 
                     alt="${place.title}"
                     loading="lazy">
                <div class="card-content">
                    <h3 class="card-title">${place.title}</h3>
                    <p class="card-description">${place.excerpt || place.specialty || ''}</p>
                    <div class="card-meta">
                        <span class="badge ${badgeColor}">${placeIcon} ${place.type_label || place.category || '장소'}</span>
                        ${dateInfo}
                        ${place.price_range ? `<span class="price-range">${place.price_range}</span>` : ''}
                    </div>
                    <div class="store-actions">
                        <a href="${detailLink}" class="btn btn-outline btn-sm">자세히 보기</a>
                        ${isSelected ? 
                            `<button class="btn btn-secondary btn-sm remove-store" data-store-id="${place.id}">
                                선택됨 ✓
                            </button>` :
                            `<button class="btn btn-primary btn-sm add-to-planner ${!canAdd ? 'disabled' : ''}" 
                                     data-store-id="${place.id}" ${!canAdd ? 'disabled' : ''}>
                                투어 추가
                            </button>`
                        }
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * 스토어 상세 정보 로드
     * @param {string} storeId - 스토어 ID
     */
    async function loadStoreDetail(storeId) {
        try {
            const response = await api.get(`stores/${storeId}`);
            
            if (response.store) {
                renderStoreDetail(response.store);
            } else {
                render404Page();
            }
        } catch (error) {
            console.error('스토어 상세 정보 로드 실패:', error);
            showToast('스토어 정보를 불러오는데 실패했습니다', 'error');
            render404Page();
        }
    }

    /**
     * 🆕 Places 상세 정보 로드
     * @param {string} placeId - 장소 ID
     */
    async function loadPlaceDetail(placeId) {
        try {
            // 먼저 통합 API에서 해당 장소 정보를 찾기
            const allPlacesResponse = await api.get('all-places', {
                params: { limit: 100 }
            });
            
            const place = allPlacesResponse.places?.find(p => p.id == placeId);
            
            if (place) {
                renderPlaceDetail(place);
            } else {
                render404Page();
            }
        } catch (error) {
            console.error('장소 상세 정보 로드 실패:', error);
            showToast('장소 정보를 불러오는데 실패했습니다', 'error');
            render404Page();
        }
    }

    /**
     * 스토어 상세 정보 렌더링
     * @param {Object} store - 스토어 데이터
     */
    function renderStoreDetail(store) {
        const isSelected = state.selectedStores.some(s => s.id == store.id);
        const canAdd = state.selectedStores.length < window.SUNGSUYA_CONFIG.maxStores;
        
        const content = `
            <div class="store-detail-header">
                <img class="store-detail-image" 
                     src="${store.image || '/wp-content/themes/sungsuya-v2-theme/assets/images/placeholder.jpg'}" 
                     alt="${store.title}">
                <h1 class="store-detail-title">${store.title}</h1>
                <div class="store-detail-meta">
                    <span class="badge badge-primary">${store.category || '팝업스토어'}</span>
                    <span>📅 ${format.date(store.start_date)} ~ ${format.date(store.end_date)}</span>
                    <span>📍 ${store.location?.address || '성수동'}</span>
                </div>
                <div class="text-center" style="margin-top: 2rem;">
                    ${isSelected ? 
                        `<button class="btn btn-secondary remove-store" data-store-id="${store.id}">
                            투어에서 제거 ✓
                        </button>` :
                        `<button class="btn btn-primary add-to-planner ${!canAdd ? 'disabled' : ''}" 
                                 data-store-id="${store.id}" ${!canAdd ? 'disabled' : ''}>
                            투어에 추가
                        </button>`
                    }
                    <button class="btn btn-outline" onclick="navigator.share?.({title: '${store.title}', url: window.location.href}) || alert('링크가 복사되었습니다')">
                        공유하기
                    </button>
                </div>
            </div>
            
            <div class="store-detail-content">
                <h2>소개</h2>
                <div class="store-detail-description">
                    ${store.content || '스토어 정보가 준비 중입니다.'}
                </div>
            </div>
            
            <div class="store-detail-info">
                <div class="info-item">
                    <h3 class="info-title">운영시간</h3>
                    <div class="info-content">
                        ${store.opening_hours || '문의 바랍니다'}
                    </div>
                </div>
                
                <div class="info-item">
                    <h3 class="info-title">위치</h3>
                    <div class="info-content">
                        ${store.location?.address || '성수동 일대'}
                        <div id="store-map" style="height: 200px; margin-top: 1rem; background: var(--bg-muted); border-radius: 0.5rem;">
                            지도 로딩 중...
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        const container = dom.$('#store-detail-content');
        if (container) {
            container.innerHTML = content;
        }
    }

    /**
     * 🆕 Places 상세 정보 렌더링
     * @param {Object} place - 장소 데이터
     */
    function renderPlaceDetail(place) {
        const isSelected = state.selectedStores.some(s => s.id == place.id);
        const canAdd = state.selectedStores.length < window.SUNGSUYA_CONFIG.maxStores;
        
        // 유형별 아이콘
        const typeIcons = {
            'restaurant': '🍽️',
            'retail_store': '🏬',
            'facility': '🚻'
        };
        
        const placeIcon = typeIcons[place.place_type] || '📍';
        
        const content = `
            <div class="store-detail-header">
                <img class="store-detail-image" 
                     src="${place.image || '/wp-content/themes/sungsuya-v2-theme/assets/images/placeholder.jpg'}" 
                     alt="${place.title}">
                <h1 class="store-detail-title">${place.title}</h1>
                <div class="store-detail-meta">
                    <span class="badge badge-success">${placeIcon} ${place.type_label}</span>
                    ${place.specialty ? `<span>🍽️ ${place.specialty}</span>` : ''}
                    <span>📍 ${place.location?.address || '성수동'}</span>
                    ${place.price_range ? `<span>💰 ${place.price_range}</span>` : ''}
                </div>
                <div class="text-center" style="margin-top: 2rem;">
                    ${isSelected ? 
                        `<button class="btn btn-secondary remove-store" data-store-id="${place.id}">
                            투어에서 제거 ✓
                        </button>` :
                        `<button class="btn btn-primary add-to-planner ${!canAdd ? 'disabled' : ''}" 
                                 data-store-id="${place.id}" ${!canAdd ? 'disabled' : ''}>
                            투어에 추가
                        </button>`
                    }
                    <button class="btn btn-outline" onclick="navigator.share?.({title: '${place.title}', url: window.location.href}) || alert('링크가 복사되었습니다')">
                        공유하기
                    </button>
                    ${place.website ? `<a href="${place.website}" target="_blank" class="btn btn-outline">🌍 웹사이트</a>` : ''}
                </div>
            </div>
            
            <div class="store-detail-content">
                <h2>소개</h2>
                <div class="store-detail-description">
                    ${place.content || '장소 정보가 준비 중입니다.'}
                </div>
            </div>
            
            <div class="store-detail-info">
                ${place.opening_hours ? `
                    <div class="info-item">
                        <h3 class="info-title">운영시간</h3>
                        <div class="info-content">
                            ${place.opening_hours}
                        </div>
                    </div>
                ` : ''}
                
                ${place.contact_info ? `
                    <div class="info-item">
                        <h3 class="info-title">연락처</h3>
                        <div class="info-content">
                            <a href="tel:${place.contact_info}">${place.contact_info}</a>
                        </div>
                    </div>
                ` : ''}
                
                <div class="info-item">
                    <h3 class="info-title">위치</h3>
                    <div class="info-content">
                        ${place.location?.address || '성수동 일대'}
                        <div id="place-map" style="height: 200px; margin-top: 1rem; background: var(--bg-muted); border-radius: 0.5rem;">
                            지도 로딩 중...
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        const container = dom.$('#place-detail-content');
        if (container) {
            container.innerHTML = content;
        }
    }

    /**
     * 스토어 필터링
     * @param {string} category - 카테고리
     */
    function filterStores(category) {
        const storeCards = document.querySelectorAll('.store-card');
        
        storeCards.forEach(card => {
            const storeData = state.stores.find(s => s.id === card.dataset.storeId);
            const shouldShow = category === 'all' || storeData?.category === category;
            
            card.style.display = shouldShow ? '' : 'none';
        });
    }

    /**
     * 🆕 Places 필터링
     * @param {string} placeType - 장소 유형
     */
    function filterPlaces(placeType) {
        if (placeType !== state.currentFilter) {
            // 새로운 데이터 로드
            loadPlaces(placeType);
        }
    }

    /**
     * 🆕 투어 플래너에 장소 추가 (통합 데이터 지원)
     * @param {string} placeId - 장소 ID
     */
    async function addToPlanner(placeId) {
        // 통합 데이터에서 장소 찾기
        let place = null;
        
        // 기존 stores 데이터에서 찾기 (ID 타입 비교 고려)
        place = state.stores.find(s => s.id == placeId);
        
        // allPlaces 데이터에서 찾기 (ID 타입 비교 고려)
        if (!place) {
            place = state.allPlaces.find(p => p.id == placeId);
        }
        
        // 메모리에 없으면 API에서 직접 가져오기
        if (!place) {
            try {
                console.log('장소 정보를 API에서 가져오는 중...', placeId);
                const response = await api.get('all-places', {
                    params: { limit: 100 }
                });
                
                if (response.places) {
                    place = response.places.find(p => p.id == placeId);
                    
                    // 전체 데이터를 state에 저장
                    state.allPlaces = response.places;
                }
            } catch (error) {
                console.error('API에서 장소 정보를 가져오는데 실패:', error);
            }
        }
        
        if (!place) {
            console.error('장소를 찾을 수 없습니다:', placeId);
            showToast('장소 정보를 찾을 수 없습니다', 'error');
            return;
        }
        
        if (state.selectedStores.length >= window.SUNGSUYA_CONFIG.maxStores) {
            showToast(`최대 ${window.SUNGSUYA_CONFIG.maxStores}개까지만 선택할 수 있습니다`, 'warning');
            return;
        }
        
        if (state.selectedStores.some(s => s.id == placeId)) {
            showToast('이미 선택된 장소입니다', 'warning');
            return;
        }
        
        // 선택된 장소에 추가
        state.selectedStores.push(place);
        storage.set('selectedStores', state.selectedStores);
        
        const placeTypeLabel = place.type_label || place.category || '장소';
        showToast(`${place.title}(이)가 투어에 추가되었습니다 (${placeTypeLabel})`, 'success');
        
        // UI 업데이트
        updatePlannerUI();
        
        // 🔥 NEW: 지도 업데이트 추가
        setTimeout(() => {
            updateMapMarkers();
        }, 100);
    }

    /**
     * 투어 플래너에서 스토어 제거
     * @param {string} storeId - 스토어 ID
     */
    function removeFromPlanner(storeId) {
        const storeIndex = state.selectedStores.findIndex(s => s.id == storeId);
        if (storeIndex === -1) return;
        
        const store = state.selectedStores[storeIndex];
        state.selectedStores.splice(storeIndex, 1);
        storage.set('selectedStores', state.selectedStores);
        
        showToast(`${store.title}이(가) 투어에서 제거되었습니다`, 'info');
        
        // UI 업데이트
        updatePlannerUI();
        
        // 🔥 NEW: 지도 업데이트 추가
        setTimeout(() => {
            updateMapMarkers();
        }, 100);
    }

    /**
     * 플래너 UI 업데이트
     */
    function updatePlannerUI() {
        // 현재 페이지가 플래너 페이지인 경우만 업데이트
        if (state.currentRoute === '/planner') {
            const listContainer = dom.$('#selected-stores-list');
            if (listContainer) {
                listContainer.innerHTML = renderSelectedStores();
            }
        }
        
        // 모든 페이지의 버튼 상태 업데이트
        updateStoreButtons();
    }

    /**
     * 스토어 버튼 상태 업데이트
     */
    function updateStoreButtons() {
        const storeCards = document.querySelectorAll('.store-card');
        storeCards.forEach(card => {
            const storeId = card.dataset.storeId;
            const isSelected = state.selectedStores.some(s => s.id == storeId);
            const canAdd = state.selectedStores.length < window.SUNGSUYA_CONFIG.maxStores;
            
            const addBtn = card.querySelector('.add-to-planner');
            const removeBtn = card.querySelector('.remove-store');
            
            if (isSelected) {
                if (addBtn) addBtn.style.display = 'none';
                if (removeBtn) removeBtn.style.display = 'inline-flex';
            } else {
                if (removeBtn) removeBtn.style.display = 'none';
                if (addBtn) {
                    addBtn.style.display = 'inline-flex';
                    addBtn.disabled = !canAdd;
                    addBtn.classList.toggle('disabled', !canAdd);
                }
            }
        });
    }

    /**
     * 플래너 초기화
     */
    function initPlanner() {
        console.log('🗺️ 투어 플래너 초기화');
        
        // 🔥 NEW: 지도 초기화 추가
        initPlannerMap();
        
        // 플래너에서 allPlaces 데이터 강제 로드
        if (state.allPlaces.length === 0) {
            console.log('📊 플래너용 전체 장소 데이터 로드 시작...');
            loadAllPlacesForPlanner();
        } else {
            console.log('✅ 이미 로드된 allPlaces 데이터:', state.allPlaces.length, '개');
        }
        
        // 🔥 NEW: 플래너 이벤트 바인딩
        bindPlannerEvents();
    }

    /**
     * 플래너 지도 초기화
     */
    function initPlannerMap() {
        console.log('🗺️ 플래너 지도 초기화 시작');
        
        // 네이버 지도 스크립트 로드 대기
        if (typeof naver === 'undefined') {
            console.log('📡 네이버 지도 API 로딩 대기 중...');
            
            // 네이버 지도 스크립트 동적 로드
            loadNaverMapsScript();
            return;
        }
        
        initMap();
    }

    /**
     * 네이버 지도 스크립트 동적 로드
     */
    function loadNaverMapsScript() {
        // 이미 로드 중인지 확인
        if (window.naverMapsLoading) {
            return;
        }
        
        window.naverMapsLoading = true;
        
        const script = document.createElement('script');
        // 🔥 FIX: 올바른 NCP Maps API URL 사용
        script.src = 'https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId=' + window.SUNGSUYA_CONFIG.naverClientId;
        
        script.onload = () => {
            console.log('✅ 네이버 지도 API 로드 완료');
            window.naverMapsLoading = false;
            
            // 약간의 지연 후 지도 초기화 (인증 확인을 위해)
            setTimeout(() => {
                initMap();
            }, 100);
        };
        
        script.onerror = () => {
            console.error('❌ 네이버 지도 API 로드 실패');
            window.naverMapsLoading = false;
            showMapError('스크립트 로드 실패');
        };
        
        document.head.appendChild(script);
    }

    /**
     * 지도 초기화 실행
     */
    function initMap() {
        const mapContainer = document.getElementById('map');
        if (!mapContainer) {
            console.error('지도 컨테이너를 찾을 수 없습니다');
            return;
        }
        
        try {
            // 성수역 중심으로 지도 생성
            const mapOptions = {
                center: new naver.maps.LatLng(37.5444, 127.0548),
                zoom: 15,
                minZoom: 12,
                maxZoom: 18,
                mapTypeControl: true,
                mapTypeControlOptions: {
                    style: naver.maps.MapTypeControlStyle.BUTTON,
                    position: naver.maps.Position.TOP_RIGHT
                },
                zoomControl: true,
                zoomControlOptions: {
                    style: naver.maps.ZoomControlStyle.SMALL,
                    position: naver.maps.Position.TOP_LEFT
                },
                // 모바일 최적화
                draggable: true,
                pinchZoom: true,
                scrollWheel: true,
                keyboardShortcuts: true
            };
            
            const map = new naver.maps.Map(mapContainer, mapOptions);
            
            // 전역 변수로 지도 저장
            window.plannerMap = map;
            window.plannerMarkers = [];
            window.plannerPolylines = [];
            
            console.log('✅ 플래너 지도 초기화 완료');
            
            // 선택된 장소들 마커 표시
            updateMapMarkers();
            
            // 지도 로딩 완료 이벤트
            naver.maps.Event.addListener(map, 'idle', () => {
                console.log('🗺️ 지도 렌더링 완료');
                hideMapLoading();
            });
            
        } catch (error) {
            console.error('지도 초기화 실패:', error);
            showMapError(error.message);
        }
    }

    /**
     * 지도 로딩 화면 숨김
     */
    function hideMapLoading() {
        const mapContainer = document.getElementById('map');
        const loadingEl = mapContainer?.querySelector('.map-loading, .flex');
        
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }
    }

    /**
     * 지도 마커 업데이트
     */
    function updateMapMarkers() {
        if (!window.plannerMap) {
            console.log('지도가 초기화되지 않음, 마커 업데이트 스킵');
            return;
        }
        
        // 기존 마커 제거
        if (window.plannerMarkers && window.plannerMarkers.length > 0) {
            window.plannerMarkers.forEach(marker => {
                marker.setMap(null);
            });
            window.plannerMarkers = [];
        }
        
        // 기존 경로선 제거
        if (window.plannerPolylines && window.plannerPolylines.length > 0) {
            window.plannerPolylines.forEach(polyline => {
                polyline.setMap(null);
            });
            window.plannerPolylines = [];
        }
        
        const selectedStores = state.selectedStores || [];
        
        if (selectedStores.length === 0) {
            console.log('선택된 장소가 없음');
            return;
        }
        
        const bounds = new naver.maps.LatLngBounds();
        const validPlaces = [];
        
        selectedStores.forEach((store, index) => {
            // 좌표가 있는 장소만 처리
            if (store.location && store.location.lat && store.location.lng) {
                const position = new naver.maps.LatLng(
                    parseFloat(store.location.lat), 
                    parseFloat(store.location.lng)
                );
                
                // 마커 생성
                const marker = new naver.maps.Marker({
                    position: position,
                    map: window.plannerMap,
                    title: store.title,
                    icon: {
                        content: createCustomMarkerContent(index + 1, store.place_type),
                        size: new naver.maps.Size(40, 50),
                        anchor: new naver.maps.Point(20, 50)
                    }
                });
                
                // 정보창 생성
                const infoWindow = new naver.maps.InfoWindow({
                    content: createInfoWindowContent(store)
                });
                
                // 마커 클릭 이벤트
                naver.maps.Event.addListener(marker, 'click', () => {
                    // 다른 정보창 모두 닫기
                    window.plannerMarkers.forEach((m, i) => {
                        if (m.infoWindow && m.infoWindow !== infoWindow) {
                            m.infoWindow.close();
                        }
                    });
                    
                    if (infoWindow.getMap()) {
                        infoWindow.close();
                    } else {
                        infoWindow.open(window.plannerMap, marker);
                    }
                });
                
                marker.infoWindow = infoWindow;
                window.plannerMarkers.push(marker);
                bounds.extend(position);
                validPlaces.push({ store, position, index });
            }
        });
        
        // 지도 범위 조정
        if (validPlaces.length > 1) {
            window.plannerMap.fitBounds(bounds, { 
                padding: { top: 50, right: 50, bottom: 50, left: 50 } 
            });
        } else if (validPlaces.length === 1) {
            window.plannerMap.setCenter(validPlaces[0].position);
            window.plannerMap.setZoom(16);
        }
        
        // 경로선 그리기 (2개 이상일 때)
        if (validPlaces.length > 1) {
            drawRouteLine(validPlaces);
        }
        
        console.log(`✅ ${validPlaces.length}개 마커 업데이트 완료`);
    }

    /**
     * 커스텀 마커 콘텐츠 생성
     */
    function createCustomMarkerContent(number, placeType) {
        const typeIcon = getPlaceTypeIcon(placeType);
        
        return `
            <div class="custom-marker">
                <div class="marker-pin">
                    <div class="marker-number">${number}</div>
                </div>
                <div class="marker-type-icon">${typeIcon}</div>
            </div>
        `;
    }

    /**
     * 정보창 콘텐츠 생성
     */
    function createInfoWindowContent(store) {
        const typeIcon = getPlaceTypeIcon(store.place_type);
        const typeLabel = store.type_label || store.category || '장소';
        
        return `
            <div class="map-info-window">
                <div class="info-header">
                    <span class="info-type">${typeIcon}</span>
                    <h4 class="info-title">${store.title}</h4>
                </div>
                <div class="info-content">
                    <p class="info-category">${typeLabel}</p>
                    ${store.location?.address ? `<p class="info-address">📍 ${store.location.address}</p>` : ''}
                    ${store.opening_hours ? `<p class="info-hours">🕒 ${store.opening_hours}</p>` : ''}
                </div>
                <div class="info-actions">
                    <button class="info-btn" onclick="removeFromPlanner('${store.id}')">투어에서 제거</button>
                </div>
            </div>
        `;
    }

    /**
     * 경로선 그리기
     */
    function drawRouteLine(validPlaces) {
        if (validPlaces.length < 2) return;
        
        // 순서대로 연결하는 경로선
        for (let i = 0; i < validPlaces.length - 1; i++) {
            const start = validPlaces[i].position;
            const end = validPlaces[i + 1].position;
            
            const polyline = new naver.maps.Polyline({
                path: [start, end],
                strokeColor: '#3b82f6',
                strokeWeight: 4,
                strokeOpacity: 0.8,
                strokeLineCap: 'round',
                strokeLineJoin: 'round',
                map: window.plannerMap
            });
            
            window.plannerPolylines.push(polyline);
        }
    }

    /**
     * 지도 오류 표시
     */
    function showMapError(errorMessage = '') {
        const mapContainer = document.getElementById('map');
        if (mapContainer) {
            const errorType = errorMessage.includes('Authentication Failed') ? 'auth' : 'general';
            
            let errorContent = '';
            if (errorType === 'auth') {
                errorContent = `
                    <div class="map-error">
                        <div class="error-icon">🔐</div>
                        <h3>지도 API 인증 오류</h3>
                        <p>네이버 지도 API 인증에 실패했습니다.</p>
                        <p><small>관리자: 네이버 클라우드 플랫폼에서 도메인 등록을 확인해주세요.</small></p>
                        <button onclick="initPlannerMap()" class="btn btn-sm btn-primary">다시 시도</button>
                        <div style="margin-top: 1rem;">
                            <p><strong>💡 투어플래너는 지도 없이도 사용 가능합니다!</strong></p>
                            <p>사이드바에서 장소를 추가하고 경로를 최적화할 수 있습니다.</p>
                        </div>
                    </div>
                `;
            } else {
                errorContent = `
                    <div class="map-error">
                        <div class="error-icon">🗺️</div>
                        <h3>지도를 불러올 수 없습니다</h3>
                        <p>네트워크 연결을 확인하고 다시 시도해주세요</p>
                        <button onclick="initPlannerMap()" class="btn btn-sm btn-primary">다시 시도</button>
                        <div style="margin-top: 1rem;">
                            <p><strong>💡 투어플래너는 지도 없이도 사용 가능합니다!</strong></p>
                            <p>사이드바에서 장소를 추가하고 경로를 최적화할 수 있습니다.</p>
                        </div>
                    </div>
                `;
            }
            
            mapContainer.innerHTML = errorContent;
        }
    }

    /**
     * 플래너 이벤트 바인딩
     */
    function bindPlannerEvents() {
        // 경로 최적화 버튼
        const optimizeBtn = document.getElementById('optimize-route');
        if (optimizeBtn) {
            optimizeBtn.addEventListener('click', optimizeRoute);
        }
        
        // 빠른 장소 추가 버튼들
        document.querySelectorAll('[data-action="add-places"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const placeType = e.target.dataset.type;
                // TODO: 모달 구현 후 showQuickAddModal(placeType) 호출
                // 임시로 기존 페이지 이동
                navigate(`/places?place_type=${placeType}`);
            });
        });
        
        // 장소 제거 버튼 (이벤트 위임)
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-store')) {
                const storeId = e.target.dataset.storeId;
                removeFromPlanner(storeId);
            }
        });
    }
    
    /**
     * 🆆 플래너용 전체 장소 데이터 로드
     */
    async function loadAllPlacesForPlanner() {
        try {
            console.log('📊 플래너용 전체 장소 로드 시작...');
            
            // 직접 fetch 사용 (안정성 향상)
            const url = `${window.SUNGSUYA_CONFIG.apiUrl}all-places?limit=50`;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.SUNGSUYA_CONFIG.nonce
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            state.allPlaces = data.places || [];
            
            console.log('✅ 플래너용 전체 장소 데이터 로드 성공:', state.allPlaces.length, '개');
            console.log('🏠 장소 유형 목록:', data.types_available);
            
        } catch (error) {
            console.error('❌ 플래너용 데이터 로드 실패:', error);
            showToast('장소 데이터를 불러오는데 실패했습니다', 'error');
        }
    }

    /**
     * 경로 최적화 (통합 장소 지원)
     */
    async function optimizeRoute() {
        if (state.selectedStores.length < 2) {
            showToast('최소 2개 이상의 장소를 선택해주세요', 'warning');
            return;
        }
        
        // 좌표가 있는 장소만 필터링
        const placesWithCoordinates = state.selectedStores.filter(place => {
            return place.location && place.location.lat && place.location.lng;
        });
        
        if (placesWithCoordinates.length < 2) {
            showToast('좌표 정보가 있는 장소가 2개 미만입니다. 경로 최적화를 할 수 없습니다.', 'warning');
            
            // 좌표가 없는 장소들 표시
            const placesWithoutCoordinates = state.selectedStores.filter(place => {
                return !place.location || !place.location.lat || !place.location.lng;
            });
            
            if (placesWithoutCoordinates.length > 0) {
                console.log('좌표가 없는 장소들:', placesWithoutCoordinates.map(p => p.title));
                showToast(`좌표가 없는 장소: ${placesWithoutCoordinates.map(p => p.title).join(', ')}`, 'info');
            }
            return;
        }
        
        try {
            showToast('경로를 최적화하고 있습니다...', 'info');
            
            // 좌표가 있는 장소들만으로 API 호출
            const response = await api.post('planner/optimize', {
                places: placesWithCoordinates.map(place => ({
                    id: place.id,
                    title: place.title,
                    place_type: place.place_type,
                    type_label: place.type_label,
                    source: place.source,
                    lat: place.location.lat,
                    lng: place.location.lng,
                    location: place.location
                }))
            });
            
            if (response.optimized_route) {
                // 최적화된 순서로 장소 재정렬 (좌표가 있는 장소만)
                const optimizedPlaces = response.optimized_route.map(index => 
                    placesWithCoordinates[index]
                );
                
                // 좌표가 없는 장소들을 뒤에 추가
                const placesWithoutCoordinates = state.selectedStores.filter(place => {
                    return !place.location || !place.location.lat || !place.location.lng;
                });
                
                state.selectedStores = [...optimizedPlaces, ...placesWithoutCoordinates];
                storage.set('selectedStores', state.selectedStores);
                
                // UI 업데이트
                updatePlannerUI();
                updateOptimizationResults(response);
                
                // 🔥 NEW: 지도 업데이트 추가
                setTimeout(() => {
                    updateMapMarkers();
                }, 100);
                
                let message = `경로가 최적화되었습니다! 총 거리: ${response.total_distance}km, 예상시간: ${response.estimated_time_formatted}`;
                if (placesWithoutCoordinates.length > 0) {
                    message += ` (좌표가 없는 ${placesWithoutCoordinates.length}개 장소는 마지막에 배치됨)`;
                }
                
                showToast(message, 'success');
            }
        } catch (error) {
            console.error('경로 최적화 실패:', error);
            showToast('경로 최적화에 실패했습니다', 'error');
        }
    }

    // 공개 API
    return {
        init,
        navigate,
        state,
        addToPlanner,
        removeFromPlanner,
        loadPlaces, // 🆕 Places 로딩 기능 추가
        searchAllPlaces, // 🆕 통합 검색 기능 추가
        renderPlaceCard, // 🆕 통합 카드 렌더링 추가
        updateMapMarkers, // 🔥 NEW: 지도 마커 업데이트 함수 노출
        initPlannerMap // 🔥 NEW: 지도 초기화 함수 노출
    };

    /**
     * 🆕 선택된 장소 유형 목록 가져오기
     * @returns {string}
     */
    function getSelectedPlaceTypes() {
        const types = [...new Set(state.selectedStores.map(place => place.type_label || place.category))];
        return types.join(', ');
    }

    /**
     * 🆕 최적화 결과 UI 업데이트
     * @param {Object} response - 최적화 API 응답
     */
    function updateOptimizationResults(response) {
        const summaryEl = dom.$('#planner-summary');
        if (!summaryEl) return;
        
        // 거리 및 시간 업데이트
        const distanceEl = dom.$('#total-distance');
        const timeEl = dom.$('#total-time');
        const placesEl = dom.$('#total-places');
        const typesEl = dom.$('#place-types');
        
        if (distanceEl) distanceEl.textContent = `${response.total_distance}km`;
        if (timeEl) timeEl.textContent = response.estimated_time_formatted;
        if (placesEl) placesEl.textContent = `${response.places_count}개`;
        if (typesEl) typesEl.textContent = Object.keys(response.place_type_counts).map(type => 
            `${getPlaceTypeIcon(type)} ${response.place_type_counts[type]}개`
        ).join(', ');
    }

    /**
     * 🆕 장소 유형별 아이콘 가져오기
     * @param {string} placeType - 장소 유형
     * @returns {string}
     */
    function getPlaceTypeIcon(placeType) {
        const icons = {
            'popup_store': '🏪',
            'restaurant': '🍽️',
            'retail_store': '🏬',
            'facility': '🚻'
        };
        return icons[placeType] || '📍';
    }
})();

// DOM 로드 완료 시 앱 초기화
document.addEventListener('DOMContentLoaded', function() {
    window.SungsuyaApp.init();
    
    // 🔥 NEW: 플래너 지도 전용 CSS 스타일 추가
    addPlannerMapStyles();
});

/**
 * 🔥 NEW: 플래너 지도 전용 CSS 스타일 추가
 */
function addPlannerMapStyles() {
    if (document.getElementById('planner-map-styles')) {
        return; // 이미 추가됨
    }
    
    const styles = `
        <style id="planner-map-styles">
        /* 커스텀 마커 스타일 */
        .custom-marker {
            position: relative;
            cursor: pointer;
        }
        
        .marker-pin {
            background: #3b82f6;
            border: 3px solid white;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            width: 30px;
            height: 30px;
            position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        .marker-number {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 20px;
            height: 20px;
            background: white;
            color: #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            transform: rotate(45deg);
        }
        
        .marker-type-icon {
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 14px;
            background: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.2);
        }
        
        /* 정보창 스타일 */
        .map-info-window {
            padding: 12px;
            min-width: 200px;
            max-width: 250px;
            font-family: inherit;
        }
        
        .info-header {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            gap: 6px;
        }
        
        .info-type {
            font-size: 16px;
        }
        
        .info-title {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .info-content p {
            margin: 4px 0;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.4;
        }
        
        .info-category {
            font-weight: 500;
            color: #3b82f6 !important;
        }
        
        .info-actions {
            margin-top: 8px;
            text-align: center;
        }
        
        .info-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .info-btn:hover {
            background: #dc2626;
        }
        
        /* 지도 오류 스타일 */
        .map-error {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 2rem;
            text-align: center;
            color: #6b7280;
            background: #f9fafb;
            border-radius: 1rem;
        }
        
        .error-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .map-error h3 {
            margin: 0 0 0.5rem 0;
            color: #374151;
            font-size: 1.1rem;
        }
        
        .map-error p {
            margin: 0 0 1rem 0;
            font-size: 0.9rem;
        }
        
        /* 지도 로딩 숨김 */
        .map-loading {
            display: none !important;
        }
        
        /* 전역 함수 노출 */
        </style>
    `;
    
    document.head.insertAdjacentHTML('beforeend', styles);
}

// 🔥 NEW: 전역 함수로 노출 (디버깅 및 정보창에서 사용)
window.updateMapMarkers = function() {
    if (window.SungsuyaApp && window.SungsuyaApp.updateMapMarkers) {
        window.SungsuyaApp.updateMapMarkers();
    }
};

window.initPlannerMap = function() {
    if (window.SungsuyaApp && window.SungsuyaApp.initPlannerMap) {
        window.SungsuyaApp.initPlannerMap();
    }
};

window.removeFromPlanner = function(storeId) {
    if (window.SungsuyaApp && window.SungsuyaApp.removeFromPlanner) {
        window.SungsuyaApp.removeFromPlanner(storeId);
    }
};
