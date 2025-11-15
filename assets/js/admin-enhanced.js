/**
 * AI SEO Manager - Enhanced Admin JavaScript
 * Modern UX/UI with accessibility, loading states, and better error handling
 *
 * @version 1.1.0
 */

(function($) {
    'use strict';

    const AiSeoManager = {
        // State management
        state: {
            isLoading: false,
            pendingRequests: new Set(),
        },

        // Initialize
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initAccessibility();
            this.loadDashboard Data();
            this.initToastContainer();
            this.initKeyboardShortcuts();
        },

        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            // Approval actions with delegation
            $(document).on('click', '.approve-btn', this.handleApprove.bind(this));
            $(document).on('click', '.reject-btn', this.handleReject.bind(this));
            $(document).on('click', '.add-note-btn', this.toggleNoteSection);

            // Settings
            $('.nav-tab').on('click', this.handleTabClick.bind(this));
            $(document).on('change', '#autopilot-toggle', this.handleAutopilotToggle.bind(this));

            // Bulk actions
            $(document).on('click', '.bulk-approve-btn', this.handleBulkApprove.bind(this));
            $(document).on('change', '.approval-checkbox', this.updateBulkActions);

            // Form validation
            $('form[data-validate]').on('submit', this.validateForm);

            // Auto-save drafts
            $(document).on('input', '.auto-save', this.debounce(this.autoSave, 2000));
        },

        /**
         * Initialize accessibility features
         */
        initAccessibility: function() {
            // Add ARIA labels to interactive elements
            $('.approve-btn').attr({
                'role': 'button',
                'aria-label': 'Approve recommendation'
            });

            $('.reject-btn').attr({
                'role': 'button',
                'aria-label': 'Reject recommendation'
            });

            // Screen reader announcements
            $('<div />', {
                id: 'ai-seo-sr-announcements',
                class: 'screen-reader-text',
                'aria-live': 'polite',
                'aria-atomic': 'true'
            }).appendTo('body');

            // Focus management
            this.manageFocus();
        },

        /**
         * Initialize keyboard shortcuts
         */
        initKeyboardShortcuts: function() {
            $(document).on('keydown', function(e) {
                // Alt + A = Approve first pending
                if (e.altKey && e.key === 'a') {
                    e.preventDefault();
                    $('.approve-btn:first').trigger('click');
                }

                // Alt + R = Reject first pending
                if (e.altKey && e.key === 'r') {
                    e.preventDefault();
                    $('.reject-btn:first').trigger('click');
                }

                // Escape = Close modals/notices
                if (e.key === 'Escape') {
                    $('.notice.is-dismissible').fadeOut().remove();
                }
            });
        },

        /**
         * Handle approve with enhanced UX
         */
        handleApprove: function(e) {
            e.preventDefault();

            const btn = $(e.currentTarget);
            const id = btn.data('id');
            const card = btn.closest('.approval-card, .approval-item');
            const note = card.find('.approval-note').val() || '';

            // Confirm with accessible dialog
            if (!this.confirmAction('approve')) {
                return;
            }

            // Set loading state
            this.setLoadingState(btn, true, 'Approving...');

            // Add to pending requests
            const requestId = 'approve-' + id;
            this.state.pendingRequests.add(requestId);

            $.ajax({
                url: aiSeoManager.restUrl + '/recommendations/' + id + '/approve',
                method: 'POST',
                headers: {'X-WP-Nonce': aiSeoManager.nonce},
                data: JSON.stringify({ note: note }),
                contentType: 'application/json',
                timeout: 30000,
            })
            .done((response) => {
                // Success animation
                card.addClass('approval-success');

                setTimeout(() => {
                    card.fadeOut(300, function() {
                        $(this).remove();
                        // Announce to screen readers
                        this.announceToScreenReader('Recommendation approved successfully');
                    }.bind(this));
                }, 500);

                // Toast notification
                this.showToast('success', '✓ Recommendation approved!', {
                    duration: 4000,
                    action: {
                        text: 'Undo',
                        callback: () => this.undoApproval(id)
                    }
                });

                // Update UI counters
                this.updateCounters();
                this.loadDashboardData();
            })
            .fail((xhr, status, error) => {
                // Enhanced error handling
                const errorMsg = this.getErrorMessage(xhr, 'Failed to approve recommendation');

                this.showToast('error', errorMsg, {
                    duration: 7000,
                    action: {
                        text: 'Retry',
                        callback: () => btn.trigger('click')
                    }
                });

                this.setLoadingState(btn, false);
                this.logError('Approval failed', {id, error, xhr});
            })
            .always(() => {
                this.state.pendingRequests.delete(requestId);
            });
        },

        /**
         * Handle reject with enhanced UX
         */
        handleReject: function(e) {
            e.preventDefault();

            const btn = $(e.currentTarget);
            const id = btn.data('id');
            const card = btn.closest('.approval-card, .approval-item');
            const note = card.find('.approval-note').val() || '';

            if (!this.confirmAction('reject')) {
                return;
            }

            this.setLoadingState(btn, true, 'Rejecting...');

            $.ajax({
                url: aiSeoManager.restUrl + '/recommendations/' + id + '/reject',
                method: 'POST',
                headers: {'X-WP-Nonce': aiSeoManager.nonce},
                data: JSON.stringify({ note: note }),
                contentType: 'application/json',
                timeout: 30000,
            })
            .done(() => {
                card.addClass('approval-rejected');
                setTimeout(() => {
                    card.fadeOut(300, () => card.remove());
                }, 300);

                this.showToast('info', 'Recommendation rejected');
                this.updateCounters();
            })
            .fail((xhr) => {
                const errorMsg = this.getErrorMessage(xhr, 'Failed to reject recommendation');
                this.showToast('error', errorMsg);
                this.setLoadingState(btn, false);
            });
        },

        /**
         * Modern toast notifications
         */
        showToast: function(type, message, options = {}) {
            const defaults = {
                duration: 5000,
                position: 'bottom-right',
                action: null,
                dismissible: true
            };

            const config = Object.assign({}, defaults, options);

            const icons = {
                success: '✓',
                error: '✗',
                warning: '⚠',
                info: 'ℹ'
            };

            const toast = $('<div />', {
                class: `ai-seo-toast ai-seo-toast-${type}`,
                role: 'alert',
                'aria-live': 'polite',
                html: `
                    <span class="toast-icon">${icons[type] || ''}</span>
                    <span class="toast-message">${this.escapeHtml(message)}</span>
                    ${config.action ? `<button class="toast-action">${config.action.text}</button>` : ''}
                    ${config.dismissible ? '<button class="toast-dismiss" aria-label="Dismiss">&times;</button>' : ''}
                `
            });

            // Append to container
            $('#ai-seo-toast-container').append(toast);

            // Animate in
            setTimeout(() => toast.addClass('toast-show'), 10);

            // Action button
            if (config.action) {
                toast.find('.toast-action').on('click', function() {
                    config.action.callback();
                    toast.removeClass('toast-show');
                    setTimeout(() => toast.remove(), 300);
                });
            }

            // Dismiss button
            toast.find('.toast-dismiss').on('click', function() {
                toast.removeClass('toast-show');
                setTimeout(() => toast.remove(), 300);
            });

            // Auto-dismiss
            if (config.duration > 0) {
                setTimeout(() => {
                    toast.removeClass('toast-show');
                    setTimeout(() => toast.remove(), 300);
                }, config.duration);
            }

            return toast;
        },

        /**
         * Initialize toast container
         */
        initToastContainer: function() {
            if (!$('#ai-seo-toast-container').length) {
                $('<div id="ai-seo-toast-container"></div>').appendTo('body');
            }
        },

        /**
         * Enhanced confirm dialog (accessible)
         */
        confirmAction: function(action) {
            // TODO: Replace with custom accessible modal
            const messages = {
                approve: 'Are you sure you want to approve this recommendation?',
                reject: 'Are you sure you want to reject this recommendation?',
                delete: 'This action cannot be undone. Continue?'
            };

            return confirm(messages[action] || 'Are you sure?');
        },

        /**
         * Set loading state on button
         */
        setLoadingState: function(btn, loading, text = '') {
            if (loading) {
                btn.prop('disabled', true)
                   .addClass('is-loading')
                   .data('original-text', btn.html())
                   .html(`<span class="spinner is-active"></span> ${text}`);
            } else {
                btn.prop('disabled', false)
                   .removeClass('is-loading')
                   .html(btn.data('original-text') || btn.html());
            }
        },

        /**
         * Update UI counters
         */
        updateCounters: function() {
            const pending = $('.approval-item:visible, .approval-card:visible').length;

            $('.pending-count').text(pending);

            if (pending === 0) {
                $('.no-pending-message').show();
                $('.approvals-list, .approvals-container').hide();
            }
        },

        /**
         * Get user-friendly error message
         */
        getErrorMessage: function(xhr, fallback) {
            if (xhr.responseJSON && xhr.responseJSON.message) {
                return xhr.responseJSON.message;
            }

            if (xhr.status === 0) {
                return 'Network error. Please check your connection.';
            }

            if (xhr.status === 403) {
                return 'Permission denied. Please refresh and try again.';
            }

            if (xhr.status === 429) {
                return 'Too many requests. Please wait a moment.';
            }

            return fallback;
        },

        /**
         * Announce to screen readers
         */
        announceToScreenReader: function(message) {
            $('#ai-seo-sr-announcements').text(message);
            setTimeout(() => {
                $('#ai-seo-sr-announcements').text('');
            }, 1000);
        },

        /**
         * Escape HTML for XSS prevention
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        },

        /**
         * Log errors for debugging
         */
        logError: function(context, data) {
            if (window.console && aiSeoManager.debugMode) {
                console.error('[AI SEO Manager]', context, data);
            }
        },

        /**
         * Toggle note section
         */
        toggleNoteSection: function() {
            $(this).closest('.approval-card').find('.approval-note-section').slideToggle(200);
        },

        /**
         * Handle tab navigation
         */
        handleTabClick: function(e) {
            e.preventDefault();
            const target = $(e.currentTarget).attr('href');

            $('.nav-tab').removeClass('nav-tab-active').attr('aria-selected', 'false');
            $(e.currentTarget).addClass('nav-tab-active').attr('aria-selected', 'true');

            $('.tab-content').removeClass('active').attr('aria-hidden', 'true');
            $(target).addClass('active').attr('aria-hidden', 'false');

            // Focus first focusable element in tab
            $(target).find(':focusable').first().focus();

            // Update URL
            if (history.pushState) {
                history.pushState(null, null, target);
            }
        },

        /**
         * Manage focus for accessibility
         */
        manageFocus: function() {
            // Trap focus in modals
            $(document).on('keydown', '.modal', function(e) {
                if (e.key === 'Tab') {
                    const focusable = $(this).find(':focusable');
                    const first = focusable.first();
                    const last = focusable.last();

                    if (e.shiftKey && document.activeElement === first[0]) {
                        e.preventDefault();
                        last.focus();
                    } else if (!e.shiftKey && document.activeElement === last[0]) {
                        e.preventDefault();
                        first.focus();
                    }
                }
            });
        },

        /**
         * Initialize tabs with ARIA
         */
        initTabs: function() {
            $('.nav-tab-wrapper').attr('role', 'tablist');
            $('.nav-tab').attr({
                'role': 'tab',
                'aria-selected': 'false'
            });
            $('.nav-tab-active').attr('aria-selected', 'true');

            $('.tab-content').attr({
                'role': 'tabpanel',
                'aria-hidden': 'true'
            });
            $('.tab-content.active').attr('aria-hidden', 'false');

            // Handle hash on load
            const hash = window.location.hash;
            if (hash) {
                $(`.nav-tab[href="${hash}"]`).trigger('click');
            }
        },

        /**
         * Load dashboard data with retry
         */
        loadDashboardData: function(retryCount = 0) {
            if (!$('.ai-seo-manager-dashboard').length) {
                return;
            }

            $.ajax({
                url: aiSeoManager.restUrl + '/stats',
                method: 'GET',
                headers: {'X-WP-Nonce': aiSeoManager.nonce},
                timeout: 15000,
            })
            .done((data) => {
                this.updateDashboardStats(data);
            })
            .fail(() => {
                if (retryCount < 3) {
                    setTimeout(() => {
                        this.loadDashboardData(retryCount + 1);
                    }, 2000 * (retryCount + 1));
                }
            });
        },

        /**
         * Update dashboard stats
         */
        updateDashboardStats: function(data) {
            if (data.pending_recommendations !== undefined) {
                $('.stat-pending').text(data.pending_recommendations);
            }
            if (data.awaiting_approval !== undefined) {
                $('.stat-awaiting').text(data.awaiting_approval);
            }
            if (data.completed !== undefined) {
                $('.stat-completed').text(data.completed);
            }
        }
    };

    // Initialize when ready
    $(document).ready(() => AiSeoManager.init());

    // Export globally
    window.AiSeoManager = AiSeoManager;

})(jQuery);
