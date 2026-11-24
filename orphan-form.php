<?php
/**
 * Plugin Name:       Orphan Project Multi-Step Form
 * Plugin URI:        https://example.com/
 * Description:       Complete multi-step registration and evaluation form for Orphan Project based on SAP & HASENE format. Includes shortcode [orphan_registration_form] or [orphan_form], admin management panel, and CSV export.
 * Version:           1.0.0
 * Author:            Antigravity
 * Text Domain:       orphan-project-form
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('OPF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OPF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OPF_VERSION', '1.0.31');

/**
 * Create Database Table on Plugin Activation
 */
function opf_install_database() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'orphan_applications';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        application_no varchar(50) NOT NULL,
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
        KEY application_no (application_no)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'opf_install_database');

/**
 * Check and ensure table exists on admin init (safety check)
 */
function opf_check_db_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'orphan_applications';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        opf_install_database();
    }
}
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
 * Handle AJAX Form Submission
 */
function opf_handle_ajax_submission() {
    check_ajax_referer('orphan_form_nonce_action', 'orphan_form_nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'orphan_applications';

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
        'age'                        => isset($_POST['age']) ? intval($_POST['age']) : 0,
        'siblings_brothers'          => isset($_POST['siblings_brothers']) ? intval($_POST['siblings_brothers']) : 0,
        'siblings_sisters'           => isset($_POST['siblings_sisters']) ? intval($_POST['siblings_sisters']) : 0,
        'siblings_position'          => isset($_POST['siblings_position']) ? sanitize_text_field($_POST['siblings_position']) : '',
        'student_mobile'             => $mobile,
        'birth_registration_number'  => $birth_reg,
        'student_nid'                => isset($_POST['student_nid']) ? sanitize_text_field($_POST['student_nid']) : '',
        'college_school_name'        => isset($_POST['college_school_name']) ? sanitize_text_field($_POST['college_school_name']) : '',
        'studying_id_no'             => isset($_POST['studying_id_no']) ? sanitize_text_field($_POST['studying_id_no']) : '',
        'studying_group_subject'     => isset($_POST['studying_group_subject']) ? sanitize_text_field($_POST['studying_group_subject']) : '',

        // 2. Family & Guardians Information
        'father_full_name'           => isset($_POST['father_full_name']) ? sanitize_text_field($_POST['father_full_name']) : '',
        'father_mobile'              => isset($_POST['father_mobile']) ? sanitize_text_field($_POST['father_mobile']) : '',
        'father_nid'                 => isset($_POST['father_nid']) ? sanitize_text_field($_POST['father_nid']) : '',
        'father_occupation'          => isset($_POST['father_occupation']) ? sanitize_text_field($_POST['father_occupation']) : '',
        'father_monthly_income'      => isset($_POST['father_monthly_income']) ? sanitize_text_field($_POST['father_monthly_income']) : '',
        'father_is_deceased'         => (isset($_POST['father_is_deceased']) && $_POST['father_is_deceased'] === 'yes') ? 'yes' : 'no',
        'father_death_reg_no'        => isset($_POST['father_death_reg_no']) ? sanitize_text_field($_POST['father_death_reg_no']) : '',
        'father_cause_of_death'      => isset($_POST['father_cause_of_death']) ? sanitize_text_field($_POST['father_cause_of_death']) : '',

        'mother_full_name'           => isset($_POST['mother_full_name']) ? sanitize_text_field($_POST['mother_full_name']) : '',
        'mother_mobile'              => isset($_POST['mother_mobile']) ? sanitize_text_field($_POST['mother_mobile']) : '',
        'mother_nid'                 => isset($_POST['mother_nid']) ? sanitize_text_field($_POST['mother_nid']) : '',
        'mother_occupation'          => isset($_POST['mother_occupation']) ? sanitize_text_field($_POST['mother_occupation']) : '',
        'mother_monthly_income'      => isset($_POST['mother_monthly_income']) ? sanitize_text_field($_POST['mother_monthly_income']) : '',
        'mother_is_deceased'         => (isset($_POST['mother_is_deceased']) && $_POST['mother_is_deceased'] === 'yes') ? 'yes' : 'no',
        'mother_death_reg_no'        => isset($_POST['mother_death_reg_no']) ? sanitize_text_field($_POST['mother_death_reg_no']) : '',
        'mother_cause_of_death'      => isset($_POST['mother_cause_of_death']) ? sanitize_text_field($_POST['mother_cause_of_death']) : '',

        'has_separate_guardian'      => (isset($_POST['has_separate_guardian']) && $_POST['has_separate_guardian'] === 'yes') ? 'yes' : 'no',
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

        // 4. Education Status & Signatures
        'eval'                       => $eval_clean,
        'applicant_signature_name'   => isset($_POST['applicant_signature_name']) ? sanitize_text_field($_POST['applicant_signature_name']) : '',
        'application_date'           => isset($_POST['application_date']) ? sanitize_text_field($_POST['application_date']) : date('Y-m-d'),
        'digital_signature_data'     => isset($_POST['digital_signature_data']) ? sanitize_textarea_field($_POST['digital_signature_data']) : '',
    ];

    // Generate unique Application ID: SAP-YYYYMMDD-RANDOM
    $application_no = 'SAP-' . date('Ymd') . '-' . rand(1000, 9999);

    // Insert into DB
    $inserted = $wpdb->insert($table_name, [
        'application_no' => $application_no,
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
        'status'         => 'pending',
        'created_at'     => current_time('mysql')
    ]);

    if ($inserted) {
        $view_url = add_query_arg('opf_view_app', $application_no, home_url('/'));
        wp_send_json_success([
            'message'        => 'Application submitted successfully!',
            'application_no' => $application_no,
            'id'             => $wpdb->insert_id,
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
        $app = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE application_no = %s", $app_no));
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
        $mobile       = isset($_POST['existing_mobile']) ? sanitize_text_field(trim($_POST['existing_mobile'])) : '';

        if (empty($lookup) || empty($student_name) || empty($mobile)) {
            wp_send_json_error(['message' => 'Please provide Application ID / Birth Reg No, Student Name, and Contact Number so we can verify your existing application.']);
        }

        $clean_lookup = preg_replace('/[^0-9A-Za-z\-]/', '', $lookup);
        $clean_mobile = preg_replace('/[^0-9]/', '', $mobile);
        if (strpos($clean_mobile, '880') === 0) {
            $clean_mobile = substr($clean_mobile, 3);
        }
        if (strpos($clean_mobile, '0') === 0) {
            $clean_mobile = substr($clean_mobile, 1);
        }

        // Find existing record
        $existing_app = null;

        // 1. Check by ID if numeric and within realistic application ID range
        if (is_numeric($clean_lookup) && intval($clean_lookup) > 0 && intval($clean_lookup) < 1000000) {
            $existing_app = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d LIMIT 1",
                intval($clean_lookup)
            ));
        }

        // 2. Check by application_no or birth_reg_no (with or without spaces)
        if (!$existing_app) {
            $existing_app = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE application_no = %s OR REPLACE(birth_reg_no, ' ', '') = %s OR birth_reg_no = %s LIMIT 1",
                $clean_lookup,
                $clean_lookup,
                $lookup
            ));
        }

        // 3. Fallback: Check by Full Name AND Mobile Number
        if (!$existing_app && !empty($student_name) && !empty($clean_mobile)) {
            $existing_app = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE student_name LIKE %s AND (contact_number LIKE %s OR contact_number LIKE %s) LIMIT 1",
                '%' . $wpdb->esc_like($student_name) . '%',
                '%' . $wpdb->esc_like($clean_mobile) . '%',
                '%' . $wpdb->esc_like('0' . $clean_mobile) . '%'
            ));
        }

        if (!$existing_app) {
            wp_send_json_error(['message' => 'No existing application found matching ID or Birth Reg "' . esc_html($lookup) . '". Please check your details or select "New Applicant".']);
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
            'message'        => 'Completed form uploaded and linked to Application #' . $existing_app->id . ' (' . $existing_app->student_name . ') successfully!',
            'application_no' => $existing_app->application_no,
            'id'             => $existing_app->id,
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
        $application_no = 'SAP-' . date('Ymd') . '-' . rand(1000, 9999);

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
                'id'             => $new_id,
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
    $table_name = $wpdb->prefix . 'orphan_applications';

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
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

