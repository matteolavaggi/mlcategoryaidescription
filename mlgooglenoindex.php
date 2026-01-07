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

class Mlgooglenoindex extends Module
{
    /**
     * Configuration keys
     */
    const CONFIG_LIVE_MODE = 'MLGOOGLENOINDEX_LIVE_MODE';
    const CONFIG_PAGINATION = 'MLGOOGLENOINDEX_PAGINATION';
    const CONFIG_ORDER_SORT = 'MLGOOGLENOINDEX_ORDER_SORT';
    const CONFIG_CURRENCY = 'MLGOOGLENOINDEX_CURRENCY';
    const CONFIG_SEARCH = 'MLGOOGLENOINDEX_SEARCH';
    const CONFIG_PRICE_FILTER = 'MLGOOGLENOINDEX_PRICE_FILTER';
    const CONFIG_ITEMS_PER_PAGE = 'MLGOOGLENOINDEX_ITEMS_PER_PAGE';
    const CONFIG_PS_FACETED = 'MLGOOGLENOINDEX_PS_FACETED';
    const CONFIG_AMAZINGFILTER = 'MLGOOGLENOINDEX_AMAZINGFILTER';
    const CONFIG_TRACKING_PARAMS = 'MLGOOGLENOINDEX_TRACKING_PARAMS';
    const CONFIG_USE_HTTP_HEADER = 'MLGOOGLENOINDEX_USE_HTTP_HEADER';
    const CONFIG_CUSTOM_PARAMS = 'MLGOOGLENOINDEX_CUSTOM_PARAMS';

