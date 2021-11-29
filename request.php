<?php

if ( !defined( 'ABSPATH' ) )
	exit;

function balitsa_request_var( string $method, string $key ) {
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
			exit( 'method' );
	}
}

function balitsa_request_str( string $method, string $key ): string|null {
	$var = balitsa_request_var( $method, $key );
	if ( is_null( $var ) )
		return NULL;
	if ( !is_string( $var ) )
		exit( $key );
	if ( $var === '' )
		return NULL;
	return $var;
}

function balitsa_request_int( string $method, string $key ): int|null {
	$var = balitsa_request_str( $method, $key );
	if ( is_null( $var ) )
		return NULL;
	$var = filter_var( $var, FILTER_VALIDATE_INT );
	if ( $var === FALSE )
		exit( $key );
	return $var;
}

function balitsa_request_text( string $method, string $key ): string|null {
	$var = balitsa_request_str( $method, $key );
	if ( is_null( $var ) )
		return NULL;
	$var = mb_ereg_replace( '\s+', ' ', $var );
	$var = mb_ereg_replace( '^ ', '', $var );
	$var = mb_ereg_replace( ' $', '', $var );
	if ( $var === '' )
		return NULL;
	return $var;
}

function balitsa_request_word( string $method, string $key ): string|null {
	$var = balitsa_request_str( $method, $key );
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

function balitsa_request_datetime( string $method, string $key ): string|null {
	$var = balitsa_request_str( $method, $key );
	if ( is_null( $var ) )
		return NULL;
	$var = DateTime::createFromFormat( 'Y-m-d\TH:i', $var, wp_timezone() );
	if ( $var === FALSE )
		exit( $key );
	return $var->format('Y-m-d H:i:s');
}

function balitsa_request_post( string $method, string $key ): WP_Post|null {
	$var = balitsa_request_int( $method, $key );
	if ( is_null( $var ) )
		return NULL;
	$var = get_post( $var );
	if ( is_null( $var ) )
		exit( $key );
	return $var;
}

function balitsa_request_user( string $method, string $key ): WP_User|null {
	$var = balitsa_request_int( $method, $key );
	if ( is_null( $var ) )
		return NULL;
	$var = get_user_by( 'ID', $var );
	if ( $var === FALSE )
		exit( $key );
	return $var;
}


// GET

function balitsa_get_str( string $key, bool $nullable = FALSE ): string|null {
	$var = balitsa_request_str( 'GET', $key );
	if ( !is_null( $var ) || $nullable )
		return $var;
	exit( $key );
}

function balitsa_get_int( string $key, bool $nullable = FALSE ): int|null {
	$var = balitsa_request_int( 'GET', $key );
	if ( !is_null( $var ) || $nullable )
		return $var;
	exit( $key );
}

function balitsa_get_post( string|null $key = NULL, bool $nullable = FALSE ): WP_Post|null {
	if ( is_null( $key ) )
		$key = 'post';
	$var = balitsa_request_post( 'GET', $key );
	if ( !is_null( $var ) || $nullable )
		return $var;
	exit( $key );
}

function balitsa_get_user( string|null $key = NULL, bool $nullable = FALSE ): WP_User|null {
	if ( is_null( $key ) )
		$key = 'user';
	$var = balitsa_request_user( 'GET', $key );
	if ( !is_null( $var ) || $nullable )
		return $var;
	exit( $key );
}


// POST

function balitsa_post_str( string $key, bool $nullable = FALSE ): string|null {
	$var = balitsa_request_str( 'POST', $key );
	if ( !is_null( $var ) || $nullable )
		return $var;
	exit( $key );
}

function balitsa_post_int( string $key, bool $nullable = FALSE ): int|null {
	$var = balitsa_request_int( 'POST', $key );
	if ( !is_null( $var ) || $nullable )
		return $var;
	exit( $key );
}

function balitsa_post_text( string $key, bool $nullable = FALSE ): string|null {
	$var = balitsa_request_text( 'POST', $key );
	if ( !is_null( $var ) || $nullable )
		return $var;
	exit( $key );
}

function balitsa_post_word( string $key, bool $nullable = FALSE ): string|null {
	$var = balitsa_request_word( 'POST', $key );
	if ( !is_null( $var ) || $nullable )
		return $var;
	exit( $key );
}

function balitsa_post_datetime( string $key, bool $nullable = FALSE ): string|null {
	$var = balitsa_request_datetime( 'POST', $key );
	if ( !is_null( $var ) || $nullable )
		return $var;
	exit( $key );
}

function balitsa_post_user( string|null $key = NULL, bool $nullable = FALSE ): WP_User|null {
	if ( is_null( $key ) )
		$key = 'user';
	$var = balitsa_request_user( 'POST', $key );
	if ( !is_null( $var ) || $nullable )
		return $var;
	exit( $key );
}
