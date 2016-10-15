(function($) {
    tinymce.create("tinymce.plugins.categoryPosts", {

        //url argument holds the absolute url of our plugin directory
        init : function(ed, url) {

            //add new button    
            ed.addButton("green", {
                title : "Insert Category Posts shortcode",
                cmd : "categoryPosts_shortcode",
                text : "C"
            });

            //button functionality.
            ed.addCommand("categoryPosts_shortcode", function() {
				ed.windowManager.open(  
					//  Properties of the window.
					{
						title: "Category Posts Insert Shortcode",   //    The title of the dialog window.
						body: [{
							type: 'textbox',
							name: 'title',
							label: 'Name'
						}],
						onsubmit: function( e ) {
							//editor.insertContent( '&lt;h3&gt;' + e.data.title + '&lt;/h3&gt;');
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