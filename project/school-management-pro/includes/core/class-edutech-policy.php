<?php

defined( 'ABSPATH' ) || die();

if ( ! class_exists( 'Edutech_Policy' ) ) {
	/**
	 * Central authorization policy for frontend routes, APIs, objects, and workflows.
	 *
	 * This is deliberately an adapter: legacy role permissions remain authoritative
	 * where they exist, while new modules get one consistent policy contract.
	 */
	final class Edutech_Policy {
		const VIEW_DASHBOARD    = 'view_dashboard';
		const VIEW_EXAMS        = 'view_exams';
		const ENTER_SCORES      = 'enter_scores';
		const SUBMIT_SCORES     = 'submit_scores';
		const REVIEW_SCORES     = 'review_scores';
		const APPROVE_RESULTS   = 'approve_exam_results';
		const FINALIZE_RESULTS  = 'finalize_results';
		const PUBLISH_RESULTS   = 'publish_results';
		const MANAGE_SETTINGS   = 'manage_settings';
		const MANAGE_CBT        = 'manage_cbt';
		const VIEW_AUDIT_LOG    = 'view_audit_log';

		/** @var array<string, array<int, string>> */
		private static $role_defaults = array(
			'view_dashboard'      => array( 'student', 'parent', 'teacher', 'staff', 'school_admin', 'exam_officer' ),
			'view_exams'          => array( 'teacher', 'staff', 'school_admin', 'exam_officer' ),
			'enter_scores'        => array( 'teacher', 'exam_officer' ),
			'submit_scores'       => array( 'teacher', 'exam_officer' ),
			'review_scores'       => array( 'school_admin', 'exam_officer' ),
			'approve_exam_results'=> array( 'school_admin', 'exam_officer' ),
			'finalize_results'    => array( 'school_admin', 'exam_officer' ),
			'publish_results'     => array( 'school_admin', 'exam_officer' ),
			'manage_settings'    => array( 'school_admin' ),
			'manage_cbt'         => array( 'school_admin', 'exam_officer' ),
			'view_audit_log'     => array( 'school_admin', 'exam_officer' ),
		);

		/** @param string $ability @param array<string, mixed>|null $context @param array<string, mixed>|null $resource */
		public static function can( $ability, $context = null, $resource = null ) {
			$context  = self::context( $context );
			$ability  = sanitize_key( $ability );
			$resource = is_array( $resource ) ? $resource : array();
			if ( empty( $context['is_authenticated'] ) || 'unknown' === ( $context['role'] ?? 'unknown' ) ) {
				return false;
			}
			if ( ! self::scope( $context, $resource ) || ! self::ownership( $context, $resource ) ) {
				return false;
			}
			if ( ! empty( $context['is_designer'] ) || 'designer' === ( $context['role'] ?? '' ) ) {
				return true;
			}
			if ( ! empty( $context['permissions']['*'] ) || ! empty( $context['permissions'][ $ability ] ) ) {
				return true;
			}
			return in_array( $context['role'] ?? '', self::$role_defaults[ $ability ] ?? array(), true );
		}

		/** @param string $route @param array<string, mixed>|null $context */
		public static function can_route( $route, $context = null ) {
			if ( ! class_exists( 'Edutech_Dashboard_Routes' ) ) {
				return false;
			}
			$definition = Edutech_Dashboard_Routes::definitions()[ Edutech_Dashboard_Routes::normalize( $route ) ] ?? array();
			if ( empty( $definition ) ) {
				return false;
			}
			$context = self::context( $context );
			if ( ! in_array( $context['role'] ?? '', $definition['roles'] ?? array(), true ) ) {
				return false;
			}
			return empty( $definition['capability'] ) || self::can( $definition['capability'], $context );
		}

		/** @param array<string, mixed>|null $context @param array<string, mixed> $resource */
		private static function scope( $context, $resource ) {
			if ( isset( $resource['school_id'] ) && $resource['school_id'] ) {
				$schools = array_map( 'absint', (array) ( $context['school_ids'] ?? array() ) );
				if ( ! in_array( absint( $resource['school_id'] ), $schools, true ) && 'designer' !== ( $context['role'] ?? '' ) ) {
					return false;
				}
			}
			if ( isset( $resource['session_id'] ) && $resource['session_id'] && ! empty( $context['session_id'] ) && absint( $resource['session_id'] ) !== absint( $context['session_id'] ) && 'designer' !== ( $context['role'] ?? '' ) ) {
				return false;
			}
			return true;
		}

		/** @param array<string, mixed> $context @param array<string, mixed> $resource */
		private static function ownership( $context, $resource ) {
			if ( ! isset( $resource['student_id'] ) || ! $resource['student_id'] ) {
				return true;
			}
			$student_id = absint( $resource['student_id'] );
			if ( 'student' === ( $context['role'] ?? '' ) ) {
				return $student_id === absint( $context['student_id'] ?? 0 );
			}
			if ( 'parent' === ( $context['role'] ?? '' ) ) {
				return in_array( $student_id, array_map( 'absint', (array) ( $context['parent_student_ids'] ?? array() ) ), true );
			}
			return true;
		}

		/** @param array<string, mixed>|null $context @return array<string, mixed> */
		private static function context( $context ) {
			return is_array( $context ) ? $context : ( class_exists( 'Edutech_Identity' ) ? Edutech_Identity::current() : array() );
		}
	}
}

/** @deprecated Use Edutech_Policy::can(). */
if ( ! function_exists( 'edutech_can' ) ) {
	function edutech_can( $ability, $context = null, $resource = null ) {
		return Edutech_Policy::can( $ability, $context, $resource );
	}
}

/** @deprecated Use Edutech_Policy::can(). */
if ( ! function_exists( 'edutech_can_access' ) ) {
	function edutech_can_access( $ability, $context = null, $resource = null ) {
		return Edutech_Policy::can( $ability, $context, $resource );
	}
}

/** @deprecated Use Edutech_Policy::can_route(). */
if ( ! function_exists( 'edutech_can_route' ) ) {
	function edutech_can_route( $route, $context = null ) {
		return Edutech_Policy::can_route( $route, $context );
	}
}
