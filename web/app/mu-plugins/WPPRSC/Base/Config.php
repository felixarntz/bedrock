<?php
/**
 * Handles everything that is usually done by wp-config.php
 */

namespace WPPRSC\Base;

class Config extends \WPPRSC\BaseAbstract {
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

		$this->data['server_protocol'] = self::get_current_protocol();
		$this->data['server_name'] = self::get_current_domain();
		$this->data['server_port'] = self::get_current_port();

		$this->data['server_url'] = $this->data['server_protocol'] . '://' . $this->data['server_name'] . ( ! empty( $this->data['server_port'] ) ? ':' . $this->data['server_port'] : '' );

		$this->data['required'] = $this->get_required_constants();
		$this->data['protected'] = $this->get_protected_constants();

		$this->load_dotenv();
		$this->load_composer();

		$this->data['wp_env'] = $this->get_constant_setting( 'WP_ENV' );
		if ( false === $this->data['wp_env'] || ! in_array( $this->data['wp_env'], array( 'production', 'staging', 'development' ), true ) ) {
			$this->data['wp_env'] = 'production';
		}

		$this->define_constants();

		$this->apply_globals();

		$this->maybe_redirect_https();
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
		if ( isset( $this->settings[ $key ] ) ) {
			return $this->settings[ $key ];
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
				$this->info[ $info_field ] = $composer[ $info_field ];
			}
		}

		$this->data['composer'] = array();
		if ( isset( $composer['extra'] ) ) {
			if ( isset( $composer['extra']['constants'] ) ) {
				$constants = $this->normalize_constants( $composer['extra']['constants'] );
				foreach ( $constants as $constant => $value ) {
					if ( ! is_array( $value ) ) {
						$this->data['composer'][ $constant ] = $value;
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
			if ( ! in_array( $constant, $this->data['protected'], true ) ) {
				$value = $this->get_constant_setting( $constant );
				if ( null !== $value ) {
					$value = $this->normalize_value( $value );
					define( $constant, $value );
				}
			} elseif ( defined( $constant ) ) {
				die( sprintf( 'The constant %s must not be defined.', $constant ) );
			}

			if ( ! defined( $constant ) && null !== $default ) {
				define( $constant, $default );
			}
		}

		foreach ( $_ENV as $constant => $value ) {
			if ( false === strpos( $constant, '-' ) && strtoupper( $constant ) === $constant ) {
				if ( ! in_array( $constant, $this->data['protected'], true ) && ! defined( $constant ) ) {
					define( $constant, $this->normalize_value( $value ) );
				}
			}
		}

		foreach ( $this->data['composer'] as $constant => $value ) {
			if ( ! in_array( $constant, $this->data['protected'], true ) && ! defined( $constant ) ) {
				define( $constant, $this->normalize_value( $value ) );
			}
		}

		define( 'WP_CONTENT_DIR', $this->data['content_dir'] );

		if ( defined( 'WP_ALLOW_MULTISITE' ) && WP_ALLOW_MULTISITE || defined( 'MULTISITE' ) && MULTISITE ) {
			define( 'SUBDOMAIN_INSTALL', true );
			define( 'ALLOW_SUBDIRECTORY_INSTALL', false );
			define( 'COOKIEPATH', '/' );
			define( 'SITECOOKIEPATH', '/' );
			define( 'ADMIN_COOKIE_PATH', '/wp-admin' );
			define( 'COOKIE_DOMAIN', '' );
			define( 'SUNRISE', true );
			// WP_HOME, WP_SITEURL and WP_CONTENT_URL on multisite are handled by the Sunrise class
		} else {
			if ( ! defined( 'WP_HOME' ) ) {
				define( 'WP_HOME', $this->data['server_url'] );
			}
			define( 'WP_SITEURL', WP_HOME . '/core' );
			define( 'WP_CONTENT_URL', WP_HOME . '/' . basename( WP_CONTENT_DIR ) );
		}

		if ( defined( 'WP_HOME' ) && 0 === strpos( WP_HOME, 'https://' ) || self::is_ssl( $this->data['server_name'] ) ) {
			define( 'FORCE_SSL_ADMIN', true );
			define( 'FORCE_SSL_LOGIN', true );
		}
	}

	protected function apply_globals() {
		global $table_prefix;

		$table_prefix = DB_PREFIX;
	}

	protected function maybe_redirect_https() {
		if ( self::is_ssl( $this->data['server_name'] ) && 'http' === $this->data['server_protocol'] ) {
			header( 'Location: https://' . $this->data['server_name'] . Config::get_current_path(), true, 301 );
			exit;
		}
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
			'FORCE_SSL_ADMIN',
			'FORCE_SSL_LOGIN',
			'WP_DEBUG',
			'WP_DEBUG_LOG',
			'WP_DEBUG_DISPLAY',
			'SCRIPT_DEBUG',
			'SAVEQUERIES',
			'ABSPATH',
			'SUBDOMAIN_INSTALL',
			'ALLOW_SUBDIRECTORY_INSTALL',
			'DOMAIN_CURRENT_SITE',
			'PATH_CURRENT_SITE',
			'SITE_ID_CURRENT_SITE',
			'BLOG_ID_CURRENT_SITE',
			'COOKIEPATH',
			'SITECOOKIEPATH',
			'ADMIN_COOKIE_PATH',
			'COOKIE_DOMAIN',
			'SUNRISE',
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
			'WP_HOME'						=> null,
			'WP_SITEURL'					=> null,
			'WP_CONTENT_DIR'				=> null,
			'WP_CONTENT_URL'				=> null,
			'WP_SSL_GLOBAL'					=> null,
			'WP_SSL_DOMAINS'				=> null,
			'FORCE_SSL_ADMIN'				=> null,
			'FORCE_SSL_LOGIN'				=> null,
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
			'ALLOW_UNFILTERED_UPLOADS'		=> null,
			// Multisite
			'WP_ALLOW_MULTISITE'			=> null,
			'MULTISITE'						=> null,
			'NOBLOGREDIRECT'				=> null,
			// Multisite Advanced
			'SUBDOMAIN_INSTALL'				=> null,
			'ALLOW_SUBDIRECTORY_INSTALL'	=> null,
			'DOMAIN_CURRENT_SITE'			=> null,
			'PATH_CURRENT_SITE'				=> null,
			'SITE_ID_CURRENT_SITE'			=> null,
			'BLOG_ID_CURRENT_SITE'			=> null,
			'COOKIEPATH'					=> null,
			'SITECOOKIEPATH'				=> null,
			'ADMIN_COOKIE_PATH'				=> null,
			'COOKIE_DOMAIN'					=> null,
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
				return null;
		}
	}

	protected function normalize_constants( $constants ) {
		return array_map( array( $this, 'normalize_constant' ), $this->flatten( $constants ) );
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

	protected function flatten( $arr, $prefix = '', $separator = '_' ) {
		$result = array();

		if ( is_object( $arr ) ) {
			$arr = get_object_vars( $arr );
		}

		foreach ( $arr as $key => $value ) {
			if ( is_array( $value ) || is_object( $value ) ) {
				$result = $result + $this->flatten( $value, $prefix . $key . $separator, $separator );
			} else {
				$result[ $prefix . $key ] = $value;
			}
		}

		return $result;
	}

	public static function is_ssl( $domain, $strict = false ) {
		if ( defined( 'WP_SSL_GLOBAL' ) && WP_SSL_GLOBAL ) {
			return true;
		}

		if ( ! defined( 'WP_SSL_DOMAINS' ) || ! WP_SSL_DOMAINS ) {
			return false;
		}

		$ssl_domains = explode( ',', WP_SSL_DOMAINS );

		if ( in_array( $domain, $ssl_domains, true ) ) {
			return true;
		} elseif ( ! $strict && 0 !== strpos( $domain, 'www.' ) && in_array( 'www.' . $domain, $ssl_domains, true ) ) {
			return true;
		} elseif ( ! $strict && 0 === strpos( $domain, 'www.' ) && in_array( substr( $domain, 4 ), $ssl_domains, true ) ) {
			return true;
		}

		return false;
	}

	public static function get_current_domain() {
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

	public static function get_current_path() {
		return stripslashes( $_SERVER['REQUEST_URI'] );
	}

	public static function get_current_protocol() {
		if ( function_exists( 'is_ssl' ) ) {
			if ( is_ssl() ) {
				return 'https';
			}
			return 'http';
		}

		if ( ( isset( $_SERVER['https'] ) && ! empty( $_SERVER['https'] ) && $_SERVER['https'] !== 'off' ) || $_SERVER['SERVER_PORT'] == '443' ) {
			return 'https';
		}
		return 'http';
	}

	public static function get_current_port() {
		if ( ! in_array( absint( $_SERVER['SERVER_PORT'] ), array( 80, 443 ), true ) ) {
			return $_SERVER['SERVER_PORT'];
		}
		return '';
	}
}
