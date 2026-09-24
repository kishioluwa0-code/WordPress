<?php
/*
 * Plugin Name: Edutech v1.0 — Education Management Platform
 * Description: Edutech v1.0 is a frontend-first education management platform for schools and institutions, with compatibility-preserving migration from the legacy School Management plugin.
 * Version: 10.7.1
 * Author: Edutech
 * Text Domain: school-management
*/

defined( 'ABSPATH' ) || die();

if ( ! defined( 'WLSM_PLUGIN_URL' ) ) {
	define( 'WLSM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'WLSM_PLUGIN_DIR_PATH' ) ) {
	define( 'WLSM_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
}

define( 'WLSM_WEBLIZAR_PLUGIN_URL', 'https://weblizar.com/plugins/school-management/' ); // Legacy compatibility URL.
define( 'WLSM_VERSION', '10.7.1' );

/* Public Edutech identity; legacy WLSM constants remain for compatibility. */
if ( ! defined( 'EDUTECH_VERSION' ) ) {
	define( 'EDUTECH_VERSION', '1.0.0' );
}

if ( ! defined( 'EDUTECH_PLUGIN_SLUG' ) ) {
	define( 'EDUTECH_PLUGIN_SLUG', 'edutech' );
}

if ( ! defined( 'EDUTECH_DB_VERSION' ) ) {
	define( 'EDUTECH_DB_VERSION', '1.0.0' );
}

require_once WLSM_PLUGIN_DIR_PATH . 'includes/core/class-edutech-environment.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/core/class-edutech-installation-health.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/core/class-edutech-migrations.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/core/class-edutech-modules.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/core/class-edutech-features.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/core/class-edutech-portal.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/core/class-edutech-portal-pages.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/core/class-edutech-auth.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/core/class-edutech-identity.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/core/class-edutech-policy.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/core/class-edutech-admin-restriction.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/core/class-edutech-context.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/core/class-edutech-dashboard-routes.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/core/class-edutech-setup-wizard.php';

final class WLSM_School_Management {
	private static $instance = null;

		private function __construct() {
			$this->initialize_hooks();
			$this->setup_database();
	}

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

		private function initialize_hooks() {
			require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Brand.php';
			add_action( 'init', array( 'Edutech_Setup_Wizard', 'handle_post' ), 1 );
			Edutech_Installation_Health::boot();
			Edutech_Auth::boot();
			Edutech_Admin_Restriction::boot();
			Edutech_Context::boot();

			if ( ! Edutech_Environment::is_compatible() ) {
				add_action( 'admin_notices', array( 'Edutech_Environment', 'render_admin_notice' ) );
				return;
			}

			if ( is_admin() ) {
			require_once WLSM_PLUGIN_DIR_PATH . 'admin/admin.php';
		}
		require_once WLSM_PLUGIN_DIR_PATH . 'public/public.php';

			// Login Redirect
			require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Login.php';
			add_filter( 'login_redirect', array( 'WLSM_Login', 'redirect_to_dashboard' ), 10, 3 );

			add_action( 'init', array( 'Edutech_Migrations', 'run' ), 1 );
			add_action( 'init', array( 'Edutech_Modules', 'boot' ), 2 );
		}

		private function setup_database() {
			require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/WLSM_Database.php';
			register_activation_hook( __FILE__, array( 'WLSM_Database', 'activation' ) );
			register_activation_hook( __FILE__, array( 'Edutech_Installation_Health', 'activate' ) );
		register_deactivation_hook( __FILE__, array( 'WLSM_Database', 'deactivation' ) );
		register_uninstall_hook( __FILE__, array( 'WLSM_Database', 'uninstall' ) );
	}
}
WLSM_School_Management::get_instance();
