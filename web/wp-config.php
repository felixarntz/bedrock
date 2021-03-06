<?php
/**
 * Do not edit this file.
 */

if ( version_compare( phpversion(), '5.5.0', '<' ) ) {
	die( 'Execution aborted because PHP Version is lower than 5.5.0' );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

\WPPRSC\Base\Config::instance()->run();

$table_prefix = DB_PREFIX;

require_once ABSPATH . 'wp-settings.php';
