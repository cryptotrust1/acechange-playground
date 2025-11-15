<?php
/**
 * Dashboard Page Template
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap ai-seo-manager-dashboard">
    <h1><?php _e('AI SEO Manager Dashboard', 'ai-seo-manager'); ?></h1>

    <!-- Stats Overview -->
    <div class="ai-seo-stats-grid">
        <div class="stat-card">
            <h3><?php _e('Pending Recommendations', 'ai-seo-manager'); ?></h3>
            <div class="stat-number"><?php echo esc_html($stats['pending']); ?></div>
        </div>

        <div class="stat-card awaiting">
            <h3><?php _e('Awaiting Approval', 'ai-seo-manager'); ?></h3>
            <div class="stat-number"><?php echo esc_html($stats['awaiting_approval']); ?></div>
            <a href="<?php echo admin_url('admin.php?page=ai-seo-manager-approvals'); ?>" class="stat-link">
                <?php _e('Review Now →', 'ai-seo-manager'); ?>
            </a>
        </div>

        <div class="stat-card completed">
            <h3><?php _e('Completed', 'ai-seo-manager'); ?></h3>
            <div class="stat-number"><?php echo esc_html($stats['completed']); ?></div>
        </div>

        <div class="stat-card api-usage">
            <h3><?php _e('API Calls Today', 'ai-seo-manager'); ?></h3>
            <div class="stat-number"><?php echo esc_html($api_usage['calls_today']); ?></div>
            <div class="stat-sub"><?php printf(__('Total: %d', 'ai-seo-manager'), $api_usage['total_calls']); ?></div>
        </div>
    </div>

    <!-- Autopilot Status -->
    <div class="autopilot-status-card">
        <h2><?php _e('🤖 Autopilot Status', 'ai-seo-manager'); ?></h2>
        <div class="autopilot-status-content">
            <div class="status-badge <?php echo $autopilot->is_enabled() ? 'active' : 'inactive'; ?>">
                <?php echo $autopilot->is_enabled() ? __('Active', 'ai-seo-manager') : __('Inactive', 'ai-seo-manager'); ?>
            </div>
            <div class="autopilot-mode">
                <strong><?php _e('Mode:', 'ai-seo-manager'); ?></strong>
                <?php echo esc_html(ucfirst($autopilot->get_mode())); ?>
            </div>
            <div class="autopilot-stats-mini">
                <span><?php printf(__('Executed: %d', 'ai-seo-manager'), $autopilot_stats['total_executed']); ?></span>
                <span><?php printf(__('Success Rate: %s%%', 'ai-seo-manager'), $autopilot_stats['success_rate']); ?></span>
            </div>
            <a href="<?php echo admin_url('admin.php?page=ai-seo-manager-autopilot'); ?>" class="button button-primary">
                <?php _e('Configure Autopilot', 'ai-seo-manager'); ?>
            </a>
        </div>
    </div>

    <div class="ai-seo-content-grid">
        <!-- Pending Approvals -->
        <div class="ai-seo-section">
            <h2><?php _e('⏳ Pending Approvals', 'ai-seo-manager'); ?></h2>

            <?php if (empty($pending_approvals)) : ?>
                <p class="no-items"><?php _e('No pending approvals', 'ai-seo-manager'); ?></p>
            <?php else : ?>
                <div class="approvals-list">
                    <?php foreach ($pending_approvals as $approval) : ?>
                        <div class="approval-item priority-<?php echo esc_attr($approval->priority); ?>">
                            <div class="approval-header">
                                <h4><?php echo esc_html($approval->title); ?></h4>
                                <span class="priority-badge"><?php echo esc_html(ucfirst($approval->priority)); ?></span>
                            </div>
                            <p><?php echo esc_html($approval->description); ?></p>
                            <?php if ($approval->post_title) : ?>
                                <div class="approval-post">
                                    <?php printf(__('Post: %s', 'ai-seo-manager'), esc_html($approval->post_title)); ?>
                                </div>
                            <?php endif; ?>
                            <div class="approval-actions">
                                <button class="button button-primary approve-btn" data-id="<?php echo esc_attr($approval->id); ?>">
                                    <?php _e('✓ Approve', 'ai-seo-manager'); ?>
                                </button>
                                <button class="button reject-btn" data-id="<?php echo esc_attr($approval->id); ?>">
                                    <?php _e('✗ Reject', 'ai-seo-manager'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <a href="<?php echo admin_url('admin.php?page=ai-seo-manager-approvals'); ?>" class="button">
                    <?php _e('View All Approvals →', 'ai-seo-manager'); ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- Recent Recommendations -->
        <div class="ai-seo-section">
            <h2><?php _e('💡 Recent Recommendations', 'ai-seo-manager'); ?></h2>

            <?php if (empty($recent_recommendations)) : ?>
                <p class="no-items"><?php _e('No recommendations yet', 'ai-seo-manager'); ?></p>
            <?php else : ?>
                <div class="recommendations-list">
                    <?php foreach (array_slice($recent_recommendations, 0, 5) as $rec) : ?>
                        <div class="recommendation-item">
                            <div class="rec-icon"><?php echo ai_seo_get_recommendation_icon($rec->recommendation_type); ?></div>
                            <div class="rec-content">
                                <strong><?php echo esc_html($rec->title); ?></strong>
                                <small><?php echo esc_html($rec->recommendation_type); ?></small>
                            </div>
                            <div class="rec-confidence">
                                <?php printf(__('%d%% confident', 'ai-seo-manager'), round($rec->ai_confidence * 100)); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <a href="<?php echo admin_url('admin.php?page=ai-seo-manager-recommendations'); ?>" class="button">
                    <?php _e('View All Recommendations →', 'ai-seo-manager'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2><?php _e('⚡ Quick Actions', 'ai-seo-manager'); ?></h2>
        <div class="action-buttons">
            <a href="<?php echo admin_url('admin.php?page=ai-seo-manager-approvals'); ?>" class="action-btn">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Review Approvals', 'ai-seo-manager'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=ai-seo-manager-autopilot'); ?>" class="action-btn">
                <span class="dashicons dashicons-controls-play"></span>
                <?php _e('Configure Autopilot', 'ai-seo-manager'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=ai-seo-manager-settings'); ?>" class="action-btn">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Plugin Settings', 'ai-seo-manager'); ?>
            </a>
        </div>
    </div>
</div>

<?php
/**
 * Helper function for recommendation icons
 *
 * @param string $type Recommendation type
 * @return string Icon emoji
 */
function ai_seo_get_recommendation_icon($type) {
    $icons = array(
        'meta_optimization' => '📝',
        'keyword_optimization' => '🔑',
        'image_optimization' => '🖼️',
        'content_structure' => '📊',
        'link_optimization' => '🔗',
        'technical_seo' => '⚙️',
        'search_opportunity' => '🎯',
        'general' => '💡',
    );
    return isset($icons[$type]) ? esc_html($icons[$type]) : esc_html($icons['general']);
}
?>
