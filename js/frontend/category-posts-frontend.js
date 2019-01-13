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
	});
}
