# AI Social Media Manager - Návod na Použitie

**Verzia:** 2.0.0 (Phase 3)
**Status:** ✅ ALL PLATFORMS COMPLETE (7/7)

---

## 📋 Podporované Platformy

✅ **Telegram** - Bot API (FREE, najprostejšia)
✅ **Facebook** - Graph API v22.0 (Business Pages)
✅ **Instagram** - Graph API (Business accounts)
✅ **Twitter/X** - API v2 (OAuth 2.0)
✅ **LinkedIn** - Posts API (OAuth 2.0)
✅ **YouTube** - Data API v3 (Google OAuth)
✅ **TikTok** - Content Posting API (requires audit)

---

## 🚀 Quick Start Guide

### 1. Telegram (Naj jednoduchšie)

**Krok 1: Vytvorte Bot**
1. Telegram → @BotFather
2. `/newbot`
3. Uložte Bot Token

**Krok 2: Vytvorte Channel & Pridajte Bota**
1. Vytvorte Channel
2. Pridajte bota ako admin
3. Povoľte "Post messages"

**Konfigurácia:**
```php
global $wpdb;
$wpdb->insert($wpdb->prefix . 'ai_seo_social_accounts', array(
    'platform' => 'telegram',
    'account_name' => 'My Channel',
    'account_id' => '@mychannel',
    'credentials' => serialize(array(
        'bot_token' => 'YOUR_BOT_TOKEN',
        'channel_id' => '@mychannel',
    )),
    'status' => 'active',
));
```

---

### 2. Facebook

**Požiadavky:**
- Facebook App (developers.facebook.com)
- Facebook Business Page
- Page Access Token

**Konfigurácia:**
```php
$wpdb->insert($wpdb->prefix . 'ai_seo_social_accounts', array(
    'platform' => 'facebook',
    'account_name' => 'My FB Page',
    'account_id' => 'PAGE_ID',
    'credentials' => serialize(array(
        'app_id' => 'YOUR_APP_ID',
        'app_secret' => 'YOUR_APP_SECRET',
        'page_id' => 'YOUR_PAGE_ID',
        'page_access_token' => 'PAGE_ACCESS_TOKEN',
    )),
    'status' => 'active',
));
```

**OAuth Flow:**
1. Create App → developers.facebook.com
2. Add "Facebook Login" product
3. Get Page Access Token via Graph API Explorer
4. Store token in credentials

---

### 3. Instagram

**Požiadavky:**
- Instagram Business/Creator account
- Connected to Facebook Page
- Access Token

**Konfigurácia:**
```php
$wpdb->insert($wpdb->prefix . 'ai_seo_social_accounts', array(
    'platform' => 'instagram',
    'account_name' => 'My IG Account',
    'account_id' => 'INSTAGRAM_ACCOUNT_ID',
    'credentials' => serialize(array(
        'app_id' => 'FB_APP_ID',
        'app_secret' => 'FB_APP_SECRET',
        'instagram_account_id' => 'IG_ACCOUNT_ID',
        'access_token' => 'ACCESS_TOKEN',
    )),
    'status' => 'active',
));
```

**DÔLEŽITÉ:** Instagram VYŽADUJE media (fotky alebo video) - text-only posty NIE SÚ podporované!

---

### 4. Twitter/X

**Požiadavky:**
- Twitter Developer Account
- App with OAuth 2.0
- Access Token + Refresh Token

**Konfigurácia:**
```php
$wpdb->insert($wpdb->prefix . 'ai_seo_social_accounts', array(
    'platform' => 'twitter',
    'account_name' => 'My Twitter',
    'account_id' => '@myusername',
    'credentials' => serialize(array(
        'api_key' => 'API_KEY',
        'api_secret' => 'API_SECRET',
        'access_token' => 'ACCESS_TOKEN',
        'refresh_token' => 'REFRESH_TOKEN',
    )),
    'status' => 'active',
));
```

**OAuth 2.0 Flow:**
1. Create App → developer.twitter.com
2. Enable OAuth 2.0 with PKCE
3. Get tokens via OAuth flow
4. Store tokens

---

### 5. LinkedIn

**Požiadavky:**
- LinkedIn App
- OAuth 2.0 tokens
- Permissions: w_member_social

