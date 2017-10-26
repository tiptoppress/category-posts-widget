<?php
/**
 * Main file of the plugin
 *
 * @package categoryposts.
 *
 * @since 1.0
 */

/*
Plugin Name: Category Posts Widget
Plugin URI: https://wordpress.org/plugins/category-posts/
Description: Adds a widget that shows the most recent posts from a single category.
Author: TipTopPress
Version: 4.8.beta2
Author URI: http://tiptoppress.com
Text Domain: category-posts
Domain Path: /languages
*/

namespace categoryPosts;

// Don't call the file directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION        = '4.8.beta2';
const DOC_URL        = 'http://tiptoppress.com/category-posts-widget/documentation-4-8?utm_source=widget_cpw&utm_campaign=documentation_4_8_cpw&utm_medium=form';
const PRO_URL        = 'http://tiptoppress.com/term-and-category-based-posts-widget/?utm_source=widget_cpw&utm_campaign=get_pro_cpw&utm_medium=action_link';
const SUPPORT_URL    = 'https://wordpress.org/support/plugin/category-posts';
const SHORTCODE_NAME = 'catposts';
const SHORTCODE_META = 'categoryPosts-shorcode';
const WIDGET_BASE_ID = 'category-posts';

/**
 *  Adds the "Customize" link to the Toolbar on edit mode.
 *
 *  @since 4.6
 */
function wp_admin_bar_customize_menu() {
	global $wp_admin_bar;

	if ( ! isset( $_GET['action'] ) || ( 'edit' !== $_GET['action'] ) ) {
		return;
	}

	if ( ! current_user_can( 'customize' ) || ! is_admin() || ! is_user_logged_in() || ! is_admin_bar_showing() ) {
		return;
	}

	$current_url = '';
	if ( isset( $_GET['post'] ) && ( '' !== $_GET['post'] ) ) {
		$current_url = get_permalink( absint( $_GET['post'] ) );
	}
	$customize_url = add_query_arg( 'url', rawurlencode( $current_url ), wp_customize_url() );

	$p = isset( $_GET['post'] ) && ! empty( $_GET['post'] ) ? get_post( absint( $_GET['post'] ) ) : false;
	$names = $p ? shortcode_names( SHORTCODE_NAME, $p->post_content ) : array();
	if ( empty( $names ) ) {
		return;
	}

	$wp_admin_bar->add_menu( array(
			'id'     => 'customize',
			'title'  => __( 'Customize' ),
			'href'   => $customize_url,
			'meta'   => array(
					'class' => 'hide-if-no-customize',
			),
	) );
	add_action( 'wp_before_admin_bar_render', 'wp_customize_support_script' );
}

add_action( 'admin_bar_menu', __NAMESPACE__ . '\wp_admin_bar_customize_menu', 35 );

/**
 * Print out required CSS, hooked on the wp_head hook.
 */
function wp_head() {

	$widget_repository = new virtualWidgetsRepository;

	$rules = array();

	foreach ( $widget_repository->getShortcodes() as $widget ) {
			$widget->getCSSRules( true, $rules );
	}

	foreach ( $widget_repository->getWidgets() as $widget ) {
		$widget->getCSSRules( false, $rules );
	}

	if ( ! empty( $rules ) ) {
		?>
<style type="text/css">
	<?php
	foreach ( $rules as $rule ) {
		echo "$rule\n";
	}
	?>
</style>
	<?php
	}
}

add_action( 'wp_head', __NAMESPACE__ . '\register_virtual_widgets', 0 );

/**
 *  Register virtual widgets for all widgets and shortcodes that are going to be displayed on the page
 *
 *  @return void
 *
 *  @since 4.7
 */
function register_virtual_widgets() {
	global $post;
	global $wp_registered_widgets;

	$repository = new virtualWidgetsRepository;

	// check first for shortcode settings.
	if ( is_singular() ) {
		$names = shortcode_names( SHORTCODE_NAME, $post->post_content );

		foreach ( $names as $name ) {
			$meta = shortcode_settings( $name );
			if ( is_array( $meta ) ) {
				$id = WIDGET_BASE_ID . '-shortcode-' . get_the_ID(); // needed to make a unique id for the widget html element.
				if ( '' !== $name ) { // if not default name append to the id.
					$id .= '-' . sanitize_title( $name ); // sanitize to be on the safe side, not sure where when and how this will be used.
				}
				$repository->addShortcode( $name, new virtualWidget( $id, WIDGET_BASE_ID . '-shortcode', $meta ) );
			}
		}
	}

	$sidebars_widgets = wp_get_sidebars_widgets();

	if ( is_array( $sidebars_widgets ) ) {
		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			if ( 'wp_inactive_widgets' === $sidebar || 'orphaned_widgets' === substr( $sidebar, 0, 16 ) ) {
				continue;
			}

			if ( is_array( $widgets ) ) {
				foreach ( $widgets as $widget ) {
					$widget_base = _get_widget_id_base( $widget );
					if ( WIDGET_BASE_ID === $widget_base ) {
						$class = __NAMESPACE__ . '\Widget';
						$widgetclass = new $class();
						$allsettings = $widgetclass->get_settings();
						$settings = isset( $allsettings[ str_replace( $widget_base . '-', '', $widget ) ] ) ? $allsettings[ str_replace( $widget_base . '-', '', $widget ) ] : false;
						$repository->addWidget( $widget, new virtualWidget( $widget, $widget, $settings ) );
					}
				}
			}
		}
	}
}

/**
 *  Enque the relevant css and scripts based on the actual settings of the widgets
 *  displayed on the page
 *
 *  @since 4.8
 */
function wp_enqueue_scripts() {

	// registering do not do anything by itself so just get over with it first.
	wp_register_script( 'category-posts-productZoom', plugins_url( 'js/elevatezoom-3.0.8/jquery.elevateZoom.min.js', __FILE__ ), array( 'jquery' ), VERSION, true );

	$widgets = virtualWidget::getAllSettings();
	foreach ( $widgets as $setting ) {
		// enqueue product zoom?
		if ( isset( $setting['thumb_hover'] ) && ( 'productZoom' === $setting['thumb_hover'] ) ) {
			// registering do not do anything by itself so just get over with it first.
			wp_enqueue_script( 'category-posts-productZoom' );
		}
	}
}

/**
 * Register our styles
 *
 * @return void
 */
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\wp_enqueue_scripts' );

add_action( 'wp_head', __NAMESPACE__ . '\wp_head' );

/**
 *	Enqueue widget related scripts for the widget admin page and customizer.
 *
 *  @param string $hook the name of the admin hook for which the function was triggered.
 */
function admin_scripts( $hook ) {

	if ( 'widgets.php' === $hook ) { // enqueue only for widget admin and customizer.

		// control open and close the widget section.
		wp_register_script( 'category-posts-widget-admin-js', plugins_url( 'js/admin/category-posts-widget.js', __FILE__ ), array( 'jquery' ), VERSION, true );
		wp_enqueue_script( 'category-posts-widget-admin-js' );

		$js_data = array( 'accordion' => false );
		$meta = get_user_meta( get_current_user_id(), __NAMESPACE__, true );
		if ( is_array( $meta ) && isset( $meta['panels'] ) ) {
			$js_data['accordion'] = true;
		}
		$js_data['template_tags'] = get_template_tags();
		$js_data[ __NAMESPACE__ ] = $js_data; // To make accessing the data in JS easier to understand.

		wp_localize_script( 'category-posts-widget-admin-js', 'tiptoppress', $js_data );
		wp_enqueue_media();
		wp_localize_script( 'category-posts-widget-admin-js', 'cwp_default_thumb_selection', array(
			'frame_title' => __( 'Select a default thumbnail', 'category-posts' ),
			'button_title' => __( 'Select', 'category-posts' ),
			'none' => __( 'None', 'category-posts' ),
		) );
	}
}

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_scripts' ); // "called on widgets.php and costumizer since 3.9.

add_action( 'admin_init', __NAMESPACE__ . '\load_textdomain' );

/**
 * Load plugin textdomain.
 *
 * @return void
 *
 * @since 4.1
 **/
