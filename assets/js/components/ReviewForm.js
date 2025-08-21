/**
 * 성수야 V2 - 리뷰 작성 폼 컴포넌트
 */

const { useState, useRef } = React;

const ReviewForm = ({ placeId, onSubmit, onCancel }) => {
    // 폼 상태
    const [formData, setFormData] = useState({
        user_name: '',
        user_email: '',
        rating: 0,
        review_text: '',
        images: []
    });
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [hoveredRating, setHoveredRating] = useState(0);
    
    // 파일 입력 ref
    const fileInputRef = useRef(null);

    // 입력 변경 핸들러
    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
        
        // 에러 제거
        if (errors[name]) {
            setErrors(prev => ({
                ...prev,
                [name]: null
            }));
        }
    };

    // 별점 클릭 핸들러
    const handleRatingClick = (rating) => {
        setFormData(prev => ({
            ...prev,
            rating: rating
        }));
        
        if (errors.rating) {
            setErrors(prev => ({
                ...prev,
                rating: null
            }));
        }
    };

    // 이미지 업로드 핸들러
    const handleImageChange = async (e) => {
        const files = Array.from(e.target.files);
        
        // 최대 3개 제한
        if (formData.images.length + files.length > 3) {
            alert('이미지는 최대 3개까지 업로드 가능합니다.');
            return;
        }

        // 이미지 미리보기 및 업로드 준비
        const newImages = await Promise.all(
            files.map(async (file) => {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onloadend = () => {
                        resolve({
                            file,
                            preview: reader.result,
                            name: file.name
                        });
                    };
                    reader.readAsDataURL(file);
                });
            })
        );

        setFormData(prev => ({
            ...prev,
            images: [...prev.images, ...newImages]
        }));
    };

    // 이미지 제거
    const handleImageRemove = (index) => {
        setFormData(prev => ({
            ...prev,
            images: prev.images.filter((_, i) => i !== index)
        }));
    };

    // 폼 검증
    const validateForm = () => {
        const newErrors = {};

        if (!formData.user_name.trim()) {
            newErrors.user_name = '이름을 입력해주세요.';
        }

        if (formData.user_email && !isValidEmail(formData.user_email)) {
            newErrors.user_email = '올바른 이메일 형식이 아닙니다.';
        }

        if (formData.rating === 0) {
            newErrors.rating = '평점을 선택해주세요.';
        }

        if (!formData.review_text.trim()) {
            newErrors.review_text = '리뷰 내용을 입력해주세요.';
        } else if (formData.review_text.length > 1000) {
            newErrors.review_text = '리뷰는 1000자 이내로 작성해주세요.';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    // 이메일 검증
    const isValidEmail = (email) => {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    };

    // reCAPTCHA 토큰 가져오기
    const getRecaptchaToken = async () => {
        if (sungsuyaReview.isLocal) {
            return 'local-development-token';
        }

        if (window.grecaptcha && sungsuyaReview.recaptchaSiteKey) {
            try {
                const token = await window.grecaptcha.execute(
                    sungsuyaReview.recaptchaSiteKey,
                    { action: 'submit_review' }
                );
                return token;
            } catch (error) {
                console.error('reCAPTCHA error:', error);
                return null;
            }
        }

        return null;
    };

    // 폼 제출
    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        setSubmitting(true);

        try {
            // reCAPTCHA 토큰 가져오기
            const recaptchaToken = await getRecaptchaToken();
            if (!recaptchaToken) {
                throw new Error('보안 검증에 실패했습니다.');
            }

            // 이미지 업로드 처리 (실제 구현 시 서버에 업로드)
            const imageUrls = formData.images.map(img => img.preview);

            // API 요청
            const response = await fetch(`${sungsuyaReview.apiUrl}/reviews`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': sungsuyaReview.nonce
                },
                body: JSON.stringify({
                    place_id: placeId,
                    user_name: formData.user_name,
                    user_email: formData.user_email,
                    rating: formData.rating,
                    review_text: formData.review_text,
                    images: imageUrls,
                    recaptcha_token: recaptchaToken
                })
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || '리뷰 저장에 실패했습니다.');
            }

            const result = await response.json();
            
            // 성공 시 콜백
            onSubmit(result.review);

            // 폼 초기화
            setFormData({
                user_name: '',
                user_email: '',
                rating: 0,
                review_text: '',
                images: []
            });

        } catch (error) {
            alert(error.message);
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <form className="review-form" onSubmit={handleSubmit}>
            <h3>리뷰 작성</h3>

            {/* 별점 선택 */}
            <div className="form-group rating-group">
                <label>평점 *</label>
                <div className="rating-input">
                    {[1, 2, 3, 4, 5].map(star => (
                        <button
                            key={star}
                            type="button"
                            className={`star-button ${star <= (hoveredRating || formData.rating) ? 'active' : ''}`}
                            onClick={() => handleRatingClick(star)}
                            onMouseEnter={() => setHoveredRating(star)}
                            onMouseLeave={() => setHoveredRating(0)}
                        >
                            ★
                        </button>
                    ))}
                    <span className="rating-text">
                        {formData.rating > 0 && `${formData.rating}점`}
                    </span>
                </div>
                {errors.rating && <span className="error">{errors.rating}</span>}
            </div>

            {/* 이름 */}
            <div className="form-group">
                <label htmlFor="user_name">이름 *</label>
                <input
                    type="text"
                    id="user_name"
                    name="user_name"
                    value={formData.user_name}
                    onChange={handleChange}
                    placeholder="이름을 입력하세요"
                    maxLength="100"
                />
                {errors.user_name && <span className="error">{errors.user_name}</span>}
            </div>

            {/* 이메일 (선택) */}
            <div className="form-group">
                <label htmlFor="user_email">이메일 (선택)</label>
                <input
                    type="email"
                    id="user_email"
                    name="user_email"
                    value={formData.user_email}
                    onChange={handleChange}
                    placeholder="email@example.com"
                />
                {errors.user_email && <span className="error">{errors.user_email}</span>}
            </div>

            {/* 리뷰 내용 */}
            <div className="form-group">
                <label htmlFor="review_text">리뷰 내용 *</label>
                <textarea
                    id="review_text"
                    name="review_text"
                    value={formData.review_text}
                    onChange={handleChange}
                    placeholder="방문 경험을 공유해주세요"
                    rows="5"
                    maxLength="1000"
                />
                <div className="char-count">
                    {formData.review_text.length} / 1000
                </div>
                {errors.review_text && <span className="error">{errors.review_text}</span>}
            </div>

            {/* 이미지 업로드 */}
            <div className="form-group">
                <label>사진 첨부 (최대 3장)</label>
                <div className="image-upload">
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/*"
                        multiple
                        onChange={handleImageChange}
                        style={{ display: 'none' }}
                    />
                    
                    <div className="image-preview-list">
                        {formData.images.map((image, index) => (
                            <div key={index} className="image-preview">
                                <img src={image.preview} alt={image.name} />
                                <button
                                    type="button"
                                    className="remove-image"
                                    onClick={() => handleImageRemove(index)}
                                >
                                    ×
                                </button>
                            </div>
                        ))}
                        
                        {formData.images.length < 3 && (
                            <button
                                type="button"
                                className="add-image"
                                onClick={() => fileInputRef.current.click()}
                            >
                                + 사진 추가
                            </button>
                        )}
                    </div>
                </div>
            </div>

            {/* 버튼 */}
            <div className="form-actions">
                <button
                    type="button"
                    className="cancel-btn"
                    onClick={onCancel}
                    disabled={submitting}
                >
                    취소
                </button>
                <button
                    type="submit"
                    className="submit-btn"
                    disabled={submitting}
                >
                    {submitting ? '저장 중...' : '리뷰 등록'}
                </button>
            </div>
        </form>
    );
};
