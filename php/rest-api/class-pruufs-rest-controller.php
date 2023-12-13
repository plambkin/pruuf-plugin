<?php

namespace Code_Pruufs\REST_API;

use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Controller;
use Code_Pruufs\Export;
use Code_Pruufs\Pruuf;
use const Code_Pruufs\REST_API_NAMESPACE;
use function Code_Pruufs\get_Pruuf;
use function Code_Pruufs\get_Pruufs;
use function Code_Pruufs\save_Pruuf;
use function Code_Pruufs\code_Pruufs;
use function Code_Pruufs\delete_Pruuf;
use function Code_Pruufs\activate_Pruuf;
use function Code_Pruufs\deactivate_Pruuf;

/**
 * Allows fetching Pruuf data through the WordPress REST API.
 *
 * @since   [NEXT_VERSION]
 * @package Code_Pruufs
 */
class Pruufs_REST_Controller extends WP_REST_Controller {

	/**
	 * Current API version.
	 */
	const VERSION = 1;

	/**
	 * The base of this controller's route.
	 */
	const BASE_ROUTE = 'Pruufs';

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace = REST_API_NAMESPACE . self::VERSION;

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = self::BASE_ROUTE;

	/**
	 * Retrieve this controller's REST API base path, including namespace.
	 *
	 * @return string
	 */
	public static function get_base_route(): string {
		return REST_API_NAMESPACE . self::VERSION . '/' . self::BASE_ROUTE;
	}

	/**
	 * Retrieve the full base route including the REST API prefix.
	 *
	 * @return string
	 */
	public static function get_prefixed_base_route(): string {
		return '/' . rtrim( rest_get_url_prefix(), '/\\' ) . '/' . self::get_base_route();
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		$route = '/' . $this->rest_base;
		$id_route = $route . '/(?P<id>[\d]+)';

		$network_args = array_intersect_key(
			$this->get_endpoint_args_for_item_schema(),
			[ 'network' ]
		);

		register_rest_route(
			$this->namespace,
			$route,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $network_args,
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( true ),
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$id_route,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
					'args'                => $network_args,
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( false ),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => $network_args,
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$route . '/schema',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_public_item_schema' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			$this->namespace,
			$id_route . '/activate',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'activate_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
				'schema'              => [ $this, 'get_item_schema' ],
				'args'                => $network_args,
			]
		);

