# AI Social Media Manager - Technická Architektúra

**Verzia:** 1.0.0
**Autor:** AceChange Development Team

---

## 🏛️ High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     WordPress Admin Interface                    │
│  ┌──────────┬──────────┬──────────┬──────────┬─────────────┐   │
│  │Settings  │Composer  │Calendar  │Analytics │Trend Monitor│   │
│  └──────────┴──────────┴──────────┴──────────┴─────────────┘   │
└────────────────────────────┬────────────────────────────────────┘
                             │
┌────────────────────────────▼────────────────────────────────────┐
│              AI Social Media Manager Core                        │
│  ┌────────────────────────────────────────────────────────┐     │
│  │   Social_Media_Manager (Orchestrator)                  │     │
│  └──────────────┬──────────────────────┬──────────────────┘     │
│                 │                      │                         │
│     ┌───────────▼──────────┐  ┌───────▼─────────┐              │
│     │ Platform_Registry    │  │ API_Rate_Limiter│              │
│     └───────────┬──────────┘  └─────────────────┘              │
│                 │                                                 │
└─────────────────┼─────────────────────────────────────────────┘
                  │
    ┌─────────────┼─────────────┐
    │             │             │
┌───▼────┐   ┌───▼────┐   ┌───▼────┐
│Platform│   │   AI   │   │Scheduler│
│Clients │   │ Engine │   │ & Queue │
└────────┘   └────────┘   └─────────┘
```

---

## 🧩 Core Components

### 1. Social_Media_Manager (Main Orchestrator)

**Zodpovednosť:**
- Centrálna koordinácia všetkých operácií
- Inicializácia platform clients
- Routing požiadaviek na správne komponenty

**Metódy:**
```php
class AI_SEO_Social_Media_Manager {
    public function init()
    public function register_platform($platform_client)
    public function publish_post($post_data, $platforms = [])
    public function schedule_post($post_data, $schedule_time, $platforms = [])
    public function generate_ai_content($topic, $tone, $category, $platform)
    public function get_trending_topics($category)
    public function get_analytics($platform, $date_range)
}
```

---

### 2. Platform_Registry

**Zodpovednosť:**
- Registrácia a správa platform clients
- Získanie dostupných platforiem
- Kontrola platform status

**Metódy:**
```php
class AI_SEO_Social_Platform_Registry {
    public function register($platform_name, $client_instance)
    public function get($platform_name)
    public function get_all_active()
    public function is_platform_available($platform_name)
    public function get_platform_capabilities($platform_name)
}
```

---

### 3. API_Rate_Limiter

**Zodpovednosť:**
- Sledovanie API volaní per platform
- Enforcement rate limits
- Queue management pri dosiahnutí limitu

**Metódy:**
```php
class AI_SEO_Social_Rate_Limiter {
    public function check_limit($platform, $action)
    public function increment($platform, $action)
    public function get_remaining($platform, $action)
    public function reset($platform)
    public function get_reset_time($platform)
}
```

---

## 📡 Platform Clients Architecture

### Base Platform Client (Abstract)

```php
abstract class AI_SEO_Social_Platform_Client {

    // Properties
    protected $platform_name;
    protected $account_id;
    protected $credentials;
    protected $logger;
    protected $performance;

    // Abstract methods (must implement)
    abstract public function authenticate();
    abstract public function publish($content, $media = []);
    abstract public function get_analytics($post_id, $date_range);
    abstract public function validate_content($content);
    abstract public function get_rate_limits();

    // Common methods
    public function is_authenticated()
    public function refresh_token()
    public function handle_error($error)
    public function log_action($action, $data)
}
```

---

### Platform-Specific Clients

#### Facebook_Client

```php
class AI_SEO_Social_Facebook_Client extends AI_SEO_Social_Platform_Client {

    private $app_id;
    private $app_secret;
    private $page_id;
    private $graph_api_version = 'v22.0';

    public function authenticate() {
        // OAuth 2.0 flow
        // Get Page Access Token
    }

    public function publish($content, $media = []) {
        // POST /{page-id}/feed
        // Handle images, videos, links
    }

    public function get_analytics($post_id, $date_range) {
        // GET /{post-id}/insights
    }

    public function validate_content($content) {
        // Check character limits
        // Validate media formats
    }
}
```

#### Instagram_Client

```php
class AI_SEO_Social_Instagram_Client extends AI_SEO_Social_Platform_Client {

    private $instagram_account_id;
    private $graph_api_version = 'v22.0';

    public function publish($content, $media = []) {
        // Two-step process:
        // 1. POST /{ig-user-id}/media (create container)
        // 2. POST /{ig-user-id}/media_publish (publish)
    }

