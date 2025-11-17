# AI Social Media Manager - Návod na Použitie

**Verzia:** 1.0.0 (Phase 2)
**Status:** ✅ Core + Telegram HOTOVÉ

---

## 🚀 Rýchly Štart - Telegram

### Krok 1: Vytvorte Telegram Bot

1. Otvorte Telegram a nájdite **@BotFather**
2. Pošlite príkaz: `/newbot`
3. Zadajte názov bota (napr. "My SEO Bot")
4. Zadajte username bota (musí končiť na "bot", napr. "myseobot")
5. **Uložte Bot Token** - vyzerá takto: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`

### Krok 2: Vytvorte Telegram Channel

1. V Telegrame vytvorte nový Channel
2. Nastavte ho ako Public alebo Private
3. Ak je Public, username je `@vaschannel`
4. Ak je Private, ID získate tak, že:
   - Pridajte bota ako admina do channelu
   - Pošlite správu do channelu
   - Použite https://api.telegram.org/bot{BOT_TOKEN}/getUpdates
   - Nájdite `chat_id` - použite to ako Channel ID

### Krok 3: Pridajte Bot ako Admin

1. Otvorte Channel Settings
2. Administrators → Add Administrator
3. Nájdite vášho bota a pridajte ho
4. Povoľte "Post messages" permission

### Krok 4: Konfigurácia v WordPress

```php
// V WordPress admin alebo priamo v databáze

// Vytvorte account record
global $wpdb;
$table = $wpdb->prefix . 'ai_seo_social_accounts';

$wpdb->insert($table, array(
    'platform' => 'telegram',
    'account_name' => 'My Telegram Channel',
    'account_id' => '@vaschannel', // alebo -100123456789 pre private
    'credentials' => serialize(array(
        'bot_token' => '123456789:ABCdefGHIjklMNOpqrsTUVwxyz',
        'channel_id' => '@vaschannel', // alebo -100123456789
    )),
    'status' => 'active',
));
```

### Krok 5: Test Publishing

```php
// Získaj Social Media Manager
$manager = AI_SEO_Social_Media_Manager::get_instance();

// Publikuj jednoduchú správu
$result = $manager->publish_now(
    '🚀 Hello from AI SEO Manager!

This is a test post from WordPress.

#WordPress #AI #SEO',
    array('telegram'),
    array(
        'created_by' => 'manual_test',
    )
);

// Check result
if (is_wp_error($result['telegram'])) {
    echo 'Error: ' . $result['telegram']->get_error_message();
} else {
    echo 'Success! Message ID: ' . $result['telegram']['platform_post_id'];
}
```

---

## 📝 API Použitie

### Základné Publikovanie

```php
$manager = AI_SEO_Social_Media_Manager::get_instance();

// Text only
$result = $manager->publish_now(
    'Your message here',
    array('telegram')
);

// Text + Image
$result = $manager->publish_now(
    'Check out this image!',
    array('telegram'),
    array(
        'media' => array('https://example.com/image.jpg'),
    )
);

// Text + Video
$result = $manager->publish_now(
    'Watch this video!',
    array('telegram'),
    array(
        'media' => array('https://example.com/video.mp4'),
    )
);
```

### Scheduling

```php
$manager = AI_SEO_Social_Media_Manager::get_instance();

// Schedule for tomorrow at 9 AM
$schedule_time = date('Y-m-d 09:00:00', strtotime('+1 day'));

$result = $manager->schedule_post(
    'Scheduled post content',
    $schedule_time,
    array('telegram'),
    array(
        'tone' => 'professional',
        'category' => 'tech',
    )
);
```

### Telegram-Specific Features

```php
// Get Telegram client directly
$telegram = $manager->get_platform_client('telegram');

// Send poll
$poll_result = $telegram->send_poll(
    'What do you think about AI?',
    array('Amazing!', 'Good', 'Okay', 'Not sure')
);

// Pin message
$telegram->pin_message($message_id);

// Delete message
$telegram->delete_message($message_id);

// Get channel info
$channel_info = $telegram->get_channel_info();
```

---

## 🔧 Aktivácia v Plugine

Pridajte do `ai-seo-manager.php`:

```php
// Social Media Manager
if (get_option('ai_seo_social_enabled', true)) {
    // Load database
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-social-database.php';

    // Load core
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-platform-registry.php';
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-rate-limiter.php';
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-social-media-manager.php';

    // Load platform clients
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-platform-client.php';
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-telegram-client.php';

    // Initialize
    add_action('plugins_loaded', function() {
        AI_SEO_Social_Media_Manager::get_instance();
    });
}
```

### Aktivačný Hook (vytvorenie tabuliek)

```php
register_activation_hook(__FILE__, function() {
    // Create social media tables
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-social-database.php';
    AI_SEO_Social_Database::get_instance()->create_tables();
});
```

---

## 🔄 Automatické Zdieľanie Blogov

Povoľte auto-sharing v options:

```php
update_option('ai_seo_social_auto_share_enabled', true);
update_option('ai_seo_social_auto_share_platforms', array('telegram'));
```

Teraz sa každý nový blog automaticky zdieľa na Telegram!

---

## ⏰ Cron Job pre Scheduled Posts

Pridajte do `wp-config.php` alebo plugin activation:

```php
// Register cron schedule
if (!wp_next_scheduled('ai_seo_social_process_queue')) {
    wp_schedule_event(time(), 'every_5_minutes', 'ai_seo_social_process_queue');
}

