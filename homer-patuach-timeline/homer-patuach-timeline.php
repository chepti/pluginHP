<?php
/**
 * Plugin Name:       Homer Patuach - Timeline
 * Plugin URI:        https://homerpatuach.com/
 * Description:       Adds a dynamic timeline view for organizing content by subject and time period.
 * Version:           1.0.0
 * Author:            Chepti
 * Author URI:        https://chepti.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       homer-patuach-timeline
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) exit;

define('HPT_VERSION', '1.0.0');
define('HPT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HPT_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Register the Timeline taxonomy.
 * This represents a study timeline for a specific subject and class combination.
 */
function hpt_register_timeline_taxonomy() {
    $labels = array(
        'name'              => 'צירי זמן',
        'singular_name'     => 'ציר זמן',
        'search_items'      => 'חיפוש צירי זמן',
        'all_items'         => 'כל צירי הזמן',
        'edit_item'         => 'עריכת ציר זמן',
        'update_item'       => 'עדכון ציר זמן',
        'add_new_item'      => 'הוספת ציר זמן חדש',
        'new_item_name'     => 'שם ציר זמן חדש',
        'menu_name'         => 'צירי זמן',
    );

    $args = array(
        'hierarchical'      => false,
        'labels'            => $labels,
        'show_ui'          => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'timeline'),
    );

    register_taxonomy('timeline', array('post'), $args);

    // רישום מטא-דאטה לציר
    register_term_meta('timeline', 'subject_term_id', array(
        'type'          => 'integer',
        'description'   => 'מזהה תחום דעת',
        'single'        => true,
        'show_in_rest'  => true,
    ));

    register_term_meta('timeline', 'class_term_id', array(
        'type'          => 'integer',
        'description'   => 'מזהה שכבת גיל',
        'single'        => true,
        'show_in_rest'  => true,
    ));
}
add_action('init', 'hpt_register_timeline_taxonomy');

/**
 * Register the Timeline Topic taxonomy.
 * These are the sections that divide the timeline.
 */
function hpt_register_timeline_topic_taxonomy() {
    $labels = array(
        'name'              => 'נושאים בציר',
        'singular_name'     => 'נושא בציר',
        'search_items'      => 'חיפוש נושאים',
        'all_items'         => 'כל הנושאים',
        'edit_item'         => 'עריכת נושא',
        'update_item'       => 'עדכון נושא',
        'add_new_item'      => 'הוספת נושא חדש',
        'new_item_name'     => 'שם נושא חדש',
        'menu_name'         => 'נושאים בציר',
    );

    $args = array(
        'hierarchical'      => false,
        'labels'            => $labels,
        'show_ui'          => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'timeline-topic'),
    );

    register_taxonomy('timeline_topic', array('post', 'timeline_item'), $args);

    // רישום מטא-דאטה לנושא
    register_term_meta('timeline_topic', 'timeline_term_id', array(
        'type'          => 'integer',
        'description'   => 'מזהה ציר זמן',
        'single'        => true,
        'show_in_rest'  => true,
    ));

    register_term_meta('timeline_topic', 'color', array(
        'type'          => 'string',
        'description'   => 'צבע הנושא',
        'single'        => true,
        'show_in_rest'  => true,
        'default'       => '#3498db',
    ));

    register_term_meta('timeline_topic', 'position', array(
        'type'          => 'number',
        'description'   => 'מיקום התחלה (0-100)',
        'single'        => true,
        'show_in_rest'  => true,
        'default'       => 0,
    ));

    register_term_meta('timeline_topic', 'length', array(
        'type'          => 'number',
        'description'   => 'אורך/משך (0-100)',
        'single'        => true,
        'show_in_rest'  => true,
        'default'       => 10,
    ));
}
add_action('init', 'hpt_register_timeline_topic_taxonomy');

/**
 * Register meta fields for timeline pins (post metadata).
 */
function hpt_register_pin_meta() {
    register_post_meta('post', 'timeline_pin_type', array(
        'type'          => 'string',
        'description'   => 'סוג הנעיצה (square/circle/triangle/star)',
        'single'        => true,
        'show_in_rest'  => true,
    ));

    register_post_meta('post', 'timeline_pin_color', array(
        'type'          => 'string',
        'description'   => 'צבע הנעיצה',
        'single'        => true,
        'show_in_rest'  => true,
    ));

    register_post_meta('post', 'timeline_pin_position', array(
        'type'          => 'number',
        'description'   => 'מיקום על הציר (0-100)',
        'single'        => true,
        'show_in_rest'  => true,
    ));

    register_post_meta('post', 'timeline_term_id', array(
        'type'          => 'integer',
        'description'   => 'מזהה ציר זמן',
        'single'        => true,
        'show_in_rest'  => true,
    ));

    register_post_meta('post', 'topic_term_id', array(
        'type'          => 'integer',
        'description'   => 'מזהה נושא בציר',
        'single'        => true,
        'show_in_rest'  => true,
    ));

    register_post_meta('post', 'timeline_pin_hidden', array(
        'type'          => 'boolean',
        'description'   => 'האם הנעיצה מוסתרת',
        'single'        => true,
        'show_in_rest'  => true,
        'default'       => false,
    ));
}
add_action('init', 'hpt_register_pin_meta');

/**
 * Add custom fields to the timeline taxonomy term add/edit page.
 */
