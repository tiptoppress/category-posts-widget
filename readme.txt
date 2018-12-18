=== Category Posts Widget ===
Contributors: mark-k, kometschuh, mkrdip
Donate link: http://mkrdip.me/donate
Tags: category, categories, posts, widget, posts widget, recent posts, category recent posts, shortcode, sidebar, excerpt, multiple widgets
Requires at least: 2.8
Tested up to: 5.0
Stable tag: 4.8.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds a widget that shows the most recent posts from a single category.

== Description ==
Category Posts Widget is a light widget designed to do one thing and do it well: display the most recent posts from a certain category.

= Term and Category based Posts Widget =
A premium version of that free widget available at [tiptoppress.com](http://tiptoppress.com/) created for big Wordpress sites.

= Premium features =
* Image-Slider
* Post List "Alterations"
* Full background post images
* Masonry (responsive layouts), Grid and Column full page layouts
* More complex ways to filter (ANY, NOT, AND, not include children)
* Custom Post Types, Events, Products support
* Add-ons
* All free features
* E-Mail support
* Free trail on localhost
* More examples on the [demo pages](http://demo.tiptoppress.com/)

= Features =
* [Template](http://tiptoppress.com/template-arrange-post-details/) to arrange the post details.
* The Template text can be a post details placeholder, plain text, HTML or a font-icons.
* Font-icon support.
* Shortcode (Easily change all Shortcode options in the customizer).
* New date format: Time since plublished
* Filter by post status: Published, scheduled, private.
* Multiple shortcodes at the same site or post.
* Add option for post offset (use two or more widgets after another).
* Add UI buttons in the editor toolbar to insert shortcode.
* Option to touch device friendly "everything is a link".
* For editing shortcode adds a Customizer link to the admin-bar ("With one click to the Customizer").
* Set thumbnail width & height.
* Fluid images for Responsive Layouts.
* Option to set mouse hover effects for post thumbnail.
* Set a default thumbnail.
* Hide widget text.
* Option to hide posts which have no thumbnail.
* Option exclude current post.
* Option show post author, comment's count, post date.
* Multi sites support.

= Documentation =
* Full [documentation](http://tiptoppress.com/category-posts-widget/documentation-4-8)
* Shortcode: Use [catposts] in the content and [edit in the customizer](http://tiptoppress.com/use-shortcode-to-add-category-posts-widget-to-the-content/)
* Formatting date and time: See <a target="_blank" href="https://codex.wordpress.org/Formatting_Date_and_Time">Formatting Date and Time</a>

= Contribute =
While using this plugin if you find any bug or any conflict, please submit an issue at
[Github](https://github.com/tiptoppress/category-posts-widget) (If possible with a pull request).

== Installation ==
= Automatic installation =
Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of Category Posts Widget,

1. log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.
2. In the search field type “Category Posts Widget” and click Search Plugins.
3. Once you’ve found plugin, you can install it by simply clicking “Install Now”.
4. Then, go to plugins page of WordPress admin activate the plugin.
5. Now, goto the Widgets page of the Appearance section and configure the Category Posts widget.

= Manual installation =
1. Download the plugin.
2. Upload it to the plugins folder of your blog.
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Now, goto the Widgets page of the Appearance section and configure the Category Posts widget.

== Upgrade Notice ==
* Please consider to re-configure the widget as the latest version has numerous changes from previous.
* Version 4.0 uses CSS file for styling the widget in front end.
* Version 3.0 or later version uses WordPress 2.9's built in post thumbnail functionality.

== Frequently Asked Questions ==
= Template, placeholder and post detail =
Here You can control the [Post Detail parts](http://tiptoppress.com/category-posts-widget/documentation-4-8/#Post_details), which appears as part of the post item. All post detail will placed as placeholder. The text in the Template area can be a post details placeholder, plain text, HTML or HTML for SVG icons.

[How it works? and examples.](http://tiptoppress.com/template-arrange-post-details/)

For more layout options please try our premium widget: [Term and Category based Posts Widget](http://tiptoppress.com/term-and-category-based-posts-widget/).

= Use SVG icons =
For SVG font-icon HTML we recommend the [WordPress Dashicons](https://developer.wordpress.org/resource/dashicons/), which are included as default and can be used without any font-icon including.

= How to use Page Bilder plugins like Divi, SiteOrigin Page Builder or Elementor =
Please read more about Page Builder plugins at our [FAQs](http://tiptoppress.com/faqs/)

= Parse error: syntax error, unexpected T_FUNCTION in /home/www/blog/wp-content/plugins/category-posts/cat-posts.php on line 58 =
Some of the features that were used in that version needs PHP 5.3+.
We apologies for any headache this may cause you, but frankly it is better for you to check with your hosting company how can you upgrade the PHP version that you are using, and not only in order to use this plugin. PHP 5.2 should be considered insecure now, and for your own sake you should upgrade.
PHP 5.2 is very old and any support for it from the php developers had ended more then 5 years ago [php.net/eol.php](http://php.net/eol.php).
We know there are peopel how use PHP 5.2 [wordpress.org/about/stats](https://wordpress.org/about/stats/) and we can't imagine this people will have no other problems, if they don't update.

= You check the PHP version with phpversion(), but the widget don't work =
Check also the .htaccess file, if there is an entry for an older PHP version.

== Screenshots ==
1. Front end of the widget with SVG font-icon support for post formats, hover effects and the Template text-area.
2. Template to arrange the post details with placeholders.
3. Edit the widget options with the customizer.
4. Use shortcode [catposts] in the content.
5. The widget configuration dialog.
6. Widget behaviour settings for each user.

== Changelog ==
[Read more on our blog ...](http://tiptoppress.com/category/category-posts-widget)

= 4.8.5 - April 02nd 2018 =
* Fixed Tabs not working

= 4.8.4 - April 02nd 2018 =
* Fixed Make widget SiteOrigin Page Builder Compatible

= 4.8.3 - March 03th 2018 =
* Fixed Updated widget with zero for the thumb dimensions caused a JavaScript error

= 4.8.2 - January 30th 2018 =
* Fixed Adding the widget with the customizer only the title is shown
* Fixed Recognize "Empty lines" > Next line is a paragraph in the Template in widget areas

= 4.8.1 - January 25th 2018 =
* Fixed Recognize "Empty lines" > Next line is a paragraph in the Template

= 4.8 - January 22th 2018 =
* SVG font-icon support for post formats
* Template to arrange the post details
* Premade Templates
* Date format: Time since plublished
* Filter by post status: Published, scheduled, private
* Hover effect: SVG font-icon

= 4.7.4 - October 21th 2017 =
* Bugfix for filter by post status (note private)

= 4.7.3 - October 10th 2017 =
* Add option to filter by post status
* Fixed footer section do not change when switching to and from "all posts"

= 4.7.2 - February 25th 2017 =
* Add option to disable only the font styles
* Fixed if option 'Everything is a link' no closing anchor tag
* Fixed if option 'Everyting is a link' wrong layout
* Fixed when a manual excerpt is provided, use it instead of generating an automatic one
* Fixed if option 'Disable the built-in CSS' the thumbnail client-side cropping isn't disabled
* Fixed if option 'Disable the built-in CSS' title class is not rendered
* Fixed when having multi shortcodes, clicking on a checkbox label marks in any of them selects the one in the "first"
* Fixed if option 'CSS crop to requested size' for multi shortcodes
* Fixed for CSS animation

= 4.7.1 - December 20th 2016 =
* Support multiple shortcodes in content
* Add option for post offset (use two or more widgets after another)
* Fluid images for Responsive Layouts
* Set a thumbnail as default thumbnail
* Add option to enable excerpt filters from Themes and plugins
* Add option to disable social buttons, banner, ... in the excerpt
* Add dropdownbox entry for 'all' categories
* Add option to disable subcategories
* Add insert shortcode buttons to the editor toolbar
* Use WP user profile for settings ('auto close' and if the shortcode button appears in the editor toolbar)
* Simple API for external use
* Support localization with translate.wordpress.org: Portuguese (Brazil) thank you [Henrique Vianna](https://profiles.wordpress.org/hvianna/) and German by [Daniel Floeter](https://profiles.wordpress.org/kometschuh/)
* Remove allow_html option (Instead we recommend to use the [manual excerpt](https://codex.wordpress.org/Excerpt#How_to_add_excerpts_to_posts) or we support this option furthermore in the [Term Posts Excerpt Extension](https://github.com/tiptoppress/term-posts-excerpt-extension) for the premium version)

= 4.6.2 - August 28th 2016 =
* Fixed only five widget instances can be costumized with shortcodes.
* For editing shortcode adds a customizer link to the admin-bar if page/post is in edit mode.

= 4.6.1 - June 5th 2016 =
* Add shortcode [catposts] edit options only in the customizer
* Keep panels open after save.
* Option to hide social buttons on output.

= 4.1.9 - May 5th 2016 =
* Fixed undefined constant.

= 4.1.8 - May 3th 2016 =
* Add mouse hover effects: blur
* Add option to choose allowed HTML in the excerpt
* Add Jetpack 'Sharing - Show buttons on posts' support
* Fixed division by zero bug (for small uploaded images)

= 4.1.7 - April 14th 2016 =
* Fixed division by zero bug.

= 4.1.6 - April 13th 2016 =
* Add option CSS cropping for thumbnails.
* Add option to set mouse hover effects for post thumbnail.
* Add option to change the excerpt more text.
* Add option to allow links in the excerpt
* Add filter 'widget_title' for the title
* Add option to hide post titles.

= 4.1.5 - February 4th 2016 =
* Support for multi sites.
* Support for localization.
* Area UI.
* Meet new plugin author [mark-k](https://profiles.wordpress.org/mark-k/)

= 4.1.4 =
* Added option exclude current post.
* Added option to hide posts which have no thumbnail.
* Added option to make the widget date link to the category page.
* Added option to link to the category page below posts list.
* Added option show post author.
* Added option to format the outputted date string.

= 4.1.3 =
* Added option to hide widget, if category have currently no posts.

= 4.1.2 =
* Fixed hide title bug.

= 4.1.1 =
* Added option to put thumbnail on top.
* Added option to show/hide the title.
* Fixed no background bug.

= 4.1.0 =
* Added PHP5 Constructor.
* Added Option to allow/disallow widget CSS.
* Now, compatible with WordPress 4.3.
* Meet new plugin author [kometschuh](https://profiles.wordpress.org/kometschuh)

= 4.0 =
* Added CSS file for post styling.
* Now compatible with latest versions of WordPress.

= 3.3 =
* Fixed random sort bug.

= 3.2 =
* Added option to change ordering of posts. Defaults to showing newest posts first.

= 3.1 =
* Fixed a bug in the thumbnail size registration routine.

= 3.0 =
* Added support for WP 2.9's post thumbnail feature.
* Removed support for Simple Post Thumbnails plugin.
* Added option to show the post date.
* Added option to set the excerpt length.
* Added option to show the number of comments.

= 2.3 =
* Really tried to fix bug where wp_query global was getting over written by manually instantiating a WP_Query object.

= 2.1 =
* Fixed bug where wp_query global was getting over written.

= 2.0 =
* Updated to use the WP 2.8 widget API.
* Added support for [Simple Post Thumbnails plugin](http://wordpress.org/extend/plugins/simple-post-thumbnails/).
