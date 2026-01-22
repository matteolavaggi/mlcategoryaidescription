---
description: Guidelines for writing markdown documentation files in projects
globs:
  - "**/*.md"
alwaysApply: true
---

# Documentation File Standards

## File Structure

| File | Purpose | Audience |
|------|---------|----------|
| `Readme.md` | Developer reference, technical overview | Developers |
| `Readme_en.md` | **Customer usage instructions** | End users/merchants |
| `CHANGELOG.md` | Version history | Both |

## Readme_en.md (Customer Documentation)

**Purpose**: Clear, non-technical instructions for module users (merchants).

### Required Sections

1. **Module Overview** - What the module does (1-2 paragraphs)
2. **Installation** - Simple step-by-step installation guide
3. **Configuration** - Each setting explained with:
   - What it does
   - Recommended values
   - Where to find API keys/credentials
4. **Usage Guide** - Step-by-step workflow with screenshots descriptions
5. **Features** - Feature list with brief explanations
6. **Troubleshooting** - Common issues and solutions
7. **Support** - Contact information

### Writing Style for Customers

- ✅ Use simple, clear language (no jargon)
- ✅ Step-by-step numbered lists
- ✅ Use screenshots or describe UI elements clearly
- ✅ Explain WHY settings matter, not just WHAT they do
- ✅ Provide recommended values for beginners
- ❌ Avoid technical details (SQL, code, architecture)
- ❌ Avoid developer-focused content

### Example Configuration Section

```markdown
## API Configuration

### API Provider
Select your AI provider:
- **OpenAI** (recommended) - Uses ChatGPT models
- **Azure OpenAI** - For enterprise Azure users
- **Custom** - Self-hosted or alternative APIs

### API Key
Your OpenAI API key. Get it from:
1. Go to https://platform.openai.com/api-keys
2. Click "Create new secret key"
3. Copy and paste the key here

💡 **Tip**: Keep your API key secret. Never share it publicly.
```

---

## CHANGELOG.md
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- New features that have been added

### Changed
- Changes in existing functionality

### Deprecated
- Soon-to-be removed features

### Removed
- Features that have been removed

### Fixed
- Bug fixes

### Security
- Security improvements

## [1.0.0] - YYYY-MM-DD

### Added
- Initial release
- Core functionality

---
ARCHITECTURE.md
# Architecture

## System Overview
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend/UI   │───▶│   Backend/API   │───▶│    Database     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## Component Flow
```
User Input
    │
    ▼
┌─────────────┐
│ Controller  │
└─────────────┘
    │
    ▼
┌─────────────┐
│  Service    │
└─────────────┘
    │
    ▼
┌─────────────┐
│ Repository  │
└─────────────┘
    │
    ▼
┌─────────────┐
│  Database   │
└─────────────┘
```

## Data Flow
1. **Input Layer**: User interactions and external requests
2. **Processing Layer**: Business logic and data transformation
3. **Storage Layer**: Data persistence and retrieval
4. **Output Layer**: Response formatting and delivery

## Key Components
- **Component A**: Brief description of purpose and responsibility
- **Component B**: Brief description of purpose and responsibility
- **Component C**: Brief description of purpose and responsibility

## Technology Stack
```
┌─────────────────────────────────────┐
│            Frontend                 │
│  [Technology/Framework Name]        │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│            Backend                  │
│  [Technology/Framework Name]        │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│            Database                 │
│  [Database Technology]              │
└─────────────────────────────────────┘
```