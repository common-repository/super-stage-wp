<?php

class WPSS_Constants{
	public function __construct(){
	}

	public function init_live_plugin(){
		$this->path();
		$this->set_env();
		$this->general();
		$this->versions();
		$this->debug();
		$this->set_mode();
	}

	public function init_staging_plugin(){
		$this->path();
		$this->set_env();
		$this->general();
		$this->versions();
		$this->debug();
		$this->set_mode();
	}

	public function init_restore(){
		$this->path();
		$this->set_env();
		$this->general();
		$this->versions();
		$this->debug();
		$this->set_mode();
	}

	public function bridge_restore(){
		$this->set_env($type = 'bridge');
		$this->general();
		$this->versions();
		$this->debug();
		$this->set_mode();
	}

	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	public function set_env($type = false){
		$path = ($type === 'bridge') ? '' : WPSS_PLUGIN_DIR ;

		if (file_exists($path . 'wpss-env-parameters.php')){
			include_once ($path . 'wpss-env-parameters.php');
		}

		$this->define( 'WPSS_ENV', 'production' );
	}

	public function set_mode(){
		switch (WPSS_ENV) {
			case 'production':
				$this->production_mode();
				break;
			case 'staging':
				$this->staging_mode();
				break;
			case 'local':
			default:
				$this->development_mode();
		}
	}

	public function debug(){
		$this->define( 'WPSS_DEBUG', false );
	}

	public function versions(){
		$this->define( 'WPSS_VERSION', '1.0.1' );
		$this->define( 'WPSS_DATABASE_VERSION', '1.0' );
	}

	public function general(){
		// For php 8.1 mysql fatal errors
		if(function_exists('mysqli_report')){
			mysqli_report(MYSQLI_REPORT_OFF);
		}

		$this->define( 'WPSS_DEFAULT_CRON_TYPE', 'SCHEDULE');
		$this->define( 'WPSS_CHUNKED_UPLOAD_THREASHOLD', 5242880); //5 MB
		$this->define( 'WPSS_MIN_REQUIRED_STORAGE_SPACE', 5242880); //5 MB
		$this->define( 'WPSS_MINUMUM_PHP_VERSION', '5.2.16' );
		$this->define( 'WPSS_NO_ACTIVITY_WAIT_TIME', 60); //5 mins to allow for socket timeouts and long uploads
		$this->define( 'WPSS_PLUGIN_PREFIX', 'wpss' );
		$this->define( 'WPSS_TC_PLUGIN_NAME', 'super-stage-wp' );
		$this->define( 'WPSS_DEBUG_SIMPLE', false );
		$this->define( 'WPSS_TIMEOUT', 23 );
		$this->define( 'WPSS_HASH_FILE_LIMIT', 1024 * 1024 * 15); //15 MB
		$this->define( 'WPSS_STAGING_COPY_SIZE', 1024 * 1024 * 2); //2 MB
		$this->define( 'HASH_CHUNK_LIMIT', 1024 * 128); // 128  KB
		$this->define( 'WPSS_CLOUD_DIR_NAME', 'super-stage-wp' );
		$this->define( 'WPSS_STAGING_PLUGIN_DIR_NAME', 'super-stage-wp-staging' );
		$this->define( 'WPSS_RESTORE_FILES_NOT_WRITABLE_COUNT', 15 );
		$this->define( 'WPSS_DEFAULT_BACKUP_SLOT', 'daily'); //subject to change
		$this->define( 'WPSS_DEFAULT_SCHEDULE_TIME_STR', '12:00 am' );
		$this->define( 'WPSS_NOTIFY_ERRORS_THRESHOLD', 10 );
		$this->define( 'WPSS_LOCAL_AUTO_BACKUP', true );
		$this->define( 'WPSS_AUTO_BACKUP', false );
		$this->define( 'WPSS_DEBUG_PRINT_ALL', true );
		$this->define( 'WPSS_DONT_BACKUP_META', true); // remove to take meta on every backup
		$this->define( 'WPSS_FALLBACK_REVISION_LIMIT_DAYS', 30 );
		$this->define( 'WPSS_DEFAULT_MAX_REVISION_LIMIT', 30 ); //30 days
		$this->define( 'WPSS_GDRIVE_TOKEN_ON_INIT_LIMIT', 2); //total connected sites limit for showing google drive
		$this->define( 'WPSS_ACTIVITY_LOG_LAZY_LOAD_LIMIT', 25);
		$this->define( 'WPSS_RESTORE_ADDING_FILES_LIMIT', 30);
		$this->define( 'WPSS_STAGING_DEFAULT_DEEP_LINK_REPLACE_LIMIT', 5000);
		$this->define( 'WPSS_CHECK_CURRENT_STATE_FILE_LIMIT', 500);
		$this->define( 'WPSS_STAGING_DEFAULT_FILE_COPY_LIMIT', 200);
		$this->define( 'WPSS_STAGING_DEFAULT_COPY_DB_ROWS_LIMIT', 100000);
		$this->define( 'WPSS_KEEP_MAX_BACKUP_DAYS_LIMIT', 366);
		$this->define( 'WPSS_REAL_TIME_BACKUP_MAX_PHP_DUMP_DB_SIZE', 209715200); //200 MB
		$this->define( 'WPSS_AUTO_BACKUP_CHECK_TIME_TOLERENCE', 600); // 10 mins (60 * 10)
		$this->define( 'WPSS_DEFAULT_DB_ROWS_BACKUP_LIMIT', 300); // 10 mins (60 * 10)
		$this->define( 'WPSS_DEFAULT_CURL_CONTENT_TYPE','Content-Type: application/x-www-form-urlencoded'); // some servers outbound requests are got blocked due to without content type
		$this->define( 'WPSS_MAX_REQUEST_PROGRESS_WAIT_TIME', 180); // 3 mins (3 * 60)
		$this->define( 'WPSS_GDRIVE_IPV4_ONLY', false);
		$this->define( 'WPSS_S3_VERIFICATION_FILE', 'wpss-verify.txt');
		$this->define( 'WPSS_CRYPT_BUFFER_SIZE', 2097152);

		//below PHP 5.4
		$this->define( 'JSON_UNESCAPED_SLASHES', 64);
		$this->define( 'JSON_UNESCAPED_UNICODE', 256);


		//Create backups in backwards for testing
		$this->define( 'WPSS_BACKWARD_BACKUPS_CREATION', false);
		$this->define( 'WPSS_BACKWARD_BACKUPS_CREATION_DAYS', 30);

		if (!defined('WPSS_BRIDGE')) {
			$this->define( 'WPSS_DROPBOX_WP_REDIRECT_URL', urlencode(base64_encode(network_admin_url() . 'admin.php?page=super-stage-wp&cloud_auth_action=dropbox&env='.WPSS_ENV))); //state wp redirect url for dropbox
		}

		$this->define( 'ADVANCED_SECONDS_FOR_TIME_CALCULATION', 300);
		$this->define( 'TRIGGER_PREVENT_TABLES_COUNT_WPSS', 180);  //this is dummy, set in js file
		$this->define( 'TRIGGER_TABLE_CRON_DELETE_FREQUENCY', 86400 + 10800); //1 day 3 hours
		$this->define( 'ITERATOR_FILES_COUNT_CHECK', 10000);
		$this->define( 'SHOW_QUERY_RECORDER_TABLE_SIZE_EXCEED_WARNING', true);
	}

