<?php
/**
 * Template Name: 통합 투어 플래너
 * 
 * 기존 테마 헤더/네비게이션과 통합된 투어플래너
 */

get_header(); ?>

<script>
// 🔥 네비게이션 활성 상태 설정
document.addEventListener('DOMContentLoaded', function() {
    // 모든 네비게이션 링크의 active 클래스 제거
    document.querySelectorAll('.nav-link, .mobile-nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // 투어플래너 네비게이션에 active 클래스 추가
    document.querySelectorAll('a[href*="/planner"]').forEach(link => {
        link.classList.add('active');
    });
});
</script>

<style>
/* 🔥 투어플래너 전용 스타일 - 기존 테마와 조화 */
.tour-planner-page {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: calc(100vh - 150px); /* 헤더 공간 확보 */
    padding: 2rem 0;
    margin-top: 0; /* 헤더와 겹치지 않게 */
    position: relative;
    z-index: 1;
}

.tour-planner-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem;
}

.planner-intro {
    text-align: center;
    color: white;
    margin-bottom: 2rem;
}

.planner-intro h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.planner-intro p {
    font-size: 1.2rem;
    opacity: 0.95;
    margin-bottom: 1.5rem;
}

.type-legend {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}

.type-item {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    padding: 0.5rem 1rem;
    border-radius: 1rem;
    color: white;
    font-weight: 600;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

/* 메인 플래너 레이아웃 */
.planner-main {
    background: white;
    border-radius: 1.5rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: grid;
    grid-template-columns: 1fr 400px;
    min-height: 600px;
    max-height: 80vh; /* 화면을 다 차지하지 않게 제한 */
    position: relative;
    z-index: 2;
}

.planner-map-section {
    position: relative;
    background: #f8fafc;
}

.planner-sidebar {
    background: white;
    border-left: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    max-height: 600px;
    overflow: hidden;
}

/* 지도 스타일 */
.planner-map {
    width: 100%;
    height: 100%;
    position: relative;
}

#map {
    width: 100% !important;
    height: 100% !important;
    border-radius: 0;
}

.map-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #6b7280;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e5e7eb;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* 사이드바 스타일 */
.sidebar-section {
    border-bottom: 1px solid #e5e7eb;
    padding: 1.5rem;
}

.sidebar-section:last-child {
    border-bottom: none;
    flex: 1;
    overflow-y: auto;
}

.section-title {
    margin: 0 0 1rem 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
}

/* 투어 목적 선택 */
.purpose-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.purpose-btn {
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.9rem;
    text-align: center;
}

.purpose-btn:hover {
    background: #f1f5f9;
}

.purpose-btn.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.purpose-btn[data-purpose="detailed"].active {
    background: #10b981;
    border-color: #10b981;
}

.purpose-btn[data-purpose="custom"].active {
    background: #6b7280;
    border-color: #6b7280;
}

/* 빠른 선택 버튼 */
.quick-add-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}

.quick-add-btn {
    padding: 0.75rem 0.5rem;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    font-size: 0.85rem;
    line-height: 1.3;
}

.quick-add-btn:hover {
    background: #f1f5f9;
    border-color: #3b82f6;
}

/* 선택된 장소 목록 */
.selected-places-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.places-count {
    background: #3b82f6;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.selected-places-list {
    max-height: 300px;
    overflow-y: auto;
}

.selected-place {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
    border: 1px solid #e5e7eb;
}

.place-number {
    width: 24px;
    height: 24px;
    background: #3b82f6;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
    flex-shrink: 0;
}

.place-info {
    flex: 1;
}

.place-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.place-type {
    font-size: 0.8rem;
    color: #6b7280;
}

.remove-place {
    width: 24px;
    height: 24px;
    border: none;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    cursor: pointer;
    font-size: 0.8rem;
    flex-shrink: 0;
    transition: background 0.2s;
}

.remove-place:hover {
    background: #dc2626;
}

/* 빈 상태 */
.empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

/* 액션 버튼들 */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.btn {
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
    flex: 1;
    text-align: center;
    font-size: 0.9rem;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-primary:disabled {
    background: #d1d5db;
    cursor: not-allowed;
}

.btn-secondary {
    background: #10b981;
    color: white;
}

.btn-secondary:hover {
    background: #059669;
}

/* 투어 미리보기 */
.tour-preview {
    background: #f0f9ff;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    border: 1px solid #0ea5e9;
}

.preview-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #0c4a6e;
    margin-bottom: 0.5rem;
}

