<?php
/**
 * 팝업스토어 크롤링 결과 관리 페이지
 * 
 * 크롤링 결과를 CSV로 다운로드하고 관리
 * 
 * @package SungsuyaV2
 * @since 2.1.0
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

// 크롤링 결과 다운로드 처리
if (isset($_GET['action']) && $_GET['action'] === 'download_crawl_results') {
    if (isset($_GET['file']) && wp_verify_nonce($_GET['_wpnonce'], 'download_crawl_results')) {
        download_crawl_results($_GET['file']);
    }
}

// AI 프롬프트 다운로드
if (isset($_GET['action']) && $_GET['action'] === 'download_ai_prompt') {
    download_ai_prompt();
}

?>

<div class="wrap">
    <h1>🎪 팝업스토어 크롤링 결과 관리</h1>
    
    <div class="notice notice-info">
        <h3>📋 워크플로우</h3>
        <ol>
            <li><strong>크롤링 실행</strong>: 팝업스토어 데이터를 수집합니다</li>
            <li><strong>CSV 다운로드</strong>: 크롤링 결과를 CSV로 다운로드합니다</li>
            <li><strong>AI 정리</strong>: 다운로드한 CSV와 AI 프롬프트를 사용해 데이터를 정리합니다</li>
            <li><strong>CSV 업로드</strong>: 정리된 CSV를 업로드하여 등록합니다</li>
        </ol>
    </div>
    
    <!-- 크롤링 실행 섹션 -->
    <div class="card">
        <h2>1️⃣ 크롤링 실행</h2>
        <p>팝업스토어 정보를 수집합니다. (네이버, 인스타그램, 팝플)</p>
        
        <div class="button-group">
            <button id="run-crawling" class="button button-primary">
                🔍 크롤링 시작
            </button>
            <span id="crawling-status" style="margin-left: 10px;"></span>
        </div>
        
        <div id="crawling-progress" style="display: none; margin-top: 20px;">
            <div class="progress-bar" style="width: 100%; height: 20px; background: #f0f0f1; border-radius: 10px;">
                <div id="progress-fill" style="width: 0%; height: 100%; background: #0073aa; border-radius: 10px; transition: width 0.3s;"></div>
            </div>
            <p id="progress-text" style="margin-top: 10px;">준비 중...</p>
        </div>
    </div>
    
    <!-- 크롤링 결과 다운로드 -->
    <div class="card" style="margin-top: 20px;">
        <h2>2️⃣ 크롤링 결과 다운로드</h2>
        
        <?php
        // 최근 크롤링 결과 파일 목록
        $upload_dir = wp_upload_dir();
        $results_dir = $upload_dir['basedir'] . '/popup-crawl-results';
        $results_url = $upload_dir['baseurl'] . '/popup-crawl-results';
        
        if (file_exists($results_dir)) {
            $files = glob($results_dir . '/*.{csv,xlsx}', GLOB_BRACE);
            rsort($files); // 최신 파일 먼저
            
            if (!empty($files)) {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>파일명</th>
                            <th>생성일시</th>
                            <th>크기</th>
                            <th>액션</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach (array_slice($files, 0, 10) as $file) { // 최근 10개만
                            $filename = basename($file);
                            $filesize = size_format(filesize($file));
                            $filetime = date('Y-m-d H:i', filemtime($file));
                            $download_url = wp_nonce_url(
                                add_query_arg(array(
                                    'action' => 'download_crawl_results',
                                    'file' => $filename
                                )),
                                'download_crawl_results'
                            );
                            ?>
                            <tr>
                                <td><?php echo esc_html($filename); ?></td>
                                <td><?php echo $filetime; ?></td>
                                <td><?php echo $filesize; ?></td>
                                <td>
                                    <a href="<?php echo $download_url; ?>" class="button button-small">
                                        📥 다운로드
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <?php
            } else {
                echo '<p>아직 크롤링 결과가 없습니다. 크롤링을 먼저 실행해주세요.</p>';
            }
        } else {
            echo '<p>크롤링 결과 디렉토리가 없습니다.</p>';
        }
        ?>
    </div>
    
    <!-- AI 프롬프트 -->
    <div class="card" style="margin-top: 20px;">
        <h2>3️⃣ AI 데이터 정리</h2>
        <p>다운로드한 CSV 파일을 AI로 정리하세요.</p>
        
        <div class="ai-prompt-section" style="background: #f0f7ff; padding: 20px; border-radius: 5px;">
            <h3>📝 AI 프롬프트</h3>
            <textarea readonly style="width: 100%; height: 300px; font-family: monospace; font-size: 12px;">
다음은 성수동 팝업스토어 크롤링 데이터입니다. 우리 시스템의 입력 양식에 맞춰 정리해주세요.

## 필수 항목 (반드시 포함)
- 브랜드명: 정확한 브랜드명
- 스토어명: 팝업스토어 전체 이름
- 주소: 성수동 상세 주소 (도로명주소 선호)
- 시작일: YYYY-MM-DD 형식
- 종료일: YYYY-MM-DD 형식
- 카테고리: 패션/뷰티/라이프스타일/푸드/아트/테크/스포츠 중 선택
- 운영상태: open(운영중)/coming_soon(오픈예정)/closed(종료)

## 선택 항목 (가능한 경우)
- 운영시간: HH:MM-HH:MM 형식
- 전화번호: 02-0000-0000 형식
- 인스타그램: @계정명
- 웹사이트: https:// 포함 전체 URL
- 예약필수: Y 또는 N
- 입장료: 무료 또는 금액
- 주차가능: Y 또는 N
- 콜라보: 협업 브랜드나 아티스트
- 설명: 팝업의 특징이나 컨셉
- 체류시간: 15-30분/30-60분/60-90분/90-120분/2시간 이상
- 포토스팟: 인기 촬영 장소
- 방문팁: 유용한 정보
- 이메일: 연락 이메일
- 해시태그: 관련 해시태그
- 예약링크: 예약 URL
- 타겟층: 주요 고객층
- 가격대: 상품 가격대
- 연령제한: 있을 경우
- 평일혼잡도: 여유/보통/혼잡/매우혼잡
- 주말혼잡도: 여유/보통/혼잡/매우혼잡
- 추천시간: 오전/점심/오후/저녁/밤/언제든지

## 주의사항
1. 정보가 불확실한 경우 "(예상)" 표시를 추가해주세요
2. 없는 정보는 빈칸으로 두세요 (거짓 정보 금지)
3. 날짜는 반드시 YYYY-MM-DD 형식
4. 예약필수, 주차가능은 Y/N으로만
5. 주소는 최대한 상세하게

CSV 형식으로 정리해주세요.
            </textarea>
            
            <div style="margin-top: 10px;">
                <button onclick="copyAIPrompt()" class="button">📋 프롬프트 복사</button>
                <a href="<?php echo add_query_arg('action', 'download_ai_prompt'); ?>" class="button">
                    📥 프롬프트 다운로드
                </a>
            </div>
        </div>
        
        <!-- CSV 템플릿 다운로드 -->
        <div style="margin-top: 20px;">
            <h3>📄 CSV 템플릿</h3>
            <p>AI에게 이 템플릿 형식으로 정리하도록 요청하세요.</p>
            <a href="<?php echo admin_url('admin.php?page=popup-csv-upload&action=download_template'); ?>" 
               class="button button-secondary">
                📥 CSV 템플릿 다운로드
            </a>
        </div>
    </div>
    
    <!-- CSV 업로드 링크 -->
    <div class="card" style="margin-top: 20px;">
        <h2>4️⃣ 정리된 데이터 업로드</h2>
        <p>AI로 정리하고 검증한 CSV 파일을 업로드합니다.</p>
        <p>
            <a href="<?php echo admin_url('admin.php?page=popup-csv-upload'); ?>" 
               class="button button-primary">
                📤 CSV 업로드 페이지로 이동
            </a>
        </p>
    </div>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.button-group {
    display: flex;
    align-items: center;
    gap: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 크롤링 실행
    $('#run-crawling').on('click', function() {
        const button = $(this);
        const status = $('#crawling-status');
        const progressDiv = $('#crawling-progress');
        const progressFill = $('#progress-fill');
        const progressText = $('#progress-text');
        
        button.prop('disabled', true);
        status.html('<span style="color: #f39c12;">⏳ 크롤링 중...</span>');
        progressDiv.show();
        
        // 시뮬레이션 (실제로는 AJAX 호출)
        let progress = 0;
        const sources = ['네이버 검색 중...', '인스타그램 수집 중...', '팝플 확인 중...', '데이터 정리 중...'];
        
        const interval = setInterval(function() {
            progress += 25;
            progressFill.css('width', progress + '%');
            progressText.text(sources[Math.floor(progress/25) - 1] || '완료!');
            
            if (progress >= 100) {
                clearInterval(interval);
                button.prop('disabled', false);
                status.html('<span style="color: #46b450;">✅ 크롤링 완료!</span>');
                progressText.text('크롤링이 완료되었습니다. CSV 파일을 다운로드하세요.');
                
                // 페이지 새로고침하여 파일 목록 업데이트
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
        }, 1000);
    });
});

// AI 프롬프트 복사
function copyAIPrompt() {
    const textarea = document.querySelector('.ai-prompt-section textarea');
    textarea.select();
    document.execCommand('copy');
    alert('프롬프트가 복사되었습니다!');
}
</script>

<?php

/**
 * 크롤링 결과 다운로드
 */
