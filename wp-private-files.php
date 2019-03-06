<?php
/**
 * Plugin Name:     WP Private Files
 * Description:     Allow upload of files to private directory
 * Author:          Jesse Day
 * Text Domain:     wp-private-files
 * Version:         0.1.0
 *
 * @package         WP_Private_Files
 */

define( 'WP_PRIVATE_FILES_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'USE_ACF_LOCAL_CONFIG' ) || ! USE_ACF_LOCAL_CONFIG ) {
    include_once( plugin_dir_path( __FILE__ ) . '/src/custom-fields.php' );
}

include_once( plugin_dir_path( __FILE__ ) . '/src/ManagedFile.php' );
include_once( plugin_dir_path( __FILE__ ) . '/src/actions.php' );
include_once( plugin_dir_path( __FILE__ ) . '/src/filters.php' );
include_once( plugin_dir_path( __FILE__ ) . '/src/routes.php' );


function wp_private_files_create_permissions() {
    $roles = [
        'administrator',
        'editor',
        'author',
    ];
    foreach ( $roles as $role ) {
        if ( ! wp_roles()->roles[ $role ]['capabilities']['view_private_files'] ) {
            wp_roles()->add_cap( $role, 'view_private_files' );
        }
    }
}

add_action( 'plugins_loaded', 'wp_private_files_create_permissions', 20 );
