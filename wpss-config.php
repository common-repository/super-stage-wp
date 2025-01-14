<?php
/**
* A class with functions the perform a backup of WordPress
*
* @copyright Copyright (C) 2011-2014 Awesoft Pty. Ltd. All rights reserved.
* @author Michael De Wildt (http://www.mikeyd.com.au/)
* @license This program is free software; you can redistribute it and/or modify
*          it under the terms of the GNU General Public License as published by
*          the Free Software Foundation; either version 2 of the License, or
*          (at your option) any later version.
*
*          This program is distributed in the hope that it will be useful,
*          but WITHOUT ANY WARRANTY; without even the implied warranty of
*          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*          GNU General Public License for more details.
*
*          You should have received a copy of the GNU General Public License
*          along with this program; if not, write to the Free Software
*          Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110, USA.
*/

class WPSS_Config {
	const MAX_HISTORY_ITEMS = 20;
	private $db,
			$options,
			$is_wpss_installed,
			$reset_login_if_failed,
			$fs,
			$base;

	public function __construct() {
		$this->db = WPSS_Factory::db();
		// $this->base = new Utils_Base();
	}

	private function init_fs(){

		if ($this->fs) {
			return ;
		}

		global $wp_filesystem;

		if ($wp_filesystem) {
			$this->fs = $wp_filesystem;
			return ;
		}

		if (!$wp_filesystem) {
			initiate_filesystem_wpss();
			if (empty($wp_filesystem)) {
				stop_if_ongoing_backup_wpss();
				send_response_wpss('FS_INIT_FAILED-CONFIG');
			}
		}

		$this->fs = $wp_filesystem;
	}

	private function is_wpss_installed(){
		if ($this->is_wpss_installed) {
			return true;
		}

		if (!defined('WPSS_BRIDGE') && class_exists('WPSS_Base_Factory')) {
			$this->is_wpss_installed = WPSS_Base_Factory::get('WPSS_App_Functions')->is_wpss_installed();
		} else {
			$this->is_wpss_installed = true;
		}

		return $this->is_wpss_installed;
	}

	public static function get_alternative_backup_dir() {
		return wp_normalize_path(WPSS_WP_CONTENT_DIR);
	}

	public static function get_default_backup_dir() {
		return wp_normalize_path(WPSS_WP_CONTENT_DIR . '/uploads');
	}

	public function get_backup_dir($only_path = false) {
		$backup_db_path = wpss_get_tmp_dir();

		if (empty($backup_db_path)) {
			$this->choose_db_backup_path();
		}

		$backup_db_path = wpss_get_tmp_dir();

		if (empty($backup_db_path)) {
			return false;
		}

		if (!$only_path) {
			if(!defined('WPSS_TEMP_DIR_BASENAME')){
				define('WPSS_TEMP_DIR_BASENAME', 'wpSS');
			}
			$path = wp_normalize_path($backup_db_path . '/'. WPSS_TEMP_DIR_BASENAME .'/backups');
		} else {
			$path = wp_normalize_path($backup_db_path);
		}

		return  wpss_replace_abspath($path);
	}


	// public function replace_slashes($data) {
	// 	return wp_normalize_path($data);
	// }

	public function set_option($name, $value) {

		if(!$this->is_wpss_installed()){
			wpss_log(array(), '--------Install not completed yet--------');
			return false;
		}

		//Short circut if not changed
		// include_once( WPSS_ABSPATH . 'wp-admin/includes/plugin.php' );

		// // check for plugin using plugin name
		// if (!is_plugin_active( 'super-stage-wp/super-stage-wp.php' ) ) {
		// 	wpss_log(array(), '---------not activated------------');
		// 	return false;
		// }
		if ($this->get_option($name) === $value) {
			return $this;
		}

		$old_value = $this->db->get_var(
			$this->db->prepare("SELECT * FROM {$this->db->base_prefix}wpss_options WHERE name = %s", $name)
		);

		if ( $value === $old_value) {
			return false;
		}

		if (empty($old_value)) {
			$this->db->insert($this->db->base_prefix . "wpss_options", array(
				'name' => $name,
				'value' => $value,
			));
		} else {
			$this->db->update(
				$this->db->base_prefix . 'wpss_options',
				array('value' => $value),
				array('name' => $name)
			);
		}

		$this->options[$name] = $value;

		return $this;
	}

	public function delete_option($option_name){
		return $this->db->delete("{$this->db->base_prefix}wpss_options", array( 'name' => $option_name ));
	}

	public function set_wpss_installed_true() {
		$this->is_wpss_installed = true;
	}

	public function get_option($name, $no_cache = false) {
		if(!$this->is_wpss_installed()){
			// wpss_log(array(), '--------Install not completed yet--------');
			return false;
		}

		if (!isset($this->options[$name]) || $no_cache) {
			$this->options[$name] = $this->db->get_var(
				$this->db->prepare("SELECT value FROM {$this->db->base_prefix}wpss_options WHERE name = %s", $name)
			);
		}

		return $this->options[$name];
	}

