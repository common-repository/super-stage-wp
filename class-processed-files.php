<?php
/**
* A class with functions the perform a backup of WordPress
*
* @copyright Copyright (C) 2011-2014 Awesoft Pty. Ltd. All rights reserved.
* @author Michael De Wildt (http://www.mikeyd.com.au/)
* @license This program is free software; you can redistribute it and/or modify
*          it under the terms of the GNU General Public License as published by
*          the Free Software Foundation; either version 2 of the License, or
*          (at your option) any later version.
*
*          This program is distributed in the hope that it will be useful,
*          but WITHOUT ANY WARRANTY; without even the implied warranty of
*          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*          GNU General Public License for more details.
*
*          You should have received a copy of the GNU General Public License
*          along with this program; if not, write to the Free Software
*          Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110, USA.
*/

class WPSS_Processed_Files extends WPSS_Processed_Base {
	const BRIDGE_RESTORE_POINTS_COUNT = 20;
	private $is_multisite             = false;
	private $multisite_config      = array();
	private $is_current_blog_is_child      = false;

	protected function getTableName() {
		return 'files';
	}

	protected function getProcessType() {
		return 'files';
	}

	protected function getRestoreTableName() {
		return 'restored_files';
	}

	protected function getRevisionId() {
		return 'revision_id';
	}

	protected function getId() {
		return 'file';
	}

	protected function getFileId() {
		return 'file_id';
	}

	protected function getUploadMtime() {
		return 'mtime_during_upload';
	}

	public function get_file_count() {
		return $this->db->get_var("SELECT COUNT(*) FROM {$this->db->base_prefix}wpss_processed_{$this->getTableName()} WHERE 	download_status = 'done'");
	}

	public function get_file($file_name) {

		$file_name = wpss_remove_fullpath($file_name);

		$prepared_query = $this->db->prepare(" SELECT * FROM {$this->db->base_prefix}wpss_processed_files WHERE backupID = %s AND file = %s ", wpss_get_cookie('backupID'), $file_name);

		$result = $this->db->get_results($prepared_query);

		if (empty($result) && WPSS_BACKWARD_DB_SEARCH) {
			$prepared_query = $this->db->prepare(" SELECT * FROM {$this->db->base_prefix}wpss_processed_files WHERE backupID = %s AND file = %s ", wpss_get_cookie('backupID'), $file_name);
			$result = $this->db->get_results($prepared_query);
		}

		if (!empty($result)) {
			return $result[0];
		}

		return array();
	}

	public function is_file_modified_from_before_backup($file_name, $file_size, $file_hash){
		if(wpss_is_always_include_file($file_name) ){
			return true;
		}

		$file_name = wpss_remove_fullpath($file_name);

		$result = $this->db->get_results(
					$this->db->prepare(
						"SELECT mtime_during_upload, file_hash, revision_id FROM {$this->db->base_prefix}wpss_processed_files WHERE filepath_md5 = %s ORDER BY file_id DESC LIMIT 1", md5($file_name)
					)
				);

		// wpss_log($result, '--------md5 $result--------');


		if (empty($result) && WPSS_BACKWARD_DB_SEARCH) {
			$result = $this->db->get_results( $this->db->prepare( "SELECT mtime_during_upload, file_hash FROM {$this->db->base_prefix}wpss_processed_files WHERE uploaded_file_size = %d  AND file = %s ORDER BY file_id DESC LIMIT 1", $file_size, $file_name ) );
		}

		// wpss_log($result, '--------$result once again--------');

		if(empty($result)){
			// wpss_log(array(), '--------Not backup before--------');
			return true;
		}

		if(empty($result[0]->revision_id)){
			// wpss_log(array(), '--------Backup prolly failed before--------');
			return true;
		}

		$file_name = wpss_add_fullpath($file_name);

		if(filemtime($file_name) == $result[0]->mtime_during_upload){
			// wpss_log(array(), '--------mtime same--------');
			return false;
		}

		return $this->is_file_modified_from_before_backup_by_hash($file_name, $file_hash, $result[0]->file_hash);
	}

	public function is_file_modified_from_before_backup_by_hash($file_name, $current_file_hash, $prev_file_hash){

		$is_hash_required = wpss_is_hash_required($file_name);

		if (!$is_hash_required) {
			return true;
		}

		if (empty($prev_file_hash)) {
			return true;
		}

		if($prev_file_hash == $current_file_hash){
			return false;
		}

		return true;
	}

