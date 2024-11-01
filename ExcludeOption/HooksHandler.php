<?php

class WPSS_Exclude_Hooks_Handler {
	protected $config;

	public function __construct() {
		$this->backup_obj = WPSS_Base_Factory::get('WPSS_Backup');
		$this->ExcludeOption = WPSS_Base_Factory::get('WPSS_ExcludeOption');
	}

	//WPTC's specific hooks start

	public function wpss_get_root_files($args) {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		$this->ExcludeOption->get_root_files();
	}

	public function wpss_get_init_root_files($args) {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		$this->ExcludeOption->get_root_files($exc_wp_files = true);
	}

	public function wpss_get_init_files_by_key($args) {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		$key = sanitize_text_field($_REQUEST['key']);
		$this->ExcludeOption->get_files_by_key($key);
	}

	public function wpss_get_tables($args) {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		$this->ExcludeOption->get_tables();
	}

	public function wpss_get_init_tables($args) {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		$this->ExcludeOption->get_tables($exc_wp_tables = true);
	}

	public function wpss_get_files_by_key() {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		$key = sanitize_text_field($_REQUEST['key']);
		$this->ExcludeOption->get_files_by_key($key);
	}

	public function include_file_list() {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		if (!isset($_POST['data'])) {
			wpss_die_with_json_encode( array('status' => 'no data found') );
		}
		$this->ExcludeOption->include_file_list($_POST['data']);
	}

	public function exclude_file_list() {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		if (!isset($_POST['data'])) {
			wpss_die_with_json_encode( array('status' => 'no data found') );
		}
		$this->ExcludeOption->exclude_file_list($_POST['data']);
	}

	public function include_table_list() {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		if (!isset($_POST['data'])) {
			wpss_die_with_json_encode( array('status' => 'no data found') );
		}
		$this->ExcludeOption->include_table_list($_POST['data']);
	}

	public function include_table_structure_only() {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		if (!isset($_POST['data'])) {
			wpss_die_with_json_encode( array('status' => 'no data found') );
		}
		$this->ExcludeOption->include_table_structure_only($_POST['data']);
	}

	public function exclude_table_list() {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		if (!isset($_POST['data'])) {
			wpss_die_with_json_encode( array('status' => 'no data found') );
		}
		$this->ExcludeOption->exclude_table_list($_POST['data']);
	}

	public function analyze_inc_exc() {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		$this->ExcludeOption->analyze_inc_exc();
	}

	public function exclude_all_suggested_items() {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		$this->ExcludeOption->exclude_all_suggested_items($_POST);
	}

	public function get_all_excluded_files() {

		WPSS_Base_Factory::get('WPSS_App_Functions')->verify_ajax_requests();

		$this->ExcludeOption->get_all_excluded_files();
	}

}