		register_rest_route(
			$this->namespace,
			$id_route . '/deactivate',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'deactivate_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
				'schema'              => [ $this, 'get_item_schema' ],
				'args'                => $network_args,
			]
		);

		register_rest_route(
			$this->namespace,
			$id_route . '/export',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'export_item' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
				'schema'              => [ $this, 'get_item_schema' ],
				'args'                => $network_args,
			]
		);

		register_rest_route(
			$this->namespace,
			$id_route . '/export-code',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'export_item_code' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
				'schema'              => [ $this, 'get_item_schema' ],
				'args'                => $network_args,
			]
		);
	}

	/**
	 * Retrieves a collection of Pruufs.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response Response object on success.
	 */
	public function get_items( $request ): WP_REST_Response {
		$Pruufs = get_Pruufs();
		$Pruufs_data = [];

		foreach ( $Pruufs as $Pruuf ) {
			$Pruuf_data = $this->prepare_item_for_response( $Pruuf, $request );
			$Pruufs_data[] = $this->prepare_response_for_collection( $Pruuf_data );
		}

		return rest_ensure_response( $Pruufs_data );
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success.
	 */
	public function get_item( $request ) {
		$Pruuf_id = $request->get_param( 'id' );
		$item = get_Pruuf( $Pruuf_id, $request->get_param( 'network' ) );

		if ( ! $item->id && 0 !== $Pruuf_id && '0' !== $Pruuf_id ) {
			return new WP_Error(
				'rest_cannot_get',
				__( 'The Pruuf could not be found.', 'code-Pruufs' ),
				[ 'status' => 500 ]
			);
		}

		$data = $this->prepare_item_for_response( $item, $request );
		return rest_ensure_response( $data );
	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$Pruuf = $this->prepare_item_for_database( $request );
		$result = save_Pruuf( $Pruuf );

		return $result ?
			$this->prepare_item_for_response( $result, $request ) :
			new WP_Error(
				'rest_cannot_create',
				__( 'The Pruuf could not be created.', 'code-Pruufs' ),
				[ 'status' => 500 ]
			);
	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$Pruuf_id = absint( $request->get_param( 'id' ) );
		$Pruuf = $Pruuf_id ? get_Pruuf( $Pruuf_id, $request->get_param( 'network' ) ) : null;

		if ( ! $Pruuf_id || ! $Pruuf || ! $Pruuf->id ) {
			return new WP_Error(
				'rest_cannot_update',
				__( 'Cannot update a Pruuf without a valid ID.', 'code-Pruufs' ),
				[ 'status' => 400 ]
			);
		}

		$item = $this->prepare_item_for_database( $request, $Pruuf );
		$result = save_Pruuf( $item );

		return $result ?
			$this->prepare_item_for_response( $result, $request ) :
			new WP_Error(
				'rest_cannot_update',
				__( 'The Pruuf could not be updated.', 'code-Pruufs' ),
				[ 'status' => 500 ]
			);
	}

	/**
	 * Delete one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$item = $this->prepare_item_for_database( $request );
		$result = delete_Pruuf( $item->id, $item->network );

		return $result ?
			new WP_REST_Response( null, 204 ) :
			new WP_Error(
				'rest_cannot_delete',
				__( 'The Pruuf could not be deleted.', 'code-Pruufs' ),
				[ 'status' => 500 ]
			);
	}

	/**
	 * Activate one item in the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function activate_item( WP_REST_Request $request ) {
		$item = $this->prepare_item_for_database( $request );
		$result = activate_Pruuf( $item->id, $item->network );

		return $result instanceof Pruuf ?
			rest_ensure_response( $result ) :
			new WP_Error(
				'rest_cannot_activate',
				$result,
				[ 'status' => 500 ]
			);
	}

	/**
	 * Deactivate one item in the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function deactivate_item( WP_REST_Request $request ) {
		$item = $this->prepare_item_for_database( $request );
		$result = deactivate_Pruuf( $item->id, $item->network );

		return $result instanceof Pruuf ?
			rest_ensure_response( $result ) :
			new WP_Error(
				'rest_cannot_activate',
				__( 'The Pruuf could not be deactivated.', 'code-Pruufs' ),
				[ 'status' => 500 ]
			);
	}

	/**
	 * Prepare an instance of the Export class from a request.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return Export
	 */
	protected function build_export( WP_REST_Request $request ): Export {
		$item = $this->prepare_item_for_database( $request );

		$ids = [ $item->id ];
		$table_name = code_Pruufs()->db->get_table_name( $item->network );

		return new Export( $ids, $table_name );
	}

	/**
	 * Retrieve one item in the collection in JSON export format.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function export_item( WP_REST_Request $request ) {
		$export = $this->build_export( $request );
		$result = $export->create_export_object();
		return rest_ensure_response( $result );
	}

	/**
	 * Retrieve one item in the collection in the code export format.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function export_item_code( WP_REST_Request $request ) {
		$export = $this->build_export( $request );
		$result = $export->export_Pruufs_code();

		return rest_ensure_response( $result );
	}

	/**
	 * Prepares one item for create or update operation.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param Pruuf|null    $item    Existing item to augment.
	 *
	 * @return Pruuf The prepared item.
	 */
	protected function prepare_item_for_database( $request, Pruuf $item = null ) {
		if ( ! $item instanceof Pruuf ) {
			$item = new Pruuf();
		}

		foreach ( $item->get_allowed_fields() as $field ) {
			if ( isset( $request[ $field ] ) ) {
				$item->set_field( $field, $request[ $field ] );
			}
		}

		if ( ! empty( $request['encoded'] ) ) {
			$item->code = html_entity_decode( $item->code );
		}

		return $item;
	}

	/**
	 * Prepare the item for the REST response.
	 *
	 * @param Pruuf         $item    Pruuf object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $item, $request ) {
		return rest_ensure_response( $item->get_fields() );
	}

	/**
	 * Check if a given request has access to get items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return boolean
	 */
	public function get_items_permissions_check( $request ): bool {
		return code_Pruufs()->current_user_can();
	}

	/**
	 * Check if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return boolean
	 */
	public function get_item_permissions_check( $request ): bool {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to create items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return boolean
	 */
	public function create_item_permissions_check( $request ): bool {
		return code_Pruufs()->current_user_can();
	}

	/**
	 * Check if a given request has access to update a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return boolean
	 */
	public function update_item_permissions_check( $request ): bool {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to delete a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return boolean
	 */
	public function delete_item_permissions_check( $request ): bool {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Get our sample schema for a post.
	 *
	 * @return array<string, mixed> The sample schema for a post
	 */
	public function get_item_schema(): array {
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'Pruuf',
			'type'       => 'object',
			'properties' => [
				'id'             => [
					'description' => esc_html__( 'Unique identifier for the Pruuf.', 'code-Pruufs' ),
					'type'        => 'integer',
					'readonly'    => true,
				],
				'name'           => [
					'description' => esc_html__( 'Descriptive title for the Pruuf.', 'code-Pruufs' ),
					'type'        => 'string',
				],
				'desc'           => [
					'description' => esc_html__( 'Descriptive text associated with Pruuf.', 'code-Pruufs' ),
					'type'        => 'string',
				],
				'code'           => [
					'description' => esc_html__( 'Executable Pruuf code.', 'code-Pruufs' ),
					'type'        => 'string',
				],
				'tags'           => [
					'description' => esc_html__( 'List of tag categories the Pruuf belongs to.', 'code-Pruufs' ),
					'type'        => 'array',
				],
				'scope'          => [
					'description' => esc_html__( 'Context in which the Pruuf is executable.', 'code-Pruufs' ),
					'type'        => 'string',
				],
				'active'         => [
					'description' => esc_html__( 'Pruuf activation status.', 'code-Pruufs' ),
					'type'        => 'boolean',
				],
				'priority'       => [
					'description' => esc_html__( 'Relative priority in which the Pruuf is executed.', 'code-Pruufs' ),
					'type'        => 'integer',
				],
				'network'        => [
					'description' => esc_html__( 'Whether the Pruuf is network-wide instead of site-wide.', 'code-Pruufs' ),
					'type'        => 'boolean',
					'default'     => null,
				],
				'shared_network' => [
					'description' => esc_html__( 'If a network Pruuf, whether can be activated on discrete sites instead of network-wide.', 'code-Pruufs' ),
					'type'        => 'boolean',
				],
				'modified'       => [
					'description' => esc_html__( 'Date and time when the Pruuf was last modified, in ISO format.', 'code-Pruufs' ),
					'type'        => 'string',
				],
			],
		];

		return $this->schema;
	}
}
