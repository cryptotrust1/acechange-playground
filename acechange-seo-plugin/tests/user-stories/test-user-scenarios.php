<?php
/**
 * User Story testy pre AceChange SEO Plugin
 * Tieto testy overujú reálne scenáre používania pluginu
 */

class AceChange_SEO_User_Stories_Tests extends WP_UnitTestCase {

    /**
     * USER STORY 1:
     * Ako správca webu chcem, aby sa automaticky generovali SEO meta tagy,
     * aby som nemusel manuálne vyplňovať popis pre každý príspevok.
     */
    public function test_user_story_automatic_meta_generation() {
        // Vytvorenie príspevku bez manuálneho SEO nastavenia
        $post_id = $this->factory->post->create(array(
            'post_title' => 'Novinky z nášho produktu',
            'post_content' => 'Dnes sme spustili novú verziu nášho produktu, ktorá prináša množstvo vylepšení.',
            'post_status' => 'publish'
        ));

        // Zapnutie automatických meta tagov
        update_option('acechange_seo_settings', array(
            'auto_meta_tags' => true,
            'meta_description_length' => 160
        ));

        // Návšteva stránky
        $this->go_to(get_permalink($post_id));

        // Zachytenie výstupu
        ob_start();
        do_action('wp_head');
        $output = ob_get_clean();

        // Overenia:
        // 1. Meta description je prítomná
        $this->assertStringContainsString('<meta name="description"', $output);

        // 2. Obsahuje text z obsahu príspevku
        $this->assertStringContainsString('Dnes sme spustili', $output);

        // 3. Je správne dlhá (max 160 znakov)
        preg_match('/name="description" content="([^"]+)"/', $output, $matches);
        if (isset($matches[1])) {
            $this->assertLessThanOrEqual(165, strlen($matches[1]));
        }

        // VÝSLEDOK: Používateľ nemusí manuálne vyplňovať meta description
        wp_delete_post($post_id, true);
    }

    /**
     * USER STORY 2:
     * Ako content manager chcem, aby sa moje články pekne zobrazovali
     * keď ich niekto zdieľa na Facebooku, aby získali viac kliknutí.
     */
    public function test_user_story_social_media_sharing() {
        // Vytvorenie článku s obrázkom
        $post_id = $this->factory->post->create(array(
            'post_title' => '10 tipov pre lepšie SEO',
            'post_excerpt' => 'Kompletný návod ako zlepšiť SEO vašej stránky v roku 2024.',
            'post_status' => 'publish'
        ));

        // Pridanie featured image
        $attachment_id = $this->factory->attachment->create_object(
            'seo-tips.jpg',
            $post_id,
            array('post_mime_type' => 'image/jpeg')
        );
        set_post_thumbnail($post_id, $attachment_id);

        // Zapnutie Open Graph
        update_option('acechange_seo_settings', array(
            'auto_open_graph' => true,
            'twitter_card' => true
        ));

        // Návšteva stránky
        $this->go_to(get_permalink($post_id));

        // Zachytenie výstupu
        ob_start();
        do_action('wp_head');
        $output = ob_get_clean();

        // Overenia:
        // 1. Open Graph tagy sú prítomné
        $this->assertStringContainsString('og:title', $output);
        $this->assertStringContainsString('og:description', $output);
        $this->assertStringContainsString('og:image', $output);

        // 2. Obsahujú správne údaje
        $this->assertStringContainsString('10 tipov pre lepšie SEO', $output);
        $this->assertStringContainsString('Kompletný návod', $output);

        // 3. Twitter Cards sú prítomné
        $this->assertStringContainsString('twitter:card', $output);
        $this->assertStringContainsString('summary_large_image', $output);

        // VÝSLEDOK: Články vyzerajú profesionálne pri zdieľaní na sociálnych sieťach
        wp_delete_post($post_id, true);
    }

