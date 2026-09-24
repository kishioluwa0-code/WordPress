<?php

defined( 'ABSPATH' ) || die();

if ( ! class_exists( 'Edutech_Dashboard_Routes' ) ) {
	/**
	 * Role-aware frontend dashboard routes and navigation metadata.
	 */
	final class Edutech_Dashboard_Routes {
		const QUERY_VAR = 'edutech_route';
		const DEFAULT   = 'dashboard';

		/** @return array<string, array<string, mixed>> */
		public static function definitions() {
			return array(
				'dashboard' => array( 'label' => __( 'Dashboard', 'school-management' ), 'roles' => array( 'student', 'parent', 'teacher', 'staff', 'school_admin', 'exam_officer' ), 'capability' => '' ),
				'student'   => array( 'label' => __( 'Student dashboard', 'school-management' ), 'roles' => array( 'student' ), 'capability' => '' ),
				'parent'    => array( 'label' => __( 'Parent dashboard', 'school-management' ), 'roles' => array( 'parent' ), 'capability' => '' ),
				'teacher'   => array( 'label' => __( 'Teacher dashboard', 'school-management' ), 'roles' => array( 'teacher' ), 'capability' => '' ),
				'staff'     => array( 'label' => __( 'Staff dashboard', 'school-management' ), 'roles' => array( 'staff', 'school_admin' ), 'capability' => '' ),
				'exams'     => array( 'label' => __( 'Examinations', 'school-management' ), 'roles' => array( 'teacher', 'staff', 'school_admin', 'exam_officer' ), 'capability' => 'view_exams' ),
				'profile'   => array( 'label' => __( 'Profile', 'school-management' ), 'roles' => array( 'student', 'parent', 'teacher', 'staff', 'school_admin', 'exam_officer' ), 'capability' => '' ),
			);
		}

		/** @param string $route Requested route. @return string */
		public static function normalize( $route ) {
			$route = sanitize_key( (string) $route );
			return array_key_exists( $route, self::definitions() ) ? $route : self::DEFAULT;
		}

		/** @param string $route @param array<string, mixed>|null $context @return bool */
		public static function can_access( $route, $context = null ) {
			$context = is_array( $context ) ? $context : ( class_exists( 'Edutech_Identity' ) ? Edutech_Identity::current() : array() );
			$route   = self::normalize( $route );
			$definition = self::definitions()[ $route ];
			return class_exists( 'Edutech_Policy' )
				? Edutech_Policy::can_route( $route, $context )
				: ( ! empty( $context['is_authenticated'] ) && ! empty( $context['role'] ) && in_array( $context['role'], $definition['roles'], true ) );
		}

		/** @param string $route @param string $base_url @param array<string, mixed>|null $context @return string */
		public static function url( $route, $base_url = '', $context = null ) {
			$route = self::normalize( $route );
			if ( ! self::can_access( $route, $context ) ) {
				$route = self::DEFAULT;
			}
			$base_url = $base_url ? $base_url : home_url( '/' );
			return add_query_arg( self::QUERY_VAR, $route, $base_url );
		}

		/** @param array<string, mixed>|null $context @param string $base_url @return array<int, array<string, string>> */
		public static function navigation( $context = null, $base_url = '' ) {
			$context = is_array( $context ) ? $context : ( class_exists( 'Edutech_Identity' ) ? Edutech_Identity::current() : array() );
			$items   = array();
			foreach ( self::definitions() as $route => $definition ) {
				if ( ! self::can_access( $route, $context ) ) {
					continue;
				}
				$items[] = array( 'route' => $route, 'label' => $definition['label'], 'url' => self::url( $route, $base_url, $context ) );
			}
			return $items;
		}

		/** @param array<string, mixed>|null $context @return array<string, mixed> */
		public static function frontend( $context = null ) {
			$context = is_array( $context ) ? $context : ( class_exists( 'Edutech_Identity' ) ? Edutech_Identity::current() : array() );
			$route   = isset( $_GET[ self::QUERY_VAR ] ) ? self::normalize( wp_unslash( $_GET[ self::QUERY_VAR ] ) ) : self::DEFAULT;
			if ( ! self::can_access( $route, $context ) ) {
				$route = self::can_access( self::DEFAULT, $context ) ? self::DEFAULT : '';
			}
			return array( 'activeRoute' => $route, 'items' => self::navigation( $context ) );
		}

		/** @param array<string, mixed>|null $context @return string */
		public static function body_class( $context = null ) {
			$frontend = self::frontend( $context );
			return ! empty( $frontend['activeRoute'] ) ? 'edutech-route-' . sanitize_html_class( $frontend['activeRoute'] ) : 'edutech-route-guest';
		}
	}
}
