<?php
/**
 * AI SEO Manager - Debug Configuration Example
 *
 * Pridajte tieto konštanty do vášho wp-config.php súboru
 * pre aktiváciu debug systému pluginu.
 *
 * DÔLEŽITÉ: Toto je len príklad! Nekopírujte celý súbor.
 * Kopírujte len potrebné riadky do vášho existujúceho wp-config.php
 */

// =============================================================================
// AI SEO MANAGER DEBUG KONFIGURÁCIA
// =============================================================================

/**
 * 1. Základná Debug Konfigurácia
 *
 * Povoľte AI_SEO_DEBUG pre aktiváciu debug systému pluginu
 */
define('AI_SEO_DEBUG', true);

/**
 * 2. Debug Level
 *
 * Možnosti:
 * - 'ERROR'   - Len kritické chyby (produkcia)
 * - 'WARNING' - Chyby + varovania (staging)
 * - 'INFO'    - Chyby + varovania + info (development)
 * - 'DEBUG'   - Všetko vrátane detailov (debugging)
 *
 * Predvolené: 'INFO'
 */
define('AI_SEO_DEBUG_LEVEL', 'DEBUG');

/**
 * 3. WordPress Debug Konfigurácia
 *
 * Pre úplnú funkcionalitu odporúčame zapnúť aj WordPress debug
 */
define('WP_DEBUG', true);           // Zapne debug mód
define('WP_DEBUG_LOG', true);       // Loguje do wp-content/debug.log
define('WP_DEBUG_DISPLAY', false);  // Nezobrazuje chyby na stránke (bezpečnosť)
@ini_set('display_errors', 0);      // Istota že chyby nie sú viditeľné

/**
 * 4. Script Debug (voliteľné)
 *
 * Používa neminifikované verzie JS/CSS súborov
 */
define('SCRIPT_DEBUG', true);

/**
 * 5. SaveQueries (voliteľné - len pre debugging)
 *
 * Uloží všetky DB queries pre analýzu
 * POZOR: Môže výrazne zvýšiť využitie pamäte!
 */
define('SAVEQUERIES', true);

// =============================================================================
// PRÍKLADY PRE RÔZNE PROSTREDIA
// =============================================================================

/**
 * PRODUKČNÉ PROSTREDIE
 *
 * Minimálne logovanie, len kritické chyby
 */
/*
define('AI_SEO_DEBUG', true);
define('AI_SEO_DEBUG_LEVEL', 'ERROR');
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
*/

/**
 * STAGING PROSTREDIE
 *
 * Stredné logovanie, chyby a varovania
 */
/*
define('AI_SEO_DEBUG', true);
define('AI_SEO_DEBUG_LEVEL', 'WARNING');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
*/

/**
 * DEVELOPMENT PROSTREDIE
 *
 * Plné logovanie pre vývoj
 */
/*
define('AI_SEO_DEBUG', true);
define('AI_SEO_DEBUG_LEVEL', 'INFO');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);
define('SCRIPT_DEBUG', true);
*/

/**
 * DEBUGGING REŽIM
 *
 * Maximálne logovanie pre riešenie problémov
 */
/*
define('AI_SEO_DEBUG', true);
define('AI_SEO_DEBUG_LEVEL', 'DEBUG');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);
define('SCRIPT_DEBUG', true);
define('SAVEQUERIES', true);
*/

// =============================================================================
// ČO SA DEJE KEĎ ZAPNETE DEBUG
// =============================================================================

/**
 * Po aktivácii AI_SEO_DEBUG sa:
 *
 * 1. ✅ Aktivuje Debug Logger
 *    - Logy sa ukladajú do: wp-content/uploads/ai-seo-manager/logs/
 *    - Formát: debug-YYYY-MM-DD.log
 *    - Automatická rotácia pri 10MB
 *
 * 2. ✅ Aktivuje Performance Monitor
 *    - Tracking času vykonávania operácií
 *    - Meranie pamäte
 *    - Počítanie DB queries
 *    - API call tracking
 *
 * 3. ✅ Zobrazí Admin Debug Panel
 *    - Menu: AI SEO Manager > Debug Logs
 *    - Filtrovanie logov
 *    - Export do CSV
 *    - Performance metriky
 *    - Memory usage info
 *
 * 4. ✅ Začne logovať do:
 *    - Plugin log súborov
 *    - WordPress debug.log (ak je WP_DEBUG_LOG = true)
 *    - Databázy (pre štatistiky)
 */

// =============================================================================
// BEZPEČNOSTNÉ POZNÁMKY
// =============================================================================

/**
 * ⚠️ DÔLEŽITÉ BEZPEČNOSTNÉ PRAVIDLÁ:
 *
 * 1. NIKDY nezapínajte WP_DEBUG_DISPLAY na produkcii
 *    - Môže odhaliť citlivé informácie
 *    - Môže zobraziť cesty k súborom
 *
 * 2. Pravidelne čistite logy
 *    - Logy môžu rýchlo rásť
 *    - Využívajte Clean Old Logs funkciu
 *
 * 3. Chráňte log súbory
 *    - Plugin automaticky pridáva .htaccess ochranu
 *    - Skontrolujte že logy nie sú verejne prístupné
 *
 * 4. Na produkcii používajte ERROR level
 *    - Minimálne logovanie
 *    - Len kritické problémy
 */

// =============================================================================
// ÚDRŽBA A ČISTENIE
// =============================================================================

/**
 * Automatické čistenie starých logov
 *
 * Pridajte do functions.php alebo custom pluginu:
 */
/*
add_action('wp_scheduled_delete', function() {
    if (class_exists('AI_SEO_Manager_Debug_Logger')) {
        // Vymaž logy staršie ako 30 dní
        AI_SEO_Manager_Debug_Logger::get_instance()->clean_old_logs(30);
    }
});
*/

// =============================================================================
// UKÁŽKA POUŽITIA V KÓDE
// =============================================================================

/**
 * Vlastné logovanie v themes/plugins:
 */
/*
if (class_exists('AI_SEO_Manager_Debug_Logger')) {
    $logger = AI_SEO_Manager_Debug_Logger::get_instance();

    // Rôzne úrovne logov
    $logger->error('Kritická chyba', array('detail' => 'hodnota'));
    $logger->warning('Varovanie');
    $logger->info('Informácia');
    $logger->debug('Debug detail', array('data' => $data));
}
*/

/**
 * Performance tracking:
 */
/*
if (class_exists('AI_SEO_Manager_Performance_Monitor')) {
    $perf = AI_SEO_Manager_Performance_Monitor::get_instance();

    // Meranie operácie
    $perf->start('my_operation');
    // ... váš kód ...
    $metric = $perf->stop('my_operation');

    // Profilovanie funkcie
    $result = $perf->profile(function() {
        // ... váš kód ...
    }, 'operation_name');
}
*/

// =============================================================================
// VIAC INFORMÁCIÍ
// =============================================================================

/**
 * Pre detailnú dokumentáciu pozri:
 * - DEBUG.md v root adresári pluginu
 * - https://github.com/cryptotrust1/acechange-playground
 *
 * Pre podporu:
 * - GitHub Issues: https://github.com/cryptotrust1/acechange-playground/issues
 */
