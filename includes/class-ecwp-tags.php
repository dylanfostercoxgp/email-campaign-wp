<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Tags {

	private $tags_table;
	private $sub_tags_table;

	public function __construct() {
		global $wpdb;
		$this->tags_table     = $wpdb->prefix . 'ecwp_tags';
		$this->sub_tags_table = $wpdb->prefix . 'ecwp_subscriber_tags';
	}

	// ── Tag CRUD ───────────────────────────────────────────────────────────

	public function get_all() {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT t.*, COUNT(st.subscriber_id) AS subscriber_count
			 FROM {$this->tags_table} t
			 LEFT JOIN {$this->sub_tags_table} st ON t.id = st.tag_id
			 GROUP BY t.id
			 ORDER BY t.name ASC"
		);
	}

	public function get_by_id( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tags_table} WHERE id = %d", $id ) );
	}

	public function get_by_name( $name ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tags_table} WHERE name = %s", $name ) );
	}

	public function create( $name, $color = '#3b82f6' ) {
		global $wpdb;
		$name = sanitize_text_field( $name );
		if ( empty( $name ) ) {
			return new WP_Error( 'empty_name', 'Tag name cannot be empty.' );
		}
		if ( $this->get_by_name( $name ) ) {
			return new WP_Error( 'duplicate', "Tag '{$name}' already exists." );
		}
		$result = $wpdb->insert( $this->tags_table, [
			'name'  => $name,
			'color' => sanitize_hex_color( $color ) ?: '#3b82f6',
		] );
		return $result ? $wpdb->insert_id : new WP_Error( 'db_error', 'Failed to create tag.' );
	}

	public function update( $id, $name, $color ) {
		global $wpdb;
		return $wpdb->update(
			$this->tags_table,
			[ 'name' => sanitize_text_field( $name ), 'color' => sanitize_hex_color( $color ) ?: '#3b82f6' ],
			[ 'id' => $id ]
		);
	}

	public function delete( $id ) {
		global $wpdb;
		$wpdb->delete( $this->sub_tags_table, [ 'tag_id' => $id ], [ '%d' ] );
		return $wpdb->delete( $this->tags_table, [ 'id' => $id ], [ '%d' ] );
	}

	// ── Subscriber ↔ Tag assignment ────────────────────────────────────────

	public function get_subscriber_tags( $subscriber_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT t.* FROM {$this->tags_table} t
			 INNER JOIN {$this->sub_tags_table} st ON t.id = st.tag_id
			 WHERE st.subscriber_id = %d ORDER BY t.name",
			$subscriber_id
		) );
	}

	public function set_subscriber_tags( $subscriber_id, array $tag_ids ) {
		global $wpdb;
		$wpdb->delete( $this->sub_tags_table, [ 'subscriber_id' => $subscriber_id ], [ '%d' ] );
		foreach ( $tag_ids as $tid ) {
			$wpdb->insert( $this->sub_tags_table, [
				'subscriber_id' => intval( $subscriber_id ),
				'tag_id'        => intval( $tid ),
			] );
		}
	}

	/** Add (or no-op if already assigned) a single tag to a subscriber. */
	public function add_tag_to_subscriber( $subscriber_id, $tag_id ) {
		global $wpdb;
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$this->sub_tags_table} WHERE subscriber_id = %d AND tag_id = %d",
			$subscriber_id, $tag_id
		) );
		if ( ! $exists ) {
			$wpdb->insert( $this->sub_tags_table, [
				'subscriber_id' => intval( $subscriber_id ),
				'tag_id'        => intval( $tag_id ),
			] );
		}
	}

	/**
	 * Bulk-assign a tag to multiple subscribers at once.
	 * @param  int   $tag_id
	 * @param  array $subscriber_ids
	 */
	public function bulk_assign( $tag_id, array $subscriber_ids ) {
		foreach ( $subscriber_ids as $sid ) {
			$this->add_tag_to_subscriber( $sid, $tag_id );
		}
	}

	/**
	 * Get all active subscriber IDs that have ANY of the given tags.
	 */
	public function get_subscriber_ids_by_tags( array $tag_ids ) {
		global $wpdb;
		if ( empty( $tag_ids ) ) return [];
		$sub_table = $wpdb->prefix . 'ecwp_subscribers';
		$ph        = implode( ',', array_fill( 0, count( $tag_ids ), '%d' ) );
		return $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT s.id FROM {$sub_table} s
			 INNER JOIN {$this->sub_tags_table} st ON s.id = st.subscriber_id
			 WHERE st.tag_id IN ({$ph}) AND s.status = 'active'",
			...$tag_ids
		) );
	}
}
