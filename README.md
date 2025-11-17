# AI SEO Manager Pro - WordPress Plugin

🤖 Inteligentný AI SEO Manažér s Claude AI, automatickou analýzou a approval workflow + AI Social Media Manager

## 🎯 Funkcie

### 1. 🤖 AI Integrácia (Claude + OpenAI)
- **Dual AI Provider System**: Claude AI (primárny) + OpenAI (backup/alternatíva)
- **Automatický Fallback**: Ak jeden AI selže, použije sa druhý
- **Konfigurovateľné API kľúče**: Vlastná konfigurácia pre oba providery
- **Pokročilá SEO analýza**: AI analyzuje obsah a generuje konkrétne odporúčania

**Možnosti:**
- `claude` - Len Claude AI (odporúčané)
- `openai` - Len OpenAI
- `both` - Oba s automatickým fallbackom

### 2. 📊 Analytické Prepojenia
- **Google Analytics 4**: Tracking udalostí a performance metrik
- **Google Search Console**: Získavanie keyword pozícií, impressions, CTR
- **Search Opportunities**: Automatická identifikácia keywords s vysokým potenciálom

**Funkcie:**
- Tracking schválených odporúčaní
- Monitoring optimalizačných akcií
- Analýza top performing stránok
- Identifikácia content gaps

### 3. 🔍 Automatická SEO Analýza
Komplexná analýza zahŕňa:

#### Technical SEO:
- Meta tagy (title, description, canonical)
- Heading štruktúra (H1-H6)
- Image optimization (ALT texty)
- Internal/external linky
- Mobile optimization
- Page speed
- Schema markup
- Sitemap prítomnosť

#### Content Analysis:
- Keyword usage a density
- Readability (Flesch score)
- Content quality (obrázky, video, listy, formátovanie)
- Word count a sentence length

#### Competitor Analysis:
- Keyword difficulty estimation
- Content gaps identification
- Backlink stratégie

### 4. 💡 AI SEO Manažér Režim
AI pracuje ako profesionálny SEO konzultant:

**Automatické Odporúčania:**
- Prioritizované podľa dôležitosti (Critical, High, Medium, Low)
- AI confidence scoring (0-100%)
- Špecifické akcie na vykonanie
- Estimated impact rating

**Strategické Plány:**
- Quick Wins - rýchle zisky
- High Impact Tasks - vysoký dopad
- Long-term Priorities - dlhodobé priority
- Resource Allocation - alokácia zdrojov

**Typy Odporúčaní:**
- Meta optimization (title, description)
- Keyword optimization
- Image optimization (ALT texty)
- Content structure (headings)
- Link optimization
- Technical SEO fixes
- Search opportunities

### 5. 🚀 Auto-pilot Režim s Approval Workflow

#### Režimy:
1. **Approval Mode** (Odporúčané)
   - AI navrhne zmeny
   - Čaká na vaše schválenie
   - Aplikuje len po vašom súhlase

2. **Auto Mode** (Pokročilé)
   - Automaticky aplikuje bezpečné zmeny
   - High-risk zmeny stále vyžadujú approval
   - Len pre dôveryhodné akcie (>85% AI confidence)

#### Bezpečnostné Funkcie:
- **Backup System**: Všetky originály sú zálohované pred zmenou
- **Rollback**: Kompletný rollback možný kedykoľvek
- **AI Confidence Threshold**: Len high-confidence recommendations
- **Priority Filtering**: Critical/High vždy vyžaduje approval
- **Activity Logging**: Úplný audit trail všetkých akcií

#### Povolené Akcie (konfigurovateľné):
- ✅ Meta Descriptions - Generovanie SEO-optimalizovaných meta descriptions
- ✅ ALT Texts - Automatické ALT texty pre obrázky
- ✅ Headings - Optimalizácia nadpisov
- ✅ Internal Links - Návrhy interných linkov

### 6. 📱 AI Social Media Manager
Komplexný systém pre správu social media:

**Podporované Platformy:**
- ✅ **Telegram** - Bot messaging s channel support
- ✅ **Facebook** - Pages a Groups posting
- ✅ **Instagram** - Photos, videos, carousel posts
- ✅ **Twitter/X** - Tweets s media support
- ✅ **LinkedIn** - Company pages a personal profiles
- ✅ **YouTube** - Video uploads s descriptions
- ✅ **TikTok** - Short video sharing

