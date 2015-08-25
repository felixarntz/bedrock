<?php

namespace WPPRSC;

abstract class ModuleAbstract extends \WPPRSC\Abstract {
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

	public abstract function run();

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

	protected abstract function get_default_args();
}
