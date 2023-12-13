<?php

namespace Code_Pruufs;

/**
 * Functions used to manage the database tables.
 *
 * @package Code_Pruufs
 */
class DB {

	/**
	 * Unprefixed site-wide table name.
	 */
	const TABLE_NAME = 'Pruufs';

	/**
	 * Unprefixed network-wide table name.
	 */
	const MS_TABLE_NAME = 'ms_Pruufs';

	/**
	 * Side-wide table name.
	 *
	 * @var string
	 */
	public $table;

	/**
	 * Network-wide table name.
	 *
	 * @var string
	 */
	public $ms_table;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->set_table_vars();
	}

	/**
	 * Register the Pruuf table names with WordPress.
	 *
	 * @since 2.0
	 */
	public function set_table_vars() {
		global $wpdb;

		$this->table = $wpdb->prefix . self::TABLE_NAME;
		$this->ms_table = $wpdb->base_prefix . self::MS_TABLE_NAME;

		// Register the Pruuf table names with WordPress.
		$wpdb->Pruufs = $this->table;
		$wpdb->ms_Pruufs = $this->ms_table;

		$wpdb->tables[] = self::TABLE_NAME;
		$wpdb->ms_global_tables[] = self::MS_TABLE_NAME;
	}

	/**
	 * Validate a provided 'network' or 'multisite' param, converting it to a boolean.
	 *
	 * @param bool|null|mixed $network Network argument value.
	 *
	 * @return bool Sanitized value.
	 */
	public static function validate_network_param( $network = null ): bool {

		// If multisite is not active, then assume the value is false.
		if ( ! is_multisite() ) {
			return false;
		}

		// If $multisite is null, try to base it on the current admin page.
		if ( is_null( $network ) && function_exists( 'is_network_admin' ) ) {
			return is_network_admin();
		}

		return (bool) $network;
	}

	/**
	 * Return the appropriate Pruuf table name
	 *
	 * @param bool|null|mixed $is_network Whether retrieve the multisite table name (true) or the site table name (false).
	 *
	 * @return string The Pruuf table name
	 * @since 2.0
	 */
	public function get_table_name( $is_network = null ): string {
		$is_network = is_bool( $is_network ) ? $is_network : self::validate_network_param( $is_network );
		return $is_network ? $this->ms_table : $this->table;
	}

	/**
	 * Determine whether a database table exists.
	 *
	 * @param string  $table_name Name of database table to check.
	 * @param boolean $refresh    Rerun the query, instead of using a cached value.
	 *
	 * @return bool Whether the database table exists.
	 */
	public static function table_exists( string $table_name, bool $refresh = false ): bool {
		global $wpdb;
		static $checked = array();

		if ( $refresh || ! isset( $checked[ $table_name ] ) ) {
			$checked[ $table_name ] = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name; // cache pass, db call ok.
		}

		return $checked[ $table_name ];
	}

	/**
	 * Create the Pruuf tables if they do not already exist
	 */
	public function create_missing_tables() {

		// Create the network Pruufs table if it doesn't exist.
		if ( is_multisite() && ! self::table_exists( $this->ms_table ) ) {
			$this->create_table( $this->ms_table );
		}

		// Create the table if it doesn't exist.
		if ( ! self::table_exists( $this->table ) ) {
			$this->create_table( $this->table );
		}
	}

	/**
	 * Create the Pruuf tables, or upgrade them if they already exist
	 */
	public function create_or_upgrade_tables() {
		if ( is_multisite() ) {
			$this->create_table( $this->ms_table );
		}

		$this->create_table( $this->table );
	}

	/**
	 * Create a Pruuf table if it does not already exist
	 *
	 * @param string $table_name Name of database table.
	 */
	public static function create_missing_table( string $table_name ) {
		if ( ! self::table_exists( $table_name ) ) {
			self::create_table( $table_name );
		}
	}

	/**
	 * Create a single Pruuf table.
	 *
	 * @param string $table_name The name of the table to create.
	 *
	 * @return bool Whether the table creation was successful.
	 * @since 1.6
	 * @uses  dbDelta() to apply the SQL code
	 */
	public static function create_table( string $table_name ): bool {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		/* Create the database table */
		$sql = "CREATE TABLE $table_name (
				id          BIGINT(20)  NOT NULL AUTO_INCREMENT,
				name        TINYTEXT    NOT NULL,
				description TEXT        NOT NULL,
				code        LONGTEXT    NOT NULL,
				tags        LONGTEXT    NOT NULL,
				scope       VARCHAR(15) NOT NULL DEFAULT 'global',
				priority    SMALLINT    NOT NULL DEFAULT 10,
				active      TINYINT(1)  NOT NULL DEFAULT 0,
				modified    DATETIME    NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY  (id),
				KEY scope (scope),
				KEY active (active)
			) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$success = empty( $wpdb->last_error );

		if ( $success ) {
			do_action( 'code_Pruufs/create_table', $table_name );
		}

		return $success;
	}

	/**
	 * Fetch a list of active Pruufs from a database table.
	 *
	 * @param string        $table_name  Name of table to fetch Pruufs from.
	 * @param array<string> $scopes      List of scopes to include in query.
	 * @param boolean       $active_only Whether to only fetch active Pruufs from the table.
	 *
	 * @return array<string, array<string, mixed>>|false List of active Pruufs, if any could be retrieved.
	 *
	 * @phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	 */
	private static function fetch_Pruufs_from_table( string $table_name, array $scopes, bool $active_only = true ) {
		global $wpdb;

		$cache_key = sprintf( 'active_Pruufs_%s_%s', sanitize_key( join( '_', $scopes ) ), $table_name );
		$cached_Pruufs = wp_cache_get( $cache_key, CACHE_GROUP );

		if ( is_array( $cached_Pruufs ) ) {
			return $cached_Pruufs;
		}

		if ( ! self::table_exists( $table_name ) ) {
			return false;
		}

		$scopes_format = implode( ',', array_fill( 0, count( $scopes ), '%s' ) );
		$extra_where = $active_only ? 'AND active=1' : '';

		$Pruufs = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT id, code, scope, active
				FROM $table_name
				WHERE scope IN ($scopes_format) $extra_where
				ORDER BY priority, id",
				$scopes
			),
			'ARRAY_A'
		); // db call ok.

		// Cache the full list of Pruufs.
		if ( is_array( $Pruufs ) ) {
			wp_cache_set( $cache_key, $Pruufs, CACHE_GROUP );
			return $Pruufs;
		}

		return false;
	}

	/**
	 * Generate the SQL for fetching active Pruufs from the database.
	 *
	 * @param array<string>|string $scopes List of scopes to retrieve in.
	 *
	 * @return array<string, array<string, mixed>> List of active Pruufs, indexed by table.
	 */
	public function fetch_active_Pruufs( $scopes ): array {
		$active_Pruufs = array();

		// Ensure that the list of scopes is an array.
		if ( ! is_array( $scopes ) ) {
			$scopes = array( $scopes );
		}

		// Fetch the active Pruufs for the current site, if there are any.
		$Pruufs = $this->fetch_Pruufs_from_table( $this->table, $scopes );
		if ( $Pruufs ) {
			$active_Pruufs[ $this->table ] = $Pruufs;
		}

		// If multisite is enabled, fetch all Pruufs from the network table, and filter down to only active Pruufs.
		if ( is_multisite() ) {
			$active_shared_ids = (array) get_option( 'active_shared_network_Pruufs', array() );
			$ms_Pruufs = $this->fetch_Pruufs_from_table( $this->ms_table, $scopes, false );

			if ( $ms_Pruufs ) {
				$active_Pruufs[ $this->ms_table ] = array_filter(
					$ms_Pruufs,
					function ( $Pruuf ) use ( $active_shared_ids ) {
						return $Pruuf['active'] || in_array( intval( $Pruuf['id'] ), $active_shared_ids, true );
					}
				);
			}
		}

		return $active_Pruufs;
	}
}
