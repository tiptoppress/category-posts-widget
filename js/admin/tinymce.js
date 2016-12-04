(function($) {
	var namespace = 'categoryPosts';
	var textdomain = 'category-posts';
	
    tinymce.create("tinymce.plugins."+namespace, {
		
        //url argument holds the absolute url of our plugin directory
        init : function(ed, url) {
            
			//add new button    
            ed.addButton(namespace, {
                title : ed.getLang(textdomain+'.tooltip'),
                cmd : "categoryPosts_shortcode",
                text : "+[CP]",
            });

            //button functionality.
            ed.addCommand("categoryPosts_shortcode", function() {
				ed.windowManager.open(  
					//  Properties of the window.
					{
						title: ed.getLang(textdomain+'.title'),
						body: [
								{
									type: 'textbox',
									name: 'title',
									label: ed.getLang(textdomain+'.name'),
								},
								{
									type:'container',
									html:'<a style="color:blue;textdecoration:underline;cursor:pointer" href="'+ed.getLang(textdomain+'.profiile_url')+'">'+ed.getLang(textdomain+'.hide_message')+'</a>',
								}
						],
						onsubmit: function( e ) {
							var shortcode = '[catposts';
							if ( e.data.title != "" ) {
								shortcode += ' name="' + e.data.title + '"';
							}
							shortcode += ']';
							ed.selection.setContent(shortcode);
						}
					}

				);
            });

        },

        createControl : function(n, cm) {
            return null;
        },

        getInfo : function() {
            return {
                longname : "Insert category post shortcode",
                author : "TipTopPress",
                version : "4.7"
            };
        }
    });

    tinymce.PluginManager.add("categoryPosts", tinymce.plugins.categoryPosts);
})(jQuery);