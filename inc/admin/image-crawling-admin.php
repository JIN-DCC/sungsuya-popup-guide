<?php
/**
 * 이미지 크롤링 관리자 페이지
 * 
 * @package SungsuyaV2
 * @since 2.3.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 이미지 크롤링 메타박스 추가
 */
function sungsuya_add_image_crawling_metabox() {
    add_meta_box(
        'sungsuya_image_crawling',
        '🖼️ 이미지 크롤링',
        'sungsuya_image_crawling_metabox_content',
        'places',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'sungsuya_add_image_crawling_metabox');

/**
 * 메타박스 내용
 */
function sungsuya_image_crawling_metabox_content($post) {
    wp_nonce_field('image_crawl_nonce', 'image_crawl_nonce');
    
    // 현재 첨부된 이미지 수
    $attachments = get_posts(array(
        'post_type' => 'attachment',
        'posts_per_page' => -1,
        'post_parent' => $post->ID
    ));
    
    $featured_id = get_post_thumbnail_id($post->ID);
    ?>
    <style>
        .image-crawl-stats {
            background: #f0f0f1;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 3px;
        }
        .image-crawl-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .image-crawl-results {
            margin-top: 15px;
            max-height: 400px;
            overflow-y: auto;
        }
        .crawled-image {
            position: relative;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            overflow: hidden;
        }
        .crawled-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        .crawled-image-actions {
            padding: 8px;
            background: #f9f9f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .crawled-image.selected {
            border-color: #2271b1;
            box-shadow: 0 0 0 2px #2271b1;
        }
        .source-badge {
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 3px;
            background: #666;
            color: white;
        }
        .source-badge.kakao { background: #fee500; color: #000; }
        .source-badge.naver { background: #1ec800; }
        
        .crawl-progress {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .crawl-progress.active {
            display: block;
        }
        
        #image-crawl-status {
            margin-top: 10px;
            padding: 10px;
            border-radius: 3px;
            display: none;
        }
        #image-crawl-status.success {
            background: #d4f4dd;
            color: #1a7c2a;
            display: block;
        }
        #image-crawl-status.error {
            background: #ffd6d6;
            color: #a00;
            display: block;
        }
    </style>
    
    <div class="image-crawl-container">
        <div class="image-crawl-stats">
            <strong>현재 이미지:</strong> <?php echo count($attachments); ?>개<br>
            <strong>대표 이미지:</strong> <?php echo $featured_id ? '설정됨' : '미설정'; ?>
        </div>
        
        <div class="image-crawl-buttons">
            <button type="button" class="button button-primary" onclick="crawlImages('all')">
                전체 소스에서 크롤링
            </button>
            <button type="button" class="button" onclick="crawlImages('kakao')">
                카카오 이미지 검색
            </button>
            <button type="button" class="button" onclick="crawlImages('naver')">
                네이버 이미지 검색
            </button>
        </div>
        
        <div id="image-crawl-status"></div>
        
        <div class="crawl-progress">
            <span class="spinner is-active"></span>
            <p>이미지를 검색중입니다...</p>
        </div>
        
        <div class="image-crawl-results" id="crawl-results"></div>
    </div>
    
    <script>
    let crawledImages = [];
    
    function crawlImages(source) {
        const postId = <?php echo $post->ID; ?>;
        const progress = document.querySelector('.crawl-progress');
        const results = document.getElementById('crawl-results');
        const status = document.getElementById('image-crawl-status');
        
        // UI 초기화
        progress.classList.add('active');
        results.innerHTML = '';
        status.style.display = 'none';
        
        // AJAX 요청
        jQuery.post(ajaxurl, {
            action: 'crawl_images',
            nonce: '<?php echo wp_create_nonce('image_crawl_nonce'); ?>',
            post_id: postId,
            source: source
        }, function(response) {
            progress.classList.remove('active');
            
            if (response.success) {
                crawledImages = response.data.images;
                
                if (crawledImages.length > 0) {
                    displayCrawledImages(crawledImages);
                    showStatus('success', `${crawledImages.length}개의 이미지를 찾았습니다.`);
                } else {
                    showStatus('error', '이미지를 찾을 수 없습니다.');
                }
            } else {
                showStatus('error', response.data || '크롤링 중 오류가 발생했습니다.');
            }
        }).fail(function() {
            progress.classList.remove('active');
            showStatus('error', '서버 오류가 발생했습니다.');
        });
    }
    
    function displayCrawledImages(images) {
        const results = document.getElementById('crawl-results');
        results.innerHTML = '<h4>검색 결과 (클릭하여 선택)</h4>';
        
        images.forEach((image, index) => {
            const div = document.createElement('div');
            div.className = 'crawled-image';
            div.innerHTML = `
                <img src="${image.thumbnail || image.url}" alt="" loading="lazy">
                <div class="crawled-image-actions">
                    <span class="source-badge ${image.source}">${image.source}</span>
                    <button type="button" class="button button-small" onclick="attachImage(${index})">
                        첨부하기
                    </button>
                </div>
            `;
            
            // 이미지 클릭 시 선택
            div.querySelector('img').addEventListener('click', function() {
                document.querySelectorAll('.crawled-image').forEach(el => {
                    el.classList.remove('selected');
                });
                div.classList.add('selected');
            });
            
            results.appendChild(div);
        });
        
        // 일괄 처리 버튼 추가
        const batchDiv = document.createElement('div');
        batchDiv.style.marginTop = '15px';
        batchDiv.innerHTML = `
            <button type="button" class="button button-primary" onclick="attachSelectedImages()">
                선택한 이미지 모두 첨부
            </button>
            <label style="margin-left: 10px;">
                <input type="checkbox" id="set-first-featured"> 
                첫 번째를 대표 이미지로
            </label>
        `;
        results.appendChild(batchDiv);
    }
    
    function attachImage(index) {
        const image = crawledImages[index];
        const postId = <?php echo $post->ID; ?>;
        const setFeatured = !<?php echo $featured_id ? 'true' : 'false'; ?>;
        
        jQuery.post(ajaxurl, {
            action: 'attach_image_to_post',
            nonce: '<?php echo wp_create_nonce('image_crawl_nonce'); ?>',
            post_id: postId,
            image_url: image.url,
            set_featured: setFeatured
        }, function(response) {
            if (response.success) {
                showStatus('success', '이미지가 첨부되었습니다.');
                
                // 선택된 이미지 제거
                const imageEl = document.querySelectorAll('.crawled-image')[index];
                if (imageEl) {
                    imageEl.style.opacity = '0.5';
                    imageEl.querySelector('button').disabled = true;
                }
                
                // 페이지 새로고침 권장
                if (response.data.featured_set) {
                    showStatus('success', '대표 이미지가 설정되었습니다. 페이지를 새로고침하세요.');
                }
            } else {
                showStatus('error', response.data || '이미지 첨부 실패');
            }
        });
    }
    
    function attachSelectedImages() {
        const selected = document.querySelectorAll('.crawled-image.selected');
        if (selected.length === 0) {
            showStatus('error', '이미지를 선택해주세요.');
            return;
        }
        
        // 선택된 이미지들 처리
        selected.forEach((el, idx) => {
            const index = Array.from(el.parentNode.children).indexOf(el) - 1; // h4 제외
            if (index >= 0) {
                setTimeout(() => attachImage(index), idx * 1000); // 1초 간격으로 처리
            }
        });
    }
    
    function showStatus(type, message) {
        const status = document.getElementById('image-crawl-status');
        status.className = type;
        status.textContent = message;
        status.style.display = 'block';
        
        // 3초 후 자동 숨김
        setTimeout(() => {
            status.style.display = 'none';
        }, 3000);
    }
    </script>
    <?php
}

/**
 * 이미지 크롤링 대량 처리 페이지
 */
function sungsuya_bulk_image_crawling_page() {
    ?>
    <div class="wrap">
        <h1>🖼️ 대량 이미지 크롤링</h1>
        
        <div class="notice notice-info">
            <p>이미지가 없는 장소들을 선택하여 일괄적으로 이미지를 크롤링할 수 있습니다.</p>
        </div>
        
        <?php
        // 이미지가 없는 Places 조회
        $places_without_images = get_posts(array(
            'post_type' => 'places',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        if (empty($places_without_images)) {
            echo '<p>모든 장소에 이미지가 설정되어 있습니다.</p>';
            return;
        }
        ?>
        
        <form id="bulk-image-crawl-form">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="check-column">
                            <input type="checkbox" id="select-all">
                        </td>
                        <th>장소명</th>
                        <th>주소</th>
                        <th>유형</th>
                        <th>현재 이미지</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($places_without_images as $place): 
                        $attachments = get_posts(array(
                            'post_type' => 'attachment',
                            'post_parent' => $place->ID,
                            'posts_per_page' => -1
                        ));
                    ?>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" name="places[]" value="<?php echo $place->ID; ?>">
                        </th>
                        <td>
                            <strong>
                                <a href="<?php echo get_edit_post_link($place->ID); ?>">
                                    <?php echo esc_html($place->post_title); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo esc_html(get_post_meta($place->ID, 'place_address', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($place->ID, 'place_type', true)); ?></td>
                        <td><?php echo count($attachments); ?>개</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 20px;">
                <label>
                    크롤링 소스:
                    <select name="crawl_source">
                        <option value="all">전체 (카카오 + 네이버)</option>
                        <option value="kakao">카카오만</option>
                        <option value="naver">네이버만</option>
                    </select>
                </label>
                
                <button type="button" class="button button-primary" onclick="startBulkCrawl()">
                    선택한 장소 이미지 크롤링 시작
                </button>
            </div>
        </form>
        
        <div id="bulk-crawl-progress" style="display: none; margin-top: 20px;">
            <h3>크롤링 진행 상황</h3>
            <div id="progress-bar" style="width: 100%; background: #f0f0f1; height: 30px; border-radius: 3px;">
                <div id="progress-fill" style="width: 0%; background: #2271b1; height: 100%; border-radius: 3px; transition: width 0.3s;"></div>
            </div>
            <p id="progress-text">준비중...</p>
            <div id="crawl-log" style="background: #f9f9f9; padding: 10px; max-height: 300px; overflow-y: auto; margin-top: 10px;"></div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#select-all').on('change', function() {
            $('input[name="places[]"]').prop('checked', this.checked);
        });
    });
    
    function startBulkCrawl() {
        const places = jQuery('input[name="places[]"]:checked').map(function() {
            return this.value;
        }).get();
        
        if (places.length === 0) {
            alert('크롤링할 장소를 선택해주세요.');
            return;
        }
        
        if (!confirm(`${places.length}개 장소의 이미지를 크롤링하시겠습니까?`)) {
            return;
        }
        
        // UI 초기화
        jQuery('#bulk-crawl-progress').show();
        jQuery('#progress-fill').css('width', '0%');
        jQuery('#crawl-log').empty();
        
        // 순차적으로 크롤링 실행
        let current = 0;
        const source = jQuery('select[name="crawl_source"]').val();
        
        function crawlNext() {
            if (current >= places.length) {
                jQuery('#progress-text').text('크롤링 완료!');
                return;
            }
            
            const placeId = places[current];
            const progress = ((current + 1) / places.length * 100).toFixed(1);
            
            jQuery('#progress-fill').css('width', progress + '%');
            jQuery('#progress-text').text(`진행중: ${current + 1} / ${places.length}`);
            
            // 개별 크롤링 실행
            jQuery.post(ajaxurl, {
                action: 'crawl_images',
                nonce: '<?php echo wp_create_nonce('image_crawl_nonce'); ?>',
                post_id: placeId,
                source: source
            }, function(response) {
                const logEntry = jQuery('<div>');
                
                if (response.success && response.data.count > 0) {
                    logEntry.html(`✅ 장소 #${placeId}: ${response.data.count}개 이미지 발견`);
                    
                    // 첫 번째 이미지 자동 첨부
                    if (response.data.images.length > 0) {
                        jQuery.post(ajaxurl, {
                            action: 'attach_image_to_post',
                            nonce: '<?php echo wp_create_nonce('image_crawl_nonce'); ?>',
                            post_id: placeId,
                            image_url: response.data.images[0].url,
                            set_featured: true
                        });
                    }
                } else {
                    logEntry.html(`❌ 장소 #${placeId}: 이미지 없음`);
                }
                
                jQuery('#crawl-log').append(logEntry);
                jQuery('#crawl-log').scrollTop(jQuery('#crawl-log')[0].scrollHeight);
                
                current++;
                setTimeout(crawlNext, 2000); // 2초 대기 후 다음 진행
            });
        }
        
        crawlNext();
    }
    </script>
    <?php
}

/**
 * 관리자 메뉴에 추가
 */
function sungsuya_add_image_crawling_menu() {
    add_submenu_page(
        'sungsuya-admin',
        '이미지 크롤링',
        '🖼️ 이미지 크롤링',
        'manage_options',
        'image-crawling',
        'sungsuya_bulk_image_crawling_page'
    );
}
add_action('admin_menu', 'sungsuya_add_image_crawling_menu', 30);
