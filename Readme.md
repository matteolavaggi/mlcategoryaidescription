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

Based on OpenAI API specifications and critical analysis of the current architecture, here's the revised improvement plan with honest assessments.

---

### ❌ Phase 1: SQL Optimizations (DEPRIORITIZED)

**Reality Check:** SQL operations take ~5-10ms per item. API calls take ~3000ms per item. SQL is NOT the bottleneck.

| Proposed Improvement | Assessment | Verdict |
|---------------------|------------|---------|
| **Bulk Category Loading** | PrestaShop ObjectModel requires individual loading for `update()` method | ❌ Skip |
| **Prepared Statements** | PrestaShop Db class doesn't support natively | ❌ Skip |
| **Batch Transactions** | Already using direct SQL. Saves ~1ms per item | ⚠️ Low priority |
| **In-memory Prompt Cache** | Could cache templates for job duration | ⚠️ Marginal gain |

**Conclusion:** Focus on the real bottleneck - API latency.

---

### ✅ Phase 2: Processing Optimizations (HIGH PRIORITY)

**Goal:** Reduce total time by parallelizing API calls.

#### 🔥 Priority 1: Parallel API Calls with curl_multi

**Analysis:**
- Current: 5 items × 3 seconds each = 15 seconds per batch
- With curl_multi: 5 items in parallel = 3 seconds per batch
- **Speedup: 5x faster**

| Question | Answer |
|----------|--------|
| Rate limit safe? | ✅ Yes - OpenAI Tier 1 = 500 RPM, 5 parallel = 300/min |
| Memory impact? | ✅ Minimal - ~10KB for 5 handles |
| Error handling? | ⚠️ More complex - handle partial failures |
| PHP timeout risk? | ✅ Safe - parallel runs in same time as single |

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

#### ⚠️ Priority 2: Server-side Queue (Future Feature)

| Question | Answer |
|----------|--------|
| Better than client-side? | ✅ Browser can close |
| CRON availability? | ⚠️ Shared hosting may limit |
| Progress visibility? | ⚠️ Needs polling |
| Complexity? | ⚠️ Medium-High |

**Verdict:** Good for large catalogs (1000+), but current approach works for most. Defer to future version.

---

### ✅ Phase 3: API Optimizations (REVISED)

**Critical Assessment of Each Feature:**

#### 🔥 Priority 1: Prompt Caching (prompt_cache_key)

| Question | Answer |
|----------|--------|
| Available? | ✅ Yes, current API |
| How it works? | OpenAI caches identical prompt prefixes |
| Our prompts similar? | ✅ Yes - same template, different category names |
| Implementation effort? | ✅ Very low - add one parameter |
| Savings? | 25-50% on input tokens |

```php
$requestData = [
    'model' => 'gpt-4o-mini',
    'messages' => [...],
    'prompt_cache_key' => 'mlcategoryai-' . $fieldType,
    'prompt_cache_retention' => '24h',
];
```

**Verdict:** ✅ **IMPLEMENT NOW** - Low effort, high savings.

---

#### ⚠️ Priority 2: Dynamic max_tokens

**Reality Check:** max_tokens is a CAP, not a speed setting.

| Question | Answer |
|----------|--------|
| Does lower max_tokens = faster? | ❌ No - speed depends on ACTUAL tokens generated |
| Does it save money? | ❌ No - you're charged for actual output, not max |
| What's the benefit? | ✅ Safety - prevents runaway responses |

**Safe Values by Field Type:**

| Field | Actual Output | Safe max_tokens |
|-------|---------------|-----------------|
| description | 200-400 tokens | 500 |
| meta_title | 10-20 tokens | 50 |
| meta_description | 30-50 tokens | 100 |

**Verdict:** ⚠️ Implement for **safety**, not speed.

---

#### ❌ Skip: Streaming

| Question | Answer |
|----------|--------|
| Does it speed up total time? | ❌ No - same compute time |
| When is it useful? | Real-time display of generation |
| Our use case? | Batch processing - user doesn't see tokens |
| Complexity? | High - SSE handling, chunked parsing |

**Verdict:** ❌ **SKIP** - Adds complexity with zero benefit for batch processing.

---

#### ⚠️ Skip for Now: OpenAI Batch API

| Question | Answer |
|----------|--------|
| Cost savings? | ✅ 50% discount |
| Processing time? | ❌ Up to 24 hours |
| User expectation? | Users want instant results |
| When useful? | Very large catalogs (1000+) |

**Verdict:** ⚠️ **NICHE** - Offer as optional "Economy Mode" for cost-conscious users with large catalogs.

---

### 📋 Revised Implementation Roadmap

Based on critical analysis, here's the corrected priority order:

| Priority | Feature | Effort | Impact | Status |
|----------|---------|--------|--------|--------|
| **🔥 1** | Parallel curl_multi | Medium | **5x faster** | To implement |
| **🔥 2** | prompt_cache_key | Low | **25-50% cost savings** | To implement |
| **3** | Dynamic max_tokens per field | Low | Safety | To implement |
| *Future* | Server-side CRON queue | High | UX improvement | v2.0 |
| *Future* | Batch API "Economy Mode" | High | Cost option | v2.0 |

### ❌ Removed from Plan

| Feature | Why Removed |
|---------|-------------|
| SQL Bulk Loading | SQL is 5ms vs API 3000ms - not the bottleneck |
| Prepared Statements | PrestaShop doesn't support natively |
| Streaming | No benefit for batch processing |

---

### 🎯 Expected Results After Implementation

| Metric | Current | After curl_multi + cache |
|--------|---------|--------------------------|
| **100 items time** | ~10 min | **~2 min** |
| **API cost** | $X | **~$0.70X** (30% savings) |
| **Browser required** | Yes | Yes (CRON in v2.0) |

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
