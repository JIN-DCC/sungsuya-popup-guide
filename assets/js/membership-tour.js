/**
 * 멤버십 기반 하이브리드 투어 시스템 - Frontend
 * 
 * 비회원: 스마트 투어 카드 (API 사용량 0)
 * 회원: 조건부 GPS 네비게이션 (월 10회 제한)
 * 
 * @version 1.0.0
 */

class SungsuyaMembershipTour {
    constructor() {
        this.isLoggedIn = SungsuyaTour.isLoggedIn;
        this.userId = SungsuyaTour.userId;
        this.apiLimit = SungsuyaTour.apiLimit;
        this.currentUsage = SungsuyaTour.currentUsage;
        this.selectedPlaces = JSON.parse(localStorage.getItem('sungsuya_tour') || '[]');
        
        this.init();
    }
    
    init() {
        this.renderMembershipStatus();
        this.renderTourInterface();
        this.bindEvents();
        
        console.log('🎯 멤버십 투어 시스템 초기화 완료');
        console.log(`사용자 상태: ${this.isLoggedIn ? '회원' : '비회원'}`);
        console.log(`API 사용량: ${this.currentUsage}/${this.apiLimit}`);
    }
    
    /**
     * 멤버십 상태 UI 렌더링
     */
    renderMembershipStatus() {
        const statusContainer = document.getElementById('membership-status');
        if (!statusContainer) return;
        
        if (this.isLoggedIn) {
            statusContainer.innerHTML = this.renderMemberStatus();
        } else {
            statusContainer.innerHTML = this.renderGuestStatus();
        }
    }
    
