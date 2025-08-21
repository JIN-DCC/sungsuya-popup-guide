/**
 * 업종별 상세정보 자동수집 관리자 JavaScript
 */
jQuery(document).ready(function($) {
    'use strict';
    
    // API 상태 확인
    checkApiStatus();
    
    // API 연결 재확인 버튼
    $('#test-api-connection').on('click', function() {
        checkApiStatus();
    });
    
    // 개별 수집 버튼
    $('.collect-single').on('click', function() {
        var $button = $(this);
        var postId = $button.data('post-id');
        var $row = $button.closest('tr');
        
        collectPlaceDetails(postId, $row);
    });
    
    // 수집 이력 보기
    $('.view-history').on('click', function() {
        var postId = $(this).data('post-id');
        viewCollectionHistory(postId);
    });
    
    // 일괄 수집 - 필터 조건
    $('#bulk-collect-filtered').on('click', function() {
        var placeType = $('#bulk-place-type').val();
        var target = $('#bulk-target').val();
        
        // 필터 조건에 맞는 장소 ID 수집
        var filteredIds = [];
        $('.wp-list-table tbody tr').each(function() {
            var $row = $(this);
            var shouldInclude = false;
            
            // 업종 필터
            if (placeType) {
                var rowType = $row.find('td:eq(2)').text().trim();
                if (placeType === 'food' && rowType.includes('음식점')) {
                    shouldInclude = true;
                } else if (placeType === 'shop' && rowType.includes('일반업종')) {
                    shouldInclude = true;
                }
            } else {
                shouldInclude = true;
            }
            
            // 수집 대상 필터
            if (shouldInclude) {
                if (target === 'all') {
                    // 모든 장소
                    shouldInclude = true;
                } else {
                    var fieldCount = parseInt($row.find('.field-count').text());
                    var lastCollected = $row.find('td:eq(5)').text().trim();
                    
                    if (target === 'empty' && fieldCount < 5) {
                        shouldInclude = true;
                    } else if (target === 'never' && lastCollected === '-') {
                        shouldInclude = true;
                    } else if (target === 'old' && lastCollected.includes('일')) {
                        var days = parseInt(lastCollected);
                        if (days >= 30) {
                            shouldInclude = true;
                        } else {
                            shouldInclude = false;
                        }
                    } else {
                        shouldInclude = false;
                    }
                }
            }
            
            if (shouldInclude) {
                filteredIds.push($row.data('post-id'));
            }
        });
        
        if (filteredIds.length === 0) {
            alert('조건에 맞는 장소가 없습니다.');
            return;
        }
        
        var confirmMsg = filteredIds.length + '개 장소의 상세정보를 수집하시겠습니까?';
        if (target === 'all') {
            confirmMsg += '\n\n⚠️ 주의: 모든 장소를 수집하면 시간이 오래 걸릴 수 있습니다.';
        }
        
        if (confirm(confirmMsg)) {
            bulkCollectPlaces(filteredIds);
        }
    });
    
    // 모달 닫기
    $('.close').on('click', function() {
        $(this).closest('.collection-modal').hide();
    });
    
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('collection-modal')) {
            $('.collection-modal').hide();
        }
    });
    
    /**
     * API 상태 확인
     */
    function checkApiStatus() {
        $('.api-status-box').each(function() {
            var $box = $(this);
            var $indicator = $box.find('.status-indicator');
            
            $indicator.html('<span class="dashicons dashicons-update spinning"></span><span class="status-text">확인 중...</span>');
            $indicator.removeClass('success error');
        });
        
        $.ajax({
            url: placeDetailCollector.ajaxUrl,
            type: 'POST',
            data: {
                action: 'test_api_availability',
                nonce: placeDetailCollector.testNonce
            },
            success: function(response) {
                if (response.success) {
                    // 네이버 API
                    updateApiStatus('naver', response.data.naver);
                    
                    // 카카오 API
                    updateApiStatus('kakao', response.data.kakao);
                    
                    // 구글 API
                    updateApiStatus('google', response.data.google);
                }
            },
            error: function() {
                $('.api-status-box .status-indicator').each(function() {
                    $(this).html('<span class="dashicons dashicons-warning"></span><span class="status-text">확인 실패</span>');
                    $(this).addClass('error');
                });
            }
        });
    }
    
    /**
     * API 상태 업데이트
     */
    function updateApiStatus(api, status) {
        var $box = $('#' + api + '-status');
        var $indicator = $box.find('.status-indicator');
        
        if (status.status === 'success') {
            $indicator.html('<span class="dashicons dashicons-yes-alt"></span><span class="status-text">' + status.message + '</span>');
            $indicator.addClass('success');
        } else {
            $indicator.html('<span class="dashicons dashicons-warning"></span><span class="status-text">' + status.message + '</span>');
            $indicator.addClass('error');
        }
    }
    
    /**
     * 개별 장소 상세정보 수집
     */
    function collectPlaceDetails(postId, $row) {
        var overwrite = $('#overwrite-existing').prop('checked');
        var sources = [];
        
        $('input[name="sources[]"]:checked').each(function() {
            sources.push($(this).val());
        });
        
        if (sources.length === 0) {
            alert('최소 하나의 수집 소스를 선택해주세요.');
            return;
        }
        
        var $button = $row.find('.collect-single');
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spinning"></span> 수집 중...');
        
        $.ajax({
            url: placeDetailCollector.ajaxUrl,
            type: 'POST',
            data: {
                action: 'collect_place_details',
                post_id: postId,
                overwrite: overwrite,
                sources: sources,
                nonce: placeDetailCollector.nonce
            },
            success: function(response) {
                if (response.success) {
                    // 성공 메시지
                    showToast(response.data.message, 'success');
                    
                    // 필드 수 업데이트
                    if (response.data.fields) {
                        var fieldCount = response.data.fields.length;
                        $row.find('.field-count').text(fieldCount + '개');
                    }
                    
                    // 마지막 수집 시간 업데이트
                    $row.find('td:eq(5)').text('방금 전');
                } else {
                    showToast('오류: ' + response.data, 'error');
                }
            },
            error: function() {
                showToast('서버 오류가 발생했습니다.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-database-import"></span> 수집');
            }
        });
    }
    
    /**
     * 일괄 수집
     */
    function bulkCollectPlaces(postIds) {
        var overwrite = $('#overwrite-existing').prop('checked');
        var sources = [];
        
        $('input[name="sources[]"]:checked').each(function() {
            sources.push($(this).val());
        });
        
        if (sources.length === 0) {
            alert('최소 하나의 수집 소스를 선택해주세요.');
            return;
        }
        
        // 진행 상태 표시
        $('.bulk-progress').show();
        var $progressFill = $('.progress-fill');
        var $progressText = $('.progress-text');
        
        var total = postIds.length;
        var completed = 0;
        var failed = 0;
        
        $progressText.text('0 / ' + total);
        
        // 순차적으로 처리
        function processNext() {
            if (completed >= total) {
                // 완료
                var message = '일괄 수집 완료: ' + (completed - failed) + '개 성공';
                if (failed > 0) {
                    message += ', ' + failed + '개 실패';
                }
                showToast(message, failed > 0 ? 'warning' : 'success');
                
                // 진행 상태 숨기기
                setTimeout(function() {
                    $('.bulk-progress').hide();
                    $progressFill.css('width', '0%');
                    
                    // 페이지 새로고침
                    location.reload();
                }, 2000);
                
                return;
            }
            
            var postId = postIds[completed];
            
            $.ajax({
                url: placeDetailCollector.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'collect_place_details',
                    post_id: postId,
                    overwrite: overwrite,
                    sources: sources,
                    nonce: placeDetailCollector.nonce
                },
                success: function(response) {
                    if (!response.success) {
                        failed++;
                    }
                },
                error: function() {
                    failed++;
                },
                complete: function() {
                    completed++;
                    
                    // 진행률 업데이트
                    var progress = (completed / total) * 100;
                    $progressFill.css('width', progress + '%');
                    $progressText.text(completed + ' / ' + total);
                    
                    // 다음 처리
                    processNext();
                }
            });
        }
        
        // 처리 시작
        processNext();
    }
    
    /**
     * 수집 이력 보기
     */
    function viewCollectionHistory(postId) {
        $.ajax({
            url: placeDetailCollector.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_collection_history',
                post_id: postId,
                nonce: placeDetailCollector.historyNonce || placeDetailCollector.nonce
            },
            success: function(response) {
                if (response.success) {
                    showHistoryModal(response.data);
                } else {
                    showToast(response.data || '수집 이력이 없습니다.', 'warning');
                }
            },
            error: function() {
                showToast('서버 오류가 발생했습니다.', 'error');
            }
        });
    }
    
    /**
     * 수집 이력 모달 표시
     */
    function showHistoryModal(history) {
        var modalHtml = '<h3>수집 이력</h3>';
        
        if (history.length === 0) {
            modalHtml += '<p>수집 이력이 없습니다.</p>';
        } else {
            history.forEach(function(entry, index) {
                modalHtml += `
                    <div class="history-entry">
                        <h4>수집 ${history.length - index}</h4>
                        <p><strong>날짜:</strong> ${entry.date}</p>
                        <p><strong>수집된 필드:</strong> ${entry.count}개</p>
                        <div class="history-fields">
                            <strong>상세 내역:</strong>
                            <ul>
                `;
                
                entry.fields.forEach(function(field) {
                    var value = field.value;
                    if (typeof value === 'boolean') {
                        value = value ? '예' : '아니오';
                    } else if (value === null || value === '') {
                        value = '(비어있음)';
                    } else if (value.length > 50) {
                        value = value.substring(0, 50) + '...';
                    }
                    
                    modalHtml += `<li><strong>${field.label}:</strong> ${value}</li>`;
                });
                
                modalHtml += `
                            </ul>
                        </div>
                    </div>
                `;
            });
        }
        
        $('#collection-history-modal .modal-body').html(modalHtml);
        $('#collection-history-modal').show();
    }
    
    /**
     * 토스트 메시지 표시
     */
    function showToast(message, type) {
        var $toast = $('<div class="notice notice-' + type + ' is-dismissible toast-message"><p>' + message + '</p></div>');
        
        $toast.css({
            position: 'fixed',
            top: '32px',
            right: '20px',
            zIndex: '9999',
            minWidth: '300px',
            boxShadow: '0 2px 5px rgba(0,0,0,0.2)'
        });
        
        $('body').append($toast);
        
        // 3초 후 자동 제거
        setTimeout(function() {
            $toast.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
});
