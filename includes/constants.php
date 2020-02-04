<?php

namespace XXSimoXX\StatsForUpdateManager;

if (!defined('ABSPATH')) {
	die('-1');
};

// Define constants.

// DB table name without prefix.
const DB_TABLE_NAME = 'sfum_logs';

// Default days for a plugin to be considered inactive.
const DEFAULT_INACTIVE_DAYS = 7;

// Default days for a plugin to be considered stale.
const DEFAULT_OLD_DAYS = 28;

// Database schema version (for future use).
const DB_REVISION = 1;

// Link to my plugins on GitHub.
const XXSIMOXX_LINK = 'https://github.com/xxsimoxx/stats-for-update-manager';

// Link to Update Manager homepage.
const UM_LINK = 'https://codepotent.com/classicpress/plugins/update-manager/';

// Update Manager class.
const UM_CLASS = '\CodePotent\UpdateManager\UpdateManager';

// Update Manager hook.
const UM_HOOK = 'codepotent_update_manager_filter_request';

// Update Manager custom post type name.
const UM_CPT = 'plugin_endpoint';