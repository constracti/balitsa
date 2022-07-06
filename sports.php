<?php

if ( !defined( 'ABSPATH' ) )
	exit;

/*

sports : sport[int]

sport : array
	'key' : string
	'name' : string
	'icon' : string
	'stats' : stat[string]

stat : array
	'key' : string
	'name' : string
	'icon' : string

*/

final class Balitsa_Sports {

	private static $sports = NULL;

	private static function load(): void {
		self::$sports = get_option( 'balitsa_sports', [] );
	}

	private static function save(): void {
		if ( !empty( self::$sports ) )
			update_option( 'balitsa_sports', self::$sports );
		else
			delete_option( 'balitsa_sports' );
	}

	// functions

	public static function select( string|null $sport = NULL ): array|null {
		if ( is_null( self::$sports ) )
			self::load();
		if ( is_null( $sport ) )
			return self::$sports;
		if ( array_key_exists( $sport, self::$sports ) )
			return self::$sports[$sport];
		return NULL;
	}

	public static function exists( string $sport ): bool {
		return array_key_exists( $sport, self::select() );
	}

	// settings

	public static function settings_echo(): void {
		echo self::settings();
	}

	public static function settings(): string {
		$html = '<div class="balitsa-home balitsa-root balitsa-flex-col">' . "\n";
		$html .= '<div class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
		$html .= self::settings_refresh_link();
		$html .= '<span class="balitsa-spinner spinner balitsa-leaf" data-balitsa-spinner-toggle="is-active"></span>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<hr class="balitsa-leaf" />' . "\n";
		$html .= '<div class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
		$html .= sprintf( '<h2 class="title balitsa-leaf">%s</h2>', esc_html__( 'Sports', 'balitsa' ) ) . "\n";
		$html .= self::settings_insert_link();
		$html .= '</div>' . "\n";
		$html .= self::settings_table();
		$html .= self::settings_sport_form();
		$html .= self::settings_stat_form();
		$html .= '</div>' . "\n";
		return $html;
	}

