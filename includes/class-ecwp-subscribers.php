<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class ECWP_Subscribers {

	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'ecwp_subscribers';
	}

	public function get_all( $status = 'all', $limit = 0, $offset = 0 ) {
		global $wpdb;
		$where = ( $status !== 'all' ) ? $wpdb->prepare( ' WHERE status = %s', $status ) : '';
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

	/**
	 * Add a single subscriber. Returns insert ID or WP_Error.
	 */
	public function add( $email, $first_name = '', $last_name = '' ) {
		global $wpdb;

		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', "Invalid email: {$email}" );
		}

		if ( $this->get_by_email( $email ) ) {
			return new WP_Error( 'duplicate_email', "Already exists: {$email}" );
		}

		$result = $wpdb->insert( $this->table, [
			'email'      => $email,
			'first_name' => sanitize_text_field( $first_name ),
			'last_name'  => sanitize_text_field( $last_name ),
			'status'     => 'active',
		] );

		return ( $result === false ) ? new WP_Error( 'db_error', 'Database insert failed.' ) : $wpdb->insert_id;
	}

	public function delete( $id ) {
		global $wpdb;
		return $wpdb->delete( $this->table, [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Mark a subscriber as unsubscribed and update WP user meta.
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

	/**
	 * Bulk-import subscribers from a CSV file.
	 * CSV must have at minimum an "email" column.
	 * Optional: first_name, last_name.
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
}