    public function publish_story($media, $options = []) {
        // Story-specific publishing
    }

    public function publish_reel($video, $options = []) {
        // Reel-specific publishing
    }
}
```

#### Twitter_X_Client

```php
class AI_SEO_Social_Twitter_Client extends AI_SEO_Social_Platform_Client {

    private $api_key;
    private $api_secret;
    private $bearer_token;
    private $api_version = 'v2';

    public function authenticate() {
        // OAuth 2.0 PKCE flow
    }

    public function publish($content, $media = []) {
        // POST /2/tweets
        // Handle media upload separately
    }

    public function publish_thread($tweets = []) {
        // Thread publishing
    }

    public function get_trending_topics($location = 'global') {
        // GET /2/trends/place
    }
}
```

#### LinkedIn_Client

```php
class AI_SEO_Social_LinkedIn_Client extends AI_SEO_Social_Platform_Client {

    private $client_id;
    private $client_secret;
    private $organization_id; // For company pages

    public function publish($content, $media = []) {
        // POST /ugcPosts
    }

    public function publish_article($article_data) {
        // Article publishing
    }
}
```

#### YouTube_Client

```php
class AI_SEO_Social_YouTube_Client extends AI_SEO_Social_Platform_Client {

    private $client_id;
    private $client_secret;
    private $api_key;

    public function upload_video($video_file, $metadata) {
        // POST /youtube/v3/videos
        // Resumable upload
    }

    public function set_thumbnail($video_id, $thumbnail_file) {
        // POST /youtube/v3/thumbnails/set
    }

    public function get_quota_usage() {
        // Check daily quota
    }
}
```

#### TikTok_Client

```php
class AI_SEO_Social_TikTok_Client extends AI_SEO_Social_Platform_Client {

    private $client_key;
    private $client_secret;
    private $audit_status = 'unaudited'; // unaudited, audited

    public function publish($video_file, $options = []) {
        // POST /share/video/upload/
        // Handle audit status limitations
    }

    public function check_audit_status() {
        // Check if app is audited
    }
}
```

#### Telegram_Client

```php
class AI_SEO_Social_Telegram_Client extends AI_SEO_Social_Platform_Client {

    private $bot_token;
    private $channel_id;

    public function publish($content, $media = []) {
        // POST https://api.telegram.org/bot{token}/sendMessage
        // or sendPhoto, sendVideo, etc.
    }

    public function publish_poll($question, $options) {
        // POST sendPoll
    }

    public function pin_message($message_id) {
        // POST pinChatMessage
    }
}
```

---

## 🤖 AI Content Engine Architecture

### Content_Generator

```php
class AI_SEO_Social_Content_Generator {

    private $ai_manager; // Reuse existing AI_SEO_Manager_AI_Manager
    private $tone_customizer;

    public function generate_post($params) {
        // $params: topic, tone, category, platform, max_length

        // 1. Get trend context if category specified
        // 2. Build AI prompt with tone
        // 3. Generate content via AI_Manager
        // 4. Format for specific platform
        // 5. Add hashtags if applicable
        // 6. Validate against platform limits

        return $formatted_post;
    }

    public function generate_hashtags($content, $category, $count = 5) {
        // AI-generated relevant hashtags
    }

    public function generate_variations($base_content, $platforms = []) {
        // Generate platform-specific variations
    }
}
```

---

### Tone_Customizer

```php
class AI_SEO_Social_Tone_Customizer {

    private $tones = [
        'professional' => 'Professional and authoritative tone...',
        'casual' => 'Friendly and conversational tone...',
        'funny' => 'Humorous and entertaining tone...',
        'inspirational' => 'Motivational and uplifting tone...',
        'educational' => 'Informative and instructive tone...',
        'promotional' => 'Marketing-focused and persuasive tone...',
    ];

    public function get_tone_prompt($tone, $platform) {
        // Return platform-optimized tone instructions
    }

    public function customize_for_platform($content, $platform) {
        // Adapt content for platform specifics
        // e.g., LinkedIn = more professional, TikTok = more casual
    }
}
```

---

### Trend_Tracker

```php
class AI_SEO_Social_Trend_Tracker {

    private $categories = [
        'crypto', 'fashion', 'tech', 'people', 'politics', 'general'
    ];

    public function fetch_trends($category, $count = 10) {
        // 1. Query external APIs (Google Trends, NewsAPI, Twitter)
        // 2. Store in database
        // 3. Calculate trend score
        // 4. Return top trends
    }

    public function get_trend_keywords($trend_topic) {
        // Extract keywords from trend
    }