    renderMemberStatus() {
        const canUseGPS = this.currentUsage < this.apiLimit;
        const progressPercent = (this.currentUsage / this.apiLimit) * 100;
        
        return `
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">⭐</span>
                        <div>
                            <h3 class="font-bold text-gray-800">프리미엄 멤버</h3>
                            <p class="text-sm text-gray-600">GPS 네비게이션 이용 가능</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600">이번 달 사용량</div>
                        <div class="font-bold text-lg ${canUseGPS ? 'text-blue-600' : 'text-red-600'}">
                            ${this.currentUsage}/${this.apiLimit}회
                        </div>
                    </div>
                </div>
                
                <!-- API 사용량 프로그레스 바 -->
                <div class="mb-4">
                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                        <span>GPS 네비게이션 사용량</span>
                        <span>${progressPercent.toFixed(0)}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                             style="width: ${Math.min(progressPercent, 100)}%"></div>
                    </div>
                </div>
                
                <!-- 프리미엄 기능 리스트 -->
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="flex items-center text-green-600">
                        <span class="mr-2">✅</span>
                        스마트 투어 카드
                    </div>
                    <div class="flex items-center ${canUseGPS ? 'text-green-600' : 'text-gray-400'}">
                        <span class="mr-2">${canUseGPS ? '✅' : '⛔'}</span>
                        GPS 네비게이션
                    </div>
                    <div class="flex items-center text-green-600">
                        <span class="mr-2">✅</span>
                        투어 기록 저장
                    </div>
                    <div class="flex items-center text-green-600">
                        <span class="mr-2">✅</span>
                        즐겨찾기 관리
                    </div>
                </div>
                
                ${!canUseGPS ? `
                    <div class="mt-4 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                        <div class="text-sm text-orange-700">
                            📊 이번 달 GPS 네비게이션 한도를 모두 사용했습니다.<br>
                            다음 달에 다시 이용할 수 있습니다.
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    renderGuestStatus() {
        return `
            <div class="bg-gradient-to-r from-gray-50 to-blue-50 rounded-xl p-6 border border-gray-200">
                <div class="text-center mb-4">
                    <div class="text-4xl mb-2">🗺️</div>
                    <h3 class="font-bold text-gray-800 mb-2">기본 투어 모드</h3>
                    <p class="text-sm text-gray-600">스마트 투어 카드로 성수동을 탐험하세요</p>
                </div>
                
                <!-- 기본 기능 -->
                <div class="grid grid-cols-2 gap-2 text-sm mb-4">
                    <div class="flex items-center text-green-600">
                        <span class="mr-2">✅</span>
                        스마트 투어 카드
                    </div>
                    <div class="flex items-center text-green-600">
                        <span class="mr-2">✅</span>
                        텍스트 방향안내
                    </div>
                    <div class="flex items-center text-green-600">
                        <span class="mr-2">✅</span>
                        도보시간 계산
                    </div>
                    <div class="flex items-center text-green-600">
                        <span class="mr-2">✅</span>
                        장소 정보 열람
                    </div>
                </div>
                
                <!-- 프리미엄 업그레이드 안내 -->
                <div class="bg-white rounded-lg p-4 border border-blue-200">
                    <div class="text-center">
                        <div class="text-sm text-gray-600 mb-3">
                            더 정확한 GPS 네비게이션이 필요하신가요?
                        </div>
                        <div class="space-y-2">
                            <button onclick="sungsuyaTour.showLoginModal()" 
                                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                                ⭐ 무료 회원가입하기
                            </button>
                            <button onclick="sungsuyaTour.showLoginModal()" 
                                    class="w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-lg text-sm hover:bg-gray-200 transition-colors">
                                이미 회원이신가요? 로그인
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * 투어 인터페이스 렌더링
     */
    renderTourInterface() {
        const tourContainer = document.getElementById('tour-interface');
        if (!tourContainer) return;
        
        if (this.selectedPlaces.length === 0) {
            tourContainer.innerHTML = this.renderEmptyTourMessage();
        } else {
            this.loadTourData();
        }
    }
    
    renderEmptyTourMessage() {
        return `
            <div class="text-center py-12">
                <div class="text-6xl mb-4">🗺️</div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">투어 계획을 시작해보세요</h3>
                <p class="text-gray-600 mb-6">성수동의 멋진 장소들을 선택하여 나만의 투어를 만들어보세요</p>
                <a href="/places" class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                    <span class="mr-2">📍</span>
                    장소 둘러보기
                </a>
            </div>
        `;
    }
    
    /**
     * 투어 데이터 로드
     */
    async loadTourData() {
        const tourContainer = document.getElementById('tour-interface');
        
        // 로딩 상태 표시
        tourContainer.innerHTML = `
            <div class="flex items-center justify-center py-12">
                <div class="text-center">
                    <div class="text-4xl mb-4 animate-pulse">🗺️</div>
                    <div class="text-lg font-medium text-gray-700">투어 경로 생성중...</div>
                    <div class="text-sm text-gray-500 mt-2">
                        ${this.isLoggedIn ? 'GPS 네비게이션 데이터를 준비하고 있습니다' : '스마트 투어 카드를 생성하고 있습니다'}
                    </div>
                </div>
            </div>
        `;
        
        try {
            const response = await fetch(SungsuyaTour.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_tour_map_data',
                    nonce: SungsuyaTour.nonce,
                    places: JSON.stringify(this.selectedPlaces.map(p => p.id))
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.renderTourData(result.data);
            } else {
                throw new Error(result.data || '투어 데이터 로드 실패');
            }
        } catch (error) {
            console.error('투어 데이터 로드 오류:', error);
            tourContainer.innerHTML = this.renderErrorMessage(error.message);
        }
    }
    
    /**
     * 투어 데이터 렌더링
     */
    renderTourData(tourData) {
        const tourContainer = document.getElementById('tour-interface');
        
        let html = `
            <div class="space-y-6">
                <!-- 투어 요약 -->
                <div class="bg-white rounded-xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-800">나의 성수동 투어</h2>
                        <div class="flex items-center space-x-4 text-sm text-gray-600">
                            <div class="flex items-center">
                                <span class="mr-1">⏱️</span>
                                ${tourData.total_time}
                            </div>
                            <div class="flex items-center">
                                <span class="mr-1">📏</span>
                                ${tourData.total_distance}
                            </div>
                            <div class="flex items-center">
                                <span class="mr-1">📍</span>
                                ${tourData.places.length}개 장소
                            </div>
                        </div>
                    </div>
                    
                    ${tourData.api_limit_exceeded ? `
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-4">
                            <div class="text-sm text-orange-700">
                                📊 이번 달 GPS 네비게이션 한도 초과로 기본 모드로 제공됩니다.
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="text-sm text-gray-500">
                        ${tourData.type === 'static' ? 
                            '🎯 스마트 투어 카드 모드 (API 사용량 0회)' : 
                            '⭐ GPS 네비게이션 모드 (API 사용량 1회)'
                        }
                    </div>
                </div>
                
                <!-- 투어 장소 카드들 -->
                <div class="space-y-4">
                    ${tourData.places.map(place => this.renderPlaceCard(place, tourData.type)).join('')}
                </div>
                
                <!-- 투어 완료 액션 -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                    <div class="text-center">
                        <div class="text-3xl mb-3">🎉</div>
                        <h3 class="font-bold text-gray-800 mb-2">투어 준비 완료!</h3>
                        <p class="text-sm text-gray-600 mb-4">위의 순서대로 방문하여 성수동의 매력을 만끽하세요</p>
                        
                        ${tourData.type === 'dynamic' ? `
                            <button onclick="sungsuyaTour.startGPSNavigation()" 
                                    class="bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 transition-colors mr-3">
                                🧭 GPS 네비게이션 시작
                            </button>
                        ` : ''}
                        
                        <button onclick="sungsuyaTour.shareTour()" 
                                class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                            📤 투어 공유하기
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        tourContainer.innerHTML = html;
    }
    
    /**
     * 장소 카드 렌더링
     */
    renderPlaceCard(place, tourType) {
        return `
            <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                <div class="flex items-start space-x-4">
                    <!-- 순서 번호 -->
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-lg">
                            ${place.order}
                        </div>
                    </div>
                    
                    <!-- 미니 지도 -->
                    <div class="flex-shrink-0">
                        <div class="w-24 h-16 rounded-lg overflow-hidden bg-gray-100">
                            ${place.static_map_url ? 
                                `<img src="${place.static_map_url}" alt="${place.title} 지도" class="w-full h-full object-cover">` :
                                `<div class="w-full h-full flex items-center justify-center text-gray-400 text-xs">지도</div>`
                            }
                        </div>
                    </div>
                    
                    <!-- 장소 정보 -->
                    <div class="flex-grow min-w-0">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-bold text-gray-800 truncate">${place.title}</h3>
                            <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                                ${this.getPlaceTypeLabel(place.type)}
                            </span>
                        </div>
                        
                        <div class="text-sm text-gray-600 mb-3">
                            📍 ${place.address}
                        </div>
                        
                        <!-- 이동 정보 -->
                        ${place.order > 1 ? `
                            <div class="bg-gray-50 rounded-lg p-3 mb-3">
                                <div class="text-sm text-gray-700">
                                    <div class="flex items-center mb-1">
                                        <span class="mr-2">🚶‍♂️</span>
                                        <strong>${place.walking_time}</strong>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ${place.directions_text}
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                        
                        <!-- 장소 특징 -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-xs text-gray-600">
                            ${place.features.subway_info ? `
                                <div class="flex items-center">
                                    <span class="mr-1">🚇</span>
                                    ${place.features.subway_info}
                                </div>
                            ` : ''}
                            
                            ${place.features.opening_hours ? `
                                <div class="flex items-center">
                                    <span class="mr-1">🕒</span>
                                    ${place.features.opening_hours}
                                </div>
                            ` : ''}
                            
                            ${place.features.phone ? `
                                <div class="flex items-center">
                                    <span class="mr-1">📞</span>
                                    ${place.features.phone}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- 액션 버튼 -->
                    <div class="flex-shrink-0 flex flex-col space-y-2">
                        <a href="/places/${place.id}" target="_blank"
                           class="text-xs bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-full text-gray-700 transition-colors">
                            상세보기
                        </a>
                        
                        ${tourType === 'dynamic' && place.navigation_url ? `
                            <button onclick="window.open('${place.navigation_url}', '_blank')"
                                    class="text-xs bg-blue-100 hover:bg-blue-200 px-3 py-1 rounded-full text-blue-700 transition-colors">
                                길찾기
                            </button>
                        ` : `
                            <button onclick="sungsuyaTour.openExternalMap('${place.title}', '${place.address}')"
                                    class="text-xs bg-green-100 hover:bg-green-200 px-3 py-1 rounded-full text-green-700 transition-colors">
                                지도앱
                            </button>
                        `}
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * 장소 타입 라벨 반환
     */
    getPlaceTypeLabel(type) {
        const labels = {
            'restaurant': '맛집',
            'cafe': '카페',
            'popup_store': '팝업스토어',
            'retail_store': '매장',
            'facility': '편의시설'
        };
        
        return labels[type] || '장소';
    }
    
    /**
     * 이벤트 바인딩
     */
    bindEvents() {
        // localStorage 변경 감지
        window.addEventListener('storage', (e) => {
            if (e.key === 'sungsuya_tour') {
                this.selectedPlaces = JSON.parse(e.newValue || '[]');
                this.renderTourInterface();
            }
        });
        
        // 페이지 가시성 변경시 데이터 새로고침
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.refreshMembershipStatus();
            }
        });
    }
    
    /**
     * 멤버십 상태 새로고침
     */
    async refreshMembershipStatus() {
        if (!this.isLoggedIn) return;
        
        try {
            const response = await fetch(SungsuyaTour.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'check_membership_status',
                    nonce: SungsuyaTour.nonce
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.currentUsage = result.data.current_usage;
                this.renderMembershipStatus();
            }
        } catch (error) {
            console.error('멤버십 상태 새로고침 실패:', error);
        }
    }
    
    /**
     * 로그인 모달 표시
     */
    showLoginModal() {
        // 간단한 리다이렉트 방식 사용
        const loginUrl = new URL('/wp-login.php', window.location.origin);
        loginUrl.searchParams.set('redirect_to', 'planner');
        loginUrl.searchParams.set('action', 'register');
        
        window.location.href = loginUrl.toString();
    }
    
    /**
     * GPS 네비게이션 시작
     */
    startGPSNavigation() {
        this.showNotification('🧭 GPS 네비게이션을 시작합니다!', 'success');
        
        // 첫 번째 장소로 네비게이션
        if (this.selectedPlaces.length > 0) {
            const firstPlace = this.selectedPlaces[0];
            const navUrl = `https://map.naver.com/v5/directions/-/-/${firstPlace.lat},${firstPlace.lng}`;
            window.open(navUrl, '_blank');
        }
    }
    
    /**
     * 투어 공유
     */
    shareTour() {
        const tourUrl = window.location.href;
        
        if (navigator.share) {
            navigator.share({
                title: '나의 성수동 투어',
                text: `${this.selectedPlaces.length}개 장소로 구성된 성수동 투어를 확인해보세요!`,
                url: tourUrl
            });
        } else {
            navigator.clipboard.writeText(tourUrl).then(() => {
                this.showNotification('🔗 투어 링크가 클립보드에 복사되었습니다!', 'success');
            });
        }
    }
    
    /**
     * 외부 지도 앱 열기
     */
    openExternalMap(placeName, address) {
        const searchQuery = encodeURIComponent(`${placeName} ${address}`);
        const naverUrl = `https://map.naver.com/v5/search/${searchQuery}`;
        window.open(naverUrl, '_blank');
    }
    
    /**
     * 에러 메시지 렌더링
     */
    renderErrorMessage(message) {
        return `
            <div class="text-center py-12">
                <div class="text-4xl mb-4">❌</div>
                <h3 class="text-lg font-bold text-gray-800 mb-2">투어 데이터 로드 실패</h3>
                <p class="text-gray-600 mb-4">${message}</p>
                <button onclick="sungsuyaTour.renderTourInterface()" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    다시 시도
                </button>
            </div>
        `;
    }
    
    /**
     * 알림 표시
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg text-white transform translate-x-full transition-transform duration-300 ${
            type === 'success' ? 'bg-green-500' : 
            type === 'error' ? 'bg-red-500' : 
            type === 'info' ? 'bg-blue-500' : 'bg-gray-500'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
}

// 전역 변수로 노출
let sungsuyaTour;

// DOM 로드 완료시 초기화
document.addEventListener('DOMContentLoaded', function() {
    if (typeof SungsuyaTour !== 'undefined') {
        sungsuyaTour = new SungsuyaMembershipTour();
    }
});