	public function append_option_arr_bool_compat($option_name, $new_val, $error_message = null) {
		if (!$new_val || $new_val == 1) {
			$this->set_option($option_name, $new_val);
			return true;
		}
		$prev_data = array();
		$raw_prev_data = $this->get_option($option_name);
		if (!empty($raw_prev_data)) {
			$prev_data = unserialize($raw_prev_data);
			if (!empty($prev_data)) {
				if (!empty($error_message)) {
					$prev_data[$error_message][] = $new_val;
				} else {
					$prev_data[] = $new_val;
				}
			}
		} else {
			if (!empty($error_message)) {
				$prev_data[$error_message][] = $new_val;
			} else {
				$prev_data[] = $new_val;
			}
		}
		$this->set_option($option_name, serialize($prev_data));
	}

	public function get_option_arr_bool_compat($option_name) {
		$this_ser = $this->get_option($option_name);
		if ($this_ser) {
			$this_arr = unserialize($this_ser);
			if ($this_arr) {
				return $this_arr;
			} else {
				return 1;
			}
		} else if (!$this_ser) {
			return 0;
		}
	}

	public function choose_db_backup_path() {
		$dump_location = self::get_default_backup_dir();
		$dump_location_tmp = $dump_location . '/' . WPSS_TEMP_DIR_BASENAME . '/backups';

		if (file_exists($dump_location_tmp)) {
			$this->set_paths_flags($dump_location);
			return true;
		}

		// $this->base->createRecursiveFileSystemFolder($dump_location_tmp, '', false);

		if (!file_exists($dump_location_tmp) ||  !is_writable($dump_location_tmp)) {
			return false;
		}

		$this->set_paths_flags($dump_location);
		return true;
	}

	public function get_default_backup_dump_dir(){
		return self::get_default_backup_dir() . '/' . WPSS_TEMP_DIR_BASENAME . '/backups';
	}

	private function set_paths_flags($path){
		$prev_path = wpss_get_tmp_dir($create = false);

		if (!empty($prev_path) && $prev_path == $path) {
			return true;
		}

		$path = wpss_remove_fullpath($path);

		$this->set_option('backup_db_path', $path);
		$this->set_option('site_abspath', WPSS_ABSPATH);
		$this->set_option('site_db_name', DB_NAME);
		$this->set_option('wp_content_dir', WPSS_WP_CONTENT_DIR);
		$this->set_option('is_wp_content_dir_moved_outside_root', $this->is_outside_content_dir());

		global $wpdb;
		$this->set_option('site_db_prefix', $wpdb->base_prefix);
	}

	private function is_outside_content_dir(){
		return dirname(WPSS_ABSPATH) === dirname(WPSS_WP_CONTENT_DIR) ? true : false;
	}

	public static function set_memory_limit() {
		@ini_set('memory_limit', WP_MAX_MEMORY_LIMIT);
	}

	public function get_cloud_path($source, $file, $root = false) {

		$dropbox_location = $this->get_cloud_root_dir();

		if ($root) {
			return $dropbox_location;
		}

		$source = rtrim($source, '/');
		$file   = wp_normalize_path($file);

		//do not do this on restore to staging site.
		if (wpss_is_wp_content_path($file) && wpss_is_wp_content_dir_moved_outside_root($this) && !$this->get_option('is_restore_to_staging')) {
			$folder = str_replace(dirname($source), $dropbox_location, $file);
		} else {
			$folder = str_replace($source, $dropbox_location, $file);
		}

		return ltrim(dirname($folder), '/');
	}

	public function get_cloud_root_dir(){
		if (!$this->get_option('dropbox_location')) {
			$dropbox_location = $this->get_dropbox_folder_tc();
			$this->set_option('dropbox_location', $dropbox_location);
			return $dropbox_location;
		}

		return  $this->get_option('dropbox_location');
	}

	public function get_dropbox_folder_tc() {
		$this_site_name = str_replace(array(
			"_",
			"/",
			"~",
		), array(
			"",
			"-",
			"-",
		), rtrim($this->remove_http(get_bloginfo('url')), "/"));

		$cur_repo = $this->get_option('default_repo');

		if($cur_repo == 'g_drive'){
			$this_site_name = $this_site_name . '-' . time();
		}

		return $this_site_name;
	}

	public function remove_http($url = '') {
		if ($url == 'http://' OR $url == 'https://') {
			return $url;
		}
		return preg_replace('/^(http|https)\:\/\/(www.)?/i', '', $url);

	}

	public function reset_restore_flags(){
		$this->set_option('in_progress_restore', false);
		$this->set_option('is_running_restore', false);
		$this->set_option('cur_res_b_id', false);
		$this->set_option('start_renaming_sql', false);
		return $this;
	}

	public function set_last_cron_trigger_time(){
		$tt = time();
		$usertime_full_stamp = $this->cnvt_UTC_to_usrTime($tt);
		$usertime_full = date('j M, g:ia', $usertime_full_stamp);
		$this->set_option('last_cron_triggered_time', $usertime_full);
	}

