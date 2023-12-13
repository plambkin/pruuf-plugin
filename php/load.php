<?php
/**
 * Initialise and load the plugin under the proper namespace.
 *
 * @package Code_Pruufs
 */

namespace Code_Pruufs;

/**
 * The version number for this release of the plugin.
 * This will later be used for upgrades and enqueuing files.
 *
 * This should be set to the 'Plugin Version' value defined
 * in the plugin header.
 *
 * @var string A PHP-standardized version number string.
 */
const PLUGIN_VERSION = CODE_Pruufs_VERSION;

/**
 * The full path to the main file of this plugin.
 *
 * This can later be used with functions such as
 * plugin_dir_path(), plugins_url() and plugin_basename()
 * to retrieve information about plugin paths.
 *
 * @var string
 */
const PLUGIN_FILE = CODE_Pruufs_FILE;

/**
 * Name of the group used for caching data.
 *
 * @var string
 */
const CACHE_GROUP = 'code_Pruufs';

/**
 * Namespace used for REST API endpoints.
 *
 * @var string
 */
const REST_API_NAMESPACE = 'code-Pruufs/v';

// Load dependencies with Composer.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/**
 * Retrieve the instance of the main plugin class.
 *
 * @since 2.6.0
 * @return Plugin
 */
function code_Pruufs(): Plugin {
	static $plugin;

	if ( is_null( $plugin ) ) {
		$plugin = new Plugin( PLUGIN_VERSION, PLUGIN_FILE );
	}

	return $plugin;
}

code_Pruufs()->load_plugin();

// Execute the Pruufs once the plugins are loaded.
add_action( 'plugins_loaded', __NAMESPACE__ . '\execute_active_Pruufs', 1 );