function load_textdomain() {
	load_plugin_textdomain( 'category-posts', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}

/*
 * Add styles for widget sections
 */

add_action( 'admin_print_styles-widgets.php', __NAMESPACE__ . '\admin_styles' );

/**
 * Add required admin styles.
 *
 * @since 4.1
 **/
function admin_styles() {
?>
<style>
.category-widget-cont h4 {
	padding: 12px 15px;
	cursor: pointer;
	margin: 5px 0;
	border: 1px solid #E5E5E5;
}
.category-widget-cont h4:first-child {
	margin-top: 10px;
}
.category-widget-cont h4:last-of-type {
	margin-bottom: 10px;
}
.category-widget-cont h4:after {
	float:right;
	font-family: "dashicons";
	content: '\f140';
	-ms-transform: translate(-1px,1px);
	-webkit-transform: translate(-1px,1px);
	-moz-transform: translate(-1px,1px);
	transform: translate(-1px,1px);
	-ms-transition: all 600ms;
	-webkit-transition: all 600ms;
	-moz-transition: all 600ms;
	transition: all 600ms;
}
.category-widget-cont h4.open:after {
	-ms-transition: all 600ms;
	-webkit-transition: all 600ms;
	-moz-transition: all 600ms;
	transition: all 600ms;
	-ms-transform: rotate(180deg);
	-webkit-transform: rotate(180deg);
	-moz-transform: rotate(180deg);
	transform: rotate(180deg);
}
.category-widget-cont > div {
	display:none;
}
.category-widget-cont > div.open {
	display:block;
}
.category-widget-cont th,
.category-widget-cont tr {
	vertical-align: baseline;
	text-align:start;
}

.cat-post-template-help {display:none;}

.categoryPosts-template textarea {width:100%}

.category-widget-cont .open-template-help {
	border:0;
	padding:0;
	cursor: pointer;
}

</style>
<?php
}

/**
 * Get image size
 *
 * @param int $thumb_w The width of the thumbnail in the widget settings.
 * @param int $thumb_h The height of the thumbnail in the widget settings.
 * @param int $image_w The width of the actual image being displayed.
 * @param int $image_h The height of the actual image being displayed.
 *
 * @return array An array with the width and height of the element containing the image
 */
function get_image_size( $thumb_w, $thumb_h, $image_w, $image_h ) {

	$image_size = array( 'image_h' => $thumb_h, 'image_w' => $thumb_w, 'marginAttr' => '', 'marginVal' => '' );
	$relation_thumbnail = $thumb_w / $thumb_h;
	$relation_cropped = $image_w / $image_h;

	if ( $relation_thumbnail < $relation_cropped ) {
		// crop left and right site
		// thumbnail width/height ration is smaller, need to inflate the height of the image to thumb height
		// and adjust width to keep aspect ration of image.
		$image_size['image_h'] = $thumb_h;
		$image_size['image_w'] = $thumb_h / $image_h * $image_w;
		$image_size['marginAttr'] = 'margin-left';
		$image_size['marginVal'] = ($image_size['image_w'] - $thumb_w) / 2;
	} else {
		// crop top and bottom
		// thumbnail width/height ration is bigger, need to inflate the width of the image to thumb width
		// and adjust height to keep aspect ration of image.
		$image_size['image_w'] = $thumb_w;
		$image_size['image_h'] = $thumb_w / $image_w * $image_h;
		$image_size['marginAttr'] = 'margin-top';
		$image_size['marginVal'] = ($image_size['image_h'] - $thumb_h) / 2;
	}

	return $image_size;
}

/**
 *  Get the tags which might be used in the template.
 *
 *  @since 4.8
 *
 *  @return array Array of strings of the tags.
 */
function get_template_tags() {
	return array( 'author', 'title', 'date', 'thumb', 'excerpt', 'commentnum', 'post_tag','category' );
}

/**
 *  Get a regex to parse the template in order to find the tags used in it.
 *
 *  @since 4.8
 *
 *  @return string The template parsing regex.
 */
function get_template_regex() {
	$tags = get_template_tags();

	$regexp = '';
	foreach ( $tags as $t ) {
		if ( ! empty( $regexp ) ) {
			$regexp .= '|';
		}
		$regexp .= '%' . $t . '%';
	}
	$regexp = '@(' . $regexp . ')@i';

	return $regexp;
}

/**
 * Category Posts Widget Class
 *
 * Shows the single category posts with some configurable options
 */
class Widget extends \WP_Widget {

	/**
	 * Widget constructor.
	 */
	function __construct() {
		$widget_ops = array( 'classname' => 'cat-post-widget', 'description' => __( 'List single category posts', 'category-posts' ) );
		parent::__construct( WIDGET_BASE_ID, __( 'Category Posts', 'category-posts' ), $widget_ops );
	}

	/**
	 * Calculate the HTML for showing the thumb of a post item.
	 *
	 * Used as a filter for the thumb wordpress API to add css based stretching and cropping
	 * when the image is not at the requested dimensions
	 *
	 * @param string	   $html The original HTML generated by the core APIS.
	 * @param int		   $post_id the ID of the post of which the thumb is a featured image.
	 * @param int    	   $post_thumbnail_id The id of the featured image attachment.
	 * @param string|array $size The requested size identified by name or (width, height) array.
	 * @param mixed	       $attr ignored in this context.
	 * @return string The HTML for the thumb related to the post
	 *
	 * @since 4.1
	 */
	function post_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
		if ( empty( $this->instance['thumb_w'] ) || empty( $this->instance['thumb_h'] ) ) {
			return $html; // bail out if no full dimensions defined.
		}
		$meta = image_get_intermediate_size( $post_thumbnail_id, $size );

		if ( empty( $meta ) ) {
			$post_img = wp_get_attachment_metadata( $post_thumbnail_id, $size );
			$meta['file'] = basename( $post_img['file'] );
		}

		$origfile = get_attached_file( $post_thumbnail_id, true ); // the location of the full file.
		$file = dirname( $origfile ) . '/' . $meta['file']; // the location of the file displayed as thumb.
		if ( file_exists( $file ) ) {
			list( $width, $height ) = getimagesize( $file );  // get actual size of the thumb file.

			if ( isset( $this->instance['use_css_cropping'] ) && $this->instance['use_css_cropping'] ) {
				$image = get_image_size( $this->instance['thumb_w'], $this->instance['thumb_h'], $width, $height );

				// replace srcset.
				$array = array();
				preg_match( '/width="([^"]*)"/i', $html, $array );
				$pattern = '/' . $array[1] . 'w/';
				$html = preg_replace( $pattern, $image['image_w'] . 'w', $html );
				// replace size.
				$pattern = '/' . $array[1] . 'px/';
				$html = preg_replace( $pattern, $image['image_w'] . 'px', $html );
				// replace width.
				$pattern = '/width="[0-9]*"/';
				$html = preg_replace( $pattern, "width='" . $image['image_w'] . "'", $html );
				// replace height.
				$pattern = '/height="[0-9]*"/';
				$html = preg_replace( $pattern, "height='" . $image['image_h'] . "'", $html );
				// set margin.
				$html = str_replace( '<img ', '<img style="' . $image['marginAttr'] . ':-' . $image['marginVal'] . 'px;height:' . $image['image_h']
					. 'px;clip:rect(auto,' . ( $this->instance['thumb_w'] + $image['marginVal'] ) . 'px,auto,' . $image['marginVal']
				. 'px);width:auto;max-width:initial;" ', $html );
				// wrap span.
				$html = '<span class="cat-post-crop" style="width:' . $this->instance['thumb_w'] . 'px;height:' . $this->instance['thumb_h'] . 'px;">'
					. $html . '</span>';
			} else {
				// use_css_cropping is not used.
				// wrap span.
				$html = '<span>' . $html . '</span>';
			}
		}
		return $html;
	}

	/*
	 * wrapper to execute the the_post_thumbnail with filters.
	 */
	/**
	 * Calculate the HTML for showing the thumb of a post item.
	 *
	 * It is a wrapper to execute the the_post_thumbnail with filters
	 *
	 * @param  string|array $size The requested size identified by name or (width, height) array.
	 *
	 * @return string The HTML for the thumb related to the post and empty string if it can not be calculated
	 *
	 * @since 4.1
	 */
	function the_post_thumbnail( $size = 'post-thumbnail' ) {
		if ( empty( $size ) ) { // if junk value, make it a normal thumb.
			$size = 'post-thumbnail';
		} elseif ( is_array( $size ) && ( 2 === count( $size ) ) ) {  // good format at least.
			// normalize to ints first.
			$size[0] = (int) $size[0];
			$size[1] = (int) $size[1];
			if ( ( 0 === $size[0] ) && ( 0 === $size[1] ) ) { // Both values zero then revert to thumbnail.
				$size = array( get_option( 'thumbnail_size_w', 150 ), get_option( 'thumbnail_size_h', 150 ) );
			} elseif ( ( 0 === $size[0] ) && ( 0 !== $size[1] ) ) {
				// if one value is zero make a square using the other value.
				$size[0] = $size[1];
			} elseif ( ( 0 !== $size[0] ) && ( 0 === $size[1] ) ) {
				$size[1] = $size[0];
			}
		} else {
			$size = array( get_option( 'thumbnail_size_w', 150 ), get_option( 'thumbnail_size_h', 150 ) ); // yet another form of junk.
		}

		$post_thumbnail_id = get_post_thumbnail_id( get_the_ID() );
		if ( ! $post_thumbnail_id && $this->instance['default_thunmbnail'] ) {
			$post_thumbnail_id = $this->instance['default_thunmbnail'];
		}

		do_action( 'begin_fetch_post_thumbnail_html', get_the_ID(), $post_thumbnail_id, $size );
		$html = wp_get_attachment_image( $post_thumbnail_id, $size, false, '' );
		if ( ! $html ) {
			$ret = '';
		} else {
			$ret = $this->post_thumbnail_html( $html, get_the_ID(), $post_thumbnail_id, $size, '' );
		}
		do_action( 'end_fetch_post_thumbnail_html', get_the_ID(), $post_thumbnail_id, $size );

		return $ret;
	}

	/**
	 * Excerpt more link filter
	 *
	 * @param string $more The "more" text passed by the filter.
	 *
	 * @return string The link to the post with the "more" text configured in the widget.
	 */
	function excerpt_more_filter( $more ) {
		return ' <a class="cat-post-excerpt-more more-link" href="' . get_permalink() . '">' . esc_html( $this->instance['excerpt_more_text'] ) . '</a>';
	}

	/**
	 * Apply the_content filter for excerpt
	 * This should show sharing buttons which comes with other widgets in the widget output in the same way as on the main content
	 *
	 * @param string $text The HTML with other applied excerpt filters.
	 *
	 * @return string If option hide_social_buttons is unchecked applay the_content filter.
	 *
	 * @since 4.6
	 */
	function apply_the_excerpt( $text ) {
		$ret = '';
		if ( isset( $this->instance['hide_social_buttons'] ) && $this->instance['hide_social_buttons'] ) {
			$ret = $text;
		} else {
			$ret = apply_filters( 'the_content', $text );
		}
		return $ret;
	}

	/**
	 * Excerpt allow HTML
	 *
	 * @param string $text The HTML with other applied excerpt filters.
	 */
	function allow_html_excerpt( $text ) {
		global $post, $wp_filter;
		$new_excerpt_length = ( isset( $this->instance['excerpt_length'] ) && $this->instance['excerpt_length'] > 0 ) ? $this->instance['excerpt_length'] : 55;
		if ( '' === $text ) {
			$text = get_the_content( '' );
			$text = strip_shortcodes( $text );
			$text = apply_filters( 'the_content', $text );
			$text = str_replace( '\]\]\>', ']]&gt;', $text );
			$text = preg_replace( '@<script[^>]*?>.*?</script>@si', '', $text );
			$cphtml = array(
				'&lt;a&gt;',
				'&lt;br&gt;',
				'&lt;em&gt;',
				'&lt;i&gt;',
				'&lt;ul&gt;',
				'&lt;ol&gt;',
				'&lt;li&gt;',
				'&lt;p&gt;',
				'&lt;img&gt;',
				'&lt;script&gt;',
				'&lt;style&gt;',
				'&lt;video&gt;',
				'&lt;audio&gt;',
			);
			$allowed_HTML = '';
			foreach ( $cphtml as $index => $name ) {
				if ( in_array( (string) ( $index ), $this->instance['excerpt_allowed_elements'], true ) ) {
					$allowed_HTML .= $cphtml[ $index ];
				}
			}
			$text = strip_tags( $text, htmlspecialchars_decode( $allowed_HTML ) );
			$excerpt_length = $new_excerpt_length;

			if ( ! empty( $this->instance['excerpt_more_text'] ) ) {
				$excerpt_more = $this->excerpt_more_filter( $this->instance['excerpt_more_text'] );
			} elseif ( $filterName = key( $wp_filter['excerpt_more'][10] ) ) {
				$excerpt_more = $wp_filter['excerpt_more'][10][ $filterName ]['function']( 0 );
			} else {
				$excerpt_more = '[...]';
			}

			$words = explode( ' ', $text, $excerpt_length + 1 );
			if ( count( $words ) > $excerpt_length ) {
				array_pop( $words );
				array_push( $words, $excerpt_more );
				$text = implode( ' ', $words );
			}
		}

		return '<p>' . $text . '</p>';
	}

	/**
	 * Calculate the wp-query arguments matching the filter settings of the widget
	 *
	 * @param  array $instance Array which contains the various settings.
	 * @return array The array that can be fed to wp_Query to get the relevant posts
	 *
	 * @since 4.6
	 */
	function queryArgs( $instance ) {
		$valid_sort_orders = array( 'date', 'title', 'comment_count', 'rand' );
		if ( isset( $instance['sort_by'] ) && in_array( $instance['sort_by'], $valid_sort_orders, true ) ) {
			$sort_by = $instance['sort_by'];
		} else {
			$sort_by = 'date';
		}
		$sort_order = ( isset( $instance['asc_sort_order'] ) && $instance['asc_sort_order'] ) ? 'ASC' : 'DESC';

		// Get array of post info.
		$args = array(
			'orderby' => $sort_by,
			'order' => $sort_order,
		);

		$non_default_valid_status = array(
			'publish',
			'future',
			'publish,future',
			'private',
			'private,publish',
			'private,publish,future',
		);
		if ( isset( $instance['status'] ) && in_array( $instance['status'], $non_default_valid_status, true ) ) {
			$args['post_status'] = $instance['status'];
		}

		if ( isset( $instance['num'] ) ) {
			$args['showposts'] = (int) $instance['num'];
		}

		if ( isset( $instance['offset'] ) && ( (int) $instance['offset'] > 1 ) ) {
			$args['offset'] = (int) $instance['offset'] - 1;
		}
		if ( isset( $instance['cat'] ) ) {
			if ( 'rand' === $instance['cat'] ) {
				$categories = get_categories();
				$instance['cat'] = $categories[ array_rand( $categories, 1 ) ]->term_id;
			}

			if ( isset( $instance['no_cat_childs'] ) && $instance['no_cat_childs'] ) {
				$args['category__in'] = (int) $instance['cat'];
			} else {
				$args['cat'] = (int) $instance['cat'];
			}
		}

		if ( is_singular() && isset( $instance['exclude_current_post'] ) && $instance['exclude_current_post'] ) {
			$args['post__not_in'] = array( get_the_ID() );
		}

		if ( isset( $instance['hideNoThumb'] ) && $instance['hideNoThumb'] ) {
			$args = array_merge( $args, array(
					'meta_query' => array(
						array(
						 'key' => '_thumbnail_id',
						 'compare' => 'EXISTS',
				 		),
					),
				)
			);
		}

		return $args;
	}

	/**
	 * Calculate the HTML of the title based on the widget settings
	 *
	 * @param  string $before_title The sidebar configured HTML that should come
	 *                              before the title itself.
	 * @param  string $after_title The sidebar configured HTML that should come
	 *                              after the title itself.
	 * @param  array  $instance Array which contains the various settings.
	 * @return string The HTML for the title area
	 *
	 * @since 4.6
	 */
	function titleHTML( $before_title, $after_title, $instance ) {
		 $ret = '';

		// If not title, use the name of the category.
		if ( ! isset( $instance['title'] ) || ! $instance['title'] ) {
			$instance['title'] = '';
			if ( isset( $instance['cat'] ) ) {
				$category_info = get_category( $instance['cat'] );
				if ( $category_info && ! is_wp_error( $category_info ) ) {
					$instance['title'] = $category_info->name;
				} else {
					$instance['title'] = __( 'Recent Posts', 'category-posts' );
				}
			} else {
				$instance['title'] = __( 'Recent Posts', 'category-posts' );
			}
		}

		if ( ! ( isset( $instance['hide_title'] ) && $instance['hide_title'] ) ) {
			$ret = $before_title;
			if ( isset( $instance['is_shortcode'] ) ) {
				$title = esc_html( $instance['title'] );
			} else {
				$title = apply_filters( 'widget_title', $instance['title'] );
			}

			if ( isset( $instance['title_link'] ) && $instance['title_link'] && ( 0 !== $instance['cat'] ) ) {
				$ret .= '<a href="' . get_category_link( $instance['cat'] ) . '">' . $title . '</a>';
			} elseif ( isset( $instance['title_link_url'] ) && $instance['title_link_url'] && ( 0 === $instance['cat'] ) ) {
				$ret .= '<a href="' . $instance['title_link_url'] . '">' . $title . '</a>';
			} else {
				$ret .= $title;
			}

			$ret .= $after_title;
		}

		return $ret;
	}

	/**
	 * Calculate the HTML of the footer based on the widget settings
	 *
	 * @param  array $instance Array which contains the various settings.
	 * @return string The HTML for the footer area
	 *
	 * @since 4.6
	 */
	function footerHTML( $instance ) {

		$ret = '';
		$url = '';
		$text = '';

		if ( isset( $instance['footer_link'] ) ) {
			$url = $instance['footer_link'];
		}

		if ( isset( $instance['footer_link_text'] ) ) {
			$text = $instance['footer_link_text'];
		}

		// if url is set, but no text, just use the url as text.
		if ( empty( $text ) && ! empty( $url ) ) {
			$text = $url;
		}

		// if no url is set but just text, assume the url should be to the relevant archive page
		// category archive for categories filter and home page or blog page when "all categories"
		// is used.
		if ( ! empty( $text ) && empty( $url ) ) {
			if ( isset( $instance['cat'] ) && ( 0 !== $instance['cat'] ) && ( null !== get_category( $instance['cat'] ) ) ) {
				$url = get_category_link( $instance['cat'] );
			} else {
				$blog_page = get_option( 'page_for_posts' );
				if ( $blog_page ) {
					$url = get_permalink( $blog_page );
				} else {
					$url = home_url();
				}
			}
		}

		if ( ! empty( $url ) ) {
			$ret .= '<a class="cat-post-footer-link" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
		}

		return $ret;
	}

	/**
	 *  Convert pre 4.8 settings into template
	 *
	 * @param  array $instance Array which contains the various settings.
	 *
	 *  @since 4.8
	 */
	function convert_settings_to_template( $instance ) {
		$template = '';

		if ( ! ( isset( $instance['hide_post_titles'] ) && $instance['hide_post_titles'] ) ) {
			$template .= '%title%';
		}
		if ( isset( $instance['date'] ) && $instance['date'] ) {
			$template .= '\n\n%date%\n\n';
		}
		if ( isset( $instance['thumb'] ) && $instance['thumb'] ) {
			$template .= '\n\n%thumb%\n\n';
		}
		if ( isset( $instance['excerpt'] ) && $instance['excerpt'] ) {
			$template .= '%excerpt%';
		}
		if ( isset( $instance['comment_num'] ) && $instance['comment_num'] ) {
			$template .= '\n\n%commentnum%\n\n';
		}
		if ( isset( $instance['author'] ) && $instance['author'] ) {
			$template .= '\n\n%author%\n\n';
		}

		return $template;
	}

	/**
	 * Current post item date string based on the format requested in the settings
	 *
	 * @param  array $instance Array which contains the various settings.
	 * @param  bool  $everything_is_link Indicates whether the return string should avoid links.
	 *
	 * @since 4.8
	 */
	function itemDate( $instance, $everything_is_link ) {
		$ret = '';

		if ( ! isset( $instance['preset_date_format'] ) ) {
			$preset_date_format = 'other';
		} else {
			$preset_date_format = $instance['preset_date_format'];
		}

		switch ( $preset_date_format ) {
			case 'sitedateandtime' : $date = get_the_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
				break;
			case 'sitedate' : $date = get_the_time( get_option( 'date_format' ) );
				break;
			case 'sincepublished' : $date = human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) );
				break;
			default :
				if ( isset( $instance['date_format'] ) && strlen( trim( $instance['date_format'] ) ) > 0 ) {
					$date_format = $instance['date_format'];
				} else {
					$date_format = 'j M Y';
				}
				$date = get_the_time( $date_format );
				break;
		}
		$ret .= '<span class="cat-post-date">';
		if ( isset( $instance['date_link'] ) && $instance['date_link'] && ! $everything_is_link ) {
			$ret .= '<a href="' . \get_the_permalink() . '">';
		}
		$ret .= $date;

		if ( isset( $instance['date_link'] ) && $instance['date_link'] && ! $everything_is_link ) {
			$ret .= '</a>';
		}
		$ret .= '</span>';
		return $ret;
	}


	/**
	 * Calculate the HTML for showing the thumb of a post item.
	 * Expected to be called from a loop with globals properly set.
	 *
	 * @param  array $instance Array which contains the various settings.
	 * @param  bool  $no_link  indicates whether the thumb should be wrapped in a link or a span.
	 * @return string The HTML for the thumb related to the post
	 *
	 * @since 4.6
	 */
	function itemThumb( $instance, $no_link ) {
		 $ret = '';

		 if ( ( isset( $instance['default_thunmbnail'] ) && (0 !== $instance['default_thunmbnail'] ) ) || has_post_thumbnail() ) {

			$class              = '';
			$use_css_cropping   = isset( $this->instance['use_css_cropping'] ) && $this->instance['use_css_cropping'];
			$disable_css        = isset( $instance['disable_css'] ) && $instance['disable_css'];
			$everything_is_link = isset( $instance['everything_is_link'] ) && $instance['everything_is_link'];
			$show_post_format   = isset( $instance['show_post_format'] ) && ( 'none' !== $instance['show_post_format'] );

			if ( isset( $this->instance['thumb_hover'] ) && ! $everything_is_link && ! $disable_css ) {
				$class = 'class="cat-post-thumbnail cat-post-' . $instance['thumb_hover'] . '"';
			} else {
				$class = 'class="cat-post-thumbnail"';
			}

			$title_args = array( 'echo' => false );

			if ( $no_link ) {
				$ret .= '<span ' . $class . '>';
			} else {
				$ret .= '<a ' . $class . ' href="' . get_the_permalink() . '" title="' . the_title_attribute( $title_args ) . '">';
			}

			$ret .= $this->the_post_thumbnail( array( $this->instance['thumb_w'], $this->instance['thumb_h'] ) );

			if ( $show_post_format || $instance['thumb_hover'] ) {
				$format = get_post_format() ? : 'standard';
				$ret .= '<span class="cat-post-format cat-post-format-' . $format . '"></span>';
			}

			if ( $no_link ) {
				$ret .= '</span>';
			} else {
				$ret .= '</a>';
			}
		}

		return $ret;
	}

	/**
	 * Current post item categories string
	 *
	 * @param  array $instance Array which contains the various settings.
	 * @param  bool  $everything_is_link Indicates whether the return string should avoid links.
	 *
	 * @since 4.8
	 */
	function itemCategories( $instance, $everything_is_link ) {

		$ret = '<span class="cat-post-tax-category">';
		$cat_ids = wp_get_post_categories( get_the_ID(), array( 'number' => 0 ) );
		foreach ( $cat_ids as $cat_id ) {
			if ( $everything_is_link ) {
				$ret .= ' ' . get_cat_name( $cat_id );
			} else {
				$ret .= " <a href='" . get_category_link( $cat_id ) . "'>" . get_cat_name( $cat_id ) . '</a>';
			}
		}
		$ret .= '</span>';
		return $ret;
	}

	/**
	 * Current post item tags string
	 *
	 * @param  array $instance Array which contains the various settings.
	 * @param  bool  $everything_is_link Indicates whether the return string should avoid links.
	 *
	 * @since 4.8
	 */
	function itemTags( $instance, $everything_is_link ) {

		$ret = '<span class="cat-post-tax-post_tag">';
		$tag_ids = wp_get_post_tags( get_the_ID(), array( 'number' => 0 ) );
		foreach ( $tag_ids as $tag_id ) {
			if ( $everything_is_link ) {
				$ret .= ' ' . $tag_id->name;
			} else {
				$ret .= " <a href='" . get_tag_link( $tag_id->term_id ) . "'>" . $tag_id->name . '</a>';
			}
		}
		$ret .= '</span>';
		return $ret;
	}

	/**
	 * Current post item comment number string
	 *
	 * @param  array $instance Array which contains the various settings.
	 * @param  bool  $everything_is_link Indicates whether the return string should avoid links.
	 *
	 * @since 4.8
	 */
	function itemCommentNum( $instance, $everything_is_link ) {

		$ret = '<span class="cat-post-comment-num">';

		if ( $everything_is_link ) {
			$ret .= '(' . \get_comments_number() . ')';
		} else {
			$link = sprintf(
				'<a href="%1$s" title="%2$s">(%3$d)</a>',
				esc_url( get_comments_link( $post->ID ) ),
				esc_attr( sprintf( __( '(%d) comments to this post' ), get_comments_number() ) ),
				get_comments_number()
			);
			$ret .= $link;
		}

		$ret .= '</span>';
		return $ret;
	}

	/**
	 * Current post item author string
	 *
	 * @param  array $instance Array which contains the various settings.
	 * @param  bool  $everything_is_link Indicates whether the return string should avoid links.
	 *
	 * @since 4.8
	 */
	function itemAuthor( $instance, $everything_is_link ) {

		$ret .= '<span class="cat-post-author">';
		global $authordata;
		if ( $everything_is_link ) {
			$ret .= get_the_author();
		} else {
			$link = sprintf(
				'<a href="%1$s" title="%2$s" rel="author">%3$s</a>',
				esc_url( get_author_posts_url( $authordata->ID, $authordata->user_nicename ) ),
				esc_attr( sprintf( __( 'Posts by %s' ), get_the_author() ) ),
				get_the_author()
			);
			$ret .= $link;
		}
		$ret .= '</span>';
		return $ret;
	}

	/**
	 * Current post item excerpt string
	 *
	 * @param  array $instance Array which contains the various settings.
	 * @param  bool  $everything_is_link Indicates whether the return string should avoid links.
	 *
	 * @since 4.8
	 */
	function itemExcerpt( $instance, $everything_is_link ) {

		// use the_excerpt filter to get the "normal" excerpt of the post
		// then apply our filter to let users customize excerpts in their own way.
		if ( isset( $instance['excerpt_length'] ) && ( $instance['excerpt_length'] > 0) ) {
			$length = (int) $instance['excerpt_length'];
		} else {
			$length = 55; // Use the wordpress default.
		}

		if ( ! isset( $instance['excerpt_filters'] ) || $instance['excerpt_filters'] ) { // pre 4.7 widgets has filters on.
			$excerpt = apply_filters( 'the_excerpt', \get_the_excerpt() );
		} else { // if filters off replicate functionality of core generating excerpt.
			$more_text = '[&hellip;]';
			if ( isset( $instance['excerpt_more_text'] ) && $instance['excerpt_more_text'] ) {
				$more_text = ltrim( $instance['excerpt_more_text'] );
			}

			if ( $everything_is_link ) {
				$excerpt_more_text = ' <span class="cat-post-excerpt-more">' . $more_text . '</span>';
			} else {
				$excerpt_more_text = ' <a class="cat-post-excerpt-more" href="' . get_permalink() . '" title="' . sprintf( __( 'Continue reading %s' ), get_the_title() ) . '">' . $more_text . '</a>';
			}
			if ( '' === $post->post_excerpt ) {
				$text = get_the_content( '' );
				$text = strip_shortcodes( $text );
				$excerpt = \wp_trim_words( $text, $length, $excerpt_more_text );
				// adjust html output same way as for the normal excerpt,
				// just force all functions depending on the_excerpt hook.
				$excerpt = shortcode_unautop( wpautop( convert_chars( convert_smilies( wptexturize( $excerpt ) ) ) ) );
			} else {
				$excerpt = $post->post_excerpt . $excerpt_more_text;
				$excerpt = shortcode_unautop( wpautop( convert_chars( convert_smilies( wptexturize( $excerpt ) ) ) ) );
			}
		}
		$ret = apply_filters( 'cpw_excerpt', $excerpt, $this );
		return $ret;
	}

	/**
	 * Current post item title string
	 *
	 * @param  array $instance Array which contains the various settings.
	 * @param  bool  $everything_is_link Indicates whether the return string should avoid links.
	 *
	 * @since 4.8
	 */
	function itemTitle( $instance, $everything_is_link ) {

		$ret = '';

		if ( $everything_is_link ) {
			$ret .= '<span class="cat-post-title">' . get_the_title() . '</span>';
		} else {
			$ret .= '<a class="cat-post-title"';
			$ret .= ' href="' . get_the_permalink() . '" rel="bookmark">' . get_the_title();
			$ret .= '</a>';
		}
		return $ret;
	}

	/**
	 * Calculate the HTML for a post item based on the widget settings and post.
	 * Expected to be called in an active loop with all the globals set.
	 *
	 * @param  array        $instance Array which contains the various settings.
	 * @param  null|integer $current_post_id If on singular page specifies the id of
	 *                      the post, otherwise null.
	 * @return string The HTML for item related to the post
	 *
	 * @since 4.6
	 */
	function itemHTML( $instance, $current_post_id ) {
		global $post;

		$everything_is_link = isset( $instance['everything_is_link'] ) && $instance['everything_is_link'];

		$template = '';
		if ( isset( $instance['template'] ) ) {
			$template = $instance['template'];
		} else {
			$template = $this->convert_settings_to_template( $instance );
		}
		$ret = '<li ';

		// Current post.
		if ( $current_post_id === $post->ID ) {
			$ret .= "class='cat-post-item cat-post-current'";
		} else {
			$ret .= "class='cat-post-item'";
		}
		$ret .= '>'; // close the li opening tag.

		if ( $everything_is_link ) {
			$ret .= '<a class="cat-post-everything-is-link" href="' . get_the_permalink() . '" title="">';
		}

		// Post details (Template).
		$widget = $this;
		$ret .= preg_replace_callback( get_template_regex(), function ( $matches ) use ( $widget, $instance, $everything_is_link ) {
			switch ( $matches[0] ) {
				case '%title%' : return $widget->itemTitle( $instance, $everything_is_link );
					break;
				case '%author%' : return $widget->itemAuthor( $instance, $everything_is_link );
					break;
				case '%commentnum%' : return $widget->itemCommentNum( $instance, $everything_is_link );
					break;
				case '%date%' : return $widget->itemDate( $instance, $everything_is_link );
					break;
				case '%thumb%' : return $widget->itemThumb( $instance, $everything_is_link );
					break;
				case '%post_tag%' : return $widget->itemTags( $instance, $everything_is_link );
					break;
				case '%category%' : return $widget->itemCategories( $instance, $everything_is_link );
					break;
				case '%excerpt%' : return $widget->itemExcerpt( $instance, $everything_is_link );
					break;
				return $matches[0];
			}
		}, $template );

		// Replace empty line with closin and opening P.
		$ret = str_replace( "\n", '</p><p>', $ret );
		$ret = '<p>' . $ret . '</p>';

		// replace new lines with spaces.
		$ret = str_replace( "\n", ' ',$ret );

		if ( $everything_is_link ) {
			$ret .= '</a>';
		}

		$ret .= '</li>';
		return $ret;
	}

	/**
	 * Filter to set the number of words in an excerpt
	 *
	 * @param  int $length The number of words as configured by wordpress core or set by previous filters.
	 * @return int The number of words configured for the widget,
	 *             or the $length parameter if it is not configured or garbage value.
	 *
	 * @since 4.6
	 */
	function excerpt_length_filter( $length ) {
		if ( isset( $this->instance['excerpt_length'] ) && $this->instance['excerpt_length'] > 0 ) {
			$length = $this->instance['excerpt_length'];
		}
		return $length;
	}

	/**
	 * Set the proper excerpt filters based on the settings
	 *
	 * @param  array $instance widget settings.
	 * @return void
	 *
	 * @since 4.6
	 */
	function setExcerpFilters( $instance ) {

		if ( isset( $instance['excerpt'] ) && $instance['excerpt'] ) {

			// Excerpt length filter.
			if ( isset( $instance['excerpt_length'] ) && ( (int) $instance['excerpt_length'] ) > 0 ) {
				add_filter( 'excerpt_length', array( $this, 'excerpt_length_filter' ) );
			}

			if ( isset( $instance['excerpt_more_text'] ) && ( '' !== ltrim( $instance['excerpt_more_text'] ) ) ) {
				add_filter( 'excerpt_more', array( $this, 'excerpt_more_filter' ) );
			}

			if ( isset( $instance['excerpt_allow_html'] ) ) {
				remove_filter( 'get_the_excerpt', 'wp_trim_excerpt' );
				add_filter( 'the_excerpt', array( $this, 'allow_html_excerpt' ) );
			} else {
				add_filter( 'the_excerpt' , array( $this, 'apply_the_excerpt' ) );
			}
		}
	}

	/**
	 * Remove the excerpt filter
	 *
	 * @param  array $instance widget settings.
	 * @return void
	 *
	 * @since 4.6
	 */
	function removeExcerpFilters( $instance ) {
		remove_filter( 'excerpt_length', array( $this, 'excerpt_length_filter' ) );
		remove_filter( 'excerpt_more', array( $this, 'excerpt_more_filter' ) );
		add_filter( 'get_the_excerpt', 'wp_trim_excerpt' );
		remove_filter( 'the_excerpt', array( $this, 'allow_html_excerpt' ) );
		remove_filter( 'the_excerpt', array( $this, 'apply_the_excerpt' ) );
	}

	/**
	 * The main widget display controller
	 *
	 * Called by the sidebar processing core logic to display the widget.
	 *
	 * @param array $args An array containing the "environment" setting for the widget,
	 *                     namely, the enclosing tags for the widget and its title.
	 * @param array $instance The settings associate with the widget.
	 *
	 * @since 4.1
	 */
	function widget( $args, $instance ) {

		extract( $args );
		$this->instance = $instance;

		$args = $this->queryArgs( $instance );
		$cat_posts = new \WP_Query( $args );

		if ( ! isset( $instance['hide_if_empty'] ) || ! $instance['hide_if_empty'] || $cat_posts->have_posts() ) {
			echo $before_widget;
			echo $this->titleHTML( $before_title, $after_title, $instance );

			$current_post_id = null;
			if ( is_singular() ) {
				$current_post_id = get_the_ID();
			}

			if ( ! ( isset( $instance['is_shortcode'] ) && $instance['is_shortcode'] ) ) { // the intenal id is needed only for widgets.
				echo '<ul id="' . WIDGET_BASE_ID . '-' . $this->number . '-internal" class="' . WIDGET_BASE_ID . '-internal' . "\">\n";
			} else {
				echo '<ul>';
			}

			// set widget filters.
			if ( ! isset( $instance['excerpt_filters'] ) || $instance['excerpt_filters'] ) { // pre 4.7 widgets has filters on.
				$this->setExcerpFilters( $instance );
			}

			while ( $cat_posts->have_posts() ) {
				$cat_posts->the_post();
				echo $this->itemHTML( $instance, $current_post_id );
			}
			echo "</ul>\n";

			echo $this->footerHTML( $instance );
			echo $after_widget;

			// remove widget filters.
			if ( ! isset( $instance['excerpt_filters'] ) || $instance['excerpt_filters'] ) { // pre 4.7 widgets has filters on.
				$this->removeExcerpFilters( $instance );
			}

			wp_reset_postdata();

			add_action( 'wp_footer',
				function () use ( $number, $instance ) {
					footer_script( $number, $instance );
				},
			100 );

			$use_css_cropping = isset( $this->instance['use_css_cropping'] ) && $this->instance['use_css_cropping'];

			if ( $use_css_cropping ) {
				// enqueue relevant scripts and parameters to perform cropping
				// once we support only 4.5+ it can be refactored to use wp_add_inline_script.
				wp_enqueue_script( 'jquery' ); // just in case the theme or other plugins didn't enqueue it.
				add_action( 'wp_footer', __NAMESPACE__ . '\change_cropped_image_dimensions', 100 );  // add to the footer the cropping script.
				$number = $this->number;
				// a temporary hack to handle difference in the number in a true widget
				// and the number format expected at the rest of the places.
				if ( is_numeric( $number ) ) {
					$number = WIDGET_BASE_ID . '-' . $number;
				}

				// add Javascript to change change cropped image dimensions on load and window resize.
				$thumb_w = $this->instance['thumb_w'];
				$thumb_h = $this->instance['thumb_h'];
				add_filter( 'cpw_crop_widgets',
					function ( $a ) use ( $number, $thumb_w, $thumb_h ) {
						$a[ $number ] = $thumb_w / $thumb_h;
						return $a;
					}
				);
			}
		}
	}

	/**
	 * Update the options.
	 *
	 * @param  array $new_instance The new settings of the widget.
	 * @param  array $old_instance The current settings of the widget.
	 * @return array
	 */
	function update( $new_instance, $old_instance ) {

		$new_instance['title'] = sanitize_text_field( $new_instance['title'] );  // sanitize the title like core widgets do.
		if ( ! isset( $new_instance['excerpt_filters'] ) ) {
			$new_instance['excerpt_filters'] = '';
		}
		if ( current_user_can( 'unfiltered_html' ) ) {
			$instance['text'] = $new_instance['template'];
		} else {
			$instance['text'] = wp_kses_post( $new_instance['template'] );
		}
		return $new_instance;
	}

	/**
	 * Output the title panel of the widget configuration form.
	 *
	 * @param  array $instance The widget's settings.
	 * @return void
	 *
	 * @since 4.6
	 */
	private function formTitlePanel( $instance ) {
		$cat = $instance['cat'];
		$hide_title = false;
		if ( isset( $instance['hide_title'] ) && $instance['hide_title'] ) {
			$hide_title = true;
		}
?>
	<h4 data-panel="title"><?php esc_html_e( 'Title', 'category-posts' )?></h4>
	<div>
		<?php echo $this->get_checkbox_block_html( $instance, 'hide_title', esc_html__( 'Hide title','category-posts' ), false, true );?>
		<div class="cpwp_ident categoryposts-data-panel-title-settings" <?php if ( $hide_title ) { echo 'style="display:none"'; } ?>>
			<?php echo $this->get_text_input_block_html( $instance, 'title',  esc_html__( 'Title','category-posts' ), '', __( 'Recent Posts','category-posts' ), true );?>
			<?php echo $this->get_checkbox_block_html( $instance, 'title_link', esc_html__( 'Make widget title link','category-posts' ), false, 0 !== $cat );?>
				<?php echo $this->get_text_input_block_html( $instance, 'title_link_url', esc_html__( 'Title link URL','category-posts' ), '', '', 0 === $cat );?>
			</div>
		</div>
<?php
	}

	/**
	 * Output the filter panel of the widget configuration form.
	 *
	 * @param  array $instance The parameters configured for the widget.
	 * @return void
	 *
	 * @since 4.6
	 */
	function formFilterPanel( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'cat' => 0 ) );
		$cat = $instance['cat'];
