<?php
$post_type     = ! empty( $attributes['postType'] ) ? sanitize_key( $attributes['postType'] ) : 'post';
$posts_to_show = isset( $attributes['postsToShow'] ) ? (int) $attributes['postsToShow'] : 6;
$posts_per_page = isset( $attributes['postsPerPage'] ) ? (int) $attributes['postsPerPage'] : 10;
$order         = isset( $attributes['order'] ) && in_array( strtolower( $attributes['order'] ), array( 'asc', 'desc' ), true ) ? $attributes['order'] : 'desc';
$orderby       = isset( $attributes['orderBy'] ) ? sanitize_key( $attributes['orderBy'] ) : 'date';
$categories    = ! empty( $attributes['categories'] ) && is_array( $attributes['categories'] ) ? array_map( 'intval', $attributes['categories'] ) : array();
$columns       = isset( $attributes['columns'] ) ? max( 1, (int) $attributes['columns'] ) : 3;
$show_author   = ! empty( $attributes['displayAuthor'] );
$show_date     = ! empty($attributes['displayDate' ] );
$display_excerpt     = ! empty($attributes['displayExcerpt' ] );
$button_text     = $attributes['buttonText'];
$button_bg_color   = $attributes['buttonBgColor'];
$button_text_color     = $attributes['buttonTextColor' ];
$img_size      = ! empty( $attributes['featuredImageSizeSlug'] ) ? sanitize_key( $attributes['featuredImageSizeSlug'] ) : 'medium';

$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$offset = ( $paged - 1 ) * ( $posts_to_show === -1 ? $posts_per_page : $posts_to_show );
$per_page = $posts_to_show === -1 ? $posts_per_page : $posts_to_show;


// Debug: Log the selected post type
error_log( 'Post Grid Block - Selected post type: ' . $post_type );

// Verify post type exists and is public
if ( ! post_type_exists( $post_type ) ) {
	echo '<p>' . esc_html__( 'Invalid post type selected: ', 'post-grid' ) . esc_html( $post_type ) . '</p>';
	return;
}

$args = array(
	'post_type'           => $post_type,
	'posts_per_page'      => $per_page,
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
		'style' => sprintf( 'grid-template-columns: repeat(%d, minmax(0, 1fr));', $columns ),
		'data-post-type' => $post_type,
	)
);
?>
<div class="pg__grid-main">
	<!-- Debug info (remove in production) -->
	<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
		<!-- <p style="background: #f0f0f0; padding: 5px; font-size: 12px;">
			<strong>Debug:</strong> Post Type: <?php //echo esc_html( $post_type ); ?> | 
			Found: <?php //echo esc_html( $q->found_posts ); ?> posts
		</p> -->
	<?php endif; ?>
	<div <?php echo $wrapper_attributes; ?>>	
		<?php if ( $q->have_posts() ) : ?>
		<?php
		while ( $q->have_posts() ) :
			$q->the_post();
			?>
			<div class="pg__post" data-post-id="<?php echo get_the_ID(); ?>" data-post-type="<?php echo get_post_type(); ?>">
				<?php if ( has_post_thumbnail() ) : ?>
					<a class="pg__post__thumb" href="<?php the_permalink(); ?>">
						<?php the_post_thumbnail( $img_size ); ?>
					</a>
				<?php endif; ?>
				<div class="pg__post-content">
					<h3 class="pg__post__title">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</h3>

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
					<?php endif; 
					if($display_excerpt):
					?>
					<div class='pg__post_excerpt'><?php the_excerpt(); ?></div>
					<?php endif; ?>
					<a href="<?php the_permalink(); ?>" class="pg__button" style="background-color:<?php echo $button_bg_color; ?>; color:<?php echo $button_text_color ?>;"><?php echo $button_text; ?></a>
				</div>
			</div>
		<?php endwhile; wp_reset_postdata(); 
		?>
	</div>
	<?php
		if ( $posts_to_show === -1 && $q->max_num_pages > 1 ) {
			$prev_link = get_previous_posts_link( __('Prev', 'post-grid') );
			$next_link = get_next_posts_link( __('Next', 'post-grid'), $q->max_num_pages );
	?>
		<div class="pg__post-pagination">
			<span class="prev"><?php echo $prev_link; ?></span>
			<span class="next"><?php echo $next_link; ?></span>
		</div>
	<?php } ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No', 'post-grid' ); ?> <?php echo esc_html( $post_type ); ?> <?php esc_html_e( 'found.', 'post-grid' ); ?></p>
	<?php endif; ?>
</div>
