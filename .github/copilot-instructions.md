# ML Google NoIndex - PrestaShop Module Development Guide

## Project Overview
SEO module for PrestaShop 1.7-9.0 that adds `<meta name="robots" content="noindex,follow">` to filtered/paginated pages to optimize Google crawl budget.

## Detailed Standards Reference
See `.github/instructions/` for comprehensive guidelines:
- [rule-index.md](instructions/rule-index.md) - Complete index of all standards
- [php-coding-standard.md](instructions/php-coding-standard.md) - PHP/PSR-2 conventions
- [prestashop-coding-standard.md](instructions/prestashop-coding-standard.md) - PrestaShop-specific patterns
- [prestashop-ml-license.md](instructions/prestashop-ml-license.md) - **License headers (MUST USE)**
- [presatshop-validation.md](instructions/presatshop-validation.md) - Marketplace validation rules
- [commit-message.md](instructions/commit-message.md) - Conventional commits format
- [html-css-template-standard.md](instructions/html-css-template-standard.md) - Template standards

## PHP Coding Standards (CRITICAL)

### PHP CS Fixer Rules - MUST FOLLOW
These rules are enforced by PrestaShop validator. **Never violate them:**

| Rule | Description | Example |
|------|-------------|---------|
| `array_syntax` | Use short array syntax `[]` not `array()` | `$arr = [];` not `$arr = array();` |
| `align_multiline_comment` | PHPDoc lines start with ` * ` (space-asterisk-space) | See license below |
| `no_blank_lines_after_phpdoc` | No blank line between `*/` and code | `*/\nif (...)` not `*/\n\nif (...)` |
| `no_trailing_whitespace_in_comment` | No trailing spaces in comments | No spaces at end of `* @param` lines |
| `no_whitespace_in_blank_line` | Blank lines must be completely empty | No spaces on empty lines |
| `phpdoc_separation` | Blank line between @param group and @return | See example below |
| `line_ending` | Use Unix LF line endings (not CRLF) | `\n` not `\r\n` |
| `cast_spaces` | Space after type casts | `(int) $var` not `(int)$var` |

### PHPDoc Format Example (CORRECT)
```php
/**
 * Description of the function.
 *
 * @param string $param1 Description
 * @param int    $param2 Description
 *
 * @return bool
 */
function myFunction($param1, $param2)
{
```

**Note:** Blank line between `@param` group and `@return` (phpdoc_separation rule).

## License Headers (Required)

### PHP Files (PSR-2 Aligned)
```php
<?php
/**
 * 2010-2025 2win.agency
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 wesite only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please contact us for extra customization service at an affordable price
 *
 * @author    2win.agency
 * @copyright 2010-2025 2win.agency
 * @license   Valid for 1 website (or project) for each purchase of license
 *            International Registered Trademark & Property of 2win.agency
 */
if (!defined('_PS_VERSION_')) {
```

**CRITICAL formatting rules:**
- Every line starts with ` * ` (space + asterisk + space) - **align_multiline_comment**
- `@author`, `@copyright`, `@license` aligned with consistent spacing
- NO blank line between `*/` and `if (!defined...)` - **no_blank_lines_after_phpdoc**
- License line continuation uses `*            ` (aligned with text above)

### PHP Index Files (redirect files)
```php
<?php
/**
 * 2010-2025 2win.agency
 *
 * ... (same license) ...
 *
 * @author    2win.agency
 * @copyright 2010-2025 2win.agency
 * @license   Valid for 1 website (or project) for each purchase of license
 *            International Registered Trademark & Property of 2win.agency
 */
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
```

**Note:** No blank line between `*/` and `header()`.

### TPL Files
```smarty
{*
 * 2010-2025 2win.agency
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 wesite only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please contact us for extra customization service at an affordable price
 *
 * @author    2win.agency
 * @copyright 2010-2025 2win.agency
 * @license   Valid for 1 website (or project) for each purchase of license
 *            International Registered Trademark & Property of 2win.agency
 *}
```

## Architecture

### Core Files
- `mlgooglenoindex.php` - Main module class extending `Module`, contains all logic
- `config.xml` - Module metadata (version, author, compatibility)
- `views/templates/admin/configure.tpl` - Smarty template for back-office config page
- `upgrade/upgrade-X.X.X.php` - Version upgrade scripts

### Key Patterns

**Configuration Constants**: Define all config keys as class constants
```php
const CONFIG_LIVE_MODE = 'MLGOOGLENOINDEX_LIVE_MODE';
const CONFIG_PAGINATION = 'MLGOOGLENOINDEX_PAGINATION';
```

**Module Detection**: Check for third-party modules using `getInstanceByName()` (not deprecated `isInstalled()`)
```php
public function isAmazingFilterEnabled()
{
    $module = Module::getInstanceByName('amazzingfilter');

    return $module !== false && Module::isEnabled('amazzingfilter');
}
```

