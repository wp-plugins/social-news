<?php
class Socialnews {

	function __construct(){
		add_action('init', array( $this, 'createSocialnewsPostType'),1);
		add_action('init', array(&$this,'adTagsToSn'),2);
		add_action('admin_menu', array($this,'add_settings_menu'),1);
		add_action('add_meta_boxes_sn', array(&$this, 'addMetaBoxes')); // add the social news metabox
		add_action('save_post', array(&$this,'saveSocialnewsMetadata')); // save metabox data
		add_action('draft_to_publish', array(&$this,'notifyPostAuthor')); // Try to notify the post autor whem we publish
		add_filter('the_author', array(&$this,'get_sn_author_name')); // modify the author
		add_filter('the_posts', array(&$this,'add_news_form'));
		add_filter('pre_get_posts', array(&$this,'get_all_post_types'));
	}

	// Display all post-types on the 'home' and 'category' page
	function get_all_post_types($query){
		if ( ( is_home() OR is_category() OR is_feed() ) && false == $query->query_vars['suppress_filters']){
			$query->set('post_type', array('post','sn'));
		}
		return $query;
	}


	/**
	 * Add post_tags and categorys to the new sn post_type
	 */
	function adTagsToSn() {
		register_taxonomy_for_object_type('post_tag','sn');
		register_taxonomy_for_object_type('category','sn');
	}

	// Lägg till en submen i kalendern, för inställningar
	function add_settings_menu() {
		add_submenu_page('edit.php?post_type=sn', 'Settings', 'Settings', 'manage_options', __FILE__.'?option=one', array(&$this,'sn_settings'));
	}

	/**
	 * Create the socialnews post type
	 */
	function createSocialnewsPostType(){
		register_post_type('sn',
			array(
				'label' => 'Social News',
				'labels' => array ( 'add_new' => 'Create new', 'add_new_item' => 'Create new', 'edit_item' => 'Modify ', 'new_item' => 'Create new'),
				'public' => true,
				'show_ui' => true,
				'publicly_queryable' => true,
				'supports' => array('title','editor','thumbnail','author','comments'),
			)
		);
	}


	/**
	 * Save metabox data
	 */
	function saveSocialnewsMetadata($post_id){

		// Make sure data came from our meta box
		if (!wp_verify_nonce($_POST['my_meta_noncename'],__FILE__)) return $post_id;

		// Check user permissions
		if ($_POST['post_type'] == 'page'){
			if (!current_user_can('edit_page', $post_id)) return $post_id;
		} else {
			if (!current_user_can('edit_post', $post_id)) return $post_id;
		}

		// Authentication passed, save data
		$current_data = get_post_meta($post_id, '_my_meta', TRUE);
		$new_data = $_POST['_my_meta'];
		$this->my_meta_clean($new_data);

		if ($current_data) {
			if (is_null($new_data)) delete_post_meta($post_id,'_my_meta');
			else update_post_meta($post_id,'_my_meta',$new_data);
		} elseif (!is_null($new_data)) {
			add_post_meta($post_id,'_my_meta',$new_data,TRUE);
		}

		return $post_id;
	}


	// Clean method
	function my_meta_clean(&$arr){
		if (is_array($arr)){
			foreach ($arr as $i => $v){
				if (is_array($arr[$i])){
					my_meta_clean($arr[$i]);
					if (!count($arr[$i])){
						unset($arr[$i]);
					}
				} else {
					if (trim($arr[$i]) == '') {
						unset($arr[$i]);
					}
				}
			}
			if (!count($arr)) {
				$arr = NULL;
			}
		}
	}

	/**
	 * Add the metaboxes
	 */
	function addMetaBoxes($post) {
		add_meta_box('socialnews_metabox_fields', 'Social News Extras', array('Socialnews', 'socialnews_metabox_fields'), 'sn', 'normal', 'high');
	}