    public function suggest_post_topic($category) {
        // AI suggests post topic based on trends
    }

    public function update_trend_scores() {
        // Cron job to update all trend scores
    }
}
```

---

## 📅 Scheduler & Queue Architecture

### Post_Scheduler

```php
class AI_SEO_Social_Post_Scheduler {

    private $queue_manager;
    private $compliance_checker;

    public function schedule($post_data, $schedule_time, $platforms = []) {
        // 1. Validate schedule time
        // 2. Check compliance rules
        // 3. Create social_post records
        // 4. Add to queue
    }

    public function process_queue() {
        // Cron job function
        // 1. Get due posts from queue
        // 2. Check rate limits
        // 3. Publish via platform clients
        // 4. Update status
        // 5. Handle failures (retry)
    }

    public function get_next_optimal_time($platform, $category) {
        // AI suggests best posting time
        // Based on historical engagement data
    }
}
```

---

### Queue_Manager

```php
class AI_SEO_Social_Queue_Manager {

    public function add_to_queue($social_post_id, $scheduled_for, $priority = 5) {
        // Insert into social_queue table
    }

    public function get_due_posts($limit = 10) {
        // Get posts scheduled for now or past
        // Not processing, ordered by priority
    }

    public function mark_processing($queue_id) {
        // Set processing flag
    }

    public function mark_completed($queue_id) {
        // Remove from queue or mark completed
    }

    public function schedule_retry($queue_id, $delay_minutes = 30) {
        // Schedule retry for failed post
    }
}
```

---

### Compliance_Checker

```php
class AI_SEO_Social_Compliance_Checker {

    public function check_google_compliance($account_id, $new_schedule) {
        // 1. Get recent posts count for today
        // 2. Check if adding new post exceeds 3/day
        // 3. Check time variation (not same time)
        // 4. Return true/false with message
    }

    public function get_daily_post_count($account_id, $date = 'today') {
        // Count posts for specific date
    }

    public function randomize_schedule($preferred_time) {
        // Add -2h to +2h random offset
        // Return randomized time
    }

    public function should_skip_day() {
        // Randomly skip some days (10% chance)
        // To avoid robotic patterns
    }
}
```

---

### Retry_Handler

```php
class AI_SEO_Social_Retry_Handler {

    private $max_retries = 3;
    private $backoff_strategy = 'exponential'; // exponential, linear

    public function should_retry($social_post_id) {
        // Check if retry count < max_retries
    }

    public function calculate_next_retry($attempt) {
        // Exponential backoff: 5min, 15min, 45min
        // or Linear: 30min, 60min, 90min
    }

    public function handle_permanent_failure($social_post_id) {
        // Mark as failed permanently
        // Notify admin
    }
}
```

---

## 📊 Analytics & Reporting Architecture

### Analytics_Aggregator

```php
class AI_SEO_Social_Analytics_Aggregator {

    public function sync_analytics($platform, $post_id) {
        // 1. Fetch analytics from platform API
        // 2. Store in social_analytics table
        // 3. Calculate engagement rate
    }

    public function sync_all_platforms() {
        // Cron job to sync all platforms
    }

    public function get_post_analytics($social_post_id) {
        // Return analytics for specific post
    }

    public function get_platform_summary($platform, $date_range) {
        // Aggregate stats for platform
    }
}
```

---

### Performance_Tracker

```php
class AI_SEO_Social_Performance_Tracker {

    public function calculate_engagement_rate($analytics) {
        // (likes + comments + shares) / impressions * 100
    }

    public function get_top_posts($platform, $metric = 'engagement_rate', $limit = 10) {
        // Return top performing posts
    }

    public function get_best_posting_times($platform) {
        // Analyze when posts perform best
    }

    public function compare_platforms($date_range) {
        // Compare performance across platforms
    }
}
```

---

### Report_Generator

```php
class AI_SEO_Social_Report_Generator {

    public function generate_daily_report() {
        // Summary of today's activity
    }

    public function generate_weekly_report() {
        // Summary of this week
    }

    public function generate_monthly_report() {
        // Summary of this month
    }

    public function export_to_pdf($report_data) {
        // Export report as PDF
    }

    public function export_to_csv($report_data) {
        // Export report as CSV
    }

    public function email_report($recipient, $report_data) {
        // Email report to admin
    }
}
```

---

## 🔐 Security Architecture

### Credentials Storage

```php
class AI_SEO_Social_Credentials_Manager {

    public function store($account_id, $credentials) {
        // Encrypt before storing
        // Use WordPress encryption functions
    }

    public function retrieve($account_id) {
        // Decrypt and return
    }

