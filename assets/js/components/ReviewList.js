/**
 * 성수야 V2 - 리뷰 목록 컴포넌트
 */

const { useState } = React;

const ReviewList = ({ reviews, loading, hasMore, onLoadMore }) => {
    const [translatedReviews, setTranslatedReviews] = useState({});
    const [reactions, setReactions] = useState({});

    // 날짜 포맷
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
            const weeks = Math.floor(days / 7);
            return `${weeks}주 전`;
        } else if (days < 365) {
            const months = Math.floor(days / 30);
            return `${months}개월 전`;
        } else {
            return date.toLocaleDateString();
        }
    };

    // 별점 렌더링
    const renderStars = (rating) => {
        return (
            <span className="stars">
                {[1, 2, 3, 4, 5].map(star => (
                    <span
                        key={star}
                        className={`star ${star <= rating ? 'full' : 'empty'}`}
                    >
                        {star <= rating ? '★' : '☆'}
                    </span>
                ))}
            </span>
        );
    };

    // 번역 토글
    const handleTranslate = async (reviewId) => {
        if (translatedReviews[reviewId]) {
            // 이미 번역된 경우 토글
            setTranslatedReviews(prev => ({
                ...prev,
                [reviewId]: null
            }));
            return;
        }

        try {
            const response = await fetch(`${sungsuyaReview.apiUrl}/reviews/translate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': sungsuyaReview.nonce
                },
                body: JSON.stringify({
                    review_id: reviewId
                })
            });

            if (response.ok) {
                const data = await response.json();
                setTranslatedReviews(prev => ({
                    ...prev,
                    [reviewId]: data.translated
                }));
            }
        } catch (error) {
            console.error('Translation failed:', error);
        }
    };

    // 반응 추가
    const handleReaction = async (reviewId, reactionType) => {
        try {
            const response = await fetch(
                `${sungsuyaReview.apiUrl}/reviews/${reviewId}/reaction`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': sungsuyaReview.nonce
                    },
                    body: JSON.stringify({
                        reaction_type: reactionType
                    })
                }
            );

            if (response.ok) {
                const data = await response.json();
                
                // 반응 상태 업데이트
                setReactions(prev => ({
                    ...prev,
                    [reviewId]: {
                        ...prev[reviewId],
                        [reactionType]: data.action === 'added'
                    }
                }));

                // 리뷰의 좋아요 수 업데이트 (부모 컴포넌트에서 처리하는 것이 더 좋음)
                // 여기서는 UI만 업데이트
            }
        } catch (error) {
            console.error('Reaction failed:', error);
        }
    };

    // 리뷰 신고
    const handleReport = async (reviewId) => {
        const reason = prompt('신고 사유를 선택해주세요:\n1. 스팸\n2. 부적절한 내용\n3. 허위 정보\n4. 기타');
        
        if (!reason) return;

        const reasonMap = {
            '1': 'spam',
            '2': 'inappropriate',
            '3': 'fake',
            '4': 'other'
        };

        try {
            const response = await fetch(
                `${sungsuyaReview.apiUrl}/reviews/${reviewId}/report`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': sungsuyaReview.nonce
                    },
                    body: JSON.stringify({
                        reason: reasonMap[reason] || 'other'
                    })
                }
            );

            if (response.ok) {
                alert('신고가 접수되었습니다.');
            }
        } catch (error) {
            console.error('Report failed:', error);
        }
    };

    // 언어 표시
    const getLanguageLabel = (lang) => {
        const labels = {
            ko: '한국어',
            en: 'English',
            ja: '日本語',
            zh: '中文',
            es: 'Español'
        };
        return labels[lang] || lang;
    };

    if (loading && reviews.length === 0) {
        return (
            <div className="reviews-loading">
                <div className="spinner"></div>
                <p>리뷰를 불러오는 중...</p>
            </div>
        );
    }

    if (!loading && reviews.length === 0) {
        return (
            <div className="reviews-empty">
                <p>아직 작성된 리뷰가 없습니다.</p>
                <p>첫 번째 리뷰를 작성해보세요!</p>
            </div>
        );
    }

    return (
        <div className="reviews-list">
            {reviews.map(review => (
                <div key={review.id} className="review-card">
                    <div className="review-header">
                        <div className="review-author">
                            <span className="author-name">{review.user_name}</span>
                            <span className="review-date">{formatDate(review.created_at)}</span>
                            {review.language !== 'ko' && (
                                <span className="review-language">
                                    {getLanguageLabel(review.language)}
                                </span>
                            )}
                        </div>
                        <div className="review-rating">
                            {renderStars(review.rating)}
                        </div>
                    </div>

                    <div className="review-content">
                        <p className="review-text">
                            {translatedReviews[review.id] || review.review_text}
                        </p>
                        
                        {/* 번역 버튼 */}
                        {review.original_language !== sungsuyaReview.currentLang && (
                            <button
                                className="translate-btn"
                                onClick={() => handleTranslate(review.id)}
                            >
                                {translatedReviews[review.id] 
                                    ? sungsuyaReview.translations.originalText 
                                    : sungsuyaReview.translations.translate}
                            </button>
                        )}
                    </div>

                    {/* 이미지 */}
                    {review.images && review.images.length > 0 && (
                        <div className="review-images">
                            {review.images.map((image, index) => (
                                <img
                                    key={index}
                                    src={image}
                                    alt={`리뷰 이미지 ${index + 1}`}
                                    onClick={() => {
                                        // 이미지 확대 모달 (추후 구현)
                                        window.open(image, '_blank');
                                    }}
                                />
                            ))}
                        </div>
                    )}

                    {/* 반응 버튼 */}
                    <div className="review-actions">
                        <button
                            className={`reaction-btn ${reactions[review.id]?.like ? 'active' : ''}`}
                            onClick={() => handleReaction(review.id, 'like')}
                        >
                            👍 도움됨 {review.likes_count > 0 && `(${review.likes_count})`}
                        </button>
                        
                        <button
                            className="report-btn"
                            onClick={() => handleReport(review.id)}
                        >
                            🚨 신고
                        </button>
                    </div>
                </div>
            ))}

            {/* 더 보기 버튼 */}
            {hasMore && (
                <div className="load-more-wrapper">
                    <button
                        className="load-more-btn"
                        onClick={onLoadMore}
                        disabled={loading}
                    >
                        {loading ? '로딩 중...' : sungsuyaReview.translations.loadMore}
                    </button>
                </div>
            )}
        </div>
    );
};
