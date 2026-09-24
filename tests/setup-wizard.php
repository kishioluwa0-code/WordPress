<?php

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['edutech_options'] = array(
	'edutech_setup_wizard' => array( 'status' => 'in_progress', 'step' => 'identity', 'school_name' => '' ),
	'edutech_migration_state' => array( 'status' => 'completed' ),
	'edutech_db_version' => '1.0.0',
	'edutech_portal_pages' => array( 'login' => 101, 'portal' => 102, 'practice-cbt' => 103, 'official-cbt' => 104 ),
);

function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['edutech_options'] ) ? $GLOBALS['edutech_options'][ $key ] : $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['edutech_options'][ $key ] = $value; return true; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function wp_unslash( $value ) { return $value; }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function apply_filters( $tag, $value ) { return $value; }
function __( $text, $domain = null ) { return $text; }
function is_user_logged_in() { return true; }
function absint( $value ) { return abs( (int) $value ); }
function current_time( $type, $gmt = false ) { return '2026-09-25 00:00:00'; }
function wp_upload_dir() { return array( 'basedir' => '/tmp' ); }

final class Edutech_Identity {
	public static function current() { return array( 'role' => 'designer', 'is_designer' => true ); }
}

require dirname( __DIR__ ) . '/project/school-management-pro/includes/core/class-edutech-installation-health.php';
require dirname( __DIR__ ) . '/project/school-management-pro/includes/core/class-edutech-setup-wizard.php';

$templates = Edutech_Setup_Wizard::templates();
assert( count( $templates ) >= 7, 'All supported institution templates should be available.' );
assert( 'NGN' === $templates['nigerian-primary']['currency'], 'Nigerian primary should default to NGN.' );

$state = Edutech_Setup_Wizard::apply_template( 'nigerian-primary' );
assert( 'nigerian-primary' === $state['template'], 'Applying a template should persist its key.' );
assert( 'three_terms' === $state['calendar'], 'Templates should provide editable calendar defaults.' );

$state = Edutech_Setup_Wizard::save( array( 'step' => 'identity', 'school_name' => 'Greenfield Academy', 'school_email' => 'admin@example.test', 'grading' => 'percentage' ) );
assert( 'Greenfield Academy' === $state['school_name'], 'Wizard identity values should be resumable.' );
assert( 'identity' === $state['step'], 'Wizard progress should persist the current step.' );
assert( true === Edutech_Setup_Wizard::can_manage(), 'Only the designer identity should manage setup.' );
assert( count( Edutech_Setup_Wizard::tasks() ) >= 1, 'Incomplete setup should expose actionable tasks.' );

$state = Edutech_Setup_Wizard::save( array( 'country' => 'NG', 'institution_type' => 'primary', 'education_level' => 'primary', 'calendar' => 'three_terms', 'currency' => 'NGN', 'school_name' => 'Greenfield Academy', 'grading' => 'percentage' ) );
assert( 'Greenfield Academy' === $state['school_name'], 'Saving later steps must not erase prior configuration.' );

echo "Setup wizard compatibility test passed.\n";
