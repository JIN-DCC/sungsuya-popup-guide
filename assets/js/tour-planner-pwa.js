/**
 * 투어플래너 PWA 전용 스크립트
 * 완전히 새로운 UI/UX로 재구성
 * 
 * @since 2025-06-30
 */

class TourPlannerPWA {
    constructor() {
        // 데이터
        this.places = [];
        this.placeTypes = [];
        this.selectedPlaces = [];
        
        // 지도 관련
        this.map = null;
        this.markers = [];
        this.polyline = null;
        this.mapInitialized = false;
        
        // 현재 뷰
        this.currentView = 'map';
        
        // 설정
        this.config = window.tourPWAConfig || {};
        
        // 초기화
        this.init();
    }
    
    async init() {
        console.log('🚀 TourPlanner PWA 초기화');
        
        try {
            // 1. UI 요소 캐싱
            this.cacheElements();
            
            // 2. 이벤트 바인딩
            this.bindEvents();
            
            // 3. 장소 유형 로드
            await this.loadPlaceTypes();
            
            // 4. 장소 데이터 로드
            await this.loadPlaces();
            
            // 5. URL 파라미터 체크
            this.checkSharedTour();
            
            // 6. 드래그 앤 드롭 설정
            this.initSortable();
            
            // 7. 저장된 데이터 복원
            this.loadFromStorage();
            
            console.log('✅ PWA 초기화 완료');
            
        } catch (error) {
            console.error('초기화 실패:', error);
            this.showToast('앱을 불러오는 중 오류가 발생했습니다', 'error');
        }
    }
    
    cacheElements() {
        this.elements = {
            // 뷰
            views: document.querySelectorAll('.pwa-view'),
            tabs: document.querySelectorAll('.pwa-tab'),
            
            // 장소 목록
            placesList: document.getElementById('places-list'),
            filterChips: document.getElementById('filter-chips'),
            searchInput: document.getElementById('place-search'),
            
            // 선택된 장소
            selectedList: document.getElementById('selected-list'),
            selectedCount: document.getElementById('selected-count'),
            tourBadge: document.getElementById('tour-badge'),
            
            // 요약
            totalPlaces: document.getElementById('total-places'),
            totalTime: document.getElementById('total-time'),
            totalDistance: document.getElementById('total-distance'),
            
            // 버튼
            shareBtn: document.getElementById('share-btn'),
            optimizeBtn: document.getElementById('optimize-btn'),
            menuBtn: document.getElementById('pwa-menu-btn'),
            
            // 모달
            shareModal: document.getElementById('share-modal'),
            menuModal: document.getElementById('menu-modal'),
            
            // 기타
            toastContainer: document.getElementById('toast-container')
        };
    }
    
