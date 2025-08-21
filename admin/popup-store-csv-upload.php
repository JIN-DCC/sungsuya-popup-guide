<?php
/**
 * 팝업스토어 CSV 업로드 관리자 페이지
 * 
 * === 개요 ===
 * 이 시스템은 팝업스토어 정보를 CSV 파일로 일괄 등록하는 기능을 제공합니다.
 * 반자동 워크플로우를 통해 데이터 품질을 보장하면서도 효율적인 입력이 가능합니다.
 * 
 * === 워크플로우 ===
 * 1. 크롤링: 네이버, 인스타그램, 팝플 등에서 팝업스토어 정보 수집
 * 2. AI 정리: 제공된 프롬프트를 사용하여 AI가 데이터를 CSV 형식으로 정리
 * 3. 검증: 관리자가 데이터 검토 및 수정
 * 4. 업로드: CSV 파일 업로드로 일괄 등록
 * 
 * === 주요 기능 ===
 * - CSV 템플릿 다운로드
 * - AI 프롬프트 제공 (필수/선택 필드 명세 포함)
 * - 중복 처리 옵션 (건너뛰기/업데이트)
 * - 발행 상태 선택 (즉시 발행/임시 저장)
 * - 업로드 이력 관리
 * 
 * === 사용 팁 ===
 * - Excel에서 작업 후 "CSV UTF-8 (쉼표로 분리)"로 저장
 * - 날짜는 반드시 YYYY-MM-DD 형식 사용
 * - 카테고리는 7개 중 하나만 사용 (패션/뷰티/라이프스타일/푸드/아트/테크/스포츠)
 * - 주소는 "서울특별시 성동구"로 시작하는 전체 도로명주소 입력
 * 
 * @package SungsuyaV2
 * @since 2.0.0
 */

// 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

// 디버깅 로그 - 페이지 로드 시작
error_log('[CSV Page] 페이지 로드됨');
error_log('[CSV Page] REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
error_log('[CSV Page] POST 데이터: ' . print_r($_POST, true));

// CSV Importer 클래스 로드
require_once get_template_directory() . '/inc/csv-importer/class-popup-store-csv-importer.php';

// 업로드 처리
if (isset($_POST['action']) && $_POST['action'] === 'upload_csv') {
    error_log('[CSV Page] upload_csv 액션 감지됨');
    check_admin_referer('popup_csv_upload', 'popup_csv_nonce');
    handle_csv_upload();
}

// 템플릿 다운로드 처리
if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
    download_csv_template();
}

?>

<div class="wrap">
    <h1>🎪 팝업스토어 CSV 일괄 등록</h1>
    
    <?php
    // 메시지 표시
    if (isset($_GET['message'])) {
        $message_type = isset($_GET['type']) ? $_GET['type'] : 'info';
        $message = urldecode($_GET['message']);
        echo "<div class='notice notice-{$message_type} is-dismissible'><p>{$message}</p></div>";
    }
    ?>
    
