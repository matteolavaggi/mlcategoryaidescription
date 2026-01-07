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

After installation, go to **Modules > ML Google SEO NoIndex** to:

1. Enable/disable the module globally
2. Choose which page types should be noindexed
3. Add custom URL parameters if needed
4. Optionally enable HTTP header output for stricter crawler compliance

## Best Practices

- **Keep pagination noindex enabled** — This is the most important setting
- **Leave tracking parameters disabled** if you already use canonical tags
- **Monitor Google Search Console** to see crawl improvements after installation

## Support

For questions or customization requests, please contact the module author.

---

**Author**: 2win.agency  
**License**: Commercial - Valid for 1 website per license purchase