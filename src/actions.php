<?php

use FTRatings\ManagedFile;

add_action( 'acf/save_post', 'ftratings_private_files_save_post', 20 );
function ftratings_private_files_save_post( $post_id ) {
	if ( $parent_id = wp_is_post_revision( $post_id ) ) {
		$post_id = $parent_id;
	}
	remove_action( 'acf/save_post', 'ftratings_private_files_save_post' );
	ManagedFile::fromPostId( $post_id )->handle();
	add_action( 'acf/save_post', 'ftratings_private_files_save_post', 20 );

	return $post_id;
}

