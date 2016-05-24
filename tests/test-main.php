<?php

define('NS','categoryPosts');

/**
 *  Normalize html for comparison by removing white space between tags, and leading/ending space
 *  
 *  @param [in] $string The html to normalize
 *  @return Normalized string
 */
function removeSpaceBetweenTags($string) {
    $string = preg_replace('~\s+~',' ',$string); // collapse spaces the way html handles it
    return trim(preg_replace('~>\s*<~','><',$string));
}

/**
 *  Filter function to test the widget_title filter behaviour. 
 *  Helps to check html escaping as a side job
 *  
 *  @param [in] $title The title as passed to the filter
 *  @return whatever constant string
 */
function titleFilterTest($title) {
    return 'Me > You';
}

/**
 *  Add a file as an attachment.
 *  
 *  @param string $filename The path of the file to add as an attachment
 *  @return int the ID of the new attachment
 */
function _make_attachment( $filename) {

    $contents = file_get_contents($filename);

    $upload = wp_upload_bits(basename($filename), null, $contents);
    $type = '';
    if ( !empty($upload['type']) ) {
        $type = $upload['type'];
    } else {
        $mime = wp_check_filetype( $upload['file'] );
        if ($mime)
            $type = $mime['type'];
    }

    $attachment = array(
        'post_title' => basename( $upload['file'] ),
        'post_content' => '',
        'post_type' => 'attachment',
        'post_mime_type' => $type,
        'guid' => $upload[ 'url' ],
    );


    // Save the data
    $id = wp_insert_attachment( $attachment, $upload[ 'file' ] );
    wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

    return $id;

}

class testWidgetFront extends WP_UnitTestCase {

    /**
     *  Check that there are no errors when instance is new
     */
    function testNoSetting() {
        $className = NS.'\Widget';
        $widget = new $className();
        ob_start();
        $widget->widget(array('before_widget'=>'',
                              'after_widget'=>'',
                              'before_title'=>'',
                              'after_title'=>'',
                              ),
                        array());
        $out = removeSpaceBetweenTags(ob_get_contents());
        ob_end_clean();
        $this->assertEquals('<ul></ul>',$out);
    }

    
    /**
     *  Test the titleHTML method of the widget
     */
    function testtitleHTML() {
        $className = NS.'\Widget';
        $widget = new $className();
        
        // test no setting, should return empty  striing
        $out = $widget->titleHTML('','',array());
        $this->assertEquals('',$out);
        
        // test simple title
        $out = $widget->titleHTML('<h3>','</h3>',array(
                                            'title'=>'test'
                                        ));
        $this->assertEquals('<h3>test</h3>',$out);

        // test simple title with html escape
        $out = $widget->titleHTML('<h3>','</h3>',array(
                                            'title'=>'te&st'
                                        ));
        $this->assertEquals('<h3>te&#038;st</h3>',$out);

        $out = $widget->titleHTML('<h3>','</h3>',array(
                                            'title'=>'te&st',
                                            'hide_title'=>false
                                        ));
        $this->assertEquals('<h3>te&#038;st</h3>',$out);

        // test hide title
        $out = $widget->titleHTML('<h3>','</h3>',array(
                                            'title'=>'test',
                                            'hide_title'=>true
                                        ));
        $this->assertEquals('',$out);
    
        // test title as category name when title empty
        $cid = $this->factory->category->create(array('name'=>'test cat'));
        
        $out = $widget->titleHTML('<h3>','</h3>',array(
                                            'cat'=>$cid
                                        ));
        $this->assertEquals('<h3>test cat</h3>',$out);
        
        // test title as category name when title is empty string
        
        $out = $widget->titleHTML('<h3>','</h3>',array(
                                            'test' => '',
                                            'cat'=>$cid
                                        ));
        $this->assertEquals('<h3>test cat</h3>',$out);
        
        $out = $widget->titleHTML('<h3>','</h3>',array(
                                            'test' => '',
                                            'hide_title'=>false,                                            
                                            'cat'=>$cid
                                        ));
        $this->assertEquals('<h3>test cat</h3>',$out);
        
        // test title as category name when title is empty string not displayed when tite is hidden       
        $out = $widget->titleHTML('<h3>','</h3>',array(
                                            'test' => '',
                                            'cat'=>$cid,
                                            'hide_title'=>true                                            
                                        ));
        $this->assertEquals('',$out);
        
        // empty title with non existing category
        $out = $widget->titleHTML('<h3>','</h3>',array(
                                            'test' => '',
                                            'cat'=>10000,
                                        ));
        $this->assertEquals('<h3></h3>',$out);
        
        // link to category with manual title
        $out = $widget->titleHTML('<h3>','</h3>',array(
                                            'test' => 'test',
                                            'cat'=>$cid,
                                            'title_link' => true
                                        ));
        $this->assertEquals('<h3><a href="http://example.org/?cat='.$cid.'">test cat</a></h3>',$out);
        
        // link to category with no manual title
        $out = $widget->titleHTML('<h3>','</h3>',array(
                                            'cat'=>$cid,
                                            'title_link' => true
                                        ));
        $this->assertEquals('<h3><a href="http://example.org/?cat='.$cid.'">test cat</a></h3>',$out);

        // no link when it is not set to be
        $out = $widget->titleHTML('<h3>','</h3>',array(
                                            'cat'=>$cid,
                                            'title_link' => false
                                        ));
        $this->assertEquals('<h3>test cat</h3>',$out);

        // link to not existing category
        $out = $widget->titleHTML('<h3>','</h3>',array(
                                            'title_link' => true
                                        ));
        $this->assertEquals('<h3></h3>',$out);
        
        // test widget_title filtering
        add_filter('widget_title','titleFilterTest');

        // widget_filte filter for link to category with no manual title
        $out = $widget->titleHTML('<h3>','</h3>',array(
                                            'cat'=>$cid,
                                            'title_link' => true
                                        ));
        $this->assertEquals('<h3><a href="http://example.org/?cat='.$cid.'">Me &gt; You</a></h3>',$out);
        
        // widget_filte filter fortitle without a link
        $out = $widget->titleHTML('<h3>','</h3>',array(
                                            'cat'=>$cid,
                                        ));
        $this->assertEquals('<h3>Me &gt; You</h3>',$out);
        
        remove_filter('widget_title','titleFilterTest');
        
    }
    
