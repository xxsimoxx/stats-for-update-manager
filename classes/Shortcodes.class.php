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
 * The "Update Me" plugin has [sfum-installs id="update-me/update-me.php"] active installs!
 *
 * Code Potent is running on [sfum-domains] sites!
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

		// No id passed in? Calculate and return total of all plugin installs.
		if (empty($atts['id'])) {
			$plugin_a = 2323;
			$plugin_b = 4921;
			$plugin_c = 6232;
			$total_installs = number_format($plugin_a + $plugin_b + $plugin_c);
			return $total_installs;
		}

		// Validate id or bail here.


		// Calculate the total installs for the given plugin.
		$total_installs = number_format(2353);

		// Return an integer.
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

		// No $atts to bother with, move on.

		// Gather all hashes into an array.

		// Use array_unique

		// Count remaining elements.

		// Format the number.
		$unique_domains = number_format(1257);

		// Return..
		return $unique_domains;

	}

}

// Run it!
new Shortcodes;