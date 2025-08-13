/**
 * Enhanced 크롤링 실시간 모니터링 JavaScript
 * 원클릭 크롤링 + 자동 포스팅 시스템
 */

jQuery(document).ready(function($) {
    let crawlingSessionId = null;
    let progressInterval = null;
    let startTime = null;
    
    console.log('Enhanced 크롤링 시스템 로드 완료');
    
    // 크롤링 시작
    $('#enhanced-crawling-form').on('submit', function(e) {
        e.preventDefault();
        
        const placeName = $('#place_name').val().trim();
        const address = $('#address').val().trim();
        const category = $('#category').val();
        const autoPosting = $('#auto_posting').is(':checked');
        
        if (!placeName) {
            alert('장소명을 입력해주세요.');
            return;
        }
        
        console.log('크롤링 시작:', {placeName, address, category, autoPosting});
        
        const formData = {
            action: 'start_enhanced_crawling',
            nonce: enhanced_ajax.nonce,
            place_name: placeName,
            address: address,
            category: category,
            auto_posting: autoPosting
        };
        
        // UI 업데이트
        $('#start-crawling').prop('disabled', true).html('🔄 시작 중...');
        $('#progress-section').show();
        $('#result-section').hide();
        resetPlatforms();
        
        startTime = Date.now();
        
        $.post(enhanced_ajax.ajax_url, formData)
            .done(function(response) {
                console.log('크롤링 응답:', response);
                
                if (response.success) {
                    crawlingSessionId = response.data.session_id;
                    startProgressMonitoring();
                    $('#start-crawling').hide();
                    $('#stop-crawling').show();
                    updateLog('✅ ' + response.data.message);
                } else {
                    alert('크롤링 시작 실패: ' + (response.data || '알 수 없는 오류'));
                    resetUI();
                }
            })
            .fail(function(xhr, status, error) {
                console.error('AJAX 실패:', error);
                alert('서버 연결 실패: ' + error);
                resetUI();
            });
    });
    
    // 실시간 진행 상황 모니터링
    function startProgressMonitoring() {
        console.log('진행 상황 모니터링 시작');
        progressInterval = setInterval(checkProgress, 3000); // 3초마다 확인
    }
    
    function checkProgress() {
        if (!crawlingSessionId) {
            console.log('세션 ID가 없어 모니터링 중단');
            return;
        }
        
        $.post(enhanced_ajax.ajax_url, {
            action: 'check_crawling_progress',
            session_id: crawlingSessionId
        })
        .done(function(response) {
            if (response.success) {
                const progress = response.data;
                console.log('진행 상황:', progress);
                
                updateProgressUI(progress);
                
                if (progress.status === 'completed') {
                    clearInterval(progressInterval);
                    showResult(progress);
                }
            } else {
                console.error('진행 상황 확인 실패:', response.data);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('진행 상황 확인 AJAX 실패:', error);
        });
    }
    
    function updateProgressUI(progress) {
        // 전체 진행률 업데이트
        const overallProgress = Math.round(progress.overall_progress);
        $('#overall-progress').css('width', overallProgress + '%');
        $('#overall-percentage').text(overallProgress + '%');
        
        // 플랫폼별 진행 상황 업데이트
        if (progress.platforms) {
            Object.keys(progress.platforms).forEach(platform => {
                const platformData = progress.platforms[platform];
                const $platformItem = $(`.platform-item[data-platform="${platform}"]`);
                
                $platformItem.find('.platform-status').text(platformData.status);
                $platformItem.find('.fill').css('width', platformData.progress + '%');
                
                // 상태에 따른 클래스 추가
                $platformItem.removeClass('running completed');
                if (platformData.status === '완료' || platformData.status === '데모 완료') {
                    $platformItem.addClass('completed');
                } else if (platformData.status === '진행 중') {
                    $platformItem.addClass('running');
                }
            });
        }
        
        // 실시간 로그 업데이트
        if (progress.log) {
            const elapsedTime = Math.round((Date.now() - startTime) / 1000);
            const logWithTime = `[${elapsedTime}s] 크롤링 진행 중...\n\n${progress.log}`;
            $('#live-log-content').html('<pre>' + logWithTime + '</pre>');
            
            // 자동 스크롤
            const logElement = $('#live-log-content')[0];
            logElement.scrollTop = logElement.scrollHeight;
        }
    }
    
    function showResult(progress) {
        const result = progress.result;
        const postId = progress.post_id;
        const elapsedTime = Math.round((Date.now() - startTime) / 1000);
        
        console.log('크롤링 완료:', result);
        
        let resultHTML = `
            <div class="result-summary">
                <h3>✅ ${result?.place_name || $('#place_name').val()} 크롤링 완료</h3>
                <p>총 소요시간: ${elapsedTime}초</p>
        `;
        
        if (result) {
            resultHTML += `
                <div class="result-stats">
                    <div class="stat">
                        <span class="number">${result.total_images || 0}</span>
                        <span class="label">실제 이미지</span>
                    </div>
                    <div class="stat">
                        <span class="number">${result.real_thumbnails || 0}</span>
                        <span class="label">실제 썸네일</span>
                    </div>
                    <div class="stat">
                        <span class="number">${result.demo_thumbnails || 7}</span>
                        <span class="label">데모 썸네일</span>
                    </div>
                </div>
            `;
        }
        
        if (postId) {
            resultHTML += `
                <div class="post-actions">
                    <a href="${enhanced_ajax.site_url}/wp-admin/post.php?post=${postId}&action=edit" 
                       class="button button-primary" target="_blank">
                        ✏️ 포스트 편집
                    </a>
                    <a href="${getPostViewUrl(postId)}" class="button" target="_blank">
                        🌐 포스트 보기
                    </a>
                    <a href="${enhanced_ajax.site_url}/enhanced-place-system-v2.php" 
                       class="button" target="_blank">
                        🚀 Enhanced v2.0 확인
                    </a>
                </div>
            `;
        }
        
        resultHTML += '</div>';
        
        $('#result-content').html(resultHTML);
        $('#result-section').show();
        
        updateLog('🎉 크롤링 완료! 총 ' + elapsedTime + '초 소요');
        
        resetUI();
    }
    
    function resetPlatforms() {
        $('.platform-item').removeClass('running completed');
        $('.platform-item .platform-status').text('대기 중');
        $('.platform-item .fill').css('width', '0%');
        $('#overall-progress').css('width', '0%');
        $('#overall-percentage').text('0%');
    }
    
    function resetUI() {
        $('#start-crawling')
            .prop('disabled', false)
            .html('🚀 크롤링 + 포스팅 시작')
            .show();
        $('#stop-crawling').hide();
        crawlingSessionId = null;
    }
    
    function updateLog(message) {
        const timestamp = new Date().toLocaleTimeString();
        const currentLog = $('#live-log-content pre').text();
        const newLog = `[${timestamp}] ${message}\n${currentLog}`;
        $('#live-log-content').html('<pre>' + newLog + '</pre>');
    }
    
    function getPostViewUrl(postId) {
        // 포스트 타입에 따라 적절한 URL 반환
        return enhanced_ajax.site_url + '/?p=' + postId;
    }
    
    // 중단 버튼
    $('#stop-crawling').on('click', function() {
        if (progressInterval) {
            clearInterval(progressInterval);
        }
        updateLog('❌ 사용자가 크롤링을 중단했습니다.');
        resetUI();
        $('#progress-section').hide();
    });
    
    // 페이지 언로드 시 정리
    $(window).on('beforeunload', function() {
        if (progressInterval) {
            clearInterval(progressInterval);
        }
    });
    
    // 개발자 도구용 디버깅 함수
    window.enhancedCrawlingDebug = {
        getCurrentSession: () => crawlingSessionId,
        forceCheck: () => checkProgress(),
        reset: () => resetUI()
    };
});
