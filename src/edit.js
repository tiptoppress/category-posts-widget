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
	ComboboxControl,
	TextControl,
	ToggleControl,
	PanelBody,
	RadioControl,
	QueryControls,
	Disabled,
	__experimentalToggleGroupControl, ToggleGroupControl as stableToggleGroupControl,
	__experimentalToggleGroupControlOption, ToggleGroupControlOption as stableToggleGroupControlOption,
	__experimentalNumberControl, NumberControl as stableNumberControl,
	DatePicker,
	Popover,
	Button,
	PanelRow,
} from '@wordpress/components';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { dateI18n } from '@wordpress/date';

export const ToggleGroupControl = __experimentalToggleGroupControl || stableToggleGroupControl;
export const ToggleGroupControlOption = __experimentalToggleGroupControlOption || stableToggleGroupControlOption;
export const NumberControl = __experimentalNumberControl || stableNumberControl;

/**
 * Module Constants
 */
const CATEGORIES_LIST_QUERY = {
	per_page: -1,
};

export default function Edit({ attributes, setAttributes }) {
	const {
		hideTitle, title, titleLink, titleLinkUrl, titleLevel,
		order, orderBy, status, categories, num, offset, dateRange, startDate, endDate, daysAgo, excludeCurrentPost, hideNoThumb, sticky,
		disableThemeStyles,
		footerLinkText, footerLink
	} = attributes;
	const blockProps = useBlockProps({
		className: disableThemeStyles ? 'widget-title' : '',
	});
	const [categoriesList, setCategoriesList] = useState([]);
	const categorySuggestions = categoriesList.reduce(
		(accumulator, category) => ({
			...accumulator,
			[category.name]: category,
		}),
		{}
	);
	const selectCategories = (tokens) => {
		const hasNoSuggestion = tokens.some(
			(token) =>
				typeof token === 'string' && !categorySuggestions[token]
		);
		if (hasNoSuggestion) {
			return;
		}
		// Categories that are already will be objects, while new additions will be strings (the name).
		// allCategories nomalizes the array so that they are all objects.
		const allCategories = tokens.map((token) => {
			return typeof token === 'string'
				? categorySuggestions[token]
				: token;
		});
		// We do nothing if the category is not selected
		// from suggestions.
		if (includes(allCategories, null)) {
			return false;
		}
		setAttributes({ categories: allCategories });
	};

	// Suggestion list
	const isStillMounted = useRef();

	useEffect(() => {
		isStillMounted.current = true;

		apiFetch({
			path: addQueryArgs(`/wp/v2/categories`, CATEGORIES_LIST_QUERY),
		})
			.then((data) => {
				if (isStillMounted.current) {
					setCategoriesList(data);
				}
			})
			.catch(() => {
				if (isStillMounted.current) {
					setCategoriesList([]);
				}
			});

		return () => {
			isStillMounted.current = false;
		};
	}, []);

	const statusOptions = [
		{
			value: 'default',
			label: 'WordPress Default',
		},
		{
			value: 'publish',
			label: 'Published',
		},
		{
			value: 'future',
			label: 'Scheduled',
		},
		{
			value: 'private',
			label: 'Private',
		},
		{
			value: 'publish,future',
			label: 'Published or Scheduled',
		},
		{
			value: 'private,publish',
			label: 'Published or Private',
		},
		{
			value: 'private,future',
			label: 'Private or Scheduled',
		},
		{
			value: 'private,publish,future',
			label: 'Published, Private or Scheduled',
		},
	];

	const dateRangeOptions = [
		{
			value: 'off',
			label: 'Off',
		},
		{
			value: 'days_ago',
			label: 'Days ago',
		},
		{
			value: 'between_dates',
			label: 'Between dates',
		},
	];

	const [openStartDatePopup, setOpenStartDatePopup] = useState( false );
	const [openEndDatePopup, setOpenEndDatePopup] = useState( false );

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Title', 'category-posts')} initialOpen={ false }>
					<ToggleControl
						label={__('Hide title', 'category-posts')}
						checked={hideTitle}
						onChange={() =>
							setAttributes({
								hideTitle: !hideTitle,
							})
						}
					/>
					<TextControl
						label={__('Title', 'category-posts')}
						value={title}
						onChange={(title) =>
							setAttributes({
								title,
							})}
					/>
					<ToggleControl
						label={__('Make widget title link', 'category-posts')}
						checked={titleLink}
						onChange={() =>
							setAttributes({
								titleLink: !titleLink,
							})
						}
					/>
					<TextControl
						label={__('Title link URL', 'category-posts')}
						value={titleLinkUrl}
						onChange={(titleLinkUrl) =>
							setAttributes({
								titleLinkUrl,
							})}
					/>
					<ToggleGroupControl
						label="Heading level"
						value={titleLevel}
						isBlock
						isAdaptiveWidth
						help={"Also, try 'Disable Theme's styles' on General tab to avoid rendering commonly used CSS classes such here widget-title, which often used in Themes to write their CSS selectors and may affect the design."}
						onChange={(titleLevel) =>
							setAttributes({
								titleLevel,
							})}
					>
						<ToggleGroupControlOption value="initial" label="Initial" />
						<ToggleGroupControlOption value="H1" label="H1" />
						<ToggleGroupControlOption value="H2" label="H2" />
						<ToggleGroupControlOption value="H3" label="H3" />
						<ToggleGroupControlOption value="H4" label="H4" />
						<ToggleGroupControlOption value="H5" label="H5" />
						<ToggleGroupControlOption value="H6" label="H6" />
					</ToggleGroupControl>
				</PanelBody>
				<PanelBody title={__('Filter', 'category-posts', 'category-posts')} initialOpen={ false }>
					<QueryControls
						{...{ order, orderBy }}
						onOrderChange={(value) =>
							setAttributes({ order: value })
						}
						onOrderByChange={(value) =>
							setAttributes({ orderBy: value })
						}
						categorySuggestions={categorySuggestions}
						onCategoryChange={selectCategories}
						selectedCategories={categories}
					/>
					<br />
					<ComboboxControl
						label={ __( 'Status' ) }
						options={ statusOptions }
						value={ status }
						onChange={( newStatus) =>
							setAttributes({
								status: newStatus,
							})
						}
						allowReset={ false }
					/>
					<NumberControl
						label={ __( 'Number of posts to show' ) }
						onChange={( newNum) =>
							setAttributes({
								num: newNum,
							})
						}
						value={ num }
						min={1}
						allowReset={ false }
					/>
					<NumberControl
						label={ __( 'Start with post' ) }
						onChange={( newOffset) =>
							setAttributes({
								offset: newOffset,
							})
						}
						value={ offset }
						min={1}
						allowReset={ false }
					/>
					<ComboboxControl
						label={ __( 'Date Range' ) }
						options={ dateRangeOptions }
						value={ dateRange }
						onChange={( newDateRange) =>
							setAttributes({
								dateRange: newDateRange,
							})
						}
						allowReset={ false }
					/>
					{dateRange === 'days_ago' && (
						<NumberControl
							label={ __( 'Up to' ) }
							onChange={( newDaysAgo) =>
								setAttributes({
									daysAgo: newDaysAgo,
								})
							}
							value={ daysAgo }
							min={1}
							allowReset={ false }
						/>
					)}
					{dateRange === 'between_dates' && (
						<>
							<PanelRow>
								<label>After</label>
								<Button isLink={true} onClick={() => setOpenStartDatePopup( ! openStartDatePopup )}>
									{ startDate ? dateI18n( 'j.m.Y', startDate ) : "tt.mm.jjjj" }
								</Button>
								{ openStartDatePopup && (
									<Popover onClose={ setOpenStartDatePopup.bind( null, false )}>
										<DatePicker
											onChange={( newStartDate) =>
												setAttributes({
													startDate: newStartDate,
												})
											}
											currentDate={ startDate }
											startOfWeek={1}
										/>
									</Popover>
								) }
							</PanelRow>
							<PanelRow>
								<label>Before</label>
								<Button isLink={true} onClick={() => setOpenEndDatePopup( ! openEndDatePopup )}>
									{ endDate ? dateI18n( 'j.m.Y', endDate ) : "tt.mm.jjjj" }
								</Button>
								{ openEndDatePopup && (
									<Popover onClose={ setOpenEndDatePopup.bind( null, false )}>
										<DatePicker
											onChange={( newEndDate) =>
												setAttributes({
													endDate: newEndDate,
												})
											}
											currentDate={ endDate }
											startOfWeek={1}
										/>
									</Popover>
								) }
							</PanelRow>
						</>
					)}
					<br />
					<ToggleControl
						label={__('Exclude current post', 'category-posts')}
						checked={hideNoThumb}
						onChange={() =>
							setAttributes({
								hideNoThumb: !hideNoThumb,
							})
						}
					/>
					<ToggleControl
						label={__('Exclude posts which have no thumbnail', 'category-posts')}
						checked={excludeCurrentPost}
						onChange={() =>
							setAttributes({
								excludeCurrentPost: !excludeCurrentPost,
							})
						}
					/>
					<ToggleControl
						label={__('Start with sticky posts', 'category-posts')}
						checked={sticky}
						onChange={() =>
							setAttributes({
								sticky: !sticky,
							})
						}
					/>
				</PanelBody>
				<PanelBody title={__('Post details', 'category-posts')} initialOpen={ false }>
				</PanelBody>
				<PanelBody title={__('General', 'category-posts')} initialOpen={ false }>
					<ToggleControl
						label={__('Disable the built-in CSS', 'category-posts')}
						checked={disableThemeStyles}
						onChange={() =>
							setAttributes({
								disableThemeStyles: !disableThemeStyles,
							})
						}
					/>
					<ToggleControl
						label={__('Disable only font styles', 'category-posts')}
						checked={disableThemeStyles}
						onChange={() =>
							setAttributes({
								disableThemeStyles: !disableThemeStyles,
							})
						}
					/>
					<ToggleControl
						label={__('Disable Theme\'s styles', 'category-posts')}
						checked={disableThemeStyles}
						onChange={() =>
							setAttributes({
								disableThemeStyles: !disableThemeStyles,
							})
						}
					/>
				</PanelBody>
				<PanelBody title={__('Footer', 'category-posts')} initialOpen={ false }>
					<TextControl
						label={__('Footer link text', 'category-posts')}
						value={footerLinkText}
						onChange={(footerLinkText) =>
							setAttributes({
								footerLinkText,
							})}
					/>
					<TextControl
						label={__('Footer link URL', 'category-posts')}
						value={footerLink}
						onChange={(footerLink) =>
							setAttributes({
								footerLink,
							})}
					/>
				</PanelBody>
			</InspectorControls>
			<div
				{...useBlockProps()}
				className={blockProps.className}
			>
				<Disabled>
					<ServerSideRender
						block="tiptip/category-posts-block"
						attributes={attributes}
					/>
				</Disabled>
			</div>
		</>
	);
}
