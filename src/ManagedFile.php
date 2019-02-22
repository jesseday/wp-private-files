<?php

namespace WP_Private_Files;

class ManagedFile {

	protected $post;
	protected $path;

	public function __construct( $post = null, $path = null ) {
		$this->post = $post;
		$this->path = $this->makeRelativePath( $path );
	}

	/*
    |--------------------------------------------------------------------------
    | Static Constructors
    |--------------------------------------------------------------------------
    */

	public static function fromPostId( $post, $path = null ) {
		$post = is_a( $post, 'WP_Post' ) ? $post : get_post( $post );
		$path = $path ? : get_post_meta( $post->ID, '_wp_attached_file' )[0];

		return new static( $post, $path );
	}

	/**
	 * Static constructor creating a managed file from a given alias
	 *
	 * @param $alias
	 *
	 * @return static
	 */
	public static function fromPath( $path ) {
		$file          = new static;
		$relative_path = $file->makeRelativePath( $path );
		$paths         = $file->normalizedFilePaths( $relative_path);

		$posts         = get_posts( [
			'ignore_sticky_posts' => true,
			'post_type'           => 'attachment',
			'meta_query'          => [
				[
					'key'     => '_wp_attached_file',
					'value'   => $paths,
					'compare' => 'IN'
				]
			]
		] );

		if ( ! is_array( $posts ) ) {
			return $file->set( 'path', $relative_path );
		}

		if ( count( $posts ) === 1 ) {
			return $file->set( 'post', reset( $posts ) )
			            ->set( 'path', $relative_path );
		}

		foreach ( $posts as $post ) {
			$meta = wp_get_attachment_metadata( $post->ID );
			if ( isset( $meta['file'] ) && $meta['file'] === $relative_path ) {

				return $file->set( 'post', $post )
				            ->set( 'path', $relative_path );
			}

			$cropped_paths = array_column( $meta['sizes'], 'file' );
			$cropped_paths = array_map( function ( $filename ) use ( $relative_path ) {
				return static::fileDir( $relative_path ) . '/' . $filename;
			}, $cropped_paths );
			if ( array_intersect( $cropped_paths, $paths ) ) {
				return $file->set( 'post', $post )
				            ->set( 'path', $relative_path );
			}
		}

		return $file->set( 'post', reset( $posts ) )
		            ->set( 'path', $relative_path );
	}

	/**
	 * Create an array of normalized file paths from a filename that may
	 * include width and height dimensions.
	 *
	 * @param $filename
	 *
	 * @return array
	 */
	public function normalizedFilePaths( $filename ) {
		$filename    = $this->makeRelativePath( $filename );
		$info        = pathinfo( $filename );
		$alternative = preg_replace( '/(.*)\-\d+x\d+$/', '$1', $info['filename'] );

		return [
			$filename,
			$info['dirname'] . '/' . $alternative . '.' . $info['extension']
		];
	}

	public static function fileDir( $path ) {
		return pathinfo( $path )['dirname'];
	}

	/**
	 * Public entry point.  Do all the things to save a file as private or public.
	 */
	public function handle() {
		return $this->move();
	}

	/*
    |--------------------------------------------------------------------------
    | File
    |--------------------------------------------------------------------------
    */

	public function move() {
		$new_path = $this->postIsPrivate() && ! $this->pathIsPrivate()
			? $this->privatePath()
			: $this->publicPath();

		$original_path = $this->postIsPrivate() && ! $this->pathIsPrivate()
			? $this->publicPath()
			: $this->privatePath();

		if ( ! is_dir( dirname( $new_path ) ) ) {
			wp_mkdir_p( dirname( $new_path ) );
		}
		if ( is_dir( $new_path ) ) {
			return false;
		}

		rename( $original_path, $new_path );

		// Move all resized images for this file.
		foreach ( $this->resizedFiles( $original_path ) as $file ) {
			rename( $file, $this->swapFilePath( $file, $new_path ) );
		}
	}

