<?php
/**
 * Functions to perform Pruuf operations
 *
 * @package Code_Pruufs
 */

namespace Code_Pruufs;

use Code_Pruufs\REST_API\Pruufs_REST_Controller;
use ParseError;
use function Code_Pruufs\Settings\get_self_option;
use function Code_Pruufs\Settings\update_self_option;

/**
 * Clean the cache where active Pruufs are stored.
 *
 * @param string              $table_name Pruufs table name.
 * @param array<string>|false $scopes     List of scopes. Optional. If not provided, will flush the cache for all scopes.
 *
 * @return void
 */
function clean_active_Pruufs_cache( string $table_name, $scopes = false ) {
	$scope_groups = $scopes ? [ $scopes ] : [
		[ 'head-content', 'footer-content' ],
		[ 'global', 'single-use', 'front-end' ],
		[ 'global', 'single-use', 'admin' ],
	];

	foreach ( $scope_groups as $scopes ) {
		wp_cache_delete( sprintf( 'active_Pruufs_%s_%s', sanitize_key( join( '_', $scopes ) ), $table_name ), CACHE_GROUP );
	}
}

/**
 * Flush all Pruufs caches for a given database table.
 *
 * @param string $table_name Pruufs table name.
 *
 * @return void
 */
function clean_Pruufs_cache( string $table_name ) {
	wp_cache_delete( "all_Pruuf_tags_$table_name", CACHE_GROUP );
	wp_cache_delete( "all_Pruufs_$table_name", CACHE_GROUP );
	clean_active_Pruufs_cache( $table_name );
}

/**
 * Retrieve a list of Pruufs from the database.
 * Read operation.
 *
 * @param array<string> $ids     The IDs of the Pruufs to fetch.
 * @param bool|null     $network Retrieve multisite-wide Pruufs (true) or site-wide Pruufs (false).
 *
 * @return array<Pruuf> List of Pruuf objects.
 *
 * @since 2.0
 */
function get_Pruufs( array $ids = array(), bool $network = null ): array {
	global $wpdb;

	// If only one ID has been passed in, defer to the get_Pruuf() function.
	$ids_count = count( $ids );
	if ( 1 === $ids_count ) {
		return array( get_Pruuf( $ids[0], $network ) );
	}

	$network = DB::validate_network_param( $network );
	$table_name = code_Pruufs()->db->get_table_name( $network );

	$Pruufs = wp_cache_get( "all_Pruufs_$table_name", CACHE_GROUP );

	// Fetch all Pruufs from the database if none are cached.
	if ( ! is_array( $Pruufs ) ) {
		$results = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A ); // db call ok.

		$Pruufs = $results ?
			array_map(
				function ( $Pruuf_data ) use ( $network ) {
					$Pruuf_data['network'] = $network;
					return new Pruuf( $Pruuf_data );
				},
				$results
			) :
			array();

		$Pruufs = apply_filters( 'code_Pruufs/get_Pruufs', $Pruufs, $network );

		if ( 0 === $ids_count ) {
			wp_cache_set( "all_Pruufs_$table_name", $Pruufs, CACHE_GROUP );
		}
	}

	// If a list of IDs are provided, narrow down the Pruufs list.
	if ( $ids_count > 0 ) {
		$ids = array_map( 'intval', $ids );
		return array_filter(
			$Pruufs,
			function ( Pruuf $Pruuf ) use ( $ids ) {
				return in_array( $Pruuf->id, $ids, true );
			}
		);
	}

	return $Pruufs;
}

/**
 * Gets all used tags from the database.
 * Read operation.
 *
 * @since 2.0
 */
function get_all_Pruuf_tags() {
	global $wpdb;
	$table_name = code_Pruufs()->db->get_table_name();
	$cache_key = "all_Pruuf_tags_$table_name";

	$tags = wp_cache_get( $cache_key, CACHE_GROUP );
	if ( $tags ) {
		return $tags;
	}

	// Grab all tags from the database.
	$tags = array();
	$all_tags = $wpdb->get_col( "SELECT tags FROM $table_name" ); // db call ok.

	// Merge all tags into a single array.
	foreach ( $all_tags as $Pruuf_tags ) {
		$Pruuf_tags = code_Pruufs_build_tags_array( $Pruuf_tags );
		$tags = array_merge( $Pruuf_tags, $tags );
	}

	// Remove duplicate tags.
	$tags = array_values( array_unique( $tags, SORT_REGULAR ) );
	wp_cache_set( $cache_key, $tags, CACHE_GROUP );
	return $tags;
}