**Hlavné Funkcie:**
- 🤖 **AI Content Generation** - Automatické generovanie obsahu pre každú platformu
- 📅 **Scheduler & Queue** - Plánovanie príspevkov, retry logic
- 📊 **Analytics** - Získavanie štatistík z platforiem
- 🎨 **Composer** - Vizuálny editor pre tvorbu príspevkov
- 📈 **Performance Tracking** - Top posts, best posting times
- 🔄 **Multi-Platform Publishing** - Publikuj na viacero platforiem naraz
- ⚡ **Rate Limiting** - Automatická kontrola API limitov
- 🔐 **Bezpečné Credential Storage** - Šifrované ukladanie API kľúčov

**Komponenty:**
- Platform Clients pre všetky 7 platforiem
- AI Content Generator s platform optimization
- Scheduler s exponential backoff retry
- Analytics s reporting a trend analysis
- Admin UI s Dashboard, Composer, Calendar
- Databázová štruktúra pre posts, queue, analytics

## 📋 Inštalácia

1. Nahrajte plugin do `/wp-content/plugins/ai-seo-manager/`
2. Aktivujte plugin v WordPress admin
3. Prejdite na **AI SEO Manager > Settings**
4. Zadajte API kľúče:
   - Claude API Key: https://console.anthropic.com
   - OpenAI API Key: https://platform.openai.com (voliteľné)
5. Nakonfigurujte Google Analytics 4 a Search Console (voliteľné)
6. Nastavte Autopilot podľa preferencií

## ⚙️ Konfigurácia

### AI Nastavenia
```
AI Provider: claude / openai / both
Claude API Key: sk-ant-...
Claude Model: claude-3-5-sonnet-20241022 (odporúčané)
OpenAI API Key: sk-... (voliteľné)
```

### Analytics
```
GA4 Measurement ID: G-XXXXXXXXXX
GA4 API Secret: (pre custom events)
Google Search Console: OAuth credentials
```

### Autopilot
```
Enabled: Áno/Nie
Mode: approval / auto
Allowed Actions:
  - Meta Descriptions ✓
  - ALT Texts ✓
  - Headings ✓
  - Internal Links ✗
```

### Pokročilé
```
Auto Analysis: Automatická analýza pri publikovaní
Max API Calls/Day: 100 (limit)
Debug Mode: Áno/Nie
```

## 🎨 Používanie

### Dashboard
- Prehľad všetkých štatistík
- Pending approvals
- Recent recommendations
- Autopilot status
- API usage

### Approvals
- Zoznam čakajúcich approval requests
- Approve/Reject s možnosťou poznámok
- Priority filtering
- AI confidence zobrazenie

### Recommendations
- Všetky SEO odporúčania
- Zoskupené podľa priority
- Post-specific recommendations
- Bulk actions

### Autopilot
- Status monitoring
- Success rate tracking
- Configuration controls
- Safety overview

## 🔧 Pre Vývojárov

### Hooks a Filtre

```php
// Po inicializácii pluginu
do_action('ai_seo_manager_init');

// Pri schválení odporúčania
do_action('ai_seo_manager_recommendation_approved', $recommendation_id, $user_id);

// Po optimalizácii obsahu
do_action('ai_seo_manager_content_optimized', $post_id);

// Filter pre admin tracking
add_filter('ai_seo_manager_exclude_admin_tracking', '__return_true');

// Filter pre approval notifications
add_filter('ai_seo_manager_send_approval_notifications', '__return_true');
```

### REST API Endpoints

```
GET    /wp-json/ai-seo-manager/v1/stats
POST   /wp-json/ai-seo-manager/v1/analyze/{post_id}
GET    /wp-json/ai-seo-manager/v1/recommendations
POST   /wp-json/ai-seo-manager/v1/recommendations/{id}/approve
POST   /wp-json/ai-seo-manager/v1/recommendations/{id}/reject
POST   /wp-json/ai-seo-manager/v1/autopilot/toggle
GET    /wp-json/ai-seo-manager/v1/settings
POST   /wp-json/ai-seo-manager/v1/settings
```

### Databázové Tabuľky

