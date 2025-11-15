/**
 * AI SEO Manager - Admin JavaScript
 */

(function($) {
    'use strict';

    const AiSeoManager = {
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.loadDashboardData();
        },

        bindEvents: function() {
            // Dashboard approval actions
            $(document).on('click', '.approve-btn', this.handleApprove);
            $(document).on('click', '.reject-btn', this.handleReject);

            // Autopilot toggle
            $(document).on('change', '#autopilot-toggle', this.handleAutopilotToggle);

            // Settings tabs
            $('.nav-tab').on('click', this.handleTabClick);

            // Note toggle
            $(document).on('click', '.add-note-btn', function() {
                $(this).closest('.approval-card').find('.approval-note-section').slideToggle();
            });
        },

        initTabs: function() {
            const hash = window.location.hash;
            if (hash) {
                $('.nav-tab[href="' + hash + '"]').trigger('click');
            }
        },

        handleTabClick: function(e) {
            e.preventDefault();
            const target = $(this).attr('href');

            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            $('.tab-content').removeClass('active');
            $(target).addClass('active');

            // Update URL hash
            if (history.pushState) {
                history.pushState(null, null, target);
            }
        },

        handleApprove: function(e) {
            e.preventDefault();

            const btn = $(this);
            const id = btn.data('id');
            const card = btn.closest('.approval-card, .approval-item');
            const note = card.find('.approval-note').val() || '';

            if (!confirm(aiSeoManager.i18n.confirmApprove || 'Are you sure you want to approve this recommendation?')) {
                return;
            }

            btn.prop('disabled', true).html(aiSeoManager.i18n.approving || 'Approving...');

            $.ajax({
                url: aiSeoManager.restUrl + '/recommendations/' + id + '/approve',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': aiSeoManager.nonce
                },
                data: JSON.stringify({ note: note }),
                contentType: 'application/json',
                success: function(response) {
                    card.fadeOut(300, function() {
                        $(this).remove();
                    });

                    AiSeoManager.showNotice('success', aiSeoManager.i18n.success || 'Recommendation approved!');

                    // Reload stats
                    AiSeoManager.loadDashboardData();
                },
                error: function(xhr) {
                    AiSeoManager.showNotice('error', xhr.responseJSON?.message || 'Failed to approve recommendation');
                    btn.prop('disabled', false).html('✓ ' + (aiSeoManager.i18n.approve || 'Approve'));
                }
            });
        },

        handleReject: function(e) {
            e.preventDefault();

            const btn = $(this);
            const id = btn.data('id');
            const card = btn.closest('.approval-card, .approval-item');
            const note = card.find('.approval-note').val() || '';

            if (!confirm(aiSeoManager.i18n.confirmReject || 'Are you sure you want to reject this recommendation?')) {
                return;
            }

            btn.prop('disabled', true).html(aiSeoManager.i18n.rejecting || 'Rejecting...');

            $.ajax({
                url: aiSeoManager.restUrl + '/recommendations/' + id + '/reject',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': aiSeoManager.nonce
                },
                data: JSON.stringify({ note: note }),
                contentType: 'application/json',
                success: function(response) {
                    card.fadeOut(300, function() {
                        $(this).remove();
                    });

                    // Reload stats
                    AiSeoManager.loadDashboardData();
                },
                error: function(xhr) {
                    AiSeoManager.showNotice('error', xhr.responseJSON?.message || 'Failed to reject recommendation');
                    btn.prop('disabled', false).html('✗ ' + (aiSeoManager.i18n.reject || 'Reject'));
                }
            });
        },

        handleAutopilotToggle: function(e) {
            const enabled = $(this).is(':checked');

            $.ajax({
                url: aiSeoManager.restUrl + '/autopilot/toggle',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': aiSeoManager.nonce
                },
                data: JSON.stringify({ enabled: enabled }),
                contentType: 'application/json',
                success: function(response) {
                    AiSeoManager.showNotice('success', 'Autopilot ' + (enabled ? 'enabled' : 'disabled'));
                },
                error: function() {
                    AiSeoManager.showNotice('error', 'Failed to toggle autopilot');
                    $(this).prop('checked', !enabled);
                }
            });
        },

        loadDashboardData: function() {
            if (!$('.ai-seo-manager-dashboard').length) {
                return;
            }

            $.ajax({
                url: aiSeoManager.restUrl + '/stats',
                method: 'GET',
                headers: {
                    'X-WP-Nonce': aiSeoManager.nonce
                },
                success: function(response) {
                    // Update dashboard stats if elements exist
                    // This is a placeholder for dynamic updates
                }
            });
        },

        showNotice: function(type, message) {
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';

            const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

            $('.wrap h1').after(notice);

            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        analyzePost: function(postId) {
            return $.ajax({
                url: aiSeoManager.restUrl + '/analyze/' + postId,
                method: 'POST',
                headers: {
                    'X-WP-Nonce': aiSeoManager.nonce
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        AiSeoManager.init();
    });

    // Export for global access
    window.AiSeoManager = AiSeoManager;

})(jQuery);
