<?php

namespace WPPRSC;

abstract class ModuleAbstract extends \WPPRSC\BaseAbstract {
	protected $module_name = '';
	protected $network_only = false;
	protected $validated = false;
	protected $args = array();

	public static function instance( $args = array() ) {
		$class = get_called_class();
		$class = explode( '\\', $class );
		array_shift( $class );
		$class = implode( '_', $class );
		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new static( $args );
			self::$instances[ $class ]->validate_args();
		}
		return self::$instances[ $class ];
	}

	protected function __construct( $args = array() ) {
		$this->set_args( $args );
	}

	public function get_setting( $name ) {
		if ( ! empty( $this->module_name ) ) {
			if ( ! $this->network_only ) {
				$constant = 'WP_' . strtoupper( $this->module_name ) . '_OPTION_' . get_current_blog_id() . '_' . strtoupper( $name );
				if ( defined( $constant ) ) {
					return constant( $constant );
				}
			}

			$constant = 'WP_' . strtoupper( $this->module_name ) . '_OPTION_' . strtoupper( $name );
			if ( defined( $constant ) ) {
				return constant( $constant );
			}

			if ( $this->network_only && is_multisite() ) {
				$options = get_site_option( 'wpprsc_' . $this->module_name, array() );
				if ( isset( $options[ $name ] ) ) {
					return $options[ $name ];
				}
			} else {
				$options = get_option( 'wpprsc_' . $this->module_name, array() );
				if ( isset( $options[ $name ] ) ) {
					return $options[ $name ];
				}
			}
		}

		if ( isset( $this->args[ $name ] ) ) {
			return $this->args[ $name ];
		}

		return null;
	}

	public function is_network_only() {
		return $this->network_only;
	}

	public function set_args( $args = array() ) {
		if ( ! $args ) {
			return;
		}

		$this->args = array_merge( $this->args, $args );
		$this->validated = false;
	}

	public function validate_args() {
		if ( $this->validated ) {
			return;
		}

		$args = $this->args;
		$defaults = $this->get_default_args();

		$this->args = array();
		foreach ( $defaults as $key => $value ) {
			$this->args[ $key ] = isset( $args[ $key ] ) ? $args[ $key ] : $value;
		}

		$this->validated = true;
	}
}
