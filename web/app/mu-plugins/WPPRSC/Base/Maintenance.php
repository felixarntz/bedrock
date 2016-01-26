<?php
/**
 * Handles maintenance page (called from /app/maintenance.php)
 */

namespace WPPRSC\Base;

class Maintenance extends \WPPRSC\BaseAbstract {
	protected $type = ''; // either 'update' or 'db'

	protected function __construct() {
	}

	public function run( $type ) {
		$this->type = $type;

		if ( defined( 'DOING_MAINTENANCE' ) ) {
			return;
		}

		define( 'DOING_MAINTENANCE', true );

		$this->load_early();
		$this->render();

		die();
	}

	protected function load_early() {
		if ( 'db' !== $this->type ) {
			wp_load_translations_early();
		}
	}

	protected function render() {
		$template = $this->get_template();

		$data = $this->setup_data( $template );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX || defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			wp_die( $data['description'] );
			return;
		}

		$this->send_headers();

		if ( $template ) {
			require $template;
		}
	}

	protected function setup_data( $template ) {
		$data = array();

		$language_attributes = array();
		if ( function_exists( 'is_rtl' ) && is_rtl() ) {
			$language_attributes[] = 'dir="rtl"';
		}
		if ( function_exists( 'get_locale' ) ) {
			$language_attributes[] = 'lang="' . str_replace( '_', '-', get_locale() ) . '"';
		}

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

		$data['language_attributes'] = implode( ' ', $language_attributes );

		$data['title'] = __( 'Maintenance' );

		if ( 'db' === $this->type ) {
			$data['description'] = __( 'Error establishing a database connection' );
		} else {
			$data['description'] = __( 'Briefly unavailable for scheduled maintenance. Check back in a minute.' );
		}

		return $data;
	}

	protected function get_template() {
		$locations = array();

		if ( defined( 'WP_DEFAULT_THEME' ) && WP_DEFAULT_THEME ) {
			$locations[] = WP_CONTENT_DIR . '/themes/' . WP_DEFAULT_THEME . '/maintenance.php';
		}

		if ( defined( 'WP_DEFAULT_MAINTENANCE_TEMPLATE' ) && WP_DEFAULT_MAINTENANCE_TEMPLATE && 'maintenance.php' !== ltrim( WP_DEFAULT_MAINTENANCE_TEMPLATE, '/' ) ) {
			$locations[] = WP_CONTENT_DIR . '/' . ltrim( WP_DEFAULT_MAINTENANCE_TEMPLATE, '/' );
		}

		$locations[] = wpprsc_get_path( 'templates/maintenance.php' );

		foreach ( $locations as $location ) {
			if ( file_exists( $location ) ) {
				return $location;
			}
		}

		return false;
	}

	protected function send_headers() {
		$protocol = wp_get_server_protocol();

		$retry_interval = 600;
		if ( 'db' === $this->type ) {
			$retry_interval = 3600;
		}

		header( $protocol . ' 503 Service Unavailable', true, 503 );
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'Retry-After: ' . $retry_interval );
	}
}
