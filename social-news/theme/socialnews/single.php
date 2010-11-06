<?php get_header(); ?>

<div class="content_single_left" id="single">

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<div <?php post_class() ?> id="post-<?php the_ID(); ?>">

	 <h3 class="storytitle"><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h3>

	<div class="storycontent">
		<?php the_content(__('(more...)')); ?>
	</div>

	<div class="user_metadata">
		<?php the_author() ?>
	</div>

	<div class="feedback">
		<?php wp_link_pages(); ?>
		<?php comments_popup_link(__('Comments (0)'), __('Comments (1)'), __('Comments (%)')); ?>
	</div>

</div>

<?php comments_template(); // Get wp-comments.php template ?>

<?php endwhile; else: ?>
<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
<?php endif; ?>

<?php posts_nav_link(' &#8212; ', __('&laquo; Newer Posts'), __('Older Posts &raquo;')); ?>

</div>

<div class="content_single_right">

	<div class="get_published">
    	<h2>Get Published Now!</h2>
        <p>I want to write something please tell me how?</p>
        <div class="button"><a href="/add/">Get Published</a></div>
 	</div>

</div><br clear="all"/>

<?php get_footer(); ?>