jQuery(document).ready(function($) {

	jQuery("#toggle_exlclude_files_n_folders_wpss, #toggle_exlclude_files_n_folders_staging_wpss, #wpss_init_toggle_files").on("click", function(e){
		e.stopImmediatePropagation();
		e.preventDefault();

		var id             = '#wpss_exc_files';
		var category       = 'backup';
		var init = false;

		switch(this.id){
			case 'toggle_exlclude_files_n_folders_staging_wpss':
				id       = '#wpss_exc_files_staging';
				category = 'staging';
			break;
			case 'wpss_init_toggle_files':
				init = true;
			break;
		}

		jQuery(id).toggle();

		if (jQuery(id).css('display') === 'block') {
			fancy_tree_init_exc_files_wpss(init, id, category);
		}

		return false;
	});

	jQuery("#toggle_wpss_db_tables, #toggle_wpss_db_tables_staging, #wpss_init_toggle_tables").on("click", function(e){
		e.stopImmediatePropagation();
		e.preventDefault();

		var id       = '#wpss_exc_db_files';
		var category = 'backup';
		var init     = false;

		switch(this.id){
			case 'toggle_wpss_db_tables_staging':
				id       = '#wpss_exc_db_files_staging';
				category = 'staging';
			break;
			case 'wpss_init_toggle_tables':
				init = true;
			break;
		}

		jQuery(id).toggle();

		if (jQuery(id).css('display') === 'block') {
			fancy_tree_init_exc_tables_wpss(init, id, category);
		}

		return false;
	});
});

function fancy_tree_init_exc_files_wpss(init, id, category){

	wpss_recent_category = category;

	jQuery(id).fancytree({
		checkbox: false,
		selectMode: 3,
		clickFolderMode: 3,
		debugLevel:0,
		source: {
			url: ajaxurl,
			security: wpss_ajax_object.ajax_nonce,
			data: (init === false) ? {
				action: "wpss_get_root_files",
				category: category,
				security: wpss_ajax_object.ajax_nonce,
			} : {
				action: "wpss_get_init_root_files",
				category: category,
				security: wpss_ajax_object.ajax_nonce,
			},
		},
		postProcess: function(event, data) {
			data.result = data.response;
		},
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected) node.setSelected(true);
				if (node.data.partial) node.addClass('fancytree-partsel');
			});
		},
		lazyLoad: function(event, ctx) {
			var key = ctx.node.key;
			ctx.result = {
				url: ajaxurl,
				security: wpss_ajax_object.ajax_nonce,
				data: (init === false) ? {
					action: "wpss_get_files_by_key",
					key : key,
					category: wpss_recent_category,
					security: wpss_ajax_object.ajax_nonce,
				} : {
					action: "wpss_get_init_files_by_key",
					key : key,
					category: wpss_recent_category,
					security: wpss_ajax_object.ajax_nonce,
				},
			};
		},
		renderNode: function(event, data){ // called for every toggle
			if (!data.node.getChildren())
				return false;
			if(data.node.expanded === false){
				data.node.resetLazy();
			}
			jQuery.each( data.node.getChildren(), function( key, value ) {
				if (value.data.preselected){
					value.setSelected(true);
				} else {
					value.setSelected(false);
				}
			});
		},
		loadChildren: function(event, data) {
			data.node.fixSelection3AfterClick();
			data.node.fixSelection3FromEndNodes();
			last_lazy_load_call = jQuery.now();
		},
		dblclick: function(event, data) {
			return false;
			// data.node.toggleSelected();
		},
		keydown: function(event, data) {
			if( event.which === 32 ) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	}).on("mouseenter", '.fancytree-node', function(event){
		mouse_enter_files_wpss(event);
	}).on("mouseleave", '.fancytree-node' ,function(event){
		mouse_leave_files_wpss(event);
	}).on("click", '.fancytree-file-exclude-key' ,function(event){
		mouse_click_files_exclude_key_wpss(event);
	}).on("click", '.fancytree-file-include-key' ,function(event){
		mouse_click_files_include_key_wpss(event);
	});

	return false;
}