?>
	<h4 data-panel="filter"><?php esc_html_e( 'Filter', 'category-posts' );?></h4>
	<div>
		<p>
			<label>
				<?php esc_html_e( 'Category', 'category-posts' ); ?>:
				<?php wp_dropdown_categories( array(
													'show_option_all' => __( 'All categories', 'category-posts' ),
													'hide_empty' => 0,
													'name' => $this->get_field_name( 'cat' ),
													'selected' => $instance['cat'],
													'class' => 'categoryposts-data-panel-filter-cat',
													'show_option_none'   => __( 'Show categories randomly', 'category-posts' ),
													'option_none_value'  => 'rand',
				) );
				?>
			</label>
		</p>
		<?php
			echo $this->get_checkbox_block_html( $instance, 'no_cat_childs', esc_html__( 'Exclude child categories', 'category-posts' ), false, true );
			echo $this->get_select_block_html( $instance, 'status', esc_html__( 'Status', 'category-posts' ), array(
																'default' => esc_html__( 'WordPress Default', 'category-posts' ),
																'publish' => esc_html__( 'Published', 'category-posts' ),
																'future' => esc_html__( 'Scheduled', 'category-posts' ),
																'private' => esc_html__( 'Private', 'category-posts' ),
																'publish,future' => esc_html__( 'Published or Scheduled', 'category-posts' ),
																'private,publish' => esc_html__( 'Published or Private', 'category-posts' ),
																'private,future' => esc_html__( 'Private or Scheduled', 'category-posts' ),
																'private,publish,future' => esc_html__( 'Published, Private or Scheduled', 'category-posts' ),
			), 'default', true );
		    echo $this->get_number_input_block_html( $instance, 'num', esc_html__( 'Number of posts to show', 'category-posts' ), get_option( 'posts_per_page' ), 1,'', '', true );
			echo $this->get_number_input_block_html( $instance, 'offset', esc_html__( 'Start with post','category-posts' ), 1, 1,'', '', true );
			echo $this->get_select_block_html( $instance, 'sort_by', esc_html__( 'Sort by','category-posts' ), array(
																'date' => esc_html__( 'Date', 'category-posts' ),
																'title' => esc_html__( 'Title', 'category-posts' ),
																'comment_count' => esc_html__( 'Number of comments', 'category-posts' ),
																'rand' => esc_html__( 'Random', 'category-posts' ),
			), 'date', true );
			echo $this->get_checkbox_block_html( $instance, 'asc_sort_order', esc_html__( 'Reverse sort order (ascending)','category-posts' ), false, true );
			echo $this->get_checkbox_block_html( $instance, 'exclude_current_post', esc_html__( 'Exclude current post','category-posts' ), false, true );
			echo $this->get_checkbox_block_html( $instance, 'hideNoThumb', esc_html__( 'Exclude posts which have no thumbnail','category-posts' ), false, true );
			?>
		</div>
