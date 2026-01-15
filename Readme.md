# ML Category AI Description

**🤖 AI-Powered Category Descriptions for PrestaShop**

Automatically generate SEO-optimized category descriptions, meta titles, meta descriptions, keywords, and friendly URLs using OpenAI or Azure OpenAI.

---

## 🏆 Why Choose This Module?

### The Problem

Writing unique, SEO-optimized descriptions for hundreds of categories is time-consuming and expensive. Many stores have empty category descriptions or duplicate content, hurting SEO rankings.

### The Solution

ML Category AI Description uses artificial intelligence to generate high-quality, unique content for all your categories in seconds. Support for multiple languages means one click generates content for your entire multilingual catalog.

---

## 💰 Business Benefits

| Benefit | Impact |
|---------|--------|
| **Time Savings** | Generate hundreds of descriptions in minutes instead of days |
| **SEO Improvement** | Unique, keyword-rich content improves search rankings |
| **Consistency** | Professional tone across all categories |
| **Multi-language** | One-click generation for all your languages |

### Perfect For:

- 🛒 **Large catalogs** with hundreds of categories
- 🔍 **SEO-focused stores** wanting unique content
- 🌍 **Multilingual shops** needing translations
- ⏰ **Time-strapped merchants** who need quality content fast

---

## ⚡ Key Features

✅ **AI-Powered Generation** — Uses GPT-4, GPT-4o-mini, or Azure OpenAI  
✅ **Batch Processing** — Generate content for multiple categories at once  
✅ **Multi-language Support** — Generate in all your shop languages  
✅ **Multiple Field Types** — Descriptions, meta titles, meta descriptions, keywords, URLs  
✅ **Customizable Prompts** — Edit prompts per language and field type  
✅ **Resume Support** — Pause and resume long-running jobs  
✅ **Fill Missing Mode** — Only generate for empty fields  
✅ **CRON Support** — Automate generation via background tasks  

---

## 🔧 Technical Architecture

### System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     PrestaShop Back Office                       │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │            Module Configuration Page                      │    │
│  │  - Select Categories  - Select Languages  - Select Fields │    │
│  └─────────────────────────────────────────────────────────┘    │
│                              │                                   │
│                              ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │              JavaScript Controller (back.js)             │    │
│  │         Client-side batch orchestration (async)          │    │
│  └─────────────────────────────────────────────────────────┘    │
│                              │ AJAX                              │
│                              ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │          AdminMlCategoryAiAjaxController                 │    │
│  │                PHP AJAX Handler                          │    │
│  └─────────────────────────────────────────────────────────┘    │
│                              │                                   │
│         ┌────────────────────┼────────────────────┐             │
│         ▼                    ▼                    ▼             │
│  ┌─────────────┐    ┌──────────────┐    ┌──────────────┐       │
│  │ JobQueue    │    │ Generator    │    │ Placeholder  │       │
│  │ (state)     │    │ (AI logic)   │    │ (variables)  │       │
│  └─────────────┘    └──────────────┘    └──────────────┘       │
│                              │                                   │
│                              ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │              MlCategoryAiClient                          │    │
│  │         cURL to OpenAI/Azure API (sync)                  │    │
│  └─────────────────────────────────────────────────────────┘    │
│                              │                                   │
└──────────────────────────────│───────────────────────────────────┘
                               ▼
                    ┌───────────────────┐
                    │   OpenAI / Azure  │
                    │   GPT-4, GPT-4o   │
                    └───────────────────┘
```

---

## 🔄 Data Flow & Processing

### 1. Job Creation Phase

**Trigger:** User clicks "Start Generation" button

**Data Collected (SQL):**
```
- Selected category IDs (from form)
- Selected language IDs (from form)  
- Selected field types (description, meta_title, etc.)
- Write mode (overwrite / fill_missing)
```

**Job Record Created:**
```sql
INSERT INTO ps_mlcategoryai_job_queue (
    category_ids,        -- JSON array: [2, 5, 8, 12]
    language_ids,        -- JSON array: [1, 2, 3]
    fields_to_generate,  -- JSON array: ["description", "meta_title"]
    total_items,         -- categories × languages × fields = 24
    current_position,    -- 0 (starting point)
    status              -- "pending"
)
```

### 2. Batch Processing Phase

**Processing Model:** Synchronous sequential with client-side orchestration

```
┌─────────────────────────────────────────────────────────────────┐
│  JavaScript (client)                                            │
│  ┌────────────────────────────────────────────────────────┐    │
│  │ Loop: while (!completed) {                              │    │
│  │   1. AJAX → processJob (batch_size items)              │    │
│  │   2. Wait for response                                  │    │
│  │   3. Update UI progress                                 │    │
│  │   4. 500ms delay                                        │    │
│  │   5. Next batch                                         │    │
│  │ }                                                        │    │
│  └────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

