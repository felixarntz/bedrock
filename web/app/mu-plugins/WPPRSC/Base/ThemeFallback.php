<?php
/**
 * Registers default theme directory if nothing else available.
 */

namespace WPPRSC\Base;

class ThemeFallback extends \WPPRSC\Abstract {
	protected function __construct() {
		if ( ! defined( 'WP_DEFAULT_THEME' ) ) {
			register_theme_directory( ABSPATH . 'wp-content/themes' );
		}
	}
}
