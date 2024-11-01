<?php
/**
 * Functionality to remove Super Stage WP from your WordPress installation
 *
 * @copyright Revmakx. All rights reserved.
 * @author Revmakx
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
if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

global $wpdb;

$table_name = $wpdb->base_prefix . 'wpss_options';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$table_name = $wpdb->base_prefix . 'wpss_processed_files';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$table_name = $wpdb->base_prefix . 'wpss_processed_iterator';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$table_name = $wpdb->base_prefix . 'wpss_current_process';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$table_name = $wpdb->base_prefix . 'wpss_activity_log';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$table_name = $wpdb->base_prefix . 'wpss_backups';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$table_name = $wpdb->base_prefix . 'wpss_inc_exc_contents';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$table_name = $wpdb->base_prefix . 'wpss_local_site_new_attachments';
$wpdb->query("DROP TABLE IF EXISTS $table_name");
