---
description: Index of coding standards for PrestaShop module development
globs:
alwaysApply: false
---

# PrestaShop Module Instructions Index

## 📁 Active Instructions (Simplified)

| File | Purpose | Auto-Apply |
|------|---------|------------|
| [coding-standards](./coding-standards.instructions.md) | PHP, JS, CSS, TPL standards (consolidated) | ✅ Yes |
| [prestashop-ml-license](./prestashop-ml-license.instructions.md) | 2win.agency license headers | ✅ Yes |
| [commit-message](./commit-message.instructions.md) | Conventional commits format | ❌ No |
| [ps-cli-command](./ps-cli-command.instructions.md) | Dev CLI commands (install, upgrade, cache) | ✅ Yes |

## 🔍 Validation Agent (Pre-Production)

| File | Purpose | When to Use |
|------|---------|-------------|
| [ps-validator-agent](./ps-validator-agent.instructions.md) | Full validation checklist & PrestaShop validator | Before marketplace submission |

## 📚 Documentation

| File | Purpose | Auto-Apply |
|------|---------|------------|
| [docs-rule](./docs-rule.instructions.md) | Markdown documentation templates | ✅ Yes (*.md) |

## 🗑️ Deprecated (Consolidated)

These files are now merged into `coding-standards.instructions.md`:
- ~~php-coding-standard.instructions.md~~ → coding-standards
- ~~prestashop-coding-standard.instructions.md~~ → coding-standards  
- ~~javascript-coding-standard.instructions.md~~ → coding-standards
- ~~html-css-template-standard.instructions.md~~ → coding-standards
- ~~presatshop-validation.instructions.md~~ → ps-validator-agent

## Quick Reference

### Development Workflow
1. Create files → License header auto-applied
2. Write code → Coding standards auto-applied
3. Commit → Use conventional commits
4. Ready for release → Run ps-validator-agent

### External Resources
- [PrestaShop Validator](https://validator.prestashop.com/)
- [PrestaShop DevDocs](https://devdocs.prestashop-project.org/)
- [PHP CS Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer)

