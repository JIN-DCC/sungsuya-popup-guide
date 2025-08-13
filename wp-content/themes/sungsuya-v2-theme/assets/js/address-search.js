/**
 * 성수야! V2 - 실시간 주소 검색 (네이버 Geocoder 통합)
 * 
 * 네이버 지도 API Geocoder 서브모듈을 활용한 실시간 주소 자동완성
 * 
 * @package SungsuyaV2
 * @version 2.0.0
 */

(function() {
    'use strict';

    class SungsuyaAddressSearch {
        constructor() {
            this.searchInput = null;
            this.suggestionsContainer = null;
            this.selectedIndex = -1;
            this.searchTimeout = null;
            this.isGeocoderReady = false;
            this.currentSuggestions = [];
            
            // 설정
            this.config = {
                debounceDelay: 500,
                maxSuggestions: 8,
                minQueryLength: 2
            };
            
            console.log('🔍 SungsuyaAddressSearch 초기화 시작');
            this.init();
        }
        
        init() {
            // DOM 로드 대기
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setupEventListeners());
            } else {
                this.setupEventListeners();
            }
            
            // 네이버 지도 API 로드 대기
            this.waitForGeocoderAPI();
        }
        
        waitForGeocoderAPI() {
            let attempts = 0;
            const maxAttempts = 50;
            
            const checkGeocoder = () => {
                attempts++;
                
                if (this.checkGeocoderAvailable()) {
                    this.isGeocoderReady = true;
                    console.log('✅ 네이버 Geocoder API 준비 완료');
                    this.onGeocoderReady();
                } else if (attempts < maxAttempts) {
                    setTimeout(checkGeocoder, 100);
                } else {
                    console.warn('⚠️ 네이버 Geocoder API 로드 시간 초과 - 폴백 모드 사용');
                    this.isGeocoderReady = false;
                }
            };
            
            checkGeocoder();
        }
        
        checkGeocoderAvailable() {
            return typeof naver !== 'undefined' && 
                   typeof naver.maps !== 'undefined' && 
                   typeof naver.maps.Service !== 'undefined' &&
                   typeof naver.maps.Service.geocode === 'function';
        }
        
        onGeocoderReady() {
            // Geocoder 준비 완료 후 추가 설정
            if (this.searchInput) {
                this.searchInput.placeholder = "주소를 입력하세요 (예: 서울특별시 성동구 성수일로8길)";
                console.log('🎯 실시간 주소 검색 활성화됨');
            }
        }
        
        setupEventListeners() {
            // 주소 검색 입력창 찾기
            this.searchInput = document.getElementById('store-address-search');
            this.suggestionsContainer = document.getElementById('address-suggestions');
            
            if (!this.searchInput) {
                console.warn('⚠️ 주소 검색 입력창을 찾을 수 없습니다');
                return;
            }
            
            if (!this.suggestionsContainer) {
                console.warn('⚠️ 자동완성 컨테이너를 찾을 수 없습니다');
                return;
            }
            
            console.log('✅ 주소 검색 요소들 확인됨');
            
            // 이벤트 리스너 등록
            this.searchInput.addEventListener('input', (e) => this.handleInput(e));
            this.searchInput.addEventListener('keydown', (e) => this.handleKeyDown(e));
            this.searchInput.addEventListener('blur', (e) => this.handleBlur(e));
            this.searchInput.addEventListener('focus', (e) => this.handleFocus(e));
            
            // 외부 클릭 시 자동완성 숨기기
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.address-input-section')) {
                    this.hideSuggestions();
                }
            });
            
            console.log('✅ 이벤트 리스너 등록 완료');
        }
        
        handleInput(event) {
            const query = event.target.value.trim();
            
            // 이전 타이머 취소
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }
            
            // 빈 입력이거나 너무 짧은 경우
            if (query.length < this.config.minQueryLength) {
                this.hideSuggestions();
                return;
            }
            
            // Debounce 적용하여 API 호출 최적화
            this.searchTimeout = setTimeout(() => {
                this.searchAddresses(query);
            }, this.config.debounceDelay);
        }
        
        handleKeyDown(event) {
            const key = event.key;
            
            if (!this.isSuggestionsVisible()) {
                return;
            }
            
            switch (key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.selectNext();
                    break;
                    
                case 'ArrowUp':
                    event.preventDefault();
                    this.selectPrevious();
                    break;
                    
                case 'Enter':
                    event.preventDefault();
                    this.confirmSelection();
                    break;
                    
                case 'Escape':
                    this.hideSuggestions();
                    break;
            }
        }
        
        handleBlur(event) {
            // 자동완성 항목 클릭을 위해 약간의 지연
            setTimeout(() => {
                if (!document.activeElement?.closest('.address-suggestions')) {
                    this.hideSuggestions();
                }
            }, 150);
        }
        
        handleFocus(event) {
            // 포커스 시 기존 결과가 있으면 다시 표시
            if (this.currentSuggestions.length > 0) {
                this.showSuggestions();
            }
        }
        
        async searchAddresses(query) {
            console.log('🔍 주소 검색:', query);
            
            if (!this.isGeocoderReady) {
                console.warn('⚠️ Geocoder API가 준비되지 않음 - 폴백 검색 사용');
                this.fallbackSearch(query);
                return;
            }
            
            try {
                // 로딩 상태 표시
                this.showLoadingState();
                
                // 네이버 Geocoder API 호출
                naver.maps.Service.geocode({
                    query: query,
                    count: this.config.maxSuggestions
                }, (status, response) => {
                    this.handleGeocoderResponse(status, response, query);
                });
                
            } catch (error) {
                console.error('❌ 네이버 Geocoder API 오류:', error);
                this.fallbackSearch(query);
            }
        }
        
        handleGeocoderResponse(status, response, originalQuery) {
            console.log('📍 Geocoder 응답:', status, response);
            
            if (status === naver.maps.Service.Status.OK) {
                const results = this.parseGeocoderResults(response);
                this.displaySuggestions(results, originalQuery);
            } else {
                console.warn('⚠️ Geocoder API 응답 오류:', status);
                this.fallbackSearch(originalQuery);
            }
        }
        
        parseGeocoderResults(response) {
            const results = [];
            
            if (response && response.v2 && response.v2.addresses) {
                response.v2.addresses.forEach(address => {
                    results.push({
                        roadAddress: address.roadAddress || '',
                        jibunAddress: address.jibunAddress || '',
                        englishAddress: address.englishAddress || '',
                        x: parseFloat(address.x),
                        y: parseFloat(address.y),
                        distance: address.distance || 0,
                        formattedAddress: address.roadAddress || address.jibunAddress,
                        latitude: parseFloat(address.y),
                        longitude: parseFloat(address.x),
                        district: this.detectDistrict(address.jibunAddress || address.roadAddress),
                        source: 'geocoder'
                    });
                });
            }
            
            return results;
        }
        
        fallbackSearch(query) {
            console.log('🔄 폴백 검색 모드:', query);
            
            // 기존 AJAX 방식으로 폴백
            if (typeof sungsuyaAdmin !== 'undefined') {
                jQuery.ajax({
                    url: sungsuyaAdmin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'search_addresses_naver',
                        query: query,
                        nonce: sungsuyaAdmin.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            this.displaySuggestions(response.data, query);
                        } else {
                            this.showErrorState(response.data || '검색 결과가 없습니다');
                        }
                    },
                    error: () => {
                        this.showErrorState('검색 중 오류가 발생했습니다');
                    }
                });
            } else {
                this.showErrorState('검색 기능을 사용할 수 없습니다');
            }
        }
        
        displaySuggestions(results, query) {
            this.currentSuggestions = results;
            this.selectedIndex = -1;
            
            if (results.length === 0) {
                this.showNoResultsState(query);
                return;
            }
            
            const html = results.map((result, index) => {
                const isRoadAddress = result.roadAddress && result.roadAddress.trim() !== '';
                const mainAddress = isRoadAddress ? result.roadAddress : result.jibunAddress;
                const subAddress = isRoadAddress && result.jibunAddress ? result.jibunAddress : '';
                
                return `
                    <div class="suggestion-item" data-index="${index}">
                        <div class="suggestion-main">
                            <span class="address-type">${isRoadAddress ? '도로명' : '지번'}</span>
                            <span class="main-address">${this.highlightQuery(mainAddress, query)}</span>
                        </div>
                        ${subAddress ? `<div class="suggestion-sub">${subAddress}</div>` : ''}
                        <div class="suggestion-meta">
                            <span class="district-badge district-${result.district}">${this.getDistrictName(result.district)}</span>
                            <span class="coordinates">${result.latitude.toFixed(4)}, ${result.longitude.toFixed(4)}</span>
                        </div>
                    </div>
                `;
            }).join('');
            
            this.suggestionsContainer.innerHTML = html;
            this.showSuggestions();
            this.attachSuggestionClickEvents();
        }
        
        showLoadingState() {
            this.suggestionsContainer.innerHTML = `
                <div class="suggestion-loading">
                    <div class="loading-spinner"></div>
                    <span>주소를 검색하고 있습니다...</span>
                </div>
            `;
            this.showSuggestions();
        }
        
        showErrorState(message) {
            this.suggestionsContainer.innerHTML = `
                <div class="suggestion-error">
                    <span class="error-icon">⚠️</span>
                    <span>${message}</span>
                </div>
            `;
            this.showSuggestions();
        }
        
        showNoResultsState(query) {
            this.suggestionsContainer.innerHTML = `
                <div class="suggestion-no-results">
                    <span class="no-results-icon">🔍</span>
                    <span>"${query}"에 대한 검색 결과가 없습니다</span>
                    <small>다른 검색어를 시도해보세요</small>
                </div>
            `;
            this.showSuggestions();
        }
        
        attachSuggestionClickEvents() {
            const items = this.suggestionsContainer.querySelectorAll('.suggestion-item');
            items.forEach(item => {
                item.addEventListener('click', () => {
                    const index = parseInt(item.dataset.index);
                    this.selectSuggestion(index);
                });
                
                item.addEventListener('mouseenter', () => {
                    this.highlightSuggestion(parseInt(item.dataset.index));
                });
            });
        }
        
        selectNext() {
            const maxIndex = this.currentSuggestions.length - 1;
            this.selectedIndex = this.selectedIndex < maxIndex ? this.selectedIndex + 1 : 0;
            this.highlightSuggestion(this.selectedIndex);
        }
        
        selectPrevious() {
            const maxIndex = this.currentSuggestions.length - 1;
            this.selectedIndex = this.selectedIndex > 0 ? this.selectedIndex - 1 : maxIndex;
            this.highlightSuggestion(this.selectedIndex);
        }
        
        highlightSuggestion(index) {
            // 이전 하이라이트 제거
            this.suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
                item.classList.remove('highlighted');
            });
            
            // 새 하이라이트 적용
            const item = this.suggestionsContainer.querySelector(`[data-index="${index}"]`);
            if (item) {
                item.classList.add('highlighted');
                item.scrollIntoView({ block: 'nearest' });
            }
            
            this.selectedIndex = index;
        }
        
        confirmSelection() {
            if (this.selectedIndex >= 0 && this.currentSuggestions[this.selectedIndex]) {
                this.selectSuggestion(this.selectedIndex);
            }
        }
        
        selectSuggestion(index) {
            const suggestion = this.currentSuggestions[index];
            if (!suggestion) return;
            
            console.log('✅ 주소 선택됨:', suggestion);
            
            // 입력창에 주소 설정
            this.searchInput.value = suggestion.formattedAddress;
            
            // 좌표 및 상세 정보 자동 입력
            this.fillAddressFields(suggestion);
            
            // 지도 업데이트
            this.updateMap(suggestion);
            
            // 자동완성 숨기기
            this.hideSuggestions();
            
            // 성공 메시지 표시
            this.showSuccessMessage(suggestion);
        }
        
        fillAddressFields(suggestion) {
            // 주소 필드들 채우기
            const addressField = document.getElementById('store_address');
            const latitudeField = document.getElementById('store_latitude');
            const longitudeField = document.getElementById('store_longitude');
            const districtField = document.getElementById('store_district');
            
            if (addressField) addressField.value = suggestion.formattedAddress;
            if (latitudeField) latitudeField.value = suggestion.latitude.toFixed(6);
            if (longitudeField) longitudeField.value = suggestion.longitude.toFixed(6);
            if (districtField) districtField.value = suggestion.district;
            
            // 입력 이벤트 트리거 (다른 스크립트들이 반응할 수 있도록)
            [addressField, latitudeField, longitudeField, districtField].forEach(field => {
                if (field) {
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
        
        updateMap(suggestion) {
            // 관리자 페이지 지도 업데이트 (새로 추가)
            if (typeof updateMapLocation === 'function') {
                updateMapLocation(suggestion.latitude, suggestion.longitude, suggestion.formattedAddress);
            }
            
            // 프론트엔드 지도 업데이트
            if (window.sungsuyaMapInstance && window.sungsuyaMapInstance.map) {
                console.log('🗺️ 프론트엔드 지도 업데이트:', suggestion.latitude, suggestion.longitude);
                
                const position = new naver.maps.LatLng(suggestion.latitude, suggestion.longitude);
                window.sungsuyaMapInstance.map.setCenter(position);
                window.sungsuyaMapInstance.map.setZoom(16);
                
                // 기존 마커 제거
                window.sungsuyaMapInstance.markers.forEach(marker => marker.setMap(null));
                window.sungsuyaMapInstance.markers = [];
                
                // 새 마커 추가
                const marker = window.sungsuyaMapInstance.addSingleMarker(
                    suggestion.latitude, 
                    suggestion.longitude, 
                    suggestion.formattedAddress,
                    suggestion.formattedAddress
                );
            }
        }
        
        showSuccessMessage(suggestion) {
            const statusElement = document.getElementById('location-status');
            if (statusElement) {
                statusElement.innerHTML = `
                    <span class="status-icon" style="color: #10b981;">✅</span>
                    <span class="status-text" style="color: #10b981;">주소가 설정되었습니다</span>
                `;
            }
        }
        
        showSuggestions() {
            this.suggestionsContainer.style.display = 'block';
            this.suggestionsContainer.classList.add('visible');
        }
        
        hideSuggestions() {
            this.suggestionsContainer.style.display = 'none';
            this.suggestionsContainer.classList.remove('visible');
            this.selectedIndex = -1;
        }
        
        isSuggestionsVisible() {
            return this.suggestionsContainer.style.display === 'block';
        }
        
        highlightQuery(text, query) {
            if (!query || !text) return text;
            
            const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            return text.replace(regex, '<mark>$1</mark>');
        }
        
        detectDistrict(address) {
            const districts = {
                'sungsu': ['성수동', '성수1가', '성수2가', '성수일로', '성수이로'],
                'seongdong': ['성동구', '왕십리', '행당동', '응봉동', '금고동', '옥수동', '사근동', '용답동'],
                'gangnam': ['강남구', '역삼동', '삼성동', '대치동', '청담동', '압구정동', '신사동', '논현동'],
                'hongdae': ['홍대', '홍익대', '상수동', '합정동', '마포구'],
                'itaewon': ['이태원', '한남동', '용산구'],
                'myeongdong': ['명동', '중구', '을지로', '충무로'],
                'insadong': ['인사동', '종로구', '삼청동', '북촌'],
                'gangbuk': ['강북구', '수유동', '미아동'],
                'gwangjin': ['광진구', '건대', '자양동', '구의동']
            };
            
            for (const [district, keywords] of Object.entries(districts)) {
                for (const keyword of keywords) {
                    if (address.includes(keyword)) {
                        return district;
                    }
                }
            }
            
            return 'etc';
        }
        
        getDistrictName(district) {
            const names = {
                'sungsu': '성수동',
                'seongdong': '성동구',
                'gangnam': '강남구',
                'hongdae': '홍대',
                'itaewon': '이태원',
                'myeongdong': '명동',
                'insadong': '인사동',
                'gangbuk': '강북구',
                'gwangjin': '광진구',
                'etc': '기타지역'
            };
            
            return names[district] || '알 수 없음';
        }
    }

    // 전역 접근 가능하게 설정
    window.SungsuyaAddressSearch = SungsuyaAddressSearch;

    // DOM 로드 완료 후 자동 초기화
    document.addEventListener('DOMContentLoaded', function() {
        // 관리자 페이지에서만 실행
        if (document.getElementById('store-address-search')) {
            console.log('🚀 실시간 주소 검색 시스템 시작');
            window.sungsuyaAddressSearch = new SungsuyaAddressSearch();
        }
    });

    console.log('🔍 SungsuyaAddressSearch 스크립트 로드 완료');

})();
