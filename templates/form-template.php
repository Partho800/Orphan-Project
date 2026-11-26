<?php
/**
 * Orphan Project Multi-Step Registration Form Template
 * Based on 'Orphan Information New 19_5_26.pdf'
 * 100% English Version with Original Top Header Logos
 */

if (!defined('ABSPATH')) {
    exit;
}

$form_id = 'orphan-multi-step-form-' . uniqid();
$today_date = date('Y-m-d');
$left_logo_url  = OPF_PLUGIN_URL . 'assets/images/logo-sap.png';
$right_logo_url = OPF_PLUGIN_URL . 'assets/images/logo-hasene.png';
?>

<div class="opf-wizard-shell" id="<?php echo esc_attr($form_id); ?>-shell">
    <!-- Left Sidebar: Step List Navigator -->
    <aside class="opf-side-panel opf-side-left" aria-label="Application Steps">
        <div class="opf-side-card">
            <div class="opf-side-header">
                <span class="opf-side-eyebrow">Steps</span>
                <span class="opf-side-pill">5 Total</span>
            </div>
            <ul class="opf-side-steps-list">
                <li class="opf-side-step-item active" data-step="1" tabindex="0" role="button" aria-label="Step 1: Personal Info">
                    <div class="opf-side-step-circle">
                        <span class="opf-side-num">1</span>
                        <svg class="opf-side-check" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                    <div class="opf-side-step-meta">
                        <span class="opf-side-step-sub">Step 1</span>
                        <strong class="opf-side-step-title">Personal Info</strong>
                    </div>
                </li>
                <li class="opf-side-step-item" data-step="2" tabindex="0" role="button" aria-label="Step 2: Family and Guardian">
                    <div class="opf-side-step-circle">
                        <span class="opf-side-num">2</span>
                        <svg class="opf-side-check" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                    <div class="opf-side-step-meta">
                        <span class="opf-side-step-sub">Step 2</span>
                        <strong class="opf-side-step-title">Family &amp; Guardian</strong>
                    </div>
                </li>
                <li class="opf-side-step-item" data-step="3" tabindex="0" role="button" aria-label="Step 3: Address and Reference">
                    <div class="opf-side-step-circle">
                        <span class="opf-side-num">3</span>
                        <svg class="opf-side-check" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                    <div class="opf-side-step-meta">
                        <span class="opf-side-step-sub">Step 3</span>
                        <strong class="opf-side-step-title">Address &amp; Ref</strong>
                    </div>
                </li>
                <li class="opf-side-step-item" data-step="4" tabindex="0" role="button" aria-label="Step 4: Education Status">
                    <div class="opf-side-step-circle">
                        <span class="opf-side-num">4</span>
                        <svg class="opf-side-check" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                    <div class="opf-side-step-meta">
                        <span class="opf-side-step-sub">Step 4</span>
                        <strong class="opf-side-step-title">Education Status</strong>
                    </div>
                </li>
                <li class="opf-side-step-item" data-step="5" tabindex="0" role="button" aria-label="Step 5: Photos and Signature">
                    <div class="opf-side-step-circle">
                        <span class="opf-side-num">5</span>
                        <svg class="opf-side-check" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                    <div class="opf-side-step-meta">
                        <span class="opf-side-step-sub">Step 5</span>
                        <strong class="opf-side-step-title">Photos &amp; Signature</strong>
                    </div>
                </li>
            </ul>
        </div>
    </aside>

