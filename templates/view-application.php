<?php
/**
 * Standalone PDF-Identical Application View & Download Page
 * Perfectly matching the original 'Orphan Information New 19_5_26.pdf' (Image 2)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure $app is available
if (!isset($app) || empty($app)) {
    wp_die('Application record not found.');
}

$data = json_decode($app->form_data, true);
if (!is_array($data)) {
    $data = [];
}

$eval = isset($data['eval']) && is_array($data['eval']) ? $data['eval'] : [];

$left_logo_url  = OPF_PLUGIN_URL . 'assets/images/logo-sap.png';
$right_logo_url = OPF_PLUGIN_URL . 'assets/images/logo-hasene.png';

// Helper to render exactly $total_boxes individual square boxes
if (!function_exists('opf_render_boxes')) {
function opf_render_boxes($value, $total_boxes) {
    $clean_val = preg_replace('/[^0-9A-Za-z]/', '', (string)$value);
    $html = '<div class="opf-digit-boxes-row">';
    for ($i = 0; $i < $total_boxes; $i++) {
        $char = isset($clean_val[$i]) ? $clean_val[$i] : '&nbsp;';
        $html .= '<span class="opf-digit-box">' . $char . '</span>';
    }
    $html .= '</div>';
    return $html;
}
}

// Helper for phone row: 3 empty boxes + [+] [8] [8] [0] + 10 digit boxes (total 17 boxes matching birth reg)
if (!function_exists('opf_render_phone_boxes')) {
function opf_render_phone_boxes($phone_value) {
    $digits = preg_replace('/[^0-9]/', '', (string)$phone_value);
    // Remove leading 880 or 0 if user entered it
    if (strlen($digits) > 10 && substr($digits, 0, 3) === '880') {
        $digits = substr($digits, 3);
    }
    if (strlen($digits) === 11 && substr($digits, 0, 1) === '0') {
        $digits = substr($digits, 1);
    }

    $html = '<div class="opf-digit-boxes-row">';
    // 3 empty spacer boxes to align with the 17-box row
    $html .= '<span class="opf-digit-box opf-box-empty">&nbsp;</span>';
    $html .= '<span class="opf-digit-box opf-box-empty">&nbsp;</span>';
    $html .= '<span class="opf-digit-box opf-box-empty">&nbsp;</span>';
    
    // Prefix + 8 8 0
    $html .= '<span class="opf-digit-box opf-prefix-box">+</span>';
    $html .= '<span class="opf-digit-box opf-prefix-box">8</span>';
    $html .= '<span class="opf-digit-box opf-prefix-box">8</span>';
    $html .= '<span class="opf-digit-box opf-prefix-box">0</span>';

    // 10 digit boxes
    for ($i = 0; $i < 10; $i++) {
        $char = isset($digits[$i]) ? $digits[$i] : '&nbsp;';
        $html .= '<span class="opf-digit-box">' . $char . '</span>';
    }
    $html .= '</div>';
    return $html;
}
}

// Helper to render radio circle indicator for rating
if (!function_exists('opf_eval_radio')) {
function opf_eval_radio($metric_key, $expected_value, $eval_array) {
    $selected = isset($eval_array[$metric_key]) && (
        strtolower(str_replace(' ', '', $eval_array[$metric_key])) === strtolower(str_replace(' ', '', $expected_value))
    );
    return $selected ? '<span class="opf-circle selected">●</span>' : '<span class="opf-circle">◯</span>';
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application - <?php echo esc_html($app->application_no); ?> - Orphan Project</title>
    <style>
        /* Printable A4 PDF Form Styles matching Image 2 */
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            background: #52616b;
            margin: 0;
            padding: 30px 0 80px;
            font-family: Arial, Helvetica, "Nimbus Sans L", sans-serif;
            color: #000000;
        }

        /* Floating / Sticky Control Bar */
        .opf-view-controls {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #0f172a;
            color: #ffffff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            z-index: 9999;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.35);
            font-family: Arial, sans-serif;
        }

        .opf-action-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .opf-ctl-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13.5px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .opf-btn-download {
            background: #16a34a;
            color: #ffffff;
        }

        .opf-btn-download:hover {
            background: #15803d;
        }

        .opf-btn-back {
            background: #334155;
            color: #ffffff;
        }

        .opf-btn-back:hover {
            background: #1e293b;
        }

        /* Zoom Controls in Toolbar */
        .opf-zoom-controls {
            display: flex;
            align-items: center;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 6px;
            padding: 2px;
            gap: 2px;
        }

        .opf-zoom-btn {
            background: transparent;
            color: #ffffff;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
            transition: background 0.2s;
        }

        .opf-zoom-btn:hover {
            background: #334155;
        }

        .opf-zoom-label {
            background: transparent;
            color: #38bdf8;
            border: none;
            font-size: 12px;
            font-weight: bold;
            padding: 0 8px;
            min-width: 42px;
            text-align: center;
            cursor: pointer;
        }

        .opf-zoom-label:hover {
            text-decoration: underline;
        }

        /* Floating Zoom Pill for Mobile Touch */
        .opf-floating-zoom {
            position: fixed;
            bottom: 22px;
            right: 18px;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 30px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.45);
            display: flex;
            align-items: center;
            padding: 4px 6px;
            gap: 3px;
            z-index: 9998;
        }

        .opf-float-btn {
            background: transparent;
            color: #ffffff;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.2s ease;
        }

        .opf-float-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .opf-float-fit {
            border-radius: 14px;
            width: auto;
            padding: 0 10px;
            font-size: 12px;
            color: #38bdf8;
        }

        /* Outer Viewer Wrapper & A4 Page Container */
        .opf-pdf-outer-wrapper {
            width: 100%;
            margin-top: 60px;
            padding: 10px 0 60px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
        }

        .opf-pdf-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
            transform-origin: top center;
            transition: transform 0.15s ease-out;
        }

        .opf-a4-page {
            width: 210mm;
            min-height: 297mm;
            padding: 8mm 12mm 8mm;
            background: #ffffff;
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.25);
            position: relative;
            font-size: 10.5px;
            line-height: 1.25;
            box-sizing: border-box;
        }

        /* Styles active during html2pdf export */
        .opf-pdf-exporting {
            margin: 0 !important;
            gap: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            width: 210mm !important;
        }

        .opf-pdf-exporting .opf-a4-page {
            box-shadow: none !important;
            margin: 0 !important;
            width: 210mm !important;
            min-height: 0 !important;
            height: 1115px !important;
            max-height: 1115px !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
        }

        .opf-pdf-exporting .opf-page-1 {
            page-break-after: always !important;
            break-after: page !important;
        }

        .opf-pdf-exporting .opf-page-2 {
            page-break-after: avoid !important;
            break-after: avoid !important;
        }

        /* Spinner animation for button */
        @keyframes opf-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .opf-spin {
            animation: opf-spin 1s linear infinite;
        }
        .opf-btn-download.is-loading {
            opacity: 0.85;
            cursor: wait;
        }

        /* Header matching original PDF */
        .opf-pdf-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            padding-bottom: 2px;
        }

        .opf-pdf-logo-img {
            height: 56px;
            width: auto;
            object-fit: contain;
        }

        .opf-pdf-header-title {
            font-family: "Times New Roman", Times, Georgia, serif;
            font-size: 32px;
            font-weight: 700;
            font-style: italic;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 0;
            text-align: center;
            color: #000000;
        }

        /* Underlined Section Headings matching PDF (Image 2) */
        .opf-pdf-section-title {
            text-align: center;
            font-size: 12.5px;
            font-weight: bold;
            text-decoration: underline;
            margin: 6px 0 3px;
            color: #000000;
            letter-spacing: 0.2px;
        }

        /* =========================================================
           Exact PDF Table Styling (Image 2)
           ========================================================= */
        .opf-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
            margin-bottom: 5px;
        }

        .opf-table td,
        .opf-table th {
            border: 1px solid #000000;
            padding: 2.5px 5px;
            vertical-align: middle;
            font-size: 10.5px;
            color: #000000;
        }

        .opf-lbl {
            font-size: 10.5px;
            font-weight: normal;
            color: #000000;
            white-space: nowrap;
        }

        .opf-sub-lbl {
            font-size: 9px;
            color: #718096;
            margin-bottom: 2px;
        }

        .opf-val {
            font-size: 11px;
            font-weight: normal;
            color: #000000;
        }

        /* Digit Boxes (Image 2) */
        .opf-digit-boxes-row {
            display: flex;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        .opf-digit-box {
            width: 20px;
            height: 20px;
            border: 1px solid #000000;
            margin-left: -1px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: monospace, Arial, sans-serif;
            font-size: 11px;
            font-weight: bold;
            background: #ffffff;
            color: #000000;
            flex-shrink: 0;
            box-sizing: border-box;
        }

        .opf-digit-box:first-child {
            margin-left: 0;
        }

        .opf-prefix-box {
            font-size: 10.5px;
            font-weight: bold;
        }

        .opf-box-empty {
            background: #ffffff;
        }

        /* Page 2 Rating Matrix Table */
        .opf-matrix-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
            margin-top: 10px;
            margin-bottom: 14px;
        }

        .opf-matrix-table th,
        .opf-matrix-table td {
            border: 1px solid #000000;
            padding: 7px 10px;
            font-size: 12px;
        }

        .opf-matrix-table thead th {
            font-weight: bold;
            text-align: center;
        }

        .opf-matrix-table thead th:first-child {
            text-align: left;
            width: 44%;
        }

        .opf-matrix-table td:not(:first-child) {
            text-align: center;
        }

        .opf-circle {
            font-size: 18px;
            line-height: 1;
            display: inline-block;
        }

        .opf-circle.selected {
            color: #000000;
            font-size: 16px;
        }

        .opf-note-para {
            font-size: 10.5px;
            color: #000000;
            margin: 8px 0 35px;
        }

        /* Page 2 Signatures Footer */
        .opf-signatures-row {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            padding: 0 10px;
        }

        .opf-sig-block {
            width: 260px;
        }

        .opf-sig-line {
            border-top: 1px solid #000000;
            margin-bottom: 6px;
            width: 100%;
        }

        .opf-sig-title {
            font-size: 11px;
            margin-bottom: 8px;
        }

        .opf-sig-item {
            font-size: 11px;
            margin-bottom: 4px;
        }

        /* Mobile Screen Responsiveness */
        @media screen and (max-width: 768px) {
            .opf-view-controls {
                padding: 10px 12px;
                gap: 8px;
            }

            .opf-action-buttons {
                width: 100%;
                justify-content: stretch;
                gap: 8px;
            }

            .opf-ctl-btn {
                flex: 1;
                justify-content: center;
                padding: 9px 10px;
                font-size: 13px;
            }

            .opf-zoom-controls {
                display: none; /* Mobile uses floating thumb pill */
            }

            .opf-pdf-outer-wrapper {
                margin-top: 75px;
                padding: 10px 4px 80px;
            }
        }

        @media screen and (min-width: 769px) {
            .opf-floating-zoom {
                display: none; /* Desktop uses toolbar zoom controls */
            }
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .opf-no-print {
                display: none !important;
            }

            .opf-pdf-outer-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            }

            .opf-pdf-container {
                margin: 0 !important;
                gap: 0 !important;
                transform: none !important;
                zoom: 1 !important;
            }

            .opf-a4-page {
                box-shadow: none !important;
                margin: 0 !important;
                width: 100% !important;
                min-height: auto !important;
                page-break-after: always !important;
                break-after: page !important;
                padding: 10mm 15mm !important;
            }

            @page {
                size: A4 portrait;
                margin: 8mm 12mm;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Toolbar (Hidden during printing) -->
    <div class="opf-view-controls opf-no-print">
        <?php if (empty($_GET['opf_admin'])) : ?>
        <div class="opf-action-buttons">
            <button type="button" class="opf-ctl-btn opf-btn-download" id="opf-btn-download" onclick="downloadApplicationPDF();">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>Download PDF</span>
            </button>
            <a href="<?php echo esc_url(remove_query_arg(['opf_view_app', 'opf_admin', 'opf_auto_download'])); ?>" class="opf-ctl-btn opf-btn-back">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                <span>Submit Another Application</span>
            </a>
        </div>
        <?php endif; ?>
        <div class="opf-zoom-controls">
            <button type="button" class="opf-zoom-btn" onclick="zoomOut();" title="Zoom Out" aria-label="Zoom Out">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </button>
            <button type="button" class="opf-zoom-label" id="opf-zoom-val" onclick="resetZoom();" title="Reset to Fit Screen">
                Fit
            </button>
            <button type="button" class="opf-zoom-btn" onclick="zoomIn();" title="Zoom In" aria-label="Zoom In">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </button>
        </div>
    </div>

    <!-- Floating Thumb Zoom Pill for Mobile Touch (Hidden during printing) -->
    <div class="opf-floating-zoom opf-no-print">
        <button type="button" class="opf-float-btn" onclick="zoomOut();" title="Zoom Out" aria-label="Zoom Out">−</button>
        <button type="button" class="opf-float-btn opf-float-fit" id="opf-float-zoom-val" onclick="resetZoom();" title="Fit to Screen">Fit</button>
        <button type="button" class="opf-float-btn" onclick="zoomIn();" title="Zoom In" aria-label="Zoom In">+</button>
    </div>

    <!-- Outer scrollable viewer wrapper with Auto-Fit & Zoom support -->
    <div class="opf-pdf-outer-wrapper" id="opf-pdf-outer-wrapper">
        <div class="opf-pdf-container" id="opf-pdf-content">

        <!-- ========================================================
             PAGE 1: Exact layout matching Image 2
             ======================================================== -->
        <div class="opf-a4-page opf-page-1">
            <!-- Header with 2 logos and center title -->
            <div class="opf-pdf-header">
                <img src="<?php echo esc_url($left_logo_url); ?>" alt="SAP" class="opf-pdf-logo-img">
                <h1 class="opf-pdf-header-title">ORPHAN PROJECT</h1>
                <img src="<?php echo esc_url($right_logo_url); ?>" alt="HASENE" class="opf-pdf-logo-img">
            </div>

            <!-- 1. Student's Personal Information (Image 2) -->
            <div class="opf-pdf-section-title">Student's Personal Information</div>
            <table class="opf-table">
                <!-- Row 1: Full Name -->
                <tr>
                    <td style="width: 135px;" class="opf-lbl">Full Name:</td>
                    <td style="width: 50%;">
                        <div class="opf-val"><?php echo esc_html($data['student_first_name'] ?? ''); ?>&nbsp;</div>
                    </td>
                    <td>
                        <div class="opf-val"><?php echo esc_html($data['student_surname'] ?? ''); ?>&nbsp;</div>
                    </td>
                </tr>

                <!-- Row 2: Gender, DOB, Age -->
                <tr>
                    <td class="opf-lbl">Gender:</td>
                    <td>
                        <span class="opf-val"><?php echo esc_html($data['gender'] ?? 'Male'); ?></span>
                    </td>
                    <td style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="border: none; border-right: 1px solid #000; width: 38%;" class="opf-lbl">Date of Birth:</td>
                                <td style="border: none; border-right: 1px solid #000; width: 38%;">
                                    <span class="opf-val">
                                        <?php 
                                        echo !empty($data['date_of_birth']) ? date('d/m/Y', strtotime($data['date_of_birth'])) : 'DD/MM/YYYY'; 
                                        ?>
                                    </span>
                                </td>
                                <td style="border: none; width: 12%;" class="opf-lbl">Age:</td>
                                <td style="border: none;">
                                    <span class="opf-val"><?php echo esc_html($data['age'] ?? ''); ?></span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Row 3: Number of Siblings (Brothers, Sisters, Your Position) -->
                <tr>
                    <td class="opf-lbl">Number of Siblings:</td>
                    <td colspan="2" style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="border: none; width: 60px;">&nbsp;</td>
                                <td style="border: none; border-left: 1px solid #000; width: 75px;" class="opf-lbl">Brothers:</td>
                                <td style="border: none; border-right: 1px solid #000; width: 65px;">
                                    <span class="opf-val"><?php echo esc_html($data['siblings_brothers'] ?? '0'); ?></span>
                                </td>
                                <td style="border: none; width: 60px;" class="opf-lbl">Sisters:</td>
                                <td style="border: none; border-right: 1px solid #000; width: 65px;">
                                    <span class="opf-val"><?php echo esc_html($data['siblings_sisters'] ?? '0'); ?></span>
                                </td>
                                <td style="border: none; width: 95px;" class="opf-lbl">Your Position:</td>
                                <td style="border: none;">
                                    <span class="opf-val"><?php echo esc_html($data['siblings_position'] ?? ''); ?></span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Row 4: Contact/Mobile Number (+880 and 10 boxes matching row below) -->
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Contact/Mobile Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <div style="display: flex; justify-content: flex-end;">
                            <?php echo opf_render_phone_boxes($data['student_mobile'] ?? ''); ?>
                        </div>
                    </td>
                </tr>

                <!-- Row 5: Birth Registration Number (17 boxes) -->
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Birth Registration Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <div style="display: flex; justify-content: flex-end;">
                            <?php echo opf_render_boxes($data['birth_registration_number'] ?? '', 17); ?>
                        </div>
                    </td>
                </tr>

                <!-- Row 6: National Identification Number (17 boxes) -->
                <tr>
                    <td class="opf-lbl" style="border-right: none;">National Identification Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <div style="display: flex; justify-content: flex-end;">
                            <?php echo opf_render_boxes($data['student_nid'] ?? '', 17); ?>
                        </div>
                    </td>
                </tr>

                <!-- Row 7: Name of Colleges -->
                <tr>
                    <td class="opf-lbl">Name of Colleges:</td>
                    <td colspan="2">
                        <span class="opf-val"><?php echo esc_html($data['college_school_name'] ?? ''); ?></span>
                    </td>
                </tr>

                <!-- Row 8: Studying, ID No, Group/Subject -->
                <tr>
                    <td class="opf-lbl">Studying:</td>
                    <td colspan="2" style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="border: none; width: 120px;">&nbsp;</td>
                                <td style="border: none; border-left: 1px solid #000; width: 55px;" class="opf-lbl">ID No:</td>
                                <td style="border: none; border-right: 1px solid #000; width: 160px;">
                                    <span class="opf-val"><?php echo esc_html($data['studying_id_no'] ?? ''); ?></span>
                                </td>
                                <td style="border: none; width: 105px;" class="opf-lbl">Group/Subject:</td>
                                <td style="border: none;">
                                    <span class="opf-val"><?php echo esc_html($data['studying_group_subject'] ?? ''); ?></span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- 2. Family and Guardians Information -->
            <div class="opf-pdf-section-title">Family and Guardians Information</div>

            <!-- Father's Table -->
            <table class="opf-table">
                <tr>
                    <td style="width: 250px;" class="opf-lbl">Full Name of Father:</td>
                    <td colspan="2">
                        <span class="opf-val"><?php echo esc_html($data['father_full_name'] ?? ''); ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Contact/Mobile Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <div style="display: flex; justify-content: flex-end;">
                            <?php echo opf_render_phone_boxes($data['father_mobile'] ?? ''); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">National Identification Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <div style="display: flex; justify-content: flex-end;">
                            <?php echo opf_render_boxes($data['father_nid'] ?? '', 17); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">Occupation:</td>
                    <td style="width: 45%;">
                        <span class="opf-val"><?php echo esc_html($data['father_occupation'] ?? ''); ?></span>
                    </td>
                    <td style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="border: none; width: 110px;" class="opf-lbl">Monthly Income:</td>
                                <td style="border: none;">
                                    <span class="opf-val"><?php echo !empty($data['father_monthly_income']) ? esc_html($data['father_monthly_income']) . ' BDT' : ''; ?></span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Death Registration Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <div style="display: flex; justify-content: flex-end;">
                            <?php echo opf_render_boxes($data['father_death_reg_no'] ?? '', 17); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">Cause of Death:</td>
                    <td colspan="2">
                        <span class="opf-val"><?php echo esc_html($data['father_cause_of_death'] ?? ''); ?></span>
                    </td>
                </tr>
            </table>

            <!-- Mother's Table -->
            <table class="opf-table">
                <tr>
                    <td style="width: 250px;" class="opf-lbl">Full Name of Mother:</td>
                    <td colspan="2">
                        <span class="opf-val"><?php echo esc_html($data['mother_full_name'] ?? ''); ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Contact/Mobile Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <div style="display: flex; justify-content: flex-end;">
                            <?php echo opf_render_phone_boxes($data['mother_mobile'] ?? ''); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">National Identification Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <div style="display: flex; justify-content: flex-end;">
                            <?php echo opf_render_boxes($data['mother_nid'] ?? '', 17); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">Occupation:</td>
                    <td style="width: 45%;">
                        <span class="opf-val"><?php echo esc_html($data['mother_occupation'] ?? ''); ?></span>
                    </td>
                    <td style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="border: none; width: 110px;" class="opf-lbl">Monthly Income:</td>
                                <td style="border: none;">
                                    <span class="opf-val"><?php echo !empty($data['mother_monthly_income']) ? esc_html($data['mother_monthly_income']) . ' BDT' : ''; ?></span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Death Registration Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <div style="display: flex; justify-content: flex-end;">
                            <?php echo opf_render_boxes($data['mother_death_reg_no'] ?? '', 17); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">Cause of Death:</td>
                    <td colspan="2">
                        <span class="opf-val"><?php echo esc_html($data['mother_cause_of_death'] ?? ''); ?></span>
                    </td>
                </tr>
            </table>

            <!-- Current Guardians Table -->
            <table class="opf-table">
                <tr>
                    <td style="width: 250px;" class="opf-lbl">Full Name of Current Guardians:</td>
                    <td colspan="2">
                        <span class="opf-val"><?php echo esc_html($data['guardian_full_name'] ?? ''); ?></span>
                        <span style="font-size: 9px; color: #718096; margin-left: 10px;">*Applicable if it is someone other than the father or mother</span>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Contact/Mobile Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <div style="display: flex; justify-content: flex-end;">
                            <?php echo opf_render_phone_boxes($data['guardian_mobile'] ?? ''); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">National Identification Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <div style="display: flex; justify-content: flex-end;">
                            <?php echo opf_render_boxes($data['guardian_nid'] ?? '', 17); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">Date of Birth:</td>
                    <td style="width: 35%;">
                        <span class="opf-val">
                            <?php 
                            echo !empty($data['guardian_dob']) ? date('d/m/Y', strtotime($data['guardian_dob'])) : 'DD/MM/YYYY'; 
                            ?>
                        </span>
                    </td>
                    <td style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="border: none; width: 145px;" class="opf-lbl">Relation with Applicant:</td>
                                <td style="border: none;">
                                    <span class="opf-val"><?php echo esc_html($data['guardian_relation'] ?? ''); ?></span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- 3. Address -->
            <div class="opf-pdf-section-title">Address</div>
            <table class="opf-table">
                <!-- Present Address -->
                <tr>
                    <td rowspan="3" style="width: 145px;" class="opf-lbl">Present Address:</td>
                    <td style="width: 80px;" class="opf-lbl">House:</td>
                    <td style="width: 32%;">
                        <span class="opf-val"><?php echo esc_html($data['present_house'] ?? ''); ?></span>
                    </td>
                    <td style="width: 90px;" class="opf-lbl">Village:</td>
                    <td>
                        <span class="opf-val"><?php echo esc_html($data['present_village'] ?? ''); ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">Post Office:</td>
                    <td>
                        <span class="opf-val"><?php echo esc_html($data['present_post_office'] ?? ''); ?></span>
                    </td>
                    <td class="opf-lbl">Police Station:</td>
                    <td>
                        <span class="opf-val"><?php echo esc_html($data['present_police_station'] ?? ''); ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">District:</td>
                    <td>
                        <span class="opf-val"><?php echo esc_html($data['present_district'] ?? ''); ?></span>
                    </td>
                    <td class="opf-lbl">Country:</td>
                    <td>
                        <span class="opf-val"><?php echo esc_html($data['present_country'] ?? 'Bangladesh'); ?></span>
                    </td>
                </tr>

                <!-- Permanent Address -->
                <tr>
                    <td rowspan="3" class="opf-lbl">Permanent Address:</td>
                    <td class="opf-lbl">House:</td>
                    <td>
                        <span class="opf-val"><?php echo esc_html($data['permanent_house'] ?? ''); ?></span>
                    </td>
                    <td class="opf-lbl">Village:</td>
                    <td>
                        <span class="opf-val"><?php echo esc_html($data['permanent_village'] ?? ''); ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">Post Office:</td>
                    <td>
                        <span class="opf-val"><?php echo esc_html($data['permanent_post_office'] ?? ''); ?></span>
                    </td>
                    <td class="opf-lbl">Police Station:</td>
                    <td>
                        <span class="opf-val"><?php echo esc_html($data['permanent_police_station'] ?? ''); ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">District:</td>
                    <td>
                        <span class="opf-val"><?php echo esc_html($data['permanent_district'] ?? ''); ?></span>
                    </td>
                    <td class="opf-lbl">Country:</td>
                    <td>
                        <span class="opf-val"><?php echo esc_html($data['permanent_country'] ?? 'Bangladesh'); ?></span>
                    </td>
                </tr>
            </table>

            <!-- 4. SAP References -->
            <div class="opf-pdf-section-title">SAP References</div>
            <table class="opf-table">
                <tr>
                    <td style="width: 250px;" class="opf-lbl">Name of Reference (SAP):</td>
                    <td>
                        <span class="opf-val"><?php echo esc_html($data['sap_reference_name'] ?? ''); ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Contact/Mobile Number:</td>
                    <td style="border-left: none; text-align: right;">
                        <div style="display: flex; justify-content: flex-end;">
                            <?php echo opf_render_phone_boxes($data['sap_reference_mobile'] ?? ''); ?>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ========================================================
             PAGE 2: Orphan education status & Signatures
             ======================================================== -->
        <div class="opf-a4-page opf-page-2">
            <div class="opf-pdf-section-title" style="font-size: 15px; margin-top: 25px; margin-bottom: 12px; text-decoration: none;">
                <strong>Orphan education status</strong>
            </div>

            <table class="opf-matrix-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>NeedImprovement</th>
                        <th>Acceptable</th>
                        <th>Very Good</th>
                        <th>Excellent</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Health & Physical Status</td>
                        <td><?php echo opf_eval_radio('health_physical', 'NeedImprovement', $eval); ?></td>
                        <td><?php echo opf_eval_radio('health_physical', 'Acceptable', $eval); ?></td>
                        <td><?php echo opf_eval_radio('health_physical', 'Very Good', $eval); ?></td>
                        <td><?php echo opf_eval_radio('health_physical', 'Excellent', $eval); ?></td>
                    </tr>
                    <tr>
                        <td>Reading Skills</td>
                        <td><?php echo opf_eval_radio('reading_skills', 'NeedImprovement', $eval); ?></td>
                        <td><?php echo opf_eval_radio('reading_skills', 'Acceptable', $eval); ?></td>
                        <td><?php echo opf_eval_radio('reading_skills', 'Very Good', $eval); ?></td>
                        <td><?php echo opf_eval_radio('reading_skills', 'Excellent', $eval); ?></td>
                    </tr>
                    <tr>
                        <td>Writing Skills</td>
                        <td><?php echo opf_eval_radio('writing_skills', 'NeedImprovement', $eval); ?></td>
                        <td><?php echo opf_eval_radio('writing_skills', 'Acceptable', $eval); ?></td>
                        <td><?php echo opf_eval_radio('writing_skills', 'Very Good', $eval); ?></td>
                        <td><?php echo opf_eval_radio('writing_skills', 'Excellent', $eval); ?></td>
                    </tr>
                    <tr>
                        <td>Speking Skills</td>
                        <td><?php echo opf_eval_radio('speaking_skills', 'NeedImprovement', $eval); ?></td>
                        <td><?php echo opf_eval_radio('speaking_skills', 'Acceptable', $eval); ?></td>
                        <td><?php echo opf_eval_radio('speaking_skills', 'Very Good', $eval); ?></td>
                        <td><?php echo opf_eval_radio('speaking_skills', 'Excellent', $eval); ?></td>
                    </tr>
                    <tr>
                        <td>Listening Skills</td>
                        <td><?php echo opf_eval_radio('listening_skills', 'NeedImprovement', $eval); ?></td>
                        <td><?php echo opf_eval_radio('listening_skills', 'Acceptable', $eval); ?></td>
                        <td><?php echo opf_eval_radio('listening_skills', 'Very Good', $eval); ?></td>
                        <td><?php echo opf_eval_radio('listening_skills', 'Excellent', $eval); ?></td>
                    </tr>
                    <tr>
                        <td>His/Her Effort on Education</td>
                        <td><?php echo opf_eval_radio('effort_education', 'NeedImprovement', $eval); ?></td>
                        <td><?php echo opf_eval_radio('effort_education', 'Acceptable', $eval); ?></td>
                        <td><?php echo opf_eval_radio('effort_education', 'Very Good', $eval); ?></td>
                        <td><?php echo opf_eval_radio('effort_education', 'Excellent', $eval); ?></td>
                    </tr>
                    <tr>
                        <td>Class Behaviour</td>
                        <td><?php echo opf_eval_radio('class_behaviour', 'NeedImprovement', $eval); ?></td>
                        <td><?php echo opf_eval_radio('class_behaviour', 'Acceptable', $eval); ?></td>
                        <td><?php echo opf_eval_radio('class_behaviour', 'Very Good', $eval); ?></td>
                        <td><?php echo opf_eval_radio('class_behaviour', 'Excellent', $eval); ?></td>
                    </tr>
                    <tr>
                        <td>Completion of Homework</td>
                        <td><?php echo opf_eval_radio('homework_done', 'NeedImprovement', $eval); ?></td>
                        <td><?php echo opf_eval_radio('homework_done', 'Acceptable', $eval); ?></td>
                        <td><?php echo opf_eval_radio('homework_done', 'Very Good', $eval); ?></td>
                        <td><?php echo opf_eval_radio('homework_done', 'Excellent', $eval); ?></td>
                    </tr>
                </tbody>
            </table>

            <p class="opf-note-para">
                Note: The above section will be filled out manually by the SAP volunteer.
            </p>

            <!-- Signatures Section matching original PDF (Image 2) -->
            <div class="opf-signatures-row">
                <div class="opf-sig-block">
                    <div class="opf-sig-line"></div>
                    <div class="opf-sig-title">Signature of Applicant</div>
                    <div class="opf-sig-item">
                        Name: <strong><?php echo esc_html($app->student_name); ?></strong>
                    </div>
                    <div class="opf-sig-item">
                        Date : <?php echo esc_html(date('d/m/Y', strtotime($app->created_at))); ?>
                    </div>
                </div>

                <div class="opf-sig-block">
                    <div class="opf-sig-line"></div>
                    <div class="opf-sig-title">Authorised Signature</div>
                    <div class="opf-sig-item">Name:</div>
                    <div class="opf-sig-item">Date :</div>
                </div>
            </div>
        </div>

        </div> <!-- /#opf-pdf-content -->
    </div> <!-- /#opf-pdf-outer-wrapper -->

    <!-- Direct Client-Side PDF Auto-Download using html2pdf.bundle.min.js -->
    <script src="<?php echo esc_url(OPF_PLUGIN_URL . 'assets/js/html2pdf.bundle.min.js?ver=' . OPF_VERSION); ?>"></script>
    <script>
    var currentScale = 1;
    var autoFitScale = 1;

    function calcAutoFitScale() {
        var availableWidth = window.innerWidth - (window.innerWidth <= 600 ? 16 : 40);
        var targetWidth = 794; // 210mm in pixels at 96 DPI
        if (availableWidth < targetWidth) {
            return availableWidth / targetWidth;
        }
        return 1;
    }

    function applyScale(scale, isFit) {
        currentScale = Math.max(0.25, Math.min(scale, 2.5));
        var container = document.getElementById('opf-pdf-content');
        var wrapper = document.getElementById('opf-pdf-outer-wrapper');
        if (!container) return;

        // Apply scale via CSS zoom (supported in iOS Safari, Android Chrome, Edge, Chrome)
        // or fallback to transform: scale
        if ('zoom' in container.style) {
            container.style.zoom = currentScale;
            container.style.transform = '';
            container.style.transformOrigin = '';
        } else {
            container.style.transformOrigin = 'top center';
            container.style.transform = 'scale(' + currentScale + ')';
            if (wrapper) {
                wrapper.style.height = (container.offsetHeight * currentScale + 50) + 'px';
            }
        }

        var labelText = isFit ? 'Fit' : Math.round(currentScale * 100) + '%';
        var zoomValEl = document.getElementById('opf-zoom-val');
        if (zoomValEl) zoomValEl.innerText = labelText;
        var floatZoomValEl = document.getElementById('opf-float-zoom-val');
        if (floatZoomValEl) floatZoomValEl.innerText = labelText;
    }

    function initAutoFit() {
        autoFitScale = calcAutoFitScale();
        if (autoFitScale < 1) {
            applyScale(autoFitScale, true);
        } else {
            applyScale(1, false);
        }
    }

    function zoomIn() {
        applyScale(currentScale + 0.15, false);
    }

    function zoomOut() {
        applyScale(currentScale - 0.15, false);
    }

    function resetZoom() {
        autoFitScale = calcAutoFitScale();
        applyScale(autoFitScale, true);
    }

    window.addEventListener('resize', function() {
        autoFitScale = calcAutoFitScale();
        var zoomValEl = document.getElementById('opf-zoom-val');
        if (zoomValEl && zoomValEl.innerText === 'Fit') {
            applyScale(autoFitScale, true);
        }
    });

    document.addEventListener('DOMContentLoaded', initAutoFit);
    window.addEventListener('load', initAutoFit);

    function downloadApplicationPDF() {
        var btn = document.getElementById('opf-btn-download');
        if (btn && btn.classList.contains('is-loading')) return;

        var originalHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.classList.add('is-loading');
            btn.disabled = true;
            btn.innerHTML = '<svg class="opf-spin" style="width:16px;height:16px;margin-right:6px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path></svg> Downloading PDF...';
        }

        var container = document.getElementById('opf-pdf-content');

        // Reset zoom to 1 so html2pdf captures full resolution A4!
        if ('zoom' in container.style) {
            container.style.zoom = '1';
        }
        container.style.transform = 'none';
        container.classList.add('opf-pdf-exporting');

        // Allow 150ms for the browser to render with the exporting styles
        setTimeout(function() {
            var filename = 'Application-<?php echo esc_js($app->application_no); ?>.pdf';
            var opt = {
                margin:       0,
                filename:     filename,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { 
                    scale: 2, 
                    useCORS: true,
                    logging: false,
                    scrollY: 0,
                    scrollX: 0
                },
                jsPDF:        { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'portrait' 
                },
                pagebreak:    { 
                    mode: ['css', 'legacy']
                }
            };

            html2pdf().set(opt).from(container).save().then(function() {
                container.classList.remove('opf-pdf-exporting');
                var zoomValEl = document.getElementById('opf-zoom-val');
                applyScale(currentScale, zoomValEl && zoomValEl.innerText === 'Fit');
                if (btn) {
                    btn.classList.remove('is-loading');
                    btn.disabled = false;
                    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:2px;"><polyline points="20 6 9 17 4 12"></polyline></svg> Downloaded!';
                    setTimeout(function() {
                        btn.innerHTML = originalHtml;
                    }, 3500);
                }
                
                try {
                    if (window.parent && window.parent !== window) {
                        window.parent.postMessage({
                            type: 'opf_download_success',
                            app_no: '<?php echo esc_js($app->application_no); ?>'
                        }, '*');
                    }
                } catch(e) {}
            }).catch(function(err) {
                console.error('PDF generation error:', err);
                container.classList.remove('opf-pdf-exporting');
                applyScale(currentScale, false);
                if (btn) {
                    btn.classList.remove('is-loading');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
                
                try {
                    if (window.parent && window.parent !== window) {
                        window.parent.postMessage({
                            type: 'opf_download_error',
                            app_no: '<?php echo esc_js($app->application_no); ?>'
                        }, '*');
                    }
                } catch(e) {}

                window.print();
            });
        }, 150);
    }

    <?php if (!empty($_GET['opf_auto_download'])) : ?>
    window.addEventListener('load', function() {
        setTimeout(function() {
            downloadApplicationPDF();
        }, 600);
    });
    <?php endif; ?>
    </script>
</body>
</html>
