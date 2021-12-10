=== Stats for Update Manager ===
Plugin Name:        Stats for Update Manager
Description:        With Stats for Update Manager you can count active installations of your plugins and themes.
Version:            1.4.1
Text Domain:        stats-for-update-manager
Domain Path:        /languages
Requires PHP:       5.6
Requires:           1.0.0
Tested:             4.9.99
Author:             Gieffe edizioni
Author URI:         https://www.gieffeedizioni.it
Plugin URI:         https://software.gieffeedizioni.it
Download link:      https://github.com/xxsimoxx/stats-for-update-manager/releases/download/v1.4.1/stats-for-update-manager-1.4.1.zip
License:            GPLv2
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

With Stats for Update Manager you can count active installations of your plugins and themes.

== Description ==

# Discover how many sites are using your plugins and themes!

With Stats for Update Manager you can count active installations of your plugins and themes.

This is a companion plugin for [**Update Manager**](https://software.gieffeedizioni.it/plugin/update-manager).

You'll find a new submenu, *Statistics*, under the *Update Manager* menu.

It's compatible with Update Manager versions 1.x and 2.x.

Plugins and themes that queried Update Manager at least once in the last week are considered active. When a plugin/theme have not queried Update Manager in the last 4 week it's removed from the database table. See FAQ section to tweak.

You can reset the count using the *Reset* row action.

It also add the number of active installations in the plugin details. See FAQ section to tweak.

It supports WP-CLI. See the help typing:
* `wp help statistics show` (get stats from WP-CLI)
* `wp help statistics purge` (delete all logs)
* `wp help statistics delete` (delete a specific plugin data)

## GDPR & Disclaimer

This plugin is intended to be used by *developers*.

This plugin stores data about plugins/themes update requests in a table.
You can configure how much time this data is kept using `sfum_old_after` filter (defaults to 4 weeks).

The table structure contains:
* URL of the site asking for updates, sha512 hashed
* plugin/theme checked
* timestamp of the last check

**Is up to you to decide if and to inform your plugin/theme users that this data is kept.**

**To help us know the number of active installations of this (Stats for Update Manager) plugin, we collect and store anonymized data when the plugin check in for updates. The date and unique plugin identifier are stored as plain text and the requesting URL is stored as a non-reversible hashed value. This data is stored for up to 28 days.**

To skip Stats for Update Manager from logging, the plugin/theme have to ask for updates defining, in the body of the request done by UpdateClient, `$body['sfum']='no-log'`.
Since version 2.2.0 of Update Manager client this can be done using a filter.

== Frequently asked questions ==

# Shortcodes 

### [sfum-installs]

The above shortcode returns an integer depicting the total number of all installations (of all plugins and themes) across the web. Developers can use it in a sentence:
> Our plugins have [_n_] active installations!

### [sfum-installs id="my-plugin-folder/my-plugin-file.php"]

Building on the previous example, you can also provide a plugin or theme id. This shortcode returns an integer depicting the total number of installs for the plugin with the given `identifier`. Developers can use it in a sentence:
> My Awesome Plugin has [_n_] active installations!
> My Amazing Theme has [_n_] active installations!

### [sfum-domains]

This shortcode returns an integer depicting the number of unique domains using all of the developer's plugins and themes. Developers can use it in a sentence: 

> Code Potent is running on [_n_] sites!

# Filters

### Change/hide the number of active installations in plugin info tab

`sfum_active_installations` let's you change/hide the number displayed in the details of your plugins.
Examples:
Add the filter...
`add_filter('sfum_active_installations', 'example_filter');`
Don't show active installation for my nothing-to-see plugin and raise it to one million for boost!
```php
	function example_filter($ar){
		unset ($ar['nothing-to-see/nothing-to-see.php']);
		$ar['boost/boost.php'] = 1000000;
		return $ar;
	}
```

Or simply disable it all
```php
	function example_filter($ar){
		return [];
	}
```

Note: the real number is cached for 6 hours.

### Add opt-in or opt-out for your plugin users
Supported by Update Manager Client since 2.3.0. _Note: this filter is from Update Manager, not from Stats for Update Manager._
**`codepotent_update_manager_filter_{$slug}_client_request`** let's you inject data in the request made by UpdateClient.class.php.

Example:

```php
add_filter('codepotent_update_manager_filter_my-slug/my-slug.php_client_request', 'example_filter');

function example_filter($body){
	if( 'no' === get_option( 'my-slug-usage-statistics' ) ) {
		$body['sfum'] = 'no-log';
	}
	return $body;
}
```

### Recognize your own sites in debug
_Note that debug information is displayed only if `WP_DEBUG` is set to true_.

`sfum_my_sites` let's you recognize your own sites. They will be marked with an * in the debug informations.
With this filter you can populate an array of sha512-hashed urls.
Example:
```php
add_filter('sfum_my_sites', 'all_my_sites');

function all_my_sites($sha) {
	$mysites = [
		'https://my-first-site.dog',
		'https://www.my-second-site.dog'
		];
	
	$myhashes = array_map(function($value){
		return hash('sha512', $value);
	}, $mysites);
	
	return $myhashes;
}
```

### Configure the timing a plugin/theme is considered active or stale

**`sfum_inactive_after`** let's you configure the number of days before a plugin/theme installations is considered inactive.

**`sfum_old_after`** let's you configure the number of days before a plugin/theme installations is considered stale and will be removed from the database.

*Note that filtering `sfum_inactive_after` to 0 will erase your database when the daily maintenence cronjob is executed.*

Example:
```php
// An entry is old after 2 days and will be removed after 7
add_filter('sfum_inactive_after', 'return_two');
add_filter('sfum_old_after', 'return_seven');

function return_two($days) {
	return 2;
}
function return_seven($days) {
	return 7;
}

```

### Prevent specific(s) plugin(s)/theme(s) to be logged

**`sfum_exclude`** let's you configure an array of identifier that are excluded from logging.

*Note: this don't clean your database from already logged ones.*

Example:
```php
// Don't log those plugins
add_filter('sfum_exclude', 'no_log_please');

function no_log_please($list) {
	$excluded = [
		'please-dont/log-me.php',
		'excluded-plugin/excluded-plugin.php',
		'my-beautiful-theme'
	];
	return $excluded;
}

```

# REST API

To get a list of endpoint identifier and active installations you can access `wp-json/stats/v1/stats/`.
To enable this, add to `wp-config.php` this line:
```php
define( 'SFUM_ENABLE_REST', true );
```

*Note that the value of active installation is affected by the [filter](#filters) `sfum_active_installations`.*

== Changelog ==
= 1.4.1 =
* Fixed REST-API for CP v. 1.4.0 [#732](https://github.com/ClassicPress/ClassicPress/pull/732) 
* Add some unescaping
* Update Update Client

= 1.4.0 =
* Document support for new filter in UM 2.3.0
* Added icons
* Deprecated REST support
* Restyle

= 1.3.1 =
* Leave cron in place even if UM is not running

= 1.3.0 =
* When Update Manager is deactivate, silently do nothing
* New code styling

= 1.2.0 =
* Added the action to reset a counter
* Http and https on the same site now are counted as one
* Fix a bug with a filter not working properly if database empty
* Deprecated compatibility with Update Manager < 2.0.0

= 1.1.2 =
* Prevent duplicate database query
* Removed unuseful transient
* Fixed a bug when Stats for Update Manager is loaded before Update Manager

= 1.1.1 =
* Fixed "Active installation" column that was not ordering properly

= 1.1.0 =
* Debug info only shown when WP_DEBUG is true
* GUI closer to Update Manager look
* Plugin is now deactivated if Update Manager is not running

= 1.1.0-rc1 =
* Added statistics for themes: support for Update Manager 2.0.0
* Added basic REST API support
* Updated UpdateClient.class.php
* Better WP-CLI comments

= 1.0.0 =
* Added statistics delete to WP-CLI
* Added statistics show --date to WP-CLI
* Code cleanup

= 1.0.0-rc3 =
* Added wp statistics purge
* Moved WP-CLI code to a separate class
* Added a filter to prevent specific(s) plugin(s) to be logged
* Added privacy
* Added a check to skip logging specific requests

= 1.0.0-rc2 =
* Added shortcodes
* Added WP-CLI
* Added filters to change when a plugin installation is considered active

= 1.0.0-rc1 =
* First release candidate

== Screenshots ==
1. Main page.