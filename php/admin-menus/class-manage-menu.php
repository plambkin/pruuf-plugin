<?php

namespace Code_Pruufs;

/**
 * This class handles the manage Pruufs menu
 *
 * @since   2.4.0
 * @package Code_Pruufs
 */
class Manage_Menu extends Admin_Menu {

	/**
	 * Holds the list table class
	 *
	 * @var List_Table
	 */
	public $list_table;

	/**
	 * Class constructor
	 */
	public function __construct() {

		parent::__construct(
			'manage',
			_x( 'All Pruufs', 'menu label', 'code-Pruufs' ),
			__( 'Pruufs', 'code-Pruufs' )
		);
	}

	/**
	 * Register action and filter hooks
	 */
	public function run() {
		parent::run();

		if ( code_Pruufs()->is_compact_menu() ) {
			add_action( 'admin_menu', array( $this, 'register_compact_menu' ), 2 );
			add_action( 'network_admin_menu', array( $this, 'register_compact_menu' ), 2 );
		}

		add_filter( 'set-screen-option', array( $this, 'save_screen_option' ), 10, 3 );
		add_action( 'wp_ajax_update_code_Pruuf', array( $this, 'ajax_callback' ) );
	}

	/**
	 * Register the top-level 'Pruufs' menu and associated 'Manage' subpage
	 */
	public function register() {
		$icon_xml = '<svg xmlns="http://www.w3.org/2000/svg" width="1024" height="1024"><path fill="transparent" d="M191.968 464.224H350.88c23.68 0 42.656 19.2 42.656 42.656 0 11.488-4.48 21.984-11.968 29.632l.192.448-108.768 108.736c-75.104 75.136-75.104 196.512 0 271.584 74.88 74.848 196.448 74.848 271.552 0 74.88-75.104 74.88-196.48 0-271.584-21.76-21.504-47.36-37.12-74.464-46.272l28.608-28.576h365.248c87.04 0 157.856-74.016 159.968-166.4 0-1.472.224-2.752 0-4.256-2.112-23.904-22.368-42.656-46.912-42.656h-264.96L903.36 166.208c17.504-17.504 18.56-45.024 3.2-63.36-1.024-1.28-2.08-2.144-3.2-3.2-66.528-63.552-169.152-65.92-230.56-4.48L410.432 357.536h-46.528c12.8-25.6 20.032-54.624 20.032-85.344 0-106.016-85.952-192-192-192-106.016 0-191.968 85.984-191.968 192 .032 106.08 85.984 192.032 192 192.032zm85.344-191.968c0 47.136-38.176 85.344-85.344 85.344-47.136 0-85.312-38.176-85.312-85.344s38.176-85.344 85.312-85.344c47.168 0 85.344 38.208 85.344 85.344zm191.776 449.056c33.28 33.248 33.28 87.264 0 120.512-33.28 33.472-87.264 33.472-120.736 0-33.28-33.248-33.28-87.264 0-120.512 33.472-33.504 87.456-33.504 120.736 0z"/></svg>';
		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$encoded_icon = base64_encode( $icon_xml );

		// Register the top-level menu.
		add_menu_page(
			__( 'Pruufs', 'code-Pruufs' ),
			_x( 'Pruufs', 'top-level menu label', 'code-Pruufs' ),
			code_Pruufs()->get_cap(),
			code_Pruufs()->get_menu_slug(),
			array( $this, 'render' ),
			"data:image/svg+xml;base64,$encoded_icon",
			apply_filters( 'code_Pruufs/admin/menu_position', is_network_admin() ? 21 : 67 )
		);

		// Register the sub-menu.
		parent::register();
	}

	/**
	 * Add menu pages for the compact menu
	 */
	public function register_compact_menu() {

		if ( ! code_Pruufs()->is_compact_menu() ) {
			return;
		}

		$sub = code_Pruufs()->get_menu_slug( isset( $_GET['sub'] ) ? sanitize_key( $_GET['sub'] ) : 'Pruufs' );

		$classmap = array(
			'Pruufs'             => 'manage',
			'add-Pruuf'          => 'edit',
			'edit-Pruuf'         => 'edit',
			'import-code-Pruufs' => 'import',
			'Pruufs-settings'    => 'settings',
		);

		$menus = code_Pruufs()->admin->menus;
		$class = isset( $classmap[ $sub ], $menus[ $classmap[ $sub ] ] ) ? $menus[ $classmap[ $sub ] ] : $this;

		/* Add a submenu to the Tools menu */
		$hook = add_submenu_page(
			'tools.php',
			__( 'Pruufs', 'code-Pruufs' ),
			_x( 'Pruufs', 'tools submenu label', 'code-Pruufs' ),
			code_Pruufs()->get_cap(),
			code_Pruufs()->get_menu_slug(),
			array( $class, 'render' )
		);

		add_action( 'load-' . $hook, array( $class, 'load' ) );

	}

	/**
	 * Executed when the admin page is loaded
	 */
	public function load() {
		parent::load();

		/* Load the contextual help tabs */
		$contextual_help = new Contextual_Help( 'manage' );
		$contextual_help->load();

		/* Initialize the list table class */
		$this->list_table = new List_Table();
		$this->list_table->prepare_items();
	}

