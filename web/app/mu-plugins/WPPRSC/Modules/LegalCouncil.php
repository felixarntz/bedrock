<?php
/**
 * Handles legal things like cookie policy.
 */

namespace WPPRSC\Modules;

class LegalCouncil extends \WPPRSC\ModuleAbstract {
	protected function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	public function run() {
		//TODO
	}

	protected function get_default_args() {
		return array(
			'cookie_notice'		=> false,
			'legal_generator'	=> false,
		);
	}
}
