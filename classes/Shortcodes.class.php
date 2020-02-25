<?php

/**
 * -----------------------------------------------------------------------------
 * Purpose: Shortcodes for Stats for Update Manager plugin.
 * Package: XXSimoXX\StatsForUpdateManager
 * Author: Code Potent
 * Author URI: https://codepotent.com
 * -----------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.txt.
 * -----------------------------------------------------------------------------
 * Copyright © 2020, Code Potent
 * -----------------------------------------------------------------------------
 */

// Declare the namespace.
namespace XXSimoXX\StatsForUpdateManager;

// Prevent direct access.
if (!defined('ABSPATH')) {
	die();
}

class Shortcodes {

	/**
	 * Constructor.
	 *
	 * The constructor simply sets up the object properties.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Hook into the system.
		$this->init();

	}

	/**
	 * Hook into the system.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 */
	private function init() {

		// Hook in the shortcode processor for # of installs.
		add_shortcode('sfum-installs', [$this, 'process_shortcode_installs']);

		// Hook in the shortcode processor for # of domains.
		add_shortcode('sfum-domains', [$this, 'process_shortcode_domains']);

	}

	/**
	 * Process shortcode for installs.
	 *
	 * This method handles processing of the [total-installs] shortcode.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Arguments passed in the shortcode.
	 *
	 * @return string
	 */
	function process_shortcode_installs($atts) {

		// Bring database object into scope.
		global $wpdb;

		// Bring StatsForUpdateManager class into scope.
		global $sfum_instance;

		// Initialization.
		$total_installs = 0;

		// Default SQL counts all rows.
		$sql = 'SELECT count(slug) FROM '.$wpdb->prefix.DB_TABLE_NAME.' WHERE last > NOW() - '.$sfum_instance->db_unactive_entry;

		// Id passed in? Refine and prepare the query.
		if (!empty($atts['id'])) {
			$sql = $wpdb->prepare('SELECT count(slug)
							FROM '.$wpdb->prefix.DB_TABLE_NAME.'
							WHERE slug
							LIKE "%s"
							AND last > NOW() - '.$sfum_instance->db_unactive_entry,
							$atts['id']
							);
		}

		// Execute SQL.
		$result = $wpdb->get_results($sql, ARRAY_A);

		// Got something? Count it.
		if (!empty($result[0])) {
			$total_installs = number_format(array_sum($result[0]));
		}

		// Return.

		return $total_installs;

	}

	/**
	 * Process shortcode for domains.
	 *
	 * This method handles processing of the [total-domains] shortcode.
	 *
	 * @author John Alarcon
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Arguments passed in the shortcode.
	 *
	 * @return string
	 */
	function process_shortcode_domains($atts) {

		// Bring database object into scope.
		global $wpdb;

		// Bring StatsForUpdateManager class into scope.
		global $sfum_instance;

		// Initialization.
		$total_installs = 0;

		// Default SQL counts all rows.
		$sql = 'SELECT count(DISTINCT site) FROM '.$wpdb->prefix.DB_TABLE_NAME.' WHERE last > NOW() - '.$sfum_instance->db_unactive_entry;

		// Execute SQL.
		$result = $wpdb->get_results($sql, ARRAY_A);

		// Got something? Count it.
		if (!empty($result[0])) {
			$total_installs = number_format(array_sum($result[0]));
		}

		// Return.
		return $total_installs;

	}

}

// Run it!
new Shortcodes;
