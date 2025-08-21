/**
 * 성수야! V2 - Places 메타박스 JavaScript (수정 완료)
 * 
 * 성공한 팝업스토어 방식을 동일하게 적용
 * 
 * @package SungsuyaV2
 * @version 2.2.0 (Fixed)
 */

(function($) {
    'use strict';

    // 전역 변수
    let naverMap = null;
    let naverGeocoder = null;
    let currentMarker = null;
    let isMapReady = false;
    let isGeocoderReady = false;

    // DOM 로드 완료 시 초기화
    $(document).ready(function() {
        console.log('🚀 Places 메타박스 JavaScript 로드됨');
        
        initPlaceTypeSelector();
        initAddressSearch();
        initMapContainer();
        
        // 성공한 팝업스토어 방식과 동일한 콜백 대기
        console.log('⏳ 네이버 API 콜백 대기 중...');
    });

    /**
     * 네이버 지도 API 통합 콜백 (수정된 버전)
     */
    window.naverMapsPlacesUnifiedReady = function() {
        console.log('✅ Places 통합 API 콜백 실행 - 지도 + 지오코더 모두 준비완료');
        
        // 지도와 지오코더 모두 준비 완료
        isMapReady = true;
        isGeocoderReady = true;
        
        // 지오코더 초기화 (수정된 방식)
        if (typeof naver !== 'undefined' && naver.maps && naver.maps.Service && naver.maps.Service.geocode) {
            try {
                // 지오코더 초기화를 자리 정리 최소화
                naverGeocoder = {
                    geocode: function(options, callback) {
                        if (naver.maps.Service && naver.maps.Service.geocode) {
                            naver.maps.Service.geocode(options, callback);
                        } else {
                            console.error('❌ 지오코더 서비스를 사용할 수 없습니다');
                            callback(naver.maps.Service.Status.ERROR, null);
                        }
                    }
                };
                console.log('✅ 네이버 지오코더 래퍼 초기화 성공');
            } catch (error) {
                console.error('❌ 지오코더 초기화 실패:', error);
                naverGeocoder = null;
            }
        } else {
            console.error('❌ 네이버 지오코더 서비스를 사용할 수 없습니다');
            naverGeocoder = null;
        }
        
        // 지도 초기화 시작
        checkAndInitializeMap();
    };

    /**
     * 지도와 지오코더 모두 준비되면 초기화
     */
    function checkAndInitializeMap() {
        if (isMapReady && isGeocoderReady) {
            console.log('🗺️ 통합 API 준비 완료 - 지도 초기화 시작');
            initNaverMap();
        } else {
            console.log('⏳ API 준비 대기 중 - Map:', isMapReady, 'Geocoder:', isGeocoderReady);
        }
    }

    /**
     * 장소 유형 선택기 초기화
     */
    function initPlaceTypeSelector() {
        $('.place-type-btn').on('click', function() {
            const selectedType = $(this).data('type');
            
            // 활성 상태 토글
            $('.place-type-btn').removeClass('active');
            $(this).addClass('active');
            
            // 숨겨진 필드 업데이트
            $('#selected_place_type').val(selectedType);
            
            // 동적 필드 표시/숨김
            showDynamicFields(selectedType);
            
            console.log('📋 장소 유형 선택:', selectedType);
        });
    }

    /**
     * 동적 필드 표시/숨김
     */
    function showDynamicFields(selectedType) {
        $('.dynamic-field-group').hide();
        $(`.dynamic-field-group[data-type="${selectedType}"]`).show();
    }

    /**
     * 주소 검색 초기화
     */
    function initAddressSearch() {
        $('#places-address-search-btn').on('click', function() {
            const address = $('#places_field_address').val().trim();
            
            if (!address) {
                showMessage('주소를 입력해주세요.', 'error');
                return;
            }
            
            searchAddress(address);
        });
        
        // Enter 키 검색
        $('#places_field_address').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#places-address-search-btn').click();
            }
        });
    }

    /**
     * 주소 검색 실행 (팝업스토어와 동일한 방식)
     */
    function searchAddress(address) {
        console.log('🔍 주소 검색 시작:', address);
        
        // 로딩 상태
        $('#places-address-search-btn').text('검색중...').prop('disabled', true);
        showMessage('주소를 검색하는 중...', 'info');
        
        // 1차: 클라이언트 사이드 지오코딩 (수정된 방식)
        if (naverGeocoder && typeof naver !== 'undefined' && naver.maps && naver.maps.Service) {
            console.log('🌐 클라이언트 사이드 지오코딩 시도');
            
            try {
                naverGeocoder.geocode({
                    query: address
                }, function(status, response) {
                    console.log('📍 지오코딩 응답:', status, response);
                    
                    if (status === naver.maps.Service.Status.OK && response.v2 && response.v2.addresses && response.v2.addresses.length > 0) {
                        const result = response.v2.addresses[0];
                        const lat = parseFloat(result.y);
                        const lng = parseFloat(result.x);
                        
                        if (!isNaN(lat) && !isNaN(lng)) {
                            console.log('✅ 클라이언트 지오코딩 성공:', lat, lng);
                            
                            // 좌표 필드 업데이트
                            $('#places_field_latitude').val(lat);
                            $('#places_field_longitude').val(lng);
                            
                            // 지도 업데이트
                            updateMapLocation(lat, lng, result.roadAddress || result.jibunAddress);
                            
                            // 지하철역 정보 가져오기
                            getSubwayInfo(lat, lng);
                            
                            showMessage('주소가 확인되었습니다!', 'success');
                        } else {
                            console.log('⚠️ 좌표 데이터 오류 - 서버사이드로 백업');
                            searchAddressServerSide(address);
                        }
                    } else {
                        console.log('⚠️ 클라이언트 지오코딩 실패 - 서버사이드로 백업');
                        searchAddressServerSide(address);
                    }
                    
                    $('#places-address-search-btn').text('🔍 주소검색').prop('disabled', false);
                });
            } catch (error) {
                console.error('❌ 지오코딩 예외 발생:', error);
                console.log('⚠️ 예외로 인한 서버사이드 백업');
                searchAddressServerSide(address);
                $('#places-address-search-btn').text('🔍 주소검색').prop('disabled', false);
            }
        } else {
            console.log('⚠️ 지오코더 없음 또는 API 미준비 - 서버사이드로 백업');
            searchAddressServerSide(address);
        }
    }

    /**
     * 서버사이드 주소 검색 (백업)
     */
    function searchAddressServerSide(address) {
        console.log('🔄 서버사이드 지오코딩 시도');
        
        $.ajax({
            url: placesMetabox.ajaxUrl,
            type: 'POST',
            data: {
                action: 'places_search_address',
                nonce: placesMetabox.nonce,
                query: address
            },
            success: function(response) {
                console.log('📡 서버 응답:', response);
                
                if (response.success && response.data.length > 0) {
                    const result = response.data[0];
                    
                    // 응답 데이터 구조 로깅
                    console.log('📊 서버 응답 데이터 구조:', result);
                    
                    // 좌표 추출 (다양한 응답 형식 지원)
                    let lat, lng;
                    if (result.latitude && result.longitude) {
                        lat = parseFloat(result.latitude);
                        lng = parseFloat(result.longitude);
                    } else if (result.y && result.x) {
                        lat = parseFloat(result.y);
                        lng = parseFloat(result.x);
                    } else if (result.lat && result.lng) {
                        lat = parseFloat(result.lat);
                        lng = parseFloat(result.lng);
                    } else {
                        console.error('❌ 좌표 정보가 응답에 없음:', result);
                        showMessage('좌표 정보를 찾을 수 없습니다.', 'error');
                        return;
                    }
                    
                    console.log('✅ 서버 지오코딩 성공:', lat, lng);
                    
                    // 주소 추출
                    const resultAddress = result.roadAddress || result.jibunAddress || result.address || address;
                    
                    // 좌표 필드 업데이트
                    $('#places_field_latitude').val(lat);
                    $('#places_field_longitude').val(lng);
                    
                    // 지도 업데이트
                    updateMapLocation(lat, lng, resultAddress);
                    
                    // 지하철역 정보 가져오기
                    getSubwayInfo(lat, lng);
                    
                    showMessage('주소가 확인되었습니다!', 'success');
                } else {
                    showMessage('주소를 찾을 수 없습니다. 정확한 주소를 입력해주세요.', 'error');
                    console.log('❌ 서버 지오코딩 실패:', response);
                }
            },
            error: function(xhr, status, error) {
                showMessage('주소 검색 중 오류가 발생했습니다.', 'error');
                console.error('❌ 서버 AJAX 오류:', error);
            },
            complete: function() {
                $('#places-address-search-btn').text('🔍 주소검색').prop('disabled', false);
            }
        });
    }

    /**
     * 지하철역 정보 가져오기
     */
    function getSubwayInfo(lat, lng) {
        $.ajax({
            url: placesMetabox.ajaxUrl,
            type: 'POST',
            data: {
                action: 'places_get_subway_info',
                nonce: placesMetabox.nonce,
                latitude: lat,
                longitude: lng
            },
            success: function(response) {
                if (response.success) {
                    const subway = response.data;
                    
                    // 필드 업데이트
                    $('#places_field_nearest_subway').val(subway.station_name);
                    $('#places_field_subway_distance').val(subway.walking_time + '분');
                    
                    // 지도 정보 패널 업데이트
                    updateMapInfoPanel(lat, lng, subway);
                    
                    console.log('🚇 지하철 정보:', subway);
                } else {
                    console.log('⚠️ 지하철 정보 없음');
                }
            },
            error: function() {
                console.log('❌ 지하철 정보 조회 실패');
            }
        });
    }

    /**
     * 지도 컨테이너 초기화
     */
    function initMapContainer() {
        console.log('🗺️ 지도 컸테이너 초기화 시작');
        
        const mapContainer = document.getElementById('places-map-preview');
        if (mapContainer) {
            mapContainer.classList.add('map-loading');
            console.log('✅ 지도 컸테이너 요소 발견:', mapContainer);
            console.log('📜 컸테이너 스타일:', {
                width: mapContainer.offsetWidth,
                height: mapContainer.offsetHeight,
                display: window.getComputedStyle(mapContainer).display,
                visibility: window.getComputedStyle(mapContainer).visibility
            });
        } else {
            console.error('❌ 지도 컸테이너 요소를 찾을 수 없음: places-map-preview');
        }
        
        $(window).on('resize', function() {
            if (naverMap) {
                console.log('🔄 창 크기 변경 - 지도 새로고침');
                naverMap.refresh();
            }
        });
    }

    /**
     * 네이버 지도 초기화 (팝업스토어와 동일한 방식)
     */
    function initNaverMap() {
        if (!placesMetabox.naverMapsConfigured) {
            console.log('⚠️ 네이버 지도 API 설정되지 않음 - 플레이스홀더 표시');
            $('#map-placeholder').html('📍 네이버 지도 API 키가<br>설정되지 않았습니다.<br><br>WordPress 관리자 > 설정 > 네이버 지도에서<br>API 키를 설정해주세요.');
            return;
        }

        try {
            const mapContainer = document.getElementById('places-map-preview');
            if (!mapContainer) {
                console.error('❌ 지도 컨테이너 요소를 찾을 수 없음: places-map-preview');
                $('#map-placeholder').html('❌ 지도 컨테이너를 찾을 수 없습니다');
                return;
            }
            
            // 컨테이너 상태 디버깅
            console.log('📜 지도 컨테이너 확인:', {
                element: mapContainer,
                dimensions: {
                    offsetWidth: mapContainer.offsetWidth,
                    offsetHeight: mapContainer.offsetHeight,
                    clientWidth: mapContainer.clientWidth,
                    clientHeight: mapContainer.clientHeight
                },
                styles: {
                    display: window.getComputedStyle(mapContainer).display,
                    visibility: window.getComputedStyle(mapContainer).visibility,
                    position: window.getComputedStyle(mapContainer).position,
                    zIndex: window.getComputedStyle(mapContainer).zIndex
                },
                parentElement: mapContainer.parentElement,
                classList: Array.from(mapContainer.classList)
            });
            
            // 컨테이너 준비 상태 표시
            mapContainer.classList.remove('map-loading');
            mapContainer.classList.add('map-ready');

            // 기본 좌표 (성수동)
            const defaultLatLng = new naver.maps.LatLng(37.5445, 127.0557);
            console.log('📍 기본 좌표 설정:', defaultLatLng);

            naverMap = new naver.maps.Map(mapContainer, {
                center: defaultLatLng,
                zoom: 15,
                minZoom: 10,
                maxZoom: 19,
                mapTypeControl: false,
                logoControl: false,
                scaleControl: false,
                mapDataControl: false
            });
            
            console.log('✅ 네이버 지도 객체 생성 완료:', naverMap);

            // 마커 생성
            currentMarker = new naver.maps.Marker({
                position: defaultLatLng,
                map: naverMap,
                title: '선택된 위치'
            });
            
            console.log('✅ 마커 생성 완료:', currentMarker);

            // 지도 컨테이너 표시 상태 업데이트
            $('#map-placeholder').hide();
            $(mapContainer).show().css({
                'display': 'block',
                'visibility': 'visible',
                'opacity': '1'
            });
            
            console.log('🗺️ 지도 컨테이너 표시 완료');
            
            // 기존 좌표가 있으면 표시
            const existingLat = parseFloat($('#places_field_latitude').val());
            const existingLng = parseFloat($('#places_field_longitude').val());
            
            if (existingLat && existingLng) {
                console.log('🔄 기존 좌표로 지도 업데이트:', existingLat, existingLng);
                setTimeout(() => {
                    updateMapLocation(existingLat, existingLng, $('#places_field_address').val());
                }, 500);
            }
            
            console.log('✅ 네이버 지도 초기화 완료');
        } catch (error) {
            console.error('❌ 네이버 지도 초기화 실패:', error);
            console.error('❌ 오류 상세정보:', {
                message: error.message,
                stack: error.stack,
                name: error.name
            });
            
            const mapContainer = document.getElementById('places-map-preview');
            if (mapContainer) {
                mapContainer.classList.remove('map-loading', 'map-ready');
                mapContainer.classList.add('map-error');
            }
            
            $('#map-placeholder').html('❌ 지도 초기화 실패<br>콘솔을 확인해주세요<br><small>' + error.message + '</small>');
        }
    }

    /**
     * 지도 위치 업데이트 (개선된 버전)
     */
    function updateMapLocation(lat, lng, address) {
        if (!naverMap || !currentMarker) {
            console.log('⚠️ 지도 또는 마커가 초기화되지 않음');
            return;
        }

        console.log('🗺️ 지도 위치 업데이트 시작:', lat, lng, address);
        
        // 좌표를 확실히 숫자로 변환
        const latitude = parseFloat(lat);
        const longitude = parseFloat(lng);
        
        if (isNaN(latitude) || isNaN(longitude)) {
            console.error('❌ 유효하지 않은 좌표:', lat, lng);
            return;
        }
        
        // 네이버 지도 LatLng 객체 생성
        const newPosition = new naver.maps.LatLng(latitude, longitude);
        
        // 지도 중심 이동
        naverMap.setCenter(newPosition);
        naverMap.setZoom(17);
        
        // 마커 위치 업데이트
        currentMarker.setPosition(newPosition);
        currentMarker.setTitle(address || '선택된 위치');
        
        // 지도 새로고침
        setTimeout(() => {
            naverMap.refresh();
            naverMap.setCenter(newPosition);
            console.log('✅ 지도 위치 업데이트 완료');
        }, 100);
    }

    /**
     * 지도 정보 패널 업데이트
     */
    function updateMapInfoPanel(lat, lng, subway) {
        $('#coordinate-display').text(`${lat.toFixed(6)}, ${lng.toFixed(6)}`);
        
        if (subway) {
            $('#subway-display').text(`${subway.station_name} (${subway.line})`);
            $('#walking-time-display').text(`${subway.walking_time}분`);
            $('#subway-info').show();
        }
        
        $('#map-info-panel').show();
    }

    /**
     * 메시지 표시
     */
    function showMessage(message, type) {
        const messageContainer = $('#geocoding-message');
        messageContainer
            .removeClass('success error info')
            .addClass(type)
            .text(message)
            .fadeIn();
        
        // 3초 후 자동 숨김 (성공 메시지만)
        if (type === 'success') {
            setTimeout(function() {
                messageContainer.fadeOut();
            }, 3000);
        }
    }

    /**
     * 전역 함수로 노출 (디버깅용)
     */
    window.placesMetaboxDebug = {
        searchAddress: searchAddress,
        updateMapLocation: updateMapLocation,
        getSubwayInfo: getSubwayInfo,
        showMessage: showMessage,
        naverMap: function() { return naverMap; },
        naverGeocoder: function() { return naverGeocoder; }
    };

    console.log('✅ Places 메타박스 JavaScript 초기화 완료 (수정 버전)');

})(jQuery);

/**
 * 인증 실패 처리 (통합 버전)
 */
window.navermap_authFailure = function() {
    console.error('❌ 네이버 지도 API 인증 실패');
    $('#map-placeholder').html('❌ 네이버 지도 API 인증 실패<br>API 키를 확인해주세요');
    
    // 전역 변수 초기화
    naverMap = null;
    naverGeocoder = null;
    isMapReady = false;
    isGeocoderReady = false;
};

/**
 * 전역 오류 처리 (디버깅용)
 */
if (typeof window.addEventListener !== 'undefined') {
    window.addEventListener('error', function(e) {
        if (e.message && e.message.includes('maps') && e.message.includes('geocoder')) {
            console.error('❌ 네이버 지도 또는 지오코더 오류:', e.message);
            
            // 서버사이드 모드로 전환
            if (naverGeocoder === null) {
                console.log('⚠️ 서버사이드 모드로 전환');
            }
        }
    });
}