**Hook Output**: Return HTML strings from `hookDisplayHeader()`, not echo
```php
public function hookDisplayHeader() {
    if ($this->shouldNoIndex()) {
        return '<meta name="robots" content="noindex,follow">' . "\n";
    }
    return '';
}
```

## Development Workflow

### Version Bumps
1. Update version in `mlgooglenoindex.php` (`$this->version`)
2. Update version in `config.xml` (`<version>`)
3. Create upgrade script: `upgrade/upgrade-X.X.X.php`

### Adding New Config Options
1. Add constant: `const CONFIG_NEW = 'MLGOOGLENOINDEX_NEW';`
2. Add to `install()`: `Configuration::updateValue(self::CONFIG_NEW, true);`
3. Add to `uninstall()`: `Configuration::deleteByName(self::CONFIG_NEW);`
4. Add to `getConfigForm()` inputs array
5. Add to `getConfigFormValues()` return array
6. Add logic in `shouldNoIndex()` method

### Testing
```bash
# Verify noindex is NOT on page 1
curl -s "https://site.com/category" | grep -i "noindex"

# Verify noindex IS on page 2+
curl -s "https://site.com/category?page=2" | grep -i "noindex"
```

## Conventions

### PHP
- Use `Tools::getValue()` and `Tools::getIsset()` for URL parameters
- Use `Configuration::get()` / `Configuration::updateValue()` for settings
- Use `$this->l('string')` for translatable strings
- All PHP files must start with `if (!defined('_PS_VERSION_')) { exit; }`
- **Use `[]` short array syntax** - never `array()`
- **Use `(int) $var`** with space after cast - never `(int)$var`
- **Use `Module::getInstanceByName()`** - never deprecated `Module::isInstalled()`
- **Use Unix LF line endings** - never Windows CRLF
- **No trailing whitespace** in comments or blank lines
- **No blank line after `*/`** before code (no_blank_lines_after_phpdoc)

### Templates (Smarty .tpl)
- Use `{l s='text' mod='mlgooglenoindex'}` for translations
- Use `{if isset($var) && $var}` for conditional display
- **2win.agency license header required** (see above)

## Module Configuration Header (Reusable)

Every 2win.agency module MUST include the `header_info.tpl` template in the configuration page. This provides consistent branding and support links.

### Template Location
Copy `views/templates/admin/header_info.tpl` to your new module.

### Integration in getContent()
```php
public function getContent()
{
    $output = '';

    // Handle form submission
    if ((bool) Tools::isSubmit('submitYourModuleModule') == true) {
        $this->postProcess();
        $output .= $this->displayConfirmation($this->l('Settings updated successfully.'));
    }

    // Header info template variables (REQUIRED)
    $this->context->smarty->assign([
        'module_dir' => $this->_path,
        'module_display_name' => $this->displayName,
        'module_description' => $this->description,
        'module_version' => $this->version,
        'documentation_url' => 'https://2win.agency/docs/YOUR_MODULE_NAME',
        'support_url' => 'https://addons.prestashop.com/en/contact-us?id_product=YOUR_PRODUCT_ID',
        'rate_url' => 'https://addons.prestashop.com/en/ratings.php', // optional
    ]);

    // Add header info panel FIRST
    $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/header_info.tpl');

    // Then add your module-specific templates
    $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

    return $output . $this->renderForm();
}
```

### Variables Reference

| Variable | Required | Description |
|----------|----------|-------------|
| `module_display_name` | Yes | Module name shown in header |
| `module_description` | No | Short module description |
| `module_version` | No | Version number displayed |
| `documentation_url` | Yes | Link to module documentation |
| `support_url` | Yes | Link to PrestaShop Addons support |
| `rate_url` | No | Link to rate the module |

### Update Translation Calls
After copying the template, update all `mod='mlgooglenoindex'` to your module's technical name:
```bash
sed -i "s/mod='mlgooglenoindex'/mod='your_module_name'/g" views/templates/admin/header_info.tpl
```

### Commits
Follow conventional commits: `feat:`, `fix:`, `docs:`, `refactor:` (see [commit-message.md](instructions/commit-message.md))

## File Structure Reference
```
mlgooglenoindex/
├── mlgooglenoindex.php      # Main module class
├── config.xml               # Module metadata
├── views/
│   ├── templates/admin/     # Back-office templates
│   ├── css/                 # Stylesheets (back.css, front.css)
│   └── js/                  # Scripts (back.js, front.js)
├── upgrade/                 # Version upgrade scripts
├── sql/                     # Install/uninstall SQL (if needed)
└── translations/            # Translation files
```

## Important: PrestaShop Validation
- Never modify core tables
- All external dependencies must be bundled
- Filter hook execution to relevant pages only
- Keep hooks lightweight for performance
