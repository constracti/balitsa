<?php

/*
 * Plugin Name: Balitsa
 * Plugin URI: https://github.com/constracti/balitsa
 * Description: Customization plugin of Balitsa website.
 * Author: constracti
 * Version: 0.2
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: balitsa
 */

if ( !defined( 'ABSPATH' ) )
	exit;

// define plugin constants
define( 'BALITSA_DIR', plugin_dir_path( __FILE__ ) );
define( 'BALITSA_URL', plugin_dir_url( __FILE__ ) );

// require php files
$files = glob( BALITSA_DIR . '*.php' );
foreach ( $files as $file ) {
	if ( $file !== __FILE__ )
		require_once( $file );
}

// return plugin version
function balitsa_version(): string {
	return strval( time() ); // TODO delete line
	$plugin_data = get_plugin_data( __FILE__ );
	return $plugin_data['Version'];
}

// load plugin translations
add_action( 'init', function(): void {
	load_plugin_textdomain( 'balitsa', FALSE, basename( __DIR__ ) . '/languages' );
} );

function balitsa_success( string $html ): void {
	header( 'content-type: application/json' );
	exit( json_encode( [
		'html' => $html,
	] ) );
}

function balitsa_attrs( array $attrs ): string {
	$return = '';
	foreach ( $attrs as $prop => $val ) {
		$return .= sprintf( ' %s="%s"', $prop, $val );
	}
	return $return;
}

function balitsa_sorter( string ...$keys ): callable {
	return function( array $a1, array $a2 ): int {
		foreach ( $keys as $key ) {
			$cmp = $a1[$key] <=> $a2[$key];
			if ( $cmp )
				return $cmp;
		}
		return 0;
	};
}


// settings page

add_action( 'admin_menu', function(): void {
	$page_title = esc_html__( 'Balitsa', 'balitsa' );
	$menu_title = esc_html__( 'Balitsa', 'balitsa' );
	$capability = 'manage_options';
	$menu_slug = 'balitsa';
	add_options_page( $page_title, $menu_title, $capability, $menu_slug, function() {
		$tab_curr = balitsa_get_str( 'tab', TRUE ) ?? 'settings';
?>
<div class="wrap">
	<h1><?= esc_html__( 'Balitsa', 'balitsa' ) ?></h1>
	<h2 class="nav-tab-wrapper">
<?php
		foreach ( apply_filters( 'balitsa_tab_list', [] ) as $tab_slug => $tab_name ) {
			$class = [];
			$class[] = 'nav-tab';
			if ( $tab_slug === $tab_curr )
				$class[] = 'nav-tab-active';
				$class = implode( ' ', $class );
				$href = menu_page_url( 'balitsa', FALSE );
				if ( $tab_slug !== 'settings' )
					$href = add_query_arg( 'tab', $tab_slug, $href );
?>
		<a class="<?= $class ?>" href="<?= $href ?>"><?= esc_html( $tab_name ) ?></a>
<?php
		}
?>
	</h2>
<?php
	do_action( 'balitsa_tab_html_' . $tab_curr );
?>
</div>
<?php
	} );
} );

add_filter( 'plugin_action_links', function( array $actions, string $plugin_file ): array {
	if ( $plugin_file !== basename( __DIR__ ) . '/' . basename( __FILE__ ) )
		return $actions;
	$actions['settings'] = sprintf( '<a href="%s">%s</a>', menu_page_url( 'balitsa', FALSE ), esc_html__( 'Settings', 'balitsa' ) );
	return $actions;
}, 10, 2 );

add_action( 'admin_enqueue_scripts', function( string $hook_suffix ): void {
	if ( $hook_suffix !== 'settings_page_balitsa' )
		return;
	wp_enqueue_style( 'flex', BALITSA_URL . 'flex.css', [], balitsa_version() );
	wp_enqueue_script( 'balitsa_script', BALITSA_URL . 'script.js', [ 'jquery', ], balitsa_version() );
} );


// nonce

function balitsa_nonce_action( string $action, string ...$args ): string {
	foreach ( $args as $arg )
		$action .= '_' . $arg;
	return $action;
}

function balitsa_nonce_create( string $action, string ...$args ): string {
	return wp_create_nonce( balitsa_nonce_action( $action, ...$args ) );
}

function balitsa_nonce_verify( string $action, string ...$args ): void {
	$nonce = balitsa_get_str( 'nonce' );
	if ( !wp_verify_nonce( $nonce, balitsa_nonce_action( $action, ...$args ) ) )
		exit( 'nonce' );
}


// sports

function balitsa_get_sports(): array {
	return get_option( 'balitsa_sports', [] );
}

function balitsa_set_sports( array $sports ): void {
	if ( !empty( $sports ) )
		update_option( 'balitsa_sports', $sports );
	else
		delete_option( 'balitsa_sports' );
}


// ranks

function balitsa_get_user_ranks( WP_User|int $user ): array {
	if ( is_a( $user, 'WP_User' ) )
		$user = $user->ID;
	$ranks = get_user_meta( $user, 'balitsa_ranks', TRUE );
	if ( $ranks === '' )
		return [];
	return $ranks;
}

function balitsa_set_user_ranks( WP_User|int $user, array $ranks ): void {
	if ( is_a( $user, 'WP_User' ) )
		$user = $user->ID;
	if ( !empty( $ranks ) )
		update_user_meta( $user, 'balitsa_ranks', $ranks );
	else
		delete_user_meta( $user, 'balitsa_ranks' );
}


// access

function balitsa_get_access( WP_Post|int $post ): array {
	if ( is_a( $post, 'WP_Post' ) )
		$post = $post->ID;
	$s = get_post_meta( $post, 'balitsa_access', TRUE );
	if ( preg_match_all( '#(\d+)#', $s, $m ) === FALSE )
		return [];
	return array_map( 'intval', $m[0] );
}

function balitsa_set_access( WP_Post|int $post, array $a ): void {
	if ( is_a( $post, 'WP_Post' ) )
		$post = $post->ID;
	$s = '';
	foreach ( $a as $i )
		$s .= sprintf( '#%d#', $i );
	if ( $s !== '' )
		update_post_meta( $post, 'balitsa_access', $s );
	else
		delete_post_meta( $post, 'balitsa_access' );
}