function fancy_tree_init_exc_tables_wpss(init, id, category){

	if (init) {
		//jQuery('#wpss_init_table_div').css('position', 'absolute');
	}

	jQuery(id).fancytree({
		checkbox: false,
		selectMode: 2,
		icon:false,
		debugLevel:0,
		source: {
			url: ajaxurl,
			data: (init === false) ? {
				action: "wpss_get_tables",
				category: category,
				security: wpss_ajax_object.ajax_nonce,
			} : {
				action: "wpss_get_init_tables",
				security: wpss_ajax_object.ajax_nonce,
			},
		},
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected){
					node.setSelected(true);
					if (node.data.content_excluded && node.data.content_excluded == 1) {
						node.addClass('fancytree-partial-selected');
					}
				}
			});
		},
		loadChildren: function(event, ctx) {
			// ctx.node.fixSelection3AfterClick();
			// ctx.node.fixSelection3FromEndNodes();
			last_lazy_load_call = jQuery.now();
		},
		dblclick: function(event, data) {
			return false;
		},
		keydown: function(event, data) {
			if( event.which === 32 ) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	}).on("mouseenter", '.fancytree-node', function(event){
		mouse_enter_tables_wpss(event);
	}).on("mouseleave", '.fancytree-node' ,function(event){
		mouse_leave_tables_wpss(event);
	}).on("click", '.fancytree-table-exclude-key' ,function(event){
		mouse_click_table_exclude_key_wpss(event);
	}).on("click", '.fancytree-table-include-key' ,function(event){
		mouse_click_table_include_key_wpss(event);
	}).on("click", '.fancytree-table-exclude-content' ,function(event){
		mouse_click_table_exclude_content_wpss(event);
	});
}

function mouse_enter_files_wpss(event){
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	if (	node &&
			typeof node.span != 'undefined'
			&& (!node.getParentList().length
					|| node.getParent().selected !== false
					|| node.getParent().partsel !== false
					|| (node.getParent()
						&& node.getParent()[0]
						&& node.getParent()[0].extraClasses
						&& node.getParent()[0].extraClasses.indexOf("fancytree-selected") !== false )
					|| (node.getParent()
						&& node.getParent()[0]
						&&node.getParent()[0].extraClasses
						&& node.getParent()[0].extraClasses.indexOf("fancytree-partsel") !== false )
						 )
			) {
		jQuery(node.span).addClass('fancytree-background-color');
		jQuery(node.span).find('.fancytree-size-key').hide();
		jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
		if(node.selected){
			jQuery(node.span).append("<span role='button' class='fancytree-file-exclude-key'><a>Exclude</a></span>");
		} else {
			jQuery(node.span).append("<span role='button' class='fancytree-file-include-key'><a>Include</a></span>");
		}
	}
}

function mouse_leave_files_wpss(event){
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	if (node && typeof node.span != 'undefined') {
		jQuery(node.span).find('.fancytree-size-key').show();
		jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
		jQuery(node.span).removeClass('fancytree-background-color');
	}
}

function mouse_click_files_exclude_key_wpss(event){
	var node = jQuery.ui.fancytree.getNode(event);

	if (!node) {
		return ;
	}

	if (node!= undefined && node.getChildren() != undefined) {
		var children = node.getChildren();
		jQuery.each(children, function( index, value ) {
			value.selected = false;
			value.setSelected(false);
			value.removeClass('fancytree-partsel fancytree-selected')
		});
	}

	folder = (node.folder) ? 1 : 0;
	node.removeClass('fancytree-partsel fancytree-selected');
	node.selected = false;
	node.partsel = false;
	jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
	save_inc_exc_data_wpss('exclude_file_list_wpss', node.key, folder);
}

