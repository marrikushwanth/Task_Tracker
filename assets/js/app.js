/**
 * TaskFlow - Interactive Frontend Application Script
 * RTIH Technical Internship Assignment
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // =========================================================================
    // 1. Password Visibility Toggle
    // =========================================================================
    const passwordToggles = document.querySelectorAll('.toggle-password');
    passwordToggles.forEach(button => {
        button.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (!targetInput) return;

            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                if (icon) {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
            } else {
                targetInput.type = 'password';
                if (icon) {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }
        });
    });

    // =========================================================================
    // 2. Demo Account Autofill Buttons (Evaluation Helper)
    // =========================================================================
    const demoBtns = document.querySelectorAll('.demo-login-btn');
    demoBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const email = this.getAttribute('data-email');
            const password = this.getAttribute('data-password');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            if (emailInput && passwordInput) {
                emailInput.value = email;
                passwordInput.value = password;

                // Subtle visual feedback
                emailInput.classList.add('is-valid');
                passwordInput.classList.add('is-valid');
                setTimeout(() => {
                    emailInput.classList.remove('is-valid');
                    passwordInput.classList.remove('is-valid');
                }, 1200);
            }
        });
    });

    // =========================================================================
    // 3. Mobile Sidebar Drawer Controls
    // =========================================================================
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (backdrop) backdrop.classList.add('show');
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
    }

    if (sidebarToggle) sidebarToggle.addEventListener('click', openSidebar);
    if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    // =========================================================================
    // 4. Delete Confirmation Modal Handler
    // =========================================================================
    const deleteModalEl = document.getElementById('deleteTaskModal');
    let deleteModalInstance = null;
    if (deleteModalEl && typeof bootstrap !== 'undefined') {
        deleteModalInstance = new bootstrap.Modal(deleteModalEl);
    }

    const deleteTaskIdInput = document.getElementById('deleteModalTaskId');
    const deleteTaskTitleSpan = document.getElementById('deleteModalTaskTitle');
    const modalDeleteForm = document.getElementById('modalDeleteForm');

    // Attach click listener on all delete buttons
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.delete-task-btn');
        if (!btn) return;

        e.preventDefault();
        const taskId = btn.getAttribute('data-id');
        const taskTitle = btn.getAttribute('data-title') || 'this task';

        if (deleteTaskIdInput && deleteTaskTitleSpan) {
            deleteTaskIdInput.value = taskId;
            deleteTaskTitleSpan.textContent = `"${taskTitle}"`;
        }

        if (deleteModalInstance) {
            deleteModalInstance.show();
        } else if (confirm(`Are you sure you want to delete "${taskTitle}"?`)) {
            // Fallback native confirm if modal isn't initialized
            if (modalDeleteForm) {
                modalDeleteForm.submit();
            }
        }
    });

    // Intercept Modal Delete Form for smooth AJAX deletion
    if (modalDeleteForm) {
        modalDeleteForm.addEventListener('submit', function (e) {
            const submitBtn = document.getElementById('confirmDeleteSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Deleting...';
            }

            // Allow natural form POST if not AJAX supported or let it submit natively
            // Submitting naturally ensures full page reload and updated counts across views
        });
    }

    // =========================================================================
    // 5. Quick Status Update Handler (AJAX + Progressive Enhancement)
    // =========================================================================
    document.addEventListener('submit', function (e) {
        const form = e.target.closest('.quick-status-form');
        if (!form) return;

        e.preventDefault(); // Intercept for AJAX

        const formData = new FormData(form);
        const actionUrl = form.getAttribute('action');

        fetch(actionUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showToast('success', data.message || 'Status updated successfully!');

                // Find the nearest status button dropdown trigger and replace its HTML badge
                const dropdown = form.closest('.dropdown');
                if (dropdown) {
                    const toggleBtn = dropdown.querySelector('.dropdown-toggle');
                    if (toggleBtn && data.badge_html) {
                        toggleBtn.innerHTML = data.badge_html;
                    }

                    // Update active state in menu items
                    const items = dropdown.querySelectorAll('.dropdown-item');
                    items.forEach(item => {
                        if (item.textContent.trim().toLowerCase().includes(data.new_status.toLowerCase())) {
                            item.classList.add('active');
                        } else {
                            item.classList.remove('active');
                        }
                    });

                    // Hide dropdown menu
                    const menu = dropdown.querySelector('.dropdown-menu');
                    if (menu) menu.classList.remove('show');
                }

                // If on tasks index, refresh counters or reload gently if needed
            } else {
                showToast('danger', data.message || 'Failed to update status.');
            }
        })
        .catch(err => {
            console.warn('[Quick Status AJAX Error] Falling back to standard submission:', err);
            // Fallback: remove listener and submit naturally
            form.submit();
        });
    });

    // =========================================================================
    // 6. Auto-submit Dropdowns for Filters
    // =========================================================================
    const autoSubmitSelects = document.querySelectorAll('.filter-auto-submit');
    autoSubmitSelects.forEach(select => {
        select.addEventListener('change', function () {
            const form = this.closest('form');
            if (form) form.submit();
        });
    });

    // =========================================================================
    // 7. Debounced Live Search Input
    // =========================================================================
    const searchInput = document.getElementById('taskSearchInput');
    if (searchInput) {
        let debounceTimer = null;
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const form = searchInput.closest('form');
                if (form) form.submit();
            }, 550);
        });
    }

    // =========================================================================
    // 8. Toast Notification Helper
    // =========================================================================
    window.showToast = function (type, message) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const iconClass = type === 'success' 
            ? 'bi-check-circle-fill text-success' 
            : (type === 'danger' ? 'bi-exclamation-triangle-fill text-danger' : 'bi-info-circle-fill text-info');

        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-bg-light border shadow-sm" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-2">
                        <i class="bi ${iconClass} fs-5"></i>
                        <span class="text-slate-800 small fw-medium">${escapeHtml(message)}</span>
                    </div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', toastHtml);
        const toastEl = document.getElementById(toastId);
        if (toastEl && typeof bootstrap !== 'undefined') {
            const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        }
    };

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});