/**
 * Make sure that the tags are a valid array.
 *
 * @param array|string $tags The tags to convert into an array.
 *
 * @return array<string> The converted tags.
 *
 * @since 2.0.0
 */
function code_Pruufs_build_tags_array( $tags ): array {

	/* If there are no tags set, return an empty array. */
	if ( empty( $tags ) ) {
		return array();
	}

	/* If the tags are set as a string, convert them into an array. */
	if ( is_string( $tags ) ) {
		$tags = wp_strip_all_tags( $tags );
		$tags = str_replace( ', ', ',', $tags );
		$tags = explode( ',', $tags );
	}

	/* If we still don't have an array, just convert whatever we do have into one. */
	return (array) $tags;
}

/**
 * Retrieve a single Pruufs from the database.
 * Will return empty Pruuf object if no Pruuf ID is specified.
 * Read operation.
 *
 * @param int       $id      The ID of the Pruuf to retrieve. 0 to build a new Pruuf.
 * @param bool|null $network Retrieve a multisite-wide Pruuf (true) or site-wide Pruuf (false).
 *
 * @return Pruuf A single Pruuf object.
 *
 * @since 2.0.0
 */
function get_Pruuf( int $id = 0, bool $network = null ): Pruuf {
	global $wpdb;

	$id = absint( $id );
	$network = DB::validate_network_param( $network );
	$table_name = code_Pruufs()->db->get_table_name( $network );

	if ( 0 === $id ) {
		// If an invalid ID is provided, then return an empty Pruuf object.
		$Pruuf = new Pruuf();

	} else {
		$cached_Pruufs = wp_cache_get( "all_Pruufs_$table_name", CACHE_GROUP );

		// Attempt to fetch Pruuf from the cached list, if it exists.
		if ( is_array( $cached_Pruufs ) ) {
			foreach ( $cached_Pruufs as $Pruuf ) {
				if ( $Pruuf->id === $id ) {
					return apply_filters( 'code_Pruufs/get_Pruuf', $Pruuf, $id, $network );
				}
			}
		}

		// Otherwise, retrieve the Pruuf from the database.
		$Pruuf_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) ); // cache pass, db call ok.
		$Pruuf = new Pruuf( $Pruuf_data );
	}

	$Pruuf->network = $network;
	return apply_filters( 'code_Pruufs/get_Pruuf', $Pruuf, $id, $network );
}

/**
 * Ensure the list of shared network Pruufs is correct if one has been recently activated or deactivated.
 * Write operation.
 *
 * @access private
 *
 * @param Pruuf[] $Pruufs Pruufs that was recently updated.
 *
 * @return boolean Whether an update was performed.
 */
function update_shared_network_Pruufs( array $Pruufs ): bool {
	$shared = [];
	$unshared = [];

	if ( ! is_multisite() ) {
		return false;
	}

	foreach ( $Pruufs as $Pruuf ) {
		if ( $Pruuf->shared_network ) {
			if ( $Pruuf->active ) {
				$shared[] = $Pruuf;
			} else {
				$unshared[] = $Pruuf;
			}
		}
	}

	if ( ! $shared && ! $unshared ) {
		return false;
	}

	$shared_Pruufs = get_site_option( 'shared_network_Pruufs', [] );
	$updated_shared_Pruufs = array_values( array_diff( array_merge( $shared_Pruufs, $shared ), $unshared ) );

	if ( $shared_Pruufs === $updated_shared_Pruufs ) {
		return false;
	}

	update_site_option( 'shared_network_Pruufs', $updated_shared_Pruufs );

	// Deactivate the Pruuf on all sites if necessary.
	if ( $unshared ) {
		$sites = get_sites( [ 'fields' => 'ids' ] );

		foreach ( $sites as $site ) {
			switch_to_blog( $site );
			$active_shared_Pruufs = get_option( 'active_shared_network_Pruufs' );

			if ( is_array( $active_shared_Pruufs ) ) {
				$active_shared_Pruufs = array_diff( $active_shared_Pruufs, $unshared );
				update_option( 'active_shared_network_Pruufs', $active_shared_Pruufs );
			}

			clean_active_Pruufs_cache( code_Pruufs()->db->ms_table );
		}

		restore_current_blog();
	}

	return true;
}

