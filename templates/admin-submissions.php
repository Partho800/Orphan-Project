<?php
/**
 * Admin Submissions Page Template
 * Redesigned with New Apply & Old Apply Tabs, Excel/CSV Import, Manual ID Entry, Bulk Actions, Status Updates & In-Page PDF Modal
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name    = $wpdb->prefix . 'orphan_applications';
$old_ids_table = $wpdb->prefix . 'orphan_old_ids';

// Active Main Tab ('new' or 'old')
$active_tab = (isset($_GET['tab']) && $_GET['tab'] === 'old') ? 'old' : 'new';

// Query parameters
$search_query = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

// New Apply counts & base filter
$new_base_cond      = "(application_type IS NULL OR application_type = '' OR application_type = 'new')";
$new_total_count    = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $new_base_cond");
$new_pending_count  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $new_base_cond AND status = 'pending'");
$new_approved_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $new_base_cond AND status = 'approved'");
$new_rejected_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $new_base_cond AND status = 'rejected'");

// Old Apply IDs counts
$old_total_count     = (int) $wpdb->get_var("SELECT COUNT(*) FROM $old_ids_table");
$old_submitted_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $old_ids_table WHERE status = 'submitted' AND application_id > 0");

$per_page      = 20;
$paged         = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$filtered_total = 0;

$status_filter = '';
$old_filter    = '';
$submissions   = [];
$old_rows      = [];

if ($active_tab === 'new') {
    $status_filter = isset($_GET['status_filter']) ? sanitize_text_field(strtolower($_GET['status_filter'])) : '';
    $where = "WHERE $new_base_cond";
    if (!empty($search_query)) {
        $search_like = '%' . $wpdb->esc_like($search_query) . '%';
        $where .= $wpdb->prepare(
            " AND (id = %d OR application_no LIKE %s OR student_name LIKE %s OR contact_number LIKE %s OR district LIKE %s)",
            intval($search_query),
            $search_like,
            $search_like,
            $search_like,
            $search_like
        );
    }
    if (!empty($status_filter) && in_array($status_filter, ['pending', 'approved', 'rejected'], true)) {
        $where .= $wpdb->prepare(" AND status = %s", $status_filter);
    }
    $filtered_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");
    $total_pages    = max(1, (int) ceil($filtered_total / $per_page));
    $paged          = min($paged, $total_pages);
    $offset         = ($paged - 1) * $per_page;
    $submissions    = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name $where ORDER BY id ASC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ));
} else {
    $old_filter = isset($_GET['old_filter']) ? sanitize_text_field(strtolower($_GET['old_filter'])) : 'submitted';
    if (!in_array($old_filter, ['submitted', 'all'], true)) {
        $old_filter = 'submitted';
    }
    $old_where = 'WHERE 1=1';
    if (!empty($search_query)) {
        $search_like = '%' . $wpdb->esc_like($search_query) . '%';
        $old_where .= $wpdb->prepare(
            " AND (o.old_id LIKE %s OR o.student_name LIKE %s OR o.note LIKE %s OR a.student_name LIKE %s OR a.contact_number LIKE %s)",
            $search_like,
            $search_like,
            $search_like,
            $search_like,
            $search_like
        );
    }
    if ($old_filter === 'submitted') {
        $old_where .= " AND o.status = 'submitted' AND o.application_id > 0";
    }
    $filtered_total = (int) $wpdb->get_var(
        "SELECT COUNT(*)
         FROM $old_ids_table o
         LEFT JOIN $table_name a ON (o.application_id > 0 AND o.application_id = a.id)
         $old_where"
    );
    $total_pages = max(1, (int) ceil($filtered_total / $per_page));
    $paged       = min($paged, $total_pages);
    $offset      = ($paged - 1) * $per_page;
    $old_rows    = $wpdb->get_results($wpdb->prepare(
        "SELECT o.*,
                a.id AS app_db_id,
                a.application_no AS app_no,
                a.student_name AS app_student_name,
                a.gender AS app_gender,
                a.father_name AS app_father_name,
                a.college_name AS app_college_name,
                a.contact_number AS app_contact_number,
                a.district AS app_district,
                a.status AS app_review_status,
                a.created_at AS app_submitted_at
         FROM $old_ids_table o
         LEFT JOIN $table_name a ON (o.application_id > 0 AND o.application_id = a.id)
         $old_where
         ORDER BY o.id DESC
         LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ));
}

/**
 * Shared Helper: Render Pagination Bar (20 per page with Prev / Page Numbers / Next)
 */
