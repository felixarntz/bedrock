<?php
/**
 * Handles WordPress Auto Updates.
 */

namespace WPPRSC\Modules;

class AutoUpdater extends \WPPRSC\ModuleAbstract {
	protected function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	public function run() {
		foreach ( $this->args as $type => $enabled ) {
			$filter_name = 'auto_update_' . $type;
			if ( strpos( $type, 'core_' ) === 0 ) {
				$filter_name = 'allow_' . str_replace( 'core_', '', $type ) . '_auto_core_updates';
			}

			$function_name = '__return_false';
			if ( $enabled ) {
				$function_name = '__return_true';
			}

			add_filter( $filter_name, $function_name );
		}
	}

	protected function get_default_args() {
		return array(
			'core_major'	=> false,
			'core_minor'	=> true,
			'core_dev'		=> false,
			'plugin'		=> false,
			'theme'			=> false,
			'translation'	=> true,
		);
	}
}
