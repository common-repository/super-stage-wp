<?php

class WPSS_Staging_Hooks_Hanlder {
	protected $staging;
	protected $config;

	public function __construct() {
		$this->staging = WPSS_Base_Factory::get('WPSS_Staging');
		$this->config = WPSS_Factory::get('config');
	}


	public function init_staging_wpss_h(){
		wpss_log(array(), '-----------init_staging_wpss_h-------------');
		$this->staging->init_staging_wpss_h(true);
	}

	public function get_staging_details(){

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();
		$details = $this->staging->get_staging_details();
		$details['is_running'] = $this->is_any_staging_process_going_on();
		wpss_die_with_json_encode( $details, 1 );
	}

	public function delete_staging_wpss(){

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		$this->staging->delete_staging_wpss();
	}

	public function stop_staging_wpss(){

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		wpss_log(array(), '-----------stop_staging_wpss_h-------------');
		$this->staging->stop_staging_wpss();
	}

	public function send_response_node_staging_wpss_h(){
		$progress_status = $this->config->get_option('staging_progress_status', true);
		$return_array = array('progress_status' => $progress_status);
		send_response_wpss('progress', 'STAGING', $return_array);
	}

	public function get_staging_url_wpss(){

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		wpss_log(array(), '---------get_staging_url_wpss---------------');
		$this->staging->get_staging_url_wpss();
	}

	public function page_settings_tab($tabs){
		$tabs['staging'] = __( 'Staging', 'super-stage-wp' );
		return $tabs;
	}

	public function add_additional_sub_menus_wpss_h($name = '', $type = 'sub'){

		if ($type === 'main') {
			$text = __($name, 'wpss');
			add_menu_page($text, $text, 'activate_plugins', 'super-stage-wp-staging-options', 'wp_super_stage_staging_options', 'dashicons-cloud', '80.0564');
			return ;
		}

		$text = __('Staging', 'wpss');
		add_submenu_page('super-stage-wp-monitor', $text, $text, 'activate_plugins', 'super-stage-wp-staging-options', 'wp_super_stage_staging_options');
	}

	public function is_any_staging_process_going_on($value=''){
		// wpss_log(array(), '---------is_any_staging_process_going_on---------------');
		return $this->staging->is_any_staging_process_going_on();
	}

	public function get_internal_staging_db_prefix($value=''){
		// wpss_log(array(), '---------get_internal_staging_db_prefix---------------');
		return $this->staging->get_staging_details('db_prefix');
	}

	public function is_staging_taken($value=''){
		// wpss_log(array(), '---------get_internal_staging_db_prefix---------------');
		if($this->config->get_option('same_server_staging_status') === 'staging_completed'){
			return true;
		}

		return false;
	}

	public function enque_js_files() {
		wp_enqueue_style('wpss-staging-style', plugins_url() . '/' . WPSS_TC_PLUGIN_NAME . '/Staging/style.css', array(), WPSS_VERSION);
		wp_enqueue_script('wpss-staging', plugins_url() . '/' . WPSS_TC_PLUGIN_NAME . '/Staging/init.js', array(), WPSS_VERSION);
	}


	public function continue_staging() {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		return $this->staging->choose_action();
	}

	public function start_fresh_staging() {
		wpss_log($_POST, "--------" . __FUNCTION__ . "--------");
		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		if (empty($_POST['path'])) {
			wpss_die_with_json_encode( array('status' => 'error', 'msg' => 'path is missing') );
		}

		if(!empty($_POST['settings'] && !empty($_POST['settings']['load_images_from_live_site']))){
			$sanitized_load_images_from_live_site = sanitize_text_field($_POST['settings']['load_images_from_live_site']);
			$this->config->set_option('load_images_from_live_site_settings', $sanitized_load_images_from_live_site);
		}

		$this->config->set_option('site_type', 'prod');
		return $this->staging->choose_action($_POST['path'], $reqeust_type = 'fresh');
	}

	public function copy_staging() {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();
		$this->config->set_option('site_type', 'prod');

		return $this->staging->choose_action(false, $reqeust_type = 'copy');
	}

	public function save_staging_settings() {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		return $this->staging->save_staging_settings($_POST['data']);
	}

	public function get_staging_current_status_key() {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		return $this->staging->get_staging_current_status_key();
	}

	public function is_staging_need_request() {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		return $this->staging->is_staging_need_request();
	}

	public function process_staging_details_hook($request) {
		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		return $this->staging->process_staging_details_hook($request);
	}

	public function set_options_to_staging_site($name, $value) {
		return $this->staging->set_options_to_staging_site($name, $value);
	}

