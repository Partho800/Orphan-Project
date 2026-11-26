<?php
/**
 * Plugin Name:       Orphan Project
 * Plugin URI:        https://techsoulbd.com/
 * Description:       Complete multi-step registration and evaluation form for Orphan Project based on SAP & HASENE format. Includes shortcode [orphan_registration_form] or [orphan_form], admin management panel, and CSV export.
 * Version:           2.0
 * Author:            Techsoul
 * Author URI:        https://techsoulbd.com/
 * Text Domain:       orphan-project-form
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('OPF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OPF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OPF_VERSION', '2.0');

/**
 * Create / Upgrade Database Tables on Plugin Activation or Upgrade
 */
function opf_install_database() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'orphan_applications';
    $old_ids_table   = $wpdb->prefix . 'orphan_old_ids';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        application_no varchar(50) NOT NULL,
        application_type varchar(20) DEFAULT 'new',
        student_name varchar(255) NOT NULL,
        gender varchar(20) DEFAULT '',
        date_of_birth date DEFAULT NULL,
        contact_number varchar(50) DEFAULT '',
        birth_reg_no varchar(50) DEFAULT '',
        college_name varchar(255) DEFAULT '',
        father_name varchar(255) DEFAULT '',
        mother_name varchar(255) DEFAULT '',
        guardian_name varchar(255) DEFAULT '',
        district varchar(100) DEFAULT '',
        sap_reference varchar(255) DEFAULT '',
        uploaded_file varchar(500) DEFAULT '',
        form_data longtext DEFAULT '',
        status varchar(30) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY application_no (application_no),
        KEY application_type (application_type)
    ) $charset_collate;";

    $sql_old_ids = "CREATE TABLE $old_ids_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        old_id varchar(64) NOT NULL,
        student_name varchar(255) DEFAULT '',
        note varchar(255) DEFAULT '',
        status varchar(20) DEFAULT 'waiting',
        application_id bigint(20) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY old_id (old_id),
        KEY status (status),
        KEY application_id (application_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql_old_ids);

    // Ensure columns exist on legacy installs even if dbDelta skips altered columns
    $col = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'application_type'");
    if (empty($col)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN application_type varchar(20) DEFAULT 'new' AFTER application_no");
    }

    $col_old_name = $wpdb->get_results("SHOW COLUMNS FROM $old_ids_table LIKE 'student_name'");
    if (empty($col_old_name)) {
        $wpdb->query("ALTER TABLE $old_ids_table ADD COLUMN student_name varchar(255) DEFAULT '' AFTER old_id");
    }

    // One-time migration: renumber existing New Applications sequentially (1, 2, 3, 4...)
    if (!get_option('opf_seq_new_ids_migrated')) {
        $new_rows = $wpdb->get_results(
            "SELECT id FROM $table_name WHERE (application_type IS NULL OR application_type = '' OR application_type = 'new') ORDER BY id ASC"
        );
        if (!empty($new_rows)) {
            $seq = 1;
            foreach ($new_rows as $nr) {
                while (true) {
                    $taken_old_app = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_name WHERE application_no = %s AND application_type = 'old'",
                        (string) $seq
                    ));
                    $taken_old_list = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $old_ids_table WHERE old_id = %s",
                        (string) $seq
                    ));
                    if ($taken_old_app === 0 && $taken_old_list === 0) {
                        break;
                    }
                    $seq++;
                }
                $wpdb->update(
                    $table_name,
                    ['application_no' => (string) $seq],
                    ['id' => intval($nr->id)],
                    ['%s'],
                    ['%d']
                );
                $seq++;
            }
        }
        update_option('opf_seq_new_ids_migrated', '1');
    }

    update_option('opf_db_version', OPF_VERSION);
}
register_activation_hook(__FILE__, 'opf_install_database');

/**
 * Check and ensure tables/columns exist automatically across updates
 */
function opf_check_db_table() {
    global $wpdb;
    $table_name    = $wpdb->prefix . 'orphan_applications';
    $old_ids_table = $wpdb->prefix . 'orphan_old_ids';

    if (
        get_option('opf_db_version') !== OPF_VERSION ||
        $wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name ||
        $wpdb->get_var("SHOW TABLES LIKE '$old_ids_table'") !== $old_ids_table
    ) {
        opf_install_database();
    }
}
add_action('plugins_loaded', 'opf_check_db_table');
add_action('admin_init', 'opf_check_db_table');

/**
 * Enqueue Frontend Scripts & Styles
 */
function opf_enqueue_frontend_assets() {
    // Enqueue Google Font Poppins
    wp_enqueue_style('opf-poppins-font', 'https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600;1,700&display=swap', [], null);

    wp_register_style('opf-frontend-css', OPF_PLUGIN_URL . 'assets/css/frontend-style.css', ['opf-poppins-font'], OPF_VERSION);
    wp_register_script('opf-frontend-js', OPF_PLUGIN_URL . 'assets/js/frontend-script.js', [], OPF_VERSION, true);

    wp_localize_script('opf-frontend-js', 'orphan_form_obj', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}
add_action('wp_enqueue_scripts', 'opf_enqueue_frontend_assets');

/**
 * Enqueue Admin Scripts & Styles
 */
function opf_enqueue_admin_assets($hook) {
    if (strpos($hook, 'orphan-applications') !== false) {
        wp_enqueue_style('opf-poppins-font', 'https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600;1,700&display=swap', [], null);
        wp_enqueue_style('opf-admin-css', OPF_PLUGIN_URL . 'assets/css/admin-style.css', ['opf-poppins-font'], OPF_VERSION);
        wp_enqueue_script('opf-admin-js', OPF_PLUGIN_URL . 'assets/js/admin-script.js', [], OPF_VERSION, true);

        wp_localize_script('opf-admin-js', 'orphan_admin_obj', [
            'ajax_url'   => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('opf_admin_nonce_action'),
            'plugin_url' => OPF_PLUGIN_URL,
            'site_url'   => home_url('/')
        ]);
    }
}
add_action('admin_enqueue_scripts', 'opf_enqueue_admin_assets');

/**
 * Shortcode to render Multi-Step Form on Frontend
 * Usage: [orphan_registration_form] or [orphan_form]
 */
function opf_render_form_shortcode($atts) {
    wp_enqueue_style('opf-frontend-css');
    wp_enqueue_script('opf-frontend-js');

    ob_start();
    include OPF_PLUGIN_DIR . 'templates/form-template.php';
    return ob_get_clean();
}
add_shortcode('orphan_registration_form', 'opf_render_form_shortcode');
add_shortcode('orphan_form', 'opf_render_form_shortcode');

/**
 * Generate the next sequential New Application ID (1, 2, 3, 4, 5...)
 * Avoids any collision with existing application_no or Admin Old IDs.
 */
function opf_generate_unique_application_id() {
    global $wpdb;
    $table_name    = $wpdb->prefix . 'orphan_applications';
    $old_ids_table = $wpdb->prefix . 'orphan_old_ids';

    $max_new = (int) $wpdb->get_var(
        "SELECT MAX(CAST(application_no AS UNSIGNED)) FROM $table_name
         WHERE (application_type IS NULL OR application_type = '' OR application_type = 'new')
           AND application_no REGEXP '^[0-9]+$'"
    );

    $candidate = max(1, $max_new + 1);
    $guard     = 0;

    while ($guard < 5000) {
        $guard++;
        $candidate_str = (string) $candidate;

        $exists_app = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE application_no = %s",
            $candidate_str
        ));
        $exists_old = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $old_ids_table WHERE old_id = %s",
            $candidate_str
        ));

        if ($exists_app === 0 && $exists_old === 0) {
            return $candidate_str;
        }
        $candidate++;
    }

    return (string) $candidate;
}

/**
 * Normalize an Application ID string (trim whitespace, strip leading #, allow alphanumeric, hyphen, underscore)
 */
function opf_normalize_old_id($raw_id) {
    $clean = preg_replace('/[^0-9A-Za-z\-_#]/', '', sanitize_text_field(trim((string) $raw_id)));
    $clean = ltrim($clean, '#');
    if (strlen($clean) > 64) {
        $clean = substr($clean, 0, 64);
    }
    return $clean;
}

/**
 * Look up an Application ID in Admin Old IDs whitelist table first, then submitted applications.
 */
