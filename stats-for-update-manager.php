<?php
/**
 * Plugin Name: Stats for Update Manager
 * Plugin URI: https://software.gieffeedizioni.it
 * Description: Statistics for Update Manager by Code Potent.
 * Version: 1.1.2
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Gieffe edizioni srl
 * Author URI: https://www.gieffeedizioni.it
 * Text Domain: stats-for-update-manager
 * Domain Path: /languages
 */

namespace XXSimoXX\StatsForUpdateManager;

if (!defined('ABSPATH')) {
	die('-1');
};

// Load constants.
require_once('includes/constants.php');

// Add auto updater https://codepotent.com/classicpress/plugins/update-manager/
require_once('classes/UpdateClient.class.php');

// Shortcodes.
require_once('classes/Shortcodes.class.php');

// WP-CLI extensions.
require_once('classes/WPCLI.class.php');

// List table.
require_once('classes/ListTable.class.php');

class StatsForUpdateManager{

	// Initialize variables

	// Time (in SQL format) for the plugin to be considered installed.
	public $db_unactive_entry = '';

	// Time (in SQL format) for the record to be deleted.
	public $db_old_entry = '';

	// Array to keep statistics for plugin details.
	public $stat_array = [];

	// Array to keep options found in the request.
	private $options = [];

	// String to keep the screen.
	private $screen = '';

	// String to keep Update Manager version.
	private $um_version = '';

	// Keep last called identifier.
	private $lastseen = [];

	public function __construct() {

		// Activation, deactivation and uninstall.
		register_activation_hook(__FILE__, [$this, 'activate']);
		register_deactivation_hook(__FILE__, [$this, 'deactivate']);
		register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);

		// Check for Update Manager running.
		if (!is_plugin_active(UM_SLUG)) {
			add_action('admin_notices', [$this, 'um_missing']);
			add_action('admin_init', [$this, 'auto_deactivate']);
			return;
		}

		// Get Update Manager version.
		$plugin_data = get_plugin_data(WP_PLUGIN_DIR.'/'.UM_SLUG);
		$this->um_version = $plugin_data['Version'];

		// Load text domain.
		add_action('plugins_loaded', [$this, 'text_domain']);

		// Hook to Update Manager filter request.
		add_filter(UM_HOOK_PLUGINS, [$this, 'log_request'], 1000);
		add_filter(UM_HOOK_THEMES, [$this, 'log_request'], 1000);
		// Keep compatible with Update Manager 1.X
		add_filter(UM_HOOK_DEPRECATED, [$this, 'log_request'], 1000);

		// On init apply filters to set the number of days for an entry
		// to be considered inactive or have to be removed from db.
		add_action('init', [$this, 'apply_timing_filters'], 1000);

		// Populate active installations.
		add_action('init', [$this, 'active_installations_filters'], 1000);

		// Register privacy policy.
		add_action('admin_init', [$this, 'privacy']);

		// Add menu for statistics.
		add_action('admin_menu', [$this, 'create_menu'], 100);
		add_action('admin_enqueue_scripts', [$this, 'backend_css']);

		// Add a button that links to statistics in plugins page.
		add_filter('plugin_action_links_'.plugin_basename(__FILE__), [$this, 'pal']);

		// Add credits to footer.
		add_filter(UM_HOOK_FOOTER.MENU_SLUG, [$this, 'filter_footer_text'], 100);

		// Add a cron to clean table.
		add_action('sfum_clean_table', [$this, 'clean_table']);
		if (!wp_next_scheduled('sfum_clean_table')) {
			wp_schedule_event(time(), 'daily', 'sfum_clean_table');
		}

		// Add "statistics" commands to WP-CLI
		if (defined('WP_CLI') && WP_CLI) {
			\WP_CLI::add_command('statistics', '\XXSimoXX\StatsForUpdateManager\Statistics');
		}

