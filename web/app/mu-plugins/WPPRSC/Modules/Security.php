<?php
/**
 * Enhances security in WordPress.
 */

namespace WPPRSC\Modules;

class Security extends \WPPRSC\ModuleAbstract {
	protected function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	public function run() {
		if ( $this->args['disable_xmlrpc'] ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
		}
	}

	protected function get_default_args() {
		return array(
			'disable_xmlrpc'		=> false,
		);
	}
}
