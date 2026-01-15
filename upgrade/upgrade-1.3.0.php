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
 * Upgrade to version 1.3.0
 * - Add performance metrics table for execution tracking
 * - Add parallel processing support
 *
 * @param Module $module
 *
 * @return bool
 */
function upgrade_module_1_3_0($module)
{
    $sql = [];

    // Performance metrics table - lightweight execution stats
    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mlcategoryai_run_stats` (
        `id_run` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_job` INT(11) UNSIGNED DEFAULT NULL,
        `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
        `started_at` DATETIME NOT NULL,
        `completed_at` DATETIME DEFAULT NULL,
        `execution_time_ms` INT(11) UNSIGNED DEFAULT NULL,
        `categories_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `languages_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `fields_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `items_processed` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `items_skipped` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `items_failed` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `write_mode` VARCHAR(20) NOT NULL DEFAULT "fill_missing",
        `tokens_input` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `tokens_output` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `parallel_requests` TINYINT UNSIGNED NOT NULL DEFAULT 1,
        `avg_request_time_ms` INT(11) UNSIGNED DEFAULT NULL,
        PRIMARY KEY (`id_run`),
        KEY `idx_started` (`started_at`),
        KEY `idx_job` (`id_job`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

    foreach ($sql as $query) {
        if (!Db::getInstance()->execute($query)) {
            return false;
        }
    }

    return true;
}
