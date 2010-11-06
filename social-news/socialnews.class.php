<?php
class Socialnews {

	function __construct(){
		add_action('init', array( $this, 'createSocialnewsPostType'),1);
		add_action('init', array(&$this,'adTagsToSn'),2);
		add_action('add_meta_boxes_sn', array(&$this, 'addMetaBoxes')); // add the social news metabox
		add_action('save_post', array(&$this,'saveSocialnewsMetadata')); // save metabox data
		add_action('draft_to_publish', array(&$this,'notifyPostAuthor')); // Try to notify the post autor whem we publish
		add_filter('the_author', array(&$this,'get_sn_author_name')); // modify the author
	}

	/**
	 * Add post_tags and categorys to the new sn post_type
	 */
	function adTagsToSn() {
		register_taxonomy_for_object_type('post_tag','sn');
		register_taxonomy_for_object_type('category','sn');
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
			$error['successMsg'] = get_option('sn_response_text');
			if(empty($error['successMsg'])){
				$error['successMsg'] = "Thank you for the text!";
			}
			$error['post_id'] = $post_id;
			$return = json_encode($error);
			$this->notifyAdmin('You have recieved a new Social News post, please login and review. \n\n// the team', $post_id);
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
				$msg = "Yes, we did publish your post\n\.And you will find it here: ".get_permalink($post->ID)."\n\n/ The Team";
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
	 * Print the for where users add their news
	 */
	function printSocialNewsForm(){

		// Get some options from the Template Options page
		$sn_use_facebook_connect = get_option('sn_use_facebook_connect');
		$sn_facebook_api_key = get_option('sn_facebook_api_key');
		$sn_use_recaptcha = get_option('sn_use_recaptcha');
		$sn_recaptcha_public_key = get_option('sn_recaptcha_public_key');
		
		echo "<div id=\"the_form\">";
		echo "<script type=\"text/javascript\">\n";
		echo "var ajaxurl = '".admin_url('admin-ajax.php')."'\n";
		echo "</script>\n";
		echo "<form id=\"post\" method=\"post\" action=\"#\" name=\"post\">\n";
		echo "<dl class=\"form_list\">";
		echo "<dt>";
			echo "<label for=\"post_title\">Title:</label>";
		echo "</dt>";
		echo "<dd>";
			echo "<input type=\"text\" id=\"title\" value=\"\" tabindex=\"1\" size=\"30\" name=\"post_title\" class=\"form_list_field\">";
		echo "</dd>";
		echo "<dt>";
			echo "<label for=\"body_text\">Text:</label>";
		echo "</dt>";
		echo "<dd>";
			echo "<textarea id=\"body_text\" name=\"body_text\" tabindex=\"2\" cols=\"50\" rows=\"10\" class=\"form_list_area_field\"></textarea><br />";
		echo "</dd>";
		if($sn_use_facebook_connect && $sn_facebook_api_key){
			echo "<dt>&nbsp;</dt>";
			echo "<dd>";
			echo "<div id=\"fb-root\"></div>";
			echo "<script src=\"http://connect.facebook.net/en_US/all.js\"></script>";
			echo "<div id=\"fb\">";
            echo "<div class=\"fb_user_login_text\"><fb:login-button></fb:login-button> Use your Facebook account to login. If you do, we can associate your facebook profile to your text.</div>";
        	echo "</div>";
 		    echo "</dd>";
		}
	    echo "<dt class=\"sn_post_author_fields\">";
			echo "<label for=\"post_author\">Name:</label>";
		echo "</dt>";
		echo "<dd class=\"sn_post_author_fields\">";
			echo "<input type=\"text\" id=\"post_author_name\" value=\"\" tabindex=\"3\" size=\"30\" name=\"post_author_name\" class=\"form_list_field short\">";
		echo "</dd>";
	    echo "<dt>";
			echo "<label for=\"post_email\">Your e-mail:</label>";
		echo "</dt>";
		echo "<dd>";
			echo "<input type=\"text\" id=\"post_email\" value=\"\" tabindex=\"3\" size=\"30\" name=\"post_email\" class=\"form_list_field short\"><br /><span class=\"small\">(So we can tell you when we publish the text)</span>";
		echo "</dd>";
		if($sn_use_facebook_connect && $sn_facebook_api_key){
		?>
   	    <script>
		FB.init({appId: '<?php echo $sn_facebook_api_key; ?>', status: true, cookie: true, xfbml: true});
        FB.Event.subscribe('auth.login', function(response) {
        	viewBox();
      	});
       	viewBox();
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
                    user_box.innerHTML = "<fb:profile-pic uid='loggedinuser' facebook-logo='true'/></fb:profile-pic>Hi, you are logged in as <fb:name uid='loggedinuser' useyou='false' ></fb:name>. Your text will be associated to your facebook profile. If you don't want that, please <a href=\"#\" onclick=\"logout();\">sign out</a>.<br clear=\"all\">";
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
		<?php
		}
		if($sn_recaptcha_public_key && $sn_use_recaptcha){
			require_once('recaptchalib.php');
			echo "<script type=\"text/javascript\">";
			echo "var RecaptchaOptions = {";
			echo "theme : 'clean'";
			echo "};";
			echo "</script>";
			echo "<dt>&nbsp;</dt>";
			echo "<dd>";
				echo recaptcha_get_html($sn_recaptcha_public_key);
			echo "</dd>";
		}
		echo "<dt>&nbsp;</dt>";
		echo "<dd id=\"the_hiddens\">";
			echo "<input type=\"hidden\" value=\"".$this->getTheNonce('social-news-nonce')."\" name=\"_wpnonce\" id=\"_wpnonce\">\n";
			echo "<input type=\"hidden\" value=\"".$this->getCurrentUri()."\" name=\"_wp_http_referer\">\n";
			echo "<input type=\"hidden\" value=\"sn\" name=\"post_type\" id=\"post_type\">\n";
			echo "<input type=\"submit\" value=\"Send your text\" tabindex=\"4\" id=\"add_socialnews\" class=\"basicsubmit\">";
			echo "<span class=\"spinner\"><img src=\"".get_bloginfo('template_directory')."/images/ajax-loader_white.gif\"></span>";
			echo "<br /><span id=\"msg\"></span>";
		echo "</dd>";
		echo "</dl>";
		echo "</form><br clear=\"all\">";
		echo "</div>";
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
				$name .= $this->print>ocialFbUserImage($post->ID);
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