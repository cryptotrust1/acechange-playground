<?php
/**
 * Prompt templates for Claude API.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/claude
 */

/**
 * Provides pre-built prompt templates for various SEO tasks.
 */
class Claude_SEO_Prompt_Templates {

    /**
     * Generate blog post prompt.
     *
     * @param array $args Arguments (topic, keyword, word_count, tone).
     * @return string Formatted prompt.
     */
    public static function blog_post($args) {
        $defaults = array(
            'topic' => '',
            'keyword' => '',
            'word_count' => 1000,
            'tone' => 'professional'
        );

        $args = wp_parse_args($args, $defaults);

        return sprintf(
            "Write a comprehensive blog post about: %s\n\n" .
            "Requirements:\n" .
            "- Target word count: approximately %d words\n" .
            "- Focus keyword: '%s' (use naturally with 1-2%% density)\n" .
            "- Tone: %s\n" .
            "- Include proper H2 and H3 headings\n" .
            "- Follow E-E-A-T principles (Experience, Expertise, Authoritativeness, Trustworthiness)\n" .
            "- Write people-first content that provides real value\n" .
            "- Include actionable takeaways\n" .
            "- Use short paragraphs (3-4 sentences max)\n" .
            "- Avoid keyword stuffing\n\n" .
            "Format the output in clean HTML with proper heading tags.",
            esc_html($args['topic']),
            absint($args['word_count']),
            esc_html($args['keyword']),
            esc_html($args['tone'])
        );
    }

    /**
     * Generate meta title prompt.
     *
     * @param string $content Post content.
     * @param string $keyword Focus keyword.
     * @return string Formatted prompt.
     */
    public static function meta_title($content, $keyword = '') {
        return sprintf(
            "Based on the following content, create an optimized SEO title tag.\n\n" .
            "Content:\n%s\n\n" .
            "Requirements:\n" .
            "- Length: 50-60 characters\n" .
            "- Include the focus keyword '%s' naturally\n" .
            "- Make it compelling to improve CTR\n" .
            "- Front-load important keywords\n" .
            "- Avoid clickbait or misleading phrasing\n\n" .
            "Provide ONLY the title tag text, nothing else.",
            wp_trim_words($content, 200),
            esc_html($keyword)
        );
    }

    /**
     * Generate meta description prompt.
     *
     * @param string $content Post content.
     * @param string $keyword Focus keyword.
     * @return string Formatted prompt.
     */
    public static function meta_description($content, $keyword = '') {
        return sprintf(
            "Based on the following content, create an optimized meta description.\n\n" .
            "Content:\n%s\n\n" .
            "Requirements:\n" .
            "- Length: 150-160 characters\n" .
            "- Include the focus keyword '%s' naturally\n" .
            "- Include a compelling call-to-action\n" .
            "- Accurately summarize the content\n" .
            "- Avoid duplicate content\n\n" .
            "Provide ONLY the meta description text, nothing else.",
            wp_trim_words($content, 200),
            esc_html($keyword)
        );
    }

    /**
     * Generate image alt text prompt.
     *
     * @param string $filename    Image filename.
     * @param string $context     Surrounding content context.
     * @param string $post_title  Post title.
     * @return string Formatted prompt.
     */
    public static function image_alt_text($filename, $context = '', $post_title = '') {
        return sprintf(
            "Generate SEO-friendly alt text for an image.\n\n" .
            "Image filename: %s\n" .
            "Post title: %s\n" .
            "Surrounding context:\n%s\n\n" .
            "Requirements:\n" .
            "- Maximum 125 characters\n" .
            "- Descriptive and specific\n" .
            "- Include relevant keywords naturally\n" .
            "- Accessible for screen readers\n" .
            "- Avoid 'image of' or 'picture of' phrases\n\n" .
            "Provide ONLY the alt text, nothing else.",
            esc_html($filename),
            esc_html($post_title),
            wp_trim_words($context, 100)
        );
    }