	public function file_complete($file) {
		$this->update_file($file, 0, 0);
	}

	public function update_file($file, $upload_id, $offset, $s3_part_number = 1, $s3_parts_array = array()) {

		$relative_path = wpss_remove_fullpath($file);

		$may_be_stored_file_obj = $this->get_file($file);
		if ($may_be_stored_file_obj) {
			$may_be_stored_file_id = $may_be_stored_file_obj->file_id;
		}

		if (!empty($may_be_stored_file_obj) && !empty($may_be_stored_file_id)) {
			$upsert_array = array(
				'file'                => $relative_path,
				'uploadid'            => $upload_id,
				'offset'              => $offset,
				'backupID'            => wpss_get_cookie('backupID'), //get the backup ID from cookie
				'file_id'             => $may_be_stored_file_id,
				'mtime_during_upload' => filemtime($file),
				'uploaded_file_size'  => filesize($file),
				's3_part_number'      => $s3_part_number,
				's3_parts_array'      => serialize($s3_parts_array),
			);
		} else {
			$upsert_array = array(
				'file'                => $relative_path,
				'uploadid'            => $upload_id,
				'offset'              => $offset,
				'backupID'            => wpss_get_cookie('backupID'),
				'mtime_during_upload' => filemtime($file),
				's3_part_number'      => $s3_part_number,
				's3_parts_array'      => serialize($s3_parts_array),
			);
		}
		$this->upsert($upsert_array);
	}

	public function add_files($new_files) {
		foreach ($new_files as $file) {
			process_parent_dirs_wpss(array(
				'file'                => wpss_remove_fullpath($file['filename']),
				'uploadid'            => null,
				'offset'              => 0,
				'backupID'            => wpss_get_cookie('backupID'),
				'revision_number'     => $file['revision_number'],
				'revision_id'         => $file['revision_id'],
				'mtime_during_upload' => $file['mtime_during_upload'],
				'uploaded_file_size'  => $file['uploaded_file_size'],
				'g_file_id'           => $file['g_file_id'],
				'cloud_type'          => DEFAULT_REPO,
				'file_hash'           => $file['file_hash'],
			), 'process_files');
		}

		return $this;
	}

	public function base_upsert($data){
		$this->upsert($data);
	}

	public function get_this_backups_html($this_backup_ids, $specific_dir = null, $type = null, $treeRecursiveCount = 0) {
		$old_backups = array();

		if(WPSS_BACKWARD_DB_SEARCH){
			$old_backups = $this->get_this_backups($this_backup_ids, rtrim(WPSS_ABSPATH, '/') );
		}

		$new_backups = $this->get_this_backups($this_backup_ids, $specific_dir);

		$backup_data = ( empty( $old_backups ) ) ? $new_backups : ( $new_backups + $old_backups );

		return $this->convert_backups_to_html($backup_data, $type, $treeRecursiveCount);
	}

	private function is_file_hide_needed_for_multisite($file, $is_dir){
		if( $this->is_multisite 
			&& $this->is_current_blog_is_child
			&& $this->multisite_config['upload_dir'] 
			){

			if( !$is_dir 
				&& stripos($file, $this->multisite_config['upload_dir']) === false){

				return true;
			} 

			if( $is_dir ) {
				if( stripos($this->multisite_config['upload_dir'], $file) !== false ){

					return false;
				} else {
					if(stripos($file, $this->multisite_config['upload_dir']) === false){
						return true;
					}
				}
			}
		}

		return false;
	}

	private function reduce_files_count(&$files_count, $backupid){
		$count = $this->do_not_show_dirs($backupid, basename( WPSS_RELATIVE_WP_CONTENT_DIR ), $return_count = true);
		$files_count -= $count;
		$files_count = $files_count < 0 ? 0 : $files_count;
	}

	private function get_db_backups_by_path($path, $key, $realtime = false){
		$path = wp_normalize_path($path);
		return $this->get_db_backups($key, $path, $realtime);
	}

