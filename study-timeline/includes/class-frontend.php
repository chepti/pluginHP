<?php
/**
 * Manages plugin assets (CSS/JS) and shortcodes.
 * Updated with new annual timeline functionality.
 */
class Study_Timeline_Frontend {

    public function __construct() {
        // Register the shortcode [study_timeline id="..."]
        add_shortcode( 'study_timeline', [ $this, 'render_timeline_shortcode' ] );

        // Register the NEW shortcode [annual_timeline ...]
        add_shortcode( 'annual_timeline', [ $this, 'render_new_timeline_shortcode' ] );

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
            [ 'vis-timeline-script', 'wp-api-fetch' ], // Re-add vis.js dependency
            STUDY_TIMELINE_VERSION,
            true
        );

        // Register our custom style
        wp_register_style(
            'study-timeline-style',
            $plugin_url . 'assets/css/timeline-style.css',
            [ 'vis-timeline-style' ], // Re-add vis.js dependency
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

        // Enqueue the assets.
        wp_enqueue_script( 'vis-timeline-script' );
        wp_enqueue_script( 'study-timeline-controller' );
        wp_enqueue_style( 'vis-timeline-style' );
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

        // New structure based on the sketch
        $output = '<div class="study-timeline-app-wrapper">';
        
        // 1. Top section for the timeline visualization
        $output .= '<div id="study-timeline-container-' . esc_attr( $timeline_id ) . '" class="study-timeline-container"></div>';

        // 2. Bottom section for the repository/search
        $output .= '<div class="study-timeline-repository">';
        $output .= '<h4>מאגר פריטים</h4>';
        $output .= '<div class="repository-search"><input type="search" id="repo-search-input" placeholder="חיפוש פריטים..."></div>';
        $output .= '<div id="repository-items-container" class="repository-items"></div>';
        $output .= '</div>';

        $output .= '</div>'; // close study-timeline-app-wrapper

        return $output;
    }

    /**
     * Registers assets for the NEW annual timeline.
     */
    public function register_new_assets() {
        $plugin_dir = dirname( dirname( __FILE__ ) );
        $plugin_url = plugin_dir_url( $plugin_dir );

        // Register Vis.js Timeline library for new timeline
        if (function_exists('wp_register_script')) {
            wp_register_script(
                'vis-timeline',
                'https://unpkg.com/vis-timeline@7.7.0/standalone/umd/vis-timeline-graph2d.min.js',
                [],
                '7.7.0',
                true
            );
        }

        // Check if the new plugin directory exists
        $new_plugin_path = $plugin_dir . '/homer-patuach-annual-timeline';

        if (function_exists('is_dir') && is_dir($new_plugin_path)) {
            // Register local assets from the new plugin
            $timeline_css_path = $new_plugin_path . '/assets/css/timeline.css';
            if (function_exists('file_exists') && file_exists($timeline_css_path) && function_exists('wp_register_style')) {
                wp_register_style(
                    'hpat-timeline-styles',
                    $plugin_url . 'homer-patuach-annual-timeline/assets/css/timeline.css',
                    [],
                    '1.0.0'
                );
            }

            $timeline_js_path = $new_plugin_path . '/assets/js/timeline.js';
            if (function_exists('file_exists') && file_exists($timeline_js_path) && function_exists('wp_register_script')) {
                wp_register_script(
                    'hpat-timeline-script',
                    $plugin_url . 'homer-patuach-annual-timeline/assets/js/timeline.js',
                    ['jquery', 'vis-timeline'],
                    '1.0.0',
                    true
                );
            }
        }

        // If assets are not registered, use inline versions as fallback
        if (function_exists('wp_script_is') && function_exists('wp_register_script') && !wp_script_is('hpat-timeline-script', 'registered')) {
            wp_register_script('hpat-timeline-script', false, ['jquery', 'vis-timeline']);
            if (function_exists('wp_add_inline_script')) {
                wp_add_inline_script('hpat-timeline-script', $this->get_inline_js());
            }
        }

        if (function_exists('wp_style_is') && function_exists('wp_register_style') && !wp_style_is('hpat-timeline-styles', 'registered')) {
            wp_register_style('hpat-timeline-styles', false);
            if (function_exists('wp_add_inline_style')) {
                wp_add_inline_style('hpat-timeline-styles', $this->get_inline_css());
            }
        }
    }

