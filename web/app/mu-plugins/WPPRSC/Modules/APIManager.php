<?php
/**
 * Handles the WP REST API.
 */

namespace WPPRSC\Modules;

class APIManager extends \WPPRSC\ModuleAbstract {
	protected function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	public function run() {
		if ( ! $this->args['enabled'] ) {
			add_filter( 'rest_enabled', '__return_false' );
			add_filter( 'rest_jsonp_enabled', '__return_false' );
			remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		}

		if ( $this->args['url_prefix'] && is_string( $this->args['url_prefix'] ) && 'wp-json' !== $this->args['url_prefix'] ) {
			add_filter( 'rest_url_prefix', array( $this, 'adjust_url_prefix' ) );
			add_filter( 'subdirectory_reserved_names', array( $this, 'adjust_url_prefix_reserved_directories' ) );
		}
	}

	public function adjust_url_prefix( $prefix ) {
		return $this->args['url_prefix'];
	}

	public function adjust_url_prefix_reserved_directories( $names ) {
		$names[] = $this->args['url_prefix'];

		return array_diff( $names, array( 'wp-json' ) );
	}

	protected function get_default_args() {
		return array(
			'enabled'		=> true,
			'url_prefix'	=> 'wp-json',
		);
	}
}
