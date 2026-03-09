<?php
/**
 * Plugin Name: Email Campaign WP
 * Plugin URI:  https://ideaboss.io
 * Description: A powerful email campaign manager with Mailgun integration, subscriber management, batch scheduling, and full analytics.
 * Version:     1.0.0
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
define( 'ECWP_VERSION',        '1.0.0' );
define( 'ECWP_PLUGIN_DIR',     plugin_dir_path( __FILE__ ) );
define( 'ECWP_PLUGIN_URL',     plugin_dir_url( __FILE__ ) );
define( 'ECWP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// ── Core includes ──────────────────────────────────────────────────────────
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-activator.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-deactivator.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-mailgun.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-subscribers.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-campaigns.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-scheduler.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-webhooks.php';
require_once ECWP_PLUGIN_DIR . 'includes/class-ecwp-unsubscribe.php';

if ( is_admin() ) {
	require_once ECWP_PLUGIN_DIR . 'admin/class-ecwp-admin.php';
}

// ── Activation / Deactivation ──────────────────────────────────────────────
register_activation_hook(   __FILE__, array( 'ECWP_Activator',   'activate' ) );
register_deactivation_hook( __FILE__, array( 'ECWP_Deactivator', 'deactivate' ) );

// ── Boot ───────────────────────────────────────────────────────────────────
function ecwp_init() {
	if ( is_admin() ) {
		( new ECWP_Admin() )->init();
	}
	( new ECWP_Scheduler() )->init();
	( new ECWP_Webhooks() )->init();
	( new ECWP_Unsubscribe() )->init();
}
add_action( 'plugins_loaded', 'ecwp_init' );
