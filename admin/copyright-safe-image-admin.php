<?php
/**
 * 저작권 안전 이미지 크롤링 관리자 페이지
 * 
 * @package SungsuyaV2
 * @version 1.0.0
 * @since 2025-07-02
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 저작권 안전 이미지 크롤링 관리자 클래스
 */
class Copyright_Safe_Image_Admin {
    
    /**
     * 생성자
     */
    public function __construct() {
        // 메뉴는 reorganized-admin-menu.php에서 통합 관리
        // add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * 관리자 메뉴 추가
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=places',
            '저작권 안전 이미지 크롤링',
            '🛡️ 안전 이미지 크롤링',
            'edit_posts',
            'copyright-safe-image-crawling',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * 관리자 스크립트 및 스타일 등록
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'places_page_copyright-safe-image-crawling') {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'copyright-safe-image-admin',
            get_template_directory_uri() . '/assets/css/copyright-safe-image-admin.css',
            array(),
            '1.0.0'
        );
        
        // JavaScript
        wp_enqueue_script(
            'copyright-safe-image-admin',
            get_template_directory_uri() . '/assets/js/copyright-safe-image-admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // AJAX 설정
        wp_localize_script('copyright-safe-image-admin', 'copyrightSafeImage', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('copyright_safe_crawl'),
            'testNonce' => wp_create_nonce('test_copyright_safety'),
            'analyzeNonce' => wp_create_nonce('analyze_image_type'),
            'messages' => array(
                'crawling' => '안전한 이미지를 검색 중입니다...',
                'testing' => '안전도를 테스트 중입니다...',
                'analyzing' => '이미지를 분석 중입니다...',
                'error' => '오류가 발생했습니다. 다시 시도해주세요.',
                'noImages' => '안전한 이미지를 찾을 수 없습니다.',
                'success' => '작업이 완료되었습니다.'
            )
        ));
    }
    
    /**
     * 관리자 페이지 렌더링
     */
    public function render_admin_page() {
        // 권한 확인
        if (!current_user_can('edit_posts')) {
            wp_die(__('권한이 없습니다.'));
        }
        
        // 최근 크롤링된 장소 가져오기
        $recent_places = $this->get_recent_crawled_places();
        
        // 통계 데이터
        $stats = $this->get_crawling_stats();
        ?>
        
        <div class="wrap copyright-safe-image-admin">
            <h1>
                <span class="dashicons dashicons-shield-alt"></span>
                저작권 안전 이미지 크롤링
            </h1>
            
            <div class="notice notice-info">
                <p>
                    <strong>저작권 안전 시스템</strong>: 
                    공식 이미지와 팩트 정보(건물 외관, 간판, 메뉴판 등)만 수집하여 저작권 이슈를 방지합니다.
                </p>
            </div>
            
            <!-- 통계 대시보드 -->
            <div class="stats-dashboard">
                <h2>크롤링 통계</h2>
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo number_format($stats['total_crawled']); ?></div>
                        <div class="stat-label">전체 크롤링</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo number_format($stats['safe_images']); ?></div>
                        <div class="stat-label">안전 이미지</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $stats['avg_safety']; ?>%</div>
                        <div class="stat-label">평균 안전도</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo number_format($stats['official_ratio']); ?>%</div>
                        <div class="stat-label">공식 이미지 비율</div>
                    </div>
                </div>
            </div>
            
            <!-- 개별 크롤링 섹션 -->
            <div class="crawling-section">
                <h2>개별 장소 이미지 크롤링</h2>
                
                <div class="crawling-options">
                    <label>
                        <input type="checkbox" id="official-only" value="1">
                        공식 이미지만 수집 (네이버 플레이스, 구글 비즈니스, 공식 웹사이트)
                    </label>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>장소명</th>
                            <th>주소</th>
                            <th>현재 이미지</th>
                            <th>마지막 크롤링</th>
                            <th style="width: 200px;">작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $places = get_posts(array(
                            'post_type' => 'places',
                            'posts_per_page' => 20,
                            'orderby' => 'modified',
                            'order' => 'DESC'
                        ));
                        
                        foreach ($places as $place) :
                            $images = get_post_meta($place->ID, '_copyright_safe_images', true);
                            $last_crawled = get_post_meta($place->ID, '_copyright_safe_images_cache', true);
                            $image_count = is_array($images) ? count($images) : 0;
                        ?>
                        <tr data-post-id="<?php echo $place->ID; ?>">
                            <td><?php echo $place->ID; ?></td>
                            <td>
                                <strong>
                                    <a href="<?php echo get_edit_post_link($place->ID); ?>">
                                        <?php echo esc_html($place->post_title); ?>
                                    </a>
                                </strong>
                            </td>
                            <td><?php echo esc_html(get_post_meta($place->ID, 'address', true)); ?></td>
                            <td>
                                <span class="image-count">
                                    <?php echo $image_count; ?>개
                                    <?php if ($image_count > 0 && is_array($images)) : ?>
                                        <span class="safety-indicator" title="평균 안전도">
                                            (<?php echo $this->calculate_avg_safety($images); ?>%)
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if (!empty($last_crawled['timestamp'])) {
                                    echo human_time_diff($last_crawled['timestamp'], current_time('timestamp')) . ' 전';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <button class="button button-primary crawl-single" 
                                        data-post-id="<?php echo $place->ID; ?>">
                                    <span class="dashicons dashicons-download"></span>
                                    크롤링
                                </button>
                                <button class="button test-safety" 
                                        data-post-id="<?php echo $place->ID; ?>">
                                    <span class="dashicons dashicons-admin-tools"></span>
                                    테스트
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 일괄 크롤링 섹션 -->
            <div class="bulk-crawling-section">
                <h2>일괄 안전 이미지 크롤링</h2>
                
                <div class="bulk-options">
                    <label>
                        최소 안전도:
                        <select id="min-safety">
                            <option value="95">95% 이상 (매우 안전)</option>
                            <option value="85" selected>85% 이상 (안전)</option>
                            <option value="70">70% 이상 (보통)</option>
                        </select>
                    </label>
                    
                    <label>
                        이미지 수:
                        <select id="max-images">
                            <option value="3">최대 3개</option>
                            <option value="5" selected>최대 5개</option>
                            <option value="10">최대 10개</option>
                        </select>
                    </label>
                </div>
                
                <div class="bulk-actions">
                    <button class="button button-primary" id="bulk-crawl-selected">
                        <span class="dashicons dashicons-images-alt2"></span>
                        선택한 장소 일괄 크롤링
                    </button>
                    
                    <button class="button" id="bulk-crawl-no-images">
                        <span class="dashicons dashicons-format-image"></span>
                        이미지 없는 장소만 크롤링
                    </button>
                </div>
                
                <div class="bulk-progress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="progress-text">0 / 0</div>
                </div>
            </div>
            
            <!-- 이미지 분석 도구 -->
            <div class="image-analyzer-section">
                <h2>이미지 안전도 분석 도구</h2>
                
                <div class="analyzer-input">
                    <label>
                        이미지 URL:
                        <input type="url" id="analyze-url" class="regular-text" 
                               placeholder="https://example.com/image.jpg">
                    </label>
                    <button class="button" id="analyze-image">
                        <span class="dashicons dashicons-search"></span>
                        분석하기
                    </button>
                </div>
                
                <div class="analyzer-result" style="display: none;">
                    <h3>분석 결과</h3>
                    <div class="result-content"></div>
                </div>
            </div>
            
            <!-- 최근 크롤링 결과 -->
            <div class="recent-results-section">
                <h2>최근 크롤링 결과</h2>
                
                <?php if (!empty($recent_places)) : ?>
                <div class="recent-grid">
                    <?php foreach ($recent_places as $place) : 
                        $images = get_post_meta($place->ID, '_copyright_safe_images', true);
                        if (!is_array($images) || empty($images)) continue;
                    ?>
                    <div class="recent-item">
                        <h4><?php echo esc_html($place->post_title); ?></h4>
                        <div class="recent-images">
                            <?php foreach (array_slice($images, 0, 3) as $img) : ?>
                            <div class="image-item">
                                <img src="<?php echo esc_url($img['url']); ?>" 
                                     alt="<?php echo esc_attr($img['source'] ?? ''); ?>">
                                <div class="image-info">
                                    <span class="safety-badge <?php echo $this->get_safety_class($img['safety_score']); ?>">
                                        <?php echo $img['safety_level']; ?>
                                    </span>
                                    <span class="source-type">
                                        <?php echo esc_html($img['source']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <p>아직 크롤링된 이미지가 없습니다.</p>
                <?php endif; ?>
            </div>
            
            <!-- 도움말 섹션 -->
            <div class="help-section">
                <h2>저작권 안전 가이드</h2>
                
                <div class="help-grid">
                    <div class="help-box">
                        <h3>✅ 안전한 이미지</h3>
                        <ul>
                            <li>네이버 플레이스 공식 이미지</li>
                            <li>구글 비즈니스 프로필 이미지</li>
                            <li>공식 웹사이트 이미지</li>
                            <li>건물 외관 사진</li>
                            <li>간판/로고 사진</li>
                            <li>메뉴판/가격표</li>
                            <li>영업시간 안내판</li>
                        </ul>
                    </div>
                    
                    <div class="help-box">
                        <h3>❌ 위험한 이미지</h3>
                        <ul>
                            <li>인물이 포함된 사진</li>
                            <li>개인 블로그 리뷰 사진</li>
                            <li>창작성 있는 음식 사진</li>
                            <li>인테리어 디자인 사진</li>
                            <li>저작권 표시가 있는 이미지</li>
                            <li>워터마크가 있는 이미지</li>
                        </ul>
                    </div>
                    
                    <div class="help-box">
                        <h3>📊 안전도 레벨</h3>
                        <ul>
                            <li><span class="safety-badge very-safe">매우 안전</span> 95% 이상</li>
                            <li><span class="safety-badge safe">안전</span> 85% 이상</li>
                            <li><span class="safety-badge moderate">보통</span> 70% 이상</li>
                            <li><span class="safety-badge caution">주의</span> 50% 이상</li>
                            <li><span class="safety-badge danger">위험</span> 50% 미만</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 결과 모달 -->
        <div id="crawling-modal" class="crawling-modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>크롤링 결과</h2>
                <div class="modal-body"></div>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * 평균 안전도 계산
     */
    private function calculate_avg_safety($images) {
        if (!is_array($images) || empty($images)) {
            return 0;
        }
        
        $total = 0;
        $count = 0;
        
        foreach ($images as $img) {
            if (isset($img['safety_score'])) {
                $total += $img['safety_score'];
                $count++;
            }
        }
        
        return $count > 0 ? round($total / $count) : 0;
    }
    
    /**
     * 안전도에 따른 CSS 클래스 반환
     */
    private function get_safety_class($score) {
        if ($score >= 95) return 'very-safe';
        if ($score >= 85) return 'safe';
        if ($score >= 70) return 'moderate';
        if ($score >= 50) return 'caution';
        return 'danger';
    }
    
    /**
     * 최근 크롤링된 장소 가져오기
     */
    private function get_recent_crawled_places() {
        return get_posts(array(
            'post_type' => 'places',
            'posts_per_page' => 6,
            'meta_query' => array(
                array(
                    'key' => '_copyright_safe_images',
                    'compare' => 'EXISTS'
                )
            ),
            'orderby' => 'modified',
            'order' => 'DESC'
        ));
    }
    
    /**
     * 크롤링 통계 가져오기
     */
    private function get_crawling_stats() {
        global $wpdb;
        
        // 전체 크롤링된 장소 수
        $total_crawled = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_copyright_safe_images'"
        );
        
        // 안전 이미지 총 개수
        $safe_images = 0;
        $total_safety = 0;
        $official_count = 0;
        
        $crawled_data = $wpdb->get_results(
            "SELECT meta_value 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_copyright_safe_images'"
        );
        
        foreach ($crawled_data as $data) {
            $images = maybe_unserialize($data->meta_value);
            if (is_array($images)) {
                foreach ($images as $img) {
                    $safe_images++;
                    if (isset($img['safety_score'])) {
                        $total_safety += $img['safety_score'];
                    }
                    if (isset($img['source']) && strpos($img['source'], '공식') !== false) {
                        $official_count++;
                    }
                }
            }
        }
        
        $avg_safety = $safe_images > 0 ? round($total_safety / $safe_images) : 0;
        $official_ratio = $safe_images > 0 ? round(($official_count / $safe_images) * 100) : 0;
        
        return array(
            'total_crawled' => $total_crawled,
            'safe_images' => $safe_images,
            'avg_safety' => $avg_safety,
            'official_ratio' => $official_ratio
        );
    }
}

// 관리자 클래스 초기화
new Copyright_Safe_Image_Admin();