<?php
	}

	/**
	 * Generate the wrapper P around a form input element
	 *
	 * @since 4.8
	 * @param string $html	The HTML to wrap.
	 * @param string $key	The key to use as the prefix to the class.
	 * @param bool	 $visible	Indicates if the element should be visible when rendered.
	 *
	 * @return string	HTML with P element contaning the html being passed with class based on the key
	 *					and style set to display:none if visibility is off.
	 */
	private function get_wrap_block_html( $html, $key, $visible ) {

		$cl = ' class="' . __NAMESPACE__ . '-' . esc_attr( $key ) . '"';

		$style = '';
		if ( ! $visible ) {
			$style = ' style="display:none"';
		}
		$ret = '<p' . $cl . $style . ">\n" . $html . "</p>\n";

		return $ret;
	}

	/**
	 * Generate a form P element containing a select element
	 *
	 * @since 4.8
	 * @param array	 $instance	The instance.
	 * @param string $key		The key in the instance array.
	 * @param string $label		The label to display and associate with the input.
	 * @param array	 $list		An array of pairs value (index) => label to be used for the options.
	 *                          The labels are expected to be html escaped.
	 * @param int	 $default	The value to use if the key is not set in the instance.
	 * @param bool	 $visible	Indicates if the element should be visible when rendered.
	 *
	 * @return string HTML a P element contaning the select, its label, class based on the key
	 *					and style set to display:none if visibility is off.
	 */
	private function get_select_block_html( $instance, $key, $label, $list, $default, $visible ) {
		$value = $default;

		if ( isset( $instance[ $key ] ) ) {
			$value = $instance[ $key ];
		}

		if ( ! array_key_exists( $value, $list ) ) {
			$value = $default;
		}

		$ret = '<label for="' . $this->get_field_id( $key ) . "\">\n" .
					$label .
				"</label>\n" .
				'<select d="' . $this->get_field_id( $key ) . '" name="' . $this->get_field_name( $key ) . '"  autocomplete="off">' . "\n";
		foreach ( $list as $v => $l ) {
			$ret .= '<option value="' . esc_attr( $v ) . '" ' . selected( $v, $value, false ) . '>' . $l . "</option>\n";
		}
		$ret .= "</select>\n";

		return $this->get_wrap_block_html( $ret, $key, $visible );
	}

	/**
	 * Generate a form P element containing a textarea input
	 *
	 * @since 4.8
	 * @param array	 $instance		The instance.
	 * @param string $key			The key in the instance array.
	 * @param string $label			The label to display and associate with the input (should be html escaped).
	 * @param int	 $default		The value to use if the key is not set in the instance.
	 * @param string $placeholder	The placeholder to use in the input (should be attribute escaped).
	 * @param bool	 $visible		Indicates if the element should be visible when rendered.
	 * @param int	 $num_rows		Number of rows.
	 *
	 * @return string HTML a P element containing the input, its label, class based on the key
	 *					and style set to display:none if visibility is off.
	 */
	private function get_textarea_html( $instance, $key, $label, $default, $placeholder, $visible, $num_rows ) {

		$value = $default;

		if ( isset( $instance[ $key ] ) ) {
			$value = $instance[ $key ];
		}

		$ret = '<p><label for="' . esc_attr( $this->get_field_id( $key ) ) . '">' . $label . '</label></p>' .
					'<textarea rows="' . esc_attr( $num_rows ) . '" placeholder="' . $placeholder . '" id="' . esc_attr( $this->get_field_id( $key ) ) . '" name="' . esc_attr( $this->get_field_name( $key ) ) . '" type="text" autocomplete="off">' . esc_textarea( $value ) . '</textarea>';

		return $this->get_wrap_block_html( $ret, $key, $visible );
	}

	/**
	 * Generate a form P element containing a text input
	 *
	 * @since 4.8
	 * @param array	 $instance	The instance.
	 * @param string $key		The key in the instance array.
	 * @param string $label		The label to display and associate with the input.
	 *                          Should be html escaped.
	 * @param int	 $default	The value to use if the key is not set in the instance.
	 * @param string $placeholder	The placeholder to use in the input. should be attribute escaped.
	 * @param bool	 $visible	Indicates if the element should be visible when rendered.
	 *
	 * @return string HTML a P element contaning the input, its label, class based on the key
	 *					and style set to display:none if visibility is off.
	 */
	private function get_text_input_block_html( $instance, $key, $label, $default, $placeholder, $visible ) {

		$value = $default;

		if ( isset( $instance[ $key ] ) ) {
			$value = $instance[ $key ];
		}

		$ret = '<label for="' . $this->get_field_id( $key ) . "\">\n" .
					$label .
					'<input placeholder="' . $placeholder . '" id="' . $this->get_field_id( $key ) . '" name="' . $this->get_field_name( $key ) . '" type="text" value="' . esc_attr( $value ) . '" autocomplete="off"/>' . "\n" .
				"</label>\n";

		return $this->get_wrap_block_html( $ret, $key, $visible );
	}

	/**
	 * Generate a form P element containing a number input
	 *
	 * @since 4.8
	 * @param array	 $instance	The instance.
	 * @param string $key		The key in the instance array.
	 * @param string $label		The label to display and associate with the input.
	 *                          expected to be escaped.
	 * @param int	 $default	The value to use if the key is not set in the instance.
	 * @param int	 $min		The minimum value allowed to be input.
	 * @param int	 $max		The maximum value allowed to be input.
	 * @param string $placeholder	The placeholder string to be used. expected to be escaped.
	 * @param bool	 $visible	Indicates if the element should be visible when rendered.
	 *
	 * @return string HTML a P element contaning the input, its label, class based on the key
	 *					and style set to display:none if visibility is off.
	 */
	private function get_number_input_block_html( $instance, $key, $label, $default, $min, $max, $placeholder, $visible ) {

		$value = $default;

		if ( isset( $instance[ $key ] ) ) {
			$value = $instance[ $key ];
		}

		$minmax = '';
		if ( '' !== $min ) {
			$minmax .= ' min="' . $min . '"';
		}
		if ( '' !== $max ) {
			$minmax .= ' max="' . $max . '"';
		}

		$ret = '<label for="' . $this->get_field_id( $key ) . "\">\n" .
					esc_html( $label ) .
					'<input placeholder="' . $placeholder . '" id="' . esc_attr( $this->get_field_id( $key ) ) . '" name="' . esc_attr( $this->get_field_name( $key ) ) . '" class="' . esc_attr( $key ) . '" type="number"' . $minmax . ' value="' . esc_attr( $value ) . '" autocomplete="off" />' . "\n" .
				"</label>\n";

		return $this->get_wrap_block_html( $ret, $key, $visible );
	}

	/**
	 * Generate a form P element containing a checkbox input
	 *
	 * @since 4.8
	 * @param array	 $instance	The instance.
	 * @param string $key		The key in the instance array.
	 * @param string $label		The label to display and associate with the checkbox.
	 *                          should be escaped string.
	 * @param bool	 $default	The value to use if the key is not set in the instance.
	 * @param bool	 $visible	Indicates if the element should be visible when rendered.
	 *
	 * @return string HTML a P element contaning the checkbox, its label, class based on the key
	 *					and style set to display:none if visibility is off.
	 */
	private function get_checkbox_block_html( $instance, $key, $label, $default, $visible ) {

		$value = $default;

		if ( array_key_exists( $key, $instance ) ) {
			if ( $instance[ $key ] ) {
				$value = true;
			} else {
				$value = false;
			}
		}
		$ret = '<label for="' . esc_attr( $this->get_field_id( $key ) ) . "\">\n" .
					'<input id="' . esc_attr( $this->get_field_id( $key ) ) . '" name="' . esc_attr( $this->get_field_name( $key ) ) . '" type="checkbox" ' . checked( $value, true, false ) . ' autocomplete="off"/>' . "\n" .
					$label .
				"</label>\n";

		return $this->get_wrap_block_html( $ret, $key, $visible );
	}

	/**
	 * The widget configuration form back end.
	 *
	 * @param  array $instance The parameters associated with the widget.
	 * @return void
	 */
	function form( $instance ) {
		if ( 0 === count( $instance ) ) { // new widget, use defaults.
			$instance = default_settings();
		} else { // updated widgets come from =< 4.6 excerpt filter is on.
			if ( ! isset( $instance['excerpt_filters'] ) ) {
				$instance['excerpt_filters'] = 'on';
			}
		}

		$instance = wp_parse_args( (array) $instance, array(
			'hide_post_titles'                => '',
			'excerpt'                         => '',
			'excerpt_more_text'               => '',
			'excerpt_filters'                 => '',
			'date'                            => '',
			'date_format'                     => '',
			'assigned_categories'             => '',
			'disable_css'                     => '',
			'disable_font_styles'             => '',
			'hide_if_empty'                   => '',
			'hide_social_buttons'             => '',
			'preset_date_format'              => 'other',
			'thumb'                           => false,
			'thumb_w'                         => get_option( 'thumbnail_size_w', 150 ),
			'thumb_h'                         => get_option( 'thumbnail_size_h', 150 ),
			'default_thunmbnail'              => 0,
			'use_css_cropping'                => true,
		) );

		$hide_post_titles                = $instance['hide_post_titles'];
		$excerpt                         = $instance['excerpt'];
		$excerpt_more_text               = $instance['excerpt_more_text'];
		$excerpt_filters                 = $instance['excerpt_filters'];
		$date                            = $instance['date'];
		$date_format                     = $instance['date_format'];
		$assigned_categories             = $instance['assigned_categories'];
		$disable_css                     = $instance['disable_css'];
		$disable_font_styles             = $instance['disable_font_styles'];
		$hide_if_empty                   = $instance['hide_if_empty'];
		$preset_date_format              = $instance['preset_date_format'];
		$thumb                           = ! empty( $instance['thumb'] );
		$thumb_w                         = $instance['thumb_w'];
		$thumb_h                         = $instance['thumb_h'];
		$default_thunmbnail              = $instance['default_thunmbnail'];
		$use_css_cropping                = $instance['use_css_cropping'];

		$cat = $instance['cat'];

		if ( ! isset( $style_done ) ) { // what an ugly hack, but can't figure out how to do it nicer on 4.3.
		?>
		<style type="text/css">
		.cpwp_ident {
			color: #6A6A6A;
			background: #F1F1F1;
			padding: 5px;
		}
		.cpwp_ident > .cpwp_ident {
			border-left:5px solid #B3B3B3;
			padding: 0 10px;
		}
		.cpwp_ident > p {
			margin: 5px 0;
		}
		.cpwp_ident > label {
			line-height: 2.75;
			display: inline-block;
		}
		.cpwp_ident_top {
			margin-top:-1em;
			padding-top:1em;
		}

		.category-widget-cont input[type="number"] {
			width:5em;
			text-align:center;
		}

		.categoryposts-template-help th {
			text-align:start;
			font-weight:bold;
		}

		.categoryposts-template-help td {
			padding:2px;
		}

		.categoryPosts-template textarea {
			font-size:16px;
			line-height:20px;
		}
		</style>

		<?php
			$style_done = true;
		}
		?>

		<div class="category-widget-cont">
			<p><a target="_blank" href="http://tiptoppress.com/term-and-category-based-posts-widget/?utm_source=widget_cpw&utm_campaign=get_pro_cpw&utm_medium=form"><?php esc_html_e( 'Get the Pro version', 'category-posts' ); ?></a></p>
			<?php
			$this->formTitlePanel( $instance );
			$this->formFilterPanel( $instance );
			?>
			<h4 data-panel="details"><?php esc_html_e( 'Post details', 'category-posts' )?></h4>
			<div>
				<?php
				echo $this->get_checkbox_block_html( $instance, 'everything_is_link', esc_html__( 'Everything is a link','category-posts' ), false, true );
				$template = '';
				if ( ! isset( $instance['template'] ) ) {
					$template = $this->convert_settings_to_template( $instance );
				} else {
					$template = $instance['template'];
				}
				?>
				<p><?php esc_html_e( 'Displayed parts', 'category-posts' ); ?></p>
				<div class="cpwp_ident">
					<?php
					echo $this->get_textarea_html( $instance, 'template', esc_html__( 'Template', 'category-posts' ) . ' <button type="button" class="dashicons open-template-help dashicons-editor-help imgedit-help-toggle"><span class="screen-reader-text">' . esc_html__( 'Show template help', 'category-posts' ) . '</span></button>', $template, '', true, 5 );
					preg_match_all( get_template_regex(), $template, $matches );
					$tags = array();
					if ( ! empty( $matches[0] ) ) {
						$tags = array_flip( $matches[0] );
					}
					?>
					<div class="cat-post-template-help">
						<p><?php esc_html_e( 'The following text will be replaced with the relevant information. In addition you can use any text and html (if you have the permisions) anywhere you want', 'category-posts' )?>
						</p>
						<table>
							<tr>
								<th><?php esc_html_e( 'New line', 'category-posts' )?></th>
								<td><?php esc_html_e( 'Space', 'category-posts' )?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Empty line', 'category-posts' )?></th>
								<td><?php esc_html_e( 'Next line is a paragraph', 'category-posts' )?></td>
							</tr>
							<tr>
								<th>%title%</th>
								<td><?php esc_html_e( 'Post title', 'category-posts' )?></td>
							</tr>
							<tr>
								<th>%thumb%</th>
								<td><?php esc_html_e( 'Post thumbnail', 'category-posts' )?></td>
							</tr>
							<tr>
								<th>%date%</th>
								<td><?php esc_html_e( 'Post publish date', 'category-posts' )?></td>
							</tr>
							<tr>
								<th>%excerpt%</th>
								<td><?php esc_html_e( 'Post excerpt', 'category-posts' )?></td>
							</tr>
							<tr>
								<th>%author%</th>
								<td><?php esc_html_e( 'Post author', 'category-posts' )?></td>
							</tr>
							<tr>
								<th>%commentnum%</th>
								<td><?php esc_html_e( 'The number of comments to the post', 'category-posts' )?></td>
							</tr>
							<tr>
								<th>%post_tag%</th>
								<td><?php esc_html_e( 'Post tags', 'category-posts' )?></td>
							</tr>
							<tr>
								<th>%category%</th>
								<td><?php esc_html_e( 'Post categories', 'category-posts' )?></td>
							</tr>
						</table>
					</div>
					<div class="cat-post-premade_templates">
						<p><label><?php esc_html_e( 'Select Premade template', 'category-posts' ) ?></label></p>
						<select>
							<option value="title"><?php esc_html_e( 'Title only', 'category-posts' )?></option>
							<option value="title_excerpt"><?php esc_html_e( 'Title and Excerpt', 'category-posts' )?></option>
							<option value="title_thumb"><?php esc_html_e( 'Title and Thumbnail', 'category-posts' )?></option>
							<option value="title_thum_excerpt"><?php esc_html_e( 'Title, Thumbnail and Excerpt', 'category-posts' )?></option>
							<option value="everything"><?php esc_html_e( 'Everything', 'category-posts' )?></option>
						</select>
						<p><button type="button" class="button"><?php esc_html_e( 'Select this template', 'category-posts' )?></button></p>
					</div>
				</div>

				<?php // Excerpt settings. ?>
				<div class="categoryposts-data-panel-excerpt" style="display:<?php echo ( isset( $tags['%excerpt%'] ) ) ? 'block' : 'none'?>">
					<p><?php esc_html_e( 'Excerpt settings', 'category-posts' );?></p>
					<div class="cpwp_ident">
					<?php
					echo $this->get_number_input_block_html( $instance, 'excerpt_length', esc_html__( 'Excerpt length (in words):','category-posts' ), get_option( 'posts_per_page' ), 1, 55, '', true );
					echo $this->get_text_input_block_html( $instance, 'excerpt_more_text',  esc_html__( 'Excerpt \'more\' text:','category-posts' ), '', esc_attr__( '...', 'category-posts' ), true );
					echo $this->get_checkbox_block_html( $instance, 'excerpt_filters', esc_html__( 'Don\'t override Themes and plugin filters','category-posts' ), false, true );
					?>
					</div>
				</div>
				<div class="categoryposts-data-panel-date" style="display:<?php echo ( isset( $tags['%date%'] ) ) ? 'block' : 'none'?>">
					<p><?php esc_html_e( 'Date format settings', 'category-posts' );?></p>
					<div class="cpwp_ident">
						<?php
						echo $this->get_select_block_html( $instance, 'preset_date_format', esc_html__( 'Date format', 'category-posts' ), array(
																			'sitedateandtime' => esc_html__( 'Site date and time', 'category-posts' ),
																			'sitedate' => esc_html__( 'Site date', 'category-posts' ),
																			'sincepublished' => esc_html__( 'Time since published', 'category-posts' ),
																			'other' => esc_html__( 'PHP style format', 'category-posts' ),
						), 'sitedateandtime', true );
						echo $this->get_text_input_block_html( $instance, 'date_format',  esc_html__( 'PHP Style Date format','category-posts' ), '', 'j M Y', 'other' === $preset_date_format );
						?>
					</div>
				</div>

				<?php // Thumbnail settings. ?>
				<div class="categoryposts-data-panel-thumb" style="display:<?php echo ( isset( $tags['%thumb%'] ) ) ? 'block' : 'none'?>">
					<p><?php esc_html_e( 'Thumbnail settings', 'category-posts' );?></p>
					<div class="cpwp_ident">
						<p>
							<label>
								<?php esc_html_e( 'Thumbnail dimensions (in pixels)', 'category-posts' ); ?><br />
								<label for="<?php echo esc_attr( $this->get_field_id( 'thumb_w' ) ); ?>">
									<?php esc_html_e( 'Width:', 'category-posts' )?> <input style="width:30%;" type="number" min="1" id="<?php echo esc_attr( $this->get_field_id( 'thumb_w' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumb_w' ) ); ?>" value="<?php echo esc_attr( $instance['thumb_w'] ); ?>" />
								</label>

								<label for="<?php echo esc_attr( $this->get_field_id( 'thumb_h' ) ); ?>">
									<?php esc_html_e( 'Height:', 'category-posts' )?> <input style="width:30%;" type="number" min="1" id="<?php echo esc_attr( $this->get_field_id( 'thumb_h' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumb_h' ) ); ?>" value="<?php echo esc_attr( $instance['thumb_h'] ); ?>" />
								</label>
							</label>
						</p>
						<?php
						echo $this->get_checkbox_block_html( $instance, 'use_css_cropping', esc_html__( 'CSS crop to requested size','category-posts' ), false, false );
						echo $this->get_select_block_html( $instance, 'thumb_hover', esc_html__( 'Animation on mouse hover:','category-posts' ), array(
																			'none' => esc_html__( 'None', 'category-posts' ),
																			'dark' => esc_html__( 'Darker', 'category-posts' ),
																			'white' => esc_html__( 'Brighter', 'category-posts' ),
																			'scale' => esc_html__( 'Zoom in', 'category-posts' ),
																			'blur' => esc_html__( 'Blur', 'category-posts' ),
																			'productZoom' => esc_html__( 'Product zoom + scroll', 'category-posts' ),
																			'icon' => esc_html__( 'Icon', 'category-posts' ),
						), 'none', true);
						echo $this->get_select_block_html( $instance, 'show_post_format', esc_html__( 'Indicate post format','category-posts' ), array(
																			'none' => esc_html__( 'None', 'category-posts' ),
																			'topleft' => esc_html__( 'Top left', 'category-posts' ),
																			'bottomleft' => esc_html__( 'Bottom left', 'category-posts' ),
																			'ceter' => esc_html__( 'Center', 'category-posts' ),
																			'topright' => esc_html__( 'Top right', 'category-posts' ),
																			'bottomright' => esc_html__( 'Bottom right', 'category-posts' ),
																			'nocss' => esc_html__( 'HTML without styling', 'category-posts' ),
						), 'none', true );
						?>
						<p>
							<label style="display:block">
								<?php esc_html_e( 'Default thumbnail: ','category-posts' ); ?>
							</label>
							<input type="hidden" class="default_thumb_id" id="<?php echo esc_attr( $this->get_field_id( 'default_thunmbnail' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'default_thunmbnail' ) ); ?>" value="<?php echo esc_attr( $default_thunmbnail )?>"/>
							<span class="default_thumb_img">
								<?php
								if ( ! $default_thunmbnail ) {
									esc_html_e( 'None', 'category-posts' );
								} else {
									$img = wp_get_attachment_image_src( $default_thunmbnail );
									echo '<img width="60" height="60" src="' . esc_url( $img[0] ) . '" />';
								}
								?>
							</span>
						</p>
						<p>
							<button type="button" class="cwp_default_thumb_select button upload-button">
								<?php esc_html_e( 'Select image', 'category-posts' )?>
							</button>
							<button type="button" class="cwp_default_thumb_remove button upload-button" <?php if ( ! $default_thunmbnail ) { echo 'style="display:none"'; } ?> >
								<?php esc_html_e( 'No default', 'category-posts' )?>
							</button>
						</p>
					</div>
				</div>

			</div>
			<h4 data-panel="general"><?php esc_html_e( 'General', 'category-posts' )?></h4>
			<div>
				<div class="cpwp_ident">
					<?php echo $this->get_checkbox_block_html( $instance, 'disable_css', esc_html__( 'Disable the built-in CSS','category-posts' ), false, true );?>
					<?php echo $this->get_checkbox_block_html( $instance, 'disable_font_styles', esc_html__( 'Disable only font styles','category-posts' ), false, true );?>
				</div>
				<?php echo $this->get_checkbox_block_html( $instance, 'hide_if_empty', esc_html__( 'Hide if there are no matching posts','category-posts' ), false, true );?>
			</div>
			<h4 data-panel="footer"><?php esc_html_e( 'Footer', 'category-posts' )?></h4>
			<div>
				<?php echo $this->get_text_input_block_html( $instance, 'footer_link_text',  esc_html__( 'Footer link text','category-posts' ), '', '', true );?>
				<?php echo $this->get_text_input_block_html( $instance, 'footer_link',  esc_html__( 'Footer link URL','category-posts' ), '', '', true );?>
			</div>
			<p><a href="<?php echo esc_url( get_edit_user_link() ) . '#' . __NAMESPACE__ ?>"><?php esc_html_e( 'Widget admin behaviour settings', 'category-posts' )?></a></p>
			<p><a target="_blank" href="<?php echo esc_url( DOC_URL ) ?>"><?php esc_html_e( 'Documentation', 'category-posts' ); ?></a></p>
			<p><a target="_blank" href="<?php echo esc_url( SUPPORT_URL ) ?>"><?php esc_html_e( 'Support', 'category-posts' ); ?></a></p>
			<p><?php echo sprintf( wp_kses( __( 'We are on <a href="%1$s">Facebook</a> and <a href="%2$s">Twitter</a>.', 'category-posts' ), array( 'a' => array( 'href' => array() ) ) ), esc_url( 'https://www.facebook.com/TipTopPress' ), esc_url( 'https://twitter.com/TipTopPress' ) ); ?></br></br></p>
		</div>
		<?php
	}
}

