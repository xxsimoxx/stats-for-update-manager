=== Stats for Update Manager ===
Plugin Name:        Stats for Update Manager
Description:        With Stats for Update Manager you can count active installations of your plugins.
Version:            1.0.0-rc2
Text Domain:        stats-for-update-manager
Domain Path:        /languages
Requires PHP:       5.6
Requires:           1.0.0
Tested:             4.9.99
Author:             Gieffe edizioni
Author URI:         https://www.gieffeedizioni.it
Plugin URI:         https://software.gieffeedizioni.it
Download link:      https://github.com/xxsimoxx/stats-for-update-manager/releases/download/v1.0.0-rc2/stats-for-update-manager.zip
License:            GPLv2
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

With Stats for Update Manager you can count active installations of your plugins.

== Description ==

With Stats for Update Manager you can count active installations of your plugins.

This is a companion plugin for [**Update Manager**](https://codepotent.com/classicpress/plugins/) from [CodePotent](https://codepotent.com/).

You'll find a new submenu, *Statistics*, under the *Update Manager* menu.

Plugins that queried Update Manager at least once in the last week are considered active. When a plugin have not queried Update Manager in the last 4 week it's removed from the database table. See FAQ section to tweak.

It also add the number of active installations in the plugin details. See FAQ section to tweak.

It supports WP-CLI (see the help typing `wp help statistics`).

## GDPR & Disclaimer

This plugin is intended to be used by *developers*.

This plugin stores data about plugins update requests in a table.
You can configure how much time this data is kept using `sfum_old_after` filter (defaults to 4 weeks).

The table structure contains:
* URL of the site asking for updates, sha512 hashed
* plugin checked
* timestamp of the last check

**Is up to you to decide if and to inform your plugin users that this data is kept.**

*This plugin itself is sending such information to the developer to keep statistical usage information.*

== faq ==

## Filters

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

**`sfum_inactive_after`** let's you configure the number of days before a plugin installations is considered inactive.

**`sfum_old_after`** let's you configure the number of days before a plugin installations is considered stale and will be removed from the database.

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
*Note that filtering `sfum_inactive_after` to 0 will erase your database when the daily maintenence cronjob is executed.*

== Changelog ==
= 1.0.0-rc2 =
* Added filters to change when a plugin installation is considered active

= 1.0.0-rc1 =
* First release candidate

== Screenshots ==
1. Main page.