	private function should_hide_upload_dir($sublings_array){
		if( $sublings_array['file_name'] === WPSS_RELATIVE_WP_CONTENT_DIR . '/uploads' ){
			return true;
		}

		if( $sublings_array['file_name'] === WPSS_WP_CONTENT_DIR . '/uploads') {
			return true;
		}

		if($sublings_array['file_name'] === WPSS_RELATIVE_WP_CONTENT_DIR . '/' . WPSS_REALTIME_DIR_BASENAME ) {
			return true;
		}

		if($sublings_array['file_name'] === WPSS_WP_CONTENT_DIR . '/' . WPSS_REALTIME_DIR_BASENAME) {
			return true;
		}

		return false;
	}

	public function get_tree_div_recursive($explodedTreeArray, $treeRecursiveCount = 0, $total_sub_content = '', $is_backup_present = '') {
		foreach ($explodedTreeArray as $top_tree_name => $sublings_array) {

			if ( $this->should_hide_upload_dir($sublings_array) ) {
				if($this->do_not_show_dirs($sublings_array['backup_id'], WPSS_RELATIVE_WP_CONTENT_DIR . '/uploads' ) ){
					continue;
				}
			}

			if (!is_array($sublings_array)) {
				continue;
			}

			if($sublings_array['is_dir'] == 1){

				$display =  $top_tree_name == WPSS_REALTIME_DIR_BASENAME || $top_tree_name == WPSS_TEMP_DIR_BASENAME || $top_tree_name == 'wpss_meta' ? 'display:none;' : '' ;
				if ( ( $top_tree_name == basename(WPSS_RELATIVE_WP_CONTENT_DIR) || $top_tree_name == basename(WPSS_WP_CONTENT_DIR) ) ) {
					if($this->do_not_show_dirs($sublings_array['backup_id'], basename( WPSS_RELATIVE_WP_CONTENT_DIR ) ) ){
						continue;
					}
				}

				if( $this->is_file_hide_needed_for_multisite($sublings_array['file_name'], $sublings_array['is_dir']) ){

					continue;
				}

				$total_sub_content .= '<li class="sl' . $treeRecursiveCount . ' sub_tree_class" recursive_count = "'.$treeRecursiveCount.'" style="margin-left:' . (($treeRecursiveCount * 50) - $treeRecursiveCount) . 'px;' . $display . '" ><div class="folder close" ' . $this->convert_arr_string($sublings_array) . '></div><div class="file_path" style="width:70%; word-break:break-all;">' . $top_tree_name . '</div></li>';

			} else {
				$is_sql_class = $is_sql_li = '';

				if ((strpos($top_tree_name, 'wpss-secret') !== false) || (strpos($top_tree_name, 'wpss_saved_queries') !== false)) {
					$is_sql_class = "sql_file";
					$is_sql_li = "sql_file_li";
				}

				if( !$is_sql_class 
					&& $this->is_file_hide_needed_for_multisite($sublings_array['file_name'], $sublings_array['is_dir']) ){
					
					continue;
				}

				$total_sub_content .= '<div class="this_leaf_node ' . $is_sql_class . ' leaf_' . $treeRecursiveCount . '" recursive_count = "'.$treeRecursiveCount.'"><li class="sl' . $treeRecursiveCount . ' ' . $is_sql_li . '" style="margin-left:' . (($treeRecursiveCount * 50) - $treeRecursiveCount) . 'px; word-break: break-all;"><div class="file_path" ' . $this->convert_arr_string($sublings_array) . ' style="width:70%; word-break:break-all;">' . $top_tree_name . '</div></li></div>';
			}
		}

		return '<div class="this_parent_node" recursive_count = "'.$treeRecursiveCount.'">' . $total_sub_content . '</div>';
	}

	public function convert_arr_string($arr){
		$str = ' g_file_id="' . $arr['g_file_id'] . '" file_name="' . $arr['file_name'] . '" file_size="' . $arr['file_size'] . '" revision_id="' . $arr['revision_id'] . '" mod_time="' . $arr['mod_time'] .'" is_dir="' . $arr['is_dir'] . '" backup_id="' . $arr['backup_id'] . '" parent_dir="' . $arr['parent_dir'] . '" '; //am appending file_size,revision_id,mod_time
		return $str;
	}

