<?php

class WPSS_App_Functions {
	private $config,
			$wpdb,
			$logger,
			$current_iterator_table,
			$exclude_option,
			$wp_version,
			$allowed_free_disk_space;

	const RESET_CHUNK_UPLOAD_ON_FAILURE_LIMIT = 4;

	public function __construct(){
		//using common config here for not making config list complex
		$this->config = WPSS_Factory::get('config');
		$this->current_iterator_table = new WPSS_Processed_iterator();
		$this->allowed_free_disk_space = 1024 * 1024 * 10; //10 MB
		$this->retry_allowed_http_status_codes = array(5, 6, 7);
		$this->logger = WPSS_Factory::get('logger');
		$this->init_db();
	}

	public function init_db(){
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function set_user_to_access(){
		if ( ! function_exists( 'wp_get_current_user' ) ){
			include_once ABSPATH.'wp-includes/pluggable.php';
		}

		$username = $this->get_current_user_meta('user_login');

		if (empty($username)) {
			return false;
		}

		$default = (object) array(
			'wl_select_action'   => 'normal',
			'plugin_name'        => '',
			'author_name'        => '',
			'author_url'         => '',
			'plugin_description' => '',
			'hide_updates'	     => '',
			'hide_edit'	         => '',
			'allowed_pages'	     => '',
			'admin_username'	 => $username,
			'additional_control' => '',
		);

		$this->config->set_option('white_lable_details', serialize($default) );
	}

	private function get_cookiepath(){
		if (defined('COOKIEPATH')) {
			return COOKIEPATH;
		}

		if (function_exists('get_home_url')) {
			return get_home_url();
		}

		return home_url();
	}

	public function get_current_user_meta($key){
		if ( ! function_exists( 'wp_get_current_user' ) )
			include_once ABSPATH.'wp-includes/pluggable.php';

		$user = wp_get_current_user();

		if (empty($user) || empty($user->data) || empty($user->data->$key)) {
			return false;
		}

		return $user->data->$key;
	}

	public function shortern_plugin_slug($full_slug){
		$result = explode('/', $full_slug);
		return empty($result[0])? false : $result[0];
	}

	public function append_slugs_plugins($req_plugins){
		if (empty($req_plugins)) {
			return array();
		}

		// foreach ($req_plugins as &$inc_plugin) {
		// 	if (strstr($inc_plugin, '.php') === false) {
		// 		$inc_plugin .= '.php';
		// 	}
		// }


		$slugs = array();
		$plugins = get_plugins();

		foreach ($plugins as $slug => $plugin_meta) {
			$temp_slug = $this->shortern_plugin_slug($slug);
			if (in_array($temp_slug, $req_plugins)) {
				array_push($slugs, $slug);
			}
		}

		return $slugs;
	}

	public function is_user_purchased_this_class($classname = false){
		if (empty($classname)) return false;

		$data = $this->config->get_option('privileges_wpss');

		if (empty($data)) return false;

		$data = json_decode($data);

		if (empty($data)) return false;

		if (!empty($data->pro)) {
			$pro_arr = $data->pro;
		}

		if (!empty($data->lite)) {
			$pro_arr = $data->lite;
		}

		if (empty($pro_arr)) return false;

		$pro_arr_values = array_values($pro_arr);

		if (empty($pro_arr_values))	return false;

		if (in_array($classname, $pro_arr_values)) {
			return true;
		}

		return false;
	}

	public function is_free_user_wpss(){
		if($this->is_user_purchased_this_class('WPSS_Weekly_Backups') || !$this->is_user_purchased_this_class('WPSS_Daily_Backups')){
			return true;
		} else {
			return false;
		}
	}

	public function validate_dropbox_upgrade(){
		if($this->config->get_option('default_repo') != 'dropbox')
			return ;

		//check upgraded is successfull then return here
		if($this->verify_dropbox_api2_upgrade() === true)
			return ;

		//try upgrade if possible
		if($this->upgrade_dropbox_api1_to_api2() === false)
			return $this->remove_dropbox_api1_flags();

		//check upgrade status once again.
		if($this->verify_dropbox_api2_upgrade() === true)
			return ;

		//try upgrade if possible
		if($this->upgrade_dropbox_api1_to_api2() === false)
			return $this->remove_dropbox_api1_flags();

		//check upgrade status once again.
		if($this->verify_dropbox_api2_upgrade() === true)
			return ;

		return $this->remove_dropbox_api1_flags();
	}


	private function remove_dropbox_api1_flags(){
		$this->config->delete_option('access_token');
		$this->config->delete_option('access_token_secret');
		$this->config->delete_option('request_token');
		$this->config->delete_option('request_token_secret');
		$this->config->delete_option('oauth_state');
		$this->config->set_option('default_repo', '');
	}

	private function verify_dropbox_api2_upgrade(){
		//API-2 flags
		if ($this->config->get_option('dropbox_access_token') && $this->config->get_option('dropbox_oauth_state') === 'access' ){
			return true;
		}

		return false;
	}

	private function upgrade_dropbox_api1_to_api2(){
		//API-1 flags
		if (!$this->config->get_option('access_token') || !$this->config->get_option('access_token_secret')){
			return false;
		}

		//try upgrade once again
		$dropbox = WPSS_Factory::get('dropbox');
		$dropbox->migrate_to_v2();
	}

	public function die_with_json_encode($msg = 'empty data', $escape = 0){
		reset_last_backup_request_wpss();

		switch ($escape) {
			case 1:
			die(json_encode($msg, JSON_UNESCAPED_SLASHES));
			case 2:
			die(json_encode($msg, JSON_UNESCAPED_UNICODE));
		}
		die(json_encode($msg));
	}

	public function die_with_msg($msg){
		die($msg);
	}

	public function verify_ajax_requests($admin_check = true){

		// wpss_log($_REQUEST, "--------verify_ajax_requests--------");

		//verify its ajax request
		if ( empty($_REQUEST['logout']) && empty($_REQUEST['action']) ) {

			wpss_log($_REQUEST, "--------verify_ajax_requests---die no action-----");


			if(empty($_REQUEST['wpss_authorized'])){

				wpss_log(get_backtrace_string_wpss(), "--------verify_ajax_requests-----die no action-2--");

				$this->die_with_msg('you are not authorized');
			}

			return false;
		}

		if ( ! function_exists( 'check_ajax_referer' ) ){
			include_once ABSPATH.'wp-includes/pluggable.php';
		}

		//Verifies the Ajax request to prevent processing requests external of the site
		check_ajax_referer( 'wpss_nonce', 'security' );

		if (!$admin_check) {
			return true;
		}

		//Check request made by admin
		if ( !current_user_can( 'administrator' ) ) {

			wpss_log('', "--------verify_ajax_requests---die not administrator-----");

			$this->die_with_msg('you are not authorized');
		}
	}

	public function server_has_free_space(){
		if (!function_exists('disk_free_space')) {
			return true;
		}

		$available_bytes = disk_free_space(ABSPATH);

		if (empty($available_bytes)) {
			return true;
		}

		$available_bytes = (int) $available_bytes; //typecasting to int because disk_free_space returns floating values

		if ($available_bytes > $this->allowed_free_disk_space) {
			return true;
		}

		return false;
	}

	public function is_retry_allowed_curl_status($code){
		return in_array($code, $this->retry_allowed_http_status_codes);
	}

	public function reset_chunk_upload_on_failure($file, $err_msg){

		wpss_log(func_get_args(), __FUNCTION__);

		$backup_controller = new WPSS_BackupController();

		if (empty($file)) {
			$this->log_activity('backup', 'Chunk Failed and File path is empty so Backup stopped!');
			return false;
		}

		$file = wpss_remove_fullpath($file);

		$limit = $this->get_chunk_upload_on_failure_count($file);


		$allow_retry = false;

		if (++$limit < self::RESET_CHUNK_UPLOAD_ON_FAILURE_LIMIT) {
			$allow_retry = true;
		}

		$backup_id = $this->get_cur_backup_id();

		$this->update_chunk_upload_on_failure_count($file, $limit);

		//delete from wpss_processed_files
		$sql = "DELETE FROM `" . $this->wpdb->base_prefix . "wpss_processed_files` WHERE backupID= " . $backup_id . " AND file ='" . $file . "'";
		$this->wpdb->query($sql);

		//get current file id
		$sql = "SELECT id FROM `" . $this->wpdb->base_prefix . "wpss_current_process` WHERE file_path = '" . $file . "'";
		wpss_log($sql, '---------------$sql-----------------');

		$file_id = $this->wpdb->get_var($sql);
		wpss_log($file_id, '---------------$file_id-----------------');

		if (empty($file_id)) {
			$this->log_activity('backup', 'Chunk reset file id is empty so Backup stopped!');
			$backup_controller->proper_backup_force_complete_exit('reset_chunk_upload_on_failure file id empty so stopping backup');
		}

		if ($allow_retry) {

			//update in wpss_current_process
			$sql = "UPDATE `" . $this->wpdb->base_prefix . "wpss_current_process` SET status = 'Q' WHERE file_path ='" . $file . "'";
			$result = $this->wpdb->query($sql);

			global $current_process_file_id;
			$current_process_file_id = $this->config->set_option('current_process_file_id', $file_id);

			//end the request
			send_response_wpss('Failure on chunk upload - File has been reset !');

		}

		//update in wpss_current_process to skip this file
		$sql = "UPDATE `" . $this->wpdb->base_prefix . "wpss_current_process` SET status = 'S' WHERE file_path = '" . $file . "'";
		$result = $this->wpdb->query($sql);

		global $current_process_file_id;
		$current_process_file_id = $this->config->set_option('current_process_file_id', $file_id + 1);

		//chunk failed more than the limit so stop the backup
		$this->log_activity('backup', 'Chunk Failed more than the limit  - '.$limit.' So File skipped!');

		$error_array = array(
			'file_name' => $file,
			'error' => $err_msg,
		);

		$this->config->append_option_arr_bool_compat('mail_backup_errors', $error_array, 'unable_to_backup');

		send_response_wpss('Unable to upload chunk so file skipped');
	}

	private function get_chunk_upload_on_failure_count($file){
		$limit = $this->config->get_option('reset_chunk_upload_on_failure_count');
		if (empty($limit)) {
			return 0;
		}

		$limit = unserialize($limit);

		if (empty($limit)) {
			return 0;
		}

		if (!isset($limit[$file])) {
			return 0;
		}

		return $limit[$file];
	}

	private function update_chunk_upload_on_failure_count($file, $count){
		$limit = $this->config->get_option('reset_chunk_upload_on_failure_count');

		if (empty($limit)) {
			$limit = array($file => $count);
		} else {
			$limit = unserialize($limit);
			$limit[$file] =  $count;
		}

		$this->config->set_option('reset_chunk_upload_on_failure_count', serialize($limit));
	}

	public function get_cur_backup_id(){
		return wpss_get_cookie('backupID');
	}

	public function log_activity($type = false, $msg = false){
		switch ($type) {
			case 'backup':
				$backup_id = $this->get_cur_backup_id();
				break;
		}
		$this->logger->log(__($msg, 'wpss'), 'backups', $backup_id);
	}

	public function is_wpss_installed(){
		//check wpss_options table present if yes then its not a fresh install
		$small_letters_table_prefix = strtolower($this->wpdb->base_prefix);
		$result = $this->wpdb->get_results("SHOW TABLES LIKE '".$small_letters_table_prefix."wpss_options'", ARRAY_N);

		if(!empty($result)){

			return true;
		}

		$result2 = $this->wpdb->get_results("SHOW TABLES LIKE '".$this->wpdb->base_prefix."wpss_options'", ARRAY_N);

		if(!empty($result2)){

			return true;
		}

		return false;
	}

	public function get_server_info() {
		$anonymous = array();
		$anonymous['server']['PHP_VERSION'] = phpversion();
		$anonymous['server']['PHP_CURL_VERSION'] = curl_version();
		$anonymous['server']['PHP_WITH_OPEN_SSL'] = function_exists('openssl_verify');
		$anonymous['server']['PHP_MAX_EXECUTION_TIME'] = ini_get('max_execution_time');
		$anonymous['server']['MYSQL_VERSION'] = $this->wpdb->get_var("select version() as V");

		if (wpss_function_exist('php_uname')) {
			$anonymous['server']['OS'] = php_uname('s');
			$anonymous['server']['OSVersion'] = php_uname('v');
			$anonymous['server']['Machine'] = php_uname('m');
		} else{
			$anonymous['server']['OS'] = 'Nil';
			$anonymous['server']['OSVersion'] = 'Nil';
			$anonymous['server']['Machine'] = 'Nil';
		}

		$anonymous['server']['PHPDisabledFunctions'] = explode(',', ini_get('disable_functions'));
		array_walk($anonymous['server']['PHPDisabledFunctions'], 'trim_value_wpss');

		$anonymous['server']['PHPDisabledClasses'] = explode(',', ini_get('disable_classes'));
		array_walk($anonymous['server']['PHPDisabledClasses'], 'trim_value_wpss');

		return $anonymous;
	}

	public function set_start_time(){
		global $wpss_ajax_start_time, $wpss_profiling_start;
		$wpss_profiling_start = $wpss_ajax_start_time = time();
	}

	public function run_deactivate_plugin( $plugin, $prefix ) {

		if(is_multisite()){
			$this->run_deactivate_plugin_multi_site($plugin, $prefix);
		}

		$sql = "SELECT option_value FROM `" . $prefix . "options` WHERE option_name = 'active_plugins'";

		$active_plugins = $this->wpdb->get_var($sql);

		if (empty($active_plugins)) {
			return false;
		}

		$active_plugins = unserialize($active_plugins);


		$key = array_search($plugin, $active_plugins);

		if($key === false || $key === NULL){
			return false;
		}

		unset($active_plugins[$key]);

		sort( $active_plugins );

		unset($active_plugins[$plugin]);

		$sql = 'UPDATE `'.$prefix."options` SET option_value = '".serialize($active_plugins)."' WHERE option_name = 'active_plugins'";

		$result = $this->wpdb->query($sql);
	}

	public function run_deactivate_plugin_multi_site( $plugin, $prefix ) {

		$sql = "SELECT meta_value FROM `" . $prefix . "sitemeta` WHERE meta_key = 'active_sitewide_plugins'";

		$active_plugins = $this->wpdb->get_var($sql);

		if (empty($active_plugins)) {
			return false;
		}

		$active_plugins = unserialize($active_plugins);

		unset($active_plugins[$plugin]);

		$sql = 'UPDATE `'.$prefix."sitemeta` SET meta_value = '".serialize($active_plugins)."' WHERE meta_key = 'active_sitewide_plugins'";

		$result = $this->wpdb->query($sql);
	}

	public function run_activate_plugin( $plugin, $prefix ) {

		if(is_multisite()){
			$this->run_activate_plugin_multi_site($plugin, $prefix);
		}

		$sql = "SELECT option_value FROM `" . $prefix. "options` WHERE option_name = 'active_plugins'";

		$current = $this->wpdb->get_var($sql);

		if (!empty($current)) {
			$current = unserialize($current);
		} else {
			$current = array();
		}

		if ( in_array( $plugin, $current ) ) {
			return false;
		}

		$current[] = $plugin;
		sort( $current );
		$sql = 'UPDATE `'. $prefix. "options` SET option_value = '".serialize($current)."' WHERE option_name = 'active_plugins'";
		$result = $this->wpdb->query($sql);
	}

	public function run_activate_plugin_multi_site($plugin, $prefix){
		wpss_log(func_get_args(), "--------" . __FUNCTION__ . "--------");

		$sql = "SELECT meta_value FROM `" . $prefix. "sitemeta` WHERE meta_key = 'active_sitewide_plugins'";

		wpss_log($sql, '--------$sql--------');

		$current = $this->wpdb->get_var($sql);

		wpss_log($current, '--------$current--------');
		if (!empty($current)) {
			$current = unserialize($current);
		} else {
			$current = array();
		}
		wpss_log($current, '--------$current--------');

		$current_plugins = array_keys($current);

		wpss_log($current_plugins, '--------$current_plugins--------');

		if ( in_array( $plugin, $current_plugins ) ) {
			return false;
		}

		$current[$plugin] = time();

		wpss_log($current, '--------$current before sort--------');

		wpss_log($current, '--------$current sort--------');

		$sql = 'UPDATE `'. $prefix. "sitemeta` SET meta_value = '".serialize($current)."' WHERE meta_key = 'active_sitewide_plugins'";
		wpss_log($sql, '--------$sql--------');
		$result = $this->wpdb->query($sql);

		wpss_log($result, '--------$result--------');

		wpss_log($this->wpdb->last_error, '--------$this->wpdb->last_error--------');
	}

	public function mkdir_by_path($path, $recursive = true){
		
		// wpss_log(func_get_args(), "--------" . __FUNCTION__ . "--------");

		if (empty($path)) {
			return false;
		}

		$path = wpss_add_fullpath($path);

		// wpss_log($path,'-----------$path----------------');

		if (file_exists($path)) {
			return false;
		}

		$this->createRecursiveFileSystemFolder($path);
	}

	public function createRecursiveFileSystemFolder($this_temp_folder, $this_absbath_length = null, $override_abspath_check = true) {
		global $wp_filesystem;
		$home_path = get_home_path_wpss();

		if (!$wp_filesystem) {
			initiate_filesystem_wpss();
			if (empty($wp_filesystem)) {
				send_response_wpss('FS_INIT_FAILED-033');
				return false;
			}
		}

		$folders = explode('/', $this_temp_folder);
		foreach ($folders as $key => $folder) {
			$current_folder = '';
			for($i=0; $i<=$key; $i++){
				$sub_dir = (string) $folders[$i];
				if ($sub_dir === false || $sub_dir === '' || $sub_dir === NULL) {
					continue;
				}
				if (is_windows_machine_wpss() && empty($current_folder)) {
					$current_folder .= $sub_dir;
				} else {
					$current_folder .= '/'. $sub_dir;
				}
			}

			if (empty($current_folder)){
				continue;
			}

			if($override_abspath_check && stripos($current_folder.'/', $home_path) === false && $override_abspath_check && stripos($current_folder.'/', WPSS_WP_CONTENT_DIR) === false) {
				continue;
			}

			if ($wp_filesystem && !$wp_filesystem->is_dir($current_folder)) {
				if (!$wp_filesystem->mkdir($current_folder, 0755)) {
					$wp_filesystem->chmod(dirname($current_folder), 0755);
					if(!$wp_filesystem->mkdir($current_folder, 0755)){
					}
				}
			} else {

				if(strpos($current_folder, 'WPSS_TEMP_DIR_BASENAME') !== false && $wp_filesystem->chmod($current_folder, 0755)){
					//
				} else {
					//
				}
			}
		}
	}

	public function check_timeout_iter_file($path, &$temp_counter, &$timeout_limit, &$qry, &$offset){

		$break = is_wpss_timeout_cut();

		$files_count_check = 10000;
		if(defined('ITERATOR_FILES_COUNT_CHECK')){
			$files_count_check = ITERATOR_FILES_COUNT_CHECK;
		}

		global $iterator_files_count_this_call;
		if($iterator_files_count_this_call > $files_count_check){

			wpss_log($iterator_files_count_this_call, "--------cutting_by_iterator_files_count--------");

			$break = true;
		}

		if (!$break) {
			return ;
		}

		wpss_log($iterator_files_count_this_call, "--------normal_cutting--------");

		if (!empty($qry)) {
			$this->insert_into_current_process($qry);
			$qry = '';
		}

		$this->save_iterator_file_size();

		$this->current_iterator_table->update_iterator($path, $offset);

		if(is_any_ongoing_wpss_backup_process()){
			wpss_send_current_backup_response_to_server();
		} else {
			$this->die_with_json_encode(array("status" => "continue", 'msg' => 'Processing files ' . $path, "path" => $path, "offset" => $offset, 'percentage' => 75), 1);
		}

	}

	private function save_iterator_file_size(){
		global $wpss_iterator_file_size;

		if (empty($wpss_iterator_file_size)) {
			return ;
		}

		$this->config->set_option('iterator_file_size', $wpss_iterator_file_size);
	}

	public function insert_into_current_process($qry){
		$sql = "insert into " . $this->wpdb->base_prefix . "wpss_current_process (file_path, status, file_hash) values $qry";
		$result = $this->wpdb->query($sql);
	}

	public function get_processing_files_count($type){
		$dir = $this->current_iterator_table->get_unfnished_folder();

		if (empty($dir)) {
			return false;
		}

		$copying_file = str_replace(WPSS_ABSPATH, '', $dir->name);

		switch ($type) {
			case 'internal_staging':
				$msg = 'Copying  - ';
				break;
			case 'backup':
				$msg = ' ';
				break;
			case 'restore':
				$msg = 'Preparing files to restore - ';
				break;
		}

		if(wpss_is_dir($copying_file) && !empty($dir->offset)){
			// return $msg . $copying_file . ' ('.$dir->offset.')';
			$folders_processed = substr($dir->offset, 0, strpos($dir->offset, '-'));
			$folders_processed = empty($folders_processed) ? '' : ' ( processed ' . $folders_processed . ' folders )';
			return $msg . $copying_file . $folders_processed;
		}

		return $msg . $copying_file;
	}

	public function fancytree_format($data, $type){
		$format_result = array();
		foreach ($data as $key => $item) {
			$format_result[] = array(
				'title' => $item['name'],
				'key' => $item['slug'],
				'preselected' => $item['selected'],
				'unselectable' => empty($item['unselectable']) ? false : $item['unselectable'],
			);
		}
		return $format_result;
	}

	public function get_all_plugins_data($specific = false, $attr = false){

		if (!function_exists('get_plugins')) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();

		$plugins = array();
		if (!$specific) {
			return $all_plugins;
		}

		if ($attr === 'slug') {
			foreach ($all_plugins as $slug => $plugin) {
				$plugins[] = $slug;
			}
		}

		return $plugins;
	}

	public function get_all_themes_data($specific = false, $attr = false){
		if (!function_exists('wp_get_themes')) {
			include_once ABSPATH . 'wp-includes/theme.php';
		}

		$all_themes = wp_get_themes();
		$themes = array();
		if (!$specific) {
			return $all_themes;
		}

		if ($attr === 'slug') {
			foreach ($all_themes as $slug => $theme) {
				$themes[] = $slug;
			}
		}

		return $themes;
	}

	public function update_staging_enable_admin_key($fresh = false){
		$current_setting = $this->config->get_option('internal_staging_disable_admin_login');
		$this->config->set_option('internal_staging_enable_admin_login', $current_setting);
		$this->config->delete_option('internal_staging_disable_admin_login');
	}

	public function truncate_all_wpss_related_tables(){
		wpss_log('', '--------truncate_all_wpss_related_tables--------');

		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wpss_activity_log`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wpss_backups`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wpss_current_process`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wpss_options`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wpss_inc_exc_contents`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wpss_processed_iterator`");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wpss_processed_files`");
	}

