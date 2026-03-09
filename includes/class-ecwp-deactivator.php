<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Deactivator {
	public static function deactivate() {
		wp_clear_scheduled_hook( 'ecwp_daily_trigger' );
		wp_clear_scheduled_hook( 'ecwp_send_batch' );
		flush_rewrite_rules();
	}
}
