<?php
/**
 * 이미지 크롤링 관리자 페이지 렌더링 함수
 * 
 * @package SungsuyaV2
 * @since 2.0.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

// 관리자 페이지 렌더링 함수
function render_image_crawling_page() {
    // 통계 가져오기
    $total_places = wp_count_posts('places')->publish;
    
    $places_with_images = get_posts(array(
        'post_type' => 'places',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_thumbnail_id',
                'compare' => 'EXISTS'
            )
        ),
        'fields' => 'ids'
    ));
    
    $with_images_count = count($places_with_images);
    $without_images_count = $total_places - $with_images_count;
    $coverage = $total_places > 0 ? round(($with_images_count / $total_places) * 100) : 0;
    
    // 이미지 없는 장소 목록
    $places_without_images = get_posts(array(
        'post_type' => 'places',
        'posts_per_page' => 20,
        'meta_query' => array(
            array(
                'key' => '_thumbnail_id',
                'compare' => 'NOT EXISTS'
            )
        )
    ));
    ?>
    
    <div class="wrap image-crawling-wrap">
        <h1>📸 이미지 크롤링 시스템</h1>
        
        <!-- 통계 -->
        <div class="crawling-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_places); ?></div>
                <div class="stat-label">전체 장소</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($with_images_count); ?></div>
                <div class="stat-label">이미지 보유</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($without_images_count); ?></div>
                <div class="stat-label">이미지 필요</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $coverage; ?>%</div>
                <div class="stat-label">이미지 커버리지</div>
            </div>
        </div>
        
        <!-- 크롤링 진행 상황 -->
        <div class="crawling-progress" id="crawlingProgress">
            <h2>🔄 크롤링 진행 중...</h2>
            <div class="progress-bar-wrapper">
                <div class="progress-bar" id="progressBar">0%</div>
            </div>
            <div class="progress-info">
                <span id="progressText">준비 중...</span>
            </div>
            <div class="crawling-log" id="crawlingLog"></div>
            <button class="button" id="stopCrawling">중지</button>
        </div>
        
        <!-- 액션 버튼 -->
        <div class="action-buttons" style="margin-bottom: 30px;">
            <button class="button button-primary button-large" id="startBatchCrawling">
                🚀 이미지 없는 모든 장소 크롤링 시작
            </button>
            
            <?php if (isset($_GET['post_ids'])): ?>
                <button class="button button-primary button-large" id="startSelectedCrawling" data-post-ids="<?php echo esc_attr($_GET['post_ids']); ?>">
                    🎯 선택한 장소 크롤링 시작
                </button>
            <?php endif; ?>
            
            <span style="margin-left: 10px; color: #666;">
                * 크롤링은 카카오맵과 네이버에서 이미지를 자동으로 수집합니다.
            </span>
        </div>
        
        <!-- 이미지 없는 장소 목록 -->
        <div class="places-without-images">
            <h2>이미지가 없는 장소 (최근 20개)</h2>
            
            <?php if ($places_without_images): ?>
                <div class="place-list">
                    <?php foreach ($places_without_images as $place): ?>
                        <div class="place-item">
                            <div>
                                <strong>
                                    <a href="<?php echo get_edit_post_link($place->ID); ?>" target="_blank">
                                        <?php echo esc_html($place->post_title); ?>
                                    </a>
                                </strong>
                                <?php
                                $address = get_post_meta($place->ID, 'address', true);
                                if ($address) {
                                    echo '<br><small style="color: #666;">' . esc_html($address) . '</small>';
                                }
                                ?>
                            </div>
                            <div>
                                <span class="no-image-badge">이미지 없음</span>
                                <button class="button button-small crawl-single" data-post-id="<?php echo $place->ID; ?>" style="margin-left: 10px;">
                                    크롤링
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($without_images_count > 20): ?>
                    <p style="margin-top: 20px; text-align: center; color: #666;">
                        ... 그리고 <?php echo number_format($without_images_count - 20); ?>개 더
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p>🎉 모든 장소에 이미지가 있습니다!</p>
            <?php endif; ?>
        </div>
        
        <!-- 도움말 -->
        <div class="help-section" style="margin-top: 30px; background: #f0f7ff; padding: 20px; border-radius: 8px;">
            <h3>💡 이미지 크롤링 시스템 안내</h3>
            <ul>
                <li><strong>자동 수집</strong>: 카카오맵과 네이버에서 장소 관련 이미지를 자동으로 수집합니다.</li>
                <li><strong>썸네일 설정</strong>: 첫 번째 이미지가 자동으로 대표 이미지로 설정됩니다.</li>
                <li><strong>갤러리 저장</strong>: 최대 5개의 이미지를 수집하여 갤러리로 저장합니다.</li>
                <li><strong>중복 방지</strong>: 이미 이미지가 있는 장소는 건너뜁니다.</li>
                <li><strong>배치 처리</strong>: 한 번에 5개씩 처리하여 서버 부하를 줄입니다.</li>
            </ul>
            
            <h4 style="margin-top: 20px;">📖 사용 방법</h4>
            <ol>
                <li><strong>개별 크롤링</strong>: 각 장소 옆의 "크롤링" 버튼을 클릭하여 해당 장소의 이미지만 수집</li>
                <li><strong>일괄 크롤링</strong>: "이미지 없는 모든 장소 크롤링 시작" 버튼으로 전체 처리</li>
                <li><strong>진행 확인</strong>: 크롤링 중 실시간 로그와 진행률 확인 가능</li>
                <li><strong>중지</strong>: 필요시 "중지" 버튼으로 크롤링 중단 가능</li>
            </ol>
            
            <h4 style="margin-top: 20px;">⚠️ 주의사항</h4>
            <ul>
                <li style="color: #d63638;"><strong>API 키 확인</strong>: 시스템 설정 → API 설정에서 카카오/네이버 API 키가 입력되어 있어야 합니다.</li>
                <li><strong>처리 시간</strong>: 장소당 약 2-3초 소요됩니다. 많은 수의 장소를 처리할 경우 시간이 걸릴 수 있습니다.</li>
                <li><strong>이미지 품질</strong>: 자동 수집된 이미지는 검토 후 필요시 수동으로 교체하세요.</li>
                <li><strong>실패 시</strong>: 일부 장소는 이미지를 찾지 못할 수 있습니다. 이 경우 플레이스홀더 이미지가 사용됩니다.</li>
            </ul>
            
            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
                <strong>💡 Tip:</strong> 크롤링 실패 시 다음을 확인하세요:
                <ol style="margin: 10px 0 0 20px;">
                    <li>API 키가 올바르게 설정되었는지 확인</li>
                    <li>wp-content/uploads 폴더의 쓰기 권한 확인</li>
                    <li>PHP 메모리 제한이 충분한지 확인 (최소 128MB)</li>
                    <li>서버의 allow_url_fopen 설정이 활성화되어 있는지 확인</li>
                </ol>
            </div>
        </div>
    </div>
    
    <style>
    .image-crawling-wrap {
        max-width: 1200px;
        margin: 20px 0;
    }
    
    .crawling-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .stat-number {
        font-size: 36px;
        font-weight: bold;
        color: #2271b1;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #666;
        font-size: 14px;
    }
    
    .crawling-progress {
        background: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        display: none;
    }
    
    .progress-bar-wrapper {
        background: #f0f0f0;
        height: 30px;
        border-radius: 15px;
        overflow: hidden;
        margin: 20px 0;
    }
    
    .progress-bar {
        background: #2271b1;
        height: 100%;
        width: 0;
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: bold;
    }
    
    .crawling-log {
        background: #23282d;
        color: #0ff;
        padding: 15px;
        border-radius: 5px;
        min-height: 150px;
        max-height: 300px;
        overflow-y: auto;
        font-family: 'Consolas', 'Monaco', monospace;
        font-size: 12px;
        margin-top: 20px;
        white-space: pre-wrap;
        word-wrap: break-word;
        border: 1px solid #444;
    }
    
    .places-without-images {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .place-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    
    .place-item:last-child {
        border-bottom: none;
    }
    
    .no-image-badge {
        background: #ffeb3b;
        color: #333;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 12px;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        console.log('이미지 크롤링 시스템 초기화');
        
        // 일괄 크롤링 시작
        $('#startBatchCrawling').on('click', function() {
            console.log('일괄 크롤링 버튼 클릭');
            
            if (!confirm('이미지가 없는 모든 장소의 이미지를 크롤링하시겠습니까?')) {
                return;
            }
            
            $(this).prop('disabled', true).text('크롤링 중...');
            $('#crawlingProgress').show();
            $('#crawlingLog').empty();
            
            startBatchCrawling();
        });
        
        // 단일 크롤링
        $('.crawl-single').on('click', function(e) {
            e.preventDefault();
            console.log('단일 크롤링 버튼 클릭');
            
            const $button = $(this);
            const postId = $button.data('post-id');
            
            $button.text('크롤링 중...').prop('disabled', true);
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'crawl_place_images',
                    post_id: postId,
                    nonce: '<?php echo wp_create_nonce('crawl_images_nonce'); ?>'
                },
                success: function(response) {
                    console.log('크롤링 응답:', response);
                    if (response.success) {
                        $button.text('완료').css('color', 'green');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        $button.text('실패').css('color', 'red');
                        alert('크롤링 실패: ' + (response.data || '알 수 없는 오류'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('크롤링 오류:', error);
                    $button.text('오류').css('color', 'red');
                    alert('서버 오류: ' + error);
                }
            });
        });
        
        // 중지 버튼
        $('#stopCrawling').on('click', function() {
            window.isRunning = false;
            $(this).text('중지됨').prop('disabled', true);
        });
        
        // 일괄 크롤링 함수
        function startBatchCrawling(postIds = []) {
            let offset = 0;
            window.isRunning = true;
            
            function processBatch() {
                if (!window.isRunning) {
                    $('#progressText').text('크롤링이 중지되었습니다.');
                    $('#startBatchCrawling').prop('disabled', false).text('🚀 이미지 없는 모든 장소 크롤링 시작');
                    return;
                }
                
                const logMessage = `[${new Date().toLocaleTimeString()}] 배치 처리 중... (offset: ${offset})`;
                $('#crawlingLog').append(logMessage + '\n');
                $('#crawlingLog').scrollTop($('#crawlingLog')[0].scrollHeight);
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'batch_crawl_images',
                        post_ids: postIds,
                        offset: offset,
                        nonce: '<?php echo wp_create_nonce('crawl_images_nonce'); ?>'
                    },
                    success: function(response) {
                        console.log('배치 응답:', response);
                        
                        if (response.success) {
                            const data = response.data;
                            offset = data.offset;
                            
                            // 진행률 업데이트
                            const progress = data.progress || 0;
                            $('#progressBar').css('width', progress + '%').text(progress + '%');
                            $('#progressText').text(`${data.offset}/${data.total} 처리 완료 (성공: ${data.success}개)`);
                            
                            // 로그 추가
                            const log = `[${new Date().toLocaleTimeString()}] ${data.processed}개 처리, ${data.success}개 성공`;
                            $('#crawlingLog').append(log + '\n').scrollTop($('#crawlingLog')[0].scrollHeight);
                            
                            // 더 있으면 계속
                            if (data.has_more && window.isRunning) {
                                setTimeout(processBatch, 1000); // 1초 대기 후 다음 배치
                            } else {
                                $('#progressText').text('✅ 크롤링 완료!');
                                $('#stopCrawling').text('완료').prop('disabled', true);
                                $('#startBatchCrawling').prop('disabled', false).text('🚀 이미지 없는 모든 장소 크롤링 시작');
                                
                                const completeLog = `[${new Date().toLocaleTimeString()}] 크롤링 완료! 총 ${data.total}개 중 ${data.success}개 성공`;
                                $('#crawlingLog').append('\n' + completeLog + '\n');
                                
                                setTimeout(() => location.reload(), 3000);
                            }
                        } else {
                            const errorMsg = response.data || '알 수 없는 오류';
                            $('#progressText').text('❌ 오류: ' + errorMsg);
                            $('#crawlingLog').append(`\n[오류] ${errorMsg}\n`);
                            $('#stopCrawling').text('오류').prop('disabled', true);
                            $('#startBatchCrawling').prop('disabled', false).text('🚀 이미지 없는 모든 장소 크롤링 시작');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('배치 크롤링 오류:', error);
                        $('#progressText').text('❌ 서버 오류가 발생했습니다.');
                        $('#crawlingLog').append(`\n[서버 오류] ${error}\n`);
                        $('#stopCrawling').text('오류').prop('disabled', true);
                        $('#startBatchCrawling').prop('disabled', false).text('🚀 이미지 없는 모든 장소 크롤링 시작');
                    }
                });
            }
            
            processBatch();
        }
    });
    </script>
    
    <?php
}
