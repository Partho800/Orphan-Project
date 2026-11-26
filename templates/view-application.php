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

$is_admin_view = !empty($_GET['opf_admin']) && current_user_can('manage_options');
$is_edit_mode  = $is_admin_view && !empty($_GET['opf_edit']);

$sig_image_url    = $data['signature_image_url'] ?? '';
$short_photo_url  = $data['short_photo_url'] ?? '';
$half_photo_url   = $data['half_photo_url'] ?? '';
$has_photos_page  = (!empty($short_photo_url) || !empty($half_photo_url));

$left_logo_url  = OPF_PLUGIN_URL . 'assets/images/logo-sap.png';
$right_logo_url = OPF_PLUGIN_URL . 'assets/images/logo-hasene.png';

if (!function_exists('opf_clean_mobile_for_input')) {
function opf_clean_mobile_for_input($phone_value) {
    $digits = preg_replace('/[^0-9]/', '', (string)$phone_value);
    if (strlen($digits) > 10 && substr($digits, 0, 3) === '880') {
        $digits = substr($digits, 3);
    }
    if (strlen($digits) === 11 && substr($digits, 0, 1) === '0') {
        $digits = substr($digits, 1);
    }
    return $digits;
}
}

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
function opf_eval_radio($metric_key, $expected_value, $eval_array, $is_edit_mode = false) {
    $selected = isset($eval_array[$metric_key]) && (
        strtolower(str_replace(' ', '', $eval_array[$metric_key])) === strtolower(str_replace(' ', '', $expected_value))
    );
    if ($is_edit_mode) {
        $checked_attr = $selected ? ' checked' : '';
        $circle_class = $selected ? 'opf-circle selected' : 'opf-circle';
        $symbol = $selected ? '●' : '◯';
        return '<label class="opf-edit-radio-label"><input type="radio" class="opf-edit-radio-input" name="eval[' . esc_attr($metric_key) . ']" value="' . esc_attr($expected_value) . '"' . $checked_attr . '><span class="' . $circle_class . '">' . $symbol . '</span></label>';
    }
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

        /* Application ID Pill & One-Click Copy */
        .opf-view-id-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 5px 6px 5px 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
            transition: all 0.2s ease;
        }

        .opf-view-id-pill:hover {
            border-color: #0e84c7;
            background: #1e2d42;
        }

        .opf-view-id-info {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .opf-view-id-label {
            font-size: 11.5px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .opf-view-id-num {
            font-size: 15px;
            font-weight: 700;
            color: #38bdf8;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            letter-spacing: 0.8px;
        }

        .opf-view-copy-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #0e84c7;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 5px 11px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            line-height: 1.2;
            outline: none;
        }

        .opf-view-copy-btn:hover {
            background: #0284c7;
            transform: translateY(-1px);
        }

        .opf-view-copy-btn.copied {
            background: #16a34a !important;
            color: #ffffff !important;
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

        .opf-pdf-exporting .opf-page-1,
        .opf-pdf-exporting .opf-page-2 {
            page-break-after: always !important;
            break-after: page !important;
        }

        .opf-pdf-exporting .opf-pdf-page-last {
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

        /* Header matching original PDF (Orphan Information New 19_5_26.pdf) */
        .opf-pdf-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: -2.5mm -3mm 4px -3mm;
            padding: 0;
        }

        .opf-pdf-logo-img {
            height: 112px;
            width: auto;
            object-fit: contain;
            display: block;
        }

        .opf-pdf-header-center {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .opf-pdf-header-title {
            font-family: "Times New Roman", Times, Georgia, serif;
            font-size: 31px;
            font-weight: 700;
            font-style: italic;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin: 0;
            text-align: center;
            color: #000000;
            line-height: 1.1;
        }

        .opf-pdf-old-id-box {
            margin-top: 6px;
            display: inline-block;
            padding: 3px 12px;
            border: 1.5px solid #000000;
            border-radius: 3px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            font-weight: 700;
            color: #000000;
            letter-spacing: 0.4px;
            background: #ffffff;
        }

        /* Interactive Admin PDF Edit Mode Styles */
        .opf-edit-input,
        .opf-edit-select {
            width: 100%;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5px;
            color: #0f172a;
            background: #f0f9ff;
            border: 1px dashed #38bdf8;
            border-radius: 2px;
            padding: 2px 5px;
            outline: none;
            min-height: 20px;
            line-height: 1.25;
            transition: border-color 0.15s ease, background-color 0.15s ease;
        }

        .opf-edit-input:focus,
        .opf-edit-select:focus {
            background: #ffffff;
            border: 1.5px solid #0e84c7;
            box-shadow: 0 0 0 2px rgba(14, 132, 199, 0.16);
        }

        .opf-edit-input[type="number"]::-webkit-outer-spin-button,
        .opf-edit-input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .opf-edit-input[type="number"] {
            -moz-appearance: textfield;
            appearance: textfield;
            min-width: 38px;
        }

        .opf-edit-inline-wrap {
            display: flex;
            align-items: center;
            gap: 4px;
            width: 100%;
        }

        .opf-edit-prefix {
            font-weight: 700;
            font-size: 10px;
            background: #e2e8f0;
            border: 1px solid #94a3b8;
            padding: 2px 5px;
            border-radius: 2px;
            color: #1e293b;
            flex-shrink: 0;
            line-height: 1.4;
        }

        .opf-edit-suffix {
            font-size: 10px;
            font-weight: 600;
            color: #334155;
            flex-shrink: 0;
        }

        .opf-edit-radio-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 4px;
            transition: background-color 0.15s ease;
        }

        .opf-edit-radio-label:hover {
            background: #e0f2fe;
        }

        .opf-edit-radio-input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            pointer-events: none;
        }

        .opf-edit-mode-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
            border-radius: 6px;
            padding: 5px 12px;
            font-size: 12.5px;
            font-weight: 600;
        }

        .opf-standalone-edit-actions {
            display: none;
            align-items: center;
            gap: 8px;
        }

        .opf-btn-save-edit {
            background: #16a34a;
            color: #ffffff;
        }

        .opf-btn-save-edit:hover {
            background: #15803d;
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
            margin-top: 14px;
            padding: 0 10px;
        }

        .opf-sig-block {
            width: 260px;
        }

        .opf-sig-img-wrap {
            height: 46px;
            display: flex;
            align-items: flex-end;
            justify-content: flex-start;
            padding-bottom: 3px;
            overflow: hidden;
        }

        .opf-sig-img {
            max-height: 44px;
            max-width: 210px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .opf-sig-line {
            border-top: 1px solid #000000;
            margin-bottom: 6px;
            width: 100%;
        }

        /* Page 3: Uploaded Photographs Sheet (1 Page) */
        .opf-photos-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-top: 16px;
        }

        .opf-photo-frame-card {
            border: 1.5px solid #000000;
            padding: 10px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
        }

        .opf-photo-frame-label {
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding-bottom: 8px;
            margin-bottom: 10px;
            border-bottom: 1px solid #000000;
            color: #000000;
        }

        .opf-photo-frame-inner {
            height: 540px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fafafa;
            border: 1px solid #d1d5db;
            padding: 8px;
            box-sizing: border-box;
            overflow: hidden;
        }

        .opf-photo-frame-inner img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .opf-photo-empty-note {
            font-size: 11px;
            color: #64748b;
            font-style: italic;
        }

        .opf-photos-meta-table {
            margin-top: 18px;
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
                flex-direction: column;
                align-items: stretch;
            }

            .opf-action-buttons {
                width: 100%;
                justify-content: stretch;
                gap: 6px;
                flex-wrap: nowrap;
            }

            .opf-ctl-btn {
                flex: 1;
                justify-content: center;
                padding: 8px 6px;
                font-size: 11.5px;
                gap: 5px;
            }

            .opf-ctl-btn svg {
                width: 14px;
                height: 14px;
            }

            .opf-view-id-pill {
                width: 100%;
                justify-content: space-between;
                box-sizing: border-box;
                padding: 6px 8px 6px 12px;
            }

            .opf-zoom-controls {
                display: none; /* Mobile uses floating thumb pill */
            }

            .opf-pdf-outer-wrapper {
                margin-top: 105px;
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
        <?php if ($is_edit_mode) : ?>
        <div class="opf-action-buttons">
            <span class="opf-edit-mode-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                <span>Edit PDF Mode — #<?php echo esc_html($app->application_no ?: $app->id); ?> (Click any field below to edit)</span>
            </span>
            <div class="opf-standalone-edit-actions" id="opf-standalone-edit-actions">
                <button type="button" class="opf-ctl-btn opf-btn-save-edit" id="opf-standalone-save-btn" onclick="saveApplicationEdits();">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Save PDF</span>
                </button>
                <a href="<?php echo esc_url(remove_query_arg('opf_edit')); ?>" class="opf-ctl-btn opf-btn-back">
                    <span>Cancel</span>
                </a>
            </div>
        </div>
        <?php elseif (empty($_GET['opf_admin'])) : ?>
        <div class="opf-action-buttons">
            <button type="button" class="opf-ctl-btn opf-btn-download" id="opf-btn-download" onclick="downloadApplicationPDF();">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>Download PDF</span>
            </button>
            <a href="<?php echo esc_url(remove_query_arg(['opf_view_app', 'opf_admin', 'opf_auto_download', 'opf_auto_preview'])); ?>" class="opf-ctl-btn opf-btn-back">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                <span>Submit Another</span>
            </a>
        </div>

        <!-- Application ID Pill with One-Click Copy -->
        <div class="opf-view-id-pill" title="Your unique Application ID. Use this ID to upload signed form.">
            <div class="opf-view-id-info">
                <span class="opf-view-id-label">Application ID:</span>
                <strong class="opf-view-id-num">#<?php echo esc_html($app->application_no ?: $app->id); ?></strong>
            </div>
            <button type="button" class="opf-view-copy-btn" id="opf-view-copy-btn" onclick="copyAppIdToClipboard('<?php echo esc_js($app->application_no ?: $app->id); ?>', this);" title="Copy Application ID to clipboard">
                <svg class="opf-copy-icon-default" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                <svg class="opf-copy-icon-success" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <span class="opf-copy-btn-text">Copy</span>
            </button>
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
        <?php if ($is_edit_mode) : ?>
        <form id="opf-admin-edit-pdf-form" onsubmit="event.preventDefault(); saveApplicationEdits();">
            <input type="hidden" name="action" value="opf_admin_update_application">
            <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('opf_admin_nonce_action')); ?>">
            <input type="hidden" name="app_id" value="<?php echo esc_attr($app->id); ?>">
        <?php endif; ?>
        <div class="opf-pdf-container" id="opf-pdf-content">

        <!-- ========================================================
             PAGE 1: Exact layout matching Image 2
             ======================================================== -->
        <div class="opf-a4-page opf-page-1">
            <!-- Header with 2 logos and center title -->
            <div class="opf-pdf-header">
                <img src="<?php echo esc_url($left_logo_url); ?>" alt="SAP" class="opf-pdf-logo-img opf-pdf-logo-left">
                <div class="opf-pdf-header-center">
                    <h1 class="opf-pdf-header-title">ORPHAN PROJECT</h1>
                    <?php
                    global $wpdb;
                    $is_old_app = (!empty($app->application_type) && $app->application_type === 'old');
                    if (!$is_old_app && !empty($wpdb)) {
                        $old_ids_tbl = $wpdb->prefix . 'orphan_old_ids';
                        $is_old_app  = ((int) $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $old_ids_tbl WHERE application_id = %d",
                            intval($app->id)
                        ))) > 0;
                    }
                    if ($is_old_app && !empty($app->application_no)) :
                    ?>
                        <div class="opf-pdf-old-id-box">Old ID: <?php echo esc_html($app->application_no); ?></div>
                    <?php endif; ?>
                </div>
                <img src="<?php echo esc_url($right_logo_url); ?>" alt="HASENE" class="opf-pdf-logo-img opf-pdf-logo-right">
            </div>

            <!-- 1. Student's Personal Information (Image 2) -->
            <div class="opf-pdf-section-title">Student's Personal Information</div>
            <table class="opf-table">
                <!-- Row 1: Full Name -->
                <tr>
                    <td style="width: 135px;" class="opf-lbl">Full Name:</td>
                    <td style="width: 50%;">
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="student_first_name" class="opf-edit-input" value="<?php echo esc_attr($data['student_first_name'] ?? ''); ?>" placeholder="First name">
                        <?php else : ?>
                            <div class="opf-val"><?php echo esc_html($data['student_first_name'] ?? ''); ?>&nbsp;</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="student_surname" class="opf-edit-input" value="<?php echo esc_attr($data['student_surname'] ?? ''); ?>" placeholder="Surname">
                        <?php else : ?>
                            <div class="opf-val"><?php echo esc_html($data['student_surname'] ?? ''); ?>&nbsp;</div>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Row 2: Gender, DOB, Age -->
                <tr>
                    <td class="opf-lbl">Gender:</td>
                    <td>
                        <?php if ($is_edit_mode) :
                            $cur_gender = $data['gender'] ?? 'Male'; ?>
                            <select name="gender" class="opf-edit-select">
                                <option value="Male" <?php selected($cur_gender, 'Male'); ?>>Male</option>
                                <option value="Female" <?php selected($cur_gender, 'Female'); ?>>Female</option>
                                <option value="Other" <?php selected($cur_gender, 'Other'); ?>>Other</option>
                            </select>
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['gender'] ?? 'Male'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="border: none; border-right: 1px solid #000; <?php echo $is_edit_mode ? 'width: auto; white-space: nowrap; padding: 3px 4px;' : 'width: 38%;'; ?>" class="opf-lbl">Date of Birth:</td>
                                <td style="border: none; border-right: 1px solid #000; <?php echo $is_edit_mode ? 'width: 108px; padding: 2px 3px;' : 'width: 38%;'; ?>">
                                    <?php if ($is_edit_mode) : ?>
                                        <input type="date" name="date_of_birth" id="opf-edit-student-dob" class="opf-edit-input" style="padding: 1px 3px;" value="<?php echo esc_attr($data['date_of_birth'] ?? ''); ?>">
                                    <?php else : ?>
                                        <span class="opf-val">
                                            <?php 
                                            echo !empty($data['date_of_birth']) ? date('d/m/Y', strtotime($data['date_of_birth'])) : 'DD/MM/YYYY'; 
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="border: none; <?php echo $is_edit_mode ? 'width: auto; white-space: nowrap; padding: 3px 4px;' : 'width: 12%;'; ?>" class="opf-lbl">Age:</td>
                                <td style="border: none; <?php echo $is_edit_mode ? 'min-width: 44px; padding: 2px 3px;' : ''; ?>">
                                    <?php
                                    $pdf_age_display = (isset($data['age']) && $data['age'] !== '') ? intval($data['age']) : '';
                                    if ($is_edit_mode) : ?>
                                        <input type="number" name="age" id="opf-edit-student-age" class="opf-edit-input" min="0" max="99" style="text-align: center;" value="<?php echo esc_attr($pdf_age_display); ?>" placeholder="Age">
                                    <?php else : ?>
                                        <span class="opf-val"><?php echo esc_html($pdf_age_display); ?></span>
                                    <?php endif; ?>
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
                                    <?php if ($is_edit_mode) : ?>
                                        <input type="number" name="siblings_brothers" class="opf-edit-input" min="0" value="<?php echo esc_attr($data['siblings_brothers'] ?? '0'); ?>">
                                    <?php else : ?>
                                        <span class="opf-val"><?php echo esc_html($data['siblings_brothers'] ?? '0'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="border: none; width: 60px;" class="opf-lbl">Sisters:</td>
                                <td style="border: none; border-right: 1px solid #000; width: 65px;">
                                    <?php if ($is_edit_mode) : ?>
                                        <input type="number" name="siblings_sisters" class="opf-edit-input" min="0" value="<?php echo esc_attr($data['siblings_sisters'] ?? '0'); ?>">
                                    <?php else : ?>
                                        <span class="opf-val"><?php echo esc_html($data['siblings_sisters'] ?? '0'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="border: none; width: 95px;" class="opf-lbl">Your Position:</td>
                                <td style="border: none;">
                                    <?php if ($is_edit_mode) : ?>
                                        <input type="text" name="siblings_position" class="opf-edit-input" value="<?php echo esc_attr($data['siblings_position'] ?? ''); ?>" placeholder="e.g. 1st">
                                    <?php else : ?>
                                        <span class="opf-val"><?php echo esc_html($data['siblings_position'] ?? ''); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Row 4: Contact/Mobile Number (+880 and 10 boxes matching row below) -->
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Contact/Mobile Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <?php if ($is_edit_mode) : ?>
                            <div class="opf-edit-inline-wrap">
                                <span class="opf-edit-prefix">+880</span>
                                <input type="text" name="student_mobile" class="opf-edit-input opf-digits-only" maxlength="11" inputmode="numeric" value="<?php echo esc_attr(opf_clean_mobile_for_input($data['student_mobile'] ?? $app->contact_number)); ?>" placeholder="1XXXXXXXXX">
                            </div>
                        <?php else : ?>
                            <div style="display: flex; justify-content: flex-end;">
                                <?php echo opf_render_phone_boxes($data['student_mobile'] ?? ''); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Row 5: Birth Registration Number (17 boxes) -->
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Birth Registration Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="birth_registration_number" class="opf-edit-input opf-digits-only" maxlength="17" inputmode="numeric" value="<?php echo esc_attr($data['birth_registration_number'] ?? $app->birth_reg_no); ?>" placeholder="17-digit Birth Registration No">
                        <?php else : ?>
                            <div style="display: flex; justify-content: flex-end;">
                                <?php echo opf_render_boxes($data['birth_registration_number'] ?? '', 17); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Row 6: National Identification Number (17 boxes) -->
                <tr>
                    <td class="opf-lbl" style="border-right: none;">National Identification Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="student_nid" class="opf-edit-input opf-digits-only" maxlength="17" inputmode="numeric" value="<?php echo esc_attr($data['student_nid'] ?? ''); ?>" placeholder="10 or 17-digit NID">
                        <?php else : ?>
                            <div style="display: flex; justify-content: flex-end;">
                                <?php echo opf_render_boxes($data['student_nid'] ?? '', 17); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Row 7: Name of Colleges -->
                <tr>
                    <td class="opf-lbl">Name of Colleges:</td>
                    <td colspan="2">
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="college_school_name" class="opf-edit-input" value="<?php echo esc_attr($data['college_school_name'] ?? $app->college_name); ?>" placeholder="Name of College / School">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['college_school_name'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Row 8: Studying, ID No, Group/Subject -->
                <tr>
                    <td class="opf-lbl">Studying:</td>
                    <td colspan="2" style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="border: none; width: 120px;">
                                    <?php if ($is_edit_mode) : ?>
                                        <input type="text" name="studying_class" class="opf-edit-input" value="<?php echo esc_attr($data['studying_class'] ?? ''); ?>" placeholder="Class / Year">
                                    <?php else : ?>
                                        <span class="opf-val"><?php echo !empty($data['studying_class']) ? esc_html($data['studying_class']) : '&nbsp;'; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="border: none; border-left: 1px solid #000; width: 55px;" class="opf-lbl">ID No:</td>
                                <td style="border: none; border-right: 1px solid #000; width: 160px;">
                                    <?php if ($is_edit_mode) : ?>
                                        <input type="text" name="studying_id_no" class="opf-edit-input" value="<?php echo esc_attr($data['studying_id_no'] ?? ''); ?>" placeholder="ID No">
                                    <?php else : ?>
                                        <span class="opf-val"><?php echo esc_html($data['studying_id_no'] ?? ''); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="border: none; width: 105px;" class="opf-lbl">Group/Subject:</td>
                                <td style="border: none;">
                                    <?php if ($is_edit_mode) : ?>
                                        <input type="text" name="studying_group_subject" class="opf-edit-input" value="<?php echo esc_attr($data['studying_group_subject'] ?? ''); ?>" placeholder="Group/Subject">
                                    <?php else : ?>
                                        <span class="opf-val"><?php echo esc_html($data['studying_group_subject'] ?? ''); ?></span>
                                    <?php endif; ?>
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
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="father_full_name" class="opf-edit-input" value="<?php echo esc_attr($data['father_full_name'] ?? $app->father_name); ?>" placeholder="Father's full name">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['father_full_name'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Contact/Mobile Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <?php if ($is_edit_mode) : ?>
                            <div class="opf-edit-inline-wrap">
                                <span class="opf-edit-prefix">+880</span>
                                <input type="text" name="father_mobile" class="opf-edit-input opf-digits-only" maxlength="11" inputmode="numeric" value="<?php echo esc_attr(opf_clean_mobile_for_input($data['father_mobile'] ?? '')); ?>" placeholder="1XXXXXXXXX">
                            </div>
                        <?php else : ?>
                            <div style="display: flex; justify-content: flex-end;">
                                <?php echo opf_render_phone_boxes($data['father_mobile'] ?? ''); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">National Identification Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="father_nid" class="opf-edit-input opf-digits-only" maxlength="17" inputmode="numeric" value="<?php echo esc_attr($data['father_nid'] ?? ''); ?>" placeholder="Father's NID">
                        <?php else : ?>
                            <div style="display: flex; justify-content: flex-end;">
                                <?php echo opf_render_boxes($data['father_nid'] ?? '', 17); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">Occupation:</td>
                    <td style="width: <?php echo $is_edit_mode ? '34%' : '45%'; ?>;">
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="father_occupation" class="opf-edit-input" value="<?php echo esc_attr($data['father_occupation'] ?? ''); ?>" placeholder="Occupation">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['father_occupation'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="border: none; <?php echo $is_edit_mode ? 'width: 86px; white-space: nowrap; padding: 3px 4px;' : 'width: 110px;'; ?>" class="opf-lbl">Monthly Income:</td>
                                <td style="border: none;">
                                    <?php if ($is_edit_mode) : ?>
                                        <div class="opf-edit-inline-wrap">
                                            <input type="number" name="father_monthly_income" class="opf-edit-input" min="0" style="min-width: 55px;" value="<?php echo esc_attr($data['father_monthly_income'] ?? ''); ?>" placeholder="0">
                                            <span class="opf-edit-suffix">BDT</span>
                                        </div>
                                    <?php else : ?>
                                        <span class="opf-val"><?php echo !empty($data['father_monthly_income']) ? esc_html($data['father_monthly_income']) . ' BDT' : ''; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Death Registration Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="father_death_reg_no" class="opf-edit-input opf-digits-only" maxlength="17" inputmode="numeric" value="<?php echo esc_attr($data['father_death_reg_no'] ?? ''); ?>" placeholder="Father's Death Registration No (if applicable)">
                        <?php else : ?>
                            <div style="display: flex; justify-content: flex-end;">
                                <?php echo opf_render_boxes($data['father_death_reg_no'] ?? '', 17); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">Cause of Death:</td>
                    <td colspan="2">
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="father_cause_of_death" class="opf-edit-input" value="<?php echo esc_attr($data['father_cause_of_death'] ?? ''); ?>" placeholder="Cause of death (if applicable)">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['father_cause_of_death'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <!-- Mother's Table -->
            <table class="opf-table">
                <tr>
                    <td style="width: 250px;" class="opf-lbl">Full Name of Mother:</td>
                    <td colspan="2">
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="mother_full_name" class="opf-edit-input" value="<?php echo esc_attr($data['mother_full_name'] ?? $app->mother_name); ?>" placeholder="Mother's full name">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['mother_full_name'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Contact/Mobile Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <?php if ($is_edit_mode) : ?>
                            <div class="opf-edit-inline-wrap">
                                <span class="opf-edit-prefix">+880</span>
                                <input type="text" name="mother_mobile" class="opf-edit-input opf-digits-only" maxlength="11" inputmode="numeric" value="<?php echo esc_attr(opf_clean_mobile_for_input($data['mother_mobile'] ?? '')); ?>" placeholder="1XXXXXXXXX">
                            </div>
                        <?php else : ?>
                            <div style="display: flex; justify-content: flex-end;">
                                <?php echo opf_render_phone_boxes($data['mother_mobile'] ?? ''); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">National Identification Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="mother_nid" class="opf-edit-input opf-digits-only" maxlength="17" inputmode="numeric" value="<?php echo esc_attr($data['mother_nid'] ?? ''); ?>" placeholder="Mother's NID">
                        <?php else : ?>
                            <div style="display: flex; justify-content: flex-end;">
                                <?php echo opf_render_boxes($data['mother_nid'] ?? '', 17); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">Occupation:</td>
                    <td style="width: <?php echo $is_edit_mode ? '34%' : '45%'; ?>;">
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="mother_occupation" class="opf-edit-input" value="<?php echo esc_attr($data['mother_occupation'] ?? ''); ?>" placeholder="Occupation">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['mother_occupation'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="border: none; <?php echo $is_edit_mode ? 'width: 86px; white-space: nowrap; padding: 3px 4px;' : 'width: 110px;'; ?>" class="opf-lbl">Monthly Income:</td>
                                <td style="border: none;">
                                    <?php if ($is_edit_mode) : ?>
                                        <div class="opf-edit-inline-wrap">
                                            <input type="number" name="mother_monthly_income" class="opf-edit-input" min="0" style="min-width: 55px;" value="<?php echo esc_attr($data['mother_monthly_income'] ?? ''); ?>" placeholder="0">
                                            <span class="opf-edit-suffix">BDT</span>
                                        </div>
                                    <?php else : ?>
                                        <span class="opf-val"><?php echo !empty($data['mother_monthly_income']) ? esc_html($data['mother_monthly_income']) . ' BDT' : ''; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Death Registration Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="mother_death_reg_no" class="opf-edit-input opf-digits-only" maxlength="17" inputmode="numeric" value="<?php echo esc_attr($data['mother_death_reg_no'] ?? ''); ?>" placeholder="Mother's Death Registration No (if applicable)">
                        <?php else : ?>
                            <div style="display: flex; justify-content: flex-end;">
                                <?php echo opf_render_boxes($data['mother_death_reg_no'] ?? '', 17); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">Cause of Death:</td>
                    <td colspan="2">
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="mother_cause_of_death" class="opf-edit-input" value="<?php echo esc_attr($data['mother_cause_of_death'] ?? ''); ?>" placeholder="Cause of death (if applicable)">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['mother_cause_of_death'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <!-- Current Guardians Table -->
            <table class="opf-table">
                <tr>
                    <td style="width: 250px;" class="opf-lbl">Full Name of Current Guardians:</td>
                    <td colspan="2">
                        <?php if ($is_edit_mode) : ?>
                            <div class="opf-edit-inline-wrap">
                                <input type="text" name="guardian_full_name" class="opf-edit-input" value="<?php echo esc_attr($data['guardian_full_name'] ?? $app->guardian_name); ?>" placeholder="Guardian's full name" style="flex: 1;">
                                <span style="font-size: 8.5px; color: #718096; white-space: nowrap;">*Other than father/mother</span>
                            </div>
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['guardian_full_name'] ?? ''); ?></span>
                            <span style="font-size: 9px; color: #718096; margin-left: 10px;">*Applicable if it is someone other than the father or mother</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Contact/Mobile Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <?php if ($is_edit_mode) : ?>
                            <div class="opf-edit-inline-wrap">
                                <span class="opf-edit-prefix">+880</span>
                                <input type="text" name="guardian_mobile" class="opf-edit-input opf-digits-only" maxlength="11" inputmode="numeric" value="<?php echo esc_attr(opf_clean_mobile_for_input($data['guardian_mobile'] ?? '')); ?>" placeholder="1XXXXXXXXX">
                            </div>
                        <?php else : ?>
                            <div style="display: flex; justify-content: flex-end;">
                                <?php echo opf_render_phone_boxes($data['guardian_mobile'] ?? ''); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">National Identification Number:</td>
                    <td colspan="2" style="border-left: none; text-align: right;">
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="guardian_nid" class="opf-edit-input opf-digits-only" maxlength="17" inputmode="numeric" value="<?php echo esc_attr($data['guardian_nid'] ?? ''); ?>" placeholder="Guardian's NID">
                        <?php else : ?>
                            <div style="display: flex; justify-content: flex-end;">
                                <?php echo opf_render_boxes($data['guardian_nid'] ?? '', 17); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">Date of Birth:</td>
                    <td style="width: 35%;">
                        <?php if ($is_edit_mode) : ?>
                            <input type="date" name="guardian_dob" class="opf-edit-input" value="<?php echo esc_attr($data['guardian_dob'] ?? ''); ?>">
                        <?php else : ?>
                            <span class="opf-val">
                                <?php 
                                echo !empty($data['guardian_dob']) ? date('d/m/Y', strtotime($data['guardian_dob'])) : 'DD/MM/YYYY'; 
                                ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="border: none; width: 145px;" class="opf-lbl">Relation with Applicant:</td>
                                <td style="border: none;">
                                    <?php if ($is_edit_mode) : ?>
                                        <input type="text" name="guardian_relation" class="opf-edit-input" value="<?php echo esc_attr($data['guardian_relation'] ?? ''); ?>" placeholder="e.g. Uncle, Grandfather">
                                    <?php else : ?>
                                        <span class="opf-val"><?php echo esc_html($data['guardian_relation'] ?? ''); ?></span>
                                    <?php endif; ?>
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
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="present_house" class="opf-edit-input" value="<?php echo esc_attr($data['present_house'] ?? ''); ?>" placeholder="House / Holding">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['present_house'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="width: 90px;" class="opf-lbl">Village:</td>
                    <td>
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="present_village" class="opf-edit-input" value="<?php echo esc_attr($data['present_village'] ?? ''); ?>" placeholder="Village / Road">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['present_village'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">Post Office:</td>
                    <td>
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="present_post_office" class="opf-edit-input" value="<?php echo esc_attr($data['present_post_office'] ?? ''); ?>" placeholder="Post Office">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['present_post_office'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="opf-lbl">Police Station:</td>
                    <td>
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="present_police_station" class="opf-edit-input" value="<?php echo esc_attr($data['present_police_station'] ?? ''); ?>" placeholder="Police Station">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['present_police_station'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">District:</td>
                    <td>
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="present_district" class="opf-edit-input" value="<?php echo esc_attr($data['present_district'] ?? $app->district); ?>" placeholder="District">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['present_district'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="opf-lbl">Country:</td>
                    <td>
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="present_country" class="opf-edit-input" value="<?php echo esc_attr($data['present_country'] ?? 'Bangladesh'); ?>" placeholder="Country">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['present_country'] ?? 'Bangladesh'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Permanent Address -->
                <tr>
                    <td rowspan="3" class="opf-lbl">Permanent Address:</td>
                    <td class="opf-lbl">House:</td>
                    <td>
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="permanent_house" class="opf-edit-input" value="<?php echo esc_attr($data['permanent_house'] ?? ''); ?>" placeholder="House / Holding">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['permanent_house'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="opf-lbl">Village:</td>
                    <td>
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="permanent_village" class="opf-edit-input" value="<?php echo esc_attr($data['permanent_village'] ?? ''); ?>" placeholder="Village / Road">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['permanent_village'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">Post Office:</td>
                    <td>
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="permanent_post_office" class="opf-edit-input" value="<?php echo esc_attr($data['permanent_post_office'] ?? ''); ?>" placeholder="Post Office">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['permanent_post_office'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="opf-lbl">Police Station:</td>
                    <td>
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="permanent_police_station" class="opf-edit-input" value="<?php echo esc_attr($data['permanent_police_station'] ?? ''); ?>" placeholder="Police Station">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['permanent_police_station'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl">District:</td>
                    <td>
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="permanent_district" class="opf-edit-input" value="<?php echo esc_attr($data['permanent_district'] ?? ''); ?>" placeholder="District">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['permanent_district'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="opf-lbl">Country:</td>
                    <td>
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="permanent_country" class="opf-edit-input" value="<?php echo esc_attr($data['permanent_country'] ?? 'Bangladesh'); ?>" placeholder="Country">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['permanent_country'] ?? 'Bangladesh'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <!-- 4. SAP References -->
            <div class="opf-pdf-section-title">SAP References</div>
            <table class="opf-table">
                <tr>
                    <td style="width: 250px;" class="opf-lbl">Name of Reference (SAP):</td>
                    <td>
                        <?php if ($is_edit_mode) : ?>
                            <input type="text" name="sap_reference_name" class="opf-edit-input" value="<?php echo esc_attr($data['sap_reference_name'] ?? $app->sap_reference); ?>" placeholder="Name of Reference (SAP)">
                        <?php else : ?>
                            <span class="opf-val"><?php echo esc_html($data['sap_reference_name'] ?? ''); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="opf-lbl" style="border-right: none;">Contact/Mobile Number:</td>
                    <td style="border-left: none; text-align: right;">
                        <?php if ($is_edit_mode) : ?>
                            <div class="opf-edit-inline-wrap">
                                <span class="opf-edit-prefix">+880</span>
                                <input type="text" name="sap_reference_mobile" class="opf-edit-input opf-digits-only" maxlength="11" inputmode="numeric" value="<?php echo esc_attr(opf_clean_mobile_for_input($data['sap_reference_mobile'] ?? '')); ?>" placeholder="1XXXXXXXXX">
                            </div>
                        <?php else : ?>
                            <div style="display: flex; justify-content: flex-end;">
                                <?php echo opf_render_phone_boxes($data['sap_reference_mobile'] ?? ''); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ========================================================
             PAGE 2: Orphan education status & Signatures
             ======================================================== -->
        <div class="opf-a4-page opf-page-2 <?php echo !$has_photos_page ? 'opf-pdf-page-last' : ''; ?>">
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
                        <td><?php echo opf_eval_radio('health_physical', 'NeedImprovement', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('health_physical', 'Acceptable', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('health_physical', 'Very Good', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('health_physical', 'Excellent', $eval, $is_edit_mode); ?></td>
                    </tr>
                    <tr>
                        <td>Reading Skills</td>
                        <td><?php echo opf_eval_radio('reading_skills', 'NeedImprovement', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('reading_skills', 'Acceptable', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('reading_skills', 'Very Good', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('reading_skills', 'Excellent', $eval, $is_edit_mode); ?></td>
                    </tr>
                    <tr>
                        <td>Writing Skills</td>
                        <td><?php echo opf_eval_radio('writing_skills', 'NeedImprovement', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('writing_skills', 'Acceptable', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('writing_skills', 'Very Good', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('writing_skills', 'Excellent', $eval, $is_edit_mode); ?></td>
                    </tr>
                    <tr>
                        <td>Speking Skills</td>
                        <td><?php echo opf_eval_radio('speaking_skills', 'NeedImprovement', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('speaking_skills', 'Acceptable', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('speaking_skills', 'Very Good', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('speaking_skills', 'Excellent', $eval, $is_edit_mode); ?></td>
                    </tr>
                    <tr>
                        <td>Listening Skills</td>
                        <td><?php echo opf_eval_radio('listening_skills', 'NeedImprovement', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('listening_skills', 'Acceptable', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('listening_skills', 'Very Good', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('listening_skills', 'Excellent', $eval, $is_edit_mode); ?></td>
                    </tr>
                    <tr>
                        <td>His/Her Effort on Education</td>
                        <td><?php echo opf_eval_radio('effort_education', 'NeedImprovement', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('effort_education', 'Acceptable', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('effort_education', 'Very Good', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('effort_education', 'Excellent', $eval, $is_edit_mode); ?></td>
                    </tr>
                    <tr>
                        <td>Class Behaviour</td>
                        <td><?php echo opf_eval_radio('class_behaviour', 'NeedImprovement', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('class_behaviour', 'Acceptable', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('class_behaviour', 'Very Good', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('class_behaviour', 'Excellent', $eval, $is_edit_mode); ?></td>
                    </tr>
                    <tr>
                        <td>Completion of Homework</td>
                        <td><?php echo opf_eval_radio('homework_done', 'NeedImprovement', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('homework_done', 'Acceptable', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('homework_done', 'Very Good', $eval, $is_edit_mode); ?></td>
                        <td><?php echo opf_eval_radio('homework_done', 'Excellent', $eval, $is_edit_mode); ?></td>
                    </tr>
                </tbody>
            </table>

            <p class="opf-note-para">
                Note: The above section will be filled out manually by the SAP volunteer.
            </p>

            <!-- Signatures Section matching original PDF (Image 2) -->
            <?php
            $sig_applicant_name = !empty($data['applicant_signature_name']) ? $data['applicant_signature_name'] : $app->student_name;
            $raw_sig_date = $data['application_date'] ?? '';
            if (!empty($raw_sig_date)) {
                $sig_applicant_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw_sig_date) ? date('d/m/Y', strtotime($raw_sig_date)) : $raw_sig_date;
            } else {
                $sig_applicant_date = date('d/m/Y', strtotime($app->created_at));
            }
            $sig_auth_name      = $data['authorised_signature_name'] ?? '';
            $sig_auth_date      = $data['authorised_signature_date'] ?? '';
            ?>
            <div class="opf-signatures-row">
                <div class="opf-sig-block">
                    <div class="opf-sig-img-wrap">
                        <?php if (!empty($sig_image_url)) : ?>
                            <img src="<?php echo esc_url($sig_image_url); ?>" alt="Signature of Applicant" class="opf-sig-img">
                        <?php endif; ?>
                    </div>
                    <div class="opf-sig-line"></div>
                    <div class="opf-sig-title">Signature of Applicant</div>
                    <div class="opf-sig-item">
                        <?php if ($is_edit_mode) : ?>
                            <div class="opf-edit-inline-wrap" style="margin-top: 4px;">
                                <span style="min-width: 38px;">Name:</span>
                                <input type="text" name="applicant_signature_name" class="opf-edit-input" value="<?php echo esc_attr($sig_applicant_name); ?>" placeholder="Applicant signature name">
                            </div>
                        <?php else : ?>
                            Name: <strong><?php echo esc_html($sig_applicant_name); ?></strong>
                        <?php endif; ?>
                    </div>
                    <div class="opf-sig-item">
                        <?php if ($is_edit_mode) : ?>
                            <div class="opf-edit-inline-wrap" style="margin-top: 4px;">
                                <span style="min-width: 38px;">Date :</span>
                                <input type="text" name="application_date" class="opf-edit-input" value="<?php echo esc_attr($sig_applicant_date); ?>" placeholder="DD/MM/YYYY">
                            </div>
                        <?php else : ?>
                            Date : <?php echo esc_html($sig_applicant_date); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="opf-sig-block">
                    <div class="opf-sig-img-wrap"></div>
                    <div class="opf-sig-line"></div>
                    <div class="opf-sig-title">Authorised Signature</div>
                    <div class="opf-sig-item">
                        <?php if ($is_edit_mode) : ?>
                            <div class="opf-edit-inline-wrap" style="margin-top: 4px;">
                                <span style="min-width: 38px;">Name:</span>
                                <input type="text" name="authorised_signature_name" class="opf-edit-input" value="<?php echo esc_attr($sig_auth_name); ?>" placeholder="Authorised name">
                            </div>
                        <?php else : ?>
                            Name:<?php echo !empty($sig_auth_name) ? ' <strong>' . esc_html($sig_auth_name) . '</strong>' : ''; ?>
                        <?php endif; ?>
                    </div>
                    <div class="opf-sig-item">
                        <?php if ($is_edit_mode) : ?>
                            <div class="opf-edit-inline-wrap" style="margin-top: 4px;">
                                <span style="min-width: 38px;">Date :</span>
                                <input type="text" name="authorised_signature_date" class="opf-edit-input" value="<?php echo esc_attr($sig_auth_date); ?>" placeholder="DD/MM/YYYY">
                            </div>
                        <?php else : ?>
                            Date :<?php echo !empty($sig_auth_date) ? ' ' . esc_html($sig_auth_date) : ''; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div> <!-- /.opf-page-2 -->

        <?php if ($has_photos_page) : ?>
        <!-- ========================================================
             PAGE 3: Uploaded Photographs (Short/Passport & Half Photo)
             ======================================================== -->
        <div class="opf-a4-page opf-page-3 opf-pdf-page-last">

            <div class="opf-photos-grid">
                <div class="opf-photo-frame-card">
                    <div class="opf-photo-frame-label">Short / Passport Photo</div>
                    <div class="opf-photo-frame-inner">
                        <?php if (!empty($short_photo_url)) : ?>
                            <img src="<?php echo esc_url($short_photo_url); ?>" alt="Short / Passport Photo">
                        <?php else : ?>
                            <span class="opf-photo-empty-note">Not provided</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="opf-photo-frame-card">
                    <div class="opf-photo-frame-label">Half Photo</div>
                    <div class="opf-photo-frame-inner">
                        <?php if (!empty($half_photo_url)) : ?>
                            <img src="<?php echo esc_url($half_photo_url); ?>" alt="Half Photo">
                        <?php else : ?>
                            <span class="opf-photo-empty-note">Not provided</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
        <?php endif; ?>

        </div> <!-- /#opf-pdf-content -->
        <?php if ($is_edit_mode) : ?>
        </form>
        <?php endif; ?>
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

    var opfGeneratedPdfBlob = null;
    var opfGeneratedPdfUrl = null;
    var opfPdfGenPromise = null;
    var opfPdfFilename = 'Application-<?php echo esc_js($app->application_no ?: $app->id); ?>.pdf';
    var opfIsAdminView = <?php echo !empty($_GET['opf_admin']) ? 'true' : 'false'; ?>;
    var opfIsEditMode = <?php echo $is_edit_mode ? 'true' : 'false'; ?>;

    function waitForAllPdfImages(container) {
        var imgs = Array.prototype.slice.call(container.querySelectorAll('img'));
        return Promise.all(imgs.map(function(img) {
            if (img.complete) return Promise.resolve();
            return new Promise(function(resolve) {
                var done = function() {
                    img.removeEventListener('load', done);
                    img.removeEventListener('error', done);
                    resolve();
                };
                img.addEventListener('load', done);
                img.addEventListener('error', done);
                setTimeout(done, 4000);
            });
        }));
    }

    function generatePdfBlob() {
        if (opfGeneratedPdfUrl && opfGeneratedPdfBlob) {
            return Promise.resolve({ blob: opfGeneratedPdfBlob, url: opfGeneratedPdfUrl });
        }
        if (opfPdfGenPromise) {
            return opfPdfGenPromise;
        }

        var container = document.getElementById('opf-pdf-content');
        if (!container) {
            return Promise.reject(new Error('PDF container not found'));
        }

        opfPdfGenPromise = waitForAllPdfImages(container).then(function() {
            return new Promise(function(resolve, reject) {
                var wrapper = document.getElementById('opf-pdf-outer-wrapper');
                var wasHidden = wrapper && wrapper.style.display === 'none';
                if (wasHidden) wrapper.style.display = '';

                if ('zoom' in container.style) {
                    container.style.zoom = '1';
                }
                container.style.transform = 'none';
                container.classList.add('opf-pdf-exporting');

                setTimeout(function() {
                    var opt = {
                        margin:      0,
                        filename:    opfPdfFilename,
                        image:       { type: 'jpeg', quality: 0.98 },
                        html2canvas: {
                            scale: 2,
                            useCORS: true,
                            logging: false,
                            scrollY: 0,
                            scrollX: 0
                        },
                        jsPDF: {
                            unit: 'mm',
                            format: 'a4',
                            orientation: 'portrait'
                        },
                        pagebreak: {
                            mode: ['css', 'legacy']
                        }
                    };

                    html2pdf().set(opt).from(container).outputPdf('blob').then(function(blob) {
                        container.classList.remove('opf-pdf-exporting');
                        var zoomValEl = document.getElementById('opf-zoom-val');
                        applyScale(currentScale, zoomValEl && zoomValEl.innerText === 'Fit');
                        if (wasHidden) wrapper.style.display = 'none';

                        opfGeneratedPdfBlob = blob;
                        if (opfGeneratedPdfUrl) {
                            try { URL.revokeObjectURL(opfGeneratedPdfUrl); } catch(e) {}
                        }
                        opfGeneratedPdfUrl = URL.createObjectURL(blob);
                        resolve({ blob: blob, url: opfGeneratedPdfUrl });
                    }).catch(function(err) {
                        container.classList.remove('opf-pdf-exporting');
                        applyScale(currentScale, false);
                        if (wasHidden) wrapper.style.display = 'none';
                        opfPdfGenPromise = null;
                        reject(err);
                    });
                }, 150);
            });
        });

        return opfPdfGenPromise;
    }

    function triggerBlobDownload(blobUrl, filename) {
        var a = document.createElement('a');
        a.href = blobUrl;
        a.download = filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        setTimeout(function() {
            if (a.parentNode) a.parentNode.removeChild(a);
        }, 250);
    }

    function downloadApplicationPDF() {
        var btn = document.getElementById('opf-btn-download');
        if (btn && btn.classList.contains('is-loading')) return;

        var originalHtml = btn ? btn.innerHTML : '';
        if (btn && !opfGeneratedPdfUrl) {
            btn.classList.add('is-loading');
            btn.disabled = true;
            btn.innerHTML = '<svg class="opf-spin" style="width:16px;height:16px;margin-right:6px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path></svg> Downloading PDF...';
        }

        generatePdfBlob().then(function(res) {
            triggerBlobDownload(res.url, opfPdfFilename);

            if (btn) {
                btn.classList.remove('is-loading');
                btn.disabled = false;
                btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:2px;"><polyline points="20 6 9 17 4 12"></polyline></svg> Downloaded!';
                setTimeout(function() {
                    btn.innerHTML = originalHtml;
                }, 2500);
            }

            try {
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({
                        type: 'opf_download_success',
                        app_no: '<?php echo esc_js($app->application_no ?: $app->id); ?>'
                    }, '*');
                }
            } catch(e) {}
        }).catch(function(err) {
            console.error('PDF generation error:', err);
            if (btn) {
                btn.classList.remove('is-loading');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }

            try {
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({
                        type: 'opf_download_error',
                        app_no: '<?php echo esc_js($app->application_no ?: $app->id); ?>'
                    }, '*');
                }
            } catch(e) {}

            window.print();
        });
    }

    function copyAppIdToClipboard(id, btn) {
        if (!id) return;
        function onCopied() {
            if (!btn) return;
            var textSpan = btn.querySelector('.opf-copy-btn-text');
            var iconDef = btn.querySelector('.opf-copy-icon-default');
            var iconSucc = btn.querySelector('.opf-copy-icon-success');

            btn.classList.add('copied');
            if (textSpan) textSpan.textContent = 'Copied!';
            if (iconDef) iconDef.style.display = 'none';
            if (iconSucc) iconSucc.style.display = 'inline-block';

            setTimeout(function() {
                btn.classList.remove('copied');
                if (textSpan) textSpan.textContent = 'Copy';
                if (iconDef) iconDef.style.display = 'inline-block';
                if (iconSucc) iconSucc.style.display = 'none';
            }, 2000);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(id).then(onCopied).catch(function() {
                fallbackCopy(id, onCopied);
            });
        } else {
            fallbackCopy(id, onCopied);
        }
    }

    function fallbackCopy(text, cb) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.top = '0';
        ta.style.left = '0';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        try {
            document.execCommand('copy');
            if (cb) cb();
        } catch(e) {}
        document.body.removeChild(ta);
    }

    <?php if ($is_edit_mode) : ?>
    (function() {
        var standaloneActions = document.getElementById('opf-standalone-edit-actions');
        if (standaloneActions && window.parent === window) {
            standaloneActions.style.display = 'inline-flex';
        }

        // Enforce digits-only on NID, Birth Reg, Death Reg, and Phone fields
        document.querySelectorAll('.opf-digits-only').forEach(function(inp) {
            inp.addEventListener('input', function() {
                var clean = this.value.replace(/[^0-9]/g, '');
                if (this.value !== clean) this.value = clean;
            });
        });

        // Auto-calculate student age when DOB changes
        var dobInput = document.getElementById('opf-edit-student-dob');
        var ageInput = document.getElementById('opf-edit-student-age');
        if (dobInput && ageInput) {
            dobInput.addEventListener('change', function() {
                if (!this.value) return;
                var b = new Date(this.value);
                if (isNaN(b.getTime())) return;
                var today = new Date();
                var age = today.getFullYear() - b.getFullYear();
                var m = today.getMonth() - b.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < b.getDate())) {
                    age--;
                }
                if (age >= 0 && age <= 120) {
                    ageInput.value = age;
                }
            });
        }

        // Live toggle visual radio circles on Page 2
        document.querySelectorAll('.opf-edit-radio-input').forEach(function(radio) {
            radio.addEventListener('change', function() {
                var groupName = this.name;
                document.querySelectorAll('input[name="' + groupName.replace(/([[\]])/g, '\\$1') + '"]').forEach(function(r) {
                    var span = r.nextElementSibling;
                    if (span) {
                        if (r.checked) {
                            span.classList.add('selected');
                            span.textContent = '●';
                        } else {
                            span.classList.remove('selected');
                            span.textContent = '◯';
                        }
                    }
                });
            });
        });
    })();

    function saveApplicationEdits() {
        var form = document.getElementById('opf-admin-edit-pdf-form');
        if (!form) return;

        var standaloneBtn = document.getElementById('opf-standalone-save-btn');
        var origBtnHtml = standaloneBtn ? standaloneBtn.innerHTML : '';
        if (standaloneBtn) {
            standaloneBtn.disabled = true;
            standaloneBtn.innerHTML = 'Saving...';
        }

        var fd = new FormData(form);
        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res && res.success) {
                try {
                    if (window.parent && window.parent !== window) {
                        window.parent.postMessage({
                            type: 'opf_edit_saved',
                            data: res.data
                        }, '*');
                        return;
                    }
                } catch(e) {}
                // Standalone tab: return to view mode
                window.location.href = '<?php echo esc_url(remove_query_arg('opf_edit')); ?>';
            } else {
                var msg = (res && res.data && res.data.message) ? res.data.message : 'Failed to save PDF changes.';
                try {
                    if (window.parent && window.parent !== window) {
                        window.parent.postMessage({
                            type: 'opf_edit_error',
                            message: msg
                        }, '*');
                    } else {
                        alert(msg);
                    }
                } catch(e) { alert(msg); }
                if (standaloneBtn) {
                    standaloneBtn.disabled = false;
                    standaloneBtn.innerHTML = origBtnHtml;
                }
            }
        })
        .catch(function(err) {
            console.error('Save edit error:', err);
            try {
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({
                        type: 'opf_edit_error',
                        message: 'Server error while saving changes.'
                    }, '*');
                } else {
                    alert('Server error while saving changes.');
                }
            } catch(e) {}
            if (standaloneBtn) {
                standaloneBtn.disabled = false;
                standaloneBtn.innerHTML = origBtnHtml;
            }
        });
    }
    <?php endif; ?>

    <?php if (!$is_edit_mode && !empty($_GET['opf_auto_download'])) : ?>
    window.addEventListener('load', function() {
        setTimeout(function() {
            downloadApplicationPDF();
        }, 400);
    });
    <?php elseif (!$is_edit_mode) : ?>
    // Live client-side PDF generation (never saved on server): show generated PDF & download the exact same blob
    window.addEventListener('load', function() {
        setTimeout(function() {
            previewApplicationPDF();
        }, 250);
    });
    <?php endif; ?>

    function positionGeneratedPdfViewer() {
        var viewer = document.getElementById('opf-generated-pdf-viewer');
        if (!viewer) return;
        if (opfIsAdminView) {
            viewer.style.top = '0px';
            viewer.style.height = '100vh';
            return;
        }
        var controls = document.querySelector('.opf-view-controls');
        var topOffset = controls ? controls.offsetHeight : 0;
        viewer.style.top = topOffset + 'px';
        viewer.style.height = 'calc(100vh - ' + topOffset + 'px)';
    }

    function previewApplicationPDF() {
        if (opfIsEditMode) return;
        var container = document.getElementById('opf-pdf-content');
        if (!container) return;

        var overlay = document.getElementById('opf-preview-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'opf-preview-overlay';
            overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.72);backdrop-filter:blur(2px);z-index:99999;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#fff;font-family:Arial,sans-serif;';
            overlay.innerHTML = '<div style="font-size:16px;font-weight:600;margin-bottom:12px;letter-spacing:0.2px;">Generating PDF...</div>'
                + '<div style="width:220px;height:6px;background:rgba(255,255,255,0.2);border-radius:4px;overflow:hidden;">'
                + '<div id="opf-preview-bar" style="height:100%;width:15%;background:#38bdf8;border-radius:4px;transition:width 0.25s ease;"></div></div>';
            document.body.appendChild(overlay);
        }

        var bar = document.getElementById('opf-preview-bar');
        var pct = 15;
        var barTimer = setInterval(function() {
            pct = Math.min(pct + 6, 88);
            if (bar) bar.style.width = pct + '%';
        }, 120);

        generatePdfBlob().then(function(res) {
            clearInterval(barTimer);
            if (bar) bar.style.width = '100%';

            var canInlinePdf = (typeof navigator.pdfViewerEnabled === 'boolean')
                ? navigator.pdfViewerEnabled
                : (window.innerWidth > 768);

            setTimeout(function() {
                var ov = document.getElementById('opf-preview-overlay');
                if (ov && ov.parentNode) ov.parentNode.removeChild(ov);

                if (canInlinePdf) {
                    var wrapper = document.getElementById('opf-pdf-outer-wrapper');
                    if (wrapper) wrapper.style.display = 'none';

                    var floatZoom = document.querySelector('.opf-floating-zoom');
                    if (floatZoom) floatZoom.style.display = 'none';

                    var zoomControls = document.querySelector('.opf-zoom-controls');
                    if (zoomControls) zoomControls.style.display = 'none';

                    if (opfIsAdminView) {
                        var topBar = document.querySelector('.opf-view-controls');
                        if (topBar) topBar.style.display = 'none';
                    }

                    document.body.style.padding = '0';
                    document.body.style.overflow = 'hidden';

                    var viewer = document.getElementById('opf-generated-pdf-viewer');
                    if (!viewer) {
                        viewer = document.createElement('iframe');
                        viewer.id = 'opf-generated-pdf-viewer';
                        viewer.title = opfPdfFilename;
                        viewer.style.cssText = 'position:fixed;left:0;right:0;width:100%;border:none;background:#525659;z-index:9990;';
                        document.body.appendChild(viewer);
                        window.addEventListener('resize', positionGeneratedPdfViewer);
                    }
                    positionGeneratedPdfViewer();
                    viewer.src = res.url + '#navpanes=0';
                }
            }, 180);
        }).catch(function(err) {
            clearInterval(barTimer);
            var ov = document.getElementById('opf-preview-overlay');
            if (ov && ov.parentNode) ov.parentNode.removeChild(ov);
            console.error('PDF preview error:', err);
        });
    }

    window.addEventListener('beforeunload', function() {
        if (opfGeneratedPdfUrl) {
            try { URL.revokeObjectURL(opfGeneratedPdfUrl); } catch(e) {}
            opfGeneratedPdfUrl = null;
        }
    });
    </script>
</body>
</html>
