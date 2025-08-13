<?php
/**
 * 회원 관리 페이지
 * 
 * 가입한 회원 목록 및 상세 정보 관리
 */

if (!defined('ABSPATH')) {
    exit;
}

// 액션 처리
$action = isset($_GET['action']) ? $_GET['action'] : '';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// 사용자 삭제 처리
if ($action === 'delete' && $user_id && check_admin_referer('delete_user_' . $user_id)) {
    if (current_user_can('delete_users') && $user_id !== get_current_user_id()) {
        wp_delete_user($user_id);
        echo '<div class="notice notice-success"><p>사용자가 삭제되었습니다.</p></div>';
    }
}

// 페이지네이션
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;

// 검색
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filter_provider = isset($_GET['provider']) ? sanitize_text_field($_GET['provider']) : '';

// 사용자 쿼리
$args = array(
    'number' => $per_page,
    'paged' => $paged,
    'orderby' => 'registered',
    'order' => 'DESC'
);

if ($search) {
    $args['search'] = '*' . $search . '*';
}

if ($filter_provider) {
    $args['meta_key'] = 'social_provider';
    $args['meta_value'] = $filter_provider;
}

$user_query = new WP_User_Query($args);
$users = $user_query->get_results();
$total_users = $user_query->get_total();
$total_pages = ceil($total_users / $per_page);

// 통계 데이터
$stats = array(
    'total' => count_users()['total_users'],
    'google' => count(get_users(array('meta_key' => 'social_provider', 'meta_value' => 'google'))),
    'kakao' => count(get_users(array('meta_key' => 'social_provider', 'meta_value' => 'kakao'))),
    'email' => count(get_users(array('meta_key' => 'social_provider', 'meta_value' => 'email')))
);
$stats['guest'] = $stats['total'] - $stats['google'] - $stats['kakao'] - $stats['email'];
?>