	public function truncate_current_backup_tables(){
		$processed = new WPSS_Processed_iterator();
		$processed->truncate();
		global $wpdb;
		$wpdb->query("TRUNCATE TABLE `" . $wpdb->base_prefix . "wpss_current_process`");
	}

	public function clear_amazon_chunks(){
		if ($this->get_option('default_repo') !== 's3') {
			return ;
		}

		if( wpss_is_auto_generated_iam() ){
			wpss_log(array(),'-----------WPSS cloud does clear_amazon_chunks automatically ----------------');
			return ;
		}

		$dropbox_location = $this->get_option('dropbox_location');
		WPSS_Factory::get('s3')->abort_all_multipart_uploads($dropbox_location);
	}

	public function clear_current_backup_history(){

		$cur_backup_id = wpss_get_cookie('backupID');

		if (empty($cur_backup_id)) {
			return false;
		}

		global $wpdb;
		$delete_processed   = $wpdb->query("DELETE FROM `" . $wpdb->base_prefix . "wpss_processed_files` WHERE backupID= ".$cur_backup_id);
		$delete_backup_meta = $wpdb->query("DELETE FROM `" . $wpdb->base_prefix . "wpss_backups` WHERE backup_id=".$cur_backup_id);
	}

	public function set_main_cycle_time(){
		$user_time_now = $this->get_wpss_user_today_date_time('Y-m-d');
		$this->set_option('wpss_today_main_cycle', $user_time_now);
		$this->set_option('wpss_main_cycle_running', false);
	}

	public function reset_complete_flags(){
		//Daily Main cycle complete process
		if ($this->get_option('wpss_main_cycle_running')) {
			$this->set_main_cycle_time();
		}
		$this->set_option('in_progress', false);
		$table_name = WPSS_Factory::db()->prefix . 'wpss_processed_files';
		$table_status_name = $table_name . "_meta_data";
		$this->set_option($table_status_name, '');
		$this->set_option('db_meta_data_status', false);
		$this->set_option('add_backup_general_data', false);
		$this->set_option('gotfileslist', false);
		$this->set_option('this_backup_exclude_files_done', false);
		$this->set_option('current_process_file_id', false);
		$this->set_option('is_meta_data_backup_failed', '');
		$this->set_option('meta_data_upload_offset', 0);
		$this->set_option('meta_data_upload_id', '');
		$this->set_option('meta_data_upload_s3_part_number', '');
		$this->set_option('meta_data_upload_s3_parts_array', '');
		$this->set_option('meta_data_backup_process', '');
		$this->set_option('db_meta_data_status', '');
		$this->set_option('recent_backup_ping', false);
		$this->set_option('is_running', false);
		$this->set_option('schedule_backup_running', false);
		$this->set_option('do_wpss_meta_data_backup', false);
		$this->set_option('allow_system_to_backup_meta_file', false);
		$this->set_option('got_exclude_files', false);
		$this->set_option('got_exclude_tables', false);
		$this->set_option('exclude_already_excluded_folders', false);
		$this->set_option('insert_exclude_file_list', false);
		$this->set_option('done_all_exclude_files_tables', false);
		// $this->set_option('cached_wpss_g_drive_folder_id', 0);
		// $this->set_option('cached_g_drive_this_site_main_folder_id', 0);
		$this->set_option('backup_before_update_progress', false);
		$this->set_option('show_sycn_db_view_wpss', true);
		$this->set_option('show_processing_files_view_wpss', true);

		$this->set_option('wpss_main_cycle_running', 0);
		$this->set_option('wpss_db_backup_completed', false);
		$this->set_option('check_current_files_state_offset', false);
		$this->set_option('get_current_files_state', false);
		$this->set_option('wpss_current_backup_type', 0);
		$this->set_option('sql_gz_compression_offset', false);
		$this->set_option('sql_gz_compression', false);

		$this->set_option('wpss_profiling_start', 0);
		$this->set_option('insert_default_excluded_files', false);
		$this->set_option('shell_db_dump_status', false);
		$this->set_option('shell_db_dump_prev_size', false);
		$this->set_option('first_backup_started_atleast_once', true);
		$this->set_option('reset_chunk_upload_on_failure_count', false);
		$this->set_option('backup_current_action', false);
		$this->set_option('restore_current_action', false);
		$this->set_option('is_latest_restore_point', false);
		$this->set_option('take_full_backup_once', false);
		$this->set_option('trigger_table_backedup', false);
		$this->set_option('triggers_schema_changed_tables', false);
		$this->set_option('no_backup_taken_after_restore', false);
		$this->set_option('is_ignored_backup', false);
		$this->set_option('is_this_process', false);
		$this->set_option('is_error', false);
		$this->set_option('last_backup_request', false);
		$this->set_option('db_encrypt_completed', false);
		$this->set_option('collected_tables_for_backups', false);
		$this->set_option('collected_tables_for_backups_offset', false);
		$this->set_option('collected_tables_for_backups_size', false);
		$this->set_option('iterator_file_size', false);
		$this->set_option('is_migration_backup', false);
	}

