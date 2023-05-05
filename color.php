<?php

if ( !defined( 'ABSPATH' ) )
	exit;

/*

color : ?string

*/

final class Balitsa_Color {

	public const LIST = [
		'Aqua'          => 'Black',
		'Black'         => 'White',
		'Blue'          => 'White',
		'Brown'         => 'White',
		'Cyan'          => 'Black',
		'DarkGray'      => 'White',
		'Gray'          => 'Black',
		'Green'         => 'White',
		'Indigo'        => 'White',
		'Khaki'         => 'Black',
		'LightBlue'     => 'Black',
		'LightGray'     => 'Black',
		'LightGreen'    => 'Black',
		'Lime'          => 'Black',
		'Orange'        => 'Black',
		'OrangeRed'     => 'White',
		'Pink'          => 'Black',
		'Purple'        => 'White',
		'RebeccaPurple' => 'White',
		'Red'           => 'White',
		'Snow'          => 'Black',
		'SteelBlue'     => 'White',
		'Teal'          => 'White',
		'White'         => 'Black',
		'Yellow'        => 'Black',
	];

	private $user;
	private $color;

	public function __construct( WP_User $user ) {
		$this->user = $user;
		$this->load();
	}

	private function load(): void {
		$this->color = get_user_meta( $this->user->ID, 'balitsa_color', TRUE );
		if ( $this->color === '' )
			$this->color = NULL;
	}

	private function save(): void {
		if ( !is_null( $this->color ) )
			update_user_meta( $this->user->ID, 'balitsa_color', $this->color );
		else
			delete_user_meta( $this->user->ID, 'balitsa_color' );
	}

	// functions

	public function get(): ?string {
		return $this->color;
	}

	public function set( ?string $color ): void {
		$this->color = $color;
		$this->save();
	}

	public function css(): string {
		$bg = $this->color;
		if ( !isset( self::LIST[$bg] ) )
			return '';
		$fg = self::LIST[$bg];
		return sprintf( 'background-color: %s; color: %s;', $bg, $fg );
	}

	// user edit section

	public static function user_edit_section( WP_User $user ): void {
		$color = new self( $user );
		$html = '';
		$html .= sprintf( '<h2>%s</h2>', esc_html__( 'Balitsa', 'balitsa' ) ) . "\n";
		$html .= '<table class="form-table" role="presentation">' . "\n";
		$html .= '<tbody>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= sprintf( '<th><label for="balitsa_color">%s</label></th>', esc_html__( 'Color', 'balitsa' ) ) . "\n";
		$html .= '<td>' . "\n";
		$html .= '<select name="balitsa_color" id="balitsa_color">' . "\n";
		$html .= sprintf( '<option value=""%s></option>', selected( is_null( $color->get() ), TRUE, FALSE ) ) . "\n";
		foreach ( Balitsa_Color::LIST as $bg => $fg ) {
			$html .= sprintf( '<option value="%s" data-fg="%s"%s>%s</option>', esc_attr( $bg ), esc_attr( $fg ), selected( $color->get() === $bg, TRUE, FALSE ), esc_html( $bg ) ) . "\n";
		}
		$html .= '</select>' . "\n";
		$html .= '<p><span id="balitsa_color_preview" style="border-radius: 4px; padding: 4px;"></span></p>' . "\n";
		$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";
		echo $html;
	}

	public static function user_edit_section_submit( int $user_id ): void {
		$user = get_user_by( 'ID', $user_id );
		if ( $user === FALSE )
			exit( 'user' );
		$color = new self( $user );
		$bg = Balitsa::request_str( 'post', 'balitsa_color', TRUE, option_list: array_keys( self::LIST ) );
		$color->set( $bg );
	}
}

add_action( 'show_user_profile', ['Balitsa_Color', 'user_edit_section'] );
add_action( 'edit_user_profile', ['Balitsa_Color', 'user_edit_section'] );

add_action( 'personal_options_update', ['Balitsa_Color', 'user_edit_section_submit'] );
add_action( 'edit_user_profile_update', ['Balitsa_Color', 'user_edit_section_submit'] );

add_action( 'admin_enqueue_scripts', function( string $hook_suffix ): void {
	if ( $hook_suffix !== 'profile.php' && $hook_suffix !== 'user-edit.php' )
		return;
	wp_enqueue_script( 'balitsa-color-user-edit-script', Balitsa::url( 'color_user_edit.js' ), [ 'jquery' ], Balitsa::version() );
} );
