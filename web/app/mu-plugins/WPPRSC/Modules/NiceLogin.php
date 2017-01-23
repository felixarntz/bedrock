<?php
/**
 * Enhances the login screen with customizable design.
 */

namespace WPPRSC\Modules;

class NiceLogin extends \WPPRSC\ModuleAbstract {
	protected function __construct( $args = array() ) {
		parent::__construct( $args );

		$this->module_name = 'nice_login';
	}

	public function run() {
		add_action( 'login_head', array( $this, 'print_styles' ) );
	}

	public function print_styles() {
		$header_image = $this->get_setting( 'header_image' );
		$header_image_size = $this->get_setting( 'header_image_size' );
		if ( $header_image_size ) {
			if ( strpos( $header_image_size, 'x' ) ) {
				list( $header_image_width, $header_image_height ) = array_map( 'absint', array_map( 'trim', explode( 'x', $header_image_size ) ) );
			} else {
				$header_image_width = absint( $header_image_size );
				$header_image_height = absint( $header_image_size );
			}
		}

		$background_color = $this->get_setting( 'background_color' );
		$highlight_color = $this->get_setting( 'highlight_color' );
		$highlight_hover_color = $this->get_setting( 'highlight_hover_color' );
		$highlight_color_rgba = $this->to_rgba( $highlight_color );

		?>
		<style type="text/css">
			body {
				color: <?php echo $background_color; ?>;
			}

			a {
				color: <?php echo $highlight_color; ?>;
			}

			a:hover,
			a:active {
				color: <?php echo $highlight_hover_color; ?>;
			}

			.login #nav a:hover,
			.login #backtoblog a:hover,
			.login h1 a:hover {
				color: <?php echo $highlight_hover_color; ?>;
			}

			input[type="text"]:focus,
			input[type="password"]:focus,
			input[type="color"]:focus,
			input[type="date"]:focus,
			input[type="datetime"]:focus,
			input[type="datetime-local"]:focus,
			input[type="email"]:focus,
			input[type="month"]:focus,
			input[type="number"]:focus,
			input[type="password"]:focus,
			input[type="search"]:focus,
			input[type="tel"]:focus,
			input[type="text"]:focus,
			input[type="time"]:focus,
			input[type="url"]:focus,
			input[type="week"]:focus,
			input[type="checkbox"]:focus,
			input[type="radio"]:focus,
			select:focus,
			textarea:focus {
				border-color: <?php echo $highlight_color; ?>;
				-webkit-box-shadow: 0 0 2px <?php echo $highlight_color_rgba; ?>;
				box-shadow: 0 0 2px <?php echo $highlight_color_rgba; ?>;
			}

			.wp-core-ui .button-primary {
				background: <?php echo $highlight_color; ?>;
				border-color: <?php echo $highlight_hover_color; ?>;
				-webkit-box-shadow: 0 1px 0 <?php echo $highlight_hover_color; ?>;
				box-shadow: 0 1px 0 <?php echo $highlight_hover_color; ?>;
				color: #fff;
				text-shadow: 0 -1px 1px <?php echo $highlight_hover_color; ?>,
					1px 0 1px <?php echo $highlight_hover_color; ?>,
					0 1px 1px <?php echo $highlight_hover_color; ?>,
					-1px 0 1px <?php echo $highlight_hover_color; ?>;
			}

			.wp-core-ui .button-primary.hover,
			.wp-core-ui .button-primary:hover,
			.wp-core-ui .button-primary.focus,
			.wp-core-ui .button-primary:focus {
				background: <?php echo $highlight_hover_color; ?>;
				border-color: <?php echo $highlight_hover_color; ?>;
				color: #fff;
			}

			.wp-core-ui .button-primary.focus,
			.wp-core-ui .button-primary:focus {
				-webkit-box-shadow: 0 1px 0 <?php echo $highlight_hover_color; ?>,
					0 0 2px 1px <?php echo $highlight_hover_color; ?>;
				box-shadow: 0 1px 0 <?php echo $highlight_hover_color; ?>,
					0 0 2px 1px <?php echo $highlight_hover_color; ?>;
			}

			.wp-core-ui .button-primary.active,
			.wp-core-ui .button-primary.active:hover,
			.wp-core-ui .button-primary.active:focus,
			.wp-core-ui .button-primary:active {
				background: <?php echo $highlight_color; ?>;
				border-color: <?php echo $highlight_hover_color; ?>;
				-webkit-box-shadow: inset 0 2px 0 <?php echo $highlight_hover_color; ?>;
				box-shadow: inset 0 2px 0 <?php echo $highlight_hover_color; ?>;
			}

			.wp-core-ui .button-primary[disabled],
			.wp-core-ui .button-primary:disabled,
			.wp-core-ui .button-primary-disabled,
			.wp-core-ui .button-primary.disabled {
				opacity: 0.8;
				color: #ffffff !important;
				background: <?php echo $highlight_color; ?> !important;
				border-color: <?php echo $highlight_hover_color; ?> !important;
			}

			.wp-core-ui .button.button-primary.button-hero {
				-webkit-box-shadow: 0 2px 0 <?php echo $highlight_hover_color; ?>;
			 	box-shadow: 0 2px 0 <?php echo $highlight_hover_color; ?>;
			}

			.wp-core-ui .button.button-primary.button-hero.active,
			.wp-core-ui .button.button-primary.button-hero.active:hover,
			.wp-core-ui .button.button-primary.button-hero.active:focus,
			.wp-core-ui .button.button-primary.button-hero:active {
				-webkit-box-shadow: inset 0 3px 0 <?php echo $highlight_hover_color; ?>;
			 	box-shadow: inset 0 3px 0 <?php echo $highlight_hover_color; ?>;
			}

			<?php if ( $header_image ) : ?>
			.login h1 a {
				background-image: url('<?php echo $header_image; ?>');
				<?php if ( isset( $header_image_width ) && isset( $header_image_height ) ) : ?>
				background-size: <?php echo $header_image_width; ?>px <?php echo $header_image_height; ?>px;
				width: <?php echo $header_image_width; ?>px;
				height: <?php echo $header_image_height; ?>px;
				<?php endif; ?>
			}
			<?php endif; ?>
		</style>
		<?php
	}

	protected function to_rgba( $hex, $opacity = 0.8 ) {
		$hex = str_replace( '#', '', $hex );

		if ( 3 === strlen( $hex ) ) {
			$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
			$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
			$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
		} else {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		}

		return 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $opacity . ')';
	}

	protected function get_default_args() {
		return array(
			'header_image'			=> false,
			'header_image_size'		=> false,
			'highlight_color'		=> '#21759b',
			'highlight_hover_color'	=> '#006799',
			'background_color'      => '#f1f1f1',
		);
	}
}
