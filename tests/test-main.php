<?php

define( 'NS', 'categoryPosts' );

/**
 *  Normalize html for comparison by removing white space between tags, and leading/ending space
 *
 *  @param string $string The html to normalize.
 *  @return string A Normalized string.
 */
function removeSpaceBetweenTags( $string ) {
	$string = preg_replace( '~\s+~', ' ', $string ); // collapse spaces the way html handles it.
	return trim( preg_replace( '~>\s*<~', '><', $string ) );
}

/**
 *  Filter function to test the widget_title filter behaviour.
 *  Helps to check html escaping as a side job
 *
 *  @param string $title The title as passed to the filter.
 *  @return string whatever constant string
 */
function titleFilterTest( $title ) {
	return 'Me > You';
}

/**
 *  Add a file as an attachment.
 *
 *  @param string $filename The path of the file to add as an attachment.
 *  @return int the ID of the new attachment
 */
function _make_attachment( $filename ) {

	$contents = file_get_contents( $filename );

	$upload = wp_upload_bits( basename( $filename ), '', $contents );
	$type = '';
	if ( ! empty( $upload['type'] ) ) {
		$type = $upload['type'];
	} else {
		$mime = wp_check_filetype( $upload['file'] );
		if ( $mime ) {
			$type = $mime['type'];
		}
	}

	$attachment = array(
		'post_title'     => basename( $upload['file'] ),
		'post_content'   => '',
		'post_type'      => 'attachment',
		'post_mime_type' => $type,
		'guid'           => $upload['url'],
	);

	// Save the data.
	$id = wp_insert_attachment( $attachment, $upload['file'] );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

	return $id;

}

class testWidgetFront extends WP_UnitTestCase {

