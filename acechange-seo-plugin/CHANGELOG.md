# Changelog

All notable changes to AceChange SEO Plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-11-15

### Added
- Initial release
- Automatic meta tags generation (description, robots, viewport)
- Open Graph protocol support for social media
- Twitter Cards implementation
- Schema.org structured data (JSON-LD)
  - Article schema
  - Organization schema
  - Breadcrumb schema
  - WebPage schema
- XML Sitemap generation
- Canonical URLs support
- Admin interface with full documentation
- Google Compliance documentation
- Meta box for custom SEO settings per post/page
- Comprehensive test suite:
  - Unit tests for meta tags and schema
  - E2E tests for full integration
  - User story tests for real-world scenarios
- GDPR compliance (no tracking, no cookies)
- Security features (XSS prevention, SQL injection protection, CSRF protection)
- Performance optimizations (<50ms overhead)
- Slovak language support in admin interface

### Security
- All outputs are properly escaped (esc_attr, esc_url, esc_html)
- Nonce verification for forms
- Data sanitization for all inputs
- No external API calls

### Performance
- Minimal HTML overhead (~5KB)
- No JavaScript loaded on frontend
- No database queries overhead
- Fast execution time (<50ms)

### Documentation
- Complete README with installation and usage instructions
- Detailed Google Compliance documentation
- In-admin documentation page
- Code comments and PHPDoc blocks
- Testing documentation

### Compatibility
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+ / MariaDB 10.0+
- Multisite compatible

---

## [Unreleased]

### Planned for 1.1.0
- Custom post types support
- WooCommerce Product schema
- Video schema markup
- FAQ schema
- HowTo schema
- Local business schema

### Planned for 1.2.0
- Multilingual support (WPML, Polylang)
- Import/Export settings
- Bulk edit meta descriptions
- SEO content analysis
- Keyword suggestions
