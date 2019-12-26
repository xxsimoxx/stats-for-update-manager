# Stats for Update Manager

This is a companion plugin for [**Update Manager** from CodePotent](https://codepotent.com/).

With Stats for Update Manager you can count active installations of your plugins that serve updates with Update Manager.

You'll find a new submenu, *Statistics*, under the *Update Manager* menu.

## When a plugin is in that count

Plugins that queried Update Manager at least once in the last week are considered active.


## Disclaimers
This plugin is intended to be used by *developers*.

### GDPR / privacy

This plugin stores data about plugin updates in a table. 
You can see/change how much time this data is kept in the first lines of the plugin.
By default (for now to change this you have to change the plugin code!) all the data is removed at plugin uninstall.

The table structure contains:

- URL of the site asking for updates, sha512 hashed
- plugin checked
- timestamp of the last check

**Is up to you to decide if and to inform your plugin users that this data is kept.**

*This plugin itself is sending such information to the developer to keep statistical usage information.*
