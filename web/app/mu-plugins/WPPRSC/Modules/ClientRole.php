<?php
/**
 * Adds an additional client role which has permissions like an admin on a multisite.
 */

namespace WPPRSC\Modules;

class ClientRole extends \WPPRSC\ModuleAbstract {
	protected function __construct( $args = array() ) {
		parent::__construct( $args );

		$this->module_name = 'client_role';
	}

	public function run() {
		if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
			return;
		}

		add_action( 'init', array( $this, 'add_role' ), 1 );
	}

	public function add_role() {
		$role = get_role( 'client' );
		if ( null !== $role ) {
			return;
		}

		$editor = get_role( 'editor' );

		$slug = 'client';
		$display_name = $this->get_setting( 'display_name' );
		$new_capabilities = array(
			'switch_themes'			=> true,
			'edit_theme_options'	=> true,
			'activate_plugins'		=> true,
			'manage_options'		=> true,
		);

		add_role( $slug, $display_name, array_merge( $editor->capabilities, $new_capabilities ) );
	}

	protected function get_default_args() {
		return array(
			'display_name'			=> 'Client',
		);
	}
}
