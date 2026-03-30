<?php
/**
 * Listing Approved Email Template.
 *
 * Sent to the listing author when their listing is approved.
 * Override this template in your theme: damdir-directory/emails/listing-approved.php
 *
 * @package All_Purpose_Directory
 * @since   1.0.0
 *
 * @var int    $listing_id       Listing ID.
 * @var string $listing_title    Listing title.
 * @var string $listing_url      Listing URL.
 * @var string $author_name      Author display name.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2><?php esc_html_e( 'Your Listing Has Been Approved!', 'damdir-directory' ); ?></h2>

<p>
	<?php
	printf(
		/* translators: %s: author name */
		esc_html__( 'Hi %s,', 'damdir-directory' ),
		esc_html( $author_name )
	);
	?>
</p>

<p><?php esc_html_e( 'Great news! Your listing has been reviewed and approved. It is now live and visible to the public.', 'damdir-directory' ); ?></p>

<table class="info-table">
	<tr>
		<td><?php esc_html_e( 'Listing Title', 'damdir-directory' ); ?></td>
		<td><strong><?php echo esc_html( $listing_title ); ?></strong></td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Status', 'damdir-directory' ); ?></td>
		<td><span style="color: #28a745; font-weight: 500;"><?php esc_html_e( 'Published', 'damdir-directory' ); ?></span></td>
	</tr>
</table>

<div class="text-center mt-20">
	<a href="<?php echo esc_url( $listing_url ); ?>" class="button"><?php esc_html_e( 'View Your Listing', 'damdir-directory' ); ?></a>
</div>

<p class="mt-20"><?php esc_html_e( 'Thank you for your submission!', 'damdir-directory' ); ?></p>
