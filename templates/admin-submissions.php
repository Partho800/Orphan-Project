<?php
/**
 * Admin Submissions Page Template
 * Redesigned with 100% Pure SVG Icons, Bulk Actions, Status Updates & In-Page PDF Modal
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'orphan_applications';

// Query parameters
$search_query   = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter  = isset($_GET['status_filter']) ? sanitize_text_field(strtolower($_GET['status_filter'])) : '';

$where = 'WHERE 1=1';
if (!empty($search_query)) {
    $search_like = '%' . $wpdb->esc_like($search_query) . '%';
    $where .= $wpdb->prepare(" AND (id = %d OR application_no LIKE %s OR student_name LIKE %s OR contact_number LIKE %s OR district LIKE %s)", intval($search_query), $search_like, $search_like, $search_like, $search_like);
}
if (!empty($status_filter) && in_array($status_filter, ['pending', 'approved', 'rejected'], true)) {
    $where .= $wpdb->prepare(" AND status = %s", $status_filter);
}

// Fetch submissions
$submissions = $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY id ASC LIMIT 500");

// Counts for filter pills
$total_count    = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$pending_count  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
$approved_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved'");
$rejected_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'rejected'");
?>

<div class="wrap opf-admin-wrap">

    <!-- Top Header -->
    <div class="opf-admin-header">
        <div class="opf-admin-header-title">
            <h1 class="opf-page-heading">Orphan Applications</h1>
            <span class="opf-count-pill" id="opf-total-counter"><?php echo number_format($total_count); ?> Total</span>
        </div>
        <div class="opf-header-actions">
            <a href="<?php echo esc_url(admin_url('admin-post.php?action=opf_export_csv')); ?>" class="opf-btn opf-btn-primary opf-btn-export">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>Export All to CSV</span>
            </a>
        </div>
    </div>

    <!-- Shortcode Helper Card -->
    <div class="opf-shortcode-card">
        <div class="opf-shortcode-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        </div>
        <div class="opf-shortcode-body">
            <span class="opf-shortcode-label">Frontend Form Shortcode:</span>
            <code id="opf-shortcode-code">[orphan_registration_form]</code>
            <button type="button" class="opf-btn-copy" id="opf-copy-shortcode-btn" title="Copy Shortcode to Clipboard">
                <svg class="opf-icon-copy" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                <span class="opf-copy-txt">Copy</span>
            </button>
            <span class="opf-shortcode-hint">Paste this shortcode into any page or post to display the multi-step registration form.</span>
        </div>
    </div>

    <!-- Filter & Search Controls Bar -->
    <div class="opf-controls-bar">
        <!-- Status Filter Tabs -->
        <div class="opf-status-tabs">
            <a href="<?php echo esc_url(remove_query_arg('status_filter')); ?>" class="opf-tab-link <?php echo empty($status_filter) ? 'active' : ''; ?>">
                All <span class="opf-tab-badge"><?php echo $total_count; ?></span>
            </a>
            <a href="<?php echo esc_url(add_query_arg('status_filter', 'pending')); ?>" class="opf-tab-link opf-tab-pending <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                Pending <span class="opf-tab-badge"><?php echo $pending_count; ?></span>
            </a>
            <a href="<?php echo esc_url(add_query_arg('status_filter', 'approved')); ?>" class="opf-tab-link opf-tab-approved <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                Approved <span class="opf-tab-badge"><?php echo $approved_count; ?></span>
            </a>
            <a href="<?php echo esc_url(add_query_arg('status_filter', 'rejected')); ?>" class="opf-tab-link opf-tab-rejected <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                Rejected <span class="opf-tab-badge"><?php echo $rejected_count; ?></span>
            </a>
        </div>

        <!-- Search Bar -->
        <form method="get" class="opf-search-form">
            <input type="hidden" name="page" value="orphan-applications">
            <?php if (!empty($status_filter)) : ?>
                <input type="hidden" name="status_filter" value="<?php echo esc_attr($status_filter); ?>">
            <?php endif; ?>
            <div class="opf-search-input-wrap">
                <svg class="opf-search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="search" name="s" id="opf-search-box" value="<?php echo esc_attr($search_query); ?>" placeholder="Search by ID, Name, Phone, District...">
                <?php if (!empty($search_query)) : ?>
                    <a href="<?php echo esc_url(remove_query_arg('s')); ?>" class="opf-search-clear" title="Clear Search">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </a>
                <?php endif; ?>
                <button type="submit" class="opf-btn opf-btn-search">Search</button>
            </div>
        </form>
    </div>

    <!-- Floating Bulk Actions Toolbar (Appears when 1+ rows are selected) -->
    <div id="opf-bulk-bar" class="opf-bulk-bar" style="display: none;">
        <div class="opf-bulk-left">
            <span class="opf-bulk-indicator">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <strong id="opf-selected-count">0</strong> application(s) selected
            </span>
        </div>
        <div class="opf-bulk-actions">
            <!-- Bulk Download PDFs Button -->
            <button type="button" class="opf-btn opf-btn-bulk-download" id="opf-btn-bulk-download">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>Download Selected PDFs</span>
            </button>

            <!-- Bulk Delete Button -->
            <button type="button" class="opf-btn opf-btn-bulk-delete" id="opf-btn-bulk-delete">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                <span>Delete Selected</span>
            </button>

            <!-- Deselect Button -->
            <button type="button" class="opf-btn opf-btn-subtle" id="opf-btn-deselect-all">
                Cancel
            </button>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="opf-table-card">
        <table class="opf-data-table" id="opf-applications-table">
            <thead>
                <tr>
                    <th class="opf-col-check">
                        <label class="opf-checkbox-wrap">
                            <input type="checkbox" id="opf-select-all" aria-label="Select all applications">
                            <span class="opf-custom-check"></span>
                        </label>
                    </th>
                    <th class="opf-col-id">Application ID</th>
                    <th class="opf-col-student">Student Name</th>
                    <th class="opf-col-dob">Date of Birth</th>
                    <th class="opf-col-contact">Contact</th>
                    <th class="opf-col-district">District</th>
                    <th class="opf-col-status">Status</th>
                    <th class="opf-col-date">Submitted Date</th>
                    <th class="opf-col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)) : ?>
                    <tr class="opf-row-empty">
                        <td colspan="9">
                            <div class="opf-empty-state">
                                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                                <p class="opf-empty-title">No applications found</p>
                                <p class="opf-empty-desc">New submissions submitted from the frontend form will appear here automatically.</p>
                            </div>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php 
                    $row_index = count($submissions);
                    foreach ($submissions as $sub) : 
                        $data = json_decode($sub->form_data, true);
                        if (!is_array($data)) $data = [];
                        $status = strtolower($sub->status ?: 'pending');
                        $age = isset($data['age']) && $data['age'] ? intval($data['age']) : '';
                        $view_url = add_query_arg([
                            'opf_view_app' => $sub->application_no,
                            'opf_admin'    => '1'
                        ], home_url('/'));
                    ?>
                    <tr id="opf-row-<?php echo esc_attr($sub->id); ?>" class="opf-app-row" data-id="<?php echo esc_attr($sub->id); ?>" data-app-no="<?php echo esc_attr($sub->application_no); ?>" data-student="<?php echo esc_attr($sub->student_name); ?>">
                        
                        <!-- Checkbox -->
                        <td class="opf-col-check">
                            <label class="opf-checkbox-wrap">
                                <input type="checkbox" class="opf-row-check" value="<?php echo esc_attr($sub->id); ?>" data-app-no="<?php echo esc_attr($sub->application_no); ?>" data-student="<?php echo esc_attr($sub->student_name); ?>" aria-label="Select application #<?php echo esc_attr($sub->id); ?>">
                                <span class="opf-custom-check"></span>
                            </label>
                        </td>

                        <!-- Application ID (1, 2, 3...) -->
                        <td class="opf-col-id">
                            <strong class="opf-numeric-id"><?php echo esc_html($sub->id); ?></strong>
                        </td>

                        <!-- Student Name -->
                        <td class="opf-col-student">
                            <strong class="opf-student-name"><?php echo esc_html($sub->student_name); ?></strong>
                            <div class="opf-student-meta">
                                <?php if (!empty($sub->gender)) : ?>
                                    <span class="opf-gender-badge opf-gender-<?php echo esc_attr(strtolower($sub->gender)); ?>">
                                        <?php echo esc_html(ucfirst($sub->gender)); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($sub->college_name)) : ?>
                                    <span class="opf-student-college" title="<?php echo esc_attr($sub->college_name); ?>">
                                        <?php echo esc_html(mb_strimwidth($sub->college_name, 0, 24, '...')); ?>
                                    </span>
                                <?php endif; ?>
                                <?php 
                                $uploaded_doc = !empty($sub->uploaded_file) ? $sub->uploaded_file : (!empty($data['uploaded_document_url']) ? $data['uploaded_document_url'] : '');
                                if (!empty($uploaded_doc)) : 
                                ?>
                                    <a href="<?php echo esc_url($uploaded_doc); ?>" target="_blank" class="opf-uploaded-doc-badge" title="View Uploaded Document File">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                                        <span>Uploaded Form</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Date of Birth & Age -->
                        <td class="opf-col-dob">
                            <?php if (!empty($sub->date_of_birth)) : ?>
                                <span class="opf-dob-val"><?php echo esc_html(date('d M, Y', strtotime($sub->date_of_birth))); ?></span>
                                <?php if ($age) : ?>
                                    <span class="opf-age-pill"><?php echo esc_html($age); ?> yrs</span>
                                <?php endif; ?>
                            <?php else : ?>
                                <span class="opf-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Contact -->
                        <td class="opf-col-contact">
                            <?php if (!empty($sub->contact_number)) : ?>
                                <a href="tel:+880<?php echo esc_attr($sub->contact_number); ?>" class="opf-contact-link" title="Call student">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                    <span>+880 <?php echo esc_html($sub->contact_number); ?></span>
                                </a>
                            <?php else : ?>
                                <span class="opf-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- District -->
                        <td class="opf-col-district">
                            <?php if (!empty($sub->district)) : ?>
                                <span class="opf-district-name"><?php echo esc_html($sub->district); ?></span>
                            <?php else : ?>
                                <span class="opf-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Interactive Status Selector -->
                        <td class="opf-col-status">
                            <div class="opf-status-select-wrap opf-status-wrap-<?php echo esc_attr($status); ?>" id="opf-status-wrap-<?php echo esc_attr($sub->id); ?>">
                                <span class="opf-status-dot"></span>
                                <select class="opf-status-select" data-app-id="<?php echo esc_attr($sub->id); ?>" aria-label="Update status">
                                    <option value="pending" <?php selected($status, 'pending'); ?>>Pending</option>
                                    <option value="approved" <?php selected($status, 'approved'); ?>>Approved</option>
                                    <option value="rejected" <?php selected($status, 'rejected'); ?>>Rejected</option>
                                </select>
                                <svg class="opf-status-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                <span class="opf-status-saving-spinner" style="display:none;"></span>
                            </div>
                        </td>

                        <!-- Submitted Date -->
                        <td class="opf-col-date">
                            <span class="opf-date-val"><?php echo esc_html(date('d M Y', strtotime($sub->created_at))); ?></span>
                            <span class="opf-time-val"><?php echo esc_html(date('h:i A', strtotime($sub->created_at))); ?></span>
                        </td>

                        <!-- Actions (Only View PDF - No individual delete here!) -->
                        <td class="opf-col-actions">
                            <div class="opf-actions-cell">
                                <button type="button" class="opf-btn opf-btn-view-pdf" data-app-id="<?php echo esc_attr($sub->id); ?>" data-app-no="<?php echo esc_attr($sub->application_no); ?>" data-student="<?php echo esc_attr($sub->student_name); ?>" data-view-url="<?php echo esc_url($view_url); ?>" title="View Application PDF">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                    <span>View PDF</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Hidden Iframe Container for Bulk Auto-Downloads -->
    <div id="opf-hidden-downloader-container" style="display:none; width:0; height:0; position:absolute; left:-9999px;"></div>

</div>

<!-- In-Page PDF Viewer Modal -->
<div id="opf-pdf-modal" class="opf-modal-overlay" style="display: none;">
    <div class="opf-modal-container">
        
        <!-- Modal Top Bar -->
        <div class="opf-modal-header">
            <div class="opf-modal-header-info">
                <div class="opf-modal-title-row">
                    <span class="opf-modal-badge" id="opf-modal-badge">Application #</span>
                    <h3 class="opf-modal-heading" id="opf-modal-student-name">View PDF</h3>
                </div>
                <span class="opf-modal-app-code" id="opf-modal-app-code"></span>
            </div>

            <div class="opf-modal-header-actions">
                <!-- Download button inside modal -->
                <button type="button" class="opf-btn opf-btn-modal-download" id="opf-modal-download-btn" title="Download Application PDF">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    <span>Download PDF</span>
                </button>

                <!-- Close Button -->
                <button type="button" class="opf-btn-modal-close" id="opf-modal-close-btn" title="Close Modal (Esc)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        </div>

        <!-- Modal Iframe Body -->
        <div class="opf-modal-body">
            <div class="opf-modal-loading" id="opf-modal-loading">
                <div class="opf-modal-spinner"></div>
                <span>Loading Application PDF...</span>
            </div>
            <iframe id="opf-modal-iframe" src="about:blank" frameborder="0"></iframe>
        </div>

    </div>
</div>

<!-- Bulk Download Progress Modal -->
<div id="opf-bulk-progress-modal" class="opf-modal-overlay opf-progress-overlay" style="display: none;">
    <div class="opf-progress-card">
        <div class="opf-progress-header">
            <svg class="opf-spin-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E84C7" stroke-width="2.5"><circle cx="12" cy="12" r="10" stroke-opacity="0.2"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path></svg>
            <h4 class="opf-progress-title">Downloading Selected Applications...</h4>
        </div>
        <p class="opf-progress-desc" id="opf-progress-desc">Preparing PDF downloads, please wait...</p>
        <div class="opf-progress-bar-wrap">
            <div class="opf-progress-bar-fill" id="opf-progress-bar-fill" style="width: 0%;"></div>
        </div>
        <div class="opf-progress-footer">
            <span id="opf-progress-count">0 / 0 Completed</span>
            <button type="button" class="opf-btn opf-btn-subtle" id="opf-progress-cancel-btn">Stop</button>
        </div>
    </div>
</div>

<!-- Toast Notification Container -->
<div id="opf-toast" class="opf-toast" style="display: none;">
    <svg class="opf-toast-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
    <span class="opf-toast-message" id="opf-toast-message">Action completed successfully</span>
</div>
