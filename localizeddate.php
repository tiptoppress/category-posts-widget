<?php
/**
 * Server side implementation of localized dates handling.
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
function embed_date_scripts() {
	echo '<script>{';
	$suffix = 'min.js';
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
		$suffix = 'js';
	}
	include __DIR__ . '/js/frontend/date.' . $suffix;
	echo '}</script>';
}
