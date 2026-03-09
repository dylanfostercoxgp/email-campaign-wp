<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Activator {

	public static function activate() {
		global $wpdb;
		$c = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// ── Subscribers ───────────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_subscribers (
			id            BIGINT(20)   NOT NULL AUTO_INCREMENT,
			email         VARCHAR(255) NOT NULL,
			first_name    VARCHAR(100) DEFAULT '',
			last_name     VARCHAR(100) DEFAULT '',
			status        VARCHAR(20)  DEFAULT 'active',
			subscribed_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
			unsubscribed_at DATETIME   DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY email (email)
		) $c;" );

		// ── Campaigns ─────────────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_campaigns (
			id               BIGINT(20)   NOT NULL AUTO_INCREMENT,
			name             VARCHAR(255) NOT NULL,
			subject          VARCHAR(255) NOT NULL,
			html_content     LONGTEXT,
			status           VARCHAR(20)  DEFAULT 'draft',
			send_time        VARCHAR(10)  DEFAULT '10:00',
			schedule_enabled TINYINT(1)   DEFAULT 0,
			batch_size       INT          DEFAULT 10,
			batch_interval   INT          DEFAULT 30,
			total_sent       INT          DEFAULT 0,
			created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP,
			updated_at       DATETIME     DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $c;" );

		// ── Campaign ↔ Subscriber junction ───────────────────────────────
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_campaign_subscribers (
			id            BIGINT(20) NOT NULL AUTO_INCREMENT,
			campaign_id   BIGINT(20) NOT NULL,
			subscriber_id BIGINT(20) NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY campaign_subscriber (campaign_id, subscriber_id),
			KEY campaign_id   (campaign_id),
			KEY subscriber_id (subscriber_id)
		) $c;" );

		// ── Send log ──────────────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_send_log (
			id            BIGINT(20)   NOT NULL AUTO_INCREMENT,
			campaign_id   BIGINT(20)   NOT NULL,
			subscriber_id BIGINT(20)   NOT NULL,
			message_id    VARCHAR(255) DEFAULT '',
			status        VARCHAR(30)  DEFAULT 'pending',
			sent_at       DATETIME     DEFAULT NULL,
			PRIMARY KEY (id),
			KEY campaign_id   (campaign_id),
			KEY subscriber_id (subscriber_id),
			KEY message_id    (message_id)
		) $c;" );

		// ── Analytics events ─────────────────────────────────────────────
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_analytics (
			id         BIGINT(20)   NOT NULL AUTO_INCREMENT,
			message_id VARCHAR(255) NOT NULL,
			event_type VARCHAR(50)  NOT NULL,
			recipient  VARCHAR(255) DEFAULT '',
			event_data LONGTEXT,
			created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY message_id (message_id),
			KEY event_type (event_type)
		) $c;" );

		// ── Default options ───────────────────────────────────────────────
		add_option( 'ecwp_from_name',        'Rodrick Cox' );
		add_option( 'ecwp_from_email',       'info@ideaboss.io' );
		add_option( 'ecwp_send_time',        '10:00' );
		add_option( 'ecwp_schedule_enabled', '0' );
		add_option( 'ecwp_batch_size',       '10' );
		add_option( 'ecwp_batch_interval',   '30' );
		add_option( 'ecwp_mailgun_region',   'us' );

		if ( ! get_option( 'ecwp_token_secret' ) ) {
			update_option( 'ecwp_token_secret', wp_generate_password( 64, true, true ) );
		}

		flush_rewrite_rules();
	}
}
