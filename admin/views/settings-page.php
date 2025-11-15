<?php
/**
 * Settings Page Template
 */

if (!defined('ABSPATH')) exit;

$current_settings = $current_settings ?? array();
?>

<div class="wrap ai-seo-manager-settings">
    <h1><?php _e('AI SEO Manager Settings', 'ai-seo-manager'); ?></h1>

    <?php if (isset($_GET['updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Settings saved successfully!', 'ai-seo-manager'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('ai_seo_manager_settings'); ?>
        <input type="hidden" name="action" value="ai_seo_manager_save_settings">

        <div class="settings-tabs">
            <h2 class="nav-tab-wrapper">
                <a href="#ai-settings" class="nav-tab nav-tab-active"><?php _e('AI Settings', 'ai-seo-manager'); ?></a>
                <a href="#analytics" class="nav-tab"><?php _e('Analytics', 'ai-seo-manager'); ?></a>
                <a href="#autopilot" class="nav-tab"><?php _e('Autopilot', 'ai-seo-manager'); ?></a>
                <a href="#advanced" class="nav-tab"><?php _e('Advanced', 'ai-seo-manager'); ?></a>
            </h2>

            <!-- AI Settings Tab -->
            <div id="ai-settings" class="tab-content active">
                <h2><?php _e('🤖 AI Configuration', 'ai-seo-manager'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('AI Provider', 'ai-seo-manager'); ?></th>
                        <td>
                            <select name="ai_provider">
                                <option value="claude" <?php selected($current_settings['ai_provider'] ?? 'claude', 'claude'); ?>>Claude (Anthropic)</option>
                                <option value="openai" <?php selected($current_settings['ai_provider'] ?? 'claude', 'openai'); ?>>OpenAI</option>
                                <option value="both" <?php selected($current_settings['ai_provider'] ?? 'claude', 'both'); ?>>Both (with fallback)</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Claude API Key', 'ai-seo-manager'); ?></th>
                        <td>
                            <input type="password" name="claude_api_key" class="regular-text" value="<?php echo esc_attr($current_settings['claude_api_key'] ?? ''); ?>" placeholder="sk-ant-...">
                            <p class="description"><?php _e('Get your API key from console.anthropic.com', 'ai-seo-manager'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Claude Model', 'ai-seo-manager'); ?></th>
                        <td>
                            <select name="claude_model">
                                <option value="claude-3-5-sonnet-20241022" <?php selected($current_settings['claude_model'] ?? '', 'claude-3-5-sonnet-20241022'); ?>>Claude 3.5 Sonnet (Recommended)</option>
                                <option value="claude-3-opus-20240229" <?php selected($current_settings['claude_model'] ?? '', 'claude-3-opus-20240229'); ?>>Claude 3 Opus</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('OpenAI API Key', 'ai-seo-manager'); ?></th>
                        <td>
                            <input type="password" name="openai_api_key" class="regular-text" value="<?php echo esc_attr($current_settings['openai_api_key'] ?? ''); ?>" placeholder="sk-...">
                            <p class="description"><?php _e('Get your API key from platform.openai.com', 'ai-seo-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Analytics Tab -->
            <div id="analytics" class="tab-content">
                <h2><?php _e('📊 Analytics Integration', 'ai-seo-manager'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Google Analytics 4', 'ai-seo-manager'); ?></th>
                        <td>
                            <input type="text" name="ga4_measurement_id" class="regular-text" value="<?php echo esc_attr($current_settings['ga4_measurement_id'] ?? ''); ?>" placeholder="G-XXXXXXXXXX">
                            <p class="description"><?php _e('Your GA4 Measurement ID', 'ai-seo-manager'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('GA4 API Secret', 'ai-seo-manager'); ?></th>
                        <td>
                            <input type="password" name="ga4_api_secret" class="regular-text" value="<?php echo esc_attr($current_settings['ga4_api_secret'] ?? ''); ?>">
                            <p class="description"><?php _e('For sending custom events', 'ai-seo-manager'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Google Search Console', 'ai-seo-manager'); ?></th>
                        <td>
                            <input type="text" name="gsc_client_id" class="regular-text" value="<?php echo esc_attr($current_settings['gsc_client_id'] ?? ''); ?>" placeholder="Client ID">
                            <input type="password" name="gsc_client_secret" class="regular-text" value="<?php echo esc_attr($current_settings['gsc_client_secret'] ?? ''); ?>" placeholder="Client Secret">
                            <p class="description"><?php _e('OAuth credentials for Search Console API', 'ai-seo-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Autopilot Tab -->
            <div id="autopilot" class="tab-content">
                <h2><?php _e('🚀 Autopilot Configuration', 'ai-seo-manager'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Autopilot', 'ai-seo-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="autopilot_enabled" value="1" <?php checked(!empty($current_settings['autopilot_enabled'])); ?>>
                                <?php _e('Enable automatic SEO optimizations', 'ai-seo-manager'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Autopilot Mode', 'ai-seo-manager'); ?></th>
                        <td>
                            <select name="autopilot_mode">
                                <option value="approval" <?php selected($current_settings['autopilot_mode'] ?? 'approval', 'approval'); ?>>
                                    <?php _e('Approval Required (Recommended)', 'ai-seo-manager'); ?>
                                </option>
                                <option value="auto" <?php selected($current_settings['autopilot_mode'] ?? 'approval', 'auto'); ?>>
                                    <?php _e('Fully Automatic (Advanced)', 'ai-seo-manager'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('Approval mode requires your confirmation before applying changes', 'ai-seo-manager'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Allowed Actions', 'ai-seo-manager'); ?></th>
                        <td>
                            <?php
                            $actions = $current_settings['autopilot_actions'] ?? array();
                            ?>
                            <label><input type="checkbox" name="autopilot_meta_description" value="1" <?php checked(!empty($actions['meta_description'])); ?>> <?php _e('Meta Descriptions', 'ai-seo-manager'); ?></label><br>
                            <label><input type="checkbox" name="autopilot_alt_texts" value="1" <?php checked(!empty($actions['alt_texts'])); ?>> <?php _e('Image ALT Texts', 'ai-seo-manager'); ?></label><br>
                            <label><input type="checkbox" name="autopilot_headings" value="1" <?php checked(!empty($actions['headings'])); ?>> <?php _e('Heading Optimization', 'ai-seo-manager'); ?></label><br>
                            <label><input type="checkbox" name="autopilot_internal_links" value="1" <?php checked(!empty($actions['internal_links'])); ?>> <?php _e('Internal Links', 'ai-seo-manager'); ?></label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Advanced Tab -->
            <div id="advanced" class="tab-content">
                <h2><?php _e('⚙️ Advanced Settings', 'ai-seo-manager'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Auto Analysis', 'ai-seo-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_analysis" value="1" <?php checked(!empty($current_settings['auto_analysis'])); ?>>
                                <?php _e('Automatically analyze posts on publish', 'ai-seo-manager'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Max API Calls/Day', 'ai-seo-manager'); ?></th>
                        <td>
                            <input type="number" name="max_api_calls_per_day" value="<?php echo esc_attr($current_settings['max_api_calls_per_day'] ?? 100); ?>" min="10" max="1000">
                            <p class="description"><?php _e('Limit to prevent excessive API usage', 'ai-seo-manager'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Debug Mode', 'ai-seo-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="debug_mode" value="1" <?php checked(!empty($current_settings['debug_mode'])); ?>>
                                <?php _e('Enable debug logging', 'ai-seo-manager'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary button-large">
                <?php _e('Save Settings', 'ai-seo-manager'); ?>
            </button>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');

        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
});
</script>
