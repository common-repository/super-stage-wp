<?php

class WPSS_Staging {

	const CLONE_TMP_FOLDER = 'wpss_staging_controller';

	protected $processed_files;
	protected $staging_id;
	protected $db_backup;
	protected $options;
	protected $logger;
	protected $config;
	protected $filesystem;
	protected $plugin_bridge_path;
	protected $same_staging_folder;
	protected $same_staging_bridge_dir;
	protected $processed_db;
	protected $exclude_class_obj;
	protected $staging_common;
	protected $file_iterator;
	protected $seek_file_iterator;
	protected $app_functions;
	protected $is_force_update;
	protected $replace_links_obj;

	public function __construct($type = false){
		$this->options = WPSS_Factory::get('config');
		$this->db_backup = WPSS_Factory::get('databaseBackup');
		$this->processed_files = WPSS_Factory::get('processed-files');
		$this->exclude_class_obj = new WPSS_ExcludeOption($category = 'staging');
		$this->app_functions = WPSS_Base_Factory::get('WPSS_App_Functions');
		$this->processed_db = new WPSS_Processed_iterator();
		$this->staging_common = new WPSS_Stage_Common();
		$this->file_iterator = new WPSS_File_Iterator();

		$this->init_db();
		$this->logger = WPSS_Factory::get('logger');
		$this->config = WPSS_Factory::get('config');

		$this->plugin_bridge_path = dirname(__FILE__) . '/' . 'bridge'.'/';

		$this->init_staging_id();
		$this->run_updates();
		$this->init_replace_db_link_obj();
		$this->do_iterator_req_process($type);
	}

	public function init() {
		$supposed_hooks_class = get_class($this) . '_Hooks';
		WPSS_Base_Factory::get($supposed_hooks_class)->register_hooks();
	}

	private function init_replace_db_link_obj(){
		include_once ( WPSS_CLASSES_DIR . 'class-replace-db-links.php' );
		$this->replace_links_obj = new WPSS_Replace_DB_Links();
	}

	private function init_seek_iterator(){
		$timeout_limit = $this->options->get_option('internal_staging_file_copy_limit');
		$timeout_limit = empty($timeout_limit) ? WPSS_STAGING_DEFAULT_FILE_COPY_LIMIT : $timeout_limit;
		$this->seek_file_iterator = new WPSS_Seek_Iterator($this, $type = 'LIVE_TO_STAGING', $timeout_limit, $category = 'staging');
	}

	private function is_switch_on(){
		return true;
	}

	private function do_iterator_req_process($type){
		if ($type !== 'iterator') {
			return ;
		}

		$this->init_fs();
		$this->same_server_set_staging_path();
	}

	private function init_fs(){
		$this->filesystem = $this->staging_common->init_fs();
	}

	private function init_db(){
		$this->wpdb = $this->staging_common->init_db();
	}

	private function init_staging_id(){
		$this->staging_id = $this->staging_common->init_staging_id();
	}

	//Process staging feature updates for existing users.
	public function run_updates(){
		$updates = $this->config->get_option('run_staging_updates');
		if (empty($updates)) {
			return false;
		}

		if (version_compare('1.9.0', $updates) >= 0) {
			$prefix = $this->get_existing_staging_prefix();
			if ($prefix !== false && $prefix !== $this->wpdb->base_prefix) {
				$this->replace_links_obj->discourage_search_engine($prefix, $reset_permalink = true);
			}
		}

		$this->config->set_option('run_staging_updates', false);
	}

	public function get_staging_details($param = null){
		$serialized_details = $this->options->get_option('same_server_staging_details');
		if (empty($serialized_details)) {
			return false;
		}

		$unserialized_details = unserialize($serialized_details);
		if (empty($unserialized_details)) {
			return false;
		}
		if (empty($param)) {
			return $unserialized_details;
		}
		return $unserialized_details[$param];
	}

	public function is_any_staging_process_going_on(){
		if (empty($this->options)) {
			$this->options = WPSS_Base_Factory::get('WPSS_staging_Config');
		}
		if ($this->options->get_option('is_staging_running', true) || $this->options->get_option('same_server_staging_running', true) ) {
			return true;
		}
		return false;
	}

	private function internal_staging_delete_bridge(){
		wpss_log('Function :','---------'.__FUNCTION__.'-----------------');
		wpss_log($this->same_staging_bridge_dir, '------$this->same_staging_bridge_dir---------------');
		return $this->filesystem->delete($this->same_staging_bridge_dir, true);
	}

	public function delete_staging_wpss(){
		$this->init_fs();
		$this->delete_internal_staging();
	}

	public function stop_staging_wpss(){
		wpss_log(func_get_args(), "--------" . __FUNCTION__ . "--------");
		$this->same_server_update_keys('same_server_staging_status', 'stop_staging');
	}

	private function delete_internal_staging($dont_print = false){
		
		wpss_log(func_get_args(), "--------" . __FUNCTION__ . "--------");

		$db = $this->delete_internal_staging_db();
		$files = $this->delete_internal_staging_files();
		$flags = $this->delete_internal_staging_flags();
		wpss_log($db, '---------$db------------');
		wpss_log($files, '---------$files------------');
		if ($dont_print) {
			return false;
		}
		if ($db && $files) {
			$this->app_functions->die_with_json_encode( array('status' => 'success', 'deleted' => 'both') );
		} else if ($db) {
			$this->app_functions->die_with_json_encode( array('status' => 'success', 'deleted' => 'db') );
		} else if($files){
			$this->app_functions->die_with_json_encode( array('status' => 'success', 'deleted' => 'files') );
		} else {
			$this->app_functions->die_with_json_encode( array('status' => 'error', 'deleted' => 'none') );
		}
	}

	public function delete_internal_staging_db($db_prefix = false){
		$db_prefix = $this->get_staging_details('db_prefix');
		wpss_log($db_prefix, '--------$db_prefix 1--------');

		if (empty($db_prefix)) {
			$db_prefix =  $this->options->get_option('same_server_staging_db_prefix');
		}

		wpss_log($db_prefix, '---------------$db_prefix-----------------');

		if (empty($db_prefix)) {
			return false;
		}

		if ($db_prefix == $this->wpdb->base_prefix) {
			return false;
		}

		return $this->processed_files->drop_tables_with_prefix($db_prefix);
	}

	public function delete_internal_staging_files($hard_delete = false){

		$staging_dir = $this->get_staging_dir();

		if (empty($staging_dir)) {
			return false;
		}

		return $this->filesystem->delete($staging_dir, true);
	}

	public function process_staging_details_hook($request = array()){
		wpss_log(func_get_args(), "--------" . __FUNCTION__ . "--------");
		if (empty($request)) {
			return false;
		}

		switch ( $request['type'] ) {
			case 'get':
				return $this->get_staging_details($request['key']);
			case 'replace':
				$this->same_staging_folder = $this->get_staging_dir();
				return $this->same_server_replace_pathname($request['key']);
			case 'get_dir':
				return $this->get_staging_dir();
			case 'get_homeurl':
				return $this->get_staging_dir();
		}

		return false;
	}

