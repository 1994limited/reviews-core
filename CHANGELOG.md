# Changelog

All notable changes to `1994/reviews-core` are documented here. It follows semantic versioning: a minor or patch release doesn't break the public API.

## 1.0.0 - 2026-10-18

### Added
- `Rating\Summary` and `Rating\Stars`, `Names\DisplayName`, `Spam\Links` and `Spam\TimeToken`, moved out of Reviews for Statamic 1.1.
- Captchas: Turnstile, hCaptcha, reCAPTCHA v2 and v3 (`Captcha\Captchas::make()`).
- `Schema\SchemaBuilder`: review JSON-LD, whole or as properties for an SEO plugin.
- `Import\Importer`: CSV and JSON, with formats for Reviews' own export, Yotpo, Judge.me, REVIEWS.io, Shopify Product Reviews, Trustpilot and WooCommerce, and any file with a rating column.
- `Sync\Sources`: Feefo, REVIEWS.io, Judge.me, Yotpo and Trustpilot, each with the settings it needs and the attribution its platform asks for.
- The default styles, `resources/css/reviews.css`.
- `Import\Formats::platformName()`: a platform's name to show beside its reviews.
- `Import\Csv::cell()` and `uncell()`: CSV cells safe to open in a spreadsheet.
