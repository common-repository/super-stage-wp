<?php
class WPSS_Staging_Hooks {
	public $hooks_handler_obj;
	public $wp_filter_id;

	public function __construct() {
		$this->hooks_handler_obj = WPSS_Base_Factory::get('WPSS_Staging_Hooks_Hanlder');
	}

	public function register_hooks() {
		$this->register_actions();
		$this->register_filters();
		$this->register_wpss_actions();
		$this->register_wpss_filters();
	}

	public function register_actions() {
		add_action('wp_ajax_start_fresh_staging_wpss', array($this->hooks_handler_obj, 'start_fresh_staging'));
		add_action('wp_ajax_copy_staging_wpss', array($this->hooks_handler_obj, 'copy_staging'));
		add_action('wp_ajax_continue_staging_wpss', array($this->hooks_handler_obj, 'continue_staging'));
		add_action('wp_ajax_delete_staging_wpss', array($this->hooks_handler_obj, 'delete_staging_wpss'));
		add_action('wp_ajax_get_staging_details_wpss', array($this->hooks_handler_obj, 'get_staging_details'));
		add_action('wp_ajax_stop_staging_wpss', array($this->hooks_handler_obj, 'stop_staging_wpss'));
		add_action('wp_ajax_is_staging_need_request_wpss', array($this->hooks_handler_obj, 'is_staging_need_request'));

		add_action('wp_ajax_get_staging_url_wpss', array($this->hooks_handler_obj, 'get_staging_url_wpss'));
		add_action('wp_ajax_save_staging_settings_wpss', array($this->hooks_handler_obj, 'save_staging_settings'));
		add_action('wp_ajax_get_staging_current_status_key_wpss', array($this->hooks_handler_obj, 'get_staging_current_status_key'));
	}

	public function register_filters() {

	}

	public function register_filters_may_be_prevent_auto_update() {

	}

	public function register_wpss_actions() {
		add_action('add_additional_sub_menus_wpss_h', array($this->hooks_handler_obj, 'add_additional_sub_menus_wpss_h'), 10, 2);
		add_action('init_staging_wpss_h', array($this->hooks_handler_obj, 'init_staging_wpss_h'));
		add_action('add_staging_req_h', array($this->hooks_handler_obj, 'add_staging_req_h'));
		add_action('send_response_node_staging_wpss_h', array($this->hooks_handler_obj, 'send_response_node_staging_wpss_h'));
		add_action('admin_enqueue_scripts', array($this->hooks_handler_obj, 'enque_js_files'));
		add_action('is_staging_taken_wpss', array($this->hooks_handler_obj, 'is_staging_taken'));
		add_action('upgrade_our_staging_plugin_wpss', array($this->hooks_handler_obj, 'upgrade_our_staging_plugin_wpss'));
		add_action( 'admin_print_footer_scripts', array($this->hooks_handler_obj, 'admin_print_footer_scripts') );
	}

	public function register_wpss_filters() {
		add_filter('is_any_staging_process_going_on', array($this->hooks_handler_obj, 'is_any_staging_process_going_on'), 10);
		add_filter('get_internal_staging_db_prefix', array($this->hooks_handler_obj, 'get_internal_staging_db_prefix'), 10);
		add_filter('page_settings_tab_wpss', array($this->hooks_handler_obj, 'page_settings_tab'), 10);
		add_filter('page_settings_content_wpss', array($this->hooks_handler_obj, 'page_settings_content'), 10);
		add_filter('process_staging_details_hook_wpss', array($this->hooks_handler_obj, 'process_staging_details_hook'), 10, 1);
		add_filter('set_options_to_staging_site_wpss', array($this->hooks_handler_obj, 'set_options_to_staging_site'), 10, 2);
	}

}
