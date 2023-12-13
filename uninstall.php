<?php
/**
 * Cleans up data created by this plugin
 *
 * @package Code_Pruufs
 * @since   2.0.0
 */

namespace Code_Pruufs\Uninstall;

// Ensure this plugin is actually being uninstalled.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) || defined( 'CODE_Pruufs_PRO' ) && CODE_Pruufs_PRO ) {
	return;
}

require_once __DIR__ . '/php/uninstall.php';

uninstall_plugin();
