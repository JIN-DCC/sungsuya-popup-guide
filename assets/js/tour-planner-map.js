/**
 * 투어플래너 지도 매니저
 * 네이버 지도 API 안정적 관리 클래스
 * 
 * @package SungsuyaV2
 * @version 1.0.0
 */

class TourPlannerMapManager {
    constructor() {
        this.map = null;
        this.markers = [];
        this.polylines = [];
        this.infoWindows = [];
        this.isInitialized = false;
        
        this.init();
    }
    
    async init() {
        console.log('🗺️ TourPlannerMapManager 초기화 시작');
        
        try {
            await this.waitForContainer();
            await this.loadNaverMapsAPI();
            await this.createMap();
            
            this.isInitialized = true;
            console.log('✅ 지도 매니저 초기화 완료');
            
        } catch (error) {
            console.error('❌ 지도 매니저 초기화 실패:', error);
            this.handleInitError(error);
        }
    }
    
    // 지도 컨테이너가 준비될 때까지 대기
    waitForContainer() {
        return new Promise((resolve, reject) => {
            const maxAttempts = 50; // 5초 대기
            let attempts = 0;
            
            const checkContainer = () => {
                const container = document.getElementById('map');
                
                if (container && container.offsetWidth > 0 && container.offsetHeight > 0) {
                    console.log('✅ 지도 컨테이너 준비 완료');
                    resolve(container);
                } else if (attempts < maxAttempts) {
                    attempts++;
                    setTimeout(checkContainer, 100);
                } else {
                    reject(new Error('지도 컨테이너를 찾을 수 없습니다'));
                }
            };
            
            checkContainer();
        });
    }
    
    // 네이버 지도 API 로드
    loadNaverMapsAPI() {
        return new Promise((resolve, reject) => {
            // 이미 로드되어 있는 경우
            if (typeof naver !== 'undefined' && naver.maps) {
                console.log('✅ 네이버 지도 API 이미 로드됨');
                resolve();
                return;
            }
            
            // 이미 로딩 중인 경우
            if (window.naverMapsLoading) {
                console.log('⏳ 네이버 지도 API 로딩 대기 중...');
                const checkLoaded = () => {
                    if (typeof naver !== 'undefined' && naver.maps) {
                        resolve();
                    } else if (window.naverMapsLoading) {
                        setTimeout(checkLoaded, 100);
                    } else {
                        reject(new Error('네이버 지도 API 로드 실패'));
                    }
                };
                checkLoaded();
                return;
            }
            
            // 새로 로드
            window.naverMapsLoading = true;
            console.log('📡 네이버 지도 API 스크립트 로드 시작...');
            
            const script = document.createElement('script');
            
            // API 키 확인
            const apiKey = window.SUNGSUYA_CONFIG?.naverClientId || 'your_naver_client_id';
            if (apiKey === 'your_naver_client_id') {
                console.warn('⚠️ 네이버 지도 API 키가 설정되지 않았습니다');
            }
            
            script.src = `https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId=${apiKey}`;
            
            script.onload = () => {
                console.log('✅ 네이버 지도 API 스크립트 로드 완료');
                window.naverMapsLoading = false;
                
                // API 인증 확인을 위한 짧은 지연
                setTimeout(() => {
                    if (typeof naver !== 'undefined' && naver.maps) {
                        resolve();
                    } else {
                        reject(new Error('네이버 지도 API 객체를 찾을 수 없습니다'));
                    }
                }, 100);
            };
            
            script.onerror = () => {
                console.error('❌ 네이버 지도 API 스크립트 로드 실패');
                window.naverMapsLoading = false;
                reject(new Error('네이버 지도 스크립트 로드 실패'));
            };
            
            document.head.appendChild(script);
        });
    }
    
