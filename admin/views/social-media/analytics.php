<?php
/**
 * Social Media Analytics View
 * @var array $report Analytics report
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1><?php _e('Social Media Analytics', 'ai-seo-manager'); ?></h1>

    <?php if (!empty($report)): ?>
        <h2><?php _e('Platform Summary', 'ai-seo-manager'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Platform', 'ai-seo-manager'); ?></th>
                    <th><?php _e('Posts', 'ai-seo-manager'); ?></th>
                    <th><?php _e('Impressions', 'ai-seo-manager'); ?></th>
                    <th><?php _e('Engagement', 'ai-seo-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report['platform_summary'] as $platform): ?>
                    <tr>
                        <td><?php echo esc_html(ucfirst($platform['platform'])); ?></td>
                        <td><?php echo esc_html($platform['total_posts']); ?></td>
                        <td><?php echo esc_html(number_format($platform['total_impressions'])); ?></td>
                        <td><?php echo esc_html(round($platform['avg_engagement_rate'], 2)); ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