<div class="wrap">
    <h1>🎪 팝업스토어 CSV 일괄 등록</h1>
    
    <?php
    // 메시지 표시
    if (isset($_GET['message'])) {
        $message_type = isset($_GET['type']) ? $_GET['type'] : 'info';
        $message = urldecode($_GET['message']);
        echo "<div class='notice notice-{$message_type} is-dismissible'><p>{$message}</p></div>";
    }
    ?>
    
    <!-- 사용 가이드 -->
    <div class="usage-guide-box" style="background: #e7f5ff; border: 2px solid #0073aa; padding: 20px; margin-bottom: 30px; border-radius: 5px;">
        <h2 style="margin-top: 0;">📖 사용 가이드</h2>
        <ol style="font-size: 14px; line-height: 1.8;">
            <li><strong>템플릿 다운로드</strong>: 먼저 CSV 템플릿을 다운로드하여 양식을 확인하세요.</li>
            <li><strong>AI로 데이터 정리</strong>: 크롤링한 데이터를 AI 프롬프트와 함께 제공하여 정리하세요.</li>
            <li><strong>데이터 검증</strong>: AI가 정리한 데이터를 검토하고 필요시 수정하세요.</li>
            <li><strong>CSV 업로드</strong>: 완성된 CSV 파일을 업로드하여 일괄 등록하세요.</li>
        </ol>
        <p style="margin-bottom: 0; color: #d63638;">⚠️ <strong>중요</strong>: 엑셀에서 CSV 저장 시 반드시 <strong>UTF-8 인코딩</strong>으로 저장하세요!</p>
    </div>
    
    <!-- 간단 사용법 -->
    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin-bottom: 30px; border-radius: 5px;">
        <h3 style="margin-top: 0;">🚀 빠른 시작</h3>
        <p style="margin: 0;"><strong>1단계:</strong> 네이버/인스타그램/팝플에서 "성수동 팝업스토어" 검색하여 정보 수집<br>
        <strong>2단계:</strong> 아래 AI 프롬프트와 함께 Claude/ChatGPT에 전달하여 CSV 형식으로 정리<br>
        <strong>3단계:</strong> 생성된 CSV를 검토 후 업로드</p>
    </div>
    
    <!-- 템플릿 다운로드 섹션 -->
    <div class="template-section card" style="background: #f0f0f1; padding: 25px; margin-bottom: 30px; border-radius: 5px; border-left: 5px solid #0073aa;">
        <h2 style="margin-top: 0;">📥 Step 1: 템플릿 다운로드</h2>
        <p style="font-size: 15px;">CSV 양식을 다운로드하여 어떤 정보가 필요한지 확인하세요.</p>
        
        <div class="button-group" style="margin: 20px 0;">
            <a href="<?php echo add_query_arg('action', 'download_template'); ?>" 
               class="button button-primary button-large">
                📥 CSV 템플릿 다운로드
            </a>
            <button type="button" onclick="showTemplatePreview()" class="button button-secondary button-large">
                👁️ 템플릿 미리보기
            </button>
        </div>
        
        <!-- 템플릿 미리보기 -->
        <div id="template-preview" style="display: none; margin-top: 20px; background: white; padding: 15px; border-radius: 5px; overflow-x: auto;">
            <h4>CSV 템플릿 구조:</h4>
            <table class="widefat" style="font-size: 12px;">
                <thead>
                    <tr style="background: #0073aa; color: white;">
                        <th>브랜드명</th>
                        <th>스토어명</th>
                        <th>주소</th>
                        <th>시작일</th>
                        <th>종료일</th>
                        <th>카테고리</th>
                        <th>운영상태</th>
                        <th>운영시간</th>
                        <th>...</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>나이키</td>
                        <td>나이키 에어맥스 팝업</td>
                        <td>서울 성동구 연무장길 7</td>
                        <td>2025-06-01</td>
                        <td>2025-06-30</td>
                        <td>패션</td>
                        <td>open</td>
                        <td>11:00-20:00</td>
                        <td>...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- AI 프롬프트 섹션 -->
    <div class="ai-prompt-section card" style="background: #fff8e5; padding: 25px; margin-bottom: 30px; border-radius: 5px; border-left: 5px solid #f39c12;">
        <h2 style="margin-top: 0;">🤖 Step 2: AI로 데이터 정리</h2>
        <p style="font-size: 15px;">크롤링한 데이터를 AI에게 정리하도록 요청하세요.</p>
        
        <div class="prompt-tabs" style="margin: 20px 0;">
            <button class="tab-button active" onclick="showTab('prompt')">📝 AI 프롬프트</button>
            <button class="tab-button" onclick="showTab('fields')">📋 필드 설명</button>
            <button class="tab-button" onclick="showTab('example')">💡 예시</button>
        </div>
        
        <!-- AI 프롬프트 탭 -->
        <div id="prompt-tab" class="tab-content" style="background: white; padding: 20px; border-radius: 5px;">
            <h4>AI에게 복사해서 전달할 내용:</h4>
            <textarea readonly id="ai-prompt-full" style="width: 100%; height: 400px; font-family: monospace; font-size: 13px; line-height: 1.5;">
