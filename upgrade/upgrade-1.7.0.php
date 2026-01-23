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
 * Upgrade to version 1.7.0
 *
 * v1.7.0 introduces batched API calls:
 * - OpenAI: all fields for a category generated in single JSON request (75% fewer calls)
 * - Google Translate: all fields translated in single batch call
 * - Job queue counts by category/language pairs, not by fields
 *
 * No database schema changes required.
 * Existing running jobs will fail on upgrade (different item structure).
 * New jobs created after upgrade will use the new batched processing.
 *
 * @param Mlcategoryaidescription $module
 *
 * @return bool
 */
function upgrade_module_1_7_0($module)
{
    // No schema changes needed for batched API calls
    // The changes are purely in PHP code structure

    // Clear any running jobs as they use the old item structure
    // Users will need to create new jobs after upgrade
    $sql = 'UPDATE `' . _DB_PREFIX_ . 'mlcategoryai_job_queue` 
            SET `status` = "failed", 
                `error_log` = "Job cancelled during upgrade to v1.7.0 (new batched processing format)",
                `updated_at` = NOW() 
            WHERE `status` IN ("pending", "running", "paused")';

    Db::getInstance()->execute($sql);

    return true;
}
