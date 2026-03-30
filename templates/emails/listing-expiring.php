<?php
/**
 * Listing Expiring Soon Email Template.
 *
 * Sent to the listing author when their listing is about to expire.
 * Override this template in your theme: damdir-directory/emails/listing-expiring.php
 *
 * @package All_Purpose_Directory
 * @since   1.0.0
 *
 * @var int    $listing_id    Listing ID.
 * @var string $listing_title Listing title.
 * @var string $listing_url   Listing URL.
 * @var string $author_name   Author display name.
 * @var int    $days_left     Days until expiration.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2><?php esc_html_e( 'Your Listing Expires Soon', 'damdir-directory' ); ?></h2>

<p>
	<?php
	printf(
		/* translators: %s: author name */
		esc_html__( 'Hi %s,', 'damdir-directory' ),
		esc_html( $author_name )
	);
	?>
</p>

<p>
	<?php
	printf(
		esc_html(
			/* translators: %d: number of days until listing expires */
			_n(
				'Your listing will expire in %d day.',
				'Your listing will expire in %d days.',
				$days_left,
				'damdir-directory'
			)
		),
		(int) $days_left
	);
	?>
</p>

<table class="info-table">
	<tr>
		<td><?php esc_html_e( 'Listing Title', 'damdir-directory' ); ?></td>
		<td><strong><?php echo esc_html( $listing_title ); ?></strong></td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Days Remaining', 'damdir-directory' ); ?></td>
		<td>
			<span style="color: #ffc107; font-weight: 600;">
				<?php
				printf(
					/* translators: %d: number of days */
					esc_html( _n( '%d day', '%d days', $days_left, 'damdir-directory' ) ),
					(int) $days_left
				);
				?>
			</span>
		</td>
	</tr>
</table>

<p><?php esc_html_e( 'To keep your listing active and visible to potential customers, please renew it before it expires.', 'damdir-directory' ); ?></p>

<div class="text-center mt-20">
	<a href="<?php echo esc_url( $listing_url ); ?>" class="button"><?php esc_html_e( 'View Your Listing', 'damdir-directory' ); ?></a>
</div>

<p class="text-muted mt-20"><?php esc_html_e( 'Once your listing expires, it will no longer be visible to the public until it is renewed.', 'damdir-directory' ); ?></p>
