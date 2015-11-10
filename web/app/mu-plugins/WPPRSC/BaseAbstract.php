<?php

namespace WPPRSC;

abstract class BaseAbstract {
	protected static $instances = array();

	public static function instance() {
		$class = get_called_class();
		$class = explode( '\\', $class );
		array_shift( $class );
		$class = implode( '_', $class );
		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new static();
		}
		return self::$instances[ $class ];
	}

	protected function __construct() {

	}

	public abstract function run();
}
