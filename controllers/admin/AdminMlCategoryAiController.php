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
 * Admin Controller for ML Category AI Description module
 * Provides a dedicated menu entry under Catalog
 */
class AdminMlCategoryAiController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();

        $this->bootstrap = true;
    }

    /**
     * Redirect to module configuration page
     */
    public function initContent()
    {
        parent::initContent();

        // Redirect to module configuration page
        $configUrl = $this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => 'mlcategoryaidescription',
            'tab_module' => 'administration',
            'module_name' => 'mlcategoryaidescription',
        ]);

        Tools::redirectAdmin($configUrl);
    }
}
