# LOCAL DEVELOPMENT

## Module: mlcategoryaidescription

---

## 🪟 Windows (PowerShell)

### PrestaShop 8.x - Create Symlink

```powershell
# Remove existing folder/symlink if exists
Remove-Item -LiteralPath "D:\FTP\local\ps8.local\modules\mlcategoryaidescription" -Force -Recurse -ErrorAction SilentlyContinue

# Create symbolic link
New-Item -ItemType SymbolicLink `
         -Path "D:\FTP\local\ps8.local\modules\mlcategoryaidescription" `
         -Target "C:\Users\Pineapple\Documents\Visual Studio Code\dev.prestashop\modules\mlcategoryaidescription"
```

### PrestaShop 1.7.x - Create Symlink

```powershell
# Remove existing folder/symlink if exists
Remove-Item -LiteralPath "D:\FTP\local\www.speedmypresta.local\ps17\modules\mlcategoryaidescription" -Force -Recurse -ErrorAction SilentlyContinue

# Create symbolic link
New-Item -ItemType SymbolicLink `
         -Path "D:\FTP\local\www.speedmypresta.local\ps17\modules\mlcategoryaidescription" `
         -Target "C:\Users\Pineapple\Documents\Visual Studio Code\dev.prestashop\modules\mlcategoryaidescription"
```

### Module CLI Commands (PowerShell)

```powershell
# Navigate to PrestaShop root
cd "D:\FTP\local\ps8.local"

# Install module
php bin/console prestashop:module install mlcategoryaidescription

# Uninstall module
php bin/console prestashop:module uninstall mlcategoryaidescription

# Upgrade module
php bin/console prestashop:module upgrade mlcategoryaidescription

# Clear cache

php bin

# Using specific PHP version (PS 1.7 with PHP 7.4)
C:\wamp64\bin\php\php7.4.33\php.exe bin/console prestashop:module install mlcategoryaidescription
```

---

## 🐧 Unix/Linux/macOS (Bash)

### PrestaShop 8.x - Create Symlink

```bash
# Remove existing folder/symlink if exists
rm -rf /var/www/ps8.local/modules/mlcategoryaidescription

# Create symbolic link
ln -s /path/to/dev/modules/mlcategoryaidescription /var/www/ps8.local/modules/mlcategoryaidescription
```

### PrestaShop 1.7.x - Create Symlink

```bash
# Remove existing folder/symlink if exists
rm -rf /var/www/ps17.local/modules/mlcategoryaidescription

# Create symbolic link
ln -s /path/to/dev/modules/mlcategoryaidescription /var/www/ps17.local/modules/mlcategoryaidescription
```

### Module CLI Commands (Bash)

```bash
# Navigate to PrestaShop root
cd /var/www/ps8.local

# Install module
php bin/console prestashop:module install mlcategoryaidescription

# Uninstall module
php bin/console prestashop:module uninstall mlcategoryaidescription

# Upgrade module
php bin/console prestashop:module upgrade mlcategoryaidescription

# Reset module (uninstall + install)
php bin/console prestashop:module uninstall mlcategoryaidescription && \
php bin/console prestashop:module install mlcategoryaidescription
```

---

## 🔧 Development Tips

### Clear Cache

```powershell
# PowerShell (PS8)
Remove-Item -Recurse -Force "D:\FTP\local\ps8.local\var\cache\*"
```

```bash
# Bash
rm -rf /var/www/ps8.local/var/cache/*
```

### Test Cron URL

```bash
# Get cron token from database or module config
curl "https://your-shop.local/module/mlcategoryaidescription/cron?token=YOUR_CRON_TOKEN"
```

### Test API Connection

```bash
# Quick API test via curl
curl -X POST "https://your-shop.local/module/mlcategoryaidescription/ajax" \
     -d "action=testConnection&token=ADMIN_TOKEN"
```

---

## 📋 Database Tables

The module creates the following tables:

- `PREFIX_mlcategoryai_generation_log` - Generation history
- `PREFIX_mlcategoryai_job_queue` - Batch job queue
- `PREFIX_mlcategoryai_prompt_template` - Prompt templates
- `PREFIX_mlcategoryai_prompt_template_lang` - Prompt translations