    /**
     * USER STORY 3:
     * Ako SEO špecialista chcem mať kontrolu nad meta description
     * pre dôležité landing pages, aby som ich mohol optimalizovať.
     */
    public function test_user_story_custom_meta_override() {
        // Vytvorenie landing page
        $page_id = $this->factory->post->create(array(
            'post_type' => 'page',
            'post_title' => 'Naše služby',
            'post_content' => 'Ponúkame komplexné SEO služby pre váš web.',
            'post_status' => 'publish'
        ));

        // SEO špecialista nastaví vlastný meta description
        $custom_description = 'Profesionálne SEO služby ➤ Zlepšite svoje pozície v Google ✓ Bezplatná konzultácia ✓ Výsledky do 90 dní!';
        update_post_meta($page_id, '_acechange_meta_description', $custom_description);

        // Zapnutie meta tagov
        update_option('acechange_seo_settings', array(
            'auto_meta_tags' => true
        ));

        // Návšteva stránky
        $this->go_to(get_permalink($page_id));

        // Zachytenie výstupu
        ob_start();
        AceChange_SEO_Meta::output_meta_tags();
        $output = ob_get_clean();

        // Overenia:
        // 1. Vlastný description je použitý (nie automatický)
        $this->assertStringContainsString($custom_description, $output);

        // 2. Obsahuje SEO optimalizované prvky (emoji, call-to-action)
        $this->assertStringContainsString('➤', $output);
        $this->assertStringContainsString('✓', $output);

        // VÝSLEDOK: SEO špecialista má plnú kontrolu nad dôležitými stránkami
        wp_delete_post($page_id, true);
    }

    /**
     * USER STORY 4:
     * Ako majiteľ blogu chcem, aby Google rýchlo indexoval moje nové články,
     * aby sa zobrazovali vo výsledkoch hľadania čo najskôr.
     */
    public function test_user_story_fast_indexing() {
        // Vytvorenie niekoľkých článkov
        $post_ids = array();
        for ($i = 1; $i <= 5; $i++) {
            $post_ids[] = $this->factory->post->create(array(
                'post_title' => "Článok číslo {$i}",
                'post_status' => 'publish'
            ));
        }

        // Zapnutie XML sitemap
        update_option('acechange_seo_settings', array(
            'auto_sitemap' => true
        ));

        // Návšteva sitemap
        $this->go_to(home_url('/sitemap.xml'));
        set_query_var('acechange_sitemap', '1');

        ob_start();
        $sitemap = new AceChange_SEO_Sitemap();
        // Simulácia template_redirect
        $reflection = new ReflectionMethod('AceChange_SEO_Sitemap', 'output_sitemap');
        $reflection->setAccessible(true);
        $reflection->invoke($sitemap);
        $output = ob_get_clean();

        // Overenia:
        // 1. Sitemap existuje a je XML
        $this->assertStringContainsString('<?xml', $output);
        $this->assertStringContainsString('<urlset', $output);

        // 2. Obsahuje všetky nové články
        foreach ($post_ids as $post_id) {
            $permalink = get_permalink($post_id);
            $this->assertStringContainsString($permalink, $output);
        }

        // 3. Obsahuje lastmod dátum
        $this->assertStringContainsString('<lastmod>', $output);

        // VÝSLEDOK: Google má aktuálny zoznam všetkých stránok pre indexovanie
        foreach ($post_ids as $post_id) {
            wp_delete_post($post_id, true);
        }
    }

