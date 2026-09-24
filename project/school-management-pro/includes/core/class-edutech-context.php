<?php

defined( 'ABSPATH' ) || die();

if ( ! class_exists( 'Edutech_Context' ) ) {
	/**
	 * Validated school, session, and parent-child context service.
	 *
	 * Selected IDs are stored server-side and every switch is checked against
	 * the canonical identity context before any state is changed.
	 */
	final class Edutech_Context {
		const SCHOOL_META   = 'edutech_current_school';
		const CAMPUS_META   = 'edutech_current_campus';
		const SESSION_META  = 'wlsm_current_session';
		const TERM_META     = 'edutech_current_term';
		const SEMESTER_META = 'edutech_current_semester';
		const CHILD_META    = 'wlsm_current_student_id';
		const AUDIT_ACTION   = 'edutech_context_changed';

		/** @return void */
		public static function boot() {
			add_action( 'init', array( __CLASS__, 'validate_request' ), 3 );
			add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		}

		/**
		 * Return a validated context and available switch targets.
		 *
		 * @param int $user_id User ID.
		 * @return array<string, mixed>
		 */
		public static function current( $user_id = 0 ) {
			$identity = class_exists( 'Edutech_Identity' ) ? Edutech_Identity::current( $user_id ) : array();
			$user_id  = absint( $identity['user_id'] ?? $user_id );
			$schools  = self::schools( $identity );
			$school_ids = array_map( 'absint', wp_list_pluck( $schools, 'id' ) );
			$stored_school = $user_id ? absint( get_user_meta( $user_id, self::SCHOOL_META, true ) ) : 0;
			$current_school = self::first_allowed( $stored_school, $school_ids );
			if ( ! $current_school ) {
				$current_school = self::first_allowed( $identity['current_school_id'] ?? 0, $school_ids );
			}
			$session_ids = self::session_ids();
			$stored_session = $user_id ? absint( get_user_meta( $user_id, self::SESSION_META, true ) ) : 0;
			$current_session = self::first_allowed( $stored_session, $session_ids );
			if ( ! $current_session ) {
				$current_session = self::first_allowed( $identity['session_id'] ?? 0, $session_ids );
			}
			$children = self::children( $identity );
			$child_ids = array_map( 'absint', wp_list_pluck( $children, 'id' ) );
			$stored_child = $user_id ? absint( get_user_meta( $user_id, self::CHILD_META, true ) ) : 0;
			$current_child = self::first_allowed( $stored_child, $child_ids );
			if ( ! $current_child ) {
				$current_child = self::first_allowed( $identity['student_id'] ?? 0, $child_ids );
			}
			$campuses = self::generic_scope( 'campuses', 'edutech_available_campuses', $identity );
			$terms = self::generic_scope( 'terms', 'edutech_available_terms', $identity );
			$semesters = self::generic_scope( 'semesters', 'edutech_available_semesters', $identity );
			return array(
				'userId'        => $user_id,
				'schoolId'      => $current_school,
				'sessionId'     => $current_session,
				'childId'       => $current_child,
				'campusId'      => self::stored_scope( $user_id, self::CAMPUS_META, $campuses, $identity['campus_id'] ?? 0 ),
				'termId'        => self::stored_scope( $user_id, self::TERM_META, $terms, $identity['term_id'] ?? 0 ),
				'semesterId'    => self::stored_scope( $user_id, self::SEMESTER_META, $semesters, $identity['semester_id'] ?? 0 ),
				'schools'       => $schools,
				'sessions'      => self::sessions(),
				'children'      => $children,
				'campuses'      => $campuses,
				'terms'         => $terms,
				'semesters'     => $semesters,
				'validated'     => (bool) ( $identity['is_authenticated'] ?? false ),
			);
		}

		/** @return void */
		public static function validate_request() {
			if ( ! is_user_logged_in() ) {
				return;
			}
			$context = self::current();
			$user_id = absint( $context['userId'] ?? 0 );
			if ( ! $user_id ) {
				return;
			}
			$stored_school = absint( get_user_meta( $user_id, self::SCHOOL_META, true ) );
			if ( $stored_school && ! self::contains( $context['schools'], $stored_school ) ) {
				delete_user_meta( $user_id, self::SCHOOL_META );
				self::audit( 'invalid_school_reset', $user_id, $stored_school, 0, 0 );
			}
			$stored_child = absint( get_user_meta( $user_id, self::CHILD_META, true ) );
			if ( $stored_child && ! self::contains( $context['children'], $stored_child ) ) {
				delete_user_meta( $user_id, self::CHILD_META );
				self::audit( 'invalid_child_reset', $user_id, 0, 0, $stored_child );
			}
		}

		/** @param int $school_id @param int $user_id @return bool */
		public static function switch_school( $school_id, $user_id = 0 ) {
			$context = self::current( $user_id );
			$school_id = absint( $school_id );
			if ( ! $school_id || ! self::contains( $context['schools'], $school_id ) ) {
				return false;
			}
			$user_id = absint( $context['userId'] );
			update_user_meta( $user_id, self::SCHOOL_META, $school_id );
			update_user_meta( $user_id, 'wlsm_school_id', $school_id );
			self::invalidate( $user_id );
			self::audit( 'school', $user_id, $school_id, $context['sessionId'], $context['childId'] );
			return true;
		}

		/** @param int $session_id @param int $user_id @return bool */
		public static function switch_session( $session_id, $user_id = 0 ) {
			$context = self::current( $user_id );
			$session_id = absint( $session_id );
			if ( ! $session_id || ! self::contains_id( $context['sessions'], $session_id ) ) {
				return false;
			}
			$user_id = absint( $context['userId'] );
			update_user_meta( $user_id, self::SESSION_META, $session_id );
			self::invalidate( $user_id );
			self::audit( 'session', $user_id, $context['schoolId'], $session_id, $context['childId'] );
			return true;
		}

		/** @param int $student_id @param int $user_id @return bool */
		public static function switch_child( $student_id, $user_id = 0 ) {
			$context = self::current( $user_id );
			$student_id = absint( $student_id );
			if ( ! $student_id || ! self::contains( $context['children'], $student_id ) ) {
				return false;
			}
			$user_id = absint( $context['userId'] );
			update_user_meta( $user_id, self::CHILD_META, $student_id );
			self::invalidate( $user_id );
			self::audit( 'child', $user_id, $context['schoolId'], $context['sessionId'], $student_id );
			return true;
		}

		/** @param string $dimension @param int $value @param array<string, mixed> $context @return bool */
		public static function allowed( $dimension, $value, $context ) {
			$collections = array( 'school' => 'schools', 'campus' => 'campuses', 'session' => 'sessions', 'term' => 'terms', 'semester' => 'semesters', 'child' => 'children' );
			$key = $collections[ sanitize_key( $dimension ) ] ?? '';
			return $key && self::contains_id( $context[ $key ] ?? array(), absint( $value ) );
		}

		/** @return void */
		public static function register_rest_routes() {
			register_rest_route( 'edutech/v1', '/context', array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( __CLASS__, 'rest_get' ),
					'permission_callback' => array( __CLASS__, 'rest_permission' ),
				),
				array(
					'methods' => WP_REST_Server::CREATABLE,
					'callback' => array( __CLASS__, 'rest_switch' ),
					'permission_callback' => array( __CLASS__, 'rest_permission' ),
					'args' => array(
						'dimension' => array( 'required' => true, 'sanitize_callback' => 'sanitize_key' ),
						'value' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
					),
				),
			) );
		}

		/** @param WP_REST_Request $request @return array<string, mixed> */
		public static function rest_get( $request ) {
			return self::current();
		}

		/** @param WP_REST_Request $request @return array<string, mixed>|WP_Error */
		public static function rest_switch( $request ) {
			$dimension = sanitize_key( $request->get_param( 'dimension' ) );
			$value = absint( $request->get_param( 'value' ) );
			$success = false;
			if ( 'school' === $dimension ) {
				$success = self::switch_school( $value );
			} elseif ( 'session' === $dimension ) {
				$success = self::switch_session( $value );
			} elseif ( 'child' === $dimension ) {
				$success = self::switch_child( $value );
			} elseif ( in_array( $dimension, array( 'campus', 'term', 'semester' ), true ) ) {
				$context = self::current();
				if ( self::allowed( $dimension, $value, $context ) ) {
					$meta = array( 'campus' => self::CAMPUS_META, 'term' => self::TERM_META, 'semester' => self::SEMESTER_META )[ $dimension ];
					update_user_meta( absint( $context['userId'] ), $meta, $value );
					self::invalidate( absint( $context['userId'] ) );
					self::audit( $dimension, absint( $context['userId'] ), $context['schoolId'], $context['sessionId'], $context['childId'] );
					$success = true;
				}
			}
			if ( ! $success ) {
				return new WP_Error( 'edutech_invalid_context', __( 'The requested context is not available to this account.', 'school-management' ), array( 'status' => 403 ) );
			}
			return self::current();
		}

		/** @return bool */
		public static function rest_permission() {
			return is_user_logged_in() && ( ! class_exists( 'Edutech_Policy' ) || Edutech_Policy::can( 'view_dashboard' ) );
		}

		/** @param array<string, mixed> $identity @return array<int, array<string, mixed>> */
		private static function schools( $identity ) {
			$schools = array();
			foreach ( (array) ( $identity['school_ids'] ?? array() ) as $id ) {
				$id = absint( $id );
				if ( ! $id ) {
					continue;
				}
				$label = '';
				if ( class_exists( 'WLSM_M_School' ) && is_callable( array( 'WLSM_M_School', 'get_active_school' ) ) ) {
					$school = WLSM_M_School::get_active_school( $id );
					if ( ! $school ) {
						continue;
					}
					$label = isset( $school->label ) ? sanitize_text_field( $school->label ) : '';
				}
				$schools[] = array( 'id' => $id, 'label' => $label );
			}
			return $schools;
		}

		/** @return array<int, array<string, mixed>> */
		private static function sessions() {
			$sessions = array();
			if ( class_exists( 'WLSM_M_Session' ) && is_callable( array( 'WLSM_M_Session', 'fetch_sessions' ) ) ) {
				foreach ( (array) WLSM_M_Session::fetch_sessions() as $session ) {
					if ( isset( $session->ID ) ) {
						$sessions[] = array( 'id' => absint( $session->ID ), 'label' => isset( $session->label ) ? sanitize_text_field( $session->label ) : '' );
					}
				}
			}
			return $sessions;
		}

		/** @return array<int, int> */
		private static function session_ids() {
			return array_map( 'absint', wp_list_pluck( self::sessions(), 'id' ) );
		}

		/** @param array<string, mixed> $identity @return array<int, array<string, mixed>> */
		private static function children( $identity ) {
			$children = array();
			foreach ( (array) ( $identity['parent_student_ids'] ?? array() ) as $id ) {
				$id = absint( $id );
				if ( $id ) {
					$children[] = array( 'id' => $id );
				}
			}
			if ( 'student' === ( $identity['role'] ?? '' ) && ! empty( $identity['student_id'] ) ) {
				$children[] = array( 'id' => absint( $identity['student_id'] ) );
			}
			return array_values( array_unique( $children, SORT_REGULAR ) );
		}

		/** @param int $user_id @param string $meta @param array<int, array<string, mixed>> $items @param int $fallback @return int */
		private static function stored_scope( $user_id, $meta, $items, $fallback ) {
			$ids = array_map( 'absint', wp_list_pluck( $items, 'id' ) );
			return self::first_allowed( $user_id ? get_user_meta( $user_id, $meta, true ) : 0, $ids ) ?: self::first_allowed( $fallback, $ids );
		}

		/** @param string $key @param string $filter @param array<string, mixed> $identity @return array<int, array<string, mixed>> */
		private static function generic_scope( $key, $filter, $identity ) {
			$values = apply_filters( $filter, (array) ( $identity[ $key . '_ids' ] ?? array() ), $identity );
			$result = array();
			foreach ( (array) $values as $value ) {
				if ( is_object( $value ) ) {
					$value = array( 'id' => $value->ID ?? 0, 'label' => $value->label ?? '' );
				}
				if ( is_array( $value ) ) {
					$id = absint( $value['id'] ?? $value['ID'] ?? 0 );
					$label = sanitize_text_field( $value['label'] ?? '' );
				} else {
					$id = absint( $value );
					$label = '';
				}
				if ( $id ) {
					$result[] = array( 'id' => $id, 'label' => $label );
				}
			}
			return $result;
		}

		/** @param int $value @param array<int, int> $allowed @return int */
		private static function first_allowed( $value, $allowed ) {
			$value = absint( $value );
			return $value && in_array( $value, array_map( 'absint', (array) $allowed ), true ) ? $value : ( absint( $allowed[0] ?? 0 ) );
		}

		/** @param array<int, array<string, mixed>> $items @param int $id @return bool */
		private static function contains( $items, $id ) {
			return self::contains_id( $items, $id );
		}

		/** @param array<int, mixed> $items @param int $id @return bool */
		private static function contains_id( $items, $id ) {
			foreach ( (array) $items as $item ) {
				$candidate = is_array( $item ) ? ( $item['id'] ?? 0 ) : ( is_object( $item ) && isset( $item->ID ) ? $item->ID : $item );
				if ( absint( $candidate ) === absint( $id ) ) {
					return true;
				}
			}
			return false;
		}

		/** @param int $user_id @return void */
		private static function invalidate( $user_id ) {
			if ( function_exists( 'wp_cache_delete' ) ) {
				wp_cache_delete( 'wlsm_user_info' );
			}
			if ( class_exists( 'Edutech_Identity' ) && is_callable( array( 'Edutech_Identity', 'flush' ) ) ) {
				Edutech_Identity::flush( $user_id );
			}
			do_action( 'edutech_context_cache_invalidated', $user_id );
		}

		/** @param string $dimension @param int $user_id @param int $school_id @param int $session_id @param int $child_id @return void */
		private static function audit( $dimension, $user_id, $school_id, $session_id, $child_id ) {
			do_action( self::AUDIT_ACTION, $dimension, array( 'user_id' => absint( $user_id ), 'school_id' => absint( $school_id ), 'session_id' => absint( $session_id ), 'child_id' => absint( $child_id ) ) );
		}
	}
}