function hpt_add_timeline_term_fields($term = null) {
    $subject_id = $term ? get_term_meta($term->term_id, 'subject_term_id', true) : '';
    $class_id = $term ? get_term_meta($term->term_id, 'class_term_id', true) : '';

    // Get all subjects
    $subjects = get_terms(array(
        'taxonomy' => 'subject',
        'hide_empty' => false,
    ));

    // Get all classes
    $classes = get_terms(array(
        'taxonomy' => 'class',
        'hide_empty' => false,
    ));
    ?>
    <div class="form-field">
        <label for="subject_term_id">תחום דעת</label>
        <select name="subject_term_id" id="subject_term_id" required>
            <option value="">בחר תחום דעת</option>
            <?php foreach ($subjects as $subject) : ?>
                <option value="<?php echo esc_attr($subject->term_id); ?>" <?php selected($subject_id, $subject->term_id); ?>>
                    <?php echo esc_html($subject->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p>בחר את תחום הדעת עבור ציר זה</p>
    </div>

    <div class="form-field">
        <label for="class_term_id">שכבת גיל</label>
        <select name="class_term_id" id="class_term_id" required>
            <option value="">בחר שכבת גיל</option>
            <?php foreach ($classes as $class) : ?>
                <option value="<?php echo esc_attr($class->term_id); ?>" <?php selected($class_id, $class->term_id); ?>>
                    <?php echo esc_html($class->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p>בחר את שכבת הגיל עבור ציר זה</p>
    </div>
    <?php
}
add_action('timeline_add_form_fields', 'hpt_add_timeline_term_fields');
add_action('timeline_edit_form_fields', 'hpt_add_timeline_term_fields');

/**
 * Save the timeline taxonomy term meta.
 */
function hpt_save_timeline_term_meta($term_id) {
    if (isset($_POST['subject_term_id'])) {
        update_term_meta($term_id, 'subject_term_id', absint($_POST['subject_term_id']));
    }
    if (isset($_POST['class_term_id'])) {
        update_term_meta($term_id, 'class_term_id', absint($_POST['class_term_id']));
    }
}
add_action('created_timeline', 'hpt_save_timeline_term_meta');
add_action('edited_timeline', 'hpt_save_timeline_term_meta');

/**
 * Add custom fields to the timeline_topic taxonomy term add/edit page.
 */
function hpt_add_topic_term_fields($term = null) {
    $timeline_id = $term ? get_term_meta($term->term_id, 'timeline_term_id', true) : '';
    $color = $term ? get_term_meta($term->term_id, 'color', true) : '#3498db';
    $position = $term ? get_term_meta($term->term_id, 'position', true) : 0;
    $length = $term ? get_term_meta($term->term_id, 'length', true) : 10;

    // Get all timelines
    $timelines = get_terms(array(
        'taxonomy' => 'timeline',
        'hide_empty' => false,
    ));
    ?>
    <div class="form-field">
        <label for="timeline_term_id">ציר זמן</label>
        <select name="timeline_term_id" id="timeline_term_id" required>
            <option value="">בחר ציר זמן</option>
            <?php foreach ($timelines as $timeline) : 
                $subject_id = get_term_meta($timeline->term_id, 'subject_term_id', true);
                $class_id = get_term_meta($timeline->term_id, 'class_term_id', true);
                $subject = get_term($subject_id, 'subject');
                $class = get_term($class_id, 'class');
                $timeline_name = sprintf('%s - %s', 
                    $subject ? $subject->name : 'לא ידוע',
                    $class ? $class->name : 'לא ידוע'
                );
            ?>
                <option value="<?php echo esc_attr($timeline->term_id); ?>" <?php selected($timeline_id, $timeline->term_id); ?>>
                    <?php echo esc_html($timeline_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p>בחר את ציר הזמן אליו שייך נושא זה</p>
    </div>

    <div class="form-field">
        <label for="color">צבע הנושא</label>
        <input type="color" name="color" id="color" value="<?php echo esc_attr($color); ?>">
        <p>בחר צבע לנושא זה</p>
    </div>

    <div class="form-field">
        <label for="position">מיקום התחלה</label>
        <input type="range" name="position" id="position" min="0" max="100" value="<?php echo esc_attr($position); ?>">
        <output for="position"><?php echo esc_html($position); ?>%</output>
        <p>קבע את נקודת ההתחלה של הנושא על הציר (0-100)</p>
    </div>

    <div class="form-field">
        <label for="length">אורך/משך</label>
        <input type="range" name="length" id="length" min="1" max="100" value="<?php echo esc_attr($length); ?>">
        <output for="length"><?php echo esc_html($length); ?>%</output>
        <p>קבע את אורך/משך הנושא על הציר (1-100)</p>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // עדכון ערכי הטווחים בזמן אמת
        $('input[type="range"]').on('input', function() {
            $(this).next('output').text(this.value + '%');
        });
    });
    </script>
    <?php
}
add_action('timeline_topic_add_form_fields', 'hpt_add_topic_term_fields');
add_action('timeline_topic_edit_form_fields', 'hpt_add_topic_term_fields');

/**
 * Save the timeline_topic taxonomy term meta.
 */
function hpt_save_topic_term_meta($term_id) {
    if (isset($_POST['timeline_term_id'])) {
        update_term_meta($term_id, 'timeline_term_id', absint($_POST['timeline_term_id']));
    }
    if (isset($_POST['color'])) {
        update_term_meta($term_id, 'color', sanitize_hex_color($_POST['color']));
    }
    if (isset($_POST['position'])) {
        $position = max(0, min(100, intval($_POST['position'])));
        update_term_meta($term_id, 'position', $position);
    }
    if (isset($_POST['length'])) {
        $length = max(1, min(100, intval($_POST['length'])));
        update_term_meta($term_id, 'length', $length);
    }
}
add_action('created_timeline_topic', 'hpt_save_topic_term_meta');
add_action('edited_timeline_topic', 'hpt_save_topic_term_meta');

/**
 * Add custom columns to the timeline taxonomy admin list.
 */
function hpt_add_timeline_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        if ($key === 'name') {
            $new_columns[$key] = $value;
            $new_columns['subject'] = 'תחום דעת';
            $new_columns['class'] = 'שכבת גיל';
        } else {
            $new_columns[$key] = $value;
        }
    }
    return $new_columns;
}
add_filter('manage_edit-timeline_columns', 'hpt_add_timeline_columns');

/**
 * Add content to the custom columns in the timeline taxonomy admin list.
 */
function hpt_manage_timeline_columns($out, $column, $term_id) {
    switch ($column) {
        case 'subject':
            $subject_id = get_term_meta($term_id, 'subject_term_id', true);
            if ($subject_id) {
                $subject = get_term($subject_id, 'subject');
                if ($subject && !is_wp_error($subject)) {
                    $out = esc_html($subject->name);
                }
            }
            break;
        case 'class':
            $class_id = get_term_meta($term_id, 'class_term_id', true);
            if ($class_id) {
                $class = get_term($class_id, 'class');
                if ($class && !is_wp_error($class)) {
                    $out = esc_html($class->name);
                }
            }
            break;
    }
    return $out;
}
add_filter('manage_timeline_custom_column', 'hpt_manage_timeline_columns', 10, 3);

/**
 * Add custom columns to the timeline_topic taxonomy admin list.
 */
