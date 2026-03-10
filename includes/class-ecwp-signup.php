<?php
/**
 * Public-facing signup form shortcode and handler.
 *
 * Usage: [ecwp_signup_form]
 *
 * Shortcode attributes (all optional):
 *   button_text     – Submit button label.     Default: "Subscribe"
 *   placeholder     – Email input placeholder. Default: "Your email address"
 *   success_message – Message shown after a successful signup.
 *   title           – Heading text shown above the form (omitted if blank).
 *   tag_id          – Tag ID to auto-apply to every new subscriber (optional).
 *
 * @package EmailCampaignWP
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Signup {

	public function init() {
		add_shortcode( 'ecwp_signup_form', [ $this, 'render_form' ] );

		// Handle form submissions from both logged-in and anonymous visitors.
		add_action( 'admin_post_nopriv_ecwp_public_signup', [ $this, 'handle_signup' ] );
		add_action( 'admin_post_ecwp_public_signup',        [ $this, 'handle_signup' ] );
	}

	/* ------------------------------------------------------------------ */
	/*  Shortcode                                                           */
	/* ------------------------------------------------------------------ */

	public function render_form( $atts ) {
		$atts = shortcode_atts( [
			'button_text'     => 'Subscribe',
			'placeholder'     => 'Your email address',
			'success_message' => 'Thank you for subscribing!',
			'title'           => '',
			'tag_id'          => '',
		], $atts, 'ecwp_signup_form' );

		$result = sanitize_key( $_GET['ecwp_signup'] ?? '' );

		ob_start();

		// ── Unique ID so multiple forms on one page don't conflict ──────
		static $instance = 0;
		$instance++;
		$form_id = 'ecwp-sf-' . $instance;

		// ── Success state: hide the form, show success message ──────────
		if ( $result === 'success' ) {
			echo '<div class="ecwp-sf-wrap ecwp-sf-done">'
			   . '<p class="ecwp-sf-success">'
			   . esc_html( $atts['success_message'] )
			   . '</p></div>';
			return ob_get_clean();
		}

		// ── Current page URL (strip the ecwp_signup param for clean redirects)
		$redirect_to = remove_query_arg( 'ecwp_signup' );

		?>
		<div class="ecwp-sf-wrap" id="<?php echo esc_attr( $form_id ); ?>">

			<?php if ( ! empty( $atts['title'] ) ) : ?>
				<p class="ecwp-sf-title"><?php echo esc_html( $atts['title'] ); ?></p>
			<?php endif; ?>

			<?php if ( $result === 'exists' ) : ?>
				<p class="ecwp-sf-info">You're already subscribed — thanks!</p>
			<?php elseif ( $result === 'error' ) : ?>
				<p class="ecwp-sf-error">Please enter a valid email address.</p>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ecwp-sf-form">
				<input type="hidden" name="action"      value="ecwp_public_signup">
				<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
				<input type="hidden" name="tag_id"      value="<?php echo esc_attr( $atts['tag_id'] ); ?>">
				<?php wp_nonce_field( 'ecwp_public_signup', 'ecwp_signup_nonce' ); ?>

				<div class="ecwp-sf-row">
					<input
						type="email"
						name="email"
						class="ecwp-sf-email"
						placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
						required
						autocomplete="email"
					>
					<button type="submit" class="ecwp-sf-btn">
						<?php echo esc_html( $atts['button_text'] ); ?>
					</button>
				</div>
			</form>

		</div><!-- .ecwp-sf-wrap -->

		<style>
		.ecwp-sf-wrap { font-family: inherit; max-width: 500px; }
		.ecwp-sf-title { font-weight: 600; font-size: 1.05em; margin: 0 0 10px; }
		.ecwp-sf-form { margin: 0; }
		.ecwp-sf-row { display: flex; gap: 8px; flex-wrap: wrap; }
		.ecwp-sf-email {
			flex: 1; min-width: 200px;
			padding: 10px 14px;
			border: 1px solid #d1d5db;
			border-radius: 6px;
			font-size: 15px;
			line-height: 1.4;
			color: inherit;
			background: #fff;
			box-sizing: border-box;
		}
		.ecwp-sf-email:focus {
			outline: none;
			border-color: #2563eb;
			box-shadow: 0 0 0 3px rgba(37,99,235,.15);
		}
		.ecwp-sf-btn {
			padding: 10px 22px;
			background: #2563eb;
			color: #fff;
			border: none;
			border-radius: 6px;
			font-size: 15px;
			font-weight: 600;
			cursor: pointer;
			white-space: nowrap;
			line-height: 1.4;
		}
		.ecwp-sf-btn:hover { background: #1d4ed8; }
		.ecwp-sf-success {
			padding: 12px 16px;
			background: #dcfce7;
			color: #15803d;
			border-radius: 6px;
			font-weight: 600;
			margin: 0;
		}
		.ecwp-sf-info {
			padding: 10px 14px;
			background: #fef9c3;
			color: #a16207;
			border-radius: 6px;
			margin: 0 0 10px;
		}
		.ecwp-sf-error {
			padding: 10px 14px;
			background: #fee2e2;
			color: #dc2626;
			border-radius: 6px;
			margin: 0 0 10px;
		}
		</style>
		<?php

		return ob_get_clean();
	}

	/* ------------------------------------------------------------------ */
	/*  Handler                                                             */
	/* ------------------------------------------------------------------ */

	public function handle_signup() {
		// Verify nonce — protects against CSRF.
		if ( ! wp_verify_nonce( $_POST['ecwp_signup_nonce'] ?? '', 'ecwp_public_signup' ) ) {
			wp_die( 'Security check failed. Please go back and try again.' );
		}

		$email       = sanitize_email( $_POST['email']       ?? '' );
		$tag_id      = intval( $_POST['tag_id']              ?? 0 );
		$redirect_to = esc_url_raw( $_POST['redirect_to']    ?? home_url() );

		// Validate
		if ( ! is_email( $email ) ) {
			wp_redirect( add_query_arg( 'ecwp_signup', 'error', $redirect_to ) );
			exit;
		}

		$result = ( new ECWP_Subscribers() )->add( $email );

		if ( is_wp_error( $result ) ) {
			$code = ( $result->get_error_code() === 'duplicate_email' ) ? 'exists' : 'error';
			wp_redirect( add_query_arg( 'ecwp_signup', $code, $redirect_to ) );
			exit;
		}

		// Auto-apply tag if specified.
		if ( $tag_id > 0 ) {
			( new ECWP_Tags() )->add_tag_to_subscriber( $result, $tag_id );
		}

		wp_redirect( add_query_arg( 'ecwp_signup', 'success', $redirect_to ) );
		exit;
	}
}
