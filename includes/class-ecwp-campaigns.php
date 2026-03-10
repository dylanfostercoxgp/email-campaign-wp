<?php
/**
 * Campaign CRUD, subscriber assignment, tag-targeting, and send-log queries.
 *
 * @package EmailCampaignWP
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Campaigns {

	private $table;
	private $junction;

	public function __construct() {
		global $wpdb;
		$this->table    = $wpdb->prefix . 'ecwp_campaigns';
		$this->junction = $wpdb->prefix . 'ecwp_campaign_subscribers';
	}

	/* ------------------------------------------------------------------ */
	/*  Read                                                                */
	/* ------------------------------------------------------------------ */

	public function get_all( $status = 'all' ) {
		global $wpdb;
		$where = ( $status !== 'all' ) ? $wpdb->prepare( ' WHERE status = %s', $status ) : '';
		return $wpdb->get_results( "SELECT * FROM {$this->table}{$where} ORDER BY created_at DESC" );
	}

	public function get_by_id( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ) );
	}

	/* ------------------------------------------------------------------ */
	/*  Create                                                              */
	/* ------------------------------------------------------------------ */

	public function create( array $data ) {
		global $wpdb;

		$result = $wpdb->insert( $this->table, [
			'name'             => sanitize_text_field( $data['name'] ?? '' ),
			'subject'          => sanitize_text_field( $data['subject'] ?? '' ),
			'html_content'     => wp_kses_post( $data['html_content'] ?? '' ),
			'status'           => 'draft',
			'target_type'      => in_array( $data['target_type'] ?? 'all', [ 'all', 'tags', 'selected' ], true ) ? $data['target_type'] : 'all',
			'target_tags'      => sanitize_text_field( $data['target_tags'] ?? '' ),
			'send_time'        => sanitize_text_field( $data['send_time'] ?? '10:00' ),
			'schedule_enabled' => isset( $data['schedule_enabled'] ) ? 1 : 0,
			'batch_size'       => max( 1, intval( $data['batch_size'] ?? 10 ) ),
			'batch_interval'   => max( 1, intval( $data['batch_interval'] ?? 30 ) ),
		] );

		return ( $result === false ) ? new WP_Error( 'db_error', 'Failed to create campaign.' ) : $wpdb->insert_id;
	}

	/* ------------------------------------------------------------------ */
	/*  Update                                                              */
	/* ------------------------------------------------------------------ */

	public function update( $id, array $data ) {
		global $wpdb;
		$set = [];

		$string_fields = [ 'name', 'subject', 'status', 'send_time', 'target_type', 'target_tags' ];
		$int_fields    = [ 'schedule_enabled', 'batch_size', 'batch_interval' ];

		foreach ( $string_fields as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$set[ $key ] = sanitize_text_field( $data[ $key ] );
			}
		}
		foreach ( $int_fields as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$set[ $key ] = intval( $data[ $key ] );
			}
		}
		if ( isset( $data['html_content'] ) ) {
			$set['html_content'] = wp_kses_post( $data['html_content'] );
		}

		$set['updated_at'] = current_time( 'mysql' );

		return $wpdb->update( $this->table, $set, [ 'id' => $id ] );
	}

	/* ------------------------------------------------------------------ */
	/*  Delete                                                              */
	/* ------------------------------------------------------------------ */

	public function delete( $id ) {
		global $wpdb;
		$wpdb->delete( $this->junction, [ 'campaign_id' => $id ], [ '%d' ] );
		return $wpdb->delete( $this->table, [ 'id' => $id ], [ '%d' ] );
	}

	/* ------------------------------------------------------------------ */
	/*  Subscriber Assignment                                               */
	/* ------------------------------------------------------------------ */

	/**
	 * Replace the manual subscriber list for a campaign.
	 */
	public function assign_subscribers( $campaign_id, array $subscriber_ids ) {
		global $wpdb;
		$wpdb->delete( $this->junction, [ 'campaign_id' => $campaign_id ], [ '%d' ] );
		foreach ( $subscriber_ids as $sid ) {
			$wpdb->insert( $this->junction, [
				'campaign_id'   => (int) $campaign_id,
				'subscriber_id' => (int) $sid,
			] );
		}
	}

	/**
	 * Populate the junction table with all currently-active subscribers.
	 */
	public function assign_all_subscribers( $campaign_id ) {
		global $wpdb;
		$sub_table = $wpdb->prefix . 'ecwp_subscribers';

		$wpdb->delete( $this->junction, [ 'campaign_id' => $campaign_id ], [ '%d' ] );

		$ids = $wpdb->get_col( "SELECT id FROM {$sub_table} WHERE status = 'active'" );
		foreach ( $ids as $sid ) {
			$wpdb->insert( $this->junction, [
				'campaign_id'   => (int) $campaign_id,
				'subscriber_id' => (int) $sid,
			] );
		}
		return count( $ids );
	}

	/**
	 * Populate the junction table from tag-based targeting.
	 * Resolves subscriber IDs for the given tags and stores them.
	 */
	public function assign_subscribers_by_tags( $campaign_id, array $tag_ids ) {
		global $wpdb;
		$tags = new ECWP_Tags();
		$ids  = $tags->get_subscriber_ids_by_tags( $tag_ids );

		$wpdb->delete( $this->junction, [ 'campaign_id' => $campaign_id ], [ '%d' ] );
		foreach ( $ids as $sid ) {
			$wpdb->insert( $this->junction, [
				'campaign_id'   => (int) $campaign_id,
				'subscriber_id' => (int) $sid,
			] );
		}
		return count( $ids );
	}

	/* ------------------------------------------------------------------ */
	/*  Subscriber Queries                                                  */
	/* ------------------------------------------------------------------ */

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

	/**
	 * Return the tag IDs associated with a campaign's target_tags column.
	 */
	public function get_target_tag_ids( $campaign ) {
		if ( empty( $campaign->target_tags ) ) {
			return [];
		}
		return array_filter( array_map( 'intval', explode( ',', $campaign->target_tags ) ) );
	}
}
