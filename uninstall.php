<?php

// Really uninstalling?
if (!defined('WP_UNINSTALL_PLUGIN')) {
	die;
}

// Load constants.
require_once('includes/constants.php');

// Delete table.
global $wpdb;
$wpdb->query($wpdb->prepare('DROP TABLE IF EXISTS %s', $wpdb->prefix.DB_TABLE_NAME)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange

// Delete options.
delete_option('sfum_db_ver');
