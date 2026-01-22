# 2win.agency PrestaShop Module Development Guide

## Project Overview
PrestaShop module development for 2win.agency (PS 1.7-9.0).

## Instructions Reference

| File | Purpose | Auto-Apply |
|------|---------|------------|
| [coding-standards](instructions/coding-standards.instructions.md) | PHP, JS, CSS, TPL consolidated standards | ✅ |
| [prestashop-ml-license](instructions/prestashop-ml-license.instructions.md) | License headers (REQUIRED) | ✅ |
| [ps-cli-command](instructions/ps-cli-command.instructions.md) | CLI for install/upgrade/cache | ✅ |
| [commit-message](instructions/commit-message.instructions.md) | Conventional commits | ❌ |
| [ps-validator-agent](instructions/ps-validator-agent.instructions.md) | **Pre-production validation** | ❌ (manual) |

## Quick Reference

### PHP Critical Rules
```php
// Array syntax
$arr = [];  // ✅ not array()

// Type cast
(int) $var  // ✅ space after cast

// Module check (not deprecated isInstalled)
$module = Module::getInstanceByName('name');

// SQL security
pSQL($string);  (int) $number;  bqSQL($table);

// All files except cron/ajax
if (!defined('_PS_VERSION_')) { exit; }
```

### Smarty TPL
```smarty
{$var|escape:'htmlall':'UTF-8'}
{l s='Text' mod='modulename'}
```

### Development Workflow
1. **Code** → Standards auto-applied
2. **Commit** → `feat:`, `fix:`, `docs:`, `refactor:`
3. **Test** → `php bin/console prestashop:module upgrade modulename`
4. **Release** → Run `ps-validator-agent` checklist

## Module Structure
```
modulename/
├── modulename.php      # Main class
├── config.xml          # Metadata
├── classes/            # Business logic
├── controllers/        # Admin/Front controllers
├── views/templates/    # Smarty TPL
├── views/css/js/       # Assets
├── sql/                # Install/uninstall SQL
├── upgrade/            # Version scripts
└── .github/
    ├── tools/          # Build tools (index.sh, zip.sh)
    └── instructions/   # Coding standards
```

## Build Tools

Located in `.github/tools/` - **run in WSL** (not PowerShell):

```bash
# Run via WSL
wsl -e bash -c 'cd "/mnt/c/.../module/.github/tools" && ./index.sh'
wsl -e bash -c 'cd "/mnt/c/.../module/.github/tools" && ./zip.sh'
```

⚠️ PowerShell creates backslash paths that fail PrestaShop validator.

## Validation Reminder
Before marketplace submission, invoke **ps-validator-agent** or use:
- https://validator.prestashop.com/
- `php vendor/bin/php-cs-fixer fix --dry-run`
