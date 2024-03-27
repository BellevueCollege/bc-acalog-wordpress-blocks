<?php 
namespace bawb;
use bawb\acalog_api\API;
require_once WP_PLUGIN_DIR . '/bc-acalog-wordpress-blocks/classes/class-acalog-api.php';


function program_callback( $attributes ) {

    // Instantiate the API
    $API = new API( api_base_url: ACALOG_BASE_API_URL, api_key: ACALOG_API_KEY );

    // Get the link to the program
    $link =  $API->get_program_link_by_name( $attributes['selectedProgram'] );

    // Parse the link to include a class for success or exception
    $success = str_contains( $link, 'exception' ) ? 'api-exception' : 'api-success';

    // Get link text
    $link_text = $attributes['linkText'];

    // Return the link
	return "<{$attributes['headingTag']}><a class='program-link $success' href='$link'>{$link_text}</a></{$attributes['headingTag']}>";
}
