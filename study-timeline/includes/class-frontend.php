<?php
/**
 * Manages plugin assets (CSS/JS) and shortcodes.
 */
class Study_Timeline_Frontend {

    public function __construct() {
        // Register the shortcode [study_timeline id="..."]
        add_shortcode( 'study_timeline', [ $this, 'render_timeline_shortcode' ] );

        // Register scripts and styles, but don't enqueue them yet.
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
    }

    /**
     * Registers all necessary CSS and JS files.
     */
    public function register_assets() {
        $plugin_url = plugin_dir_url( dirname( __FILE__ ) );

        // Register Vis.js Timeline library
        wp_register_style(
            'vis-timeline-style',
            'https://unpkg.com/vis-timeline@latest/styles/vis-timeline-graph2d.min.css',
            [],
            '7.7.0'
        );
        wp_register_script(
            'vis-timeline-script',
            'https://unpkg.com/vis-timeline@latest/standalone/umd/vis-timeline-graph2d.min.js',
            [],
            '7.7.0',
            true
        );

        // Register our custom controller script
        wp_register_script(
            'study-timeline-controller',
            $plugin_url . 'assets/js/timeline-controller.js',
            [ 'vis-timeline-script', 'wp-api-fetch' ], // Dependencies
            STUDY_TIMELINE_VERSION,
            true
        );

        // Register our custom style
        wp_register_style(
            'study-timeline-style',
            $plugin_url . 'assets/css/timeline-style.css',
            [ 'vis-timeline-style' ],
            STUDY_TIMELINE_VERSION
        );
    }

    /**
     * Renders the timeline via a shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_timeline_shortcode( $atts ) {
        // Extract the timeline ID from attributes, with a default of 0.
        $atts = shortcode_atts( [
            'id' => 0,
        ], $atts, 'study_timeline' );

        $timeline_id = (int) $atts['id'];

        if ( ! $timeline_id ) {
            return '<p>Error: Please provide a valid timeline ID.</p>';
        }

        // Now, enqueue the assets since the shortcode is being used.
        wp_enqueue_script( 'study-timeline-controller' );
        wp_enqueue_style( 'study-timeline-style' );

        // Localize script to pass data like timeline_id and API nonce to JS.
        wp_localize_script(
            'study-timeline-controller',
            'studyTimelineData',
            [
                'timelineId'  => $timeline_id,
                'nonce'       => wp_create_nonce( 'wp_rest' ),
                'apiBaseUrl'  => rest_url( 'study-timeline/v1/' )
            ]
        );

        // Return the container for the timeline. JS will populate this.
        $output = '<div class="study-timeline-wrapper">';
        $output .= '<div id="study-timeline-repository" class="study-timeline-repository"><h4>מאגר פריטים</h4><div class="repository-items"></div></div>';
        $output .= '<div id="study-timeline-container-' . esc_attr( $timeline_id ) . '" class="study-timeline-container"></div>';
        $output .= '</div>';

        return $output;
    }
}