function opf_resolve_old_application_id($raw_id) {
    $clean_id = opf_normalize_old_id($raw_id);
    if (empty($clean_id)) {
        return ['valid_input' => false, 'matched' => false, 'clean_id' => '', 'old_id_row' => null, 'existing_app' => null, 'resolved_no' => ''];
    }

    global $wpdb;
    $table_name    = $wpdb->prefix . 'orphan_applications';
    $old_ids_table = $wpdb->prefix . 'orphan_old_ids';

    $old_id_row   = $wpdb->get_row($wpdb->prepare("SELECT * FROM $old_ids_table WHERE old_id = %s LIMIT 1", $clean_id));
    $existing_app = null;

    if ($old_id_row) {
        if (!empty($old_id_row->application_id)) {
            $existing_app = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d LIMIT 1", intval($old_id_row->application_id)));
        }
        if (!$existing_app) {
            $existing_app = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE application_no = %s LIMIT 1", $old_id_row->old_id));
        }
    } else {
        $existing_app = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE application_no = %s LIMIT 1", $clean_id));
        if (!$existing_app && is_numeric($clean_id) && intval($clean_id) > 0 && intval($clean_id) < 1000000) {
            $existing_app = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d LIMIT 1", intval($clean_id)));
        }
    }

    if (!$old_id_row && !$existing_app) {
        return ['valid_input' => true, 'matched' => false, 'clean_id' => $clean_id, 'old_id_row' => null, 'existing_app' => null, 'resolved_no' => ''];
    }

    $resolved_no = $old_id_row
        ? $old_id_row->old_id
        : ($existing_app->application_no ?: (string) $existing_app->id);

    return [
        'valid_input'  => true,
        'matched'      => true,
        'clean_id'     => $clean_id,
        'old_id_row'   => $old_id_row,
        'existing_app' => $existing_app,
        'resolved_no'  => $resolved_no,
    ];
}

/**
 * Verify Existing Application ID for Old Application Mode
 */
function opf_ajax_verify_old_id() {
    check_ajax_referer('orphan_form_nonce_action', 'nonce');

    $raw_id = isset($_POST['old_application_id']) ? $_POST['old_application_id'] : '';
    $match  = opf_resolve_old_application_id($raw_id);

    if (!$match['valid_input']) {
        wp_send_json_error(['message' => 'Please enter an existing Application ID.']);
    }

    if (!$match['matched']) {
        wp_send_json_error(['message' => 'Invalid ID. Please enter a valid ID.']);
    }

    $student_name = '';
    if ($match['existing_app'] && !empty($match['existing_app']->student_name)) {
        $student_name = $match['existing_app']->student_name;
    } elseif ($match['old_id_row'] && !empty($match['old_id_row']->student_name)) {
        $student_name = $match['old_id_row']->student_name;
    } elseif ($match['old_id_row'] && !empty($match['old_id_row']->note)) {
        $student_name = $match['old_id_row']->note;
    }

    wp_send_json_success([
        'id'             => $match['existing_app'] ? $match['existing_app']->id : ($match['old_id_row'] ? $match['old_id_row']->id : 0),
        'application_no' => $match['resolved_no'],
        'student_name'   => $student_name
    ]);
}
add_action('wp_ajax_opf_verify_old_id', 'opf_ajax_verify_old_id');
add_action('wp_ajax_nopriv_opf_verify_old_id', 'opf_ajax_verify_old_id');

/**
 * Shared Helper: Save an uploaded cropped image (signature or photo) to wp-content/uploads/orphan-forms/
 */
function opf_save_step5_image_upload($file_key, $prefix_tag) {
    if (empty($_FILES[$file_key]) || empty($_FILES[$file_key]['tmp_name']) || !is_uploaded_file($_FILES[$file_key]['tmp_name'])) {
        return '';
    }

    $file = $_FILES[$file_key];
    if ($file['size'] > 8 * 1024 * 1024) {
        return '';
    }

    $img_info = @getimagesize($file['tmp_name']);
    if (!$img_info || empty($img_info['mime'])) {
        return '';
    }

    $mime_to_ext = [
        'image/png'  => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];
    if (!isset($mime_to_ext[$img_info['mime']])) {
        return '';
    }

    $ext        = $mime_to_ext[$img_info['mime']];
    $upload_dir = wp_upload_dir();
    $target_dir = trailingslashit($upload_dir['basedir']) . 'orphan-forms';
    $target_url = trailingslashit($upload_dir['baseurl']) . 'orphan-forms';

    if (!file_exists($target_dir)) {
        wp_mkdir_p($target_dir);
    }

    $filename  = sanitize_file_name($prefix_tag . '_' . uniqid() . '_' . wp_rand(100, 999) . '.' . $ext);
    $dest_path = trailingslashit($target_dir) . $filename;

    if (@move_uploaded_file($file['tmp_name'], $dest_path)) {
        return trailingslashit($target_url) . $filename;
    }

    return '';
}

/**
 * Handle AJAX Form Submission
 */
