<?php

use Ftratings\ManagedFile;

function ftratings_private_files_routes() {
	Routes::map( 'managed/files/[i:year]/[i:month]/[*:filename]', function ( $params ) {
		Routes::load( FTRATINGS_PRIVATE_FILES_PLUGIN_PATH . '/src/templates/managed-file.php', [
			'file' => vsprintf( '%s/%s/%s', [
				$params['year'],
				$params['month'],
				$params['filename']
			] )
		] , false);
	} );
}

add_action( 'wp_loaded', 'ftratings_private_files_routes', 0);
