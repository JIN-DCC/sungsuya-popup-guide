<?php
/**
 * 도움말 시스템
 * 
 * @package SungsuyaV2
 * @since 2025.06.27
 */

// 각 페이지에 도움말 추가
add_action('admin_head', 'sungsuya_add_help_system');

function sungsuya_add_help_system() {
    $screen = get_current_screen();
    if (!$screen) return;
    
    // 도움말 내용 정의
    $help_contents = array(
        'places_page_bulk-crawling-system' => array(
            'title' => '대량크롤링 사용법',
            'content' => '
                <h3>대량크롤링 시스템</h3>
                <p>카카오맵 API를 통해 성수동 지역의 장소 정보를 자동으로 수집합니다.</p>
                <h4>사용 방법:</h4>
                <ol>
                    <li>키워드 입력 (예: 성수동 카페, 성수동 맛집)</li>
                    <li>크롤링 시작 버튼 클릭</li>
                    <li>실시간으로 진행 상황 확인</li>
                    <li>완료 후 장소 목록에서 확인</li>
                </ol>
                <h4>주의사항:</h4>
                <ul>
                    <li>API 키가 설정되어 있어야 합니다</li>
                    <li>한 번에 너무 많은 데이터를 크롤링하면 API 제한에 걸릴 수 있습니다</li>
                </ul>
            '
        ),
        
        'toplevel_page_image-crawling' => array(
            'title' => '이미지 크롤링 시스템',
            'content' => '
                <h3>이미지 크롤링</h3>
                <p>등록된 장소의 이미지를 카카오맵과 네이버에서 자동으로 수집합니다.</p>
                <h4>기능:</h4>
                <ul>
                    <li>개별 크롤링: 특정 장소의 이미지만 수집</li>
                    <li>일괄 크롤링: 이미지가 없는 모든 장소 대상</li>
                    <li>자동 썸네일 설정: 첫 번째 이미지를 대표 이미지로</li>
                    <li>최대 5개까지 갤러리 이미지 수집</li>
                </ul>
                <h4>처리 시간:</h4>
                <p>장소당 약 2-3초 소요됩니다. 많은 수를 처리할 경우 시간이 걸릴 수 있습니다.</p>
            '
        ),
        
        'places_page_integrated-map-generation' => array(
            'title' => '지도 생성 시스템',
            'content' => '
                <h3>통합 지도생성 시스템</h3>
                <p>주소 정보를 기반으로 정확한 좌표를 생성하고 정적지도를 만듭니다.</p>
                <h4>처리 과정:</h4>
                <ol>
                    <li>주소가 있지만 좌표가 없는 장소 자동 감지</li>
                    <li>네이버 지오코딩 API로 정확한 좌표 생성</li>
                    <li>좌표 기반으로 정적지도 이미지 생성</li>
                    <li>장소에 좌표와 지도 정보 저장</li>
                </ol>
                <h4>API 설정:</h4>
                <p>네이버 Maps API 키가 반드시 설정되어 있어야 합니다.</p>
            '
        ),
        
        'sungsuya-관리_page_popup-csv-upload' => array(
            'title' => 'CSV 업로드 가이드',
            'content' => '
                <h3>팝업스토어 CSV 업로드</h3>
                <p>AI가 생성한 CSV 파일을 통해 팝업스토어 정보를 일괄 등록합니다.</p>
                <h4>CSV 형식:</h4>
                <ul>
                    <li>인코딩: UTF-8 (필수)</li>
                    <li>구분자: 쉼표(,)</li>
                    <li>필수 컬럼: name, address, start_date, end_date</li>
                </ul>
                <h4>업로드 과정:</h4>
                <ol>
                    <li>CSV 파일 선택</li>
                    <li>미리보기로 데이터 확인</li>
                    <li>업로드 실행</li>
                    <li>자동 지오코딩 처리</li>
                    <li>WordPress 포스트로 저장</li>
                </ol>
            '
        )
    );
    
    // 현재 페이지의 도움말 가져오기
    $current_help = isset($help_contents[$screen->id]) ? $help_contents[$screen->id] : null;
    
    if ($current_help) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // 도움말 버튼 추가
            var helpButton = '<button class="help-button" title="도움말">?</button>';
            $('.wrap h1').append(helpButton);
            
            // 도움말 모달 HTML
            var helpModal = '<div class="help-modal-overlay">';
            helpModal += '<div class="help-modal">';
            helpModal += '<div class="help-modal-header">';
            helpModal += '<h2><?php echo esc_js($current_help['title']); ?></h2>';
            helpModal += '<button class="help-modal-close">×</button>';
            helpModal += '</div>';
            helpModal += '<div class="help-modal-content">';
            helpModal += '<?php echo esc_js(str_replace(array("\r", "\n"), '', $current_help['content'])); ?>';
            helpModal += '</div>';
            helpModal += '</div>';
            helpModal += '</div>';
            
            $('body').append(helpModal);
            
            // 도움말 버튼 클릭 이벤트
            $('.help-button').on('click', function(e) {
                e.preventDefault();
                $('.help-modal-overlay').fadeIn(200);
            });
            
            // 닫기 버튼 클릭
            $('.help-modal-close, .help-modal-overlay').on('click', function(e) {
                if (e.target === this) {
                    $('.help-modal-overlay').fadeOut(200);
                }
            });
            
            // ESC 키로 닫기
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) {
                    $('.help-modal-overlay').fadeOut(200);
                }
            });
        });
        </script>
        
        <style>
        /* 도움말 버튼 */
        .help-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            margin-left: 10px;
            background: #2271b1;
            color: #fff;
            border: none;
            border-radius: 50%;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .help-button:hover {
            background: #135e96;
            transform: scale(1.1);
        }
        
        /* 도움말 모달 */
        .help-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 100000;
        }
        
        .help-modal {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
        }
        
        .help-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .help-modal-header h2 {
            margin: 0;
            font-size: 18px;
            color: #23282d;
        }
        
        .help-modal-close {
            width: 30px;
            height: 30px;
            background: none;
            border: none;
            font-size: 24px;
            color: #666;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .help-modal-close:hover {
            color: #dc3232;
        }
        
        .help-modal-content {
            padding: 20px;
            overflow-y: auto;
            max-height: calc(80vh - 70px);
        }
        
        .help-modal-content h3 {
            margin-top: 0;
            color: #23282d;
        }
        
        .help-modal-content h4 {
            margin-top: 20px;
            margin-bottom: 10px;
            color: #2271b1;
        }
        
        .help-modal-content ol,
        .help-modal-content ul {
            margin-left: 20px;
        }
        
        .help-modal-content li {
            margin-bottom: 5px;
        }
        </style>
        <?php
    }
}

