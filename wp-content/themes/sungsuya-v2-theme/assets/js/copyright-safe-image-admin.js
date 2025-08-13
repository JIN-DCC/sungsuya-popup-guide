/**
 * 저작권 안전 이미지 크롤링 관리자 스크립트
 * 
 * @package SungsuyaV2
 * @version 1.0.0
 * @since 2025-07-02
 */

(function($) {
    'use strict';
    
    const CopyrightSafeImageAdmin = {
        
        /**
         * 초기화
         */
        init: function() {
            this.bindEvents();
            this.initModal();
        },
        
        /**
         * 이벤트 바인딩
         */
        bindEvents: function() {
            // 개별 크롤링
            $(document).on('click', '.crawl-single', this.handleSingleCrawl);
            
            // 안전도 테스트
            $(document).on('click', '.test-safety', this.handleSafetyTest);
            
            // 이미지 분석
            $('#analyze-image').on('click', this.handleImageAnalysis);
            
            // 일괄 크롤링
            $('#bulk-crawl-selected').on('click', this.handleBulkCrawlSelected);
            $('#bulk-crawl-no-images').on('click', this.handleBulkCrawlNoImages);
            
            // 엔터키 처리
            $('#analyze-url').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#analyze-image').click();
                }
            });
        },
        
        /**
         * 모달 초기화
         */
        initModal: function() {
            $('.close').on('click', function() {
                $('#crawling-modal').hide();
            });
            
            $(window).on('click', function(e) {
                if ($(e.target).is('#crawling-modal')) {
                    $('#crawling-modal').hide();
                }
            });
        },
        
        /**
         * 개별 크롤링 처리
         */
        handleSingleCrawl: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const postId = $button.data('post-id');
            const officialOnly = $('#official-only').is(':checked');
            const $row = $button.closest('tr');
            
            // 로딩 상태
            $button.prop('disabled', true)
                   .html('<span class="loading-spinner"></span> 크롤링 중...');
            
            $.ajax({
                url: copyrightSafeImage.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'copyright_safe_crawl',
                    nonce: copyrightSafeImage.nonce,
                    post_id: postId,
                    official_only: officialOnly
                },
                success: function(response) {
                    if (response.success) {
                        CopyrightSafeImageAdmin.showSuccessMessage(response.data.message);
                        
                        // 테이블 업데이트
                        const imageCount = response.data.images.length;
                        const avgSafety = CopyrightSafeImageAdmin.calculateAvgSafety(response.data.images);
                        
                        $row.find('.image-count').html(
                            imageCount + '개 <span class="safety-indicator" title="평균 안전도">(' + 
                            avgSafety + '%)</span>'
                        );
                        
                        // 결과 모달 표시
                        CopyrightSafeImageAdmin.showCrawlingResults(response.data);
                    } else {
                        CopyrightSafeImageAdmin.showErrorMessage(response.data || copyrightSafeImage.messages.error);
                    }
                },
                error: function() {
                    CopyrightSafeImageAdmin.showErrorMessage(copyrightSafeImage.messages.error);
                },
                complete: function() {
                    $button.prop('disabled', false)
                           .html('<span class="dashicons dashicons-download"></span> 크롤링');
                }
            });
        },
        
        /**
         * 안전도 테스트 처리
         */
        handleSafetyTest: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const postId = $button.data('post-id');
            
            // 로딩 상태
            $button.prop('disabled', true)
                   .html('<span class="loading-spinner"></span> 테스트 중...');
            
            $.ajax({
                url: copyrightSafeImage.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'test_copyright_safety',
                    nonce: copyrightSafeImage.testNonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        CopyrightSafeImageAdmin.showTestResults(response.data);
                    } else {
                        CopyrightSafeImageAdmin.showErrorMessage(response.data || copyrightSafeImage.messages.error);
                    }
                },
                error: function() {
                    CopyrightSafeImageAdmin.showErrorMessage(copyrightSafeImage.messages.error);
                },
                complete: function() {
                    $button.prop('disabled', false)
                           .html('<span class="dashicons dashicons-admin-tools"></span> 테스트');
                }
            });
        },
        
        /**
         * 이미지 분석 처리
         */
        handleImageAnalysis: function(e) {
            e.preventDefault();
            
            const imageUrl = $('#analyze-url').val().trim();
            
            if (!imageUrl) {
                alert('이미지 URL을 입력해주세요.');
                return;
            }
            
            const $button = $(this);
            const $result = $('.analyzer-result');
            const $content = $('.result-content');
            
            // 로딩 상태
            $button.prop('disabled', true)
                   .html('<span class="loading-spinner"></span> 분석 중...');
            
            $.ajax({
                url: copyrightSafeImage.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'analyze_image_type',
                    nonce: copyrightSafeImage.analyzeNonce,
                    image_url: imageUrl
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        let html = '<table class="wp-list-table widefat">';
                        html += '<tr><th>URL 안전도</th><td>' + data.url_safety_score + '%</td></tr>';
                        html += '<tr><th>위험 패턴</th><td>' + (data.has_unsafe_patterns ? '⚠️ 있음' : '✅ 없음') + '</td></tr>';
                        html += '<tr><th>안전 키워드</th><td>' + (data.has_safe_keywords ? '✅ 있음' : '❌ 없음') + '</td></tr>';
                        html += '<tr><th>추론된 타입</th><td>' + data.type_description + '</td></tr>';
                        html += '<tr><th>권장사항</th><td><strong>' + data.recommendation + '</strong></td></tr>';
                        html += '</table>';
                        
                        $content.html(html);
                        $result.show();
                    } else {
                        CopyrightSafeImageAdmin.showErrorMessage(response.data || copyrightSafeImage.messages.error);
                    }
                },
                error: function() {
                    CopyrightSafeImageAdmin.showErrorMessage(copyrightSafeImage.messages.error);
                },
                complete: function() {
                    $button.prop('disabled', false)
                           .html('<span class="dashicons dashicons-search"></span> 분석하기');
                }
            });
        },
        
        /**
         * 선택된 장소 일괄 크롤링
         */
        handleBulkCrawlSelected: function(e) {
            e.preventDefault();
            
            const selectedPlaces = [];
            $('input[type="checkbox"]:checked').each(function() {
                const postId = $(this).closest('tr').data('post-id');
                if (postId) {
                    selectedPlaces.push(postId);
                }
            });
            
            if (selectedPlaces.length === 0) {
                alert('크롤링할 장소를 선택해주세요.');
                return;
            }
            
            CopyrightSafeImageAdmin.processBulkCrawl(selectedPlaces);
        },
        
        /**
         * 이미지 없는 장소만 크롤링
         */
        handleBulkCrawlNoImages: function(e) {
            e.preventDefault();
            
            const noImagePlaces = [];
            $('.image-count').each(function() {
                if ($(this).text().startsWith('0개')) {
                    const postId = $(this).closest('tr').data('post-id');
                    if (postId) {
                        noImagePlaces.push(postId);
                    }
                }
            });
            
            if (noImagePlaces.length === 0) {
                alert('이미지가 없는 장소가 없습니다.');
                return;
            }
            
            if (confirm(noImagePlaces.length + '개의 장소를 크롤링하시겠습니까?')) {
                CopyrightSafeImageAdmin.processBulkCrawl(noImagePlaces);
            }
        },
        
        /**
         * 일괄 크롤링 처리
         */
        processBulkCrawl: function(placeIds) {
            const $progress = $('.bulk-progress');
            const $progressFill = $('.progress-fill');
            const $progressText = $('.progress-text');
            const minSafety = $('#min-safety').val();
            const maxImages = $('#max-images').val();
            
            let processed = 0;
            const total = placeIds.length;
            
            $progress.show();
            $progressText.text('0 / ' + total);
            
            // 순차적으로 처리
            const processNext = function() {
                if (processed >= total) {
                    CopyrightSafeImageAdmin.showSuccessMessage('일괄 크롤링이 완료되었습니다.');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                    return;
                }
                
                const postId = placeIds[processed];
                
                $.ajax({
                    url: copyrightSafeImage.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'copyright_safe_crawl',
                        nonce: copyrightSafeImage.nonce,
                        post_id: postId,
                        min_safety: minSafety,
                        max_images: maxImages
                    },
                    success: function(response) {
                        if (response.success) {
                            console.log('크롤링 성공:', postId);
                        } else {
                            console.error('크롤링 실패:', postId, response.data);
                        }
                    },
                    complete: function() {
                        processed++;
                        const progress = (processed / total) * 100;
                        $progressFill.css('width', progress + '%');
                        $progressText.text(processed + ' / ' + total);
                        
                        // 다음 처리
                        processNext();
                    }
                });
            };
            
            // 처리 시작
            processNext();
        },
        
        /**
         * 크롤링 결과 표시
         */
        showCrawlingResults: function(data) {
            let html = '<div class="crawling-results">';
            html += '<p>' + data.message + '</p>';
            
            if (data.images && data.images.length > 0) {
                html += '<div class="result-images" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-top: 20px;">';
                
                data.images.forEach(function(img) {
                    html += '<div class="result-image" style="border: 1px solid #ddd; padding: 5px; border-radius: 4px;">';
                    html += '<img src="' + img.url + '" style="width: 100%; height: 100px; object-fit: cover; margin-bottom: 5px;">';
                    html += '<div style="font-size: 11px;">';
                    html += '<div><strong>안전도:</strong> ' + img.safety_score + '% (' + img.safety_level + ')</div>';
                    html += '<div><strong>출처:</strong> ' + img.source + '</div>';
                    html += '</div>';
                    html += '</div>';
                });
                
                html += '</div>';
            }
            
            html += '</div>';
            
            $('#crawling-modal .modal-body').html(html);
            $('#crawling-modal').show();
        },
        
        /**
         * 테스트 결과 표시
         */
        showTestResults: function(data) {
            let html = '<div class="test-results">';
            
            // 통계
            html += '<h3>안전도 통계</h3>';
            html += '<table class="wp-list-table widefat">';
            html += '<tr><th>전체 이미지</th><td>' + data.stats.total + '개</td></tr>';
            html += '<tr><th>매우 안전 (95%+)</th><td>' + data.stats.very_safe + '개</td></tr>';
            html += '<tr><th>안전 (85%+)</th><td>' + data.stats.safe + '개</td></tr>';
            html += '<tr><th>보통 (70%+)</th><td>' + data.stats.moderate + '개</td></tr>';
            html += '<tr><th>주의 (50%+)</th><td>' + data.stats.caution + '개</td></tr>';
            html += '<tr><th>위험 (50%-)</th><td>' + data.stats.danger + '개</td></tr>';
            html += '</table>';
            
            // 상세 결과
            if (data.results && data.results.length > 0) {
                html += '<h3 style="margin-top: 20px;">상세 결과</h3>';
                html += '<div style="max-height: 300px; overflow-y: auto;">';
                html += '<table class="wp-list-table widefat">';
                html += '<thead><tr><th>이미지</th><th>소스</th><th>안전도</th><th>공식</th></tr></thead>';
                html += '<tbody>';
                
                data.results.forEach(function(result) {
                    html += '<tr>';
                    html += '<td><img src="' + result.url + '" style="width: 50px; height: 50px; object-fit: cover;"></td>';
                    html += '<td>' + result.source_desc + '</td>';
                    html += '<td><span class="safety-badge ' + CopyrightSafeImageAdmin.getSafetyClass(result.safety_score) + '">' + 
                            result.safety_score + '% (' + result.safety_level + ')</span></td>';
                    html += '<td>' + (result.is_official ? '✅' : '❌') + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                html += '</div>';
            }
            
            html += '</div>';
            
            $('#crawling-modal .modal-body').html(html);
            $('#crawling-modal').show();
        },
        
        /**
         * 평균 안전도 계산
         */
        calculateAvgSafety: function(images) {
            if (!images || images.length === 0) return 0;
            
            let total = 0;
            let count = 0;
            
            images.forEach(function(img) {
                if (img.safety_score) {
                    total += parseInt(img.safety_score);
                    count++;
                }
            });
            
            return count > 0 ? Math.round(total / count) : 0;
        },
        
        /**
         * 안전도 클래스 반환
         */
        getSafetyClass: function(score) {
            score = parseInt(score);
            if (score >= 95) return 'very-safe';
            if (score >= 85) return 'safe';
            if (score >= 70) return 'moderate';
            if (score >= 50) return 'caution';
            return 'danger';
        },
        
        /**
         * 성공 메시지 표시
         */
        showSuccessMessage: function(message) {
            const $notice = $('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>');
            $('.wrap > h1').after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * 에러 메시지 표시
         */
        showErrorMessage: function(message) {
            const $notice = $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
            $('.wrap > h1').after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };
    
    // 문서 준비 완료 시 초기화
    $(document).ready(function() {
        CopyrightSafeImageAdmin.init();
    });
    
})(jQuery);