**Batch Size:** Configurable (default: 5 items per AJAX call)

**Per-Item Processing:**
```
For each item in batch:
  1. Load Category object (SQL SELECT)
  2. Check if field is empty (fill_missing mode)
  3. Load prompt template (SQL SELECT with JOIN)
  4. Resolve placeholders (SQL queries for products, etc.)
  5. Send to OpenAI API (cURL, synchronous, ~2-5 seconds)
  6. Clean/sanitize response (Markdown → HTML conversion)
  7. Update Category field (SQL UPDATE)
  8. Log generation (SQL INSERT)
  9. Sleep(request_delay) seconds between requests
```

### 3. API Request Details

**Request Structure:**
```json
{
    "model": "gpt-4o-mini",
    "messages": [
        {
            "role": "user", 
            "content": "<resolved prompt with placeholders + format instructions>"
        }
    ],
    "max_tokens": 1000,
    "temperature": 0.7
}
```

**Payload Size:**
- Request: ~500-2000 bytes (prompt dependent)
- Response: ~1000-4000 bytes (content + metadata)

**Timeouts:**
- Connection: 30 seconds
- Total request: 120 seconds

**Chunking:** None - each item is a single API call

### 4. Data Update Phase

**Category Update (per field):**
```sql
UPDATE ps_category_lang 
SET description = '<generated content>'
WHERE id_category = X AND id_lang = Y
```

**Generation Log:**
```sql
INSERT INTO ps_mlcategoryai_generation_log (
    id_category, id_lang, field_type,
    model_used, tokens_used, status
)
```

**Job Progress Update:**
```sql
UPDATE ps_mlcategoryai_job_queue SET
    current_position = current_position + batch_size,
    processed_items = processed_items + success_count,
    failed_items = failed_items + error_count
WHERE id_job = X
```

---

## ⚡ Performance Characteristics

### Current Implementation

| Aspect | Details |
|--------|---------|
| **Processing Model** | Synchronous, sequential |
| **Parallelism** | None (1 API call at a time) |
| **Batch Size** | Configurable (default: 5 items per AJAX request) |
| **Inter-batch Delay** | 500ms (client-side JavaScript) |
| **Inter-request Delay** | Configurable (0-10 seconds, server-side) |
| **API Timeout** | 120 seconds per request |
| **Client Requirement** | Browser must stay open during processing |

### Estimated Processing Times

| Items | Estimated Time | Notes |
|-------|---------------|-------|
| 10 | ~30-60 seconds | Single category, 2 langs, 5 fields |
| 50 | ~3-5 minutes | Small catalog |
| 200 | ~15-20 minutes | Medium catalog |
| 1000 | ~1-2 hours | Large catalog, use CRON mode |

### Why Sequential Processing?

1. **API Rate Limits** - OpenAI has rate limits (RPM/TPM)
2. **Reliability** - Easier error handling and retry logic
3. **Resource Usage** - Lower memory footprint
4. **Resume Support** - Can pause/resume at any point

### Current Bottlenecks

| Bottleneck | Cause | Impact |
|------------|-------|--------|
| API Latency | ~2-5 seconds per GPT request | Primary slowdown |
| Sequential Processing | No parallel requests | Linear time growth |
| Client-side Orchestration | Browser must stay open | User must wait |
| PHP Timeout | Default 30-60 seconds | Long batches may fail |

### Optimization Settings

| Setting | Recommended | Notes |
|---------|-------------|-------|
| Batch Size | 5-10 | Higher = fewer AJAX calls, but longer per-call |
| Request Delay | 1-2 seconds | Prevents rate limiting |
| Max Tokens | 500-1000 | Lower = faster responses |
| Model | gpt-4o-mini | Faster & cheaper than gpt-4 |

---

## 🚀 Performance Improvement Plan

### Overview

Based on OpenAI API specifications and current module architecture, here's a phased improvement plan organized by component.

---

### 📊 Phase 1: SQL Optimizations

**Goal:** Reduce database round-trips and improve query efficiency.

| Improvement | Current | Proposed | Speed Gain |
|-------------|---------|----------|------------|
| **Bulk Category Loading** | 1 query per category | Load all categories in batch | ~50% fewer queries |
| **Prepared Statements** | Dynamic SQL strings | PDO prepared statements | Faster execution |
| **Index Optimization** | Default indexes | Add composite indexes on (id_category, id_lang, id_shop) | Faster lookups |
| **Batch Updates** | 1 UPDATE per field | Batch multiple UPDATEs in transaction | ~80% fewer queries |
| **Prompt Caching** | Query prompt per request | Cache prompts in memory for job duration | ~30% fewer queries |