	private function get_staging_dir(){
		$staging_folder = $this->get_staging_details('staging_folder');
		if (empty($staging_folder)) {
			$staging_folder = $this->options->get_option('same_server_staging_path');
		}
		wpss_log($staging_folder, '---------------$staging_folder global-----------------');

		if (empty($staging_folder)) {
			return false;
		}

		$staging_path = get_home_path_wpss() . $staging_folder . '/';
		$staging_path = wp_normalize_path($staging_path);

		//check its live site before deleting it
		if (wpss_is_abspath($staging_path)) {
			return false;
		}


		wpss_log($staging_path, '---------$staging_path------------');

		return $staging_path;
	}

	private function delete_internal_staging_flags(){
		$this->options->set_option('staging_type', false);
		$this->options->set_option('same_server_staging_details', false);
		$this->options->set_option('same_server_staging_status', false);
		$this->options->set_option('is_staging_completed', false);
		$this->options->set_option('staging_id', false);
		$this->config->set_option('staging_tables_clone_new_status', false);
		$this->config->set_option('staging_tables_replace_link_status', false);
		$this->config->set_option('replace_links_row_count_staging', false);
		$this->hard_reset_staging_flags();
		$this->options->staging_flush();
	}

	private function hard_reset_staging_flags(){
		$this->options->set_option('staging_type', false);
		$this->options->set_option('is_staging_completed', false);
		$this->options->set_option('staging_completed', false);
		$this->options->set_option('staging_id', false);
		$this->complete();//truncate tables
	}

	public function get_staging_url_wpss(){
		wpss_log(array(), '-----------get_staging_url_wpss----------');
		$destination_url = $this->same_server_staging_url();
		$this->app_functions->die_with_json_encode( array('destination_url' => $destination_url), 1 );
	}

	public function test_connection($path = false) {
		wpss_log(func_get_args(), "--------" . __FUNCTION__ . "--------");
		$this->options->set_option('staging_type', 'internal');
		$this->options->staging_flush();
		$this->processed_db->truncate();
		$this->init_staging_id();
		$this->options->set_option('same_server_copy_staging', true);
		$this->same_server_copy_bridge_files($path);
	}

	public function get_staging_current_status_key(){
		$current_status = $this->options->get_option('same_server_staging_status', true);
		$know_more_link = '<a href="http://docs.wpsuperstage.com/article/35-how-to-fix-internal-staging-failures" target="_blank">How to fix</a>';
		switch ($current_status) {
			case 'test_bridge_over':
			case 'db_clone_progress':
				$msg = 'Failed to clone database. ' . $know_more_link;
				break;
			case 'copy_files_progress':
			case 'db_clone_over':
				$msg = 'Failed to copy files. ' . $know_more_link;
				break;
			case 'copy_files_over':
				$msg = 'Failed to replace links. ' . $know_more_link;
				break;
			default:
				$msg = 'Unknown error, email us at <a href="mailto:help@wpsuperstage.com?Subject=Contact" target="_top">help@wpsuperstage.com</a> ';
				break;
		}

		$this->app_functions->die_with_json_encode( array( 'msg' => $msg ) );
	}

	public function choose_action($path = false, $reqeust_type = false){

		wpss_log(func_get_args(), "--------" . __FUNCTION__ . "--------");

		$this->options->set_option('last_staging_ping', time());

		$this->app_functions->set_start_time();

		$this->init_fs();

		$this->same_server_set_staging_path();

		$status = $this->options->get_option('same_server_staging_status', true);

		if (empty($status) || $reqeust_type === 'copy' || $reqeust_type === 'fresh') {
			$status = 'test_connection';
		}

		wpss_log($status, '--------$current_action--------');

		if ($status === 'test_connection') {
			wpss_manual_debug('', 'start_test_connection_staging');
			$this->config->set_option('same_server_send_completion_email', true);
			$this->config->set_option('staging_tables_clone_new_status', false);
			$this->config->set_option('staging_tables_replace_link_status', false);
			$this->config->set_option('replace_links_row_count_staging', false);
			$this->test_connection($path);
		}

		if ($status === 'stop_staging') {
			wpss_manual_debug('', 'start_stop_staging_request');
			$this->process_stop_staging_req();
			$this->app_functions->die_with_json_encode( array('status' => 'success', 'msg' => 'Stopping Staging process.', 'percentage' => 0) );
		}

		if ($status === 'test_bridge_over' || $status === 'db_clone_progress') {
			wpss_manual_debug('', 'start_process_db_clone_staging');
			$this->process_db_clone_req($status);
			$this->app_functions->die_with_json_encode( array('status' => 'continue', 'msg' => 'Database is cloned.', 'percentage' => 30) );
		}

		if($status === 'db_clone_over' || $status === 'copy_files_progress'){
			wpss_manual_debug('', 'start_copy_files_staging');
			$this->process_copy_files_req($status);
			$this->app_functions->die_with_json_encode( array('status' => 'continue', 'msg' => 'Files are copied successfully' , 'percentage' => 75) );
		}

		if($status === 'copy_files_over'){
			wpss_manual_debug('', 'start_replace_links_staging');
			$this->process_replace_links_req($status);
			$this->app_functions->die_with_json_encode( array('status' => 'continue', 'msg' => 'Links are replaced.', 'percentage' => 90) );
		}

		if($status === 'replace_links_over'){
			wpss_manual_debug('', 'start_completing_staging');
			$this->process_staging_completed_req();
			$this->app_functions->die_with_json_encode( array('status' => 'continue', 'msg' => 'Resetting flags...', 'percentage' => 95) );
		}

		if ($status === 'staging_completed') {
			$this->staging_completed_email($type = 'internal');
			$this->things_to_do_after_completion();
			wpss_manual_debug('', 'staging_completed');
			$this->options->staging_flush();
			$response = array(
				'status'                => 'success',
				'msg'                   => 'Staging is completed.',
				'percentage'            => 100,
				'is_restore_to_staging' => apply_filters('is_restore_to_staging_wpss', '')
			);

			$this->app_functions->die_with_json_encode( $response );
		}

		$this->app_functions->die_with_json_encode( array('status' => 'error', 'msg' => 'Cannot fetch the current status. Please try again', 'percentage' => 100) );
	}

	private function process_stop_staging_req(){
		$this->delete_internal_staging($dont_print = true);
		wpss_manual_debug('', 'end_stop_staging_request');
	}

	private function process_db_clone_req($status){
		if ($status === 'test_bridge_over') {
			$this->logger->log("Starting to clone tables.", 'staging', $this->staging_id);
			$this->do_copy_staging_actions();
		}

		$staging_path = $this->options->get_option('same_server_staging_path');

		if (!empty($staging_path)) {
			$this->same_server_copy_bridge_files($staging_path, true);
		}

		$this->same_server_update_keys('same_server_staging_status', 'db_clone_progress');
		$this->same_server_clone_db();
		$this->same_server_update_keys('same_server_staging_status', 'db_clone_over');
		$this->logger->log("DB has been cloned successfully", 'staging', $this->staging_id);

		wpss_manual_debug('', 'end_process_db_clone_staging');
	}

