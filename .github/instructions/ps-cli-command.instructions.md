---
description: PrestaShop CLI commands for module development and testing
globs:
alwaysApply: true
---

# PrestaShop Module CLI Commands

## Module Information

| Property | Value |
|----------|-------|
| **Module Name** | `mlcategoryaidescription` |
| **Module Path (Dev)** | `C:\Users\Pineapple\Documents\Visual Studio Code\dev.prestashop\modules\mlcategoryaidescription` |
| **PS8 Path** | `D:\FTP\local\ps8.local` |
| **PS 1.7 Path** | `D:\FTP\local\www.speedmypresta.local\ps17` |

---

## 🤖 LLM Instructions

When executing PrestaShop module commands:

1. **Use WSL for shell scripts** (`.sh` files) - run via `wsl -e bash -c 'command'`
2. **Use PowerShell for PHP commands** (module install/upgrade/cache)
3. **Use absolute paths** with proper escaping for the detected shell
4. **Run as Administrator** when creating symlinks on Windows
5. **Always clear cache** after install/upgrade operations

### Shell Usage Rules

| Task | Shell | Command Format |
|------|-------|----------------|
| Shell scripts (.sh) | **WSL** | `wsl -e bash -c 'cd "/mnt/c/..." && ./script.sh'` |
| PHP commands | PowerShell | `cd D:\FTP\local\ps8.local; php bin/console ...` |
| Git commands | Either | Works in both |

### Path Conversion (Windows → WSL)

| Windows Path | WSL Path |
|--------------|----------|
| `C:\Users\...` | `/mnt/c/Users/...` |
| `D:\FTP\...` | `/mnt/d/FTP/...` |

---

## 🆕 Version & Branch Workflow

**CRITICAL RULE**: Branch name MUST match module version. When you update the module version, you MUST create a new branch.

### Workflow Steps

1. **Update CHANGELOG.md** - Add new version entry with changes
2. **Update module version** - In `mlcategoryaidescription.php` and `config.xml`
3. **Create upgrade script** - If database changes needed
4. **Create new branch** - Branch name = version number (e.g., `1.4.2`)
5. **Commit and push** - With conventional commit message

### Files to Update for Each Version

| File | Property to Update |
|------|-------------------|
| `CHANGELOG.md` | Add `## [X.Y.Z] - YYYY-MM-DD` section |
| `mlcategoryaidescription.php` | `$this->version = 'X.Y.Z';` |
| `config.xml` | `<version><![CDATA[X.Y.Z]]></version>` |
| `upgrade/upgrade-X.Y.Z.php` | Create if DB changes needed |

### Create New Version Branch (Git Bash/WSL)

```bash
VERSION="1.4.2"

# 1. Update CHANGELOG.md (add new version section at top)

# 2. Update version in module files
sed -i "s/\$this->version = '[0-9.]*';/\$this->version = '$VERSION';/" mlcategoryaidescription.php
sed -i "s/<version><!\[CDATA\[[0-9.]*\]\]>/<version><![CDATA[$VERSION]]>/" config.xml

# 3. Create upgrade script if needed
# cp upgrade/upgrade-1.4.1.php upgrade/upgrade-$VERSION.php

# 4. Create new branch matching version
git checkout -b $VERSION

# 5. Commit all changes
git add -A && git commit -m "chore: release version $VERSION"

# 6. Push branch
git push -u origin $VERSION
```

### Create New Version Branch (PowerShell)

```powershell
$VERSION = "1.4.2"

# 1. Update CHANGELOG.md (add new version section at top)

# 2. Update version in module files
(Get-Content mlcategoryaidescription.php) -replace "\`$this->version = '[0-9.]+';", "`$this->version = '$VERSION';" | Set-Content mlcategoryaidescription.php
(Get-Content config.xml) -replace '<version><!\[CDATA\[[0-9.]+\]\]>', "<version><![CDATA[$VERSION]]>" | Set-Content config.xml

# 3. Create new branch matching version
git checkout -b $VERSION

# 4. Commit all changes
git add -A; git commit -m "chore: release version $VERSION"

