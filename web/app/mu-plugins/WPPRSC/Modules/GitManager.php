<?php
/**
 * Handles Git inside the WordPress backend.
 */

namespace WPPRSC\Modules;

class GitManager extends \WPPRSC\ModuleAbstract {
	private $current_dir;
	private $root_dir;
	private $branch_name;

	private $admin_message = '';
	private $admin_error = false;

	protected function __construct( $args = array() ) {
		parent::__construct( $args );

		$this->module_name = 'git_manager';
		$this->network_only = true;
	}

	public function run() {
		add_action( 'admin_bar_init', array( $this, 'init' ) );

		add_action( 'admin_action_git_push', array( $this, 'action_git_push' ) );
		add_action( 'admin_action_git_pull', array( $this, 'action_git_pull' ) );
		add_action( 'admin_action_git_reset_commit', array( $this, 'action_git_reset_commit' ) );
	}

	public function init() {
		if ( ! current_user_can( $this->get_setting( 'capability' ) ) ) {
			return;
		}

		if ( ! function_exists( 'exec' ) ) {
			return;
		}

		if ( ! $this->get_setting( 'git_path' ) ) {
			return;
		}

		$this->current_dir = getcwd();
		if ( ! $this->current_dir ) {
			return;
		}

		$this->root_dir = $this->get_git_root_dir();
		if ( ! $this->root_dir ) {
			return;
		}

		$this->branch_name = $this->get_branch_name();
		if ( ! $this->branch_name ) {
			return;
		}

		add_action( 'admin_bar_menu', array( $this, 'show_admin_bar_info' ), 10, 1 );

		add_action( 'wp_head', array( $this, 'print_admin_bar_styles' ) );
		add_action( 'admin_head', array( $this, 'print_admin_bar_styles' ) );
	}

	public function action_git_push() {
		wp_reset_vars( array( 'action' ) );

		if ( ! current_user_can( $this->get_setting( 'capability' ) ) ) {
			return;
		}

		$output = $this->exec( 'push', array( '--all' ) );
		if ( ! isset( $output[0] ) ) {
			$this->hook_admin_notice( __( 'An unknown error occurred while trying to push latest changes to remote.', 'wpprsc' ), true );
			return;
		}

		if ( 0 === strpos( $output[0], 'Permission denied' ) ) {
			$this->hook_admin_notice( __( 'Permission denied. Could not push latest changes to remote.', 'wpprsc' ), true );
			return;
		}

		if ( 0 === strpos( $output[0], 'Everything up-to-date' ) ) {
			$this->hook_admin_notice( __( 'The remote is already up to date with the local changes. Nothing was pushed.', 'wpprsc' ) );
			return;
		}

		$this->hook_admin_notice( __( 'Changes pushed to remote successfully.', 'wpprsc' ) );
	}

	public function action_git_pull() {
		wp_reset_vars( array( 'action' ) );

		if ( ! current_user_can( $this->get_setting( 'capability' ) ) ) {
			return;
		}

		$output = $this->exec( 'pull', array(), array( 'SSH_AGENT_PID' => '1704' ) );
		if ( ! isset( $output[0] ) ) {
			$this->hook_admin_notice( __( 'An unknown error occurred while trying to pull latest changes from remote.', 'wpprsc' ), true );
			return;
		}

		if ( 0 === strpos( $output[0], 'Permission denied' ) ) {
			$this->hook_admin_notice( __( 'Permission denied. Could not pull latest changes from remote.', 'wpprsc' ), true );
			return;
		}

		if ( 0 === strpos( $output[0], 'Already up-to-date' ) ) {
			$this->hook_admin_notice( __( 'The local changes are already up to date with the remote. Nothing was pulled.', 'wpprsc' ) );
			return;
		}

		$this->hook_admin_notice( __( 'Changes pulled from remote successfully.', 'wpprsc' ) );
	}

	public function action_git_reset_commit() {
		$commit_hash = $_REQUEST['commit'];

		wp_reset_vars( array( 'action', 'commit' ) );

		if ( ! current_user_can( $this->get_setting( 'capability' ) ) ) {
			return;
		}

		$output = $this->exec( 'reset', array( '--hard', $commit_hash ) );
		if ( ! isset( $output[0] ) ) {
			$this->hook_admin_notice( sprintf( __( 'An unknown error occurred while trying to reset to commit %s.', 'wpprsc' ), esc_html( $commit_hash ) ), true );
			return;
		}

		if ( 0 === strpos( $output[0], 'fatal:' ) ) {
			$this->hook_admin_notice( sprintf( __( '%s is not a valid commit hash to reset to.', 'wpprsc' ), esc_html( $commit_hash ) ), true );
			return;
		}

		$this->hook_admin_notice( sprintf( __( 'Local repository reset to commit %s.', 'wpprsc' ), esc_html( $commit_hash ) ) );
	}

