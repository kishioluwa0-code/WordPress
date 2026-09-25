<?php

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
function __( $text, $domain = null ) { return $text; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html_e( $value, $domain = null ) { echo esc_html( $value ); }
function esc_attr_e( $value, $domain = null ) { echo esc_attr( $value ); }
function esc_url( $value ) { return $value; }
function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function add_query_arg( $key, $value, $url ) { return $url . '?' . $key . '=' . $value; }
function wp_logout_url( $url ) { return '/logout?redirect=' . rawurlencode( $url ); }
function wp_get_current_user() { return (object) array( 'display_name' => 'Amina' ); }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_html_class( $value ) { return sanitize_key( $value ); }
function current_user_can( $capability ) { return true; }
function wp_unslash( $value ) { return $value; }

final class Edutech_Identity {
	public static function current() { return array( 'role' => 'school_admin', 'role_label' => 'School administrator', 'username' => 'Amina', 'current_school' => array( 'label' => 'Greenfield Academy' ), 'session_id' => 3 ); }
	public static function frontend() { return array( 'role' => 'school_admin' ); }
}
final class Edutech_Dashboard_Routes {
	public static function frontend( $context = null ) { return array( 'activeRoute' => 'dashboard', 'items' => array( array( 'route' => 'dashboard', 'label' => 'Dashboard', 'url' => '/?edutech_route=dashboard' ), array( 'route' => 'staff', 'label' => 'Staff dashboard', 'url' => '/?edutech_route=staff' ) ) ); }
}

require dirname( __DIR__ ) . '/project/school-management-pro/includes/core/class-edutech-dashboard-shell.php';
ob_start();
Edutech_Dashboard_Shell::open();
Edutech_Dashboard_Shell::close();
$html = ob_get_clean();
foreach ( array( 'edutech-control-center', 'edutech-control-center__sidebar', 'edutech-welcome-panel', 'edutech-kpi-grid', 'edutech-quick-actions', 'edutech-mobile-menu', 'data-edutech-role="school_admin"' ) as $hook ) {
	assert( false !== strpos( $html, $hook ), "Missing control-center hook: {$hook}" );
}
assert( false !== strpos( $html, 'Greenfield Academy' ), 'School context should be visible.' );
assert( false !== strpos( $html, 'School administrator' ), 'Role label should be visible.' );
echo "Dashboard shell compatibility test passed.\n";
