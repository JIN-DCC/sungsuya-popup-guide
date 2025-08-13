<?php
/**
 * 관리자 알림 시스템
 * 
 * @package SungsuyaV2
 * @since 2025.06.27
 */

// 관리자 알림 표시
add_action('admin_notices', 'sungsuya_admin_notices');

function sungsuya_admin_notices() {
    // 크롤링 완료 알림
    if (isset($_GET['crawling']) && $_GET['crawling'] == 'success') {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>✓ 크롤링이 완료되었습니다!</strong></p>
        </div>
        <?php
    }
    
    // 이미지 크롤링 완료 알림
    if (isset($_GET['image_crawling']) && $_GET['image_crawling'] == 'success') {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>✓ 이미지 크롤링이 완료되었습니다!</strong></p>
        </div>
        <?php
    }
    
    // CSV 업로드 완료 알림
    if (isset($_GET['csv_upload']) && $_GET['csv_upload'] == 'success') {
        $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>✓ CSV 업로드가 완료되었습니다!</strong> <?php echo $count; ?>개의 장소가 추가되었습니다.</p>
        </div>
        <?php
    }
    
    // 에러 알림
    if (isset($_GET['error'])) {
        $error_message = '';
        switch ($_GET['error']) {
            case 'api_key':
                $error_message = 'API 키가 설정되지 않았습니다. API 설정 페이지에서 확인해주세요.';
                break;
            case 'crawling_failed':
                $error_message = '크롤링 중 오류가 발생했습니다. 다시 시도해주세요.';
                break;
            case 'no_address':
                $error_message = '주소가 입력되지 않은 장소입니다.';
                break;
        }
        
        if ($error_message) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>⚠️ 오류:</strong> <?php echo esc_html($error_message); ?></p>
            </div>
            <?php
        }
    }
}

// AJAX 작업을 위한 JavaScript 추가
add_action('admin_footer', 'sungsuya_admin_ajax_script');

function sungsuya_admin_ajax_script() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // 크롤링 버튼 처리
        $('.crawling-button, .image-crawling-button').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var originalText = $button.text();
            
            // 버튼 상태 변경
            $button.text('처리 중...').prop('disabled', true);
            
            // AJAX 요청 (실제 구현은 기존 코드 활용)
            $.ajax({
                url: $button.attr('href'),
                success: function() {
                    $button.text('완료!').addClass('button-success');
                    
                    // 3초 후 원래 텍스트로 복원
                    setTimeout(function() {
                        $button.text(originalText).prop('disabled', false).removeClass('button-success');
                    }, 3000);
                },
                error: function() {
                    $button.text('오류 발생').addClass('button-error');
                    setTimeout(function() {
                        $button.text(originalText).prop('disabled', false).removeClass('button-error');
                    }, 3000);
                }
            });
        });
        
        // 일괄 작업 확인
        $('#doaction, #doaction2').on('click', function(e) {
            var action = $(this).prev('select').val();
            
            if (action === 'trash') {
                if (!confirm('선택한 항목을 정말 삭제하시겠습니까?')) {
                    e.preventDefault();
                }
            }
        });
    });
    </script>
    
    <style>
    /* 버튼 상태 스타일 */
    .button-success {
        background: #46b450 !important;
        border-color: #46b450 !important;
        color: #fff !important;
    }
    
    .button-error {
        background: #dc3232 !important;
        border-color: #dc3232 !important;
        color: #fff !important;
    }
    
    /* 알림 개선 */
    .notice {
        margin: 15px 0;
        padding: 12px !important;
    }
    
    .notice p {
        margin: 0.5em 0 !important;
    }
    </style>
    <?php
}

// 작업 완료 후 리다이렉트 헬퍼 함수
function sungsuya_redirect_with_message($url, $type, $message = '') {
    $redirect_url = add_query_arg($type, 'success', $url);
    
    if ($message) {
        $redirect_url = add_query_arg('message', urlencode($message), $redirect_url);
    }
    
    wp_redirect($redirect_url);
    exit;
}
