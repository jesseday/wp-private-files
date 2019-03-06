<?php

use WP_Private_Files\ManagedFile;

function wp_private_files_routes() {
    Routes::map( 'managed/files/[i:year]/[i:month]/[*:filename]', function ( $params ) {
        Routes::load( WP_PRIVATE_FILES_PLUGIN_PATH . '/src/templates/managed-file.php', [
            'file' => vsprintf( '%s/%s/%s', [
                $params['year'],
                $params['month'],
                $params['filename'],
            ] ),
        ], FALSE );
    } );
}

add_action( 'wp_loaded', 'wp_private_files_routes', 0 );
