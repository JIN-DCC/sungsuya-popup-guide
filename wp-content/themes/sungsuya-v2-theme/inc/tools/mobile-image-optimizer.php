<?php
/**
 * 모바일 이미지 최적화 도구
 * 
 * @package SungsuyaV2
 * @since 1.0.0
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 모바일 이미지 최적화 클래스
 */
class Mobile_Image_Optimizer {
    
    /**
     * 생성자
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_optimize_mobile_images', array($this, 'ajax_optimize_images'));
        add_filter('wp_generate_attachment_metadata', array($this, 'generate_webp_on_upload'), 10, 2);
        add_filter('the_content', array($this, 'add_lazy_loading_to_content'));
        add_action('init', array($this, 'add_mobile_image_sizes'));
    }
    
    /**
     * 관리자 메뉴 추가
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            '모바일 이미지 최적화',
            '모바일 이미지 최적화',
            'manage_options',
            'mobile-image-optimizer',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * 모바일용 이미지 사이즈 추가
     */
    public function add_mobile_image_sizes() {
        // 모바일 전용 사이즈
        add_image_size('mobile-small', 480, 320, true);
        add_image_size('mobile-medium', 768, 512, true);
        add_image_size('mobile-large', 1024, 683, true);
        
        // WebP 대응 사이즈
        add_image_size('mobile-webp-small', 480, 320, true);
        add_image_size('mobile-webp-medium', 768, 512, true);
    }
    
    /**
     * 관리자 페이지 렌더링
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>모바일 이미지 최적화</h1>
            
            <div class="notice notice-info">
                <p>이 도구는 모바일 환경에 최적화된 이미지를 생성합니다. WebP 변환, 지연 로딩, 반응형 이미지를 자동으로 설정합니다.</p>
            </div>
            
            <div class="card">
                <h2>이미지 최적화 상태</h2>
                <div id="optimization-stats">
                    <?php $this->display_optimization_stats(); ?>
                </div>
            </div>
            
            <div class="card">
                <h2>일괄 최적화</h2>
                <p>기존 이미지를 모바일에 최적화된 형식으로 변환합니다.</p>
                <button class="button button-primary" id="start-optimization">최적화 시작</button>
                <div id="optimization-progress" style="display:none;">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%;"></div>
                    </div>
                    <p class="progress-text">처리 중... <span id="current-progress">0</span>%</p>
                </div>
            </div>
            
            <div class="card">
                <h2>최적화 설정</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('mobile_image_settings'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">WebP 자동 변환</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="mobile_auto_webp" value="1" 
                                           <?php checked(get_option('mobile_auto_webp', 1)); ?>>
                                    업로드 시 자동으로 WebP 형식 생성
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">지연 로딩</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="mobile_lazy_loading" value="1" 
                                           <?php checked(get_option('mobile_lazy_loading', 1)); ?>>
                                    이미지 지연 로딩 활성화
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">압축 품질</th>
                            <td>
                                <input type="number" name="mobile_image_quality" 
                                       value="<?php echo get_option('mobile_image_quality', 85); ?>" 
                                       min="50" max="100" step="5">
                                <p class="description">WebP 변환 시 품질 (50-100, 권장: 85)</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('설정 저장'); ?>
                </form>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#start-optimization').on('click', function() {
                $(this).prop('disabled', true);
                $('#optimization-progress').show();
                
                optimizeImages(0);
            });
            
            function optimizeImages(offset) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'optimize_mobile_images',
                        offset: offset,
                        nonce: '<?php echo wp_create_nonce('mobile_image_optimization'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var progress = response.data.progress;
                            $('.progress-fill').css('width', progress + '%');
                            $('#current-progress').text(progress);
                            
                            if (progress < 100) {
                                optimizeImages(response.data.next_offset);
                            } else {
                                $('#start-optimization').prop('disabled', false);
                                alert('이미지 최적화가 완료되었습니다!');
                                location.reload();
                            }
                        }
                    }
                });
            }
        });
        </script>
        
        <style>
        .progress-bar {
            background: #f0f0f0;
            border-radius: 5px;
            height: 30px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-fill {
            background: #0073aa;
            height: 100%;
            transition: width 0.3s ease;
        }
        .card {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 5px;
            margin: 20px 0;
            padding: 20px;
        }
        </style>
        <?php
    }
    
    /**
     * 최적화 통계 표시
     */
    private function display_optimization_stats() {
        global $wpdb;
        
        // 전체 이미지 수
        $total_images = $wpdb->get_var(
            "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'"
        );
        
        // WebP 변환된 이미지 수
        $webp_count = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM $wpdb->postmeta WHERE meta_key = '_webp_generated'"
        );
        
