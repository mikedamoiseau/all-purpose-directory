<?php
/**
 * Contact Handler class.
 *
 * Processes contact form submissions.
 *
 * @package All_Purpose_Directory
 * @since 1.0.0
 */

namespace APD\Contact;

/**
 * ContactHandler class.
 */
class ContactHandler {

	/**
	 * Single instance.
	 *
	 * @var ContactHandler|null
	 */
	private static ?ContactHandler $instance = null;

	/**
	 * Configuration.
	 *
	 * @var array
	 */
	private array $config = [
		'min_message_length' => 10,
		'phone_required'     => false,
		'subject_required'   => false,
		'send_admin_copy'    => false,
		'admin_email'        => '',
	];

	/**
	 * Get single instance.
	 *
	 * @return ContactHandler
	 */
	public static function get_instance(): ContactHandler {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @param array $config Configuration options.
	 */
	public function __construct( array $config = [] ) {
		$this->config = array_merge( $this->config, $config );
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Register AJAX handlers.
		add_action( 'wp_ajax_apd_send_contact', [ $this, 'handle_ajax' ] );
		add_action( 'wp_ajax_nopriv_apd_send_contact', [ $this, 'handle_ajax' ] );

		/**
		 * Fires after contact handler initializes.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_contact_handler_init' );
	}

	/**
	 * Handle AJAX contact form submission.
	 *
	 * @return void
	 */
	public function handle_ajax(): void {
		// Verify nonce.
		if ( ! $this->verify_nonce() ) {
			wp_send_json_error( [
				'message' => __( 'Security check failed. Please refresh and try again.', 'all-purpose-directory' ),
				'code'    => 'nonce_failed',
			] );
			return;
		}

		$result = $this->process();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [
				'message' => $result->get_error_message(),
				'code'    => $result->get_error_code(),
				'errors'  => $result->get_error_messages(),
			] );
			return;
		}

		wp_send_json_success( [
			'message' => __( 'Your message has been sent successfully!', 'all-purpose-directory' ),
		] );
	}

	/**
	 * Verify nonce.
	 *
	 * @return bool
	 */
	public function verify_nonce(): bool {
		$nonce = isset( $_POST[ ContactForm::NONCE_NAME ] )
			? sanitize_text_field( wp_unslash( $_POST[ ContactForm::NONCE_NAME ] ) )
			: '';

		return wp_verify_nonce( $nonce, ContactForm::NONCE_ACTION );
	}

	/**
	 * Process the contact form submission.
	 *
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	public function process(): bool|\WP_Error {
		// Get and sanitize data.
		$data = $this->get_sanitized_data();

		// Validate.
		$validation = $this->validate( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Check listing can receive contact.
		$listing_id = (int) $data['listing_id'];
		$form = new ContactForm();

		if ( ! $form->can_receive_contact( $listing_id ) ) {
			return new \WP_Error(
				'listing_unavailable',
				__( 'This listing cannot receive messages at this time.', 'all-purpose-directory' )
			);
		}

		// Get listing and owner.
		$listing = get_post( $listing_id );
		$owner = get_userdata( $listing->post_author );

		/**
		 * Fires before sending contact message.
		 *
		 * @since 1.0.0
		 * @param array    $data    Sanitized form data.
		 * @param \WP_Post $listing Listing post.
		 * @param \WP_User $owner   Listing owner.
		 */
		do_action( 'apd_before_send_contact', $data, $listing, $owner );

		// Send email.
		$sent = $this->send_email( $data, $listing, $owner );

		if ( ! $sent ) {
			return new \WP_Error(
				'email_failed',
				__( 'Failed to send message. Please try again later.', 'all-purpose-directory' )
			);
		}

		/**
		 * Fires after contact message is sent successfully.
		 *
		 * @since 1.0.0
		 * @param array    $data    Sanitized form data.
		 * @param \WP_Post $listing Listing post.
		 * @param \WP_User $owner   Listing owner.
		 */
		do_action( 'apd_contact_sent', $data, $listing, $owner );