	public function make_this_fresh_site(){
		$this->truncate_all_wpss_related_tables();

		$this->set_fresh_install_flags();
		$this->die_with_json_encode(array('status' => 'success'));
	}


	public function refresh_cached_paths(){
		$this->config->delete_option('backup_db_path');

		//Used for staging purpose
		$this->config->delete_option('site_abspath');

		$this->config->choose_db_backup_path();
	}

	public function set_fresh_install_flags(){
		$this->config->set_option('database_version', WPSS_DATABASE_VERSION);
		$this->config->set_option('wpss_version', WPSS_VERSION);
		$this->config->set_option('activity_log_lazy_load_limit', WPSS_ACTIVITY_LOG_LAZY_LOAD_LIMIT);
		$this->config->set_option('backup_before_update_setting', 'everytime');
		$this->config->set_option('revision_limit', WPSS_FALLBACK_REVISION_LIMIT_DAYS);
		$this->config->set_option('run_init_setup_bbu', true);
		WPSS_Base_Factory::get('WPSS_ExcludeOption')->insert_default_excluded_files();
		$this->set_user_to_access();
		$this->config->set_option('internal_staging_db_rows_copy_limit', WPSS_STAGING_DEFAULT_COPY_DB_ROWS_LIMIT);
		$this->config->set_option('internal_staging_file_copy_limit', WPSS_STAGING_DEFAULT_FILE_COPY_LIMIT);
		$this->config->set_option('dropbox_oauth_upgraded', true);
		$this->config->set_option('internal_staging_enable_admin_login', '');
		$this->config->set_option('backup_slot', 'daily');
		$this->config->set_option('user_excluded_extenstions', strtolower(
			'.zip, .mp4, .mp3, .avi, .mov, .mpg, .log, .DS_Store, .git, .gitignore, .gitmodules, .svn, .dropbox, .sass-cache, .wpress, .db, .tmp'
			)
		);
		$this->config->set_option('user_excluded_extenstions_staging', strtolower(
			'.zip, .mp4, .mp3, .avi, .mov, .mpg, .pdf, .log, .DS_Store, .git, .gitignore, .gitmodules, .svn, .dropbox, .sass-cache, .wpress, .db, .tmp'
			)
		);
		$this->config->set_option('user_excluded_files_more_than_size_settings', serialize(array('status' => 'yes', 'size' => 52428800) )); //50MB
		$this->config->set_option('update_prev_backups_1_14_10', true); //set it like it already done for new users
		$this->config->set_option('update_prev_backups_1_15_10', true); //set it like it already done for new users
		$this->config->set_option('update_prev_backups_1_18_0', true); //set it like it already done for new users
		$this->config->set_option('backup_db_query_limit', WPSS_DEFAULT_DB_ROWS_BACKUP_LIMIT);

		//Only for testing purpose
		if (WPSS_ENV !== 'production' && WPSS_BACKWARD_BACKUPS_CREATION) {
			$this->config->set_option('testing_current_date', WPSS_BACKWARD_BACKUPS_CREATION_DAYS);
		}
		// WPSS_Base_Factory::get('WPSS_App_Functions')->register_Must_Use();
	}