	private function process_copy_files_req($status){
		if ($status === 'db_clone_over') {
			$this->logger->log("Copying Files is started.", 'staging', $this->staging_id);
		}

		$this->same_server_update_keys('same_server_staging_status', 'copy_files_progress');
		$iter_count = $this->same_server_copy();

		$this->same_server_update_keys('same_server_staging_status', 'copy_files_over');

		$this->logger->log("All files are copied to staging location succesfully.", 'staging', $this->staging_id);

		wpss_manual_debug('', 'end_copy_files_staging');
	}

	private function process_replace_links_req($status){
		$this->same_server_replace_links();
		$this->same_server_update_keys('same_server_staging_status', 'replace_links_over');
		$this->logger->log("Replaced links in the staging site successfully", 'staging', $this->staging_id);
	}

	private function process_staging_completed_req(){
		$this->internal_staging_delete_bridge();
		$this->complete();
		$this->processed_db->truncate();

		$this->logger->log("Site staged succesfully.", 'staging', $this->staging_id);


		$this->same_server_update_keys('same_server_staging_status', 'staging_completed');
		$staging_path = $this->options->get_option('same_server_staging_path');

		if (empty($staging_path)) {
			return false;
		}

		$db_prefix = $this->get_staging_db_prefix('full_prefix');

		$this->options->set_option('same_server_staging_details',
						serialize(
							array(
								'destination_url' => wpss_get_live_url().'/'.$staging_path.'/',
								'wpss_staging_plugin_url' => wpss_get_live_url().'/'.$staging_path.'/wp-admin/admin.php?page=super-stage-wp-staging&wpss_s2l_start=yes',
								'human_completed_time' => user_formatted_time_wpss(time()),
								'timestamp' => time(),
								'db_prefix' => $db_prefix,
								'staging_folder' => $staging_path
								)
							)
						);

		$this->options->staging_flush();

		wpss_manual_debug('', 'end_completing_staging');
	}

	private function staging_completed_email($type = false){
		if(!$this->config->get_option('same_server_send_completion_email')){
			return ;
		}

		$staging_url = $this->get_staging_details('destination_url');

		if (empty($staging_url)) {
			return ;
		}

		$email_data = array(
			'type' => 'staging_completed',
			'staging_url' => $staging_url,
		);

		$this->config->set_option('same_server_send_completion_email', false);

		error_alert_wpss_server($email_data);
	}

	private function do_copy_staging_actions(){
		wpss_log(func_get_args(), "--------" . __FUNCTION__ . "--------");
		if(!$this->options->get_option('same_server_copy_staging')){
			wpss_log(array(), '--------Get out--------');
			return false;
		}
		$db = $this->delete_internal_staging_db();
		wpss_log($db, '--------$db--------');
		$files = $this->delete_internal_staging_files();
		wpss_log($files, '--------$files--------');
		$this->options->set_option('same_server_staging_details', false);
	}

	private function same_server_update_keys($key, $value){
		wpss_log(func_get_args(), "--------" . __FUNCTION__ . "--------");

		if ($key === 'same_server_staging_status') {
			$current_value = $this->config->get_option('same_server_staging_status', true);
			wpss_log($current_value, '--------$current_value--------');
			if ($current_value == "stop_staging") {
				wpss_log(array(), '--------RETURNN--------');
				return false;
			}
		}

		$this->config->set_option($key, $value);
		$updated_result = $this->config->get_option($key, true);
		wpss_log($updated_result, '--------$updated_result--------');
	}

	private function is_same_server_running(){
		return $this->options->get_option('same_server_staging_running', true);
	}

	private function rewrite_permalink_structure(){

		$new_prefix = $this->get_staging_db_prefix('full_prefix');

		if (empty($new_prefix) || $new_prefix == $this->wpdb->base_prefix) {
			return false;
		}

		$result = $this->wpdb->query(
			"UPDATE " . $new_prefix . "options SET option_value=''  WHERE option_name = 'permalink_structure'"
		);

		if ($result === false) {
			return $this->logger->log("rewrite permalink structure failed.", 'staging', $this->staging_id);
		} else {
			return $this->logger->log("rewrite permalink structure successfully done.", 'staging', $this->staging_id);
		}

	}

	private function same_server_copy_bridge_files($path = false, $do_not_die = false){
		if ($path === false) {
			$path = $this->get_staging_details('staging_folder');
		}
		wpss_log($path, '---------$path same_server_copy_bridge_files------------');
		$path = wp_normalize_path($path);
		$this->same_server_set_staging_path($path);

		if (!$this->is_force_update) {
			$this->same_server_update_keys('same_server_staging_status', 'test_bridge');
		}

		$this->app_functions->mkdir_by_path($this->same_staging_bridge_dir);

		if (!$this->is_force_update) {
			wpss_manual_debug('', 'end_test_connection_staging');
		}

		if ($this->filesystem->exists($this->same_staging_bridge_dir) !== true && $do_not_die === false) {
			$this->same_server_update_keys('same_server_staging_status', 'test_bridge_folder_create_failed');
			$this->logger->log("Testing connection by uploading bridge file is failed", 'staging', $this->staging_id);
			$this->app_functions->die_with_json_encode( array('status' => 'error' , 'msg' => 'Cannot create staging folder.') );
		}

		$cp_result = $this->filesystem->copy($this->plugin_bridge_path . 'bridge.php', $this->same_staging_bridge_dir . 'bridge.php', true, FS_CHMOD_FILE);

		if(!$cp_result && $do_not_die === false){
			$this->same_server_update_keys('same_server_staging_status', 'test_bridge_file_copy_failed');
			$this->logger->log("Testing connection by uploading bridge file is failed", 'staging', $this->staging_id);
			$this->app_functions->die_with_json_encode( array('status' => 'error' , 'msg' => 'Cannot create copy bridge file.') );
		}

		if (!$this->is_force_update) {
			$this->same_server_update_keys('same_server_staging_status', 'test_bridge_over');
			$this->options->set_option('same_server_staging_path', $path);
			$this->options->set_option('same_server_staging_running', true);
		}

		if (!$this->is_force_update && $do_not_die === false) {
			$this->logger->log("Tested connection succesfully.", 'staging', $this->staging_id);
			if (apply_filters('is_restore_to_staging_wpss', '')) {
				$last_staging_ping = $this->options->get_option('last_staging_ping');
				$this->options->set_option('last_staging_ping', ( $last_staging_ping - ( WPSS_TIMEOUT * 3 ) ) );
			}
			$this->app_functions->die_with_json_encode( array('status' => 'continue', 'msg' => 'Test bridge copied.', 'percentage' => 10) );
		}
	}

	private function skip_file($file){
		if(!is_readable($file)){
			return true;
		}

		if (stripos($file . '/' , $this->same_staging_folder) !== false || is_wpss_file($file)) {
			return true;
		}

		if ($this->exclude_class_obj->is_excluded_file($file)) {
			return true;
		}

		$is_image_file = $this->exclude_class_obj->is_upload_dir_media_file_on_prod_site($file);

		if($is_image_file){

			return true;
		}
		return false;
	}

	private function same_server_copy(){
		$this->get_folders();
		$this->copy_selected_folders();
	}

