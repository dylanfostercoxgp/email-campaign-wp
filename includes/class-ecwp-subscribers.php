<?php
/**
 * Subscriber CRUD, CSV import, unsubscribe, and tag-aware lookup.
 *
 * @package EmailCampaignWP
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Subscribers {

	private $table;
	private $tag_pivot;

	public function __construct() {
		global $wpdb;
		$this->table     = $wpdb->prefix . 'ecwp_subscribers';
		$this->tag_pivot = $wpdb->prefix . 'ecwp_subscriber_tags';
	}

	/* ------------------------------------------------------------------ */
	/*  Read                                                                */
	/* ------------------------------------------------------------------ */

	public function get_all( $status = 'all', $limit = 0, $offset = 0 ) {
		global $wpdb;
		$where        = ( $status !== 'all' ) ? $wpdb->prepare( ' WHERE status = %s', $status ) : '';
		$limit_clause = $limit ? $wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset ) : '';
		return $wpdb->get_results( "SELECT * FROM {$this->table}{$where} ORDER BY subscribed_at DESC{$limit_clause}" );
	}

	public function count( $status = 'all' ) {
		global $wpdb;
		$where = ( $status !== 'all' ) ? $wpdb->prepare( ' WHERE status = %s', $status ) : '';
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}{$where}" );
	}

	public function get_by_id( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ) );
	}

	public function get_by_email( $email ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE email = %s", $email ) );
	}

	/* ------------------------------------------------------------------ */
	/*  Create                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * Add a single subscriber. Returns insert ID or WP_Error.
	 *
	 * @param string $email
	 * @param string $first_name
	 * @param string $last_name
	 * @param array  $extra  Optional keys: phone, address, website, notes
	 */
	public function add( $email, $first_name = '', $last_name = '', $extra = [] ) {
		global $wpdb;

		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', "Invalid email: {$email}" );
		}

		if ( $this->get_by_email( $email ) ) {
			return new WP_Error( 'duplicate_email', "Already exists: {$email}" );
		}

		$row = [
			'email'      => $email,
			'first_name' => sanitize_text_field( $first_name ),
			'last_name'  => sanitize_text_field( $last_name ),
			'status'     => 'active',
		];

		if ( isset( $extra['phone'] ) )   { $row['phone']   = sanitize_text_field( $extra['phone'] ); }
		if ( isset( $extra['address'] ) ) { $row['address'] = sanitize_textarea_field( $extra['address'] ); }
		if ( isset( $extra['website'] ) ) { $row['website'] = esc_url_raw( $extra['website'] ); }
		if ( isset( $extra['notes'] ) )   { $row['notes']   = sanitize_textarea_field( $extra['notes'] ); }

		$result = $wpdb->insert( $this->table, $row );

		return ( $result === false ) ? new WP_Error( 'db_error', 'Database insert failed.' ) : $wpdb->insert_id;
	}

	/* ------------------------------------------------------------------ */
	/*  Update                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * Edit an existing subscriber's details.
	 * Does NOT change the subscriber's tags — use ECWP_Tags::set_subscriber_tags().
	 *
	 * @param int    $id
	 * @param array  $data  Keys: email, first_name, last_name, status, phone, address, website, notes
	 * @return int|WP_Error  Rows updated, or WP_Error on failure.
	 */
	public function update( $id, array $data ) {
		global $wpdb;

		$allowed = [];

		if ( isset( $data['email'] ) ) {
			$email = sanitize_email( $data['email'] );
			if ( ! is_email( $email ) ) {
				return new WP_Error( 'invalid_email', 'Invalid email address.' );
			}
			// Check uniqueness (exclude self)
			$existing = $this->get_by_email( $email );
			if ( $existing && (int) $existing->id !== (int) $id ) {
				return new WP_Error( 'duplicate_email', 'That email already belongs to another subscriber.' );
			}
			$allowed['email'] = $email;
		}

		if ( isset( $data['first_name'] ) ) { $allowed['first_name'] = sanitize_text_field( $data['first_name'] ); }
		if ( isset( $data['last_name'] ) )  { $allowed['last_name']  = sanitize_text_field( $data['last_name'] ); }
		if ( isset( $data['phone'] ) )      { $allowed['phone']      = sanitize_text_field( $data['phone'] ); }
		if ( isset( $data['address'] ) )    { $allowed['address']    = sanitize_textarea_field( $data['address'] ); }
		if ( isset( $data['website'] ) )    { $allowed['website']    = esc_url_raw( $data['website'] ); }
		if ( isset( $data['notes'] ) )      { $allowed['notes']      = sanitize_textarea_field( $data['notes'] ); }

		if ( isset( $data['status'] ) && in_array( $data['status'], [ 'active', 'unsubscribed' ], true ) ) {
			$allowed['status'] = $data['status'];
			if ( $data['status'] === 'unsubscribed' ) {
				$allowed['unsubscribed_at'] = current_time( 'mysql' );
			}
		}

		if ( empty( $allowed ) ) {
			return 0;
		}

		return $wpdb->update( $this->table, $allowed, [ 'id' => $id ], null, [ '%d' ] );
	}

	/* ------------------------------------------------------------------ */
	/*  Delete                                                              */
	/* ------------------------------------------------------------------ */

	public function delete( $id ) {
		global $wpdb;
		// Also remove tag assignments
		$wpdb->delete( $this->tag_pivot, [ 'subscriber_id' => $id ], [ '%d' ] );
		return $wpdb->delete( $this->table, [ 'id' => $id ], [ '%d' ] );
	}

	/* ------------------------------------------------------------------ */
	/*  Unsubscribe                                                         */
	/* ------------------------------------------------------------------ */

	/**
	 * Mark a subscriber as unsubscribed and mirror to WP user meta.
	 */
	public function unsubscribe( $email ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->table,
			[ 'status' => 'unsubscribed', 'unsubscribed_at' => current_time( 'mysql' ) ],
			[ 'email'  => $email ],
			[ '%s', '%s' ],
			[ '%s' ]
		);

		// Mirror on WP user account if one exists.
		$wp_user = get_user_by( 'email', $email );
		if ( $wp_user ) {
			update_user_meta( $wp_user->ID, 'ecwp_unsubscribed',    1 );
			update_user_meta( $wp_user->ID, 'ecwp_unsubscribed_at', current_time( 'mysql' ) );
		}

		return $result;
	}

	/* ------------------------------------------------------------------ */
	/*  CSV Import                                                          */
	/* ------------------------------------------------------------------ */

	/**
	 * Bulk-import subscribers from a CSV file.
	 * CSV must have at minimum an "email" column.
	 * Optional columns: first_name, last_name.
	 *
	 * @return array|WP_Error  [ 'imported', 'skipped', 'errors' ]
	 */
	public function import_csv( $file_path ) {
		$results = [ 'imported' => 0, 'skipped' => 0, 'errors' => [] ];

		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_missing', 'CSV file not found.' );
		}

		$handle = fopen( $file_path, 'r' );
		if ( ! $handle ) {
			return new WP_Error( 'file_open', 'Could not open CSV file.' );
		}

		// Read & normalize headers.
		$raw_headers = fgetcsv( $handle );
		if ( ! $raw_headers ) {
			fclose( $handle );
			return new WP_Error( 'csv_headers', 'Could not read CSV headers.' );
		}
		$headers = array_map( 'trim', array_map( 'strtolower', $raw_headers ) );

		// Resolve column indices — support common header variants.
		$email_idx = false;
		$fn_idx    = false;
		$ln_idx    = false;

		foreach ( $headers as $i => $h ) {
			if ( in_array( $h, [ 'email', 'email address', 'e-mail', 'emailaddress' ], true ) ) {
				$email_idx = $i;
			} elseif ( in_array( $h, [ 'first_name', 'firstname', 'first name', 'first' ], true ) ) {
				$fn_idx = $i;
			} elseif ( in_array( $h, [ 'last_name', 'lastname', 'last name', 'last' ], true ) ) {
				$ln_idx = $i;
			}
		}

		if ( $email_idx === false ) {
			fclose( $handle );
			return new WP_Error( 'csv_no_email', 'CSV must contain an "email" column.' );
		}

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( empty( $row[ $email_idx ] ) ) {
				continue;
			}

			$email      = trim( $row[ $email_idx ] );
			$first_name = ( $fn_idx !== false && isset( $row[ $fn_idx ] ) ) ? trim( $row[ $fn_idx ] ) : '';
			$last_name  = ( $ln_idx !== false && isset( $row[ $ln_idx ] ) ) ? trim( $row[ $ln_idx ] ) : '';

			$result = $this->add( $email, $first_name, $last_name );

			if ( is_wp_error( $result ) ) {
				if ( $result->get_error_code() === 'duplicate_email' ) {
					$results['skipped']++;
				} else {
					$results['errors'][] = $email . ': ' . $result->get_error_message();
				}
			} else {
				$results['imported']++;
			}
		}

		fclose( $handle );
		return $results;
	}

	/* ------------------------------------------------------------------ */
	/*  Helper: return IDs only                                             */
	/* ------------------------------------------------------------------ */

	public function get_all_ids( $status = 'active' ) {
		global $wpdb;
		$where = $wpdb->prepare( ' WHERE status = %s', $status );
		return $wpdb->get_col( "SELECT id FROM {$this->table}{$where}" );
	}
}
