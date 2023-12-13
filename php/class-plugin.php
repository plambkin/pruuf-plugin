<?php

namespace Code_Pruufs;

use Code_Pruufs\REST_API\Pruufs_REST_Controller;

/**
 * The main plugin class
 *
 * @package Code_Pruufs
 */
class Plugin {

	/**
	 * Current plugin version number
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Filesystem path to the main plugin file
	 *
	 * @var string
	 */
	public $file;

	/**
	 * Database class
	 *
	 * @var DB
	 */
	public $db;

	/**
	 * Administration area class
	 *
	 * @var Admin
	 */
	public $admin;

	/**
	 * Front-end functionality class
	 *
	 * @var Frontend
	 */
	public $frontend;

	/**
	 * Class for managing active Pruufs
	 *
	 * @var Active_Pruufs
	 */
	public $active_Pruufs;

	/**
	 * Class constructor
	 *
	 * @param string $version Current plugin version.
	 * @param string $file    Path to main plugin file.
	 */
	public function __construct( string $version, string $file ) {
		$this->version = $version;
		$this->file = $file;

		wp_cache_add_global_groups( CACHE_GROUP );

		add_filter( 'code_Pruufs/execute_Pruufs', array( $this, 'disable_Pruuf_execution' ), 5 );

		if ( isset( $_REQUEST['Pruufs-safe-mode'] ) ) {
			add_filter( 'home_url', array( $this, 'add_safe_mode_query_var' ) );
			add_filter( 'admin_url', array( $this, 'add_safe_mode_query_var' ) );
		}

		add_action( 'rest_api_init', [ $this, 'register_rest_api_controllers' ] );
	}

	/**
	 * Initialise classes and include files
	 */
	public function load_plugin() {
		$includes_path = __DIR__;

		// Database operation functions.
		$this->db = new DB();

		// Pruuf operation functions.
		require_once $includes_path . '/pruuf-ops.php';

		// CodeMirror editor functions.
		require_once $includes_path . '/editor.php';

		// General Administration functions.
		if ( is_admin() ) {
			$this->admin = new Admin();
		}

		// Settings component.
		require_once $includes_path . '/settings/settings-fields.php';
		require_once $includes_path . '/settings/editor-preview.php';
		require_once $includes_path . '/settings/settings.php';
        require_once $includes_path . '/class-active-pruufs.php';


        $this->active_Pruufs = new Active_Pruufs();
		$this->frontend = new Frontend();

		$upgrade = new Upgrade( $this->version, $this->db );
		add_action( 'plugins_loaded', array( $upgrade, 'run' ), 0 );
	}

	/**
	 * Register custom REST API controllers.
	 *
	 * @return void
	 *
	 * @since [NEXT_VERSION]
	 */
	public function register_rest_api_controllers() {
		$controllers = [ new Pruufs_REST_Controller() ];

		foreach ( $controllers as $controller ) {
			$controller->register_routes();
		}
	}

	/**
	 * Disable Pruuf execution if the necessary query var is set.
	 *
	 * @param bool $execute_Pruufs Current filter value.
	 *
	 * @return bool New filter value.
	 */
	public function disable_Pruuf_execution( bool $execute_Pruufs ): bool {
		return ! empty( $_REQUEST['Pruufs-safe-mode'] ) && $this->current_user_can() ? false : $execute_Pruufs;
	}

	/**
	 * Determine whether the menu is full or compact.
	 *
	 * @return bool
	 */
	public function is_compact_menu(): bool {
		return ! is_network_admin() && apply_filters( 'code_Pruufs_compact_menu', false );
	}

	/**
	 * Fetch the admin menu slug for a menu.
	 *
	 * @param string $menu Name of menu to retrieve the slug for.
	 *
	 * @return string The menu's slug.
	 */
	public function get_menu_slug( string $menu = '' ): string {
		$add = array( 'single', 'add', 'add-new', 'add-Pruuf', 'new-Pruuf', 'add-new-Pruuf' );
		$edit = array( 'edit', 'edit-Pruuf' );
		$import = array( 'import', 'import-Pruufs', 'import-code-Pruufs' );
		$settings = array( 'settings', 'Pruufs-settings' );

		if ( in_array( $menu, $edit, true ) ) {
			return 'edit-Pruuf';
		} elseif ( in_array( $menu, $add, true ) ) {
			return 'add-Pruuf';
		} elseif ( in_array( $menu, $import, true ) ) {
			return 'import-code-Pruufs';
		} elseif ( in_array( $menu, $settings, true ) ) {
			return 'Pruufs-settings';
		} else {
			return 'Pruufs';
		}
	}

	/**
	 * Fetch the URL to a Pruufs admin menu.
	 *
	 * @param string $menu    Name of menu to retrieve the URL to.
	 * @param string $context URL scheme to use.
	 *
	 * @return string The menu's URL.
	 */
	public function get_menu_url( string $menu = '', string $context = 'self' ): string {
		$slug = $this->get_menu_slug( $menu );

		if ( $this->is_compact_menu() && 'network' !== $context ) {
			$base_slug = $this->get_menu_slug();
			$url = 'tools.php?page=' . $base_slug;

			if ( $slug !== $base_slug ) {
				$url .= '&sub=' . $slug;
			}
		} else {
			$url = 'admin.php?page=' . $slug;
		}

		if ( 'network' === $context || 'Pruufs-settings' === $slug ) {
			return network_admin_url( $url );
		} elseif ( 'admin' === $context ) {
			return admin_url( $url );
		} else {
			return self_admin_url( $url );
		}
	}