    /**
     *  Test the footerHTML method of the widget
     */
    function testfooterHTML() {
        $className = NS.'\Widget';
        $widget = new $className();

        // no options set
        $out = $widget->footerHTML(array());
        $this->assertEquals('',$out);

        // option set to not do it
        $out = $widget->footerHTML(array(
                                    'footer_link'=>false,
                                    ));
        $this->assertEquals('',$out);

        // empty category
        $out = $widget->footerHTML(array(
                                    'footer_link'=>true,
                                    ));
        $this->assertEquals('',$out);

        // bad category
        $out = $widget->footerHTML(array(
                                    'footer_link'=>true,
                                    'cat'=>1000
                                    ));
        $this->assertEquals('',$out);

        // bad category no css
        $out = $widget->footerHTML(array(
                                    'footer_link'=>true,
                                    'cat'=>1000,
                                    'disable_css' => true
                                    ));
        $this->assertEquals('',$out);

        // valid category
        $cid = $this->factory->category->create(array('name'=>'test cat'));
        
        $out = $widget->footerHTML(array(
                                    'footer_link'=>true,
                                    'cat'=>$cid
                                    ));
        $this->assertEquals('<a class="cat-post-footer-link" href="http://example.org/?cat='.$cid.'">1</a>',$out);
        
        // valid category explicit css
        $out = $widget->footerHTML(array(
                                    'footer_link'=>true,
                                    'disable_css' => false,
                                    'cat'=>$cid
                                    ));
        $this->assertEquals('<a class="cat-post-footer-link" href="http://example.org/?cat='.$cid.'">1</a>',$out);
        
        // valid category no css
        
        $out = $widget->footerHTML(array(
                                    'footer_link'=>true,
                                    'cat'=>$cid,
                                    'disable_css' => true
                                    ));
        $this->assertEquals('<a href="http://example.org/?cat='.$cid.'">1</a>',$out);

    }
    
    /**
     *  Test the excerpt_length_filter method of the widget
     */
    function testexcerpt_length_filter() {
        $className = NS.'\Widget';
        $widget = new $className();
        
        // no setting
        $widget->instance = array();
        $this->assertEquals(55,$widget->excerpt_length_filter(55));

        $widget->instance = array('excerpt_length'=>20);
        $this->assertEquals(20,$widget->excerpt_length_filter(55));
    }

