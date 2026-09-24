<?php

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function __( $text ) { return $text; }
require dirname( __DIR__ ) . '/project/school-management-pro/includes/core/class-edutech-policy.php';

$teacher = array( 'is_authenticated' => true, 'role' => 'teacher', 'school_ids' => array( 3 ), 'session_id' => 42, 'permissions' => array() );
$student = array( 'is_authenticated' => true, 'role' => 'student', 'student_id' => 77, 'school_ids' => array( 3 ), 'session_id' => 42, 'permissions' => array() );
$parent = array( 'is_authenticated' => true, 'role' => 'parent', 'parent_student_ids' => array( 77, 78 ), 'school_ids' => array( 3 ), 'permissions' => array() );
$staff = array( 'is_authenticated' => true, 'role' => 'staff', 'school_ids' => array( 3 ), 'session_id' => 42, 'permissions' => array( 'manage_settings' => true ) );

$checks = array(
	Edutech_Policy::can( 'enter_scores', $teacher ) === true,
	Edutech_Policy::can( 'approve_exam_results', $teacher ) === false,
	Edutech_Policy::can( 'view_dashboard', $student, array( 'school_id' => 3, 'session_id' => 42 ) ) === true,
	Edutech_Policy::can( 'view_dashboard', $student, array( 'school_id' => 9 ) ) === false,
	Edutech_Policy::can( 'view_dashboard', $student, array( 'student_id' => 78 ) ) === false,
	Edutech_Policy::can( 'view_dashboard', $parent, array( 'student_id' => 78 ) ) === true,
	Edutech_Policy::can( 'view_dashboard', $parent, array( 'student_id' => 99 ) ) === false,
	Edutech_Policy::can( 'manage_settings', $staff ) === true,
	Edutech_Policy::can( 'view_audit_log', $staff ) === false,
	Edutech_Policy::can( 'anything', array( 'is_authenticated' => true, 'role' => 'designer', 'is_designer' => true ), array( 'school_id' => 999 ) ) === true,
);
if ( in_array( false, $checks, true ) ) {
	fwrite( STDERR, "Central policy authorization test failed.\n" );
	exit( 1 );
}
echo "Central policy authorization test passed.\n";