# 5. Push branch
git push -u origin $VERSION
```

### Version Bump Checklist

- [ ] Update CHANGELOG.md with new version and changes
- [ ] Update `$this->version` in main PHP file
- [ ] Update `<version>` in config.xml  
- [ ] Create upgrade script if DB changes needed
- [ ] Create new branch with version number as name
- [ ] Commit with message `chore: release version X.Y.Z`
- [ ] Push branch to origin

### ⚠️ Common Mistakes to Avoid

- ❌ Updating module version without creating new branch
- ❌ Branch name different from module version
- ❌ Forgetting to update CHANGELOG.md
- ❌ Committing version changes to wrong branch

---

## 🪟 Windows PowerShell Commands

### Variables (set these first)

```powershell
$MODULE_NAME = "mlcategoryaidescription"
$DEV_PATH = "C:\Users\Pineapple\Documents\Visual Studio Code\dev.prestashop\modules\mlcategoryaidescription"
$PS8_ROOT = "D:\FTP\local\ps8.local"
$PS17_ROOT = "D:\FTP\local\www.speedmypresta.local\ps17"
```

### Create Symlink (PrestaShop 8.x)

```powershell
# Must run as Administrator
Remove-Item -LiteralPath "$PS8_ROOT\modules\$MODULE_NAME" -Force -Recurse -ErrorAction SilentlyContinue
New-Item -ItemType SymbolicLink -Path "$PS8_ROOT\modules\$MODULE_NAME" -Target $DEV_PATH
```

### Create Symlink (PrestaShop 1.7.x)

```powershell
# Must run as Administrator
Remove-Item -LiteralPath "$PS17_ROOT\modules\$MODULE_NAME" -Force -Recurse -ErrorAction SilentlyContinue
New-Item -ItemType SymbolicLink -Path "$PS17_ROOT\modules\$MODULE_NAME" -Target $DEV_PATH
```

### Module Install/Uninstall/Upgrade

```powershell
# Navigate to PrestaShop root
cd $PS8_ROOT

# Install module
php bin/console prestashop:module install $MODULE_NAME

# Uninstall module
php bin/console prestashop:module uninstall $MODULE_NAME

# Upgrade module (applies upgrade scripts)
php bin/console prestashop:module upgrade $MODULE_NAME

# Reset module (uninstall + install)
php bin/console prestashop:module uninstall $MODULE_NAME; php bin/console prestashop:module install $MODULE_NAME
```

### Clear Cache

```powershell
# PrestaShop 8.x
Remove-Item -Recurse -Force "$PS8_ROOT\var\cache\*" -ErrorAction SilentlyContinue

# PrestaShop 1.7.x
Remove-Item -Recurse -Force "$PS17_ROOT\var\cache\*" -ErrorAction SilentlyContinue

# Alternative: via console
php bin/console cache:clear
```

### Use Specific PHP Version

```powershell
# PHP 8.1 for PS8
& "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe" bin/console prestashop:module upgrade $MODULE_NAME

# PHP 7.4 for PS 1.7
& "C:\laragon\bin\php\php-7.4.33-Win32-vc15-x64\php.exe" bin/console prestashop:module upgrade $MODULE_NAME
```

---

## 🐚 Git Bash (Windows) Commands

### Variables (set these first)

```bash
MODULE_NAME="mlcategoryaidescription"
DEV_PATH="/c/Users/Pineapple/Documents/Visual Studio Code/dev.prestashop/modules/mlcategoryaidescription"
PS8_ROOT="/d/FTP/local/ps8.local"
PS17_ROOT="/d/FTP/local/www.speedmypresta.local/ps17"
```

### Create Symlink (Git Bash on Windows)

```bash
# Git Bash uses Windows symlinks via MSYS
rm -rf "$PS8_ROOT/modules/$MODULE_NAME"
ln -s "$DEV_PATH" "$PS8_ROOT/modules/$MODULE_NAME"
```

### Module Install/Uninstall/Upgrade

```bash
cd "$PS8_ROOT"

# Install
php bin/console prestashop:module install $MODULE_NAME

# Uninstall
php bin/console prestashop:module uninstall $MODULE_NAME

# Upgrade
php bin/console prestashop:module upgrade $MODULE_NAME

