<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_School.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Session.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Config.php';

class WLSM_Shortcode {
	public static function account( $attr ) {
		self::enqueue_assets();
		ob_start();
		$content = require WLSM_PLUGIN_DIR_PATH . 'public/inc/account/route.php';
		return Edutech_Portal::wrap( $content, $attr );
	}

	public static function fees( $attr ) {
		self::enqueue_assets();
		wp_enqueue_script( 'razorpay-checkout', '//checkout.razorpay.com/v1/checkout.js', array(), NULL, true );
		wp_enqueue_script( 'paystack-checkout', '//js.paystack.co/v1/inline.js', array(), NULL, true );
		wp_enqueue_script( 'stripe-checkout', '//checkout.stripe.com/checkout.js', array(), NULL, true );
		ob_start();
		return require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/forms/fees.php';
	}

	public static function inquiry( $attr ) {
		self::enqueue_assets();
		ob_start();
		return require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/forms/inquiry.php';
	}

	public static function registration( $attr ) {
		self::enqueue_assets();
		ob_start();
		return require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/forms/registration.php';
	}

	public static function school_register( $attr ) {
		self::enqueue_assets();
		ob_start();
		return require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/forms/school_register.php';
	}

	public static function staff_registration( $attr ) {
		self::enqueue_assets();
		ob_start();
		return require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/forms/registration_staff.php';
	}

	public static function exam_time_table( $attr ) {
		self::enqueue_assets();
		ob_start();
		return require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/forms/exam-time-table.php';
	}

	public static function exam_admit_card( $attr ) {
		self::enqueue_assets();
		ob_start();
		return require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/forms/exam-admit-card.php';
	}

	public static function exam_result( $attr ) {
		self::enqueue_assets();
		ob_start();
		return require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/forms/exam-result.php';
	}

	public static function certificate( $attr ) {
		self::enqueue_assets();
		ob_start();
		return require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/forms/certificate.php';
	}

	public static function lesson( $attr ) {
		self::enqueue_assets();
		ob_start();
		return require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/lesson/route.php';
	}

	public static function invoice_history( $attr ) {
		self::enqueue_assets();
		ob_start();
		return require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/forms/invoice_history.php';
	}

	public static function zoom_redirect( $attr ) {
		self::enqueue_assets();
		ob_start();
		return require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/forms/zoom_redirect.php';
	}

	/**
	 * Scope Edutech design tokens to pages that contain supported shortcodes.
	 *
	 * @param array<string> $classes Existing body classes.
	 * @return array<string>
	 */
	public static function add_portal_body_class( $classes ) {
		if ( is_admin() || ! is_singular() ) {
			return $classes;
		}

		$post = get_post();
		if ( ! $post ) {
			return $classes;
		}

		$shortcodes = array(
			'school_management_account',
			'school_management_fees',
			'school_management_inquiry',
			'school_management_registration',
			'school_management_staff_registration',
			'school_management_noticeboard',
			'school_management_exam_time_table',
			'school_management_exam_admit_card',
			'school_management_exam_result',
			'school_management_certificate',
			'school_management_lesson',
			'school_management_invoice_history',
			'school_management_zoom_redirect',
		);
		foreach ( $shortcodes as $shortcode ) {
			if ( has_shortcode( $post->post_content, $shortcode ) ) {
				$classes[] = 'edutech-portal';
				$identity   = class_exists( 'Edutech_Identity' ) ? Edutech_Identity::current() : array();
				if ( ! empty( $identity['role'] ) ) {
					$classes[] = 'edutech-role-' . sanitize_html_class( $identity['role'] );
				}
				if ( class_exists( 'Edutech_Dashboard_Routes' ) ) {
					$classes[] = Edutech_Dashboard_Routes::body_class( $identity );
				}
				break;
			}
		}
		return array_values( array_unique( $classes ) );
	}

	public static function noticeboard( $attr ) {
		self::enqueue_assets();
		ob_start();
		return require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/noticeboard/index.php';
	}

