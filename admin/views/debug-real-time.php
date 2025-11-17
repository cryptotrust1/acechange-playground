<?php
/**
 * Real-Time Debug Dashboard
 * Live monitoring všetkých funkcií a operácií
 */

if (!defined('ABSPATH')) {
    exit;
}

// Zapni debug mode ak nie je
if (!defined('AI_SEO_DEBUG')) {
    define('AI_SEO_DEBUG', true);
}
if (!defined('AI_SEO_DEBUG_LEVEL')) {
    define('AI_SEO_DEBUG_LEVEL', 'DEBUG');
}

$logger = AI_SEO_Manager_Debug_Logger::get_instance();
$performance = AI_SEO_Manager_Performance_Monitor::get_instance();

// Get latest logs
$recent_logs = $logger->get_recent_logs(100);
$errors = $logger->get_errors(50);
$warnings = $logger->get_warnings(50);

// Performance stats
$perf_stats = $performance->get_stats();
?>

<div class="wrap ai-seo-debug-realtime">
    <h1>
        🔧 AI SEO Manager - Real-Time Debug Dashboard
        <span class="debug-status <?php echo AI_SEO_DEBUG ? 'active' : 'inactive'; ?>">
            <?php echo AI_SEO_DEBUG ? '● DEBUG ACTIVE' : '○ DEBUG INACTIVE'; ?>
        </span>
    </h1>

    <!-- Quick Actions -->
    <div class="debug-quick-actions">
        <button class="button button-primary" id="test-all-functions">
            🧪 Test All Functions
        </button>
        <button class="button" id="clear-all-logs">
            🗑️ Clear All Logs
        </button>
        <button class="button" id="export-debug-report">
            📥 Export Debug Report
        </button>
        <button class="button" id="refresh-logs">
            🔄 Refresh Logs
        </button>
        <label>
            <input type="checkbox" id="auto-refresh" checked>
            Auto-refresh (5s)
        </label>
    </div>

    <!-- Stats Overview -->
    <div class="debug-stats-grid">
        <div class="stat-card errors">
            <h3>❌ Errors</h3>
            <div class="stat-number"><?php echo count($errors); ?></div>
            <div class="stat-label">Last 24h</div>
        </div>
        <div class="stat-card warnings">
            <h3>⚠️ Warnings</h3>
            <div class="stat-number"><?php echo count($warnings); ?></div>
            <div class="stat-label">Last 24h</div>
        </div>
        <div class="stat-card logs">
            <h3>📝 Total Logs</h3>
            <div class="stat-number"><?php echo count($recent_logs); ?></div>
            <div class="stat-label">Last 100 entries</div>
        </div>
        <div class="stat-card performance">
            <h3>⚡ Avg Response</h3>
            <div class="stat-number">
                <?php echo isset($perf_stats['avg_duration']) ? round($perf_stats['avg_duration'], 2) : 0; ?>ms
            </div>
            <div class="stat-label">API calls</div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <h2 class="nav-tab-wrapper">
        <a href="#live-logs" class="nav-tab nav-tab-active">🔴 Live Logs</a>
        <a href="#errors-tab" class="nav-tab">❌ Errors (<?php echo count($errors); ?>)</a>
        <a href="#warnings-tab" class="nav-tab">⚠️ Warnings (<?php echo count($warnings); ?>)</a>
        <a href="#function-tests" class="nav-tab">🧪 Function Tests</a>
        <a href="#performance-tab" class="nav-tab">⚡ Performance</a>
        <a href="#social-debug" class="nav-tab">📱 Social Media</a>
        <a href="#settings-tab" class="nav-tab">⚙️ Settings</a>
    </h2>

    <!-- Live Logs Tab -->
    <div id="live-logs" class="tab-content active">
        <div class="debug-controls">
            <select id="log-level-filter">
                <option value="">All Levels</option>
                <option value="ERROR">Errors Only</option>
                <option value="WARNING">Warnings Only</option>
                <option value="INFO">Info Only</option>
                <option value="DEBUG">Debug Only</option>
            </select>
            <input type="text" id="log-search" placeholder="Search logs...">
        </div>

        <div class="live-log-container" id="live-log-output">
            <?php if (empty($recent_logs)): ?>
                <div class="no-logs">
                    <p>📭 No logs yet. Start using the plugin to see logs appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($recent_logs as $log): ?>
                    <div class="log-entry log-<?php echo strtolower($log->level); ?>" data-level="<?php echo $log->level; ?>">
                        <span class="log-time"><?php echo $log->created_at; ?></span>
                        <span class="log-level log-level-<?php echo strtolower($log->level); ?>">
                            <?php echo $log->level; ?>
                        </span>
                        <span class="log-message"><?php echo esc_html($log->message); ?></span>
                        <?php if (!empty($log->context)): ?>
                            <button class="toggle-context" data-target="context-<?php echo $log->id; ?>">
                                📋 Context
                            </button>
                            <div class="log-context" id="context-<?php echo $log->id; ?>" style="display:none;">
                                <pre><?php echo esc_html(json_encode(json_decode($log->context), JSON_PRETTY_PRINT)); ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Errors Tab -->
    <div id="errors-tab" class="tab-content">
        <h3>❌ Recent Errors</h3>
        <?php if (empty($errors)): ?>
            <p class="success-message">✅ No errors found! Everything is working correctly.</p>
        <?php else: ?>
            <div class="error-list">
                <?php foreach ($errors as $error): ?>
                    <div class="error-item">
                        <div class="error-header">
                            <strong><?php echo esc_html($error->message); ?></strong>
                            <span class="error-time"><?php echo $error->created_at; ?></span>
                        </div>
                        <?php if (!empty($error->context)): ?>
                            <div class="error-context">
                                <pre><?php echo esc_html(json_encode(json_decode($error->context), JSON_PRETTY_PRINT)); ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Warnings Tab -->
    <div id="warnings-tab" class="tab-content">
        <h3>⚠️ Recent Warnings</h3>
        <?php if (empty($warnings)): ?>
            <p class="success-message">✅ No warnings found!</p>
        <?php else: ?>
            <div class="warning-list">
                <?php foreach ($warnings as $warning): ?>
                    <div class="warning-item">
                        <div class="warning-header">
                            <strong><?php echo esc_html($warning->message); ?></strong>
                            <span class="warning-time"><?php echo $warning->created_at; ?></span>
                        </div>
                        <?php if (!empty($warning->context)): ?>
                            <div class="warning-context">
                                <pre><?php echo esc_html(json_encode(json_decode($warning->context), JSON_PRETTY_PRINT)); ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Function Tests Tab -->
    <div id="function-tests" class="tab-content">
        <h3>🧪 Test All Plugin Functions</h3>
        <p>Click buttons below to test individual functions:</p>

        <div class="function-tests-grid">
            <!-- Social Media Tests -->
            <div class="test-section">
                <h4>📱 Social Media Functions</h4>
                <button class="test-btn" data-test="test-telegram-auth">Test Telegram Auth</button>
                <button class="test-btn" data-test="test-facebook-auth">Test Facebook Auth</button>
                <button class="test-btn" data-test="test-instagram-auth">Test Instagram Auth</button>
                <button class="test-btn" data-test="test-twitter-auth">Test Twitter Auth</button>
                <button class="test-btn" data-test="test-social-db">Test Social Database</button>
                <button class="test-btn" data-test="test-rate-limiter">Test Rate Limiter</button>
            </div>

            <!-- AI Functions Tests -->
            <div class="test-section">
                <h4>🤖 AI Functions</h4>
                <button class="test-btn" data-test="test-claude-api">Test Claude API</button>
                <button class="test-btn" data-test="test-openai-api">Test OpenAI API</button>
                <button class="test-btn" data-test="test-content-generation">Test Content Generation</button>
            </div>

            <!-- SEO Functions Tests -->
            <div class="test-section">
                <h4>🔍 SEO Functions</h4>
                <button class="test-btn" data-test="test-meta-generation">Test Meta Generation</button>
                <button class="test-btn" data-test="test-schema-generation">Test Schema Generation</button>
                <button class="test-btn" data-test="test-keyword-analysis">Test Keyword Analysis</button>
            </div>

            <!-- Database Tests -->
            <div class="test-section">
                <h4>💾 Database Tests</h4>
                <button class="test-btn" data-test="test-db-connection">Test DB Connection</button>
                <button class="test-btn" data-test="test-db-tables">Test DB Tables</button>
                <button class="test-btn" data-test="test-db-migration">Test DB Migration</button>
            </div>
        </div>

        <div id="test-results" class="test-results">
            <!-- Results appear here -->
        </div>
    </div>

    <!-- Performance Tab -->
    <div id="performance-tab" class="tab-content">
        <h3>⚡ Performance Monitoring</h3>

        <div class="performance-stats">
            <h4>API Call Statistics</h4>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Total Calls</th>
                        <th>Successful</th>
                        <th>Failed</th>
                        <th>Avg Duration</th>
                        <th>Success Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $api_stats = $performance->get_api_stats();
                    if (!empty($api_stats)):
                        foreach ($api_stats as $provider => $stats):
                    ?>
                        <tr>
                            <td><?php echo esc_html($provider); ?></td>
                            <td><?php echo $stats['total']; ?></td>
                            <td class="success"><?php echo $stats['successful']; ?></td>
                            <td class="error"><?php echo $stats['failed']; ?></td>
                            <td><?php echo round($stats['avg_duration'], 2); ?>ms</td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo $stats['success_rate']; ?>%"></div>
                                    <span><?php echo round($stats['success_rate'], 1); ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="6">No API calls recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Social Media Debug Tab -->
    <div id="social-debug" class="tab-content">
        <h3>📱 Social Media Debug</h3>

        <?php
        // Get social media manager
        if (class_exists('AI_SEO_Social_Media_Manager')) {
            $social_manager = AI_SEO_Social_Media_Manager::get_instance();
            $stats = $social_manager->get_stats();
        ?>
            <div class="social-debug-info">
                <h4>📊 Social Media Stats</h4>
                <pre><?php echo esc_html(json_encode($stats, JSON_PRETTY_PRINT)); ?></pre>

                <h4>🔌 Registered Platforms</h4>
                <?php
                $platforms = isset($stats['platforms']) ? $stats['platforms'] : array();
                if (!empty($platforms)):
                ?>
                    <ul class="platform-list">
                        <?php foreach ($platforms as $platform => $status): ?>
                            <li>
                                <span class="platform-name"><?php echo esc_html($platform); ?></span>
                                <span class="platform-status <?php echo $status ? 'active' : 'inactive'; ?>">
                                    <?php echo $status ? '✅ Active' : '❌ Inactive'; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="warning-message">⚠️ No platforms registered!</p>
                <?php endif; ?>

                <h4>🧪 Test Social Posting</h4>
                <div class="social-test-form">
                    <textarea id="test-post-content" placeholder="Enter test post content..." rows="4"></textarea>
                    <select id="test-platform">
                        <option value="">Select Platform</option>
                        <?php foreach ($platforms as $platform => $status): ?>
                            <?php if ($status): ?>
                                <option value="<?php echo esc_attr($platform); ?>">
                                    <?php echo esc_html(ucfirst($platform)); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button class="button button-primary" id="test-social-post">
                        📤 Test Post
                    </button>
                </div>
                <div id="social-test-result"></div>
            </div>
        <?php
        } else {
            echo '<p class="error-message">❌ Social Media Manager not initialized!</p>';
        }
        ?>
    </div>

    <!-- Settings Tab -->
    <div id="settings-tab" class="tab-content">
        <h3>⚙️ Debug Settings</h3>

        <form method="post" action="options.php">
            <?php settings_fields('ai_seo_debug_settings'); ?>

            <table class="form-table">
                <tr>
                    <th>Enable Debug Mode</th>
                    <td>
                        <label>
                            <input type="checkbox" name="ai_seo_debug_enabled" value="1"
                                <?php checked(get_option('ai_seo_debug_enabled', false)); ?>>
                            Enable comprehensive debugging
                        </label>
                        <p class="description">
                            When enabled, all plugin operations will be logged.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>Debug Level</th>
                    <td>
                        <select name="ai_seo_debug_level">
                            <option value="ERROR" <?php selected(get_option('ai_seo_debug_level'), 'ERROR'); ?>>
                                ERROR - Only errors
                            </option>
                            <option value="WARNING" <?php selected(get_option('ai_seo_debug_level'), 'WARNING'); ?>>
                                WARNING - Errors + Warnings
                            </option>
                            <option value="INFO" <?php selected(get_option('ai_seo_debug_level'), 'INFO'); ?>>
                                INFO - Errors + Warnings + Info
                            </option>
                            <option value="DEBUG" <?php selected(get_option('ai_seo_debug_level', 'DEBUG'), 'DEBUG'); ?>>
                                DEBUG - Everything (recommended)
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Log Retention</th>
                    <td>
                        <input type="number" name="ai_seo_log_retention_days"
                            value="<?php echo esc_attr(get_option('ai_seo_log_retention_days', 7)); ?>"
                            min="1" max="90">
                        days
                        <p class="description">How long to keep debug logs</p>
                    </td>
                </tr>
                <tr>
                    <th>Email Notifications</th>
                    <td>
                        <label>
                            <input type="checkbox" name="ai_seo_debug_email_errors" value="1"
                                <?php checked(get_option('ai_seo_debug_email_errors', false)); ?>>
                            Email me when critical errors occur
                        </label>
                        <br>
                        <input type="email" name="ai_seo_debug_email"
                            value="<?php echo esc_attr(get_option('ai_seo_debug_email', get_option('admin_email'))); ?>"
                            placeholder="admin@example.com">
                    </td>
                </tr>
            </table>

            <?php submit_button('Save Debug Settings'); ?>
        </form>

        <hr>

        <h4>🔧 System Information</h4>
        <div class="system-info">
            <table class="widefat">
                <tr>
                    <th>WordPress Version</th>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <th>PHP Version</th>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <th>WP_DEBUG</th>
                    <td><?php echo defined('WP_DEBUG') && WP_DEBUG ? '✅ Enabled' : '❌ Disabled'; ?></td>
                </tr>
                <tr>
                    <th>WP_DEBUG_LOG</th>
                    <td><?php echo defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? '✅ Enabled' : '❌ Disabled'; ?></td>
                </tr>
                <tr>
                    <th>Memory Limit</th>
                    <td><?php echo WP_MEMORY_LIMIT; ?></td>
                </tr>
                <tr>
                    <th>Max Execution Time</th>
                    <td><?php echo ini_get('max_execution_time'); ?>s</td>
                </tr>
            </table>
        </div>
    </div>