	/**
	 *  Check that there are no errors when instance is new
	 */
	public function testNoSetting() {
		$className = NS . '\Widget';
		$widget = new $className();
		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			),
			array()
		);
		$out = removeSpaceBetweenTags( ob_get_contents() );
		ob_end_clean();
		$this->assertEquals( 'Recent Posts<ul id="category-posts--internal" class="category-posts-internal"></ul>', $out );
	}

	/**
	 *  Test the titleHTML method of the widget
	 */
	function testtitleHTML() {
		$className = NS . '\Widget';
		$widget = new $className();

		// test no setting, should return empty  string
		$out = $widget->titleHTML( '', '', array() );
		$this->assertEquals( 'Recent Posts', $out );

		// test simple title
		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'title' => 'test',
			)
		);
		$this->assertEquals( '<h3>test</h3>', $out );

		// test simple title with html escape
		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'title' => 'te&st',
			)
		);
		$this->assertEquals( '<h3>te&#038;st</h3>', $out );

		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'title'      => 'te&st',
				'hide_title' => false,
			)
		);
		$this->assertEquals( '<h3>te&#038;st</h3>', $out );

		// test hide title
		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'title'      => 'test',
				'hide_title' => true,
			)
		);
		$this->assertEquals( '', $out );

		// test title as category name when title empty
		$cid = $this->factory->category->create( array( 'name' => 'test cat' ) );

		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'cat' => $cid,
			)
		);
		$this->assertEquals( '<h3>test cat</h3>', $out );

		// test title as category name when title is empty string
		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'test' => '',
				'cat'  => $cid,
			)
		);
		$this->assertEquals( '<h3>test cat</h3>', $out );

		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'test'       => '',
				'hide_title' => false,
				'cat'        => $cid,
			)
		);
		$this->assertEquals( '<h3>test cat</h3>', $out );

		// test title as category name when title is empty string not displayed when tite is hidden
		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'test'       => '',
				'cat'        => $cid,
				'hide_title' => true,
			)
		);
		$this->assertEquals( '', $out );

		// empty title with non existing category
		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'test' => '',
				'cat'  => 10000,
			)
		);
		$this->assertEquals( '<h3>Recent Posts</h3>', $out );

		// link to category with manual title
		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'test'       => 'test',
				'cat'        => $cid,
				'title_link' => true,
			)
		);
		$this->assertEquals( '<h3><a href="http://example.org/?cat=' . $cid . '">test cat</a></h3>', $out );

		// link to category with no manual title
		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'cat'        => $cid,
				'title_link' => true,
			)
		);
		$this->assertEquals( '<h3><a href="http://example.org/?cat=' . $cid . '">test cat</a></h3>', $out );

		// no link when it is not set to be
		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'cat'        => $cid,
				'title_link' => false,
			)
		);
		$this->assertEquals( '<h3>test cat</h3>', $out );

		// link to not existing category will just not link unless link is provided.
		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'title_link' => true,
			)
		);
		$this->assertEquals( '<h3><a href="http://example.org">Recent Posts</a></h3>', $out );

		// test widget_title filtering
		add_filter( 'widget_title', 'titleFilterTest' );

		// widget_filte filter for link to category with no manual title
		// for a widget, the title should be escaped by the filtering code
		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'cat'        => $cid,
				'title_link' => true,
			)
		);
		$this->assertEquals( '<h3><a href="http://example.org/?cat=' . $cid . '">Me > You</a></h3>', $out );

		// for a shortcode the filter is not applied
		 $out = $widget->titleHTML(
			 '<h3>', '</h3>', array(
				 'cat'          => $cid,
				 'title_link'   => true,
				 'is_shortcode' => true,
			 )
		 );
		$this->assertEquals( '<h3><a href="http://example.org/?cat=' . $cid . '">test cat</a></h3>', $out );

		// widget_filte filter fortitle without a link
		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'cat' => $cid,
			)
		);
		$this->assertEquals( '<h3>Me > You</h3>', $out );

		remove_filter( 'widget_title', 'titleFilterTest' );

		// test all categories links point to the post page when appropriate.
		$page = $this->factory->post->create(
			array(
				'post_type'   => 'page',
				'title'       => 'test',
				'post_status' => 'publish',
			)
		);

		update_option( 'page_for_posts', $page );
		$out = $widget->titleHTML(
			'<h3>', '</h3>', array(
				'title_link' => true,
			)
		);
		$this->assertEquals( '<h3><a href="http://example.org/?page_id=' . $page . '">Recent Posts</a></h3>', $out );
	}

	/**
	 *  Test the footerHTML method of the widget
	 */
	function testfooterHTML() {
		$className = NS . '\Widget';
		$widget = new $className();

		// no options set
		$out = $widget->footerHTML( array() );
		$this->assertEquals( '', $out );

		// option set to not do it
		$out = $widget->footerHTML(
			array(
				'footer_link' => false,
			)
		);
		$this->assertEquals( '', $out );

		// empty category.
		$out = $widget->footerHTML(
			array(
				'footer_link' => 'http://test.org',
			)
		);
		$this->assertEquals( '<a class="cat-post-footer-link" href="http://test.org">http://test.org</a>', $out );

		// bad category.
		$out = $widget->footerHTML(
			array(
				'footer_link' => 'http://test.org',
				'cat'         => 1000,
			)
		);
		$this->assertEquals( '<a class="cat-post-footer-link" href="http://test.org">http://test.org</a>', $out );

		// bad category no css.
		$out = $widget->footerHTML(
			array(
				'footer_link' => 'http://test.org',
				'cat'         => 1000,
				'disable_css' => true,
			)
		);
		$this->assertEquals( '<a class="cat-post-footer-link" href="http://test.org">http://test.org</a>', $out );

		// valid category.
		$cid = $this->factory->category->create(
			array(
				'name' => 'test cat',
			)
		);

		$out = $widget->footerHTML(
			array(
				'footer_link' => 'http://test.org',
				'cat'         => $cid,
			)
		);
		$this->assertEquals( '<a class="cat-post-footer-link" href="http://test.org">http://test.org</a>', $out );

		// valid category explicit css.
		$out = $widget->footerHTML(
			array(
				'footer_link' => 'http://test.org',
				'disable_css' => false,
				'cat'         => $cid,
			)
		);
		$this->assertEquals( '<a class="cat-post-footer-link" href="http://test.org">http://test.org</a>', $out );

		// valid category no css.
		$out = $widget->footerHTML(
			array(
				'footer_link' => 'http://test.org',
				'cat'         => $cid,
				'disable_css' => true,
			)
		);
		$this->assertEquals( '<a class="cat-post-footer-link" href="http://test.org">http://test.org</a>', $out );

		// test footer link for "all categories" when a posts page is set.
		$page = $this->factory->post->create(
			array(
				'post_type'   => 'page',
				'title'       => 'test',
				'post_status' => 'publish',
			)
		);

		update_option( 'page_for_posts', $page );
		$out = $widget->footerHTML(
			array(
				'footer_link' => 'http://test.org',
				'cat'         => 0,
			)
		);
		$this->assertEquals( '<a class="cat-post-footer-link" href="http://test.org">http://test.org</a>', $out );
	}

	/**
	 *  Test the excerpt_length_filter method of the widget
	 */
	function testexcerpt_length_filter() {
		$className = NS . '\Widget';
		$widget = new $className();

		// no setting
		$widget->instance = array();
		$this->assertEquals( 55, $widget->excerpt_length_filter( 55 ) );

		$widget->instance = array( 'excerpt_length' => 20 );
		$this->assertEquals( 20, $widget->excerpt_length_filter( 55 ) );
	}

	/**
	 *  Test the excerpt_more_filter method of the widget
	 */
	function testexcerpt_more_filter() {
		$className = NS . '\Widget';
		$widget = new $className();

		// generate a post to test with as the function expects to be called in a loop
		$pid = $this->factory->post->create(
			array(
				'title'       => 'test',
				'post_status' => 'publish',
			)
		);

		global $post;
		$post = get_post( $pid );
		setup_postdata( $post );

		$widget->instance['excerpt_more_text'] = 'text"';
		$this->assertEquals( ' <a class="cat-post-excerpt-more more-link" href="http://example.org/?p=' . $pid . '">text&quot;</a>', $widget->excerpt_more_filter( '' ) );
	}

	/**
	 *  Test the queryArgs method of the widget
	 */
	function testqueryArgs() {
		$className = NS . '\Widget';
		$widget = new $className();

		// no settings, just have defaults.
		$instance = array();
		$expected = array(
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => 1,
		);
		$this->assertEquals( $expected, $widget->queryArgs( $instance ) );

		$sort_criteria = array( null, 'date', 'title', 'comment_count', 'rand', 'garbage' );
		$sort_criteria_results = array( 'date', 'date', 'title', 'comment_count', 'rand', 'date' );

		$sort_order = array( 'whatever', true, null, false );
		$sort_order_results = array( 'ASC', 'ASC', 'DESC', 'DESC' );

		$cats = array( '10', 7, null, 'fail' );
		$cats_results = array( 10, 7, null, 0 );

		$nums = array( '10', 7, null, 'oops' );
		$nums_results = array( 10, 7, null, 0 );

		$offsets = array( null, 1, 2, 4 );
		$offset_results = array( null, null, 1, 3 ); // nuul for offset not set at all.

		$hidethumbs = array( true, null, false );
		$hidethumbs_results = array(
			array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
			null,
			null,
		);

		$no_cat_childs = array( null, false, true );
		$cat_param = array( 'cat', 'cat', 'category__in' );

		$pid = $this->factory->post->create(
			array(
				'title'       => 'test',
				'post_status' => 'publish',
			)
		);

		$statuses = array(
			null,
			'publish',
			'future',
			'publish,future',
			'private',
			'private,publish',
			'private,publish,future',
		);

		$exclude_current = array( 'whatever', true, null, false );

		$archivetests = array();
		$archiveresults = array();
		$posttest = array();
		$postresults = array();
		foreach ( $sort_criteria as $ksc => $sc ) {
			foreach ( $sort_order as $kso => $so ) {
				foreach ( $cats as $kcat => $cat ) {
					foreach ( $nums as $knum => $num ) {
						foreach ( $hidethumbs as $kt => $thumb ) {
							foreach ( $exclude_current as $ke => $exclude ) {
								foreach ( $offsets as $of => $offset ) {
									foreach ( $no_cat_childs as $onc => $no_child ) {
										foreach ( $statuses as $st => $status ) {
											$instance = array(
												'sort_by' => $sc,
												'asc_sort_order' => $so,
												'cat'     => $cat,
												'hideNoThumb' => $thumb,
												'exclude_current_post' => $exclude,
												'num'     => $num,
												'offset'  => $offset,
												'no_cat_childs' => $no_child,
												'status'  => $status,
											);
											$expected = array(
												'orderby' => $sort_criteria_results[ $ksc ],
												'order'   => $sort_order_results[ $kso ],
											);
											if ( $cat ) {
												$expected[ $cat_param[ $onc ] ] = $cats_results[ $kcat ];
											}

											if ( $num ) {
												$expected['showposts'] = $nums_results[ $knum ];
											}

											if ( $offset ) {
												if ( $offset_results[ $of ] ) {
													$expected['offset'] = $offset_results[ $of ];
												}
											}

											if ( $thumb ) {
												$expected['meta_query'] = $hidethumbs_results[ $kt ];
											}

											$expected['ignore_sticky_posts'] = 1;

											if ( $status ) {
												$expected['post_status'] = $status;
											}

											// tests for archive page.
											$archivetests[] = $instance;
											$archiveresults[] = $expected;

											// tests for single post page.
											if ( $exclude ) {
												$expected['post__not_in'] = array( $pid );
											}

											$posttests[] = $instance;
											$postresults[] = $expected;
										}
									}
								}
							}
						}
					}
				}
			}
		}

		// test archive type of page.
		$this->go_to( '/' );
		foreach ( $archivetests as $k => $instance ) {
			$this->assertEquals( $archiveresults[ $k ], $widget->queryArgs( $instance ) );
		}

		// test single post page
		$this->go_to( '/?p=' . $pid );
		foreach ( $posttests as $k => $instance ) {
			$this->assertEquals( $postresults[ $k ], $widget->queryArgs( $instance ) );
		}
	}

	/**
	 *  Differences between version 4.3 4.4 and 4.5 require manipulation of expected results for thunmbnail testing
	 *
	 *  @param string    $expected The expected result 4.5 format
	 *  @param WP_Widget $widget the widget to use for testing
	 *  $param int|array $size the size of requested thumbnail
	 */
	function postThumbnailTester( $expected, $widget, $size ) {
		global $wp_version;

		$compare_to = $widget->the_post_thumbnail( $size );
		$compare_to = preg_replace( '/ alt="[^"]*"/', '', $compare_to );

		if ( version_compare( $wp_version, '4.5', '<' ) ) { // size_WxH and size-{size name} were added to image at 4.5, remove if exist
		}
		if ( version_compare( $wp_version, '4.4', '<' ) ) { // srcset and sizes were added in 4.4
			$expected = preg_replace( '/ size-\w*/', '', $expected );
			$expected = preg_replace( '/ srcset="[^"]*"/', '', $expected );
			$expected = preg_replace( '/ sizes="[^"]*"/', '', $expected );
		}
		$this->assertEquals( $expected, $compare_to );
	}

	/**
	 * Test the post_thumbnail method of the widget
	 *
	 * @since 4.6
	 */
	public function test_the_post_thumbnail() {

		global $wp_version;

		$className = NS . '\Widget';
		$widget = new $className();

		// clean upload dir for consistent file names.
		$dir = wp_upload_dir();
		$dirurl = $dir['url'];
		$dir = $dir['path'];
		array_map( 'unlink', glob( $dir . '/*' ) );

		// 1) use image size: 640x480
		$pid = $this->factory->post->create(
			array(
				'title'       => 'canola',
				'post_status' => 'publish',
			)
		);
		$thumbnail_id = _make_attachment( DIR_TESTDATA . '/images/canola.jpg' ); // wp-content\plugins\4.5\tests\phpunit\includes\..\data\images\canola.jpg.
		set_post_thumbnail( $pid, $thumbnail_id );

		global $post;
		$post = get_post( $pid );
		setup_postdata( $post );

		// test no thumb width and height, should get same html
		// there are slight differences with how versions handle "empty" values.
		if ( version_compare( $wp_version, '4.5', '<' ) ) {
			$this->postThumbnailTester( '<img width="640" height="480" src="' . $dirurl . '/canola.jpg" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" srcset="' . $dirurl . '/canola-300x225.jpg 300w, ' . $dirurl . '/canola.jpg 640w" sizes="(max-width: 640px) 100vw, 640px" />', $widget, array() );
		} else {
			$this->postThumbnailTester( '<img width="640" height="480" src="' . $dirurl . '/canola.jpg" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" srcset="' . $dirurl . '/canola.jpg 640w, ' . $dirurl . '/canola-300x225.jpg 300w" sizes="(max-width: 640px) 100vw, 640px" />', $widget, array() );
		}

		$this->postThumbnailTester( '<img width="10" height="10" src="' . $dirurl . '/canola-150x150.jpg" class="attachment-10x10 size-10x10 wp-post-image" />', $widget, array( 10, '' ) );

		$this->postThumbnailTester( '<img width="10" height="10" src="' . $dirurl . '/canola-150x150.jpg" class="attachment-10x10 size-10x10 wp-post-image" />', $widget, array( '', 10 ) );

		// equal to min thumb size. no manipulation needed.
		$widget->instance = array(
			'thumb_h' => 150,
			'thumb_w' => 150,
		);
		$this->postThumbnailTester(
			'<span><img width="150" height="150" src="' . $dirurl . '/canola-150x150.jpg" class="attachment-150x150 size-150x150 wp-post-image" /></span>',
			$widget, array( 150, 150 )
		);

		$widget->instance = array(
			'thumb_h' => 200,
			'thumb_w' => 200,
		);

		if ( version_compare( $wp_version, '4.6', '<' ) ) {
			$this->postThumbnailTester(
				'<img width="200" height="150" src="' . $dirurl . '/canola-300x225.jpg" class="attachment-200x200 size-200x200 wp-post-image" srcset="' . $dirurl . '/canola-300x225.jpg 300w, ' . $dirurl . '/canola.jpg 640w" sizes="(max-width: 200px) 100vw, 200px" />',
				$widget, array( 200, 200 )
			);
		} else {
			$this->postThumbnailTester(
				'<span><img width="200" height="150" src="' . $dirurl . '/canola.jpg" class="attachment-200x200 size-200x200 wp-post-image" srcset="' . $dirurl . '/canola.jpg 640w, ' . $dirurl . '/canola-300x225.jpg 300w" sizes="(max-width: 200px) 100vw, 200px" /></span>',
				$widget, array( 200, 200 )
			);
		}

		// Use with "use_css_cropping".
		$widget->instance = array(
			'thumb_h'          => 150,
			'thumb_w'          => 150,
			'use_css_cropping' => true,
		);
		$this->postThumbnailTester(
			'<span class="cat-post-crop" style="width:150px;height:150px;"><img style="margin-top:-0px;height:150px;clip:rect(auto,150px,auto,0px);width:auto;max-width:initial;" width=\'150\' height=\'150\' src="' . $dirurl . '/canola-150x150.jpg" class="attachment-150x150 size-150x150 wp-post-image" /></span>',
			$widget, array( 150, 150 )
		);

		$widget->instance = array(
			'thumb_h'          => 200,
			'thumb_w'          => 200,
			'use_css_cropping' => true,
		);
		if ( version_compare( $wp_version, '4.6', '<' ) ) {
			$this->postThumbnailTester(
				'<span style="width:200px;height:200px;"><img style="margin-left:-33.333333333333px;height:200px;clip:rect(auto,233.33333333333px,auto,33.333333333333px);width:auto;max-width:initial;" width=\'266.66666666667\' height=\'200\' src="' . $dirurl . '/canola-300x225.jpg" class="attachment-200x200 size-200x200 wp-post-image" srcset="' . $dirurl . '/canola-300x225.jpg 300w, ' . $dirurl . '/canola.jpg 3w" sizes="(max-width: 266.66666666667px) 100vw, 266.66666666667px" /></span>',
				$widget, array( 200, 200 )
			);
		} else {
			$this->postThumbnailTester(
				'<span class="cat-post-crop" style="width:200px;height:200px;"><img style="margin-left:-33.333333333333px;height:200px;clip:rect(auto,233.33333333333px,auto,33.333333333333px);width:auto;max-width:initial;" width=\'266.66666666667\' height=\'200\' src="' . $dirurl . '/canola.jpg" class="attachment-200x200 size-200x200 wp-post-image" srcset="' . $dirurl . '/canola.jpg 640w, ' . $dirurl . '/canola-300x225.jpg 300w" sizes="(max-width: 266.66666666667px) 100vw, 266.66666666667px" /></span>',
				$widget, array( 200, 200 )
			);
		}

		// 2.) use smaller image as media -> settings thumbnail_size, image size: 50x50
		delete_post_thumbnail( $pid );
		$pid = $this->factory->post->create(
			array(
				'title'       => 'test-image',
				'post_status' => 'publish',
			)
		);
		$thumbnail_id = _make_attachment( DIR_TESTDATA . '/images/test-image.jpg' ); // wp-content\plugins\4.5\tests\phpunit\includes\..\data\images\test-image.jpg
		set_post_thumbnail( $pid, $thumbnail_id );

		$post = get_post( $pid );
		setup_postdata( $post );

		$widget->instance = array( 'use_css_cropping' => false );

		$widget->instance = array(
			'thumb_h' => 150,
			'thumb_w' => 150,
		);
		$this->postThumbnailTester(
			'<span><img width="50" height="50" src="' . $dirurl . '/test-image.jpg" class="attachment-150x150 size-150x150 wp-post-image" /></span>',
			$widget, array( 150, 150 )
		);

		$widget->instance = array(
			'thumb_h' => 200,
			'thumb_w' => 200,
		);
		$this->postThumbnailTester(
			'<span><img width="50" height="50" src="' . $dirurl . '/test-image.jpg" class="attachment-200x200 size-200x200 wp-post-image" /></span>',
			$widget, array( 200, 200 )
		);

		// Use with "use_css_cropping".
		$widget->instance = array(
			'thumb_h'          => 150,
			'thumb_w'          => 150,
			'use_css_cropping' => true,
		);
		$this->postThumbnailTester(
			'<span class="cat-post-crop" style="width:150px;height:150px;"><img style="margin-top:-0px;height:150px;clip:rect(auto,150px,auto,0px);width:auto;max-width:initial;" width=\'150\' height=\'150\' src="' . $dirurl . '/test-image.jpg" class="attachment-150x150 size-150x150 wp-post-image" /></span>',
			$widget, array( 150, 150 )
		);

		$widget->instance = array(
			'thumb_h'          => 200,
			'thumb_w'          => 200,
			'use_css_cropping' => true,
		);
		$this->postThumbnailTester(
			'<span class="cat-post-crop" style="width:200px;height:200px;"><img style="margin-top:-0px;height:200px;clip:rect(auto,200px,auto,0px);width:auto;max-width:initial;" width=\'200\' height=\'200\' src="' . $dirurl . '/test-image.jpg" class="attachment-200x200 size-200x200 wp-post-image" /></span>',
			$widget, array( 200, 200 )
		);

		// 3.) use bigger image as media -> settings large_size, image size: 1920x1080
		delete_post_thumbnail( $pid );
		$pid = $this->factory->post->create(
			array(
				'title'       => '33772',
				'post_status' => 'publish',
			)
		);
		$thumbnail_id = _make_attachment( DIR_TESTDATA . '/images/33772.jpg' ); // wp-content\plugins\4.5\tests\phpunit\includes\..\data\images\33772.jpg.
		set_post_thumbnail( $pid, $thumbnail_id );

		$post = get_post( $pid );
		setup_postdata( $post );

		$widget->instance = array( 'use_css_cropping' => false );

		$widget->instance = array(
			'thumb_h' => 150,
			'thumb_w' => 150,
		);
		$this->postThumbnailTester(
			'<span><img width="150" height="150" src="' . $dirurl . '/33772-150x150.jpg" class="attachment-150x150 size-150x150 wp-post-image" /></span>',
			$widget, array( 150, 150 )
		);

		$widget->instance = array(
			'thumb_h' => 200,
			'thumb_w' => 200,
		);
		if ( version_compare( $wp_version, '4.5', '<' ) ) {
			$this->postThumbnailTester( '<img width="825" height="510" src="' . $dirurl . '/33772-825x510.jpg" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" />', $widget, array() );
		} elseif ( version_compare( $wp_version, '4.6', '<' ) ) {
			$this->postThumbnailTester(
				'<img width="200" height="113" src="' . $dirurl . '/33772-768x432.jpg" class="attachment-200x200 size-200x200 wp-post-image" srcset="' . $dirurl . '/33772-768x432.jpg 768w, ' . $dirurl . '/33772-300x169.jpg 300w, ' . $dirurl . '/33772-1024x576.jpg 1024w" sizes="(max-width: 200px) 100vw, 200px" />',
				$widget, array( 200, 200 )
			);
		} else {
			$this->postThumbnailTester(
				'<span><img width="200" height="113" src="' . $dirurl . '/33772.jpg" class="attachment-200x200 size-200x200 wp-post-image" srcset="' . $dirurl . '/33772.jpg 1920w, ' . $dirurl . '/33772-300x169.jpg 300w, ' . $dirurl . '/33772-768x432.jpg 768w, ' . $dirurl . '/33772-1024x576.jpg 1024w" sizes="(max-width: 200px) 100vw, 200px" /></span>',
				$widget, array( 200, 200 )
			);
		}

		// Use with "use_css_cropping"
		$widget->instance = array(
			'thumb_h'          => 150,
			'thumb_w'          => 150,
			'use_css_cropping' => true,
		);
		$this->postThumbnailTester(
			'<span class="cat-post-crop" style="width:150px;height:150px;"><img style="margin-top:-0px;height:150px;clip:rect(auto,150px,auto,0px);width:auto;max-width:initial;" width=\'150\' height=\'150\' src="' . $dirurl . '/33772-150x150.jpg" class="attachment-150x150 size-150x150 wp-post-image" /></span>',
			$widget, array( 150, 150 )
		);

		$widget->instance = array(
			'thumb_h'          => 200,
			'thumb_w'          => 200,
			'use_css_cropping' => true,
		);
		if ( version_compare( $wp_version, '4.5', '<' ) ) {
			$this->postThumbnailTester(
				'<span style="width:200px;height:200px;"><img style="margin-left:-77.777777777778px;height:200px;clip:rect(auto,277.77777777778px,auto,77.777777777778px);width:auto;max-width:initial;" width=\'355.55555555556\' height=\'200\' src="' . $dirurl . '/33772-768x432.jpg" class="attachment-200x200 size-200x200 wp-post-image" /></span>',
				$widget, array( 200, 200 )
			);
		} elseif ( version_compare( $wp_version, '4.6', '<' ) ) {
			$this->postThumbnailTester(
				'<span style="width:200px;height:200px;"><img style="margin-left:-77.777777777778px;height:200px;clip:rect(auto,277.77777777778px,auto,77.777777777778px);width:auto;max-width:initial;" width=\'355.55555555556\' height=\'200\' src="' . $dirurl . '/33772-768x432.jpg" class="attachment-200x200 size-200x200 wp-post-image" srcset="' . $dirurl . '/33772-768x432.jpg 768w, ' . $dirurl . '/33772-300x169.jpg 300w, ' . $dirurl . '/33772-1024x576.jpg 1024w" sizes="(max-width: 355.55555555556px) 100vw, 355.55555555556px" /></span>',
				$widget, array( 200, 200 )
			);
		} else {
			$this->postThumbnailTester(
				'<span class="cat-post-crop" style="width:200px;height:200px;"><img style="margin-left:-77.777777777778px;height:200px;clip:rect(auto,277.77777777778px,auto,77.777777777778px);width:auto;max-width:initial;" width=\'355.55555555556\' height=\'200\' src="' . $dirurl . '/33772.jpg" class="attachment-200x200 size-200x200 wp-post-image" srcset="' . $dirurl . '/33772.jpg 1920w, ' . $dirurl . '/33772-300x169.jpg 300w, ' . $dirurl . '/33772-768x432.jpg 768w, ' . $dirurl . '/33772-1024x576.jpg 1024w" sizes="(max-width: 355.55555555556px) 100vw, 355.55555555556px" /></span>',
				$widget, array( 200, 200 )
			);
		}

		// test default thumb.
		$pidd = $this->factory->post->create(
			array(
				'title'       => 'default thumb',
				'post_status' => 'publish',
			)
		);
		$post = get_post( $pidd );
		setup_postdata( $post );

		$widget->instance = array(
			'thumb_h'            => 200,
			'thumb_w'            => 200,
			'use_css_cropping'   => true,
			'default_thunmbnail' => $thumbnail_id,
		);
		if ( version_compare( $wp_version, '4.5', '<' ) ) {
			$this->postThumbnailTester(
				'<span style="width:200px;height:200px;"><img style="margin-left:-77.777777777778px;height:200px;clip:rect(auto,277.77777777778px,auto,77.777777777778px);width:auto;max-width:initial;" width=\'355.55555555556\' height=\'200\' src="' . $dirurl . '/33772-768x432.jpg" class="attachment-200x200 size-200x200 wp-post-image" /></span>',
				$widget, array( 200, 200 )
			);
		} elseif ( version_compare( $wp_version, '4.6', '<' ) ) {
			$this->postThumbnailTester(
				'<span style="width:200px;height:200px;"><img style="margin-left:-77.777777777778px;height:200px;clip:rect(auto,277.77777777778px,auto,77.777777777778px);width:auto;max-width:initial;" width=\'355.55555555556\' height=\'200\' src="' . $dirurl . '/33772-768x432.jpg" class="attachment-200x200 size-200x200 wp-post-image" srcset="' . $dirurl . '/33772-768x432.jpg 768w, ' . $dirurl . '/33772-300x169.jpg 300w, ' . $dirurl . '/33772-1024x576.jpg 1024w" sizes="(max-width: 355.55555555556px) 100vw, 355.55555555556px" /></span>',
				$widget, array( 200, 200 )
			);
		} else {
			$this->postThumbnailTester(
				'<span class="cat-post-crop" style="width:200px;height:200px;"><img style="margin-left:-77.777777777778px;height:200px;clip:rect(auto,277.77777777778px,auto,77.777777777778px);width:auto;max-width:initial;" width=\'355.55555555556\' height=\'200\' src="' . $dirurl . '/33772.jpg" class="attachment-200x200 size-200x200 wp-post-image" srcset="' . $dirurl . '/33772.jpg 1920w, ' . $dirurl . '/33772-300x169.jpg 300w, ' . $dirurl . '/33772-768x432.jpg 768w, ' . $dirurl . '/33772-1024x576.jpg 1024w" sizes="(max-width: 355.55555555556px) 100vw, 355.55555555556px" /></span>',
				$widget, array( 200, 200 )
			);
		}

		$widget->instance = array(
			'thumb_h'            => 200,
			'thumb_w'            => 200,
			'default_thunmbnail' => $thumbnail_id,
		);
		if ( version_compare( $wp_version, '4.5', '<' ) ) {
			$this->postThumbnailTester( '<img width="825" height="510" src="' . $dirurl . '/33772-825x510.jpg" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" />', $widget, array() );
		} elseif ( version_compare( $wp_version, '4.6', '<' ) ) {
			$this->postThumbnailTester(
				'<img width="200" height="113" src="' . $dirurl . '/33772-768x432.jpg" class="attachment-200x200 size-200x200 wp-post-image" srcset="' . $dirurl . '/33772-768x432.jpg 768w, ' . $dirurl . '/33772-300x169.jpg 300w, ' . $dirurl . '/33772-1024x576.jpg 1024w" sizes="(max-width: 200px) 100vw, 200px" />',
				$widget, array( 200, 200 )
			);
		} else {
			$this->postThumbnailTester(
				'<span><img width="200" height="113" src="' . $dirurl . '/33772.jpg" class="attachment-200x200 size-200x200 wp-post-image" srcset="' . $dirurl . '/33772.jpg 1920w, ' . $dirurl . '/33772-300x169.jpg 300w, ' . $dirurl . '/33772-768x432.jpg 768w, ' . $dirurl . '/33772-1024x576.jpg 1024w" sizes="(max-width: 200px) 100vw, 200px" /></span>',
				$widget, array( 200, 200 )
			);
		}
	}

	/**
	 * Test that the global post variable is reset after widget loop.
	 */
	public function testLoopReset() {
		$className = NS . '\Widget';
		$widget = new $className();

		$cid = get_option( 'default_category' );

		$pid = $this->factory->post->create(
			array(
				'title'        => 'test',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		$pid2 = $this->factory->post->create(
			array(
				'title'        => 'test2',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);

		$this->go_to( '/' );
		$tempid = get_the_ID();
		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			), array(
				'cat' => $cid,
				'num' => 10,
			)
		);
		ob_end_clean();
		$this->assertEquals( $tempid, get_the_ID() );

		$this->go_to( '/?p=' . $pid );
		$tempid = get_the_ID();
		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			), array(
				'cat' => $cid,
				'num' => 10,
			)
		);
		ob_end_clean();
		$this->assertEquals( $tempid, get_the_ID() );
	}

	/**
	 *  Helper function to test that excerptmore filter is triggered
	 */
	function excerptMoreFilter( $v ) {
		return '[more test]';
	}

	/**
	 *  Helper function to test that excerpt length filter is triggered
	 */
	function excerptLengthFilter( $v ) {
		return 2;
	}

	/**
	 * Test that the excerpt filters are removed after the loop.
	 */
	public function testExcerptFilters() {
		$className = NS . '\Widget';
		$widget = new $className();

		$cid = get_option( 'default_category' );

		$pid = $this->factory->post->create(
			array(
				'post_title'   => 'test',
				'post_status'  => 'publish',
				'post_content' => 'more then one word',
				'post_excerpt' => '',
			)
		);

		add_filter( 'excerpt_more', array( $this, 'excerptMoreFilter' ), 10 );
		add_filter( 'excerpt_length', array( $this, 'excerptLengthFilter' ), 10 );

		// Test filters not applied when excerpt off.
		$this->go_to( '/?p=' . $pid );
		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			), array(
				'cat'            => $cid,
				'num'            => 10,
				'excerpt_length' => 1,
			)
		);
		$o = removeSpaceBetweenTags( ob_get_clean() );
		$this->assertEquals( 'Uncategorized<ul id="category-posts--internal" class="category-posts-internal"><li class=\'cat-post-item cat-post-current\'><p><a class="cat-post-title" href="http://example.org/?p=' . $pid . '" rel="bookmark">test</a></p></li></ul>', $o );

		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			), array(
				'cat'             => $cid,
				'num'             => 10,
				'excerpt_length'  => 1,
				'excerpt_filters' => true,
			)
		);
		$o = removeSpaceBetweenTags( ob_get_clean() );
		$this->assertEquals( 'Uncategorized<ul id="category-posts--internal" class="category-posts-internal"><li class=\'cat-post-item cat-post-current\'><p><a class="cat-post-title" href="http://example.org/?p=' . $pid . '" rel="bookmark">test</a></p></li></ul>', $o );

		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			), array(
				'cat'            => $cid,
				'num'            => 10,
				'excerpt'        => false,
				'excerpt_length' => 1,
			)
		);
		$o = removeSpaceBetweenTags( ob_get_clean() );
		$this->assertEquals( 'Uncategorized<ul id="category-posts--internal" class="category-posts-internal"><li class=\'cat-post-item cat-post-current\'><p><a class="cat-post-title" href="http://example.org/?p=' . $pid . '" rel="bookmark">test</a></p></li></ul>', $o );

		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			), array(
				'cat'             => $cid,
				'num'             => 10,
				'excerpt'         => false,
				'excerpt_length'  => 1,
				'excerpt_filters' => true,
			)
		);
		$o = removeSpaceBetweenTags( ob_get_clean() );
		$this->assertEquals( 'Uncategorized<ul id="category-posts--internal" class="category-posts-internal"><li class=\'cat-post-item cat-post-current\'><p><a class="cat-post-title" href="http://example.org/?p=' . $pid . '" rel="bookmark">test</a></p></li></ul>', $o );

		// test excerpt length filter.
		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			), array(
				'cat'            => $cid,
				'num'            => 10,
				'excerpt'        => true,
				'excerpt_length' => 1,
			)
		);
		$o = removeSpaceBetweenTags( ob_get_clean() );
		$this->assertEquals( 'Uncategorized<ul id="category-posts--internal" class="category-posts-internal"><li class=\'cat-post-item cat-post-current\'><p><a class="cat-post-title" href="http://example.org/?p=' . $pid . '" rel="bookmark">test</a></p><p>more[more test]</p></li></ul>', $o );

		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			), array(
				'cat'             => $cid,
				'num'             => 10,
				'excerpt'         => true,
				'excerpt_length'  => 1,
				'excerpt_filters' => true,
			)
		);
		$o = removeSpaceBetweenTags( ob_get_clean() );
		$this->assertEquals( 'Uncategorized<ul id="category-posts--internal" class="category-posts-internal"><li class=\'cat-post-item cat-post-current\'><p><a class="cat-post-title" href="http://example.org/?p=' . $pid . '" rel="bookmark">test</a></p><p>more[more test]</p></li></ul>', $o );

		// test excerpt more filter.
		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			), array(
				'cat'               => $cid,
				'num'               => 10,
				'excerpt'           => true,
				'excerpt_length'    => 1,
				'excerpt_more_text' => 'blabla',
			)
		);
		$o = removeSpaceBetweenTags( ob_get_clean() );
		$this->assertEquals( 'Uncategorized<ul id="category-posts--internal" class="category-posts-internal"><li class=\'cat-post-item cat-post-current\'><p><a class="cat-post-title" href="http://example.org/?p=' . $pid . '" rel="bookmark">test</a></p><p>more <a class="cat-post-excerpt-more more-link" href="http://example.org/?p=' . $pid . '">blabla</a></p></li></ul>', $o );

		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			), array(
				'cat'               => $cid,
				'num'               => 10,
				'excerpt'           => true,
				'excerpt_length'    => 1,
				'excerpt_more_text' => 'blabla',
				'excerpt_filters'   => true,
			)
		);
		$o = removeSpaceBetweenTags( ob_get_clean() );
		$this->assertEquals( 'Uncategorized<ul id="category-posts--internal" class="category-posts-internal"><li class=\'cat-post-item cat-post-current\'><p><a class="cat-post-title" href="http://example.org/?p=' . $pid . '" rel="bookmark">test</a></p><p>more <a class="cat-post-excerpt-more more-link" href="http://example.org/?p=' . $pid . '">blabla</a></p></li></ul>', $o );

		remove_filter( 'excerpt_more', array( $this, 'excerptMoreFilter' ), 10 );
		remove_filter( 'excerpt_length', array( $this, 'excerptLengthFilter' ), 10 );

	}

	/**
	 * Test that the excerpt filters are removed after the loop.
	 */
	public function testExcerptFilterRemove() {
		$className = NS . '\Widget';
		$widget = new $className();

		$cid = get_option( 'default_category' );

		$pid = $this->factory->post->create(
			array(
				'title'        => 'test',
				'post_status'  => 'publish',
				'post_content' => 'more then one word',
				'post_excerpt' => '',
			)
		);

		add_filter( 'excerpt_more', array( $this, 'excerptMoreFilter' ), 10 );
		add_filter( 'excerpt_length', array( $this, 'excerptLengthFilter' ), 10 );

		$this->go_to( '/?p=' . $pid );
		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			), array(
				'cat'               => $cid,
				'num'               => 10,
				'excerpt'           => true,
				'excerpt_length'    => 1,
				'excerpt_more_text' => 'blabla',
			)
		);
		ob_end_clean();
		ob_start();
		the_excerpt();
		$excerpt = trim( ob_get_clean() );
		$this->assertEquals( '<p>more then[more test]</p>', $excerpt );

		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			), array(
				'cat'               => $cid,
				'num'               => 10,
				'excerpt'           => true,
				'excerpt_length'    => 1,
				'excerpt_more_text' => 'blabla',
				'excerpt_filters'   => true,
			)
		);
		ob_end_clean();
		ob_start();
		the_excerpt();
		$excerpt = trim( ob_get_clean() );
		$this->assertEquals( '<p>more then[more test]</p>', $excerpt );

		remove_filter( 'excerpt_more', array( $this, 'excerptMoreFilter' ), 10 );
		remove_filter( 'excerpt_length', array( $this, 'excerptLengthFilter' ), 10 );

	}

	/**
	 *  test that the internal excerpt genertion works
	 */
	function testInternalExcerptGeneration() {
		$className = NS . '\Widget';
		$widget = new $className();

		$cid = get_option( 'default_category' );

		$pid = $this->factory->post->create(
			array(
				'post_title'   => 'test',
				'post_status'  => 'publish',
				'post_content' => 'more then one word',
				'post_excerpt' => '',
			)
		);

		$this->go_to( '/?p=' . $pid );

		// test length default.
		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			), array(
				'cat'             => $cid,
				'num'             => 10,
				'excerpt'         => true,
				'excerpt_filters' => '',
			)
		);
		$o = removeSpaceBetweenTags( ob_get_clean() );
		$this->assertEquals( 'Uncategorized<ul id="category-posts--internal" class="category-posts-internal"><li class=\'cat-post-item cat-post-current\'><p><a class="cat-post-title" href="http://example.org/?p=' . $pid . '" rel="bookmark">test</a></p><p>more then one word</p></li></ul>', $o );

		// test more text default.
		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			), array(
				'cat'             => $cid,
				'num'             => 10,
				'excerpt'         => true,
				'excerpt_length'  => 1,
				'excerpt_filters' => '',
			)
		);
		$o = removeSpaceBetweenTags( ob_get_clean() );
		$this->assertEquals( 'Uncategorized<ul id="category-posts--internal" class="category-posts-internal"><li class=\'cat-post-item cat-post-current\'><p><a class="cat-post-title" href="http://example.org/?p=' . $pid . '" rel="bookmark">test</a></p><p>more <a class="cat-post-excerpt-more" href="http://example.org/?p=' . $pid . '" title="Continue reading test">[&hellip;]</a></p></li></ul>', $o );

		ob_start();
		$widget->widget(
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			), array(
				'cat'               => $cid,
				'num'               => 10,
				'excerpt'           => true,
				'excerpt_length'    => 1,
				'excerpt_more_text' => 'blabla',
				'excerpt_filters'   => '',
			)
		);
		$o = removeSpaceBetweenTags( ob_get_clean() );
		$this->assertEquals( 'Uncategorized<ul id="category-posts--internal" class="category-posts-internal"><li class=\'cat-post-item cat-post-current\'><p><a class="cat-post-title" href="http://example.org/?p=' . $pid . '" rel="bookmark">test</a></p><p>more <a class="cat-post-excerpt-more" href="http://example.org/?p=' . $pid . '" title="Continue reading test">blabla</a></p></li></ul>', $o );

	}
}

