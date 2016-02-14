<?php

// Don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'get_image_size' ) ) :
/**
 * Get image size
 */
function get_image_size( $image_size ) {
	
	if($image_size['thumb_h'] < get_option( 'thumbnail_size_h' ) && $image_size['thumb_w'] < get_option( 'thumbnail_size_w')) {
		$image_size['image_h'] = get_option( 'thumbnail_size_h' );
		$image_size['image_w'] = get_option( 'thumbnail_size_w' );
	} elseif($image_size['thumb_h'] < get_option( 'medium_size_h' ) && $image_size['thumb_w'] < get_option( 'medium_size_w')) {
		$image_size['image_h'] = get_option( 'medium_size_h' );
		$image_size['image_w'] = get_option( 'medium_size_w' );
	}elseif($image_size['thumb_h'] < get_option( 'large_size_h' ) && $image_size['thumb_w'] < get_option( 'large_size_w')) {
		$image_size['image_h'] = get_option( 'large_size_h' );
		$image_size['image_w'] = get_option( 'large_size_w' );
	}
	
	return $image_size;
}
endif;

if ( ! function_exists( 'get_cropping_css_class' ) ) :
/**
 * Get cropping CSS class
 */
function get_cropping_css_class( $image_size ) {
	
	$relation_thumbnail = $image_size['image_h'] / $image_size['image_w'];
	$cropping_css_class = "";
	if( !empty($image_size['thumb_h']) && !empty($image_size['thumb_w'])) {
		$relation_cropped = $image_size['thumb_h'] / $image_size['thumb_w'];
		$cropping_css_class = $relation_thumbnail < $relation_cropped ? "cat-post-css-hcropping" : "cat-post-css-vcropping";
	}
	return $cropping_css_class;
}
endif;