</div>

<style>
.ai-seo-debug-realtime {
    padding: 20px;
}

.debug-status {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    margin-left: 20px;
}

.debug-status.active {
    background: #00a32a;
    color: white;
}

.debug-status.inactive {
    background: #dba617;
    color: white;
}

.debug-quick-actions {
    margin: 20px 0;
    padding: 15px;
    background: white;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.debug-quick-actions button {
    margin-right: 10px;
}

.debug-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #c3c4c7;
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
}

.stat-number {
    font-size: 36px;
    font-weight: bold;
    color: #1d2327;
}

.stat-label {
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}

.stat-card.errors .stat-number {
    color: #d63638;
}

.stat-card.warnings .stat-number {
    color: #dba617;
}

.stat-card.logs .stat-number {
    color: #2271b1;
}

.stat-card.performance .stat-number {
    color: #00a32a;
}

.tab-content {
    display: none;
    background: white;
    padding: 20px;
    border: 1px solid #c3c4c7;
    border-top: none;
}

.tab-content.active {
    display: block;
}

.live-log-container {
    max-height: 600px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 10px;
    background: #f9f9f9;
    font-family: monospace;
    font-size: 12px;
}

.log-entry {
    padding: 8px;
    margin: 5px 0;
    border-left: 4px solid #ddd;
    background: white;
}

