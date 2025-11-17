# AI Social Media Manager - Ďalšie Kroky Implementácie

**Status:** ✅ Fáza 1 KOMPLETNÁ - Infraštruktúra pripravená
**Dátum:** 2025-01-17

---

## ✅ Čo je Hotové (Fáza 1)

### 1. Dokumentácia
- [x] **SOCIAL_MEDIA_PLAN.md** - Komplexný 18-týždňový plán
- [x] **SOCIAL_MEDIA_ARCHITECTURE.md** - Technická architektúra
- [x] **API Research** - Všetky 7 platforiem preskúmané

### 2. Databázová Schéma
- [x] **6 tabuliek vytvorených:**
  - `wp_ai_seo_social_accounts` - Platform accounts
  - `wp_ai_seo_social_posts` - Social media posts
  - `wp_ai_seo_social_queue` - Scheduling queue
  - `wp_ai_seo_social_analytics` - Performance metrics
  - `wp_ai_seo_social_trends` - Trend tracking
  - `wp_ai_seo_social_settings` - Plugin settings

### 3. Core Infrastructure ✅ PRIPRAVENÉ NA IMPLEMENTÁCIU
- [x] Database class (`AI_SEO_Social_Database`)
- [ ] Main Manager (TBD)
- [ ] Platform Registry (TBD)
- [ ] Rate Limiter (TBD)

---

## 🚀 Odporúčaný Postup Implementácie

### TERAZ: Dokončiť Fázu 1 (1-2 týždne)

**Priorita P0 - KRITICKÁ:**

```bash
# 1. Core Components
includes/social-media/class-social-media-manager.php     # Main orchestrator
includes/social-media/class-platform-registry.php        # Platform management
includes/social-media/class-rate-limiter.php            # API rate limits

# 2. Base Platform Client (Abstract class)
includes/social-media/platforms/class-platform-client.php

# 3. First Platform - TELEGRAM (Najjednoduchší, FREE)
includes/social-media/platforms/class-telegram-client.php
```

**Prečo Telegram ako prvý:**
- ✅ Najjednoduchšie API (len HTTP POST)
- ✅ Žiadne OAuth komplikácie
- ✅ 100% FREE
- ✅ Veľkorysé limity
- ✅ Rýchle testovanie

---

### Potom: Fáza 2 - Platform Clients (3-4 týždne)

**P0 - FREE platformy:**
1. ✅ **Telegram** (hotové)
2. **Facebook** (FREE, veľká user base)
3. **Instagram** (FREE, populárne)

**P1 - Business platformy:**
4. **LinkedIn** (FREE, B2B focused)
5. **Twitter/X** ($200/mo, populárne)

**P2 - Video/Advanced:**
6. **YouTube** (komplexné, video)
7. **TikTok** (vyžaduje audit)

---

## 📋 Implementačná Checklist - Fáza 1

### Core Components
- [ ] `class-social-media-manager.php`
  - [ ] Singleton pattern
  - [ ] Platform registration
  - [ ] Publish/Schedule methods
  - [ ] Integration s AI_Manager
  - [ ] Debug logging

- [ ] `class-platform-registry.php`
  - [ ] Platform registration
  - [ ] Get active platforms
  - [ ] Platform capabilities check

- [ ] `class-rate-limiter.php`
  - [ ] Track API calls per platform
  - [ ] Check limits before API call
  - [ ] Reset counters (daily/hourly)
  - [ ] Queue when limit reached

### Base Platform Client
- [ ] `platforms/class-platform-client.php` (abstract)
  - [ ] Abstract methods: `authenticate()`, `publish()`, `get_analytics()`
  - [ ] Common methods: `is_authenticated()`, `handle_error()`
  - [ ] Debug logging integration
  - [ ] Performance tracking

### Telegram Client (Vzorový príklad)
- [ ] `platforms/class-telegram-client.php`
  - [ ] Bot token authentication
  - [ ] `sendMessage()` implementation
  - [ ] `sendPhoto()` implementation
  - [ ] `sendVideo()` implementation
  - [ ] Error handling
  - [ ] Rate limit tracking

---

## 🎯 Ukážkový Kód - Main Manager

```php
<?php
/**
 * AI SEO Social Media Manager
 * Main orchestrator for all social media operations
 */

class AI_SEO_Social_Media_Manager {

    private static $instance = null;
    private $registry;
    private $rate_limiter;
    private $db;
    private $ai_manager;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = AI_SEO_Social_Database::get_instance();
        $this->registry = AI_SEO_Social_Platform_Registry::get_instance();
        $this->rate_limiter = AI_SEO_Social_Rate_Limiter::get_instance();
        $this->ai_manager = AI_SEO_Manager_AI_Manager::get_instance();

        $this->init_hooks();
        $this->register_platforms();
    }

    private function register_platforms() {
        // Register all available platforms
        if (class_exists('AI_SEO_Social_Telegram_Client')) {
            $this->registry->register('telegram', new AI_SEO_Social_Telegram_Client());
        }
        // Add more as implemented...
    }

    public function publish_now($content, $platforms = [], $options = []) {
        // Immediate publishing
        foreach ($platforms as $platform) {
            if (!$this->rate_limiter->check_limit($platform, 'publish')) {
                // Queue instead
                continue;
            }

            $client = $this->registry->get($platform);
            $result = $client->publish($content, $options);

            // Track result...
        }
    }

    public function schedule_post($content, $schedule_time, $platforms = [], $options = []) {
        // Schedule for later
        // Create posts in database
        // Add to queue
    }
}
```