	public function complete($this_process = null, $ignored_backup = false, $is_error = false) {
		wpss_log(func_get_args(), __FUNCTION__);
		if ($this_process == 'restore') {
			return $this->reset_restore_flags();
		}
		$in_progress = $this->get_option('in_progress', true);
		if(empty($in_progress)){
			wpss_log(array(), '-----------Break in middle because force stop 2-------------');
			if ($this_process == 'logout') {
				return false;
			}

			send_response_wpss('backup_stopped_manually', 'BACKUP');
		}

		if ($this_process == 'logout') {
			return false;
		}

		do_action('check_any_upgrades_available_wpss', time());

		$this->set_option('is_ignored_backup', $ignored_backup);
		$this->set_option('is_this_process', $this_process);
		$this->set_option('is_error', $is_error);

		$this->complete_backup();
	}

	public function get_sql_files_count_current_backup_id(){
		$cur_backup_id = wpss_get_cookie('backupID');

		if (empty($cur_backup_id)) {
			return false;
		}

		global $wpdb;
		$sql_files_count = $wpdb->get_var("SELECT COUNT(file) FROM " . $wpdb->base_prefix . "wpss_processed_files WHERE backupID=$cur_backup_id AND file LIKE '%backup.sql%'");

		return $sql_files_count;
	}

	public function complete_backup(){
		$this->truncate_current_backup_tables();
		$this->reset_complete_flags();

		$this->clean_tmp_dirs();

	}

	private function clean_tmp_dirs(){
		$tmp_dir = $this->get_backup_dir();
		WPSS_Base_Factory::get('WPSS_App_Functions')->make_folders_empty($tmp_dir);

		do_action('refresh_realtime_tmp_wpss', false);
	}

	public function force_complete(){
		$this->clean_tmp_dirs();
		$this->reset_complete_flags();
		$this->truncate_current_backup_tables();
		$this->clear_amazon_chunks();
		$this->clear_current_backup_history();
		$this->force_complete_reset_flags();
		wpss_set_backup_in_progress_server(false);
	}

	public function force_complete_reset_flags(){
		$this->set_option('starting_first_backup', false);
	}

	public function send_first_backup_completed_email() {
		$email_data = array(
			'type' => 'first_backup_completed',
		);
		error_alert_wpss_server($email_data);
	}

	public function die_if_stopped() {
		$in_progress = $this->db->get_var("SELECT value FROM {$this->db->base_prefix}wpss_options WHERE name = 'in_progress'");
		if (!$in_progress) {
			$msg = __('Backup has been stopped.', 'wpss');
			WPSS_Factory::get('logger')->log($msg, wpss_get_cookie('backupID'));
			wpss_die_with_json_encode( array( 'error' => $msg ) );
		}
	}

	//Getting WPSS Timezone by given format
	// @format inputs should be in the list of datetime format parameter
	public function get_wpss_user_today_date_time($format, $timestamp = null) {
		if (empty($timestamp)) {
			$timestamp = time();
		}

		$wpss_timezone = $this->get_option('wpss_timezone');
		if(empty($wpss_timezone)){
			$wpss_timezone = 'UTC';
		}

		/*
		//Converting to UTC and then again to wpss timezone makes problem in some server
		// $user_tz = new DateTime(date('Y-m-d H:i:s', $timestamp), new DateTimeZone('UTC'));
		// $user_tz->setTimeZone(new DateTimeZone($wpss_timezone));
		// $user_tz_now = $user_tz->format($format);
		*/
		
		//directly change to user's timezone
		$date = new DateTime(date('Y-m-d H:i:s', $timestamp));
		$timezone_obj = $date->setTimezone(new DateTimeZone($wpss_timezone));
		$user_tz_now = $date->format($format);
		return $user_tz_now;
	}

	public function get_some_min_advanced_current_hour_of_the_day_wpss($time = 0) {
		$FORMAT = 'g:00 a';

		if (empty($time)) {
			$time = time();
		}

		$sleeped_time_stamp = $time + 300;

		$wpss_timezone = $this->get_option('wpss_timezone');
		$user_tz = new DateTime(date('Y-m-d H:i:s', $sleeped_time_stamp), new DateTimeZone('UTC'));
		$user_tz->setTimeZone(new DateTimeZone($wpss_timezone));
		$user_tz_now = $user_tz->format($FORMAT);
		return $user_tz_now;
	}

	public function get_adjusted_timestamp_for_that_timezone_wpss($scheduled_time_string) {
		$hour_from_string_arr = explode(':', $scheduled_time_string);
		$hour_from_string = $hour_from_string_arr[0];

		wpss_log($hour_from_string, "--------hour_from_string--------");

		$this_timestamp = mktime($hour_from_string, 0, 0);
		$adjusted_timestamp = $this_timestamp - (60 * 2); //2 mins adjustment

		$tz_formatted_timestamp = $this->get_wpss_user_today_date_time('u', $adjusted_timestamp);

		return $tz_formatted_timestamp;
	}


	public function reset_plans(){
		$this->set_option('plan_info', json_encode(array(), true));
		$this->set_option('privileges_wpss', false);
		$this->set_option('valid_user_but_no_plans_purchased', false);
		$this->set_option('card_added', false);
		$this->set_option('active_sites', false);
		$this->set_option('user_slot_info', json_encode(array(), true));
	}

