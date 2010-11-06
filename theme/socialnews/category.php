<?php get_header(); ?>

<div class="content_left" id="firstpage">

		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

		<div <?php post_class() ?> id="post-<?php the_ID(); ?>">
			 <h3 class="storytitle"><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h3>

			<div class="storycontent">

				<?php if(has_post_thumbnail()){ ?>
				<a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>">
					<?php the_post_thumbnail(array(100,100),true); // skär ner bilden och cacha ?>
				</a>
				<?php } ?>


				<a href="<?php the_permalink() ?>" rel="bookmark">
				<?php the_content(''); ?>
				</a>

				<div class="meta"><?php _e("Filed under:"); ?> <?php the_category(',') ?> <?php the_tags(__('Tags: '), ', '); ?> <?php _e("By:"); ?> <?php the_author() ?> @ <?php the_time() ?> <?php edit_post_link(__('Edit This')); ?></div>

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

<div class="content_right">

	<div class="get_published">
    	<h2>Get Published Now!</h2>
        <p>I want to write something please tell me how? Or send us a YouTube link?</p>
        <div class="button"><a href="/add/">Get Published</a></div>
 	</div>

</div><br clear="all"/>

<?php get_footer(); ?>