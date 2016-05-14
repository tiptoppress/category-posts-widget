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

        $sort_order = array('whatever', null);
        $sort_order_results = array('ASC', 'DESC');
        
        $cats = array('10', 7, null,'fail');
        $cats_results = array(10, 7, null,0);
        
        $nums = array('10', 7, null,'oops');
        $nums_results = array(10, 7, null,0);

        $hidethumbs = array(true,null);
        $hidethumbs_results = array(array(
					array(
					 'key' => '_thumbnail_id',
					 'compare' => 'EXISTS' )
				), null);
        
        $pid = $this->factory->post->create(array('title'=>'test','post_status'=>'publish')); 
        
        $exclude_current = array('whatever', null);

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