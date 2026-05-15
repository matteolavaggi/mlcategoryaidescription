<?php
/**
 * 2010-2026 2win.agency
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Placeholder resolver for manufacturer prompt templates.
 */
class MlManufacturerAiPlaceholder
{
    protected $idManufacturer;
    protected $idLang;
    protected $idShop;
    /** @var Manufacturer */
    protected $manufacturer;
    /** @var Language */
    protected $language;
    protected $cache = [];

    public function __construct($idManufacturer, $idLang, $idShop = null)
    {
        $this->idManufacturer = (int) $idManufacturer;
        $this->idLang = (int) $idLang;
        $this->idShop = $idShop ? (int) $idShop : (int) Shop::getContextShopID();

        $this->manufacturer = new Manufacturer($this->idManufacturer, $this->idLang);
        $this->language = new Language($this->idLang);
    }

    /**
     * @param string $template
     *
     * @return string
     */
    public function resolve($template)
    {
        $pattern = '/\{([a-z_]+)(?::(\d+))?\}/i';

        return preg_replace_callback($pattern, function ($matches) {
            $placeholder = strtolower($matches[1]);
            $param = isset($matches[2]) ? (int) $matches[2] : null;

            return $this->resolvePlaceholder($placeholder, $param);
        }, $template);
    }

    /**
     * @param string $placeholder
     * @param int|null $param
     *
     * @return string
     */
    protected function resolvePlaceholder($placeholder, $param = null)
    {
        $cacheKey = $placeholder . ($param !== null ? ':' . $param : '');
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $value = '';
        switch ($placeholder) {
            case 'manufacturer_name':
                $value = $this->getManufacturerName();
                break;
            case 'manufacturer_description':
                $value = strip_tags($this->manufacturer->description ?: '');
                break;
            case 'manufacturer_meta_title':
                $value = $this->manufacturer->meta_title ?: '';
                break;
            case 'manufacturer_meta_description':
                $value = $this->manufacturer->meta_description ?: '';
                break;
            case 'manufacturer_meta_keywords':
                $value = $this->manufacturer->meta_keywords ?: '';
                break;
            case 'manufacturer_short_description':
                $value = strip_tags($this->manufacturer->short_description ?: '');
                break;
            case 'unique_categories':
                $value = $this->getUniqueCategoryNames($param ?: 50);
                break;
            case 'first_products':
                $value = $this->getFirstProducts($param ?: 10);
                break;
            case 'random_products':
                $value = $this->getRandomProducts($param ?: 5);
                break;
            case 'site_name':
                $value = Configuration::get('PS_SHOP_NAME') ?: '';
                break;
            case 'site_description':
                $value = $this->getSiteDescription();
                break;
            case 'shop_url':
                $value = $this->getShopUrl();
                break;
            case 'language_code':
                $value = $this->language->iso_code ?: '';
                break;
            case 'language_name':
                $value = $this->language->name ?: '';
                break;
            default:
                $value = '{' . $placeholder . '}';
        }

        $this->cache[$cacheKey] = $value;

        return $value;
    }

    protected function getManufacturerName()
    {
        return $this->manufacturer->name ?: '';
    }

    /**
     * Distinct category names (from category_product) for active products of this manufacturer.
     *
     * @param int $maxNames
     *
     * @return string
     */
    protected function getUniqueCategoryNames($maxNames)
    {
        try {
            $sql = 'SELECT DISTINCT cl.`name`
                FROM `' . _DB_PREFIX_ . 'product` p
                INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.`id_product` = p.`id_product` AND ps.`id_shop` = ' . (int) $this->idShop . '
                INNER JOIN `' . _DB_PREFIX_ . 'category_product` cp ON cp.`id_product` = p.`id_product`
                INNER JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                    ON cl.`id_category` = cp.`id_category` AND cl.`id_lang` = ' . (int) $this->idLang . ' AND cl.`id_shop` = ' . (int) $this->idShop . '
                WHERE p.`id_manufacturer` = ' . (int) $this->idManufacturer . '
                    AND ps.`active` = 1
                ORDER BY cl.`name` ASC
                LIMIT ' . (int) $maxNames;

            $rows = Db::getInstance()->executeS($sql);
            if (empty($rows)) {
                return '';
            }
            $names = array_unique(array_column($rows, 'name'));

            return implode(', ', $names);
        } catch (Exception $e) {
            return '';
        }
    }

    protected function getFirstProducts($limit)
    {
        try {
            $sql = 'SELECT pl.`name`
                FROM `' . _DB_PREFIX_ . 'product` p
                INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.`id_product` = p.`id_product` AND ps.`id_shop` = ' . (int) $this->idShop . '
                INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON pl.`id_product` = p.`id_product`
                    AND pl.`id_lang` = ' . (int) $this->idLang . ' AND pl.`id_shop` = ' . (int) $this->idShop . '
                WHERE p.`id_manufacturer` = ' . (int) $this->idManufacturer . ' AND ps.`active` = 1
                ORDER BY p.`id_product` ASC
                LIMIT ' . (int) $limit;

            $products = Db::getInstance()->executeS($sql);
            if (empty($products)) {
                return '';
            }

            return implode(', ', array_column($products, 'name'));
        } catch (Exception $e) {
            return '';
        }
    }

    protected function getRandomProducts($limit)
    {
        try {
            $sql = 'SELECT pl.`name`
                FROM `' . _DB_PREFIX_ . 'product` p
                INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.`id_product` = p.`id_product` AND ps.`id_shop` = ' . (int) $this->idShop . '
                INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON pl.`id_product` = p.`id_product`
                    AND pl.`id_lang` = ' . (int) $this->idLang . ' AND pl.`id_shop` = ' . (int) $this->idShop . '
                WHERE p.`id_manufacturer` = ' . (int) $this->idManufacturer . ' AND ps.`active` = 1
                ORDER BY RAND()
                LIMIT ' . (int) $limit;

            $products = Db::getInstance()->executeS($sql);
            if (empty($products)) {
                return '';
            }

            return implode(', ', array_column($products, 'name'));
        } catch (Exception $e) {
            return '';
        }
    }

    protected function getShopUrl()
    {
        $shop = new Shop($this->idShop);
        if (Validate::isLoadedObject($shop)) {
            $ssl = Configuration::get('PS_SSL_ENABLED');
            $protocol = $ssl ? 'https://' : 'http://';

            return $protocol . $shop->domain . $shop->getBaseURI();
        }

        return Configuration::get('PS_SHOP_DOMAIN') ?: '';
    }

    protected function getSiteDescription()
    {
        $sql = 'SELECT ml.description
                FROM `' . _DB_PREFIX_ . 'meta` m
                LEFT JOIN `' . _DB_PREFIX_ . 'meta_lang` ml
                    ON m.id_meta = ml.id_meta
                    AND ml.id_lang = ' . (int) $this->idLang . '
                    AND ml.id_shop = ' . (int) $this->idShop . '
                WHERE m.page = "index"';

        $description = Db::getInstance()->getValue($sql);

        return $description ?: '';
    }
}
