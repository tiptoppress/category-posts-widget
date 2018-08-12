/**
 * Category Posts Widget
 * https://github.com/tiptoppress/category-posts-widget
 *
 * Adds a widget that shows the most recent posts from a single category.
 *
 * Released under the GPLv2 license or later -  http://www.gnu.org/licenses/gpl-2.0.html
 */

if (typeof jQuery !== 'undefined')  {

	// var cwp_namespace = window.cwp_namespace || {};

	// /**
	//  * Object handling responsive images.
	//  */
	// cwp_namespace.fluid_images = {



	// 	Widgets : {},
	// 	widget : null,

	// 	Span : function (_self, _imageRatio) {

	// 		this.self = _self;
	// 		this.imageRatio = _imageRatio;
	// 	},

	// 	WidgetPosts : function (widget, ratio) {

	// 		this.Spans = {};
	// 		this.allSpans = widget.find( '.cat-post-crop' );
	// 		this.firstSpan = this.allSpans.first();
	// 		this.maxSpanWidth = this.firstSpan.width();
	// 		this.firstListItem = this.firstSpan.closest( 'li' );
	// 		this.ratio = ratio;

	// 		for( var i = 0; i < this.allSpans.length; i++ ){
	// 			var imageRatio = this.firstSpan.width() / jQuery(this.allSpans[i]).find( 'img' ).height();
	// 			this.Spans[i] = new cwp_namespace.fluid_images.Span( jQuery(this.allSpans[i]), imageRatio );
	// 		}

	// 		this.changeImageSize = function changeImageSize() {

	// 			this.listItemWidth = this.firstListItem.width();
	// 			this.SpanWidth = this.firstSpan.width();

	// 			if(this.listItemWidth < this.SpanWidth ||			// if the layout-width have not enough space to show the regular source-width
	// 				this.listItemWidth < this.maxSpanWidth) {		// defined start and stop working width for the image: Accomplish only the image width will be get smaller as the source-width
	// 					this.allSpans.width( this.listItemWidth );
	// 					var spanHeight = this.listItemWidth / this.ratio;
	// 					this.allSpans.height( spanHeight );

	// 					for( var index in this.Spans ){
	// 						var imageHeight = this.listItemWidth / this.Spans[index].imageRatio;
	// 						jQuery(this.Spans[index].self).find( 'img' ).css({
	// 							height: imageHeight,
	// 							marginTop: -(imageHeight - spanHeight) / 2
	// 						});
	// 					};
	// 			}
	// 		}
	// 	},
	// }

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
