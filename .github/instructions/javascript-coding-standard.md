---
description: JavaScript coding standards for PrestaShop module development (Airbnb style guide)
globs:
  - "**/*.js"
alwaysApply: false
---

# JavaScript Coding Standards

Reference: https://devdocs.prestashop-project.org/1.7/development/coding-standards/

## Style Guide

JavaScript files MUST follow the **Airbnb Javascript style guide**.

Reference: https://github.com/airbnb/javascript

## Linting and Auto-fixing

Starting from PrestaShop 1.7.7, you can run the linter to help you comply with these coding standards:

```bash
npm run lint-fix
```

This command will automatically fix most style violations in your JavaScript files.

## Key Principles

The Airbnb style guide emphasizes:

- **Consistency**: Maintain consistent code style across the project
- **Readability**: Write code that is easy to understand and maintain
- **Best practices**: Follow modern JavaScript conventions and patterns
- **Performance**: Write efficient code that doesn't impact page load times

## Common Rules

### Variable Declaration

- Use `const` by default
- Use `let` if you need to reassign
- Avoid `var`

### Arrow Functions

- Use arrow functions for callbacks
- Use regular functions for methods

### Object and Array Literals

- Use shorthand syntax when possible
- Use destructuring for cleaner code

### Strings

- Use single quotes for strings
- Use template literals for string interpolation

### Comments

- Write meaningful comments
- Avoid obvious comments
- Use JSDoc for functions and classes

## File Structure

- One main export per file when possible
- Group related functionality together
- Keep files focused and maintainable

## Module-Specific Considerations

When writing JavaScript for PrestaShop modules:

1. **Avoid global scope pollution**: Use modules or namespaces
2. **Handle DOM safely**: Check for element existence before manipulation
3. **Use event delegation**: For dynamically added elements
4. **Minimize external dependencies**: Keep module size small
5. **Test in multiple browsers**: Ensure compatibility

## Performance Tips

- Minimize DOM manipulation
- Use event delegation for multiple elements
- Cache DOM queries
- Avoid synchronous operations
- Use async/await for asynchronous code

## Security Considerations

- Sanitize user input
- Avoid `eval()` and similar functions
- Use Content Security Policy (CSP) compatible code
- Escape output when inserting into DOM