- `wp_ai_seo_analysis` - SEO analýzy
- `wp_ai_seo_recommendations` - Odporúčania
- `wp_ai_seo_approvals` - Approval záznamy
- `wp_ai_seo_logs` - Activity logy
- `wp_ai_seo_keywords` - Keyword tracking

## 🚀 Najlepšie Postupy

### Pre Začiatočníkov:
1. Začnite s **Approval Mode**
2. Povoľte len **Meta Descriptions** a **ALT Texts**
3. Prezrite si prvých 10-20 approvalov manuálne
4. Sledujte success rate a AI confidence

### Pre Pokročilých:
1. Povoľte **Auto Mode** po 50+ schválených recommendations
2. Aktivujte všetky akcie podľa potreby
3. Integrujte s Google Analytics a Search Console
4. Využívajte API pre custom integrácie

### Optimalizácia:
- Nastavte focus keyword pre každý post
- Spúšťajte analýzu pravidelne (daily cron)
- Monitorujte API usage limits
- Využívajte Search Console opportunities

## 📊 Metriky a Reporting

- **Pending Recommendations**: Čakajúce odporúčania
- **Awaiting Approval**: Na schválenie
- **Completed**: Dokončené optimalizácie
- **Success Rate**: Úspešnosť autopilota
- **API Calls**: Denné/celkové volania AI API

## 🔐 Bezpečnosť

- ✅ Všetky API kľúče uložené v WordPress options
- ✅ CSRF protection (nonces)
- ✅ Capability checks (edit_posts, manage_options)
- ✅ Input sanitization a validation
- ✅ Backup originálov pred zmenou
- ✅ Audit logging všetkých akcií

## 🆘 Podpora

- **GitHub Issues**: https://github.com/cryptotrust1/acechange-playground/issues
- **Dokumentácia**: V plugine
- **API Dokumentácia**: console.anthropic.com

## 📝 Licencia

GPL v2 or later

## 👨‍💻 Autor

AceChange - https://acechange.com

## 🐛 Debug a Monitoring

Plugin obsahuje komplexný debug systém pre vývoj a troubleshooting:

### Zapnutie Debug Módu

Pridajte do `wp-config.php`:
```php
define('AI_SEO_DEBUG', true);
define('AI_SEO_DEBUG_LEVEL', 'DEBUG'); // ERROR, WARNING, INFO, DEBUG
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Funkcie Debug Systému

- **Multi-level Logging**: ERROR, WARNING, INFO, DEBUG
- **Performance Monitoring**: Tracking času, pamäte, DB queries
- **API Call Tracking**: Success rate, duration, errors
- **Admin Debug Panel**: `AI SEO Manager > Debug Logs`
- **Auto-rotation**: Automatická rotácia log súborov
- **CSV Export**: Export logov pre analýzu

### Admin Debug Panel

Po zapnutí debug módu sa zobrazí nové menu **Debug Logs** kde môžete:
- Prezerať všetky logy s filtrami
- Exportovať logy do CSV
- Sledovať API performance metriky
- Monitorovať memory usage
- Čistiť staré logy

**Detailná dokumentácia:** Pozri [DEBUG.md](DEBUG.md)

## 🎉 Changelog

### v2.0.0 (2025-01-17)
- 🚀 **NEW: AI Social Media Manager**
  - ✅ Podpora pre 7 platforiem (Telegram, Facebook, Instagram, Twitter, LinkedIn, YouTube, TikTok)
  - ✅ AI Content Generator s platform optimization
  - ✅ Scheduler & Queue Manager s retry logic
  - ✅ Analytics Component s reporting
  - ✅ Admin UI (Dashboard, Composer, Calendar, Analytics)
  - ✅ Rate Limiting pre všetky platformy
  - ✅ Multi-platform publishing
  - ✅ Kompletné testy (Unit + E2E)

### v1.0.0 (2025-01-15)
- ✅ Prvé vydanie
- ✅ Claude + OpenAI AI integrácia
- ✅ Google Analytics 4 a Search Console
- ✅ Komplexná SEO analýza
- ✅ AI SEO Manažér režim
- ✅ Auto-pilot s approval workflow
- ✅ Admin dashboard a UI
- ✅ REST API
- ✅ Komplexný debug a monitoring systém
- ✅ Performance tracking
- ✅ Multi-level logging
- ✅ Admin debug panel

---

Made with ❤️ and 🤖 AI