class testWidgetAdmin extends WP_UnitTestCase {

	public function testformTitlePanel() {
		$className = NS . '\Widget';
		$widget = new $className();

		// no setting.
		ob_start();
		$widget->formTitlePanel( array() );
		$out = removeSpaceBetweenTags( ob_get_contents() );
		ob_end_clean();
		$this->assertEquals(
			'<h4 data-panel="title">Title</h4><div><p><label for="widget-category-posts--title">' .
					' Title: ' .
					'<input class="widefat" style="width:80%;" id="widget-category-posts--title" name="widget-category-posts[][title]" type="text" value="" /></label></p><p><label for="widget-category-posts--title_link"><input type="checkbox" class="checkbox" id="widget-category-posts--title_link" name="widget-category-posts[][title_link]" />' .
					' Make widget title link </label></p><p><label for="widget-category-posts--hide_title"><input type="checkbox" class="checkbox" id="widget-category-posts--hide_title" name="widget-category-posts[][hide_title]" />' .
					' Hide title </label></p></div>', $out
		);

		// title.
		ob_start();
		$widget->formTitlePanel( array( 'title' => 'title <> me' ) );
		$out = removeSpaceBetweenTags( ob_get_contents() );
		ob_end_clean();
		$this->assertEquals(
			'<h4 data-panel="title">Title</h4><div><p><label for="widget-category-posts--title">' .
					' Title: ' .
					'<input class="widefat" style="width:80%;" id="widget-category-posts--title" name="widget-category-posts[][title]" type="text" value="title &lt;&gt; me" />' .
					'</label></p><p><label for="widget-category-posts--title_link"><input type="checkbox" class="checkbox" id="widget-category-posts--title_link" name="widget-category-posts[][title_link]" />' .
					' Make widget title link </label></p><p><label for="widget-category-posts--hide_title"><input type="checkbox" class="checkbox" id="widget-category-posts--hide_title" name="widget-category-posts[][hide_title]" />' .
					' Hide title </label></p></div>', $out
		);

		// title and link.
		ob_start();
		$widget->formTitlePanel(
			array(
				'title'      => 'title <> me',
				'title_link' => true,
			)
		);
		$out = removeSpaceBetweenTags( ob_get_contents() );
		ob_end_clean();
		$this->assertEquals(
			'<h4 data-panel="title">Title</h4><div><p><label for="widget-category-posts--title">' .
					' Title: ' .
					'<input class="widefat" style="width:80%;" id="widget-category-posts--title" name="widget-category-posts[][title]" type="text" value="title &lt;&gt; me" />' .
					'</label></p>' .
					'<p><label for="widget-category-posts--title_link"><input type="checkbox" class="checkbox" id="widget-category-posts--title_link" name="widget-category-posts[][title_link]" checked=\'checked\' />' .
					' Make widget title link </label></p><p><label for="widget-category-posts--hide_title"><input type="checkbox" class="checkbox" id="widget-category-posts--hide_title" name="widget-category-posts[][hide_title]" />' .
					' Hide title </label></p></div>', $out
		);

		// no title just link.
		ob_start();
		$widget->formTitlePanel(
			array(
				'title_link' => true,
			)
		);
		$out = removeSpaceBetweenTags( ob_get_contents() );
		ob_end_clean();
		$this->assertEquals(
			'<h4 data-panel="title">Title</h4><div><p><label for="widget-category-posts--title">' .
					' Title: ' .
					'<input class="widefat" style="width:80%;" id="widget-category-posts--title" name="widget-category-posts[][title]" type="text" value="" />' .
					'</label></p>' .
					'<p><label for="widget-category-posts--title_link"><input type="checkbox" class="checkbox" id="widget-category-posts--title_link" name="widget-category-posts[][title_link]" checked=\'checked\' />' .
					' Make widget title link </label></p><p><label for="widget-category-posts--hide_title"><input type="checkbox" class="checkbox" id="widget-category-posts--hide_title" name="widget-category-posts[][hide_title]" />' .
					' Hide title </label></p></div>', $out
		);

		// no title just link.
		ob_start();
		$widget->formTitlePanel(
			array(
				'hide_title' => true,
			)
		);
		$out = removeSpaceBetweenTags( ob_get_contents() );
		ob_end_clean();
		$this->assertEquals(
			'<h4 data-panel="title">Title</h4><div><p><label for="widget-category-posts--title">' .
					' Title: ' .
					'<input class="widefat" style="width:80%;" id="widget-category-posts--title" name="widget-category-posts[][title]" type="text" value="" />' .
					'</label></p>' .
					'<p><label for="widget-category-posts--title_link"><input type="checkbox" class="checkbox" id="widget-category-posts--title_link" name="widget-category-posts[][title_link]" />' .
					' Make widget title link </label></p><p><label for="widget-category-posts--hide_title"><input type="checkbox" class="checkbox" id="widget-category-posts--hide_title" name="widget-category-posts[][hide_title]" checked=\'checked\' />' .
					' Hide title </label></p></div>', $out
		);
	}
}

