<?php
/**
 * Easy Digital Downloads dependencies checker.
 *
 * Version: 1.0.1
 *
 * @category Helpers
 * @package  Easy Digital Downloads
 * @author   Matt Gates <info@mgates.me>
 */
class Easy_Digital_Downloads_Dependencies {

	/**
	 * Current version of the Easy Digital Downloads_Dependencies class
	 *
	 * @var float
	 *
	 * @access private
	 * @static
	 */

	public function edd_active_check( $required_version = '' ) {
		$this->required_version = $required_version;
		$this->file = $this->get_calling_file();

		$this->do_actions_and_hooks();
	}

	/**
	 * Return the main plugin that's calling the Easy Digital Downloads
	 * dependencies library.
	 *
	 * @access private
	 *
	 * @return string File path to the main plugin.
	 */
	private function get_calling_file() {
		$file = debug_backtrace();

		// Three functions is how long it took for
		// the main plugin to call us. So three we go!
		$file = $file[2]['file'];

		return $file;
	}

	/**
	 * Begins the necessary actions this library
	 * requires.
	 *
	 * @access private
	 */
	private function do_actions_and_hooks() {
		add_action( 'admin_init', array( &$this, 'get_plugin_title' ) );
		add_action( 'admin_init', array( &$this, 'check_edd_on_init' ) );
		add_action( 'admin_init', array( &$this, 'check_edd_version' ) );
	}

	/**
	 * Retrieve the main plugin's title.
	 * Used for display in the admin notices.
	 *
	 * @access public
	 */
	public function get_plugin_title() {
		$plugin = get_plugin_data( $this->file );
		$this->title = $plugin['Name'];
	}

	/**
	 * Verifies whether or not Easy Digital Downloads is activated.
	 *
	 * @access private
	 *
	 * @return boolean Whether JIGOSHOP_VERSION is defined.
	 */
	private function is_edd_activated() {
		return defined( 'EDD_VERSION' );
	}

	/**
	 * Deactivate the main plugin.
	 *
	 * @access private
	 */
	private function deactivate_main_plugin() {
		deactivate_plugins( plugin_basename( $this->file ) );
	}

	/**
	 * Deactivate if Easy Digital Downloads isn't activated.
	 *
	 * @access public
	 */
	public function check_edd_on_init() {
		if ( !$this->is_edd_activated() ) {
			$this->deactivate_main_plugin();
			add_action( 'admin_notices', array( &$this, 'edd_is_not_activated' ) );
		}
	}

	/**
	 * Version check against Easy Digital Downloads.
	 * Compares a specified version against the current
	 * installed Easy Digital Downloads version, and deactivates if
	 * there is a discrepancy.
	 *
	 * @access public
	 */
	public function check_edd_version() {
		if ( empty( $this->required_version ) || !$this->is_edd_activated() ) return false;

		$plugins = get_plugins();
		foreach ( $plugins as $folder => $data ) {

			if ( !strpos( $folder, '/easy-digital-downloads.php' ) ) continue;

			if ( version_compare( $data['Version'], $this->required_version, '<' ) ) {
				$this->deactivate_main_plugin();
				add_action( 'admin_notices', array( &$this, 'invalid_edd_version' ) );
			}

		}
	}

	/**
	 * Prompt the user to update Easy Digital Downloads.
	 *
	 * @access public
	 */
	public function invalid_edd_version() {
		echo '<div class="error">
				<h3>' . $this->title . '</h3>
				<p>' . sprintf( __('<a href="%s" target="_TOP">Easy Digital Downloads</a> v%s or greater is required to activate this plugin. Please update Easy Digital Downloads.', 'edd'), 'http://easydigitaldownloads.com', $this->required_version ) . '</p>
			  </div>';
	}

	/**
	 * Prompt the user to install / activate Easy Digital Downloads.
	 *
	 * @access public
	 */
	public function edd_is_not_activated() {
		echo '<div class="error">
				<h3>' . $this->title . '</h3>
				<p>' . sprintf( __('<a href="%s" target="_TOP">Easy Digital Downloads</a> is not installed or is inactive. Please install / activate Easy Digital Downloads.', 'edd'), 'http://easydigitaldownloads.com' ) . '</p>
			  </div>';
	}

}
