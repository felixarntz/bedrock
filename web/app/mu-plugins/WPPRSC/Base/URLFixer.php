<?php
/**
 * Fixes home URL and site URL (especially for multisite).
 */

namespace WPPRSC\Base;

class URLFixer extends \WPPRSC\BaseAbstract {
	protected function __construct() {
	}

	public function run() {
		add_filter( 'option_home', array( $this, 'fix_home_url' ) );
		add_filter( 'sanitize_option_home', array( $this, 'fix_home_url' ) );

		add_filter( 'option_siteurl', array( $this, 'fix_site_url' ) );
		add_filter( 'sanitize_option_siteurl', array( $this, 'fix_site_url' ) );
	}

	public function fix_home_url( $value ) {
		if ( '/core' === substr( $value, -5 ) ) {
			$value = substr( $value, 0, -5 );
		}
		return $value;
	}

	public function fix_site_url( $value ) {
		if ( '/core' !== substr( $value, -5 ) ) {
			$value .= '/core';
		}
		return $value;
	}
}
