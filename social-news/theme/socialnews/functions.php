<?php

// Add the thumb support
add_theme_support('post-thumbnails');

// The widgets
function socialnews_widgets_init() {
	register_sidebar( array(
		'name' => __( 'Right Top Widget Area', 'socialnews' ),
		'id' => 'right-top-widget-area',
		'description' => __( 'Right Top Widget Area', 'socialnews' ),
		'before_widget' => '<div id="%1$s" class="widget-container %2$s">',
		'after_widget' => '</div>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );
}

// And add them
add_action('widgets_init', 'socialnews_widgets_init');

// Add the menus
add_action( 'init', 'register_my_menus' );

function register_my_menus(){
	register_nav_menus(
    	array('top-menu' => __( 'Top Menu' ))
    );
}

// Display all post-types on the 'home' and 'category' page
function get_all_post_types($query){
	if ( ( is_home() OR is_category() OR is_feed() ) && false == $query->query_vars['suppress_filters']){
		$query->set('post_type', array('post','sn'));
	}
	return $query;
}
add_filter('pre_get_posts','get_all_post_types');

/**
 * Template settings
 *
 */
$options = array (
	array("type" => "open"),
	array(
		"name" => "Use Facebook Connect",
		"desc" => "",
		"id" => "sn_use_facebook_connect",
		"std" => "false",
		"type" => "checkbox"),
	array(
		"name" => "Facebook API Key",
		"desc" => "",
		"id" => "sn_facebook_api_key",
		"std" => "",
		"type" => "text"),
	array(
		"name" => "Use reCaptcha",
		"desc" => "",
		"id" => "sn_use_recaptcha",
		"std" => "true",
		"type" => "checkbox"),
	array(
		"name" => "reCaptcha public key",
		"desc" => "",
		"id" => "sn_recaptcha_public_key",
		"std" => "",
		"type" => "text"),
	array(
		"name" => "reCaptcha private key",
		"desc" => "",
		"id" => "sn_recaptcha_private_key",
		"std" => "",
		"type" => "text"),
	array(
		"name" => "Succsess respons text",
		"desc" => "Shown after a user send us a text (Thank you for the text...)",
		"id" => "sn_response_text",
		"std" => "",
		"type" => "textarea"),
	array(
		"name" => "The editors email",
		"desc" => "So you will get a notification email after a new post is sent.",
		"id" => "sn_editor_email",
		"std" => "",
		"type" => "text"),
	array("type" => "close")
);

/**
 * Svae template settings
 */
function mytheme_add_admin() {
	global $shortname, $options;

	if($_GET['page'] == basename(__FILE__)){
    	if ('save' == $_REQUEST['action']){
    		foreach ($options as $value){
				update_option($value['id'], $_REQUEST[$value['id']]);
			}
			foreach ($options as $value){
            	if(isset($_REQUEST[$value['id']])){
            		update_option($value['id'], $_REQUEST[$value['id']]);
            	} else {
            		delete_option($value['id']);
            	}
            }
            header("Location: themes.php?page=functions.php&saved=true");
    	} else if('reset' == $_REQUEST['action']){

    		foreach ($options as $value){
				delete_option($value['id']);
			}
			header("Location: themes.php?page=functions.php&reset=true");

    	}
    }
    add_theme_page('Theme Options', 'Theme Options', 'edit_theme_options', basename(__FILE__), 'mytheme_admin');
}

function mytheme_admin() {
    global $shortname, $options;
    if ( $_REQUEST['saved'] ) echo '<div id="message" class="updated fade"><p><strong>Saved</strong></p></div>';
?>
<style type="text/css">td.thin { padding: 0 10px }</style>
<div class="wrap">
<form method="post">

<h2>Theme Options</h2>

<?php foreach ($options as $value) {
	switch ( $value['type'] ) {
		case "open":
?>
<table class="form-table sn">
	<?php break;
	case "close":
	?>
    </table><br />
	<?php break;
	case "title":
	?>
	<table class="form-table sn">
	<?php break;
		case 'text':
	?>
     <tr>
	     <td valign="top" width="20%" valign="middle"><strong><?php echo $value['name']; ?></strong></td>
         <td width="80%"><input style="width:600px;" name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" type="<?php echo $value['type']; ?>" value="<?php if ( get_settings( $value['id'] ) != "") { echo get_settings( $value['id'] ); } else { echo $value['std']; } ?>" /><br />
         <small><?php echo $value['desc']; ?></small></td>
	</tr>
	<?php break;
		case 'textarea':
	?>
     <tr>
	     <td valign="top" width="20%" valign="middle"><strong><?php echo $value['name']; ?></strong></td>
         <td width="80%"><textarea style="width:600px; height:140px;" name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" type="<?php echo $value['type']; ?>" /><?php if ( get_settings( $value['id'] ) != "") { echo get_settings( $value['id'] ); } else { echo $value['std']; } ?></textarea><br />
         <small><?php echo $value['desc']; ?></small></td>
	</tr>
	<?php break;
		case "checkbox":
	?>
	<tr>
		<td valign="top" width="20%"  valign="middle"><strong><?php echo $value['name']; ?></strong></td>
	    <td>
	    <?php
	    if(get_settings($value['id'])){
	    	$checked = "checked=\"checked\"";
	   	} else {
	   		$checked = "";
	   	} ?>
		<input type="checkbox" name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" value="true" <?php echo $checked; ?> />
		<small><?php echo $value['desc']; ?></small>
	 	</td>
	</tr>
    <?php break;
 	}
}
?>

</table>

<p class="submit">
	<input name="save" type="submit" value="Save Theme Options" />
	<input type="hidden" name="action" value="save" />
</p>
</form>
<?php
}
add_action('admin_menu', 'mytheme_add_admin');?>