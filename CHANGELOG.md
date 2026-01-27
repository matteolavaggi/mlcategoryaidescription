# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.8.0] - 2026-01-27

### Added
- **Translate Missing Only** - New recovery feature for interrupted jobs
  - Finds categories with primary language content but missing translations
  - Creates translate-only jobs (no OpenAI API calls needed)
  - AJAX actions: `countMissingTranslations`, `createTranslateOnlyJob`
  - Perfect for recovering from jobs killed mid-process

- **Smart Fill-Missing Detection**
  - In "fill_missing" mode with Google Translate enabled
  - Checks if primary language content already exists
  - Skips OpenAI generation if content exists, goes directly to translation
  - Saves API costs and prevents duplicate content generation

- **Language ID from Module Config** - AJAX handlers now use module config as defaults
  - `handleCountMissingTranslations()` reads from `MLCATEGORYAI_PRIMARY_LANGUAGE` and `MLCATEGORYAI_TRANSLATE_LANGUAGES`
  - `handleCreateTranslateOnlyJob()` reads from module config if parameters not provided
  - Prevents hardcoded language ID assumptions (e.g., assuming EN=2, DE=3)
  - Responses now include language ISO codes for clarity

### Changed
- **Per-Category Interleaved Processing** (breaking change for Google Translate mode)
  - OLD flow: Generate ALL OpenAI → Translate ALL (two-phase)
  - NEW flow: For each category: OpenAI → Translate → Next category
  - **Benefit**: If process dies, you have complete categories, not partial translations
  - Categories are now fully processed (all languages) before moving to the next

- **Simplified Progress Calculation**
  - Removed phase-based progress (60% OpenAI + 40% Translate)
  - Now uses simple linear progress across all items

- **categoryHasPrimaryContent() method** - Now queries database directly
  - Fixed PrestaShop language fallback issue (Category object shows default language content for missing languages)
  - Ensures accurate detection of missing translations

### Removed
- Two-phase architecture (phase transitions that could leave jobs stuck)

### Fixed
- Categories left with primary content but no translations after interrupted jobs
- Language ID mismatch when using hardcoded assumptions vs actual database IDs

## [1.7.2] - 2026-01-26

### Added
- **Auto-refresh job status** - Job queue table updates every 10 seconds via AJAX
  - Real-time progress updates without page reload
  - Automatic detection of new, updated, or deleted jobs
- **Restart failed jobs** - New "Resume from failure point" button for failed jobs
  - Resets job status to pending while keeping current position
  - Allows resuming processing from where it stopped
- **Event delegation** for job queue table buttons
  - Properly handles dynamically rendered buttons

### Changed
- Job queue buttons now work via event delegation (more reliable)
- Status badges and progress bars update in real-time

## [1.7.1] - 2026-01-26

### Fixed
- **Orphan process handling** - Processing now stops when job is deleted or marked as failed
  - Added `STATUS_FAILED` check in `processNextBatch()` to terminate orphan processes
  - Previously, running PHP processes would continue after job deletion/failure

### Changed
- **UX/Queue sync** - Failed jobs now appear in job queue UX with delete option
  - `getAllPendingJobs()` and `getAllActiveJobs()` now include failed status
  - Users can see and delete failed jobs from the admin interface

## [1.7.0] - 2026-01-23

### Changed
- **Batched API Calls** - 75% reduction in API calls
  - OpenAI: All fields for a category generated in single JSON request
  - Google Translate: All fields translated in single batch call
  - Uses `response_format: json_object` for guaranteed valid JSON responses
- Job queue now counts by category/language pairs instead of individual fields
- Simplified error handling: category succeeds or fails atomically

### Added
- `generateCategoryBatch()` method for multi-field OpenAI generation
- `translateCategoryBatch()` method for multi-field translation
- `generateJson()` client method with native JSON response format

### Technical
- Prompt templates from DB are combined into structured JSON request
- link_rewrite excluded from AI, generated locally from meta_title
- HTML stripped from non-description fields after translation

## [1.6.2] - 2026-01-23

### Added
- **Generation tracking display** in category selector
  - Shows last generation date (dd/mm format) next to each category name
  - Type indicator: "full" (4 fields) or "partial" generation
  - Red styling for quick visibility
- **Cron lock mechanism** - prevents concurrent cron executions
- **CLI cron script** (`cron-cli.php`) for unlimited execution time
- CLI command shown in admin interface for large jobs

### Fixed
- **Google Translate API key placement** - moved from POST body to URL query parameter (API requirement)
- **CSS not loading** - ensure `displayBackOfficeHeader` hook is properly registered
- Category tree max-height increased to 500px with scroll

### Changed
- **Cron timeout**: Web mode now runs for 5 minutes (was 55 seconds)
- Cron outputs progress every 10 seconds with processing rate
- Preserve `generation_log` and `run_stats` tables on uninstall (historical data)
- Cleaned up debug console.log statements from JavaScript

## [1.6.0] - 2026-01-23

### Added
- **Google Translate Integration** - New two-phase processing mode:
  - Generate content in primary language using OpenAI
  - Automatically translate to other languages using Google Translate API
  - Significantly faster and more cost-effective for multi-language stores
- New `MlCategoryAiTranslator` class for Google Translate API v2 integration
- Translation Settings form section in module configuration
  - Enable/disable Google Translate mode
  - API key configuration (encrypted storage)
  - Primary language selection
  - Target languages checkboxes