	private function get_folders(){
		$get_folders = $this->config->get_option('same_server_get_folders');

		if (!empty($get_folders)) {
			return false;
		}

		$this->file_iterator->get_folders();
		$this->config->set_option('same_server_get_folders', true);
	}

	private function copy_selected_folders(){

		$break = false;

		while(!$break){
			$dir_meta = $this->processed_db->get_unfnished_folder();

			wpss_manual_debug('', 'during_process_folders_staging', 50);

			wpss_log($dir_meta, '--------$dir_meta--------');

			$deep_dirs = false;

			if (empty($dir_meta) || $dir_meta->offset === -1) {
				$break = true;
				wpss_log(array(), '--------SKIP META 1--------');
				continue;
			}

			if( array_search($dir_meta->name, $this->file_iterator->get_deep_dirs()) !== false ){
				$deep_dirs = true;
			}

			$file = wpss_add_fullpath($dir_meta->name);

			if ($deep_dirs === false && $this->skip_file($file) === true) {
				wpss_log(array(), '--------SKIP FILE OR FOLDER 2--------');
				$this->processed_db->update_iterator($dir_meta->name, -1);
				//exit;
				continue;
			}

			if(wpss_is_dir($file)){
				wpss_log(array(), '--------COPY DIR--------');
				$this->copy_dir($dir_meta->name, $dir_meta->offset, $deep_dirs);
			} else {
				wpss_log(array(), '--------COPY FILE--------');
				$this->copy_file($dir_meta->name, $update_status = true);
			}
		}
	}


	public function process_file($iterator, $is_recursive, $path, $key, &$counter, $iterator_loop_limit){
		wpss_manual_debug('', 'during_process_files_staging', 50);

		$file = $iterator->getPathname();

		if (!$iterator->isReadable()) {
			return ;
		}

		$file = wp_normalize_path($file);

		if (!$is_recursive && wpss_is_dir($file)){
			wpss_log($file, '--------skip because of deep dir--------');
			return;
		}

		// wpss_log($file, '--------$file--------');

		if ($this->skip_file($file) === true) {
			wpss_log(array(), '--------SKIP FILE FOLDERS 3--------');
			$this->check_timeout_iter_file($path,  $key, $counter, $iterator_loop_limit);
			return;
		}

		if(wpss_is_dir($file)){
			return;
		}

		$this->copy_file($file);

		$this->check_timeout_iter_file($path,  $key, $counter, $iterator_loop_limit);
	}

	private function copy_file($live_file, $update_status = false){
		$this->replace_links_obj->make_cpu_idle();

		wpss_manual_debug('', 'during_copy_files_staging', 50);

		// wpss_log(func_get_args(), "--------" . __FUNCTION__ . "--------");

		$live_file = wpss_add_fullpath($live_file);

		$live_file = wp_normalize_path($live_file);


		$staging_file = $this->same_server_replace_pathname($live_file);

		// wpss_log($staging_file, '---------------$staging_file-----------------');

		$this->app_functions->mkdir_by_path(dirname($staging_file));

		$size = filesize($live_file);

		if ($size > WPSS_STAGING_COPY_SIZE) {
			wpss_log(array(), '--------large file--------');
			$copy_status = wpss_copy_large_file($live_file, $staging_file);
		} else {
			$copy_status = $this->filesystem->copy($live_file, $staging_file, true, FS_CHMOD_FILE);
		}

		// wpss_log($copy_status, '--------Copy status--------');

		if (!$copy_status) {
			$this->logger->log('Could not copy this file - '.$live_file, 'staging', $this->staging_id);
			wpss_log(error_get_last(),'-----------error_get_last()----------------');
		}

		if ($update_status) {
			$this->processed_db->update_iterator($live_file, -1);
		}
	}

	private function copy_dir($live_path, $offset, $deep_dirs){

		$this->init_seek_iterator();

		$is_recursive = ($deep_dirs) ? false : true;

		try{
			$this->seek_file_iterator->process_iterator($live_path, $offset, $is_recursive);
		} catch(Exception $e){

			$exception_msg = $e->getMessage();
			wpss_log($exception_msg, '---------------Exception-----------------');

			if (wpss_is_file_iterator_allowed_exception($exception_msg)) {
				$this->logger->log($exception_msg , 'staging', $this->staging_id);
				$this->logger->log($live_path . ' This folder has been excluded since it is not readable.', 'staging', $this->staging_id);
				$this->exclude_class_obj->exclude_file_list(array('file' => $live_path, 'isdir' => true, 'category' => 'staging') , true);
				$this->app_functions->die_with_json_encode( array('status' => 'continue', 'msg' => 'Seeking failed, Skipped folder and Retrying...') );
			}

			if (!wpss_is_seeking_exception($exception_msg)) {
				$this->app_functions->die_with_json_encode( array('status' => 'error' , 'msg' => $exception_msg) );
			}

			wpss_log($live_path, '---------------Retry Seeking-----------------');
			$this->processed_db->update_iterator($live_path, 0);
			$this->app_functions->die_with_json_encode( array('status' => 'continue', 'msg' => 'Seeking failed, Retrying...') );
		}
		$this->processed_db->update_iterator($live_path, -1);
	}

	private function check_timeout_iter_file($path, $offset, &$temp_counter, $timeout_limit){
		$is_break = is_wpss_timeout_cut(false, 5);

		if (++$temp_counter < $timeout_limit) {
			if(!$is_break){
				return false;
			}
		}
		
		if(!$is_break){
			return false;
		}

		$this->processed_db->update_iterator($path, $offset);

		$this->app_functions->die_with_json_encode(array('status' => 'continue', 'msg' => $this->app_functions->get_processing_files_count($type = 'internal_staging'), 'percentage' => 50));
	}

