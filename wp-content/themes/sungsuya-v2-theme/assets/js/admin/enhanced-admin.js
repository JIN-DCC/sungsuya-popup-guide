/**
 * 성수야! V2 - 혁신적 관리자 메타박스 JavaScript
 * 네이버 지도 API 연동 및 탭 시스템
 * 
 * @package Sungsuya_V2
 * @version 3.0.0
 */

// 전역 클래스 중복 선언 방지
if (typeof window.SungsuyaEnhancedMetabox !== 'undefined') {
    console.log('ℹ️ SungsuyaEnhancedMetabox 클래스가 이미 존재합니다. 재초기화를 건너뜁니다.');
} else {

class SungsuyaEnhancedMetabox {
    constructor() {
        console.log('🚀 SungsuyaEnhancedMetabox 초기화 시작');
        
        // 초기화 상태
        this.initialized = false;
        this.map = null;
        this.marker = null;
        this.geocoder = null;
        this.currentLocation = null;
        this.searchTimeout = null;
        this.autoSaveEnabled = true;
        this.lastSavedTime = null;
        
        // DOM 요소들
        this.elements = {};
        
        // 바인딩
        this.init = this.init.bind(this);
        this.setupTabNavigation = this.setupTabNavigation.bind(this);
        this.setupFormValidation = this.setupFormValidation.bind(this);
        this.initializeProgressTracking = this.initializeProgressTracking.bind(this);
        
        // DOM이 준비되면 초기화
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', this.init);
        } else {
            this.init();
        }
    }
    
    /**
     * 초기화
     */
    async init() {
        if (this.initialized) {
            console.log('ℹ️ 이미 초기화되었습니다.');
            return;
        }
        
        try {
            console.log('🚀 SungsuyaEnhancedMetabox 초기화 시작');
            
            // sungsuyaEnhancedAdmin 객체 확인 (enhanced-admin-metabox.php에서 localized)
            if (typeof sungsuyaEnhancedAdmin === 'undefined') {
                console.warn('⚠️ sungsuyaEnhancedAdmin 객체가 정의되지 않았습니다');
                return;
            }
            
            console.log('✅ sungsuyaEnhancedAdmin 객체 확인:', sungsuyaEnhancedAdmin);
            
            this.cacheDOMElements();
            this.setupTabNavigation();
            this.setupFormValidation();
            this.setupEventListeners();
            this.initializeProgressTracking();
            this.setupAutoSave();
            
            // 네이버 지도 API 체크
            if (typeof naver !== 'undefined' && sungsuyaEnhancedAdmin.naverMapClientId) {
                console.log('🗺️ 네이버 지도 API 사용 가능, 지도 초기화 중...');
                await this.initializeMap();
                this.loadExistingLocation();
            } else if (sungsuyaEnhancedAdmin.naverMapClientId) {
                console.warn('⚠️ 네이버 API 키는 설정되었지만 naver 객체가 로드되지 않았습니다');
            } else {
                console.info('ℹ️ 네이버 API 키가 설정되지 않았습니다');
            }
            
            this.initialized = true;
            console.log('✅ SungsuyaEnhancedMetabox 초기화 완료');
            
        } catch (error) {
            console.error('❌ Enhanced Metabox 초기화 오류:', error);
        }
    }
    
    /**
     * DOM 요소 캐싱
     */
    cacheDOMElements() {
        // 탭 관련
        this.elements.tabNavItems = document.querySelectorAll('.tab-nav-item');
        this.elements.tabPanes = document.querySelectorAll('.tab-pane');
        
        // 검색 및 지도 관련
        this.elements.addressSearch = document.getElementById('address_search');
        this.elements.searchButton = document.getElementById('search-address-btn');
        this.elements.mapPreview = document.getElementById('enhanced-map-preview');
        this.elements.addressField = document.getElementById('address');
        this.elements.latitudeField = document.getElementById('latitude');
        this.elements.longitudeField = document.getElementById('longitude');
        this.elements.districtField = document.getElementById('district');
        
        // 지하철역 관련
        this.elements.nearestSubwayDisplay = document.getElementById('nearest_subway_display');
        this.elements.nearestSubwayStation = document.getElementById('nearest_subway_station');
        this.elements.subwayExitInfo = document.getElementById('subway_exit_info');
        this.elements.walkingTimeDisplay = document.getElementById('walking_time_display');
        this.elements.walkingTimeFromStation = document.getElementById('walking_time_from_station');
        this.elements.subwayStatusIndicator = document.getElementById('subway_status_indicator');
        this.elements.subwayExitDescription = document.getElementById('subway_exit_description');
        
        // 진행률 관련
        this.elements.progressBar = document.querySelector('.progress-fill');
        this.elements.progressText = document.querySelector('.progress-text');
        
        // 투어 호환성 관련
        this.elements.compatibilityScore = document.querySelector('.score-number');
        this.elements.statusDetails = document.querySelector('.status-details');
        this.elements.checkCompatibilityBtn = document.getElementById('check-tour-compatibility');
        this.elements.validateAllBtn = document.getElementById('validate-all-data');
        
        // 빠른 액션 버튼들
        this.elements.quickSaveBtn = document.getElementById('quick-save');
        this.elements.previewInTourBtn = document.getElementById('preview-in-tour');
        this.elements.testTourPlannerBtn = document.getElementById('test-in-tour-planner');
        
        // 지도 컨트롤 버튼들
        this.elements.centerSungsudongBtn = document.getElementById('center-sungsudong');
        this.elements.manualAdjustBtn = document.getElementById('manual-adjust');
        
        // 자동 저장 관련
        this.elements.autoSaveToggle = document.getElementById('auto-save-toggle');
        this.elements.autoSaveStatus = document.getElementById('auto-save-status');
        this.elements.lastSavedTime = document.getElementById('last-saved-time');
    }
    
    /**
     * 탭 네비게이션 설정
     */
    setupTabNavigation() {
        if (!this.elements.tabNavItems || !this.elements.tabPanes) return;
        
        this.elements.tabNavItems.forEach(navItem => {
            navItem.addEventListener('click', (e) => {
                e.preventDefault();
                const targetTab = navItem.getAttribute('data-tab');
                this.switchTab(targetTab);
            });
        });
    }
    
    /**
     * 탭 전환
     */
    switchTab(targetTab) {
        // 모든 탭 비활성화
        this.elements.tabNavItems.forEach(item => {
            item.classList.remove('active');
        });
        
        this.elements.tabPanes.forEach(pane => {
            pane.classList.remove('active');
        });
        
        // 선택된 탭 활성화
        const activeNavItem = document.querySelector(`[data-tab="${targetTab}"]`);
        const activePane = document.getElementById(`tab-${targetTab}`);
        
        if (activeNavItem) activeNavItem.classList.add('active');
        if (activePane) activePane.classList.add('active');
        
        // 지도가 있는 탭으로 전환 시 지도 리사이즈
        if (targetTab === 'location' && this.map) {
            setTimeout(() => {
                this.map.autoResize();
            }, 100);
        }
        
        console.log(`📑 탭 전환: ${targetTab}`);
    }
    
    /**
     * 이벤트 리스너 설정
     */
    setupEventListeners() {
        // 주소 검색
        if (this.elements.addressSearch) {
            this.elements.addressSearch.addEventListener('input', (e) => {
                this.handleAddressSearch(e.target.value);
            });
        }
        
        if (this.elements.searchButton) {
            this.elements.searchButton.addEventListener('click', () => {
                this.performAddressSearch();
            });
        }
        
        // 지하철역 자동 검색 (좌표 변경 시)
        if (this.elements.latitudeField && this.elements.longitudeField) {
            const handleCoordinateChange = () => {
                this.findNearestSubway();
            };
            
            this.elements.latitudeField.addEventListener('change', handleCoordinateChange);
            this.elements.longitudeField.addEventListener('change', handleCoordinateChange);
        }
        
        // 지도 컨트롤
        if (this.elements.centerSungsudongBtn) {
            this.elements.centerSungsudongBtn.addEventListener('click', () => {
                this.centerToSungsudong();
            });
        }
        
        if (this.elements.manualAdjustBtn) {
            this.elements.manualAdjustBtn.addEventListener('click', () => {
                this.enableManualAdjustment();
            });
        }
        
        // 투어 호환성 체크
        if (this.elements.checkCompatibilityBtn) {
            this.elements.checkCompatibilityBtn.addEventListener('click', () => {
                this.checkTourCompatibility();
            });
        }
        
        if (this.elements.validateAllBtn) {
            this.elements.validateAllBtn.addEventListener('click', () => {
                this.validateAllData();
            });
        }
        
        // 빠른 액션
        if (this.elements.quickSaveBtn) {
            this.elements.quickSaveBtn.addEventListener('click', () => {
                this.quickSave();
            });
        }
        
        if (this.elements.previewInTourBtn) {
            this.elements.previewInTourBtn.addEventListener('click', () => {
                this.previewInTour();
            });
        }
        
        if (this.elements.testTourPlannerBtn) {
            this.elements.testTourPlannerBtn.addEventListener('click', () => {
                this.testInTourPlanner();
            });
        }
        
        // 자동 저장 토글
        if (this.elements.autoSaveToggle) {
            this.elements.autoSaveToggle.addEventListener('change', (e) => {
                this.toggleAutoSave(e.target.checked);
            });
        }
    }
    
    /**
     * 폼 검증 설정
     */
    setupFormValidation() {
        const requiredFields = document.querySelectorAll('.validation-required');
        
        requiredFields.forEach(field => {
            field.addEventListener('blur', () => {
                this.validateField(field);
            });
            
            field.addEventListener('input', () => {
                this.clearFieldValidation(field);
            });
        });
    }
    
    /**
     * 필드 검증
     */
    validateField(field) {
        const value = field.value.trim();
        const validation = field.getAttribute('data-validation');
        const fieldId = field.id;
        const validationElement = document.getElementById(`validation-${fieldId}`);
        
        if (!validationElement) return;
        
        let isValid = true;
        let errorMessage = '';
        
        if (validation) {
            const rules = validation.split('|');
            
            for (let rule of rules) {
                if (rule === 'required' && !value) {
                    isValid = false;
                    errorMessage = '필수 입력 항목입니다.';
                    break;
                }
                
                if (rule.startsWith('min:')) {
                    const minLength = parseInt(rule.split(':')[1]);
                    if (value.length < minLength) {
                        isValid = false;
                        errorMessage = `최소 ${minLength}자 이상 입력해주세요.`;
                        break;
                    }
                }
                
                if (rule.startsWith('max:')) {
                    const maxLength = parseInt(rule.split(':')[1]);
                    if (value.length > maxLength) {
                        isValid = false;
                        errorMessage = `최대 ${maxLength}자까지 입력 가능합니다.`;
                        break;
                    }
                }
            }
        }
        
        if (isValid) {
            validationElement.textContent = '';
            validationElement.className = 'field-validation';
            field.classList.remove('invalid');
            field.classList.add('valid');
        } else {
            validationElement.textContent = errorMessage;
            validationElement.className = 'field-validation error';
            field.classList.remove('valid');
            field.classList.add('invalid');
        }
        
        this.updateProgress();
        return isValid;
    }
    
    /**
     * 필드 검증 상태 클리어
     */
    clearFieldValidation(field) {
        const fieldId = field.id;
        const validationElement = document.getElementById(`validation-${fieldId}`);
        
        if (validationElement) {
            validationElement.textContent = '';
            validationElement.className = 'field-validation';
        }
        
        field.classList.remove('valid', 'invalid');
    }
    
    /**
     * 진행률 추적 초기화
     */
    initializeProgressTracking() {
        this.updateProgress();
        
        // 모든 입력 필드에 이벤트 리스너 추가
        const allInputs = document.querySelectorAll('#sungsuya-enhanced-metabox input, #sungsuya-enhanced-metabox textarea, #sungsuya-enhanced-metabox select');
        
        allInputs.forEach(input => {
            input.addEventListener('input', () => {
                this.updateProgress();
            });
            
            input.addEventListener('change', () => {
                this.updateProgress();
            });
        });
    }
    
    /**
     * 진행률 업데이트
     */
    updateProgress() {
        const totalFields = document.querySelectorAll('#sungsuya-enhanced-metabox input[required], #sungsuya-enhanced-metabox textarea[required], #sungsuya-enhanced-metabox select[required], .validation-required').length;
        const completedFields = document.querySelectorAll('#sungsuya-enhanced-metabox input[required]:valid, #sungsuya-enhanced-metabox textarea[required]:not(:placeholder-shown), #sungsuya-enhanced-metabox select[required]:not([value=""]), .validation-required:not([value=""])').length;
        
        const progress = totalFields > 0 ? Math.round((completedFields / totalFields) * 100) : 0;
        
        if (this.elements.progressBar) {
            this.elements.progressBar.style.width = `${progress}%`;
        }
        
        if (this.elements.progressText) {
            this.elements.progressText.textContent = `${progress}% 완료`;
        }
        
        this.updateTabStatuses();
    }
    
    /**
     * 탭 상태 업데이트
     */
    updateTabStatuses() {
        const tabs = ['basic', 'location', 'operation', 'social', 'tour'];
        
        tabs.forEach(tab => {
            const tabPane = document.getElementById(`tab-${tab}`);
            const statusElement = document.getElementById(`status-${tab}`);
            
            if (!tabPane || !statusElement) return;
            
            const requiredFields = tabPane.querySelectorAll('.validation-required');
            const completedFields = Array.from(requiredFields).filter(field => field.value.trim() !== '');
            
            let status = '⚪'; // 기본
            
            if (requiredFields.length === 0) {
                status = '⚪'; // 선택사항만 있는 탭
            } else if (completedFields.length === requiredFields.length) {
                status = '✅'; // 완료
            } else if (completedFields.length > 0) {
                status = '🔄'; // 진행 중
            } else {
                status = '❌'; // 미완료
            }
            
            statusElement.textContent = status;
        });
    }
    
    /**
     * 주소 검색 처리
     */
    handleAddressSearch(query) {
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        if (query.length < 2) return;
        
        this.searchTimeout = setTimeout(() => {
            this.performAddressSearch(query);
        }, 500);
    }
    
    /**
     * 주소 검색 실행
     */
    async performAddressSearch(query = null) {
        if (!query) {
            query = this.elements.addressSearch?.value;
        }
        
        if (!query || query.length < 2) {
            this.showMessage('검색어를 2자 이상 입력해주세요.', 'warning');
            return;
        }
        
        console.log('🔍 주소 검색 시작:', query);
        
        try {
            const response = await fetch(sungsuyaEnhancedAdmin.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sungsuya_search_address',
                    query: query,
                    nonce: sungsuyaEnhancedAdmin.nonce
                })
            });
            
            console.log('📶 AJAX 응답 상태:', response.status, response.statusText);
            
            const data = await response.json();
            console.log('📝 AJAX 응답 데이터:', data);
            
            if (data.success && data.data) {
                console.log('✅ 주소 검색 성공 - 데이터:', data.data);
                this.updateLocationFromSearch(data.data);
                this.showMessage('✅ 주소를 찾았습니다.', 'success');
            } else {
                console.error('❌ 주소 검색 실패:', data);
                this.showMessage(data.data || '주소를 찾을 수 없습니다.', 'error');
            }
            
        } catch (error) {
            console.error('❌ 주소 검색 오류:', error);
            this.showMessage('주소 검색 중 오류가 발생했습니다.', 'error');
        }
    }
    
    /**
     * 검색 결과로 위치 정보 업데이트
     */
    updateLocationFromSearch(addressData) {
        console.log('📍 주소 검색 결과 데이터:', addressData);
        
        // 주소 필드 업데이트
        if (this.elements.addressField) {
            this.elements.addressField.value = addressData.formatted_address || addressData.roadAddress || addressData.jibunAddress || '';
            console.log('📮 주소 필드 업데이트:', this.elements.addressField.value);
        }
        
        // 좌표 필드 업데이트 (올바른 필드명 사용)
        if (this.elements.latitudeField) {
            this.elements.latitudeField.value = addressData.latitude || addressData.y || '';
            console.log('🌍 위도 업데이트:', this.elements.latitudeField.value);
        }
        
        if (this.elements.longitudeField) {
            this.elements.longitudeField.value = addressData.longitude || addressData.x || '';
            console.log('🌍 경도 업데이트:', this.elements.longitudeField.value);
        }
        
        // 구역 정보 업데이트
        if (this.elements.districtField && addressData.district) {
            this.elements.districtField.value = addressData.district;
            console.log('🏘️ 구역 업데이트:', addressData.district);
        }
        
        // 지도 업데이트 (좌표가 있는 경우)
        const lat = addressData.latitude || addressData.y;
        const lng = addressData.longitude || addressData.x;
        
        if (this.map && lat && lng) {
            const position = new naver.maps.LatLng(parseFloat(lat), parseFloat(lng));
            this.updateMapLocation(position);
            console.log('🗺️ 지도 위치 업데이트:', lat, lng);
        }
        
        // 지하철역 자동 검색
        this.findNearestSubway();
        
        // 진행률 업데이트
        this.updateProgress();
    }
    
    /**
     * 가장 가까운 지하철역 검색
     */
    async findNearestSubway() {
        const latitude = this.elements.latitudeField?.value;
        const longitude = this.elements.longitudeField?.value;
        
        if (!latitude || !longitude) {
            return;
        }
        
        try {
            console.log('🚇 지하철역 검색 중...', latitude, longitude);
            
            const response = await fetch(sungsuyaEnhancedAdmin.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sungsuya_find_nearest_subway_enhanced',
                    latitude: latitude,
                    longitude: longitude,
                    nonce: sungsuyaEnhancedAdmin.nonce
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.updateSubwayInfo(data.data);
                console.log('✅ 지하철역 정보 업데이트 완료:', data.data);
            } else {
                console.warn('⚠️ 지하철역 검색 실패:', data.data);
                this.clearSubwayInfo();
            }
            
        } catch (error) {
            console.error('❌ 지하철역 검색 오류:', error);
            this.clearSubwayInfo();
        }
    }
    
    /**
     * 지하철역 정보 업데이트
     */
    updateSubwayInfo(subwayData) {
        // 표시용 필드 업데이트
        if (this.elements.nearestSubwayDisplay) {
            this.elements.nearestSubwayDisplay.value = subwayData.display_text || '';
        }
        
        if (this.elements.walkingTimeDisplay) {
            this.elements.walkingTimeDisplay.value = `도보 ${subwayData.walking_time}분`;
        }
        
        // 히든 필드 업데이트
        if (this.elements.nearestSubwayStation) {
            this.elements.nearestSubwayStation.value = subwayData.station_key || '';
        }
        
        if (this.elements.walkingTimeFromStation) {
            this.elements.walkingTimeFromStation.value = subwayData.walking_time_range || '';
        }
        
        if (this.elements.subwayExitInfo) {
            this.elements.subwayExitInfo.value = JSON.stringify({
                station_name: subwayData.station_name,
                line: subwayData.line,
                recommended_exit: subwayData.recommended_exit,
                exit_description: subwayData.exit_description,
                distance: subwayData.distance
            });
        }
        
        // 상태 표시 업데이트
        if (this.elements.subwayStatusIndicator) {
            this.elements.subwayStatusIndicator.textContent = '✅';
            this.elements.subwayStatusIndicator.className = 'status-indicator success';
        }
        
        if (this.elements.subwayExitDescription) {
            this.elements.subwayExitDescription.textContent = subwayData.exit_description || '';
        }
    }
    
    /**
     * 지하철역 정보 클리어
     */
    clearSubwayInfo() {
        if (this.elements.nearestSubwayDisplay) {
            this.elements.nearestSubwayDisplay.value = '주소 입력 시 자동으로 설정됩니다';
        }
        
        if (this.elements.walkingTimeDisplay) {
            this.elements.walkingTimeDisplay.value = '주소 입력 시 자동으로 계산됩니다';
        }
        
        if (this.elements.subwayStatusIndicator) {
            this.elements.subwayStatusIndicator.textContent = '⏳';
            this.elements.subwayStatusIndicator.className = 'status-indicator pending';
        }
        
        if (this.elements.subwayExitDescription) {
            this.elements.subwayExitDescription.textContent = '';
        }
    }
    
    /**
     * 네이버 지도 초기화
     */
    async initializeMap() {
        if (!this.elements.mapPreview || typeof naver === 'undefined') {
            console.warn('지도를 초기화할 수 없습니다.');
            return;
        }
        
        try {
            // 성수동 중심 좌표
            const sungsudongCenter = new naver.maps.LatLng(37.5447, 127.0557);
            
            // 지도 생성
            this.map = new naver.maps.Map(this.elements.mapPreview, {
                center: sungsudongCenter,
                zoom: 16,
                minZoom: 10,
                maxZoom: 19,
                mapTypeControl: true,
                scaleControl: true,
                logoControl: false,
                mapDataControl: false
            });
            
            // 마커 생성
            this.marker = new naver.maps.Marker({
                position: sungsudongCenter,
                map: this.map,
                draggable: false,
                title: '팝업스토어 위치'
            });
            
            console.log('✅ 네이버 지도 초기화 완료');
            
        } catch (error) {
            console.error('❌ 지도 초기화 오류:', error);
        }
    }
    
    /**
     * 지도 위치 업데이트
     */
    updateMapLocation(position) {
        if (!this.map || !this.marker) return;
        
        this.map.setCenter(position);
        this.map.setZoom(17);
        this.marker.setPosition(position);
    }
    
    /**
     * 성수동 중심으로 이동
     */
    centerToSungsudong() {
        if (!this.map) return;
        
        const sungsudongCenter = new naver.maps.LatLng(37.5447, 127.0557);
        this.map.setCenter(sungsudongCenter);
        this.map.setZoom(16);
        
        this.showMessage('🏠 성수동 중심으로 이동했습니다.', 'info');
    }
    
    /**
     * 수동 조정 모드 활성화
     */
    enableManualAdjustment() {
        if (!this.marker) return;
        
        this.marker.setDraggable(true);
        
        if (this.elements.manualAdjustBtn) {
            this.elements.manualAdjustBtn.textContent = '✅ 수동 조정 활성화됨';
            this.elements.manualAdjustBtn.disabled = true;
        }
        
        this.showMessage('🖱️ 마커를 드래그하여 정확한 위치를 설정하세요.', 'info');
        
        // 마커 드래그 이벤트
        naver.maps.Event.addListener(this.marker, 'dragend', (e) => {
            const position = e.coord;
            if (this.elements.latitudeField) {
                this.elements.latitudeField.value = position.lat();
            }
            if (this.elements.longitudeField) {
                this.elements.longitudeField.value = position.lng();
            }
            this.findNearestSubway();
            this.showMessage('📍 위치가 업데이트되었습니다.', 'success');
        });
    }
    
    /**
     * 기존 위치 로드
     */
    loadExistingLocation() {
        const lat = this.elements.latitudeField?.value;
        const lng = this.elements.longitudeField?.value;
        
        if (lat && lng && this.map && this.marker) {
            const position = new naver.maps.LatLng(parseFloat(lat), parseFloat(lng));
            this.updateMapLocation(position);
        }
    }
    
    /**
     * 투어 호환성 체크
     */
    async checkTourCompatibility() {
        const postId = document.getElementById('post_ID')?.value;
        
        if (!postId) {
            this.showMessage('포스트 ID를 찾을 수 없습니다.', 'error');
            return;
        }
        
        try {
            const response = await fetch(sungsuyaEnhancedAdmin.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sungsuya_validate_tour_data',
                    post_id: postId,
                    nonce: sungsuyaEnhancedAdmin.nonce
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.updateCompatibilityScore(data.data.score, data.data.status);
                this.showMessage('🎯 투어 호환성 검사가 완료되었습니다.', 'success');
            } else {
                this.showMessage('투어 호환성 검사 중 오류가 발생했습니다.', 'error');
            }
            
        } catch (error) {
            console.error('투어 호환성 검사 오류:', error);
            this.showMessage('투어 호환성 검사 중 오류가 발생했습니다.', 'error');
        }
    }
    
    /**
     * 호환성 점수 업데이트
     */
    updateCompatibilityScore(score, status) {
        if (this.elements.compatibilityScore) {
            this.elements.compatibilityScore.textContent = `${score}%`;
        }
        
        if (this.elements.statusDetails) {
            this.elements.statusDetails.innerHTML = `
                <div class="status-${status.class}">
                    <span class="status-icon">${status.icon}</span>
                    <span class="status-text">${status.text}</span>
                </div>
            `;
        }
    }
    
    /**
     * 전체 데이터 검증
     */
    validateAllData() {
        const requiredFields = document.querySelectorAll('.validation-required');
        let allValid = true;
        
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                allValid = false;
            }
        });
        
        if (allValid) {
            this.showMessage('✅ 모든 필수 데이터가 유효합니다.', 'success');
        } else {
            this.showMessage('❌ 일부 필수 데이터가 누락되었거나 유효하지 않습니다.', 'error');
        }
    }
    
    /**
     * 빠른 저장
     */
    quickSave() {
        // WordPress의 기본 저장 기능 트리거
        const saveButton = document.getElementById('publish') || document.getElementById('save-post');
        if (saveButton) {
            saveButton.click();
            this.showMessage('💾 저장 중...', 'info');
        }
    }
    
    /**
     * 투어에서 미리보기
     */
    previewInTour() {
        const postId = document.getElementById('post_ID')?.value;
        if (postId) {
            window.open(`${sungsuyaEnhancedAdmin.tourPlannerUrl}?preview=${postId}`, '_blank');
        }
    }
    
    /**
     * 투어 플래너 테스트
     */
    testInTourPlanner() {
        window.open(sungsuyaEnhancedAdmin.tourPlannerUrl, '_blank');
    }
    
    /**
     * 자동 저장 설정
     */
    setupAutoSave() {
        // 5분마다 자동 저장 (WordPress 기본)
        setInterval(() => {
            if (this.autoSaveEnabled) {
                this.performAutoSave();
            }
        }, 300000); // 5분
    }
    
    /**
     * 자동 저장 토글
     */
    toggleAutoSave(enabled) {
        this.autoSaveEnabled = enabled;
        
        if (this.elements.autoSaveStatus) {
            this.elements.autoSaveStatus.textContent = enabled ? 'ON' : 'OFF';
        }
        
        this.showMessage(enabled ? '🔄 자동 저장이 활성화되었습니다.' : '⏸️ 자동 저장이 비활성화되었습니다.', 'info');
    }
    
    /**
     * 자동 저장 실행
     */
    performAutoSave() {
        // WordPress 기본 자동 저장 기능 활용
        if (typeof autosave === 'function') {
            autosave();
            this.updateLastSavedTime();
        }
    }
    
    /**
     * 마지막 저장 시간 업데이트
     */
    updateLastSavedTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('ko-KR');
        
        if (this.elements.lastSavedTime) {
            this.elements.lastSavedTime.textContent = `마지막 저장: ${timeString}`;
        }
        
        this.lastSavedTime = now;
    }
    
    /**
     * 메시지 표시
     */
    showMessage(message, type = 'info', duration = 3000) {
        // 기존 메시지 제거
        const existingMessage = document.querySelector('.enhanced-admin-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // 새 메시지 생성
        const messageElement = document.createElement('div');
        messageElement.className = `enhanced-admin-message ${type}`;
        messageElement.textContent = message;
        
        // 메타박스 상단에 추가
        const metabox = document.getElementById('sungsuya-enhanced-metabox');
        if (metabox) {
            metabox.insertBefore(messageElement, metabox.firstChild);
        }
        
        // 자동 제거
        if (duration > 0) {
            setTimeout(() => {
                messageElement.remove();
            }, duration);
        }
        
        console.log(`💬 [${type}] ${message}`);
    }
}

