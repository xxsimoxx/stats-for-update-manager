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

// Class for display statistics page.
class SFUM_List_Table extends \WP_List_Table {

	// Contains the data to be rendered, as we want this to be passed from another class.
	private $data = [];

	// Contains the requested filter type (all, plugins or themes).
	private $filtertype = 'all';

	// Output columns definition.
	function get_columns() {
		$columns = [
			'name'       => esc_html__('Name', 'stats-for-update-manager'),
			'identifier' => 'Identifier',
			'id'         => 'Id',
			'count'      => esc_html__('Active Installations', 'stats-for-update-manager'),
			'type'       => esc_html__('Type', 'stats-for-update-manager'),
		];
		return $columns;
	}

	// Output sortable columns.
	function get_sortable_columns() {
		$sortable_columns = [
			'name'    => ['name', false],
			'count'   => ['count', false],
			'type'    => ['type', false],
		];
		return $sortable_columns;
	}

	// Output hidden columns.
	function get_hidden_columns() {
		$hidden_columns = [
			'identifier',
			'id',
		];
		return $hidden_columns;
	}

	// Callable to be used with usort.
	function reorder( $a, $b ) {
		// If no orderby or wrong orderby, default to plugin or theme name.
		$orderby = (!empty($_GET['orderby']) && in_array($_GET['orderby'], ['name', 'count', 'type'], true)) ? $_GET['orderby'] : 'name';
		// If no order or wrong order, default to asc.
		$order = (!empty($_GET['order']) && $_GET['order'] !== 'asc') ? 'desc' : 'asc';

		$result = strcasecmp( $a[$orderby], $b[$orderby] );

		return ( $order === 'asc' ) ? $result : -$result;
	}

	// Just output the column.
	function column_default($item, $column_name) {
		return $item[$column_name];
	}

	// For "Name" column add row action and reformat it.
	function column_name($item) {
		$actions = [
			'edit' => '<a href="'.admin_url('post.php?post='.$item['id'].'&action=edit">'.esc_html__('Edit', 'stats-for-update-manager').'</a>'),
		];
		$name = '<span class="row-title">'.$item['name'].'</span>';
		return sprintf('%1$s %2$s', $name, $this->row_actions($actions));
	}

	// Display filter for plugins or themes.
	function extra_tablenav($which) {
		$theme_count = $this->get_theme_count();
		$all_count = count($this->data);
		$plugin_count = $all_count-$theme_count;
		echo '<ul class="subsubsub">';
		echo '<li><a '.($this->filtertype==='all'?'class="current"':'').' href="'.home_url(add_query_arg('filtertype', 'all')).'">'.esc_html__('All', 'stats-for-update-manager').'<span class="count"> ('.$all_count.')</span></a> |</li>';
		echo '<li><a '.($this->filtertype==='plugins'?'class="current"':'').' href="'.home_url(add_query_arg('filtertype', 'plugins')).'">'.esc_html__('Plugins', 'stats-for-update-manager').'<span class="count"> ('.$plugin_count.')</span></a> |</li>';
		echo '<li><a '.($this->filtertype==='themes'?'class="current"':'').' href="'.home_url(add_query_arg('filtertype', 'themes')).'">'.esc_html__('Themes', 'stats-for-update-manager').'<span class="count"> ('.$theme_count.')</span></a></li>';
		echo '</ul>';
	}

	// Load list items, as we want this to be passed from another class.
	function load_items($statistics) {
		$this->data = $statistics;
	}

	// Filter plugin or themes for display.
	function filter_data($data) {
		$list=[];
		foreach($data as $val) {
			if ($this->filtertype==='plugins' && strpos($val['identifier'],'/')===false) {
				continue;
			}
			if ($this->filtertype==='themes' && !strpos($val['identifier'],'/')===false) {
				continue;
			}
			$list[] = $val;
		}
		return $list;
	}

	// Retrieve the number of themes.
	function get_theme_count() {
		$count = 0;
		foreach($this->data as $val) {
			if (strpos($val['identifier'],'/')===false) {
				$count++;
			}
		}
		return $count;
	}

	// Parse request to understand what to filter for.
	function get_filtertype() {
		// Sanitize filtertype and default to all
		if (empty($_GET['filtertype']) || !in_array($_GET['filtertype'], ['all', 'plugins', 'themes'], true)) {
			return 'all';
		}
		return $_GET['filtertype'];
	}

	// Prepare our columns and insert data.
	function prepare_items() {
		$this->filtertype = $this->get_filtertype();
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = [$columns, $hidden, $sortable];
		$data = $this->filter_data($this->data);
		usort($data, [&$this, 'reorder']);
		$this->items = $data;
	}

}