	public static function enqueue_assets() {
		wp_enqueue_style(
			'edutech-design-system',
			WLSM_PLUGIN_URL . 'assets/css/edutech-design-system.css',
			array(),
			defined( 'EDUTECH_VERSION' ) ? EDUTECH_VERSION : '1.0.0'
		);

		wp_enqueue_style( 'jquery-confirm', WLSM_PLUGIN_URL . 'assets/css/jquery-confirm.min.css' );
		wp_enqueue_style( 'toastr', WLSM_PLUGIN_URL . 'assets/css/toastr.min.css' );
		wp_enqueue_style( 'zebra-datepicker', WLSM_PLUGIN_URL . 'assets/css/zebra_datepicker.min.css' );
		wp_enqueue_style( 'sumoselect', WLSM_PLUGIN_URL . 'assets/js/select/sumoselect.min.css' );

		wp_enqueue_style( 'wlsm-print-preview', WLSM_PLUGIN_URL . 'assets/css/print/wlsm-preview.css', array(), '5.1', 'all' );
		wp_enqueue_style( 'wlsm', WLSM_PLUGIN_URL . 'assets/css/wlsm.css', array(), '5.1', 'all' );
		wp_enqueue_style( 'wlsm-dashboard', WLSM_PLUGIN_URL . 'assets/css/wlsm-dashboard.css', array(), '5.1', 'all' );
		wp_enqueue_style( 'font-awesome', WLSM_PLUGIN_URL.'assets/css/all.min.css', array(), '5.10.2' );

		wp_enqueue_script( 'jquery-confirm', WLSM_PLUGIN_URL . 'assets/js/jquery-confirm.min.js', array( 'jquery' ), true, true );
		wp_enqueue_script( 'toastr', WLSM_PLUGIN_URL . 'assets/js/toastr.min.js', array( 'jquery' ), true, true );
		wp_enqueue_script( 'zebra-datepicker', WLSM_PLUGIN_URL . 'assets/js/zebra_datepicker.min.js', array( 'jquery' ), true, true );
		wp_enqueue_script( 'sumoselects', WLSM_PLUGIN_URL . 'assets/js/select/jquery.sumoselect.min.js', array( 'jquery' ), true, true );

		wp_enqueue_script( 'wlsm-public', WLSM_PLUGIN_URL . 'assets/js/wlsm.js', array( 'jquery', 'jquery-form' ), '5.1', true );
		wp_localize_script( 'wlsm-public', 'wlsmdateformat', WLSM_Config::date_format() );
		wp_localize_script( 'wlsm-public', 'wlsmgenderlist', WLSM_Helper::gender_list() );
		wp_localize_script( 'wlsm-public', 'wlsmbloodgrouplist', WLSM_Helper::blood_group_list() );
		wp_localize_script( 'wlsm-public', 'edutechIdentity', Edutech_Identity::frontend() );
		wp_localize_script( 'wlsm-public', 'edutechDashboard', Edutech_Dashboard_Routes::frontend() );
		if ( class_exists( 'Edutech_Context' ) ) {
			wp_localize_script( 'wlsm-public', 'edutechContext', Edutech_Context::current() );
		}

		wp_localize_script( 'wlsm-public', 'wlsmajaxurl', admin_url( 'admin-ajax.php' ) );
		wp_localize_script( 'wlsm-public', 'wlsmadminurl', admin_url() );

		wp_localize_script( 'wlsm-public', 'get_class_sections_nonce', wp_create_nonce('get-class-sections') );

		wp_localize_script( 'wlsm-public', 'studentAdd', esc_html__('Add Student', 'school-management') );
		wp_localize_script( 'wlsm-public', 'personalDetail', esc_html__('Personal Detail', 'school-management') );
		wp_localize_script( 'wlsm-public', 'studentName', esc_html__('Student Name', 'school-management') );
		wp_localize_script( 'wlsm-public', 'Gender', esc_html__('Gender', 'school-management') );
		wp_localize_script( 'wlsm-public', 'dateOfBirth', esc_html__('Date of Birth', 'school-management') );
		wp_localize_script( 'wlsm-public', 'BloodGroup', esc_html__('Blood Group', 'school-management') );
		wp_localize_script( 'wlsm-public', 'StudentType', esc_html__('Student Type', 'school-management') );
		wp_localize_script( 'wlsm-public', 'Class', esc_html__('Class', 'school-management') );
		wp_localize_script( 'wlsm-public', 'Section', esc_html__('Section', 'school-management') );
		wp_localize_script( 'wlsm-public', 'Subjects', esc_html__('Subjects', 'school-management') );
		wp_localize_script( 'wlsm-public', 'Activity', esc_html__('Activity', 'school-management') );
		wp_localize_script( 'wlsm-public', 'StudentLoginDetail', esc_html__('Student Login Detail', 'school-management') );
		wp_localize_script( 'wlsm-public', 'Username', esc_html__('Username', 'school-management') );
		wp_localize_script( 'wlsm-public', 'LoginEmail', esc_html__('Login Email', 'school-management') );
		wp_localize_script( 'wlsm-public', 'Password', esc_html__('Password', 'school-management') );
		wp_localize_script( 'wlsm-public', 'EnterLoginEmail', esc_html__('Enter login email', 'school-management') );
		wp_localize_script( 'wlsm-public', 'EnterPassword', esc_html__('Enter Password', 'school-management') );
		wp_localize_script( 'wlsm-public', 'EnterPassword', esc_html__('Enter Password', 'school-management') );
		wp_localize_script( 'wlsm-public', 'SelectSubjects', esc_html__('Select subjects', 'school-management') );
		wp_localize_script( 'wlsm-public', 'SelectSubjects', esc_html__('Select subjects', 'school-management') );
		wp_localize_script( 'wlsm-public', 'SelectActivity', esc_html__('Select Activity', 'school-management') );

		wp_enqueue_script('razorpay-checkout', '//checkout.razorpay.com/v1/checkout.js', array(), NULL, true);
		wp_enqueue_script('paystack-checkout', '//js.paystack.co/v1/inline.js', array(), NULL, true);
		wp_enqueue_script('stripe-checkout', '//checkout.stripe.com/checkout.js', array(), NULL, true);

		$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

		if ('calendar' === $action || '' === $action) {
			wp_enqueue_script(
				'wlsm-calendar',
				WLSM_PLUGIN_URL . 'assets/js/wlsm-calendar.js',
				array('jquery', 'toastr'),
				'1.0.0',
				true
			);

			// Build month names.
			$months = array();
			for ($m = 1; $m <= 12; $m++) {
				$months[] = date_i18n('F', mktime(0, 0, 0, $m, 1));
			}

			wp_localize_script('wlsm-calendar', 'wlsmCalendar', array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce'   => wp_create_nonce('wlsm_student_calendar_nonce'),
				'action'  => 'wlsm-p-st-fetch-calendar',
				'examsUrl' => add_query_arg(array('action' => 'exams-time-table'), get_permalink()),
				'months'  => $months,
				'i18n'    => array(
					'loading'    => esc_html__('Loading...', 'school-management'),
					'noData'     => esc_html__('No events or holidays this month.', 'school-management'),
					'noDataYear' => esc_html__('No events or holidays found for this year.', 'school-management'),
					'error'      => esc_html__('Failed to load calendar data.', 'school-management'),
					'days'       => array(
						esc_html__('Sun', 'school-management'),
						esc_html__('Mon', 'school-management'),
						esc_html__('Tue', 'school-management'),
						esc_html__('Wed', 'school-management'),
						esc_html__('Thu', 'school-management'),
						esc_html__('Fri', 'school-management'),
						esc_html__('Sat', 'school-management'),
					),
				),
			));
		}
	}
}