	private function process_service_info(&$cust_info) {
		if (empty($cust_info) || !empty($cust_info->error)) {
			$err_msg = $this->process_wpss_error_msg_then_take_action($cust_info);

			$this->set_option('card_added', false);

			if($err_msg == 'logged_in_but_no_plans_yet'){
				$this->do_options_for_no_plans_yet($cust_info);

				return true;			//hack
			}
			return false;
		} else {
			return true;
		}
	}

	private function save_plan_info_limited(&$cust_info) {
		wpss_log(func_get_args(), "--------" . __FUNCTION__ . "--------");
		if (empty($cust_info) || empty($cust_info->plan_info_limited)) {
			return $this->set_option('plan_info_limited', false);
		} else {
			$plans = json_decode(json_encode($cust_info->plan_info_limited),true);
			wpss_log($plans,'----------$plans----------------');
			return $this->set_option('plan_info_limited', serialize($plans));
		}
	}

	public function do_options_for_no_plans_yet(&$cust_info)
	{
		$this->set_option('valid_user_but_no_plans_purchased', true);
		$this->set_option('card_added', $cust_info->is_card_added);
		$this->set_option('plan_info', json_encode($cust_info->plan_info, true));
		$this->set_option('user_slot_info', json_encode($cust_info->this_user_slot, true));
		$this->set_option('active_sites', json_encode($cust_info->active_sites, true));

		$this->set_option('is_user_logged_in', true);	//hack
	}

	public function process_subs_info_wpss($cust_req_info=null) {

		if(empty($cust_req_info->slot_info)){
			// $this->set_option('subscription_info', false);
			return false;
		}

		$sub_info = (array)$cust_req_info->slot_info;

		$this->set_option('subscription_info', json_encode($sub_info));
	}

	private function process_privilege_wpss($cust_req_info = null) {

		if(empty($cust_req_info->subscription_features)){
			$this->reset_privileges();
			return false;
		}

		$sub_features = (array)$cust_req_info->subscription_features;

		$privileged_feature = array();
		$privileges_args = array();

		foreach($sub_features as $plan_id => $single_sub){
			foreach($single_sub as $key => $v){
				$privileged_feature[$v->type][] = 'WPSS_' . ucfirst($v->feature);
				$privileges_args['WPSS_' . ucfirst($v->feature)] = (!empty($v->args)) ? $v->args : array();
			}
		}

		//Remove on production
		array_push($privileges_args, 'WPSS_Rollback');
		array_push($privileged_feature['pro'], 'WPSS_Rollback');

		$this->set_option('privileges_wpss', json_encode($privileged_feature));
		$this->set_option('privileges_args', json_encode($privileges_args));
		$revision_limit = new WPSS_Revision_Limit();
		$revision_limit->update_eligible_revision_limit($privileges_args);
	}

	public function reset_privileges() {
		$this->set_option('privileges_wpss', false);
		$this->set_option('privileges_args', false);
	}

	public function hash_pwd($str) {
		return md5($str);
	}

	public function check_if_cron_service_exists() {
		if (!$this->get_option('wpss_server_connected') || !$this->get_option('appID') || $this->get_option('signup') != 'done') {
			if ($this->get_option('main_account_email')) {
				return signup_wpss_server_wpss();
			}
		}
		return true;
	}

	public function get_last_login_error_msg() {
		if (empty($_GET['error'])) {
			return 'Oops. The login details seems to be incorrect. Please try again.';
		}
		return sanitize_text_field($_GET['error']);
	}

	public function process_wpss_error_msg_then_take_action(&$cust_info) {
		$err_msg = 'Oops. The login details seems to be incorrect. Please try again.';

		if ($cust_info->error == 'process_site_validation') {
			$err_msg = 'Oops. Trial period expired for this site.';

			if(!empty($cust_info->extra) && !empty($cust_info->error)){
				if($cust_info->error == 'no_free_slot_available'){
					$err_msg = 'Oops. This site seems to be connected with some other account.';
				}
			} else {
				return 'logged_in_but_no_plans_yet';
			}
		} else if ($cust_info->error == 'no_slot_available') {
			$err_msg = 'Oops. This site seems to be connected with some other account.';
		}

		if(!$this->reset_login_if_failed){
			wpss_log(array(), '--------NOT UI login Request--------');
			return false;
		}

		wpss_log(array(), '--------UI login request--------');

		$this->set_option('main_account_email', false);
		$this->set_option('main_account_pwd', false);
		$this->set_option('privileges_wpss', false);

		return array('error' => $err_msg );
	}