function default_settings() {
	return array(
		'title'                => 'Recent Posts',
		'title_link'           => false,
		'title_link_url'       => '',
		'hide_title'           => false,
		'cat'                  => 0,
		'num'                  => get_option( 'posts_per_page' ),
		'sort_by'              => 'date',
		'status'               => 'publish',
		'asc_sort_order'       => false,
		'exclude_current_post' => false,
		'hideNoThumb'          => false,
		'footer_link'          => '',
		'footer_link_text'     => '',
		'thumb_w'              => '150',
		'thumb_h'              => '150',
		'use_css_cropping'     => true,
		'thumb_hover'          => 'none',
		'hide_post_titles'     => false,
		'excerpt_length'       => 55,
		'excerpt_more_text'    => '...',
		'comment_num'          => false,
		'date_link'            => false,
		'date_format'          => '',
		'disable_css'          => false,
		'disable_font_styles'  => false,
		'offset'               => 1,
		'hide_social_buttons'  => '',
		'no_cat_childs'        => false,
		'excerpt_filters'      => false,
		'everything_is_link'   => false,
		'preset_date_format'   => 'sitedateandtime',
		'template'             => "%title%\n%thumb%",
		'show_post_format'     => 'none',
	);
}

class testShortCode extends WP_UnitTestCase {

