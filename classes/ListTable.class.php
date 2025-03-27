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

if (!defined('ABSPATH')) {
	die('-1');
}


if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}

// Class for display statistics page.
class SFUM_List_Table extends \WP_List_Table {
	// No need to check nonces to reorder or filter a column
	// phpcs:disable WordPress.Security.NonceVerification.Recommended

	// Contains the requested filter type (all, plugins or themes).
	private $filtertype = 'all';

	// Contains the data to be rendered, as we want this to be passed from another class.
	private $data = [];

	// Load list items, as we want this to be passed from another class.
	public function load_items($statistics) {
		$this->data = $statistics;
	}

	// Parse request to understand what to filter for.
	function get_filtertype() {
		// Sanitize filtertype and default to all
		if (!isset($_GET['filtertype']) || !in_array($_GET['filtertype'], ['all', 'plugins', 'themes'], true)) {
			return 'all';
		}
		return sanitize_key(wp_unslash($_GET['filtertype']));
	}

	// Output columns definition.
	function get_columns() {
		return [
			'name'       => esc_html__('Name', 'stats-for-update-manager'),
			'identifier' => 'Identifier',
			'id'         => 'Id',
			'count'      => esc_html__('Active Installations', 'stats-for-update-manager'),
			'type'       => esc_html__('Type', 'stats-for-update-manager'),
		];
	}

	// Output hidden columns.
	function get_hidden_columns() {
		return [
			'identifier',
			'id',
		];
	}

	// Output sortable columns.
	function get_sortable_columns() {
		return [
			'name'    => ['name',  false],
			'count'   => ['count', false],
			'type'    => ['type',  false],
		];
	}

	// Filter plugin or themes for display.
	function filter_data($data) {
		$list = [];
		foreach ($data as $val) {
			if ($this->filtertype === 'plugins' && strpos($val['identifier'], '/') === false) {
				continue;
			}
			if ($this->filtertype === 'themes' && !strpos($val['identifier'], '/') === false) {
				continue;
			}
			$list[] = $val;
		}
		return $list;
	}

	// Callable to be used with usort.
	function reorder($a, $b) {
		// If no orderby or wrong orderby, default to plugin or theme name.
		$orderby = (isset($_GET['orderby']) && in_array($_GET['orderby'], ['name', 'count', 'type'], true)) ? sanitize_key(wp_unslash($_GET['orderby'])) : 'name';
		// If no order or wrong order, default to asc.
		$order = (isset($_GET['order']) && $_GET['order'] !== 'asc') ? 'desc' : 'asc';

		// Properly order numeric values or reorder text case-insensitive.
		if (is_int($a[$orderby])) {
			$result = $a[$orderby] - $b[$orderby];
		} else {
			$result = strcasecmp($a[$orderby], $b[$orderby]);
		}

		return ( $order === 'asc' ) ? $result : -$result;
	}

	// Just output the column.
	function column_default($item, $column_name) {
		return $item[$column_name];
	}

	// For "Count" column reformat it.
	function column_count($item) {
		return '<p style="font-size: 1.5em; padding-top:16px;">'.$item['count'].'</p>';
	}

	// For "Type" column reformat it.
	function column_type($item) {
		return '<p style="padding-top:16px;">'.$item['type'].'</p>';
	}

	// For "Name" column add row actions and reformat it.
	function column_name($item) {

		$actions = [
			'edit'  => '<a href="'.admin_url('post.php?post='.$item['id'].'&action=edit">'.esc_html__('Edit', 'stats-for-update-manager').'</a>'),
			'delete' => '<a href="'.wp_nonce_url(home_url(add_query_arg(['action' => 'delete', 'id' => $item['id']])), 'delete', '_sfum').'">'.esc_html__('Reset', 'stats-for-update-manager').'</a>',
		];

		$logo = get_logo($item['identifier']);
		if ($logo === false) {
			$logo = '<div style="min-width: 64px; min-height: 64px; background: #357EC0; color: white; border-radius: 50%; line-height: 64px; text-align: center; display: inline-block"><span style="font-size: 200%;">'.initials($item['name']).'</span></div>';
		} else {
			$logo = '<div style="vertical-align: middle; display: inline;"><img style="width: 64px; height: 64px;" src="'.$logo.'"></div>';
		}

		$name = $logo.'<span class="row-title" style="padding-left: 15px;">'.$item['name'].'</span>';

		return sprintf('%1$s %2$s', $name, $this->row_actions($actions));

	}

	// Display filter for plugins or themes.
	function extra_tablenav($which) { // phpcs:ignore
		$theme_count = $this->get_theme_count();
		$all_count = count($this->data);
		$plugin_count = $all_count - $theme_count;
		echo '<ul class="subsubsub">';
		echo '<li><a '.($this->filtertype === 'all' ? 'class="current"' : '').' href="'.esc_url(home_url(add_query_arg('filtertype', 'all'))).'">'.esc_html__('All', 'stats-for-update-manager').'<span class="count"> ('.esc_html($all_count).')</span></a> |</li>';
		echo '<li><a '.($this->filtertype === 'plugins' ? 'class="current"' : '').' href="'.esc_url(home_url(add_query_arg('filtertype', 'plugins'))).'">'.esc_html__('Plugins', 'stats-for-update-manager').'<span class="count"> ('.esc_html($plugin_count).')</span></a> |</li>';
		echo '<li><a '.($this->filtertype === 'themes' ? 'class="current"' : '').' href="'.esc_url(home_url(add_query_arg('filtertype', 'themes'))).'">'.esc_html__('Themes', 'stats-for-update-manager').'<span class="count"> ('.esc_html($theme_count).')</span></a></li>';
		echo '</ul>';
	}

	// Retrieve the number of themes.
	function get_theme_count() {
		$count = 0;
		foreach ($this->data as $val) {
			if (strpos($val['identifier'], '/') !== false) {
				continue;
			}
			$count++;
		}
		return $count;
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
