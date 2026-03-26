<?php
// מניעת גישה ישירה
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * מחלקת ניהול עבור היבואן.
 * מטפלת בתפריט ניהול, הצגת העמודים, וטעינת סקריפטים.
 */
class ACF_CSV_Importer_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_acf_csv_importer_upload_csv', array( $this, 'handle_csv_upload' ) );
    }

    /**
     * הוספת עמוד ניהול לתפריט
     */
    public function add_admin_menu() {
        add_management_page(
            __( 'CSV Import with ACF', 'acf-csv-importer' ),
            __( 'CSV Import with ACF', 'acf-csv-importer' ),
            'manage_options', // הרשאה נדרשת
            'acf-csv-importer',
            array( $this, 'render_importer_page' )
        );
    }

    /**
     * טעינת סקריפטים וסגנונות
     */
    public function enqueue_scripts( $hook ) {
        if ( 'tools_page_acf-csv-importer' !== $hook ) {
            return;
        }

        wp_enqueue_style( 'acf-csv-importer-style', ACF_CSV_IMPORTER_PLUGIN_URL . 'assets/css/importer-style.css', array(), ACF_CSV_IMPORTER_VERSION );
        wp_enqueue_script( 'acf-csv-importer-script', ACF_CSV_IMPORTER_PLUGIN_URL . 'assets/js/importer.js', array( 'jquery' ), ACF_CSV_IMPORTER_VERSION, true );

        wp_localize_script( 'acf-csv-importer-script', 'acf_csv_importer', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'acf_csv_importer_nonce' ),
            'i18n'     => array(
                'uploading'        => __( 'מעלה קובץ...', 'acf-csv-importer' ),
                'processing'       => __( 'מעבד נתונים... נא לא לסגור את החלון.', 'acf-csv-importer' ),
                'import_complete'  => __( 'הייבוא הושלם!', 'acf-csv-importer' ),
                'error_processing' => __( 'אירעה שגיאה. נא לנסות שוב.', 'acf-csv-importer' ),
            ),
        ));
    }

    /**
     * הצגת עמוד היבואן
     */
    public function render_importer_page() {
        ?>
        <div class="wrap acf-csv-importer-wrap">
            <h1><?php _e( 'ייבוא CSV עם שדות ACF', 'acf-csv-importer' ); ?></h1>
            
            <!-- שלב 1: העלאת קובץ -->
            <div id="importer-step-1" class="importer-step">
                <h2><?php _e( 'שלב 1: העלאת קובץ CSV', 'acf-csv-importer' ); ?></h2>
                <form id="csv-upload-form" method="post" enctype="multipart/form-data">
                    <p>
                        <label for="csv_file"><?php _e( 'בחר קובץ CSV להעלאה:', 'acf-csv-importer' ); ?></label>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                    </p>
                    <p class="submit">
                        <?php wp_nonce_field( 'csv_upload_nonce', 'csv_upload_nonce_field' ); ?>
                        <input type="submit" class="button button-primary" value="<?php _e( 'העלה והמשך', 'acf-csv-importer' ); ?>">
                    </p>
                </form>
            </div>

            <!-- שלב 2: מיפוי שדות -->
            <div id="importer-step-2" class="importer-step" style="display: none;">
                <h2><?php _e( 'שלב 2: מיפוי עמודות לשדות', 'acf-csv-importer' ); ?></h2>
                <form id="csv-mapping-form" method="post">
                    <input type="hidden" id="uploaded-file-path" name="uploaded_file_path" value="">
                    <table class="widefat fixed">
                        <thead>
                            <tr>
                                <th><?php _e( 'עמודה ב-CSV', 'acf-csv-importer' ); ?></th>
                                <th><?php _e( 'מפה לשדה', 'acf-csv-importer' ); ?></th>
                                <th><?php _e( 'תצוגה מקדימה (שורה ראשונה)', 'acf-csv-importer' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="csv-mapping-table-body"></tbody>
                    </table>
                     <p>
                        <label for="post_type_selector"><?php _e( 'יבא לסוג תוכן:', 'acf-csv-importer' ); ?></label>
                        <select id="post_type_selector" name="post_type">
                            <?php
                            $post_types = get_post_types( array( 'public' => true ), 'objects' );
                            foreach ( $post_types as $post_type ) {
                                echo '<option value="' . esc_attr( $post_type->name ) . '">' . esc_html( $post_type->labels->singular_name ) . '</option>';
                            }
                            ?>
                        </select>
                    </p>
                    <h3 style="margin-top:1.5em;"><?php _e( 'תורם במערכת וקרדיט טקסטואלי', 'acf-csv-importer' ); ?></h3>
                    <p class="description" style="max-width:60em;">
                        <?php _e( '«מחבר הפוסט» בוורדפרס נקבע לפי משתמש אמיתי במערכת (מופיע נכון בתבנית ובלוח הבקרה). הקרדיט הטקסטואלי הוא ערך נפרד מהקובץ — למשל משפט קרדיט בתוכן — ונשמר לשדה שתבחרו.', 'acf-csv-importer' ); ?>
                    </p>
                    <fieldset class="acf-csv-importer-author-mode" style="border:1px solid #ccd0d4;padding:12px;max-width:60em;">
                        <legend><strong><?php _e( 'מי יהיה מחבר הפוסט (מהמערכת)?', 'acf-csv-importer' ); ?></strong></legend>
                        <p>
                            <label><input type="radio" name="post_author_mode" value="fixed" id="post_author_mode_fixed"> <?php _e( 'משתמש קבוע לכל השורות (בחירה מהרשימה — השם המדויק מהאתר)', 'acf-csv-importer' ); ?></label>
                        </p>
                        <p class="acf-csv-indent" style="margin-right:1.5em;">
                            <label for="post_author_fixed_id"><?php _e( 'משתמש:', 'acf-csv-importer' ); ?></label><br>
                            <select id="post_author_fixed_id" class="regular-text" style="max-width:100%;">
                                <option value="0"><?php _e( '— בחרו משתמש —', 'acf-csv-importer' ); ?></option>
                                <?php
                                $author_users = get_users(
                                    array(
                                        'capability' => 'edit_posts',
                                        'orderby'    => 'display_name',
                                        'order'      => 'ASC',
                                    )
                                );
                                foreach ( $author_users as $u ) {
                                    printf(
                                        '<option value="%1$s">%2$s | %3$s | %4$s</option>',
                                        esc_attr( (string) $u->ID ),
                                        esc_html( $u->display_name ),
                                        esc_html( $u->user_login ),
                                        esc_html( $u->user_email )
                                    );
                                }
                                ?>
                            </select>
                        </p>
                        <p>
                            <label><input type="radio" name="post_author_mode" value="csv_login" id="post_author_mode_csv_login"> <?php _e( 'לפי עמודה בקובץ — רק אימייל או שם משתמש (ללא התאמה לפי שם תצוגה מהגיליון)', 'acf-csv-importer' ); ?></label>
                        </p>
                        <p class="acf-csv-indent" style="margin-right:1.5em;">
                            <label for="post_author_csv_header"><?php _e( 'עמודת זיהוי ב־CSV:', 'acf-csv-importer' ); ?></label><br>
                            <select id="post_author_csv_header" class="regular-text" style="max-width:100%;" disabled>
                                <option value=""><?php _e( '— לאחר העלאה ימולא מהכותרות —', 'acf-csv-importer' ); ?></option>
                            </select>
                        </p>
                        <p>
                            <label><input type="radio" name="post_author_mode" value="map" id="post_author_mode_map" checked> <?php _e( 'לפי מיפוי בטבלה למטה (שדה «מחבר») — כולל זיהוי גמיש לפי שם תצוגה אם צריך', 'acf-csv-importer' ); ?></label>
                        </p>
                        <p>
                            <label for="post_author_fallback_id"><?php _e( 'גיבוי: אם לא נקבע מחבר או שהזיהוי נכשל:', 'acf-csv-importer' ); ?></label><br>
                            <select id="post_author_fallback_id" class="regular-text" style="max-width:100%;">
                                <option value="0"><?php _e( '— ללא גיבוי —', 'acf-csv-importer' ); ?></option>
                                <?php
                                foreach ( $author_users as $u ) {
                                    printf(
                                        '<option value="%1$s">%2$s (%3$s)</option>',
                                        esc_attr( (string) $u->ID ),
                                        esc_html( $u->display_name ),
                                        esc_html( $u->user_login )
                                    );
                                }
                                ?>
                            </select>
                        </p>
                    </fieldset>
                    <fieldset style="border:1px solid #ccd0d4;padding:12px;max-width:60em;margin-top:1em;">
                        <legend><strong><?php _e( 'קרדיט טקסטואלי (מעמודת CSV לשדה יעד)', 'acf-csv-importer' ); ?></strong></legend>
                        <p>
                            <label for="credit_text_csv_header"><?php _e( 'עמודת מקור בקובץ:', 'acf-csv-importer' ); ?></label><br>
                            <select id="credit_text_csv_header" class="regular-text" style="max-width:100%;" disabled>
                                <option value=""><?php _e( '— לאחר העלאה —', 'acf-csv-importer' ); ?></option>
                            </select>
                        </p>
                        <p>
                            <label for="credit_text_target"><?php _e( 'שמירה ב:', 'acf-csv-importer' ); ?></label><br>
                            <select id="credit_text_target" class="regular-text" style="max-width:100%;">
                                <?php foreach ( acf_csv_importer_credit_target_options() as $val => $lab ) : ?>
                                    <option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $lab ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                    </fieldset>
                    <p class="submit">
                        <input type="submit" class="button button-primary" value="<?php _e( 'התחל ייבוא', 'acf-csv-importer' ); ?>">
                    </p>
                </form>
            </div>

            <!-- שלב 3: ייבוא -->
            <div id="importer-step-3" class="importer-step" style="display: none;">
                <h2><?php _e( 'שלב 3: מבצע ייבוא', 'acf-csv-importer' ); ?></h2>
                <div class="import-progress">
                    <div class="progress-bar-wrapper"><div id="progress-bar" class="progress-bar"></div></div>
                    <p id="progress-status"><?php _e( 'מתכונן לייבוא...', 'acf-csv-importer' ); ?></p>
                </div>
                <div id="import-results" style="display: none;">
                    <h3><?php _e( 'סיכום ייבוא', 'acf-csv-importer' ); ?></h3>
                    <p id="results-summary"></p>
                    <div id="error-log-wrapper" style="display:none;">
                        <h4><?php _e( 'יומן שגיאות:', 'acf-csv-importer' ); ?></h4>
                        <ul id="error-log"></ul>
                    </div>
                    <button class="button" onclick="location.reload();"><?php _e( 'התחל ייבוא חדש', 'acf-csv-importer' ); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * אחזור שדות זמינים למיפוי
     */
    private function get_mapping_fields() {
        $fields = [
            'core' => [
                'label'   => __( 'שדות ליבה', 'acf-csv-importer' ),
                'options' => [
                    'post_title'   => __( 'כותרת', 'acf-csv-importer' ),
                    'post_content' => __( 'תוכן', 'acf-csv-importer' ),
                    'post_excerpt' => __( 'תקציר', 'acf-csv-importer' ),
                    'post_date'    => __( 'תאריך פרסום', 'acf-csv-importer' ),
                    'post_status'  => __( 'סטטוס', 'acf-csv-importer' ),
                    'post_author'  => __( 'מחבר (רק במצב «לפי מיפוי בטבלה»: מזהה / אימייל / לוגין / שם תצוגה)', 'acf-csv-importer' ),
                    'post_thumbnail' => __( 'תמונה ראשית (קישור)', 'acf-csv-importer' ),
                ],
            ],
            'taxonomy' => [
                'label' => __( 'טקסונומיות', 'acf-csv-importer' ),
                'options' => [],
            ],
            'acf' => [
                'label' => __( 'שדות ACF', 'acf-csv-importer' ),
                'options' => [],
            ],
        ];
        
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        foreach ($taxonomies as $tax) {
            $fields['taxonomy']['options']['tax_' . $tax->name] = $tax->labels->name;
        }

        if ( function_exists('acf_get_field_groups') ) {
            $field_groups = acf_get_field_groups();
            foreach ( $field_groups as $group ) {
                $acf_fields = acf_get_fields( $group['ID'] );
                if (is_array($acf_fields)) {
                    foreach ( $acf_fields as $field ) {
                        $fields['acf']['options'][ $field['name'] ] = $field['label'] . ' (' . $field['type'] . ')';
                    }
                }
            }
        }
        
        return $fields;
    }

    /**
     * טיפול בהעלאת קובץ CSV דרך AJAX
     */
    public function handle_csv_upload() {
        check_ajax_referer( 'csv_upload_nonce', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'אין הרשאות.', 'acf-csv-importer' ) ] );
        }

        if ( empty( $_FILES['csv_file'] ) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( [ 'message' => __( 'שגיאת העלאת קובץ.', 'acf-csv-importer' ) ] );
        }
        
        $file = $_FILES['csv_file'];
        $file_type = wp_check_filetype( $file['name'], [ 'csv' => 'text/csv' ] );

        if ( 'csv' !== $file_type['ext'] ) {
            wp_send_json_error( [ 'message' => __( 'סוג קובץ לא חוקי. יש להעלות קובץ csv.', 'acf-csv-importer' ) ] );
        }
        
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/acf-importer-' . time() . '.csv';

        if ( ! move_uploaded_file( $file['tmp_name'], $file_path ) ) {
            wp_send_json_error( [ 'message' => __( 'כשל בהעברת הקובץ.', 'acf-csv-importer' ) ] );
        }

        $handle = fopen( $file_path, 'r' );
        if ( $handle === false ) {
            wp_send_json_error( [ 'message' => __( 'כשל בפתיחת קובץ ה-CSV.', 'acf-csv-importer' ) ] );
        }

        $headers = fgetcsv( $handle );
        $first_row = fgetcsv( $handle );
        fclose( $handle );

        if ( empty( $headers ) ) {
            wp_send_json_error( [ 'message' => __( 'קובץ CSV ריק או לא תקין.', 'acf-csv-importer' ) ] );
        }
        
        wp_send_json_success(
            array(
                'file_path'       => $file_path,
                'headers'         => $headers,
                'first_row'       => $first_row,
                'mapping_fields'  => $this->get_mapping_fields(),
                'credit_targets'  => acf_csv_importer_credit_target_options(),
            )
        );
    }
}
