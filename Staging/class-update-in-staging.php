<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WPSS_Update_In_Staging{

	private $config;
	private	$staging_common;
	private	$logger;
	private	$staging_id;
	private	$app_functions;

	public function __construct(){
		$this->config = WPSS_Factory::get('config');
		$this->staging_common = new WPSS_Stage_Common();
		$this->logger = WPSS_Factory::get('logger');
		$this->app_functions = WPSS_Base_Factory::get('WPSS_App_Functions');
		$this->init_staging_id();
	}

	private function init_staging_id(){
		$this->staging_id = $this->staging_common->init_staging_id();
	}

	
}
