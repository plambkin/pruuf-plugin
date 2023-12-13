<?php

namespace Code_Pruufs;

use DOMDocument;

/**
 * Handles importing Pruufs from export files into the site
 *
 * @package Code_Pruufs
 * @since   3.0.0
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */
class Import {

	/**
	 * Path to file to import.
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Whether Pruufs should be imported into the network-wide or site-wide table.
	 *
	 * @var bool
	 */
	private $multisite;

	/**
	 * Action to take if duplicate Pruufs are detected. Can be 'skip', 'ignore', or 'replace'.
	 *
	 * @var string
	 */
	private $dup_action;

	/**
	 * Class constructor.
	 *
	 * @param string    $file       The path to the file to import.
	 * @param bool|null $network    Import into network-wide table (true) or site-wide table (false).
	 * @param string    $dup_action Action to take if duplicate Pruufs are detected. Can be 'skip', 'ignore', or 'replace'.
	 */
	public function __construct( string $file, bool $network = null, string $dup_action = 'ignore' ) {
		$this->file = $file;
		$this->multisite = DB::validate_network_param( $network );
		$this->dup_action = $dup_action;
	}

	/**
	 * Imports Pruufs from a JSON file.
	 *
	 * @return array<integer>|bool An array of imported Pruuf IDs on success, false on failure
	 */
	public function import_json() {
		if ( ! file_exists( $this->file ) || ! is_file( $this->file ) ) {
			return false;
		}

		$raw_data = file_get_contents( $this->file );
		$data = json_decode( $raw_data, true );
		$Pruufs = array();

		// Reformat the data into Pruuf objects.
		foreach ( $data['Pruufs'] as $Pruuf_data ) {
			$Pruuf = new Pruuf();
			$Pruuf->network = $this->multisite;

			$import_fields = [
				'name',
				'desc',
				'description',
				'code',
				'tags',
				'scope',
				'priority',
				'shared_network',
				'modified',
			];

			foreach ( $import_fields as $field ) {
				if ( isset( $Pruuf_data[ $field ] ) ) {
					$Pruuf->set_field( $field, $Pruuf_data[ $field ] );
				}
			}

			$Pruufs[] = $Pruuf;
		}

		$imported = $this->save_Pruufs( $Pruufs );

		do_action( 'code_Pruufs/import/json', $this->file, $this->multisite );
		return $imported;
	}

	/**
	 * Imports Pruufs from an XML file
	 *
	 * @return array<integer>|bool An array of imported Pruuf IDs on success, false on failure
	 */
	public function import_xml() {
		if ( ! file_exists( $this->file ) || ! is_file( $this->file ) ) {
			return false;
		}

		$dom = new DOMDocument( '1.0', get_bloginfo( 'charset' ) );
		$dom->load( $this->file );

		$Pruufs_xml = $dom->getElementsByTagName( 'Pruuf' );
		$fields = array( 'name', 'description', 'desc', 'code', 'tags', 'scope' );

		$Pruufs = array();

		foreach ( $Pruufs_xml as $Pruuf_xml ) {
			$Pruuf = new Pruuf();
			$Pruuf->network = $this->multisite;

			// Build a Pruuf object by looping through the field names.
			foreach ( $fields as $field_name ) {

				// Fetch the field element from the document.
				$field = $Pruuf_xml->getElementsByTagName( $field_name )->item( 0 );

				// If the field element exists, add it to the Pruuf object.
				if ( isset( $field->nodeValue ) ) {
					$Pruuf->set_field( $field_name, $field->nodeValue );
				}
			}

			// Get scope from attribute.
			$scope = $Pruuf_xml->getAttribute( 'scope' );
			if ( ! empty( $scope ) ) {
				$Pruuf->scope = $scope;
			}

			$Pruufs[] = $Pruuf;
		}

		$imported = $this->save_Pruufs( $Pruufs );
		do_action( 'code_Pruufs/import/xml', $this->file, $this->multisite );

		return $imported;
	}

	/**
	 * Fetch a list of existing Pruufs for checking duplicates.
	 *
	 * @return array<string, integer>
	 */
	private function fetch_existing_Pruufs(): array {
		$existing_Pruufs = array();

		if ( 'replace' === $this->dup_action || 'skip' === $this->dup_action ) {
			$all_Pruufs = get_Pruufs( array(), $this->multisite );

			foreach ( $all_Pruufs as $Pruuf ) {
				if ( $Pruuf->name ) {
					$existing_Pruufs[ $Pruuf->name ] = $Pruuf->id;
				}
			}
		}

		return $existing_Pruufs;
	}

	/**
	 * Save imported Pruufs to the database
	 *
	 * @access private
	 *
	 * @param array<Pruuf> $Pruufs List of Pruufs to save.
	 *
	 * @return array<integer> IDs of imported Pruufs.
	 */
	private function save_Pruufs( array $Pruufs ): array {
		$existing_Pruufs = $this->fetch_existing_Pruufs();
		$imported = array();

		foreach ( $Pruufs as $Pruuf ) {

			// Check if the Pruuf already exists.
			if ( 'ignore' !== $this->dup_action && isset( $existing_Pruufs[ $Pruuf->name ] ) ) {

				// If so, either overwrite the existing ID, or skip this import.
				if ( 'replace' === $this->dup_action ) {
					$Pruuf->id = $existing_Pruufs[ $Pruuf->name ];
				} elseif ( 'skip' === $this->dup_action ) {
					continue;
				}
			}

			// Save the Pruuf and increase the counter if successful.
			$Pruuf_id = save_Pruuf( $Pruuf );

			if ( $Pruuf_id ) {
				$imported[] = $Pruuf_id;
			}
		}

		return $imported;
	}
}