        // 모바일 사이즈 생성된 이미지 수
        $mobile_count = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM $wpdb->postmeta WHERE meta_key = '_mobile_sizes_generated'"
        );
        
        ?>
        <table class="wp-list-table widefat">
            <tr>
                <td>전체 이미지</td>
                <td><strong><?php echo number_format($total_images); ?></strong>개</td>
            </tr>
            <tr>
                <td>WebP 변환됨</td>
                <td><strong><?php echo number_format($webp_count); ?></strong>개 
                    (<?php echo $total_images > 0 ? round($webp_count / $total_images * 100) : 0; ?>%)</td>
            </tr>
            <tr>
                <td>모바일 사이즈 생성됨</td>
                <td><strong><?php echo number_format($mobile_count); ?></strong>개 
                    (<?php echo $total_images > 0 ? round($mobile_count / $total_images * 100) : 0; ?>%)</td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * AJAX 이미지 최적화 처리
     */
    public function ajax_optimize_images() {
        check_ajax_referer('mobile_image_optimization', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('권한이 없습니다.');
        }
        
        $offset = intval($_POST['offset']);
        $batch_size = 10;
        
        $images = get_posts(array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'post_status' => 'any'
        ));
        
        foreach ($images as $image) {
            $this->optimize_single_image($image->ID);
        }
        
        $total_images = wp_count_posts('attachment')->inherit;
        $processed = $offset + count($images);
        $progress = min(100, round($processed / $total_images * 100));
        
        wp_send_json_success(array(
            'progress' => $progress,
            'next_offset' => $processed,
            'total' => $total_images,
            'processed' => $processed
        ));
    }
    
    /**
     * 단일 이미지 최적화
     */
    private function optimize_single_image($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        
        if (!file_exists($file_path)) {
            return false;
        }
        
        // WebP 변환
        if (get_option('mobile_auto_webp', 1)) {
            $this->convert_to_webp($attachment_id, $file_path);
        }
        
        // 모바일 사이즈 생성
        $this->generate_mobile_sizes($attachment_id);
        
        return true;
    }
    
    /**
     * WebP 변환
     */
    private function convert_to_webp($attachment_id, $file_path) {
        // 이미 변환된 경우 스킵
        if (get_post_meta($attachment_id, '_webp_generated', true)) {
            return;
        }
        
        $info = pathinfo($file_path);
        $webp_path = $info['dirname'] . '/' . $info['filename'] . '.webp';
        
        // GD 라이브러리 사용
        if (function_exists('imagewebp')) {
            $image_type = exif_imagetype($file_path);
            $image = null;
            
            switch ($image_type) {
                case IMAGETYPE_JPEG:
                    $image = imagecreatefromjpeg($file_path);
                    break;
                case IMAGETYPE_PNG:
                    $image = imagecreatefrompng($file_path);
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                    break;
            }
            
            if ($image) {
                $quality = get_option('mobile_image_quality', 85);
                imagewebp($image, $webp_path, $quality);
                imagedestroy($image);
                
                update_post_meta($attachment_id, '_webp_generated', true);
                update_post_meta($attachment_id, '_webp_path', $webp_path);
            }
        }
    }
    
    /**
     * 모바일 사이즈 생성
     */
    private function generate_mobile_sizes($attachment_id) {
        // 이미 생성된 경우 스킵
        if (get_post_meta($attachment_id, '_mobile_sizes_generated', true)) {
            return;
        }
        
        // WordPress 내장 함수로 추가 사이즈 생성
        $metadata = wp_get_attachment_metadata($attachment_id);
        $file_path = get_attached_file($attachment_id);
        
        if ($metadata && file_exists($file_path)) {
            // 추가 사이즈 생성
            $new_sizes = array('mobile-small', 'mobile-medium', 'mobile-large');
            
            foreach ($new_sizes as $size) {
                if (!isset($metadata['sizes'][$size])) {
                    $resized = image_make_intermediate_size($file_path, 
                        get_option($size . '_size_w'), 
                        get_option($size . '_size_h'), 
                        get_option($size . '_crop')
                    );
                    
                    if ($resized) {
                        $metadata['sizes'][$size] = $resized;
                    }
                }
            }
            
            wp_update_attachment_metadata($attachment_id, $metadata);
            update_post_meta($attachment_id, '_mobile_sizes_generated', true);
        }
    }
    
    /**
     * 업로드 시 WebP 자동 생성
     */
    public function generate_webp_on_upload($metadata, $attachment_id) {
        if (get_option('mobile_auto_webp', 1)) {
            $file_path = get_attached_file($attachment_id);
            $this->convert_to_webp($attachment_id, $file_path);
        }
        
        return $metadata;
    }
    
    /**
     * 콘텐츠에 지연 로딩 추가
     */
    public function add_lazy_loading_to_content($content) {
        if (!get_option('mobile_lazy_loading', 1)) {
            return $content;
        }
        
        // img 태그에 loading="lazy" 추가
        $content = preg_replace('/<img(.*?)>/i', '<img$1 loading="lazy">', $content);
        
        // 이미 loading 속성이 있는 경우 중복 방지
        $content = preg_replace('/loading="[^"]*"\s*loading="lazy"/i', 'loading="lazy"', $content);
        
        return $content;
    }
}

