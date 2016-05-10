=== Category Posts Widget ===
Contributors: mkrdip, mark-k, kometschuh
Donate link: http://mkrdip.me/donate
Tags: category, posts, widget, single category widget, posts widget, category recent posts
Requires at least: 2.8
Tested up to: 4.5.2
Stable tag: 4.1.9
License: GPLv2 or later 
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds a widget that shows the most recent posts from a single category.

== Description ==
Category Posts Widget is a light widget designed to do one thing and do it well: display the most recent posts from a certain category.

= Term and Category based Posts Widget =
It's the pro version and available at on <a target="_blank" href="http://tiptoppress.com/">Tip Top Press</a> created for big Wordpress sites.

= Pro features =
* Custom Post Types, Terms and Custom Taxonomies
* Multi selection
* Different styles, like vertical scrolling ticker

= Features =
* Option to change ordering of posts.
* Option to show post thumbnail & set dimension by width & height.
* Option to crop thumbnails with CSS. <a target="_blank" href="http://tiptoppress.com/css-image-crop/">What is 'CSS Image Crop'?</a>
* Option to set mouse hover effects for post thumbnail.
* Jetpack 'Sharing - Show buttons on posts' support
* Option to put thumbnail on top
* Option to hide posts which have no thumbnail.
* Option to disable widget CSS.
* Set how many posts to show.
* Option exclude current post.
* Option show post author.
* Set which category the posts should come form.
* Option to show the post excerpt, set the length, allow HTML and change 'more' text.
* Option to show the post date.
* Option to make the widget date link to the category page.
* Option to format the outputted date string.
* Option to show the comment count.
* Option to make the widget title link to the category page.
* Option to link to the category page below posts list.
* Option to hide the widget title and post titles.
* Option to hide widget, if category have currently no posts.
* Multiple widgets.
* Multi sites support.
* Localization support.

= Documentation =
Formatting date and time: See <a target="_blank" href="https://codex.wordpress.org/Formatting_Date_and_Time">Formatting Date and Time</a>.

= Contribute =
While using this plugin if you find any bug or any conflict, please submit an issue at 
[Github](https://github.com/mkrdip/category-posts-widget) (If possible with a pull request). 

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
= The font-size is different from that of other widgets or Theme elements? =
Please use the option: "Disable widget CSS".

= I want the title as a link pointing to the selected Categorie page? =
Enable the check box "Make widget title link".

= Parse error: syntax error, unexpected T_FUNCTION in /home/www/blog/wp-content/plugins/category-posts/cat-posts.php on line 58 =
Some of the features that were used in that version needs PHP 5.3+.
We apologies for any headache this may cause you, but frankly it is better for you to check with your hosting company how can you upgrade the PHP version that you are using, and not only in order to use this plugin. PHP 5.2 should be considered insecure now, and for your own sake you should upgrade.
PHP 5.2 is very old and any support for it from the php developers had ended more then 5 years ago [php.net/eol.php](http://php.net/eol.php).
We know there are peopel how use PHP 5.2 [wordpress.org/about/stats](https://wordpress.org/about/stats/) and we can't imagine this people will have no other problems, if they don't update.

== Screenshots ==
1. The widget configuration dialog.
2. Front end of the widget using a default WordPress Theme.

== Changelog ==
[Read more on our blog ...](http://tiptoppress.com/category/category-posts-widget/)

= 4.1.9 - May 5th 2016 =
* Fixed undefined constant.

= 4.1.8 - May 03th 2016 =
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
* Generate thumbs from existing images.
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
* Added CSS file for post styling .
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
