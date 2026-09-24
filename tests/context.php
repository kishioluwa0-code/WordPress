<?php

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
require dirname( __DIR__ ) . '/project/school-management-pro/includes/core/class-edutech-context.php';

$context = array(
	'schools'   => array( array( 'id' => 11 ), array( 'id' => 12 ) ),
	'campuses'  => array( array( 'id' => 21 ) ),
	'sessions'  => array( array( 'id' => 31 ) ),
	'terms'     => array( array( 'id' => 41 ) ),
	'semesters' => array( array( 'id' => 51 ) ),
	'children'  => array( array( 'id' => 61 ), array( 'id' => 62 ) ),
);

$checks = array(
	Edutech_Context::allowed( 'school', 11, $context ) === true,
	Edutech_Context::allowed( 'school', 99, $context ) === false,
	Edutech_Context::allowed( 'campus', 21, $context ) === true,
	Edutech_Context::allowed( 'campus', 99, $context ) === false,
	Edutech_Context::allowed( 'session', 31, $context ) === true,
	Edutech_Context::allowed( 'term', 41, $context ) === true,
	Edutech_Context::allowed( 'semester', 51, $context ) === true,
	Edutech_Context::allowed( 'child', 61, $context ) === true,
	Edutech_Context::allowed( 'child', 62, $context ) === true,
	Edutech_Context::allowed( 'child', 99, $context ) === false,
	Edutech_Context::allowed( 'unknown', 11, $context ) === false,
);
if ( in_array( false, $checks, true ) ) {
	fwrite( STDERR, "Context switching test failed.\n" );
	exit( 1 );
}
echo "Context switching test passed.\n";
