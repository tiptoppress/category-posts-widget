<?php

if ( ! function_exists( 'get_css_cropping' ) ) :
/**
 * Get css cropping
 */
function get_css_cropping( $css_cropping, $instance ) {
	
	if($instance['thumb_h'] < get_option( 'thumbnail_size_h' ) && $instance['thumb_w'] < get_option( 'thumbnail_size_w')) {
		$css_cropping['image_size_h'] = get_option( 'thumbnail_size_h' );
		$css_cropping['image_size_w'] = get_option( 'thumbnail_size_w' );
	} elseif($instance['thumb_h'] < get_option( 'medium_size_h' ) && $instance['thumb_w'] < get_option( 'medium_size_w')) {
		$css_cropping['image_size_h'] = get_option( 'medium_size_h' );
		$css_cropping['image_size_w'] = get_option( 'medium_size_w' );
	}elseif($instance['thumb_h'] < get_option( 'large_size_h' ) && $instance['thumb_w'] < get_option( 'large_size_w')) {
		$css_cropping['image_size_h'] = get_option( 'large_size_h' );
		$css_cropping['image_size_w'] = get_option( 'large_size_w' );
	}
	
	$relation_thumbnail = $css_cropping['image_size_h'] / $css_cropping['image_size_w'];
	if( !empty($instance['thumb_h']) && !empty($instance['thumb_w'])) {
		$relation_cropped = $instance['thumb_h'] / $instance['thumb_w'];
		$css_cropping['css_class'] = $relation_thumbnail < $relation_cropped ? "cat-post-css-hcropping" : "cat-post-css-vcropping";
	}
	return $css_cropping;
}
endif;