    // 지도 생성
    createMap() {
        return new Promise((resolve, reject) => {
            try {
                const container = document.getElementById('map');
                if (!container) {
                    throw new Error('지도 컨테이너를 찾을 수 없습니다');
                }
                
                // 기존 로딩 화면 제거
                const loading = container.querySelector('.map-loading, .flex');
                if (loading) {
                    loading.style.display = 'none';
                }
                
                // 지도 옵션
                const mapOptions = {
                    center: new naver.maps.LatLng(37.5444, 127.0548), // 성수역
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
                    keyboardShortcuts: false // PWA에서 키보드 충돌 방지
                };
                
                // 지도 생성
                this.map = new naver.maps.Map(container, mapOptions);
                
                // 전역 참조 설정 (호환성)
                window.tourPlannerMap = this.map;
                window.tourPlannerMarkers = this.markers;
                window.tourPlannerPolylines = this.polylines;
                
                console.log('✅ 네이버 지도 생성 완료');
                
                // 지도 이벤트 리스너
                naver.maps.Event.addListener(this.map, 'idle', () => {
                    console.log('🗺️ 지도 렌더링 완료');
                    resolve();
                });
                
                // 지도 로드 실패 타임아웃
                setTimeout(() => {
                    if (!this.isInitialized) {
                        resolve(); // 타임아웃이어도 일단 성공으로 처리
                    }
                }, 3000);
                
            } catch (error) {
                console.error('지도 생성 실패:', error);
                reject(error);
            }
        });
    }
    
    // 에러 처리
    handleInitError(error) {
        console.error('지도 초기화 에러:', error);
        
        const container = document.getElementById('map');
        if (!container) return;
        
        // 에러 유형 판단
        let errorType = 'general';
        let errorMessage = error.message || '지도를 불러올 수 없습니다';
        
        if (errorMessage.includes('Authentication Failed') || errorMessage.includes('API key')) {
            errorType = 'auth';
            errorMessage = '네이버 지도 API 인증 실패';
        } else if (errorMessage.includes('network') || errorMessage.includes('fetch')) {
            errorType = 'network';
            errorMessage = '네트워크 연결을 확인해주세요';
        }
        
        this.showMapError(container, errorType, errorMessage);
    }
    
    // 지도 에러 UI 표시
    showMapError(container, errorType = 'general', errorMessage = '') {
        let errorContent = '';
        
        if (errorType === 'auth') {
            errorContent = `
                <div class="map-error-container">
                    <div class="map-error-content">
                        <div class="error-icon">🔐</div>
                        <h3 class="error-title">지도 API 인증 오류</h3>
                        <p class="error-message">네이버 지도 API 인증에 실패했습니다.</p>
                        <p class="error-message" style="font-size: 0.8rem; color: #9ca3af;">
                            관리자: 네이버 클라우드 플랫폼에서 도메인 등록을 확인해주세요.
                        </p>
                        <div class="error-actions">
                            <button class="btn-retry" onclick="window.location.reload()">페이지 새로고침</button>
                        </div>
                        <div class="error-alternative">
                            <p><strong>💡 투어플래너는 지도 없이도 사용 가능합니다!</strong></p>
                            <p>사이드바에서 장소를 선택하고 경로를 최적화할 수 있습니다.</p>
                        </div>
                    </div>
                </div>
            `;
        } else {
            errorContent = `
                <div class="map-error-container">
                    <div class="map-error-content">
                        <div class="error-icon">🗺️</div>
                        <h3 class="error-title">지도를 불러올 수 없습니다</h3>
                        <p class="error-message">${errorMessage}</p>
                        <div class="error-actions">
                            <button class="btn-retry" onclick="tourPlannerMapManager.retryInit()">다시 시도</button>
                        </div>
                        <div class="error-alternative">
                            <p><strong>💡 투어플래너는 지도 없이도 사용 가능합니다!</strong></p>
                            <p>사이드바에서 장소를 선택하고 경로를 최적화할 수 있습니다.</p>
                        </div>
                    </div>
                </div>
            `;
        }
        
        container.innerHTML = errorContent;
    }
    
