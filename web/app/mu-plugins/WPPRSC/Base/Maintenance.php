<?php
/**
 * Handles maintenance page (called from /app/maintenance.php)
 */

namespace WPPRSC\Base;

class Config extends \WPPRSC\BaseAbstract {
	protected $maintenance_template = false;

	protected function __construct() {
	}

	public function run() {
		$this->load_translations();
		$this->send_headers();
		$this->render();

		die();
	}

	protected function load_translations() {
		wp_load_translations_early();
	}

	protected function send_headers() {
		$protocol = wp_get_server_protocol();

		header( $protocol . ' 503 Service Unavailable', true, 503 );
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'Retry-After: 600' );
	}

	protected function render() {
		$data = $this->setup_data();

		if ( $this->maintenance_template ) {
			require_once $maintenance_template;
			return;
		}

		?>
<!DOCTYPE html>
<html <?php echo $data['language_attributes']; ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title><?php echo $data['title']; ?></title>
	</head>
	<body>
		<h1><?php echo $data['description']; ?></h1>
	</body>
</html>
		<?php
	}

	protected function setup_data() {
		$data = array();

		$language_attributes = array();
		if ( function_exists( 'is_rtl' ) && is_rtl() ) {
			$language_attributes[] = 'dir="rtl"';
		}
		if ( function_exists( 'get_locale' ) ) {
			$language_attributes[] = 'lang="' . str_replace( '_', '-', get_locale() ) . '"';
		}

		$data['language_attributes'] = implode( ' ', $language_attributes );
		$data['title'] = __( 'Maintenance' );
		$data['description'] = __( 'Briefly unavailable for scheduled maintenance. Check back in a minute.' );

		return $data;
	}
}
