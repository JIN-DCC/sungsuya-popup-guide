jQuery(document).ready(function($) {
    // 일괄 크롤링 시작
    $('#startBatchCrawling').on('click', function() {
        if (!confirm('이미지가 없는 모든 장소의 이미지를 크롤링하시겠습니까?')) {
            return;
        }
        
        $(this).prop('disabled', true);
        $('#crawlingProgress').show();
        
        startBatchCrawling();
    });
    
    // 선택된 장소 크롤링
    $('#startSelectedCrawling').on('click', function() {
        const postIds = $(this).data('post-ids').split(',');
        
        $(this).prop('disabled', true);
        $('#crawlingProgress').show();
        
        startBatchCrawling(postIds);
    });
    
    // 단일 크롤링
    $('.crawl-single').on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const postId = $button.data('post-id');
        
        $button.text('크롤링 중...').prop('disabled', true);
        
        $.ajax({
            url: imageCrawling.ajaxUrl,
            type: 'POST',
            data: {
                action: 'crawl_place_images',
                post_id: postId,
                nonce: imageCrawling.nonce
            },
            success: function(response) {
                if (response.success) {
                    $button.text('완료').css('color', 'green');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    $button.text('실패').css('color', 'red');
                }
            },
            error: function() {
                $button.text('오류').css('color', 'red');
            }
        });
    });
    
    // 목록 페이지에서 단일 크롤링
    $(document).on('click', '.crawl-single-image', function(e) {
        e.preventDefault();
        const $link = $(this);
        const postId = $link.data('post-id');
        
        $link.text('크롤링 중...').css('color', '#999');
        
        $.ajax({
            url: imageCrawling.ajaxUrl,
            type: 'POST',
            data: {
                action: 'crawl_place_images',
                post_id: postId,
                nonce: imageCrawling.nonce
            },
            success: function(response) {
                if (response.success && response.data.thumbnail) {
                    // 이미지 표시
                    const $cell = $link.closest('td');
                    $cell.html('<img src="' + response.data.thumbnail + '" width="60" height="60" style="object-fit: cover;">');
                } else {
                    $link.text('실패').css('color', 'red');
                }
            },
            error: function() {
                $link.text('오류').css('color', 'red');
            }
        });
    });
    
    // 일괄 크롤링 함수
    function startBatchCrawling(postIds = []) {
        let offset = 0;
        let isRunning = true;
        
        $('#stopCrawling').on('click', function() {
            isRunning = false;
            $(this).text('중지됨').prop('disabled', true);
        });
        
        function processBatch() {
            if (!isRunning) {
                $('#progressText').text('크롤링이 중지되었습니다.');
                return;
            }
            
            $.ajax({
                url: imageCrawling.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'batch_crawl_images',
                    post_ids: postIds,
                    offset: offset,
                    nonce: imageCrawling.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        offset = data.offset;
                        
                        // 진행률 업데이트
                        const progress = data.progress || 0;
                        $('#progressBar').css('width', progress + '%').text(progress + '%');
                        $('#progressText').text(`${data.offset}/${data.total} 처리 완료 (성공: ${data.success}개)`);
                        
                        // 로그 추가
                        const log = `[${new Date().toLocaleTimeString()}] ${data.processed}개 처리, ${data.success}개 성공\n`;
                        $('#crawlingLog').append(log).scrollTop($('#crawlingLog')[0].scrollHeight);
                        
                        // 더 있으면 계속
                        if (data.has_more && isRunning) {
                            setTimeout(processBatch, 1000); // 1초 대기 후 다음 배치
                        } else {
                            $('#progressText').text('✅ 크롤링 완료!');
                            $('#stopCrawling').text('완료').prop('disabled', true);
                            setTimeout(() => location.reload(), 2000);
                        }
                    }
                },
                error: function() {
                    $('#progressText').text('❌ 오류가 발생했습니다.');
                    $('#stopCrawling').text('오류').prop('disabled', true);
                }
            });
        }
        
        processBatch();
    }
});
