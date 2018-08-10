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
		jQuery('.cat-post-date[data-publishtime]').each(function ( ) {
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
	});
}