function download_crawl_results($filename) {
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/popup-crawl-results/' . $filename;
    
    if (!file_exists($file_path)) {
        wp_die('파일을 찾을 수 없습니다.');
    }
    
    // 보안 체크
    $allowed_extensions = array('csv', 'xlsx');
    $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
    
    if (!in_array($file_extension, $allowed_extensions)) {
        wp_die('허용되지 않은 파일 형식입니다.');
    }
    
    // 파일 다운로드
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file_path));
    
    readfile($file_path);
    exit;
}

/**
 * AI 프롬프트 다운로드
 */
function download_ai_prompt() {
    $prompt_content = "다음은 성수동 팝업스토어 크롤링 데이터입니다. 우리 시스템의 입력 양식에 맞춰 정리해주세요.\n\n";
    $prompt_content .= "## 필수 항목 (반드시 포함)\n";
    $prompt_content .= "- 브랜드명: 정확한 브랜드명\n";
    $prompt_content .= "- 스토어명: 팝업스토어 전체 이름\n";
    $prompt_content .= "- 주소: 성수동 상세 주소 (도로명주소 선호)\n";
    $prompt_content .= "- 시작일: YYYY-MM-DD 형식\n";
    $prompt_content .= "- 종료일: YYYY-MM-DD 형식\n";
    $prompt_content .= "- 카테고리: 패션/뷰티/라이프스타일/푸드/아트/테크/스포츠 중 선택\n";
    $prompt_content .= "- 운영상태: open(운영중)/coming_soon(오픈예정)/closed(종료)\n\n";
    
    $prompt_content .= "## 선택 항목 (가능한 경우)\n";
    $prompt_content .= "- 운영시간: HH:MM-HH:MM 형식\n";
    $prompt_content .= "- 전화번호: 02-0000-0000 형식\n";
    $prompt_content .= "- 인스타그램: @계정명\n";
    $prompt_content .= "- 웹사이트: https:// 포함 전체 URL\n";
    $prompt_content .= "- 예약필수: Y 또는 N\n";
    $prompt_content .= "- 입장료: 무료 또는 금액\n";
    $prompt_content .= "- 주차가능: Y 또는 N\n";
    $prompt_content .= "- 콜라보: 협업 브랜드나 아티스트\n";
    $prompt_content .= "- 설명: 팝업의 특징이나 컨셉\n";
    $prompt_content .= "- 체류시간: 15-30분/30-60분/60-90분/90-120분/2시간 이상\n";
    $prompt_content .= "- 포토스팟: 인기 촬영 장소\n";
    $prompt_content .= "- 방문팁: 유용한 정보\n\n";
    
    $prompt_content .= "## 주의사항\n";
    $prompt_content .= "1. 정보가 불확실한 경우 \"(예상)\" 표시를 추가해주세요\n";
    $prompt_content .= "2. 없는 정보는 빈칸으로 두세요 (거짓 정보 금지)\n";
    $prompt_content .= "3. 날짜는 반드시 YYYY-MM-DD 형식\n";
    $prompt_content .= "4. 예약필수, 주차가능은 Y/N으로만\n";
    $prompt_content .= "5. 주소는 최대한 상세하게\n\n";
    
    $prompt_content .= "CSV 형식으로 정리해주세요.";
    
    // 파일 다운로드
    header('Content-Type: text/plain; charset=UTF-8');
    header('Content-Disposition: attachment; filename="popup_ai_prompt.txt"');
    echo $prompt_content;
    exit;
}
