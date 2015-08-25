<?php
/**
 * Handles everything that is usually done by wp-config.php
 */

namespace WPPRSC\Base;

class Config extends \WPPRSC\Abstract {
	/**
	 * Data required for constants definition
	 * @var array
	 */
	protected $data;

	/**
	 * Information about project (read from Composer)
	 * @var array
	 */
	protected $info;

	/**
	 * Additional settings (read from Composer)
	 * @var array
	 */
	protected $settings;

	protected function __construct() {
	}

	public function run() {
		$this->data = $this->info = $this->settings = array();

		$this->data['content_dir'] = str_replace( '/mu-plugins/WPPRSC/Base', '', dirname( __FILE__ ) );
		$this->data['webroot_dir'] = dirname( $this->data['content_dir'] );
		$this->data['root_dir'] = dirname( $this->data['webroot_dir'] );

		$this->data['server_protocol'] = 'http';
		if ( ( isset( $_SERVER['https'] ) && ! empty( $_SERVER['https'] ) && $_SERVER['https'] !== 'off' ) || $_SERVER['SERVER_PORT'] == '443' ) {
			$this->data['server_protocol'] = 'https';
		}
		$this->data['server_name'] = $_SERVER['SERVER_NAME'];
		$this->data['server_port'] = '';
		if ( ! in_array( $_SERVER['SERVER_PORT'], array( '80', '443' ) ) ) {
			$this->data['server_port'] = $_SERVER['SERVER_PORT'];
		}

		$this->data['server_url'] = $this->data['server_protocol'] . '://' . $this->data['server_name'] . ( ! empty( $this->data['server_port'] ) ? ':' . $this->data['server_port'] : '' );

		$this->data['required'] = $this->get_required_constants();
		$this->data['protected'] = $this->get_protected_constants();

		$this->load_dotenv();
		$this->load_composer();

		$this->data['wp_env'] = $this->get_constant_setting( 'WP_ENV' );
		if ( $this->data['wp_env'] === false || ! in_array( $this->data['wp_env'], array( 'production', 'staging', 'development' ) ) ) {
			$this->data['wp_env'] = 'production';
		}

		$this->define_constants();

		$this->apply_globals();
	}

	public function get_info( $field = null ) {
		if ( $field !== null ) {
			if ( isset( $this->info[ $field ] ) ) {
				return $this->info[ $field ];
			}
			return false;
		}

		return $this->info;
	}

	public function get_setting( $key, $default = false ) {
		if ( isset( $this->settings[ $setting ] ) ) {
			return $this->settings[ $setting ];
		}
		return $default;
	}

	protected function load_dotenv() {
		$dotenv = new \Dotenv\Dotenv( $this->data['root_dir'] );
		if ( file_exists( $this->data['root_dir'] . '/.env' ) ) {
			$dotenv->load();
			$dotenv->required( $this->data['required'] );
		}
	}

	protected function load_composer() {
		$composer = array();
		try {
			$_composer = file_get_contents( $this->data['root_dir'] . '/composer.json' );
			$composer = json_decode( $_composer, true );
		} catch ( \Exception $e ) {

		}

		$info_fields = array(
			'name', 'version', 'description', 'type', 'license', 'homepage', 'authors', 'keywords'
		);
		foreach ( $info_fields as $info_field ) {
			if ( isset( $composer[ $info_field ] ) ) {
				$this->info[ $info_field ] = $composer[ $info_field ];
			}
		}

		$this->data['composer'] = array();
		if ( isset( $composer['extra'] ) ) {
			if ( isset( $composer['extra']['constants'] ) ) {
				foreach ( $composer['extra']['constants'] as $constant => $value ) {
					if ( ! is_array( $value ) ) {
						$this->data['composer'][ $this->normalize_constant( $constant ) ] = $value;
					}
				}
			}

			if ( isset( $composer['extra']['settings'] ) ) {
				foreach ( $composer['extra']['settings'] as $setting => $value ) {
					$this->settings[ $setting ] = $this->normalize_value( $value );
				}
			}
		}
	}

	protected function define_constants() {
		$constants = $this->get_default_constants();

		foreach ( $constants as $constant => $default ) {
			if ( ! in_array( $constant, $this->data['protected'] ) ) {
				$value = $this->get_constant_setting( $constant );
				if ( $value !== false ) {
					$value = $this->normalize_value( $value );
					define( $constant, $value );
				}
			}

			if ( ! defined( $constant ) && $default !== null ) {
				if ( is_string( $default ) ) {
					$default = preg_replace_callback( '/\{\{([A-Z_]+)\}\}/', function( $matches ) {
						if ( isset( $matches[1] ) && defined( $matches[1] ) ) {
							return constant( $matches[1] );
						}
						return '';
					}, $default );
				}
				define( $constant, $default );
			}
		}
	}

	protected function apply_globals() {
		global $table_prefix;

		$table_prefix = DB_PREFIX;
	}

	protected function get_required_constants() {
		return array(
			'DB_NAME',
			'DB_USER',
			'DB_PASSWORD',
		);
	}

