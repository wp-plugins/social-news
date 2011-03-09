/**
 * The socialnews js-methods
 */
jQuery(document).ready(function() {
	
	// add socialnews
	jQuery("#add_socialnews").click(function() {
		jQuery('.spinner').show();
	
		// Clear msg tag and error-class
		jQuery('#msg').html('');	
		jQuery('.error').removeClass('error');

		// Get the fields
		var title = jQuery("#title").val();
		var body_text = jQuery("#body_text").val();
		var _wpnonce = jQuery("#_wpnonce").val();
		var post_type = jQuery("#post_type").val();
		var post_author_name = jQuery("#post_author_name").val();
		var post_facebook_id = jQuery("#post_facebook_id").val();
		var post_email = jQuery("#post_email").val();		
		var recaptcha_challenge_field = jQuery("input[name=recaptcha_challenge_field]").val();
		var recaptcha_response_field = jQuery("input[name=recaptcha_response_field]").val();
		
		jQuery.post(ajaxurl,{action: 'add_action', title: title, body_text: body_text, _wpnonce: _wpnonce, post_type: post_type, post_author_name: post_author_name, post_facebook_id: post_facebook_id, post_email: post_email, recaptcha_challenge_field: recaptcha_challenge_field, recaptcha_response_field: recaptcha_response_field }, function(response){		
			if(!response.success){
				if(response.recaptch_error === true){
					jQuery('#msg').append(response.recaptch_error_msg + "<br />");		
					Recaptcha.reload();
				} else {
					if(response.titleIsMissing !== false){
						jQuery('#title').addClass('error');
						jQuery('#msg').append(response.titleIsMissing + "<br />");
					}
					if(response.body_textIsMissing !== false){
						jQuery('#body_text').addClass('error');
						jQuery('#msg').append(response.body_textIsMissing + "<br />");
					}
				}
			} else {
				jQuery('#the_form #title').val('');
				jQuery('#the_form #body_text').val('');
				jQuery('#the_form #post_email').val('');
				jQuery('#the_form').html(response.successMsg);
				jQuery("html").animate({ scrollTop: 0 }, "slow");
			}
			jQuery('.spinner').hide();
		}, "json");
		return false;
	});
	
});
