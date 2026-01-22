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
 * Placeholder resolver for prompt templates
 * Resolves placeholders like {category_name}, {first_products:10}, etc.
 */
class MlCategoryAiPlaceholder
{
    /**
     * @var int Category ID
     */
    protected $idCategory;

    /**
     * @var int Language ID
     */
    protected $idLang;

    /**
     * @var int Shop ID
     */
    protected $idShop;

    /**
     * @var Category Category object
     */
    protected $category;

    /**
     * @var Language Language object
     */
    protected $language;

    /**
     * @var array Cached placeholder values
     */
    protected $cache = [];

    /**
     * Constructor
     *
     * @param int $idCategory
     * @param int $idLang
     * @param int|null $idShop
     */
    public function __construct($idCategory, $idLang, $idShop = null)
    {
        $this->idCategory = (int) $idCategory;
        $this->idLang = (int) $idLang;
        $this->idShop = $idShop ? (int) $idShop : (int) Shop::getContextShopID();

        $this->category = new Category($this->idCategory, $this->idLang);
        $this->language = new Language($this->idLang);
    }

    /**
     * Resolve all placeholders in a template
     *
     * @param string $template
     *
     * @return string
     */
    public function resolve($template)
    {
        // Pattern to match placeholders like {placeholder} or {placeholder:param}
        $pattern = '/\{([a-z_]+)(?::(\d+))?\}/i';

        return preg_replace_callback($pattern, function ($matches) {
            $placeholder = strtolower($matches[1]);
            $param = isset($matches[2]) ? (int) $matches[2] : null;

            return $this->resolvePlaceholder($placeholder, $param);
        }, $template);
    }

    /**
     * Resolve a single placeholder
     *
     * @param string $placeholder
     * @param int|null $param
     *
     * @return string
     */
    protected function resolvePlaceholder($placeholder, $param = null)
    {
        // Check cache first
        $cacheKey = $placeholder . ($param !== null ? ':' . $param : '');
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $value = '';

        switch ($placeholder) {
            // Category placeholders
            case 'category_name':
                $value = $this->getCategoryName();
                break;

            case 'category_description':
                $value = $this->getCategoryDescription();
                break;

            case 'category_meta_title':
                $value = $this->getCategoryMetaTitle();
                break;

            case 'category_meta_description':
                $value = $this->getCategoryMetaDescription();
                break;

            case 'category_meta_keywords':
                $value = $this->getCategoryMetaKeywords();
                break;

            case 'category_link_rewrite':
                $value = $this->getCategoryLinkRewrite();
                break;

            case 'parent_category_name':
                $value = $this->getParentCategoryName();
                break;

            case 'site_name':
                $value = $this->getSiteName();
                break;

            case 'site_description':
                $value = $this->getSiteDescription();
                break;

            case 'product_list':
                $value = $this->getProductList();
                break;

            case 'product_count':
                $value = $this->getProductCount();
                break;

            case 'first_products':
                $value = $this->getFirstProducts($param ?: 10);
                break;

            case 'random_products':
                $value = $this->getRandomProducts($param ?: 5);
                break;

            case 'language_code':
                $value = $this->getLanguageCode();
                break;

            case 'language_name':
                $value = $this->getLanguageName();
                break;

            case 'category_breadcrumb':
                $value = $this->getCategoryBreadcrumb();
                break;

            case 'category_url':
                $value = $this->getCategoryUrl();
                break;

            case 'shop_url':
                $value = $this->getShopUrl();
                break;

            default:
                $value = '{' . $placeholder . '}'; // Return unchanged if unknown
                break;
        }

        $this->cache[$cacheKey] = $value;

        return $value;
    }

    /**
     * Get category name
     *
     * @return string
     */
    protected function getCategoryName()
    {
        return $this->category->name ?: '';
    }

    /**
     * Get category description
     *
     * @return string
     */
    protected function getCategoryDescription()
    {
        return strip_tags($this->category->description ?: '');
    }

    /**
     * Get category meta title
     *
     * @return string
     */
    protected function getCategoryMetaTitle()
    {
        return $this->category->meta_title ?: '';
    }

    /**
     * Get category meta description
     *
     * @return string
     */
    protected function getCategoryMetaDescription()
    {
        return $this->category->meta_description ?: '';
    }