    // 재시도
    async retryInit() {
        console.log('🔄 지도 초기화 재시도...');
        
        const container = document.getElementById('map');
        if (container) {
            container.innerHTML = `
                <div class="map-loading">
                    <div class="loading-spinner"></div>
                    <p>지도를 다시 불러오는 중...</p>
                </div>
            `;
        }
        
        // 상태 초기화
        this.isInitialized = false;
        this.map = null;
        this.clearMarkers();
        this.clearPolylines();
        
        // 재초기화
        await this.init();
    }
    
    // 마커 업데이트
    updateMarkers(places) {
        if (!this.isInitialized || !this.map) {
            console.log('⏳ 지도가 준비되지 않아 마커 업데이트를 연기합니다');
            return;
        }
        
        console.log(`🔄 마커 업데이트: ${places.length}개 장소`);
        
        // 기존 마커 제거
        this.clearMarkers();
        this.clearPolylines();
        
        if (!places || places.length === 0) {
            console.log('📍 표시할 장소가 없습니다');
            return;
        }
        
        const bounds = new naver.maps.LatLngBounds();
        const validPlaces = [];
        
        // 마커 생성
        places.forEach((place, index) => {
            if (this.hasValidCoordinates(place)) {
                const position = new naver.maps.LatLng(
                    parseFloat(place.location.lat),
                    parseFloat(place.location.lng)
                );
                
                const marker = this.createMarker(place, position, index + 1);
                this.markers.push(marker);
                bounds.extend(position);
                validPlaces.push({ place, position, index });
            } else {
                console.warn('⚠️ 좌표가 없는 장소:', place.title);
            }
        });
        
        // 지도 범위 조정
        if (validPlaces.length > 1) {
            this.map.fitBounds(bounds, { 
                padding: { top: 50, right: 50, bottom: 50, left: 50 } 
            });
        } else if (validPlaces.length === 1) {
            this.map.setCenter(validPlaces[0].position);
            this.map.setZoom(16);
        }
        
        // 경로선 그리기
        if (validPlaces.length > 1) {
            this.drawRoutePath(validPlaces);
        }
        
        console.log(`✅ ${validPlaces.length}개 마커 업데이트 완료`);
    }
    
    // 좌표 유효성 검사
    hasValidCoordinates(place) {
        return place.location && 
               place.location.lat && 
               place.location.lng && 
               !isNaN(parseFloat(place.location.lat)) && 
               !isNaN(parseFloat(place.location.lng));
    }
    
    // 마커 생성
    createMarker(place, position, number) {
        const marker = new naver.maps.Marker({
            position: position,
            map: this.map,
            title: place.title,
            icon: {
                content: this.createMarkerContent(number, place.place_type),
                size: new naver.maps.Size(40, 50),
                anchor: new naver.maps.Point(20, 50)
            }
        });
        
        // 정보창 생성
        const infoWindow = new naver.maps.InfoWindow({
            content: this.createInfoWindowContent(place),
            borderWidth: 0,
            backgroundColor: 'transparent'
        });
        
        // 마커 클릭 이벤트
        naver.maps.Event.addListener(marker, 'click', () => {
            this.closeAllInfoWindows();
            
            if (infoWindow.getMap()) {
                infoWindow.close();
            } else {
                infoWindow.open(this.map, marker);
                this.infoWindows.push(infoWindow);
            }
        });
        
        return marker;
    }
    
    // 마커 콘텐츠 생성
    createMarkerContent(number, placeType) {
        const typeIcon = this.getPlaceTypeIcon(placeType);
        
        return `
            <div class="custom-map-marker">
                <div class="marker-pin">
                    <div class="marker-number">${number}</div>
                </div>
                <div class="marker-type-icon">${typeIcon}</div>
            </div>
        `;
    }
    
