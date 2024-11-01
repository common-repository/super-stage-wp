<?php

class WPSS_Load_Live_Image {

    public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;
        $this->config = WPSS_Factory::get('config');
        $this->set_wpss_globals();
        // $this->fill_global_js_vars();
	}

    public function set_wpss_globals()	{
		global $WPSS_SITE_TYPE;
		global $WPSS_PROD_UPLOADS_URL;
		global $WPSS_PROD_URL;
		global $WPSS_LOCAL_URL;
		global $WPSS_LOCAL_UPLOADS_URL;
		global $WPSS_PROD_UPLOADS_URL_WITHOUT_PROTOCOL;
		global $WPSS_LOCAL_UPLOADS_URL_WITHOUT_PROTOCOL;
		global $WPSS_LOAD_IMAGES_FROM_LIVE;

		$WPSS_SITE_TYPE = 'local';
		$WPSS_LOAD_IMAGES_FROM_LIVE = $this->config->get_option('load_images_from_live_site_settings');

		if(empty($WPSS_SITE_TYPE) || $WPSS_SITE_TYPE == 'local'){
			$WPSS_PROD_URL = $this->config->get_option('s2l_live_url');
			$WPSS_PROD_URL = trim($WPSS_PROD_URL, '/');

			$upload_dir_meta = wp_upload_dir();
			$WPSS_LOCAL_UPLOADS_URL = $upload_dir_meta['baseurl'];

			$WPSS_LOCAL_UPLOADS_URL_WITHOUT_PROTOCOL = str_replace('https://', '//', $WPSS_LOCAL_UPLOADS_URL);
			$WPSS_LOCAL_UPLOADS_URL_WITHOUT_PROTOCOL = str_replace('http://', '//', $WPSS_LOCAL_UPLOADS_URL_WITHOUT_PROTOCOL);
			$local_site_url = get_home_url();
			$WPSS_LOCAL_URL = $local_site_url;

			$WPSS_PROD_UPLOADS_URL = str_replace($local_site_url, $WPSS_PROD_URL, $WPSS_LOCAL_UPLOADS_URL);
			$WPSS_PROD_UPLOADS_URL_WITHOUT_PROTOCOL = str_replace('https://', '//', $WPSS_PROD_UPLOADS_URL);
			$WPSS_PROD_UPLOADS_URL_WITHOUT_PROTOCOL = str_replace('http://', '//', $WPSS_PROD_UPLOADS_URL_WITHOUT_PROTOCOL);
		}

	}

	public function fill_global_js_vars()	{

		wpss_log('', "--------fill_global_js_vars--------");
		global $WPSS_SITE_TYPE;
		global $WPSS_PROD_UPLOADS_URL;
		global $WPSS_PROD_URL;
		global $WPSS_LOCAL_URL;
		global $WPSS_LOCAL_UPLOADS_URL;
		global $WPSS_PROD_UPLOADS_URL_WITHOUT_PROTOCOL;
		global $WPSS_LOCAL_UPLOADS_URL_WITHOUT_PROTOCOL;
		global $WPSS_LOAD_IMAGES_FROM_LIVE;

		$all_new_attachment_urls_arr = $this->get_local_site_new_attachments_url() ?? [];
		$all_new_attachment_urls_arr = json_encode($all_new_attachment_urls_arr, true);

		if(!isset($_GET['wpss_s2l_start'])){
			$is_start_staging_to_live = 'no';
		} else {
			$is_start_staging_to_live = sanitize_text_field($_GET['wpss_s2l_start']);
		}

		echo "<script>
			var IS_START_STAGING_TO_LIVE_WPSS = '$is_start_staging_to_live';
		</script>";

		

		echo "<script>
			var WPSS_SITE_TYPE = '$WPSS_SITE_TYPE';
			var WPSS_PROD_UPLOADS_URL = '$WPSS_PROD_UPLOADS_URL';
			var WPSS_PROD_URL = '$WPSS_PROD_URL';
			var WPSS_LOCAL_URL = '$WPSS_LOCAL_URL';
			var WPSS_LOCAL_UPLOADS_URL = '$WPSS_LOCAL_UPLOADS_URL';
			var WPSS_PROD_UPLOADS_URL_WITHOUT_PROTOCOL = '$WPSS_PROD_UPLOADS_URL_WITHOUT_PROTOCOL';
			var WPSS_LOCAL_UPLOADS_URL_WITHOUT_PROTOCOL = '$WPSS_LOCAL_UPLOADS_URL_WITHOUT_PROTOCOL';
			var WPSS_LOAD_IMAGES_FROM_LIVE = '$WPSS_LOAD_IMAGES_FROM_LIVE';
			var WPSS_ALL_NEW_ATTACHMENT_URLS = $all_new_attachment_urls_arr;
		</script>";


		
	}

    public function modify_posts_content($content) {
		// wpss_log($content, "--------modify_posts_content--------");

		global $WPSS_SITE_TYPE;
		global $WPSS_PROD_UPLOADS_URL;
		global $WPSS_LOCAL_UPLOADS_URL;
		global $WPSS_PROD_UPLOADS_URL_WITHOUT_PROTOCOL;
		global $WPSS_LOCAL_UPLOADS_URL_WITHOUT_PROTOCOL;
		global $WPSS_LOAD_IMAGES_FROM_LIVE;

		if( $WPSS_LOAD_IMAGES_FROM_LIVE != 'yes'
			|| empty($WPSS_SITE_TYPE) 
			|| $WPSS_SITE_TYPE != 'local' 
			|| empty($WPSS_PROD_UPLOADS_URL) ){

			return $content;
		}

		// return $content;
		
		wpss_log($content, "----before----modify_posts_content--------");

		$all_new_attachment_urls_arr = $this->get_local_site_new_attachments_url();
		foreach ($all_new_attachment_urls_arr as $key => $value) {
			$content = str_replace($value, $key, $content);
		}

		$content = str_replace($WPSS_LOCAL_UPLOADS_URL_WITHOUT_PROTOCOL, $WPSS_PROD_UPLOADS_URL_WITHOUT_PROTOCOL, $content);

		foreach ($all_new_attachment_urls_arr as $key => $value) {
			$content = str_replace($key, $value, $content);
		}

		wpss_log($content, "--------modified_posts_content--------");
		
		return $content;
	}

	public function modify_image_site_url( $url, $post_id = null ) {
		// wpss_log($url, "--------modify_image_site_url--------");

		global $WPSS_SITE_TYPE;
		global $WPSS_PROD_UPLOADS_URL;
		global $WPSS_LOCAL_UPLOADS_URL;
		global $WPSS_LOAD_IMAGES_FROM_LIVE;

		if( $WPSS_LOAD_IMAGES_FROM_LIVE != 'yes'
			|| empty($WPSS_SITE_TYPE) 
			|| $WPSS_SITE_TYPE != 'local' 
			|| empty($WPSS_PROD_UPLOADS_URL) ){

			return $url;
		}

		$all_new_attachment_urls_arr = $this->get_local_site_new_attachments_url();

		if(in_array($url, $all_new_attachment_urls_arr)){

			return $url;
		}

		$url = str_replace($WPSS_LOCAL_UPLOADS_URL, $WPSS_PROD_UPLOADS_URL, $url);

		wpss_log($url, "--------modified_image_site_url--------");
		
		return $url;
	}

	public function wp_prepare_attachment_for_js($response, $attachment = null, $meta = null)	{

		global $WPSS_SITE_TYPE;
		global $WPSS_PROD_UPLOADS_URL;
		global $WPSS_LOCAL_UPLOADS_URL;
		global $WPSS_LOAD_IMAGES_FROM_LIVE;

		if( $WPSS_LOAD_IMAGES_FROM_LIVE != 'yes'
			|| empty($WPSS_SITE_TYPE) 
			|| $WPSS_SITE_TYPE != 'local' 
			|| empty($WPSS_PROD_UPLOADS_URL) ){

			wpss_log($WPSS_SITE_TYPE, "--------WPSS_SITE_TYPE--------");
			wpss_log($WPSS_PROD_UPLOADS_URL, "--------WPSS_PROD_UPLOADS_URL--------");
			wpss_log($WPSS_LOCAL_UPLOADS_URL, "--------WPSS_LOCAL_UPLOADS_URL--------");

			return $response;
		}


		if(empty($response) || $response['type'] != 'image' || empty($response['sizes']) ){

			return $response;
		}

		$all_new_attachment_urls_arr = $this->get_local_site_new_attachments_url();

		// wpss_log($all_new_attachment_urls_arr, "--------all_new_attachment_urls_arr--------");

		$needs_change = true;
		foreach ($response['sizes'] as $key => $value) {
			if(in_array($value['url'], $all_new_attachment_urls_arr)){

				wpss_log($value['url'], "--------value['url']---kusumban-----");

				$needs_change = false;

				break;
			}
		}

		if(!$needs_change){

			return $response;
		}

		foreach ($response['sizes'] as $key => $value) {
			$response['sizes'][$key]['url'] = str_replace($WPSS_LOCAL_UPLOADS_URL, $WPSS_PROD_UPLOADS_URL, $value['url']);
		}
		
		return $response;
	}

	public function modify_image_src_set( $sources, $size_array = null, $image_src = null, $image_meta = null, $attachment_id = null ) {
		global $WPSS_SITE_TYPE;
		global $WPSS_PROD_UPLOADS_URL;
		global $WPSS_LOCAL_UPLOADS_URL;
		global $WPSS_PROD_UPLOADS_URL_WITHOUT_PROTOCOL;
		global $WPSS_LOCAL_UPLOADS_URL_WITHOUT_PROTOCOL;
		global $WPSS_LOAD_IMAGES_FROM_LIVE;

		if( $WPSS_LOAD_IMAGES_FROM_LIVE != 'yes'
			|| empty($WPSS_SITE_TYPE) 
			|| $WPSS_SITE_TYPE != 'local' 
			|| empty($WPSS_PROD_UPLOADS_URL) ){

			return $sources;
		}

		// wpss_log($sources, "--------modify_image_src_set--------");
		// wpss_log($image_src, "--------image_src--------");
		// wpss_log($image_meta, "--------image_meta--------");
		// wpss_log($attachment_id, "--------attachment_id--------");

		$all_new_attachment_urls_arr = $this->get_local_site_new_attachments_url();

		// wpss_log($all_new_attachment_urls_arr, "--------all_new_attachment_urls_arr--------");

		$needs_change = true;
		foreach ($sources as $key => $value) {
			if(in_array($value['url'], $all_new_attachment_urls_arr)){

				wpss_log($value['url'], "--------value['url']---kusumban-----");

				$needs_change = false;

				break;
			}
		}

		if(!$needs_change){

			return $sources;
		}

		foreach ($sources as $key => $value) {
			$value['url'] = str_replace($WPSS_LOCAL_UPLOADS_URL_WITHOUT_PROTOCOL, $WPSS_PROD_UPLOADS_URL_WITHOUT_PROTOCOL, $value['url']);
			$sources[$key] = $value;
		}

		wpss_log($sources, "---sources-----modified_image_src_set--------");
		
		return $sources;
	}

	public function insert_into_local_site_new_attachments_table($data = null)	{
		global $wpdb;

		$uploads_url_with_slash = content_url() . '/uploads/';
		$relative_file_path = str_replace($uploads_url_with_slash, '', $data['guid']);

		$table_name = $this->wpdb->base_prefix . 'wpss_local_site_new_attachments';
		$insert_res = $wpdb->replace($table_name, array(
			'url' => $data['guid'],
			'name' =>$data['post_name'], 
			'relative_file_path' => $relative_file_path
		));

		if(false === $insert_res){
			wpss_log($wpdb->last_error, "--------insert_res--error--insert_into_local_site_new_attachments_table----");
		}
	}

	public function get_local_site_new_attachments_url()	{
		global $wpdb;

		$table_name = $this->wpdb->base_prefix . 'wpss_local_site_new_attachments';

		$sql = "SELECT url FROM `$table_name` WHERE 1 ORDER BY url";
		$all_new_attachment_urls = $wpdb->get_results($sql, ARRAY_A);

		if($all_new_attachment_urls === false){
			wpss_log($sql, "--------error----get_local_site_new_attachments_url----");
			
			return [];
		}

		$all_new_attachment_urls_arr = array();

		foreach ($all_new_attachment_urls as $key => $value) {
			$md5 = md5($value['url']);
			$all_new_attachment_urls_arr[$md5] = $value['url'];
		}

		wpss_log($all_new_attachment_urls_arr, "--------all_new_attachment_urls_arr--------");

		return $all_new_attachment_urls_arr;
	}

	public function get_local_site_new_attachments_file_path()	{
		global $wpdb;

		$table_name = $this->wpdb->base_prefix . 'wpss_local_site_new_attachments';

		$sql = "SELECT relative_file_path FROM `$table_name` WHERE 1 ORDER BY relative_file_path";
		$all_new_attachment_files = $wpdb->get_results($sql, ARRAY_A);

		if($all_new_attachment_files === false){
			wpss_log($sql, "--------error----get_local_site_new_attachments_url----");
		}

		$all_new_attachment_files_arr = array();

		foreach ($all_new_attachment_files as $key => $value) {
			$md5 = md5($value['relative_file_path']);
			$all_new_attachment_files_arr[$md5] = $value['relative_file_path'];
		}

		// wpss_log($all_new_attachment_files_arr, "--------all_new_attachment_files_arr--------");

		return $all_new_attachment_files_arr;
	}

	public function wp_insert_attachment_data($data = null, $arg2 = null) {
		// wpss_log($data, "--------arg1-wp_insert_attachment_data-------");
		// wpss_log($arg2, "--------arg2-wp_insert_attachment_data-------");

		if(empty($data)){
			return $data;
		}

		if($data['post_type'] == 'attachment'){
			$this->insert_into_local_site_new_attachments_table($data);
		}

		return $data;
	}

	public function admin_print_footer_scripts() {
		global $WPSS_PROD_URL;
		global $WPSS_LOCAL_URL;
		global $WPSS_LOAD_IMAGES_FROM_LIVE;

		if($WPSS_LOAD_IMAGES_FROM_LIVE != 'yes'){
			echo '';

			return;
		}

		$hotlink_live_images_wpss = $this->config->get_option('load_images_from_live_site_settings');
		wpss_log("", "--------admin_print_footer_scripts--------");

		echo '<script type="text/javascript">
			var WPSS_PROD_URL = "'.$WPSS_PROD_URL.'";
			var WPSS_LOCAL_URL = "'.$WPSS_LOCAL_URL.'";
			var WPSS_LOCAL_URL = "'.$WPSS_LOCAL_URL.'";
			var HOTLINK_LIVE_IMAGES_WPSS = "'.$hotlink_live_images_wpss.'";
			setTimeout(function(){ jQuery(".editor-writing-flow img").each(function(){
				var srcAttr = jQuery(this).attr("src");
				
				if(typeof srcAttr == "undefined" || srcAttr == "" || !srcAttr){
					return;
				}

				if(srcAttr.indexOf(WPSS_LOCAL_URL) < 0){
					return;
				}

				srcAttr = srcAttr.replace(WPSS_LOCAL_URL, WPSS_PROD_URL);
				jQuery(this).attr("src", srcAttr);
			}); }, 3000);

		</script>';
	}

}
