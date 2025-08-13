<?php
/**
 * 팝업스토어 지도 섹션
 * 네이버 지도 API를 사용하여 위치 표시
 */

$store_id = get_the_ID();

// 위치 정보 가져오기 (다양한 키 형태로 시도)
$latitude = get_post_meta($store_id, 'latitude', true) ?: 
           get_post_meta($store_id, '_store_latitude', true) ?: 
           get_post_meta($store_id, '_latitude', true);
           
$longitude = get_post_meta($store_id, 'longitude', true) ?: 
            get_post_meta($store_id, '_store_longitude', true) ?: 
            get_post_meta($store_id, '_longitude', true);
            
$address = get_post_meta($store_id, 'address', true) ?: 
          get_post_meta($store_id, '_store_address', true) ?: 
          get_post_meta($store_id, '_address', true);
          
$address_detail = get_post_meta($store_id, 'address_detail', true) ?: 
                 get_post_meta($store_id, '_store_address_detail', true) ?: 
                 get_post_meta($store_id, '_address_detail', true);

// 네이버 API 클라이언트 ID (설정에서 가져오거나 기본값 사용)
$naver_client_id = get_option('sungsuya_naver_client_id', 'jsua6vun65');
?>

<div class="container">
    <div class="section-header">
        <h2 class="section-title">
            <span class="section-icon">📍</span>
            위치 정보
        </h2>
    </div>
    
    <?php if ($latitude && $longitude) : ?>
    
    <div class="location-content">
        <!-- 주소 정보 -->
        <div class="address-info">
            <div class="address-main">
                <span class="address-icon">📍</span>
                <div class="address-text-container">
                    <span class="address-text"><?php echo esc_html($address ?: '주소 정보 없음'); ?></span>
                    <?php if ($address_detail) : ?>
                        <span class="address-detail"><?php echo esc_html($address_detail); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="address-actions">
                <button type="button" class="copy-address-btn" data-address="<?php echo esc_attr(($address ?: '') . ($address_detail ? ' ' . $address_detail : '')); ?>">
                    📋 주소복사
                </button>
                <a href="https://map.naver.com/p/search/<?php echo urlencode($address ?: ''); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="naver-map-link">
                    🗺️ 네이버지도
                </a>
                <a href="https://map.naver.com/p/directions/-/-/-/car?c=<?php echo esc_attr($longitude); ?>,<?php echo esc_attr($latitude); ?>,15,0,0,0,dh" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="directions-link">
                    🚗 길찾기
                </a>
            </div>
        </div>

        <!-- 지도 컨테이너 -->
        <div class="map-container">
            <div id="store-map" 
                 class="store-map"
                 data-lat="<?php echo esc_attr($latitude); ?>"
                 data-lng="<?php echo esc_attr($longitude); ?>"
                 data-title="<?php echo esc_attr(get_the_title()); ?>"
                 data-address="<?php echo esc_attr($address ?: ''); ?>">
                <!-- 지도 로딩 표시 -->
                <div class="map-loading">
                    <div class="loading-spinner"></div>
                    <p>지도를 불러오는 중...</p>
                </div>
            </div>
            
            <!-- 지도 오류 시 대체 표시 -->
            <div class="map-error" style="display: none;">
                <div class="map-error-icon">🗺️</div>
                <h3>지도를 불러올 수 없습니다</h3>
                <p>네트워크 연결을 확인하거나 잠시 후 다시 시도해주세요.</p>
                <button type="button" class="retry-map-btn" onclick="location.reload()">다시 시도</button>
            </div>
        </div>

        <!-- 지도 컨트롤 -->
        <div class="map-controls">
            <button type="button" class="map-control-btn" id="map-zoom-in" title="확대">🔍+</button>
            <button type="button" class="map-control-btn" id="map-zoom-out" title="축소">🔍-</button>
            <button type="button" class="map-control-btn" id="map-center" title="중심으로">🎯</button>
            <button type="button" class="map-control-btn" id="map-fullscreen" title="전체화면">📱</button>
        </div>
    </div>

    <?php else : ?>
    
    <!-- 위치 정보가 없는 경우 -->
    <div class="no-location-info">
        <div class="no-location-icon">📍</div>
        <h3>위치 정보가 등록되지 않았습니다</h3>
        <p>정확한 위치 정보는 관리자를 통해 업데이트될 예정입니다.</p>
        
        <?php if ($address) : ?>
        <div class="address-only">
            <strong>등록된 주소:</strong>
            <span><?php echo esc_html($address); ?></span>
            <?php if ($address_detail) : ?>
                <span class="address-detail"><?php echo esc_html($address_detail); ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php endif; ?>
</div>

