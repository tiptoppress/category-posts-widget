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
	
	$image_size = array('image_h' => $thumb_h, 'image_w' => $thumb_w, 'margin' => '');
	$relation_thumbnail = $thumb_w / $thumb_h;
	$relation_cropped = $image_h / $image_w;
	
	if ($relation_thumbnail < $relation_cropped) {
		// thumbnail width/height ration is smaller, need to inflate the height of the image to thumb height
		// and adjust width to keep aspect ration of image
		$image_size['image_h'] = $thumb_h;
		$image_size['image_w'] = $thumb_h / $image_h * $image_w; 
		$image_size['margin'] = 'margin-left:-'.($image_size['image_w'] - $thumb_w) /2 .'px;';
	} else {
		// thumbnail width/height ration is bigger, need to inflate the width of the image to thumb width
		// and adjust height to keep aspect ration of image
		$image_size['image_w'] = $thumb_w;
		$image_size['image_h'] = $thumb_w / $image_w * $image_h; 
		$image_size['margin'] = 'margin-top:-'.($image_size['image_h'] - $thumb_h) /2 .'px;';
	}
	
	return $image_size;
}
endif;
