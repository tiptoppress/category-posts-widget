/**
 * Category Posts Widget
 * http://mkrdip.me/category-posts-widget
 *
 * Adds a widget that shows the most recent posts from a single category.
 *
 * Released under the GPLv2 license or later -  http://www.gnu.org/licenses/gpl-2.0.html
 */

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

        // Show hide excerpt options on excerpt option check box change
        toggleExcerptPanel: function(item) {
            var value = jQuery(item).find("input").attr('checked');		
            var panel = item.parentElement.parentElement;
            var layout = jQuery(panel).find(".layout_select option:selected").attr('value');
            if(value == 'checked') {
                jQuery(panel).find('.categoryposts-data-panel-excerpt').show();
            }
            else {
                jQuery(panel).find('.categoryposts-data-panel-excerpt').hide();
            }	
        },
		
        // Show hide date options on date option check box change
        toggleDatePanel: function(item) {
            var value = jQuery(item).find("input").attr('checked');		
            var panel = item.parentElement.parentElement;
            var layout = jQuery(panel).find(".layout_select option:selected").attr('value');
            if(value == 'checked') {
                jQuery(panel).find('.categoryposts-data-panel-date').show();
            }
            else {
                jQuery(panel).find('.categoryposts-data-panel-date').hide();
            }	
        },
		
		// Show hide foote link URL
		toggleCatSelection: function(item) {
            var cat = jQuery(item).find("option:selected").attr('value');
			var panel = item.parentElement.parentElement.parentElement.parentElement;
            if(cat == '0') {
				jQuery(panel).find('.categoryPosts-title_link').hide();
				jQuery(panel).find('.categoryPosts-title_link_url').show();
            }
            else {
				jQuery(panel).find('.categoryPosts-title_link').show();
				jQuery(panel).find('.categoryPosts-title_link_url').hide();
            }	
        },

		// Show hide disable font styles
		toggleDisableFontStyles: function(item) {
            var value = jQuery(item).find("input").attr('checked');
			var panel = item.parentElement.parentElement;
            if(value == 'checked') {
                jQuery(panel).find('.categoryposts-data-panel-general-disable-font-styles').hide();
            }
            else {
                jQuery(panel).find('.categoryposts-data-panel-general-disable-font-styles').show();
            }	
        },
			
		// Show hide other date format
		toggleDateFormat: function(item) {
            var value = jQuery(item).val();
			var panel = item.parentElement.parentElement;
            if( value != 'other') {
                jQuery(panel).find('.categoryposts-data-panel-date-other-format').hide();
            }
            else {
                jQuery(panel).find('.categoryposts-data-panel-date-other-format').show();
            }	
        },
			
		// Show template help
		openTemplateHelp: function(item,event) {
			event.preventDefault();
			var panel = item.parentElement.parentElement.parentElement;
            jQuery(panel).find('.cat-post-template-help').show('slow');
        },
			
		toggleAssignedCategoriesTop: function(item) {
            var value = jQuery(item).find("input").attr('checked');
			var panel = item.parentElement.parentElement;
            if(value == 'checked') {
                jQuery(panel).find('.categoryposts-details-panel-assigned-cat-top').show();
            }
            else {
                jQuery(panel).find('.categoryposts-details-panel-assigned-cat-top').hide();
            }	
        },
		
		toggleHideTitle: function(item) {
            var value = jQuery(item).find("input").attr('checked');
			var panel = item.parentElement.parentElement;
            if (value != 'checked') {
                jQuery(panel).find('.categoryposts-data-panel-title-settings').show();
            }
            else {
                jQuery(panel).find('.categoryposts-data-panel-title-settings').hide();
            }	
        },

		toggleThumb: function(item) {
            var value = jQuery(item).find("input").attr('checked');
			var panel = item.parentElement.parentElement;
            if (value == 'checked') {
                jQuery(panel).find('.categoryposts-data-panel-thumb-settings').show();
            }
            else {
                jQuery(panel).find('.categoryposts-data-panel-thumb-settings').hide();
            }	
        },

		selectPremadeTemplate: function(item) {
			var panel = item.parentElement.parentElement;
			var div = item.parentElement;
            var select = jQuery(div).find('select');
			var template = '%title%';
			value = select.val();
            switch (value) {
				case 'title':
					template = '%title%';
					break;
				case 'title_excerpt':
					template = '%title%\n%excerpt%';
					break;
				case 'title_thumb':
					template = '%title%\n%thumb%';
					break;
				case 'title_thum_excerpt':
					template = '%title%\n%thumb%\n%excerpt%';
					break;
				case 'everything':
					template = '%title%\n%thumb%\n%excerpt%\n\n';
					template += 'Published by %author% on %date%\n';
					template += 'and has %commentnum% comments\n\n';
					template += 'Categories: %category%\n\n';
					template += 'Tags: %post_tag%';
			}	
            var textarea = jQuery(panel).find('.categoryPosts-template textarea');
			textarea.val(template);
			textarea.trigger('change');
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
					jQuery(elem).parent().prev().find('.default_thumb_img').html(img_html);
					jQuery(elem).parent().find('.cwp_default_thumb_remove').show();
					jQuery(elem).parent().prev().find('.default_thumb_id').val(attachment.id).change();
				}
			});

			frame.open();
			return false;
		},
		
		removeDefaultThumbnailSelection : function (elem) {
			jQuery(elem).parent().prev().find('.default_thumb_img').html(cwp_default_thumb_selection.none);
			jQuery(elem).hide();
			jQuery(elem).parent().prev().find('.default_thumb_id').val(0).change();

			return false;
		},
			
    }
	