/*
 * Plugin action links section
 */

/**
 *  Applied to the list of links to display on the plugins page (beside the activate/deactivate links).
 *
 *  @return array of the widget links
 *
 *  @since 4.6.3
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), __NAMESPACE__ . '\add_action_links' );

/**
 * Handle the add links filter, add our links to the links displayed under the plugin_basename
 * in the plugin admin screen.
 *
 * @param array $links The current links about to be displayed.
 */
function add_action_links( $links ) {
	$pro_link = array(
		'<a target="_blank" href="' . esc_url( PRO_URL ) . '">' . __( 'Get the Pro version', 'category-posts' ) . '</a>',
	);

	$links = array_merge( $pro_link, $links );

	return $links;
}

/**
 * Register the widget.
 */
function register_widget() {
	return \register_widget( __NAMESPACE__ . '\Widget' );
}

add_action( 'widgets_init', __NAMESPACE__ . '\register_widget' );

/**
 *	Output initialization Javascript code for Styles
 *
 * @param int   $number The widget number used to identify the specific list.
 * @param array $widgetsettings The "instance" parameters of the widget.
 *
 * @return void
 *
 * @since 4.8
 */
function footer_script( $number, $widgetsettings ) {
	// set prodct zoom configurations.
	if ( isset( $widgetsettings['thumb_hover'] ) && ( 'productZoom' === $widgetsettings['thumb_hover'] ) ) {
		?>
		<script type="text/javascript">
			jQuery( document ).ready(function () {
				jQuery('.cat-post-item span').elevateZoom({
					zoomType:"inner"
				});
			});
		</script>
		<?php
	}
}

