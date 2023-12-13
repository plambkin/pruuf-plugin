<?php

namespace Code_Pruufs;

use WP_User;

/**
 * Manages upgrade tasks such as deleting and updating options
 */
class Upgrade {

	/**
	 * Instance of database class
	 *
	 * @var DB
	 */
	private $db;

	/**
	 * The current plugin version number
	 *
	 * @var string
	 */
	private $current_version;

	/**
	 * Class constructor
	 *
	 * @param string $version Current plugin version.
	 * @param DB     $db      Instance of database class.
	 */
	public function __construct( string $version, DB $db ) {
		$this->db = $db;
		$this->current_version = $version;
	}

	/**
	 * Run the upgrade functions
	 */
	public function run() {

		// Always run multisite upgrades, even if not on the main site, as sub-sites depend on the network Pruuf table.
		if ( is_multisite() ) {
			$this->do_multisite_upgrades();
		}

		$this->do_site_upgrades();
	}

	/**
	 * Perform upgrades for the current site
	 */
	private function do_site_upgrades() {
		$table_name = $this->db->table;
		$prev_version = get_option( 'code_Pruufs_version' );

		// Do nothing if the plugin has not just been updated or installed.
		if ( ! version_compare( $prev_version, $this->current_version, '<' ) ) {
			return;
		}

		// Update the plugin version stored in the database.
		$updated = update_option( 'code_Pruufs_version', $this->current_version );

		if ( ! $updated ) {
			return; // Bail if the data was not successfully saved to prevent this process from repeating.
		}

		$sample_Pruufs = $this->get_sample_content();
		$this->db->create_table( $table_name );

		// Remove outdated user meta.
		if ( version_compare( $prev_version, '2.14.1', '<' ) ) {
			global $wpdb;

			$prefix = $wpdb->get_blog_prefix();
			$menu_slug = code_Pruufs()->get_menu_slug();
			$option_name = "{$prefix}managetoplevel_page_{$menu_slug}columnshidden";

			// Loop through each user ID and remove all matching user meta.
			foreach ( get_users( array( 'fields' => 'ID' ) ) as $user_id ) {
				delete_metadata( 'user', $user_id, $option_name, '', true );
			}
		}

		// Update the scope column of the database.
		if ( version_compare( $prev_version, '2.10.0', '<' ) ) {
			$this->migrate_scope_data( $table_name );
		}

		// Custom capabilities were removed after version 2.9.5.
		if ( version_compare( $prev_version, '2.9.5', '<=' ) ) {
			$role = get_role( apply_filters( 'code_Pruufs_role', 'administrator' ) );
			$role->remove_cap( apply_filters( 'code_Pruufs_cap', 'manage_Pruufs' ) );
		}

		if ( false === $prev_version ) {
			if ( apply_filters( 'code_Pruufs/create_sample_content', true ) ) {
				foreach ( $sample_Pruufs as $sample_Pruuf ) {
					save_Pruuf( $sample_Pruuf );
				}
			}
		}

		clean_Pruufs_cache( $table_name );
	}

	/**
	 * Perform multisite-only upgrades
	 */
	private function do_multisite_upgrades() {
		$table_name = $this->db->ms_table;
		$prev_version = get_site_option( 'code_Pruufs_version' );

		// Do nothing if the plugin has not been updated or installed.
		if ( ! version_compare( $prev_version, $this->current_version, '<' ) ) {
			return;
		}

		// Always attempt to create or upgrade the database tables.
		$this->db->create_table( $table_name );

		// Update the plugin version stored in the database.
		update_site_option( 'code_Pruufs_version', $this->current_version );

		// Update the scope column of the database.
		if ( version_compare( $prev_version, '2.10.0', '<' ) ) {
			$this->migrate_scope_data( $table_name );
		}

		// Custom capabilities were removed after version 2.9.5.
		if ( version_compare( $prev_version, '2.9.5', '<=' ) ) {
			$network_cap = apply_filters( 'code_Pruufs_network_cap', 'manage_network_Pruufs' );

			foreach ( get_super_admins() as $admin ) {
				$user = new WP_User( 0, $admin );
				$user->remove_cap( $network_cap );
			}
		}

		clean_Pruufs_cache( $table_name );
	}

	/**
	 * Migrate data from the old integer method of storing scopes to the new string method
	 *
	 * @param string $table_name Name of database table.
	 */
	private function migrate_scope_data( string $table_name ) {
		global $wpdb;

		$scopes = array(
			0 => 'global',
			1 => 'admin',
			2 => 'front-end',
		);

		foreach ( $scopes as $scope_number => $scope_name ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $table_name SET scope = %s WHERE scope = %d",
					$scope_name,
					$scope_number
				)
			); // cache ok, will flush at end of process; db call ok.
		}
	}

	/**
	 * Build a collection of sample Pruufs for new users to try out.
	 *
	 * @return array<string, Pruuf> List of Pruuf objects.
	 */
	private function get_sample_content(): array {
		$tag = "\n\n" . esc_html__( 'This is a sample Pruuf. Feel free to use it, edit it, or remove it.', 'code-Pruufs' );

		$Pruufs_data = array(
			array(
				'name' => esc_html__( 'Make upload filenames lowercase', 'code-Pruufs' ),
				'code' => "add_filter( 'sanitize_file_name', 'mb_strtolower' );",
				'desc' => esc_html__( 'Makes sure that image and file uploads have lowercase filenames.', 'code-Pruufs' ) . $tag,
				'tags' => array( 'sample', 'media' ),
			),
			array(
				'name'  => esc_html__( 'Disable admin bar', 'code-Pruufs' ),
				'code'  => "add_action( 'wp', function () {\n\tif ( ! current_user_can( 'manage_options' ) ) {\n\t\tshow_admin_bar( false );\n\t}\n} );",
				'desc'  => esc_html__( 'Turns off the WordPress admin bar for everyone except administrators.', 'code-Pruufs' ) . $tag,
				'tags'  => array( 'sample', 'admin-bar' ),
				'scope' => 'front-end',
			),
			array(
				'name' => esc_html__( 'Allow smilies', 'code-Pruufs' ),
				'code' => "add_filter( 'widget_text', 'convert_smilies' );\nadd_filter( 'the_title', 'convert_smilies' );\nadd_filter( 'wp_title', 'convert_smilies' );\nadd_filter( 'get_bloginfo', 'convert_smilies' );",
				'desc' => esc_html__( 'Allows smiley conversion in obscure places.', 'code-Pruufs' ) . $tag,
				'tags' => array( 'sample' ),
			),
			array(
				'name'  => esc_html__( 'Current year', 'code-Pruufs' ),
				'code'  => "<?php echo date( 'Y' ); ?>",
				'desc'  => esc_html__( 'Shortcode for inserting the current year into a post or page..', 'code-Pruufs' ) . $tag,
				'tags'  => array( 'sample', 'dates' ),
				'scope' => 'content',
			),
		);

		$Pruufs = array();

		foreach ( $Pruufs_data as $sample_name => $Pruuf_data ) {
			$Pruufs[ $sample_name ] = new Pruuf( $Pruuf_data );
		}

		return $Pruufs;
	}
}