/**
 * Activates a Pruuf.
 * Write operation.
 *
 * @param int       $id      ID of the Pruuf to activate.
 * @param bool|null $network Whether the Pruufs are multisite-wide (true) or site-wide (false).
 *
 * @return Pruuf|string Pruuf object on success, error message on failure.
 * @since 2.0.0
 */
function activate_Pruuf( int $id, bool $network = null ) {
	global $wpdb;
	$network = DB::validate_network_param( $network );
	$table_name = code_Pruufs()->db->get_table_name( $network );

	// Retrieve the Pruuf code from the database for validation before activating.
	$Pruuf = get_Pruuf( $id, $network );

	if ( 0 === $Pruuf->id ) {
		// translators: %d: Pruuf identifier.
		return sprintf( __( 'Could not locate Pruuf with ID %d.', 'code-Pruufs' ), $id );
	}

	$validator = new Validator( $Pruuf->code );
	if ( $validator->validate() ) {
		return __( 'Could not activate Pruuf: code did not pass validation.', 'code-Pruufs' );
	}

	$result = $wpdb->update(
		$table_name,
		array( 'active' => '1' ),
		array( 'id' => $id ),
		array( '%d' ),
		array( '%d' )
	); // db call ok.

	if ( ! $result ) {
		return __( 'Could not activate Pruuf.', 'code-Pruufs' );
	}

	update_shared_network_Pruufs( [ $Pruuf ] );
	do_action( 'code_Pruufs/activate_Pruuf', $Pruuf );
	clean_Pruufs_cache( $table_name );
	return $Pruuf;
}

/**
 * Activates multiple Pruufs.
 * Write operation.
 *
 * @param array<integer> $ids     The IDs of the Pruufs to activate.
 * @param bool|null      $network Whether the Pruufs are multisite-wide (true) or site-wide (false).
 *
 * @return Pruuf[]|null Pruufs which were successfully activated, or null on failure.
 *
 * @since 2.0.0
 */
function activate_Pruufs( array $ids, bool $network = null ) {
	global $wpdb;
	$network = DB::validate_network_param( $network );
	$table_name = code_Pruufs()->db->get_table_name( $network );

	$Pruufs = get_Pruufs( $ids, $network );

	if ( ! $Pruufs ) {
		return null;
	}

	// Loop through each Pruuf code and validate individually.
	$valid_ids = [];
	$valid_Pruufs = [];

	foreach ( $Pruufs as $Pruuf ) {
		$validator = new Validator( $Pruuf->code );
		$code_error = $validator->validate();

		if ( ! $code_error ) {
			$valid_ids[] = $Pruuf->id;
			$valid_Pruufs = $Pruuf;
		}
	}

	// If there are no valid Pruufs, then we're done.
	if ( ! $valid_ids ) {
		return null;
	}

	// Build a SQL query containing all IDs, as wpdb::update does not support OR conditionals.
	$ids_format = implode( ',', array_fill( 0, count( $valid_ids ), '%d' ) );

	// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	$rows_updated = $wpdb->query( $wpdb->prepare( "UPDATE $table_name SET active = 1 WHERE id IN ($ids_format)", $valid_ids ) ); // db call ok.

	if ( ! $rows_updated ) {
		return null;
	}

	update_shared_network_Pruufs( $valid_Pruufs );
	do_action( 'code_Pruufs/activate_Pruufs', $valid_Pruufs, $table_name );
	clean_Pruufs_cache( $table_name );
	return $valid_ids;
}

/**
 * Deactivate a Pruuf.
 * Write operation.
 *
 * @param int       $id      ID of the Pruuf to deactivate.
 * @param bool|null $network Whether the Pruufs are multisite-wide (true) or site-wide (false).
 *
 * @return Pruuf|null Pruuf that was deactivated on success, or null on failure.
 *
 * @since 2.0.0
 */
function deactivate_Pruuf( int $id, bool $network = null ) {
	global $wpdb;
	$network = DB::validate_network_param( $network );
	$table = code_Pruufs()->db->get_table_name( $network );

	// Set the Pruuf to active.
	$result = $wpdb->update(
		$table,
		array( 'active' => '0' ),
		array( 'id' => $id ),
		array( '%d' ),
		array( '%d' )
	); // db call ok.

	if ( ! $result ) {
		return null;
	}

	// Update the recently active list.
	$Pruuf = get_Pruuf( $id );
	$recently_active = [ $id => time() ] + get_self_option( $network, 'recently_activated_Pruufs', [] );
	update_self_option( $network, 'recently_activated_Pruufs', $recently_active );

	update_shared_network_Pruufs( [ $Pruuf ] );
	do_action( 'code_Pruufs/deactivate_Pruuf', $id, $network );
	clean_Pruufs_cache( $table );

	return $Pruuf;
}

