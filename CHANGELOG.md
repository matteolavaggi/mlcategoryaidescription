# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- New placeholder `{category_breadcrumb}` - full category path (e.g., "Clothing > Socks > Wool > Merino")
- New placeholder `{category_url}` - full URL to category page
- New placeholder `{shop_url}` - shop base URL
- Updated all default prompts (8 languages) to use breadcrumb for better AI context

### Fixed
- `{site_description}` now correctly reads from ps_meta_lang (index page) instead of non-existent PS_META_DESCRIPTION

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