    bindEvents() {
        // 탭 네비게이션
        this.elements.tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                this.switchView(tab.dataset.tab);
            });
        });
        
        // 메뉴 버튼
        this.elements.menuBtn.addEventListener('click', () => {
            this.showModal('menu-modal');
        });
        
        // 지도 지연 로딩
        const mapPlaceholder = document.querySelector('.map-placeholder');
        if (mapPlaceholder) {
            mapPlaceholder.addEventListener('click', () => {
                this.loadMap();
            });
        }
        
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
        
        // 필터
        this.elements.filterChips.addEventListener('click', (e) => {
            const chip = e.target.closest('.chip');
            if (chip) {
                this.setFilter(chip.dataset.type);
            }
        });
        
        // 검색
        let searchTimeout;
        this.elements.searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.renderPlaces(this.currentFilter, e.target.value);
            }, 300);
        });
        
        // 공유 버튼
        this.elements.shareBtn.addEventListener('click', () => {
            this.share();
        });
        
        // 경로 최적화
        this.elements.optimizeBtn.addEventListener('click', () => {
            this.optimizeRoute();
        });
        
        // PWA 설치 프롬프트
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
        });
    }
    
    switchView(viewName) {
        // 뷰 전환
        this.elements.views.forEach(view => {
            view.classList.toggle('active', view.dataset.view === viewName);
        });
        
        // 탭 활성화
        this.elements.tabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.tab === viewName);
        });
        
        this.currentView = viewName;
        
        // 지도 뷰로 전환 시 지도 로드
        if (viewName === 'map' && !this.mapInitialized) {
            // 자동으로 지도 로드하지 않음 (사용자가 클릭해야 함)
        }
    }
    
    async loadPlaceTypes() {
        try {
            const response = await fetch(`${this.config.apiBase}/sungsuya/v2/place-types`);
            if (!response.ok) throw new Error('장소 유형 로드 실패');
            
            this.placeTypes = await response.json();
            this.renderFilters();
            
        } catch (error) {
            console.error('장소 유형 로드 오류:', error);
            this.placeTypes = [
                { id: 1, slug: 'popup_store', name: '팝업스토어', icon: '🏪' },
                { id: 2, slug: 'restaurant', name: '맛집', icon: '🍽️' },
                { id: 3, slug: 'cafe', name: '카페', icon: '☕' },
                { id: 4, slug: 'retail_store', name: '상설매장', icon: '🏬' }
            ];
            this.renderFilters();
        }
    }
    
    async loadPlaces() {
        try {
            this.elements.placesList.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>장소를 불러오는 중...</p>
                </div>
            `;
            
            const response = await fetch(`${this.config.apiBase}/wp/v2/places?per_page=100&_embed`);
            if (!response.ok) throw new Error('장소 로드 실패');
            
            const places = await response.json();
            
            // 메타데이터 포함하여 저장
            this.places = places.map(place => ({
                id: place.id,
                title: place.title.rendered,
                type: this.getPlaceType(place),
                address: place.meta?.address || '성수동',
                location: {
                    lat: parseFloat(place.meta?.latitude) || 37.5444,
                    lng: parseFloat(place.meta?.longitude) || 127.0557
                }
            }));
            
            this.renderPlaces();
            
        } catch (error) {
            console.error('장소 로드 오류:', error);
            this.elements.placesList.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">❌</div>
                    <h3>오류가 발생했습니다</h3>
                    <p>잠시 후 다시 시도해주세요</p>
                </div>
            `;
        }
    }
    
    getPlaceType(place) {
        if (place._embedded && place._embedded['wp:term']) {
            const terms = place._embedded['wp:term'];
            for (const termGroup of terms) {
                for (const term of termGroup) {
                    if (term.taxonomy === 'place_type') {
                        return term.slug;
                    }
                }
            }
        }
        return 'place';
    }
    
    renderFilters() {
        const filtersHTML = `
            <button class="chip active" data-type="all">전체</button>
            ${this.placeTypes.map(type => `
                <button class="chip" data-type="${type.slug}">
                    ${type.icon} ${type.name}
                </button>
            `).join('')}
        `;
        
        this.elements.filterChips.innerHTML = filtersHTML;
        this.currentFilter = 'all';
    }
    
    setFilter(type) {
        this.currentFilter = type;
        
        // 필터 활성화
        this.elements.filterChips.querySelectorAll('.chip').forEach(chip => {
            chip.classList.toggle('active', chip.dataset.type === type);
        });
        
        this.renderPlaces(type, this.elements.searchInput.value);
    }
    
    renderPlaces(filter = 'all', search = '') {
        let filteredPlaces = this.places;
        
        // 필터링
        if (filter !== 'all') {
            filteredPlaces = filteredPlaces.filter(place => place.type === filter);
        }
        
        // 검색
        if (search) {
            const searchLower = search.toLowerCase();
            filteredPlaces = filteredPlaces.filter(place => 
                place.title.toLowerCase().includes(searchLower) ||
                place.address.toLowerCase().includes(searchLower)
            );
        }
        
        // 렌더링
        if (filteredPlaces.length === 0) {
            this.elements.placesList.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">🔍</div>
                    <h3>검색 결과가 없습니다</h3>
                    <p>다른 검색어를 시도해보세요</p>
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
    
    togglePlace(placeId) {
        const place = this.places.find(p => p.id === parseInt(placeId));
        if (!place) return;
        
        const index = this.selectedPlaces.findIndex(p => p.id === place.id);
        
        if (index > -1) {
            this.selectedPlaces.splice(index, 1);
            this.showToast(`${place.title} 제거됨`);
        } else {
            if (this.selectedPlaces.length >= 8) {
                this.showToast('최대 8개까지 선택 가능합니다', 'error');
                return;
            }
            
            this.selectedPlaces.push(place);
            this.showToast(`${place.title} 추가됨`);
        }
        
        this.updateUI();
    }
    
    removePlace(placeId) {
        const index = this.selectedPlaces.findIndex(p => p.id === parseInt(placeId));
        if (index > -1) {
            const place = this.selectedPlaces[index];
            this.selectedPlaces.splice(index, 1);
            this.showToast(`${place.title} 제거됨`);
            this.updateUI();
        }
    }
    
    updateUI() {
        // 장소 카드 업데이트
        this.updatePlaceCards();
        
        // 선택된 장소 목록
        this.updateSelectedList();
        
        // 요약 정보
        this.updateSummary();
        
        // 버튼 상태
        this.updateButtons();
        
        // 배지 업데이트
        this.updateBadges();
        
        // 지도 업데이트
        if (this.mapInitialized) {
            this.updateMap();
        }
        
        // 저장
        this.saveToStorage();
    }
    
    updatePlaceCards() {
        document.querySelectorAll('.place-card').forEach(card => {
            const placeId = parseInt(card.dataset.placeId);
            const isSelected = this.selectedPlaces.some(p => p.id === placeId);
            card.classList.toggle('selected', isSelected);
        });
    }
    
    updateSelectedList() {
        const count = this.selectedPlaces.length;
        this.elements.selectedCount.textContent = count;
        
        if (count === 0) {
            this.elements.selectedList.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3>아직 선택한 장소가 없어요</h3>
                    <p>장소 탭에서 가고 싶은 곳을 선택해주세요</p>
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
                        <div class="selected-name">${place.title}</div>
                        <span class="selected-type">${typeInfo.icon} ${typeInfo.name}</span>
                    </div>
                    <button class="remove-btn" data-place-id="${place.id}">×</button>
                </div>
            `;
        }).join('');
        
        this.elements.selectedList.innerHTML = selectedHTML;
    }
    
    updateSummary() {
        const count = this.selectedPlaces.length;
        
        // 장소 수
        this.elements.totalPlaces.textContent = count;
        
        // 예상 시간
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
        
        // 이동거리
        const avgDistance = 0.5;
        const totalDistance = count > 1 ? (count - 1) * avgDistance : 0;
        this.elements.totalDistance.textContent = `${totalDistance.toFixed(1)}km`;
    }
    
    updateButtons() {
        const hasPlaces = this.selectedPlaces.length > 0;
        const hasMultiple = this.selectedPlaces.length > 1;
        
        this.elements.shareBtn.disabled = !hasPlaces;
        this.elements.optimizeBtn.disabled = !hasMultiple;
    }
    
    updateBadges() {
        const count = this.selectedPlaces.length;
        
        if (count > 0) {
            this.elements.tourBadge.textContent = count;
            this.elements.tourBadge.style.display = 'block';
        } else {
            this.elements.tourBadge.style.display = 'none';
        }
    }
    
    async loadMap() {
        const mapContainer = document.getElementById('tour-map');
        if (!mapContainer || this.mapInitialized) return;
        
        // 로딩 표시
        mapContainer.innerHTML = `
            <div class="loading">
                <div class="spinner"></div>
                <p>지도를 불러오는 중...</p>
            </div>
        `;
        
        // 네이버 지도 API 체크
        if (typeof naver === 'undefined' || !naver.maps) {
            await this.loadNaverMapScript();
        }
        
        // 지도 초기화
        setTimeout(() => {
            this.initMap();
        }, 100);
    }
    
    loadNaverMapScript() {
        return new Promise((resolve, reject) => {
            if (window.naver && window.naver.maps) {
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = `https://openapi.map.naver.com/openapi/v3/maps.js?ncpClientId=${this.config.naverClientId}`;
            script.async = true;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    initMap() {
        const mapOptions = {
            center: new naver.maps.LatLng(37.5444, 127.0557),
            zoom: 15,
            mapTypeControl: false,
            zoomControl: true,
            zoomControlOptions: {
                style: naver.maps.ZoomControlStyle.SMALL,
                position: naver.maps.Position.RIGHT_CENTER
            }
        };
        
        this.map = new naver.maps.Map('tour-map', mapOptions);
        this.mapInitialized = true;
        
        // 선택된 장소가 있으면 마커 표시
        if (this.selectedPlaces.length > 0) {
            this.updateMap();
        }
        
        console.log('✅ 지도 초기화 완료');
    }
    
    updateMap() {
        if (!this.map || !this.mapInitialized) return;
        
        // 기존 마커 제거
        this.markers.forEach(marker => marker.setMap(null));
        this.markers = [];
        
        // 기존 경로 제거
        if (this.polyline) {
            this.polyline.setMap(null);
        }
        
        // 새 마커 추가
        this.selectedPlaces.forEach((place, index) => {
            const marker = new naver.maps.Marker({
                position: new naver.maps.LatLng(place.location.lat, place.location.lng),
                map: this.map,
                title: place.title,
                icon: {
                    content: `<div style="
                        width: 36px;
                        height: 36px;
                        background: #667eea;
                        color: white;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-weight: 600;
                        font-size: 16px;
                        border: 3px solid white;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                    ">${index + 1}</div>`,
                    size: new naver.maps.Size(36, 36),
                    anchor: new naver.maps.Point(18, 18)
                }
            });
            
            this.markers.push(marker);
        });
        
        // 경로 표시
        if (this.selectedPlaces.length >= 2) {
            const path = this.selectedPlaces.map(place => 
                new naver.maps.LatLng(place.location.lat, place.location.lng)
            );
            
            this.polyline = new naver.maps.Polyline({
                path: path,
                strokeColor: '#667eea',
                strokeWeight: 4,
                strokeOpacity: 0.8,
                map: this.map
            });
        }
        
        // 지도 범위 조정
        if (this.selectedPlaces.length > 0) {
            const bounds = new naver.maps.LatLngBounds();
            this.selectedPlaces.forEach(place => {
                bounds.extend(new naver.maps.LatLng(place.location.lat, place.location.lng));
            });
            this.map.fitBounds(bounds, { 
                padding: 50
            });
        }
    }
    
    optimizeRoute() {
        if (this.selectedPlaces.length < 2) return;
        
        // 성수역 기준 최적화
        const station = { lat: 37.5444, lng: 127.0557 };
        
        // Nearest Neighbor 알고리즘
        let optimized = [];
        let remaining = [...this.selectedPlaces];
        let current = station;
        
        while (remaining.length > 0) {
            let nearest = null;
            let nearestDist = Infinity;
            let nearestIdx = -1;
            
            remaining.forEach((place, idx) => {
                const dist = this.getDistance(
                    current.lat, current.lng,
                    place.location.lat, place.location.lng
                );
                if (dist < nearestDist) {
                    nearestDist = dist;
                    nearest = place;
                    nearestIdx = idx;
                }
            });
            
            if (nearest) {
                optimized.push(nearest);
                current = nearest.location;
                remaining.splice(nearestIdx, 1);
            }
        }
        
        this.selectedPlaces = optimized;
        this.updateUI();
        this.showToast('경로가 최적화되었습니다');
    }
    
    getDistance(lat1, lng1, lat2, lng2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
    
    share() {
        if (navigator.share) {
            const shareData = {
                title: '🗺️ 나의 성수동 투어',
                text: `${this.selectedPlaces.length}개 장소: ${this.selectedPlaces.map(p => p.title).join(', ')}`,
                url: this.generateShareUrl()
            };
            
            navigator.share(shareData)
                .then(() => this.showToast('공유되었습니다'))
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        this.showShareModal();
                    }
                });
        } else {
            this.showShareModal();
        }
    }
    
    generateShareUrl() {
        const placeIds = this.selectedPlaces.map(p => p.id);
        const data = btoa(JSON.stringify(placeIds));
        return `${this.config.homeUrl}/tour-pwa?data=${data}`;
    }
    
    showShareModal() {
        const shareUrl = this.generateShareUrl();
        document.getElementById('share-url').value = shareUrl;
        this.showModal('share-modal');
    }
    
    checkSharedTour() {
        const urlParams = new URLSearchParams(window.location.search);
        const data = urlParams.get('data');
        
        if (!data) return;
        
        try {
            const placeIds = JSON.parse(atob(data));
            
            setTimeout(() => {
                placeIds.forEach(id => {
                    const place = this.places.find(p => p.id === id);
                    if (place && this.selectedPlaces.length < 8) {
                        this.selectedPlaces.push(place);
                    }
                });
                
                this.updateUI();
                this.showToast('공유된 투어를 불러왔습니다');
            }, 1000);
            
        } catch (error) {
            console.error('공유 투어 복원 실패:', error);
        }
    }
    
    initSortable() {
        if (typeof Sortable === 'undefined') return;
        
        new Sortable(this.elements.selectedList, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            filter: '.empty-state',
            onEnd: (evt) => {
                const item = this.selectedPlaces.splice(evt.oldIndex, 1)[0];
                this.selectedPlaces.splice(evt.newIndex, 0, item);
                this.updateUI();
            }
        });
    }
    
    saveToStorage() {
        const data = {
            selectedPlaces: this.selectedPlaces,
            savedAt: new Date().toISOString()
        };
        localStorage.setItem('tourPWAData', JSON.stringify(data));
    }
    
    loadFromStorage() {
        try {
            const saved = localStorage.getItem('tourPWAData');
            if (!saved) return;
            
            const data = JSON.parse(saved);
            const savedDate = new Date(data.savedAt);
            const now = new Date();
            const daysDiff = (now - savedDate) / (1000 * 60 * 60 * 24);
            
            if (daysDiff > 7) {
                localStorage.removeItem('tourPWAData');
                return;
            }
            
            this.selectedPlaces = data.selectedPlaces || [];
            this.updateUI();
            
        } catch (error) {
            console.error('저장 데이터 복원 실패:', error);
        }
    }
    
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
        }
    }
    
    showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        
        this.elements.toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
}

