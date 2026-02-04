<?php
/**
 * Listing Archive Template.
 *
 * This template displays the listing archive/search results page.
 *
 * This template can be overridden by copying it to:
 * yourtheme/all-purpose-directory/archive-listing.php
 *
 * @package APD\Templates
 * @since   1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// Get template loader instance.
$template_loader = new \APD\Core\TemplateLoader();

// Get current view mode.
$current_view = $template_loader->get_current_view();
$grid_columns = $template_loader->get_grid_columns();

/**
 * Fires before the listing archive content.
 *
 * @since 1.0.0
 */
do_action( 'apd_before_archive' );
?>

<div class="apd-archive-wrapper">

	<?php
	/**
	 * Fires at the start of the archive wrapper.
	 *
	 * @since 1.0.0
	 */
	do_action( 'apd_archive_wrapper_start' );
	?>

	<header class="apd-archive-header">
		<h1 class="apd-archive-title"><?php echo esc_html( $template_loader->get_archive_title() ); ?></h1>

		<?php
		$description = $template_loader->get_archive_description();
		if ( ! empty( $description ) ) :
			?>
			<div class="apd-archive-description">
				<?php echo wp_kses_post( $description ); ?>
			</div>
		<?php endif; ?>
	</header>

	<?php
	/**
	 * Fires before the search form.
	 *
	 * @since 1.0.0
	 */
	do_action( 'apd_before_archive_search_form' );
	?>

	<div class="apd-archive-search">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo apd_render_search_form();
		?>
	</div>

	<?php
	/**
	 * Fires after the search form.
	 *
	 * @since 1.0.0
	 */
	do_action( 'apd_after_archive_search_form' );
	?>

	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo apd_render_active_filters();
	?>

	<div class="apd-archive-toolbar">
		<div class="apd-archive-toolbar__left">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $template_loader->render_results_count();
			?>
		</div>
		<div class="apd-archive-toolbar__right">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $template_loader->render_view_switcher();
			?>
		</div>
	</div>

	<?php
	/**
	 * Fires before the listings loop.
	 *
	 * @since 1.0.0
	 */
	do_action( 'apd_before_archive_loop' );
	?>

	<?php if ( have_posts() ) : ?>

		<div class="apd-listings apd-listings--<?php echo esc_attr( $current_view ); ?> apd-listings--columns-<?php echo esc_attr( (string) $grid_columns ); ?>"
			data-view="<?php echo esc_attr( $current_view ); ?>"
			data-columns="<?php echo esc_attr( (string) $grid_columns ); ?>">

			<?php
			while ( have_posts() ) :
				the_post();

				// Load the appropriate card template based on view.
				$template_name = $current_view === 'list' ? 'listing-card-list' : 'listing-card';

				apd_get_template_part(
					$template_name,
					null,
					[
						'listing_id'   => get_the_ID(),
						'current_view' => $current_view,
					]
				);
			endwhile;
			?>

		</div>

		<?php
		/**
		 * Fires after the listings loop, before pagination.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_after_archive_loop' );
		?>

		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $template_loader->render_pagination();
		?>

	<?php else : ?>

		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo apd_render_no_results();
		?>

	<?php endif; ?>

	<?php
	/**
	 * Fires at the end of the archive wrapper.
	 *
	 * @since 1.0.0
	 */
	do_action( 'apd_archive_wrapper_end' );
	?>

</div>

<?php
/**
 * Fires after the listing archive content.
 *
 * @since 1.0.0
 */
do_action( 'apd_after_archive' );

get_footer();
