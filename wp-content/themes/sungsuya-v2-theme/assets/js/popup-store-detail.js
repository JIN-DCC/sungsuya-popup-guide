/**
 * 팝업스토어 상세페이지 JavaScript (개선 버전)
 * 지도, 인터랙션, 접근성 기능 통합
 */

class PopupStoreDetail {
    constructor() {
        this.map = null;
        this.marker = null;
        this.infoWindow = null;
        this.isFullscreen = false;
        this.bookmarks = this.getBookmarks();
        
        this.init();
    }
    
    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initMap();
            this.initInteractions();
            this.initAccessibility();
            this.initAnimations();
            this.checkBookmarkStatus();
        });
    }
    
    /**
     * 지도 초기화
     */
    initMap() {
        const mapElement = document.getElementById('store-map');
        if (!mapElement) return;
        
        // 네이버 지도 API 로드 확인
        this.waitForNaverMaps().then(() => {
            this.createMap();
        }).catch(error => {
            console.error('지도 로드 실패:', error);
            this.showMapError();
        });
    }
    
    /**
     * 네이버 지도 API 로드 대기
     */
    waitForNaverMaps() {
        return new Promise((resolve, reject) => {
            let attempts = 0;
            const maxAttempts = 50;
            
            const checkAPI = () => {
                attempts++;
                
                if (typeof naver !== 'undefined' && 
                    typeof naver.maps !== 'undefined' && 
                    typeof naver.maps.Map !== 'undefined') {
                    console.log('✅ 네이버 지도 API 로드 완료');
                    resolve();
                    return;
                }
                
                if (attempts < maxAttempts) {
                    setTimeout(checkAPI, 100);
                } else {
                    reject(new Error('네이버 지도 API 로드 시간 초과'));
                }
            };
            
            checkAPI();
        });
    }
    
    /**
     * 지도 생성
     */
    createMap() {
        try {
            const mapElement = document.getElementById('store-map');
            const lat = parseFloat(mapElement.dataset.lat);
            const lng = parseFloat(mapElement.dataset.lng);
            const title = mapElement.dataset.title;
            const address = mapElement.dataset.address;
            
            // 지도 옵션
            const mapOptions = {
                center: new naver.maps.LatLng(lat, lng),
                zoom: 16,
                mapTypeControl: false,
                mapDataControl: false,
                logoControl: false,
                scaleControl: true,
                zoomControl: false,
                mapTypeId: naver.maps.MapTypeId.NORMAL
            };
            
            // 지도 생성
            this.map = new naver.maps.Map('store-map', mapOptions);
            
            // 마커 생성
            this.createMarker(lat, lng, title, address);
            
            // 지도 컨트롤 바인딩
            this.bindMapControls();
            
            // 지도 이벤트 리스너
            this.addMapEventListeners();
            
            // 로딩 표시 제거
            this.hideMapLoading();
            
            console.log('✅ 지도 초기화 완료');
            
        } catch (error) {
            console.error('❌ 지도 생성 오류:', error);
            this.showMapError();
        }
    }
    
    /**
     * 마커 생성
     */
    createMarker(lat, lng, title, address) {
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
        this.marker = new naver.maps.Marker({
            position: new naver.maps.LatLng(lat, lng),
            map: this.map,
            title: title,
            icon: markerIcon
        });
        
        // 정보창 생성
        this.infoWindow = new naver.maps.InfoWindow({
            content: this.createInfoWindowContent(title, address, lng, lat),
            anchorSkew: true,
            borderWidth: 0,
            disableAnchor: false
        });
        
        // 마커 클릭 이벤트
        naver.maps.Event.addListener(this.marker, 'click', () => {
            this.toggleInfoWindow();
        });
    }
    
    /**
     * 정보창 내용 생성
     */
    createInfoWindowContent(title, address, lng, lat) {
        return `
            <div class="info-window">
                <h4 class="info-title">${title}</h4>
                <p class="info-address">${address}</p>
                <div class="info-window-actions">
                    <a href="https://map.naver.com/p/directions/-/-/-/car?c=${lng},${lat},15,0,0,0,dh" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="directions-link">
                        🚗 길찾기
                    </a>
                </div>
            </div>
        `;
    }
    
    /**
     * 정보창 토글
     */
    toggleInfoWindow() {
        if (this.infoWindow.getMap()) {
            this.infoWindow.close();
        } else {
            this.infoWindow.open(this.map, this.marker);
        }
    }
    
    /**
     * 지도 컨트롤 바인딩
     */
    bindMapControls() {
        const zoomInBtn = document.getElementById('map-zoom-in');
        const zoomOutBtn = document.getElementById('map-zoom-out');
        const centerBtn = document.getElementById('map-center');
        const fullscreenBtn = document.getElementById('map-fullscreen');
        
        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', () => {
                if (this.map) {
                    this.map.setZoom(this.map.getZoom() + 1);
                }
            });
        }
        
        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', () => {
                if (this.map) {
                    this.map.setZoom(this.map.getZoom() - 1);
                }
            });
        }
        
        if (centerBtn) {
            centerBtn.addEventListener('click', () => {
                if (this.map && this.marker) {
                    this.map.setCenter(this.marker.getPosition());
                    this.map.setZoom(16);
                }
            });
        }
        
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', () => {
                this.toggleFullscreen();
            });
        }
    }
    
    /**
     * 지도 이벤트 리스너
     */
    addMapEventListeners() {
        // 지도 클릭 시 정보창 닫기
        naver.maps.Event.addListener(this.map, 'click', () => {
            if (this.infoWindow) {
                this.infoWindow.close();
            }
        });
        
        // 지도 로딩 완료
        naver.maps.Event.addListener(this.map, 'idle', () => {
            this.hideMapLoading();
        });
    }
    
    /**
     * 전체화면 토글
     */
    toggleFullscreen() {
        const mapContainer = document.querySelector('.map-container');
        if (!mapContainer) return;
        
        this.isFullscreen = !this.isFullscreen;
        mapContainer.classList.toggle('fullscreen', this.isFullscreen);
        
        // 지도 크기 재조정
        setTimeout(() => {
            if (this.map) {
                this.map.refresh();
            }
        }, 300);
        
        // ESC 키로 전체화면 해제
        if (this.isFullscreen) {
            document.addEventListener('keydown', this.handleFullscreenEscape.bind(this));
        }
    }
    
    /**
     * 전체화면 ESC 처리
     */
    handleFullscreenEscape(e) {
        if (e.key === 'Escape' && this.isFullscreen) {
            this.toggleFullscreen();
            document.removeEventListener('keydown', this.handleFullscreenEscape);
        }
    }
    
    /**
     * 지도 로딩 표시 숨기기
     */
    hideMapLoading() {
        const loadingElement = document.querySelector('.map-loading');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }
    
    /**
     * 지도 오류 표시
     */
    showMapError() {
        this.hideMapLoading();
        const errorElement = document.querySelector('.map-error');
        if (errorElement) {
            errorElement.style.display = 'flex';
        }
    }
    
    /**
     * 인터랙션 초기화
     */
    initInteractions() {
        // 주소 복사 기능
        this.initAddressCopy();
        
        // 공유 기능
        this.initShareFeatures();
        
        // 북마크 기능
        this.initBookmarkFeatures();
        
        // 키보드 네비게이션
        this.initKeyboardNavigation();
        
        // 터치 제스처
        this.initTouchGestures();
        
        // 스크롤 효과
        this.initScrollEffects();
    }
    
    /**
     * 주소 복사 기능
     */
    initAddressCopy() {
        const copyBtn = document.querySelector('.copy-address-btn');
        if (!copyBtn) return;
        
        copyBtn.addEventListener('click', () => {
            const address = copyBtn.dataset.address;
            
            if (!address || address.trim() === '') {
                this.showToast('복사할 주소가 없습니다', 'warning');
                return;
            }
            
            this.copyToClipboard(address).then(() => {
                this.showToast('주소가 복사되었습니다', 'success');
            }).catch(() => {
                this.showToast('복사에 실패했습니다', 'error');
            });
        });
    }
    
    /**
     * 클립보드 복사
     */
    async copyToClipboard(text) {
        if (navigator.clipboard) {
            return navigator.clipboard.writeText(text);
        } else {
            return this.fallbackCopyText(text);
        }
    }
    
    /**
     * 구형 브라우저 복사 지원
     */
    fallbackCopyText(text) {
        return new Promise((resolve, reject) => {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                if (successful) {
                    resolve();
                } else {
                    reject(new Error('복사 명령 실패'));
                }
            } catch (err) {
                document.body.removeChild(textArea);
                reject(err);
            }
        });
    }
    
    /**
     * 공유 기능 초기화
     */
    initShareFeatures() {
        const shareBtn = document.querySelector('.share-btn');
        if (!shareBtn) return;
        
        shareBtn.addEventListener('click', () => {
            this.shareStore();
        });
    }
    
    /**
     * 스토어 공유
     */
    async shareStore() {
        const storeTitle = document.querySelector('.store-title')?.textContent || '성수야 팝업스토어';
        const storeUrl = window.location.href;
        const shareData = {
            title: `${storeTitle} - 성수야`,
            text: '성수동의 특별한 팝업스토어를 확인해보세요!',
            url: storeUrl
        };
        
        if (navigator.share && navigator.canShare && navigator.canShare(shareData)) {
            try {
                await navigator.share(shareData);
                this.showToast('공유되었습니다', 'success');
            } catch (error) {
                if (error.name !== 'AbortError') {
                    this.fallbackShare(storeUrl);
                }
            }
        } else {
            this.fallbackShare(storeUrl);
        }
    }
    
    /**
     * 공유 대안 (URL 복사)
     */
    async fallbackShare(url) {
        try {
            await this.copyToClipboard(url);
            this.showToast('링크가 복사되었습니다', 'success');
        } catch (error) {
            this.showToast('공유에 실패했습니다', 'error');
        }
    }
    
    /**
     * 북마크 기능 초기화
     */
    initBookmarkFeatures() {
        const bookmarkBtn = document.querySelector('.bookmark-btn');
        if (!bookmarkBtn) return;
        
        bookmarkBtn.addEventListener('click', () => {
            const storeId = this.getStoreId();
            if (storeId) {
                this.toggleBookmark(storeId);
            }
        });
    }
    
    /**
     * 스토어 ID 가져오기
     */
    getStoreId() {
        const bodyClasses = document.body.className;
        const postIdMatch = bodyClasses.match(/postid-(\d+)/);
        return postIdMatch ? parseInt(postIdMatch[1]) : null;
    }
    
    /**
     * 북마크 토글
     */
    toggleBookmark(storeId) {
        const isBookmarked = this.bookmarks.includes(storeId);
        const bookmarkBtn = document.querySelector('.bookmark-btn');
        
        if (isBookmarked) {
            this.bookmarks = this.bookmarks.filter(id => id !== storeId);
            bookmarkBtn?.classList.remove('bookmarked');
            this.showToast('북마크에서 제거되었습니다', 'info');
        } else {
            this.bookmarks.push(storeId);
            bookmarkBtn?.classList.add('bookmarked');
            this.showToast('북마크에 추가되었습니다', 'success');
        }
        
        this.saveBookmarks();
    }
    
    /**
     * 북마크 상태 확인
     */
    checkBookmarkStatus() {
        const storeId = this.getStoreId();
        const bookmarkBtn = document.querySelector('.bookmark-btn');
        
        if (storeId && this.bookmarks.includes(storeId) && bookmarkBtn) {
            bookmarkBtn.classList.add('bookmarked');
        }
    }
    
    /**
     * 북마크 불러오기
     */
    getBookmarks() {
        try {
            const saved = localStorage.getItem('sungsuya_bookmarks');
            return saved ? JSON.parse(saved) : [];
        } catch (error) {
            console.warn('북마크 불러오기 실패:', error);
            return [];
        }
    }
    
    /**
     * 북마크 저장
     */
    saveBookmarks() {
        try {
            localStorage.setItem('sungsuya_bookmarks', JSON.stringify(this.bookmarks));
        } catch (error) {
            console.warn('북마크 저장 실패:', error);
        }
    }
    
    /**
     * 키보드 네비게이션
     */
    initKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            // 입력 필드에서는 무시
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }
            
            switch(e.key) {
                case 'ArrowLeft':
                    e.preventDefault();
                    this.navigateToPrevStore();
                    break;
                    
                case 'ArrowRight':
                    e.preventDefault();
                    this.navigateToNextStore();
                    break;
                    
                case 'Escape':
                    e.preventDefault();
                    if (this.isFullscreen) {
                        this.toggleFullscreen();
                    } else {
                        this.navigateToStoreList();
                    }
                    break;
                    
                case 's':
                case 'S':
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        this.shareStore();
                    }
                    break;
                    
                case 'b':
                case 'B':
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        const storeId = this.getStoreId();
                        if (storeId) {
                            this.toggleBookmark(storeId);
                        }
                    }
                    break;
            }
        });
        
        // 키보드 힌트 표시
        this.showKeyboardHints();
    }
    
    /**
     * 이전 스토어로 이동
     */
    navigateToPrevStore() {
        const prevLink = document.querySelector('.nav-prev .nav-link:not(.nav-disabled)');
        if (prevLink) {
            window.location.href = prevLink.href;
        }
    }
    
    /**
     * 다음 스토어로 이동
     */
    navigateToNextStore() {
        const nextLink = document.querySelector('.nav-next .nav-link:not(.nav-disabled)');
        if (nextLink) {
            window.location.href = nextLink.href;
        }
    }
    
    /**
     * 스토어 목록으로 이동
     */
    navigateToStoreList() {
        const homeLink = document.querySelector('.nav-home-link') || 
                        document.querySelector('.back-btn');
        if (homeLink) {
            window.location.href = homeLink.href;
        }
    }
    
    /**
     * 터치 제스처
     */
    initTouchGestures() {
        let touchStartX = 0;
        let touchEndX = 0;
        
        document.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        document.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe();
        }, { passive: true });
        
        const handleSwipe = () => {
            const swipeThreshold = 50;
            const swipeDistance = touchEndX - touchStartX;
            
            if (Math.abs(swipeDistance) > swipeThreshold) {
                if (swipeDistance > 0) {
                    // 오른쪽 스와이프 - 이전 스토어
                    this.navigateToPrevStore();
                } else {
                    // 왼쪽 스와이프 - 다음 스토어
                    this.navigateToNextStore();
                }
            }
        };
        
        this.handleSwipe = handleSwipe;
    }
    
    /**
     * 스크롤 효과
     */
    initScrollEffects() {
        // 스크롤 진행도
        this.createScrollProgress();
        
        // 패럴랙스 효과
        this.initParallaxEffects();
        
        // 스크롤 시 헤더 숨김/표시
        this.initScrollHeader();
    }
    
    /**
     * 스크롤 진행도
     */
    createScrollProgress() {
        const progressBar = document.createElement('div');
        progressBar.className = 'scroll-progress';
        progressBar.innerHTML = '<div class="progress-fill"></div>';
        document.body.appendChild(progressBar);
        
        let ticking = false;
        
        const updateProgress = () => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = height > 0 ? (winScroll / height) * 100 : 0;
            
            const progressFill = document.querySelector('.progress-fill');
            if (progressFill) {
                progressFill.style.width = `${scrolled}%`;
            }
            
            ticking = false;
        };
        
        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(updateProgress);
                ticking = true;
            }
        }, { passive: true });
    }
    
    /**
     * 패럴랙스 효과
     */
    initParallaxEffects() {
        const parallaxElements = document.querySelectorAll('.store-header');
        
        if (parallaxElements.length === 0) return;
        
        let ticking = false;
        
        const updateParallax = () => {
            const scrolled = window.pageYOffset;
            
            parallaxElements.forEach(element => {
                const rate = scrolled * -0.5;
                element.style.transform = `translateY(${rate}px)`;
            });
            
            ticking = false;
        };
        
        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(updateParallax);
                ticking = true;
            }
        }, { passive: true });
    }
    
    /**
     * 스크롤 헤더
     */
    initScrollHeader() {
        let lastScrollTop = 0;
        const scrollThreshold = 100;
        
        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > scrollThreshold) {
                if (scrollTop > lastScrollTop) {
                    // 스크롤 다운 - 헤더 숨김
                    document.body.classList.add('scroll-down');
                } else {
                    // 스크롤 업 - 헤더 표시
                    document.body.classList.remove('scroll-down');
                }
            }
            
            lastScrollTop = scrollTop;
        }, { passive: true });
    }
    
    /**
     * 접근성 초기화
     */
    initAccessibility() {
        // 포커스 관리
        this.initFocusManagement();
        
        // 스크린 리더 지원
        this.initScreenReaderSupport();
        
        // 고대비 모드
        this.initHighContrastMode();
    }
    
    /**
     * 포커스 관리
     */
    initFocusManagement() {
        // 스킵 링크
        this.createSkipLinks();
        
        // 포커스 트랩 (모달/전체화면)
        this.initFocusTrap();
    }
    
    /**
     * 스킵 링크 생성
     */
    createSkipLinks() {
        const skipLinks = document.createElement('div');
        skipLinks.className = 'skip-links sr-only';
        skipLinks.innerHTML = `
            <a href="#main-content" class="skip-link">메인 컨텐츠로 이동</a>
            <a href="#store-map" class="skip-link">지도로 이동</a>
            <a href="#related-stores" class="skip-link">관련 스토어로 이동</a>
        `;
        
        document.body.insertBefore(skipLinks, document.body.firstChild);
        
        // 스킵 링크 스타일
        const style = document.createElement('style');
        style.textContent = `
            .skip-link {
                position: absolute;
                top: -40px;
                left: 6px;
                background: var(--primary-color, #2563eb);
                color: white;
                padding: 8px;
                text-decoration: none;
                border-radius: 4px;
                z-index: 10000;
            }
            .skip-link:focus {
                top: 6px;
            }
        `;
        document.head.appendChild(style);
    }
    
    /**
     * 포커스 트랩
     */
    initFocusTrap() {
        // 전체화면 지도에서 포커스 트랩
        document.addEventListener('keydown', (e) => {
            if (this.isFullscreen && e.key === 'Tab') {
                const focusableElements = document.querySelectorAll(
                    '.map-container.fullscreen button, .map-container.fullscreen a'
                );
                
                if (focusableElements.length > 0) {
                    const firstElement = focusableElements[0];
                    const lastElement = focusableElements[focusableElements.length - 1];
                    
                    if (e.shiftKey) {
                        if (document.activeElement === firstElement) {
                            e.preventDefault();
                            lastElement.focus();
                        }
                    } else {
                        if (document.activeElement === lastElement) {
                            e.preventDefault();
                            firstElement.focus();
                        }
                    }
                }
            }
        });
    }
    
    /**
     * 스크린 리더 지원
     */
    initScreenReaderSupport() {
        // ARIA 라이브 영역
        this.createAriaLiveRegion();
        
        // 동적 콘텐츠 알림
        this.announceToScreenReader = (message) => {
            const liveRegion = document.getElementById('aria-live-region');
            if (liveRegion) {
                liveRegion.textContent = message;
                setTimeout(() => {
                    liveRegion.textContent = '';
                }, 1000);
            }
        };
    }
    
    /**
     * ARIA 라이브 영역 생성
     */
    createAriaLiveRegion() {
        const liveRegion = document.createElement('div');
        liveRegion.id = 'aria-live-region';
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.className = 'sr-only';
        document.body.appendChild(liveRegion);
    }
    
    /**
     * 고대비 모드
     */
    initHighContrastMode() {
        // 시스템 고대비 모드 감지
        if (window.matchMedia('(prefers-contrast: high)').matches) {
            document.body.classList.add('high-contrast');
        }
        
        // 수동 고대비 모드 토글 (옵션)
        const contrastToggle = document.createElement('button');
        contrastToggle.className = 'contrast-toggle sr-only';
        contrastToggle.textContent = '고대비 모드 토글';
        contrastToggle.addEventListener('click', () => {
            document.body.classList.toggle('high-contrast');
        });
        
        // 키보드 단축키 (Ctrl + Alt + C)
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.altKey && e.key === 'c') {
                e.preventDefault();
                document.body.classList.toggle('high-contrast');
                this.announceToScreenReader?.('고대비 모드가 ' + 
                    (document.body.classList.contains('high-contrast') ? '활성화' : '비활성화') + 
                    '되었습니다.');
            }
        });
    }
    
    /**
     * 애니메이션 초기화
     */
    initAnimations() {
        // Intersection Observer for scroll animations
        this.initScrollAnimations();
        
        // 카드 호버 효과
        this.initCardHoverEffects();
        
        // 버튼 리플 효과
        this.initRippleEffects();
    }
    
    /**
     * 스크롤 애니메이션
     */
    initScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('section-visible');
                    
                    // 스크린 리더 알림
                    const sectionTitle = entry.target.querySelector('.section-title');
                    if (sectionTitle) {
                        this.announceToScreenReader?.(`${sectionTitle.textContent} 섹션이 표시되었습니다.`);
                    }
                }
            });
        }, observerOptions);
        
        const sections = document.querySelectorAll('.store-info-section, .store-location-section, .store-transport-section, .store-description-section, .related-stores-section');
        sections.forEach(section => {
            section.classList.add('section-observe');
            observer.observe(section);
        });
    }
    
    /**
     * 카드 호버 효과
     */
    initCardHoverEffects() {
        const cards = document.querySelectorAll('.related-store-card, .info-item, .transport-item');
        
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    card.style.transform = 'translateY(-4px) scale(1.02)';
                }
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    }
    
    /**
     * 리플 효과
     */
    initRippleEffects() {
        const buttons = document.querySelectorAll('.action-btn, .map-control-btn, .copy-address-btn, .naver-map-link, .directions-link');
        
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return;
                }
                
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255, 255, 255, 0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
        
        // 리플 애니메이션 CSS 추가
        if (!document.getElementById('ripple-styles')) {
            const style = document.createElement('style');
            style.id = 'ripple-styles';
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    /**
     * 키보드 힌트 표시
     */
    showKeyboardHints() {
        // 접근성을 위해 처음 방문 시에만 표시
        if (sessionStorage.getItem('keyboard-hints-shown')) {
            return;
        }
        
        const hints = document.createElement('div');
        hints.className = 'keyboard-hints';
        hints.setAttribute('role', 'alert');
        hints.setAttribute('aria-live', 'polite');
        hints.innerHTML = `
            <div class="hints-content">
                <h4>키보드 단축키</h4>
                <ul>
                    <li><kbd>←</kbd> <span>이전 스토어</span></li>
                    <li><kbd>→</kbd> <span>다음 스토어</span></li>
                    <li><kbd>ESC</kbd> <span>스토어 목록</span></li>
                    <li><kbd>Ctrl+S</kbd> <span>공유하기</span></li>
                    <li><kbd>Ctrl+B</kbd> <span>북마크</span></li>
                </ul>
                <button class="hints-close" aria-label="힌트 닫기">×</button>
            </div>
        `;
        
        document.body.appendChild(hints);
        
        // 힌트 닫기 버튼
        const closeBtn = hints.querySelector('.hints-close');
        closeBtn.addEventListener('click', () => {
            hints.classList.remove('hints-show');
            setTimeout(() => {
                if (document.body.contains(hints)) {
                    document.body.removeChild(hints);
                }
            }, 300);
        });
        
        // 3초 후 표시, 8초 후 자동 숨김
        setTimeout(() => {
            hints.classList.add('hints-show');
            sessionStorage.setItem('keyboard-hints-shown', 'true');
            
            setTimeout(() => {
                if (hints.classList.contains('hints-show')) {
                    hints.classList.remove('hints-show');
                    setTimeout(() => {
                        if (document.body.contains(hints)) {
                            document.body.removeChild(hints);
                        }
                    }, 300);
                }
            }, 8000);
        }, 1000);
    }
    
    /**
     * 토스트 메시지 표시
     */
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.innerHTML = `
            <span class="toast-icon">${this.getToastIcon(type)}</span>
            <span class="toast-message">${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        // 스크린 리더 알림
        this.announceToScreenReader?.(message);
        
        // 애니메이션
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }
    
    /**
     * 토스트 아이콘 반환
     */
    getToastIcon(type) {
        const icons = {
            success: '✅',
            warning: '⚠️',
            error: '❌',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    }
}

// 전역에서 접근 가능하도록 함수들 추가
window.shareStore = function() {
    if (window.popupStoreDetail) {
        window.popupStoreDetail.shareStore();
    }
};

window.toggleBookmark = function(storeId) {
    if (window.popupStoreDetail) {
        window.popupStoreDetail.toggleBookmark(storeId);
    }
};

// 초기화
window.popupStoreDetail = new PopupStoreDetail();
