/**
 * Category Posts Widget
 * https://github.com/tiptoppress/category-posts-widget
 *
 * JS for the "load more" functionality.
 *
 * Released under the GPLv2 license or later -  http://www.gnu.org/licenses/gpl-2.0.html
 */

if (typeof jQuery !== 'undefined') {

    var php_settings_var = 'categoryPosts'; // should be identical to namespace.

    jQuery(document).ready(function() {

        // scrollbar
        jQuery('.' + php_settings_var + '-loadmore button').each(function() {
            if ( jQuery(this).data('scrollto') ) {
                var _ul = jQuery(this.parentElement.parentElement).find('ul'); // The UL of the widget.
                _ul.css({
                    "height":_ul.prop('scrollHeight'),
                });
            }
        });

        // Handle the click of load more.
        jQuery(document).on('click', '.' + php_settings_var + '-loadmore button', function() {
            var _this = jQuery(this),
                id = _this.data('id'),
                number = _this.data('number'),
                start = _this.data('start'),
                context = _this.data('context'),
                url = tiptoppress[php_settings_var].json_root_url,
                _ul = jQuery(this.parentElement.parentElement).find('ul'), // The UL of the widget.
                origText = _this.text(),
                postCount = _this.data('post-count'),
                loadingText = _this.data('loading'),
                loadmoreText = _this.data('placeholder'),
                widgetNumber = jQuery(this).closest("[id*='" + id + "']").attr('id'),
                scrollHeight = _ul.prop('scrollHeight'), // Scrollbar
                useScrollTo = _this.data('scrollto'); // Scrollbar

            // Change the button text to indicate loading.
            _this.text(loadingText);
            // Get the data from the server
            jQuery.getJSON(url + '/' + id + '/' + start + '/' + number + '/' + context + '/', function(data) {
                // appened the returned data to the UL in the returned order.
                jQuery.each(data, function(key, li) {
                    _ul.append(li);
                    // apend returns the _ul, therefor we need to actualy find
                    // the newly added item.
                    _ul.children().last().trigger('catposts.load_more');
                });
                if (postCount < start + number) {
                    _this.hide();
                } else {
                    loadmoreText = loadmoreText.replace("%step%", start + number - 1);
                    loadmoreText = loadmoreText.replace("%all%", postCount);
                    _this.text(loadmoreText);
                    _this.data('start', start + number);
                }
            }).done(function() {

                // Scrollbar
                if (useScrollTo) {
                    _ul.stop().animate({
                        scrollTop:scrollHeight,
                    }, 1000, 'swing');
                }

                var widget = jQuery('#' + widgetNumber);
                var widgetImage = jQuery(widget).find('.cat-post-item img').first();

                // do each time new items are added
                cat_posts_namespace.layout_wrap_text.preWrap(widget);
                cat_posts_namespace.layout_wrap_text.setClass(widget);
                if (0 !== parseInt(widgetImage.data('cat-posts-height')) && 0 !== parseInt(widgetImage.data('cat-posts-width'))) {
                    cat_posts_namespace.layout_img_size.setHeight(widget);
                }
            }).fail(function() {
                // Revert to original text.
                _this.text(origText);
            });
        });
    });
}
