<?php
 
 namespace categoryPosts;
 
if ( ! defined( 'ABSPATH' ) )
    exit;
 
if ( ! class_exists( '_WP_Editors' ) )
    require( ABSPATH . WPINC . '/class-wp-editor.php' );
 
 /**
 *  register translations for the tinymce shortcode creation button and dialog
 *  
 *  @since 4.7
 */
function tinymce_translation() {
    $strings = array(
        'name' => __('Name', TEXTDOMAIN),
        'tooltip' => __('Insert Category Posts shortcode', TEXTDOMAIN),
		'title' => __('Category Posts Insert Shortcode', TEXTDOMAIN),
		'hide_message' => __('Hide the button', TEXTDOMAIN),
		'profiile_url' => get_edit_user_link().'#'.__NAMESPACE__,
    );
 
    $locale = \_WP_Editors::$mce_locale;
    $translated = 'tinyMCE.addI18n("' . $locale . '.' . TEXTDOMAIN.'", ' . json_encode( $strings ) . ");\n";
 
    return $translated;
}
 
$strings = tinymce_translation();

