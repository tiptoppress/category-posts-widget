(function($) {
    tinymce.create("tinymce.plugins.categoryPosts", {
		
        //url argument holds the absolute url of our plugin directory
        init : function(ed, url) {

			var textdomain = 'categoryposts';
            
			//add new button    
            ed.addButton("green", {
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
						body: [{
							type: 'textbox',
							name: 'title',
							label: ed.getLang(textdomain+'.name'),
						}],
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