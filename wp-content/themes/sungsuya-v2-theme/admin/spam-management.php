<?php
/**
 * 스팸 관리 페이지
 */

// 권한 확인
if (!current_user_can('manage_options')) {
    wp_die(__('권한이 없습니다.'));
}

// 스팸 감지기 초기화
$detector = Sungsuya_Spam_Detector::get_instance();

// 액션 처리
if (isset($_POST['action'])) {
    if (wp_verify_nonce($_POST['_wpnonce'], 'spam_management')) {
        switch ($_POST['action']) {
            case 'add_keyword':
                if (!empty($_POST['keyword'])) {
                    $detector->add_spam_keyword(sanitize_text_field($_POST['keyword']));
                    echo '<div class="notice notice-success"><p>키워드가 추가되었습니다.</p></div>';
                }
                break;
                
            case 'bulk_check':
                $result = $detector->bulk_check_spam(intval($_POST['limit'] ?? 100));
                echo '<div class="notice notice-success"><p>';
                echo sprintf('%d개 리뷰 검사 완료. %d개 스팸 발견.', 
                    $result['checked'], $result['spam_found']);
                echo '</p></div>';
                break;
                
            case 'auto_delete_spam':
                global $wpdb;
                $table_name = $wpdb->prefix . 'place_reviews';
                $deleted = $wpdb->delete($table_name, array(
                    'is_spam' => 1,
                    'spam_score' => array('value' => 10, 'compare' => '>=')
                ));
                echo '<div class="notice notice-success"><p>';
                echo sprintf('%d개의 스팸 리뷰가 삭제되었습니다.', $deleted);
                echo '</p></div>';
                break;
        }
    }
}

// 키워드 삭제 처리
if (isset($_GET['remove_keyword']) && isset($_GET['_wpnonce'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'remove_keyword')) {
        $detector->remove_spam_keyword($_GET['remove_keyword']);
        wp_redirect(remove_query_arg(array('remove_keyword', '_wpnonce')));
        exit;
    }
}

// 통계 가져오기
global $wpdb;
$table_name = $wpdb->prefix . 'place_reviews';

$total_reviews = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$unchecked_reviews = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE spam_checked = 0 OR spam_checked IS NULL");
$spam_reviews = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_spam = 1");
$hidden_spam = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_spam = 1 AND is_hidden = 1");

