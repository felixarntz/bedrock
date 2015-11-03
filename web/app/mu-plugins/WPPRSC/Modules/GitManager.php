<?php
/**
 * Handles Git inside the WordPress backend.
 */

namespace WPPRSC\Modules;

class GitManager extends \WPPRSC\ModuleAbstract {
	protected function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	public function run() {
		//TODO
	}

	protected function get_default_args() {
		return array(
			'git_path'			=> 'git',
			'repository'		=> '',
			'capability'		=> 'install_plugins',
		);
	}
}
