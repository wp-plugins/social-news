<?php get_header(); ?>

<div class="content_left" id="firstpage">

		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

		<div <?php post_class() ?> id="post-<?php the_ID(); ?>">
			 <h3 class="storytitle"><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h3>

			<div class="storycontent">

				<?php if(has_post_thumbnail()){ ?>
				<a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>">
					<?php the_post_thumbnail(array(100,100),true); ?>
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

	<div class="fb-container">
	<?php 
		$sn_use_facebook_connect = get_option('sn_use_facebook_connect');
		$sn_facebook_api_key = get_option('sn_facebook_api_key');
		if($sn_use_facebook_connect && $sn_facebook_api_key){
		echo "<div id=\"fb-root\"></div>";
		echo "<script src=\"http://connect.facebook.net/en_US/all.js\"></script>";
		echo "<div id=\"fb\">";
		echo "<div class=\"fb_user_login_text\">Use your Facebook account to login. If you do, we can associate your facebook profile to your posts. <fb:login-button></fb:login-button></div>";
       	echo "</div>";
		}
	?>
	</div>
	<?php if($sn_use_facebook_connect && $sn_facebook_api_key){ ?>
    <script>
	FB.init({appId: '<?php echo $sn_facebook_api_key; ?>', status: true, cookie: true, xfbml: true});
	FB.Event.subscribe('auth.login', function(response) {
       	viewBox();
     	});
      	viewBox(); // Visa boxen om man är inloggad
       function logout() {
       	FB.logout();
           var user_box = document.getElementById("fb");
           user_box.innerHTML = "You signed out from Facebook. Please fill in your name and send your text.";
           jQuery('#post_author_name').val('');
           jQuery('.sn_post_author_fields').show();
     	}
       function viewBox() {
       	FB.getLoginStatus(function(response) {
           	if (response.session) {
               	var user_box = document.getElementById("fb");
                   user_box.innerHTML = "<fb:profile-pic uid='loggedinuser' facebook-logo='true'/></fb:profile-pic><strong>Hi</strong>, you are logged in as <fb:name uid='loggedinuser' useyou='false' ></fb:name>. Your text will be associated to your facebook profile. If you don't want that, please <a href=\"#\" onclick=\"logout();\">sign out</a>.<br clear=\"all\">";
                   FB.api(response.session.uid, function(response) {
                   	jQuery('#post_author_name').val(response.name);
                       jQuery('.sn_post_author_fields').hide();
                       jQuery('#the_hiddens').prepend('<input type="hidden" value="' + response.id + '" id="post_facebook_id">');
                 	});
            	} else {
               	jQuery('.sn_post_author_fields').show();
             	}
         	FB.XFBML.parse();
        	});
      	}
    </script>
    <?php } ?>

	<div class="get_published">
    	<h2>Get Published Now!</h2>
        <p>I want to write something please tell me how? Or send us a YouTube link?</p>
        <div class="button"><a href="/add/">Get Published</a></div>
 	</div>

	<?php if ( ! dynamic_sidebar( 'right-top-widget-area' ) ) : endif; ?>


</div><br clear="all"/>

<?php get_footer(); ?>