function opf_handle_ajax_submission() {
    check_ajax_referer('orphan_form_nonce_action', 'orphan_form_nonce');

    global $wpdb;
    $table_name    = $wpdb->prefix . 'orphan_applications';
    $old_ids_table = $wpdb->prefix . 'orphan_old_ids';

    $application_mode = isset($_POST['application_mode']) ? sanitize_text_field($_POST['application_mode']) : 'new';
    $existing_app = null;
    $old_id_row   = null;
    $resolved_old_no = '';

    if ($application_mode === 'old') {
        $raw_old_id = isset($_POST['old_application_id']) ? $_POST['old_application_id'] : '';
        $match      = opf_resolve_old_application_id($raw_old_id);

        if (!$match['valid_input']) {
            wp_send_json_error(['message' => 'Application ID is required for Old Application.']);
        }

        if (!$match['matched']) {
            wp_send_json_error(['message' => 'Invalid ID. Please enter a valid ID.']);
        }

        $old_id_row      = $match['old_id_row'];
        $existing_app    = $match['existing_app'];
        $resolved_old_no = $match['resolved_no'];
    }

    // Required fields check
    $first_name = isset($_POST['student_first_name']) ? sanitize_text_field($_POST['student_first_name']) : '';
    $surname    = isset($_POST['student_surname']) ? sanitize_text_field($_POST['student_surname']) : '';
    $mobile     = isset($_POST['student_mobile']) ? sanitize_text_field($_POST['student_mobile']) : '';
    $birth_reg  = isset($_POST['birth_registration_number']) ? sanitize_text_field($_POST['birth_registration_number']) : '';

    if (empty($first_name) || empty($surname) || empty($mobile) || empty($birth_reg)) {
        wp_send_json_error(['message' => 'Please fill in all required fields (Name, Mobile, Birth Registration Number).']);
    }

    $student_full_name = trim($first_name . ' ' . $surname);

    // Sanitize Evaluation array
    $eval_clean = [];
    if (isset($_POST['eval']) && is_array($_POST['eval'])) {
        foreach ($_POST['eval'] as $k => $v) {
            $eval_clean[sanitize_key($k)] = sanitize_text_field($v);
        }
    }

    // Build comprehensive structured data
    $submission_data = [
        // 1. Student Personal Information
        'student_first_name'         => $first_name,
        'student_surname'            => $surname,
        'student_full_name'          => $student_full_name,
        'gender'                     => isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : '',
        'date_of_birth'              => isset($_POST['date_of_birth']) ? sanitize_text_field($_POST['date_of_birth']) : '',
        'age'                        => isset($_POST['age']) ? sanitize_text_field($_POST['age']) : '',
        'siblings_brothers'          => isset($_POST['siblings_brothers']) ? intval($_POST['siblings_brothers']) : 0,
        'siblings_sisters'           => isset($_POST['siblings_sisters']) ? intval($_POST['siblings_sisters']) : 0,
        'siblings_position'          => isset($_POST['siblings_position']) ? sanitize_text_field($_POST['siblings_position']) : '',
        'student_mobile'             => $mobile,
        'birth_registration_number'  => $birth_reg,
        'student_nid'                => isset($_POST['student_nid']) ? sanitize_text_field($_POST['student_nid']) : '',
        'college_school_name'        => isset($_POST['college_school_name']) ? sanitize_text_field($_POST['college_school_name']) : '',
        'studying_class'             => isset($_POST['studying_class']) ? sanitize_text_field($_POST['studying_class']) : '',
        'studying_id_no'             => isset($_POST['studying_id_no']) ? sanitize_text_field($_POST['studying_id_no']) : '',
        'studying_group_subject'     => isset($_POST['studying_group_subject']) ? sanitize_text_field($_POST['studying_group_subject']) : '',

        // 2. Family & Guardians Information
        'father_full_name'           => isset($_POST['father_full_name']) ? sanitize_text_field($_POST['father_full_name']) : '',
        'father_mobile'              => isset($_POST['father_mobile']) ? sanitize_text_field($_POST['father_mobile']) : '',
        'father_nid'                 => isset($_POST['father_nid']) ? sanitize_text_field($_POST['father_nid']) : '',
        'father_occupation'          => isset($_POST['father_occupation']) ? sanitize_text_field($_POST['father_occupation']) : '',
        'father_monthly_income'      => isset($_POST['father_monthly_income']) ? sanitize_text_field($_POST['father_monthly_income']) : '',
        'father_is_deceased'         => (!empty($_POST['father_death_reg_no']) || !empty($_POST['father_cause_of_death']) || (isset($_POST['father_is_deceased']) && $_POST['father_is_deceased'] === 'yes')) ? 'yes' : 'no',
        'father_death_reg_no'        => isset($_POST['father_death_reg_no']) ? sanitize_text_field($_POST['father_death_reg_no']) : '',
        'father_cause_of_death'      => isset($_POST['father_cause_of_death']) ? sanitize_text_field($_POST['father_cause_of_death']) : '',

        'mother_full_name'           => isset($_POST['mother_full_name']) ? sanitize_text_field($_POST['mother_full_name']) : '',
        'mother_mobile'              => isset($_POST['mother_mobile']) ? sanitize_text_field($_POST['mother_mobile']) : '',
        'mother_nid'                 => isset($_POST['mother_nid']) ? sanitize_text_field($_POST['mother_nid']) : '',
        'mother_occupation'          => isset($_POST['mother_occupation']) ? sanitize_text_field($_POST['mother_occupation']) : '',
        'mother_monthly_income'      => isset($_POST['mother_monthly_income']) ? sanitize_text_field($_POST['mother_monthly_income']) : '',
        'mother_is_deceased'         => (!empty($_POST['mother_death_reg_no']) || !empty($_POST['mother_cause_of_death']) || (isset($_POST['mother_is_deceased']) && $_POST['mother_is_deceased'] === 'yes')) ? 'yes' : 'no',
        'mother_death_reg_no'        => isset($_POST['mother_death_reg_no']) ? sanitize_text_field($_POST['mother_death_reg_no']) : '',
        'mother_cause_of_death'      => isset($_POST['mother_cause_of_death']) ? sanitize_text_field($_POST['mother_cause_of_death']) : '',

        'has_separate_guardian'      => (!empty($_POST['guardian_full_name']) || !empty($_POST['guardian_mobile']) || !empty($_POST['guardian_nid']) || !empty($_POST['guardian_relation']) || (isset($_POST['has_separate_guardian']) && $_POST['has_separate_guardian'] === 'yes')) ? 'yes' : 'no',
        'guardian_full_name'         => isset($_POST['guardian_full_name']) ? sanitize_text_field($_POST['guardian_full_name']) : '',
        'guardian_mobile'            => isset($_POST['guardian_mobile']) ? sanitize_text_field($_POST['guardian_mobile']) : '',
        'guardian_nid'               => isset($_POST['guardian_nid']) ? sanitize_text_field($_POST['guardian_nid']) : '',
        'guardian_dob'               => isset($_POST['guardian_dob']) ? sanitize_text_field($_POST['guardian_dob']) : '',
        'guardian_relation'          => isset($_POST['guardian_relation']) ? sanitize_text_field($_POST['guardian_relation']) : '',

        // 3. Address & SAP References
        'present_house'              => isset($_POST['present_house']) ? sanitize_text_field($_POST['present_house']) : '',
        'present_village'            => isset($_POST['present_village']) ? sanitize_text_field($_POST['present_village']) : '',
        'present_post_office'        => isset($_POST['present_post_office']) ? sanitize_text_field($_POST['present_post_office']) : '',
        'present_police_station'     => isset($_POST['present_police_station']) ? sanitize_text_field($_POST['present_police_station']) : '',
        'present_district'           => isset($_POST['present_district']) ? sanitize_text_field($_POST['present_district']) : '',
        'present_country'            => isset($_POST['present_country']) ? sanitize_text_field($_POST['present_country']) : 'Bangladesh',

        'permanent_house'            => isset($_POST['permanent_house']) ? sanitize_text_field($_POST['permanent_house']) : '',
        'permanent_village'          => isset($_POST['permanent_village']) ? sanitize_text_field($_POST['permanent_village']) : '',
        'permanent_post_office'      => isset($_POST['permanent_post_office']) ? sanitize_text_field($_POST['permanent_post_office']) : '',
        'permanent_police_station'   => isset($_POST['permanent_police_station']) ? sanitize_text_field($_POST['permanent_police_station']) : '',
        'permanent_district'         => isset($_POST['permanent_district']) ? sanitize_text_field($_POST['permanent_district']) : '',
        'permanent_country'          => isset($_POST['permanent_country']) ? sanitize_text_field($_POST['permanent_country']) : 'Bangladesh',

        'sap_reference_name'         => isset($_POST['sap_reference_name']) ? sanitize_text_field($_POST['sap_reference_name']) : '',
        'sap_reference_mobile'       => isset($_POST['sap_reference_mobile']) ? sanitize_text_field($_POST['sap_reference_mobile']) : '',

        // 4. Education Status & Signatures / Photos (Step 4 & Step 5)
        'eval'                       => $eval_clean,
        'applicant_signature_name'   => isset($_POST['applicant_signature_name']) ? sanitize_text_field($_POST['applicant_signature_name']) : '',
        'application_date'           => isset($_POST['application_date']) ? sanitize_text_field($_POST['application_date']) : date('Y-m-d'),
        'digital_signature_data'     => isset($_POST['digital_signature_data']) ? sanitize_textarea_field($_POST['digital_signature_data']) : '',
        'signature_image_url'        => opf_save_step5_image_upload('signature_image_file', 'sig'),
        'short_photo_url'            => opf_save_step5_image_upload('short_photo_file', 'short'),
        'half_photo_url'             => opf_save_step5_image_upload('half_photo_file', 'half'),
    ];

    if ($application_mode === 'old' && $existing_app) {
        $existing_data = json_decode($existing_app->form_data, true);
        if (is_array($existing_data)) {
            $preserve_keys = ['uploaded_document_url', 'uploaded_at', 'signature_image_url', 'short_photo_url', 'half_photo_url'];
            foreach ($preserve_keys as $pk) {
                if (empty($submission_data[$pk]) && !empty($existing_data[$pk])) {
                    $submission_data[$pk] = $existing_data[$pk];
                }
            }
        }

        $target_app_no = $existing_app->application_no ?: (string) $existing_app->id;

        $update_row = [
            'student_name'   => $student_full_name,
            'gender'         => $submission_data['gender'],
            'date_of_birth'  => !empty($submission_data['date_of_birth']) ? $submission_data['date_of_birth'] : null,
            'contact_number' => $mobile,
            'birth_reg_no'   => $birth_reg,
            'college_name'   => $submission_data['college_school_name'],
            'father_name'    => $submission_data['father_full_name'],
            'mother_name'    => $submission_data['mother_full_name'],
            'guardian_name'  => $submission_data['guardian_full_name'],
            'district'       => $submission_data['present_district'],
            'sap_reference'  => $submission_data['sap_reference_name'],
            'form_data'      => wp_json_encode($submission_data),
        ];
        if ($old_id_row) {
            $update_row['application_type'] = 'old';
        }

        $updated = $wpdb->update($table_name, $update_row, ['id' => $existing_app->id]);

        if ($updated !== false) {
            if ($old_id_row) {
                $wpdb->update(
                    $old_ids_table,
                    ['status' => 'submitted', 'application_id' => $existing_app->id],
                    ['id' => $old_id_row->id],
                    ['%s', '%d'],
                    ['%d']
                );
            }
            $view_url = add_query_arg('opf_view_app', $target_app_no, home_url('/'));
            wp_send_json_success([
                'message'        => 'Application updated successfully!',
                'application_no' => $target_app_no,
                'id'             => $target_app_no,
                'db_id'          => $existing_app->id,
                'view_url'       => $view_url
            ]);
        } else {
            wp_send_json_error(['message' => 'Database error. Could not update application.']);
        }
    }

    // Use pre-approved Old Application ID if submitting via Old Apply list, otherwise generate unique random ID
    $is_old_mode    = ($application_mode === 'old' && !empty($resolved_old_no));
    $application_no = $is_old_mode ? $resolved_old_no : opf_generate_unique_application_id();
    $app_type       = $is_old_mode ? 'old' : 'new';

    // Insert into DB
    $inserted = $wpdb->insert($table_name, [
        'application_no'   => $application_no,
        'application_type' => $app_type,
        'student_name'     => $student_full_name,
        'gender'           => $submission_data['gender'],
        'date_of_birth'    => !empty($submission_data['date_of_birth']) ? $submission_data['date_of_birth'] : null,
        'contact_number'   => $mobile,
        'birth_reg_no'     => $birth_reg,
        'college_name'     => $submission_data['college_school_name'],
        'father_name'      => $submission_data['father_full_name'],
        'mother_name'      => $submission_data['mother_full_name'],
        'guardian_name'    => $submission_data['guardian_full_name'],
        'district'         => $submission_data['present_district'],
        'sap_reference'    => $submission_data['sap_reference_name'],
        'form_data'        => wp_json_encode($submission_data),
        'status'           => 'pending',
        'created_at'       => current_time('mysql')
    ]);

    if ($inserted) {
        $new_app_id = $wpdb->insert_id;
        if ($is_old_mode && $old_id_row) {
            $wpdb->update(
                $old_ids_table,
                ['status' => 'submitted', 'application_id' => $new_app_id],
                ['id' => $old_id_row->id],
                ['%s', '%d'],
                ['%d']
            );
        }
        $view_url = add_query_arg('opf_view_app', $application_no, home_url('/'));
        wp_send_json_success([
            'message'        => 'Application submitted successfully!',
            'application_no' => $application_no,
            'id'             => $application_no,
            'db_id'          => $new_app_id,
            'view_url'       => $view_url
        ]);
    } else {
        wp_send_json_error(['message' => 'Database error. Could not save application.']);
    }
}
add_action('wp_ajax_submit_orphan_registration_form', 'opf_handle_ajax_submission');
add_action('wp_ajax_nopriv_submit_orphan_registration_form', 'opf_handle_ajax_submission');