function hpt_add_topic_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        if ($key === 'name') {
            $new_columns[$key] = $value;
            $new_columns['timeline'] = 'ציר זמן';
            $new_columns['position'] = 'מיקום';
            $new_columns['length'] = 'אורך';
            $new_columns['color'] = 'צבע';
        } else {
            $new_columns[$key] = $value;
        }
    }
    return $new_columns;
}
add_filter('manage_edit-timeline_topic_columns', 'hpt_add_topic_columns');

/**
 * Add content to the custom columns in the timeline_topic taxonomy admin list.
 */
function hpt_manage_topic_columns($out, $column, $term_id) {
    switch ($column) {
        case 'timeline':
            $timeline_id = get_term_meta($term_id, 'timeline_term_id', true);
            if ($timeline_id) {
                $timeline = get_term($timeline_id, 'timeline');
                if ($timeline && !is_wp_error($timeline)) {
                    $subject_id = get_term_meta($timeline->term_id, 'subject_term_id', true);
                    $class_id = get_term_meta($timeline->term_id, 'class_term_id', true);
                    $subject = get_term($subject_id, 'subject');
                    $class = get_term($class_id, 'class');
                    $timeline_name = sprintf('%s - %s', 
                        $subject ? $subject->name : 'לא ידוע',
                        $class ? $class->name : 'לא ידוע'
                    );
                    $out = esc_html($timeline_name);
                }
            }
            break;
        case 'position':
            $position = get_term_meta($term_id, 'position', true);
            $out = $position . '%';
            break;
        case 'length':
            $length = get_term_meta($term_id, 'length', true);
            $out = $length . '%';
            break;
        case 'color':
            $color = get_term_meta($term_id, 'color', true);
            $out = sprintf(
                '<div style="background-color: %s; width: 30px; height: 30px; border-radius: 4px; border: 1px solid #ddd;"></div>',
                esc_attr($color)
            );
            break;
    }
    return $out;
}
add_filter('manage_timeline_topic_custom_column', 'hpt_manage_topic_columns', 10, 3);

/**
 * Register the shortcode for displaying the timeline.
 * Usage: [homer_timeline subject_id="123" class_id="456"]
 */
function hpt_timeline_shortcode($atts) {
    $atts = shortcode_atts(array(
        'subject_id' => 0,
        'class_id' => 0,
    ), $atts, 'homer_timeline');

    // Get the timeline for this subject and class combination
    $timeline = get_terms(array(
        'taxonomy' => 'timeline',
        'hide_empty' => false,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'subject_term_id',
                'value' => $atts['subject_id'],
                'compare' => '=',
            ),
            array(
                'key' => 'class_term_id',
                'value' => $atts['class_id'],
                'compare' => '=',
            ),
        ),
    ));

    if (is_wp_error($timeline) || empty($timeline)) {
        return '<p>לא נמצא ציר זמן מתאים.</p>';
    }

    $timeline = $timeline[0];

    // Get all topics for this timeline
    $topics = get_terms(array(
        'taxonomy' => 'timeline_topic',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key' => 'timeline_term_id',
                'value' => $timeline->term_id,
                'compare' => '=',
            ),
        ),
        'orderby' => 'meta_value_num',
        'meta_key' => 'position',
        'order' => 'ASC',
    ));

    if (is_wp_error($topics)) {
        return '<p>אירעה שגיאה בטעינת הנושאים.</p>';
    }

    // Get subject and class names
    $subject = get_term($atts['subject_id'], 'subject');
    $class = get_term($atts['class_id'], 'class');
    $title = sprintf('%s - %s',
        $subject ? $subject->name : 'לא ידוע',
        $class ? $class->name : 'לא ידוע'
    );

    ob_start();
    ?>
    <div class="hpt-timeline-container" 
         data-timeline-id="<?php echo esc_attr($timeline->term_id); ?>"
         data-subject-id="<?php echo esc_attr($atts['subject_id']); ?>"
         data-class-id="<?php echo esc_attr($atts['class_id']); ?>">
        
        <div class="hpt-timeline-header">
            <h2 class="hpt-timeline-title"><?php echo esc_html($title); ?></h2>
            <div class="hpt-timeline-controls">
                <div class="hpt-zoom-controls">
                    <button class="hpt-zoom-button hpt-zoom-out" title="הקטן תצוגה">-</button>
                    <button class="hpt-zoom-button hpt-zoom-in" title="הגדל תצוגה">+</button>
                </div>
            </div>
        </div>

        <div class="hpt-timeline-scale">
            <?php
            $months = array(
                'ספטמבר', 'אוקטובר', 'נובמבר', 'דצמבר',
                'ינואר', 'פברואר', 'מרץ', 'אפריל', 'מאי', 'יוני'
            );
            foreach ($months as $i => $month) {
                $position = ($i / count($months)) * 100;
                echo sprintf(
                    '<div class="hpt-timeline-scale-marker" style="right: %s%%;">%s</div>',
                    esc_attr($position),
                    esc_html($month)
                );
            }
            ?>
        </div>

        <div class="hpt-timeline-content">
            <div class="hpt-timeline-topics">
                <?php
                foreach ($topics as $topic) {
                    $position = get_term_meta($topic->term_id, 'position', true) ?: 0;
                    $length = get_term_meta($topic->term_id, 'length', true) ?: 10;
                    $color = get_term_meta($topic->term_id, 'color', true) ?: '#3498db';

                    echo sprintf(
                        '<div class="hpt-timeline-topic" data-topic-id="%s" style="right: %s%%; width: %s%%; background-color: %s;">%s</div>',
                        esc_attr($topic->term_id),
                        esc_attr($position),
                        esc_attr($length),
                        esc_attr($color),
                        esc_html($topic->name)
                    );
                }
                ?>
            </div>

            <div class="hpt-timeline-items">
                <?php
                // Get all items (posts) pinned to this timeline
                $items = get_posts(array(
                    'post_type' => 'post',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => 'timeline_term_id',
                            'value' => $timeline->term_id,
                            'compare' => '=',
                        ),
                        array(
                            'key' => 'timeline_pin_hidden',
                            'value' => '1',
                            'compare' => '!=',
                        ),
                    ),
                ));

                foreach ($items as $item) {
                    $position = get_post_meta($item->ID, 'timeline_pin_position', true) ?: 0;
                    $type = get_post_meta($item->ID, 'timeline_pin_type', true) ?: 'square';
                    $color = get_post_meta($item->ID, 'timeline_pin_color', true) ?: '#3498db';
                    $topic_id = get_post_meta($item->ID, 'topic_term_id', true);

                    echo sprintf(
                        '<div class="hpt-timeline-item" data-item-id="%s" data-topic-id="%s" style="right: %s%%;">
                            <div class="hpt-item-line"></div>
                            <div class="hpt-item-shape %s" style="background-color: %s;"></div>
                            <div class="hpt-item-title">%s</div>
                        </div>',
                        esc_attr($item->ID),
                        esc_attr($topic_id),
                        esc_attr($position),
                        esc_attr($type),
                        esc_attr($color),
                        esc_html($item->post_title)
                    );
                }
                ?>
            </div>
        </div>

        <?php if (current_user_can('edit_posts')) : ?>
        <div class="hpt-search-panel">
            <input type="text" class="hpt-search-input" placeholder="חפש פריטים להוספה...">
            <div class="hpt-search-results"></div>
        </div>

        <div class="hpt-pin-dialog">
            <h3>הגדרות נעיצה</h3>
            <div class="hpt-pin-options">
                <label class="hpt-pin-option">
                    <input type="radio" name="pin_type" value="square">
                    <span>מערך או דף עבודה</span>
                </label>
                <label class="hpt-pin-option">
                    <input type="radio" name="pin_type" value="circle">
                    <span>פעילות אינטראקטיבית</span>
                </label>
                <label class="hpt-pin-option">
                    <input type="radio" name="pin_type" value="triangle">
                    <span>מדיה (סרטון, מצגת)</span>
                </label>
                <label class="hpt-pin-option">
                    <input type="radio" name="pin_type" value="star">
                    <span>הערכה</span>
                </label>
            </div>
            <div class="hpt-pin-color">
                <label>צבע הנעיצה</label>
                <input type="color" name="pin_color" value="#3498db">
            </div>
            <div class="hpt-pin-actions">
                <button class="hpt-pin-button hpt-pin-cancel">ביטול</button>
                <button class="hpt-pin-button hpt-pin-save">שמירה</button>
            </div>
        </div>

        <div class="hpt-overlay"></div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('homer_timeline', 'hpt_timeline_shortcode');