	public function prapare_tree_like_array($this_file_name_array) {
		$stripped_file_name_array = array();

		if (empty($this_file_name_array)) {
			return $stripped_file_name_array;
		}

		foreach ($this_file_name_array as $k => $v) {
			$this_removed_abs_file_name = wp_normalize_path($v->file);

			if ($this->skip_this_folder($this_removed_abs_file_name)) {
				continue;
			}

			$stripped_file_name_array[basename($this_removed_abs_file_name)] = array (
											'backup_id'   => $v->backupID,
											'g_file_id'   => $v->g_file_id,
											'file_name'   => $v->file,
											'file_size'   => $v->uploaded_file_size,
											'revision_id' => $v->revision_id,
											'mod_time'    => $v->mtime_during_upload,
											'parent_dir'  => $v->parent_dir,
											'is_dir'      => $v->is_dir
										);

		}
		return $stripped_file_name_array;
	}

	private function skip_this_folder($file){

		if ($this->db->base_prefix === $this->db->prefix) {
			return false;
		}

		if (!$this->is_multisite) {
			return false;
		}

		if (strstr($file, $this->multisite_config['parent_upload_dir']) !== false && $file !== $this->multisite_config['parent_upload_dir'] && strstr($file, $this->multisite_config['upload_dir']) === false) {
			return true;
		}

		return false;
	}

	private function set_multisite_config(){
		global $wpdb;

		if(!is_multisite()){
			return $this->is_multisite = false;
		}

		$this->is_multisite = true;
		$upload_dir = wpss_get_upload_dir();
		$this->multisite_config['upload_dir'] = $upload_dir;
		$this->multisite_config['parent_upload_dir'] = dirname($upload_dir);

		$this->is_current_blog_is_child = ($wpdb->base_prefix == $wpdb->prefix) ? false : true;
	}

	public function explodeTree($array, $delimiter = '_', $baseval = false) {
		if (!is_array($array)) {
			return false;
		}

		$splitRE = '/' . preg_quote($delimiter, '/') . '/';
		$returnArr = array();
		foreach ($array as $key => $val) {
			// Get parent parts and the current leaf
			$parts = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
			$leafPart = array_pop($parts);

			// Build parent structure
			// Might be slow for really deep and large structures
			$parentArr = &$returnArr;
			foreach ($parts as $part) {
				if (!isset($parentArr[$part])) {
					$parentArr[$part] = array();
				} elseif (!is_array($parentArr[$part])) {
					if ($baseval) {
						$parentArr[$part] = array('__base_val' => $parentArr[$part]);
					} else {
						$parentArr[$part] = array();
					}
				}
				$parentArr = &$parentArr[$part];
			}

			// Add the final part to the structure
			if (empty($parentArr[$leafPart])) {
				$parentArr[$leafPart] = $val;
			} elseif ($baseval && is_array($parentArr[$leafPart])) {
				$parentArr[$leafPart]['__base_val'] = $val;
			}
		}
		return $returnArr;
	}

	public function sort_dir_n_files($backup_files){

		if (empty($backup_files) || !array($backup_files)) {
			return $backup_files;
		}

		$dirs = $files =  array();

		foreach ($backup_files as $file) {

			if ($file->file === WPSS_RELATIVE_ABSPATH) {
				continue;
			}

			if ($file->is_dir == 1) {
				$dirs[] = $file;
			} else {
				$files[] = $file;
			}
		}

		$sorted_result = array_merge($dirs, $files);

		return $sorted_result;
	}

	public function modify_schedule_backup_time(&$time){
		$this_day = date("Y-m-d H:i a", $time);
		$hours = date('H', $time);
		$minutes = date('i', $time);
		$meridian = date('a', $time);
		if ($meridian === 'pm') {
			if ($hours == '23') {
				if ($minutes >= 55) {
					$add_remaining = (60 - $minutes) * 60;
					$time = $time + $add_remaining;
				}
			}
		}
	}

