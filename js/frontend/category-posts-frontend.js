/**
 * Category Posts Widget
 * https://github.com/tiptoppress/category-posts-widget
 *
 * Adds a widget that shows the most recent posts from a single category.
 *
 * Released under the GPLv2 license or later -  http://www.gnu.org/licenses/gpl-2.0.html
 */

if (typeof jQuery !== 'undefined')  {
	jQuery( document ).ready(function () {
		if ('objectFit' in document.documentElement.style === false) {
			jQuery('.cat-post-item figure').removeClass('cat-post-crop');
			jQuery('.cat-post-item figure').addClass('cat-post-crop-not-supported');
		}
		if (document.documentMode || /Edge/.test(navigator.userAgent)) {
			jQuery('.cat-post-item figure img').height('+=1');
			window.setTimeout(function(){
				jQuery('.cat-post-item figure img').height('-=1');
			},0);
		}

		// var uid =1;
		// // get all widgets as object
		// jQuery.each( jQuery('[data-cpw-image-ratio]'), function( indes ) {
		// 	var num = uid++,
		// 		ratio = jQuery(this).data("cpw-image-ratio");
		// 	cwp_namespace.fluid_images.widget = jQuery(this);
		// 	cwp_namespace.fluid_images.Widgets[num] = new cwp_namespace.fluid_images.WidgetPosts(cwp_namespace.fluid_images.widget, ratio);
		// });

		// // crop on page load or on resize the browser window
		// jQuery(window).on('load resize', function() {
		// 	for (var widget in cwp_namespace.fluid_images.Widgets) {
		// 		cwp_namespace.fluid_images.Widgets[widget].changeImageSize();
		// 	}
		// });
	});
}
