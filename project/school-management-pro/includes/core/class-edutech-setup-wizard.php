<?php

defined( 'ABSPATH' ) || die();

if ( ! class_exists( 'Edutech_Setup_Wizard' ) ) {
	/**
	 * Resumable, frontend-first setup wizard for the designer.
	 *
	 * The wizard stores configuration separately from legacy school records. Later
	 * domain slices can consume this normalized configuration without renaming or
	 * mutating legacy identifiers.
	 */
	final class Edutech_Setup_Wizard {
		const OPTION = 'edutech_setup_wizard';
		const NONCE  = 'edutech_setup_wizard_save';

		/** @return array<string, array<string, mixed>> */
		public static function templates() {
			$templates = array(
				'nigerian-primary'     => array( 'label' => __( 'Nigerian primary', 'school-management' ), 'country' => 'NG', 'institution_type' => 'primary', 'education_level' => 'primary', 'calendar' => 'three_terms', 'currency' => 'NGN', 'grading' => 'percentage' ),
				'nigerian-secondary'   => array( 'label' => __( 'Nigerian secondary', 'school-management' ), 'country' => 'NG', 'institution_type' => 'secondary', 'education_level' => 'secondary', 'calendar' => 'three_terms', 'currency' => 'NGN', 'grading' => 'percentage' ),
				'nigerian-tertiary'    => array( 'label' => __( 'Nigerian tertiary', 'school-management' ), 'country' => 'NG', 'institution_type' => 'tertiary', 'education_level' => 'tertiary', 'calendar' => 'two_semesters', 'currency' => 'NGN', 'grading' => 'gpa_5' ),
				'international-primary' => array( 'label' => __( 'International primary', 'school-management' ), 'country' => '', 'institution_type' => 'primary', 'education_level' => 'primary', 'calendar' => 'two_terms', 'currency' => 'USD', 'grading' => 'percentage' ),
				'international-secondary' => array( 'label' => __( 'International secondary', 'school-management' ), 'country' => '', 'institution_type' => 'secondary', 'education_level' => 'secondary', 'calendar' => 'two_terms', 'currency' => 'USD', 'grading' => 'letter' ),
				'university-college'    => array( 'label' => __( 'University or college', 'school-management' ), 'country' => '', 'institution_type' => 'tertiary', 'education_level' => 'tertiary', 'calendar' => 'two_semesters', 'currency' => 'USD', 'grading' => 'gpa_4' ),
				'vocational'           => array( 'label' => __( 'Vocational institution', 'school-management' ), 'country' => '', 'institution_type' => 'vocational', 'education_level' => 'vocational', 'calendar' => 'two_terms', 'currency' => 'USD', 'grading' => 'competency' ),
			);
			return apply_filters( 'edutech_setup_templates', $templates );
		}

		/** @return array<int, array<string, string>> */
		public static function steps() {
			return array(
				'foundation' => array( 'label' => __( 'Foundation', 'school-management' ), 'description' => __( 'Country, institution type, education level, calendar, and currency.', 'school-management' ) ),
				'identity'   => array( 'label' => __( 'School identity', 'school-management' ), 'description' => __( 'School name, contact details, and branding.', 'school-management' ) ),
				'academics'  => array( 'label' => __( 'Academics', 'school-management' ), 'description' => __( 'Grading scheme, classes, subjects, and session defaults.', 'school-management' ) ),
				'operations' => array( 'label' => __( 'Operations', 'school-management' ), 'description' => __( 'Modules, users, and integrations to enable.', 'school-management' ) ),
				'launch'     => array( 'label' => __( 'Launch checks', 'school-management' ), 'description' => __( 'Resolve requirements before opening the portal.', 'school-management' ) ),
			);
		}

		/** @return array<string, mixed> */
		public static function defaults() {
			return array(
				'status' => 'in_progress', 'step' => 'foundation', 'template' => '',
				'country' => '', 'institution_type' => '', 'education_level' => '', 'calendar' => '', 'currency' => '',
				'school_name' => '', 'school_email' => '', 'school_phone' => '', 'logo_url' => '',
				'grading' => '', 'modules' => array( 'frontend_modules' ), 'users' => array(), 'classes' => array(), 'subjects' => array(), 'integrations' => array(),
			);
		}

		/** @return array<string, mixed> */
		public static function state() {
			$stored = get_option( self::OPTION, array() );
			return wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
		}

		/** @return bool */
		public static function can_manage() {
			$context = class_exists( 'Edutech_Identity' ) ? Edutech_Identity::current() : array();
			return is_user_logged_in() && ( ! empty( $context['is_designer'] ) || 'designer' === ( $context['role'] ?? '' ) );
		}

		/** @param string $template @return array<string, mixed> */
		public static function apply_template( $template ) {
			$template = sanitize_key( $template );
			$templates = self::templates();
			if ( empty( $templates[ $template ] ) ) {
				return self::state();
			}
			$state = array_merge( self::state(), $templates[ $template ], array( 'template' => $template, 'step' => 'foundation', 'status' => 'in_progress' ) );
			update_option( self::OPTION, $state, false );
			return $state;
		}

		/** @param array<string, mixed> $input @return array<string, mixed> */
		public static function save( $input ) {
			$state = self::state();
			$allowed = array( 'step', 'template', 'country', 'institution_type', 'education_level', 'calendar', 'currency', 'school_name', 'school_email', 'school_phone', 'logo_url', 'grading', 'modules', 'users', 'classes', 'subjects', 'integrations' );
			foreach ( $allowed as $key ) {
				if ( ! array_key_exists( $key, $input ) ) {
					continue;
				}
				$value = $input[ $key ];
				$state[ $key ] = is_array( $value ) ? array_map( 'sanitize_text_field', wp_unslash( $value ) ) : sanitize_text_field( wp_unslash( $value ) );
			}
			$state['step'] = array_key_exists( $state['step'], self::steps() ) ? $state['step'] : 'foundation';
			$state['status'] = 'in_progress';
			update_option( self::OPTION, $state, false );
			return $state;
		}

		/** @return array<int, array<string, string>> */
		public static function tasks() {
			$state = self::state();
			$tasks = array();
			$required = array( 'country' => __( 'Select a country.', 'school-management' ), 'institution_type' => __( 'Select an institution type.', 'school-management' ), 'education_level' => __( 'Select an education level.', 'school-management' ), 'calendar' => __( 'Choose an academic calendar.', 'school-management' ), 'currency' => __( 'Choose a currency.', 'school-management' ), 'school_name' => __( 'Add the school identity name.', 'school-management' ), 'grading' => __( 'Choose a default grading scheme.', 'school-management' ) );
			foreach ( $required as $key => $message ) {
				if ( empty( $state[ $key ] ) ) {
					$tasks[] = array( 'key' => $key, 'message' => $message, 'step' => in_array( $key, array( 'school_name' ), true ) ? 'identity' : ( in_array( $key, array( 'grading' ), true ) ? 'academics' : 'foundation' ) );
				}
			}
			if ( ! class_exists( 'Edutech_Installation_Health' ) || empty( Edutech_Installation_Health::report()['healthy'] ) ) {
				$tasks[] = array( 'key' => 'installation_health', 'message' => __( 'Resolve installation health issues before launch.', 'school-management' ), 'step' => 'launch' );
			}
			return apply_filters( 'edutech_setup_tasks', $tasks, $state );
		}

		/** @return array<string, mixed> */
		public static function launch_report() {
			$tasks = self::tasks();
			$state = self::state();
			$state['status'] = empty( $tasks ) ? 'ready' : 'in_progress';
			update_option( self::OPTION, $state, false );
			return array( 'ready' => empty( $tasks ), 'tasks' => $tasks, 'state' => $state );
		}

		/** @return string */
		public static function render() {
			if ( ! self::can_manage() ) {
				return '<p class="edutech-setup-wizard__notice">' . esc_html__( 'The setup wizard is available to the designer only.', 'school-management' ) . '</p>';
			}
			$state = self::state();
			$tasks = self::tasks();
			ob_start();
			?>
			<section class="edutech-setup-wizard" aria-labelledby="edutech-setup-title">
				<h2 id="edutech-setup-title"><?php esc_html_e( 'Edutech setup wizard', 'school-management' ); ?></h2>
				<p><?php esc_html_e( 'Resume setup at any time. Your configuration is saved after each step.', 'school-management' ); ?></p>
				<ol class="edutech-setup-wizard__steps"><?php foreach ( self::steps() as $key => $step ) : ?><li class="<?php echo esc_attr( $key === $state['step'] ? 'is-current' : '' ); ?>"><strong><?php echo esc_html( $step['label'] ); ?></strong><span><?php echo esc_html( $step['description'] ); ?></span></li><?php endforeach; ?></ol>
				<form method="post"><input type="hidden" name="edutech_setup_action" value="save"><input type="hidden" name="edutech_setup_step" value="<?php echo esc_attr( $state['step'] ); ?>"><?php wp_nonce_field( self::NONCE ); ?>
					<p><label><?php esc_html_e( 'Institution template', 'school-management' ); ?><select name="template"><option value=""><?php esc_html_e( 'Choose a template', 'school-management' ); ?></option><?php foreach ( self::templates() as $key => $template ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $state['template'], $key ); ?>><?php echo esc_html( $template['label'] ); ?></option><?php endforeach; ?></select></label></p>
					<p><label><?php esc_html_e( 'School name', 'school-management' ); ?><input type="text" name="school_name" value="<?php echo esc_attr( $state['school_name'] ); ?>" required></label></p>
					<p><label><?php esc_html_e( 'School email', 'school-management' ); ?><input type="email" name="school_email" value="<?php echo esc_attr( $state['school_email'] ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Logo URL', 'school-management' ); ?><input type="url" name="logo_url" value="<?php echo esc_attr( $state['logo_url'] ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Default grading scheme', 'school-management' ); ?><select name="grading"><option value=""><?php esc_html_e( 'Choose grading', 'school-management' ); ?></option><?php foreach ( array( 'percentage', 'letter', 'gpa_4', 'gpa_5', 'competency' ) as $grading ) : ?><option value="<?php echo esc_attr( $grading ); ?>" <?php selected( $state['grading'], $grading ); ?>><?php echo esc_html( ucfirst( str_replace( '_', ' ', $grading ) ) ); ?></option><?php endforeach; ?></select></label></p>
					<button type="submit"><?php esc_html_e( 'Save and continue', 'school-management' ); ?></button>
				</form>
				<h3><?php esc_html_e( 'Launch tasks', 'school-management' ); ?></h3><?php if ( empty( $tasks ) ) : ?><p><?php esc_html_e( 'All launch checks are ready.', 'school-management' ); ?></p><?php else : ?><ul><?php foreach ( $tasks as $task ) : ?><li><?php echo esc_html( $task['message'] ); ?> <small>(<?php echo esc_html( $task['step'] ); ?>)</small></li><?php endforeach; ?></ul><?php endif; ?>
			</section>
			<?php
			return (string) ob_get_clean();
		}

		/** @return void */
		public static function handle_post() {
			if ( empty( $_POST['edutech_setup_action'] ) || 'save' !== $_POST['edutech_setup_action'] || ! self::can_manage() ) {
				return;
			}
			check_admin_referer( self::NONCE );
			$input = wp_unslash( $_POST );
			if ( ! empty( $input['template'] ) ) {
				self::apply_template( $input['template'] );
			}
			self::save( $input );
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : home_url( '/' ) );
			exit;
		}
	}
}
