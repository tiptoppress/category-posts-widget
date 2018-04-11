
if (typeof jQuery !== 'undefined')  {

	var cwp_namespace = window.cwp_namespace || {};
	cwp_namespace.fluid_images = window.cwp_namespace.fluid_images || {};
	cwp_namespace.fluid_images.crop = {

		Widgets : {},
		widget : null,

		Span : function (_self, _imageRatio) {

			this.self = _self;
			this.imageRatio = _imageRatio;
		},

		WidgetPosts : function (widget, ratio) {

			this.Spans = {};
			this.allSpans = widget.find( '.cat-post-crop' );
			this.firstSpan = this.allSpans.first();
			this.maxSpanWidth = this.firstSpan.width();
			this.firstListItem = this.firstSpan.closest( 'li' );
			this.ratio = ratio;

			for( var i = 0; i < this.allSpans.length; i++ ){
				var imageRatio = this.firstSpan.width() / jQuery(this.allSpans[i]).find( 'img' ).height();
				this.Spans[i] = new cwp_namespace.fluid_images.crop.Span( jQuery(this.allSpans[i]), imageRatio );
			}

			this.changeImageSize = function changeImageSize() {

				this.listItemWidth = this.firstListItem.width();
				this.SpanWidth = this.firstSpan.width();

				if(this.listItemWidth < this.SpanWidth ||			// if the layout-width have not enough space to show the regular source-width
					this.listItemWidth < this.maxSpanWidth) {		// defined start and stop working width for the image: Accomplish only the image width will be get smaller as the source-width
						this.allSpans.width( this.listItemWidth );
						var spanHeight = this.listItemWidth / this.ratio;
						this.allSpans.height( spanHeight );

						for( var index in this.Spans ){
							var imageHeight = this.listItemWidth / this.Spans[index].imageRatio;
							jQuery(this.Spans[index].self).find( 'img' ).css({
								height: imageHeight,
								marginTop: -(imageHeight - spanHeight) / 2
							});
						};
				}
			}
		},
	}
	
	jQuery.each(cwp_namespace.fluid_images.ratios, function(num, ratio) {		
		cwp_namespace.fluid_images.crop.widget = jQuery('#' + num);
		cwp_namespace.fluid_images.crop.Widgets[num] = new cwp_namespace.fluid_images.crop.WidgetPosts(cwp_namespace.fluid_images.crop.widget, ratio);
	});
		
	jQuery( document ).ready(function () {

		//do on page load or on resize the browser window
		jQuery(window).on('load resize', function() {
			for (var widget in cwp_namespace.fluid_images.crop.Widgets) {
				cwp_namespace.fluid_images.crop.Widgets[widget].changeImageSize();
			}
		});
	});
}