	public function page_settings_content($more_tables_div, $dets1 = null, $dets2 = null, $dets3 = null) {
		$internal_staging_db_rows_copy_limit = $this->config->get_option('internal_staging_db_rows_copy_limit');
		$internal_staging_db_rows_copy_limit = ($internal_staging_db_rows_copy_limit) ? $internal_staging_db_rows_copy_limit : WPSS_STAGING_DEFAULT_COPY_DB_ROWS_LIMIT ;

		$internal_staging_file_copy_limit = $this->config->get_option('internal_staging_file_copy_limit');
		$internal_staging_file_copy_limit = ($internal_staging_file_copy_limit) ? $internal_staging_file_copy_limit : WPSS_STAGING_DEFAULT_FILE_COPY_LIMIT ;

		$internal_staging_deep_link_limit = $this->config->get_option('internal_staging_deep_link_limit');
		$internal_staging_deep_link_limit = ($internal_staging_deep_link_limit) ? $internal_staging_deep_link_limit : WPSS_STAGING_DEFAULT_DEEP_LINK_REPLACE_LIMIT ;

		$enable_admin_login = $this->config->get_option('internal_staging_enable_admin_login');
		if ($enable_admin_login === 'yes') {
			$enable_admin_login = 'checked="checked"';
			$disable_admin_login = '';
			$login_custom_link =  '';
		} else {
			$enable_admin_login = '';
			$disable_admin_login = 'checked="checked"';
			$login_custom_link =  "style='display:none'";
		}

		$enable_load_images_from_live_site = $this->config->get_option('load_images_from_live_site_settings');

		if ($enable_load_images_from_live_site === 'yes') {
			$enable_load_images_from_live_site = 'checked="checked"';
			$disable_load_images_from_live_site = '';
		} else {
			$enable_load_images_from_live_site = '';
			$disable_load_images_from_live_site = 'checked="checked"';
		}

		$reset_permalink_wpss = $this->config->get_option('staging_is_reset_permalink');
		$reset_permalink_wpss = ($reset_permalink_wpss) ? 'checked="checked"' : '';

		$staging_login_custom_link = $this->config->get_option('staging_login_custom_link');

		$user_excluded_extenstions_staging = $this->config->get_option('user_excluded_extenstions_staging');

		$more_tables_div .= '
		<div class="table " id="super-stage-wp-tab-staging" style="padding-top: 20px;">

			<table class="form-table">
				<tr>
					<th scope="row">Include/Exclude Content (<a href="https://docs.wpsuperstage.com/article/43-how-to-include-exclude-files-in-creating-or-copying-staging-site" target="_blank" style="text-decoration: underline;">Need Help?</a>)</th>
					<td >
					<fieldset style="float: left; margin-right: 2%">
						<button class="button button-secondary wpss_dropdown" id="toggle_exlclude_files_n_folders_staging_wpss" style="width: 408px; outline:none; text-align: left;">
							<span class="dashicons dashicons-portfolio" style="position: relative; top: 3px; font-size: 20px"></span>
							<span style="left: 10px; position: relative;">Folders &amp; Files </span>
							<span class="dashicons dashicons-arrow-down" style="position: relative; top: 3px; left: 255px;"></span>
						</button>
						<div style="display:none" id="wpss_exc_files_staging"></div>
					<p class="description" style="font-size: 14px;width: 408px;">Non WordPress files from the root directory are excluded by default. Include them if you want.</p>
					</fieldset>
					<fieldset style="float: left; margin-right: 2%">
							<button class="button button-secondary wpss_dropdown" id="toggle_wpss_db_tables_staging" style="width: 408px; outline:none; text-align: left;">
								<span class="dashicons dashicons-menu" style="position: relative;top: 3px; font-size: 20px"></span>
								<span style="left: 10px; position: relative;">Database</span>
								<span class="dashicons dashicons-arrow-down" style="position: relative;top: 3px;left: 288px;"></span>
							</button>
							<div style="display:none" id="wpss_exc_db_files_staging"></div>
					</fieldset>
					<br><br>
				</tr>
				<tr>
					<td></td>
					<td><br><strong style="font-size: 14px;"> The following settings are common for Live-to-Staging & Staging-to-Live processes.</strong></td>
				</tr>
				<tr>
					<th scope="row">Exclude Files of These Extensions</th>
					<td>
						<fieldset>
							<input class="wpss-split-column" type="text" name="user_excluded_extenstions_staging" id="user_excluded_extenstions_staging"  placeholder="Eg. .mp4, .mov" value="'. esc_textarea($user_excluded_extenstions_staging) . '" />
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="db_rows_clone_limit_wpss">DB Rows Cloning Limit</label>
					</th>
					<td>
						<input name="db_rows_clone_limit_wpss" type="number" min="0" step="1" id="db_rows_clone_limit_wpss" value="'.esc_textarea($internal_staging_db_rows_copy_limit).'" class="medium-text">
					<p class="description">'. __( 'Reduce this number by a few hundred if staging process hangs at <strong>Failed to clone database</strong>', 'super-stage-wp' ).' </p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="files_clone_limit_wpss">Files Cloning Limit</label>
					</th>
					<td>
						<input name="files_clone_limit_wpss" type="number" min="0" step="1" id="files_clone_limit_wpss" value="'.esc_textarea($internal_staging_file_copy_limit).'" class="medium-text">
					<p class="description">'. __( 'Reduce this number by a few hundred if staging process hangs at <strong>Failed to copy files.</strong>', 'super-stage-wp' ).' </p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="deep_link_replace_limit_wpss">Deep Link Replacing Limit</label>
					</th>
					<td>
						<input name="deep_link_replace_limit_wpss" type="number" min="0" step="1" id="deep_link_replace_limit_wpss" value="'.esc_textarea($internal_staging_deep_link_limit).'" class="medium-text">
					<p class="description">'. __( 'Reduce this number by a few hundred if staging process hangs at <strong>Failed to replace links.</strong>', 'super-stage-wp' ).' </p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="reset_permalink_wpss">Reset Permalink</label>
					</th>
					<td>
					<input type="checkbox" id="reset_permalink_wpss" name="reset_permalink_wpss" value="1" '.esc_textarea($reset_permalink_wpss).'>
					<p class="description">'. __( 'Enabling this will reset the permalink to default one in staging site.', 'super-stage-wp' ).' </p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label>Require login to view the site</label>
					</th>
					<td>
					<fieldset >
						<label title="Yes">
							<input name="enable_admin_login_wpss"  type="radio" '.$enable_admin_login.' value="yes">
							<span class="">
								'.__( 'Yes', 'super-stage-wp' ).'
							</span>
						</label>
						<label title="No" style="margin-left: 10px !important;">
							<input name="enable_admin_login_wpss" type="radio" '.$disable_admin_login.' value="no">
							<span class="">
								'.__( 'No', 'super-stage-wp' ).'
							</span>
						</label>
						<p class="description">'. __( 'Select "Yes" if you don\'t want the front-end of your staging site to be visible to the public.', 'super-stage-wp' ).' </p>
						<br>
						<div id="login_custom_link" '. $login_custom_link.'">
							<label>Login Custom Link:</label>
							<br>
							<label>' . get_home_url() .'/ </label>
							<input  name="custom_admin_url" type="text" id="login_custom_link_wpss" value="'.esc_textarea($staging_login_custom_link).'" class="medium-text">
						<p class="description">'. __( 'Enter the string which links to your login page if you are using a custom login page instead the default WordPress login. ', 'super-stage-wp' ).' </p>
						</div>
					</fieldset>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label>Load Images from the Live Site</label>
					</th>
					<td>
					<fieldset >
						<label title="Yes">
							<input name="load_images_from_live_site_settings_radio_wpss"  type="radio" '.$enable_load_images_from_live_site.' value="yes">
							<span class="">
								'.__( 'Yes', 'super-stage-wp' ).'
							</span>
						</label>
						<label title="No" style="margin-left: 10px !important;">
							<input name="load_images_from_live_site_settings_radio_wpss" type="radio" '.$disable_load_images_from_live_site.' value="no">
							<span class="">
								'.__( 'No', 'super-stage-wp' ).'
							</span>
						</label>
						<p class="description">'. __( 'Select "Yes" if you want the images to be loaded directly from the Prod site URLs.', 'super-stage-wp' ).' </p>
						<br>
					</fieldset>
					</td>
				</tr>
				';

		return $more_tables_div. '</table> </div>';
	}

	public function admin_print_footer_scripts() {
		$hotlink_live_images_wpss = $this->config->get_option('load_images_from_live_site_settings');

		echo '<script type="text/javascript">

		var HOTLINK_LIVE_IMAGES_WPSS = "'.$hotlink_live_images_wpss.'";
		
		</script>';
		
	}

	public function upgrade_our_staging_plugin_wpss() {
		wpss_log('', "--------trying to upgrade_our_staging_plugin_wpss--------");

		$staging_details = $this->staging->get_staging_details();
		if(!empty($staging_details) && $staging_details['staging_folder']){
			$this->staging->staging_to_live_copy_files(true);
		}
	}

}
