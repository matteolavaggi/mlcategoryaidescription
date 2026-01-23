<?php
/**
 * 2010-2026 2win.agency
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 wesite only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please contact us for extra customization service at an affordable price
 *
 * @author    2win.agency
 * @copyright 2010-2026 2win.agency
 * @license   Valid for 1 website (or project) for each purchase of license
 *            International Registered Trademark & Property of 2win.agency
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade module to version 1.6.0
 *
 * Adds Google Translate integration support:
 * - New columns for two-phase job processing
 * - Default configuration values for GT settings
 *
 * @param Mlcategoryaidescription $module
 *
 * @return bool
 */
function upgrade_module_1_6_0($module)
{
    $db = Db::getInstance();

    // Add new columns to job_queue table for Google Translate support
    $columns = [
        'use_google_translate' => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT "0=OpenAI only, 1=OpenAI+GT"',
        'primary_language_id' => 'INT(10) UNSIGNED DEFAULT NULL COMMENT "Language ID for OpenAI generation"',
        'translate_language_ids' => 'TEXT DEFAULT NULL COMMENT "JSON array of target language IDs"',
        'phase' => 'VARCHAR(20) NOT NULL DEFAULT "openai" COMMENT "openai|translate|completed"',
        'current_translate_lang_index' => 'INT(10) UNSIGNED NOT NULL DEFAULT 0',
        'current_translate_position' => 'INT(10) UNSIGNED NOT NULL DEFAULT 0',
    ];

    $tableName = _DB_PREFIX_ . 'mlcategoryai_job_queue';

    foreach ($columns as $columnName => $columnDef) {
        // Check if column already exists
        $columnExists = $db->executeS(
            'SHOW COLUMNS FROM `' . bqSQL($tableName) . '` LIKE "' . pSQL($columnName) . '"'
        );

        if (empty($columnExists)) {
            // Add column after write_mode
            $sql = 'ALTER TABLE `' . bqSQL($tableName) . '` ADD COLUMN `' . bqSQL($columnName) . '` ' . $columnDef;

            if (!$db->execute($sql)) {
                PrestaShopLogger::addLog(
                    'mlcategoryaidescription upgrade 1.6.0: Failed to add column ' . $columnName,
                    3,
                    null,
                    'Module',
                    0,
                    true
                );

                return false;
            }
        }
    }

    // Set default configuration values for Google Translate settings
    $defaults = [
        Mlcategoryaidescription::CONFIG_GOOGLE_TRANSLATE_ENABLED => false,
        Mlcategoryaidescription::CONFIG_GOOGLE_TRANSLATE_API_KEY => '',
        Mlcategoryaidescription::CONFIG_PRIMARY_LANGUAGE => (int) Configuration::get('PS_LANG_DEFAULT'),
        Mlcategoryaidescription::CONFIG_TRANSLATE_LANGUAGES => '[]',
    ];

    foreach ($defaults as $key => $defaultValue) {
        // Only set if config doesn't exist
        $existingValue = Configuration::get($key);
        if ($existingValue === false) {
            Configuration::updateValue($key, $defaultValue);
        }
    }

    PrestaShopLogger::addLog(
        'mlcategoryaidescription upgrade 1.6.0: Successfully added Google Translate support',
        1,
        null,
        'Module',
        0,
        true
    );

    return true;
}
