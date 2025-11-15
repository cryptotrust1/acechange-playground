<?php
/**
 * Post editor meta boxes.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/admin
 */

/**
 * Handles post meta boxes.
 */
class Claude_SEO_Meta_Boxes {

    /**
     * Add meta boxes.
     */
    public function add_meta_boxes() {
        $post_types = get_post_types(array('public' => true));

        foreach ($post_types as $post_type) {
            add_meta_box(
                'claude_seo_meta',
                __('Claude SEO', 'claude-seo'),
                array($this, 'render_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render meta box.
     */
    public function render_meta_box($post) {
        wp_nonce_field('claude_seo_save_meta', 'claude_seo_meta_nonce');

        $seo_title = get_post_meta($post->ID, '_seo_title', true);
        $seo_description = get_post_meta($post->ID, '_seo_description', true);
        $seo_keywords = get_post_meta($post->ID, '_seo_keywords', true);
        $seo_score = get_post_meta($post->ID, '_seo_score', true);

        ?>
        <div class="claude-seo-meta-box">
            <div class="claude-seo-score-display">
                <h3><?php esc_html_e('SEO Score', 'claude-seo'); ?></h3>
                <div class="score-circle" data-score="<?php echo esc_attr($seo_score); ?>">
                    <span class="score-number"><?php echo esc_html($seo_score ?: '0'); ?></span>
                </div>
            </div>

            <p>
                <label for="claude_seo_keywords"><?php esc_html_e('Focus Keyword', 'claude-seo'); ?></label>
                <input type="text" id="claude_seo_keywords" name="claude_seo_keywords" value="<?php echo esc_attr($seo_keywords); ?>" class="widefat">
            </p>

            <p>
                <label for="claude_seo_title"><?php esc_html_e('SEO Title', 'claude-seo'); ?></label>
                <input type="text" id="claude_seo_title" name="claude_seo_title" value="<?php echo esc_attr($seo_title); ?>" class="widefat" maxlength="60">
                <span class="char-count"><span class="count"><?php echo mb_strlen($seo_title); ?></span>/60</span>
            </p>

            <p>
                <label for="claude_seo_description"><?php esc_html_e('Meta Description', 'claude-seo'); ?></label>
                <textarea id="claude_seo_description" name="claude_seo_description" rows="3" class="widefat" maxlength="160"><?php echo esc_textarea($seo_description); ?></textarea>
                <span class="char-count"><span class="count"><?php echo mb_strlen($seo_description); ?></span>/160</span>
            </p>

            <p>
                <button type="button" class="button button-secondary" id="claude-analyze-content">
                    <?php esc_html_e('Analyze SEO', 'claude-seo'); ?>
                </button>
                <button type="button" class="button button-secondary" id="claude-generate-meta">
                    <?php esc_html_e('Generate Meta Tags (AI)', 'claude-seo'); ?>
                </button>
            </p>

            <div id="claude-seo-analysis-results"></div>
        </div>
        <?php
    }

    /**
     * Save meta boxes.
     */
    public function save_meta_boxes($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['claude_seo_meta_nonce']) || !wp_verify_nonce($_POST['claude_seo_meta_nonce'], 'claude_seo_save_meta')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save meta data
        if (isset($_POST['claude_seo_title'])) {
            update_post_meta($post_id, '_seo_title', Claude_SEO_Sanitizer::sanitize_title($_POST['claude_seo_title']));
        }

        if (isset($_POST['claude_seo_description'])) {
            update_post_meta($post_id, '_seo_description', Claude_SEO_Sanitizer::sanitize_description($_POST['claude_seo_description']));
        }

        if (isset($_POST['claude_seo_keywords'])) {
            update_post_meta($post_id, '_seo_keywords', Claude_SEO_Sanitizer::sanitize_keyword($_POST['claude_seo_keywords']));
        }

        // Run analysis and save score
        $analysis = Claude_SEO_Analyzer::analyze_post($post_id);
        update_post_meta($post_id, '_seo_score', $analysis['seo_score']);
        update_post_meta($post_id, '_seo_readability_score', $analysis['readability']['flesch_reading_ease']);
        update_post_meta($post_id, '_seo_keyword_density', $analysis['keyword_analysis']['density']);

        // Clear sitemap cache
        Claude_SEO_Sitemap::clear_cache();
    }
}
