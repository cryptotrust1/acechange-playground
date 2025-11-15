<?php
/**
 * Unit testy pre Schema.org funkcionalitu
 */

class AceChange_SEO_Schema_Unit_Tests extends WP_UnitTestCase {

    /**
     * Test: Organization schema obsahuje povinné polia
     */
    public function test_organization_schema_structure() {
        $reflection = new ReflectionClass('AceChange_SEO_Schema');
        $method = $reflection->getMethod('get_organization_schema');
        $method->setAccessible(true);

        $schema = $method->invoke(null);

        // Povinné polia
        $this->assertArrayHasKey('@context', $schema);
        $this->assertArrayHasKey('@type', $schema);
        $this->assertArrayHasKey('name', $schema);
        $this->assertArrayHasKey('url', $schema);

        // Správne hodnoty
        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('Organization', $schema['@type']);
        $this->assertEquals(get_bloginfo('name'), $schema['name']);
    }

    /**
     * Test: Article schema pre blog post
     */
    public function test_article_schema_structure() {
        $post_id = $this->factory->post->create(array(
            'post_title' => 'Test Article',
            'post_content' => 'Article content',
            'post_excerpt' => 'Article excerpt',
            'post_status' => 'publish',
            'post_author' => 1
        ));

        $this->go_to(get_permalink($post_id));

        $reflection = new ReflectionClass('AceChange_SEO_Schema');
        $method = $reflection->getMethod('get_article_schema');
        $method->setAccessible(true);

        $schema = $method->invoke(null);

        // Povinné polia pre Article
        $this->assertArrayHasKey('@context', $schema);
        $this->assertArrayHasKey('@type', $schema);
        $this->assertArrayHasKey('headline', $schema);
        $this->assertArrayHasKey('datePublished', $schema);
        $this->assertArrayHasKey('dateModified', $schema);
        $this->assertArrayHasKey('author', $schema);
        $this->assertArrayHasKey('publisher', $schema);

        // Správne hodnoty
        $this->assertEquals('Article', $schema['@type']);
        $this->assertEquals('Test Article', $schema['headline']);

        // Author je objektom typu Person
        $this->assertArrayHasKey('@type', $schema['author']);
        $this->assertEquals('Person', $schema['author']['@type']);

        // Publisher je objektom typu Organization
        $this->assertArrayHasKey('@type', $schema['publisher']);
        $this->assertEquals('Organization', $schema['publisher']['@type']);

        wp_delete_post($post_id, true);
    }

    /**
     * Test: Breadcrumb schema generovanie
     */
    public function test_breadcrumb_schema_structure() {
        $category_id = $this->factory->category->create(array('name' => 'Test Category'));
        $post_id = $this->factory->post->create(array(
            'post_title' => 'Test Post',
            'post_category' => array($category_id),
            'post_status' => 'publish'
        ));

        $this->go_to(get_permalink($post_id));

        $reflection = new ReflectionClass('AceChange_SEO_Schema');
        $method = $reflection->getMethod('get_breadcrumb_schema');
        $method->setAccessible(true);

        $schema = $method->invoke(null);

        // Povinné polia
        $this->assertArrayHasKey('@context', $schema);
        $this->assertArrayHasKey('@type', $schema);
        $this->assertArrayHasKey('itemListElement', $schema);

        // Typ
        $this->assertEquals('BreadcrumbList', $schema['@type']);

        // Položky breadcrumb
        $this->assertIsArray($schema['itemListElement']);
        $this->assertGreaterThanOrEqual(2, count($schema['itemListElement']));

        // Prvý item je domov
        $first_item = $schema['itemListElement'][0];
        $this->assertEquals('ListItem', $first_item['@type']);
        $this->assertEquals(1, $first_item['position']);
        $this->assertEquals('Domov', $first_item['name']);

        wp_delete_post($post_id, true);
    }