.preview-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
    font-size: 0.85rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
}

/* 반응형 디자인 */
@media (max-width: 1024px) {
    .planner-main {
        grid-template-columns: 1fr;
        grid-template-rows: 400px 1fr;
    }
    
    .planner-sidebar {
        border-left: none;
        border-top: 1px solid #e5e7eb;
        max-height: none;
    }
}

@media (max-width: 768px) {
    .tour-planner-container {
        padding: 0 0.5rem;
    }
    
    .planner-intro h1 {
        font-size: 2rem;
    }
    
    .planner-intro p {
        font-size: 1rem;
    }
    
    .type-legend {
        gap: 0.5rem;
    }
    
    .type-item {
        font-size: 0.85rem;
        padding: 0.5rem 0.75rem;
    }
    
    .planner-main {
        grid-template-rows: 300px 1fr;
        border-radius: 1rem;
    }
    
    .sidebar-section {
        padding: 1rem;
    }
    
    .quick-add-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}

/* 지도 에러 스타일 */
.map-error-container {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    width: 100%;
    background: #f9fafb;
}

.map-error-content {
    text-align: center;
    padding: 2rem;
    max-width: 300px;
}

.error-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.error-title {
    margin: 0 0 0.5rem 0;
    color: #374151;
    font-size: 1.1rem;
    font-weight: 600;
}

.error-message {
    margin: 0 0 1rem 0;
    font-size: 0.9rem;
    color: #6b7280;
    line-height: 1.4;
}