	public function is_backup_request_timeout($return = false, $print_time = false) {
		global $wpss_ajax_start_time;

		if ((time() - $wpss_ajax_start_time) >= WPSS_TIMEOUT) {

			if ($return) return true;

			WPSS_Factory::get('logger')->log(__("Preparing for next call from server.", 'wpss'), 'backups', wpss_get_cookie('backupID'));
			wpss_send_current_backup_response_to_server();
		}

		if ($print_time) {
			wpss_log(time() - $wpss_ajax_start_time, '------------I still have time--------------------');
		}

		return false;
	}

	public function can_show_this_page(){

		// include_once ( WPSS_PLUGIN_DIR . 'Views/wpss-options-helper.php' );
		// $options_helper = new WPSS_Options_Helper();

		// if( !$options_helper->get_is_user_logged_in() ||
		// 	$options_helper->is_show_privilege_box() ||
		// 	!WPSS_Factory::get('config')->get_option('wpss_server_connected') ||
		// 	!(WPSS_Factory::get('config')->get_option('privileges_wpss')) ){
		// 	wordpress_time_capsule_admin_menu_contents();
		// 	return false;
		// }

		return true;
	}

	public function is_cloud_authorized(){

		if (!defined('DEFAULT_REPO') ) {
			return false;
		}

		$a = DEFAULT_REPO;
		if (empty($a)) {
			return false;
		}

		$cloud = WPSS_Factory::get(DEFAULT_REPO);

		if ( empty($cloud) || !$cloud->is_authorized() ) {

			wpss_log('', "--------is_cloud_authorized--failed------");
			
			return false;
		}

		return true;
	}

