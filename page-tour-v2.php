<?php
/**
 * Template Name: 투어플래너
 * 
 * 깔끔한 MVP 버전의 투어플래너
 * 복잡한 기능 제거, 핵심 기능만 구현
 * 
 * @since 2025-06-29
 */

// 모바일 디바이스 감지
$is_mobile = wp_is_mobile();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// 스마트폰 감지 (태블릿 제외)
$is_smartphone = false;
if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $user_agent)) {
    $is_smartphone = true;
}

// 화면 크기가 작은 모바일 디바이스는 PWA 버전으로 리다이렉트
if ($is_smartphone && !isset($_GET['desktop'])) {
    // PWA 페이지 URL 확인
    $pwa_page = get_page_by_path('tour-pwa');
    if ($pwa_page) {
        wp_redirect(get_permalink($pwa_page));
        exit;
    }
}

get_header(); ?>

<div class="tour-v2-container">
    <!-- 상단 헤더 -->
    <header class="tour-v2-header">
        <div class="header-content">
            <h1>🗺️ 성수동 투어 만들기</h1>
            <p>원하는 장소를 선택해서 나만의 투어 코스를 만들어보세요</p>
        </div>
    </header>

    <!-- PWA 설치 가이드는 footer.php에 포함됨 -->

    <!-- 메인 레이아웃 -->
    <main class="tour-v2-main">
        <!-- 모바일 탭 네비게이션 -->
        <div class="mobile-tabs">
            <button class="mobile-tab active" data-tab="map">
                <span>🗺️</span>
                <span>지도</span>
            </button>
            <button class="mobile-tab" data-tab="places">
                <span>📍</span>
                <span>장소</span>
            </button>
            <button class="mobile-tab" data-tab="tour">
                <span>📋</span>
                <span>내 투어</span>
            </button>
        </div>

        <!-- 왼쪽: 장소 목록 -->
        <aside class="places-panel mobile-panel" data-panel="places">
            <div class="panel-header">
                <h2>📍 장소 선택</h2>
                <div class="place-filters">
                    <input type="text" 
                           id="place-search" 
                           placeholder="장소 검색..." 
                           class="search-input">
                    <div class="filter-buttons" id="filter-buttons">
                        <button class="filter-btn active" data-type="all">전체</button>
                        <!-- 동적으로 추가됨 -->
                    </div>
                </div>
            </div>
            <div class="places-list" id="places-list">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>장소를 불러오는 중...</p>
                </div>
            </div>
        </aside>

        <!-- 중앙: 지도 -->
        <section class="map-panel mobile-panel active" data-panel="map">
            <div id="tour-map" class="tour-map">
                <div class="map-loading">
                    <div class="spinner"></div>
                    <p>지도를 준비하는 중...</p>
                </div>
            </div>
        </section>

        <!-- 오른쪽: 선택된 장소 & 요약 -->
        <aside class="tour-panel mobile-panel" data-panel="tour">
            <!-- 선택된 장소 -->
            <div class="selected-places">
                <div class="panel-header">
                    <h2>✅ 선택한 장소</h2>
                    <span class="place-count-text"><span id="selected-count">0</span>개 장소가 선택되었습니다</span>
                </div>
                <div class="selected-list" id="selected-list">
                    <div class="empty-state">
                        <span class="empty-icon">📝</span>
                        <p>아직 선택한 장소가 없습니다</p>
                        <small>왼쪽 목록에서 장소를 클릭해주세요</small>
                    </div>
                </div>
            </div>

            <!-- 투어 요약 -->
            <div class="tour-summary">
                <h3>📊 투어 요약</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="value" id="total-places">0</span>
                        <span class="label">장소</span>
                    </div>
                    <div class="summary-item">
                        <span class="value" id="total-time">0분</span>
                        <span class="label">예상시간</span>
                    </div>
                    <div class="summary-item">
                        <span class="value" id="total-distance">0km</span>
                        <span class="label">이동거리</span>
                    </div>
                </div>
            </div>

            <!-- 액션 버튼 -->
            <div class="tour-actions">
                <button class="btn btn-primary" id="share-btn" disabled>
                    <span class="icon">🔗</span>
                    공유하기
                </button>
                <button class="btn btn-secondary" id="optimize-btn" disabled>
                    <span class="icon">✨</span>
                    경로 최적화
                </button>
                <button class="btn btn-secondary" id="reset-btn" disabled>
                    <span class="icon">🔄</span>
                    초기화
                </button>
            </div>
            
            <!-- 저장된 투어 -->
            <div class="saved-tours">
                <div class="saved-tours-header">
                    <h4>💾 저장된 투어</h4>
                    <button class="btn btn-sm" id="manage-tours-btn">관리</button>
                </div>
                <div class="saved-tours-list" id="saved-tours-list">
                    <!-- 동적으로 추가됨 -->
                </div>
                <div class="saved-tours-login-prompt" id="saved-tours-login-prompt" style="display: none;">
                    <p style="text-align: center; color: var(--text-secondary); font-size: 0.813rem; margin: 0.5rem 0;">
                        투어를 저장하려면 로그인이 필요합니다
                    </p>
                    <button class="btn btn-sm btn-primary" style="width: 100%;" onclick="window.sungsuyaAuth.showAuthModal()">
                        로그인하기
                    </button>
                </div>
            </div>
        </aside>
    </main>