/**
 * AJAX handler for searching posts.
 */
function hpt_search_posts_handler() {
    check_ajax_referer('hpt-ajax-nonce', 'nonce');

    $query = sanitize_text_field($_POST['query']);
    $subject_id = intval($_POST['subject_id']);
    $class_id = intval($_POST['class_id']);

    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 10,
        's' => $query,
        'tax_query' => array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'subject',
                'field' => 'term_id',
                'terms' => $subject_id,
            ),
            array(
                'taxonomy' => 'class',
                'field' => 'term_id',
                'terms' => $class_id,
            ),
        ),
    );

    $query = new WP_Query($args);
    $items = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $items[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
            );
        }
    }
    wp_reset_postdata();

    wp_send_json_success($items);
}
add_action('wp_ajax_hpt_search_posts', 'hpt_search_posts_handler');

/**
 * AJAX handler for saving a pin.
 */
function hpt_save_pin_handler() {
    check_ajax_referer('hpt-ajax-nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied');
    }

    $item_id = intval($_POST['item_id']);
    $topic_id = intval($_POST['topic_id']);
    $position = floatval($_POST['position']);
    $type = sanitize_text_field($_POST['type']);
    $color = sanitize_hex_color($_POST['color']);
    $timeline_id = intval($_POST['timeline_id']);

    // Save pin metadata
    update_post_meta($item_id, 'timeline_pin_type', $type);
    update_post_meta($item_id, 'timeline_pin_color', $color);
    update_post_meta($item_id, 'timeline_pin_position', $position);
    update_post_meta($item_id, 'timeline_term_id', $timeline_id);
    update_post_meta($item_id, 'topic_term_id', $topic_id);

    // Get the topic for its name
    $topic = get_term($topic_id, 'timeline_topic');
    if (!is_wp_error($topic)) {
        // Add the topic name as a tag
        wp_set_post_tags($item_id, $topic->name, true);
    }

    wp_send_json_success(array(
        'id' => $item_id,
        'title' => get_the_title($item_id),
        'type' => $type,
        'color' => $color,
        'position' => $position,
        'topic_id' => $topic_id,
    ));
}
add_action('wp_ajax_hpt_save_pin', 'hpt_save_pin_handler');

/**
 * AJAX handler for updating a pin's position.
 */
function hpt_update_pin_position_handler() {
    check_ajax_referer('hpt-ajax-nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied');
    }

    $item_id = intval($_POST['item_id']);
    $topic_id = intval($_POST['topic_id']);
    $position = floatval($_POST['position']);

    // Update pin metadata
    update_post_meta($item_id, 'timeline_pin_position', $position);
    update_post_meta($item_id, 'topic_term_id', $topic_id);

    // Get the old topic tags
    $old_topic_id = get_post_meta($item_id, 'topic_term_id', true);
    if ($old_topic_id) {
        $old_topic = get_term($old_topic_id, 'timeline_topic');
        if (!is_wp_error($old_topic)) {
            wp_remove_object_terms($item_id, $old_topic->name, 'post_tag');
        }
    }

    // Add the new topic as a tag
    $topic = get_term($topic_id, 'timeline_topic');
    if (!is_wp_error($topic)) {
        wp_set_post_tags($item_id, $topic->name, true);
    }

    wp_send_json_success();
}
add_action('wp_ajax_hpt_update_pin_position', 'hpt_update_pin_position_handler');

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'hpt_activate');
register_deactivation_hook(__FILE__, 'hpt_deactivate');

/**
 * Plugin activation hook.
 */
function hpt_activate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation hook.
 */
function hpt_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}






/**
 * Check if user can manage timeline topics.
 */
function hpt_can_manage_timeline_topics($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }

    return (
        user_can($user_id, 'manage_timeline_topics') ||
        user_can($user_id, 'edit_others_timeline_topics') ||
        user_can($user_id, 'edit_timeline_topics')
    );
}

/**
 * Check if user can edit a specific timeline topic.
 */
function hpt_can_edit_timeline_topic($topic_id, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }

    // Admins and editors can edit all topics
    if (user_can($user_id, 'edit_others_timeline_topics')) {
        return true;
    }

    // Get the topic creator
    $topic_creator_id = get_term_meta($topic_id, 'hpt_creator_id', true);

    // Users can edit their own topics if they have the capability
    if ($topic_creator_id == $user_id && user_can($user_id, 'edit_timeline_topics')) {
        return true;
    }

    return false;
}

