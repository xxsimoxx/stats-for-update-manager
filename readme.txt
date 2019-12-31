=== Stats for Update Manager ===
Plugin Name:        Stats for Update Manager
Description:        With Stats for Update Manager you can count active installations of your plugins.
Version:            1.0.0-rc1
Text Domain:        stats-for-update-manager
Domain Path:        /languages
Requires PHP:       5.6
Requires:           1.0.0
Tested:             4.9.99
Author:             Gieffe edizioni
Author URI:         https://www.gieffeedizioni.it
Plugin URI:         https://software.gieffeedizioni.it
Download link:      https://github.com/xxsimoxx/stats-for-update-manager/releases/download/v1.0.0-rc1/stats-for-update-manager.zip
License:            GPLv2
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

With Stats for Update Manager you can count active installations of your plugins.

== Description ==

With Stats for Update Manager you can count active installations of your plugins.

This is a companion plugin for [**Update Manager**](https://codepotent.com/classicpress/plugins/) from [CodePotent](https://codepotent.com/).

You'll find a new submenu, *Statistics*, under the *Update Manager* menu.

Plugins that queried Update Manager at least once in the last week are considered active.

It also add the number of active installations in the plugin details. (See Filters section to tweak)

## GDPR & Disclaimer

This plugin is intended to be used by *developers*.

This plugin stores data about plugin updates in a table. 
You can see/change how much time this data is kept in the first lines of the plugin (defaults to 4 weeks).
By default (for now to change this you have to change the plugin code!) all the data is removed at plugin uninstall.

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

`add_filter('sfum_active_installations',[$this, 'example_filter'] );`

Don't show active installation for my nothing-to-see plugin and raise it to one million for boost!

```php
	public function example_filter($ar){
		unset ($ar['nothing-to-see/nothing-to-see.php']);
		$ar['boost/boost.php'] = 1000000;
		return $ar;
	}
```

Or simply disable it all

```php
	public function example_filter($ar){
		return [];
	}
```

Note: the real number is cached for 6 hours.

`sfum_my_sites` let's you recognize your own sites. They will be marked with an * in the debug informations.
with this filter you can populate an array of sha512-hashed urls.
Example:

```php
add_filter('sfum_my_sites', [$this, 'all_my_sites']);

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

== Changelog ==

= 1.0.0-rc1 =
* First release candidate

== Screenshots ==
1. Main page.