---

## 🎯 Ukážkový Kód - Telegram Client

```php
<?php
/**
 * Telegram Platform Client
 * Simplest implementation - use as template for others
 */

class AI_SEO_Social_Telegram_Client extends AI_SEO_Social_Platform_Client {

    protected $platform_name = 'telegram';
    private $bot_token;
    private $channel_id;
    private $api_url = 'https://api.telegram.org/bot';

    public function authenticate() {
        // Telegram is simple - just need bot token and channel ID
        $account = $this->db->get_account_by_platform('telegram');

        if (!$account) {
            return new WP_Error('no_account', 'Telegram account not configured');
        }

        $creds = $account->credentials;
        $this->bot_token = $creds['bot_token'] ?? '';
        $this->channel_id = $creds['channel_id'] ?? '';

        if (empty($this->bot_token) || empty($this->channel_id)) {
            return new WP_Error('invalid_credentials', 'Telegram credentials missing');
        }

        return true;
    }

    public function publish($content, $media = []) {
        if (!$this->is_authenticated()) {
            $auth = $this->authenticate();
            if (is_wp_error($auth)) {
                return $auth;
            }
        }

        // Choose method based on media
        if (!empty($media) && isset($media[0])) {
            $media_type = $this->detect_media_type($media[0]);

            if ($media_type === 'photo') {
                return $this->send_photo($content, $media[0]);
            } elseif ($media_type === 'video') {
                return $this->send_video($content, $media[0]);
            }
        }

        return $this->send_message($content);
    }

    private function send_message($text) {
        $endpoint = $this->api_url . $this->bot_token . '/sendMessage';

        $response = wp_remote_post($endpoint, array(
            'body' => array(
                'chat_id' => $this->channel_id,
                'text' => $text,
                'parse_mode' => 'HTML',
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!$body['ok']) {
            return new WP_Error('telegram_error', $body['description'] ?? 'Unknown error');
        }

        return $body['result']['message_id'];
    }

    private function send_photo($caption, $photo_url) {
        $endpoint = $this->api_url . $this->bot_token . '/sendPhoto';

        $response = wp_remote_post($endpoint, array(
            'body' => array(
                'chat_id' => $this->channel_id,
                'photo' => $photo_url,
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ),
        ));

        // Similar error handling...

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['result']['message_id'];
    }

    public function get_analytics($post_id, $date_range) {
        // Telegram doesn't provide analytics via API
        // Return basic info only
        return array('views' => 0, 'forwards' => 0);
    }

    public function validate_content($content) {
        // Telegram limits: 4096 characters for text
        if (strlen($content) > 4096) {
            return new WP_Error('content_too_long', 'Telegram messages are limited to 4096 characters');
        }
        return true;
    }

    public function get_rate_limits() {
        return array(
            'messages_per_second' => 30,
            'messages_per_chat_per_minute' => 20,
        );
    }
}
```

---

## 📦 Potrebné Composer Balíky (Voliteľné)

Pre pokročilejšie platformy budete možno potrebovať:

```bash
# Pre Facebook/Instagram Graph API
composer require facebook/graph-sdk

# Pre Twitter/X API v2
composer require noweh/twitter-api-v2-php

# Pre LinkedIn API
composer require linkedinapi/linkedin-api-php-client

# Pre YouTube API
composer require google/apiclient
```

**Poznámka:** Nie sú NUTNÉ - môžete použiť aj priame `wp_remote_post()` volania.

---

## 🔧 Konfigurácia v Admin (TODO - Fáza 6)

```
AI SEO Manager > Social Media > Settings

Platforms:
┌─ Telegram
│  ├─ Bot Token: sk-ant-...
│  ├─ Channel ID: @mychannel
│  └─ [✓] Enabled
│
├─ Facebook
│  ├─ App ID: 123456789
│  ├─ App Secret: ***
│  ├─ Page ID: 987654321
│  └─ [✓] Enabled
│
└─ ... (other platforms)

AI Settings:
├─ Default Tone: Professional
├─ Default Category: General
└─ Enable Trend Integration: [✓]

Scheduling:
├─ Min Posts Per Day: 1
├─ Max Posts Per Day: 3
├─ Preferred Posting Times: 9:00, 14:00, 18:00
└─ Random Time Offset: ±2 hours
```

