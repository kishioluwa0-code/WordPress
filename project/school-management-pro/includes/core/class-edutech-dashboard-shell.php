<?php

defined( 'ABSPATH' ) || die();

if ( ! class_exists( 'Edutech_Dashboard_Shell' ) ) {
	final class Edutech_Dashboard_Shell {
		public static function open() {
			$identity  = class_exists( 'Edutech_Identity' ) ? Edutech_Identity::current() : array();
			$frontend  = class_exists( 'Edutech_Identity' ) ? Edutech_Identity::frontend() : array();
			$dashboard = class_exists( 'Edutech_Dashboard_Routes' ) ? Edutech_Dashboard_Routes::frontend( $identity ) : array( 'activeRoute' => '', 'items' => array() );
			$role      = $identity['role_label'] ?? __( 'User', 'school-management' );
			$name      = $identity['username'] ?? wp_get_current_user()->display_name;
			$school    = $identity['current_school']['label'] ?? __( 'Current institution', 'school-management' );
			$session   = ! empty( $identity['session_id'] ) ? sprintf( __( 'Session %d', 'school-management' ), absint( $identity['session_id'] ) ) : __( 'Current academic session', 'school-management' );
			$logout    = wp_logout_url( home_url( '/' ) );
			$icons     = array( 'dashboard' => '⌂', 'student' => '◎', 'parent' => '◉', 'teacher' => '◇', 'staff' => '▦', 'exams' => '▤', 'profile' => '○' );
			$active    = $dashboard['activeRoute'] ?? 'dashboard';
			$items     = (array) ( $dashboard['items'] ?? array() );
			?>
			<div class="edutech-control-center" data-edutech-shell="control-center" data-edutech-role="<?php echo esc_attr( $frontend['role'] ?? 'unknown' ); ?>">
				<aside class="edutech-control-center__sidebar" aria-label="<?php esc_attr_e( 'Edutech navigation', 'school-management' ); ?>">
					<div class="edutech-brand-lockup"><span class="edutech-brand-lockup__mark" aria-hidden="true">E</span><span><strong>Edutech</strong><small><?php esc_html_e( 'Education control center', 'school-management' ); ?></small></span></div>
					<div class="edutech-context-card"><span class="edutech-eyebrow"><?php esc_html_e( 'Workspace', 'school-management' ); ?></span><strong><?php echo esc_html( $school ); ?></strong><small><?php echo esc_html( $session ); ?></small></div>
					<nav class="edutech-control-center__nav"><span class="edutech-eyebrow"><?php esc_html_e( 'Workspace', 'school-management' ); ?></span><ul>
					<?php foreach ( $items as $item ) : $current = $active === $item['route']; ?>
						<li><a class="<?php echo $current ? 'is-active' : ''; ?>" href="<?php echo esc_url( $item['url'] ); ?>" <?php echo $current ? 'aria-current="page"' : ''; ?>><span class="edutech-nav-icon" aria-hidden="true"><?php echo esc_html( $icons[ $item['route'] ] ?? '•' ); ?></span><span><?php echo esc_html( $item['label'] ); ?></span></a></li>
					<?php endforeach; ?></ul></nav>
					<a class="edutech-sidebar-help" href="<?php echo esc_url( add_query_arg( 'edutech_route', 'profile', home_url( '/' ) ) ); ?>"><span class="edutech-nav-icon" aria-hidden="true">?</span><span><?php esc_html_e( 'Help and support', 'school-management' ); ?></span></a>
				</aside>
				<div class="edutech-control-center__body">
					<header class="edutech-topbar"><div class="edutech-mobile-brand"><span class="edutech-brand-lockup__mark" aria-hidden="true">E</span><strong>Edutech</strong></div><details class="edutech-mobile-menu"><summary aria-label="<?php esc_attr_e( 'Open navigation', 'school-management' ); ?>">☰</summary><div class="edutech-mobile-menu__panel">
					<?php foreach ( $items as $item ) : ?><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a><?php endforeach; ?></div></details><div class="edutech-topbar__title"><span class="edutech-eyebrow"><?php echo esc_html( $role ); ?></span><h1><?php echo esc_html( 'dashboard' === $active ? __( 'Good to see you', 'school-management' ) : ( $items[0]['label'] ?? __( 'Control center', 'school-management' ) ) ); ?></h1></div><div class="edutech-topbar__actions"><button class="edutech-icon-button" type="button" aria-label="<?php esc_attr_e( 'Notifications', 'school-management' ); ?>">⌁<span class="edutech-notification-dot"></span></button><div class="edutech-user-chip"><span class="edutech-avatar"><?php echo esc_html( strtoupper( substr( (string) $name, 0, 1 ) ) ); ?></span><span><strong><?php echo esc_html( $name ); ?></strong><small><?php echo esc_html( $role ); ?></small></span></div><a class="edutech-button edutech-button--secondary edutech-button--compact" href="<?php echo esc_url( $logout ); ?>"><?php esc_html_e( 'Sign out', 'school-management' ); ?></a></div></header>
					<main class="edutech-control-center__main">
						<section class="edutech-welcome-panel"><div><span class="edutech-eyebrow"><?php echo esc_html( $school ); ?></span><h2><?php esc_html_e( 'Your institution at a glance', 'school-management' ); ?></h2><p><?php esc_html_e( 'Use your workspace to manage today’s priorities and keep your academic operations moving.', 'school-management' ); ?></p></div><a class="edutech-button" href="<?php echo esc_url( add_query_arg( 'edutech_route', 'profile', home_url( '/' ) ) ); ?>"><?php esc_html_e( 'View profile', 'school-management' ); ?></a></section>
						<section class="edutech-kpi-grid" aria-label="<?php esc_attr_e( 'Overview', 'school-management' ); ?>"><article class="edutech-kpi-card edutech-kpi-card--blue"><span class="edutech-kpi-card__icon">◈</span><span class="edutech-eyebrow"><?php esc_html_e( 'Active learners', 'school-management' ); ?></span><strong>—</strong><small><?php esc_html_e( 'Live data will appear here', 'school-management' ); ?></small></article><article class="edutech-kpi-card edutech-kpi-card--green"><span class="edutech-kpi-card__icon">✓</span><span class="edutech-eyebrow"><?php esc_html_e( 'Attendance today', 'school-management' ); ?></span><strong>—</strong><small><?php esc_html_e( 'Awaiting attendance module', 'school-management' ); ?></small></article><article class="edutech-kpi-card edutech-kpi-card--violet"><span class="edutech-kpi-card__icon">▤</span><span class="edutech-eyebrow"><?php esc_html_e( 'Pending tasks', 'school-management' ); ?></span><strong>—</strong><small><?php esc_html_e( 'No tasks loaded yet', 'school-management' ); ?></small></article><article class="edutech-kpi-card edutech-kpi-card--orange"><span class="edutech-kpi-card__icon">₦</span><span class="edutech-eyebrow"><?php esc_html_e( 'Fee collection', 'school-management' ); ?></span><strong>—</strong><small><?php esc_html_e( 'Finance summary will appear here', 'school-management' ); ?></small></article></section>
						<section class="edutech-dashboard-grid"><article class="edutech-panel edutech-panel--wide"><div class="edutech-panel__header"><div><span class="edutech-eyebrow"><?php esc_html_e( 'Quick actions', 'school-management' ); ?></span><h2><?php esc_html_e( 'Move work forward', 'school-management' ); ?></h2></div><span class="edutech-badge edutech-badge--live"><?php esc_html_e( 'Live workspace', 'school-management' ); ?></span></div><div class="edutech-quick-actions"><a href="<?php echo esc_url( add_query_arg( 'edutech_route', 'student', home_url( '/' ) ) ); ?>"><span>＋</span><strong><?php esc_html_e( 'Manage learners', 'school-management' ); ?></strong><small><?php esc_html_e( 'View records and assignments', 'school-management' ); ?></small></a><a href="<?php echo esc_url( add_query_arg( 'edutech_route', 'exams', home_url( '/' ) ) ); ?>"><span>▤</span><strong><?php esc_html_e( 'Open examinations', 'school-management' ); ?></strong><small><?php esc_html_e( 'Results and assessment tools', 'school-management' ); ?></small></a><a href="<?php echo esc_url( add_query_arg( 'edutech_route', 'profile', home_url( '/' ) ) ); ?>"><span>⚙</span><strong><?php esc_html_e( 'Workspace settings', 'school-management' ); ?></strong><small><?php esc_html_e( 'Profile and preferences', 'school-management' ); ?></small></a></div></article><article class="edutech-panel"><div class="edutech-panel__header"><div><span class="edutech-eyebrow"><?php esc_html_e( 'Notifications', 'school-management' ); ?></span><h2><?php esc_html_e( 'Stay up to date', 'school-management' ); ?></h2></div><span class="edutech-notification-count">0</span></div><div class="edutech-empty-state"><span class="edutech-empty-state__icon">⌁</span><strong><?php esc_html_e( 'You’re all caught up', 'school-management' ); ?></strong><p><?php esc_html_e( 'New notices and tasks will appear here.', 'school-management' ); ?></p></div></article></section>
						<div class="edutech-legacy-content">
			<?php
		}

		public static function close() {
			?>
						</div></main></div></div>
			<?php
		}
	}
}
