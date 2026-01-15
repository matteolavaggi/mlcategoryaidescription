---
description: HTML, CSS, Sass, Twig & Smarty coding standards for PrestaShop modules
globs:
  - "**/*.tpl"
  - "**/*.scss"
  - "**/*.css"
  - "**/*.html"
alwaysApply: false
---

# HTML, CSS (Sass), Twig & Smarty Code Conventions

Reference: https://devdocs.prestashop-project.org/1.7/development/coding-standards/

## Style Guide

HTML, CSS (Sass), Twig and Smarty files MUST follow **Mark Otto's coding standards**.

Mark Otto is the creator of the Bootstrap framework and has established widely-adopted conventions for frontend code.

## Linting and Auto-fixing

### Stylelint Configuration

Stylelint, a stylesheet linter, has been configured in the PrestaShop project to help developers comply with these conventions.

**Requirements**: NodeJS and NPM

### Running Stylelint

To run the linter:
```bash
npm run scss-lint
```

To fix auto-fixable errors:
```bash
npm run scss-fix
```

## CSS/Sass Conventions

### File Organization

- Use Sass for stylesheets when possible
- Organize styles logically (layout, components, utilities)
- Use variables for colors, fonts, and spacing
- Use mixins for reusable styles

### Naming Conventions

- Use kebab-case for class names: `.my-component`
- Use meaningful names that describe purpose
- Avoid abbreviations unless widely understood
- Prefix component classes with module name: `.module-name-component`

### Selectors

- Keep selectors simple and specific
- Avoid deep nesting (max 3 levels)
- Use classes over IDs for styling
- Avoid element selectors in components

### Properties

- Use shorthand properties when appropriate
- Group related properties together
- Use consistent property order
- Include vendor prefixes when needed

## HTML Conventions

### Structure

- Use semantic HTML elements
- Maintain proper nesting and indentation
- Use meaningful class and ID names
- Keep HTML clean and readable

### Attributes

- Use lowercase for attribute names
- Quote all attribute values
- Use data attributes for JavaScript hooks: `data-js-hook`
- Include ARIA attributes for accessibility

### Forms

- Use proper form elements (input, select, textarea)
- Associate labels with inputs using `for` attribute
- Include validation attributes
- Provide clear error messages

## Smarty Template Conventions

### Variable Escaping

**All Smarty variables present in TPL files MUST be escaped** to avoid malicious code injection.

#### For HTML content:
```smarty
{$variable|escape:'htmlall':'UTF-8'}
```

Instead of:
```smarty
{$variable}
```

#### For inline JavaScript:
```smarty
{$variable|escape:'javascript':'UTF-8'}
```

Instead of:
```smarty
{$variable}
```

### Using nofilter

In some cases, you will need to use `{$var nofilter}` to display unescaped content. This is **strongly discouraged** and will be analyzed on a case-by-case basis. It may pose security problems (XSS vulnerability).

### Best Practices

- Always escape variables by default
- Use `nofilter` only when absolutely necessary
- Document why `nofilter` is needed
- Review security implications carefully

## Twig Template Conventions

### Variable Escaping

Similar to Smarty, always escape variables in Twig:

```twig
{{ variable|escape('html') }}
```

### Filters and Functions

- Use Twig filters for data transformation
- Use Twig functions for logic
- Keep templates focused on presentation

## Accessibility

- Use semantic HTML elements
- Include alt text for images
- Use proper heading hierarchy
- Ensure color contrast meets WCAG standards
- Test with screen readers

## Performance

- Minimize CSS file size
- Use CSS classes efficiently
- Avoid inline styles
- Optimize images
- Use CSS Grid and Flexbox for layouts

## Responsive Design

- Use mobile-first approach
- Use CSS media queries appropriately
- Test on multiple screen sizes
- Use flexible layouts
- Ensure touch-friendly interactive elements

