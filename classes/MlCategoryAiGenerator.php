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

require_once dirname(__FILE__) . '/MlCategoryAiClient.php';
require_once dirname(__FILE__) . '/MlCategoryAiPlaceholder.php';
require_once dirname(__FILE__) . '/MlCategoryAiLogger.php';

/**
 * Content generator for categories using AI
 */
class MlCategoryAiGenerator
{
    /**
     * @var Module Module instance
     */
    protected $module;

    /**
     * @var MlCategoryAiClient API client
     */
    protected $client;

    /**
     * @var int Shop ID
     */
    protected $idShop;

    /**
     * @var bool|null Cache for meta_keywords column existence check
     */
    protected static $hasMetaKeywordsColumn = null;

    /**
     * Check if meta_keywords column exists in category_lang table
     * PS9+ removed this column
     *
     * @return bool
     */
    public static function hasMetaKeywordsSupport()
    {
        if (self::$hasMetaKeywordsColumn === null) {
            $columns = Db::getInstance()->executeS(
                'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'category_lang` LIKE \'meta_keywords\''
            );
            self::$hasMetaKeywordsColumn = !empty($columns);
        }

        return self::$hasMetaKeywordsColumn;
    }

    /**
     * Constructor
     *
     * @param Module $module
     * @param int|null $idShop
     */
    public function __construct($module, $idShop = null)
    {
        $this->module = $module;
        $this->client = MlCategoryAiClient::createFromConfig($module);
        $this->idShop = $idShop ? (int) $idShop : (int) Shop::getContextShopID();
    }

    /**
     * Generate content for a category in a specific language
     *
     * @param int $idCategory
     * @param int $idLang
     * @param string $fieldType description|meta_title|meta_description
     * @param string $writeMode overwrite|fill_missing
     *
     * @return array Result with 'success', 'content', 'error', 'tokens'
     */
    public function generateField($idCategory, $idLang, $fieldType, $writeMode = 'fill_missing')
    {
        $t0 = microtime(true);

        $result = [
            'success' => false,
            'content' => '',
            'error' => '',
            'tokens' => 0,
            'skipped' => false,
        ];

        // Load category
        $category = new Category((int) $idCategory, (int) $idLang);
        if (!Validate::isLoadedObject($category)) {
            $result['error'] = 'Category not found: ' . $idCategory;
            MlCategoryAiLogger::error('Category not found: ' . $idCategory);

            return $result;
        }

        $t1 = microtime(true);
        MlCategoryAiLogger::debug('loadCategory took ' . round(($t1 - $t0) * 1000) . 'ms - cat=' . $idCategory . ' (' . $category->name . ')');

        // Check if we should skip (fill_missing mode and field has content)
        if ($writeMode === Mlcategoryaidescription::WRITE_MODE_FILL_MISSING) {
            $existingContent = $this->getFieldValue($category, $fieldType);
            if (!empty(trim(strip_tags($existingContent)))) {
                $result['success'] = true;
                $result['skipped'] = true;
                $result['content'] = $existingContent;
                MlCategoryAiLogger::debug('Skipped (fill_missing mode): cat=' . $idCategory . ' field=' . $fieldType);

                return $result;
            }
        }

        // Get prompt template for this field type and language
        $t2 = microtime(true);
        $prompt = $this->getPromptTemplate($fieldType, $idLang);
        if (empty($prompt)) {
            $result['error'] = 'No prompt template found for field: ' . $fieldType;
            MlCategoryAiLogger::error('No prompt template for field=' . $fieldType . ' lang=' . $idLang);

            return $result;
        }

        // Resolve placeholders
        $placeholder = new MlCategoryAiPlaceholder($idCategory, $idLang, $this->idShop);
        $resolvedPrompt = $placeholder->resolve($prompt);
        MlCategoryAiLogger::debug('promptResolve took ' . round((microtime(true) - $t2) * 1000) . 'ms - promptLength=' . strlen($resolvedPrompt));

        // Append language and format instructions
        $languageName = $placeholder->resolve('{language_name}');
        $resolvedPrompt .= PHP_EOL . PHP_EOL . 'CRITICAL INSTRUCTIONS:';
        $resolvedPrompt .= PHP_EOL . '1. Write your response in ' . $languageName . '.';

        // Add format instruction based on field type
        if ($fieldType === Mlcategoryaidescription::FIELD_DESCRIPTION) {
            $resolvedPrompt .= PHP_EOL . '2. Use PLAIN HTML formatting only (use <h2>, <h3>, <p>, <strong>, <ul>, <li> tags).';
            $resolvedPrompt .= PHP_EOL . '3. DO NOT use Markdown formatting (no #, ##, **, __, etc.).';
            $resolvedPrompt .= PHP_EOL . '4. Return ONLY the HTML content, no explanations or additional text.';
        } else {
            $resolvedPrompt .= PHP_EOL . '2. Return ONLY plain text, no HTML tags, no Markdown, no formatting.';
            $resolvedPrompt .= PHP_EOL . '3. Do not include any explanations or additional text.';
        }

        // Generate content via AI (with prompt caching by field type)
        $generatedContent = $this->client->generate($resolvedPrompt, '', $fieldType);

        if ($generatedContent === false) {
            $result['error'] = $this->client->getLastError();
            $this->logGeneration($idCategory, $idLang, $fieldType, 'error', $result['error']);

            return $result;
        }

        // Clean up the generated content
        $generatedContent = $this->cleanContent($generatedContent, $fieldType);

        // Update category field
        $updateResult = $this->updateCategoryField($category, $fieldType, $generatedContent, $idLang);

        if (!$updateResult) {
            $result['error'] = 'Failed to update category field';
            $this->logGeneration($idCategory, $idLang, $fieldType, 'error', $result['error']);

            return $result;
        }

        // Log successful generation
        $tokensUsed = $this->client->getLastTokensUsed();
        $this->logGeneration($idCategory, $idLang, $fieldType, 'success', '', $tokensUsed);

        $result['success'] = true;
        $result['content'] = $generatedContent;
        $result['tokens'] = $tokensUsed;

        return $result;
    }

    /**
     * Get field value from category
     *
     * @param Category $category
     * @param string $fieldType
     *
     * @return string
     */
    protected function getFieldValue($category, $fieldType)
    {
        switch ($fieldType) {
            case Mlcategoryaidescription::FIELD_DESCRIPTION:
                return $category->description ?: '';

            case Mlcategoryaidescription::FIELD_META_TITLE:
                return $category->meta_title ?: '';

            case Mlcategoryaidescription::FIELD_META_DESCRIPTION:
                return $category->meta_description ?: '';

            case Mlcategoryaidescription::FIELD_META_KEYWORDS:
                // meta_keywords may not exist in all PS versions
                return isset($category->meta_keywords) ? ($category->meta_keywords ?: '') : '';

            case Mlcategoryaidescription::FIELD_LINK_REWRITE:
                return $category->link_rewrite ?: '';

            default:
                return '';
        }
    }

    /**
     * Get prompt template for field type and language
     *
     * @param string $fieldType
     * @param int $idLang
     *
     * @return string
     */
    protected function getPromptTemplate($fieldType, $idLang)
    {
        // Ensure we have valid values
        $fieldType = (string) $fieldType;
        $idLang = (int) $idLang;
        $idShop = (int) $this->idShop;

        if (empty($fieldType)) {
            throw new Exception('Field type cannot be empty');
        }

        if ($idLang <= 0) {
            throw new Exception('Language ID must be positive, got: ' . $idLang);
        }

        if ($idShop <= 0) {
            // Fallback to default shop
            $idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
            if ($idShop <= 0) {
                $idShop = 1;
            }
        }

        // Use DbQuery for safer query building
        $query = new DbQuery();
        $query->select('ptl.`prompt_template`');
        $query->from('mlcategoryai_prompt_template', 'pt');
        $query->innerJoin('mlcategoryai_prompt_template_lang', 'ptl', 'pt.`id_prompt_template` = ptl.`id_prompt_template`');
        $query->where('pt.`field_type` = "' . pSQL($fieldType) . '"');
        $query->where('pt.`is_active` = 1');
        $query->where('pt.`id_shop` = ' . $idShop);
        $query->where('ptl.`id_lang` = ' . $idLang);
        $query->orderBy('pt.`id_prompt_template` ASC');

        try {
            $prompt = Db::getInstance()->getValue($query);
        } catch (Exception $e) {
            throw new Exception('SQL Error in getPromptTemplate: ' . $e->getMessage() . ' | Query: ' . $query->build());
        }

        // Fallback to default language if not found
        if (empty($prompt)) {
            $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
            if ($defaultLang <= 0) {
                $defaultLang = 1;
            }

            $query = new DbQuery();
            $query->select('ptl.`prompt_template`');
            $query->from('mlcategoryai_prompt_template', 'pt');
            $query->innerJoin('mlcategoryai_prompt_template_lang', 'ptl', 'pt.`id_prompt_template` = ptl.`id_prompt_template`');
            $query->where('pt.`field_type` = "' . pSQL($fieldType) . '"');
            $query->where('pt.`is_active` = 1');
            $query->where('pt.`id_shop` = ' . $idShop);
            $query->where('ptl.`id_lang` = ' . $defaultLang);
            $query->orderBy('pt.`id_prompt_template` ASC');

            try {
                $prompt = Db::getInstance()->getValue($query);
            } catch (Exception $e) {
                throw new Exception('SQL Error in getPromptTemplate fallback: ' . $e->getMessage());
            }
        }

        // If still no prompt, try any shop
        if (empty($prompt)) {
            $query = new DbQuery();
            $query->select('ptl.`prompt_template`');
            $query->from('mlcategoryai_prompt_template', 'pt');
            $query->innerJoin('mlcategoryai_prompt_template_lang', 'ptl', 'pt.`id_prompt_template` = ptl.`id_prompt_template`');
            $query->where('pt.`field_type` = "' . pSQL($fieldType) . '"');
            $query->where('pt.`is_active` = 1');
            $query->orderBy('pt.`id_prompt_template` ASC');

            try {
                $prompt = Db::getInstance()->getValue($query);
            } catch (Exception $e) {
                throw new Exception('SQL Error in getPromptTemplate any shop: ' . $e->getMessage());
            }
        }

        return $prompt ?: '';
    }

    /**
     * Clean generated content based on field type
     *
     * @param string $content
     * @param string $fieldType
     *
     * @return string
     */
    protected function cleanContent($content, $fieldType)
    {
        // Remove leading/trailing whitespace
        $content = trim($content);

        // Remove quotes if wrapped
        if (preg_match('/^["\'](.+)["\']$/s', $content, $matches)) {
            $content = $matches[1];
        }

        switch ($fieldType) {
            case Mlcategoryaidescription::FIELD_META_TITLE:
                // Strip any HTML/Markdown and limit to 70 characters
                $content = strip_tags($content);
                $content = mb_substr($content, 0, 70);
                break;

            case Mlcategoryaidescription::FIELD_META_DESCRIPTION:
                // Strip any HTML/Markdown and limit to 160 characters
                $content = strip_tags($content);
                $content = mb_substr($content, 0, 160);
                break;

            case Mlcategoryaidescription::FIELD_META_KEYWORDS:
                // Strip any HTML/Markdown
                $content = strip_tags($content);
                break;

            case Mlcategoryaidescription::FIELD_LINK_REWRITE:
                // Ensure valid URL slug
                $content = strip_tags($content);
                $content = strtolower($content);
                $content = preg_replace('/[^a-z0-9-]/', '', $content);
                $content = mb_substr($content, 0, 128);
                break;

            case Mlcategoryaidescription::FIELD_DESCRIPTION:
                // Convert Markdown to HTML if present, then sanitize
                $content = $this->convertMarkdownToHtml($content);
                $content = $this->sanitizeHtml($content);
                break;
        }

        return $content;
    }

    /**
     * Convert common Markdown patterns to HTML
     *
     * @param string $content
     *
     * @return string
     */
    protected function convertMarkdownToHtml($content)
    {
        // Convert headers: ### Text -> <h3>Text</h3>
        $content = preg_replace('/^#{3}\s+(.+)$/m', '<h3>$1</h3>', $content);
        $content = preg_replace('/^#{2}\s+(.+)$/m', '<h2>$1</h2>', $content);
        $content = preg_replace('/^#{1}\s+(.+)$/m', '<h2>$1</h2>', $content);

        // Convert bold: **text** or __text__ -> <strong>text</strong>
        $content = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $content);
        $content = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $content);

        // Convert italic: *text* or _text_ -> <em>text</em>
        $content = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $content);
        $content = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/s', '<em>$1</em>', $content);

        // Convert line breaks to paragraphs
        $paragraphs = preg_split('/\n\s*\n/', $content);
        $paragraphs = array_filter(array_map('trim', $paragraphs));

        if (count($paragraphs) > 1) {
            $content = '<p>' . implode('</p><p>', $paragraphs) . '</p>';
        }

        return $content;
    }

    /**
     * Sanitize HTML content
     *
     * @param string $content
     *
     * @return string
     */
    protected function sanitizeHtml($content)
    {
        // Allow only safe HTML tags
        $allowedTags = '<p><br><strong><b><em><i><ul><ol><li><h1><h2><h3><h4><h5><h6><a>';

        return strip_tags($content, $allowedTags);
    }

    /**
     * Update category field with generated content
     *
     * @param Category $category
     * @param string $fieldType
     * @param string $content
     * @param int $idLang
     *
     * @return bool
     */
    protected function updateCategoryField($category, $fieldType, $content, $idLang)
    {
        // Check if meta_keywords is supported (PS9+ removed it)
        if ($fieldType === Mlcategoryaidescription::FIELD_META_KEYWORDS && !self::hasMetaKeywordsSupport()) {
            MlCategoryAiLogger::debug('meta_keywords not supported in this PS version, skipping');

            return true; // Return true to not count as failure
        }

        $fieldMap = [
            Mlcategoryaidescription::FIELD_DESCRIPTION => 'description',
            Mlcategoryaidescription::FIELD_META_TITLE => 'meta_title',
            Mlcategoryaidescription::FIELD_META_DESCRIPTION => 'meta_description',
            Mlcategoryaidescription::FIELD_META_KEYWORDS => 'meta_keywords',
            Mlcategoryaidescription::FIELD_LINK_REWRITE => 'link_rewrite',
        ];

        if (!isset($fieldMap[$fieldType])) {
            return false;
        }

        $dbField = $fieldMap[$fieldType];

        // For link_rewrite, ensure URL-safe format
        if ($fieldType === Mlcategoryaidescription::FIELD_LINK_REWRITE) {
            $content = Tools::str2url($content);
        }

        // Update via direct SQL for specific language
        return Db::getInstance()->update(
            'category_lang',
            [
                $dbField => pSQL($content, $fieldType === Mlcategoryaidescription::FIELD_DESCRIPTION),
            ],
            '`id_category` = ' . (int) $category->id . ' AND `id_lang` = ' . (int) $idLang
        );
    }

    /**
     * Log generation result
     *
     * @param int $idCategory
     * @param int $idLang
     * @param string $fieldType
     * @param string $status
     * @param string $errorMessage
     * @param int $tokensUsed
     *
     * @return bool
     */
    protected function logGeneration($idCategory, $idLang, $fieldType, $status, $errorMessage = '', $tokensUsed = 0)
    {
        return Db::getInstance()->insert('mlcategoryai_generation_log', [
            'id_category' => (int) $idCategory,
            'id_lang' => (int) $idLang,
            'id_shop' => (int) $this->idShop,
            'field_type' => pSQL($fieldType),
            'generated_at' => date('Y-m-d H:i:s'),
            'model_used' => pSQL($this->client->getModel()),
            'prompt_hash' => '', // Could add prompt hash for tracking changes
            'tokens_used' => (int) $tokensUsed,
            'status' => pSQL($status),
            'error_message' => pSQL($errorMessage),
        ]);
    }

    /**
     * Get generation history for a category
     *
     * @param int $idCategory
     * @param int $idLang
     *
     * @return array
     */
    public function getGenerationHistory($idCategory, $idLang = null)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mlcategoryai_generation_log`
                WHERE `id_category` = ' . (int) $idCategory . '
                AND `id_shop` = ' . (int) $this->idShop;

        if ($idLang !== null) {
            $sql .= ' AND `id_lang` = ' . (int) $idLang;
        }

        $sql .= ' ORDER BY `generated_at` DESC';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Check if a field was previously generated
     *
     * @param int $idCategory
     * @param int $idLang
     * @param string $fieldType
     *
     * @return array|null
     */
    public function getLastGeneration($idCategory, $idLang, $fieldType)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mlcategoryai_generation_log`
                WHERE `id_category` = ' . (int) $idCategory . '
                AND `id_lang` = ' . (int) $idLang . '
                AND `id_shop` = ' . (int) $this->idShop . '
                AND `field_type` = "' . pSQL($fieldType) . '"
                AND `status` = "success"
                ORDER BY `generated_at` DESC
                LIMIT 1';

        $result = Db::getInstance()->getRow($sql);

        return $result ?: null;
    }

    /**
     * Build prompt for an item (public wrapper for parallel processing)
     *
     * @param int $idCategory
     * @param int $idLang
     * @param string $fieldType
     *
     * @return array|false ['prompt' => string] or false on error
     */
    public function buildPromptForItem($idCategory, $idLang, $fieldType)
    {
        $category = new Category((int) $idCategory, (int) $idLang);
        if (!Validate::isLoadedObject($category)) {
            return false;
        }

        $prompt = $this->getPromptTemplate($fieldType, $idLang);
        if (empty($prompt)) {
            return false;
        }

        // Resolve placeholders
        $placeholder = new MlCategoryAiPlaceholder($idCategory, $idLang, $this->idShop);
        $resolvedPrompt = $placeholder->resolve($prompt);

        // Append language and format instructions
        $languageName = $placeholder->resolve('{language_name}');
        $resolvedPrompt .= PHP_EOL . PHP_EOL . 'CRITICAL INSTRUCTIONS:';
        $resolvedPrompt .= PHP_EOL . '1. Write your response in ' . $languageName . '.';

        if ($fieldType === Mlcategoryaidescription::FIELD_DESCRIPTION) {
            $resolvedPrompt .= PHP_EOL . '2. Use PLAIN HTML formatting only (use <h2>, <h3>, <p>, <strong>, <ul>, <li> tags).';
            $resolvedPrompt .= PHP_EOL . '3. DO NOT use Markdown formatting (no #, ##, **, __, etc.).';
            $resolvedPrompt .= PHP_EOL . '4. Return ONLY the HTML content, no explanations or additional text.';
        } else {
            $resolvedPrompt .= PHP_EOL . '2. Return ONLY plain text, no HTML tags, no Markdown, no formatting.';
            $resolvedPrompt .= PHP_EOL . '3. Do not include any explanations or additional text.';
        }

        return ['prompt' => $resolvedPrompt];
    }

    /**
     * Get field value (public wrapper)
     *
     * @param Category $category
     * @param string $fieldType
     *
     * @return string
     */
    public function getFieldValuePublic($category, $fieldType)
    {
        return $this->getFieldValue($category, $fieldType);
    }

    /**
     * Clean content (public wrapper)
     *
     * @param string $content
     * @param string $fieldType
     *
     * @return string
     */
    public function cleanContentPublic($content, $fieldType)
    {
        return $this->cleanContent($content, $fieldType);
    }

    /**
     * Update category field (public wrapper)
     *
     * @param Category $category
     * @param string $fieldType
     * @param string $content
     * @param int $idLang
     *
     * @return bool
     */
    public function updateCategoryFieldPublic($category, $fieldType, $content, $idLang)
    {
        return $this->updateCategoryField($category, $fieldType, $content, $idLang);
    }

    /**
     * Log generation (public wrapper)
     *
     * @param int $idCategory
     * @param int $idLang
     * @param string $fieldType
     * @param string $status
     * @param string $errorMessage
     * @param int $tokensUsed
     */
    public function logGenerationPublic($idCategory, $idLang, $fieldType, $status, $errorMessage = '', $tokensUsed = 0)
    {
        $this->logGeneration($idCategory, $idLang, $fieldType, $status, $errorMessage, $tokensUsed);
    }
}
