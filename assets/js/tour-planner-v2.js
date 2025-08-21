/**
 * 투어플래너 V2 메인 스크립트
 * 깔끔한 MVP 버전 - 핵심 기능만 구현
 * 
 * @since 2025-06-29
 */

class TourPlannerV2 {
    constructor() {
        // 데이터
        this.places = [];           // 전체 장소 목록
        this.placeTypes = [];       // 장소 유형
        this.selectedPlaces = [];   // 선택된 장소
        
        // 지도 초기화 상태
        this.mapInitialized = false;
        
        // UI 요소
        this.elements = {
            placesList: document.getElementById('places-list'),
            selectedList: document.getElementById('selected-list'),
            filterButtons: document.getElementById('filter-buttons'),
            searchInput: document.getElementById('place-search'),
            selectedCount: document.getElementById('selected-count'),
            totalPlaces: document.getElementById('total-places'),
            totalTime: document.getElementById('total-time'),
            totalDistance: document.getElementById('total-distance'),
            shareBtn: document.getElementById('share-btn'),
            optimizeBtn: document.getElementById('optimize-btn'),
            resetBtn: document.getElementById('reset-btn'),
            shareModal: document.getElementById('share-modal'),
            shareUrl: document.getElementById('share-url')
        };
        
        // 지도
        this.map = null;
        this.markers = [];
        this.polyline = null;
        
        // 설정
        this.config = window.tourV2Config || {};
        
        // 초기화
        this.init();
    }
    
    /**
     * 초기화
     */
    async init() {
        console.log('🚀 TourPlannerV2 초기화 시작');
        
        try {
            // 1. 장소 유형 로드
            await this.loadPlaceTypes();
            
            // 2. 장소 데이터 로드
            await this.loadPlaces();
            
            // 3. URL 파라미터 체크 (공유된 투어)
            this.checkSharedTour();
            
            // 4. 지도 초기화
            this.initMap();
            
            // 5. 이벤트 바인딩
            this.bindEvents();
            
            // 6. 드래그 앤 드롭 설정
            this.initSortable();
            
            // 7. 저장된 데이터 불러오기
            setTimeout(() => {
                this.loadFromStorage();
                this.updateSavedToursList(); // 저장된 투어 목록 표시
            }, 1000);
            
            // 8. 모바일에서 초기 뷰 설정
            if (window.innerWidth <= 768) {
                this.initializeMobileView();
            }
            
            // 9. 인증 상태에 따른 UI 업데이트
            this.updateAuthBasedUI();
            
            console.log('✅ TourPlannerV2 초기화 완료');
            
        } catch (error) {
            console.error('초기화 실패:', error);
            this.showError('투어플래너를 불러오는 중 오류가 발생했습니다.');
        }
    }
    
