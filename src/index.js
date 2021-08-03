
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

import './style.scss';

import Edit from './edit';
import save from './save';

import metadata from './../block.json';
const { name } = metadata;

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/#registering-a-block
 */
registerBlockType( name, {
	...metadata,

	/**
	 * This is a short description for your block, can be translated with `i18n` functions.
	 * It will be shown in the Block Tab in the Settings Sidebar.
	 */
	description: __(
		'Adds a widget that shows the most recent posts from a single category.',
		'category-posts'
	),

	keywords: [
		__( 'category' ),
		__( 'categories' ),
		__( 'posts widget' ),
		__( 'recent posts' ),
		__( 'excerpt' ),
		__( 'tiptoppress' ),
	],

	example: {
		attributes: {
			values:
				'<ul><a>January 2021</a><a>December 2020</a><a>November 2020</a><a>October 2020</a></ul>',
			showPostCounts: 
				true,
		},
	},

	transforms: {
		from: [
			{
				type: 'block',
				blocks: [ 'core/legacy-widget', 'core/paragraph' ],
				isMatch: ( { idBase, instance } ) => {
					if ( ! instance?.raw ) {
						// Can't transform if raw instance is not shown in REST API.
						return false;
					}
					return idBase === 'category-posts';
				},
				transform: ( { instance } ) => { console.log(instance);
					return createBlock( 'tiptip/category-posts-block', {
						instance,
						order: instance.raw.asc_sort_order ? 'asc' : 'desc',
					} );
				},

			},
		]
	},

	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	/**
	 * @see ./save.js
	 */
	save: () => {
		const blockProps = useBlockProps.save();
		return <div { ...blockProps }> Hello in Save.</div>;
	},
} );
