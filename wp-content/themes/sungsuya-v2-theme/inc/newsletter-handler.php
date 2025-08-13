<?php
/**
 * Newsletter Handler
 * 
 * @package SungsuyaV2
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

// AJAX 핸들러 등록
add_action('wp_ajax_subscribe_newsletter', 'sungsuya_handle_newsletter_subscription');
add_action('wp_ajax_nopriv_subscribe_newsletter', 'sungsuya_handle_newsletter_subscription');

/**
 * 뉴스레터 구독 처리
 */
function sungsuya_handle_newsletter_subscription() {
    // 이메일 검증
    $email = sanitize_email($_POST['email']);
    
    if (!is_email($email)) {
        wp_send_json_error('올바른 이메일 주소를 입력해주세요.');
        return;
    }
    
    // 이메일을 데이터베이스에 저장 (옵션으로 저장)
    $subscribers = get_option('sungsuya_newsletter_subscribers', array());
    
    // 중복 체크
    if (in_array($email, $subscribers)) {
        wp_send_json_error('이미 구독 중인 이메일입니다.');
        return;
    }
    
    // 이메일 추가
    $subscribers[] = $email;
    update_option('sungsuya_newsletter_subscribers', $subscribers);
    
    // 관리자에게 알림 이메일 전송 (선택사항)
    $admin_email = get_option('admin_email');
    $subject = '[성수야!] 새로운 뉴스레터 구독자';
    $message = "새로운 뉴스레터 구독자가 등록되었습니다.\n\n";
    $message .= "이메일: {$email}\n";
    $message .= "등록 시간: " . current_time('mysql') . "\n";
    
    wp_mail($admin_email, $subject, $message);
    
    wp_send_json_success('뉴스레터 구독이 완료되었습니다!');
}

// 관리자 페이지에 구독자 목록 메뉴 추가
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=places',
        '뉴스레터 구독자',
        '📧 뉴스레터 구독자',
        'manage_options',
        'newsletter-subscribers',
        'sungsuya_newsletter_subscribers_page'
    );
});

/**
 * 뉴스레터 구독자 관리 페이지
 */
function sungsuya_newsletter_subscribers_page() {
    $subscribers = get_option('sungsuya_newsletter_subscribers', array());
    ?>
    <div class="wrap">
        <h1>뉴스레터 구독자 목록</h1>
        
        <div class="card">
            <h2>총 구독자: <?php echo count($subscribers); ?>명</h2>
            
            <?php if (!empty($subscribers)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;">번호</th>
                            <th>이메일 주소</th>
                            <th style="width: 150px;">작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribers as $index => $email): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo esc_html($email); ?></td>
                                <td>
                                    <button class="button button-small remove-subscriber" data-email="<?php echo esc_attr($email); ?>">
                                        제거
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <br>
                <button class="button button-primary" id="export-subscribers">
                    CSV로 내보내기
                </button>
            <?php else: ?>
                <p>아직 구독자가 없습니다.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // 구독자 제거
        $('.remove-subscriber').on('click', function() {
            if (confirm('이 구독자를 제거하시겠습니까?')) {
                var email = $(this).data('email');
                var row = $(this).closest('tr');
                
                $.post(ajaxurl, {
                    action: 'remove_newsletter_subscriber',
                    email: email,
                    nonce: '<?php echo wp_create_nonce('remove_subscriber'); ?>'
                }, function(response) {
                    if (response.success) {
                        row.fadeOut();
                    }
                });
            }
        });
        
        // CSV 내보내기
        $('#export-subscribers').on('click', function() {
            var subscribers = <?php echo json_encode($subscribers); ?>;
            var csv = 'Email\n';
            subscribers.forEach(function(email) {
                csv += email + '\n';
            });
            
            var blob = new Blob([csv], { type: 'text/csv' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'newsletter-subscribers.csv';
            a.click();
        });
    });
    </script>
    <?php
}

// 구독자 제거 AJAX 핸들러
add_action('wp_ajax_remove_newsletter_subscriber', function() {
    check_ajax_referer('remove_subscriber', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die();
    }
    
    $email = sanitize_email($_POST['email']);
    $subscribers = get_option('sungsuya_newsletter_subscribers', array());
    
    $key = array_search($email, $subscribers);
    if ($key !== false) {
        unset($subscribers[$key]);
        $subscribers = array_values($subscribers); // 인덱스 재정렬
        update_option('sungsuya_newsletter_subscribers', $subscribers);
        wp_send_json_success();
    }
    
    wp_send_json_error();
});