    /**
     * 모바일 뷰 초기화
     */
    initializeMobileView() {
        console.log('📱 모바일 뷰 초기화');
        
        // 모든 패널 숨기기
        document.querySelectorAll('.mobile-panel').forEach(panel => {
            panel.classList.remove('active');
            panel.style.display = 'none';
        });
        
        // 지도 패널 활성화 (기본)
        const mapPanel = document.querySelector('.map-panel.mobile-panel');
        if (mapPanel) {
            mapPanel.classList.add('active');
            mapPanel.style.display = 'flex';
        }
        
        // 지도 탭 활성화
        document.querySelectorAll('.mobile-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        const mapTab = document.querySelector('.mobile-tab[data-tab="map"]');
        if (mapTab) {
            mapTab.classList.add('active');
        }
    }
    
    /**
     * 인증 상태에 따른 UI 업데이트
     */
    updateAuthBasedUI() {
        const savedToursSection = document.querySelector('.saved-tours');
        const savedToursList = document.getElementById('saved-tours-list');
        const loginPrompt = document.getElementById('saved-tours-login-prompt');
        
        if (!window.sungsuyaAuth || !window.sungsuyaAuth.user || window.sungsuyaAuth.isGuest) {
            // 비로그인 또는 게스트 상태
            if (savedToursList) savedToursList.style.display = 'none';
            if (loginPrompt) loginPrompt.style.display = 'block';
            
            // 관리 버튼 숨기기
            const manageBtn = document.getElementById('manage-tours-btn');
            if (manageBtn) manageBtn.style.display = 'none';
        } else {
            // 로그인 상태
            if (savedToursList) savedToursList.style.display = 'block';
            if (loginPrompt) loginPrompt.style.display = 'none';
            
            // 관리 버튼 표시
            const manageBtn = document.getElementById('manage-tours-btn');
            if (manageBtn) manageBtn.style.display = 'inline-block';
            
            // 저장된 투어 목록 업데이트
            this.updateSavedToursList();
        }
    }
    
    /**
     * 장소 유형 로드
     */
    async loadPlaceTypes() {
        try {
            // 하드코딩된 장소 유형 사용 (taxonomy API 대신)
            this.placeTypes = [
                { id: 1, slug: 'popup_store', name: '팝업스토어', icon: '🏪' },
                { id: 2, slug: 'restaurant', name: '맛집', icon: '🍽️' },
                { id: 3, slug: 'retail_store', name: '상설매장', icon: '🛍️' },
                { id: 4, slug: 'facility', name: '편의시설', icon: '🛍️' },
                { id: 5, slug: 'edit_shop', name: '편집샵', icon: '🛍️' }
            ];
            
            this.renderFilters();
            
        } catch (error) {
            console.error('장소 유형 로드 오류:', error);
            // 기본 유형 사용
            this.placeTypes = [
                { id: 1, slug: 'popup_store', name: '팝업스토어', icon: '🏪' },
                { id: 2, slug: 'restaurant', name: '맛집', icon: '🍽️' },
                { id: 3, slug: 'retail_store', name: '상설매장', icon: '🛍️' },
                { id: 4, slug: 'facility', name: '편의시설', icon: '🛍️' },
                { id: 5, slug: 'edit_shop', name: '편집샵', icon: '🛍️' }
            ];
            this.renderFilters();
        }
    }
    
    /**
     * 장소 데이터 로드
     */
    async loadPlaces() {
        try {
            // 로딩 표시
            this.elements.placesList.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>장소를 불러오는 중...</p>
                </div>
            `;
            
            // WordPress 표준 REST API 호출
            const response = await fetch('/wp-json/wp/v2/places?per_page=100&_embed&status=publish');
            
            if (!response.ok) {
                throw new Error(`서버 응답 오류: ${response.status}`);
            }
            
            const places = await response.json();
            
            if (!Array.isArray(places) || places.length === 0) {
                throw new Error('장소 데이터가 비어있습니다');
            }
            
            // 메타데이터를 직접 처리 (개별 API 호출 없이)
            this.places = places.map(place => ({
                id: place.id,
                title: place.title.rendered || place.title,
                type: this.getPlaceTypeFromMeta(place),
                address: place.meta?.address || '성수동',
                location: {
                    lat: parseFloat(place.meta?.latitude) || 37.5444,
                    lng: parseFloat(place.meta?.longitude) || 127.0557
                }
            }));
            
            // 장소 목록 렌더링
            this.renderPlaces();
            
        } catch (error) {
            console.error('장소 로드 오류:', error);
            
            // 사용자 친화적 에러 메시지
            let errorMessage = '장소를 불러올 수 없습니다';
            if (error.message.includes('Failed to fetch')) {
                errorMessage = '네트워크 연결을 확인해주세요';
            } else if (error.message.includes('404')) {
                errorMessage = '요청한 페이지를 찾을 수 없습니다';
            } else if (error.message.includes('500')) {
                errorMessage = '서버 오류가 발생했습니다';
            }
            
            this.elements.placesList.innerHTML = `
                <div class="empty-state">
                    <span class="empty-icon">❌</span>
                    <p>${errorMessage}</p>
                    <button onclick="tourPlanner.loadPlaces()" class="btn btn-primary">
                        다시 시도
                    </button>
                </div>
            `;
            
            this.showError(errorMessage);
        }
    }
    

    
    /**
     * 장소 타입 추출
     */
    getPlaceTypeFromMeta(place) {
        // _embedded 데이터에서 택소노미 정보 추출
        if (place._embedded && place._embedded['wp:term']) {
            const terms = place._embedded['wp:term'];
            // place_type 택소노미 찾기
            for (const termGroup of terms) {
                for (const term of termGroup) {
                    if (term.taxonomy === 'place_type') {
                        return term.slug;
                    }
                }
            }
        }
        
        // 기존 방식 (place_type 배열)
        if (place.place_type && place.place_type.length > 0) {
            const typeId = place.place_type[0];
            const type = this.placeTypes.find(t => t.id === typeId);
            return type ? type.slug : 'place';
        }
        return 'place';
    }
    
    /**
     * 필터 버튼 렌더링
     */
    renderFilters() {
        if (!this.elements.filterButtons) return;
        
        // placeTypes가 배열인지 확인
        const types = Array.isArray(this.placeTypes) ? this.placeTypes : [];
        
        const filtersHTML = `
            <button class="filter-btn active" data-type="all">전체</button>
            ${types.map(type => `
                <button class="filter-btn" data-type="${type.slug}">
                    ${type.icon || ''} ${type.name}
                </button>
            `).join('')}
        `;
        
        this.elements.filterButtons.innerHTML = filtersHTML;
    }
    
    /**
     * 장소 목록 렌더링
     */
    renderPlaces(filter = 'all', search = '') {
        // 필터링
        let filteredPlaces = this.places;
        
        if (filter !== 'all') {
            filteredPlaces = filteredPlaces.filter(place => place.type === filter);
        }
        
        if (search) {
            // 검색어 정규화: 소문자 변환, 공백 제거
            const searchNormalized = search.toLowerCase().replace(/\s+/g, '');
            
            filteredPlaces = filteredPlaces.filter(place => {
                // 장소명과 주소도 정규화하여 비교
                const titleNormalized = place.title.toLowerCase().replace(/\s+/g, '');
                const addressNormalized = place.address.toLowerCase().replace(/\s+/g, '');
                
                return titleNormalized.includes(searchNormalized) ||
                       addressNormalized.includes(searchNormalized);
            });
        }
        
        // 렌더링
        if (filteredPlaces.length === 0) {
            this.elements.placesList.innerHTML = `
                <div class="empty-state">
                    <span class="empty-icon">🔍</span>
                    <p>검색 결과가 없습니다</p>
                </div>
            `;
            return;
        }
        
        const placesHTML = filteredPlaces.map(place => {
            const typeInfo = this.placeTypes.find(t => t.slug === place.type) || 
                           { icon: '📍', name: '장소' };
            const isSelected = this.selectedPlaces.some(p => p.id === place.id);
            
            return `
                <div class="place-card ${isSelected ? 'selected' : ''}" 
                     data-place-id="${place.id}">
                    <div class="place-card-header">
                        <div class="place-icon">${typeInfo.icon}</div>
                        <div class="place-info">
                            <h3>${place.title}</h3>
                            <span class="place-type">${typeInfo.name}</span>
                        </div>
                    </div>
                    <p class="place-address">${place.address}</p>
                </div>
            `;
        }).join('');
        
        this.elements.placesList.innerHTML = placesHTML;
    }
    
    /**
     * 장소 선택/해제
     */
    togglePlace(placeId) {
        const place = this.places.find(p => p.id === parseInt(placeId));
        if (!place) return;
        
        const index = this.selectedPlaces.findIndex(p => p.id === place.id);
        
        if (index > -1) {
            // 이미 선택된 경우 제거
            this.selectedPlaces.splice(index, 1);
            this.showToast(`${place.title}을(를) 제거했습니다`);
        } else {
            // 새로 선택
            if (this.selectedPlaces.length >= 8) {
                this.showToast('최대 8개까지 선택 가능합니다', 'error');
                return;
            }
            
            this.selectedPlaces.push(place);
            this.showToast(`${place.title}을(를) 추가했습니다`);
        }
        
        // UI 업데이트
        this.updateUI();
    }
    
    /**
     * 선택된 장소 제거
     */
    removePlace(placeId) {
        const index = this.selectedPlaces.findIndex(p => p.id === parseInt(placeId));
        if (index > -1) {
            const place = this.selectedPlaces[index];
            this.selectedPlaces.splice(index, 1);
            this.showToast(`${place.title}을(를) 제거했습니다`);
            this.updateUI();
        }
    }
    
    /**
     * 지도 업데이트 디바운싱
     */
    updateMapDebounced() {
        if (this.updateMapTimeout) {
            clearTimeout(this.updateMapTimeout);
        }
        this.updateMapTimeout = setTimeout(() => {
            this.updateMap();
        }, 300); // 300ms 디바운스
    }
    
    /**
     * 경로 최적화 (지하철역 기준)
     */
    optimizeTourRoute() {
        if (this.selectedPlaces.length < 2) return;
        
        // 성수역 지하철역 좌표
        const subwayStations = [
            { name: '성수역', lat: 37.5444, lng: 127.0557 },
            { name: '뚝섬역', lat: 37.5322, lng: 127.0498 },
            { name: '서울숲역', lat: 37.5444, lng: 127.0374 }
        ];
        
        // 1. 가장 가까운 지하철역 찾기
        let nearestStation = subwayStations[0];
        let minDistanceToStation = Infinity;
        
        // 모든 장소의 중심점 계산
        const centerLat = this.selectedPlaces.reduce((sum, p) => sum + p.location.lat, 0) / this.selectedPlaces.length;
        const centerLng = this.selectedPlaces.reduce((sum, p) => sum + p.location.lng, 0) / this.selectedPlaces.length;
        
        subwayStations.forEach(station => {
            const distance = this.calculateDistance(
                centerLat, centerLng,
                station.lat, station.lng
            );
            if (distance < minDistanceToStation) {
                minDistanceToStation = distance;
                nearestStation = station;
            }
        });
        
        console.log(`가장 가까운 역: ${nearestStation.name}`);
        
        // 2. 역에서 가장 가까운 장소를 첫 번째로
        let optimizedPlaces = [...this.selectedPlaces];
        let currentPos = { lat: nearestStation.lat, lng: nearestStation.lng };
        let result = [];
        
        // Nearest Neighbor 알고리즘
        while (optimizedPlaces.length > 0) {
            let nearestPlace = null;
            let nearestDistance = Infinity;
            let nearestIndex = -1;
            
            optimizedPlaces.forEach((place, index) => {
                const distance = this.calculateDistance(
                    currentPos.lat, currentPos.lng,
                    place.location.lat, place.location.lng
                );
                if (distance < nearestDistance) {
                    nearestDistance = distance;
                    nearestPlace = place;
                    nearestIndex = index;
                }
            });
            
            if (nearestPlace) {
                result.push(nearestPlace);
                currentPos = nearestPlace.location;
                optimizedPlaces.splice(nearestIndex, 1);
            }
        }
        
        // 3. 최적화된 순서로 업데이트
        this.selectedPlaces = result;
        this.updateUI();
        
        this.showToast(`${nearestStation.name} 기준으로 경로를 최적화했습니다`);
    }
    
    /**
     * 두 지점 사이의 거리 계산 (Haversine formula)
     */
    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371; // 지구 반경 (km)
        const dLat = this.deg2rad(lat2 - lat1);
        const dLng = this.deg2rad(lng2 - lng1);
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(this.deg2rad(lat1)) * Math.cos(this.deg2rad(lat2)) *
            Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        const distance = R * c;
        return distance;
    }
    
    deg2rad(deg) {
        return deg * (Math.PI/180);
    }
    
    /**
     * 전체 초기화
     */
    resetTour() {
        if (this.selectedPlaces.length === 0) return;
        
        if (confirm('선택한 모든 장소를 초기화하시겠습니까?')) {
            this.selectedPlaces = [];
            this.updateUI();
            this.showToast('투어가 초기화되었습니다');
        }
    }
    
    /**
     * UI 전체 업데이트
     */
    updateUI() {
        // 장소 목록 업데이트
        this.updatePlaceCards();
        
        // 선택된 장소 목록 업데이트
        this.updateSelectedList();
        
        // 요약 정보 업데이트
        this.updateSummary();
        
        // 버튼 상태 업데이트
        this.updateButtons();
        
        // 지도 업데이트 (디바운싱 적용)
        this.updateMapDebounced();
        
        // 로컬스토리지 저장
        this.saveToStorage();
        
        // 모바일 버튼 상태 업데이트
        this.updateMobileButtons();
    }
    
    /**
     * 모바일 버튼 상태 업데이트
     */
    updateMobileButtons() {
        const mobileBottomActions = document.querySelector('.mobile-bottom-actions');
        const mobileShareBtn = document.getElementById('mobile-share-btn');
        const mobileResetBtn = document.getElementById('mobile-reset-btn');
        
        if (window.innerWidth <= 768 && mobileBottomActions) {
            const hasPlaces = this.selectedPlaces.length > 0;
            mobileBottomActions.style.display = hasPlaces ? 'flex' : 'none';
            
            if (mobileShareBtn) mobileShareBtn.disabled = !hasPlaces;
            if (mobileResetBtn) mobileResetBtn.disabled = !hasPlaces;
        }
    }
    
    /**
     * 장소 카드 선택 상태 업데이트
     */
    updatePlaceCards() {
        document.querySelectorAll('.place-card').forEach(card => {
            const placeId = parseInt(card.dataset.placeId);
            const isSelected = this.selectedPlaces.some(p => p.id === placeId);
            card.classList.toggle('selected', isSelected);
        });
    }
    
    /**
     * 선택된 장소 목록 업데이트
     */
    updateSelectedList() {
        this.elements.selectedCount.textContent = this.selectedPlaces.length;
        
        if (this.selectedPlaces.length === 0) {
            this.elements.selectedList.innerHTML = `
                <div class="empty-state">
                    <span class="empty-icon">📝</span>
                    <p>아직 선택한 장소가 없습니다</p>
                    <small>왼쪽 목록에서 장소를 클릭해주세요</small>
                </div>
            `;
            return;
        }
        
        const selectedHTML = this.selectedPlaces.map((place, index) => {
            const typeInfo = this.placeTypes.find(t => t.slug === place.type) || 
                           { icon: '📍', name: '장소' };
            
            return `
                <div class="selected-item" data-place-id="${place.id}">
                    <div class="order-number">${index + 1}</div>
                    <div class="selected-info">
                        <h4 class="selected-name">${place.title}</h4>
                        <span class="selected-type">${typeInfo.icon} ${typeInfo.name}</span>
                    </div>
                    <button class="remove-btn" data-place-id="${place.id}">✕</button>
                </div>
            `;
        }).join('');
        
        this.elements.selectedList.innerHTML = selectedHTML;
    }
    
    /**
     * 요약 정보 업데이트
     */
    updateSummary() {
        const count = this.selectedPlaces.length;
        
        // 장소 수
        this.elements.totalPlaces.textContent = count;
        
        // 예상 시간 (장소당 30분 + 이동시간)
        const visitTime = count * 30;
        const moveTime = count > 1 ? (count - 1) * 10 : 0;
        const totalMinutes = visitTime + moveTime;
        
        if (totalMinutes >= 60) {
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;
            this.elements.totalTime.textContent = minutes > 0 ? `${hours}시간 ${minutes}분` : `${hours}시간`;
        } else {
            this.elements.totalTime.textContent = totalMinutes > 0 ? `${totalMinutes}분` : '0분';
        }
        
        // 이동거리 (대략적인 계산)
        const avgDistance = 0.5; // 평균 500m
        const totalDistance = count > 1 ? (count - 1) * avgDistance : 0;
        this.elements.totalDistance.textContent = `${totalDistance.toFixed(1)}km`;
    }
    
    /**
     * 버튼 상태 업데이트
     */
    updateButtons() {
        const hasPlaces = this.selectedPlaces.length > 0;
        const hasMultiplePlaces = this.selectedPlaces.length > 1;
        
        this.elements.shareBtn.disabled = !hasPlaces;
        this.elements.optimizeBtn.disabled = !hasMultiplePlaces;
        this.elements.resetBtn.disabled = !hasPlaces;
    }
    

    
    /**
     * 지도 초기화
     */
    initMap() {
        const mapContainer = document.getElementById('tour-map');
        if (!mapContainer) {
            console.error('지도 컨테이너를 찾을 수 없습니다');
            return;
        }
        
        // 컨테이너 크기 확인 및 강제 설정
        console.log('🔍 지도 컨테이너 크기 확인:');
        console.log('offsetWidth:', mapContainer.offsetWidth);
        console.log('offsetHeight:', mapContainer.offsetHeight);
        
        // 크기가 0이면 강제 설정
        if (mapContainer.offsetHeight === 0) {
            console.warn('⚠️ 지도 컨테이너 높이가 0입니다. 강제 설정합니다.');
            
            // 부모 요소들 확인 및 설정
            const tourMap = mapContainer.closest('.tour-map');
            const mapPanel = mapContainer.closest('.map-panel');
            
            if (mapPanel) {
                mapPanel.style.height = '600px';
                mapPanel.style.minHeight = '600px';
            }
            
            if (tourMap) {
                tourMap.style.height = '600px';
                tourMap.style.minHeight = '600px';
                tourMap.style.position = 'relative';
            }
            
            mapContainer.style.height = '600px';
            mapContainer.style.minHeight = '600px';
            mapContainer.style.width = '100%';
            mapContainer.style.display = 'block';
            mapContainer.style.position = 'absolute';
            mapContainer.style.top = '0';
            mapContainer.style.left = '0';
        }
        
        // 지도 로딩 오버레이 제거
        const loadingOverlay = mapContainer.querySelector('.map-loading');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
        
        // 네이버 지도 API가 로드되지 않았으면 대기
        if (typeof naver === 'undefined' || !naver.maps) {
            console.warn('네이버 지도 API 로드 대기 중...');
            
            // naverMapReady 이벤트 리스너 등록
            window.addEventListener('naverMapReady', () => {
                console.log('📍 naverMapReady 이벤트 수신 - 지도 초기화 시작');
                this.initializeMap(mapContainer);
            });
            
            return;
        }
        
        // API가 로드되었으면 바로 지도 초기화
        this.initializeMap(mapContainer);
    }
    
    /**
     * 실제 지도 초기화 함수
     */
    initializeMap(mapContainer) {
        // 이미 초기화되었으면 무시
        if (this.mapInitialized) {
            console.log('지도 이미 초기화됨');
            return;
        }
        
        try {
            console.log('🗺️ 네이버 지도 초기화 시작...');
            
            // 지도 옵션
            const mapOptions = {
                center: new naver.maps.LatLng(37.5444, 127.0557),
                zoom: 15,
                logoControl: false,
                scaleControl: false,
                mapDataControl: false,
                mapTypeControl: true,
                mapTypeControlOptions: {
                    style: naver.maps.MapTypeControlStyle.BUTTON,
                    position: naver.maps.Position.TOP_RIGHT
                },
                zoomControl: true,
                zoomControlOptions: {
                    style: naver.maps.ZoomControlStyle.SMALL,
                    position: naver.maps.Position.TOP_RIGHT
                },
                minZoom: 10,
                maxZoom: 19
            };
            
            // 지도 생성
            this.map = new naver.maps.Map(mapContainer, mapOptions);
            this.mapInitialized = true;
            
            // 지도 로드 완료 표시
            mapContainer.classList.add('map-loaded');
            
            // API 인증 실패 즉시 체크
            setTimeout(() => {
                // 네이버 지도 인증 실패 시 생성되는 요소 확인
                const authError = mapContainer.querySelector('.map_auth_error_box, .map-error, [class*="auth"], [class*="error"]');
                const mapTiles = mapContainer.querySelectorAll('img[src*="map"], img[src*="tile"]');
                
                if (authError || mapTiles.length === 0) {
                    console.error('네이버 지도 API 인증 실패 감지');
                    this.mapInitialized = false;
                    this.map = null;
                    
                    mapContainer.innerHTML = `
                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f5f5f5;">
                            <div style="text-align: center; padding: 2rem;">
                                <div style="font-size: 3rem; margin-bottom: 1rem;">🗺️</div>
                                <h3 style="margin: 0 0 0.5rem 0;">지도 인증 오류</h3>
                                <p style="color: #666; margin: 0 0 1rem 0;">로컬 환경에서는 네이버 지도가 제한될 수 있습니다</p>
                                <div style="background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 400px; margin: 0 auto;">
                                    <p style="margin: 0 0 0.5rem 0; font-size: 0.875rem; color: #333;">
                                        네이버 클라우드 플랫폼에서 다음 도메인을 등록해주세요:
                                    </p>
                                    <code style="display: block; background: #f5f5f5; padding: 0.5rem; border-radius: 4px; font-size: 0.75rem; margin: 0.5rem 0;">
                                        http://sungsuya-v2.local/*
                                    </code>
                                    <p style="margin: 0.5rem 0 0 0; font-size: 0.75rem; color: #666;">
                                        실제 서비스 환경에서는 정상 작동합니다.
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;
                    return;
                }
                
                console.log('✅ 지도 정상 로드 확인');
            }, 500); // 500ms로 단축
            
