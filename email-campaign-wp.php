<?php
/**
 * Plugin Name: Email Campaign WP
 * Plugin URI:  https://ideaboss.io
 * Description: A powerful email campaign manager with Mailgun integration, subscriber management, tagging, HTML editor, batch scheduling, and full analytics.
 * Version:     1.0.6
 * Author:      ideaBoss
 * Author URI:  https://ideaboss.io
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: email-campaign-wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Constants ──────────────────────────────────────────────────────────────
define( 'ECWP_VERSION',         '1.0.6' );
define( 'ECWP_PLUGIN_DIR',      plugin_dir_path( __FILE__ ) );
define( 'ECWP_PLUGIN_URL',      plugin_dir_url( __FILE__ ) );
define( 'ECWP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// ── Core includes ──────────────────────────────────────────────────────────
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-activator.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-deactivator.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-mailgun.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-subscribers.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-tags.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-templates.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-campaigns.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-scheduler.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-webhooks.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-unsubscribe.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-signup.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-updater.php';

if ( is_admin() ) {
	require_once ECWP_PLUGIN_DIR . 'admin/class-ecwp-admin.php';
}

// ── Activation / Deactivation ──────────────────────────────────────────────
register_activation_hook(   __FILE__, [ 'ECWP_Activator',   'activate' ] );
register_deactivation_hook( __FILE__, [ 'ECWP_Deactivator', 'deactivate' ] );

// ── Auto-migration: run dbDelta when the stored DB version doesn't match ───
function ecwp_maybe_upgrade() {
	if ( get_option( 'ecwp_db_version' ) !== ECWP_VERSION ) {
		ECWP_Activator::activate();
		update_option( 'ecwp_db_version', ECWP_VERSION );
	}
}
add_action( 'plugins_loaded', 'ecwp_maybe_upgrade', 5 ); // priority 5 = before ecwp_init

// ── Boot ───────────────────────────────────────────────────────────────────
function ecwp_init() {
	if ( is_admin() ) {
		( new ECWP_Admin() )->init();
	}
	( new ECWP_Scheduler() )->init();
	( new ECWP_Webhooks() )->init();
	( new ECWP_Unsubscribe() )->init();
	( new ECWP_Signup() )->init();
	( new ECWP_Updater() )->init();
}
add_action( 'plugins_loaded', 'ecwp_init' );