	public function get_bridge_html($data){


		$html = '<div class="show_restores container"><div class="pu_title">Last '.$data['total_backup_points'].' restore points</div> <div class="row"> <ul class="bu_list_ul col-sm-12"><div id="migration_settings"></div>';

		if (empty($data) || !is_array($data)) {
			return false;
		}

		$added_latest_point = false;

		foreach ($data as $key => $backups) {
			if (empty($backups) || !is_array($backups)) {
				continue;
			}

			$html .= "<li><span>".$backups['time']."</span><span>".count($backups['backup_details'])." restore points</span><a class='btn_wpss btn btn-primary wpss-custom-btn show_restore_points' style='top:8px'>SHOW RESTORE POINT</a>";

			foreach ($backups['backup_details'] as $key => $backup) {
				if ($added_latest_point === false) {
					$html .= $this->add_latest_restore_point_in_bridge($backup);
					$added_latest_point = true;
				}

				$html .= "<div style='display:none;' class='rp'><span>".$backup['backup_time']."</span><a class='btn_wpss btn btn-primary wpss-custom-btn bridge_restore_now_wpss' backup_id = '".$backup['backup_id']."' style='top: 0px'>RESTORE SITE TO THIS POINT</a></div>";
			}

			$html .= "</li>";
		}

		$html .= '</ul></div></div><div style="display:none" class="restore_process"><div id="TB_ajaxContent"><div class="pu_title">Restoring your website</div><div class="wcard progress_reverse" style="height:60px; padding:0;"><div class="progress_bar" style="width: 0%;"></div>  <div class="progress_cont">Preparing files to restore...</div></div><div style="padding: 10px; text-align: center;">Note: Please do not close this tab until restore completes.</div><div id="resume_restore_wpss" style="display: none;"><a class="btn_wpss">Resume</a></div></div></div>';
		return $html;
	}

	private function add_latest_restore_point_in_bridge($backup){
		return '';
		if ($this->config->get_option('backup_slot') === 'daily') {
			return '';
		}

		return "<div style='display:none;' class='rp'><span>Latest Point</span><a class='btn_wpss btn btn-primary wpss-custom-btn bridge_restore_now_wpss' id='wpss_restore_latest_point'backup_id = '" . $backup['backup_id'] . "' style='top: 0px'>RESTORE SITE TO THIS POINT</a></div>";
	}

	public function get_overall_tables(){

		$tables = $this->get_all_tables($override_meta = true);

		if (wpss_is_meta_data_backup()) {
			$meta_tables = WPSS_Base_Factory::get('WPSS_App_Functions')->get_meta_backup_tables();
			$tables = array_merge($tables, $meta_tables);
		}

		$count = 0;

		foreach ($tables as $table) {
			if (WPSS_Base_Factory::get('WPSS_ExcludeOption')->is_excluded_table($table) !== 'table_excluded') {
				$count ++;
			}
		}

		return $count;
	}

	public function do_not_show_dirs($backup_id, $hide_folder, $return_count = false){

		$files = $this->db->get_results( "SELECT file FROM {$this->db->base_prefix}wpss_processed_files WHERE is_dir = '0' AND backupID = " . $backup_id . " AND file LIKE '%" . $hide_folder . "%'" );

		if (empty($files)) {
			return false;
		}

		$counter = 0;

		$needles = array(
			str_replace('/backups', '', $this->config->get_backup_dir()),
		) ;

		$needles = apply_filters('add_hide_dirs_wpss', $needles);

		foreach ($files as $file_meta) {
			foreach ($needles as $needle) {
				$needle = wpss_remove_fullpath($needle);
				if (strstr($file_meta->file, $needle) !== false) {
					$counter++;
				}
			}
		}

		if ($return_count) {
			return $counter;
		}

		return $counter === count( $files ) ? true : false;
	}

	public function is_meta_found_on_backup($backup_id){
		$db_meta_data_count = $this->db->get_var("SELECT count(*) FROM {$this->db->base_prefix}wpss_processed_files WHERE is_dir = '0' AND backupID = " . $backup_id . " AND file LIKE '%wpss_meta%'");

		return ($db_meta_data_count) ? true : false;
	}


	public function get_all_tables($override_meta = false){

		if (wpss_is_meta_data_backup() && $override_meta === false) {
			return WPSS_Base_Factory::get('WPSS_App_Functions')->get_meta_backup_tables();
		}

		$staging_db_prefix = apply_filters('get_internal_staging_db_prefix', '');
		if ($staging_db_prefix) {
			$sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME NOT LIKE '%wpss_%' AND TABLE_NAME NOT LIKE '%".$staging_db_prefix."%' AND TABLE_SCHEMA = '".DB_NAME."' AND TABLE_TYPE='BASE TABLE'";
		} else {
			$sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME NOT LIKE '%wpss_%' AND TABLE_SCHEMA = '".DB_NAME."' AND TABLE_TYPE='BASE TABLE'";
		}
		$result_obj = $this->db->get_results($sql, ARRAY_N);
		foreach ($result_obj as $table) {
			$tables[] = $table[0];
		}
		return $tables;
	}