	protected function get_protected_constants() {
		return array(
			'WP_SITEURL',
			'WP_CONTENT_DIR',
			'WP_CONTENT_URL',
			'WP_DEBUG',
			'WP_DEBUG_LOG',
			'WP_DEBUG_DISPLAY',
			'SCRIPT_DEBUG',
			'SAVEQUERIES',
			'ABSPATH',
		);
	}

	protected function get_default_constants() {
		return array(
			// Database Settings
			'DB_NAME'						=> 'wp',
			'DB_USER'						=> 'root',
			'DB_PASSWORD'					=> '',
			'DB_HOST'						=> 'localhost',
			'DB_PREFIX'						=> 'wp_',
			'DB_CHARSET'					=> 'utf8',
			'DB_COLLATE'					=> '',
			// Salt Keys
			'AUTH_KEY'						=> '',
			'SECURE_AUTH_KEY'				=> '',
			'LOGGED_IN_KEY'					=> '',
			'NONCE_KEY'						=> '',
			'AUTH_SALT'						=> '',
			'SECURE_AUTH_SALT'				=> '',
			'LOGGED_IN_SALT'				=> '',
			'NONCE_SALT'					=> '',
			// Site Data
			'WPLANG'						=> '',
			'WP_HOME'						=> $this->data['server_url'],
			'WP_SITEURL'					=> '{{WP_HOME}}/core',
			'WP_CONTENT_DIR'				=> $this->data['content_dir'],
			'WP_CONTENT_URL'				=> '{{WP_HOME}}/' . basename( $this->data['content_dir'] ),
			'FORCE_SSL_ADMIN'				=> 'https' === $this->data['server_protocol'],
			'FORCE_SSL_LOGIN'				=> 'https' === $this->data['server_protocol'],
			// Environment
			'WP_ENV'						=> $this->data['wp_env'],
			'WP_DEBUG'						=> 'production' !== $this->data['wp_env'],
			'WP_DEBUG_LOG'					=> 'production' !== $this->data['wp_env'],
			'WP_DEBUG_DISPLAY'				=> 'development' === $this->data['wp_env'],
			'SCRIPT_DEBUG'					=> 'development' === $this->data['wp_env'],
			'SAVEQUERIES'					=> 'development' === $this->data['wp_env'],
			// Custom Settings
			'DISALLOW_FILE_EDIT'			=> true,
			'DISALLOW_FILE_MODS'			=> true,
			'AUTOMATIC_UPDATER_DISABLED'	=> true,
			'AUTOSAVE_INTERVAL'				=> null,
			'COMPRESS_CSS'					=> null,
			'COMPRESS_SCRIPTS'				=> null,
			'CONCATENATE_SCRIPTS'			=> null,
			'CORE_UPGRADE_SKIP_NEW_BUNDLED'	=> null,
			'DISABLE_WP_CRON'				=> null,
			'EMPTY_TRASH_DAYS'				=> null,
			'ENFORCE_GZIP'					=> null,
			'IMAGE_EDIT_OVERWRITE'			=> null,
			'MEDIA_TRASH'					=> null,
			'WP_CACHE'						=> null,
			'WP_DEFAULT_THEME'				=> null,
			'WP_CRON_LOCK_TIMEOUT'			=> null,
			'WP_MAIL_INTERVAL'				=> null,
			'WP_POST_REVISIONS'				=> null,
			'WP_MAX_MEMORY_LIMIT'			=> null,
			'WP_MEMORY_LIMIT'				=> null,
			// Multisite
			'WP_ALLOW_MULTISITE'			=> null,
			'MULTISITE'						=> null,
			'ALLOW_SUBDIRECTORY_INSTALL'	=> null,
			'SUBDOMAIN_INSTALL'				=> null,
			'SUNRISE'						=> null,
			// WordPress Bootstrap
			'ABSPATH'						=> $this->data['webroot_dir'] . '/core/'
		);
	}

	protected function get_constant_setting( $name ) {
		switch ( true ) {
			case array_key_exists( $name, $_ENV ):
				return $_ENV[ $name ];
			case array_key_exists( $name, $_SERVER ):
				return $_SERVER[ $name ];
			case getenv( $name ) !== false:
				return getenv( $name );
			case array_key_exists( $name, $this->data['composer'] ):
				return $this->data['composer'][ $name ];
			default:
				return false;
		}
	}

	protected function normalize_constant( $constant ) {
		return strtoupper( str_replace( array( '-', ' ' ), '_', $constant ) );
	}

	protected function normalize_value( $value ) {
		if ( is_array( $value ) ) {
			$normalized = array();
			foreach ( $value as $k => $v ) {
				$normalized[ $k ] = $this->normalize_value( $v );
			}
			return $normalized;
		}

		switch ( $value ) {
			case 'TRUE':
			case 'true':
				return true;
			case 'FALSE':
			case 'false':
				return false;
			default:
				if ( is_numeric( $value ) ) {
					if ( intval( $value ) == floatval( $value ) ) {
						return intval( $value );
					}
					return floatval( $value );
				}
				return $value;
		}
	}
}