	public function show_admin_bar_info( $admin_bar ) {
		$commits = $this->get_commits();
		$unpushed_commits = $this->get_commits( array( 'mode' => 'unpushed' ), OBJECT_K );

		$main_node_args = array(
			'id'		=> 'git-info',
			'parent'	=> 'top-secondary',
			'title'		=> sprintf( __( 'Git: branch %s', 'wpprsc' ), $this->branch_name ),
		);

		$repository = $this->get_setting( 'repository' );
		if ( $repository ) {
			$main_node_args['title'] = sprintf( __( 'Git: %1$s branch %2$s', 'wpprsc' ), $repository, $this->branch_name );
			$main_node_args['href'] = 'https://github.com/' . $repository . '/tree/' . $this->branch_name;
		}

		if ( 0 < count( $unpushed_commits ) ) {
			$main_node_args['title'] = ' <span class="git-info-warning">' . $main_node_args['title'] . '</span>';
		}

		$admin_bar->add_node( $main_node_args );

		$admin_bar->add_group( array(
			'id'		=> 'git-actions',
			'parent'	=> 'git-info',
		) );

		if ( 0 < count( $unpushed_commits ) ) {
			$admin_bar->add_node( array(
				'id'		=> 'git-action-push',
				'parent'	=> 'git-actions',
				'title'		=> __( 'Push changes to remote', 'wpprsc' ),
				'href'		=> $this->add_admin_query_arg( 'action', 'git_push' ),
			) );
		}

		$admin_bar->add_node( array(
			'id'		=> 'git-action-pull',
			'parent'	=> 'git-actions',
			'title'		=> __( 'Pull changes from remote', 'wpprsc' ),
			'href'		=> $this->add_admin_query_arg( 'action', 'git_pull' ),
		) );

		$admin_bar->add_group( array(
			'id'		=> 'git-commits',
			'parent'	=> 'git-info',
			'meta'		=> array(
				'class'		=> 'ab-sub-secondary',
			),
		) );

		foreach ( $commits as $commit ) {
			$args = array(
				'id'		=> 'git-commit-' . $commit['commit_hash_abbrev'],
				'parent'	=> 'git-commits',
				'title'		=> $commit['commit_message'] . ' [' . $commit['commit_hash_abbrev'] . ']',
			);
			if ( isset( $unpushed_commits[ $commit['commit_hash'] ] ) ) {
				$args['title'] .= ' <span class="git-info-warning">' . __( '(unpushed)', 'wpprsc' ) . '</span>';
			} elseif ( $repository ) {
				$args['href'] = 'https://github.com/' . $repository . '/commit/' . $commit['commit_hash'];
			}

			$admin_bar->add_node( $args );
			$admin_bar->add_node( array(
				'id'		=> 'git-commit-' . $commit['commit_hash_abbrev'] . '-reset',
				'parent'	=> 'git-commit-' . $commit['commit_hash_abbrev'],
				'title'		=> __( 'Reset to this commit', 'wpprsc' ),
				'href'		=> $this->add_admin_query_arg( array(
					'action'	=> 'git_reset_commit',
					'commit'	=> $commit['commit_hash'],
				) ),
			) );
		}
	}

