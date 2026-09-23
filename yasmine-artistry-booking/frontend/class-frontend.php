<?php
/**
 * Frontend shortcode engine, script enqueueing, and customer portal router.
 *
 * @package Yasmine_Artistry_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Frontend {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_shortcode( 'yasmine_booking', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	/**
	 * Register frontend scripts and styles.
	 */
	public static function register_assets() {
		// Enqueue Google Fonts: Cormorant Garamond, Montserrat, Playfair Display
		wp_register_style(
			'yab-google-fonts',
			'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,400;1,600&family=Montserrat:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400;1,600&display=swap',
			array(),
			null
		);

		wp_register_style(
			'yab-frontend-css',
			YAB_PLUGIN_URL . 'assets/css/yab-frontend.css',
			array( 'yab-google-fonts' ),
			YAB_VERSION
		);

		// Official Paystack Inline checkout library
		wp_register_script(
			'paystack-inline',
			'https://js.paystack.co/v1/inline.js',
			array(),
			null,
			true
		);

		wp_register_script(
			'yab-frontend-js',
			YAB_PLUGIN_URL . 'assets/js/yab-frontend.js',
			array( 'paystack-inline' ),
			YAB_VERSION,
			true
		);

		wp_localize_script(
			'yab-frontend-js',
			'yabConfig',
			array(
				'restUrl'        => esc_url_raw( rest_url( 'yab/v1' ) ),
				'nonce'          => wp_create_nonce( 'wp_rest' ),
				'currencySymbol' => YAB_Settings::get( 'general', 'currency_symbol', '₦' ),
				'currency'       => YAB_Settings::get( 'general', 'currency', 'NGN' ),
				'paystackKey'    => YAB_Paystack::get_public_key(),
				'businessName'   => YAB_Settings::get( 'general', 'business_name', 'Yasmine Artistry' ),
				'depositType'    => YAB_Settings::get( 'deposit', 'deposit_type', 'percentage' ),
				'depositValue'   => floatval( YAB_Settings::get( 'deposit', 'deposit_percentage', 50 ) ),
				'extraLookRate'  => floatval( YAB_Settings::get( 'deposit', 'extra_look_rate', 50000.00 ) ),
			)
		);
	}

	/**
	 * Render the booking container shortcode.
	 *
	 * @param array $atts
	 * @return string HTML output
	 */
	public static function render_shortcode( $atts ) {
		wp_enqueue_style( 'yab-frontend-css' );
		wp_enqueue_script( 'yab-frontend-js' );

		ob_start();

		$action = sanitize_key( $_GET['yab_action'] ?? '' );
		$token  = sanitize_text_field( $_GET['token'] ?? '' );
		$ref    = sanitize_text_field( $_GET['ref'] ?? '' );

		if ( 'manage' === $action && ! empty( $token ) ) {
			include YAB_PLUGIN_DIR . 'frontend/views/reschedule-form.php';
		} elseif ( 'confirmation' === $action && ! empty( $ref ) ) {
			include YAB_PLUGIN_DIR . 'frontend/views/confirmation.php';
		} else {
			include YAB_PLUGIN_DIR . 'frontend/views/booking-form.php';
		}

		return ob_get_clean();
	}
}
