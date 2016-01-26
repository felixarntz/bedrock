<?php
/**
 * Sends nice HTML emails with customizable design instead of boring plain text.
 */

namespace WPPRSC\Modules;

class NiceEmails extends \WPPRSC\ModuleAbstract {
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
