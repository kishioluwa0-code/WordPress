<?php

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
function apply_filters( $tag, $value ) { return $value; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function __( $text ) { return $text; }
require dirname( __DIR__ ) . '/project/school-management-pro/includes/core/class-edutech-admin-restriction.php';

$operator = array( 'user_id' => 10, 'role' => 'staff', 'is_authenticated' => true, 'is_designer' => false );
$designer = array( 'user_id' => 13, 'role' => 'designer', 'is_authenticated' => true, 'is_designer' => true );
$anonymous = array( 'user_id' => 0, 'role' => 'unknown', 'is_authenticated' => false, 'is_designer' => false );

$checks = array(
	Edutech_Admin_Restriction::should_restrict( $operator, array() ) === true,
	Edutech_Admin_Restriction::should_restrict( $designer, array() ) === false,
	Edutech_Admin_Restriction::should_restrict( $anonymous, array() ) === false,
	Edutech_Admin_Restriction::should_restrict( $operator, array( 'safe' => true ) ) === false,
	Edutech_Admin_Restriction::should_restrict( $operator, array( 'cron' => true ) ) === false,
	Edutech_Admin_Restriction::should_restrict( $operator, array( 'rest' => true ) ) === false,
	Edutech_Admin_Restriction::should_restrict( $operator, array( 'webhook' => true ) ) === false,
	Edutech_Admin_Restriction::is_emergency_recovery( $operator ) === false,
);
if ( in_array( false, $checks, true ) ) {
	fwrite( STDERR, "Admin restriction test failed.\n" );
	exit( 1 );
}
echo "Admin restriction test passed.\n";
