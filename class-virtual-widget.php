<?php
/**
 * Implementation of virtual widget.
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
 *  Class that represent a virtual widget. Each widget being created will have relevant
 *  CSS output in the header, but still requires a call for getHTML method or renderHTML
 *  to get or output the HTML
 *
 *  @since 4.7
 */
class Virtual_Widget {

	/**
	 * A container for all the "active" objects
	 *
	 * @var Array
	 *
	 * @since 4.7
	 */
	private static $collection = array();

	/**
	 * The identifier use as the id of the root html element when the HTML is generated.
	 *
	 * @var string
	 *
	 * @since 4.7
	 */
	private $id;

	/**
	 * The class name to be use us the class attribute on the root html element.
	 *
	 * @var string
	 *
	 * @since 4.7
	 */
	private $class;

	/**
	 *  Construct the virtual widget. This should happen before wp_head action with priority
	 *  10 is executed if any CSS output should be generated.
	 *
	 *  @param string $id    The identifier use as the id of the root html element when the HTML
	 *                       is generated.
	 *
	 *  @param string $class The class name to be use us the class attribute on the root html element.
	 *
	 *  @param array  $args  The setting to be applied to the widget.
	 *
	 *  @since 4.7
	 */
	public function __construct( $id, $class, $args ) {
		$this->id = $id;
		$this->class = $class;
		self::$collection[ $id ] = upgrade_settings( $args );
	}

	/**
	 *  Do what ever cleanup needed when the object is destroyed.
	 *
	 *  @since 4.7
	 */
	public function __destruct() {
		unset( self::$collection[ $this->id ] );
	}