**Konfigurácia:**
```php
$wpdb->insert($wpdb->prefix . 'ai_seo_social_accounts', array(
    'platform' => 'linkedin',
    'account_name' => 'My LinkedIn',
    'account_id' => 'person_urn',
    'credentials' => serialize(array(
        'client_id' => 'CLIENT_ID',
        'client_secret' => 'CLIENT_SECRET',
        'access_token' => 'ACCESS_TOKEN',
        'refresh_token' => 'REFRESH_TOKEN',
        'person_urn' => 'urn:li:person:XXXXX',
    )),
    'status' => 'active',
));
```

---

### 6. YouTube

**Požiadavky:**
- Google Cloud Project
- YouTube Data API v3 enabled
- OAuth 2.0 credentials

**Konfigurácia:**
```php
$wpdb->insert($wpdb->prefix . 'ai_seo_social_accounts', array(
    'platform' => 'youtube',
    'account_name' => 'My YT Channel',
    'account_id' => 'CHANNEL_ID',
    'credentials' => serialize(array(
        'client_id' => 'GOOGLE_CLIENT_ID',
        'client_secret' => 'GOOGLE_CLIENT_SECRET',
        'access_token' => 'ACCESS_TOKEN',
        'refresh_token' => 'REFRESH_TOKEN',
        'channel_id' => 'CHANNEL_ID',
    )),
    'status' => 'active',
));
```

**DÔLEŽITÉ:** YouTube VYŽADUJE video - text/image posty NIE SÚ podporované!

---

### 7. TikTok

**Požiadavky:**
- TikTok Developer Account
- App (MUST be approved/audited)
- OAuth 2.0 tokens

**Konfigurácia:**
```php
$wpdb->insert($wpdb->prefix . 'ai_seo_social_accounts', array(
    'platform' => 'tiktok',
    'account_name' => 'My TikTok',
    'account_id' => 'open_id',
    'credentials' => serialize(array(
        'client_key' => 'CLIENT_KEY',
        'client_secret' => 'CLIENT_SECRET',
        'access_token' => 'ACCESS_TOKEN',
        'refresh_token' => 'REFRESH_TOKEN',
        'open_id' => 'USER_OPEN_ID',
    )),
    'status' => 'active',
));
```

**DÔLEŽITÉ:**
- TikTok VYŽADUJE video
- App MUSÍ byť auditovaný TikTokom pred použitím Content Posting API

---

## 📝 API Usage Examples

### Základné Publikovanie

```php
$manager = AI_SEO_Social_Media_Manager::get_instance();

// Text only (funguje: Telegram, Facebook, Twitter, LinkedIn)
$result = $manager->publish_now(
    'Your message here #hashtag',
    array('telegram', 'facebook', 'twitter')
);

// Text + Image (funguje: všetky okrem YouTube, TikTok)
$result = $manager->publish_now(
    'Check out this image!',
    array('telegram', 'facebook', 'instagram', 'twitter', 'linkedin'),
    array(
        'media' => array('https://example.com/image.jpg'),
    )
);

// Video post (funguje: všetky platformy)
$result = $manager->publish_now(
    'Watch this video!',
    array('telegram', 'facebook', 'instagram', 'twitter', 'youtube', 'tiktok'),
    array(
        'media' => array('https://example.com/video.mp4'),
    )
);
```

### Multi-Platform Publishing

```php
// Publikuj na všetky platformy naraz
$result = $manager->publish_now(
    'Universal message with image',
    array('telegram', 'facebook', 'instagram', 'twitter', 'linkedin'),
    array(
        'media' => array('https://example.com/image.jpg'),
        'tone' => 'professional',
        'category' => 'tech',
    )
);

// Check results
foreach ($result as $platform => $status) {
    if (is_wp_error($status)) {
        echo "{$platform}: ERROR - " . $status->get_error_message() . "\n";
    } else {
        echo "{$platform}: SUCCESS - Post ID: {$status['platform_post_id']}\n";
    }
}
```

### Scheduling Posts

```php
$schedule_time = date('Y-m-d 09:00:00', strtotime('+1 day'));

$result = $manager->schedule_post(
    'Scheduled content',
    $schedule_time,
    array('telegram', 'facebook', 'twitter'),
    array(
        'media' => array('https://example.com/image.jpg'),
    )
);
```

### Platform-Specific Features

