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
            var panel = jQuery(element).data('panel');
            var id = jQuery(element).parent().parent().parent().parent().parent().attr('id');
            var o = {};
            if (this.open_panels.hasOwnProperty(id))
                o = this.open_panels[id];
            if (o.hasOwnProperty(panel))
                delete o[panel];
            else 
                o[panel] = true;
            this.open_panels[id] = o;
        },
		
		// Close all open panels if open
		autoCloseOpenPanels: function(_this) {
			if( categoryPosts.accordion  ) {
				if(!jQuery(_this).hasClass('open')) {
					var jCloseElement = jQuery(_this).parent().find('.open');
					this.clickHandler(jCloseElement);
				}
			}
		},
		
		defaultThumbnailSelection: function (elem, title, button_title) {

			var frame = wp.media({
				title : title,
				multiple : false,
				library : { type : 'image' },
				button : { text : button_title },
			});

			// Handle results from media manager.
			frame.on('close',function( ) {
				var attachments = frame.state().get('selection').toJSON();
				if (attachments.length == 1) {
					var attachment = attachments[0];
					var img_html = '<img src="' + attachment.url + '" ';
					img_html += 'width="60" ';
					img_html += 'height="60" ';
					img_html += '/>';
					jQuery(elem).parent().find('.default_thumb_img').html(img_html);
					jQuery(elem).parent().find('.cwp_default_thumb_remove').show();
					jQuery(elem).parent().find('.default_thumb_id').val(attachment.id).change();
				}
			});

			frame.open();
			return false;
		},
		
		removeDefaultThumbnailSelection : function (elem) {
			jQuery(elem).parent().find('.default_thumb_img').html(cwp_default_thumb_selection.none);
			jQuery(elem).hide();
			jQuery(elem).parent().find('.default_thumb_id').val(0).change();

			return false;
		},
			
    }
	
	jQuery('.category-widget-cont h4').click(function () { // for widgets page
		cwp_namespace.autoCloseOpenPanels(this);
		// toggle panel open/close
        cwp_namespace.clickHandler(this);
	});

	// needed to reassign click handlers after widget refresh
	jQuery(document).on('widget-added widget-updated', function(root,element){ // for customize and after save on widgets page
		jQuery('.category-widget-cont h4').off('click').on('click', function () {
			cwp_namespace.autoCloseOpenPanels(this);
			// toggle panel open/close
            cwp_namespace.clickHandler(this);
		});
    	jQuery('.cwp_default_thumb_select').off('click').on('click', function () { // select default thumb
			cwp_namespace.defaultThumbnailSelection(this, cwp_default_thumb_selection.frame_title,cwp_default_thumb_selection.button_title);
		});

		jQuery('.cwp_default_thumb_remove').off('click').on('click', function () { // remove default thumb
			cwp_namespace.removeDefaultThumbnailSelection(this);
		});
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

	jQuery('.cwp_default_thumb_select').off('click').on('click', function () { // select default thumb
		cwp_namespace.defaultThumbnailSelection(this, cwp_default_thumb_selection.frame_title,cwp_default_thumb_selection.button_title);
	});

	jQuery('.cwp_default_thumb_remove').off('click').on('click', function () { // remove default thumb
		cwp_namespace.removeDefaultThumbnailSelection(this);
	});
	
});

