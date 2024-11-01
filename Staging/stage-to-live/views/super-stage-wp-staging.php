<div class="wpss-css">

	<h2 id='staging_area_wpss'>Staging Area</h2>

	<?php
	if(!$stage_to_live->is_allowed_to_copy_site_to_live()){?>
		"Staging to Live" feature is not active on your account. <span> Please email us at <a href="mailto:help@wpsuperstage.com?Subject=Contact" target="_top">help@wpsuperstage.com</a> for further assistance.</span>.<br>
		<?php
		return ;
	}

	$user_excluded_extenstions_staging = $stage_to_live->get_user_excluded_extenstions_s2l();

	add_thickbox(); ?>

	<div id="dashboard_activity_wpss" class="staging_area_wpss" >
		<h4>Include/Exclude Content ( <a href="https://docs.wpsuperstage.com/article/28-how-push-staging-site-to-production" style="text-decoration: underline;">Need Help?</a> )</h4>
		
		<div style="width: 40%; float: left;" >
			<button class="button button-secondary wpss_dropdown" id="toggle_exlclude_files_n_folders_staging_wpss" style="width: 98%; outline:none; text-align: left;">
				<span class="dashicons dashicons-portfolio" style="position: relative; top: 3px; font-size: 20px"></span>
				<span style="left: 10px; position: relative;">Folders &amp; Files </span>
				<span class="dashicons dashicons-arrow-down" style="position: relative; top: 3px; float: right;"</span>
			</button>
			<div style="display:none; width: 98%;" id="wpss_exc_files_staging"></div>
		</div>

		<div style="float: left; position: relative;width: 40%;">
				<button class="button button-secondary wpss_dropdown" id="toggle_wpss_db_tables_staging" style="width: 98%; outline:none; text-align: left;">
					<span class="dashicons dashicons-menu" style="position: relative;top: 3px; font-size: 20px"></span>
					<span style="left: 10px; position: relative;">Database</span>
					<span class="dashicons dashicons-arrow-down" style="position: relative;top: 3px; float: right;"></span>
				</button>
				<div style="display:none; width: 98%;" id="wpss_exc_db_files_staging"></div>
		</div>

		<div style="float: left; width: 100%;">
			<tr>
				<p style="margin-top: 30px;">Excluded File Extensions</p>
				<td>
					<fieldset>
						<input class="wpss-split-column" type="text" readonly name="user_excluded_extenstions_staging" id="user_excluded_extenstions_staging" style="width:48%;" placeholder="Eg. .mp4, .mov" value="<?php echo esc_textarea($user_excluded_extenstions_staging); ?>" />
					</fieldset>
				</td>
			</tr>
		</div>

		<div style="float: left; width: 100%;">
			<div style="margin: 30px 0px 10px 0px; font-style: italic;">Last copy to live on : <span id="last_copy_to_live"><?php echo esc_textarea($stage_to_live->get_last_time_copy_to_live()); ?></span>. </div>
			<a href="#TB_inline?width=600&height=550" class="thickbox wpss-thickbox " style="display: none"></a>
			<a id="ask_copy_staging_wpss" class="button button-primary ">Copy site to live</a>
		</div>

	</div>
	<div class="wpss_dialog_wrapper" style="display: none;">
        <div class="dialog-cont wpss_dialogs copy_staging_to_live_dialog_wpss" style="display: none;">
			<div class="dialog-icon-cont">
				<div class="dialog-icon stg-live"></div>
			</div>
			<div class="dialog-message">Push this Staging to Live?</div>
			<div class="dialog-helptext">
				Are you sure you want to push this staging site to the live site?
			</div>
			<div class="dialog-btn-cont">
				<button class="cancel">No, don't push</button>
				<button id="wpss_copy_stage_to_live" class="confirm yes_copy_staging_to_live_dialog_wpss">Yes, push this staging</button>
			</div>
		</div>
	</div>
	
</div>
