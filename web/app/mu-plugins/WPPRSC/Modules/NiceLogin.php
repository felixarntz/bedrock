<?php
/**
 * Enhances the login screen with customizable design.
 */

namespace WPPRSC\Modules;

class NiceLogin extends \WPPRSC\ModuleAbstract {
	protected function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	public function run() {
		//TODO
	}

	protected function get_default_args() {
		return array(
			'enabled'		=> false,
		);
	}
}
