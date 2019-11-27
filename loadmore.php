<?php
/**
 * Server side implementation of load more handling.
 *
 * @package categoryposts.
 *
 * @since 4.9
 */

namespace categoryPosts;

// Don't call the file directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Embed the front end JS for load more.
 *
 * @since 4.9
 */
function embed_loadmore_scripts() {
	echo '<script>{';
	$suffix = 'min.js';
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
		$suffix = 'js';
	}
	echo 'var tiptoppress = Array();';
	echo 'tiptoppress["' . esc_js( __NAMESPACE__ ) . '"] = { json_root_url : "' . esc_js( rest_url( __NAMESPACE__ . '/loadmore' ) ) . '"};';
	include __DIR__ . '/js/frontend/loadmore.' . $suffix;
	echo '}</script>';
}

/**
 * Generate the JSON response which includes additional element as a response
 * to a "load more" request.
 *
 * @param \WP_REST_Request $request The rest request with widget aand start point info.
 */
function get_next_elements( \WP_REST_Request $request ) {
	$id = (string) $request['id'];
	$start = (int) $request['start'];
	$number = (int) $request['number'];
	$context = (string) $request['context'];

	$ret = array();

	$id_components = explode( '-', $id );
	if ( 2 <= count( $id_components ) ) {
		switch ( $id_components[0] ) {
			case 'shortcode':
				if ( 3 === count( $id_components ) ) {
					$pid = $id_components[1];  // The ID of the relevant post.
					$name = $id_components[2]; // The shortcode "name".
					$settings = shortcode_settings( $pid, $name );
					if ( ! empty( $settings ) ) {
						$virtual_widget = new Virtual_Widget( '', '', $settings );
						$ret = $virtual_widget->get_elements_HTML( $start, $number, $context );
					}
				}
				break;
			case 'widget':
				if ( 2 === count( $id_components ) ) {
					$id = $id_components[1];  // The ID of the widget.
					$class = __NAMESPACE__ . '\Widget';
					$widgetclass = new $class();
					$allsettings = $widgetclass->get_settings();
					if ( isset( $allsettings[ $id ] ) ) {
						$virtual_widget = new Virtual_Widget( '', '', $allsettings[ $id ] );
						$ret = $virtual_widget->get_elements_HTML( $start, $number, $context );
					}
				}
				break;
		}
	}

	return new \WP_REST_Response( $ret );
}

/**
 * This function is where we register our routes for our example endpoint.
 */
function register_route() {
	register_rest_route( __NAMESPACE__, '/loadmore/(?P<id>[\w-]+)/(?P<start>[\d]+)/(?P<number>[\d]+)/(?P<context>[\w]+)', array(
		'methods'  => 'GET',
		'callback' => __NAMESPACE__ . '\get_next_elements',
	) );
}

add_action( 'rest_api_init', __NAMESPACE__ . '\register_route' );