    /**
     * Generate internal linking suggestions prompt.
     *
     * @param string $content       Current post content.
     * @param array  $available_posts Available posts for linking.
     * @return string Formatted prompt.
     */
    public static function internal_linking($content, $available_posts) {
        $posts_list = '';
        foreach ($available_posts as $post) {
            $posts_list .= sprintf(
                "- ID: %d | Title: %s | URL: %s | Excerpt: %s\n",
                $post['id'],
                $post['title'],
                $post['url'],
                wp_trim_words($post['excerpt'], 30)
            );
        }

        return sprintf(
            "Analyze this content and suggest internal links from the available posts.\n\n" .
            "Content:\n%s\n\n" .
            "Available posts for linking:\n%s\n\n" .
            "Requirements:\n" .
            "- Suggest 2-5 highly relevant internal links\n" .
            "- Provide natural anchor text (varied, not exact match)\n" .
            "- Only suggest contextually relevant links\n" .
            "- Specify where in the content each link should be placed\n\n" .
            "Format output as JSON array:\n" .
            "[{\"post_id\": 123, \"anchor_text\": \"example text\", \"context\": \"sentence where link fits\"}]",
            wp_trim_words($content, 300),
            $posts_list
        );
    }

    /**
     * Generate FAQ schema prompt.
     *
     * @param string $content Post content.
     * @return string Formatted prompt.
     */
    public static function faq_schema($content) {
        return sprintf(
            "Analyze this content and extract question-answer pairs for FAQ schema.\n\n" .
            "Content:\n%s\n\n" .
            "Requirements:\n" .
            "- Identify clear question-answer patterns\n" .
            "- Extract 3-10 FAQ pairs (if available)\n" .
            "- Questions should be in interrogative form\n" .
            "- Answers should be concise but complete\n\n" .
            "Format output as JSON:\n" .
            "[{\"question\": \"Question text?\", \"answer\": \"Answer text.\"}]\n\n" .
            "If no clear Q&A patterns exist, return empty array: []",
            wp_trim_words($content, 500)
        );
    }

    /**
     * Generate content improvement suggestions prompt.
     *
     * @param string $content Current content.
     * @param string $keyword Focus keyword.
     * @param int    $seo_score Current SEO score.
     * @return string Formatted prompt.
     */
    public static function content_improvements($content, $keyword, $seo_score) {
        return sprintf(
            "Analyze this content and provide SEO improvement suggestions.\n\n" .
            "Content:\n%s\n\n" .
            "Focus keyword: %s\n" .
            "Current SEO score: %d/100\n\n" .
            "Provide specific, actionable recommendations to improve:\n" .
            "1. Keyword usage and placement\n" .
            "2. Content structure and headings\n" .
            "3. Readability and user engagement\n" .
            "4. E-E-A-T signals\n" .
            "5. Internal and external linking opportunities\n\n" .
            "Format as a prioritized list of 5-10 recommendations.",
            wp_trim_words($content, 300),
            esc_html($keyword),
            absint($seo_score)
        );
    }

    /**
     * Generate topic ideas prompt.
     *
     * @param string $niche    Content niche.
     * @param int    $count    Number of topics.
     * @param string $audience Target audience.
     * @return string Formatted prompt.
     */
    public static function topic_ideas($niche, $count = 10, $audience = '') {
        $audience_text = $audience ? "Target audience: {$audience}\n" : '';

        return sprintf(
            "Generate %d blog post topic ideas for the following niche.\n\n" .
            "Niche: %s\n" .
            "%s\n" .
            "Requirements:\n" .
            "- Topics should be specific and actionable\n" .
            "- Include a mix of informational, how-to, and comparison topics\n" .
            "- Focus on search intent and user value\n" .
            "- Suggest realistic word counts (300-3000 words)\n" .
            "- Include primary keyword suggestions\n\n" .
            "Format as JSON array:\n" .
            "[{\"topic\": \"Topic title\", \"keyword\": \"focus keyword\", \"word_count\": 1500, \"type\": \"how-to\"}]",
            absint($count),
            esc_html($niche),
            $audience_text
        );
    }

    /**
     * Get system prompt for all requests.
     *
     * @return string System prompt.
     */
    public static function get_system_prompt() {
        return "You are an expert SEO content specialist with deep knowledge of:\n" .
               "- Google Search Central guidelines and E-E-A-T principles\n" .
               "- On-page SEO best practices\n" .
               "- Natural language and keyword optimization\n" .
               "- Content structure and readability\n" .
               "- Schema markup and technical SEO\n\n" .
               "Always provide:\n" .
               "- Accurate, helpful content that serves user intent\n" .
               "- Natural keyword usage (avoid stuffing)\n" .
               "- Actionable, specific recommendations\n" .
               "- Google-compliant SEO strategies\n\n" .
               "Never suggest:\n" .
               "- Black hat SEO techniques\n" .
               "- Keyword stuffing or cloaking\n" .
               "- Misleading or clickbait content\n" .
               "- Link schemes or manipulation";
    }
}