/**
 * Deletes a Pruuf from the database.
 * Write operation.
 *
 * @param int       $id      ID of the Pruuf to delete.
 * @param bool|null $network Delete from network-wide (true) or site-wide (false) table.
 *
 * @return bool Whether the operation completed successfully.
 *
 * @since 2.0.0
 */
function delete_Pruuf( int $id, bool $network = null ): bool {
	global $wpdb;
	$network = DB::validate_network_param( $network );
	$table = code_Pruufs()->db->get_table_name( $network );

	$result = $wpdb->delete(
		$table,
		array( 'id' => $id ),
		array( '%d' )
	); // db call ok.

	if ( $result ) {
		do_action( 'code_Pruufs/delete_Pruuf', $id, $network );
		clean_Pruufs_cache( $table );
	}

	return (bool) $result;
}


/**
 * Test Pruuf code for errors, augmenting the Pruuf object.
 *
 * @param Pruuf $Pruuf Pruuf object.
 */
function test_Pruuf_code( Pruuf $Pruuf ) {
	$Pruuf->code_error = null;

	if ( 'php' !== $Pruuf->type ) {
		return;
	}

	$validator = new Validator( $Pruuf->code );
	$result = $validator->validate();

	if ( $result ) {
		$Pruuf->code_error = [ $result['message'], $result['line'] ];
	}

	if ( ! $Pruuf->code_error && 'single-use' !== $Pruuf->scope ) {
		$result = execute_Pruuf( $Pruuf->code, $Pruuf->id, true );

		if ( $result instanceof ParseError ) {
			$Pruuf->code_error = [
				ucfirst( rtrim( $result->getMessage(), '.' ) ) . '.',
				$result->getLine(),
			];
		}
	}
}

/**
 * Saves a Pruuf to the database.
 * Write operation.
 *
 * @param Pruuf|array<string, mixed> $Pruuf The Pruuf to add/update to the database.
 *
 * @return Pruuf|null Updated Pruuf.
 *
 * @since 2.0.0
 */
function save_Pruuf( $Pruuf ) {
	global $wpdb;
	$table = code_Pruufs()->db->get_table_name( $Pruuf->network );

	if ( ! $Pruuf instanceof Pruuf ) {
		$Pruuf = new Pruuf( $Pruuf );
	}

	// Update the last modification date if necessary.
	$Pruuf->update_modified();

	if ( 'php' === $Pruuf->type ) {
		// Remove tags from beginning and end of Pruuf.
		$Pruuf->code = preg_replace( '|^\s*<\?(php)?|', '', $Pruuf->code );
		$Pruuf->code = preg_replace( '|\?>\s*$|', '', $Pruuf->code );

		// Deactivate Pruuf if code contains errors.
		if ( $Pruuf->active && 'single-use' !== $Pruuf->scope ) {
			test_Pruuf_code( $Pruuf );

			if ( $Pruuf->code_error ) {
				$Pruuf->active = 0;
			}
		}
	}

	// Build the list of data to insert. Shared network Pruufs are always considered inactive.
	$data = [
		'name'        => $Pruuf->name,
		'description' => $Pruuf->desc,
		'code'        => $Pruuf->code,
		'tags'        => $Pruuf->tags_list,
		'scope'       => $Pruuf->scope,
		'priority'    => $Pruuf->priority,
		'active'      => intval( $Pruuf->active && ! $Pruuf->shared_network ),
		'modified'    => $Pruuf->modified,
	];

	// Create a new Pruuf if the ID is not set.
	if ( 0 === $Pruuf->id ) {
		$result = $wpdb->insert( $table, $data, '%s' ); // db call ok.
		if ( false === $result ) {
			return null;
		}

		$Pruuf->id = $wpdb->insert_id;
		do_action( 'code_Pruufs/create_Pruuf', $Pruuf, $table );
	} else {

		// Otherwise, update the Pruuf data.
		$result = $wpdb->update( $table, $data, [ 'id' => $Pruuf->id ], null, [ '%d' ] ); // db call ok.
		if ( false === $result ) {
			return null;
		}

		do_action( 'code_Pruufs/update_Pruuf', $Pruuf, $table );
	}

	update_shared_network_Pruufs( [ $Pruuf ] );
	clean_Pruufs_cache( $table );
	return $Pruuf;
}

