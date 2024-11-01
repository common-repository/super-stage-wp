jQuery(document).ready(function($) {
	jQuery('body').on('click', '#wpss_copy_stage_to_live', function (e){
		if (jQuery(this).hasClass('disabled')) {
			return false;
		}
		jQuery('.staging_area_wpss').hide();
		tb_remove();
		live_in_progress_wpss();
		wpss_copy_stage_to_live();
		close_wpss_dialogs();
	});

	wpss_staging_in_progress = false;
	resume_count_wpss = 0;
	window.onbeforeunload = confirm_exist_wpss;

	jQuery('body').on('click', '.close', function (e){
		tb_remove();
	});

	jQuery('body').on('click', '#ask_copy_staging_wpss', function (e){

		jQuery('.wpss_dialog_wrapper').show();
		jQuery('.wpss_dialogs').hide();
		jQuery('.copy_staging_to_live_dialog_wpss').show();


		return false;
	});

	jQuery('body').on('click', '#resume_s2l_wpss', function (e){
		resume_count_wpss = 0;

		resume_s2l_auto_click_wpss();
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

	check_and_initiate_state_s2l_wpss();
	//Remove all notices to avoid css collapse
	// jQuery('#update-nag').remove();
});
function check_and_initiate_state_s2l_wpss(){
	if(IS_START_STAGING_TO_LIVE_WPSS != 'yes'){

		return;
	}

	setTimeout(function(){
		jQuery('#ask_copy_staging_wpss').click();

		let thisNewUrl = window.location.href.split('&wpss_s2l_start')[0];
		window.history.replaceState("object or string", "Title", thisNewUrl);
	}, 1000);
}

function resume_s2l_auto_click_wpss(){
	if(jQuery('#resume_s2l_wpss').is(":visible")){
		jQuery('#resume_s2l_wpss').hide();
		jQuery('#staging_progress_bar_note_wpss').css('color', 'black');
		wpss_copy_stage_to_live();
	}
}

function close_wpss_dialogs(){
	jQuery('.wpss_dialogs').hide();
	jQuery('.wpss_dialog_wrapper').hide();
}

function confirm_exist_wpss(){
	if (wpss_staging_in_progress) {
		return "Do not close the tab until process gets done !";
	}
}

function live_in_progress_wpss(){
	var html = copying_in_progress_template_wpss();
	jQuery('#staging_area_wpss').after(html);
}

function copying_in_progress_template_wpss(){
	// var header = '<div id="dashboard_activity_wpss" class="postbox wpss-progress" style="width: 700px;margin: 60px auto 460px;"> <h2 class="hndle ui-sortable-handle title-bar-staging-wpss"><span style="margin-left: 15px;position: relative;bottom: 8px;">Staging to Live Progress</span><span style="margin-left: 15px;position: relative;bottom: 8px;float: right;right: 35px; display:none" id="staging_err_retry"><a style="cursor: pointer;text-decoration: underline; font-size: 14px; float: right;">Try again</a></span></h2><div class="inside" style="width: 500px; height: 180px;">';
	// var inside = ' <div style="min-height: 40px;background: #fef4f4;border-left: 5px solid #e82828;width: 330px;position: absolute;left: 102px;top: 21px; display:none"><span style="position: relative;left: 5px;top: 10px;word-break: break-word;">Error: Folder Paths mismatch</span></div> <div class="l1 wpss_prog_wrap_staging" style=" top: 40px;position: relative; margin: 0px 0px 0px 90px; width: 100% !important;"><div class="staging_progress_bar_cont"><span id="staging_progress_bar_note_wpss">Syncing changes</span><div class="staging_progress_bar_wpss" style="width:0%"></div></div></div>';
	// var footer = '<div class="l1 do_not_close_staging" style="position: relative;top: 90px;text-align: center;left: 100px;">Do not close the tab. until it’s done. <div id="resume_s2l_wpss" style="margin-top: 6px; display: none;"><a class="button button-primary">Resume</a></div> </div></div></div><?php';
	// var final_html = header + inside + footer;
	// return final_html;

	return `
	<div id="dashboard_activity_wpss">
		<div class="create-staging-cont">
			<h4>Copying to Live site...</h4>
			<div class="progress-cont">
			<div id="staging_progress_bar_note_wpss">Syncing changes</div>
			<div class=" staging_progress_bar_wpss progress-bar">
				<div class="inside"></div>
			</div>
			</div>
			<div style="text-align: center; margin-bottom: 20px;">
				<span>Note : Please do not close this tab until the staging to live process completes.</span>
				<br>
				<strong>
					Note : Go to Settings -> Permalinks and click "Save Changes" on the Live site\'s WP admin dashboard, after the staging site is copied to the live site successfully.
				</strong>
			</div>
			<div id="resume_staging_wpss" style="margin-top: 6px; display: none; text-align: center;">
				<a class="button button-primary">Resume</a>
			</div>
		</div>
	</div>
	`;
}

function showS2LResumeButton() {
	jQuery("#staging_progress_bar_note_wpss").html('Error: Something went wrong, please click the resume button.').css('color', '#ca4a1f');
	jQuery('#resume_s2l_wpss').show();

	if(typeof resume_count_wpss == 'undefined'){
		resume_count_wpss = 0;
	}

	if(resume_count_wpss > 9){

		return;
	}

	resume_count_wpss++;

	setTimeout(function(){

		resume_s2l_auto_click_wpss();
	}, 180000);
}

function wpss_copy_stage_to_live(){
	wpss_staging_in_progress = true;
	jQuery.post(ajaxurl, {
		security: wpss_ajax_object.ajax_nonce,
		action: 'wpss_copy_stage_to_live',
	}, function(data) {
		try{
			var data = jQuery.parseJSON(data);

			jQuery('#staging_progress_bar_note_wpss').html(data.msg);
			jQuery(".staging_progress_bar_wpss .inside").css('width', data.percentage+'%');

			if(typeof data == 'undefined' || !data.status ){
				showS2LResumeButton();

				return;
			}

			if(data.status === 'continue'){
				wpss_copy_stage_to_live();
			} else if(data.status == 'error'){
				jQuery("#staging_progress_bar_note_wpss").html('Error: '+ data.msg);
				jQuery('#resume_s2l_wpss').show();

				if(typeof resume_count_wpss == 'undefined'){
					resume_count_wpss = 0;
				}

				if(resume_count_wpss > 10){

					return;
				}

				resume_count_wpss++;

				setTimeout(function(){
					resume_s2l_auto_click_wpss();
				}, 180000);

			} else {
				wpss_staging_in_progress = false;
				jQuery('#last_copy_to_live').html(data.time);
				setTimeout(function(){
					jQuery('#dashboard_activity_wpss').hide();
					jQuery('.staging_area_wpss').show();
				}, 3000);
			}

		} catch(err){
			console.log(err, 'caught live js');

			showS2LResumeButton();

			return ;
		}
	}).fail(function(request) {
		showS2LResumeButton();
	});;
}