## 팝업스토어 데이터 정리 요청

아래 크롤링 데이터를 다음 CSV 양식에 맞춰 정리해주세요.

### 필수 항목 (반드시 포함)
- 브랜드명: 정확한 브랜드명 (예: 나이키, 아디다스, 무신사)
- 스토어명: 팝업스토어 전체 이름 (예: 나이키 에어맥스 팝업)
- 주소: 성수동 상세 주소 (도로명주소 선호)
- 시작일: YYYY-MM-DD 형식 (예: 2025-06-01)
- 종료일: YYYY-MM-DD 형식 (예: 2025-06-30)
- 카테고리: 다음 중 하나만 선택
  * 패션 (의류, 신발, 액세서리)
  * 뷰티 (화장품, 향수, 스킨케어)
  * 라이프스타일 (가구, 소품, 문구)
  * 푸드 (카페, 베이커리, 식품)
  * 아트 (전시, 갤러리, 예술)
  * 테크 (전자제품, 게임, IT)
  * 스포츠 (운동, 아웃도어)
- 운영상태: 다음 중 하나만 선택
  * open (현재 운영중)
  * coming_soon (오픈 예정)
  * closed (종료됨)

### 선택 항목 (있는 정보만 포함)
- 운영시간: HH:MM-HH:MM 형식 (예: 11:00-20:00)
- 전화번호: 02-0000-0000 형식
- 인스타그램: @계정명 (@ 포함)
- 웹사이트: https:// 포함한 전체 URL
- 예약필수: Y 또는 N만 입력
- 입장료: 무료 또는 구체적 금액 (예: 5000원)
- 주차가능: Y 또는 N만 입력
- 콜라보: 협업 브랜드나 아티스트명
- 설명: 팝업의 특징이나 컨셉 (100자 이내)
- 체류시간: 다음 중 선택
  * 15-30분
  * 30-60분
  * 60-90분
  * 90-120분
  * 2시간 이상
- 포토스팟: 인기 촬영 장소나 포인트
- 방문팁: 유용한 정보나 추천사항

