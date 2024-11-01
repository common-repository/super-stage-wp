var WPSS_STATGING_PATH = '';
jQuery(document).ready(function($) {

	//restart staging process
	is_staging_need_request_wpss();

	if (window.location.href.indexOf('wpss-main-page') !== -1) {
		jQuery('#wpfooter').remove();
	}

	jQuery('body').on('click', '#yes_delete_site_wpss', function (){
		delete_staging_site_wpss();
	});

	jQuery('body').on('click', '#delete_staging_wpss, #no_delete_site_wpss', function (){
		// jQuery("#staging_delete_options_wpss").toggle();
		// jQuery("#delete_staging_wpss").toggle();

		jQuery('.wpss_dialog_wrapper').show();
		jQuery('.wpss_dialogs').hide();
		jQuery('.delete_staging_dialog_wpss').show();
	});

	jQuery('body').on('click', '.wpss_dialogs .yes_delete_staging_dialog_wpss', function (e){
		delete_staging_site_wpss();
		close_wpss_dialogs();
	});

	jQuery('body').on('click', '#edit_staging_wpss', function (){

		if(jQuery(this).hasClass('disabled')){
			return false;
		}

		choose_staging_wpss(true);
		jQuery('.staging_heading_wpss').text('Edit Your Staging Site');
		jQuery('.edit_staging_cancel_wpss').show();
		jQuery('#same_server_path_staging_wpss').val(WPSS_STATGING_PATH);
	});

	jQuery('body').on('click', '#ask_push_to_live_wpss', function (e){
		window.open(jQuery(this).attr('href'), '_blank').focus();
	});

	jQuery('body').on('click', '#ask_copy_staging_wpss', function (e){
		if (jQuery(this).hasClass('disabled')) {
			return false;
		}

		jQuery('.wpss_dialog_wrapper').show();
		jQuery('.wpss_dialogs').hide();
		jQuery('.copy_live_to_staging_dialog_wpss').show();


		return false;

	});

	jQuery('body').on('click', '.wpss_dialog_wrapper', function (e){
		close_wpss_dialogs();
	});

	jQuery('body').on('click', '.wpss_dialogs', function (e){
		return false;
	});
	jQuery('body').on('click', '.wpss_dialogs .cancel', function (e){
		close_wpss_dialogs();
	});

	jQuery('body').on('click', '.wpss_dialogs .yes_copy_live_to_staging_dialog_wpss', function (e){
		select_copy_staging_type_wpss(true);
		close_wpss_dialogs();
	});

	jQuery('body').on('click', '#copy_staging_wpss', function (e){
		select_copy_staging_type_wpss(true);
	});

	jQuery('body').on('click', '#resume_staging_wpss', function (e){
		resume_count_wpss = 0;

		resume_staging_auto_click_wpss();
	});

	jQuery('body').on('click', '.yes_stop_staging_dialog_wpss', function (e){
		wpss_stop_staging_confirmed();
		
	});

	jQuery('body').on('click', '#stop_staging_wpss', function (e){
		jQuery('.wpss_dialog_wrapper').show();
		jQuery('.wpss_dialogs').hide();
		jQuery('.stop_staging_dialog_wpss').show();		


		// swal({
		// 	title              : wpss_get_dialog_header('Stop staging process?'),
		// 	html               : wpss_get_dialog_body('Clicking on Yes will delete your current staging site. Are you sure want to continue ?', ''),
		// 	padding            : '0px 0px 10px 0',
		// 	buttonsStyling     : false,
		// 	showCancelButton   : true,
		// 	confirmButtonColor : '',
		// 	cancelButtonColor  : '',
		// 	confirmButtonClass : 'button-primary wtpc-button-primary',
		// 	cancelButtonClass  : 'button-secondary wtpc-button-secondary',
		// 	confirmButtonText  : 'Yes',
		// 	cancelButtonText   : 'Cancel',
		// }).then(function () {
		// 	wpss_stop_staging_confirmed();
		// });

	});

	jQuery('body').on('click', '#refresh-s-status-area-wtpc', function (e){
		if (jQuery(this).hasClass('disabled')) {
			return false;
		}
		get_staging_details_wpss();
	});


	jQuery("body").on("click", ".upgrade-plugins-staging-wpss" ,function(e) {
		handle_plugin_upgrade_request_wpss(this, e , false , true, 'plugin');
	});

	jQuery("body").on("click", ".update-link-plugins-staging-wpss, .update-now-plugins-staging-wpss" ,function(e) {
		handle_plugin_themes_link_request_wpss(this, e, false, true , 'plugin');
	});

	jQuery("body").on("click", ".update-link-themes-staging-wpss, .update-now-themes-staging-wpss" ,function(e) {
		handle_plugin_themes_link_request_wpss(this, e, false, true , 'theme');
	});

	jQuery("body").on("click", ".button-action-plugins-staging-wpss" ,function(e) {
		handle_plugin_themes_button_action_request_wpss(this, e , false, true, 'plugin');
	});

	jQuery("body").on("click", ".button-action-themes-staging-wpss" ,function(e) {
		handle_plugin_themes_button_action_request_wpss(this, e , false, true, 'theme');
	});

	jQuery("body").on("click", ".upgrade-themes-staging-wpss" ,function(e) {
		handle_themes_upgrade_request_wpss(this, e , false, true);
	});

	jQuery("body").on("click", ".upgrade-core-staging-wpss" ,function(e) {
		handle_core_upgrade_request_wpss(this, e , false, true);
	});

	jQuery("body").on("click", ".upgrade-translations-staging-wpss" ,function(e) {
		handle_translation_upgrade_request_wpss(this, e , false, true);
	});

	jQuery('body').on("click", '.plugin-update-from-iframe-staging-wpss', function(e) {
		handle_iframe_requests_wpss(this, e , false, true);
	});

	jQuery('body').on("click", '#same_server_submit_wpss', function(e) {
		jQuery('#internal_staging_error_wpss').html('');
		if(jQuery(this).hasClass('disabled')){
			return false;
		}

		jQuery(this).prop('disabled', true);
		jQuery(this).text('Processing...');
		var path = jQuery('#same_server_path_staging_wpss').val();
		if(path.length < 1){
			jQuery('#internal_staging_error_wpss').html('Error : Staging path cannot be empty.');
			jQuery('#same_server_submit_wpss').text('Stage Now').prop('disabled', false);
			return false;
		}

		let settings = {
			load_images_from_live_site: jQuery('input[name="load_images_from_live_site_settings_radio_wpss"]:checked').val(),
		};

		wpss_start_staging(path, settings);

	});

	jQuery('body').on("click", '#select_same_server_wpss', function(e) {
		if(jQuery(this).hasClass('disabled')){
			return false;
		}
		same_server_wpss();
	});

	jQuery('input[name="enable_admin_login_wpss"]').on("click", function(e) {
		if(jQuery(this).val() === 'yes'){
			jQuery('#login_custom_link').show();
		} else {
			jQuery('#login_custom_link').hide();
		}
	});
	jQuery('body').on("click", '.edit_staging_cancel_wpss', function(e) {
		window.location.reload();
	});
	jQuery("#wpss_save_changes").on("click", function() {
		if (jQuery(this).hasClass('disabled')) {
			return false;
		}
		jQuery(this).addClass('disabled').attr('disabled', 'disabled').val('Saving new changes...').html('Saving...');
		save_staging_settings_wpss();
		return false;
	});
});
function close_wpss_dialogs(){
	if (jQuery('.yes_stop_staging_dialog_wpss').hasClass('disabled')) {
		return false;
	}
	
	jQuery('.wpss_dialogs').hide();
	jQuery('.wpss_dialog_wrapper').hide();
}
function save_staging_settings_wpss(){
	var db_rows_clone_limit_wpss     = jQuery("#db_rows_clone_limit_wpss").val();
	var files_clone_limit_wpss       = jQuery("#files_clone_limit_wpss").val();
	var deep_link_replace_limit_wpss = jQuery("#deep_link_replace_limit_wpss").val();
	var enable_admin_login_wpss      = jQuery('input[name=enable_admin_login_wpss]:checked').val();
	var reset_permalink_wpss         = jQuery('input[name=reset_permalink_wpss]:checked').val();
	var login_custom_link_wpss       = jQuery('#login_custom_link_wpss').val();
	var user_excluded_extenstions_staging       = jQuery('#user_excluded_extenstions_staging').val();
	var load_images_from_live_site_settings_radio_wpss         = jQuery('input[name=load_images_from_live_site_settings_radio_wpss]:checked').val();

	var request_params = {
			"db_rows_clone_limit_wpss"     : db_rows_clone_limit_wpss,
			"files_clone_limit_wpss"       : files_clone_limit_wpss,
			"deep_link_replace_limit_wpss" : deep_link_replace_limit_wpss,
			"enable_admin_login_wpss"      : enable_admin_login_wpss,
			"reset_permalink_wpss"         : reset_permalink_wpss,
			"login_custom_link_wpss"       : login_custom_link_wpss,
			"user_excluded_extenstions_staging"       : user_excluded_extenstions_staging,
			"load_images_from_live_site_settings_wpss"       : load_images_from_live_site_settings_radio_wpss,
	};

	jQuery.post(ajaxurl, {
		security: wpss_ajax_object.ajax_nonce,
		action: 'save_staging_settings_wpss',
		dataType: "json",
		data: request_params,
	}, function(data) {
		// wpss_process_init_request(data);

		enable_save_settings_button_wpss();
	});
}

