<?php
/**
 * Contact form template.
 *
 * This template can be overridden by copying it to yourtheme/all-purpose-directory/contact/contact-form.php.
 *
 * @package All_Purpose_Directory
 * @since 1.0.0
 *
 * @var \APD\Contact\ContactForm $form        ContactForm instance.
 * @var int                      $listing_id  Listing ID.
 * @var \WP_Post                 $listing     Listing post object.
 * @var \WP_User                 $owner       Listing owner.
 * @var array                    $errors      Form errors.
 * @var array                    $values      Form values.
 * @var string                   $nonce_action Nonce action.
 * @var string                   $nonce_name  Nonce field name.
 */

defined( 'ABSPATH' ) || exit;

$has_errors = ! empty( $errors );
$form_classes = $form->get_css_classes();
?>

<div class="apd-contact-form-wrapper">
	<h3 class="apd-contact-form-title">
		<?php esc_html_e( 'Contact the Owner', 'all-purpose-directory' ); ?>
	</h3>

	<?php if ( $has_errors ) : ?>
		<div class="apd-contact-form-errors apd-notice apd-notice--error" role="alert">
			<ul>
				<?php foreach ( $errors as $error ) : ?>
					<li><?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<form
		class="<?php echo esc_attr( $form_classes ); ?>"
		method="post"
		action=""
		data-listing-id="<?php echo esc_attr( $listing_id ); ?>"
		aria-label="<?php esc_attr_e( 'Contact form', 'all-purpose-directory' ); ?>"
	>
		<?php wp_nonce_field( $nonce_action, $nonce_name ); ?>
		<input type="hidden" name="action" value="apd_send_contact">
		<input type="hidden" name="listing_id" value="<?php echo esc_attr( $listing_id ); ?>">

		<div class="apd-contact-form-field apd-contact-form-field--name">
			<label for="apd-contact-name-<?php echo esc_attr( $listing_id ); ?>">
				<?php esc_html_e( 'Your Name', 'all-purpose-directory' ); ?>
				<span class="apd-required" aria-hidden="true">*</span>
			</label>
			<input
				type="text"
				id="apd-contact-name-<?php echo esc_attr( $listing_id ); ?>"
				name="contact_name"
				value="<?php echo esc_attr( $form->get_value( 'contact_name' ) ); ?>"
				required
				aria-required="true"
				autocomplete="name"
			>
		</div>

		<div class="apd-contact-form-field apd-contact-form-field--email">
			<label for="apd-contact-email-<?php echo esc_attr( $listing_id ); ?>">
				<?php esc_html_e( 'Your Email', 'all-purpose-directory' ); ?>
				<span class="apd-required" aria-hidden="true">*</span>
			</label>
			<input
				type="email"
				id="apd-contact-email-<?php echo esc_attr( $listing_id ); ?>"
				name="contact_email"
				value="<?php echo esc_attr( $form->get_value( 'contact_email' ) ); ?>"
				required
				aria-required="true"
				autocomplete="email"
			>
		</div>

		<?php if ( $form->show_phone() ) : ?>
			<div class="apd-contact-form-field apd-contact-form-field--phone">
				<label for="apd-contact-phone-<?php echo esc_attr( $listing_id ); ?>">
					<?php esc_html_e( 'Your Phone', 'all-purpose-directory' ); ?>
					<?php if ( $form->is_phone_required() ) : ?>
						<span class="apd-required" aria-hidden="true">*</span>
					<?php else : ?>
						<span class="apd-optional"><?php esc_html_e( '(optional)', 'all-purpose-directory' ); ?></span>
					<?php endif; ?>
				</label>
				<input
					type="tel"
					id="apd-contact-phone-<?php echo esc_attr( $listing_id ); ?>"
					name="contact_phone"
					value="<?php echo esc_attr( $form->get_value( 'contact_phone' ) ); ?>"
					<?php echo $form->is_phone_required() ? 'required aria-required="true"' : ''; ?>
					autocomplete="tel"
				>
			</div>
		<?php endif; ?>

		<?php if ( $form->show_subject() ) : ?>
			<div class="apd-contact-form-field apd-contact-form-field--subject">
				<label for="apd-contact-subject-<?php echo esc_attr( $listing_id ); ?>">
					<?php esc_html_e( 'Subject', 'all-purpose-directory' ); ?>
					<?php if ( $form->is_subject_required() ) : ?>
						<span class="apd-required" aria-hidden="true">*</span>
					<?php else : ?>
						<span class="apd-optional"><?php esc_html_e( '(optional)', 'all-purpose-directory' ); ?></span>
					<?php endif; ?>
				</label>
				<input
					type="text"
					id="apd-contact-subject-<?php echo esc_attr( $listing_id ); ?>"
					name="contact_subject"
					value="<?php echo esc_attr( $form->get_value( 'contact_subject' ) ); ?>"
					<?php echo $form->is_subject_required() ? 'required aria-required="true"' : ''; ?>
				>
			</div>
		<?php endif; ?>

		<div class="apd-contact-form-field apd-contact-form-field--message">
			<label for="apd-contact-message-<?php echo esc_attr( $listing_id ); ?>">
				<?php esc_html_e( 'Message', 'all-purpose-directory' ); ?>
				<span class="apd-required" aria-hidden="true">*</span>
			</label>
			<textarea
				id="apd-contact-message-<?php echo esc_attr( $listing_id ); ?>"
				name="contact_message"
				rows="5"
				required
				aria-required="true"
				minlength="<?php echo esc_attr( $form->get_min_message_length() ); ?>"
			><?php echo esc_textarea( $form->get_value( 'contact_message' ) ); ?></textarea>
			<p class="apd-field-hint">
				<?php
				printf(
					/* translators: %d: minimum number of characters */
					esc_html__( 'Minimum %d characters required.', 'all-purpose-directory' ),
					$form->get_min_message_length()
				);
				?>
			</p>
		</div>

		<?php
		/**
		 * Fires after contact form fields, before submit button.
		 *
		 * @since 1.0.0
		 * @param int                      $listing_id Listing ID.
		 * @param \APD\Contact\ContactForm $form       ContactForm instance.
		 */
		do_action( 'apd_contact_form_after_fields', $listing_id, $form );
		?>

		<div class="apd-contact-form-submit">
			<button type="submit" class="apd-button apd-button--primary apd-contact-submit">
				<?php esc_html_e( 'Send Message', 'all-purpose-directory' ); ?>
			</button>
		</div>

		<div class="apd-contact-form-success apd-notice apd-notice--success" role="alert" style="display: none;">
			<?php esc_html_e( 'Your message has been sent successfully!', 'all-purpose-directory' ); ?>
		</div>
	</form>
</div>
