<?php
/**
 * SEO Schema.org trieda
 * Generuje strukturované dáta (JSON-LD) pre lepšie zobrazenie vo vyhľadávačoch
 */

if (!defined('ABSPATH')) {
    exit;
}

class AceChange_SEO_Schema {

    /**
     * Výstup Schema.org markup
     */
    public static function output_schema_markup() {
        $schema = array();

        // Organization/Website schema
        $schema[] = self::get_organization_schema();

        // Breadcrumbs
        if (!is_front_page()) {
            $breadcrumb_schema = self::get_breadcrumb_schema();
            if ($breadcrumb_schema) {
                $schema[] = $breadcrumb_schema;
            }
        }

        // Article schema
        if (is_singular('post')) {
            $schema[] = self::get_article_schema();
        }

        // Page schema
        if (is_page()) {
            $schema[] = self::get_webpage_schema();
        }

        // Výstup JSON-LD
        if (!empty($schema)) {
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            echo "\n" . '</script>' . "\n";
        }
    }

    /**
     * Organization schema
     */
    private static function get_organization_schema() {
        $site_name = get_bloginfo('name');
        $site_url = home_url('/');

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $site_name,
            'url' => $site_url,
        );

        // Logo
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_src($custom_logo_id, 'full');
            if ($logo_url) {
                $schema['logo'] = array(
                    '@type' => 'ImageObject',
                    'url' => $logo_url[0],
                );
            }
        }

        return $schema;
    }

    /**
     * Breadcrumb schema
     */
    private static function get_breadcrumb_schema() {
        $items = array();
        $position = 1;

        // Domov
        $items[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Domov',
            'item' => home_url('/'),
        );

        if (is_singular()) {
            global $post;

            // Kategórie
            $categories = get_the_category($post->ID);
            if ($categories) {
                $category = $categories[0];
                $items[] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $category->name,
                    'item' => get_category_link($category->term_id),
                );
            }

            // Aktuálna stránka
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => get_the_title(),
                'item' => get_permalink(),
            );
        }

        if (empty($items)) {
            return null;
        }

        return array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        );
    }

    /**
     * Article schema
     */
    private static function get_article_schema() {
        global $post;

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author(),
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url(),
                ),
            ),
        );

        // Obrázok
        if (has_post_thumbnail()) {
            $image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
            if ($image) {
                $schema['image'] = array(
                    '@type' => 'ImageObject',
                    'url' => $image[0],
                    'width' => $image[1],
                    'height' => $image[2],
                );
            }
        }

        // Description
        if ($post->post_excerpt) {
            $schema['description'] = wp_strip_all_tags($post->post_excerpt);
        }

        return $schema;
    }

    /**
     * WebPage schema
     */
    private static function get_webpage_schema() {
        global $post;

        return array(
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => get_the_title(),
            'description' => wp_strip_all_tags($post->post_excerpt ?: $post->post_content),
            'url' => get_permalink(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
        );
    }
}
