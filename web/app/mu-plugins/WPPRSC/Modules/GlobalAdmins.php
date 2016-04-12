<?php
/**
 * Handles super admins vs global admins (i.e. admins that have power across all networks).
 */

namespace WPPRSC\Modules;

class GlobalAdmins extends \WPPRSC\ModuleAbstract {
	protected function __construct( $args = array() ) {
		parent::__construct( $args );

		$this->module_name = 'global_admins';
		$this->network_only = true;
	}

	public function run() {
		if ( is_multisite() ) {
			$this->set_super_admins();
			add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
			add_action( 'pre_user_query', array( $this, 'pre_user_query' ), 10, 1 );
		} else {
			add_filter( 'map_meta_cap', array( $this, 'map_meta_cap_non_multisite' ), 10, 4 );
		}
	}

	public function set_super_admins() {
		global $super_admins;

		$current_network = get_current_site();

		$super_admins = array_unique( array_merge( $this->get_global_admins(), $this->get_network_admins( $current_network->id ) ) );
	}

	public function map_meta_cap( $caps, $cap, $user_id, $args ) {
		switch ( $cap ) {
			case 'manage_cache':
			case 'manage_global_users':
				if ( ! mnga_is_global_admin( $user_id ) ) {
					$caps[] = 'do_not_allow';
				}
				break;
			case 'edit_user':
				if ( ! mnga_is_global_admin( $user_id ) && isset( $args[0] ) && mnga_is_global_admin( $args[0] ) ) {
					$caps[] = 'do_not_allow';
				}
				break;
		}

		return $caps;
	}

	public function map_meta_cap_non_multisite( $caps, $cap, $user_id, $args ) {
		switch ( $cap ) {
			case 'manage_cache':
				if ( mnga_is_global_admin( $user_id ) ) {
					$caps = array( 'manage_options' );
				}
				break;
		}

		return $caps;
	}

	public function pre_user_query( &$user_query ) {
		global $wpdb;

		if ( current_user_can( 'manage_global_users' ) ) {
			return;
		}

		if ( 0 < absint( $user_query->query_vars['blog_id'] ) ) {
			return;
		}

		$sites = wp_get_sites();

		$site_queries = array();
		foreach ( $sites as $site ) {
			$site_queries[] = array(
				'key'     => $wpdb->get_blog_prefix( $site->blog_id ) . 'capabilities',
				'compare' => 'EXISTS',
			);
		}

		$site_queries['relation'] = 'OR';

		if ( empty( $user_query->meta_query->queries ) ) {
			$user_query->meta_query->queries = $site_queries;
		} else {
			$user_query->meta_query->queries = array(
				'relation' => 'AND',
				array( $user_query->meta_query->queries, $site_queries ),
			);
		}
	}

	private function is_global_admin( $user_id ) {
		$global_admins = $this->get_global_admins();
		if ( empty( $global_admins ) ) {
			return false;
		}

		$user = get_user_by( 'id', $user_id );
		return in_array( $user->user_login, $global_admins, true );
	}

	private function get_global_admins() {
		$user_logins = $this->get_setting( 'global_admins' );
		if ( ! $user_logins ) {
			return array();
		}

		if ( ! is_array( $user_logins ) ) {
			$user_logins = array_map( 'trim', explode( ',', $user_logins ) );
		}

		return array_filter( $user_logins );
	}

	private function get_network_admins( $network_id ) {
		$user_logins = $this->get_setting( 'network_admins' );
		if ( ! is_array( $user_logins ) || ! isset( $user_logins[ $network_id ] ) ) {
			return array();
		}

		$user_logins = $user_logins[ $network_id ];

		if ( ! $user_logins ) {
			return array();
		}

		if ( ! is_array( $user_logins ) ) {
			$user_logins = array_map( 'trim', explode( ',', $user_logins ) );
		}

		return array_filter( $user_logins );
	}

	protected function get_default_args() {
		return array(
			'global_admins'		=> '',
			'network_admins'	=> array(
				'1'					=> '',
			),
		);
	}
}
