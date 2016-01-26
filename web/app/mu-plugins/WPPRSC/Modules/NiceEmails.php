<?php
/**
 * Sends nice HTML emails with customizable design instead of boring plain text.
 */

namespace WPPRSC\Modules;

use WPPRSC\Base\Config;

class NiceEmails extends \WPPRSC\ModuleAbstract {
	protected $current_adjustment_mode = 'none';

	protected function __construct( $args = array() ) {
		parent::__construct( $args );

		$this->module_name = 'nice_emails';
	}

	public function run() {
		add_filter( 'wp_mail', array( $this, 'content' ), 1000 );
		add_filter( 'wp_mail_content_type', array( $this, 'content_type' ), 1000 );
		add_filter( 'wp_mail_from', array( $this, 'from_email' ), 1000 );
		add_filter( 'wp_mail_from_name', array( $this, 'from_name' ), 1000 );
	}

	public function content( $args = array() ) {
		if ( isset( $args['message'] ) && ( false !== strpos( $args['message'], '</body>' ) && ( false !== strpos( $args['message'], '</p>' ) || false !== strpos( $args['message'], '</table>' ) ) ) {
			$this->current_adjustment_mode = 'normal';

			$template = $this->get_template();

			$data = $this->setup_data( $args, $template );

			if ( $template ) {
				ob_start();
				require $template;
				$args['message'] = ob_get_clean();
			}
		} else {
			$this->current_adjustment_mode = 'none';
		}

		return $args;
	}

	public function content_type( $content_type = 'text/plain' ) {
		if ( 'normal' === $this->current_adjustment_mode ) {
			$content_type = 'text/html';
		}
		return $content_type;
	}

	public function from_email( $from_email = '' ) {
		if ( 0 === strpos( $from_email, 'wordpress@' ) || $this->get_setting( 'force_from' ) ) {
			if ( $option = $this->get_setting( 'from_email' ) ) {
				$from_email = $option;
			} else {
				$from_email = $this->get_setting( 'default_from_prefix' ) . '@' . str_replace( array( 'http://', 'https://' ), '', get_bloginfo( 'url' ) );
			}
		}
		return $from_email;
	}

	public function from_name( $from_name = '' ) {
		if ( 'WordPress' === $from_name || $this->get_setting( 'force_from' ) ) {
			if ( $option = $this->get_setting( 'from_name' ) ) {
				$from_name = $option;
			} else {
				$from_name = get_bloginfo( 'name' );
			}
		}
		return $from_name;
	}

	protected function setup_data( $args, $template ) {
		$data = array();

		if ( ! defined( 'WP_CONTENT_URL' ) ) {
			$server_url = Config::get_current_protocol() . '://' . Config::get_current_domain();
			if ( $server_port = Config::get_current_port() ) {
				$server_url .= ':' . $server_port;
			}
			define( 'WP_CONTENT_URL', $server_url . '/' . basename( WP_CONTENT_DIR ) );
		}

		if ( $template ) {
			$data['basedir'] = dirname( $template );
			$data['baseurl'] = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $data['basedir'] );
		} else {
			$data['basedir'] = false;
			$data['baseurl'] = false;
		}

		if ( isset( $args['subject'] ) ) {
			$data['title'] = $args['subject'] . ' - ' . get_bloginfo( 'name' );
			$data['headline'] = $args['subject'];
		} else {
			$data['title'] = get_bloginfo( 'name' );
			$data['headline'] = get_bloginfo( 'name' );
		}
		$data['header_image'] = $this->get_setting( 'header_image' );
		$data['main_content'] = $this->prepare_content( $args['message'] );
		$data['footer_content'] = $this->prepare_content( $this->get_setting( 'footer_content' ) );

		$data['styles'] = 'a { text-decoration: none !important; } a:hover, a:focus { text-decoration: underline !important; }';
		if ( $this->get_setting( 'highlight_color' ) ) {
			$data['styles'] .= ' a, a:hover, a:focus { color: ' . $this->get_setting( 'highlight_color' ) . ' !important; }';
		}

		return $data;
	}

	protected function get_template() {
		$locations = array();

		$locations[] = get_stylesheet_directory() . '/email.php';

		if ( is_child_theme() ) {
			$locations[] = get_template_directory() . '/email.php';
		}

		if ( defined( 'WP_DEFAULT_THEME' ) && WP_DEFAULT_THEME ) {
			$locations[] = WP_CONTENT_DIR . '/themes/' . WP_DEFAULT_THEME . '/email.php';
		}

		if ( defined( 'WP_DEFAULT_EMAIL_TEMPLATE' ) && WP_DEFAULT_EMAIL_TEMPLATE ) {
			$locations[] = WP_CONTENT_DIR . '/' . ltrim( WP_DEFAULT_EMAIL_TEMPLATE, '/' );
		}

		$locations[] = wpprsc_get_path( 'templates/email.php' );

		foreach ( $locations as $location ) {
			if ( file_exists( $location ) ) {
				return $location;
			}
		}

		return false;
	}

	protected function prepare_content( $content = '' ) {
		if ( ! is_string( $content ) ) {
			return '';
		}

		$content = wpautop( $content );

		if ( '<p>' === substr( $content, 0, 3 ) ) {
			$content = '<p style="margin-top: 0 !important">' . substr( $content, 3 );
		}

		return $content;
	}

	protected function get_default_args() {
		return array(
			'enabled'				=> false,
			'force_from'			=> false,
			'default_from_prefix'	=> 'wordpress',
			'header_image'			=> false,
			'highlight_color'		=> '#21759b',
			'footer_content'		=> '',
			'from_email'			=> false,
			'from_name'				=> false,
		);
	}
}
