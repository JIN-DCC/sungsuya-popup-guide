/**
 * 성수야 V2 - 리뷰 시스템 메인 컴포넌트
 */

// React와 hooks
const { useState, useEffect, useCallback } = React;

// 메인 리뷰 시스템 컴포넌트
const ReviewSystem = ({ placeId }) => {
    // 상태 관리
    const [reviews, setReviews] = useState([]);
    const [loading, setLoading] = useState(true);
    const [currentLang, setCurrentLang] = useState('all');
    const [currentPage, setCurrentPage] = useState(1);
    const [stats, setStats] = useState({
        total: 0,
        avgRating: 0,
        pages: 1
    });
    const [showForm, setShowForm] = useState(false);

    // GTranslate 현재 언어와 동기화
    useEffect(() => {
        const syncWithGTranslate = () => {
            const urlParams = new URLSearchParams(window.location.search);
            const gtranslateLang = urlParams.get('lang') || 'ko';
            
            // 사용자가 'all' 선택하지 않은 경우에만 자동 동기화
            if (currentLang !== 'all') {
                setCurrentLang(gtranslateLang);
            }
        };

        // GTranslate 언어 변경 감지
        const observer = new MutationObserver(() => {
            syncWithGTranslate();
        });
        
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['lang']
        });

        // URL 변경 감지
        window.addEventListener('popstate', syncWithGTranslate);
        
        return () => {
            observer.disconnect();
            window.removeEventListener('popstate', syncWithGTranslate);
        };
    }, [currentLang]);

    // 리뷰 목록 불러오기
    const fetchReviews = useCallback(async (page = 1, append = false) => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                page: page,
                per_page: 10,
                language: currentLang
            });

            const response = await fetch(
                `${sungsuyaReview.apiUrl}/reviews/${placeId}?${params}`,
                {
                    headers: {
                        'X-WP-Nonce': sungsuyaReview.nonce
                    }
                }
            );

            if (!response.ok) throw new Error('Failed to fetch reviews');

            const data = await response.json();
            
            if (append) {
                setReviews(prev => [...prev, ...data.reviews]);
            } else {
                setReviews(data.reviews);
            }
            
            setStats({
                total: data.total,
                avgRating: data.avg_rating,
                pages: data.pages
            });
            setCurrentPage(data.current_page);
        } catch (error) {
            console.error('Failed to fetch reviews:', error);
        } finally {
            setLoading(false);
        }
    }, [placeId, currentLang]);

    // 초기 로드 및 언어 변경 시 리로드
    useEffect(() => {
        fetchReviews(1);
    }, [fetchReviews]);

    // 새 리뷰 추가 핸들러
    const handleNewReview = (newReview) => {
        // 낙관적 업데이트
        setReviews(prev => [newReview, ...prev]);
        setStats(prev => ({
            ...prev,
            total: prev.total + 1,
            avgRating: ((prev.avgRating * prev.total) + newReview.rating) / (prev.total + 1)
        }));
        setShowForm(false);
    };

    // 더 보기 핸들러
    const handleLoadMore = () => {
        if (currentPage < stats.pages) {
            fetchReviews(currentPage + 1, true);
        }
    };

    // 별점 렌더링
    const renderStars = (rating) => {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
        
        return (
            <span className="stars">
                {[...Array(fullStars)].map((_, i) => (
                    <span key={`full-${i}`} className="star full">★</span>
                ))}
                {hasHalfStar && <span className="star half">★</span>}
                {[...Array(emptyStars)].map((_, i) => (
                    <span key={`empty-${i}`} className="star empty">☆</span>
                ))}
            </span>
        );
    };

    return (
        <div className="review-system">
            {/* 평점 요약 */}
            <div className="rating-summary">
                <div className="avg-rating">
                    <span className="number">{stats.avgRating.toFixed(1)}</span>
                    <div className="stars-wrapper">
                        {renderStars(stats.avgRating)}
                    </div>
                    <span className="total">({stats.total}개 리뷰)</span>
                </div>
                
                <button 
                    className="write-review-btn"
                    onClick={() => setShowForm(!showForm)}
                >
                    {sungsuyaReview.translations.writeReview}
                </button>
            </div>

            {/* SNS 공유 */}
            <ShareButtons placeData={{
                id: placeId,
                title: document.querySelector('h1')?.textContent || '',
                url: window.location.href
            }} />

            {/* 리뷰 작성 폼 */}
            {showForm && (
                <ReviewForm 
                    placeId={placeId} 
                    onSubmit={handleNewReview}
                    onCancel={() => setShowForm(false)}
                />
            )}

            {/* 언어 필터 */}
            <div className="language-filter">
                <button 
                    className={currentLang === 'all' ? 'active' : ''}
                    onClick={() => setCurrentLang('all')}
                >
                    전체
                </button>
                <button 
                    className={currentLang === 'ko' ? 'active' : ''}
                    onClick={() => setCurrentLang('ko')}
                    data-lang="ko"
                >
                    한국어
                </button>
                <button 
                    className={currentLang === 'en' ? 'active' : ''}
                    onClick={() => setCurrentLang('en')}
                    data-lang="en"
                >
                    English
                </button>
                <button 
                    className={currentLang === 'zh' ? 'active' : ''}
                    onClick={() => setCurrentLang('zh')}
                    data-lang="zh"
                >
                    中文
                </button>
                <button 
                    className={currentLang === 'ja' ? 'active' : ''}
                    onClick={() => setCurrentLang('ja')}
                    data-lang="ja"
                >
                    日本語
                </button>
            </div>

            {/* 리뷰 목록 */}
            <ReviewList 
                reviews={reviews} 
                loading={loading}
                hasMore={currentPage < stats.pages}
                onLoadMore={handleLoadMore}
            />
        </div>
    );
};

// DOM에 마운트
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('place-review-system');
    if (container) {
        const placeId = container.dataset.placeId || sungsuyaReview.placeId;
        ReactDOM.render(<ReviewSystem placeId={placeId} />, container);
    }
});
