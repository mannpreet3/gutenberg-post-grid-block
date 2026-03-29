/**displayAuthor
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

import {
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
	TextControl,
	Spinner,
	Placeholder,
} from '@wordpress/components';


/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	const {
	  postType = 'post',
	  postsToShow = 6,
	  columns = 3,
	  order = 'desc',
	  orderBy = 'date',
	  categories = [],
	  displayAuthor = true,
	  displayDate = true,
	  featuredImageSizeSlug = 'medium',
	} = attributes;
  
	// Get all public post types
	const postTypes = useSelect((select) => {
	  const types = select('core').getPostTypes({ per_page: -1 });
	  return types?.filter((type) => type.viewable && type.slug !== 'attachment') || [];
	}, []);
  
	// Build query with cache busting
	const query = {
	  per_page: postsToShow,
	  order,
	  orderby: orderBy,
	  _embed: true,
	  // Add timestamp to bust cache when post type changes
	  _timestamp: Date.now(),
	};
  
	// Only add categories filter for 'post' post type
	if (postType === 'post' && categories?.length) {
	  query.categories = categories;
	}
  
	// Get posts for the selected post type
	const { posts, isLoading } = useSelect(
	  (select) => {
		if (!postType) {
		  return { posts: null, isLoading: false };
		}
  
		// Force invalidate cache when post type changes
		const coreData = select('core');
		
		// Get posts
		const fetchedPosts = coreData.getEntityRecords('postType', postType, query);
		const isResolving = coreData.isResolving('getEntityRecords', ['postType', postType, query]);
		
		return {
		  posts: fetchedPosts,
		  isLoading: isResolving || fetchedPosts === undefined,
		};
	  },
	  [postType, postsToShow, order, orderBy, JSON.stringify(categories)]
	);
  
	// Force cache invalidation when post type changes
	useEffect(() => {
	  if (postType) {
		// Invalidate cache for the previous post type queries
		wp.data.dispatch('core').invalidateResolution('getEntityRecords', [
		  'postType',
		  postType,
		]);
	  }
	}, [postType]);
  
	const blockProps = useBlockProps({
	  className: 'pg__post-grid',
	  style: { ['--grid-columns']: columns },
	});
  
	// Category input only for 'post' post type
	const categoryIdsText = categories?.length ? categories.join(',') : '';
  
	function parseCategoryIds(text) {
	  return text
		.split(',')
		.map((v) => parseInt(v.trim(), 10))
		.filter((n) => !Number.isNaN(n));
	}
  
	// Create post type options
	const postTypeOptions = postTypes.map((type) => ({
	  label: type.name,
	  value: type.slug,
	}));

	// Utility function to clear all post-related cache
	const clearPostCache = () => {
		const coreData = wp.data.dispatch('core');
		
		// Clear all entity records cache
		['post', 'page'].forEach(postType => {
			coreData.invalidateResolution('getEntityRecords', ['postType', postType]);
		});
		
		// Clear post types cache
		coreData.invalidateResolution('getPostTypes');
	};

  
	// When post type changes, reset categories and invalidate cache
	const handlePostTypeChange = (newPostType) => {
		// Clear all cache
		clearPostCache();
		
		// Small delay to ensure cache is cleared before new request
		setTimeout(() => {
			setAttributes({ 
				postType: newPostType,
				categories: [] 
			});
		}, 100);
	};
  
	// Debug info (remove in production)
	console.log('Current postType:', postType);
	console.log('Fetched posts:', posts);
	console.log('Posts length:', posts?.length || 0);
  
	return (
	  <>
		<InspectorControls>
		  <PanelBody title={__('Query', 'post-grid')} initialOpen={true}>
			<SelectControl
			  label={__('Post Type', 'post-grid')}
			  value={postType}
			  options={[
				{ label: __('Select Post Type...', 'post-grid'), value: '' },
				...postTypeOptions,
			  ]}
			  onChange={handlePostTypeChange}
			/>
			
			<RangeControl
			  label={__('Posts to show', 'post-grid')}
			  min={1}
			  max={12}
			  value={postsToShow}
			  onChange={(val) => setAttributes({ postsToShow: val })}
			/>
			
			<SelectControl
			  label={__('Order by', 'post-grid')}
			  value={orderBy}
			  options={[
				{ label: __('Date', 'post-grid'), value: 'date' },
				{ label: __('Title', 'post-grid'), value: 'title' },
				{ label: __('Modified', 'post-grid'), value: 'modified' },
				{ label: __('Menu Order', 'post-grid'), value: 'menu_order' },
				{ label: __('Random', 'post-grid'), value: 'rand' },
			  ]}
			  onChange={(val) => setAttributes({ orderBy: val })}
			/>
			
			<SelectControl
			  label={__('Order', 'post-grid')}
			  value={order}
			  options={[
				{ label: __('Descending', 'post-grid'), value: 'desc' },
				{ label: __('Ascending', 'post-grid'), value: 'asc' },
			  ]}
			  onChange={(val) => setAttributes({ order: val })}
			/>
  
			{/* Categories field only for 'post' post type */}
			{postType === 'post' && (
			  <TextControl
				label={__('Category IDs (comma-separated)', 'post-grid')}
				help={__('Example: 2,5,7', 'post-grid')}
				value={categoryIdsText}
				onChange={(text) => setAttributes({ categories: parseCategoryIds(text) })}
			  />
			)}
		  </PanelBody>
  
		  <PanelBody title={__('Display', 'post-grid')} initialOpen={false}>
			<RangeControl
			  label={__('Columns', 'post-grid')}
			  min={1}
			  max={6}
			  value={columns}
			  onChange={(val) => setAttributes({ columns: val })}
			/>
			<ToggleControl
			  label={__('Show author', 'post-grid')}
			  checked={displayAuthor}
			  onChange={(val) => setAttributes({ displayAuthor: val })}
			/>
			<ToggleControl
			  label={__('Show date', 'post-grid')}
			  checked={displayDate}
			  onChange={(val) => setAttributes({ displayDate: val })}
			/>
			<SelectControl
			  label={__('Featured image size', 'post-grid')}
			  value={featuredImageSizeSlug}
			  options={[
				{ label: 'Thumbnail', value: 'thumbnail' },
				{ label: 'Medium', value: 'medium' },
				{ label: 'Large', value: 'large' },
				{ label: 'Full', value: 'full' },
			  ]}
			  onChange={(val) => setAttributes({ featuredImageSizeSlug: val })}
			/>
		  </PanelBody>
		</InspectorControls>
  
		<div {...blockProps}>
		  {!postType && (
			<Placeholder label={__('Post Grid', 'post-grid')}>
			  {__('Please select a post type to display.', 'post-grid')}
			</Placeholder>
		  )}
		  
		  {postType && isLoading && (
			<Placeholder label={__('Post Grid', 'post-grid')}>
			  <Spinner />
			</Placeholder>
		  )}
		  
		  {postType && !isLoading && (!posts || posts.length === 0) && (
			<Placeholder label={__('Post Grid', 'post-grid')}>
			  {__(`No ${postType}s found.`, 'post-grid')}
			</Placeholder>
		  )}
		  
		  {/* {postType && !isLoading && Array.isArray(posts) && (
			<div className="pg__post-grid-debug">
			  <p><small>Showing: {posts.length} {postType}(s)</small></p>
			</div>
		  )} */}
		  
		  {postType && !isLoading && Array.isArray(posts) && posts.map((post) => {
			const title = post.title?.rendered || __('(No title)', 'post-grid');
			const link = post.link;
			
			// Try to get featured image URL from _embed
			let imgUrl;
			const media = post?._embedded?.['wp:featuredmedia']?.[0];
			if (media) {
			  const sizes = media?.media_details?.sizes || {};
			  imgUrl = sizes?.[featuredImageSizeSlug]?.source_url || media?.source_url;
			}
			
			const dateStr = post.date_gmt || post.date;
			
			return (
			  <div key={post.id} className="pg__post" data-post-type={post.type}>
				{imgUrl && (
				  <a href={link} className="pg__post__thumb" target="_blank" rel="noreferrer">
					<img src={imgUrl} alt="" />
				  </a>
				)}
				<h3 className="pg__post__title">
				  <a href={link} target="_blank" rel="noreferrer" dangerouslySetInnerHTML={{ __html: title }} />
				</h3>
				<p className="pg__post-type-debug">Type: {post.type}</p>
				{(displayDate || displayAuthor) && (
				  <div className="pg__post__meta">
					{displayDate && dateStr && (
					  <time>{new Date(dateStr).toLocaleDateString()}</time>
					)}
					{displayAuthor && post._embedded?.author?.[0]?.name && (
					  <span className="pg__post__byline"> {__('by', 'post-grid')} {post._embedded.author[0].name}</span>
					)}
				  </div>
				)}
			  </div>
			);
		  })}
		</div>
	  </>
	);
  }