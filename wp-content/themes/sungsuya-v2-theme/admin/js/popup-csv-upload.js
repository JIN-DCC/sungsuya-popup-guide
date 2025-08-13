/**
 * 팝업스토어 CSV 업로드 AJAX 처리
 */
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('[CSV Upload] JavaScript 로드됨');
    
    // 폼 제출 이벤트
    $('#csv-upload-form').on('submit', function(e) {
        e.preventDefault();
        console.log('[CSV Upload] 폼 제출 시작');
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $fileInput = $('#popup_csv');
        
        // 파일 검증
        if (!$fileInput[0].files.length) {
            alert('CSV 파일을 선택해주세요.');
            return;
        }
        
        // FormData 생성
        const formData = new FormData();
        formData.append('action', 'handle_popup_csv_upload');
        formData.append('popup_csv', $fileInput[0].files[0]);
        formData.append('popup_csv_nonce', $('#popup_csv_nonce').val());
        formData.append('duplicate_action', $('input[name="duplicate_action"]:checked').val());
        formData.append('auto_publish', $('#auto_publish').is(':checked') ? '1' : '0');
        
        // 버튼 비활성화
        $submitBtn.prop('disabled', true).html('📤 업로드 중...');
        
        // 결과 영역 초기화
        $('.upload-result').remove();
        
        // AJAX 요청
        $.ajax({
            url: popupCsvUpload.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('[CSV Upload] 응답:', response);
                
                if (response.success) {
                    // 성공 메시지 표시
                    showResult('success', response.data.message);
                    
                    // 상세 결과 표시
                    if (response.data.details) {
                        showDetails(response.data.details);
                    }
                    
                    // 폼 리셋
                    $form[0].reset();
                    
                    // 페이지 새로고침 (업로드 이력 갱신)
                    setTimeout(function() {
                        window.location.reload();
                    }, 3000);
                } else {
                    showResult('error', response.data || '업로드에 실패했습니다.');
                }
            },
            error: function(xhr, status, error) {
                console.error('[CSV Upload] AJAX 에러:', error);
                showResult('error', '서버 오류가 발생했습니다: ' + error);
            },
            complete: function() {
                // 버튼 복원
                $submitBtn.prop('disabled', false).html('📤 CSV 업로드 및 등록');
            }
        });
    });
    
    // 결과 표시 함수
    function showResult(type, message) {
        const $result = $(`
            <div class="notice notice-${type} is-dismissible upload-result">
                <p>${message}</p>
            </div>
        `);
        
        $('#csv-upload-form').before($result);
        
        // 자동으로 dismiss 버튼 추가
        setTimeout(function() {
            $result.find('.notice-dismiss').click();
        }, 10000);
    }
    
    // 상세 결과 표시
    function showDetails(details) {
        if (!details.log || details.log.length === 0) return;
        
        let logHtml = '<div class="upload-details" style="background: #f0f0f1; padding: 15px; margin: 10px 0; border-radius: 5px;">';
        logHtml += '<h4>📋 처리 상세 내역</h4>';
        logHtml += '<ul style="list-style: none; padding: 0;">';
        
        details.log.forEach(function(item) {
            const icon = item.type === 'success' ? '✅' : 
                        item.type === 'skip' ? '⏭️' : 
                        item.type === 'error' ? '❌' : 'ℹ️';
            logHtml += `<li>${icon} ${item.message}</li>`;
        });
        
        logHtml += '</ul></div>';
        
        $('.upload-result').append(logHtml);
    }
    
    // 파일 크기 검증
    $('#popup_csv').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            console.log('[CSV Upload] 파일 크기:', fileSize + 'MB');
            
            if (fileSize > 2) {
                alert('파일 크기가 2MB를 초과합니다.');
                e.target.value = '';
            }
        }
    });
});

// AI 프롬프트 모달 함수들 (전역 스코프)
function showAIPrompt() {
    document.getElementById('ai-prompt-modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('ai-prompt-modal').style.display = 'none';
}

function copyPrompt() {
    const textarea = document.getElementById('ai-prompt-text');
    textarea.select();
    document.execCommand('copy');
    alert('프롬프트가 복사되었습니다!');
}