function enable_save_settings_button_wpss(){
	jQuery("#wpss_save_changes").removeAttr('disabled').removeClass('disabled').val("Save Changes").html("Save");
}

function resume_staging_auto_click_wpss(){
	if(jQuery('#resume_staging_wpss').is(":visible")){
		jQuery('#resume_staging_wpss').hide();
		jQuery('#staging_progress_bar_note_wpss').css('color', 'black');
		wpss_continue_staging();
	}
}

function wpss_stop_staging_confirmed(){
	jQuery('#resume_staging_wpss').hide();
	if (jQuery('.yes_stop_staging_dialog_wpss').hasClass('disabled')) {
		return false;
	}
	jQuery('.yes_stop_staging_dialog_wpss').text('Stopping...').addClass('disabled');
	stop_staging_wpss();
	bp_in_progress = false;
}

function copy_staging_wpss(){
	jQuery.post(ajaxurl, {
		security: wpss_ajax_object.ajax_nonce,
		action: 'copy_staging_wpss',
		dataType: "json",
	}, function(data) {
		wpss_process_init_request(data);
	});
}

function is_staging_need_request_wpss(){

	if(typeof wpss_ajax_object == 'undefined'){
		
		return;
	}

	jQuery.post(ajaxurl, {
		security: wpss_ajax_object.ajax_nonce,
		action: 'is_staging_need_request_wpss',
		dataType: "json",
	}, function(data) {
		try{
			var data = jQuery.parseJSON(data);
		} catch(err){
			return ;
		}

		if(typeof data == 'undefined' || typeof data.status == 'undefined' || typeof data.check_again == 'undefined'){
			return false;
		}

		if(data.status) {
			get_staging_details_wpss();
			return false;
		}
		if(data.check_again){
			if (typeof is_staging_need_request_var_wpss != 'undefined') {
				delete is_staging_need_request_var_wpss;
			}

			is_staging_need_request_var_wpss = setTimeout(function(){
				is_staging_need_request_wpss();
			}, 10000)

			get_staging_details_wpss(true);

			return false;
		}

		if (typeof is_staging_need_request_var_wpss != 'undefined') {
			delete is_staging_need_request_var_wpss;
		}

		get_staging_details_wpss(true);
	});
}

function wpss_start_staging(path, settings){
	jQuery.post(ajaxurl, {
		security: wpss_ajax_object.ajax_nonce,
		action: 'start_fresh_staging_wpss',
		path: path,
		settings: settings,
		dataType: "json",
	}, function(data) {
		wpss_process_init_request(data);
	});
}

function wpss_process_init_request(data){
	jQuery('#same_server_submit_wpss').text('Stage Now').prop('disabled', false);
	try{
		var data = jQuery.parseJSON(data);
		if(data.status === 'continue'){
			if (typeof wpss_redirect_to_staging_page !== 'undefined' && jQuery.isFunction(wpss_redirect_to_staging_page)) {
				wpss_redirect_to_staging_page();
			}
			wpss_staging_in_progress();
			wpss_continue_staging();
		} else if(data.status === 'error'){
			jQuery('#internal_staging_error_wpss').html('Error: '+ data.msg);
		} else {
			jQuery('#internal_staging_error_wpss').html('Error: Something went wrong, try again.');
		}
	} catch(err){
		// alert("Cannot make ajax calls");
		jQuery('#internal_staging_error_wpss').html('Error: Something went wrong, try again.');
	}
}

