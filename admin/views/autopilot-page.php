<?php
/**
 * Autopilot Page Template
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap ai-seo-manager-autopilot">
    <h1><?php _e('🚀 Autopilot Control Center', 'ai-seo-manager'); ?></h1>

    <div class="autopilot-overview">
        <div class="autopilot-status-large">
            <h2><?php _e('Status', 'ai-seo-manager'); ?></h2>
            <div class="status-indicator <?php echo $autopilot->is_enabled() ? 'active' : 'inactive'; ?>">
                <span class="status-light"></span>
                <span class="status-text">
                    <?php echo $autopilot->is_enabled() ? __('ACTIVE', 'ai-seo-manager') : __('INACTIVE', 'ai-seo-manager'); ?>
                </span>
            </div>
            <p class="mode-description">
                <strong><?php _e('Mode:', 'ai-seo-manager'); ?></strong>
                <?php echo esc_html(ucfirst($autopilot->get_mode())); ?>
            </p>
        </div>

        <div class="autopilot-stats-grid">
            <div class="stat-box">
                <div class="stat-value"><?php echo esc_html($stats['total_executed']); ?></div>
                <div class="stat-label"><?php _e('Total Executed', 'ai-seo-manager'); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo esc_html($stats['pending_approval']); ?></div>
                <div class="stat-label"><?php _e('Pending Approval', 'ai-seo-manager'); ?></div>
            </div>
            <div class="stat-box success">
                <div class="stat-value"><?php echo esc_html($stats['success_rate']); ?>%</div>
                <div class="stat-label"><?php _e('Success Rate', 'ai-seo-manager'); ?></div>
            </div>
        </div>
    </div>

    <div class="autopilot-info-cards">
        <div class="info-card">
            <h3><?php _e('⚙️ How Autopilot Works', 'ai-seo-manager'); ?></h3>
            <ol>
                <li><?php _e('AI analyzes your content and identifies SEO opportunities', 'ai-seo-manager'); ?></li>
                <li><?php _e('Generates specific, actionable recommendations', 'ai-seo-manager'); ?></li>
                <li><?php _e('In Approval mode: Waits for your confirmation', 'ai-seo-manager'); ?></li>
                <li><?php _e('In Auto mode: Applies safe changes automatically', 'ai-seo-manager'); ?></li>
                <li><?php _e('Logs all actions and maintains backups', 'ai-seo-manager'); ?></li>
            </ol>
        </div>

        <div class="info-card">
            <h3><?php _e('✨ Current Capabilities', 'ai-seo-manager'); ?></h3>
            <ul class="capabilities-list">
                <li>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Meta Description Optimization', 'ai-seo-manager'); ?>
                </li>
                <li>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Image ALT Text Generation', 'ai-seo-manager'); ?>
                </li>
                <li>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Heading Structure Optimization', 'ai-seo-manager'); ?>
                </li>
                <li>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Keyword Optimization Suggestions', 'ai-seo-manager'); ?>
                </li>
                <li>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Technical SEO Issue Detection', 'ai-seo-manager'); ?>
                </li>
            </ul>
        </div>

        <div class="info-card warning">
            <h3><?php _e('⚠️ Safety Features', 'ai-seo-manager'); ?></h3>
            <ul>
                <li><?php _e('All changes are backed up before modification', 'ai-seo-manager'); ?></li>
                <li><?php _e('AI confidence threshold prevents uncertain changes', 'ai-seo-manager'); ?></li>
                <li><?php _e('Critical/High priority changes always require approval', 'ai-seo-manager'); ?></li>
                <li><?php _e('Complete rollback capability', 'ai-seo-manager'); ?></li>
                <li><?php _e('Activity logging for full transparency', 'ai-seo-manager'); ?></li>
            </ul>
        </div>
    </div>

    <div class="autopilot-controls">
        <h2><?php _e('Quick Actions', 'ai-seo-manager'); ?></h2>
        <div class="control-buttons">
            <a href="<?php echo admin_url('admin.php?page=ai-seo-manager-settings&tab=autopilot'); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Configure Autopilot Settings', 'ai-seo-manager'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=ai-seo-manager-approvals'); ?>" class="button button-secondary button-hero">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Review Pending Approvals', 'ai-seo-manager'); ?>
            </a>
        </div>
    </div>

    <div class="autopilot-recommendations">
        <h2><?php _e('💡 Recommendations', 'ai-seo-manager'); ?></h2>
        <div class="recommendation-box">
            <h4><?php _e('For Best Results:', 'ai-seo-manager'); ?></h4>
            <ul>
                <li><?php _e('Start with "Approval" mode to learn how Autopilot works', 'ai-seo-manager'); ?></li>
                <li><?php _e('Enable "Meta Description" and "ALT Texts" first (safest options)', 'ai-seo-manager'); ?></li>
                <li><?php _e('Review approved changes regularly to fine-tune AI recommendations', 'ai-seo-manager'); ?></li>
                <li><?php _e('Monitor success rate and adjust AI confidence threshold if needed', 'ai-seo-manager'); ?></li>
                <li><?php _e('Consider "Auto" mode only after reviewing 20+ approved recommendations', 'ai-seo-manager'); ?></li>
            </ul>
        </div>
    </div>
</div>
