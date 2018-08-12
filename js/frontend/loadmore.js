/**
 * Category Posts Widget
 * https://github.com/tiptoppress/category-posts-widget
 *
 * JS for the "load more" functionality.
 *
 * Released under the GPLv2 license or later -  http://www.gnu.org/licenses/gpl-2.0.html
 */

if (typeof jQuery !== 'undefined')  {

	var php_settings_var = 'categoryPosts'; // should be identical to namespace.

	jQuery( document ).ready(function () {

		// Handle the click of load more.
		jQuery(document).on('click', '.' + php_settings_var + '-loadmore button', function() {
			var $this = jQuery(this);
			var id = $this.data( 'id' );
			var number = $this.data( 'number' );
			var start = $this.data( 'start' );
			var context = $this.data( 'context' );
			var url = tiptoppress[php_settings_var].json_root_url;
			var $ul = jQuery(this.parentElement.parentElement).find('ul'); // The UL of the widget.
			var orig_text = $this.text();
			var loading_text = $this.data( 'loading' );

			// Change the button text to indicate loading.
			$this.text( loading_text );
			// Get the data from the server
			jQuery.getJSON(url + '/' + id + '/' + start + '/' + number + '/' + context + '/', function ( data ) {
				// appened the returned data to the UL in the returned order.
				jQuery.each(data, function (key, li) {
					$ul.append(li);
					// apend returns the $ul, therefor we need to actualy find
					// the newly added item.
					$ul.children().last().trigger('catposts.load_more');
				});
				if (data.length != number) {
					$this.hide();
				} else {
					$this.data( 'start', start+number );
				}
			}).always( function () {
				// Revert to original text.
				$this.text( orig_text );
			});
		});
	});
}