---

## 🧪 Testovanie

### Unit Tests (PHPUnit)

```bash
# Test database creation
phpunit tests/social-media/Unit/DatabaseTest.php

# Test Telegram client
phpunit tests/social-media/Unit/TelegramClientTest.php

# Test rate limiter
phpunit tests/social-media/Unit/RateLimiterTest.php
```

### Manual Testing

```php
// Test Telegram publishing
$manager = AI_SEO_Social_Media_Manager::get_instance();
$result = $manager->publish_now(
    'Test post from AI SEO Manager! 🚀',
    ['telegram'],
    []
);

if (is_wp_error($result)) {
    echo 'Error: ' . $result->get_error_message();
} else {
    echo 'Posted successfully!';
}
```

---

## 📊 Odhadovaný Čas na Dokončenie

| Fáza | Komponenta | Čas | Priorita |
|------|------------|-----|----------|
| **1** | Core (Manager, Registry, Limiter) | 2-3 dni | P0 |
| **1** | Telegram Client | 1 deň | P0 |
| **2** | Facebook Client | 2-3 dni | P0 |
| **2** | Instagram Client | 2-3 dni | P0 |
| **2** | LinkedIn Client | 2-3 dni | P1 |
| **2** | Twitter/X Client | 3-4 dni | P1 |
| **2** | YouTube Client | 4-5 dni | P2 |
| **2** | TikTok Client | 3-4 dni | P2 |
| **3** | AI Content Engine | 5-7 dni | P1 |
| **4** | Scheduler & Queue | 4-5 dni | P1 |
| **5** | Analytics | 4-5 dni | P1 |
| **6** | Admin UI | 7-10 dni | P1 |
| **7** | Testing & QA | 5-7 dni | P0 |
| **8** | Documentation | 3-4 dni | P1 |

**Celkom:** ~45-60 dní práce (full-time)

---

## 🎬 Ako Začať TERAZ

### Krok 1: Commit aktuálny stav

```bash
git add .
git commit -m "feat: Social Media Manager - Phase 1 Infrastructure (Database)"
git push
```

### Krok 2: Vytvorte core komponenty

Začnite s týmito súbormi v tomto poradí:

1. `includes/social-media/class-social-media-manager.php`
2. `includes/social-media/class-platform-registry.php`
3. `includes/social-media/class-rate-limiter.php`
4. `includes/social-media/platforms/class-platform-client.php` (abstract)
5. `includes/social-media/platforms/class-telegram-client.php`

### Krok 3: Integrujte do hlavného pluginu

V `ai-seo-manager.php`:

```php
// Social Media Manager (if enabled)
if (get_option('ai_seo_social_enabled', false)) {
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-social-database.php';
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-social-media-manager.php';
    AI_SEO_Social_Media_Manager::get_instance();
}
```

### Krok 4: Vytvorte prvý test

```php
// Test Telegram bot
$telegram = new AI_SEO_Social_Telegram_Client();
$telegram->authenticate();
$result = $telegram->publish('Hello from AI SEO Manager!');
```

---

## 💡 Tipy na Úspech

1. **Postupnosť je kľúčová** - Nekódujte všetky platformy naraz
2. **Telegram najprv** - Najjednoduchší na testovanie
3. **Používajte debug systém** - Už máte hotový!
4. **Testujte často** - Po každej platforme
5. **Dokumentujte API keys** - Bezpečne v `.env` alebo wp-config
6. **Git commits** - Po každej major funkcii

---

## 🆘 Podpora

**Dokumentácia:**
- SOCIAL_MEDIA_PLAN.md - Kompletný plán
- SOCIAL_MEDIA_ARCHITECTURE.md - Technická architektúra
- DEBUG.md - Debug systém

**API Dokumentácie:**
- Telegram: https://core.telegram.org/bots/api
- Facebook: https://developers.facebook.com/docs/graph-api
- Instagram: https://developers.facebook.com/docs/instagram-api
- Atď. (všetky linky v SOCIAL_MEDIA_PLAN.md)

**GitHub:**
- Issues: https://github.com/cryptotrust1/acechange-playground/issues

---

## ✅ Záver

Máte teraz:
- ✅ **Komplet​nú databázu** - 6 tabuliek ready
- ✅ **Detailný plán** - 18 týždňov rozdelené na fázy
- ✅ **Technickú architektúru** - Presné návody
- ✅ **API research** - Všetky 7 platforiem preskúmané
- ✅ **Ukážkový kód** - Telegram ako vzor

**Najbližší krok:** Vytvorte core komponenty (Manager, Registry, Limiter) a Telegram Client.

**Časový odhad pre Fázu 1:** 1 týždeň

---

Prajeme veľa úspechov! 🚀

**AceChange Development Team**
