---
description: PrestaShop Module Validator Agent - Run before marketplace submission
globs:
alwaysApply: false
---

# 🔍 PrestaShop Module Validator Agent

**Purpose**: Comprehensive pre-production validation using PrestaShop's official validator tool and manual checks.

## When to Invoke This Agent

Call this agent when:
- Module is ready for PrestaShop Addons marketplace submission
- Before creating release ZIP
- After major refactoring
- Before merging to main/production branch

---

## 🛠️ Available Tools

Tools are located in `.github/tools/` and **MUST be run in WSL** (not PowerShell):

```bash
# Run in WSL
wsl -e bash -c 'cd "/mnt/c/path/to/module/.github/tools" && ./index.sh'
wsl -e bash -c 'cd "/mnt/c/path/to/module/.github/tools" && ./zip.sh'
```

### index.sh - Add index.php to all folders
```bash
# Adds security index.php to every folder (PrestaShop requirement)
./index.sh
```

### zip.sh - Create release ZIP
```bash
# Auto-detects module name and version from main PHP file
./zip.sh

# Or specify version manually
./zip.sh 1.4.2
```

The ZIP will be created in the parent folder with name `{module}-{version}.zip`.
It automatically excludes: `.git/`, `.github/`, `.vscode/`, `*.sh`, `*.log`, etc.

⚠️ **Important**: Always use WSL to create ZIPs. PowerShell creates backslash paths that fail PrestaShop validator.

---

## Validation Workflow

### Step 1: Run PrestaShop Official Validator

```bash
# Install validator tool (if not installed)
composer global require prestashop/autoindex
composer global require prestashop/php-dev-tools

# Run PHP CS Fixer (PrestaShop config)
php vendor/bin/php-cs-fixer fix --dry-run --diff

# Check for issues
php vendor/bin/php-cs-fixer fix --dry-run --diff --verbose
```

**Online Validator**: https://validator.prestashop.com/
- Upload module ZIP
- Review all errors and warnings
- ALL errors must be fixed; warnings should be addressed

### Step 2: Automated Checks

Run these checks on the module directory:

#### 2.1 Structure Validation
```bash
# Check index.php in all folders
find . -type d ! -path "./vendor/*" -exec sh -c 'test -f "$1/index.php" || echo "Missing: $1/index.php"' _ {} \;

# Check for .htaccess in root
test -f .htaccess || echo "Missing: .htaccess"

# Check for required files
for f in "config.xml" "logo.png" "logo.gif"; do
  test -f "$f" || echo "Missing: $f"
done
```

#### 2.2 PHP Syntax Check
```bash
# Check all PHP files for syntax errors
find . -name "*.php" ! -path "./vendor/*" -exec php -l {} \;
```

#### 2.3 Security Scan
```bash
# Check for _PS_VERSION_ guard (grep for files missing it)
find . -name "*.php" ! -path "./vendor/*" ! -name "index.php" -exec grep -L "_PS_VERSION_" {} \;

# Check for serialize/unserialize (FORBIDDEN)
grep -r "serialize\|unserialize" --include="*.php" . | grep -v vendor

# Check for eval (FORBIDDEN)
grep -r "\beval\s*(" --include="*.php" . | grep -v vendor

# Check for debug statements
grep -rn "var_dump\|print_r\|dump(\|console\.log" --include="*.php" --include="*.js" --include="*.tpl" . | grep -v vendor
```

#### 2.4 SQL Security Check
```bash
# Find potential SQL injection (missing pSQL/intval)
grep -rn "SELECT\|INSERT\|UPDATE\|DELETE" --include="*.php" . | grep -v vendor | grep -v pSQL | grep -v "(int)"
```

#### 2.5 Smarty Escaping Check
```bash
# Find unescaped variables in TPL (potential XSS)
grep -rn '{\$[^}]*}' --include="*.tpl" . | grep -v escape | grep -v nofilter
```

### Step 3: Manual Validation Checklist

#### Module Structure
- [ ] `config.xml` has correct version, author, compatibility
- [ ] Main PHP file matches folder name exactly
- [ ] `ps_versions_compliancy` declared in constructor
- [ ] All folders have `index.php`
- [ ] `.htaccess` prevents direct PHP execution
- [ ] No files outside expected structure

#### Code Quality
- [ ] No `array()` syntax (use `[]`)
- [ ] No `(int)$var` (use `(int) $var` with space)
- [ ] No Windows CRLF line endings
- [ ] No trailing whitespace in comments
- [ ] No blank lines after `*/` before code
- [ ] All PHPDoc has blank line before `@return`
- [ ] No deprecated `Module::isInstalled()` (use `getInstanceByName()`)

