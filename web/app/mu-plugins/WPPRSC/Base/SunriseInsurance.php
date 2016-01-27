<?php
/**
 * Adjusts the Network Setup screen UI to the required multisite setup.
 */

namespace WPPRSC\Base;

class SunriseInsurance extends \WPPRSC\BaseAbstract {
	protected function __construct() {
	}

	public function run() {
		add_action( 'all_admin_notices', array( $this, 'ob_start' ), 1000 );
		add_action( 'in_admin_footer', array( $this, 'ob_get_clean' ), -1000 );
	}

	public function ob_start() {
		$screen = get_current_screen();
		if ( ! ( is_blog_admin() && 'network' === $screen->id || is_network_admin() && 'setup-network' === $screen->id ) ) {
			return;
		}

		ob_start();
	}

	public function ob_get_clean() {
		$screen = get_current_screen();
		if ( ! ( is_blog_admin() && 'network' === $screen->id || is_network_admin() && 'setup-network' === $screen->id ) ) {
			return;
		}

		$output = ob_get_clean();

		if ( false !== strpos( $output, '<code>wp-config.php</code>' ) ) {
			// network_step2()
			$output = preg_replace_callback( '#<li>(.*)</li>#Us', function( $matches ) {
				$match = $matches[1];
				if ( false !== strpos( $match, '<code>wp-config.php</code>' ) ) {
					$match = preg_replace_callback( '#<code>(.*)</code>#U', function( $submatches ) {
						$submatch = $submatches[1];
						if ( 'wp-config.php' === $submatch ) {
							$submatch = 'composer.json';
						} elseif ( 0 === strpos( $submatch, '/*' ) ) {
							$submatch = '"wp": {';
						} else {
							$submatch = dirname( $submatch ) . '/';
						}
						return '<code>' . $submatch . '</code>';
					}, $match );
					$match = preg_replace_callback( '#<textarea(.*)>(.*)</textarea>#Us', function( $submatches ) {
						$submatch = $submatches[2];
						$submatch = '      "multisite": true,' . "\n";
						return '<textarea' . str_replace( '7', '2', $submatches[1] ) . '>' . $submatch . '</textarea>';
					}, $match );
				}
				return '<li>' . $match . '</li>';
			}, $output );
		} else {
			// network_step1()
			$output = preg_replace_callback( '#<tr>(.*)</tr>#Us', function( $matches ) {
				$match = $matches[1];
				if ( false !== strpos( $match, '<input type="radio" name="subdomain_install" value="0"' ) ) {
					return '';
				}
				return '<tr>' . $match . '</tr>';
			}, $output );
		}

		echo $output;
	}
}