    /**
     *  Test the excerpt_more_filter method of the widget
     */
    function testexcerpt_more_filter() {
        $className = NS.'\Widget';
        $widget = new $className();
        
        // generate a post to test with as the function expects to be called in a loop
        $pid = $this->factory->post->create(array('title'=>'test','post_status'=>'publish')); 

        global $post;
        $post = get_post($pid);
        setup_postdata($post);
        
        $widget->instance["excerpt_more_text"] = 'text"';
        $this->assertEquals(' <a class="cat-post-excerpt-more" href="http://example.org/?p='.$pid.'">text&quot;</a>',$widget->excerpt_more_filter(''));
    }
    
    /**
     *  Test the queryArgs method of the widget
     */
	function testqueryArgs() {
        $className = NS.'\Widget';
        $widget = new $className();
   
        // no settings, just have defaults
        $instance = array();
        $expected = array(
                        'orderby' => 'date',
                        'order' =>'DESC'
                        );
        $this->assertEquals($expected,$widget->queryArgs($instance));
        
        $sort_criteria = array(null,'date', 'title', 'comment_count', 'rand','garbage');
        $sort_criteria_results = array('date','date', 'title', 'comment_count', 'rand','date');

        $sort_order = array('whatever', true,null,false);
        $sort_order_results = array('ASC', 'ASC', 'DESC', 'DESC');
        
        $cats = array('10', 7, null,'fail');
        $cats_results = array(10, 7, null,0);
        
        $nums = array('10', 7, null,'oops');
        $nums_results = array(10, 7, null,0);

        $hidethumbs = array(true,null,false);
        $hidethumbs_results = array(array(
					array(
					 'key' => '_thumbnail_id',
					 'compare' => 'EXISTS' )
				), null,null);
        
        $pid = $this->factory->post->create(array('title'=>'test','post_status'=>'publish')); 
        
        $exclude_current = array('whatever',true, null,false);

        foreach ($sort_criteria as $ksc=>$sc) 
            foreach ($sort_order as $kso => $so) 
                foreach ($cats as $kcat => $cat) 
                    foreach ($nums as $knum => $num) 
                        foreach ($hidethumbs as $kt => $thumb) 
                            foreach ($exclude_current as $ke => $exclude) {
                                $instance = array(
                                    'sort_by' => $sc,
                                    'asc_sort_order' => $so,
                                    'cat' => $cat,
                                    'hideNoThumb' => $thumb,
                                    'exclude_current_post' => $exclude,
                                    'num' => $num
                                );
                                $expected = array(
                                            'orderby' => $sort_criteria_results[$ksc],
                                            'order' => $sort_order_results[$kso]
                                            );
                                if ($cat)
                                    $expected['cat'] = $cats_results[$kcat];
                                    
                                if ($num)
                                    $expected['showposts'] = $nums_results[$knum];
                                    
                                if ($thumb)
                                    $expected['meta_query'] = $hidethumbs_results[$kt];
                                    
                                // test archive type of page
                                $this->go_to('/');
                                $this->assertEquals($expected,$widget->queryArgs($instance));
                                
                                // test single post page
                                if ($exclude)
                                    $expected['post__not_in'] = $pid;
                                $this->go_to('/?p='.$pid);
                                $this->assertEquals($expected,$widget->queryArgs($instance));
                            }
                    
    }
    