// 툴팁 시스템 추가
add_action('admin_footer', 'sungsuya_add_tooltip_system');

function sungsuya_add_tooltip_system() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // 툴팁이 필요한 요소에 data-tooltip 속성 추가
        var tooltips = {
            '.column-operating_status': '장소의 현재 운영 상태를 표시합니다',
            '.column-place_type': '장소의 유형 (맛집, 카페, 팝업스토어 등)',
            '.crawling-button': '클릭하여 이 장소의 이미지를 크롤링합니다',
            '.stat-box': '클릭하면 해당 목록으로 이동합니다'
        };
        
        // 툴팁 요소 생성
        $('body').append('<div class="sungsuya-tooltip"></div>');
        var $tooltip = $('.sungsuya-tooltip');
        
        // 툴팁 표시/숨기기
        $.each(tooltips, function(selector, text) {
            $(document).on('mouseenter', selector, function(e) {
                $tooltip.text(text).show();
                
                var offset = $(this).offset();
                $tooltip.css({
                    top: offset.top - $tooltip.outerHeight() - 5,
                    left: offset.left + ($(this).outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                });
            }).on('mouseleave', selector, function() {
                $tooltip.hide();
            });
        });
    });
    </script>
    
    <style>
    .sungsuya-tooltip {
        display: none;
        position: absolute;
        background: #333;
        color: #fff;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 10000;
        pointer-events: none;
    }
    
    .sungsuya-tooltip:after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        margin-left: -5px;
        border-width: 5px;
        border-style: solid;
        border-color: #333 transparent transparent transparent;
    }
    </style>
    <?php
}
