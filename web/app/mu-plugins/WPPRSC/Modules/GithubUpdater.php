<?php
/**
 * Handles Github Updater settings.
 */

namespace WPPRSC\Modules;

class GithubUpdater extends \WPPRSC\ModuleAbstract {
	protected function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	public function run() {
		if ( $this->args['extended_naming'] ) {
			if ( ! defined( 'GITHUB_UPDATER_EXTENDED_NAMING' ) ) {
				define( 'GITHUB_UPDATER_EXTENDED_NAMING', true );
			}
		}

		if ( is_array( $this->args['token_distribution'] ) && $this->args['token_distribution'] ) {
			add_filter( 'github_updater_token_distribution', array( $this, 'filter_token_distribution' ) );
		}

		if ( $this->args['hide_settings'] ) {
			add_filter( 'github_updater_hide_settings', '__return_true' );
		}
	}

	public function filter_token_distribution( $tokens = array() ) {
		return array_merge( $tokens, $this->args['token_distribution'] );
	}

	protected function get_default_args() {
		return array(
			'extended_naming'		=> false,
			'token_distribution'	=> array(),
			'hide_settings'			=> false,
		);
	}
}
