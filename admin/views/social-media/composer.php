<?php
/**
 * Social Media Composer View
 *
 * @var array $platforms Active platform accounts
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ai-seo-social-composer">
    <h1><?php _e('Social Media Composer', 'ai-seo-manager'); ?></h1>

    <div class="composer-container">
        <div class="composer-main">
            <div class="composer-form">
                <h2><?php _e('Create New Post', 'ai-seo-manager'); ?></h2>

                <div class="composer-section">
                    <label><?php _e('Content', 'ai-seo-manager'); ?></label>
                    <textarea id="post-content" rows="8" placeholder="<?php esc_attr_e('Write your post content here...', 'ai-seo-manager'); ?>"></textarea>
                    <div class="character-count">
                        <span id="char-count">0</span> <?php _e('characters', 'ai-seo-manager'); ?>
                    </div>
                </div>

                <div class="composer-section">
                    <label><?php _e('AI Generate Content', 'ai-seo-manager'); ?></label>
                    <div class="ai-generate-controls">
                        <input type="text" id="ai-topic" placeholder="<?php esc_attr_e('Enter topic...', 'ai-seo-manager'); ?>">
                        <select id="ai-tone">
                            <option value="professional"><?php _e('Professional', 'ai-seo-manager'); ?></option>
                            <option value="casual"><?php _e('Casual', 'ai-seo-manager'); ?></option>
                            <option value="humorous"><?php _e('Humorous', 'ai-seo-manager'); ?></option>
                            <option value="formal"><?php _e('Formal', 'ai-seo-manager'); ?></option>
                        </select>
                        <button type="button" id="ai-generate-btn" class="button">
                            <span class="dashicons dashicons-superhero"></span>
                            <?php _e('Generate with AI', 'ai-seo-manager'); ?>
                        </button>
                    </div>
                </div>

                <div class="composer-section">
                    <label><?php _e('Select Platforms', 'ai-seo-manager'); ?></label>
                    <?php if (!empty($platforms)): ?>
                        <div class="platform-selector">
                            <?php foreach ($platforms as $platform_name => $accounts): ?>
                                <label class="platform-option">
                                    <input type="checkbox" name="platforms[]" value="<?php echo esc_attr($platform_name); ?>">
                                    <span class="platform-icon platform-<?php echo esc_attr($platform_name); ?>">
                                        <?php echo esc_html(ucfirst($platform_name)); ?>
                                    </span>
                                    <span class="platform-count"><?php echo count($accounts); ?> <?php _e('account(s)', 'ai-seo-manager'); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="notice notice-warning">
                            <?php _e('No active accounts. Please configure your accounts first.', 'ai-seo-manager'); ?>
                            <a href="<?php echo admin_url('admin.php?page=ai-seo-social-accounts'); ?>"><?php _e('Manage Accounts', 'ai-seo-manager'); ?></a>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="composer-section">
                    <label><?php _e('Media (Optional)', 'ai-seo-manager'); ?></label>
                    <div class="media-uploader">
                        <button type="button" id="upload-media-btn" class="button">
                            <span class="dashicons dashicons-format-image"></span>
                            <?php _e('Add Image/Video', 'ai-seo-manager'); ?>
                        </button>
                        <div id="media-preview"></div>
                    </div>
                </div>

                <div class="composer-section">
                    <label><?php _e('Schedule (Optional)', 'ai-seo-manager'); ?></label>
                    <div class="schedule-controls">
                        <label>
                            <input type="radio" name="schedule-type" value="now" checked>
                            <?php _e('Publish Now', 'ai-seo-manager'); ?>
                        </label>
                        <label>
                            <input type="radio" name="schedule-type" value="schedule">
                            <?php _e('Schedule for Later', 'ai-seo-manager'); ?>
                        </label>
                    </div>
                    <div id="schedule-datetime" style="display: none;">
                        <input type="datetime-local" id="scheduled-time" min="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                </div>

                <div class="composer-actions">
                    <button type="button" id="save-draft-btn" class="button">
                        <?php _e('Save as Draft', 'ai-seo-manager'); ?>
                    </button>
                    <button type="button" id="publish-btn" class="button button-primary">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Publish', 'ai-seo-manager'); ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="composer-sidebar">
            <div class="composer-preview">
                <h3><?php _e('Preview', 'ai-seo-manager'); ?></h3>
                <div id="preview-content" class="preview-box">
                    <p class="preview-empty"><?php _e('Your post will appear here...', 'ai-seo-manager'); ?></p>
                </div>
            </div>

            <div class="composer-tips">
                <h3><?php _e('Tips', 'ai-seo-manager'); ?></h3>
                <ul>
                    <li><?php _e('Use hashtags to increase discoverability', 'ai-seo-manager'); ?></li>
                    <li><?php _e('Keep your message clear and concise', 'ai-seo-manager'); ?></li>
                    <li><?php _e('Include a call-to-action', 'ai-seo-manager'); ?></li>
                    <li><?php _e('Post at optimal times for engagement', 'ai-seo-manager'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.composer-container {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
    margin-top: 20px;
}

.composer-form {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.composer-section {
    margin-bottom: 20px;
}

.composer-section label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
}

.composer-section textarea {
    width: 100%;
    min-height: 150px;
    padding: 10px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
}

.character-count {
    text-align: right;
    font-size: 12px;
    color: #646970;
    margin-top: 5px;
}

.ai-generate-controls {
    display: flex;
    gap: 10px;
}

.ai-generate-controls input {
    flex: 1;
}

.platform-selector {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
}

.platform-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    border: 2px solid #dcdcde;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.platform-option:hover {
    border-color: #2271b1;
    background: #f6f7f7;
}

.platform-option input:checked + .platform-icon {
    font-weight: 700;
}

.platform-icon {
    font-size: 12px;
}

.platform-count {
    font-size: 11px;
    color: #646970;
}

.schedule-controls {
    display: flex;
    gap: 20px;
}

.schedule-controls label {
    font-weight: normal;
    display: flex;
    align-items: center;
    gap: 5px;
}

#schedule-datetime {
    margin-top: 10px;
}

#schedule-datetime input {
    width: 100%;
    padding: 8px;
}

.composer-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 1px solid #dcdcde;
}

.composer-sidebar > div {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.composer-sidebar h3 {
    margin-top: 0;
}

.preview-box {
    border: 1px solid #dcdcde;
    border-radius: 4px;
    padding: 15px;
    min-height: 100px;
    background: #f6f7f7;
}

.preview-empty {
    text-align: center;
    color: #646970;
    font-style: italic;
}

.composer-tips ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.composer-tips li {
    padding: 8px 0;
    padding-left: 20px;
    position: relative;
}

.composer-tips li:before {
    content: "→";
    position: absolute;
    left: 0;
    color: #2271b1;
}

@media (max-width: 1200px) {
    .composer-container {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Character counter
    $('#post-content').on('input', function() {
        $('#char-count').text($(this).val().length);
        $('#preview-content').html('<p>' + $(this).val().replace(/\n/g, '<br>') + '</p>');
    });

    // Schedule type toggle
    $('input[name="schedule-type"]').on('change', function() {
        if ($(this).val() === 'schedule') {
            $('#schedule-datetime').slideDown();
        } else {
            $('#schedule-datetime').slideUp();
        }
    });

    // AI Generate (placeholder - implement with AJAX)
    $('#ai-generate-btn').on('click', function() {
        var topic = $('#ai-topic').val();
        if (!topic) {
            alert('<?php _e('Please enter a topic', 'ai-seo-manager'); ?>');
            return;
        }
        // TODO: AJAX call to generate content
        alert('AI generation will be implemented via AJAX');
    });
});
</script>