/**
 * Output js code to handle responsive thumbnails
 *
 * @return void
 *
 * @since 4.7
 */
function change_cropped_image_dimensions() {
	?>
	<script type="text/javascript">

		if (typeof jQuery !== 'undefined')  {

			jQuery( document ).ready(function () {

<?php			// namespace. ?>
				var cwp_namespace = window.cwp_namespace || {};
				cwp_namespace.fluid_images = cwp_namespace.fluid_images || {};

				cwp_namespace.fluid_images = {

<?php 				/* variables */ ?>
					Widgets : {},
					widget : null,

<?php				// class. ?>
					Span : function (_self, _imageRatio) {

<?php 					// variables. ?>
						this.self = _self;
						this.imageRatio = _imageRatio;
					},

<?php				// class. ?>
					WidgetPosts : function (widget, ratio) {

<?php 					// variables. ?>
						this.Spans = {};
						this.allSpans = widget.find( '.cat-post-crop' );
						this.firstSpan = this.allSpans.first();
						this.maxSpanWidth = this.firstSpan.width();
						this.firstListItem = this.firstSpan.closest( 'li' );
						this.ratio = ratio;

						for( var i = 0; i < this.allSpans.length; i++ ){
							var imageRatio = this.firstSpan.width() / jQuery(this.allSpans[i]).find( 'img' ).height();
							this.Spans[i] = new cwp_namespace.fluid_images.Span( jQuery(this.allSpans[i]), imageRatio );
						}

<?php 					/* functions */ ?>
						this.changeImageSize = function changeImageSize() {

							this.listItemWidth = this.firstListItem.width();
							this.SpanWidth = this.firstSpan.width();

							if(this.listItemWidth < this.SpanWidth ||	<?php /* if the layout-width have not enough space to show the regular source-width */ echo "\r\n" ?>
								this.listItemWidth < this.maxSpanWidth) {				<?php /* defined start and stop working width for the image: Accomplish only the image width will be get smaller as the source-width */ echo "\r\n" ?>
									this.allSpans.width( this.listItemWidth );
									var spanHeight = this.listItemWidth / this.ratio;
									this.allSpans.height( spanHeight );

									for( var index in this.Spans ){
										var imageHeight = this.listItemWidth / this.Spans[index].imageRatio;
										jQuery(this.Spans[index].self).find( 'img' ).css({
											height: imageHeight,
											marginTop: -(imageHeight - spanHeight) / 2
										});
									};
							}
						}
					},
				}

				<?php
				/**
				 *  The cpw_crop_widgets is an internal filter that is used
				 *  to gather the ids of the widgets to which apply cropping
				 *
				 *  For easier prevention of duplication, the widget id number should be an index
				 *  in the array while the ratio of width/height be the value
				 */
				$widgets_ids = apply_filters( 'cpw_crop_widgets', array() );
				foreach ( $widgets_ids as $number => $ratio ) {
				?>
				cwp_namespace.fluid_images.widget = jQuery('#<?php echo $number?>');
				cwp_namespace.fluid_images.Widgets['<?php echo $number?>'] = new cwp_namespace.fluid_images.WidgetPosts(cwp_namespace.fluid_images.widget,<?php echo $ratio?>);
<?php			} ?>

<?php 			/* do on page load or on resize the browser window */ echo "\r\n" ?>
				jQuery(window).on('load resize', function() {
					for (var widget in cwp_namespace.fluid_images.Widgets) {
						cwp_namespace.fluid_images.Widgets[widget].changeImageSize();
					}
				});
			});
		}
	</script>
	<?php
}

/*
 * shortcode section.
 */

/**
 *  Get shortcode settings taking into account if it is being customized
 *
 *  When not customized returns the settings as stored in the meta, but when
 *  it is customized returns the setting stored in the virtual option used by the customizer
 *
 *  @param string $name The name of the shortcode to retun, empty string indicates the nameless.
 *
 *  @return array the shortcode settings if a short code exists or empty string, empty array if name not found
 *
 *  @since 4.6
 */
function shortcode_settings( $name ) {
	$meta = get_post_meta( get_the_ID(), SHORTCODE_META, true );

	if ( ! empty( $meta ) && ! is_array( reset( $meta ) ) ) {
		$meta = array( '' => $meta );  // the coversion.
	}

	if ( ! isset( $meta[ $name ] ) ) { // name do not exists? return empty array.
		return array();
	}

	$instance = $meta[ $name ];
	if ( is_customize_preview() ) {
		$o = get_option( '_virtual-' . WIDGET_BASE_ID );
		if ( is_array( $o ) ) {
			$instance = $o[ get_the_ID() ][ $name ];
		}
	}

	return $instance;
}

/**
 *  Handle the shortcode
 *
 *  @param array  $attr Array of the attributes to the short code, none is expected.
 *  @param string $content The content enclosed in the shortcode, none is expected.
 *
 *  @return string An HTML of the "widget" based on its settings, actual or customized
 */
function shortcode( $attr, $content = null ) {
	$repository = new virtualWidgetsRepository;

	$shortcodes = $repository->getShortcodes();

	$name = '';
	if ( isset( $attr['name'] ) ) {
		$name = $attr['name'];
	}

	if ( is_singular() ) {
		if ( isset( $shortcodes[ $name ] ) ) {
			return $shortcodes[ $name ]->getHTML();
		}
	}

	return '';
}

add_shortcode( SHORTCODE_NAME, __NAMESPACE__ . '\shortcode' );

/**
 *  Find if a specific shortcode is used in a content
 *
 *  @param string $shortcode_name The name of the shortcode.
 *  @param string $content The content to look at.
 *
 *  @return array An array containing the name attributes of the shortcodes. Empty array is
 *                an indication there were no shourcodes
 *
 *  @since 4.7
 */
function shortcode_names( $shortcode_name, $content ) {

	$names = array();

	$regex_pattern = get_shortcode_regex();
	if ( preg_match_all( '/' . $regex_pattern . '/s', $content, $matches ) ) {
		foreach ( $matches[2] as $k => $shortcode ) {
			if ( SHORTCODE_NAME === $shortcode ) {
				$name = '';
				$atts = shortcode_parse_atts( $matches[3][ $k ] );
				if ( ! empty( $atts['name'] ) ) {
					$name = $atts['name'];
				}
				$names[] = $name;
			}
		}
	}

	return $names;
}

/**
 *  Organized way to have the default widget settings accessible
 *
 *  @since 4.6
 */
function default_settings() {
	return array(
				'title'                           => __( 'Recent Posts', 'category-posts' ),
				'title_link'                      => false,
				'title_link_url'                  => '',
				'hide_title'                      => false,
				'cat'                             => 0,
				'num'                             => get_option( 'posts_per_page' ),
				'offset'                          => 1,
				'sort_by'                         => 'date',
				'status'                          => 'publish',
				'asc_sort_order'                  => false,
				'exclude_current_post'            => false,
				'hideNoThumb'                     => false,
				'footer_link_text'                => '',
				'footer_link'                     => '',
				'thumb'                           => false,
				'thumb_w'                         => get_option( 'thumbnail_size_w', 150 ),
				'thumb_h'                         => get_option( 'thumbnail_size_h', 150 ),
				'use_css_cropping'                => true,
				'thumb_hover'                     => 'none',
				'hide_post_titles'                => false,
				'excerpt'                         => false,
				'excerpt_length'                  => 55,
				'excerpt_more_text'               => __( '...', 'category-posts' ),
				'excerpt_filters'	              => false,
				'comment_num'                     => false,
				'author'                          => false,
				'date'                            => false,
				'date_link'                       => false,
				'date_format'                     => '',
				'assigned_categories'             => false,
				'assigned_cat_top'                => false,
				'assigned_tags'                   => false,
				'disable_css'                     => false,
				'disable_font_styles'             => false,
				'hide_if_empty'                   => false,
				'show_post_format'                => 'none',
				'hide_social_buttons'             => '',
				'no_cat_childs'                   => false,
				'everything_is_link'			  => false,
				'preset_date_format'              => 'sitedateandtime',
				'template'                   	  => "%title%\n%thumb%",
				);
}