### 추가 항목 (선택)
- 이메일: 공식 이메일 주소
- 해시태그: 공식 해시태그 (# 포함)
- 예약링크: 예약 페이지 URL
- 타겟층: 주요 고객층 (예: 20대 여성)
- 가격대: 상품 가격대 (예: 1-5만원)
- 연령제한: 있을 경우만 (예: 19세 이상)
- 평일혼잡도: 여유/보통/혼잡/매우혼잡
- 주말혼잡도: 여유/보통/혼잡/매우혼잡
- 추천시간: 오전/점심/오후/저녁/밤/언제든지

### 중요 규칙
1. 확실하지 않은 정보는 "(예상)" 표시 추가
2. 없는 정보는 빈칸으로 (거짓 정보 절대 금지)
3. 날짜는 반드시 YYYY-MM-DD 형식
4. 예약필수, 주차가능은 Y 또는 N만
5. 주소는 "서울특별시 성동구"로 시작하는 전체 주소
6. 각 항목은 쉼표(,)로 구분하되, 설명에 쉼표가 있으면 큰따옴표로 감싸기

### 출력 형식
CSV 형식으로 출력해주세요. 첫 줄은 헤더, 그 다음부터 데이터입니다.

---
[여기에 크롤링한 데이터를 붙여넣으세요]
            </textarea>
            <button onclick="copyFullPrompt()" class="button button-primary">📋 전체 프롬프트 복사</button>
        </div>
        
        <!-- 필드 설명 탭 -->
        <div id="fields-tab" class="tab-content" style="display: none; background: white; padding: 20px; border-radius: 5px;">
            <h4>필드별 상세 설명:</h4>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>필드명</th>
                        <th>필수/선택</th>
                        <th>형식</th>
                        <th>예시</th>
                        <th>설명</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>브랜드명</strong></td>
                        <td><span style="color: red;">필수</span></td>
                        <td>텍스트</td>
                        <td>나이키</td>
                        <td>공식 브랜드명</td>
                    </tr>
                    <tr>
                        <td><strong>스토어명</strong></td>
                        <td><span style="color: red;">필수</span></td>
                        <td>텍스트</td>
                        <td>나이키 에어맥스 팝업</td>
                        <td>팝업스토어 전체 이름</td>
                    </tr>
                    <tr>
                        <td><strong>주소</strong></td>
                        <td><span style="color: red;">필수</span></td>
                        <td>주소</td>
                        <td>서울특별시 성동구 연무장길 7</td>
                        <td>도로명주소 전체</td>
                    </tr>
                    <tr>
                        <td><strong>시작일</strong></td>
                        <td><span style="color: red;">필수</span></td>
                        <td>날짜</td>
                        <td>2025-06-01</td>
                        <td>YYYY-MM-DD 형식</td>
                    </tr>
                    <tr>
                        <td><strong>종료일</strong></td>
                        <td><span style="color: red;">필수</span></td>
                        <td>날짜</td>
                        <td>2025-06-30</td>
                        <td>YYYY-MM-DD 형식</td>
                    </tr>
                    <tr>
                        <td><strong>카테고리</strong></td>
                        <td><span style="color: red;">필수</span></td>
                        <td>선택</td>
                        <td>패션</td>
                        <td>7개 중 택1</td>
                    </tr>
                    <tr>
                        <td><strong>운영상태</strong></td>
                        <td><span style="color: red;">필수</span></td>
                        <td>선택</td>
                        <td>open</td>
                        <td>open/coming_soon/closed</td>
                    </tr>
                    <tr style="background: #f9f9f9;">
                        <td>운영시간</td>
                        <td>선택</td>
                        <td>시간</td>
                        <td>11:00-20:00</td>
                        <td>HH:MM-HH:MM</td>
                    </tr>
                    <tr style="background: #f9f9f9;">
                        <td>예약필수</td>
                        <td>선택</td>
                        <td>Y/N</td>
                        <td>Y</td>
                        <td>Y 또는 N만</td>
                    </tr>
                    <tr style="background: #f9f9f9;">
                        <td>주차가능</td>
                        <td>선택</td>
                        <td>Y/N</td>
                        <td>N</td>
                        <td>Y 또는 N만</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- 예시 탭 -->
        <div id="example-tab" class="tab-content" style="display: none; background: white; padding: 20px; border-radius: 5px;">
            <h4>CSV 예시:</h4>
            <pre style="background: #f0f0f1; padding: 15px; overflow-x: auto; font-size: 12px;">
브랜드명,스토어명,주소,시작일,종료일,카테고리,운영상태,운영시간,전화번호,인스타그램,웹사이트,예약필수,입장료,주차가능,콜라보,설명,체류시간,포토스팟,방문팁
나이키,나이키 에어맥스 팝업,서울특별시 성동구 연무장길 7,2025-06-01,2025-06-30,패션,open,11:00-20:00,02-1234-5678,@nike_seoul,https://nike.com,N,무료,Y,카시나,"에어맥스 신제품 체험 공간",30-60분,"입구 네온사인, 2층 포토존",주말 오전 방문 추천
아모레퍼시픽,설화수 팝업스토어,서울특별시 성동구 왕십리로 63,2025-06-15,2025-07-15,뷰티,open,10:00-19:00,,@sulwhasoo_official,,Y,무료,N,,"한국 전통미를 현대적으로 재해석",60-90분,한옥 컨셉 공간,사전예약 필수
            </pre>
        </div>
    </div>
        
    <!-- 파일 업로드 섹션 -->
    <div class="upload-section card" style="background: #fff; padding: 25px; margin-bottom: 30px; border-radius: 5px; border-left: 5px solid #46b450;">
        <h2 style="margin-top: 0;">📤 Step 3: CSV 파일 업로드</h2>
        <p style="font-size: 15px;">AI로 정리하고 검증한 CSV 파일을 업로드하세요.</p>
        
        <!-- 업로드 전 체크리스트 -->
        <div class="checklist" style="background: #f0f0f1; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h4 style="margin-top: 0;">✅ 업로드 전 체크리스트</h4>
            <ul style="margin: 0;">
                <li>날짜가 YYYY-MM-DD 형식인가요?</li>
                <li>주소가 성수동 주소인가요?</li>
                <li>카테고리가 지정된 7개 중 하나인가요?</li>
                <li>예약필수/주차가능이 Y 또는 N인가요?</li>
                <li>UTF-8 인코딩으로 저장했나요?</li>
            </ul>
        </div>
        
        <form method="post" enctype="multipart/form-data" id="csv-upload-form">
            <?php wp_nonce_field('popup_csv_upload', 'popup_csv_nonce'); ?>
            <input type="hidden" name="action" value="upload_csv">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="popup_csv">CSV 파일 선택</label>
                    </th>
                    <td>
                        <input type="file" name="popup_csv" id="popup_csv" accept=".csv" required />
                        <p class="description">
                            <strong>파일 요구사항:</strong><br>
                            • 형식: CSV (쉼표로 구분)<br>
                            • 인코딩: UTF-8 (한글 깨짐 방지)<br>
                            • 크기: 최대 2MB<br>
                            • 첫 줄: 헤더 (컬럼명)
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">중복 처리 방법</th>
                    <td>
                        <fieldset>
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="radio" name="duplicate_action" value="skip" checked />
                                <strong>건너뛰기</strong>
                                <span class="description" style="display: block; margin-left: 25px;">
                                    동일한 브랜드명+주소가 이미 있으면 건너뜁니다. (기존 데이터 보존)
                                </span>
                            </label>
                            <label style="display: block;">
                                <input type="radio" name="duplicate_action" value="update" />
                                <strong>업데이트</strong>
                                <span class="description" style="display: block; margin-left: 25px;">
                                    기존 데이터를 새 데이터로 덮어씁니다. (날짜나 정보 업데이트 시 사용)
                                </span>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">발행 상태</th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_publish" id="auto_publish" value="1" />
                            <strong>즉시 발행</strong>
                            <span class="description">
                                체크하면 바로 공개됩니다. 체크 안하면 임시저장 상태로 등록됩니다.
                            </span>
                        </label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary button-large">
                    📤 CSV 업로드 및 등록 시작
                </button>
                <span class="spinner" style="float: none; margin-top: 0;"></span>
            </p>
        </form>
    </div>
    
    <!-- 사용 가이드 -->
    <div class="usage-guide" style="margin-top: 40px;">
        <h2>📚 사용 가이드</h2>
        
        <div style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 5px;">
            <h3>필수 컬럼</h3>
            <ul>
                <li><strong>브랜드명</strong>: 정확한 브랜드명 (예: 나이키)</li>
                <li><strong>스토어명</strong>: 팝업스토어 전체 이름 (예: 나이키 에어맥스 팝업)</li>
                <li><strong>주소</strong>: 성수동 상세 주소 (도로명주소 선호)</li>
                <li><strong>시작일</strong>: YYYY-MM-DD 형식</li>
                <li><strong>종료일</strong>: YYYY-MM-DD 형식</li>
                <li><strong>카테고리</strong>: 패션/뷰티/라이프스타일/푸드/아트/테크/스포츠 중 선택</li>
                <li><strong>운영상태</strong>: open(운영중)/coming_soon(오픈예정)/closed(종료)</li>
            </ul>
            
            <h3>주의사항</h3>
            <ul>
                <li>날짜는 반드시 YYYY-MM-DD 형식으로 입력해주세요.</li>
                <li>예약필수, 주차가능은 Y 또는 N으로 입력해주세요.</li>
                <li>정보가 없는 항목은 빈칸으로 두면 됩니다.</li>
                <li>한글이 깨지는 경우 UTF-8 BOM으로 저장해주세요.</li>
            </ul>
        </div>
    </div>
    
    <!-- 최근 업로드 이력 -->
    <div class="upload-history" style="margin-top: 40px;">
        <h2>📊 최근 업로드 이력</h2>
        <?php display_upload_history(); ?>
    </div>
</div>

<!-- AI 프롬프트 모달 -->
<div id="ai-prompt-modal" class="modal" style="display: none;">
    <div class="modal-content" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); max-width: 600px; width: 90%; z-index: 100000;">
        <h3>🤖 AI용 수집 프롬프트</h3>
        <textarea readonly id="ai-prompt-text" style="width: 100%; height: 400px; font-family: monospace; font-size: 12px;">