$opf_render_pagination = function($current_page, $total_items, $per_page_limit) {
    $total_pages = (int) ceil($total_items / $per_page_limit);
    if ($total_items <= $per_page_limit || $total_pages <= 1) {
        return;
    }
    $start_item = ($current_page - 1) * $per_page_limit + 1;
    $end_item   = min($total_items, $current_page * $per_page_limit);
    $base_url   = remove_query_arg('paged');
    $page_url   = function($p) use ($base_url) {
        return $p <= 1 ? remove_query_arg('paged', $base_url) : add_query_arg('paged', $p, $base_url);
    };

    $pages_to_show = [1];
    $win_start     = max(2, $current_page - 2);
    $win_end       = min($total_pages - 1, $current_page + 2);
    if ($win_start > 2) {
        $pages_to_show[] = '...';
    }
    for ($p = $win_start; $p <= $win_end; $p++) {
        $pages_to_show[] = $p;
    }
    if ($win_end < $total_pages - 1) {
        $pages_to_show[] = '...';
    }
    if ($total_pages > 1) {
        $pages_to_show[] = $total_pages;
    }
    ?>
    <div class="opf-pagination-bar">
        <div class="opf-pagination-info">
            Showing <strong><?php echo number_format($start_item); ?>–<?php echo number_format($end_item); ?></strong> of <strong><?php echo number_format($total_items); ?></strong>
            <span class="opf-pagination-page-label">(Page <?php echo number_format($current_page); ?> of <?php echo number_format($total_pages); ?>)</span>
        </div>
        <div class="opf-pagination-controls">
            <?php if ($current_page > 1) : ?>
                <a href="<?php echo esc_url($page_url($current_page - 1)); ?>" class="opf-page-btn opf-page-nav" title="Previous Page">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    <span>Prev</span>
                </a>
            <?php else : ?>
                <span class="opf-page-btn opf-page-nav is-disabled">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    <span>Prev</span>
                </span>
            <?php endif; ?>

            <?php foreach ($pages_to_show as $item) : ?>
                <?php if ($item === '...') : ?>
                    <span class="opf-page-ellipsis">...</span>
                <?php elseif ((int) $item === (int) $current_page) : ?>
                    <span class="opf-page-btn active"><?php echo intval($item); ?></span>
                <?php else : ?>
                    <a href="<?php echo esc_url($page_url((int) $item)); ?>" class="opf-page-btn"><?php echo intval($item); ?></a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($current_page < $total_pages) : ?>
                <a href="<?php echo esc_url($page_url($current_page + 1)); ?>" class="opf-page-btn opf-page-nav" title="Next Page">
                    <span>Next</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
            <?php else : ?>
                <span class="opf-page-btn opf-page-nav is-disabled">
                    <span>Next</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <?php
};
?>