/**
 *  Manipulate the relevant meta related to the short code when a post is save
 *
 *  If A post has a short code, a meta holder is created, If it does not the meta holder is deleted
 *
 *  @param integer $pid  The post ID of the post being saved.
 *  @param WP_Post $post The post being saved.
 *  @return void
 *
 *  @since 4.6
 */
function save_post( $pid, $post ) {

	// ignore revisions and auto saves.
	if ( wp_is_post_revision( $pid ) || wp_is_post_autosave( $pid ) ) {
		return;
	}

	$meta = get_post_meta( $pid, SHORTCODE_META, true );
	if ( empty( $meta ) ) {
		$meta = array();
	}

	// check if only one shortcode format - non array of arrays, and convert it.
	if ( ! empty( $meta ) && ! is_array( reset( $meta ) ) ) {
		$meta = array( '' => $meta );  // the conversion.
	}

	$old_names = array_keys( $meta ); // keep list of current shortcode names to delete lter whatever was deleted.
	$names = shortcode_names( SHORTCODE_NAME, $post->post_content );

	// remove setting for unused names.
	$to_delete = array_diff( $old_names, $names );
	foreach ( $to_delete as $k ) {
		unset( $meta[ $k ] );
	}

	foreach ( $names as $name ) {
		if ( ! isset( $meta[ $name ] ) ) {
			$meta[ $name ] = default_settings();
		}
	}

	delete_post_meta( $pid, SHORTCODE_META );
	if ( ! empty( $meta ) ) {
		add_post_meta( $pid, SHORTCODE_META, $meta, true );
	}
}

add_action( 'save_post', __NAMESPACE__ . '\save_post', 10, 2 );

/**
 * Called on Customizer init to do related registrations.
 *
 * @param mixed $wp_customize The customizer object.
 */
function customize_register( $wp_customize ) {

	class shortCodeControl extends \WP_Customize_Control {
		public $form;
		public $title_postfix;

		public function render_content() {
			$widget_title = 'Category Posts Shortcode' . $this->title_postfix;
			?>
			<div class="widget-top">
			<div class="widget-title"><h3><?php echo $widget_title; ?><span class="in-widget-title"></span></h3></div>
			</div>
			<div class="widget-inside" style="display: block;">
				<div class="form">
					<div class="widget-content">
						<?php echo $this->form;	?>
					</div>
				</div>
			</div>
			<?php
		}
	}

	$args = array(
		'post_type' => 'any',
		'post_status' => 'any',
		'posts_per_page' => -1,
		'update_post_term_cache' => false,
		'meta_query' => array(
					array(
					 'key' => SHORTCODE_META,
					 'compare' => 'EXISTS',
				 ),
		),

	);
	$posts = get_posts( $args );

	if ( count( $posts ) > 0 ) {
		$wp_customize->add_panel( __NAMESPACE__, array(
			'title'           => __( 'Category Posts Shortcode', 'category-posts' ),
			'priority'        => 300,
			'capability' => 'edit_theme_options',
		) );

		foreach ( $posts as $p ) {
			$widget = new Widget();
			$meta = get_post_meta( $p->ID, SHORTCODE_META, true );
			if ( ! is_array( $meta ) ) {
				continue;
			}

			if ( ! is_array( reset( $meta ) ) ) { // 4.6 format.
				$meta = array( '' => $meta );
			}

			foreach ( $meta as $k => $m ) {
				$m = wp_parse_args( $m, default_settings() );

				if ( 0 === count( $meta ) ) { // new widget, use defaults.
					;
				} else { // updated widgets come from =< 4.6 excerpt filter is on.
					if ( ! isset( $m['excerpt_filters'] ) ) {
						$m['excerpt_filters'] = 'on';
					}
				}

				$section_title = $k;
				if ( '' === $section_title ) {
					$section_title = __( '[shortcode]', 'category-posts' );
				}

				$wp_customize->add_section( __NAMESPACE__ . '-' . $p->id . '-' . $k, array(
					'title'           => $section_title,
					'priority'        => 10,
					'capability' => 'edit_theme_options',
					'panel' => __NAMESPACE__,
				) );

				ob_start();
				$widget->number = $p->ID . '_' . $k;
				$widget->form( $m );
				$form = ob_get_clean();
				$form = preg_replace_callback('/<(input|select|textarea)\s+.*name=("|\').*\[\w*\]\[([^\]]*)\][^>]*>/',
					function ( $matches ) use ( $p, $wp_customize, $m, $k ) {
						$setting = '_virtual-' . WIDGET_BASE_ID . '[' . $p->ID . '][' . $k . '][' . $matches[3] . ']';
						if ( ! isset( $m[ $matches[3] ] ) ) {
							$m[ $matches[3] ] = null;
						}
						$wp_customize->add_setting( $setting, array(
							'default' => $m[ $matches[3] ], // set default to current value.
							'type' => 'option',
						) );

						return str_replace( '<' . $matches[1], '<' . $matches[1] . ' data-customize-setting-link="' . $setting . '"' , $matches[0] );
					},
					$form
				);

				$args = array(
						'label'   => __( 'Layout', 'twentyfourteen' ),
						'section' => __NAMESPACE__ . '-' . $p->id . '-' . $k,
						'form' => $form,
						'settings' => '_virtual-' . WIDGET_BASE_ID . '[' . $p->ID . '][' . $k . '][title]',
						'active_callback' => function () use ( $p ) { return is_singular() && ( get_the_ID() === $p->ID); },
						);

				if ( get_option( 'page_on_front' ) === $p->ID ) {
					$args['active_callback'] = function () {
						return is_front_page();
					};
				}

				$sc = new shortCodeControl(
					$wp_customize,
					'_virtual-' . WIDGET_BASE_ID . '[' . $p->ID . '][' . $k . '][title]',
					$args
				);

				if ( '' !== $k ) {
					$sc->title_postfix = ' ' . $k;
				}
				$wp_customize->add_control( $sc );
			}
		}
	}
}

add_action( 'customize_register', __NAMESPACE__ . '\customize_register' );

/**
 *  Save the virtual option used by the customizer into the proper meta values.
 *
 *  The customizer actually saves only the changed values, so a merge needs to be done.
 *  After everything is updated the virtual option is deleted to leave a clean slate
 *
 *  @return void
 *
 *  @since 4.6
 */
function customize_save_after() {
	$virtual = get_option( '_virtual-' . WIDGET_BASE_ID );

	if ( is_array( $virtual ) ) {
		foreach ( $virtual as $pid => $instance ) {
			$meta = get_post_meta( $pid, SHORTCODE_META, true );
			if ( ! empty( $meta ) && ! is_array( reset( $meta ) ) ) {
				$meta = array( '' => $meta );  // the conversion.
			}

			foreach ( $instance as $name => $new ) {
				if ( isset( $meta[ $name ] ) ) { // unlikely but maybe that short code was deleted by other session.
					$meta[ $name ] = array_merge( $meta[ $name ], $new );
				}
			}
		}
		update_post_meta( $pid, SHORTCODE_META, $meta );
	}

	delete_option( '_virtual-' . WIDGET_BASE_ID );
}

add_action( 'customize_save_after', __NAMESPACE__ . '\customize_save_after', 100 );

/*
 * tinymce related functions.
 */

/**
 *  Uninstall handler, cleanup DB from options and meta
 *
 *  @return void
 *
 *  @since 4.7
 */
function uninstall() {
	delete_option( 'widget-' . WIDGET_BASE_ID ); // delete the option storing the widget options.
	delete_post_meta_by_key( SHORTCODE_META ); // delete the meta storing the shortcode.
	delete_metadata( 'user', 0, __NAMESPACE__, '', true );  // delete all user metadata.
}

register_uninstall_hook( __FILE__, __NAMESPACE__ . 'uninstall' );

/**
 *  Register the tinymce shortcode plugin
 *
 *  @param array $plugin_array An array containing the current plugins to be used by tinymce.
 *
 *  @return array An array containing the plugins to be used by tinymce, our plugin added to the $plugin_array parameter
 *
 *  @since 4.7
 */
function mce_external_plugins( $plugin_array ) {
	if ( current_user_can( 'edit_theme_options' ) ) { // don't load the code if the user can not customize the shortcode.
		// enqueue TinyMCE plugin script with its ID.
		$meta = get_user_meta( get_current_user_id(), __NAMESPACE__ , true );
		if ( is_array( $meta ) && isset( $meta['editor'] ) ) {
			;
		} else {
			$plugin_array[ __NAMESPACE__ ] = plugins_url( 'js/admin/tinymce.js?ver=' . VERSION, __FILE__ );
		}
	}

	return $plugin_array;
}

add_filter( 'mce_external_plugins', __NAMESPACE__ . '\mce_external_plugins' );

/**
 *  Register the tinymce buttons for the add shortcode
 *
 *  @param array $buttons An array containing the current buttons to be used by tinymce.
 *
 *  @return array An array containing the buttons to be used by tinymce, our button added to the $buttons parameter
 *
 *  @since 4.7
 */
function mce_buttons( $buttons ) {
	if ( current_user_can( 'edit_theme_options' ) ) { // don't load the code if the user can not customize the shortcode
		// register buttons with their id.
		$meta = get_user_meta( get_current_user_id(), __NAMESPACE__, true );
		if ( is_array( $meta ) && isset( $meta['editor'] ) ) {
			;
		} else {
			array_push( $buttons, __NAMESPACE__ );
		}
	}

	return $buttons;
}

add_filter( 'mce_buttons', __NAMESPACE__ . '\mce_buttons' );

/**
 *  Register the tinymcetranslation file
 *
 *  @param array $locales An array containing the current translations to be used by tinymce.
 *
 *  @return array An array containing the translations to be used by tinymce, our localization added to the $locale parameter
 *
 *  @since 4.7
 */
function mce_external_languages( $locales ) {
	if ( current_user_can( 'edit_theme_options' ) ) { // don't load the code if the user can not customize the shortcode.
		$meta = get_user_meta( get_current_user_id(), __NAMESPACE__, true );
		if ( is_array( $meta ) && isset( $meta['editor'] ) ) {
			;
		} else {
			$locales['cat-posts'] = plugin_dir_path( __FILE__ ) . 'tinymce_translations.php';
		}
	}

	return $locales;
}

add_filter( 'mce_external_languages', __NAMESPACE__ . '\mce_external_languages' );

/*
 * user profile related functions.
 */

add_action( 'show_user_profile', __NAMESPACE__ . '\show_user_profile' );
add_action( 'edit_user_profile', __NAMESPACE__ . '\show_user_profile' );

