<?php
/**
 * 2010-2025 2win.agency
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
 * @copyright 2010-2025 2win.agency
 * @license   Valid for 1 website (or project) for each purchase of license
 *            International Registered Trademark & Property of 2win.agency
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade script for version 1.2.0
 * - Merges ORDER and SORT into ORDER_SORT
 * - Adds new configuration options: PRICE_FILTER, ITEMS_PER_PAGE, PS_FACETED, TRACKING_PARAMS, USE_HTTP_HEADER
 *
 * @param Mlgooglenoindex $module
 *
 * @return bool
 */
function upgrade_module_1_2_0($module)
{
    // Migrate ORDER and SORT to merged ORDER_SORT
    $oldOrder = Configuration::get('MLGOOGLENOINDEX_ORDER');
    $oldSort = Configuration::get('MLGOOGLENOINDEX_SORT');

    // If either was enabled, enable the new merged option
    $orderSortEnabled = ($oldOrder || $oldSort);
    Configuration::updateValue('MLGOOGLENOINDEX_ORDER_SORT', $orderSortEnabled ? true : true);

    // Remove old separate config keys
    Configuration::deleteByName('MLGOOGLENOINDEX_ORDER');
    Configuration::deleteByName('MLGOOGLENOINDEX_SORT');

    // Add new configuration options with defaults
    Configuration::updateValue('MLGOOGLENOINDEX_PRICE_FILTER', true);
    Configuration::updateValue('MLGOOGLENOINDEX_ITEMS_PER_PAGE', true);
    Configuration::updateValue('MLGOOGLENOINDEX_PS_FACETED', true);
    Configuration::updateValue('MLGOOGLENOINDEX_TRACKING_PARAMS', false); // Disabled by default - optional feature
    Configuration::updateValue('MLGOOGLENOINDEX_USE_HTTP_HEADER', true); // Enabled by default

    return true;
}
