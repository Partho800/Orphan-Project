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
        const tableBody = document.querySelector('#opf-applications-table tbody');
        const totalCounter = document.getElementById('opf-total-counter');

        // Modal Elements
        const pdfModal = document.getElementById('opf-pdf-modal');
        const modalCloseBtn = document.getElementById('opf-modal-close-btn');
        const modalIframe = document.getElementById('opf-modal-iframe');
        const modalBadge = document.getElementById('opf-modal-badge');
        const modalHeading = document.getElementById('opf-modal-student-name');
        const modalAppCode = document.getElementById('opf-modal-app-code');
        const modalNewTabLink = document.getElementById('opf-modal-newtab-link');
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

        // 3. Reliable Status Update via AJAX
        if (tableBody) {
            tableBody.addEventListener('change', function (e) {
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

        // 5. In-Page PDF Modal Popup
        function openPdfModal(appId, appNo, studentName, viewUrl) {
            if (!pdfModal) return;

            if (modalBadge) modalBadge.textContent = 'Application #' + appId;
            if (modalHeading) modalHeading.textContent = studentName || 'Application View';
            if (modalAppCode) modalAppCode.textContent = appNo;
            if (modalNewTabLink) modalNewTabLink.href = viewUrl;

            if (modalLoading) modalLoading.style.display = 'flex';
            if (modalIframe) {
                modalIframe.style.opacity = '0';
                modalIframe.src = viewUrl;
                modalIframe.onload = function () {
                    if (modalLoading) modalLoading.style.display = 'none';
                    modalIframe.style.opacity = '1';
                };
            }

            pdfModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closePdfModal() {
            if (!pdfModal) return;
            pdfModal.style.display = 'none';
            document.body.style.overflow = '';
            if (modalIframe) modalIframe.src = 'about:blank';
        }

        if (modalCloseBtn) modalCloseBtn.addEventListener('click', closePdfModal);

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

        // Table Event Delegation for "View PDF" Button
        if (tableBody) {
            tableBody.addEventListener('click', function (e) {
                const btn = e.target.closest('.opf-btn-view-pdf');
                if (btn) {
                    const appId = btn.getAttribute('data-app-id');
                    const appNo = btn.getAttribute('data-app-no');
                    const studentName = btn.getAttribute('data-student');
                    const viewUrl = btn.getAttribute('data-view-url');
                    openPdfModal(appId, appNo, studentName, viewUrl);
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
    });
})();