function show_user_profile( $user ) {

	if ( ! current_user_can( 'edit_user', $user->ID ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_theme_options', $user->ID ) ) {
		return;
	}

	$meta = get_the_author_meta( __NAMESPACE__, $user->ID );

	if ( empty( $meta ) ) {
		$meta = array();
	}

	$accordion = false;
	if ( isset( $meta['panels'] ) ) {
		$accordion = true;
	}

	$editor = false;
	if ( isset( $meta['editor'] ) ) {
		$editor = true;
	}
?>
	<h3 id="<?php echo __NAMESPACE__ ?>"><?php esc_html_e( 'Category Posts Widget behaviour settings', 'category-posts' )?></h3>

	<table class="form-table">
		<tr>
			<th><label for="<?php echo __NAMESPACE__?>[panels]"><?php esc_html_e( 'Open panels behavior', 'category-posts' )?></label></th>
			<td>
				<input type="checkbox" name="<?php echo __NAMESPACE__?>[panels]" id="<?php echo __NAMESPACE__?>[panels]" <?php checked( $accordion ); ?>>
				<label for=<?php echo __NAMESPACE__?>[panels]><?php esc_html_e( 'Close the curremtly open panel when opening a new one', 'category-posts' )?></label>
			</td>
		</tr>
		<tr>
			<th><label for="<?php echo __NAMESPACE__?>[editor]"><?php esc_html_e( 'Visual editor button', 'category-posts' )?></label></th>
			<td>
				<input type="checkbox" name="<?php echo __NAMESPACE__?>[editor]" id="<?php echo __NAMESPACE__?>[editor]" <?php checked( $editor ); ?>>
				<label for="<?php echo __NAMESPACE__?>[editor]"><?php esc_html_e( 'Hide the "insert shortcode" button from the editor', 'category-posts' )?></label>
			</td>
		</tr>
	</table>
<?php
}

add_action( 'personal_options_update', __NAMESPACE__ . '\personal_options_update' );
add_action( 'edit_user_profile_update', __NAMESPACE__ . '\personal_options_update' );

function personal_options_update( $user_id ) {

	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	if ( ! current_user_can( 'edit_theme_options', $user_id ) ) {
		return;
	}

	if ( isset( $_POST[ __NAMESPACE__ ] ) ) {
		update_user_meta( $user_id, __NAMESPACE__, wp_unslash( $_POST[ __NAMESPACE__ ] ) );
	} else {
		delete_user_meta( $user_id, __NAMESPACE__ );
	}
}

/*
 * external API.
 */

/**
 *  Class that represent a virtual widget. Each widget being created will have relevant
 *  CSS output in the header, but strill requires a call for getHTML method or renderHTML
 *  to get or output the HTML
 *
 *  @since 4.7
 */
class virtualWidget {
	private static $collection = array();
	private $id;
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
	function __construct( $id, $class, $args ) {
		$this->id = $id;
		$this->class = $class;
		self::$collection[ $id ] = wp_parse_args( $args, default_settings() );
	}

	function __destruct() {
		 unset( self::$collection[ $this->id ] );
	}

	/**
	 *  Return the HTML of the widget as is generated based on the settings passed at construction time
	 *
	 *  @return string
	 *
	 *  @since 4.7
	 */
	function getHTML() {

		$widget = new Widget();
		$widget->number = $this->id; // needed to make a unique id for the widget html element.
		ob_start();
		$args = self::$collection[ $this->id ];
		$args['is_shortcode'] = true;  // indicate that we are doing shortcode processing to outputting funtions.
		$widget->widget(array(
							'before_widget' => '',
							'after_widget' => '',
							'before_title' => '',
							'after_title' => '',
		), $args);
		$ret = ob_get_clean();
		$ret = '<div id="' . esc_attr( $this->id ) . '" class="' . esc_attr( $this->class ) . '">' . $ret . '</div>';
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
	function renderHTML() {
		echo $this->getHTML();
	}

	/**
	 *  Calculate the CSS rules required for the widget as is generated based on the settings passed at construction time
	 *
	 *  @param bool  $is_shortcode Indicated if rules are generated for a shortcode.
	 *  @param array $ret "returned" Collection of CSS rules.
	 *
	 *  @since 4.7
	 */
	function getCSSRules( $is_shortcode, &$ret ) {
		$settings = self::$collection[ $this->id ];
		$widget_id = $this->id;
		if ( ! $is_shortcode ) {
			$widget_id .= '-internal';
		}
		$disable_css        = isset( $settings['disable_css'] ) && $settings['disable_css'];

		if ( ! $disable_css ) { // checks if css disable is not set.

			$rules = array( // rules that should be applied to all widgets.
				'.cat-post-item img {max-width: initial; max-height: initial; margin: initial;}',
				'.cat-post-author {margin-bottom: 0;}',
				'.cat-post-thumbnail {margin: 5px 10px 5px 0; display: table;}',
				'.cat-post-item:before {content: ""; clear: both;}',
			);

			if ( ! ( isset( $settings['disable_font_styles'] ) && $settings['disable_font_styles'] ) ) { // checks if disable font styles is not set.
				// add general rules which apply to font styling.
				$rules[] = '.cat-post-title {font-size: 15px;}';
				$rules[] = '.cat-post-current .cat-post-title {font-weight: bold; text-transform: uppercase;}';
				$rules[] = '.cat-post-date {font-size: 14px; line-height: 18px; font-style: italic; margin-bottom: 5px;}';
				$rules[] = '.cat-post-comment-num {font-size: 14px; line-height: 18px;}';
			}

			/*
			 *	The twenty seventeen theme have a border between the LI elements of a widget,
			 *	so remove our border if we detect its use to avoid conflicting styling.
			 */
			if ( ! $is_shortcode && function_exists( 'twentyseventeen_setup' ) ) {
				$rules[] = '.cat-post-item {list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
			} else {
				$rules[] = '.cat-post-item {border-bottom: 1px solid #ccc;	list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
				$rules[] = '.cat-post-item:last-child {border-bottom: none;}';
			}

			// everything link related styling
		    // if we are dealing with "everything is a link" option, we need to add the clear:both to the a element, not the div.
			if ( isset( $settings['everything_is_link'] ) && $settings['everything_is_link'] ) {
				$rules[] = '.cat-post-everything-is-link { }';
				$rules[] = '.cat-post-item a:after {content: ""; display: table;	clear: both;}';
			} else {
				$rules[] = '.cat-post-item:after {content: ""; display: table;	clear: both;}';
			}

			// add post format css if needed.
			if ( isset( $settings['template'] ) && preg_match( '/%thumb%/', $settings['template'] ) ) {
				if ( ! isset( $settings['show_post_format'] ) || ( ( 'none' !== $settings['show_post_format'] ) && ( 'nocss' !== $settings['show_post_format'] ) ) ) {
					static $fonts_added = false;
					if ( ! $fonts_added ) {
						$fonturl = esc_url( plugins_url( 'icons/font', __FILE__ ) );
						$ret[] = "@font-face {\n" .
								 "font-family: 'cat_post';\n" .
								 "src: url('$fonturl/cat_post.eot?4618166');\n" .
								 "src: url('$fonturl/cat_post.eot?4618166#iefix') format('embedded-opentype'),\n" .
								 "	   url('$fonturl/cat_post.woff2?4618166') format('woff2'),\n" .
								 "	   url('$fonturl/cat_post.woff?4618166') format('woff'),\n" .
								 "	   url('$fonturl/cat_post.ttf?4618166') format('truetype');\n" .
								 " font-weight: normal;\n" .
								 " font-style: normal;\n" .
								 "}\n";
					}
					$fonts_added = true;

					$placement = '';
					switch ( $settings['show_post_format'] ) {
						case 'topleft': $placement = 'top:10%; left:10%;';
							break;
						case 'bottomleft': $placement = 'bottom:10%; left:10%;';
							break;
						case 'ceter': $placement = 'top:calc(50% - 25px); left:calc(50% - 25px);';
							break;
						case 'topright': $placement = 'top:10%; right:10%;';
							break;
						case 'bottomright': $placement = 'bottom:10%; right:10%;';
							break;
					}
					$rules[] = '.cat-post-thumbnail {position:relative}';
					$rules[] = '.cat-post-format:before {font-family: "cat_post"; position:absolute; color:white; border:1px solid rgba(255,255,255,.8); ' .
								'font-size:14px; line-height:14px; padding:15px; border-radius:4px; background-color:rgba(0,0,0,.6); ' .
								$placement . '}';

					$rules[] = ".cat-post-format-image:before { content: '\\e800'; }";
					$rules[] = ".cat-post-format-video:before { content: '\\e801'; }";
					$rules[] = ".cat-post-format-chat:before { content: '\\e802'; }";
					$rules[] = ".cat-post-format-audio:before { content: '\\e803'; }";
					$rules[] = ".cat-post-format-gallery:before { content: '\\e805'; }";
				}
			}

			foreach ( $rules as $rule ) {
				$ret[] = '#' . $widget_id . ' ' . $rule;
			}

			if ( $is_shortcode ) {
				// Twenty Sixteen Theme adds underlines to links with box whadow wtf ...
				$ret[] = '#' . $widget_id . ' .cat-post-thumbnail {box-shadow:none}'; // this for the thumb link.
				if ( ! ( isset( $settings['disable_font_styles'] ) && $settings['disable_font_styles'] ) ) { // checks if disable font styles is not set.
					$ret[] = '#' . $widget_id . ' .cat-post-tax-post_tag a {box-shadow:none}';     // this for the tag link.
					$ret[] = '#' . $widget_id . ' .cat-post-tax-post_tag span {box-shadow:none}';     // this for the tag link.
				}
				// Twenty Fifteen Theme adds border ...
				$ret[] = '#' . $widget_id . ' .cat-post-thumbnail {border:0}'; // this for the thumb link.
				if ( ! ( isset( $settings['disable_font_styles'] ) && $settings['disable_font_styles'] ) ) { // checks if disable font styles is not set.
					$ret[] = '#' . $widget_id . ' .cat-post-tax-post_tag a {border:0}';     // this for the tag link.
					$ret[] = '#' . $widget_id . ' .cat-post-tax-post_tag span {border:0}';     // this for the tag link.
				}
				// probably all Themes have too much margin on their p element when used in the shortcode
				$ret[] = '#' . $widget_id . ' p {margin:5px 0 0 0}';	// since on bottom it will make the spacing on cover
																	   // bigger (add to the padding) use only top for now.
			}
		}

		if ( ( isset( $settings['use_css_cropping'] ) && $settings['use_css_cropping'] ) || ! $disable_css ) {
			if ( isset( $settings['use_css_cropping'] ) && $settings['use_css_cropping'] ) {
				$ret[] = '#' . $widget_id . ' .cat-post-crop {overflow: hidden; display:block}';
			} else {
				$ret[] = '#' . $widget_id . ' .cat-post-thumbnail span {overflow: hidden; display:block}';
			}
			$ret[] = '#' . $widget_id . ' .cat-post-item img {margin: initial;}';
		}

		if ( ! $disable_css ) { // backward compatibility to =< 4.7.
			if ( isset( $settings['thumb_hover'] ) ) {
				switch ( $settings['thumb_hover'] ) {
					case 'white':
						$ret[] = '#' . $widget_id . ' .cat-post-white {background-color: white;}';
						$ret[] = '#' . $widget_id . ' .cat-post-white img {padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
						$ret[] = '#' . $widget_id . ' .cat-post-white:hover img {opacity: 0.8;}';
						break;
					case 'dark':
						$ret[] = '#' . $widget_id . ' .cat-post-dark img {padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
						$ret[] = '#' . $widget_id . ' .cat-post-dark:hover img {-webkit-filter: brightness(75%); -moz-filter: brightness(75%); -ms-filter: brightness(75%); -o-filter: brightness(75%); filter: brightness(75%);}';
						break;
					case 'scale':
						$ret[] = '#' . $widget_id . ' .cat-post-scale img {margin: initial; padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
						$ret[] = '#' . $widget_id . ' .cat-post-scale:hover img {-webkit-transform: scale(1.1, 1.1); -ms-transform: scale(1.1, 1.1); transform: scale(1.1, 1.1);}';
						break;
					case 'blur':
						$ret[] = '#' . $widget_id . ' .cat-post-blur img {padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
						$ret[] = '#' . $widget_id . ' .cat-post-blur:hover img {-webkit-filter: blur(2px); -moz-filter: blur(2px); -o-filter: blur(2px); -ms-filter: blur(2px); filter: blur(2px);}';
						break;
					case 'icon':
						$fonturl = esc_url( plugins_url( 'icons/font', __FILE__ ) );
						$ret[] = "@font-face {\n" .
								 "font-family: 'cat_post';\n" .
								 "src: url('$fonturl/cat_post.eot?4618166');\n" .
								 "src: url('$fonturl/cat_post.eot?4618166#iefix') format('embedded-opentype'),\n" .
								 "	   url('$fonturl/cat_post.woff2?4618166') format('woff2'),\n" .
								 "	   url('$fonturl/cat_post.woff?4618166') format('woff'),\n" .
								 "	   url('$fonturl/cat_post.ttf?4618166') format('truetype');\n" .
								 " font-weight: normal;\n" .
								 " font-style: normal;\n" .
								 "}\n";

						$ret[] = '#' . $widget_id . ' .cat-post-format-standard {opacity:0; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
						$ret[] = '#' . $widget_id . ' .cat-post-thumbnail:hover .cat-post-format-standard {opacity:1;}';

						if ( isset( $settings['show_post_format'] ) && ( 'none' === $settings['show_post_format'] ) ) {
							$ret[] = '#' . $widget_id . ' .cat-post-thumbnail {position:relative}';
							$ret[] = '#' . $widget_id . ' .cat-post-icon .cat-post-format:before {font-family: "cat_post"; position:absolute; color:white; border:1px solid rgba(255,255,255,.8); ' .
										'font-size:14px; line-height:14px; padding:15px; border-radius:4px; background-color:rgba(0,0,0,.6); ' .
										'top:calc(50% - 25px); left:calc(50% - 25px);}';
						}

						$ret[] = '#' . $widget_id . " .cat-post-format-standard:before {padding:15px 16px; content: '\\e806'; }";
						break;
				}
			}
		}
	}

	/**
	 *  Output the widget CSS
	 *
	 *  Just a wrapper that output getCSSRules
	 *
	 *  @since 4.7
	 */
	function outputCSS( $is_shortcode ) {
		$rules = array();
		getCSSRules( $is_shortcode, $rules );
		foreach ( $rules as $rule ) {
			echo "$rule\n";
		}
	}

	/**
	 *  Get the id the virtual widget was registered with
	 *
	 *  @return string
	 *
	 *  @since 4.7
	 */
	function id() {
		return $this->id;
	}

	/**
	 *  Get all the setting of the virtual widgets in an array
	 *
	 *  @return array
	 *
	 *  @since 4.7
	 */
	static function getAllSettings() {
		return self::$collection;
	}

}

/**
 *  Class that implement a simple repository for the virtual widgets representing
 *  actuall shortcode and widgets
 *
 *  @since 4.7
 */
class virtualWidgetsRepository {
	private static $shortcodeCollection = array();
	private static $widgetCollection = array();

	/**
	 *  Add a virtual widget representing a shortcode to the repository
	 *
	 *  @param string        $index  A name to identify the specific shortcode.
	 *  @param virtualWidget $widget The virtual widget for it.
	 *
	 *  @since 4.7
	 */
	function addShortcode( $index, $widget ) {
		self::$shortcodeCollection[ $index ] = $widget;
	}

	/**
	 *  Get all the virtual widgets representing actual shortcodes
	 *
	 *  @return array
	 *
	 *  @since 4.7
	 */
	function getShortcodes() {
		return self::$shortcodeCollection;
	}

	/**
	 *  Add a virtual widget representing awidget to the repository
	 *
	 *  @param string        $index A name to identify the specific widget.
	 *  @param virtualWidget $widget The virstual widget for it.
	 *
	 *  @since 4.7
	 */
	function addWidget( $index, $widget ) {
		self::$widgetCollection[ $index ] = $widget;
	}

	/**
	 *  Get all the virtual widgets representing actual widgets
	 *
	 *  @return array
	 *
	 *  @since 4.7
	 */
	function getWidgets() {
		return self::$widgetCollection;
	}

}

add_action( 'wp_loaded', __NAMESPACE__ . '\wp_loaded' );

/**
 *  Run after wordpress finished bootstrapping, do whatever is needed at this stage
 *  like registering the meta
 */
function wp_loaded() {
	register_meta( 'post', SHORTCODE_META, null, '__return_false' ); // do not allow access to the shortcode meta
																	// use the pre 4.6 format for backward compatibility.
}
