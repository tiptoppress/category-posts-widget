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
Version: 4.9.5
Author URI: http://tiptoppress.com
Text Domain: category-posts
Domain Path: /languages
*/

namespace categoryPosts;

// Don't call the file directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION        = '4.9.5';
const DOC_URL        = 'http://tiptoppress.com/category-posts-widget/documentation-4-9/';
const PRO_URL        = 'http://tiptoppress.com/term-and-category-based-posts-widget/';
const SUPPORT_URL    = 'https://wordpress.org/support/plugin/category-posts/';
const SHORTCODE_NAME = 'catposts';
const SHORTCODE_META = 'categoryPosts-shorcode';
const WIDGET_BASE_ID = 'category-posts';

require_once __DIR__ . '/class-virtual-widget.php';
require_once __DIR__ . '/class-virtual-widgets-repository.php';
require_once __DIR__ . '/class-widget.php';
require_once __DIR__ . '/loadmore.php';
require_once __DIR__ . '/localizeddate.php';

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
		'id'    => 'customize',
		'title' => __( 'Customize' ),
		'href'  => $customize_url,
		'meta'  => array(
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

	$widget_repository = new Virtual_Widgets_Repository();

	$styles = array();

	foreach ( $widget_repository->getShortcodes() as $widget ) {
		$widget->getCSSRules( true, $styles );
	}

	foreach ( $widget_repository->getWidgets() as $widget ) {
		$widget->getCSSRules( false, $styles );
	}

	if ( ! empty( $styles ) ) {
	?>
<style>
	<?php
	foreach ( $styles as $rules ) {
		foreach ( $rules as $rule ) {
			echo "$rule\n"; // Xss ok. raw css output, can not be html escaped.
		}
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

	$repository = new Virtual_Widgets_Repository();

	// check first for shortcode settings.
	if ( is_singular() ) {
		$names = shortcode_names( SHORTCODE_NAME, $post->post_content );

		foreach ( $names as $name ) {
			$meta = shortcode_settings( get_the_ID(), $name );
			if ( is_array( $meta ) ) {
				$id = WIDGET_BASE_ID . '-shortcode-' . get_the_ID(); // needed to make a unique id for the widget html element.
				if ( '' !== $name ) { // if not default name append to the id.
					$id .= '-' . sanitize_title( $name ); // sanitize to be on the safe side, not sure where when and how this will be used.
				}
				$repository->addShortcode( $name, new Virtual_Widget( $id, WIDGET_BASE_ID . '-shortcode', $meta ) );
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
						$repository->addWidget( $widget, new Virtual_Widget( $widget, $widget, $settings ) );
					}
				}
			}
		}
	}
}

add_action( 'wp_head', __NAMESPACE__ . '\wp_head' );

/**
 * Enqueue widget related scripts for the widget front-end
 *
 * @since 4.8
 */
function frontend_script() {
	$suffix = 'min.js';
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
		$suffix = 'js';
	}
	wp_enqueue_script( 'cat-posts-frontend-js', plugins_url( 'js/frontend/category-posts-frontend.' . $suffix, __FILE__ ), array( 'jquery' ), VERSION, true );
}

/**
 * Embed the front end JS in the HTML footer.
 *
 * @since 4.9
 */
function embed_front_end_scripts() {
	$suffix = 'min.js';
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
		$suffix = 'js';
	}
	echo '<script>';
	include __DIR__ . '/js/frontend/category-posts-frontend.' . $suffix;
	echo '</script>';
}

/**
 * Enqueue widget related scripts for the widget admin page and customizer.
 *
 * @param string $hook the name of the admin hook for which the function was triggered.
 */