    /**
     *  Test the post_thumbnail method of the widget
     */
    function test_the_post_thumbnail() {
        $className = NS.'\Widget';
        $widget = new $className();
        
        // clean upload dir for consistant file names
        $dir = wp_upload_dir();
        $dir = $dir['path'];
        array_map('unlink', glob($dir."/*"));		
        
		// 1) use image size: 640x480
        $pid = $this->factory->post->create(array('title'=>'canola','post_status'=>'publish')); 
        $thumbnail_id = _make_attachment(DIR_TESTDATA . '/images/canola.jpg'); // wp-content\plugins\4.5\tests\phpunit\includes\..\data\images\canola.jpg
        set_post_thumbnail( $pid, $thumbnail_id);
        
        global $post;
        $post = get_post($pid);
        setup_postdata($post);

        // test no thumb width and height, shoud get same html
        $this->assertEquals('',$widget->the_post_thumbnail(array()));
        
        $this->assertEquals('',$widget->the_post_thumbnail(array(10,'')));

        $this->assertEquals('',$widget->the_post_thumbnail(array('',10)));
        
        // equal to min thumb size. no manipulation needed
        $widget->instance=array('thumb_h' => 150,'thumb_w' => 150);
		// MODIF for older WP <4.5: class size-WxH is supported up +4.5
        $this->assertEquals('<img width="150" height="150" src="http://example.org/wp-content/uploads/2016/05/canola-150x150.jpg" class="attachment-150x150 wp-post-image" alt="canola.jpg" />',
								str_replace(" size-150x150", "", $widget->the_post_thumbnail(array(150,150)))
							);

        $widget->instance=array('thumb_h' => 200,'thumb_w' => 200);
        // MODIF for older WP <4.5: class size-WxH is supported up +4.5
		$this->assertEquals('<img width="200" height="150" src="http://example.org/wp-content/uploads/2016/05/canola-300x225.jpg" class="attachment-200x200 wp-post-image" alt="canola.jpg" srcset="http://example.org/wp-content/uploads/2016/05/canola-300x225.jpg 300w, http://example.org/wp-content/uploads/2016/05/canola.jpg 640w" sizes="(max-width: 200px) 100vw, 200px" />',
								str_replace(" size-200x200", "", $widget->the_post_thumbnail(array(200,200)))
							);

		// Use with "use_css_cropping"
        $widget->instance=array('thumb_h' => 150,'thumb_w' => 150,'use_css_cropping' => true);
        // MODIF for older WP <4.5: class size-WxH is supported up +4.5
		$this->assertEquals('<img width="150" height="150" src="http://example.org/wp-content/uploads/2016/05/canola-150x150.jpg" class="attachment-150x150 wp-post-image" alt="canola.jpg" />',
								str_replace(" size-150x150", "", $widget->the_post_thumbnail(array(150,150)))
							);
		
        $widget->instance=array('thumb_h' => 200,'thumb_w' => 200,'use_css_cropping' => true);
        // MODIF for older WP <4.5: class size-WxH is supported up +4.5
		$this->assertEquals('<span style="width:200px;height:200px;"><img style="margin-left:-33.333333333333px;height:200px;clip:rect(auto,233.33333333333px,auto,33.333333333333px);width:auto;max-width:initial;" width=\'266.66666666667\' height=\'200\' src="http://example.org/wp-content/uploads/2016/05/canola-300x225.jpg" class="attachment-200x200 wp-post-image" alt="canola.jpg" srcset="http://example.org/wp-content/uploads/2016/05/canola-300x225.jpg 300w, http://example.org/wp-content/uploads/2016/05/canola.jpg 640w" sizes="(max-width: 266.66666666667px) 100vw, 266.66666666667px" /></span>',
								str_replace(" size-200x200", "", $widget->the_post_thumbnail(array(200,200)))
							);

		// 2.) use smaller image as media -> settings thumbnail_size, image size: 50x50
        delete_post_thumbnail( $pid );
		$pid = $this->factory->post->create(array('title'=>'test-image','post_status'=>'publish')); 
        $thumbnail_id = _make_attachment(DIR_TESTDATA . '/images/test-image.jpg'); // wp-content\plugins\4.5\tests\phpunit\includes\..\data\images\test-image.jpg
        set_post_thumbnail( $pid, $thumbnail_id);
		
        $post = get_post($pid);
        setup_postdata($post);

		$widget->instance=array('use_css_cropping' => false);
		
        $widget->instance=array('thumb_h' => 150,'thumb_w' => 150);
        // MODIF for older WP <4.5: class size-WxH is supported up +4.5
		$this->assertEquals('<img width="50" height="50" src="http://example.org/wp-content/uploads/2016/05/test-image.jpg" class="attachment-150x150 wp-post-image" alt="test-image.jpg" />',
								str_replace(" size-150x150", "", $widget->the_post_thumbnail(array(150,150)))
							);

        $widget->instance=array('thumb_h' => 200,'thumb_w' => 200);
        // MODIF for older WP <4.5: class size-WxH is supported up +4.5
		$this->assertEquals('<img width="50" height="50" src="http://example.org/wp-content/uploads/2016/05/test-image.jpg" class="attachment-200x200 wp-post-image" alt="test-image.jpg" />',
								str_replace(" size-200x200", "", $widget->the_post_thumbnail(array(200,200)))
							);

		// Use with "use_css_cropping"
        $widget->instance=array('thumb_h' => 150,'thumb_w' => 150,'use_css_cropping' => true);
        // MODIF for older WP <4.5: class size-WxH is supported up +4.5
		$this->assertEquals('<img width="50" height="50" src="http://example.org/wp-content/uploads/2016/05/test-image.jpg" class="attachment-150x150 wp-post-image" alt="test-image.jpg" />',
								str_replace(" size-150x150", "", $widget->the_post_thumbnail(array(150,150)))
							);

        $widget->instance=array('thumb_h' => 200,'thumb_w' => 200,'use_css_cropping' => true);
        // MODIF for older WP <4.5: class size-WxH is supported up +4.5
		$this->assertEquals('<img width="50" height="50" src="http://example.org/wp-content/uploads/2016/05/test-image.jpg" class="attachment-200x200 wp-post-image" alt="test-image.jpg" />',
								str_replace(" size-200x200", "", $widget->the_post_thumbnail(array(200,200)))
							);

		// 3.) use bigger image as media -> settings large_size, image size: 1920x1080
        delete_post_thumbnail( $pid );
		$pid = $this->factory->post->create(array('title'=>'33772','post_status'=>'publish')); 
        $thumbnail_id = _make_attachment(DIR_TESTDATA . '/images/33772.jpg'); // wp-content\plugins\4.5\tests\phpunit\includes\..\data\images\33772.jpg
        set_post_thumbnail( $pid, $thumbnail_id);
		
        $post = get_post($pid);
        setup_postdata($post);		

		$widget->instance=array('use_css_cropping' => false);
		
        $widget->instance=array('thumb_h' => 150,'thumb_w' => 150);
        // MODIF for older WP <4.5: class size-WxH is supported up +4.5
		$this->assertEquals('<img width="150" height="150" src="http://example.org/wp-content/uploads/2016/05/33772-150x150.jpg" class="attachment-150x150 wp-post-image" alt="33772.jpg" />',
								str_replace(" size-150x150", "", $widget->the_post_thumbnail(array(150,150)))
							);

        $widget->instance=array('thumb_h' => 200,'thumb_w' => 200);
        // MODIF for older WP <4.5: class size-WxH is supported up +4.5
		$this->assertEquals('<img width="200" height="113" src="http://example.org/wp-content/uploads/2016/05/33772-768x432.jpg" class="attachment-200x200 wp-post-image" alt="33772.jpg" srcset="http://example.org/wp-content/uploads/2016/05/33772-768x432.jpg 768w, http://example.org/wp-content/uploads/2016/05/33772-300x169.jpg 300w, http://example.org/wp-content/uploads/2016/05/33772-1024x576.jpg 1024w" sizes="(max-width: 200px) 100vw, 200px" />',
								str_replace(" size-200x200", "", $widget->the_post_thumbnail(array(200,200)))
							);
		
		// Use with "use_css_cropping"
        $widget->instance=array('thumb_h' => 150,'thumb_w' => 150,'use_css_cropping' => true);
        // MODIF for older WP <4.5: class size-WxH is supported up +4.5
		$this->assertEquals('<img width="150" height="150" src="http://example.org/wp-content/uploads/2016/05/33772-150x150.jpg" class="attachment-150x150 wp-post-image" alt="33772.jpg" />',
								str_replace(" size-150x150", "", $widget->the_post_thumbnail(array(150,150)))
							);

        $widget->instance=array('thumb_h' => 200,'thumb_w' => 200,'use_css_cropping' => true);
        // MODIF for older WP <4.5: class size-WxH is supported up +4.5
		$this->assertEquals('<span style="width:200px;height:200px;"><img style="margin-left:-77.777777777778px;height:200px;clip:rect(auto,277.77777777778px,auto,77.777777777778px);width:auto;max-width:initial;" width=\'355.55555555556\' height=\'200\' src="http://example.org/wp-content/uploads/2016/05/33772-768x432.jpg" class="attachment-200x200 wp-post-image" alt="33772.jpg" srcset="http://example.org/wp-content/uploads/2016/05/33772-768x432.jpg 768w, http://example.org/wp-content/uploads/2016/05/33772-300x169.jpg 300w, http://example.org/wp-content/uploads/2016/05/33772-1024x576.jpg 1024w" sizes="(max-width: 355.55555555556px) 100vw, 355.55555555556px" /></span>',
								str_replace(" size-200x200", "", $widget->the_post_thumbnail(array(200,200)))
							);
    }	
}

