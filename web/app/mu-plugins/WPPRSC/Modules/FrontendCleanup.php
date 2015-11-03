<?php
/**
 * Cleans up unnecessary WP output from the frontend.
 */

namespace WPPRSC\Modules;

class FrontendCleanup extends \WPPRSC\ModuleAbstract {
	protected function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	public function run() {
		//TODO
	}

	protected function get_default_args() {
		return array(
			'cleanup_feed_links'		=> false,
			'cleanup_feed_links_extra'	=> false,
			'cleanup_rsd_link'			=> false,
			'cleanup_wlwmanifest_link'	=> false,
			'cleanup_wp_generator'		=> false,
			'cleanup_asset_versions'	=> false,
			'cleanup_style_tags'		=> false,
			'cleanup_script_tags'		=> false,
			'cleanup_img_dimensions'	=> false,
		);
	}
}
