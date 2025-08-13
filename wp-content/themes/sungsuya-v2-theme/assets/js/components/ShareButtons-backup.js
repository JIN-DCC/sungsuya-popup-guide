/**
 * 성수야 V2 - SNS 공유 버튼 컴포넌트
 */

(function() {
    const { useState } = React;
    const e = React.createElement;

    const ShareButtons = ({ placeData }) => {
        const [showMoreOptions, setShowMoreOptions] = useState(false);
        
        // 현재 URL과 공유 정보
        const currentUrl = placeData.url || window.location.href;
        const title = placeData.title || document.title;
        const description = `성수동 핫플 "${title}" 방문했어요! 📍`;
        
        // 해시태그
        const hashtags = ['성수동', 'SeoulTravel', '서울여행', 'Seongsu', '성수동핫플'];
        const hashtagString = hashtags.join(',');

        // 공유 핸들러들
        const shareHandlers = {
            // 카카오톡 - 링크 복사 방식 (실제 배포시 카카오 SDK로 변경 가능)
            kakao: () => {
                // 실제 배포시: 카카오 개발자에서 앱 등록 후 아래 코드 사용
                // if (window.Kakao) {
                //     Kakao.Share.sendDefault({
                //         objectType: 'feed',
                //         content: {
                //             title: title,
                //             description: description,
                //             imageUrl: image,
                //             link: { mobileWebUrl: currentUrl, webUrl: currentUrl }
                //         }
                //     });
                // }
                
                // 현재: 링크 복사 방식
                navigator.clipboard.writeText(currentUrl).then(() => {
                    alert('링크가 복사되었습니다!\n카카오톡에서 붙여넣기하여 공유해주세요.');
                }).catch(() => {
                    // 폴백
                    const textArea = document.createElement('textarea');
                    textArea.value = currentUrl;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    alert('링크가 복사되었습니다!\n카카오톡에서 붙여넣기하여 공유해주세요.');
                });
            },

            // 인스타그램 (앱으로 리다이렉트)
            instagram: () => {
                // 인스타그램은 직접 공유 API가 없으므로 앱 열기 시도
                const instagramUrl = 'instagram://camera';
                window.location.href = instagramUrl;
                
                // 앱이 없는 경우 웹으로 이동
                setTimeout(() => {
                    window.open('https://www.instagram.com/', '_blank');
                }, 1000);
            },

            // 페이스북
            facebook: () => {
                const fbUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl)}&quote=${encodeURIComponent(description)}`;
                window.open(fbUrl, '_blank', 'width=600,height=400');
            },

            // 트위터(X)
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
                const naverUrl = `https://share.naver.com/web/shareView.nhn?url=${encodeURIComponent(currentUrl)}&title=${encodeURIComponent(title)}`;
                window.open(naverUrl, '_blank');
            },

            // 샤오홍슈 (小红书)
            xiaohongshu: () => {
                // 샤오홍슈는 모바일 앱 중심이므로 일반 공유 링크 사용
                alert('샤오홍슈 앱에서 직접 공유해주세요.');
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
                    alert('링크가 복사되었습니다!');
                } catch (err) {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = currentUrl;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    alert('링크가 복사되었습니다!');
                }
            }
        };

        // 스타일 정의
        const styles = {
            container: {
                padding: '10px',
                maxWidth: '400px',
                margin: '0 auto'
            },
            shareGrid: {
                display: 'grid',
                gridTemplateColumns: 'repeat(5, 1fr)',
                gap: '15px',
                marginBottom: '20px'
            },
            shareButton: {
                width: '60px',
                height: '60px',
                border: 'none',
                borderRadius: '50%',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                transition: 'all 0.2s',
                position: 'relative',
                overflow: 'hidden',
                boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
            },
            logoStyle: {
                width: '32px',
                height: '32px',
                objectFit: 'contain'
            },
            iconStyle: {
                fontSize: '32px',
                color: 'white',
                fontWeight: 'bold',
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
            },
            utilityBar: {
                display: 'flex',
                gap: '10px',
                justifyContent: 'center',
                marginTop: '20px',
                paddingTop: '20px',
                borderTop: '1px solid #eee'
            },
            utilityButton: {
                padding: '10px 20px',
                border: 'none',
                borderRadius: '25px',
                cursor: 'pointer',
                fontSize: '14px',
                fontWeight: '500',
                display: 'flex',
                alignItems: 'center',
                gap: '6px',
                transition: 'all 0.2s',
                background: '#f3f4f6',
                color: '#374151'
            },
            moreOptions: {
                marginTop: '15px',
                display: 'grid',
                gridTemplateColumns: 'repeat(4, 1fr)',
                gap: '15px'
            }
        };

        // SVG 로고들
        const logos = {
            kakao: e('svg', { width: '32', height: '32', viewBox: '0 0 24 24', fill: '#3C1E1E' },
                e('path', { d: 'M12 3C6.477 3 2 6.477 2 10.5c0 2.586 1.834 4.856 4.604 6.14-.203.73-.737 2.657-.844 3.065-.133.512.188.517.398.375.162-.11 2.589-1.758 3.642-2.474.395.057.793.094 1.2.094 5.523 0 10-3.477 10-7.5S17.523 3 12 3z' })
            ),
            facebook: e('span', { style: styles.iconStyle }, 'f'),
            line: e('svg', { width: '32', height: '32', viewBox: '0 0 24 24', fill: 'white' },
                e('path', { d: 'M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.349 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314' })
            ),
            instagram: e('svg', { width: '32', height: '32', viewBox: '0 0 24 24', fill: 'white' },
                e('path', { d: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zM5.838 12a6.162 6.162 0 1 1 12.324 0 6.162 6.162 0 0 1-12.324 0zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm4.965-10.405a1.44 1.44 0 1 1 2.881.001 1.44 1.44 0 0 1-2.881-.001z' })
            )
        };

        // React.createElement를 사용한 UI 구성
        return e('div', { className: 'share-section', style: styles.container },
            
            // 메인 공유 버튼들 - WeChat 제외
            e('div', { style: styles.shareGrid },
                // 카카오톡
                e('button', {
                    onClick: shareHandlers.kakao,
                    style: { ...styles.shareButton, background: '#FEE500' },
                    title: '카카오톡',
                    onMouseEnter: (e) => e.target.style.transform = 'scale(1.1)',
                    onMouseLeave: (e) => e.target.style.transform = 'scale(1)'
                }, logos.kakao),
                
                // 페이스북
                e('button', {
                    onClick: shareHandlers.facebook,
                    style: { ...styles.shareButton, background: '#1877F2' },
                    title: '페이스북',
                    onMouseEnter: (e) => e.target.style.transform = 'scale(1.1)',
                    onMouseLeave: (e) => e.target.style.transform = 'scale(1)'
                }, logos.facebook),
                
                // LINE
                e('button', {
                    onClick: shareHandlers.line,
                    style: { ...styles.shareButton, background: '#00C300' },
                    title: 'LINE',
                    onMouseEnter: (e) => e.target.style.transform = 'scale(1.1)',
                    onMouseLeave: (e) => e.target.style.transform = 'scale(1)'
                }, logos.line),
                
                // 트위터(X)
                e('button', {
                    onClick: shareHandlers.twitter,
                    style: { ...styles.shareButton, background: '#000000' },
                    title: 'X (트위터)',
                    onMouseEnter: (e) => e.target.style.transform = 'scale(1.1)',
                    onMouseLeave: (e) => e.target.style.transform = 'scale(1)'
                }, e('span', { style: { ...styles.iconStyle, fontSize: '28px' } }, 'X')),
                
                // 인스타그램
                e('button', {
                    onClick: shareHandlers.instagram,
                    style: { ...styles.shareButton, background: 'linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%)' },
                    title: '인스타그램',
                    onMouseEnter: (e) => e.target.style.transform = 'scale(1.1)',
                    onMouseLeave: (e) => e.target.style.transform = 'scale(1)'
                }, logos.instagram),
                
                // 링크 복사
                e('button', {
                    onClick: shareHandlers.copyLink,
                    style: { ...styles.shareButton, background: '#6B7280' },
                    title: '링크 복사',
                    onMouseEnter: (e) => e.target.style.transform = 'scale(1.1)',
                    onMouseLeave: (e) => e.target.style.transform = 'scale(1)'
                }, e('svg', { width: '32', height: '32', viewBox: '0 0 24 24', fill: 'white' },
                    e('path', { d: 'M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z' })
                ))
            ),
            
            // 추가 공유 옵션 (토글)
            e('div', { style: { textAlign: 'center', marginBottom: '15px' } },
                e('button', {
                    onClick: () => setShowMoreOptions(!showMoreOptions),
                    style: {
                        background: 'none',
                        border: 'none',
                        color: '#6B7280',
                        cursor: 'pointer',
                        fontSize: '14px',
                        padding: '5px 15px',
                        borderRadius: '20px',
                        transition: 'all 0.2s'
                    },
                    onMouseEnter: (e) => e.target.style.background = '#f3f4f6',
                    onMouseLeave: (e) => e.target.style.background = 'none'
                }, showMoreOptions ? '간단히 보기 ▲' : '더 많은 옵션 ▼')
            ),
            
            // 추가 옵션 그리드
            showMoreOptions && e('div', { style: styles.moreOptions },
                // WhatsApp
                e('button', {
                    onClick: shareHandlers.whatsapp,
                    style: { ...styles.shareButton, background: '#25D366' },
                    title: 'WhatsApp',
                    onMouseEnter: (e) => e.target.style.transform = 'scale(1.1)',
                    onMouseLeave: (e) => e.target.style.transform = 'scale(1)'
                }, e('svg', { width: '32', height: '32', viewBox: '0 0 24 24', fill: 'white' },
                    e('path', { d: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z' })
                )),
                
                // Telegram
                e('button', {
                    onClick: shareHandlers.telegram,
                    style: { ...styles.shareButton, background: '#0088CC' },
                    title: 'Telegram',
                    onMouseEnter: (e) => e.target.style.transform = 'scale(1.1)',
                    onMouseLeave: (e) => e.target.style.transform = 'scale(1)'
                }, e('svg', { width: '32', height: '32', viewBox: '0 0 24 24', fill: 'white' },
                    e('path', { d: 'M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z' })
                )),
                
                // 네이버
                e('button', {
                    onClick: shareHandlers.naver,
                    style: { ...styles.shareButton, background: '#03C75A' },
                    title: '네이버',
                    onMouseEnter: (e) => e.target.style.transform = 'scale(1.1)',
                    onMouseLeave: (e) => e.target.style.transform = 'scale(1)'
                }, e('span', { style: { ...styles.iconStyle, fontSize: '26px' } }, 'N')),
                
                // 웨이보
                e('button', {
                    onClick: shareHandlers.weibo,
                    style: { ...styles.shareButton, background: '#E6162D' },
                    title: '웨이보',
                    onMouseEnter: (e) => e.target.style.transform = 'scale(1.1)',
                    onMouseLeave: (e) => e.target.style.transform = 'scale(1)'
                }, e('svg', { width: '32', height: '32', viewBox: '0 0 24 24', fill: 'white' },
                    e('path', { d: 'M10.098 20.323c-3.977.391-7.414-1.406-7.672-4.02-.259-2.609 2.759-5.047 6.74-5.441 3.979-.394 7.413 1.404 7.671 4.018.259 2.6-2.759 5.049-6.737 5.439l-.002.004zM9.05 17.219c-.384.616-1.208.88-1.829.602-.612-.279-.793-.991-.406-1.593.379-.595 1.176-.861 1.793-.601.622.263.819.972.442 1.592zm1.27-1.627c-.141.237-.449.353-.689.253-.236-.09-.313-.364-.177-.586.138-.227.436-.346.672-.24.239.09.315.36.18.601l.014-.028zm.176-2.719c-1.893-.493-4.033.45-4.857 2.118-.836 1.704-.026 3.591 1.886 4.21 1.983.64 4.318-.341 5.132-2.179.8-1.793-.201-3.642-2.161-4.149zm7.563-1.224c-.346-.105-.57-.18-.405-.615.375-.977.412-1.806.003-2.398-.76-1.084-2.848-1.035-5.244-.030 0 .015-.751.331-.556-.27.365-1.188.31-2.19-.27-2.764-.631-.689-2.346-.669-3.839.09-1.501.781-2.784 2.223-3.342 3.629-.699 1.807-.451 3.627 1.477 4.975.239.164.525.359.825.569 1.521 1.07 3.427 2.061 5.39 2.061 2.955 0 5.59-2.116 6.085-4.545.273-1.33-.255-2.356-.36-2.4l-.015.015.06-.045zm1.819-4.897c-.749-1.277-2.223-1.986-3.609-1.781 0 0-.315.045-.539.179-.225.119-.314.345-.21.57.121.269.424.314.66.239 1.069-.314 2.19.238 2.764 1.305.585 1.079.271 2.221-.465 2.941-.165.164-.225.404-.119.644.12.271.42.337.689.15v-.015c1.077-1.004 1.594-2.945.828-4.232v.005l.001-.005z' })
                )),
                
                // 샤오홍슈
                e('button', {
                    onClick: shareHandlers.xiaohongshu,
                    style: { ...styles.shareButton, background: '#FE2C55' },
                    title: '샤오홍슈',
                    onMouseEnter: (e) => e.target.style.transform = 'scale(1.1)',
                    onMouseLeave: (e) => e.target.style.transform = 'scale(1)'
                }, e('span', { style: { ...styles.iconStyle, fontSize: '24px' } }, '小'))
            )
        );
    };

    // ShareButtons 컴포넌트를 window 객체에 등록
    window.ShareButtons = ShareButtons;
})();
