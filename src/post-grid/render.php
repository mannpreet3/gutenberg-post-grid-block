<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
$post_type     = ! empty( $attributes['postType'] ) ? sanitize_key( $attributes['postType'] ) : 'post';
$posts_to_show = isset( $attributes['postsToShow'] ) ? (int) $attributes['postsToShow'] : 6;
$order         = isset( $attributes['order'] ) && in_array( strtolower( $attributes['order'] ), array( 'asc', 'desc' ), true ) ? $attributes['order'] : 'desc';
$orderby       = isset( $attributes['orderBy'] ) ? sanitize_key( $attributes['orderBy'] ) : 'date';
$categories    = ! empty( $attributes['categories'] ) && is_array( $attributes['categories'] ) ? array_map( 'intval', $attributes['categories'] ) : array();
$columns       = isset( $attributes['columns'] ) ? max( 1, (int) $attributes['columns'] ) : 3;
$show_author   = ! empty( $attributes['displayAuthor'] );
$show_date     = ! empty( $attributes['displayDate'] );
$img_size      = ! empty( $attributes['featuredImageSizeSlug'] ) ? sanitize_key( $attributes['featuredImageSizeSlug'] ) : 'medium';

// Debug: Log the selected post type
error_log( 'Post Grid Block - Selected post type: ' . $post_type );

// Verify post type exists and is public
if ( ! post_type_exists( $post_type ) ) {
	echo '<p>' . esc_html__( 'Invalid post type selected: ', 'post-grid' ) . esc_html( $post_type ) . '</p>';
	return;
}

$args = array(
	'post_type'           => $post_type,
	'posts_per_page'      => $posts_to_show,
	'orderby'             => $orderby,
	'order'               => $order,
	'ignore_sticky_posts' => true,
	'post_status'         => 'publish',
);

// Only add category filter for 'post' post type
if ( $post_type === 'post' && $categories ) {
	$args['category__in'] = $categories;
}

// Debug: Log the query args
error_log( 'Post Grid Block - Query args: ' . print_r( $args, true ) );

$q = new WP_Query( $args );

// Debug: Log the found posts count
error_log( 'Post Grid Block - Found posts: ' . $q->found_posts );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'style' => sprintf( '--grid-columns:%d;', $columns ),
		'data-post-type' => $post_type,
	)
);
?>
<div <?php echo $wrapper_attributes; ?>>
	<!-- Debug info (remove in production) -->
	<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
		<!-- <p style="background: #f0f0f0; padding: 5px; font-size: 12px;">
			<strong>Debug:</strong> Post Type: <?php //echo esc_html( $post_type ); ?> | 
			Found: <?php //echo esc_html( $q->found_posts ); ?> posts
		</p> -->
	<?php endif; ?>

	<?php if ( $q->have_posts() ) : ?>
		<?php
		while ( $q->have_posts() ) :
			$q->the_post();
			?>
			<article class="pg__post" data-post-id="<?php echo get_the_ID(); ?>" data-post-type="<?php echo get_post_type(); ?>">
				<?php if ( has_post_thumbnail() ) : ?>
					<a class="pg__post__thumb" href="<?php the_permalink(); ?>">
						<?php the_post_thumbnail( $img_size ); ?>
					</a>
				<?php endif; ?>

				<h3 class="pg__post__title">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h3>

				<!-- Debug info for each post -->
				<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
					<p style="font-size: 11px; color: #666;">
						ID: <?php echo get_the_ID(); ?> | Type: <?php echo get_post_type(); ?>
					</p>
				<?php endif; ?>

				<?php if ( $show_author || $show_date ) : ?>
					<div class="pg__post__meta">
						<?php if ( $show_date ) : ?>
							<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
								<?php echo esc_html( get_the_date() ); ?>
							</time>
						<?php endif; ?>
						<?php if ( $show_author ) : ?>
							<span class="pg__post__byline">
								<?php echo esc_html_x( 'by', 'byline', 'post-grid' ); ?>
								<?php the_author(); ?>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</article>
		<?php endwhile; wp_reset_postdata(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No', 'post-grid' ); ?> <?php echo esc_html( $post_type ); ?> <?php esc_html_e( 'found.', 'post-grid' ); ?></p>
	<?php endif; ?>
</div>