.log-entry.log-error {
    border-left-color: #d63638;
    background: #fff0f0;
}

.log-entry.log-warning {
    border-left-color: #dba617;
    background: #fffef0;
}

.log-entry.log-info {
    border-left-color: #2271b1;
    background: #f0f6ff;
}

.log-entry.log-debug {
    border-left-color: #00a32a;
    background: #f0fff0;
}

.log-time {
    color: #666;
    margin-right: 10px;
}

.log-level {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-weight: bold;
    font-size: 11px;
    margin-right: 10px;
}

.log-level-error {
    background: #d63638;
    color: white;
}

.log-level-warning {
    background: #dba617;
    color: white;
}

.log-level-info {
    background: #2271b1;
    color: white;
}

.log-level-debug {
    background: #00a32a;
    color: white;
}

.toggle-context {
    float: right;
    font-size: 11px;
}

.log-context {
    margin-top: 10px;
    background: #f0f0f0;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
}

.function-tests-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.test-section {
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 4px;
}

.test-section h4 {
    margin-top: 0;
}

.test-btn {
    display: block;
    width: 100%;
    margin: 5px 0;
    text-align: left;
    padding: 8px 12px;
}

.test-results {
    margin-top: 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-height: 100px;
}

.success-message {
    color: #00a32a;
    padding: 15px;
    background: #f0fff0;
    border: 1px solid #00a32a;
    border-radius: 4px;
}

