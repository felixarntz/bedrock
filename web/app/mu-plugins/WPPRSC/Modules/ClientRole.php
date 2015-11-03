<?php
/**
 * Adds an additional client role which has permissions like an admin on a multisite.
 */

namespace WPPRSC\Modules;

class ClientRole extends \WPPRSC\ModuleAbstract {
	protected function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	public function run() {
		//TODO
	}

	protected function get_default_args() {
		return array();
	}
}
