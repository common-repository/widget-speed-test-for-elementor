<?php
/**
 *
 * @link              https://www.dcsdigital.co.uk
 * @since             1.0.0
 * @package           Widget_Speed_Test_For_Elementor
 *
 * @wordpress-plugin
 * Plugin Name:       Widget Speed Test for Elementor
 * Plugin URI:        https://www.dcsdigital.co.uk
 * Description:       Identify Elementor widgets that are slowing down page rendering and load times
 * Version:           1.0.4
 * Author:            DCS Digital
 * Author URI:        https://www.dcsdigital.co.uk/contact
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dcs-digital-guides
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Only initiate the plugin after Elementor has loaded (and by extension, if it's installed and active)
add_action( 'after_setup_theme', 'widget_speed_test_elementor_loaded' ); 

function widget_speed_test_elementor_loaded() {
    if ( did_action( 'elementor/loaded' ) ) {
        new Widget_Speed_Test_For_Elementor();
    }
}

class Widget_Speed_Test_For_Elementor {

    private $render_times = [];
    private $render_start_time = 0;
    private $render_total_time = 0;

	public function __construct() {

        // Initiate the debugger
        // Don't show on Elementor screens by detecting if GET 'action' is set (we can't use the built-in
        // Elementor functions such as is_edit_mode at this point because it hasn't been initiated yet)
        //if ( !is_admin() && is_plugin_active( 'elementor/elementor.php' )  && !isset( $_GET['action'] )  && !isset( $_GET['elementor-preview'] ) ) {
        if ( !is_admin()  && !isset( $_GET['action'] )  && !isset( $_GET['elementor-preview'] ) ) {

            $this->render_times = 0;
            $this->render_total_time = 0;
            $this->render_times = [];

            add_action( 'init', [ $this, 'launch_debug' ] );

        }

    }

    /**
     * Setup the hooks, filters and enqueue required files
     *
     * @since 1.0.0
     */
    public function launch_debug() {

        // Only show debugging information to logged-in admins on front-end pages
        if( is_user_logged_in() && current_user_can( 'manage_options' )) {

            add_action( 'admin_bar_menu', [ $this, 'add_menu_in_admin_bar' ], 202 );
            add_action( 'elementor/frontend/before_render', [ $this, 'before_render' ], 10, 1 );
            add_action( 'elementor/frontend/after_render', [ $this, 'after_render' ], 10, 1 );
            add_action( 'wp_footer', [ $this, 'output_results' ] ); 

            add_filter( 'elementor/widget/render_content', [ $this, 'render_content' ], 10, 2 );

            wp_enqueue_style( 'Widget Speed Tests for Elementor', plugin_dir_url( __FILE__ ) . 'css/style.css', [], '1.0.4', 'all' );
            wp_enqueue_script( 'Widget Speed Tests for Elementor', plugin_dir_url( __FILE__ ) . 'js/scripts.js', [ 'jquery' ], '1.0.4', 'all' );
        }

    }

    /**
     * Start the timer!
     * Reference: See print_element() in wp-content/plugins/elementor/includes/base/element-base.php
     *
     * @since 1.0.0
     * @param \Elementor\Element_Base $element The element instance.
     */
    public function before_render( $element ) {

        $this->render_start_time = microtime(true);

    }

    /**
     * End the timer!
     *
     * @since 1.0.0
     * @param \Elementor\Element_Base $element The element instance.
     */
    public function after_render( $element ) {

        $time_elapsed_secs = microtime(true) - $this->render_start_time;

        $this->render_times[] = [
            'id' => $element->get_id(),
            'type' => $element->get_type(),
            'name' => $element->get_name(),
            'icon' => $element->get_icon(),
            'help' => $element->get_help_url(),
            'time' => $time_elapsed_secs,
            'class_name' => $element->get_class_name(),
            //'config' => $element->get_config(),
            'active_settings' => $element->get_active_settings(),
        ];

        if($element->get_type() != 'container') {
            $this->render_total_time += $time_elapsed_secs;
        }

    }

    /**
     * Add content to the widget being analysed
     * @since 1.0.0
     * @param  $widget_content The rendered content of the widget
     * @param  $widget The widget instance
     */
    public function render_content( $widget_content, $widget ) {

        return $widget_content;

    }

    /**
     * Show the results on the frontend
     * @since 1.0.0
     */
    public function output_results() { 

        // Sort results by render time
        usort($this->render_times, function($a, $b) {
            return $a['time'] <=> $b['time'];
        });

        // Sort slowest to fastest, so the slowest widgets show at the top
        $this->render_times = array_reverse($this->render_times);

        $total_speed_class = 'speed-normal';

        if($this->render_total_time >= 2) {
            $total_speed_class = 'speed-xx-slow';
        } else if($this->render_total_time >= 1.5) {
            $total_speed_class = 'speed-x-slow';
        } else if($this->render_total_time >= 1) {
            $total_speed_class = 'speed-slow';
        }

        // Output the results
        echo '
            <div id="dcs-elementor-speed-results">
                <h4>Widget Speed Results</h4>
                <p><strong>Total render time: <span class="'.esc_attr( $total_speed_class ).'">'.esc_html( round( $this->render_total_time, 3 ) ).' secs</span></strong></p>
                <hr />
            ';

            foreach($this->render_times as $key => $result) {

                if($result['type'] != 'container' && $result['type'] != 'section' && $result['type'] != 'column') {

                    $speed_class = 'speed-normal';

                    if($result['time'] >= 1) {
                        $speed_class = 'speed-xx-slow';
                    } else if($result['time'] >= 0.5) {
                        $speed_class = 'speed-x-slow';
                    } else if($result['time'] >= 0.1) {
                        $speed_class = 'speed-slow';
                    }

                    echo '
                        <div class="widget-result">
                            <strong>
                                <a href="#'.esc_attr( $result['id'] ).'" class="dcs-elementor-render-time-result" data-target="'.esc_attr( $result['id'] ).'">
                                    <i class="'.esc_attr( $result['icon'] ).'" aria-hidden="true"></i>
                                    '.esc_html( $result['name'] ).'
                                </a>
                            </strong>
                        
                            <ul>
                                <li>Render time: <strong class="'.esc_attr( $speed_class ).'">'.esc_html( round($result['time'],4) ).' seconds</strong></li>
                                <li>Type: '.esc_html( $result['type'] ).'</li>
                                <li>Name: '.esc_html( $result['name'] ).'</li>
                                <li>Class Name: '.esc_html( $result['class_name'] ).'</li>
                                <li><a href="'.esc_url( $result['help'] ).'" target="_blank">Author docs</a></li>
                                <li><a href="#" class="show-active-settings" data-target="'.esc_attr( $result['id'] ).'">View active settings</a></li>
                                <li></li>
                            </ul>

                            <pre class="dcs-elementor-speed-results-active-settings active-settings-'.esc_attr( $result['id'] ).'" >'.esc_html( json_encode($result['active_settings'], JSON_PRETTY_PRINT) ).'</pre>
                        </div>
                        ';

                }

            }

        echo '
            </div>
        ';

    }

	/**
     * Add the debug bar to the WordPress toolbar (black bar)
	 * @since 1.0.0
	 */
	public function add_menu_in_admin_bar( \WP_Admin_Bar $wp_admin_bar ) {

		$wp_admin_bar->add_node( [
			'id' => 'elementor_widget_inspector',
			'title' => 'Widget Speed Test',
		] );

	}
}