	public function remove_garbage_files($options = array('is_restore' => false), $hard_reset = false) {
		try {

			wpss_log(get_backtrace_string_wpss(), "--------removing garbage files--------");

			$this->init_fs();

			$this_config_like_file = $this->wp_filesystem_safe_abspath_replace(WPSS_ABSPATH);
			$this_config_like_file = $this_config_like_file . 'config-like-file.php';

			if ($this->fs->exists($this_config_like_file)) {
				$this->fs->delete($this_config_like_file);
			}

			$this->remove_tmp_dir();

			if(!$this->get_option('is_staging_running')){
				$current_bridge_file_name = $this->get_option('current_bridge_file_name');
				if (!empty($current_bridge_file_name)) {
					$root_bridge_file_path = WPSS_ABSPATH . '/' . $current_bridge_file_name;
					$root_bridge_file_path = $this->wp_filesystem_safe_abspath_replace($root_bridge_file_path);
					$this->delete_files_of_this_folder($root_bridge_file_path, $options);
					$this->fs->delete($root_bridge_file_path);
				}
			}

			$this_backups = $this->wp_filesystem_safe_abspath_replace(WPSS_ABSPATH . '/backups');
			$this->delete_files_of_this_folder($this_backups, $options);
			$this->fs->delete($this_backups);

			$this->set_option('garbage_deleted', true);
			if (!$hard_reset) {
				$this->send_restore_complete_status();
			}
		} catch (Exception $e) {
			wpss_log(array(), "--------error --------");
		}
	}

	public function remove_tmp_dir(){
		$this_temp_backup_folder = wpss_get_tmp_dir() . '/' . WPSS_TEMP_DIR_BASENAME;
		$this_temp_backup_folder = $this->wp_filesystem_safe_abspath_replace($this_temp_backup_folder);
		$this->delete_files_of_this_folder($this_temp_backup_folder);
	}

	public function delete_files_by_path($root_bridge_file_path, $options){

		$is_staging_running = $this->get_option('is_staging_running');
		if (!$is_staging_running) {
			$root_bridge_file_path = $this->wp_filesystem_safe_abspath_replace($root_bridge_file_path);
		}
		$this->delete_files_of_this_folder($root_bridge_file_path, $options);

		$this->init_fs();

		$this->fs->delete($root_bridge_file_path);
	}

	public function save_encoded_not_safe_for_write_files($single_file) {
		$not_safe_for_write_files = array();

		$not_safe_for_write_files = $this->get_encoded_not_safe_for_write_files();

		$not_safe_for_write_files[$single_file] = 1;
		$this->set_option('not_safe_for_write_files', json_encode($not_safe_for_write_files));
	}

	public function get_encoded_not_safe_for_write_files() {
		$not_safe_for_write_files = array();

		$not_safe_for_write_files_ser = $this->get_option('not_safe_for_write_files');
		if ($not_safe_for_write_files_ser) {
			$not_safe_for_write_files = json_decode($not_safe_for_write_files_ser, true);
		}

		return $not_safe_for_write_files;
	}

	public function delete_files_of_this_folder($folder_name, $options = array('is_restore' => false)) {
		$folder_name = trailingslashit($folder_name);

		$this->init_fs();

		if (!$this->fs->is_dir($folder_name)) {
			return;
		}

		$dirlist = $this->fs->dirlist($folder_name);
		$folder_name = trailingslashit($folder_name);

		if (empty($dirlist)) {
			$this->fs->delete($folder_name);
			return;
		}
		foreach ($dirlist as $filename => $fileinfo) {
			if ('f' == $fileinfo['type']) {
				$this->fs->delete($folder_name . $filename);
				if (!empty($options['is_restore'])) {
					is_wpss_restore_request_timed_out();
				} else if (!empty($options['is_backup'])) {
					check_timeout_cut_and_exit_wpss();
				}
			} elseif ('d' == $fileinfo['type']) {
				$this->delete_files_of_this_folder($folder_name . $filename);
				$this->delete_files_of_this_folder($folder_name . $filename); //second time to delete empty folders
			}
		}
	}

	public function delete_empty_folders($this_folder = null, $prev_dir_deleted_count = 0) {
		$this_folder = trailingslashit($this_folder);
		$this->init_fs();
		$this_folder_dir_list = $this->fs->dirlist($this_folder);
		$is_any_exists_count = 0;
		if (!empty($this_folder_dir_list)) {
			foreach ($this_folder_dir_list as $key => $value) {
				$is_any_exists_count++;
				if ($value['type'] == 'd') {
					$prev_del_count = $this->delete_empty_folders($this_folder . '/' . $key, $prev_dir_deleted_count);
					$is_any_exists_count -= $prev_del_count;
				}
			}
		}
		if ($is_any_exists_count < 1) {
			$prev_dir_deleted_count++;
			$this->fs->delete($this_folder);
			return $prev_dir_deleted_count;
		}
	}

	public static function init_delete_user_recorded_exculuded_files() {
		$files_array = get_user_excluded_files_folders_from_settings_wpss();
		WPSS_Factory::get('fileList')->delete_excluded_files($files_array);
	}