```php
// Telegram: Send poll
$telegram = $manager->get_platform_client('telegram');
$poll_result = $telegram->send_poll(
    'What do you think?',
    array('Option 1', 'Option 2', 'Option 3')
);

// Facebook: Publish album
$facebook = $manager->get_platform_client('facebook');
// (albums are automatically created when publishing with multiple images)

// Instagram: Carousel
$manager->publish_now(
    'Carousel post',
    array('instagram'),
    array(
        'media' => array(
            'https://example.com/img1.jpg',
            'https://example.com/img2.jpg',
            'https://example.com/img3.jpg',
        ),
    )
);
```

---

## 🔧 Aktivácia v Plugine

V `ai-seo-manager.php`:

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
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-facebook-client.php';
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-instagram-client.php';
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-twitter-client.php';
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-linkedin-client.php';
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-youtube-client.php';
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-tiktok-client.php';

    // Initialize
    add_action('plugins_loaded', function() {
        AI_SEO_Social_Media_Manager::get_instance();
    });
}
```

### Activation Hook

```php
register_activation_hook(__FILE__, function() {
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-social-database.php';
    AI_SEO_Social_Database::get_instance()->create_tables();
});
```

---

## 🔄 Automatické Zdieľanie Blogov

```php
update_option('ai_seo_social_auto_share_enabled', true);
update_option('ai_seo_social_auto_share_platforms', array(
    'telegram',
    'facebook',
    'twitter',
    'linkedin'
));
```

Teraz sa každý nový blog automaticky zdieľa!

---

## ⏰ Cron Job Setup

```php
// Register cron schedule
if (!wp_next_scheduled('ai_seo_social_process_queue')) {
    wp_schedule_event(time(), 'every_5_minutes', 'ai_seo_social_process_queue');
}

// Add custom schedule
add_filter('cron_schedules', function($schedules) {
    $schedules['every_5_minutes'] = array(
        'interval' => 300,
        'display' => __('Every 5 Minutes'),
    );
    return $schedules;
});
```

---

## 📊 Platform Comparison

| Platform  | Text Only | Images | Videos | Carousel | Analytics | Free API |
|-----------|-----------|--------|--------|----------|-----------|----------|
| Telegram  | ✅        | ✅     | ✅     | ✅       | ❌        | ✅       |
| Facebook  | ✅        | ✅     | ✅     | ✅       | ✅        | ✅       |
| Instagram | ❌        | ✅     | ✅     | ✅       | ✅        | ✅       |
| Twitter/X | ✅        | ✅     | ✅     | ❌       | ✅        | ⚠️       |
| LinkedIn  | ✅        | ✅     | ✅     | ❌       | ⚠️        | ✅       |
| YouTube   | ❌        | ❌     | ✅     | ❌       | ✅        | ✅       |
| TikTok    | ❌        | ❌     | ✅     | ❌       | ⚠️        | ⚠️       |

**Legend:**
- ✅ Fully supported
- ⚠️ Limited/requires additional access
- ❌ Not supported

---

## 📈 Rate Limits

| Platform  | Per Minute | Per Hour | Per Day  |
|-----------|------------|----------|----------|
| Telegram  | 30         | 1,000    | 10,000   |
| Facebook  | 10         | 200      | 2,000    |
| Instagram | 5          | 25       | 25       |
| Twitter/X | 3          | 50       | 300      |
| LinkedIn  | 5          | 100      | 500      |
| YouTube   | 1          | 10       | 50       |
| TikTok    | 1          | 5        | 20       |

---

## 🐛 Debugging

```php
define('AI_SEO_DEBUG', true);
define('AI_SEO_DEBUG_LEVEL', 'DEBUG');
```

Logs: `wp-content/uploads/ai-seo-manager/logs/`

---

## ✅ Platform Setup Checklist

### Telegram ✅
- [x] Create bot via @BotFather
- [x] Create channel
- [x] Add bot as admin
- [x] Store bot token & channel ID

### Facebook ✅
- [x] Create Facebook App
- [x] Create/Connect Business Page
- [x] Get Page Access Token
- [x] Store credentials

### Instagram ✅
- [x] Convert to Business account
- [x] Connect to Facebook Page
- [x] Get access token
- [x] Get Instagram account ID

### Twitter/X ✅
- [x] Create Developer Account
- [x] Create App
- [x] Enable OAuth 2.0
- [x] Complete OAuth flow
- [x] Store tokens

### LinkedIn ✅
- [x] Create LinkedIn App
- [x] Request API access
- [x] Complete OAuth flow
- [x] Store tokens & person URN

### YouTube ✅
- [x] Create Google Cloud Project
- [x] Enable YouTube Data API v3
- [x] Create OAuth credentials
- [x] Complete OAuth flow
- [x] Store tokens & channel ID

### TikTok ⚠️
- [x] Create Developer Account
- [x] Create App
- [ ] **Submit for audit** (required!)
- [x] Complete OAuth flow
- [x] Store tokens & open_id

---

## 🔍 Troubleshooting

### Authentication Errors

**"Invalid credentials"**
- Check API keys/tokens
- Verify OAuth flow completed
- Check token expiration

**"Token expired"**
- Refresh tokens automatically handled
- Check refresh_token is stored
- Re-authenticate if needed

### Publishing Errors

**"Content too long"**
- Check platform limits (see Rate Limits table)
- Content is automatically validated before publishing

**"Media required"** (Instagram, YouTube, TikTok)
- These platforms require media
- Provide image/video URL in `media` array

**"Rate limit exceeded"**
- Posts automatically queued
- Wait times shown in error message
- Check rate limit stats

### Platform-Specific Issues

**Facebook:**
- Ensure Page Access Token (not User Access Token)
- Check page permissions

**Instagram:**
- Must be Business/Creator account
- Must be connected to Facebook Page

**Twitter:**
- Check OAuth 2.0 PKCE flow
- Verify app permissions

**LinkedIn:**
- Verify w_member_social permission
- Check person_urn format

**YouTube:**
- Check quota limits (10,000 units/day)
- Video upload = 1600 units

**TikTok:**
- App MUST be audited by TikTok
- Without audit, API calls will fail

---

## 💡 Best Practices

### 1. Content Strategy

```php
// Customize per platform
$content = array(
    'telegram' => 'Casual message with emojis 🚀',
    'facebook' => 'Longer, engaging post with story',
    'twitter' => 'Short, punchy message #hashtag',
    'linkedin' => 'Professional update with insights',
    'instagram' => 'Visual story with hashtags #instagood',
);