// 전역으로 클래스 등록
window.SungsuyaEnhancedMetabox = SungsuyaEnhancedMetabox;
console.log('✅ SungsuyaEnhancedMetabox 클래스 등록 완료');

} // 중복 방지 if 문 종료

// 네이버 지도 API 콜백 함수 정의
function naverMapEnhancedAdminReady() {
    console.log('🗺️ 네이버 지도 API 로드 완료 - Enhanced Admin용');
    
    // SungsuyaEnhancedMetabox 인스턴스가 없으면 생성
    if (typeof window.sungsuyaEnhancedMetaboxInstance === 'undefined') {
        window.sungsuyaEnhancedMetaboxInstance = new window.SungsuyaEnhancedMetabox();
    }
}

// 전역 콜백 함수로 등록
if (typeof window.naverMapEnhancedAdminReady === 'undefined') {
    window.naverMapEnhancedAdminReady = naverMapEnhancedAdminReady;
}

// DOM 로드 시 즉시 실행 (네이버 지도 API가 아직 로드되지 않은 경우)
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.sungsuyaEnhancedMetaboxInstance === 'undefined') {
        console.log('🚀 DOM 로드 완료 - Enhanced Metabox 초기화');
        window.sungsuyaEnhancedMetaboxInstance = new window.SungsuyaEnhancedMetabox();
    }
});
