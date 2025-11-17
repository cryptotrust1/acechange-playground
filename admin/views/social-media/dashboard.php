<?php
/**
 * Social Media Dashboard View
 *
 * @var array $manager_stats Manager statistics
 * @var array $queue_stats Queue statistics
 * @var array $analytics_stats Analytics statistics
 * @var array $recent_posts Recent posts
 * @var array $upcoming_posts Upcoming scheduled posts
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ai-seo-social-dashboard">
    <h1><?php _e('Social Media Manager', 'ai-seo-manager'); ?></h1>

    <div class="ai-seo-social-stats-grid">
        <!-- Database Stats -->
        <div class="ai-seo-stat-card">
            <h3><?php _e('Database', 'ai-seo-manager'); ?></h3>
            <div class="stat-number"><?php echo esc_html($manager_stats['database']['total_posts'] ?? 0); ?></div>
            <div class="stat-label"><?php _e('Total Posts', 'ai-seo-manager'); ?></div>
            <div class="stat-meta">
                <span><?php echo esc_html($manager_stats['database']['published_posts'] ?? 0); ?> <?php _e('published', 'ai-seo-manager'); ?></span>
                <span><?php echo esc_html($manager_stats['database']['total_accounts'] ?? 0); ?> <?php _e('accounts', 'ai-seo-manager'); ?></span>
            </div>
        </div>

        <!-- Queue Stats -->
        <div class="ai-seo-stat-card">
            <h3><?php _e('Queue', 'ai-seo-manager'); ?></h3>
            <div class="stat-number"><?php echo esc_html($queue_stats['pending'] ?? 0); ?></div>
            <div class="stat-label"><?php _e('Pending', 'ai-seo-manager'); ?></div>
            <div class="stat-meta">
                <?php if (($queue_stats['overdue'] ?? 0) > 0): ?>
                    <span class="warning"><?php echo esc_html($queue_stats['overdue']); ?> <?php _e('overdue', 'ai-seo-manager'); ?></span>
                <?php endif; ?>
                <span><?php echo esc_html($queue_stats['completed_today'] ?? 0); ?> <?php _e('completed today', 'ai-seo-manager'); ?></span>
            </div>
        </div>

        <!-- Platforms -->
        <div class="ai-seo-stat-card">
            <h3><?php _e('Platforms', 'ai-seo-manager'); ?></h3>
            <div class="stat-number"><?php echo esc_html($manager_stats['platforms']['active'] ?? 0); ?></div>
            <div class="stat-label"><?php _e('Active', 'ai-seo-manager'); ?></div>
            <div class="stat-meta">
                <span><?php echo esc_html($manager_stats['platforms']['total'] ?? 0); ?> <?php _e('total', 'ai-seo-manager'); ?></span>
            </div>
        </div>

        <!-- Analytics -->
        <div class="ai-seo-stat-card">
            <h3><?php _e('Analytics', 'ai-seo-manager'); ?></h3>
            <div class="stat-number"><?php echo esc_html($analytics_stats['total_records'] ?? 0); ?></div>
            <div class="stat-label"><?php _e('Records', 'ai-seo-manager'); ?></div>
            <div class="stat-meta">
                <span><?php echo esc_html($analytics_stats['platforms_tracked'] ?? 0); ?> <?php _e('platforms', 'ai-seo-manager'); ?></span>
            </div>
        </div>
    </div>

    <div class="ai-seo-social-columns">
        <!-- Recent Posts -->
        <div class="ai-seo-social-column">
            <div class="ai-seo-card">
                <h2><?php _e('Recent Posts', 'ai-seo-manager'); ?></h2>
                <?php if (!empty($recent_posts)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Platform', 'ai-seo-manager'); ?></th>
                                <th><?php _e('Content', 'ai-seo-manager'); ?></th>
                                <th><?php _e('Status', 'ai-seo-manager'); ?></th>
                                <th><?php _e('Date', 'ai-seo-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_posts as $post): ?>
                                <tr>
                                    <td>
                                        <span class="platform-badge platform-<?php echo esc_attr($post->platform); ?>">
                                            <?php echo esc_html(ucfirst($post->platform)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo esc_html(wp_trim_words($post->content, 10)); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($post->status); ?>">
                                            <?php echo esc_html($post->status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(mysql2date('M j, Y H:i', $post->created_at)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-items"><?php _e('No posts yet.', 'ai-seo-manager'); ?></p>
                <?php endif; ?>

                <p class="card-footer">
                    <a href="<?php echo admin_url('admin.php?page=ai-seo-social-composer'); ?>" class="button button-primary">
                        <?php _e('Create New Post', 'ai-seo-manager'); ?>
                    </a>
                </p>
            </div>
        </div>

        <!-- Upcoming Scheduled Posts -->
        <div class="ai-seo-social-column">
            <div class="ai-seo-card">
                <h2><?php _e('Upcoming Scheduled', 'ai-seo-manager'); ?></h2>
                <?php if (!empty($upcoming_posts)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Platform', 'ai-seo-manager'); ?></th>
                                <th><?php _e('Content', 'ai-seo-manager'); ?></th>
                                <th><?php _e('Scheduled For', 'ai-seo-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_posts as $post): ?>
                                <tr>
                                    <td>
                                        <span class="platform-badge platform-<?php echo esc_attr($post->platform); ?>">
                                            <?php echo esc_html(ucfirst($post->platform)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(wp_trim_words($post->content, 8)); ?></td>
                                    <td><?php echo esc_html(mysql2date('M j, Y H:i', $post->scheduled_for)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-items"><?php _e('No scheduled posts.', 'ai-seo-manager'); ?></p>
                <?php endif; ?>

                <p class="card-footer">
                    <a href="<?php echo admin_url('admin.php?page=ai-seo-social-calendar'); ?>" class="button">
                        <?php _e('View Calendar', 'ai-seo-manager'); ?>
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="ai-seo-card">
        <h2><?php _e('Quick Actions', 'ai-seo-manager'); ?></h2>
        <div class="quick-actions">
            <a href="<?php echo admin_url('admin.php?page=ai-seo-social-composer'); ?>" class="button button-large button-primary">
                <span class="dashicons dashicons-edit"></span>
                <?php _e('Create Post', 'ai-seo-manager'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=ai-seo-social-calendar'); ?>" class="button button-large">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e('View Calendar', 'ai-seo-manager'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=ai-seo-social-analytics'); ?>" class="button button-large">
                <span class="dashicons dashicons-chart-line"></span>
                <?php _e('Analytics', 'ai-seo-manager'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=ai-seo-social-accounts'); ?>" class="button button-large">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Manage Accounts', 'ai-seo-manager'); ?>
            </a>
        </div>
    </div>
</div>

<style>
.ai-seo-social-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.ai-seo-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.ai-seo-stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #646970;
}

.ai-seo-stat-card .stat-number {
    font-size: 32px;
    font-weight: 600;
    color: #1d2327;
    line-height: 1;
}

.ai-seo-stat-card .stat-label {
    font-size: 12px;
    color: #646970;
    margin: 5px 0;
}

.ai-seo-stat-card .stat-meta {
    font-size: 11px;
    color: #787c82;
    margin-top: 10px;
}

.ai-seo-stat-card .stat-meta span {
    display: inline-block;
    margin-right: 10px;
}

.ai-seo-stat-card .stat-meta .warning {
    color: #d63638;
}

.ai-seo-social-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

.ai-seo-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.ai-seo-card h2 {
    margin-top: 0;
    border-bottom: 1px solid #f0f0f1;
    padding-bottom: 10px;
}

.ai-seo-card .no-items {
    text-align: center;
    color: #646970;
    padding: 40px 0;
}

.ai-seo-card .card-footer {
    margin: 15px 0 0 0;
    padding-top: 15px;
    border-top: 1px solid #f0f0f1;
    text-align: center;
}

.platform-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.platform-telegram { background: #0088cc; color: #fff; }
.platform-facebook { background: #1877f2; color: #fff; }
.platform-instagram { background: #e4405f; color: #fff; }
.platform-twitter { background: #1da1f2; color: #fff; }
.platform-linkedin { background: #0077b5; color: #fff; }
.platform-youtube { background: #ff0000; color: #fff; }
.platform-tiktok { background: #000000; color: #fff; }

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
}

.status-published { background: #00a32a; color: #fff; }
.status-scheduled { background: #dba617; color: #fff; }
.status-draft { background: #646970; color: #fff; }
.status-failed { background: #d63638; color: #fff; }

.quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.quick-actions .button {
    display: flex;
    align-items: center;
    gap: 5px;
}

@media (max-width: 782px) {
    .ai-seo-social-columns {
        grid-template-columns: 1fr;
    }
}
</style>
