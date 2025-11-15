<?php
/**
 * Approvals Page Template
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap ai-seo-manager-approvals">
    <h1><?php _e('Approval Queue', 'ai-seo-manager'); ?></h1>
    <p class="description"><?php _e('Review and approve AI-generated SEO recommendations before they are applied to your content.', 'ai-seo-manager'); ?></p>

    <?php if (empty($pending)) : ?>
        <div class="notice notice-info">
            <p><?php _e('🎉 No pending approvals! All recommendations have been reviewed.', 'ai-seo-manager'); ?></p>
        </div>
    <?php else : ?>
        <div class="approvals-container">
            <?php foreach ($pending as $item) : ?>
                <div class="approval-card priority-<?php echo esc_attr($item->priority); ?>" data-id="<?php echo esc_attr($item->id); ?>">
                    <div class="approval-card-header">
                        <div class="approval-title-section">
                            <h3><?php echo esc_html($item->title); ?></h3>
                            <div class="approval-meta">
                                <span class="priority-badge <?php echo esc_attr($item->priority); ?>">
                                    <?php echo esc_html(ucfirst($item->priority)); ?>
                                </span>
                                <span class="confidence-badge">
                                    <?php printf(__('%d%% AI Confidence', 'ai-seo-manager'), round($item->ai_confidence * 100)); ?>
                                </span>
                                <span class="type-badge">
                                    <?php echo esc_html(str_replace('_', ' ', ucwords($item->recommendation_type, '_'))); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="approval-card-body">
                        <?php if (!empty($item->post_title)) : ?>
                            <div class="affected-post">
                                <strong><?php _e('Affected Post:', 'ai-seo-manager'); ?></strong>
                                <a href="<?php echo esc_url($item->post_url); ?>" target="_blank">
                                    <?php echo esc_html($item->post_title); ?>
                                </a>
                                <a href="<?php echo admin_url('post.php?post=' . $item->post_id . '&action=edit'); ?>" class="edit-link">
                                    <?php _e('Edit', 'ai-seo-manager'); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="recommendation-description">
                            <p><?php echo nl2br(esc_html($item->description)); ?></p>
                        </div>

                        <?php if (!empty($item->action_data)) : ?>
                            <div class="action-preview">
                                <strong><?php _e('Proposed Action:', 'ai-seo-manager'); ?></strong>
                                <code><?php echo esc_html(json_encode($item->action_data, JSON_PRETTY_PRINT)); ?></code>
                            </div>
                        <?php endif; ?>

                        <div class="approval-note-section" style="display:none;">
                            <label><?php _e('Note (optional):', 'ai-seo-manager'); ?></label>
                            <textarea class="approval-note" rows="2" placeholder="<?php esc_attr_e('Add a note about your decision...', 'ai-seo-manager'); ?>"></textarea>
                        </div>
                    </div>

                    <div class="approval-card-footer">
                        <div class="approval-actions">
                            <button class="button button-primary button-large approve-btn" data-id="<?php echo esc_attr($item->id); ?>">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php _e('Approve & Apply', 'ai-seo-manager'); ?>
                            </button>
                            <button class="button button-large reject-btn" data-id="<?php echo esc_attr($item->id); ?>">
                                <span class="dashicons dashicons-dismiss"></span>
                                <?php _e('Reject', 'ai-seo-manager'); ?>
                            </button>
                            <button class="button add-note-btn">
                                <?php _e('Add Note', 'ai-seo-manager'); ?>
                            </button>
                        </div>
                        <div class="approval-timestamp">
                            <?php printf(__('Created: %s', 'ai-seo-manager'), human_time_diff(strtotime($item->created_at), current_time('timestamp')) . ' ago'); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Add note toggle
    $('.add-note-btn').on('click', function() {
        $(this).closest('.approval-card').find('.approval-note-section').slideToggle();
    });

    // Approve
    $('.approve-btn').on('click', function() {
        const btn = $(this);
        const id = btn.data('id');
        const card = btn.closest('.approval-card');
        const note = card.find('.approval-note').val();

        btn.prop('disabled', true).text('<?php esc_js(_e('Approving...', 'ai-seo-manager')); ?>');

        $.post(ajaxurl, {
            action: 'ai_seo_approve_recommendation',
            nonce: aiSeoManager.nonce,
            recommendation_id: id,
            note: note
        }, function(response) {
            if (response.success) {
                card.fadeOut(300, function() { $(this).remove(); });
                alert(response.data.message || '<?php esc_js(_e('Recommendation approved!', 'ai-seo-manager')); ?>');
            } else {
                alert(response.data.message || '<?php esc_js(_e('Failed to approve', 'ai-seo-manager')); ?>');
                btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> <?php esc_js(_e('Approve & Apply', 'ai-seo-manager')); ?>');
            }
        });
    });

    // Reject
    $('.reject-btn').on('click', function() {
        const btn = $(this);
        const id = btn.data('id');
        const card = btn.closest('.approval-card');
        const note = card.find('.approval-note').val();

        if (!confirm('<?php esc_js(_e('Are you sure you want to reject this recommendation?', 'ai-seo-manager')); ?>')) {
            return;
        }

        btn.prop('disabled', true).text('<?php esc_js(_e('Rejecting...', 'ai-seo-manager')); ?>');

        $.post(ajaxurl, {
            action: 'ai_seo_reject_recommendation',
            nonce: aiSeoManager.nonce,
            recommendation_id: id,
            note: note
        }, function(response) {
            if (response.success) {
                card.fadeOut(300, function() { $(this).remove(); });
            } else {
                alert(response.data.message || '<?php esc_js(_e('Failed to reject', 'ai-seo-manager')); ?>');
                btn.prop('disabled', false).html('<span class="dashicons dashicons-dismiss"></span> <?php esc_js(_e('Reject', 'ai-seo-manager')); ?>');
            }
        });
    });
});
</script>
