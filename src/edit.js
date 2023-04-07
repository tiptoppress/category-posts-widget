/**
 * External dependencies
 */
import { includes } from 'lodash';
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import { 
	TextControl, 
	ToggleControl, 
	PanelBody, 
	RadioControl, 
	QueryControls, 
	Disabled,
	__experimentalToggleGroupControl, ToggleGroupControl as stableToggleGroupControl,
	__experimentalToggleGroupControlOption, ToggleGroupControlOption as stableToggleGroupControlOption
} from '@wordpress/components';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

export const ToggleGroupControl       = __experimentalToggleGroupControl || stableToggleGroupControl;
export const ToggleGroupControlOption = __experimentalToggleGroupControlOption || stableToggleGroupControlOption;

/**
 * Module Constants
 */
const CATEGORIES_LIST_QUERY = {
	per_page: -1,
};

export default function Edit( { attributes, setAttributes } ) {
	const {
		hideTitle, title, titleLink, titleLinkUrl, titleLevel,
		disableThemeStyles,
		showPostCounts,
		displayAsDropdown,
		groupBy,
		order, orderBy,
		categories,
		footerLinkText,
		footerLink
	} = attributes;
	const titleTagName = 'h' + titleLevel;
	const blockProps = useBlockProps( {
		className:  disableThemeStyles ? 'widget-title' : '',
	} );
	const [ categoriesList, setCategoriesList ] = useState( [] );
	const categorySuggestions = categoriesList.reduce(
		( accumulator, category ) => ( {
			...accumulator,
			[ category.name ]: category,
		} ),
		{}
	);
	const selectCategories = ( tokens ) => {
		const hasNoSuggestion = tokens.some(
			( token ) =>
				typeof token === 'string' && ! categorySuggestions[ token ]
		);
		if ( hasNoSuggestion ) {
			return;
		}
		// Categories that are already will be objects, while new additions will be strings (the name).
		// allCategories nomalizes the array so that they are all objects.
		const allCategories = tokens.map( ( token ) => {
			return typeof token === 'string'
				? categorySuggestions[ token ]
				: token;
		} );
		// We do nothing if the category is not selected
		// from suggestions.
		if ( includes( allCategories, null ) ) {
			return false;
		}
		setAttributes( { categories: allCategories } );
	};

	// Suggestion list
	const isStillMounted = useRef();

	useEffect( () => {
		isStillMounted.current = true;

		apiFetch( {
			path: addQueryArgs( `/wp/v2/categories`, CATEGORIES_LIST_QUERY ),
		} )
			.then( ( data ) => {
				if ( isStillMounted.current ) {
					setCategoriesList( data );
				}
			} )
			.catch( () => {
				if ( isStillMounted.current ) {
					setCategoriesList( [] );
				}
			} );

		return () => {
			isStillMounted.current = false;
		};
	}, [] );

	const coreBlocks = wp.blocks.getBlockTypes().filter( ( block ) => {
		return (
			block.name === 'core/query'
		);
	} )
	//console.log(coreBlocks[0].settings.edit);
	//return coreBlocks[0].edit;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Title', 'category-posts' ) }>
					<ToggleControl
						label={ __( 'Hide title', 'category-posts' ) }
						checked={ hideTitle }
						onChange={ () =>
							setAttributes( {
								hideTitle: ! hideTitle,
							} )
						}
					/>
					<TextControl
						label={ __( 'Title', 'category-posts' ) }
						value={ title }
						onChange={ ( title ) =>
							setAttributes( {
								title,
							} ) }
					/>
					<ToggleControl
						label={ __( 'Make widget title link', 'category-posts' ) }
						checked={ titleLink }
						onChange={ () =>
							setAttributes( {
								titleLink: ! titleLink,
							} )
						}
					/>
					<TextControl
						label={ __( 'Title link URL', 'category-posts' ) }
						value={ titleLinkUrl  }
						onChange={ ( titleLinkUrl ) =>
							setAttributes( {
								titleLinkUrl,
							} ) }
					/>
					<ToggleGroupControl 
						label="Heading level" 
						value={ titleLevel }
						isBlock 
						isAdaptiveWidth 
						help={"Also, try 'Disable Theme's styles' on General tab to avoid rendering commonly used CSS classes such here widget-title, which often used in Themes to write their CSS selectors and may affect the design."}
						onChange={ ( titleLevel ) =>
							setAttributes( {
								titleLevel,
							} ) }
						>
						<ToggleGroupControlOption value="initial" label="Initial" />
						<ToggleGroupControlOption value="h1" label="H1" />
						<ToggleGroupControlOption value="h2" label="H2" />
						<ToggleGroupControlOption value="h3" label="H3" />
						<ToggleGroupControlOption value="h4" label="H4" />
						<ToggleGroupControlOption value="h5" label="H5" />
						<ToggleGroupControlOption value="h6" label="H6" />
					</ToggleGroupControl>
				</PanelBody>
				<PanelBody title={ __( 'Filter', 'category-posts', 'category-posts' ) }>
					<RadioControl
						label={ __( 'Group by', 'category-posts' ) }
						selected={ groupBy }
						options={ [
							{ label: 'Month', value: 'monthly' },
							{ label: 'Year', value: 'yearly' },
						] }
						onChange={ ( groupBy ) =>
							setAttributes( {
								groupBy,
							} )
						}
					/>
					<ToggleControl
						label={ __( 'Display as dropdown', 'category-posts' ) }
						checked={ displayAsDropdown }
						onChange={ () =>
							setAttributes( {
								displayAsDropdown: ! displayAsDropdown,
							} )
						}
					/>
					<ToggleControl
						label={ __( 'Show post counts', 'category-posts' ) }
						checked={ showPostCounts }
						onChange={ () =>
							setAttributes( {
								showPostCounts: ! showPostCounts,
							} )
						}
					/>
					<QueryControls
						{ ...{ order, orderBy } }
						onOrderChange={ ( value ) =>
							setAttributes( { order: value } )
						}
						onOrderByChange={ ( value ) =>
							setAttributes( { orderBy: value } )
						}
						categorySuggestions={ categorySuggestions }
						onCategoryChange={ selectCategories }
						selectedCategories={ categories }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Post details', 'category-posts' ) }>
				</PanelBody>
				<PanelBody title={ __( 'General', 'category-posts' ) }>
					<ToggleControl
						label={ __( 'Disable the built-in CSS', 'category-posts' ) }
						checked={ disableThemeStyles }
						onChange={ () =>
							setAttributes( {
								disableThemeStyles: ! disableThemeStyles,
							} )
						}
					/>
					<ToggleControl
						label={ __( 'Disable only font styles', 'category-posts' ) }
						checked={ disableThemeStyles }
						onChange={ () =>
							setAttributes( {
								disableThemeStyles: ! disableThemeStyles,
							} )
						}
					/>
					<ToggleControl
						label={ __( 'Disable Theme\'s styles', 'category-posts' ) }
						checked={ disableThemeStyles }
						onChange={ () =>
							setAttributes( {
								disableThemeStyles: ! disableThemeStyles,
							} )
						}
					/>
				</PanelBody>
				<PanelBody title={ __( 'Footer', 'category-posts' ) }>
					<TextControl
						label={ __( 'Footer link text', 'category-posts' ) }
						value={ footerLinkText  }
						onChange={ ( footerLinkText ) =>
							setAttributes( {
								footerLinkText,
							} ) }
					/>
					<TextControl
						label={ __( 'Footer link URL', 'category-posts' ) }
						value={ footerLink  }
						onChange={ ( footerLink ) =>
							setAttributes( {
								footerLink,
							} ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div 
				{ ...useBlockProps() } 
				className={ blockProps.className }
			>
				<Disabled>
					<ServerSideRender
						block="tiptip/category-posts-block"
						attributes={ attributes }
					/>
				</Disabled>
			</div>
		</>
	);
}
