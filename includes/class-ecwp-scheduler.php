<?php
/**
 * Scheduling, batch sending, and personalization.
 *
 * @package EmailCampaignWP
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Scheduler {

	public function init() {
		add_action( 'ecwp_daily_trigger',  [ $this, 'run_daily_trigger' ] );
		add_action( 'ecwp_send_batch',     [ $this, 'send_batch' ], 10, 3 );
		add_action( 'ecwp_fire_campaign',  [ $this, 'start_campaign_sending' ] );

		// Ensure the daily trigger cron is registered.
		if ( ! wp_next_scheduled( 'ecwp_daily_trigger' ) ) {
			$this->schedule_daily_trigger();
		}

		// Re-schedule whenever relevant settings change.
		add_action( 'update_option_ecwp_send_time',        [ $this, 'reschedule_daily_trigger' ] );
		add_action( 'update_option_ecwp_schedule_enabled', [ $this, 'reschedule_daily_trigger' ] );
	}

	/* ------------------------------------------------------------------ */
	/*  Cron scheduling                                                     */
	/* ------------------------------------------------------------------ */

	public function schedule_daily_trigger() {
		if ( get_option( 'ecwp_schedule_enabled', '0' ) !== '1' ) {
			return;
		}

		$send_time = get_option( 'ecwp_send_time', '10:00' );
		list( $hour, $minute ) = array_pad( explode( ':', $send_time ), 2, '00' );

		// Calculate next occurrence of the target time in site local time.
		$site_tz = wp_timezone();
		$now     = new DateTime( 'now', $site_tz );
		$target  = clone $now;
		$target->setTime( (int) $hour, (int) $minute, 0 );

		if ( $target <= $now ) {
			$target->modify( '+1 day' );
		}

		wp_schedule_event( $target->getTimestamp(), 'daily', 'ecwp_daily_trigger' );
	}

	public function reschedule_daily_trigger() {
		wp_clear_scheduled_hook( 'ecwp_daily_trigger' );
		$this->schedule_daily_trigger();
	}

	/* ------------------------------------------------------------------ */
	/*  Per-campaign scheduling                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Schedule a single-fire cron event for a campaign at a specific date + time.
	 *
	 * @param int    $campaign_id
	 * @param string $datetime_local  "Y-m-d H:i" in site local time (e.g. "2026-04-15 10:30")
	 * @return bool  true on success, false if the datetime is in the past or invalid.
	 */
	public function schedule_campaign( $campaign_id, $datetime_local ) {
		// Remove any existing scheduled event for this campaign first.
		$this->unschedule_campaign( $campaign_id );

		$site_tz = wp_timezone();
		$target  = DateTime::createFromFormat( 'Y-m-d H:i', $datetime_local, $site_tz );

		if ( ! $target || $target->getTimestamp() <= time() ) {
			return false; // Past datetime or invalid format.
		}

		wp_schedule_single_event( $target->getTimestamp(), 'ecwp_fire_campaign', [ $campaign_id ] );
		return true;
	}

	/**
	 * Remove a campaign's scheduled cron event.
	 */
	public function unschedule_campaign( $campaign_id ) {
		wp_clear_scheduled_hook( 'ecwp_fire_campaign', [ $campaign_id ] );
	}

	/**
	 * Return the next scheduled timestamp for a campaign, or false if none.
	 */
	public function get_next_run( $campaign_id ) {
		return wp_next_scheduled( 'ecwp_fire_campaign', [ $campaign_id ] );
	}

	/* ------------------------------------------------------------------ */
	/*  Daily trigger: fire all scheduled campaigns                         */
	/* ------------------------------------------------------------------ */

	public function run_daily_trigger() {
		if ( get_option( 'ecwp_schedule_enabled', '0' ) !== '1' ) {
			return;
		}

		global $wpdb;
		$campaigns = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}ecwp_campaigns
			 WHERE schedule_enabled = 1 AND status IN ('draft','scheduled')"
		);

		foreach ( $campaigns as $campaign ) {
			$this->start_campaign_sending( $campaign->id );
		}
	}

	/* ------------------------------------------------------------------ */
	/*  Core sending logic                                                  */
	/* ------------------------------------------------------------------ */

	public function start_campaign_sending( $campaign_id ) {
		$campaigns_class = new ECWP_Campaigns();
		$campaign        = $campaigns_class->get_by_id( $campaign_id );

		if ( ! $campaign ) {
			return;
		}

		// ── Resolve target audience based on target_type ──────────────
		switch ( $campaign->target_type ) {
			case 'tags':
				$tag_ids = $campaigns_class->get_target_tag_ids( $campaign );
				if ( ! empty( $tag_ids ) ) {
					$campaigns_class->assign_subscribers_by_tags( $campaign_id, $tag_ids );
				}
				break;

			case 'all':
				$campaigns_class->assign_all_subscribers( $campaign_id );
				break;

			// 'selected' keeps whatever is already in the junction table.
		}

		$campaigns_class->update( $campaign_id, [ 'status' => 'sending' ] );

		$subscribers    = $campaigns_class->get_unsent_subscribers( $campaign_id );
		$batch_size     = max( 1, (int) $campaign->batch_size );
		$batch_interval = max( 1, (int) $campaign->batch_interval );

		if ( empty( $subscribers ) ) {
			$campaigns_class->update( $campaign_id, [ 'status' => 'sent' ] );
			return;
		}

		$batches = array_chunk( $subscribers, $batch_size );
		$delay   = 0;

		foreach ( $batches as $batch_num => $batch ) {
			$subscriber_ids = array_column( (array) $batch, 'id' );

			if ( $batch_num === 0 ) {
				// First batch fires immediately.
				$this->send_batch( $campaign_id, $subscriber_ids, 1 );
			} else {
				$delay += $batch_interval * 60; // minutes → seconds
				wp_schedule_single_event(
					time() + $delay,
					'ecwp_send_batch',
					[ $campaign_id, $subscriber_ids, $batch_num + 1 ]
				);
			}
		}
	}

	/**
	 * Process and send one batch.
	 *
	 * @param int   $campaign_id
	 * @param array $subscriber_ids
	 * @param int   $batch_number  (1-based, for logging)
	 */
	public function send_batch( $campaign_id, $subscriber_ids, $batch_number ) {
		global $wpdb;

		$campaigns_class = new ECWP_Campaigns();
		$campaign        = $campaigns_class->get_by_id( $campaign_id );

		if ( ! $campaign || $campaign->status === 'paused' ) {
			return;
		}

		$mailgun        = new ECWP_Mailgun();
		$link_tracker   = new ECWP_Link_Tracker();
		$custom_tracking = get_option( 'ecwp_custom_link_tracking', '0' ) === '1';
		$from_name  = get_option( 'ecwp_from_name',  'ideaBoss' );
		$from_email = get_option( 'ecwp_from_email', '' );
		$log_table  = $wpdb->prefix . 'ecwp_send_log';
		$sub_table  = $wpdb->prefix . 'ecwp_subscribers';
		$sent_count = 0;

		foreach ( $subscriber_ids as $sub_id ) {
			$subscriber = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$sub_table} WHERE id = %d AND status = 'active'",
				$sub_id
			) );

			if ( ! $subscriber ) {
				continue;
			}

			$html = $this->inject_preview_text( $campaign->html_content, $campaign->preview_text ?? '' );
			// Inject first-party tracking links before personalisation (so tracking
			// URLs themselves don't get mangled by merge-tag replacement).
			if ( $custom_tracking ) {
				$html = $link_tracker->inject_tracking_links( $html, $campaign_id, $sub_id );
			}
			$html = $this->personalize( $html, $subscriber, $campaign_id );

			// Insert a pending log row first (so unsent check excludes this sub).
			$wpdb->insert( $log_table, [
				'campaign_id'   => $campaign_id,
				'subscriber_id' => $sub_id,
				'status'        => 'pending',
				'sent_at'       => null,
			] );
			$log_id = $wpdb->insert_id;

			// When custom link tracking is on, tell Mailgun not to wrap our
			// already-wrapped links a second time.
			if ( $custom_tracking ) {
				add_filter( 'ecwp_mailgun_click_tracking', '__return_false', 99 );
			}

			$result = $mailgun->send_email(
				$subscriber->email,
				trim( $subscriber->first_name . ' ' . $subscriber->last_name ),
				$from_name,
				$from_email,
				$campaign->subject,
				$html,
				'',
				[ 'ecwp-campaign-' . $campaign_id, 'batch-' . $batch_number ]
			);

			if ( $custom_tracking ) {
				remove_filter( 'ecwp_mailgun_click_tracking', '__return_false', 99 );
			}

			if ( is_wp_error( $result ) ) {
				$wpdb->update( $log_table, [ 'status' => 'failed' ], [ 'id' => $log_id ] );
			} else {
				// Mailgun message IDs come back wrapped in angle brackets.
				$message_id = isset( $result['id'] ) ? trim( $result['id'], '<>' ) : '';
				$wpdb->update( $log_table, [
					'status'     => 'sent',
					'message_id' => $message_id,
					'sent_at'    => current_time( 'mysql' ),
				], [ 'id' => $log_id ] );
				$sent_count++;
			}
		}

		// Increment the campaign-level counter.
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}ecwp_campaigns SET total_sent = total_sent + %d WHERE id = %d",
			$sent_count,
			$campaign_id
		) );

		// If nothing left to send, mark as complete.
		if ( empty( $campaigns_class->get_unsent_subscribers( $campaign_id ) ) ) {
			$campaigns_class->update( $campaign_id, [ 'status' => 'sent' ] );
		}
	}

	/* ------------------------------------------------------------------ */
	/*  Personalization                                                     */
	/* ------------------------------------------------------------------ */

	/**
	 * Inject a hidden preheader/preview-text span at the top of the <body>.
	 * Email clients show this text as the inbox snippet beneath the subject line.
	 * Zero-width non-joiners pad the text so clients don't bleed email body copy
	 * into the preview when the preview text is short.
	 */
	private function inject_preview_text( $html, $preview_text ) {
		$preview_text = trim( $preview_text );
		if ( empty( $preview_text ) ) {
			return $html;
		}
		// Build padding: repeat &zwnj;&nbsp; enough times to fill 150 chars of preview space.
		$padding_char  = '&zwnj;&nbsp;';
		$padding_count = max( 0, (int) ceil( ( 150 - mb_strlen( $preview_text ) ) / 2 ) );
		$padding       = str_repeat( $padding_char, $padding_count );

		$span = '<span style="display:none;font-size:1px;color:#ffffff;max-height:0;max-width:0;opacity:0;overflow:hidden;">'
		      . esc_html( $preview_text )
		      . $padding
		      . '</span>';

		// Inject right after <body ...> if it exists, otherwise prepend.
		if ( preg_match( '/<body[^>]*>/i', $html ) ) {
			return preg_replace( '/(<body[^>]*>)/i', '$1' . $span, $html, 1 );
		}
		return $span . $html;
	}

	private function personalize( $html, $subscriber, $campaign_id ) {
		$unsubscribe_url  = $this->build_unsubscribe_url( $subscriber->email );
		$unsubscribe_link = '<a href="' . esc_url( $unsubscribe_url ) . '" style="color:#999;">Unsubscribe</a>';

		$replacements = [
			'{{first_name}}'       => ! empty( $subscriber->first_name ) ? esc_html( $subscriber->first_name ) : '',
			'{{last_name}}'        => ! empty( $subscriber->last_name )  ? esc_html( $subscriber->last_name )  : '',
			'{{email}}'            => esc_html( $subscriber->email ),
			'{{unsubscribe_url}}'  => esc_url( $unsubscribe_url ),
			'{{unsubscribe_link}}' => $unsubscribe_link,
		];

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $html );
	}

	private function build_unsubscribe_url( $email ) {
		return add_query_arg( [
			'email' => urlencode( $email ),
			'token' => $this->unsubscribe_token( $email ),
		], home_url( '/ecwp-unsubscribe/' ) );
	}

	public function unsubscribe_token( $email ) {
		$secret = get_option( 'ecwp_token_secret' );
		if ( ! $secret ) {
			$secret = wp_generate_password( 64, true, true );
			update_option( 'ecwp_token_secret', $secret );
		}
		return hash_hmac( 'sha256', strtolower( trim( $email ) ), $secret );
	}

	/** Manual trigger from admin panel. */
	public function manual_trigger( $campaign_id ) {
		$this->start_campaign_sending( $campaign_id );
	}
}