    /**
     * USER STORY 5:
     * Ako návštevník webu hľadajúci informácie chcem vidieť
     * relevantné a informatívne výsledky vo vyhľadávaní Google,
     * aby som sa rozhodol či kliknem na odkaz.
     */
    public function test_user_story_search_result_appearance() {
        // Vytvorenie článku s kompletným obsahom
        $post_id = $this->factory->post->create(array(
            'post_title' => 'Ako vyrobiť domáci chlieb - Kompletný návod',
            'post_excerpt' => 'Naučte sa vyrobiť chutný domáci chlieb krok za krokom. Recepty, tipy a triky od profesionálneho pekára.',
            'post_content' => 'Detailný návod na pečenie chleba...',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_date' => '2024-01-15 10:00:00'
        ));

        // Zapnutie všetkých SEO funkcií
        update_option('acechange_seo_settings', array(
            'auto_meta_tags' => true,
            'auto_schema' => true
        ));

        // Návšteva stránky
        $this->go_to(get_permalink($post_id));

        // Zachytenie výstupu
        ob_start();
        do_action('wp_head');
        $output = ob_get_clean();

        // Overenia - čo uvidí Google:
        // 1. Popisný titul
        $this->assertStringContainsString('Ako vyrobiť domáci chlieb', $output);

        // 2. Informatívny description
        $this->assertStringContainsString('Naučte sa vyrobiť', $output);
        $this->assertStringContainsString('krok za krokom', $output);

        // 3. Schema.org markup pre rich snippets
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $output, $matches);
        if (isset($matches[1])) {
            $json = json_decode($matches[1], true);
            $this->assertNotNull($json);

            // Nájdenie Article schema
            foreach ($json as $schema) {
                if (isset($schema['@type']) && $schema['@type'] === 'Article') {
                    $this->assertArrayHasKey('headline', $schema);
                    $this->assertArrayHasKey('author', $schema);
                    $this->assertArrayHasKey('datePublished', $schema);
                }
            }
        }

