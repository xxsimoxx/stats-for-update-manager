<?php
/**
 * Plugin Name: Stats for Update Manager
 * Plugin URI: https://software.gieffeedizioni.it
 * Description: Statistics for Update Manager by CodePotent.
 * Version: 1.0.0-rc2
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

// Load constants.
require_once('includes/constants.php');

// Add auto updater https://codepotent.com/classicpress/plugins/update-manager/
require_once('classes/UpdateClient.class.php');

// Shortcodes.
require_once('classes/Shortcodes.class.php');

class StatsForUpdateManager{

	// Initialize variables

	// Time (in SQL format) for the plugin to be considered installed.
	private $db_unactive_entry = '';

	// Time (in SQL format) for the record to be deleted.
	private $db_old_entry = '';

	// Is Update Manager running?
	private $um_running = false;

	// Array to keep statistics for plugin details.
	private $stat_array = [];

	public function __construct() {

		// Check for Update Manager running.
		if (!class_exists(UM_CLASS)) {
			add_action('admin_notices', [$this, 'um_missing']);
		} else {
			$this->um_running = true;
		}

		// Load text domain.
		add_action('plugins_loaded', [$this, 'text_domain']);

		// Hook to Update Manager filter request.
		add_filter(UM_HOOK, [$this, 'log_request'], PHP_INT_MAX);

		// On init apply filters to set the number of days for an entry
		// to be considered inactive or have to be removed from db.
		add_action('init', [$this, 'apply_timing_filters']);

		// Populate active installations.
		add_action('init', [$this, 'active_installations_filters'], PHP_INT_MAX);

		// Add menu	for statistics.
		add_action('admin_menu', [$this, 'create_menu']);
		add_action('admin_enqueue_scripts', [$this, 'backend_css']);
		add_filter('admin_footer_text', [$this, 'change_footer_text'], PHP_INT_MAX);

		// Add a button that links to statistics in plugins page.
		add_filter('plugin_action_links_'.plugin_basename(__FILE__), [$this, 'pal']);

		// Add a cron to clean table.
		add_action('sfum_clean_table', [$this, 'clean_table']);
		if (!wp_next_scheduled('sfum_clean_table')) {
			wp_schedule_event(time(), 'daily', 'sfum_clean_table');
		}

		// Add "statistics" command to WP-CLI
		if (defined( 'WP_CLI' ) && WP_CLI){
			\WP_CLI::add_command('statistics', [$this, 'wpcli_statistics']);
		}

		// Activation, deactivation and uninstall.
		register_activation_hook(__FILE__, [$this, 'activate']);
		register_deactivation_hook(__FILE__, [$this, 'deactivate']);
		register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);

	}

	// Helpful? in developement.
	private function test($x) {
		 trigger_error(print_r($x, TRUE), E_USER_WARNING);
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
			// This will work after https://github.com/codepotent/Update-Manager/pull/20
			add_filter('codepotent_update_manager_'.$slug.'_active_installs', [$this, 'active_installations_filter'], 10, 2);
		}
	}

	// Get active installations array.
	private function active_installations_populate() {
		// Check if we have already a transient with needed data.
		if(!($all_stats = get_transient('sfum_all_stats'))) {
			// Build an array in the form 'slug'->count.
			$all_stats = [];
			global $wpdb;
			$results = $wpdb->get_results('SELECT slug, count(*) as total FROM '.$wpdb->prefix.DB_TABLE_NAME.' WHERE last > NOW() - '.$this->db_unactive_entry.' group by slug', 'ARRAY_A');
			foreach ($results as $result){
				$all_stats[$result['slug']]=$result['total'];
			}
			// Save it all for 6 hours.
			set_transient('sfum_all_stats', $all_stats, 6 * HOUR_IN_SECONDS );
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

	// Notice for Update Manager missing.
	public function um_missing() {
		if (!current_user_can('manage_options') || (defined('WP_CLI') && WP_CLI)) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen->id !== 'tools_page_sfum_statistics') {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		/* translators: 1 is the link to Update Manager homepage */
		printf(__('<b>Stats for Update Manager</b> is pretty unuseful without <a href="%1$s" target="_blank">Update Manager</a>.', 'stats-for-update-manager'), UM_LINK);
		echo '</p></div>';
	}

	// Get associative array to resolve Endpoint Identifier/Post ID.
	private function get_cpt() {
		$data = [];
		if (!$this->um_running) {
			return $data;
		}
		$posts = get_posts([
			'post_type' => UM_CPT,
			'post_status' => ['publish', 'pending' ,'draft'],
			'numberposts' => -1
			]);
		foreach($posts as $post) {
			$meta = get_post_meta($post->ID, 'id', true);
			$data[$meta] = $post->ID;
		}
		return $data;
	}

	// Log requests to the db.
	public function log_request($query) {
		// If the input is corrupted, don't log.
		if(!$this->is_safe_slug($query["plugin"]) || !$this->is_safe_url($query["site_url"])) {
			// Don't break Update Manager if something changes.
			return $query;
		}

		global $wpdb;
		$where = [
			'site' => hash('sha512', $query["site_url"]),
			'slug' => $query["plugin"],
			];
		$data      = [
			'site' => hash('sha512', $query["site_url"]),
			'slug' => $query["plugin"],
			'last' => current_time('mysql', 1)
			];

		if (!$wpdb->update( $wpdb->prefix.DB_TABLE_NAME, $data, $where)) {
			// Ensure that if the log_request is called twice in the same second
			// we don't get a SQL error
			$wpdb->delete($wpdb->prefix.DB_TABLE_NAME, $where);
			$wpdb->insert($wpdb->prefix.DB_TABLE_NAME, $data);
		}

		// Return unchanged.
		return $query;
	}

	// Check that the slug is in the correct form.
	private function is_safe_slug($slug) {
		// Is defined, looks like a good slug and is not too long.
		return isset($slug) && (bool) preg_match('/^[a-zA-Z0-9\-\_]*\/[a-zA-Z0-9\-\_]*\.php$/', $slug) && (strlen($slug) <= 100);
	}

	// Check that the url is in the correct form.
	private function is_safe_url($url) {
		// We don't care too much here because it's hashed early.
		return isset($url) && is_string($url);
	}

	// Register Statistics submenu.
	public function create_menu() {
		if ( current_user_can( 'manage_options' ) ) {
			// If Update Manager is not there, go under "tools" menu.
			$parent_slug = $this->um_running ? 'edit.php?post_type='.UM_CPT : 'tools.php';
			$menu_title  = $this->um_running ? esc_html_x('Statistics', 'Menu Title', 'stats-for-update-manager') : esc_html_x('Statistics for Update Manager', 'Menu Title with UM deactivated', 'stats-for-update-manager');
			$page = add_submenu_page(
				$parent_slug,
				esc_html_x('Statistics for Update Manager', 'Page Title', 'stats-for-update-manager'),
				$menu_title,
				'manage_options',
				'sfum_statistics',
				[$this, 'render_page'],
				PHP_INT_MAX
			);
		}
	}

	// Enqueue CSS only in the page.
	public function backend_css($hook) {
		//             When UM disabled.                                       When UM enabled.
		if ($hook === 'tools_page_sfum_statistics' || $hook === UM_CPT.'_page_sfum_statistics' ) {
			wp_enqueue_style('sfum_statistics', plugin_dir_url(__FILE__).'/css/sfum-backend.css', [], '1.0.0');
		}
	}

	// Render statistics page.
	public function render_page() {
		if (!$this->um_running){
			$this->render_page_debug();
			return;
		}

		// Get needed data.
		$um_posts = $this->get_cpt();
		global $wpdb;
		$active = $wpdb->get_results('SELECT slug, count(*) as total FROM '.$wpdb->prefix.DB_TABLE_NAME.' WHERE last > NOW() - '.$this->db_unactive_entry.' group by slug');

		// Display statistics.
		echo '<h1>'.esc_html__('Active installations', 'stats-for-update-manager').'</h1>';
		// Exit if query returned 0 results.
		if (count($active) === 0){
			echo '<p>'.esc_html__('No active installations.', 'stats-for-update-manager').'<p>';
			$this->render_page_debug();
			return;
		}

		// Sort by most active.
		usort($active, function($a, $b) {
			return $b->total - $a->total;
		});

		echo '<ul class="sfum-list">';
		foreach ($active as $value){
			// If there is a request for a plugin not served by UM don't display.
			if (isset($um_posts[$value->slug])){
				$title = '<a href="'.admin_url('post.php?post='.$um_posts[$value->slug].'&action=edit').'">'.get_the_title($um_posts[$value->slug]).'</a>';
				/* Translators: %1 is plugin name, %2 is the number of active installations */
				printf('<li>'.esc_html(_n('%1$s has %2$d active installation.', '%1$s has %2$d active installations.', $value->total, 'stats-for-update-manager')).'</li>' , $title, $value->total);
			}
		}
		echo '</ul>';

		// Display the debug section.
		$this->render_page_debug();
	}

	// Render the debug section of the page.
	private function render_page_debug() {
		global $wpdb;
		$last = $wpdb->get_results(
			'SELECT slug, site, last FROM '.$wpdb->prefix.DB_TABLE_NAME.' ORDER BY last DESC LIMIT 100' );
		if (count($last) === 0){
			echo '<p>'.esc_html__('No database entries.', 'stats-for-update-manager').'<p>';
			return;
		}
		// Display debug information.
		echo '<div class="wrap-collabsible"><input id="collapsible" class="toggle" type="checkbox">';
		echo '<label for="collapsible" class="lbl-toggle">'.esc_html__('Debug information', 'stats-for-update-manager').'</label>';
  		echo '<div class="collapsible-content"><div class="content-inner">';
  		echo '<h2>'.esc_html__('Latest updates', 'stats-for-update-manager').'</h2>';
		echo '<pre>';
		printf('%-32s %-21s %s<br>', __("FIRST 30 CHAR OF THE HASH", 'stats-for-update-manager'), __("DATE", 'stats-for-update-manager'), __("PLUGIN", 'stats-for-update-manager'));
		foreach ($last as $value){
		/* translators: %1 is plugin slug, %2 is the number of active installations */
			printf('%-32s %-21s %s', substr($value->site, 0, 30), date('Y/m/d H:i:s', strtotime($value->last)), $value->slug);
			if (in_array($value->site, apply_filters('sfum_my_sites', []))){
				echo " *";
			}
			echo "<br>";
		}
		echo '</pre></p></div></div></div>';
	}

	// Change footer text in statistic section.
	public function change_footer_text($text) {
		$screen = get_current_screen();
		if ($screen->base === UM_CPT.'_page_sfum_statistics') {
			$text = '<span><a href="'.XXSIMOXX_LINK.'" target="_blank">'.esc_html__('Statistics for Update Manager', 'stats-for-update-manager').'</a> v.'.PLUGIN_VERSION.'</span>';
		}
		return $text;
	}

	// Add link to statistic page in plugins page.
	public function pal($links) {
		if(!$this->um_running){
			$link = '<a href="'.admin_url('tools.php?page=sfum_statistics').'" title="'.esc_html__('Update Manager statistics', 'stats-for-update-manager').'">'.esc_html_x('Statistics', 'Menu Title', 'stats-for-update-manager').' <i class="dashicon dashicons-warning"></i> </a>';
			array_unshift($links, $link);
			return $links;
		}
		$link = '<a href="'.admin_url('edit.php?post_type='.UM_CPT.'&page=sfum_statistics').'" title="'.esc_html__('Update Manager statistics', 'stats-for-update-manager').'"><i class="dashicon dashicons-chart-bar"></i></a>';
		array_unshift($links, $link);
		return $links;
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
	public function wpcli_statistics($args, $assoc_args) {
		// Check for UM running.
		if (!$this->um_running) {
			return \WP_CLI::error('Update Manager is not running.');
		}
		// Use option from command line or default for days.
		if (!is_numeric($timing=\WP_CLI\Utils\get_flag_value($assoc_args, 'days'))){
			$timing = $this->db_unactive_entry;
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
		$cpt = $this->get_cpt();
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
			return $b['active'] - $a['active'];
		});
		// Display results using buildin WP CLI function.
		\WP_CLI\Utils\format_items(
			\WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'table'),
			$results,
			\WP_CLI\Utils\get_flag_value($assoc_args, 'fields', 'ID,title,active,identifier,status')
		);
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
		dbDelta( $sql );

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
		$sql = "DROP TABLE IF EXISTS $table_name;";
		$wpdb->query($sql);
		// Delete options.
		delete_option('sfum_db_ver');
	}

};

// Fire up...
new StatsForUpdateManager;
