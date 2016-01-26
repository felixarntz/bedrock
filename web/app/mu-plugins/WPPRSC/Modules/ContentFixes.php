<?php
/**
 * Fixes some content-related stuff in WordPress.
 */

namespace WPPRSC\Modules;

class ContentFixes extends \WPPRSC\ModuleAbstract {
	protected function __construct( $args = array() ) {
		parent::__construct( $args );

		$this->module_name = 'content_fixes';
	}

	public function run() {
		if ( $this->get_setting( 'disable_posts' ) ) {
			remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );

			if ( ! is_admin() || defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				add_filter( 'posts_results', array( $this, 'hide_posts' ), 10, 2 );
				add_action( 'pre_get_posts', array( $this, 'exclude_posts_from_search' ), 10, 1 );
			}
			add_filter( 'pre_option_show_on_front', array( $this, '__return_page' ) );
			add_filter( 'pre_option_page_for_posts', '__return_zero' );
			add_filter( 'pre_option_page_on_front', array( $this, 'force_front_page' ) );

			add_action( 'admin_init', array( $this, 'redirect_posts_screens' ), 1 );
			add_action( 'admin_menu', array( $this, 'remove_posts_menu' ), 100 );
			add_action( 'admin_head', array( $this, 'hide_posts_setting' ) );
		}

		if ( $this->get_setting( 'disable_comments' ) ) {
			add_filter( 'pre_option_default_comment_status', array( $this, '__return_closed' ) );
			add_filter( 'comments_open', '__return_false' );
			add_filter( 'pings_open', '__return_false' );
			add_filter( 'comments_array', '__return_empty_array' );

			add_action( 'init', array( $this, 'remove_comment_support' ), 100 );

			add_action( 'admin_init', array( $this, 'redirect_comments_screen' ), 1 );
			add_action( 'admin_init', array( $this, 'remove_comments_dashboard_widget' ) );
			add_action( 'admin_menu', array( $this, 'remove_comments_menu' ), 100 );
			add_action( 'init', array( $this, 'remove_comments_admin_bar_menu' ), 100 );
		} elseif ( $this->get_setting( 'disable_pingbacks' ) ) {
			add_filter( 'pre_option_default_ping_status', array( $this, '__return_closed' ) );
			add_filter( 'pings_open', '__return_false' );

			add_action( 'init', array( $this, 'remove_pingback_support' ), 100 );

			add_action( 'admin_head', array( $this, 'hide_pingback_setting' ) );
		}
	}

	public function hide_posts( $posts, $query ) {
		if ( is_home() ) {
			return array();
		}

		$post_types = $query->get( 'post_type' );
		if ( is_array( $post_types ) && in_array( 'post', $post_types ) ) {
			return array_filter( $posts, array( $this, 'hide_posts_cb' ) );
		} elseif ( is_string( $post_types ) && 'post' === $post_types ) {
			return array();
		}

		return $posts;
	}

	public function hide_posts_cb( $post ) {
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return false;
		}

		if ( 'post' === $post->post_type ) {
			return false;
		}

		return true;
	}

	public function exclude_posts_from_search( $query ) {
		$post_types = $query->get( 'post_type' );
		if ( is_array( $post_types ) && false !== ( $key = array_search( 'post', $post_types ) ) ) {
			unset( $post_types[ $key ] );
			$query->set( 'post_type', array_values( $post_types ) );
		}
	}

	public function __return_page() {
		return 'page';
	}

	public function force_front_page( $page_on_front ) {
		if ( 0 !== intval( $page_on_front ) ) {
			return $page_on_front;
		}

		$pages = get_pages( array(
			'sort_order'	=> 'ASC',
			'sort_column'	=> 'ID',
			'parent'		=> 0,
			'number'		=> 1,
		) );

		if ( ! is_array( $pages ) || ! isset( $pages[0] ) ) {
			return $page_on_front;
		}

		return $pages[0]->ID;
	}

	public function redirect_posts_screens() {
		global $pagenow;

		if ( ! in_array( $pagenow, array( 'edit.php', 'edit-tags.php', 'post-new.php' ) ) ) {
			return;
		}

		if ( isset( $_GET['post_type'] ) || isset( $_GET['taxonomy'] ) ) {
			return;
		}

		wp_safe_redirect( admin_url(), 301 );
		exit;
	}

	public function remove_posts_menu() {
		global $menu, $submenu;

		foreach( $menu as $k => $v ) {
			foreach( $v as $key => $value ) {
				if ( __( 'Posts' ) === $value ) {
					unset( $menu[ $k ] );
					break 2;
				}
			}
		}

		foreach( $submenu as $k => $v ) {
			if ( 'edit.php' === $k ) {
				unset( $submenu[ $k ] );
				break;
			}
		}
	}

	public function hide_posts_setting() {
		global $pagenow;

		if ( 'options-reading.php' !== $pagenow ) {
			return;
		}

		?>
<style type="text/css">
	#front-static-pages p,
	#front-static-pages ul > li:last-child {
		display: none;
	}
	#front-static-pages ul {
		margin: 0;
	}
</style>
		<?php
	}

	public function __return_closed() {
		return 'closed';
	}

	public function remove_comment_support() {
		$post_types = get_post_types();
		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
			}
			if ( post_type_supports( $post_type, 'trackbacks' ) ) {
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}

	public function redirect_comments_screen() {
		global $pagenow;

		if ( ! in_array( $pagenow, array( 'edit-comments.php', 'options-discussion.php' ) ) ) {
			return;
		}

		wp_safe_redirect( admin_url(), 301 );
		exit;
	}

	public function remove_comments_dashboard_widget() {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	public function remove_comments_menu() {
		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}

	public function remove_comments_admin_bar_menu() {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
	}

	public function remove_pingback_support() {
		$post_types = get_post_types();
		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type, 'trackbacks' ) ) {
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}

	public function hide_pingback_setting() {
		global $pagenow;

		if ( 'options-discussion.php' !== $pagenow ) {
			return;
		}

		?>
<style type="text/css">
	label[for="default_ping_status"],
	label[for="default_ping_status"] + br {
		display: none;
	}
</style>
		<?php
	}

	protected function get_default_args() {
		return array(
			'disable_posts'		=> false,
			// disabling comments will also disable pingbacks
			'disable_comments'	=> false,
			'disable_pingbacks'	=> false,
		);
	}
}
