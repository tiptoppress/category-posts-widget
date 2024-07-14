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

	$instance['title']                  = $attributes['title'];
	$instance['title_link']             = $attributes['titleLink'];
	$instance['title_level']            = $attributes['titleLevel'];
	$instance['title_link_url']         = $attributes['titleLinkUrl'];
	$instance['hide_title']             = $attributes['hideTitle'];
	$instance['category_suggestions']   = $attributes['categorySuggestions'];
	$instance['select_categories']      = $attributes['selectCategories'];
	$instance['cat']                    = $attributes['categories'];
	$instance['num']                    = $attributes['num'];
	$instance['offset']                 = $attributes['offset'];
	$instance['sort_by']                = $attributes['orderBy'];
	$instance['status']                 = $attributes['status'];
	$instance['asc_sort_order']         = $attributes['order'] === 'desc' ? false : true;
	$instance['exclude_current_post']   = $attributes['excludeCurrentPost'];
	$instance['hide_no_thumb']          = $attributes['hideNoThumb'];
	$instance['sticky']                 = $attributes['sticky'];
	$instance['footer_link_text']       = $attributes['footerLinkText'];
	$instance['footer_link']            = $attributes['footerLink'];
	$instance['item_title_level']       = $attributes['itemTitleLevel'];
	$instance['item_title_lines']       = $attributes['itemTitleLines'];
	$instance['thumb_w']                = $attributes['thumbW'];
	$instance['thumb_fluid_width']      = $attributes['thumbFluidWidth'];
	$instance['thumb_h']                = $attributes['thumbH'];
	$instance['thumb_hover']            = $attributes['thumbHover'];
	$instance['hide_post_titles']       = $attributes['hidePostTitles'];
	$instance['excerpt_lines']          = $attributes['excerptLines'];
	$instance['excerpt_length']         = $attributes['excerptLength'];
	$instance['excerpt_more_text']      = $attributes['excerptMoreText'];
	$instance['excerpt_filters']        = $attributes['excerptFilters'];
	$instance['comment_num']            = $attributes['commentNum'];
	$instance['disable_css']            = $attributes['disableCss'];
	$instance['disable_font_styles']    = $attributes['disableFontStyles'];
	$instance['disable_theme_styles']   = $attributes['disableThemeStyles'];
	$instance['show_post_format']       = $attributes['showPostFormat'];
	$instance['no_cat_childs']           = $attributes['noCatChilds'];
	$instance['everything_is_link']     = $attributes['everythingIsLink'];
	$instance['preset_date_format']     = $attributes['presetDateFormat'];
	$instance['date_format']            = $attributes['dateFormat'];
	$instance['date_past_time']         = $attributes['datePastTime'];
	$instance['template']               = $attributes['template'];
	$instance['text_do_not_wrap_thumb'] = $attributes['textDoNotWrapThumb'];
	$instance['enable_loadmore']        = $attributes['enableLoadmore'];
	$instance['loadmore_scroll_to']     = $attributes['loadmoreScrollTo'];
	$instance['loadmore_text']          = $attributes['loadmoreText'];
	$instance['loading_text']           = $attributes['loadingText'];
	$instance['date_range']             = $attributes['dateRange'];
	$instance['start_date']             = $attributes['startDate'];
	$instance['end_date']               = $attributes['endDate'];
	$instance['days_ago']               = $attributes['daysAgo'];
	$instance['no_match_handling']      = $attributes['noMatchHandling'];
	$instance['no_match_text']          = $attributes['noMatchText'];
	$instance['default_thunmbnail']     = $attributes['defaultThunmbnail'];
	$instance['ver']                    = $attributes['ver'];

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

	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => __NAMESPACE__ . '\render_category_posts_block',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\category_posts_block_init' );