/**
 * Handle Standalone Application View & PDF Download Page
 */
function opf_handle_view_application() {
    if (isset($_GET['opf_view_app'])) {
        $app_no = sanitize_text_field($_GET['opf_view_app']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'orphan_applications';
        $app = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE application_no = %s LIMIT 1", $app_no));
        if (!$app && is_numeric($app_no) && intval($app_no) > 0) {
            $app = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d LIMIT 1", intval($app_no)));
        }
        if ($app) {
            include OPF_PLUGIN_DIR . 'templates/view-application.php';
            exit;
        }
    }
}
add_action('template_redirect', 'opf_handle_view_application');

/**
 * Handle Automatic Blank Form Download
 */
function opf_handle_download_blank_form() {
    if (isset($_GET['opf_download_blank'])) {
        $pdf_path = OPF_PLUGIN_DIR . 'Orphan Information New 19_5_26.pdf';
        if (file_exists($pdf_path)) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Orphan_Project_Blank_Application_Form.pdf"');
            header('Content-Length: ' . filesize($pdf_path));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            readfile($pdf_path);
            exit;
        }
    }
}
add_action('template_redirect', 'opf_handle_download_blank_form');

/**
 * Handle AJAX Upload Form Submission (New & Existing Applicants)
 */
function opf_handle_ajax_upload_submission() {
    check_ajax_referer('orphan_form_nonce_action', 'orphan_form_nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'orphan_applications';

    // 1. Validate File
    if (empty($_FILES['uploaded_form_file']) || empty($_FILES['uploaded_form_file']['name'])) {
        wp_send_json_error(['message' => 'Please select a PDF or image file of your completed form to upload.']);
    }

    $file = $_FILES['uploaded_form_file'];
    $allowed_mimes = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png'
    ];

    $file_info = wp_check_filetype(basename($file['name']), $allowed_mimes);
    if (empty($file_info['ext']) || !in_array($file_info['type'], $allowed_mimes, true)) {
        wp_send_json_error(['message' => 'Invalid file format. Only PDF, JPG, and PNG files are allowed.']);
    }

    // Max 20MB
    if ($file['size'] > 20 * 1024 * 1024) {
        wp_send_json_error(['message' => 'File size exceeds maximum limit of 20MB.']);
    }

    // Process file upload using WordPress media handling
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    $upload_overrides = ['test_form' => false];
    if (is_uploaded_file($file['tmp_name'])) {
        $movefile = wp_handle_upload($file, $upload_overrides);
    } else {
        $movefile = wp_handle_sideload($file, $upload_overrides);
    }

    if (!$movefile || isset($movefile['error'])) {
        wp_send_json_error(['message' => 'File upload failed: ' . ($movefile['error'] ?? 'Unknown error')]);
    }

    $uploaded_file_url = $movefile['url'];
    $applicant_type = isset($_POST['applicant_type']) ? sanitize_text_field($_POST['applicant_type']) : 'new';

    if ($applicant_type === 'existing') {
        // Existing / Old Applicant
        $lookup       = isset($_POST['existing_app_lookup']) ? sanitize_text_field(trim($_POST['existing_app_lookup'])) : '';
        $student_name = isset($_POST['existing_student_name']) ? sanitize_text_field(trim($_POST['existing_student_name'])) : '';

        if (empty($lookup) || empty($student_name)) {
            wp_send_json_error(['message' => 'Please provide Application ID and Full Name so we can verify your existing application.']);
        }

        $clean_lookup = ltrim(preg_replace('/[^0-9A-Za-z\-#]/', '', $lookup), '#');

        // Find existing record strictly by Application ID (application_no or legacy numeric id)
        $existing_app = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE application_no = %s LIMIT 1",
            $clean_lookup
        ));

        if (!$existing_app && is_numeric($clean_lookup) && intval($clean_lookup) > 0 && intval($clean_lookup) < 1000000) {
            $existing_app = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d LIMIT 1",
                intval($clean_lookup)
            ));
        }

        if (!$existing_app || stripos(trim($existing_app->student_name), $student_name) === false) {
            wp_send_json_error(['message' => 'Invalid ID. Please enter a valid ID.']);
        }

        // Update existing application
        $form_data = json_decode($existing_app->form_data, true);
        if (!is_array($form_data)) $form_data = [];
        $form_data['uploaded_document_url'] = $uploaded_file_url;
        $form_data['uploaded_at'] = current_time('mysql');

        $wpdb->update(
            $table_name,
            [
                'uploaded_file' => $uploaded_file_url,
                'form_data'     => wp_json_encode($form_data)
            ],
            ['id' => $existing_app->id]
        );

        wp_send_json_success([
            'message'        => 'Completed form uploaded and linked to Application #' . $existing_app->application_no . ' (' . $existing_app->student_name . ') successfully!',
            'application_no' => $existing_app->application_no,
            'id'             => $existing_app->application_no,
            'student_name'   => $existing_app->student_name,
            'file_url'       => $uploaded_file_url,
            'is_existing'    => true
        ]);

    } else {
        // New Applicant
        $first_name = isset($_POST['student_first_name']) ? sanitize_text_field(trim($_POST['student_first_name'])) : '';
        $surname    = isset($_POST['student_surname']) ? sanitize_text_field(trim($_POST['student_surname'])) : '';
        $mobile     = isset($_POST['student_mobile']) ? sanitize_text_field(trim($_POST['student_mobile'])) : '';
        $birth_reg  = isset($_POST['birth_registration_number']) ? sanitize_text_field(trim($_POST['birth_registration_number'])) : '';
        $gender     = isset($_POST['gender']) ? sanitize_text_field(trim($_POST['gender'])) : 'Male';
        $district   = isset($_POST['district']) ? sanitize_text_field(trim($_POST['district'])) : '';
        $dob        = isset($_POST['date_of_birth']) ? sanitize_text_field(trim($_POST['date_of_birth'])) : null;

        if (empty($first_name) || empty($surname) || empty($mobile) || empty($birth_reg)) {
            wp_send_json_error(['message' => 'Please fill in required fields (Student Name, Mobile Number, Birth Registration Number).']);
        }

        $student_full_name = trim($first_name . ' ' . $surname);
        // Generate unique Application ID: 4-6 random digits (non-sequential, unpredictable)
        $application_no = opf_generate_unique_application_id();

        $form_data = [
            'student_first_name'         => $first_name,
            'student_surname'            => $surname,
            'student_full_name'          => $student_full_name,
            'gender'                     => $gender,
            'date_of_birth'              => $dob,
            'student_mobile'             => $mobile,
            'birth_registration_number'  => $birth_reg,
            'present_district'           => $district,
            'uploaded_document_url'      => $uploaded_file_url,
            'submission_source'          => 'offline_upload',
            'uploaded_at'                => current_time('mysql')
        ];

        $inserted = $wpdb->insert($table_name, [
            'application_no' => $application_no,
            'student_name'   => $student_full_name,
            'gender'         => $gender,
            'date_of_birth'  => !empty($dob) ? $dob : null,
            'contact_number' => $mobile,
            'birth_reg_no'   => $birth_reg,
            'district'       => $district,
            'uploaded_file'  => $uploaded_file_url,
            'form_data'      => wp_json_encode($form_data),
            'status'         => 'pending',
            'created_at'     => current_time('mysql')
        ]);

        if ($inserted) {
            $new_id = $wpdb->insert_id;
            wp_send_json_success([
                'message'        => 'Application registered and completed form uploaded successfully!',
                'application_no' => $application_no,
                'id'             => $application_no,
                'db_id'          => $new_id,
                'student_name'   => $student_full_name,
                'file_url'       => $uploaded_file_url,
                'is_existing'    => false
            ]);
        } else {
            wp_send_json_error(['message' => 'Could not save application into database.']);
        }
    }
}
add_action('wp_ajax_submit_orphan_upload_form', 'opf_handle_ajax_upload_submission');
add_action('wp_ajax_nopriv_submit_orphan_upload_form', 'opf_handle_ajax_upload_submission');


