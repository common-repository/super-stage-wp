<?php 

class WPSS_Init {

    public function __construct() {
        $this->init();
    }

    public function init() {
        $this->check_install();

        add_action('admin_menu', array($this, 'register_filters'));
        add_action('admin_enqueue_scripts', array($this, 'enque_js_files'));
        add_action('wp_enqueue_scripts', array($this, 'enque_frontend_js_files'));

        $staging_hooks = new WPSS_Staging_Hooks();
        $staging_hooks->register_hooks();
        $exclude_hooks = new WPSS_Exclude_Hooks();
        $exclude_hooks->register_hooks();
    }

    public function check_install() {
        global $wpdb;
        $is_wpss_installed 	= WPSS_Base_Factory::get('WPSS_App_Functions')->is_wpss_installed();

        if($is_wpss_installed){
            
            return false;
        }

        include_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
        $cachecollation 	= wpss_get_collation();
    
        $table_name = $wpdb->base_prefix . 'wpss_options';
        dbDelta("CREATE TABLE IF NOT EXISTS $table_name (
            name varchar(50) NOT NULL,
            value text NOT NULL,
            UNIQUE KEY name (name)
        ) " . $cachecollation . " ;");
    
        $table_name = $wpdb->base_prefix . 'wpss_current_process';
        dbDelta("CREATE TABLE IF NOT EXISTS $table_name (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `file_path` text NOT NULL,
            `status` char(1) NOT NULL DEFAULT 'Q' COMMENT 'P=Processed, Q= In Queue, S- Skipped',
            `processed_time` varchar(30) NOT NULL,
            `file_hash` varchar(128) DEFAULT NULL,
            PRIMARY KEY (`id`),
            INDEX `file_path` (`file_path`(191))
            ) ENGINE=MyISAM " . $cachecollation . ";"
        );
    
        $table_name = $wpdb->base_prefix . 'wpss_processed_files';
        dbDelta("CREATE TABLE IF NOT EXISTS $table_name (
          `file` text DEFAULT NULL,
          `offset` int(50) NULL DEFAULT '0',
          `uploadid` text DEFAULT NULL,
          `file_id` bigint(20) NOT NULL AUTO_INCREMENT,
          `backupID` double DEFAULT NULL,
          `revision_number` text DEFAULT NULL,
          `revision_id` text DEFAULT NULL,
          `mtime_during_upload` varchar(22) DEFAULT NULL,
          `uploaded_file_size` bigint(20) DEFAULT NULL,
          `g_file_id` text DEFAULT NULL,
          `s3_part_number` int(10) DEFAULT NULL,
          `s3_parts_array` longtext DEFAULT NULL,
          `cloud_type` varchar(50) DEFAULT NULL,
          `parent_dir` TEXT DEFAULT NULL,
          `is_dir` INT(1) DEFAULT NULL,
          `file_hash` varchar(128) DEFAULT NULL,
          `life_span` double DEFAULT NULL,
          `filepath_md5` varchar(32) NULL,
          PRIMARY KEY (`file_id`),
          INDEX `uploaded_file_size` (`uploaded_file_size`),
          INDEX `backupID` (`backupID`),
          INDEX `filepath_md5` (`filepath_md5`),
          INDEX `file` (`file`(191))
        ) ENGINE=InnoDB  " . $cachecollation . ";");
    
        $table_name = $wpdb->base_prefix . 'wpss_processed_iterator';
        dbDelta("CREATE TABLE IF NOT EXISTS $table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` longtext NOT NULL,
            `offset` text DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) " . $cachecollation . " ;");
    
        $table_name = $wpdb->base_prefix . 'wpss_inc_exc_contents';
        dbDelta("CREATE TABLE IF NOT EXISTS $table_name (
                `id` int NOT NULL AUTO_INCREMENT,
                `key` text NOT NULL,
                `type` varchar(20) NOT NULL,
                `category` varchar(30) NOT NULL,
                `action` varchar(30) NOT NULL,
                `table_structure_only` int(1) NULL,
                `is_dir` int(1) NULL,
                PRIMARY KEY (`id`),
                INDEX `key` (`key`(191))
            ) ENGINE=InnoDB " . $cachecollation . ";");
    
        
        $table_name = $wpdb->base_prefix . 'wpss_activity_log';
        dbDelta("CREATE TABLE IF NOT EXISTS $table_name (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `type` varchar(50) NOT NULL,
                `log_data` text NOT NULL,
                `parent` tinyint(1) NOT NULL DEFAULT '0',
                `parent_id` bigint(20) NOT NULL,
                `is_reported` tinyint(1) NOT NULL DEFAULT '0',
                `report_id` varchar(50) NOT NULL,
                `action_id` text NOT NULL,
                `show_user` ENUM('1','0') NOT NULL DEFAULT '1',
                PRIMARY KEY (`id`),
                UNIQUE KEY `id` (`id`),
                INDEX `action_id` (`action_id`(191)),
                INDEX `show_user` (`show_user`)
              ) ENGINE=InnoDB  " . $cachecollation . ";");
    
