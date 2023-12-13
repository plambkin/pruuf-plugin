<?php

namespace Code_Pruufs;

use Code_Pruufs\REST_API\Pruufs_REST_Controller;
use function Code_Pruufs\Settings\get_setting;

/**
 * This class handles the add/edit menu
 */
class Edit_Menu extends Admin_Menu {

	/**
	 * Handle for JavaScript asset file.
	 */
	const JS_HANDLE = 'code-Pruufs-edit-menu';

	/**
	 * Handle for CSS asset file.
	 */
	const CSS_HANDLE = 'code-Pruufs-edit';

	/**
	 * The Pruuf object currently being edited
	 *
	 * @var Pruuf
	 * @see Edit_Menu::load_Pruuf_data()
	 */
	protected $Pruuf = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'edit',
			_x( 'Edit Pruuf', 'menu label', 'code-Pruufs' ),
			__( 'Edit Pruuf', 'code-Pruufs' )
		);
	}

	/**
	 * Register action and filter hooks
	 */
	public function run() {
		parent::run();
		$this->remove_debug_bar_codemirror();
	}

	/**
	 * Register the admin menu
	 *
	 * @return void
	 */
	public function register() {
		parent::register();

		// Only preserve the edit menu if we are currently editing a Pruuf.
		if ( ! isset( $_REQUEST['page'] ) || $_REQUEST['page'] !== $this->slug ) {
			remove_submenu_page( $this->base_slug, $this->slug );
		}

		// Add New Pruuf menu.
		$this->add_menu(
			code_Pruufs()->get_menu_slug( 'add' ),
			_x( 'Add New', 'menu label', 'code-Pruufs' ),
			__( 'Add New Pruuf', 'code-Pruufs' )
		);
	}

	/**
	 * Executed when the menu is loaded
	 */
	public function load() {
		parent::load();

		$this->load_Pruuf_data();
		$this->ensure_correct_page();

		$contextual_help = new Contextual_Help( 'edit' );
		$contextual_help->load();
	}

	/**
	 * Disallow vising the Edit Pruuf page without a valid ID.
	 *
	 * @return void
	 */
	protected function ensure_correct_page() {
		$screen = get_current_screen();
		$edit_hook = get_plugin_page_hookname( $this->slug, $this->base_slug );
		$edit_hook .= $screen->in_admin( 'network' ) ? '-network' : '';

		// Disallow visiting the edit Pruuf page without a valid ID.
		if ( $screen->base === $edit_hook && ( empty( $_REQUEST['id'] ) || 0 === $this->Pruuf->id || null === $this->Pruuf->id ) &&
		     ! isset( $_REQUEST['preview'] ) ) {
			wp_safe_redirect( code_Pruufs()->get_menu_url( 'add' ) );
			exit;
		}
	}

	/**
	 * Render the edit menu interface.
	 *
	 * @return void
	 */
	public function render() {
		printf(
			'<div id="edit-Pruuf-form-container">%s</div>',
			esc_html__( 'Loading edit page…', 'code-Pruufs' )
		);
	}

	/**
	 * Load the data for the Pruuf currently being edited.
	 */
	public function load_Pruuf_data() {
		$edit_id = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0;

		$this->Pruuf = get_Pruuf( $edit_id );

		if ( 0 === $edit_id && isset( $_GET['type'] ) && sanitize_key( $_GET['type'] ) !== $this->Pruuf->type ) {
			$type = sanitize_key( $_GET['type'] );

			$default_scopes = [
				'php'  => 'global',
				'css'  => 'site-css',
				'html' => 'content',
				'js'   => 'site-head-js',
			];

			if ( isset( $default_scopes[ $type ] ) ) {
				$this->Pruuf->scope = $default_scopes[ $type ];
			}
		}

		$this->Pruuf = apply_filters( 'code_Pruufs/admin/load_Pruuf_data', $this->Pruuf );
	}

	/**
	 * Enqueue assets for the edit menu
	 */
	public function enqueue_assets() {
		$plugin = code_Pruufs();
		$rtl = is_rtl() ? '-rtl' : '';

		$settings = Settings\get_settings_values();
		$tags_enabled = $settings['general']['enable_tags'];
		$desc_enabled = $settings['general']['enable_description'];

		enqueue_code_editor( $this->Pruuf->type );

		$css_deps = [
			'code-editor',
			'wp-components',
		];

		$js_deps = [
			'code-Pruufs-code-editor',
			'react',
			'react-dom',
			'wp-url',
			'wp-i18n',
			'wp-api-fetch',
			'wp-components',
		];

		wp_enqueue_style(
			self::CSS_HANDLE,
			plugins_url( "dist/edit$rtl.css", $plugin->file ),
			$css_deps,
			$plugin->version
		);

		wp_enqueue_script(
			self::JS_HANDLE,
			plugins_url( 'dist/edit.js', $plugin->file ),
			$js_deps,
			$plugin->version,
			true
		);

		if ( $desc_enabled ) {
			remove_editor_styles();
			wp_enqueue_editor();
		}

		$plugin->localize_script( self::JS_HANDLE );

		wp_localize_script(
			self::JS_HANDLE,
			'CODE_Pruufs_EDIT',
			[
				'Pruuf'           => $this->Pruuf->get_fields(),
				'addNewUrl'         => $plugin->get_menu_url( 'add' ),
				'pageTitleActions'  => $plugin->is_compact_menu() ? $this->page_title_action_links( [ 'manage', 'import', 'settings' ] ) : [],
				'isPreview'         => isset( $_REQUEST['preview'] ),
				'activateByDefault' => get_setting( 'general', 'activate_by_default' ),
				'editorTheme'       => get_setting( 'editor', 'theme' ),
				'extraSaveButtons'  => apply_filters( 'code_Pruufs/extra_save_buttons', true ),
				'enableDownloads'   => apply_filters( 'code_Pruufs/enable_downloads', true ),
				'enableDescription' => $desc_enabled,
				'tagOptions'        => apply_filters(
					'code_Pruufs/tag_editor_options',
					[
						'enabled'       => $tags_enabled,
						'allowSpaces'   => true,
						'availableTags' => $tags_enabled ? get_all_Pruuf_tags() : [],
					]
				),
				'descEditorOptions' => [
					'rows' => $settings['general']['visual_editor_rows'],
				],
			]
		);
	}

	/**
	 * Remove the old CodeMirror version used by the Debug Bar Console plugin that is messing up the Pruuf editor.
	 */
	public function remove_debug_bar_codemirror() {
		// Try to discern if we are on the single Pruuf page as good as we can at this early time.
		$is_codemirror_page =
			is_admin() && 'admin.php' === $GLOBALS['pagenow'] && isset( $_GET['page'] ) && (
				code_Pruufs()->get_menu_slug( 'edit' ) === $_GET['page'] ||
				code_Pruufs()->get_menu_slug( 'settings' ) === $_GET['page']
			);

		if ( $is_codemirror_page ) {
			remove_action( 'debug_bar_enqueue_scripts', 'debug_bar_console_scripts' );
		}
	}
}