- Test Google Translate API button
- ISO code mapping for non-standard PrestaShop codes (gb→en, br→pt, mx→es, etc.)
- HTML format preservation for description field translations
- Two-phase job processing:
  - Phase 1: OpenAI generation for primary language
  - Phase 2: Google Translate for target languages
- Progress bar shows combined progress (60% OpenAI, 40% translation)
- `translateField()` method for single field translation
- `generateLinkRewriteFromMetaTitle()` for local link_rewrite generation

### Changed
- Job creation now supports `gtOptions` parameter for translation settings
- Progress calculation shows phase-aware percentages
- Parallel processing only applies to OpenAI phase
- Enforce field order (meta_title before link_rewrite) for proper translation flow
- Renamed "Test API Connection" to "Test OpenAI Connection"

### Technical
- New database columns in `mlcategoryai_job_queue`:
  - `use_google_translate`, `primary_language_id`, `translate_language_ids`
  - `phase`, `current_translate_lang_index`, `current_translate_position`
- Upgrade script `upgrade-1.6.0.php` for existing installations
- New configuration constants: `CONFIG_GOOGLE_TRANSLATE_*`

## [1.5.0] - 2026-01-22

### Added
- **New category selector UI** with tree-based structure
- Search functionality to filter categories in real-time
- Checkbox-based selection (no more Ctrl+click needed)
- "Select All" and "Deselect All" buttons
- "Expand All" and "Collapse All" buttons for tree navigation
- "Select all subcategories" button on each parent category
- Selected categories counter
- Visual highlighting for search matches

### Changed
- Replaced multi-select dropdown with interactive tree component
- Improved UX for selecting large numbers of categories

## [1.4.3] - 2026-01-22

### Added
- New placeholder `{category_breadcrumb}` - full category path (e.g., "Clothing > Socks > Wool > Merino")
- New placeholder `{category_url}` - full URL to category page
- New placeholder `{shop_url}` - shop base URL
- Updated all default prompts (8 languages) to use breadcrumb for better AI context

### Fixed
- `{site_description}` now correctly reads from ps_meta_lang (index page) instead of non-existent PS_META_DESCRIPTION

### Changed
- Upgrade script resets prompt templates to apply new placeholders

## [1.4.2] - 2026-01-22

### Added
- Job status dashboard with real-time updates
- Cron URL display with setup instructions
- Background (cron) processing mode option
- Auto-detect stuck jobs (no updates for 5+ minutes)
- Delete job functionality
- Menu entry under Catalog in back-office
- CHANGELOG.md for version history
- Build tools in `.github/tools/` (index.sh, zip.sh)
- Consolidated coding standards documentation
- Pre-release validator agent instructions

### Fixed
- Exit at end of cron to prevent Smarty error
- Remove duplicate LIMIT 1 in getActiveJob SQL
- Background mode job creation not auto-resuming
- PrestaShop validator: Context::getContext()->shop->id → Shop::getContextShopID()
- PrestaShop validator: Configuration::get() param order for defaults
- PrestaShop validator: Tools::link_rewrite() → Tools::str2url()
- PrestaShop validator: Tab::active type (bool not int)
- PrestaShop validator: meta_keywords property access with isset() check
- PrestaShop validator: Smarty escaping (intval/floatval before number_format)
- PrestaShop validator: _PS_VERSION_ check in all index.php files

### Changed
- Moved zip.sh and index.sh to .github/tools/ (run in WSL)

## [1.4.1] - 2026-01-22

### Fixed
- SQL syntax in upgrade script
- Remove duplicate LIMIT in upgrade SQL

## [1.4.0] - 2026-01-15

### Added
- Performance stats UI with execution metrics
- File-based debug logging with UI viewer
- API Latency Benchmark tool
- Parallel API Requests switch in config
- Auto-detect max_completion_tokens for newer AI models
- Keep console open after job completion

### Changed
- Improved description prompts with better product context guidelines

### Fixed
- Use max_completion_tokens as default for newer models
- Save prompts JSON decode issues
- Skip temperature param for mini/nano models
- Use temperature 1.0 in benchmark (mini/nano compatibility)
- Make max_tokens optional for newer models
- Calculate execution_time_ms from job timestamps

## [1.3.0] - 2026-01-15

### Added
- Performance metrics table (`mlcategoryai_run_stats`) for execution tracking
- Parallel API processing support

### Changed
- Updated CLI instructions for LLM usage

## [1.2.0] - 2026-01-08

### Added
- Meta keywords field support
- Link rewrite field support
- French and Italian prompt translations for all fields
- Prompt template editor in back-office
- Generation log table (`mlcategoryai_generation_log`)
- Job queue table (`mlcategoryai_job_queue`)
- Prompt template tables (`mlcategoryai_prompt_template`, `mlcategoryai_prompt_template_lang`)

### Removed
- Enable Module toggle (module is always enabled when installed)

## [1.0.1] - 2026-01-08

### Fixed
- Initial bug fixes after first release

## [1.0.0] - 2026-01-07

### Added
- Initial release
- AI-powered category description generation using OpenAI API
- Support for PrestaShop 1.7.x and 8.x
- Multi-language support
- Batch processing for multiple categories
- Configuration panel in back-office
