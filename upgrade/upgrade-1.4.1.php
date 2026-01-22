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
 * Upgrade to version 1.4.1
 *
 * Changes:
 * - Added visible menu entry under Catalog
 *
 * @param Mlcategoryaidescription $module
 *
 * @return bool
 */
function upgrade_module_1_4_1($module)
{
    // Check if menu tab already exists
    $idMenuTab = (int) Tab::getIdFromClassName('AdminMlCategoryAi');
    if ($idMenuTab) {
        // Already exists, skip
        return true;
    }

    // Find Catalog parent tab
    $catalogTabId = (int) Tab::getIdFromClassName('AdminCatalog');
    if (!$catalogTabId) {
        // Fallback: try to find SELL parent tab (PS 1.7.7+)
        $catalogTabId = (int) Tab::getIdFromClassName('SELL');
    }

    // Install visible menu tab under Catalog
    $menuTab = new Tab();
    $menuTab->class_name = 'AdminMlCategoryAi';
    $menuTab->module = $module->name;
    $menuTab->id_parent = $catalogTabId;
    $menuTab->position = 99;
    $menuTab->active = true;
    $menuTab->icon = 'category';

    foreach (Language::getLanguages(true) as $lang) {
        $menuTab->name[$lang['id_lang']] = 'ML Category AI';
    }

    return $menuTab->add();
}