	/**
	 * Stream the private file to the client and exit the script.
	 */
	public function transfer() {
		if ( ! $this->userHasAccess() ) {
			$this->forbid();
		}

		if ( ! $this->exists() ) {
			$this->abort();
		}

		header( 'Content-Type: ' . $this->post->post_mime_type );
		header( 'Content-Length: ' . filesize( $this->source() ) );

		$file = fopen( $this->source(), 'rb' );
		fpassthru( $file );
		exit;
	}

	protected function forbid() {
		header( 'HTTP/1.0 403 Forbidden' );
		echo 'Access denied';
		exit;
	}

	protected function abort() {
		header( 'HTTP/1.0 404 Not Found' );
		echo 'The requested resource could not be found';
		exit;
	}

	/*
    |--------------------------------------------------------------------------
    | Access
    |--------------------------------------------------------------------------
    */

	protected function userHasAccess() {

		return current_user_can( 'view_private_files' );
	}

	protected function exists() {
		return ! is_null( $this->path )
		       && file_exists( $this->source() )
		       && is_file( $this->source() );
	}

	/*
    |--------------------------------------------------------------------------
    | Path
    |--------------------------------------------------------------------------
    */

	protected function source() {
		if ( $this->postIsPublic() ) {
			return $this->publicPath();
		}

		return $this->privatePath();
	}

	private function publicPath() {
		return wp_upload_dir()['basedir'] . '/' . $this->path;
	}

	private function privatePath() {
		return wp_upload_dir()['basedir'] . '/private/' . $this->path;
	}

	private function makeRelativePath( $url = null ) {
		$url = $url ? : get_post_meta( $this->post->ID, '_wp_attached_file' )[0];

		$attachment_url_parts = wp_parse_url( $url );
		$attachment_path      = $attachment_url_parts['path'];

		return preg_replace( '/^\/wp-content\/uploads\/(.*)/', '$1', $attachment_path );
	}

	/*
    |--------------------------------------------------------------------------
    | Privacy
    |--------------------------------------------------------------------------
    */

	public function postIsPrivate() {
		if ( is_null( $this->post ) ) {
			return false;
		}
		if ( 'attachment' !== get_post_type( $this->post->ID ) ) {
			return false;
		}

		if ( ! get_field( 'is_private', $this->post->ID ) ) {
			return false;
		}

		return true;
	}

	public function pathIsPrivate() {
		return file_exists( $this->privatePath() );
	}

	public function postIsPublic() {
		return ! $this->postIsPrivate();
	}

	public function pathIsPublic() {
		return ! $this->pathIsPrivate();
	}

	/*
    |--------------------------------------------------------------------------
    | URL
    |--------------------------------------------------------------------------
    */

	public function url() {
		return wp_upload_dir()['baseurl'] . '/' . $this->path;
	}

	public function managedUrl() {
		if ( $this->postIsPublic() ) {
			return $this->url();
		}

		return home_url( '/' ) . 'managed/files/' . $this->path;
	}

	/*
    |--------------------------------------------------------------------------
    | Image meta
    |--------------------------------------------------------------------------
    */

	private function resizedFiles( $path = null ) {
		$path          = $path ? : $this->path;
		$meta          = wp_get_attachment_metadata( $this->post->ID );
		$cropped_paths = array_column( $meta['sizes'], 'file' );

		return array_map( function ( $filename ) use ( $path ) {
			return static::fileDir( $path ) . '/' . $filename;
		}, $cropped_paths );
	}

	private function swapFilePath( $existing, $moving_to ) {
		$existing  = pathinfo( $existing );
		$moving_to = pathinfo( $moving_to );

		return $moving_to['dirname'] . '/' . $existing['basename'];
	}

	/*
    |--------------------------------------------------------------------------
    | Setters
    |--------------------------------------------------------------------------
    */

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return $this
	 */
	public function set( $key, $value ) {
		$this->{$key} = $value;

		return $this;
	}
}
