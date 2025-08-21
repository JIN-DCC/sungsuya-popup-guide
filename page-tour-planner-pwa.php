<?php
/**
 * Template Name: PWA 바텀시트 투어플래너
 * 
 * 헤더가 있는 완전한 통합 투어플래너
 */

get_header(); ?>

<!-- 투어플래너 핵심 초기화 스크립트 -->
<script src="<?php echo get_template_directory_uri(); ?>/assets/js/tour-planner-init.js?ver=<?php echo time(); ?>"></script>

<style>
/* 🔥 투어플래너 전용 스타일 - 기존 테마와 조화 */
.tour-planner-page {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: calc(100vh - 120px); /* 헤더 높이 고려 */
    padding: 1rem 0 2rem; /* 상단 여백 줄임 */
    margin-top: 0;
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

/* 메인 플래너 레이아웃 - UX 개선된 3단 구조 */
.planner-main {
    background: white;
    border-radius: 1.5rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: grid;
    grid-template-columns: 320px 1fr 280px; /* 왼쪽 컨트롤 + 중앙 지도 + 우측 상세 */
    min-height: 70vh;
    height: auto;
    position: relative;
    z-index: 2;
}

.planner-controls {
    background: #f8fafc;
    border-right: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    max-height: 70vh;
}

.planner-map-section {
    position: relative;
    background: #f8fafc;
    min-height: 500px;
}

.planner-details {
    background: white;
    border-left: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    max-height: 70vh;
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

/* 컨트롤 패널 스타일 */
.control-section {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.control-section:last-child {
    border-bottom: none;
}

.control-title {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* 개선된 투어 목적 선택 */
.purpose-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.purpose-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.purpose-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.purpose-btn.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.purpose-btn[data-purpose="detailed"].active {
    background: #10b981;
    border-color: #10b981;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.purpose-btn[data-purpose="custom"].active {
    background: #6b7280;
    border-color: #6b7280;
    box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
}

.purpose-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.purpose-info {
    flex: 1;
}

.purpose-name {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.purpose-desc {
    font-size: 0.8rem;
    opacity: 0.8;
}

.purpose-btn.active .purpose-desc {
    opacity: 0.9;
}

/* 개선된 빠른 추가 버튼 */
.quick-add-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}

.quick-add-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.quick-add-btn:hover {
    background: #f1f5f9;
    border-color: #3b82f6;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.add-icon {
    font-size: 1.2rem;
}

.add-label {
    font-size: 0.8rem;
    font-weight: 500;
    color: #374151;
}

/* 투어 설정 */
.tour-settings {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.setting-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.setting-item label {
    font-size: 0.9rem;
    font-weight: 500;
    color: #374151;
}

.setting-select {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: white;
    font-size: 0.9rem;
    cursor: pointer;
    transition: border-color 0.2s;
}

.setting-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* 상세 정보 패널 스타일 */
.detail-section {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.detail-section:last-child {
    border-bottom: none;
}

.detail-title {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* 투어 요약 카드 */
.summary-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.stat-card {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 0.75rem;
    text-align: center;
    border: 1px solid #e5e7eb;
    transition: all 0.2s;
}

.stat-card:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.8rem;
    color: #6b7280;
    font-weight: 500;
}

/* 장소 목록 헤더 */
.places-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.places-count {
    background: #3b82f6;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.8rem;
    font-weight: 600;
}

/* 개선된 빈 상태 */
.empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    opacity: 0.6;
}

.btn-empty {
    margin-top: 1rem;
    padding: 0.5rem 1rem;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 500;
    transition: background 0.2s;
}

.btn-empty:hover {
    background: #2563eb;
}

/* 개선된 액션 버튼 */
.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
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

.btn-icon {
    font-size: 1rem;
}

.btn-primary {
    background: #3b82f6;
    color: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.btn-primary:hover:not(:disabled) {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-primary:disabled {
    background: #d1d5db;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-secondary {
    background: #10b981;
    color: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.btn-secondary:hover:not(:disabled) {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

/* 선택된 장소 목록 */
.selected-places-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.selected-places-list {
    max-height: 350px; /* 장소 목록만 제한적 스크롤 */
    overflow-y: auto;
    margin-bottom: 1rem;
    padding-right: 0.5rem; /* 스크롤바 공간 */
}

/* 스크롤바 스타일링 */
.selected-places-list::-webkit-scrollbar {
    width: 6px;
}

.selected-places-list::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.selected-places-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.selected-places-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
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
    transition: all 0.2s ease;
    position: relative;
}

.selected-place:hover {
    background: #f1f5f9;
    border-color: #d1d5db;
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

/* 액션 버튼들 */
.btn-success {
    background: #10b981;
    color: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.btn-success:hover {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn-outline {
    background: transparent;
    color: #374151;
    border: 1px solid #d1d5db;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
    transform: translateY(-1px);
}

.save-share-buttons {
    display: flex;
    gap: 0.5rem;
}

.save-share-buttons .btn {
    flex: 1;
}

/* 반응형 디자인 */
@media (max-width: 1024px) {
    .planner-main {
        grid-template-columns: 1fr;
        grid-template-rows: auto auto auto;
    }
    
    .planner-controls {
        max-height: none;
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .planner-map-section {
        min-height: 400px;
    }
    
    .planner-details {
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
        flex-direction: column;
        align-items: center;
    }
    
    .planner-main {
        border-radius: 1rem;
        margin: 0 0.5rem;
    }
    
    .quick-add-grid {
        grid-template-columns: 1fr;
    }
    
    .summary-stats {
        grid-template-columns: repeat(2, 1fr);
    }
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

        <!-- 메인 플래너 - 개선된 3단 레이아웃 -->
        <div class="planner-main">
            <!-- 왼쪽: 컨트롤 패널 -->
            <div class="planner-controls">
                <!-- 투어 스타일 선택 -->
                <div class="control-section">
                    <h3 class="control-title">🎯 투어 스타일</h3>
                    <div class="purpose-buttons">
                        <button class="purpose-btn" data-purpose="quick">
                            <span class="purpose-icon">⚡</span>
                            <div class="purpose-info">
                                <div class="purpose-name">빠른 투어</div>
                                <div class="purpose-desc">90분 • 3-4곳</div>
                            </div>
                        </button>
                        <button class="purpose-btn" data-purpose="detailed">
                            <span class="purpose-icon">🕒</span>
                            <div class="purpose-info">
                                <div class="purpose-name">여유 투어</div>
                                <div class="purpose-desc">3시간 • 5-6곳</div>
                            </div>
                        </button>
                        <button class="purpose-btn" data-purpose="custom">
                            <span class="purpose-icon">🎨</span>
                            <div class="purpose-info">
                                <div class="purpose-name">직접 선택</div>
                                <div class="purpose-desc">자유 • 최대 8곳</div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- 빠른 장소 추가 -->
                <div class="control-section">
                    <h3 class="control-title">🏪 빠른 추가</h3>
                    <div class="quick-add-grid" id="quick-add-buttons">
                        <!-- 동적으로 로드됩니다 -->
                        <div class="loading-spinner" style="margin: 2rem auto;">
                            <div style="text-align: center; color: #6b7280;">
                                <div class="spinner" style="
                                    width: 30px; height: 30px;
                                    border: 3px solid #e5e7eb;
                                    border-top: 3px solid #3b82f6;
                                    border-radius: 50%;
                                    animation: spin 1s linear infinite;
                                    margin: 0 auto 0.5rem;
                                "></div>
                                <p>장소유형 로딩중...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 투어 설정 -->
                <div class="control-section">
                    <h3 class="control-title">⚙️ 투어 설정</h3>
                    <div class="tour-settings">
                        <div class="setting-item">
                            <label for="start-time">시작 시간</label>
                            <select id="start-time" class="setting-select">
                                <option value="09:00">오전 9시</option>
                                <option value="10:00" selected>오전 10시</option>
                                <option value="11:00">오전 11시</option>
                                <option value="14:00">오후 2시</option>
                                <option value="15:00">오후 3시</option>
                            </select>
                        </div>
                        <div class="setting-item">
                            <label for="transport">이동 수단</label>
                            <select id="transport" class="setting-select">
                                <option value="walk" selected>도보</option>
                                <option value="public">대중교통</option>
                                <option value="car">자동차</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 중앙: 지도 섹션 -->
            <div class="planner-map-section">
                <div class="planner-map">
                    <div id="map" style="width: 100%; height: 100%;">
                        <div class="map-loading">
                            <div class="loading-spinner"></div>
                            <p>지도를 불러오는 중...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 우측: 상세 정보 패널 -->
            <div class="planner-details">
                <!-- 투어 요약 -->
                <div class="detail-section">
                    <div class="tour-summary">
                        <h3 class="detail-title">📋 투어 요약</h3>
                        <div class="summary-stats" id="tour-summary-stats">
                            <div class="stat-card">
                                <div class="stat-value" id="summary-places">0</div>
                                <div class="stat-label">장소</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" id="summary-time">0분</div>
                                <div class="stat-label">소요시간</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" id="summary-distance">0km</div>
                                <div class="stat-label">이동거리</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" id="summary-cost">0원</div>
                                <div class="stat-label">예상비용</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 선택된 장소 목록 -->
                <div class="detail-section">
                    <div class="places-header">
                        <h3 class="detail-title">📍 선택된 장소</h3>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span class="places-count" id="places-count">0곳</span>
                            <button onclick="tourPlanner.clearAllPlaces()" style="
                                background: #ef4444;
                                color: white;
                                border: none;
                                padding: 0.25rem 0.5rem;
                                border-radius: 0.25rem;
                                font-size: 0.75rem;
                                cursor: pointer;
                                transition: background 0.2s;
                            " onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                                전체 삭제
                            </button>
                        </div>
                    </div>
                    
                    <div class="selected-places-list" id="selected-places-list">
                        <div class="empty-state">
                            <div class="empty-icon">🗺️</div>
                            <p>장소를 선택하면<br>여기에 표시됩니다</p>
                            <button class="btn-empty" onclick="showPlaceSelector()">
                                장소 선택하기
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 액션 버튼 -->
                <div class="detail-section">
                    <div class="action-buttons">
                        <button class="btn btn-primary" id="optimize-btn" disabled>
                            <span class="btn-icon">⚡</span>
                            경로 최적화
                        </button>
                        <button class="btn btn-secondary" id="guide-btn" disabled>
                            <span class="btn-icon">📖</span>
                            투어 가이드
                        </button>
                    </div>
                    
                    <!-- 저장/공유 버튼 -->
                    <div class="save-share-buttons" style="margin-top: 0.75rem;">
                        <button class="btn btn-success" id="save-tour-btn" disabled>
                            <span class="btn-icon">💾</span>
                            투어 저장
                        </button>
                        <button class="btn btn-outline" id="share-tour-btn" disabled>
                            <span class="btn-icon">📤</span>
                            공유하기
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 🔥 네비게이션 활성 상태 설정
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 투어플래너 페이지 초기화');
    
    // 모든 네비게이션 링크의 active 클래스 제거
    document.querySelectorAll('.nav-link, .mobile-nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // 투어플래너 네비게이션에 active 클래스 추가
    document.querySelectorAll('a[href*="/planner"]').forEach(link => {
        link.classList.add('active');
    });
    
    // 선택된 장소 표시 업데이트
    updateSelectedPlacesList();
    updateTourSummary();
    updateActionButtons();
    
    // 지도 초기화
    initMap();
    
    // 동적 장소유형 로드
    loadPlaceTypes();
});

// 동적으로 장소유형 로드
async function loadPlaceTypes() {
    console.log('📡 장소유형 로딩 시작...');
    
    try {
        const response = await fetch('/wp-json/sungsuya/v2/place-types');
        if (!response.ok) {
            throw new Error('Failed to load place types');
        }
        
        const placeTypes = await response.json();
        console.log('✅ 장소유형 로드 완료:', placeTypes);
        
        // 빠른 추가 버튼 생성
        renderQuickAddButtons(placeTypes);
        
        // 범례 업데이트
        updateTypeLegend(placeTypes);
        
    } catch (error) {
        console.error('장소유형 로드 실패:', error);
        
        // 오류 시 기본 버튼 표시
        document.getElementById('quick-add-buttons').innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; color: #ef4444;">
                <p>장소유형을 불러올 수 없습니다.</p>
                <button onclick="loadPlaceTypes()" style="
                    margin-top: 0.5rem;
                    padding: 0.5rem 1rem;
                    background: #3b82f6;
                    color: white;
                    border: none;
                    border-radius: 0.375rem;
                    cursor: pointer;
                ">다시 시도</button>
            </div>
        `;
    }
}

// 빠른 추가 버튼 렌더링
function renderQuickAddButtons(placeTypes) {
    const container = document.getElementById('quick-add-buttons');
    if (!container) return;
    
    // 주요 장소유형만 표시 (최대 6개)
    const mainTypes = placeTypes.slice(0, 6);
    
    container.innerHTML = mainTypes.map(type => `
        <button class="quick-add-btn" data-type="${type.slug}" data-type-id="${type.id}">
            <span class="add-icon">${type.icon}</span>
            <span class="add-label">${type.name}</span>
        </button>
    `).join('');
    
    // 이벤트 리스너 재바인딩
    container.querySelectorAll('.quick-add-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const addBtn = e.target.closest('.quick-add-btn');
            if (addBtn && window.tourPlanner) {
                window.tourPlanner.quickAddPlaces(addBtn.dataset.type);
            }
        });
    });
}

// 범례 업데이트
function updateTypeLegend(placeTypes) {
    const legendContainer = document.querySelector('.type-legend');
    if (!legendContainer) return;
    
    // 상위 4개 장소유형만 표시
    const topTypes = placeTypes.slice(0, 4);
    
    legendContainer.innerHTML = topTypes.map(type => `
        <span class="type-item">${type.icon} ${type.name}</span>
    `).join('');
}

// 선택된 장소 목록 업데이트
function updateSelectedPlacesList() {
    const selectedStores = JSON.parse(localStorage.getItem('selectedStores') || '[]');
    const listContainer = document.getElementById('selected-places-list');
    const countElement = document.getElementById('places-count');
    
    if (!listContainer || !countElement) return;
    
    countElement.textContent = `${selectedStores.length}곳`;
    
    if (selectedStores.length === 0) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">🗺️</div>
                <p>장소를 선택하면<br>여기에 표시됩니다</p>
                <button class="btn-empty" onclick="showPlaceSelector()">
                    장소 선택하기
                </button>
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
    
    const typeLabels = {
        'popup_store': '팝업스토어',
        'restaurant': '맛집',
        'retail_store': '상설매장',
        'facility': '편의시설'
    };
    
    listContainer.innerHTML = selectedStores.map((place, index) => {
        const icon = typeIcons[place.place_type] || '📍';
        const typeLabel = typeLabels[place.place_type] || '장소';
        
        return `
            <div class="selected-place">
                <div class="place-number">${index + 1}</div>
                <div class="place-info">
                    <div class="place-name">${place.title || place.name || '장소'}</div>
                    <div class="place-type">
                        <span>${icon} ${typeLabel}</span>
                    </div>
                </div>
                <button class="remove-place" data-place-id="${place.id}">✕</button>
            </div>
        `;
    }).join('');
}

// 투어 요약 업데이트
function updateTourSummary() {
    const selectedStores = JSON.parse(localStorage.getItem('selectedStores') || '[]');
    const placeCount = selectedStores.length;
    
    document.getElementById('summary-places').textContent = placeCount;
    document.getElementById('summary-time').textContent = placeCount > 0 ? `${placeCount * 30}분` : '0분';
    document.getElementById('summary-distance').textContent = placeCount > 1 ? `${((placeCount - 1) * 0.4).toFixed(1)}km` : '0km';
    document.getElementById('summary-cost').textContent = placeCount > 0 ? `${(placeCount * 7000).toLocaleString()}원` : '0원';
}

// 액션 버튼 상태 업데이트
function updateActionButtons() {
    const selectedStores = JSON.parse(localStorage.getItem('selectedStores') || '[]');
    const hasPlaces = selectedStores.length > 0;
    
    document.getElementById('optimize-btn').disabled = !hasPlaces;
    document.getElementById('guide-btn').disabled = !hasPlaces;
    document.getElementById('save-tour-btn').disabled = !hasPlaces;
    document.getElementById('share-tour-btn').disabled = !hasPlaces;
}

// 지도 초기화
function initMap() {
    // 네이버 지도 API가 로드되었는지 확인
    if (typeof naver !== 'undefined' && naver.maps) {
        const mapOptions = {
            center: new naver.maps.LatLng(37.5444, 127.0557), // 성수동 중심
            zoom: 15,
            mapTypeControl: true,
            mapTypeControlOptions: {
                style: naver.maps.MapTypeControlStyle.DROPDOWN,
                position: naver.maps.Position.TOP_RIGHT
            },
            zoomControl: true,
            zoomControlOptions: {
                style: naver.maps.ZoomControlStyle.SMALL,
                position: naver.maps.Position.TOP_RIGHT
            }
        };
        
        const map = new naver.maps.Map('map', mapOptions);
        
        // 선택된 장소들 마커 표시
        const selectedStores = JSON.parse(localStorage.getItem('selectedStores') || '[]');
        selectedStores.forEach((place, index) => {
            if (place.location && place.location.lat && place.location.lng) {
                new naver.maps.Marker({
                    position: new naver.maps.LatLng(place.location.lat, place.location.lng),
                    map: map,
                    title: place.title || place.name,
                    icon: {
                        content: `<div style="
                            width: 32px; height: 32px;
                            background: #3b82f6;
                            color: white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-weight: 600;
                            font-size: 12px;
                            border: 2px solid white;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                        ">${index + 1}</div>`,
                        size: new naver.maps.Size(32, 32),
                        anchor: new naver.maps.Point(16, 16)
                    }
                });
            }
        });
        
        // 모든 마커가 보이도록 지도 범위 조정
        if (selectedStores.length > 0) {
            const bounds = new naver.maps.LatLngBounds();
            selectedStores.forEach(place => {
                if (place.location && place.location.lat && place.location.lng) {
                    bounds.extend(new naver.maps.LatLng(place.location.lat, place.location.lng));
                }
            });
            map.fitBounds(bounds, { padding: 50 });
        }
        
        console.log('✅ 지도 초기화 완료');
    } else {
        console.warn('네이버 지도 API가 아직 로드되지 않았습니다');
        // 1초 후 재시도
        setTimeout(initMap, 1000);
    }
}
</script>

<?php get_footer(); ?>