    public function __construct()
    {
        $this->name = 'mlgooglenoindex';
        $this->tab = 'seo';
        $this->version = '1.2.0';
        $this->author = '2win.agency';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ML Google SEO NoIndex');
        $this->description = $this->l('This module enhances Google crawl budget handling, noindex in pagination, and filters results for stores with a big catalog.');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => '9.0'];
    }

    /**
     * Module installation
     *
     * @return bool
     */
    public function install()
    {
        // Default configuration values - all enabled by default
        Configuration::updateValue(self::CONFIG_LIVE_MODE, true);
        Configuration::updateValue(self::CONFIG_PAGINATION, true);
        Configuration::updateValue(self::CONFIG_ORDER_SORT, true);
        Configuration::updateValue(self::CONFIG_CURRENCY, true);
        Configuration::updateValue(self::CONFIG_SEARCH, true);
        Configuration::updateValue(self::CONFIG_PRICE_FILTER, true);
        Configuration::updateValue(self::CONFIG_ITEMS_PER_PAGE, true);
        Configuration::updateValue(self::CONFIG_PS_FACETED, true);
        Configuration::updateValue(self::CONFIG_AMAZINGFILTER, true);
        Configuration::updateValue(self::CONFIG_TRACKING_PARAMS, false);
        Configuration::updateValue(self::CONFIG_USE_HTTP_HEADER, true);
        Configuration::updateValue(self::CONFIG_CUSTOM_PARAMS, '');

        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBackOfficeHeader');
    }

    /**
     * Module uninstallation
     *
     * @return bool
     */
    public function uninstall()
    {
        // Remove all configuration values
        Configuration::deleteByName(self::CONFIG_LIVE_MODE);
        Configuration::deleteByName(self::CONFIG_PAGINATION);
        Configuration::deleteByName(self::CONFIG_ORDER_SORT);
        Configuration::deleteByName(self::CONFIG_CURRENCY);
        Configuration::deleteByName(self::CONFIG_SEARCH);
        Configuration::deleteByName(self::CONFIG_PRICE_FILTER);
        Configuration::deleteByName(self::CONFIG_ITEMS_PER_PAGE);
        Configuration::deleteByName(self::CONFIG_PS_FACETED);
        Configuration::deleteByName(self::CONFIG_AMAZINGFILTER);
        Configuration::deleteByName(self::CONFIG_TRACKING_PARAMS);
        Configuration::deleteByName(self::CONFIG_USE_HTTP_HEADER);
        Configuration::deleteByName(self::CONFIG_CUSTOM_PARAMS);

        // Clean up old config keys from previous versions
        Configuration::deleteByName('MLGOOGLENOINDEX_ORDER');
        Configuration::deleteByName('MLGOOGLENOINDEX_SORT');

        return parent::uninstall();
    }

    /**
     * Check if AmazingFilter module is installed and active
     *
     * @return bool
     */
    public function isAmazingFilterEnabled()
    {
        $module = Module::getInstanceByName('amazzingfilter');

        return $module !== false && Module::isEnabled('amazzingfilter');
    }

    /**
     * Check if PS Faceted Search module is installed and active
     *
     * @return bool
     */
    public function isPsFacetedSearchEnabled()
    {
        $module = Module::getInstanceByName('ps_facetedsearch');

        return $module !== false && Module::isEnabled('ps_facetedsearch');
    }

    /**
     * Load the configuration form
     *
     * @return string
     */
    public function getContent()
    {
        $output = '';

        if ((bool) Tools::isSubmit('submitMlgooglenoindexModule') == true) {
            $this->postProcess();
            $output .= $this->displayConfirmation($this->l('Settings updated successfully.'));
        }

        // Header info template variables
        $this->context->smarty->assign([
            'module_dir' => $this->_path,
            'module_display_name' => $this->displayName,
            'module_description' => $this->description,
            'module_version' => $this->version,
            'documentation_url' => 'https://2win.agency/docs/mlgooglenoindex',
            'support_url' => 'https://addons.prestashop.com/en/contact-us?id_product=123456',
            'rate_url' => 'https://addons.prestashop.com/en/ratings.php',
            'amazingfilter_installed' => $this->isAmazingFilterEnabled(),
            'ps_faceted_installed' => $this->isPsFacetedSearchEnabled(),
        ]);

        // Add header info panel
        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/header_info.tpl');

        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     *
     * @return string
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMlgooglenoindexModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Create the structure of your form.
     *
     * @return array
     */
    protected function getConfigForm()
    {
        $inputs = [
            // Master switch
            [
                'type' => 'switch',
                'label' => $this->l('Enable Module'),
                'name' => self::CONFIG_LIVE_MODE,
                'is_bool' => true,
                'desc' => $this->l('Master switch to enable/disable the noindex functionality.'),
                'values' => [
                    ['id' => 'live_on', 'value' => true, 'label' => $this->l('Enabled')],
                    ['id' => 'live_off', 'value' => false, 'label' => $this->l('Disabled')],
                ],
            ],
            // HTTP Header option
            [
                'type' => 'switch',
                'label' => $this->l('Use HTTP Header'),
                'name' => self::CONFIG_USE_HTTP_HEADER,
                'is_bool' => true,
                'desc' => $this->l('Also send X-Robots-Tag HTTP header (more reliable for some crawlers). Meta tag is always added.'),
                'values' => [
                    ['id' => 'header_on', 'value' => true, 'label' => $this->l('Enabled')],
                    ['id' => 'header_off', 'value' => false, 'label' => $this->l('Disabled')],
                ],
            ],
            // Separator - Standard PrestaShop Parameters
            [
                'type' => 'free',
                'name' => 'separator_standard',
            ],
            // Pagination
            [
                'type' => 'switch',
                'label' => $this->l('Pagination'),
                'name' => self::CONFIG_PAGINATION,
                'is_bool' => true,
                'desc' => $this->l('Add noindex to pages with ?page=2+ or ?p=2+'),
                'values' => [
                    ['id' => 'pagination_on', 'value' => true, 'label' => $this->l('Enabled')],
                    ['id' => 'pagination_off', 'value' => false, 'label' => $this->l('Disabled')],
                ],
            ],
            // Order & Sort (merged)
            [
                'type' => 'switch',
                'label' => $this->l('Order & Sort'),
                'name' => self::CONFIG_ORDER_SORT,
                'is_bool' => true,
                'desc' => $this->l('Add noindex to pages with ordering/sorting parameters (?order=, ?orderby=, ?orderway=)'),
                'values' => [
                    ['id' => 'ordersort_on', 'value' => true, 'label' => $this->l('Enabled')],
                    ['id' => 'ordersort_off', 'value' => false, 'label' => $this->l('Disabled')],
                ],
            ],
            // Currency
            [
                'type' => 'switch',
                'label' => $this->l('Currency'),
                'name' => self::CONFIG_CURRENCY,
                'is_bool' => true,
                'desc' => $this->l('Add noindex to pages with currency parameters (?id_currency=, ?SubmitCurrency)'),
                'values' => [
                    ['id' => 'currency_on', 'value' => true, 'label' => $this->l('Enabled')],
                    ['id' => 'currency_off', 'value' => false, 'label' => $this->l('Disabled')],
                ],
            ],
            // Search
            [
                'type' => 'switch',
                'label' => $this->l('Search Results'),
                'name' => self::CONFIG_SEARCH,
                'is_bool' => true,
                'desc' => $this->l('Add noindex to search result pages (?s=, ?q=, ?search_query=, controller=search)'),
                'values' => [
                    ['id' => 'search_on', 'value' => true, 'label' => $this->l('Enabled')],
                    ['id' => 'search_off', 'value' => false, 'label' => $this->l('Disabled')],
                ],
            ],
            // Price filters
            [
                'type' => 'switch',
                'label' => $this->l('Price Range Filters'),
                'name' => self::CONFIG_PRICE_FILTER,
                'is_bool' => true,
                'desc' => $this->l('Add noindex to pages with price filters (?from=, ?to=, ?price_min=, ?price_max=)'),
                'values' => [
                    ['id' => 'price_on', 'value' => true, 'label' => $this->l('Enabled')],
                    ['id' => 'price_off', 'value' => false, 'label' => $this->l('Disabled')],
                ],
            ],
            // Items per page
            [
                'type' => 'switch',
                'label' => $this->l('Items Per Page'),
                'name' => self::CONFIG_ITEMS_PER_PAGE,
                'is_bool' => true,
                'desc' => $this->l('Add noindex to pages with items per page parameters (?n=, ?resultsPerPage=)'),
                'values' => [
                    ['id' => 'items_on', 'value' => true, 'label' => $this->l('Enabled')],
                    ['id' => 'items_off', 'value' => false, 'label' => $this->l('Disabled')],
                ],
            ],
        ];

        // PS Faceted Search section
        if ($this->isPsFacetedSearchEnabled()) {
            $inputs[] = [
                'type' => 'free',
                'name' => 'separator_faceted',
            ];
            $inputs[] = [
                'type' => 'switch',
                'label' => $this->l('PS Faceted Search'),
                'name' => self::CONFIG_PS_FACETED,
                'is_bool' => true,
                'desc' => $this->l('Add noindex to native faceted search filtered pages (attribute filters, features, etc.)'),
                'values' => [
                    ['id' => 'faceted_on', 'value' => true, 'label' => $this->l('Enabled')],
                    ['id' => 'faceted_off', 'value' => false, 'label' => $this->l('Disabled')],
                ],
            ];
        }

        // AmazingFilter section
        if ($this->isAmazingFilterEnabled()) {
            $inputs[] = [
                'type' => 'free',
                'name' => 'separator_amazing',
            ];
            $inputs[] = [
                'type' => 'switch',
                'label' => $this->l('AmazingFilter'),
                'name' => self::CONFIG_AMAZINGFILTER,
                'is_bool' => true,
                'desc' => $this->l('Add noindex to AmazingFilter filtered pages (/f-* URLs, ?af=, ?from-xhr)'),
                'values' => [
                    ['id' => 'amazingfilter_on', 'value' => true, 'label' => $this->l('Enabled')],
                    ['id' => 'amazingfilter_off', 'value' => false, 'label' => $this->l('Disabled')],
                ],
            ];
        }

        // Tracking parameters section
        $inputs[] = [
            'type' => 'free',
            'name' => 'separator_tracking',
        ];
        $inputs[] = [
            'type' => 'switch',
            'label' => $this->l('Tracking Parameters'),
            'name' => self::CONFIG_TRACKING_PARAMS,
            'is_bool' => true,
            'desc' => $this->l('Add noindex to pages with tracking parameters (utm_*, gclid, fbclid, msclkid, etc.) - Optional, disable if you use canonical tags.'),
            'values' => [
                ['id' => 'tracking_on', 'value' => true, 'label' => $this->l('Enabled')],
                ['id' => 'tracking_off', 'value' => false, 'label' => $this->l('Disabled')],
            ],
        ];

        // Custom parameters section
        $inputs[] = [
            'type' => 'free',
            'name' => 'separator_custom',
        ];
        $inputs[] = [
            'type' => 'textarea',
            'label' => $this->l('Custom Parameters'),
            'name' => self::CONFIG_CUSTOM_PARAMS,
            'desc' => $this->l('Add custom URL parameters to trigger noindex (one per line, without "?" or "="). Example: my_filter'),
            'cols' => 60,
            'rows' => 5,
        ];

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('NoIndex Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => $inputs,
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     *
     * @return array
     */
    protected function getConfigFormValues()
    {
        $values = [
            self::CONFIG_LIVE_MODE => Configuration::get(self::CONFIG_LIVE_MODE),
            self::CONFIG_USE_HTTP_HEADER => Configuration::get(self::CONFIG_USE_HTTP_HEADER),
            self::CONFIG_PAGINATION => Configuration::get(self::CONFIG_PAGINATION),
            self::CONFIG_ORDER_SORT => Configuration::get(self::CONFIG_ORDER_SORT),
            self::CONFIG_CURRENCY => Configuration::get(self::CONFIG_CURRENCY),
            self::CONFIG_SEARCH => Configuration::get(self::CONFIG_SEARCH),
            self::CONFIG_PRICE_FILTER => Configuration::get(self::CONFIG_PRICE_FILTER),
            self::CONFIG_ITEMS_PER_PAGE => Configuration::get(self::CONFIG_ITEMS_PER_PAGE),
            self::CONFIG_TRACKING_PARAMS => Configuration::get(self::CONFIG_TRACKING_PARAMS),
            self::CONFIG_CUSTOM_PARAMS => Configuration::get(self::CONFIG_CUSTOM_PARAMS),
            'separator_standard' => $this->context->smarty->fetch(
                $this->local_path . 'views/templates/admin/separator_standard.tpl'
            ),
            'separator_tracking' => $this->context->smarty->fetch(
                $this->local_path . 'views/templates/admin/separator_tracking.tpl'
            ),
            'separator_custom' => $this->context->smarty->fetch(
                $this->local_path . 'views/templates/admin/separator_custom.tpl'
            ),
        ];

        if ($this->isPsFacetedSearchEnabled()) {
            $values[self::CONFIG_PS_FACETED] = Configuration::get(self::CONFIG_PS_FACETED);
            $values['separator_faceted'] = $this->context->smarty->fetch(
                $this->local_path . 'views/templates/admin/separator_faceted.tpl'
            );
        }

        if ($this->isAmazingFilterEnabled()) {
            $values[self::CONFIG_AMAZINGFILTER] = Configuration::get(self::CONFIG_AMAZINGFILTER);
            $values['separator_amazing'] = $this->context->smarty->fetch(
                $this->local_path . 'views/templates/admin/separator_amazing.tpl'
            );
        }

        return $values;
    }

    /**
     * Save form data.
     *
     * @return void
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     *
     * @return void
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Main hook to inject noindex meta tag
     *
     * @return string
     */
    public function hookDisplayHeader()
    {
        // Check if module is enabled
        if (!Configuration::get(self::CONFIG_LIVE_MODE)) {
            return '';
        }

        // Check if we should add noindex
        if ($this->shouldNoIndex()) {
            // Send HTTP header if enabled
            if (Configuration::get(self::CONFIG_USE_HTTP_HEADER) && !headers_sent()) {
                header('X-Robots-Tag: noindex, follow', true);
            }

            return '<meta name="robots" content="noindex,follow">' . "\n";
        }

        return '';
    }

    /**
     * Determine if the current page should have noindex
     *
     * @return bool
     */
    protected function shouldNoIndex()
    {
        $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        // Check pagination (page > 1)
        if (Configuration::get(self::CONFIG_PAGINATION)) {
            $page = (int) Tools::getValue('page');
            if ($page > 1) {
                return true;
            }
            // Also check for 'p' parameter (used in some PrestaShop versions)
            $p = (int) Tools::getValue('p');
            if ($p > 1) {
                return true;
            }
        }

        // Check order & sort parameters
        if (Configuration::get(self::CONFIG_ORDER_SORT)) {
            if (Tools::getValue('order')
                || Tools::getIsset('order')
                || Tools::getValue('orderby')
                || Tools::getValue('orderway')
            ) {
                return true;
            }
        }

        // Check currency parameters
        if (Configuration::get(self::CONFIG_CURRENCY)) {
            if (Tools::getValue('id_currency')
                || Tools::getValue('SubmitCurrency')
                || Tools::getIsset('SubmitCurrency')
            ) {
                return true;
            }
        }

        // Check search pages
        if (Configuration::get(self::CONFIG_SEARCH)) {
            $controller = Tools::getValue('controller');
            if ($controller === 'search'
                || Tools::getValue('s')
                || Tools::getValue('q')
                || Tools::getValue('search_query')
            ) {
                return true;
            }
        }

        // Check price range filters
        if (Configuration::get(self::CONFIG_PRICE_FILTER)) {
            if (Tools::getValue('from')
                || Tools::getValue('to')
                || Tools::getValue('price_min')
                || Tools::getValue('price_max')
            ) {
                return true;
            }
        }

        // Check items per page
        if (Configuration::get(self::CONFIG_ITEMS_PER_PAGE)) {
            if (Tools::getValue('n') || Tools::getValue('resultsPerPage')) {
                return true;
            }
        }

        // Check Native PS Faceted Search (only if module is enabled)
        if ($this->isPsFacetedSearchEnabled() && Configuration::get(self::CONFIG_PS_FACETED)) {
            if ($this->hasFacetedSearchParams()) {
                return true;
            }
        }

        // Check AmazingFilter (only if module is enabled)
        if ($this->isAmazingFilterEnabled() && Configuration::get(self::CONFIG_AMAZINGFILTER)) {
            // Check for /f-* URL pattern
            if (preg_match('#/f-[^/]+#', $requestUri)) {
                return true;
            }
            // Check for ?af= parameter
            if (Tools::getValue('af') || Tools::getIsset('af')) {
                return true;
            }
            // Check for amazzingfilter specific parameters
            if (Tools::getValue('from-xhr')) {
                return true;
            }
        }

        // Check tracking parameters
        if (Configuration::get(self::CONFIG_TRACKING_PARAMS)) {
            if ($this->hasTrackingParams()) {
                return true;
            }
        }

        // Check custom parameters
        $customParams = Configuration::get(self::CONFIG_CUSTOM_PARAMS);
        if (!empty($customParams)) {
            $params = array_filter(array_map('trim', explode("\n", $customParams)));
            foreach ($params as $param) {
                $param = trim($param, "\r");
                if (!empty($param) && (Tools::getValue($param) !== false || Tools::getIsset($param))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check for PS Faceted Search parameters
     *
     * @return bool
     */
    protected function hasFacetedSearchParams()
    {
        // Common faceted search parameter patterns in PrestaShop
        $facetedParams = [
            'id_attribute_group',
            'id_feature',
            'id_attribute',
            'weight',
            'condition',
            'manufacturer',
            'availability',
            'color',
            'size',
            'material',
        ];

        foreach ($facetedParams as $param) {
            if (Tools::getValue($param) || Tools::getIsset($param)) {
                return true;
            }
        }

        // Check for URL-encoded facet patterns (PrestaShop 1.7+ format)
        $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        // Pattern: /category-name/color-blue/size-m etc.
        if (preg_match('#/[a-z]+-[^/]+/[a-z]+-[^/]+#i', $requestUri)) {
            // Multiple filter segments in URL
            $controller = Tools::getValue('controller');
            if ($controller === 'category'
                || $controller === 'manufacturer'
                || $controller === 'supplier'
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for tracking/marketing parameters
     *
     * @return bool
     */
    protected function hasTrackingParams()
    {
        $queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

        // UTM parameters
        if (preg_match('/utm_(source|medium|campaign|term|content|id)=/i', $queryString)) {
            return true;
        }

        // Ad platform click IDs
        $trackingParams = [
            'gclid',
            'gbraid',
            'wbraid',
            'fbclid',
            'msclkid',
            'twclid',
            'li_fat_id',
            'mc_cid',
            'mc_eid',
            '_ga',
            'dclid',
        ];

        foreach ($trackingParams as $param) {
            if (Tools::getValue($param) || Tools::getIsset($param)) {
                return true;
            }
        }

        return false;
    }
}