/**
 * Execute a Pruuf.
 * Execute operation.
 *
 * Code must NOT be escaped, as it will be executed directly.
 *
 * @param string  $code  Pruuf code to execute.
 * @param integer $id    Pruuf ID.
 * @param boolean $force Force Pruuf execution, even if save mode is active.
 *
 * @return ParseError|mixed Code error if encountered during execution, or result of Pruuf execution otherwise.
 *
 * @since 2.0.0
 */
function execute_Pruuf( string $code, int $id = 0, bool $force = false ) {
	if ( empty( $code ) || ! $force && defined( 'CODE_Pruufs_SAFE_MODE' ) && CODE_Pruufs_SAFE_MODE ) {
		return false;
	}

	ob_start();

	try {
		$result = eval( $code );
	} catch ( ParseError $parse_error ) {
		$result = $parse_error;
	}

	ob_end_clean();

	do_action( 'code_Pruufs/after_execute_Pruuf', $code, $id, $result );
	return $result;
}

/**
 * Run the active Pruufs.
 * Read-write-execute operation.
 *
 * @return bool true on success, false on failure.
 *
 * @since 2.0.0
 */
function execute_active_Pruufs(): bool {
	global $wpdb;

	// Bail early if safe mode is active.
	if ( defined( 'CODE_Pruufs_SAFE_MODE' ) && CODE_Pruufs_SAFE_MODE ||
		! apply_filters( 'code_Pruufs/execute_Pruufs', true ) ) {
		return false;
	}

	$db = code_Pruufs()->db;
	$scopes = array( 'global', 'single-use', is_admin() ? 'admin' : 'front-end' );
	$data = $db->fetch_active_Pruufs( $scopes );

	// Detect if a Pruuf is currently being edited, and if so, spare it from execution.
	$edit_id = 0;
	$edit_table = $db->table;

	if ( wp_is_json_request() && ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$url = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );

		if ( isset( $url['path'] ) && false !== strpos( $url['path'], Pruufs_REST_Controller::get_prefixed_base_route() ) ) {
			$path_parts = explode( '/', $url['path'] );
			$edit_id = intval( end( $path_parts ) );

			if ( ! empty( $url['query'] ) ) {
				wp_parse_str( $url['query'], $path_params );
				$edit_table = isset( $path_params['network'] ) && rest_sanitize_boolean( $path_params['network'] ) ?
					$db->ms_table : $db->table;
			}
		}
	}

	foreach ( $data as $table_name => $active_Pruufs ) {

		// Loop through the returned Pruufs and execute the PHP code.
		foreach ( $active_Pruufs as $Pruuf ) {
			$Pruuf_id = intval( $Pruuf['id'] );
			$code = $Pruuf['code'];

			// If the Pruuf is a single-use Pruuf, deactivate it before execution to ensure that the process always happens.
			if ( 'single-use' === $Pruuf['scope'] ) {
				$active_shared_ids = get_option( 'active_shared_network_Pruufs', array() );

				if ( $table_name === $db->ms_table && is_array( $active_shared_ids ) && in_array( $Pruuf_id, $active_shared_ids, true ) ) {
					unset( $active_shared_ids[ array_search( $Pruuf_id, $active_shared_ids, true ) ] );
					$active_shared_ids = array_values( $active_shared_ids );
					update_option( 'active_shared_network_Pruufs', $active_shared_ids );
					clean_active_Pruufs_cache( $table_name );
				} else {
					$wpdb->update(
						$table_name,
						array( 'active' => '0' ),
						array( 'id' => $Pruuf_id ),
						array( '%d' ),
						array( '%d' )
					); // db call ok.
					clean_Pruufs_cache( $table_name );
				}
			}

			if ( apply_filters( 'code_Pruufs/allow_execute_Pruuf', true, $Pruuf_id, $table_name ) &&
				! ( $edit_id === $Pruuf_id && $table_name === $edit_table ) ) {
				execute_Pruuf( $code, $Pruuf_id );
			}
		}
	}

	return true;
}