function wpss_continue_staging(){
	jQuery.ajax({
		url: ajaxurl,
		method: "POST",
		dataType: "json",
		timeout: 30000,
		data: {
			security: wpss_ajax_object.ajax_nonce,
			action: 'continue_staging_wpss',
		}
	}).done(function(data) {
		jQuery('#same_server_submit_wpss').text('Stage Now').prop('disabled', false);
		try{
			// var data = jQuery.parseJSON(data);
			if(typeof data.percentage != 'undefined' && data.percentage){
				jQuery('.staging_progress_bar_wpss .inside').css('width', data.percentage+'%');
			}
			if(data.status === 'continue'){
				jQuery("#staging_progress_bar_note_wpss").html(data.msg);
				wpss_continue_staging();
			} else if(data.status === 'error'){
				jQuery("#staging_progress_bar_note_wpss").html('Error: '+ data.msg);
			} else if(data.status === 'success'){
				get_staging_details_wpss();
				if (data.is_restore_to_staging) {
					start_restore_wpss(null, null, null, null, true);
				}
			} else {
				jQuery("#staging_progress_bar_note_wpss").html('Error: Something went wrong, please click the resume button.').css('color', '#ca4a1f');
				jQuery('#resume_staging_wpss').show();

				if(typeof resume_count_wpss == 'undefined'){
					resume_count_wpss = 0;
				}

				if(resume_count_wpss > 9){

					return;
				}

				resume_count_wpss++;

				setTimeout(function(){
					resume_staging_auto_click_wpss();
				}, 180000);
			}
		} catch(err){
			jQuery("#staging_progress_bar_note_wpss").html('Error: Something went wrong, please click the resume button.').css('color', '#ca4a1f');
			jQuery('#resume_staging_wpss').show();

			if(typeof resume_count_wpss == 'undefined'){
				resume_count_wpss = 0;
			}

			if(resume_count_wpss > 9){

				return;
			}

			resume_count_wpss++;

			setTimeout(function(){
				resume_staging_auto_click_wpss();
			}, 180000);
		}
	}). fail(function(request, textStatus) {
		if(textStatus == 'timeout'){
			if(typeof staging_timeout_count_wpss == 'undefined'){
				staging_timeout_count_wpss = 0;
			}
			staging_timeout_count_wpss++;

			if(staging_timeout_count_wpss < 50){
				wpss_continue_staging();

				return;
			}
		}
		jQuery('#resume_staging_wpss').show();

		if(typeof resume_count_wpss == 'undefined'){
			resume_count_wpss = 0;
		}

		if(resume_count_wpss > 9){

			return;
		}

		resume_count_wpss++;

		setTimeout(function(){
			resume_staging_auto_click_wpss();
		}, 180000);

		get_staging_current_status_key_wpss(request.status, request.statusText);
	});
}

function get_staging_current_status_key_wpss(status, statusText){
		jQuery.post(ajaxurl, {
		security: wpss_ajax_object.ajax_nonce,
		action: 'get_staging_current_status_key_wpss',
		dataType: "json",
	}, function(data) {
		jQuery('#same_server_submit_wpss').text('Stage Now').prop('disabled', false);
		try{
			var data = jQuery.parseJSON(data);
			jQuery("#staging_progress_bar_note_wpss").html('Error : ' + status + ' ('+ statusText + '). ' + data.msg).css('color', '#ca4a1f');
		} catch(err){
			jQuery("#staging_progress_bar_note_wpss").html('Unknown error, email us at <a href="mailto:help@wpsuperstage.com?Subject=Contact" target="_top">help@wpsuperstage.com</a> ').css('color', '#ca4a1f');
		}
	}). fail(function(request) {
		jQuery("#staging_progress_bar_note_wpss").html('Unknown error, email us at <a href="mailto:help@wpsuperstage.com?Subject=Contact" target="_top">help@wpsuperstage.com</a> ').css('color', '#ca4a1f');
	});
}

function choose_staging_wpss(){
	//its not a staging page
	if(window.location.href.indexOf('wpss-main-page') === -1){
		return ;
	}

	remove_prev_activity_ui_wpss();
	jQuery('#dashboard_activity_wpss').remove();

	var html = same_server_template_wpss();
	jQuery('#staging_area_wpss').after(html);
}

function same_server_wpss(){
	remove_prev_activity_ui_wpss();
	var template = same_server_template_wpss();
	jQuery('#staging_area_wpss').after(template);
}