성수동의 현재 운영 중인 팝업스토어 정보를 수집해서 CSV 형식으로 만들어주세요.

필수 컬럼 (반드시 포함):
- 브랜드명: 정확한 브랜드명
- 스토어명: 팝업스토어 전체 이름
- 주소: 성수동 상세 주소 (도로명주소 선호)
- 시작일: YYYY-MM-DD 형식
- 종료일: YYYY-MM-DD 형식
- 카테고리: 패션/뷰티/라이프스타일/푸드/아트/테크/스포츠 중 선택
- 운영상태: open(운영중)/coming_soon(오픈예정)/closed(종료)

선택 컬럼 (가능한 경우):
- 운영시간: HH:MM-HH:MM 형식
- 전화번호: 02-0000-0000 형식
- 인스타그램: @계정명
- 웹사이트: https:// 포함 전체 URL
- 예약필수: Y 또는 N
- 입장료: 무료 또는 금액
- 주차가능: Y 또는 N
- 콜라보: 협업 브랜드나 아티스트
- 설명: 팝업의 특징이나 컨셉
- 체류시간: 15-30분/30-60분/60-90분
- 포토스팟: 인기 촬영 장소
- 방문팁: 유용한 정보

수집 방법:
1. 네이버에서 "성수동 팝업스토어 <?php echo date('Y년 n월'); ?>" 검색
2. 인스타그램 #성수동팝업 #성수팝업스토어 최신 게시물
3. 팝플(popple.co.kr) 성수동 지역 검색