    public function update($account_id, $new_credentials) {
        // Update encrypted credentials
    }

    public function delete($account_id) {
        // Securely delete credentials
    }

    private function encrypt($data) {
        // Encryption logic
    }

    private function decrypt($encrypted_data) {
        // Decryption logic
    }
}
```

---

## 🔄 Data Flow Diagrams

### Publishing Flow

```
User Input (Admin)
    │
    ├─> Manual Post Composer
    │       │
    │       ├─> Content entered
    │       ├─> Platforms selected
    │       ├─> Schedule time (optional)
    │       └─> Media uploaded (optional)
    │
    └─> OR Auto-generation
            │
            ├─> Topic/Trend selected
            ├─> AI generates content
            └─> User reviews & approves

    ↓
Social_Media_Manager
    │
    ├─> Compliance_Checker validates
    ├─> Content_Generator formats for each platform
    ├─> Creates social_post records
    │
    ├─> If Immediate:
    │   └─> Publish_Now()
    │       └─> Platform_Client→publish()
    │
    └─> If Scheduled:
        └─> Queue_Manager→add_to_queue()
            └─> Waits for Cron

Cron Job (Every 5 minutes)
    │
    └─> Post_Scheduler→process_queue()
        │
        ├─> Get due posts
        ├─> Check rate limits
        ├─> Platform_Client→publish()
        ├─> Update status
        └─> If failure → Retry_Handler
```

---

### Analytics Sync Flow

```
Cron Job (Every hour)
    │
    └─> Analytics_Aggregator→sync_all_platforms()
        │
        ├─> For each platform:
        │   │
        │   ├─> Get published posts (last 7 days)
        │   ├─> Platform_Client→get_analytics()
        │   ├─> Store in social_analytics table
        │   └─> Calculate metrics
        │
        └─> Performance_Tracker updates stats
            └─> Identifies top posts
```

---

### Trend Tracking Flow

```
Cron Job (Every 6 hours)
    │
    └─> Trend_Tracker→update_trends()
        │
        ├─> For each category:
        │   │
        │   ├─> Query Google Trends API
        │   ├─> Query NewsAPI
        │   ├─> Query Twitter/X Trends
        │   ├─> Aggregate & score
        │   └─> Store in social_trends table
        │
        └─> Expire old trends (>48h)
```

---

## 🗂️ File Structure

```
ai-seo-manager/
├── includes/
│   └── social-media/
│       ├── class-social-media-manager.php
│       ├── class-platform-registry.php
│       ├── class-rate-limiter.php
│       ├── class-credentials-manager.php
│       │
│       ├── platforms/
│       │   ├── class-platform-client.php (abstract)
│       │   ├── class-facebook-client.php
│       │   ├── class-instagram-client.php
│       │   ├── class-twitter-client.php
│       │   ├── class-linkedin-client.php
│       │   ├── class-youtube-client.php
│       │   ├── class-tiktok-client.php
│       │   └── class-telegram-client.php
│       │
│       ├── ai-engine/
│       │   ├── class-content-generator.php
│       │   ├── class-tone-customizer.php
│       │   └── class-trend-tracker.php
│       │
│       ├── scheduler/
│       │   ├── class-post-scheduler.php
│       │   ├── class-queue-manager.php
│       │   ├── class-compliance-checker.php
│       │   └── class-retry-handler.php
│       │
│       ├── analytics/
│       │   ├── class-analytics-aggregator.php
│       │   ├── class-performance-tracker.php
│       │   └── class-report-generator.php
│       │
│       └── database/
│           └── class-social-database.php
│
├── admin/
│   └── social-media/
│       ├── class-social-admin-menu.php
│       ├── class-settings-page.php
│       ├── class-post-composer.php
│       ├── class-calendar-view.php
│       ├── class-analytics-dashboard.php
│       └── class-trend-monitor.php
│       │
│       └── views/
│           ├── settings-page.php
│           ├── post-composer.php
│           ├── calendar-view.php
│           ├── analytics-dashboard.php
│           └── trend-monitor.php
│
├── assets/
│   ├── css/
│   │   └── social-media-admin.css
│   ├── js/
│   │   ├── social-media-admin.js
│   │   ├── post-composer.js
│   │   ├── calendar-view.js
│   │   └── analytics-charts.js
│   └── img/
│       └── platform-icons/
│
└── tests/
    └── social-media/
        ├── Unit/
        │   ├── PlatformClientTest.php
        │   ├── ContentGeneratorTest.php
        │   └── ComplianceCheckerTest.php
        └── Integration/
            ├── FacebookPublishTest.php
            ├── InstagramPublishTest.php
            └── SchedulerTest.php
