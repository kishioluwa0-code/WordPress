<?php

defined( 'ABSPATH' ) || die();

if ( ! class_exists( 'Edutech_Identity' ) ) {
	/**
	 * Canonical identity and scope context for all Edutech frontend roles.
	 */
	final class Edutech_Identity {
		const STUDENT       = 'student';
		const PARENT        = 'parent';
		const TEACHER       = 'teacher';
		const STAFF         = 'staff';
		const SCHOOL_ADMIN  = 'school_admin';
		const EXAM_OFFICER  = 'exam_officer';
		const DESIGNER      = 'designer';
		const UNKNOWN       = 'unknown';

		/** @var array<int, array<string, mixed>> */
		private static $contexts = array();

		/**
		 * Build a normalized context for a WordPress user.
		 *
		 * @param int $user_id User ID; current user when omitted.
		 * @return array<string, mixed>
		 */
		public static function current( $user_id = 0 ) {
			$user_id = absint( $user_id ? $user_id : get_current_user_id() );
			if ( isset( self::$contexts[ $user_id ] ) ) {
				return self::$contexts[ $user_id ];
			}

			$user  = $user_id ? get_userdata( $user_id ) : false;
			$info  = class_exists( 'WLSM_M_Role' ) ? WLSM_M_Role::get_user_info( $user_id ) : array();
			$staff = ! empty( $info['current_school'] ) && is_array( $info['current_school'] ) ? $info['current_school'] : array();
			$roles = $user && is_array( $user->roles ) ? array_values( array_map( 'sanitize_key', $user->roles ) ) : array();
			$school_ids = array();
			foreach ( (array) ( $info['schools_assigned'] ?? array() ) as $school ) {
				if ( isset( $school['id'] ) ) {
					$school_ids[] = absint( $school['id'] );
				}
			}

			$student_id       = 0;
			$parent_student_ids = array();
			$is_student       = false;
			$is_parent        = false;
			if ( $user_id && class_exists( 'WLSM_M' ) && is_callable( array( 'WLSM_M', 'get_student' ) ) ) {
				$student     = WLSM_M::get_student( $user_id );
				$is_student  = (bool) $student;
				$student_id  = $student && isset( $student->ID ) ? absint( $student->ID ) : 0;
				if ( $student && isset( $student->school_id ) ) {
					$school_ids[] = absint( $student->school_id );
				}
			}
			if ( $user_id && class_exists( 'WLSM_M_Parent' ) && is_callable( array( 'WLSM_M_Parent', 'get_parent_student_ids' ) ) ) {
				$parent_student_ids = array_values( array_filter( array_map( 'absint', (array) WLSM_M_Parent::get_parent_student_ids( $user_id ) ) ) );
				$is_parent = ! empty( $parent_student_ids );
			}

			$role = self::resolve_role( $roles, $staff, $is_student, $is_parent );
			$school_ids = array_values( array_unique( array_filter( array_map( 'absint', $school_ids ) ) ) );
			$current_school_id = isset( $staff['id'] ) ? absint( $staff['id'] ) : ( isset( $staff['school_id'] ) ? absint( $staff['school_id'] ) : ( $school_ids[0] ?? 0 ) );
			$session_id = $student_id && $user_id ? absint( get_user_meta( $user_id, 'wlsm_current_session', true ) ) : 0;
			$context = array(
				'user_id'             => $user_id,
				'username'            => $user && isset( $user->user_login ) ? sanitize_user( $user->user_login ) : '',
				'roles'               => $roles,
				'role'                => $role,
				'role_label'          => self::label( $role ),
				'school_ids'          => $school_ids,
				'current_school_id'   => $current_school_id,
				'current_school'      => $staff,
				'session_id'          => $session_id,
				'student_id'          => $student_id,
				'parent_student_ids'  => $parent_student_ids,
				'permissions'         => isset( $staff['permissions'] ) && is_array( $staff['permissions'] ) ? $staff['permissions'] : array(),
				'is_authenticated'    => $user_id > 0,
				'is_designer'         => self::DESIGNER === $role,
			);
			self::$contexts[ $user_id ] = $context;
			return $context;
		}

		/**
		 * Return only safe fields for browser-side role-aware rendering.
		 *
		 * @param int $user_id User ID.
		 * @return array<string, mixed>
		 */
		public static function frontend( $user_id = 0 ) {
			$context = self::current( $user_id );
			return array(
				'authenticated'   => (bool) $context['is_authenticated'],
				'role'            => $context['role'],
				'roleLabel'       => $context['role_label'],
				'schoolId'        => $context['current_school_id'],
				'sessionId'       => $context['session_id'],
				'studentId'       => $context['student_id'],
				'parentStudentIds'=> $context['parent_student_ids'],
				'isDesigner'      => $context['is_designer'],
			);
		}

		/** @param int $user_id User ID; clear all cached contexts when omitted. @return void */
		public static function flush( $user_id = 0 ) {
			$user_id = absint( $user_id );
			if ( $user_id ) {
				unset( self::$contexts[ $user_id ] );
				return;
			}
			self::$contexts = array();
		}

		/** @param array<string> $roles @param array<string, mixed> $staff @param bool $is_student @param bool $is_parent */
		private static function resolve_role( $roles, $staff, $is_student, $is_parent ) {
			if ( in_array( 'designer', $roles, true ) || in_array( 'edutech_designer', $roles, true ) ) {
				return self::DESIGNER;
			}
			if ( in_array( 'exam_officer', $roles, true ) || in_array( 'examination_officer', $roles, true ) ) {
				return self::EXAM_OFFICER;
			}
			if ( in_array( 'teacher', $roles, true ) ) {
				return self::TEACHER;
			}
			if ( $is_student ) {
				return self::STUDENT;
			}
			if ( $is_parent ) {
				return self::PARENT;
			}
			if ( isset( $staff['role'] ) && 'admin' === sanitize_key( $staff['role'] ) ) {
				return self::SCHOOL_ADMIN;
			}
			if ( ! empty( $staff ) || in_array( 'staff', $roles, true ) || in_array( 'employee', $roles, true ) ) {
				return self::STAFF;
			}
			if ( in_array( 'administrator', $roles, true ) || in_array( 'manage_school_management', $roles, true ) ) {
				return self::SCHOOL_ADMIN;
			}
			return self::UNKNOWN;
		}

		/** @param string $role Canonical role. */
		private static function label( $role ) {
			$labels = array(
				self::STUDENT      => __( 'Student', 'school-management' ),
				self::PARENT       => __( 'Parent', 'school-management' ),
				self::TEACHER      => __( 'Teacher', 'school-management' ),
				self::STAFF        => __( 'Staff', 'school-management' ),
				self::SCHOOL_ADMIN => __( 'School administrator', 'school-management' ),
				self::EXAM_OFFICER => __( 'Examination officer', 'school-management' ),
				self::DESIGNER     => __( 'Designer', 'school-management' ),
				self::UNKNOWN      => __( 'User', 'school-management' ),
			);
			return $labels[ $role ] ?? $labels[ self::UNKNOWN ];
		}
	}
}
