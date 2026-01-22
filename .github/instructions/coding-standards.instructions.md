---
description: Consolidated coding standards for PrestaShop module development (PHP, JS, CSS, TPL)
globs:
  - "**/*.php"
  - "**/*.js"
  - "**/*.css"
  - "**/*.scss"
  - "**/*.tpl"
alwaysApply: true
---

# PrestaShop Module Coding Standards

Quick reference for 2win.agency module development. Full docs: https://devdocs.prestashop-project.org/

## PHP Rules (Critical - Validator Enforced)

| Rule | Do | Don't |
|------|-----|-------|
| Array syntax | `$arr = [];` | `$arr = array();` |
| Type cast | `(int) $var` | `(int)$var` |
| Line endings | Unix LF (`\n`) | Windows CRLF |
| PHPDoc spacing | Blank line before `@return` | All tags together |
| After `*/` | Code immediately | Blank line |
| Blank lines | Empty (no spaces) | Spaces on blank lines |

### PHPDoc Format
```php
/**
 * Brief description.
 *
 * @param string $param1 Description
 * @param int    $param2 Description
 *
 * @return bool
 */
```

### Required Patterns
```php
// All PHP files (except CRON/Ajax)
if (!defined('_PS_VERSION_')) {
    exit;
}

// Strict typing (PS 1.7.7+)
declare(strict_types=1);

// Configuration keys - prefix with module name
const CONFIG_EXAMPLE = 'MLMODULE_EXAMPLE';

// Check modules (not deprecated isInstalled)
$module = Module::getInstanceByName('modulename');
if ($module !== false && Module::isEnabled('modulename')) { }

// URL parameters
$value = Tools::getValue('param', 'default');

// Translations
$this->l('Translatable string');
```

### SQL Security
```php
// Strings: pSQL()
$sql = 'WHERE name = "' . pSQL($name) . '"';

// Integers: (int)
$sql = 'WHERE id = ' . (int) $id;

// Arrays: array_map
'IN (' . implode(',', array_map('intval', $ids)) . ')';

// Table/field names: bqSQL()
'FROM `' . _DB_PREFIX_ . bqSQL($table) . '`';
```

## JavaScript Rules

Follow Airbnb style guide. Key points:

```javascript
// Use const/let, not var
const config = {};
let counter = 0;

// Arrow functions for callbacks
items.forEach((item) => { });

// Template literals
const msg = `Hello ${name}`;

// Check DOM exists
const el = document.querySelector('.selector');
if (el) { el.addEventListener('click', handler); }
```

Run linter: `npm run lint-fix`

## CSS/Sass Rules

```scss
// Prefix classes with module name
.mlmodule-component { }

// Max 3 levels nesting
.parent {
  .child {
    .grandchild { }
  }
}

// Use variables
$primary-color: #007bff;
```

Run linter: `npm run scss-fix`

## Smarty Templates (.tpl)

```smarty
{* Always escape variables *}
{$variable|escape:'htmlall':'UTF-8'}

{* JavaScript context *}
{$variable|escape:'javascript':'UTF-8'}

{* Translations *}
{l s='Text' mod='modulename'}

{* Conditional *}
{if isset($var) && $var}...{/if}
```

⚠️ Avoid `{$var nofilter}` - security risk (XSS)

## File Requirements

- UTF-8 without BOM
- Unix LF line endings
- End with single blank line
- `index.php` in every folder (security)
- License header on all PHP/TPL files (see license instructions)
