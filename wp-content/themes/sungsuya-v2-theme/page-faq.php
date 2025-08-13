<?php
/**
 * Template Name: FAQ
 * 
 * 성수야! FAQ 페이지
 */

get_header();
?>

<div class="faq-container" style="max-width: 800px; margin: 0 auto; padding: 40px 20px;">
    <h1 class="page-title" style="text-align: center; margin-bottom: 20px; font-size: 36px;">자주 묻는 질문</h1>
    <p style="text-align: center; color: #666; margin-bottom: 50px; font-size: 18px;">
        성수야! 서비스 이용에 대해 궁금한 점을 확인해보세요
    </p>
    
    <!-- FAQ 카테고리 탭 -->
    <div class="faq-tabs" style="display: flex; justify-content: center; gap: 10px; margin-bottom: 40px; flex-wrap: wrap;">
        <button class="tab-button active" data-category="all" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 25px; cursor: pointer; font-size: 16px; transition: all 0.3s;">
            전체
        </button>
        <button class="tab-button" data-category="service" style="padding: 10px 20px; background: #f5f5f5; color: #666; border: none; border-radius: 25px; cursor: pointer; font-size: 16px; transition: all 0.3s;">
            서비스 이용
        </button>
        <button class="tab-button" data-category="tour" style="padding: 10px 20px; background: #f5f5f5; color: #666; border: none; border-radius: 25px; cursor: pointer; font-size: 16px; transition: all 0.3s;">
            투어플래너
        </button>
        <button class="tab-button" data-category="account" style="padding: 10px 20px; background: #f5f5f5; color: #666; border: none; border-radius: 25px; cursor: pointer; font-size: 16px; transition: all 0.3s;">
            계정/회원
        </button>
        <button class="tab-button" data-category="etc" style="padding: 10px 20px; background: #f5f5f5; color: #666; border: none; border-radius: 25px; cursor: pointer; font-size: 16px; transition: all 0.3s;">
            기타
        </button>
    </div>
    
    <!-- FAQ 아이템들 -->
    <div class="faq-items">
        
        <!-- 서비스 이용 -->
        <div class="faq-item" data-category="service" style="background: #f8f9fa; border-radius: 10px; margin-bottom: 20px; overflow: hidden;">
            <div class="faq-question" style="padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; color: #333;">성수야!는 무료로 이용할 수 있나요?</h3>
                <span class="toggle-icon" style="font-size: 20px; color: #4CAF50;">+</span>
            </div>
            <div class="faq-answer" style="padding: 0 20px; max-height: 0; overflow: hidden; transition: all 0.3s;">
                <div style="padding-bottom: 20px; color: #666; line-height: 1.6;">
                    네, 성수야!의 모든 기본 기능은 완전히 무료로 이용하실 수 있습니다. 
                    장소 검색, 투어플래너, 지도 서비스 등을 회원가입 없이도 자유롭게 사용할 수 있습니다. 
                    향후 프리미엄 기능이 추가될 수 있지만, 기본 서비스는 계속 무료로 제공될 예정입니다.
                </div>
            </div>
        </div>
        
        <div class="faq-item" data-category="service" style="background: #f8f9fa; border-radius: 10px; margin-bottom: 20px; overflow: hidden;">
            <div class="faq-question" style="padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; color: #333;">오프라인에서도 사용할 수 있나요?</h3>
                <span class="toggle-icon" style="font-size: 20px; color: #4CAF50;">+</span>
            </div>
            <div class="faq-answer" style="padding: 0 20px; max-height: 0; overflow: hidden; transition: all 0.3s;">
                <div style="padding-bottom: 20px; color: #666; line-height: 1.6;">
                    네, 성수야!는 PWA(Progressive Web App) 기술을 사용하여 오프라인에서도 이용 가능합니다. 
                    첫 방문 시 필요한 데이터가 자동으로 저장되며, 인터넷 연결이 없어도 저장된 투어 정보와 
                    기본 지도를 확인할 수 있습니다. 모바일에서 '홈 화면에 추가'하시면 앱처럼 사용하실 수 있습니다.
                </div>
            </div>
        </div>
        
        <div class="faq-item" data-category="service" style="background: #f8f9fa; border-radius: 10px; margin-bottom: 20px; overflow: hidden;">
            <div class="faq-question" style="padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; color: #333;">장소 정보는 얼마나 자주 업데이트되나요?</h3>
                <span class="toggle-icon" style="font-size: 20px; color: #4CAF50;">+</span>
            </div>
            <div class="faq-answer" style="padding: 0 20px; max-height: 0; overflow: hidden; transition: all 0.3s;">
                <div style="padding-bottom: 20px; color: #666; line-height: 1.6;">
                    장소 정보는 매일 자동으로 업데이트됩니다. 영업시간, 메뉴, 가격 등의 변경사항을 
                    실시간으로 반영하고 있으며, 팝업스토어의 경우 매일 새벽 자동으로 기간을 확인하여 
                    종료된 팝업은 자동으로 비활성화됩니다. 사용자 제보를 통한 수정도 즉시 반영하고 있습니다.
                </div>
            </div>
        </div>
        
        <!-- 투어플래너 -->
        <div class="faq-item" data-category="tour" style="background: #f8f9fa; border-radius: 10px; margin-bottom: 20px; overflow: hidden;">
            <div class="faq-question" style="padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; color: #333;">투어플래너는 어떻게 사용하나요?</h3>
                <span class="toggle-icon" style="font-size: 20px; color: #4CAF50;">+</span>
            </div>
            <div class="faq-answer" style="padding: 0 20px; max-height: 0; overflow: hidden; transition: all 0.3s;">
                <div style="padding-bottom: 20px; color: #666; line-height: 1.6;">
                    투어플래너 사용법은 매우 간단합니다:<br><br>
                    1. 투어플래너 페이지에서 방문하고 싶은 장소를 선택하세요 (최대 8개)<br>
                    2. 선택한 장소들은 드래그 앤 드롭으로 순서를 변경할 수 있습니다<br>
                    3. '경로 최적화' 버튼을 누르면 가장 효율적인 동선을 추천해드립니다<br>
                    4. 완성된 투어는 저장하거나 친구들과 공유할 수 있습니다<br>
                    5. 모바일에서는 스와이프로 쉽게 지도와 장소 목록을 전환할 수 있습니다
                </div>
            </div>
        </div>
        
        <div class="faq-item" data-category="tour" style="background: #f8f9fa; border-radius: 10px; margin-bottom: 20px; overflow: hidden;">
            <div class="faq-question" style="padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; color: #333;">투어 코스를 친구와 공유할 수 있나요?</h3>
                <span class="toggle-icon" style="font-size: 20px; color: #4CAF50;">+</span>
            </div>
            <div class="faq-answer" style="padding: 0 20px; max-height: 0; overflow: hidden; transition: all 0.3s;">
                <div style="padding-bottom: 20px; color: #666; line-height: 1.6;">
                    네, 여러 가지 방법으로 공유할 수 있습니다:<br><br>
                    - URL 공유: 생성된 링크를 복사하여 메신저나 이메일로 전송<br>
                    - 카카오톡: 카카오톡 공유 버튼으로 바로 전송<br>
                    - 소셜미디어: 인스타그램, 페이스북 등에 공유<br>
                    - QR코드: QR코드를 생성하여 오프라인에서도 공유 가능<br><br>
                    공유받은 사람은 링크를 통해 동일한 투어 코스를 확인하고 수정할 수 있습니다.
                </div>
            </div>
        </div>
        
        <div class="faq-item" data-category="tour" style="background: #f8f9fa; border-radius: 10px; margin-bottom: 20px; overflow: hidden;">
            <div class="faq-question" style="padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; color: #333;">저장한 투어는 어디서 확인하나요?</h3>
                <span class="toggle-icon" style="font-size: 20px; color: #4CAF50;">+</span>
            </div>
            <div class="faq-answer" style="padding: 0 20px; max-height: 0; overflow: hidden; transition: all 0.3s;">
                <div style="padding-bottom: 20px; color: #666; line-height: 1.6;">
                    투어플래너 페이지 상단의 '저장된 투어' 버튼을 클릭하면 확인할 수 있습니다. 
                    최대 10개까지 저장 가능하며, 각 투어의 이름, 장소 개수, 저장 날짜가 표시됩니다. 
                    저장된 투어는 클릭하여 불러오거나 삭제할 수 있습니다. 
                    로그인하지 않은 경우 브라우저에 저장되므로, 다른 기기에서는 확인할 수 없습니다.
                </div>
            </div>
        </div>
        
        <!-- 계정/회원 -->
        <div class="faq-item" data-category="account" style="background: #f8f9fa; border-radius: 10px; margin-bottom: 20px; overflow: hidden;">
            <div class="faq-question" style="padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; color: #333;">회원가입은 필수인가요?</h3>
                <span class="toggle-icon" style="font-size: 20px; color: #4CAF50;">+</span>
            </div>
            <div class="faq-answer" style="padding: 0 20px; max-height: 0; overflow: hidden; transition: all 0.3s;">
                <div style="padding-bottom: 20px; color: #666; line-height: 1.6;">
                    아니요, 회원가입 없이도 대부분의 기능을 이용할 수 있습니다. 
                    게스트 모드로 투어플래너, 장소 검색, 지도 서비스를 모두 사용할 수 있습니다. 
                    다만 회원가입을 하시면 투어 저장, 리뷰 작성, 즐겨찾기 등의 추가 기능을 이용할 수 있고, 
                    여러 기기에서 동기화하여 사용할 수 있습니다.
                </div>
            </div>
        </div>
        
        <div class="faq-item" data-category="account" style="background: #f8f9fa; border-radius: 10px; margin-bottom: 20px; overflow: hidden;">
            <div class="faq-question" style="padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; color: #333;">소셜 로그인은 어떤 것을 지원하나요?</h3>
                <span class="toggle-icon" style="font-size: 20px; color: #4CAF50;">+</span>
            </div>
            <div class="faq-answer" style="padding: 0 20px; max-height: 0; overflow: hidden; transition: all 0.3s;">
                <div style="padding-bottom: 20px; color: #666; line-height: 1.6;">
                    현재 카카오, 네이버, 구글 계정으로 간편하게 로그인할 수 있습니다. 
                    소셜 로그인을 사용하면 별도의 비밀번호 없이 안전하게 서비스를 이용할 수 있으며, 
                    프로필 정보는 자동으로 연동됩니다. 이메일 회원가입도 가능합니다.
                </div>
            </div>
        </div>
        
        <div class="faq-item" data-category="account" style="background: #f8f9fa; border-radius: 10px; margin-bottom: 20px; overflow: hidden;">
            <div class="faq-question" style="padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; color: #333;">회원 탈퇴는 어떻게 하나요?</h3>
                <span class="toggle-icon" style="font-size: 20px; color: #4CAF50;">+</span>
            </div>
            <div class="faq-answer" style="padding: 0 20px; max-height: 0; overflow: hidden; transition: all 0.3s;">
                <div style="padding-bottom: 20px; color: #666; line-height: 1.6;">
                    마이페이지 > 계정 설정 > 회원 탈퇴 메뉴에서 탈퇴할 수 있습니다. 
                    탈퇴 시 모든 개인정보는 즉시 삭제되며, 작성한 리뷰는 익명으로 처리됩니다. 
                    탈퇴 후 동일한 이메일로 재가입이 가능하지만, 이전 데이터는 복구할 수 없습니다.
                </div>
            </div>
        </div>
        
        <!-- 기타 -->
        <div class="faq-item" data-category="etc" style="background: #f8f9fa; border-radius: 10px; margin-bottom: 20px; overflow: hidden;">
            <div class="faq-question" style="padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; color: #333;">성수동 외 다른 지역도 서비스되나요?</h3>
                <span class="toggle-icon" style="font-size: 20px; color: #4CAF50;">+</span>
            </div>
            <div class="faq-answer" style="padding: 0 20px; max-height: 0; overflow: hidden; transition: all 0.3s;">
                <div style="padding-bottom: 20px; color: #666; line-height: 1.6;">
                    현재는 성수동 지역에 특화된 서비스를 제공하고 있습니다. 
                    성수동의 모든 정보를 가장 깊이 있고 정확하게 제공하는 것이 목표입니다. 
                    서비스가 안정화되면 강남, 홍대, 이태원 등 서울의 다른 핫플레이스로 
                    확대할 계획이 있습니다.
                </div>
            </div>
        </div>
        
        <div class="faq-item" data-category="etc" style="background: #f8f9fa; border-radius: 10px; margin-bottom: 20px; overflow: hidden;">
            <div class="faq-question" style="padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; color: #333;">잘못된 정보를 발견했어요. 어떻게 신고하나요?</h3>
                <span class="toggle-icon" style="font-size: 20px; color: #4CAF50;">+</span>
            </div>
            <div class="faq-answer" style="padding: 0 20px; max-height: 0; overflow: hidden; transition: all 0.3s;">
                <div style="padding-bottom: 20px; color: #666; line-height: 1.6;">
                    각 장소 페이지 하단의 '정보 수정 제안' 버튼을 통해 신고할 수 있습니다. 
                    또는 contact@sungsuya.com으로 이메일을 보내주시면 확인 후 빠르게 수정하겠습니다. 
                    정확한 정보 제공을 위해 사용자님들의 제보를 적극 환영합니다.
                </div>
            </div>
        </div>
        
        <div class="faq-item" data-category="etc" style="background: #f8f9fa; border-radius: 10px; margin-bottom: 20px; overflow: hidden;">
            <div class="faq-question" style="padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; color: #333;">광고나 제휴 문의는 어떻게 하나요?</h3>
                <span class="toggle-icon" style="font-size: 20px; color: #4CAF50;">+</span>
            </div>
            <div class="faq-answer" style="padding: 0 20px; max-height: 0; overflow: hidden; transition: all 0.3s;">
                <div style="padding-bottom: 20px; color: #666; line-height: 1.6;">
                    비즈니스 제휴 및 광고 문의는 business@sungsuya.com으로 연락 주시기 바랍니다. 
                    성수동 지역 상인분들의 홍보, 팝업스토어 등록, 이벤트 프로모션 등 
                    다양한 협업을 환영합니다. 문의 시 사업자 정보와 제안 내용을 함께 보내주시면 
                    빠른 검토가 가능합니다.
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- 추가 도움말 -->
    <div class="help-section" style="margin-top: 60px; padding: 40px; background: #f8f9fa; border-radius: 15px; text-align: center;">
        <h3 style="font-size: 24px; margin-bottom: 20px; color: #333;">찾으시는 답변이 없나요?</h3>
        <p style="font-size: 16px; color: #666; margin-bottom: 30px; line-height: 1.6;">
            더 궁금한 사항이 있으시면 언제든지 문의해주세요.<br>
            빠르고 친절하게 답변드리겠습니다.
        </p>
        <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo home_url('/contact'); ?>" style="background: #4CAF50; color: white; padding: 12px 30px; border-radius: 25px; text-decoration: none; font-size: 16px; transition: all 0.3s;">
                <i class="fas fa-envelope"></i> 문의하기
            </a>
            <a href="mailto:help@sungsuya.com" style="background: white; color: #4CAF50; padding: 12px 30px; border-radius: 25px; text-decoration: none; font-size: 16px; border: 2px solid #4CAF50; transition: all 0.3s;">
                <i class="fas fa-at"></i> 이메일 보내기
            </a>
        </div>
    </div>
    