/**
 * Register Admin Menu for Form Submissions
 */
function opf_register_admin_menu() {
    add_menu_page(
        'Orphan Applications',
        'Orphan Forms',
        'manage_options',
        'orphan-applications',
        'opf_render_admin_page',
        'dashicons-id-alt',
        26
    );
}
add_action('admin_menu', 'opf_register_admin_menu');

/**
 * Render Admin Page
 */
function opf_render_admin_page() {
    include OPF_PLUGIN_DIR . 'templates/admin-submissions.php';
}

/**
 * Export All Applications to CSV
 */
function opf_export_submissions_csv() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'orphan_applications';
    $rows = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");

    $filename = 'orphan_applications_' . date('Y-m-d_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');

    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Headers
    fputcsv($output, [
        'ID',
        'Application No',
        'Submission Date',
        'Student Name',
        'Gender',
        'Date of Birth',
        'Age',
        'Contact Number',
        'Birth Reg No',
        'NID',
        'College / School',
        'Class',
        'Studying ID',
        'Group / Subject',
        'Father Name',
        'Father Mobile',
        'Father NID',
        'Father Occupation',
        'Father Monthly Income',
        'Father Deceased',
        'Mother Name',
        'Mother Mobile',
        'Mother NID',
        'Mother Occupation',
        'Mother Monthly Income',
        'Mother Deceased',
        'Guardian Name',
        'Guardian Mobile',
        'Guardian Relation',
        'Present Address',
        'Permanent Address',
        'District',
        'SAP Reference Name',
        'SAP Reference Mobile',
        'Status'
    ]);

    foreach ($rows as $r) {
        $d = json_decode($r->form_data, true);
        if (!is_array($d)) $d = [];

        $present_addr = trim(implode(', ', array_filter([
            $d['present_house'] ?? '',
            $d['present_village'] ?? '',
            $d['present_post_office'] ?? '',
            $d['present_police_station'] ?? '',
            $d['present_district'] ?? '',
            $d['present_country'] ?? ''
        ])));

        $permanent_addr = trim(implode(', ', array_filter([
            $d['permanent_house'] ?? '',
            $d['permanent_village'] ?? '',
            $d['permanent_post_office'] ?? '',
            $d['permanent_police_station'] ?? '',
            $d['permanent_district'] ?? '',
            $d['permanent_country'] ?? ''
        ])));

        fputcsv($output, [
            $r->id,
            $r->application_no,
            $r->created_at,
            $r->student_name,
            $r->gender,
            $r->date_of_birth,
            $d['age'] ?? '',
            '+880' . $r->contact_number,
            $r->birth_reg_no,
            $d['student_nid'] ?? '',
            $r->college_name,
            $d['studying_class'] ?? '',
            $d['studying_id_no'] ?? '',
            $d['studying_group_subject'] ?? '',
            $r->father_name,
            !empty($d['father_mobile']) ? '+880' . $d['father_mobile'] : '',
            $d['father_nid'] ?? '',
            $d['father_occupation'] ?? '',
            $d['father_monthly_income'] ?? '',
            $d['father_is_deceased'] ?? '',
            $r->mother_name,
            !empty($d['mother_mobile']) ? '+880' . $d['mother_mobile'] : '',
            $d['mother_nid'] ?? '',
            $d['mother_occupation'] ?? '',
            $d['mother_monthly_income'] ?? '',
            $d['mother_is_deceased'] ?? '',
            $r->guardian_name,
            !empty($d['guardian_mobile']) ? '+880' . $d['guardian_mobile'] : '',
            $d['guardian_relation'] ?? '',
            $present_addr,
            $permanent_addr,
            $r->district,
            $r->sap_reference,
            !empty($d['sap_reference_mobile']) ? '+880' . $d['sap_reference_mobile'] : '',
            $r->status
        ]);
    }

    fclose($output);
    exit;
}
add_action('admin_post_opf_export_csv', 'opf_export_submissions_csv');

/**
 * AJAX: Update Application Status
 */
