<?php
/*
Plugin Name: Super Stage WP Staging
Plugin URI: https://wpsuperstage.com
Description: Super Stage WP Staging plugin.
Author: Revmakx
Version: 1.0.0
Author URI: http://www.revmakx.com
Tested up to: 5.9.3
/************************************************************
 * This plugin was modified by Revmakx
 * Copyright (c) 2017 Revmakx
 * www.revmakx.com
 ************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WP_Super_Stage_Staging{

	private $stage_to_live;

	public function __construct(){
		$this->include_constants_file();
		$this->include_files();
		$this->include_primary_files_wpss();
		$this->create_objects();
		$this->init_hooks();

		$exclude_hooks = new WPSS_Exclude_Hooks();
        $exclude_hooks->register_hooks();
	}

	/**
	 * Define WPSS Staging Constants.
	*/
	private function include_constants_file() {
		$this->define('WPSS_IS_STAGING_SITE', true);
		require_once dirname(__FILE__).  DIRECTORY_SEPARATOR  .'wpss-constants.php';
		$constants = new WPSS_Constants();
		$constants->init_staging_plugin();
	}

	private function include_files(){

		include_once(WPSS_PLUGIN_DIR . 'wpss-common-functions.php');

		include_once(WPSS_PLUGIN_DIR . 'wpss-init.php');

		include_once(WPSS_PLUGIN_DIR . '/wpss-factory.php');
		include_once(WPSS_PLUGIN_DIR . '/wpss-base-factory.php');
		include_once(WPSS_PLUGIN_DIR . '/wpss-app-functions.php');
		include_once(WPSS_PLUGIN_DIR . '/wpss-config.php');
		include_once(WPSS_PLUGIN_DIR . 'wpss-base-config.php');
		// include_once(WPSS_PLUGIN_DIR . '/wpss-exclude-option.php');
		include_once(WPSS_PLUGIN_DIR . '/class-processed-base.php');
		include_once(WPSS_PLUGIN_DIR . '/class-processed-files.php');
		include_once(WPSS_PLUGIN_DIR . '/class-processed-iterator.php');
		include_once(WPSS_PLUGIN_DIR . '/class-file-iterator.php');
		include_once(WPSS_PLUGIN_DIR . '/class-replace-db-links.php');
		include_once(WPSS_PLUGIN_DIR . '/class-database-backup.php');
		include_once(WPSS_PLUGIN_DIR . '/class-logger.php');
		include_once(WPSS_PLUGIN_DIR . '/class-filelist.php');

		include_once(WPSS_PLUGIN_DIR . 'includes/class-stage-to-live.php' );
		include_once(WPSS_PLUGIN_DIR . 'includes/class-load-live-image.php' );

		include_once(WPSS_PLUGIN_DIR . 'ExcludeOption/init.php');
		include_once(WPSS_PLUGIN_DIR . 'ExcludeOption/Hooks.php');
		include_once(WPSS_PLUGIN_DIR . 'ExcludeOption/HooksHandler.php');
		include_once(WPSS_PLUGIN_DIR . 'ExcludeOption/Config.php');
		include_once(WPSS_PLUGIN_DIR . 'ExcludeOption/ExcludeOption.php');

		include_once(WPSS_PLUGIN_DIR . '/Staging/init.php');
		// include_once(WPSS_PLUGIN_DIR . '/Staging/Config.php');
		include_once(WPSS_PLUGIN_DIR . '/Staging/Hooks.php');
		include_once(WPSS_PLUGIN_DIR . '/Staging/HooksHandler.php');
		include_once(WPSS_PLUGIN_DIR . '/Staging/class-stage-common.php');
		include_once(WPSS_PLUGIN_DIR . '/Staging/class-update-in-staging.php');

	}

	private function include_primary_files_wpss() {

		// include_once( WPSS_PLUGIN_DIR.'Base/Factory.php' );

		// include_once( WPSS_PLUGIN_DIR.'Base/init.php' );
		// include_once( WPSS_PLUGIN_DIR.'Base/Hooks.php' );
		// include_once( WPSS_PLUGIN_DIR.'Base/HooksHandler.php' );
		// include_once( WPSS_PLUGIN_DIR.'Base/Config.php' );

		// include_once( WPSS_PLUGIN_DIR.'Base/CurlWrapper.php' );

		// include_once( WPSS_CLASSES_DIR.'CronServer/Config.php' );
		// include_once( WPSS_CLASSES_DIR.'CronServer/CurlWrapper.php' );

		// include_once( WPSS_CLASSES_DIR.'WPSSBackup/init.php' );
		// include_once( WPSS_CLASSES_DIR.'WPSSBackup/Hooks.php' );
		// include_once( WPSS_CLASSES_DIR.'WPSSBackup/HooksHandler.php' );
		// include_once( WPSS_CLASSES_DIR.'WPSSBackup/Config.php' );

		// include_once( WPSS_CLASSES_DIR.'Common/init.php' );
		// include_once( WPSS_CLASSES_DIR.'Common/Hooks.php' );
		// include_once( WPSS_CLASSES_DIR.'Common/HooksHandler.php' );
		// include_once( WPSS_CLASSES_DIR.'Common/Config.php' );

		// include_once( WPSS_CLASSES_DIR.'Analytics/init.php' );
		// include_once( WPSS_CLASSES_DIR.'Analytics/Hooks.php' );
		// include_once( WPSS_CLASSES_DIR.'Analytics/HooksHandler.php' );
		// include_once( WPSS_CLASSES_DIR.'Analytics/Config.php' );
		// include_once( WPSS_CLASSES_DIR.'Analytics/BackupAnalytics.php' );

		// include_once( WPSS_CLASSES_DIR.'ExcludeOption/init.php' );
		// include_once( WPSS_CLASSES_DIR.'ExcludeOption/Hooks.php' );
		// include_once( WPSS_CLASSES_DIR.'ExcludeOption/HooksHandler.php' );
		// include_once( WPSS_CLASSES_DIR.'ExcludeOption/Config.php' );
		// include_once( WPSS_CLASSES_DIR.'ExcludeOption/ExcludeOption.php' );

		// include_once( WPSS_CLASSES_DIR.'Settings/init.php' );
		// include_once( WPSS_CLASSES_DIR.'Settings/Hooks.php' );
		// include_once( WPSS_CLASSES_DIR.'Settings/HooksHandler.php' );
		// include_once( WPSS_CLASSES_DIR.'Settings/Config.php' );
		// include_once( WPSS_CLASSES_DIR.'Settings/Settings.php' );

		// include_once( WPSS_CLASSES_DIR.'AppFunctions/init.php' );
		// include_once( WPSS_CLASSES_DIR.'AppFunctions/Hooks.php' );
		// include_once( WPSS_CLASSES_DIR.'AppFunctions/HooksHandler.php' );
		// include_once( WPSS_CLASSES_DIR.'AppFunctions/Config.php' );
		// include_once( WPSS_CLASSES_DIR.'AppFunctions/AppFunctions.php' );

		// include_once( WPSS_CLASSES_DIR.'InitialSetup/init.php' );
		// include_once( WPSS_CLASSES_DIR.'InitialSetup/Hooks.php' );
		// include_once( WPSS_CLASSES_DIR.'InitialSetup/HooksHandler.php' );
		// include_once( WPSS_CLASSES_DIR.'InitialSetup/Config.php' );
		// include_once( WPSS_CLASSES_DIR.'InitialSetup/InitialSetup.php' );

		if(is_wpss_server_req() || is_admin()) {
			// WPSS_Base_Factory::get('WPSS_Base')->init();
			// new WPSS_Init();
		}
	}

	private function create_objects(){
		$this->stage_to_live = new WPSS_Stage_To_Live();
	}

	private function init_hooks(){
		add_action('wp_enqueue_scripts',           array($this,                'add_frontend_styles'));
		add_action('admin_init',                            array($this, 'admin_init'));
		add_action('init',                            array($this, 'add_frontend_scripts'));
		// add_action('admin_enqueue_scripts',           array($this,                'add_scripts'));
		add_action('admin_enqueue_scripts',           array($this,                'add_styles'));
		add_action('admin_head', array($this, 'admin_head'));
		add_action('wp_head', array($this, 'admin_head'));
		add_action('login_head', array($this, 'admin_head'));

		add_action('wp_before_admin_bar_render',      array($this->stage_to_live, 'change_sitename'));
		add_action('init',                            array($this->stage_to_live, 'check_permissions'));
		add_action('wp_ajax_wpss_copy_stage_to_live', array($this->stage_to_live, 'to_live'));

		// $exclude_class_obj = new WPSS_Exclude_Hooks_Handler($category = 'staging');

		// add_action('wp_ajax_wpss_get_root_files',               array($exclude_class_obj, 'wpss_get_root_files'));
		// add_action('wp_ajax_wpss_get_files_by_key',             array($exclude_class_obj, 'wpss_get_files_by_key'));
		// add_action('wp_ajax_wpss_get_tables',                   array($exclude_class_obj, 'wpss_get_tables'));
		// add_action('wp_ajax_exclude_file_list_wpss',            array($exclude_class_obj, 'exclude_file_list'));
		// add_action('wp_ajax_include_file_list_wpss',            array($exclude_class_obj, 'include_file_list'));
		// add_action('wp_ajax_exclude_table_list_wpss',           array($exclude_class_obj, 'exclude_table_list'));
		// add_action('wp_ajax_include_table_list_wpss',           array($exclude_class_obj, 'include_table_list'));
		// add_action('wp_ajax_include_table_structure_only_wpss', array($exclude_class_obj, 'include_table_structure_only'));

		$load_live_image = new WPSS_Load_Live_Image();
		// add_action( 'setup_theme', array($load_live_image, 'handle_requests') );
		add_action( 'the_content', array($load_live_image, 'modify_posts_content') );
		add_action( 'wp_get_attachment_url', array($load_live_image, 'modify_image_site_url') );
		add_action( 'admin_print_footer_scripts', array($load_live_image, 'admin_print_footer_scripts') );
		// add_action( 'admin_print_footer_scripts', array($load_live_image, 'fill_global_js_vars') );
		add_filter( 'wp_calculate_image_srcset', array($load_live_image, 'modify_image_src_set') );
		add_filter( 'wp_insert_attachment_data', array($load_live_image, 'wp_insert_attachment_data') );

		add_filter('the_content', array($this, 'replace_relative_url_wpss'));
		$this->add_admin_menu_hook();
	}

	public function replace_relative_url_wpss($content){
		if(empty($content)){
			return $content;
		}
		
		$this_site_url = site_url();

		global $wpdb;
		$query = "SELECT `value` FROM " . $wpdb->base_prefix . "wpss_options WHERE `name`='s2l_live_url'; ";
		$this_live_site_url = $wpdb->get_var($query);

		$live_site_base_paths = explode("/", $this_live_site_url);
		$live_site_origin_url = $live_site_base_paths[0] . '//' . $live_site_base_paths[2];

		$live_site_base_path = str_replace($live_site_origin_url, '', $this_live_site_url);

		if(empty($live_site_base_path)){
			$live_site_base_path = '';
		}

		$live_site_base_path = ltrim($live_site_base_path, '/');
      
		$content = str_replace('href="/' . $live_site_base_path, 'href="' . $this_site_url . '/', $content);

		return $content;
	}

	private function add_admin_menu_hook(){


		if ( is_multisite() ) {
			add_action('network_admin_menu', array($this, 'add_admin_menu_new'));
		} else{
			// add_action('admin_menu', array($this, 'add_admin_menu'));
			add_action('admin_menu', array($this, 'add_admin_menu_new'));
		}
	}

	public function add_frontend_styles()	{

		if(is_windows_machine_wpss()){
			$site_url = site_url();
			$wp_content = basename(WPSS_WP_CONTENT_DIR);
			$plugin_dir = $site_url . '/' . $wp_content . '/' . 'plugins';
		} else {
			$plugin_dir = plugins_url();
		}

		wpss_log('', "--------add_frontend_styles--------");
		wp_enqueue_style('wpss-s2l-css',              $plugin_dir . '/' . basename(dirname(__FILE__)) . '/css/wpss-s2l.css',                  array(), WPSS_VERSION);
		wp_enqueue_script('wpss-s2l-frontend',              $plugin_dir . '/' . basename(dirname(__FILE__)) . '/js/wpss-s2l-frontend.js',                  array(), WPSS_VERSION);
	}

	public function admin_init() {
		

		// $load_live_image = new WPSS_Load_Live_Image();
		// $load_live_image->fill_global_js_vars();
	}

	public function add_frontend_scripts()	{
		if(is_windows_machine_wpss()){
			$site_url = site_url();
			$wp_content = basename(WPSS_WP_CONTENT_DIR);
			$plugin_dir = $site_url . '/' . $wp_content . '/' . 'plugins';
		} else {
			$plugin_dir = plugins_url();
		}

		wpss_log('', "--------add_frontend_scripts	--------");
		
		wp_enqueue_script("jquery");
		
		wp_enqueue_script('wpss-s2l-frontend',              $plugin_dir . '/' . basename(dirname(__FILE__)) . '/js/wpss-s2l-frontend.js',                  array(), WPSS_VERSION);


	}

	public function add_scripts(){

		if(is_windows_machine_wpss()){
			$site_url = site_url();
			$wp_content = basename(WPSS_WP_CONTENT_DIR);
			$plugin_dir = $site_url . '/' . $wp_content . '/' . 'plugins';
		} else {
			$plugin_dir = plugins_url();
		}

		wp_enqueue_script('wpss-staging-js',          $plugin_dir . '/' . basename(dirname(__FILE__)) . '/js/wpss-staging.js',                array(), WPSS_VERSION);
		wp_enqueue_script('wpss-jquery-ui-custom-js', $plugin_dir . '/' . basename(dirname(__FILE__)) . '/treeView/jquery-ui.custom.js',   array(), WPSS_VERSION);
		wp_enqueue_script('wpss-fancytree-js',        $plugin_dir . '/' . basename(dirname(__FILE__)) . '/treeView/jquery.fancytree.js',   array(), WPSS_VERSION);
		wp_enqueue_style('wpss-fancytree-css',        $plugin_dir . '/' . basename(dirname(__FILE__)) . '/treeView/skin/ui.fancytree.css', array(), WPSS_VERSION);
		wp_enqueue_script('wpss-filetree-common-js',  $plugin_dir . '/' . basename(dirname(__FILE__)) . '/treeView/common.js',              array(), WPSS_VERSION);
		// wp_enqueue_style('wpss-s2l-css',              $plugin_dir . '/' . basename(dirname(__FILE__)) . '/css/wpss-s2l.css',                  array(), WPSS_VERSION);
		// wp_enqueue_style('wpss-css',                  $plugin_dir . '/' . basename(dirname(__FILE__)) . '/super-stage-wp.css',               array(), WPSS_VERSION);
		// wp_enqueue_style('wpss-ui-css',               $plugin_dir . '/' . basename(dirname(__FILE__)) . '/tc-ui.css',                         array(), WPSS_VERSION);
		$this->add_nonce();
	}

	public function admin_head(){
		wpss_log(array(),'-----------admin_head----------------');

		$load_live_image = new WPSS_Load_Live_Image();
		$load_live_image->fill_global_js_vars();
	}

	public function add_styles(){

		if(is_windows_machine_wpss()){
			$site_url = site_url();
			$wp_content = basename(WPSS_WP_CONTENT_DIR);
			$plugin_dir = $site_url . '/' . $wp_content . '/' . 'plugins';
		} else {
			$plugin_dir = plugins_url();
		}

		wp_enqueue_style('wpss-s2l-css',              $plugin_dir . '/' . basename(dirname(__FILE__)) . '/css/wpss-s2l.css',                  array(), WPSS_VERSION);
		wp_enqueue_style('wpss-admin-css',                  $plugin_dir . '/' . basename(dirname(__FILE__)) . '/admin.css',               array(), WPSS_VERSION);
		// wp_enqueue_style('wpss-ui-css',               $plugin_dir . '/' . basename(dirname(__FILE__)) . '/tc-ui.css',                         array(), WPSS_VERSION);
		$this->add_nonce();
	}

	public function add_nonce(){
		$params = array(
			'ajax_nonce' => wp_create_nonce('wpss_nonce'),
			'admin_url'  => network_admin_url(),
		);
		wp_localize_script( 'wpss-staging-js', 'wpss_ajax_object', $params );
	}

	public function add_admin_menu_with_this_name($name = 'Super Stage WP') {
		$text = __($name . ' Staging', 'super-stage-wp-staging');

		if($name == 'Super Stage WP'){
			$my_page = add_menu_page($text, $text, 'activate_plugins', 'super-stage-wp-staging', array($this, 'staging_page'), 'dashicons-wpss', '80.0564');
			add_action( 'load-' . $my_page, array($this, 'initiate_custom_js_enqueue_script') );
		} else {
			$my_page = add_menu_page($text, $text, 'activate_plugins', 'super-stage-wp-staging', array($this, 'staging_page'), 'dashicons-cloud', '80.0564');
			add_action( 'load-' . $my_page, array($this, 'initiate_custom_js_enqueue_script') );
		}

	}

	public function initiate_custom_js_enqueue_script() {
		add_action('admin_enqueue_scripts',           array($this,                'add_scripts'));
	}

	public function add_admin_menu() {
		$text = __('Super Stage WP Staging', 'super-stage-wp-staging');
		add_menu_page($text, $text, 'activate_plugins', 'super-stage-wp-staging', array($this, 'staging_page'), 'dashicons-wpss', '80.0564');
	}

	public function add_admin_menu_new(){

		$this->add_admin_menu_with_this_name();

	}

	public function staging_page() {
		$stage_to_live = $this->stage_to_live;
		include_once 'views/super-stage-wp-staging.php';
	}

	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
}

new WP_Super_Stage_Staging();