	private function complete(){
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wpss_current_process`;");
		$this->wpdb->query("TRUNCATE TABLE `" . $this->wpdb->base_prefix . "wpss_processed_iterator`;");
	}

	public function is_staging_need_request(){

		if(!$this->is_any_staging_process_going_on()){
			$this->app_functions->die_with_json_encode( array('status' => false, 'check_again' => false) );
		}

		$last_staging_ping = $this->options->get_option('last_staging_ping');

		wpss_log($last_staging_ping,'-----------$last_staging_ping----------------');

		wpss_log($last_staging_ping - time(),'-----------$last_staging_ping - time()----------------');

		if (empty($last_staging_ping)) {
			$this->app_functions->die_with_json_encode( array('status' => false, 'check_again' => false) );
		}

		if ($last_staging_ping + ( WPSS_TIMEOUT * 2 )  <  time() )  {
			$this->app_functions->die_with_json_encode( array('status' => true, 'check_again' => false) );
		}

		$this->app_functions->die_with_json_encode( array('status' => false, 'check_again' => true) );
	}

	private function same_server_add_completed_table(){
		$completed_tables = $this->options->get_option('same_server_clone_db_completed_tables');
		if (empty($completed_tables)) {
			return $this->options->set_option('same_server_clone_db_completed_tables', 1);
		}
		return $this->options->set_option('same_server_clone_db_completed_tables', $completed_tables + 1);
	}

	private function same_server_clone_db() {
		$limit = $this->options->get_option('internal_staging_db_rows_copy_limit');

		if (empty($limit)) {
			$limit = WPSS_STAGING_DEFAULT_COPY_DB_ROWS_LIMIT; //fallback to default value
		}

		$wp_tables = $this->processed_files->get_all_tables();

		$this->options->set_option('same_server_clone_db_total_tables', count($wp_tables));
		wpss_log($wp_tables, '---------$wp_tables------------');
		foreach ($wp_tables as $table) {
			wpss_manual_debug('', 'start_clone_table_staging_' .$table);

			if (!$this->is_same_server_running()) {
				$this->app_functions->die_with_json_encode( array('status' => 'success', 'msg' => 'Staging process stopped.', 'percentage' => 0) );
			}
			wpss_log($table, '---------$table------------');

			$unique_prefix = $this->get_staging_db_prefix();
			$new_table =  $unique_prefix . '_' . $table;

			wpss_log($new_table, '---------$new_table------------');
			$unique_prefix = (string) $unique_prefix;

			$table_skip_status = $this->exclude_class_obj->is_excluded_table($table);

			if ($table_skip_status === 'table_excluded' || stripos($table, $unique_prefix) !== false) {
				wpss_log($table, '---------------$table excluded from staging-----------------');
				$this->same_server_add_completed_table();
				continue;
			}

			wpss_log($new_table, '---------$new_table------------');

			if( $table_skip_status === 'content_excluded'){
				$this->staging_common->clone_table_structure($table, $new_table);
				$this->processed_db->update_iterator($table, -1); //Done
				$this->same_server_add_completed_table();
				continue;
			}

			if (!$this->processed_db->is_complete($table)) {
				if (is_wpss_table($table)) {
					$this->processed_db->update_iterator($table, -1); //Done
					wpss_log(array(), '---------Done already so skipping------------');
					$this->same_server_add_completed_table();
					continue;
				}

				$table_meta = $this->staging_common->get_table_data($table);
				extract($table_meta);
			} else {
				wpss_log(array(), '--------skiipped---------');
				$this->same_server_add_completed_table();
				continue;
			}
			if ($is_new) {
				$result = $this->staging_common->clone_table_structure($table, $new_table);

				if ($result === false) {
					$this->processed_db->update_iterator($table, -1); //Done
					$this->same_server_add_completed_table();
					continue;
				}
			}
			$this->staging_common->clone_table_content($table, $new_table, $limit, $offset);
			$this->same_server_add_completed_table();
		}
	}

	public function same_server_replace_links() {

		$same_server_replace_old_url = $this->options->get_option('same_server_replace_old_url');
		wpss_log($same_server_replace_old_url , '-------------$same_server_replace_old_url -------------------');

		if(empty($same_server_replace_old_url)){

			wpss_manual_debug('', 'start_replace_old_url_staging');

			$this->replace_old_url();

			wpss_manual_debug('', 'end_replace_old_url_staging');
			
		}

		wpss_manual_debug('', 'httacc_created_staging');

		$new_prefix = $this->get_staging_db_prefix('full_prefix');

		if (empty($new_prefix) || $new_prefix == $this->wpdb->base_prefix) {
			return false;
		}

		$this->disable_search_engine_indexing($new_prefix);

		wpss_manual_debug('', 'disabled_search_engine_staging');

		$this->replace_links_obj->update_site_and_home_url($new_prefix, $this->same_server_staging_url());

		$this->replace_links_obj->rewrite_rules($new_prefix);

		$this->replace_links_obj->update_user_roles($new_prefix, $this->wpdb->base_prefix);

		$this->logger->log('User roles has been modified succesfully', 'staging', $this->staging_id);


		wpss_manual_debug('', 'update_user_roles_staging');

		//Deactivate Super Stage WP on staging site
		$this->app_functions->run_deactivate_plugin('super-stage-wp/super-stage-wp.php', $new_prefix);

		//Activate Super Stage WP Staging plugin on staging site
		$this->app_functions->run_activate_plugin('super-stage-wp-staging/super-stage-wp-staging.php', $new_prefix);

		wpss_manual_debug('', 'activated_plugin_staging');

		//Replace new prefix
		$this->replace_links_obj->replace_prefix($new_prefix, $this->wpdb->base_prefix);

		$this->logger->log('Updating db prefix "' . $this->wpdb->base_prefix . '" to  "' . $new_prefix . '" has been done succesfully.', 'staging', $this->staging_id);

		$new_site_url = $this->same_server_staging_url();

		//multisite changes
		if (is_multisite()) {
			$this->replace_links_obj->multi_site_db_changes($new_prefix, $new_site_url, get_home_url());
		}


		//replace $table_prefix in wp-config.php
		$this->modify_wp_config($new_site_url, $new_prefix);

		// Replace path in index.php
		$this->same_server_reset_index_php();

		$this->staging_to_live_compatible();

		$this->on_staging_complete_apply_fixes($new_prefix, $this->wpdb->base_prefix);

		$this->refresh_elementor_css();

		wpss_manual_debug('', 'staging_to_live_compatible_staging');

		wpss_log(array(), '---------COMPLETED------------');
	}

	private function on_staging_complete_apply_fixes($new_prefix, $old_prefix){
		global $wpdb;
		
		if($old_prefix == 'db') {
			$new_table = $new_prefix . 'options';
			$staging_option_name = $new_prefix . '_version';
			$new_query = "UPDATE `$new_table` SET option_name='db_version' WHERE option_name='$staging_option_name' ";
			$wpdb->query($new_query);

			wpss_log('----on_staging_complete_apply_fixes-----new_query--', $new_query);
		}
	}

	public function refresh_elementor_css(){
		$sql_result = $this->wpdb->query("DELETE FROM `{$this->wpdb->base_prefix}postmeta` WHERE `meta_key`='_elementor_css'");
		if($sql_result === false){
			wpss_log($this->wpdb->last_error, "--------refresh_elementor_css-query failed-----");
		}
	}

	private function modify_wp_config($new_site_url, $new_prefix){
		if ($this->wpdb->base_prefix == $new_prefix) {
			wpss_log(array(), '--------Do not modify processing site site--------');
			return false;
		}

		if (wpss_is_abspath($this->same_staging_folder)) {
			wpss_log(array(), '--------Do not modify wp-config live site--------');
			return ;
		}

		$this->replace_links_obj->modify_wp_config(
			array(
			'old_url' =>  site_url(),
			'new_url' =>  $new_site_url,
			'new_path' => $this->same_staging_folder,
			'old_path' =>  WPSS_ABSPATH,
			'new_prefix' =>  $new_prefix,
			), 'LIVE_TO_STAGING'
		);

		if (!$this->is_replace_permalink()) {
			wpss_log(array(),'-----------do not replace----------------');
			$this->replace_links_obj->replace_htaccess( array(
				'new_url' => $new_site_url,
				'old_url' =>  site_url(),
				'old_path' =>  WPSS_ABSPATH,
				'new_path' => $this->same_staging_folder,
			)
		);
		} else {
			wpss_log(array(),'-----------Replace----------------');
			$this->rewrite_permalink_structure();
			$this->create_default_htaccess();
		}
	}

	private function is_replace_permalink(){
		return $this->config->get_option('staging_is_reset_permalink');
	}

	private function disable_search_engine_indexing($new_prefix){
		//discourage indexing
		// $this->create_robot_txt();
		$this->replace_links_obj->discourage_search_engine($new_prefix, $reset_permalink = false);
		$this->logger->log('Enabled discouraging search engines from indexing', 'staging', $this->staging_id);
	}

	private function replace_old_url(){
		$this->same_server_set_staging_path();
		$raw_result = $this->options->get_option('same_server_replace_old_url_data');
		$tables = false;
		if (!empty($raw_result)) {
			$tables = @unserialize($raw_result);
		}

		$old_url       = site_url();
		$new_url       = $this->same_server_staging_url();
		$old_file_path = WPSS_ABSPATH;
		$new_file_path = wp_normalize_path($this->same_staging_folder);

		wpss_log($old_url, '---------------$old_url-----------------');
		wpss_log($new_url, '---------------$new_url-----------------');
		wpss_log($old_file_path, '---------------$old_file_path-----------------');
		wpss_log($new_file_path, '---------------$new_file_path-----------------');

		$table_prefix = $this->get_staging_db_prefix('full_prefix');

		$this->replace_links_obj->replace_uri($old_url, $new_url, $old_file_path, $new_file_path, $table_prefix, $tables, '', 'staging');
	}

	private function create_default_htaccess(){

		$this->logger->log('.htaccess has been modified.', 'staging', $this->staging_id);

		if (is_multisite()) {
			return $this->multi_site_default_htaccess();
		}

		return $this->normal_site_default_htaccess();
	}

	private function multi_site_default_htaccess(){
		if (wpss_is_abspath($this->same_staging_folder)) {
			return ;
		}

		$this->replace_links_obj->create_htaccess($this->same_server_staging_url(), $this->same_staging_folder, 'multisite');
	}

	private function normal_site_default_htaccess(){
		if (wpss_is_abspath($this->same_staging_folder)) {
			return ;
		}

		$this->replace_links_obj->create_htaccess($this->same_server_staging_url(), $this->same_staging_folder, 'normal');
	}

	private function create_robot_txt(){
		if (wpss_is_abspath($this->same_staging_folder)) {
			return ;
		}

		$data = "User-agent: *\nDisallow: /\n";
		@file_put_contents($this->same_staging_folder . 'robots.txt', $data);
	}

	private function same_server_reset_index_php(){

		if (wpss_is_abspath($this->same_staging_folder)) {
			return ;
		}

		$path = $this->same_staging_folder . 'index.php';
		$content = file_get_contents($path);

		if ($content) {

			$pattern = "/(require(.*)wp-blog-header.php' \);)/";
			if ( !preg_match($pattern, $content, $matches) ){
				wpss_log(array(), '---------Fatal error: wp-blog-header.php not------------');
			}
			$pattern2 = "/require(.*) dirname(.*) __FILE__ (.*) \. '(.*)wp-blog-header.php'(.*);/";
			$replace = "require( dirname( __FILE__ ) . '/wp-blog-header.php' );// " . $matches[0] . " // Changed by Super Stage WP";
			$content = preg_replace($pattern2, $replace, $content);

			$content = $this->replace_links_obj->remove_unwanted_comment_lines($content);

			if (FALSE === file_put_contents($path, $content)) {
				wpss_log($path . ' is not writable', '-------FAILED--------------');
			} else {
				wpss_log('Index file updated successfully', '-------FAILED--------------');
			}
		} else {
			wpss_log($path . ' is not writable', '-------FAILED--------------');
		}
	}

	private function same_server_replace_pathname($path){

		if ( wpss_is_wp_content_path($path) 
			&& wpss_is_wp_content_dir_moved_outside_root($this->config) ) {

			return wp_normalize_path(str_replace(dirname(WPSS_WP_CONTENT_DIR) , $this->same_staging_folder, $path));
		}

		return wp_normalize_path(str_replace(WPSS_ABSPATH , $this->same_staging_folder, $path));
	}

	private function same_server_set_staging_path($path = false){
		$path = empty($path) ? $this->options->get_option('same_server_staging_path', true) : $path;
		$this->same_staging_folder = wp_normalize_path(get_home_path_wpss() . $path . '/');
		$this->same_staging_bridge_dir = wp_normalize_path($this->same_staging_folder . self::CLONE_TMP_FOLDER . '/');

		wpss_log($this->same_staging_bridge_dir, '---------------$this->same_staging_bridge_dir-----------------');
	}

	private function get_staging_db_prefix($type = 'prefix'){
		$prefix = $this->options->get_option('same_server_staging_db_prefix');

		if (empty($prefix)) {
			$prefix = time();
			$full_prefix = $prefix . '_' . $this->wpdb->base_prefix;
			$this->options->set_option('same_server_staging_db_prefix', $prefix);
			$this->options->set_option('same_server_staging_full_db_prefix', $full_prefix);
		}

		if ($type === 'prefix') {
			return $prefix;
		}

		return $this->options->get_option('same_server_staging_full_db_prefix');
	}

	public function get_existing_staging_prefix(){
		$prefix = $this->get_staging_details('db_prefix');
		$full_prefix = $prefix . $this->wpdb->base_prefix;
		if (empty($prefix) || $prefix == $this->wpdb->base_prefix) {
			return false;
		}
		return $full_prefix;
	}

	private function same_server_staging_url(){
		return wpss_get_live_url() . '/' . $this->options->get_option('same_server_staging_path');
	}

	public function same_server_staging_bridge_url(){
		return wpss_get_live_url() . '/' . $this->options->get_option('same_server_staging_path') . '/' . self::CLONE_TMP_FOLDER . '/' . 'bridge.php' ;
	}

	public function save_staging_settings($data){

		if (!empty($data['db_rows_clone_limit_wpss'])) {
			$this->config->set_option('internal_staging_db_rows_copy_limit', $data['db_rows_clone_limit_wpss']);
			$this->update_settings_on_staging_site('internal_staging_db_rows_copy_limit', $data['db_rows_clone_limit_wpss']);
		}

		if (!empty($data['files_clone_limit_wpss'])) {
			$this->config->set_option('internal_staging_file_copy_limit', $data['files_clone_limit_wpss']);
			$this->update_settings_on_staging_site('internal_staging_file_copy_limit', $data['files_clone_limit_wpss']);
		}

		if (!empty($data['deep_link_replace_limit_wpss'])) {
			$this->config->set_option('internal_staging_deep_link_limit', $data['deep_link_replace_limit_wpss']);
			$this->update_settings_on_staging_site('internal_staging_deep_link_limit', $data['deep_link_replace_limit_wpss']);
		}

		if (!empty($data['user_excluded_extenstions_staging'])) {
			$this->config->set_option('user_excluded_extenstions_staging', $data['user_excluded_extenstions_staging']);
			$this->update_settings_on_staging_site('user_excluded_extenstions_staging', $data['user_excluded_extenstions_staging']);
		}

		if (!empty($data['enable_admin_login_wpss'])) {
			$this->config->set_option('internal_staging_enable_admin_login', $data['enable_admin_login_wpss']);
			$this->update_settings_on_staging_site('internal_staging_enable_admin_login', $data['enable_admin_login_wpss']);
		} else {
			$this->config->set_option('internal_staging_enable_admin_login', false);
			$this->update_settings_on_staging_site('internal_staging_enable_admin_login', false);
		}

		if (!empty($data['load_images_from_live_site_settings_wpss'])) {
			$this->config->set_option('load_images_from_live_site_settings', $data['load_images_from_live_site_settings_wpss']);
			$this->update_settings_on_staging_site('load_images_from_live_site_settings', $data['load_images_from_live_site_settings_wpss']);
		} else {
			$this->config->set_option('load_images_from_live_site_settings', false);
			$this->update_settings_on_staging_site('load_images_from_live_site_settings', false);
		}

		if (!empty($data['reset_permalink_wpss'])) {
			$this->config->set_option('staging_is_reset_permalink', true);
		} else{
			$this->config->set_option('staging_is_reset_permalink', false);
		}

		if (!empty($data['login_custom_link_wpss'])) {
			$this->config->set_option('staging_login_custom_link', $data['login_custom_link_wpss']);
		} else{
			$this->config->set_option('staging_login_custom_link', false);
		}
		
	}

	private function staging_to_live_compatible(){
		$this->staging_to_live_copy_files();
		$this->staging_to_live_set_flags();
		$this->staging_to_live_copy_db();
	}

	public function staging_to_live_copy_files($is_our_staging_plugin_upgrade = false){

		$this->init_fs();
		$this->same_server_set_staging_path();

		$stage_wp_content = $this->same_server_replace_pathname(WPSS_WP_CONTENT_DIR);

		wpss_log($stage_wp_content, "--------stage_wp_content---before-----");

		if($is_our_staging_plugin_upgrade){
			$staging_details = $this->get_staging_details();
			$new_staging_abspath = ABSPATH . $staging_details['staging_folder'] . '/';
			$stage_wp_content = str_replace(ABSPATH, $new_staging_abspath, $stage_wp_content);
		}

		wpss_log($stage_wp_content, "--------stage_wp_content---after-----");
		
		if (WPSS_WP_CONTENT_DIR === $stage_wp_content) {

			wpss_log(ABSPATH, "--------ABSPATH is--------");
			wpss_log(WPSS_WP_CONTENT_DIR, '--------Dont copy to live site--------');

			return false;
		}

		$stage_plugins_dir = $stage_wp_content . '/plugins/' . WPSS_STAGING_PLUGIN_DIR_NAME;

		wpss_log($stage_plugins_dir, '---------------$stage_plugins_dir-----------------');

		//Key is Live path and Value is Staging path
		$files = array(
			WPSS_PLUGIN_DIR 	. 'wpss-common-functions.php'                                  => $stage_plugins_dir . '/wpss-common-functions.php' ,
			WPSS_PLUGIN_DIR 	. 'wpss-constants.php'                                    => $stage_plugins_dir . '/wpss-constants.php' ,
			WPSS_CLASSES_DIR 	. 'class-file-iterator.php'                               => $stage_plugins_dir . '/class-file-iterator.php',
			WPSS_PLUGIN_DIR 	. 'views/admin.css'                                             => $stage_plugins_dir . '/admin.css' ,

			WPSS_PLUGIN_DIR . '/wpss-init.php' => $stage_plugins_dir . '/wpss-init.php',

			WPSS_PLUGIN_DIR . '/wpss-factory.php' => $stage_plugins_dir . '/wpss-factory.php',
			WPSS_PLUGIN_DIR . '/wpss-base-factory.php' => $stage_plugins_dir . '/wpss-base-factory.php',
			WPSS_PLUGIN_DIR . '/wpss-app-functions.php' => $stage_plugins_dir . '/wpss-app-functions.php',
			WPSS_PLUGIN_DIR . '/wpss-config.php' => $stage_plugins_dir . '/wpss-config.php',
			WPSS_PLUGIN_DIR . '/wpss-base-config.php' => $stage_plugins_dir . '/wpss-base-config.php',
			WPSS_PLUGIN_DIR . '/wpss-exclude-option.php' => $stage_plugins_dir . '/wpss-exclude-option.php',
			WPSS_PLUGIN_DIR . '/class-processed-base.php' => $stage_plugins_dir . '/class-processed-base.php',
			WPSS_PLUGIN_DIR . '/class-processed-files.php' => $stage_plugins_dir . '/class-processed-files.php',
			WPSS_PLUGIN_DIR . '/class-processed-iterator.php' => $stage_plugins_dir . '/class-processed-iterator.php',
			WPSS_PLUGIN_DIR . '/class-file-iterator.php' => $stage_plugins_dir . '/class-file-iterator.php',
			WPSS_PLUGIN_DIR . '/class-replace-db-links.php' => $stage_plugins_dir . '/class-replace-db-links.php',
			WPSS_PLUGIN_DIR . '/class-database-backup.php' => $stage_plugins_dir . '/class-database-backup.php',
			WPSS_PLUGIN_DIR . '/class-logger.php' => $stage_plugins_dir . '/class-logger.php',
			WPSS_PLUGIN_DIR . '/class-filelist.php' => $stage_plugins_dir . '/class-filelist.php',

			WPSS_PLUGIN_DIR . '/Staging/init.php' => $stage_plugins_dir . '/Staging/init.php',
			WPSS_PLUGIN_DIR . '/Staging/Hooks.php' => $stage_plugins_dir . '/Staging/Hooks.php',
			WPSS_PLUGIN_DIR . '/Staging/Config.php' => $stage_plugins_dir . '/Staging/Config.php',
			WPSS_PLUGIN_DIR . '/Staging/HooksHandler.php' => $stage_plugins_dir . '/Staging/HooksHandler.php',
			WPSS_PLUGIN_DIR . '/Staging/class-stage-common.php' => $stage_plugins_dir . '/Staging/class-stage-common.php',
			WPSS_PLUGIN_DIR . '/Staging/class-update-in-staging.php' => $stage_plugins_dir . '/Staging/class-update-in-staging.php',
		);

		if (file_exists(WPSS_PLUGIN_DIR . 'wpss-env-parameters.php')) {
			$files[WPSS_PLUGIN_DIR . 'wpss-env-parameters.php'] = $stage_plugins_dir . '/wpss-env-parameters.php';
		}

		//Key is Live path and Value is Staging path
		$dirs = array(
			WPSS_PLUGIN_DIR . 'Staging'                    => $stage_plugins_dir . '/Staging/Views',
			WPSS_PLUGIN_DIR . 'ExcludeOption'                    => $stage_plugins_dir . '/ExcludeOption',
			// WPSS_PLUGIN_DIR . 'Classes'                    => $stage_plugins_dir . '/Classes',
			// WPSS_PLUGIN_DIR . 'Base'                       => $stage_plugins_dir . '/Base',
			// WPSS_PLUGIN_DIR . 'fonts'                      => $stage_plugins_dir . '/fonts',
			// WPSS_PLUGIN_DIR . 'utils'                      => $stage_plugins_dir . '/utils',
			// WPSS_PLUGIN_DIR . 'debug-chart'                => $stage_plugins_dir . '/debug-chart',
			WPSS_PLUGIN_DIR . 'Staging/stage-to-live/' => $stage_plugins_dir . '/',
			WPSS_PLUGIN_DIR . 'treeView'                   => $stage_plugins_dir . '/treeView',
		);

		foreach ($dirs as $live_path => $stage_path) {
			$this->file_iterator->copy_dir($live_path, $stage_path, true);
		}

		foreach ($files as $live_path => $stage_path) {
			$this->filesystem->copy($live_path, $stage_path, true, FS_CHMOD_FILE);
		}
	}

	public function staging_to_live_set_flags(){
		$this->config->set_option('s2l_live_path', WPSS_ABSPATH);

		if ($this->is_subdir_installation()) {
			$this->config->set_option('s2l_live_url', home_url());
			$this->config->set_option('s2l_site_url', site_url());
		} else {
			$this->config->set_option('s2l_live_url', site_url());
			$this->config->set_option('s2l_site_url', site_url());
		}

		$this->config->set_option('s2l_live_db_prefix', $this->wpdb->base_prefix);
		$this->config->set_option('s2l_wp_content_dir', WPSS_WP_CONTENT_DIR);
		$this->config->set_option('s2l_live_permalink_structure', get_option('permalink_structure'));
		$this->config->set_option('s2l_wpss_plugin_version', WPSS_VERSION);

		$this->subdir_installation_related();
	}

	private function subdir_installation_related(){
		if($this->is_subdir_installation()){
			$this->config->set_option('s2l_is_subdir_installation', true);
		} else {
			$this->config->set_option('s2l_is_subdir_installation', false);
		}
	}

	private function is_subdir_installation(){
		$admin_url = get_admin_url();
		$admin_url = wpss_remove_trailing_slash($admin_url);
		wpss_log($admin_url,'-----------$admin_url----------------');

		$home_url = get_home_url();
		$home_url = wpss_remove_trailing_slash($home_url);
		wpss_log($home_url,'----------$home_url-----------------');

		$final_url = str_replace($home_url, '', $admin_url);
		$count = substr_count($final_url, '/');
		wpss_log($count,'-----------$count----------------');

		return $count > 1 ? true : false;
	}


	public function staging_to_live_copy_db(){

		if (apply_filters('is_restore_to_staging_wpss', '')) {
				$tables = array(
				$this->wpdb->prefix . 'wpss_activity_log'             => 'schema',
				$this->wpdb->prefix . 'wpss_current_process'          => 'schema',
				$this->wpdb->prefix . 'wpss_processed_iterator'       => 'schema',

				$this->wpdb->prefix . 'wpss_options'                  => 'full',
				$this->wpdb->prefix . 'wpss_inc_exc_contents'         => 'full',
				$this->wpdb->prefix . 'wpss_backups'                  => 'full',
				$this->wpdb->prefix . 'wpss_processed_files'          => 'full',
		);
		} else {
			$tables = array(
				$this->wpdb->base_prefix . 'wpss_activity_log'       => 'schema',
				$this->wpdb->base_prefix . 'wpss_current_process'    => 'schema',
				$this->wpdb->base_prefix . 'wpss_processed_iterator' => 'schema',
				$this->wpdb->base_prefix . 'wpss_options'            => 'full',
				$this->wpdb->prefix      . 'wpss_inc_exc_contents'   => 'full',
				$this->wpdb->base_prefix . 'wpss_local_site_new_attachments' => 'schema',
				);
		}

		foreach ($tables as $table => $type) {
			$db_prefix = $this->get_staging_db_prefix('prefix');
			$new_table  = $db_prefix . '_' . $table;
			$this->staging_common->clone_table_structure($table, $new_table);

			if (empty($type) || $type !== 'full' || $type === 'schema') {
				continue;
			}

			$this->staging_common->clone_table_content($table, $new_table, $limit = WPSS_STAGING_DEFAULT_COPY_DB_ROWS_LIMIT, $offset = 0);
		}
	}

	private function things_to_do_after_completion(){
		wpss_log(func_get_args(), "--------" . __FUNCTION__ . "--------");
		if(!apply_filters('is_restore_to_staging_wpss', '')) {
			return false;
		}

		//Replace live paths to staging

		$local_paths = array(
			'backup_db_path',
			'site_abspath',
		);

		foreach ($local_paths as $key) {
			$live_path = $this->get_option_on_staging_site($key);
			$staging_path = $this->process_staging_details_hook(array('type' => 'replace', 'key' => $live_path));
			$this->update_settings_on_staging_site($key, $staging_path);
		}

		$staging_url = $this->process_staging_details_hook(array('type' => 'get', 'key' => 'destination_url'));

		$staging_url = wpss_remove_trailing_slash($staging_url);

		$this->update_settings_on_staging_site('site_url_wpss', $staging_url);

		$staging_network_admin = str_replace($this->config->get_option('site_url_wpss') , $staging_url, $this->config->get_option('network_admin_url'));
		$this->update_settings_on_staging_site('network_admin_url', $staging_network_admin);

		$this->update_settings_on_staging_site('same_server_staging_running', false);
		$this->update_settings_on_staging_site('same_server_replace_old_url_data', false);
		$this->update_settings_on_staging_site('same_server_replace_old_url', false);
		$this->update_settings_on_staging_site('restore_deep_links_completed', false);
		$this->update_settings_on_staging_site('R2S_replace_links', false);
	}

	public function set_options_to_staging_site($name, $value){
		wpss_log(func_get_args(), "--------" . __FUNCTION__ . "--------");
		$this->update_settings_on_staging_site($name, $value);
	}

	private function update_settings_on_staging_site($name, $value){

		if (empty($name)) {
			return ;
		}

		$staging_prefix = $this->get_staging_details('db_prefix');

		if (empty($staging_prefix)) {
			return ;
		}

		$old_value = $this->get_option_on_staging_site($name);

		if ($old_value === $value) {
			return true;
		}

		if ( !$this->get_key_on_staging_site($name) ) {
			$insert = $this->wpdb->insert( $staging_prefix . 'wpss_options',	array(
					'name' => $name,
					'value' => $value,
					)
				);
			if ($insert === false) {
				wpss_log($this->wpdb->last_error,'-----------$this->wpdb->last_error----------------');
			}

			return $insert;
		}

		$update = $this->wpdb->update(	$staging_prefix . 'wpss_options',
				array('value' => $value),
				array('name' => $name)
			);

		if ($update === false) {
			wpss_log($this->wpdb->last_error,'-----------$this->wpdb->last_error----------------');
		}

		return $update;
	}

	private function get_option_on_staging_site($name) {
		$staging_prefix = $this->get_staging_details('db_prefix');

		return $this->wpdb->get_var(
			$this->wpdb->prepare("SELECT value FROM " .$staging_prefix ."wpss_options WHERE name = %s", $name)
		);
	}

	private function get_key_on_staging_site($name) {
		$staging_prefix = $this->get_staging_details('db_prefix');

		return $this->wpdb->get_var(
			$this->wpdb->prepare("SELECT name FROM " .$staging_prefix ."wpss_options WHERE name = %s", $name)
		);

	}
}
