<?php
/**
 * -----------------------------------------------------------------------------
 * Purpose: REST API.
 * Package: XXSimoXX\StatsForUpdateManager
 * -----------------------------------------------------------------------------
 * Copyright © 2020 - Simone Fioravanti
 * -----------------------------------------------------------------------------
 */

namespace XXSimoXX\StatsForUpdateManager;

if (!defined('ABSPATH')){
	die('-1');
};


class CustomEndPoint{

	public function __construct( ){
		
		// Create a custom endpoint at /wp-json/stats/v1/stats/
		add_action( 'rest_api_init', function () {
			register_rest_route(
				'stats/v1',
				'/stats',
				[
					'methods' => 'GET',
					'callback' => [$this, 'stats_route']
				]
			);
		});

	}
	
	// Return statistics.
	public function stats_route($data){
	
		// Bring StatsForUpdateManager class into scope.
		global $sfum_instance;
		
		// We are serving filtered results.
		$list = apply_filters('sfum_active_installations', $sfum_instance->stat_array);
		
		// Build the response.
		$response=[];
		foreach ($list as $key=>$value) {
			$response[]=['endpoint_identifier'=>$key, 'active_installations'=>$value];
		}
		
		return $response;

	}
	
}
