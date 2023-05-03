<?php

/*
 * Plugin Name: Balitsa
 * Plugin URI: https://github.com/constracti/balitsa
 * Description: Customization plugin of Balitsa website.
 * Version: 0.5.1
 * Requires PHP: 8.0
 * Author: constracti
 * Author URI: https://github.com/constracti
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: balitsa
 */

if ( !defined( 'ABSPATH' ) )
	exit;

// require php files
$files = glob( Balitsa::dir( '*.php' ) );
foreach ( $files as $file ) {
	if ( $file !== __FILE__ )
		require_once( $file );
}

// load plugin translations
add_action( 'init', function(): void {
	load_plugin_textdomain( 'balitsa', FALSE, basename( __DIR__ ) . '/languages' );
} );

// settings page
add_action( 'admin_menu', function(): void {
	$page_title = esc_html__( 'Balitsa', 'balitsa' );
	$menu_title = esc_html__( 'Balitsa', 'balitsa' );
	$capability = 'manage_options';
	$menu_slug = 'balitsa';
	add_options_page( $page_title, $menu_title, $capability, $menu_slug, function() {
		$tab_curr = Balitsa_Request::get( 'str', 'tab', TRUE ) ?? 'settings';
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
	wp_enqueue_style( 'balitsa-flex', Balitsa::url( 'flex.css' ), [], Balitsa::version() );
	wp_enqueue_style( 'balitsa-tree', Balitsa::url( 'tree.css' ), [], Balitsa::version() );
	wp_enqueue_script( 'balitsa-script', Balitsa::url( 'script.js' ), [ 'jquery' ], Balitsa::version() );
} );

final class Balitsa {

	public static function dir( string $dir ): string {
		return plugin_dir_path( __FILE__ ) . $dir;
	}

	public static function url( string $url ): string {
		return plugin_dir_url( __FILE__ ) . $url;
	}

	public static function version(): string {
		if ( !function_exists( 'get_plugin_data' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$plugin_data = get_plugin_data( __FILE__ );
		return $plugin_data['Version'];
	}

	public static function success( string $html ): void {
		header( 'content-type: application/json' );
		exit( json_encode( [
			'html' => $html,
		] ) );
	}

	public static function atts( array $atts ): string {
		$return = '';
		foreach ( $atts as $prop => $val ) {
			$return .= sprintf( ' %s="%s"', $prop, $val );
		}
		return $return;
	}

	private static function nonce_action( string $action, string ...$args ): string {
		foreach ( $args as $arg )
			$action .= '_' . $arg;
		return $action;
	}

	public static function nonce_create( string $action, string ...$args ): string {
		return wp_create_nonce( self::nonce_action( $action, ...$args ) );
	}

	public static function nonce_verify( string $action, string ...$args ): void {
		$nonce = Balitsa_Request::get( 'str', 'nonce' );
		if ( !wp_verify_nonce( $nonce, self::nonce_action( $action, ...$args ) ) )
			exit( 'nonce' );
	}

	public static function sorter( string ...$keys ): callable {
		return function( array $a1, array $a2 ) use ( $keys ): int {
			foreach ( $keys as $key ) {
				$negate = FALSE;
				if ( substr( $key, 0, 1 ) === '~' ) {
					$negate = TRUE;
					$key = substr( $key, 1 );
				}
				$cmp = $a1[$key] <=> $a2[$key];
				if ( $negate )
					$cmp = -$cmp;
				if ( $cmp )
					return $cmp;
			}
			return 0;
		};
	}
}
