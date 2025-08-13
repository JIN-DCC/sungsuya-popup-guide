<?php
/**
 * 성수야! V2 - 기본 템플릿 (폴백)
 * 
 * 이 파일은 WordPress 템플릿 계층구조의 최종 폴백 파일입니다.
 * 다른 특정 템플릿이 없을 때 사용됩니다.
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

get_header(); ?>

<div class="site-content">
    <div class="container">
        
        <?php if (is_home() && !is_front_page()) : ?>
            <header class="page-header">
                <h1 class="page-title"><?php single_post_title(); ?></h1>
            </header>
        <?php endif; ?>

        <main id="main" class="site-main">
            
            <?php
            if (have_posts()) :
                
                // 포스트 타입별 다른 레이아웃 적용
                if (is_singular()) :
                    // 단일 포스트/페이지
                    while (have_posts()) : the_post();
                        ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                            <header class="entry-header">
                                <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                                
                                <?php if ('post' === get_post_type()) : ?>
                                <div class="entry-meta">
                                    <?php
                                    printf(
                                        esc_html__('게시일: %s', 'sungsuya-v2'),
                                        '<time datetime="' . esc_attr(get_the_date('c')) . '">' . esc_html(get_the_date()) . '</time>'
                                    );
                                    ?>
                                </div>
                                <?php endif; ?>
                            </header>
                            
                            <?php if (has_post_thumbnail()) : ?>
                            <div class="post-thumbnail">
                                <?php the_post_thumbnail('large'); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="entry-content">
                                <?php
                                the_content();
                                
                                wp_link_pages(array(
                                    'before' => '<div class="page-links">' . esc_html__('페이지:', 'sungsuya-v2'),
                                    'after'  => '</div>',
                                ));
                                ?>
                            </div>
                            
                            <?php if (get_edit_post_link()) : ?>
                            <footer class="entry-footer">
                                <?php
                                edit_post_link(
                                    sprintf(
                                        wp_kses(
                                            __('이 %s 편집', 'sungsuya-v2'),
                                            array(
                                                'span' => array(
                                                    'class' => array(),
                                                ),
                                            )
                                        ),
                                        get_post_type()
                                    ),
                                    '<span class="edit-link">',
                                    '</span>'
                                );
                                ?>
                            </footer>
                            <?php endif; ?>
                        </article>
                        <?php
                    endwhile;
                    
                else :
                    // 아카이브 페이지 (목록)
                    ?>
                    <header class="page-header">
                        <?php
                        if (is_archive()) {
                            the_archive_title('<h1 class="page-title">', '</h1>');
                            the_archive_description('<div class="archive-description">', '</div>');
                        } elseif (is_search()) {
                            ?>
                            <h1 class="page-title">
                                <?php
                                printf(esc_html__('검색 결과: %s', 'sungsuya-v2'), '<span>' . get_search_query() . '</span>');
                                ?>
                            </h1>
                            <?php
                        }
                        ?>
                    </header>
                    
                    <div class="posts-grid">
                        <?php
                        while (have_posts()) : the_post();
                            ?>
                            <article id="post-<?php the_ID(); ?>" <?php post_class('post-card'); ?>>
                                <?php if (has_post_thumbnail()) : ?>
                                <div class="post-thumbnail">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <div class="post-content">
                                    <header class="entry-header">
                                        <?php the_title('<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>'); ?>
                                        
                                        <?php if ('post' === get_post_type()) : ?>
                                        <div class="entry-meta">
                                            <?php echo get_the_date(); ?>
                                        </div>
                                        <?php endif; ?>
                                    </header>
                                    
                                    <div class="entry-summary">
                                        <?php the_excerpt(); ?>
                                    </div>
                                    
                                    <footer class="entry-footer">
                                        <a href="<?php the_permalink(); ?>" class="read-more">
                                            더 읽기 →
                                        </a>
                                    </footer>
                                </div>
                            </article>
                            <?php
                        endwhile;
                        ?>
                    </div>
                    
                    <?php
                    // 페이지네이션
                    the_posts_pagination(array(
                        'prev_text' => '이전',
                        'next_text' => '다음',
                    ));
                    
                endif;
                
            else :
                // 콘텐츠가 없을 때
                ?>
                <section class="no-results not-found">
                    <header class="page-header">
                        <h1 class="page-title">찾을 수 없습니다</h1>
                    </header>
                    
                    <div class="page-content">
                        <?php
                        if (is_home() && current_user_can('publish_posts')) :
                            ?>
                            <p>첫 번째 포스트를 게시할 준비가 되셨나요? <a href="<?php echo esc_url(admin_url('post-new.php')); ?>">여기서 시작하세요</a>.</p>
                            <?php
                        elseif (is_search()) :
                            ?>
                            <p>죄송합니다. 검색어와 일치하는 결과를 찾을 수 없습니다. 다른 키워드로 다시 시도해보세요.</p>
                            <?php
                            get_search_form();
                        else :
                            ?>
                            <p>찾으시는 내용이 없는 것 같습니다. 검색을 시도해보시겠습니까?</p>
                            <?php
                            get_search_form();
                        endif;
                        ?>
                    </div>
                </section>
                <?php
            endif;
            ?>
            
        </main>
    </div>
</div>

<style>
.site-content {
    padding: 60px 0;
    background: #f8f9fa;
    min-height: 70vh;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.page-header {
    margin-bottom: 40px;
    text-align: center;
}

.page-title {
    font-size: 2.5rem;
    color: #333;
    margin-bottom: 20px;
}

.archive-description {
    font-size: 1.125rem;
    color: #666;
    max-width: 700px;
    margin: 0 auto;
}

/* 단일 포스트/페이지 스타일 */
article.post,
article.page {
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.entry-header {
    margin-bottom: 30px;
}

.entry-title {
    font-size: 2rem;
    color: #333;
    margin-bottom: 10px;
}

.entry-meta {
    color: #666;
    font-size: 0.875rem;
}

.post-thumbnail {
    margin-bottom: 30px;
    text-align: center;
}

.post-thumbnail img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
}

.entry-content {
    font-size: 1.125rem;
    line-height: 1.8;
    color: #444;
}

.entry-content h2,
.entry-content h3 {
    margin-top: 30px;
    margin-bottom: 15px;
}

.entry-content p {
    margin-bottom: 20px;
}

/* 목록 스타일 */
.posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.post-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.post-card:hover {
    transform: translateY(-5px);
}

.post-card .post-thumbnail {
    margin-bottom: 0;
    height: 200px;
    overflow: hidden;
}

.post-card .post-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.post-card .post-content {
    padding: 25px;
}

.post-card .entry-title {
    font-size: 1.5rem;
    margin-bottom: 10px;
}

.post-card .entry-title a {
    color: #333;
    text-decoration: none;
}

.post-card .entry-title a:hover {
    color: #667eea;
}

.entry-summary {
    color: #666;
    margin-bottom: 20px;
}

.read-more {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.read-more:hover {
    text-decoration: underline;
}

/* 페이지네이션 - 글로벌 스타일 사용 */
.pagination {
    text-align: center;
    margin-top: 40px;
}

/* 검색 결과 없음 */
.no-results {
    background: white;
    padding: 60px 40px;
    text-align: center;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

/* 반응형 */
@media (max-width: 768px) {
    .posts-grid {
        grid-template-columns: 1fr;
    }
    
    article.post,
    article.page {
        padding: 25px;
    }
    
    .page-title {
        font-size: 2rem;
    }
}
</style>

<?php get_footer(); ?>