.error-message {
    color: #d63638;
    padding: 15px;
    background: #fff0f0;
    border: 1px solid #d63638;
    border-radius: 4px;
}

.warning-message {
    color: #dba617;
    padding: 15px;
    background: #fffef0;
    border: 1px solid #dba617;
    border-radius: 4px;
}

.progress-bar {
    position: relative;
    height: 25px;
    background: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
}

.progress {
    height: 100%;
    background: #00a32a;
    transition: width 0.3s;
}

.progress-bar span {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
    font-weight: bold;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');

        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });

    // Toggle context
    $('.toggle-context').on('click', function() {
        var target = '#' + $(this).data('target');
        $(target).slideToggle();
    });

    // Auto-refresh logs
    var autoRefresh = null;
    $('#auto-refresh').on('change', function() {
        if ($(this).is(':checked')) {
            autoRefresh = setInterval(refreshLogs, 5000);
        } else {
            clearInterval(autoRefresh);
        }
    });

    function refreshLogs() {
        $.post(ajaxurl, {
            action: 'ai_seo_get_latest_logs',
            nonce: '<?php echo wp_create_nonce('ai_seo_debug'); ?>'
        }, function(response) {
            if (response.success) {
                updateLogDisplay(response.data);
            }
        });
    }

    // Refresh button
    $('#refresh-logs').on('click', function() {
        refreshLogs();
    });

    // Clear logs
    $('#clear-all-logs').on('click', function() {
        if (confirm('Are you sure you want to clear all debug logs?')) {
            $.post(ajaxurl, {
                action: 'ai_seo_clear_logs',
                nonce: '<?php echo wp_create_nonce('ai_seo_debug'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                }
            });
        }
    });

    // Test functions
    $('.test-btn').on('click', function() {
        var testName = $(this).data('test');
        var $btn = $(this);
        var originalText = $btn.text();

        $btn.text('Testing...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'ai_seo_run_test',
            test: testName,
            nonce: '<?php echo wp_create_nonce('ai_seo_debug'); ?>'
        }, function(response) {
            $btn.text(originalText).prop('disabled', false);

            var result = '<div class="test-result ' + (response.success ? 'success' : 'error') + '">';
            result += '<h4>' + testName + '</h4>';
            result += '<pre>' + JSON.stringify(response.data, null, 2) + '</pre>';
            result += '</div>';

            $('#test-results').prepend(result);
        });
    });

    // Test all functions
    $('#test-all-functions').on('click', function() {
        $('.test-btn').each(function(i) {
            var $btn = $(this);
            setTimeout(function() {
                $btn.click();
            }, i * 1000); // 1 second delay between tests
        });
    });

    // Export debug report
    $('#export-debug-report').on('click', function() {
        window.location.href = ajaxurl + '?action=ai_seo_export_debug_report&nonce=<?php echo wp_create_nonce('ai_seo_debug'); ?>';
    });

    // Test social post
    $('#test-social-post').on('click', function() {
        var content = $('#test-post-content').val();
        var platform = $('#test-platform').val();

        if (!content || !platform) {
            alert('Please enter content and select a platform');
            return;
        }

        $(this).text('Posting...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'ai_seo_test_social_post',
            content: content,
            platform: platform,
            nonce: '<?php echo wp_create_nonce('ai_seo_debug'); ?>'
        }, function(response) {
            $('#test-social-post').text('📤 Test Post').prop('disabled', false);

            var result = '<div class="test-result ' + (response.success ? 'success' : 'error') + '">';
            result += '<h4>Social Post Result</h4>';
            result += '<pre>' + JSON.stringify(response.data, null, 2) + '</pre>';
            result += '</div>';

            $('#social-test-result').html(result);
        });
    });

    // Log filtering
    $('#log-level-filter').on('change', function() {
        var level = $(this).val();
        if (level) {
            $('.log-entry').hide();
            $('.log-entry[data-level="' + level + '"]').show();
        } else {
            $('.log-entry').show();
        }
    });

    // Log search
    $('#log-search').on('keyup', function() {
        var search = $(this).val().toLowerCase();
        $('.log-entry').each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(search) > -1);
        });
    });
});
</script>
<?php
