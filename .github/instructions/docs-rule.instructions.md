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

## Implementation Plan Documents (`docs/*.md`)

**Purpose**: Technical implementation plans for LLM agents to execute. These are NOT for human project management.

### Required Sections

1. **Overview** - What the feature/change does
2. **Current vs New Behavior** - Clear before/after comparison
3. **Phase N: [Feature Name]** - Logical implementation phases
   - Current implementation (code snippets)
   - New implementation (code snippets)
   - Files to modify
   - Tasks (checkbox list)
4. **Testing & Validation** - Test cases checklist
5. **Rollback Plan** - How to revert if issues arise
6. **Version Checklist** - Release preparation tasks

### Writing Style for LLM Plans

- ✅ Include concrete code examples (not pseudocode)
- ✅ Reference exact file paths and method names
- ✅ Use checkbox task lists (`- [ ]`)
- ✅ Explain the "why" behind design decisions
- ✅ Include error handling strategies
- ✅ Document API limits and constraints
- ❌ **NEVER include time estimates** (not relevant for LLM execution)
- ❌ Avoid vague descriptions like "implement feature"
- ❌ Don't include human-focused project management content

### Example Phase Section

```markdown
## Phase 1: Feature Name

**Priority:** HIGH (explain why)

### Current Implementation

File: `classes/ClassName.php`

\`\`\`php
// Current: description of limitation
public function oldMethod()
{
    // actual code from codebase
}
\`\`\`

### New Implementation

\`\`\`php
/**
 * Description of new method
 */
public function newMethod()
{
    // proposed implementation
}
\`\`\`

### Error Handling

- Condition A: action to take
- Condition B: action to take

### Tasks

- [ ] Add `newMethod()` to `ClassName`
- [ ] Update callers in `OtherClass`
- [ ] Add logging for error conditions
```

---

## ARCHITECTURE.md
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