Category Posts Widget
=====================

[![Join the chat at https://gitter.im/mkrdip/category-posts-widget](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/mkrdip/category-posts-widget?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

Adds a widget that shows the most recent posts from a single category.

**Contributors:** Mrinal, Mark, Daniel
  
**Donate link:** http://mkrdip.me/donate
  
**License:** GPLv2 or later 
  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html


## Description ##
Category Posts Widget is a light widget designed to do one thing and do it well: display the most recent posts from a certain category.

We are working on a pro version that is available in a few days. If you want, you can send us your e-mail address to: tiptop@tiptoppress.com. Once our new website is accessible, we will inform you about the new features. There will be a multi-category option that supports custom post types and new WP tags. We want to design different styles that are easy to be selected, like vertical scrolling ticker. Then we plan a bundle offer with our other expanded Posts widgets, so you can upgrade your site quickly by widget bundle. The free version will still exist, maintained and supported with most of the new features.

### Features ###
* Option to change ordering of posts.
* Option to show post thumbnail & set dimension by width & height.
* Option to set mouse hover effects for post thumbnail.
* Option to put thumbnail on top.
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
* Option to hide the title.
* Option to hide widget, if category have currently no posts.
* Multiple widgets.
* Multi sites support.
* Localization support.
* Generate thumbs from existing images.

### Documentation ###
Formatting date and time: See [Formatting Date and Time] (https://codex.wordpress.org/Formatting_Date_and_Time).

### Contribute ###
While using this plugin if you find any bug or any conflict, please submit an issue at 
[Github](https://github.com/mkrdip/category-posts-widget) (If possible with a pull request). 

## Installation ##
### Automatic installation ###
Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of Category Posts Widget, 

1. log in to your WordPress dashboard, navigate to the Plugins menu and click Add New. 
2. In the search field type “Category Posts Widget” and click Search Plugins. 
3. Once you’ve found plugin, you can install it by simply clicking “Install Now”. 
4. Then, go to plugins page of WordPress admin activate the plugin. 
5. Now, goto the Widgets page of the Appearance section and configure the Category Posts widget.

### Manual installation ###
1. Download the plugin.
2. Upload it to the plugins folder of your blog.
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Now, goto the Widgets page of the Appearance section and configure the Category Posts widget.


## Changelog ##
4.1.6
* Add option to set mouse hover effects for post thumbnail.
* Add option to change the excerpt more text.
* Add option to allow HTML in the excerpt
* Add filter 'widget_title' for the title

4.1.5 - February 4 2016
* Support for multi sites.
* Support for localization.
* Generate thumbs from existing images.
* Area UI.
* Meet new plugin author [mark-k](https://profiles.wordpress.org/mark-k/)

4.1.4
* Added option exclude current post.
* Added option to hide posts which have no thumbnail.
* Added option to make the widget date link to the category page.
* Addd option to link to the category page below posts list.
* Added option show post author.
* Added option to format the outputted date string.

4.1.3
* Added option to hide widget, if category have currently no posts.

4.1.2
* Fixed hide title bug.

4.1.1
* Added Option to put thumbnail on top
* Added Option to show/hide the title
* Fixed no background bug.

4.1.0
* Added PHP5 Constructor 
* Added Option to allow/disallow widget CSS
* Now, compatible with WordPress 4.3
* Meet new plugin author [kometschuh](https://profiles.wordpress.org/kometschuh)

4.0
* Added CSS file for post styling 
* Now compaitable with latest versions of WordPress

3.3
* Fixed random sort bug.

3.2
* Added option to change ordering of posts. Defaults to showing newest posts first.

3.1
* Fixed a bug in the thumbnail size registration routine.

3.0
* Added support for WP 2.9's post thumbnail feature.
* Removed support for Simple Post Thumbnails plugin.
* Added option to show the post date.
* Added option to set the excerpt length.
* Added option to show the number of comments.

2.3
* Really tried to fix bug where wp_query global was getting over written by manually instantiating a WP_Query object

2.1
* Fixed bug where wp_query global was getting over written.

2.0
* Updated to use the WP 2.8 widget API.
* Added support for [Simple Post Thumbnails plugin](http://wordpress.org/extend/plugins/simple-post-thumbnails/)
