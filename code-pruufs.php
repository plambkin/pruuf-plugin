<?php
/**
 * Plugin Name:  Pruufs
 * Plugin URI:   https://Pruuf.app
 * Description:  An easy, clean and simple way to run Pruufs on your site. No need to edit to your theme's functions.php file again!
 * Author:       Pruufs Pro
 * Author URI:   https://Pruuf.app
 * License:      GPL-2.0-or-later
 * License URI:  license.txt
 * Text Domain:  code-Pruufs
 * Version:      3.4.2
 * Requires PHP: 7.0
 * Requires at least: 5.0
 *
 * @version   3.4.2
 * @package   Code_Pruufs
 * @author    Paul Lambkin <shea@Pruuf.app>
 * @copyright 2012-2023 Pruufs Pro
 * @license   GPL-2.0-or-later https://spdx.org/licenses/GPL-2.0-or-later.html
 * @link      https://github.com/codePruufspro/code-Pruufs
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Halt loading here if the plugin is already loaded, or we're running an incompatible version of PHP.
if ( ! defined( 'CODE_Pruufs_FILE' ) && version_compare( phpversion(), '7.0', '>=' ) ) {

	/**
	 * The current plugin version.
	 *
	 * Should be set to the same value as set above.
	 *
	 * @const string
	 */
	define( 'CODE_Pruufs_VERSION', '3.4.2' );

	/**
	 * The full path to the main file of this plugin.
	 *
	 * This can later be passed to functions such as plugin_dir_path(), plugins_url() and plugin_basename()
	 * to retrieve information about plugin paths.
	 *
	 * @since 2.0.0
	 * @const string
	 */
	define( 'CODE_Pruufs_FILE', __FILE__ );

	/**
	 * Used to determine which version of Pruufs is running.
	 *
	 * @since 3.0.0
	 * @onst  boolean
	 */
	define( 'CODE_Pruufs_PRO', true );

	require_once dirname( __FILE__ ) . '/php/load.php';
} else {
	require_once dirname( __FILE__ ) . '/php/deactivation-notice.php';
}
