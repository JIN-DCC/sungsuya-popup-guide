<?php
/**
 * 성수야! 데이터 정리 도구 - 관리자 메뉴
 * 
 * 이 파일을 테마의 inc 폴더에 넣고 functions.php에서 include 하세요
 */

// 관리자 메뉴 추가
add_action('admin_menu', 'sungsuya_add_cleanup_menu');
function sungsuya_add_cleanup_menu() {
    add_submenu_page(
        'edit.php?post_type=places',  // 장소 메뉴 하위에 추가
        '데이터 정리 도구',           // 페이지 제목
        '🗑️ 데이터 정리',            // 메뉴 제목
        'manage_options',             // 권한
        'sungsuya-data-cleanup',      // 슬러그
        'sungsuya_data_cleanup_page'  // 콜백 함수
    );
}

// 정리 도구 페이지
function sungsuya_data_cleanup_page() {
    // 권한 재확인
    if (!current_user_can('manage_options')) {
        wp_die('권한이 없습니다.');
    }
    
    // AJAX nonce 생성
    $nonce = wp_create_nonce('sungsuya_cleanup_nonce');
    
    ?>
    <div class="wrap">
        <h1>🗑️ 성수야! 데이터 정리 도구</h1>
        
        <div id="cleanup-dashboard" style="margin-top: 20px;">
            <!-- 현재 상태 -->
            <div class="card" style="max-width: 800px;">
                <h2>📊 현재 데이터 현황</h2>
                <div id="data-stats" style="padding: 20px;">
                    <p>데이터를 불러오는 중...</p>
                </div>
            </div>
            
            <!-- 정리 옵션 -->
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>🧹 정리 옵션</h2>
                <div style="padding: 20px;">
                    <div style="margin-bottom: 30px; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107;">
                        <h3 style="margin-top: 0;">⚠️ 주의사항</h3>
                        <ul>
                            <li>이 작업은 <strong>되돌릴 수 없습니다</strong></li>
                            <li>실행 전 반드시 <strong>백업</strong>을 하세요</li>
                            <li>모든 장소 데이터와 관련 파일이 삭제됩니다</li>
                        </ul>
                    </div>
                    
                    <div class="cleanup-options">
                        <h3>정리 수준 선택:</h3>
                        
                        <label style="display: block; margin: 10px 0; padding: 15px; border: 2px solid #ddd; border-radius: 5px; cursor: pointer;">
                            <input type="radio" name="cleanup_level" value="soft" checked>
                            <strong>🧹 일반 정리</strong> (권장)
                            <p style="margin: 5px 0 0 25px; color: #666;">
                                WordPress API를 통한 안전한 정리<br>
                                포스트, 메타데이터, 썸네일 삭제<br>
                                <strong style="color: #4caf50;">✅ 장소 유형은 보존됨</strong>
                            </p>
                        </label>
                        
                        <label style="display: block; margin: 10px 0; padding: 15px; border: 2px solid #ddd; border-radius: 5px; cursor: pointer;">
                            <input type="radio" name="cleanup_level" value="deep">
                            <strong>🔨 심층 정리</strong>
                            <p style="margin: 5px 0 0 25px; color: #666;">
                                일반 정리 + 캐시 및 임시 데이터<br>
                                업로드 폴더의 관련 파일 정리<br>
                                <strong style="color: #4caf50;">✅ 장소 유형은 보존됨</strong>
                            </p>
                        </label>
                        
                        <label style="display: block; margin: 10px 0; padding: 15px; border: 2px solid #d32f2f; border-radius: 5px; cursor: pointer; background: #ffebee;">
                            <input type="radio" name="cleanup_level" value="nuclear">
                            <strong style="color: #d32f2f;">☢️ 완전 삭제</strong> (위험!)
                            <p style="margin: 5px 0 0 25px; color: #d32f2f;">
                                DB 직접 조작으로 모든 흔적 제거<br>
                                고아 데이터 및 관련 옵션 모두 삭제<br>
                                <strong style="color: #4caf50;">✅ 장소 유형은 보존됨</strong>
                            </p>
                        </label>
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <button id="start-cleanup" class="button button-primary button-large" style="background: #d32f2f; border-color: #d32f2f;">
                            🗑️ 정리 시작
                        </button>
                        <span id="cleanup-spinner" class="spinner" style="float: none; margin-left: 10px;"></span>
                    </div>
                </div>
            </div>
            
            <!-- 진행 상황 -->
            <div id="cleanup-progress" class="card" style="max-width: 800px; margin-top: 20px; display: none;">
                <h2>🔄 정리 진행 중...</h2>
                <div style="padding: 20px;">
                    <div class="progress-bar" style="width: 100%; height: 30px; background: #f0f0f0; border-radius: 15px; overflow: hidden;">
                        <div id="progress-fill" style="width: 0%; height: 100%; background: linear-gradient(90deg, #4caf50, #8bc34a); transition: width 0.3s;">
                            <span id="progress-text" style="display: block; text-align: center; line-height: 30px; color: white;">0%</span>
                        </div>
                    </div>
                    <div id="cleanup-log" style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;">
                    </div>
                </div>
            </div>
            
            <!-- 결과 -->
            <div id="cleanup-result" class="card" style="max-width: 800px; margin-top: 20px; display: none;">
                <h2>✅ 정리 완료</h2>
                <div id="result-content" style="padding: 20px;">
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .cleanup-options label:hover {
            background: #f9f9f9;
            border-color: #999;
        }
        .cleanup-options input[type="radio"]:checked + strong {
            color: #0073aa;
        }
        #cleanup-log {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .log-success { color: #4caf50; }
        .log-error { color: #f44336; }
        .log-warning { color: #ff9800; }
        .log-info { color: #2196f3; }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // 초기 데이터 로드
        loadDataStats();
        
        // 데이터 통계 로드
        function loadDataStats() {
            $.post(ajaxurl, {
                action: 'sungsuya_get_cleanup_stats',
                nonce: '<?php echo $nonce; ?>'
            }, function(response) {
                if (response.success) {
                    $('#data-stats').html(response.data.html);
                }
            });
        }
        
        // 정리 시작 버튼
        $('#start-cleanup').on('click', function() {
            const level = $('input[name="cleanup_level"]:checked').val();
            
            let confirmMsg = '정말로 모든 장소 데이터를 삭제하시겠습니까?\n\n이 작업은 되돌릴 수 없습니다!';
            if (level === 'nuclear') {
                confirmMsg += '\n\n☢️ 경고: 완전 삭제 모드는 매우 위험합니다!';
            }
            
            if (!confirm(confirmMsg)) {
                return;
            }
            
            // 두 번째 확인
            if (!confirm('정말로 확실하십니까? 마지막 확인입니다!')) {
                return;
            }
            
            // UI 업데이트
            $(this).prop('disabled', true);
            $('#cleanup-spinner').addClass('is-active');
            $('#cleanup-progress').slideDown();
            $('#cleanup-result').hide();
            $('#cleanup-log').empty();
            
            // 정리 시작
            startCleanup(level);
        });
        
        // 정리 프로세스
        function startCleanup(level) {
            addLog('🚀 정리 프로세스 시작...', 'info');
            addLog('정리 수준: ' + level, 'info');
            
            $.post(ajaxurl, {
                action: 'sungsuya_execute_cleanup',
                nonce: '<?php echo $nonce; ?>',
                level: level
            }, function(response) {
                $('#cleanup-spinner').removeClass('is-active');
                
                if (response.success) {
                    // 로그 업데이트
                    if (response.data.logs) {
                        response.data.logs.forEach(function(log) {
                            addLog(log.message, log.type);
                        });
                    }
                    
                    // 진행률 업데이트
                    updateProgress(100);
                    
                    // 결과 표시
                    showResult(response.data);
                    
                    // 통계 새로고침
                    setTimeout(loadDataStats, 1000);
                } else {
                    addLog('❌ 오류: ' + response.data, 'error');
                    $('#start-cleanup').prop('disabled', false);
                }
            }).fail(function() {
                addLog('❌ 서버 오류가 발생했습니다.', 'error');
                $('#cleanup-spinner').removeClass('is-active');
                $('#start-cleanup').prop('disabled', false);
            });
        }
        
        // 진행률 업데이트
        function updateProgress(percent) {
            $('#progress-fill').css('width', percent + '%');
            $('#progress-text').text(percent + '%');
        }
        
        // 로그 추가
        function addLog(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const logClass = 'log-' + type;
            $('#cleanup-log').append(
                '<div class="' + logClass + '">[' + timestamp + '] ' + message + '</div>'
            );
            $('#cleanup-log').scrollTop($('#cleanup-log')[0].scrollHeight);
        }
        
        // 결과 표시
        function showResult(data) {
            let html = '<div style="padding: 20px; background: #c8e6c9; border-radius: 5px; margin-bottom: 20px;">';
            html += '<h3 style="margin-top: 0; color: #2e7d32;">✅ 정리가 완료되었습니다!</h3>';
            html += '<p>삭제된 항목:</p>';
            html += '<ul>';
            html += '<li>포스트: ' + (data.deleted_posts || 0) + '개</li>';
            html += '<li>메타데이터: ' + (data.deleted_meta || 0) + '개</li>';
            html += '<li>첨부파일: ' + (data.deleted_attachments || 0) + '개</li>';
            html += '<li>캐시: ' + (data.deleted_cache || 0) + '개</li>';
            html += '</ul>';
            html += '</div>';
            
            html += '<p>이제 깨끗한 상태에서 다시 시작할 수 있습니다:</p>';
            html += '<a href="' + '<?php echo admin_url('admin.php?page=enhanced-bulk-crawling'); ?>' + '" class="button button-primary">🔄 대량크롤링 시작</a> ';
            html += '<a href="' + '<?php echo admin_url('edit.php?post_type=places'); ?>' + '" class="button">📋 장소 목록 확인</a>';
            
            $('#result-content').html(html);
            $('#cleanup-result').slideDown();
            $('#start-cleanup').prop('disabled', false);
        }
    });
    </script>
    <?php
}

// AJAX: 데이터 통계 가져오기
add_action('wp_ajax_sungsuya_get_cleanup_stats', 'sungsuya_ajax_get_cleanup_stats');
function sungsuya_ajax_get_cleanup_stats() {
    // nonce 확인
    if (!wp_verify_nonce($_POST['nonce'], 'sungsuya_cleanup_nonce')) {
        wp_die('보안 검증 실패');
    }
    
    global $wpdb;
    
    // 통계 수집
    $places_count = wp_count_posts('places');
    $total_places = $places_count->publish + $places_count->draft + $places_count->private + $places_count->trash;
    
    $meta_count = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE p.post_type = 'places'
    ");
    
    $attachment_count = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$wpdb->posts} 
        WHERE post_type = 'attachment' 
        AND post_parent IN (
            SELECT ID FROM {$wpdb->posts} WHERE post_type = 'places'
        )
    ");
    
    // 장소 유형은 시스템 필수 데이터이므로 카운트에서 제외
    $terms_count = 0; // wp_count_terms('place_type', array('hide_empty' => false));
    
    // 캐시 카운트
    $cache_count = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$wpdb->options} 
        WHERE option_name LIKE '_transient_%places%'
        OR option_name LIKE '_transient_%crawling%'
        OR option_name LIKE '_transient_%geocoding%'
    ");
    
    $html = '<table class="wp-list-table widefat fixed striped">';
    $html .= '<thead><tr><th>항목</th><th>개수</th><th>상태</th></tr></thead>';
    $html .= '<tbody>';
    $html .= '<tr><td>📍 장소 포스트</td><td>' . $total_places . '개</td><td>' . ($total_places > 0 ? '<span style="color: #ff9800;">데이터 있음</span>' : '<span style="color: #4caf50;">비어있음</span>') . '</td></tr>';
    $html .= '<tr><td>📊 메타데이터</td><td>' . $meta_count . '개</td><td>' . ($meta_count > 0 ? '<span style="color: #ff9800;">데이터 있음</span>' : '<span style="color: #4caf50;">비어있음</span>') . '</td></tr>';
    $html .= '<tr><td>🖼️ 첨부파일</td><td>' . $attachment_count . '개</td><td>' . ($attachment_count > 0 ? '<span style="color: #ff9800;">데이터 있음</span>' : '<span style="color: #4caf50;">비어있음</span>') . '</td></tr>';
    // 장소 유형은 시스템 필수 데이터이므로 표시하지 않음
    $html .= '<tr><td>💾 캐시 데이터</td><td>' . $cache_count . '개</td><td>' . ($cache_count > 0 ? '<span style="color: #ff9800;">캐시 있음</span>' : '<span style="color: #4caf50;">캐시 없음</span>') . '</td></tr>';
    $html .= '</tbody></table>';
    
    if ($total_places == 0 && $meta_count == 0 && $attachment_count == 0) {
        $html .= '<div style="margin-top: 20px; padding: 15px; background: #c8e6c9; border-radius: 5px;">';
        $html .= '<strong style="color: #2e7d32;">✅ 데이터베이스가 깨끗한 상태입니다!</strong>';
        $html .= '</div>';
    }
    
    wp_send_json_success(array('html' => $html));
}