<div class="opf-container" id="<?php echo esc_attr($form_id); ?>">
    <!-- Top Header Banner matching the PDF -->
    <div class="opf-header-top">
        <div class="opf-logos-wrap">
            <div class="opf-logo-col opf-logo-left">
                <img src="<?php echo esc_url($left_logo_url); ?>" alt="Society for Aid Programs (SAP)" class="opf-header-logo opf-logo-sap">
            </div>
            <div class="opf-project-title-col">
                <h1 class="opf-project-main-title">ORPHAN PROJECT</h1>
            </div>
            <div class="opf-logo-col opf-logo-right">
                <img src="<?php echo esc_url($right_logo_url); ?>" alt="HASENE" class="opf-header-logo opf-logo-hasene">
            </div>
        </div>
    </div>

    <!-- Top 2-Tab Navigation Bar (New Application & Old Application) -->
    <div class="opf-action-modes-bar opf-main-tabs-bar" role="tablist">
        <button type="button" role="tab" aria-selected="true" class="opf-main-tab active" id="opf-mode-btn-online" data-target="opf-online-mode">
            <div class="opf-type-text">
                <strong class="opf-type-title">New Application</strong>
                <span class="opf-type-desc">Fill out a new online application form</span>
            </div>
        </button>

        <button type="button" role="tab" aria-selected="false" class="opf-main-tab" id="opf-mode-btn-upload" data-target="opf-old-mode">
            <div class="opf-type-text">
                <strong class="opf-type-title">Old Application</strong>
                <span class="opf-type-desc">Update existing application with your ID</span>
            </div>
        </button>
    </div>

    <!-- Alert Message Container -->
    <div class="opf-alert-box" style="display: none;"></div>

    <div id="opf-online-mode" class="opf-mode-content">
    <form class="opf-form" method="post" novalidate>
        <?php wp_nonce_field('orphan_form_nonce_action', 'orphan_form_nonce'); ?>
        <input type="hidden" name="action" value="submit_orphan_registration_form">
        <input type="hidden" name="application_mode" id="opf-application-mode" value="new">

        <!-- ========================================================
             STEP 1: Student's Personal Information
             ======================================================== -->
        <div class="opf-step-pane active" data-step="1">
            <div class="opf-pane-header">
                <div class="opf-pane-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <div class="opf-pane-header-content">
                    <h3 class="opf-section-title">Student's Personal Information</h3>
                    <p class="opf-section-desc">Please fill out the student's personal details accurately</p>
                </div>
            </div>

            <!-- Extra Required ID Field shown only in Old Application mode -->
            <div id="opf-old-id-field-wrap" class="opf-old-id-wrap" style="display: none;">
                <div class="opf-field-group">
                    <label class="opf-label required">ID</label>
                    <input type="text" name="old_application_id" id="opf-old-application-id" class="opf-input" placeholder="Enter existing Application ID">
                    <span class="opf-err-msg" id="opf-old-id-err">Valid existing ID is required.</span>
                </div>
            </div>

            <div class="opf-grid-2">
                <div class="opf-field-group">
                    <label class="opf-label required">First Name</label>
                    <input type="text" name="student_first_name" class="opf-input opf-req" placeholder="First name" required>
                    <span class="opf-err-msg">First name is required.</span>
                </div>
                <div class="opf-field-group">
                    <label class="opf-label required">Surname</label>
                    <input type="text" name="student_surname" class="opf-input opf-req" placeholder="Surname" required>
                    <span class="opf-err-msg">Surname is required.</span>
                </div>
            </div>

            <div class="opf-grid-3">
                <div class="opf-field-group">
                    <label class="opf-label required">Gender</label>
                    <div class="opf-radio-group">
                        <label class="opf-radio-label">
                            <input type="radio" name="gender" value="Male" class="opf-radio" checked> Male
                        </label>
                        <label class="opf-radio-label">
                            <input type="radio" name="gender" value="Female" class="opf-radio"> Female
                        </label>
                        <label class="opf-radio-label">
                            <input type="radio" name="gender" value="Other" class="opf-radio"> Other
                        </label>
                    </div>
                </div>

                <div class="opf-field-group">
                    <label class="opf-label required">Student Date of Birth</label>
                    <input type="date" name="date_of_birth" class="opf-input opf-req opf-dob-field" required>
                    <span class="opf-err-msg">Student date of birth is required.</span>
                </div>

                <div class="opf-field-group">
                    <label class="opf-label">Age</label>
                    <input type="text" name="age" class="opf-input opf-age-field" placeholder="Years, Months, Days" readonly>
                </div>
            </div>

            <div class="opf-sub-card">
                <h4 class="opf-sub-card-title">Number of Siblings</h4>
                <div class="opf-grid-3">
                    <div class="opf-field-group">
                        <label class="opf-label">Brothers</label>
                        <input type="number" name="siblings_brothers" class="opf-input" min="0" value="0">
                    </div>
                    <div class="opf-field-group">
                        <label class="opf-label">Sisters</label>
                        <input type="number" name="siblings_sisters" class="opf-input" min="0" value="0">
                    </div>
                    <div class="opf-field-group">
                        <label class="opf-label">Your Position</label>
                        <input type="text" name="siblings_position" class="opf-input" placeholder="1st, 2nd, 3rd, etc.">
                    </div>
                </div>
            </div>

            <div class="opf-grid-2">
                <div class="opf-field-group">
                    <label class="opf-label required">Contact / Mobile Number</label>
                    <div class="opf-phone-wrapper">
                        <span class="opf-phone-prefix">+880</span>
                        <input type="tel" name="student_mobile" class="opf-input opf-phone-input opf-req" placeholder="1XXXXXXXXX" maxlength="10" pattern="[0-9]{10}" required>
                    </div>
                    <span class="opf-err-msg">Valid 10-digit mobile number is required.</span>
                </div>

                <div class="opf-field-group">
                    <label class="opf-label required">Student Birth Registration Number</label>
                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="birth_registration_number" class="opf-input opf-req opf-digits-only" placeholder="17-Digit Birth Registration No" maxlength="17" required>
                    <span class="opf-err-msg">17-digit student birth registration number is required.</span>
                </div>
            </div>

            <div class="opf-grid-2">
                <div class="opf-field-group">
                    <label class="opf-label">National Identification Number (NID)</label>
                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="student_nid" class="opf-input opf-digits-only" placeholder="10 or 17 Digit NID Number" maxlength="17">
                </div>

                <div class="opf-field-group">
                    <label class="opf-label required">Name of Colleges / School</label>
                    <input type="text" name="college_school_name" class="opf-input opf-req" placeholder="College / School Name" required>
                    <span class="opf-err-msg">College or school name is required.</span>
                </div>
            </div>

            <div class="opf-grid-3">
                <div class="opf-field-group">
                    <label class="opf-label">Class</label>
                    <input type="text" name="studying_class" class="opf-input" placeholder="Class / Year">
                </div>

                <div class="opf-field-group">
                    <label class="opf-label">Studying - ID No</label>
                    <input type="text" name="studying_id_no" class="opf-input" placeholder="ID / Roll No">
                </div>

                <div class="opf-field-group">
                    <label class="opf-label">Studying - Group / Subject</label>
                    <input type="text" name="studying_group_subject" class="opf-input" placeholder="Science / Arts / Commerce">
                </div>
            </div>

            <div class="opf-actions-bar">
                <div></div>
                <button type="button" class="opf-btn opf-btn-next" data-next="2">
                    Next
                </button>
            </div>
        </div>

        <!-- ========================================================
             STEP 2: Family and Guardians Information
             ======================================================== -->
        <div class="opf-step-pane" data-step="2">
            <div class="opf-pane-header">
                <div class="opf-pane-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <div class="opf-pane-header-content">
                    <h3 class="opf-section-title">Family and Guardians Information</h3>
                    <p class="opf-section-desc">Provide information about parents and current guardians</p>
                </div>
            </div>

            <!-- Father's Info Box -->
            <div class="opf-card-section">
                <div class="opf-card-heading">
                    <span class="opf-card-badge">A</span>
                    <h4>Father's Information</h4>
                </div>

                <div class="opf-grid-2">
                    <div class="opf-field-group">
                        <label class="opf-label required">Full Name of Father</label>
                        <input type="text" name="father_full_name" class="opf-input opf-req" placeholder="Enter father's full name" required>
                        <span class="opf-err-msg">Father's name is required.</span>
                    </div>

                    <div class="opf-field-group">
                        <label class="opf-label">Contact / Mobile Number</label>
                        <div class="opf-phone-wrapper">
                            <span class="opf-phone-prefix">+880</span>
                            <input type="tel" name="father_mobile" class="opf-input opf-phone-input" placeholder="1XXXXXXXXX" maxlength="10">
                        </div>
                    </div>
                </div>

                <div class="opf-grid-3">
                    <div class="opf-field-group">
                        <label class="opf-label">National Identification Number (NID)</label>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" name="father_nid" class="opf-input opf-digits-only" placeholder="NID Number" maxlength="17">
                    </div>

                    <div class="opf-field-group">
                        <label class="opf-label">Occupation</label>
                        <input type="text" name="father_occupation" class="opf-input" placeholder="Occupation">
                    </div>

                    <div class="opf-field-group">
                        <label class="opf-label">Monthly Income</label>
                        <input type="number" name="father_monthly_income" class="opf-input" placeholder="Monthly income amount" min="0">
                    </div>
                </div>

                <div class="opf-grid-2 opf-deceased-fields" id="opf-father-deceased-details">
                    <div class="opf-field-group">
                        <label class="opf-label">Death Registration Number</label>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" name="father_death_reg_no" class="opf-input opf-digits-only" placeholder="Death Registration No" maxlength="17">
                    </div>

                    <div class="opf-field-group">
                        <label class="opf-label">Cause of Death</label>
                        <input type="text" name="father_cause_of_death" class="opf-input" placeholder="Cause of death">
                    </div>
                </div>
            </div>

            <!-- Mother's Info Box -->
            <div class="opf-card-section">
                <div class="opf-card-heading">
                    <span class="opf-card-badge">B</span>
                    <h4>Mother's Information</h4>
                </div>

                <div class="opf-grid-2">
                    <div class="opf-field-group">
                        <label class="opf-label required">Full Name of Mother</label>
                        <input type="text" name="mother_full_name" class="opf-input opf-req" placeholder="Enter mother's full name" required>
                        <span class="opf-err-msg">Mother's name is required.</span>
                    </div>

                    <div class="opf-field-group">
                        <label class="opf-label">Contact / Mobile Number</label>
                        <div class="opf-phone-wrapper">
                            <span class="opf-phone-prefix">+880</span>
                            <input type="tel" name="mother_mobile" class="opf-input opf-phone-input" placeholder="1XXXXXXXXX" maxlength="10">
                        </div>
                    </div>
                </div>

                <div class="opf-grid-3">
                    <div class="opf-field-group">
                        <label class="opf-label">National Identification Number (NID)</label>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" name="mother_nid" class="opf-input opf-digits-only" placeholder="NID Number" maxlength="17">
                    </div>

                    <div class="opf-field-group">
                        <label class="opf-label">Occupation</label>
                        <input type="text" name="mother_occupation" class="opf-input" placeholder="Occupation">
                    </div>

                    <div class="opf-field-group">
                        <label class="opf-label">Monthly Income</label>
                        <input type="number" name="mother_monthly_income" class="opf-input" placeholder="Monthly income amount" min="0">
                    </div>
                </div>

                <div class="opf-grid-2 opf-deceased-fields" id="opf-mother-deceased-details">
                    <div class="opf-field-group">
                        <label class="opf-label">Death Registration Number</label>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" name="mother_death_reg_no" class="opf-input opf-digits-only" placeholder="Death Registration No" maxlength="17">
                    </div>

                    <div class="opf-field-group">
                        <label class="opf-label">Cause of Death</label>
                        <input type="text" name="mother_cause_of_death" class="opf-input" placeholder="Cause of death">
                    </div>
                </div>
            </div>

            <!-- Current Guardian Box -->
            <div class="opf-card-section">
                <div class="opf-card-heading">
                    <span class="opf-card-badge">C</span>
                    <div>
                        <h4>Current Guardians</h4>
                        <small class="opf-guardian-note">*If other than father or mother</small>
                    </div>
                </div>

                <div id="opf-guardian-details">
                    <div class="opf-grid-2">
                        <div class="opf-field-group">
                            <label class="opf-label">Full Name of Current Guardian</label>
                            <input type="text" name="guardian_full_name" class="opf-input" placeholder="Enter guardian's full name">
                        </div>

                        <div class="opf-field-group">
                            <label class="opf-label">Contact / Mobile Number</label>
                            <div class="opf-phone-wrapper">
                                <span class="opf-phone-prefix">+880</span>
                                <input type="tel" name="guardian_mobile" class="opf-input opf-phone-input" placeholder="1XXXXXXXXX" maxlength="10">
                            </div>
                        </div>
                    </div>

                    <div class="opf-grid-3">
                        <div class="opf-field-group">
                            <label class="opf-label">National Identification Number (NID)</label>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" name="guardian_nid" class="opf-input opf-digits-only" placeholder="Guardian NID" maxlength="17">
                        </div>

                        <div class="opf-field-group">
                            <label class="opf-label">Date of Birth</label>
                            <input type="date" name="guardian_dob" class="opf-input">
                        </div>

                        <div class="opf-field-group">
                            <label class="opf-label">Relation with Applicant</label>
                            <input type="text" name="guardian_relation" class="opf-input" placeholder="Uncle / Grandfather / Aunt">
                        </div>
                    </div>
                </div>
            </div>

            <div class="opf-actions-bar">
                <button type="button" class="opf-btn opf-btn-prev" data-prev="1">
                    Previous
                </button>
                <button type="button" class="opf-btn opf-btn-next" data-next="3">
                    Next
                </button>
            </div>
        </div>

        <!-- ========================================================
             STEP 3: Address & References
             ======================================================== -->
        <div class="opf-step-pane" data-step="3">
            <div class="opf-pane-header">
                <div class="opf-pane-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </div>
                <div class="opf-pane-header-content">
                    <h3 class="opf-section-title">Address & SAP References</h3>
                    <p class="opf-section-desc">Present address, permanent address, and reference information</p>
                </div>
            </div>

            <!-- Present Address -->
            <div class="opf-card-section">
                <div class="opf-card-heading">
                    <span class="opf-card-badge">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    </span>
                    <h4>Present Address</h4>
                </div>

                <div class="opf-grid-3">
                    <div class="opf-field-group">
                        <label class="opf-label">House</label>
                        <input type="text" name="present_house" id="opf-present-house" class="opf-input" placeholder="House / Holding No">
                    </div>
                    <div class="opf-field-group">
                        <label class="opf-label required">Village</label>
                        <input type="text" name="present_village" id="opf-present-village" class="opf-input opf-req" placeholder="Village / Area" required>
                        <span class="opf-err-msg">Village is required.</span>
                    </div>
                    <div class="opf-field-group">
                        <label class="opf-label required">Post Office</label>
                        <input type="text" name="present_post_office" id="opf-present-post-office" class="opf-input opf-req" placeholder="Post Office" required>
                        <span class="opf-err-msg">Post office is required.</span>
                    </div>
                </div>

                <div class="opf-grid-3">
                    <div class="opf-field-group">
                        <label class="opf-label required">Police Station</label>
                        <input type="text" name="present_police_station" id="opf-present-police-station" class="opf-input opf-req" placeholder="Police Station" required>
                        <span class="opf-err-msg">Police station is required.</span>
                    </div>
                    <div class="opf-field-group">
                        <label class="opf-label required">District</label>
                        <input type="text" name="present_district" id="opf-present-district" class="opf-input opf-req" placeholder="District" required>
                        <span class="opf-err-msg">District is required.</span>
                    </div>
                    <div class="opf-field-group">
                        <label class="opf-label">Country</label>
                        <input type="text" name="present_country" id="opf-present-country" class="opf-input" value="Bangladesh">
                    </div>
                </div>
            </div>

            <!-- Permanent Address -->
            <div class="opf-card-section">
                <div class="opf-card-heading opf-flex-between">
                    <div class="opf-flex-align">
                        <span class="opf-card-badge">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5L12 3l9 6.5V20a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9.5z"></path><path d="M9 22V12h6v10"></path></svg>
                        </span>
                        <h4>Permanent Address</h4>
                    </div>
                    <label class="opf-same-addr-label">
                        <input type="checkbox" id="opf-same-as-present" class="opf-checkbox">
                        <span>Same as Present Address</span>
                    </label>
                </div>

                <div class="opf-grid-3">
                    <div class="opf-field-group">
                        <label class="opf-label">House</label>
                        <input type="text" name="permanent_house" id="opf-permanent-house" class="opf-input" placeholder="House / Holding No">
                    </div>
                    <div class="opf-field-group">
                        <label class="opf-label required">Village</label>
                        <input type="text" name="permanent_village" id="opf-permanent-village" class="opf-input opf-req" placeholder="Village / Area" required>
                        <span class="opf-err-msg">Permanent village is required.</span>
                    </div>
                    <div class="opf-field-group">
                        <label class="opf-label required">Post Office</label>
                        <input type="text" name="permanent_post_office" id="opf-permanent-post-office" class="opf-input opf-req" placeholder="Post Office" required>
                        <span class="opf-err-msg">Permanent post office is required.</span>
                    </div>
                </div>

                <div class="opf-grid-3">
                    <div class="opf-field-group">
                        <label class="opf-label required">Police Station</label>
                        <input type="text" name="permanent_police_station" id="opf-permanent-police-station" class="opf-input opf-req" placeholder="Police Station" required>
                        <span class="opf-err-msg">Permanent police station is required.</span>
                    </div>
                    <div class="opf-field-group">
                        <label class="opf-label required">District</label>
                        <input type="text" name="permanent_district" id="opf-permanent-district" class="opf-input opf-req" placeholder="District" required>
                        <span class="opf-err-msg">Permanent district is required.</span>
                    </div>
                    <div class="opf-field-group">
                        <label class="opf-label">Country</label>
                        <input type="text" name="permanent_country" id="opf-permanent-country" class="opf-input" value="Bangladesh">
                    </div>
                </div>
            </div>

            <!-- SAP References -->
            <div class="opf-card-section">
                <div class="opf-card-heading">
                    <span class="opf-card-badge">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13l2.5 2.5a2 2 0 0 0 2.83 0l4.67-4.67a2 2 0 0 0 0-2.83l-2-2a2 2 0 0 0-2.83 0l-5.17 5"></path><path d="M14 11l-2.5-2.5a2 2 0 0 0-2.83 0l-4.67 4.67a2 2 0 0 0 0 2.83l2 2a2 2 0 0 0 2.83 0l5.17-5"></path></svg>
                    </span>
                    <h4>SAP References</h4>
                </div>

                <div class="opf-grid-2">
                    <div class="opf-field-group">
                        <label class="opf-label required">Name of Reference (SAP)</label>
                        <input type="text" name="sap_reference_name" class="opf-input opf-req" placeholder="SAP volunteer or reference name" required>
                        <span class="opf-err-msg">Reference name is required.</span>
                    </div>

                    <div class="opf-field-group">
                        <label class="opf-label required">Contact / Mobile Number</label>
                        <div class="opf-phone-wrapper">
                            <span class="opf-phone-prefix">+880</span>
                            <input type="tel" name="sap_reference_mobile" class="opf-input opf-phone-input opf-req" placeholder="1XXXXXXXXX" maxlength="10" required>
                        </div>
                        <span class="opf-err-msg">Reference mobile number is required.</span>
                    </div>
                </div>
            </div>

            <div class="opf-actions-bar">
                <button type="button" class="opf-btn opf-btn-prev" data-prev="2">
                    Previous
                </button>
                <button type="button" class="opf-btn opf-btn-next" data-next="4">
                    Next
                </button>
            </div>
        </div>

        <!-- ========================================================
             STEP 4: Orphan Education Status
             ======================================================== -->
        <div class="opf-step-pane" data-step="4">
            <div class="opf-pane-header">
                <div class="opf-pane-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
                </div>
                <div class="opf-pane-header-content">
                    <h3 class="opf-section-title">Orphan Education Status</h3>
                    <p class="opf-section-desc">Education status assessment</p>
                </div>
            </div>

            <!-- Education Status Matrix -->
            <div class="opf-card-section">
                <div class="opf-card-heading">
                    <span class="opf-card-badge">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    </span>
                    <div>
                        <h4>Orphan education status</h4>
                        <p class="opf-note-text">
                            <strong>Note:</strong> The above section will be filled out manually by the SAP volunteer.
                        </p>
                    </div>
                </div>

                <div class="opf-table-responsive">
                    <table class="opf-eval-table">
                        <thead>
                            <tr>
                                <th class="opf-th-criteria">Criteria</th>
                                <th>NeedImprovement</th>
                                <th>Acceptable</th>
                                <th>Very Good</th>
                                <th>Excellent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $eval_metrics = [
                                'health_physical' => 'Health & Physical Status',
                                'reading_skills'  => 'Reading Skills',
                                'writing_skills'  => 'Writing Skills',
                                'speaking_skills' => 'Speaking Skills',
                                'listening_skills'=> 'Listening Skills',
                                'effort_education'=> 'His/Her Effort on Education',
                                'class_behaviour' => 'Class Behaviour',
                                'homework_done'   => 'Completion of Homework'
                            ];

                            foreach ($eval_metrics as $key => $title) :
                            ?>
                            <tr>
                                <td class="opf-td-title"><strong><?php echo esc_html($title); ?></strong></td>
                                <td class="opf-td-center">
                                    <label class="opf-cell-radio">
                                        <input type="radio" name="eval[<?php echo esc_attr($key); ?>]" value="NeedImprovement">
                                        <span></span>
                                    </label>
                                </td>
                                <td class="opf-td-center">
                                    <label class="opf-cell-radio">
                                        <input type="radio" name="eval[<?php echo esc_attr($key); ?>]" value="Acceptable" checked>
                                        <span></span>
                                    </label>
                                </td>
                                <td class="opf-td-center">
                                    <label class="opf-cell-radio">
                                        <input type="radio" name="eval[<?php echo esc_attr($key); ?>]" value="Very Good">
                                        <span></span>
                                    </label>
                                </td>
                                <td class="opf-td-center">
                                    <label class="opf-cell-radio">
                                        <input type="radio" name="eval[<?php echo esc_attr($key); ?>]" value="Excellent">
                                        <span></span>
                                    </label>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="opf-actions-bar">
                <button type="button" class="opf-btn opf-btn-prev" data-prev="3">
                    Previous
                </button>
                <button type="button" class="opf-btn opf-btn-next" data-next="5">
                    Next
                </button>
            </div>
        </div>

        <!-- ========================================================
             STEP 5: Applicant Signature & Student Photos (With Cropper)
             ======================================================== -->
        <div class="opf-step-pane" data-step="5">
            <div class="opf-pane-header">
                <div class="opf-pane-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                </div>
                <div class="opf-pane-header-content">
                    <h3 class="opf-section-title">Photos &amp; Signature Upload</h3>
                    <p class="opf-section-desc">Upload and crop your signature and two student photographs</p>
                </div>
            </div>

            <?php
            $step5_cards = [
                [
                    'key'        => 'signature_image',
                    'title'      => 'Applicant Signature',
                    'hint'       => 'Upload signature photo and crop clearly (auto-placed on PDF)',
                    'aspect'     => '3',
                    'empty_text' => 'Choose Signature Image & Crop',
                    'err_text'   => 'Applicant signature image is required.',
                    'is_wide'    => true,
                ],
                [
                    'key'        => 'short_photo',
                    'title'      => 'Short / Passport Photo',
                    'hint'       => 'Upload passport / short student photo & crop',
                    'aspect'     => '0.82',
                    'empty_text' => 'Choose Short Photo & Crop',
                    'err_text'   => 'Short / Passport photo is required.',
                    'is_wide'    => false,
                ],
                [
                    'key'        => 'half_photo',
                    'title'      => 'Half Photo',
                    'hint'       => 'Upload half-size student photo & crop',
                    'aspect'     => '0.75',
                    'empty_text' => 'Choose Half Photo & Crop',
                    'err_text'   => 'Half photo is required.',
                    'is_wide'    => false,
                ],
            ];
            ?>

            <div class="opf-upload-cards-grid">
                <?php foreach ($step5_cards as $card) :
                    $k = $card['key'];
                ?>
                <div class="opf-img-upload-card <?php echo $card['is_wide'] ? 'opf-img-card-wide' : ''; ?>"
                     data-field="<?php echo esc_attr($k); ?>"
                     data-aspect="<?php echo esc_attr($card['aspect']); ?>"
                     data-title="<?php echo esc_attr($card['title']); ?>">
                    <div class="opf-img-card-header">
                        <label class="opf-label"><?php echo esc_html($card['title']); ?> <span class="opf-req-star">*</span></label>
                        <p class="opf-img-card-hint"><?php echo esc_html($card['hint']); ?></p>
                    </div>

                    <input type="hidden" id="opf-state-<?php echo esc_attr($k); ?>" class="opf-req opf-image-req" data-field-key="<?php echo esc_attr($k); ?>" value="">
                    <input type="file" id="opf-file-<?php echo esc_attr($k); ?>" class="opf-img-file-input" accept="image/*" style="display:none;">

                    <!-- Empty Drop/Select Zone -->
                    <button type="button" class="opf-img-picker-btn" data-trigger-file="<?php echo esc_attr($k); ?>">
                        <span class="opf-img-picker-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        </span>
                        <span class="opf-img-picker-txt"><?php echo esc_html($card['empty_text']); ?></span>
                        <span class="opf-img-picker-sub">Supports JPG, PNG (with Crop Tool)</span>
                    </button>

                    <!-- Cropped Preview Zone -->
                    <div class="opf-img-preview-wrap" style="display:none;">
                        <div class="opf-img-preview-box <?php echo $card['is_wide'] ? 'is-sig' : ''; ?>">
                            <img id="opf-preview-<?php echo esc_attr($k); ?>" src="" alt="<?php echo esc_attr($card['title']); ?>">
                        </div>
                        <div class="opf-img-preview-actions">
                            <button type="button" class="opf-btn-mini opf-btn-recrop" data-recrop="<?php echo esc_attr($k); ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.13 1L6 16a2 2 0 0 0 2 2h15"></path><path d="M1 6.13L16 6a2 2 0 0 1 2 2v15"></path></svg>
                                <span>Crop</span>
                            </button>
                            <button type="button" class="opf-btn-mini opf-btn-reupload" data-trigger-file="<?php echo esc_attr($k); ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                <span>Change</span>
                            </button>
                            <button type="button" class="opf-btn-mini opf-btn-remove-img" data-remove-img="<?php echo esc_attr($k); ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        </div>
                    </div>

                    <span class="opf-err-msg"><?php echo esc_html($card['err_text']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="opf-actions-bar">
                <button type="button" class="opf-btn opf-btn-prev" data-prev="4">
                    Previous
                </button>
                <button type="submit" class="opf-btn opf-btn-submit" id="opf-submit-btn">
                    <span class="opf-btn-text-content">Submit Application</span>
                    <span class="opf-spinner" style="display: none;"></span>
                </button>
            </div>
        </div>
    </form>
    </div><!-- /#opf-online-mode -->

    <!-- Success Modal Box -->
    <div class="opf-success-overlay" style="display: none;">
        <div class="opf-success-card">
            <div class="opf-success-icon-wrap">
                <div class="opf-success-checkmark">✓</div>
            </div>
            <h3 class="opf-success-heading">Application Submitted Successfully!</h3>
            <p class="opf-success-message">
                Your application has been received successfully. The information has been recorded in the system.
            </p>
            <div class="opf-app-id-pill">
                <span>Application ID:</span>
                <strong id="opf-success-app-id">--</strong>
            </div>
            <div class="opf-success-actions">
                <button type="button" class="opf-btn opf-btn-print" id="opf-print-btn">
                    🖨️ Print / Save Application
                </button>
                <button type="button" class="opf-btn opf-btn-secondary" id="opf-new-app-btn">
                    Submit Another Application
                </button>
            </div>
        </div>
    </div>
</div>
</div><!-- /.opf-wizard-shell -->

<!-- Centered Custom Alert / Error Modal Dialog (outside container for perfect viewport centering) -->
<div class="opf-modal-backdrop" id="opf-alert-modal" style="display: none;" role="dialog" aria-modal="true">
    <div class="opf-modal-dialog">
        <button type="button" class="opf-modal-close-btn" id="opf-modal-close" aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        <div class="opf-modal-body">
            <div class="opf-modal-icon-wrap" id="opf-modal-icon-wrap">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <h3 class="opf-modal-title" id="opf-modal-title">Invalid ID</h3>
            <p class="opf-modal-message" id="opf-modal-msg">Please enter a valid Application ID.</p>
            <div class="opf-modal-actions">
                <button type="button" class="opf-modal-btn-ok" id="opf-modal-btn-ok">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Image Cropper Modal -->
<div class="opf-modal-backdrop" id="opf-crop-modal" style="display: none;" role="dialog" aria-modal="true">
    <div class="opf-crop-dialog">
        <div class="opf-crop-header">
            <h4 class="opf-crop-title" id="opf-crop-modal-title">Crop Image</h4>
            <button type="button" class="opf-crop-close-btn" id="opf-crop-close" aria-label="Close cropper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        <div class="opf-crop-toolbar">
            <div class="opf-crop-aspect-btns">
                <button type="button" class="opf-crop-pill active" id="opf-crop-ratio-lock">Recommended Ratio</button>
                <button type="button" class="opf-crop-pill" id="opf-crop-ratio-free">Free Crop</button>
            </div>
            <div class="opf-crop-tools-right">
                <button type="button" class="opf-crop-tool-btn" id="opf-crop-rotate-btn" title="Rotate 90 degrees">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                    <span>Rotate</span>
                </button>
                <button type="button" class="opf-crop-tool-btn" id="opf-crop-reset-btn" title="Reset crop area">
                    <span>Reset</span>
                </button>
            </div>
        </div>

        <div class="opf-crop-stage-wrap">
            <div class="opf-crop-stage" id="opf-crop-stage">
                <canvas id="opf-crop-canvas"></canvas>
                <div class="opf-crop-box" id="opf-crop-box">
                    <span class="opf-crop-grid-h"></span>
                    <span class="opf-crop-grid-v"></span>
                    <span class="opf-crop-handle opf-h-nw" data-handle="nw"></span>
                    <span class="opf-crop-handle opf-h-ne" data-handle="ne"></span>
                    <span class="opf-crop-handle opf-h-sw" data-handle="sw"></span>
                    <span class="opf-crop-handle opf-h-se" data-handle="se"></span>
                </div>
            </div>
        </div>

        <div class="opf-crop-footer">
            <button type="button" class="opf-crop-btn-cancel" id="opf-crop-cancel-btn">Cancel</button>
            <button type="button" class="opf-crop-btn-save" id="opf-crop-save-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <span>Crop &amp; Save</span>
            </button>
        </div>
    </div>
</div>
