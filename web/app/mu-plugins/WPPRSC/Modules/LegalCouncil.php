<?php
/**
 * Handles legal things like cookie policy.
 */

namespace WPPRSC\Modules;

class LegalCouncil extends \WPPRSC\ModuleAbstract {
	protected $cookie_name = 'cookies_accepted';

	protected function __construct( $args = array() ) {
		parent::__construct( $args );

		$this->module_name = 'legal_council';
	}

	public function run() {
		if ( $this->get_setting( 'cookie_notice' ) && ! is_admin() && ! isset( $_COOKIE[ $this->cookie_name ] ) ) {
			add_action( 'wp_head', array( $this, 'print_cookie_notice_style' ), 7 );
			add_action( 'wp_head', array( $this, 'print_cookie_notice_script' ), 7 );
			add_action( 'wp_footer', array( $this, 'print_cookie_notice' ), 1000 );
			add_filter( 'body_class', array( $this, 'add_body_class' ) );
		}
	}

	public function print_cookie_notice_style() {
		?>
		<style type="text/css">
			.cookie-notice {
				position: fixed;
				right: 0;
				bottom: 0;
				left: 0;
				z-index: 100;
				padding: 5px 10px;
				color: white;
				background: black;
			}

			.cookie-notice-inner {
				margin: 0 auto;
				width: 100%;
				max-width: 1200px;
				text-align: center;
			}
		</style>
		<?php
	}

	public function print_cookie_notice_script() {
		$tracking_code = '';
		if ( class_exists( 'Yoast_GA_Options' ) ) {
			$tracking_code = \Yoast_GA_Options::instance()->get_tracking_code();
		}

		?>
		<script type="text/javascript">
			var _cookieNotice = {
				active: true,
				acceptString: '<?php echo $this->cookie_name; ?>',
				disableGAString: '<?php echo $tracking_code ? 'ga-disable-' . $tracking_code : ''; ?>',
				checkGA: function() {
					if ( _cookieNotice.disableGAString.length ) {
						if ( 0 > document.cookie.indexOf( _cookieNotice.acceptString + '=true' ) ) {
							window[ _cookieNotice.disableGAString ] = true;
						} else {
							if ( 'undefined' !== typeof window[ _cookieNotice.disableGAString ] ) {
								window[ _cookieNotice.disableGAString ] = undefined;
							}
						}
					}
				},
				setAcceptCookie: function() {
					document.cookie = _cookieNotice.acceptString + '=true; expires=Thu, 31 Dec 2099 23:59:59 UTC; path=/';
					_cookieNotice.checkGA();
				},
				init: function() {
					_cookieNotice.checkGA();
					_cookieNotice.active = 0 > document.cookie.indexOf( _cookieNotice.acceptString + '=true' );
					if ( ! _cookieNotice.active ) {
						return;
					}
					window.onload = function() {
						var acceptCookieButton = document.getElementById( 'accept-cookies' );
						if ( ! acceptCookieButton ) {
							return;
						}
						acceptCookieButton.addEventListener( 'click', function( event ) {
							_cookieNotice.setAcceptCookie();
							document.body.classList.remove( 'has-cookie-notice' );
							var cookieNotice = document.getElementById( 'cookie-notice' );
							cookieNotice.parentNode.removeChild( cookieNotice );
							event.preventDefault();
						});
					}
				}
			};
			_cookieNotice.init();
		</script>
		<?php
	}

	public function print_cookie_notice() {
		$text = $this->get_setting( 'cookie_notice' );
		if ( ! is_string( $text ) ) {
			$text = __( 'This site uses cookies to improve your experience. By continuing to use this site, you accept the usage of cookies.', 'wpprsc' );
		}
		?>
		<div id="cookie-notice" class="cookie-notice">
			<div class="cookie-notice-inner">
				<span><?php echo $text; ?></span>
				<a href="#" id="accept-cookies" class="btn btn-secondary btn-sm">OK</a>
			</div>
		</div>
		<?php
	}

	public function add_body_class( $classes ) {
		$classes[] = 'has-cookie-notice';

		return $classes;
	}

	protected function get_default_args() {
		return array(
			'cookie_notice'		=> false,
			'legal_generator'	=> false,
		);
	}
}