**Implementation Details:**

```php
// BEFORE: 1 query per category (N queries)
foreach ($categoryIds as $id) {
    $category = new Category($id, $idLang);
}

// AFTER: Bulk load all categories (1 query)
$sql = 'SELECT * FROM ps_category c
        JOIN ps_category_lang cl ON c.id_category = cl.id_category
        WHERE c.id_category IN (' . implode(',', array_map('intval', $categoryIds)) . ')
        AND cl.id_lang = ' . (int)$idLang;
$categories = Db::getInstance()->executeS($sql);
```

```php
// BEFORE: 1 UPDATE per category field
foreach ($results as $result) {
    Db::getInstance()->update('category_lang', ['description' => $result['content']], ...);
}

// AFTER: Batch UPDATE with transaction
Db::getInstance()->execute('START TRANSACTION');
foreach ($results as $result) {
    // Queue updates
}
Db::getInstance()->execute('COMMIT');
```

**Estimated Impact:** 40-60% reduction in SQL overhead

---

### ⚙️ Phase 2: Processing Optimizations

**Goal:** Improve orchestration and reduce idle time.

| Improvement | Current | Proposed | Speed Gain |
|-------------|---------|----------|------------|
| **Parallel API Calls** | 1 request at a time | 3-5 concurrent requests | 3-5x faster |
| **Server-side Queue** | Client orchestration | PHP background worker | No browser needed |
| **Smarter Batching** | Random order | Group by field type (same prompt) | Better caching |
| **Early Exit** | Process all items | Skip if category unchanged | Variable savings |
| **Streaming Response** | Wait for full response | Process streamed chunks | ~30% faster perceived |

**Parallel Processing Architecture:**

```
Current (Sequential):
  Request 1 ──────────> Response 1
                        Request 2 ──────────> Response 2
                                              Request 3 ──────────> Response 3
  Total: 9 seconds (3 × 3 sec)

Proposed (Parallel):
  Request 1 ──────────> Response 1
  Request 2 ──────────> Response 2
  Request 3 ──────────> Response 3
  Total: 3 seconds (parallel)
```

**PHP curl_multi Implementation:**

```php
// Parallel requests using curl_multi
$multiHandle = curl_multi_init();
$handles = [];

// Add all requests
foreach ($items as $item) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [...]);
    curl_multi_add_handle($multiHandle, $ch);
    $handles[] = $ch;
}

// Execute all in parallel
do {
    curl_multi_exec($multiHandle, $running);
    curl_multi_select($multiHandle);
} while ($running > 0);

// Collect responses
foreach ($handles as $ch) {
    $response = curl_multi_getcontent($ch);
    // Process response
}
```

**Rate Limit Considerations:**
- OpenAI Tier 1: 500 RPM (requests per minute)
- OpenAI Tier 2: 5000 RPM
- Safe parallel limit: 3-5 concurrent requests with 200ms delay

**Estimated Impact:** 3-5x faster processing

---

### 🌐 Phase 3: API Optimizations

**Goal:** Leverage OpenAI API features for speed and cost savings.

| Improvement | Current | Proposed | Benefit |
|-------------|---------|----------|---------|
| **Batch API** | Sync requests | Async batch (24h window) | **50% cost savings** |
| **Streaming** | Wait for complete response | Stream tokens as generated | Faster time-to-first-byte |
| **Prompt Caching** | No caching | Use `prompt_cache_key` | **50% input token cost** |
| **Lower max_tokens** | 1000 tokens | Dynamic based on field type | Faster responses |
| **Temperature 0** | Temperature 0.7 | Temperature 0 for deterministic | Slightly faster |

#### OpenAI Batch API (50% Discount)

For large catalogs (100+ categories), use the Batch API for asynchronous processing at 50% discount:

```
Workflow:
1. Create JSONL file with all requests
2. Upload file to OpenAI
3. Create batch job
4. Poll for completion (within 24 hours)
5. Download results and update categories
```

**JSONL Input Format:**
```jsonl
{"custom_id": "cat-1-lang-1-desc", "method": "POST", "url": "/v1/chat/completions", "body": {"model": "gpt-4o-mini", "messages": [...]}}
{"custom_id": "cat-1-lang-1-meta", "method": "POST", "url": "/v1/chat/completions", "body": {"model": "gpt-4o-mini", "messages": [...]}}
{"custom_id": "cat-2-lang-1-desc", "method": "POST", "url": "/v1/chat/completions", "body": {"model": "gpt-4o-mini", "messages": [...]}}
```

