<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPSS_Stage_Common{
	private $fs,
			$config,
			$processed_db,
			$staging_id,
			$logger;

	public function __construct(){
		$this->config = WPSS_Factory::get('config');
		$this->processed_db = new WPSS_Processed_iterator();
		$this->logger = WPSS_Factory::get('logger');
	}

	public function init_fs(){
		global $wp_filesystem;
		if (!$wp_filesystem) {
			initiate_filesystem_wpss();
			if (empty($wp_filesystem)) {
				// $this->app_functions->die_with_json_encode(array("status" => "error", 'msg' => 'Could not initiate File system'));
				return false;
			}
		}
		$this->fs = $wp_filesystem;
		return $wp_filesystem;
	}

	public function init_db(){
		global $wpdb;
		$this->wpdb = $wpdb;
		return $wpdb;
	}

	public function get_table_data($table){
		$table_data = $this->processed_db->get_table($table);

		if ($table_data) {
			return array('offset' => $table_data->offset, 'is_new' => false);
		}

		return array('offset' => 0, 'is_new' => true);
	}

	public function clone_table_structure($table, $new_table){
		$this->wpdb->query("DROP TABLE IF EXISTS `$new_table`");

		$sql = "CREATE TABLE `$new_table` LIKE `$table`";

		$is_cloned = $this->wpdb->query($sql);

		if ($is_cloned === false) {
			wpss_log($sql,'-----------$sql----------------');
			$this->log(__('Creating table ' . $table . ' has been failed', 'wpss') , 'staging', $this->staging_id);
			wpss_log('Creating table ' . $this->wpdb->last_error . ' has been failed.', '--------Failed-------------');
			return false;
		}

		$this->log(__("Created table " . $table, 'wpss'), 'staging', $this->staging_id);
		return true;
	}

	public function update_staging_tables_clone_new_status($new_table, $status){
		$all_tables_status_arr = $this->config->get_option('staging_tables_clone_new_status');
		if(empty($all_tables_status_arr)){
			$all_tables_status_arr = [];
		} else {
			$all_tables_status_arr = json_decode($all_tables_status_arr, true);
		}

		$all_tables_status_arr[$new_table] = $status;
		$this->config->set_option('staging_tables_clone_new_status', json_encode($all_tables_status_arr, true));
	}

	public function get_staging_tables_clone_new_status($new_table = null){
		$all_tables_status_arr = $this->config->get_option('staging_tables_clone_new_status', true);
		if(empty($all_tables_status_arr)){
			$all_tables_status_arr = [];
		} else {
			$all_tables_status_arr = json_decode($all_tables_status_arr, true);
		}

		if(!empty($new_table)){
			if(!empty($all_tables_status_arr[$new_table])){

				return $all_tables_status_arr[$new_table];
			}

			return false;
		}

		return $all_tables_status_arr;
	}

	public function modify_staging_clone_table_rows_limit(){
		$internal_staging_db_rows_copy_limit = $this->config->get_option('internal_staging_db_rows_copy_limit', true);
		if($internal_staging_db_rows_copy_limit > 99999){
			$new_internal_staging_db_rows_copy_limit = 50000;
		} elseif($internal_staging_db_rows_copy_limit > 49999){
			$new_internal_staging_db_rows_copy_limit = 10000;
		} elseif($internal_staging_db_rows_copy_limit > 9999){
			$new_internal_staging_db_rows_copy_limit = 5000;
		} elseif($internal_staging_db_rows_copy_limit > 4999){
			$new_internal_staging_db_rows_copy_limit = 1000;
		}

		if(!empty($new_internal_staging_db_rows_copy_limit)){

			wpss_log($new_internal_staging_db_rows_copy_limit, "---------modified---internal_staging_db_rows_copy_limit--------");

			$this->config->set_option('internal_staging_db_rows_copy_limit', $new_internal_staging_db_rows_copy_limit);
		}
	}
	public function clone_table_content($table, $new_table, $limit, $offset){
		while(true){
			$inserted_rows = 0;
			// exit;

			wpss_manual_debug('', 'during_clone_table_staging_common_' .$table, 100);
			$this_table_old_clone_status = $this->get_staging_tables_clone_new_status($new_table);

			wpss_log($this_table_old_clone_status, "---------this_table_old_clone_status-------$new_table-----");
			wpss_log($limit, "---------staging db rows limit-------$new_table-----");

			if(empty($this_table_old_clone_status)){
				$this->update_staging_tables_clone_new_status($new_table, 'STARTED');
			} elseif($this_table_old_clone_status == 'STARTED' || $this_table_old_clone_status == 'TRUNCATING'){
				$offset = 0;
				$this->processed_db->update_iterator($table, $offset);

				$this->update_staging_tables_clone_new_status($new_table, 'TRUNCATING');

				wpss_log($new_table, '---------starting truncating table------------');

				$q_result = $this->wpdb->query(
					"TRUNCATE table `$new_table`;"
				);
				
				if($q_result === false){
					wpss_log($this->wpdb->last_error, "-------TRUNCATE table----- $new_table---error---------");
				}

				sleep(3);

				$new_table_row_count = $this->wpdb->get_var(
					"COUNT (*) FROM `$new_table`;"
				);

				if($new_table_row_count !== false && $new_table_row_count > 0){
					sleep(10);
					wpss_die_with_json_encode( array('status' => 'continue', 'msg' => 'Cloning ' . $table . '(' . $offset . ')' , 'percentage' => 20) );
				} else {
					$this->modify_staging_clone_table_rows_limit();
					$this->update_staging_tables_clone_new_status($new_table, 'STARTED');

					$limit = $this->config->get_option('internal_staging_db_rows_copy_limit', true);
					// wpss_die_with_json_encode( array('status' => 'continue', 'msg' => 'Cloning ' . $table . '(' . $offset . ')' , 'percentage' => 20) );
				}
			}

			// $offset = 0;

			$inserted_rows = $this->wpdb->query(
				"insert `$new_table` select * from `$table` limit $offset, $limit"
			);

			// if($table == 'wp_big_records' & $inserted_rows > 90000){
			// 	// sleep(40);
			// }

			wpss_log($inserted_rows, "---------inserted_rows-------$table-----");

			if ($inserted_rows !== false) {
				$this_table_old_clone_status = $this->get_staging_tables_clone_new_status($new_table);
				if(!empty($this_table_old_clone_status) && $this_table_old_clone_status == 'TRUNCATING'){
					$offset = 0;
					$this->processed_db->update_iterator($table, $offset);
				} else {
					$this->update_staging_tables_clone_new_status($new_table, 'COMPLETED');

					if ($offset != 0) {
						$this->log(__( 'Copy database table: ' . $table . ' DB rows: ' . $offset, 'wpss') , 'staging', $this->staging_id); //create staging id
					}
					$offset = $offset + $inserted_rows;
					if ($inserted_rows < $limit) {
						$this->processed_db->update_iterator($table, -1); //Done
						if(is_wpss_timeout_cut()){
							wpss_die_with_json_encode( array('status' => 'continue', 'msg' => 'Cloning ' . $table . '(' . $offset . ')' , 'percentage' => 20) );
						}
						break;
					}
				}
				if(is_wpss_timeout_cut()){
					$this->processed_db->update_iterator($table, $offset);
					wpss_die_with_json_encode( array('status' => 'continue', 'msg' => 'Cloning ' . $table . '(' . $offset . ')' , 'percentage' => 20) );
				}
			} else {
				$this->processed_db->update_iterator($table, -1); //Done
				wpss_log('Error: '.$this->wpdb->error.'Table ' . $new_table . ' has been created, but inserting rows failed! Rows will be skipped. Offset: ' . $offset , '--------Failed-------------');
				break;
				$this->log(__('Error: '.$this->wpdb->error.'inserting rows failed! Rows will be skipped. Offset: ' . $offset, 'wpss') , 'staging', $this->staging_id);
			}
		}
	}

	public function init_staging_id(){
		$this->staging_id = $this->config->get_option('staging_id',true);
		if (empty($this->staging_id)) {
			$this->config->set_option('staging_id', time());
			$this->staging_id = $this->config->get_option('staging_id');
		}

		return $this->staging_id;
	}

	public function log($msg, $name, $id){
		// return false;
		$this->logger->log($msg, $name, $id);
	}

}