	public function print_admin_bar_styles() {
		?>
		<style type="text/css">
			#wpadminbar .git-info-warning {
				color: #ffb900;
			}
		</style>
		<?php
	}

	public function get_commits( $args = array(), $output = OBJECT ) {
		$_args = wp_parse_args( $args, array(
			'mode'			=> 'all',
			'number'		=> 10,
			'offset'		=> 0,
			'author'		=> '',
			'committer'		=> '',
		) );

		$args = array();

		switch ( $_args['mode'] ) {
			case 'unpushed':
				$args[] = $this->get_setting( 'remote_name' ) . '/' . $this->branch_name . '..' . $this->branch_name;
				break;
			case 'unpulled':
				$args[] = $this->branch_name . '..' . $this->get_setting( 'remote_name' ) . '/' . $this->branch_name;
				break;
			default:
				$args[] = 'HEAD';
		}

		if ( 0 <= $_args['number'] ) {
			$args[] = '--max-count=' . $_args['number'];
		}

		if ( 0 < $_args['offset'] ) {
			$args[] = '--skip=' . $_args['offset'];
		}

		if ( ! empty( $_args['author'] ) ) {
			$args[] = '--author=' . $_args['author'];
		}

		if ( ! empty( $_args['committer'] ) ) {
			$args[] = '--committer=' . $_args['committer'];
		}

		$commits = $this->log( $args );

		if ( OBJECT_K !== $output ) {
			$commits = array_values( $commits );
		}

		return $commits;
	}

	public function log( $args = array(), $env_vars = array() ) {
		$index = -1;
		for ( $i = 0; $i < count( $args ); $i++ ) {
			if ( strpos( $args[ $i ], '--pretty=' ) === 0 ) {
				$index = $i;
				break;
			}
		}

		if ( $index > -1 ) {
			$args[ $index ] = '--pretty=format:' . $this->get_commit_formatstring();
		} else {
			$args[] = '--pretty=format:' . $this->get_commit_formatstring();
		}

		$output = $this->exec( 'log', $args, $env_vars );

		$commits = array();
		$current_commit = array();
		if ( is_array( $output ) ) {
			foreach ( $output as $index => $line ) {
				$line = trim( $line );
				if ( ! empty( $line ) ) {
					$key = explode( ':', $line );
					if ( isset( $key[0] ) ) {
						$key = $key[0];
						$value = str_replace( $key . ':', '', $line );
						$current_commit[ $key ] = $value;
					}
				}
				if ( empty( $line ) || $index == count( $output ) - 1 ) {
					if ( isset( $current_commit['commit_hash'] ) ) {
						$commits[ $current_commit['commit_hash'] ] = $current_commit;
						$current_commit = array();
					}
				}
			}
		}

		return $commits;
	}

	public function exec( $command, $args = array(), $env_vars = array() ) {
		$command = str_replace( '_', '-', $command );

		$env_vars_string = '';
		foreach ( $env_vars as $env_var => $value ) {
			$env_vars_string .= $env_var . '="' . $value . '" ';
		}

		$path = $this->escape_shell_arg( $this->get_setting( 'git_path' ) );
		$command = $this->escape_shell_arg( $command );
		$args = join( ' ', array_map( array( $this, 'escape_shell_arg' ), $args ) );

		$output = array();
		$status = 0;

		chdir( $this->root_dir );
		exec( "$env_vars_string$path $command $args 2>&1", $output, $status );
		chdir( $this->current_dir );

		if ( ! is_array( $output ) ) {
			if ( $output ) {
				$output = array( $output );
			} else {
				$output = array();
			}
		}

		return $output;
	}

	public function hook_admin_notice( $message, $error = false ) {
		$this->admin_message = $message;
		$this->admin_error = $error;

		add_action( 'admin_notices', array( $this, 'show_notice' ) );
	}

	public function show_notice() {
		$class = $this->admin_error ? 'notice-error' : 'notice-success';
		?>
		<div class="notice <?php echo $class; ?> is-dismissible">
			<p>
				<strong><?php _e( 'Git:', 'wpprsc' ); ?></strong>
				<?php echo $this->admin_message; ?>
			</p>
		</div>
		<?php
	}

	private function escape_shell_arg( $arg ) {
		$os = strtoupper( substr( php_uname( 's' ), 0, 3 ) );

		if ( $os === 'WIN' ) {
			return '"' . str_replace( "'", "'\\''", $arg ) . '"';
		}

		return escapeshellarg( $arg );
	}

	private function get_commit_formatstring() {
		$fields = array(
			'commit_hash'				=> '%H',
			'commit_hash_abbrev'		=> '%h',
			'commit_message'			=> '%s',
			'tree_hash'					=> '%T',
			'tree_hash_abbrev'			=> '%t',
			'author_name'				=> '%an',
			'author_email'				=> '%ae',
			'author_date'				=> '%ai',
			'author_date_timestamp'		=> '%at',
			'committer_name'			=> '%cn',
			'committer_email'			=> '%ce',
			'committer_date'			=> '%ci',
			'committer_date_timestamp'	=> '%ct',
		);

		$formatstring = '';
		foreach ( $fields as $field => $placeholder ) {
			$formatstring .= $field . ':' . $placeholder . '%n';
		}

		return $formatstring;
	}

	private function get_git_root_dir() {
		$path = $this->get_setting( 'git_path' );

		chdir( ABSPATH );
		$git_dir = exec( "$path rev-parse --show-toplevel" );
		chdir( $this->current_dir );
		if ( $git_dir ) {
			return $git_dir;
		}

		return '';
	}

	private function get_branch_name() {
		if ( is_dir( trailingslashit( $this->root_dir ) . '.git' ) && is_file( trailingslashit( $this->root_dir ) . '.git/HEAD' ) ) {
			$filecontents = file( trailingslashit( $this->root_dir ) . '.git/HEAD', FILE_USE_INCLUDE_PATH );
			$line = $filecontents[0];
			$line = explode( '/', $line, 3 );

			return trim( $line[2] );
		}

		return '';
	}

	private function add_admin_query_arg( $key, $value = '' ) {
		$url = false;
		if ( ! is_admin() ) {
			$url = admin_url( '/' );
		}

		if ( is_array( $key ) ) {
			return add_query_arg( $key, $url );
		}
		return add_query_arg( $key, $value, $url );
	}

	protected function get_default_args() {
		return array(
			'git_path'			=> 'git',
			'remote_name'		=> 'origin',
			'repository'		=> '',
			'capability'		=> 'manage_options',
		);
	}
}