**Benefits:**
- 50% cost reduction
- No rate limit concerns
- Process 50,000 requests per batch
- Up to 200 MB per batch file

#### Prompt Caching

OpenAI automatically caches prompt prefixes. Optimize by using consistent system prompts:

```php
// Use prompt_cache_key for similar requests
$requestData = [
    'model' => 'gpt-4o-mini',
    'messages' => [...],
    'prompt_cache_key' => 'mlcategoryai-' . $fieldType,  // Group by field type
    'prompt_cache_retention' => '24h',  // Extended caching
];
```

**Benefit:** Up to 50% reduction in input token costs for cached prompts

#### Streaming Responses

Enable streaming for faster perceived performance:

```php
$requestData = [
    'model' => 'gpt-4o-mini',
    'messages' => [...],
    'stream' => true,
    'stream_options' => ['include_usage' => true],
];
```

**Benefit:** See tokens as they're generated, ~30% faster time-to-first-byte

#### Optimized max_tokens by Field Type

| Field Type | Current | Optimized | Savings |
|------------|---------|-----------|---------|
| description | 1000 | 800 | 20% |
| meta_title | 1000 | 100 | 90% |
| meta_description | 1000 | 200 | 80% |
| meta_keywords | 1000 | 150 | 85% |

**Estimated Impact:** 30-50% cost reduction, 20-40% faster responses

---

### 📋 Implementation Roadmap

| Phase | Focus | Effort | Impact | Priority |
|-------|-------|--------|--------|----------|
| **1.1** | Bulk category loading | Low | Medium | High |
| **1.2** | Prompt caching in memory | Low | Medium | High |
| **1.3** | Batch UPDATE transactions | Medium | Medium | Medium |
| **2.1** | Parallel API calls (curl_multi) | Medium | High | High |
| **2.2** | Dynamic max_tokens | Low | Medium | High |
| **2.3** | Server-side queue (CRON) | High | High | Medium |
| **3.1** | OpenAI Batch API | High | Very High | Medium |
| **3.2** | Prompt cache key | Low | Medium | Medium |
| **3.3** | Streaming responses | Medium | Low | Low |

---

### 🎯 Expected Results After Optimization

| Metric | Current | After Phase 1 | After Phase 2 | After Phase 3 |
|--------|---------|---------------|---------------|---------------|
| **100 items time** | ~10 min | ~8 min | ~3 min | ~3 min |
| **1000 items time** | ~100 min | ~80 min | ~25 min | 24h (batch) |
| **SQL queries** | ~500 | ~100 | ~100 | ~100 |
| **API cost** | $X | $X | $X | **$0.5X** |
| **Browser required** | Yes | Yes | No | No |

---

## 📊 Database Schema

### Tables Created

```sql
-- Generation history log
ps_mlcategoryai_generation_log
  - id_generation_log (PK)
  - id_category, id_lang, id_shop
  - field_type, model_used, tokens_used
  - status, error_message, generated_at

-- Job queue for batch processing  
ps_mlcategoryai_job_queue
  - id_job (PK)
  - category_ids (JSON), language_ids (JSON)
  - fields_to_generate (JSON), write_mode
  - status, total_items, processed_items, failed_items
  - current_position (for resume support)
  - error_log

-- Customizable prompt templates
ps_mlcategoryai_prompt_template
  - id_prompt_template (PK)
  - field_type, is_active

ps_mlcategoryai_prompt_template_lang
  - id_prompt_template (FK)
  - id_lang
  - prompt_template (TEXT)
```

---

## 🔌 API Configuration

### OpenAI (Default)

```
Provider: openai
Endpoint: https://api.openai.com/v1
Model: gpt-4o-mini (or gpt-4, gpt-4o)
```

### Azure OpenAI

```
Provider: azure
Endpoint: https://YOUR-RESOURCE.openai.azure.com
Model: YOUR-DEPLOYMENT-NAME
```

### Custom/Self-hosted

```
Provider: custom
Endpoint: https://your-api.com/v1
Model: your-model-name
```

---

## 🖥️ Requirements

- PrestaShop 1.7.x - 8.x
- PHP 7.2+ (7.4+ recommended)
- cURL extension enabled
- OpenAI API key or Azure OpenAI access

---

## 📝 License

Valid for 1 website per license purchase.  
© 2010-2026 2win.agency

---

## 🆘 Support

For support, please contact us through PrestaShop Addons or visit [2win.agency](https://2win.agency).
