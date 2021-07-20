/**
 * Category Posts Widget
 * https://github.com/tiptoppress/category-posts-widget
 *
 * Adds a widget that shows the most recent posts from a single category.
 *
 * Released under the GPLv2 license or later -  http://www.gnu.org/licenses/gpl-2.0.html
 */

// namespace

var cwp_namespace = {

    php_settings_var: 'categoryPosts',
    widget_class: '.category-widget-cont',
    template_panel_prefix: '.categoryposts-data-panel-',
    open_panels: {}, // holds an array of open panels per wiget id
    template_change_timer: null, // Used for debouncing change events generate when template changes.

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

    // Show hide foote link URL
    toggleCatSelection: function(item) {
        var cat = jQuery(item).find("option:selected").attr('value');
        var panel = item.parentElement.parentElement.parentElement.parentElement;
        if (cat == '0') {
            jQuery(panel).find('.categoryPosts-title_link').hide();
            jQuery(panel).find('.categoryPosts-title_link_url').show();
            jQuery(panel).find('.categoryPosts-no_cat_childs').hide();
        } else {
            jQuery(panel).find('.categoryPosts-title_link').show();
            jQuery(panel).find('.categoryPosts-title_link_url').hide();
            jQuery(panel).find('.categoryPosts-no_cat_childs').show();
        }
    },

    // Show hide font styles on disable CSS setting change
    toggleDisableFontStyles: function(item) {
        var panel = item.parentElement.parentElement.parentElement;
        if (item.checked) {
            jQuery(panel).find('.categoryPosts-disable_font_styles').hide();
        } else {
            jQuery(panel).find('.categoryPosts-disable_font_styles').show();
        }
    },

    // Show hide other date format
    toggleDateFormat: function(item) {
        var value = jQuery(item).val();
        var panel = item.parentElement.parentElement;
        if (value != 'other') {
            jQuery(panel).find('.categoryPosts-date_format').hide();
        } else {
            jQuery(panel).find('.categoryPosts-date_format').show();
        }
    },

    // Show hide other date range settings
    toggleDateRange: function(item) {
        var value = jQuery(item).val();
        var panel = item.parentElement.parentElement;
        jQuery(panel).find('.categoryPosts-date-range p').hide();
        jQuery(panel).find('.categoryPosts-date-range').show();
        switch (value) {
            case 'off':
                jQuery(panel).find('.categoryPosts-date-range').hide();
                break;
            case 'days_ago':
                jQuery(panel).find('.categoryPosts-days_ago').show();
                break;
            case 'between_dates':
                jQuery(panel).find('.categoryPosts-start_date').show();
                jQuery(panel).find('.categoryPosts-end_date').show();
                break;
        }
    },

    // Show/hide no match related settings
    toggleNoMatch: function(item) {
        var value = jQuery(item).val();
        var panel = item.parentElement.parentElement;
        if ('text' == value) {
            jQuery(panel).find('.categoryPosts-no-match-text').show();
        } else {
            jQuery(panel).find('.categoryPosts-no-match-text').hide();
        }
    },

    // Show template help
    toggleTemplateHelp: function(item, event) {
        event.preventDefault();
        var panel = item.parentElement.parentElement.parentElement.parentElement;
        jQuery(panel).find('.cat-post-template-help').toggle('slow');
    },

    // Show image dimensions help
    toggleImageDimensionsHelp: function(item, event) {
        event.preventDefault();
        var panel = item.parentElement.parentElement.parentElement.parentElement;
        jQuery(panel).find('.cat-post-image-dimensions-help').toggle('slow');
    },

    // Show title level help
    toggleTitleLevelHelp: function(item, event) {
        event.preventDefault();
        var panel = item.parentElement.parentElement.parentElement.parentElement;
        jQuery(panel).find('.cat-post-title-level-help').toggle('slow');
    },

    // Show More Link help
    toggleMoreLinkHelp: function(item, event) {
        event.preventDefault();
        var panel = item.parentElement.parentElement.parentElement.parentElement;
        jQuery(panel).find('.cat-post-more-link-help').toggle('slow');
    },

    // Show Load More button text help
    toggleButtonTextHelp: function(item, event) {
        event.preventDefault();
        var panel = item.parentElement.parentElement.parentElement.parentElement;
        jQuery(panel).find('.cat-post-button-text-help').toggle('slow');
    },

    toggleHideTitle: function(item) {
        var panel = item.parentElement.parentElement.parentElement;
        if (item.checked) {
            jQuery(panel).find('.categoryposts-data-panel-title-settings').hide();
        } else {
            jQuery(panel).find('.categoryposts-data-panel-title-settings').show();
        }
    },

    toggleLoadMore: function(item) {
        var panel = item.parentElement.parentElement.parentElement;
        if (item.checked) {
            jQuery(panel).find('.loadmore-settings').show();
        } else {
            jQuery(panel).find('.loadmore-settings').hide();
        }
    },

    selectPremadeTemplate: function(item) {
        var panel = item.parentElement.parentElement.parentElement;
        var div = item.parentElement.parentElement;
        var select = jQuery(div).find('select');
        var template = '%title%';
        value = select.val();
        switch (value) {
            case 'title':
                template = '%title%';
                break;
            case 'title_excerpt':
                template = '%title%\n\n%excerpt%\n\n%more-link%';
                break;
            case 'title_thumb':
                template = '%title%\n\n%thumb%';
                break;
            case 'title_thum_excerpt':
                template = '%title%\n\n%thumb%\n%excerpt%\n\n%more-link%';
                break;
            case 'everything':
                template = '%title%\n\n';
                template += '%date%\n\n';
                template += '%thumb%\n';
                template += '<span class="dashicons dashicons-admin-comments"></span> %commentnum% ';
                template += '<span class="dashicons dashicons-admin-users"></span> %author%\n';
                template += '%excerpt%\n\n';
                template += '%more-link%\n\n';
                template += 'Categories: %category% ';
                template += '<span class="dashicons dashicons-tag"></span> %post_tag%';
        }
        var textarea = jQuery(panel).find('textarea');
        textarea.val(template);
        textarea.trigger('input', 'change');
    },

    // Close all open panels if open
    autoCloseOpenPanels: function(_this) {
        if (tiptoppress[this.php_settings_var].accordion) {
            if (!jQuery(_this).hasClass('open')) {
                var jCloseElement = jQuery(_this).parent().find('.open');
                this.clickHandler(jCloseElement);
            }
        }
    },

    defaultThumbnailSelection: function(elem, title, button_title) {

        var frame = wp.media({
            title: title,
            multiple: false,
            library: { type: 'image' },
            button: { text: button_title },
        });

        // Handle results from media manager.
        frame.on('close', function() {
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

    removeDefaultThumbnailSelection: function(elem) {
        jQuery(elem).parent().prev().find('.default_thumb_img').html(cwp_default_thumb_selection.none);
        jQuery(elem).hide();
        jQuery(elem).parent().prev().find('.default_thumb_id').val(0).change();

        return false;
    },

    templateChange: function(elem) {

        function adjustUiToTemplate() {
            var template = jQuery(elem).val();
            var tags = tiptoppress[this.php_settings_var].template_tags;
            var widget_cont = jQuery(elem.parentElement.parentElement.parentElement.parentElement);
            for (var key in tags) {
                if (-1 !== template.indexOf(tags[key])) {
                    widget_cont.find(this.template_panel_prefix + tags[key]).show();
                } else {
                    widget_cont.find(this.template_panel_prefix + tags[key]).hide();
                }
            }
        }

        if (null != this.template_change_timer) {
            clearTimeout(this.template_change_timer);
        }
        this.template_change_timer = setTimeout(adjustUiToTemplate.bind(this), 250);

    },

    thumbnailSizeChange: function(elem) {

        var _that = jQuery(elem),
            thumb_h,
            thumb_w,
            _input_thumb_h = _that.closest('.categoryposts-data-panel-thumb').find('.thumb_h'),
            _input_thumb_w = _that.closest('.categoryposts-data-panel-thumb').find('.thumb_w');

        if (_that.hasClass('smaller')) {
            thumb_w = _input_thumb_w.val() / 1.015;
            thumb_h = _input_thumb_h.val() / 1.015;
        } else if (_that.hasClass('quarter')) {
            thumb_w = _input_thumb_w.val() / 4;
            thumb_h = _input_thumb_h.val() / 4;
        } else if (_that.hasClass('half')) {
            thumb_h = _input_thumb_h.val() / 2;
            thumb_w = _input_thumb_w.val() / 2;
        } else if (_that.hasClass('double')) {
            thumb_h = _input_thumb_h.val() * 2;
            thumb_w = _input_thumb_w.val() * 2;
        } else if (_that.hasClass('bigger')) {
            thumb_w = _input_thumb_w.val() * 1.02;
            thumb_h = _input_thumb_h.val() * 1.02;
        } else if (_that.hasClass('square')) {
            if (parseInt(_input_thumb_w.val()) >= parseInt(_input_thumb_h.val())) {
                thumb_h = _input_thumb_w.val();
                thumb_w = _input_thumb_w.val();
            } else {
                thumb_h = _input_thumb_h.val();
                thumb_w = _input_thumb_h.val();
            }
        } else if (_that.hasClass('standard')) {
            if (parseInt(_input_thumb_w.val()) >= parseInt(_input_thumb_h.val())) {
                thumb_h = _input_thumb_w.val() / 4 * 3
                thumb_w = _input_thumb_w.val();
            } else {
                thumb_h = _input_thumb_w.val() * 4 / 3;
                thumb_w = _input_thumb_w.val();
            }
        } else if (_that.hasClass('wide')) {
            if (parseInt(_input_thumb_w.val()) >= parseInt(_input_thumb_h.val())) {
                thumb_h = _input_thumb_w.val() / 16 * 9;
                thumb_w = _input_thumb_w.val();
            } else {
                thumb_h = _input_thumb_w.val() * 16 / 9;
                thumb_w = _input_thumb_w.val();
            }
        } else if (_that.hasClass('switch')) {
            thumb_h = _input_thumb_w.val();
            thumb_w = _input_thumb_h.val();
        } else if (_that.hasClass('width')) {
            _input_thumb_w.val() == 0 ? thumb_w = 300 : thumb_w = _input_thumb_w.val();
            thumb_h = 0;
        } else if (_that.hasClass('height')) {
            _input_thumb_h.val() == 0 ? thumb_h = 300 : thumb_h = _input_thumb_h.val();
            thumb_w = 0;
        } else if (_that.hasClass('both')) {
            thumb_h = 0;
            thumb_w = 0;
        } else {
            thumb_w = _that.data("thumb-w");
            thumb_h = _that.data("thumb-h");
        }
        _input_thumb_w.val(Math.floor(thumb_w));
        _input_thumb_h.val(Math.floor(thumb_h));
        _input_thumb_w.trigger('input', 'change');
        _input_thumb_h.trigger('input', 'change');

        return false;
    },

    thumbnailFluidWidthChange: function(elem) {

        var _that = jQuery(elem),
            _input_thumb_h = _that.closest('.categoryposts-data-panel-thumb').find('.thumb_h');

        _that.closest('label').find('span').html(_that.val() + '%');

        _input_thumb_h.trigger('input', 'change');

        return false;
    },

    openAddPlaceholder: function(elem) {

        var _that = jQuery(elem);

        _that.closest('.cat-post-add_premade_templates').find('.cpwp-placeholder-dropdown-menu').toggle();

        _that.closest('.cat-post-add_premade_templates').find('.cpwp-placeholder-dropdown-menu span').off('click').on('click', function() {
            var text = jQuery(this).data('value');
            switch (text) {
                case 'NewLine':
                    text = '\n';
                    break;
                case 'EmptyLine':
                    text = '\n\n';
                    break;
                default:
                    text = '%' + text + '%';
                    break;
            }
            var _div = this.parentElement.parentElement.parentElement;
            var textarea = jQuery(_div).find('textarea');
            var textareaPos = textarea[0].selectionStart;
            var textareaTxt = textarea.val();
            textarea.val(textareaTxt.substring(0, textareaPos) + text + textareaTxt.substring(textareaPos));

            textarea[0].selectionStart = textareaPos + text.length;
            textarea[0].selectionEnd = textareaPos + text.length;
            textarea.focus();
            textarea.trigger('input', 'change');

            //_that.closest( '.cat-post-add_premade_templates' ).find( '.cpwp-placeholder-dropdown-menu' ).hide();
        });

        _that.closest('.cat-post-add_premade_templates').find('.cpwp-placeholder-dropdown-menu').on('mouseenter', function() {
            jQuery(this).addClass('cpw-doNotClose');
        });
        _that.closest('.cat-post-add_premade_templates').find('.cpwp-placeholder-dropdown-menu').on('mouseleave', function() {
            jQuery(this).removeClass('cpw-doNotClose');
        });

        _that.closest('.cat-post-add_premade_templates').find('.cpwp-close-placeholder-dropdown-menu').off('click').on('click', function() {
            _that.closest('.cat-post-add_premade_templates').find('.cpwp-placeholder-dropdown-menu').toggle();
        });

        return false;
    },

    selectPlaceholderHelper: function(elem) {

        var textarea = jQuery(elem);
        var textareaPos = textarea[0].selectionStart;
        var textareaTxt = textarea.val();

        var nStartSel = textareaTxt.substring(0, textareaPos).lastIndexOf('%');
        var nEndSel = textareaPos + textareaTxt.substring(textareaPos).indexOf('%') + 1;

        var strSelTxt = textareaTxt.substring(nStartSel, nEndSel);
        if (strSelTxt.indexOf('\n') >= 0 || strSelTxt.indexOf(' ') >= 0 || strSelTxt.length <= 2) {
            return false;
        }

        textarea[0].selectionStart = nStartSel;
        textarea[0].selectionEnd = nEndSel;
        return false;
    },
}

jQuery(document).ready(function() {

    var class_namespace = '.category-widget-cont';

    jQuery('.category-widget-cont h4').on('click', function() { // for widgets page
        cwp_namespace.autoCloseOpenPanels(this);
        // toggle panel open/close
        cwp_namespace.clickHandler(this);
    });

    // needed to reassign click handlers after widget refresh
    jQuery(document).on('widget-added widget-updated panelsopen', function(root, element) { // for customize and after save on widgets page (add panelsopen: fix make widget SiteOrigin Page Builder plugin, GH issue #181)

        jQuery('.category-widget-cont h4').off('click').on('click', function() {
            cwp_namespace.autoCloseOpenPanels(this);
            // toggle panel open/close
            cwp_namespace.clickHandler(this);
        });
        jQuery('.cwp_default_thumb_select').off('click').on('click', function() { // select default thumb
            cwp_namespace.defaultThumbnailSelection(this, cwp_default_thumb_selection.frame_title, cwp_default_thumb_selection.button_title);
        });

        jQuery('.cwp_default_thumb_remove').off('click').on('click', function() { // remove default thumb
            cwp_namespace.removeDefaultThumbnailSelection(this);
        });

        // refresh panels to state before the refresh
        var id = jQuery(element).attr('id');
        if (cwp_namespace.open_panels.hasOwnProperty(id)) {
            var o = cwp_namespace.open_panels[id];
            for (var panel in o) {
                jQuery(element).find('[data-panel=' + panel + ']').toggleClass('open')
                    .next().stop().show();
            }
        }

        setChangeHandlers();
    });

    function setChangeHandlers() {
        // Title tab
        jQuery(document).on('click', class_namespace + ' .categoryPosts-hide_title input[type=checkbox]', function() {
            cwp_namespace.toggleHideTitle(this);
        });

        // Filter tab
        jQuery(document).on('change', class_namespace + ' .categoryposts-data-panel-filter-cat', function() { // change category filter
            cwp_namespace.toggleCatSelection(this);
        });

        jQuery(document).on('change', class_namespace + ' .categoryPosts-date_range select', function() { // change date range
            cwp_namespace.toggleDateRange(this);
        });

        // Post details tab
        jQuery('.cwp_default_thumb_select').off('click').on('click', function() { // select default thumb
            cwp_namespace.defaultThumbnailSelection(this, cwp_default_thumb_selection.frame_title, cwp_default_thumb_selection.button_title);
        });

        jQuery(document).on('click', class_namespace + ' .cat-post-premade_templates button', function() { // select a pre made template
            cwp_namespace.selectPremadeTemplate(this);
        });

        jQuery(document).on('change', class_namespace + ' .cat-post-premade_templates select', function(event) { // prevent refresh ontemplate selection
            event.preventDefault();
            event.stopPropagation();
        });

        jQuery(document).on('input', class_namespace + ' .categoryPosts-template textarea', function() { // prevent refresh ontemplate selection
            cwp_namespace.templateChange(this);
        });

        jQuery(document).on('change', class_namespace + ' .categoryPosts-preset_date_format select', function() { // change date format
            cwp_namespace.toggleDateFormat(this);
        });

        jQuery(class_namespace + ' .cat-post-thumb-change-size button').off('click').on('click', function() { // find a thumbnail size
            cwp_namespace.thumbnailSizeChange(this);
        });

        jQuery(document).on('input', class_namespace + ' .thumb_fluid_width', function() { // select a thumbnail fluid size
            cwp_namespace.thumbnailFluidWidthChange(this);
        });

        jQuery('.cwp_default_thumb_remove').off('click').on('click', function() { // remove default thumb
            cwp_namespace.removeDefaultThumbnailSelection(this);
        });

        jQuery(class_namespace + ' a.toggle-template-help').off('click').on('click', function(event) { // show template help
            cwp_namespace.toggleTemplateHelp(this, event);
        });

        jQuery(class_namespace + ' a.toggle-image-dimensions-help').off('click').on('click', function(event) { // show image dimensions help
            cwp_namespace.toggleImageDimensionsHelp(this, event);
        });

        jQuery(class_namespace + ' a.toggle-title-level-help').off('click').on('click', function(event) { // show title level help
            cwp_namespace.toggleTitleLevelHelp(this, event);
        });

        jQuery(class_namespace + ' a.toggle-more-link-help').off('click').on('click', function(event) { // show image dimensions help
            cwp_namespace.toggleMoreLinkHelp(this, event);
        });

        jQuery(class_namespace + ' a.toggle-button-text-help').off('click').on('click', function(event) { // show load more button text help
            cwp_namespace.toggleButtonTextHelp(this, event);
        });

        jQuery(class_namespace + ' .cpwp-open-placholder-dropdown-menu').off('click').on('click', function() { // open drop down and add placeholder
            cwp_namespace.openAddPlaceholder(this);
        });

        jQuery(document).on('onfocusout, blur', class_namespace + ' .cpwp-open-placholder-dropdown-menu,' + class_namespace + ' .categoryPosts-template textarea', function() { // close drop down placeholder, if not used
            jQuery(this).closest(class_namespace + ' .categoryPosts-template').parent().find('.cpwp-placeholder-dropdown-menu').not('.cpw-doNotClose').hide();
        });

        jQuery(document).on('click', class_namespace + ' .categoryPosts-template textarea', function() { // close drop down placeholder, if textarea is clicked
            jQuery(this).closest(class_namespace + ' .categoryPosts-template').parent().find('.cpwp-placeholder-dropdown-menu').not('.cpw-doNotClose').hide();
        });

        jQuery(document).on('mousedown', class_namespace + ' .categoryPosts-template textarea', function() { // help to select the placeholder
            var _that = this;
            setTimeout(function() { cwp_namespace.selectPlaceholderHelper(_that); }, 0);;
        });

        // General tab
        jQuery(document).on('click', class_namespace + ' .categoryPosts-disable_css input[type=checkbox]', function() { // toggle UI font styles on disable CSS setting change.
            cwp_namespace.toggleDisableFontStyles(this);
        });

        jQuery(document).on('change', class_namespace + ' .categoryPosts-no_match_handling select', function() { // change date range
            cwp_namespace.toggleNoMatch(this);
        });

        jQuery(document).on('click', class_namespace + ' .categoryPosts-enable_loadmore input[type=checkbox]', function() {
            cwp_namespace.toggleLoadMore(this);
        });
    }

    setChangeHandlers();
});