function same_server_template_wpss(){
	// var head_div = '<div id="dashboard_activity_wpss" class="postbox" style="overflow: hidden;margin: 0px; width: 702px; margin: 60px auto;"><h2 class="hndle ui-sortable-handle">'
	// var title = '<span style="margin-left: 15px;position: relative;bottom: 8px;" class="title-bar-staging-wpss"> <span id="goto_staging_setup_wpss" style="cursor: pointer;">Staging Setup </span> >  Same Server</span></h2>';
	// var body_start =  '<div class="inside">';
	// var inside_block_start = '<div style="position: relative;margin-bottom: 50px;top: 10px; margin-top: 35px;">';
	// var content = '<div class="stage-on-the-server">Stage on the same server</div>';
	// var input = '<div style="top: 30px;position: relative;left: 23%;"><label class="same-server-staging-path" title=' + get_home_url_wpss() + ' >Staging Path: <span style="max-width: 200px;display: table-cell;overflow: hidden !important;text-overflow: ellipsis;" >'+get_home_url_wpss()+'</span></label><input id="same_server_path_staging_wpss" type="text" value="staging" class="staging-path-input-wpss"></div><div style=" position: absolute; top: 70px; color: #D54E21; left: 24%;" id="internal_staging_error_wpss"></div>';
	// var button = '<div><input id="same_server_submit_wpss" type="submit" value="Stage Now" style="margin: 60px 0px 0px 270px;width: 140px;" class="button-primary"></div>';
	// var inside_block_end = '</div>';
	// var body_end = '</div>';
	// var footer = '';
	// return head_div+title+body_start+inside_block_start+content+input+button+inside_block_end+body_end+footer;

	let enable_load_images_from_live_site = '';
	let disable_load_images_from_live_site = '';

	if (typeof HOTLINK_LIVE_IMAGES_WPSS != 'undefined' && HOTLINK_LIVE_IMAGES_WPSS === 'yes') {
		enable_load_images_from_live_site = 'checked="checked"';
		disable_load_images_from_live_site = '';
	} else {
		enable_load_images_from_live_site = '';
		disable_load_images_from_live_site = 'checked="checked"';
	}

	return `
	<div id="dashboard_activity_wpss">
		<div class="create-staging-cont">
			<h4 class="staging_heading_wpss">Create New Staging Site</h4>
			<form onsubmit="return false;">
				<label for="staging-name" class="mbox" style="cursor:text;">
					<div class="form-top-label">STAGING SITE NAME</div>
					<div class="staging-link-cont">
						<div class="staging-domain-base">${get_home_url_wpss()}</div>
						<input type="text" value="staging" id="same_server_path_staging_wpss" class="staging-path-input-wpss">
					</div>
				</label>
				<div class="mbox">
					<div class="form-top-label">HOT LINK IMAGES?</div>
					<label style="margin-bottom:5px;">
						<input type="radio" name="load_images_from_live_site_settings_radio_wpss" value="yes" ${enable_load_images_from_live_site} > Yes, link images directly from the live site (recommended)
					</label>

					<label>
						<input type="radio" name="load_images_from_live_site_settings_radio_wpss" value="no" ${disable_load_images_from_live_site} > No, do not copy images from the live site
					</label>
				</div>
				<div class="button-cont">
					<button class="cancel edit_staging_cancel_wpss" style="float: left;display: none;">Cancel</button>
					<button id="same_server_submit_wpss" style="float: right;">Stage Now</button>
					<div style="clear: both;"></div>
				</div>
			</form>
			<div style=" position: relative; top: 70px; color: #D54E21; left: 28%;" id="internal_staging_error_wpss">
			</div>
		</div>
	</div>
	`;
}

function choose_staging_template_wpss(){
	var head_div = '<div id="dashboard_activity_wpss" class="postbox" style="overflow: hidden;margin: 0px; width: 702px; margin: 60px auto;"><h2 class="hndle ui-sortable-handle">'
	var title = '<span style="margin-left: 15px;position: relative;bottom: 8px;"  class="title-bar-staging-wpss">Staging Setup</span></h2>';
	var body_start =  '<div class="inside">';
	var border = '<div class="staging-border-wpss" style="position: relative; left: 49%;"></div>';
	var inside_block_start = '<div style="position: relative;">';
	var same_server_content = '<div class="staging-same-server-block" style="position: absolute;top: -160px;left: 88px;"><span class="stage-on-the-server">Stage on the same server</span><div class="staging-recommended">(Recommended)</div><input id="select_same_server_wpss" type="submit" value="Stage Now" style="position: absolute; margin: 50px 0px 0px -146px;width: 140px;" class="button-primary"><div class="staging-speed-note">Faster!</div></div>';
	var diff_server_content = '<div class="staging-different-server-block" style="position: absolute;top: -160px;right: 91px;"><span class="stage-on-the-server">Stage on different server</span><input id="select_different_server_wpss" type="submit" value="Stage Now" style="margin: 50px 0px 0px -146px;width: 140px; position: absolute;" class="button-primary"><div class="staging-speed-note">Slower...</div></div>';
	var inside_block_end = '</div>';
	var body_end = '</div>';
	var footer = '';
	return head_div+title+body_start+border+inside_block_start+same_server_content+diff_server_content+inside_block_end+body_end+footer;
}

function get_home_url_wpss() {
  var href = window.location.href;
  var index = href.indexOf('/wp-admin');
  var homeUrl = href.substring(0, index);
  return homeUrl+'/';
}

function wpss_staging_in_progress(){
	if(jQuery('.wpss_prog_wrap_staging').length != 0){
		return ;
	}

	remove_prev_activity_ui_wpss();

	var html = wpss_staging_in_progress_template();
	jQuery('#staging_area_wpss').after(html);

	jQuery('#stop_staging_wpss').val('Stop and clear staging');

	wpss_get_staging_url();

}

function wpss_staging_in_progress_template(){
	// var header = '<div id="dashboard_activity_wpss" class="postbox " style="width: 700px;margin: 60px auto 460px;"> <h2 class="hndle ui-sortable-handle title-bar-staging-wpss"><span style="margin-left: 15px;position: relative;bottom: 8px;">Staging Progress</span><input id="stop_staging_wpss" type="submit" class="button-primary" value="Stop Staging" style="float:right;position: relative;bottom: 11px;right: 19px;display:block"><span style="margin-left: 15px;position: relative;bottom: 8px;float: right;right: 35px; display:none" id="staging_err_retry"><a style="cursor: pointer;text-decoration: underline; font-size: 14px; float: right;">Try again</a></span></h2><div class="inside" style="width: 500px; height: 180px;">';
	// var inside = '<div class="l1" style="margin: 0px 0px 10px 100px;text-align: center;width: 100%;position: relative;top: 15px;">Your site will be staged to <span class="staging_completed_dest_url_wpss"> </span></div> <div style="min-height: 40px;background: #fef4f4;border-left: 5px solid #e82828;width: 330px;position: absolute;left: 102px;top: 21px; display:none"><span style="position: relative;left: 5px;top: 10px;word-break: break-word;">Error: Folder Paths mismatch</span></div> <div class="l1 wpss_prog_wrap_staging" style=" top: 40px;position: relative; margin: 0px 0px 0px 90px; width: 100% !important;"><div class="staging_progress_bar_cont"><span id="staging_progress_bar_note_wpss">Syncing changes</span><div class="staging_progress_bar_wpss" style="width:0%"></div></div></div>';
	// var footer = '<div class="l1" style="position: relative;top: 70px;text-align: center;left: 100px;"><span>Note : Please do not close this tab until staging completes.</span><br><strong>Note : Go to Settings -> Permalinks and click "Save Changes" on the Staging site\'s WP admin dashboard, after the staging site is created successfully.</strong><div id="resume_staging_wpss" style="margin-top: 6px; display: none;"><a class="button button-primary">Resume</a></div> </div></div></div><?php';
	// var final_html = header + inside + footer;
	// return final_html;

	return `
	<div id="dashboard_activity_wpss">
		<div class="create-staging-cont">
			<h4>Creating new staging site...</h4>
			<div class="progress-cont">
				<div id="staging_progress_bar_note_wpss">Syncing changes</div>
				<div class=" staging_progress_bar_wpss progress-bar">
					<div class="inside"></div>
				</div>
			</div>
			<div style="text-align: center; margin-bottom: 20px;">
				<span>Note : Please do not close this tab until staging completes.</span>
				<br>
				<strong>
					Note : Go to Settings -> Permalinks and click "Save Changes" on the Staging site\'s WP admin dashboard, after the staging site is created successfully.
				</strong>
			</div>
			<div id="stop_staging_wpss" class="cancel-staging-process"><a class="">Cancel & clear</a></div>
			<div id="resume_staging_wpss" style="margin-top: 6px; display: none; text-align: center;">
				<a class="button button-primary">Resume</a>
			</div>
		</div>
	</div>
	`;
}

