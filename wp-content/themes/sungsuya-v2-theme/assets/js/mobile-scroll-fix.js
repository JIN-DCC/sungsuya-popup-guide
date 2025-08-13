/**
 * 모바일 스크롤 버그 수정 JavaScript
 * 
 * @since 2025-07-04
 */

(function() {
    'use strict';
    
    // 모바일 디바이스 감지
    const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    
    if (!isMobile) return;
    
    // DOM 로드 완료 후 실행
    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. body에 올바른 스타일 적용
        document.body.style.overflow = 'auto';
        document.body.style.position = 'relative';
        document.body.style.height = 'auto';
        document.body.style.minHeight = '100%';
        
        // 2. html에도 스타일 적용
        document.documentElement.style.overflow = 'auto';
        document.documentElement.style.height = '100%';
        
        // 3. 모든 고정 높이 요소 확인 및 수정
        const fixHeightElements = document.querySelectorAll('[style*="height: 100vh"], [style*="height:100vh"]');
        fixHeightElements.forEach(el => {
            el.style.height = 'auto';
            el.style.minHeight = '100vh';
        });
        
        // 4. iOS bounce 효과 방지하면서 스크롤은 허용
        let startY = 0;
        document.addEventListener('touchstart', function(e) {
            startY = e.touches[0].pageY;
        }, { passive: true });
        
        document.addEventListener('touchmove', function(e) {
            const y = e.touches[0].pageY;
            const scrollTop = window.pageYOffset;
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            
            // 상단이나 하단에서 추가 스크롤 방지
            if ((scrollTop <= 0 && y > startY) || (scrollTop >= maxScroll && y < startY)) {
                e.preventDefault();
            }
        }, { passive: false });
        
        // 5. 메뉴가 열렸을 때만 스크롤 방지
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle, .menu-toggle');
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', function() {
                if (document.body.classList.contains('menu-open')) {
                    // 메뉴 닫기
                    document.body.style.overflow = 'auto';
                    document.body.style.position = 'relative';
                } else {
                    // 메뉴 열기
                    document.body.style.overflow = 'hidden';
                    document.body.style.position = 'fixed';
                    document.body.style.width = '100%';
                }
            });
        }
        
        // 6. 화면 방향 변경 시 스타일 재적용
        window.addEventListener('orientationchange', function() {
            setTimeout(function() {
                document.body.style.overflow = 'auto';
                document.body.style.position = 'relative';
                document.body.style.height = 'auto';
            }, 300);
        });
        
        // 7. 디버그 정보 출력 (개발 중에만)
        console.log('Mobile scroll fix applied');
        console.log('Body overflow:', getComputedStyle(document.body).overflow);
        console.log('Body position:', getComputedStyle(document.body).position);
        console.log('Body height:', getComputedStyle(document.body).height);
    });
    
    // 8. 페이지 로드 완료 후 한 번 더 확인
    window.addEventListener('load', function() {
        // 모든 리소스 로드 후 스타일 재확인
        setTimeout(function() {
            if (getComputedStyle(document.body).overflow === 'hidden') {
                document.body.style.overflow = 'auto';
            }
        }, 500);
    });
    
})();
