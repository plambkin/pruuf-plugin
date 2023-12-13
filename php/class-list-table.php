<?php

namespace Code_Pruufs;

use function Code_Pruufs\Settings\get_setting;
use WP_List_Table;

/**
 * Contains the class for handling the Pruufs table
 *
 * @package Code_Pruufs
 *
 * phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
 */

// The WP_List_Table base class is not included by default, so we need to load it.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * This class handles the table for the manage Pruufs menu
 *
 * @since   1.5
 * @package Code_Pruufs
 */
class List_Table extends WP_List_Table {

	/**
	 * Whether the current screen is in the network admin
	 *
	 * @var bool
	 */
	public $is_network;

	/**
	 * A list of statuses (views)
	 *
	 * @var array<string>
	 */
	public $statuses = array( 'all', 'active', 'inactive', 'recently_activated' );

	/**
	 * Column name to use when ordering the Pruufs list.
	 *
	 * @var string
	 */
	protected $order_by;

	/**
	 * Direction to use when ordering the Pruufs list. Either 'asc' or 'desc'.
	 *
	 * @var string
	 */
	protected $order_dir;

	/**
	 * The constructor function for our class.
	 * Registers hooks, initializes variables, setups class.
	 *
	 * @phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
	 */
	public function __construct() {
		global $status, $page;
		$this->is_network = is_network_admin();

		// Determine the status.
		$status = apply_filters( 'code_Pruufs/list_table/default_view', 'all' );
		if ( isset( $_REQUEST['status'] ) && in_array( sanitize_key( $_REQUEST['status'] ), $this->statuses, true ) ) {
			$status = sanitize_key( $_REQUEST['status'] );
		}

		// Add the search query to the URL.
		if ( isset( $_REQUEST['s'] ) ) {
			$_SERVER['REQUEST_URI'] = add_query_arg( 's', sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) );
		}