	public function get_unfinished_table(){

		$sql = "SELECT * FROM {$this->db->base_prefix}wpss_processed_iterator WHERE `offset` != '-1' AND `name` NOT LIKE '%/%' ORDER BY `id` LIMIT 1";
		wpss_log($sql,'-----------$sql----------------');
		$result_obj = $this->db->get_results($sql);

		wpss_log($result_obj,'-----------$result_obj----------------');

		if (empty($result_obj[0])) {
			return false;
		}

		$result_obj[0]->offset = empty($result_obj[0]->offset) ? 0 : $result_obj[0]->offset ;

		return $result_obj[0];
	}

	public function get_all_included_tables($structure_only = false){

		if (wpss_is_meta_data_backup()) {
			$filter = $structure_only ? 'structure' : 'full';
			return WPSS_Base_Factory::get('WPSS_App_Functions')->get_meta_backup_tables($filter);
		}

		$all_tables = $this->get_all_tables();

		$tables = array();

		foreach ($all_tables as $key => $table) {
			if ($structure_only) {
				if (WPSS_Base_Factory::get('WPSS_ExcludeOption')->is_excluded_table($table) === 'content_excluded') {
					$tables[] = $table;
				}
			} else {
				if (WPSS_Base_Factory::get('WPSS_ExcludeOption')->is_excluded_table($table) === 'table_included') {
					$tables[] = $table;
				}
			}
		}

		return $tables;
	}

	public function drop_tables_with_prefix($prefix){
		if(empty($prefix)){
			return false;
		}
		if ($prefix == $this->db->base_prefix ) {
			return false;
		}

		$sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%".$prefix."%' AND TABLE_SCHEMA = '".DB_NAME."'";

		$result_obj = $this->db->get_results($sql, ARRAY_N);

		foreach ($result_obj as $table) {
			$tables[] = '`' . $table[0] . '`';
		}

		if (empty($tables)) {
			wpss_log($sql, "--------drop_tables_with_prefix--failed------");
			wpss_log($this->db->last_error, "--------drop_tables_with_prefix--failed------");

			return true;
		}

		$str = implode (", ", $tables);
		$sql = "DROP TABLES ".$str;

		$result_obj = $this->db->query($sql);

		if($result_obj === false){
			wpss_log($sql, "--------drop_tables_with_prefix--failed------");
			wpss_log($this->db->last_error, "--------drop_tables_with_prefix--failed------");
		}

		return $result_obj;
	}

	public function get_only_wp_tables(){
		$sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME like '%".$this->db->base_prefix."%' AND TABLE_NAME NOT LIKE '%wpss_%' AND TABLE_SCHEMA = ".DB_NAME."'";
		// $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME like '%".$this->db->base_prefix."%' AND TABLE_SCHEMA = '".DB_NAME."'";
		$result_obj = $this->db->get_results($sql, ARRAY_N);
		foreach ($result_obj as $table) {
			if (stripos($table[0], $this->db->base_prefix) !== FALSE) {
				$tables[] = $table[0];
			}
		}
		return $tables;
	}

	public function get_table_size($table_name, $return = 1){
		$sql = "SHOW TABLE STATUS LIKE '".$table_name."'";
		$result = $this->db->get_results($sql);
		if (isset($result[0]->Data_length) && isset($result[0]->Index_length) && $return) {
			return $this->convert_bytes_to_hr_format(($result[0]->Data_length) + ($result[0]->Index_length));
		} else {
			return $result[0]->Data_length + $result[0]->Index_length;
		}
		return '0 B';
	}

	public function convert_bytes_to_hr_format($size){
		if (empty($size)) {
			$size = 0;
		}

		if (1024 > $size) {
			return $size.' B';
		} else if (1048576 > $size) {
			return round( ($size / 1024) , 2). ' KB';
		} else if (1073741824 > $size) {
			return round( (($size / 1024) / 1024) , 2). ' MB';
		} else if (1099511627776 > $size) {
			return round( ((($size / 1024) / 1024) / 1024) , 2). ' GB';
		}
	}

