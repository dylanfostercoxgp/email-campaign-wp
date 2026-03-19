<?php
/**
 * Admin menu, page routing, and form handlers.
 *
 * @package EmailCampaignWP
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Admin {

	public function init() {
		add_action( 'admin_menu',            [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// ── Form POST handlers ─────────────────────────────────────────
		add_action( 'admin_post_ecwp_save_settings',       [ $this, 'save_settings' ] );
		add_action( 'admin_post_ecwp_import_subscribers',  [ $this, 'import_subscribers' ] );
		add_action( 'admin_post_ecwp_add_subscriber',      [ $this, 'add_subscriber' ] );
		add_action( 'admin_post_ecwp_edit_subscriber',     [ $this, 'edit_subscriber' ] );
		add_action( 'admin_post_ecwp_delete_subscriber',   [ $this, 'delete_subscriber' ] );
		add_action( 'admin_post_ecwp_bulk_tag',            [ $this, 'bulk_tag_subscribers' ] );
		add_action( 'admin_post_ecwp_create_campaign',     [ $this, 'create_campaign' ] );
		add_action( 'admin_post_ecwp_update_campaign',     [ $this, 'update_campaign' ] );
		add_action( 'admin_post_ecwp_delete_campaign',     [ $this, 'delete_campaign' ] );
		add_action( 'admin_post_ecwp_trigger_campaign',    [ $this, 'trigger_campaign' ] );
		add_action( 'admin_post_ecwp_pause_campaign',      [ $this, 'pause_campaign' ] );
		add_action( 'admin_post_ecwp_schedule_campaign',   [ $this, 'schedule_campaign' ] );
		add_action( 'admin_post_ecwp_unschedule_campaign', [ $this, 'unschedule_campaign' ] );
		add_action( 'admin_post_ecwp_send_test',           [ $this, 'send_test_email' ] );
		add_action( 'admin_post_ecwp_test_mailgun',        [ $this, 'test_mailgun_connection' ] );
		add_action( 'admin_post_ecwp_create_tag',                    [ $this, 'create_tag' ] );
		add_action( 'admin_post_ecwp_edit_tag',                      [ $this, 'edit_tag' ] );
		add_action( 'admin_post_ecwp_delete_tag',                    [ $this, 'delete_tag' ] );
		add_action( 'admin_post_ecwp_remove_subscriber_from_tag',    [ $this, 'remove_subscriber_from_tag' ] );
		add_action( 'admin_post_ecwp_save_template',       [ $this, 'save_template' ] );
		add_action( 'admin_post_ecwp_delete_template',     [ $this, 'delete_template' ] );

		// ── AJAX handlers ─────────────────────────────────────────────
		add_action( 'wp_ajax_ecwp_autosave_html',          [ $this, 'ajax_autosave_html' ] );
	}

	/* ------------------------------------------------------------------ */
	/*  Menu                                                                */
	/* ------------------------------------------------------------------ */

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
		add_submenu_page( 'ecwp-dashboard', 'Tags',         'Tags',         'manage_options', 'ecwp-tags',        [ $this, 'page_tags' ] );
		add_submenu_page( 'ecwp-dashboard', 'Templates',    'Templates',    'manage_options', 'ecwp-templates',   [ $this, 'page_templates' ] );
		add_submenu_page( 'ecwp-dashboard', 'Analytics',    'Analytics',    'manage_options', 'ecwp-analytics',   [ $this, 'page_analytics' ] );
		add_submenu_page( 'ecwp-dashboard', 'Settings',     'Settings',     'manage_options', 'ecwp-settings',    [ $this, 'page_settings' ] );
		// Hidden page for HTML editor (linked from campaign edit)
		add_submenu_page( null, 'HTML Editor', 'HTML Editor', 'manage_options', 'ecwp-html-editor', [ $this, 'page_html_editor' ] );
	}

	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'ecwp' ) === false ) {
			return;
		}
		wp_enqueue_style(  'ecwp-admin', ECWP_PLUGIN_URL . 'admin/css/ecwp-admin.css', [], ECWP_VERSION );
		wp_enqueue_script( 'ecwp-admin', ECWP_PLUGIN_URL . 'admin/js/ecwp-admin.js',  [ 'jquery' ], ECWP_VERSION, true );
		wp_localize_script( 'ecwp-admin', 'ecwpData', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'ecwp_nonce' ),
		] );

		// Enqueue the HTML editor script only on the editor page.
		if ( strpos( $hook, 'ecwp-html-editor' ) !== false || ( isset( $_GET['page'] ) && $_GET['page'] === 'ecwp-html-editor' ) ) {
			wp_enqueue_code_editor( [ 'type' => 'text/html' ] );
			wp_enqueue_script( 'ecwp-editor', ECWP_PLUGIN_URL . 'admin/js/ecwp-editor.js', [ 'jquery', 'code-editor' ], ECWP_VERSION, true );
			wp_localize_script( 'ecwp-editor', 'ecwpEditorData', [
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'ecwp_autosave_html' ),
				'campaign_id' => intval( $_GET['campaign_id'] ?? 0 ),
			] );
		}
	}

	/* ------------------------------------------------------------------ */
	/*  Pages                                                               */
	/* ------------------------------------------------------------------ */

	public function page_dashboard() {
		global $wpdb;
		$subscribers = new ECWP_Subscribers();
		$campaigns   = new ECWP_Campaigns();

		$stats = [
			'active_subs'      => $subscribers->count( 'active' ),
			'unsub_count'      => $subscribers->count( 'unsubscribed' ),
			'total_campaigns'  => count( $campaigns->get_all() ),
			'active_campaigns' => count( $campaigns->get_all( 'sending' ) ),
		];

		$log  = $wpdb->prefix . 'ecwp_send_log';
		$anal = $wpdb->prefix . 'ecwp_analytics';

		$stats['total_sent']      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status NOT IN ('pending','failed')" );
		$stats['total_delivered'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status = 'delivered'" );
		$stats['total_opens']     = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT message_id) FROM {$anal} WHERE event_type = 'opened'" );
		$stats['total_clicks']    = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT message_id) FROM {$anal} WHERE event_type = 'clicked'" );
		$stats['total_bounces']   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status = 'bounced'" );

		$recent_campaigns = array_slice( $campaigns->get_all(), 0, 5 );

		include ECWP_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	public function page_campaigns() {
		$campaigns   = new ECWP_Campaigns();
		$subscribers = new ECWP_Subscribers();
		$tags        = new ECWP_Tags();
		$templates   = new ECWP_Templates();
		$action      = sanitize_text_field( $_GET['action'] ?? 'list' );
		$campaign_id = intval( $_GET['campaign_id'] ?? 0 );

		if ( $action === 'edit' && $campaign_id ) {
			$campaign             = $campaigns->get_by_id( $campaign_id );
			$campaign_subscribers = $campaigns->get_subscribers( $campaign_id );
			$all_subscribers      = $subscribers->get_all( 'active' );
			$all_tags             = $tags->get_all();
			$selected_tag_ids     = $campaigns->get_target_tag_ids( $campaign );
			$all_templates        = array_merge( $templates->get_system_templates(), $templates->get_all() );
			include ECWP_PLUGIN_DIR . 'admin/views/campaign-edit.php';
		} elseif ( $action === 'new' ) {
			$all_subscribers = $subscribers->get_all( 'active' );
			$all_tags        = $tags->get_all();
			$all_templates   = array_merge( $templates->get_system_templates(), $templates->get_all() );
			include ECWP_PLUGIN_DIR . 'admin/views/campaign-new.php';
		} else {
			global $wpdb;
			$all_campaigns = $campaigns->get_all();
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
		$tags            = new ECWP_Tags();
		$action          = sanitize_text_field( $_GET['action'] ?? 'list' );
		$subscriber_id   = intval( $_GET['subscriber_id'] ?? 0 );

		if ( $action === 'edit' && $subscriber_id ) {
			$subscriber      = $subscribers->get_by_id( $subscriber_id );
			$all_tags        = $tags->get_all();
			$subscriber_tags = $tags->get_subscriber_tags( $subscriber_id );
			$subscriber_tag_ids = array_column( $subscriber_tags, 'id' );
			include ECWP_PLUGIN_DIR . 'admin/views/subscriber-edit.php';
		} else {
			$per_page        = 100;
			$paged           = max( 1, intval( $_GET['paged'] ?? 1 ) );
			$total_count     = $subscribers->count();
			$total_pages     = (int) ceil( $total_count / $per_page );
			$offset          = ( $paged - 1 ) * $per_page;
			$all_subscribers = $subscribers->get_all( 'all', $per_page, $offset );
			$active_count    = $subscribers->count( 'active' );
			$unsub_count     = $subscribers->count( 'unsubscribed' );
			$all_tags        = $tags->get_all();
			include ECWP_PLUGIN_DIR . 'admin/views/subscribers.php';
		}
	}

	public function page_tags() {
		$tags      = new ECWP_Tags();
		$action    = sanitize_text_field( $_GET['action'] ?? 'list' );
		$tag_id    = intval( $_GET['tag_id'] ?? 0 );

		if ( $action === 'edit' && $tag_id ) {
			$tag = $tags->get_by_id( $tag_id );
			if ( ! $tag ) { wp_redirect( admin_url( 'admin.php?page=ecwp-tags' ) ); exit; }
			include ECWP_PLUGIN_DIR . 'admin/views/tag-edit.php';
		} elseif ( $action === 'view' && $tag_id ) {
			$tag             = $tags->get_by_id( $tag_id );
			if ( ! $tag ) { wp_redirect( admin_url( 'admin.php?page=ecwp-tags' ) ); exit; }
			$tag_subscribers = $tags->get_tag_subscribers( $tag_id );
			$all_tags        = $tags->get_all();
			include ECWP_PLUGIN_DIR . 'admin/views/tag-view.php';
		} else {
			$all_tags = $tags->get_all(); // includes subscriber_count
			include ECWP_PLUGIN_DIR . 'admin/views/tags.php';
		}
	}

	public function page_templates() {
		$templates        = new ECWP_Templates();
		$system_templates = $templates->get_system_templates();
		$user_templates   = $templates->get_all();
		include ECWP_PLUGIN_DIR . 'admin/views/templates.php';
	}

	public function page_html_editor() {
		$campaign_id = intval( $_GET['campaign_id'] ?? 0 );
		$campaigns   = new ECWP_Campaigns();
		$campaign    = $campaign_id ? $campaigns->get_by_id( $campaign_id ) : null;
		include ECWP_PLUGIN_DIR . 'admin/views/html-editor.php';
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
			'sent'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log}{$where_log}" ),
			'delivered'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status='delivered'{$where_log2}" ),
			'opened'       => 0,
			'clicked'      => 0,
			'bounced'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status='bounced'{$where_log2}" ),
			'failed'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status='failed'{$where_log2}" ),
			'complained'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status='complained'{$where_log2}" ),
			'unsubscribed' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log} WHERE status='unsubscribed'{$where_log2}" ),
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

		$recent_events  = $wpdb->get_results(
			"SELECT a.* FROM {$anal} a ORDER BY a.created_at DESC LIMIT 100"
		);
		$campaign_stats = $wpdb->get_results(
			"SELECT c.id, c.name, c.status,
			        COUNT(sl.id)                  AS total_sent,
			        SUM(sl.status='delivered')    AS delivered,
			        SUM(sl.status='bounced')      AS bounced,
			        SUM(sl.status='opened')       AS opened,
			        SUM(sl.status='clicked')      AS clicked,
			        SUM(sl.status='unsubscribed') AS unsubscribed
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

	/* ------------------------------------------------------------------ */
	/*  Settings                                                            */
	/* ------------------------------------------------------------------ */

	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_save_settings' ) ) {
			wp_die( 'Unauthorized' );
		}
		foreach ( [
			'ecwp_mailgun_api_key', 'ecwp_mailgun_domain', 'ecwp_mailgun_region',
			'ecwp_from_name', 'ecwp_from_email', 'ecwp_send_time',
			'ecwp_batch_size', 'ecwp_batch_interval',
		] as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_option( $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}
		update_option( 'ecwp_schedule_enabled', isset( $_POST['ecwp_schedule_enabled'] ) ? '1' : '0' );
		update_option( 'ecwp_click_tracking',   isset( $_POST['ecwp_click_tracking'] )   ? '1' : '0' );
		( new ECWP_Scheduler() )->reschedule_daily_trigger();
		wp_redirect( admin_url( 'admin.php?page=ecwp-settings&saved=1' ) );
		exit;
	}

	/* ------------------------------------------------------------------ */
	/*  Subscribers                                                         */
	/* ------------------------------------------------------------------ */

	public function import_subscribers() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_import_subscribers' ) ) {
			wp_die( 'Unauthorized' );
		}
		if ( empty( $_FILES['subscriber_csv'] ) || $_FILES['subscriber_csv']['error'] !== UPLOAD_ERR_OK ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-subscribers&import_error=upload_failed' ) );
			exit;
		}
		$file = $_FILES['subscriber_csv'];
		if ( strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) !== 'csv' ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-subscribers&import_error=not_csv' ) );
			exit;
		}
		$result = ( new ECWP_Subscribers() )->import_csv( $file['tmp_name'] );
		if ( is_wp_error( $result ) ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-subscribers&import_error=' . urlencode( $result->get_error_message() ) ) );
			exit;
		}
		// After import, tag only the newly imported subscribers (not existing ones).
		if ( ! empty( $_POST['import_tag_id'] ) && ! empty( $result['new_ids'] ) ) {
			$tag_id = intval( $_POST['import_tag_id'] );
			( new ECWP_Tags() )->bulk_assign( $tag_id, $result['new_ids'] );
		}
		wp_redirect( admin_url( "admin.php?page=ecwp-subscribers&imported={$result['imported']}&skipped={$result['skipped']}" ) );
		exit;
	}

	public function add_subscriber() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_add_subscriber' ) ) {
			wp_die( 'Unauthorized' );
		}
		$email = sanitize_email( $_POST['email'] ?? '' );
		$fn    = sanitize_text_field( $_POST['first_name'] ?? '' );
		$ln    = sanitize_text_field( $_POST['last_name'] ?? '' );
		$extra = [
			'phone'   => $_POST['phone']   ?? '',
			'address' => $_POST['address'] ?? '',
			'website' => $_POST['website'] ?? '',
			'notes'   => $_POST['notes']   ?? '',
		];

		$result = ( new ECWP_Subscribers() )->add( $email, $fn, $ln, $extra );
		if ( is_wp_error( $result ) ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-subscribers&add_error=' . urlencode( $result->get_error_message() ) ) );
		} else {
			// Assign tags if provided.
			if ( ! empty( $_POST['tag_ids'] ) ) {
				( new ECWP_Tags() )->set_subscriber_tags( $result, array_map( 'intval', (array) $_POST['tag_ids'] ) );
			}
			wp_redirect( admin_url( 'admin.php?page=ecwp-subscribers&add_success=1' ) );
		}
		exit;
	}

	public function edit_subscriber() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_edit_subscriber' ) ) {
			wp_die( 'Unauthorized' );
		}
		$id = intval( $_POST['subscriber_id'] ?? 0 );
		if ( ! $id ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-subscribers' ) );
			exit;
		}
		$subs   = new ECWP_Subscribers();
		$result = $subs->update( $id, [
			'email'      => $_POST['email']      ?? '',
			'first_name' => $_POST['first_name'] ?? '',
			'last_name'  => $_POST['last_name']  ?? '',
			'status'     => $_POST['status']     ?? 'active',
			'phone'      => $_POST['phone']      ?? '',
			'address'    => $_POST['address']    ?? '',
			'website'    => $_POST['website']    ?? '',
			'notes'      => $_POST['notes']      ?? '',
		] );
		if ( is_wp_error( $result ) ) {
			wp_redirect( admin_url( "admin.php?page=ecwp-subscribers&action=edit&subscriber_id={$id}&edit_error=" . urlencode( $result->get_error_message() ) ) );
			exit;
		}
		// Update tags — only when the tags panel was actually in the form.
		// 'tags_submitted' is a hidden field present in subscriber-edit.php;
		// its absence means the form came from elsewhere and we should not wipe tags.
		if ( isset( $_POST['tags_submitted'] ) ) {
			$tag_ids = ! empty( $_POST['tag_ids'] ) ? array_map( 'intval', (array) $_POST['tag_ids'] ) : [];
			( new ECWP_Tags() )->set_subscriber_tags( $id, $tag_ids );
		}

		wp_redirect( admin_url( "admin.php?page=ecwp-subscribers&action=edit&subscriber_id={$id}&updated=1" ) );
		exit;
	}

	public function delete_subscriber() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_delete_subscriber' ) ) {
			wp_die( 'Unauthorized' );
		}
		$id = intval( $_POST['subscriber_id'] ?? 0 );
		if ( $id ) { ( new ECWP_Subscribers() )->delete( $id ); }
		// Support custom redirect (e.g. back to a tag view), verified against admin URL.
		$redirect = isset( $_POST['ecwp_redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['ecwp_redirect_to'] ) ) : '';
		if ( $redirect && strpos( $redirect, admin_url() ) === 0 ) {
			wp_redirect( $redirect );
		} else {
			wp_redirect( admin_url( 'admin.php?page=ecwp-subscribers&deleted=1' ) );
		}
		exit;
	}

	public function bulk_tag_subscribers() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_bulk_tag' ) ) {
			wp_die( 'Unauthorized' );
		}
		$action = sanitize_text_field( $_POST['bulk_action'] ?? 'tag' );
		$tag_id = intval( $_POST['bulk_tag_id'] ?? 0 );
		$ids    = ! empty( $_POST['subscriber_ids'] ) ? array_map( 'intval', (array) $_POST['subscriber_ids'] ) : [];

		if ( ! $tag_id || empty( $ids ) ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-subscribers&bulk_error=1' ) );
			exit;
		}

		if ( $action === 'untag' ) {
			( new ECWP_Tags() )->bulk_remove( $tag_id, $ids );
			wp_redirect( admin_url( 'admin.php?page=ecwp-subscribers&bulk_untagged=' . count( $ids ) ) );
		} else {
			( new ECWP_Tags() )->bulk_assign( $tag_id, $ids );
			wp_redirect( admin_url( 'admin.php?page=ecwp-subscribers&bulk_tagged=' . count( $ids ) ) );
		}
		exit;
	}

	/* ------------------------------------------------------------------ */
	/*  Tags                                                                */
	/* ------------------------------------------------------------------ */

	public function create_tag() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_create_tag' ) ) {
			wp_die( 'Unauthorized' );
		}
		$name  = sanitize_text_field( $_POST['name']  ?? '' );
		$color = sanitize_hex_color( $_POST['color'] ?? '#3b82f6' ) ?: '#3b82f6';
		if ( $name ) {
			( new ECWP_Tags() )->create( $name, $color );
		}
		wp_redirect( admin_url( 'admin.php?page=ecwp-tags&created=1' ) );
		exit;
	}

	public function delete_tag() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_delete_tag' ) ) {
			wp_die( 'Unauthorized' );
		}
		$id = intval( $_POST['tag_id'] ?? 0 );
		if ( $id ) { ( new ECWP_Tags() )->delete( $id ); }
		wp_redirect( admin_url( 'admin.php?page=ecwp-tags&deleted=1' ) );
		exit;
	}

	public function edit_tag() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_edit_tag' ) ) {
			wp_die( 'Unauthorized' );
		}
		$id    = intval( $_POST['tag_id'] ?? 0 );
		$name  = sanitize_text_field( $_POST['name']  ?? '' );
		$color = sanitize_hex_color( $_POST['color'] ?? '#3b82f6' ) ?: '#3b82f6';
		if ( $id && $name ) {
			( new ECWP_Tags() )->update( $id, $name, $color );
		}
		wp_redirect( admin_url( "admin.php?page=ecwp-tags&action=edit&tag_id={$id}&updated=1" ) );
		exit;
	}

	public function remove_subscriber_from_tag() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_remove_subscriber_from_tag' ) ) {
			wp_die( 'Unauthorized' );
		}
		$tag_id = intval( $_POST['tag_id']        ?? 0 );
		$sub_id = intval( $_POST['subscriber_id'] ?? 0 );
		if ( $tag_id && $sub_id ) {
			( new ECWP_Tags() )->remove_subscriber_from_tag( $sub_id, $tag_id );
		}
		wp_redirect( admin_url( "admin.php?page=ecwp-tags&action=view&tag_id={$tag_id}&removed=1" ) );
		exit;
	}

	/* ------------------------------------------------------------------ */
	/*  Templates                                                           */
	/* ------------------------------------------------------------------ */

	public function save_template() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_save_template' ) ) {
			wp_die( 'Unauthorized' );
		}
		$name    = sanitize_text_field( $_POST['name']    ?? '' );
		$subject = sanitize_text_field( $_POST['subject'] ?? '' );

		// HTML source priority: uploaded file > textarea.
		$html = '';
		if ( ! empty( $_FILES['html_file'] ) && $_FILES['html_file']['error'] === UPLOAD_ERR_OK ) {
			$ext = strtolower( pathinfo( $_FILES['html_file']['name'], PATHINFO_EXTENSION ) );
			if ( in_array( $ext, [ 'html', 'htm' ], true ) ) {
				$html = file_get_contents( $_FILES['html_file']['tmp_name'] );
			}
		}
		if ( empty( $html ) && ! empty( $_POST['html_content'] ) ) {
			$html = wp_unslash( $_POST['html_content'] );
		}

		if ( $name && $html ) {
			( new ECWP_Templates() )->save( $name, $subject, $html );
		}
		wp_redirect( admin_url( 'admin.php?page=ecwp-templates&saved=1' ) );
		exit;
	}

	public function delete_template() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_delete_template' ) ) {
			wp_die( 'Unauthorized' );
		}
		$id = intval( $_POST['template_id'] ?? 0 );
		if ( $id ) { ( new ECWP_Templates() )->delete( $id ); }
		wp_redirect( admin_url( 'admin.php?page=ecwp-templates&deleted=1' ) );
		exit;
	}

	/* ------------------------------------------------------------------ */
	/*  Campaigns                                                           */
	/* ------------------------------------------------------------------ */

	public function create_campaign() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_create_campaign' ) ) {
			wp_die( 'Unauthorized' );
		}

		// Determine HTML source: upload > editor > template.
		$html        = '';
		$target_type = sanitize_text_field( $_POST['target_type'] ?? 'all' );
		$target_tags = '';

		if ( ! empty( $_FILES['html_file'] ) && $_FILES['html_file']['error'] === UPLOAD_ERR_OK ) {
			$ext = strtolower( pathinfo( $_FILES['html_file']['name'], PATHINFO_EXTENSION ) );
			if ( in_array( $ext, [ 'html', 'htm' ], true ) ) {
				$html = file_get_contents( $_FILES['html_file']['tmp_name'] );
			}
		} elseif ( ! empty( $_POST['html_content'] ) ) {
			$html = wp_kses_post( $_POST['html_content'] );
		} elseif ( ! empty( $_POST['template_id'] ) ) {
			$tpl_id = sanitize_text_field( $_POST['template_id'] );
			// Check system templates first (string IDs like 'sys_newsletter').
			$sys = ECWP_Templates::get_system_template_by_id( $tpl_id );
			if ( $sys ) {
				$html = $sys['html'];
			} else {
				// User-saved template (numeric ID).
				$tmpl = ( new ECWP_Templates() )->get_by_id( intval( $tpl_id ) );
				if ( $tmpl ) {
					$html = $tmpl->html;
				}
			}
		}

		if ( $target_type === 'tags' && ! empty( $_POST['target_tag_ids'] ) ) {
			$target_tags = implode( ',', array_map( 'intval', (array) $_POST['target_tag_ids'] ) );
		}

		$campaigns   = new ECWP_Campaigns();
		$campaign_id = $campaigns->create( [
			'name'             => $_POST['name']         ?? '',
			'subject'          => $_POST['subject']      ?? '',
			'preview_text'     => $_POST['preview_text'] ?? '',
			'html_content'     => $html,
			'target_type'      => $target_type,
			'target_tags'      => $target_tags,
			'send_time'        => $_POST['send_time'] ?? '10:00',
			'schedule_enabled' => isset( $_POST['schedule_enabled'] ) ? 1 : 0,
			'batch_size'       => intval( $_POST['batch_size']     ?? 10 ),
			'batch_interval'   => intval( $_POST['batch_interval'] ?? 30 ),
		] );

		if ( is_wp_error( $campaign_id ) ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-campaigns&action=new&error=' . urlencode( $campaign_id->get_error_message() ) ) );
			exit;
		}

		// For 'selected' targeting, store the subscriber list.
		if ( $target_type === 'selected' ) {
			if ( isset( $_POST['assign_all'] ) ) {
				$campaigns->assign_all_subscribers( $campaign_id );
			} elseif ( ! empty( $_POST['subscriber_ids'] ) ) {
				$campaigns->assign_subscribers( $campaign_id, array_map( 'intval', $_POST['subscriber_ids'] ) );
			}
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

		$target_type = sanitize_text_field( $_POST['target_type'] ?? 'all' );
		$target_tags = '';
		if ( $target_type === 'tags' && ! empty( $_POST['target_tag_ids'] ) ) {
			$target_tags = implode( ',', array_map( 'intval', (array) $_POST['target_tag_ids'] ) );
		}

		$data = [
			'name'             => $_POST['name']         ?? '',
			'subject'          => $_POST['subject']      ?? '',
			'preview_text'     => $_POST['preview_text'] ?? '',
			'target_type'      => $target_type,
			'target_tags'      => $target_tags,
			'send_time'        => $_POST['send_time'] ?? '10:00',
			'schedule_enabled' => isset( $_POST['schedule_enabled'] ) ? 1 : 0,
			'batch_size'       => intval( $_POST['batch_size']     ?? 10 ),
			'batch_interval'   => intval( $_POST['batch_interval'] ?? 30 ),
		];

		// Update HTML if provided.
		if ( ! empty( $_FILES['html_file'] ) && $_FILES['html_file']['error'] === UPLOAD_ERR_OK ) {
			$ext = strtolower( pathinfo( $_FILES['html_file']['name'], PATHINFO_EXTENSION ) );
			if ( in_array( $ext, [ 'html', 'htm' ], true ) ) {
				$data['html_content'] = file_get_contents( $_FILES['html_file']['tmp_name'] );
			}
		} elseif ( ! empty( $_POST['template_id'] ) ) {
			// Apply a selected template to this campaign.
			$tpl_id = sanitize_text_field( $_POST['template_id'] );
			$sys    = ECWP_Templates::get_system_template_by_id( $tpl_id );
			if ( $sys ) {
				$data['html_content'] = $sys['html'];
			} else {
				$tmpl = ( new ECWP_Templates() )->get_by_id( intval( $tpl_id ) );
				if ( $tmpl ) {
					$data['html_content'] = $tmpl->html;
				}
			}
		} elseif ( isset( $_POST['html_content'] ) && $_POST['html_content'] !== '' ) {
			$data['html_content'] = wp_unslash( $_POST['html_content'] );
		}

		$campaigns = new ECWP_Campaigns();
		$campaigns->update( $campaign_id, $data );

		if ( $target_type === 'selected' ) {
			if ( isset( $_POST['assign_all'] ) ) {
				$campaigns->assign_all_subscribers( $campaign_id );
			} elseif ( ! empty( $_POST['subscriber_ids'] ) ) {
				$campaigns->assign_subscribers( $campaign_id, array_map( 'intval', $_POST['subscriber_ids'] ) );
			}
		}

		// Send immediately if requested (schedule disabled + user clicked Send Now).
		if ( ! empty( $_POST['send_immediately'] ) ) {
			( new ECWP_Scheduler() )->manual_trigger( $campaign_id );
			wp_redirect( admin_url( "admin.php?page=ecwp-campaigns&action=edit&campaign_id={$campaign_id}&updated=1&sent=1" ) );
			exit;
		}

		wp_redirect( admin_url( "admin.php?page=ecwp-campaigns&action=edit&campaign_id={$campaign_id}&updated=1" ) );
		exit;
	}

	public function delete_campaign() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_delete_campaign' ) ) {
			wp_die( 'Unauthorized' );
		}
		$id = intval( $_POST['campaign_id'] ?? 0 );
		if ( $id ) { ( new ECWP_Campaigns() )->delete( $id ); }
		wp_redirect( admin_url( 'admin.php?page=ecwp-campaigns&deleted=1' ) );
		exit;
	}

	public function trigger_campaign() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_trigger_campaign' ) ) {
			wp_die( 'Unauthorized' );
		}
		$id = intval( $_POST['campaign_id'] ?? 0 );
		if ( $id ) { ( new ECWP_Scheduler() )->manual_trigger( $id ); }
		wp_redirect( admin_url( 'admin.php?page=ecwp-campaigns&triggered=1' ) );
		exit;
	}

	public function pause_campaign() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_pause_campaign' ) ) {
			wp_die( 'Unauthorized' );
		}
		$id = intval( $_POST['campaign_id'] ?? 0 );
		if ( $id ) { ( new ECWP_Campaigns() )->update( $id, [ 'status' => 'paused' ] ); }
		wp_redirect( admin_url( 'admin.php?page=ecwp-campaigns&paused=1' ) );
		exit;
	}

	public function schedule_campaign() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_schedule_campaign' ) ) {
			wp_die( 'Unauthorized' );
		}
		$id = intval( $_POST['campaign_id'] ?? 0 );
		if ( ! $id ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-campaigns' ) );
			exit;
		}

		// Build the full "Y-m-d H:i" datetime from the form inputs.
		$send_date = sanitize_text_field( $_POST['send_date'] ?? date( 'Y-m-d' ) );
		$send_time = sanitize_text_field( $_POST['send_time'] ?? '10:00' );
		$datetime  = $send_date . ' ' . $send_time;

		// Set status to scheduled.
		( new ECWP_Campaigns() )->update( $id, [
			'status'           => 'scheduled',
			'schedule_enabled' => 1,
			'send_time'        => $send_time,
		] );

		// Register the per-campaign cron event at the exact date+time.
		$ok = ( new ECWP_Scheduler() )->schedule_campaign( $id, $datetime );

		if ( ! $ok ) {
			// Past datetime — revert to draft and show error.
			( new ECWP_Campaigns() )->update( $id, [ 'status' => 'draft' ] );
			wp_redirect( admin_url( "admin.php?page=ecwp-campaigns&action=edit&campaign_id={$id}&schedule_error=past" ) );
			exit;
		}

		wp_redirect( admin_url( "admin.php?page=ecwp-campaigns&action=edit&campaign_id={$id}&scheduled=1" ) );
		exit;
	}

	public function unschedule_campaign() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ecwp_unschedule_campaign' ) ) {
			wp_die( 'Unauthorized' );
		}
		$id = intval( $_POST['campaign_id'] ?? 0 );
		if ( $id ) {
			( new ECWP_Campaigns() )->update( $id, [ 'status' => 'draft' ] );
			( new ECWP_Scheduler() )->unschedule_campaign( $id );
		}
		wp_redirect( admin_url( "admin.php?page=ecwp-campaigns&action=edit&campaign_id={$id}&unscheduled=1" ) );
		exit;
	}

	/* ------------------------------------------------------------------ */
	/*  Mailgun tests                                                       */
	/* ------------------------------------------------------------------ */

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
		// Save the credentials passed with the test request so ECWP_Mailgun()
		// can read them from options — even if the user hasn't hit Save Settings yet.
		if ( isset( $_POST['ecwp_mailgun_api_key'] ) ) {
			update_option( 'ecwp_mailgun_api_key', sanitize_text_field( $_POST['ecwp_mailgun_api_key'] ) );
		}
		if ( isset( $_POST['ecwp_mailgun_domain'] ) ) {
			update_option( 'ecwp_mailgun_domain', sanitize_text_field( $_POST['ecwp_mailgun_domain'] ) );
		}
		if ( isset( $_POST['ecwp_mailgun_region'] ) ) {
			update_option( 'ecwp_mailgun_region', sanitize_text_field( $_POST['ecwp_mailgun_region'] ) );
		}
		$result = ( new ECWP_Mailgun() )->test_connection();
		if ( is_wp_error( $result ) ) {
			wp_redirect( admin_url( 'admin.php?page=ecwp-settings&conn_error=' . urlencode( $result->get_error_message() ) ) );
		} else {
			wp_redirect( admin_url( 'admin.php?page=ecwp-settings&conn_ok=1' ) );
		}
		exit;
	}

	/* ------------------------------------------------------------------ */
	/*  AJAX: Auto-save HTML editor                                         */
	/* ------------------------------------------------------------------ */

	public function ajax_autosave_html() {
		check_ajax_referer( 'ecwp_autosave_html', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}
		$campaign_id = intval( $_POST['campaign_id'] ?? 0 );
		$html        = wp_kses_post( wp_unslash( $_POST['html'] ?? '' ) );
		if ( $campaign_id ) {
			( new ECWP_Campaigns() )->update( $campaign_id, [ 'html_content' => $html ] );
			wp_send_json_success( [ 'message' => 'Saved', 'time' => current_time( 'H:i:s' ) ] );
		}
		wp_send_json_error( 'Missing campaign_id' );
	}
}
