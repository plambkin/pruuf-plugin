<?php

namespace Code_Pruufs;

/**
 * Handles exporting Pruufs from the site to a downloadable file over HTTP.
 *
 * @package Code_Pruufs
 */
class Export_Attachment extends Export {

	/**
	 * Set up the current page to act like a downloadable file instead of being shown in the browser
	 *
	 * @param string $format    File format. Used for file extension.
	 * @param string $mime_type File MIME type. Used for Content-Type header.
	 */
	private function do_headers( string $format, string $mime_type = 'text/plain' ) {
		header( 'Content-Disposition: attachment; filename=' . sanitize_file_name( $this->build_filename( $format ) ) );
		header( sprintf( 'Content-Type: %s; charset=%s', sanitize_mime_type( $mime_type ), get_bloginfo( 'charset' ) ) );
	}

	/**
	 * Export Pruufs in JSON format as a downloadable file.
	 */
	public function download_Pruufs_json() {
		$this->do_headers( 'json', 'application/json' );
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_json_encode(
			$this->create_export_object(),
			apply_filters( 'code_Pruufs/export/json_encode_options', 0 )
		);
		exit;
	}

	/**
	 * Export Pruufs in their code file format.
	 */
	public function download_Pruufs_code() {
		$mime_types = [
			'php' => 'text/php',
			'css' => 'text/css',
			'js'  => 'text/javascript',
		];

		$type = isset( $mime_types[ $this->Pruufs_list[0]->type ] ) ? $this->Pruufs_list[0]->type : 'php';
		$this->do_headers( $type, $mime_types[ $type ] );

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo ( 'php' === $type || 'html' === $type ) ?
			$this->export_Pruufs_php() :
			$this->export_Pruufs_code( $type );

		exit;
	}
}
