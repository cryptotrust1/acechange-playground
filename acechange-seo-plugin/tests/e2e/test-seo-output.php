<?php
/**
 * E2E testy pre AceChange SEO Plugin
 * Tieto testy overujú end-to-end funkcionalitu pluginu
 */

class AceChange_SEO_E2E_Tests extends WP_UnitTestCase {

    private $post_id;

    /**
     * Príprava testov
     */
    public function setUp() {
        parent::setUp();

        // Vytvorenie testovacieho príspevku
        $this->post_id = $this->factory->post->create(array(
            'post_title' => 'Test SEO Article',
            'post_content' => 'This is a test article for SEO plugin. It contains enough content to test meta description generation and other SEO features.',
            'post_excerpt' => 'This is a test excerpt for SEO meta description testing.',
            'post_status' => 'publish'
        ));

        // Nastavenie featured image
        $attachment_id = $this->factory->attachment->create_upload_object(__DIR__ . '/../fixtures/test-image.jpg');
        set_post_thumbnail($this->post_id, $attachment_id);

        // Aktivácia všetkých SEO funkcií
        update_option('acechange_seo_settings', array(
            'auto_meta_tags' => true,
            'auto_open_graph' => true,
            'auto_schema' => true,
            'auto_sitemap' => true,
            'meta_description_length' => 160,
            'twitter_card' => true,
            'canonical_urls' => true,
            'noindex_search' => true
        ));
    }

    /**
     * Upratanie po testoch
     */
    public function tearDown() {
        wp_delete_post($this->post_id, true);
        parent::tearDown();
    }

    /**
     * Test: Meta description je generovaná správne
     */
    public function test_meta_description_output() {
        $this->go_to(get_permalink($this->post_id));

        ob_start();
        AceChange_SEO_Meta::output_meta_tags();
        $output = ob_get_clean();

        // Overenie, že meta description existuje
        $this->assertStringContainsString('<meta name="description"', $output);

        // Overenie, že obsahuje excerpt
        $this->assertStringContainsString('test excerpt', $output);

        // Overenie dĺžky (max 160 znakov + safety margin)
        preg_match('/content="([^"]+)"/', $output, $matches);
        $this->assertLessThanOrEqual(165, strlen($matches[1]));
    }

    /**
     * Test: Open Graph tagy sú generované
     */
    public function test_open_graph_output() {
        $this->go_to(get_permalink($this->post_id));

        ob_start();
        AceChange_SEO_Meta::output_open_graph_tags();
        $output = ob_get_clean();

        // Základné OG tagy
        $this->assertStringContainsString('og:type', $output);
        $this->assertStringContainsString('og:title', $output);
        $this->assertStringContainsString('og:description', $output);
        $this->assertStringContainsString('og:url', $output);
        $this->assertStringContainsString('og:image', $output);

        // Overenie hodnôt
        $this->assertStringContainsString('Test SEO Article', $output);
        $this->assertStringContainsString('article', $output);
    }

    /**
     * Test: Twitter Card tagy sú generované
     */
    public function test_twitter_card_output() {
        $this->go_to(get_permalink($this->post_id));

        ob_start();
        AceChange_SEO_Meta::output_twitter_card_tags();
        $output = ob_get_clean();

        $this->assertStringContainsString('twitter:card', $output);
        $this->assertStringContainsString('twitter:title', $output);
        $this->assertStringContainsString('twitter:description', $output);
        $this->assertStringContainsString('twitter:image', $output);
        $this->assertStringContainsString('summary_large_image', $output);
    }

    /**
     * Test: Schema.org markup je generovaný
     */
    public function test_schema_markup_output() {
        $this->go_to(get_permalink($this->post_id));

        ob_start();
        AceChange_SEO_Schema::output_schema_markup();
        $output = ob_get_clean();

        // Overenie JSON-LD
        $this->assertStringContainsString('application/ld+json', $output);
        $this->assertStringContainsString('"@context"', $output);
        $this->assertStringContainsString('https://schema.org', $output);

        // Article schema
        $this->assertStringContainsString('"@type":"Article"', $output);
        $this->assertStringContainsString('headline', $output);
        $this->assertStringContainsString('datePublished', $output);

        // Overenie validného JSON
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $output, $matches);
        if (isset($matches[1])) {
            $json = json_decode($matches[1], true);
            $this->assertNotNull($json, 'Schema markup musí byť validný JSON');
        }
    }

    /**
     * Test: Canonical URL je správny
     */
    public function test_canonical_url_output() {
        $this->go_to(get_permalink($this->post_id));

        ob_start();
        AceChange_SEO_Meta::output_canonical_url();
        $output = ob_get_clean();

        $permalink = get_permalink($this->post_id);

        $this->assertStringContainsString('<link rel="canonical"', $output);
        $this->assertStringContainsString($permalink, $output);
    }

    /**
     * Test: XML Sitemap je generovaná
     */
    public function test_sitemap_generation() {
        global $wp_rewrite;
        $wp_rewrite->init();

        // Simulácia požiadavky na sitemap
        $this->go_to(home_url('/sitemap.xml'));
        set_query_var('acechange_sitemap', '1');

        ob_start();
        $sitemap = new AceChange_SEO_Sitemap();
        do_action('template_redirect');
        $output = ob_get_clean();

        // Overenie XML štruktúry
        $this->assertStringContainsString('<?xml', $output);
        $this->assertStringContainsString('<urlset', $output);
        $this->assertStringContainsString('<url>', $output);
        $this->assertStringContainsString('<loc>', $output);

        // Overenie, že obsahuje náš testovací príspevok
        $this->assertStringContainsString(get_permalink($this->post_id), $output);
    }

    /**
     * Test: Robots tag pre search stránky
     */
    public function test_robots_tag_search_pages() {
        $this->go_to(home_url('/?s=test'));

        ob_start();
        AceChange_SEO_Meta::output_meta_tags();
        $output = ob_get_clean();

        // Search stránky by mali mať noindex
        $this->assertStringContainsString('noindex', $output);
        $this->assertStringContainsString('follow', $output);
    }

    /**
     * Test: Vlastný meta description z meta boxu
     */
    public function test_custom_meta_description() {
        $custom_desc = 'This is a custom meta description set via meta box.';
        update_post_meta($this->post_id, '_acechange_meta_description', $custom_desc);

        $this->go_to(get_permalink($this->post_id));

        ob_start();
        AceChange_SEO_Meta::output_meta_tags();
        $output = ob_get_clean();

        $this->assertStringContainsString($custom_desc, $output);
    }

    /**
     * Test: Integračný test - všetky funkcie naraz
     */
    public function test_full_integration() {
        $this->go_to(get_permalink($this->post_id));

        ob_start();
        do_action('wp_head');
        $output = ob_get_clean();

        // Overenie prítomnosti všetkých SEO prvkov
        $required_elements = array(
            '<meta name="description"',
            '<meta property="og:title"',
            '<meta name="twitter:card"',
            '<script type="application/ld+json">',
            '<link rel="canonical"'
        );

        foreach ($required_elements as $element) {
            $this->assertStringContainsString(
                $element,
                $output,
                "SEO output musí obsahovať: {$element}"
            );
        }
    }

    /**
     * Test: Performance - plugin nepridáva veľa dát
     */
    public function test_output_size_performance() {
        $this->go_to(get_permalink($this->post_id));

        ob_start();
        do_action('wp_head');
        $output = ob_get_clean();

        // SEO output by nemal byť väčší ako 10KB
        $size = strlen($output);
        $this->assertLessThan(10240, $size, 'SEO output je príliš veľký (>10KB)');
    }
}