#### Security
- [ ] All SQL strings use `pSQL()`
- [ ] All SQL integers use `(int)`
- [ ] All SQL arrays use `array_map('intval', ...)`
- [ ] All SQL table names use `bqSQL()`
- [ ] No `serialize()`/`unserialize()` (use JSON)
- [ ] No `eval()` or similar
- [ ] All Smarty vars escaped with `|escape:'htmlall':'UTF-8'`
- [ ] AJAX/Cron endpoints have token validation
- [ ] AJAX/Cron use front controllers (not standalone PHP)

#### License & Documentation
- [ ] All PHP files have 2win.agency license header
- [ ] All TPL files have 2win.agency license header
- [ ] `docs/` folder contains PDF documentation
- [ ] README.md is complete
- [ ] No external links in module (except docs/support)

#### Functionality
- [ ] Module installs without errors
- [ ] Module uninstalls cleanly (removes all data/tables)
- [ ] Module upgrades correctly
- [ ] No PHP notices/warnings in debug mode
- [ ] Tested on PS 1.7.x AND PS 8.x
- [ ] Works with multiple languages/shops

#### Files to Remove Before ZIP
- [ ] `.git/` folder
- [ ] `.github/` folder
- [ ] `node_modules/`
- [ ] `.DS_Store` files
- [ ] `Thumbs.db` files
- [ ] IDE folders (`.idea/`, `.vscode/`)
- [ ] Test files
- [ ] Log files
- [ ] `.env` files
- [ ] `composer.lock` (keep `composer.json`)

### Step 4: Create Release ZIP

Use the provided tool (handles all exclusions automatically):

```bash
cd .github/tools

# First, ensure all folders have index.php
./index.sh

# Create the ZIP (auto-detects module name + version)
./zip.sh
```

**Manual alternative** (if tools not available):
zip -r "${MODULE_NAME}-${VERSION}.zip" "${MODULE_NAME}/" \
  -x "*.git*" \
  -x "*node_modules*" \
  -x "*.idea*" \
  -x "*.vscode*" \
  -x "*.github*" \
  -x "*__MACOSX*" \
  -x "*.DS_Store" \
  -x "*Thumbs.db"
```

### Step 5: Final Upload Validation

1. Upload to https://validator.prestashop.com/
2. Fix ALL reported errors
3. Address warnings where possible
4. Document any intentional exceptions

## Common Validator Errors & Fixes

| Error | Fix |
|-------|-----|
| `array()` syntax | Replace with `[]` |
| Missing `_PS_VERSION_` check | Add `if (!defined('_PS_VERSION_')) { exit; }` |
| Unsafe SQL | Add `pSQL()`, `(int)`, `bqSQL()` |
| Unescaped Smarty | Add `\|escape:'htmlall':'UTF-8'` |
| Missing index.php | Run autoindex tool |
| CRLF line endings | Convert to LF |
| Debug statements | Remove all `var_dump`, `console.log`, etc. |
| Commented code | Remove unused commented blocks |

## Environment Testing Matrix

| PrestaShop | PHP | Status |
|------------|-----|--------|
| 1.7.6.x | 7.1+ | ⬜ Test |
| 1.7.7.x | 7.2+ | ⬜ Test |
| 1.7.8.x | 7.2+ | ⬜ Test |
| 8.0.x | 7.4+ | ⬜ Test |
| 8.1.x | 8.0+ | ⬜ Test |
| 9.0.x | 8.1+ | ⬜ Test |

## Validation Commands Summary

```bash
# Quick validation pipeline
cd /path/to/module

# 1. Syntax check
find . -name "*.php" ! -path "./vendor/*" -exec php -l {} \; 2>&1 | grep -v "No syntax errors"

# 2. Security check
echo "=== Security Issues ===" && \
grep -rn "serialize\|unserialize\|eval\s*(" --include="*.php" . | grep -v vendor

# 3. Debug check
echo "=== Debug Statements ===" && \
grep -rn "var_dump\|print_r\|console\.log" --include="*.php" --include="*.js" . | grep -v vendor

# 4. Missing escapes
echo "=== Unescaped TPL Vars ===" && \
grep -rn '{\$' --include="*.tpl" . | grep -v escape | head -20

# 5. PS CS Fixer (if available)
php vendor/bin/php-cs-fixer fix --dry-run --diff 2>/dev/null || echo "Run: composer require --dev prestashop/php-dev-tools"
```
