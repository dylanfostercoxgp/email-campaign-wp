<?php
/**
 * ECWP_Automations — drip automation engine.
 *
 * Stores rules (ecwp_automations) and per-subscriber send history
 * (ecwp_automation_log).  A WP-Cron hook fires daily to evaluate all
 * active automations and dispatch follow-up emails via Mailgun.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Automations {

	/* ------------------------------------------------------------------ */
	/*  Init / hooks                                                        */
	/* ------------------------------------------------------------------ */

	public function init() {
		add_action( 'ecwp_evaluate_automations', [ $this, 'evaluate_all' ] );

		// Register the daily cron event if not already scheduled.
		if ( ! wp_next_scheduled( 'ecwp_evaluate_automations' ) ) {
			wp_schedule_event( time(), 'daily', 'ecwp_evaluate_automations' );
		}
	}

	/* ------------------------------------------------------------------ */
	/*  Table helpers                                                       */
	/* ------------------------------------------------------------------ */

	private function table() {
		global $wpdb;
		return $wpdb->prefix . 'ecwp_automations';
	}

	private function log_table() {
		global $wpdb;
		return $wpdb->prefix . 'ecwp_automation_log';
	}

	/* ------------------------------------------------------------------ */
	/*  CRUD                                                                */
	/* ------------------------------------------------------------------ */

	/**
	 * Return all automations, newest first, joined with campaign names.
	 */
	public function get_all() {
		global $wpdb;
		$t  = $this->table();
		$c  = $wpdb->prefix . 'ecwp_campaigns';
		return $wpdb->get_results(
			"SELECT a.*,
			        tc.subject AS trigger_subject,
			        fc.subject AS followup_subject
			 FROM {$t} a
			 LEFT JOIN {$c} tc ON tc.id = a.trigger_campaign_id
			 LEFT JOIN {$c} fc ON fc.id = a.followup_campaign_id
			 ORDER BY a.id DESC"
		);
	}

	/**
	 * Return a single automation row (with campaign subjects).
	 */
	public function get_by_id( $id ) {
		global $wpdb;
		$t = $this->table();
		$c = $wpdb->prefix . 'ecwp_campaigns';
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT a.*,
			        tc.subject AS trigger_subject,
			        fc.subject AS followup_subject
			 FROM {$t} a
			 LEFT JOIN {$c} tc ON tc.id = a.trigger_campaign_id
			 LEFT JOIN {$c} fc ON fc.id = a.followup_campaign_id
			 WHERE a.id = %d",
			$id
		) );
	}

	/**
	 * Create a new automation.
	 *
	 * @param  array $data  Keys: name, trigger_campaign_id, followup_campaign_id,
	 *                             condition, delay_days
	 * @return int|false  Inserted ID or false on failure.
	 */
	public function create( $data ) {
		global $wpdb;
		$ok = $wpdb->insert( $this->table(), [
			'name'                  => sanitize_text_field( $data['name'] ),
			'trigger_campaign_id'   => intval( $data['trigger_campaign_id'] ),
			'followup_campaign_id'  => intval( $data['followup_campaign_id'] ),
			'condition'             => sanitize_key( $data['condition'] ),
			'delay_days'            => max( 1, intval( $data['delay_days'] ) ),
			'status'                => 'active',
			'last_run_at'           => null,
			'total_sent'            => 0,
			'created_at'            => current_time( 'mysql' ),
		] );
		return $ok ? $wpdb->insert_id : false;
	}

	/**
	 * Update fields on an automation.
	 */
	public function update( $id, $data ) {
		global $wpdb;
		$allowed = [ 'name', 'trigger_campaign_id', 'followup_campaign_id',
		             'condition', 'delay_days', 'status', 'last_run_at', 'total_sent' ];
		$clean = [];
		foreach ( $allowed as $k ) {
			if ( array_key_exists( $k, $data ) ) {
				$clean[ $k ] = $data[ $k ];
			}
		}
		if ( empty( $clean ) ) { return false; }
		return $wpdb->update( $this->table(), $clean, [ 'id' => intval( $id ) ] );
	}

	/**
	 * Delete an automation and its log.
	 */
	public function delete( $id ) {
		global $wpdb;
		$wpdb->delete( $this->log_table(), [ 'automation_id' => intval( $id ) ] );
		return $wpdb->delete( $this->table(), [ 'id' => intval( $id ) ] );
	}

	/* ------------------------------------------------------------------ */
	/*  Log queries                                                         */
	/* ------------------------------------------------------------------ */

	/**
	 * Return the send log for one automation (newest first).
	 *
	 * @param  int $automation_id
	 * @param  int $limit          0 = no limit
	 * @return array
	 */
	public function get_log( $automation_id, $limit = 0 ) {
		global $wpdb;
		$lt = $this->log_table();
		$st = $wpdb->prefix . 'ecwp_subscribers';
		$limit_sql = $limit ? $wpdb->prepare( 'LIMIT %d', $limit ) : '';
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT l.*, s.first_name, s.last_name, s.status AS subscriber_status
			 FROM {$lt} l
			 LEFT JOIN {$st} s ON s.id = l.subscriber_id
			 WHERE l.automation_id = %d
			 ORDER BY l.sent_at DESC {$limit_sql}",
			$automation_id
		) );
	}

	/**
	 * Total number of log entries for an automation.
	 */
	public function get_log_count( $automation_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->log_table()} WHERE automation_id = %d",
			$automation_id
		) );
	}

	/* ------------------------------------------------------------------ */
	/*  Evaluation engine                                                   */
	/* ------------------------------------------------------------------ */

	/**
	 * Evaluate all active automations.  Called by WP-Cron daily.
	 */
	public function evaluate_all() {
		global $wpdb;
		$automations = $wpdb->get_results(
			"SELECT * FROM {$this->table()} WHERE status = 'active'"
		);
		foreach ( $automations as $auto ) {
			$this->evaluate( $auto );
		}
	}

	/**
	 * Evaluate a single automation and send the follow-up to eligible subscribers.
	 *
	 * @param  object $auto  Row from ecwp_automations.
	 * @return int  Number of emails dispatched this run.
	 */
	public function evaluate( $auto ) {
		global $wpdb;

		// ── 1. Fetch the trigger campaign ─────────────────────────────────
		$trigger = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ecwp_campaigns WHERE id = %d",
			$auto->trigger_campaign_id
		) );

		if ( ! $trigger || $trigger->status !== 'sent' ) {
			return 0; // Campaign hasn't been fully sent yet.
		}

		// ── 2. Check that the wait period has elapsed ──────────────────────
		// Use the campaign's last send_log sent_at timestamp as "campaign sent" time.
		$sent_at_str = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(sent_at) FROM {$wpdb->prefix}ecwp_send_log
			 WHERE campaign_id = %d AND status = 'sent'",
			$auto->trigger_campaign_id
		) );

		if ( ! $sent_at_str ) {
			return 0;
		}

		$sent_ts   = strtotime( $sent_at_str );
		$ready_ts  = $sent_ts + ( intval( $auto->delay_days ) * DAY_IN_SECONDS );

		if ( time() < $ready_ts ) {
			return 0; // Not yet time to evaluate.
		}

		// ── 3. Get all recipients of the trigger campaign ─────────────────
		$recipients = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT sl.subscriber_id
			 FROM {$wpdb->prefix}ecwp_send_log sl
			 INNER JOIN {$wpdb->prefix}ecwp_subscribers s ON s.id = sl.subscriber_id
			 WHERE sl.campaign_id = %d
			   AND sl.status      = 'sent'
			   AND s.status       = 'active'",
			$auto->trigger_campaign_id
		) );

		if ( empty( $recipients ) ) {
			return 0;
		}

		// ── 4. Filter by condition ─────────────────────────────────────────
		// Build a set of subscriber_ids who ALREADY MET the condition (i.e., engaged)
		// and exclude them from the follow-up.
		$engaged_emails = [];

		switch ( $auto->condition ) {

			case 'not_opened':
				// Exclude anyone who opened the trigger campaign.
				$engaged_emails = $wpdb->get_col( $wpdb->prepare(
					"SELECT DISTINCT a.recipient
					 FROM {$wpdb->prefix}ecwp_analytics a
					 WHERE a.campaign_id = %d AND a.event_type = 'opened'",
					$auto->trigger_campaign_id
				) );
				break;

			case 'opened_not_clicked':
				// Keep only people who opened but did NOT click.
				$opened_emails = $wpdb->get_col( $wpdb->prepare(
					"SELECT DISTINCT recipient FROM {$wpdb->prefix}ecwp_analytics
					 WHERE campaign_id = %d AND event_type = 'opened'",
					$auto->trigger_campaign_id
				) );
				$clicked_emails = $wpdb->get_col( $wpdb->prepare(
					"SELECT DISTINCT recipient FROM {$wpdb->prefix}ecwp_analytics
					 WHERE campaign_id = %d AND event_type = 'clicked'",
					$auto->trigger_campaign_id
				) );
				// Engaged = (opened AND clicked) OR (did not open at all — not our target)
				// We want only: opened && !clicked → $engaged_emails = clicked ∪ never_opened
				// Simpler: start from opened; exclude those who clicked.
				$opened_set  = array_flip( $opened_emails );
				$clicked_set = array_flip( $clicked_emails );
				// People who opened but DID click → not eligible.  People who didn't open → not eligible.
				// So eligible = opened && !clicked.
				// We'll keep recipients in "opened" who are not in "clicked".
				// For this branch, $engaged_emails = those we EXCLUDE:
				// exclude clicked (they converted) + exclude never-opened (they didn't open at all)
				$never_opened = $wpdb->get_col( $wpdb->prepare(
					"SELECT DISTINCT sl.subscriber_id
					 FROM {$wpdb->prefix}ecwp_send_log sl
					 WHERE sl.campaign_id = %d AND sl.status = 'sent'
					   AND NOT EXISTS (
					     SELECT 1 FROM {$wpdb->prefix}ecwp_analytics a
					     WHERE a.campaign_id = sl.campaign_id
					       AND a.recipient   = (
					           SELECT email FROM {$wpdb->prefix}ecwp_subscribers
					           WHERE id = sl.subscriber_id LIMIT 1
					         )
					       AND a.event_type  = 'opened'
					   )",
					$auto->trigger_campaign_id
				) );
				// We'll handle this differently below — override recipient list.
				// Build eligible_sub_ids directly for this condition.
				$all_recipient_emails = $wpdb->get_col( $wpdb->prepare(
					"SELECT s.email
					 FROM {$wpdb->prefix}ecwp_send_log sl
					 INNER JOIN {$wpdb->prefix}ecwp_subscribers s ON s.id = sl.subscriber_id
					 WHERE sl.campaign_id = %d AND sl.status = 'sent' AND s.status = 'active'",
					$auto->trigger_campaign_id
				) );
				// eligible = opened && !clicked
				$eligible_emails = array_diff(
					array_intersect( $all_recipient_emails, $opened_emails ),
					$clicked_emails
				);
				// We'll continue with email-based filtering; skip the normal sub_id path below.
				return $this->_send_followup_to_emails( $auto, array_values( $eligible_emails ) );

			case 'not_clicked':
			default:
				// Exclude anyone who clicked in the trigger campaign.
				$engaged_emails = $wpdb->get_col( $wpdb->prepare(
					"SELECT DISTINCT a.recipient
					 FROM {$wpdb->prefix}ecwp_analytics a
					 WHERE a.campaign_id = %d AND a.event_type = 'clicked'",
					$auto->trigger_campaign_id
				) );
				break;
		}

		// ── 5. Build eligible subscriber_id list ───────────────────────────
		// Fetch emails for all recipient sub_ids so we can compare against engaged_emails.
		$recipients_with_email = $wpdb->get_results(
			"SELECT s.id, s.email
			 FROM {$wpdb->prefix}ecwp_subscribers s
			 WHERE s.id IN (" . implode( ',', array_map( 'intval', $recipients ) ) . ")"
		);

		$engaged_set = array_flip( $engaged_emails );
		$eligible_emails = [];
		foreach ( $recipients_with_email as $r ) {
			if ( ! isset( $engaged_set[ $r->email ] ) ) {
				$eligible_emails[] = $r->email;
			}
		}

		if ( empty( $eligible_emails ) ) {
			return 0;
		}

		return $this->_send_followup_to_emails( $auto, $eligible_emails );
	}

	/**
	 * Internal: send the follow-up campaign to a list of email addresses,
	 * skipping any already in the automation log, and recording each send.
	 *
	 * @param  object $auto
	 * @param  array  $eligible_emails
	 * @return int  Number sent.
	 */
	private function _send_followup_to_emails( $auto, $eligible_emails ) {
		global $wpdb;

		if ( empty( $eligible_emails ) ) {
			return 0;
		}

		// ── Exclude already-sent ───────────────────────────────────────────
		$already_sent = $wpdb->get_col( $wpdb->prepare(
			"SELECT email FROM {$this->log_table()} WHERE automation_id = %d",
			$auto->id
		) );
		$already_set  = array_flip( $already_sent );
		$to_send      = array_filter( $eligible_emails, function( $e ) use ( $already_set ) {
			return ! isset( $already_set[ $e ] );
		} );

		if ( empty( $to_send ) ) {
			$this->update( $auto->id, [ 'last_run_at' => current_time( 'mysql' ) ] );
			return 0;
		}

		// ── Fetch follow-up campaign ───────────────────────────────────────
		$followup = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ecwp_campaigns WHERE id = %d",
			$auto->followup_campaign_id
		) );

		if ( ! $followup ) {
			return 0;
		}

		// ── Send via Mailgun ───────────────────────────────────────────────
		$mailgun         = new ECWP_Mailgun();
		$scheduler       = new ECWP_Scheduler();
		$link_tracker    = new ECWP_Link_Tracker();
		$custom_tracking = get_option( 'ecwp_custom_link_tracking', '0' ) === '1';
		$from_name  = get_option( 'ecwp_from_name',  '' );
		$from_email = get_option( 'ecwp_from_email', '' );
		$sent_count = 0;
		$log_table  = $this->log_table();
		$send_log   = $wpdb->prefix . 'ecwp_send_log';
		$sub_table  = $wpdb->prefix . 'ecwp_subscribers';

		foreach ( $to_send as $email ) {
			$subscriber = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$sub_table} WHERE email = %s AND status = 'active' LIMIT 1",
				$email
			) );

			if ( ! $subscriber ) {
				continue;
			}

			// Personalise the follow-up HTML (reuse scheduler helpers via a thin wrapper).
			$html = $this->_personalise( $followup->html_content, $followup->preview_text ?? '', $subscriber, $auto->followup_campaign_id );
			// Inject first-party tracking links if enabled.
			if ( $custom_tracking ) {
				$html = $link_tracker->inject_tracking_links( $html, (int) $auto->followup_campaign_id, (int) $subscriber->id );
				add_filter( 'ecwp_mailgun_click_tracking', '__return_false', 99 );
			}

			// Log a pending row in the main send_log for analytics continuity.
			$wpdb->insert( $send_log, [
				'campaign_id'   => $auto->followup_campaign_id,
				'subscriber_id' => $subscriber->id,
				'status'        => 'pending',
				'sent_at'       => null,
			] );
			$send_log_id = $wpdb->insert_id;

			$result = $mailgun->send_email(
				$subscriber->email,
				trim( $subscriber->first_name . ' ' . $subscriber->last_name ),
				$from_name,
				$from_email,
				$followup->subject,
				$html,
				'',
				[ 'ecwp-automation-' . $auto->id, 'ecwp-campaign-' . $auto->followup_campaign_id ]
			);

			if ( $custom_tracking ) {
				remove_filter( 'ecwp_mailgun_click_tracking', '__return_false', 99 );
			}

			if ( is_wp_error( $result ) ) {
				$wpdb->update( $send_log, [ 'status' => 'failed' ], [ 'id' => $send_log_id ] );
				continue;
			}

			$message_id = isset( $result['id'] ) ? trim( $result['id'], '<>' ) : '';

			// Update main send_log.
			$wpdb->update( $send_log, [
				'status'     => 'sent',
				'message_id' => $message_id,
				'sent_at'    => current_time( 'mysql' ),
			], [ 'id' => $send_log_id ] );

			// Insert into automation_log (prevents re-send).
			$wpdb->insert( $log_table, [
				'automation_id' => (int) $auto->id,
				'subscriber_id' => (int) $subscriber->id,
				'email'         => $subscriber->email,
				'campaign_id'   => (int) $auto->followup_campaign_id,
				'message_id'    => $message_id,
				'sent_at'       => current_time( 'mysql' ),
			] );

			$sent_count++;
		}

		// ── Update automation totals ───────────────────────────────────────
		if ( $sent_count > 0 ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$this->table()}
				 SET total_sent = total_sent + %d, last_run_at = %s
				 WHERE id = %d",
				$sent_count,
				current_time( 'mysql' ),
				$auto->id
			) );
		} else {
			$this->update( $auto->id, [ 'last_run_at' => current_time( 'mysql' ) ] );
		}

		return $sent_count;
	}

	/* ------------------------------------------------------------------ */
	/*  Personalisation helpers (mirrors ECWP_Scheduler — no dependency)   */
	/* ------------------------------------------------------------------ */

	private function _personalise( $html, $preview_text, $subscriber, $campaign_id ) {
		// Inject preview text.
		$preview_text = trim( $preview_text );
		if ( $preview_text ) {
			$padding = str_repeat( '&zwnj;&nbsp;', max( 0, (int) ceil( ( 150 - mb_strlen( $preview_text ) ) / 2 ) ) );
			$span = '<span style="display:none;font-size:1px;color:#ffffff;max-height:0;max-width:0;opacity:0;overflow:hidden;">'
			      . esc_html( $preview_text ) . $padding . '</span>';
			if ( preg_match( '/<body[^>]*>/i', $html ) ) {
				$html = preg_replace( '/(<body[^>]*>)/i', '$1' . $span, $html, 1 );
			} else {
				$html = $span . $html;
			}
		}

		// Replace merge tags.
		$secret          = get_option( 'ecwp_token_secret', '' );
		$token           = hash_hmac( 'sha256', $subscriber->email, $secret );
		$unsubscribe_url = add_query_arg( [
			'email' => urlencode( $subscriber->email ),
			'token' => $token,
		], home_url( '/ecwp-unsubscribe/' ) );

		$replacements = [
			'{{first_name}}'       => esc_html( $subscriber->first_name ?? '' ),
			'{{last_name}}'        => esc_html( $subscriber->last_name  ?? '' ),
			'{{email}}'            => esc_html( $subscriber->email ),
			'{{unsubscribe_url}}'  => esc_url( $unsubscribe_url ),
			'{{unsubscribe_link}}' => '<a href="' . esc_url( $unsubscribe_url ) . '" style="color:#999;">Unsubscribe</a>',
		];

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $html );
	}
}
