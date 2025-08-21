<?php
/**
 * Template Name: Tour Planner PWA
 * 
 * PWA 전용 투어플래너 - 전체화면, 모바일 최적화
 * 
 * @since 2025-06-30
 */

// PWA 모드에서는 헤더/푸터 제거
$is_pwa = isset($_GET['mode']) && $_GET['mode'] === 'pwa';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#667eea">
    
    <title>성수야! 투어플래너</title>
    
    <?php wp_head(); ?>
    
    <!-- PWA 전용 스타일 -->
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/tour-planner-pwa.css?ver=<?php echo time(); ?>">
    
    <!-- 네이버 지도 API -->
    <?php
    $naver_client_id = get_option('naver_maps_client_id');
    if ($naver_client_id) : ?>
    <script type="text/javascript" src="https://oapi.map.naver.com/openapi/v3/maps.js?ncpClientId=<?php echo esc_attr($naver_client_id); ?>&submodules=geocoder"></script>
    <?php endif; ?>
    
    <!-- PWA manifest -->
    <link rel="manifest" href="<?php echo esc_url(home_url('/manifest.json')); ?>">
</head>

<body class="tour-pwa-body">
    
<div class="tour-pwa-container">
    <!-- PWA 헤더 (고정) -->
    <header class="pwa-header">
        <div class="pwa-header-content">
            <h1>🗺️ 성수야! 투어플래너</h1>
            <button class="pwa-menu-btn" id="pwa-menu-btn">☰</button>
        </div>
    </header>

    <!-- 메인 컨텐츠 영역 -->
    <main class="pwa-main">
        <!-- 지도 뷰 -->
        <div class="pwa-view active" data-view="map">
            <div id="tour-map" class="pwa-map">
                <div class="map-placeholder">
                    <div class="map-loading-content">
                        <div class="map-icon">🗺️</div>
                        <h3>지도 보기</h3>
                        <p>탭하여 지도를 불러옵니다</p>
                        <button class="load-map-btn">지도 불러오기</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 장소 목록 뷰 -->
        <div class="pwa-view" data-view="places">
            <div class="places-header">
                <div class="search-box">
                    <input type="text" id="place-search" placeholder="장소 검색..." class="search-input">
                </div>
                <div class="filter-chips" id="filter-chips">
                    <button class="chip active" data-type="all">전체</button>
                    <!-- 동적 추가 -->
                </div>
            </div>
            <div class="places-content" id="places-list">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>장소를 불러오는 중...</p>
                </div>
            </div>
        </div>

        <!-- 내 투어 뷰 -->
        <div class="pwa-view" data-view="tour">
            <div class="tour-header">
                <h2>선택한 장소</h2>
                <span class="place-count"><span id="selected-count">0</span>개</span>
            </div>
            
            <div class="tour-content">
                <!-- 선택된 장소 목록 -->
                <div class="selected-places" id="selected-list">
                    <div class="empty-state">
                        <div class="empty-icon">📝</div>
                        <h3>아직 선택한 장소가 없어요</h3>
                        <p>장소 탭에서 가고 싶은 곳을 선택해주세요</p>
                    </div>
                </div>
                
                <!-- 투어 요약 -->
                <div class="tour-summary-card">
                    <h3>투어 요약</h3>
                    <div class="summary-items">
                        <div class="summary-item">
                            <span class="icon">📍</span>
                            <div class="summary-value">
                                <span id="total-places">0</span>
                                <span class="label">장소</span>
                            </div>
                        </div>
                        <div class="summary-item">
                            <span class="icon">⏱️</span>
                            <div class="summary-value">
                                <span id="total-time">0분</span>
                                <span class="label">예상시간</span>
                            </div>
                        </div>
                        <div class="summary-item">
                            <span class="icon">🚶</span>
                            <div class="summary-value">
                                <span id="total-distance">0km</span>
                                <span class="label">이동거리</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 액션 버튼 -->
                <div class="tour-actions">
                    <button class="action-btn primary" id="share-btn" disabled>
                        <span class="icon">🔗</span>
                        공유하기
                    </button>
                    <button class="action-btn secondary" id="optimize-btn" disabled>
                        <span class="icon">✨</span>
                        경로 최적화
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- 하단 탭 네비게이션 -->
    <nav class="pwa-tabs">
        <button class="pwa-tab active" data-tab="map">
            <span class="tab-icon">🗺️</span>
            <span class="tab-label">지도</span>
        </button>
        <button class="pwa-tab" data-tab="places">
            <span class="tab-icon">📍</span>
            <span class="tab-label">장소</span>
            <span class="tab-badge" id="places-badge" style="display: none;">0</span>
        </button>
        <button class="pwa-tab" data-tab="tour">
            <span class="tab-icon">📋</span>
            <span class="tab-label">내 투어</span>
            <span class="tab-badge" id="tour-badge" style="display: none;">0</span>
        </button>
    </nav>
</div>

<!-- 공유 모달 -->
<div class="pwa-modal" id="share-modal">
    <div class="modal-overlay" onclick="closeModal('share-modal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>투어 공유하기</h3>
            <button class="modal-close" onclick="closeModal('share-modal')">×</button>
        </div>
        <div class="modal-body">
            <p>친구들과 투어를 공유해보세요!</p>
            <div class="share-options">
                <button class="share-option" onclick="shareWithWebAPI()">
                    <span class="icon">📱</span>
                    <span>공유하기</span>
                </button>
                <button class="share-option" onclick="copyShareUrl()">
                    <span class="icon">📋</span>
                    <span>링크 복사</span>
                </button>
                <button class="share-option kakao" onclick="shareToKakao()">
                    <span class="icon">💬</span>
                    <span>카카오톡</span>
                </button>
            </div>
            <input type="hidden" id="share-url" value="">
        </div>
    </div>
</div>

<!-- PWA 메뉴 모달 -->
<div class="pwa-modal" id="menu-modal">
    <div class="modal-overlay" onclick="closeModal('menu-modal')"></div>
    <div class="modal-content modal-menu">
        <div class="modal-header">
            <h3>메뉴</h3>
            <button class="modal-close" onclick="closeModal('menu-modal')">×</button>
        </div>
        <div class="modal-body">
            <button class="menu-item" onclick="saveTourWithName()">
                <span class="icon">💾</span>
                <span>투어 저장</span>
            </button>
            <button class="menu-item" onclick="showSavedTours()">
                <span class="icon">📂</span>
                <span>저장된 투어</span>
            </button>
            <button class="menu-item" onclick="resetTour()">
                <span class="icon">🔄</span>
                <span>초기화</span>
            </button>
            <button class="menu-item" onclick="goToHome()">
                <span class="icon">🏠</span>
                <span>홈으로</span>
            </button>
        </div>
    </div>
</div>

<!-- 토스트 컨테이너 -->
<div id="toast-container"></div>

<!-- 스크립트 설정 -->
<script>
// WordPress API 설정
window.tourPWAConfig = {
    apiBase: '<?php echo esc_url(home_url('/wp-json')); ?>',
    homeUrl: '<?php echo esc_url(home_url()); ?>',
    nonce: '<?php echo wp_create_nonce('wp_rest'); ?>',
    naverClientId: '<?php echo esc_js($naver_client_id); ?>',
    isPWA: true
};
</script>

<!-- 필요한 스크립트들 -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://developers.kakao.com/sdk/js/kakao.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/assets/js/tour-planner-pwa.js?ver=<?php echo time(); ?>"></script>

<?php wp_footer(); ?>

</body>
</html>
