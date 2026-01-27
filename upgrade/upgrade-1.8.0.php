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
 * Upgrade to 1.8.0
 *
 * Major improvements to processing flow and recovery features:
 *
 * 1. Per-category interleaved processing (vs two-phase):
 *    - OLD: Generate ALL OpenAI → Translate ALL
 *    - NEW: For each category: OpenAI → Translate → Next category
 *    - Benefit: If process dies, you have complete categories, not partial translations
 *
 * 2. Smart fill-missing detection:
 *    - In "fill_missing" mode with Google Translate enabled
 *    - Checks if primary language content already exists
 *    - If yes, skips OpenAI and goes directly to translation
 *    - Saves API costs and prevents duplicate content
 *
 * 3. Translate Missing Only feature:
 *    - New AJAX actions: countMissingTranslations, createTranslateOnlyJob
 *    - Finds categories with primary content but missing translations
 *    - Creates a translate-only job (no OpenAI calls)
 *    - Perfect for recovering from interrupted jobs
 *
 * 4. Removed two-phase architecture:
 *    - No more phase transitions that could leave jobs stuck
 *    - Simplified progress calculation (linear instead of weighted)
 *
 * @param Module $module
 *
 * @return bool
 */
function upgrade_module_1_8_0($module)
{
    // No database changes needed - processing flow improvements only
    return true;
}
