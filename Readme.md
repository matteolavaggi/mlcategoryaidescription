# ML Google SEO NoIndex

Optimize your Google crawl budget and prevent duplicate content issues in your PrestaShop store.

## What does this module do?

When Google crawls your online store, it has a limited "crawl budget" — the number of pages it will scan before moving on. If Google wastes time crawling filtered, paginated, or sorted versions of the same category page, your important product pages may not get indexed properly.

**ML Google SEO NoIndex** tells Google to skip indexing these low-value pages while still following the links on them. This means:

- ✅ Your main category and product pages get indexed
- ✅ Filtered/sorted variations are ignored by Google
- ✅ No duplicate content penalties
- ✅ Better use of your crawl budget

## Which pages are affected?

The module can add "noindex" to pages with:

| Page Type | Example URLs |
|-----------|--------------|
| **Pagination** | `/category?page=2`, `/category?p=3` |
| **Sorting** | `/category?order=price`, `/category?orderby=name` |
| **Currency changes** | `/product?id_currency=2` |
| **Search results** | `/search?s=keyword` |
| **Price filters** | `/category?price_min=10&price_max=50` |
| **Items per page** | `/category?n=48` |
| **PS Faceted Search filters** | Attribute and feature filter combinations |
| **AmazingFilter** | `/category/f-color-blue/` |
| **Tracking parameters** | `?utm_source=google`, `?gclid=...` (optional) |

## Why is this important for SEO?

### The Problem
Without this module, Google may index thousands of nearly identical pages:
- `/shoes?page=1`
- `/shoes?page=2`  
- `/shoes?order=price`
- `/shoes?order=name&page=2`
- `/shoes?color=red&size=42`

This creates **duplicate content** and wastes your crawl budget on pages that don't bring value.

### The Solution
This module adds a simple instruction to these pages telling Google:
> "Don't add this page to your search results, but feel free to follow the links here."

Your main pages remain indexed, and Google focuses its resources where they matter.

## Compatibility

- **PrestaShop**: 1.7.x – 9.0.x
- **PS Faceted Search**: Fully supported
- **AmazingFilter**: Fully supported

## Configuration

After installation, go to **Modules > Module Manager**, search for "ML Google SEO NoIndex" and click **Configure**.

### General Settings

| Option | Description | Default |
|--------|-------------|---------|
| **Enable Module** | Master switch to turn on/off all noindex functionality | Enabled |
| **Use HTTP Header** | Also sends `X-Robots-Tag: noindex, follow` HTTP header in addition to the meta tag. Some crawlers prefer headers over meta tags. | Disabled |

### Standard PrestaShop Parameters

| Option | What it does | Default |
|--------|--------------|---------|
| **Pagination** | Adds noindex to paginated pages (`?page=2`, `?p=3`, etc.) | Enabled |
| **Order & Sort** | Adds noindex when products are sorted (`?order=price`, `?orderby=name`) | Enabled |
| **Currency** | Adds noindex when currency is changed (`?id_currency=2`) | Enabled |
| **Search Results** | Adds noindex to search result pages (`?s=keyword`, `?search_query=`) | Enabled |
| **Price Range Filters** | Adds noindex to price-filtered pages (`?price_min=10&price_max=50`) | Enabled |
| **Items Per Page** | Adds noindex when items per page is changed (`?n=48`) | Enabled |

### Filter Module Integration

These options appear automatically when the corresponding module is installed:

| Option | When it appears | What it does |
|--------|-----------------|--------------|
| **PS Faceted Search** | When `ps_facetedsearch` is active | Adds noindex to attribute/feature filter combinations |
| **AmazingFilter** | When `amazzingfilter` is active | Adds noindex to AmazingFilter URLs (`/f-color-blue/`, `?af=`) |

### Advanced Options

| Option | Description | Default |
|--------|-------------|---------|
| **Tracking Parameters** | Adds noindex to URLs with UTM or ad platform parameters (`utm_source`, `gclid`, `fbclid`, etc.). **Disable this if you already use canonical tags** — they handle tracking parameters better. | Disabled |
| **Custom Parameters** | Add your own URL parameters that should trigger noindex. Enter one parameter per line, without `?` or `=`. Example: `my_custom_filter` | Empty |

### Recommended Configuration

For most stores, the default settings work well:

1. ✅ Keep **Enable Module** on
2. ✅ Keep all **Standard Parameters** enabled
3. ✅ Enable **PS Faceted Search** or **AmazingFilter** if you use them
4. ❌ Leave **Tracking Parameters** disabled (canonical tags handle these better)
5. ➕ Add any custom filter parameters your theme uses

## Best Practices

- **Keep pagination noindex enabled** — This is the most important setting
- **Leave tracking parameters disabled** if you already use canonical tags
- **Monitor Google Search Console** to see crawl improvements after installation

## Support

For questions or customization requests, please contact the module author.

---

**Author**: 2win.agency  
**License**: Commercial - Valid for 1 website per license purchase