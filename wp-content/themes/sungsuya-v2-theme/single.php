<?php
/**
 * 성수야! V2 - 단일 포스트 템플릿 (폴백)
 * 
 * 특정 포스트 타입 템플릿이 없을 때 사용되는 기본 템플릿
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 포스트 타입에 따라 적절한 템플릿으로 리다이렉트
if (is_singular('places')) {
    // single-places.php로 직접 로드
    $template = get_template_directory() . '/single-places.php';
    if (file_exists($template)) {
        include($template);
        exit;
    }
} elseif (is_singular('popup_store')) {
    // single-popup_store.php로 직접 로드
    $template = get_template_directory() . '/single-popup_store.php';
    if (file_exists($template)) {
        include($template);
        exit;
    }
}

// 기본 단일 포스트 템플릿
get_header(); ?>

<div class="site-content">
    <div class="container">
        <main id="main" class="site-main">
            
            <?php while (have_posts()) : the_post(); ?>
                
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
                
            <?php endwhile; ?>
            
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
    font-size: 2.5rem;
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

@media (max-width: 768px) {
    article.post,
    article.page {
        padding: 25px;
    }
    
    .entry-title {
        font-size: 2rem;
    }
}
</style>

<?php get_footer(); ?>