    /**
     * Get category meta keywords
     *
     * @return string
     */
    protected function getCategoryMetaKeywords()
    {
        // meta_keywords may not exist in all PS versions
        return isset($this->category->meta_keywords) ? ($this->category->meta_keywords ?: '') : '';
    }

    /**
     * Get category link rewrite (friendly URL)
     *
     * @return string
     */
    protected function getCategoryLinkRewrite()
    {
        return $this->category->link_rewrite ?: '';
    }

    /**
     * Get parent category name
     *
     * @return string
     */
    protected function getParentCategoryName()
    {
        if ($this->category->id_parent > 0) {
            $parent = new Category($this->category->id_parent, $this->idLang);

            return $parent->name ?: '';
        }

        return '';
    }

    /**
     * Get full category breadcrumb path
     * Example: "Clothing > Socks > Wool > Merino"
     *
     * @return string
     */
    protected function getCategoryBreadcrumb()
    {
        $breadcrumb = [];
        $category = $this->category;

        // Add current category
        if ($category->name) {
            $breadcrumb[] = $category->name;
        }

        // Walk up the parent chain (skip root categories 1 and 2)
        $parentId = (int) $category->id_parent;
        $maxDepth = 10; // Prevent infinite loops
        $depth = 0;

        while ($parentId > 2 && $depth < $maxDepth) {
            $parent = new Category($parentId, $this->idLang);
            if (Validate::isLoadedObject($parent) && $parent->name) {
                array_unshift($breadcrumb, $parent->name);
                $parentId = (int) $parent->id_parent;
            } else {
                break;
            }
            $depth++;
        }

        return implode(' > ', $breadcrumb);
    }

    /**
     * Get full category URL
     *
     * @return string
     */
    protected function getCategoryUrl()
    {
        $link = Context::getContext()->link;
        if ($link && Validate::isLoadedObject($this->category)) {
            return $link->getCategoryLink($this->category, null, $this->idLang);
        }

        return '';
    }

    /**
     * Get shop base URL
     *
     * @return string
     */
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

    /**
     * Get site/shop name
     *
     * @return string
     */
    protected function getSiteName()
    {
        return Configuration::get('PS_SHOP_NAME') ?: '';
    }

