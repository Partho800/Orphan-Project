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

    <!-- Mode Switcher Navigation Bar -->
    <div class="opf-action-modes-bar">
        <button type="button" class="opf-mode-btn active" id="opf-mode-btn-online" data-target="opf-online-mode">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            <span>Apply Online</span>
        </button>

        <a href="<?php echo esc_url(add_query_arg('opf_download_blank', '1', home_url('/'))); ?>" class="opf-mode-btn opf-mode-btn-download" id="opf-download-blank-btn" download="Orphan_Project_Blank_Application_Form.pdf" title="Download Printable Blank Form PDF">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            <span>Download Form</span>
        </a>

        <button type="button" class="opf-mode-btn" id="opf-mode-btn-upload" data-target="opf-upload-mode">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            <span>Upload Form</span>
        </button>
    </div>

    <!-- Alert Message Container -->
    <div class="opf-alert-box" style="display: none;"></div>

    <div id="opf-online-mode" class="opf-mode-content">
    <form class="opf-form" method="post" novalidate>
        <?php wp_nonce_field('orphan_form_nonce_action', 'orphan_form_nonce'); ?>
        <input type="hidden" name="action" value="submit_orphan_registration_form">

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
                    <label class="opf-label required">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="opf-input opf-req opf-dob-field" required>
                    <span class="opf-err-msg">Date of birth is required.</span>
                </div>

                <div class="opf-field-group">
                    <label class="opf-label">Age</label>
                    <input type="number" name="age" class="opf-input opf-age-field" min="1" max="30" placeholder="Years" readonly>
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
                        <input type="text" name="siblings_position" class="opf-input" placeholder="e.g. 1st, 2nd, youngest">
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
                    <label class="opf-label required">Birth Registration Number</label>
                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="birth_registration_number" class="opf-input opf-req opf-digits-only" placeholder="17-Digit Birth Registration No" maxlength="17" required>
                    <span class="opf-err-msg">17-digit birth registration number is required.</span>
                </div>
            </div>

            <div class="opf-grid-2">
                <div class="opf-field-group">
                    <label class="opf-label">National Identification Number (NID)</label>
                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="student_nid" class="opf-input opf-digits-only" placeholder="10 or 17 Digit NID Number" maxlength="17">
                </div>

                <div class="opf-field-group">
                    <label class="opf-label required">Name of Colleges / School</label>
                    <input type="text" name="college_school_name" class="opf-input opf-req" placeholder="e.g. Dhaka College" required>
                    <span class="opf-err-msg">College or school name is required.</span>
                </div>
            </div>

            <div class="opf-grid-2">
                <div class="opf-field-group">
                    <label class="opf-label">Studying - ID No</label>
                    <input type="text" name="studying_id_no" class="opf-input" placeholder="e.g. ID / Roll No">
                </div>

                <div class="opf-field-group">
                    <label class="opf-label">Studying - Group / Subject</label>
                    <input type="text" name="studying_group_subject" class="opf-input" placeholder="e.g. Science / Arts / Commerce">
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

                <div class="opf-toggle-box">
                    <label class="opf-toggle-label">
                        <input type="checkbox" name="father_is_deceased" value="yes" class="opf-toggle-check" id="opf-father-deceased-chk">
                        <span>Father is Deceased</span>
                    </label>
                </div>

                <div class="opf-grid-2 opf-deceased-fields" id="opf-father-deceased-details" style="display: none;">
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

                <div class="opf-toggle-box">
                    <label class="opf-toggle-label">
                        <input type="checkbox" name="mother_is_deceased" value="yes" class="opf-toggle-check" id="opf-mother-deceased-chk">
                        <span>Mother is Deceased</span>
                    </label>
                </div>

                <div class="opf-grid-2 opf-deceased-fields" id="opf-mother-deceased-details" style="display: none;">
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
                        <h4>Full Name of Current Guardians</h4>
                        <small class="opf-guardian-note">*Applicable if it is someone other than the father or mother</small>
                    </div>
                </div>

                <div class="opf-toggle-box">
                    <label class="opf-toggle-label">
                        <input type="checkbox" name="has_separate_guardian" value="yes" class="opf-toggle-check" id="opf-separate-guardian-chk">
                        <span>Current guardian is someone other than the father or mother</span>
                    </label>
                </div>

                <div id="opf-guardian-details" style="display: none;">
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
                            <input type="text" name="guardian_relation" class="opf-input" placeholder="e.g. Uncle / Grandfather / Aunt">
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
                <button type="submit" class="opf-btn opf-btn-submit" id="opf-submit-btn">
                    <span class="opf-btn-text-content">Submit Application</span>
                    <span class="opf-spinner" style="display: none;"></span>
                </button>
            </div>
        </div>
    </form>
    </div><!-- /#opf-online-mode -->

    <!-- ========================================================
         UPLOAD FORM MODE CONTAINER
         ======================================================== -->
    <div id="opf-upload-mode" class="opf-mode-content" style="display: none;">
        <form id="opf-upload-form" class="opf-upload-form" method="post" enctype="multipart/form-data" novalidate>
            <?php wp_nonce_field('orphan_form_nonce_action', 'orphan_form_nonce'); ?>
            <input type="hidden" name="action" value="submit_orphan_upload_form">

            <div class="opf-pane-header">
                <div class="opf-pane-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                </div>
                <div class="opf-pane-header-content">
                    <h3 class="opf-section-title">Upload Completed Application Form</h3>
                    <p class="opf-section-desc">Submit your filled and signed application form by uploading a scanned PDF or clear photo.</p>
                </div>
            </div>

            <!-- Applicant Type Selector -->
            <div class="opf-type-selector-wrap">
                <label class="opf-type-option active" id="opf-type-opt-new">
                    <input type="radio" name="applicant_type" value="new" checked>
                    <span class="opf-type-radio-bullet"></span>
                    <div class="opf-type-text">
                        <strong class="opf-type-title">New Applicant</strong>
                        <span class="opf-type-desc">Submitting a new completed form for the first time</span>
                    </div>
                </label>

                <label class="opf-type-option" id="opf-type-opt-existing">
                    <input type="radio" name="applicant_type" value="existing">
                    <span class="opf-type-radio-bullet"></span>
                    <div class="opf-type-text">
                        <strong class="opf-type-title">Existing / Previous Applicant</strong>
                        <span class="opf-type-desc">Already applied online or have an existing ID / record</span>
                    </div>
                </label>
            </div>

            <!-- Existing Applicant Verification Fields -->
            <div id="opf-upload-fields-existing" class="opf-upload-fields-section" style="display: none;">
                <div class="opf-grid-2">
                    <div class="opf-field-group">
                        <label class="opf-label required">Application ID or Birth Reg No</label>
                        <input type="text" name="existing_app_lookup" class="opf-input" placeholder="e.g. 1 or SAP-20261124-xxxx or 17-digit Birth Reg">
                        <span class="opf-err-msg">Application ID or Birth Registration Number is required.</span>
                    </div>
                    <div class="opf-field-group">
                        <label class="opf-label required">Full Name</label>
                        <input type="text" name="existing_student_name" class="opf-input" placeholder="Enter full name">
                        <span class="opf-err-msg">Full name is required.</span>
                    </div>
                </div>

                <div class="opf-grid-2" style="margin-top: 14px;">
                    <div class="opf-field-group">
                        <label class="opf-label required">Contact / Mobile Number</label>
                        <div class="opf-phone-wrapper">
                            <span class="opf-phone-prefix">+880</span>
                            <input type="tel" name="existing_mobile" class="opf-input opf-phone-input opf-digits-only" placeholder="1XXXXXXXXX" maxlength="11" pattern="[0-9]*" inputmode="numeric">
                        </div>
                        <span class="opf-err-msg">Valid contact number is required.</span>
                    </div>
                </div>
            </div>

            <!-- New Applicant Registration Fields -->
            <div id="opf-upload-fields-new" class="opf-upload-fields-section">
                <div class="opf-grid-2">
                    <div class="opf-field-group">
                        <label class="opf-label required">First Name</label>
                        <input type="text" name="student_first_name" class="opf-input" placeholder="First name">
                        <span class="opf-err-msg">First name is required.</span>
                    </div>
                    <div class="opf-field-group">
                        <label class="opf-label required">Surname</label>
                        <input type="text" name="student_surname" class="opf-input" placeholder="Surname">
                        <span class="opf-err-msg">Surname is required.</span>
                    </div>
                </div>

                <div class="opf-grid-3" style="margin-top: 14px;">
                    <div class="opf-field-group">
                        <label class="opf-label required">Gender</label>
                        <div class="opf-radio-group">
                            <label class="opf-radio-label"><input type="radio" name="gender" value="Male" checked> Male</label>
                            <label class="opf-radio-label"><input type="radio" name="gender" value="Female"> Female</label>
                            <label class="opf-radio-label"><input type="radio" name="gender" value="Other"> Other</label>
                        </div>
                    </div>
                    <div class="opf-field-group">
                        <label class="opf-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="opf-input">
                    </div>
                    <div class="opf-field-group">
                        <label class="opf-label required">District</label>
                        <input type="text" name="district" class="opf-input" placeholder="e.g. Dhaka">
                        <span class="opf-err-msg">District is required.</span>
                    </div>
                </div>

                <div class="opf-grid-2" style="margin-top: 14px;">
                    <div class="opf-field-group">
                        <label class="opf-label required">Contact / Mobile Number</label>
                        <div class="opf-phone-wrapper">
                            <span class="opf-phone-prefix">+880</span>
                            <input type="tel" name="student_mobile" class="opf-input opf-phone-input opf-digits-only" placeholder="1XXXXXXXXX" maxlength="11" pattern="[0-9]*" inputmode="numeric">
                        </div>
                        <span class="opf-err-msg">Valid mobile number is required.</span>
                    </div>
                    <div class="opf-field-group">
                        <label class="opf-label required">Birth Registration Number</label>
                        <input type="text" name="birth_registration_number" class="opf-input opf-digits-only" placeholder="17-Digit Birth Registration No" maxlength="17" pattern="[0-9]*" inputmode="numeric">
                        <span class="opf-err-msg">Valid 17-digit birth registration number is required.</span>
                    </div>
                </div>
            </div>

            <!-- File Upload Dropzone -->
            <div class="opf-upload-box-wrap" style="margin-top: 22px;">
                <label class="opf-label required">Completed Form Document (PDF or Images)</label>
                <div class="opf-dropzone-area" id="opf-dropzone-area">
                    <input type="file" name="uploaded_form_file" id="opf-upload-file-input" accept=".pdf,image/jpeg,image/png" class="opf-file-hidden-input" required>
                    
                    <div class="opf-dropzone-placeholder" id="opf-dropzone-placeholder">
                        <div class="opf-dropzone-icon-circle">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#0E84C7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        </div>
                        <p class="opf-dropzone-main-text">Click to choose a file or drag & drop here</p>
                        <p class="opf-dropzone-sub-text">Supported formats: PDF, JPG, PNG (Max size: 20MB)</p>
                    </div>

                    <!-- Selected File Preview -->
                    <div class="opf-file-selected-card" id="opf-file-selected-card" style="display: none;">
                        <div class="opf-file-info-group">
                            <div class="opf-file-icon-box">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E84C7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            </div>
                            <div class="opf-file-details">
                                <strong class="opf-selected-file-name" id="opf-selected-file-name">form-document.pdf</strong>
                                <span class="opf-selected-file-size" id="opf-selected-file-size">1.4 MB</span>
                            </div>
                        </div>
                        <button type="button" class="opf-btn-clear-file" id="opf-btn-clear-file" title="Remove selected file">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>
                </div>
                <span class="opf-err-msg" id="opf-upload-file-err">Please choose a completed form document to upload.</span>
            </div>

            <!-- Upload Submit Button -->
            <div class="opf-actions-bar" style="margin-top: 26px;">
                <button type="submit" class="opf-btn opf-btn-submit" id="opf-upload-btn-submit">
                    <span class="opf-btn-text-content">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        <span>Submit Uploaded Form</span>
                    </span>
                    <span class="opf-spinner" style="display: none;"></span>
                </button>
            </div>
        </form>

        <!-- Upload Success View -->
        <div class="opf-upload-success-view" id="opf-upload-success-view" style="display: none;">
            <div class="opf-success-icon-wrap">
                <div class="opf-success-checkmark">✓</div>
            </div>
            <h3 class="opf-success-heading" id="opf-upload-success-title">Form Uploaded Successfully!</h3>
            <p class="opf-success-message" id="opf-upload-success-desc">
                Your completed application form has been received and recorded into the system.
            </p>
            <div class="opf-app-id-pill">
                <span>Application ID:</span>
                <strong id="opf-upload-success-id">#1</strong>
            </div>
            <div class="opf-success-actions" style="margin-top: 22px;">
                <a href="#" id="opf-upload-success-link" target="_blank" class="opf-btn opf-btn-primary" style="display:inline-flex; align-items:center; gap:7px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    <span>View Uploaded File</span>
                </a>
                <button type="button" class="opf-btn opf-btn-secondary" id="opf-upload-reset-btn">
                    Upload Another Form
                </button>
            </div>
        </div>
    </div>

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
                <strong id="opf-success-app-id">SAP-000000</strong>
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
            <h3 class="opf-modal-title" id="opf-modal-title">Application Not Found</h3>
            <p class="opf-modal-message" id="opf-modal-msg">No existing application found matching the provided details. Please check your details or select "New Applicant".</p>
            <div class="opf-modal-actions">
                <button type="button" class="opf-modal-btn-ok" id="opf-modal-btn-ok">OK</button>
            </div>
        </div>
    </div>
</div>
