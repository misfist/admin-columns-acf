<?php
/*
Plugin Name: 		Admin Columns - ACF add-on
Version: 			1.3.1
Description: 		Show Advanced Custom Fields fields in your admin post overviews and edit them inline! ACF integration Add-on for Admin Columns.
Author: 			Codepress
Author URI: 		https://admincolumns.com
Text Domain: 		codepress-admin-columns
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit when access directly

// Addon information
define( 'CAC_ACF_VERSION',	'1.3.1' );
define( 'CAC_ACF_FILE',		__FILE__ );
define( 'CAC_ACF_URL',		plugin_dir_url( __FILE__ ) );
define( 'CAC_ACF_DIR',		plugin_dir_path( __FILE__ ) );

/**
 * Main ACF Addon plugin class
 *
 * @since 1.0
 */
class CPAC_Addon_ACF {

	/**
	 * Admin Columns main plugin class instance
	 *
	 * @since 1.0
	 * @var CPAC
	 */
	public $cpac;

	/**
	 * Advanced Custom Fields main plugin class instance
	 *
	 * @since 1.1
	 * @var acf
	 */
	public $acf;

	/**
	 * Main plugin directory
	 *
	 * @since 1.0
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	function __construct() {

		$this->plugin_basename = plugin_basename( __FILE__ );

		// load translations from pro version
		if ( defined( 'CAC_PRO_URL' ) ) {
			load_plugin_textdomain( 'codepress-admin-columns', false, CAC_PRO_URL . 'languages/' );
		}

		// Includes
		require_once 'api.php';

		// Plugin-dependent setup
		add_action( 'cac/loaded', array( $this, 'init' ) );
		add_action( 'plugins_loaded', array( $this, 'init_acf' ) );

		// Hooks
		add_filter( 'cac/storage_model/column_type_groups', array( $this, 'column_type_groups' ) );
		add_filter( 'cac/columns/custom', array( $this, 'add_columns' ) );
		add_action( 'after_plugin_row_' . $this->plugin_basename, array( $this, 'display_plugin_row_notices' ), 11 );
	}

	/**
	 * Init
	 *
	 * @since 1.0
	 */
	function init( $cpac ) {

		$this->cpac = $cpac;

		// Setup callback
		$this->after_setup();
	}

	/**
	 * Loads ACF main plugin class instance
	 * Callback for after ACF is set up
	 *
	 * @since 1.1
	 */
	public function init_acf() {

		if ( ! $this->is_acf_active() ) {
			return;
		}

		$this->acf = acf();
	}

	/**
	 * Fire callbacks for plugin setup completion
	 *
	 * @since 1.1
	 */
	public function after_setup() {

		/**
		 * Fires when the Admin Columns ACF plugin is fully loaded
		 *
		 * @since 1.1
		 * @param CPAC_Addon_ACF $cpac_wc_instance Main Admin Columns ACF plugin class instance
		 */
		do_action( 'cpac-acf/loaded', $this );
	}

	/**
	 * @since 1.1
	 */
	public function column_type_groups( $groups ) {

		// add to the top of the group menu
		return array( 'acf' => __( 'Advanced Custom Fields', 'codepress-admin-columns' ) ) + $groups;
	}

	/**
	 * Add custom columns
	 *
	 * @since 1.0
	 */
	public function add_columns( $columns ) {

		// ACF Field column
		if ( $this->is_acf_active() ) {
			require_once CAC_ACF_DIR . 'classes/column/acf-field.php';

			$acf_version = explode( '.', $this->get_acf_version() );

			if ( count( $acf_version ) > 1 ) {

				if ( '4' == $acf_version[0] ) {
					$columns['CPAC_ACF_Column_ACF_Field_ACF4'] = CAC_ACF_DIR . 'classes/column/acf-field-acf4.php';
				}
				elseif ( '5' == $acf_version[0] ) {
					$columns['CPAC_ACF_Column_ACF_Field_ACF5'] = CAC_ACF_DIR . 'classes/column/acf-field-acf5.php';
				}

				// Remove ACF placeholder column
				if ( isset( $columns['CPAC_Column_ACF_Placeholder'] ) ) {
					unset( $columns['CPAC_Column_ACF_Placeholder'] );
				}
			}
		}

		return $columns;
	}

	/**
	 * Shows a message below the plugin on the plugins page
	 *
	 * @since 1.0
	 */
	public function display_plugin_row_notices() {

		// Display notice for missing dependencies
		$missing_dependencies = array();

		if ( ! $this->is_cpac_active() ) {
			$missing_dependencies[] = '<a href="' . admin_url( 'plugin-install.php' ) . '?tab=search&s=Admin+Columns&plugin-search-input=Search+Plugins' . '" target="_blank">' . __( 'Admin Columns', 'codepress-admin-columns' ) . '</a>';
		}

		if ( ! $this->is_acf_active() ) {
			$missing_dependencies[] = '<a href="' . admin_url( 'plugin-install.php' ) . '?tab=search&s=Advanced+Custom+Fields&plugin-search-input=Search+Plugins' . '" target="_blank">' . __( 'Advanced Custom Fields', 'codepress-admin-columns' ) . '</a>';
		}

		$missing_list = '';

		if ( ! empty( $missing_dependencies ) ) {
			if ( count( $missing_dependencies ) === 1 ) {
				$missing_list = $missing_dependencies[0];
			}
			else {
				$missing_list = implode( ', ', array_slice( $missing_dependencies, 0, -1 ) );
				$missing_list = sprintf( __( '%s and %s', 'codepress-admin-columns' ), $missing_list, implode( '', array_slice( $missing_dependencies, -1 ) ) );
			}

			?>
			<tr class="plugin-update-tr">
				<td colspan="3" class="plugin-update">
					<div class="update-message">
						<?php printf( __( 'The ACF add-on is enabled but not effective. It requires %s in order to work.', 'codepress-admin-columns' ), $missing_list ); ?>
					</div>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Whether the main plugin is active
	 *
	 * @since 1.0
	 *
	 * @return bool Returns true if the main Admin Columns plugin is active, false otherwise
	 */
	public function is_cpac_active() {

		return class_exists( 'CPAC', false );
	}

	/**
	 * Whether ACF is active
	 *
	 * @since 1.1
	 *
	 * @return bool Returns true if ACF is active, false otherwise
	 */
	public function is_acf_active() {

		return function_exists( 'acf' ) && is_object( acf() );
	}

	/**
	 * Get the version of the currently active ACF plugin
	 *
	 * @since 1.1
	 *
	 * @return string Currently active ACF plugin version
	 */
	public function get_acf_version() {

		if ( $this->is_acf_active() ) {
			if ( function_exists( 'acf_get_setting' ) ) {
				$version = acf_get_setting( 'version' );
			}
			else {
				$version = apply_filters( 'acf/get_info', 'version' );
			}

			if ( $version ) {
				return $version;
			}
		}

		return false;
	}
}

new CPAC_Addon_ACF();