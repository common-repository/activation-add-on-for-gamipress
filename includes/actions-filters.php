<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class GamiPress_Activation_Actions_Filters {

    /**
     * @var GamiPress_Activation_Actions_Filters
     */
    private static $instance;

    const DEBUG = 1;

    /**
     * Main GamiPress_Activation_Actions_Filters Instance
     *
     * Insures that only one instance of GamiPress_Activation_Actions_Filters exists in memory at
     * any one time. Also prevents needing to define globals all over the place.
     *
     * @since GamiPress_Activation_Actions_Filters (0.0.3)
     *
     * @staticvar array $instance
     *
     * @return GamiPress_Activation_Actions_Filters
     */
    public static function instance( ) {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new GamiPress_Activation_Actions_Filters;
            self::$instance->setup_filters();
            self::$instance->setup_actions();
        }

        return self::$instance;
    }

    /**
     * A dummy constructor to prevent loading more than one instance
     *
     * @since GamiPress_Activation_Actions_Filters (0.0.1)
     */
    private function __construct() { /* Do nothing here */
    }

    /**
     * Setup the actions
     *
     * @since GamiPress_Activation_Actions_Filters (0.0.1)
     * @access private
     *
     * @uses remove_action() To remove various actions
     * @uses add_action() To add various actions
     */
    private function setup_actions() {

        if ( self::DEBUG )
            add_action( 'admin_notices', array( $this, 'print_invalid_achievements') );


    }

	/**
	 * Setup the filters
	 *
	 * @since GamiPress_Activation_Actions_Filters (0.0.1)
	 * @access private
	 *
	 * @uses remove_filter() To remove various filters
	 * @uses add_filter() To add various filters
	 */
	private function setup_filters() {

		if ( ! $this->badges_activated() ) {
			add_filter( 'gamipress_user_deserves_trigger', '__return_false', 999 );
			add_filter( 'user_has_access_to_achievement', '__return_false', 999 );
			add_filter( 'gamipress_update_user_trigger_count', array( $this, 'return_zero' ), 999 );
		}

		add_filter( 'gamipress_settings_addons_meta_boxes', array( $this, 'settings_meta_boxes' ) );
	}

	public function print_invalid_achievements() {
        $screen = get_current_screen();

        if ( ! $screen || $screen->base != 'gamipress_page_gamipress_settings' )
            return;

        $invalid_achievements = $this->get_invalid_achievements();

        if ( empty( $invalid_achievements ) )
            return;

        echo '<div id="message" class="error">';
        echo '<h4>GamiPress Activation Addon has detected the following invalid achievements:</h4>';
        foreach ( $invalid_achievements as $achievement ) {
            echo "<p>$achievement</p>";
        }
        echo '</div>';
    }

    private function get_activation_date( $type = 'start' ) {
		$gamipress_settings = get_option( 'gamipress_settings' );
        return apply_filters( "gamipress_activation_$type",
			isset( $gamipress_settings["activate_badges_$type"] ) ? $gamipress_settings["activate_badges_$type"] : '' );
    }

    private function badges_activated() {
        $gamipress_settings = get_option( 'gamipress_settings' );

        $activate_badges = ( isset( $gamipress_settings['activate_badges'] ) ) ? $gamipress_settings['activate_badges'] :
			$this->activate_badges_default();

        $activate_badges_start = $this->get_activation_date( 'start' );
        $activate_badges_end   = $this->get_activation_date( 'end' );

        // if global switch is off, bail
        if ( ! $activate_badges )
            return false;

        // if start date is set and not yet reached, bail
        if ( $activate_badges_start && strtotime( $activate_badges_start ) > time() )
            return false;

         // if end date is set and already over, bail
        if ( $activate_badges_end && strtotime( $activate_badges_end ) < time() )
            return false;

        return true;
    }

    function get_invalid_achievements() {
               $activate_badges_start = $this->get_activation_date( 'start' );
               $activate_badges_end   = $this->get_activation_date( 'end' );
        $invalid_achievements = array();

        if ( ! $activate_badges_start && ! $activate_badges_end )
            return $invalid_achievements;

        $args = array( 'fields' => array('ID', 'display_name', 'user_nicename' ) );
        $query = new WP_User_Query( $args );

        foreach ( $query->results as $user ) {
            $earned_achievements = gamipress_get_user_achievements( array(
                'user_id'          => $user->ID
            ) );

            foreach ( $earned_achievements as $key => $achievement ) {

                $date = $achievement->date_earned;
                $last_achievement = get_post( $achievement->ID );

                $print = false;
                if ( $activate_badges_start && strtotime( $activate_badges_start ) > $date )
                    $print = "Invalid before start";

                if ( $activate_badges_end && strtotime( $activate_badges_end ) < $date )
                    $print = "Invalid after end";

                if ( $print )
                    $invalid_achievements[] = "$print: User: $user->ID: $user->display_name: " . gmdate("Y-m-d\TH:i:s\Z", $date) . " : $last_achievement->ID:$last_achievement->post_title";

            }

        }

        return $invalid_achievements;
    }

    /**
     * just return 0
     * @return 0
     */
    public function return_zero() {
        return 0;
    }

	/**
	 * Activation Settings meta boxes
	 *
	 * @since  0.0.1
	 *
	 * @param $meta_boxes
	 *
	 * @return mixed
	 */
	public function settings_meta_boxes( $meta_boxes ) {

		$default_atts          = array(
			'data-datepicker' => json_encode( array(
				'dateFormat' => 'dd.mm.yy'
			) ),
		);
		$start_date_atts       = apply_filters( 'gamipress_activation_start_date_input_attributes', $default_atts );
		$end_date_atts         = apply_filters( 'gamipress_activation_end_date_input_attributes', $default_atts );
		$activate_badges_start = $this->get_activation_date( 'start' );
		$activate_badges_end   = $this->get_activation_date( 'end' );


		$meta_boxes['gamipress-activation-settings'] = array(
			'title'  => __( 'Activation', 'gamipress-activation-addon' ),
			'fields' => array(
				'activate_badges'       => array(
					'name'    => __( 'Activate Badges', 'gamipress-activation-addon' ),
					'desc'    => __( 'Activate the awarding of badges (if unchecked overrides date settings).', 'gamipress-activation-addon' ),
					'type'    => 'checkbox',
					'default' => array( $this, 'activate_badges_default' ),
					'classes' => 'gamipress-switch',
				),
				'activate_badges_start' => array(
					'name'       => __( 'Activate Badges date', 'gamipress-activation-addon' ),
					'desc'       => __( 'Activate the awarding of badges on this date (leave empty to activate immediately)', 'gamipress-activation-addon' ),
					'type'       => 'text_date',
					'default'    => $activate_badges_start,
					'attributes' => $start_date_atts,
				),
				'activate_badges_end'   => array(
					'name'       => __( 'Deactivate Badges date', 'gamipress-activation-addon' ),
					'desc'       => __( 'Deactivate the awarding of badges on this date (leave empty to never deactivate)', 'gamipress-activation-addon' ),
					'type'       => 'text_date',
					'default'    => $activate_badges_end,
					'attributes' => $end_date_atts,
				),
				'activate_settings_saved'   => array(
					'type'      => 'hidden',
					'default'   => true
				),
			)
		);

		return $meta_boxes;
	}

	function activate_badges_default() {
		$gamipress_settings = get_option( 'gamipress_settings' );
		return isset( $gamipress_settings[ 'activate_settings_saved' ] ) ? '' : true;
	}
}

GamiPress_Activation_Actions_Filters::instance();