function admin_scripts( $hook ) {

	if ( 'widgets.php' === $hook || 'post.php' === $hook  ) { // enqueue only for widget admin and customizer. (add if post.php: fix make widget SiteOrigin Page Builder plugin, GH issue #181)

		/*
		 * Add script to control admin UX.
		 */

		// Use unminified version of JS when debuging, and minified when not.
		$suffix = 'min.js';
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			$suffix = 'js';
		}
		wp_register_script( 'category-posts-widget-admin-js', plugins_url( 'js/admin/category-posts-widget.' . $suffix, __FILE__ ), array( 'jquery' ), VERSION, true );
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
			'frame_title'  => __( 'Select a default thumbnail', 'category-posts' ),
			'button_title' => __( 'Select', 'category-posts' ),
			'none'         => __( 'None', 'category-posts' ),
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

/**
 * Add styles for widget admin sections
 *
 * @since 4.1
 **/
function admin_styles() {
	wp_enqueue_style( 'cat-posts-admin-styles', plugins_url( 'styles/admin/category-posts-widget.css', __FILE__ ), array(), VERSION, false );
}

add_action( 'admin_print_styles-widgets.php', __NAMESPACE__ . '\admin_styles' );

// fix make widget SiteOrigin Page Builder plugin, GH issue #181
add_action('siteorigin_panel_enqueue_admin_scripts', __NAMESPACE__ . '\admin_styles' );

/**
 *  Get the tags which might be used in the template.
 *
 *  @since 4.8
 *
 *  @return array Array of strings of the tags.
 */
function get_template_tags() {
	return array( 'author', 'title', 'date', 'thumb', 'excerpt', 'commentnum', 'post_tag', 'category' );
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
 * The convert old data structure to current one.
 *
 * @param array $settings The settings to upgrade.
 *
 * @return array The settings matching current version standard.
 *
 * @since 4.8
 */
function upgrade_settings( $settings ) {

	if ( 0 === count( $settings ) ) {
		return default_settings();
	}

	if ( ! isset( $settings['ver'] ) ) {
		/*
		 * Pre 4.9 version.
		 */

		// Upgrade the hide if empty option.
		if ( isset( $settings['hide_if_empty'] ) && $settings['hide_if_empty'] ) {
			$settings['no_match_handling'] = 'hide';
		} else {
			$settings['no_match_handling'] = 'nothing';
		}
		if ( isset( $settings['hide_if_empty'] ) ) {
			unset( $settings['hide_if_empty'] );
		}
	}

	$settings['ver'] = VERSION;

	// Make sure all "empty" settings have default value.
	$settings = wp_parse_args( $settings, default_settings() );

	return $settings;
}

/**
 * Convert pre 4.8 settings into template
 *
 * @param  array $instance Array which contains the various settings.
 *
 * @since 4.8
 */
function convert_settings_to_template( $instance ) {

	$template = '';

	if ( isset( $instance['thumb'] ) && $instance['thumb'] ) {
		if ( isset( $instance['thumbTop'] ) && $instance['thumbTop'] ) {
			$template .= "%thumb%\n\n";
			if ( ! ( isset( $instance['hide_post_titles'] ) && $instance['hide_post_titles'] ) ) {
				$template .= "%title%\n";
			}
		} elseif ( isset( $instance['date'] ) && $instance['date'] ) {
			if ( ! ( isset( $instance['hide_post_titles'] ) && $instance['hide_post_titles'] ) ) {
				$template .= "%title%\n\n";
			}
			$template .= "%date%\n\n";
			$template .= "%thumb%\n";
		} elseif ( ! ( isset( $instance['hide_post_titles'] ) && $instance['hide_post_titles'] ) ) {
			$template .= "%thumb%\n%title%\n";
		}
	} else {
		if ( ! ( isset( $instance['hide_post_titles'] ) && $instance['hide_post_titles'] ) ) {
			$template .= "%title%\n";
		}
		if ( isset( $instance['date'] ) && $instance['date'] ) {
			$template .= "%date%\n\n";
		}
	}
	if ( isset( $instance['excerpt'] ) && $instance['excerpt'] ) {
		$template .= '%excerpt%';
	}
	if ( isset( $instance['comment_num'] ) && $instance['comment_num'] ) {
		$template .= "%commentnum%\n\n";
	}
	if ( isset( $instance['author'] ) && $instance['author'] ) {
		$template .= "%author%\n\n";
	}

	return $template;
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

	if ( ! class_exists( '\\termcategoryPostsPro\\Widget' ) ) {
		$pro_link = array(
			'<a target="_blank" href="' . esc_url( PRO_URL ) . '">' . esc_html__( 'Get the Pro version', 'category-posts' ) . '</a>',
		);

		$links = array_merge( $pro_link, $links );
	}

	return $links;
}

/**
 * Register the widget.
 */
function register_widget() {
	return \register_widget( __NAMESPACE__ . '\Widget' );
}

add_action( 'widgets_init', __NAMESPACE__ . '\register_widget' );

/*
 * shortcode section.
 */

/**
 * Get shortcode settings taking into account if it is being customized
 *
 * When not customized returns the settings as stored in the meta, but when
 * it is customized returns the setting stored in the virtual option used by the customizer
 *
 * @param string $pid  The ID of the post in which the shortcode is.
 * @param string $name The name of the shortcode to retun, empty string indicates the nameless.
 *
 * @return array The shortcode settings if a short code name exists or is an empty string,
 *               empty array if name not found.
 *
 * @since 4.6
 */
function shortcode_settings( $pid, $name ) {
	$meta = get_post_meta( $pid, SHORTCODE_META, true );

	if ( ! empty( $meta ) && ! is_array( reset( $meta ) ) ) {
		$meta = array( '' => $meta );  // the conversion.
	}

	if ( ! isset( $meta[ $name ] ) ) { // name do not exists? return empty array.
		return array();
	}

	$instance = $meta[ $name ];
	if ( is_customize_preview() ) {
		$o = get_option( '_virtual-' . WIDGET_BASE_ID );
		if ( is_array( $o ) ) {
			$instance = $o[ $pid ][ $name ];
			$instance['ver'] = VERSION;
		}
	}

	if ( isset( $instance['template'] ) && $instance['template'] ) {
		;
	} else {
		$instance['template'] = convert_settings_to_template( $instance );
	}

	$instance = upgrade_settings( $instance );

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
	$repository = new Virtual_Widgets_Repository();

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
		'title'                  => __( 'Recent Posts', 'category-posts' ),
		'title_link'             => false,
		'title_link_url'         => '',
		'hide_title'             => false,
		'cat'                    => 0,
		'num'                    => get_option( 'posts_per_page' ),
		'offset'                 => 1,
		'sort_by'                => 'date',
		'status'                 => 'publish',
		'asc_sort_order'         => false,
		'exclude_current_post'   => false,
		'hideNoThumb'            => false,
		'footer_link_text'       => '',
		'footer_link'            => '',
		'thumb_w'                => get_option( 'thumbnail_size_w', 150 ),
		'thumb_fluid_width'      => 100,
		'thumb_h'                => get_option( 'thumbnail_size_h', 150 ),
		'use_css_cropping'       => true,
		'thumb_hover'            => 'none',
		'hide_post_titles'       => false,
		'excerpt_lines'          => 0,
		'excerpt_length'         => 0,
		'excerpt_more_text'      => __( '...', 'category-posts' ),
		'excerpt_filters'        => false,
		'comment_num'            => false,
		'date_link'              => false,
		'date_format'            => '',
		'disable_css'            => false,
		'disable_font_styles'    => false,
		'show_post_format'       => 'none',
		'no_cat_childs'          => false,
		'everything_is_link'     => false,
		'preset_date_format'     => 'sitedateandtime',
		'template'               => "%title%\n\n%thumb%",
		'text_do_not_wrap_thumb' => false,
		'enable_loadmore'        => false,
		'loadmore_text'          => __( 'Load More', 'category-posts' ),
		'loading_text'           => __( 'Loading...', 'category-posts' ),
		'date_range'             => 'off',
		'start_date'             => '',
		'end_date'               => '',
		'days_ago'               => 30,
		'no_match_handling'      => 'nothing',
		'no_match_text'          => '',
		'default_thunmbnail'     => 0,
		'ver'                    => VERSION,
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

	require_once __DIR__ . '/class-shortcode-control.php';

	$args = array(
		'post_type'              => 'any',
		'post_status'            => 'any',
		'posts_per_page'         => -1,
		'update_post_term_cache' => false,
		'meta_query'             => array(
			array(
				'key'     => SHORTCODE_META,
				'compare' => 'EXISTS',
			),
		),

	);
	$posts = get_posts( $args );

	if ( count( $posts ) > 0 ) {
		$wp_customize->add_panel( __NAMESPACE__, array(
			'title'      => __( 'Category Posts Shortcode', 'category-posts' ),
			'priority'   => 300,
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

				if ( isset( $m['template'] ) && $m['template'] ) {
					;
				} else {
					$m['template'] = convert_settings_to_template( $m );
				}

				if ( 0 === count( $meta ) ) { // new widget, use defaults.
					;
				} else { // updated widgets come from =< 4.6 excerpt filter is on.
					if ( ! isset( $m['excerpt_filters'] ) ) {
						$m['excerpt_filters'] = 'on';
					}
				}

				$m = upgrade_settings( $m );

				$section_title = $k;
				if ( '' === $section_title ) {
					$section_title = __( '[shortcode]', 'category-posts' );
				}

				$wp_customize->add_section( __NAMESPACE__ . '-' . $p->id . '-' . $k, array(
					'title'      => $section_title,
					'priority'   => 10,
					'capability' => 'edit_theme_options',
					'panel'      => __NAMESPACE__,
				) );

				ob_start();

				// For the form method to handle generation gracefully, the number needs to be a simple string, and the name might include other chars as well, so for simplisity md5 it.
				if ( '' !== $k ) {
					$widget->number = $p->ID . '_' . md5( $k );
				} else {
					$widget->number = $p->ID . '_';
				}

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
							'type'    => 'option',
						) );

						return str_replace( '<' . $matches[1], '<' . $matches[1] . ' data-customize-setting-link="' . $setting . '"', $matches[0] );
					},
					$form
				);

				$args = array(
					'label'           => __( 'Layout', 'twentyfourteen' ),
					'section'         => __NAMESPACE__ . '-' . $p->id . '-' . $k,
					'form'            => $form,
					'settings'        => '_virtual-' . WIDGET_BASE_ID . '[' . $p->ID . '][' . $k . '][title]',
					'active_callback' => function () use ( $p ) {
						return is_singular() && ( get_the_ID() === $p->ID );
					},
				);

				if ( get_option( 'page_on_front' ) === $p->ID ) {
					$args['active_callback'] = function () {
						return is_front_page();
					};
				}

				$sc = new ShortCode_Control(
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
		$meta = get_user_meta( get_current_user_id(), __NAMESPACE__, true );
		if ( is_array( $meta ) && isset( $meta['editor'] ) ) {
			;
		} else {
			$plugin_array[ __NAMESPACE__ ] = plugins_url( 'js/admin/tinymce.min.js?ver=' . VERSION, __FILE__ );
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

/**
 *  Display the user specific setting on its profile page
 *
 *  @param WP_user $user The user for which the profile page displays information.
 *
 *  @since 4.7
 */
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
	<h3 id="<?php echo __NAMESPACE__; ?>"><?php esc_html_e( 'Category Posts Widget behaviour settings', 'category-posts' ); ?></h3>

	<table class="form-table">
		<tr>
			<th><label for="<?php echo __NAMESPACE__; ?>[panels]"><?php esc_html_e( 'Open panels behavior', 'category-posts' ); ?></label></th>
			<td>
				<input type="checkbox" name="<?php echo __NAMESPACE__; ?>[panels]" id="<?php echo __NAMESPACE__; ?>[panels]" <?php checked( $accordion ); ?>>
				<label for=<?php echo __NAMESPACE__; ?>[panels]><?php esc_html_e( 'Close the currently open panel when opening a new one', 'category-posts' ); ?></label>
			</td>
		</tr>
		<tr>
			<th><label for="<?php echo __NAMESPACE__; ?>[editor]"><?php esc_html_e( 'Visual editor button', 'category-posts' ); ?></label></th>
			<td>
				<input type="checkbox" name="<?php echo __NAMESPACE__; ?>[editor]" id="<?php echo __NAMESPACE__; ?>[editor]" <?php checked( $editor ); ?>>
				<label for="<?php echo __NAMESPACE__; ?>[editor]"><?php esc_html_e( 'Hide the "insert shortcode" button from the editor', 'category-posts' ); ?></label>
			</td>
		</tr>
	</table>
<?php
}

add_action( 'personal_options_update', __NAMESPACE__ . '\personal_options_update' );
add_action( 'edit_user_profile_update', __NAMESPACE__ . '\personal_options_update' );

/**
 *  Handles saving user related settings as was set in the profile page.
 *
 *  @param int $user_id the ID of the user for which the data is saved..
 *
 *  @since 4.7
 */
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

add_action( 'wp_loaded', __NAMESPACE__ . '\wp_loaded' );

/**
 *  Run after WordPress finished bootstrapping, do whatever is needed at this stage
 *  like registering the meta.
 */
function wp_loaded() {
	register_meta( 'post', SHORTCODE_META, null, '__return_false' ); // do not allow access to the shortcode meta
																	// use the pre 4.6 format for backward compatibility.
}
