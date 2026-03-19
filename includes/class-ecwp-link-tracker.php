<?php
/**
 * ECWP_Link_Tracker — first-party click tracking.
 *
 * When the "Custom Link Tracking" setting is enabled every outgoing link in a
 * campaign email is rewritten to a signed redirect URL on the site itself.
 * Clicks are intercepted on `template_redirect`, recorded in
 * `ecwp_link_clicks`, and the visitor is immediately bounced to the real URL.
 *
 * Mailgun's own click tracking is disabled when this feature is on so
 * subscribers see only one redirect (ours) rather than two.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Link_Tracker {

	/* ------------------------------------------------------------------ */
	/*  Init                                                                */
	/* ------------------------------------------------------------------ */

	public function init() {
		add_filter( 'query_vars',       [ $this, 'add_query_var' ] );
		add_action( 'template_redirect', [ $this, 'handle_click' ], 1 );
	}

	public function add_query_var( $vars ) {
		$vars[] = 'ecwp_lc';
		return $vars;
	}

	/* ------------------------------------------------------------------ */
	/*  Click interception                                                  */
	/* ------------------------------------------------------------------ */

	/**
	 * Fires on every front-end request.  If the `ecwp_lc` query var is set
	 * we verify the signature, log the click, and redirect immediately.
	 */
	public function handle_click() {
		if ( ! get_query_var( 'ecwp_lc' ) ) {
			return;
		}

		$campaign_id   = intval( $_GET['c']   ?? 0 );
		$subscriber_id = intval( $_GET['s']   ?? 0 );
		$raw_url       = $_GET['url'] ?? '';
		$sig           = sanitize_text_field( $_GET['sig'] ?? '' );

		// Decode the URL (it was rawurlencode'd when building the link).
		$destination = esc_url_raw( urldecode( $raw_url ) );

		// ── Signature check ───────────────────────────────────────────
		$expected = $this->sign( $campaign_id, $subscriber_id, $destination );
		if ( ! hash_equals( $expected, $sig ) ) {
			// Signature mismatch — redirect home silently.
			wp_redirect( home_url( '/' ) );
			exit;
		}

		// ── Record the click ─────────────────────────────────────────
		$this->record_click( $campaign_id, $subscriber_id, $destination );

		// ── Redirect to real destination ──────────────────────────────
		wp_redirect( $destination, 302 );
		exit;
	}

	/* ------------------------------------------------------------------ */
	/*  Link injection (called before send)                                 */
	/* ------------------------------------------------------------------ */

	/**
	 * Replace every http/https link in the HTML with a signed tracking URL.
	 * Safe to call even when custom tracking is disabled — returns $html unchanged.
	 *
	 * @param  string $html
	 * @param  int    $campaign_id
	 * @param  int    $subscriber_id
	 * @return string
	 */
	public function inject_tracking_links( $html, $campaign_id, $subscriber_id ) {
		if ( get_option( 'ecwp_custom_link_tracking', '0' ) !== '1' ) {
			return $html;
		}

		return preg_replace_callback(
			'/href=(["\'])(https?:\/\/[^"\'>\s]+)\1/i',
			function ( $m ) use ( $campaign_id, $subscriber_id ) {
				$quote = $m[1];
				$url   = $m[2];

				// Never wrap unsubscribe links or existing tracking URLs.
				if (
					strpos( $url, 'ecwp-unsubscribe' ) !== false ||
					strpos( $url, 'ecwp_lc=' )         !== false ||
					strpos( $url, '?ecwp_lc' )         !== false
				) {
					return $m[0];
				}

				$tracking = $this->build_tracking_url( $url, $campaign_id, $subscriber_id );
				return 'href=' . $quote . esc_attr( $tracking ) . $quote;
			},
			$html
		);
	}

	/**
	 * Build a signed click-tracking URL.
	 */
	public function build_tracking_url( $destination, $campaign_id, $subscriber_id ) {
		$sig = $this->sign( $campaign_id, $subscriber_id, $destination );

		return add_query_arg( [
			'ecwp_lc' => '1',
			'c'       => $campaign_id,
			's'       => $subscriber_id,
			'url'     => rawurlencode( $destination ),
			'sig'     => $sig,
		], home_url( '/' ) );
	}

	/* ------------------------------------------------------------------ */
	/*  Signature                                                           */
	/* ------------------------------------------------------------------ */

	private function sign( $campaign_id, $subscriber_id, $url ) {
		$secret = get_option( 'ecwp_token_secret', '' );
		if ( ! $secret ) {
			$secret = wp_generate_password( 64, true, true );
			update_option( 'ecwp_token_secret', $secret );
		}
		return hash_hmac( 'sha256', "{$campaign_id}:{$subscriber_id}:{$url}", $secret );
	}

	/* ------------------------------------------------------------------ */
	/*  DB write                                                            */
	/* ------------------------------------------------------------------ */

	private function record_click( $campaign_id, $subscriber_id, $url ) {
		global $wpdb;

		// Resolve email from subscriber table (denormalised for resilience).
		$email = '';
		if ( $subscriber_id ) {
			$email = (string) $wpdb->get_var( $wpdb->prepare(
				"SELECT email FROM {$wpdb->prefix}ecwp_subscribers WHERE id = %d LIMIT 1",
				$subscriber_id
			) );
		}

		$wpdb->insert(
			$wpdb->prefix . 'ecwp_link_clicks',
			[
				'campaign_id'   => $campaign_id,
				'subscriber_id' => $subscriber_id,
				'email'         => $email,
				'link_url'      => $url,
				'link_hash'     => md5( $url ),
				'clicked_at'    => current_time( 'mysql' ),
				'ip_address'    => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
				'user_agent'    => substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 500 ),
			]
		);
	}

	/* ------------------------------------------------------------------ */
	/*  Analytics queries                                                   */
	/* ------------------------------------------------------------------ */

	/**
	 * Per-link stats for a campaign.
	 * Returns: link_url, link_hash, total_clicks, unique_clickers
	 *
	 * @param  int $campaign_id  0 = all campaigns.
	 * @return array
	 */
	public function get_link_stats( $campaign_id = 0 ) {
		global $wpdb;
		$t     = $wpdb->prefix . 'ecwp_link_clicks';
		$where = $campaign_id ? $wpdb->prepare( ' WHERE campaign_id = %d', $campaign_id ) : '';
		return $wpdb->get_results(
			"SELECT link_url, link_hash,
			        COUNT(*)                AS total_clicks,
			        COUNT(DISTINCT email)   AS unique_clickers
			 FROM {$t}{$where}
			 GROUP BY link_hash
			 ORDER BY total_clicks DESC"
		);
	}

	/**
	 * Per-subscriber click detail for a campaign.
	 * Returns: email, first_name, last_name, link_url, click_count, first_click, last_click
	 *
	 * @param  int $campaign_id
	 * @return array
	 */
	public function get_clicker_detail( $campaign_id = 0 ) {
		global $wpdb;
		$t     = $wpdb->prefix . 'ecwp_link_clicks';
		$sub   = $wpdb->prefix . 'ecwp_subscribers';
		$where = $campaign_id ? $wpdb->prepare( ' WHERE lc.campaign_id = %d', $campaign_id ) : '';
		return $wpdb->get_results(
			"SELECT lc.email, lc.link_url, lc.link_hash,
			        COUNT(*)         AS click_count,
			        MIN(lc.clicked_at) AS first_click,
			        MAX(lc.clicked_at) AS last_click,
			        s.id             AS subscriber_id,
			        s.first_name,
			        s.last_name,
			        s.status         AS subscriber_status
			 FROM {$t} lc
			 LEFT JOIN {$sub} s ON s.email = lc.email
			 {$where}
			 GROUP BY lc.email, lc.link_hash
			 ORDER BY last_click DESC"
		);
	}

	/**
	 * Total click counts for use in dashboard stats.
	 *
	 * @return array  [ 'total_clicks' => int, 'unique_clickers' => int ]
	 */
	public function get_summary_stats( $campaign_id = 0 ) {
		global $wpdb;
		$t     = $wpdb->prefix . 'ecwp_link_clicks';
		$where = $campaign_id ? $wpdb->prepare( ' WHERE campaign_id = %d', $campaign_id ) : '';
		return [
			'total_clicks'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}{$where}" ),
			'unique_clickers' => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT email) FROM {$t}{$where}" ),
		];
	}
}