	public function get_issue_data($id) {

		if (empty($id)) {
			return array();
		}

		$prepared_query = $this->wpdb->prepare('SELECT * FROM ' . $this->wpdb->base_prefix . 'wpss_activity_log WHERE id = %d', $id);

		$specficlog = $this->wpdb->get_row($prepared_query, OBJECT);

		if (!$specficlog) {
			return array();
		}

		if (empty($specficlog->action_id)) {
			return $specficlog->log_data;
		}

		$action_log = $this->wpdb->get_results('SELECT * FROM ' . $this->wpdb->base_prefix . 'wpss_activity_log WHERE action_id = ' . $specficlog->action_id, OBJECT);

		if (!count($action_log)) {
			return $specficlog->log_data;
		}

		foreach ($action_log as $all) {
			$report[] = $all->log_data;
		}

		return $report;
	}


	//Generate Random keys
	private function generate_random_string($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}


	public function truncate_activity_log(){
		if ($this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wpss_activity_log`")) {
			$this->die_with_json_encode(array('success' => true));
		}

		$this->die_with_json_encode(array('error' => true));
	}

	public function convert_mb_to_bytes($size){
		$size = trim($size);
		return $size * pow( 1024, 2 );
	}

	public function convert_bytes_to_mb($size){

		if (empty($size)) {
			return 0;
		}

		$size = trim($size);
		return ( ($size / 1024 ) / 1024 );
	}

	private function init_exclude_option(){
		if ( !empty($this->exclude_option) ) {
			return ;
		}

		$this->exclude_option = WPSS_Base_Factory::get('WPSS_ExcludeOption');
	}

	public function is_bigger_than_allowed_file_size($file){

		$this->init_exclude_option();

		$settings = $this->exclude_option->get_user_excluded_files_more_than_size();

		if ($settings['status'] === 'no') {
			return false;
		}

		if ( $this->exclude_option->is_included_file($file) ) {
			return false;
		}

		if (filesize($file) > $settings['size']) {
			return true;
		}

		return false;
	}

	public function get_meta_backup_tables($filter = false){

		$structure_tables = array(
			$this->wpdb->prefix . 'wpss_activity_log',
			$this->wpdb->prefix . 'wpss_current_process',
			$this->wpdb->prefix . 'wpss_processed_iterator',
			$this->wpdb->prefix . 'wpss_local_site_new_attachments',
		);


		$full_tables = array(
			$this->wpdb->prefix . 'wpss_backups',
			$this->wpdb->prefix . 'wpss_inc_exc_contents',
			$this->wpdb->prefix . 'wpss_options',
			$this->wpdb->prefix . 'wpss_processed_files',
		);

		switch ($filter) {
			case 'structure':
				return $structure_tables;
			case 'full':
				return $full_tables;
			default:
				return array_merge($structure_tables, $full_tables);
		}

	}


	public function is_meta_table_excluded($table){

		$structure_tables = $this->get_meta_backup_tables($filer = 'structure');

		if (in_array( $table, $structure_tables) ) {
			return 'content_excluded';
		}

		$full_tables = $this->get_meta_backup_tables($filer = 'full');

		if (in_array( $table , $full_tables ) ) {
			return 'table_included';
		}

		return 'table_excluded';
	}

	public function get_wp_core_version($hard_refresh = false ){

		if ($this->wp_version && !$hard_refresh) {
			return $this->wp_version;
		}

		@include( ABSPATH . WPINC . '/version.php' );
		$this->wp_version = $wp_version;

		return $this->wp_version;
	}

	public function update_prev_backups(){
		if(!$this->config->get_option('update_prev_backups_1_14_10')){
			include_once ( WPSS_PLUGIN_DIR . 'updates/update_1_14_10.php' );
			new WPSS_Update_1_14_10($this, $this->wpdb, $this->config);
		}

		if(!$this->config->get_option('update_prev_backups_1_15_10')){
			include_once ( WPSS_PLUGIN_DIR . 'updates/update_1_15_10.php' );
			new WPSS_Update_1_15_10($this, $this->wpdb, $this->config);
		}

		if(!$this->config->get_option('update_prev_backups_1_18_0')){
			include_once ( WPSS_PLUGIN_DIR . 'updates/update_1_18_0.php' );
			new WPSS_Update_1_18_0($this, $this->wpdb, $this->config);
		}
	}

	public function plugin_upgrades($version){
		include_once ( WPSS_PLUGIN_DIR . 'updates/class.upgrade-common.php' );
		new WPSS_Upgrade_Common($this, $this->wpdb, $this->config, $version);
	}

	public function plugin_upgrades_pro($version){
		include_once ( WPSS_PLUGIN_DIR . 'updates/class.upgrade-pro.php' );
		new WPSS_Upgrade_Pro($this, $this->wpdb, $this->config, $version);
	}

	public function make_folders_empty($dir, $delete_index_file = false ){
		$file_iterator = new WPSS_File_Iterator();

		$file_obj = $file_iterator->get_files_obj_by_path($dir, true);

		if (empty($file_obj)) {
			return ;
		}

		foreach ($file_obj as $file_meta) {

			$file = $file_meta->getPathname();

			$file = wp_normalize_path($file);

			if (wpss_is_dir($file)) {
				continue;
			}

			if (!$delete_index_file && basename($file) === 'index.php') {
				continue;
			}

			@unlink($file);
		}
	}

	public function force_start_or_restart_backup(){
		$this->config->set_option('run_vulns_checker', false);
		$this->config->set_option('is_upgrade_in_progress_wpss', false);

		if(is_any_ongoing_wpss_backup_process()){
			stop_fresh_backup_tc_callback_wpss(null, false);
			start_fresh_backup_tc_callback_wpss('manual', $args = null, $test_connection = true, $ajax_check = false);
			return ;
		}
		start_fresh_backup_tc_callback_wpss('manual', $args = null, $test_connection = true, $ajax_check = false);
	}

	public function get_backup_db_query_limit(){
		$get_backup_db_query_limit = $this->config->get_option('backup_db_query_limit');

		if(empty($get_backup_db_query_limit)){

			return WPSS_DEFAULT_DB_ROWS_BACKUP_LIMIT;
		}

		if($get_backup_db_query_limit < 5){
			$get_backup_db_query_limit = WPSS_DEFAULT_DB_ROWS_BACKUP_LIMIT;
		}

		return $get_backup_db_query_limit;
	}

	public function add_wpengine_cookie(&$request){

		if (!defined('WPE_APIKEY')) {
			return $request;
		}

		//This will be used to perform updates in WP Engine sites.
		$request['wpengine_cookie'] = md5('wpe_auth_salty_dog|' . WPE_APIKEY);
	}

	public function register_Must_Use(){
		try {
			$this->registerMustUse('0-mu-super-stage-wp.php', $this->buildLoaderContent('super-stage-wp/super-stage-wp.php'));
		} catch (Exception $e) {
			return array('Unable to remove loader' => array('exception' => $e));
		}
	}

	private function buildLoaderContent($pluginBasename) {
		$loader = <<<EOF
<?php

/*
Plugin Name: Super Stage WP
Plugin URI: https://wpsuperstage.com
Description: Super Stage WP is an incremental automated backup plugin that backups up your website to Dropbox, Google Drive and Amazon S3 on a daily basis.
Author: Revmakx
Author URI: http://www.revmakx.com
*/

if (!function_exists('untrailingslashit') || !defined('WP_PLUGIN_DIR')) {
	// WordPress is probably not bootstrapped.
	exit;
}

if (file_exists(untrailingslashit(WP_PLUGIN_DIR).'/$pluginBasename')) {
	if (in_array('$pluginBasename', (array) get_option('active_plugins')) ||
		(function_exists('get_site_option') && array_key_exists('super-stage-wp/super-stage-wp.php', (array) get_site_option('active_sitewide_plugins')))) {
		if(!empty(\$_GET) && !empty(\$_GET['action']) && strpos(\$_GET['action'], 'wpamelia_api') === false) {
			return;
		}
		\$GLOBALS['wpss_is_mu'] = true;
		include_once untrailingslashit(WP_PLUGIN_DIR).'/$pluginBasename';
	}
}

EOF;
		return $loader;
	}

	private function registerMustUse($loaderName, $loaderContent) {
		$mustUsePluginDir = rtrim(WPMU_PLUGIN_DIR, '/');
		$loaderPath       = $mustUsePluginDir.'/'.$loaderName;

		if (file_exists($loaderPath) && md5($loaderContent) === md5_file($loaderPath)) {
			return;
		}

		if (!is_dir($mustUsePluginDir)) {
			$dirMade = @mkdir($mustUsePluginDir);

			if (!$dirMade) {
				$error = error_get_last();
				return array('');
				throw new Exception(sprintf('Unable to create loader directory: %s', $error['message']));
			}
		}

		if (!is_writable($mustUsePluginDir)) {
			throw new Exception('MU-plugin directory is not writable.');
		}

		$loaderWritten = @file_put_contents($loaderPath, $loaderContent);

		if (!$loaderWritten) {
			$error = error_get_last();
			throw new Exception(sprintf('Unable to write loader: %s', $error['message']));
		}
	}

	public function table_exist($table){
		$small_letters_table = strtolower($table);

		if( $this->wpdb->get_var("SHOW TABLES LIKE '$small_letters_table'") == $small_letters_table ){
			return true;
		}

		if( $this->wpdb->get_var("SHOW TABLES LIKE '$table'") == $table ){
			return true;
		}

		wpss_log("SHOW TABLES LIKE '$table'", "--------table_exist_failed_for--------");

		return false;
	}
}
