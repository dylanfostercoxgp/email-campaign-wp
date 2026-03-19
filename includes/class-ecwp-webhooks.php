<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Handles incoming Mailgun webhook events.
 *
 * Endpoint: POST /wp-json/ecwp/v1/webhook
 *
 * Configure this URL in your Mailgun dashboard under
 * Sending → Webhooks for: delivered, opened, clicked,
 * bounced, complained, unsubscribed, failed.
 */
class ECWP_Webhooks {

	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		register_rest_route( 'ecwp/v1', '/webhook', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle' ],
			'permission_callback' => '__return_true',
		] );
	}

	public function handle( WP_REST_Request $request ) {
		global $wpdb;

		$payload = $request->get_json_params();

		// ── Normalise payload ── Mailgun v3 (event-data envelope) vs legacy ──
		if ( ! empty( $payload['event-data'] ) ) {
			$event_data = $payload['event-data'];
			$event_type = $event_data['event']                                   ?? '';
			$message_id = $event_data['message']['headers']['message-id']        ?? '';
			$recipient  = $event_data['recipient']                               ?? '';
		} else {
			// Legacy format (or URL-encoded form POST).
			$body       = $request->get_body_params();
			$event_data = $body;
			$event_type = $body['event']      ?? '';
			$message_id = $body['Message-Id'] ?? '';
			$recipient  = $body['recipient']  ?? '';
		}

		if ( empty( $event_type ) ) {
			return new WP_REST_Response( [ 'status' => 'ignored' ], 200 );
		}

		$message_id = trim( $message_id, '<>' );

		$log_table       = $wpdb->prefix . 'ecwp_send_log';
		$analytics_table = $wpdb->prefix . 'ecwp_analytics';

		// ── Look up campaign_id from send_log by message_id ──────────────
		$campaign_id = 0;
		if ( ! empty( $message_id ) ) {
			$campaign_id = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT campaign_id FROM {$log_table} WHERE message_id = %s LIMIT 1",
				$message_id
			) );
		}

		// ── Record the event ──────────────────────────────────────────────
		$wpdb->insert( $analytics_table, [
			'campaign_id' => $campaign_id,
			'message_id'  => sanitize_text_field( $message_id ),
			'event_type'  => sanitize_text_field( $event_type ),
			'recipient'   => sanitize_email( $recipient ),
			'raw_data'    => wp_json_encode( $event_data ),
			'created_at'  => current_time( 'mysql' ),
		] );

		// ── Update send-log status ────────────────────────────────────────
		$status_map = [
			'delivered'    => 'delivered',
			'opened'       => 'opened',
			'clicked'      => 'clicked',
			'failed'       => 'failed',
			'bounced'      => 'bounced',
			'complained'   => 'complained',
			'unsubscribed' => 'unsubscribed',
		];

		if ( ! empty( $message_id ) && isset( $status_map[ $event_type ] ) ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$log_table} SET status = %s WHERE message_id = %s",
				$status_map[ $event_type ],
				$message_id
			) );
		}

		// ── Auto-unsubscribe via Mailgun signal ───────────────────────────
		if ( $event_type === 'unsubscribed' && ! empty( $recipient ) ) {
			( new ECWP_Subscribers() )->unsubscribe( sanitize_email( $recipient ) );
		}

		return new WP_REST_Response( [ 'status' => 'ok' ], 200 );
	}
}
