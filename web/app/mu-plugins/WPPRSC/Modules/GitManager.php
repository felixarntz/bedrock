<?php
/**
 * Handles Git inside the WordPress backend.
 */

namespace WPPRSC\Modules;

class GitManager extends \WPPRSC\ModuleAbstract {
	private $current_dir;
	private $root_dir;
	private $branch_name;

	protected function __construct( $args = array() ) {
		parent::__construct( $args );

		$this->module_name = 'git_manager';
		$this->network_only = true;
	}

	public function run() {
		add_action( 'admin_bar_init', array( $this, 'init' ) );
	}

	public function init() {
		$cap = $this->get_setting( 'capability' );
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

		foreach ( $commits as $commit ) {
			$args = array(
				'id'		=> 'git-info-commit-' . $commit['commit_hash_abbrev'],
				'parent'	=> 'git-info',
				'title'		=> $commit['commit_message'] . ' [' . $commit['commit_hash_abbrev'] . ']',
			);
			if ( isset( $unpushed_commits[ $commit['commit_hash'] ] ) ) {
				$args['title'] .= ' <span class="git-info-warning">' . __( '(unpushed)', 'wpprsc' ) . '</span>';
			} elseif ( $repository ) {
				$args['href'] = 'https://github.com/' . $repository . '/commit/' . $commit['commit_hash'];
			}

			$admin_bar->add_node( $args );
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

	protected function get_default_args() {
		return array(
			'git_path'			=> 'git',
			'remote_name'		=> 'origin',
			'repository'		=> '',
			'capability'		=> 'manage_options',
		);
	}
}