jQuery(document).ready( function () {
	
	var class_namespace = 'categoryPosts';

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
		
		jQuery('.categoryPosts-template a').on('click', function (event) { // open template help
			cwp_namespace.openTemplateHelp(this, event);
		});
		
		jQuery('.cat-post-premade_templates button').on('click', function () { // select a pre made template
			cwp_namespace.selectPremadeTemplate(this);
		});

		jQuery('.cat-post-premade_templates select').on('change', function (event) { // prevent refresh ontemplate selection
			event.preventDefault();
			event.stopPropagation();
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
		
		setChangeHandlers();
	});	

	function setChangeHandlers() {
		jQuery('.cwp_default_thumb_select').off('click').on('click', function () { // select default thumb
			cwp_namespace.defaultThumbnailSelection(this, cwp_default_thumb_selection.frame_title,cwp_default_thumb_selection.button_title);
		});

		jQuery('.cwp_default_thumb_remove').off('click').on('click', function () { // remove default thumb
			cwp_namespace.removeDefaultThumbnailSelection(this);
		});
		
		jQuery('.'+class_namespace+'-excerpt').off('click').on('click', function () { 
			cwp_namespace.toggleExcerptPanel(this);
		});
		
		jQuery('.'+class_namespace+'-date').off('click').on('click', function () { 
			cwp_namespace.toggleDatePanel(this);
		});
		
		jQuery('.'+class_namespace+'-assigned_categories').off('click').on('click', function () {
			cwp_namespace.toggleAssignedCategoriesTop(this);
		});
		
		jQuery('.'+class_namespace+'-hide_title').off('click').on('click', function () {
			cwp_namespace.toggleHideTitle(this);
		});
		
		jQuery('.'+class_namespace+'-thumb').off('click').on('click', function () {
			cwp_namespace.toggleThumb(this);
		});
		
		jQuery('.categoryposts-data-panel-filter-cat').on('change', function () { // change category filter
			cwp_namespace.toggleCatSelection(this);
		});
		
		jQuery('.categoryposts-data-panel-date-preset-format').on('change', function () { // change date format
			cwp_namespace.toggleDateFormat(this);
		});
		
		jQuery('.categoryPosts-template a').on('click', function (event) { // open template help
			cwp_namespace.openTemplateHelp(this, event);
		});
		
		jQuery('.cat-post-premade_templates button').on('click', function () { // select a pre made template
			cwp_namespace.selectPremadeTemplate(this);
		});

		jQuery('.cat-post-premade_templates select').on('change', function (event) { // prevent refresh ontemplate selection
			event.preventDefault();
			event.stopPropagation();
		});
	}
	
	setChangeHandlers();
});

