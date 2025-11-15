<?php
/**
 * Unit testy pre Meta Tags funkcionalitu
 */

class AceChange_SEO_Meta_Unit_Tests extends WP_UnitTestCase {

    /**
     * Test: Skrátenie description na správnu dĺžku
     */
    public function test_truncate_description() {
        $long_text = str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 10);

        $reflection = new ReflectionClass('AceChange_SEO_Meta');
        $method = $reflection->getMethod('truncate_description');
        $method->setAccessible(true);

        $result = $method->invoke(null, $long_text, 160);

        // Overenie dĺžky
        $this->assertLessThanOrEqual(160, strlen($result));

        // Overenie, že končí na slove
        $this->assertStringEndsWith('...', $result);

        // Overenie, že neobsahuje HTML tagy
        $this->assertStringNotContainsString('<', $result);
    }

    /**
     * Test: Skrátenie description na hranici slova
     */
    public function test_truncate_on_word_boundary() {
        $text = 'This is a test sentence that should be truncated properly.';

        $reflection = new ReflectionClass('AceChange_SEO_Meta');
        $method = $reflection->getMethod('truncate_description');
        $method->setAccessible(true);

        $result = $method->invoke(null, $text, 30);

        // Nemal by skrátiť slovo napoly
        $this->assertStringNotContainsString('sen...', $result);
        $this->assertStringNotContainsString('sente...', $result);
    }

    /**
     * Test: Odstránenie HTML tagov z description
     */
    public function test_strip_html_from_description() {
        $html_text = '<p>This is <strong>bold</strong> and <em>italic</em> text.</p>';

        $reflection = new ReflectionClass('AceChange_SEO_Meta');
        $method = $reflection->getMethod('truncate_description');
        $method->setAccessible(true);

        $result = $method->invoke(null, $html_text, 160);

        $this->assertStringNotContainsString('<p>', $result);
        $this->assertStringNotContainsString('<strong>', $result);
        $this->assertStringNotContainsString('<em>', $result);
        $this->assertStringContainsString('This is bold and italic text', $result);
    }

    /**
     * Test: Odstránenie viacerých medzier
     */
    public function test_remove_multiple_spaces() {
        $text_with_spaces = 'This   has    multiple     spaces.';

        $reflection = new ReflectionClass('AceChange_SEO_Meta');
        $method = $reflection->getMethod('truncate_description');
        $method->setAccessible(true);

        $result = $method->invoke(null, $text_with_spaces, 160);

        $this->assertStringNotContainsString('  ', $result);
        $this->assertEquals('This has multiple spaces.', $result);
    }

    /**
     * Test: Krátky text ostane nezmenený
     */
    public function test_short_text_unchanged() {
        $short_text = 'Short description.';

        $reflection = new ReflectionClass('AceChange_SEO_Meta');
        $method = $reflection->getMethod('truncate_description');
        $method->setAccessible(true);

        $result = $method->invoke(null, $short_text, 160);

        $this->assertEquals('Short description.', $result);
        $this->assertStringNotContainsString('...', $result);
    }

    /**
     * Test: Robots meta pre search stránky
     */
    public function test_robots_meta_for_search() {
        // Simulácia search stránky
        $this->go_to(home_url('/?s=test'));

        update_option('acechange_seo_settings', array(
            'noindex_search' => true
        ));

        $reflection = new ReflectionClass('AceChange_SEO_Meta');
        $method = $reflection->getMethod('get_robots_meta');
        $method->setAccessible(true);

        $result = $method->invoke(null);

        $this->assertStringContainsString('noindex', $result);
        $this->assertStringContainsString('follow', $result);
    }

    /**
     * Test: Canonical URL pre single post
     */
    public function test_canonical_url_single_post() {
        $post_id = $this->factory->post->create(array(
            'post_title' => 'Test Post',
            'post_status' => 'publish'
        ));

        $this->go_to(get_permalink($post_id));

        ob_start();
        AceChange_SEO_Meta::output_canonical_url();
        $output = ob_get_clean();

        $this->assertStringContainsString('<link rel="canonical"', $output);
        $this->assertStringContainsString(get_permalink($post_id), $output);

        wp_delete_post($post_id, true);
    }

    /**
     * Test: Získanie obrázku z featured image
     */
    public function test_get_post_image_featured() {
        $post_id = $this->factory->post->create();
        $attachment_id = $this->factory->attachment->create_object(
            'test-image.jpg',
            $post_id,
            array(
                'post_mime_type' => 'image/jpeg',
                'post_type' => 'attachment'
            )
        );

        set_post_thumbnail($post_id, $attachment_id);

        $reflection = new ReflectionClass('AceChange_SEO_Meta');
        $method = $reflection->getMethod('get_post_image');
        $method->setAccessible(true);

        $result = $method->invoke(null, $post_id);

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('.jpg', $result);

        wp_delete_post($post_id, true);
        wp_delete_attachment($attachment_id, true);
    }

    /**
     * Test: Fallback na default obrázok
     */
    public function test_get_post_image_default_fallback() {
        $post_id = $this->factory->post->create(array(
            'post_content' => 'Content without images'
        ));

        $default_image = 'https://example.com/default-image.jpg';
        update_option('acechange_seo_settings', array(
            'social_share_image' => $default_image
        ));

        $reflection = new ReflectionClass('AceChange_SEO_Meta');
        $method = $reflection->getMethod('get_post_image');
        $method->setAccessible(true);

        $result = $method->invoke(null, $post_id);

        $this->assertEquals($default_image, $result);

        wp_delete_post($post_id, true);
    }

    /**
     * Test: Escapovanie HTML v meta tagoch
     */
    public function test_html_escaping_in_meta_tags() {
        $post_id = $this->factory->post->create(array(
            'post_title' => 'Test <script>alert("XSS")</script> Post',
            'post_excerpt' => 'Description with <strong>HTML</strong> & special chars',
            'post_status' => 'publish'
        ));

        $this->go_to(get_permalink($post_id));

        ob_start();
        AceChange_SEO_Meta::output_meta_tags();
        $output = ob_get_clean();

        // Overenie, že HTML je escapovaný
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('<strong>', $output);

        // Overenie, že & je escapované
        $this->assertStringNotContainsString('&', $output);

        wp_delete_post($post_id, true);
    }
}
