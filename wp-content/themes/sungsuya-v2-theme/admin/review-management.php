<?php
/**
 * 리뷰 관리 페이지
 */

// 권한 확인
if (!current_user_can('manage_options')) {
    wp_die(__('권한이 없습니다.'));
}

// 액션 처리
if (isset($_GET['action']) && isset($_GET['review_id'])) {
    $action = $_GET['action'];
    $review_id = intval($_GET['review_id']);
    $nonce = $_GET['_wpnonce'] ?? '';
    
    if (wp_verify_nonce($nonce, 'review_action')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'place_reviews';
        
        switch ($action) {
            case 'delete':
                $wpdb->delete($table_name, array('id' => $review_id));
                wp_redirect(add_query_arg('message', 'deleted', remove_query_arg(['action', 'review_id', '_wpnonce'])));
                exit;
                break;
                
            case 'hide':
                $wpdb->update($table_name, array('is_hidden' => 1), array('id' => $review_id));
                wp_redirect(add_query_arg('message', 'hidden', remove_query_arg(['action', 'review_id', '_wpnonce'])));
                exit;
                break;
                
            case 'show':
                $wpdb->update($table_name, array('is_hidden' => 0), array('id' => $review_id));
                wp_redirect(add_query_arg('message', 'shown', remove_query_arg(['action', 'review_id', '_wpnonce'])));
                exit;
                break;
        }
    }
}

// 페이지네이션
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;

// 필터
$place_id = isset($_GET['place_id']) ? intval($_GET['place_id']) : 0;
$rating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// 리뷰 가져오기
global $wpdb;
$table_name = $wpdb->prefix . 'place_reviews';

// 쿼리 생성
$where = array('1=1');
$where_values = array();

if ($place_id) {
    $where[] = 'place_id = %d';
    $where_values[] = $place_id;
}

if ($rating) {
    $where[] = 'rating = %d';
    $where_values[] = $rating;
}

if ($status === 'reported') {
    $where[] = 'report_count > 0';
} elseif ($status === 'hidden') {
    $where[] = 'is_hidden = 1';
} elseif ($status === 'spam') {
    $where[] = 'is_spam = 1';
}

$where_clause = implode(' AND ', $where);

// 전체 개수 가져오기
$total_query = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";
if (!empty($where_values)) {
    $total_query = $wpdb->prepare($total_query, $where_values);
}
$total_items = $wpdb->get_var($total_query);

// 리뷰 목록 가져오기
$query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
$query_values = array_merge($where_values, array($per_page, $offset));
$reviews = $wpdb->get_results($wpdb->prepare($query, $query_values));

// 장소 목록 가져오기 (필터용)
$places = get_posts(array(
    'post_type' => 'places',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
));

?>