.btn-retry {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-retry:hover {
    background: #2563eb;
}
</style>

<div class="tour-planner-page">
    <div class="tour-planner-container">
        <!-- 소개 섹션 -->
        <div class="planner-intro">
            <h1>🗺️ 성수동 투어 플래너</h1>
            <p>4가지 장소를 자유롭게 조합해서 나만의 투어를 만들어보세요!</p>
            
            <div class="type-legend">
                <span class="type-item">🏪 팝업스토어</span>
                <span class="type-item">🍽️ 맛집</span>
                <span class="type-item">🏬 상설매장</span>
                <span class="type-item">🚻 편의시설</span>
            </div>
        </div>

        <!-- 메인 플래너 -->
        <div class="planner-main">
            <!-- 지도 섹션 - API 최적화 완료 -->
            <div class="planner-map-section">
                <div class="planner-map">
                    <div id="map" style="width: 100%; height: 100%;">
                        <!-- 지도 플레이스홀더 (조건부 로딩) -->
                        <div id="map-placeholder" class="map-loading">
                            <div class="map-loading-content" style="text-align: center; padding: 2rem;">
                                <div style="font-size: 3rem; margin-bottom: 1rem;">🗺️</div>
                                <h3 style="margin-bottom: 1rem; color: #374151;">성수동 투어 지도</h3>
                                <p style="color: #6b7280; margin-bottom: 2rem;">선택한 장소들이 지도에 표시됩니다</p>
                                
                                <!-- 지도 활성화 버튼 -->
                                <button id="activate-map-btn" 
                                        class="btn btn-primary" 
                                        onclick="tourPlanner.activateInteractiveMap()"
                                        style="background: #3b82f6; color: white; border: none; padding: 1rem 2rem; border-radius: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                                    🗺️ 지도 활성화하기
                                </button>
                                
                                <div style="margin-top: 1.5rem; padding: 1rem; background: #f0f9ff; border-radius: 0.75rem; border: 1px solid #0ea5e9;">
                                    <p style="font-size: 0.9rem; color: #0c4a6e; margin: 0;">
                                        <strong>💡 API 최적화:</strong> 필요시에만 지도를 로드하여 빠른 성능을 제공합니다
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 실제 지도 컨테이너 (숨겨짐) -->
                        <div id="interactive-map" style="display: none; width: 100%; height: 100%;"></div>
                    </div>
                </div>
            </div>

            <!-- 사이드바 -->
            <div class="planner-sidebar">
                <!-- 투어 목적 선택 -->
                <div class="sidebar-section">
                    <h3 class="section-title">투어 스타일 선택</h3>
                    <div class="purpose-buttons">
                        <button class="purpose-btn" data-purpose="quick">
                            ⚡ 빠른 투어 (90분)
                        </button>
                        <button class="purpose-btn" data-purpose="detailed">
                            🕒 여유 투어 (3시간)
                        </button>
                        <button class="purpose-btn" data-purpose="custom">
                            🎯 직접 선택 (자유)
                        </button>
                    </div>
                </div>

                <!-- 빠른 장소 추가 -->
                <div class="sidebar-section">
                    <h3 class="section-title">빠른 장소 추가</h3>
                    <div class="quick-add-grid">
                        <button class="quick-add-btn" onclick="goToPlaces('popup_store')">
                            🏪<br>팝업스토어
                        </button>
                        <button class="quick-add-btn" onclick="goToPlaces('restaurant')">
                            🍽️<br>맛집
                        </button>
                        <button class="quick-add-btn" onclick="goToPlaces('retail_store')">
                            🏬<br>상설매장
                        </button>
                        <button class="quick-add-btn" onclick="goToPlaces('facility')">
                            🚻<br>편의시설
                        </button>
                    </div>
                </div>

                <!-- 선택된 장소 목록 -->
                <div class="sidebar-section">
                    <div class="selected-places-header">
                        <h3 class="section-title">선택된 장소</h3>
                        <span class="places-count" id="places-count">0곳</span>
                    </div>
                    
                    <!-- 투어 미리보기 -->
                    <div class="tour-preview" id="tour-preview" style="display: none;">
                        <h4 class="preview-title">🔍 투어 미리보기</h4>
                        <div class="preview-stats">
                            <div class="stat-item">
                                <span>장소 수</span>
                                <strong id="preview-places">0곳</strong>
                            </div>
                            <div class="stat-item">
                                <span>예상 시간</span>
                                <strong id="preview-time">0분</strong>
                            </div>
                            <div class="stat-item">
                                <span>이동 거리</span>
                                <strong id="preview-distance">0km</strong>
                            </div>
                            <div class="stat-item">
                                <span>예상 비용</span>
                                <strong id="preview-cost">0원</strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="selected-places-list" id="selected-places-list">
                        <div class="empty-state">
                            <div class="empty-icon">🗺️</div>
                            <p>방문하고 싶은 장소를 선택해주세요<br>최대 8곳까지 선택 가능합니다</p>
                        </div>
                    </div>
                    
                    <!-- 액션 버튼들 -->
                    <div class="action-buttons">
                        <button class="btn btn-primary" id="optimize-btn" disabled>
                            ⚡ 경로 최적화
                        </button>
                        <button class="btn btn-secondary" id="guide-btn" disabled>
                            📖 투어 가이드
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 🔥 통합 투어플래너 JavaScript
class IntegratedTourPlanner {
    constructor() {
        this.selectedPlaces = JSON.parse(localStorage.getItem('selectedStores') || '[]');
        this.tourPurpose = null;
        this.lastOptimizedRoute = null;
        this.apiBase = '<?php echo esc_url(home_url("/wp-json/sungsuya/v2/")); ?>';
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateUI();
        this.restoreSelectedPlaces();
        this.connectToExistingSystem();
        this.initMapSafely();
        
        console.log('🚀 통합 투어플래너 초기화 완료');
    }

    bindEvents() {
        // 투어 목적 선택
        document.querySelectorAll('.purpose-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.selectTourPurpose(e.target.dataset.purpose);
            });
        });

        // 경로 최적화
        document.getElementById('optimize-btn').addEventListener('click', () => {
            this.optimizeRoute();
        });

        // 투어 가이드
        document.getElementById('guide-btn').addEventListener('click', () => {
            this.showTourGuide();
        });

        // 장소 제거 (이벤트 위임)
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-place')) {
                const placeId = e.target.dataset.placeId;
                this.removePlace(placeId);
            }
        });
    }

    selectTourPurpose(purpose) {
        // 기존 선택 해제
        document.querySelectorAll('.purpose-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // 새 선택 적용
        document.querySelector(`[data-purpose="${purpose}"]`).classList.add('active');
        this.tourPurpose = purpose;

        console.log('선택된 투어 목적:', purpose);

        // 목적에 따른 자동 추천
        this.handlePurposeSelection(purpose);
    }

    async handlePurposeSelection(purpose) {
        switch(purpose) {
            case 'quick':
                await this.recommendQuickTour();
                this.showMessage('⚡ 빠른 투어: 핵심 장소 3-4곳을 추천드립니다!');
                break;
            case 'detailed':
                await this.recommendDetailedTour();
                this.showMessage('🕒 여유 투어: 다양한 장소 5-6곳을 추천드립니다!');
                break;
            case 'custom':
                this.showMessage('🎯 직접 선택: 원하는 장소를 자유롭게 선택하세요!');
                break;
        }
    }

    async recommendQuickTour() {
        try {
            // 샘플 추천 데이터 (API 연결 전)
            const recommendations = [
                {
                    id: 'quick-1',
                    title: '성수동 인기 팝업스토어',
                    place_type: 'popup_store',
                    location: { address: '성수동1가', lat: 37.5444, lng: 127.0548 }
                },
                {
                    id: 'quick-2',
                    title: '성수동 맛집',
                    place_type: 'restaurant',
                    location: { address: '성수동2가', lat: 37.5454, lng: 127.0558 }
                },
                {
                    id: 'quick-3',
                    title: '성수동 카페',
                    place_type: 'retail_store',
                    location: { address: '성수동1가', lat: 37.5434, lng: 127.0538 }
                }
            ];
            
            this.autoSelectPlaces(recommendations);
        } catch (error) {
            console.error('빠른 투어 추천 실패:', error);
        }
    }

    async recommendDetailedTour() {
        try {
            // 샘플 추천 데이터 (API 연결 전)
            const recommendations = [
                {
                    id: 'detailed-1',
                    title: '성수동 팝업스토어 A',
                    place_type: 'popup_store',
                    location: { address: '성수동1가', lat: 37.5444, lng: 127.0548 }
                },
                {
                    id: 'detailed-2',
                    title: '성수동 브런치 카페',
                    place_type: 'restaurant',
                    location: { address: '성수동2가', lat: 37.5454, lng: 127.0558 }
                },
                {
                    id: 'detailed-3',
                    title: '성수동 편집샵',
                    place_type: 'retail_store',
                    location: { address: '성수동1가', lat: 37.5434, lng: 127.0538 }
                },
                {
                    id: 'detailed-4',
                    title: '성수동 공원',
                    place_type: 'facility',
                    location: { address: '성수동3가', lat: 37.5464, lng: 127.0568 }
                },
                {
                    id: 'detailed-5',
                    title: '성수동 갤러리',
                    place_type: 'retail_store',
                    location: { address: '성수동1가', lat: 37.5424, lng: 127.0528 }
                }
            ];
            
            this.autoSelectPlaces(recommendations);
        } catch (error) {
            console.error('여유 투어 추천 실패:', error);
        }
    }

    autoSelectPlaces(places) {
        // 기존 선택 초기화
        this.selectedPlaces = [];
        
        // 추천 장소들 자동 선택
        places.forEach(place => {
            this.selectedPlaces.push(place);
        });
        
        this.saveSelectedPlaces();
        this.updateUI();
        this.syncWithExistingSystem();
    }

    removePlace(placeId) {
        const index = this.selectedPlaces.findIndex(p => p.id == placeId);
        if (index !== -1) {
            const place = this.selectedPlaces[index];
            this.selectedPlaces.splice(index, 1);
            this.saveSelectedPlaces();
            this.updateUI();
            this.syncWithExistingSystem();
            
            this.showMessage(`${place.title}이(가) 투어에서 제거되었습니다`);
        }
    }

    async optimizeRoute() {
        if (this.selectedPlaces.length < 2) {
            this.showMessage('최소 2개 이상의 장소를 선택해주세요');
            return;
        }

        const optimizeBtn = document.getElementById('optimize-btn');
        const originalText = optimizeBtn.textContent;
        optimizeBtn.textContent = '최적화 중...';
        optimizeBtn.disabled = true;

        try {
            // 임시 최적화 결과 (실제 API 연결 전)
            const data = {
                success: true,
                optimized_route: this.selectedPlaces.map((_, index) => index),
                total_distance: (this.selectedPlaces.length * 0.4).toFixed(1),
                estimated_time: this.selectedPlaces.length * 25
            };
            
            this.lastOptimizedRoute = data;
            this.showMessage(`경로가 최적화되었습니다! 총 거리: ${data.total_distance}km`);
            
        } catch (error) {
            console.error('경로 최적화 실패:', error);
            this.showMessage('경로 최적화에 실패했습니다');
        } finally {
            optimizeBtn.textContent = originalText;
            optimizeBtn.disabled = false;
        }
    }

    showTourGuide() {
        if (this.selectedPlaces.length === 0) {
            this.showMessage('먼저 장소를 선택해주세요');
            return;
        }

        // 투어 가이드 생성
        const guide = this.generateTourGuide();
        this.displayTourGuide(guide);
    }

    generateTourGuide() {
        const places = this.selectedPlaces;
        let currentTime = new Date();
        currentTime.setHours(10, 0, 0, 0); // 오전 10시 시작
        
        const timeline = places.map((place, index) => {
            const timeStr = this.formatTime(currentTime);
            const duration = '30분';
            const tips = this.getPlaceTips(place.place_type);
            
            const item = {
                time: timeStr,
                place: place.title,
                type: this.getTypeLabel(place.place_type),
                duration: duration,
                tips: tips
            };
            
            currentTime.setMinutes(currentTime.getMinutes() + 35); // 30분 체류 + 5분 이동
            return item;
        });
        
        return {
            summary: {
                places: places.length,
                time: `${places.length * 35}분`,
                distance: `${(places.length * 0.4).toFixed(1)}km`,
                cost: `${places.length * 7000}원`
            },
            timeline: timeline,
            tips: [
                '성수역에서 시작하는 것을 추천합니다',
                '편한 신발을 착용하세요',
                '점심시간(12-13시) 혼잡을 피하세요',
                '휴대폰 충전기를 챙기세요'
            ]
        };
    }

    getPlaceTips(placeType) {
        const tips = {
            'popup_store': ['포토존 활용', '한정 상품 확인'],
            'restaurant': ['대기시간 확인', '인기 메뉴 주문'],
            'retail_store': ['할인 정보 확인', '영업시간 확인'],
            'facility': ['필요시에만 이용', '편의시설 활용']
        };
        return tips[placeType] || ['즐거운 시간 보내세요'];
    }

    getTypeLabel(placeType) {
        const labels = {
            'popup_store': '팝업스토어',
            'restaurant': '맛집',
            'retail_store': '상설매장',
            'facility': '편의시설'
        };
        return labels[placeType] || '장소';
    }

    formatTime(date) {
        return date.getHours().toString().padStart(2, '0') + ':' + 
               date.getMinutes().toString().padStart(2, '0');
    }

    displayTourGuide(guide) {
        const guideHTML = `
            <div class="tour-guide-modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center;">
                <div class="guide-content" style="background: white; border-radius: 1rem; padding: 2rem; max-width: 600px; max-height: 80vh; overflow-y: auto; margin: 1rem;">
                    <div class="guide-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2 style="margin: 0;">📋 투어 가이드</h2>
                        <button onclick="tourPlanner.closeTourGuide()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">✕</button>
                    </div>
                    
                    <div class="guide-summary" style="background: #f0f9ff; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                        <h3 style="margin-bottom: 0.5rem;">투어 요약</h3>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem;">
                            <span>📍 총 ${guide.summary.places}곳</span>
                            <span>⏱️ ${guide.summary.time}</span>
                            <span>🚶 ${guide.summary.distance}</span>
                            <span>💰 ${guide.summary.cost}</span>
                        </div>
                    </div>
                    
                    <div class="guide-timeline" style="margin-bottom: 1.5rem;">
                        <h3 style="margin-bottom: 1rem;">시간별 일정</h3>
                        ${guide.timeline.map((item, index) => `
                            <div style="display: flex; margin-bottom: 1rem; padding: 1rem; background: #f9fafb; border-radius: 0.5rem;">
                                <div style="width: 60px; font-weight: bold; color: #3b82f6;">${item.time}</div>
                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 0.25rem 0;">${item.place}</h4>
                                    <p style="margin: 0 0 0.5rem 0; color: #6b7280; font-size: 0.9rem;">${item.type} • ${item.duration}</p>
                                    <p style="margin: 0; font-size: 0.8rem; color: #059669;">💡 ${item.tips.join(', ')}</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    
                    <div class="guide-tips">
                        <h3 style="margin-bottom: 1rem;">💡 투어 팁</h3>
                        <ul style="margin: 0; padding-left: 1.5rem;">
                            ${guide.tips.map(tip => `<li style="margin-bottom: 0.5rem;">${tip}</li>`).join('')}
                        </ul>
                    </div>
                    
                    <div class="guide-actions" style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
                        <button onclick="tourPlanner.shareTourGuide()" style="background: #3b82f6; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 0.5rem; cursor: pointer;">📤 공유하기</button>
                        <button onclick="tourPlanner.saveTourGuide()" style="background: #10b981; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 0.5rem; cursor: pointer;">💾 저장하기</button>
                        <button onclick="tourPlanner.closeTourGuide()" style="background: #6b7280; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 0.5rem; cursor: pointer;">닫기</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', guideHTML);
    }

    closeTourGuide() {
        const modal = document.querySelector('.tour-guide-modal');
        if (modal) modal.remove();
    }

    shareTourGuide() {
        if (navigator.share) {
            navigator.share({
                title: '성수동 투어 가이드',
                text: '성수동 투어 계획을 확인해보세요!',
                url: window.location.href
            });
        } else {
            navigator.clipboard.writeText(window.location.href);
            this.showMessage('투어 링크가 복사되었습니다!');
        }
    }

    saveTourGuide() {
        localStorage.setItem('lastTourGuide', JSON.stringify({
            timestamp: new Date().toISOString(),
            places: this.selectedPlaces
        }));
        this.showMessage('투어 가이드가 저장되었습니다!');
    }

    updateUI() {
        this.updateSelectedPlacesList();
        this.updateTourPreview();
        this.updateActionButtons();
    }

    updateSelectedPlacesList() {
        const listContainer = document.getElementById('selected-places-list');
        const countElement = document.getElementById('places-count');

        countElement.textContent = `${this.selectedPlaces.length}곳`;

        if (this.selectedPlaces.length === 0) {
            listContainer.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">🗺️</div>
                    <p>방문하고 싶은 장소를 선택해주세요<br>최대 8곳까지 선택 가능합니다</p>
                </div>
            `;
            return;
        }

        const typeIcons = {
            'popup_store': '🏪',
            'restaurant': '🍽️',
            'retail_store': '🏬',
            'facility': '🚻'
        };

        listContainer.innerHTML = this.selectedPlaces.map((place, index) => {
            const icon = typeIcons[place.place_type] || '📍';
            const typeLabel = this.getTypeLabel(place.place_type);

            return `
                <div class="selected-place">
                    <div class="place-number">${index + 1}</div>
                    <div class="place-info">
                        <div class="place-name">${place.title}</div>
                        <div class="place-type">
                            <span style="margin-right: 0.25rem;">${icon}</span>
                            <span>${typeLabel}</span>
                        </div>
                    </div>
                    <button class="remove-place" data-place-id="${place.id}">✕</button>
                </div>
            `;
        }).join('');
    }

    updateTourPreview() {
        const previewContainer = document.getElementById('tour-preview');
        
        if (this.selectedPlaces.length === 0) {
            previewContainer.style.display = 'none';
            return;
        }

        previewContainer.style.display = 'block';

        // 기본 계산
        const count = this.selectedPlaces.length;
        const estimatedTime = count * 25; // 장소당 25분
        const estimatedDistance = count > 1 ? (count - 1) * 0.4 : 0; // 장소간 400m
        const estimatedCost = count * 7000; // 장소당 7천원

        document.getElementById('preview-places').textContent = `${count}곳`;
        document.getElementById('preview-time').textContent = `${estimatedTime}분`;
        document.getElementById('preview-distance').textContent = `${estimatedDistance.toFixed(1)}km`;
        document.getElementById('preview-cost').textContent = `${estimatedCost.toLocaleString()}원`;
    }

    updateActionButtons() {
        const optimizeBtn = document.getElementById('optimize-btn');
        const guideBtn = document.getElementById('guide-btn');

        const hasEnoughPlaces = this.selectedPlaces.length >= 2;
        const hasAnyPlaces = this.selectedPlaces.length > 0;

        optimizeBtn.disabled = !hasEnoughPlaces;
        guideBtn.disabled = !hasAnyPlaces;
    }

    saveSelectedPlaces() {
        localStorage.setItem('selectedStores', JSON.stringify(this.selectedPlaces));
    }

    restoreSelectedPlaces() {
        // 페이지 로드시 기존에 선택된 장소들 복원
        this.updateUI();
    }

    connectToExistingSystem() {
        // 기존 시스템과 연동
        if (window.SungsuyaApp) {
            window.SungsuyaApp.state.selectedStores = this.selectedPlaces;
        }
    }

    syncWithExistingSystem() {
        // 기존 시스템의 상태 업데이트
        if (window.SungsuyaApp && window.SungsuyaApp.state) {
            window.SungsuyaApp.state.selectedStores = this.selectedPlaces;
        }
    }

    initMapSafely() {
        // 지도 초기화 시도 (실패해도 계속 진행)
        setTimeout(() => {
            try {
                this.initBasicMap();
            } catch (error) {
                console.log('지도 초기화 실패 (선택사항):', error);
                this.showMapError();
            }
        }, 1000);
    }

    initBasicMap() {
        const mapContainer = document.getElementById('map');
        if (!mapContainer) return;

        // 네이버 지도 API 체크
        if (typeof naver !== 'undefined' && naver.maps) {
            this.createMap();
        } else {
            this.loadNaverMapsScript();
        }
    }

    loadNaverMapsScript() {
        // 네이버 지도 스크립트 로드 (선택사항)
        console.log('네이버 지도 API 로드 시도...');
        
        const script = document.createElement('script');
        script.src = 'https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId=YOUR_CLIENT_ID';
        
        script.onload = () => {
            this.createMap();
        };
        
        script.onerror = () => {
            this.showMapError();
        };
        
        document.head.appendChild(script);
    }

    createMap() {
        const mapContainer = document.getElementById('map');
        if (!mapContainer) return;

        try {
            const map = new naver.maps.Map(mapContainer, {
                center: new naver.maps.LatLng(37.5444, 127.0548),
                zoom: 15
            });
            
            console.log('지도 생성 완료');
            this.map = map;
            
        } catch (error) {
            console.error('지도 생성 실패:', error);
            this.showMapError();
        }
    }

    showMapError() {
        const mapContainer = document.getElementById('map');
        if (mapContainer) {
            mapContainer.innerHTML = `
                <div class="map-error-container">
                    <div class="map-error-content">
                        <div class="error-icon">🗺️</div>
                        <h3 class="error-title">지도를 불러올 수 없습니다</h3>
                        <p class="error-message">네트워크 연결을 확인하고 다시 시도해주세요</p>
                        <button class="btn-retry" onclick="tourPlanner.initMapSafely()">다시 시도</button>
                        <div style="margin-top: 1rem; padding: 1rem; background: #f0f9ff; border-radius: 0.5rem; font-size: 0.9rem; color: #1e40af;">
                            <p><strong>💡 지도 없이도 투어플래너를 사용할 수 있습니다!</strong></p>
                            <p>우측 사이드바에서 장소를 선택하고 경로를 최적화하세요.</p>
                        </div>
                    </div>
                </div>
            `;
        }
    }

    showMessage(message) {
        // 간단한 토스트 메시지
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            z-index: 1000;
            max-width: 80%;
            text-align: center;
            font-size: 0.9rem;
        `;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    
    // 🎯 API 최적화 함수들 추가
    activateInteractiveMap() {
        console.log('🗺️ 사용자 요청: 지도 활성화 시작 (API 사용)');
        
        // 플레이스홀더 숨기고 로딩 표시
        document.getElementById('map-placeholder').style.display = 'none';
        document.getElementById('interactive-map').style.display = 'block';
        document.getElementById('interactive-map').innerHTML = `
            <div class="map-loading">
                <div class="loading-spinner"></div>
                <p>상세 지도 로딩중...</p>
            </div>
        `;
        
        // 네이버 지도 API 로드 및 초기화
        if (typeof naver !== 'undefined' && naver.maps) {
            this.createInteractiveMap();
        } else {
            this.loadNaverMapsAPIOptimized().then(() => {
                this.createInteractiveMap();
            }).catch(() => {
                this.showInteractiveMapError();
            });
        }
    }
    
    createInteractiveMap() {
        try {
            const mapContainer = document.getElementById('interactive-map');
            
            const map = new naver.maps.Map(mapContainer, {
                center: new naver.maps.LatLng(37.5444, 127.0548),
                zoom: 15,
                mapTypeControl: true,
                mapDataControl: false,
                scaleControl: true,
                logoControl: false,
                zoomControl: true
            });
            
            this.map = map;
            this.markers = [];
            
            // 선택된 장소들을 지도에 마커로 표시
            this.addPlaceMarkersToMap();
            
            // "정적 모드로 돌아가기" 버튼 추가
            setTimeout(() => {
                const backButton = document.createElement('button');
                backButton.innerHTML = '📷 정적 모드로 돌아가기';
                backButton.className = 'back-to-static-btn';
                backButton.style.cssText = `
                    position: absolute;
                    top: 1rem;
                    left: 1rem;
                    background: white;
                    border: 1px solid #d1d5db;
                    padding: 0.5rem 1rem;
                    border-radius: 0.5rem;
                    font-size: 0.875rem;
                    cursor: pointer;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    z-index: 1000;
                `;
                backButton.onclick = () => this.deactivateInteractiveMap();
                
                mapContainer.style.position = 'relative';
                mapContainer.appendChild(backButton);
            }, 1000);
            
            console.log('✅ 투어플래너 지도 생성 완료 (API 1회 사용)');
            
        } catch (error) {
            console.error('❌ 투어플래너 지도 생성 실패:', error);
            this.showInteractiveMapError();
        }
    }
    
    deactivateInteractiveMap() {
        // 동적 지도 숨기고 플레이스홀더 표시
        document.getElementById('interactive-map').style.display = 'none';
        document.getElementById('map-placeholder').style.display = 'block';
        
        // 돌아가기 버튼 제거
        const backButton = document.querySelector('.back-to-static-btn');
        if (backButton) backButton.remove();
        
        console.log('📷 정적 모드로 돌아감 (API 절약 모드)');
    }
    
    addPlaceMarkersToMap() {
        if (!this.map || !this.selectedPlaces.length) return;
        
        // 기존 마커들 제거
        if (this.markers) {
            this.markers.forEach(marker => marker.setMap(null));
        }
        this.markers = [];
        
        // 새 마커들 추가
        this.selectedPlaces.forEach((place, index) => {
            if (place.location && place.location.lat && place.location.lng) {
                const marker = new naver.maps.Marker({
                    position: new naver.maps.LatLng(place.location.lat, place.location.lng),
                    map: this.map,
                    title: place.title,
                    icon: {
                        content: `<div style="background: #3b82f6; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">${index + 1}</div>`,
                        anchor: new naver.maps.Point(15, 15)
                    }
                });
                
                // 마커 클릭시 정보창 표시
                const infoWindow = new naver.maps.InfoWindow({
                    content: `<div style="padding: 10px; font-size: 12px;"><strong>${place.title}</strong><br/>${this.getTypeLabel(place.place_type)}</div>`
                });
                
                naver.maps.Event.addListener(marker, 'click', function() {
                    if (infoWindow.getMap()) {
                        infoWindow.close();
                    } else {
                        infoWindow.open(this.map, marker);
                    }
                }.bind(this));
                
                this.markers.push(marker);
            }
        });
        
        // 모든 마커가 보이도록 지도 범위 조정
        if (this.markers.length > 1) {
            const bounds = new naver.maps.LatLngBounds();
            this.markers.forEach(marker => {
                bounds.extend(marker.getPosition());
            });
            this.map.fitBounds(bounds, { padding: 50 });
        }
    }
    
    updateMapButtonState() {
        const activateBtn = document.getElementById('activate-map-btn');
        if (activateBtn && this.selectedPlaces.length > 0) {
            activateBtn.innerHTML = `🗺️ 지도 활성화하기 (${this.selectedPlaces.length}곳 표시)`;
            activateBtn.style.background = '#10b981';
        }
    }
    
    loadNaverMapsAPIOptimized() {
        return new Promise((resolve, reject) => {
            if (typeof naver !== 'undefined' && naver.maps) {
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = 'https://openapi.map.naver.com/openapi/v3/maps.js?ncpClientId=mvz9m8g0bt&callback=naverMapAPILoadedForTourPlanner';
            script.async = true;
            script.onerror = reject;
            
            window.naverMapAPILoadedForTourPlanner = function() {
                console.log('📍 네이버 지도 API 투어플래너용 동적 로드 완료');
                resolve();
            };
            
            document.head.appendChild(script);
            
            setTimeout(reject, 10000); // 10초 타임아웃
        });
    }
    
    showInteractiveMapError() {
        document.getElementById('interactive-map').innerHTML = `
            <div class="map-error-container">
                <div class="map-error-content">
                    <div class="error-icon">❌</div>
                    <h3 class="error-title">지도 로드 실패</h3>
                    <p class="error-message">네트워크 연결을 확인하고 다시 시도해주세요</p>
                    <button class="btn-retry" onclick="tourPlanner.deactivateInteractiveMap()">정적 모드로 돌아가기</button>
                    <div style="margin-top: 1rem; padding: 1rem; background: #f0f9ff; border-radius: 0.5rem; font-size: 0.9rem; color: #1e40af;">
                        <p><strong>💡 지도 없이도 투어플래너를 완전히 사용할 수 있습니다!</strong></p>
                        <p>경로 최적화, 투어 가이드 등 모든 기능이 정상 작동합니다.</p>
                    </div>
                </div>
            </div>
        `;
    }
}

// 전역 함수들
function goToPlaces(type) {
    let url = '<?php echo esc_url(home_url("/places")); ?>';
    if (type) {
        url += `?place_type=${type}`;
    }
    window.location.href = url;
}

// 투어플래너 초기화
let tourPlanner;

document.addEventListener('DOMContentLoaded', function() {
    tourPlanner = new IntegratedTourPlanner();
});

// 기존 시스템과의 연동을 위한 전역 접근
window.tourPlanner = tourPlanner;
</script>

<?php get_footer(); ?>
