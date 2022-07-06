<?php

if ( !defined( 'ABSPATH' ) )
	exit;

final class Balitsa_Request {

	private static function request( string $method, string $type, string|null $key = NULL, bool $nullable = FALSE ) {
		if ( !in_array( $method, [ 'GET', 'POST' ], TRUE ) )
			exit( sprintf( 'request: invalid method %s', $method ) );
		$class = get_class();
		$classfn = 'request_' . $type;
		if ( !method_exists( $class, $classfn ) )
			exit( sprintf( 'request %s: invalid type %s', $method, $type ) );
		if ( is_null( $key ) )
			$key = $type;
		$var = call_user_func( [ $class, $classfn ], $method, $key );
		if ( !is_null( $var ) || $nullable )
			return $var;
		exit( $key );
	}

	public static function get( string $type, string|null $key = NULL, bool $nullable = FALSE ) {
		return self::request( 'GET', $type, $key, $nullable );
	}

	public static function post( string $type, string|null $key = NULL, bool $nullable = FALSE ) {
		return self::request( 'POST', $type, $key, $nullable );
	}

	private static function request_var( string $method, string $key ) {
		switch ( $method ) {
			case 'GET':
				if ( !array_key_exists( $key, $_GET ) )
					return NULL;
				return $_GET[$key];
			case 'POST':
				if ( !array_key_exists( $key, $_POST ) )
					return NULL;
				return $_POST[$key];
			default:
				exit( sprintf( 'request: invalid method %s', $method ) );
		}
	}

	private static function request_str( string $method, string $key ): string|null {
		$var = self::request_var( $method, $key );
		if ( is_null( $var ) )
			return NULL;
		if ( !is_string( $var ) )
			exit( $key );
		if ( $var === '' )
			return NULL;
		return $var;
	}

	private static function request_onoff( string $method, string $key ): bool|null {
		$var = self::request_str( $method, $key );
		if ( is_null( $var ) )
			return NULL;
		switch ( $var ) {
			case 'on':
				return TRUE;
			case 'off':
				return FALSE;
			default:
				exit( $key );
		}
	}

	private static function request_int( string $method, string $key ): int|null {
		$var = self::request_str( $method, $key );
		if ( is_null( $var ) )
			return NULL;
		$var = filter_var( $var, FILTER_VALIDATE_INT );
		if ( $var === FALSE )
			exit( $key );
		return $var;
	}

	private static function request_text( string $method, string $key ): string|null {
		$var = self::request_str( $method, $key );
		if ( is_null( $var ) )
			return NULL;
		$var = mb_ereg_replace( '\s+', ' ', $var );
		$var = mb_ereg_replace( '^ ', '', $var );
		$var = mb_ereg_replace( ' $', '', $var );
		if ( $var === '' )
			return NULL;
		return $var;
	}

	private static function request_word( string $method, string $key ): string|null {
		$var = self::request_str( $method, $key );
		if ( is_null( $var ) )
			return NULL;
		$var = filter_var( $var, FILTER_VALIDATE_REGEXP, [
			'options' => [
				'regexp' => '/^\w+$/',
			],
		] );
		if ( $var === FALSE )
			exit( $key );
		return $var;
	}

	private static function request_datetime( string $method, string $key ): string|null {
		$var = self::request_str( $method, $key );
		if ( is_null( $var ) )
			return NULL;
		$var = DateTime::createFromFormat( 'Y-m-d\TH:i', $var, wp_timezone() );
		if ( $var === FALSE )
			exit( $key );
		return $var->format('Y-m-d H:i:s');
	}

	private static function request_post( string $method, string $key ): WP_Post|null {
		$var = self::request_int( $method, $key );
		if ( is_null( $var ) )
			return NULL;
		$var = get_post( $var );
		if ( is_null( $var ) )
			exit( $key );
		return $var;
	}

	private static function request_user( string $method, string $key ): WP_User|null {
		$var = self::request_int( $method, $key );
		if ( is_null( $var ) )
			return NULL;
		$var = get_user_by( 'ID', $var );
		if ( $var === FALSE )
			exit( $key );
		return $var;
	}
}
