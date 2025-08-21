<?php
/**
 * 성수야 리뷰 시스템 - 인증 연동
 * 
 * 리뷰 작성 시 로그인 요구 예제
 */

// 장소 상세페이지에서 리뷰 섹션 렌더링
function sungsuya_render_review_section($place_id) {
    ?>
    <div class="review-section" id="review-section">
        <h3>리뷰</h3>
        
        <!-- 리뷰 작성 폼 -->
        <div class="review-form-container">
            <button class="btn-write-review" onclick="handleWriteReview()">
                리뷰 작성하기
            </button>
        </div>
        
        <!-- 리뷰 목록 -->
        <div id="reviews-list" class="reviews-list">
            <!-- 리뷰가 여기에 동적으로 로드됩니다 -->
        </div>
    </div>
    
    <script>
    // 리뷰 작성 버튼 클릭 핸들러
    function handleWriteReview() {
        // 인증 확인 후 리뷰 작성 모달 표시
        window.sungsuyaAuth.requireAuth(() => {
            showReviewModal();
        });
    }
    
    // 리뷰 작성 모달 표시
    function showReviewModal() {
        const modal = document.createElement('div');
        modal.className = 'review-modal';
        modal.innerHTML = `
            <div class="review-modal-content">
                <span class="close" onclick="this.parentElement.parentElement.remove()">&times;</span>
                <h3>리뷰 작성</h3>
                <form id="review-form" onsubmit="submitReview(event)">
                    <div class="rating-input">
                        <label>평점:</label>
                        <div class="star-rating">
                            ${[1,2,3,4,5].map(i => `
                                <span class="star" data-rating="${i}" onclick="setRating(${i})">☆</span>
                            `).join('')}
                        </div>
                    </div>
                    <textarea name="content" placeholder="리뷰를 작성해주세요..." required></textarea>
                    <button type="submit" class="btn-submit">작성 완료</button>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // 평점 설정
    function setRating(rating) {
        document.querySelectorAll('.star').forEach((star, index) => {
            star.textContent = index < rating ? '★' : '☆';
            star.classList.toggle('active', index < rating);
        });
        document.getElementById('review-form').dataset.rating = rating;
    }
    
    // 리뷰 제출
    async function submitReview(event) {
        event.preventDefault();
        
        const form = event.target;
        const rating = parseInt(form.dataset.rating || 0);
        const content = form.content.value;
        
        if (!rating) {
            alert('평점을 선택해주세요.');
            return;
        }
        
        try {
            const response = await fetch('/wp-json/sungsuya/v1/reviews', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${window.sungsuyaAuth.token}`
                },
                body: JSON.stringify({
                    place_id: <?php echo $place_id; ?>,
                    rating: rating,
                    content: content
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('리뷰가 등록되었습니다!');
                document.querySelector('.review-modal').remove();
                loadReviews(); // 리뷰 목록 새로고침
            } else {
                alert(result.message || '리뷰 등록에 실패했습니다.');
            }
        } catch (error) {
            console.error('리뷰 제출 오류:', error);
            alert('리뷰 등록 중 오류가 발생했습니다.');
        }
    }
    
    // 리뷰 목록 로드
    async function loadReviews() {
        try {
            const response = await fetch('/wp-json/sungsuya/v1/reviews?place_id=<?php echo $place_id; ?>');
            const reviews = await response.json();
            
            const reviewsList = document.getElementById('reviews-list');
            
            if (reviews.length === 0) {
                reviewsList.innerHTML = '<p class="no-reviews">아직 작성된 리뷰가 없습니다.</p>';
                return;
            }
            
            reviewsList.innerHTML = reviews.map(review => `
                <div class="review-item">
                    <div class="review-header">
                        <span class="reviewer-name">${review.author_name}</span>
                        <span class="review-rating">${'★'.repeat(review.rating)}${'☆'.repeat(5-review.rating)}</span>
                    </div>
                    <p class="review-content">${review.content}</p>
                    <div class="review-footer">
                        <span class="review-date">${new Date(review.created_at).toLocaleDateString()}</span>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            console.error('리뷰 로드 오류:', error);
        }
    }
    
    // 페이지 로드 시 리뷰 목록 로드
    document.addEventListener('DOMContentLoaded', loadReviews);
    </script>
    
    <style>
    .review-section {
        margin: 40px 0;
        padding: 20px;
        background: #f9f9f9;
        border-radius: 12px;
    }
    
    .btn-write-review {
        background: #FF6B6B;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-write-review:hover {
        background: #ff5252;
        transform: translateY(-2px);
    }
    
    .review-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    }
    
    .review-modal-content {
        background: white;
        padding: 30px;
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
    }
    
    .star-rating {
        font-size: 24px;
        cursor: pointer;
    }
    
    .star {
        color: #ddd;
        transition: color 0.2s;
    }
    
    .star.active {
        color: #FFD700;
    }
    
    .review-item {
        background: white;
        padding: 15px;
        margin: 10px 0;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .reviewer-name {
        font-weight: bold;
    }
    
    .review-rating {
        color: #FFD700;
    }
    
    .review-date {
        color: #999;
        font-size: 14px;
    }
    </style>
    <?php
}
