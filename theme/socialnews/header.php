<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<title><?php wp_title('&laquo;', true, 'right'); ?> <?php bloginfo('name'); ?></title>
<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>

<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div id="page" class="page">

	<span class="top_date"><?php echo date('l jS \of F Y'); ?> <?php bloginfo('description'); ?></span>

	<div id="header" >
		<div id="headerimg">
			<a href="<?php echo get_option('home'); ?>/"><img src="<?php echo get_bloginfo('template_directory'); ?>/images/logo.png" alt="<?php bloginfo('name'); ?>"></a>
		</div>
	</div>

	<?php wp_nav_menu( array( 'container_class' => 'main_menu', 'theme_location' => 'top-menu')); ?>

