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
 * Upgrade to 1.7.2
 *
 * Features:
 * - Auto-refresh job status every 10 seconds via AJAX
 * - Restart button for failed jobs (resume from failure point)
 * - Event delegation for job queue table buttons
 * - Real-time progress updates without page reload
 *
 * @param Module $module
 *
 * @return bool
 */
function upgrade_module_1_7_2($module)
{
    // No database changes needed - JavaScript/UX improvements only
    return true;
}
