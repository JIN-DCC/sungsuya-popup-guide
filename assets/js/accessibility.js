/**
 * 접근성 개선 JavaScript
 * WCAG 2.1 준수를 위한 기능
 * 
 * @since 2025-07-02
 */

(function() {
    'use strict';

    /**
     * 접근성 매니저
     */
    const AccessibilityManager = {
        // 설정
        settings: window.accessibilitySettings || {
            focusOutlineEnabled: true,
            keyboardNavEnabled: true,
            ariaLiveEnabled: true,
            highContrastEnabled: false
        },

        // 초기화
        init() {
            this.setupKeyboardNavigation();
            this.setupAriaLive();
            this.setupHighContrast();
            this.setupTextResize();
            this.enhanceForms();
            this.enhanceModals();
            this.enhanceMenus();
            this.setupSkipLinks();
            this.checkImages();
            this.setupTooltips();
        },

        /**
         * 키보드 네비게이션 설정
         */
        setupKeyboardNavigation() {
            if (!this.settings.keyboardNavEnabled) return;

            // Tab 키 감지
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    document.body.classList.add('keyboard-navigation');
                }
            });

            // 마우스 클릭 시 키보드 네비게이션 클래스 제거
            document.addEventListener('mousedown', () => {
                document.body.classList.remove('keyboard-navigation');
            });

            // ESC 키로 모달/메뉴 닫기
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeOpenElements();
                }
            });

            // 화살표 키 네비게이션
            this.setupArrowKeyNavigation();
        },

        /**
         * 화살표 키 네비게이션
         */
        setupArrowKeyNavigation() {
            // 메뉴 네비게이션
            document.querySelectorAll('.menu').forEach(menu => {
                const items = menu.querySelectorAll('a');
                
                items.forEach((item, index) => {
                    item.addEventListener('keydown', (e) => {
                        switch(e.key) {
                            case 'ArrowDown':
                                e.preventDefault();
                                const next = items[index + 1] || items[0];
                                next.focus();
                                break;
                            case 'ArrowUp':
                                e.preventDefault();
                                const prev = items[index - 1] || items[items.length - 1];
                                prev.focus();
                                break;
                            case 'Home':
                                e.preventDefault();
                                items[0].focus();
                                break;
                            case 'End':
                                e.preventDefault();
                                items[items.length - 1].focus();
                                break;
                        }
                    });
                });
            });
        },

        /**
         * ARIA Live 영역 설정
         */
        setupAriaLive() {
            if (!this.settings.ariaLiveEnabled) return;

            // 알림 영역 생성
            const liveRegion = document.createElement('div');
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.className = 'aria-notification';
            document.body.appendChild(liveRegion);

            // 전역 알림 함수
            window.announceToScreenReader = (message, priority = 'polite') => {
                liveRegion.setAttribute('aria-live', priority);
                liveRegion.textContent = message;
                
                // 메시지 지우기
                setTimeout(() => {
                    liveRegion.textContent = '';
                }, 1000);
            };
        },

        /**
         * 고대비 모드
         */
        setupHighContrast() {
            // 저장된 설정 확인
            const savedContrast = localStorage.getItem('highContrastMode');
            if (savedContrast === 'true' || this.settings.highContrastEnabled) {
                document.body.classList.add('high-contrast');
            }

            // 토글 버튼 생성
            const toggleButton = document.createElement('button');
            toggleButton.className = 'high-contrast-toggle';
            toggleButton.innerHTML = '<span class="screen-reader-text">고대비 모드 전환</span>🌓';
            toggleButton.setAttribute('aria-pressed', document.body.classList.contains('high-contrast'));
            
            toggleButton.addEventListener('click', () => {
                const isEnabled = document.body.classList.toggle('high-contrast');
                toggleButton.setAttribute('aria-pressed', isEnabled);
                localStorage.setItem('highContrastMode', isEnabled);
                
                if (window.announceToScreenReader) {
                    window.announceToScreenReader(isEnabled ? '고대비 모드 활성화' : '고대비 모드 비활성화');
                }
            });

            // 페이지에 추가
            const header = document.querySelector('header') || document.body;
            header.appendChild(toggleButton);
        },

        /**
         * 텍스트 크기 조절
         */
        setupTextResize() {
            // 저장된 설정 확인
            const savedSize = localStorage.getItem('textSize') || 'medium';
            document.body.className = document.body.className.replace(/text-size-\w+/, '');
            document.body.classList.add('text-size-' + savedSize);

            // 컨트롤 생성
            const controls = document.createElement('div');
            controls.className = 'text-size-controls';
            controls.innerHTML = `
                <button class="text-size-decrease" aria-label="텍스트 크기 줄이기">A-</button>
                <button class="text-size-reset" aria-label="텍스트 크기 초기화">A</button>
                <button class="text-size-increase" aria-label="텍스트 크기 늘리기">A+</button>
            `;

            const sizes = ['small', 'medium', 'large', 'xlarge'];
            let currentSizeIndex = sizes.indexOf(savedSize);

            controls.querySelector('.text-size-decrease').addEventListener('click', () => {
                if (currentSizeIndex > 0) {
                    currentSizeIndex--;
                    this.setTextSize(sizes[currentSizeIndex]);
                }
            });

            controls.querySelector('.text-size-increase').addEventListener('click', () => {
                if (currentSizeIndex < sizes.length - 1) {
                    currentSizeIndex++;
                    this.setTextSize(sizes[currentSizeIndex]);
                }
            });

            controls.querySelector('.text-size-reset').addEventListener('click', () => {
                currentSizeIndex = 1; // medium
                this.setTextSize('medium');
            });

            // 페이지에 추가
            const header = document.querySelector('header') || document.body;
            header.appendChild(controls);
        },

        /**
         * 텍스트 크기 설정
         */
        setTextSize(size) {
            document.body.className = document.body.className.replace(/text-size-\w+/, '');
            document.body.classList.add('text-size-' + size);
            localStorage.setItem('textSize', size);
            
            if (window.announceToScreenReader) {
                const sizeNames = {
                    small: '작게',
                    medium: '보통',
                    large: '크게',
                    xlarge: '매우 크게'
                };
                window.announceToScreenReader('텍스트 크기: ' + sizeNames[size]);
            }
        },

        /**
         * 폼 개선
         */
        enhanceForms() {
            // 모든 폼 요소 찾기
            document.querySelectorAll('form').forEach(form => {
                // 필수 필드 표시
                form.querySelectorAll('[required]').forEach(field => {
                    const label = form.querySelector(`label[for="${field.id}"]`);
                    if (label && !label.querySelector('.required')) {
                        label.innerHTML += ' <span class="required" aria-label="필수">*</span>';
                    }
                });

                // 에러 메시지 연결
                form.querySelectorAll('.form-error').forEach(error => {
                    const fieldId = error.getAttribute('data-for');
                    if (fieldId) {
                        const field = document.getElementById(fieldId);
                        if (field) {
                            error.id = error.id || 'error-' + fieldId;
                            field.setAttribute('aria-describedby', error.id);
                            field.setAttribute('aria-invalid', 'true');
                        }
                    }
                });

                // 폼 제출 시 유효성 검사
                form.addEventListener('submit', (e) => {
                    const firstError = form.querySelector(':invalid');
                    if (firstError) {
                        e.preventDefault();
                        firstError.focus();
                        
                        if (window.announceToScreenReader) {
                            window.announceToScreenReader('폼에 오류가 있습니다. 필수 필드를 확인해주세요.');
                        }
                    }
                });
            });

            // 자동완성 속성 추가
            this.addAutocompleteAttributes();
        },

        /**
         * 자동완성 속성 추가
         */
        addAutocompleteAttributes() {
            const autocompleteMap = {
                'email': 'email',
                'phone': 'tel',
                'name': 'name',
                'firstname': 'given-name',
                'lastname': 'family-name',
                'address': 'street-address',
                'city': 'address-level2',
                'zip': 'postal-code',
                'country': 'country'
            };

            Object.keys(autocompleteMap).forEach(key => {
                document.querySelectorAll(`input[name*="${key}"]`).forEach(input => {
                    if (!input.getAttribute('autocomplete')) {
                        input.setAttribute('autocomplete', autocompleteMap[key]);
                    }
                });
            });
        },

        /**
         * 모달 개선
         */
        enhanceModals() {
            document.querySelectorAll('.modal').forEach(modal => {
                // 모달 열기 버튼
                const triggers = document.querySelectorAll(`[data-modal="${modal.id}"]`);
                triggers.forEach(trigger => {
                    trigger.addEventListener('click', () => this.openModal(modal));
                });

                // 닫기 버튼
                const closeButtons = modal.querySelectorAll('.modal-close, [data-dismiss="modal"]');
                closeButtons.forEach(button => {
                    button.addEventListener('click', () => this.closeModal(modal));
                });

                // 배경 클릭으로 닫기
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        this.closeModal(modal);
                    }
                });

                // 포커스 트랩
                this.setupFocusTrap(modal);
            });
        },

        /**
         * 모달 열기
         */
        openModal(modal) {
            modal.setAttribute('aria-hidden', 'false');
            modal.style.display = 'block';
            
            // 이전 포커스 저장
            modal._previousFocus = document.activeElement;
            
            // 첫 번째 포커스 가능한 요소로 포커스
            const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (firstFocusable) {
                firstFocusable.focus();
            }
            
            // 배경 스크롤 방지
            document.body.style.overflow = 'hidden';
        },

        /**
         * 모달 닫기
         */
        closeModal(modal) {
            modal.setAttribute('aria-hidden', 'true');
            modal.style.display = 'none';
            
            // 이전 포커스로 복귀
            if (modal._previousFocus) {
                modal._previousFocus.focus();
            }
            
            // 배경 스크롤 복원
            document.body.style.overflow = '';
        },

        /**
         * 포커스 트랩 설정
         */
        setupFocusTrap(container) {
            container.addEventListener('keydown', (e) => {
                if (e.key !== 'Tab') return;
                
                const focusables = container.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                const firstFocusable = focusables[0];
                const lastFocusable = focusables[focusables.length - 1];
                
                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        e.preventDefault();
                        lastFocusable.focus();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        e.preventDefault();
                        firstFocusable.focus();
                    }
                }
            });
        },

        /**
         * 메뉴 개선
         */
        enhanceMenus() {
            document.querySelectorAll('.menu-item-has-children').forEach(item => {
                const link = item.querySelector('a');
                const submenu = item.querySelector('.sub-menu');
                
                if (link && submenu) {
                    link.setAttribute('aria-haspopup', 'true');
                    link.setAttribute('aria-expanded', 'false');
                    
                    // 키보드로 열기/닫기
                    link.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            const isExpanded = link.getAttribute('aria-expanded') === 'true';
                            link.setAttribute('aria-expanded', !isExpanded);
                            submenu.style.display = isExpanded ? 'none' : 'block';
                        }
                    });
                }
            });
        },

        /**
         * Skip Links 설정
         */
        setupSkipLinks() {
            const skipLinks = document.querySelectorAll('.skip-link');
            
            skipLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const target = document.querySelector(link.getAttribute('href'));
                    if (target) {
                        target.tabIndex = -1;
                        target.focus();
                        target.scrollIntoView();
                    }
                });
            });
        },

        /**
         * 이미지 체크
         */
        checkImages() {
            document.querySelectorAll('img').forEach(img => {
                if (!img.alt) {
                    if (window.debugLog) {
                        window.debugLog('이미지에 alt 텍스트가 없습니다:', img.src);
                    }
                    img.alt = ''; // 장식용 이미지로 처리
                }
            });
        },

        /**
         * 툴팁 설정
         */
        setupTooltips() {
            document.querySelectorAll('[data-tooltip]').forEach(element => {
                const tooltipText = element.getAttribute('data-tooltip');
                const tooltip = document.createElement('span');
                tooltip.className = 'tooltip';
                tooltip.setAttribute('role', 'tooltip');
                tooltip.textContent = tooltipText;
                tooltip.id = 'tooltip-' + Math.random().toString(36).substr(2, 9);
                
                element.setAttribute('aria-describedby', tooltip.id);
                element.appendChild(tooltip);
                
                // 포커스/호버 시 표시
                element.addEventListener('mouseenter', () => tooltip.style.display = 'block');
                element.addEventListener('mouseleave', () => tooltip.style.display = 'none');
                element.addEventListener('focus', () => tooltip.style.display = 'block');
                element.addEventListener('blur', () => tooltip.style.display = 'none');
            });
        },

        /**
         * 열린 요소 닫기
         */
        closeOpenElements() {
            // 열린 모달 닫기
            document.querySelectorAll('.modal[aria-hidden="false"]').forEach(modal => {
                this.closeModal(modal);
            });
            
            // 열린 메뉴 닫기
            document.querySelectorAll('[aria-expanded="true"]').forEach(element => {
                element.setAttribute('aria-expanded', 'false');
            });
        }
    };

    // DOM 준비 시 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            AccessibilityManager.init();
        });
    } else {
        AccessibilityManager.init();
    }

    // 전역 노출
    window.AccessibilityManager = AccessibilityManager;

})();
