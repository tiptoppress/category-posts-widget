/**
 * Category Posts Widget
 * http://mkrdip.me/category-posts-widget
 *
 * Adds a widget that shows the most recent posts from a single category.
 *
 * Released under the GPLv2 license or later -  http://www.gnu.org/licenses/gpl-2.0.html
 */
 
if (window.jQuery) {

	jQuery('.category-widget-cont h4').click(function () { // for widgets page
		jQuery(this).toggleClass('open')
					.next().stop().slideToggle('open');
	})
		
		
	jQuery(document).on('widget-added widget-updated', function(){ // for customize and after save on widgets page
		jQuery('.category-widget-cont h4').off('click').on('click', function () {	
			jQuery(this).toggleClass('open')
						.next().stop().slideToggle('open');
		})
	});	
}