// 클래스 초기화
new Mobile_Image_Optimizer();

/**
 * 헬퍼 함수: WebP 이미지 URL 가져오기
 */
function sungsuya_get_webp_url($attachment_id) {
    $webp_path = get_post_meta($attachment_id, '_webp_path', true);
    
    if ($webp_path && file_exists($webp_path)) {
        $upload_dir = wp_upload_dir();
        $webp_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $webp_path);
        return $webp_url;
    }
    
    return false;
}

/**
 * 헬퍼 함수: 반응형 이미지 소스 생성
 */
function sungsuya_get_responsive_image($attachment_id, $size = 'full', $attr = array()) {
    $image_src = wp_get_attachment_image_src($attachment_id, $size);
    
    if (!$image_src) {
        return '';
    }
    
    $srcset = wp_get_attachment_image_srcset($attachment_id, $size);
    $sizes = wp_get_attachment_image_sizes($attachment_id, $size);
    
    $default_attr = array(
        'src' => $image_src[0],
        'srcset' => $srcset,
        'sizes' => $sizes,
        'loading' => 'lazy',
        'decoding' => 'async'
    );
    
    $attr = wp_parse_args($attr, $default_attr);
    
    // WebP 대체 이미지 추가
    $webp_url = sungsuya_get_webp_url($attachment_id);
    
    if ($webp_url) {
        $html = '<picture>';
        $html .= '<source type="image/webp" srcset="' . esc_url($webp_url) . '">';
        $html .= wp_get_attachment_image($attachment_id, $size, false, $attr);
        $html .= '</picture>';
        
        return $html;
    }
    
    return wp_get_attachment_image($attachment_id, $size, false, $attr);
}
