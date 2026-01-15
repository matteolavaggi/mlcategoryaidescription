# ML Category AI Description - Project Plan

## 📋 Project Overview

**Module Name:** `mlcategoryaidescription`  
**Display Name:** ML Category AI Description  
**Version:** 1.0.0  
**Author:** 2win.agency  
**Compatibility:** PrestaShop 1.7.x, 8.x, 9.x  

A PrestaShop module that integrates OpenAI API (and compatible providers) to automatically generate category descriptions, meta titles, and meta descriptions using AI. Features batch processing, cron execution, and resume capabilities.

---

## 🎯 Core Features

### 1. AI Content Generation
- Generate category descriptions using OpenAI API (or compatible providers)
- Support multiple AI providers (OpenAI, Azure OpenAI, custom endpoints)
- Configurable prompts with dynamic placeholders
- Multi-language support (generates content for all active languages)

### 2. Batch Processing System
- Process multiple categories in batches (configurable batch size)
- AJAX-based processing to avoid PHP timeout
- Cron-based background processing for large catalogs
- Progress tracking with visual feedback in back-office

### 3. Resume & Recovery
- Automatic state saving after each batch
- Resume interrupted operations
- Skip already processed items
- Error logging and retry mechanism

### 4. Generation Tracking
- Dedicated database table for tracking generation history
- Track per-category, per-language generation timestamps
- Track which fields were generated (description, meta_title, meta_description)
- Store generation metadata (model used, prompt hash, etc.)

### 5. Write Modes
- **Full Overwrite:** Replace existing content with AI-generated content
- **Fill Missing Only:** Only generate content for empty fields
- **Selective:** Choose which fields to generate (description, meta_title, meta_description)

### 6. Dynamic Prompt System
- Customizable prompt templates stored in database
- Placeholder system for dynamic content injection
- Preview generated prompts before execution

---

## 🔧 Technical Architecture

### Database Schema

