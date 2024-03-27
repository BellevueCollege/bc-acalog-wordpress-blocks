<?php
/**
 * Plugin Name: BC Acalog Blocks
 * Plugin URI: https://github.com/bellevuecollege/bc-acalog-wordpress-blocks
 * Description: Unofficial WordPress blocks to integrate Acalog with WordPress.
 * Author: BC Integration (Taija)
 * Author URI: https://www.bellevuecollege.edu
 * Version: 0.0.0 - BETA 1 #{versionStamp}#
 * License: GPL2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 */

namespace bawb;

use bawb\acalog_api\API;
require_once WP_PLUGIN_DIR . '/bc-acalog-wordpress-blocks/classes/class-acalog-api.php';

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exit if configuration is not set.
if ( ! defined( 'ACALOG_API_KEY' ) || ! defined( 'ACALOG_BASE_API_URL' ) || ! defined( 'ACALOG_BASE_URL' ) ) {
	return;
}

/**
 * Load the blocks!
 *
 * Note- all blocks must be registered here.
 *
 * @return void
 */

add_action( 'init', __NAMESPACE__ . '\blocks_init' );

function blocks_init() {
	/** List of blocks - should match folder names */

	// Dynamic Blocks.
	register_block( 'program', true );

	// Static Blocks.
	//register_block( 'alert' );

}

/**
 * Register blocks
 *
 * @param string  $block_name Name of the block.
 * @param boolean $dynamic Is the block dynamic?
 * @return void
 *
 * Registers static and dynamic blocks
 */
function register_block( $block_name, $dynamic = false ) {
	$path = dirname( __FILE__ ) . "/build/$block_name";
	if ( $dynamic ) {
		require "src/$block_name/block.php";
		register_block_type(
			"$path/block.json",
			array(
				'render_callback' => __NAMESPACE__ . '\\' . str_replace( '-', '_', $block_name ) . '_callback',
			)
		);
	} else {
		register_block_type( "$path/block.json" );
	}
}


/**
 * Register API Routes
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'bawb/v1', '/programs', array(
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\program_list_callback',
	) );
} );


function program_list_callback( \WP_REST_Request $request ) {
	$API = new API(
		api_base_url: ACALOG_BASE_API_URL,
		api_key: ACALOG_API_KEY
	);
	$programs = $API->get_all_programs();

	if ( 'select' === $request->get_param( 'format' ) ) {
		$programs = array_map( function ( $program ) {
			return [
				'value' => sanitize_title( $program['name'] ),
				'label' => $program['name'],
			];
		}, $programs );

		return array_values( $programs ); !!
	}
	return $programs;
}