<!-- 네이버 지도 API 스크립트 -->
<?php if ($latitude && $longitude) : ?>
<script type="text/javascript" src="https://openapi.map.naver.com/openapi/v3/maps.js?ncpClientId=<?php echo esc_attr($naver_client_id); ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let map = null;
    let marker = null;
    let infoWindow = null;
    
    // 지도 초기화 함수
    function initializeMap() {
        try {
            const mapElement = document.getElementById('store-map');
            if (!mapElement) return;
            
            const lat = parseFloat(mapElement.dataset.lat);
            const lng = parseFloat(mapElement.dataset.lng);
            const storeTitle = mapElement.dataset.title;
            const storeAddress = mapElement.dataset.address;
            
            // 지도 옵션
            const mapOptions = {
                center: new naver.maps.LatLng(lat, lng),
                zoom: 16,
                mapTypeControl: false,
                mapDataControl: false,
                logoControl: false,
                scaleControl: true,
                zoomControl: false, // 커스텀 컨트롤 사용
                mapTypeId: naver.maps.MapTypeId.NORMAL
            };
            
            // 지도 생성
            map = new naver.maps.Map('store-map', mapOptions);
            
            // 커스텀 마커 아이콘
            const markerIcon = {
                content: `
                    <div class="custom-marker">
                        <div class="marker-pin">📍</div>
                        <div class="marker-pulse"></div>
                    </div>
                `,
                anchor: new naver.maps.Point(20, 40)
            };
            
            // 마커 생성
            marker = new naver.maps.Marker({
                position: new naver.maps.LatLng(lat, lng),
                map: map,
                title: storeTitle,
                icon: markerIcon
            });
            
            // 정보창 생성
            infoWindow = new naver.maps.InfoWindow({
                content: `
                    <div class="info-window">
                        <h4 class="info-title">${storeTitle}</h4>
                        <p class="info-address">${storeAddress}</p>
                        <div class="info-window-actions">
                            <a href="https://map.naver.com/p/directions/-/-/-/car?c=${lng},${lat},15,0,0,0,dh" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="directions-link">길찾기</a>
                        </div>
                    </div>
                `,
                anchorSkew: true,
                borderWidth: 0,
                disableAnchor: false
            });
            
            // 마커 클릭 시 정보창 표시/숨김
            naver.maps.Event.addListener(marker, 'click', function() {
                if (infoWindow.getMap()) {
                    infoWindow.close();
                } else {
                    infoWindow.open(map, marker);
                }
            });
            
            // 지도 클릭 시 정보창 닫기
            naver.maps.Event.addListener(map, 'click', function() {
                infoWindow.close();
            });
            
            // 지도 로딩 완료 처리
            naver.maps.Event.addListener(map, 'idle', function() {
                hideMapLoading();
            });
            
            // 지도 컨트롤 이벤트 바인딩
            bindMapControls();
            
            console.log('✅ 지도 초기화 완료');
            
        } catch (error) {
            console.error('❌ 지도 초기화 오류:', error);
            showMapError();
        }
    }
    
    // 지도 컨트롤 이벤트 바인딩
    function bindMapControls() {
        const zoomInBtn = document.getElementById('map-zoom-in');
        const zoomOutBtn = document.getElementById('map-zoom-out');
        const centerBtn = document.getElementById('map-center');
        const fullscreenBtn = document.getElementById('map-fullscreen');
        
        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', function() {
                if (map) map.setZoom(map.getZoom() + 1);
            });
        }
        
        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', function() {
                if (map) map.setZoom(map.getZoom() - 1);
            });
        }
        
        if (centerBtn) {
            centerBtn.addEventListener('click', function() {
                if (map && marker) {
                    map.setCenter(marker.getPosition());
                    map.setZoom(16);
                }
            });
        }
        
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', function() {
                const mapContainer = document.querySelector('.map-container');
                if (mapContainer) {
                    mapContainer.classList.toggle('fullscreen');
                    setTimeout(() => {
                        if (map) map.refresh();
                    }, 300);
                }
            });
        }
    }
    
    // 지도 로딩 표시 숨기기
    function hideMapLoading() {
        const loadingElement = document.querySelector('.map-loading');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }
    
    // 지도 오류 표시
    function showMapError() {
        hideMapLoading();
        const errorElement = document.querySelector('.map-error');
        if (errorElement) {
            errorElement.style.display = 'flex';
        }
    }
    
    // 네이버 지도 API 로드 확인 및 초기화
    function checkNaverMapsAPI() {
        let attempts = 0;
        const maxAttempts = 50; // 5초 대기
        
        function check() {
            attempts++;
            
            if (typeof naver !== 'undefined' && 
                typeof naver.maps !== 'undefined' && 
                typeof naver.maps.Map !== 'undefined') {
                console.log('✅ 네이버 지도 API 로드 완료');
                initializeMap();
                return;
            }
            
            if (attempts < maxAttempts) {
                console.log(`⏳ 네이버 지도 API 로드 대기 중... (${attempts}/${maxAttempts})`);
                setTimeout(check, 100);
            } else {
                console.error('❌ 네이버 지도 API 로드 시간 초과');
                showMapError();
            }
        }
        
        check();
    }
    
    // API 로드 확인 시작
    checkNaverMapsAPI();
});

// 주소 복사 기능
document.addEventListener('DOMContentLoaded', function() {
    const copyAddressBtn = document.querySelector('.copy-address-btn');
    if (copyAddressBtn) {
        copyAddressBtn.addEventListener('click', function() {
            const address = this.dataset.address;
            
            if (!address || address.trim() === '') {
                showToast('복사할 주소가 없습니다', 'warning');
                return;
            }
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(address).then(function() {
                    showToast('주소가 복사되었습니다', 'success');
                }).catch(function() {
                    fallbackCopyText(address);
                });
            } else {
                fallbackCopyText(address);
            }
        });
    }
    
    // 구형 브라우저 지원 복사 기능
    function fallbackCopyText(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showToast('주소가 복사되었습니다', 'success');
        } catch (err) {
            showToast('복사에 실패했습니다', 'error');
        }
        
        document.body.removeChild(textArea);
    }
    
    // 토스트 메시지 표시
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `copy-toast copy-toast--${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${type === 'success' ? '✅' : type === 'warning' ? '⚠️' : '❌'}</span>
            <span class="toast-message">${message}</span>
        `;
        document.body.appendChild(toast);
        
        // 애니메이션
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, 2500);
    }
});
</script>
<?php endif; ?>
