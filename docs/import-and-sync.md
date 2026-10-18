# Import and sync

Both turn another platform's reviews into `Import\ImportedReview` objects. The product (Craft or Statamic) matches each to an entry or product, saves it, and remembers its `fingerprint()` so the same review is never added twice.

## Import

`Importer::read($contents, ?$format)` reads CSV (any delimiter, with or without a byte-order mark) or JSON (a list, or `{"reviews": [...]}`; nested keys are flattened with `_`). Without a format it picks the one whose columns match best.

| Format | Key | Recognised by | Products matched on |
| --- | --- | --- | --- |
| Reviews (Craft or Statamic export) | `reviews` | entry, rating, status, created_at | entry ID |
| Yotpo | `yotpo` | Review Score, Review Content | SKU (`yotpo_site_reviews` = the business) |
| Judge.me | `judgeme` | rating, body, review_date, reviewer_name | Shopify handle |
| REVIEWS.io | `reviewsio` | Star Rating, Comment | SKU |
| Shopify Product Reviews | `shopify` | product_handle, state, rating, author, body | Shopify handle |
| Trustpilot | `trustpilot` | Review Stars, Review Content | SKU, if given; else the business |
| WooCommerce | `woocommerce` | comment_content, rating | SKU, URL or post ID |
| Anything else | `generic` | a rating column | whichever product column it has |

Ratings are whole stars from 1 to 5 (a format on another scale, out of 10 or 100, says so and is brought down to five). A row without one is skipped, with its row number and why.

## Sync

`Sync\Sources::all()` lists the platforms. Each `Source` says which settings it needs (`fields()`; secret ones are for the CMS to store encrypted), what attribution it asks for (`attribution()`), and whether its reviews may go in the site's own schema (`schemaAllowed()`). `fetch($http, $settings, $since)` yields reviews, newest changes first where the platform allows asking for changes since the last sync, and throws `SyncException` with a message for editors when it can't.

| Platform | Settings | Notes |
| --- | --- | --- |
| Feefo | merchant identifier | No key needed. A review rating the service and products becomes one review each. Feefo documents building product schema from its API, so these may go in schema. |
| REVIEWS.io | store ID, API key (optional) | Company and product reviews. |
| Judge.me | shop domain, private API token | Shopify stores. The API has no replies. |
| Yotpo | app key, secret key | Only reviews changed since the last sync are fetched. |
| Trustpilot | business unit ID, API key, profile address, SKUs | Needs Trustpilot's paid API module. Show the Trustpilot logo and link to the profile wherever its reviews appear. |

Every page is capped (`PagedSource::MAX_PAGES`), so a platform that keeps answering can't keep a sync running for ever.

### Not synced: Google

Google's Business Profile API terms don't allow keeping its reviews for more than 30 days, and the Places API doesn't allow keeping them at all. Show Google reviews with Google's own widget instead.

## Third-party reviews and schema

Google's review snippet guidelines say not to mark up reviews or ratings gathered from other websites, and reviews of a business on its own site aren't eligible. So, in both products:

- Imported and synced reviews keep their source, and are shown with any attribution the platform asks for.
- They're left out of a page's average and schema unless the editor opts a source in, and only a source whose `schemaAllowed()` is true can be.
- Reviews of the business (`isAboutTheBusiness()`) never go in schema.

Importing your own reviews from a platform you're leaving (Reviews' own export, or a final export from Yotpo or Judge.me that you then cancel) is different: those were collected from your customers for your site, and can be counted as your own when imported.