</div>

<style>
/* FAQ 스타일 */
.tab-button:hover {
    background: #4CAF50 !important;
    color: white !important;
    transform: translateY(-2px);
}

.tab-button.active {
    background: #4CAF50 !important;
    color: white !important;
}

.faq-question:hover {
    background: rgba(76, 175, 80, 0.05);
}

.faq-answer.open {
    max-height: 500px !important;
}

.toggle-icon {
    transition: transform 0.3s;
}

.toggle-icon.rotate {
    transform: rotate(45deg);
}

.help-section a:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* 반응형 */
@media (max-width: 768px) {
    .faq-container {
        padding: 20px 15px;
    }
    
    .page-title {
        font-size: 28px !important;
    }
    
    .faq-tabs {
        gap: 5px !important;
    }
    
    .tab-button {
        padding: 8px 15px !important;
        font-size: 14px !important;
    }
    
    .faq-question h3 {
        font-size: 16px !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // FAQ 아코디언 기능
    const questions = document.querySelectorAll('.faq-question');
    
    questions.forEach(question => {
        question.addEventListener('click', function() {
            const answer = this.nextElementSibling;
            const icon = this.querySelector('.toggle-icon');
            const isOpen = answer.classList.contains('open');
            
            // 모든 답변 닫기
            document.querySelectorAll('.faq-answer').forEach(a => {
                a.classList.remove('open');
                a.style.maxHeight = '0';
            });
            document.querySelectorAll('.toggle-icon').forEach(i => {
                i.classList.remove('rotate');
                i.textContent = '+';
            });
            
            // 클릭한 항목 토글
            if (!isOpen) {
                answer.classList.add('open');
                answer.style.maxHeight = answer.scrollHeight + 'px';
                icon.classList.add('rotate');
                icon.textContent = '×';
            }
        });
    });
    
    // 카테고리 필터링
    const tabButtons = document.querySelectorAll('.tab-button');
    const faqItems = document.querySelectorAll('.faq-item');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            
            // 버튼 활성화 상태 변경
            tabButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // FAQ 아이템 필터링
            faqItems.forEach(item => {
                if (category === 'all' || item.getAttribute('data-category') === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
            
            // 열려있는 답변 닫기
            document.querySelectorAll('.faq-answer').forEach(a => {
                a.classList.remove('open');
                a.style.maxHeight = '0';
            });
            document.querySelectorAll('.toggle-icon').forEach(i => {
                i.classList.remove('rotate');
                i.textContent = '+';
            });
        });
    });
});
</script>

<?php
get_footer();
?>
