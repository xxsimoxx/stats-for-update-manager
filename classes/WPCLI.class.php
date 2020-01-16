<?php
/**
 * Plugin Name: Stats for Update Manager
 * Plugin URI: https://software.gieffeedizioni.it
 * Description: Statistics for Update Manager by CodePotent.
 * Version: 1.0.0
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Gieffe edizioni srl
 * Author URI: https://www.gieffeedizioni.it
 * Text Domain: stats-for-update-manager
 * Domain Path: /languages
 */

namespace XXSimoXX\StatsForUpdateManager;

if (!defined('ABSPATH')){
	die('-1');
};

global $sfum_instance;

/**
* Commands to work with Stats Update Manager.
*
*
* ## EXAMPLES
*
*     wp statistics show --days=4
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
	* ## EXAMPLES
	*
	*     wp statistics show --days=4
	*
	* @when after_wp_load
	*/
	// Handle WP-CLI statistics command.
	public function show($args, $assoc_args) {
		// Bring StatsForUpdateManager class into scope.
		global $sfum_instance;
		// Check for UM running.
		if (!$sfum_instance->um_running) {
			return \WP_CLI::error('Update Manager is not running.');
		}
		// Use option from command line or default for days.
		if (!is_numeric($timing=\WP_CLI\Utils\get_flag_value($assoc_args, 'days'))){
			
			$timing = $sfum_instance->db_unactive_entry;
		} else {
			$timing = 'INTERVAL '.$timing.' DAY';
		}
		// Query database and CPT
		global $wpdb;
		$results = $wpdb->get_results('SELECT slug as "identifier", count(*) as "active" FROM '.$wpdb->prefix.DB_TABLE_NAME.' WHERE last > NOW() - '.$timing.' group by slug', 'ARRAY_A');
		if (count($results) === 0){
			return \WP_CLI::error('No active plugins found.');
		}
		// Get CPT
		$cpt = $sfum_instance->get_cpt();
		// Join db results with CPT informations.
		foreach ($results as $key=>&$result) {
			if (!isset($cpt[$result['identifier']])){
				unset($results[$key]);
				continue;
			}
			$result['ID'] = $cpt[$result['identifier']];
			$result['title']= get_the_title($cpt[$result['identifier']]);
			$result['status']= get_post_status($cpt[$result['identifier']]);
		}
		// Sort results.
		usort($results, function($a, $b) {
			$c = $b['active'] - $a['active'];
			if ($c !== 0) {
				return $c;
			}
			return strcasecmp($a['title'], $b['title']);
		});
		// Display results using buildin WP CLI function.
		\WP_CLI\Utils\format_items(
			\WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'table'),
			$results,
			\WP_CLI\Utils\get_flag_value($assoc_args, 'fields', 'ID,title,active,identifier,status')
		);
	}
}
