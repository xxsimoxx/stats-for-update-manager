<?php
/**
 * -----------------------------------------------------------------------------
 * Purpose: Display statistics using standard WP List Table.
 * Package: XXSimoXX\StatsForUpdateManager
 * -----------------------------------------------------------------------------
 * Copyright © 2020 - Simone Fioravanti
 * -----------------------------------------------------------------------------
 */

namespace XXSimoXX\StatsForUpdateManager;

if (!defined('ABSPATH')){
	die('-1');
};


if (!class_exists('WP_List_Table')) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class SFUM_List_Table extends \WP_List_Table {

	// Contains the data to be rendered, as we want this to be passed from another class.
	private $data = [];

	function get_columns() {
		$columns = [
			'name'       => esc_html__('Name', 'stats-for-update-manager'),
			'identifier' => 'Identifier',
			'id'         => 'Id',
			'count'      => esc_html__('Active installations', 'stats-for-update-manager'),
			'type'       => esc_html__('Type', 'stats-for-update-manager'),
		];
		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = [
			'name'    => ['name', false],
			'count'   => ['count', false],
			'type'    => ['type', false],
		];
		return $sortable_columns;
	}

	function reorder( $a, $b ) {
		// If no sort or wrong sort, default to plugin or theme name.
		$orderby = (!empty($_GET['orderby']) && in_array($_GET['orderby'], ['name', 'count', 'type'])) ? $_GET['orderby'] : 'name';
		// If no order, default to asc.
		$order = (!empty($_GET['order']) && in_array($_GET['order'], ['asc', 'desc'])) ? $_GET['order'] : 'asc';
		// Determine sort order.
		$result = strcasecmp( $a[$orderby], $b[$orderby] );
		// Send final sort direction to usort.
		return ( $order === 'asc' ) ? $result : -$result;
	}

	function column_default($item, $column_name) {
		return $item[$column_name];
	}

	function column_name($item) {
		$actions = [
			'edit' => '<a href="'.admin_url('post.php?post='.$item['id'].'&action=edit">'.esc_html__('Edit', 'stats-for-update-manager').'</a>'),
		];
		$name = '<span class="row-title">'.$item['name'].'</span>';

		return sprintf('%1$s %2$s', $name, $this->row_actions($actions));
	}

	function column_count($item) {
	$padded='              '.$item['count'];
	$padded=substr($padded, -10);
	$padded=str_replace(' ', '&nbsp;', $padded);
		return $padded;
	}

	// Load list items, as we want this to be passed from another class.
	function load_items($statistics) {
		$this->data = $statistics;
	}

	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = ['identifier', 'id'];
		$primary = 'name';
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = [$columns, $hidden, $sortable, $primary];
		usort($this->data, [&$this, 'reorder']);
		$this->items = $this->data;
	}

}