function opf_ajax_update_status() {
    check_ajax_referer('opf_admin_nonce_action', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized access.']);
    }

    $app_id     = isset($_POST['app_id']) ? intval($_POST['app_id']) : 0;
    $new_status = isset($_POST['status']) ? sanitize_text_field(strtolower($_POST['status'])) : '';

    $allowed = ['pending', 'approved', 'rejected'];
    if (!$app_id || !in_array($new_status, $allowed, true)) {
        wp_send_json_error(['message' => 'Invalid parameters provided.']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'orphan_applications';

    $updated = $wpdb->update(
        $table_name,
        ['status' => $new_status],
        ['id' => $app_id],
        ['%s'],
        ['%d']
    );

    if ($updated !== false) {
        wp_send_json_success([
            'message'    => 'Status updated to ' . ucfirst($new_status) . ' successfully!',
            'app_id'     => $app_id,
            'new_status' => $new_status,
            'label'      => ucfirst($new_status)
        ]);
    } else {
        wp_send_json_error(['message' => 'Could not update status in database.']);
    }
}
add_action('wp_ajax_opf_update_status', 'opf_ajax_update_status');

/**
 * AJAX: Bulk Delete Applications
 */
function opf_ajax_bulk_delete() {
    check_ajax_referer('opf_admin_nonce_action', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized access.']);
    }

    $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
    $ids = array_values(array_filter($ids, function($id) { return $id > 0; }));

    if (empty($ids)) {
        wp_send_json_error(['message' => 'No valid applications selected.']);
    }

    global $wpdb;
    $table_name    = $wpdb->prefix . 'orphan_applications';
    $old_ids_table = $wpdb->prefix . 'orphan_old_ids';

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));

    // Unlink any old IDs pointing to deleted application records
    $wpdb->query($wpdb->prepare(
        "UPDATE $old_ids_table SET status = 'waiting', application_id = 0 WHERE application_id IN ($placeholders)",
        $ids
    ));

    $query = $wpdb->prepare("DELETE FROM $table_name WHERE id IN ($placeholders)", $ids);
    $deleted = $wpdb->query($query);

    if ($deleted !== false) {
        wp_send_json_success([
            'message'     => $deleted . ' application(s) deleted successfully!',
            'deleted_ids' => $ids,
            'count'       => $deleted
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to delete applications from database.']);
    }
}
add_action('wp_ajax_opf_bulk_delete', 'opf_ajax_bulk_delete');

/**
 * AJAX: Admin Update Application / PDF Fields
 */
function opf_ajax_admin_update_application() {
    check_ajax_referer('opf_admin_nonce_action', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized access.']);
    }

    $app_id = isset($_POST['app_id']) ? intval($_POST['app_id']) : 0;
    if (!$app_id) {
        wp_send_json_error(['message' => 'Invalid application ID.']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'orphan_applications';
    $existing   = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $app_id));

    if (!$existing) {
        wp_send_json_error(['message' => 'Application record not found.']);
    }

    $existing_data = json_decode($existing->form_data, true);
    if (!is_array($existing_data)) {
        $existing_data = [];
    }

    $digits_only = function($key, $max_len = 0) {
        $val = isset($_POST[$key]) ? preg_replace('/[^0-9]/', '', wp_unslash($_POST[$key])) : '';
        return ($max_len > 0 && strlen($val) > $max_len) ? substr($val, 0, $max_len) : $val;
    };

    $text_val = function($key, $default = '') {
        return isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : $default;
    };

    $first_name = $text_val('student_first_name');
    $surname    = $text_val('student_surname');
    $full_name  = trim($first_name . ' ' . $surname);
    if (empty($full_name)) {
        $full_name = $existing->student_name;
    }

    $dob            = $text_val('date_of_birth');
    $age            = isset($_POST['age']) && $_POST['age'] !== '' ? intval($_POST['age']) : '';
    $student_mobile = $digits_only('student_mobile', 11);
    $birth_reg      = $digits_only('birth_registration_number', 17);

    // Sanitize evaluation matrix ratings
    $eval_clean = isset($existing_data['eval']) && is_array($existing_data['eval']) ? $existing_data['eval'] : [];
    if (isset($_POST['eval']) && is_array($_POST['eval'])) {
        $eval_clean = [];
        foreach ($_POST['eval'] as $k => $v) {
            $eval_clean[sanitize_key($k)] = sanitize_text_field(wp_unslash($v));
        }
    }

    $updated_fields = [
        'student_first_name'         => $first_name,
        'student_surname'            => $surname,
        'student_full_name'          => $full_name,
        'gender'                     => $text_val('gender', $existing->gender ?: 'Male'),
        'date_of_birth'              => $dob,
        'age'                        => $age,
        'siblings_brothers'          => isset($_POST['siblings_brothers']) ? intval($_POST['siblings_brothers']) : 0,
        'siblings_sisters'           => isset($_POST['siblings_sisters']) ? intval($_POST['siblings_sisters']) : 0,
        'siblings_position'          => $text_val('siblings_position'),
        'student_mobile'             => $student_mobile,
        'birth_registration_number'  => $birth_reg,
        'student_nid'                => $digits_only('student_nid', 17),
        'college_school_name'        => $text_val('college_school_name'),
        'studying_class'             => $text_val('studying_class'),
        'studying_id_no'             => $text_val('studying_id_no'),
        'studying_group_subject'     => $text_val('studying_group_subject'),

        'father_full_name'           => $text_val('father_full_name'),
        'father_mobile'              => $digits_only('father_mobile', 11),
        'father_nid'                 => $digits_only('father_nid', 17),
        'father_occupation'          => $text_val('father_occupation'),
        'father_monthly_income'      => $text_val('father_monthly_income'),
        'father_death_reg_no'        => $digits_only('father_death_reg_no', 17),
        'father_cause_of_death'      => $text_val('father_cause_of_death'),

        'mother_full_name'           => $text_val('mother_full_name'),
        'mother_mobile'              => $digits_only('mother_mobile', 11),
        'mother_nid'                 => $digits_only('mother_nid', 17),
        'mother_occupation'          => $text_val('mother_occupation'),
        'mother_monthly_income'      => $text_val('mother_monthly_income'),
        'mother_death_reg_no'        => $digits_only('mother_death_reg_no', 17),
        'mother_cause_of_death'      => $text_val('mother_cause_of_death'),

        'guardian_full_name'         => $text_val('guardian_full_name'),
        'guardian_mobile'            => $digits_only('guardian_mobile', 11),
        'guardian_nid'               => $digits_only('guardian_nid', 17),
        'guardian_dob'               => $text_val('guardian_dob'),
        'guardian_relation'          => $text_val('guardian_relation'),

        'present_house'              => $text_val('present_house'),
        'present_village'            => $text_val('present_village'),
        'present_post_office'        => $text_val('present_post_office'),
        'present_police_station'     => $text_val('present_police_station'),
        'present_district'           => $text_val('present_district'),
        'present_country'            => $text_val('present_country', 'Bangladesh'),

        'permanent_house'            => $text_val('permanent_house'),
        'permanent_village'          => $text_val('permanent_village'),
        'permanent_post_office'      => $text_val('permanent_post_office'),
        'permanent_police_station'   => $text_val('permanent_police_station'),
        'permanent_district'         => $text_val('permanent_district'),
        'permanent_country'          => $text_val('permanent_country', 'Bangladesh'),

        'sap_reference_name'         => $text_val('sap_reference_name'),
        'sap_reference_mobile'       => $digits_only('sap_reference_mobile', 11),

        'eval'                       => $eval_clean,
        'applicant_signature_name'   => $text_val('applicant_signature_name'),
        'application_date'           => $text_val('application_date'),
        'authorised_signature_name'  => $text_val('authorised_signature_name'),
        'authorised_signature_date'  => $text_val('authorised_signature_date'),
    ];

    $merged_data = array_merge($existing_data, $updated_fields);

    $updated = $wpdb->update(
        $table_name,
        [
            'student_name'   => $full_name,
            'gender'         => $updated_fields['gender'],
            'date_of_birth'  => !empty($dob) ? $dob : null,
            'contact_number' => $student_mobile,
            'birth_reg_no'   => $birth_reg,
            'college_name'   => $updated_fields['college_school_name'],
            'father_name'    => $updated_fields['father_full_name'],
            'mother_name'    => $updated_fields['mother_full_name'],
            'guardian_name'  => $updated_fields['guardian_full_name'],
            'district'       => $updated_fields['present_district'],
            'sap_reference'  => $updated_fields['sap_reference_name'],
            'form_data'      => wp_json_encode($merged_data),
        ],
        ['id' => $app_id]
    );

    if ($updated !== false) {
        wp_send_json_success([
            'message'        => 'Application PDF updated successfully!',
            'app_id'         => $app_id,
            'application_no' => $existing->application_no ?: $app_id,
            'student_name'   => $full_name,
            'gender'         => $updated_fields['gender'],
            'college_name'   => $updated_fields['college_school_name'],
            'dob_formatted'  => !empty($dob) ? date('d M, Y', strtotime($dob)) : '',
            'age'            => ($age !== '' && $age > 0) ? intval($age) : '',
            'contact_number' => $student_mobile,
            'district'       => $updated_fields['present_district'],
        ]);
    } else {
        wp_send_json_error(['message' => 'Could not save changes to database.']);
    }
}
add_action('wp_ajax_opf_admin_update_application', 'opf_ajax_admin_update_application');

/**
 * Shared Helper: Insert Batch of Admin Old Application IDs (Deduplicating Safely)
 */
function opf_insert_old_ids_batch($items) {
    global $wpdb;
    $old_ids_table = $wpdb->prefix . 'orphan_old_ids';
    $apps_table    = $wpdb->prefix . 'orphan_applications';

    $added   = 0;
    $skipped = 0;
    $invalid = 0;
    $added_ids = [];
    $seen_in_batch = [];

    foreach ($items as $item) {
        $raw_id       = is_array($item) ? ($item['id'] ?? '') : $item;
        $student_name = is_array($item) ? sanitize_text_field(trim((string) ($item['name'] ?? ''))) : '';
        $note         = is_array($item) ? sanitize_text_field(trim((string) ($item['note'] ?? ''))) : '';
        $student_name = substr($student_name, 0, 250);
        $note         = substr($note, 0, 250);

        $clean_id = opf_normalize_old_id($raw_id);
        if (empty($clean_id)) {
            $invalid++;
            continue;
        }

        $key_lower = strtolower($clean_id);
        if (isset($seen_in_batch[$key_lower])) {
            $skipped++;
            continue;
        }
        $seen_in_batch[$key_lower] = true;

        $existing_old = $wpdb->get_row($wpdb->prepare(
            "SELECT id, student_name, note FROM $old_ids_table WHERE old_id = %s LIMIT 1",
            $clean_id
        ));

        if ($existing_old) {
            // Preserve existing row; fill empty name/note if missing without deleting anything
            $meta_update = [];
            if (empty($existing_old->student_name) && !empty($student_name)) {
                $meta_update['student_name'] = $student_name;
            }
            if (empty($existing_old->note) && !empty($note)) {
                $meta_update['note'] = $note;
            }
            if (!empty($meta_update)) {
                $wpdb->update($old_ids_table, $meta_update, ['id' => $existing_old->id]);
            }
            $skipped++;
            continue;
        }

        // If an application with this application_no already exists in applications table, link it automatically
        $existing_app = $wpdb->get_row($wpdb->prepare(
            "SELECT id, student_name FROM $apps_table WHERE application_no = %s LIMIT 1",
            $clean_id
        ));

        $status = $existing_app ? 'submitted' : 'waiting';
        $app_id = $existing_app ? intval($existing_app->id) : 0;
        if (empty($student_name) && $existing_app && !empty($existing_app->student_name)) {
            $student_name = sanitize_text_field($existing_app->student_name);
        }

        $ok = $wpdb->insert($old_ids_table, [
            'old_id'         => $clean_id,
            'student_name'   => $student_name,
            'note'           => $note,
            'status'         => $status,
            'application_id' => $app_id,
            'created_at'     => current_time('mysql'),
        ], ['%s', '%s', '%s', '%s', '%d', '%s']);

        if ($ok) {
            $added++;
            $added_ids[] = $clean_id;
        } else {
            $skipped++;
        }
    }

    return [
        'added'     => $added,
        'skipped'   => $skipped,
        'invalid'   => $invalid,
        'added_ids' => $added_ids,
    ];
}

/**
 * Shared Helper: Convert 2D Grid of Cells into Clean (ID, Name, Note) Entries
 * Supports 3 columns (Note, Name, ID OR ID, Name, Note), 2 columns, or 1 column ID lists.
 */
function opf_extract_id_rows_from_grid($grid) {
    if (empty($grid) || !is_array($grid)) {
        return [];
    }

    // Re-index and trim empty rows
    $clean_grid = [];
    foreach ($grid as $row) {
        if (!is_array($row)) continue;
        $trimmed = array_map(function($val) {
            return trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $val));
        }, array_values($row));
        if (implode('', $trimmed) !== '') {
            $clean_grid[] = $trimmed;
        }
    }

    if (empty($clean_grid)) {
        return [];
    }

    $id_col    = -1;
    $name_col  = -1;
    $note_col  = -1;
    $start_row = 0;
    $first_row = $clean_grid[0];
    $col_count = count($first_row);

    // Detect header row by matching known column labels in row 0
    $has_header = false;
    for ($c = 0; $c < $col_count; $c++) {
        $hdr = strtolower($first_row[$c]);
        if (preg_match('/^(id|app(lication)?[\s_-]*(id|no|number)?|old[\s_-]*id|note|notes|name|student[\s_-]*name|applicant[\s_-]*name|full[\s_-]*name|remark|remarks|comment|sl|serial|s\/n|#)$/', $hdr)) {
            $has_header = true;
            break;
        }
    }

    if ($has_header) {
        $start_row = 1;
        for ($c = 0; $c < $col_count; $c++) {
            $hdr = strtolower($first_row[$c]);
            if ($id_col === -1 && preg_match('/^(id|app(lication)?[\s_-]*(id|no|number)?|old[\s_-]*id)$/', $hdr)) {
                $id_col = $c;
            } elseif ($note_col === -1 && preg_match('/^(note|notes|remark|remarks|comment|comments|batch|description)$/', $hdr)) {
                $note_col = $c;
            } elseif ($name_col === -1 && preg_match('/(name|student|applicant)/', $hdr)) {
                $name_col = $c;
            }
        }
        // Fallback if header row had SL / custom labels
        if ($id_col === -1) {
            for ($c = 0; $c < $col_count; $c++) {
                if ($c === $note_col || $c === $name_col) continue;
                $hdr = strtolower($first_row[$c]);
                if (!preg_match('/^(sl|serial|s\/n|#)$/', $hdr)) {
                    $id_col = $c;
                    break;
                }
            }
        }
    } else {
        // Headerless fallback:
        // - 1 column: Col A = ID
        // - 2 columns: Col A = ID, Col B = Name
        // - 3+ columns: Detect whether Sample order (Note, Name, ID) or standard (ID, Name, Note)
        if ($col_count === 1) {
            $id_col = 0;
        } elseif ($col_count === 2) {
            $id_col   = 0;
            $name_col = 1;
        } else {
            $c0 = trim($first_row[0]);
            $c2 = trim($first_row[2]);
            $c2_looks_like_id = ($c2 !== '' && preg_match('/^[0-9A-Za-z\-_#]{1,32}$/', $c2) && strpos($c2, ' ') === false);
            $c0_looks_like_id = ($c0 !== '' && preg_match('/^[0-9A-Za-z\-_#]{1,32}$/', $c0) && strpos($c0, ' ') === false);
            $c0_has_digits    = preg_match('/\d/', $c0);
            $c2_has_digits    = preg_match('/\d/', $c2);
            if ($c2_looks_like_id && (!$c0_looks_like_id || strpos($c0, ' ') !== false || (!$c0_has_digits && $c2_has_digits))) {
                // Headerless Sample format: Note | Name | ID
                $note_col = 0;
                $name_col = 1;
                $id_col   = 2;
            } else {
                // Default 3 columns: ID | Name | Note
                $id_col   = 0;
                $name_col = 1;
                $note_col = 2;
            }
        }
    }

    if ($id_col < 0) {
        $id_col = 0;
    }

    $items = [];
    for ($r = $start_row; $r < count($clean_grid); $r++) {
        $row = $clean_grid[$r];
        $val = isset($row[$id_col]) ? trim($row[$id_col]) : '';
        if ($val === '' || preg_match('/^(id|application[\s_-]*id|old[\s_-]*id)$/i', $val)) {
            continue;
        }
        $name = ($name_col >= 0 && isset($row[$name_col])) ? trim($row[$name_col]) : '';
        $note = ($note_col >= 0 && isset($row[$note_col])) ? trim($row[$note_col]) : '';
        $items[] = [
            'id'   => $val,
            'name' => $name,
            'note' => $note,
        ];
    }

    return $items;
}

