<?php
/**
 * 문의하기 폼 처리
 * 
 * AJAX를 통해 폼 데이터를 받아 이메일로 전송
 */

// 문의 폼 처리 AJAX 핸들러
add_action('wp_ajax_submit_contact_form', 'sungsuya_handle_contact_form');
add_action('wp_ajax_nopriv_submit_contact_form', 'sungsuya_handle_contact_form');

function sungsuya_handle_contact_form() {
    // Nonce 검증
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'contact_form_nonce')) {
        wp_send_json_error('보안 검증에 실패했습니다.');
    }
    
    // 폼 데이터 수집 및 검증
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $subject = sanitize_text_field($_POST['subject'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    
    // 필수 필드 검증
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        wp_send_json_error('필수 항목을 모두 입력해주세요.');
    }
    
    // 이메일 유효성 검증
    if (!is_email($email)) {
        wp_send_json_error('올바른 이메일 주소를 입력해주세요.');
    }
    
    // 문의 유형 한글 변환
    $subject_types = array(
        'general' => '일반 문의',
        'place' => '장소 등록/수정 요청',
        'tour' => '투어플래너 관련',
        'partnership' => '제휴/광고 문의',
        'bug' => '버그 신고',
        'other' => '기타'
    );
    
    $subject_korean = $subject_types[$subject] ?? '기타';
    
    // 이메일 제목
    $email_subject = '[성수야!] ' . $subject_korean . ' - ' . $name;
    
    // 이메일 본문
    $email_body = "새로운 문의가 접수되었습니다.\n\n";
    $email_body .= "======================\n";
    $email_body .= "이름: " . $name . "\n";
    $email_body .= "이메일: " . $email . "\n";
    $email_body .= "연락처: " . ($phone ?: '미입력') . "\n";
    $email_body .= "문의 유형: " . $subject_korean . "\n";
    $email_body .= "======================\n\n";
    $email_body .= "문의 내용:\n";
    $email_body .= $message . "\n\n";
    $email_body .= "======================\n";
    $email_body .= "발송 시간: " . date('Y-m-d H:i:s') . "\n";
    $email_body .= "발송 IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    
    // 이메일 헤더
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: 성수야! <noreply@' . $_SERVER['HTTP_HOST'] . '>',
        'Reply-To: ' . $name . ' <' . $email . '>'
    );
    
    // 관리자 이메일
    $admin_email = 'dcclab2022@gmail.com';
    
    // 이메일 전송
    $sent = wp_mail($admin_email, $email_subject, $email_body, $headers);
    
    if ($sent) {
        // 데이터베이스에 문의 내역 저장 (옵션)
        global $wpdb;
        $table_name = $wpdb->prefix . 'contact_inquiries';
        
        // 테이블이 존재하면 저장
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $wpdb->insert(
                $table_name,
                array(
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'subject' => $subject,
                    'message' => $message,
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'created_at' => current_time('mysql')
                )
            );
        }
        
        // 자동 응답 이메일 전송 (옵션)
        $auto_reply_subject = '[성수야!] 문의가 접수되었습니다.';
        $auto_reply_body = $name . "님, 안녕하세요.\n\n";
        $auto_reply_body .= "성수야!에 문의해주셔서 감사합니다.\n";
        $auto_reply_body .= "고객님의 문의가 정상적으로 접수되었습니다.\n\n";
        $auto_reply_body .= "빠른 시일 내에 답변 드리도록 하겠습니다.\n";
        $auto_reply_body .= "업무 시간은 평일 10:00 - 18:00이며,\n";
        $auto_reply_body .= "주말 및 공휴일에 접수된 문의는 다음 영업일에 처리됩니다.\n\n";
        $auto_reply_body .= "감사합니다.\n\n";
        $auto_reply_body .= "성수야! 팀 드림";
        
        $auto_reply_headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: 성수야! <noreply@' . $_SERVER['HTTP_HOST'] . '>',
        );
        
        wp_mail($email, $auto_reply_subject, $auto_reply_body, $auto_reply_headers);
        
        wp_send_json_success('문의가 성공적으로 전송되었습니다.');
    } else {
        wp_send_json_error('메일 전송에 실패했습니다. 잠시 후 다시 시도해주세요.');
    }
}

// 문의 내역 저장을 위한 테이블 생성 (옵션)
function sungsuya_create_contact_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_inquiries';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(20),
        subject varchar(50) NOT NULL,
        message text NOT NULL,
        ip_address varchar(45),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        status varchar(20) DEFAULT 'pending',
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// 테마 활성화 시 테이블 생성
add_action('after_switch_theme', 'sungsuya_create_contact_table');

// 관리자 메뉴에 문의 관리 페이지 추가
add_action('admin_menu', 'sungsuya_add_contact_admin_menu', 35);

function sungsuya_add_contact_admin_menu() {
    add_menu_page(
        '문의 관리',
        '문의 관리',
        'manage_options',
        'contact-inquiries',
        'sungsuya_contact_admin_page',
        'dashicons-email-alt',
        30
    );
}

// 문의 관리 페이지
function sungsuya_contact_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_inquiries';
    
    // 테이블이 없으면 생성
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        sungsuya_create_contact_table();
    }
    
    // 문의 목록 조회
    $inquiries = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 100");
    
    ?>
    <div class="wrap">
        <h1>문의 관리</h1>
        
        <div class="notice notice-info">
            <p>문의 내용은 <strong>dcclab2022@gmail.com</strong>으로도 전송됩니다.</p>
        </div>
        
        <?php if ($inquiries) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="15%">이름</th>
                        <th width="20%">이메일</th>
                        <th width="15%">문의 유형</th>
                        <th width="25%">내용</th>
                        <th width="10%">상태</th>
                        <th width="10%">날짜</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inquiries as $inquiry) : 
                        $subject_types = array(
                            'general' => '일반 문의',
                            'place' => '장소 등록/수정',
                            'tour' => '투어플래너',
                            'partnership' => '제휴/광고',
                            'bug' => '버그 신고',
                            'other' => '기타'
                        );
                        $subject_korean = $subject_types[$inquiry->subject] ?? '기타';
                    ?>
                        <tr>
                            <td><?php echo $inquiry->id; ?></td>
                            <td><?php echo esc_html($inquiry->name); ?></td>
                            <td>
                                <a href="mailto:<?php echo esc_attr($inquiry->email); ?>">
                                    <?php echo esc_html($inquiry->email); ?>
                                </a>
                                <?php if ($inquiry->phone) : ?>
                                    <br><small><?php echo esc_html($inquiry->phone); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $subject_korean; ?></td>
                            <td>
                                <div style="max-height: 60px; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo nl2br(esc_html($inquiry->message)); ?>
                                </div>
                                <a href="#" onclick="alert('<?php echo esc_js($inquiry->message); ?>'); return false;">전체보기</a>
                            </td>
                            <td>
                                <?php 
                                $status_labels = array(
                                    'pending' => '<span style="color: orange;">대기중</span>',
                                    'replied' => '<span style="color: green;">답변완료</span>',
                                    'closed' => '<span style="color: gray;">종료</span>'
                                );
                                echo $status_labels[$inquiry->status] ?? $inquiry->status;
                                ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($inquiry->created_at)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>아직 접수된 문의가 없습니다.</p>
        <?php endif; ?>
    </div>
    <?php
}
