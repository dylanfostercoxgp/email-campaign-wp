<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Mailgun {

	private $api_key;
	private $domain;
	private $api_base;

	public function __construct() {
		$this->api_key  = get_option( 'ecwp_mailgun_api_key', '' );
		$this->domain   = get_option( 'ecwp_mailgun_domain', '' );
		$region         = get_option( 'ecwp_mailgun_region', 'us' );
		$this->api_base = ( $region === 'eu' )
			? 'https://api.eu.mailgun.net/v3/'
			: 'https://api.mailgun.net/v3/';
	}

	/**
	 * Send a single email via Mailgun.
	 *
	 * @return array|WP_Error  Returns Mailgun response array on success.
	 */
	public function send_email( $to_email, $to_name, $from_name, $from_email, $subject, $html, $text = '', $tags = [] ) {
		if ( empty( $this->api_key ) || empty( $this->domain ) ) {
			return new WP_Error( 'mailgun_config', 'Mailgun API key or domain is not configured.' );
		}

		$from = $from_name ? "{$from_name} <{$from_email}>" : $from_email;
		$to   = $to_name   ? "{$to_name} <{$to_email}>"     : $to_email;

		$click_tracking = get_option( 'ecwp_click_tracking', '0' ) === '1' ? 'yes' : 'no';

		$body = [
			'from'                => $from,
			'to'                  => $to,
			'subject'             => $subject,
			'html'                => $html,
			'o:tracking'          => 'yes',
			'o:tracking-clicks'   => $click_tracking,
			'o:tracking-opens'    => 'yes',
		];

		if ( ! empty( $text ) ) {
			$body['text'] = $text;
		}

		foreach ( (array) $tags as $tag ) {
			$body['o:tag[]'] = sanitize_text_field( $tag );
		}

		$response = wp_remote_post(
			$this->api_base . $this->domain . '/messages',
			[
				'headers' => [
					'Authorization' => 'Basic ' . base64_encode( 'api:' . $this->api_key ),
				],
				'body'    => $body,
				'timeout' => 30,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code         = wp_remote_retrieve_response_code( $response );
		$decoded_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 ) {
			$msg = isset( $decoded_body['message'] ) ? $decoded_body['message'] : 'Unknown Mailgun error';
			return new WP_Error( 'mailgun_api', $msg, [ 'http_status' => $code ] );
		}

		return $decoded_body;
	}

	/**
	 * Verify API key + domain are valid.
	 */
	public function test_connection() {
		if ( empty( $this->api_key ) || empty( $this->domain ) ) {
			return new WP_Error( 'missing_config', 'API key and domain are required.' );
		}

		$response = wp_remote_get(
			$this->api_base . 'domains/' . $this->domain,
			[
				'headers' => [
					'Authorization' => 'Basic ' . base64_encode( 'api:' . $this->api_key ),
				],
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code === 200 ) {
			return true;
		}

		return new WP_Error( 'mailgun_failed', "Connection failed (HTTP {$code}). Check your API key and domain." );
	}

	/**
	 * Send a quick test email.
	 */
	public function send_test_email( $to ) {
		$from_name  = get_option( 'ecwp_from_name',  'ideaBoss' );
		$from_email = get_option( 'ecwp_from_email', '' );

		$html = '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;padding:30px;color:#333;">
			<h2 style="color:#2c3e50;">&#9989; Test Email — Email Campaign WP</h2>
			<p>Your Mailgun configuration is working correctly!</p>
			<hr style="border:none;border-top:1px solid #eee;margin:20px 0;">
			<p style="font-size:12px;color:#999;">Powered by <a href="https://ideaboss.io" style="color:#3498db;">ideaBoss</a></p>
		</body></html>';

		return $this->send_email( $to, '', $from_name, $from_email, 'Test Email — Email Campaign WP', $html );
	}
}
