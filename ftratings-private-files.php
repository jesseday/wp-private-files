<?php
/**
 * Plugin Name:     Ftratings Private Files
 * Plugin URI:      franklintrustratings.com
 * Description:     Allow upload of files to private directory
 * Author:          Jesse Day
 * Text Domain:     ftratings-private-files
 * Version:         0.1.0
 *
 * @package         Ftratings_Private_Files
 */

define('FTRATINGS_PRIVATE_FILES_PLUGIN_PATH', plugin_dir_path(__FILE__));
if ( ! defined( 'USE_ACF_LOCAL_CONFIG' ) || ! USE_ACF_LOCAL_CONFIG ) {
	include_once( plugin_dir_path( __FILE__ ) . '/src/custom-fields.php' );
}

include_once(plugin_dir_path(__FILE__) . '/src/ManagedFile.php');
include_once(plugin_dir_path(__FILE__) . '/src/actions.php');
include_once(plugin_dir_path(__FILE__) . '/src/filters.php');
include_once(plugin_dir_path(__FILE__) . '/src/routes.php');


function ftratings_private_files_create_permissions() {
	$roles = [
		'administrator',
		'editor',
		'author',
		'customer',
	];
	foreach ($roles as $role) {
		if (!wp_roles()->roles[$role]['capabilities']['view_private_files']) {
			wp_roles()->add_cap($role, 'view_private_files');
		}
	}
}
add_action( 'plugins_loaded', 'ftratings_private_files_create_permissions' , 20);
