<?php
/**
 * 성수야! V2 - 팝업스토어 Taxonomy 관리
 * 
 * 관리자 메뉴에서 팝업스토어 분류를 쉽게 관리
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

class PopupStoreTaxonomyManager {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_fix_popup_taxonomy', array($this, 'handle_fix_taxonomy'));
    }
    
    /**
     * 관리자 메뉴 추가
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=places',
            '팝업스토어 분류 관리',
            '팝업스토어 분류 관리',
            'manage_options',
            'popup-store-taxonomy',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * 관리자 페이지 렌더링
     */
    public function render_admin_page() {
        // 메시지 처리
        $message = '';
        if (isset($_GET['message'])) {
            if ($_GET['message'] === 'success') {
                $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
                $message = sprintf(
                    '<div class="notice notice-success is-dismissible"><p>✅ %d개의 팝업스토어가 올바르게 분류되었습니다.</p></div>',
                    $count
                );
            }
        }
        
        // 현재 상태 확인
        $stats = $this->get_popup_stats();
        
        ?>
        <div class="wrap">
            <h1>팝업스토어 분류 관리</h1>
            
            <?php echo $message; ?>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>현재 상태</h2>
                <table class="widefat" style="margin-bottom: 20px;">
                    <tbody>
                        <tr>
                            <th>전체 Places</th>
                            <td><?php echo $stats['total']; ?>개</td>
                        </tr>
                        <tr>
                            <th>올바른 팝업스토어 (popup-store)</th>
                            <td><?php echo $stats['correct_popup']; ?>개</td>
                        </tr>
                        <tr>
                            <th>잘못된 팝업스토어 (popup_store)</th>
                            <td><strong style="color: #d63638;"><?php echo $stats['wrong_popup']; ?>개</strong></td>
                        </tr>
                        <tr>
                            <th>일반 장소</th>
                            <td><?php echo $stats['other']; ?>개</td>
                        </tr>
                    </tbody>
                </table>
                
                <?php if ($stats['wrong_popup'] > 0) : ?>
                    <div class="notice notice-warning inline">
                        <p>
                            <strong><?php echo $stats['wrong_popup']; ?>개</strong>의 팝업스토어가 잘못된 분류(popup_store)를 가지고 있습니다.
                            아래 버튼을 클릭하면 올바른 분류(popup-store)로 자동 변경됩니다.
                        </p>
                    </div>
                    
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="fix_popup_taxonomy">
                        <?php wp_nonce_field('fix_popup_taxonomy', 'popup_nonce'); ?>
                        
                        <p>
                            <button type="submit" class="button button-primary button-large">
                                팝업스토어 분류 자동 수정
                            </button>
                        </p>
                    </form>
                <?php else : ?>
                    <div class="notice notice-success inline">
                        <p>✅ 모든 팝업스토어가 올바르게 분류되어 있습니다.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>팝업스토어 목록</h2>
                <?php $this->render_popup_list(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * 팝업스토어 통계 가져오기
     */
    private function get_popup_stats() {
        $stats = array(
            'total' => 0,
            'correct_popup' => 0,
            'wrong_popup' => 0,
            'other' => 0
        );
        
        // 전체 Places
        $all_places = wp_count_posts('places');
        $stats['total'] = $all_places->publish + $all_places->draft;
        
        // 올바른 팝업스토어 (popup-store)
        $correct_term = get_term_by('slug', 'popup-store', 'place_type');
        if ($correct_term) {
            $stats['correct_popup'] = $correct_term->count;
        }
        
        // 잘못된 팝업스토어 (popup_store)
        $wrong_term = get_term_by('slug', 'popup_store', 'place_type');
        if ($wrong_term) {
            $stats['wrong_popup'] = $wrong_term->count;
        }
        
        // 나머지
        $stats['other'] = $stats['total'] - $stats['correct_popup'] - $stats['wrong_popup'];
        
        return $stats;
    }
    
    /**
     * 팝업스토어 목록 렌더링
     */
    private function render_popup_list() {
        // 모든 팝업스토어 가져오기 (popup_store와 popup-store 모두)
        $popup_query = new WP_Query(array(
            'post_type' => 'places',
            'posts_per_page' => 20,
            'tax_query' => array(
                array(
                    'taxonomy' => 'place_type',
                    'field' => 'slug',
                    'terms' => array('popup-store', 'popup_store')
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if ($popup_query->have_posts()) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>제목</th>
                        <th>현재 분류</th>
                        <th>종료일</th>
                        <th>상태</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($popup_query->have_posts()) : $popup_query->the_post();
                        $post_id = get_the_ID();
                        $terms = wp_get_post_terms($post_id, 'place_type');
                        $current_slug = !empty($terms) ? $terms[0]->slug : '';
                        $end_date = get_post_meta($post_id, 'popup_end_date', true);
                        
                        // 상태 확인
                        $status = '진행중';
                        $status_color = '#00a32a';
                        if ($end_date && $end_date < date('Y-m-d')) {
                            $status = '종료됨';
                            $status_color = '#d63638';
                        }
                    ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="<?php echo get_edit_post_link($post_id); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </strong>
                            </td>
                            <td>
                                <?php if ($current_slug === 'popup_store') : ?>
                                    <span style="color: #d63638;">popup_store (수정 필요)</span>
                                <?php else : ?>
                                    <span style="color: #00a32a;">popup-store</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $end_date ? esc_html($end_date) : '-'; ?></td>
                            <td style="color: <?php echo $status_color; ?>"><?php echo $status; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>등록된 팝업스토어가 없습니다.</p>
        <?php endif;
        
        wp_reset_postdata();
    }
    
    /**
     * Taxonomy 수정 처리
     */
    public function handle_fix_taxonomy() {
        // 권한 확인
        if (!current_user_can('manage_options')) {
            wp_die('권한이 없습니다.');
        }
        
        // Nonce 확인
        if (!isset($_POST['popup_nonce']) || !wp_verify_nonce($_POST['popup_nonce'], 'fix_popup_taxonomy')) {
            wp_die('보안 검증 실패');
        }
        
        // popup_store를 popup-store로 변경
        $wrong_term = get_term_by('slug', 'popup_store', 'place_type');
        $correct_term = get_term_by('slug', 'popup-store', 'place_type');
        
        // popup-store 텀이 없으면 생성
        if (!$correct_term) {
            $result = wp_insert_term('팝업스토어', 'place_type', array(
                'slug' => 'popup-store',
                'description' => '기간 한정으로 운영되는 팝업스토어'
            ));
            
            if (!is_wp_error($result)) {
                $correct_term = get_term($result['term_id'], 'place_type');
            }
        }
        
        $fixed_count = 0;
        
        if ($wrong_term && $correct_term) {
            // popup_store를 가진 모든 포스트 가져오기
            $posts = get_objects_in_term($wrong_term->term_id, 'place_type');
            
            foreach ($posts as $post_id) {
                // 기존 텀 제거하고 새 텀 추가
                wp_remove_object_terms($post_id, 'popup_store', 'place_type');
                wp_set_object_terms($post_id, 'popup-store', 'place_type', true);
                $fixed_count++;
            }
        }
        
        // 리다이렉트
        wp_redirect(admin_url('edit.php?post_type=places&page=popup-store-taxonomy&message=success&count=' . $fixed_count));
        exit;
    }
}

// 인스턴스 생성
new PopupStoreTaxonomyManager();