		// Fire REST API class. It have to be enabled defining SFUM_ENABLE_REST = true.
		if (defined('\SFUM_ENABLE_REST') && \SFUM_ENABLE_REST === true) {
			require_once('classes/CustomEndPoint.class.php');
			new CustomEndPoint;
		}

	}

	// Trigger a warning. Helpful in developement.
	private function warn($x) {
		 trigger_error(print_r($x, true), E_USER_WARNING);
	}

	// Apply filters to set the number of days for an entry to be considered inactive or have to be removed from db.
	public function apply_timing_filters() {
		$this->db_unactive_entry = 'INTERVAL '.apply_filters('sfum_inactive_after', DEFAULT_INACTIVE_DAYS).' DAY';
		$this->db_old_entry = 'INTERVAL '.apply_filters('sfum_old_after', DEFAULT_OLD_DAYS).' DAY';
	}

	// Fill a stat array and add hooks to show active installations in plugin details.
	public function active_installations_filters() {
		$this->stat_array = $this->active_installations_populate();
		foreach ($this->stat_array as $slug => $count) {
			add_filter('codepotent_update_manager_'.$slug.'_active_installs', [$this, 'active_installations_filter'], 10, 2);
		}
	}

	// Populate active installations array.
	private function active_installations_populate() {
		// Check if we have already a transient with needed data.
		if (!($all_stats = get_transient('sfum_all_stats'))) {
			// Build an array in the form 'slug'->count.
			$all_stats = [];
			global $wpdb;
			$results = $wpdb->get_results('SELECT slug, count(*) as total FROM '.$wpdb->prefix.DB_TABLE_NAME.' WHERE last > NOW() - '.$this->db_unactive_entry.' group by slug', 'ARRAY_A');
			foreach ($results as $result) {
				$all_stats[$result['slug']] = $result['total'];
			}
			// Save it all for 6 hours.
			set_transient('sfum_all_stats', $all_stats, 6 * HOUR_IN_SECONDS);
		}
		return $all_stats;
	}

	// Filter that return number of installations of a plugin.
	public function active_installations_filter($val, $identifier = null) {
		if (is_null($identifier)) {
			return 0;
		}
		// Let's user change or hide the numbers.
		$retval = apply_filters('sfum_active_installations', $this->stat_array);
		if (isset($retval[$identifier])) {
			return $retval[$identifier];
		} else {
			return 0;
		}
	}

	// Error for Update Manager missing.
	public static function um_missing() {
		echo '<div class="notice notice-error is-dismissible"><p>';
		/* translators: 1 is the link to Update Manager homepage */
		printf(__('<b>Stats for Update Manager</b> requires <a href="%1$s" target="_blank">Update Manager</a>.', 'stats-for-update-manager'), UM_LINK);
		_e(' Stats for Update Manager <b>is not active</b>.', 'stats-for-update-manager');
		echo '</p></div>';
	}

	// Get associative array to resolve Endpoint Identifier/Post ID.
	public function get_cpt() {
		$data = [];

		$posts = get_posts(
			[
			'post_type' => [UM_CPT_PLUGINS, UM_CPT_THEMES],
			'post_status' => ['publish', 'pending' ,'draft'],
			'numberposts' => -1,
			]
		);
		foreach ($posts as $post) {
			$meta = get_post_meta($post->ID, 'id', true);
			$data[$meta] = $post->ID;
		}
		return $data;
	}

	// Parse a request. Return false if not valid.
	// Otherwise return identifier.
	private function get_identifier($query) {

		// Not an update.
		if (!isset($query['update'])) {
			// Don't log and don't break Update Manager.
			return false;
		}

		// Check what we are dealing with.
		if (in_array($query['update'], ['query_themes', 'theme_information'])) {
			// We are dealing with a theme.
			if (!$this->is_safe_theme_slug($query['theme'])) {
				return false;
			}
			$identifier = $query['theme'];
		}

		if (in_array($query['update'], ['plugin_information', 'query_plugins'])) {
			// We are dealing with a plugin.
			if (!$this->is_safe_plugin_slug($query['plugin'])) {
				return false;
			}
			$identifier = $query['plugin'];
		}

		// At this point $identifier should be set.
		if (!isset($identifier)) {
			return false;
		}

		return $identifier;

	}

	// Log requests to the db.
	// $query have to be always returned unchanged.
	public function log_request($query) {

		// Get identifier and bail if it's not good.
		if (($identifier = $this->get_identifier($query)) === false) {
			// Don't log and don't break Update Manager.
			return $query;
		}

		// Exit if it's called twice.
		if (in_array($identifier, $this->lastseen, true)) {
			// Don't log and don't break Update Manager.
			return $query;
		}
		$this->lastseen[] = $identifier;

		// Bad url, don't log.
		if (!$this->is_safe_url($query['site_url'])) {
			// Don't log and don't break Update Manager.
			return $query;
		}

		// Parse options from request.
		if (isset($query['sfum'])) {
			$this->options = explode(',', $query['sfum']);
		}

		// Allow opt-out.
		if (in_array('no-log', $this->options)) {
			// Don't log and don't break Update Manager.
			return $query;
		}

		// Prevent specific(s) plugin to be logged.
		if (in_array($identifier, apply_filters('sfum_exclude', []))) {
			// Don't log and don't break Update Manager.
			return $query;
		}

		$hashed = hash('sha512', $query['site_url']);

		global $wpdb;

		$where = [
			'site' => $hashed,
			'slug' => $identifier,
			];

		$data = [
			'site' => $hashed,
			'slug' => $identifier,
			'last' => current_time('mysql', 1),
			];

		// Update the site/slug last seen time.
		if ($wpdb->update($wpdb->prefix.DB_TABLE_NAME, $data, $where)) {
			return $query;
		}

		// New site/slug... insert.
		$wpdb->insert($wpdb->prefix.DB_TABLE_NAME, $data);

		// Return unchanged.
		return $query;
	}

	// Check that the slug is in the correct form.
	private function is_safe_plugin_slug($slug) {
		// Is defined, looks like a good slug and is not too long.
		return isset($slug) && (bool) preg_match('/^[a-zA-Z0-9\-\_]*\/[a-zA-Z0-9\-\_]*\.php$/', $slug) && (strlen($slug) <= 100);
	}

	private function is_safe_theme_slug($slug) {
		// Is defined, looks like a good slug and is not too long.
		return isset($slug) && (bool) preg_match('/^[a-zA-Z0-9\-\_]*$/', $slug) && (strlen($slug) <= 100);
	}

	// Check that the url is in the correct form.
	private function is_safe_url($url) {
		// We don't care too much here because it's hashed early.
		return isset($url) && is_string($url) && (bool) preg_match('/^http(s)?:\/\//', $url);
	}

	private function is_theme($id) {
		return get_post_type($id) === UM_CPT_THEMES;
	}

	// Register Statistics submenu.
	public function create_menu() {

		if (current_user_can('manage_options')) {
			if (version_compare($this->um_version, '1.9999.0', '>')) {
				// Correct menu for Update Manager 2.0.0-rcX+.
				$parent_slug = UM_PAGE;
			} else {
				// Keep compatibility with UM <2.0.0
				$parent_slug = 'edit.php?post_type='.UM_CPT_PLUGINS;
			}

			$this->screen = add_submenu_page(
				$parent_slug,
				esc_html_x('Statistics for Update Manager', 'Page Title', 'stats-for-update-manager'),
				esc_html_x('Statistics', 'Menu Title', 'stats-for-update-manager'),
				'manage_options',
				MENU_SLUG,
				[$this, 'render_page']
			);
		}

	}

	// Enqueue CSS for debug section only in the page and only if WP_DEBUG is true.
	public function backend_css($hook) {
		if ($hook === $this->screen && defined('WP_DEBUG') && WP_DEBUG === true) {
			wp_enqueue_style('sfum_statistics', plugin_dir_url(__FILE__).'css/sfum-backend.css', [], '1.1.0');
		}
	}

	// Render statistics page.
	public function render_page() {

		// Title.
		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline" style="margin-bottom:10px;">'.esc_html_x('Update Manager &#8211; Statistics', 'Page Title', 'stats-for-update-manager').'</h1>';

		// Render list table.
		$statistics = $this->get_statistics();
		$ListTable = new SFUM_List_Table();
		$ListTable->load_items($statistics);
		$ListTable->prepare_items();
		$ListTable->display();

		// Show debug information if WP_DEBUG is true.
		if (defined('WP_DEBUG') && WP_DEBUG === true) {
			$this->render_page_debug();
		}

		echo '</div>';

	}

	// Get an array of statistics that can be passed to SFUM_List_Table.
	private function get_statistics() {
		$items = [];

		// Get CPTs and query database for statistics.
		$um_posts = $this->get_cpt();
		global $wpdb;
		$active = $wpdb->get_results('SELECT slug, count(*) as total FROM '.$wpdb->prefix.DB_TABLE_NAME.' WHERE last > NOW() - '.$this->db_unactive_entry.' group by slug');

		// Loop through results and build an array.
		foreach ($active as $value) {

			// Not in Update manager... skip
			if (!isset($um_posts[$value->slug])) {
				continue;
			}

			$items[] = [
				'identifier' => $value->slug,
				'name'       => get_the_title($um_posts[$value->slug]),
				'id'         => $um_posts[$value->slug],
				'count'      => (int)$value->total,
				'type'       => $this->is_theme($um_posts[$value->slug]) ? esc_html__('Theme', 'stats-for-update-manager') : esc_html__('Plugin', 'stats-for-update-manager'),
			];
		}

		return $items;
	}

	// Render the debug section of the page.
	private function render_page_debug() {
		global $wpdb;
		$last = $wpdb->get_results(
			'SELECT slug, site, last FROM '.$wpdb->prefix.DB_TABLE_NAME.' ORDER BY last DESC LIMIT 100'
		);
		if (count($last) === 0) {
			echo '<p>'.esc_html__('No database entries.', 'stats-for-update-manager').'<p>';
			return;
		}
		// Display debug information.
		echo '<div class="wrap-collabsible"><input id="collapsible" class="toggle" type="checkbox">';
		echo '<label for="collapsible" class="lbl-toggle">'.esc_html__('Debug information', 'stats-for-update-manager').'</label>';
		echo '<div class="collapsible-content"><div class="content-inner">';
		echo '<h2>'.esc_html__('Latest updates', 'stats-for-update-manager').'</h2>';
		echo '<pre>';
		printf('%-32s %-21s %s<br>', __('FIRST 30 CHAR OF THE HASH', 'stats-for-update-manager'), __('DATE', 'stats-for-update-manager'), __('PLUGIN/THEME', 'stats-for-update-manager'));
		foreach ($last as $value) {
		/* translators: %1 is plugin slug, %2 is the number of active installations */
			printf('%-32s %-21s %s', substr($value->site, 0, 30), date('Y/m/d H:i:s', strtotime($value->last)), $value->slug);
			if (in_array($value->site, apply_filters('sfum_my_sites', []))) {
				echo ' *';
			}
			echo '<br>';
		}
		echo '</pre></p></div></div></div>';
	}

	// Add link to statistic page in plugins page.
	public function pal($links) {
		if (version_compare($this->um_version, '1.9999.0', '>')) {
			$destination = admin_url('admin.php?page='.MENU_SLUG);
		} else {
			// Keep compatibility with UM <2.0.0
			$destination = admin_url('edit.php?post_type='.UM_CPT_PLUGINS.'&page='.MENU_SLUG);
		}

		$link = '<a href="'.$destination.'" title="'.esc_html__('Update Manager statistics', 'stats-for-update-manager').'"><i class="dashicon dashicons-chart-bar" style="font: 16px dashicons;vertical-align: text-bottom;"></i></a>';

		array_unshift($links, $link);
		return $links;
	}

	// Add footer text.
	public function filter_footer_text($text) {
		$text = '<a href="'.GITHUB_PAGE.'/" title="Stats for Update Manager">Stats for Update Manager</a> '.VERSION;
		$text .= ' &#8211; by <a href="'.SW_PAGE.'" title="Gieffe edizioni">Gieffe edizioni</a>';
		return $text;
	}

	// Delete old entries.
	public function clean_table() {
		global $wpdb;
		$wpdb->query('DELETE FROM '.$wpdb->prefix.DB_TABLE_NAME.' WHERE last < NOW() - '.$this->db_old_entry);
	}

	// Load text domain.
	public function text_domain() {
		load_plugin_textdomain('stats-for-update-manager', false, basename(dirname(__FILE__)).'/languages');
	}

	// Activation hook.
	public function activate() {
		// Create or update database structure.
		global $wpdb;
		$table_name = $wpdb->prefix.DB_TABLE_NAME;
		$wpdb_collate = $wpdb->collate;
		$sql =
			"CREATE TABLE {$table_name} (
			site CHAR(128),
			slug VARCHAR(100),
			last DATETIME,
			UNIQUE KEY siteslug (site,slug)
			)
			COLLATE {$wpdb_collate}";
		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		// In the future HERE do something interesting with db version.

		// Register database version for a future use.
		update_option('sfum_db_ver', DB_REVISION);
	}

	// Deactivation hook.
	public function deactivate() {
		// Unschedule cron.
		$timestamp = wp_next_scheduled('sfum_clean_table');
		wp_unschedule_event($timestamp, 'sfum_clean_table');
	}

	// Uninstall hook.
	public static function uninstall() {
		// Delete table.
		global $wpdb;
		$table_name = $wpdb->prefix.DB_TABLE_NAME;
		$sql = 'DROP TABLE IF EXISTS '.$table_name.';';
		$wpdb->query($sql);
		// Delete options.
		delete_option('sfum_db_ver');
	}

	public function auto_deactivate() {
		deactivate_plugins(plugin_basename(__FILE__));
		if (isset($_GET['activate'])) {
			unset($_GET['activate']);
		}
	}

	// Register privacy policy.
	public function privacy() {
		$content = sprintf(
			esc_html__(
			'
				This plugin stores data about plugins/themes update requests in a table.

				The table structure contains:
				%1$s
				%2$sURL of the site asking for updates, sha512 hashed%3$s
				%4$splugin/theme checked%5$s
				%6$stimestamp of the last check%7$s
				%8$s
				This data is kept %9$sXX%10$s days.

				%11$sTo help us know the number of active installations of this plugin,
				we collect and store anonymized data when the plugin check in for
				updates. The date and unique plugin identifier are stored as plain
				text and the requesting URL is stored as a non-reversible hashed
				value. This data is stored for up to 28 days.%12$s
				',
				'stats-for-update-manager'
			),
			'<ul style="list-style-type: disc; list-style-position: inside">',
			'<li>',
			'</li>',
			'<li>',
			'</li>',
			'<li>',
			'</li>',
			'<ul>',
			'<span style="color:red;">',
			'</span>',
			'<strong>',
			'</strong>'
		);

		$content = wpautop($content, false);
		wp_add_privacy_policy_content('Stats for Update Manager', $content);

	}

}

// Fire up...
$sfum_instance = new StatsForUpdateManager;