	public function is_table_included($table){
		$check_exist_sql = $this->db->prepare("
					SELECT count(*)
					FROM {$this->db->base_prefix}wpss_inc_exc_contents
					WHERE `key` = %s AND `type` = 'table' AND action = 'include'", $table);

		$count = $this->db->get_var($check_exist_sql);

		return ($count) ? true : false;
	}

	public function get_processed_tables(){
		$processed_tables = $this->db->get_var("SELECT count(*) FROM {$this->db->base_prefix}wpss_processed_iterator WHERE `offset` = '-1' AND `name` != 'header' AND `name` NOT LIKE '%/%'");
		$processed_tables = empty($processed_tables) ? 0 : $processed_tables;
		return $processed_tables;
	}

	public function get_overall_files(){
		return $this->db->get_var("SELECT count(*) FROM {$this->db->base_prefix}wpss_current_process ");
	}

	public function get_processed_files(){
		$current_process_file_id = $this->config->get_option('current_process_file_id');
		if (empty($current_process_file_id)) {
			$current_process_file_id = $this->config->get_option('current_process_file_id');
			return 0;
		}
		return $current_process_file_id;
		return $this->db->get_var("SELECT count(*) FROM {$this->db->base_prefix}wpss_current_process WHERE status='P' OR status='E' OR status='S'");
	}

	public function get_current_backup_progress(&$return_array){
		if (!$this->config->get_option('in_progress', true)) {
			return $return_array['progress_complete'] = true;
		}

		$current_backup_ID = wpss_get_cookie('backupID');

		$current_process_file_id = $this->config->get_option('current_process_file_id');
		$processed_files_total = empty( $current_process_file_id ) ? 0 : $current_process_file_id;
		$processed_files_current = $this->get_processed_files_count($current_backup_ID);

		$iterator_file_size                  = $this->config->get_option('iterator_file_size');
		$collected_tables_for_backups_size   = $this->config->get_option('collected_tables_for_backups_size');
		$collected_tables_for_backups_offset = $this->config->get_option('collected_tables_for_backups_offset');

		//Process database backup status
		$overall_tables = $this->get_overall_tables();
		$processed_tables = $this->get_processed_tables();
		$overall_files = $this->get_overall_files();

		$return_array['backup_progress']['db']['overall'] = (int) $overall_tables;
		$return_array['backup_progress']['db']['processed'] = (int) $processed_tables;
		$return_array['backup_progress']['db']['progress'] = $processed_tables.'/'.$overall_tables;

		if (empty($overall_tables)) {
			$return_array['backup_progress']['db']['percentage'] = 0;
		} else {
			$return_array['backup_progress']['db']['percentage'] = round(($processed_tables / $overall_tables) * 100, 1);
		}
		$return_array['backup_progress']['db']['size'] = $this->convert_bytes_to_hr_format($collected_tables_for_backups_size);
		$return_array['backup_progress']['db']['total'] = (int) $collected_tables_for_backups_offset;

		if (empty($processed_files_total) && !$this->config->get_option('gotfileslist') ) {
			$return_array['backup_progress']['files']['processing']['running'] = true;
			$return_array['backup_progress']['files']['processed']['running'] = false;
		} else {
			$return_array['backup_progress']['files']['processed']['running'] = true;
			$return_array['backup_progress']['files']['processing']['running'] = false;
		}

		$return_array['backup_progress']['files']['processed']['total'] = (int) $processed_files_total;
		$return_array['backup_progress']['files']['processed']['current'] = (int) $processed_files_current;
		$return_array['backup_progress']['files']['processing']['size'] = $this->convert_bytes_to_hr_format($iterator_file_size);

		$app_functions = WPSS_Base_Factory::get('WPSS_App_Functions');
		$message = $app_functions->get_processing_files_count($type = 'backup');
		$return_array['backup_progress']['files']['processing']['progress'] = $message;

		$return_array['backup_progress']['files']['processing']['overall'] = (int) $overall_files;

		if (empty($overall_files)) {
			$return_array['backup_progress']['files']['processing']['percentage'] = 0;
		} else {
			$return_array['backup_progress']['files']['processing']['percentage'] = round(($processed_files_current / $overall_files) * 100, 1);
		}

		$progress_percent = 0;

		if (!empty($processed_files_current) && !empty($overall_files)) {
			$progress_percent = ($processed_files_total / $overall_files) * 100;
			if ($progress_percent > 99) {
				$progress_percent = 0;
			}
		}

		$return_array['backup_progress']['progress_percent'] = round($progress_percent, 1);

		if ($return_array['backup_progress']['db']['percentage'] === 100) {
			$return_array['backup_progress']['current_state']['msg'] = "Sending the database to your " . DEFAULT_REPO_LABEL . " account...";
		} else {
			$return_array['backup_progress']['current_state']['msg'] = "Sending all files to your " . DEFAULT_REPO_LABEL . " account...";
		}

		if (($overall_tables > $processed_tables || ($overall_tables == 0 && $processed_tables == 0) ) && $progress_percent === 0) {
			$return_array['backup_progress']['db']['running'] = true;
		} else if($overall_tables == $processed_tables) {
			$return_array['backup_progress']['db']['running'] = false;
		}

		if ( !$this->config->get_option('do_wpss_meta_data_backup') ) {
			return $return_array['backup_progress']['meta']['running'] = false;
		}

		$return_array['backup_progress']['meta']['running'] = true;
		return $return_array['backup_progress']['meta']['message'] = 'Processing meta data...';
	}

	public function get_processed_files_count($backup_id = null) {
		if (empty($backup_id)) {
			$backup_id = wpss_get_cookie('backupID');
		}

		$count = $this->db->get_var($this->db->prepare("
				SELECT COUNT(*)
				FROM {$this->db->base_prefix}wpss_processed_{$this->getTableName()}
				WHERE is_dir = %s ", $backup_id, 0));

		return $count;
	}

	public function backup_type_check($backup_id) {
		$type = $this->db->get_row('SELECT backup_type FROM ' . $this->db->base_prefix . 'wpss_backups WHERE backup_id =' . $backup_id);
		if ($type != "") {
			return $type->backup_type;
		} else {
			return "";
		}

	}

	public function save_manual_backup_name_wpss($backup_name) {
		$backup_id = wpss_get_cookie('backupID');
		$query = $this->db->prepare("UPDATE {$this->db->base_prefix}wpss_backups SET backup_name = %s WHERE `backup_id` = %s", $backup_name, $backup_id);
		$query_result = $this->db->query($query);
		die(json_encode(array('status'=>'success')));
	}

	public function get_no_of_backups() {

		$last_month_time = apply_filters('get_days_show_from_revision_limits_wpss', '');

		if(empty($last_month_time)){
			return 0;
		}

		$count = $this->db->get_var('SELECT count(*) FROM ' . $this->db->base_prefix . 'wpss_backups WHERE `backup_id` > ' . $last_month_time);
		if (empty($count)) {
			return 0;
		}
		return $count;
	}

	public function get_backups_meta() {

		$last_month_time = apply_filters('get_days_show_from_revision_limits_wpss', '');

		if(empty($last_month_time)){
			return [];
		}

		$final_data = array();
		$backups_meta = $this->db->get_results('SELECT * FROM ' . $this->db->base_prefix . 'wpss_backups  WHERE `backup_id` > ' . $last_month_time);
		$i = 0;

		foreach ($backups_meta as $meta) {
			$final_data[$i]['id']             = $meta->backup_id;
			$final_data[$i]['type']           = $meta->backup_type;
			$final_data[$i]['files_count']    = $meta->files_count;
			$final_data[$i]['name']           = !empty($meta->backup_name) ? $meta->backup_name : '';
			$final_data[$i]['update_details'] = !empty($meta->update_details) ? unserialize($meta->update_details) : NULL;
			$i++;
		}

		return $final_data;
	}

	public function save_PTC_update_response($formatted_response){
		$backup_id = wpss_get_cookie('backupID');
		$query = $this->db->prepare("UPDATE {$this->db->base_prefix}wpss_backups SET update_details = %s WHERE `backup_id` = ".$backup_id."", serialize($formatted_response));
		$query_result = $this->db->query($query);
	}

	public function get_all_distinct_files($offset){
		$query = "SELECT DISTINCT file FROM {$this->db->base_prefix}wpss_processed_files WHERE is_dir = 0 LIMIT $offset," . WPSS_CHECK_CURRENT_STATE_FILE_LIMIT;
		// wpss_log($query, '---------------$query-----------------');
		return $this->db->get_results($query);
	}

}
