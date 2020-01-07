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
 * Copyright © 2019 - CodePotent
 * -----------------------------------------------------------------------------
 *           ____          _      ____       _             _
 *          / ___|___   __| | ___|  _ \ ___ | |_ ___ _ __ | |_
 *         | |   / _ \ / _` |/ _ \ |_) / _ \| __/ _ \ '_ \| __|
 *         | |__| (_) | (_| |  __/  __/ (_) | ||  __/ | | | |_
 *          \____\___/ \__,_|\___|_|   \___/ \__\___|_| |_|\__|.com
 *
 * -----------------------------------------------------------------------------
 */

/**
 * Example Usage
 * ---------------------------------
 *
 * Code Potent has [sfum-installs] active plugin installations!
 *
 * This plugin has [sfum-installs id="update-me/update-me.php"] active installs!
 *
 * Code Potent is running on [sfum-domains] sites!
 *
 *
 * To Do
 * ---------------------------------
 *
 * Calculate numbers in process_shortcode_installs and process_shortcode_domains
 * methods; currently hardcoded for PoC.
 *
 * Line 114; id needs to be validated/checked.
 *
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

		// Initialization.
		$total_installs = 0;

		// Default SQL counts all rows.
		$sql = 'SELECT count(slug) FROM '.$wpdb->prefix.'sfum_logs';

		// Id passed in? Refine and prepare the query.
		if (!empty($atts['id'])) {
			$sql = $wpdb->prepare('SELECT count(slug)
							FROM '.$wpdb->prefix.'sfum_logs
							WHERE slug
							LIKE "%s"',
							$atts['id']);
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

		// Initialization.
		$total_installs = 0;

		// Default SQL counts all rows.
		$sql = 'SELECT count(DISTINCT site) FROM '.$wpdb->prefix.'sfum_logs';

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