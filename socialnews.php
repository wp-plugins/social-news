<?php
/*
Plugin Name: SocialNews
Plugin URI: http://www.poetleaks.com/about-this/
Description: Let the vistiors add news to your site
Version: 1.2
Author: johannesfosseus
Author URI: http://www.fosseus.se
*/

include_once 'socialnews.class.php';

if (class_exists('socialnews')){
	$socialnews = new Socialnews();
}

// Load js only in backend
if(is_admin()){
	wp_enqueue_style('socialnews_css', WP_PLUGIN_URL.'/social-news/style.css');
}

// frontend
if(!is_admin()){
	wp_enqueue_script('socialnews_js', WP_PLUGIN_URL.'/social-news/socialnews.js', array('jquery'));
	wp_enqueue_style('socialnews_css', WP_PLUGIN_URL.'/social-news/style_frontend.css');
}

// Prints the add-form
function socialNewsForm(){
	global $socialnews;
	echo $socialnews->printSocialNewsForm();
}

/**
 * Register ajax call, stupied I have to register this twice...
 */
add_action('wp_ajax_add_action', 'socialnews_add_callback'); // to the singed in users
add_action('wp_ajax_nopriv_add_action', 'socialnews_add_callback'); // to the not sigend in users

// save new readernews post in the callback
function socialnews_add_callback() {
	global $socialnews;

	$title = $_POST['title'];
	$body_text = $_POST['body_text'];
	$_wpnonce = $_POST['_wpnonce'];
	$post_type = $_POST['post_type'];
	$post_author_name = $_POST['post_author_name'];
	$post_facebook_id = $_POST['post_facebook_id'];
	$post_email = $_POST['post_email'];

	// if we use reCaptcha, get the fields
	if($_POST["recaptcha_challenge_field"]){
		$remote_addr = $_SERVER["REMOTE_ADDR"];
		$recaptcha_challenge_field = $_POST["recaptcha_challenge_field"];
		$recaptcha_response_field = $_POST["recaptcha_response_field"];
	}

	$result = $socialnews->addSocialNews($title,$body_text,$_wpnonce,$post_type,$post_author_name,$post_facebook_id,$post_email,$remote_addr,$recaptcha_challenge_field,$recaptcha_response_field);
	echo $result;
	die;
}


// set an 'active/inactive' option
if (isset($socialnews)) {
   register_activation_hook(__FILE__, array($socialnews,'activate'));
   register_deactivation_hook(__FILE__, array($socialnews, 'deactivate'));
}