/**
 * Add creator ID when creating a new timeline topic.
 */
function hpt_add_topic_creator($term_id, $tt_id) {
    $user_id = get_current_user_id();
    if ($user_id) {
        add_term_meta($term_id, 'hpt_creator_id', $user_id, true);
    }
}
add_action('created_timeline_topic', 'hpt_add_topic_creator', 10, 2);

/**
 * Filter timeline topics query to show only those the user can edit.
 */
function hpt_filter_timeline_topics_for_user($pieces, $taxonomies, $args) {
    if (!is_admin() || !in_array('timeline_topic', (array)$taxonomies)) {
        return $pieces;
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        return $pieces;
    }

    // Admins and editors can see all topics
    if (current_user_can('edit_others_timeline_topics')) {
        return $pieces;
    }

    // Authors and contributors can only see their own topics
    global $wpdb;
    $pieces['join'] .= " LEFT JOIN {$wpdb->termmeta} AS creator_meta ON t.term_id = creator_meta.term_id AND creator_meta.meta_key = 'hpt_creator_id'";
    $pieces['where'] .= $wpdb->prepare(" AND (creator_meta.meta_value = %d)", $user_id);

    return $pieces;
}
add_filter('terms_clauses', 'hpt_filter_timeline_topics_for_user', 10, 3);



/**
 * Add custom fields to the timeline topic taxonomy term edit page.
 */
