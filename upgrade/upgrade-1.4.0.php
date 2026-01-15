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
 * Upgrade to version 1.4.0
 *
 * Changes:
 * - Added run_stats table for performance tracking
 * - Improved prompt templates with better product context guidelines
 * - Added file-based debug logging
 * - Fixed temperature/max_completion_tokens for newer AI models
 *
 * @param Mlcategoryaidescription $module
 *
 * @return bool
 */
function upgrade_module_1_4_0($module)
{
    $db = Db::getInstance();

    // Create run_stats table if not exists
    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mlcategoryai_run_stats` (
        `id_run` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_job` INT(11) UNSIGNED DEFAULT NULL,
        `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
        `run_date` DATETIME NOT NULL,
        `categories_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `languages_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `fields_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `total_items` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `processed_items` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `failed_items` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `skipped_items` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `total_tokens_in` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `total_tokens_out` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `execution_time_ms` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `parallel_requests` INT(11) UNSIGNED NOT NULL DEFAULT 1,
        `model_used` VARCHAR(100) DEFAULT NULL,
        PRIMARY KEY (`id_run`),
        KEY `idx_run_date` (`run_date`),
        KEY `idx_id_job` (`id_job`),
        KEY `idx_id_shop` (`id_shop`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

    if (!$db->execute($sql)) {
        return false;
    }

    // Create logs directory if not exists
    $logsDir = _PS_MODULE_DIR_ . 'mlcategoryaidescription/logs/';
    if (!is_dir($logsDir)) {
        @mkdir($logsDir, 0755, true);
    }

    // Check if prompts need updating (old format detection)
    $idTemplate = (int) $db->getValue(
        'SELECT id_prompt_template FROM `' . _DB_PREFIX_ . 'mlcategoryai_prompt_template`
        WHERE field_type = "description"'
    );

    if ($idTemplate) {
        $existingPrompt = $db->getValue(
            'SELECT prompt_template FROM `' . _DB_PREFIX_ . 'mlcategoryai_prompt_template_lang`
            WHERE id_prompt_template = ' . $idTemplate
        );

        // Check if it's the old format (doesn't contain "CONTEXT INFORMATION")
        if ($existingPrompt && strpos($existingPrompt, 'CONTEXT INFORMATION') === false) {
            // Old format detected - log for user awareness
            PrestaShopLogger::addLog(
                '[MLCATAI] Upgrade 1.4.0: Old prompt format detected. Consider updating prompts manually for better results.',
                2
            );
        }
    }

    return true;
}