// 전역 함수들
window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
};

window.copyShareUrl = function() {
    const input = document.getElementById('share-url');
    input.select();
    document.execCommand('copy');
    tourPWA.showToast('링크가 복사되었습니다');
};

window.shareWithWebAPI = function() {
    tourPWA.share();
};

window.shareToKakao = function() {
    if (!window.Kakao || !Kakao.isInitialized()) {
        tourPWA.showToast('카카오톡 공유를 사용할 수 없습니다', 'error');
        return;
    }
    
    const shareUrl = document.getElementById('share-url').value;
    const placeNames = tourPWA.selectedPlaces.map(p => p.title).join(', ');
    
    Kakao.Share.sendDefault({
        objectType: 'feed',
        content: {
            title: '🗺️ 나의 성수동 투어',
            description: `${tourPWA.selectedPlaces.length}개 장소: ${placeNames}`,
            imageUrl: window.location.origin + '/tour-share.jpg',
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

window.saveTourWithName = function() {
    if (tourPWA.selectedPlaces.length === 0) {
        tourPWA.showToast('저장할 장소를 선택해주세요', 'error');
        return;
    }
    
    const tourName = prompt('투어 이름을 입력하세요:', `성수동 투어 ${new Date().toLocaleDateString()}`);
    if (!tourName) return;
    
    const savedTours = JSON.parse(localStorage.getItem('savedTours') || '[]');
    
    const newTour = {
        id: Date.now(),
        name: tourName,
        places: tourPWA.selectedPlaces,
        savedAt: new Date().toISOString()
    };
    
    savedTours.unshift(newTour);
    
    if (savedTours.length > 10) {
        savedTours.pop();
    }
    
    localStorage.setItem('savedTours', JSON.stringify(savedTours));
    
    tourPWA.showToast(`"${tourName}" 투어가 저장되었습니다`);
    closeModal('menu-modal');
};

window.showSavedTours = function() {
    // 저장된 투어 목록 표시 (추후 구현)
    tourPWA.showToast('저장된 투어 기능은 준비 중입니다', 'info');
};

window.resetTour = function() {
    if (tourPWA.selectedPlaces.length === 0) return;
    
    if (confirm('모든 선택을 초기화하시겠습니까?')) {
        tourPWA.selectedPlaces = [];
        tourPWA.updateUI();
        tourPWA.showToast('투어가 초기화되었습니다');
        closeModal('menu-modal');
    }
};

window.goToHome = function() {
    window.location.href = window.tourPWAConfig.homeUrl;
};

// 초기화
let tourPWA;
document.addEventListener('DOMContentLoaded', () => {
    tourPWA = new TourPlannerPWA();
    
    // Service Worker 등록
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('Service Worker 등록 성공'))
            .catch(err => console.log('Service Worker 등록 실패:', err));
    }
    
    // Kakao SDK 초기화
    const kakaoKey = document.querySelector('meta[name="kakao-key"]')?.content;
    if (window.Kakao && kakaoKey) {
        Kakao.init(kakaoKey);
    }
});
