<?php
/**
 * Template Name: 문의하기
 * 
 * 성수야! 문의하기 페이지 - Industrial Heritage 디자인
 */

get_header();
?>

<style>
/* Industrial Heritage 디자인 시스템 */
:root {
    --warm-white: #FAFAF8;
    --charcoal: #2C2C2C;
    --brick-red: #B85450;
    --industrial-green: #4A6741;
    --concrete-grey: #E8E6E1;
    --cafe-latte: #D4A574;
    --steel-blue: #5B7C99;
    --muted-text: #666666;
}

body {
    background: var(--warm-white) !important;
}

/* 히어로 섹션 */
.contact-hero {
    background: linear-gradient(135deg, var(--brick-red) 0%, var(--cafe-latte) 100%);
    padding: 100px 0 80px;
    text-align: center;
    color: white;
    position: relative;
    overflow: hidden;
}

.contact-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

.contact-hero-content {
    position: relative;
    z-index: 1;
}

.contact-title {
    font-family: 'Pretendard', sans-serif;
    font-size: 48px;
    font-weight: 800;
    margin-bottom: 16px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.contact-subtitle {
    font-size: 20px;
    opacity: 0.95;
}

/* 컨테이너 */
.contact-container {
    max-width: 1200px;
    margin: -40px auto 80px;
    padding: 0 20px;
    position: relative;
    z-index: 2;
}

/* 메인 그리드 */
.contact-grid {
    display: block;
    max-width: 800px;
    margin: 0 auto;
}

/* 문의 폼 카드 */
.contact-form-card {
    background: white;
    border-radius: 16px;
    padding: 48px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    border: 1px solid var(--concrete-grey);
    margin-bottom: 40px;
}

.form-title {
    font-family: 'Pretendard', sans-serif;
    font-size: 28px;
    font-weight: 700;
    color: var(--charcoal);
    margin-bottom: 32px;
}

/* 폼 스타일 */
.contact-form {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-label {
    font-weight: 600;
    color: var(--charcoal);
    font-size: 15px;
}

.form-input,
.form-textarea {
    padding: 14px 20px;
    border: 1px solid var(--concrete-grey);
    border-radius: 12px;
    font-size: 16px;
    font-family: 'Pretendard', sans-serif;
    transition: all 0.3s ease;
    background: white;
    color: var(--charcoal);
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--brick-red);
    box-shadow: 0 0 0 3px rgba(184, 84, 80, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 150px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-submit {
    background: var(--brick-red);
    color: white;
    padding: 16px 32px;
    border: none;
    border-radius: 28px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 12px;
}

.form-submit:hover {
    background: #A04A46;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(184, 84, 80, 0.3);
}

/* 정보 카드들 */
.contact-info-section {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.info-card {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    border: 1px solid var(--concrete-grey);
}

.info-card-icon {
    width: 48px;
    height: 48px;
    background: var(--concrete-grey);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 16px;
}

.info-card-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--charcoal);
    margin-bottom: 12px;
}

.info-card-content {
    color: var(--muted-text);
    line-height: 1.6;
}

.info-card-content a {
    color: var(--brick-red);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.info-card-content a:hover {
    text-decoration: underline;
}

/* FAQ 섹션 */
.faq-section {
    margin-top: 80px;
}

.faq-title {
    font-family: 'Pretendard', sans-serif;
    font-size: 32px;
    font-weight: 700;
    color: var(--charcoal);
    text-align: center;
    margin-bottom: 48px;
}

.faq-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

.faq-item {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid var(--concrete-grey);
}

.faq-question {
    font-size: 18px;
    font-weight: 600;
    color: var(--charcoal);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.faq-answer {
    color: var(--muted-text);
    line-height: 1.6;
}

/* 성공 메시지 */
.success-message {
    background: #D4EDDA;
    color: #155724;
    padding: 16px 24px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: none;
    align-items: center;
    gap: 12px;
}

.success-message.show {
    display: flex;
}

/* 반응형 */
@media (max-width: 768px) {
    .contact-form-card {
        padding: 32px 24px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .faq-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .contact-hero {
        padding: 80px 0 60px;
    }
    
    .contact-title {
        font-size: 32px;
    }
    
    .contact-subtitle {
        font-size: 16px;
    }
    
    .contact-form-card {
        padding: 24px 20px;
    }
}
</style>

<!-- 히어로 섹션 -->
<div class="contact-hero">
    <div class="contact-hero-content">
        <h1 class="contact-title">문의하기</h1>
        <p class="contact-subtitle">성수야!에 대한 문의사항을 남겨주세요</p>
    </div>
</div>

<!-- 메인 컨텐츠 -->
<div class="contact-container">
    <div class="contact-grid">
        <!-- 문의 폼 -->
        <div class="contact-form-card">
            <h2 class="form-title">무엇을 도와드릴까요?</h2>
            
            <div class="success-message" id="successMessage">
                <span>✅</span>
                <span>문의가 성공적으로 전송되었습니다. 빠른 시일 내에 답변 드리겠습니다.</span>
            </div>
            
            <form class="contact-form" id="contactForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="name">이름 *</label>
                        <input type="text" id="name" name="name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">이메일 *</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="phone">연락처</label>
                    <input type="tel" id="phone" name="phone" class="form-input" placeholder="010-0000-0000">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="subject">문의 유형 *</label>
                    <select id="subject" name="subject" class="form-input" required>
                        <option value="">선택해주세요</option>
                        <option value="general">일반 문의</option>
                        <option value="place">장소 등록/수정 요청</option>
                        <option value="tour">투어플래너 관련</option>
                        <option value="partnership">제휴/광고 문의</option>
                        <option value="bug">버그 신고</option>
                        <option value="other">기타</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="message">문의 내용 *</label>
                    <textarea id="message" name="message" class="form-textarea" required placeholder="문의하실 내용을 자세히 작성해주세요."></textarea>
                </div>
                
                <button type="submit" class="form-submit">문의 전송</button>
            </form>
        </div>
    </div>
    
    <!-- 이메일 정보 -->
    <div style="text-align: center; margin: 40px 0; color: var(--muted-text);">
        <p style="font-size: 16px;">
            문의사항은 이메일로도 보내실 수 있습니다.<br>
            <a href="mailto:dcclab2022@gmail.com" style="color: var(--brick-red); font-weight: 600; text-decoration: none;">dcclab2022@gmail.com</a>
        </p>
    </div>
    
    <!-- FAQ 섹션 -->
    <div class="faq-section">
        <h2 class="faq-title">자주 묻는 질문</h2>
        <div class="faq-grid">
            <div class="faq-item">
                <div class="faq-question">
                    <span>❓</span>
                    <span>성수야!는 어떤 서비스인가요?</span>
                </div>
                <div class="faq-answer">
                    성수야!는 성수동의 다양한 장소 정보를 제공하고, 나만의 투어 코스를 만들 수 있는 지역 기반 플랫폼입니다.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <span>❓</span>
                    <span>우리 가게도 등록할 수 있나요?</span>
                </div>
                <div class="faq-answer">
                    네, 가능합니다. 문의 유형에서 '장소 등록/수정 요청'을 선택하여 상세 정보를 보내주시면 검토 후 등록해드립니다.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <span>❓</span>
                    <span>투어플래너는 무료인가요?</span>
                </div>
                <div class="faq-answer">
                    네, 투어플래너를 포함한 모든 기본 서비스는 무료로 이용하실 수 있습니다.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <span>❓</span>
                    <span>모바일 앱도 있나요?</span>
                </div>
                <div class="faq-answer">
                    현재 PWA(Progressive Web App)를 지원하여 모바일에서도 앱처럼 사용하실 수 있습니다. 홈 화면에 추가하여 이용해보세요.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 폼 제출 처리
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // 버튼 비활성화
    const submitBtn = this.querySelector('.form-submit');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = '전송 중...';
    submitBtn.disabled = true;
    
    // 폼 데이터 수집
    const formData = new FormData(this);
    formData.append('action', 'submit_contact_form');
    formData.append('nonce', '<?php echo wp_create_nonce('contact_form_nonce'); ?>');
    
    // AJAX 요청
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 성공 메시지 표시
            document.getElementById('successMessage').classList.add('show');
            
            // 폼 초기화
            this.reset();
            
            // 스크롤 상단으로
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // 5초 후 메시지 숨기기
            setTimeout(() => {
                document.getElementById('successMessage').classList.remove('show');
            }, 5000);
        } else {
            // 오류 메시지
            alert(data.data || '전송에 실패했습니다. 다시 시도해주세요.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('네트워크 오류가 발생했습니다. 다시 시도해주세요.');
    })
    .finally(() => {
        // 버튼 활성화
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
});

// 전화번호 포맷팅
document.getElementById('phone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/[^0-9]/g, '');
    if (value.length > 3 && value.length <= 7) {
        value = value.slice(0, 3) + '-' + value.slice(3);
    } else if (value.length > 7) {
        value = value.slice(0, 3) + '-' + value.slice(3, 7) + '-' + value.slice(7, 11);
    }
    e.target.value = value;
});
</script>

<?php get_footer(); ?>
