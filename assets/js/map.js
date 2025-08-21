/**
 * 성수야! V2 - 네이버 지도 통합 클래스
 * 이전 버전의 안정적인 SungsuyaMap 클래스를 V2용으로 최적화
 * 
 * @package SungsuyaV2
 * @version 2.0.0
 */

(function() {
    'use strict';

    class SungsuyaMapV2 {
        constructor(containerId = 'popup-map') {
            this.map = null;
            this.markers = [];
            this.infoWindows = [];
            this.currentLocation = null;
            this.containerId = containerId;
            
            // 성수동 기본 좌표
            this.defaultCenter = {
                lat: 37.5447,
                lng: 127.0558
            };

            console.log('🗺️ SungsuyaMapV2 초기화 시작');
            this.init();
        }

        init() {
            // 네이버 지도 API 로드 확인 (안정화된 방식)
            if (this.checkNaverMapsAPI()) {
                console.log('✅ 네이버 지도 API 확인됨');
                this.initMap();
            } else {
                console.log('⏳ 네이버 지도 API 로드 대기 중...');
                // 공식 테스트 페이지에서 검증된 안정적인 대기 방식 사용
                this.waitForNaverMapsAPIStable();
            }
        }
        
        checkNaverMapsAPI() {
            return typeof naver !== 'undefined' && 
                   typeof naver.maps !== 'undefined' && 
                   typeof naver.maps.Map !== 'undefined';
        }
        
        waitForNaverMapsAPI() {
            // naverMapsReady 이벤트 대기
            document.addEventListener('naverMapsReady', () => {
                console.log('✅ 네이버 지도 API 콜백 이벤트 수신');
                this.initMap();
            });
            
            // 기존 폴백 방식도 유지 (window.sungsuyaMapReady 처리)
            let attempts = 0;
            const maxAttempts = 50;
            
            const checkInterval = setInterval(() => {
                attempts++;
                
                if (this.checkNaverMapsAPI() || window.sungsuyaMapReady) {
                    clearInterval(checkInterval);
                    console.log('✅ 네이버 지도 API 로드 완료');
                    this.initMap();
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkInterval);
                    console.warn('⚠️ 네이버 지도 API 로드 시간 초과 - 대체 UI 표시');
                    this.showApiKeyError('네이버 지도 API 로드에 실패했습니다. API 키와 도메인 설정을 확인해주세요.');
                }
            }, 100);
        }
        
        /**
         * 공식 테스트 페이지에서 검증된 안정적인 API 대기 방식
         */
        waitForNaverMapsAPIStable() {
            let attempts = 0;
            const maxAttempts = 100; // 10초 대기
            let authFailureCalled = false;
            
            // 인증 실패 이벤트 리스너 등록
            const authFailureHandler = () => {
                authFailureCalled = true;
                console.error('❌ 네이버 지도 API 인증 실패 감지됨');
                this.showApiKeyError('네이버 지도 API 인증에 실패했습니다. 도메인 등록을 확인해주세요.');
            };
            
            document.addEventListener('naverMapsAuthFailure', authFailureHandler, { once: true });
            
            const checkAPI = () => {
                attempts++;
                
                if (authFailureCalled) {
                    // 인증 실패로 인한 종료
                    return;
                } else if (this.checkNaverMapsAPI()) {
                    console.log('✅ 네이버 지도 API 로드 완료');
                    document.removeEventListener('naverMapsAuthFailure', authFailureHandler);
                    this.initMap();
                } else if (attempts < maxAttempts) {
                    setTimeout(checkAPI, 100);
                } else {
                    console.error('❌ 네이버 지도 API 로드 실패: 시간 초과');
                    document.removeEventListener('naverMapsAuthFailure', authFailureHandler);
                    this.showApiKeyError('네이버 지도 API 로드에 실패했습니다. 페이지를 새로고침해주세요.');
                }
            };
            
            checkAPI();
        }
        
        showApiKeyError(message = '네이버 지도를 로드할 수 없습니다') {
            const mapContainer = document.getElementById(this.containerId);
            
            if (mapContainer) {
                // 네이버 API가 설정한 스타일 강제 제거
                mapContainer.style.backgroundImage = 'none';
                mapContainer.style.background = '#f8f9fa';
                
                mapContainer.innerHTML = `
                    <div style="
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        height: 100%;
                        min-height: 400px;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        border-radius: 12px;
                        text-align: center;
                        color: white;
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        position: relative;
                        overflow: hidden;
                    ">
                        <!-- 배경 애니메이션 -->
                        <div style="
                            position: absolute;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48cGF0dGVybiBpZD0iZ3JpZCIgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiBwYXR0ZXJuVW5pdHM9InVzZXJTcGFjZU9uVXNlIj48cGF0aCBkPSJNIDQwIDAgTCAwIDAgMCA0MCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMSkiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==');
                            opacity: 0.3;
                            z-index: 1;
                        "></div>
                        
                        <div style="z-index: 10; position: relative; padding: 2rem;">
                            <div style="font-size: 64px; margin-bottom: 20px; animation: bounce 2s ease-in-out infinite;">🗺️</div>
                            <h2 style="margin: 0 0 12px 0; font-size: 24px; font-weight: 700;성수동 지도</h2>
                            <p style="margin: 0 0 8px 0; font-size: 16px; opacity: 0.9;">네이버 지도 API 인증 대기 중...</p>
                            <p style="margin: 0 0 24px 0; font-size: 14px; opacity: 0.7; max-width: 300px; line-height: 1.5;">${message}</p>
                            
                            <!-- 성수동 주요 지역 버튼들 -->
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 24px; max-width: 400px;">
                                <button onclick="window.open('https://map.naver.com/v5/search/성수역', '_blank')" 
                                        style="
                                            padding: 12px 16px;
                                            background: rgba(255,255,255,0.2);
                                            color: white;
                                            border: 1px solid rgba(255,255,255,0.3);
                                            border-radius: 8px;
                                            font-size: 14px;
                                            cursor: pointer;
                                            transition: all 0.3s ease;
                                            backdrop-filter: blur(10px);
                                        "
                                        onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='translateY(-2px)'"
                                        onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(0)'">
                                    🚆 성수역
                                </button>
                                <button onclick="window.open('https://map.naver.com/v5/search/성수동 커피거리', '_blank')" 
                                        style="
                                            padding: 12px 16px;
                                            background: rgba(255,255,255,0.2);
                                            color: white;
                                            border: 1px solid rgba(255,255,255,0.3);
                                            border-radius: 8px;
                                            font-size: 14px;
                                            cursor: pointer;
                                            transition: all 0.3s ease;
                                            backdrop-filter: blur(10px);
                                        "
                                        onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='translateY(-2px)'"
                                        onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(0)'">
                                    ☕ 커피거리
                                </button>
                                <button onclick="window.open('https://map.naver.com/v5/search/성수 수제비맨', '_blank')" 
                                        style="
                                            padding: 12px 16px;
                                            background: rgba(255,255,255,0.2);
                                            color: white;
                                            border: 1px solid rgba(255,255,255,0.3);
                                            border-radius: 8px;
                                            font-size: 14px;
                                            cursor: pointer;
                                            transition: all 0.3s ease;
                                            backdrop-filter: blur(10px);
                                        "
                                        onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='translateY(-2px)'"
                                        onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(0)'">
                                    🌊 수제비맨
                                </button>
                                <button onclick="window.open('https://map.naver.com/v5/search/성수동 스트리트', '_blank')" 
                                        style="
                                            padding: 12px 16px;
                                            background: rgba(255,255,255,0.2);
                                            color: white;
                                            border: 1px solid rgba(255,255,255,0.3);
                                            border-radius: 8px;
                                            font-size: 14px;
                                            cursor: pointer;
                                            transition: all 0.3s ease;
                                            backdrop-filter: blur(10px);
                                        "
                                        onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='translateY(-2px)'"
                                        onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(0)'">
                                    🏢 스트리트
                                </button>
                            </div>
                            
                            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                                <button onclick="window.open('https://map.naver.com/v5/search/성수동', '_blank')" 
                                        style="
                                            display: inline-flex;
                                            align-items: center;
                                            padding: 10px 20px;
                                            background: #00c73c;
                                            color: white;
                                            border: none;
                                            border-radius: 25px;
                                            font-size: 14px;
                                            font-weight: 600;
                                            cursor: pointer;
                                            text-decoration: none;
                                            transition: all 0.3s ease;
                                            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                                        "
                                        onmouseover="this.style.background='#00b235'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(0,0,0,0.25)'"
                                        onmouseout="this.style.background='#00c73c'; this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.2)'">
                                    🗺️ 네이버지도 열기
                                </button>
                                <button onclick="location.reload()" 
                                        style="
                                            display: inline-flex;
                                            align-items: center;
                                            padding: 10px 20px;
                                            background: rgba(255,255,255,0.2);
                                            color: white;
                                            border: 1px solid rgba(255,255,255,0.3);
                                            border-radius: 25px;
                                            font-size: 14px;
                                            font-weight: 600;
                                            cursor: pointer;
                                            transition: all 0.3s ease;
                                            backdrop-filter: blur(10px);
                                        "
                                        onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='translateY(-2px)'"
                                        onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(0)'">
                                    🔄 다시 시도
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <style>
                        @keyframes bounce {
                            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
                            40% { transform: translateY(-8px); }
                            60% { transform: translateY(-4px); }
                        }
                        
                        @keyframes float {
                            0%, 100% { transform: translateY(0px); }
                            50% { transform: translateY(-10px); }
                        }
                    </style>
                `;
            }
        }

        initMap() {
            const mapContainer = document.getElementById(this.containerId);
            
            if (!mapContainer) {
                console.error('❌ 지도 컨테이너를 찾을 수 없습니다:', this.containerId);
                return;
            }

            console.log('✅ 지도 컨테이너 확인:', mapContainer);
            console.log('컨테이너 크기:', mapContainer.offsetWidth + 'x' + mapContainer.offsetHeight);

            try {
                // 지도 옵션
                const mapOptions = {
                    center: new naver.maps.LatLng(this.defaultCenter.lat, this.defaultCenter.lng),
                    zoom: 15,
                    mapTypeControl: true,
                    mapTypeControlOptions: {
                        style: naver.maps.MapTypeControlStyle.BUTTON,
                        position: naver.maps.Position.TOP_RIGHT
                    },
                    zoomControl: true,
                    zoomControlOptions: {
                        style: naver.maps.ZoomControlStyle.SMALL,
                        position: naver.maps.Position.RIGHT_CENTER
                    },
                    scaleControl: false,
                    logoControl: true,
                    mapDataControl: false,
                    minZoom: 10,
                    maxZoom: 21
                };

                // 지도 생성
                this.map = new naver.maps.Map(mapContainer, mapOptions);
                console.log('✅ 네이버 지도 생성 완료');

                // 인증 실패 감지 및 처리
                setTimeout(() => {
                    this.checkAndHandleAuthFailure(mapContainer);
                }, 1000);

                return this.map;

            } catch (error) {
                console.error('❌ 지도 생성 오류:', error);
                this.showApiKeyError('지도 초기화 중 오류가 발생했습니다.');
                return null;
            }
        }

        /**
         * 인증 실패 감지 및 처리
         */
        checkAndHandleAuthFailure(mapContainer) {
            const computedStyle = window.getComputedStyle(mapContainer);
            const backgroundImage = computedStyle.backgroundImage;
            
            // 네이버 API가 설정한 인증 실패 배경 이미지 감지
            if (backgroundImage && backgroundImage.includes('auth_fail.png')) {
                console.warn('🚨 네이버 지도 인증 실패 감지됨 - 대체 UI로 전환');
                this.showApiKeyError('네이버 지도 인증에 실패했습니다. 도메인 등록을 확인해주세요.');
            }
        }

        /**
         * 단일 마커 추가 (팝업스토어 상세 페이지용)
         */
        addSingleMarker(lat, lng, title, address = '') {
            if (!this.map) {
                console.error('❌ 지도가 초기화되지 않음');
                return null;
            }

            try {
                const position = new naver.maps.LatLng(lat, lng);
                
                // 마커 생성
                const marker = new naver.maps.Marker({
                    position: position,
                    map: this.map,
                    title: title,
                    icon: {
                        content: this.getStoreMarkerIcon(),
                        size: new naver.maps.Size(40, 40),
                        anchor: new naver.maps.Point(20, 40)
                    }
                });

                // 정보창 생성
                const infoWindow = new naver.maps.InfoWindow({
                    content: this.getInfoWindowContent(title, address),
                    maxWidth: 300,
                    backgroundColor: "#fff",
                    borderColor: "#4f46e5",
                    borderWidth: 2,
                    anchorSize: new naver.maps.Size(10, 10),
                    anchorSkew: true,
                    anchorColor: "#fff",
                    pixelOffset: new naver.maps.Point(0, -10)
                });

                // 마커 클릭 이벤트
                naver.maps.Event.addListener(marker, 'click', () => {
                    if (infoWindow.getMap()) {
                        infoWindow.close();
                    } else {
                        infoWindow.open(this.map, marker);
                    }
                });

                // 지도 중심을 마커 위치로 이동
                this.map.setCenter(position);

                console.log('✅ 마커 생성 완료:', title);
                
                this.markers.push(marker);
                this.infoWindows.push(infoWindow);

                return marker;

            } catch (error) {
                console.error('❌ 마커 생성 오류:', error);
                return null;
            }
        }

        /**
         * 팝업스토어용 마커 아이콘
         */
        getStoreMarkerIcon() {
            return `
                <div style="
                    background: linear-gradient(135deg, #4f46e5, #7c3aed);
                    border: 3px solid white;
                    border-radius: 50%;
                    width: 36px;
                    height: 36px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 16px;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                    cursor: pointer;
                    transition: transform 0.2s ease;
                " onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    🏪
                </div>
            `;
        }

        /**
         * 정보창 HTML 생성
         */
        getInfoWindowContent(title, address) {
            return `
                <div style="padding: 16px; min-width: 250px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                    <h3 style="margin: 0 0 8px 0; color: #1f2937; font-size: 16px; font-weight: 600;">${title}</h3>
                    ${address ? `<p style="margin: 0 0 12px 0; color: #6b7280; font-size: 14px; line-height: 1.4;">${address}</p>` : ''}
                    <div style="display: flex; justify-content: center; margin-top: 12px;">
                        <button onclick="window.open('https://map.naver.com/v5/search/${encodeURIComponent(title + ' ' + address)}', '_blank')" 
                                style="
                                    background: #4f46e5;
                                    color: white;
                                    border: none;
                                    padding: 8px 16px;
                                    border-radius: 6px;
                                    font-size: 13px;
                                    cursor: pointer;
                                    transition: background-color 0.2s ease;
                                " 
                                onmouseover="this.style.background='#3730a3'" 
                                onmouseout="this.style.background='#4f46e5'">
                            🗺️ 네이버지도에서 보기
                        </button>
                    </div>
                </div>
            `;
        }

        /**
         * 다중 마커 추가 (메인 지도용)
         */
        addMultipleMarkers(stores) {
            if (!this.map || !Array.isArray(stores)) {
                console.error('❌ 지도 또는 스토어 데이터가 유효하지 않음');
                return;
            }

            stores.forEach(store => {
                const lat = parseFloat(store.latitude || store.lat);
                const lng = parseFloat(store.longitude || store.lng);
                
                if (lat && lng) {
                    this.addStoreMarker(lat, lng, store);
                }
            });

            console.log(`✅ ${stores.length}개 스토어 마커 추가 완료`);
        }

        addStoreMarker(lat, lng, storeData) {
            const marker = new naver.maps.Marker({
                position: new naver.maps.LatLng(lat, lng),
                map: this.map,
                title: storeData.title || storeData.name || 'Unknown',
                icon: {
                    content: this.getCategoryMarkerIcon(storeData.category || 'default'),
                    size: new naver.maps.Size(40, 40),
                    anchor: new naver.maps.Point(20, 40)
                }
            });

            const infoWindow = new naver.maps.InfoWindow({
                content: this.getStoreInfoWindowContent(storeData),
                maxWidth: 300
            });

            naver.maps.Event.addListener(marker, 'click', () => {
                this.infoWindows.forEach(iw => iw.close());
                infoWindow.open(this.map, marker);
            });

            this.markers.push(marker);
            this.infoWindows.push(infoWindow);

            return marker;
        }

        getCategoryMarkerIcon(category) {
            const icons = {
                'fashion': '👗',
                'cafe': '☕',
                'food': '🍽️',
                'beauty': '💄',
                'art': '🎨',
                'lifestyle': '🏠',
                'tech': '💻',
                'default': '🏪'
            };

            const emoji = icons[category] || icons.default;
            return `
                <div style="
                    background: white;
                    border: 2px solid #4f46e5;
                    border-radius: 50%;
                    width: 36px;
                    height: 36px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 16px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                    cursor: pointer;
                ">${emoji}</div>
            `;
        }

        getStoreInfoWindowContent(storeData) {
            const title = storeData.title || storeData.name || 'Unknown';
            const description = storeData.description || 'No description available';
            const category = storeData.category || 'default';

            return `
                <div style="padding: 15px; min-width: 250px;">
                    <h3 style="margin: 0 0 10px 0; color: #333; font-size: 16px; font-weight: bold;">${title}</h3>
                    <p style="margin: 0 0 10px 0; color: #666; font-size: 14px; line-height: 1.4;">${description}</p>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                        <span style="background: #f0f0f0; padding: 4px 8px; border-radius: 12px; font-size: 12px; color: #666;">${category}</span>
                    </div>
                </div>
            `;
        }

        /**
         * 지도 리사이즈
         */
        resizeMap() {
            if (this.map) {
                naver.maps.Event.trigger(this.map, 'resize');
                console.log('✅ 지도 리사이즈 완료');
            }
        }

        /**
         * 리소스 정리
         */
        destroy() {
            this.markers.forEach(marker => {
                marker.setMap(null);
            });
            
            this.infoWindows.forEach(infoWindow => {
                infoWindow.close();
            });
            
            this.markers = [];
            this.infoWindows = [];
            this.map = null;
            
            console.log('✅ SungsuyaMapV2 리소스 정리 완료');
        }
    }

    // 전역 사용 가능하게 설정
    window.SungsuyaMapV2 = SungsuyaMapV2;

    // 단일 스토어 지도 초기화 함수 (상세 페이지용)
    window.initSingleStoreMap = function(lat, lng, title, address) {
        if (!window.sungsuyaMapInstance) {
            window.sungsuyaMapInstance = new SungsuyaMapV2('popup-map');
        }
        
        // 지도가 생성되었다면 마커 추가
        if (window.sungsuyaMapInstance.map) {
            window.sungsuyaMapInstance.addSingleMarker(lat, lng, title, address);
        }
    };

    // 메인 지도 초기화 함수
    window.initMainMap = function(containerId = 'naver-map') {
        if (!window.sungsuyaMapInstance) {
            window.sungsuyaMapInstance = new SungsuyaMapV2(containerId);
        }
        return window.sungsuyaMapInstance;
    };

    // 리사이즈 이벤트
    window.addEventListener('resize', function() {
        if (window.sungsuyaMapInstance && window.sungsuyaMapInstance.map) {
            window.sungsuyaMapInstance.resizeMap();
        }
    });

    console.log('🗺️ SungsuyaMapV2 클래스 로드 완료');

})();
