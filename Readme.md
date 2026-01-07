# ML Google SEO NoIndex

**🚀 Boost Your PrestaShop SEO Performance & Maximize Google Crawl Budget**

Optimize your Google crawl budget and prevent duplicate content issues in your PrestaShop store.

---

## 🏆 Why Choose ML Google SEO NoIndex?

**Stop wasting your Google crawl budget on low-value pages.** Every e-commerce store with filters, pagination, or sorting generates thousands of duplicate URLs that confuse search engines and dilute your SEO power.

### The Hidden SEO Problem Costing You Rankings

Did you know that a typical PrestaShop store with 1,000 products can generate **over 50,000 indexable URLs** from filters alone? Google's crawler has limited time for your site. When it wastes resources on:

- `/shoes?page=1`, `/shoes?page=2`, `/shoes?page=3`...
- `/shoes?color=red`, `/shoes?color=blue`, `/shoes?size=42`...
- `/shoes?order=price&color=red&page=2`...

...your **actual product pages don't get crawled or indexed properly**. This directly impacts your organic traffic and sales.

### The Professional Solution

**ML Google SEO NoIndex** implements Google's recommended `noindex, follow` directive on filtered and paginated pages. This tells search engines:

> *"Don't index this page, but follow the links to discover our real content."*

**Result:** Google focuses 100% of its crawl budget on your money pages — products, categories, and landing pages that actually convert visitors into customers.

---

## 💰 Business Benefits

| Benefit | Impact |
|---------|--------|
| **Improved Crawl Efficiency** | Google indexes your new products faster |
| **No Duplicate Content Penalties** | Avoid ranking dilution across filter variations |
| **Better Organic Rankings** | Consolidated link equity on canonical pages |
| **Faster Product Discovery** | New arrivals appear in search results sooner |
| **Professional SEO Setup** | Enterprise-level configuration in one click |

### Perfect For:

- 🛒 **Large catalogs** (1,000+ products)
- 🔍 **Stores using faceted navigation** (PS Faceted Search, AmazingFilter)
- 📈 **SEO-focused merchants** wanting to maximize organic traffic
- 🏪 **Multi-language/multi-currency stores** with URL variations
- 💼 **Agencies** managing multiple PrestaShop clients

---

## ⚡ Key Features

✅ **One-Click Installation** — Works immediately with smart defaults  
✅ **Granular Control** — Enable/disable noindex per parameter type  
✅ **PS Faceted Search Integration** — Automatic detection and handling  
✅ **AmazingFilter Support** — Full compatibility with /f-* URLs  
✅ **HTTP Header + Meta Tag** — Double protection for all crawlers  
✅ **Custom Parameters** — Add your own filter parameters  
✅ **Zero Performance Impact** — Lightweight, no database queries  
✅ **PrestaShop 1.7 – 9.0** — Future-proof compatibility  

---

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

---

## 📊 See Results in Google Search Console

After installing ML Google SEO NoIndex, monitor your improvements in Google Search Console:

1. **Coverage Report** — Watch "Excluded" pages increase (filtered URLs correctly excluded)
2. **Crawl Stats** — See crawl requests focus on important pages
3. **Index Coverage** — More product pages indexed vs. filter variations
4. **Performance** — Track organic traffic improvements over 2-4 weeks

*Most merchants see measurable crawl efficiency improvements within 1-2 weeks of installation.*

---

## 🤝 Support & Documentation

| Resource | Link |
|----------|------|
| 📖 **Documentation** | [Download PDF Guide](https://drive.google.com/file/d/1WOMUEDDBpbt98AML3TBpcU4Yu9WkMVii/view?usp=sharing) |
| 💬 **Technical Support** | [PrestaShop Addons](https://addons.prestashop.com/) |
| ⭐ **Rate This Module** | [Leave a Review](https://addons.prestashop.com/en/ratings.php) |
| 🛒 **More Modules** | [2win.agency on Addons](https://addons.prestashop.com/en/2_community-developer?contributor=77754) |

---

**Author**: 2win.agency  
**License**: Commercial - Valid for 1 website per license purchase