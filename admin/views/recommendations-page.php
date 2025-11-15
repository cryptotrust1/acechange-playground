<?php
/**
 * Recommendations Page Template
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap ai-seo-manager-recommendations">
    <h1><?php _e('SEO Recommendations', 'ai-seo-manager'); ?></h1>

    <?php if (empty($recommendations)) : ?>
        <div class="notice notice-info">
            <p><?php _e('No recommendations available. Recommendations will appear here after content analysis.', 'ai-seo-manager'); ?></p>
        </div>
    <?php else : ?>
        <div class="recommendations-grid">
            <?php
            $grouped = array();
            foreach ($recommendations as $rec) {
                $grouped[$rec->priority][] = $rec;
            }

            foreach (array('critical', 'high', 'medium', 'low') as $priority) :
                if (empty($grouped[$priority])) continue;
                ?>
                <div class="priority-group priority-<?php echo esc_attr($priority); ?>">
                    <h2><?php echo esc_html(ucfirst($priority)); ?> <?php _e('Priority', 'ai-seo-manager'); ?> (<?php echo count($grouped[$priority]); ?>)</h2>

                    <div class="recommendations-list">
                        <?php foreach ($grouped[$priority] as $rec) : ?>
                            <div class="recommendation-card">
                                <div class="rec-header">
                                    <h3><?php echo esc_html($rec->title); ?></h3>
                                    <span class="rec-type"><?php echo esc_html($rec->recommendation_type); ?></span>
                                </div>
                                <p><?php echo esc_html($rec->description); ?></p>
                                <?php if ($rec->post_id) : ?>
                                    <div class="rec-post">
                                        <strong><?php _e('Post:', 'ai-seo-manager'); ?></strong>
                                        <?php echo esc_html(get_the_title($rec->post_id)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="rec-confidence">
                                    AI Confidence: <?php echo round($rec->ai_confidence * 100); ?>%
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
