---
description: PHP coding standards for PrestaShop module development (PSR-2 & Symfony)
globs:
  - "**/*.php"
alwaysApply: false
---

# PHP Coding Standards

Reference: https://devdocs.prestashop-project.org/1.7/development/coding-standards/

## General File Conventions

All PHP files MUST:

- Use only **UTF-8 without BOM**
- Use **Unix LF (linefeed)** line endings
- End with a **single blank line**

## Code Documentation

Make sure that classes, attributes and methods are properly documented using doc blocks, regardless of visibility.

### Documentation Guidelines

- **Describe the class's purpose**: If you have a hard time describing what the class is meant for, it may be a sign that it has too many responsibilities and needs to be decomposed.
- **Describe intent, not implementation**: Explain what the method/class is for, not how it performs it—unless that information is important for the developer using it.
- **Document attributes, parameters and return values**: Include both type and semantics. A variable named `$content` might be obviously a string, but we need more context to understand what its purpose is.
- **Waste time describing the obvious**: It is preferable to waste time describing the obvious than to leave the ambiguous open to interpretation. This prevents more bugs than you can imagine.

## PSR-2 & Symfony Standards

PHP files MUST follow the **PSR-2 standard** alongside **Symfony standards**.

Although Yoda conditions are suggested, they are not enforced.

### Using PHP CS Fixer

PHP CS Fixer has been configured for the PrestaShop project to help developers comply with these conventions.

Run it using:
```bash
php ./vendor/bin/php-cs-fixer fix
```

## Documenting Types

Parameters and return values SHOULD be described using PHPDoc as documented by PHPStan, especially when the type cannot be strictly defined using PHP types (e.g., collections, structs, mixed types).

### Example

```php
/**
 * @var string[] Collection of IETF language tag (eg. "en-US")
 */
public $locales;
```

### Array Documentation

Pay particular attention to documenting arrays and array structures:

- Use the **simplified syntax** for collections: `string[]` or `SomeType[]`
- For arrays with relevant indexes (non-sequential): use generics-style annotation: `array<int, string>`
- For associative arrays with known schema: use array shape annotation: `array{'foo': int, "bar": string}`

## Strict Typing

Starting on 1.7.7, all new PHP code should be **strictly typed**.

This means:
- All new methods must specify a type for all parameters and the return type
- All new classes (except interfaces) must enforce type strictness via `declare(strict_types=1)`

### Example

```php
<?php
/** 2007-2025 PrestaShop SA and Contributors... */

declare(strict_types=1);

namespace Foo\Bar;

class MyClass
{
    public function doStuff(string $foo, array $bar): void
    {
    }   
}
```

**Important**: It's crucial that all classes declare type strictness. Since PHP is still inherently weakly typed, if the consuming class does not enforce type strictness, methods will have their parameters silently coerced instead of being type checked.

## Deprecations

Following Symfony conventions, method and class deprecations in PrestaShop must be noted by adding the appropriate PHPDoc as well as a deprecation error.

### Example

```php
<?php

namespace PrestaShop\Awesome\Path;

@trigger_error(
    sprintf(
        '%s is deprecated since version 1.7.8.0 and will be removed in the next major version.',
        MyClass::class
    ),
    E_USER_DEPRECATED
);

/**
 * @deprecated Since 1.7.8.0 and will be removed in the next major.
 */
class MyClass
{
    /**
     * @deprecated Since 1.7.6.0, use AnotherClass::someNewMethod() instead.
     */
    public function someOldMethod()
    {
        @trigger_error(
            sprintf(
                '%s is deprecated since version 1.7.6.0. Use %s instead.',
                __METHOD__,
                AnotherClass::class . '::someNewMethod()'
            ),
            E_USER_DEPRECATED
        );
    }
}
```

### Deprecating Services

If you need to deprecate services, use the `deprecated` key for the service:

```yaml
awesome.path.myclass:
    class: 'PrestaShop\Awesome\Path\MyClass'
    deprecated: 'The "%service_id%" service is deprecated since 1.7.8.0 and will be removed in next major.'
    public: true
```