	public function create_dump_dir($options = array('is_bridge' => false)) {
		if(!is_wpss_server_req() && !is_admin()){
			return false;
		}

		if (!$options['is_bridge']) {
			require_once WPSS_ABSPATH . "wp-admin/includes/class-wp-filesystem-base.php";
			require_once WPSS_ABSPATH . "wp-admin/includes/class-wp-filesystem-direct.php";
			require_once WPSS_ABSPATH . "wp-admin/includes/class-wp-filesystem-ftpext.php";
			require_once WPSS_ABSPATH . "wp-admin/includes/class-wp-filesystem-ssh2.php";
			require_once WPSS_ABSPATH . "wp-admin/includes/class-wp-filesystem-ftpsockets.php";

			initiate_filesystem_wpss();
		}

		$backup_dir = $this->get_backup_dir();

		if( empty ( $backup_dir ) ){
			stop_if_ongoing_backup_wpss();
			$error_message = sprintf(__("WordPress Time Capsule requires write access to '%s', please ensure it exists and has write permissions.", 'wpss'), $this->get_default_backup_dump_dir());
			throw new Exception($error_message);
		}

		if (!file_exists($backup_dir)){
			return false;
		}

		$this->create_silence_file();
		return true;
	}

	public function wp_filesystem_safe_abspath_replace($file_path) {

		$file_path = trailingslashit($file_path);
		$options = WPSS_Factory::get('config');
		$this->init_fs();
		$safe_path = str_replace(WPSS_ABSPATH, wp_normalize_path($this->fs->abspath()), wp_normalize_path($file_path));
		return wp_normalize_path($safe_path);
	}

	public function replace_to_original_abspath($file_path){
		$is_staging_running = $this->get_option('is_staging_running');
		if($is_staging_running){
			$site_abspath = $this->get_option('site_abspath');
			$this->init_fs();
			$safe_path = str_replace($this->fs->abspath(), $site_abspath, $file_path);
		} else{
			return $file_path;
		}
		return $safe_path;
	}

	public function tc_file_system_copy_dir($from, $to = '', $action = array('multicall_exit' => false)) {

		$from = trailingslashit($from);
		$to = trailingslashit($to);

		$this->init_fs();
		$dirlist = $this->fs->dirlist($from);

		foreach ((array) $dirlist as $filename => $fileinfo) {
			if ('f' == $fileinfo['type'] && $filename != '.htaccess') {
				if (!$this->tc_file_system_copy($from . $filename, $to . $filename, false, FS_CHMOD_FILE)) {
					$this->fs->chmod($to . $filename, 0644);
					if (!$this->tc_file_system_copy($from . $filename, $to . $filename, false, FS_CHMOD_FILE)) {
						return false;
					}
				}
				if ($action['multicall_exit'] == true) {
					is_wpss_restore_request_timed_out();
				}
			} elseif ('d' == $fileinfo['type']) {
				if (!$this->fs->is_dir($to . $filename)) {
					if (!$this->fs->mkdir($to . $filename, FS_CHMOD_DIR)) {
						return false;
					}
				}
				$result = $this->tc_file_system_copy_dir($from . $filename, $to . $filename, $action);
				if (!$result) {
					return false;
				}
			}
		}
		return true;
	}

	public function tc_file_system_copy($source, $destination, $overwrite = false, $mode = 0644) {
		$this->init_fs();

		$copy_result = $this->fs->copy($source, $destination, $overwrite, $mode);

		if (!$copy_result && !$overwrite) {
			return true;
		}
		return $copy_result;
	}

	public function cnvt_UTC_to_usrTime($time_stamp = "1453964442", $format = "F j, Y, g:i a") {
		$user_time_zone = $this->get_option('wpss_timezone');

		if (empty($user_time_zone)) {
			$user_time_zone = 'utc';
		}

		date_default_timezone_set('UTC');

		$str_time = date($format, $time_stamp);

		$local_time = new DateTime($str_time);
		$tz_start = new DateTimeZone($user_time_zone);
		$local_time->setTimezone($tz_start);
		$start_date_time = (array) $local_time;

		$new_time = $start_date_time['date'];
		$new_time_stamp = strtotime($new_time);

		return $new_time_stamp;
	}

	private function create_silence_file() {
		$silence = $this->get_backup_dir();
		$silence = $silence. '/' . 'index.php';

		$this->init_fs();

		if (!$this->fs->exists($silence)) {
			$this->fs->put_contents($silence, "<?php\n// Silence is golden.\n");
		}
	}

	public function get_database_encryption_settings($type){
		$settings = $this->get_option('database_encrypt_settings');
		$settings = !empty($settings) ? unserialize($settings) : array();

		if (empty($settings)) {
			return false;
		}

		if ($type === 'status') {
			return (!empty($settings['status']) && $settings['status'] === 'yes') ? true : false ;
		}

		return !empty($settings['key']) ? base64_decode($settings['key']) : '';
	}

