<?php
/**
 * 팝업스토어 상세페이지 템플릿 (개선 버전)
 * 
 * @package SungsuyaV2
 */

get_header(); ?>

<div class="popup-store-detail">
    <?php while (have_posts()) : the_post(); ?>
        
        <!-- 스토어 헤더 섹션 -->
        <section class="store-header">
            <div class="store-header-content">
                <!-- 네비게이션 -->
                <?php get_template_part('template-parts/popup-store-navigation'); ?>
                
                <!-- 메인 이미지 -->
                <div class="store-hero">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="store-image">
                            <?php the_post_thumbnail('large', ['class' => 'hero-image']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 스토어 기본 정보 -->
                    <div class="store-basic-info">
                        <div class="store-meta-badges">
                            <?php
                            // 카테고리 표시 (다양한 키로 시도)
                            $categories = get_the_terms(get_the_ID(), 'store_category');
                            if ($categories && !is_wp_error($categories)) {
                                foreach ($categories as $category) {
                                    echo '<span class="category-badge category-' . esc_attr($category->slug) . '">';
                                    echo esc_html($category->name);
                                    echo '</span>';
                                }
                            }
                            
                            // 인기 스토어 배지
                            $featured = get_post_meta(get_the_ID(), 'featured', true) ?: 
                                       get_post_meta(get_the_ID(), '_featured', true);
                            if ($featured) {
                                echo '<span class="featured-badge">⭐ 인기스토어</span>';
                            }
                            ?>
                        </div>
                        
                        <h1 class="store-title"><?php the_title(); ?></h1>
                        
                        <!-- 운영 상태 표시 -->
                        <?php
                        $start_date = get_post_meta(get_the_ID(), 'start_date', true) ?: 
                                     get_post_meta(get_the_ID(), '_start_date', true);
                        $end_date = get_post_meta(get_the_ID(), 'end_date', true) ?: 
                                   get_post_meta(get_the_ID(), '_end_date', true);
                        $current_date = date('Y-m-d');
                        
                        if ($start_date && $end_date) {
                            $is_active = ($current_date >= $start_date && $current_date <= $end_date);
                            $status_class = $is_active ? 'status-active' : 'status-closed';
                            
                            if ($is_active) {
                                $end_date_obj = new DateTime($end_date);
                                $today_obj = new DateTime();
                                $diff = $today_obj->diff($end_date_obj);
                                $status_text = '운영중 (D-' . $diff->days . ')';
                            } else if ($current_date < $start_date) {
                                $status_text = '오픈 예정';
                                $status_class = 'status-upcoming';
                            } else {
                                $status_text = '운영 종료';
                                $status_class = 'status-closed';
                            }
                            
                            echo '<div class="store-status ' . $status_class . '">';
                            echo '<span class="status-dot"></span>';
                            echo '<span class="status-text">' . $status_text . '</span>';
                            echo '</div>';
                        }
                        ?>
                        
                        <!-- 간단한 요약 정보 -->
                        <div class="store-summary">
                            <?php 
                            $excerpt = get_the_excerpt();
                            if ($excerpt) {
                                echo '<p class="store-excerpt">' . esc_html($excerpt) . '</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 메인 콘텐츠 영역 -->
        <div class="store-content-wrapper">
            
            <!-- 스토어 정보 섹션 -->
            <section class="store-info-section">
                <?php get_template_part('template-parts/popup-store-info'); ?>
            </section>

            <!-- 소셜 미디어 & 연락처 섹션 -->
            <section class="store-social-section">
                <?php get_template_part('template-parts/popup-store-social'); ?>
            </section>

            <!-- 투어 정보 섹션 -->
            <section class="store-tour-section">
                <?php get_template_part('template-parts/popup-store-tour-info'); ?>
            </section>

            <!-- 위치 및 지도 섹션 -->
            <section class="store-location-section">
                <?php get_template_part('template-parts/popup-store-map'); ?>
            </section>

            <!-- 교통 정보 섹션 -->
            <section class="store-transport-section">
                <?php get_template_part('template-parts/popup-store-transport'); ?>
            </section>

            <!-- 스토어 설명 -->
            <?php if (get_the_content()) : ?>
            <section class="store-description-section">
                <div class="container">
                    <div class="section-header">
                        <h2 class="section-title">
                            <span class="section-icon">📝</span>
                            스토어 소개
                        </h2>
                    </div>
                    <div class="store-description">
                        <?php 
                        // 더미 텍스트 제거
                        $content = get_the_content();
                        $dummy_phrases = [
                            'Developers or web designers developing mobile application for banking in the office, close-up on phone and paper sketches',
                            'Lorem ipsum dolor sit amet',
                            'consectetur adipiscing elit',
                            'sed do eiusmod tempor',
                            'incididunt ut labore'
                        ];
                        
                        foreach ($dummy_phrases as $phrase) {
                            $content = str_replace($phrase, '', $content);
                        }
                        
                        $content = trim($content);
                        
                        if (!empty($content)) {
                            echo apply_filters('the_content', $content);
                        } else {
                            echo '<div class="placeholder-content">';
                            echo '<h3>성수동의 특별한 팝업스토어</h3>';
                            echo '<p>다양한 브랜드와 아티스트들이 선보이는 독특한 콘텐츠와 경험을 만나보세요.</p>';
                            echo '<p>성수동만의 창조적이고 실험적인 공간에서 새로운 발견을 해보세요.</p>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- 관련 스토어 섹션 -->
            <section class="related-stores-section">
                <?php get_template_part('template-parts/popup-store-related'); ?>
            </section>

        </div>

        <!-- 공유 및 액션 버튼 -->
        <div class="store-actions">
            <div class="container">
                <div class="actions-wrapper">
                    <button type="button" class="action-btn share-btn" onclick="shareStore()">
                        <span class="btn-icon">📤</span>
                        <span class="btn-text">공유하기</span>
                    </button>
                    
                    <button type="button" class="action-btn bookmark-btn" onclick="toggleBookmark(<?php echo get_the_ID(); ?>)">
                        <span class="btn-icon">🔖</span>
                        <span class="btn-text">북마크</span>
                    </button>
                    
                    <a href="<?php echo home_url('/stores/'); ?>" class="action-btn back-btn">
                        <span class="btn-icon">🏠</span>
                        <span class="btn-text">스토어 목록</span>
                    </a>
                </div>
            </div>
        </div>

    <?php endwhile; ?>
</div>

<!-- 공유 및 상호작용 스크립트 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 스크롤 진행도 표시
    createScrollProgress();
    
    // 섹션 진입 애니메이션
    observeSections();
    
    // 키보드 단축키 안내
    showKeyboardHints();
});

// 스크롤 진행도 표시
function createScrollProgress() {
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    progressBar.innerHTML = '<div class="progress-fill"></div>';
    document.body.appendChild(progressBar);
    
    window.addEventListener('scroll', function() {
        const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;
        document.querySelector('.progress-fill').style.width = scrolled + '%';
    });
}

// 섹션 진입 애니메이션
function observeSections() {
    if ('IntersectionObserver' in window) {
        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('section-visible');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        const sections = document.querySelectorAll('.store-info-section, .store-location-section, .store-transport-section, .store-description-section, .related-stores-section');
        sections.forEach(section => {
            section.classList.add('section-observe');
            sectionObserver.observe(section);
        });
    }
}

// 키보드 단축키 안내
function showKeyboardHints() {
    const hints = document.createElement('div');
    hints.className = 'keyboard-hints';
    hints.innerHTML = `
        <div class="hints-content">
            <h4>키보드 단축키</h4>
            <ul>
                <li><kbd>←</kbd> 이전 스토어</li>
                <li><kbd>→</kbd> 다음 스토어</li>
                <li><kbd>ESC</kbd> 스토어 목록</li>
            </ul>
        </div>
    `;
    
    // 3초 후 자동 숨김
    setTimeout(() => {
        hints.classList.add('hints-show');
        setTimeout(() => {
            hints.classList.remove('hints-show');
            setTimeout(() => {
                if (document.body.contains(hints)) {
                    document.body.removeChild(hints);
                }
            }, 300);
        }, 3000);
    }, 1000);
    
    document.body.appendChild(hints);
}

// 스토어 공유 기능
function shareStore() {
    const storeTitle = document.querySelector('.store-title').textContent;
    const storeUrl = window.location.href;
    
    if (navigator.share) {
        navigator.share({
            title: storeTitle + ' - 성수야',
            text: '성수동의 특별한 팝업스토어를 확인해보세요!',
            url: storeUrl
        }).catch(console.error);
    } else {
        // 폴백: URL 복사
        if (navigator.clipboard) {
            navigator.clipboard.writeText(storeUrl).then(() => {
                showToast('링크가 복사되었습니다', 'success');
            });
        } else {
            // 구형 브라우저 지원
            const textArea = document.createElement('textarea');
            textArea.value = storeUrl;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showToast('링크가 복사되었습니다', 'success');
        }
    }
}

// 북마크 토글
function toggleBookmark(storeId) {
    const bookmarks = JSON.parse(localStorage.getItem('sungsuya_bookmarks') || '[]');
    const isBookmarked = bookmarks.includes(storeId);
    const btn = document.querySelector('.bookmark-btn');
    
    if (isBookmarked) {
        const index = bookmarks.indexOf(storeId);
        bookmarks.splice(index, 1);
        btn.classList.remove('bookmarked');
        showToast('북마크에서 제거되었습니다', 'info');
    } else {
        bookmarks.push(storeId);
        btn.classList.add('bookmarked');
        showToast('북마크에 추가되었습니다', 'success');
    }
    
    localStorage.setItem('sungsuya_bookmarks', JSON.stringify(bookmarks));
}

// 토스트 메시지 (전역 함수)
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${type === 'success' ? '✅' : type === 'warning' ? '⚠️' : type === 'error' ? '❌' : 'ℹ️'}</span>
        <span class="toast-message">${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// 페이지 로드 완료 시 북마크 상태 확인
window.addEventListener('load', function() {
    const storeId = <?php echo get_the_ID(); ?>;
    const bookmarks = JSON.parse(localStorage.getItem('sungsuya_bookmarks') || '[]');
    const bookmarkBtn = document.querySelector('.bookmark-btn');
    
    if (bookmarks.includes(storeId) && bookmarkBtn) {
        bookmarkBtn.classList.add('bookmarked');
    }
});
</script>

<?php get_footer(); ?>
