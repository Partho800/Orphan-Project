/**
 * Modern Admin Panel JavaScript for Orphan Project Applications
 * 100% Pure SVG Icons, In-Page PDF Modal, Reliable AJAX Status Updates & Bulk Actions
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const config = window.orphan_admin_obj || {
            ajax_url: ajaxurl || '',
            nonce: '',
            site_url: '/'
        };

        // DOM Elements
        const selectAllCheckbox = document.getElementById('opf-select-all');
        const bulkBar = document.getElementById('opf-bulk-bar');
        const selectedCountEl = document.getElementById('opf-selected-count');
        const bulkDeleteBtn = document.getElementById('opf-btn-bulk-delete');
        const bulkDownloadBtn = document.getElementById('opf-btn-bulk-download');
        const deselectAllBtn = document.getElementById('opf-btn-deselect-all');
        const copyShortcodeBtn = document.getElementById('opf-copy-shortcode-btn');
        const adminWrap = document.querySelector('.opf-admin-wrap');
        const tableBody = document.querySelector('#opf-applications-table tbody');
        const activeTableBody = document.querySelector('.opf-data-table tbody');
        const totalCounter = document.getElementById('opf-total-counter');

        // Modal Elements
        const pdfModal = document.getElementById('opf-pdf-modal');
        const modalCloseBtn = document.getElementById('opf-modal-close-btn');
        const modalIframe = document.getElementById('opf-modal-iframe');
        const modalBadge = document.getElementById('opf-modal-badge');
        const modalHeading = document.getElementById('opf-modal-student-name');
        const modalAppCode = document.getElementById('opf-modal-app-code');
        const modalNewTabLink = document.getElementById('opf-modal-newtab-link');
        const modalEditBtn = document.getElementById('opf-modal-edit-btn');
        const modalSaveBtn = document.getElementById('opf-modal-save-btn');
        const modalCancelBtn = document.getElementById('opf-modal-cancel-btn');
        const modalDownloadBtn = document.getElementById('opf-modal-download-btn');
        const modalLoading = document.getElementById('opf-modal-loading');

        // Progress Modal Elements
        const progressModal = document.getElementById('opf-bulk-progress-modal');
        const progressDesc = document.getElementById('opf-progress-desc');
        const progressBarFill = document.getElementById('opf-progress-bar-fill');
        const progressCount = document.getElementById('opf-progress-count');
        const progressCancelBtn = document.getElementById('opf-progress-cancel-btn');

        // Toast Element
        const toast = document.getElementById('opf-toast');
        const toastMsg = document.getElementById('opf-toast-message');
        let toastTimeout = null;

        function showToast(message, isError) {
            if (!toast) return;
            if (toastTimeout) clearTimeout(toastTimeout);
            toast.classList.toggle('is-error', !!isError);
            toastMsg.textContent = message;
            toast.style.display = 'flex';
            toastTimeout = setTimeout(function () {
                toast.style.display = 'none';
            }, 3800);
        }

        // 1. Copy Shortcode to Clipboard
        if (copyShortcodeBtn) {
            copyShortcodeBtn.addEventListener('click', function () {
                const code = '[orphan_registration_form]';
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(code).then(onCopied);
                } else {
                    const temp = document.createElement('textarea');
                    temp.value = code;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                    onCopied();
                }

                function onCopied() {
                    const txtSpan = copyShortcodeBtn.querySelector('.opf-copy-txt');
                    const originalTxt = txtSpan ? txtSpan.textContent : 'Copy';
                    copyShortcodeBtn.classList.add('copied');
                    if (txtSpan) txtSpan.textContent = 'Copied!';
                    showToast('Shortcode copied to clipboard!');
                    setTimeout(function () {
                        copyShortcodeBtn.classList.remove('copied');
                        if (txtSpan) txtSpan.textContent = originalTxt;
                    }, 2200);
                }
            });
        }

        // 2. Row Selection & Bulk Actions Bar
        function getSelectedCheckboxes() {
            return Array.from(document.querySelectorAll('.opf-row-check:checked'));
        }

        function updateSelectionState() {
            const checkedBoxes = getSelectedCheckboxes();
            const totalBoxes = document.querySelectorAll('.opf-row-check');
            const count = checkedBoxes.length;

            if (selectedCountEl) {
                selectedCountEl.textContent = count;
            }

            if (count > 0) {
                bulkBar.style.display = 'flex';
            } else {
                bulkBar.style.display = 'none';
            }

            // Sync master checkbox
            if (selectAllCheckbox && totalBoxes.length > 0) {
                selectAllCheckbox.checked = (count === totalBoxes.length);
                selectAllCheckbox.indeterminate = (count > 0 && count < totalBoxes.length);
            }

            // Highlight selected rows
            totalBoxes.forEach(function (cb) {
                const row = cb.closest('tr');
                if (row) {
                    row.classList.toggle('is-selected', cb.checked);
                }
            });
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                const checked = this.checked;
                const rowCheckboxes = document.querySelectorAll('.opf-row-check');
                rowCheckboxes.forEach(function (cb) {
                    cb.checked = checked;
                });
                updateSelectionState();
            });
        }

        // Event delegation for row checkboxes
        if (tableBody) {
            tableBody.addEventListener('change', function (e) {
                if (e.target && e.target.classList.contains('opf-row-check')) {
                    updateSelectionState();
                }
            });
        }

        if (deselectAllBtn) {
            deselectAllBtn.addEventListener('click', function () {
                if (selectAllCheckbox) selectAllCheckbox.checked = false;
                document.querySelectorAll('.opf-row-check').forEach(function (cb) {
                    cb.checked = false;
                });
                updateSelectionState();
            });
        }

        // 3. Reliable Status Update via AJAX (works in both New Apply and Old Apply tables)
        if (activeTableBody) {
            activeTableBody.addEventListener('change', function (e) {
                if (e.target && e.target.classList.contains('opf-status-select')) {
                    const select = e.target;
                    const appId = select.getAttribute('data-app-id');
                    const newStatus = select.value;
                    const wrap = document.getElementById('opf-status-wrap-' + appId);
                    const spinner = wrap ? wrap.querySelector('.opf-status-saving-spinner') : null;

                    if (spinner) spinner.style.display = 'inline-block';

                    const formData = new FormData();
                    formData.append('action', 'opf_update_status');
                    formData.append('nonce', config.nonce);
                    formData.append('app_id', appId);
                    formData.append('status', newStatus);

                    fetch(config.ajax_url, {
                        method: 'POST',
                        body: formData
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (spinner) spinner.style.display = 'none';
                            if (data.success) {
                                if (wrap) {
                                    wrap.className = 'opf-status-select-wrap opf-status-wrap-' + newStatus;
                                }
                                showToast(data.data.message || 'Status updated to ' + newStatus + '!');
                            } else {
                                showToast(data.data.message || 'Could not update status', true);
                            }
                        })
                        .catch(function (err) {
                            if (spinner) spinner.style.display = 'none';
                            console.error('Status update error:', err);
                            showToast('Server error while updating status', true);
                        });
                }
            });
        }

        // 4. Bulk Delete Action
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function () {
                const checkedBoxes = getSelectedCheckboxes();
                const count = checkedBoxes.length;
                if (count === 0) return;

                const confirmed = confirm(
                    'Are you sure you want to permanently delete ' + count + ' selected application' + (count > 1 ? 's' : '') + '?\nThis action cannot be undone.'
                );
                if (!confirmed) return;

                const ids = checkedBoxes.map(function (cb) {
                    return cb.value;
                });

                bulkDeleteBtn.disabled = true;
                const originalHtml = bulkDeleteBtn.innerHTML;
                bulkDeleteBtn.innerHTML = '<span class="opf-status-saving-spinner" style="position:static;display:inline-block;margin-right:6px;"></span> Deleting...';

                const formData = new FormData();
                formData.append('action', 'opf_bulk_delete');
                formData.append('nonce', config.nonce);
                ids.forEach(function (id) {
                    formData.append('ids[]', id);
                });

                fetch(config.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        bulkDeleteBtn.disabled = false;
                        bulkDeleteBtn.innerHTML = originalHtml;

                        if (data.success) {
                            // Smoothly remove rows from the table
                            ids.forEach(function (id) {
                                const row = document.getElementById('opf-row-' + id);
                                if (row) {
                                    row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                                    row.style.opacity = '0';
                                    row.style.transform = 'scale(0.95)';
                                    setTimeout(function () {
                                        if (row.parentNode) row.parentNode.removeChild(row);
                                        checkIfTableEmpty();
                                    }, 300);
                                }
                            });

                            updateSelectionState();
                            showToast(data.data.message || count + ' application(s) deleted!');

                            // Decrement counter
                            if (totalCounter) {
                                const currentTotal = parseInt(totalCounter.textContent.replace(/[^0-9]/g, ''), 10) || 0;
                                const newTotal = Math.max(0, currentTotal - count);
                                totalCounter.textContent = newTotal + ' Total';
                            }
                        } else {
                            showToast(data.data.message || 'Error deleting applications', true);
                        }
                    })
                    .catch(function (err) {
                        bulkDeleteBtn.disabled = false;
                        bulkDeleteBtn.innerHTML = originalHtml;
                        console.error('Bulk delete error:', err);
                        showToast('Server error while deleting applications', true);
                    });
            });
        }

        function checkIfTableEmpty() {
            const rows = document.querySelectorAll('.opf-app-row');
            if (rows.length === 0 && tableBody) {
                tableBody.innerHTML = `
                    <tr class="opf-row-empty">
                        <td colspan="9">
                            <div class="opf-empty-state">
                                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                                <p class="opf-empty-title">No applications found</p>
                                <p class="opf-empty-desc">All selected applications have been removed.</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
        }

        // 5. In-Page PDF Modal Popup & Inline PDF Edit Mode
        let currentModalViewUrl = '';
        let currentModalDbId = '';
        let currentModalEditMode = false;
        const origSaveBtnHtml = modalSaveBtn ? modalSaveBtn.innerHTML : '';

        function buildModalUrl(baseUrl, isEdit) {
            try {
                const u = new URL(baseUrl, window.location.href);
                if (isEdit) {
                    u.searchParams.set('opf_edit', '1');
                    u.searchParams.delete('opf_auto_preview');
                } else {
                    u.searchParams.delete('opf_edit');
                    u.searchParams.set('opf_auto_preview', '1');
                }
                return u.toString();
            } catch (err) {
                let clean = baseUrl.replace(/([?&])(opf_edit|opf_auto_preview)=1(&|$)/g, '$1').replace(/[?&]$/, '');
                const sep = clean.indexOf('?') !== -1 ? '&' : '?';
                return isEdit ? (clean + sep + 'opf_edit=1') : (clean + sep + 'opf_auto_preview=1');
            }
        }

        function updateModalToolbarMode(isEdit) {
            currentModalEditMode = !!isEdit;
            if (modalEditBtn) modalEditBtn.style.display = isEdit ? 'none' : 'inline-flex';
            if (modalDownloadBtn) modalDownloadBtn.style.display = isEdit ? 'none' : 'inline-flex';
            if (modalSaveBtn) {
                modalSaveBtn.style.display = isEdit ? 'inline-flex' : 'none';
                modalSaveBtn.disabled = false;
                if (origSaveBtnHtml) modalSaveBtn.innerHTML = origSaveBtnHtml;
            }
            if (modalCancelBtn) {
                modalCancelBtn.style.display = isEdit ? 'inline-flex' : 'none';
                modalCancelBtn.disabled = false;
            }
        }

        function loadModalIframe(isEdit) {
            if (!currentModalViewUrl || !modalIframe) return;
            updateModalToolbarMode(isEdit);

            const targetUrl = buildModalUrl(currentModalViewUrl, isEdit);
            if (modalNewTabLink) modalNewTabLink.href = targetUrl;

            if (modalLoading) modalLoading.style.display = 'flex';
            modalIframe.style.opacity = '0';
            modalIframe.src = targetUrl;
            modalIframe.onload = function () {
                if (modalLoading) modalLoading.style.display = 'none';
                modalIframe.style.opacity = '1';
            };
        }

        function openPdfModal(appId, appNo, studentName, viewUrl, dbId, startInEditMode) {
            if (!pdfModal) return;

            currentModalViewUrl = viewUrl;
            currentModalDbId = dbId || '';

            var displayId = appNo || appId;
            if (modalBadge) modalBadge.textContent = 'Application #' + displayId;
            if (modalHeading) modalHeading.textContent = studentName || 'Application View';
            if (modalAppCode) modalAppCode.textContent = '';

            loadModalIframe(!!startInEditMode);

            pdfModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closePdfModal() {
            if (!pdfModal) return;
            pdfModal.style.display = 'none';
            document.body.style.overflow = '';
            currentModalViewUrl = '';
            currentModalDbId = '';
            currentModalEditMode = false;
            if (modalIframe) modalIframe.src = 'about:blank';
        }

        if (modalCloseBtn) modalCloseBtn.addEventListener('click', closePdfModal);

        if (modalEditBtn) {
            modalEditBtn.addEventListener('click', function () {
                loadModalIframe(true);
            });
        }

        if (modalCancelBtn) {
            modalCancelBtn.addEventListener('click', function () {
                loadModalIframe(false);
            });
        }

        if (modalSaveBtn) {
            modalSaveBtn.addEventListener('click', function () {
                if (modalSaveBtn.disabled) return;
                if (!modalIframe || !modalIframe.contentWindow || typeof modalIframe.contentWindow.saveApplicationEdits !== 'function') {
                    showToast('PDF editor is still loading. Please try again.', true);
                    return;
                }
                modalSaveBtn.disabled = true;
                if (modalCancelBtn) modalCancelBtn.disabled = true;
                modalSaveBtn.innerHTML = '<svg class="opf-spin" style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path></svg> <span>Saving...</span>';
                modalIframe.contentWindow.saveApplicationEdits();
            });
        }

        function updateTableRowFromEdit(data) {
            if (!data || !data.app_id) return;
            const row = document.getElementById('opf-row-' + data.app_id);
            if (!row) return;

            if (data.student_name) {
                row.setAttribute('data-student', data.student_name);
                const nameEl = row.querySelector('.opf-student-name');
                if (nameEl) nameEl.textContent = data.student_name;

                const cb = row.querySelector('.opf-row-check');
                if (cb) cb.setAttribute('data-student', data.student_name);

                const viewBtn = row.querySelector('.opf-btn-view-pdf');
                if (viewBtn) viewBtn.setAttribute('data-student', data.student_name);

                const editBtn = row.querySelector('.opf-btn-edit-pdf');
                if (editBtn) editBtn.setAttribute('data-student', data.student_name);

                if (modalHeading) modalHeading.textContent = data.student_name;
            }

            if (data.gender) {
                const genderEl = row.querySelector('.opf-gender-badge');
                if (genderEl) {
                    genderEl.className = 'opf-gender-badge opf-gender-' + data.gender.toLowerCase();
                    genderEl.textContent = data.gender.charAt(0).toUpperCase() + data.gender.slice(1);
                }
            }

            if (typeof data.college_name === 'string') {
                const collegeEl = row.querySelector('.opf-student-college');
                if (collegeEl) {
                    const trimmed = data.college_name.length > 24 ? data.college_name.substring(0, 21) + '...' : data.college_name;
                    collegeEl.textContent = trimmed;
                    collegeEl.title = data.college_name;
                }
            }

            if (data.dob_formatted) {
                const dobCell = row.querySelector('.opf-col-dob');
                if (dobCell) {
                    let html = '<span class="opf-dob-val"></span>';
                    if (data.age) html += ' <span class="opf-age-pill"></span>';
                    dobCell.innerHTML = html;
                    dobCell.querySelector('.opf-dob-val').textContent = data.dob_formatted;
                    if (data.age) dobCell.querySelector('.opf-age-pill').textContent = data.age + ' yrs';
                }
            }

            if (typeof data.contact_number === 'string') {
                const contactCell = row.querySelector('.opf-col-contact');
                if (contactCell) {
                    if (data.contact_number) {
                        contactCell.innerHTML = '<a href="" class="opf-contact-link" title="Call student"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> <span></span></a>';
                        const link = contactCell.querySelector('.opf-contact-link');
                        link.href = 'tel:+880' + data.contact_number;
                        link.querySelector('span').textContent = '+880 ' + data.contact_number;
                    } else {
                        contactCell.innerHTML = '<span class="opf-muted">—</span>';
                    }
                }
            }

            if (typeof data.district === 'string') {
                const distCell = row.querySelector('.opf-col-district');
                if (distCell) {
                    if (data.district) {
                        distCell.innerHTML = '<span class="opf-district-name"></span>';
                        distCell.querySelector('.opf-district-name').textContent = data.district;
                    } else {
                        distCell.innerHTML = '<span class="opf-muted">—</span>';
                    }
                }
            }
        }

        window.addEventListener('message', function (e) {
            if (!e.data || !e.data.type) return;
            if (e.data.type === 'opf_edit_saved') {
                const d = e.data.data || {};
                showToast(d.message || 'Application PDF updated successfully!');
                updateTableRowFromEdit(d);
                loadModalIframe(false);
            } else if (e.data.type === 'opf_edit_error') {
                showToast(e.data.message || 'Failed to save PDF changes.', true);
                if (modalSaveBtn) {
                    modalSaveBtn.disabled = false;
                    if (origSaveBtnHtml) modalSaveBtn.innerHTML = origSaveBtnHtml;
                }
                if (modalCancelBtn) modalCancelBtn.disabled = false;
            }
        });

        window.addEventListener('click', function (e) {
            if (e.target === pdfModal) {
                closePdfModal();
            }
        });

        window.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (pdfModal && pdfModal.style.display === 'flex') {
                    closePdfModal();
                }
            }
        });

        // Trigger Download from inside modal header button
        if (modalDownloadBtn) {
            modalDownloadBtn.addEventListener('click', function () {
                if (modalDownloadBtn.classList.contains('is-loading')) return;
                const originalHtml = modalDownloadBtn.innerHTML;
                modalDownloadBtn.classList.add('is-loading');
                modalDownloadBtn.disabled = true;
                modalDownloadBtn.innerHTML = '<svg class="opf-spin" style="width:14px;height:14px;margin-right:6px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path></svg> <span>Downloading...</span>';

                let downloadTriggered = false;
                if (modalIframe && modalIframe.contentWindow) {
                    try {
                        if (typeof modalIframe.contentWindow.downloadApplicationPDF === 'function') {
                            modalIframe.contentWindow.downloadApplicationPDF();
                            downloadTriggered = true;
                        }
                    } catch (e) {
                        console.warn('Could not call iframe function directly', e);
                    }
                }

                if (!downloadTriggered && modalIframe && modalIframe.contentWindow) {
                    modalIframe.contentWindow.print();
                    modalDownloadBtn.classList.remove('is-loading');
                    modalDownloadBtn.disabled = false;
                    modalDownloadBtn.innerHTML = originalHtml;
                    return;
                }

                function onDownloadMsg(e) {
                    if (e.data && (e.data.type === 'opf_download_success' || e.data.type === 'opf_download_error')) {
                        window.removeEventListener('message', onDownloadMsg);
                        modalDownloadBtn.classList.remove('is-loading');
                        modalDownloadBtn.disabled = false;
                        modalDownloadBtn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>Downloaded!</span>';
                        showToast('PDF downloaded successfully!');
                        setTimeout(function () {
                            modalDownloadBtn.innerHTML = originalHtml;
                        }, 3000);
                    }
                }

                window.addEventListener('message', onDownloadMsg);

                // Fallback timeout
                setTimeout(function () {
                    window.removeEventListener('message', onDownloadMsg);
                    modalDownloadBtn.classList.remove('is-loading');
                    modalDownloadBtn.disabled = false;
                    modalDownloadBtn.innerHTML = originalHtml;
                }, 7000);
            });
        }

        // Table Event Delegation for "View PDF" & "Edit" Buttons (works in both New Apply and Old Apply)
        if (activeTableBody) {
            activeTableBody.addEventListener('click', function (e) {
                const viewBtn = e.target.closest('.opf-btn-view-pdf');
                if (viewBtn) {
                    openPdfModal(
                        viewBtn.getAttribute('data-app-id'),
                        viewBtn.getAttribute('data-app-no'),
                        viewBtn.getAttribute('data-student'),
                        viewBtn.getAttribute('data-view-url'),
                        viewBtn.getAttribute('data-db-id'),
                        false
                    );
                    return;
                }

                const editBtn = e.target.closest('.opf-btn-edit-pdf');
                if (editBtn) {
                    openPdfModal(
                        editBtn.getAttribute('data-app-id'),
                        editBtn.getAttribute('data-app-no'),
                        editBtn.getAttribute('data-student'),
                        editBtn.getAttribute('data-view-url'),
                        editBtn.getAttribute('data-db-id'),
                        true
                    );
                }
            });
        }

        // 6. Bulk PDF Downloads Sequencer
        let isDownloadingQueue = false;
        let cancelRequested = false;

        if (bulkDownloadBtn) {
            bulkDownloadBtn.addEventListener('click', function () {
                const checkedBoxes = getSelectedCheckboxes();
                const total = checkedBoxes.length;
                if (total === 0) return;

                const queue = checkedBoxes.map(function (cb) {
                    return {
                        id: cb.value,
                        app_no: cb.getAttribute('data-app-no'),
                        student: cb.getAttribute('data-student')
                    };
                });

                startBulkDownload(queue);
            });
        }

        if (progressCancelBtn) {
            progressCancelBtn.addEventListener('click', function () {
                cancelRequested = true;
                if (progressDesc) progressDesc.textContent = 'Stopping downloads...';
                setTimeout(function () {
                    closeProgressModal();
                    showToast('Bulk PDF download was cancelled');
                }, 500);
            });
        }

        function closeProgressModal() {
            if (progressModal) progressModal.style.display = 'none';
            isDownloadingQueue = false;
            cancelRequested = false;
            const container = document.getElementById('opf-hidden-downloader-container');
            if (container) container.innerHTML = '';
        }

        function startBulkDownload(queue) {
            if (isDownloadingQueue) return;
            isDownloadingQueue = true;
            cancelRequested = false;

            const total = queue.length;
            let current = 0;

            if (progressModal) progressModal.style.display = 'flex';
            updateProgress(0, total, queue[0] ? queue[0].student : '');

            const container = document.getElementById('opf-hidden-downloader-container');

            function downloadNext() {
                if (cancelRequested || current >= total) {
                    if (!cancelRequested) {
                        updateProgress(total, total, 'Complete');
                        setTimeout(function () {
                            closeProgressModal();
                            showToast('All ' + total + ' application PDF(s) downloaded successfully!');
                        }, 800);
                    }
                    return;
                }

                const item = queue[current];
                updateProgress(current, total, item.student);

                // Build download URL with opf_auto_download=1
                const siteUrl = config.site_url || '/';
                const separator = siteUrl.indexOf('?') !== -1 ? '&' : '?';
                const downloadUrl = siteUrl + separator + 'opf_view_app=' + encodeURIComponent(item.app_no) + '&opf_admin=1&opf_auto_download=1';

                // Create clean isolated iframe
                if (container) container.innerHTML = '';
                const iframe = document.createElement('iframe');
                iframe.style.width = '1px';
                iframe.style.height = '1px';
                iframe.style.position = 'absolute';
                iframe.style.left = '-9999px';
                iframe.src = downloadUrl;
                if (container) container.appendChild(iframe);

                let completed = false;

                function stepDone() {
                    if (completed) return;
                    completed = true;
                    window.removeEventListener('message', messageHandler);
                    current++;
                    updateProgress(current, total, item.student);
                    // 1200ms pause between downloads so browser doesn't block multi-download
                    setTimeout(downloadNext, 1200);
                }

                function messageHandler(e) {
                    if (e.data && (e.data.type === 'opf_download_success' || e.data.type === 'opf_download_error')) {
                        stepDone();
                    }
                }

                window.addEventListener('message', messageHandler);

                // Fallback timeout in case postMessage didn't arrive (e.g. 5.5s)
                setTimeout(function () {
                    stepDone();
                }, 5500);
            }

            downloadNext();
        }

        function updateProgress(done, total, currentName) {
            const percent = total > 0 ? Math.round((done / total) * 100) : 0;
            if (progressBarFill) progressBarFill.style.width = percent + '%';
            if (progressCount) progressCount.textContent = done + ' / ' + total + ' Completed';
            if (progressDesc) {
                if (done < total && currentName) {
                    progressDesc.textContent = 'Downloading ' + (done + 1) + ' of ' + total + ': ' + currentName;
                } else {
                    progressDesc.textContent = 'All downloads completed!';
                }
            }
        }

        // 7. Old Apply Tab: Manual Add ID, Excel/CSV Import & Delete Old IDs
        const manualOldForm = document.getElementById('opf-manual-old-id-form');
        const manualOldIdsInput = document.getElementById('opf-manual-old-ids');
        const manualOldNameInput = document.getElementById('opf-manual-old-name');
        const manualOldNoteInput = document.getElementById('opf-manual-old-note');
        const addManualBtn = document.getElementById('opf-btn-add-manual-id');

        const excelOldForm = document.getElementById('opf-excel-old-id-form');
        const excelDropzone = document.getElementById('opf-excel-dropzone');
        const excelFileInput = document.getElementById('opf-old-excel-file');
        const excelFileNameEl = document.getElementById('opf-excel-file-name');
        const uploadExcelBtn = document.getElementById('opf-btn-upload-excel');

        const oldTbody = document.getElementById('opf-old-ids-tbody');
        const oldSelectAll = document.getElementById('opf-old-select-all');
        const oldBulkBar = document.getElementById('opf-old-bulk-bar');
        const oldSelectedCount = document.getElementById('opf-old-selected-count');
        const oldBulkDeleteBtn = document.getElementById('opf-btn-old-bulk-delete');
        const oldDeselectBtn = document.getElementById('opf-btn-old-deselect-all');
        const oldTabBadge = document.getElementById('opf-old-tab-count');

        function reloadOldApplyTab() {
            const url = new URL(window.location.href);
            url.searchParams.set('page', 'orphan-applications');
            url.searchParams.set('tab', 'old');
            url.searchParams.set('old_filter', 'all');
            url.searchParams.delete('s');
            window.location.href = url.toString();
        }

        if (manualOldForm && manualOldIdsInput) {
            manualOldForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const rawIds = manualOldIdsInput.value.trim();
                if (!rawIds) {
                    showToast('Please enter at least one Application ID.', true);
                    manualOldIdsInput.focus();
                    return;
                }

                const origHtml = addManualBtn ? addManualBtn.innerHTML : '';
                if (addManualBtn) {
                    addManualBtn.disabled = true;
                    addManualBtn.innerHTML = 'Adding...';
                }

                const fd = new FormData();
                fd.append('action', 'opf_add_old_ids');
                fd.append('nonce', config.nonce);
                fd.append('old_ids', rawIds);
                fd.append('student_name', manualOldNameInput ? manualOldNameInput.value.trim() : '');
                fd.append('note', manualOldNoteInput ? manualOldNoteInput.value.trim() : '');

                fetch(config.ajax_url, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (addManualBtn) {
                            addManualBtn.disabled = false;
                            addManualBtn.innerHTML = origHtml;
                        }
                        if (res.success) {
                            showToast((res.data && res.data.message) || 'Old ID(s) added!');
                            manualOldIdsInput.value = '';
                            if (manualOldNameInput) manualOldNameInput.value = '';
                            if (manualOldNoteInput) manualOldNoteInput.value = '';
                            if (res.data && res.data.added > 0) {
                                setTimeout(reloadOldApplyTab, 650);
                            }
                        } else {
                            showToast((res.data && res.data.message) || 'Failed to add ID(s).', true);
                        }
                    })
                    .catch(function () {
                        if (addManualBtn) {
                            addManualBtn.disabled = false;
                            addManualBtn.innerHTML = origHtml;
                        }
                        showToast('Server error while adding Old ID.', true);
                    });
            });
        }

        function setSelectedExcelFile(file) {
            if (!excelFileNameEl) return;
            if (file) {
                excelFileNameEl.textContent = file.name;
                if (excelDropzone) excelDropzone.classList.add('has-file');
            } else {
                excelFileNameEl.textContent = 'Click to choose Excel (.xlsx / .xls) or CSV file';
                if (excelDropzone) excelDropzone.classList.remove('has-file');
            }
        }

        if (excelFileInput) {
            excelFileInput.addEventListener('change', function () {
                setSelectedExcelFile(this.files && this.files[0] ? this.files[0] : null);
            });
        }

        if (excelDropzone && excelFileInput) {
            ['dragenter', 'dragover'].forEach(function (evtName) {
                excelDropzone.addEventListener(evtName, function (e) {
                    e.preventDefault();
                    excelDropzone.classList.add('has-file');
                });
            });
            excelDropzone.addEventListener('dragleave', function () {
                if (!excelFileInput.files || !excelFileInput.files.length) {
                    excelDropzone.classList.remove('has-file');
                }
            });
            excelDropzone.addEventListener('drop', function (e) {
                e.preventDefault();
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                    excelFileInput.files = e.dataTransfer.files;
                    setSelectedExcelFile(e.dataTransfer.files[0]);
                }
            });
        }

        if (excelOldForm && excelFileInput) {
            excelOldForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!excelFileInput.files || !excelFileInput.files.length) {
                    showToast('Please select an Excel (.xlsx / .xls) or CSV file.', true);
                    return;
                }

                const origHtml = uploadExcelBtn ? uploadExcelBtn.innerHTML : '';
                if (uploadExcelBtn) {
                    uploadExcelBtn.disabled = true;
                    uploadExcelBtn.innerHTML = 'Importing...';
                }

                const fd = new FormData();
                fd.append('action', 'opf_upload_old_ids_excel');
                fd.append('nonce', config.nonce);
                fd.append('excel_file', excelFileInput.files[0]);

                fetch(config.ajax_url, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (uploadExcelBtn) {
                            uploadExcelBtn.disabled = false;
                            uploadExcelBtn.innerHTML = origHtml;
                        }
                        if (res.success) {
                            showToast((res.data && res.data.message) || 'Excel IDs imported!');
                            excelFileInput.value = '';
                            setSelectedExcelFile(null);
                            if (res.data && res.data.added > 0) {
                                setTimeout(reloadOldApplyTab, 750);
                            }
                        } else {
                            showToast((res.data && res.data.message) || 'Failed to import Excel IDs.', true);
                        }
                    })
                    .catch(function () {
                        if (uploadExcelBtn) {
                            uploadExcelBtn.disabled = false;
                            uploadExcelBtn.innerHTML = origHtml;
                        }
                        showToast('Server error while uploading Excel file.', true);
                    });
            });
        }

        function getSelectedOldCheckboxes() {
            return Array.from(document.querySelectorAll('.opf-old-row-check:checked'));
        }

        function updateOldSelectionState() {
            if (!oldBulkBar) return;
            const checked = getSelectedOldCheckboxes();
            const allBoxes = document.querySelectorAll('.opf-old-row-check');
            const count = checked.length;

            if (oldSelectedCount) oldSelectedCount.textContent = count;
            oldBulkBar.style.display = count > 0 ? 'flex' : 'none';

            if (oldSelectAll && allBoxes.length > 0) {
                oldSelectAll.checked = (count === allBoxes.length);
                oldSelectAll.indeterminate = (count > 0 && count < allBoxes.length);
            } else if (oldSelectAll) {
                oldSelectAll.checked = false;
                oldSelectAll.indeterminate = false;
            }

            allBoxes.forEach(function (cb) {
                const tr = cb.closest('tr');
                if (tr) tr.classList.toggle('is-selected', cb.checked);
            });
        }

        function deleteOldIdsRequest(ids, triggerBtn) {
            if (!ids || !ids.length) return;
            const origHtml = triggerBtn ? triggerBtn.innerHTML : '';
            if (triggerBtn) triggerBtn.disabled = true;

            const fd = new FormData();
            fd.append('action', 'opf_delete_old_ids');
            fd.append('nonce', config.nonce);
            ids.forEach(function (id) { fd.append('ids[]', id); });

            fetch(config.ajax_url, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (triggerBtn) {
                        triggerBtn.disabled = false;
                        triggerBtn.innerHTML = origHtml;
                    }
                    if (res.success) {
                        let submittedRemoved = 0;
                        ids.forEach(function (rowId) {
                            const tr = oldTbody ? oldTbody.querySelector('tr[data-old-row-id="' + rowId + '"]') : null;
                            if (tr) {
                                const delBtnEl = tr.querySelector('.opf-btn-delete-old-id');
                                if (delBtnEl && delBtnEl.getAttribute('data-submitted') === '1') {
                                    submittedRemoved++;
                                }
                                if (tr.parentNode) {
                                    tr.parentNode.removeChild(tr);
                                }
                            }
                        });
                        if (submittedRemoved > 0) {
                            if (oldTabBadge) {
                                const cur = parseInt(oldTabBadge.textContent.replace(/[^0-9]/g, ''), 10) || 0;
                                oldTabBadge.textContent = Math.max(0, cur - submittedRemoved);
                            }
                            if (totalCounter && window.location.search.indexOf('tab=old') !== -1) {
                                const curT = parseInt(totalCounter.textContent.replace(/[^0-9]/g, ''), 10) || 0;
                                totalCounter.textContent = Math.max(0, curT - submittedRemoved) + ' Submitted';
                            }
                        }
                        updateOldSelectionState();
                        if (oldTbody && oldTbody.querySelectorAll('.opf-old-id-row').length === 0) {
                            oldTbody.innerHTML = '<tr class="opf-row-empty"><td colspan="8"><div class="opf-empty-state"><p class="opf-empty-title">No Old Applicant IDs found</p><p class="opf-empty-desc">Add IDs manually or upload an Excel/CSV file above.</p></div></td></tr>';
                        }
                        showToast((res.data && res.data.message) || 'Old ID(s) deleted!');
                    } else {
                        showToast((res.data && res.data.message) || 'Could not delete Old ID(s).', true);
                    }
                })
                .catch(function () {
                    if (triggerBtn) {
                        triggerBtn.disabled = false;
                        triggerBtn.innerHTML = origHtml;
                    }
                    showToast('Server error while deleting Old IDs.', true);
                });
        }

        if (oldSelectAll) {
            oldSelectAll.addEventListener('change', function () {
                const checked = this.checked;
                document.querySelectorAll('.opf-old-row-check').forEach(function (cb) {
                    cb.checked = checked;
                });
                updateOldSelectionState();
            });
        }

        if (oldDeselectBtn) {
            oldDeselectBtn.addEventListener('click', function () {
                document.querySelectorAll('.opf-old-row-check').forEach(function (cb) {
                    cb.checked = false;
                });
                updateOldSelectionState();
            });
        }

        if (oldBulkDeleteBtn) {
            oldBulkDeleteBtn.addEventListener('click', function () {
                const ids = getSelectedOldCheckboxes().map(function (cb) { return cb.value; });
                if (!ids.length) return;
                if (!confirm('Are you sure you want to remove ' + ids.length + ' selected Old ID(s)?')) {
                    return;
                }
                deleteOldIdsRequest(ids, oldBulkDeleteBtn);
            });
        }

        if (oldTbody) {
            oldTbody.addEventListener('change', function (e) {
                if (e.target && e.target.classList.contains('opf-old-row-check')) {
                    updateOldSelectionState();
                }
            });

            oldTbody.addEventListener('click', function (e) {
                const delBtn = e.target.closest('.opf-btn-delete-old-id');
                if (!delBtn) return;
                const rowId = delBtn.getAttribute('data-old-row-id');
                const oldIdText = delBtn.getAttribute('data-old-id');
                const isSubmitted = delBtn.getAttribute('data-submitted') === '1';
                const warnMsg = isSubmitted
                    ? 'Delete Old ID "' + oldIdText + '" and its submitted Old Apply record?'
                    : 'Remove Old ID "' + oldIdText + '" from the allowed list?';
                if (!confirm(warnMsg)) return;
                deleteOldIdsRequest([rowId], delBtn);
            });
        }
    });
})();
