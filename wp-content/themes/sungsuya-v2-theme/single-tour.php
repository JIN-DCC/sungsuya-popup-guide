    // 소셜 공유
    document.querySelectorAll('.social-btn').forEach(button => {
        button.addEventListener('click', function() {
            const platform = this.dataset.platform;
            const tourUrl = encodeURIComponent(window.location.href);
            const tourTitle = encodeURIComponent(tourData.title);
            const tourDescription = encodeURIComponent(`성수동 ${tourData.places_data.length}곳 투어 코스를 확인해보세요!`);
            
            let shareUrl = '';
            
            switch(platform) {
                case 'kakao':
                    // 카카오톡 공유 (실제 앱키 필요)
                    if (window.Kakao) {
                        window.Kakao.Share.sendDefault({
                            objectType: 'location',
                            address: '서울시 성동구 성수동',
                            addressTitle: tourData.title,
                            content: {
                                title: tourData.title,
                                description: tourDescription,
                                imageUrl: 'https://via.placeholder.com/400x300',
                                link: {
                                    mobileWebUrl: window.location.href,
                                    webUrl: window.location.href
                                }
                            }
                        });
                    } else {
                        alert('카카오톡 앱이 필요합니다.');
                    }
                    break;
                    
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${tourUrl}&quote=${tourTitle}`;
                    break;
                    
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?text=${tourTitle}&url=${tourUrl}&hashtags=성수동,투어,성수야`;
                    break;
                    
                case 'line':
                    shareUrl = `https://social-plugins.line.me/lineit/share?url=${tourUrl}&text=${tourTitle}`;
                    break;
            }
            
            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
            }
            
            // 공유 횟수 증가 API 호출
            fetch(`/wp-json/sungsuya/v2/tours/${tourData.tour_id}/share`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            // Analytics 추적
            if (window.sungsuyaAnalytics) {
                window.sungsuyaAnalytics.tourShared(platform, tourData);
            }
            
            // 모달 닫기
            document.getElementById('share-modal').style.display = 'none';
        });
    });
});
</script>

<?php 
// 언어 이름 반환 함수
function get_language_name($code) {
    $languages = array(
        'ko' => '한국어',
        'en' => 'English',
        'ja' => '日本語',
        'zh' => '中文',
        'es' => 'Español'
    );
    return $languages[$code] ?? $code;
}

get_footer(); ?>