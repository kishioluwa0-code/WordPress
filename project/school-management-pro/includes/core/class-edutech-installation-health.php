<?php

defined( 'ABSPATH' ) || die();

if ( ! class_exists( 'Edutech_Installation_Health' ) ) {
	/**
	 * Installation readiness, repair, and diagnostic service for Edutech.
	 */
	final class Edutech_Installation_Health {
		const OPTION = 'edutech_installation_health';

		/**
		 * Register repair and diagnostic hooks.
		 *
		 * @return void
		 */
		public static function boot() {
			add_action( 'admin_init', array( __CLASS__, 'repair_if_needed' ), 20 );
			add_action( 'admin_notices', array( __CLASS__, 'render_admin_notice' ), 20 );
		}

		/**
		 * Run the foundation setup after plugin activation.
		 *
		 * @return void
		 */
		public static function activate() {
			if ( ! class_exists( 'Edutech_Environment' ) || ! Edutech_Environment::is_compatible() ) {
				self::persist_report( self::report() );
				return;
			}

			if ( class_exists( 'Edutech_Migrations' ) ) {
				Edutech_Migrations::run();
			}
			if ( class_exists( 'Edutech_Modules' ) ) {
				Edutech_Modules::boot();
			}
			if ( class_exists( 'Edutech_Portal_Pages' ) ) {
				Edutech_Portal_Pages::ensure_all();
			}
			self::persist_report( self::report() );
		}

		/**
		 * Repair incomplete foundation setup for an authorized administrator.
		 *
		 * @return void
		 */
		public static function repair_if_needed() {
			if ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$report = self::report();
			if ( ! empty( $report['ok'] ) ) {
				self::persist_report( $report );
				return;
			}

			if ( class_exists( 'Edutech_Migrations' ) ) {
				Edutech_Migrations::run();
			}
			if ( class_exists( 'Edutech_Modules' ) ) {
				Edutech_Modules::boot();
			}
			if ( class_exists( 'Edutech_Portal_Pages' ) ) {
				Edutech_Portal_Pages::ensure_all();
			}
			self::persist_report( self::report() );
		}

		/**
		 * Return a complete, safe installation report.
		 *
		 * @return array<string, mixed>
		 */
		public static function report() {
			$runtime = class_exists( 'Edutech_Environment' ) ? Edutech_Environment::report() : array(
				'ok'                 => false,
				'checks'             => array(),
				'missing_extensions' => array(),
			);
			$migration = class_exists( 'Edutech_Migrations' ) ? Edutech_Migrations::health() : array(
				'current' => '0.0.0',
				'target'  => defined( 'EDUTECH_DB_VERSION' ) ? EDUTECH_DB_VERSION : '1.0.0',
				'state'   => array( 'status' => 'missing' ),
			);

			$pages = array();
			if ( class_exists( 'Edutech_Portal_Pages' ) ) {
				foreach ( Edutech_Portal_Pages::definitions() as $key => $definition ) {
					$pages[ $key ] = array(
						'slug'  => $definition['slug'],
						'exists' => (bool) Edutech_Portal_Pages::get_page_id( $key ),
					);
				}
			}

			$integrations = array(
				'elementor' => defined( 'ELEMENTOR_VERSION' ) || class_exists( 'Elementor\\Plugin' ),
				'gutenberg' => function_exists( 'register_block_type' ) || function_exists( 'use_block_editor_for_post' ),
			);

			$capability = true;
			if ( function_exists( 'get_role' ) ) {
				$administrator = get_role( 'administrator' );
				$capability    = is_object( $administrator ) && (bool) $administrator->has_cap( 'manage_options' );
			}

			$checks = array(
			'runtime'      => ! empty( $runtime['ok'] ),
			'migrations'   => version_compare( (string) $migration['current'], (string) $migration['target'], '>=' ) && 'failed' !== ( $migration['state']['status'] ?? '' ),
			'system_pages' => empty( $pages ) || ! in_array( false, wp_list_pluck( $pages, 'exists' ), true ),
			'capabilities' => $capability,
			'filesystem'   => empty( $runtime['checks']['filesystem'] ) ? false : true,
		);

		$issues = array();
		if ( empty( $checks['runtime'] ) ) {
			$issues[] = 'The PHP, WordPress, database, extension, or filesystem requirements are not ready.';
		}
		if ( empty( $checks['migrations'] ) ) {
			$issues[] = 'The Edutech database migration is incomplete or failed and must be retried.';
		}
		if ( empty( $checks['system_pages'] ) ) {
			$issues[] = 'One or more Edutech system pages are missing and can be regenerated safely.';
		}
		if ( empty( $checks['capabilities'] ) ) {
			$issues[] = 'The administrator role is missing the manage_options capability.';
		}

		return array(
			'ok'           => ! in_array( false, $checks, true ),
			'checks'       => $checks,
			'issues'       => $issues,
			'runtime'      => $runtime,
			'migrations'   => $migration,
			'pages'        => $pages,
			'integrations' => $integrations,
			'checked_at'   => function_exists( 'current_time' ) ? current_time( 'mysql', true ) : gmdate( 'Y-m-d H:i:s' ),
		);
		}

		/**
		 * Render an actionable administrator-only health notice.
		 *
		 * @return void
		 */
		public static function render_admin_notice() {
			if ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$report = self::report();
			if ( ! empty( $report['ok'] ) || empty( $report['issues'] ) ) {
				return;
			}

			echo '<div class="notice notice-warning"><p><strong>';
			echo esc_html__( 'Edutech installation needs attention.', 'school-management' );
			echo '</strong> ' . esc_html( implode( ' ', $report['issues'] ) );
			echo ' ' . esc_html__( 'Review the plugin health report or reload this page to retry safe repairs.', 'school-management' ) . '</p></div>';
		}

		/**
		 * Persist only non-sensitive readiness metadata.
		 *
		 * @param array<string, mixed> $report Health report.
		 * @return void
		 */
		private static function persist_report( $report ) {
			if ( function_exists( 'update_option' ) ) {
				update_option( self::OPTION, $report, false );
			}
		}
	}
	}

	/* phpcs:ignoreFile */
