<?php
/**
 * Admin rozhranie pre AceChange SEO Plugin
 * Obsahuje nastavenia a dokumentáciu
 */

if (!defined('ABSPATH')) {
    exit;
}

class AceChange_SEO_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box_data'));
    }

    /**
     * Pridanie menu do admin panelu
     */
    public function add_admin_menu() {
        add_menu_page(
            'AceChange SEO',
            'AceChange SEO',
            'manage_options',
            'acechange-seo',
            array($this, 'render_settings_page'),
            'dashicons-search',
            80
        );

        add_submenu_page(
            'acechange-seo',
            'Nastavenia',
            'Nastavenia',
            'manage_options',
            'acechange-seo',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'acechange-seo',
            'Dokumentácia',
            'Dokumentácia',
            'manage_options',
            'acechange-seo-docs',
            array($this, 'render_documentation_page')
        );

        add_submenu_page(
            'acechange-seo',
            'Google Compliance',
            'Google Compliance',
            'manage_options',
            'acechange-seo-compliance',
            array($this, 'render_compliance_page')
        );
    }

    /**
     * Registrácia nastavení
     */
    public function register_settings() {
        register_setting('acechange_seo_settings', 'acechange_seo_settings', array($this, 'sanitize_settings'));
    }

    /**
     * Sanitizácia nastavení
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        $sanitized['auto_meta_tags'] = !empty($input['auto_meta_tags']);
        $sanitized['auto_open_graph'] = !empty($input['auto_open_graph']);
        $sanitized['auto_schema'] = !empty($input['auto_schema']);
        $sanitized['auto_sitemap'] = !empty($input['auto_sitemap']);
        $sanitized['meta_description_length'] = absint($input['meta_description_length']);
        $sanitized['auto_keywords'] = !empty($input['auto_keywords']);
        $sanitized['social_share_image'] = esc_url_raw($input['social_share_image']);
        $sanitized['twitter_card'] = !empty($input['twitter_card']);
        $sanitized['canonical_urls'] = !empty($input['canonical_urls']);
        $sanitized['noindex_archives'] = !empty($input['noindex_archives']);
        $sanitized['noindex_search'] = !empty($input['noindex_search']);

        return $sanitized;
    }

    /**
     * Načítanie admin štýlov a skriptov
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'acechange-seo') === false) {
            return;
        }

        wp_enqueue_style(
            'acechange-seo-admin',
            ACECHANGE_SEO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ACECHANGE_SEO_VERSION
        );

        wp_enqueue_script(
            'acechange-seo-admin',
            ACECHANGE_SEO_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            ACECHANGE_SEO_VERSION,
            true
        );
    }

    /**
     * Vykreslenie stránky nastavení
     */
    public function render_settings_page() {
        $settings = get_option('acechange_seo_settings', array());
        ?>
        <div class="wrap acechange-seo-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="acechange-seo-header">
                <h2>🚀 Profesionálny SEO Plugin pre WordPress</h2>
                <p class="description">
                    Automatická optimalizácia vašej stránky pre vyhľadávače. 100% White Hat - bezpečné pre Google.
                </p>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('acechange_seo_settings'); ?>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="auto_meta_tags">Meta Tagy</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="acechange_seo_settings[auto_meta_tags]" id="auto_meta_tags" value="1" <?php checked(!empty($settings['auto_meta_tags'])); ?>>
                                    Automaticky generovať meta description a robots tagy
                                </label>
                                <p class="description">Optimalizuje meta tagy pre každú stránku.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="meta_description_length">Dĺžka Meta Description</label>
                            </th>
                            <td>
                                <input type="number" name="acechange_seo_settings[meta_description_length]" id="meta_description_length" value="<?php echo esc_attr($settings['meta_description_length'] ?? 160); ?>" min="120" max="320" class="small-text">
                                <p class="description">Odporúčané: 150-160 znakov (Google zobrazí max. ~160 znakov)</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="auto_open_graph">Open Graph</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="acechange_seo_settings[auto_open_graph]" id="auto_open_graph" value="1" <?php checked(!empty($settings['auto_open_graph'])); ?>>
                                    Aktivovať Open Graph tagy pre sociálne siete
                                </label>
                                <p class="description">Optimalizuje vzhľad odkazov na Facebook, LinkedIn a iných platformách.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="twitter_card">Twitter Cards</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="acechange_seo_settings[twitter_card]" id="twitter_card" value="1" <?php checked(!empty($settings['twitter_card'])); ?>>
                                    Aktivovať Twitter Card tagy
                                </label>
                                <p class="description">Optimalizuje vzhľad odkazov na Twitter/X.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="auto_schema">Schema.org Markup</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="acechange_seo_settings[auto_schema]" id="auto_schema" value="1" <?php checked(!empty($settings['auto_schema'])); ?>>
                                    Generovať štruktúrované dáta (JSON-LD)
                                </label>
                                <p class="description">Rich snippets pre Google (články, breadcrumbs, organization).</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="canonical_urls">Canonical URLs</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="acechange_seo_settings[canonical_urls]" id="canonical_urls" value="1" <?php checked(!empty($settings['canonical_urls'])); ?>>
                                    Pridať canonical URL tagy
                                </label>
                                <p class="description">Predchádza problémom s duplicitným obsahom.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="auto_sitemap">XML Sitemap</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="acechange_seo_settings[auto_sitemap]" id="auto_sitemap" value="1" <?php checked(!empty($settings['auto_sitemap'])); ?>>
                                    Automaticky generovať XML sitemap
                                </label>
                                <p class="description">
                                    Dostupná na: <a href="<?php echo esc_url(home_url('/sitemap.xml')); ?>" target="_blank"><?php echo esc_url(home_url('/sitemap.xml')); ?></a>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="social_share_image">Predvolený Obrázok</label>
                            </th>
                            <td>
                                <input type="url" name="acechange_seo_settings[social_share_image]" id="social_share_image" value="<?php echo esc_attr($settings['social_share_image'] ?? ''); ?>" class="regular-text">
                                <p class="description">URL obrázka pre stránky bez featured image (odporúčané: 1200x630px)</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="noindex_search">NoIndex pre Vyhľadávanie</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="acechange_seo_settings[noindex_search]" id="noindex_search" value="1" <?php checked(!empty($settings['noindex_search'])); ?>>
                                    Skryť výsledky vyhľadávania pred robotmi
                                </label>
                                <p class="description">Odporúčané: áno (search stránky nemajú hodnotu pre SEO)</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="noindex_archives">NoIndex pre Archívy</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="acechange_seo_settings[noindex_archives]" id="noindex_archives" value="1" <?php checked(!empty($settings['noindex_archives'])); ?>>
                                    Skryť archívne stránky pred robotmi
                                </label>
                                <p class="description">Závisí od typu webu - pre blog odporúčame vypnúť.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button('Uložiť nastavenia'); ?>
            </form>

            <div class="acechange-seo-info-box">
                <h3>✅ Rýchly prehľad funkcií</h3>
                <ul>
                    <li><strong>Meta Tagy:</strong> Automaticky generované description, robots, viewport tagy</li>
                    <li><strong>Open Graph:</strong> Optimalizácia pre Facebook, LinkedIn (og:title, og:description, og:image)</li>
                    <li><strong>Twitter Cards:</strong> Rich media pre Twitter/X</li>
                    <li><strong>Schema.org:</strong> Štruktúrované dáta pre Google Rich Snippets</li>
                    <li><strong>XML Sitemap:</strong> Automatická mapa stránky pre Google Search Console</li>
                    <li><strong>Canonical URLs:</strong> Prevencia duplicitného obsahu</li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Vykreslenie stránky dokumentácie
     */
    public function render_documentation_page() {
        ?>
        <div class="wrap acechange-seo-admin">
            <h1>📚 Dokumentácia - AceChange SEO Plugin</h1>

            <div class="acechange-seo-docs">
                <h2>Ako funguje tento plugin?</h2>
                <p>
                    AceChange SEO Plugin automaticky optimalizuje vašu WordPress stránku pre vyhľadávače a sociálne siete.
                    Plugin pracuje na pozadí a pridáva dôležité SEO prvky do kódu vašej stránky bez potreby manuálnej konfigurácie.
                </p>

                <h3>🔧 Hlavné funkcie</h3>

                <h4>1. Meta Tagy</h4>
                <p>Plugin automaticky generuje:</p>
                <ul>
                    <li><strong>Meta Description:</strong> Krátky popis stránky (zobrazuje sa vo výsledkoch vyhľadávania)</li>
                    <li><strong>Robots Tag:</strong> Inštrukcie pre roboty vyhľadávačov</li>
                    <li><strong>Viewport:</strong> Optimalizácia pre mobilné zariadenia</li>
                </ul>
                <p><strong>Ako to funguje:</strong></p>
                <ol>
                    <li>Pre príspevky používa excerpt alebo prvých 160 znakov obsahu</li>
                    <li>Pre kategórie používa popis kategórie</li>
                    <li>Môžete nastaviť vlastný popis v meta boxe pri úprave príspevku</li>
                </ol>

                <h4>2. Open Graph Tagy</h4>
                <p>Optimalizuje vzhľad odkazov na sociálnych sieťach (Facebook, LinkedIn, WhatsApp):</p>
                <ul>
                    <li>Automaticky pridá titul, popis a obrázok</li>
                    <li>Používa featured image z príspevku</li>
                    <li>Ak nie je nastavený obrázok, použije predvolený z nastavení</li>
                </ul>

                <h4>3. Twitter Cards</h4>
                <p>Podobné ako Open Graph, ale špecificky pre Twitter/X:</p>
                <ul>
                    <li>Generuje "summary_large_image" karty</li>
                    <li>Automaticky preberá údaje z príspevku</li>
                </ul>

                <h4>4. Schema.org Markup (Štruktúrované dáta)</h4>
                <p>Pomáha Google lepšie pochopiť váš obsah:</p>
                <ul>
                    <li><strong>Organization Schema:</strong> Informácie o vašej organizácii/webe</li>
                    <li><strong>Article Schema:</strong> Detaily o článkoch (autor, dátum publikovania, obrázok)</li>
                    <li><strong>Breadcrumb Schema:</strong> Navigačná cesta pre lepšiu orientáciu</li>
                </ul>
                <p><strong>Výsledok:</strong> Rich snippets vo vyhľadávaní (hodnotenie hvezdičkami, breadcrumbs, atď.)</p>

                <h4>5. XML Sitemap</h4>
                <p>Automaticky vytvorená mapa stránky:</p>
                <ul>
                    <li>Dostupná na: <code><?php echo esc_url(home_url('/sitemap.xml')); ?></code></li>
                    <li>Obsahuje všetky stránky, príspevky a kategórie</li>
                    <li>Priorita: hlavná stránka (1.0), stránky (0.8), príspevky (0.6), kategórie (0.4)</li>
                </ul>
                <p><strong>Odporúčanie:</strong> Odošlite sitemap do Google Search Console</p>

                <h4>6. Canonical URLs</h4>
                <p>Predchádza problémom s duplicitným obsahom:</p>
                <ul>
                    <li>Každá stránka má jednoznačnú "canonical" URL</li>
                    <li>Google vie, ktorú verziu stránky indexovať</li>
                </ul>

                <h3>⚙️ Ako nastaviť plugin</h3>

                <h4>Prvé kroky:</h4>
                <ol>
                    <li>Nainštalujte a aktivujte plugin</li>
                    <li>Choďte do <strong>AceChange SEO → Nastavenia</strong></li>
                    <li>Zapnite funkcie, ktoré chcete používať (odporúčame všetky)</li>
                    <li>Nastavte predvolený obrázok pre social sharing (odporúčané: 1200x630px)</li>
                    <li>Uložte nastavenia</li>
                </ol>

                <h4>Pre jednotlivé príspevky:</h4>
                <ol>
                    <li>Pri úprave príspevku nájdete meta box "AceChange SEO"</li>
                    <li>Môžete nastaviť vlastný:
                        <ul>
                            <li>Meta Description (popis pre vyhľadávače)</li>
                            <li>Robots Tag (indexovanie)</li>
                        </ul>
                    </li>
                    <li>Ak necháte prázdne, použijú sa automatické hodnoty</li>
                </ol>

                <h4>XML Sitemap v Google Search Console:</h4>
                <ol>
                    <li>Choďte do <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a></li>
                    <li>Vyberte vašu stránku</li>
                    <li>V ľavom menu kliknite na "Sitemaps"</li>
                    <li>Pridajte: <code>sitemap.xml</code></li>
                    <li>Kliknite "Odoslať"</li>
                </ol>

                <h3>🎯 Odporúčané nastavenia</h3>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Funkcia</th>
                            <th>Odporúčané</th>
                            <th>Dôvod</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Meta Tagy</td>
                            <td>✅ Zapnúť</td>
                            <td>Základ SEO</td>
                        </tr>
                        <tr>
                            <td>Open Graph</td>
                            <td>✅ Zapnúť</td>
                            <td>Lepší vzhľad na sociálnych sieťach</td>
                        </tr>
                        <tr>
                            <td>Twitter Cards</td>
                            <td>✅ Zapnúť</td>
                            <td>Optimalizácia pre Twitter/X</td>
                        </tr>
                        <tr>
                            <td>Schema.org</td>
                            <td>✅ Zapnúť</td>
                            <td>Rich snippets v Googlu</td>
                        </tr>
                        <tr>
                            <td>XML Sitemap</td>
                            <td>✅ Zapnúť</td>
                            <td>Rýchlejšie indexovanie</td>
                        </tr>
                        <tr>
                            <td>Canonical URLs</td>
                            <td>✅ Zapnúť</td>
                            <td>Predchádza duplicate content</td>
                        </tr>
                        <tr>
                            <td>NoIndex Search</td>
                            <td>✅ Zapnúť</td>
                            <td>Search stránky nemajú SEO hodnotu</td>
                        </tr>
                        <tr>
                            <td>NoIndex Archives</td>
                            <td>❌ Vypnúť</td>
                            <td>Pre blogy sú archívy užitočné</td>
                        </tr>
                    </tbody>
                </table>

                <h3>💡 Tipy a triky</h3>
                <ul>
                    <li><strong>Featured Images:</strong> Vždy pridajte featured image k príspevkom (odporúčané: 1200x630px)</li>
                    <li><strong>Excerpts:</strong> Napíšte vlastný excerpt - bude použitý ako meta description</li>
                    <li><strong>Tituly:</strong> Optimálna dĺžka titulu: 50-60 znakov</li>
                    <li><strong>Descriptions:</strong> Optimálna dĺžka: 150-160 znakov</li>
                    <li><strong>Kategórie:</strong> Pridajte popis ku kategóriám - zlepší to SEO archívnych stránok</li>
                </ul>

                <h3>🔍 Overenie, že všetko funguje</h3>
                <ol>
                    <li><strong>Meta Tagy:</strong>
                        <ul>
                            <li>Otvorte vašu stránku</li>
                            <li>Kliknite pravým tlačidlom → "Zobraziť zdroj stránky"</li>
                            <li>Hľadajte <code>&lt;meta name="description"</code></li>
                        </ul>
                    </li>
                    <li><strong>Open Graph:</strong>
                        <ul>
                            <li>Použijte <a href="https://developers.facebook.com/tools/debug/" target="_blank">Facebook Debugger</a></li>
                            <li>Vložte URL vašej stránky</li>
                        </ul>
                    </li>
                    <li><strong>Schema.org:</strong>
                        <ul>
                            <li>Použijte <a href="https://search.google.com/test/rich-results" target="_blank">Google Rich Results Test</a></li>
                            <li>Vložte URL vašej stránky</li>
                        </ul>
                    </li>
                    <li><strong>Sitemap:</strong>
                        <ul>
                            <li>Otvorte <code><?php echo esc_url(home_url('/sitemap.xml')); ?></code></li>
                            <li>Malo by sa zobraziť XML</li>
                        </ul>
                    </li>
                </ol>

                <h3>❓ Často kladené otázky</h3>

                <h4>Q: Môžem používať tento plugin spolu s Yoast SEO alebo Rank Math?</h4>
                <p>A: Technicky áno, ale nie je to odporúčané. Použite len jeden SEO plugin, aby nedochádzalo ku konfliktom.</p>

                <h4>Q: Ako dlho trvá, kým uvidím výsledky v Googlu?</h4>
                <p>A: Google potrebuje čas na re-indexáciu (typicky 1-4 týždne). Môžete urýchliť pomocou Google Search Console.</p>

                <h4>Q: Musia byť všetky funkcie zapnuté?</h4>
                <p>A: Nie, ale odporúčame to. Každá funkcia zlepšuje SEO z iného uhla pohľadu.</p>

                <h4>Q: Čo ak nemám featured image?</h4>
                <p>A: Nastavte predvolený obrázok v nastaveniach. Plugin ho použije ako fallback.</p>
            </div>
        </div>
        <?php
    }

    /**
     * Vykreslenie stránky Google Compliance
     */
    public function render_compliance_page() {
        ?>
        <div class="wrap acechange-seo-admin">
            <h1>✅ Google Compliance - Bezpečnosť a Pravidlá</h1>

            <div class="acechange-seo-compliance">
                <div class="notice notice-success">
                    <p><strong>✅ Tento plugin je 100% White Hat a bezpečný pre Google!</strong></p>
                </div>

                <h2>🛡️ Prečo je tento plugin bezpečný?</h2>

                <h3>1. White Hat SEO techniky</h3>
                <p>Plugin používa <strong>výhradne schválené SEO techniky</strong>, ktoré sú v súlade s Google Webmaster Guidelines:</p>
                <ul>
                    <li>✅ Štruktúrované dáta podľa Schema.org štandardov</li>
                    <li>✅ Meta tagy podľa HTML5 špecifikácie</li>
                    <li>✅ Open Graph protokol (podporovaný Facebookom a Google)</li>
                    <li>✅ Sitemap v XML formáte (odporúčaný Google)</li>
                    <li>✅ Canonical URLs (oficiálne podporované Google)</li>
                </ul>

                <h3>2. Čo plugin NEROBÍ (Black Hat techniky)</h3>
                <p>Plugin sa vyhýba všetkým zakázaným praktikám:</p>
                <ul>
                    <li>❌ ŽIADNE keyword stuffing</li>
                    <li>❌ ŽIADNY skrytý text (hidden text)</li>
                    <li>❌ ŽIADNE cloaking (zobrazenie iného obsahu robotom)</li>
                    <li>❌ ŽIADNE automatické generovanie nízkokvalitného obsahu</li>
                    <li>❌ ŽIADNE link schemes alebo kupovanie linkov</li>
                    <li>❌ ŽIADNA manipulácia s PageRank</li>
                    <li>❌ ŽIADNE doorway pages</li>
                    <li>❌ ŽIADNE scraped content</li>
                </ul>

                <h3>3. Google oficiálne podporuje tieto techniky</h3>

                <h4>Schema.org (Štruktúrované dáta)</h4>
                <p>
                    <strong>Zdroj:</strong> <a href="https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data" target="_blank">Google Structured Data Documentation</a>
                </p>
                <blockquote>
                    "Google používa štruktúrované dáta na pochopenie obsahu stránky a zobrazenie bohatších výsledkov vyhľadávania (rich results)."
                </blockquote>

                <h4>Open Graph Protocol</h4>
                <p>
                    <strong>Zdroj:</strong> <a href="https://ogp.me/" target="_blank">The Open Graph Protocol</a>
                </p>
                <blockquote>
                    "Open Graph protokol umožňuje akejkoľvek webovej stránke stať sa bohatým objektom v sociálnom grafe."
                </blockquote>

                <h4>XML Sitemaps</h4>
                <p>
                    <strong>Zdroj:</strong> <a href="https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview" target="_blank">Google Sitemaps Documentation</a>
                </p>
                <blockquote>
                    "Sitemap je súbor, v ktorom poskytnete informácie o stránkach, videách a iných súboroch na vašom webe a vzťahoch medzi nimi."
                </blockquote>

                <h4>Canonical URLs</h4>
                <p>
                    <strong>Zdroj:</strong> <a href="https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls" target="_blank">Google Canonical URLs Documentation</a>
                </p>
                <blockquote>
                    "Canonical URL je URL stránky, ktorú Google považuje za najreprezentativnejšiu zo skupiny duplicitných stránok."
                </blockquote>

                <h3>4. Prečo vás Google NEBUDE penalizovať</h3>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Technika</th>
                            <th>Typ</th>
                            <th>Google Postoj</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Meta Description</td>
                            <td>White Hat</td>
                            <td>✅ Oficiálne odporúčané</td>
                        </tr>
                        <tr>
                            <td>Schema.org Markup</td>
                            <td>White Hat</td>
                            <td>✅ Oficiálne podporované</td>
                        </tr>
                        <tr>
                            <td>Open Graph</td>
                            <td>White Hat</td>
                            <td>✅ Podporované pre social signals</td>
                        </tr>
                        <tr>
                            <td>XML Sitemap</td>
                            <td>White Hat</td>
                            <td>✅ Oficiálne odporúčané</td>
                        </tr>
                        <tr>
                            <td>Canonical URLs</td>
                            <td>White Hat</td>
                            <td>✅ Oficiálne odporúčané</td>
                        </tr>
                        <tr>
                            <td>Robots Meta Tag</td>
                            <td>White Hat</td>
                            <td>✅ Oficiálne podporované</td>
                        </tr>
                    </tbody>
                </table>

                <h3>5. Dôkazy z Google dokumentácie</h3>

                <h4>Google Webmaster Guidelines:</h4>
                <p>
                    <a href="https://developers.google.com/search/docs/essentials" target="_blank">Google Search Essentials</a>
                </p>
                <ul>
                    <li>✅ "Pomôžte Google nájsť váš obsah" - XML Sitemap</li>
                    <li>✅ "Pomôžte Google pochopiť váš obsah" - Štruktúrované dáta</li>
                    <li>✅ "Pomôžte Google zobraziť váš obsah" - Meta tagy</li>
                </ul>

                <h4>Google Quality Guidelines - Čo sa NESMIE:</h4>
                <ul>
                    <li>❌ Automaticky generovaný obsah</li>
                    <li>❌ Link schemes</li>
                    <li>❌ Cloaking</li>
                    <li>❌ Hidden text and links</li>
                    <li>❌ Keyword stuffing</li>
                </ul>
                <p><strong>Tento plugin nerobí NIČ z vyššie uvedeného!</strong></p>

                <h3>6. Overenie Google Compliance</h3>

                <h4>Použite oficiálne Google nástroje:</h4>
                <ol>
                    <li>
                        <strong>Rich Results Test:</strong>
                        <a href="https://search.google.com/test/rich-results" target="_blank">https://search.google.com/test/rich-results</a>
                        <p>Overí, či sú štruktúrované dáta správne implementované.</p>
                    </li>
                    <li>
                        <strong>Mobile-Friendly Test:</strong>
                        <a href="https://search.google.com/test/mobile-friendly" target="_blank">https://search.google.com/test/mobile-friendly</a>
                        <p>Overí mobile optimalizáciu (viewport tag).</p>
                    </li>
                    <li>
                        <strong>PageSpeed Insights:</strong>
                        <a href="https://pagespeed.web.dev/" target="_blank">https://pagespeed.web.dev/</a>
                        <p>Plugin nepridáva žiadny JavaScript, ktorý by spomaľoval stránku.</p>
                    </li>
                    <li>
                        <strong>Google Search Console:</strong>
                        <a href="https://search.google.com/search-console" target="_blank">https://search.google.com/search-console</a>
                        <p>Sledujte indexáciu a prípadné problémy.</p>
                    </li>
                </ol>

                <h3>7. Právne a etické aspekty</h3>

                <h4>Licencia a transparentnosť:</h4>
                <ul>
                    <li>✅ Plugin je open source (GPL v2)</li>
                    <li>✅ Kód je transparentný a auditovateľný</li>
                    <li>✅ Žiadne skryté funkcie alebo telemetria</li>
                    <li>✅ Plná kontrola nad vašimi dátami</li>
                </ul>

                <h4>GDPR Compliance:</h4>
                <ul>
                    <li>✅ Plugin nezberá žiadne osobné údaje</li>
                    <li>✅ Žiadne tracking cookies</li>
                    <li>✅ Žiadne pripojenia na externé servery</li>
                </ul>

                <h3>8. Výsledky a očakávania</h3>

                <h4>Čo môžete očakávať:</h4>
                <ul>
                    <li>✅ Lepšie zobrazenie vo výsledkoch vyhľadávania (rich snippets)</li>
                    <li>✅ Vyššie CTR (Click-Through Rate) vďaka lepším popisom</li>
                    <li>✅ Lepší vzhľad pri zdieľaní na sociálnych sieťach</li>
                    <li>✅ Rýchlejšie indexovanie vďaka sitemap</li>
                    <li>✅ Žiadne problémy s duplicitným obsahom (canonical URLs)</li>
                </ul>

                <h4>Čo plugin NEMÔŽE urobiť:</h4>
                <ul>
                    <li>❌ Automaticky vás posunúť na prvú pozíciu v Googlu</li>
                    <li>❌ Nahradiť kvalitný obsah</li>
                    <li>❌ Vyriešiť technické problémy hostingu</li>
                    <li>❌ Zrýchliť pomalý web (ale nepridáva žiadne spomalenie)</li>
                </ul>

                <h3>9. Porovnanie s konkurenciou</h3>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Funkcia</th>
                            <th>AceChange SEO</th>
                            <th>Yoast SEO</th>
                            <th>Rank Math</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Meta Tagy</td>
                            <td>✅ Áno</td>
                            <td>✅ Áno</td>
                            <td>✅ Áno</td>
                        </tr>
                        <tr>
                            <td>Open Graph</td>
                            <td>✅ Áno</td>
                            <td>✅ Áno</td>
                            <td>✅ Áno</td>
                        </tr>
                        <tr>
                            <td>Schema.org</td>
                            <td>✅ Áno</td>
                            <td>✅ Áno (premium)</td>
                            <td>✅ Áno</td>
                        </tr>
                        <tr>
                            <td>XML Sitemap</td>
                            <td>✅ Áno</td>
                            <td>✅ Áno</td>
                            <td>✅ Áno</td>
                        </tr>
                        <tr>
                            <td>Google Safe</td>
                            <td>✅ 100%</td>
                            <td>✅ 100%</td>
                            <td>✅ 100%</td>
                        </tr>
                        <tr>
                            <td>Open Source</td>
                            <td>✅ Áno</td>
                            <td>⚠️ Čiastočne</td>
                            <td>⚠️ Čiastočne</td>
                        </tr>
                    </tbody>
                </table>

                <h3>10. Odporúčania a best practices</h3>

                <h4>Pre maximálnu bezpečnosť:</h4>
                <ol>
                    <li>Používajte plugin na legitímnych weboch s kvalitným obsahom</li>
                    <li>Nekombinujte s Black Hat technikami z iných zdrojov</li>
                    <li>Pravidelne aktualizujte WordPress a plugin</li>
                    <li>Monitorujte Google Search Console pre varovania</li>
                    <li>Testujte stránku pomocou Google nástrojov</li>
                </ol>

                <div class="notice notice-info">
                    <h4>📞 Kontakt a podpora</h4>
                    <p>Ak máte otázky ohľadom Google Compliance alebo bezpečnosti pluginu:</p>
                    <ul>
                        <li>GitHub Issues: <a href="https://github.com/cryptotrust1/acechange-playground/issues" target="_blank">Nahlásiť problém</a></li>
                        <li>Dokumentácia: Navštívte záložku "Dokumentácia"</li>
                    </ul>
                </div>

                <div class="notice notice-success">
                    <h4>✅ Záver</h4>
                    <p>
                        <strong>AceChange SEO Plugin je 100% bezpečný pre Google.</strong> Všetky techniky sú oficiálne podporované
                        a odporúčané Google. Plugin nepoužíva žiadne Black Hat techniky a nemôže spôsobiť penalizáciu alebo
                        blacklisting vašej stránky.
                    </p>
                    <p>
                        Pre maximálny účinok kombinujte tento plugin s kvalitným obsahom, rýchlym hostingom a dobrými backlinkami.
                    </p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Pridanie meta boxov
     */
    public function add_meta_boxes() {
        add_meta_box(
            'acechange_seo_meta',
            'AceChange SEO',
            array($this, 'render_meta_box'),
            array('post', 'page'),
            'normal',
            'high'
        );
    }

    /**
     * Vykreslenie meta boxu
     */
    public function render_meta_box($post) {
        wp_nonce_field('acechange_seo_meta_box', 'acechange_seo_meta_box_nonce');

        $meta_description = get_post_meta($post->ID, '_acechange_meta_description', true);
        $robots = get_post_meta($post->ID, '_acechange_robots', true);

        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="acechange_meta_description">Meta Description</label>
                    </th>
                    <td>
                        <textarea name="acechange_meta_description" id="acechange_meta_description" rows="3" class="large-text"><?php echo esc_textarea($meta_description); ?></textarea>
                        <p class="description">
                            Vlastný popis pre vyhľadávače (150-160 znakov). Ak necháte prázdne, použije sa automatický.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="acechange_robots">Robots Tag</label>
                    </th>
                    <td>
                        <select name="acechange_robots" id="acechange_robots">
                            <option value="">Automatické</option>
                            <option value="index, follow" <?php selected($robots, 'index, follow'); ?>>Index, Follow</option>
                            <option value="noindex, follow" <?php selected($robots, 'noindex, follow'); ?>>NoIndex, Follow</option>
                            <option value="index, nofollow" <?php selected($robots, 'index, nofollow'); ?>>Index, NoFollow</option>
                            <option value="noindex, nofollow" <?php selected($robots, 'noindex, nofollow'); ?>>NoIndex, NoFollow</option>
                        </select>
                        <p class="description">
                            Kontrola indexovania pre túto stránku.
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Uloženie meta box dát
     */
    public function save_meta_box_data($post_id) {
        // Overenie nonce
        if (!isset($_POST['acechange_seo_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['acechange_seo_meta_box_nonce'], 'acechange_seo_meta_box')) {
            return;
        }

        // Overenie autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Overenie oprávnení
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Uloženie meta description
        if (isset($_POST['acechange_meta_description'])) {
            update_post_meta(
                $post_id,
                '_acechange_meta_description',
                sanitize_textarea_field($_POST['acechange_meta_description'])
            );
        }

        // Uloženie robots tag
        if (isset($_POST['acechange_robots'])) {
            update_post_meta(
                $post_id,
                '_acechange_robots',
                sanitize_text_field($_POST['acechange_robots'])
            );
        }
    }
}