#### Table: `PREFIX_mlcategoryai_generation_log`
```sql
CREATE TABLE IF NOT EXISTS `PREFIX_mlcategoryai_generation_log` (
    `id_generation_log` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_category` INT(11) UNSIGNED NOT NULL,
    `id_lang` INT(11) UNSIGNED NOT NULL,
    `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `field_type` VARCHAR(50) NOT NULL COMMENT 'description|meta_title|meta_description',
    `generated_at` DATETIME NOT NULL,
    `model_used` VARCHAR(100) DEFAULT NULL,
    `prompt_hash` VARCHAR(64) DEFAULT NULL,
    `tokens_used` INT(11) DEFAULT 0,
    `status` VARCHAR(20) NOT NULL DEFAULT 'success' COMMENT 'success|error|pending',
    `error_message` TEXT DEFAULT NULL,
    PRIMARY KEY (`id_generation_log`),
    KEY `idx_category_lang` (`id_category`, `id_lang`),
    KEY `idx_generated_at` (`generated_at`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Table: `PREFIX_mlcategoryai_job_queue`
```sql
CREATE TABLE IF NOT EXISTS `PREFIX_mlcategoryai_job_queue` (
    `id_job` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `job_type` VARCHAR(50) NOT NULL COMMENT 'batch_generation',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending|running|paused|completed|failed',
    `total_items` INT(11) NOT NULL DEFAULT 0,
    `processed_items` INT(11) NOT NULL DEFAULT 0,
    `failed_items` INT(11) NOT NULL DEFAULT 0,
    `category_ids` TEXT NOT NULL COMMENT 'JSON array of category IDs',
    `language_ids` TEXT NOT NULL COMMENT 'JSON array of language IDs',
    `fields_to_generate` VARCHAR(255) NOT NULL COMMENT 'JSON array: description,meta_title,meta_description',
    `write_mode` VARCHAR(20) NOT NULL DEFAULT 'fill_missing' COMMENT 'overwrite|fill_missing',
    `current_position` INT(11) NOT NULL DEFAULT 0,
    `last_processed_id` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `started_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME NOT NULL,
    `error_log` TEXT DEFAULT NULL,
    PRIMARY KEY (`id_job`),
    KEY `idx_status` (`status`),
    KEY `idx_shop` (`id_shop`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Table: `PREFIX_mlcategoryai_prompt_template`
```sql
CREATE TABLE IF NOT EXISTS `PREFIX_mlcategoryai_prompt_template` (
    `id_prompt_template` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `name` VARCHAR(128) NOT NULL,
    `field_type` VARCHAR(50) NOT NULL COMMENT 'description|meta_title|meta_description',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id_prompt_template`),
    KEY `idx_field_type` (`field_type`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Table: `PREFIX_mlcategoryai_prompt_template_lang` (Multilanguage Prompts)
```sql
CREATE TABLE IF NOT EXISTS `PREFIX_mlcategoryai_prompt_template_lang` (
    `id_prompt_template` INT(11) UNSIGNED NOT NULL,
    `id_lang` INT(11) UNSIGNED NOT NULL,
    `prompt_template` TEXT NOT NULL,
    PRIMARY KEY (`id_prompt_template`, `id_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### Configuration Keys

```php
// API Configuration
const CONFIG_API_PROVIDER = 'MLCATEGORYAI_API_PROVIDER';      // openai|azure|custom
const CONFIG_API_KEY = 'MLCATEGORYAI_API_KEY';                // Encrypted API key
const CONFIG_API_ENDPOINT = 'MLCATEGORYAI_API_ENDPOINT';      // Custom endpoint URL
const CONFIG_API_MODEL = 'MLCATEGORYAI_API_MODEL';            // gpt-4o-mini, gpt-4, etc.

// Generation Settings
const CONFIG_BATCH_SIZE = 'MLCATEGORYAI_BATCH_SIZE';          // Items per batch (default: 5)
const CONFIG_WRITE_MODE = 'MLCATEGORYAI_WRITE_MODE';          // overwrite|fill_missing
const CONFIG_ENABLED_FIELDS = 'MLCATEGORYAI_ENABLED_FIELDS';  // JSON: which fields to generate
const CONFIG_MAX_TOKENS = 'MLCATEGORYAI_MAX_TOKENS';          // Max tokens per request
const CONFIG_TEMPERATURE = 'MLCATEGORYAI_TEMPERATURE';        // AI temperature (0.0-2.0)

// Cron Settings
const CONFIG_CRON_ENABLED = 'MLCATEGORYAI_CRON_ENABLED';      // Enable cron processing
const CONFIG_CRON_TOKEN = 'MLCATEGORYAI_CRON_TOKEN';          // Security token for cron URL

// Module State
const CONFIG_LIVE_MODE = 'MLCATEGORYAI_LIVE_MODE';            // Enable/disable module
```

---

### Placeholder System

Placeholders are resolved at **runtime** when generation executes. All data is fetched in the target language.

#### Static Placeholders (Category/Shop Data)
| Placeholder | Description | Example Output |
|-------------|-------------|----------------|
| `{category_name}` | Category name in target language | "Men's Shoes" |
| `{category_description}` | Current category description in target language | "Browse our collection..." |
| `{category_meta_title}` | Current meta title in target language | "Men's Shoes - MyShop" |
| `{category_meta_description}` | Current meta description in target language | "Shop the best..." |
| `{category_meta_keywords}` | Meta keywords in target language | "shoes, men, leather" |
| `{parent_category_name}` | Parent category name in target language | "Footwear" |
| `{site_name}` | Shop name | "MyShop" |
| `{site_description}` | Shop meta description | "Your online store..." |

#### Product Placeholders (Fetched in Target Language)
| Placeholder | Description | Example Output |
|-------------|-------------|----------------|
| `{product_list}` | All products in category (names) | "Product A, Product B..." |
| `{product_count}` | Number of products in category | "42" |
| `{random_products:N}` | N random products from category | "Shoe Model X, Shoe Model Y" |
| `{first_products:N}` | First N products from category | "Product 1, Product 2" |

#### Runtime Language Placeholders (Auto-injected)
| Placeholder | Description | Example Output |
|-------------|-------------|----------------|
| `{language_code}` | Target language ISO code (auto-set at runtime) | "en", "fr", "de" |
| `{language_name}` | Target language full name (auto-set at runtime) | "English", "Français", "Deutsch" |

> **Note:** `{language_code}` and `{language_name}` are automatically injected based on the target language being processed. You don't need to set these manually.

---

## 📁 File Structure

```
mlcategoryaidescription/
├── mlcategoryaidescription.php          # Main module class
├── config.xml                            # Module metadata
├── index.php                             # Security redirect
├── logo.png                              # Module logo (32x32)
├── logo.gif                              # Module logo fallback
│
├── classes/                              # Business logic classes
│   ├── index.php
│   ├── MlCategoryAiClient.php           # AI API client abstraction
│   ├── MlCategoryAiGenerator.php        # Content generation logic
│   ├── MlCategoryAiPlaceholder.php      # Placeholder parser/resolver
│   ├── MlCategoryAiJobQueue.php         # Job queue management
│   ├── MlCategoryAiLogger.php           # Generation logging
│   └── MlCategoryAiPromptTemplate.php   # Prompt template ObjectModel
│
├── controllers/
│   ├── index.php
│   ├── admin/                            # Admin controllers
│   │   ├── index.php
│   │   └── AdminMlCategoryAiController.php  # Main admin controller (optional)
│   └── front/                            # Front controllers
│       ├── index.php
│       ├── ajax.php                      # AJAX handler for batch processing
│       └── cron.php                      # Cron handler for background processing
│
├── sql/                                  # Database scripts
│   ├── index.php
│   ├── install.php                       # Table creation
│   └── uninstall.php                     # Table removal
│
├── upgrade/                              # Upgrade scripts
│   ├── index.php
│   └── upgrade-X.X.X.php                 # Version-specific upgrades
│
├── vendor/                               # Composer dependencies (if needed)
│   └── ... (OpenAI PHP client - optional)
│
├── views/
│   ├── index.php
│   ├── css/
│   │   ├── index.php
│   │   └── back.css                      # Back-office styles
│   ├── js/
│   │   ├── index.php
│   │   └── back.js                       # Back-office JavaScript (AJAX handling)
│   └── templates/
│       ├── index.php
│       └── admin/
│           ├── index.php
│           ├── configure.tpl             # Main configuration page
│           ├── header_info.tpl           # Module header info
│           ├── prompt_editor.tpl         # Prompt template editor
│           ├── batch_runner.tpl          # Batch processing UI
│           ├── generation_log.tpl        # Generation history view
│           └── placeholder_help.tpl      # Placeholder documentation
│
└── translations/                         # Translation files
    └── index.php
```

---

## 🔄 Processing Flow

### AJAX Batch Processing Flow

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   User starts   │────▶│  Create Job in  │────▶│  Return Job ID  │
│  batch process  │     │   job_queue     │     │   to frontend   │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                                                         │
         ┌───────────────────────────────────────────────┘
         ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│  AJAX: Process  │────▶│ Get next batch  │────▶│  Call AI API    │
│   next batch    │     │  from job_queue │     │  for each item  │
└─────────────────┘     └─────────────────┘     └─────────────────┘
         │                                               │
         │              ┌─────────────────┐              │
         │              │  Update category │◀────────────┘
         │              │  Save to log    │
         │              └─────────────────┘
         │                       │
         ▼                       ▼
┌─────────────────┐     ┌─────────────────┐
│  Update job     │────▶│  Return status  │────▶ (Loop until done)
│  progress       │     │  to frontend    │
└─────────────────┘     └─────────────────┘
```

### Cron Processing Flow

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Cron trigger  │────▶│  Find pending/  │────▶│  Process batch  │
│   (every min)   │     │  running jobs   │     │  (same as AJAX) │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                                                         │
                        ┌─────────────────┐              │
                        │  Update job     │◀─────────────┘
                        │  Exit (timeout) │
                        └─────────────────┘
```

---

## 🔐 Security Considerations

1. **API Key Storage:** Encrypt API key using PrestaShop's `_COOKIE_KEY_`
2. **AJAX Security:** Use PrestaShop token validation for all AJAX calls
3. **Cron Security:** Require secure token in cron URL
4. **Input Sanitization:** Use `pSQL()`, `(int)`, `Tools::getValue()` for all inputs
5. **XSS Prevention:** Escape all Smarty variables with `escape:'htmlall':'UTF-8'`
6. **Rate Limiting:** Implement delay between API calls to respect rate limits

---

## 📝 Implementation Phases

### Phase 1: Core Infrastructure (Foundation) ✅
- [x] Rename boilerplate files to `mlcategoryaidescription`
- [x] Create database tables (install.php, uninstall.php)
- [x] Implement configuration keys and settings form
- [x] Create base classes structure
- [x] Implement API client abstraction (MlCategoryAiClient)

### Phase 2: Placeholder System ✅
- [x] Implement MlCategoryAiPlaceholder class
- [x] Add placeholder resolution for all supported placeholders
- [x] Implement language-aware data fetching (fetch data in target language)
- [x] Auto-inject `{language_code}` and `{language_name}` at runtime
- [x] Create placeholder documentation/help panel
- [ ] Test placeholder parsing with various inputs

### Phase 3: Prompt Templates (Multilanguage) ✅
- [x] Implement prompt templates with _lang table (simplified approach)
- [x] Add default prompt templates for each active language on install
- [ ] Create prompt template CRUD interface with multilanguage tabs
- [ ] Implement prompt preview functionality with language selector
- [x] Auto-append language instruction to prompts at runtime

### Phase 4: Content Generation ✅
- [x] Implement MlCategoryAiGenerator class
- [x] Add OpenAI API integration (native cURL)
- [x] Implement write modes (overwrite/fill_missing)
- [x] Add multi-language support with language selection in batch UI
- [x] Implement generation logging

### Phase 5: Batch Processing & AJAX ✅
- [x] Implement MlCategoryAiJobQueue class
- [x] Create AJAX front controller
- [x] Implement batch processing UI with language checkboxes
- [x] Add progress tracking and display
- [x] Implement pause/resume functionality

### Phase 6: Cron Integration ✅
- [x] Create cron front controller
- [x] Implement background processing
- [x] Add timeout handling
- [x] Implement auto-resume for interrupted jobs

### Phase 7: Testing & Polish
- [ ] Test on PrestaShop 1.7.x
- [ ] Test on PrestaShop 8.x
- [ ] Test on PrestaShop 9.x
- [ ] Run PHP CS Fixer
- [ ] Validate with PrestaShop validator
- [ ] Add translations

---

## 📦 Dependencies

### PHP Requirements
- PHP 7.2+ (PrestaShop 1.7 minimum)
- PHP 8.0+ (PrestaShop 8.x/9.x)
- cURL extension
- JSON extension

### External Libraries (Optional - Vendor Folder)
If using a PHP OpenAI client library:
```json
{
    "require": {
        "openai-php/client": "^0.8"
    }
}
```

**Alternative:** Native cURL implementation (no dependencies) - **Recommended for simplicity**

---

## 🔗 Hooks Used

| Hook | Purpose |
|------|---------|
| `displayBackOfficeHeader` | Load admin CSS/JS assets |
| `actionCategoryUpdate` | Optional: trigger regeneration on category update |

---

## 📊 Multilanguage Prompt System

### How It Works

1. **Prompt templates support PrestaShop's native multilanguage system** - Each prompt can be written in multiple languages
2. **At runtime, the system selects the prompt in the target language** - If generating French content, it uses the French prompt
3. **All placeholders are resolved in the target language** - Category names, products, etc. are fetched in that language
4. **Language instruction is auto-appended** - System adds "IMPORTANT: Write your response in {language_name}" to ensure AI responds correctly

### Generation Flow Example

```
┌─────────────────────────────────────────────────────────────────┐
│ Target: Generate French description for category "Chaussures"  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 1. Load prompt template in FRENCH (from _lang table)           │
│    "Rédigez une description SEO pour cette catégorie..."       │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. Resolve placeholders in FRENCH                              │
│    {category_name} → "Chaussures Homme"                        │
│    {first_products:5} → "Mocassin Cuir, Derby Noir, ..."       │
│    {language_name} → "Français"                                │
│    {language_code} → "fr"                                      │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. Auto-append language instruction                            │
│    "\n\nIMPORTANT: You MUST write your response in Français." │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. Send to AI API → Receive French response                    │
└─────────────────────────────────────────────────────────────────┘
```

### Language Selection in Batch Jobs

When starting a batch job, users can:
- **Select specific languages** to generate (e.g., only French and German)
- **Select all active languages** in the shop
- **Process sequentially**: Category 1 (EN) → Category 1 (FR) → Category 2 (EN) → ...

---

## 📊 Default Prompt Templates

> **Note:** These are installed for each active language. Customize per language in back-office.

### Description Prompt (English Version)
```
Write a compelling and SEO-friendly product category description for an e-commerce website.

Category: {category_name}
Parent Category: {parent_category_name}
Website: {site_name}

Products in this category include: {first_products:10}

Requirements:
- 150-300 words
- Include relevant keywords naturally
- Highlight benefits and variety
- Use engaging, professional tone
- Do not mention prices or specific promotions
```

### Description Prompt (French Version)
```
Rédigez une description de catégorie de produits attrayante et optimisée pour le SEO pour un site e-commerce.

Catégorie: {category_name}
Catégorie parente: {parent_category_name}
Site web: {site_name}

Produits dans cette catégorie: {first_products:10}

Exigences:
- 150-300 mots
- Inclure des mots-clés pertinents naturellement
- Mettre en avant les avantages et la variété
- Utiliser un ton engageant et professionnel
- Ne pas mentionner les prix ou promotions spécifiques
```

### Meta Title Prompt (English Version)
```
Generate an SEO-optimized meta title for this e-commerce category page.

Category: {category_name}
Website: {site_name}

Requirements:
- Maximum 60 characters
- Include category name and brand if space allows
- Make it compelling for search results
```

### Meta Description Prompt (English Version)
```
Write an SEO-friendly meta description for this e-commerce category page.

Category: {category_name}
Products available: {product_count}
Sample products: {random_products:5}

Requirements:
- Maximum 155 characters
- Include call-to-action
- Mention variety/selection
```

> **Fallback Behavior:** If no prompt exists for a target language, the system uses the default language prompt and still enforces response language via the auto-appended instruction.

---

## ⚙️ Cron URL Format

```
https://yourshop.com/module/mlcategoryaidescription/cron?token=SECURE_TOKEN&job_id=X
```

Or for processing any pending job:
```
https://yourshop.com/module/mlcategoryaidescription/cron?token=SECURE_TOKEN
```

---

## 📋 Checklist Before Submission

- [ ] All PHP files have correct license header
- [ ] All TPL files have correct license header
- [ ] index.php exists in every folder
- [ ] .htaccess in root folder
- [ ] No debug statements (var_dump, console.log)
- [ ] No commented code blocks
- [ ] All SQL queries use pSQL() / (int) sanitization
- [ ] All Smarty variables escaped
- [ ] PHP CS Fixer passed
- [ ] PrestaShop validator passed
- [ ] Tested on all target PS versions
- [ ] Documentation in docs/ folder

---

## 📝 Notes

1. **Rate Limiting:** OpenAI has rate limits. Implement delays between API calls (configurable, default 1 second).

2. **Token Counting:** Track tokens used for cost estimation and monitoring.

3. **Error Handling:** Graceful degradation - if API fails, log error and continue to next item.

4. **Multishop:** Support PrestaShop multishop - separate configs and jobs per shop.

5. **Memory Management:** For large catalogs, process in small batches to avoid memory issues.

---

*Last Updated: 2026-01-08*
