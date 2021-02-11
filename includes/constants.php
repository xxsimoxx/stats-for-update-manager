<?php

namespace XXSimoXX\StatsForUpdateManager;

if (!defined('ABSPATH')) {
	die('-1');
}

// Define constants.

// Plugin version.
const VERSION = '1.2.0';

// DB table name without prefix.
const DB_TABLE_NAME = 'sfum_logs';

// Default days for a plugin to be considered inactive.
const DEFAULT_INACTIVE_DAYS = 7;

// Default days for a plugin to be considered stale.
const DEFAULT_OLD_DAYS = 28;

// Database schema version (for future use).
const DB_REVISION = 1;

// GitHub plugin page.
const GITHUB_PAGE = 'https://github.com/xxsimoxx/stats-for-update-manager';

// Link to Gieffe software page.
const SW_PAGE = 'https://software.gieffeedizioni.it';

// Menu Slug.
const MENU_SLUG = 'update-manager-statistics';

// Link to Update Manager homepage.
const UM_LINK = 'https://codepotent.com/classicpress/plugins/update-manager/';

// Update Manager class.
const UM_SLUG = 'codepotent-update-manager/codepotent-update-manager.php';

// Update Manager class.
const UM_CLASS = '\CodePotent\UpdateManager\UpdateManager';

// Update Manager hook.
const UM_HOOK_DEPRECATED = 'codepotent_update_manager_filter_request';
const UM_HOOK_PLUGINS = 'codepotent_update_manager_filter_plugin_request';
const UM_HOOK_THEMES = 'codepotent_update_manager_filter_theme_request';

// Update Manager custom post type name for plugins.
const UM_CPT_PLUGINS = 'plugin_endpoint';

// Update Manager custom post type name for themes.
const UM_CPT_THEMES = 'theme_endpoint';

// Update Manager custom post type name.
const UM_PAGE = 'update-manager';

// Update Manager footer filter hook.
const UM_HOOK_FOOTER = 'codepotent_update_manager_extension_footer_';