	public function path(){

		$this->define( 'WPSS_ABSPATH', wp_normalize_path( ABSPATH ) );
		$this->define( 'WPSS_RELATIVE_ABSPATH', '/' );
		$this->define( 'WPSS_WP_CONTENT_DIR', wp_normalize_path( WP_CONTENT_DIR ) );
		$this->define( 'WPSS_WP_CONTENT_BASENAME', basename( WPSS_WP_CONTENT_DIR ) );
		$this->define( 'WPSS_RELATIVE_WP_CONTENT_DIR', '/' . WPSS_WP_CONTENT_BASENAME );

		//Before modifying these, think about existing users
		$this->define( 'WPSS_TEMP_DIR_BASENAME', 'wpSS' );
		$this->define( 'WPSS_REALTIME_DIR_BASENAME', 'wpss_realtime_tmp' );

		if (defined('WPSS_BRIDGE')) {
			$this->define( 'WPSS_EXTENSIONS_DIR', wp_normalize_path(BRIDGE_NAME_WPSS . '/Classes/Extension/') );
			$this->define( 'WPSS_PLUGIN_DIR', '' );
			$this->define( 'WPSS_RELATIVE_PLUGIN_DIR', '' );
			return ;
		}

		$this->define( 'WPSS_EXTENSIONS_DIR', wp_normalize_path(plugin_dir_path(__FILE__) . 'Classes/Extension/' ));
		$this->define( 'WPSS_CLASSES_DIR', wp_normalize_path(plugin_dir_path(__FILE__) . '') );
		$this->define( 'WPSS_PRO_DIR', wp_normalize_path(plugin_dir_path(__FILE__) . 'Pro/') );

		$plugin_dir_path = wp_normalize_path( plugin_dir_path( __FILE__ ) );
		$this->define( 'WPSS_RELATIVE_PLUGIN_DIR', str_replace(WPSS_ABSPATH, WPSS_RELATIVE_ABSPATH, $plugin_dir_path ) );
		$this->define( 'WPSS_PLUGIN_DIR', $plugin_dir_path );

		$uploads_meta = wp_upload_dir();
		$basedir_path = wp_normalize_path( $uploads_meta['basedir'] );
		$this->define( 'WPSS_RELATIVE_UPLOADS_DIR', str_replace(WPSS_WP_CONTENT_DIR . '/', WPSS_RELATIVE_ABSPATH, $basedir_path ) );
		$this->define( 'WPSS_UPLOADS_DIR', $basedir_path);

	}

	public function production_mode(){
	}

	public function staging_mode(){
	}

	public function development_mode(){
	}
}
