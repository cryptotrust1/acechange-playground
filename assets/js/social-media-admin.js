/**
 * AI SEO Social Media - Admin JavaScript
 */

(function($) {
    'use strict';

    const SocialMediaAdmin = {

        init: function() {
            this.bindEvents();
            this.initTooltips();
        },

        bindEvents: function() {
            // Account Management
            $(document).on('click', '#add-account-btn', this.openAccountModal.bind(this));
            $(document).on('click', '.edit-account-btn', this.editAccount.bind(this));
            $(document).on('click', '.delete-account-btn', this.deleteAccount.bind(this));
            $(document).on('click', '#test-connection-btn', this.testConnection.bind(this));
            $(document).on('submit', '#account-form', this.saveAccount.bind(this));
            $(document).on('change', '#account-platform', this.showPlatformFields.bind(this));

            // Content Generation
            $(document).on('click', '#ai-generate-btn', this.generateContent.bind(this));
            $(document).on('click', '#generate-variations-btn', this.generateVariations.bind(this));
            $(document).on('click', '.use-variation', this.useVariation.bind(this));

            // Publishing
            $(document).on('click', '#publish-btn', this.publishNow.bind(this));
            $(document).on('click', '#schedule-btn', this.schedulePost.bind(this));
            $(document).on('click', '#save-draft-btn', this.saveDraft.bind(this));

            // Character counter
            $(document).on('input', '#post-content', this.updateCharCount.bind(this));

            // Platform selector
            $(document).on('change', 'input[name="platforms[]"]', this.updatePlatformInfo.bind(this));

            // Schedule toggle
            $(document).on('change', 'input[name="schedule-type"]', this.toggleSchedule.bind(this));

            // Modal close
            $(document).on('click', '.modal-close, .modal-backdrop', this.closeModal.bind(this));
            $(document).on('keyup', this.handleEscape.bind(this));
        },

        /**
         * Account Management
         */
        openAccountModal: function(e) {
            e.preventDefault();
            this.showModal('account-modal');
            $('#account-form')[0].reset();
            $('#account-id').val('');
            $('#account-modal-title').text('Add New Account');
        },

        editAccount: function(e) {
            e.preventDefault();
            const accountId = $(e.currentTarget).data('account-id');

            this.ajaxRequest('ai_seo_social_get_account', {
                account_id: accountId
            }, (response) => {
                const account = response.account;

                $('#account-id').val(account.id);
                $('#account-platform').val(account.platform).trigger('change');
                $('#account-name').val(account.account_name);
                $('#account-status').val(account.status);

                // Fill platform-specific credentials
                this.fillCredentials(account.platform, account.credentials);

                this.showModal('account-modal');
                $('#account-modal-title').text('Edit Account');
            });
        },

        deleteAccount: function(e) {
            e.preventDefault();

            if (!confirm(aiSeoSocial.strings.confirm_delete)) {
                return;
            }

            const accountId = $(e.currentTarget).data('account-id');

            this.ajaxRequest('ai_seo_social_delete_account', {
                account_id: accountId
            }, (response) => {
                this.showNotice(response.message, 'success');
                setTimeout(() => location.reload(), 1000);
            });
        },

        testConnection: function(e) {
            e.preventDefault();

            const platform = $('#account-platform').val();
            const credentials = this.getCredentials(platform);

            if (!this.validateCredentials(platform, credentials)) {
                this.showNotice('Please fill all required fields', 'error');
                return;
            }

            $('#test-connection-btn').prop('disabled', true).text('Testing...');

            this.ajaxRequest('ai_seo_social_test_connection', {
                platform: platform,
                credentials: credentials
            }, (response) => {
                this.showNotice(response.message, 'success');
                $('#test-connection-btn').prop('disabled', false).text('Test Connection');
            }, (error) => {
                this.showNotice(error.message, 'error');
                $('#test-connection-btn').prop('disabled', false).text('Test Connection');
            });
        },

        saveAccount: function(e) {
            e.preventDefault();

            const platform = $('#account-platform').val();
            const formData = {
                account_id: $('#account-id').val(),
                platform: platform,
                account_name: $('#account-name').val(),
                platform_account_id: $('#platform-account-id').val(),
                status: $('#account-status').val(),
                credentials: this.getCredentials(platform)
            };

            if (!this.validateCredentials(platform, formData.credentials)) {
                this.showNotice('Please fill all required fields', 'error');
                return;
            }

            $('#save-account-btn').prop('disabled', true).text('Saving...');

            this.ajaxRequest('ai_seo_social_save_account', formData, (response) => {
                this.showNotice(response.message, 'success');
                this.closeModal();
                setTimeout(() => location.reload(), 1000);
            }, (error) => {
                this.showNotice(error.message, 'error');
                $('#save-account-btn').prop('disabled', false).text('Save Account');
            });
        },

        showPlatformFields: function(e) {
            const platform = $(e.target).val();

            // Hide all credential fields
            $('.platform-credentials').hide();

            // Show selected platform fields
            $(`#${platform}-credentials`).show();
        },

        getCredentials: function(platform) {
            const credentials = {};

            $(`#${platform}-credentials`).find('input').each(function() {
                const name = $(this).attr('name').replace(`${platform}_`, '');
                credentials[name] = $(this).val();
            });

            return credentials;
        },

        fillCredentials: function(platform, credentials) {
            Object.keys(credentials).forEach(key => {
                $(`#${platform}-credentials input[name="${platform}_${key}"]`).val(credentials[key]);
            });
        },

        validateCredentials: function(platform, credentials) {
            // Basic validation - check all required fields filled
            for (let key in credentials) {
                if (!credentials[key]) {
                    return false;
                }
            }
            return true;
        },

        /**
         * Content Generation
         */
        generateContent: function(e) {
            e.preventDefault();

            const topic = $('#ai-topic').val().trim();
            if (!topic) {
                this.showNotice('Please enter a topic', 'error');
                return;
            }

            const platforms = this.getSelectedPlatforms();
            if (platforms.length === 0) {
                this.showNotice('Please select at least one platform', 'error');
                return;
            }

            const platform = platforms[0]; // Use first selected platform

            $('#ai-generate-btn').prop('disabled', true).html('<span class="spinner"></span> Generating...');

            this.ajaxRequest('ai_seo_social_generate_content', {
                topic: topic,
                platform: platform,
                tone: $('#ai-tone').val(),
                category: $('#ai-category').val() || 'general',
                include_hashtags: true,
                include_emojis: true
            }, (response) => {
                const content = response.content;

                // Fill content textarea
                let fullContent = content.text;
                if (content.hashtags && content.hashtags.length > 0) {
                    fullContent += '\n\n' + content.hashtags.map(h => '#' + h).join(' ');
                }

                $('#post-content').val(fullContent).trigger('input');

                this.showNotice(response.message, 'success');
                $('#ai-generate-btn').prop('disabled', false).html('<span class="dashicons dashicons-superhero"></span> Generate with AI');
            }, (error) => {
                this.showNotice(error.message || 'Failed to generate content', 'error');
                $('#ai-generate-btn').prop('disabled', false).html('<span class="dashicons dashicons-superhero"></span> Generate with AI');
            });
        },

        generateVariations: function(e) {
            e.preventDefault();

            const topic = $('#ai-topic').val().trim();
            if (!topic) {
                this.showNotice('Please enter a topic', 'error');
                return;
            }

            const platforms = this.getSelectedPlatforms();
            const platform = platforms[0] || 'facebook';

            this.showModal('variations-modal');
            $('#variations-list').html('<div class="loading">Generating variations...</div>');

            this.ajaxRequest('ai_seo_social_generate_variations', {
                topic: topic,
                platform: platform,
                tone: $('#ai-tone').val(),
                category: $('#ai-category').val() || 'general',
                count: 3
            }, (response) => {
                this.displayVariations(response.variations);
            }, (error) => {
                $('#variations-list').html('<div class="error">Failed to generate variations</div>');
            });
        },

        displayVariations: function(variations) {
            let html = '';

            variations.forEach((variation, index) => {
                let fullText = variation.text;
                if (variation.hashtags && variation.hashtags.length > 0) {
                    fullText += '\n\n' + variation.hashtags.map(h => '#' + h).join(' ');
                }

                html += `
                    <div class="variation-item">
                        <div class="variation-number">Variation ${index + 1}</div>
                        <div class="variation-content">${this.escapeHtml(fullText)}</div>
                        <button type="button" class="button use-variation" data-content="${this.escapeAttr(fullText)}">
                            Use This
                        </button>
                    </div>
                `;
            });

            $('#variations-list').html(html);
        },

        useVariation: function(e) {
            const content = $(e.target).data('content');
            $('#post-content').val(content).trigger('input');
            this.closeModal();
            this.showNotice('Variation applied!', 'success');
        },

        /**
         * Publishing
         */
        publishNow: function(e) {
            e.preventDefault();

            const content = $('#post-content').val().trim();
            if (!content) {
                this.showNotice('Please enter content', 'error');
                return;
            }

            const platforms = this.getSelectedPlatforms();
            if (platforms.length === 0) {
                this.showNotice('Please select at least one platform', 'error');
                return;
            }

            if (!confirm(`Publish to ${platforms.join(', ')}?`)) {
                return;
            }

            $('#publish-btn').prop('disabled', true).html('<span class="spinner"></span> Publishing...');

            this.ajaxRequest('ai_seo_social_publish_now', {
                content: content,
                platforms: platforms,
                media: this.getMediaUrls()
            }, (response) => {
                this.showNotice(response.message, 'success');

                // Clear form
                $('#post-content').val('');
                $('input[name="platforms[]"]').prop('checked', false);

                $('#publish-btn').prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> Publish');

                // Redirect to dashboard after 2 seconds
                setTimeout(() => {
                    window.location.href = aiSeoSocial.dashboardUrl;
                }, 2000);
            }, (error) => {
                this.showNotice(error.message || 'Publishing failed', 'error');
                $('#publish-btn').prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> Publish');
            });
        },

        schedulePost: function(e) {
            e.preventDefault();

            const content = $('#post-content').val().trim();
            const scheduledTime = $('#scheduled-time').val();
            const platforms = this.getSelectedPlatforms();

            if (!content || !scheduledTime || platforms.length === 0) {
                this.showNotice('Please fill all required fields', 'error');
                return;
            }

            $('#schedule-btn').prop('disabled', true).text('Scheduling...');

            this.ajaxRequest('ai_seo_social_schedule_post', {
                content: content,
                platforms: platforms,
                scheduled_time: scheduledTime,
                media: this.getMediaUrls()
            }, (response) => {
                this.showNotice(response.message, 'success');
                $('#post-content').val('');
                $('#schedule-btn').prop('disabled', false).text('Schedule');

                setTimeout(() => {
                    window.location.href = aiSeoSocial.calendarUrl;
                }, 2000);
            }, (error) => {
                this.showNotice(error.message, 'error');
                $('#schedule-btn').prop('disabled', false).text('Schedule');
            });
        },

        saveDraft: function(e) {
            e.preventDefault();

            const content = $('#post-content').val().trim();
            if (!content) {
                this.showNotice('Please enter content', 'error');
                return;
            }

            const platforms = this.getSelectedPlatforms();
            if (platforms.length === 0) {
                platforms.push('facebook'); // Default platform for drafts
            }

            $('#save-draft-btn').prop('disabled', true).text('Saving...');

            this.ajaxRequest('ai_seo_social_save_draft', {
                content: content,
                platforms: platforms
            }, (response) => {
                this.showNotice(response.message, 'success');
                $('#save-draft-btn').prop('disabled', false).text('Save as Draft');
            }, (error) => {
                this.showNotice(error.message, 'error');
                $('#save-draft-btn').prop('disabled', false).text('Save as Draft');
            });
        },

        /**
         * Helpers
         */
        getSelectedPlatforms: function() {
            const platforms = [];
            $('input[name="platforms[]"]:checked').each(function() {
                platforms.push($(this).val());
            });
            return platforms;
        },

        getMediaUrls: function() {
            const urls = [];
            $('.media-item').each(function() {
                urls.push($(this).data('url'));
            });
            return urls;
        },

        updateCharCount: function(e) {
            const length = $(e.target).val().length;
            $('#char-count').text(length);

            // Update preview
            const content = $(e.target).val().replace(/\n/g, '<br>');
            $('#preview-content').html(content || '<p class="preview-empty">Your post will appear here...</p>');
        },

        updatePlatformInfo: function(e) {
            const platforms = this.getSelectedPlatforms();
            // Could show character limits, best practices, etc.
        },

        toggleSchedule: function(e) {
            const type = $('input[name="schedule-type"]:checked').val();
            if (type === 'schedule') {
                $('#schedule-datetime').slideDown();
            } else {
                $('#schedule-datetime').slideUp();
            }
        },

        /**
         * UI Helpers
         */
        showModal: function(modalId) {
            const $modal = $(`#${modalId}`);
            if ($modal.length === 0) {
                // Create modal if it doesn't exist
                $('body').append(this.createModal(modalId));
            }
            $(`#${modalId}`).fadeIn(200);
            $('body').addClass('modal-open');
        },

        closeModal: function() {
            $('.social-modal').fadeOut(200);
            $('body').removeClass('modal-open');
        },

        handleEscape: function(e) {
            if (e.keyCode === 27) { // ESC
                this.closeModal();
            }
        },

        showNotice: function(message, type = 'info') {
            const noticeClass = type === 'success' ? 'notice-success' :
                              type === 'error' ? 'notice-error' :
                              'notice-info';

            const $notice = $(`
                <div class="notice ${noticeClass} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss"></button>
                </div>
            `);

            $('.wrap h1').after($notice);

            // Auto dismiss after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);

            // Manual dismiss
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },

        ajaxRequest: function(action, data, successCallback, errorCallback) {
            $.ajax({
                url: aiSeoSocial.ajaxurl,
                type: 'POST',
                data: {
                    action: action,
                    nonce: aiSeoSocial.nonce,
                    ...data
                },
                success: function(response) {
                    if (response.success) {
                        if (successCallback) successCallback(response.data);
                    } else {
                        if (errorCallback) errorCallback(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    if (errorCallback) {
                        errorCallback({ message: 'Network error: ' + error });
                    }
                }
            });
        },

        initTooltips: function() {
            // Could add tooltips for buttons
        },

        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        },

        escapeAttr: function(text) {
            return text.replace(/"/g, '&quot;');
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SocialMediaAdmin.init();
    });

    // Export to global scope
    window.SocialMediaAdmin = SocialMediaAdmin;

})(jQuery);
