<?php

if (!isset($_REQUEST)) {
	$this->send_response(array('error' => "Request is missing"));
}
$bridge = new WPSS_Bridge($_REQUEST);
$bridge->init();


class WPSS_Bridge{
	protected $params;
	protected $secret_code_start;
	protected $secret_code_end;
	protected $options_obj;
	protected $staging_abspath;
	protected $meta_file_name;

	public function __construct($params){
		$this->params = $params;
		$this->secret_code_start = '<WPSSHEADER>';
		$this->secret_code_end = '</ENDWPSSHEADER>';
		$this->staging_abspath = $this->get_staging_abspath();
		$this->meta_file_name = $this->staging_abspath.'wp-tcapsule-bridge/wordpress-db_meta_data.sql';
	}

	public function get_staging_abspath(){
		return dirname(dirname(__FILE__)). '/';
	}

	public function init(){
		if (!isset($this->params['data'])) {
			$this->send_response(array('error' => "Request data is missing"));
		}
		$this->decode_request_data();
		$this->find_action();
	}

	public function decode_request_data(){
		$this->params = unserialize(base64_decode($this->params['data']));
	}

	public function find_action(){
		if (!isset($this->params['action'])){
			$this->send_response(array('error' => "could not find action"));
		}
		$this->define_constants();
		switch ($this->params['action']) {
			case 'update_in_staging':
				break;
			default:
				$this->send_response(array('error' => "action is not found"));
		}
	}

	public function define_constants(){
		if(!defined('WP_DEBUG')){
			define('WP_DEBUG', false);
		}
		if(!defined('WP_DEBUG_DISPLAY')){
			define('WP_DEBUG_DISPLAY', false);
		}
	}

	public function send_response($data){
		$response_data = $this->secret_code_start . base64_encode(serialize($data)) . $this->secret_code_end;
		die($response_data);
	}

	private function include_wp_config(){
		@include_once $this->staging_abspath.'wp-config.php';
		@include_once $this->staging_abspath.'wp-admin/includes/file.php';
	}



	private function initiate_filesystem_wpss() {
		$creds = request_filesystem_credentials("", "", false, false, null);
		if (false === $creds) {
			return false;
		}

		if (!WP_Filesystem($creds)) {
			return false;
		}
	}
}