	const SHORTCODE_NAME = 'catposts';
	const SHORTCODE_META = 'categoryPosts-shorcode';
	const WIDGET_BASE_ID = 'category-posts';

	/**
	 *  Test the generation and removal of met values when a shortcode is
	 *  inserted and removed from content
	 */
	public function testsave_post() {
		$pid = $this->factory->post->create(
			array(
				'title'        => 'test',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		// test no meta when post created with no shortcode.
		$this->assertEmpty( get_post_meta( $pid, self::SHORTCODE_META, true ) );

		// initialization to defaults when inserted.
		wp_update_post(
			array(
				'ID'           => $pid,
				'post_content' => '[' . self::SHORTCODE_NAME . ']',
			)
		);
		$this->assertEquals(
			array( '' => default_settings() ),
			get_post_meta( $pid, self::SHORTCODE_META, true )
		);

		// test change in other parts of the content.
		wp_update_post(
			array(
				'ID'           => $pid,
				'post_content' => '[' . self::SHORTCODE_NAME . '] lovely day',
			)
		);
		$this->assertEquals(
			array( '' => default_settings() ),
			get_post_meta( $pid, self::SHORTCODE_META, true )
		);

		// test removal.
		wp_update_post(
			array(
				'ID'           => $pid,
				'post_content' => '[' . $this->SHORTCODE_NAME . 'bla] ' . $this->SHORTCODE_NAME,
			)
		);
		$this->assertEmpty( get_post_meta( $pid, self::SHORTCODE_META, true ) );

		// same as above with name parameter
		// initialization to defaults when inserted.
		wp_update_post(
			array(
				'ID'           => $pid,
				'post_content' => '[' . self::SHORTCODE_NAME . ' name="test"]',
			)
		);
		$this->assertEquals(
			array( 'test' => default_settings() ),
			get_post_meta( $pid, self::SHORTCODE_META, true )
		);

		// test change in other parts of the content.
		wp_update_post(
			array(
				'ID'           => $pid,
				'post_content' => '[' . self::SHORTCODE_NAME . ' name="test"] lovely day',
			)
		);
		$this->assertEquals(
			array( 'test' => default_settings() ),
			get_post_meta( $pid, self::SHORTCODE_META, true )
		);

		// test removal.
		wp_update_post(
			array(
				'ID'           => $pid,
				'post_content' => '[' . $this->SHORTCODE_NAME . 'bla] ' . $this->SHORTCODE_NAME,
			)
		);
		$this->assertEmpty( get_post_meta( $pid, self::SHORTCODE_META, true ) );

		// test multiple shortcodes.
		wp_update_post(
			array(
				'ID'           => $pid,
				'post_content' => '[' . self::SHORTCODE_NAME . ' name="test"]' .
																		 '[' . self::SHORTCODE_NAME . ' mistake="test2"]' .
																		 '[' . self::SHORTCODE_NAME . ' name="test testing"]',
			)
		);
		$this->assertEquals(
			array(
				''             => default_settings(),
				'test'         => default_settings(),
				'test testing' => default_settings(),
			),
			get_post_meta( $pid, self::SHORTCODE_META, true )
		);
	}

	/**
	 * Test the customize_save_after function to make sure the shortcode meta is updated (or not)
	 * when the customizer save.
	 */
	public function test_customize_save_after() {
		$pid = $this->factory->post->create(
			array(
				'title'        => 'test',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		$pid2 = $this->factory->post->create(
			array(
				'title'        => 'test2',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		wp_update_post(
			array(
				'ID'           => $pid,
				'post_content' => '[' . self::SHORTCODE_NAME . ']',
			)
		);
		wp_update_post(
			array(
				'ID'           => $pid2,
				'post_content' => '[' . self::SHORTCODE_NAME . ']',
			)
		);

		// no update at all
		categoryPosts\customize_save_after();
		$this->assertEquals(
			array( '' => default_settings() ),
			get_post_meta( $pid, self::SHORTCODE_META, true )
		);

		// update some other post
		update_option( '_virtual-' . self::WIDGET_BASE_ID, array( $pid2 => array( 'title' => 'bla' ) ) );
		categoryPosts\customize_save_after();
		$this->assertEquals(
			array( '' => default_settings() ),
			get_post_meta( $pid, self::SHORTCODE_META, true )
		);

		// update some property on "our" post, title
		update_option( '_virtual-' . self::WIDGET_BASE_ID, array( $pid => array( '' => array( 'title' => 'bla' ) ) ) );
		categoryPosts\customize_save_after();
		$out = default_settings();
		$out['title'] = 'bla';
		$this->assertEquals(
			array( '' => $out ),
			get_post_meta( $pid, self::SHORTCODE_META, true )
		);

		// test multiple shortcodes
		wp_update_post(
			array(
				'ID'           => $pid,
				'post_content' => '[' . self::SHORTCODE_NAME . ' name="test"]' .
																		  '[' . self::SHORTCODE_NAME . ' mistake="test2"]' .
																		  '[' . self::SHORTCODE_NAME . ' name="test testing"]',
			)
		);
		update_option(
			'_virtual-' . self::WIDGET_BASE_ID, array(
				$pid => array(
					''             => array( 'title' => 'bla' ),
					'test'         => array( 'title' => 'ble' ),
					'test testing' => array( 'title' => 'bla2' ),
				),
			)
		);
		categoryPosts\customize_save_after();

		$out1 = default_settings();
		$out1['title'] = 'bla';
		$out2 = default_settings();
		$out2['title'] = 'ble';
		$out3 = default_settings();
		$out3['title'] = 'bla2';
		$this->assertEquals(
			array(
				''             => $out1,
				'test'         => $out2,
				'test testing' => $out3,
			), get_post_meta( $pid, self::SHORTCODE_META, true )
		);
	}

	/**
	 *  Test shortcode output
	 */
	function test_output() {
		$pid = $this->factory->post->create(
			array(
				'post_type'    => 'post',
				'post_title'   => 'test',
				'post_status'  => 'publish',
				'post_content' => '[' . self::SHORTCODE_NAME . ']',
			)
		);
		// setup global enviroment.
		$this->go_to( '/?p=' . $pid );
		the_post();
		categoryposts\register_virtual_widgets(); // generate virtual widgets usually done in the head of the theme.
		ob_start();
		the_content();
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertEquals(
			'<div id="category-posts-shortcode-' . $pid . '" class="category-posts-shortcode">Recent Posts<ul>' .
						'<li class=\'cat-post-item cat-post-current\'><p><a class="cat-post-title" href="http://example.org/?p=' . $pid . '" rel="bookmark">test</a></p></li></ul>' .
						'</div>', str_replace( "\n", '', $content )
		);

		// named shortcode.
		wp_update_post(
			array(
				'ID'           => $pid,
				'post_content' => '[' . self::SHORTCODE_NAME . ' name="bla"]',
			)
		);
		// setup global environment.
		$this->go_to( '/?p=' . $pid );
		the_post();
		categoryposts\register_virtual_widgets(); // generate virtual widgets usually done in the head of the theme.
		ob_start();
		the_content();
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertEquals(
			'<div id="category-posts-shortcode-' . $pid . '-bla" class="category-posts-shortcode">Recent Posts<ul>' .
						'<li class=\'cat-post-item cat-post-current\'><p><a class="cat-post-title" href="http://example.org/?p=' . $pid . '" rel="bookmark">test</a></p></li></ul>' .
						'</div>', str_replace( "\n", '', $content )
		);
	}

	/**
	 * Test that the DB is cleaned after uninstall.
	 */
	public function test_uninstall() {
		// test widget option.
		add_option( 'widget-category-posts', 'dummy' );
		$uninstall = NS . '\uninstall';
		$uninstall();
		$this->assertEquals( false, get_option( 'widget-category-posts', false ) );

		// test meta
		$pid = $this->factory->post->create(
			array(
				'title'        => 'test',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		$pid2 = $this->factory->post->create(
			array(
				'title'        => 'test2',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		$pid3 = $this->factory->post->create(
			array(
				'title'        => 'test2',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		wp_update_post(
			array(
				'ID'           => $pid,
				'post_content' => '[' . self::SHORTCODE_NAME . ']',
			)
		);
		wp_update_post(
			array(
				'ID'           => $pid2,
				'post_content' => 'dummy',
			)
		);
		wp_update_post(
			array(
				'ID'           => $pid3,
				'post_content' => '[' . self::SHORTCODE_NAME . ']',
			)
		);
		$uninstall();
		$this->assertEquals( false, get_post_meta( $pid, 'categoryPosts-shorcode', true ) );
		$this->assertEquals( false, get_post_meta( $pid2, 'categoryPosts-shorcode', true ) );
		$this->assertEquals( false, get_post_meta( $pid3, 'categoryPosts-shorcode', true ) );
	}
}

class testVirtualwidget extends WP_UnitTestCase {

	/**
	 *  Test the id method
	 */
	function testId() {
		$v = new categoryPosts\virtualWidget( 'test', 'testclass', array() );
		$this->assertEquals( $v->id(), 'test' );
	}

	/**
	 * Test that virtual widgets are added from the collectio.
	 */
	public function testConstructor() {

		// test default setting with no override.
		$v = new categoryPosts\virtualWidget( 'test', 'testclass', array() );
		$col = categoryPosts\virtualWidget::getAllSettings();
		$this->assertEquals( $col['test'], default_settings() );

		// test default setting with override
		$v = new categoryPosts\virtualWidget( 'test2', 'testclass', array( 'title' => 'bla' ) );
		$col = categoryPosts\virtualWidget::getAllSettings();
		$expect = default_settings();
		$expect['title'] = 'bla';
		$this->assertEquals( $col['test2'], $expect );

	}

	/**
	 * Generate css rules that are applied to all widgets.
	 *
	 * @since 4.7
	 *
	 * @param string $id the identifier to be use as the widget id.
	 */
	public function defaultCss( $id ) {
		$rules = array(
			'.cat-post-item span.cat-post-css-cropping img {max-width: initial;	max-height: initial;}',
			'.cat-post-title {display: inline-block; font-size: 15px;}',
			'.cat-post-current .cat-post-title {font-weight: bold; text-transform: uppercase;}' .
			'.cat-post-date {font-size: 12px;	line-height: 18px; font-style: italic; margin-bottom: 10px;}',
			'.cat-post-comment-num {font-size: 12px; line-height: 18px;}',
			'.cat-post-author {margin-bottom: 0;}',
			'.cat-post-thumbnail {display: block;}',
			'.cat-post-thumbnail {margin: 5px 10px 5px 0;}',
			'item_clenup' => '.cat-post-item:before {content: ""; display: table; clear: both;}',
			'.cat-post-item:after {content: ""; display: table;	clear: both;}',
			'.cat-post-item .cat-post-css-cropping span {margin: 5px 10px 5px 0;  overflow: hidden; display:inline-block}',
			'.cat-post-item .cat-post-css-cropping img {margin: initial;}',
		);

		foreach ( $rules as $key => $rule ) {
			$ret[ $key ] = '#' . $id . ' ' . $rule;
		}

		return $ret;

	}

	/**
	 * Test getCSSRules method
	 *
	 * @since 4.7
	 */
	public function testGetCSSRules() {
		$v = new categoryPosts\virtualWidget(
			'test', 'testclass', array(
				'disable_css' => true,
			)
		);

		// no css for widget. Only essential css should be returned.
		$test = array();
		$expected = array(
			'thumb_crop'    => '#test-internal .cat-post-crop {overflow: hidden; display:block}',
			'thumb_styling' => '#test-internal .cat-post-item img {margin: initial;}',
		);
		$v->getCSSRules( false, $test );
		$this->assertEquals( $expected, $test );

		// no css for shortcode.
		$test = array();
		$expected = array(
			'thumb_crop'    => '#test .cat-post-crop {overflow: hidden; display:block}',
			'thumb_styling' => '#test .cat-post-item img {margin: initial;}',
		);
		$v->getCSSRules( true, $test );
		$this->assertEquals( $expected, $test );

		$v = new categoryPosts\virtualWidget(
			'test2', 'testclass', array()
		);

		// css for widget default settings.
		$test = array();
		$v->getCSSRules( false, $test );
		$expected = $this->defaultCss( 'test2-internal' );
		$expected['shortcode_styling'] = '#test2-internal .cat-post-item {border-bottom: 1px solid #ccc;	list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
		$expected['thumb_crop'] = '#test2-internal .cat-post-item:last-child {border-bottom: none;}';
		$expected['thumb_styling'] = '#test2-internal .cat-post-thumbnail {float:left;}';

		$this->assertEquals( $expected, $test );

		// css for shortcode default settings.
		$test = array();
		$v->getCSSRules( true, $test );
		$expected = $this->defaultCss( 'test2' );
		$expected[] = '#test2 .cat-post-item {border-bottom: 1px solid #ccc;	list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
		$expected[] = '#test2 .cat-post-item:last-child {border-bottom: none;}';
		$expected[] = '#test2 .cat-post-thumbnail {float:left;}';
		$expected[] = '#test2 .cat-post-thumbnail a {box-shadow:none}'; // this for the thumb link.
		$expected[] = '#test2 .cat-post-thumbnail a {border:0}'; // this for the thumb link.
		$expected[] = '#test2 p {margin:5px 0 0 0}'; // since on bottom it will make the spacing on cover.
		$this->assertEquals( $expected, $test );

		$v = new categoryPosts\virtualWidget(
			'test3', 'testclass', array(
				'thumbTop' => true,
			)
		);

		// css for widget with thumb up settings.
		$test = array();
		$v->getCSSRules( false, $test );
		$expected = $this->defaultCss( 'test3-internal' );
		$expected[] = '#test3-internal .cat-post-item {border-bottom: 1px solid #ccc;	list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
		$expected[] = '#test3-internal .cat-post-item:last-child {border-bottom: none;}';

		$this->assertEquals( $expected, $test );

		// css for shortcode with thumb up settings.
		$test = array();
		$v->getCSSRules( true, $test );
		$expected = $this->defaultCss( 'test3' );
		$expected[] = '#test3 .cat-post-item {border-bottom: 1px solid #ccc;	list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
		$expected[] = '#test3 .cat-post-item:last-child {border-bottom: none;}';
		$expected[] = '#test3 .cat-post-thumbnail a {box-shadow:none}'; // this for the thumb link.
		$expected[] = '#test3 .cat-post-thumbnail a {border:0}'; // this for the thumb link.
		$expected[] = '#test3 p {margin:5px 0 0 0}'; // since on bottom it will make the spacing on cover.

		$this->assertEquals( $expected, $test );

		$v = new categoryPosts\virtualWidget(
			'test4', 'testclass', array(
				'thumb_hover' => 'white',
			)
		);

		// css for widget with white hover settings.
		$test = array();
		$v->getCSSRules( false, $test );
		$expected = $this->defaultCss( 'test4-internal' );
		$expected[] = '#test4-internal .cat-post-item {border-bottom: 1px solid #ccc;	list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
		$expected[] = '#test4-internal .cat-post-item:last-child {border-bottom: none;}';
		$expected[] = '#test4-internal .cat-post-thumbnail {float:left;}';
		$expected[] = '#test4-internal .cat-post img {padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
		$expected[] = '#test4-internal .cat-post-white {background-color: white;}';
		$expected[] = '#test4-internal .cat-post-white img:hover {opacity: 0.8;}';

		$this->assertEquals( $expected, $test );

		// css for shortcode with white hover settings.
		$test = array();
		$v->getCSSRules( true, $test );
		$expected = $this->defaultCss( 'test4' );
		$expected[] = '#test4 .cat-post-item {border-bottom: 1px solid #ccc;	list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
		$expected[] = '#test4 .cat-post-item:last-child {border-bottom: none;}';
		$expected[] = '#test4 .cat-post-thumbnail {float:left;}';
		$expected[] = '#test4 .cat-post img {padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
		$expected[] = '#test4 .cat-post-white {background-color: white;}';
		$expected[] = '#test4 .cat-post-white img:hover {opacity: 0.8;}';
		$expected[] = '#test4 .cat-post-thumbnail a {box-shadow:none}'; // this for the thumb link.
		$expected[] = '#test4 .cat-post-thumbnail a {border:0}'; // this for the thumb link.
		$expected[] = '#test4 p {margin:5px 0 0 0}'; // since on bottom it will make the spacing on cover.

		$this->assertEquals( $expected, $test );

		$v = new categoryPosts\virtualWidget(
			'test5', 'testclass', array(
				'thumb_hover' => 'dark',
			)
		);

		// css for widget with dark hover settings.
		$test = array();
		$v->getCSSRules( false, $test );
		$expected = $this->defaultCss( 'test5-internal' );
		$expected[] = '#test5-internal .cat-post-item {border-bottom: 1px solid #ccc;	list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
		$expected[] = '#test5-internal .cat-post-item:last-child {border-bottom: none;}';
		$expected[] = '#test5-internal .cat-post-thumbnail {float:left;}';
		$expected[] = '#test5-internal .cat-post img {padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
		$expected[] = '#test5-internal .cat-post img:hover {-webkit-filter: brightness(75%); -moz-filter: brightness(75%); -ms-filter: brightness(75%); -o-filter: brightness(75%); filter: brightness(75%);}';

		$this->assertEquals( $expected, $test );

		// css for shortcode with dark hover settings.
		$test = array();
		$v->getCSSRules( true, $test );
		$expected = $this->defaultCss( 'test5' );
		$expected[] = '#test5 .cat-post-item {border-bottom: 1px solid #ccc;	list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
		$expected[] = '#test5 .cat-post-item:last-child {border-bottom: none;}';
		$expected[] = '#test5 .cat-post-thumbnail {float:left;}';
		$expected[] = '#test5 .cat-post img {padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
		$expected[] = '#test5 .cat-post img:hover {-webkit-filter: brightness(75%); -moz-filter: brightness(75%); -ms-filter: brightness(75%); -o-filter: brightness(75%); filter: brightness(75%);}';
		$expected[] = '#test5 .cat-post-thumbnail a {box-shadow:none}'; // this for the thumb link
		$expected[] = '#test5 .cat-post-thumbnail a {border:0}'; // this for the thumb link
		$expected[] = '#test5 p {margin:5px 0 0 0}'; // since on bottom it will make the spacing on cover

		$this->assertEquals( $expected, $test );

		$v = new categoryPosts\virtualWidget(
			'test6', 'testclass', array(
				'thumb_hover' => 'scale',
			)
		);

		// css for widget with scale hover settings
		$test = array();
		$v->getCSSRules( false, $test );
		$expected = $this->defaultCss( 'test6-internal' );
		$expected[] = '#test6-internal .cat-post-item {border-bottom: 1px solid #ccc;	list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
		$expected[] = '#test6-internal .cat-post-item:last-child {border-bottom: none;}';
		$expected[] = '#test6-internal .cat-post-thumbnail {float:left;}';
		$expected[] = '#test6-internal .cat-post img {padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
		$expected[] = '#test6-internal .cat-post-scale span {overflow: hidden; margin: 5px 10px 5px 0;}';
		$expected[] = '#test6-internal .cat-post-scale img {margin: initial; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
		$expected[] = '#test6-internal .cat-post-scale img:hover {-webkit-transform: scale(1.1, 1.1); -ms-transform: scale(1.1, 1.1); transform: scale(1.1, 1.1);}';

		$this->assertEquals( $expected, $test );

		// css for shortcode with blur hover settings
		$test = array();
		$v->getCSSRules( true, $test );
		$expected = $this->defaultCss( 'test6' );
		$expected[] = '#test6 .cat-post-item {border-bottom: 1px solid #ccc;	list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
		$expected[] = '#test6 .cat-post-item:last-child {border-bottom: none;}';
		$expected[] = '#test6 .cat-post-thumbnail {float:left;}';
		$expected[] = '#test6 .cat-post img {padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
		$expected[] = '#test6 .cat-post-scale span {overflow: hidden; margin: 5px 10px 5px 0;}';
		$expected[] = '#test6 .cat-post-scale img {margin: initial; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
		$expected[] = '#test6 .cat-post-scale img:hover {-webkit-transform: scale(1.1, 1.1); -ms-transform: scale(1.1, 1.1); transform: scale(1.1, 1.1);}';
		$expected[] = '#test6 .cat-post-thumbnail a {box-shadow:none}'; // this for the thumb link
		$expected[] = '#test6 .cat-post-thumbnail a {border:0}'; // this for the thumb link
		$expected[] = '#test6 p {margin:5px 0 0 0}'; // since on bottom it will make the spacing on cover

		$this->assertEquals( $expected, $test );

		$v = new categoryPosts\virtualWidget(
			'test7', 'testclass', array(
				'thumb_hover' => 'blur',
			)
		);

		// css for widget with blur hover settings
		$test = array();
		$v->getCSSRules( false, $test );
		$expected = $this->defaultCss( 'test7-internal' );
		$expected[] = '#test7-internal .cat-post-item {border-bottom: 1px solid #ccc;	list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
		$expected[] = '#test7-internal .cat-post-item:last-child {border-bottom: none;}';
		$expected[] = '#test7-internal .cat-post-thumbnail {float:left;}';
		$expected[] = '#test7-internal .cat-post img {padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
		$expected[] = '#test7-internal .cat-post-blur img:hover {-webkit-filter: blur(2px); -moz-filter: blur(2px); -o-filter: blur(2px); -ms-filter: blur(2px); filter: blur(2px);}';

		$this->assertEquals( $expected, $test );

		// css for shortcode with blur hover settings
		$test = array();
		$v->getCSSRules( true, $test );
		$expected = $this->defaultCss( 'test7' );
		$expected[] = '#test7 .cat-post-item {border-bottom: 1px solid #ccc;	list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
		$expected[] = '#test7 .cat-post-item:last-child {border-bottom: none;}';
		$expected[] = '#test7 .cat-post-thumbnail {float:left;}';
		$expected[] = '#test7 .cat-post img {padding-bottom: 0 !important; -webkit-transition: all 0.3s ease; -moz-transition: all 0.3s ease; -ms-transition: all 0.3s ease; -o-transition: all 0.3s ease; transition: all 0.3s ease;}';
		$expected[] = '#test7 .cat-post-blur img:hover {-webkit-filter: blur(2px); -moz-filter: blur(2px); -o-filter: blur(2px); -ms-filter: blur(2px); filter: blur(2px);}';
		$expected[] = '#test7 .cat-post-thumbnail a {box-shadow:none}'; // this for the thumb link
		$expected[] = '#test7 .cat-post-thumbnail a {border:0}'; // this for the thumb link
		$expected[] = '#test7 p {margin:5px 0 0 0}'; // since on bottom it will make the spacing on cover

		$this->assertEquals( $expected, $test );

		$v = new categoryPosts\virtualWidget(
			'test8', 'testclass', array()
		);

		function twentyseventeen_setup() {};

		// widget twenty seventeen
		$test = array();
		$v->getCSSRules( false, $test );
		$expected = $this->defaultCss( 'test8-internal' );
		$expected[] = '#test8-internal .cat-post-item {border-bottom: 1px solid #ccc;	list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
		$expected[] = '#test8-internal .cat-post-item:last-child {border-bottom: none;}';
		$expected[] = '#test8-internal .cat-post-thumbnail {float:left;}';

		$this->assertEquals( $expected, $test );

		// shortcode twenty seventeen
		$test = array();
		$v->getCSSRules( true, $test );
		$expected = $this->defaultCss( 'test8' );
		$expected[] = '#test8 .cat-post-item {list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
		$expected[] = '#test8 .cat-post-item {border-bottom: 1px solid #ccc;	list-style: none; list-style-type: none; margin: 3px 0;	padding: 3px 0;}';
		$expected[] = '#test8 .cat-post-item:last-child {border-bottom: none;}';
		$expected[] = '#test8 .cat-post-thumbnail {float:left;}';
		$expected[] = '#test8 .cat-post-thumbnail a {box-shadow:none}'; // this for the thumb link
		$expected[] = '#test8 .cat-post-thumbnail a {border:0}'; // this for the thumb link
		$expected[] = '#test8 p {margin:5px 0 0 0}'; // since on bottom it will make the spacing on cover
		$this->assertEquals( $expected, $test );

	}
}
