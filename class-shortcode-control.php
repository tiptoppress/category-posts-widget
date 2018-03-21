<?php
/**
 * Costumizer Shortcode control class implementation.
 *
 * @package categoryposts.
 *
 * @since 4.9
 */

namespace categoryPosts;

// Don't call the file directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Costumizer Shortcode control.
 *
 * @since 4.9
 */
class ShortCode_Control extends \WP_Customize_Control {

	/**
	 * The form that should be displayed in the control.
	 *
	 * @var string
	 *
	 * @since 4.7
	 */
	public $form;

	/**
	 * The suffix of the title to be displayed in the control (unescaped).
	 *
	 * @var string
	 *
	 * @since 4.7
	 */
	public $title_postfix;

	/**
	 * Render the control.
	 *
	 * @since 4.6
	 */
	public function render_content() {
		$widget_title = 'Category Posts Shortcode' . $this->title_postfix;
		?>
		<div class="widget-top">
		<div class="widget-title"><h3><?php echo esc_html( $widget_title ); ?><span class="in-widget-title"></span></h3></div>
		</div>
		<div class="widget-inside" style="display: block;">
			<div class="form">
				<div class="widget-content">
					<?php echo $this->form; // Xss off. ?>
				</div>
			</div>
		</div>
		<?php
	}
}
