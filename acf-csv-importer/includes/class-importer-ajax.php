<?php
// מניעת גישה ישירה
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * מחלקה לטיפול בבקשות AJAX של היבואן
 */
class ACF_CSV_Importer_Ajax {

    const BATCH_SIZE = 20; // מספר השורות לעיבוד בכל קריאת AJAX

    public function __construct() {
        add_action( 'wp_ajax_acf_csv_importer_prepare_import', array( $this, 'prepare_import' ) );
        add_action( 'wp_ajax_acf_csv_importer_perform_import', array( $this, 'perform_import' ) );
    }

    /**
     * הכנת הייבוא: ספירת שורות ושמירת נתונים
     */
    public function prepare_import() {
        check_ajax_referer( 'acf_csv_importer_nonce', 'security' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'אין הרשאות.', 'acf-csv-importer' ) ] );
        }

        $file_path = sanitize_text_field( $_POST['file_path'] );
        if ( ! file_exists( $file_path ) ) {
            wp_send_json_error( [ 'message' => __( 'קובץ לא נמצא.', 'acf-csv-importer' ) ] );
        }

        $total_rows = 0;
        if ( ( $handle = fopen( $file_path, 'r' ) ) !== false ) {
            while ( fgetcsv( $handle ) !== false ) {
                $total_rows++;
            }
            fclose( $handle );
        }
        $total_rows--; // הסרת שורת הכותרת מהספירה

        $default_author_id = isset( $_POST['default_author_id'] ) ? absint( wp_unslash( $_POST['default_author_id'] ) ) : 0;
        if ( $default_author_id && ! get_userdata( $default_author_id ) ) {
            $default_author_id = 0;
        }

        $import_data = array(
            'file_path'         => $file_path,
            'mapping'           => $_POST['mapping'],
            'post_type'         => sanitize_text_field( $_POST['post_type'] ),
            'default_author_id' => $default_author_id,
            'total_rows'        => $total_rows,
            'processed'         => 0,
            'errors'            => array(),
        );

        set_transient( 'acf_csv_import_data_' . get_current_user_id(), $import_data, HOUR_IN_SECONDS );

        wp_send_json_success( [ 'total_rows' => $total_rows ] );
    }

    /**
     * ביצוע הייבוא באצוות (batches)
     */
    public function perform_import() {
        check_ajax_referer( 'acf_csv_importer_nonce', 'security' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'אין הרשאות.', 'acf-csv-importer' ) ] );
        }

        $transient_key = 'acf_csv_import_data_' . get_current_user_id();
        $import_data = get_transient( $transient_key );

        if ( false === $import_data ) {
            wp_send_json_error( [ 'message' => __( 'נתוני ייבוא לא נמצאו. ייתכן שפג תוקפם.', 'acf-csv-importer' ) ] );
        }

        $file_path = $import_data['file_path'];
        $mapping = $import_data['mapping'];
        $post_type = $import_data['post_type'];
        $processed_count = $import_data['processed'];

        $handle = fopen( $file_path, 'r' );
        $headers = fgetcsv( $handle ); // קריאת הכותרות
        
        // דלג על שורות שכבר עובדו
        for ( $i = 0; $i < $processed_count; $i++ ) {
            fgetcsv( $handle );
        }
        
        $batch_processed = 0;
        while ( ( $row = fgetcsv( $handle ) ) !== false && $batch_processed < self::BATCH_SIZE ) {
            $this->import_row( $row, $headers, $mapping, $post_type, $import_data['errors'], $import_data );
            $processed_count++;
            $batch_processed++;
        }
        
        fclose( $handle );

        $import_data['processed'] = $processed_count;
        set_transient( $transient_key, $import_data, HOUR_IN_SECONDS );

        if ( $processed_count >= $import_data['total_rows'] ) {
            // סיים ייבוא
            delete_transient( $transient_key );
            unlink($file_path); // מחיקת הקובץ הזמני
            wp_send_json_success( [ 'done' => true, 'processed' => $processed_count, 'errors' => $import_data['errors'] ] );
        } else {
            // המשך לאצווה הבאה
            wp_send_json_success( [ 'done' => false, 'processed' => $processed_count ] );
        }
    }

    /**
     * ייבוא שורה בודדת מה-CSV
     */
    private function import_row( $row, $headers, $mapping, $post_type, &$errors, $import_data ) {
        $row_data = array_combine( $headers, $row );
        $default_author_id = isset( $import_data['default_author_id'] ) ? absint( $import_data['default_author_id'] ) : 0;

        $post_args = [
            'post_type' => $post_type,
            'post_status' => 'draft', // ברירת מחדל
        ];
        $acf_fields = [];
        $taxonomies = [];
        $thumbnail_url = '';
        $post_author_raw = '';

        foreach ( $mapping as $map ) {
            $csv_header = $map['csv_header'];
            $field_id = $map['field_id'];
            $value = isset( $row_data[$csv_header] ) ? $row_data[$csv_header] : '';

            if ( empty($field_id) || $field_id === 'skip' ) continue;

            if ($field_id === 'post_thumbnail') {
                $thumbnail_url = $value;
            } elseif ( $field_id === 'post_author' ) {
                $post_author_raw = $value;
            } elseif ( strpos( $field_id, 'tax_' ) === 0 ) {
                $tax_name = substr( $field_id, 4 );
                $taxonomies[$tax_name] = array_map( 'trim', explode( ',', $value ) );
            } elseif ( array_key_exists( $field_id, acf_get_field_groups() ) || function_exists('acf_get_field') && acf_get_field($field_id) ) {
                $acf_fields[$field_id] = sanitize_text_field( $value );
            } else {
                $post_args[$field_id] = sanitize_text_field( $value );
            }
        }

        $author_id = $this->resolve_post_author_value( $post_author_raw );
        if ( $author_id <= 0 && $default_author_id > 0 && get_userdata( $default_author_id ) ) {
            $author_id = $default_author_id;
        }
        if ( $author_id > 0 ) {
            $post_args['post_author'] = $author_id;
        }
        
        if ( empty( $post_args['post_title'] ) ) {
            $errors[] = __( 'שורה ללא כותרת, דילוג.', 'acf-csv-importer' );
            return;
        }

        $post_id = wp_insert_post( $post_args, true );

        if ( is_wp_error( $post_id ) ) {
            $errors[] = sprintf( __( 'שגיאה ביצירת פוסט "%s": %s', 'acf-csv-importer' ), $post_args['post_title'], $post_id->get_error_message() );
            return;
        }

        // טיפול בתמונה ראשית
        if ( ! empty( $thumbnail_url ) ) {
            $this->set_featured_image_from_url( $post_id, $thumbnail_url, $errors );
        }

        // עדכון שדות ACF
        foreach ( $acf_fields as $key => $val ) {
            update_field( $key, $val, $post_id );
        }

        // שיוך טקסונומיות
        foreach ( $taxonomies as $tax => $terms ) {
            wp_set_object_terms( $post_id, $terms, $tax, true );
        }
    }

    /**
     * הורדת תמונה מקישור והגדרתה כתמונה ראשית
     */
    /**
     * פיענוח מזהה משתמש מטקסט (מזהה, אימייל, לוגין, slug, שם תצוגה — לרוב שם בעמודת קרדיט)
     */
    private function resolve_post_author_value( $value ) {
        if ( ! is_string( $value ) ) {
            return 0;
        }
        $value = trim( wp_strip_all_tags( $value ) );
        if ( $value === '' ) {
            return 0;
        }
        if ( is_numeric( $value ) ) {
            $id = absint( $value );
            return ( $id && get_userdata( $id ) ) ? $id : 0;
        }
        if ( is_email( $value ) ) {
            $user = get_user_by( 'email', $value );
            return $user ? (int) $user->ID : 0;
        }
        $user = get_user_by( 'login', $value );
        if ( $user ) {
            return (int) $user->ID;
        }
        $slug = sanitize_title( $value );
        if ( $slug !== '' ) {
            $user = get_user_by( 'slug', $slug );
            if ( $user ) {
                return (int) $user->ID;
            }
        }
        $users = get_users(
            array(
                'number' => 300,
                'orderby' => 'display_name',
                'fields'  => array( 'ID', 'display_name' ),
            )
        );
        foreach ( $users as $u ) {
            if ( (string) $u->display_name === $value ) {
                return (int) $u->ID;
            }
        }
        if ( function_exists( 'mb_strtolower' ) ) {
            $needle = mb_strtolower( $value, 'UTF-8' );
            foreach ( $users as $u ) {
                if ( mb_strtolower( (string) $u->display_name, 'UTF-8' ) === $needle ) {
                    return (int) $u->ID;
                }
            }
        } else {
            foreach ( $users as $u ) {
                if ( strcasecmp( (string) $u->display_name, $value ) === 0 ) {
                    return (int) $u->ID;
                }
            }
        }
        return 0;
    }

    /**
     * ניקוי URL והמרת נתיב עם תווים שאינם ASCII (למשל עברית בשם קובץ) לפורמט ש־PHP ו־wp_http_validate_url מקבלים
     */
    private function normalize_url_for_download( $url ) {
        $url = is_string( $url ) ? trim( $url ) : '';
        if ( $url === '' ) {
            return '';
        }
        $url = preg_replace( '/^[\x{FEFF}\x{200B}\x{200C}\x{200D}\x{2060}]+|[\x{FEFF}\x{200B}\x{200C}\x{200D}\x{2060}]+$/u', '', $url );
        $parsed = wp_parse_url( $url );
        if ( empty( $parsed['scheme'] ) || empty( $parsed['host'] ) ) {
            return $url;
        }
        if ( empty( $parsed['path'] ) || $parsed['path'] === '/' ) {
            return $url;
        }
        $segments  = explode( '/', $parsed['path'] );
        $rebuilt_s = array();
        foreach ( $segments as $segment ) {
            if ( $segment === '' ) {
                $rebuilt_s[] = '';
                continue;
            }
            $rebuilt_s[] = rawurlencode( rawurldecode( $segment ) );
        }
        $new_path = implode( '/', $rebuilt_s );
        $out      = $parsed['scheme'] . '://' . $parsed['host'];
        if ( ! empty( $parsed['port'] ) ) {
            $out .= ':' . (int) $parsed['port'];
        }
        $out .= $new_path;
        if ( ! empty( $parsed['query'] ) ) {
            $out .= '?' . $parsed['query'];
        }
        if ( ! empty( $parsed['fragment'] ) ) {
            $out .= '#' . $parsed['fragment'];
        }
        return $out;
    }

    private function set_featured_image_from_url( $post_id, $image_url, &$errors ) {
        $image_url = $this->normalize_url_for_download( $image_url );
        if ( $image_url === '' || ! wp_http_validate_url( $image_url ) ) {
            $errors[] = sprintf( __( 'קישור תמונה לא תקין עבור פוסט %d: %s', 'acf-csv-importer' ), $post_id, $image_url );
            return;
        }

        // טעינת קבצי עזר של וורדפרס
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        // הורדת התמונה מהקישור
        $tmp = download_url( $image_url, 300 );

        if ( is_wp_error( $tmp ) ) {
            $errors[] = sprintf( __( 'שגיאה בהורדת תמונה עבור פוסט %d: %s', 'acf-csv-importer' ), $post_id, $tmp->get_error_message() );
            return;
        }

        $file_name = basename( $image_url );
        $file_array = array(
            'name'     => $file_name,
            'tmp_name' => $tmp,
        );

        // העלאת התמונה לספריית המדיה
        $attachment_id = media_handle_sideload( $file_array, $post_id );

        // אם יש שגיאה, נקה ומחק את הקובץ הזמני
        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $file_array['tmp_name'] );
            $errors[] = sprintf( __( 'שגיאה בהעלאת תמונה לספריית המדיה עבור פוסט %d: %s', 'acf-csv-importer' ), $post_id, $attachment_id->get_error_message() );
            return;
        }

        // הגדרת התמונה כראשית
        set_post_thumbnail( $post_id, $attachment_id );
    }
}
