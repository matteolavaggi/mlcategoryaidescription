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
 * Upgrade to version 1.4.2
 *
 * Changes:
 * - Added job status dashboard with cron URL and setup instructions
 * - Added background (cron) processing mode option
 * - Auto-detect stuck jobs (no updates for 5+ minutes)
 * - Delete job functionality
 *
 * @param Mlcategoryaidescription $module
 *
 * @return bool
 */
function upgrade_module_1_4_2($module)
{
    // No database changes needed for this version
    // All changes are in templates and JavaScript
    return true;
}