```

---

## 🔌 WordPress Hooks & Filters

### Actions

```php
// Po inicializácii Social Media Manager
do_action('ai_seo_social_init');

// Pred publikovaním postu
do_action('ai_seo_social_before_publish', $social_post_id, $platform);

// Po publikovaní postu
do_action('ai_seo_social_after_publish', $social_post_id, $platform, $platform_post_id);

// Pred AI generovaním obsahu
do_action('ai_seo_social_before_generate_content', $params);

// Po AI generovaní obsahu
do_action('ai_seo_social_after_generate_content', $content, $params);

// Pri synchronizácii analytics
do_action('ai_seo_social_analytics_synced', $platform, $post_id);

// Pri aktualizácii trendov
do_action('ai_seo_social_trends_updated', $category, $trends);
```

### Filters

```php
// Filter AI generated content pred publikovaním
add_filter('ai_seo_social_generated_content', $content, $platform, $params);

// Filter hashtags
add_filter('ai_seo_social_hashtags', $hashtags, $content, $platform);

// Filter post scheduling time
add_filter('ai_seo_social_schedule_time', $schedule_time, $compliance_data);

// Filter compliance rules
add_filter('ai_seo_social_compliance_rules', $rules, $platform);

// Filter platform capabilities
add_filter('ai_seo_social_platform_capabilities', $capabilities, $platform);

// Filter analytics data
add_filter('ai_seo_social_analytics_data', $analytics, $platform, $post_id);
```

---

## 🎯 Performance Optimization

### Caching Strategy

```php
// Cache trend data (6 hours)
set_transient('ai_seo_social_trends_' . $category, $trends, 6 * HOUR_IN_SECONDS);

// Cache analytics (1 hour)
set_transient('ai_seo_social_analytics_' . $post_id, $analytics, HOUR_IN_SECONDS);

// Cache platform capabilities (1 day)
set_transient('ai_seo_social_capabilities_' . $platform, $capabilities, DAY_IN_SECONDS);
```

### Queue Processing

- Process max 10 posts per cron run
- Prioritize by priority field (1-10)
- Spread API calls to avoid rate limits
- Use exponential backoff for retries

### Database Optimization

- Indexes on frequently queried columns
- Partitioning by date for analytics table
- Regular cleanup of old data (>90 days)
- Archive instead of delete for compliance

---

## 📈 Scalability Considerations

### Horizontal Scaling

- **Queue System:** Use external queue (Redis, RabbitMQ) for large deployments
- **Cron Jobs:** Distribute across multiple servers
- **API Calls:** Load balance across multiple API keys

### Vertical Scaling

- **Database:** Optimize queries, add indexes
- **Memory:** Increase PHP memory limit for AI operations
- **Processing:** Batch operations where possible

### Multi-Site Support

- Separate credentials per site
- Shared trend data across network
- Centralized analytics aggregation

---

## ✅ Implementačná Checklist

### Fáza 1: Infrastructure ✅
- [ ] Databázové tabuľky
- [ ] Základné triedy (Manager, Registry, Rate Limiter)
- [ ] Debug integrácia

### Fáza 2: Platform Clients (Prioritizované)
**P0:**
- [ ] Telegram_Client
- [ ] Facebook_Client
- [ ] Instagram_Client

**P1:**
- [ ] LinkedIn_Client
- [ ] Twitter_X_Client

**P2:**
- [ ] YouTube_Client
- [ ] TikTok_Client

### Fáza 3: AI Engine
- [ ] Content_Generator
- [ ] Tone_Customizer
- [ ] Trend_Tracker

### Fáza 4: Scheduler
- [ ] Post_Scheduler
- [ ] Queue_Manager
- [ ] Compliance_Checker
- [ ] Retry_Handler

### Fáza 5: Analytics
- [ ] Analytics_Aggregator
- [ ] Performance_Tracker
- [ ] Report_Generator

### Fáza 6: Admin UI
- [ ] Settings Page
- [ ] Post Composer
- [ ] Calendar View
- [ ] Analytics Dashboard
- [ ] Trend Monitor

### Fáza 7: Testing
- [ ] Unit Tests
- [ ] Integration Tests
- [ ] E2E Tests

### Fáza 8: Documentation
- [ ] User Manual
- [ ] API Setup Guides
- [ ] Video Tutorials

---

**Poznámka:** Táto architektúra je modulárna a flexibilná. Každý komponent môže byť vyvíjaný a testovaný samostatne.

---

**Autor:** AceChange Development Team
**Aktualizované:** 2025-01-17
**Verzia:** 1.0.0
