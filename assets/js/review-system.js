/**
 * 성수야 리뷰 시스템
 * React 기반 댓글 및 SNS 공유 기능
 */

(function() {
    'use strict';
    
    console.log('Review system script loading...');
    
    // WordPress React 사용
    const { createElement: h, useState, useEffect, useRef } = window.React || {};
    
    if (!window.React || !window.ReactDOM) {
        console.error('React or ReactDOM not loaded');
        return;
    }
    
    console.log('React version:', React.version);
    
    // 메인 리뷰 시스템 컴포넌트
    const ReviewSystem = ({ placeId }) => {
        console.log('ReviewSystem component rendering, placeId:', placeId);
        
        const [reviews, setReviews] = useState([]);
        const [stats, setStats] = useState(null);
        const [loading, setLoading] = useState(true);
        const [currentPage, setCurrentPage] = useState(1);
        
        // 리뷰 목록 로드
        useEffect(() => {
            loadReviews();
            loadStats();
        }, [placeId]);
        
        const loadReviews = async () => {
            setLoading(true);
            try {
                const response = await fetch(
                    `/wp-json/sungsuya/v1/reviews/${placeId}?page=${currentPage}`
                );
                const data = await response.json();
                console.log('Reviews loaded:', data);
                setReviews(Array.isArray(data) ? data : []);
            } catch (error) {
                console.error('리뷰 로드 실패:', error);
                setReviews([]);
            } finally {
                setLoading(false);
            }
        };
        
        const loadStats = async () => {
            try {
                const response = await fetch(`/wp-json/sungsuya/v1/reviews/stats/${placeId}`);
                const data = await response.json();
                console.log('Stats loaded:', data);
                setStats(data);
            } catch (error) {
                console.error('통계 로드 실패:', error);
            }
        };
        
        const handleNewReview = (newReview) => {
            loadReviews();
            loadStats();
        };
        
        return h('div', { className: 'review-system' },
            h('h2', { style: { fontSize: '32px', marginBottom: '30px', textAlign: 'center' } }, '💬 리뷰 & 평점'),
            stats && h(ReviewSummary, { stats }),
            h(ReviewForm, { placeId, onSubmit: handleNewReview }),
            h(ReviewList, { 
                reviews, 
                loading,
                onLike: loadReviews,
                onReport: loadReviews 
            })
        );
    };
    
    // 리뷰 요약 컴포넌트
    const ReviewSummary = ({ stats }) => {
        const avgRating = parseFloat(stats.average_rating) || 0;
        const totalReviews = parseInt(stats.total_reviews) || 0;
        
        return h('div', { className: 'review-summary' },
            h('div', { className: 'rating-overview' },
                h('div', { className: 'average-rating' }, avgRating.toFixed(1)),
                h('div', { className: 'rating-stars' }, 
                    renderStars(avgRating)
                ),
                h('div', { className: 'total-reviews' }, 
                    `(${totalReviews}개의 리뷰)`
                )
            ),
            totalReviews > 0 && h('div', { className: 'rating-distribution' },
                [5, 4, 3, 2, 1].map(rating => 
                    h('div', { key: rating, className: 'rating-bar' },
                        h('span', { className: 'rating-label' }, `${rating}점`),
                        h('div', { className: 'bar-container' },
                            h('div', { 
                                className: 'bar-fill',
                                style: { width: `${getPercentage(stats[`${numberToWord(rating)}_stars`], totalReviews)}%` }
                            })
                        ),
                        h('span', { className: 'rating-count' }, 
                            stats[`${numberToWord(rating)}_stars`] || 0
                        )
                    )
                )
            )
        );
    };
    
    // 리뷰 작성 폼 컴포넌트
    const ReviewForm = ({ placeId, onSubmit }) => {
        const [rating, setRating] = useState(5);
        const [userName, setUserName] = useState('');
        const [userEmail, setUserEmail] = useState('');
        const [reviewText, setReviewText] = useState('');
        const [submitting, setSubmitting] = useState(false);
        const [hoveredRating, setHoveredRating] = useState(0);
        const [uploadedImages, setUploadedImages] = useState([]);
        const [uploading, setUploading] = useState(false);
        
        const handleImageUpload = async (e) => {
            const files = e.target.files;
            if (!files || files.length === 0) return;
            
            // 최대 3개 제한
            if (uploadedImages.length + files.length > 3) {
                alert('최대 3개까지 사진을 업로드할 수 있습니다.');
                return;
            }
            
            setUploading(true);
            
            for (let i = 0; i < files.length && uploadedImages.length + i < 3; i++) {
                const file = files[i];
                const formData = new FormData();
                formData.append('image', file);
                
                try {
                    const response = await fetch('/wp-json/sungsuya/v1/reviews/upload-image', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        setUploadedImages(prev => [...prev, data]);
                    } else {
                        alert('이미지 업로드에 실패했습니다.');
                    }
                } catch (error) {
                    console.error('업로드 오류:', error);
                    alert('이미지 업로드 중 오류가 발생했습니다.');
                }
            }
            
            setUploading(false);
            e.target.value = ''; // 입력 초기화
        };
        
        const removeImage = (index) => {
            setUploadedImages(prev => prev.filter((_, i) => i !== index));
        };
        
        const handleSubmit = async (e) => {
            e.preventDefault();
            
            if (!userName.trim()) {
                alert('이름을 입력해주세요.');
                return;
            }
            
            setSubmitting(true);
            
            try {
                const response = await fetch('/wp-json/sungsuya/v1/reviews', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        place_id: parseInt(placeId),
                        user_name: userName,
                        user_email: userEmail,
                        rating: rating,
                        review_text: reviewText,
                        images: uploadedImages.map(img => img.attachment_id),
                        recaptcha_token: '' // 개발 환경에서는 비워둠
                    })
                });
                
                const data = await response.json();
                console.log('Review submission response:', data);
                
                if (response.ok) {
                    alert('리뷰가 등록되었습니다!');
                    // 폼 초기화
                    setRating(5);
                    setUserName('');
                    setUserEmail('');
                    setReviewText('');
                    setUploadedImages([]);
                    onSubmit(data);
                } else {
                    alert(data.message || '리뷰 등록에 실패했습니다.');
                }
            } catch (error) {
                console.error('리뷰 제출 실패:', error);
                alert('오류가 발생했습니다. 다시 시도해주세요.');
            } finally {
                setSubmitting(false);
            }
        };
        
        return h('form', { className: 'review-form', onSubmit: handleSubmit },
            h('h3', null, '리뷰 작성'),
            
            // 별점 선택
            h('div', { className: 'rating-input' },
                h('label', null, '평점'),
                h('div', { className: 'star-rating-input' },
                    [1, 2, 3, 4, 5].map(star =>
                        h('span', {
                            key: star,
                            className: `star ${star <= (hoveredRating || rating) ? 'filled' : ''}`,
                            onClick: () => setRating(star),
                            onMouseEnter: () => setHoveredRating(star),
                            onMouseLeave: () => setHoveredRating(0)
                        }, '★')
                    )
                )
            ),
            
            // 작성자 정보
            h('div', { className: 'form-group' },
                h('input', {
                    type: 'text',
                    placeholder: '이름 *',
                    value: userName,
                    onChange: (e) => setUserName(e.target.value),
                    required: true,
                    maxLength: 50
                })
            ),
            
            h('div', { className: 'form-group' },
                h('input', {
                    type: 'email',
                    placeholder: '이메일 (선택)',
                    value: userEmail,
                    onChange: (e) => setUserEmail(e.target.value)
                })
            ),
            
            // 리뷰 내용
            h('div', { className: 'form-group' },
                h('textarea', {
                    placeholder: '이 장소에 대한 경험을 공유해주세요',
                    value: reviewText,
                    onChange: (e) => setReviewText(e.target.value),
                    rows: 4,
                    maxLength: 1000
                })
            ),
            
            // 이미지 업로드 섹션
            h('div', { className: 'form-group image-upload-section' },
                h('label', null, '사진 추가 (최대 3장)'),
                h('div', { className: 'image-upload-area' },
                    // 업로드된 이미지 미리보기
                    uploadedImages.length > 0 && h('div', { className: 'uploaded-images' },
                        uploadedImages.map((img, index) => 
                            h('div', { key: index, className: 'uploaded-image' },
                                h('img', { 
                                    src: img.urls.thumbnail,
                                    alt: `업로드 이미지 ${index + 1}`
                                }),
                                h('button', {
                                    type: 'button',
                                    className: 'remove-image',
                                    onClick: () => removeImage(index)
                                }, '×')
                            )
                        )
                    ),
                    
                    // 업로드 버튼
                    uploadedImages.length < 3 && h('label', { className: 'upload-button' },
                        h('input', {
                            type: 'file',
                            accept: 'image/*',
                            multiple: true,
                            onChange: handleImageUpload,
                            disabled: uploading,
                            style: { display: 'none' },
                            id: 'review-image-upload'
                        }),
                        h('div', { 
                            className: 'upload-btn-content',
                            onClick: () => {
                                console.log('Upload button clicked');
                                const input = document.getElementById('review-image-upload');
                                if (input) {
                                    console.log('Triggering file input click');
                                    input.click();
                                } else {
                                    console.error('File input not found');
                                }
                            }
                        },
                            uploading ? '업로드 중...' : '📷 사진 추가'
                        )
                    )
                )
            ),
            
            // 제출 버튼
            h('button', {
                type: 'submit',
                className: 'submit-button',
                disabled: submitting
            }, submitting ? '등록 중...' : '리뷰 등록')
        );
    };
    
    // 리뷰 목록 컴포넌트
    const ReviewList = ({ reviews, loading, onLike, onReport }) => {
        if (loading) {
            return h('div', { className: 'loading' }, '리뷰를 불러오는 중...');
        }
        
        if (!reviews || reviews.length === 0) {
            return h('div', { className: 'no-reviews' }, 
                '아직 리뷰가 없습니다. 첫 번째 리뷰를 작성해보세요!'
            );
        }
        
        return h('div', { className: 'review-list' },
            reviews.map(review => 
                h(ReviewItem, { 
                    key: review.id, 
                    review,
                    onLike,
                    onReport
                })
            )
        );
    };
    
    // 개별 리뷰 아이템 컴포넌트
    const ReviewItem = ({ review, onLike, onReport }) => {
        const handleLike = async () => {
            try {
                const response = await fetch(`/wp-json/sungsuya/v1/reviews/${review.id}/like`, {
                    method: 'POST'
                });
                
                if (response.ok) {
                    onLike();
                } else {
                    const data = await response.json();
                    alert(data.message || '이미 좋아요를 누르셨습니다.');
                }
            } catch (error) {
                console.error('좋아요 실패:', error);
            }
        };
        
        const handleReport = async () => {
            if (!confirm('이 리뷰를 신고하시겠습니까?')) {
                return;
            }
            
            try {
                const response = await fetch(`/wp-json/sungsuya/v1/reviews/${review.id}/report`, {
                    method: 'POST'
                });
                
                if (response.ok) {
                    alert('신고가 접수되었습니다.');
                    onReport();
                } else {
                    const data = await response.json();
                    alert(data.message || '이미 신고하셨습니다.');
                }
            } catch (error) {
                console.error('신고 실패:', error);
            }
        };
        
        return h('div', { className: 'review-item' },
            h('div', { className: 'review-header' },
                h('div', { className: 'reviewer-info' },
                    h('strong', null, review.user_name),
                    h('span', { className: 'review-date' }, 
                        formatDate(review.created_at)
                    )
                ),
                h('div', { className: 'review-rating' },
                    renderStars(parseInt(review.rating))
                )
            ),
            
            review.review_text && h('div', { className: 'review-text' },
                h('p', null, review.review_text)
            ),
            
            // 리뷰 이미지
            review.images && review.images.length > 0 && h('div', { className: 'review-images' },
                review.images.map((img, index) => 
                    h('img', {
                        key: index,
                        src: img.image_url,
                        alt: `리뷰 이미지 ${index + 1}`,
                        onClick: () => window.open(img.image_url, '_blank')
                    })
                )
            ),
            
            h('div', { className: 'review-actions' },
                h('button', {
                    className: `action-button ${review.user_liked ? 'liked' : ''}`,
                    onClick: handleLike
                }, `👍 ${review.likes_count || 0}`),
                
                review.can_report !== false && h('button', {
                    className: 'action-button report',
                    onClick: handleReport
                }, '🚨 신고')
            )
        );
    };
    
    // 유틸리티 함수들
    const renderStars = (rating) => {
        const stars = [];
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        
        for (let i = 0; i < 5; i++) {
            if (i < fullStars) {
                stars.push('★');
            } else if (i === fullStars && hasHalfStar) {
                stars.push('☆');
            } else {
                stars.push('☆');
            }
        }
        
        return stars.join('');
    };
    
    const numberToWord = (num) => {
        const words = ['', 'one', 'two', 'three', 'four', 'five'];
        return words[num] || '';
    };
    
    const getPercentage = (count, total) => {
        if (!total) return 0;
        return Math.round((count / total) * 100);
    };
    
    const formatDate = (dateString) => {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        
        if (days === 0) {
            return '오늘';
        } else if (days === 1) {
            return '어제';
        } else if (days < 7) {
            return `${days}일 전`;
        } else if (days < 30) {
            return `${Math.floor(days / 7)}주 전`;
        } else if (days < 365) {
            return `${Math.floor(days / 30)}개월 전`;
        } else {
            return `${Math.floor(days / 365)}년 전`;
        }
    };
    
    // 초기화 함수
    const initReviewSystem = () => {
        console.log('Initializing review system...');
        const container = document.getElementById('place-review-system');
        if (container) {
            const placeId = container.getAttribute('data-place-id');
            console.log('Found container with place ID:', placeId);
            
            // React 18 방식으로 렌더링
            if (ReactDOM.createRoot) {
                console.log('Using ReactDOM.createRoot (React 18)');
                // 이미 root가 있는지 확인
                if (!container._reactRoot) {
                    const root = ReactDOM.createRoot(container);
                    container._reactRoot = root;
                    root.render(h(ReviewSystem, { placeId }));
                } else {
                    // 이미 있으면 재렌더링
                    container._reactRoot.render(h(ReviewSystem, { placeId }));
                }
            } else if (ReactDOM.render) {
                // React 17 이하 폴백
                console.log('Using ReactDOM.render (React 17)');
                ReactDOM.render(
                    h(ReviewSystem, { placeId }), 
                    container
                );
            } else {
                console.error('No render method available');
            }
        } else {
            console.log('Review container not found');
        }
    };
    
    // 다양한 방법으로 초기화 시도
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReviewSystem);
    } else {
        // 이미 로드됨
        setTimeout(initReviewSystem, 100);
    }
    
    // 글로벌 노출 (디버깅용)
    window.SungsuyaReviewSystem = {
        init: initReviewSystem,
        ReviewSystem
    };
    
})();