    // 정보창 콘텐츠 생성
    createInfoWindowContent(place) {
        const typeIcon = this.getPlaceTypeIcon(place.place_type);
        const typeLabel = this.getPlaceTypeLabel(place.place_type);
        
        return `
            <div class="map-info-window">
                <div class="info-header">
                    <span class="info-type">${typeIcon}</span>
                    <h4 class="info-title">${place.title}</h4>
                </div>
                <div class="info-content">
                    <p class="info-category">${typeLabel}</p>
                    ${place.location?.address ? `<p class="info-address">📍 ${place.location.address}</p>` : ''}
                    ${place.opening_hours ? `<p class="info-hours">🕒 ${place.opening_hours}</p>` : ''}
                </div>
                <div class="info-actions">
                    <button class="info-btn" onclick="tourPlanner.togglePlace('${place.id}')">투어에서 제거</button>
                </div>
            </div>
        `;
    }
    
    // 경로선 그리기
    drawRoutePath(validPlaces) {
        if (validPlaces.length < 2) return;
        
        const path = validPlaces.map(item => item.position);
        
        const polyline = new naver.maps.Polyline({
            path: path,
            strokeColor: '#3b82f6',
            strokeWeight: 4,
            strokeOpacity: 0.8,
            strokeLineCap: 'round',
            strokeLineJoin: 'round',
            map: this.map
        });
        
        this.polylines.push(polyline);
        
        console.log('🛣️ 경로선 그리기 완료');
    }
    
    // 모든 마커 제거
    clearMarkers() {
        this.markers.forEach(marker => {
            marker.setMap(null);
        });
        this.markers = [];
        this.closeAllInfoWindows();
    }
    
    // 모든 경로선 제거
    clearPolylines() {
        this.polylines.forEach(polyline => {
            polyline.setMap(null);
        });
        this.polylines = [];
    }
    
    // 모든 정보창 닫기
    closeAllInfoWindows() {
        this.infoWindows.forEach(infoWindow => {
            infoWindow.close();
        });
        this.infoWindows = [];
    }
    
    // 장소 유형 아이콘
    getPlaceTypeIcon(placeType) {
        const icons = {
            'popup_store': '🏪',
            'restaurant': '🍽️',
            'retail_store': '🏬',
            'facility': '🚻'
        };
        return icons[placeType] || '📍';
    }
    
    // 장소 유형 라벨
    getPlaceTypeLabel(placeType) {
        const labels = {
            'popup_store': '팝업스토어',
            'restaurant': '맛집',
            'retail_store': '상설매장',
            'facility': '편의시설'
        };
        return labels[placeType] || '장소';
    }
    
    // 지도 인스턴스 반환
    getMap() {
        return this.map;
    }
    
    // 초기화 상태 확인
    isReady() {
        return this.isInitialized && this.map !== null;
    }
}

// 전역 인스턴스 생성
let tourPlannerMapManager = null;

// DOM 로드 완료 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 지도 컨테이너가 있는 경우에만 초기화
    const mapContainer = document.getElementById('map');
    if (mapContainer) {
        console.log('🗺️ 투어플래너 지도 매니저 초기화 준비');
        
        // 잠시 후 초기화 (다른 스크립트 로드 대기)
        setTimeout(() => {
            tourPlannerMapManager = new TourPlannerMapManager();
            
            // 전역 접근 설정
            window.tourPlannerMapManager = tourPlannerMapManager;
            window.updateMapMarkers = function(places) {
                if (tourPlannerMapManager && tourPlannerMapManager.isReady()) {
                    tourPlannerMapManager.updateMarkers(places);
                }
            };
        }, 500);
    }
});

// 🔥 HybridTourPlanner와의 연동을 위한 전역 함수
window.updateMapMarkers = function(places) {
    if (tourPlannerMapManager && tourPlannerMapManager.isReady()) {
        tourPlannerMapManager.updateMarkers(places);
    } else {
        console.log('⏳ 지도 매니저가 준비되지 않음');
    }
};

// 스타일 자동 추가 (CSS가 로드되지 않은 경우 대비)
if (!document.getElementById('tour-planner-map-styles')) {
    const style = document.createElement('style');
    style.id = 'tour-planner-map-styles';
    style.textContent = `
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    `;
    document.head.appendChild(style);
}
