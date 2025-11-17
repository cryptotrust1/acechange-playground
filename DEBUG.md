# AI SEO Manager - Debug Systém

Komplexný debug a monitoring systém pre AI SEO Manager plugin s plnou podporou WordPress best practices.

## 🎯 Funkcie Debug Systému

### 1. **Multi-level Logging**
- **ERROR** - Kritické chyby, ktoré znemožňujú funkčnosť
- **WARNING** - Problémy, ktoré by mohli spôsobiť problémy
- **INFO** - Dôležité informačné správy
- **DEBUG** - Detailné debug informácie pre vývojárov

### 2. **Performance Monitoring**
- Tracking času vykonávania operácií
- Meranie pamäte použitej jednotlivými komponentmi
- Počítanie databázových queries
- API call tracking (úspešnosť, trvanie, chyby)
- Automatická detekcia pomalých operácií

### 3. **Admin Debug Panel**
- Prehľadné zobrazenie všetkých logov v admin rozhraní
- Filtrovanie podľa úrovne (ERROR, WARNING, INFO, DEBUG)
- Vyhľadávanie v logoch
- Štatistiky a grafy
- Export logov do CSV
- Real-time performance metriky

### 4. **Integrácia s WordPress**
- Plná podpora `WP_DEBUG`, `WP_DEBUG_LOG`, `WP_DEBUG_DISPLAY`
- Vlastné debug konštanty pre plugin
- Automatické logovanie do WordPress debug.log
- Bezpečné uloženie logov mimo web rootu

## 🔧 Konfigurácia

### Základné Nastavenie

Pridajte tieto konštanty do vášho `wp-config.php`:

```php
// Povoliť WordPress debug mód
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Povoliť AI SEO Manager debug systém
define('AI_SEO_DEBUG', true);

// Nastaviť debug level (voliteľné)
// Možnosti: ERROR, WARNING, INFO, DEBUG
define('AI_SEO_DEBUG_LEVEL', 'DEBUG');
```

### Debug Úrovne

**ERROR** (úroveň 1)
- Len kritické chyby
- API zlyhania
- Databázové chyby
- Odporúčané pre produkciu

**WARNING** (úroveň 2)
- Chyby + varovania
- Pomalé operácie
- Fallback scenáre
- Odporúčané pre staging

**INFO** (úroveň 3) - Predvolené
- Chyby + varovania + info
- Úspešné operácie
- Hlavné akcie systému
- Odporúčané pre vývoj

**DEBUG** (úroveň 4)
- Všetko vrátane detailov
- Úplné backtrace
- Všetky API volania
- Odporúčané pre debugging

## 📊 Používanie

### V Kóde

```php
// Získanie logger instance
$logger = AI_SEO_Manager_Debug_Logger::get_instance();

// Logovanie rôznych úrovní
$logger->error('Kritická chyba', array('detail' => 'hodnota'));
$logger->warning('Varovanie o probléme');
$logger->info('Informácia o akcii');
$logger->debug('Debug detaily', array('data' => $data));

// Performance monitoring
$performance = AI_SEO_Manager_Performance_Monitor::get_instance();

// Meranie času operácie
$performance->start('my_operation');
// ... váš kód ...
$metric = $performance->stop('my_operation');

// Profilovanie funkcie
$result = $performance->profile(function() {
    // ... váš kód ...
}, 'operation_name');

// Tracking API volaní
$performance->track_api_call(
    'claude',              // provider
    'analyze_content',     // endpoint
    2.5,                   // duration v sekundách
    true,                  // success
    null                   // error message (ak zlyhal)
);
```

### Admin Panel

1. Prejdite na **AI SEO Manager > Debug Logs**
2. Zobrazí sa debug panel s:
   - Debug status (aktívne/neaktívne)
   - Štatistiky logov (celkovo, errors, warnings, info)
   - API performance metriky
   - Memory usage info
   - Zoznam všetkých logov s filtrami

### Akcie v Admin Paneli

- **Filter** - Filtrovanie podľa úrovne a vyhľadávanie
- **Export CSV** - Export všetkých logov do CSV súboru
- **Clean Old Logs** - Vymazanie logov starších ako 30 dní
- **Clear All Logs** - Vymazanie všetkých debug logov
- **Reset Performance Stats** - Reset API performance štatistík

## 📁 Log Súbory

### Umiestnenie

Logy sa ukladajú do:
```
wp-content/uploads/ai-seo-manager/logs/debug-YYYY-MM-DD.log
```

### Ochrana

- Directory je chránený `.htaccess` (Deny from all)
- Index.php súbor pre ochranu
- Automatická rotácia pri dosiahnutí 10MB
- Starý log sa premenuje na `.log.old`

### Čistenie Logov

```php
// Programovo vymazať staré logy
$logger = AI_SEO_Manager_Debug_Logger::get_instance();

// Vymazať logy staršie ako 30 dní
$deleted = $logger->clean_old_logs(30);

// Vymazať všetky debug logy
$deleted = $logger->clear_all_logs();
```

## 🔍 Trackované Komponenty

### AI Manager
- Začiatok/koniec SEO analýz
- AI provider fallback
- API volania (úspech/zlyhanie)
- Performance metriky

### Claude Client
- API volania
- Token usage
- Response times
- Error handling

