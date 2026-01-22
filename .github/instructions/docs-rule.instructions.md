---
description: Guidelines for writing markdown documentation files in projects
globs:
  - "**/*.md"
alwaysApply: true
---
CHANGELOG.md
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