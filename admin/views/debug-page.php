<?php
/**
 * Debug Panel View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ai-seo-debug-panel">
    <h1><?php esc_html_e('AI SEO Manager - Debug Logs', 'ai-seo-manager'); ?></h1>

    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                switch ($_GET['message']) {
                    case 'logs_cleared':
                        esc_html_e('All debug logs have been cleared.', 'ai-seo-manager');
                        break;
                    case 'old_logs_cleaned':
                        esc_html_e('Old debug logs have been cleaned.', 'ai-seo-manager');
                        break;
                    case 'performance_reset':
                        esc_html_e('Performance statistics have been reset.', 'ai-seo-manager');
                        break;
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Debug Status -->
    <div class="ai-seo-debug-status">
        <div class="postbox">
            <div class="inside">
                <h3><?php esc_html_e('Debug Status', 'ai-seo-manager'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Debug Mode', 'ai-seo-manager'); ?></th>
                        <td>
                            <?php if ($this->logger->is_debug_mode()): ?>
                                <span class="badge badge-success"><?php esc_html_e('ACTIVE', 'ai-seo-manager'); ?></span>
                                <p class="description"><?php esc_html_e('AI_SEO_DEBUG constant is enabled', 'ai-seo-manager'); ?></p>
                            <?php elseif ($this->logger->is_enabled()): ?>
                                <span class="badge badge-info"><?php esc_html_e('WP_DEBUG', 'ai-seo-manager'); ?></span>
                                <p class="description"><?php esc_html_e('WordPress debug mode is active', 'ai-seo-manager'); ?></p>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?php esc_html_e('DISABLED', 'ai-seo-manager'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Performance Monitoring', 'ai-seo-manager'); ?></th>
                        <td>
                            <?php if ($this->performance->is_enabled()): ?>
                                <span class="badge badge-success"><?php esc_html_e('ACTIVE', 'ai-seo-manager'); ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?php esc_html_e('DISABLED', 'ai-seo-manager'); ?></span>
                                <p class="description"><?php esc_html_e('Enable AI_SEO_DEBUG to activate performance monitoring', 'ai-seo-manager'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Debug Level', 'ai-seo-manager'); ?></th>
                        <td>
                            <code><?php echo esc_html(defined('AI_SEO_DEBUG_LEVEL') ? AI_SEO_DEBUG_LEVEL : 'INFO'); ?></code>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="ai-seo-debug-stats">
        <h3><?php esc_html_e('Statistics', 'ai-seo-manager'); ?></h3>
        <div class="ai-seo-stats-grid">
            <div class="stat-box">
                <div class="stat-value"><?php echo esc_html(number_format($stats['total'])); ?></div>
                <div class="stat-label"><?php esc_html_e('Total Logs', 'ai-seo-manager'); ?></div>
            </div>
            <div class="stat-box stat-error">
                <div class="stat-value"><?php echo esc_html(number_format($stats['by_level']['error'])); ?></div>
                <div class="stat-label"><?php esc_html_e('Errors', 'ai-seo-manager'); ?></div>
            </div>
            <div class="stat-box stat-warning">
                <div class="stat-value"><?php echo esc_html(number_format($stats['by_level']['warning'])); ?></div>
                <div class="stat-label"><?php esc_html_e('Warnings', 'ai-seo-manager'); ?></div>
            </div>
            <div class="stat-box stat-info">
                <div class="stat-value"><?php echo esc_html(number_format($stats['by_level']['info'])); ?></div>
                <div class="stat-label"><?php esc_html_e('Info', 'ai-seo-manager'); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo esc_html(number_format($stats['today'])); ?></div>
                <div class="stat-label"><?php esc_html_e('Today', 'ai-seo-manager'); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo esc_html(number_format($stats['this_week'])); ?></div>
                <div class="stat-label"><?php esc_html_e('This Week', 'ai-seo-manager'); ?></div>
            </div>
        </div>
    </div>

    <!-- Performance Stats -->
    <?php if (!empty($api_stats)): ?>
    <div class="ai-seo-performance-stats">
        <h3><?php esc_html_e('API Performance', 'ai-seo-manager'); ?></h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Provider', 'ai-seo-manager'); ?></th>
                    <th><?php esc_html_e('Total Calls', 'ai-seo-manager'); ?></th>
                    <th><?php esc_html_e('Failed', 'ai-seo-manager'); ?></th>
                    <th><?php esc_html_e('Avg Duration', 'ai-seo-manager'); ?></th>
                    <th><?php esc_html_e('Success Rate', 'ai-seo-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($api_stats as $provider => $stat): ?>
                <tr>
                    <td><strong><?php echo esc_html(ucfirst($provider)); ?></strong></td>
                    <td><?php echo esc_html(number_format($stat['total_calls'])); ?></td>
                    <td class="<?php echo $stat['failed_calls'] > 0 ? 'text-error' : ''; ?>">
                        <?php echo esc_html(number_format($stat['failed_calls'])); ?>
                    </td>
                    <td><?php echo esc_html(round($stat['avg_duration'], 2)) . 's'; ?></td>
                    <td>
                        <?php
                        $success_rate = $stat['total_calls'] > 0
                            ? round((($stat['total_calls'] - $stat['failed_calls']) / $stat['total_calls']) * 100, 2)
                            : 0;
                        $badge_class = $success_rate >= 95 ? 'success' : ($success_rate >= 80 ? 'warning' : 'error');
                        ?>
                        <span class="badge badge-<?php echo esc_attr($badge_class); ?>">
                            <?php echo esc_html($success_rate) . '%'; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Memory Info -->
    <div class="ai-seo-memory-info">
        <h3><?php esc_html_e('Memory Usage', 'ai-seo-manager'); ?></h3>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Current Memory Usage', 'ai-seo-manager'); ?></th>
                <td><code><?php echo esc_html($memory_info['current_formatted']); ?></code></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Peak Memory Usage', 'ai-seo-manager'); ?></th>
                <td><code><?php echo esc_html($memory_info['peak_formatted']); ?></code></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Memory Limit', 'ai-seo-manager'); ?></th>
                <td><code><?php echo esc_html($memory_info['limit']); ?></code></td>
            </tr>
        </table>
    </div>

    <!-- Filters and Actions -->
    <div class="ai-seo-debug-controls">
        <div class="tablenav top">
            <div class="alignleft actions">
                <!-- Level Filter -->
                <select name="level" id="log-level-filter">
                    <option value=""><?php esc_html_e('All Levels', 'ai-seo-manager'); ?></option>
                    <option value="error" <?php selected($level, 'error'); ?>><?php esc_html_e('Errors', 'ai-seo-manager'); ?></option>
                    <option value="warning" <?php selected($level, 'warning'); ?>><?php esc_html_e('Warnings', 'ai-seo-manager'); ?></option>
                    <option value="info" <?php selected($level, 'info'); ?>><?php esc_html_e('Info', 'ai-seo-manager'); ?></option>
                    <option value="debug" <?php selected($level, 'debug'); ?>><?php esc_html_e('Debug', 'ai-seo-manager'); ?></option>
                </select>

                <!-- Search -->
                <input type="search" id="log-search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search logs...', 'ai-seo-manager'); ?>">

                <button type="button" class="button" id="filter-logs"><?php esc_html_e('Filter', 'ai-seo-manager'); ?></button>
            </div>

            <div class="alignright actions">
                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('page' => 'ai-seo-manager-debug', 'ai_seo_debug_action' => 'export_logs')), 'ai_seo_debug_action')); ?>" class="button">
                    <?php esc_html_e('Export CSV', 'ai-seo-manager'); ?>
                </a>

                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('page' => 'ai-seo-manager-debug', 'ai_seo_debug_action' => 'clean_old_logs', 'days' => 30)), 'ai_seo_debug_action')); ?>" class="button" onclick="return confirm('<?php esc_attr_e('Clean logs older than 30 days?', 'ai-seo-manager'); ?>');">
                    <?php esc_html_e('Clean Old Logs', 'ai-seo-manager'); ?>
                </a>

                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('page' => 'ai-seo-manager-debug', 'ai_seo_debug_action' => 'clear_logs')), 'ai_seo_debug_action')); ?>" class="button button-danger" onclick="return confirm('<?php esc_attr_e('Are you sure you want to clear all logs?', 'ai-seo-manager'); ?>');">
                    <?php esc_html_e('Clear All Logs', 'ai-seo-manager'); ?>
                </a>

                <?php if (!empty($api_stats)): ?>
                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('page' => 'ai-seo-manager-debug', 'ai_seo_debug_action' => 'reset_performance')), 'ai_seo_debug_action')); ?>" class="button" onclick="return confirm('<?php esc_attr_e('Reset performance statistics?', 'ai-seo-manager'); ?>');">
                    <?php esc_html_e('Reset Performance Stats', 'ai-seo-manager'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="ai-seo-debug-logs">
        <h3><?php esc_html_e('Recent Logs', 'ai-seo-manager'); ?></h3>

        <?php if (empty($logs)): ?>
            <p><?php esc_html_e('No debug logs found.', 'ai-seo-manager'); ?></p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 80px;"><?php esc_html_e('Level', 'ai-seo-manager'); ?></th>
                        <th style="width: 150px;"><?php esc_html_e('Timestamp', 'ai-seo-manager'); ?></th>
                        <th><?php esc_html_e('Message', 'ai-seo-manager'); ?></th>
                        <th style="width: 100px;"><?php esc_html_e('Details', 'ai-seo-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <?php
                        $data = maybe_unserialize($log->data);
                        $log_level = isset($data['level']) ? strtoupper($data['level']) : AI_SEO_Manager_Debug_Panel::format_log_type($log->log_type);
                        ?>
                        <tr>
                            <td>
                                <span class="badge badge-<?php echo esc_attr(AI_SEO_Manager_Debug_Panel::get_level_badge_class($log_level)); ?>">
                                    <?php echo esc_html($log_level); ?>
                                </span>
                            </td>
                            <td>
                                <small><?php echo esc_html($log->created_at); ?></small>
                            </td>
                            <td>
                                <strong><?php echo esc_html($log->message); ?></strong>
                                <?php if (!empty($data['context'])): ?>
                                    <div class="log-context">
                                        <small><?php echo esc_html(wp_json_encode($data['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></small>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="button button-small toggle-log-details" data-log-id="<?php echo esc_attr($log->id); ?>">
                                    <?php esc_html_e('Details', 'ai-seo-manager'); ?>
                                </button>
                                <div class="log-details" id="log-details-<?php echo esc_attr($log->id); ?>" style="display: none;">
                                    <pre><?php echo esc_html(wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php
            $total_pages = ceil($total_logs / $per_page);
            if ($total_pages > 1):
            ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $page,
                    ));
                    ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Debug Configuration -->
    <div class="ai-seo-debug-config">
        <h3><?php esc_html_e('Configuration', 'ai-seo-manager'); ?></h3>
        <p><?php esc_html_e('To enable debug mode, add these constants to your wp-config.php:', 'ai-seo-manager'); ?></p>
        <pre class="code-block">
// Povoliť AI SEO Manager debug mód
define('AI_SEO_DEBUG', true);

// Nastaviť debug level (ERROR, WARNING, INFO, DEBUG)
define('AI_SEO_DEBUG_LEVEL', 'DEBUG');

// Povoliť WordPress debug logging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
        </pre>
    </div>
</div>

<style>
.ai-seo-debug-panel {
    margin: 20px 20px 20px 0;
}

.ai-seo-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.stat-box {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.stat-box.stat-error {
    border-left: 4px solid #d63638;
}

.stat-box.stat-warning {
    border-left: 4px solid #dba617;
}

.stat-box.stat-info {
    border-left: 4px solid #2271b1;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #1d2327;
}

.stat-label {
    color: #646970;
    font-size: 14px;
    margin-top: 5px;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-success {
    background: #00a32a;
    color: #fff;
}

.badge-error {
    background: #d63638;
    color: #fff;
}

.badge-warning {
    background: #dba617;
    color: #fff;
}

.badge-info {
    background: #2271b1;
    color: #fff;
}

.badge-secondary {
    background: #646970;
    color: #fff;
}

.log-context {
    margin-top: 5px;
    padding: 8px;
    background: #f6f7f7;
    border-radius: 3px;
}

.log-details {
    margin-top: 10px;
    padding: 10px;
    background: #f6f7f7;
    border: 1px solid #dcdcde;
    border-radius: 3px;
    max-height: 300px;
    overflow: auto;
}

.log-details pre {
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.code-block {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 15px;
    border-radius: 4px;
    overflow-x: auto;
}

.button-danger {
    background: #d63638;
    border-color: #d63638;
    color: #fff;
}

.button-danger:hover {
    background: #b32d2e;
    border-color: #b32d2e;
    color: #fff;
}

.text-error {
    color: #d63638;
    font-weight: 600;
}

.ai-seo-debug-controls {
    margin: 20px 0;
}

#log-search {
    min-width: 300px;
}

.ai-seo-debug-status .postbox,
.ai-seo-performance-stats,
.ai-seo-memory-info,
.ai-seo-debug-config {
    margin-bottom: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle log details
    $('.toggle-log-details').on('click', function() {
        var logId = $(this).data('log-id');
        $('#log-details-' + logId).slideToggle();
    });

    // Filter logs
    $('#filter-logs').on('click', function() {
        var level = $('#log-level-filter').val();
        var search = $('#log-search').val();

        var url = new URL(window.location.href);
        url.searchParams.set('page', 'ai-seo-manager-debug');
        if (level) {
            url.searchParams.set('level', level);
        } else {
            url.searchParams.delete('level');
        }
        if (search) {
            url.searchParams.set('search', search);
        } else {
            url.searchParams.delete('search');
        }
        url.searchParams.delete('paged');

        window.location.href = url.toString();
    });

    // Enter key support for search
    $('#log-search').on('keypress', function(e) {
        if (e.which === 13) {
            $('#filter-logs').click();
        }
    });
});
</script>
