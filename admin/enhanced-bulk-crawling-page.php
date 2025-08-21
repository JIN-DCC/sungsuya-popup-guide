<?php
/**
 * Enhanced 크롤링 대량 페이지
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

// 관리자 권한 확인
if (!current_user_can('manage_options')) {
    wp_die('권한이 없습니다.');
}

?>
<div class="wrap">
    <h1>Enhanced 크롤링 - 대량 데이터 수집</h1>
    <p>네이버 API를 통해 실제 장소 데이터를 수집합니다.</p>
    
    <div class="crawling-settings">
        <h2>크롤링 설정</h2>
        
        <table class="form-table">
            <tr>
                <th>API 소스</th>
                <td>
                    <select id="source">
                        <option value="naver">네이버 지역검색</option>
                        <option value="kakao">카카오 로컬</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>장소유형 선택</th>
                <td>
                    <div id="place-type-radios">
                        <?php
                        // 장소유형 동적 로드
                        $place_types = get_terms(array(
                            'taxonomy' => 'place_type',
                            'hide_empty' => false
                        ));
                        
                        if (!is_wp_error($place_types) && !empty($place_types)) {
                            foreach ($place_types as $type) {
                                // 팝업스토어는 제외 (CSV 프로세스로 별도 관리)
                                if ($type->slug === 'popup-store' || $type->name === '팝업스토어') {
                                    continue;
                                }
                                
                                $metafield_type = get_term_meta($type->term_id, 'metafield_type', true);
                                $icon = ($metafield_type === 'food') ? '🍽️' : '🛍️';
                                $checked = ($type->name === '카페') ? 'checked' : '';
                                ?>
                                <label style="display: inline-block; margin-right: 20px; margin-bottom: 10px;">
                                    <input type="radio" name="place_type" value="<?php echo esc_attr($type->name); ?>" 
                                           data-type-id="<?php echo $type->term_id; ?>"
                                           data-metafield-type="<?php echo esc_attr($metafield_type); ?>"
                                           <?php echo $checked; ?>>
                                    <?php echo $icon; ?> <?php echo esc_html($type->name); ?>
                                    <?php if ($type->count > 0) { ?>
                                        <span style="color: #666;">(<?php echo $type->count; ?>)</span>
                                    <?php } ?>
                                </label>
                                <?php
                            }
                        } else {
                            echo '<p style="color: red;">장소유형을 불러올 수 없습니다.</p>';
                        }
                        ?>
                    </div>
                    <p class="description">크롤링할 장소유형을 선택하세요. 선택한 유형에 맞는 메타필드가 자동으로 수집됩니다.</p>
                </td>
            </tr>
            <tr>
                <th>지역</th>
                <td>
                    <input type="text" id="region" value="성수동" />
                </td>
            </tr>
            <tr>
                <th>최대 개수</th>
                <td>
                    <select id="limit">
                        <option value="10">10개</option>
                        <option value="20" selected>20개</option>
                        <option value="50">50개</option>
                    </select>
                </td>
            </tr>
        </table>
        
        <p>
            <button type="button" id="start-crawling" class="button button-primary button-large">
                크롤링 시작
            </button>
        </p>
    </div>
    
    <div id="crawling-progress" style="display: none;">
        <h2>진행 상황</h2>
        <div id="progress-text">준비 중...</div>
        <div id="log-container" style="height: 400px; overflow-y: auto; background: #f0f0f0; padding: 10px; margin-top: 20px;">
        </div>
    </div>
    
    <div id="results" style="display: none; margin-top: 20px;">
        <h2>크롤링 결과</h2>
        <div id="results-content"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let sessionId = null;
    let checkInterval = null;
    
    $('#start-crawling').on('click', function() {
        const source = $('#source').val();
        const selectedType = $('input[name="place_type"]:checked');
        
        if (!selectedType.length) {
            alert('장소유형을 선택해주세요.');
            return;
        }
        
        const placeTypeName = selectedType.val();
        const placeTypeId = selectedType.data('type-id');
        const metafieldType = selectedType.data('metafield-type');
        
        const region = $('#region').val();
        const limit = $('#limit').val();
        
        $('#start-crawling').prop('disabled', true);
        $('#crawling-progress').show();
        $('#log-container').empty();
        
        // AJAX 시작
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'start_bulk_discovery',
                nonce: '<?php echo wp_create_nonce('enhanced_crawling_nonce'); ?>',
                source: source,
                categories: [placeTypeName], // 배열로 전달 (기존 코드 호환성)
                place_type_id: placeTypeId,
                metafield_type: metafieldType,
                region: region,
                limit: limit
            },
            success: function(response) {
                if (response.success) {
                    sessionId = response.data.session_id;
                    addLog('크롤링 시작됨: ' + response.data.message);
                    startProgressCheck();
                } else {
                    addLog('오류: ' + response.data, 'error');
                    $('#start-crawling').prop('disabled', false);
                }
            },
            error: function() {
                addLog('AJAX 오류 발생', 'error');
                $('#start-crawling').prop('disabled', false);
            }
        });
    });
    
    function startProgressCheck() {
        checkInterval = setInterval(function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'check_bulk_progress',
                    session_id: sessionId
                },
                success: function(response) {
                    if (response.success) {
                        updateProgress(response.data);
                        
                        if (response.data.status === 'completed') {
                            clearInterval(checkInterval);
                            showResults(response.data);
                        }
                    }
                }
            });
        }, 2000); // 2초마다 체크
    }
    
    function updateProgress(data) {
        $('#progress-text').text(data.current_step || '진행 중...');
        
        // 로그 업데이트
        if (data.log) {
            const lines = data.log.split('\n');
            $('#log-container').empty();
            lines.forEach(function(line) {
                if (line.trim()) {
                    addLog(line);
                }
            });
        }
    }
    
    function showResults(data) {
        $('#results').show();
        $('#start-crawling').prop('disabled', false);
        
        let html = '<p>발견: ' + data.discovered_count + '개</p>';
        html += '<p>생성: ' + (data.created_posts ? data.created_posts.length : 0) + '개</p>';
        
        if (data.created_posts && data.created_posts.length > 0) {
            html += '<h3>생성된 포스트</h3>';
            html += '<ul>';
            data.created_posts.forEach(function(post) {
                html += '<li>' + post.name + ' - <a href="post.php?post=' + post.post_id + '&action=edit" target="_blank">편집</a></li>';
            });
            html += '</ul>';
        }
        
        $('#results-content').html(html);
    }
    
    function addLog(message, type = 'info') {
        const color = type === 'error' ? 'red' : 'black';
        $('#log-container').append('<div style="color: ' + color + ';">' + message + '</div>');
        $('#log-container').scrollTop($('#log-container')[0].scrollHeight);
    }
});
</script>
