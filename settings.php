<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_filter( 'balitsa_tab_list', function( array $tabs ): array {
	$tabs['settings'] = esc_html__( 'Settings', 'balitsa' );
	return $tabs;
} );

add_action( 'balitsa_tab_html_settings', 'balitsa_settings_echo' );

function balitsa_settings_echo(): void {
	$sports = balitsa_get_sports();
?>
<div class="balitsa-container root8 flex-col">
	<div class="flex-row flex-justify-between">
<?php
	echo sprintf( '<a%s>%s</a>', balitsa_attrs( [
		'href' => add_query_arg( [
			'action' => 'balitsa_settings_refresh',
			'nonce' => balitsa_nonce_create( 'balitsa_settings_refresh' ),
		], admin_url( 'admin-ajax.php' ) ),
		'class' => 'balitsa-link button leaf',
	] ), esc_html__( 'Refresh', 'balitsa' ) ) . "\n";
?>
		<span class="balitsa-spinner spinner leaf" data-balitsa-spinner-toggle="is-active"></span>
	</div>
	<hr class="leaf" />
	<div class="flex-row flex-justify-between flex-align-center">
		<h2 class="title leaf"><?= esc_html__( 'Sports', 'balitsa' ) ?></h2>
<?php
	echo sprintf( '<a%s>%s</a>', balitsa_attrs( [
		'href' => add_query_arg( [
			'action' => 'balitsa_settings_sport_insert',
			'nonce' => balitsa_nonce_create( 'balitsa_settings_sport_insert' ),
		], admin_url( 'admin-ajax.php' ) ),
		'class' => 'balitsa-insert button leaf',
		'data-balitsa-form' => '.balitsa-form-sport',
	] ), esc_html__( 'Insert', 'balitsa' ) ) . "\n";
?>
	</div>
	<div class="leaf">
		<table class="fixed widefat striped">
			<thead>
				<tr>
					<th class="column-primary has-row-actions"><?= esc_html__( 'Name', 'balitsa' ) ?></th>
					<th><?= esc_html__( 'Key', 'balitsa' ) ?></th>
					<th><?= esc_html__( 'Statistics', 'balitsa' ) ?></th>
				</tr>
			</thead>
			<tbody>
<?php
	foreach ( $sports as $sport_key => $sport ) {
		$actions = [];
		$actions['update'] = sprintf( '<a%s>%s</a>', balitsa_attrs( [
			'href' => add_query_arg( [
				'action' => 'balitsa_settings_sport_update',
				'sport' => $sport_key,
				'nonce' => balitsa_nonce_create( 'balitsa_settings_sport_update', $sport_key ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'balitsa-insert',
			'data-balitsa-form' => '.balitsa-form-sport',
			'data-balitsa-field-key' => esc_attr( $sport['key'] ),
			'data-balitsa-field-name' => esc_attr( $sport['name'] ),
			'data-balitsa-field-icon' => esc_attr( $sport['icon'] ),
		] ), esc_html__( 'Update', 'balitsa' ) );
		$actions['delete'] = sprintf( '<span class="delete"><a%s>%s</a></span>', balitsa_attrs( [
			'href' => add_query_arg( [
				'action' => 'balitsa_settings_sport_delete',
				'sport' => $sport_key,
				'nonce' => balitsa_nonce_create( 'balitsa_settings_sport_delete', $sport_key ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'balitsa-link',
			'data-balitsa-confirm' => esc_attr( sprintf( __( 'Delete sport %s?', 'balitsa' ), $sport['name'] ) ),
		] ), esc_html__( 'Delete', 'balitsa' ) );
		$actions['insert_stat'] = sprintf( '<a%s>%s</a>', balitsa_attrs( [
			'href' => add_query_arg( [
				'action' => 'balitsa_settings_sport_stat_insert',
				'sport' => $sport_key,
				'nonce' => balitsa_nonce_create( 'balitsa_settings_sport_stat_insert', $sport_key ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'balitsa-insert',
			'data-balitsa-form' => '.balitsa-form-stat',
		] ), esc_html__( 'Insert Statistic', 'balitsa' ) ) . "\n";
?>
				<tr>
					<td class="column-primary has-row-actions">
						<strong>
							<span class="<?= esc_attr( $sport['icon'] ) ?>"></span>
							<span><?= esc_html( $sport['name'] ) ?></span>
						</strong>
						<div class="row-actions"><?= implode( ' | ', $actions ) ?></div>
					</td>
					<td><?= esc_html( $sport_key ) ?></td>
					<td>
<?php
		foreach ( $sport['stats'] as $stat_key => $stat ) {
?>
						<div class="flex-row flex-justify-between flex-align-center">
							<div>
								<span class="<?= esc_attr( $stat['icon'] ) ?>"></span>
								<span><?= esc_html( $stat['name'] ) ?></span>
							</div>
							<div>
<?php
			echo sprintf( '<a%s><span class="fas fa-fw fa-trash"></span></a>', balitsa_attrs( [
				'href' => add_query_arg( [
					'action' => 'balitsa_settings_sport_stat_delete',
					'sport' => $sport_key,
					'stat' => $stat_key,
					'nonce' => balitsa_nonce_create( 'balitsa_settings_sport_stat_delete', $sport_key, $stat_key ),
				], admin_url( 'admin-ajax.php' ) ),
				'class' => 'balitsa-link',
				'title' => esc_attr__( 'Delete', 'balitsa' ),
				'data-balitsa-confirm' => esc_attr( sprintf( __( 'Delete statistic %s?', 'balitsa' ), $stat['name'] ) ),
			] ) ) . "\n";
?>
							</div>
						</div>
<?php
		}
?>
					</td>
				</tr>
<?php
	}
?>
			</tbody>
		</table>
	</div>
	<div class="balitsa-form balitsa-form-sport leaf root8 root-border flex-col" style="display: none;">
		<h3 class="leaf"><?= esc_html__( 'Sport', 'balitsa' ) ?></h3>
		<div class="leaf">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="balitsa-form-sport-key"><?= esc_html__( 'Key', 'balitsa' ) ?></label></th>
						<td><input type="text" class="balitsa-field regular-text" data-balitsa-name="key" id="balitsa-form-sport-key" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="balitsa-form-sport-name"><?= esc_html__( 'Name', 'balitsa' ) ?></label></th>
						<td><input type="text" class="balitsa-field regular-text" data-balitsa-name="name" id="balitsa-form-sport-name" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="balitsa-form-sport-icon"><?= esc_html__( 'Icon', 'balitsa' ) ?></label></th>
						<td><input type="text" class="balitsa-field regular-text" data-balitsa-name="icon" id="balitsa-form-sport-icon" /></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="flex-row flex-justify-between flex-align-center">
			<a href="" class="balitsa-link balitsa-submit button button-primary leaf"><?= esc_html__( 'Submit', 'balitsa' ) ?></a>
			<a href="" class="balitsa-cancel button leaf"><?= esc_html__( 'Cancel', 'balitsa' ) ?></a>
		</div>
	</div>
	<div class="balitsa-form balitsa-form-stat leaf root8 root-border flex-col" style="display: none;">
		<h3 class="leaf"><?= esc_html__( 'Statistic', 'balitsa' ) ?></h3>
		<div class="leaf">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="balitsa-form-stat-key"><?= esc_html__( 'Key', 'balitsa' ) ?></label></th>
						<td><input type="text" class="balitsa-field regular-text" data-balitsa-name="key" id="balitsa-form-stat-key" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="balitsa-form-stat-name"><?= esc_html__( 'Name', 'balitsa' ) ?></label></th>
						<td><input type="text" class="balitsa-field regular-text" data-balitsa-name="name" id="balitsa-form-stat-name" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="balitsa-form-stat-icon"><?= esc_html__( 'Icon', 'balitsa' ) ?></label></th>
						<td><input type="text" class="balitsa-field regular-text" data-balitsa-name="icon" id="balitsa-form-stat-icon" /></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="flex-row flex-justify-between flex-align-center">
			<a href="" class="balitsa-link balitsa-submit button button-primary leaf"><?= esc_html__( 'Submit', 'balitsa' ) ?></a>
			<a href="" class="balitsa-cancel button leaf"><?= esc_html__( 'Cancel', 'balitsa' ) ?></a>
		</div>
	</div>
</div>
<?php
}

function balitsa_settings(): string {
	ob_start();
	balitsa_settings_echo();
	return ob_get_clean();
}

add_action( 'wp_ajax_' . 'balitsa_settings_refresh', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	balitsa_nonce_verify( 'balitsa_settings_refresh' );
	balitsa_success( balitsa_settings() );
} );

add_action( 'wp_ajax_' . 'balitsa_settings_sport_insert', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	$sports = balitsa_get_sports();
	balitsa_nonce_verify( 'balitsa_settings_sport_insert' );
	$sport = [
		'key' => balitsa_post_word( 'key' ),
		'name' => balitsa_post_text( 'name' ),
		'icon' => balitsa_post_text( 'icon' ),
		'stats' => [],
	];
	if ( array_key_exists( $sport['key'], $sports ) )
		exit( 'key' );
	$sports[$sport['key']] = $sport;
	balitsa_set_sports( $sports );
	balitsa_success( balitsa_settings() );
} );

add_action( 'wp_ajax_' . 'balitsa_settings_sport_update', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	$sports = balitsa_get_sports();
	$sport_key = balitsa_get_str( 'sport' );
	if ( !array_key_exists( $sport_key, $sports ) )
		exit( 'sport' );
	$sport = &$sports[$sport_key];
	balitsa_nonce_verify( 'balitsa_settings_sport_update', $sport_key );
	$sport['key'] = balitsa_post_word( 'key' );
	$sport['name'] = balitsa_post_text( 'name' );
	$sport['icon'] = balitsa_post_text( 'icon' );
	if ( $sport['key'] === $sport_key ) {
	} elseif ( array_key_exists( $sport['key'], $sports ) ) {
		exit( 'key' );
	} else {
		unset( $sports[$sport_key] );
		$sports[$sport['key']] = $sport;
	}
	balitsa_set_sports( $sports );
	balitsa_success( balitsa_settings() );
} );

add_action( 'wp_ajax_' . 'balitsa_settings_sport_delete', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	$sports = balitsa_get_sports();
	$sport_key = balitsa_get_str( 'sport' );
	if ( !array_key_exists( $sport_key, $sports ) )
		exit( 'sport' );
	balitsa_nonce_verify( 'balitsa_settings_sport_delete', $sport_key );
	unset( $sports[$sport_key] );
	balitsa_set_sports( $sports );
	balitsa_success( balitsa_settings() );
} );

add_action( 'wp_ajax_' . 'balitsa_settings_sport_stat_insert', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	$sports = balitsa_get_sports();
	$sport_key = balitsa_get_str( 'sport' );
	if ( !array_key_exists( $sport_key, $sports ) )
		exit( 'sport' );
	$sport = &$sports[$sport_key];
	balitsa_nonce_verify( 'balitsa_settings_sport_stat_insert', $sport_key );
	$stat = [
		'key' => balitsa_post_text( 'key' ),
		'name' => balitsa_post_text( 'name' ),
		'icon' => balitsa_post_text( 'icon' ),
	];
	if ( array_key_exists( $stat['key'], $sport['stats'] ) )
		exit( 'key' );
	$sport['stats'][$stat['key']] = $stat;
	balitsa_set_sports( $sports );
	balitsa_success( balitsa_settings() );
} );

add_action( 'wp_ajax_' . 'balitsa_settings_sport_stat_delete', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	$sports = balitsa_get_sports();
	$sport_key = balitsa_get_str( 'sport' );
	if ( !array_key_exists( $sport_key, $sports ) )
		exit( 'sport' );
	$sport = &$sports[$sport_key];
	$stat_key = balitsa_get_str( 'stat' );
	if ( !array_key_exists( $stat_key, $sport['stats'] ) )
		exit( 'stat' );
	balitsa_nonce_verify( 'balitsa_settings_sport_stat_delete', $sport_key, $stat_key );
	unset( $sport['stats'][$stat_key] );
	balitsa_set_sports( $sports );
	balitsa_success( balitsa_settings() );
} );
