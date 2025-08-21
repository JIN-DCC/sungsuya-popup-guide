/**
 * 향상된 이미지 크롤링 시스템 JavaScript
 */
jQuery(document).ready(function($) {
    'use strict';
    
    // 이미지 크롤링 버튼 클릭
    $('#crawl-images-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var postId = $button.data('post-id');
        var $status = $('#crawling-status');
        var $result = $('#crawling-result');
        
        // UI 업데이트
        $button.prop('disabled', true);
        $status.show();
        $result.empty();
        
        // AJAX 요청
        $.ajax({
            url: enhancedImageCrawling.ajaxUrl,
            type: 'POST',
            data: {
                action: 'enhanced_crawl_images',
                post_id: postId,
                nonce: enhancedImageCrawling.nonce
            },
            success: function(response) {
                $status.hide();
                $button.prop('disabled', false);
                
                if (response.success) {
                    var html = '<div class="notice notice-success">';
                    html += '<p><strong>' + response.data.message + '</strong></p>';
                    
                    if (response.data.method) {
                        html += '<p>방식: ' + (response.data.method === 'advanced' ? '향상된 검색' : '기본 검색') + '</p>';
                    }
                    
                    if (response.data.thumbnail) {
                        html += '<p>썸네일:</p>';
                        html += '<img src="' + response.data.thumbnail + '" style="max-width: 150px; height: auto;">';
                    }
                    
                    html += '</div>';
                    
                    $result.html(html);
                    
                    // 페이지 새로고침
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                $status.hide();
                $button.prop('disabled', false);
                
                var errorMsg = '오류가 발생했습니다: ' + error;
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMsg = xhr.responseJSON.data;
                }
                
                $result.html('<div class="notice notice-error"><p>' + errorMsg + '</p></div>');
            }
        });
    });
    
    // 이미지 검증 기능
    $('.verify-image-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var imageUrl = $button.data('image-url');
        var placeName = $button.data('place-name');
        
        $button.text('검증 중...').prop('disabled', true);
        
        $.ajax({
            url: enhancedImageCrawling.ajaxUrl,
            type: 'POST',
            data: {
                action: 'verify_image_relevance',
                image_url: imageUrl,
                place_name: placeName,
                nonce: enhancedImageCrawling.nonce
            },
            success: function(response) {
                if (response.success) {
                    var score = response.data.score;
                    var status = score > 7 ? '관련성 높음' : (score > 4 ? '보통' : '관련성 낮음');
                    var color = score > 7 ? 'green' : (score > 4 ? 'orange' : 'red');
                    
                    $button.replaceWith('<span style="color: ' + color + ';">' + status + ' (' + score + '점)</span>');
                } else {
                    $button.text('검증 실패').prop('disabled', false);
                }
            },
            error: function() {
                $button.text('재시도').prop('disabled', false);
            }
        });
    });
    
    // 일괄 크롤링 개선
    var batchCrawling = {
        isRunning: false,
        totalPlaces: 0,
        currentOffset: 0,
        successCount: 0,
        failCount: 0,
        
        start: function() {
            if (this.isRunning) return;
            
            this.isRunning = true;
            this.totalPlaces = 0;
            this.currentOffset = 0;
            this.successCount = 0;
            this.failCount = 0;
            
            $('#batch-crawl-btn').prop('disabled', true).text('크롤링 중...');
            $('#batch-progress').show();
            $('#batch-results').empty();
            
            this.processBatch();
        },
        
        processBatch: function() {
            var self = this;
            
            $.ajax({
                url: enhancedImageCrawling.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'enhanced_batch_crawl_images',
                    offset: self.currentOffset,
                    nonce: enhancedImageCrawling.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        
                        self.totalPlaces = data.total;
                        self.successCount += data.success;
                        self.failCount += (data.processed - data.success);
                        
                        // 진행률 업데이트
                        var progress = data.progress || 0;
                        $('#batch-progress-bar').css('width', progress + '%');
                        $('#batch-progress-text').text(progress + '%');
                        
                        // 결과 표시
                        var resultHtml = '<p>전체: ' + self.totalPlaces + '개 / ';
                        resultHtml += '완료: ' + (self.currentOffset + data.processed) + '개 / ';
                        resultHtml += '성공: ' + self.successCount + '개 / ';
                        resultHtml += '실패: ' + self.failCount + '개</p>';
                        
                        $('#batch-results').html(resultHtml);
                        
                        if (data.has_more) {
                            self.currentOffset = data.offset;
                            setTimeout(function() {
                                self.processBatch();
                            }, 1000); // 서버 부담 줄이기
                        } else {
                            self.complete();
                        }
                    } else {
                        self.error(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    self.error('네트워크 오류: ' + error);
                }
            });
        },
        
        complete: function() {
            this.isRunning = false;
            $('#batch-crawl-btn').prop('disabled', false).text('일괄 크롤링 시작');
            
            var message = '<div class="notice notice-success">';
            message += '<p><strong>일괄 크롤링 완료!</strong></p>';
            message += '<p>총 ' + this.successCount + '개 장소의 이미지를 성공적으로 크롤링했습니다.</p>';
            message += '</div>';
            
            $('#batch-results').append(message);
        },
        
        error: function(errorMsg) {
            this.isRunning = false;
            $('#batch-crawl-btn').prop('disabled', false).text('일괄 크롤링 시작');
            
            var message = '<div class="notice notice-error">';
            message += '<p><strong>오류 발생:</strong> ' + errorMsg + '</p>';
            message += '</div>';
            
            $('#batch-results').append(message);
        }
    };
    
    // 일괄 크롤링 버튼
    $('#batch-crawl-btn').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('이미지가 없는 모든 장소에 대해 크롤링을 시작하시겠습니까?\n\n이 작업은 시간이 걸릴 수 있습니다.')) {
            batchCrawling.start();
        }
    });
    
    // 검색 전략 테스트
    $('#test-search-strategy').on('click', function(e) {
        e.preventDefault();
        
        var placeName = $('#test-place-name').val();
        if (!placeName) {
            alert('장소명을 입력해주세요.');
            return;
        }
        
        var $results = $('#search-strategy-results');
        $results.html('<p>검색 중...</p>');
        
        $.ajax({
            url: enhancedImageCrawling.ajaxUrl,
            type: 'POST',
            data: {
                action: 'test_search_strategies',
                place_name: placeName,
                nonce: enhancedImageCrawling.nonce
            },
            success: function(response) {
                if (response.success) {
                    var html = '<h4>검색 전략 테스트 결과</h4>';
                    html += '<table class="widefat">';
                    html += '<thead><tr><th>검색어</th><th>소스</th><th>결과 수</th><th>샘플 이미지</th></tr></thead>';
                    html += '<tbody>';
                    
                    response.data.strategies.forEach(function(strategy) {
                        html += '<tr>';
                        html += '<td>' + strategy.query + '</td>';
                        html += '<td>' + strategy.source + '</td>';
                        html += '<td>' + strategy.count + '</td>';
                        html += '<td>';
                        if (strategy.sample) {
                            html += '<img src="' + strategy.sample + '" style="max-width: 100px; height: auto;">';
                        }
                        html += '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table>';
                    $results.html(html);
                } else {
                    $results.html('<p class="error">테스트 실패: ' + response.data + '</p>');
                }
            },
            error: function() {
                $results.html('<p class="error">네트워크 오류가 발생했습니다.</p>');
            }
        });
    });
});
