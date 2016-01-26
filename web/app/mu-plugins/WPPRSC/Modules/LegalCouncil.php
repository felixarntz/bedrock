<?php
/**
 * Handles legal things like cookie policy.
 */

namespace WPPRSC\Modules;

class LegalCouncil extends \WPPRSC\ModuleAbstract {
	protected function __construct( $args = array() ) {
		parent::__construct( $args );

		$this->module_name = 'legal_council';
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
