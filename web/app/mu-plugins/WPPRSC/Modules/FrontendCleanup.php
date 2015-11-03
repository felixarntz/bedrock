<?php
/**
 * Cleans up unnecessary WP output from the frontend.
 */

namespace WPPRSC\Modules;

class FrontendCleanup extends \WPPRSC\ModuleAbstract {
	protected function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	public function run() {
		if ( $this->args['clean_feed_links'] ) {
			remove_action( 'wp_head', 'feed_links', 2 );
		}

		if ( $this->args['clean_feed_links_extra'] ) {
			remove_action( 'wp_head', 'feed_links_extra', 3 );
		}

		if ( $this->args['clean_rsd_link'] ) {
			remove_action( 'wp_head', 'rsd_link' );
		}

		if ( $this->args['clean_wlwmanifest_link'] ) {
			remove_action( 'wp_head', 'wlwmanifest_link' );
		}

		if ( $this->args['clean_wp_generator'] ) {
			remove_action( 'wp_head', 'wp_generator' );
			foreach ( array( 'rss2_head', 'commentsrss2_head', 'rss_head', 'rdf_header', 'atom_head', 'comments_atom_head', 'opml_head', 'app_head' ) as $action ) {
				remove_action( $action, 'the_generator' );
			}
		}

		if ( $this->args['clean_asset_versions'] ) {
			add_filter( 'style_loader_src', array( $this, 'strip_version_arg' ), 10, 2 );
			add_filter( 'script_loader_src', array( $this, 'strip_version_arg' ), 10, 2 );
		}

		if ( $this->args['clean_img_dimensions'] ) {
			add_filter( 'post_thumbnail_html', array( $this, 'strip_hwstring' ) );
			add_filter( 'image_send_to_editor', array( $this, 'strip_hwstring' ) );
		}

		if ( $this->args['improve_html5_support'] ) {
			// hook this in later so that we can check theme support
			add_action( 'init', array( $this, 'improve_html5_support' ) );
		}
	}

	public function improve_html5_support() {
		if ( ! current_theme_supports( 'html5' ) ) {
			return;
		}

		add_filter( 'style_loader_tag', array( $this, 'clean_style_tag' ), 10, 3 );
		add_filter( 'script_loader_tag', array( $this, 'clean_script_tag' ), 10, 3 );

		add_filter( 'post_thumbnail_html', array( $this, 'remove_self_closing_tag' ) );
		add_filter( 'get_image_tag', array( $this, 'remove_self_closing_tag' ) );
		add_filter( 'get_avatar', array( $this, 'remove_self_closing_tag' ) );
		add_filter( 'comment_id_fields', array( $this, 'remove_self_closing_tag' ) );
		add_filter( 'style_loader_tag', array( $this, 'remove_self_closing_tag' ) );

		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_generator' );
		add_action( 'wp_head', array( $this, 'head_links' ), 2 );
	}

	public function strip_version_arg( $src, $handle ) {
		return remove_query_arg( 'ver', $src );
	}

	public function strip_hwstring( $html ) {
		return preg_replace( "/ (width|height)=(\"|').*(\"|')/U", '', $html );
	}

	public function clean_style_tag( $html, $handle, $src ) {
		$html = preg_replace( "/(rel|id|title|type|href|media)='(.*)'/U", '$1="$2"', $html );

		return str_replace( array(
			' type="text/css"',
			' media="all"',
		), '', $html );
	}

	public function clean_script_tag( $html, $handle, $src ) {
		$html = preg_replace( "/(type|src)='(.*)'/U", '$1="$2"', $html );

		return str_replace( array(
			' type="text/javascript"',
		), '', $html );
	}

	public function remove_self_closing_tag( $html ) {
		return str_replace( '/>', '>', str_replace( ' />', '>', $html ) );
	}

	public function head_links() {
		ob_start();
		if ( ! $this->args['clean_feed_links'] ) {
			feed_links();
		}
		if ( ! $this->args['clean_feed_links_extra'] ) {
			feed_links_extra();
		}
		if ( ! $this->args['clean_rsd_link'] ) {
			rsd_link();
		}
		if ( ! $this->args['clean_wlwmanifest_link'] ) {
			wlwmanifest_link();
		}
		if ( ! $this->args['clean_wp_generator'] ) {
			wp_generator();
		}
		echo $this->remove_self_closing_tag( ob_get_clean() );
	}

	protected function get_default_args() {
		return array(
			'clean_feed_links'			=> false,
			'clean_feed_links_extra'	=> false,
			'clean_rsd_link'			=> false,
			'clean_wlwmanifest_link'	=> false,
			'clean_wp_generator'		=> false,
			'clean_asset_versions'		=> false,
			'clean_img_dimensions'		=> false,
			'improve_html5_support'		=> false,
		);
	}
}