            // 지도가 제대로 표시되는지 확인
            console.log('✅ 지도 객체 생성 완료:', this.map);
            console.log('📏 지도 크기:', this.map.getSize());
            console.log('📍 지도 중심:', this.map.getCenter());
            
            // 지도 크기 조정 이벤트 (여러 번 시도)
            const resizeTimes = [100, 300, 500, 1000];
            resizeTimes.forEach(time => {
                setTimeout(() => {
                    if (this.map && naver.maps && naver.maps.Event) {
                        naver.maps.Event.trigger(this.map, 'resize');
                        const size = this.map.getSize();
                        console.log(`🔄 ${time}ms 후 리사이즈 - 크기: ${size.width}x${size.height}`);
                    }
                }, time);
            });
            
            // 윈도우 리사이즈 이벤트
            this.handleResize = () => {
                if (this.map && naver.maps) {
                    setTimeout(() => {
                        naver.maps.Event.trigger(this.map, 'resize');
                    }, 100);
                }
            };
            window.addEventListener('resize', this.handleResize);
            
            console.log('✅ 지도 초기화 완료');
            
            // 선택된 장소가 있으면 마커 표시
            if (this.selectedPlaces.length > 0) {
                this.updateMap();
            }
            
        } catch (error) {
            console.error('지도 초기화 실패:', error);
            mapContainer.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f5f5f5;">
                    <div style="text-align: center; padding: 2rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">❌</div>
                        <h3 style="margin: 0 0 0.5rem 0;">지도 초기화 실패</h3>
                        <p style="color: #666; margin: 0;">${error.message}</p>
                    </div>
                </div>
            `;
        }
    }
    
    /**
     * 지도 마커 업데이트
     */
    updateMap() {
        // 네이버 지도 API가 로드되지 않았으면 종료
        if (!window.naver || !window.naver.maps) {
            console.log('⏳ 네이버 지도 API가 아직 로드되지 않음');
            return;
        }
        
        if (!this.map || !this.mapInitialized) {
            console.log('⏳ 지도가 아직 초기화되지 않았습니다');
            return;
        }
        
        // 지도가 유효한지 확인
        try {
            if (!this.map.getCenter) {
                console.error('지도 객체가 손상되었습니다');
                return;
            }
        } catch (e) {
            console.error('지도 상태 확인 실패:', e);
            return;
        }
        
        // 기존 마커 안전하게 제거
        if (this.markers && this.markers.length > 0) {
            this.markers.forEach(marker => {
                try {
                    if (marker && marker.setMap) {
                        marker.setMap(null);
                    }
                } catch (e) {
                    console.warn('마커 제거 중 오류:', e);
                }
            });
        }
        this.markers = [];
        
        // 기존 경로 안전하게 제거
        if (this.polyline) {
            try {
                this.polyline.setMap(null);
            } catch (e) {
                console.warn('경로 제거 중 오류:', e);
            }
            this.polyline = null;
        }
        
        // 선택된 장소가 없으면 기본 위치로
        if (this.selectedPlaces.length === 0) {
            try {
                this.map.setCenter(new naver.maps.LatLng(37.5444, 127.0557));
                this.map.setZoom(15);
            } catch (e) {
                console.warn('지도 중심 설정 중 오류:', e);
            }
            return;
        }
        
        // 새 마커 추가
        this.selectedPlaces.forEach((place, index) => {
            try {
                const marker = new naver.maps.Marker({
                    position: new naver.maps.LatLng(place.location.lat, place.location.lng),
                    map: this.map,
                    title: place.title,
                    icon: {
                        content: `<div style="
                            width: 32px;
                            height: 32px;
                            background: #3b82f6;
                            color: white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-weight: 600;
                            font-size: 14px;
                            border: 2px solid white;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                        ">${index + 1}</div>`,
                        size: new naver.maps.Size(32, 32),
                        anchor: new naver.maps.Point(16, 16)
                    }
                });
                
                // 마커 클릭 이벤트 추가
                naver.maps.Event.addListener(marker, 'click', () => {
                    // 장소 카드로 스크롤
                    const placeCard = document.querySelector(`[data-place-id="${place.id}"]`);
                    if (placeCard) {
                        placeCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        // 강조 효과
                        placeCard.classList.add('highlight');
                        setTimeout(() => {
                            placeCard.classList.remove('highlight');
                        }, 2000);
                    }
                });
                
                this.markers.push(marker);
            } catch (e) {
                console.error(`마커 생성 실패 (${place.title}):`, e);
            }
        });
        
        // 경로 표시 (2개 이상 장소 선택 시)
        if (this.selectedPlaces.length >= 2) {
            try {
                const path = this.selectedPlaces.map(place => 
                    new naver.maps.LatLng(place.location.lat, place.location.lng)
                );
                
                this.polyline = new naver.maps.Polyline({
                    path: path,
                    strokeColor: '#3b82f6',
                    strokeWeight: 4,
                    strokeOpacity: 0.8,
                    strokeStyle: 'solid',
                    map: this.map
                });
                
                // 화살표 추가 (선택사항)
                if (naver.maps.PointingIcon) {
                    this.polyline.setOptions({
                        startIcon: naver.maps.PointingIcon.CIRCLE,
                        endIcon: naver.maps.PointingIcon.OPEN_ARROW
                    });
                }
            } catch (e) {
                console.error('경로 생성 실패:', e);
            }
        }
        
        // 지도 범위 조정
        try {
            const bounds = new naver.maps.LatLngBounds();
            this.selectedPlaces.forEach(place => {
                bounds.extend(new naver.maps.LatLng(place.location.lat, place.location.lng));
            });
            this.map.fitBounds(bounds, { 
                padding: { top: 50, right: 50, bottom: 50, left: 50 }
            });
        } catch (e) {
            console.error('지도 범위 조정 실패:', e);
        }
    }
    
    /**
     * 공유 URL 생성
     */
    generateShareUrl() {
        const placeIds = this.selectedPlaces.map(p => p.id);
        const data = btoa(JSON.stringify(placeIds));
        return `${this.config.homeUrl}/tour-v2?data=${data}`;
    }
    
    /**
     * 공유 모달 표시
     */
    showShareModal() {
        const shareUrl = this.generateShareUrl();
        this.elements.shareUrl.value = shareUrl;
        this.elements.shareModal.style.display = 'flex';
    }
    
    /**
     * Web Share API 사용 (PWA)
     */
    async shareWithWebAPI() {
        if (navigator.share) {
            try {
                const shareData = {
                    title: '🗺️ 나의 성수동 투어',
                    text: `${this.selectedPlaces.length}개 장소: ${this.selectedPlaces.map(p => p.title).join(', ')}`,
                    url: this.generateShareUrl()
                };
                await navigator.share(shareData);
                this.showToast('공유되었습니다');
            } catch (err) {
                if (err.name !== 'AbortError') {
                    this.showShareModal();
                }
            }
        } else {
            this.showShareModal();
        }
    }
    
    /**
     * URL에서 공유된 투어 확인
     */
    checkSharedTour() {
        const urlParams = new URLSearchParams(window.location.search);
        const data = urlParams.get('data');
        
        if (!data) return;
        
        try {
            const placeIds = JSON.parse(atob(data));
            
            // 장소 데이터가 로드된 후 복원
            const checkAndRestore = () => {
                if (this.places.length === 0) {
                    setTimeout(checkAndRestore, 100);
                    return;
                }
                
                placeIds.forEach(id => {
                    const place = this.places.find(p => p.id === id);
                    if (place && this.selectedPlaces.length < 8) {
                        this.selectedPlaces.push(place);
                    }
                });
                
                this.updateUI();
                this.showToast('공유된 투어를 불러왔습니다');
            };
            
            checkAndRestore();
            
        } catch (error) {
            console.error('공유된 투어 복원 실패:', error);
        }
    }
    
    /**
     * 로컬스토리지 저장
     */
    saveToStorage() {
        // 현재 투어 저장
        const currentTour = {
            selectedPlaces: this.selectedPlaces,
            savedAt: new Date().toISOString()
        };
        
        localStorage.setItem('tourV2Data', JSON.stringify(currentTour));
        
        // 저장된 투어 목록 업데이트
        this.updateSavedToursList();
    }
    
    /**
     * 투어 저장 (이름 지정)
     */
    saveTourWithName() {
        if (this.selectedPlaces.length === 0) {
            this.showToast('저장할 장소를 선택해주세요', 'error');
            return;
        }
        
        // 인증 체크
        if (!window.sungsuyaAuth || !window.sungsuyaAuth.user || window.sungsuyaAuth.isGuest) {
            this.showToast('투어를 저장하려면 로그인이 필요합니다', 'info');
            
            // 인증 모달 표시
            if (window.sungsuyaAuth) {
                window.sungsuyaAuth.showAuthModal();
                
                // 로그인 성공 시 저장 진행
                window.onAuthSuccess = (user) => {
                    this.saveTourWithName();
                    window.onAuthSuccess = null; // 리스너 제거
                };
            }
            return;
        }
        
        const tourName = prompt('투어 이름을 입력하세요:', `성수동 투어 ${new Date().toLocaleDateString()}`);
        if (!tourName) return;
        
        // 저장된 투어 목록 가져오기
        const savedTours = JSON.parse(localStorage.getItem('savedTours') || '[]');
        
        // 새 투어 추가
        const newTour = {
            id: Date.now(),
            name: tourName,
            places: this.selectedPlaces,
            savedAt: new Date().toISOString(),
            userId: window.sungsuyaAuth.user.id // 사용자 ID 추가
        };
        
        savedTours.unshift(newTour); // 최신 항목을 맨 앞에
        
        // 최대 10개까지만 저장
        if (savedTours.length > 10) {
            savedTours.pop();
        }
        
        localStorage.setItem('savedTours', JSON.stringify(savedTours));
        
        this.showToast(`"${tourName}" 투어가 저장되었습니다`);
        this.updateSavedToursList();
        this.updateSavedToursManager(); // 모달 내용도 업데이트
    }
    
    /**
     * 저장된 투어 목록 업데이트
     */
    updateSavedToursList() {
        const savedTours = JSON.parse(localStorage.getItem('savedTours') || '[]');
        const listElement = document.getElementById('saved-tours-list');
        
        if (!listElement) return;
        
        // 현재 사용자의 투어만 필터링
        const userTours = savedTours.filter(tour => {
            // 사용자 ID가 없는 기존 투어는 모두 표시
            if (!tour.userId) return true;
            // 로그인한 사용자의 투어만 표시
            return window.sungsuyaAuth && window.sungsuyaAuth.user && 
                   tour.userId === window.sungsuyaAuth.user.id;
        });
        
        if (userTours.length === 0) {
            listElement.innerHTML = '<p style="text-align: center; color: var(--text-secondary); font-size: 0.813rem;">저장된 투어가 없습니다</p>';
            return;
        }
        
        const toursHTML = userTours.map(tour => {
            const date = new Date(tour.savedAt).toLocaleDateString();
            return `
                <div class="saved-tour-item" data-tour-id="${tour.id}">
                    <div class="saved-tour-info">
                        <div class="saved-tour-name">${tour.name}</div>
                        <div class="saved-tour-meta">
                            <span>📍 ${tour.places.length}개</span>
                            <span>📅 ${date}</span>
                        </div>
                    </div>
                    <button class="load-tour-btn" onclick="tourPlanner.loadSavedTour(${tour.id})" title="불러오기">
                        <span>📂</span>
                    </button>
                </div>
            `;
        }).join('');
        
        listElement.innerHTML = toursHTML;
    }
    
    /**
     * 저장된 투어 관리 모달 업데이트
     */
    updateSavedToursManager() {
        const savedTours = JSON.parse(localStorage.getItem('savedTours') || '[]');
        const managerElement = document.getElementById('saved-tours-manager');
        
        if (!managerElement) return;
        
        // 현재 사용자의 투어만 필터링
        const userTours = savedTours.filter(tour => {
            // 사용자 ID가 없는 기존 투어는 모두 표시
            if (!tour.userId) return true;
            // 로그인한 사용자의 투어만 표시
            return window.sungsuyaAuth && window.sungsuyaAuth.user && 
                   tour.userId === window.sungsuyaAuth.user.id;
        });
        
        if (userTours.length === 0) {
            managerElement.innerHTML = `
                <div class="empty-state">
                    <span class="empty-icon">💾</span>
                    <p>저장된 투어가 없습니다</p>
                    <small>현재 투어를 저장해보세요</small>
                </div>
            `;
            return;
        }
        
        const managerHTML = userTours.map(tour => {
            const date = new Date(tour.savedAt).toLocaleDateString();
            const places = tour.places.map(p => p.title).join(', ');
            
            return `
                <div class="saved-tour-manager-item">
                    <div class="tour-details">
                        <h4>${tour.name}</h4>
                        <p class="tour-places">${tour.places.length}개 장소: ${places}</p>
                        <p class="tour-date">저장일: ${date}</p>
                    </div>
                    <div class="tour-actions">
                        <button class="btn btn-sm btn-primary" onclick="tourPlanner.loadSavedTour(${tour.id})">
                            불러오기
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="tourPlanner.deleteSavedTour(${tour.id})">
                            삭제
                        </button>
                    </div>
                </div>
            `;
        }).join('');
        
        managerElement.innerHTML = managerHTML;
    }
    
    /**
     * 저장된 투어 불러오기
     */
    loadSavedTour(tourId) {
        const savedTours = JSON.parse(localStorage.getItem('savedTours') || '[]');
        const tour = savedTours.find(t => t.id === tourId);
        
        if (!tour) {
            this.showToast('투어를 찾을 수 없습니다', 'error');
            return;
        }
        
        // 현재 선택 초기화
        this.selectedPlaces = [];
        
        // 저장된 장소 복원
        tour.places.forEach(savedPlace => {
            const place = this.places.find(p => p.id === savedPlace.id);
            if (place) {
                this.selectedPlaces.push(place);
            }
        });
        
        this.updateUI();
        this.showToast(`"${tour.name}" 투어를 불러왔습니다`);
    }
    
    /**
     * 저장된 투어 삭제
     */
    deleteSavedTour(tourId) {
        const savedTours = JSON.parse(localStorage.getItem('savedTours') || '[]');
        const tour = savedTours.find(t => t.id === tourId);
        
        if (!tour) {
            this.showToast('투어를 찾을 수 없습니다', 'error');
            return;
        }
        
        if (!confirm(`"${tour.name}" 투어를 삭제하시겠습니까?`)) return;
        
        const filteredTours = savedTours.filter(t => t.id !== tourId);
        localStorage.setItem('savedTours', JSON.stringify(filteredTours));
        
        this.updateSavedToursList();
        this.updateSavedToursManager();
        this.showToast('투어가 삭제되었습니다');
    }
    
    /**
     * 로컬스토리지에서 복원
     */
    loadFromStorage() {
        try {
            const saved = localStorage.getItem('tourV2Data');
            if (!saved) return;
            
            const data = JSON.parse(saved);
            
            // 7일 이상 지난 데이터는 무시
            const savedDate = new Date(data.savedAt);
            const now = new Date();
            const daysDiff = (now - savedDate) / (1000 * 60 * 60 * 24);
            
            if (daysDiff > 7) {
                localStorage.removeItem('tourV2Data');
                return;
            }
            
            // 복원
            this.selectedPlaces = data.selectedPlaces || [];
            this.updateUI();
            
        } catch (error) {
            console.error('저장된 데이터 복원 실패:', error);
        }
    }
    
    /**
     * 드래그 앤 드롭 초기화
     */
    initSortable() {
        if (typeof Sortable === 'undefined') {
            console.warn('Sortable.js가 로드되지 않았습니다');
            return;
        }
        
        new Sortable(this.elements.selectedList, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            filter: '.empty-state',
            onStart: (evt) => {
                // 드래그 시작 시 컨테이너에 클래스 추가
                this.elements.selectedList.classList.add('drag-over');
            },
            onEnd: (evt) => {
                // 드래그 종료 시 컨테이너 클래스 제거
                this.elements.selectedList.classList.remove('drag-over');
                
                // 순서 재정렬
                const item = this.selectedPlaces.splice(evt.oldIndex, 1)[0];
                this.selectedPlaces.splice(evt.newIndex, 0, item);
                
                // UI 업데이트
                this.updateUI();
            }
        });
    }
    
    /**
     * 이벤트 바인딩
     */
    bindEvents() {
        // 장소 클릭
        this.elements.placesList.addEventListener('click', (e) => {
            const card = e.target.closest('.place-card');
            if (card) {
                this.togglePlace(card.dataset.placeId);
            }
        });
        
        // 장소 제거
        this.elements.selectedList.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.remove-btn');
            if (removeBtn) {
                this.removePlace(removeBtn.dataset.placeId);
            }
        });
        
        // 필터 버튼
        this.elements.filterButtons.addEventListener('click', (e) => {
            const btn = e.target.closest('.filter-btn');
            if (!btn) return;
            
            // 활성 상태 변경
            this.elements.filterButtons.querySelectorAll('.filter-btn').forEach(b => {
                b.classList.remove('active');
            });
            btn.classList.add('active');
            
            // 필터링
            const type = btn.dataset.type;
            const search = this.elements.searchInput.value;
            this.renderPlaces(type, search);
        });
        
        // 검색
        let searchTimeout;
        this.elements.searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const activeFilter = this.elements.filterButtons.querySelector('.filter-btn.active');
                const type = activeFilter ? activeFilter.dataset.type : 'all';
                this.renderPlaces(type, e.target.value);
            }, 300);
        });
        
        // 공유 버튼
        this.elements.shareBtn.addEventListener('click', () => {
            this.shareWithWebAPI();
        });
        
        // 공유 버튼에 길게 누르기로 저장 기능 추가
        let pressTimer;
        this.elements.shareBtn.addEventListener('mousedown', () => {
            pressTimer = window.setTimeout(() => {
                // 인증 체크
                if (!window.sungsuyaAuth || !window.sungsuyaAuth.user || window.sungsuyaAuth.isGuest) {
                    this.showToast('투어를 저장하려면 로그인이 필요합니다', 'info');
                    window.sungsuyaAuth.showAuthModal();
                    
                    // 로그인 성공 시 저장 진행
                    window.onAuthSuccess = (user) => {
                        this.saveTourWithName();
                        window.onAuthSuccess = null;
                    };
                } else {
                    this.saveTourWithName();
                }
            }, 1000);
        });
        
        this.elements.shareBtn.addEventListener('mouseup', () => {
            clearTimeout(pressTimer);
        });
        
        this.elements.shareBtn.addEventListener('mouseleave', () => {
            clearTimeout(pressTimer);
        });
        
        // 최적화 버튼
        this.elements.optimizeBtn.addEventListener('click', () => {
            this.optimizeTourRoute();
        });
        
        // 초기화 버튼
        this.elements.resetBtn.addEventListener('click', () => {
            this.resetTour();
        });
        
        // 저장된 투어 관리 버튼
        const manageToursBtn = document.getElementById('manage-tours-btn');
        if (manageToursBtn) {
            manageToursBtn.addEventListener('click', () => {
                this.showSavedToursModal();
            });
        }
        
        // 저장된 투어 목록 클릭 이벤트 (이벤트 위임)
        const savedToursList = document.getElementById('saved-tours-list');
        if (savedToursList) {
            savedToursList.addEventListener('click', (e) => {
                const tourItem = e.target.closest('.saved-tour-item');
                if (tourItem && !e.target.closest('.load-tour-btn')) {
                    const tourId = parseInt(tourItem.dataset.tourId);
                    this.loadSavedTour(tourId);
                }
            });
        }
        
        // 모바일 버튼
        const mobileShareBtn = document.getElementById('mobile-share-btn');
        const mobileResetBtn = document.getElementById('mobile-reset-btn');
        const mobileBottomActions = document.querySelector('.mobile-bottom-actions');
        
        if (mobileShareBtn) {
            mobileShareBtn.addEventListener('click', () => {
                this.shareWithWebAPI();
            });
        }
        
        if (mobileResetBtn) {
            mobileResetBtn.addEventListener('click', () => {
                this.resetTour();
            });
        }
        
        // 모바일 하단 버튼 표시/숨김
        const checkMobileView = () => {
            if (window.innerWidth <= 768) {
                if (mobileBottomActions && this.selectedPlaces.length > 0) {
                    mobileBottomActions.style.display = 'flex';
                } else if (mobileBottomActions) {
                    mobileBottomActions.style.display = 'none';
                }
            } else if (mobileBottomActions) {
                mobileBottomActions.style.display = 'none';
            }
        };
        
        window.addEventListener('resize', checkMobileView);
        checkMobileView();
        
        // 모바일 탭 전환 - 문서 전체에 이벤트 위임
        console.log('🔍 모바일 탭 이벤트 등록 시작');
        const self = this; // this 참조 저장
        document.addEventListener('click', (e) => {
            // 모바일 탭 클릭 처리
            const tab = e.target.closest('.mobile-tab');
            if (!tab) return;
            
            console.log('✅ 모바일 탭 클릭됨:', tab.dataset.tab);
            e.preventDefault();
            e.stopPropagation();
            
            // 탭 활성화
            document.querySelectorAll('.mobile-tab').forEach(t => {
                t.classList.remove('active');
            });
            tab.classList.add('active');
            
            // 패널 전환
            const tabName = tab.dataset.tab;
            document.querySelectorAll('.mobile-panel').forEach(panel => {
                if (panel.dataset.panel === tabName) {
                    panel.classList.add('active');
                    panel.style.display = 'flex'; // 강제로 display 설정
                    console.log(`✅ ${panel.dataset.panel} 패널 활성화`);
                } else {
                    panel.classList.remove('active');
                    panel.style.display = 'none'; // 강제로 display 설정
                }
            });
            
            // 지도 탭 선택 시 지도 크기 조정
            if (tabName === 'map' && self.map && self.mapInitialized) {
                setTimeout(() => {
                    window.dispatchEvent(new Event('resize'));
                    if (self.map && window.naver && window.naver.maps && naver.maps.Event) {
                        naver.maps.Event.trigger(self.map, 'resize');
                        // 컨테이너 크기 다시 확인
                        const mapContainer = document.getElementById('tour-map');
                        if (mapContainer && mapContainer.offsetHeight > 0) {
                            self.map.setSize(new naver.maps.Size(mapContainer.offsetWidth, mapContainer.offsetHeight));
                        }
                    }
                }, 100);
            }
        });
        
        // 모바일에서 스와이프 제스처 (선택사항)
        let touchStartX = 0;
        let touchEndX = 0;
        
        const handleSwipe = () => {
            const swipeThreshold = 50;
            const diff = touchStartX - touchEndX;
            
            if (Math.abs(diff) < swipeThreshold) return;
            
            const currentTab = document.querySelector('.mobile-tab.active');
            const tabs = Array.from(document.querySelectorAll('.mobile-tab'));
            const currentIndex = tabs.indexOf(currentTab);
            
            let newIndex = currentIndex;
            if (diff > 0 && currentIndex < tabs.length - 1) {
                // 왼쪽으로 스와이프 (다음 탭)
                newIndex = currentIndex + 1;
            } else if (diff < 0 && currentIndex > 0) {
                // 오른쪽으로 스와이프 (이전 탭)
                newIndex = currentIndex - 1;
            }
            
            if (newIndex !== currentIndex) {
                tabs[newIndex].click();
            }
        };
        
        document.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        document.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            if (window.innerWidth <= 768) {
                handleSwipe();
            }
        });
    }
    
    /**
     * 저장된 투어 관리 모달 표시
     */
    showSavedToursModal() {
        // 인증 체크
        if (!window.sungsuyaAuth || !window.sungsuyaAuth.user || window.sungsuyaAuth.isGuest) {
            this.showToast('투어를 관리하려면 로그인이 필요합니다', 'info');
            window.sungsuyaAuth.showAuthModal();
            
            // 로그인 성공 시 모달 표시
            window.onAuthSuccess = (user) => {
                this.updateAuthBasedUI();
                this.showSavedToursModal();
                window.onAuthSuccess = null;
            };
            return;
        }
        
        this.updateSavedToursManager();
        this.updateSavedToursList(); // 모달 표시 전에 목록도 업데이트
        const modal = document.getElementById('saved-tours-modal');
        if (modal) {
            modal.style.display = 'flex';
        }
    }
    
    /**
     * 토스트 메시지 표시
     */
    showToast(message, type = 'success') {
        // 기존 토스트 제거
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            existingToast.remove();
        }
        
        // 토스트 컨테이너 생성
        const toastContainer = document.createElement('div');
        toastContainer.style.cssText = `
            position: fixed;
            bottom: 2rem;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            z-index: 1100;
            pointer-events: none;
        `;
        
        // 토스트 메시지 생성
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        
        toastContainer.appendChild(toast);
        document.body.appendChild(toastContainer);
        
        // 3초 후 제거
        setTimeout(() => {
            toastContainer.remove();
        }, 3000);
    }
    
    /**
     * 에러 표시
     */
    showError(message) {
        console.error(message);
        this.showToast(message, 'error');
    }
    
    /**
     * 컴포넌트 정리 (페이지 이동 시 호출)
     */
    destroy() {
        console.log('🧹 TourPlannerV2 정리 시작');
        
        // 지도 관련 정리
        if (this.map) {
            try {
                // 모든 마커 제거
                if (this.markers) {
                    this.markers.forEach(marker => {
                        try {
                            marker.setMap(null);
                        } catch (e) {}
                    });
                }
                
                // 경로 제거
                if (this.polyline) {
                    try {
                        this.polyline.setMap(null);
                    } catch (e) {}
                }
                
                // 지도 파괴
                this.map.destroy();
            } catch (e) {
                console.warn('지도 정리 중 오류:', e);
            }
        }
        
        // 이벤트 리스너 제거
        if (this.resizeHandler) {
            window.removeEventListener('resize', this.resizeHandler);
        }
        if (this.handleResize) {
            window.removeEventListener('resize', this.handleResize);
        }
        
        // 타이머 정리
        if (this.updateMapTimeout) {
            clearTimeout(this.updateMapTimeout);
        }
        
        // 초기화 플래그 리셋
        this.mapInitialized = false;
        
        console.log('✅ TourPlannerV2 정리 완료');
    }
}

