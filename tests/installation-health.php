<?php

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['wp_version'] = '6.6';
$GLOBALS['wpdb']       = (object) array( 'dbh' => true );
$GLOBALS['edutech_options'] = array(
	'edutech_db_version'      => '1.0.0',
	'edutech_migration_state' => array( 'status' => 'completed' ),
	'edutech_portal_pages'    => array(
		'login'                 => 101,
		'portal'                => 102,
		'dashboard'             => 103,
		'student'               => 104,
		'parent'                => 105,
		'teacher'               => 106,
		'school-administration' => 107,
		'profile'               => 108,
		'password-reset'        => 109,
		'notifications'         => 110,
		'exam-results'          => 111,
		'practice-cbt'          => 112,
		'official-cbt'          => 113,
		'fees'                  => 114,
		'support'               => 115,
	),
);

function add_action( $tag, $callback, $priority = 10 ) {}
function current_user_can( $capability ) { return 'manage_options' === $capability; }
function current_time( $type, $gmt = false ) { return '2026-09-25 00:00:00'; }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['edutech_options'] ) ? $GLOBALS['edutech_options'][ $key ] : $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['edutech_options'][ $key ] = $value; return true; }
function wp_upload_dir() { return array( 'basedir' => '/tmp' ); }
function wp_list_pluck( $list, $field ) { return array_map( static function ( $item ) use ( $field ) { return $item[ $field ]; }, $list ); }
function get_role( $role ) { return new class { public function has_cap( $capability ) { return 'manage_options' === $capability; } }; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function esc_html__( $text, $domain = null ) { return $text; }
function esc_html( $text ) { return $text; }
function apply_filters( $tag, $value ) { return $value; }
function __( $text, $domain = null ) { return $text; }
function absint( $value ) { return abs( (int) $value ); }
function get_post_type( $id ) { return ( (int) $id >= 101 && (int) $id <= 115 ) ? 'page' : false; }

require dirname( __DIR__ ) . '/project/school-management-pro/includes/core/class-edutech-environment.php';
require dirname( __DIR__ ) . '/project/school-management-pro/includes/core/class-edutech-migrations.php';
require dirname( __DIR__ ) . '/project/school-management-pro/includes/core/class-edutech-portal-pages.php';
require dirname( __DIR__ ) . '/project/school-management-pro/includes/core/class-edutech-installation-health.php';

$healthy = Edutech_Installation_Health::report();
assert( true === $healthy['ok'], 'A complete installation should report healthy.' );
assert( true === $healthy['checks']['runtime'], 'Runtime requirements should pass.' );
assert( true === $healthy['checks']['migrations'], 'Completed migrations should pass.' );
assert( true === $healthy['checks']['system_pages'], 'Registered system pages should pass.' );
assert( false === $healthy['integrations']['elementor'], 'Elementor detection should be false when absent.' );
assert( false === $healthy['integrations']['gutenberg'], 'Gutenberg detection should be false when absent.' );
$assignments = Edutech_Portal_Pages::assignment_report();
assert( true === $assignments['complete'], 'All Slice 4.3 system pages should be assigned.' );
assert( isset( $assignments['assignments']['portal'] ), 'The legacy portal page key must remain available.' );
assert( isset( $assignments['assignments']['school-administration'] ), 'School administration must be a managed system page.' );

$GLOBALS['edutech_options']['edutech_db_version'] = '0.0.0';
$GLOBALS['edutech_options']['edutech_portal_pages']['dashboard'] = 0;
$needs_repair = Edutech_Installation_Health::report();
assert( false === $needs_repair['ok'], 'An incomplete installation should report unhealthy.' );
assert( false === $needs_repair['checks']['migrations'], 'Outdated migrations should be reported.' );
assert( false === $needs_repair['checks']['system_pages'], 'Missing system pages should be reported.' );
assert( count( $needs_repair['issues'] ) >= 2, 'The report should contain actionable repair issues.' );

echo "Installation health compatibility test passed.\n";
