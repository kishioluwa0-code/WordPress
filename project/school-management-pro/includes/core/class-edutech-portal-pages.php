<?php

defined( 'ABSPATH' ) || die();

if ( ! class_exists( 'Edutech_Portal_Pages' ) ) {
	/**
	 * Metadata, assignment, and safe regeneration helpers for portal system pages.
	 */
	final class Edutech_Portal_Pages {
		const OPTION = 'edutech_portal_pages';

		/** @return array<string, array<string, string>> */
		public static function definitions() {
			$account = '[school_management_account mode="full-width"]';
			$definitions = array(
				'login'               => array( 'slug' => 'edutech-login', 'title' => __( 'Edutech Login', 'school-management' ), 'content' => '[school_management_account mode="embedded"]' ),
				'portal'              => array( 'slug' => 'edutech-portal', 'title' => __( 'Edutech Portal', 'school-management' ), 'content' => $account ),
				'dashboard'           => array( 'slug' => 'edutech-dashboard', 'title' => __( 'Edutech Dashboard', 'school-management' ), 'content' => $account ),
				'student'             => array( 'slug' => 'edutech-student', 'title' => __( 'Edutech Student', 'school-management' ), 'content' => $account ),
				'parent'              => array( 'slug' => 'edutech-parent', 'title' => __( 'Edutech Parent', 'school-management' ), 'content' => $account ),
				'teacher'             => array( 'slug' => 'edutech-teacher', 'title' => __( 'Edutech Teacher', 'school-management' ), 'content' => $account ),
				'school-administration' => array( 'slug' => 'edutech-school-administration', 'title' => __( 'Edutech School Administration', 'school-management' ), 'content' => $account ),
				'profile'             => array( 'slug' => 'edutech-profile', 'title' => __( 'Edutech Profile', 'school-management' ), 'content' => $account ),
				'password-reset'      => array( 'slug' => 'edutech-password-reset', 'title' => __( 'Edutech Password Reset', 'school-management' ), 'content' => '[school_management_account mode="embedded"]' ),
				'notifications'       => array( 'slug' => 'edutech-notifications', 'title' => __( 'Edutech Notifications', 'school-management' ), 'content' => $account ),
				'exam-results'        => array( 'slug' => 'edutech-exam-results', 'title' => __( 'Edutech Exam Results', 'school-management' ), 'content' => $account ),
				'practice-cbt'        => array( 'slug' => 'edutech-practice-cbt', 'title' => __( 'Edutech Practice CBT', 'school-management' ), 'content' => $account ),
				'official-cbt'        => array( 'slug' => 'edutech-official-cbt', 'title' => __( 'Edutech Official CBT', 'school-management' ), 'content' => $account ),
				'fees'                => array( 'slug' => 'edutech-fees', 'title' => __( 'Edutech Fees', 'school-management' ), 'content' => $account ),
				'support'             => array( 'slug' => 'edutech-support', 'title' => __( 'Edutech Support', 'school-management' ), 'content' => $account ),
			);

			/** @param array<string, array<string, string>> $definitions Definitions. */
			return apply_filters( 'edutech_portal_page_definitions', $definitions );
		}

		/** @param string $key Definition key. @return int */
		public static function get_page_id( $key ) {
			$key   = sanitize_key( $key );
			$pages = get_option( self::OPTION, array() );
			$id    = isset( $pages[ $key ] ) ? absint( $pages[ $key ] ) : 0;
			return $id && 'page' === get_post_type( $id ) ? $id : 0;
		}

		/** @param string $key Definition key. @return int|WP_Error */
		public static function ensure( $key ) {
			$key         = sanitize_key( $key );
			$definitions = self::definitions();
			if ( empty( $definitions[ $key ] ) ) {
				return new WP_Error( 'edutech_page_not_found', __( 'Portal page definition not found.', 'school-management' ) );
			}
			$existing_id = self::get_page_id( $key );
			if ( $existing_id ) {
				return $existing_id;
			}

			$definition = $definitions[ $key ];
			$existing   = get_page_by_path( $definition['slug'], OBJECT, 'page' );
			$page_id    = $existing ? absint( $existing->ID ) : wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $definition['title'],
					'post_name'    => $definition['slug'],
					'post_content' => $definition['content'],
				),
				true
			);
			if ( is_wp_error( $page_id ) ) {
				return $page_id;
			}

			$pages         = get_option( self::OPTION, array() );
			$pages[ $key ] = absint( $page_id );
			update_option( self::OPTION, $pages, false );
			do_action( 'edutech_portal_page_registered', $key, absint( $page_id ), $definition );
			return absint( $page_id );
		}

		/** @return array<string, int|WP_Error> */
		public static function ensure_all() {
			$pages = array();
			foreach ( self::definitions() as $key => $definition ) {
				$pages[ $key ] = self::ensure( $key );
			}
			return $pages;
		}

		/** @return array<string, int|WP_Error> */
		public static function regenerate_missing() {
			$pages = array();
			foreach ( self::definitions() as $key => $definition ) {
				$pages[ $key ] = self::get_page_id( $key ) ? self::get_page_id( $key ) : self::ensure( $key );
			}
			return $pages;
		}

		/** @return array<string, array<string, mixed>> */
		public static function assignments() {
			$assignments = array();
			foreach ( self::definitions() as $key => $definition ) {
				$page_id = self::get_page_id( $key );
				$assignments[ $key ] = array(
					'key'       => $key,
					'title'     => $definition['title'],
					'slug'      => $definition['slug'],
					'page_id'   => $page_id,
					'assigned'  => (bool) $page_id,
					'url'       => $page_id && function_exists( 'get_permalink' ) ? get_permalink( $page_id ) : '',
				);
			}
			return $assignments;
		}

		/** @return array<string, mixed> */
		public static function assignment_report() {
			$assignments = self::assignments();
			$missing     = array();
			foreach ( $assignments as $key => $assignment ) {
				if ( empty( $assignment['assigned'] ) ) {
					$missing[] = $key;
				}
			}
			return array( 'complete' => empty( $missing ), 'missing' => $missing, 'assignments' => $assignments );
		}
	}
}
