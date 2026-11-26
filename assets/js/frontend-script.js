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
        const totalSteps = 5;

        // Elements
        const shell = container.closest('.opf-wizard-shell') || container;
        const appModeInput = container.querySelector('#opf-application-mode');
        const oldIdWrap = container.querySelector('#opf-old-id-field-wrap');
        const oldIdInput = container.querySelector('#opf-old-application-id');
        const oldIdErr = container.querySelector('#opf-old-id-err');
        const stepPanes = container.querySelectorAll('.opf-step-pane');
        const stepItems = container.querySelectorAll('.opf-step-item');
        const sideStepItems = shell.querySelectorAll('.opf-side-step-item');
        const progressBar = container.querySelector('.opf-progress-bar');
        const alertBox = container.querySelector('.opf-alert-box');
        const successOverlay = container.querySelector('.opf-success-overlay');
        const successAppId = container.querySelector('#opf-success-app-id');
        const submitBtn = container.querySelector('#opf-submit-btn');

        const ajaxUrl = (typeof orphan_form_obj !== 'undefined' && orphan_form_obj.ajax_url)
            ? orphan_form_obj.ajax_url
            : '/wp-admin/admin-ajax.php';

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

            // Wizard indicators & Left Sidebar steps
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

            sideStepItems.forEach(function (item) {
                const itemStep = parseInt(item.getAttribute('data-step'), 10);
                const subEl = item.querySelector('.opf-side-step-sub');
                item.classList.remove('active');
                if (itemStep < step) {
                    item.classList.add('completed');
                    if (subEl) subEl.textContent = 'Completed';
                } else if (itemStep === step) {
                    item.classList.remove('completed');
                    item.classList.add('active');
                    if (subEl) subEl.textContent = 'Current Step';
                } else {
                    item.classList.remove('completed');
                    if (subEl) subEl.textContent = 'Step ' + itemStep;
                }
            });

            // Progress bar
            const percent = Math.round((step / totalSteps) * 100);
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

        // Initialize Step 1 indicator labels immediately
        if (sideStepItems.length) {
            const firstSub = sideStepItems[0].querySelector('.opf-side-step-sub');
            if (firstSub) firstSub.textContent = 'Current Step';
        }

        function proceedWithStepValidation(targetStep, triggerBtn) {
            if (!targetStep || targetStep === currentStep) return;

            if (targetStep < currentStep) {
                goToStep(targetStep);
                return;
            }

            for (let s = currentStep; s < targetStep; s++) {
                if (!validateStep(s)) {
                    if (s !== currentStep) goToStep(s);
                    return;
                }
            }

            // If in Old Application mode and leaving Step 1, verify ID exists in DB first
            if (appModeInput && appModeInput.value === 'old' && currentStep === 1 && oldIdInput) {
                const enteredId = oldIdInput.value.trim();
                const nonceEl = form.querySelector('input[name="orphan_form_nonce"]');
                const origText = triggerBtn ? triggerBtn.textContent : '';
                if (triggerBtn) {
                    triggerBtn.disabled = true;
                    triggerBtn.textContent = 'Checking ID...';
                }

                const fd = new FormData();
                fd.append('action', 'opf_verify_old_id');
                fd.append('nonce', nonceEl ? nonceEl.value : '');
                fd.append('old_application_id', enteredId);

                fetch(ajaxUrl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (triggerBtn) {
                            triggerBtn.disabled = false;
                            triggerBtn.textContent = origText;
                        }
                        if (res && res.success) {
                            oldIdInput.classList.remove('has-error');
                            goToStep(targetStep);
                        } else {
                            const msg = (res && res.data && res.data.message)
                                ? res.data.message
                                : 'Invalid ID. Please enter a valid ID.';
                            oldIdInput.classList.add('has-error');
                            if (oldIdErr) {
                                oldIdErr.textContent = msg;
                                oldIdErr.style.display = 'block';
                            }
                            oldIdInput.focus();
                            oldIdInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            showCustomAlert('Invalid ID', 'Please enter a valid ID.', 'error');
                        }
                    })
                    .catch(function () {
                        if (triggerBtn) {
                            triggerBtn.disabled = false;
                            triggerBtn.textContent = origText;
                        }
                        showCustomAlert('Connection Error', 'Could not verify Application ID. Please try again.', 'warning');
                    });
                return;
            }

            goToStep(targetStep);
        }

        // Click / Keyboard handler for Left Sidebar Step Items
        sideStepItems.forEach(function (item) {
            function activateSideStep() {
                const targetStep = parseInt(item.getAttribute('data-step'), 10);
                proceedWithStepValidation(targetStep, null);
            }
            item.addEventListener('click', activateSideStep);
            item.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    activateSideStep();
                }
            });
        });

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

        window.addEventListener('hashchange', function () {
            try {
                const m = window.location.hash.match(/#step-(\d+)/);
                if (m && m[1]) {
                    const hStep = parseInt(m[1], 10);
                    if (hStep >= 1 && hStep <= totalSteps && hStep !== currentStep) {
                        goToStep(hStep, false);
                    }
                }
            } catch (e) {}
        });

        // Validate a given step
        function validateStep(step) {
            const currentPane = container.querySelector('.opf-step-pane[data-step="' + step + '"]');
            if (!currentPane) return true;

            let isValid = true;
            let firstInvalidField = null;

            // Clear previous errors in this step
            currentPane.querySelectorAll('.opf-img-upload-card').forEach(function (card) {
                card.classList.remove('has-error');
            });

            const inputs = currentPane.querySelectorAll('.opf-req');
            inputs.forEach(function (input) {
                input.classList.remove('has-error');

                if (input.classList.contains('opf-image-req')) {
                    if (!input.value.trim()) {
                        isValid = false;
                        const card = input.closest('.opf-img-upload-card');
                        if (card) {
                            card.classList.add('has-error');
                            if (!firstInvalidField) firstInvalidField = card;
                        }
                    }
                } else if (input.type === 'checkbox') {
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
                if (typeof firstInvalidField.focus === 'function') {
                    firstInvalidField.focus();
                }
                firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            return isValid;
        }

        // Next Buttons click
        nextBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const targetStep = parseInt(this.getAttribute('data-next'), 10);
                proceedWithStepValidation(targetStep, this);
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
        // Auto Calculate Age (Years, Months, Days) from Date of Birth
        // -------------------------------------------------------------
        const dobInput = container.querySelector('.opf-dob-field');
        const ageInput = container.querySelector('.opf-age-field');
        if (dobInput && ageInput) {
            function updateCalculatedAge() {
                const val = dobInput.value;
                if (!val) {
                    ageInput.value = '';
                    return;
                }
                const parts = val.split('-');
                if (parts.length !== 3) return;

                const birthYear = parseInt(parts[0], 10);
                const birthMonth = parseInt(parts[1], 10) - 1;
                const birthDay = parseInt(parts[2], 10);
                const birthDate = new Date(birthYear, birthMonth, birthDay);
                if (isNaN(birthDate.getTime())) return;

                const now = new Date();
                const todayDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                if (birthDate > todayDate) {
                    ageInput.value = '';
                    return;
                }

                let years = todayDate.getFullYear() - birthYear;
                let months = todayDate.getMonth() - birthMonth;
                let days = todayDate.getDate() - birthDay;

                if (days < 0) {
                    months--;
                    const prevMonthDays = new Date(todayDate.getFullYear(), todayDate.getMonth(), 0).getDate();
                    days += prevMonthDays;
                }
                if (months < 0) {
                    years--;
                    months += 12;
                }

                if (years >= 0 && years <= 120) {
                    const yLabel = years === 1 ? 'Year' : 'Years';
                    const mLabel = months === 1 ? 'Month' : 'Months';
                    const dLabel = days === 1 ? 'Day' : 'Days';
                    ageInput.value = years + ' ' + yLabel + ', ' + months + ' ' + mLabel + ', ' + days + ' ' + dLabel;
                }
            }

            dobInput.addEventListener('change', updateCalculatedAge);
            dobInput.addEventListener('input', updateCalculatedAge);
            if (dobInput.value) {
                updateCalculatedAge();
            }
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

            // Validate steps 1..4 cleanly before submitting
            for (let s = 1; s <= totalSteps; s++) {
                if (!validateStep(s)) {
                    if (s !== currentStep) {
                        goToStep(s, false);
                        validateStep(s);
                    }
                    return;
                }
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
            if (typeof croppedImagesState === 'object') {
                if (croppedImagesState.signature_image && croppedImagesState.signature_image.blob) {
                    formData.append('signature_image_file', croppedImagesState.signature_image.blob, 'signature.png');
                }
                if (croppedImagesState.short_photo && croppedImagesState.short_photo.blob) {
                    formData.append('short_photo_file', croppedImagesState.short_photo.blob, 'short_photo.jpg');
                }
                if (croppedImagesState.half_photo && croppedImagesState.half_photo.blob) {
                    formData.append('half_photo_file', croppedImagesState.half_photo.blob, 'half_photo.jpg');
                }
            }

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
                        successAppId.textContent = '#' + (res.data.application_no || res.data.id);
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
                    showCustomAlert('Submission Error', msg, 'error');
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
        // Top 2-Tab Switcher (New Application vs Old Application)
        // =========================================================
        const modeBtnOnline = container.querySelector('#opf-mode-btn-online');
        const modeBtnUpload = container.querySelector('#opf-mode-btn-upload');

        function switchMode(mode) {
            if (mode === 'upload' || mode === 'old') {
                if (modeBtnUpload) {
                    modeBtnUpload.classList.add('active');
                    modeBtnUpload.setAttribute('aria-selected', 'true');
                }
                if (modeBtnOnline) {
                    modeBtnOnline.classList.remove('active');
                    modeBtnOnline.setAttribute('aria-selected', 'false');
                }
                if (appModeInput) appModeInput.value = 'old';
                if (oldIdWrap) oldIdWrap.style.display = 'block';
                if (oldIdInput) {
                    oldIdInput.classList.add('opf-req');
                    oldIdInput.setAttribute('required', 'required');
                }
                if (currentStep !== 1 && (!oldIdInput || !oldIdInput.value.trim())) {
                    goToStep(1, false);
                }
            } else {
                if (modeBtnOnline) {
                    modeBtnOnline.classList.add('active');
                    modeBtnOnline.setAttribute('aria-selected', 'true');
                }
                if (modeBtnUpload) {
                    modeBtnUpload.classList.remove('active');
                    modeBtnUpload.setAttribute('aria-selected', 'false');
                }
                if (appModeInput) appModeInput.value = 'new';
                if (oldIdWrap) oldIdWrap.style.display = 'none';
                if (oldIdInput) {
                    oldIdInput.classList.remove('opf-req', 'has-error');
                    oldIdInput.removeAttribute('required');
                }
                if (oldIdErr) {
                    oldIdErr.style.display = '';
                }
            }
        }

        if (modeBtnOnline) {
            modeBtnOnline.addEventListener('click', function () {
                switchMode('new');
            });
        }

        if (modeBtnUpload) {
            modeBtnUpload.addEventListener('click', function () {
                switchMode('old');
            });
        }

        // Auto-switch mode or pre-fill from URL query / hash
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const opfMode = urlParams.get('opf_mode') || (window.location.hash === '#old' || window.location.hash === '#upload' ? 'old' : null);
            const prefillAppId = urlParams.get('app_id');

            if (opfMode === 'upload' || opfMode === 'old') {
                switchMode('old');

                if (prefillAppId && oldIdInput) {
                    oldIdInput.value = prefillAppId;
                }
            }
        } catch (e) {}

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

        // =========================================================
        // Step 5: Built-In Canvas Image Upload + Interactive Cropper
        // =========================================================
        const croppedImagesState = {
            signature_image: { img: null, blob: null, rotation: 0 },
            short_photo: { img: null, blob: null, rotation: 0 },
            half_photo: { img: null, blob: null, rotation: 0 }
        };

        const cropModal = document.getElementById('opf-crop-modal');
        if (cropModal && cropModal.parentElement !== document.body) {
            document.body.appendChild(cropModal);
        }

        const cropModalTitle = cropModal ? cropModal.querySelector('#opf-crop-modal-title') : null;
        const cropCanvas = cropModal ? cropModal.querySelector('#opf-crop-canvas') : null;
        const cropBox = cropModal ? cropModal.querySelector('#opf-crop-box') : null;
        const cropCloseBtn = cropModal ? cropModal.querySelector('#opf-crop-close') : null;
        const cropCancelBtn = cropModal ? cropModal.querySelector('#opf-crop-cancel-btn') : null;
        const cropSaveBtn = cropModal ? cropModal.querySelector('#opf-crop-save-btn') : null;
        const cropRotateBtn = cropModal ? cropModal.querySelector('#opf-crop-rotate-btn') : null;
        const cropResetBtn = cropModal ? cropModal.querySelector('#opf-crop-reset-btn') : null;
        const cropRatioLockBtn = cropModal ? cropModal.querySelector('#opf-crop-ratio-lock') : null;
        const cropRatioFreeBtn = cropModal ? cropModal.querySelector('#opf-crop-ratio-free') : null;

        let activeCropField = null;
        let activeCropAspect = null;
        let lockAspect = true;
        let rotCanvas = null; // Holds full-resolution rotated source canvas
        let dispW = 0;
        let dispH = 0;
        let boxRect = { x: 0, y: 0, w: 100, h: 100 };

        function syncCropBoxDOM() {
            if (!cropBox) return;
            cropBox.style.left = boxRect.x + 'px';
            cropBox.style.top = boxRect.y + 'px';
            cropBox.style.width = boxRect.w + 'px';
            cropBox.style.height = boxRect.h + 'px';
        }

        function resetCropBoxToDefault() {
            if (!dispW || !dispH) return;
            let targetW = dispW * 0.85;
            let targetH = dispH * 0.85;

            if (lockAspect && activeCropAspect) {
                if (targetW / activeCropAspect <= dispH * 0.9) {
                    targetH = targetW / activeCropAspect;
                } else {
                    targetH = dispH * 0.85;
                    targetW = targetH * activeCropAspect;
                }
            }

            targetW = Math.max(40, Math.min(dispW, targetW));
            targetH = Math.max(30, Math.min(dispH, targetH));

            boxRect = {
                x: Math.round((dispW - targetW) / 2),
                y: Math.round((dispH - targetH) / 2),
                w: Math.round(targetW),
                h: Math.round(targetH)
            };
            syncCropBoxDOM();
        }

        function renderCropStage() {
            if (!cropCanvas || !activeCropField || !croppedImagesState[activeCropField].img) return;
            const entry = croppedImagesState[activeCropField];
            const srcImg = entry.img;
            const rot = entry.rotation % 360;

            const rw = (rot === 90 || rot === 270) ? srcImg.naturalHeight : srcImg.naturalWidth;
            const rh = (rot === 90 || rot === 270) ? srcImg.naturalWidth : srcImg.naturalHeight;

            rotCanvas = document.createElement('canvas');
            rotCanvas.width = rw;
            rotCanvas.height = rh;
            const rCtx = rotCanvas.getContext('2d');
            rCtx.save();
            rCtx.translate(rw / 2, rh / 2);
            rCtx.rotate((rot * Math.PI) / 180);
            rCtx.drawImage(srcImg, -srcImg.naturalWidth / 2, -srcImg.naturalHeight / 2);
            rCtx.restore();

            const maxStageW = Math.min(520, window.innerWidth - 70);
            const maxStageH = Math.min(360, Math.round(window.innerHeight * 0.52));
            const scale = Math.min(maxStageW / rw, maxStageH / rh, 1);

            dispW = Math.max(80, Math.round(rw * scale));
            dispH = Math.max(60, Math.round(rh * scale));

            cropCanvas.width = dispW;
            cropCanvas.height = dispH;
            const cCtx = cropCanvas.getContext('2d');
            cCtx.clearRect(0, 0, dispW, dispH);
            cCtx.drawImage(rotCanvas, 0, 0, dispW, dispH);

            resetCropBoxToDefault();
        }

        function openCropperModal(fieldKey) {
            if (!cropModal || !croppedImagesState[fieldKey] || !croppedImagesState[fieldKey].img) return;
            const card = container.querySelector('.opf-img-upload-card[data-field="' + fieldKey + '"]');
            const aspectStr = card ? card.getAttribute('data-aspect') : '';
            const cardTitle = card ? card.getAttribute('data-title') : 'Image';

            activeCropField = fieldKey;
            activeCropAspect = aspectStr ? parseFloat(aspectStr) : null;
            lockAspect = !!activeCropAspect;

            if (cropRatioLockBtn) cropRatioLockBtn.classList.toggle('active', lockAspect);
            if (cropRatioFreeBtn) cropRatioFreeBtn.classList.toggle('active', !lockAspect);
            if (cropModalTitle) cropModalTitle.textContent = 'Crop ' + cardTitle;

            cropModal.style.display = 'flex';
            cropModal.classList.add('show');
            document.body.style.overflow = 'hidden';

            renderCropStage();
        }

        function closeCropperModal() {
            if (!cropModal) return;
            cropModal.classList.remove('show');
            cropModal.style.display = 'none';
            document.body.style.overflow = '';
            activeCropField = null;
        }

        // Aspect toggle, Rotate, Reset buttons
        if (cropRatioLockBtn) {
            cropRatioLockBtn.addEventListener('click', function () {
                lockAspect = true;
                cropRatioLockBtn.classList.add('active');
                if (cropRatioFreeBtn) cropRatioFreeBtn.classList.remove('active');
                resetCropBoxToDefault();
            });
        }
        if (cropRatioFreeBtn) {
            cropRatioFreeBtn.addEventListener('click', function () {
                lockAspect = false;
                cropRatioFreeBtn.classList.add('active');
                if (cropRatioLockBtn) cropRatioLockBtn.classList.remove('active');
            });
        }
        if (cropRotateBtn) {
            cropRotateBtn.addEventListener('click', function () {
                if (!activeCropField || !croppedImagesState[activeCropField]) return;
                croppedImagesState[activeCropField].rotation = (croppedImagesState[activeCropField].rotation + 90) % 360;
                renderCropStage();
            });
        }
        if (cropResetBtn) {
            cropResetBtn.addEventListener('click', resetCropBoxToDefault);
        }
        if (cropCloseBtn) cropCloseBtn.addEventListener('click', closeCropperModal);
        if (cropCancelBtn) cropCancelBtn.addEventListener('click', closeCropperModal);

        // Pointer drag (move & corner resize) inside Crop Stage
        if (cropBox) {
            let dragMode = null;
            let startX = 0;
            let startY = 0;
            let startRect = null;

            function getClientPoint(e) {
                if (e.touches && e.touches.length) {
                    return { x: e.touches[0].clientX, y: e.touches[0].clientY };
                }
                return { x: e.clientX, y: e.clientY };
            }

            function onPointerDown(e) {
                e.preventDefault();
                const pt = getClientPoint(e);
                const handleEl = e.target.closest('.opf-crop-handle');
                dragMode = handleEl ? handleEl.getAttribute('data-handle') : 'move';
                startX = pt.x;
                startY = pt.y;
                startRect = Object.assign({}, boxRect);
            }

            function onPointerMove(e) {
                if (!dragMode || !startRect) return;
                const pt = getClientPoint(e);
                const dx = pt.x - startX;
                const dy = pt.y - startY;
                const minSize = 32;

                if (dragMode === 'move') {
                    boxRect.x = Math.max(0, Math.min(dispW - startRect.w, Math.round(startRect.x + dx)));
                    boxRect.y = Math.max(0, Math.min(dispH - startRect.h, Math.round(startRect.y + dy)));
                } else {
                    let nx = startRect.x;
                    let ny = startRect.y;
                    let nw = startRect.w;
                    let nh = startRect.h;

                    if (dragMode.indexOf('e') !== -1) nw = startRect.w + dx;
                    if (dragMode.indexOf('s') !== -1) nh = startRect.h + dy;
                    if (dragMode.indexOf('w') !== -1) { nw = startRect.w - dx; nx = startRect.x + dx; }
                    if (dragMode.indexOf('n') !== -1) { nh = startRect.h - dy; ny = startRect.y + dy; }

                    nw = Math.max(minSize, nw);
                    nh = Math.max(minSize, nh);

                    if (lockAspect && activeCropAspect) {
                        nh = nw / activeCropAspect;
                        if (dragMode.indexOf('n') !== -1) {
                            ny = startRect.y + (startRect.h - nh);
                        }
                    }

                    // Keep within canvas bounds
                    if (nx < 0) { nw += nx; nx = 0; }
                    if (ny < 0) { nh += ny; ny = 0; }
                    if (nx + nw > dispW) nw = dispW - nx;
                    if (ny + nh > dispH) nh = dispH - ny;

                    if (lockAspect && activeCropAspect) {
                        if (nw / activeCropAspect > nh) nw = nh * activeCropAspect;
                        else nh = nw / activeCropAspect;
                    }

                    if (nw >= minSize && nh >= 24) {
                        boxRect = { x: Math.round(nx), y: Math.round(ny), w: Math.round(nw), h: Math.round(nh) };
                    }
                }
                syncCropBoxDOM();
            }

            function onPointerUp() {
                dragMode = null;
                startRect = null;
            }

            cropBox.addEventListener('mousedown', onPointerDown);
            cropBox.addEventListener('touchstart', onPointerDown, { passive: false });
            window.addEventListener('mousemove', onPointerMove);
            window.addEventListener('touchmove', onPointerMove, { passive: false });
            window.addEventListener('mouseup', onPointerUp);
            window.addEventListener('touchend', onPointerUp);
        }

        // Save cropped region -> render high-res Blob & update card preview
        if (cropSaveBtn) {
            cropSaveBtn.addEventListener('click', function () {
                if (!activeCropField || !rotCanvas || !dispW || !dispH) return;
                const fieldKey = activeCropField;
                const scaleX = rotCanvas.width / dispW;
                const scaleY = rotCanvas.height / dispH;

                const srcX = Math.max(0, Math.round(boxRect.x * scaleX));
                const srcY = Math.max(0, Math.round(boxRect.y * scaleY));
                const srcW = Math.max(1, Math.min(rotCanvas.width - srcX, Math.round(boxRect.w * scaleX)));
                const srcH = Math.max(1, Math.min(rotCanvas.height - srcY, Math.round(boxRect.h * scaleY)));

                const maxOutW = fieldKey === 'signature_image' ? 900 : 1000;
                const outScale = Math.min(1, maxOutW / srcW);
                const outW = Math.max(1, Math.round(srcW * outScale));
                const outH = Math.max(1, Math.round(srcH * outScale));

                const outCanvas = document.createElement('canvas');
                outCanvas.width = outW;
                outCanvas.height = outH;
                const oCtx = outCanvas.getContext('2d');
                oCtx.fillStyle = '#ffffff';
                oCtx.fillRect(0, 0, outW, outH);
                oCtx.drawImage(rotCanvas, srcX, srcY, srcW, srcH, 0, 0, outW, outH);

                const isSig = (fieldKey === 'signature_image');
                const mime = isSig ? 'image/png' : 'image/jpeg';
                const quality = isSig ? undefined : 0.9;
                const dataUrlPreview = outCanvas.toDataURL(mime, quality);

                function finalizeSave(blob) {
                    croppedImagesState[fieldKey].blob = blob;

                    const card = container.querySelector('.opf-img-upload-card[data-field="' + fieldKey + '"]');
                    if (card) {
                        card.classList.remove('has-error');
                        const picker = card.querySelector('.opf-img-picker-btn');
                        const prevWrap = card.querySelector('.opf-img-preview-wrap');
                        const prevImg = card.querySelector('#opf-preview-' + fieldKey);
                        const stateInput = card.querySelector('#opf-state-' + fieldKey);

                        if (prevImg) prevImg.src = dataUrlPreview;
                        if (picker) picker.style.display = 'none';
                        if (prevWrap) prevWrap.style.display = 'flex';
                        if (stateInput) stateInput.value = 'ready';
                    }
                    closeCropperModal();
                }

                if (outCanvas.toBlob) {
                    outCanvas.toBlob(function (blob) {
                        if (blob) {
                            finalizeSave(blob);
                        }
                    }, mime, quality);
                } else {
                    const byteStr = atob(dataUrlPreview.split(',')[1]);
                    const arr = new Uint8Array(byteStr.length);
                    for (let i = 0; i < byteStr.length; i++) arr[i] = byteStr.charCodeAt(i);
                    finalizeSave(new Blob([arr], { type: mime }));
                }
            });
        }

        // Bind card picker / file input / recrop / remove actions across all 3 cards
        container.querySelectorAll('.opf-img-upload-card').forEach(function (card) {
            const fieldKey = card.getAttribute('data-field');
            const fileInput = card.querySelector('#opf-file-' + fieldKey);
            const stateInput = card.querySelector('#opf-state-' + fieldKey);
            const picker = card.querySelector('.opf-img-picker-btn');
            const prevWrap = card.querySelector('.opf-img-preview-wrap');
            const prevImg = card.querySelector('#opf-preview-' + fieldKey);

            card.querySelectorAll('[data-trigger-file]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (fileInput) {
                        fileInput.value = '';
                        fileInput.click();
                    }
                });
            });

            if (fileInput) {
                fileInput.addEventListener('change', function () {
                    const file = this.files && this.files[0];
                    if (!file) return;
                    if (!file.type || file.type.indexOf('image/') !== 0) {
                        showCustomAlert('Invalid File', 'Please select a valid JPG or PNG image file.', 'warning');
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        const img = new Image();
                        img.onload = function () {
                            croppedImagesState[fieldKey].img = img;
                            croppedImagesState[fieldKey].rotation = 0;
                            openCropperModal(fieldKey);
                        };
                        img.src = ev.target.result;
                    };
                    reader.readAsDataURL(file);
                });
            }

            const recropBtn = card.querySelector('[data-recrop]');
            if (recropBtn) {
                recropBtn.addEventListener('click', function () {
                    if (croppedImagesState[fieldKey].img) {
                        openCropperModal(fieldKey);
                    }
                });
            }

            const removeBtn = card.querySelector('[data-remove-img]');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    croppedImagesState[fieldKey] = { img: null, blob: null, rotation: 0 };
                    if (stateInput) stateInput.value = '';
                    if (prevImg) prevImg.src = '';
                    if (prevWrap) prevWrap.style.display = 'none';
                    if (picker) picker.style.display = 'flex';
                    if (fileInput) fileInput.value = '';
                });
            }
        });
    }
})();
