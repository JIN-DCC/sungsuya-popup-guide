<?php
/**
 * 정확도 개선된 이미지 크롤링 관리자 페이지
 * 
 * @package SungsuyaV2
 * @since 3.0.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

// 메뉴 추가
// 기존 메뉴들은 통합 시스템으로 대체됨 (2025-07-02)
/*
add_action('admin_menu', 'accurate_image_crawling_menu', 30);

function accurate_image_crawling_menu() {
    add_submenu_page(
        'edit.php?post_type=places',
        '정확한 이미지 크롤링',
        '🎯 정확한 이미지 크롤링',
        'manage_options',
        'accurate-image-crawling',
        'render_accurate_image_crawling_page'
    );
}
*/

/**
 * 관리 페이지 렌더링
 */
function render_accurate_image_crawling_page() {
    ?>
    <div class="wrap">
        <h1>
            <span class="dashicons dashicons-camera" style="font-size: 30px; margin-right: 10px;"></span>
            정확한 이미지 크롤링
        </h1>
        
        <div class="notice notice-info">
            <p>
                <strong>🎯 정확도 개선 버전</strong><br>
                3단계 검색 전략으로 장소와 정확히 일치하는 이미지만 수집합니다.<br>
                • 1단계: 정확한 주소 매칭<br>
                • 2단계: 공식 채널 우선<br>
                • 3단계: 검증된 리뷰/블로그
            </p>
        </div>

        <div style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px;">
            <h2>장소 선택</h2>
            
            <?php
            // 이미지가 없는 장소 우선 표시
            $args = array(
                'post_type' => 'places',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_thumbnail_id',
                        'compare' => 'NOT EXISTS'
                    )
                ),
                'orderby' => 'date',
                'order' => 'DESC'
            );
            
            $places_without_images = new WP_Query($args);
            
            // 모든 장소
            $all_places = get_posts(array(
                'post_type' => 'places',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            ?>
            
            <div style="margin-bottom: 20px;">
                <label for="place-select" style="display: block; margin-bottom: 10px; font-weight: bold;">
                    크롤링할 장소를 선택하세요:
                </label>
                <select id="place-select" style="width: 100%; max-width: 500px; padding: 8px;">
                    <option value="">-- 장소를 선택하세요 --</option>
                    
                    <?php if ($places_without_images->have_posts()) : ?>
                        <optgroup label="🚨 이미지가 없는 장소 (우선 처리)">
                            <?php while ($places_without_images->have_posts()) : $places_without_images->the_post(); ?>
                                <option value="<?php echo get_the_ID(); ?>" data-address="<?php echo esc_attr(get_post_meta(get_the_ID(), 'address', true)); ?>">
                                    <?php the_title(); ?> - <?php echo get_post_meta(get_the_ID(), 'address', true); ?>
                                </option>
                            <?php endwhile; ?>
                        </optgroup>
                    <?php endif; ?>
                    <?php wp_reset_postdata(); ?>
                    
                    <optgroup label="📍 모든 장소">
                        <?php foreach ($all_places as $place) : ?>
                            <option value="<?php echo $place->ID; ?>" data-address="<?php echo esc_attr(get_post_meta($place->ID, 'address', true)); ?>">
                                <?php echo $place->post_title; ?> - <?php echo get_post_meta($place->ID, 'address', true); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            
            <div id="place-info" style="display: none; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin-top: 0;">선택된 장소 정보</h3>
                <p><strong>장소명:</strong> <span id="place-name"></span></p>
                <p><strong>주소:</strong> <span id="place-address"></span></p>
                <p><strong>현재 이미지:</strong> <span id="current-images"></span></p>
            </div>
            
            <div style="margin-top: 20px;">
                <button id="start-crawling" class="button button-primary button-large" disabled>
                    <span class="dashicons dashicons-search" style="vertical-align: middle;"></span>
                    정확한 이미지 크롤링 시작
                </button>
                
                <button id="test-accuracy" class="button button-secondary button-large" disabled style="margin-left: 10px;">
                    <span class="dashicons dashicons-visibility" style="vertical-align: middle;"></span>
                    정확도 테스트
                </button>
            </div>
        </div>
        
        <!-- 진행 상황 표시 -->
        <div id="crawling-progress" style="display: none; background: white; padding: 20px; margin-top: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>크롤링 진행 상황</h2>
            <div class="progress-bar" style="background: #f0f0f0; height: 30px; border-radius: 15px; overflow: hidden; margin-bottom: 20px;">
                <div class="progress-fill" style="background: #27ae60; height: 100%; width: 0%; transition: width 0.5s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                    0%
                </div>
            </div>
            <div id="progress-log" style="background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 13px;">
                
            </div>
        </div>
        
        <!-- 결과 표시 -->
        <div id="crawling-results" style="display: none; background: white; padding: 20px; margin-top: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>크롤링 결과</h2>
            <div id="results-content"></div>
        </div>
    </div>
    
    <style>
        .image-result {
            display: inline-block;
            margin: 10px;
            text-align: center;
            vertical-align: top;
            width: 200px;
        }
        .image-result img {
            max-width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .image-score {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        .score-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: bold;
            margin-left: 5px;
        }
        .score-high { background: #27ae60; color: white; }
        .score-medium { background: #f39c12; color: white; }
        .score-low { background: #e74c3c; color: white; }
        
        .stage-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-top: 3px;
        }
        .stage-1 { background: #3498db; color: white; }
        .stage-2 { background: #9b59b6; color: white; }
        .stage-3 { background: #e67e22; color: white; }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        const placeSelect = $('#place-select');
        const placeInfo = $('#place-info');
        const startButton = $('#start-crawling');
        const testButton = $('#test-accuracy');
        const progressDiv = $('#crawling-progress');
        const resultsDiv = $('#crawling-results');
        const progressLog = $('#progress-log');
        
        // 장소 선택 시
        placeSelect.on('change', function() {
            const placeId = $(this).val();
            
            if (placeId) {
                const selectedOption = $(this).find('option:selected');
                const placeName = selectedOption.text().split(' - ')[0];
                const address = selectedOption.data('address');
                
                $('#place-name').text(placeName);
                $('#place-address').text(address || '주소 없음');
                
                // 현재 이미지 확인
                checkCurrentImages(placeId);
                
                placeInfo.show();
                startButton.prop('disabled', false);
                testButton.prop('disabled', false);
            } else {
                placeInfo.hide();
                startButton.prop('disabled', true);
                testButton.prop('disabled', true);
            }
        });
        
        // 현재 이미지 확인
        function checkCurrentImages(placeId) {
            $.post(ajaxurl, {
                action: 'check_place_images',
                post_id: placeId,
                _ajax_nonce: '<?php echo wp_create_nonce('check_place_images'); ?>'
            }, function(response) {
                if (response.success) {
                    $('#current-images').html(response.data.html);
                }
            });
        }
        
        // 크롤링 시작
        startButton.on('click', function() {
            const placeId = placeSelect.val();
            if (!placeId) return;
            
            $(this).prop('disabled', true);
            progressDiv.show();
            resultsDiv.hide();
            progressLog.empty();
            
            addLog('🚀 정확한 이미지 크롤링을 시작합니다...');
            updateProgress(10);
            
            $.post(ajaxurl, {
                action: 'accurate_crawl_images',
                post_id: placeId,
                nonce: '<?php echo wp_create_nonce('accurate_image_crawl'); ?>'
            }, function(response) {
                if (response.success) {
                    updateProgress(100);
                    addLog('✅ ' + response.data.message);
                    showResults(response.data.images);
                } else {
                    addLog('❌ 오류: ' + response.data);
                    alert('크롤링 실패: ' + response.data);
                }
            }).fail(function() {
                addLog('❌ 서버 오류가 발생했습니다.');
                alert('서버 오류가 발생했습니다.');
            }).always(function() {
                startButton.prop('disabled', false);
            });
        });
        
        // 정확도 테스트
        testButton.on('click', function() {
            const placeId = placeSelect.val();
            if (!placeId) return;
            
            $(this).prop('disabled', true);
            progressDiv.show();
            resultsDiv.hide();
            progressLog.empty();
            
            addLog('🔍 정확도 테스트를 시작합니다...');
            
            $.post(ajaxurl, {
                action: 'test_image_accuracy',
                post_id: placeId,
                nonce: '<?php echo wp_create_nonce('test_image_accuracy'); ?>'
            }, function(response) {
                if (response.success) {
                    showTestResults(response.data);
                } else {
                    alert('테스트 실패: ' + response.data);
                }
            }).always(function() {
                testButton.prop('disabled', false);
            });
        });
        
        // 진행 상황 업데이트
        function updateProgress(percent) {
            $('.progress-fill').css('width', percent + '%').text(percent + '%');
        }
        
        // 로그 추가
        function addLog(message) {
            const time = new Date().toLocaleTimeString();
            progressLog.append(`<div>[${time}] ${message}</div>`);
            progressLog.scrollTop(progressLog[0].scrollHeight);
        }
        
        // 결과 표시
        function showResults(images) {
            resultsDiv.show();
            const content = $('#results-content');
            content.empty();
            
            if (images && images.length > 0) {
                content.append('<p>총 ' + images.length + '개의 이미지가 저장되었습니다:</p>');
                const grid = $('<div style="display: flex; flex-wrap: wrap;"></div>');
                
                images.forEach(function(img, index) {
                    const scoreClass = img.score >= 15 ? 'score-high' : (img.score >= 10 ? 'score-medium' : 'score-low');
                    const item = $(`
                        <div class="image-result">
                            <img src="${img.url}" alt="이미지 ${index + 1}">
                            <div class="image-score">
                                점수: <span class="score-badge ${scoreClass}">${img.score || 'N/A'}</span>
                            </div>
                        </div>
                    `);
                    grid.append(item);
                });
                
                content.append(grid);
            } else {
                content.html('<p>저장된 이미지가 없습니다.</p>');
            }
        }
        
        // 테스트 결과 표시
        function showTestResults(data) {
            resultsDiv.show();
            const content = $('#results-content');
            content.empty();
            
            content.append('<h3>정확도 테스트 결과</h3>');
            content.append('<p>총 ' + data.total + '개의 후보 이미지를 찾았습니다.</p>');
            
            const table = $('<table class="wp-list-table widefat fixed striped"></table>');
            table.append(`
                <thead>
                    <tr>
                        <th>이미지</th>
                        <th>출처</th>
                        <th>단계</th>
                        <th>점수</th>
                        <th>검색어</th>
                    </tr>
                </thead>
            `);
            
            const tbody = $('<tbody></tbody>');
            data.results.forEach(function(result) {
                const stageText = ['', '정확한 매칭', '공식 채널', '검증된 리뷰'][result.stage] || '알 수 없음';
                const row = $(`
                    <tr>
                        <td><img src="${result.url}" style="max-width: 100px; max-height: 60px;"></td>
                        <td>${result.source}</td>
                        <td><span class="stage-badge stage-${result.stage}">단계 ${result.stage}: ${stageText}</span></td>
                        <td><strong>${result.score}</strong></td>
                        <td style="font-size: 12px;">${result.query}</td>
                    </tr>
                `);
                tbody.append(row);
            });
            
            table.append(tbody);
            content.append(table);
        }
    });
    </script>
    <?php
}

// AJAX: 현재 이미지 확인
add_action('wp_ajax_check_place_images', 'ajax_check_place_images');
function ajax_check_place_images() {
    check_ajax_referer('check_place_images');
    
    $post_id = intval($_POST['post_id']);
    $thumbnail_id = get_post_thumbnail_id($post_id);
    
    if ($thumbnail_id) {
        $thumbnail_url = wp_get_attachment_thumb_url($thumbnail_id);
        $attachment_count = count(get_attached_media('image', $post_id));
        
        $html = sprintf(
            '<img src="%s" style="max-height: 50px; vertical-align: middle; margin-right: 10px;"> %d개의 이미지',
            esc_url($thumbnail_url),
            $attachment_count
        );
    } else {
        $html = '<span style="color: #e74c3c;">이미지 없음</span>';
    }
    
    wp_send_json_success(array('html' => $html));
}
