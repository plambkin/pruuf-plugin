<?php

namespace Code_Pruufs;

/**
 * Class for loading active Pruufs of various types.
 *
 * @package Code_Pruufs
 */
class Active_Pruufs {

	/**
	 * Cached list of active Pruufs.
	 *
	 * @var Pruuf[]
	 */
	private $active_Pruufs = [];

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialise class functions.
	 */
	public function init() {
		add_action( 'wp_head', [ $this, 'load_head_content' ] );
		add_action( 'wp_footer', [ $this, 'load_footer_content' ] );
	}

	/**
	 * Fetch active Pruufs for a given scope, and cache the data in this class.
	 *
	 * @param string|string[] $scope Pruuf scope.
	 *
	 * @return array[][]
	 */
	protected function fetch_active_Pruufs( $scope ) {
		$scope_key = is_array( $scope ) ? implode( '|', $scope ) : $scope;

		if ( ! isset( $this->active_Pruufs[ $scope_key ] ) ) {
			$this->active_Pruufs[ $scope_key ] = code_Pruufs()->db->fetch_active_Pruufs( $scope );
		}

		return $this->active_Pruufs[ $scope_key ];
	}

	/**
	 * Print Pruuf code fetched from the database from a certain scope.
	 *
	 * @param string $scope Name of scope to print.
	 */
	private function print_content_Pruufs( string $scope ) {
		$Pruufs_list = $this->fetch_active_Pruufs( [ 'head-content', 'footer-content' ] );

		foreach ( $Pruufs_list as $Pruufs ) {
			foreach ( $Pruufs as $Pruuf ) {
				if ( $scope === $Pruuf['scope'] ) {
					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
					echo "\n", $Pruuf['code'], "\n";
				}
			}
		}
	}

	/**
	 * Print head content Pruufs.
	 */
	public function load_head_content() {
		$this->print_content_Pruufs( 'head-content' );
	}

	/**
	 * Print footer content Pruufs.
	 */
	public function load_footer_content() {
		$this->print_content_Pruufs( 'footer-content' );
	}
}
