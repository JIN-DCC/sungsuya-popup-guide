<?php
/**
 * 팝업스토어 CSV 업로드 초기화
 * 
 * @package SungsuyaV2
 * @since 2.0.0
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

// JavaScript 및 CSS 등록
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'places_page_popup-csv-upload') return;
    
    wp_enqueue_script(
        'popup-csv-upload',
        get_template_directory_uri() . '/admin/js/popup-csv-upload.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    // ajaxurl 전달
    wp_localize_script('popup-csv-upload', 'popupCsvUpload', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('popup_csv_upload')
    ));
});

// AJAX 핸들러 등록
add_action('wp_ajax_handle_popup_csv_upload', 'handle_popup_csv_ajax');

/**
 * AJAX 업로드 핸들러
 */
function handle_popup_csv_ajax() {
    // 권한 확인
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('권한이 없습니다.');
    }
    
    // Nonce 확인
    check_ajax_referer('popup_csv_upload', 'popup_csv_nonce');
    
    error_log('[CSV AJAX] 업로드 시작');
    error_log('[CSV AJAX] FILES: ' . print_r($_FILES, true));
    error_log('[CSV AJAX] POST: ' . print_r($_POST, true));
    
    if (!isset($_FILES['popup_csv']) || $_FILES['popup_csv']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('파일 업로드에 실패했습니다.');
    }
    
    $uploaded_file = $_FILES['popup_csv'];
    $file_path = $uploaded_file['tmp_name'];
    
    // CSV Importer 클래스 로드
    require_once get_template_directory() . '/inc/csv-importer/class-popup-store-csv-importer.php';
    
    // CSV Importer 인스턴스 생성
    $importer = new PopupStoreCSVImporter();
    
    // CSV 검증
    $validation = $importer->validate_csv($file_path);
    
    if (!$validation['valid']) {
        wp_send_json_error('CSV 검증 실패: ' . implode(', ', $validation['errors']));
    }
    
    // 옵션 설정
    $options = [
        'duplicate_action' => $_POST['duplicate_action'] ?? 'skip',
        'auto_publish' => isset($_POST['auto_publish']) && $_POST['auto_publish'] === '1'
    ];
    
    // 데이터 임포트
    $import_results = $importer->import($validation['data'], $options);
    
    // 업로드 이력 저장
    save_popup_csv_upload_history($uploaded_file['name'], $import_results);
    
    // 결과 메시지 생성
    $message = sprintf(
        '업로드 완료: 성공 %d개, 건너뜀 %d개, 실패 %d개',
        $import_results['success'],
        $import_results['skipped'],
        $import_results['failed']
    );
    
    if (!empty($validation['warnings'])) {
        $message .= ' (경고: ' . count($validation['warnings']) . '개)';
    }
    
    wp_send_json_success([
        'message' => $message,
        'details' => $import_results
    ]);
}

/**
 * 업로드 이력 저장
 */
function save_popup_csv_upload_history($filename, $results) {
    $history = get_option('popup_csv_upload_history', []);
    
    $history[] = [
        'date' => current_time('mysql'),
        'user' => wp_get_current_user()->display_name,
        'filename' => $filename,
        'success' => $results['success'],
        'skipped' => $results['skipped'],
        'failed' => $results['failed'],
        'log' => array_slice($results['log'], -10) // 최근 10개 로그만 저장
    ];
    
    // 최근 20개만 유지
    if (count($history) > 20) {
        $history = array_slice($history, -20);
    }
    
    update_option('popup_csv_upload_history', $history);
}
