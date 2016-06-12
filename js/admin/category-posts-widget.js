/**
 * Category Posts Widget
 * http://mkrdip.me/category-posts-widget
 *
 * Adds a widget that shows the most recent posts from a single category.
 *
 * Released under the GPLv2 license or later -  http://www.gnu.org/licenses/gpl-2.0.html
 */

 
jQuery(document).ready( function () {

    // namespace 
    
    var cwp_namespace = {

        open_panels : {},  // holds an array of open panels per wiget id
        
        // generic click handler on the panel title
        clickHandler: function(element) {
            // open the div "below" the h4 title
            jQuery(element).toggleClass('open')
                        .next().stop().slideToggle();
            // mark the change of state in the open panels array
            var panel = element.getAttribute('data-panel');
            var id = jQuery(element).parent().parent().parent().parent().parent().attr('id');
            var o = {};
            if (this.open_panels.hasOwnProperty(id))
                o = this.open_panels[id];
            if (o.hasOwnProperty(panel))
                delete o[panel];
            else 
                o[panel] = true;
            this.open_panels[id] = o;
        }
    }

	jQuery('.category-widget-cont h4').click(function () { // for widgets page
        cwp_namespace.clickHandler(this);
	});
		
	// needed to reassign click handlers after widget refresh
	jQuery(document).on('widget-added widget-updated', function(root,element){ // for customize and after save on widgets page
		jQuery('.category-widget-cont h4').off('click').on('click', function () {	
            cwp_namespace.clickHandler(this);
		})
        // refresh panels to state before the refresh
        var id = jQuery(element).attr('id');
        if (cwp_namespace.open_panels.hasOwnProperty(id)) {
            var o = cwp_namespace.open_panels[id];
            for (var panel in o) {
                jQuery(element).find('[data-panel='+panel+']').toggleClass('open')
					.next().stop().show();
            }
        }
	});	
});