	private static function settings_table(): string {
		$html = '<div class="balitsa-leaf">' . "\n";
		$html .= '<table class="fixed widefat striped">' . "\n";
		$html .= '<thead>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= sprintf( '<th class="column-primary has-row-actions">%s</th>', esc_html__( 'Name', 'balitsa' ) ) . "\n";
		$html .= sprintf( '<th>%s</th>', esc_html__( 'Key', 'balitsa' ) ) . "\n";
		$html .= sprintf( '<th>%s</th>', esc_html__( 'Statistics', 'balitsa' ) ) . "\n";
		$html .= '</tr>' . "\n";
		$html .= '</thead>' . "\n";
		$html .= '<tbody>' . "\n";
		foreach ( self::select() as $sport )
			$html .= self::settings_table_body_row( $sport );
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private static function settings_table_body_row( array $sport ): string {
		$actions = [];
		$actions[] = self::settings_update_link( $sport );
		$actions[] = self::settings_delete_link( $sport );
		$actions[] = self::settings_stat_insert_link( $sport );
		$html = '<tr>' . "\n";
		$html .= '<td class="column-primary has-row-actions">' . "\n";
		$html .= '<strong>' . "\n";
		$html .= sprintf( '<span class="%s"></span>', esc_attr( $sport['icon'] ) ) . "\n";
		$html .= sprintf( '<span>%s</span>', esc_html( $sport['name'] ) ) . "\n";
		$html .= '</strong>' . "\n";
		$html .= '<div class="row-actions">' . implode( ' | ', $actions ) . '</div>' . "\n";
		$html .= '</td>' . "\n";
		$html .= sprintf( '<td>%s</td>', esc_html( $sport['key'] ) ) . "\n";
		$html .= '<td>' . "\n";
		foreach ( $sport['stats'] as $stat ) {
			$html .= '<div class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
			$html .= '<div>' . "\n";
			$html .= sprintf( '<span class="%s"></span>', esc_attr( $stat['icon'] ) ) . "\n";
			$html .= sprintf( '<span>%s</span>', esc_html( $stat['name'] ) ) . "\n";
			$html .= '</div>' . "\n";
			$html .= self::settings_stat_delete_link( $sport, $stat );
			$html .= '</div>' . "\n";
		}
		$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";
		return $html;
	}

	private static function settings_sport_form(): string {
		$html = '<div class="balitsa-form balitsa-form-sport balitsa-leaf balitsa-root balitsa-root-border balitsa-flex-col" style="display: none;">' . "\n";
		$html .= sprintf( '<h3 class="balitsa-leaf">%s</h3>', esc_html__( 'Sport', 'balitsa' ) ) . "\n";
		$html .= '<div class="balitsa-leaf">' . "\n";
		$html .= '<table class="form-table" role="presentation">' . "\n";
		$html .= '<tbody>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= sprintf( '<th scope="row"><label for="balitsa-form-sport-key">%s</label></th>', esc_html__( 'Key', 'balitsa' ) ) . "\n";
		$html .= '<td><input type="text" class="balitsa-field regular-text" data-balitsa-name="key" id="balitsa-form-sport-key" /></td>' . "\n";
		$html .= '</tr>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= sprintf( '<th scope="row"><label for="balitsa-form-sport-name">%s</label></th>', esc_html__( 'Name', 'balitsa' ) ) . "\n";
		$html .= '<td><input type="text" class="balitsa-field regular-text" data-balitsa-name="name" id="balitsa-form-sport-name" /></td>' . "\n";
		$html .= '</tr>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= sprintf( '<th scope="row"><label for="balitsa-form-sport-icon">%s</label></th>', esc_html__( 'Icon', 'balitsa' ) ) . "\n";
		$html .= '<td><input type="text" class="balitsa-field regular-text" data-balitsa-name="icon" id="balitsa-form-sport-icon" /></td>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<div class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
		$html .= sprintf( '<a href="" class="balitsa-link balitsa-submit button button-primary balitsa-leaf">%s</a>', esc_html__( 'Submit', 'balitsa' ) ) . "\n";
		$html .= sprintf( '<a href="" class="balitsa-cancel button balitsa-leaf">%s</a>', esc_html__( 'Cancel', 'balitsa' ) ) . "\n";
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private static function settings_stat_form(): string {
		$html = '<div class="balitsa-form balitsa-form-stat balitsa-leaf balitsa-root balitsa-root-border balitsa-flex-col" style="display: none;">' . "\n";
		$html .= sprintf( '<h3 class="balitsa-leaf">%s</h3>', esc_html__( 'Statistic', 'balitsa' ) ) . "\n";
		$html .= '<div class="balitsa-leaf">' . "\n";
		$html .= '<table class="form-table" role="presentation">' . "\n";
		$html .= '<tbody>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= sprintf( '<th scope="row"><label for="balitsa-form-stat-key">%s</label></th>', esc_html__( 'Key', 'balitsa' ) ) . "\n";
		$html .= '<td><input type="text" class="balitsa-field regular-text" data-balitsa-name="key" id="balitsa-form-stat-key" /></td>' . "\n";
		$html .= '</tr>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= sprintf( '<th scope="row"><label for="balitsa-form-stat-name">%s</label></th>', esc_html__( 'Name', 'balitsa' ) ) . "\n";
		$html .= '<td><input type="text" class="balitsa-field regular-text" data-balitsa-name="name" id="balitsa-form-stat-name" /></td>' . "\n";
		$html .= '</tr>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= sprintf( '<th scope="row"><label for="balitsa-form-stat-icon">%s</label></th>', esc_html__( 'Icon', 'balitsa' ) ) . "\n";
		$html .= '<td><input type="text" class="balitsa-field regular-text" data-balitsa-name="icon" id="balitsa-form-stat-icon" /></td>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<div class="balitsa-flex-row balitsa-flex-justify-between balitsa-flex-align-center">' . "\n";
		$html .= sprintf( '<a href="" class="balitsa-link balitsa-submit button button-primary balitsa-leaf">%s</a>', esc_html__( 'Submit', 'balitsa' ) ) . "\n";
		$html .= sprintf( '<a href="" class="balitsa-cancel button balitsa-leaf">%s</a>', esc_html__( 'Cancel', 'balitsa' ) ) . "\n";
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private static function settings_refresh_link(): string {
		return sprintf( '<a%s>%s</a>', Balitsa::atts( [
			'href' => self::ajax_href( 'settings_refresh' ),
			'class' => 'balitsa-link button balitsa-leaf',
		] ), esc_html__( 'Refresh', 'balitsa' ) ) . "\n";
	}

	private static function settings_insert_link(): string {
		return sprintf( '<a%s>%s</a>', Balitsa::atts( [
			'href' => self::ajax_href( 'settings_insert' ),
			'class' => 'balitsa-insert button balitsa-leaf',
			'data-balitsa-form' => '.balitsa-form-sport',
		] ), esc_html__( 'Insert', 'balitsa' ) ) . "\n";
	}

	private static function settings_update_link( array $sport ): string {
		return sprintf( '<span><a%s>%s</a></span>', Balitsa::atts( [
			'href' => self::ajax_href( 'settings_update', [
				'sport' => $sport['key'],
			] ),
			'class' => 'balitsa-insert',
			'data-balitsa-form' => '.balitsa-form-sport',
			'data-balitsa-field-key' => esc_attr( $sport['key'] ),
			'data-balitsa-field-name' => esc_attr( $sport['name'] ),
			'data-balitsa-field-icon' => esc_attr( $sport['icon'] ),
		] ), esc_html__( 'Update', 'balitsa' ) ) . "\n";
	}

	private static function settings_delete_link( array $sport ): string {
		return sprintf( '<span class="delete"><a%s>%s</a></span>', Balitsa::atts( [
			'href' => self::ajax_href( 'settings_delete', [
				'sport' => $sport['key'],
			] ),
			'class' => 'balitsa-link',
			'data-balitsa-confirm' => esc_attr( sprintf( __( 'Delete sport %s?', 'balitsa' ), $sport['name'] ) ),
		] ), esc_html__( 'Delete', 'balitsa' ) ) . "\n";
	}

	private static function settings_stat_insert_link( array $sport ): string {
		return sprintf( '<span><a%s>%s</a></span>', Balitsa::atts( [
			'href' => self::ajax_href( 'settings_stat_insert', [
				'sport' => $sport['key'],
			] ),
			'class' => 'balitsa-insert',
			'data-balitsa-form' => '.balitsa-form-stat',
		] ), esc_html__( 'Insert Statistic', 'balitsa' ) ) . "\n";
	}

	private static function settings_stat_delete_link( array $sport, array $stat ): string {
		return sprintf( '<a%s><span class="fas fa-fw fa-trash"></span></a>', Balitsa::atts( [
				'href' => self::ajax_href( 'settings_stat_delete', [
					'sport' => $sport['key'],
					'stat' => $stat['key'],
				] ),
				'class' => 'balitsa-link',
				'title' => esc_attr__( 'Delete', 'balitsa' ),
				'data-balitsa-confirm' => esc_attr( sprintf( __( 'Delete statistic %s?', 'balitsa' ), $stat['name'] ) ),
			] ) ) . "\n";
	}

	// ajax

	private static function ajax_href( string $task, array $args = [] ): string {
		return add_query_arg( array_merge( [
			'action' => 'balitsa_sports',
			'task' => $task,
			'nonce' => self::nonce_create( $task ),
		], $args ), admin_url( 'admin-ajax.php' ) );
	}

	private static function nonce_create( string $task ): string {
		return Balitsa::nonce_create( 'balitsa_sports', $task );
	}

	private static function nonce_verify( string $task ): void {
		Balitsa::nonce_verify( 'balitsa_sports', $task );
	}

	public static function ajax( string $task ): void {
		self::nonce_verify( $task );
		switch ( $task ) {
			case 'settings_refresh':
				Balitsa::success( self::settings() );
			case 'settings_insert':
				self::select();
				$sport = [
					'key' => Balitsa_Request::post( 'word', 'key' ),
					'name' => Balitsa_Request::post( 'text', 'name' ),
					'icon' => Balitsa_Request::post( 'text', 'icon' ),
					'stats' => [],
				];
				if ( array_key_exists( $sport['key'], self::$sports ) )
					exit( 'key' );
				self::$sports[$sport['key']] = $sport;
				self::save();
				Balitsa::success( self::settings() );
			case 'settings_update':
				self::select();
				$sport_key = Balitsa_Request::get( 'str', 'sport' );
				if ( !array_key_exists( $sport_key, self::$sports ) )
					exit( 'sport' );
				$sport = &self::$sports[$sport_key];
				$sport['key'] = Balitsa_Request::post( 'word', 'key' );
				$sport['name'] = Balitsa_Request::post( 'text', 'name' );
				$sport['icon'] = Balitsa_Request::post( 'text', 'icon' );
				if ( $sport['key'] === $sport_key ) {
				} elseif ( array_key_exists( $sport['key'], self::$sports ) ) {
					exit( 'key' );
				} else {
					unset( self::$sports[$sport_key] );
					self::$sports[$sport['key']] = $sport;
				}
				self::save();
				Balitsa::success( self::settings() );
			case 'settings_delete':
				self::select();
				$sport_key = Balitsa_Request::get( 'str', 'sport' );
				if ( !array_key_exists( $sport_key, self::$sports ) )
					exit( 'sport' );
				unset( self::$sports[$sport_key] );
				self::save();
				Balitsa::success( self::settings() );
			case 'settings_stat_insert':
				self::select();
				$sport_key = Balitsa_Request::get( 'str', 'sport' );
				if ( !array_key_exists( $sport_key, self::$sports ) )
					exit( 'sport' );
				$sport = &self::$sports[$sport_key];
				$stat = [
					'key' => Balitsa_Request::post( 'text', 'key' ),
					'name' => Balitsa_Request::post( 'text', 'name' ),
					'icon' => Balitsa_Request::post( 'text', 'icon' ),
				];
				if ( array_key_exists( $stat['key'], $sport['stats'] ) )
					exit( 'key' );
				$sport['stats'][$stat['key']] = $stat;
				self::save();
				Balitsa::success( self::settings() );
			case 'settings_stat_delete':
				self::select();
				$sport_key = Balitsa_Request::get( 'str', 'sport' );
				if ( !array_key_exists( $sport_key, self::$sports ) )
					exit( 'sport' );
				$sport = &self::$sports[$sport_key];
				$stat_key = Balitsa_Request::get( 'str', 'stat' );
				if ( !array_key_exists( $stat_key, $sport['stats'] ) )
					exit( 'stat' );
				unset( $sport['stats'][$stat_key] );
				self::save();
				Balitsa::success( self::settings() );
			default:
				exit( 'task' );
		}
	}
}

add_filter( 'balitsa_tab_list', function( array $tabs ): array {
	$tabs['settings'] = esc_html__( 'Settings', 'balitsa' );
	return $tabs;
} );

add_action( 'balitsa_tab_html_settings', [ 'Balitsa_Sports', 'settings_echo' ] );

add_action( 'wp_ajax_' . 'balitsa_sports', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	$task = Balitsa_Request::get( 'str', 'task' );
	Balitsa_Sports::ajax( $task );
} );