<div class="wrap">
    <h1 class="wp-heading-inline">리뷰 관리</h1>
    
    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                switch ($_GET['message']) {
                    case 'deleted':
                        echo '리뷰가 삭제되었습니다.';
                        break;
                    case 'hidden':
                        echo '리뷰가 숨김 처리되었습니다.';
                        break;
                    case 'shown':
                        echo '리뷰가 공개 처리되었습니다.';
                        break;
                }
                ?>
            </p>
        </div>
    <?php endif; ?>
    
    <!-- 필터 -->
    <div class="tablenav top">
        <form method="get" style="display: inline-block;">
            <input type="hidden" name="post_type" value="places">
            <input type="hidden" name="page" value="place-reviews">
            
            <select name="place_id" onchange="this.form.submit()">
                <option value="">모든 장소</option>
                <?php foreach ($places as $place): ?>
                    <option value="<?php echo $place->ID; ?>" <?php selected($place_id, $place->ID); ?>>
                        <?php echo esc_html($place->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="rating" onchange="this.form.submit()">
                <option value="">모든 평점</option>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?php echo $i; ?>" <?php selected($rating, $i); ?>>
                        <?php echo str_repeat('★', $i); ?> (<?php echo $i; ?>점)
                    </option>
                <?php endfor; ?>
            </select>
            
            <select name="status" onchange="this.form.submit()">
                <option value="">모든 상태</option>
                <option value="reported" <?php selected($status, 'reported'); ?>>신고된 리뷰</option>
                <option value="hidden" <?php selected($status, 'hidden'); ?>>숨김 리뷰</option>
                <option value="spam" <?php selected($status, 'spam'); ?>>스팸 리뷰</option>
            </select>
            
            <?php if ($place_id || $rating || $status): ?>
                <a href="?post_type=places&page=place-reviews" class="button">필터 초기화</a>
            <?php endif; ?>
        </form>
        
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo $total_items; ?>개 항목</span>
            <?php
            $pagination = paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => ceil($total_items / $per_page),
                'current' => $paged,
                'type' => 'array'
            ));
            
            if ($pagination) {
                echo '<span class="pagination-links">';
                echo implode(' ', $pagination);
                echo '</span>';
            }
            ?>
        </div>
    </div>
    
    <!-- 리뷰 테이블 -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th>장소</th>
                <th style="width: 100px;">작성자</th>
                <th style="width: 100px;">평점</th>
                <th>내용</th>
                <th style="width: 100px;">작성일</th>
                <th style="width: 80px;">좋아요</th>
                <th style="width: 80px;">신고</th>
                <th style="width: 150px;">작업</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reviews)): ?>
                <tr>
                    <td colspan="9" style="text-align: center;">리뷰가 없습니다.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($reviews as $review): 
                    $place = get_post($review->place_id);
                    $review_images = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}review_images WHERE review_id = %d",
                        $review->id
                    ));
                ?>
                    <tr <?php if ($review->is_hidden): ?>style="opacity: 0.6;"<?php endif; ?>>
                        <td><?php echo $review->id; ?></td>
                        <td>
                            <?php if ($place): ?>
                                <a href="<?php echo get_edit_post_link($place->ID); ?>">
                                    <?php echo esc_html($place->post_title); ?>
                                </a>
                            <?php else: ?>
                                <em>삭제된 장소</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo esc_html($review->user_name); ?>
                            <?php if ($review->user_email): ?>
                                <br><small><?php echo esc_html($review->user_email); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="color: #ffc107;">
                                <?php echo str_repeat('★', $review->rating); ?><?php echo str_repeat('☆', 5 - $review->rating); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($review->review_text): ?>
                                <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo esc_html($review->review_text); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($review_images)): ?>
                                <div style="margin-top: 5px;">
                                    📷 사진 <?php echo count($review_images); ?>장
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo date('Y-m-d', strtotime($review->created_at)); ?>
                            <br>
                            <small><?php echo date('H:i', strtotime($review->created_at)); ?></small>
                        </td>
                        <td><?php echo $review->likes_count; ?></td>
                        <td>
                            <?php if ($review->report_count > 0): ?>
                                <span style="color: red;">⚠️ <?php echo $review->report_count; ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $action_url = wp_nonce_url(
                                add_query_arg(array(
                                    'post_type' => 'places',
                                    'page' => 'place-reviews',
                                    'review_id' => $review->id
                                )),
                                'review_action'
                            );
                            ?>
                            
                            <?php if ($review->is_hidden): ?>
                                <a href="<?php echo add_query_arg('action', 'show', $action_url); ?>" 
                                   class="button button-small">공개</a>
                            <?php else: ?>
                                <a href="<?php echo add_query_arg('action', 'hide', $action_url); ?>" 
                                   class="button button-small">숨김</a>
                            <?php endif; ?>
                            
                            <a href="<?php echo add_query_arg('action', 'delete', $action_url); ?>" 
                               class="button button-small button-link-delete"
                               onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
                            
                            <?php if ($place): ?>
                                <a href="<?php echo get_permalink($place->ID); ?>#review-<?php echo $review->id; ?>" 
                                   target="_blank" class="button button-small">보기</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- 통계 -->
    <div style="margin-top: 40px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <div class="card">
            <h3>평점 통계</h3>
            <?php
            $rating_stats = $wpdb->get_results("
                SELECT rating, COUNT(*) as count 
                FROM $table_name 
                GROUP BY rating 
                ORDER BY rating DESC
            ");
            
            $total_reviews = array_sum(array_column($rating_stats, 'count'));
            $total_score = 0;
            
            foreach ($rating_stats as $stat) {
                $total_score += $stat->rating * $stat->count;
            }
            
            $avg_rating = $total_reviews > 0 ? round($total_score / $total_reviews, 1) : 0;
            ?>
            
            <div style="font-size: 24px; margin-bottom: 10px;">
                평균: <strong><?php echo $avg_rating; ?></strong> / 5.0
            </div>
            
            <?php foreach ($rating_stats as $stat): ?>
                <div style="margin-bottom: 5px;">
                    <span style="color: #ffc107;">
                        <?php echo str_repeat('★', $stat->rating); ?><?php echo str_repeat('☆', 5 - $stat->rating); ?>
                    </span>
                    : <?php echo $stat->count; ?>개
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card">
            <h3>최근 활동</h3>
            <?php
            $recent_reviews = $wpdb->get_results("
                SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM $table_name 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            ?>
            
            <?php foreach ($recent_reviews as $day): ?>
                <div style="margin-bottom: 5px;">
                    <?php echo date('m월 d일', strtotime($day->date)); ?>: 
                    <strong><?php echo $day->count; ?>개</strong>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card">
            <h3>인기 장소</h3>
            <?php
            $popular_places = $wpdb->get_results("
                SELECT place_id, COUNT(*) as review_count, AVG(rating) as avg_rating
                FROM $table_name
                GROUP BY place_id
                ORDER BY review_count DESC
                LIMIT 5
            ");
            ?>
            
            <?php foreach ($popular_places as $place_stat): 
                $place = get_post($place_stat->place_id);
                if (!$place) continue;
            ?>
                <div style="margin-bottom: 8px;">
                    <a href="<?php echo get_edit_post_link($place->ID); ?>">
                        <?php echo esc_html($place->post_title); ?>
                    </a>
                    <br>
                    <small>
                        리뷰 <?php echo $place_stat->review_count; ?>개, 
                        평균 <?php echo round($place_stat->avg_rating, 1); ?>점
                    </small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.card {
    background: white;
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.card h3 {
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.wp-list-table .button-small {
    margin-right: 5px;
}

.pagination-links a,
.pagination-links span {
    display: inline-block;
    padding: 4px 8px;
    margin: 0 2px;
    text-decoration: none;
}

.pagination-links .current {
    background: #0073aa;
    color: white;
    border-radius: 3px;
}
</style>
