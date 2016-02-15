<?php

// Don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'category_posts_get_image_size' ) ) :
/**
 * Get image size
 *
 * $thumb_w, $thumb_h - the width and height of the thumbnail in the widget settings
 * $image_w,$image_h - the width and height of the actual image being displayed
 *
 * return: an array with the width and height of the element containing the image
 */
function category_posts_get_image_size( $thumb_w,$thumb_h,$image_w,$image_h) {
	
	$image_size = array('image_h' => $thumb_h, 'image_w' => $thumb_w);
	if($thumb_h < $image_h && $thumb_w < $image_w) {
		$image_size['image_h'] = $image_h;
		$image_size['image_w'] = $image_w;
	}
	
	return $image_size;
}
endif;

if ( ! function_exists( 'category_posts_get_cropping_css_class' ) ) :
/**
 * Get cropping CSS class
 *
 * $thumb_w, $thumb_h - the width and height of the thumbnail in the widget settings
 * $width,$height - the actual image size
 *
 * Return: The class to apply to the element containing the thumbnail image
 */
function category_posts_get_cropping_css_class( $thumb_w,$thumb_h,$width,$height ) {
	
	$relation_thumbnail = $thumb_w / $thumb_h;
	$cropping_css_class = "";
	$relation_cropped = $height / $width;
	
	if ($relation_thumbnail < $relation_cropped)
		$cropping_css_class = "cat-post-css-vcropping";
	else if ($relation_thumbnail > $relation_cropped)
		$cropping_css_class = "cat-post-css-hcropping";

	return $cropping_css_class;
}
endif;
