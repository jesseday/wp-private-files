<?php

use WP_Private_Files\ManagedFile;

add_filter( 'wp_get_attachment_url', 'wp_private_files_attachment_url_filter', 100, 2 );
/**
 * @param $url
 * @param $post_id
 *
 * @return string
 */
function wp_private_files_attachment_url_filter( $url, $post_id ) {
	remove_filter( 'wp_get_attachment_url', 'wp_private_files_attachment_url_filter' );
	$url = ManagedFile::fromPostId( $post_id )->managedUrl();
	add_filter( 'wp_get_attachment_url', 'wp_private_files_attachment_url_filter', 100, 2 );

	return $url;
}

add_filter( 'wp_calculate_image_srcset', 'wp_private_files_attachment_image_src_filter', 100, 5 );
/**
 * @param $sources
 * @param $size_array
 * @param $image_src
 * @param $image_meta
 * @param $attachment_id
 *
 * @return array
 */
function wp_private_files_attachment_image_src_filter( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
	$sources = array_map( function ( $source ) {
		$source['url'] = ManagedFile::fromPath( $source['url'] )->managedUrl();

		return $source;
	}, $sources );

	return $sources;
}