</div>

<!-- 모바일 하단 고정 버튼 -->
<div class="mobile-bottom-actions" style="display: none;">
    <button class="btn btn-primary" id="mobile-share-btn">
        <span class="icon">🔗</span>
        공유하기
    </button>
    <button class="btn btn-secondary" id="mobile-reset-btn">
        <span class="icon">🔄</span>
        초기화
    </button>
</div>

<!-- 공유 모달 -->
<div class="share-modal" id="share-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>🔗 투어 공유하기</h3>
            <button class="close-btn" onclick="closeShareModal()">✕</button>
        </div>
        <div class="modal-body">
            <p>아래 링크를 복사해서 공유하세요:</p>
            <div class="share-url-box">
                <input type="text" id="share-url" readonly>
                <button onclick="copyShareUrl()" class="copy-btn">복사</button>
            </div>
            <div class="sns-share">
                <button class="sns-btn kakao" onclick="shareToKakao()">
                    <span>💬</span>
                </button>
                <button class="sns-btn" onclick="shareToInstagram()">
                    <span>📷</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 저장된 투어 관리 모달 -->
<div class="saved-tours-modal" id="saved-tours-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>💾 저장된 투어 관리</h3>
            <button class="close-btn" onclick="closeSavedToursModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="saved-tours-manager" id="saved-tours-manager">
                <!-- 동적으로 추가됨 -->
            </div>
            <button class="btn btn-primary" onclick="tourPlanner.saveTourWithName()">
                <span class="icon">➕</span>
                현재 투어 저장
            </button>
        </div>
    </div>
</div>

<!-- 스타일시트 -->
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/tour-planner-v2.css?ver=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/auth-system.css?ver=<?php echo time(); ?>">

<!-- 네이버 지도 API -->
<?php
$naver_client_id = get_option('sungsuya_naver_client_id');
$naver_client_secret = get_option('sungsuya_naver_client_secret');
if (!$naver_client_id) {
    $naver_client_id = get_option('naver_maps_client_id');
}
if (!$naver_client_secret) {
    $naver_client_secret = get_option('naver_maps_client_secret');
}

// 디버깅: 실제 API 키 확인
error_log('[투어플래너 V2] 네이버 API Client ID: ' . $naver_client_id);
error_log('[투어플래너 V2] Client ID from DB (sungsuya_naver_client_id): ' . get_option('sungsuya_naver_client_id'));
error_log('[투어플래너 V2] Client ID from DB (naver_maps_client_id): ' . get_option('naver_maps_client_id'));

// qosb7em5i9가 맞는지 확인
if ($naver_client_id === 'qosb7em5i9') {
    error_log('[투어플래너 V2] Client ID가 올바르게 설정됨: qosb7em5i9');
} else {
    error_log('[투어플래너 V2] Client ID가 다름: ' . $naver_client_id);
}

if ($naver_client_id) : ?>
<script type="text/javascript">
// 네이버 지도 API 키를 전역 변수로 설정
window._NAVER_CLIENT_ID = '<?php echo esc_js($naver_client_id); ?>';
window._NAVER_CLIENT_SECRET = '<?php echo esc_js($naver_client_secret); ?>';
console.log('[투어플래너 V2] Client ID:', window._NAVER_CLIENT_ID);
</script>
<!-- 먼저 submodules 포함하여 로드 -->
<script type="text/javascript" src="https://oapi.map.naver.com/openapi/v3/maps.js?ncpKeyId=<?php echo esc_attr($naver_client_id); ?>&submodules=geocoder"></script>
<script type="text/javascript">
// API 로드 후 이벤트 발생
window.addEventListener('load', function() {
    if (typeof naver !== 'undefined' && naver.maps) {
        console.log('✅ 네이버 지도 API 로드 완료 (window.load)');
        window.dispatchEvent(new Event('naverMapReady'));
    }
});
</script>
<?php else: ?>
<script>
console.error('[투어플래너 V2] 네이버 API 키가 설정되지 않았습니다.');
</script>
<?php endif; ?>

<!-- PWA manifest -->
<link rel="manifest" href="<?php echo esc_url(home_url('/manifest.json')); ?>">

<!-- 스크립트 -->
<script>
// WordPress API 설정
window.tourV2Config = {
    apiBase: '<?php echo esc_url(home_url('/wp-json')); ?>',
    homeUrl: '<?php echo esc_url(home_url()); ?>',
    nonce: '<?php echo wp_create_nonce('wp_rest'); ?>',
    mapClientId: '<?php echo esc_js($naver_client_id); ?>' // 위에서 가져온 값 사용
};
</script>

<!-- Sortable.js (드래그 앤 드롭) -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<!-- Kakao SDK -->
<script src="https://developers.kakao.com/sdk/js/kakao.js"></script>

<!-- 인증 시스템 -->
<script src="<?php echo get_template_directory_uri(); ?>/assets/js/auth-system.js?ver=<?php echo time(); ?>"></script>

<!-- 메인 스크립트 -->
<script src="<?php echo get_template_directory_uri(); ?>/assets/js/tour-planner-v2.js?ver=<?php echo time(); ?>"></script>

<?php get_footer(); ?>
