<?php
/*
 * Plugin Name: Post View Counter
 * Version: 1.0
 * Plugin URI: https://github.com/hlashbrooke/Post-View-Counter
 * Description: A counter to track how many times your posts are viewed.
 * Author: Hugh Lashbrooke
 * Author URI: https://hugh.blog/
 * Requires at least: 4.7
 * Tested up to: 4.8.2
 *
 * Text Domain: post-view-counter
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */


// Basic security check to ensure that WordPress is running
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Count a new post view
 * @return void
 */
add_action( 'wp', 'pvc_count_post_view' );
function pvc_count_post_view () {

	// Check if we're in a single post or page
	if( is_single() || is_page() ) {

		// Get the global post object
		global $post;

		// Check if a post ID exists
		if( isset( $post->ID ) ) {

			// Get the existing post view count (default to 0)
			$views = intval( get_post_meta( $post->ID, '_post_views', true ) );

			// Increment view count
			$views_updated = $views + 1;

			// Update stored view count
			update_post_meta( $post->ID, '_post_views', $views_updated, $views );
		}

	}
}






/**
 * Display the post view count on the post edit screen
 * @return void
 */
add_action( 'post_submitbox_misc_actions', 'pvc_display_post_views_meta' );
function pvc_display_post_views_meta () {

	// WordPress admin globals
	global $post, $pagenow;

	// Make sure we're on the post edit screen
	if( 'post.php' == $pagenow ) {

		// Get post view count (default to 0)
		$views = intval( get_post_meta( $post->ID, '_post_views', true ) );

		// Add HTML for displaying view count
		?>
 		<div class="misc-pub-section misc-pub-post-views" id="post-views">
 			<?php _e( 'Views:', 'post-view-counter' ); ?>
 			<strong><?php echo esc_html( $views ); ?></strong>
 		</div>
 		<?php
	}
}






/**
 * Add 'Views' section to admin bar on frontend for single posts
 * @param  object $wp_admin_bar WordPress admin bar object
 * @return void
 */
add_action( 'admin_bar_menu', 'pvc_display_post_views_admin_bar', 9999, 1 );
function pvc_display_post_views_admin_bar ( $wp_admin_bar ) {

	// Check if we're on a single post/page
	if( is_single() || is_page() ) {

		// Fetch the global post object
		global $post;

		// Check if a post ID exists and current user can edit this post
		if( isset( $post->ID ) && current_user_can( 'edit_post', $post->ID ) ) {

			// Get post view count (default to 0)
			$views = intval( get_post_meta( $post->ID, '_post_views', true ) );

			// Create post view count admin bar node
			$args = array(
				'id'    => 'view_counter',
				'title' => sprintf( _n( '1 View', '%s Views', $views, 'post-view-counter' ), $views ),
				'meta'  => array( 'class' => 'view-counter' )
			);

			// Add node to admin bar
			$wp_admin_bar->add_node( $args );
		}
	}
}






/**
 * Shortcode for displaying view count
 * @param  array  $atts Shortcode attributes
 * @return string 		Shortcode HTML
 */
add_shortcode( 'post_views', 'pvc_post_views_shortcode' );
function pvc_post_views_shortcode ( $atts = array() ) {

	// Parse shortcode parameters
	$atts = shortcode_atts( array(
		'post' => 0,
	), $atts, 'post_views' );

	// Set default output to empty string
	$output = '';

	// Get post ID to use in shortcode (default to 0)
	$post_id = 0;

	// First check shortcode attributes
	if( $atts['post'] ) {
		$post_id = $atts['post'];
	} else {

		// If no post is specified in the shortcode, then use the current post ID
		global $post;
		if( isset( $post->ID ) ) {
			$post_id = $post->ID;
		}

	}

	// Get shortcode output if post ID is present
	if( $post_id ) {

		// Get post view count (default to 0)
		$views = intval( get_post_meta( $post_id, '_post_views', true ) );

		// Generate output HTML for shortcode
		$output = '<span class="view-count">' . sprintf( __( 'Views: %d', 'post-view-counter' ), $views ) . '</span>';
	}

	// Return shortcode output
	return apply_filters( 'pvc_post_views_shortcode_output', $output );
}





/**
 * Register dashboard widgets
 * @return void
 */
add_action( 'wp_dashboard_setup', 'pvc_register_dashboard_widgets' );
function pvc_register_dashboard_widgets () {

	// Add widget to dashboard and set 'pvc_dashboard_widget' as the function to output the HTML
	wp_add_dashboard_widget( 'most-viewed-posts', apply_filters( 'pvc_dashboard_widget_title', __( 'Most Viewed Posts', 'post-view-counter' ) ), 'pvc_dashboard_widget' );
}

/**
 * Add content to dashboard widget
 * @return void
 */
function pvc_dashboard_widget () {

	// Define dashboard widget query arguments
	$args = array(
		'post_type'			  => 'post',
		'posts_per_page'      => 5,
		'no_found_rows'       => true,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'meta_key'			  => '_post_views',
		'orderby'			  => 'meta_value_num',
		'order' => 'ASC',
	);

	// Allow arguments to be filtered
	$args = apply_filters( 'dashboard_widget_most_viewed_posts_args', $args );

	// Fetch all relvant posts ordered by view count
	$posts = new WP_Query( $args );

	// Set default HTML output
	$html = '';

	// If query contains poosts, then generate post list
	if ( $posts->have_posts() ) {

		$html .= '<ul>';

		// Loop through posts
		while ( $posts->have_posts() ) {

			// Set up the post data for each loop
			$posts->the_post();

			// Get post view count (default to 0)
			$views = intval( get_post_meta( get_the_ID(), '_post_views', true ) );

			//  Set up format for post output
			$format = __( '<strong><span>%1$s views</span></strong> &mdash; <a href="%2$s">%3$s</a>', 'post-view-counter' );

			// Generate HTML with placeholder functions
			$html .= sprintf( '<li>' . $format . '</li>', $views, get_edit_post_link(), _draft_or_post_title() );
		}

		$html .= '</ul>';
	} else {

		// If there are no posts to display, then show default message
		$html .= '<p><em>' . __( 'No viewed posts.', 'post-view-counter' ) . '</em></p>';
	}

	echo $html;
}

// Make sure that shortcodes will work inside text widgets
add_filter( 'widget_text', 'do_shortcode' );