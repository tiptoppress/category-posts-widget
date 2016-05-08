<?php

define('NS','categoryPosts');

/**
 *  Normalize html for comparison by removing white space between tags, and leading/ending space
 *  
 *  @param [in] $string The html to normalize
 *  @return Normalized string
 */
function removeSpaceBetweenTags($string) {
    return trim(preg_replace('~>\s*\n\s*<~','><',$string));
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
    }
}