    /**
     * Renders the NEW annual timeline via shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_new_timeline_shortcode( $atts ) {
        // Default attributes
        $atts = shortcode_atts( [
            'group_id' => '',
            'academic_year' => date('Y') . '-' . (date('Y') + 1),
            'height' => 400,
            'zoom_level' => 30,
            'show_search' => 'true',
            'show_controls' => 'true'
        ], $atts, 'annual_timeline' );

        // For now, create a demo timeline if none exists
        if (empty($atts['group_id'])) {
            $atts['group_id'] = 'demo_timeline';
        }

        // Register and enqueue the new assets only when needed
        $this->register_new_assets();

        // Safe enqueue with error handling
        if (function_exists('wp_script_is') && function_exists('wp_enqueue_script') && wp_script_is('vis-timeline', 'registered')) {
            wp_enqueue_script( 'vis-timeline' );
        }

        if (function_exists('wp_script_is') && function_exists('wp_enqueue_script') && wp_script_is('hpat-timeline-script', 'registered')) {
            wp_enqueue_script( 'hpat-timeline-script' );
        }

        if (function_exists('wp_style_is') && function_exists('wp_enqueue_style') && wp_style_is('hpat-timeline-styles', 'registered')) {
            wp_enqueue_style( 'hpat-timeline-styles' );
        }

        // Localize script only if function exists and script is registered
        if (function_exists('wp_localize_script') && function_exists('wp_script_is') && wp_script_is('hpat-timeline-script', 'registered')) {
            wp_localize_script(
                'hpat-timeline-script',
                'hpat_ajax',
                [
                    'ajax_url' => function_exists('admin_url') ? admin_url('admin-ajax.php') : '',
                    'nonce' => function_exists('wp_create_nonce') ? wp_create_nonce('hpat_timeline_nonce') : '',
                    'strings' => [
                        'loading' => __('טוען...', 'hpat'),
                        'no_data' => __('אין נתונים להצגה', 'hpat'),
                        'error' => __('אירעה שגיאה', 'hpat'),
                        'drag_to_timeline' => __('גרור לציר הזמן', 'hpat'),
                        'choose_shape' => __('בחר צורה:', 'hpat'),
                        'choose_color' => __('בחר צבע:', 'hpat'),
                        'square' => __('ריבוע - מערך שיעור', 'hpat'),
                        'circle' => __('עיגול - פעילות אינטראקטיבית', 'hpat'),
                        'triangle' => __('משולש - מדיה', 'hpat'),
                        'star' => __('כוכב - הערכה', 'hpat')
                    ]
                ]
            );
        }

        // Create demo timeline data
        $demo_data = $this->get_demo_timeline_data($atts);

        $output = '<div class="hpat-annual-timeline-wrapper" data-timeline-id="demo">';
        $output .= '<div class="hpat-timeline-header">';
        $output .= '<h2 class="hpat-timeline-title">' . esc_html($atts['group_id']) . ' - שנת ' . esc_html($atts['academic_year']) . '</h2>';
        if ($atts['show_controls'] === 'true') {
            $output .= '<div class="hpat-timeline-controls">';
            $output .= '<button class="hpat-zoom-in" title="הגדל">🔍+</button>';
            $output .= '<button class="hpat-zoom-out" title="הקטן">🔍-</button>';
            $output .= '<button class="hpat-fit-to-screen" title="התאם למסך">📐</button>';
            $output .= '</div>';
        }
        $output .= '</div>';

        $output .= '<div class="hpat-timeline-container" style="height: ' . intval($atts['height']) . 'px;">';
        $output .= '<div id="hpat-timeline-visualization"></div>';
        $output .= '</div>';

        // Legend
        $output .= '<div class="hpat-timeline-legend">';
        $output .= '<h4>מקרא נושאים:</h4>';
        $output .= '<div class="hpat-legend-items">';
        foreach ($demo_data['topics'] as $topic) {
            $output .= '<div class="hpat-legend-item">';
            $output .= '<span class="hpat-legend-color" style="background-color: ' . esc_attr($topic['color']) . ';"></span>';
            $output .= '<span class="hpat-legend-text">' . esc_html($topic['title']) . '</span>';
            $output .= '</div>';
        }
        $output .= '</div>';
        $output .= '</div>';

        // Shapes legend
        $output .= '<div class="hpat-shapes-legend">';
        $output .= '<h4>מקרא צורות:</h4>';
        $output .= '<div class="hpat-shapes-items">';
        $output .= '<div class="hpat-shape-item"><span class="hpat-shape-icon hpat-shape-square">■</span><span class="hpat-shape-text">ריבוע - מערך שיעור</span></div>';
        $output .= '<div class="hpat-shape-item"><span class="hpat-shape-icon hpat-shape-circle">●</span><span class="hpat-shape-text">עיגול - פעילות אינטראקטיבית</span></div>';
        $output .= '<div class="hpat-shape-item"><span class="hpat-shape-icon hpat-shape-triangle">▲</span><span class="hpat-shape-text">משולש - מדיה</span></div>';
        $output .= '<div class="hpat-shape-item"><span class="hpat-shape-icon hpat-shape-star">★</span><span class="hpat-shape-text">כוכב - הערכה</span></div>';
        $output .= '</div>';
        $output .= '</div>';

        // Search panel
        if ($atts['show_search'] === 'true') {
            $output .= '<div class="hpat-search-panel">';
            $output .= '<div class="hpat-search-header"><h3>חיפוש פריטים להוספה לציר הזמן</h3></div>';
            $output .= '<div class="hpat-search-controls">';
            $output .= '<input type="text" id="hpat-search-input" placeholder="חפש פריטים..." class="hpat-search-input">';
            $output .= '<button id="hpat-search-button" class="hpat-search-button">חפש</button>';
            $output .= '</div>';
            $output .= '<div id="hpat-search-results" class="hpat-search-results"></div>';
            $output .= '</div>';
        }

        // Hidden demo data
        $output .= '<script type="application/json" id="hpat-timeline-data">' . json_encode($demo_data) . '</script>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Get inline CSS as fallback
     */
    private function get_inline_css() {
        return '
        .hpat-annual-timeline-wrapper {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            direction: rtl;
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .hpat-timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .hpat-timeline-title {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .hpat-timeline-container {
            position: relative;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            height: 400px;
        }

        .hpat-timeline-legend {
            padding: 15px 20px;
            background: #fff;
            border-bottom: 1px solid #dee2e6;
        }

        .hpat-legend-items {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .hpat-legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .hpat-legend-color {
            width: 16px;
            height: 16px;
            border-radius: 3px;
            border: 1px solid rgba(0,0,0,0.1);
        }
        ';
    }

    /**
     * Get inline JS as fallback
     */
    private function get_inline_js() {
        return '
        (function($) {
            "use strict";

            class HPAT_Timeline {
                constructor(container) {
                    this.container = $(container);
                    this.timeline = null;
                    this.init();
                }

                init() {
                    this.loadTimelineData();
                    this.bindEvents();
                }

                loadTimelineData() {
                    const self = this;
                    const timelineData = this.container.find("#hpat-timeline-data");

                    if (timelineData.length > 0) {
                        try {
                            this.timelineData = JSON.parse(timelineData.html());
                            this.renderTimeline();
                        } catch(e) {
                            console.error("Error parsing timeline data:", e);
                        }
                    }
                }

                renderTimeline() {
                    const self = this;
                    const container = this.container.find(".hpat-timeline-container")[0];

                    if (!container || !this.timelineData) {
                        return;
                    }

                    // Create simple visualization placeholder
                    $(container).html("<div style=\"padding: 20px; text-align: center; color: #666;\">טוען ציר זמן...<br><small>אם אתה רואה הודעה זו, יש בעיה בטעינת הספריות</small></div>");
                }

                bindEvents() {
                    // Basic event binding
                    this.container.on("click", ".hpat-zoom-in", function() {
                        console.log("Zoom in clicked");
                    });

                    this.container.on("click", ".hpat-zoom-out", function() {
                        console.log("Zoom out clicked");
                    });
                }
            }

            // Initialize timelines when document is ready
            $(document).ready(function() {
                $(".hpat-annual-timeline-wrapper").each(function() {
                    new HPAT_Timeline(this);
                });
            });
        })(jQuery);
        ';
    }

    /**
     * Creates demo timeline data for testing
     */
    private function get_demo_timeline_data($atts) {
        $current_year = date('Y');

        return [
            'id' => 'demo',
            'group_name' => $atts['group_id'],
            'academic_year' => $atts['academic_year'],
            'topics' => [
                [
                    'id' => 1,
                    'title' => 'יסודות ומבוא',
                    'start_date' => $current_year . '-09-01',
                    'end_date' => $current_year . '-10-31',
                    'color' => '#3498db',
                    'position' => 1
                ],
                [
                    'id' => 2,
                    'title' => 'פיתוח מיומנויות',
                    'start_date' => $current_year . '-11-01',
                    'end_date' => $current_year . '-12-31',
                    'color' => '#e74c3c',
                    'position' => 2
                ],
                [
                    'id' => 3,
                    'title' => 'עמקה ויישום',
                    'start_date' => ($current_year + 1) . '-01-01',
                    'end_date' => ($current_year + 1) . '-03-31',
                    'color' => '#2ecc71',
                    'position' => 3
                ],
                [
                    'id' => 4,
                    'title' => 'סיכום והערכה',
                    'start_date' => ($current_year + 1) . '-04-01',
                    'end_date' => ($current_year + 1) . '-06-30',
                    'color' => '#f39c12',
                    'position' => 4
                ]
            ],
            'items' => [
                [
                    'id' => 1,
                    'timeline_id' => 'demo',
                    'topic_id' => 1,
                    'post_id' => 1,
                    'post_title' => 'שיעור מבוא - מה זה למידה?',
                    'item_date' => $current_year . '-09-05',
                    'lane' => 0,
                    'item_shape' => 'square',
                    'item_color' => '',
                    'topic_title' => 'יסודות ומבוא',
                    'topic_color' => '#3498db'
                ],
                [
                    'id' => 2,
                    'timeline_id' => 'demo',
                    'topic_id' => 1,
                    'post_id' => 2,
                    'post_title' => 'פעילות קבוצתית - הכרת חברים',
                    'item_date' => $current_year . '-09-12',
                    'lane' => 1,
                    'item_shape' => 'circle',
                    'item_color' => '',
                    'topic_title' => 'יסודות ומבוא',
                    'topic_color' => '#3498db'
                ],
                [
                    'id' => 3,
                    'timeline_id' => 'demo',
                    'topic_id' => 2,
                    'post_id' => 3,
                    'post_title' => 'סדנה - כתיבה יוצרת',
                    'item_date' => $current_year . '-11-15',
                    'lane' => 2,
                    'item_shape' => 'triangle',
                    'item_color' => '',
                    'topic_title' => 'פיתוח מיומנויות',
                    'topic_color' => '#e74c3c'
                ],
                [
                    'id' => 4,
                    'timeline_id' => 'demo',
                    'topic_id' => 3,
                    'post_id' => 4,
                    'post_title' => 'פרויקט גמר - יצירה מקורית',
                    'item_date' => ($current_year + 1) . '-02-20',
                    'lane' => 0,
                    'item_shape' => 'star',
                    'item_color' => '',
                    'topic_title' => 'עמקה ויישום',
                    'topic_color' => '#2ecc71'
                ],
                [
                    'id' => 5,
                    'timeline_id' => 'demo',
                    'topic_id' => 4,
                    'post_id' => 5,
                    'post_title' => 'מבחן סיום ומשוב',
                    'item_date' => ($current_year + 1) . '-06-15',
                    'lane' => 3,
                    'item_shape' => 'star',
                    'item_color' => '',
                    'topic_title' => 'סיכום והערכה',
                    'topic_color' => '#f39c12'
                ]
            ]
        ];
    }
}