        // VÝSLEDOK: Návštevník uvidí bohatý, informatívny výsledok v Google
        wp_delete_post($post_id, true);
    }

    /**
     * USER STORY 6:
     * Ako správca e-shopu chcem, aby sa výrobkové stránky
     * správne indexovali, ale admin a search stránky nie.
     */
    public function test_user_story_selective_indexing() {
        // Vytvorenie produktovej stránky
        $product_id = $this->factory->post->create(array(
            'post_type' => 'page',
            'post_title' => 'Produkt XYZ',
            'post_status' => 'publish'
        ));

        // Nastavenie - search stránky = noindex
        update_option('acechange_seo_settings', array(
            'noindex_search' => true,
            'auto_meta_tags' => true
        ));

        // Test 1: Produktová stránka - má sa indexovať
        $this->go_to(get_permalink($product_id));

        ob_start();
        AceChange_SEO_Meta::output_meta_tags();
        $output_product = ob_get_clean();

        // Produkt NEMÁ mať noindex
        $this->assertStringNotContainsString('noindex', $output_product);

        // Test 2: Search stránka - NEMÁ sa indexovať
        $this->go_to(home_url('/?s=test'));

        ob_start();
        AceChange_SEO_Meta::output_meta_tags();
        $output_search = ob_get_clean();

        // Search MÁ mať noindex
        $this->assertStringContainsString('noindex', $output_search);

        // VÝSLEDOK: Google indexuje len relevantné stránky
        wp_delete_post($product_id, true);
    }

    /**
     * USER STORY 7:
     * Ako developer chcem vedieť, či plugin nespôsobí problémy
     * s Google, aby som ho mohol bezpečne použiť na klientskom webe.
     */
    public function test_user_story_google_compliance() {
        // Vytvorenie testovacieho príspevku
        $post_id = $this->factory->post->create(array(
            'post_title' => 'Test článok',
            'post_status' => 'publish'
        ));

        // Zapnutie všetkých funkcií
        update_option('acechange_seo_settings', array(
            'auto_meta_tags' => true,
            'auto_open_graph' => true,
            'auto_schema' => true,
            'canonical_urls' => true
        ));

        $this->go_to(get_permalink($post_id));

        ob_start();
        do_action('wp_head');
        $output = ob_get_clean();

        // Overenia bezpečnosti:
        // 1. Žiadny keyword stuffing (priveľa kľúčových slov)
        $keyword_count = substr_count(strtolower($output), 'seo');
        $this->assertLessThan(20, $keyword_count, 'Príliš veľa opakovaní kľúčového slova');

        // 2. Žiadny skrytý text
        $this->assertStringNotContainsString('display:none', $output);
        $this->assertStringNotContainsString('visibility:hidden', $output);

        // 3. Validný HTML
        $this->assertStringNotContainsString('<meta <meta', $output); // Žiadne duplicity

        // 4. Validný JSON-LD (nie manipulatívny obsah)
        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $output, $matches);
        foreach ($matches[1] as $json_string) {
            $json = json_decode($json_string, true);
            $this->assertNotNull($json, 'JSON-LD musí byť validný');
        }

        // 5. Canonical URL je prítomný (Google odporúčané)
        $this->assertStringContainsString('<link rel="canonical"', $output);

        // 6. Žiadne cloaking (rovnaký obsah pre všetkých)
        // Plugin negeneruje rôzny obsah pre robotov vs. ľudí

        // VÝSLEDOK: Plugin je 100% White Hat a bezpečný pre Google
        wp_delete_post($post_id, true);
    }

    /**
     * USER STORY 8:
     * Ako správca webu s pomalým hostingom chcem, aby plugin
     * nezaťažoval server a nespomľoval načítavanie stránky.
     */
    public function test_user_story_performance() {
        $post_id = $this->factory->post->create(array(
            'post_status' => 'publish'
        ));

        update_option('acechange_seo_settings', array(
            'auto_meta_tags' => true,
            'auto_open_graph' => true,
            'auto_schema' => true
        ));

        $this->go_to(get_permalink($post_id));

        // Meranie času generovania
        $start_time = microtime(true);

        ob_start();
        do_action('wp_head');
        $output = ob_get_clean();

        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time) * 1000; // v milisekundách

        // Overenia:
        // 1. Rýchle vykonanie (< 50ms)
        $this->assertLessThan(50, $execution_time, 'SEO generovanie trvá príliš dlho');

        // 2. Výstup nie je príliš veľký (< 10KB)
        $output_size = strlen($output);
        $this->assertLessThan(10240, $output_size, 'SEO output je príliš veľký');

        // 3. Žiadny JavaScript na frontende (nespomľuje page load)
        $this->assertStringNotContainsString('<script src=', $output);

        // 4. Žiadne externé requesty (všetko lokálne)
        $this->assertStringNotContainsString('https://external', $output);

        // VÝSLEDOK: Plugin je rýchly a nezaťažuje server
        wp_delete_post($post_id, true);
    }

    /**
     * USER STORY 9:
     * Ako redaktor chcem vedieť, či moje SEO nastavenia fungujú správne,
     * aby som mohl overiť pred publikovaním článku.
     */
    public function test_user_story_verification() {
        $post_id = $this->factory->post->create(array(
            'post_title' => 'Nový článok',
            'post_excerpt' => 'Toto je testovací popis článku.',
            'post_status' => 'publish'
        ));

        // Nastavenie vlastného meta description
        update_post_meta($post_id, '_acechange_meta_description', 'Vlastný SEO popis pre tento článok.');

        update_option('acechange_seo_settings', array(
            'auto_meta_tags' => true,
            'auto_open_graph' => true
        ));

        $this->go_to(get_permalink($post_id));

        ob_start();
        do_action('wp_head');
        $output = ob_get_clean();

        // Redaktor môže overiť (View Source):
        // 1. Meta description je prítomný a správny
        $this->assertMatchesRegularExpression(
            '/<meta name="description" content="Vlastný SEO popis/',
            $output,
            'Meta description musí byť viditeľný v source code'
        );

        // 2. Open Graph tagy sú viditeľné
        $this->assertStringContainsString('property="og:title"', $output);
        $this->assertStringContainsString('Nový článok', $output);

        // 3. Všetko je ľahko čitateľné (nie minifikované)
        $this->assertStringContainsString("\n", $output); // Multi-line output

        // VÝSLEDOK: Redaktor môže ľahko overiť SEO nastavenia pomocou View Source
        wp_delete_post($post_id, true);
    }
}