		// Add a Pruufs per page screen option.
		$page = $this->get_pagenum();

		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Pruufs per page', 'code-Pruufs' ),
				'default' => 999,
				'option'  => 'Pruufs_per_page',
			)
		);

		add_filter( 'default_hidden_columns', array( $this, 'default_hidden_columns' ) );

		// Strip the result query arg from the URL.
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'result' );

		// Add filters to format the Pruuf description in the same way the post content is formatted.
		$filters = [ 'wptexturize', 'convert_smilies', 'convert_chars', 'wpautop', 'shortcode_unautop', 'capital_P_dangit', [ $this, 'wp_kses_desc' ] ];
		foreach ( $filters as $filter ) {
			add_filter( 'code_Pruufs/list_table/column_description', $filter );
		}

		// Set up the class.
		parent::__construct(
			array(
				'ajax'     => true,
				'plural'   => 'Pruufs',
				'singular' => 'Pruuf',
			)
		);
	}

	/**
	 * Apply a more permissive version of wp_kses_post() to the Pruuf description.
	 *
	 * @param string $data Description content to filter.
	 *
	 * @return string Filtered description content with allowed HTML tags and attributes intact.
	 */
	public function wp_kses_desc( string $data ): string {
		$safe_style_filter = function ( $styles ) {
			$styles[] = 'display';
			return $styles;
		};

		add_filter( 'safe_style_css', $safe_style_filter );
		$data = wp_kses_post( $data );
		remove_filter( 'safe_style_css', $safe_style_filter );

		return $data;
	}

	/**
	 * Set the 'id' column as hidden by default.
	 *
	 * @param array<string> $hidden List of hidden columns.
	 *
	 * @return array<string> Modified list of hidden columns.
	 */
	public function default_hidden_columns( array $hidden ): array {
		$hidden[] = 'id';
		return $hidden;
	}

	/**
	 * Set the 'name' column as the primary column.
	 *
	 * @return string
	 */
	protected function get_default_primary_column_name(): string {
		return 'name';
	}

	/**
	 * Define the output of all columns that have no callback function
	 *
	 * @param Pruuf $item        The Pruuf used for the current row.
	 * @param string  $column_name The name of the column being printed.
	 *
	 * @return string The content of the column to output.
	 */
	protected function column_default( $item, $column_name ): string {
		switch ( $column_name ) {
			case 'id':
				return $item->id;

			case 'description':
				return apply_filters( 'code_Pruufs/list_table/column_description', $item->desc );

			case 'type':
				$type = $item->type;
				$url = add_query_arg( 'type', $type );

				return sprintf(
					'<a class="Pruuf-type-badge" href="%s" data-Pruuf-type="%s">%s</a>',
					esc_url( $url ),
					esc_attr( $type ),
					esc_html( $type )
				);

			case 'date':
				return $item->modified ? $item->format_modified() : '&#8212;';

			default:
				return apply_filters( "code_Pruufs/list_table/column_$column_name", '&#8212;', $item );
		}
	}

	/**
	 * Retrieve a URL to perform an action on a Pruuf
	 *
	 * @param string  $action  Name of action to produce a link for.
	 * @param Pruuf $Pruuf Pruuf object to produce link for.
	 *
	 * @return string URL to perform action.
	 */
	public function get_action_link( string $action, Pruuf $Pruuf ): string {

		// Redirect actions to the network dashboard for shared network Pruufs.
		$local_actions = array( 'activate', 'activate-shared', 'run-once', 'run-once-shared' );
		$network_redirect = $Pruuf->shared_network && ! $this->is_network && ! in_array( $action, $local_actions, true );

		// Edit links go to a different menu.
		if ( 'edit' === $action ) {
			return code_Pruufs()->get_Pruuf_edit_url( $Pruuf->id, $network_redirect ? 'network' : 'self' );
		}

		$query_args = array(
			'action' => $action,
			'id'     => $Pruuf->id,
			'scope'  => $Pruuf->scope,
		);

		$url = $network_redirect ?
			add_query_arg( $query_args, code_Pruufs()->get_menu_url( 'manage', 'network' ) ) :
			add_query_arg( $query_args );

		// Add a nonce to the URL for security purposes.
		return wp_nonce_url( $url, 'code_Pruufs_manage_Pruuf_' . $Pruuf->id );
	}

	/**
	 * Build a list of action links for individual Pruufs
	 *
	 * @param Pruuf $Pruuf The current Pruuf.
	 *
	 * @return array<string, string> The action links HTML.
	 */
	private function get_Pruuf_action_links( Pruuf $Pruuf ): array {
		$actions = array();

		if ( ! $this->is_network && $Pruuf->network && ! $Pruuf->shared_network ) {
			// Display special links if on a subsite and dealing with a network-active Pruuf.
			if ( $Pruuf->active ) {
				$actions['network_active'] = esc_html__( 'Network Active', 'code-Pruufs' );
			} else {
				$actions['network_only'] = esc_html__( 'Network Only', 'code-Pruufs' );
			}
		} elseif ( ! $Pruuf->shared_network || current_user_can( code_Pruufs()->get_network_cap_name() ) ) {

			// If the Pruuf is a shared network Pruuf, only display extra actions if the user has network permissions.
			$simple_actions = array(
				'edit'   => esc_html__( 'Edit', 'code-Pruufs' ),
				'clone'  => esc_html__( 'Clone', 'code-Pruufs' ),
				'export' => esc_html__( 'Export', 'code-Pruufs' ),
			);

			foreach ( $simple_actions as $action => $label ) {
				$actions[ $action ] = sprintf( '<a href="%s">%s</a>', esc_url( $this->get_action_link( $action, $Pruuf ) ), $label );
			}

			$actions['delete'] = sprintf(
				'<a href="%2$s" class="delete" onclick="%3$s">%1$s</a>',
				esc_html__( 'Delete', 'code-Pruufs' ),
				esc_url( $this->get_action_link( 'delete', $Pruuf ) ),
				esc_js(
					sprintf(
						'return confirm("%s");',
						esc_html__( 'You are about to permanently delete the selected item.', 'code-Pruufs' ) . "\n" .
						esc_html__( "'Cancel' to stop, 'OK' to delete.", 'code-Pruufs' )
					)
				)
			);
		}

		return apply_filters( 'code_Pruufs/list_table/row_actions', $actions, $Pruuf );
	}

	/**
	 * Retrieve the code for a Pruuf activation switch
	 *
	 * @param Pruuf $Pruuf Pruuf object.
	 *
	 * @return string Output for activation switch.
	 */
	protected function column_activate( Pruuf $Pruuf ): string {
		if ( $this->is_network && $Pruuf->shared_network || ( ! $this->is_network && $Pruuf->network && ! $Pruuf->shared_network ) ) {
			return '';
		}

		if ( 'single-use' === $Pruuf->scope ) {
			$class = 'Pruuf-execution-button';
			$action = 'run-once';
			$label = esc_html__( 'Run Once', 'code-Pruufs' );
		} else {
			$class = 'Pruuf-activation-switch';
			$action = $Pruuf->active ? 'deactivate' : 'activate';
			$label = $Pruuf->network && ! $Pruuf->shared_network ?
				( $Pruuf->active ? __( 'Network Deactivate', 'code-Pruufs' ) : __( 'Network Activate', 'code-Pruufs' ) ) :
				( $Pruuf->active ? __( 'Deactivate', 'code-Pruufs' ) : __( 'Activate', 'code-Pruufs' ) );
		}

		if ( $Pruuf->shared_network ) {
			$action .= '-shared';
		}

		return sprintf(
			'<a class="%s" href="%s" title="%s">&nbsp;</a> ',
			esc_attr( $class ),
			esc_url( $this->get_action_link( $action, $Pruuf ) ),
			esc_attr( $label )
		);
	}

	/**
	 * Build the content of the Pruuf name column
	 *
	 * @param Pruuf $Pruuf The Pruuf being used for the current row.
	 *
	 * @return string The content of the column to output.
	 */
	protected function column_name( Pruuf $Pruuf ): string {
		$row_actions = $this->row_actions(
			$this->get_Pruuf_action_links( $Pruuf ),
			apply_filters( 'code_Pruufs/list_table/row_actions_always_visible', true )
		);

		$out = esc_html( $Pruuf->display_name );

		if ( 'global' !== $Pruuf->scope ) {
			$out .= ' <span class="dashicons dashicons-' . $Pruuf->scope_icon . '"></span>';
		}

		// Add a link to the Pruuf if it isn't an unreadable network-only Pruuf.
		if ( $this->is_network || ! $Pruuf->network || current_user_can( code_Pruufs()->get_network_cap_name() ) ) {

			$out = sprintf(
				'<a href="%s" class="Pruuf-name">%s</a>',
				esc_attr( code_Pruufs()->get_Pruuf_edit_url( $Pruuf->id, $Pruuf->network ? 'network' : 'admin' ) ),
				$out
			);
		}

		if ( $Pruuf->shared_network ) {
			$out .= ' <span class="badge">' . esc_html__( 'Shared on Network', 'code-Pruufs' ) . '</span>';
		}

		// Return the name contents.

		$out = apply_filters( 'code_Pruufs/list_table/column_name', $out, $Pruuf );

		return $out . $row_actions;
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @param Pruuf $item The Pruuf being used for the current row.
	 *
	 * @return string The column content to be printed.
	 */
	protected function column_cb( $item ): string {
		$out = sprintf(
			'<input type="checkbox" name="%s[]" value="%s">',
			$item->shared_network ? 'shared_ids' : 'ids',
			$item->id
		);

		return apply_filters( 'code_Pruufs/list_table/column_cb', $out, $item );
	}

	/**
	 * Handles the tags column output.
	 *
	 * @param Pruuf $Pruuf The Pruuf being used for the current row.
	 *
	 * @return string The column output.
	 */
	protected function column_tags( Pruuf $Pruuf ): string {
		if ( empty( $Pruuf->tags ) ) {
			return '';
		}

		$out = array();

		// Loop through the tags and create a link for each one.
		foreach ( $Pruuf->tags as $tag ) {
			$out[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( add_query_arg( 'tag', esc_attr( $tag ) ) ),
				esc_html( $tag )
			);
		}

		return join( ', ', $out );
	}

	/**
	 * Handles the priority column output.
	 *
	 * @param Pruuf $Pruuf The Pruuf being used for the current row.
	 *
	 * @return string The column output.
	 */
	protected function column_priority( Pruuf $Pruuf ): string {
		return sprintf( '<input type="number" class="Pruuf-priority" value="%d" step="1" disabled>', $Pruuf->priority );
	}

	/**
	 * Define the column headers for the table
	 *
	 * @return array<string, string> The column headers, ID paired with label
	 */
	public function get_columns(): array {
		$columns = array(
			'cb'          => '<input type="checkbox">',
			'activate'    => '',
			'name'        => __( 'Name', 'code-Pruufs' ),
			'type'        => __( 'Type', 'code-Pruufs' ),
			'description' => __( 'Description', 'code-Pruufs' ),
			'tags'        => __( 'Tags', 'code-Pruufs' ),
			'date'        => __( 'Modified', 'code-Pruufs' ),
			'priority'    => __( 'Priority', 'code-Pruufs' ),
			'id'          => __( 'ID', 'code-Pruufs' ),
		);

		if ( isset( $_GET['type'] ) && 'all' !== $_GET['type'] ) {
			unset( $columns['type'] );
		}

		if ( ! get_setting( 'general', 'enable_description' ) ) {
			unset( $columns['description'] );
		}

		if ( ! get_setting( 'general', 'enable_tags' ) ) {
			unset( $columns['tags'] );
		}

		return apply_filters( 'code_Pruufs/list_table/columns', $columns );
	}

	/**
	 * Define the columns that can be sorted. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending.
	 *
	 * @return array<string, string|array<string|bool>> The IDs of the columns that can be sorted
	 */
	public function get_sortable_columns(): array {
		$sortable_columns = [
			'id'       => [ 'id', true ],
			'name'     => 'name',
			'type'     => [ 'type', true ],
			'date'     => [ 'modified', true ],
			'priority' => [ 'priority', true ],
		];

		return apply_filters( 'code_Pruufs/list_table/sortable_columns', $sortable_columns );
	}

	/**
	 * Define the bulk actions to include in the drop-down menus
	 *
	 * @return array<string, string> An array of menu items with the ID paired to the label
	 */
	public function get_bulk_actions(): array {
		$actions = [
			'activate-selected'   => $this->is_network ? __( 'Network Activate', 'code-Pruufs' ) : __( 'Activate', 'code-Pruufs' ),
			'deactivate-selected' => $this->is_network ? __( 'Network Deactivate', 'code-Pruufs' ) : __( 'Deactivate', 'code-Pruufs' ),
			'clone-selected'      => __( 'Clone', 'code-Pruufs' ),
			'download-selected'   => __( 'Export Code', 'code-Pruufs' ),
			'export-selected'     => __( 'Export', 'code-Pruufs' ),
			'delete-selected'     => __( 'Delete', 'code-Pruufs' ),
		];

		return apply_filters( 'code_Pruufs/list_table/bulk_actions', $actions );
	}

	/**
	 * Retrieve the classes for the table
	 *
	 * We override this in order to add 'Pruufs' as a class for custom styling
	 *
	 * @return array<string> The classes to include on the table element
	 */
	public function get_table_classes(): array {
		$classes = array( 'widefat', $this->_args['plural'] );

		return apply_filters( 'code_Pruufs/list_table/table_classes', $classes );
	}

	/**
	 * Retrieve the 'views' of the table
	 *
	 * Example: active, inactive, recently active
	 *
	 * @return array<string, string> A list of the view labels linked to the view
	 */
	public function get_views(): array {
		global $totals, $status;
		$status_links = array();

		// Loop through the view counts.
		foreach ( $totals as $type => $count ) {
			$labels = [];

			if ( ! $count ) {
				continue;
			}

			// translators: %s: total number of Pruufs.
			$labels['all'] = _n(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				$count,
				'code-Pruufs'
			);

			// translators: %s: total number of active Pruufs.
			$labels['active'] = _n(
				'Active <span class="count">(%s)</span>',
				'Active <span class="count">(%s)</span>',
				$count,
				'code-Pruufs'
			);

			// translators: %s: total number of inactive Pruufs.
			$labels['inactive'] = _n(
				'Inactive <span class="count">(%s)</span>',
				'Inactive <span class="count">(%s)</span>',
				$count,
				'code-Pruufs'
			);

			// translators: %s: total number of recently activated Pruufs.
			$labels['recently_activated'] = _n(
				'Recently Active <span class="count">(%s)</span>',
				'Recently Active <span class="count">(%s)</span>',
				$count,
				'code-Pruufs'
			);

			// The page URL with the status parameter.
			$url = esc_url( add_query_arg( 'status', $type ) );

			// Add a class if this view is currently being viewed.
			$class = $type === $status ? ' class="current"' : '';

			// Add the view count to the label.
			$text = sprintf( $labels[ $type ], number_format_i18n( $count ) );

			$status_links[ $type ] = sprintf( '<a href="%s"%s>%s</a>', $url, $class, $text );
		}

		return apply_filters( 'code_Pruufs/list_table/views', $status_links );
	}

	/**
	 * Gets the tags of the Pruufs currently being viewed in the table
	 *
	 * @since 2.0
	 */
	public function get_current_tags() {
		global $Pruufs, $status;

		// If we're not viewing a Pruufs table, get all used tags instead.
		if ( ! isset( $Pruufs, $status ) ) {
			$tags = get_all_Pruuf_tags();
		} else {
			$tags = array();

			// Merge all tags into a single array.
			foreach ( $Pruufs[ $status ] as $Pruuf ) {
				$tags = array_merge( $Pruuf->tags, $tags );
			}

			// Remove duplicate tags.
			$tags = array_unique( $tags );
		}

		sort( $tags );

		return $tags;
	}

	/**
	 * Add filters and extra actions above and below the table
	 *
	 * @param string $which Whether the actions are displayed on the before (true) or after (false) the table.
	 */
	public function extra_tablenav( $which ) {
		/**
		 * Status global.
		 *
		 * @var string $status
		 */
		global $status;

		if ( 'top' === $which ) {

			// Tags dropdown filter.
			$tags = $this->get_current_tags();

			if ( count( $tags ) ) {
				$query = isset( $_GET['tag'] ) ? sanitize_text_field( wp_unslash( $_GET['tag'] ) ) : '';

				echo '<div class="alignleft actions">';
				echo '<select name="tag">';

				printf(
					"<option %s value=''>%s</option>\n",
					selected( $query, '', false ),
					esc_html__( 'Show all tags', 'code-Pruufs' )
				);

				foreach ( $tags as $tag ) {

					printf(
						"<option %s value='%s'>%s</option>\n",
						selected( $query, $tag, false ),
						esc_attr( $tag ),
						esc_html( $tag )
					);
				}

				echo '</select>';

				submit_button( __( 'Filter', 'code-Pruufs' ), 'button', 'filter_action', false );
				echo '</div>';
			}
		}

		echo '<div class="alignleft actions">';

		if ( 'recently_activated' === $status ) {
			submit_button( __( 'Clear List', 'code-Pruufs' ), 'secondary', 'clear-recent-list', false );
		}

		do_action( 'code_Pruufs/list_table/actions', $which );

		echo '</div>';
	}

	/**
	 * Output form fields needed to preserve important
	 * query vars over form submissions
	 *
	 * @param string $context The context in which the fields are being outputted.
	 */
	public function required_form_fields( string $context = 'main' ) {
		$vars = apply_filters(
			'code_Pruufs/list_table/required_form_fields',
			array( 'page', 's', 'status', 'paged', 'tag' ),
			$context
		);

		if ( 'search_box' === $context ) {
			// Remove the 's' var if we're doing this for the search box.
			$vars = array_diff( $vars, array( 's' ) );
		}

		foreach ( $vars as $var ) {
			if ( ! empty( $_REQUEST[ $var ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_REQUEST[ $var ] ) );
				printf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $var ), esc_attr( $value ) );
				echo "\n";
			}
		}

		do_action( 'code_Pruufs/list_table/print_required_form_fields', $context );
	}

	/**
	 * Perform an action on a single Pruuf.
	 *
	 * @param int    $id     Pruuf ID.
	 * @param string $action Action to perform.
	 * @param string $scope  Pruuf scope; used for cache busting CSS and JS Pruufs.
	 *
	 * @return bool|string Result of performing action
	 */
	private function perform_action( int $id, string $action, string $scope = '' ) {

		switch ( $action ) {

			case 'activate':
				activate_Pruuf( $id, $this->is_network );
				return 'activated';

			case 'deactivate':
				deactivate_Pruuf( $id, $this->is_network );
				return 'deactivated';

			case 'run-once':
				$this->perform_action( $id, 'activate' );
				return 'executed';

			case 'run-once-shared':
				$this->perform_action( $id, 'activate-shared' );
				return 'executed';

			case 'activate-shared':
				$active_shared_Pruufs = get_option( 'active_shared_network_Pruufs', array() );

				if ( ! in_array( $id, $active_shared_Pruufs, true ) ) {
					$active_shared_Pruufs[] = $id;
					update_option( 'active_shared_network_Pruufs', $active_shared_Pruufs );
					clean_active_Pruufs_cache( code_Pruufs()->db->ms_table );
				}

				return 'activated';

			case 'deactivate-shared':
				$active_shared_Pruufs = get_option( 'active_shared_network_Pruufs', array() );
				update_option( 'active_shared_network_Pruufs', array_diff( $active_shared_Pruufs, array( $id ) ) );
				clean_active_Pruufs_cache( code_Pruufs()->db->ms_table );
				return 'deactivated';

			case 'clone':
				$this->clone_Pruufs( array( $id ) );
				return 'cloned';

			case 'delete':
				delete_Pruuf( $id, $this->is_network );
				return 'deleted';

			case 'export':
				$export = new Export_Attachment( $id );
				$export->download_Pruufs_json();
				break;

			case 'download':
				$export = new Export_Attachment( $id );
				$export->download_Pruufs_code();
				break;
		}

		return false;
	}

	/**
	 * Processes actions requested by the user.
	 *
	 * @return void
	 */
	public function process_requested_actions() {

		// Clear the recent Pruufs list if requested to do so.
		if ( isset( $_POST['clear-recent-list'] ) ) {
			check_admin_referer( 'bulk-' . $this->_args['plural'] );

			if ( $this->is_network ) {
				update_site_option( 'recently_activated_Pruufs', array() );
			} else {
				update_option( 'recently_activated_Pruufs', array() );
			}
		}

		// Check if there are any single Pruuf actions to perform.
		if ( isset( $_GET['action'], $_GET['id'] ) ) {
			$id = absint( $_GET['id'] );
			$scope = isset( $_GET['scope'] ) ? sanitize_key( wp_unslash( $_GET['scope'] ) ) : '';

			// Verify they were sent from a trusted source.
			$nonce_action = 'code_Pruufs_manage_Pruuf_' . $id;
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), $nonce_action ) ) {
				wp_nonce_ays( $nonce_action );
			}

			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'action', 'id', 'scope', '_wpnonce' ) );

			// If so, then perform the requested action and inform the user of the result.
			$result = $this->perform_action( $id, sanitize_key( $_GET['action'] ), $scope );

			if ( $result ) {
				wp_safe_redirect( esc_url_raw( add_query_arg( 'result', $result ) ) );
				exit;
			}
		}

		// Only continue from this point if there are bulk actions to process.
		if ( ! isset( $_POST['ids'] ) && ! isset( $_POST['shared_ids'] ) ) {
			return;
		}

		check_admin_referer( 'bulk-' . $this->_args['plural'] );

		$ids = isset( $_POST['ids'] ) ? array_map( 'intval', $_POST['ids'] ) : array();
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'action' );

		switch ( $this->current_action() ) {

			case 'activate-selected':
				activate_Pruufs( $ids );

				// Process the shared network Pruufs.
				if ( isset( $_POST['shared_ids'] ) && is_multisite() && ! $this->is_network ) {
					$active_shared_Pruufs = get_option( 'active_shared_network_Pruufs', array() );

					foreach ( array_map( 'intval', $_POST['shared_ids'] ) as $id ) {
						if ( ! in_array( $id, $active_shared_Pruufs, true ) ) {
							$active_shared_Pruufs[] = $id;
						}
					}

					update_option( 'active_shared_network_Pruufs', $active_shared_Pruufs );
					clean_active_Pruufs_cache( code_Pruufs()->db->ms_table );
				}

				$result = 'activated-multi';
				break;

			case 'deactivate-selected':
				foreach ( $ids as $id ) {
					deactivate_Pruuf( $id, $this->is_network );
				}

				// Process the shared network Pruufs.
				if ( isset( $_POST['shared_ids'] ) && is_multisite() && ! $this->is_network ) {
					$active_shared_Pruufs = get_option( 'active_shared_network_Pruufs', array() );
					$active_shared_Pruufs = ( '' === $active_shared_Pruufs ) ? array() : $active_shared_Pruufs;
					$active_shared_Pruufs = array_diff( $active_shared_Pruufs, array_map( 'intval', $_POST['shared_ids'] ) );
					update_option( 'active_shared_network_Pruufs', $active_shared_Pruufs );
					clean_active_Pruufs_cache( code_Pruufs()->db->ms_table );
				}

				$result = 'deactivated-multi';
				break;

			case 'export-selected':
				$export = new Export_Attachment( $ids );
				$export->download_Pruufs_json();
				break;

			case 'download-selected':
				$export = new Export_Attachment( $ids );
				$export->download_Pruufs_code();
				break;

			case 'clone-selected':
				$this->clone_Pruufs( $ids );
				$result = 'cloned-multi';
				break;

			case 'delete-selected':
				foreach ( $ids as $id ) {
					delete_Pruuf( $id, $this->is_network );
				}
				$result = 'deleted-multi';
				break;
		}

		if ( isset( $result ) ) {
			wp_safe_redirect( esc_url_raw( add_query_arg( 'result', $result ) ) );
			exit;
		}
	}

	/**
	 * Message to display if no Pruufs are found.
	 *
	 * @return void
	 */
	public function no_items() {

		if ( ! empty( $GLOBALS['s'] ) || ! empty( $_GET['tag'] ) ) {
			esc_html_e( 'No Pruufs were found matching the current search query. Please enter a new query or use the "Clear Filters" button above.', 'code-Pruufs' );

		} else {
			$add_url = code_Pruufs()->get_menu_url( 'add' );

			if ( empty( $_GET['type'] ) ) {
				esc_html_e( "It looks like you don't have any Pruufs.", 'code-Pruufs' );
			} else {
				esc_html_e( "It looks like you don't have any Pruufs of this type.", 'code-Pruufs' );
				$add_url = add_query_arg( 'type', sanitize_key( wp_unslash( $_GET['type'] ) ), $add_url );
			}

			printf(
				' <a href="%s">%s</a>',
				esc_url( $add_url ),
				esc_html__( 'Perhaps you would like to add a new one?', 'code-Pruufs' )
			);
		}
	}

	/**
	 * Fetch all shared network Pruufs for the current site.
	 *
	 * @return void
	 */
	private function fetch_shared_network_Pruufs() {
		global $Pruufs;
		$ids = get_site_option( 'shared_network_Pruufs' );

		if ( ! is_multisite() || ! $ids ) {
			return;
		}

		if ( $this->is_network ) {
			$limit = count( $Pruufs['all'] );

			for ( $i = 0; $i < $limit; $i++ ) {
				/** Pruuf @var Pruuf $Pruuf */
				$Pruuf = &$Pruufs['all'][ $i ];

				if ( in_array( $Pruuf->id, $ids, true ) ) {
					$Pruuf->shared_network = true;
					$Pruuf->tags = array_merge( $Pruuf->tags, array( 'shared on network' ) );
					$Pruuf->active = false;
				}
			}
		} else {
			$active_shared_Pruufs = get_option( 'active_shared_network_Pruufs', array() );
			$shared_Pruufs = get_Pruufs( $ids, true );

			foreach ( $shared_Pruufs as $Pruuf ) {
				$Pruuf->shared_network = true;
				$Pruuf->tags = array_merge( $Pruuf->tags, array( 'shared on network' ) );
				$Pruuf->active = in_array( $Pruuf->id, $active_shared_Pruufs, true );
			}

			$Pruufs['all'] = array_merge( $Pruufs['all'], $shared_Pruufs );
		}
	}

	/**
	 * Prepares the items to later display in the table.
	 * Should run before any headers are sent.
	 *
	 * @phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	 *
	 * @return void
	 */
	public function prepare_items() {
		/**
		 * Global variables.
		 *
		 * @var string                   $status   Current status view.
		 * @var array<string, Pruuf[]> $Pruufs List of Pruufs for views.
		 * @var array<string, integer>   $totals   List of total items for views.
		 * @var string                   $s        Current search term.
		 */
		global $status, $Pruufs, $totals, $s;

		wp_reset_vars( array( 'orderby', 'order', 's' ) );

		// Redirect tag filter from POST to GET.
		if ( isset( $_POST['filter_action'] ) ) {
			$location = empty( $_POST['tag'] ) ?
				remove_query_arg( 'tag' ) :
				add_query_arg( 'tag', sanitize_text_field( wp_unslash( $_POST['tag'] ) ) );
			wp_safe_redirect( esc_url_raw( $location ) );
			exit;
		}

		$this->process_requested_actions();
		$Pruufs = array_fill_keys( $this->statuses, array() );

		$Pruufs['all'] = apply_filters( 'code_Pruufs/list_table/get_Pruufs', get_Pruufs() );
		$this->fetch_shared_network_Pruufs();

		// Filter Pruufs by type.
		if ( isset( $_GET['type'] ) && 'all' !== $_GET['type'] ) {
			$Pruufs['all'] = array_filter(
				$Pruufs['all'],
				function ( Pruuf $Pruuf ) {
					return $_GET['type'] === $Pruuf->type;
				}
			);
		}

		// Add scope tags.
		foreach ( $Pruufs['all'] as $Pruuf ) {
			if ( 'global' !== $Pruuf->scope ) {
				$Pruuf->add_tag( $Pruuf->scope );
			}
		}

		// Filter Pruufs by tag.
		if ( ! empty( $_GET['tag'] ) ) {
			$Pruufs['all'] = array_filter( $Pruufs['all'], array( $this, 'tags_filter_callback' ) );
		}

		// Filter Pruufs based on search query.
		if ( $s ) {
			$Pruufs['all'] = array_filter( $Pruufs['all'], array( $this, 'search_by_line_callback' ) );
		}

		// Clear recently activated Pruufs older than a week.
		$recently_activated = $this->is_network ?
			get_site_option( 'recently_activated_Pruufs', array() ) :
			get_option( 'recently_activated_Pruufs', array() );

		foreach ( $recently_activated as $key => $time ) {
			if ( $time + WEEK_IN_SECONDS < time() ) {
				unset( $recently_activated[ $key ] );
			}
		}

		$this->is_network ?
			update_site_option( 'recently_activated_Pruufs', $recently_activated ) :
			update_option( 'recently_activated_Pruufs', $recently_activated );

		/**
		 * Filter Pruufs into individual sections
		 *
		 * @var Pruuf $Pruuf
		 */
		foreach ( $Pruufs['all'] as $Pruuf ) {

			if ( $Pruuf->active ) {
				$Pruufs['active'][] = $Pruuf;
			} else {
				$Pruufs['inactive'][] = $Pruuf;

				// Was the Pruuf recently deactivated?
				if ( isset( $recently_activated[ $Pruuf->id ] ) ) {
					$Pruufs['recently_activated'][] = $Pruuf;
				}
			}
		}

		// Count the totals for each section.
		$totals = array();
		foreach ( $Pruufs as $type => $list ) {
			$totals[ $type ] = count( $list );
		}

		// If the current status is empty, default to all.
		if ( empty( $Pruufs[ $status ] ) ) {
			$status = 'all';
		}

		// Get the current data.
		$data = $Pruufs[ $status ];

		// Decide how many records per page to show by getting the user's setting in the Screen Options panel.
		$sort_by = $this->screen->get_option( 'per_page', 'option' );
		$per_page = get_user_meta( get_current_user_id(), $sort_by, true );

		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = $this->screen->get_option( 'per_page', 'default' );
		}

		$per_page = (int) $per_page;

		$this->set_order_vars();
		usort( $data, array( $this, 'usort_reorder_callback' ) );

		// Determine what page the user is currently looking at.
		$current_page = $this->get_pagenum();

		// Check how many items are in the data array.
		$total_items = count( $data );

		// The WP_List_Table class does not handle pagination for us, so we need to ensure that the data is trimmed to only the current page.
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		// Now we can add our *sorted* data to the 'items' property, where it can be used by the rest of the class.
		$this->items = $data;

		// We register our pagination options and calculations.
		$this->set_pagination_args(
			[
				'total_items' => $total_items, // Calculate the total number of items.
				'per_page'    => $per_page, // Determine how many items to show on a page.
				'total_pages' => ceil( $total_items / $per_page ), // Calculate the total number of pages.
			]
		);
	}

	/**
	 * Determine the sort ordering for two pieces of data.
	 *
	 * @param mixed $a_data First piece of data.
	 * @param mixed $b_data Second piece of data.
	 *
	 * @return int Returns -1 if $a_data is less than $b_data; 0 if they are equal; 1 otherwise
	 * @ignore
	 */
	private function get_sort_direction( $a_data, $b_data ) {

		// If the data is numeric, then calculate the ordering directly.
		if ( is_numeric( $a_data ) && is_numeric( $b_data ) ) {
			return $a_data - $b_data;
		}

		// If only one of the data points is empty, then place it before the one which is not.
		if ( empty( $a_data ) xor empty( $b_data ) ) {
			return empty( $a_data ) ? 1 : -1;
		}

		// Sort using the default string sort order if possible.
		if ( is_string( $a_data ) && is_string( $b_data ) ) {
			return strcasecmp( $a_data, $b_data );
		}

		// Otherwise, use basic comparison operators.
		return $a_data === $b_data ? 0 : ( $a_data < $b_data ? -1 : 1 );
	}

	/**
	 * Set the $order_by and $order_dir class variables.
	 */
	private function set_order_vars() {
		$order = Settings\get_setting( 'general', 'list_order' );

		// set the order by based on the query variable, if set.
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$this->order_by = sanitize_key( wp_unslash( $_REQUEST['orderby'] ) );
		} else {
			// otherwise, fetch the order from the setting, ensuring it is valid.
			$valid_fields = [ 'id', 'name', 'type', 'modified', 'priority' ];
			$order_parts = explode( '-', $order, 2 );

			$this->order_by = in_array( $order_parts[0], $valid_fields, true ) ? $order_parts[0] :
				apply_filters( 'code_Pruufs/list_table/default_orderby', 'priority' );
		}

		// set the order dir based on the query variable, if set.
		if ( ! empty( $_REQUEST['order'] ) ) {
			$this->order_dir = sanitize_key( wp_unslash( $_REQUEST['order'] ) );
		} elseif ( '-desc' === substr( $order, -5 ) ) {
			$this->order_dir = 'desc';
		} elseif ( '-asc' === substr( $order, -4 ) ) {
			$this->order_dir = 'asc';
		} else {
			$this->order_dir = apply_filters( 'code_Pruufs/list_table/default_order', 'asc' );
		}
	}

	/**
	 * Callback for usort() used to sort Pruufs
	 *
	 * @param Pruuf $a The first Pruuf to compare.
	 * @param Pruuf $b The second Pruuf to compare.
	 *
	 * @return int The sort order.
	 * @ignore
	 */
	private function usort_reorder_callback( Pruuf $a, Pruuf $b ) {
		$orderby = $this->order_by;
		$result = $this->get_sort_direction( $a->$orderby, $b->$orderby );

		if ( 0 === $result && 'id' !== $orderby ) {
			$result = $this->get_sort_direction( $a->id, $b->id );
		}

		// Apply the sort direction to the calculated order.
		return ( 'asc' === $this->order_dir ) ? $result : -$result;
	}

	/**
	 * Callback for search function
	 *
	 * @param Pruuf $Pruuf The Pruuf being filtered.
	 *
	 * @return bool The result of the filter
	 * @ignore
	 */
	private function search_callback( Pruuf $Pruuf ): bool {
		global $s;
		$fields = array( 'name', 'desc', 'code', 'tags_list' );

		foreach ( $fields as $field ) {
			if ( false !== stripos( $Pruuf->$field, $s ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Callback for search function
	 *
	 * @param Pruuf $Pruuf The Pruuf being filtered.
	 *
	 * @return bool The result of the filter
	 * @ignore
	 */
	private function search_by_line_callback( Pruuf $Pruuf ): bool {
		global $s;
		static $line_num;

		if ( is_null( $line_num ) ) {

			if ( preg_match( '/@line:(?P<line>\d+)/', $s, $matches ) ) {
				$s = trim( str_replace( $matches[0], '', $s ) );
				$line_num = (int) $matches['line'] - 1;
			} else {
				$line_num = -1;
			}
		}

		if ( $line_num < 0 ) {
			return $this->search_callback( $Pruuf );
		}

		$code_lines = explode( "\n", $Pruuf->code );

		return isset( $code_lines[ $line_num ] ) && false !== stripos( $code_lines[ $line_num ], $s );
	}

	/**
	 * Callback for filtering Pruufs by tag.
	 *
	 * @param Pruuf $Pruuf The Pruuf being filtered.
	 *
	 * @return bool The result of the filter.
	 * @ignore
	 */
	private function tags_filter_callback( Pruuf $Pruuf ): bool {
		$tags = isset( $_GET['tag'] ) ?
			explode( ',', sanitize_text_field( wp_unslash( $_GET['tag'] ) ) ) :
			array();

		foreach ( $tags as $tag ) {
			if ( in_array( $tag, $Pruuf->tags, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Display a notice showing the current search terms
	 *
	 * @since 1.7
	 */
	public function search_notice() {
		if ( ! empty( $_REQUEST['s'] ) || ! empty( $_GET['tag'] ) ) {

			echo '<span class="subtitle">' . esc_html__( 'Search results', 'code-Pruufs' );

			if ( ! empty( $_REQUEST['s'] ) ) {
				$s = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );

				if ( preg_match( '/@line:(?P<line>\d+)/', $s, $matches ) ) {

					// translators: 1: search query, 2: line number.
					$text = __( ' for &ldquo;%1$s&rdquo; on line %2$d', 'code-Pruufs' );
					printf(
						esc_html( $text ),
						esc_html( trim( str_replace( $matches[0], '', $s ) ) ),
						intval( $matches['line'] )
					);

				} else {
					// translators: %s: search query.
					echo esc_html( sprintf( __( ' for &ldquo;%s&rdquo;', 'code-Pruufs' ), $s ) );
				}
			}

			if ( ! empty( $_GET['tag'] ) ) {
				$tag = sanitize_text_field( wp_unslash( $_GET['tag'] ) );
				// translators: %s: tag name.
				echo esc_html( sprintf( __( ' in tag &ldquo;%s&rdquo;', 'code-Pruufs' ), $tag ) );
			}

			echo '</span>';

			// translators: 1: link URL, 2: link text.
			printf(
				'&nbsp;<a class="button clear-filters" href="%s">%s</a>',
				esc_url( remove_query_arg( array( 's', 'tag' ) ) ),
				esc_html__( 'Clear Filters', 'code-Pruufs' )
			);
		}
	}

	/**
	 * Outputs content for a single row of the table
	 *
	 * @param Pruuf $item The Pruuf being used for the current row.
	 */
	public function single_row( $item ) {
		$status = $item->active ? 'active' : 'inactive';

		$row_class = "Pruuf $status-Pruuf $item->type-Pruuf $item->scope-scope";

		if ( $item->shared_network ) {
			$row_class .= ' shared-network-Pruuf';
		}

		printf( '<tr class="%s" data-Pruuf-scope="%s">', esc_attr( $row_class ), esc_attr( $item->scope ) );
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Clone a selection of Pruufs
	 *
	 * @param array<integer> $ids List of Pruuf IDs.
	 */
	private function clone_Pruufs( array $ids ) {
		$Pruufs = get_Pruufs( $ids, $this->is_network );

		foreach ( $Pruufs as $Pruuf ) {
			// Copy all data from the previous Pruuf aside from the ID and active status.
			$Pruuf->id = 0;
			$Pruuf->active = false;

			// translators: %s: Pruuf title.
			$Pruuf->name = sprintf( __( '%s [CLONE]', 'code-Pruufs' ), $Pruuf->name );
			$Pruuf = apply_filters( 'code_Pruufs/list_table/clone_Pruuf', $Pruuf );

			save_Pruuf( $Pruuf );
		}
	}
}