function hpt_add_topic_term_fields($term) {
    $start_date = get_term_meta($term->term_id, 'hpt_start_date', true);
    $end_date = get_term_meta($term->term_id, 'hpt_end_date', true);
    $color = get_term_meta($term->term_id, 'hpt_color', true) ?: '#3498db';
    ?>
    <tr class="form-field">
        <th scope="row">
            <label for="hpt_start_date"><?php _e('תאריך התחלה', 'homer-patuach-timeline'); ?></label>
        </th>
        <td>
            <input type="date" name="hpt_start_date" id="hpt_start_date" value="<?php echo esc_attr($start_date); ?>" required>
            <p class="description"><?php _e('תאריך תחילת הנושא', 'homer-patuach-timeline'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row">
            <label for="hpt_end_date"><?php _e('תאריך סיום', 'homer-patuach-timeline'); ?></label>
        </th>
        <td>
            <input type="date" name="hpt_end_date" id="hpt_end_date" value="<?php echo esc_attr($end_date); ?>" required>
            <p class="description"><?php _e('תאריך סיום הנושא', 'homer-patuach-timeline'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row">
            <label for="hpt_color"><?php _e('צבע הנושא', 'homer-patuach-timeline'); ?></label>
        </th>
        <td>
            <input type="color" name="hpt_color" id="hpt_color" value="<?php echo esc_attr($color); ?>">
            <p class="description"><?php _e('צבע הנושא בציר הזמן', 'homer-patuach-timeline'); ?></p>
        </td>
    </tr>
    <?php
}
add_action('timeline_topic_edit_form_fields', 'hpt_add_topic_term_fields', 10, 2);

/**
 * Add custom fields to the timeline topic taxonomy term add page.
 */
function hpt_add_topic_term_add_fields() {
    ?>
    <div class="form-field">
        <label for="hpt_start_date"><?php _e('תאריך התחלה', 'homer-patuach-timeline'); ?></label>
        <input type="date" name="hpt_start_date" id="hpt_start_date" required>
        <p><?php _e('תאריך תחילת הנושא', 'homer-patuach-timeline'); ?></p>
    </div>
    <div class="form-field">
        <label for="hpt_end_date"><?php _e('תאריך סיום', 'homer-patuach-timeline'); ?></label>
        <input type="date" name="hpt_end_date" id="hpt_end_date" required>
        <p><?php _e('תאריך סיום הנושא', 'homer-patuach-timeline'); ?></p>
    </div>
    <div class="form-field">
        <label for="hpt_color"><?php _e('צבע הנושא', 'homer-patuach-timeline'); ?></label>
        <input type="color" name="hpt_color" id="hpt_color" value="#3498db">
        <p><?php _e('צבע הנושא בציר הזמן', 'homer-patuach-timeline'); ?></p>
    </div>
    <?php
}
add_action('timeline_topic_add_form_fields', 'hpt_add_topic_term_add_fields');

/**
 * Save the custom fields when a timeline topic term is created or edited.
 */
function hpt_save_topic_term_fields($term_id) {
    if (isset($_POST['hpt_start_date'])) {
        update_term_meta($term_id, 'hpt_start_date', sanitize_text_field($_POST['hpt_start_date']));
    }
    if (isset($_POST['hpt_end_date'])) {
        update_term_meta($term_id, 'hpt_end_date', sanitize_text_field($_POST['hpt_end_date']));
    }
    if (isset($_POST['hpt_color'])) {
        update_term_meta($term_id, 'hpt_color', sanitize_hex_color($_POST['hpt_color']));
    }
}
add_action('created_timeline_topic', 'hpt_save_topic_term_fields');
add_action('edited_timeline_topic', 'hpt_save_topic_term_fields');












/**
 * Register the Timeline Item custom post type.
 * This will store the items that appear on the timeline.
 */
function hpt_register_timeline_item_post_type() {
    $labels = array(
        'name'               => _x('פריטי ציר', 'post type general name', 'homer-patuach-timeline'),
        'singular_name'      => _x('פריט ציר', 'post type singular name', 'homer-patuach-timeline'),
        'menu_name'          => _x('ציר זמן', 'admin menu', 'homer-patuach-timeline'),
        'name_admin_bar'     => _x('פריט ציר', 'add new on admin bar', 'homer-patuach-timeline'),
        'add_new'           => _x('הוסף פריט חדש', 'timeline item', 'homer-patuach-timeline'),
        'add_new_item'      => __('הוסף פריט חדש', 'homer-patuach-timeline'),
        'new_item'          => __('פריט חדש', 'homer-patuach-timeline'),
        'edit_item'         => __('ערוך פריט', 'homer-patuach-timeline'),
        'view_item'         => __('צפה בפריט', 'homer-patuach-timeline'),
        'all_items'         => __('כל הפריטים', 'homer-patuach-timeline'),
        'search_items'      => __('חפש פריטים', 'homer-patuach-timeline'),
        'not_found'         => __('לא נמצאו פריטים.', 'homer-patuach-timeline'),
        'not_found_in_trash'=> __('לא נמצאו פריטים בסל המחזור.', 'homer-patuach-timeline')
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'timeline-item'),
        'capability_type'   => 'post',
        'has_archive'       => false,
        'hierarchical'      => false,
        'menu_position'     => 5,
        'menu_icon'         => 'dashicons-clock',
        'supports'          => array('title', 'editor', 'thumbnail'),
        'show_in_rest'      => true, // Enable Gutenberg editor
        'taxonomies'        => array('timeline_topic', 'subject'), // Add our taxonomies
    );

    register_post_type('timeline_item', $args);

    // Register meta fields for timeline items
    register_post_meta('timeline_item', 'hpt_item_type', array(
        'type' => 'string',
        'description' => 'Type of timeline item (square, circle, triangle, star)',
        'single' => true,
        'show_in_rest' => true,
        'default' => 'square',
    ));

    register_post_meta('timeline_item', 'hpt_item_color', array(
        'type' => 'string',
        'description' => 'Color for the item on the timeline',
        'single' => true,
        'show_in_rest' => true,
    ));

    register_post_meta('timeline_item', 'hpt_item_position', array(
        'type' => 'number',
        'description' => 'Position of the item within its topic (0-100)',
        'single' => true,
        'show_in_rest' => true,
        'default' => 0,
    ));

    register_post_meta('timeline_item', 'hpt_item_link', array(
        'type' => 'string',
        'description' => 'Link to the related content',
        'single' => true,
        'show_in_rest' => true,
    ));
}
add_action('init', 'hpt_register_timeline_item_post_type');

/**
 * Add meta boxes to the timeline item edit screen.
 */
function hpt_add_timeline_item_meta_boxes() {
    add_meta_box(
        'hpt_item_details',
        __('פרטי הפריט בציר', 'homer-patuach-timeline'),
        'hpt_render_timeline_item_meta_box',
        'timeline_item',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'hpt_add_timeline_item_meta_boxes');

/**
 * Render the meta box content for timeline items.
 */
function hpt_render_timeline_item_meta_box($post) {
    // Add nonce for security
    wp_nonce_field('hpt_save_timeline_item_meta', 'hpt_timeline_item_meta_nonce');

    // Get current values
    $item_type = get_post_meta($post->ID, 'hpt_item_type', true) ?: 'square';
    $item_color = get_post_meta($post->ID, 'hpt_item_color', true);
    $item_position = get_post_meta($post->ID, 'hpt_item_position', true) ?: 0;
    $item_link = get_post_meta($post->ID, 'hpt_item_link', true);

    ?>
    <div class="hpt-meta-box-content">
        <p>
            <label for="hpt_item_type"><?php _e('סוג הפריט:', 'homer-patuach-timeline'); ?></label><br>
            <select id="hpt_item_type" name="hpt_item_type">
                <option value="square" <?php selected($item_type, 'square'); ?>><?php _e('ריבוע - מערך או דף עבודה', 'homer-patuach-timeline'); ?></option>
                <option value="circle" <?php selected($item_type, 'circle'); ?>><?php _e('עיגול - פעילות אינטראקטיבית', 'homer-patuach-timeline'); ?></option>
                <option value="triangle" <?php selected($item_type, 'triangle'); ?>><?php _e('משולש - מדיה (סרטון, מצגת)', 'homer-patuach-timeline'); ?></option>
                <option value="star" <?php selected($item_type, 'star'); ?>><?php _e('כוכב - הערכה', 'homer-patuach-timeline'); ?></option>
            </select>
        </p>
        <p>
            <label for="hpt_item_color"><?php _e('צבע הפריט:', 'homer-patuach-timeline'); ?></label><br>
            <input type="color" id="hpt_item_color" name="hpt_item_color" value="<?php echo esc_attr($item_color); ?>">
            <span class="description"><?php _e('אם לא נבחר צבע, יירש מצבע הנושא', 'homer-patuach-timeline'); ?></span>
        </p>
        <p>
            <label for="hpt_item_position"><?php _e('מיקום בנושא (0-100):', 'homer-patuach-timeline'); ?></label><br>
            <input type="number" id="hpt_item_position" name="hpt_item_position" value="<?php echo esc_attr($item_position); ?>" min="0" max="100">
        </p>
        <p>
            <label for="hpt_item_link"><?php _e('קישור לתוכן:', 'homer-patuach-timeline'); ?></label><br>
            <input type="url" id="hpt_item_link" name="hpt_item_link" value="<?php echo esc_url($item_link); ?>" class="large-text">
        </p>
    </div>
    <?php
}

/**
 * Save the meta box data for timeline items.
 */
function hpt_save_timeline_item_meta($post_id) {
    // Security checks
    if (!isset($_POST['hpt_timeline_item_meta_nonce']) || 
        !wp_verify_nonce($_POST['hpt_timeline_item_meta_nonce'], 'hpt_save_timeline_item_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save the meta fields
    if (isset($_POST['hpt_item_type'])) {
        update_post_meta($post_id, 'hpt_item_type', sanitize_text_field($_POST['hpt_item_type']));
    }

    if (isset($_POST['hpt_item_color'])) {
        update_post_meta($post_id, 'hpt_item_color', sanitize_hex_color($_POST['hpt_item_color']));
    }

    if (isset($_POST['hpt_item_position'])) {
        $position = intval($_POST['hpt_item_position']);
        if ($position >= 0 && $position <= 100) {
            update_post_meta($post_id, 'hpt_item_position', $position);
        }
    }

    if (isset($_POST['hpt_item_link'])) {
        update_post_meta($post_id, 'hpt_item_link', esc_url_raw($_POST['hpt_item_link']));
    }
}
add_action('save_post_timeline_item', 'hpt_save_timeline_item_meta');

/**
 * Add meta boxes to the timeline topic edit screen.
 */
function hpt_add_topic_meta_boxes() {
    add_meta_box(
        'hpt_topic_details',
        __('פרטי הנושא בציר', 'homer-patuach-timeline'),
        'hpt_render_topic_meta_box',
        'timeline_topic',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'hpt_add_topic_meta_boxes');

/**
 * Render the meta box content.
 */
function hpt_render_topic_meta_box($post) {
    // Add nonce for security
    wp_nonce_field('hpt_save_topic_meta', 'hpt_topic_meta_nonce');

    // Get current values
    $start_date = get_post_meta($post->ID, 'hpt_start_date', true);
    $end_date = get_post_meta($post->ID, 'hpt_end_date', true);
    $color = get_post_meta($post->ID, 'hpt_color', true) ?: '#3498db';

    ?>
    <div class="hpt-meta-box-content">
        <p>
            <label for="hpt_start_date"><?php _e('תאריך התחלה:', 'homer-patuach-timeline'); ?></label><br>
            <input type="date" id="hpt_start_date" name="hpt_start_date" value="<?php echo esc_attr($start_date); ?>" required>
        </p>
        <p>
            <label for="hpt_end_date"><?php _e('תאריך סיום:', 'homer-patuach-timeline'); ?></label><br>
            <input type="date" id="hpt_end_date" name="hpt_end_date" value="<?php echo esc_attr($end_date); ?>" required>
        </p>
        <p>
            <label for="hpt_color"><?php _e('צבע הנושא:', 'homer-patuach-timeline'); ?></label><br>
            <input type="color" id="hpt_color" name="hpt_color" value="<?php echo esc_attr($color); ?>">
        </p>
    </div>
    <?php
}

/**
 * Save the meta box data.
 */
function hpt_save_topic_meta($post_id) {
    // Security checks
    if (!isset($_POST['hpt_topic_meta_nonce']) || 
        !wp_verify_nonce($_POST['hpt_topic_meta_nonce'], 'hpt_save_topic_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save the meta fields
    if (isset($_POST['hpt_start_date'])) {
        update_post_meta($post_id, 'hpt_start_date', sanitize_text_field($_POST['hpt_start_date']));
    }

    if (isset($_POST['hpt_end_date'])) {
        update_post_meta($post_id, 'hpt_end_date', sanitize_text_field($_POST['hpt_end_date']));
    }

    if (isset($_POST['hpt_color'])) {
        update_post_meta($post_id, 'hpt_color', sanitize_hex_color($_POST['hpt_color']));
    }
}
add_action('save_post_timeline_topic', 'hpt_save_topic_meta');

/**
 * Enqueue scripts and styles.
 */
function hpt_enqueue_assets() {
    // Frontend assets
    wp_enqueue_style(
        'hpt-style',
        HPT_PLUGIN_URL . 'assets/css/style.css',
        array(),
        HPT_VERSION
    );

    wp_enqueue_style(
        'hpt-drag-drop-style',
        HPT_PLUGIN_URL . 'assets/css/drag-drop.css',
        array('hpt-style'),
        HPT_VERSION
    );

    wp_enqueue_style(
        'hpt-search-style',
        HPT_PLUGIN_URL . 'assets/css/search.css',
        array('hpt-style', 'hpt-drag-drop-style'),
        HPT_VERSION
    );

    wp_enqueue_style(
        'hpt-animations-style',
        HPT_PLUGIN_URL . 'assets/css/animations.css',
        array('hpt-style', 'hpt-drag-drop-style', 'hpt-search-style'),
        HPT_VERSION
    );

    wp_enqueue_script(
        'hpt-timeline',
        HPT_PLUGIN_URL . 'assets/js/timeline.js',
        array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable'),
        HPT_VERSION,
        true
    );

    wp_enqueue_script(
        'hpt-drag-drop',
        HPT_PLUGIN_URL . 'assets/js/drag-drop.js',
        array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'hpt-timeline'),
        HPT_VERSION,
        true
    );

    wp_enqueue_script(
        'hpt-search',
        HPT_PLUGIN_URL . 'assets/js/search.js',
        array('jquery', 'hpt-timeline', 'hpt-drag-drop'),
        HPT_VERSION,
        true
    );

    wp_enqueue_script(
        'hpt-animations',
        HPT_PLUGIN_URL . 'assets/js/animations.js',
        array('jquery', 'jquery-ui-effects', 'hpt-timeline', 'hpt-drag-drop', 'hpt-search'),
        HPT_VERSION,
        true
    );

    wp_localize_script('hpt-timeline', 'hpt_globals', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('hpt-ajax-nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'hpt_enqueue_assets');

/**
 * Enqueue admin scripts and styles.
 */
function hpt_enqueue_admin_assets($hook) {
    $screen = get_current_screen();

    // Only load on timeline topic taxonomy pages
    if ($screen && $screen->taxonomy === 'timeline_topic') {
        // Admin styles
        wp_enqueue_style(
            'hpt-admin-style',
            HPT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            HPT_VERSION
        );

        // WordPress color picker
        wp_enqueue_style('wp-color-picker');
        
        // jQuery UI datepicker styles
        wp_enqueue_style(
            'jquery-ui-style',
            '//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css',
            array(),
            '1.13.2'
        );

        // Admin scripts
        wp_enqueue_script(
            'hpt-admin',
            HPT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-datepicker'),
            HPT_VERSION,
            true
        );

        wp_localize_script('hpt-admin', 'hpt_admin_globals', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hpt-admin-nonce'),
        ));
    }
}
add_action('admin_enqueue_scripts', 'hpt_enqueue_admin_assets');

/**
 * Register the shortcode for displaying the timeline.
 * Usage: [homer_timeline subject_id="123"]
 */
function hpt_timeline_shortcode($atts) {
    $atts = shortcode_atts(array(
        'subject_id' => 0,
        'title' => '',
        'show_search' => true,
        'show_zoom' => true,
        'show_legend' => true,
    ), $atts, 'homer_timeline');

    ob_start();

    // Verify the subject exists
    $subject = get_term($atts['subject_id'], 'subject');
    if (!$subject || is_wp_error($subject)) {
        return '<p>תחום דעת לא נמצא.</p>';
    }

    // Get all topics for this subject
    $topics = get_terms(array(
        'taxonomy' => 'timeline_topic',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key' => 'hpt_subject_id',
                'value' => $atts['subject_id'],
                'compare' => '=',
            ),
        ),
        'orderby' => 'meta_value',
        'meta_key' => 'hpt_start_date',
        'order' => 'ASC',
    ));

    if (is_wp_error($topics) || empty($topics)) {
        echo '<p>לא נמצאו נושאים בציר הזמן.</p>';
        return ob_get_clean();
    }

    // Get the earliest and latest dates
    $earliest_date = null;
    $latest_date = null;
    foreach ($topics as $topic) {
        $start_date = get_term_meta($topic->term_id, 'hpt_start_date', true);
        $end_date = get_term_meta($topic->term_id, 'hpt_end_date', true);
        
        if (!$earliest_date || strtotime($start_date) < strtotime($earliest_date)) {
            $earliest_date = $start_date;
        }
        if (!$latest_date || strtotime($end_date) > strtotime($latest_date)) {
            $latest_date = $end_date;
        }
    }

    ?>
    <div class="hpt-timeline-grid">
        <?php if ($atts['show_search']) : ?>
        <aside class="hpt-timeline-sidebar">
            <h3><?php echo esc_html($atts['title'] ?: 'ציר זמן'); ?></h3>
            <div class="hpt-timeline-filters">
                <div class="hpt-timeline-filter">
                    <label for="hpt-timeline-view">תצוגה</label>
                    <select id="hpt-timeline-view">
                        <option value="all">הכל</option>
                        <option value="square">מערכי שיעור</option>
                        <option value="circle">פעילויות</option>
                        <option value="triangle">מדיה</option>
                        <option value="star">הערכה</option>
                    </select>
                </div>
                <?php if (current_user_can('edit_posts')) : ?>
                <div class="hpt-timeline-filter">
                    <div class="hpt-search-container">
                        <input type="text" class="hpt-search-input" placeholder="חפש פריטים להוספה...">
                        <div class="hpt-search-results"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </aside>
        <?php endif; ?>

        <div class="hpt-timeline-container" data-subject-id="<?php echo esc_attr($atts['subject_id']); ?>">
            <?php if ($atts['show_zoom']) : ?>
            <div class="hpt-timeline-header">
                <div class="hpt-zoom-controls">
                    <button class="hpt-zoom-in" title="הגדל">+</button>
                    <button class="hpt-zoom-out" title="הקטן">-</button>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="hpt-timeline-wrapper">
                <div class="hpt-timeline">
                    <div class="hpt-timeline-scale">
                        <?php
                        // Add scale markers for months
                        $start = new DateTime($earliest_date);
                        $end = new DateTime($latest_date);
                        $interval = new DateInterval('P1M'); // One month interval
                        $period = new DatePeriod($start, $interval, $end);

                        $total_months = iterator_count($period);
                        $month_width = 100 / $total_months;

                        $i = 0;
                        foreach ($period as $date) {
                            $month_name = $date->format('F');
                            $position = $i * $month_width;
                            echo sprintf(
                                '<div class="hpt-timeline-scale-marker" style="right: %s%%;">%s</div>',
                                esc_attr($position),
                                esc_html($month_name)
                            );
                            $i++;
                        }
                        ?>
                    </div>

                    <div class="hpt-timeline-tracks">
                        <?php
                        foreach ($topics as $topic) {
                            $start_date = get_term_meta($topic->term_id, 'hpt_start_date', true);
                            $end_date = get_term_meta($topic->term_id, 'hpt_end_date', true);
                            $color = get_term_meta($topic->term_id, 'hpt_color', true) ?: '#3498db';
                            
                            // Calculate position and width
                            $start_diff = (strtotime($start_date) - strtotime($earliest_date)) / (strtotime($latest_date) - strtotime($earliest_date)) * 100;
                            $width = (strtotime($end_date) - strtotime($start_date)) / (strtotime($latest_date) - strtotime($earliest_date)) * 100;
                            
                            $style = sprintf(
                                'background-color: %s; right: %s%%; width: %s%%;',
                                esc_attr($color),
                                esc_attr($start_diff),
                                esc_attr($width)
                            );
                            
                            ?>
                            <div class="hpt-timeline-track">
                                <div class="hpt-timeline-topic" 
                                     data-topic-id="<?php echo esc_attr($topic->term_id); ?>"
                                     data-start="<?php echo esc_attr($start_date); ?>"
                                     data-end="<?php echo esc_attr($end_date); ?>"
                                     style="<?php echo $style; ?>">
                                    <div class="hpt-timeline-topic-content">
                                        <h3 class="hpt-timeline-topic-title"><?php echo esc_html($topic->name); ?></h3>
                                        <?php if (current_user_can('edit_term', $topic->term_id)) : ?>
                                            <div class="hpt-timeline-topic-actions">
                                                <button class="hpt-edit-topic" title="ערוך נושא">✎</button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="hpt-timeline-items">
                                        <!-- Items will be loaded here -->
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>

            <?php if ($atts['show_legend']) : ?>
            <div class="hpt-timeline-footer">
                <div class="hpt-timeline-legend">
                    <div class="hpt-timeline-legend-item">
                        <div class="hpt-timeline-legend-icon square"></div>
                        <span>מערך או דף עבודה</span>
                    </div>
                    <div class="hpt-timeline-legend-item">
                        <div class="hpt-timeline-legend-icon circle"></div>
                        <span>פעילות אינטראקטיבית</span>
                    </div>
                    <div class="hpt-timeline-legend-item">
                        <div class="hpt-timeline-legend-icon triangle"></div>
                        <span>מדיה (סרטון, מצגת)</span>
                    </div>
                    <div class="hpt-timeline-legend-item">
                        <div class="hpt-timeline-legend-icon star"></div>
                        <span>הערכה</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('homer_timeline', 'hpt_timeline_shortcode');

/**
 * AJAX handler for searching items to add to the timeline.
 */
function hpt_search_items_ajax_handler() {
    check_ajax_referer('hpt-ajax-nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied');
    }

    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;

    if (empty($search) || empty($subject_id)) {
        wp_send_json_error('Invalid parameters');
    }

    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 10,
        's' => $search,
        'tax_query' => array(
            array(
                'taxonomy' => 'subject',
                'field' => 'term_id',
                'terms' => $subject_id,
            ),
        ),
    );

    $query = new WP_Query($args);
    $items = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $items[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'type' => 'post', // For future expansion to other item types
                'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
            );
        }
    }
    wp_reset_postdata();

    wp_send_json_success($items);
}
add_action('wp_ajax_hpt_search_items', 'hpt_search_items_ajax_handler');

/**
 * AJAX handler for saving an item's position on the timeline.
 */
function hpt_save_item_position_ajax_handler() {
    check_ajax_referer('hpt-ajax-nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied');
    }

    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
    $position = isset($_POST['position']) ? floatval($_POST['position']) : 0;

    if (!$item_id || !$topic_id) {
        wp_send_json_error('Invalid parameters');
    }

    // Save the item's position in the topic
    update_post_meta($item_id, 'hpt_timeline_topic', $topic_id);
    update_post_meta($item_id, 'hpt_timeline_position', $position);

    wp_send_json_success();
}
add_action('wp_ajax_hpt_save_item_position', 'hpt_save_item_position_ajax_handler');
