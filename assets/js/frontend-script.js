/**
 * Orphan Project Multi-Step Registration Form JavaScript
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const containers = document.querySelectorAll('.opf-container');

        containers.forEach(function (container) {
            initMultiStepForm(container);
        });
    });

    function initMultiStepForm(container) {
        const form = container.querySelector('.opf-form');
        if (!form) return;

        let currentStep = 1;
        const totalSteps = 4;

        // Elements
        const stepPanes = container.querySelectorAll('.opf-step-pane');
        const stepItems = container.querySelectorAll('.opf-step-item');
        const progressBar = container.querySelector('.opf-progress-bar');
        const alertBox = container.querySelector('.opf-alert-box');
        const successOverlay = container.querySelector('.opf-success-overlay');
        const successAppId = container.querySelector('#opf-success-app-id');
        const submitBtn = container.querySelector('#opf-submit-btn');

        // Navigation Buttons
        const nextBtns = container.querySelectorAll('.opf-btn-next');
        const prevBtns = container.querySelectorAll('.opf-btn-prev');

        // Step Navigation Logic
        function goToStep(step, scroll) {
            if (step < 1 || step > totalSteps) return;

            // Panes
            stepPanes.forEach(function (pane) {
                const paneStep = parseInt(pane.getAttribute('data-step'), 10);
                if (paneStep === step) {
                    pane.classList.add('active');
                } else {
                    pane.classList.remove('active');
                }
            });

            // Wizard indicators
            stepItems.forEach(function (item) {
                const itemStep = parseInt(item.getAttribute('data-step'), 10);
                item.classList.remove('active');
                if (itemStep < step) {
                    item.classList.add('completed');
                } else if (itemStep === step) {
                    item.classList.remove('completed');
                    item.classList.add('active');
                } else {
                    item.classList.remove('completed');
                }
            });

            // Progress bar
            const percent = (step / totalSteps) * 100;
            if (progressBar) {
                progressBar.style.width = percent + '%';
            }

            currentStep = step;

            // Persist current step in sessionStorage and URL hash so reload stays on same step
            try {
                sessionStorage.setItem('opf_form_current_step', step);
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, null, '#step-' + step);
                }
            } catch (e) {}

            // Scroll to form top smoothly (skip scroll on initial page load reload)
            if (scroll !== false) {
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Restore step on page reload if previously stored
        try {
            const hashMatch = window.location.hash.match(/#step-(\d+)/);
            const savedStep = sessionStorage.getItem('opf_form_current_step');
            let initialStep = 1;

            if (hashMatch && hashMatch[1]) {
                const hStep = parseInt(hashMatch[1], 10);
                if (hStep >= 1 && hStep <= totalSteps) {
                    initialStep = hStep;
                }
            } else if (savedStep) {
                const sStep = parseInt(savedStep, 10);
                if (sStep >= 1 && sStep <= totalSteps) {
                    initialStep = sStep;
                }
            }

            if (initialStep > 1) {
                goToStep(initialStep, false);
            }
        } catch (e) {}

        // Validate a given step
        function validateStep(step) {
            const currentPane = container.querySelector('.opf-step-pane[data-step="' + step + '"]');
            if (!currentPane) return true;

            let isValid = true;
            let firstInvalidField = null;

            // Clear previous errors in this step
            const inputs = currentPane.querySelectorAll('.opf-req');
            inputs.forEach(function (input) {
                input.classList.remove('has-error');

                if (input.type === 'checkbox') {
                    if (!input.checked) {
                        isValid = false;
                        input.classList.add('has-error');
                        if (!firstInvalidField) firstInvalidField = input;
                    }
                } else if (input.type === 'tel') {
                    const val = input.value.trim();
                    // Mobile validation: 10 digits
                    if (!val || val.length !== 10 || !/^\d{10}$/.test(val)) {
                        isValid = false;
                        input.classList.add('has-error');
                        if (!firstInvalidField) firstInvalidField = input;
                    }
                } else if (input.name === 'birth_registration_number') {
                    const val = input.value.trim();
                    if (!val || val.length < 10) {
                        isValid = false;
                        input.classList.add('has-error');
                        if (!firstInvalidField) firstInvalidField = input;
                    }
                } else {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('has-error');
                        if (!firstInvalidField) firstInvalidField = input;
                    }
                }
            });

            if (!isValid && firstInvalidField) {
                firstInvalidField.focus();
                firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            return isValid;
        }

        // Next Buttons click
        nextBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const targetStep = parseInt(this.getAttribute('data-next'), 10);
                if (validateStep(currentStep)) {
                    goToStep(targetStep);
                }
            });
        });

        // Prev Buttons click
        prevBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const targetStep = parseInt(this.getAttribute('data-prev'), 10);
                goToStep(targetStep);
            });
        });

        // Clear error class on typing
        container.addEventListener('input', function (e) {
            if (e.target.classList.contains('opf-req')) {
                e.target.classList.remove('has-error');
            }
        });

        // -------------------------------------------------------------
        // Strict Digits-Only Enforcement (Numbers only: 0-9)
        // For Birth Registration, NID, Death Reg, and Phone inputs
        // -------------------------------------------------------------
        const digitsOnlySelector = '.opf-digits-only, input[name="birth_registration_number"], input[name="student_nid"], input[name*="_nid"], input[name*="_reg_no"], .opf-phone-input';

        container.addEventListener('keydown', function (e) {
            if (e.target.matches(digitsOnlySelector)) {
                // Allow control keys: Backspace (8), Tab (9), Enter (13), Escape (27), Delete (46)
                if ([8, 9, 13, 27, 46].indexOf(e.keyCode) !== -1 ||
                    // Allow Ctrl/Cmd shortcuts: A, C, V, X, Z
                    (e.ctrlKey === true || e.metaKey === true) ||
                    // Allow Navigation: Home, End, Left, Right, Up, Down
                    (e.keyCode >= 35 && e.keyCode <= 40)) {
                    return;
                }
                // Stop non-numeric keys (block shift+numbers as symbols, letters, etc.)
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            }
        });

        container.addEventListener('input', function (e) {
            if (e.target.matches(digitsOnlySelector)) {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            }
        });

        container.addEventListener('paste', function (e) {
            if (e.target.matches(digitsOnlySelector)) {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text');
                const cleanDigits = pasteData.replace(/[^0-9]/g, '');
                const maxLen = parseInt(e.target.getAttribute('maxlength'), 10) || 17;
                e.target.value = (e.target.value + cleanDigits).slice(0, maxLen);
                e.target.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });

        // -------------------------------------------------------------
        // Auto Calculate Age from Date of Birth
        // -------------------------------------------------------------
        const dobInput = container.querySelector('.opf-dob-field');
        const ageInput = container.querySelector('.opf-age-field');
        if (dobInput && ageInput) {
            dobInput.addEventListener('change', function () {
                if (this.value) {
                    const birthDate = new Date(this.value);
                    const today = new Date();
                    let age = today.getFullYear() - birthDate.getFullYear();
                    const m = today.getMonth() - birthDate.getMonth();
                    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                        age--;
                    }
                    if (age >= 0 && age <= 100) {
                        ageInput.value = age;
                    }
                }
            });
        }

        // -------------------------------------------------------------
        // Father Deceased Toggle
        // -------------------------------------------------------------
        const fatherDeceasedChk = container.querySelector('#opf-father-deceased-chk');
        const fatherDeceasedBox = container.querySelector('#opf-father-deceased-details');
        if (fatherDeceasedChk && fatherDeceasedBox) {
            fatherDeceasedChk.addEventListener('change', function () {
                fatherDeceasedBox.style.display = this.checked ? 'grid' : 'none';
            });
        }

        // -------------------------------------------------------------
        // Mother Deceased Toggle
        // -------------------------------------------------------------
        const motherDeceasedChk = container.querySelector('#opf-mother-deceased-chk');
        const motherDeceasedBox = container.querySelector('#opf-mother-deceased-details');
        if (motherDeceasedChk && motherDeceasedBox) {
            motherDeceasedChk.addEventListener('change', function () {
                motherDeceasedBox.style.display = this.checked ? 'grid' : 'none';
            });
        }

        // -------------------------------------------------------------
        // Separate Guardian Toggle
        // -------------------------------------------------------------
        const guardianChk = container.querySelector('#opf-separate-guardian-chk');
        const guardianBox = container.querySelector('#opf-guardian-details');
        if (guardianChk && guardianBox) {
            guardianChk.addEventListener('change', function () {
                guardianBox.style.display = this.checked ? 'block' : 'none';
            });
        }

        // -------------------------------------------------------------
        // "Same as Present Address" Logic
        // -------------------------------------------------------------
        const sameAddrChk = container.querySelector('#opf-same-as-present');
        if (sameAddrChk) {
            const addrFields = ['house', 'village', 'post-office', 'police-station', 'district', 'country'];

            function syncAddresses() {
                if (sameAddrChk.checked) {
                    addrFields.forEach(function (field) {
                        const src = container.querySelector('#opf-present-' + field);
                        const dst = container.querySelector('#opf-permanent-' + field);
                        if (src && dst) {
                            dst.value = src.value;
                            dst.classList.remove('has-error');
                        }
                    });
                }
            }

            sameAddrChk.addEventListener('change', syncAddresses);

            addrFields.forEach(function (field) {
                const src = container.querySelector('#opf-present-' + field);
                if (src) {
                    src.addEventListener('input', function () {
                        if (sameAddrChk.checked) {
                            const dst = container.querySelector('#opf-permanent-' + field);
                            if (dst) dst.value = src.value;
                        }
                    });
                }
            });
        }

        // -------------------------------------------------------------
        // Digital Signature Canvas
        // -------------------------------------------------------------
        const canvas = container.querySelector('#opf-signature-pad');
        const sigDataInput = container.querySelector('#opf-signature-data');
        const clearSigBtn = container.querySelector('#opf-clear-signature');

        if (canvas) {
            const ctx = canvas.getContext('2d');
            let isDrawing = false;
            let hasDrawn = false;

            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            function getPos(e) {
                const rect = canvas.getBoundingClientRect();
                const scaleX = canvas.width / rect.width;
                const scaleY = canvas.height / rect.height;

                let clientX = e.clientX;
                let clientY = e.clientY;

                if (e.touches && e.touches.length > 0) {
                    clientX = e.touches[0].clientX;
                    clientY = e.touches[0].clientY;
                }

                return {
                    x: (clientX - rect.left) * scaleX,
                    y: (clientY - rect.top) * scaleY
                };
            }

            function startDrawing(e) {
                e.preventDefault();
                isDrawing = true;
                const pos = getPos(e);
                ctx.beginPath();
                ctx.moveTo(pos.x, pos.y);
            }

            function draw(e) {
                if (!isDrawing) return;
                e.preventDefault();
                hasDrawn = true;
                const pos = getPos(e);
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
            }

            function stopDrawing(e) {
                if (isDrawing) {
                    isDrawing = false;
                    if (sigDataInput && hasDrawn) {
                        sigDataInput.value = canvas.toDataURL('image/png');
                    }
                }
            }

            // Mouse events
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            window.addEventListener('mouseup', stopDrawing);

            // Touch events
            canvas.addEventListener('touchstart', startDrawing, { passive: false });
            canvas.addEventListener('touchmove', draw, { passive: false });
            window.addEventListener('touchend', stopDrawing);

            // Clear button
            if (clearSigBtn) {
                clearSigBtn.addEventListener('click', function () {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    if (sigDataInput) sigDataInput.value = '';
                    hasDrawn = false;
                });
            }
        }

        // -------------------------------------------------------------
        // AJAX Form Submission
        // -------------------------------------------------------------
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Validate step 4
            if (!validateStep(4)) {
                return;
            }

            // Sync signature before submit
            if (canvas && sigDataInput) {
                sigDataInput.value = canvas.toDataURL('image/png');
            }

            // Button loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                const textSpan = submitBtn.querySelector('.opf-btn-text-content');
                const spinner = submitBtn.querySelector('.opf-spinner');
                if (textSpan) textSpan.textContent = 'Submitting...';
                if (spinner) spinner.style.display = 'inline-block';
            }

            if (alertBox) {
                alertBox.style.display = 'none';
                alertBox.className = 'opf-alert-box';
            }

            const formData = new FormData(form);

            const ajaxUrl = (typeof orphan_form_obj !== 'undefined' && orphan_form_obj.ajax_url)
                ? orphan_form_obj.ajax_url
                : '/wp-admin/admin-ajax.php';

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (res) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    const textSpan = submitBtn.querySelector('.opf-btn-text-content');
                    const spinner = submitBtn.querySelector('.opf-spinner');
                    if (textSpan) textSpan.textContent = 'Submit Application';
                    if (spinner) spinner.style.display = 'none';
                }

                if (res.success) {
                    try {
                        sessionStorage.removeItem('opf_form_current_step');
                        if (window.history && window.history.replaceState) {
                            window.history.replaceState(null, null, window.location.pathname + window.location.search);
                        }
                    } catch (e) {}

                    // Redirect immediately to the PDF-format view page
                    if (res.data && res.data.view_url) {
                        window.location.href = res.data.view_url;
                        return;
                    }

                    if (successAppId) {
                        successAppId.textContent = res.data.application_no || 'SAP-' + Date.now();
                    }
                    if (successOverlay) {
                        successOverlay.style.display = 'flex';
                    }
                } else {
                    const msg = (res.data && res.data.message) ? res.data.message : 'An error occurred while submitting.';
                    if (alertBox) {
                        alertBox.textContent = msg;
                        alertBox.classList.add('opf-alert-error');
                        alertBox.style.display = 'block';
                        alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            })
            .catch(function (err) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    const textSpan = submitBtn.querySelector('.opf-btn-text-content');
                    const spinner = submitBtn.querySelector('.opf-spinner');
                    if (textSpan) textSpan.textContent = 'Submit Application';
                    if (spinner) spinner.style.display = 'none';
                }

                if (alertBox) {
                    alertBox.textContent = 'Network or server error. Please try again later.';
                    alertBox.classList.add('opf-alert-error');
                    alertBox.style.display = 'block';
                }
            });
        });

        // Print Application
        const printBtn = container.querySelector('#opf-print-btn');
        if (printBtn) {
            printBtn.addEventListener('click', function () {
                window.print();
            });
        }

        // Submit Another Application
        const newAppBtn = container.querySelector('#opf-new-app-btn');
        if (newAppBtn) {
            newAppBtn.addEventListener('click', function () {
                try {
                    sessionStorage.removeItem('opf_form_current_step');
                    if (window.history && window.history.replaceState) {
                        window.history.replaceState(null, null, window.location.pathname + window.location.search);
                    }
                } catch (e) {}
                if (successOverlay) {
                    successOverlay.style.display = 'none';
                }
                form.reset();
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                }
                goToStep(1, false);
            });
        }

        // =========================================================
        // Mode Switcher (Apply Online vs Upload Form)
        // =========================================================
        const modeBtnOnline = container.querySelector('#opf-mode-btn-online');
        const modeBtnUpload = container.querySelector('#opf-mode-btn-upload');
        const onlineModeContainer = container.querySelector('#opf-online-mode');
        const uploadModeContainer = container.querySelector('#opf-upload-mode');

        function switchMode(mode) {
            if (mode === 'upload') {
                if (modeBtnUpload) modeBtnUpload.classList.add('active');
                if (modeBtnOnline) modeBtnOnline.classList.remove('active');
                if (onlineModeContainer) onlineModeContainer.style.display = 'none';
                if (uploadModeContainer) uploadModeContainer.style.display = 'block';
            } else {
                if (modeBtnOnline) modeBtnOnline.classList.add('active');
                if (modeBtnUpload) modeBtnUpload.classList.remove('active');
                if (uploadModeContainer) uploadModeContainer.style.display = 'none';
                if (onlineModeContainer) onlineModeContainer.style.display = 'block';
            }
        }

        if (modeBtnOnline) {
            modeBtnOnline.addEventListener('click', function () {
                switchMode('online');
            });
        }

        if (modeBtnUpload) {
            modeBtnUpload.addEventListener('click', function () {
                switchMode('upload');
            });
        }

        // Applicant Type Switcher (New vs Existing)
        const optCardNew = container.querySelector('#opf-type-opt-new');
        const optCardExisting = container.querySelector('#opf-type-opt-existing');
        const fieldsNew = container.querySelector('#opf-upload-fields-new');
        const fieldsExisting = container.querySelector('#opf-upload-fields-existing');
        const radioTypeInputs = container.querySelectorAll('input[name="applicant_type"]');

        radioTypeInputs.forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (this.value === 'existing') {
                    if (optCardExisting) optCardExisting.classList.add('active');
                    if (optCardNew) optCardNew.classList.remove('active');
                    if (fieldsExisting) fieldsExisting.style.display = 'block';
                    if (fieldsNew) fieldsNew.style.display = 'none';
                } else {
                    if (optCardNew) optCardNew.classList.add('active');
                    if (optCardExisting) optCardExisting.classList.remove('active');
                    if (fieldsNew) fieldsNew.style.display = 'block';
                    if (fieldsExisting) fieldsExisting.style.display = 'none';
                }
            });
        });

        // File Dropzone & Preview Handling
        const dropzoneArea = container.querySelector('#opf-dropzone-area');
        const fileInput = container.querySelector('#opf-upload-file-input');
        const dropzonePlaceholder = container.querySelector('#opf-dropzone-placeholder');
        const fileSelectedCard = container.querySelector('#opf-file-selected-card');
        const fileNameEl = container.querySelector('#opf-selected-file-name');
        const fileSizeEl = container.querySelector('#opf-selected-file-size');
        const clearFileBtn = container.querySelector('#opf-btn-clear-file');
        const fileErrEl = container.querySelector('#opf-upload-file-err');

        function formatBytes(bytes) {
            if (!bytes || bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function updateFilePreview(file) {
            if (file) {
                if (fileNameEl) fileNameEl.textContent = file.name;
                if (fileSizeEl) fileSizeEl.textContent = formatBytes(file.size);
                if (dropzonePlaceholder) dropzonePlaceholder.style.display = 'none';
                if (fileSelectedCard) fileSelectedCard.style.display = 'flex';
                if (fileErrEl) fileErrEl.style.display = 'none';
            } else {
                if (fileInput) fileInput.value = '';
                if (dropzonePlaceholder) dropzonePlaceholder.style.display = 'flex';
                if (fileSelectedCard) fileSelectedCard.style.display = 'none';
            }
        }

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    updateFilePreview(this.files[0]);
                }
            });
        }

        if (clearFileBtn) {
            clearFileBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                updateFilePreview(null);
            });
        }

        if (dropzoneArea) {
            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropzoneArea.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    dropzoneArea.classList.add('dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(function (eventName) {
                dropzoneArea.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    dropzoneArea.classList.remove('dragover');
                }, false);
            });

            dropzoneArea.addEventListener('drop', function (e) {
                const dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length) {
                    fileInput.files = dt.files;
                    updateFilePreview(dt.files[0]);
                }
            }, false);
        }

        // =========================================================
        // Custom Centered Modal Alert
        // =========================================================
        const modalBackdrop = container.querySelector('#opf-alert-modal') || document.getElementById('opf-alert-modal');
        if (modalBackdrop && modalBackdrop.parentElement !== document.body) {
            document.body.appendChild(modalBackdrop);
        }
        const modalCloseBtn = modalBackdrop ? modalBackdrop.querySelector('#opf-modal-close') : null;
        const modalOkBtn = modalBackdrop ? modalBackdrop.querySelector('#opf-modal-btn-ok') : null;
        const modalTitle = modalBackdrop ? modalBackdrop.querySelector('#opf-modal-title') : null;
        const modalMsg = modalBackdrop ? modalBackdrop.querySelector('#opf-modal-msg') : null;
        const modalIconWrap = modalBackdrop ? modalBackdrop.querySelector('#opf-modal-icon-wrap') : null;

        function showCustomAlert(title, message, type) {
            if (!modalBackdrop) return;
            if (modalTitle) modalTitle.textContent = title || 'Notice';
            if (modalMsg) modalMsg.textContent = message || '';

            if (modalIconWrap) {
                if (type === 'warning') {
                    modalIconWrap.className = 'opf-modal-icon-wrap opf-modal-icon-warning';
                    modalIconWrap.innerHTML = '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
                } else {
                    modalIconWrap.className = 'opf-modal-icon-wrap opf-modal-icon-error';
                    modalIconWrap.innerHTML = '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
                }
            }

            modalBackdrop.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeCustomAlert() {
            if (modalBackdrop) {
                modalBackdrop.classList.remove('show');
                document.body.style.overflow = '';
            }
        }

        if (modalCloseBtn) {
            modalCloseBtn.addEventListener('click', closeCustomAlert);
        }
        if (modalOkBtn) {
            modalOkBtn.addEventListener('click', closeCustomAlert);
        }
        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', function (e) {
                if (e.target === modalBackdrop) {
                    closeCustomAlert();
                }
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modalBackdrop && modalBackdrop.classList.contains('show')) {
                closeCustomAlert();
            }
        });

        // Upload Form AJAX Submission
        const uploadForm = container.querySelector('#opf-upload-form');
        const uploadSubmitBtn = container.querySelector('#opf-upload-btn-submit');
        const uploadSuccessView = container.querySelector('#opf-upload-success-view');
        const uploadSuccessId = container.querySelector('#opf-upload-success-id');
        const uploadSuccessFileLink = container.querySelector('#opf-upload-success-link');
        const uploadResetBtn = container.querySelector('#opf-upload-reset-btn');

        if (uploadForm) {
            uploadForm.addEventListener('submit', function (e) {
                e.preventDefault();

                // Clear previous errors
                uploadForm.querySelectorAll('.opf-err-msg').forEach(function (el) {
                    el.style.display = 'none';
                });
                uploadForm.querySelectorAll('.opf-has-err').forEach(function (el) {
                    el.classList.remove('opf-has-err');
                });

                let hasError = false;
                const checkedRadio = uploadForm.querySelector('input[name="applicant_type"]:checked');
                const applicantType = checkedRadio ? checkedRadio.value : 'new';

                function markFieldErr(input) {
                    if (!input) return;
                    input.classList.add('opf-has-err');
                    const err = input.parentElement ? input.parentElement.querySelector('.opf-err-msg') : null;
                    if (err) err.style.display = 'block';
                }

                if (applicantType === 'existing') {
                    const lookupInput = uploadForm.querySelector('input[name="existing_app_lookup"]');
                    const nameInput = uploadForm.querySelector('input[name="existing_student_name"]');
                    const mobileInput = uploadForm.querySelector('input[name="existing_mobile"]');

                    if (!lookupInput || !lookupInput.value.trim()) { markFieldErr(lookupInput); hasError = true; }
                    if (!nameInput || !nameInput.value.trim()) { markFieldErr(nameInput); hasError = true; }
                    if (!mobileInput || !mobileInput.value.trim() || mobileInput.value.trim().length < 9) { markFieldErr(mobileInput); hasError = true; }
                } else {
                    const fnInput = uploadForm.querySelector('input[name="student_first_name"]');
                    const snInput = uploadForm.querySelector('input[name="student_surname"]');
                    const mobInput = uploadForm.querySelector('input[name="student_mobile"]');
                    const brInput = uploadForm.querySelector('input[name="birth_registration_number"]');
                    const distInput = uploadForm.querySelector('input[name="district"]');

                    if (!fnInput || !fnInput.value.trim()) { markFieldErr(fnInput); hasError = true; }
                    if (!snInput || !snInput.value.trim()) { markFieldErr(snInput); hasError = true; }
                    if (!mobInput || !mobInput.value.trim() || mobInput.value.trim().length < 9) { markFieldErr(mobInput); hasError = true; }
                    if (!brInput || !brInput.value.trim() || brInput.value.trim().length < 10) { markFieldErr(brInput); hasError = true; }
                    if (!distInput || !distInput.value.trim()) { markFieldErr(distInput); hasError = true; }
                }

                // Check file selected
                if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                    if (fileErrEl) fileErrEl.style.display = 'block';
                    hasError = true;
                }

                if (hasError) {
                    if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                        showCustomAlert('Document Required', 'Please choose or drag & drop a PDF or image of your completed application form.', 'warning');
                    }
                    return;
                }

                const formData = new FormData(uploadForm);
                const btnContent = uploadSubmitBtn ? uploadSubmitBtn.querySelector('.opf-btn-text-content') : null;
                const spinner = uploadSubmitBtn ? uploadSubmitBtn.querySelector('.opf-spinner') : null;
                const originalBtnHtml = btnContent ? btnContent.innerHTML : '';

                if (uploadSubmitBtn) {
                    uploadSubmitBtn.disabled = true;
                    if (btnContent) btnContent.textContent = 'Uploading Form...';
                    if (spinner) spinner.style.display = 'inline-block';
                }

                fetch(orphan_form_obj.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (uploadSubmitBtn) {
                            uploadSubmitBtn.disabled = false;
                            if (btnContent) btnContent.innerHTML = originalBtnHtml;
                            if (spinner) spinner.style.display = 'none';
                        }

                        if (data.success) {
                            uploadForm.style.display = 'none';
                            if (uploadSuccessView) uploadSuccessView.style.display = 'flex';
                            if (uploadSuccessId) uploadSuccessId.textContent = '#' + (data.data.id || data.data.application_no);
                            if (uploadSuccessFileLink && data.data.file_url) {
                                uploadSuccessFileLink.href = data.data.file_url;
                            }
                        } else {
                            showCustomAlert(
                                'Application Not Found',
                                data.data && data.data.message ? data.data.message : 'Invalid details provided. Please check your information or select "New Applicant".',
                                'error'
                            );
                        }
                    })
                    .catch(function (err) {
                        if (uploadSubmitBtn) {
                            uploadSubmitBtn.disabled = false;
                            if (btnContent) btnContent.innerHTML = originalBtnHtml;
                            if (spinner) spinner.style.display = 'none';
                        }
                        showCustomAlert(
                            'Connection Error',
                            'Network error during upload. Please check your internet connection and try again.',
                            'warning'
                        );
                        console.error('Upload error:', err);
                    });
            });
        }

        if (uploadResetBtn) {
            uploadResetBtn.addEventListener('click', function () {
                if (uploadForm) {
                    uploadForm.reset();
                    updateFilePreview(null);
                    uploadForm.style.display = 'block';
                }
                if (uploadSuccessView) {
                    uploadSuccessView.style.display = 'none';
                }
            });
        }
    }
})();
