<?php
/**
 * Scheduling, batch sending, and personalization.
 *
 * @package EmailCampaignWP
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Scheduler {

	public function init() {
		add_action( 'ecwp_daily_trigger', [ $this, 'run_daily_trigger' ] );
		add_action( 'ecwp_send_batch',    [ $this, 'send_batch' ], 10, 3 );

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

		$mailgun    = new ECWP_Mailgun();
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

			$html = $this->personalize( $campaign->html_content, $subscriber, $campaign_id );

			// Insert a pending log row first (so unsent check excludes this sub).
			$wpdb->insert( $log_table, [
				'campaign_id'   => $campaign_id,
				'subscriber_id' => $sub_id,
				'status'        => 'pending',
				'sent_at'       => null,
			] );
			$log_id = $wpdb->insert_id;

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
