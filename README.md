# Reviews Core

The framework-free core shared by [Reviews for Craft CMS](https://github.com/1994limited/reviews-craft) and [Reviews for Statamic](https://github.com/1994limited/reviews-statamic). You don't install it yourself; the plugin or addon requires it.

Reviews collects reviews and star ratings on the site itself, moderates them, and marks them up for search engines. Each product stores reviews its own way (Craft elements; Statamic files or a database) and draws its own Control Panel. Everything that's the same on both lives here:

- **Ratings** (`Rating\Summary`, `Rating\Stars`): averages, the star breakdown, per-criterion averages, and stars to draw.
- **Names** (`Names\DisplayName`): "Jo Bloggs" shown as "Jo B.", or in full.
- **Spam checks** (`Spam\Links`, `Spam\TimeToken`): too many links, and forms sent faster than a person could fill them in.
- **Captchas** (`Captcha\*`): Turnstile, hCaptcha, reCAPTCHA v2 and v3, behind one interface.
- **Schema** (`Schema\SchemaBuilder`): Product (or other type) JSON-LD with `aggregateRating` and `review`, or just the properties, for an SEO plugin's own markup.
- **Import** (`Import\Importer`): CSV or JSON exports from Yotpo, Judge.me, REVIEWS.io, Shopify Product Reviews, Trustpilot, WooCommerce, Reviews itself, or any file with a rating column. See [Import and sync](docs/import-and-sync.md).
- **Sync** (`Sync\Sources`): fetching reviews from Feefo, REVIEWS.io, Judge.me, Yotpo and Trustpilot on a schedule.
- **The default styles** (`resources/css/reviews.css`): `--reviews-*` variables, in `@layer reviews`, inheriting the site's fonts and colours.

Core depends on no framework or CMS. CI fails if `src/` names `Illuminate`, `Laravel`, `Craft`, `Yii` or `Statamic` (`bin/check-boundaries`). HTTP goes through `Http\Client`, which each product implements with its own client.

## Using it

```php
use NineteenNinetyFour\Reviews\Core\Import\Importer;
use NineteenNinetyFour\Reviews\Core\Rating\Summary;
use NineteenNinetyFour\Reviews\Core\Sync\Sources;

$summary = Summary::from(['stars' => [5 => 12, 4 => 3, 3 => 0, 2 => 1, 1 => 0]]);
$summary->average();   // 4.6

$result = (new Importer)->read(file_get_contents('yotpo-export.csv'));
$result->format?->label();   // "Yotpo"

foreach (Sources::find('feefo')->fetch($http, ['merchant' => 'dog-ear-books']) as $review) {
    // An ImportedReview: match $review->product to an entry, and save it once ($review->fingerprint()).
}
```

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint
vendor/bin/phpstan analyse
php bin/check-boundaries
```

## Licence

Commercial, as part of Reviews. See [LICENSE.md](LICENSE.md).
