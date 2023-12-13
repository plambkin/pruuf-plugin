<?php
/**
 * Manages the settings fields definitions
 *
 * @package    Code_Pruufs
 * @subpackage Settings
 */

namespace Code_Pruufs\Settings;

/**
 * Retrieve the default setting values
 *
 * @return array<string, array<string, array>>
 */
function get_default_settings(): array {
	static $defaults;

	if ( isset( $defaults ) ) {
		return $defaults;
	}

	$defaults = array();

	foreach ( get_settings_fields() as $section_id => $fields ) {
		$defaults[ $section_id ] = array();

		foreach ( $fields as $field_id => $field_atts ) {
			$defaults[ $section_id ][ $field_id ] = $field_atts['default'];
		}
	}

	return $defaults;
}

/**
 * Retrieve the settings fields
 *
 * @return array<string, array<string, array>>
 */
function get_settings_fields(): array {
	static $fields;

	if ( isset( $fields ) ) {
		return $fields;
	}

	$fields = [];

	$fields['general'] = [
		'activate_by_default' => [
			'name'    => __( 'Activate by Default', 'code-Pruufs' ),
			'type'    => 'checkbox',
			'label'   => __( "Make the 'Save and Activate' button the default action when saving a Pruuf.", 'code-Pruufs' ),
			'default' => true,
		],

		'enable_tags' => [
			'name'    => __( 'Enable Pruuf Tags', 'code-Pruufs' ),
			'type'    => 'checkbox',
			'label'   => __( 'Show Pruuf tags on admin pages.', 'code-Pruufs' ),
			'default' => true,
		],

		'enable_description' => [
			'name'    => __( 'Enable Pruuf Descriptions', 'code-Pruufs' ),
			'type'    => 'checkbox',
			'label'   => __( 'Show Pruuf descriptions on admin pages.', 'code-Pruufs' ),
			'default' => true,
		],

		'visual_editor_rows' => [
			'name'    => __( 'Description Editor Height', 'code-Pruufs' ),
			'type'    => 'number',
			'label'   => _x( 'rows', 'unit', 'code-Pruufs' ),
			'default' => 5,
			'min'     => 0,
		],

		'list_order' => [
			'name'    => __( 'Pruufs List Order', 'code-Pruufs' ),
			'type'    => 'select',
			'desc'    => __( 'Default way to order Pruufs on the All Pruufs admin menu.', 'code-Pruufs' ),
			'options' => [
				'priority-asc'  => __( 'Priority', 'code-Pruufs' ),
				'name-asc'      => __( 'Name (A-Z)', 'code-Pruufs' ),
				'name-desc'     => __( 'Name (Z-A)', 'code-Pruufs' ),
				'modified-desc' => __( 'Modified (latest first)', 'code-Pruufs' ),
				'modified-asc'  => __( 'Modified (oldest first)', 'code-Pruufs' ),
			],
			'default' => 'priority-asc',
		],

		'disable_prism' => [
			'name'    => __( 'Disable Syntax Highlighter', 'code-Pruufs' ),
			'type'    => 'checkbox',
			'label'   => __( 'Disable syntax highlighting when displaying Pruuf code on the front-end.', 'code-Pruufs' ),
			'default' => false,
		],

		'complete_uninstall' => [
			'name'    => __( 'Complete Uninstall', 'code-Pruufs' ),
			'type'    => 'checkbox',
			'label'   => __( 'When the plugin is deleted from the Plugins menu, also delete all Pruufs and plugin settings.', 'code-Pruufs' ),
			'default' => false,
		],
	];

	if ( is_multisite() && ! is_main_site() ) {
		unset( $fields['general']['complete_uninstall'] );
	}

	// Code Editor settings section.

	$fields['editor'] = [
		'theme' => [
			'name'       => __( 'Theme', 'code-Pruufs' ),
			'type'       => 'select',
			'default'    => 'default',
			'options'    => get_editor_theme_list(),
			'codemirror' => 'theme',
		],

		'indent_with_tabs' => [
			'name'       => __( 'Indent With Tabs', 'code-Pruufs' ),
			'type'       => 'checkbox',
			'label'      => __( 'Use hard tabs instead of spaces for indentation.', 'code-Pruufs' ),
			'default'    => true,
			'codemirror' => 'indentWithTabs',
		],

		'tab_size' => [
			'name'       => __( 'Tab Size', 'code-Pruufs' ),
			'type'       => 'number',
			'desc'       => __( 'The width of a tab character.', 'code-Pruufs' ),
			'default'    => 4,
			'label'      => _x( 'spaces', 'unit', 'code-Pruufs' ),
			'codemirror' => 'tabSize',
			'min'        => 0,
		],

		'indent_unit' => [
			'name'       => __( 'Indent Unit', 'code-Pruufs' ),
			'type'       => 'number',
			'desc'       => __( 'The number of spaces to indent a block.', 'code-Pruufs' ),
			'default'    => 4,
			'label'      => _x( 'spaces', 'unit', 'code-Pruufs' ),
			'codemirror' => 'indentUnit',
			'min'        => 0,
		],

		'wrap_lines' => [
			'name'       => __( 'Wrap Lines', 'code-Pruufs' ),
			'type'       => 'checkbox',
			'label'      => __( 'Soft-wrap long lines of code instead of horizontally scrolling.', 'code-Pruufs' ),
			'default'    => true,
			'codemirror' => 'lineWrapping',
		],

		'code_folding' => [
			'name'       => __( 'Code Folding', 'code-Pruufs' ),
			'type'       => 'checkbox',
			'label'      => __( 'Allow folding functions or other blocks into a single line.', 'code-Pruufs' ),
			'default'    => true,
			'codemirror' => 'foldGutter',
		],

		'line_numbers' => [
			'name'       => __( 'Line Numbers', 'code-Pruufs' ),
			'type'       => 'checkbox',
			'label'      => __( 'Show line numbers to the left of the editor.', 'code-Pruufs' ),
			'default'    => true,
			'codemirror' => 'lineNumbers',
		],

		'auto_close_brackets' => [
			'name'       => __( 'Auto Close Brackets', 'code-Pruufs' ),
			'type'       => 'checkbox',
			'label'      => __( 'Auto-close brackets and quotes when typed.', 'code-Pruufs' ),
			'default'    => true,
			'codemirror' => 'autoCloseBrackets',
		],

		'highlight_selection_matches' => [
			'name'       => __( 'Highlight Selection Matches', 'code-Pruufs' ),
			'label'      => __( 'Highlight all instances of a currently selected word.', 'code-Pruufs' ),
			'type'       => 'checkbox',
			'default'    => true,
			'codemirror' => 'highlightSelectionMatches',
		],

		'highlight_active_line' => [
			'name'       => __( 'Highlight Active Line', 'code-Pruufs' ),
			'label'      => __( 'Highlight the line that is currently being edited.', 'code-Pruufs' ),
			'type'       => 'checkbox',
			'default'    => true,
			'codemirror' => 'styleActiveLine',
		],
		'keymap'                => [
			'name'       => __( 'Keymap', 'code-Pruufs' ),
			'type'       => 'select',
			'desc'       => __( 'The set of keyboard shortcuts to use in the code editor.', 'code-Pruufs' ),
			'default'    => 'default',
			'options'    => [
				'default' => __( 'Default', 'code-Pruufs' ),
				'vim'     => __( 'Vim', 'code-Pruufs' ),
				'emacs'   => __( 'Emacs', 'code-Pruufs' ),
				'sublime' => __( 'Sublime Text', 'code-Pruufs' ),
			],
			'codemirror' => 'keyMap',
		],

	];

	$fields = apply_filters( 'code_Pruufs_settings_fields', $fields );

	return $fields;
}
