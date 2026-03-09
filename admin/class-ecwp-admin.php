<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Admin {

	public function init() {
		add_action( 'admin_menu',            [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Form POST handlers.
		add_action( 'admin_post_ecwp_save_settings',       [ $this, 'save_settings' ] );
		add_action( 'admin_post_ecwp_import_subscribers',  [ $this, 'import_subscribers' ] );
		add_action( 'admin_post_ecwp_delete_subscriber',   [ $this, 'delete_subscriber' ] );
		add_action( 'admin_post_ecwp_create_campaign',     [ $this, 'create_campaign' ] );
		add_action( 'admin_post_ecwp_update_campaign',     [ $this, 'update_campaign' ] );
		add_action( 'admin_post_ecwp_delete_campaign',     [ $this, 'delete_campaign' ] );
		add_action( 'admin_post_ecwp_trigger_campaign',    [ $this, 'trigger_campaign' ] );
		add_action( 'admin_post_ecwp_pause_campaign',      [ $this, 'pause_campaign' ] );
		add_action( 'admin_post_ecwp_send_test',           [ $this, 'send_test_email' ] );
		add_action( 'admin_post_ecwp_test_mailgun',        [ $this, 'test_mailgun_connection' ] );
	}

	// ── Menu ───────────────────────────────────────────────────────────────

	public function register_menu() {
		add_menu_page(
			'Email Campaign WP',
			'Email Campaigns',
			'manage_options',
			'ecwp-dashboard',
			[ $this, 'page_dashboard' ],
			'dashicons-email-alt2',
			26
		);
		add_submenu_page( 'ecwp-dashboard', 'Dashboard',    'Dashboard',    'manage_options', 'ecwp-dashboard',   [ $this, 'page_dashboard' ] );
		add_submenu_page( 'ecwp-dashboard', 'Campaigns',    'Campaigns',    'manage_options', 'ecwp-campaigns',   [ $this, 'page_campaigns' ] );
		add_submenu_page( 'ecwp-dashboard', 'Subscribers',  'Subscribers',  'manage_options', 'ecwp-subscribers', [ $this, 'page_subscribers' ] );
		add_submenu_page( 'ecwp-dashboard', 'Analytics',    'Analytics',    'manage_options', 'ecwp-analytics',   [ $this, 'page_analytics' ] );
		add_submenu_page( 'ecwp-dashboard', 'Settings',     'Settings',     'manage_options', 'ecwp-settings',    [ $this, 'page_settings' ] );
	}

	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'ecwp' ) === false ) {
			return;
		}
		wp_enqueue_style(  'ecwp-admin', ECWP_PLUGIN_URL . 'admin/css/ecwp-admin.css', [],       ECWP_VERSION );
		wp_enqueue_script( 'ecwp-admin', ECWP_PLUGIN_URL . 'admin/js/ecwp-admin.js',  [ 'jquery' ], ECWP_VERSION, true );
		wp_localize_script( 'ecwp-admin', 'ecwpData', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'ecwp_nonce' ),
		] );
	}

	// ── Pages ──────────────────────────────────────────────────────────────

	public function page_dashboard() {
		global $wpdb;
		$subscribers = new ECWP_Subscribers();
		$campaigns   = new ECWP_Campaigns();

		$stats = [
			'active_subs'    => $subscribers->count( 'active' ),
			'unsub_count'    => $subscribers->count( 'unsubscribed' ),
			'total_campaigns'=> count( $campaigns->get_all() ),
			'active_campaigns'=> count( $campaigns->get_all( 'sending' ) ),
		];

		$log   = $wpdb->prefix . 'ecwp_send_log';
		$anal  = $wpdb->prefix . 'ecwp_analytics';

		$stats['total_sent']      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status NOT IN ('pending','failed')" );
		$stats['total_delivered'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status = 'delivered'" );
		$stats['total_opens']     = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT message_id) FROM {$anal} WHERE event_type = 'opened'" );
		$stats['total_clicks']    = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT message_id) FROM {$anal} WHERE event_type = 'clicked'" );
		$stats['total_bounces']   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status = 'bounced'" );

		$recent_campaigns = $campaigns->get_all();
		$recent_campaigns = array_slice( $recent_campaigns, 0, 5 );

		include ECWP_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	public function page_campaigns() {
		$campaigns   = new ECWP_Campaigns();
		$subscribers = new ECWP_Subscribers();
		$action      = sanitize_text_field( $_GET['action']      ?? 'list' );
		$campaign_id = intval(              $_GET['campaign_id'] ?? 0 );

		if ( $action === 'edit' && $campaign_id ) {
			$campaign             = $campaigns->get_by_id( $campaign_id );
			$campaign_subscribers = $campaigns->get_subscribers( $campaign_id );
			$all_subscribers      = $subscribers->get_all( 'active' );
			include ECWP_PLUGIN_DIR . 'admin/views/campaign-edit.php';
		} elseif ( $action === 'new' ) {
			$all_subscribers = $subscribers->get_all( 'active' );
			include ECWP_PLUGIN_DIR . 'admin/views/campaign-new.php';
		} else {
			global $wpdb;
			$all_campaigns = $campaigns->get_all();

			// Attach per-campaign send stats.
			$log = $wpdb->prefix . 'ecwp_send_log';
			foreach ( $all_campaigns as &$c ) {
				$c->sent_count = (int) $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM {$log} WHERE campaign_id = %d AND status NOT IN ('pending','failed')",
					$c->id
				) );
				$c->sub_count = $campaigns->get_subscriber_count( $c->id );
			}
			unset( $c );
			include ECWP_PLUGIN_DIR . 'admin/views/campaigns.php';
		}
	}

	public function page_subscribers() {
		$subscribers     = new ECWP_Subscribers();
		$all_subscribers = $subscribers->get_all();
		$active_count    = $subscribers->count( 'active' );
		$unsub_count     = $subscribers->count( 'unsubscribed' );
		include ECWP_PLUGIN_DIR . 'admin/views/subscribers.php';
	}

	public function page_analytics() {
		global $wpdb;
		$campaigns_obj = new ECWP_Campaigns();
		$all_campaigns = $campaigns_obj->get_all();
		$selected_id   = intval( $_GET['campaign_id'] ?? 0 );

		$log  = $wpdb->prefix . 'ecwp_send_log';
		$anal = $wpdb->prefix . 'ecwp_analytics';

		$where_log  = $selected_id ? $wpdb->prepare( ' WHERE campaign_id = %d', $selected_id ) : '';
		$where_log2 = $selected_id ? $wpdb->prepare( ' AND campaign_id = %d', $selected_id )   : '';

		$stats = [
			'sent'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log}{$where_log}" ),
			'delivered'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status='delivered'{$where_log2}" ),
			'opened'      => 0,
			'clicked'     => 0,
			'bounced'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status='bounced'{$where_log2}" ),
			'failed'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status='failed'{$where_log2}" ),
			'complained'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status='complained'{$where_log2}" ),
			'unsubscribed'=> (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status='unsubscribed'{$where_log2}" ),
		];

		if ( $selected_id ) {
			$msg_ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT message_id FROM {$log} WHERE campaign_id = %d AND message_id != ''",
				$selected_id
			) );
			if ( $msg_ids ) {
				$ph = implode( ',', array_fill( 0, count( $msg_ids ), '%s' ) );
				$stats['opened']  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT message_id) FROM {$anal} WHERE event_type='opened' AND message_id IN ({$ph})",  ...$msg_ids ) );
				$stats['clicked'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT message_id) FROM {$anal} WHERE event_type='clicked' AND message_id IN ({$ph})", ...$msg_ids ) );
			}
		} else {
			$stats['opened']  = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT message_id) FROM {$anal} WHERE event_type='opened'" );
			$stats['clicked'] = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT message_id) FROM {$anal} WHERE event_type='clicked'" );
		}

		$recent_events = $wpdb->get_results(
			"SELECT a.*, sl.campaign_id
			 FROM {$anal} a
			 LEFT JOIN {$log} sl ON a.message_id = sl.message_id
			 ORDER BY a.created_at DESC LIMIT 100"
		);

		// Per-campaign summary table.
		$campaign_stats = $wpdb->get_results(
			"SELECT c.id, c.name, c.status,
			        COUNT(sl.id)                                                         AS total_sent,
			        SUM(sl.status = 'delivered')                                         AS delivered,
			        SUM(sl.status = 'bounced')                                           AS bounced,
			        SUM(sl.status = 'opened')                                            AS opened,
			        SUM(sl.status = 'clicked')                                           AS clicked,
			        SUM(sl.status = 'unsubscribed')                                      AS unsubscribed
			 FROM {$wpdb->prefix}ecwp_campaigns c
			 LEFT JOIN {$log} sl ON c.id = sl.campaign_id
			 GROUP BY c.id
			 ORDER BY c.created_at DESC"
		);

		include ECWP_PLUGIN_DIR . 'admin/views/analytics.php';
	}

	public function page_settings() {
		include ECWP_PLUGIN_DIR . 'admin/views/settings.php';
	}

	// ── Form handlers ──────────────────────────────────────────────────────

	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_save_settings' ) ) {
			wp_die( 'Unauthorized' );
		}
		foreach ( [
			'ecwp_mailgun_api_key',
			'ecwp_mailgun_domain',
			'ecwp_mailgun_region',
			'ecwp_from_name',
			'ecwp_from_email',
			'ecwp_send_time',
			'ecwp_batch_size',
			'ecwp_batch_interval',
		] as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_option( $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}
		update_option( 'ecwp_schedule_enabled', isset( $_POST['ecwp_schedule_enabled'] ) ? '1' : '0' );

		// Rebuild cron schedule.
		( new ECWP_Scheduler() )->reschedule_daily_trigger();

		wp_redirect( admin_url( 'admin.php?page=ecwp-settings&saved=1' ) );
		exit;
	}

	public function import_subscribers() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_import_subscribers' ) ) {
			wp_die( 'Unauthorized' );
		}
		if ( empty( $_FILES['subscriber_csv'] ) || $_FILES['subscriber_csv']['error'] !== UPLOAD_ERR_OK ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-subscribers&import_error=upload_failed' ) );
			exit;
		}
		$file = $_FILES['subscriber_csv'];
		$ext  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( $ext !== 'csv' ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-subscribers&import_error=not_csv' ) );
			exit;
		}
		$result = ( new ECWP_Subscribers() )->import_csv( $file['tmp_name'] );
		if ( is_wp_error( $result ) ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-subscribers&import_error=' . urlencode( $result->get_error_message() ) ) );
			exit;
		}
		wp_redirect( admin_url( "admin.php?page=ecwp-subscribers&imported={$result['imported']}&skipped={$result['skipped']}" ) );
		exit;
	}

	public function delete_subscriber() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_delete_subscriber' ) ) {
			wp_die( 'Unauthorized' );
		}
		$id = intval( $_POST['subscriber_id'] ?? 0 );
		if ( $id ) {
			( new ECWP_Subscribers() )->delete( $id );
		}
		wp_redirect( admin_url( 'admin.php?page=ecwp-subscribers&deleted=1' ) );
		exit;
	}

	public function create_campaign() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_create_campaign' ) ) {
			wp_die( 'Unauthorized' );
		}
		$html = '';
		if ( ! empty( $_FILES['html_file'] ) && $_FILES['html_file']['error'] === UPLOAD_ERR_OK ) {
			$ext = strtolower( pathinfo( $_FILES['html_file']['name'], PATHINFO_EXTENSION ) );
			if ( in_array( $ext, [ 'html', 'htm' ], true ) ) {
				$html = file_get_contents( $_FILES['html_file']['tmp_name'] );
			}
		}
		$campaigns   = new ECWP_Campaigns();
		$campaign_id = $campaigns->create( [
			'name'             => $_POST['name']           ?? '',
			'subject'          => $_POST['subject']        ?? '',
			'html_content'     => $html,
			'send_time'        => $_POST['send_time']      ?? '10:00',
			'schedule_enabled' => isset( $_POST['schedule_enabled'] ) ? 1 : 0,
			'batch_size'       => intval( $_POST['batch_size']     ?? 10 ),
			'batch_interval'   => intval( $_POST['batch_interval'] ?? 30 ),
		] );
		if ( is_wp_error( $campaign_id ) ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-campaigns&action=new&error=' . urlencode( $campaign_id->get_error_message() ) ) );
			exit;
		}
		if ( isset( $_POST['assign_all'] ) ) {
			$campaigns->assign_all_subscribers( $campaign_id );
		} elseif ( ! empty( $_POST['subscriber_ids'] ) ) {
			$campaigns->assign_subscribers( $campaign_id, array_map( 'intval', $_POST['subscriber_ids'] ) );
		}
		wp_redirect( admin_url( 'admin.php?page=ecwp-campaigns&created=1' ) );
		exit;
	}

	public function update_campaign() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_update_campaign' ) ) {
			wp_die( 'Unauthorized' );
		}
		$campaign_id = intval( $_POST['campaign_id'] ?? 0 );
		if ( ! $campaign_id ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-campaigns' ) );
			exit;
		}
		$data = [
			'name'             => $_POST['name']           ?? '',
			'subject'          => $_POST['subject']        ?? '',
			'send_time'        => $_POST['send_time']      ?? '10:00',
			'schedule_enabled' => isset( $_POST['schedule_enabled'] ) ? 1 : 0,
			'batch_size'       => intval( $_POST['batch_size']     ?? 10 ),
			'batch_interval'   => intval( $_POST['batch_interval'] ?? 30 ),
		];
		if ( ! empty( $_FILES['html_file'] ) && $_FILES['html_file']['error'] === UPLOAD_ERR_OK ) {
			$ext = strtolower( pathinfo( $_FILES['html_file']['name'], PATHINFO_EXTENSION ) );
			if ( in_array( $ext, [ 'html', 'htm' ], true ) ) {
				$data['html_content'] = file_get_contents( $_FILES['html_file']['tmp_name'] );
			}
		}
		$campaigns = new ECWP_Campaigns();
		$campaigns->update( $campaign_id, $data );
		if ( isset( $_POST['assign_all'] ) ) {
			$campaigns->assign_all_subscribers( $campaign_id );
		} elseif ( ! empty( $_POST['subscriber_ids'] ) ) {
			$campaigns->assign_subscribers( $campaign_id, array_map( 'intval', $_POST['subscriber_ids'] ) );
		}
		wp_redirect( admin_url( "admin.php?page=ecwp-campaigns&action=edit&campaign_id={$campaign_id}&updated=1" ) );
		exit;
	}

	public function delete_campaign() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_delete_campaign' ) ) {
			wp_die( 'Unauthorized' );
		}
		$id = intval( $_POST['campaign_id'] ?? 0 );
		if ( $id ) {
			( new ECWP_Campaigns() )->delete( $id );
		}
		wp_redirect( admin_url( 'admin.php?page=ecwp-campaigns&deleted=1' ) );
		exit;
	}

	public function trigger_campaign() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_trigger_campaign' ) ) {
			wp_die( 'Unauthorized' );
		}
		$id = intval( $_POST['campaign_id'] ?? 0 );
		if ( $id ) {
			( new ECWP_Scheduler() )->manual_trigger( $id );
		}
		wp_redirect( admin_url( 'admin.php?page=ecwp-campaigns&triggered=1' ) );
		exit;
	}

	public function pause_campaign() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_pause_campaign' ) ) {
			wp_die( 'Unauthorized' );
		}
		$id = intval( $_POST['campaign_id'] ?? 0 );
		if ( $id ) {
			( new ECWP_Campaigns() )->update( $id, [ 'status' => 'paused' ] );
		}
		wp_redirect( admin_url( 'admin.php?page=ecwp-campaigns&paused=1' ) );
		exit;
	}

	public function send_test_email() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_send_test' ) ) {
			wp_die( 'Unauthorized' );
		}
		$to = sanitize_email( $_POST['test_email'] ?? '' );
		if ( ! is_email( $to ) ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-settings&test_error=invalid_email' ) );
			exit;
		}
		$result = ( new ECWP_Mailgun() )->send_test_email( $to );
		if ( is_wp_error( $result ) ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-settings&test_error=' . urlencode( $result->get_error_message() ) ) );
		} else {
			wp_redirect( admin_url( 'admin.php?page=ecwp-settings&test_sent=1' ) );
		}
		exit;
	}

	public function test_mailgun_connection() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_test_mailgun' ) ) {
			wp_die( 'Unauthorized' );
		}
		$result = ( new ECWP_Mailgun() )->test_connection();
		if ( is_wp_error( $result ) ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-settings&conn_error=' . urlencode( $result->get_error_message() ) ) );
		} else {
			wp_redirect( admin_url( 'admin.php?page=ecwp-settings&conn_ok=1' ) );
		}
		exit;
	}
}