주의사항:
- 정보가 없는 항목은 빈칸으로 둡니다
- 날짜는 반드시 YYYY-MM-DD 형식
- 주소는 최대한 상세하게
- 이미 종료된 팝업은 운영상태를 'closed'로 표시
        </textarea>
        <p style="margin-top: 10px;">
            <button onclick="copyPrompt()" class="button button-primary">📋 복사</button>
            <button onclick="closeModal()" class="button">닫기</button>
        </p>
    </div>
    <div class="modal-backdrop" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999;" onclick="closeModal()"></div>
</div>

</div>

<script>
// 템플릿 미리보기
function showTemplatePreview() {
    const preview = document.getElementById('template-preview');
    preview.style.display = preview.style.display === 'none' ? 'block' : 'none';
}

// 탭 전환
function showTab(tabName) {
    // 모든 탭 버튼과 컨텐츠 숨기기
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.style.display = 'none');
    
    // 선택된 탭 표시
    event.target.classList.add('active');
    document.getElementById(tabName + '-tab').style.display = 'block';
}

// 전체 프롬프트 복사
function copyFullPrompt() {
    const textarea = document.getElementById('ai-prompt-full');
    textarea.select();
    document.execCommand('copy');
    
    // 복사 완료 메시지
    const button = event.target;
    const originalText = button.textContent;
    button.textContent = '✅ 복사 완료!';
    button.style.background = '#46b450';
    
    setTimeout(() => {
        button.textContent = originalText;
        button.style.background = '';
    }, 2000);
}

