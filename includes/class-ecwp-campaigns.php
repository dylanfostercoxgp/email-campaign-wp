<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Campaigns {

	private $table;
	private $junction;

	public function __construct() {
		global $wpdb;
		$this->table    = $wpdb->prefix . 'ecwp_campaigns';
		$this->junction = $wpdb->prefix . 'ecwp_campaign_subscribers';
	}

	public function get_all( $status = 'all' ) {
		global $wpdb;
		$where = ( $status !== 'all' ) ? $wpdb->prepare( ' WHERE status = %s', $status ) : '';
		return $wpdb->get_results( "SELECT * FROM {$this->table}{$where} ORDER BY created_at DESC" );
	}

	public function get_by_id( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ) );
	}

	public function create( array $data ) {
		global $wpdb;

		$result = $wpdb->insert( $this->table, [
			'name'             => sanitize_text_field( $data['name'] ?? '' ),
			'subject'          => sanitize_text_field( $data['subject'] ?? '' ),
			'html_content'     => $data['html_content'] ?? '',
			'status'           => 'draft',
			'send_time'        => sanitize_text_field( $data['send_time'] ?? '10:00' ),
			'schedule_enabled' => isset( $data['schedule_enabled'] ) ? 1 : 0,
			'batch_size'       => max( 1, intval( $data['batch_size'] ?? 10 ) ),
			'batch_interval'   => max( 1, intval( $data['batch_interval'] ?? 30 ) ),
		] );

		return ( $result === false ) ? new WP_Error( 'db_error', 'Failed to create campaign.' ) : $wpdb->insert_id;
	}

	public function update( $id, array $data ) {
		global $wpdb;
		$set = [];

		$map = [
			'name'             => 'sanitize_text_field',
			'subject'          => 'sanitize_text_field',
			'html_content'     => null,
			'status'           => 'sanitize_text_field',
			'send_time'        => 'sanitize_text_field',
			'schedule_enabled' => 'intval',
			'batch_size'       => 'intval',
			'batch_interval'   => 'intval',
		];

		foreach ( $map as $key => $sanitizer ) {
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}
			$set[ $key ] = $sanitizer ? call_user_func( $sanitizer, $data[ $key ] ) : $data[ $key ];
		}

		$set['updated_at'] = current_time( 'mysql' );

		return $wpdb->update( $this->table, $set, [ 'id' => $id ] );
	}

	public function delete( $id ) {
		global $wpdb;
		$wpdb->delete( $this->junction, [ 'campaign_id' => $id ], [ '%d' ] );
		return $wpdb->delete( $this->table, [ 'id' => $id ], [ '%d' ] );
	}

	// ── Subscriber assignment ─────────────────────────────────────────────

	public function assign_subscribers( $campaign_id, array $subscriber_ids ) {
		global $wpdb;
		$wpdb->delete( $this->junction, [ 'campaign_id' => $campaign_id ], [ '%d' ] );
		foreach ( $subscriber_ids as $sid ) {
			$wpdb->insert( $this->junction, [
				'campaign_id'   => $campaign_id,
				'subscriber_id' => intval( $sid ),
			] );
		}
	}

	public function assign_all_subscribers( $campaign_id ) {
		global $wpdb;
		$sub_table = $wpdb->prefix . 'ecwp_subscribers';

		$wpdb->delete( $this->junction, [ 'campaign_id' => $campaign_id ], [ '%d' ] );

		$ids = $wpdb->get_col( "SELECT id FROM {$sub_table} WHERE status = 'active'" );
		foreach ( $ids as $sid ) {
			$wpdb->insert( $this->junction, [
				'campaign_id'   => $campaign_id,
				'subscriber_id' => intval( $sid ),
			] );
		}
		return count( $ids );
	}

	public function get_subscribers( $campaign_id ) {
		global $wpdb;
		$sub_table = $wpdb->prefix . 'ecwp_subscribers';
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT s.* FROM {$sub_table} s
			 INNER JOIN {$this->junction} cs ON s.id = cs.subscriber_id
			 WHERE cs.campaign_id = %d AND s.status = 'active'",
			$campaign_id
		) );
	}

	/**
	 * Return active subscribers who have NOT yet been sent this campaign.
	 */
	public function get_unsent_subscribers( $campaign_id ) {
		global $wpdb;
		$sub_table = $wpdb->prefix . 'ecwp_subscribers';
		$log_table = $wpdb->prefix . 'ecwp_send_log';

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT s.* FROM {$sub_table} s
			 INNER JOIN {$this->junction} cs ON s.id = cs.subscriber_id
			 WHERE cs.campaign_id = %d
			   AND s.status = 'active'
			   AND s.id NOT IN (
			       SELECT subscriber_id FROM {$log_table}
			       WHERE campaign_id = %d AND status != 'failed'
			   )",
			$campaign_id,
			$campaign_id
		) );
	}

	public function get_subscriber_count( $campaign_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->junction} WHERE campaign_id = %d",
			$campaign_id
		) );
	}
}
