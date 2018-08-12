/**
 * Category Posts Widget
 * https://github.com/tiptoppress/category-posts-widget
 *
 * JS for the localized date functionality.
 *
 * Released under the GPLv2 license or later -  http://www.gnu.org/licenses/gpl-2.0.html
 */

if (typeof jQuery !== 'undefined')  {

	jQuery( document ).ready(function () {
		var $elements = jQuery('.cat-post-item .cat-post-date[data-publishtime]');
		if ( 0 !== $elements) {
			/**
			 * Adjust the dates for the items indicated in the $elements
			 * array.
			 *
			 * @param array $elements array of dom elements.
			 */
			var adjustlocalizeddate = function ( $elements ) {
				$elements.each(function ( ) {
					var $this = jQuery( this );
					var time = $this.data( 'publishtime' ) * 1000; // new Date() requires time in ms.
					var format = $this.data( 'format' );
					var orig_date = new Date( time );
					switch ( format ) {
						case 'date' :
							$this.text( orig_date.toLocaleDateString() );
							break;
						case 'time' :
							$this.text( orig_date.toLocaleDateString() + ' '
							            + orig_date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) // Eliminate seconds.
									 );
							break;
					}
				});
			}

			adjustlocalizeddate( $elements );

			// Wait for catposts.load_more event that load more triggers when for
			// mewly added item, and localize the date if needed.
			jQuery( 'ul' ).on('catposts.load_more', '.cat-post-item', function () {
				adjustlocalizeddate( jQuery( this ).find( '.cat-post-date[data-publishtime]' ) );
			});
		}
	});
}