		return true;
	}

	/**
	 * Get sanitized form data.
	 *
	 * @return array
	 */
	public function get_sanitized_data(): array {
		return [
			'listing_id'      => isset( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : 0,
			'contact_name'    => isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '',
			'contact_email'   => isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '',
			'contact_phone'   => isset( $_POST['contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) : '',
			'contact_subject' => isset( $_POST['contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_subject'] ) ) : '',
			'contact_message' => isset( $_POST['contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ) ) : '',
		];
	}

	/**
	 * Validate form data.
	 *
	 * @param array $data Sanitized form data.
	 * @return true|\WP_Error True if valid, WP_Error with messages if not.
	 */
	public function validate( array $data ): bool|\WP_Error {
		$errors = new \WP_Error();

		// Listing ID required.
		if ( empty( $data['listing_id'] ) ) {
			$errors->add( 'listing_id', __( 'Invalid listing.', 'all-purpose-directory' ) );
		}

		// Name required.
		if ( empty( $data['contact_name'] ) ) {
			$errors->add( 'contact_name', __( 'Please enter your name.', 'all-purpose-directory' ) );
		}

		// Email required and valid.
		if ( empty( $data['contact_email'] ) ) {
			$errors->add( 'contact_email', __( 'Please enter your email address.', 'all-purpose-directory' ) );
		} elseif ( ! is_email( $data['contact_email'] ) ) {
			$errors->add( 'contact_email', __( 'Please enter a valid email address.', 'all-purpose-directory' ) );
		}

		// Phone required (if configured).
		if ( $this->config['phone_required'] && empty( $data['contact_phone'] ) ) {
			$errors->add( 'contact_phone', __( 'Please enter your phone number.', 'all-purpose-directory' ) );
		}

		// Subject required (if configured).
		if ( $this->config['subject_required'] && empty( $data['contact_subject'] ) ) {
			$errors->add( 'contact_subject', __( 'Please enter a subject.', 'all-purpose-directory' ) );
		}

		// Message required.
		if ( empty( $data['contact_message'] ) ) {
			$errors->add( 'contact_message', __( 'Please enter your message.', 'all-purpose-directory' ) );
		} elseif ( strlen( $data['contact_message'] ) < $this->config['min_message_length'] ) {
			$errors->add(
				'contact_message',
				sprintf(
					/* translators: %d: minimum number of characters */
					__( 'Message must be at least %d characters.', 'all-purpose-directory' ),
					$this->config['min_message_length']
				)
			);
		}

		/**
		 * Filter contact form validation errors.
		 *
		 * @since 1.0.0
		 * @param \WP_Error $errors Validation errors.
		 * @param array     $data   Form data.
		 */
		$errors = apply_filters( 'apd_contact_validation_errors', $errors, $data );

		if ( $errors->has_errors() ) {
			return $errors;
		}

		return true;
	}

	/**
	 * Send the contact email.
	 *
	 * @param array    $data    Form data.
	 * @param \WP_Post $listing Listing post.
	 * @param \WP_User $owner   Listing owner.
	 * @return bool
	 */
	public function send_email( array $data, \WP_Post $listing, \WP_User $owner ): bool {
		$to = $owner->user_email;

		/**
		 * Filter contact email recipient.
		 *
		 * @since 1.0.0
		 * @param string   $to      Recipient email.
		 * @param array    $data    Form data.
		 * @param \WP_Post $listing Listing post.
		 */
		$to = apply_filters( 'apd_contact_email_to', $to, $data, $listing );

		// Build subject.
		$subject = ! empty( $data['contact_subject'] )
			? $data['contact_subject']
			: sprintf(
				/* translators: %s: listing title */
				__( 'New inquiry about: %s', 'all-purpose-directory' ),
				$listing->post_title
			);

		/**
		 * Filter contact email subject.
		 *
		 * @since 1.0.0
		 * @param string   $subject Email subject.
		 * @param array    $data    Form data.
		 * @param \WP_Post $listing Listing post.
		 */
		$subject = apply_filters( 'apd_contact_email_subject', $subject, $data, $listing );

		// Build message.
		$message = $this->build_email_message( $data, $listing );

		/**
		 * Filter contact email message.
		 *
		 * @since 1.0.0
		 * @param string   $message Email message.
		 * @param array    $data    Form data.
		 * @param \WP_Post $listing Listing post.
		 */
		$message = apply_filters( 'apd_contact_email_message', $message, $data, $listing );

		// Headers.
		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'Reply-To: %s <%s>', $data['contact_name'], $data['contact_email'] ),
		];

		/**
		 * Filter contact email headers.
		 *
		 * @since 1.0.0
		 * @param array    $headers Email headers.
		 * @param array    $data    Form data.
		 * @param \WP_Post $listing Listing post.
		 */
		$headers = apply_filters( 'apd_contact_email_headers', $headers, $data, $listing );

		// Send to owner.
		$sent = wp_mail( $to, $subject, $message, $headers );

		// Send admin copy if configured.
		if ( $sent && $this->should_send_admin_copy() ) {
			$admin_email = $this->get_admin_email();
			if ( $admin_email ) {
				wp_mail( $admin_email, '[Copy] ' . $subject, $message, $headers );
			}
		}

		return $sent;
	}

	/**
	 * Build the email message body.
	 *
	 * @param array    $data    Form data.
	 * @param \WP_Post $listing Listing post.
	 * @return string
	 */
	public function build_email_message( array $data, \WP_Post $listing ): string {
		$message = '<html><body>';

		$message .= '<h2>' . sprintf(
			/* translators: %s: listing title */
			esc_html__( 'New inquiry about: %s', 'all-purpose-directory' ),
			esc_html( $listing->post_title )
		) . '</h2>';

		$message .= '<table style="border-collapse: collapse; width: 100%; max-width: 600px;">';

		$message .= '<tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>'
			. esc_html__( 'From:', 'all-purpose-directory' ) . '</strong></td>'
			. '<td style="padding: 8px; border-bottom: 1px solid #ddd;">'
			. esc_html( $data['contact_name'] ) . '</td></tr>';

		$message .= '<tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>'
			. esc_html__( 'Email:', 'all-purpose-directory' ) . '</strong></td>'
			. '<td style="padding: 8px; border-bottom: 1px solid #ddd;">'
			. '<a href="mailto:' . esc_attr( $data['contact_email'] ) . '">'
			. esc_html( $data['contact_email'] ) . '</a></td></tr>';

		if ( ! empty( $data['contact_phone'] ) ) {
			$message .= '<tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>'
				. esc_html__( 'Phone:', 'all-purpose-directory' ) . '</strong></td>'
				. '<td style="padding: 8px; border-bottom: 1px solid #ddd;">'
				. esc_html( $data['contact_phone'] ) . '</td></tr>';
		}

		$message .= '</table>';

		$message .= '<h3>' . esc_html__( 'Message:', 'all-purpose-directory' ) . '</h3>';
		$message .= '<div style="background: #f9f9f9; padding: 15px; border-radius: 4px;">'
			. nl2br( esc_html( $data['contact_message'] ) ) . '</div>';

		$message .= '<hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">';
		$message .= '<p style="color: #666; font-size: 12px;">'
			. sprintf(
				/* translators: %s: listing URL */
				esc_html__( 'This message was sent via the contact form on: %s', 'all-purpose-directory' ),
				'<a href="' . esc_url( get_permalink( $listing->ID ) ) . '">'
				. esc_html( $listing->post_title ) . '</a>'
			)
			. '</p>';

		$message .= '</body></html>';

		return $message;
	}

	/**
	 * Check if admin copy should be sent.
	 *
	 * @return bool
	 */
	public function should_send_admin_copy(): bool {
		/**
		 * Filter whether to send admin copy of contact emails.
		 *
		 * @since 1.0.0
		 * @param bool $send_copy Whether to send admin copy.
		 */
		return apply_filters( 'apd_contact_send_admin_copy', $this->config['send_admin_copy'] );
	}

	/**
	 * Get admin email for copies.
	 *
	 * @return string
	 */
	public function get_admin_email(): string {
		$email = $this->config['admin_email'];

		if ( empty( $email ) ) {
			$email = get_option( 'admin_email' );
		}

		/**
		 * Filter admin email for contact copies.
		 *
		 * @since 1.0.0
		 * @param string $email Admin email address.
		 */
		return apply_filters( 'apd_contact_admin_email', $email );
	}

	/**
	 * Get configuration value.
	 *
	 * @param string $key     Config key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_config( string $key, $default = null ) {
		return $this->config[ $key ] ?? $default;
	}

	/**
	 * Set configuration.
	 *
	 * @param array $config Configuration array.
	 * @return self
	 */
	public function set_config( array $config ): self {
		$this->config = array_merge( $this->config, $config );
		return $this;
	}
}