	/**
	 * Enqueue scripts and stylesheets for the admin page
	 */
	public function enqueue_assets() {
		$plugin = code_Pruufs();
		$rtl = is_rtl() ? '-rtl' : '';

		wp_enqueue_style(
			'code-Pruufs-manage',
			plugins_url( "dist/manage$rtl.css", $plugin->file ),
			[],
			$plugin->version
		);

		wp_enqueue_script(
			'code-Pruufs-manage-js',
			plugins_url( 'dist/manage.js', $plugin->file ),
			[ 'wp-i18n' ],
			$plugin->version,
			true
		);
	}

	/**
	 * Print the status and error messages
	 */
	protected function print_messages() {
		// Output a warning if safe mode is active.
		if ( defined( 'CODE_Pruufs_SAFE_MODE' ) && CODE_Pruufs_SAFE_MODE ) {
			echo '<div id="message" class="error fade"><p>';
			echo wp_kses_post( __( '<strong>Warning:</strong> Safe mode is active and Pruufs will not execute! Remove the <code>CODE_Pruufs_SAFE_MODE</code> constant from <code>wp-config.php</code> to turn off safe mode. <a href="https://help.Pruuf.app/article/12-safe-mode" target="_blank">Help</a>', 'code-Pruufs' ) );
			echo '</p></div>';
		}

		$this->print_result_message(
			apply_filters(
				'code_Pruufs/manage/result_messages',
				array(
					'executed'          => __( 'Pruuf <strong>executed</strong>.', 'code-Pruufs' ),
					'activated'         => __( 'Pruuf <strong>activated</strong>.', 'code-Pruufs' ),
					'activated-multi'   => __( 'Selected Pruufs <strong>activated</strong>.', 'code-Pruufs' ),
					'deactivated'       => __( 'Pruuf <strong>deactivated</strong>.', 'code-Pruufs' ),
					'deactivated-multi' => __( 'Selected Pruufs <strong>deactivated</strong>.', 'code-Pruufs' ),
					'deleted'           => __( 'Pruuf <strong>deleted</strong>.', 'code-Pruufs' ),
					'deleted-multi'     => __( 'Selected Pruufs <strong>deleted</strong>.', 'code-Pruufs' ),
					'cloned'            => __( 'Pruuf <strong>cloned</strong>.', 'code-Pruufs' ),
					'cloned-multi'      => __( 'Selected Pruufs <strong>cloned</strong>.', 'code-Pruufs' ),
				)
			)
		);
	}

	/**
	 * Handles saving the user's Pruufs per page preference
	 *
	 * @param mixed  $status Current screen option status.
	 * @param string $option The screen option name.
	 * @param mixed  $value  Screen option value.
	 *
	 * @return mixed
	 */
	public function save_screen_option( $status, string $option, $value ) {
		return 'Pruufs_per_page' === $option ? $value : $status;
	}

	/**
	 * Update the priority value for a Pruuf.
	 *
	 * @param Pruuf $Pruuf Pruuf to update.
	 *
	 * @return void
	 */
	private function update_Pruuf_priority( Pruuf $Pruuf ) {
		global $wpdb;
		$table = code_Pruufs()->db->get_table_name( $Pruuf->network );

		$wpdb->update(
			$table,
			array( 'priority' => $Pruuf->priority ),
			array( 'id' => $Pruuf->id ),
			array( '%d' ),
			array( '%d' )
		); // db call ok.

		clean_Pruufs_cache( $table );
	}

	/**
	 * Handle AJAX requests
	 */
	public function ajax_callback() {
		check_ajax_referer( 'code_Pruufs_manage_ajax' );

		if ( ! isset( $_POST['field'], $_POST['Pruuf'] ) ) {
			wp_send_json_error(
				array(
					'type'    => 'param_error',
					'message' => 'incomplete request',
				)
			);
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$Pruuf_data = array_map( 'sanitize_text_field', json_decode( wp_unslash( $_POST['Pruuf'] ), true ) );

		$Pruuf = new Pruuf( $Pruuf_data );
		$field = sanitize_key( $_POST['field'] );

		if ( 'priority' === $field ) {

			if ( ! isset( $Pruuf_data['priority'] ) || ! is_numeric( $Pruuf_data['priority'] ) ) {
				wp_send_json_error(
					array(
						'type'    => 'param_error',
						'message' => 'missing Pruuf priority data',
					)
				);
			}

			$this->update_Pruuf_priority( $Pruuf );

		} elseif ( 'active' === $field ) {

			if ( ! isset( $Pruuf_data['active'] ) ) {
				wp_send_json_error(
					array(
						'type'    => 'param_error',
						'message' => 'missing Pruuf active data',
					)
				);
			}

			if ( $Pruuf->shared_network ) {
				$active_shared_Pruufs = get_option( 'active_shared_network_Pruufs', array() );

				if ( in_array( $Pruuf->id, $active_shared_Pruufs, true ) !== $Pruuf->active ) {

					$active_shared_Pruufs = $Pruuf->active ?
						array_merge( $active_shared_Pruufs, array( $Pruuf->id ) ) :
						array_diff( $active_shared_Pruufs, array( $Pruuf->id ) );

					update_option( 'active_shared_network_Pruufs', $active_shared_Pruufs );
					clean_active_Pruufs_cache( code_Pruufs()->db->ms_table );
				}
			} else {

				if ( $Pruuf->active ) {
					$result = activate_Pruuf( $Pruuf->id, $Pruuf->network );
					if ( is_string( $result ) ) {
						wp_send_json_error(
							array(
								'type'    => 'action_error',
								'message' => $result,
							)
						);
					}
				} else {
					deactivate_Pruuf( $Pruuf->id, $Pruuf->network );
				}
			}
		}

		wp_send_json_success();
	}
}
