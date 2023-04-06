<?php

/**
 * -----------------------------------------------------------------------------
 * Purpose: Parse Update Manager endpoints
 * Package: XXSimoXX\StatsForUpdateManager
 * -----------------------------------------------------------------------------
 * Copyright © 2021 - Simone Fioravanti
 * -----------------------------------------------------------------------------
 */

namespace XXSimoXX\StatsForUpdateManager;

function get_logo($slug = '') {

	$plugin_file = WP_PLUGIN_DIR.'/'.$slug;

	if (!file_exists($plugin_file)) {
		return false;
	}

	$plugin_base = dirname($slug);
	$plugin_dir  = WP_PLUGIN_DIR.'/'.$plugin_base;
	$image_path = $plugin_dir.'/images';
	$image_path = apply_filters('codepotent_update_manager_image_path', $image_path);
	$image_path = apply_filters('codepotent_update_manager_'.$slug.'_image_path', $image_path);

	$image_url  = WP_PLUGIN_URL.'/'.$plugin_base.'/images';
	$image_url  = apply_filters('codepotent_update_manager_image_url', $image_url);
	$image_url  = apply_filters('codepotent_update_manager'.$slug.'__image_url', $image_url);

	if (!file_exists($image_path.'/icon.svg')) {
		return false;
	}

	return $image_url.'/icon.svg';

}

function initials($name) {
	$split = preg_split('/[-_ ]/', $name.'- ');
	$a = array_shift($split);
	$b = array_shift($split);
	return strtoupper(substr($a, 0, 1).substr($b, 0, 1));
}