# Reset (uninstall + install)
php bin/console prestashop:module uninstall $MODULE_NAME && \
php bin/console prestashop:module install $MODULE_NAME
```

### Clear Cache

```bash
rm -rf "$PS8_ROOT/var/cache/"*
# or
php bin/console cache:clear
```

---

## 🐧 Native Bash (Linux/macOS/WSL) Commands

### Variables

```bash
MODULE_NAME="mlcategoryaidescription"
DEV_PATH="/mnt/c/Users/Pineapple/Documents/Visual Studio Code/dev.prestashop/modules/mlcategoryaidescription"
PS8_ROOT="/mnt/d/FTP/local/ps8.local"
PS17_ROOT="/mnt/d/FTP/local/www.speedmypresta.local/ps17"
```

### Create Symlink

```bash
rm -rf "$PS8_ROOT/modules/$MODULE_NAME"
ln -s "$DEV_PATH" "$PS8_ROOT/modules/$MODULE_NAME"
```

### Module Commands

```bash
cd "$PS8_ROOT"

php bin/console prestashop:module install $MODULE_NAME
php bin/console prestashop:module uninstall $MODULE_NAME
php bin/console prestashop:module upgrade $MODULE_NAME
```

---

## 🔧 Quick Reference (Copy-Paste Ready)

### PowerShell One-Liners

```powershell
# Full upgrade PS8 (run from any directory)
cd "D:\FTP\local\ps8.local"; php bin/console prestashop:module upgrade mlcategoryaidescription; Remove-Item -Recurse -Force "var\cache\*"

# Reset module PS8
cd "D:\FTP\local\ps8.local"; php bin/console prestashop:module uninstall mlcategoryaidescription; php bin/console prestashop:module install mlcategoryaidescription
```

### Git Bash One-Liners

```bash
# Full upgrade PS8
cd /d/FTP/local/ps8.local && php bin/console prestashop:module upgrade mlcategoryaidescription && rm -rf var/cache/*

# Reset module PS8
cd /d/FTP/local/ps8.local && php bin/console prestashop:module uninstall mlcategoryaidescription && php bin/console prestashop:module install mlcategoryaidescription
```

---

## 📋 Database Tables

The module creates these tables (prefix may vary):

| Table | Description |
|-------|-------------|
| `ps_mlcategoryai_generation_log` | Generation history per category/field |
| `ps_mlcategoryai_job_queue` | Batch processing job queue |
| `ps_mlcategoryai_prompt_template` | Prompt template definitions |
| `ps_mlcategoryai_prompt_template_lang` | Multilingual prompt content |
| `ps_mlcategoryai_run_stats` | Performance metrics (v1.2.1+) |

### Check Tables Exist

```sql
SHOW TABLES LIKE '%mlcategoryai%';
```

### Drop All Module Tables (reset)

```sql
DROP TABLE IF EXISTS ps_mlcategoryai_generation_log;
DROP TABLE IF EXISTS ps_mlcategoryai_job_queue;
DROP TABLE IF EXISTS ps_mlcategoryai_prompt_template_lang;
DROP TABLE IF EXISTS ps_mlcategoryai_prompt_template;
DROP TABLE IF EXISTS ps_mlcategoryai_run_stats;
```

---

## 🧪 Testing Commands

### Test Cron Endpoint

```bash
# Get token from module config or DB
curl "https://ps8.local/module/mlcategoryaidescription/cron?token=YOUR_CRON_TOKEN"
```

### Check Module Status

```powershell
# PowerShell - check if module is installed
php bin/console prestashop:module status mlcategoryaidescription
```

### List All Modules

```bash
php bin/console prestashop:module list
```

---

## ⚠️ Troubleshooting

### Symlink Permission Error (Windows)

Run PowerShell as Administrator, or enable Developer Mode:
```
Settings > Update & Security > For Developers > Developer Mode: ON
```

### Module Not Found After Symlink

```powershell
# Clear Smarty cache
Remove-Item -Recurse -Force "$PS8_ROOT\var\cache\*"

# Rebuild class index
php bin/console cache:clear
```

### Upgrade Script Not Running

Force upgrade by temporarily changing version in DB:
```sql
UPDATE ps_module SET version = '1.0.0' WHERE name = 'mlcategoryaidescription';
```
Then run upgrade command.