// 전역 함수들
window.closeShareModal = function() {
    document.getElementById('share-modal').style.display = 'none';
};

window.closeSavedToursModal = function() {
    document.getElementById('saved-tours-modal').style.display = 'none';
};

window.copyShareUrl = function() {
    const input = document.getElementById('share-url');
    input.select();
    document.execCommand('copy');
    
    tourPlanner.showToast('링크가 복사되었습니다');
};

window.shareToKakao = function() {
    if (!window.Kakao) {
        alert('카카오톡 공유 기능을 사용할 수 없습니다');
        return;
    }
    
    // Kakao SDK 초기화 확인
    if (!Kakao.isInitialized()) {
        const kakaoKey = '<?php echo esc_js(get_option("kakao_javascript_key")); ?>';
        if (kakaoKey) {
            Kakao.init(kakaoKey);
        }
    }
    
    const shareUrl = document.getElementById('share-url').value;
    const placeNames = tourPlanner.selectedPlaces.map(p => p.title).join(', ');
    
    Kakao.Share.sendDefault({
        objectType: 'feed',
        content: {
            title: '🗺️ 나의 성수동 투어',
            description: `${tourPlanner.selectedPlaces.length}개 장소: ${placeNames}`,
            imageUrl: '<?php echo get_template_directory_uri(); ?>/assets/images/tour-share.jpg',
            link: {
                mobileWebUrl: shareUrl,
                webUrl: shareUrl
            }
        },
        buttons: [{
            title: '투어 보기',
            link: {
                mobileWebUrl: shareUrl,
                webUrl: shareUrl
            }
        }]
    });
};

window.shareToInstagram = function() {
    // 인스타그램은 직접 공유 API가 없으므로 안내 메시지
    alert('링크를 복사한 후 인스타그램 스토리나 프로필에 공유해주세요');
    copyShareUrl();
};

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', () => {
    console.log('🎯 TourPlannerV2 시작');
    window.tourPlanner = new TourPlannerV2();
    
    // Service Worker 등록 (PWA)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('Service Worker 등록 성공:', registration.scope);
            })
            .catch(error => {
                console.log('Service Worker 등록 실패:', error);
            });
    }
});

// 페이지 언로드 시 정리
window.addEventListener('beforeunload', () => {
    if (window.tourPlanner && typeof window.tourPlanner.destroy === 'function') {
        window.tourPlanner.destroy();
    }
});
