<?php
/**
 * Social Media Accounts Management View
 * @var array $accounts All accounts
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1><?php _e('Social Media Accounts', 'ai-seo-manager'); ?></h1>

    <p><?php _e('Manage your connected social media accounts here.', 'ai-seo-manager'); ?></p>

    <?php settings_errors('ai_seo_social_messages'); ?>

    <?php if (!empty($accounts)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Platform', 'ai-seo-manager'); ?></th>
                    <th><?php _e('Account Name', 'ai-seo-manager'); ?></th>
                    <th><?php _e('Status', 'ai-seo-manager'); ?></th>
                    <th><?php _e('Actions', 'ai-seo-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $account): ?>
                    <tr>
                        <td><?php echo esc_html(ucfirst($account->platform)); ?></td>
                        <td><?php echo esc_html($account->account_name); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($account->status); ?>">
                                <?php echo esc_html($account->status); ?>
                            </span>
                        </td>
                        <td>
                            <a href="#" class="button button-small"><?php _e('Edit', 'ai-seo-manager'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p><?php _e('No accounts configured yet.', 'ai-seo-manager'); ?></p>
    <?php endif; ?>

    <p>
        <a href="#" class="button button-primary"><?php _e('Add New Account', 'ai-seo-manager'); ?></a>
    </p>
</div>
