<?php
/**
 * Gutenberg Block implementation.
 *
 * @package categoryposts.
 *
 * @since 4.9
 */

namespace categoryPosts;

/**
 * Renders the `tiptip/category-posts-block` on server.
 *
 * @see WP_Widget_Archives
 *
 * @param array $attributes The block attributes.
 *
 * @return string Returns the post content with archives added.
 */
function render_category_posts_block( $attributes ) {
	global $attr, $before_title, $after_title;

	$attr = $attributes;

	// Get HTML
	$widget = new Widget();
	$instance = array();

	$instance['asc_sort_order']      = $attributes['order'] === 'desc' ? false : true;
	$instance['title']               = $attributes['title'];
	$instance['hide_title']          = $attributes['hideTitle'];

	$instance['footer_link_text']    = $attributes['footerLinkText'];
	$instance['footer_link']         = $attributes['footerLink'];

	$instance = upgrade_settings( $instance );
	
	$current_post_id = '';
	if ( is_singular() ) {
		$current_post_id = get_the_ID();
	}

	$items = $widget->get_elements_HTML( $instance, $current_post_id, 0, 0 );

	$ret = $widget->titleHTML( $before_title, $after_title, $instance );

	$ret .= "<ul>" . implode( $items ) . "</ul>";

	return $ret;
}

/**
 * Registers all block assets so that they can be enqueued through the block editor
 * in the corresponding context.
 *
 * @see https://developer.wordpress.org/block-editor/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */
function category_posts_block_init() {
	$dir = __DIR__;

	$script_asset_path = "$dir/build/index.asset.php";
	if ( ! file_exists( $script_asset_path ) ) {
		throw new Error(
			'You need to run `npm start` or `npm run build` for the "tiptip/category-posts-block" block first.'
		);
	}
	$index_js     = 'build/index.js';
	$script_asset = require( $script_asset_path );
	wp_register_script(
		'tiptip-category-posts-block-editor',
		plugins_url( $index_js, __FILE__ ),
		$script_asset['dependencies'],
		$script_asset['version']
	);
	wp_set_script_translations( 'tiptip-category-posts-block-editor', 'category-posts' );

	$editor_css = 'build/style-index.css';
	wp_register_style(
		'tiptip-category-posts-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		array(),
		filemtime( "$dir/$editor_css" )
	);

	$style_css = 'build/style-index.css';
	wp_register_style(
		'tiptip-category-posts-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "$dir/$style_css" )
	);

	register_block_type(
		'tiptip/category-posts-block',
		array(
			'editor_script' => 'tiptip-category-posts-block-editor',
			'editor_style'  => 'tiptip-category-posts-block-editor',
			'style'         => 'tiptip-category-posts-block',
			'attributes'    => array(
				'hideTitle' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'title' => array(
					'type'    => 'string',
					'default' => __( 'Category Posts', 'category-posts' ),
				),
				'titleLink' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'titleLinkUrl' => array(
					'type'    => 'string',
					'default' => __( '', 'category-posts' ),
				),
				'titleLevel' => array(
					'type'    => 'string',
					'default' => __( 'initial', 'category-posts' ),
				),
				'disableCSS' => array(
					'type'           => 'boolean',
					'default'        => false,
				),
				'disableFontStyles' => array(
					'type'           => 'boolean',
					'default'        => false,
				),
				'disableThemeStyles' => array(
					'type'           => 'boolean',
					'default'        => false,
				),
				'showPostCounts' => array(
					'type'           => 'boolean',
					'default'        => false,
				),
				'displayAsDropdown' => array(
					'type'           => 'boolean',
					'default'        => false,
				),
				'groupBy' => array(
					'type'    => 'string',
					'default' => 'monthly',
				),
				'order' => array(
					'type'    => 'string',
					'default' => 'desc',
				),
				'orderBy' => array(
					'type'    => 'string',
					'default' => 'date',
				),
				'categorySuggestions' => array(
					'type'    => 'array',
					'default' => [],
				),
				'selectCategories' => array(
					'type'    => 'array',
					'default' => '',
				),
				'categories' => array(
					'type'    => 'array',
					'default' => [],
				),
				'footerLinkText' => array(
					'type'    => 'string',
					'default' => '',
				),
				'footerLink' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'render_callback' => __NAMESPACE__ . '\render_category_posts_block',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\category_posts_block_init' );
