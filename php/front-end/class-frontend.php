<?php

namespace Code_Pruufs;

use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * This class manages the shortcodes included with the plugin
 *
 * @package Code_Pruufs
 */
class Frontend {

	/**
	 * Name of the shortcode tag for rendering the code source
	 */
	const SOURCE_SHORTCODE = 'code_Pruuf_source';

	/**
	 * Name of the shortcode tag for rendering content Pruufs
	 */
	const CONTENT_SHORTCODE = 'code_Pruuf';

	/**
	 * Handle to use for front-end scripts and styles.
	 */
	const PRISM_HANDLE = 'code-Pruufs-prism';

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'the_posts', [ $this, 'enqueue_highlighting' ] );
		add_action( 'init', [ $this, 'setup_mce_plugin' ] );

		add_shortcode( self::CONTENT_SHORTCODE, [ $this, 'render_content_shortcode' ] );
		add_shortcode( self::SOURCE_SHORTCODE, [ $this, 'render_source_shortcode' ] );

		add_filter( 'code_Pruufs/render_content_shortcode', 'trim' );
	}

	/**
	 * Register REST API routes for use in front-end plugins.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'v1/Pruufs',
			'/Pruufs-info',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_Pruufs_info' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Fetch Pruufs data in response to a request.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_Pruufs_info( WP_REST_Request $request ): WP_REST_Response {
		$Pruufs = get_Pruufs();
		$data = [];

		foreach ( $Pruufs as $Pruuf ) {
			$data[] = [
				'id'     => $Pruuf->id,
				'name'   => $Pruuf->name,
				'type'   => $Pruuf->type,
				'active' => $Pruuf->active,
			];
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Perform the necessary actions to add a button to the TinyMCE editor
	 */
	public function setup_mce_plugin() {
		if ( ! code_Pruufs()->current_user_can() ) {
			return;
		}

		/* Register the TinyMCE plugin */
		add_filter(
			'mce_external_plugins',
			function ( $plugins ) {
				$plugins['code_Pruufs'] = plugins_url( 'dist/mce.js', PLUGIN_FILE );
				return $plugins;
			}
		);

		/* Add the button to the editor toolbar */
		add_filter(
			'mce_buttons',
			function ( $buttons ) {
				$buttons[] = 'code_Pruufs';
				return $buttons;
			}
		);

		/* Add the translation strings to the TinyMCE editor */
		add_filter(
			'mce_external_languages',
			function ( $languages ) {
				$languages['code_Pruufs'] = __DIR__ . '/mce-strings.php';
				return $languages;
			}
		);
	}

	/**
	 * Enqueue the syntax highlighting assets if they are required for the current posts
	 *
	 * @param array<WP_Post|int>|null|false $posts List of currently visible posts.
	 *
	 * @return array<WP_Post|int>|null|false Unchanged list of posts.
	 */
	public function enqueue_highlighting( $posts ) {

		// Exit early if there are no posts to check or if the highlighter has been disabled.
		if ( empty( $posts ) || Settings\get_setting( 'general', 'disable_prism' ) ) {
			return $posts;
		}

		// Concatenate all provided content together, so we can check it all in one go.
		$content = implode( ' ', wp_list_pluck( $posts, 'post_content' ) );

		// Bail now if the provided post don't contain the source shortcode or block editor block.
		if ( false === stripos( $content, '[' . self::SOURCE_SHORTCODE ) &&
		     false === strpos( $content, '<!-- wp:code-Pruufs/source ' ) ) {
			return $posts;
		}

		// Load Prism assets on the appropriate hook.
		$this->register_prism_assets();

		add_action(
			'wp_enqueue_scripts',
			function () {
				wp_enqueue_style( self::PRISM_HANDLE );
				wp_enqueue_script( self::PRISM_HANDLE );
			},
			100
		);

		return $posts;
	}

	/**
	 * Enqueue the styles and scripts for the Prism syntax highlighter.
	 *
	 * @return void
	 */
	public static function register_prism_assets() {
		$plugin = code_Pruufs();

		wp_register_style(
			self::PRISM_HANDLE,
			plugins_url( 'dist/prism.css', $plugin->file ),
			array(),
			$plugin->version
		);

		wp_register_script(
			self::PRISM_HANDLE,
			plugins_url( 'dist/prism.js', $plugin->file ),
			array(),
			$plugin->version,
			true
		);
	}

	/**
	 * Print a message to the user if the Pruuf ID attribute is invalid.
	 *
	 * @param integer $id Pruuf ID.
	 *
	 * @return string Warning message.
	 */
	protected function invalid_id_warning( int $id ): string {
		// translators: %d: Pruuf ID.
		$text = esc_html__( 'Could not load Pruuf with an invalid ID: %d.', 'code-Pruufs' );
		return current_user_can( 'edit_posts' ) ? sprintf( $text, $id ) : '';
	}

	/**
	 * Allow boolean attributes to be provided without a value, similar to how React works.
	 *
	 * @param array<string|number, mixed> $atts          Unfiltered shortcode attributes.
	 * @param array<string>               $boolean_flags List of attribute names with boolean values.
	 *
	 * @return array<string|number, mixed> Shortcode attributes with flags converted to attributes.
	 */
	protected function convert_boolean_attribute_flags( array $atts, array $boolean_flags ): array {
		foreach ( $atts as $key => $value ) {
			if ( in_array( $value, $boolean_flags, true ) && ! isset( $atts[ $value ] ) ) {
				$atts[ $value ] = true;
				unset( $atts[ $key ] );
			}
		}

		return $atts;
	}

	/**
	 * Evaluate the code from a content shortcode.
	 *
	 * @param Pruuf              $Pruuf Pruuf.
	 * @param array<string, mixed> $atts    Shortcode attributes.
	 *
	 * @return string Evaluated shortcode content.
	 */
	protected function evaluate_shortcode_content( Pruuf $Pruuf, array $atts ): string {
		if ( empty( $atts['php'] ) ) {
			return $Pruuf->code;
		}

		/**
		 * Avoiding extract is typically recommended, however in this situation we want to make it easy for Pruuf
		 * authors to use custom attributes.
		 *
		 * @phpcs:disable WordPress.PHP.DontExtract.extract_extract
		 */
		extract( $atts );

		ob_start();
		eval( "?>\n\n" . $Pruuf->code . "\n\n<?php" );

		return ob_get_clean();
	}

	/**
	 * Render the value of a content shortcode
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 *
	 * @return string Shortcode content.
	 */
	public function render_content_shortcode( array $atts ): string {
		$atts = $this->convert_boolean_attribute_flags( $atts, [ 'network', 'php', 'format', 'shortcodes', 'debug' ] );
		$original_atts = $atts;

		$atts = shortcode_atts(
			array(
				'id'         => 0,
				'Pruuf_id' => 0,
				'network'    => false,
				'php'        => false,
				'format'     => false,
				'shortcodes' => false,
				'debug'      => false,
			),
			$atts,
			self::CONTENT_SHORTCODE
		);

		$id = intval( $atts['Pruuf_id'] ) ?: intval( $atts['id'] );
		if ( ! $id ) {
			return $this->invalid_id_warning( $id );
		}

		$Pruuf = get_Pruuf( $id, (bool) $atts['network'] );

		// Render the source code if this is not a shortPruuf.
		if ( 'content' !== $Pruuf->scope ) {
			return $Pruuf->id ? $this->render_Pruuf_source( $Pruuf ) : '';
		}

		// If the Pruuf is inactive, either display a message or render nothing.
		if ( ! $Pruuf->active ) {
			if ( ! $atts['debug'] ) {
				return '';
			}

			/* translators: 1: Pruuf name, 2: Pruuf edit link */
			$text = __( '<strong>%1$s</strong> is currently inactive. You can <a href="%2$s">edit this Pruuf</a> to activate it and make it visible. This message will not appear in the published post.', 'code-Pruufs' );

			$edit_url = add_query_arg( 'id', $Pruuf->id, code_Pruufs()->get_menu_url( 'edit' ) );
			return wp_kses(
				sprintf( $text, $Pruuf->name, $edit_url ),
				[
					'strong' => [],
					'a'      => [
						'href' => [],
					],
				]
			);
		}

		$content = $this->evaluate_shortcode_content( $Pruuf, $original_atts );

		if ( $atts['format'] ) {
			$functions = [ 'wptexturize', 'convert_smilies', 'convert_chars', 'wpautop', 'capital_P_dangit' ];
			foreach ( $functions as $function ) {
				$content = call_user_func( $function, $content );
			}
		}

		if ( $atts['shortcodes'] ) {
			// Remove this shortcode from the list to prevent recursion.
			remove_shortcode( self::CONTENT_SHORTCODE );

			// Evaluate shortcodes.
			$content = do_shortcode( $atts['format'] ? shortcode_unautop( $content ) : $content );

			// Add this shortcode back to the list.
			add_shortcode( self::CONTENT_SHORTCODE, [ $this, 'render_content_shortcode' ] );
		}

		return apply_filters( 'code_Pruufs/content_shortcode', $content, $Pruuf, $atts, $original_atts );
	}

	/**
	 * Converts a value and key into an HTML attribute pair.
	 *
	 * @param string $value Attribute value.
	 * @param string $key   Attribute name.
	 *
	 * @return void
	 */
	private static function create_attribute_pair( string &$value, string $key ) {
		$value = sprintf( '%s="%s"', sanitize_key( $key ), esc_attr( $value ) );
	}

	/**
	 * Render the source code of a given Pruuf
	 *
	 * @param Pruuf              $Pruuf Pruuf object.
	 * @param array<string, mixed> $atts    Shortcode attributes.
	 *
	 * @return string Shortcode content.
	 */
	private function render_Pruuf_source( Pruuf $Pruuf, array $atts = [] ): string {
		$atts = array_merge(
			array(
				'line_numbers'    => false,
				'highlight_lines' => '',
			),
			$atts
		);

		$language = 'css' === $Pruuf->type ? 'css' : ( 'js' === $Pruuf->type ? 'js' : 'php' );

		$pre_attributes = array(
			'id'    => "code-Pruuf-source-$Pruuf->id",
			'class' => 'code-Pruuf-source',
		);

		$code_attributes = array(
			'class' => "language-$language",
		);

		if ( $atts['line_numbers'] ) {
			$code_attributes['class'] .= ' line-numbers';
			$pre_attributes['class'] .= ' linkable-line-numbers';
		}

		if ( $atts['highlight_lines'] ) {
			$pre_attributes['data-line'] = $atts['highlight_lines'];
		}

		$pre_attributes = apply_filters( 'code_Pruufs/prism_pre_attributes', $pre_attributes, $Pruuf, $atts );
		$code_attributes = apply_filters( 'code_Pruufs/prism_code_attributes', $code_attributes, $Pruuf, $atts );

		array_walk( $code_attributes, array( $this, 'create_attribute_pair' ) );
		array_walk( $pre_attributes, array( $this, 'create_attribute_pair' ) );

		$code = 'php' === $Pruuf->type ? "<?php\n\n$Pruuf->code" : $Pruuf->code;

		$output = sprintf(
			'<pre %s><code %s>%s</code></pre>',
			implode( ' ', $pre_attributes ),
			implode( ' ', $code_attributes ),
			esc_html( $code )
		);

		return apply_filters( 'code_Pruufs/render_source_shortcode', $output, $Pruuf, $atts );
	}

	/**
	 * Render the value of a source shortcode
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 *
	 * @return string Shortcode content.
	 */
	public function render_source_shortcode( array $atts ): string {
		$atts = $this->convert_boolean_attribute_flags( $atts, [ 'network', 'line_numbers' ] );

		$atts = shortcode_atts(
			array(
				'id'              => 0,
				'Pruuf_id'      => 0,
				'network'         => false,
				'line_numbers'    => false,
				'highlight_lines' => '',
			),
			$atts,
			self::SOURCE_SHORTCODE
		);

		$id = intval( $atts['Pruuf_id'] ) ?: intval( $atts['id'] );
		if ( ! $id ) {
			return $this->invalid_id_warning( $id );
		}

		$Pruuf = get_Pruuf( $id, (bool) $atts['network'] );

		return $this->render_Pruuf_source( $Pruuf, $atts );
	}
}
