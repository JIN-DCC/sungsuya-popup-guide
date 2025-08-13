/**
 * 팝업스토어 대시보드 JavaScript
 */

jQuery(document).ready(function($) {
    // 워크플로우 단계 클릭
    $('.workflow-step').on('click', function() {
        var step = $(this).data('step');
        
        // 활성 단계 변경
        $('.workflow-step').removeClass('active');
        $(this).addClass('active');
        
        // 진행률 업데이트
        var progress = step * 25;
        $('.workflow-progress .progress-bar').css('width', progress + '%');
        
        // 단계별 액션
        switch(step) {
            case 1:
                checkCrawlStatus();
                break;
            case 2:
                showDownloadOptions();
                break;
            case 3:
                showAIPrompt();
                break;
            case 4:
                // CSV 업로드 페이지로 이동
                break;
        }
    });
    
    // 크롤링 상태 확인
    $('#check-crawl-status').on('click', function() {
        checkCrawlStatus();
    });
    
    // Excel 다운로드
    $('#download-excel').on('click', function() {
        downloadExcel();
    });
    
    // AI 프롬프트 복사
    $('#copy-ai-prompt').on('click', function() {
        copyAIPrompt();
    });
    
    // 지금 크롤링 실행
    $('#run-crawl-now').on('click', function() {
        runCrawlNow();
    });
    
    // 일괄 이미지 크롤링
    $('#batch-image-crawl').on('click', function() {
        showBatchImageCrawlDialog();
    });
    
    // 통계 자동 업데이트 (30초마다)
    setInterval(updateDashboardStats, 30000);
    
    /**
     * 크롤링 상태 확인
     */
    function checkCrawlStatus() {
        var $widget = $('.recent-crawl-widget');
        $widget.addClass('loading');
        
        $.ajax({
            url: ajaxurl,
            type: 'GET',
            data: {
                action: 'get_crawl_status',
                nonce: popupDashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateCrawlStatusDisplay(response.data);
                    
                    // 2단계로 자동 진행
                    $('.workflow-step[data-step="2"]').trigger('click');
                }
            },
            complete: function() {
                $widget.removeClass('loading');
            }
        });
    }
    
    /**
     * Excel 다운로드
     */
    function downloadExcel() {
        var $button = $('#download-excel');
        $button.prop('disabled', true).text('생성 중...');
        
        $.ajax({
            url: popupDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'popup_dashboard_download_excel',
                nonce: popupDashboard.nonce
            },
            success: function(response) {
                if (response.success && response.data.url) {
                    // 다운로드 시작
                    window.location.href = response.data.url;
                    
                    // 3단계로 진행
                    setTimeout(function() {
                        $('.workflow-step[data-step="3"]').trigger('click');
                    }, 1000);
                }
            },
            complete: function() {
                $button.prop('disabled', false).text('📥 다운로드');
            }
        });
    }
    
    /**
     * AI 프롬프트 복사
     */
    function copyAIPrompt() {
        var prompt = `## 팝업스토어 데이터 정리 요청

아래 크롤링 데이터를 다음 CSV 양식에 맞춰 정리해주세요.

### 필수 항목 (반드시 포함)
- 브랜드명: 정확한 브랜드명
- 스토어명: 팝업스토어 전체 이름
- 주소: 성수동 상세 주소
- 시작일: YYYY-MM-DD 형식
- 종료일: YYYY-MM-DD 형식
- 카테고리: 패션/뷰티/라이프스타일/푸드/아트/테크/스포츠 중 선택
- 운영상태: open/coming_soon/closed 중 선택

### 중요 규칙
1. 확실하지 않은 정보는 "(예상)" 표시
2. 없는 정보는 빈칸으로
3. 날짜는 반드시 YYYY-MM-DD 형식

[여기에 크롤링 데이터 붙여넣기]`;
        
        // 클립보드에 복사
        if (navigator.clipboard) {
            navigator.clipboard.writeText(prompt).then(function() {
                showNotification('AI 프롬프트가 복사되었습니다!', 'success');
                
                // 4단계로 진행
                $('.workflow-step[data-step="4"]').trigger('click');
            });
        } else {
            // Fallback
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(prompt).select();
            document.execCommand('copy');
            $temp.remove();
            
            showNotification('AI 프롬프트가 복사되었습니다!', 'success');
        }
    }
    
    /**
     * 지금 크롤링 실행
     */
    function runCrawlNow() {
        if (!confirm('지금 크롤링을 실행하시겠습니까?')) {
            return;
        }
        
        var $button = $('#run-crawl-now');
        $button.prop('disabled', true);
        $button.find('.label').text('실행 중...');
        
        $.ajax({
            url: popupDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'popup_dashboard_run_crawl',
                nonce: popupDashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    
                    // 크롤링 상태 모니터링 시작
                    monitorCrawlProgress();
                }
            },
            error: function() {
                showNotification('크롤링 실행에 실패했습니다.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false);
                $button.find('.label').text('지금 크롤링');
            }
        });
    }
    
    /**
     * 크롤링 진행 상황 모니터링
     */
    function monitorCrawlProgress() {
        var checkInterval = setInterval(function() {
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'check_crawl_progress',
                    nonce: popupDashboard.nonce
                },
                success: function(response) {
                    if (response.data.status === 'completed') {
                        clearInterval(checkInterval);
                        updateCrawlResults(response.data);
                        updateDashboardStats();
                    } else {
                        // 진행 상황 업데이트
                        updateProgressDisplay(response.data.progress);
                    }
                }
            });
        }, 3000); // 3초마다 체크
    }
    
    /**
     * 일괄 이미지 크롤링 다이얼로그
     */
    function showBatchImageCrawlDialog() {
        var dialog = `
            <div class="batch-image-dialog">
                <h3>🖼️ 일괄 이미지 크롤링</h3>
                <p>이미지가 없는 팝업스토어의 이미지를 자동으로 수집합니다.</p>
                <div class="dialog-options">
                    <label>
                        <input type="radio" name="crawl_target" value="no_image" checked>
                        이미지 없는 팝업만 (추천)
                    </label>
                    <label>
                        <input type="radio" name="crawl_target" value="all">
                        모든 팝업 재크롤링
                    </label>
                </div>
                <div class="dialog-actions">
                    <button class="button button-primary" onclick="startBatchImageCrawl()">
                        시작
                    </button>
                    <button class="button" onclick="closeBatchDialog()">
                        취소
                    </button>
                </div>
            </div>
        `;
        
        // 다이얼로그 표시 (실제 구현시 모달 라이브러리 사용)
        alert('일괄 이미지 크롤링 기능은 준비 중입니다.');
    }
    
    /**
     * 대시보드 통계 업데이트
     */
    function updateDashboardStats() {
        $.ajax({
            url: popupDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'popup_dashboard_get_stats',
                nonce: popupDashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    // 상태 카드 업데이트
                    $('.status-card.coming-soon .card-number').text(response.data.coming_soon);
                    $('.status-card.open .card-number').text(response.data.open);
                    $('.status-card.ending-soon .card-number').text(response.data.ending_soon);
                    $('.status-card.closed .card-number').text(response.data.closed);
                    
                    // 애니메이션 효과
                    $('.status-card').addClass('updated');
                    setTimeout(function() {
                        $('.status-card').removeClass('updated');
                    }, 1000);
                }
            }
        });
    }
    
    /**
     * 알림 표시
     */
    function showNotification(message, type) {
        var $notification = $('<div class="dashboard-notification ' + type + '">' + message + '</div>');
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.addClass('show');
        }, 10);
        
        setTimeout(function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 3000);
    }
    
    /**
     * 크롤링 상태 표시 업데이트
     */
    function updateCrawlStatusDisplay(data) {
        var html = `
            <div class="crawl-status-info">
                <p><strong>마지막 실행:</strong> ${data.last_run}</p>
                <p><strong>다음 예정:</strong> ${data.next_run}</p>
                <p><strong>상태:</strong> <span class="status-badge success">${data.status}</span></p>
            </div>
        `;
        
        $('.recent-crawl-widget .crawl-result-info').html(html);
    }
    
    // 페이지 로드시 초기화
    initializeDashboard();
    
    function initializeDashboard() {
        // 툴팁 초기화
        $('[data-tooltip]').each(function() {
            $(this).attr('title', $(this).data('tooltip'));
        });
        
        // 첫 로드시 통계 업데이트
        updateDashboardStats();
    }
});

// 전역 함수 (다이얼로그용)
function startBatchImageCrawl() {
    var target = jQuery('input[name="crawl_target"]:checked').val();
    console.log('Starting batch image crawl for:', target);
    // 실제 구현 필요
}

function closeBatchDialog() {
    jQuery('.batch-image-dialog').remove();
}

// CSS 추가 (알림용)
jQuery('<style>')
    .text(`
        .dashboard-notification {
            position: fixed;
            top: 50px;
            right: -300px;
            background: #fff;
            padding: 15px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 9999;
            max-width: 300px;
        }
        
        .dashboard-notification.show {
            right: 20px;
        }
        
        .dashboard-notification.success {
            border-left: 4px solid #27ae60;
        }
        
        .dashboard-notification.error {
            border-left: 4px solid #e74c3c;
        }
        
        .status-card.updated {
            animation: highlight 0.5s ease;
        }
        
        @keyframes highlight {
            0% { background-color: #fff; }
            50% { background-color: #fff3cd; }
            100% { background-color: #fff; }
        }
    `)
    .appendTo('head');
