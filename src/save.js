
import { __ } from '@wordpress/i18n';
import { RichText, useBlockProps } from '@wordpress/block-editor';
import classnames from 'classnames';

/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#save
 *
 * @return {WPElement} Element to render.
 */
export default function save(props, className) {
	const {
		hideTitle, title, titleLink, titleLevel,
		disableThemeStyles,
		showPostCounts,
		displayAsDropdown,
		groupBy,
		order, orderBy,
		categories 
	} = props.attributes;
	const wrapperClasses = classnames( className ); console.log( title );

	return <div { ...useBlockProps.save( { className: wrapperClasses } ) }>
			<RichText.Content
				tagName="h2"
				value={ title }
				allowedFormats={ [ 'core/bold', 'core/italic' ] }
				onChange={ ( title ) => setAttributes( { title } ) }
				placeholder={ __( 'Category Posts', 'category-posts' ) }
			/>
	</div>;
}