	public function decrypt($fullpath, $key, $to_temporary_file = false) {
		wpss_log(func_get_args(), "--------" . __FUNCTION__ . "--------");

		// include Rijndael library from phpseclib
		$ensure_phpseclib = wpss_ensure_phpseclib('Crypt_Rijndael', array('Crypt/Base', 'Crypt/Rijndael'));

		if (is_wp_error($ensure_phpseclib)) {
			if (defined('WPSS_BRIDGE')) {
				WPSS_Factory::get('logger')->log("Failed to load phpseclib classes (" . $ensure_phpseclib->get_error_code() . "): " . $ensure_phpseclib->get_error_message(), 'restores', $this->restore_id);
			}

			return false;
		}

		if (defined('WPSS_DECRYPTION_ENGINE')) {
			if ('openssl' == WPSS_DECRYPTION_ENGINE) {
				$rijndael->setPreferredEngine(CRYPT_ENGINE_OPENSSL);
			} elseif ('mcrypt' == WPSS_DECRYPTION_ENGINE) {
				$rijndael->setPreferredEngine(CRYPT_ENGINE_MCRYPT);
			} elseif ('internal' == WPSS_DECRYPTION_ENGINE) {
				$rijndael->setPreferredEngine(CRYPT_ENGINE_INTERNAL);
			}
		}

		// open file to read
		if (false === ($file_handle = fopen($fullpath, 'rb'))) return false;

		$decrypted_path = dirname($fullpath).'/decrypt_'.basename($fullpath).'.tmp';
		// open new file from new path
		if (false === ($decrypted_handle = fopen($decrypted_path, 'wb+'))) return false;

		// setup encryption
		$rijndael = new Crypt_Rijndael();
		$rijndael->setKey($key);
		$rijndael->disablePadding();
		$rijndael->enableContinuousBuffer();

		$file_size = filesize($fullpath);
		$bytes_decrypted = 0;

		// loop around the file
		while ($bytes_decrypted < $file_size) {
			// read buffer sized amount from file
			if (false === ($file_part = fread($file_handle, WPSS_CRYPT_BUFFER_SIZE))) return false;
			// check to ensure padding is needed before decryption
			$length = strlen($file_part);
			if (0 != $length % 16) {
				$pad = 16 - ($length % 16);
				$file_part = str_pad($file_part, $length + $pad, chr($pad));
				// $file_part = str_pad($file_part, $length + $pad, chr(0));
			}

			$decrypted_data = $rijndael->decrypt($file_part);

			$is_last_block = ($bytes_decrypted + strlen($decrypted_data) >= $file_size);

			$write_bytes = min($file_size - $bytes_decrypted, strlen($decrypted_data));
			if ($is_last_block) {
				$is_padding = false;
				$last_byte = ord(substr($decrypted_data, -1, 1));
				if ($last_byte < 16) {
					$is_padding = true;
					for ($j = 1; $j<=$last_byte; $j++) {
						if (substr($decrypted_data, -$j, 1) != chr($last_byte)) $is_padding = false;
					}
				}
				if ($is_padding) {
					$write_bytes -= $last_byte;
				}
			}

			if (false === fwrite($decrypted_handle, $decrypted_data, $write_bytes)) return false;
			$bytes_decrypted += WPSS_CRYPT_BUFFER_SIZE;
		}

		// close the main file handle
		fclose($decrypted_handle);
		// close original file
		fclose($file_handle);

		// remove the crypt extension from the end as this causes issues when opening
		$fullpath_new = preg_replace('/\.crypt$/', '', $fullpath, 1);
		// //need to replace original file with tmp file

		$fullpath_basename = basename($fullpath_new);

		if ($to_temporary_file) {
			return array(
				'fullpath' 	=> $decrypted_path,
				'basename' => $fullpath_basename,
				'status' => 'success',
			);
		}

		if (false === rename($decrypted_path, $fullpath_new)){
			@unlink($fullpath);
			return false;
		}

		// need to send back the new decrypted path
		$decrypt_return = array(
			'fullpath' 	=> $fullpath_new,
			'basename' => $fullpath_basename,
			'status' => 'success',
		);

		@unlink($fullpath);

		return $decrypt_return;
	}

	public function enable_maintenance_mode($path) {

		if (empty($path)) {
			return ;
		}

		$path = wpss_add_trailing_slash($path);

		$file = $path . '.maintenance';

		$content = '<?php global $upgrading; $upgrading = time();';

		if (file_exists($file)) {

			return ;
		}

		@file_put_contents($file, $content);
	}

	public function staging_flush() {
		$flush_options = [
			'is_staging_running',
			'is_staging_completed',
			'staging_id',
			'same_server_staging_path',
			'same_server_staging_running',
			'same_server_staging_db_prefix',
			'same_server_staging_full_db_prefix',
			'same_server_clone_db_total_tables',
			'same_server_clone_db_completed_tables',
			'same_server_copy_staging',
			'same_server_replace_old_url',
			'same_server_replace_old_url_data',
			'same_server_replace_url_multicall_status',
			'same_server_get_folders',
			'last_staging_ping',
			'staging_tables_clone_new_status',
			'staging_tables_replace_link_status',
			'replace_links_row_count_staging',
		];

		foreach($flush_options as $v){
			$this->set_option($v, false);
		}
	}

	public function disable_maintenance_mode($path) {

		if (empty($path)) {
			return ;
		}

		$path = wpss_add_trailing_slash($path);

		$file = $path . '.maintenance';

		wpss_wait_for_sometime();

		if (!is_file($file) && !file_exists($file)) {
			return ;
		}

		unlink($file);
	}
}