    /**
     * Get site description from homepage meta
     *
     * @return string
     */
    protected function getSiteDescription()
    {
        // Get description from index (homepage) page meta
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

    /**
     * Get all product names in category as comma-separated list
     *
     * @return string
     */
    protected function getProductList()
    {
        return $this->getFirstProducts(50);
    }

    /**
     * Get product count in category
     *
     * @return string
     */
    protected function getProductCount()
    {
        try {
            $sql = 'SELECT COUNT(DISTINCT cp.`id_product`)
                    FROM `' . _DB_PREFIX_ . 'category_product` cp
                    INNER JOIN `' . _DB_PREFIX_ . 'product` p ON p.`id_product` = cp.`id_product`
                    INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.`id_product` = p.`id_product`
                    WHERE cp.`id_category` = ' . (int) $this->idCategory . '
                    AND ps.`id_shop` = ' . (int) $this->idShop . '
                    AND ps.`active` = 1';

            $count = (int) Db::getInstance()->getValue($sql);

            return (string) $count;
        } catch (Exception $e) {
            return '0';
        }
    }

    /**
     * Get first N products from category
     *
     * @param int $limit
     *
     * @return string
     */
    protected function getFirstProducts($limit = 10)
    {
        try {
            $sql = 'SELECT pl.`name`
                    FROM `' . _DB_PREFIX_ . 'category_product` cp
                    INNER JOIN `' . _DB_PREFIX_ . 'product` p ON p.`id_product` = cp.`id_product`
                    INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.`id_product` = p.`id_product`
                    INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON pl.`id_product` = p.`id_product`
                    WHERE cp.`id_category` = ' . (int) $this->idCategory . '
                    AND ps.`id_shop` = ' . (int) $this->idShop . '
                    AND pl.`id_lang` = ' . (int) $this->idLang . '
                    AND pl.`id_shop` = ' . (int) $this->idShop . '
                    AND ps.`active` = 1
                    ORDER BY cp.`position` ASC
                    LIMIT ' . (int) $limit;

            $products = Db::getInstance()->executeS($sql);

            if (empty($products)) {
                return '';
            }

            $names = array_column($products, 'name');

            return implode(', ', $names);
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Get N random products from category
     *
     * @param int $limit
     *
     * @return string
     */
    protected function getRandomProducts($limit = 5)
    {
        try {
            $sql = 'SELECT pl.`name`
                    FROM `' . _DB_PREFIX_ . 'category_product` cp
                    INNER JOIN `' . _DB_PREFIX_ . 'product` p ON p.`id_product` = cp.`id_product`
                    INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.`id_product` = p.`id_product`
                    INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON pl.`id_product` = p.`id_product`
                    WHERE cp.`id_category` = ' . (int) $this->idCategory . '
                    AND ps.`id_shop` = ' . (int) $this->idShop . '
                    AND pl.`id_lang` = ' . (int) $this->idLang . '
                    AND pl.`id_shop` = ' . (int) $this->idShop . '
                    AND ps.`active` = 1
                    ORDER BY RAND()
                    LIMIT ' . (int) $limit;

            $products = Db::getInstance()->executeS($sql);

            if (empty($products)) {
                return '';
            }

            $names = array_column($products, 'name');

            return implode(', ', $names);
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Get language ISO code
     *
     * @return string
     */
    protected function getLanguageCode()
    {
        return $this->language->iso_code ?: 'en';
    }

    /**
     * Get language full name
     *
     * @return string
     */
    protected function getLanguageName()
    {
        return $this->language->name ?: 'English';
    }

    /**
     * Get list of all available placeholders with descriptions
     *
     * @return array
     */
    public static function getAvailablePlaceholders()
    {
        return [
            // Category placeholders
            [
                'placeholder' => '{category_name}',
                'description' => 'Category name in target language',
                'example' => 'Men\'s Shoes',
            ],
            [
                'placeholder' => '{category_description}',
                'description' => 'Current category description (text only)',
                'example' => 'Browse our collection...',
            ],
            [
                'placeholder' => '{category_meta_title}',
                'description' => 'Current meta title',
                'example' => 'Men\'s Shoes - MyShop',
            ],
            [
                'placeholder' => '{category_meta_description}',
                'description' => 'Current meta description',
                'example' => 'Shop the best...',
            ],
            [
                'placeholder' => '{category_meta_keywords}',
                'description' => 'Meta keywords',
                'example' => 'shoes, men, leather',
            ],
            [
                'placeholder' => '{parent_category_name}',
                'description' => 'Parent category name',
                'example' => 'Footwear',
            ],
            [
                'placeholder' => '{category_breadcrumb}',
                'description' => 'Full category path (all parents)',
                'example' => 'Clothing > Socks > Wool > Merino',
            ],
            [
                'placeholder' => '{category_url}',
                'description' => 'Full URL to category page',
                'example' => 'https://myshop.com/en/3-category-name',
            ],
            // Site placeholders
            [
                'placeholder' => '{site_name}',
                'description' => 'Shop name',
                'example' => 'MyShop',
            ],
            [
                'placeholder' => '{site_description}',
                'description' => 'Shop meta description',
                'example' => 'Your online store...',
            ],
            [
                'placeholder' => '{shop_url}',
                'description' => 'Shop base URL',
                'example' => 'https://myshop.com/',
            ],
            // Product placeholders
            [
                'placeholder' => '{product_list}',
                'description' => 'Up to 50 product names from category',
                'example' => 'Product A, Product B...',
            ],
            [
                'placeholder' => '{product_count}',
                'description' => 'Number of products in category',
                'example' => '42',
            ],
            [
                'placeholder' => '{first_products:N}',
                'description' => 'First N products from category',
                'example' => '{first_products:10}',
            ],
            [
                'placeholder' => '{random_products:N}',
                'description' => 'N random products from category',
                'example' => '{random_products:5}',
            ],
            // Language placeholders
            [
                'placeholder' => '{language_code}',
                'description' => 'Target language ISO code (auto-injected)',
                'example' => 'en, fr, de',
            ],
            [
                'placeholder' => '{language_name}',
                'description' => 'Target language full name (auto-injected)',
                'example' => 'English, Français',
            ],
        ];
    }
}