function mouse_click_files_include_key_wpss(event){
	var node = jQuery.ui.fancytree.getNode(event);

	if (!node) {
		return ;
	}

	if (node != undefined && node.getChildren() != undefined) {
		var children = node.getChildren();
		jQuery.each(children, function( index, value ) {
			value.selected = true;
			value.setSelected(true);
			value.addClass('fancytree-selected')
		});
	}

	folder = (node.folder) ? 1 : 0;
	node.addClass('fancytree-selected');
	node.selected = true;
	jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
	save_inc_exc_data_wpss('include_file_list_wpss', node.key, folder);
}

function mouse_enter_tables_wpss(event){
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	jQuery(node.span).addClass('fancytree-background-color');
	jQuery(node.span).find('.fancytree-size-key').hide();
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	if(node.selected || (node.extraClasses  && node.extraClasses.indexOf('fancytree-selected')!== -1 ) ){
		if (!node.extraClasses || node.extraClasses.indexOf('fancytree-partial-selected') === -1) {
			jQuery(node.span).append("<span role='button' class='fancytree-table-exclude-key' style='margin-left: 10px;position: absolute;right: 120px;'><a>Exclude Table</a></span>");
			jQuery(node.span).append("<span role='button' class='fancytree-table-exclude-content' style='position: absolute;right: 4px;'><a>Exclude Content</a></span>");
		} else {
			jQuery(node.span).append("<span role='button' class='fancytree-table-exclude-key'><a>Exclude Table</a></span>");
		}
	} else {
		jQuery(node.span).append("<span role='button' class='fancytree-table-include-key'><a>Include Table</a></span>");
	}
}

function mouse_leave_tables_wpss(event){
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	if (node && typeof node.span != 'undefined') {
		jQuery(node.span).find('.fancytree-size-key').show();
		jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
		jQuery(node.span).removeClass('fancytree-background-color');
		jQuery(node.span).removeClass('fancytree-background-color');
	}
}

function mouse_click_table_exclude_key_wpss(event){
	event.stopImmediatePropagation();
	event.preventDefault();
	var node = jQuery.ui.fancytree.getNode(event);
	node.removeClass('fancytree-partsel fancytree-selected fancytree-partial-selected');
	node.partsel = node.selected = false;
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	save_inc_exc_data_wpss('exclude_table_list_wpss', node.key, false);

	var oldCount = jQuery('#excluded_tables_count_wpss').val();
	oldCount = parseInt(oldCount);
	jQuery('#excluded_tables_count_wpss').val(oldCount + 1);
}

function mouse_click_table_include_key_wpss(event){
	event.stopImmediatePropagation();
	event.preventDefault();
	var node = jQuery.ui.fancytree.getNode(event);
	node.removeClass('fancytree-partial-selected');
	node.addClass('fancytree-selected ');
	node.selected = true;
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	save_inc_exc_data_wpss('include_table_list_wpss', node.key, false);

	var oldCount = jQuery('#excluded_tables_count_wpss').val();
	oldCount = parseInt(oldCount);
	jQuery('#excluded_tables_count_wpss').val(oldCount - 1);
}

function mouse_click_table_exclude_content_wpss(event){
	event.stopImmediatePropagation();
	event.preventDefault();
	var node = jQuery.ui.fancytree.getNode(event);
	node.addClass('fancytree-partial-selected ');
	node.selected = true;
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	save_inc_exc_data_wpss('include_table_structure_only_wpss', node.key, false);
}

function save_inc_exc_data_wpss(request, file, isdir){
	jQuery.post(ajaxurl, {
		security: wpss_ajax_object.ajax_nonce,
		action: request,
		data: {file : file, isdir : isdir, category: get_current_category_wpss()},
	}, function(data) {
	});
}

function get_current_category_wpss(){
	return 'staging';
	// return (location.search.indexOf('wpss-settings-page') !== -1 || (location.hash && location.hash.indexOf('#wpss-settings-page') !== -1 ))  ? 'staging' : 'backup';
}