class testWidgetAdmin extends WP_UnitTestCase {

    function testformTitlePanel() {
        $className = NS.'\Widget';
        $widget = new $className();
        
        // no setting
        ob_start();
        $widget->formTitlePanel(array());
        $out = removeSpaceBetweenTags(ob_get_contents());
        ob_end_clean();
        $this->assertEquals('<h4 data-panel="title">Title</h4><div><p><label for="widget-category-posts--title">'.
                    ' Title: '.
                    '<input class="widefat" style="width:80%;" id="widget-category-posts--title" name="widget-category-posts[][title]" type="text" value="" /></label></p><p><label for="widget-category-posts--title_link"><input type="checkbox" class="checkbox" id="widget-category-posts--title_link" name="widget-category-posts[][title_link]" />'.
                    ' Make widget title link </label></p><p><label for="widget-category-posts--hide_title"><input type="checkbox" class="checkbox" id="widget-category-posts--hide_title" name="widget-category-posts[][hide_title]" />'.
                    ' Hide title </label></p></div>',$out);    
                    
        // title
        ob_start();
        $widget->formTitlePanel(array('title' => 'title <> me'));
        $out = removeSpaceBetweenTags(ob_get_contents());
        ob_end_clean();
        $this->assertEquals('<h4 data-panel="title">Title</h4><div><p><label for="widget-category-posts--title">'.
                    ' Title: '.
                    '<input class="widefat" style="width:80%;" id="widget-category-posts--title" name="widget-category-posts[][title]" type="text" value="title &lt;&gt; me" />'.
                    '</label></p><p><label for="widget-category-posts--title_link"><input type="checkbox" class="checkbox" id="widget-category-posts--title_link" name="widget-category-posts[][title_link]" />'.
                    ' Make widget title link </label></p><p><label for="widget-category-posts--hide_title"><input type="checkbox" class="checkbox" id="widget-category-posts--hide_title" name="widget-category-posts[][hide_title]" />'.
                    ' Hide title </label></p></div>',$out);    
                   
        // title and link
        ob_start();
        $widget->formTitlePanel(array(
                                    'title' => 'title <> me',
                                    'title_link' => true
                                    ));
        $out = removeSpaceBetweenTags(ob_get_contents());
        ob_end_clean();
        $this->assertEquals('<h4 data-panel="title">Title</h4><div><p><label for="widget-category-posts--title">'.
                    ' Title: '.
                    '<input class="widefat" style="width:80%;" id="widget-category-posts--title" name="widget-category-posts[][title]" type="text" value="title &lt;&gt; me" />'.
                    '</label></p>'.
                    '<p><label for="widget-category-posts--title_link"><input type="checkbox" class="checkbox" id="widget-category-posts--title_link" name="widget-category-posts[][title_link]" checked=\'checked\' />'.
                    ' Make widget title link </label></p><p><label for="widget-category-posts--hide_title"><input type="checkbox" class="checkbox" id="widget-category-posts--hide_title" name="widget-category-posts[][hide_title]" />'.
                    ' Hide title </label></p></div>',$out);    

        // no title just link
        ob_start();
        $widget->formTitlePanel(array(
                                    'title_link' => true
                                    ));
        $out = removeSpaceBetweenTags(ob_get_contents());
        ob_end_clean();
        $this->assertEquals('<h4 data-panel="title">Title</h4><div><p><label for="widget-category-posts--title">'.
                    ' Title: '.
                    '<input class="widefat" style="width:80%;" id="widget-category-posts--title" name="widget-category-posts[][title]" type="text" value="" />'.
                    '</label></p>'.
                    '<p><label for="widget-category-posts--title_link"><input type="checkbox" class="checkbox" id="widget-category-posts--title_link" name="widget-category-posts[][title_link]" checked=\'checked\' />'.
                    ' Make widget title link </label></p><p><label for="widget-category-posts--hide_title"><input type="checkbox" class="checkbox" id="widget-category-posts--hide_title" name="widget-category-posts[][hide_title]" />'.
                    ' Hide title </label></p></div>',$out);    

        // no title just link
        ob_start();
        $widget->formTitlePanel(array(
                                    'hide_title' => true
                                    ));
        $out = removeSpaceBetweenTags(ob_get_contents());
        ob_end_clean();
        $this->assertEquals('<h4 data-panel="title">Title</h4><div><p><label for="widget-category-posts--title">'.
                    ' Title: '.
                    '<input class="widefat" style="width:80%;" id="widget-category-posts--title" name="widget-category-posts[][title]" type="text" value="" />'.
                    '</label></p>'.
                    '<p><label for="widget-category-posts--title_link"><input type="checkbox" class="checkbox" id="widget-category-posts--title_link" name="widget-category-posts[][title_link]" />'.
                    ' Make widget title link </label></p><p><label for="widget-category-posts--hide_title"><input type="checkbox" class="checkbox" id="widget-category-posts--hide_title" name="widget-category-posts[][hide_title]" checked=\'checked\' />'.
                    ' Hide title </label></p></div>',$out);    
    }
}