function wpss_staging_completed(completed_time, destination_url, wpss_staging_plugin_url){
	remove_prev_activity_ui_wpss();
	var html = wpss_staging_completed_template();
	jQuery('#staging_area_wpss').after(html);
	jQuery("#staging_completed_time_wpss").html(completed_time);
	jQuery(".staging_completed_dest_url_wpss").html("<a href='"+destination_url+"' target='_blank'>"+destination_url+"</a>");
	jQuery("#ask_push_to_live_wpss").attr('href', wpss_staging_plugin_url);
	wpss_disable_staging_button_after_dom_loaded('staging_completed');
	wpss_request_staging_tool_tip('staging_completed');
}

function wpss_staging_completed_template(){
	
	return `
	<div id="dashboard_activity_wpss">
		<ul class="staging-sites">
			<li class="single-site">
				<div class="name-timestamp-cont">
					<div class="staging-site-name staging_completed_dest_url_wpss">
						<a href="#"></a>
					</div>
					<div id="staging_completed_time_wpss" class="staging-site-timestamp">15 Nov @ 3:40 am</div>
				</div>
				<div class="action-btns">
					<button id="ask_copy_staging_wpss" class="copy">Copy from live site</button>
					<button id="ask_push_to_live_wpss" class="push" href="" target='_blank'>Push to live site <div class="ask_push_to_live_tooltip_wpss">This will open the staging site in a new tab where you can push it to live.</div></button>
					<button id="edit_staging_wpss" class="edit">Edit this staging site</button>
					<button id="delete_staging_wpss" class="delete">Delete this staging site</button>
					<button id="staging_delete_options_wpss" style="display:none; right: 14px; background-color: white;"> Sure? <span id="delete_staging_progress"><a style="color: #e95d5d;" class="wpss_link"
								id="yes_delete_site_wpss">Yes</a></span> | <span><a class="wpss_link" id="no_delete_site_wpss">No</a>
					</button>
				</div>
			</li>
		</ul>
		<div id="staging_delete_display_messages_wpss" style="display:none;"> </div>
	</div>
	`;
}

function stop_staging_wpss(){
	jQuery.post(ajaxurl, {
		security: wpss_ajax_object.ajax_nonce,
		action: 'stop_staging_wpss',
	}, function(data){

	});
	setTimeout(function(){
		get_staging_details_wpss();
	}, 35000)
}

function wpss_get_staging_url(){
	if (window.location.href.indexOf('wpss-main-page') === -1 ){
		return false;
	}
	jQuery.post(ajaxurl, {
		security: wpss_ajax_object.ajax_nonce,
		action: 'get_staging_url_wpss',
	}, function(data) {
		try{
			var data = jQuery.parseJSON(data);
			jQuery(".staging_completed_dest_url_wpss").html(' '+data.destination_url);
		} catch(err){
			//
		}
	});
}

function delete_staging_site_wpss(){
	jQuery('#staging_delete_display_messages_wpss').html('Removing database and files...').show();
	jQuery.post(ajaxurl, {
		security: wpss_ajax_object.ajax_nonce,
		action: 'delete_staging_wpss',
	}, function(data) {
		try{
			var data = jQuery.parseJSON(data);
		} catch(err){
			return ;
		}
		if (data == undefined || !data) {
			jQuery('#staging_current_progress_wpss').html('I cannot work without data');
			return false;
		}
		if (data.status === 'success') {
			jQuery('#staging_delete_display_messages_wpss').addClass('success_wpss');
			if (data.deleted === 'both') {
				jQuery('#staging_delete_display_messages_wpss').html('Staging site deleted completely !');
			} else if (data.deleted === 'files') {
				jQuery('#staging_delete_display_messages_wpss').html('Files deleted completely but we cannot delete database !');
			} else if (data.deleted === 'db') {
				jQuery('#staging_delete_display_messages_wpss').html('Database deleted completely but we cannot delete files !');
			} else {
				jQuery('#staging_delete_display_messages_wpss').removeClass('.success_wpss').addClass('error_wpss');
				jQuery('#staging_delete_display_messages_wpss').html('We could not delete staging site, please do it manually');
			}
			setTimeout(function(){
				parent.location.assign(parent.location.href);
			}, 3000);
		} else {
			jQuery('#staging_delete_display_messages_wpss').addClass('error_wpss');
			jQuery('#staging_delete_display_messages_wpss').html('We could not delete staging site, please do it manually');
		}
	});
}

function remove_prev_activity_ui_wpss(){
	if(window.location.href.indexOf('wpss-main-page') !== -1){
		jQuery('#dashboard_activity_wpss, .postbox').remove();
	}
}

