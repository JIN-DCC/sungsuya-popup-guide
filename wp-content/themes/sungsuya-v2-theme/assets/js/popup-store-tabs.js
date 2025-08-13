/**
 * 팝업스토어 탭 UI JavaScript
 */

(function($) {
    'use strict';
    
    var PopupStoreTabs = {
        currentTab: 'basic',
        tabs: ['basic', 'operation', 'social', 'tour'],
        fieldGroups: {},
        validationErrors: {},
        isInitialized: false,
        
        /**
         * 초기화
         */
        init: function() {
            // 팝업스토어 타입이 선택되었는지 확인
            this.checkPlaceType();
            
            // 장소 타입 변경 감지
            $(document).on('change', 'input[name="place_type"]', this.checkPlaceType.bind(this));
            
            // 이벤트 바인딩
            this.bindEvents();
        },
        
        /**
         * 장소 타입 확인
         */
        checkPlaceType: function() {
            var placeType = $('input[name="place_type"]:checked').val();
            
            if (placeType === 'popup_store' && !this.isInitialized) {
                this.initializeTabs();
            } else if (placeType !== 'popup_store' && this.isInitialized) {
                this.destroyTabs();
            }
        },
        
        /**
         * 탭 시스템 초기화
         */
        initializeTabs: function() {
            var self = this;
            
            // 템플릿 로드
            var template = $('#popup-store-tabs-template').html();
            if (!template) {
                console.log('❌ 탭 템플릿을 찾을 수 없습니다');
                return;
            }
            
            // 팝업스토어 필드 영역 찾기 - 실제 HTML 구조에 맞게 수정
            var $popupFields = $('.popup-store-fields-container');
            if (!$popupFields.length) {
                console.log('❌ 팝업스토어 필드 컨테이너를 찾을 수 없습니다');
                return;
            }
            
            console.log('✅ 팝업스토어 필드 컨테이너 발견:', $popupFields);
            
            // 탭 컨테이너 추가
            var $tabContainer = $(template);
            $popupFields.before($tabContainer);
            
            // 필드 그룹화
            this.groupFields();
            
            // 탭 콘텐츠 생성
            this.createTabContent();
            
            // 첫 번째 탭 활성화
            this.switchTab('basic');
            
            // 진행률 업데이트
            this.updateProgress();
            
            // 초기화 완료
            this.isInitialized = true;
            $popupFields.addClass('tabs-initialized');
            
            console.log('✅ 탭 시스템 초기화 완료');
        },
        
        /**
         * 탭 시스템 제거
         */
        destroyTabs: function() {
            $('.popup-store-tabs-container').remove();
            $('.popup-store-fields-container').removeClass('tabs-initialized');
            this.isInitialized = false;
        },
        
        /**
         * 이벤트 바인딩
         */
        bindEvents: function() {
            var self = this;
            
            // 탭 클릭
            $(document).on('click', '.tab-nav-item', function() {
                var tab = $(this).data('tab');
                self.switchTab(tab);
            });
            
            // 이전/다음 버튼
            $(document).on('click', '.prev-tab', function() {
                self.navigateTab('prev');
            });
            
            $(document).on('click', '.next-tab', function() {
                self.navigateTab('next');
            });
            
            // 전체 검증
            $(document).on('click', '.validate-all', function() {
                self.validateAll();
            });
            
            // 필드 변경 감지
            $(document).on('change', '.tab-pane input, .tab-pane select, .tab-pane textarea', function() {
                self.validateField($(this));
                self.updateProgress();
            });
            
            // 실시간 검증
            $(document).on('blur', '.tab-pane input, .tab-pane textarea', function() {
                self.validateField($(this));
            });
            
            // 저장 전 검증
            $('#publish, #save-post').on('click', function(e) {
                if (!self.validateBeforeSave()) {
                    e.preventDefault();
                    self.showValidationSummary();
                }
            });
        },
        
        /**
         * 필드 그룹화
         */
        groupFields: function() {
            var self = this;
            
            // 각 그룹의 실제 필드 찾기
            var fieldGroups = {
                'basic': '.popup-store-field-group[data-group="basic"] .places-field',
                'operation': '.popup-store-field-group[data-group="operation"] .places-field',
                'social': '.popup-store-field-group[data-group="social"] .places-field',
                'tour': '.popup-store-field-group[data-group="tour"] .places-field'
            };
            
            // 필드 수집
            $.each(fieldGroups, function(tab, selector) {
                self.fieldGroups[tab] = $(selector);
                console.log('📋 ' + tab + ' 탭 필드 수:', self.fieldGroups[tab].length);
            });
        },
        
        /**
         * 탭 콘텐츠 생성
         */
        createTabContent: function() {
            var self = this;
            var $tabsContent = $('.tabs-content');
            
            $.each(this.tabs, function(i, tab) {
                var $tabPane = $('<div class="tab-pane" data-tab="' + tab + '"></div>');
                
                // 탭별 설명 추가
                var descriptions = {
                    'basic': '<p class="tab-description">팝업스토어의 기본 정보를 입력해주세요.</p>',
                    'operation': '<p class="tab-description">운영 기간과 시간, 예약 정보 등을 설정해주세요.</p>',
                    'social': '<p class="tab-description">고객과 소통할 수 있는 채널 정보를 입력해주세요.</p>',
                    'tour': '<p class="tab-description">방문객을 위한 유용한 정보를 제공해주세요.</p>'
                };
                
                $tabPane.append(descriptions[tab]);
                
                // 필드 이동
                if (self.fieldGroups[tab] && self.fieldGroups[tab].length) {
                    var $fieldGroup = $('<div class="tab-field-group"></div>');
                    
                    self.fieldGroups[tab].each(function() {
                        var $field = $(this).clone(true);
                        $field.addClass('tab-field');
                        
                        // 필수 필드 표시
                        var fieldName = $field.attr('class').match(/field-(\w+)/)[1];
                        if (self.isRequiredField(fieldName)) {
                            $field.find('label').first().append('<span class="required">*</span>');
                        }
                        
                        $fieldGroup.append($field);
                    });
                    
                    $tabPane.append($fieldGroup);
                }
                
                $tabsContent.append($tabPane);
            });
        },
        
        /**
         * 탭 전환
         */
        switchTab: function(tab) {
            // 탭 네비게이션 업데이트
            $('.tab-nav-item').removeClass('active');
            $('.tab-nav-item[data-tab="' + tab + '"]').addClass('active');
            
            // 탭 콘텐츠 전환
            $('.tab-pane').removeClass('active');
            $('.tab-pane[data-tab="' + tab + '"]').addClass('active');
            
            // 현재 탭 저장
            this.currentTab = tab;
            
            // 버튼 상태 업데이트
            this.updateNavigationButtons();
        },
        
        /**
         * 탭 네비게이션
         */
        navigateTab: function(direction) {
            var currentIndex = this.tabs.indexOf(this.currentTab);
            var newIndex;
            
            if (direction === 'prev') {
                newIndex = Math.max(0, currentIndex - 1);
            } else {
                // 다음 탭으로 이동 전 현재 탭 검증
                if (this.validateCurrentTab()) {
                    newIndex = Math.min(this.tabs.length - 1, currentIndex + 1);
                } else {
                    this.showTabErrors();
                    return;
                }
            }
            
            this.switchTab(this.tabs[newIndex]);
        },
        
        /**
         * 네비게이션 버튼 업데이트
         */
        updateNavigationButtons: function() {
            var currentIndex = this.tabs.indexOf(this.currentTab);
            
            // 이전 버튼
            $('.prev-tab').prop('disabled', currentIndex === 0);
            
            // 다음 버튼
            if (currentIndex === this.tabs.length - 1) {
                $('.next-tab').hide();
                $('.validate-all').show();
            } else {
                $('.next-tab').show();
                $('.validate-all').hide();
            }
        },
        
        /**
         * 필드 검증
         */
        validateField: function($field) {
            var fieldName = this.getFieldName($field);
            var value = $field.val();
            var isValid = true;
            var errorMessage = '';
            
            // 필수 필드 검증
            if (this.isRequiredField(fieldName) && !value) {
                isValid = false;
                errorMessage = popupStoreTabs.strings.required;
            }
            
            // 이메일 검증
            if (fieldName === 'email' && value) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = '올바른 이메일 형식이 아닙니다';
                }
            }
            
            // URL 검증
            var urlFields = ['website', 'instagram', 'facebook', 'youtube', 'reservation_link'];
            if (urlFields.indexOf(fieldName) !== -1 && value) {
                try {
                    new URL(value);
                } catch (e) {
                    isValid = false;
                    errorMessage = '올바른 URL 형식이 아닙니다';
                }
            }
            
            // 날짜 검증
            if (fieldName === 'end_date') {
                var startDate = $('input[name="start_date"]').val();
                if (startDate && value && new Date(value) < new Date(startDate)) {
                    isValid = false;
                    errorMessage = popupStoreTabs.strings.endBeforeStart;
                }
            }
            
            // 오류 표시
            this.toggleFieldError($field, isValid, errorMessage);
            
            return isValid;
        },
        
        /**
         * 현재 탭 검증
         */
        validateCurrentTab: function() {
            var self = this;
            var isValid = true;
            
            $('.tab-pane[data-tab="' + this.currentTab + '"]').find('input, select, textarea').each(function() {
                if (!self.validateField($(this))) {
                    isValid = false;
                }
            });
            
            // 탭 상태 업데이트
            var $tabNav = $('.tab-nav-item[data-tab="' + this.currentTab + '"]');
            if (isValid) {
                $tabNav.removeClass('error').addClass('complete');
            } else {
                $tabNav.addClass('error').removeClass('complete');
            }
            
            return isValid;
        },
        
        /**
         * 전체 검증
         */
        validateAll: function() {
            var self = this;
            var isValid = true;
            this.validationErrors = {};
            
            // 모든 탭 검증
            $.each(this.tabs, function(i, tab) {
                var tabErrors = [];
                
                $('.tab-pane[data-tab="' + tab + '"]').find('input, select, textarea').each(function() {
                    if (!self.validateField($(this))) {
                        isValid = false;
                        var fieldName = self.getFieldName($(this));
                        var label = $(this).closest('.tab-field').find('label').first().text().replace('*', '').trim();
                        tabErrors.push(label);
                    }
                });
                
                if (tabErrors.length > 0) {
                    self.validationErrors[tab] = tabErrors;
                }
            });
            
            if (!isValid) {
                this.showValidationSummary();
            } else {
                alert('모든 필드가 올바르게 입력되었습니다!');
            }
            
            return isValid;
        },
        
        /**
         * 검증 요약 표시
         */
        showValidationSummary: function() {
            var self = this;
            var $summary = $('.validation-summary');
            var $errors = $('.validation-errors');
            
            $errors.empty();
            
            $.each(this.validationErrors, function(tab, errors) {
                var tabLabel = $('.tab-nav-item[data-tab="' + tab + '"] .tab-label').text();
                
                $.each(errors, function(i, error) {
                    $errors.append('<li><strong>' + tabLabel + '</strong>: ' + error + '</li>');
                });
            });
            
            $summary.show();
            
            // 첫 번째 오류 탭으로 이동
            var firstErrorTab = Object.keys(this.validationErrors)[0];
            if (firstErrorTab) {
                this.switchTab(firstErrorTab);
            }
        },
        
        /**
         * 진행률 업데이트
         */
        updateProgress: function() {
            var totalFields = 0;
            var completedFields = 0;
            
            $('.tab-pane input, .tab-pane select, .tab-pane textarea').each(function() {
                if ($(this).attr('type') !== 'hidden') {
                    totalFields++;
                    if ($(this).val()) {
                        completedFields++;
                    }
                }
            });
            
            var progress = totalFields > 0 ? Math.round((completedFields / totalFields) * 100) : 0;
            
            $('.progress-bar').css('--progress', progress + '%');
            $('.progress-text').text(progress + '% 완료');
        },
        
        /**
         * 헬퍼 함수들
         */
        getFieldName: function($field) {
            var name = $field.attr('name') || $field.attr('id') || '';
            return name.replace(/\[\]$/, '');
        },
        
        isRequiredField: function(fieldName) {
            var required = ['store_name', 'brand_name', 'popup_category', 'start_date', 'end_date', 'operation_status'];
            return required.indexOf(fieldName) !== -1;
        },
        
        toggleFieldError: function($field, isValid, message) {
            var $fieldWrapper = $field.closest('.tab-field');
            
            $fieldWrapper.find('.field-error-message').remove();
            
            if (isValid) {
                $fieldWrapper.removeClass('field-error').addClass('field-complete');
            } else {
                $fieldWrapper.removeClass('field-complete').addClass('field-error');
                if (message) {
                    $field.after('<span class="field-error-message">' + message + '</span>');
                }
            }
        },
        
        showTabErrors: function() {
            var $currentTab = $('.tab-pane[data-tab="' + this.currentTab + '"]');
            $currentTab.find('.field-error').first().find('input, select, textarea').focus();
        },
        
        validateBeforeSave: function() {
            // 팝업스토어가 아닌 경우 검증 건너뛰기
            if ($('input[name="place_type"]:checked').val() !== 'popup_store') {
                return true;
            }
            
            return this.validateAll();
        }
    };
    
    // 문서 준비 완료시 초기화
    $(document).ready(function() {
        PopupStoreTabs.init();
    });
    
})(jQuery);
