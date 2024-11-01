jQuery(document).ready(function($) {
    
    // get_staging_details_wpss();
});

function wpss_get_dialog_header(title){
	return '<div class="ui-dialog-titlebar ui-widget-header  ui-helper-clearfix" style="text-align: left;padding-left: 10px;"><span class="ui-dialog-title" style="font-size: 15px;line-height: 29px;text-align: left !important;">' + title + '</span></div>';
}

function wpss_get_dialog_body(content, status){

	var status_icon = '';

	if (status != undefined) {
		switch(status){
			case 'success':
				status_icon = '<span class=" wpss-model-icon dashicons dashicons-yes" style="color: #79ba49;"></span>';
				break;
			case 'warning':
				status_icon = '<span class="wpss-model-alert-icon dashicons dashicons-warning" style="color: #ffb900;"></span>';
				break;
			case 'error':
				status_icon = '<span class=" wpss-model-icon dashicons dashicons-no-alt" style="color: #dc3232;"></span>';
				break;
		}
	}

	return '<div class="ui-dialog-content ui-widget-content" style="font-size: 14px;text-align: left;padding: 20px 30px 20px 30px;"> ' + status_icon + content + '</div><hr>';
}