    /**
     * Test: WebPage schema pre stránky
     */
    public function test_webpage_schema_structure() {
        $page_id = $this->factory->post->create(array(
            'post_type' => 'page',
            'post_title' => 'Test Page',
            'post_content' => 'Page content',
            'post_status' => 'publish'
        ));

        $this->go_to(get_permalink($page_id));

        $reflection = new ReflectionClass('AceChange_SEO_Schema');
        $method = $reflection->getMethod('get_webpage_schema');
        $method->setAccessible(true);

        $schema = $method->invoke(null);

        // Povinné polia
        $this->assertArrayHasKey('@context', $schema);
        $this->assertArrayHasKey('@type', $schema);
        $this->assertArrayHasKey('name', $schema);
        $this->assertArrayHasKey('url', $schema);

        // Správne hodnoty
        $this->assertEquals('WebPage', $schema['@type']);
        $this->assertEquals('Test Page', $schema['name']);

        wp_delete_post($page_id, true);
    }

    /**
     * Test: Validný JSON-LD výstup
     */
    public function test_valid_json_ld_output() {
        $post_id = $this->factory->post->create(array(
            'post_status' => 'publish'
        ));

        $this->go_to(get_permalink($post_id));

        ob_start();
        AceChange_SEO_Schema::output_schema_markup();
        $output = ob_get_clean();

        // Extrakcia JSON z script tagu
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $output, $matches);

        $this->assertNotEmpty($matches, 'JSON-LD script tag musí byť prítomný');

        if (isset($matches[1])) {
            $json = json_decode($matches[1], true);

            $this->assertNotNull($json, 'JSON-LD musí byť validný JSON');
            $this->assertEquals(JSON_ERROR_NONE, json_last_error());

            // Overenie, že je to pole objektov
            $this->assertIsArray($json);

            // Každý objekt musí mať @context a @type
            foreach ($json as $schema_object) {
                $this->assertArrayHasKey('@context', $schema_object);
                $this->assertArrayHasKey('@type', $schema_object);
            }
        }

        wp_delete_post($post_id, true);
    }

    /**
     * Test: Schema obsahuje obrázok ak existuje
     */
    public function test_schema_includes_image() {
        $post_id = $this->factory->post->create(array(
            'post_status' => 'publish'
        ));

        $attachment_id = $this->factory->attachment->create_object(
            'test-image.jpg',
            $post_id,
            array(
                'post_mime_type' => 'image/jpeg'
            )
        );

        set_post_thumbnail($post_id, $attachment_id);

        $this->go_to(get_permalink($post_id));

        $reflection = new ReflectionClass('AceChange_SEO_Schema');
        $method = $reflection->getMethod('get_article_schema');
        $method->setAccessible(true);

        $schema = $method->invoke(null);

        // Overenie prítomnosti obrázku
        $this->assertArrayHasKey('image', $schema);
        $this->assertArrayHasKey('@type', $schema['image']);
        $this->assertEquals('ImageObject', $schema['image']['@type']);
        $this->assertArrayHasKey('url', $schema['image']);

        wp_delete_post($post_id, true);
        wp_delete_attachment($attachment_id, true);
    }

    /**
     * Test: Escapovanie špeciálnych znakov v Schema
     */
    public function test_schema_special_characters_escaping() {
        $post_id = $this->factory->post->create(array(
            'post_title' => 'Test "Quotes" & Ampersand',
            'post_content' => 'Content with special chars: < > & " \'',
            'post_status' => 'publish'
        ));

        $this->go_to(get_permalink($post_id));

        ob_start();
        AceChange_SEO_Schema::output_schema_markup();
        $output = ob_get_clean();

        // JSON by mal byť validný napriek špeciálnym znakom
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $output, $matches);

        if (isset($matches[1])) {
            $json = json_decode($matches[1], true);
            $this->assertNotNull($json, 'JSON musí byť validný aj so špeciálnymi znakmi');
        }

        wp_delete_post($post_id, true);
    }
}
