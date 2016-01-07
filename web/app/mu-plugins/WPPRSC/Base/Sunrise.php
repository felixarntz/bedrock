<?php
/**
 * Handles site and network detection.
 *
 * This class is invoked in sunrise.php.
 * Note that the variables in this code use the modern Multisite terminology (networks of sites).
 */

namespace WPPRSC\Base;

class Sunrise extends \WPPRSC\BaseAbstract {
	protected function __construct() {
	}

	public function run() {
		global $wpdb;

		if ( ! is_multisite() ) {
			// skip if not a multisite
			return;
		}

		if ( ! is_subdomain_install() ) {
			// die if a subdirectory install
			wp_die( 'This multisite does not support a subdirectory installation.', 'Multisite Error', array( 'response' => 500 ) );
			exit;
		}

		$domain = $this->get_current_domain();

		$domains = array( $domain );
		if ( 0 === strpos( $domain, 'www.' ) ) {
			$domains[] = substr( $domain, 4 );
		} elseif ( 1 === substr_count( $domain, '.' ) ) {
			$domains[] = 'www.' . $domain;
		}

		$site = $this->detect_site( $domains );
		if ( $site ) {
			if ( $domain !== $site->domain ) {
				$this->redirect( $site->domain );
				exit;
			}

			if ( empty( $site->site_id ) ) {
				$site->site_id = 1;
			}

			$network = $this->detect_network( $site );
		} else {
			// try to detect network another way if no site is found
			$network = $this->detect_network( $domains );
			if ( $network && $domain !== $network->domain ) {
				$this->redirect( $network->domain );
				exit;
			}

			if ( wp_installing() ) {
				// create dummy site if we're installing
				$site = new \stdClass();
				$site->blog_id = 1;
				$site->site_id = 1;
				$site->domain = '';
				$site->path = '/';
				$site->public = 1;
			}
		}

		if ( ! $network ) {
			$this->fail_gracefully( $domain, 'network' );
			exit;
		}

		if ( ! $site ) {
			$this->fail_gracefully( $domain, 'site' );
			exit;
		}

		// detect the network's main site ID if not set yet
		if ( empty( $network->blog_id ) ) {
			if ( $site->domain === $network->domain && $site->path === $network->path ) {
				$network->blog_id = $site->blog_id;
			} elseif ( ! ( $network->blog_id = wp_cache_get( 'network:' . $network->id . ':main_site', 'site-options' ) ) ) {
				$network->blog_id = $wpdb->get_var( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE domain = %s AND path = %s;", $network->domain, $network->path ) );
				wp_cache_add( 'network:' . $network->id . ':main_site', $network->blog_id, 'site-options' );
			}
		}

		// if we reach this point, everything has been detected successfully
		$this->expose_globals( $site, $network );
	}

	private function detect_site( $domains = array() ) {
		global $wpdb;

		$search_domains = "'" . implode( "','", $wpdb->_escape( $domains ) ) . "'";

		$site = $wpdb->get_row( "SELECT * FROM $wpdb->blogs WHERE domain IN ($domains) AND path = '/' ORDER BY CHAR_LENGTH(domain) DESC, CHAR_LENGTH(path) DESC LIMIT 1;" );
		if ( ! empty( $site ) && ! is_wp_error( $site ) ) {
			return $site;
		}

		return false;
	}

	private function detect_network( $domains_or_site = array() ) {
		global $wpdb;

		if ( is_object( $domains_or_site ) && isset( $domains_or_site->site_id ) ) {
			return WP_Network::get_instance( $domains_or_site->site_id );
		}

		$search_domains = "'" . implode( "','", $wpdb->_escape( $domains_or_site ) ) . "'";

		$network = $wpdb->get_row( "SELECT * FROM $wpdb->site WHERE domain IN ($domains) AND path = '/' ORDER BY CHAR_LENGTH(domain) DESC, CHAR_LENGTH(path) DESC LIMIT 1;" );
		if ( ! empty( $network ) && ! is_wp_error( $network ) ) {
			return new WP_Network( $network );
		}

		return false;
	}

	private function fail_gracefully( $domain, $mode = 'site' ) {
		if ( 'network' === $mode ) {
			do_action( 'ms_network_not_found', $domain, '/' );
		} elseif ( defined( 'NOBLOGREDIRECT' ) && '%siteurl%' !== NOBLOGREDIRECT ) {
			header( 'Location: ' . NOBLOGREDIRECT );
			exit;
		}

		ms_not_installed( $domain, '/' );
	}

	private function expose_globals( $site, $network ) {
		global $current_blog, $current_site, $blog_id, $site_id, $public;

		$current_blog = $site;
		$current_site = $network;

		$blog_id = $site->blog_id;
		$site_id = $site->site_id;

		$public = $site->public;

		wp_load_core_site_options( $site_id );
	}

	private function redirect( $domain ) {
		$scheme = is_ssl() ? 'https' : 'http';
		$path = $this->get_current_path();

		header( 'Location: ' . $scheme . '://' . $domain . $path, true, 301 );
		exit;
	}

	private function get_current_domain() {
		$domain = strtolower( stripslashes( $_SERVER['HTTP_HOST'] ) );
		if ( ':80' === substr( $domain, -3 ) ) {
			$domain = substr( $domain, 0, -3 );
			$_SERVER['HTTP_HOST'] = substr( $_SERVER['HTTP_HOST'], 0, -3 );
		} elseif ( ':443' === substr( $domain, -4 ) ) {
			$domain = substr( $domain, 0, -4 );
			$_SERVER['HTTP_HOST'] = substr( $_SERVER['HTTP_HOST'], 0, -4 );
		}

		return $domain;
	}

	private function get_current_path() {
		return stripslashes( $_SERVER['REQUEST_URI'] );
	}
}