// 최근 스팸 리뷰
$recent_spam = $wpdb->get_results("
    SELECT r.*, p.post_title as place_name
    FROM $table_name r
    LEFT JOIN {$wpdb->posts} p ON r.place_id = p.ID
    WHERE r.is_spam = 1
    ORDER BY r.created_at DESC
    LIMIT 10
");

?>

<div class="wrap">
    <h1>스팸 관리</h1>
    
    <!-- 통계 대시보드 -->
    <div class="spam-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="stat-card" style="background: white; padding: 20px; border: 1px solid #ddd; text-align: center;">
            <h3 style="margin: 0; font-size: 32px; color: #0073aa;"><?php echo number_format($total_reviews); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">전체 리뷰</p>
        </div>
        
        <div class="stat-card" style="background: white; padding: 20px; border: 1px solid #ddd; text-align: center;">
            <h3 style="margin: 0; font-size: 32px; color: #ff9800;"><?php echo number_format($unchecked_reviews); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">미검사 리뷰</p>
        </div>
        
        <div class="stat-card" style="background: white; padding: 20px; border: 1px solid #ddd; text-align: center;">
            <h3 style="margin: 0; font-size: 32px; color: #f44336;"><?php echo number_format($spam_reviews); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">스팸 리뷰</p>
        </div>
        
        <div class="stat-card" style="background: white; padding: 20px; border: 1px solid #ddd; text-align: center;">
            <h3 style="margin: 0; font-size: 32px; color: #4caf50;"><?php echo number_format($hidden_spam); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">차단된 스팸</p>
        </div>
    </div>
    
    <!-- 자동 스팸 검사 -->
    <div class="card" style="background: white; padding: 20px; margin-bottom: 20px;">
        <h2>자동 스팸 검사</h2>
        <p>아직 검사하지 않은 리뷰를 자동으로 스팸 검사합니다.</p>
        
        <form method="post" style="display: inline;">
            <?php wp_nonce_field('spam_management'); ?>
            <input type="hidden" name="action" value="bulk_check">
            <label>
                검사할 리뷰 수:
                <input type="number" name="limit" value="100" min="1" max="1000" style="width: 100px;">
            </label>
            <button type="submit" class="button button-primary">스팸 검사 실행</button>
        </form>
        
        <?php if ($unchecked_reviews > 0): ?>
            <p style="color: #ff9800; margin-top: 10px;">
                ⚠️ <?php echo number_format($unchecked_reviews); ?>개의 리뷰가 검사 대기 중입니다.
            </p>
        <?php endif; ?>
    </div>
    
    <!-- 스팸 키워드 관리 -->
    <div class="card" style="background: white; padding: 20px; margin-bottom: 20px;">
        <h2>스팸 키워드 관리</h2>
        
        <!-- 키워드 추가 -->
        <form method="post" style="margin-bottom: 20px;">
            <?php wp_nonce_field('spam_management'); ?>
            <input type="hidden" name="action" value="add_keyword">
            <input type="text" name="keyword" placeholder="새 키워드 입력" style="width: 300px;">
            <button type="submit" class="button">키워드 추가</button>
        </form>
        
        <!-- 현재 키워드 목록 -->
        <h3>사용자 정의 키워드</h3>
        <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 200px; overflow-y: auto;">
            <?php 
            $custom_keywords = $detector->get_custom_keywords();
            if (empty($custom_keywords)): 
            ?>
                <p style="color: #666;">추가된 키워드가 없습니다.</p>
            <?php else: ?>
                <?php foreach ($custom_keywords as $keyword): ?>
                    <span style="display: inline-block; background: white; padding: 5px 10px; margin: 5px; border-radius: 3px;">
                        <?php echo esc_html($keyword); ?>
                        <a href="<?php echo wp_nonce_url(add_query_arg('remove_keyword', $keyword), 'remove_keyword'); ?>" 
                           style="color: red; text-decoration: none; margin-left: 5px;">×</a>
                    </span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <details style="margin-top: 15px;">
            <summary style="cursor: pointer; color: #0073aa;">기본 스팸 키워드 보기</summary>
            <div style="background: #f5f5f5; padding: 15px; margin-top: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto;">
                <p style="font-size: 12px; color: #666;">
                    광고, 홍보, 마케팅, 할인, 이벤트, 무료, 공짜, 특가, 최저가, 파격, 득템, 한정, 선착순, 쿠폰, 프로모션,
                    카지노, 바카라, 토토, 베팅, 도박, 성인, 19금, 다이어트, 살빼기, 지방흡입, 성형, 시술, 병원,
                    대출, 대부, 금융, 투자, 주식, 코인, 비트코인 등
                </p>
            </div>
        </details>
    </div>
    
    <!-- 최근 스팸 리뷰 -->
    <div class="card" style="background: white; padding: 20px; margin-bottom: 20px;">
        <h2>최근 감지된 스팸 리뷰</h2>
        
        <?php if (empty($recent_spam)): ?>
            <p>감지된 스팸이 없습니다.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 100px;">날짜</th>
                        <th style="width: 150px;">장소</th>
                        <th style="width: 100px;">작성자</th>
                        <th>내용</th>
                        <th style="width: 80px;">스팸 점수</th>
                        <th style="width: 150px;">감지 이유</th>
                        <th style="width: 100px;">상태</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_spam as $spam): 
                        $reasons = json_decode($spam->spam_reasons, true) ?: array();
                    ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($spam->created_at)); ?></td>
                            <td>
                                <?php if ($spam->place_name): ?>
                                    <a href="<?php echo get_permalink($spam->place_id); ?>" target="_blank">
                                        <?php echo esc_html($spam->place_name); ?>
                                    </a>
                                <?php else: ?>
                                    <em>삭제된 장소</em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($spam->user_name); ?></td>
                            <td>
                                <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo esc_html($spam->review_text); ?>
                                </div>
                            </td>
                            <td>
                                <span style="color: <?php echo $spam->spam_score >= 10 ? 'red' : ($spam->spam_score >= 5 ? 'orange' : 'gray'); ?>; font-weight: bold;">
                                    <?php echo $spam->spam_score; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($reasons)): ?>
                                    <ul style="margin: 0; font-size: 12px;">
                                        <?php foreach (array_slice($reasons, 0, 3) as $reason): ?>
                                            <li><?php echo esc_html($reason); ?></li>
                                        <?php endforeach; ?>
                                        <?php if (count($reasons) > 3): ?>
                                            <li>... 외 <?php echo count($reasons) - 3; ?>개</li>
                                        <?php endif; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($spam->is_hidden): ?>
                                    <span style="color: green;">✓ 차단됨</span>
                                <?php else: ?>
                                    <span style="color: red;">⚠️ 노출중</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p style="margin-top: 15px;">
                <a href="<?php echo admin_url('edit.php?post_type=places&page=place-reviews&status=spam'); ?>" 
                   class="button">전체 스팸 리뷰 보기</a>
            </p>
        <?php endif; ?>
    </div>
    
    <!-- 자동 삭제 -->
    <div class="card" style="background: white; padding: 20px;">
        <h2>스팸 일괄 처리</h2>
        <p>스팸 점수가 10점 이상인 리뷰를 자동으로 삭제합니다.</p>
        
        <form method="post" onsubmit="return confirm('정말로 스팸 리뷰를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.');">
            <?php wp_nonce_field('spam_management'); ?>
            <input type="hidden" name="action" value="auto_delete_spam">
            <button type="submit" class="button button-link-delete">고위험 스팸 삭제</button>
        </form>
    </div>
</div>

<style>
.card {
    border: 1px solid #ccc;
    border-radius: 5px;
}

.card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.card h3 {
    margin-top: 20px;
    margin-bottom: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 실시간 스팸 검사 진행률 표시
    $('#run-spam-check').on('click', function() {
        var $button = $(this);
        var $progress = $('#spam-check-progress');
        
        $button.prop('disabled', true);
        $progress.show();
        
        // AJAX로 스팸 검사 실행
        $.post(ajaxurl, {
            action: 'check_review_spam',
            _wpnonce: '<?php echo wp_create_nonce('spam_check'); ?>'
        }, function(response) {
            if (response.success) {
                alert('스팸 검사 완료! ' + response.data.checked + '개 검사, ' + response.data.spam_found + '개 스팸 발견');
                location.reload();
            }
        }).always(function() {
            $button.prop('disabled', false);
            $progress.hide();
        });
    });
});
</script>
