<?php
/**
 * Fired during plugin activation.
 * Creates / upgrades all custom DB tables and sets default options.
 *
 * @package EmailCampaignWP
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Activator {

	public static function activate() {
		self::create_tables();
		self::set_defaults();
		update_option( 'ecwp_db_version', ECWP_VERSION );
		flush_rewrite_rules();
	}

	/* ------------------------------------------------------------------ */
	/*  Tables                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * Explicitly add any columns that dbDelta may have failed to create on
	 * an already-existing table (TEXT NOT NULL DEFAULT '' is refused by some
	 * MySQL/MariaDB versions, causing dbDelta to silently skip the column).
	 */
	private static function upgrade_columns() {
		global $wpdb;

		/* ── Campaigns table ──────────────────────────────────────────── */
		$campaigns = $wpdb->prefix . 'ecwp_campaigns';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $campaigns ) ) === $campaigns ) {
			$existing = array_column( $wpdb->get_results( "SHOW COLUMNS FROM {$campaigns}" ), 'Field' );

			if ( ! in_array( 'target_type', $existing, true ) ) {
				$wpdb->query( "ALTER TABLE {$campaigns} ADD COLUMN target_type VARCHAR(20) NOT NULL DEFAULT 'all' AFTER status" );
			}
			if ( ! in_array( 'target_tags', $existing, true ) ) {
				$wpdb->query( "ALTER TABLE {$campaigns} ADD COLUMN target_tags TEXT AFTER target_type" );
			}
			if ( ! in_array( 'preview_text', $existing, true ) ) {
				$wpdb->query( "ALTER TABLE {$campaigns} ADD COLUMN preview_text VARCHAR(255) NOT NULL DEFAULT '' AFTER subject" );
			}
		}

		/* ── Subscribers table — optional contact fields ──────────────── */
		$subs = $wpdb->prefix . 'ecwp_subscribers';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $subs ) ) === $subs ) {
			$existing = array_column( $wpdb->get_results( "SHOW COLUMNS FROM {$subs}" ), 'Field' );

			if ( ! in_array( 'phone', $existing, true ) ) {
				$wpdb->query( "ALTER TABLE {$subs} ADD COLUMN phone VARCHAR(50) NOT NULL DEFAULT '' AFTER last_name" );
			}
			if ( ! in_array( 'address', $existing, true ) ) {
				$wpdb->query( "ALTER TABLE {$subs} ADD COLUMN address VARCHAR(500) NOT NULL DEFAULT '' AFTER phone" );
			}
			if ( ! in_array( 'website', $existing, true ) ) {
				$wpdb->query( "ALTER TABLE {$subs} ADD COLUMN website VARCHAR(255) NOT NULL DEFAULT '' AFTER address" );
			}
			if ( ! in_array( 'notes', $existing, true ) ) {
				$wpdb->query( "ALTER TABLE {$subs} ADD COLUMN notes TEXT AFTER website" );
			}
		}

		/* ── Automations table — add delay_unit for existing installs ─── */
		$autos = $wpdb->prefix . 'ecwp_automations';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $autos ) ) === $autos ) {
			$existing_auto = array_column( $wpdb->get_results( "SHOW COLUMNS FROM {$autos}" ), 'Field' );
			if ( ! in_array( 'delay_unit', $existing_auto, true ) ) {
				$wpdb->query( "ALTER TABLE {$autos} ADD COLUMN delay_unit VARCHAR(20) NOT NULL DEFAULT 'days' AFTER delay_days" );
			}
		}
	}

	private static function create_tables() {
		global $wpdb;
		$c = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Explicit column migrations must run before dbDelta on the same table.
		self::upgrade_columns();

		/* ── Subscribers ──────────────────────────────────────────────── */
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_subscribers (
			id              BIGINT(20)   NOT NULL AUTO_INCREMENT,
			email           VARCHAR(255) NOT NULL,
			first_name      VARCHAR(100) NOT NULL DEFAULT '',
			last_name       VARCHAR(100) NOT NULL DEFAULT '',
			phone           VARCHAR(50)  NOT NULL DEFAULT '',
			address         VARCHAR(500) NOT NULL DEFAULT '',
			website         VARCHAR(255) NOT NULL DEFAULT '',
			notes           TEXT,
			status          VARCHAR(20)  NOT NULL DEFAULT 'active',
			subscribed_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
			unsubscribed_at DATETIME     DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY email (email)
		) $c;" );

		/* ── Tags ─────────────────────────────────────────────────────── */
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_tags (
			id         BIGINT(20)   NOT NULL AUTO_INCREMENT,
			name       VARCHAR(100) NOT NULL,
			color      VARCHAR(20)  NOT NULL DEFAULT '#3b82f6',
			created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uq_tag_name (name)
		) $c;" );

		/* ── Subscriber–Tag pivot ─────────────────────────────────────── */
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_subscriber_tags (
			subscriber_id BIGINT(20) NOT NULL,
			tag_id        BIGINT(20) NOT NULL,
			PRIMARY KEY (subscriber_id, tag_id),
			KEY idx_tag_id (tag_id)
		) $c;" );

		/* ── Templates ────────────────────────────────────────────────── */
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_templates (
			id         BIGINT(20)   NOT NULL AUTO_INCREMENT,
			name       VARCHAR(191) NOT NULL,
			subject    VARCHAR(255) NOT NULL DEFAULT '',
			html       LONGTEXT     NOT NULL,
			is_system  TINYINT(1)   NOT NULL DEFAULT 0,
			created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $c;" );

		/* ── Campaigns ────────────────────────────────────────────────── */
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_campaigns (
			id               BIGINT(20)   NOT NULL AUTO_INCREMENT,
			name             VARCHAR(255) NOT NULL,
			subject          VARCHAR(255) NOT NULL DEFAULT '',
			preview_text     VARCHAR(255) NOT NULL DEFAULT '',
			html_content     LONGTEXT     NOT NULL DEFAULT '',
			status           VARCHAR(20)  NOT NULL DEFAULT 'draft',
			target_type      VARCHAR(20)  NOT NULL DEFAULT 'all',
			target_tags      TEXT,
			send_time        VARCHAR(10)  NOT NULL DEFAULT '10:00',
			schedule_enabled TINYINT(1)  NOT NULL DEFAULT 0,
			batch_size       INT          NOT NULL DEFAULT 10,
			batch_interval   INT          NOT NULL DEFAULT 30,
			total_sent       INT          NOT NULL DEFAULT 0,
			created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP,
			updated_at       DATETIME     DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $c;" );

		/* ── Campaign–Subscriber pivot ────────────────────────────────── */
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_campaign_subscribers (
			id            BIGINT(20) NOT NULL AUTO_INCREMENT,
			campaign_id   BIGINT(20) NOT NULL,
			subscriber_id BIGINT(20) NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY campaign_subscriber (campaign_id, subscriber_id),
			KEY campaign_id   (campaign_id),
			KEY subscriber_id (subscriber_id)
		) $c;" );

		/* ── Send log ─────────────────────────────────────────────────── */
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_send_log (
			id            BIGINT(20)   NOT NULL AUTO_INCREMENT,
			campaign_id   BIGINT(20)   NOT NULL,
			subscriber_id BIGINT(20)   NOT NULL,
			message_id    VARCHAR(255) NOT NULL DEFAULT '',
			status        VARCHAR(30)  NOT NULL DEFAULT 'pending',
			sent_at       DATETIME     DEFAULT NULL,
			PRIMARY KEY (id),
			KEY campaign_id   (campaign_id),
			KEY subscriber_id (subscriber_id),
			KEY message_id    (message_id)
		) $c;" );

		/* ── Analytics / webhook events ───────────────────────────────── */
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_analytics (
			id          BIGINT(20)   NOT NULL AUTO_INCREMENT,
			campaign_id BIGINT(20)   NOT NULL DEFAULT 0,
			message_id  VARCHAR(255) NOT NULL DEFAULT '',
			event_type  VARCHAR(50)  NOT NULL,
			recipient   VARCHAR(255) NOT NULL DEFAULT '',
			raw_data    TEXT         NOT NULL DEFAULT '',
			created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY campaign_id (campaign_id),
			KEY message_id  (message_id),
			KEY event_type  (event_type)
		) $c;" );

		/* ── First-party link click tracking ─────────────────────────── */
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_link_clicks (
			id             BIGINT(20)   NOT NULL AUTO_INCREMENT,
			campaign_id    BIGINT(20)   NOT NULL DEFAULT 0,
			subscriber_id  BIGINT(20)   NOT NULL DEFAULT 0,
			email          VARCHAR(255) NOT NULL DEFAULT '',
			link_url       TEXT         NOT NULL,
			link_hash      VARCHAR(32)  NOT NULL DEFAULT '',
			clicked_at     DATETIME     NOT NULL,
			ip_address     VARCHAR(45)  NOT NULL DEFAULT '',
			user_agent     VARCHAR(500) NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			KEY campaign_id   (campaign_id),
			KEY subscriber_id (subscriber_id),
			KEY link_hash     (link_hash),
			KEY clicked_at    (clicked_at)
		) $c;" );

		/* ── Drip automations ─────────────────────────────────────────── */
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_automations (
			id                    BIGINT(20)   NOT NULL AUTO_INCREMENT,
			name                  VARCHAR(255) NOT NULL DEFAULT '',
			trigger_campaign_id   BIGINT(20)   NOT NULL,
			followup_campaign_id  BIGINT(20)   NOT NULL,
			condition             VARCHAR(50)  NOT NULL DEFAULT 'not_clicked',
			delay_days            INT          NOT NULL DEFAULT 5,
			delay_unit            VARCHAR(20)  NOT NULL DEFAULT 'days',
			status                VARCHAR(20)  NOT NULL DEFAULT 'active',
			last_run_at           DATETIME     DEFAULT NULL,
			total_sent            INT          NOT NULL DEFAULT 0,
			created_at            DATETIME     NOT NULL,
			PRIMARY KEY (id),
			KEY trigger_campaign_id  (trigger_campaign_id),
			KEY followup_campaign_id (followup_campaign_id),
			KEY status               (status)
		) $c;" );

		/* ── Automation send log ───────────────────────────────────────── */
		dbDelta( "CREATE TABLE {$wpdb->prefix}ecwp_automation_log (
			id             BIGINT(20)   NOT NULL AUTO_INCREMENT,
			automation_id  BIGINT(20)   NOT NULL,
			subscriber_id  BIGINT(20)   NOT NULL DEFAULT 0,
			email          VARCHAR(255) NOT NULL DEFAULT '',
			campaign_id    BIGINT(20)   NOT NULL DEFAULT 0,
			message_id     VARCHAR(255) NOT NULL DEFAULT '',
			sent_at        DATETIME     NOT NULL,
			PRIMARY KEY (id),
			KEY automation_id (automation_id),
			KEY subscriber_id (subscriber_id),
			UNIQUE KEY automation_email (automation_id, email)
		) $c;" );

		update_option( 'ecwp_db_version', ECWP_VERSION );
	}

	/* ------------------------------------------------------------------ */
	/*  Defaults                                                            */
	/* ------------------------------------------------------------------ */

	private static function set_defaults() {
		$defaults = [
			'ecwp_from_name'        => 'Rodrick Cox',
			'ecwp_from_email'       => 'info@ideaboss.io',
			'ecwp_send_time'        => '10:00',
			'ecwp_schedule_enabled' => '0',
			'ecwp_batch_size'       => '10',
			'ecwp_batch_interval'   => '30',
			'ecwp_mailgun_region'   => 'us',
		];

		foreach ( $defaults as $key => $value ) {
			if ( get_option( $key ) === false ) {
				add_option( $key, $value );
			}
		}

		if ( ! get_option( 'ecwp_token_secret' ) ) {
			update_option( 'ecwp_token_secret', wp_generate_password( 64, true, true ) );
		}
	}
}