        $table_name = $wpdb->base_prefix . 'wpss_backups';
        dbDelta("CREATE TABLE IF NOT EXISTS $table_name (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `backup_id` varchar(100) NOT NULL,
                `backup_name` text,
                `backup_type` char(1) NOT NULL COMMENT 'M = Manual, D = Daily Main Cycle , S- Sub Cycle',
                `files_count` int(11) NOT NULL,
                `memory_usage` text NOT NULL,
                `update_details` text DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `id` (`id`)
              ) ENGINE=InnoDB  " . $cachecollation . ";");
        $table_name = $wpdb->base_prefix . 'wpss_local_site_new_attachments';
        dbDelta("CREATE TABLE IF NOT EXISTS $table_name (
            `id` int NOT NULL AUTO_INCREMENT,
            `url` text NOT NULL,
            `name` text NOT NULL,
            `relative_file_path` text NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `url` (`url`(191))
        ) ENGINE=InnoDB " . $cachecollation . ";");
    
        //Ensure that there where no insert errors
        $errors = array();
    
        global $EZSQL_ERROR;
        if ($EZSQL_ERROR) {
            foreach ($EZSQL_ERROR as $error) {
                if (preg_match("/^CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}wpss_/", $error['query'])) {
                    $errors[] = $error['error_str'];
                }
            }
        }
    
    
        //Only set the DB version if there are no errors
        if (!empty($errors)) {

            wpss_log($errors,'-----------$errors----------------');

            return false;
        }
    
        //Only should execute on first time activation
        if (!$is_wpss_installed) {
            WPSS_Base_Factory::get('WPSS_App_Functions')->set_fresh_install_flags();
        }
    
        wpss_log(array(), "--------installing finished--------");
    }

    public function register_filters() {
        $text = __('Super Stage WP', 'wpss');
		add_menu_page($text, $text, 'activate_plugins', 'wpss-main-page', array($this, 'include_wpss_main_page'), plugins_url( 'images/wpss-icon.svg', __FILE__ ));

        $text = __('Settings', 'wpss');
        add_submenu_page('wpss-main-page', $text, $text, 'activate_plugins', 'wpss-settings-page', array($this, 'include_wpss_settings_page'), '');

    }

    public function include_wpss_main_page() {
        include_once(WPSS_PLUGIN_DIR . 'views/wpss-main-page.php');
    }
    
    public function include_wpss_settings_page() {
        include_once(WPSS_PLUGIN_DIR . 'views/wpss-settings.php');
    }

    public function include_global_js_vars() {
        $hotlink_live_images_wpss = WPSS_Factory::get('config')->get_option('load_images_from_live_site_settings');

        echo '<script>
            var HOTLINK_LIVE_IMAGES_WPSS = "' . $hotlink_live_images_wpss . '";
        </script>';
    }
    public function enque_js_files() {
        $this->include_global_js_vars();
        wp_enqueue_script('wpss-jquery-ui-custom-js', plugins_url('', __FILE__) . '/treeView/jquery-ui.custom.js', array(), WPSS_VERSION);
        wp_enqueue_script('wpss-fancytree-js', plugins_url('', __FILE__) . '/treeView/jquery.fancytree.js', array(), WPSS_VERSION);
        wp_enqueue_script('wpss-filetree-common-js', plugins_url('', __FILE__) . '/treeView/common.js', array(), WPSS_VERSION);
        wp_enqueue_style('wpss-fancytree-css', plugins_url('', __FILE__) . '/treeView/skin/ui.fancytree.css', array(), WPSS_VERSION);

        wp_enqueue_style('wpss-admin-css', plugins_url() . '/' . WPSS_TC_PLUGIN_NAME . '/views/admin.css', array(), WPSS_VERSION);
		wp_enqueue_script('wpss-admin-js', plugins_url() . '/' . WPSS_TC_PLUGIN_NAME . '/views/admin.js', array(), WPSS_VERSION);

        $this->add_nonce();
    }

    public function enque_frontend_js_files() {
		wp_enqueue_script('wpss-frontend-js', plugins_url() . '/' . WPSS_TC_PLUGIN_NAME . '/views/wpss-frontend.js', array(), WPSS_VERSION);

        $params = array(
			'ajax_nonce' => wp_create_nonce('wpss_nonce'),
			'admin_url'  => network_admin_url(),
		);
		wp_localize_script( 'wpss-frontend-js', 'wpss_ajax_object', $params );
    }

    public function add_nonce(){
		$params = array(
			'ajax_nonce' => wp_create_nonce('wpss_nonce'),
			'admin_url'  => network_admin_url(),
		);
		wp_localize_script( 'wpss-admin-js', 'wpss_ajax_object', $params );
	}
}