	/**
	 * Print the metabox
	 */
	function socialnews_metabox_fields($post) {
		global $post;

		// Fill the meta box if this is an update
		$meta = get_post_meta($post->ID,'_my_meta',TRUE);

		echo "<div class=\"my_meta_control\">";
		echo "<p>Here we collect all extra data that is connected to the Social News post type</code>.</p>";
		echo "<label>Author Name <span>(If not a registered wp user)</span></label>";
		echo "<p>";
		echo "<input type=\"text\" class=\"regular-text\" id=\"post_author_name\" name=\"_my_meta[sn_author_name]\" value=\""; if(!empty($meta['sn_author_name'])){echo $meta['sn_author_name'];} echo "\">";
		echo "</p>";
		echo "<label>Author Facebook ID <span>(If that exists)</span></label>";
		echo "<p>";
		echo "<input type=\"text\" class=\"regular-text\" id=\"post_facebook_id\" value=\""; if(!empty($meta['sn_facebook_id'])){echo $meta['sn_facebook_id'];} echo "\" name=\"_my_meta[sn_facebook_id]\">";
		echo "</p>";
		echo "<label>Author e-mail <span>(If that exists)</span></label>";
		echo "<p>";
		echo "<input type=\"text\" class=\"regular-text\" id=\"post_email\" value=\""; if(!empty($meta['sn_email'])){echo $meta['sn_email'];} echo "\" name=\"_my_meta[sn_email]\">";
		echo "</p>";
		echo "</div>";
		echo '<input type="hidden" name="my_meta_noncename" value="'.wp_create_nonce(__FILE__).'" />';

	}


	/**
	 * Run the reCaptcha validation test
	 */
	private function doRecaptchaValidation($remote_addr,$recaptcha_challenge_field,$recaptcha_response_field){
		require_once('recaptchalib.php');
		$sn_recaptcha_private_key = get_option('sn_recaptcha_private_key');
		$resp = recaptcha_check_answer($sn_recaptcha_private_key,$remote_addr,$recaptcha_challenge_field,$recaptcha_response_field);
		if (!$resp->is_valid) {
			$return = FALSE;
		} else {
			$return = TRUE;
  		}
  		return $return;
	}


