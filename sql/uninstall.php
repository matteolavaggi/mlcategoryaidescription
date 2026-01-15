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

$sql = [];

// Only drop job and log tables on uninstall
// Keep configuration and prompt templates for reinstall
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mlcategoryai_generation_log`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mlcategoryai_job_queue`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mlcategoryai_run_stats`';

// Uncomment the following lines if you want to completely remove all data:
// $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mlcategoryai_prompt_template_lang`';
// $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mlcategoryai_prompt_template`';

foreach ($sql as $query) {
    Db::getInstance()->execute($query);
}

// Note: Configuration values (API key, settings) are NOT deleted.
// To completely remove, use: Configuration::deleteByName('MLCATEGORYAI_...');

return true;