	/**
	 * Fetch the admin menu slug for a Pruufs admin menu.
	 *
	 * @param integer $Pruuf_id Pruuf ID.
	 * @param string  $context    URL scheme to use.
	 *
	 * @return string The URL to the edit Pruuf page for that Pruuf.
	 */
	public function get_Pruuf_edit_url( int $Pruuf_id, string $context = 'self' ): string {
		return add_query_arg(
			'id',
			absint( $Pruuf_id ),
			$this->get_menu_url( 'edit', $context )
		);
	}

	/**
	 * Determine whether the current user can perform actions on Pruufs.
	 *
	 * @return boolean Whether the current user has the required capability.
	 *
	 * @since 2.8.6
	 */
	public function current_user_can(): bool {
		return current_user_can( $this->get_cap() );
	}

	/**
	 * Retrieve the name of the capability required to manage sub-site Pruufs.
	 *
	 * @return string
	 */
	public function get_cap_name(): string {
		return apply_filters( 'code_Pruufs_cap', 'manage_options' );
	}

	/**
	 * Retrieve the name of the capability required to manage network Pruufs.
	 *
	 * @return string
	 */
	public function get_network_cap_name(): string {
		return apply_filters( 'code_Pruufs_network_cap', 'manage_network_options' );
	}

	/**
	 * Get the required capability to perform a certain action on Pruufs.
	 * Does not check if the user has this capability or not.
	 *
	 * If multisite, checks if *Enable Administration Menus: Pruufs* is active
	 * under the *Settings > Network Settings* network admin menu
	 *
	 * @return string The capability required to manage Pruufs.
	 *
	 * @since 2.0
	 */
	public function get_cap(): string {
		if ( is_multisite() ) {
			$menu_perms = get_site_option( 'menu_items', array() );

			// If multisite is enabled and the Pruuf menu is not activated, restrict Pruuf operations to super admins only.
			if ( empty( $menu_perms['Pruufs'] ) ) {
				return $this->get_network_cap_name();
			}
		}

		return $this->get_cap_name();
	}

	/**
	 * Inject the safe mode query var into URLs
	 *
	 * @param string $url Original URL.
	 *
	 * @return string Modified URL.
	 */
	public function add_safe_mode_query_var( string $url ): string {
		return isset( $_REQUEST['Pruufs-safe-mode'] ) ?
			add_query_arg( 'Pruufs-safe-mode', (bool) $_REQUEST['Pruufs-safe-mode'], $url ) :
			$url;
	}

	/**
	 * Retrieve a list of available Pruuf types and their labels.
	 *
	 * @return array<string, string> Pruuf types.
	 */
	public static function get_types(): array {
		return apply_filters(
			'code_Pruufs_types',
			array(
				'php'  => __( 'Functions', 'code-Pruufs' ),
				'html' => __( 'Content', 'code-Pruufs' ),
				'css'  => __( 'Styles', 'code-Pruufs' ),
				'js'   => __( 'Scripts', 'code-Pruufs' ),
			)
		);
	}

	/**
	 * Determine whether a Pruuf type is Pro-only.
	 *
	 * @param string $type Pruuf type name.
	 *
	 * @return bool
	 */
	public static function is_pro_type( string $type ): bool {
		return 'css' === $type || 'js' === $type;
	}

	/**
	 * Retrieve the description for a particular Pruuf type.
	 *
	 * @param string $type Pruuf type name.
	 *
	 * @return string
	 */
	public function get_type_description( string $type ): string {
		$descriptions = array(
			'php'  => __( 'Function Pruufs are run on your site as if there were in a plugin or theme functions.php file.', 'code-Pruufs' ),
			'html' => __( 'Content Pruufs are bits of reusable PHP and HTML content that can be inserted into posts and pages.', 'code-Pruufs' ),
			'css'  => __( 'Style Pruufs are written in CSS and loaded in the admin area or on the site front-end, just like the theme style.css.', 'code-Pruufs' ),
			'js'   => __( 'Script Pruufs are loaded on the site front-end in a JavaScript file, either in the head or body sections.', 'code-Pruufs' ),
		);

		$descriptions = apply_filters( 'code_Pruufs/plugins/type_descriptions', $descriptions );
		return $descriptions[ $type ] ?? '';
	}

	/**
	 * Localise a plugin script to provide the CODE_Pruufs object.
	 *
	 * @param string $handle Script handle.
	 *
	 * @return void
	 */
	public function localize_script( string $handle ) {
		wp_localize_script(
			$handle,
			'CODE_Pruufs',
			[
				'isLicensed' => false,
				'restAPI'    => [
					'base'     => esc_url_raw( rest_url() ),
					'Pruufs' => esc_url_raw( rest_url( Pruufs_REST_Controller::get_base_route() ) ),
					'nonce'    => wp_create_nonce( 'wp_rest' ),
				],
				'pluginUrl'  => plugins_url( '', PLUGIN_FILE ),
			]
		);
	}
}