function get_staging_details_wpss(do_not_continue_staging){

	continue_staging_wpss = true;

	if(do_not_continue_staging){
		continue_staging_wpss = false;
	}

	jQuery.post(ajaxurl, {
		security: wpss_ajax_object.ajax_nonce,
		action: 'get_staging_details_wpss',
	}, function(data) {
		try{
			var data = jQuery.parseJSON(data);
		} catch(err){
			return ;
		}

		if (!data) {
			choose_staging_wpss(true);
			wpss_request_staging_tool_tip('not_staged_yet');
			wpss_disable_staging_button_after_dom_loaded('not_staged_yet');
			return ;
		}

		if (typeof data.staging_folder != 'undefined' && data.staging_folder) {
			WPSS_STATGING_PATH = data.staging_folder;
		}
		if (typeof data.is_running != 'undefined' && data.is_running) {
			wpss_request_staging_tool_tip('staging_running');
			wpss_staging_in_progress();
			if(continue_staging_wpss){
				wpss_continue_staging();
			}
			return ;
		} else {
			if(jQuery('.yes_stop_staging_dialog_wpss').hasClass('disabled')){
				jQuery('.yes_stop_staging_dialog_wpss').removeClass('disabled');
				jQuery('.yes_stop_staging_dialog_wpss').text('Yes, delete this staging');
				close_wpss_dialogs();
			}
		}

		if( (typeof data.destination_url != 'undefined' && data.destination_url ) && (typeof data.human_completed_time != 'undefined' && data.human_completed_time ) ){
			wpss_staging_completed(data.human_completed_time, data.destination_url, data.wpss_staging_plugin_url);
			return ;
		}

		choose_staging_wpss(true);
		wpss_request_staging_tool_tip('not_staged_yet');
		wpss_disable_staging_button_after_dom_loaded('not_staged_yet');
		if (typeof continue_staging_wpss != 'undefined') {
			delete continue_staging_wpss;
		}
	});
}

function wpss_request_staging_tool_tip(type){
	if (wpss_is_backup_running()) {
		return add_tool_tip_staging_wpss('backup_progress');
	}

	add_tool_tip_staging_wpss(type);
}

function push_staging_button_wpss(data){

	if(typeof data != 'undefined' && !data.is_whitelabling_staging_allowed){

		return ;
	}

	var extra_class = '';
	if(typeof staging_status_wpss == 'undefined' || staging_status_wpss === false){
		var extra_class = 'disabled button-disabled-staging-4-wpss';
	} else if(staging_status_wpss == 'progress' || staging_status_wpss == 'error'){
		var extra_class = 'disabled button-disabled-staging-1-wpss';
	} else if(staging_status_wpss == 'not_started'){
		var extra_class = 'disabled button-disabled-staging-2-wpss';
	} else if(staging_status_wpss == 'backup_progress'){
		var extra_class = 'disabled button-disabled-staging-3-wpss';
	}

	var current_path = window.location.href;
	if (current_path.toLowerCase().indexOf('update-core') !== -1) {

		if (!wpss_is_allowed_to_show_extra_buttons()) {	return ; }

		jQuery('.upgrade-plugins-staging-wpss, .upgrade-themes-staging-wpss, .upgrade-translations-staging-wpss, .upgrade-core-staging-wpss, .plugin-update-from-iframe-staging-wpss').remove();
		var update_plugins = '&nbsp; <input class="upgrade-plugins-staging-wpss button '+extra_class+'" type="submit" value="Update in staging">';
		var update_themes = '&nbsp; <input class="upgrade-themes-staging-wpss button  '+extra_class+'" type="submit" value="Update in staging">';
		var update_translations = '&nbsp;<input class="upgrade-translations-staging-wpss button  '+extra_class+'" type="submit" value="Update in staging">';
		var update_core = '&nbsp;<input type="submit" class="upgrade-core-staging-wpss button button regular  '+extra_class+'" value="Update in staging">';
		var iframe_update = '<a class="plugin-update-from-iframe-staging-wpss button button-primary right  '+extra_class+'" style=" margin-right: 10px;">Update in staging</a>';
		jQuery('form[name=upgrade-plugins]').find('input[name=upgrade]').after(update_plugins);
		jQuery('form[name=upgrade-themes]').find('input[name=upgrade]').after(update_themes);
		jQuery('form[name=upgrade]').find('input[name=upgrade]').after(update_core);
		jQuery('form[name=upgrade-translations]').find('input[name=upgrade]').after(update_translations);
		setTimeout(function(){
			jQuery("#TB_iframeContent").contents().find(".plugin-update-from-iframe-staging-wpss").remove();
			jQuery("#TB_iframeContent").contents().find("#plugin_update_from_iframe").after(iframe_update);
			if(jQuery("#TB_iframeContent").contents().find(".plugin-update-from-iframe-staging-wpss").length > 0){
				add_tool_tip_staging_wpss();
			}
		}, 5000);
	} else if(current_path.toLowerCase().indexOf('plugins.php') !== -1){

		if (!wpss_is_allowed_to_show_extra_buttons()) {	return ; }

		jQuery('.wpss-span-spacing-staging , .update-link-plugins-staging-wpss , .button-action-plugins-staging-wpss').remove();
		var in_app_update = '<span class="wpss-span-spacing-staging">&nbsp;or</span> <a href="#" class="update-link-plugins-staging-wpss  '+extra_class+'">Update in staging</a>';
		var selected_update = '<span class="wpss-span-spacing-staging">&nbsp</span><input type="submit" class="button-action-plugins-staging-wpss button  '+extra_class+'" value="Update in staging">';
		var iframe_update = '<a class="plugin-update-from-iframe-staging-wpss button button-primary right  '+extra_class+'" style=" margin-right: 10px;">Update in staging</a>';
		jQuery('form[id=bulk-action-form]').find('.update-link').after(in_app_update);
		jQuery('form[id=bulk-action-form]').find('.button.action').after(selected_update);
		setTimeout(function(){
			jQuery("#TB_iframeContent").contents().find(".plugin-update-from-iframe-staging-wpss").remove();
			jQuery("#TB_iframeContent").contents().find("#plugin_update_from_iframe").after(iframe_update);
			if(jQuery("#TB_iframeContent").contents().find(".plugin-update-from-iframe-staging-wpss").length > 0){
				add_tool_tip_staging_wpss();
			}
			add_tool_tip_staging_wpss();
		}, 5000);
	} else if(current_path.toLowerCase().indexOf('plugin-install.php') !== -1){

		if (!wpss_is_allowed_to_show_extra_buttons()) {	return ; }

		jQuery('.update-now-plugins-staging-wpss, .plugin-update-from-iframe-staging-wpss').remove();
		var in_app_update = '<li><a class="button update-now-plugins-staging-wpss '+extra_class+'" href="#">Update in staging</a></li>';
		var iframe_update = '<a class="plugin-update-from-iframe-staging-wpss button button-primary right  '+extra_class+'" style=" margin-right: 10px;">Update in staging</a>';
		setTimeout(function(){
			jQuery("#TB_iframeContent").contents().find(".plugin-update-from-iframe-staging-wpss").remove();
			jQuery("#TB_iframeContent").contents().find("#plugin_update_from_iframe").after(iframe_update);
			add_tool_tip_staging_wpss();
			if(jQuery("#TB_iframeContent").contents().find(".plugin-update-from-iframe-staging-wpss").length > 0){
				add_tool_tip_staging_wpss();
			}
		}, 5000);
		jQuery('.plugin-action-buttons .update-now.button').parents('.plugin-action-buttons').append(in_app_update);
	} else if(current_path.toLowerCase().indexOf('themes.php?theme=') !== -1){

		if (!wpss_is_allowed_to_show_extra_buttons()) {	return ; }

		var update_link = jQuery('.wpss-span-spacing-staging ~ #update-theme-staging-wpss');
		var spacing = jQuery('.wpss-span-spacing-staging ~ #update-theme-staging-wpss').siblings('.wpss-span-spacing-staging');
		jQuery(update_link).remove();
		jQuery(spacing).remove();
		var popup_update = '<span class="wpss-span-spacing-staging">&nbsp;or</span> <a href="#" id="update-theme-staging-wpss" class=" '+extra_class+'">Update in staging</a>';
		jQuery('#update-theme').after(popup_update);
		add_tool_tip_staging_wpss();
	} else if(current_path.toLowerCase().indexOf('themes.php') !== -1){

		if (!wpss_is_allowed_to_show_extra_buttons()) {	return ; }

		jQuery('.button-link-themes-staging-wpss, .button-action-themes-staging-wpss , #update-theme-staging-wpss, .wpss-span-spacing-staging, .button-action-themes-staging-wpss').remove();
		var in_app_update = '<span class="wpss-span-spacing-staging">&nbsp;or </span><button class="button-link-themes-staging-wpss button-link  '+extra_class+'" type="button">Update in staging</button>';
		var selected_update = '<span class="wpss-span-spacing-staging">&nbsp;</span><input type="submit" class="button-action-themes-staging-wpss button  '+extra_class+'" value="Update in staging">';
		jQuery('.button-link[type=button]').not('.wp-auth-check-close, .button-link-themes-staging-wpss, .button-link-themes-bbu-wpss').after(in_app_update);
		jQuery('form[id=bulk-action-form]').find('.button.action').after(selected_update);

		if (wpss_is_multisite) {
			jQuery('.wpss-span-spacing-staging , .update-link-themes-staging-wpss ').remove();
			var in_app_update = '<span class="wpss-span-spacing-staging">&nbsp;or</span> <a href="#" class="update-link-themes-staging-wpss  '+extra_class+'">Update in staging</a>';
			jQuery('form[id=bulk-action-form]').find('.update-link').after(in_app_update);
		}
	}
	setTimeout(function (){
		jQuery('.theme').on('click', '.button-link-themes-staging-wpss , #update-theme', function(e) {
			handle_theme_button_link_request_wpss(this, e, false, true);
		});
	}, 1000);

	setTimeout(function (){
		jQuery('#update-theme-staging-wpss').on('click', function(e) {
			handle_theme_link_request_wpss(this, e, false, true)
		});
	}, 500);
	// get_staging_details_wpss();
}