foreach ($content as $platform => $text) {
    $manager->publish_now($text, array($platform));
}
```

### 2. Scheduling Strategy

```php
// Stagger posts across platforms
$platforms = array('telegram', 'facebook', 'twitter', 'linkedin');
$base_time = strtotime('+1 hour');

foreach ($platforms as $i => $platform) {
    $schedule_time = date('Y-m-d H:i:s', $base_time + ($i * 900)); // 15 min apart
    $manager->schedule_post($content, $schedule_time, array($platform));
}
```

### 3. Media Optimization

```php
// Use appropriate media for each platform
$media = array(
    'instagram' => 'https://example.com/square-1080x1080.jpg',
    'facebook' => 'https://example.com/landscape-1200x630.jpg',
    'twitter' => 'https://example.com/card-1200x675.jpg',
);
```

### 4. Error Handling

```php
$result = $manager->publish_now($content, $platforms);

foreach ($result as $platform => $status) {
    if (is_wp_error($status)) {
        // Log error
        error_log("Failed to publish to {$platform}: " . $status->get_error_message());

        // Notify admin
        wp_mail(get_option('admin_email'), 'Social Media Error', $status->get_error_message());
    }
}
```

---

## 📞 Support & Documentation

**Complete Documentation:**
- SOCIAL_MEDIA_PLAN.md - Implementation plan
- SOCIAL_MEDIA_ARCHITECTURE.md - Technical architecture
- SOCIAL_MEDIA_USAGE.md - This file

**API Documentation:**
- Telegram: https://core.telegram.org/bots/api
- Facebook: https://developers.facebook.com/docs/graph-api
- Instagram: https://developers.facebook.com/docs/instagram-api
- Twitter: https://developer.twitter.com/en/docs/twitter-api
- LinkedIn: https://learn.microsoft.com/en-us/linkedin/marketing/
- YouTube: https://developers.google.com/youtube/v3
- TikTok: https://developers.tiktok.com/

**GitHub:**
- https://github.com/cryptotrust1/acechange-playground

---

**Vytvorené:** AceChange Development Team
**Aktualizované:** 2025-01-17
**Verzia:** Phase 3 (ALL 7 Platforms Complete)