// 파일 선택 시 검증
document.getElementById('popup_csv').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        if (fileSize > 2) {
            alert('파일 크기가 2MB를 초과합니다.');
            e.target.value = '';
            return;
        }
        
        // 파일명 표시
        const fileName = file.name;
        const fileInfo = document.createElement('div');
        fileInfo.style.marginTop = '10px';
        fileInfo.style.color = '#0073aa';
        fileInfo.innerHTML = `📄 선택된 파일: <strong>${fileName}</strong> (${fileSize}MB)`;
        
        const existingInfo = this.parentNode.querySelector('.file-info');
        if (existingInfo) {
            existingInfo.remove();
        }
        fileInfo.classList.add('file-info');
        this.parentNode.appendChild(fileInfo);
    }
});

// 폼 제출 시 확인
document.getElementById('csv-upload-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (confirm('CSV 파일을 업로드하고 팝업스토어를 등록하시겠습니까?')) {
        // 스피너 표시
        this.querySelector('.spinner').style.visibility = 'visible';
        this.querySelector('button[type="submit"]').disabled = true;
        this.submit();
    }
});
</script>

<style>
/* 카드 스타일 */
.card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}

/* 탭 스타일 */
.tab-button {
    background: #f0f0f1;
    border: 1px solid #c3c4c7;
    padding: 10px 20px;
    margin-right: 5px;
    cursor: pointer;
    border-radius: 3px 3px 0 0;
    border-bottom: none;
}

.tab-button.active {
    background: white;
    border-bottom: 1px solid white;
    position: relative;
    z-index: 1;
}

.tab-content {
    border: 1px solid #c3c4c7;
    border-radius: 0 5px 5px 5px;
    margin-top: -1px;
}

/* 업로드 이력 테이블 */
.upload-history table {
    width: 100%;
    border-collapse: collapse;
}

.upload-history th,
.upload-history td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.upload-history th {
    background: #f0f0f1;
    font-weight: 600;
}