/**
 * Shared Helper: Convert Excel Column Letters (e.g. "A", "B", "AA") to 0-based index
 */
function opf_excel_col_to_index($cell_ref) {
    if (!preg_match('/^([A-Za-z]+)/', (string) $cell_ref, $m)) {
        return -1;
    }
    $letters = strtoupper($m[1]);
    $index = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
        $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
    }
    return $index - 1;
}

/**
 * Shared Helper: Parse .xlsx/.xls/.csv Spreadsheet into Application ID Pairs
 */
function opf_parse_old_ids_spreadsheet($tmp_path, $ext) {
    $ext = strtolower((string) $ext);

    if ($ext === 'xlsx') {
        if (!class_exists('PclZip')) {
            require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
        }
        $zip = new PclZip($tmp_path);
        $list = $zip->listContent();
        if (!is_array($list) || empty($list)) {
            return new WP_Error('invalid_xlsx', 'Could not read .xlsx file archive.');
        }

        $shared_entry = null;
        $sheet_entry  = null;
        foreach ($list as $entry) {
            $name = str_replace('\\', '/', $entry['filename']);
            if ($name === 'xl/sharedStrings.xml') {
                $shared_entry = $entry['filename'];
            } elseif (preg_match('#^xl/worksheets/sheet\d+\.xml$#i', $name)) {
                if ($sheet_entry === null || strtolower($name) === 'xl/worksheets/sheet1.xml') {
                    $sheet_entry = $entry['filename'];
                }
            }
        }

        if ($sheet_entry === null) {
            return new WP_Error('no_sheet', 'No worksheet found inside uploaded .xlsx file.');
        }

        $prev_err = libxml_use_internal_errors(true);

        $shared_strings = [];
        if ($shared_entry !== null) {
            $ss_res = $zip->extract(PCLZIP_OPT_BY_NAME, $shared_entry, PCLZIP_OPT_EXTRACT_AS_STRING);
            if (!empty($ss_res[0]['content'])) {
                $ss_xml = simplexml_load_string($ss_res[0]['content'], 'SimpleXMLElement', LIBXML_NONET);
                if ($ss_xml) {
                    $ss_xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                    $si_nodes = $ss_xml->xpath('//m:si');
                    if (empty($si_nodes)) {
                        $si_nodes = $ss_xml->si;
                    }
                    foreach ($si_nodes as $si) {
                        $si->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                        $t_nodes = $si->xpath('.//m:t');
                        if (empty($t_nodes)) {
                            $text = (string) $si->t;
                        } else {
                            $parts = [];
                            foreach ($t_nodes as $tn) {
                                $parts[] = (string) $tn;
                            }
                            $text = implode('', $parts);
                        }
                        $shared_strings[] = $text;
                    }
                }
            }
        }

        $ws_res = $zip->extract(PCLZIP_OPT_BY_NAME, $sheet_entry, PCLZIP_OPT_EXTRACT_AS_STRING);
        if (empty($ws_res[0]['content'])) {
            libxml_clear_errors();
            libxml_use_internal_errors($prev_err);
            return new WP_Error('empty_sheet', 'Worksheet content is empty.');
        }

        $ws_xml = simplexml_load_string($ws_res[0]['content'], 'SimpleXMLElement', LIBXML_NONET);
        if (!$ws_xml) {
            libxml_clear_errors();
            libxml_use_internal_errors($prev_err);
            return new WP_Error('bad_xml', 'Malformed XML inside uploaded .xlsx file.');
        }

        $grid = [];
        $rows = isset($ws_xml->sheetData->row) ? $ws_xml->sheetData->row : [];
        foreach ($rows as $row_node) {
            $row_arr = [];
            $col_fallback = 0;
            foreach ($row_node->c as $cell) {
                $ref = (string) $cell['r'];
                $col_idx = $ref !== '' ? opf_excel_col_to_index($ref) : $col_fallback;
                if ($col_idx < 0) $col_idx = $col_fallback;
                $col_fallback = $col_idx + 1;

                $type = (string) $cell['t'];
                $val  = '';
                if ($type === 's') {
                    $idx = intval((string) $cell->v);
                    $val = isset($shared_strings[$idx]) ? $shared_strings[$idx] : '';
                } elseif ($type === 'inlineStr') {
                    $val = isset($cell->is->t) ? (string) $cell->is->t : '';
                } else {
                    $val = isset($cell->v) ? (string) $cell->v : '';
                }
                $row_arr[$col_idx] = $val;
            }
            if (!empty($row_arr)) {
                $max_col = max(array_keys($row_arr));
                $dense   = array_fill(0, $max_col + 1, '');
                foreach ($row_arr as $k => $v) {
                    $dense[$k] = $v;
                }
                $grid[] = $dense;
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($prev_err);
        return opf_extract_id_rows_from_grid($grid);
    }

    // Handle .xls (XML SpreadsheetML, HTML table exports, or CSV/TSV) and .csv/.txt
    $raw_head = file_get_contents($tmp_path, false, null, 0, 8192);
    if ($raw_head === false) {
        return new WP_Error('read_failed', 'Unable to read uploaded file.');
    }

    // Reject binary legacy BIFF8 .xls politely since PHP cannot parse OLE2 binary directly without libraries
    if ($ext === 'xls' && substr($raw_head, 0, 4) === "\xD0\xCF\x11\xE0") {
        return new WP_Error('legacy_xls', 'Legacy binary .xls files are not supported directly. Please save the file as .xlsx or .csv in Excel and upload again.');
    }

    $content_full = file_get_contents($tmp_path);
    if (stripos($raw_head, '<table') !== false || stripos($raw_head, '<tr') !== false) {
        $prev_err = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $content_full, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        $grid = [];
        foreach ($dom->getElementsByTagName('tr') as $tr) {
            $row_arr = [];
            foreach ($tr->childNodes as $cell) {
                if ($cell->nodeType === XML_ELEMENT_NODE && in_array(strtolower($cell->nodeName), ['td', 'th'], true)) {
                    $row_arr[] = trim($cell->textContent);
                }
            }
            if (!empty($row_arr)) {
                $grid[] = $row_arr;
            }
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prev_err);
        return opf_extract_id_rows_from_grid($grid);
    }

    if (stripos($raw_head, '<Workbook') !== false || stripos($raw_head, 'spreadsheet') !== false) {
        $prev_err = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content_full, 'SimpleXMLElement', LIBXML_NONET);
        if ($xml) {
            $grid = [];
            $xml->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
            $rows = $xml->xpath('//ss:Row');
            if (empty($rows)) $rows = $xml->xpath('//Row');
            foreach ($rows as $row_node) {
                $row_arr = [];
                foreach ($row_node->children() as $cell) {
                    $row_arr[] = trim((string) $cell->Data ?: (string) $cell);
                }
                if (!empty($row_arr)) $grid[] = $row_arr;
            }
            libxml_clear_errors();
            libxml_use_internal_errors($prev_err);
            return opf_extract_id_rows_from_grid($grid);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prev_err);
    }

    // Standard CSV / TSV / semicolon-delimited parsing
    $delimiter = ',';
    if (strpos($raw_head, "\t") !== false && strpos($raw_head, ',') === false) {
        $delimiter = "\t";
    } elseif (strpos($raw_head, ';') !== false && strpos($raw_head, ',') === false) {
        $delimiter = ';';
    }

    $grid = [];
    if (($handle = fopen($tmp_path, 'r')) !== false) {
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!empty($row)) {
                $grid[] = $row;
            }
        }
        fclose($handle);
    }

    return opf_extract_id_rows_from_grid($grid);
}