	/**
	 * Add socialnews
	 */
	function addSocialNews($title,$body_text,$_wpnonce,$post_type,$post_author_name,$post_facebook_id,$post_email,$remote_addr = '',$recaptcha_challenge_field = '',$recaptcha_response_field = ''){

		$reCaptchIsValid = TRUE;

		// Check the reCaptch if that is used
		if($recaptcha_challenge_field){
			$reCaptchIsValid = $this->doRecaptchaValidation($remote_addr,$recaptcha_challenge_field,$recaptcha_response_field);
		}

		if($reCaptchIsValid === FALSE){
			$error['success'] = FALSE;
			$error['recaptch_error'] = TRUE;
			$error['recaptch_error_msg'] =  'You typed typed wrong captcha words';
			$return = json_encode($error);
			return $return;
			die;
		}

		$error = array();
		$error['titleIsMissing'] = $this->isMissing($title);
		$error['body_textIsMissing'] = $this->isMissing($body_text);
		$error = $this->checkTheAjaxErrors($error); // check the errors and die if there are any

		// 'Die' if we have errors, and return them to gui
		if(isset($error['success']) && $error['success'] === FALSE){
			$return = json_encode($error);
			return $return;
			die;
		}

		// Set admin to 'post_author' if missing
		if(empty($userID)){
			$userID = 1;
		}

		// Create post object
		$new_post = array();
		$new_post['post_title'] = esc_html($title);
		$new_post['post_content'] = wp_kses($body_text, $allowed_html = '');
		$new_post['post_status'] = 'draft';
		$new_post['post_type'] = $post_type;
		$new_post['post_author'] = $userID;
		$new_post['post_author_name'] = $post_author_name;
		$new_post['post_facebook_id'] = $post_facebook_id;
		$new_post['post_email'] = $post_email;

		// Save, wp will take care of cleaning the post data
		$post_id = $this->savePost($new_post);

		// If we have a post_id, we have success
		if($post_id){
			$error['success'] = TRUE;
			$error['successMsg'] = stripslashes(get_option('sn_response_text'));
			if(empty($error['successMsg'])){
				$error['successMsg'] = "Thank you for the text!";
			}
			$error['post_id'] = $post_id;
			$return = json_encode($error);
			$this->notifyAdmin('You have recieved a new Social News post, please login and review.

			// the team', $post_id);
		}

		return $return;
	}


	/**
	 * Cheack for 'TRUE' in the error array, TRUE meadns yes we have errors
	 */
	private function checkTheAjaxErrors($error){
		if(!empty($error)){
			foreach ($error as $item => $key){
				if($key == TRUE){
					$msg = $this->addErrorMsg($item);
					$error[$item] = $msg;
					$error['success'] = FALSE;
				}
			}
		}
		return $error;
	}


	/**
	 * Add a proper error message
	 */
	private function addErrorMsg($item){
		$error_msg['titleIsMissing'] = 'The text title is missing';
		$error_msg['body_textIsMissing'] = 'The body text is missing';
		return $error_msg[$item];
	}


	/**
	 * check is a field is missing
	 */
	private function isMissing($field = ''){
		$return = FALSE;
		$field = trim($field);
		if(empty($field)){
			$return = TRUE;
		}
		return $return;
	}


	/**
	 * Notify admin, send email to editor after a new post
	 */
	private function notifyAdmin($msg = '', $post_id){
		$to = get_option('sn_editor_email');
		$from = get_option('admin_email');
		$blogname = get_option('blogname');
		$subject = "Notification from ".$blogname;
		$headers = "From: ".$from."\nReply-To: ".$from;
		$config = "-f".$from;
		mail($to, $subject, $msg, $headers, $config);
	}


	/**
	 * Try to notify the post autor when we publish, if this is an social-post
	 */
	function notifyPostAuthor(){
		global $post;

		// Check the post_type, we only email the sn-posters
		$post_type = get_post_type($post);
		if($post_type == 'sn'){

			// Try to get the authors email, if added
			$meta = get_post_meta($post->ID,'_my_meta',TRUE);
			if($meta['sn_email']){
				$to = $meta['sn_email'];
				$from = get_option('admin_email');
				$blogname = get_option('blogname');
				$subject = $blogname." published your text";
				$msg = "Yes, we did publish your post\n\.And you will find it here: ".get_permalink($post->ID)."

				// The Editor";
				$headers = "From: ".$from."\nReply-To: ".$from;
				$config = "-f".$from;
				mail($to, $subject, $msg, $headers, $config);
			}
		}
	}


	/**
	 * Do the save, using 'wp_insert_post'
	 */
	private function savePost($new_post){
		$post_id = wp_insert_post($new_post);
		$meta_data = array(
			'sn_author_name' => $new_post['post_author_name'],
			'sn_facebook_id' => $new_post['post_facebook_id'],
			'sn_email' => $new_post['post_email']
		);
		add_post_meta($post_id, '_my_meta', $meta_data); // Add the neede meta-data
		return $post_id;
	}


	/**
	 * Prints the add form
	 */
	function add_news_form($posts){
		global $wp,$wp_query;

		$page_slug = 'add';
		$page_title = 'Add item';

		if(count($posts) == 0 && (strtolower($wp->request) == $page_slug || $wp->query_vars['page_id'] == $page_slug)){
			$post = new stdClass;
			$post->post_title = $page_title;
			$post->post_content = $this->printSocialNewsForm();
			$posts[] = $post;
			$wp_query->is_page = true;
		}

		return $posts;
	}


	/**
	 * Print the for where users add their news
	 */
	function printSocialNewsForm(){

		$return = "";

		// Get some options from the Template Options page
		$sn_use_facebook_connect = get_option('sn_use_facebook_connect');
		$sn_facebook_api_key = get_option('sn_facebook_api_key');
		$sn_use_recaptcha = get_option('sn_use_recaptcha');
		$sn_recaptcha_public_key = get_option('sn_recaptcha_public_key');

		$return .= "<div id=\"the_form\">";
		$return .= "<script type=\"text/javascript\">\n";
		$return .= "var ajaxurl = '".admin_url('admin-ajax.php')."'\n";
		$return .= "</script>\n";
		$return .= "<form id=\"post\" method=\"post\" action=\"#\" name=\"post\">\n";
		$return .= "<dl class=\"form_list\">";
		$return .= "<dt>";
		$return .= "<label for=\"post_title\">Title:</label>";
		$return .= "</dt>";
		$return .= "<dd>";
		$return .= "<input type=\"text\" id=\"title\" value=\"\" tabindex=\"1\" size=\"30\" name=\"post_title\" class=\"form_list_field\">";
		$return .= "</dd>";
		$return .= "<dt>";
		$return .= "<label for=\"body_text\">Text:</label>";
		$return .= "</dt>";
		$return .= "<dd>";
		$return .= "<textarea id=\"body_text\" name=\"body_text\" tabindex=\"2\" cols=\"50\" rows=\"10\" class=\"form_list_area_field\"></textarea><br />";
		$return .= "</dd>";
		if($sn_use_facebook_connect && $sn_facebook_api_key){
			$return .= "<dt>&nbsp;</dt>";
			$return .= "<dd>";
			$return .= "<div id=\"fb-root\"></div>";
			$return .= "<script src=\"http://connect.facebook.net/en_US/all.js\"></script>";
			$return .= "<div id=\"fb\">";
            $return .= "<div class=\"fb_user_login_text\"><fb:login-button></fb:login-button> Use your Facebook account to login. If you do, we can associate your facebook profile to your text.</div>";
        	$return .= "</div>";
 		    $return .= "</dd>";
		}
	    $return .= "<dt class=\"sn_post_author_fields\">";
		$return .= "<label for=\"post_author\">Name:</label>";
		$return .= "</dt>";
		$return .= "<dd class=\"sn_post_author_fields\">";
		$return .= "<input type=\"text\" id=\"post_author_name\" value=\"\" tabindex=\"3\" size=\"30\" name=\"post_author_name\" class=\"form_list_field short\">";
		$return .= "</dd>";
	    $return .= "<dt>";
		$return .= "<label for=\"post_email\">Your e-mail:</label>";
		$return .= "</dt>";
		$return .= "<dd>";
		$return .= "<input type=\"text\" id=\"post_email\" value=\"\" tabindex=\"3\" size=\"30\" name=\"post_email\" class=\"form_list_field short\"></span>";
		$return .= "</dd>";
		if($sn_use_facebook_connect && $sn_facebook_api_key){
			$return .= "<script>";
			$return .= "FB.init({appId: '".$sn_facebook_api_key."', status: true, cookie: true, xfbml: true});";
			$return .= "FB.Event.subscribe('auth.login', function(response) {";
			$return .= "viewBox();";
			$return .= "});";
			$return .= "viewBox();";
			$return .= "function logout() {";
			$return .= "FB.logout();";
			$return .= "var user_box = document.getElementById(\"fb\");";
			$return .= "user_box.innerHTML = \"You signed out from Facebook. Please fill in your name and send your text.\";";
			$return .= "jQuery('#post_author_name').val('');";
			$return .= "jQuery('.sn_post_author_fields').show();";
			$return .= "}";
			$return .= "function viewBox(){";
			$return .= "FB.getLoginStatus(function(response){";
			$return .= "if (response.session){";
			$return .= "var user_box = document.getElementById(\"fb\");";
			$return .= "user_box.innerHTML = \"<fb:profile-pic uid=\"loggedinuser\" facebook-logo=\"true\"/></fb:profile-pic>Hi, you are logged in as <fb:name uid=\"loggedinuser\" useyou=\"false\" ></fb:name>. Your text will be associated to your facebook profile. If you don't want that, please <a href=\"#\" onclick=\"logout();\">sign out</a>.<br clear=\"all\">\";";
			$return .= "FB.api(response.session.uid, function(response){";
			$return .= "jQuery('#post_author_name').val(response.name);";
			$return .= "jQuery('.sn_post_author_fields').hide();";
			$return .= " jQuery('#the_hiddens').prepend('<input type=\"hidden\" value=\"' + response.id + '\" id=\"post_facebook_id\">');";
			$return .= "});";
			$return .= "} else {";
			$return .= "jQuery('.sn_post_author_fields').show();";
			$return .= "}";
			$return .= "FB.XFBML.parse();";
			$return .= "});";
			$return .= "}";
			$return .= "</script>";
		}
		if($sn_recaptcha_public_key && $sn_use_recaptcha){
			require_once('recaptchalib.php');
			$return .= "<script type=\"text/javascript\">";
			$return .= "var RecaptchaOptions = {";
			$return .= "theme : 'clean'";
			$return .= "};";
			$return .= "</script>";
			$return .= "<dt>&nbsp;</dt>";
			$return .= "<dd>";
			$return .= recaptcha_get_html($sn_recaptcha_public_key);
			$return .= "</dd>";
		}
		$return .= "<dt>&nbsp;</dt>";
		$return .= "<dd id=\"the_hiddens\">";
		$return .= "<input type=\"hidden\" value=\"".$this->getTheNonce('social-news-nonce')."\" name=\"_wpnonce\" id=\"_wpnonce\">\n";
		$return .= "<input type=\"hidden\" value=\"".$this->getCurrentUri()."\" name=\"_wp_http_referer\">\n";
		$return .= "<input type=\"hidden\" value=\"sn\" name=\"post_type\" id=\"post_type\">\n";
		$return .= "<input type=\"submit\" value=\"Send your text\" tabindex=\"4\" id=\"add_socialnews\" class=\"basicsubmit\">";
		$return .= "<span class=\"spinner\"><img src=\"".get_bloginfo('template_directory')."/images/ajax-loader_white.gif\"></span>";
		$return .= "<br /><span id=\"msg\"></span>";
		$return .= "</dd>";
		$return .= "</dl>";
		$return .= "</form><br clear=\"all\">";
		$return .= "</div>";
		return $return;
	}


	/**
	 * Modify the author to use the sn_author_name, if exists
	 */
	function get_sn_author_name($name){
	  	global $post;
		$meta = get_post_meta($post->ID,'_my_meta',TRUE);
		if($meta['sn_author_name']){
			$name = $meta['sn_author_name'];
			if(is_single($post)){
				$name .= $this->printSocialFbUserImage($post->ID);
			}

		}
		return $name;
	}

	/**
	 * Print the auth on a singlepage
	 */
	function printSocialFbUserImage($post_id){

		// Try to get the post fb-id
		$meta = get_post_meta($post_id,'_my_meta',TRUE);
		if($meta['sn_facebook_id']){
			$fbid = $meta['sn_facebook_id'];
			$sn_facebook_api_key = get_option('sn_facebook_api_key');
			$return = "<div class=\"user_data_box\"></div>";
			$return .= "<div id=\"fb-root\"></div>";
			$return .= "<script src=\"http://connect.facebook.net/en_US/all.js\"></script>";
			$return .=  "<script>";
			$return .= "FB.init({appId: '".$sn_facebook_api_key."', status: true, cookie: true, xfbml: true});";
			$return .= "FB.api('/' + ".$fbid." + '/', function(response){";
			$return .= "if(response.name){";
			$return .= "jQuery('.user_data_box').prepend('<img src=\"http://graph.facebook.com/' + ".$fbid." + '/picture\" alt=\"avatar\" />');";
			$return .= "}";
			$return .= "});";
			$return .= "</script>";
		}
		return $return;
	}


	/**
	 * Save the settings
	 */
	function save_settings($options){
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
	}


	/**
	 * Display settings page
	 */
	function sn_settings(){

		// Options array, here we can add more in the future
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

		// If we have a post, pass the options and save
		if($_POST['save']){
			$this->save_settings($options);
			echo "<div class=\"updated\"><p><strong>Saved</strong></p></div>";
		}

		echo "<div class=\"wrap\">";
		echo "<div class=\"icon32\" id=\"icon-options-general\"><br></div>";
		echo "<h2>Settings</h2>";
		echo "<form method=\"post\">";

		foreach ($options as $value) {
			switch ($value['type']){
				case "open":
					echo "<table class=\"form-table\">";
				break;
				case "close":
					echo "</table>";
				break;
				case "text":
					echo "<tr valign=\"top\">";
					echo "<th scope=\"row\">".$value['name']."</th>";
					echo "<td width=\"80%\"><input class=\"regular-text\" name=\"".$value['id']."\" id=\"".$value['id']."\" type=\"".$value['type']."\" value=\"";
						if(get_settings($value['id']) != ""){
							echo get_settings($value['id']);
						} else {
							echo $value['std'];
						}
					echo "\"><br />";
					echo $value['desc']."</td>";
					echo "</tr>";
				break;
				case "textarea":
					echo "<tr valign=\"top\">";
					echo "<th scope=\"row\">".$value['name']."</th>";
					echo "<td width=\"80%\"><textarea name=\"".$value['id']."\" id=\"".$value['id']."\" rows=\"10\" class=\"large-text code\" type=\"".$value['type']."\">";
					if(get_settings($value['id']) != ""){
						echo stripslashes(get_settings($value['id']));
					} else {
						echo stripslashes($value['std']);
					}
					echo "</textarea><br />";
					echo $value['desc']."</td>";
					echo "</tr>";
				break;
				case "checkbox":
					echo "<tr valign=\"top\">";
					echo "<th scope=\"row\">".$value['name']."</th>";
					echo "<td>";
					if(get_settings($value['id'])){
						$checked = "checked=\"checked\"";
					} else {
						$checked = "";
					}
					echo "<input type=\"checkbox\" name=\"".$value['id']."\" id=\"".$value['id']."\" value=\"true\"".$checked.">";
					echo $value['desc']."</td>";
					echo "</tr>";
				break;
			}
		}
		echo "</table>";
		echo "<p class=\"submit\">";
		echo "<input type=\"submit\" value=\"Save Settings\" class=\"button-primary\" id=\"submit\" name=\"save\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"save\">";
		echo "</p>";
		echo "</form>";
		echo "</div>";
	}


	/**
	 * Return the nonce
	 */
	private function getTheNonce($key){
		return wp_create_nonce($key);
	}

	/**
	 * Return current uri
	 */
	private function getCurrentUri(){
		return $_SERVER['REQUEST_URI'];
	}

	/**
	 * activate the plugin
	 */
	function activate() {
		add_option('socialnews','Activated');
   	}

	/**
	 * deactivate the plugin
	 */
   	function deactivate() {
      delete_option('socialnews');
   	}

}