/* 상태 색상 */
.status-success { color: #46b450; font-weight: bold; }
.status-warning { color: #ffb900; font-weight: bold; }
.status-error { color: #dc3232; font-weight: bold; }

/* 버튼 그룹 */
.button-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

/* 스피너 */
.spinner {
    visibility: hidden;
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #0073aa;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* 사용 가이드 박스 */
.usage-guide-box {
    position: relative;
    overflow: hidden;
}

.usage-guide-box::before {
    content: '📖';
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 48px;
    opacity: 0.1;
}

/* 체크리스트 */
.checklist ul {
    list-style: none;
    padding-left: 0;
}

.checklist li {
    padding-left: 25px;
    position: relative;
    margin-bottom: 5px;
}

.checklist li::before {
    content: '☐';
    position: absolute;
    left: 0;
}

/* 반응형 */
@media screen and (max-width: 782px) {
    .button-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .tab-button {
        display: block;
        width: 100%;
        margin-bottom: 5px;
    }
}
</style>

<?php

/**
 * CSV 업로드 처리 함수
 */
function handle_csv_upload() {
    // 디버깅 로그
    error_log('[CSV Upload] 업로드 시작');
    error_log('[CSV Upload] FILES: ' . print_r($_FILES, true));
    error_log('[CSV Upload] POST: ' . print_r($_POST, true));
    
    if (!isset($_FILES['popup_csv']) || $_FILES['popup_csv']['error'] !== UPLOAD_ERR_OK) {
        error_log('[CSV Upload] 파일 업로드 에러: ' . ($_FILES['popup_csv']['error'] ?? 'no file'));
        wp_redirect(add_query_arg([
            'message' => urlencode('파일 업로드에 실패했습니다.'),
            'type' => 'error'
        ]));
        exit;
    }
    
    $uploaded_file = $_FILES['popup_csv'];
    $file_path = $uploaded_file['tmp_name'];
    
    // CSV Importer 인스턴스 생성
    $importer = new PopupStoreCSVImporter();
    
    error_log('[CSV Upload] CSV 검증 시작: ' . $file_path);
    
    // CSV 검증
    $validation = $importer->validate_csv($file_path);
    
    error_log('[CSV Upload] 검증 결과: ' . ($validation['valid'] ? '성공' : '실패'));
    error_log('[CSV Upload] 데이터 행 수: ' . count($validation['data']));
    
    if (!$validation['valid']) {
        wp_redirect(add_query_arg([
            'message' => urlencode('CSV 검증 실패: ' . implode(', ', $validation['errors'])),
            'type' => 'error'
        ]));
        exit;
    }
    
    // 옵션 설정
    $options = [
        'duplicate_action' => $_POST['duplicate_action'] ?? 'skip',
        'auto_publish' => isset($_POST['auto_publish']) && $_POST['auto_publish'] === '1'
    ];
    
    // 데이터 임포트
    $import_results = $importer->import($validation['data'], $options);
    
    // 업로드 이력 저장
    save_upload_history($uploaded_file['name'], $import_results);
    
    // 결과 메시지 생성
    $message = sprintf(
        '업로드 완료: 성공 %d개, 건너뜀 %d개, 실패 %d개',
        $import_results['success'],
        $import_results['skipped'],
        $import_results['failed']
    );
    
    if (!empty($validation['warnings'])) {
        $message .= ' (경고: ' . count($validation['warnings']) . '개)';
    }
    
    wp_redirect(add_query_arg([
        'message' => urlencode($message),
        'type' => $import_results['failed'] > 0 ? 'warning' : 'success'
    ]));
    exit;
}

/**
 * CSV 템플릿 다운로드
 */
function download_csv_template() {
    $importer = new PopupStoreCSVImporter();
    $csv_content = $importer->generate_template();
    
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="popup_store_template.csv"');
    echo $csv_content;
    exit;
}

/**
 * 업로드 이력 저장
 */
function save_upload_history($filename, $results) {
    $history = get_option('popup_csv_upload_history', []);
    
    $history[] = [
        'date' => current_time('mysql'),
        'user' => wp_get_current_user()->display_name,
        'filename' => $filename,
        'success' => $results['success'],
        'skipped' => $results['skipped'],
        'failed' => $results['failed'],
        'log' => array_slice($results['log'], -10) // 최근 10개 로그만 저장
    ];
    
    // 최근 20개만 유지
    if (count($history) > 20) {
        $history = array_slice($history, -20);
    }
    
    update_option('popup_csv_upload_history', $history);
}

/**
 * 업로드 이력 표시
 */
function display_upload_history() {
    $history = get_option('popup_csv_upload_history', []);
    
    if (empty($history)) {
        echo '<p>아직 업로드 이력이 없습니다.</p>';
        return;
    }
    
    // 최신순으로 정렬
    $history = array_reverse($history);
    ?>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>업로드 일시</th>
                <th>파일명</th>
                <th>업로더</th>
                <th>성공</th>
                <th>건너뜀</th>
                <th>실패</th>
                <th>상태</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history as $item): ?>
                <tr>
                    <td><?php echo date('Y-m-d H:i', strtotime($item['date'])); ?></td>
                    <td><?php echo esc_html($item['filename']); ?></td>
                    <td><?php echo esc_html($item['user']); ?></td>
                    <td class="status-success"><?php echo $item['success']; ?></td>
                    <td class="status-warning"><?php echo $item['skipped']; ?></td>
                    <td class="status-error"><?php echo $item['failed']; ?></td>
                    <td>
                        <?php if ($item['failed'] > 0): ?>
                            <span class="status-error">⚠️ 일부 실패</span>
                        <?php elseif ($item['success'] > 0): ?>
                            <span class="status-success">✅ 성공</span>
                        <?php else: ?>
                            <span class="status-warning">⏭️ 모두 건너뜀</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php
}
