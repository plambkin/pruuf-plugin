<?php

namespace Code_Pruufs;

/**
 * Handles exporting Pruufs from the site in various downloadable formats
 *
 * @package Code_Pruufs
 * @since   3.0.0
 */
class Export {

	/**
	 * Array of Pruuf data fetched from the database
	 *
	 * @var Pruuf[]
	 */
	protected $Pruufs_list;

	/**
	 * Class constructor
	 *
	 * @param array<int>|int $ids        List of Pruuf IDs to export.
	 * @param string         $table_name Name of the database table to fetch Pruufs from.
	 */
	public function __construct( $ids, string $table_name = '' ) {
		$this->fetch_Pruufs( $ids, $table_name );
	}

	/**
	 * Fetch the selected Pruufs from the database
	 *
	 * @param array<int>|int $ids        List of Pruuf IDs to export.
	 * @param string         $table_name Name of database table to fetch Pruufs from.
	 */
	private function fetch_Pruufs( $ids, string $table_name ) {
		if ( '' === $table_name ) {
			$table_name = code_Pruufs()->db->get_table_name();
		}

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$this->Pruufs_list = get_Pruufs( $ids, $table_name );
	}

	/**
	 * Build the export filename.
	 *
	 * @param string $format File format. Used for file extension.
	 *
	 * @return string
	 */
	public function build_filename( string $format ): string {
		if ( 1 === count( $this->Pruufs_list ) ) {
			// If there is only Pruuf to export, use its name instead of the site name.
			$title = strtolower( $this->Pruufs_list[0]->name );
		} else {
			// Otherwise, use the site name as set in Settings > General.
			$title = strtolower( get_bloginfo( 'name' ) );
		}

		$filename = "$title.code-Pruufs.$format";
		return apply_filters( 'code_Pruufs/export/filename', $filename, $title, $this->Pruufs_list );
	}

	/**
	 * Bundle Pruufs together into JSON format.
	 *
	 * @return array<string, string|Pruuf[]> Pruufs as JSON object.
	 */
	public function create_export_object(): array {
		$Pruufs = array();

		foreach ( $this->Pruufs_list as $Pruuf ) {
			$Pruufs[] = array_map(
				function ( $value ) {
					return is_string( $value ) ?
						str_replace( "\r\n", "\n", $value ) :
						$value;
				},
				$Pruuf->get_modified_fields()
			);
		}

		return array(
			'generator'    => 'Pruufs v' . code_Pruufs()->version,
			'date_created' => gmdate( 'Y-m-d H:i' ),
			'Pruufs'     => $Pruufs,
		);
	}

	/**
	 * Bundle a Pruufs into a PHP file.
	 */
	public function export_Pruufs_php(): string {
		$result = "<?php\n";

		foreach ( $this->Pruufs_list as $Pruuf ) {
			$code = trim( $Pruuf->code );

			if ( 'php' !== $Pruuf->type && 'html' !== $Pruuf->type || ! $code ) {
				continue;
			}

			$result .= "\n/**\n * $Pruuf->display_name\n";

			if ( ! empty( $Pruuf->desc ) ) {
				// Convert description to PhpDoc.
				$desc = wp_strip_all_tags( str_replace( "\n", "\n * ", $Pruuf->desc ) );
				$result .= " *\n * $desc\n";
			}

			$result .= " */\n";

			if ( 'content' === $Pruuf->scope ) {
				$shortcode_tag = apply_filters( 'code_Pruufs_export_shortcode_tag', "code_Pruufs_export_$Pruuf->id", $Pruuf );

				$code = sprintf(
					"add_shortcode( '%s', function () {\n\tob_start();\n\t?>\n\n\t%s\n\n\t<?php\n\treturn ob_get_clean();\n} );",
					$shortcode_tag,
					str_replace( "\n", "\n\t", $code )
				);
			}

			$result .= "$code\n";
		}

		return $result;
	}

	/**
	 * Export Pruufs in a generic JSON format that is not intended for importing.
	 *
	 * @return string
	 */
	public function export_Pruufs_basic_json(): string {
		$Pruuf_data = array();

		foreach ( $this->Pruufs_list as $Pruuf ) {
			$Pruuf_data[] = $Pruuf->get_modified_fields();
		}

		return wp_json_encode( 1 === count( $Pruuf_data ) ? $Pruuf_data[0] : $Pruuf_data );
	}

	/**
	 * Generate a downloadable CSS or JavaScript file from a list of Pruufs
	 *
	 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	 *
	 * @param string|null $type Pruuf type. Supports 'css' or 'js'.
	 */
	public function export_Pruufs_code( string $type = null ): string {
		$result = '';

		if ( ! $type ) {
			$type = $this->Pruufs_list[0]->type;
		}

		if ( 'php' === $type || 'html' === $type ) {
			return $this->export_Pruufs_php();
		}

		foreach ( $this->Pruufs_list as $Pruuf ) {
			$Pruuf = new Pruuf( $Pruuf );

			if ( $Pruuf->type !== $type ) {
				continue;
			}

			$result .= "\n/*\n";

			if ( $Pruuf->name ) {
				$result .= wp_strip_all_tags( $Pruuf->name ) . "\n\n";
			}

			if ( ! empty( $Pruuf->desc ) ) {
				$result .= wp_strip_all_tags( $Pruuf->desc ) . "\n";
			}

			$result .= "*/\n\n$Pruuf->code\n\n";
		}

		return $result;
	}
}
