<?php
/**
 * -----------------------------------------------------------------------------
 * Purpose: WP-CLI commands.
 * Package: XXSimoXX\StatsForUpdateManager
 * -----------------------------------------------------------------------------
 * Copyright © 2020 - Simone Fioravanti
 * -----------------------------------------------------------------------------
 */

namespace XXSimoXX\StatsForUpdateManager;

if (!defined('ABSPATH')) {
	die('-1');
};

global $sfum_instance;

/**
* Commands to work with Stats for Update Manager.
*
*
* ## EXAMPLES
*
*     wp statistics show [--days=<integer>] [--format=<format>] [--fields=<fields>] [--date=<date-format>]
*     wp statistics delete <identifier>
*     wp statistics purge [--yes]
*
* @when after_wp_load
*/

class Statistics{

	/**
	* Print statistics for Update Manager.
	*
	* ## OPTIONS
	*
	*
	* [--days=<integer>]
	* : How many day an unseen installation is considered active
	*
	* [--format=<format>]
	* : Render output in a particular format.
	* ---
	* default: table
	* options:
	*   - table
	*   - csv
	*   - ids
	*   - json
	*   - count
	*   - yaml
	* ---
	*
	* [--fields=<fields>]
	* : Limit the output to specific object fields (comma separated list).
	*
	* [--date=<date-format>]
	* : Add current date to results in the specified format.
	*
	* ## EXAMPLES
	*
	*     wp statistics show --days=1 --date='d/m/Y'
	*
	* @when after_wp_load
	*/
	public function show($args, $assoc_args) {

		// Bring StatsForUpdateManager class into scope.
		global $sfum_instance;

		// Use option from command line or default for days.
		if (!is_numeric($timing = \WP_CLI\Utils\get_flag_value($assoc_args, 'days'))) {
			$timing = $sfum_instance->db_unactive_entry;
		} else {
			$timing = 'INTERVAL '.$timing.' DAY';
		}

		// Query database.
		global $wpdb;
		$results = $wpdb->get_results('SELECT slug as "identifier", count(*) as "active" FROM '.$wpdb->prefix.DB_TABLE_NAME.' WHERE last > NOW() - '.$timing.' group by slug', 'ARRAY_A');
		if (count($results) === 0) {
			return \WP_CLI::error('No active plugins found.');
		}

		// Get CPT.
		$cpt = $sfum_instance->get_cpt();

		// Define fields
		$field_list = 'ID,title,active,identifier,status,endpoint';

		// Handle --date.
		if ($date_format = \WP_CLI\Utils\get_flag_value($assoc_args, 'date')) {
			$field_list = 'date,'.$field_list;
		}

		// Join db results with CPT informations.
		foreach ($results as $key => &$result) {
			if (!isset($cpt[$result['identifier']])) {
				unset($results[$key]);
				continue;
			}
			$result['ID'] = $cpt[$result['identifier']];
			$result['title'] = get_the_title($cpt[$result['identifier']]);
			$result['status'] = get_post_status($cpt[$result['identifier']]);
			$result['endpoint'] = get_post_type($cpt[$result['identifier']]);
			if ($date_format !== null) {
				$result['date'] = date($date_format);
			}
		}

		// Sort results.
		usort(
			$results,
			function($a, $b) {
				$c = $b['active'] - $a['active'];
				if ($c !== 0) {
					return $c;
				}
				return strcasecmp($a['title'], $b['title']);
			}
		);

		// Display results using buildin WP CLI function.
		\WP_CLI\Utils\format_items(
			\WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'table'),
			$results,
			\WP_CLI\Utils\get_flag_value($assoc_args, 'fields', $field_list)
		);
	}

	/**
	* Purge (empty) the log table.
	*
	* Be careful, this deletes all the logs in an irreversible way!
	*
	* ## OPTIONS
	*
	*
	* [--yes]
	* : Skip confirmation.
	*
	* @when after_wp_load
	*/
	public function purge($args, $assoc_args) {

		// Ask for confirmation if --yes not given.
		if (!\WP_CLI\Utils\get_flag_value($assoc_args, 'yes', false)) {
			\WP_CLI::warning('This will delete ALL the logs.');
		}
		\WP_CLI::confirm('Are you sure?', $assoc_args);

		// Truncate the table.
		global $wpdb;
		$wpdb->suppress_errors();
		if (!$wpdb->query('TRUNCATE TABLE '.$wpdb->prefix.DB_TABLE_NAME)) {
			// Failed, bail and exit.
			\WP_CLI::error('Can\'t delete logs.', true);
		}

		// Success.
		\WP_CLI::success('Table is empty now.');
	}

	/**
	* Delete a plugin/theme from logs.
	*
	* ## PARAMETER
	*
	*
	* <identifier>
	* : The identifier of the plugin/theme you want to remove logs.
	*
	* @when after_wp_load
	*/
	public function delete($args, $assoc_args) {

		// Bring StatsForUpdateManager class into scope.
		global $sfum_instance;

		// Get CPT.
		$cpt = $sfum_instance->get_cpt();

		// Check if the identifier exists.
		if (!array_key_exists($args[0], $cpt)) {
			\WP_CLI::error('Can\'t find "'.$args[0].'".', true);
		}

		// Delete from DB.
		$where = ['slug' => $args[0]];
		global $wpdb;
		$deleted = $wpdb->delete($wpdb->prefix.DB_TABLE_NAME, $where);

		// Bail if nothing deleted.
		if (!$deleted > 0) {
			\WP_CLI::error('Can\'t find "'.$args[0].'".', true);
		}

		// Success.
		\WP_CLI::success('"'.$args[0].'" deleted.');

	}

}
