/**
 * 성수야 V2 - SNS 공유 버튼 컴포넌트 (Font Awesome 버전)
 * 
 * @version 2.1.0
 * @description Font Awesome 아이콘을 사용한 고품질 SNS 공유 버튼
 */

(function() {
    const { useState } = React;
    const e = React.createElement;

    const ShareButtons = ({ placeData }) => {
        const [showMoreOptions, setShowMoreOptions] = useState(false);
        const [showToast, setShowToast] = useState(false);
        const [toastMessage, setToastMessage] = useState('');
        
        // 현재 URL과 공유 정보
        const currentUrl = placeData.url || window.location.href;
        const title = placeData.title || document.title;
        const description = `성수동 핫플 "${title}" 방문했어요! 📍`;
        
        // 해시태그
        const hashtags = ['성수동', 'SeoulTravel', '서울여행', 'Seongsu', '성수동핫플'];
        const hashtagString = hashtags.join(',');

        // 토스트 메시지 표시
        const showToastMessage = (message) => {
            setToastMessage(message);
            setShowToast(true);
            setTimeout(() => setShowToast(false), 3000);
        };

        // 공유 핸들러들
        const shareHandlers = {
            // 카카오톡
            kakao: () => {
                if (window.Kakao && window.Kakao.isInitialized()) {
                    Kakao.Share.sendDefault({
                        objectType: 'feed',
                        content: {
                            title: title,
                            description: description,
                            imageUrl: placeData.image || '',
                            link: { 
                                mobileWebUrl: currentUrl, 
                                webUrl: currentUrl 
                            }
                        }
                    });
                } else {
                    // 카카오 SDK가 없으면 링크 복사
                    navigator.clipboard.writeText(currentUrl).then(() => {
                        showToastMessage('링크가 복사되었습니다! 카카오톡에서 붙여넣기해주세요.');
                    });
                }
            },

            // 인스타그램
            instagram: () => {
                navigator.clipboard.writeText(currentUrl).then(() => {
                    showToastMessage('링크가 복사되었습니다! 인스타그램 스토리나 DM에 붙여넣기해주세요.');
                });
            },

            // 페이스북
            facebook: () => {
                const fbUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl)}&quote=${encodeURIComponent(description)}`;
                window.open(fbUrl, '_blank', 'width=600,height=400');
            },

            // X (트위터)
            twitter: () => {
                const twitterUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(description)}&url=${encodeURIComponent(currentUrl)}&hashtags=${hashtagString}`;
                window.open(twitterUrl, '_blank', 'width=600,height=400');
            },

            // WhatsApp
            whatsapp: () => {
                const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(description + ' ' + currentUrl)}`;
                window.open(whatsappUrl, '_blank');
            },

            // 텔레그램
            telegram: () => {
                const telegramUrl = `https://t.me/share/url?url=${encodeURIComponent(currentUrl)}&text=${encodeURIComponent(description)}`;
                window.open(telegramUrl, '_blank');
            },

            // 네이버 블로그
            naver: () => {
                const naverUrl = `https://share.naver.com/web/shareView?url=${encodeURIComponent(currentUrl)}&title=${encodeURIComponent(title)}`;
                window.open(naverUrl, '_blank', 'width=500,height=600');
            },

            // 샤오홍슈
            xiaohongshu: () => {
                navigator.clipboard.writeText(currentUrl).then(() => {
                    showToastMessage('링크가 복사되었습니다! 샤오홍슈 앱에서 공유해주세요.');
                });
            },

            // 웨이보
            weibo: () => {
                const weiboUrl = `https://service.weibo.com/share/share.php?url=${encodeURIComponent(currentUrl)}&title=${encodeURIComponent(description)}`;
                window.open(weiboUrl, '_blank');
            },

            // LINE
            line: () => {
                const lineUrl = `https://social-plugins.line.me/lineit/share?url=${encodeURIComponent(currentUrl)}`;
                window.open(lineUrl, '_blank');
            },

            // 링크 복사
            copyLink: async () => {
                try {
                    await navigator.clipboard.writeText(currentUrl);
                    showToastMessage('링크가 복사되었습니다!');
                } catch (err) {
                    // Fallback
                    const textArea = document.createElement('textarea');
                    textArea.value = currentUrl;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showToastMessage('링크가 복사되었습니다!');
                }
            }
        };

        // 스타일 정의
        const styles = {
            container: {
                padding: '10px',
                maxWidth: '450px',
                margin: '0 auto'
            },
            shareGrid: {
                display: 'grid',
                gridTemplateColumns: 'repeat(5, 1fr)',
                gap: '20px',
                marginBottom: '20px'
            },
            shareButton: {
                width: '65px',
                height: '65px',
                border: 'none',
                borderRadius: '50%',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                transition: 'all 0.3s ease',
                position: 'relative',
                overflow: 'hidden',
                boxShadow: '0 2px 15px rgba(0,0,0,0.1)'
            },
            iconStyle: {
                fontSize: '28px',
                color: 'white'
            },
            toggleButton: {
                background: 'none',
                border: '1px solid #e5e7eb',
                color: '#6B7280',
                cursor: 'pointer',
                fontSize: '14px',
                padding: '8px 20px',
                borderRadius: '25px',
                transition: 'all 0.2s',
                fontWeight: '500'
            },
            moreOptions: {
                marginTop: '15px',
                display: 'grid',
                gridTemplateColumns: 'repeat(5, 1fr)',
                gap: '20px'
            },
            toast: {
                position: 'fixed',
                bottom: '20px',
                left: '50%',
                transform: 'translateX(-50%)',
                background: '#333',
                color: 'white',
                padding: '12px 24px',
                borderRadius: '25px',
                boxShadow: '0 4px 20px rgba(0,0,0,0.2)',
                zIndex: 1000,
                animation: 'slideUp 0.3s ease-out'
            },
            tooltip: {
                position: 'absolute',
                bottom: '-35px',
                left: '50%',
                transform: 'translateX(-50%)',
                background: '#333',
                color: 'white',
                padding: '4px 10px',
                borderRadius: '4px',
                fontSize: '12px',
                whiteSpace: 'nowrap',
                opacity: 0,
                pointerEvents: 'none',
                transition: 'opacity 0.3s'
            }
        };

        // 버튼 설정
        const mainButtons = [
            { id: 'kakao', icon: 'fa-solid fa-comment', bg: '#FEE500', color: '#3C1E1E', name: '카카오톡' },
            { id: 'facebook', icon: 'fa-brands fa-facebook-f', bg: '#1877f2', color: 'white', name: '페이스북' },
            { id: 'line', icon: 'fa-brands fa-line', bg: '#00B900', color: 'white', name: '라인' },
            { id: 'twitter', icon: 'fa-brands fa-x-twitter', bg: '#000000', color: 'white', name: 'X' },
            { id: 'instagram', icon: 'fa-brands fa-instagram', bg: '#E1306C', color: 'white', name: '인스타그램' },
            { id: 'copyLink', icon: 'fa-solid fa-link', bg: '#6c757d', color: 'white', name: '링크 복사' }
        ];

        const extraButtons = [
            { id: 'whatsapp', icon: 'fa-brands fa-whatsapp', bg: '#25D366', color: 'white', name: '왓츠앱' },
            { id: 'telegram', icon: 'fa-brands fa-telegram', bg: '#0088cc', color: 'white', name: '텔레그램' },
            { id: 'naver', icon: '', bg: '#03C75A', color: 'white', name: '네이버', text: 'N' },
            { id: 'weibo', icon: 'fa-brands fa-weibo', bg: '#E6162D', color: 'white', name: '웨이보' },
            { id: 'xiaohongshu', icon: '', bg: '#FF2442', color: 'white', name: '샤오홍슈', text: '小' }
        ];

        // 버튼 렌더링 함수
        const renderButton = (btn) => {
            return e('button', {
                key: btn.id,
                onClick: shareHandlers[btn.id],
                style: { 
                    ...styles.shareButton, 
                    background: btn.id === 'instagram' ? 
                        'linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%)' : 
                        btn.bg,
                    color: btn.color
                },
                title: btn.name,
                className: 'share-btn',
                onMouseEnter: (e) => {
                    e.currentTarget.style.transform = 'translateY(-3px) scale(1.05)';
                    e.currentTarget.style.boxShadow = '0 5px 25px rgba(0,0,0,0.2)';
                },
                onMouseLeave: (e) => {
                    e.currentTarget.style.transform = 'translateY(0) scale(1)';
                    e.currentTarget.style.boxShadow = '0 2px 15px rgba(0,0,0,0.1)';
                }
            },
                btn.icon ? 
                    e('i', { 
                        className: btn.icon, 
                        style: { 
                            ...styles.iconStyle,
                            color: btn.color
                        } 
                    }) :
                    e('span', { 
                        style: { 
                            ...styles.iconStyle, 
                            fontSize: btn.text === '小' ? '20px' : '24px',
                            fontWeight: 'bold',
                            color: btn.color,
                            fontFamily: btn.text === '小' ? 'PingFang SC, Hiragino Sans GB, Microsoft YaHei, Arial' : 'inherit'
                        } 
                    }, btn.text)
            );
        };

        // CSS 애니메이션 추가
        if (!document.getElementById('share-buttons-style')) {
            const style = document.createElement('style');
            style.id = 'share-buttons-style';
            style.innerHTML = `
                @keyframes slideUp {
                    from {
                        transform: translate(-50%, 100%);
                        opacity: 0;
                    }
                    to {
                        transform: translate(-50%, 0);
                        opacity: 1;
                    }
                }
                @keyframes fadeIn {
                    from {
                        opacity: 0;
                        transform: translateY(10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                .share-btn {
                    -webkit-tap-highlight-color: transparent;
                }
                .share-btn:active {
                    transform: scale(0.95) !important;
                }
                .share-section {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                }
            `;
            document.head.appendChild(style);
        }

        return e('div', { className: 'share-section', style: styles.container },
            // 메인 공유 버튼들
            e('div', { style: styles.shareGrid },
                mainButtons.slice(0, 5).map(btn => renderButton(btn))
            ),
            
            // 링크 복사 버튼 (아래 중앙에)
            e('div', { style: { textAlign: 'center', marginBottom: '15px' } },
                renderButton(mainButtons[5])
            ),
            
            // 토글 버튼
            e('div', { style: { textAlign: 'center', marginBottom: '15px' } },
                e('button', {
                    onClick: () => setShowMoreOptions(!showMoreOptions),
                    style: styles.toggleButton,
                    onMouseEnter: (e) => {
                        e.target.style.background = '#f3f4f6';
                        e.target.style.borderColor = '#d1d5db';
                    },
                    onMouseLeave: (e) => {
                        e.target.style.background = 'none';
                        e.target.style.borderColor = '#e5e7eb';
                    }
                }, 
                    showMoreOptions ? '간단히 보기 ▲' : '더 많은 옵션 ▼'
                )
            ),
            
            // 추가 옵션
            showMoreOptions && e('div', { 
                style: { 
                    ...styles.moreOptions,
                    animation: 'fadeIn 0.3s ease-out'
                } 
            },
                extraButtons.map(btn => renderButton(btn))
            ),
            
            // 토스트 메시지
            showToast && e('div', { style: styles.toast }, toastMessage)
        );
    };

    // ShareButtons 컴포넌트를 window 객체에 등록
    window.ShareButtons = ShareButtons;
})();
