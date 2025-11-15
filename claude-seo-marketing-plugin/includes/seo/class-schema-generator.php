<?php
/**
 * Schema markup generator.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/seo
 */

/**
 * Generates JSON-LD schema markup.
 */
class Claude_SEO_Schema_Generator {

    /**
     * Generate Article schema.
     *
     * @param WP_Post $post Post object.
     * @return array Schema data.
     */
    public static function generate_article_schema($post) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title($post),
            'datePublished' => get_the_date('c', $post),
            'dateModified' => get_the_modified_date('c', $post),
            'author' => self::get_author_schema($post->post_author),
            'publisher' => self::get_organization_schema(),
        );

        // Add featured image
        if (has_post_thumbnail($post)) {
            $image_id = get_post_thumbnail_id($post);
            $image = wp_get_attachment_image_src($image_id, 'full');
            if ($image) {
                $schema['image'] = $image[0];
            }
        }

        // Add description
        $excerpt = get_the_excerpt($post);
        if (!empty($excerpt)) {
            $schema['description'] = wp_trim_words($excerpt, 30);
        }

        return $schema;
    }

    /**
     * Get author schema.
     *
     * @param int $author_id Author ID.
     * @return array Author schema.
     */
    private static function get_author_schema($author_id) {
        return array(
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', $author_id),
            'url' => get_author_posts_url($author_id)
        );
    }

    /**
     * Get organization schema.
     *
     * @return array Organization schema.
     */
    private static function get_organization_schema() {
        $settings = get_option('claude_seo_settings', array());
        $org_data = isset($settings['schema_organization']) ? $settings['schema_organization'] : array();

        $schema = array(
            '@type' => 'Organization',
            'name' => isset($org_data['name']) ? $org_data['name'] : get_bloginfo('name'),
            'url' => isset($org_data['url']) ? $org_data['url'] : home_url()
        );

        if (!empty($org_data['logo'])) {
            $schema['logo'] = $org_data['logo'];
        }

        if (!empty($org_data['social_profiles'])) {
            $schema['sameAs'] = $org_data['social_profiles'];
        }

        return $schema;
    }

    /**
     * Generate FAQ schema from content.
     *
     * @param array $faqs FAQ data.
     * @return array|null Schema data or null if no FAQs.
     */
    public static function generate_faq_schema($faqs) {
        if (empty($faqs)) {
            return null;
        }

        $questions = array();

        foreach ($faqs as $faq) {
            $questions[] = array(
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                )
            );
        }

        return array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions
        );
    }

    /**
     * Generate Breadcrumb schema.
     *
     * @return array Schema data.
     */
    public static function generate_breadcrumb_schema() {
        if (is_front_page()) {
            return null;
        }

        $items = array();
        $position = 1;

        // Home
        $items[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => get_bloginfo('name'),
            'item' => home_url()
        );

        // Add breadcrumb items based on page type
        if (is_single()) {
            $post = get_post();
            $categories = get_the_category($post->ID);

            if (!empty($categories)) {
                $category = $categories[0];
                $items[] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $category->name,
                    'item' => get_category_link($category->term_id)
                );
            }

            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => get_the_title($post),
                'item' => get_permalink($post)
            );
        } elseif (is_category()) {
            $category = get_queried_object();
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $category->name,
                'item' => get_category_link($category->term_id)
            );
        } elseif (is_page()) {
            $page = get_post();
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => get_the_title($page),
                'item' => get_permalink($page)
            );
        }

        return array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        );
    }

    /**
     * Generate WebPage schema.
     *
     * @return array Schema data.
     */
    public static function generate_webpage_schema() {
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => wp_get_document_title(),
            'url' => get_permalink(),
            'inLanguage' => get_bloginfo('language')
        );
    }
}
