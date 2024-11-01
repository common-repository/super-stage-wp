<?php

function enque_settings_page_js_files_wpss(){
    wp_enqueue_script('wpss-jquery-ui-custom-js', plugins_url('', __FILE__) . '/' . WPSS_TC_PLUGIN_NAME . '/treeView/jquery-ui.custom.js', array(), WPSS_VERSION);
    wp_enqueue_script('wpss-fancytree-js', plugins_url('', __FILE__) . '/' . WPSS_TC_PLUGIN_NAME . '/treeView/jquery.fancytree.js', array(), WPSS_VERSION);
    wp_enqueue_script('wpss-filetree-common-js', plugins_url('', __FILE__) . '/' . WPSS_TC_PLUGIN_NAME . '/treeView/common.js', array(), WPSS_VERSION);

    wp_enqueue_style('wpss-fancytree-css', plugins_url('', __FILE__) . '/' . WPSS_TC_PLUGIN_NAME . '/treeView/skin/ui.fancytree.css', array(), WPSS_VERSION);
}

add_action('admin_enqueue_scripts', 'enque_settings_page_js_files_wpss');

?>

<div class="wrap" id="super-stage-wp-page">
    <?php add_thickbox(); ?>    
    <div id="wpss-content-id" style="display:none;"> <p> This is my hidden content! It will appear in ThickBox when the link is clicked. </p></div>
    <a style="display:none" href="#TB_inline?width=600&height=550&inlineId=wpss-content-id" class="thickbox wpss-thickbox">View my inline content!</a>


    <h2>Super Stage WP Settings</h2>
    
    <form id="wpss-settingsform" action="#" method="post" onsubmit="return false;">
		<?php wp_nonce_field( 'super-stage-wpsettings_page' ); ?>
		<input type="hidden" name="page" value="super-stage-wpsettings" />
		<input type="hidden" name="action" value="super-stage-wp" />

        <?php $more_tables_div = apply_filters('page_settings_content_wpss', '');?>
		<?php echo __($more_tables_div); ?>

        <p class="submit">
			<input type="submit" name="submit" id="wpss_save_changes" class="button-primary" value="<?php _e( 'Save Changes', 'super-stage-wp' ); ?>" />
		</p>
    </form>

</div>
