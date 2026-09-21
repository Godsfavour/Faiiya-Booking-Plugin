<?php
/**
 * Elementor Page Builder Integration.
 * Provides custom drag-and-drop widgets for the booking engine.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YAB_Elementor {

	/**
	 * Initialize Elementor integration hooks.
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'on_plugins_loaded' ) );
	}

	/**
	 * Check if Elementor is loaded.
	 */
	public static function on_plugins_loaded() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		// Register custom widget category
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_categories' ) );

		// Register widgets (Elementor v3.5+)
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
		// Legacy fallback for older Elementor versions
		add_action( 'elementor/widgets/widgets_registered', array( __CLASS__, 'register_widgets_legacy' ) );
	}

	/**
	 * Register custom Elementor category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public static function register_categories( $elements_manager ) {
		$elements_manager->add_category(
			'yasmine-artistry',
			array(
				'title' => __( 'Yasmine Artistry', 'yasmine-artistry-booking' ),
				'icon'  => 'eicon-calendar',
			)
		);
	}

	/**
	 * Register widgets with Elementor (v3.5+).
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public static function register_widgets( $widgets_manager ) {
		self::load_widget_class();
		if ( class_exists( 'YAB_Elementor_Booking_Widget' ) ) {
			$widgets_manager->register( new YAB_Elementor_Booking_Widget() );
		}
	}

	/**
	 * Legacy widget registration callback.
	 */
	public static function register_widgets_legacy() {
		self::load_widget_class();
		if ( class_exists( 'YAB_Elementor_Booking_Widget' ) && class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new YAB_Elementor_Booking_Widget() );
		}
	}

	/**
	 * Load widget class definition.
	 */
	private static function load_widget_class() {
		if ( class_exists( 'YAB_Elementor_Booking_Widget' ) ) {
			return;
		}

		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		class YAB_Elementor_Booking_Widget extends \Elementor\Widget_Base {

			public function get_name() {
				return 'yab_booking_form';
			}

			public function get_title() {
				return __( 'Yasmine Booking Form', 'yasmine-artistry-booking' );
			}

			public function get_icon() {
				return 'eicon-form-horizontal';
			}

			public function get_categories() {
				return array( 'yasmine-artistry', 'general' );
			}

			public function get_keywords() {
				return array( 'booking', 'appointment', 'salon', 'yasmine', 'calendar', 'service', 'reservation' );
			}

			protected function register_controls() {
				$this->start_controls_section(
					'section_content',
					array(
						'label' => __( 'Booking Form Settings', 'yasmine-artistry-booking' ),
					)
				);

				$this->add_control(
					'form_type',
					array(
						'label'   => __( 'Form Component', 'yasmine-artistry-booking' ),
						'type'    => \Elementor\Controls_Manager::SELECT,
						'default' => 'booking',
						'options' => array(
							'booking' => __( 'Client Booking Flow (Multi-Step)', 'yasmine-artistry-booking' ),
							'portal'  => __( 'Self-Service Reschedule Portal', 'yasmine-artistry-booking' ),
						),
					)
				);

				$this->add_control(
					'custom_title',
					array(
						'label'       => __( 'Custom Section Title', 'yasmine-artistry-booking' ),
						'type'        => \Elementor\Controls_Manager::TEXT,
						'default'     => '',
						'placeholder' => __( 'e.g. Book Your Appointment', 'yasmine-artistry-booking' ),
						'label_block' => true,
					)
				);

				$this->add_control(
					'max_width',
					array(
						'label'      => __( 'Container Max Width (px)', 'yasmine-artistry-booking' ),
						'type'       => \Elementor\Controls_Manager::SLIDER,
						'size_units' => array( 'px', '%' ),
						'range'      => array(
							'px' => array(
								'min'  => 400,
								'max'  => 1200,
								'step' => 10,
							),
							'%'  => array(
								'min'  => 50,
								'max'  => 100,
							),
						),
						'default'    => array(
							'unit' => 'px',
							'size' => 820,
						),
						'selectors'  => array(
							'{{WRAPPER}} .yab-booking-container' => 'max-width: {{SIZE}}{{UNIT}};',
							'{{WRAPPER}} .yab-portal-container'  => 'max-width: {{SIZE}}{{UNIT}};',
						),
					)
				);

				$this->end_controls_section();
			}

			protected function render() {
				$settings = $this->get_settings_for_display();

				if ( ! empty( $settings['custom_title'] ) ) {
					echo '<h2 class="yab-elementor-title" style="text-align:center; margin-bottom: 20px; font-weight:800; color: #1a365d;">' . esc_html( $settings['custom_title'] ) . '</h2>';
				}

				if ( 'portal' === $settings['form_type'] ) {
					echo do_shortcode( '[yasmine_client_portal]' );
				} else {
					echo do_shortcode( '[yasmine_booking]' );
				}
			}
		}
	}
}
