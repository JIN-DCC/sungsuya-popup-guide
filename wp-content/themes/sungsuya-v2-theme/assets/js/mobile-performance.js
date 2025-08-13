/**
 * 모바일 성능 최적화 스크립트
 * mobile-performance.js
 * 2025-07-05
 */
(function($) {
    'use strict';
    
    // 스크롤 최적화
    let isScrolling = false;
    let scrollTimeout;
    
    function handleScroll() {
        if (!isScrolling) {
            document.body.classList.add('is-scrolling');
            isScrolling = true;
        }
        
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            document.body.classList.remove('is-scrolling');
            isScrolling = false;
        }, 100);
    }
    
    window.addEventListener('scroll', handleScroll, { passive: true });
    
    // 이미지 Lazy Loading 폴백
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.classList.add('loaded');
                        delete img.dataset.src;
                    }
                    imageObserver.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px'
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // 터치 최적화
    document.addEventListener('touchstart', function() {}, { passive: true });
    
    // 링크 클릭 시 부드러운 전환
    $(document).on('click', 'a[href^="/"]:not([target="_blank"])', function(e) {
        const href = $(this).attr('href');
        if (href && !href.startsWith('#')) {
            e.preventDefault();
            $('body').css('opacity', '0.5');
            setTimeout(() => {
                window.location.href = href;
            }, 200);
        }
    });
    
    // 스크롤 방향에 따른 헤더 숨김/표시
    let lastScrollTop = 0;
    $(window).on('scroll', function() {
        const scrollTop = $(this).scrollTop();
        
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            // 아래로 스크롤
            $('.site-header').addClass('hidden');
        } else {
            // 위로 스크롤
            $('.site-header').removeClass('hidden');
        }
        
        lastScrollTop = scrollTop;
    });
    
})(jQuery);