// AJAX: 정리 실행
add_action('wp_ajax_sungsuya_execute_cleanup', 'sungsuya_ajax_execute_cleanup');
function sungsuya_ajax_execute_cleanup() {
    // nonce 확인
    if (!wp_verify_nonce($_POST['nonce'], 'sungsuya_cleanup_nonce')) {
        wp_die('보안 검증 실패');
    }
    
    // 권한 확인
    if (!current_user_can('manage_options')) {
        wp_die('권한이 없습니다.');
    }
    
    global $wpdb;
    $level = sanitize_text_field($_POST['level']);
    $logs = array();
    $result = array(
        'deleted_posts' => 0,
        'deleted_meta' => 0,
        'deleted_attachments' => 0,
        'deleted_terms' => 0,
        'deleted_cache' => 0
    );
    
    set_time_limit(300);
    
    // 1. 포스트 삭제
    $logs[] = array('message' => '📍 장소 포스트 검색 중...', 'type' => 'info');
    
    $places = get_posts(array(
        'post_type' => 'places',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'draft', 'private', 'trash'),
        'fields' => 'ids'
    ));
    
    $logs[] = array('message' => '발견된 장소: ' . count($places) . '개', 'type' => 'info');
    
    foreach ($places as $place_id) {
        // 썸네일 처리
        $thumbnail_id = get_post_thumbnail_id($place_id);
        if ($thumbnail_id) {
            wp_delete_attachment($thumbnail_id, true);
            $result['deleted_attachments']++;
        }
        
        // 추가 첨부파일
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_parent' => $place_id,
            'fields' => 'ids'
        ));
        
        foreach ($attachments as $att_id) {
            wp_delete_attachment($att_id, true);
            $result['deleted_attachments']++;
        }
        
        // 포스트 삭제
        if (wp_delete_post($place_id, true)) {
            $result['deleted_posts']++;
        }
    }
    
    $logs[] = array('message' => '✅ 포스트 ' . $result['deleted_posts'] . '개 삭제 완료', 'type' => 'success');
    
    // 2. 심층 정리 (deep, nuclear)
    if ($level === 'deep' || $level === 'nuclear') {
        // 캐시 정리
        $logs[] = array('message' => '💾 캐시 정리 중...', 'type' => 'info');
        
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%places%'
            OR option_name LIKE '_transient_%crawling%'
            OR option_name LIKE '_transient_%geocoding%'
        ");
        $result['deleted_cache'] = $wpdb->rows_affected;
        
        $logs[] = array('message' => '✅ 캐시 ' . $result['deleted_cache'] . '개 삭제 완료', 'type' => 'success');
        
        // 업로드 폴더 정리
        $upload_dir = wp_upload_dir();
        $dirs = array('static-maps', 'places', 'crawling-cache');
        
        foreach ($dirs as $dir) {
            $path = $upload_dir['basedir'] . '/' . $dir;
            if (is_dir($path)) {
                sungsuya_delete_directory($path);
                $logs[] = array('message' => '✅ 디렉토리 삭제: ' . $dir, 'type' => 'success');
            }
        }
    }
    
    // 3. 완전 삭제 (nuclear)
    if ($level === 'nuclear') {
        $logs[] = array('message' => '☢️ 완전 삭제 모드 실행...', 'type' => 'warning');
        
        // 고아 메타데이터
        $wpdb->query("
            DELETE pm FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        $result['deleted_meta'] += $wpdb->rows_affected;
        
        // 관련 옵션 모두 삭제
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '%places%'
            OR option_name LIKE '%crawling%'
            OR option_name LIKE '%geocoding%'
        ");
        
        // DB 최적화
        $tables = array($wpdb->posts, $wpdb->postmeta, $wpdb->options);
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE $table");
        }
        
        $logs[] = array('message' => '✅ 데이터베이스 최적화 완료', 'type' => 'success');
    }
    
    // 택소노미 정리 - 장소 유형은 시스템 필수이므로 제외
    /*
    $terms = get_terms(array(
        'taxonomy' => 'place_type',
        'hide_empty' => false,
        'fields' => 'ids'
    ));
    
    if (!is_wp_error($terms)) {
        foreach ($terms as $term_id) {
            wp_delete_term($term_id, 'place_type');
            $result['deleted_terms']++;
        }
    }
    */
    $result['deleted_terms'] = 0; // 장소 유형은 삭제하지 않음
    
    $logs[] = array('message' => '🎉 모든 정리 작업이 완료되었습니다!', 'type' => 'success');
    
    wp_send_json_success(array(
        'logs' => $logs,
        'deleted_posts' => $result['deleted_posts'],
        'deleted_meta' => $result['deleted_meta'],
        'deleted_attachments' => $result['deleted_attachments'],
        'deleted_terms' => $result['deleted_terms'],
        'deleted_cache' => $result['deleted_cache']
    ));
}

// 디렉토리 삭제 헬퍼 함수
function sungsuya_delete_directory($dir) {
    if (!is_dir($dir)) return;
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            sungsuya_delete_directory($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}
