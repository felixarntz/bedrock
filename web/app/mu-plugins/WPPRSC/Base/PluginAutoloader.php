<?php
/**
 * Made from Bedrock Autoloader
 */

namespace WPPRSC\Base;

class PluginAutoloader extends \WPPRSC\BaseAbstract {
	protected $cache;
	protected $auto_plugins;
	protected $mu_plugins;
	protected $count;
	protected $activated;
	protected $relative_path;

	protected function __construct() {
	}

	public function run() {
		if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
			return;
		}

		$this->relative_path = '/../' . basename( untrailingslashit( wpprsc_get_path() ) );

		if ( is_admin() ) {
			add_filter( 'show_advanced_plugins', array( $this, 'show_in_admin' ), 0, 2 );
		}

		$this->load_plugins();
	}

	public function load_plugins() {
		$this->check_cache();
		$this->validate_plugins();
		$this->count_plugins();

		foreach ( $this->cache['plugins'] as $plugin_file => $plugin_info ) {
			include_once WPMU_PLUGIN_DIR . '/' . $plugin_file;
		}

		$this->plugin_hooks();
	}

	public function show_in_admin( $bool, $type ) {
		$screen = get_current_screen();

		$current = is_multisite() ? 'plugins-network' : 'plugins';

		if ( $screen->base != $current || $type != 'mustuse' || ! current_user_can( 'activate_plugins' ) ) {
			return $bool;
		}

		$this->update_cache();

		$this->auto_plugins = array_map( function( $auto_plugin ) {
			$auto_plugin['Name'] .= ' *';
			return $auto_plugin;
		}, $this->auto_plugins );

		$GLOBALS['plugins']['mustuse'] = array_unique( array_merge( $this->auto_plugins, $this->mu_plugins ), SORT_REGULAR );

		return false;
	}

	protected function check_cache() {
		$cache = get_site_option( 'wpprsc_plugin_autoloader' );

		if ( $cache === false ) {
			return $this->update_cache();
		}

		$this->cache = $cache;
	}

	protected function update_cache() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$this->auto_plugins = get_plugins( $this->relative_path );
		$this->mu_plugins = get_mu_plugins( $this->relative_path );
		$plugins = array_diff_key( $this->auto_plugins, $this->mu_plugins );
		$rebuild = ! isset( $this->cache['plugins'] ) || ! is_array( $this->cache['plugins'] );
		$this->activated = $rebuild ? $plugins : array_diff_key( $plugins, $this->cache['plugins'] );
		$this->cache = array( 'plugins' => $plugins, 'count' => $this->count_plugins() );

		update_site_option( 'wpprsc_plugin_autoloader', $this->cache );
	}

	protected function plugin_hooks() {
		if ( ! is_array( $this->activated ) ) {
			return;
		}

		foreach ( $this->activated as $plugin_file => $plugin_info ) {
			do_action( 'activate_' . $plugin_file );
		}
	}

	protected function validate_plugins() {
		foreach ( $this->cache['plugins'] as $plugin_file => $plugin_info ) {
			if ( ! file_exists( WPMU_PLUGIN_DIR . '/' . $plugin_file ) ) {
				$this->update_cache();
				break;
			}
		}
	}

	protected function count_plugins() {
		if ( isset( $this->count ) ) {
			return $this->count;
		}

		$count = count( glob( WPMU_PLUGIN_DIR . '/*/', GLOB_ONLYDIR | GLOB_NOSORT ) );

		if ( ! isset( $this->cache['count'] ) || $count != $this->cache['count'] ) {
			$this->count = $count;
			$this->update_cache();
		}

		return $this->count;
	}
}
