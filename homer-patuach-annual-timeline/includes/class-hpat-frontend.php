<?php
/**
 * קלאס לניהול הממשק הציבורי של התוסף
 */

class HPAT_Frontend {

    /**
     * קונסטרוקטור
     */
    public function __construct() {
        // לא נדרש פעולות נוספות בקונסטרוקטור
    }

    /**
     * טעינת סגנונות CSS
     */
    public function enqueue_styles() {
        if ($this->should_load_assets()) {
            wp_enqueue_style(
                'hpat-timeline-styles',
                HPAT_PLUGIN_URL . 'assets/css/timeline.css',
                array(),
                HPAT_VERSION
            );
        }
    }

    /**
     * טעינת סקריפטים JavaScript
     */
    public function enqueue_scripts() {
        if ($this->should_load_assets()) {
            // טעינת ספריית Vis.js לציר זמן
            wp_enqueue_script(
                'vis-timeline',
                'https://unpkg.com/vis-timeline@7.7.0/standalone/umd/vis-timeline-graph2d.min.js',
                array(),
                '7.7.0',
                true
            );

            wp_enqueue_script(
                'hpat-timeline-script',
                HPAT_PLUGIN_URL . 'assets/js/timeline.js',
                array('jquery', 'vis-timeline'),
                HPAT_VERSION,
                true
            );

            // העברת נתונים ל-JavaScript
            wp_localize_script(
                'hpat-timeline-script',
                'hpat_ajax',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('hpat_timeline_nonce'),
                    'strings' => array(
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
                    )
                )
            );
        }
    }

    /**
     * בדיקה האם יש לטעון את ה-assets
     */
    private function should_load_assets() {
        // טעינה כאשר יש שורטקוד בציר זמן או כאשר אנחנו בעמוד עם פרמטר timeline_id
        if (has_shortcode(get_post()->post_content ?? '', 'annual_timeline')) {
            return true;
        }

        if (isset($_GET['timeline_id'])) {
            return true;
        }

        return false;
    }

    /**
     * הצגת ציר זמן עם שורטקוד
     */
    public function render_timeline_shortcode($atts) {
        return $this->render_timeline($atts);
    }

    /**
     * הצגת ציר זמן עם שורטקוד (פונקציה ראשית)
     */
    public function render_timeline($atts) {
        // ברירת מחדל לפרמטרים
        $atts = shortcode_atts(
            array(
                'group_id' => '',
                'academic_year' => '',
                'height' => 400,
                'zoom_level' => 30,
                'show_search' => 'true',
                'show_controls' => 'true'
            ),
            $atts,
            'annual_timeline'
        );

        // קבלת מזהה ציר הזמן
        $timeline_id = $this->get_timeline_id($atts);

        if (!$timeline_id) {
            return '<div class="hpat-error">' . __('לא נמצא ציר זמן עבור הקבוצה והשנה שנבחרו', 'hpat') . '</div>';
        }

        // קבלת נתוני ציר הזמן
        $timeline_data = HPAT_Database::get_full_timeline_data($timeline_id);

        if (!$timeline_data) {
            return '<div class="hpat-error">' . __('שגיאה בטעינת נתוני ציר הזמן', 'hpat') . '</div>';
        }

        // יצירת HTML של הציר הזמן
        ob_start();
        $this->render_timeline_html($timeline_data, $atts);
        return ob_get_clean();
    }

    /**
     * קבלת מזהה ציר הזמן
     */
    private function get_timeline_id($atts) {
        // אם יש timeline_id ב-URL, השתמש בו
        if (isset($_GET['timeline_id']) && is_numeric($_GET['timeline_id'])) {
            return intval($_GET['timeline_id']);
        }

        // אם יש group_id ואקדמיק יר בפרמטרים, חפש ציר זמן מתאים
        if (!empty($atts['group_id']) && !empty($atts['academic_year'])) {
            $timeline = HPAT_Database::get_timeline_by_group($atts['group_id'], $atts['academic_year']);
            return $timeline ? $timeline['id'] : false;
        }

        // אם לא צוין, חזור לציר הזמן הראשון
        $timelines = HPAT_Database::get_all_timelines();
        return !empty($timelines) ? $timelines[0]['id'] : false;
    }

    /**
     * הצגת HTML של הציר הזמן
     */
    private function render_timeline_html($timeline_data, $atts) {
        $height = intval($atts['height']);
        $show_search = $atts['show_search'] === 'true';
        $show_controls = $atts['show_controls'] === 'true';

        ?>
        <div class="hpat-annual-timeline-wrapper" data-timeline-id="<?php echo esc_attr($timeline_data['id']); ?>">
            <!-- כותרת הציר הזמן -->
            <div class="hpat-timeline-header">
                <h2 class="hpat-timeline-title">
                    <?php echo esc_html($timeline_data['group_name']); ?> -
                    שנת <?php echo esc_html($timeline_data['academic_year']); ?>
                </h2>
                <?php if ($show_controls): ?>
                <div class="hpat-timeline-controls">
                    <button class="hpat-zoom-in" title="הגדל">🔍+</button>
                    <button class="hpat-zoom-out" title="הקטן">🔍-</button>
                    <button class="hpat-fit-to-screen" title="התאם למסך">📐</button>
                </div>
                <?php endif; ?>
            </div>

            <!-- הציר הזמן הראשי -->
            <div class="hpat-timeline-container" style="height: <?php echo esc_attr($height); ?>px;">
                <div id="hpat-timeline-visualization"></div>
            </div>

            <!-- מקרא הצבעים -->
            <div class="hpat-timeline-legend">
                <h4>מקרא נושאים:</h4>
                <div class="hpat-legend-items">
                    <?php foreach ($timeline_data['topics'] as $topic): ?>
                        <div class="hpat-legend-item">
                            <span class="hpat-legend-color" style="background-color: <?php echo esc_attr($topic['color']); ?>;"></span>
                            <span class="hpat-legend-text"><?php echo esc_html($topic['title']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- מקרא הצורות -->
            <div class="hpat-shapes-legend">
                <h4>מקרא צורות:</h4>
                <div class="hpat-shapes-items">
                    <div class="hpat-shape-item">
                        <span class="hpat-shape-icon hpat-shape-square">■</span>
                        <span class="hpat-shape-text">ריבוע - מערך שיעור</span>
                    </div>
                    <div class="hpat-shape-item">
                        <span class="hpat-shape-icon hpat-shape-circle">●</span>
                        <span class="hpat-shape-text">עיגול - פעילות אינטראקטיבית</span>
                    </div>
                    <div class="hpat-shape-item">
                        <span class="hpat-shape-icon hpat-shape-triangle">▲</span>
                        <span class="hpat-shape-text">משולש - מדיה</span>
                    </div>
                    <div class="hpat-shape-item">
                        <span class="hpat-shape-icon hpat-shape-star">★</span>
                        <span class="hpat-shape-text">כוכב - הערכה</span>
                    </div>
                </div>
            </div>

            <?php if ($show_search): ?>
            <!-- חלונית חיפוש וגרירה -->
            <div class="hpat-search-panel">
                <div class="hpat-search-header">
                    <h3>חיפוש פריטים להוספה לציר הזמן</h3>
                </div>
                <div class="hpat-search-controls">
                    <input type="text" id="hpat-search-input" placeholder="חפש פריטים..." class="hpat-search-input">
                    <button id="hpat-search-button" class="hpat-search-button">חפש</button>
                </div>
                <div id="hpat-search-results" class="hpat-search-results">
                    <!-- תוצאות החיפוש יוצגו כאן -->
                </div>
            </div>
            <?php endif; ?>

            <!-- חלון קופץ לבחירת צורה וצבע -->
            <div id="hpat-item-config-modal" class="hpat-modal" style="display: none;">
                <div class="hpat-modal-content">
                    <div class="hpat-modal-header">
                        <h3>בחר צורה וצבע לפריט</h3>
                        <button class="hpat-modal-close">&times;</button>
                    </div>
                    <div class="hpat-modal-body">
                        <div class="hpat-shape-selector">
                            <label>צורה:</label>
                            <div class="hpat-shape-options">
                                <button class="hpat-shape-option" data-shape="square">■ ריבוע</button>
                                <button class="hpat-shape-option" data-shape="circle">● עיגול</button>
                                <button class="hpat-shape-option" data-shape="triangle">▲ משולש</button>
                                <button class="hpat-shape-option" data-shape="star">★ כוכב</button>
                            </div>
                        </div>
                        <div class="hpat-color-selector">
                            <label>צבע:</label>
                            <div class="hpat-color-options">
                                <button class="hpat-color-option" data-color="" style="background-color: inherit;">ברירת מחדל</button>
                                <button class="hpat-color-option" data-color="#3498db" style="background-color: #3498db;"></button>
                                <button class="hpat-color-option" data-color="#e74c3c" style="background-color: #e74c3c;"></button>
                                <button class="hpat-color-option" data-color="#2ecc71" style="background-color: #2ecc71;"></button>
                                <button class="hpat-color-option" data-color="#f39c12" style="background-color: #f39c12;"></button>
                                <button class="hpat-color-option" data-color="#9b59b6" style="background-color: #9b59b6;"></button>
                            </div>
                        </div>
                    </div>
                    <div class="hpat-modal-footer">
                        <button id="hpat-item-config-save" class="hpat-button-primary">שמור</button>
                        <button id="hpat-item-config-cancel" class="hpat-button-secondary">ביטול</button>
                    </div>
                </div>
            </div>

            <!-- נתונים מוסתרים עבור JavaScript -->
            <script type="application/json" id="hpat-timeline-data">
                <?php echo wp_json_encode($timeline_data); ?>
            </script>
        </div>
        <?php
    }
}
