<?php

defined( 'ABSPATH' ) || die();

if ( ! class_exists( 'Edutech_Admin_Restriction' ) ) {
	/**
	 * Keeps WordPress admin available to the designer while blocking operational users.
	 *
	 * This is a boundary adapter during migration: legacy admin handlers remain in
	 * place, but ordinary school users cannot reach them by typing an admin URL.
	 */
	final class Edutech_Admin_Restriction {
		const AUDIT_ACTION = 'edutech_admin_restriction_audit';

		/** @return void */
		public static function boot() {
			add_action( 'admin_init', array( __CLASS__, 'enforce' ), 1 );
		}

		/** @return void */
		public static function enforce() {
			if ( ! self::should_restrict() ) {
				return;
			}

			$context = class_exists( 'Edutech_Identity' ) ? Edutech_Identity::current() : array();
			if ( self::is_emergency_recovery( $context ) ) {
				self::audit( 'emergency_recovery', $context );
				return;
			}

			self::audit( 'blocked', $context );
			if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
				if ( function_exists( 'wp_send_json_error' ) ) {
					wp_send_json_error( array( 'code' => 'edutech_admin_restricted', 'message' => __( 'Please use the Edutech portal for this operation.', 'school-management' ) ), 403 );
				}
				wp_die( esc_html__( 'Please use the Edutech portal for this operation.', 'school-management' ), esc_html__( 'Access restricted', 'school-management' ), array( 'response' => 403 ) );
			}

			if ( self::is_admin_post() ) {
				wp_die( esc_html__( 'Please use the Edutech portal for this operation.', 'school-management' ), esc_html__( 'Access restricted', 'school-management' ), array( 'response' => 403 ) );
			}

			$destination = class_exists( 'Edutech_Dashboard_Routes' )
				? Edutech_Dashboard_Routes::url( 'dashboard', home_url( '/' ), $context )
				: home_url( '/' );
			wp_safe_redirect( $destination );
			exit;
		}

		/**
		 * Return whether the current admin request must be blocked.
		 *
		 * @param array<string, mixed>|null $context Optional identity context.
		 * @param array<string, mixed>|null $request Optional request data for tests/adapters.
		 * @return bool
		 */
		public static function should_restrict( $context = null, $request = null ) {
			$context = is_array( $context ) ? $context : ( class_exists( 'Edutech_Identity' ) ? Edutech_Identity::current() : array() );
			$request = is_array( $request ) ? $request : self::request_context();
			if ( empty( $context['is_authenticated'] ) || ! empty( $context['is_designer'] ) || 'designer' === ( $context['role'] ?? '' ) ) {
				return false;
			}
			if ( ! empty( $request['cron'] ) || ! empty( $request['rest'] ) || ! empty( $request['webhook'] ) || ! empty( $request['safe'] ) ) {
				return false;
			}
			return true;
		}

		/** @param array<string, mixed> $context @return bool */
		public static function is_emergency_recovery( $context ) {
			$enabled = defined( 'EDUTECH_EMERGENCY_RECOVERY' ) && EDUTECH_EMERGENCY_RECOVERY;
			return (bool) apply_filters( 'edutech_allow_emergency_recovery', $enabled, $context );
		}

		/** @return array<string, bool> */
		private static function request_context() {
			$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
			$safe_actions = (array) apply_filters( 'edutech_safe_admin_actions', array( 'heartbeat', 'upload_attachment', 'query_attachments', 'get-attachment', 'health-check-site-status-result' ) );
			$safe_post_actions = (array) apply_filters( 'edutech_safe_admin_post_actions', array( 'logout', 'postpass', 'lostpassword', 'rp', 'resetpass' ) );
			$is_ajax = function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : ( defined( 'DOING_AJAX' ) && DOING_AJAX );
			$is_cron = function_exists( 'wp_doing_cron' ) ? wp_doing_cron() : ( defined( 'DOING_CRON' ) && DOING_CRON );
			$is_post = self::is_admin_post();
			return array(
				'cron'    => $is_cron,
				'rest'    => defined( 'REST_REQUEST' ) && REST_REQUEST,
				'webhook' => defined( 'EDUTECH_WEBHOOK_REQUEST' ) && EDUTECH_WEBHOOK_REQUEST,
				'safe'    => ( $is_ajax && in_array( $action, array_map( 'sanitize_key', $safe_actions ), true ) ) || ( $is_post && in_array( $action, array_map( 'sanitize_key', $safe_post_actions ), true ) ),
			);
		}

		/** @return bool */
		private static function is_admin_post() {
			$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) : '';
			return 'admin-post.php' === $script;
		}

		/** @param string $event @param array<string, mixed> $context @return void */
		private static function audit( $event, $context ) {
			do_action( self::AUDIT_ACTION, $event, array(
				'user_id' => absint( $context['user_id'] ?? 0 ),
				'role'    => sanitize_key( $context['role'] ?? 'unknown' ),
				'path'    => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
			) );
		}
	}
}
