<?php

// Really uninstalling?
if (!defined('WP_UNINSTALL_PLUGIN')) {
	die;
}

// Load constants.
require_once('includes/constants.php');

// Delete table.
global $wpdb;
$table_name = $wpdb->prefix.DB_TABLE_NAME;
$sql = 'DROP TABLE IF EXISTS '.$table_name.';';
$wpdb->query($sql);

// Delete options.
delete_option('sfum_db_ver');