// Add custom cron schedule
add_filter('cron_schedules', function($schedules) {
    $schedules['every_5_minutes'] = array(
        'interval' => 300,
        'display' => __('Every 5 Minutes'),
    );
    return $schedules;
});
```

---

## 📊 Rate Limits

Telegram má veľkorysé limity:

```
- 30 messages per second
- ~2500 messages per day per chat
- No strict hourly/daily limits
```

Náš Rate Limiter je nastavený konzervatívne:

```php
'minute' => 30,
'hour' => 1000,
'day' => 10000,
```

Môžete upraviť:

```php
$rate_limiter = AI_SEO_Social_Rate_Limiter::get_instance();
$rate_limiter->set_platform_limits('telegram', array(
    'minute' => 50,
    'hour' => 2000,
    'day' => 20000,
));
```

---

## 🐛 Debugging

Zapnite debug v `wp-config.php`:

```php
define('AI_SEO_DEBUG', true);
define('AI_SEO_DEBUG_LEVEL', 'DEBUG');
```

Všetky Telegram API volania sa budú logovať:
- **AI SEO Manager > Debug Logs**
- `wp-content/uploads/ai-seo-manager/logs/debug-YYYY-MM-DD.log`

---

## ✅ Kontrolný Zoznam

Pred použitím overte:

- [x] Telegram bot vytvorený (@BotFather)
- [x] Bot token uložený
- [x] Channel vytvorený
- [x] Bot je admin v channeli
- [x] Bot má "Post messages" permission
- [x] Account record vytvorený v databáze
- [x] Plugin aktivovaný
- [x] Test message poslaná úspešne

---

## 🔍 Troubleshooting

### "Telegram bot token not configured"

Skontrolujte či v databáze existuje account record:

```sql
SELECT * FROM wp_ai_seo_social_accounts WHERE platform = 'telegram';
```

### "Chat not found"

- Skontrolujte Channel ID
- Pre public channel: `@channelname`
- Pre private channel: číselné ID (napr. `-100123456789`)

### "Bot is not a member of the channel"

- Bot musí byť pridaný ako administrator
- Otvorte Channel Settings → Administrators

### "Insufficient rights to send messages"

- Bot potrebuje "Post messages" permission
- Otvorte Bot v Administrators a povoľte túto permission

### Rate limit warnings v logoch

Normálne - Rate Limiter funguje správne. Posty sa automaticky zařadia do fronty.

---

## 📈 Štatistiky

```php
$manager = AI_SEO_Social_Media_Manager::get_instance();
$stats = $manager->get_stats();

print_r($stats);
```

Output:

```php
array(
    'database' => array(
        'total_accounts' => 1,
        'active_accounts' => 1,
        'total_posts' => 10,
        'published_posts' => 8,
        'scheduled_posts' => 2,
        'failed_posts' => 0,
    ),
    'platforms' => array(
        'total' => 1,
        'active' => 1,
        'platforms' => array('telegram'),
    ),
    'rate_limits' => array(
        'telegram' => array(
            'limits' => array('minute' => 30, 'hour' => 1000, 'day' => 10000),
            'remaining' => array('minute' => 28, 'hour' => 995, 'day' => 9990),
            'usage_percent' => array('minute' => 6.67, 'hour' => 0.5, 'day' => 0.1),
        ),
    ),
)
```

---

## 🎯 Ďalšie Kroky

### Pripravované Platformy:

1. **Facebook** (P0) - Coming soon
2. **Instagram** (P0) - Coming soon
3. **LinkedIn** (P1) - Coming soon
4. **Twitter/X** (P1) - Coming soon
5. **YouTube** (P2) - Coming soon
6. **TikTok** (P2) - Coming soon

### Admin UI (Fáza 6):

- Settings page (API credentials)
- Post composer (visual editor)
- Calendar view (scheduled posts)
- Analytics dashboard
- Trend monitor

---

## 💡 Best Practices

### 1. Testujte na Private Channel

Pred použitím na production channeli, testujte na private test channeli.

### 2. Použite Queue pre Hromadné Posty

```php
// Namiesto 10x publish_now()
foreach ($posts as $post) {
    $manager->schedule_post($post, $schedule_time, ['telegram']);
    $schedule_time = date('Y-m-d H:i:s', strtotime($schedule_time . ' +30 minutes'));
}
```

### 3. Monitorujte Rate Limits

```php
$rate_limiter = AI_SEO_Social_Rate_Limiter::get_instance();
$remaining = $rate_limiter->get_remaining('telegram');

if ($remaining['minute'] < 5) {
    // Čakajte alebo použite queue
}
```

### 4. Používajte Tone a Category

```php
$manager->publish_now($content, ['telegram'], array(
    'tone' => 'professional', // alebo casual, funny, etc.
    'category' => 'crypto', // pre trend tracking
));
```

---

## 📞 Podpora

**Dokumentácia:**
- SOCIAL_MEDIA_PLAN.md
- SOCIAL_MEDIA_ARCHITECTURE.md
- SOCIAL_MEDIA_USAGE.md (tento súbor)

**Telegram Bot API:**
- https://core.telegram.org/bots/api

**GitHub:**
- https://github.com/cryptotrust1/acechange-playground

---

**Vytvorené:** AceChange Development Team
**Aktualizované:** 2025-01-17
**Verzia:** Phase 2 (Core + Telegram)
