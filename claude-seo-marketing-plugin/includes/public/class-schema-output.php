<?php
/**
 * Schema markup output.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/public
 */

/**
 * Outputs JSON-LD schema markup.
 */
class Claude_SEO_Schema_Output {

    /**
     * Output schema markup.
     */
    public function output_schema_markup() {
        $schemas = array();

        // Add appropriate schema based on page type
        if (is_singular('post')) {
            $post = get_queried_object();
            $schemas[] = Claude_SEO_Schema_Generator::generate_article_schema($post);
        }

        // Add breadcrumb schema
        $breadcrumb = Claude_SEO_Schema_Generator::generate_breadcrumb_schema();
        if ($breadcrumb) {
            $schemas[] = $breadcrumb;
        }

        // Output all schemas
        foreach ($schemas as $schema) {
            if (!empty($schema)) {
                echo '<script type="application/ld+json">';
                echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                echo '</script>' . "\n";
            }
        }
    }
}