	/**
	 *  Return the HTML of the widget as is generated based on the settings passed at construction time
	 *
	 *  @return string
	 *
	 *  @since 4.7
	 */
	public function getHTML() {

		$widget = new Widget();
		$widget->number = $this->id; // needed to make a unique id for the widget html element.
		ob_start();
		$args = self::$collection[ $this->id ];
		$args['is_shortcode'] = true;  // indicate that we are doing shortcode processing to outputting funtions.
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			),
			$args
		);
		$ret = ob_get_clean();
		$ret = '<div id="' . esc_attr( $this->id ) . '" class="' . esc_attr( $this->class ) . '">' . $ret . '</div>';
		return $ret;
	}

	/**
	 * Get an array of HTML pre item, for item starting from a specific position.
	 *
	 * @since 4.9
	 *
	 * @param int    $start  The start element (0 based).
	 * @param int    $number The maximal number of elements to return. A value of 0
	 *                       Indicates to use the widget settings for that.
	 * @param string $context The ID of the post in which the items will be displayed.
	 *                        A empty string or any value which is not of an ID
	 *                        of actual post will be treated as if there is no context.
	 *
	 * @return string[] Array of HTML per element with the $start element first
	 *                  $start+1 next etc. An empty array is returned if there
	 *                  are no applicable items.
	 */
	public function get_elements_HTML( $start, $number, $context ) {
		$ret = array();

		$widget = new Widget();
		$widget->number = $this->id; // needed to make a unique id for the widget html element.

		$ret = $widget->get_elements_HTML( self::$collection[ $this->id ], $context, $start, $number );
		return $ret;
	}

	/**
	 *  Output the widget HTML
	 *
	 *  Just a wrapper that output getHTML
	 *
	 *  @return void
	 *
	 *  @since 4.7
	 */
	public function renderHTML() {
		echo $this->getHTML(); // Xss off. Raw HTML is generated elsewhre.
	}

	/**
	 *  Calculate the CSS rules required for the widget as is generated based on the settings passed at construction time
	 *
	 *  @param bool  $is_shortcode Indicated if rules are generated for a shortcode.
	 *  @param array $rules "returned" Collection of CSS rules.
	 *
	 *  @since 4.7
	 */
	public function getCSSRules( $is_shortcode, &$rules ) {
		$ret = array();
		$settings = self::$collection[ $this->id ];
		$everything_is_link = isset( $settings['everything_is_link'] ) && $settings['everything_is_link'];

		$widget_id = $this->id;
		if ( ! $is_shortcode ) {
			$widget_id .= '-internal';
		}
		$disable_css = isset( $settings['disable_css'] ) && $settings['disable_css'];

		if ( ! $disable_css ) { // checks if css disable is not set.

			$styles = array( // styles that should be applied to all widgets.
				'normalize'     => 'ul {padding: 0;}',
				'thumb_clenup'  => '.cat-post-item img {max-width: initial; max-height: initial; margin: initial;}',
				'author_clenup' => '.cat-post-author {margin-bottom: 0;}',
				'thumb'         => '.cat-post-thumbnail {margin: 5px 10px 5px 0;}',
				'item_clenup'   => '.cat-post-item:before {content: ""; clear: both;}',
				'more_link'     => '.cat-post-excerpt-more {display: inline-block;}',
				'item_style'    => '.cat-post-item {list-style: none; margin: 3px 0 10px; padding: 3px 0;}',
			);

			if ( ! ( isset( $settings['disable_font_styles'] ) && $settings['disable_font_styles'] ) ) { // checks if disable font styles is not set.
				// add general styles which apply to font styling.
				$styles['current_title_font'] = '.cat-post-current .cat-post-title {font-weight: bold; text-transform: uppercase;}';
				$styles['post-taxs']          = '[class*=cat-post-tax] {font-size: 0.85em;}';
				$styles['post-tax-childs']    = '[class*=cat-post-tax] * {display:inline-block;}';
			}

			// everything link related styling
			// if we are dealing with "everything is a link" option, we need to add the clear:both to the a element, not the div.
			if ( $everything_is_link ) {
				$styles['after_item'] = '.cat-post-item a:after {content: ""; display: table;	clear: both;}';
			} else {
				$styles['after_item'] = '.cat-post-item:after {content: ""; display: table;	clear: both;}';
			}

			// title height in lines.
			if ( isset( $settings['template'] ) && preg_match( '/%title%/', $settings['template'] ) ) {
				$styles['item_title_lines'] = '.cat-post-item .cat-post-title {overflow: hidden;text-overflow: ellipsis;white-space: initial;' .
					'display: -webkit-box;-webkit-line-clamp: ' . $settings['item_title_lines'] . ';-webkit-box-orient: vertical;padding-bottom: 0 !important;}';
			}

			// wrap text around image.
			if ( isset( $settings['template'] ) && preg_match( '/%excerpt%/', $settings['template'] ) ) {
				$selector_wrap_text = 'p.cpwp-excerpt-text';
				$no_wrap = isset( $settings['text_do_not_wrap_thumb'] ) && $settings['text_do_not_wrap_thumb'];
				if ( ! $no_wrap ) {
					// wrap thumb and line-clamp: set the CSS two parent knotes higher (first parent for float, second parent is a browser hack for that float works well).
					$styles['wrap_thumb'] = '.cpwp-wrap-text p {display: inline;}';
					$selector_wrap_text = '.cpwp-wrap-text';
				}
				$styles['excerpt_lines'] = '.cat-post-item ' . $selector_wrap_text . ' {overflow: hidden;text-overflow: ellipsis;white-space: initial;' .
					'display: -webkit-box;-webkit-line-clamp: ' . $settings['excerpt_lines'] . ';-webkit-box-orient: vertical;padding-bottom: 0 !important;}';
				// float text instead wrap and don't hide the excerpt if there is no space
				$styles['float_min_nowrap'] = 'p.cpwp-excerpt-text {min-width: 120px;}';
			}

			// add post format css if needed.
			if ( isset( $settings['template'] ) && preg_match( '/%thumb%/', $settings['template'] ) ) {
				if ( ! isset( $settings['show_post_format'] ) || ( ( 'none' !== $settings['show_post_format'] ) && ( 'nocss' !== $settings['show_post_format'] ) ) ) {
					static $fonts_added = false;
					if ( ! $fonts_added ) {
						$fonturl = esc_url( plugins_url( 'icons/font', __FILE__ ) );
						$ret['post_format_font'] = "@font-face {\n" .
								"font-family: 'cat_post';\n" .
								"src: url('$fonturl/cat_post.eot?58348147');\n" .
								"src: url('$fonturl/cat_post.eot?58348147#iefix') format('embedded-opentype'),\n" .
								"	   url('$fonturl/cat_post.woff2?58348147') format('woff2'),\n" .
								"	   url('$fonturl/cat_post.woff?58348147') format('woff'),\n" .
								"	   url('$fonturl/cat_post.ttf?58348147') format('truetype');\n" .
								" font-weight: normal;\n" .
								" font-style: normal;\n" .
								"}\n";
					}
					$fonts_added = true;

					$placement = '';
					switch ( $settings['show_post_format'] ) {
						case 'topleft':
							$placement = 'top:10%; left:10%;';
							break;
						case 'bottomleft':
							$placement = 'bottom:10%; left:10%;';
							break;
						case 'ceter':
							$placement = 'top:calc(50% - 34px); left:calc(50% - 34px);';
							break;
						case 'topright':
							$placement = 'top:10%; right:10%;';
							break;
						case 'bottomright':
							$placement = 'bottom:10%; right:10%;';
							break;
					}
					$styles['post_format_thumb'] = '.cat-post-thumbnail span {position:relative; display:inline-block;}';
					$styles['post_format_icon_styling'] = '.cat-post-format:after {font-family: "cat_post"; position:absolute; color:#FFFFFF; font-size:64px; line-height: 1; ' . $placement . '}';

					$styles['post_format_icon_aside'] = ".cat-post-format-aside:after { content: '\\f0f6'; }";
					$styles['post_format_icon_chat'] = ".cat-post-format-chat:after { content: '\\e802'; }";
					$styles['post_format_icon_gallery'] = ".cat-post-format-gallery:after { content: '\\e805'; }";
					$styles['post_format_icon_link'] = ".cat-post-format-link:after { content: '\\e809'; }";
					$styles['post_format_icon_image'] = ".cat-post-format-image:after { content: '\\e800'; }";
					$styles['post_format_icon_quote'] = ".cat-post-format-quote:after { content: '\\f10d'; }";
					$styles['post_format_icon_status'] = ".cat-post-format-status:after { content: '\\e80a'; }";
					$styles['post_format_icon_video'] = ".cat-post-format-video:after { content: '\\e801'; }";
					$styles['post_format_icon_audio'] = ".cat-post-format-audio:after { content: '\\e803'; }";

				}
			}

			// everything link related styling
			// if we are dealing with "everything is a link" option, we need to add the clear:both to the a element, not the div.
			if ( $everything_is_link ) {
				$styles['clear_previous_item'] = '.cat-post-item a:after {content: ""; clear: both;}';
			} else {
				$styles['clear_previous_item'] = '.cat-post-item:after {content: ""; display: table;	clear: both;}';
			}

			foreach ( $styles as $key => $style ) {
				$ret[ $key ] = '#' . $widget_id . ' ' . $style;
			}

			if ( $is_shortcode ) {
				// Twenty Sixteen Theme adds underlines to links with box whadow wtf ...
				$ret['twentysixteen_thumb'] = '#' . $widget_id . ' .cat-post-thumbnail {box-shadow:none}'; // this for the thumb link.
				if ( ! ( isset( $settings['disable_font_styles'] ) && $settings['disable_font_styles'] ) ) { // checks if disable font styles is not set.
					$ret['twentysixteen_tag_link'] = '#' . $widget_id . ' .cat-post-tax-tag a {box-shadow:none}';     // this for the tag link.
					$ret['twentysixteen_tag_span'] = '#' . $widget_id . ' .cat-post-tax-tag span {box-shadow:none}';     // this for the tag link.
				}
				// Twenty Fifteen Theme adds border ...
				$ret['twentyfifteen_thumb'] = '#' . $widget_id . ' .cat-post-thumbnail {border:0}'; // this for the thumb link.
				if ( ! ( isset( $settings['disable_font_styles'] ) && $settings['disable_font_styles'] ) ) { // checks if disable font styles is not set.
					$ret['twentysixteen_tag_link'] = '#' . $widget_id . ' .cat-post-tax-tag a {border:0}';     // this for the tag link.
					$ret['twentysixteen_tag_span'] = '#' . $widget_id . ' .cat-post-tax-tag span {border:0}';     // this for the tag link.
				}
			}

			// localized widget CSS rules for the thumbnail.
			$ret['left'] = '#' . $widget_id . ' .cat-post-thumbnail {display:block; float:left; margin:5px 10px 5px 0;}';
			$ret['crop'] = '#' . $widget_id . ' .cat-post-crop {overflow:hidden;display:block;}';

			// probably all Themes have too much margin on their p element when used in the shortcode or widget.
			$ret['p_styling'] = '#' . $widget_id . ' p {margin:5px 0 0 0}'; // since on bottom it will make the spacing on cover
																// bigger (add to the padding) use only top for now.
			$ret['div_styling'] = '#' . $widget_id . ' li > div {margin:5px 0 0 0; clear:both;}'; // Add margin between the rows.

			// use WP dashicons in the template (e.g. for premade Template 'All and icons').
			$ret['dashicons'] = '#' . $widget_id . ' .dashicons {vertical-align:middle;}';
		}

		// Regardless if css is disabled we need some styling for the thumbnail
		// to make sure cropping is properly done, and they fit the allocated space.
		if ( isset( $settings['template'] ) && preg_match( '/%thumb%/', $settings['template'], $m, PREG_OFFSET_CAPTURE ) ) {
			if ( isset( $settings['thumb_h'] ) && 0 !== intval( $settings['thumb_h'] ) ) {
				$ret['thumb_crop_h'] = '#' . $widget_id . ' .cat-post-thumbnail .cat-post-crop img {height: ' . $settings['thumb_h'] . 'px;}';
			}
			if ( isset( $settings['thumb_w'] ) && 0 !== intval( $settings['thumb_w'] ) ) {
				$ret['thumb_crop_w'] = '#' . $widget_id . ' .cat-post-thumbnail .cat-post-crop img {width: ' . $settings['thumb_w'] . 'px;}';
			}
			$ret['thumb_crop'] = '#' . $widget_id . ' .cat-post-thumbnail .cat-post-crop img {object-fit: cover; max-width: 100%; display: block;}';
			$ret['thumb_crop_not_supported'] = '#' . $widget_id . ' .cat-post-thumbnail .cat-post-crop-not-supported img {width: 100%;}';
			$ret['thumb_fluid_width'] = '#' . $widget_id . ' .cat-post-thumbnail {max-width:' . $settings['thumb_fluid_width'] . '%;}';
			$ret['thumb_styling'] = '#' . $widget_id . ' .cat-post-item img {margin: initial;}';
		}

		// Some hover effect require css to work, add it even if CSS is disabled.
		if ( isset( $settings['thumb_hover'] ) ) {
			switch ( $settings['thumb_hover'] ) {
				case 'white':
					$ret['white_hover_background'] = '#' . $widget_id . ' .cat-post-white span {background-color: white;}';
					$ret['white_hover_thumb'] = '#' . $widget_id . ' .cat-post-white img {padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
					$ret['white_hover_transform'] = '#' . $widget_id . ' .cat-post-white:hover img {opacity: 0.8;}';
					break;
				case 'dark':
					$ret['dark_hover_thumb'] = '#' . $widget_id . ' .cat-post-dark img {padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
					$ret['dark_hover_transform'] = '#' . $widget_id . ' .cat-post-dark:hover img {-webkit-filter: brightness(75%); -moz-filter: brightness(75%); -ms-filter: brightness(75%); -o-filter: brightness(75%); filter: brightness(75%);}';
					break;
				case 'scale':
					$ret['scale_hover_thumb'] = '#' . $widget_id . ' .cat-post-scale img {margin: initial; padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
					$ret['scale_hover_transform'] = '#' . $widget_id . ' .cat-post-scale:hover img {-webkit-transform: scale(1.1, 1.1); -ms-transform: scale(1.1, 1.1); transform: scale(1.1, 1.1);}';
					break;
				case 'blur':
					$ret['blur_hover_thumb'] = '#' . $widget_id . ' .cat-post-blur img {padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
					$ret['blur_hover_transform'] = '#' . $widget_id . ' .cat-post-blur:hover img {-webkit-filter: blur(2px); -moz-filter: blur(2px); -o-filter: blur(2px); -ms-filter: blur(2px); filter: blur(2px);}';
					break;
				case 'icon':
					$fonturl = esc_url( plugins_url( 'icons/font', __FILE__ ) );
					$ret['icon_hover_font'] = "@font-face {\n" .
							"font-family: 'cat_post';\n" .
							"src: url('$fonturl/cat_post.eot?58348147');\n" .
							"src: url('$fonturl/cat_post.eot?58348147#iefix') format('embedded-opentype'),\n" .
							"	   url('$fonturl/cat_post.woff2?58348147') format('woff2'),\n" .
							"	   url('$fonturl/cat_post.woff?58348147') format('woff'),\n" .
							"	   url('$fonturl/cat_post.ttf?58348147') format('truetype');\n" .
							" font-weight: normal;\n" .
							" font-style: normal;\n" .
							"}\n";

					$ret['icon_hover_thumb'] = '#' . $widget_id . ' .cat-post-format-standard:after {opacity:0; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
					$ret['icon_hover_transform'] = '#' . $widget_id . ' .cat-post-thumbnail:hover .cat-post-format-standard:after {opacity:1;}';
					if ( isset( $settings['show_post_format'] ) && ( 'none' === $settings['show_post_format'] ) ) {
						$ret[] = '#' . $widget_id . ' .cat-post-thumbnail span {position:relative; display:inline-block;}';
						$ret[] = '#' . $widget_id . ' .cat-post-icon .cat-post-format:after {font-family: "cat_post"; position:absolute; color:#FFFFFF; font-size:64px; line-height: 1; ' .
									'top:calc(50% - 34px); left:calc(50% - 34px);}';
					}
					$ret[] = '#' . $widget_id . " .cat-post-format-standard:after {padding-left:12px; content: '\\e806'; }";
					break;
			}
		}
		if ( $settings['enable_loadmore'] ) {
			// $this->id is used over $widget_id because we need the id of the outer div, not the UL itself.
			$ret['loadmore'] = '#' . $this->id . ' .' . __NAMESPACE__ . '-loadmore {text-align:center;margin-top:10px}';

			// Scrollbar
			if ( isset( $settings['loadmore_scrollTo'] ) && $settings['loadmore_scrollTo'] ) {
				$ret['loadmore_scrollTo'] = '#' . $widget_id . ' {overflow-y:scroll;}';
			}
		}
		$rules[] = $ret;
	}

	/**
	 *  Output the widget CSS
	 *
	 *  Just a wrapper that output getCSSRules
	 *
	 * @param bool $is_shortcode Indicates if we are in the context os a shortcode.
	 *
	 *  @since 4.7
	 */
	public function outputCSS( $is_shortcode ) {
		$rules = array();
		getCSSRules( $is_shortcode, $rules );
		foreach ( $rules as $rule ) {
			echo "$rule\n";  // Xss off - raw css can not be html escaped.
		}
	}

	/**
	 *  Get the id the virtual widget was registered with
	 *
	 *  @return string
	 *
	 *  @since 4.7
	 */
	public function id() {
		return $this->id;
	}

	/**
	 *  Get all the setting of the virtual widgets in an array
	 *
	 *  @return array
	 *
	 *  @since 4.7
	 */
	public static function getAllSettings() {
		return self::$collection;
	}

}