<div class="wrap opf-admin-wrap">

    <!-- Top Header -->
    <div class="opf-admin-header">
        <div class="opf-admin-header-title">
            <h1 class="opf-page-heading">Orphan Applications</h1>
            <?php if ($active_tab === 'old') : ?>
                <span class="opf-count-pill" id="opf-total-counter"><?php echo number_format($old_submitted_count); ?> Submitted</span>
            <?php else : ?>
                <span class="opf-count-pill" id="opf-total-counter"><?php echo number_format($new_total_count); ?> Total</span>
            <?php endif; ?>
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

    <!-- Main 2-Tab Bar: New Apply & Old Apply -->
    <div class="opf-main-admin-tabs" role="tablist">
        <a href="<?php echo esc_url(admin_url('admin.php?page=orphan-applications&tab=new')); ?>"
           class="opf-main-admin-tab <?php echo $active_tab === 'new' ? 'active' : ''; ?>"
           role="tab"
           aria-selected="<?php echo $active_tab === 'new' ? 'true' : 'false'; ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
            <span>New Apply</span>
            <span class="opf-main-tab-badge" id="opf-new-tab-count"><?php echo number_format($new_total_count); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=orphan-applications&tab=old')); ?>"
           class="opf-main-admin-tab <?php echo $active_tab === 'old' ? 'active' : ''; ?>"
           role="tab"
           aria-selected="<?php echo $active_tab === 'old' ? 'true' : 'false'; ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><line x1="9" y1="12" x2="15" y2="12"></line><line x1="9" y1="16" x2="13" y2="16"></line></svg>
            <span>Old Apply</span>
            <span class="opf-main-tab-badge" id="opf-old-tab-count"><?php echo number_format($old_submitted_count); ?></span>
        </a>
    </div>

    <?php if ($active_tab === 'new') : ?>

    <!-- Filter & Search Controls Bar (New Apply) -->
    <div class="opf-controls-bar">
        <div class="opf-status-tabs">
            <a href="<?php echo esc_url(admin_url('admin.php?page=orphan-applications&tab=new')); ?>" class="opf-tab-link <?php echo empty($status_filter) ? 'active' : ''; ?>">
                All <span class="opf-tab-badge"><?php echo $new_total_count; ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=orphan-applications&tab=new&status_filter=pending')); ?>" class="opf-tab-link opf-tab-pending <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                Pending <span class="opf-tab-badge"><?php echo $new_pending_count; ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=orphan-applications&tab=new&status_filter=approved')); ?>" class="opf-tab-link opf-tab-approved <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                Approved <span class="opf-tab-badge"><?php echo $new_approved_count; ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=orphan-applications&tab=new&status_filter=rejected')); ?>" class="opf-tab-link opf-tab-rejected <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                Rejected <span class="opf-tab-badge"><?php echo $new_rejected_count; ?></span>
            </a>
        </div>

        <!-- Search Bar -->
        <form method="get" class="opf-search-form">
            <input type="hidden" name="page" value="orphan-applications">
            <input type="hidden" name="tab" value="new">
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
            <button type="button" class="opf-btn opf-btn-bulk-download" id="opf-btn-bulk-download">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>Download Selected PDFs</span>
            </button>

            <button type="button" class="opf-btn opf-btn-bulk-delete" id="opf-btn-bulk-delete">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                <span>Delete Selected</span>
            </button>

            <button type="button" class="opf-btn opf-btn-subtle" id="opf-btn-deselect-all">
                Cancel
            </button>
        </div>
    </div>

    <!-- Main Table Container (New Apply) -->
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
                    foreach ($submissions as $sub) : 
                        $data = json_decode($sub->form_data, true);
                        if (!is_array($data)) $data = [];
                        $status = strtolower($sub->status ?: 'pending');
                        $age = isset($data['age']) && $data['age'] ? intval($data['age']) : '';
                        $view_url = add_query_arg([
                            'opf_view_app'    => $sub->application_no ?: $sub->id,
                            'opf_admin'       => '1',
                            'opf_auto_preview'=> '1'
                        ], home_url('/'));
                    ?>
                    <tr id="opf-row-<?php echo esc_attr($sub->id); ?>" class="opf-app-row" data-id="<?php echo esc_attr($sub->id); ?>" data-app-no="<?php echo esc_attr($sub->application_no); ?>" data-student="<?php echo esc_attr($sub->student_name); ?>">
                        <td class="opf-col-check">
                            <label class="opf-checkbox-wrap">
                                <input type="checkbox" class="opf-row-check" value="<?php echo esc_attr($sub->id); ?>" data-app-no="<?php echo esc_attr($sub->application_no); ?>" data-student="<?php echo esc_attr($sub->student_name); ?>" aria-label="Select application #<?php echo esc_attr($sub->id); ?>">
                                <span class="opf-custom-check"></span>
                            </label>
                        </td>

                        <td class="opf-col-id">
                            <strong class="opf-numeric-id"><?php echo esc_html($sub->application_no ?: $sub->id); ?></strong>
                        </td>

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

                        <td class="opf-col-district">
                            <?php if (!empty($sub->district)) : ?>
                                <span class="opf-district-name"><?php echo esc_html($sub->district); ?></span>
                            <?php else : ?>
                                <span class="opf-muted">—</span>
                            <?php endif; ?>
                        </td>

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

                        <td class="opf-col-date">
                            <span class="opf-date-val"><?php echo esc_html(date('d M Y', strtotime($sub->created_at))); ?></span>
                            <span class="opf-time-val"><?php echo esc_html(date('h:i A', strtotime($sub->created_at))); ?></span>
                        </td>

                        <td class="opf-col-actions">
                            <div class="opf-actions-cell">
                                <button type="button" class="opf-btn opf-btn-view-pdf" data-db-id="<?php echo esc_attr($sub->id); ?>" data-app-id="<?php echo esc_attr($sub->application_no ?: $sub->id); ?>" data-app-no="<?php echo esc_attr($sub->application_no); ?>" data-student="<?php echo esc_attr($sub->student_name); ?>" data-view-url="<?php echo esc_url($view_url); ?>" title="View Application PDF">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                    <span>View PDF</span>
                                </button>
                                <button type="button" class="opf-btn opf-btn-edit-pdf" data-db-id="<?php echo esc_attr($sub->id); ?>" data-app-id="<?php echo esc_attr($sub->application_no ?: $sub->id); ?>" data-app-no="<?php echo esc_attr($sub->application_no); ?>" data-student="<?php echo esc_attr($sub->student_name); ?>" data-view-url="<?php echo esc_url($view_url); ?>" title="Edit Application PDF">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    <span>Edit</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php $opf_render_pagination($paged, $filtered_total, $per_page); ?>
    </div>

    <?php else : ?>

    <!-- =========================================================
         OLD APPLY TAB: Add Manually + Excel Upload + Old IDs List
         ========================================================= -->
    <div class="opf-old-import-grid">
        <!-- Manual Add ID Card -->
        <div class="opf-import-card">
            <div class="opf-import-card-head">
                <div class="opf-import-icon opf-import-icon-blue">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </div>
                <div>
                    <h3 class="opf-import-title">Add Old ID Manually</h3>
                    <p class="opf-import-desc">Type an Application ID or paste multiple IDs separated by commas or new lines.</p>
                </div>
            </div>
            <form id="opf-manual-old-id-form" class="opf-import-form">
                <div class="opf-import-field">
                    <label for="opf-manual-old-ids">Application ID(s) <span class="opf-req-star">*</span></label>
                    <textarea id="opf-manual-old-ids" rows="2" placeholder="1001, 1002, SAP-2026-05 (or one ID per line)" required></textarea>
                </div>
                <div class="opf-import-field-row">
                    <div class="opf-import-field" style="flex:1;">
                        <label for="opf-manual-old-name">Name (Optional)</label>
                        <input type="text" id="opf-manual-old-name" placeholder="Student / applicant name">
                    </div>
                    <div class="opf-import-field" style="flex:1;">
                        <label for="opf-manual-old-note">Note (Optional)</label>
                        <input type="text" id="opf-manual-old-note" placeholder="Batch or extra note">
                    </div>
                    <button type="submit" class="opf-btn opf-btn-primary opf-btn-add-id" id="opf-btn-add-manual-id">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Add ID(s)</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Excel / CSV Upload Card -->
        <div class="opf-import-card">
            <div class="opf-import-card-head">
                <div class="opf-import-icon opf-import-icon-green">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><path d="M8 13h2l2 3 2-3h2"></path><path d="M8 17h8"></path></svg>
                </div>
                <div>
                    <h3 class="opf-import-title">Upload Excel / CSV File</h3>
                    <p class="opf-import-desc">Upload <strong>.xlsx</strong>, <strong>.xls</strong>, or <strong>.csv</strong> (Columns: <strong>Note</strong>, <strong>Name</strong>, <strong>ID</strong>). New uploads append to your list without deleting previous IDs.</p>
                </div>
            </div>
            <form id="opf-excel-old-id-form" class="opf-import-form" enctype="multipart/form-data">
                <label class="opf-excel-dropzone" for="opf-old-excel-file" id="opf-excel-dropzone">
                    <input type="file" id="opf-old-excel-file" name="excel_file" accept=".xlsx,.xls,.csv,.txt">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0E84C7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    <span class="opf-excel-drop-text" id="opf-excel-file-name">Click to choose Excel (.xlsx / .xls) or CSV file</span>
                </label>
                <div class="opf-excel-actions-row">
                    <a href="<?php echo esc_url(admin_url('admin-post.php?action=opf_download_old_sample_csv')); ?>" class="opf-sample-link">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <span>Sample CSV Format</span>
                    </a>
                    <button type="submit" class="opf-btn opf-btn-primary" id="opf-btn-upload-excel">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        <span>Upload &amp; Import IDs</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Filter & Search Controls Bar (Old Apply IDs) -->
    <div class="opf-controls-bar">
        <div class="opf-status-tabs">
            <a href="<?php echo esc_url(admin_url('admin.php?page=orphan-applications&tab=old&old_filter=submitted')); ?>" class="opf-tab-link opf-tab-approved <?php echo $old_filter === 'submitted' ? 'active' : ''; ?>">
                Submitted <span class="opf-tab-badge"><?php echo $old_submitted_count; ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=orphan-applications&tab=old&old_filter=all')); ?>" class="opf-tab-link <?php echo $old_filter === 'all' ? 'active' : ''; ?>">
                All IDs <span class="opf-tab-badge"><?php echo $old_total_count; ?></span>
            </a>
        </div>

        <form method="get" class="opf-search-form">
            <input type="hidden" name="page" value="orphan-applications">
            <input type="hidden" name="tab" value="old">
            <input type="hidden" name="old_filter" value="<?php echo esc_attr($old_filter); ?>">
            <div class="opf-search-input-wrap">
                <svg class="opf-search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="search" name="s" id="opf-search-box" value="<?php echo esc_attr($search_query); ?>" placeholder="Search Old ID, Note, Student...">
                <?php if (!empty($search_query)) : ?>
                    <a href="<?php echo esc_url(remove_query_arg('s')); ?>" class="opf-search-clear" title="Clear Search">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </a>
                <?php endif; ?>
                <button type="submit" class="opf-btn opf-btn-search">Search</button>
            </div>
        </form>
    </div>

    <!-- Floating Bulk Actions Toolbar (Old IDs) -->
    <div id="opf-old-bulk-bar" class="opf-bulk-bar" style="display: none;">
        <div class="opf-bulk-left">
            <span class="opf-bulk-indicator">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <strong id="opf-old-selected-count">0</strong> ID(s) selected
            </span>
        </div>
        <div class="opf-bulk-actions">
            <button type="button" class="opf-btn opf-btn-bulk-delete" id="opf-btn-old-bulk-delete">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                <span>Delete Selected IDs</span>
            </button>
            <button type="button" class="opf-btn opf-btn-subtle" id="opf-btn-old-deselect-all">
                Cancel
            </button>
        </div>
    </div>

    <!-- Old Apply IDs Table -->
    <div class="opf-table-card">
        <table class="opf-data-table" id="opf-old-ids-table">
            <thead>
                <tr>
                    <th class="opf-col-check">
                        <label class="opf-checkbox-wrap">
                            <input type="checkbox" id="opf-old-select-all" aria-label="Select all old IDs">
                            <span class="opf-custom-check"></span>
                        </label>
                    </th>
                    <th class="opf-col-id">ID</th>
                    <th class="opf-col-student">Name</th>
                    <th class="opf-col-note">Note</th>
                    <th class="opf-col-old-state">Form Status</th>
                    <th class="opf-col-status">Review Status</th>
                    <th class="opf-col-date">Added Date</th>
                    <th class="opf-col-actions">Actions</th>
                </tr>
            </thead>
            <tbody id="opf-old-ids-tbody">
                <?php if (empty($old_rows)) : ?>
                    <tr class="opf-row-empty">
                        <td colspan="8">
                            <div class="opf-empty-state">
                                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"></rect><line x1="7" y1="9" x2="17" y2="9"></line><line x1="7" y1="13" x2="13" y2="13"></line></svg>
                                <?php if ($old_filter === 'submitted') : ?>
                                    <p class="opf-empty-title">No submitted Old Applications yet</p>
                                    <p class="opf-empty-desc">Once applicants submit the form using an Old ID, their records will appear here. Click <strong>All IDs</strong> to view all uploaded IDs.</p>
                                <?php else : ?>
                                    <p class="opf-empty-title">No Old Application IDs found</p>
                                    <p class="opf-empty-desc">Add IDs manually or upload an Excel/CSV file above so applicants can match them on the Old Application form.</p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($old_rows as $row) :
                        $is_submitted  = (!empty($row->app_db_id) && intval($row->app_db_id) > 0);
                        $review_status = $is_submitted ? strtolower($row->app_review_status ?: 'pending') : 'pending';
                        $display_no    = $is_submitted && !empty($row->app_no) ? $row->app_no : $row->old_id;
                        $display_name  = $is_submitted && !empty($row->app_student_name) ? $row->app_student_name : (!empty($row->student_name) ? $row->student_name : '');
                        $view_url      = $is_submitted ? add_query_arg([
                            'opf_view_app'    => $display_no,
                            'opf_admin'       => '1',
                            'opf_auto_preview'=> '1'
                        ], home_url('/')) : '';
                    ?>
                    <tr id="<?php echo $is_submitted ? 'opf-row-' . esc_attr($row->app_db_id) : 'opf-old-row-' . esc_attr($row->id); ?>"
                        class="opf-old-id-row"
                        data-old-row-id="<?php echo esc_attr($row->id); ?>"
                        data-old-id="<?php echo esc_attr($row->old_id); ?>">
                        <td class="opf-col-check">
                            <label class="opf-checkbox-wrap">
                                <input type="checkbox" class="opf-old-row-check" value="<?php echo esc_attr($row->id); ?>" aria-label="Select Old ID">
                                <span class="opf-custom-check"></span>
                            </label>
                        </td>

                        <td class="opf-col-id">
                            <strong class="opf-numeric-id"><?php echo esc_html($row->old_id); ?></strong>
                        </td>

                        <td class="opf-col-student">
                            <?php if (!empty($display_name)) : ?>
                                <strong class="opf-student-name"><?php echo esc_html($display_name); ?></strong>
                                <?php if ($is_submitted) : ?>
                                    <div class="opf-student-meta">
                                        <?php if (!empty($row->app_gender)) : ?>
                                            <span class="opf-gender-badge opf-gender-<?php echo esc_attr(strtolower($row->app_gender)); ?>">
                                                <?php echo esc_html(ucfirst($row->app_gender)); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($row->app_college_name)) : ?>
                                            <span class="opf-student-college" title="<?php echo esc_attr($row->app_college_name); ?>">
                                                <?php echo esc_html(mb_strimwidth($row->app_college_name, 0, 24, '...')); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else : ?>
                                <span class="opf-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <td class="opf-col-note">
                            <?php if (!empty($row->note)) : ?>
                                <span class="opf-old-note-txt"><?php echo esc_html($row->note); ?></span>
                            <?php else : ?>
                                <span class="opf-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <td class="opf-col-old-state">
                            <?php if ($is_submitted) : ?>
                                <span class="opf-old-state-pill is-submitted">
                                    <span class="opf-old-dot"></span> Submitted
                                </span>
                                <?php if (!empty($row->app_submitted_at)) : ?>
                                    <span class="opf-time-val" style="display:block;margin-top:3px;"><?php echo esc_html(date('d M Y', strtotime($row->app_submitted_at))); ?></span>
                                <?php endif; ?>
                            <?php else : ?>
                                <span class="opf-old-state-pill is-waiting">
                                    <span class="opf-old-dot"></span> Waiting
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="opf-col-status">
                            <?php if ($is_submitted) : ?>
                                <div class="opf-status-select-wrap opf-status-wrap-<?php echo esc_attr($review_status); ?>" id="opf-status-wrap-<?php echo esc_attr($row->app_db_id); ?>">
                                    <span class="opf-status-dot"></span>
                                    <select class="opf-status-select" data-app-id="<?php echo esc_attr($row->app_db_id); ?>" aria-label="Update status">
                                        <option value="pending" <?php selected($review_status, 'pending'); ?>>Pending</option>
                                        <option value="approved" <?php selected($review_status, 'approved'); ?>>Approved</option>
                                        <option value="rejected" <?php selected($review_status, 'rejected'); ?>>Rejected</option>
                                    </select>
                                    <svg class="opf-status-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                    <span class="opf-status-saving-spinner" style="display:none;"></span>
                                </div>
                            <?php else : ?>
                                <span class="opf-muted">Not submitted yet</span>
                            <?php endif; ?>
                        </td>

                        <td class="opf-col-date">
                            <span class="opf-date-val"><?php echo esc_html(date('d M Y', strtotime($row->created_at))); ?></span>
                            <span class="opf-time-val"><?php echo esc_html(date('h:i A', strtotime($row->created_at))); ?></span>
                        </td>

                        <td class="opf-col-actions">
                            <div class="opf-actions-cell">
                                <?php if ($is_submitted) : ?>
                                    <button type="button" class="opf-btn opf-btn-view-pdf" data-db-id="<?php echo esc_attr($row->app_db_id); ?>" data-app-id="<?php echo esc_attr($display_no); ?>" data-app-no="<?php echo esc_attr($display_no); ?>" data-student="<?php echo esc_attr($row->app_student_name); ?>" data-view-url="<?php echo esc_url($view_url); ?>" title="View Application PDF">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                        <span>View PDF</span>
                                    </button>
                                    <button type="button" class="opf-btn opf-btn-edit-pdf" data-db-id="<?php echo esc_attr($row->app_db_id); ?>" data-app-id="<?php echo esc_attr($display_no); ?>" data-app-no="<?php echo esc_attr($display_no); ?>" data-student="<?php echo esc_attr($row->app_student_name); ?>" data-view-url="<?php echo esc_url($view_url); ?>" title="Edit Application PDF">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        <span>Edit</span>
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="opf-btn opf-btn-delete-old-id" data-old-row-id="<?php echo esc_attr($row->id); ?>" data-old-id="<?php echo esc_attr($row->old_id); ?>" data-submitted="<?php echo $is_submitted ? '1' : '0'; ?>" title="Delete Old ID">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    <span>Delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php $opf_render_pagination($paged, $filtered_total, $per_page); ?>
    </div>

    <?php endif; ?>

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
                <!-- Edit / Save / Cancel buttons inside modal -->
                <button type="button" class="opf-btn opf-btn-modal-edit" id="opf-modal-edit-btn" title="Edit Application Details">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    <span>Edit PDF</span>
                </button>
                <button type="button" class="opf-btn opf-btn-modal-save" id="opf-modal-save-btn" style="display:none;" title="Save PDF Changes">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Save Changes</span>
                </button>
                <button type="button" class="opf-btn opf-btn-modal-cancel" id="opf-modal-cancel-btn" style="display:none;" title="Cancel Editing">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    <span>Cancel</span>
                </button>

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
