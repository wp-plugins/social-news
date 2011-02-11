<?php
/*
Template Name: Add
*/
?>
<?php get_header(); ?>

<div class="content_add_left">
	<h2>Get Published!</h2>
	<p>This is where you can add you news to this site. If you login using Facebook we can add your profile picture to your article. All submitted articles will be reviewed by an editor before publishing.</p>
</div>

<div class="content_add_right">
<?php if (function_exists('socialNewsForm')){
	socialNewsForm();
}?>
</div>

<br clear="all"/>

<?php get_footer(); ?>