<div class="wrap">
    <h1 class="wp-heading-inline">👥 회원 관리</h1>
    
    <!-- 통계 카드 -->
    <div class="member-stats">
        <div class="stat-card">
            <h3>전체 회원</h3>
            <p class="stat-number"><?php echo number_format($stats['total']); ?></p>
        </div>
        <div class="stat-card">
            <h3>Google 가입</h3>
            <p class="stat-number"><?php echo number_format($stats['google']); ?></p>
        </div>
        <div class="stat-card">
            <h3>카카오 가입</h3>
            <p class="stat-number"><?php echo number_format($stats['kakao']); ?></p>
        </div>
        <div class="stat-card">
            <h3>이메일 가입</h3>
            <p class="stat-number"><?php echo number_format($stats['email']); ?></p>
        </div>
    </div>
    
    <!-- 검색 및 필터 -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="post_type" value="places">
            <input type="hidden" name="page" value="member-management">
            
            <div class="alignleft actions">
                <select name="provider" id="filter-provider">
                    <option value="">모든 가입 경로</option>
                    <option value="google" <?php selected($filter_provider, 'google'); ?>>Google</option>
                    <option value="kakao" <?php selected($filter_provider, 'kakao'); ?>>카카오</option>
                    <option value="email" <?php selected($filter_provider, 'email'); ?>>이메일</option>
                </select>
                <?php submit_button('필터', 'button', 'filter_action', false); ?>
            </div>
            
            <div class="alignright">
                <p class="search-box">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="이메일 또는 이름 검색">
                    <?php submit_button('검색', 'button', false, false); ?>
                </p>
            </div>
        </form>
    </div>
    
    <!-- 회원 목록 테이블 -->
    <table class="wp-list-table widefat fixed striped users">
        <thead>
            <tr>
                <th scope="col" class="column-username">사용자명</th>
                <th scope="col" class="column-email">이메일</th>
                <th scope="col" class="column-country">국가</th>
                <th scope="col" class="column-provider">가입 경로</th>
                <th scope="col" class="column-registered">가입일</th>
                <th scope="col" class="column-last-login">마지막 로그인</th>
                <th scope="col" class="column-actions">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($users) : ?>
                <?php foreach ($users as $user) : 
                    $provider = get_user_meta($user->ID, 'social_provider', true) ?: 'email';
                    $last_login = get_user_meta($user->ID, 'last_login', true);
                    $country = get_user_meta($user->ID, 'user_country', true);
                    $country_name = get_user_meta($user->ID, 'user_country_name', true);
                    $language = get_user_meta($user->ID, 'preferred_language', true);
                    $avatar = get_avatar($user->ID, 32);
                    
                    // 국가 코드를 국기 이모지로 변환
                    $flag = '';
                    if ($country && $country !== 'OTHER') {
                        $flag = mb_convert_encoding('&#' . (127397 + ord($country[0])) . ';&#' . (127397 + ord($country[1])) . ';', 'UTF-8', 'HTML-ENTITIES');
                    } elseif ($country === 'OTHER') {
                        $flag = '🌍';
                    }
                ?>
                <tr>
                    <td class="column-username">
                        <?php echo $avatar; ?>
                        <strong><?php echo esc_html($user->display_name); ?></strong>
                        <br>
                        <span class="user-id">ID: <?php echo $user->ID; ?></span>
                    </td>
                    <td class="column-email">
                        <?php echo esc_html($user->user_email); ?>
                    </td>
                    <td class="column-country">
                        <?php if ($country) : ?>
                            <span title="<?php echo esc_attr($language); ?>">
                                <?php 
                                if ($country === 'OTHER' && $country_name) {
                                    echo $flag . ' ' . esc_html($country_name);
                                } else {
                                    echo $flag . ' ' . esc_html($country);
                                }
                                ?>
                            </span>
                        <?php else : ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="column-provider">
                        <span class="provider-badge provider-<?php echo esc_attr($provider); ?>">
                            <?php echo ucfirst($provider); ?>
                        </span>
                    </td>
                    <td class="column-registered">
                        <?php echo date_i18n('Y-m-d', strtotime($user->user_registered)); ?>
                    </td>
                    <td class="column-last-login">
                        <?php echo $last_login ? date_i18n('Y-m-d H:i', $last_login) : '-'; ?>
                    </td>
                    <td class="column-actions">
                        <a href="<?php echo get_edit_user_link($user->ID); ?>" class="button button-small">편집</a>
                        <?php if ($user->ID !== get_current_user_id()) : ?>
                            <a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=places&page=member-management&action=delete&user_id=' . $user->ID), 'delete_user_' . $user->ID); ?>" 
                               class="button button-small delete-button" 
                               onclick="return confirm('정말 이 사용자를 삭제하시겠습니까?');">삭제</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6">회원이 없습니다.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- 페이지네이션 -->
    <?php if ($total_pages > 1) : ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo sprintf('%s명의 회원', number_format($total_users)); ?></span>
            <?php
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $paged
            ));
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* 통계 카드 */
.member-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
    font-weight: 600;
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    color: #2271b1;
    margin: 0;
}

/* 프로바이더 뱃지 */
.provider-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.provider-google {
    background: #4285f4;
    color: white;
}

.provider-kakao {
    background: #FEE500;
    color: #000;
}

.provider-email {
    background: #666;
    color: white;
}

/* 사용자 정보 */
.column-username img {
    float: left;
    margin-right: 10px;
}

.user-id {
    color: #666;
    font-size: 12px;
}

/* 삭제 버튼 */
.delete-button {
    color: #a00;
}

.delete-button:hover {
    color: #fff;
    background: #a00;
    border-color: #a00;
}

/* 테이블 열 너비 */
.column-username { width: 20%; }
.column-email { width: 20%; }
.column-country { width: 10%; }
.column-provider { width: 10%; }
.column-registered { width: 15%; }
.column-last-login { width: 15%; }
.column-actions { width: 10%; }
</style>

<script>
// 마지막 로그인 시간 업데이트 (AJAX)
jQuery(document).ready(function($) {
    // 실시간 업데이트 기능 추가 가능
});
</script>