### Autopilot Engine
- Vykonávanie odporúčaní
- Optimalizačné operácie
- Success/failure rate

### Database Operations
- Query performance
- Pomalé databázové operácie
- Rows affected

## 📈 Performance Metriky

### API Performance

Plugin automaticky trackuje:
- Celkový počet API volaní
- Počet zlyhaných volaní
- Priemerné trvanie
- Success rate v %

Zobrazené v Admin Debug Paneli pre každý provider (Claude, OpenAI).

### Operation Metrics

Pre každú operáciu sa zaznamenáva:
- **Duration** - Čas trvania v sekundách
- **Memory Used** - Pamäť použitá operáciou
- **Queries Count** - Počet DB queries

### Slow Operation Detection

Automatické detekovanie:
- Operácie > 5 sekúnd: WARNING log
- API volania > 10 sekúnd: WARNING log
- DB operácie > 1 sekunda: WARNING log

## 🔐 Bezpečnosť

### Ochrana Logov

1. **File System**
   - Logy mimo public_html/htdocs
   - `.htaccess` ochrana
   - Index.php protection

2. **Database**
   - Logy v zabezpečenej WordPress databáze
   - Sanitizácia všetkých vstupov
   - Prepared statements

3. **Admin Panel**
   - `manage_options` capability required
   - Nonce verification pre všetky akcie
   - CSRF protection

### Citlivé Údaje

Logger **NIKDY** neloguje:
- API kľúče
- Heslá
- Tokeny
- Osobné údaje používateľov (len user_id)

## 🎛️ WordPress Hooks

### Custom Actions

```php
// Po každom logu
do_action('ai_seo_manager_log', $level, $message, $context);

// Použitie:
add_action('ai_seo_manager_log', function($level, $message, $context) {
    // Vlastný logger (napr. Slack, email)
}, 10, 3);
```

## 🚀 Best Practices

### Pre Produkčné Prostredie

```php
define('AI_SEO_DEBUG', false); // Vypnuté
// alebo
define('AI_SEO_DEBUG_LEVEL', 'ERROR'); // Len chyby
```

### Pre Staging/Development

```php
define('AI_SEO_DEBUG', true);
define('AI_SEO_DEBUG_LEVEL', 'INFO');
```

### Pre Debugging Problémov

```php
define('AI_SEO_DEBUG', true);
define('AI_SEO_DEBUG_LEVEL', 'DEBUG');
define('WP_DEBUG_LOG', true);
```

### Pravidelná Údržba

1. **Automatické čistenie** - Nastavte cron job:
```php
// V themes/functions.php alebo custom plugin
add_action('wp_scheduled_delete', function() {
    if (class_exists('AI_SEO_Manager_Debug_Logger')) {
        AI_SEO_Manager_Debug_Logger::get_instance()->clean_old_logs(30);
    }
});
```

2. **Monitoring veľkosti** - Pravidelne kontrolujte:
```
wp-content/uploads/ai-seo-manager/logs/
```

## 🐛 Troubleshooting

### Debug logy sa nezobrazujú

1. Skontrolujte `wp-config.php`:
```php
define('WP_DEBUG', true);
// alebo
define('AI_SEO_DEBUG', true);
```

2. Skontrolujte permissions:
```bash
chmod 755 wp-content/uploads/ai-seo-manager/logs/
```

### Debug Panel nie je viditeľný

Debug panel sa zobrazí len ak je aktívny debug mód:
```php
define('WP_DEBUG', true);
// alebo
define('AI_SEO_DEBUG', true);
```

### Logy sú príliš veľké

1. Znížte debug level:
```php
define('AI_SEO_DEBUG_LEVEL', 'WARNING');
```

2. Vyčistite staré logy:
```php
$logger->clean_old_logs(7); // 7 dní
```

## 📚 Príklady Použitia

### Custom Debug Hook

```php
add_action('ai_seo_manager_log', function($level, $message, $context) {
    if ($level === 'ERROR') {
        // Pošli email adminovi
        wp_mail(
            get_option('admin_email'),
            'AI SEO Manager Error',
            $message . "\n\n" . print_r($context, true)
        );
    }
}, 10, 3);
```

### Vlastné Performance Tracking

```php
$performance = AI_SEO_Manager_Performance_Monitor::get_instance();

// Track vlastnú operáciu
$performance->start('my_custom_operation');

// Váš kód...
$result = expensive_operation();

$metric = $performance->stop('my_custom_operation');

// Získať metriku
if ($metric) {
    error_log("Operation took: " . $metric['duration'] . "s");
}
```

### Podmienené Logovanie

```php
$logger = AI_SEO_Manager_Debug_Logger::get_instance();

// Log len ak je debug aktívny
if ($logger->is_debug_mode()) {
    $logger->debug('Detailed debug info', array(
        'large_data' => $big_array,
    ));
}
```

## 🆘 Podpora

Pre problémy s debug systémom:
1. Skontrolujte túto dokumentáciu
2. Overte wp-config.php nastavenia
3. Skontrolujte WordPress debug.log
4. Vytvorte issue na GitHub: https://github.com/cryptotrust1/acechange-playground/issues

---

**Vyvinul:** AceChange
**Verzia:** 1.0.0
**Licencia:** GPL v2 or later
