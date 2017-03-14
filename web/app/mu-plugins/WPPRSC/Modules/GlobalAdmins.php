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
			add_filter( 'networks_user_is_network_admin', array( $this, 'user_has_networks' ), 10, 2 );
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
			case 'manage_git':
			case 'manage_networks':
			case 'create_networks':
			case 'delete_networks':
			case 'delete_network':
			case 'manage_global_users':
				if ( ! $this->is_global_admin( $user_id ) ) {
					$caps[] = 'do_not_allow';
				}
				break;
			case 'edit_user':
				if ( ! $this->is_global_admin( $user_id ) && isset( $args[0] ) && $this->is_global_admin( $args[0] ) ) {
					$caps[] = 'do_not_allow';
				}
				break;
		}

		return $caps;
	}

	public function map_meta_cap_non_multisite( $caps, $cap, $user_id, $args ) {
		switch ( $cap ) {
			case 'manage_cache':
				if ( $this->is_global_admin( $user_id ) ) {
					$caps = array( 'manage_options' );
				}
				break;
		}

		return $caps;
	}

	public function user_has_networks( $networks, $user_id ) {
		global $wpdb;

		$all_networks = $wpdb->get_col( "SELECT id FROM {$wpdb->site}" );
		if ( $this->is_global_admin( $user_id ) ) {
			$user_networks = $all_networks;
		} else {
			$user_networks = array();
			foreach ( $all_networks as $network_id ) {
				if ( $this->is_network_admin( $user_id, $network_id ) ) {
					$user_networks[] = (int) $network_id;
				}
			}
		}

		if ( empty( $user_networks ) ) {
			$user_networks = false;
		}

		return $user_networks;
	}

	public function pre_user_query( &$user_query ) {
		global $wpdb;

		if ( current_user_can( 'manage_global_users' ) ) {
			return;
		}

		if ( 0 < absint( $user_query->query_vars['blog_id'] ) ) {
			return;
		}

		$network_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->site}" );
		if ( 1 >= $network_count ) {
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

	private function is_network_admin( $user_id, $network_id = null ) {
		$network_admins = $this->get_network_admins( $network_id );
		if ( empty( $network_admins ) ) {
			return false;
		}

		$user = get_user_by( 'id', $user_id );
		return $user && $user->exists() && in_array( $user->user_login, $network_admins, true );
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
