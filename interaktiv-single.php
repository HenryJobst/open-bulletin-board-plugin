<?php
/**
 * The Template for displaying all single posts.
 *
 * @package vantage
 * @since vantage 1.0
 * @license GPL 2.0
 */

get_header(); ?>

<div id="primary" class="content-area">
	<div id="content" class="site-content" role="main">

	<?php while ( have_posts() ) : the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class('post'); ?>>

        <div class="entry-main">

            <?php do_action('vantage_entry_main_top') ?>

            <?php

                function get_column($preamble, $column) {
                    $value = the_column(get_the_ID(), false, $column);
                    if (isset($value)):
                        return sprintf('%s', $preamble . esc_html($value));
                    endif;
                    return '';
                }

                $title = the_title('', '', false);
                $name = get_column('', 'name');
                $homepage = get_column('', 'url');
                $phone = get_column('', 'phone');
                $email = get_column('', 'email');
                $location = get_column('', 'location');

                if (!isset($title)):
                    $title = '<div itemscope itemtype="http://schema.org/Person">';
                    if ($name != ''):
                        $title .= '<span class="interactive-entry-title-name" itemprop="name"><strong>' .
                            $name . '</strong></span>';
                    endif;
                    if ($homepage != ''):
                        if ($name != ''):
                            $title .= '&nbsp;';
                        endif;
                        $title .= '<span itemprop="url">[<a class="interaktiv-entry-title-url" href="' .
                            $homepage . '">' . __('Seite') . '</a>]</span>';
                    endif;
                    if ($phone != ''):
                        if ($name != '' || $homepage != ''):
                            $title .= '&nbsp;';
                        endif;
                        $title .= '<span itemprop="telephone">[<a class="interaktiv-entry-title-phone" href="tel:' .
                            $phone . '">' . __('Telefon') . '</a>]</span>';
                    endif;
                    if ($email != ''):
                        if ($name != '' || $homepage != '' || $phone != ''):
                            $title .= '&nbsp;';
                        endif;
                        $title .= '<span itemprop="email">[<a class="interaktiv-entry-title-email" href="mailto:' .
                            $email . '">' . __('E-Mail') . '</a>]</span>';
                    endif;
                    $title .= '<span class="alignright">';
                    if ($location != ''):
                        $title .= $location . ', ';
                    else:
                        $title .= '&nbsp';
                    endif;
                    $title .= the_date('','', '', false);
                    $title .= '</span>';
                    $title .= '</div>';
                endif;
                ?>

                <?php if (
                   ( $title && siteorigin_page_setting( 'page_title' ) )
                    || ( has_post_thumbnail() && siteorigin_setting( 'blog_featured_image' ) )
                    || ( siteorigin_setting( 'blog_post_metadata' ) && get_post_type() == 'post' ) ) : ?>
                     <header class="entry-header">

                    <?php if( has_post_thumbnail() && siteorigin_setting('blog_featured_image') ): ?>
                        <div class="entry-thumbnail"><?php vantage_entry_thumbnail(); ?></div>
                    <?php endif; ?>

                    <?php if ( $title && siteorigin_page_setting( 'page_title' ) ) : ?>
                        <h1 class="interaktiv-entry-title"><?php echo $title; ?></h1>
                    <?php endif; ?>

                    <?php if ( siteorigin_setting( 'blog_post_metadata' ) && get_post_type() == 'post' ) : ?>
                        <div class="entry-meta">
                            <?php vantage_posted_on(); ?>
                        </div><!-- .entry-meta -->
                    <?php endif; ?>

                </header><!-- .entry-header -->
            <?php endif; ?>

            <div class="entry-content">
                <?php the_content(); ?>
                <?php wp_link_pages( array( 'before' => '<div class="page-links">' . __( 'Pages:', 'vantage' ), 'after' => '</div>' ) ); ?>
            </div><!-- .entry-content -->

            <?php if( vantage_get_post_categories() && ! is_singular( 'jetpack-testimonial' ) ) : ?>
                <div class="entry-categories">
                    <?php echo vantage_get_post_categories() ?>
                </div>
            <?php endif; ?>

            <?php if ( is_singular() && siteorigin_setting( 'blog_author_box' ) ) vantage_author_box( $post ); ?>

            <?php do_action('vantage_entry_main_bottom') ?>

        </div>
        </article><!-- #post-<?php the_ID(); ?> -->

		<?php if ( siteorigin_setting( 'navigation_post_nav' ) ) vantage_content_nav( 'nav-below' ); ?>

		<?php if ( ! is_attachment() && siteorigin_setting( 'blog_related_posts' ) ) {
			vantage_related_posts( $post->ID );
		} ?>

		<?php if ( comments_open() || '0' != get_comments_number() ) : ?>
			<?php comments_template( '', true ); ?>
		<?php endif; ?>

	<?php endwhile; // end of the loop. ?>

	</div><!-- #content .site-content -->
</div><!-- #primary .content-area -->

<?php get_sidebar(); ?>

<?php get_footer(); ?>