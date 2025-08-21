<?php
/**
 * 성수야! V2 - 관리자용 썸네일 관리 시스템
 * 
 * WordPress 표준 준수 썸네일 업로드/관리 시스템
 * - 목록보기: 모든 Places의 썸네일 현황
 * - 개별업로드: WordPress 미디어 라이브러리 활용
 * - 일괄업로드: 파일명 매칭으로 자동 할당
 * 
 * @package SungsuyaV2
 * @version 1.0.0
 */

// WordPress 보안 체크
if (!defined('ABSPATH')) {
    exit;
}

// 관리자 권한 체크
if (!current_user_can('edit_posts')) {
    wp_die('권한이 없습니다.');
}

class SungsuyaThumbnailManager {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_upload_place_thumbnail', array($this, 'ajax_upload_thumbnail'));
        add_action('wp_ajax_remove_place_thumbnail', array($this, 'ajax_remove_thumbnail'));
        add_action('wp_ajax_bulk_assign_thumbnails', array($this, 'ajax_bulk_assign'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=places',
            '📸 썸네일 관리',
            '📸 썸네일 관리',
            'edit_posts',
            'places-thumbnail-manager',
            array($this, 'admin_page')
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'places_page_places-thumbnail-manager') {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_script('jquery');
        
        // 인라인 스크립트 추가
        wp_add_inline_script('jquery', $this->get_inline_scripts());
        wp_add_inline_style('wp-admin', $this->get_inline_styles());
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>📸 Places 썸네일 관리 시스템</h1>
            
            <!-- 통계 대시보드 -->
            <div class="thumbnail-stats-dashboard">
                <?php $this->render_stats_dashboard(); ?>
            </div>
            
            <!-- 탭 네비게이션 -->
            <h2 class="nav-tab-wrapper">
                <a href="#tab-overview" class="nav-tab nav-tab-active">📊 현황 보기</a>
                <a href="#tab-upload" class="nav-tab">🖼️ 개별 업로드</a>
                <a href="#tab-bulk" class="nav-tab">📁 일괄 업로드</a>
            </h2>
            
            <!-- 탭 내용 -->
            <div id="tab-overview" class="tab-content">
                <?php $this->render_overview_tab(); ?>
            </div>
            
            <div id="tab-upload" class="tab-content" style="display: none;">
                <?php $this->render_upload_tab(); ?>
            </div>
            
            <div id="tab-bulk" class="tab-content" style="display: none;">
                <?php $this->render_bulk_tab(); ?>
            </div>
        </div>
        
        <!-- 모달창들 -->
        <div id="thumbnail-modal" class="thumbnail-modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div id="modal-body"></div>
            </div>
        </div>
        <?php
    }
    
    private function render_stats_dashboard() {
        $stats = $this->get_thumbnail_stats();
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">총 Places</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number"><?php echo $stats['with_thumbnail']; ?></div>
                <div class="stat-label">썸네일 있음</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?php echo $stats['without_thumbnail']; ?></div>
                <div class="stat-label">썸네일 없음</div>
            </div>
            <div class="stat-card info">
                <div class="stat-number"><?php echo $stats['completion_rate']; ?>%</div>
                <div class="stat-label">완성도</div>
            </div>
        </div>
        <?php
    }
    
    private function render_overview_tab() {
        $places = $this->get_all_places();
        ?>
        <div class="places-grid">
            <?php foreach ($places as $place): ?>
                <div class="place-card" data-place-id="<?php echo $place->ID; ?>">
                    <div class="place-thumbnail">
                        <?php if (has_post_thumbnail($place->ID)): ?>
                            <div class="thumbnail-status success">🟢</div>
                            <?php echo get_the_post_thumbnail($place->ID, 'medium', array('class' => 'thumbnail-image')); ?>
                            <div class="thumbnail-actions">
                                <button class="button change-thumbnail" data-place-id="<?php echo $place->ID; ?>">변경</button>
                                <button class="button remove-thumbnail" data-place-id="<?php echo $place->ID; ?>">제거</button>
                            </div>
                        <?php else: ?>
                            <div class="thumbnail-status missing">🔴</div>
                            <div class="no-thumbnail">
                                <div class="placeholder-icon">🏪</div>
                                <div class="placeholder-text">썸네일 없음</div>
                                <button class="button button-primary upload-thumbnail" data-place-id="<?php echo $place->ID; ?>">
                                    📸 업로드
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="place-info">
                        <h3><?php echo esc_html($place->post_title); ?></h3>
                        <div class="place-meta">
                            <?php
                            $terms = wp_get_post_terms($place->ID, 'place_type');
                            if (!empty($terms)) {
                                echo '<span class="place-type">' . $terms[0]->name . '</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    private function render_upload_tab() {
        $places = $this->get_all_places();
        ?>
        <div class="upload-section">
            <h3>🖼️ 개별 썸네일 업로드</h3>
            <p>Places를 선택하고 WordPress 미디어 라이브러리에서 썸네일을 설정하세요.</p>
            
            <div class="upload-form">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Places명</th>
                            <th>카테고리</th>
                            <th>현재 썸네일</th>
                            <th>액션</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($places as $place): ?>
                            <tr>
                                <td><strong><?php echo esc_html($place->post_title); ?></strong></td>
                                <td>
                                    <?php
                                    $terms = wp_get_post_terms($place->ID, 'place_type');
                                    echo !empty($terms) ? $terms[0]->name : '미분류';
                                    ?>
                                </td>
                                <td>
                                    <?php if (has_post_thumbnail($place->ID)): ?>
                                        <span class="status-badge success">✅ 있음</span>
                                    <?php else: ?>
                                        <span class="status-badge missing">❌ 없음</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="button upload-thumbnail" data-place-id="<?php echo $place->ID; ?>">
                                        📸 썸네일 설정
                                    </button>
                                    <?php if (has_post_thumbnail($place->ID)): ?>
                                        <button class="button remove-thumbnail" data-place-id="<?php echo $place->ID; ?>">
                                            🗑️ 제거
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    private function render_bulk_tab() {
        ?>
        <div class="bulk-upload-section">
            <h3>📁 일괄 썸네일 할당</h3>
            <p>파일명과 Places명을 매칭하여 자동으로 썸네일을 할당합니다.</p>
            
            <div class="bulk-upload-form">
                <div class="upload-step">
                    <h4>1단계: 이미지 파일들을 미디어 라이브러리에 업로드</h4>
                    <p>WordPress 관리자 → 미디어 → 새로 추가에서 이미지들을 업로드하세요.</p>
                    <a href="<?php echo admin_url('media-new.php'); ?>" class="button button-secondary" target="_blank">
                        📁 미디어 라이브러리 열기
                    </a>
                </div>
                
                <div class="upload-step">
                    <h4>2단계: 자동 매칭 및 할당</h4>
                    <p>파일명에 Places명이 포함된 이미지를 자동으로 찾아 할당합니다.</p>
                    <button id="bulk-assign-btn" class="button button-primary">
                        🔄 자동 매칭 시작
                    </button>
                </div>
                
                <div id="bulk-results" class="bulk-results" style="display: none;">
                    <h4>매칭 결과</h4>
                    <div id="bulk-results-content"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function get_thumbnail_stats() {
        $args = array(
            'post_type' => 'places',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );
        
        $places = get_posts($args);
        $total = count($places);
        $with_thumbnail = 0;
        
        foreach ($places as $place) {
            if (has_post_thumbnail($place->ID)) {
                $with_thumbnail++;
            }
        }
        
        $without_thumbnail = $total - $with_thumbnail;
        $completion_rate = $total > 0 ? round(($with_thumbnail / $total) * 100, 1) : 0;
        
        return array(
            'total' => $total,
            'with_thumbnail' => $with_thumbnail,
            'without_thumbnail' => $without_thumbnail,
            'completion_rate' => $completion_rate
        );
    }
    
    private function get_all_places() {
        $args = array(
            'post_type' => 'places',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        return get_posts($args);
    }
    
    public function ajax_upload_thumbnail() {
        check_ajax_referer('thumbnail_manager_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('권한이 없습니다.');
        }
        
        $place_id = intval($_POST['place_id']);
        $attachment_id = intval($_POST['attachment_id']);
        
        if ($place_id && $attachment_id) {
            set_post_thumbnail($place_id, $attachment_id);
            wp_send_json_success('썸네일이 설정되었습니다.');
        } else {
            wp_send_json_error('잘못된 요청입니다.');
        }
    }
    
    public function ajax_remove_thumbnail() {
        check_ajax_referer('thumbnail_manager_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('권한이 없습니다.');
        }
        
        $place_id = intval($_POST['place_id']);
        
        if ($place_id) {
            delete_post_thumbnail($place_id);
            wp_send_json_success('썸네일이 제거되었습니다.');
        } else {
            wp_send_json_error('잘못된 요청입니다.');
        }
    }
    
    public function ajax_bulk_assign() {
        check_ajax_referer('thumbnail_manager_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('권한이 없습니다.');
        }
        
        $results = $this->perform_bulk_assignment();
        wp_send_json_success($results);
    }
    
    private function perform_bulk_assignment() {
        $places = $this->get_all_places();
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_mime_type' => 'image'
        ));
        
        $results = array(
            'matched' => 0,
            'skipped' => 0,
            'details' => array()
        );
        
        foreach ($places as $place) {
            if (has_post_thumbnail($place->ID)) {
                $results['skipped']++;
                continue;
            }
            
            $place_name = $place->post_title;
            $place_keywords = explode(' ', $place_name);
            
            foreach ($attachments as $attachment) {
                $filename = basename(get_attached_file($attachment->ID));
                $filename_clean = pathinfo($filename, PATHINFO_FILENAME);
                
                $match_found = false;
                foreach ($place_keywords as $keyword) {
                    if (strlen($keyword) > 2 && stripos($filename_clean, $keyword) !== false) {
                        $match_found = true;
                        break;
                    }
                }
                
                if ($match_found) {
                    set_post_thumbnail($place->ID, $attachment->ID);
                    $results['matched']++;
                    $results['details'][] = array(
                        'place' => $place_name,
                        'image' => $filename
                    );
                    break;
                }
            }
        }
        
        return $results;
    }
    
    private function get_inline_scripts() {
        return "
        jQuery(document).ready(function($) {
            // 탭 네비게이션
            $('.nav-tab').click(function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').hide();
                $(target).show();
            });
            
            // 썸네일 업로드
            $(document).on('click', '.upload-thumbnail, .change-thumbnail', function(e) {
                e.preventDefault();
                var placeId = $(this).data('place-id');
                var button = $(this);
                
                // wp.media가 로드되었는지 확인
                if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                    alert('미디어 라이브러리를 로드할 수 없습니다. 페이지를 새로고침해주세요.');
                    return;
                }
                
                var mediaUploader = wp.media({
                    title: '썸네일 선택',
                    button: { text: '썸네일로 설정' },
                    multiple: false,
                    library: { type: 'image' }
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    
                    $.post(ajaxurl, {
                        action: 'upload_place_thumbnail',
                        nonce: '" . wp_create_nonce('thumbnail_manager_nonce') . "',
                        place_id: placeId,
                        attachment_id: attachment.id
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('오류: ' + response.data);
                        }
                    });
                });
                
                mediaUploader.open();
            });
            
            // 썸네일 제거
            $(document).on('click', '.remove-thumbnail', function(e) {
                e.preventDefault();
                if (!confirm('정말 썸네일을 제거하시겠습니까?')) return;
                
                var placeId = $(this).data('place-id');
                
                $.post(ajaxurl, {
                    action: 'remove_place_thumbnail',
                    nonce: '" . wp_create_nonce('thumbnail_manager_nonce') . "',
                    place_id: placeId
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('오류: ' + response.data);
                    }
                });
            });
            
            // 일괄 할당
            $('#bulk-assign-btn').click(function() {
                var button = $(this);
                button.prop('disabled', true).text('처리 중...');
                
                $.post(ajaxurl, {
                    action: 'bulk_assign_thumbnails',
                    nonce: '" . wp_create_nonce('thumbnail_manager_nonce') . "'
                }, function(response) {
                    button.prop('disabled', false).text('🔄 자동 매칭 시작');
                    
                    if (response.success) {
                        var results = response.data;
                        var html = '<p><strong>매칭 완료:</strong> ' + results.matched + '개, <strong>건너뜀:</strong> ' + results.skipped + '개</p>';
                        
                        if (results.details.length > 0) {
                            html += '<ul>';
                            results.details.forEach(function(detail) {
                                html += '<li>' + detail.place + ' ← ' + detail.image + '</li>';
                            });
                            html += '</ul>';
                        }
                        
                        $('#bulk-results-content').html(html);
                        $('#bulk-results').show();
                        
                        if (results.matched > 0) {
                            setTimeout(function() { location.reload(); }, 2000);
                        }
                    } else {
                        alert('오류: ' + response.data);
                    }
                });
            });
        });
        ";
    }
    
    private function get_inline_styles() {
        return "
        .thumbnail-stats-dashboard {
            margin: 20px 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-card.success { border-left: 4px solid #46b450; }
        .stat-card.warning { border-left: 4px solid #ffba00; }
        .stat-card.info { border-left: 4px solid #00a0d2; }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #23282d;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }
        
        .places-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .place-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .place-thumbnail {
            position: relative;
            height: 150px;
            background: #f9f9f9;
        }
        
        .thumbnail-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .thumbnail-status {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        .no-thumbnail {
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #666;
        }
        
        .placeholder-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .thumbnail-actions {
            position: absolute;
            bottom: 10px;
            left: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
        }
        
        .place-info {
            padding: 15px;
        }
        
        .place-info h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        
        .place-type {
            background: #0073aa;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
        }
        
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-badge.success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.missing {
            background: #f8d7da;
            color: #721c24;
        }
        
        .upload-step {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .upload-step h4 {
            margin-top: 0;
        }
        
        .bulk-results {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .thumbnail-modal {
            display: none;
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            position: relative;
        }
        
        .close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        ";
    }
}

// 시스템 초기화
new SungsuyaThumbnailManager();
?>