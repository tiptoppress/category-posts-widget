<?php
/**
 * Implementation of virtual widget repository.
 *
 * @package categoryposts.
 *
 * @since 4.7
 */

namespace categoryPosts;

// Don't call the file directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *  Class that implement a simple repository for the virtual widgets representing
 *  actuall shortcode and widgets
 *
 *  @since 4.7
 */
class Virtual_Widgets_Repository {

	/**
	 * Collection of objects representing shortcodes.
	 *
	 * @var array
	 *
	 * @since 4.7
	 */
	private static $shortcodeCollection = array();

	/**
	 * Collection of objects representing widgets.
	 *
	 * @var array
	 *
	 * @since 4.7
	 */
	private static $widgetCollection = array();

	/**
	 *  Add a virtual widget representing a shortcode to the repository
	 *
	 *  @param string         $index  A name to identify the specific shortcode.
	 *  @param Virtual_Widget $widget The virtual widget for it.
	 *
	 *  @since 4.7
	 */
	public function addShortcode( $index, $widget ) {
		self::$shortcodeCollection[ $index ] = $widget;
	}

	/**
	 *  Get all the virtual widgets representing actual shortcodes
	 *
	 *  @return array
	 *
	 *  @since 4.7
	 */
	public function getShortcodes() {
		return self::$shortcodeCollection;
	}

	/**
	 *  Add a virtual widget representing awidget to the repository
	 *
	 *  @param string         $index A name to identify the specific widget.
	 *  @param Virtual_Widget $widget The virstual widget for it.
	 *
	 *  @since 4.7
	 */
	public function addWidget( $index, $widget ) {
		self::$widgetCollection[ $index ] = $widget;
	}

	/**
	 *  Get all the virtual widgets representing actual widgets
	 *
	 *  @return array
	 *
	 *  @since 4.7
	 */
	public function getWidgets() {
		return self::$widgetCollection;
	}

}
