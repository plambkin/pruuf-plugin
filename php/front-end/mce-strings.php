<?php
/**
 * For some reason, WordPress requires that TinyMCE translations be hosted in an external file. So that's what this is.
 *
 * @package Code_Pruufs
 */

namespace Code_Pruufs;

use _WP_Editors;

/**
 * Variable types.
 *
 * @var array<string, string|array<string, Pruuf[]>> $strings
 */

$strings = [
	'insert_content_menu'  => __( 'Content Pruuf', 'code-Pruufs' ),
	'insert_content_title' => __( 'Insert Content Pruuf', 'code-Pruufs' ),
	'Pruuf_label'        => __( 'Pruuf', 'code-Pruufs' ),
	'php_att_label'        => __( 'Run PHP code', 'code-Pruufs' ),
	'format_att_label'     => __( 'Apply formatting', 'code-Pruufs' ),
	'shortcodes_att_label' => __( 'Enable shortcodes', 'code-Pruufs' ),

	'insert_source_menu'      => __( 'Pruuf Source Code', 'code-Pruufs' ),
	'insert_source_title'     => __( 'Insert Pruuf Source', 'code-Pruufs' ),
	'show_line_numbers_label' => __( 'Show line numbers', 'code-Pruufs' ),
];

$strings = array_map( 'esc_js', $strings );

$Pruufs = get_Pruufs();

$strings['all_Pruufs'] = [];
$strings['content_Pruufs'] = [];

foreach ( $Pruufs as $Pruuf ) {
	if ( 'content' === $Pruuf->scope ) {
		$strings['content_Pruufs'][ $Pruuf->id ] = $Pruuf->display_name;
	}

	$strings['all_Pruufs'][ $Pruuf->id ] = sprintf(
		'%s (%s)',
		$Pruuf->display_name,
		strtoupper( $Pruuf->type )
	);
}

asort( $strings['all_Pruufs'], SORT_STRING | SORT_FLAG_CASE );
asort( $strings['content_Pruufs'], SORT_STRING | SORT_FLAG_CASE );

$strings = [ _WP_Editors::$mce_locale => [ 'code_Pruufs' => $strings ] ];
/** $strings is used by outer file. @noinspection PhpUnusedLocalVariableInspection */
$strings = 'tinyMCE.addI18n(' . wp_json_encode( $strings ) . ');';