function wpss_choose_update_in_stage(update_items, type){
	swal({
		title              : wpss_get_dialog_header('Choose option'),
		html               : wpss_get_dialog_body('Want to try this update in staging site?'),
		padding            : '0px 0px 10px 0',
		buttonsStyling     : false,
		showCancelButton   : true,
		confirmButtonColor : '',
		cancelButtonColor  : '',
		confirmButtonClass : 'button-primary wtpc-button-primary',
		cancelButtonClass  : 'button-secondary wtpc-button-secondary',
		confirmButtonText  : 'Update in staging',
		cancelButtonText   : 'Stage and update',
	}).then(function () {
			swal({
				title              : wpss_get_dialog_header('Are you sure?'),
				html               : wpss_get_dialog_body('This will just perform the update in the staging site, Other changes are safe.', ''),
				padding            : '0px 0px 10px 0',
				buttonsStyling     : false,
				showCancelButton   : true,
				confirmButtonColor : '',
				cancelButtonColor  : '',
				confirmButtonClass : 'button-primary wtpc-button-primary',
				cancelButtonClass  : 'button-secondary wtpc-button-secondary',
				confirmButtonText  : 'Yes',
				cancelButtonText   : 'Cancel',
			}).then(function () {
					wpss_save_upgrade_meta_in_staging(update_items, type, 'update_in_staging');
				}, function (dismiss) {
				}
			);
		}, function (dismiss) {

			if (dismiss === 'overlay') {
				return ;
			}

			swal({
				title              : wpss_get_dialog_header('Are you sure?'),
				html               : wpss_get_dialog_body('This will erase your entire staging site and do fresh staging then initiate the update.', ''),
				padding            : '0px 0px 10px 0',
				buttonsStyling     : false,
				showCancelButton   : true,
				confirmButtonColor : '',
				cancelButtonColor  : '',
				confirmButtonClass : 'button-primary wtpc-button-primary',
				cancelButtonClass  : 'button-secondary wtpc-button-secondary',
				confirmButtonText  : 'Yes',
				cancelButtonText   : 'Cancel',
			}).then(function () {
					wpss_save_upgrade_meta_in_staging(update_items, type, 'update_and_staging');
				}, function (dismiss) {
				}
			);
		}
	);
}

function wpss_save_upgrade_meta_in_staging(update_items, type, choice){
	jQuery.post(ajaxurl, {
		security: wpss_ajax_object.ajax_nonce,
		action: 'save_upgrade_meta_in_staging_wpss',
		update_items: update_items,
		type: type,
	}, function(data) {
		try{
			var data = jQuery.parseJSON(data);
		} catch(err){
			return wpss_request_failed();
		}

		if(!data.status && data.status === 'success'){
			return wpss_request_failed();
		}

		if (choice === 'update_in_staging') {
			swal({
				title              : wpss_get_dialog_header('Update initiated'),
				html               : wpss_get_dialog_body('Continue your work, we will update you once update is done on staging site', 'success'),
				padding            : '0px 0px 10px 0',
				buttonsStyling     : false,
				confirmButtonColor : '',
				confirmButtonClass : 'button-primary wtpc-button-primary',
				confirmButtonText  : 'Ok',
			});
			wpss_force_update_in_staging();

		} else if(choice === 'update_and_staging'){
			swal({
				title              : wpss_get_dialog_header('Update in staging initiated'),
				html               : wpss_get_dialog_body('Continue your work, we will update you once state and update is done', 'success'),
				padding            : '0px 0px 10px 0',
				buttonsStyling     : false,
				confirmButtonColor : '',
				confirmButtonClass : 'button-primary wtpc-button-primary',
				confirmButtonText  : 'Ok',
			});
			select_copy_staging_type_wpss();
		} else {
			wpss_request_failed();
		}
	});
}

