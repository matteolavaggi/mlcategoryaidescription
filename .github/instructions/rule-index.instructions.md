---
description: Index of all coding standards and guidelines for PrestaShop module development
globs:
alwaysApply: false
---

# PrestaShop Module Development Rules Index

This index provides a comprehensive guide to all coding standards, guidelines, and best practices for developing PrestaShop modules. Each rule file focuses on a specific aspect of development.

## 📋 Core Coding Standards

### [PHP Coding Standards](./php-coding-standard.mdc)
PSR-2 and Symfony standards for PHP code, including:
- File conventions (UTF-8, LF line endings)
- Code documentation and PHPDoc
- Strict typing requirements
- Deprecation handling

### [JavaScript Coding Standards](./javascript-coding-standard.mdc)
Airbnb JavaScript style guide compliance for module scripts:
- Code style and formatting
- Linting and auto-fixing
- Best practices for frontend code

### [HTML, CSS & Template Standards](./html-css-template-standard.mdc)
Standards for HTML, CSS (Sass), Twig, and Smarty templates:
- Mark Otto's coding standards
- Stylelint configuration
- Template best practices

## 🔒 Security & Validation

### [PrestaShop Validation Rules](./prestashop-validation.mdc)
Technical validation requirements for module submission:
- Module structure compliance
- Security requirements (SQL sanitization, XSS prevention)
- Performance considerations
- Best practices for hooks and AJAX

### [License Headers](./prestashop-ml-license.mdc)
License header requirements for PHP and template files:
- PHP file license headers
- Template file license headers
- Copyright and attribution

## 📝 Version Control & Documentation

### [Commit Message Guidelines](./commit-message.mdc)
Conventional commit format for consistent version control:
- Commit message structure
- Type prefixes (feat, fix, docs, etc.)
- Breaking change indicators
- Examples and rules

## 📝 Development

### [Usage of prestashop cli](./ps-cli-command.instructions.md.mdc)
How to upgrade, install, uninstall module from cli and clear cache
- path of dev prestashop
- command for bash, git bash, powershell
- php version to use

## 🎯 Quick Reference

| Rule | Purpose | Applies To |
|------|---------|-----------|
| PHP Standards | Code style and documentation | `*.php` files |
| JavaScript Standards | Frontend code quality | `*.js` files |
| HTML/CSS Standards | Template and style formatting | `*.tpl`, `*.scss`, `*.css` |
| Validation Rules | Module submission requirements | All module files |
| License Headers | Legal compliance | `*.php`, `*.tpl` files |
| Commit Messages | Version control consistency | Git commits |

## 🚀 Getting Started

1. **Before writing code**: Review the relevant coding standard for your file type
2. **During development**: Use linters and formatters (PHP CS Fixer, ESLint, Stylelint)
3. **Before committing**: Follow commit message guidelines
4. **Before submission**: Ensure all validation rules are met

## 📚 Additional Resources

- [PrestaShop Official Documentation](https://devdocs.prestashop-project.org/)
- [PSR-2 Standard](https://www.php-fig.org/psr/psr-2/)
- [Airbnb JavaScript Style Guide](https://github.com/airbnb/javascript)
- [Conventional Commits](https://www.conventionalcommits.org/)