/**
 * AJAX: Admin Manual Add Old Application ID(s)
 */
function opf_ajax_add_old_ids() {
    check_ajax_referer('opf_admin_nonce_action', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized access.']);
    }

    $raw_ids      = isset($_POST['old_ids']) ? wp_unslash($_POST['old_ids']) : '';
    $student_name = isset($_POST['student_name']) ? sanitize_text_field(wp_unslash($_POST['student_name'])) : '';
    $note         = isset($_POST['note']) ? sanitize_text_field(wp_unslash($_POST['note'])) : '';

    $tokens = preg_split('/[\r\n,;]+/', (string) $raw_ids);
    $items  = [];
    if (is_array($tokens)) {
        foreach ($tokens as $tok) {
            $tok = trim($tok);
            if ($tok !== '') {
                $items[] = ['id' => $tok, 'name' => $student_name, 'note' => $note];
            }
        }
    }

    if (empty($items)) {
        wp_send_json_error(['message' => 'Please enter at least one valid Application ID.']);
    }

    $result = opf_insert_old_ids_batch($items);

    if ($result['added'] === 0 && $result['skipped'] === 0) {
        wp_send_json_error(['message' => 'No valid Application IDs found in input.']);
    }

    $msg = $result['added'] . ' ID(s) added successfully.';
    if ($result['skipped'] > 0) {
        $msg .= ' (' . $result['skipped'] . ' duplicate skipped)';
    }

    wp_send_json_success(array_merge($result, ['message' => $msg]));
}
add_action('wp_ajax_opf_add_old_ids', 'opf_ajax_add_old_ids');

/**
 * AJAX: Admin Upload Excel / CSV of Old Application IDs
 */
function opf_ajax_upload_old_ids_excel() {
    check_ajax_referer('opf_admin_nonce_action', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized access.']);
    }

    if (empty($_FILES['excel_file']) || !is_uploaded_file($_FILES['excel_file']['tmp_name'])) {
        wp_send_json_error(['message' => 'Please select an Excel (.xlsx / .xls) or CSV file to upload.']);
    }

    $file     = $_FILES['excel_file'];
    $filename = sanitize_file_name($file['name']);
    $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'], true)) {
        wp_send_json_error(['message' => 'Unsupported file type. Please upload a .xlsx, .xls, or .csv file.']);
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        wp_send_json_error(['message' => 'File size exceeds 10MB limit.']);
    }

    $items = opf_parse_old_ids_spreadsheet($file['tmp_name'], $ext);
    if (is_wp_error($items)) {
        wp_send_json_error(['message' => $items->get_error_message()]);
    }

    if (empty($items)) {
        wp_send_json_error(['message' => 'No Application IDs found in the uploaded file. Check that your file includes an ID column.']);
    }

    $result = opf_insert_old_ids_batch($items);

    $msg = $result['added'] . ' ID(s) imported from "' . $filename . '".';
    if ($result['skipped'] > 0) {
        $msg .= ' ' . $result['skipped'] . ' duplicate(s) skipped.';
    }
    if ($result['added'] === 0 && $result['skipped'] > 0) {
        $msg = 'All ' . $result['skipped'] . ' ID(s) in "' . $filename . '" already exist in the list.';
    }

    wp_send_json_success(array_merge($result, ['message' => $msg]));
}
add_action('wp_ajax_opf_upload_old_ids_excel', 'opf_ajax_upload_old_ids_excel');

/**
 * AJAX: Admin Delete Old Application ID(s)
 */
function opf_ajax_delete_old_ids() {
    check_ajax_referer('opf_admin_nonce_action', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized access.']);
    }

    $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
    $ids = array_values(array_filter($ids, function($id) { return $id > 0; }));

    if (empty($ids)) {
        wp_send_json_error(['message' => 'No Old IDs selected for deletion.']);
    }

    global $wpdb;
    $old_ids_table = $wpdb->prefix . 'orphan_old_ids';
    $apps_table    = $wpdb->prefix . 'orphan_applications';
    $placeholders  = implode(',', array_fill(0, count($ids), '%d'));

    // Also delete any submitted applications of type 'old' linked strictly to these Old ID rows
    $linked_app_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT application_id FROM $old_ids_table WHERE id IN ($placeholders) AND application_id > 0",
        $ids
    ));

    if (!empty($linked_app_ids)) {
        $linked_app_ids = array_values(array_filter(array_map('intval', $linked_app_ids)));
        if (!empty($linked_app_ids)) {
            $app_ph = implode(',', array_fill(0, count($linked_app_ids), '%d'));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $apps_table WHERE id IN ($app_ph) AND application_type = 'old'",
                $linked_app_ids
            ));
        }
    }

    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM $old_ids_table WHERE id IN ($placeholders)",
        $ids
    ));

    if ($deleted !== false) {
        wp_send_json_success([
            'message'     => $deleted . ' Old ID(s) deleted successfully!',
            'deleted_ids' => $ids,
            'count'       => $deleted
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to delete selected Old IDs.']);
    }
}
add_action('wp_ajax_opf_delete_old_ids', 'opf_ajax_delete_old_ids');

/**
 * Download Sample CSV Template for Old Application IDs Import
 */
function opf_download_old_sample_csv() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access.');
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=old_application_ids_sample.csv');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['Note', 'Name', 'ID']);
    fputcsv($out, ['Batch 2026 Record', 'Sample Student One', '1001']);
    fputcsv($out, ['Batch 2026 Record', 'Sample Student Two', '1002']);
    fputcsv($out, ['Previous Year', 'Sample Student Three', 'SAP-2026-01']);
    fclose($out);
    exit;
}
add_action('admin_post_opf_download_old_sample_csv', 'opf_download_old_sample_csv');