function wpss_force_update_in_staging(){
	jQuery.post(ajaxurl, {
		security: wpss_ajax_object.ajax_nonce,
		action: 'force_update_in_staging_wpss',
	}, function(data) {

	});
}

function select_copy_staging_type_wpss(direct_copy){
	// if(direct_copy === undefined){
		// var data = {
		// 	bbu_note_view:{
		// 		type: 'message',note:'Update in staging initiated! We will notify you once it\'s completed.',
		// 	},
		// };

		// show_notification_bar_wpss(data);
	// }

	copy_staging_wpss();
}

function add_tool_tip_staging_wpss(type){
	if(type){
		add_tool_tip_staging_wpss_type = type;
	} else {
		if(typeof add_tool_tip_staging_wpss_type != 'undefined'){
			type = add_tool_tip_staging_wpss_type;
		}
	}
	var class_staging_in_update = '.upgrade-plugins-staging-wpss, .upgrade-themes-staging-wpss, .upgrade-translations-staging-wpss, .upgrade-core-staging-wpss, .update-link-plugins-staging-wpss, .button-action-plugins-staging-wpss, .plugin-update-from-iframe-staging-wpss , .update-now-plugins-staging-wpss, .button-link-themes-staging-wpss, .button-action-plugins-staging-wpss, #update-theme-staging-wpss, .update-link-themes-staging-wpss, .button-action-themes-staging-wpss';
	var class_bbu_in_update = "#update-theme-bbu-wpss, .update-link-plugins-bbu-wpss , .upgrade-plugins-bbu-wpss, .upgrade-themes-bbu-wpss, .upgrade-translations-bbu-wpss, .upgrade-core-bbu-wpss, .plugin-update-from-iframe-bbu-wpss, .update-link-plugins-bbu-wpss, .button-action-plugins-bbu-wpss, .update-now-plugins-bbu-wpss, .button-link-themes-bbu-wpss, .button-action-plugins-bbu-wpss, .update-link-themes-bbu-wpss, .button-action-themes-bbu-wpss";
	if(type === 'staging_running'){
		jQuery(class_staging_in_update).each(function(tagElement , key) {
			jQuery(key).opentip('Staging is running. Please wait until it finishes.', { style: "dark" });
		});

		jQuery(class_bbu_in_update).each(function(tagElement , key) {
			jQuery(key).addClass('disabled button-disabled-bbu-from-staging-wpss');
			jQuery(key).opentip('Staging is running. Please wait until it finishes.', { style: "dark" });
		});
	} else if(type === 'not_staged_yet'){
		jQuery(class_staging_in_update).each(function(tagElement , key) {
			jQuery(key).opentip('Set up a staging in Super Stage WP -> Staging.', { style: "dark" });
		});
	} else if(type === 'backup_progress'){
		jQuery(class_staging_in_update).each(function(tagElement , key) {
			jQuery(key).opentip('Backup in progress. Please wait until it finishes', { style: "dark" });
		});
	} else if(type === 'staging_error'){
		jQuery(class_staging_in_update).each(function(tagElement , key) {
			jQuery(key).opentip('Previous staging failed. Please fix it.', { style: "dark" });
		});
	} else if (type === 'staging_completed'){
		jQuery(class_staging_in_update).removeClass('disabled button-disabled-staging-1-wpss button-disabled-staging-2-wpss button-disabled-staging-3-wpss button-disabled-staging-4-wpss');
	} else {
		jQuery(class_staging_in_update).each(function(tagElement , key) {
			jQuery(key).opentip('You cannot stage now, Please try after sometime.', { style: "dark" });
		});
	}
}

function wpss_disable_staging_button_after_dom_loaded(type){
	if(!wpss_is_backup_running()){
		return false;
	}

	switch(type){
		case 'staging_completed':
			wpss_disable_staging_completed_button();
			break;
		case 'not_staged_yet':
			wpss_disable_staging_start_button();
			break;
	}
}

function wpss_disable_staging_completed_button() {
	jQuery('#ask_copy_staging_wpss, #edit_staging_wpss').addClass('disabled').css('color','gray');

	if(jQuery('#ask_copy_staging_wpss').length > 0)
		jQuery('#ask_copy_staging_wpss').opentip('Backup in progress. Please wait until it finishes', { style: "dark" });

	if(jQuery('#edit_staging_wpss').length > 0)
		jQuery('#edit_staging_wpss').opentip('Backup in progress. Please wait until it finishes', { style: "dark" });
}

function wpss_disable_staging_start_button() {
	jQuery('#select_same_server_wpss, #select_different_server_wpss, #same_server_submit_wpss').prop('disabled', true).css('cursor', 'not-allowed');

	if(jQuery('#select_same_server_wpss').length > 0)
		jQuery('#select_same_server_wpss').opentip('Backup in progress. Please wait until it finishes', { style: "dark" });

	if(jQuery('#select_different_server_wpss').length > 0)
		jQuery('#select_different_server_wpss').opentip('Backup in progress. Please wait until it finishes', { style: "dark" });

	if(jQuery('#same_server_submit_wpss').length > 0)
		jQuery('#same_server_submit_wpss').opentip('Backup in progress. Please wait until it finishes', { style: "dark" });
}

function enable_staging_button_wpss(){
	setTimeout(function(){
		jQuery('#select_same_server_wpss, #select_different_server_wpss, #same_server_submit_wpss').prop('disabled', false).css('cursor', 'pointer');
	}, 2000);
}

function wpss_is_backup_running(){
	if(typeof wpss_backup_running == 'undefined' || !wpss_backup_running){
		return false;
	}

	return true;
}
