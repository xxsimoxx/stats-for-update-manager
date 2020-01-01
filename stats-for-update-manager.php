<?php
/**
 * Plugin Name: Stats for Update Manager
 * Plugin URI: https://software.gieffeedizioni.it
 * Description: Statistics for Update Manager by CodePotent.
 * Version: 1.0.0-rc1
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

// Add auto updater
// https://codepotent.com/classicpress/plugins/update-manager/
require_once('classes/UpdateClient.class.php');

// Define table name.
const DB_TABLE_NAME = 'sfum_logs';

class StatsForUpdateManager{

	// DB table name without prefix.
	private $db_table_name = DB_TABLE_NAME;
	
	// Time (in SQL format) for the plugin to be considered installed.
	private $db_unactive_entry = 'INTERVAL 1 WEEK';

	// Time (in SQL format) for the record to be deleted.
	private $db_old_entry = 'INTERVAL 4 WEEK';	
	
	// Delete SQL table when uninstalling?
	private $db_remove_on_uninstall = true;	
	
	// Database schema version (for future use).
	private $db_revision = 1;
	
	// Link to our plugins on GitHub.
	private $xxsimoxx_link = 'https://github.com/xxsimoxx?tab=repositories';
	
	// Link to Update Manager homepage.
	private $um_link = 'https://codepotent.com/classicpress/plugins/update-manager/';

	// Update Manager class.
	private $um_class = '\CodePotent\UpdateManager\UpdateManager';
	
	// Update Manager hook.	
	private $um_hook = 'codepotent_update_manager_filter_request';
	
	// Update Manager custom post type name.
	private $um_cpt = 'plugin_endpoint';
	
	// Is Update Manager running?
	private $um_running = false;
	
	// Array to keep statistics for plugin details.
	private $stat_array = [];
	
	
	public function __construct() {

		// Check for Update Manager running.
		if (!class_exists($this->um_class)) {
			add_action('admin_notices', [$this, 'um_missing']);
		} else {
			$this->um_running = true;
		}
		
		// Load text domain.
		add_action('plugins_loaded', [$this, 'text_domain']);

		// Hook to Update Manager filter request.
		add_filter($this->um_hook, [$this, 'log_request'], PHP_INT_MAX);
		
		// Populate active installations.
		add_action('init', [$this, 'active_installations_filters']);
		
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

		// Activation, deactivation and uninstall.
		register_activation_hook(__FILE__, [$this, 'activate']);
		register_deactivation_hook(__FILE__, [$this, 'deactivate']);
		register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);
				
	}

	// Helpful? in developement.
	private function test($x) {
		 trigger_error(print_r($x, TRUE), E_USER_WARNING);
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
		if(true || !($all_stats = get_transient('sfum_all_stats'))) {
			// Build an array in the form 'slug'->count.
			$all_stats = [];
			global $wpdb;
			$results = $wpdb->get_results('SELECT slug, count(*) as total FROM '.$wpdb->prefix.$this->db_table_name.' WHERE last > NOW() - '.$this->db_unactive_entry.' group by slug', 'ARRAY_A');
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
		echo '<div class="notice notice-warning"><p>';
		/* translators: 1 is the link to Update Manager homepage */
		printf(__('<b>Stats for Update Manager</b> is pretty unuseful without <a href="%1$s" target="_blank">Update Manager</a>.', 'stats-for-update-manager'), $this->um_link);
		/* translators: 1 is the link to the Statistics page */
		printf(__('<br>You can view statistics under tools menu or by clicking <a href="%1$s">here</a>.', 'stats-for-update-manager'), admin_url('tools.php?page=sfum_statistics'));		
		echo '</p></div>';
	}
	
	// Get associative array to resolve Endpoint Identifier/Post ID.
	private function get_cpt() {
		$data = [];
		if (!$this->um_running) {
			return $data;
		}
		$posts = get_posts([
			'post_type' => $this->um_cpt,
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
		
		$updated = $wpdb->update( $wpdb->prefix.$this->db_table_name, $data, $where );
 
		if ($updated === 0) {
			$wpdb->insert($wpdb->prefix.$this->db_table_name, $data);
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
			$parent_slug = $this->um_running ? 'edit.php?post_type='.$this->um_cpt : 'tools.php';
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
		if ($hook === 'tools_page_sfum_statistics' || $hook === $this->um_cpt.'_page_sfum_statistics' ) {
			wp_enqueue_style('sfum_statistics', plugin_dir_url(__FILE__).'/css/sfum-backend.css');
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
		$active = $wpdb->get_results('SELECT slug, count(*) as total FROM '.$wpdb->prefix.$this->db_table_name.' WHERE last > NOW() - '.$this->db_unactive_entry.' group by slug');

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
			'SELECT slug, site, last FROM '.$wpdb->prefix.$this->db_table_name.' ORDER BY last DESC LIMIT 100' );
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
		if ($screen->base === $this->um_cpt.'_page_sfum_statistics') {
			$text = '<span><a href="'.$this->xxsimoxx_link.'" target="_blank">'.esc_html__('Statistics for Update Manager', 'stats-for-update-manager').'</a></span>';
		}
		return $text;
	}
	
	// Add link to statistic page in plugins page.
	public function pal($links) {
		if(!$this->um_running){
			return $links;
		}
		$link = '<a href="'.admin_url('edit.php?post_type='.$this->um_cpt.'&page=sfum_statistics').'" title="'.esc_html__('Update Manager statistics', 'stats-for-update-manager').'"><i class="dashicon dashicons-chart-bar"></i></a>';
		array_unshift($links, $link);
		return $links;
	}
	
	// Delete old entries.
	public function clean_table() {
		global $wpdb;
		$wpdb->query('DELETE FROM '.$wpdb->prefix.$this->db_table_name.' WHERE last < NOW() - '.$this->db_old_entry);
	}	
	
	// Load text domain.
	public function text_domain() {
		load_plugin_textdomain('stats-for-update-manager', false, basename(dirname(__FILE__)).'/languages'); 
	}

	// Activation hook.
	public function activate() {
		// Create or update database structure.
		global $wpdb;
		$table_name = $wpdb->prefix.$this->db_table_name;
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
		update_option('sfum_db_ver', $this->db_revision);
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
