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
require_once dirname(__FILE__) . '/MlCategoryAiTranslator.php';
require_once dirname(__FILE__) . '/MlManufacturerAiPlaceholder.php';

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
     * @var bool|null Cache: manufacturer_lang has id_shop (multistore)
     */
    protected static $manufacturerLangHasIdShop = null;

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
     * @return bool
     */
    public static function hasManufacturerMetaKeywordsSupport()
    {
        static $has = null;
        if ($has === null) {
            $columns = Db::getInstance()->executeS(
                'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'manufacturer_lang` LIKE \'meta_keywords\''
            );
            $has = !empty($columns);
        }

        return $has;
    }

    /**
     * @return bool
     */
    public static function manufacturerLangTableHasIdShop()
    {
        if (self::$manufacturerLangHasIdShop === null) {
            $rows = Db::getInstance()->executeS(
                'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'manufacturer_lang` WHERE Field = \'id_shop\''
            );
            self::$manufacturerLangHasIdShop = !empty($rows);
        }

        return self::$manufacturerLangHasIdShop;
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
        if ($this->idShop <= 0) {
            $this->idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
        }
        if ($this->idShop <= 0) {
            $this->idShop = 1;
        }
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
     * Translate a field from source language to target language using Google Translate
     *
     * @param int $idCategory Category ID
     * @param int $idTargetLang Target language ID
     * @param string $fieldType Field type to translate
     * @param int $idSourceLang Source language ID (primary language)
     * @param string $writeMode overwrite|fill_missing
     *
     * @return array Result with 'success', 'content', 'error', 'chars_translated'
     */
    public function translateField($idCategory, $idTargetLang, $fieldType, $idSourceLang, $writeMode = 'fill_missing')
    {
        $result = [
            'success' => false,
            'content' => '',
            'error' => '',
            'skipped' => false,
            'chars_translated' => 0,
        ];

        // Load category for target language
        $category = new Category((int) $idCategory, (int) $idTargetLang);

        if (!Validate::isLoadedObject($category)) {
            $result['error'] = 'Category not found: ' . $idCategory;

            return $result;
        }

        // Check if should skip (fill_missing mode)
        if ($writeMode === Mlcategoryaidescription::WRITE_MODE_FILL_MISSING) {
            $existingContent = $this->getFieldValue($category, $fieldType);
            if (!empty(trim(strip_tags($existingContent)))) {
                $result['success'] = true;
                $result['skipped'] = true;

                return $result;
            }
        }

        // Load source category to get primary content
        $sourceCategory = new Category((int) $idCategory, (int) $idSourceLang);

        if (!Validate::isLoadedObject($sourceCategory)) {
            $result['error'] = 'Source category not found for language: ' . $idSourceLang;

            return $result;
        }

        // Get source content
        $sourceContent = $this->getFieldValue($sourceCategory, $fieldType);

        if (empty(trim(strip_tags($sourceContent)))) {
            MlCategoryAiLogger::warning('Skipping translation: primary content is empty', [
                'category_id' => $idCategory,
                'field' => $fieldType,
                'source_lang' => $idSourceLang,
                'target_lang' => $idTargetLang,
            ]);
            $result['error'] = 'Primary content is empty';

            return $result;
        }

        // Get ISO codes
        $sourceLangIso = Language::getIsoById((int) $idSourceLang);
        $targetLangIso = Language::getIsoById((int) $idTargetLang);

        if (!$sourceLangIso || !$targetLangIso) {
            $result['error'] = 'Could not get language ISO codes';

            return $result;
        }

        // Determine format based on field type
        $format = ($fieldType === Mlcategoryaidescription::FIELD_DESCRIPTION) ? 'html' : 'text';

        // Create translator and translate
        $translator = MlCategoryAiTranslator::createFromConfig($this->module);
        $translatedContent = $translator->translate($sourceContent, $sourceLangIso, $targetLangIso, $format);

        if ($translatedContent === false) {
            $result['error'] = 'Translation failed: ' . $translator->getLastError();
            MlCategoryAiLogger::error('Translation failed', [
                'category_id' => $idCategory,
                'field' => $fieldType,
                'source_lang' => $sourceLangIso,
                'target_lang' => $targetLangIso,
                'error' => $translator->getLastError(),
            ]);

            return $result;
        }

        // Update category field
        $updateResult = $this->updateCategoryField($category, $fieldType, $translatedContent, $idTargetLang);

        if (!$updateResult) {
            $result['error'] = 'Failed to update category field';

            return $result;
        }

        $result['success'] = true;
        $result['content'] = $translatedContent;
        $result['chars_translated'] = $translator->getLastCharactersTranslated();

        MlCategoryAiLogger::debug('Translation successful', [
            'category_id' => $idCategory,
            'field' => $fieldType,
            'source_lang' => $sourceLangIso,
            'target_lang' => $targetLangIso,
            'chars' => $result['chars_translated'],
        ]);

        return $result;
    }

    /**
     * Generate link_rewrite from the category's meta_title (or name) for a given language
     * Used in Google Translate mode where link_rewrite is generated locally from translated meta_title
     *
     * @param int $idCategory Category ID
     * @param int $idLang Target language ID
     * @param string $writeMode overwrite|fill_missing
     *
     * @return array Result with 'success', 'content', 'error'
     */
    public function generateLinkRewriteFromMetaTitle($idCategory, $idLang, $writeMode = 'fill_missing')
    {
        $result = [
            'success' => false,
            'content' => '',
            'error' => '',
            'skipped' => false,
        ];

        // Load category
        $category = new Category((int) $idCategory, (int) $idLang);

        if (!Validate::isLoadedObject($category)) {
            $result['error'] = 'Category not found: ' . $idCategory;

            return $result;
        }

        // Check if should skip (fill_missing mode)
        if ($writeMode === Mlcategoryaidescription::WRITE_MODE_FILL_MISSING) {
            $existingLinkRewrite = $this->getFieldValue($category, Mlcategoryaidescription::FIELD_LINK_REWRITE);
            if (!empty(trim($existingLinkRewrite))) {
                $result['success'] = true;
                $result['skipped'] = true;

                return $result;
            }
        }

        // Get meta_title first, fallback to name
        $sourceText = $category->meta_title;
        if (empty(trim($sourceText))) {
            $sourceText = $category->name;
        }

        if (empty(trim($sourceText))) {
            $result['error'] = 'No meta_title or name available for link_rewrite generation';

            return $result;
        }

        // Generate URL-safe link_rewrite using PrestaShop's native function
        $linkRewrite = Tools::str2url($sourceText);

        if (empty($linkRewrite)) {
            $result['error'] = 'Failed to generate valid link_rewrite from: ' . $sourceText;

            return $result;
        }

        // Update category field
        $updateResult = $this->updateCategoryField($category, Mlcategoryaidescription::FIELD_LINK_REWRITE, $linkRewrite, $idLang);

        if (!$updateResult) {
            $result['error'] = 'Failed to update link_rewrite field';

            return $result;
        }

        $result['success'] = true;
        $result['content'] = $linkRewrite;

        MlCategoryAiLogger::debug('link_rewrite generated from meta_title', [
            'category_id' => $idCategory,
            'lang_id' => $idLang,
            'source' => $sourceText,
            'link_rewrite' => $linkRewrite,
        ]);

        return $result;
    }

    /**
     * Generate all fields for a category in a single API call (batched)
     * Uses existing prompt templates from DB, combines them into structured JSON request
     *
     * @param int $idCategory
     * @param int $idLang
     * @param array $fields ['description', 'meta_title', 'meta_description', 'meta_keywords']
     * @param string $writeMode overwrite|fill_missing
     *
     * @return array ['success' => bool, 'results' => [...], 'error' => string, 'tokens' => int]
     */
    public function generateCategoryBatch($idCategory, $idLang, array $fields, $writeMode = 'fill_missing')
    {
        $t0 = microtime(true);

        $result = [
            'success' => false,
            'results' => [],
            'error' => '',
            'tokens' => 0,
            'skipped' => false,
            'skipped_fields' => [],
        ];

        // Load category
        $category = new Category((int) $idCategory, (int) $idLang);
        if (!Validate::isLoadedObject($category)) {
            $result['error'] = 'Category not found: ' . $idCategory;
            MlCategoryAiLogger::error('Category not found: ' . $idCategory);

            return $result;
        }

        $placeholder = new MlCategoryAiPlaceholder($idCategory, $idLang, $this->idShop);

        // Build combined prompt using existing templates
        $fieldPrompts = [];
        $skippedFields = [];
        $hasLinkRewrite = false;

        foreach ($fields as $fieldType) {
            // link_rewrite is generated locally from meta_title, not by AI
            if ($fieldType === Mlcategoryaidescription::FIELD_LINK_REWRITE) {
                $hasLinkRewrite = true;
                continue;
            }

            // Skip if fill_missing and field has content
            if ($writeMode === Mlcategoryaidescription::WRITE_MODE_FILL_MISSING) {
                $existing = $this->getFieldValue($category, $fieldType);
                if (!empty(trim(strip_tags($existing)))) {
                    $skippedFields[] = $fieldType;
                    continue;
                }
            }

            // Get the prompt template from DB (respects user customizations)
            $template = $this->getPromptTemplate($fieldType, $idLang);
            if (empty($template)) {
                MlCategoryAiLogger::warning('No prompt template for field=' . $fieldType . ' lang=' . $idLang);
                continue;
            }

            $resolvedPrompt = $placeholder->resolve($template);
            $fieldPrompts[$fieldType] = $resolvedPrompt;
        }

        $this->appendMtgCategoryPatternHints($fieldPrompts, (int) $idLang);

        // If all fields were skipped, return success
        if (empty($fieldPrompts)) {
            $result['success'] = true;
            $result['skipped'] = true;
            $result['skipped_fields'] = $skippedFields;

            // Still generate link_rewrite if requested and meta_title exists
            if ($hasLinkRewrite) {
                $linkResult = $this->generateLinkRewriteFromMetaTitle($idCategory, $idLang, $writeMode);
                $result['results'][Mlcategoryaidescription::FIELD_LINK_REWRITE] = $linkResult['success'] ? 'success' : 'failed';
            }

            MlCategoryAiLogger::debug('All fields skipped (fill_missing mode)', [
                'category_id' => $idCategory,
                'lang_id' => $idLang,
            ]);

            return $result;
        }

        // Build the combined JSON request
        $combinedPrompt = $this->buildCombinedJsonPrompt($fieldPrompts, $idLang, $placeholder);

        MlCategoryAiLogger::debug('generateCategoryBatch: calling generateJson', [
            'category_id' => $idCategory,
            'lang_id' => $idLang,
            'fields' => array_keys($fieldPrompts),
            'prompt_length' => strlen($combinedPrompt),
        ]);

        // Use response_format for guaranteed JSON
        $response = $this->client->generateJson($combinedPrompt);

        if ($response === false) {
            $result['error'] = $this->client->getLastError();
            MlCategoryAiLogger::error('generateCategoryBatch API error', [
                'category_id' => $idCategory,
                'error' => $result['error'],
            ]);

            return $result;
        }

        // Parse and save results (atomic: all or nothing)
        $saveResult = $this->parseAndSaveBatchResults($response, $category, $fieldPrompts, $idLang, $hasLinkRewrite, $writeMode);

        $saveResult['skipped_fields'] = $skippedFields;
        $saveResult['tokens'] = $this->client->getLastTokensUsed();

        $t1 = microtime(true);
        MlCategoryAiLogger::debug('generateCategoryBatch completed', [
            'category_id' => $idCategory,
            'lang_id' => $idLang,
            'success' => $saveResult['success'],
            'time_ms' => round(($t1 - $t0) * 1000),
            'tokens' => $saveResult['tokens'],
        ]);

        return $saveResult;
    }

    /**
     * Build combined prompt that embeds all field prompts and requests JSON output
     *
     * @param array $fieldPrompts Resolved prompts per field
     * @param int $idLang Language ID
     * @param MlCategoryAiPlaceholder $placeholder Placeholder resolver
     *
     * @return string Combined prompt
     */
    protected function buildCombinedJsonPrompt(array $fieldPrompts, $idLang, $placeholder)
    {
        $languageName = $placeholder->resolve('{language_name}');

        $prompt = "Generate SEO content in {$languageName}. For each field below, follow the specific instructions provided.\n\n";

        foreach ($fieldPrompts as $fieldType => $fieldPrompt) {
            $prompt .= "=== FIELD: {$fieldType} ===\n";
            $prompt .= $fieldPrompt . "\n\n";
        }

        $prompt .= "RESPONSE FORMAT:\n";
        $prompt .= "Return a JSON object with these exact keys: " . json_encode(array_keys($fieldPrompts)) . "\n";
        $prompt .= "Each value should be the generated content for that field.\n";
        $prompt .= "For 'description': use HTML tags (<p>, <h2>, <strong>, <ul>, <li>). Do NOT use Markdown.\n";
        if (isset($fieldPrompts[Mlcategoryaidescription::FIELD_SHORT_DESCRIPTION])) {
            $prompt .= "For 'short_description': a single plain-text line only (no HTML), as instructed in that field block.\n";
        }
        $prompt .= "For other fields: plain text only, no HTML, no Markdown.\n";

        return $prompt;
    }

    /**
     * Append MTG_SAVED_PATTERNS length/template hints for meta_* fields (shared).
     *
     * @param array<string, string> $fieldPrompts
     * @param array<string, array{active: bool, value: string, length: int}> $rules
     *
     * @return void
     */
    protected function appendMtgPatternHintsToFieldPrompts(array &$fieldPrompts, array $rules)
    {
        if (empty($rules)) {
            return;
        }

        foreach ($fieldPrompts as $fieldType => &$resolved) {
            if (empty($rules[$fieldType]) || !$rules[$fieldType]['active']) {
                continue;
            }
            $len = (int) $rules[$fieldType]['length'];
            $pat = $rules[$fieldType]['value'];
            $resolved .= "\n\n--- Metatags Generator (shop pattern for this language) ---\n";
            $resolved .= 'Maximum length (characters): ' . $len . "\n";
            $resolved .= 'Reference template (MTG placeholders e.g. {name}, {description}, {description_short}, {auto_keywords}, {shop_name}): ' . $pat . "\n";
            $resolved .= "Respect the length limit. Mirror the marketing intent and 'slots' of the template using the context in the prompts above (plain text for meta fields).\n";
        }
        unset($resolved);
    }

    /**
     * Append Metatags Generator (MTG_SAVED_PATTERNS) constraints for category meta fields.
     *
     * @param array<string, string> $fieldPrompts
     * @param int $idLang
     *
     * @return void
     */
    protected function appendMtgCategoryPatternHints(array &$fieldPrompts, $idLang)
    {
        require_once dirname(__FILE__) . '/MtgSavedPatternsParser.php';
        $rules = MtgSavedPatternsParser::getMtgRulesForMlCategoryFields((int) $idLang);
        $this->appendMtgPatternHintsToFieldPrompts($fieldPrompts, $rules);
    }

    /**
     * Append Metatags Generator (MTG_SAVED_PATTERNS) constraints for manufacturer meta fields
     * so the LLM sees the same length / template structure as the SEO module.
     *
     * @param array<string, string> $fieldPrompts
     * @param int $idLang
     *
     * @return void
     */
    protected function appendMtgManufacturerPatternHints(array &$fieldPrompts, $idLang)
    {
        require_once dirname(__FILE__) . '/MtgSavedPatternsParser.php';
        $rules = MtgSavedPatternsParser::getMtgRulesForMlManufacturerFields((int) $idLang);
        $this->appendMtgPatternHintsToFieldPrompts($fieldPrompts, $rules);
    }

    /**
     * Parse JSON response and save all fields to category
     * Atomic: if any field is missing, fail entirely without saving anything
     *
     * @param array $jsonData Parsed JSON from OpenAI
     * @param Category $category
     * @param array $fieldPrompts Fields that were requested (excludes link_rewrite)
     * @param int $idLang
     * @param bool $hasLinkRewrite Whether link_rewrite should be generated
     * @param string $writeMode Write mode for link_rewrite
     *
     * @return array Result with success, results, error
     */
    protected function parseAndSaveBatchResults($jsonData, $category, $fieldPrompts, $idLang, $hasLinkRewrite, $writeMode)
    {
        $result = [
            'success' => false,
            'results' => [],
            'error' => '',
        ];

        // First, validate all required fields are present
        foreach (array_keys($fieldPrompts) as $fieldType) {
            if (!isset($jsonData[$fieldType])) {
                MlCategoryAiLogger::error('Missing field in JSON response: ' . $fieldType, [
                    'category_id' => $category->id,
                    'lang_id' => $idLang,
                    'received_keys' => array_keys($jsonData),
                ]);
                $result['error'] = 'Missing field in JSON response: ' . $fieldType;

                return $result;
            }
        }

        // All fields present, now save them
        foreach (array_keys($fieldPrompts) as $fieldType) {
            $content = $this->cleanContent($jsonData[$fieldType], $fieldType);
            $updateResult = $this->updateCategoryField($category, $fieldType, $content, $idLang);
            $result['results'][$fieldType] = $updateResult ? 'success' : 'failed';

            // Log generation
            if ($updateResult) {
                $this->logGeneration($category->id, $idLang, $fieldType, 'success', '', $this->client->getLastTokensUsed());
            } else {
                $this->logGeneration($category->id, $idLang, $fieldType, 'error', 'Failed to update field');
            }
        }

        // Generate link_rewrite from meta_title if it was in original request
        if ($hasLinkRewrite) {
            $linkResult = $this->generateLinkRewriteFromMetaTitle($category->id, $idLang, $writeMode);
            $result['results'][Mlcategoryaidescription::FIELD_LINK_REWRITE] = $linkResult['success'] ? 'success' : 'failed';
        }

        $result['success'] = true;

        return $result;
    }

    /**
     * Generate all manufacturer fields in one JSON API call.
     *
     * @param int $idManufacturer
     * @param int $idLang
     * @param array $fields
     * @param string $writeMode
     *
     * @return array
     */
    public function generateManufacturerBatch($idManufacturer, $idLang, array $fields, $writeMode = 'fill_missing')
    {
        $t0 = microtime(true);

        $result = [
            'success' => false,
            'results' => [],
            'error' => '',
            'tokens' => 0,
            'skipped' => false,
            'skipped_fields' => [],
        ];

        $manufacturer = new Manufacturer((int) $idManufacturer, (int) $idLang);
        if (!Validate::isLoadedObject($manufacturer)) {
            $result['error'] = 'Manufacturer not found: ' . $idManufacturer;
            MlCategoryAiLogger::error('Manufacturer not found: ' . $idManufacturer);

            return $result;
        }

        $placeholder = new MlManufacturerAiPlaceholder($idManufacturer, $idLang, $this->idShop);
        $fieldPrompts = [];
        $skippedFields = [];

        foreach ($fields as $fieldType) {
            if ($fieldType === Mlcategoryaidescription::FIELD_LINK_REWRITE) {
                continue;
            }
            if ($fieldType === Mlcategoryaidescription::FIELD_META_KEYWORDS
                && !self::hasManufacturerMetaKeywordsSupport()) {
                $skippedFields[] = $fieldType;
                continue;
            }

            if ($writeMode === Mlcategoryaidescription::WRITE_MODE_FILL_MISSING) {
                $existing = $this->getManufacturerLangFieldFromDb((int) $idManufacturer, (int) $idLang, $fieldType);
                if (!empty(trim(strip_tags($existing)))) {
                    $skippedFields[] = $fieldType;
                    continue;
                }
            }

            $template = $this->getPromptTemplate($fieldType, $idLang, Mlcategoryaidescription::ENTITY_MANUFACTURER);
            if (empty($template)) {
                MlCategoryAiLogger::warning('No manufacturer prompt for field=' . $fieldType . ' lang=' . $idLang);
                continue;
            }

            $fieldPrompts[$fieldType] = $placeholder->resolve($template);
        }

        $this->appendMtgManufacturerPatternHints($fieldPrompts, (int) $idLang);

        if (empty($fieldPrompts)) {
            $result['success'] = true;
            $result['skipped'] = true;
            $result['skipped_fields'] = $skippedFields;

            return $result;
        }

        $combinedPrompt = $this->buildCombinedJsonPrompt($fieldPrompts, $idLang, $placeholder);
        $response = $this->client->generateJson($combinedPrompt);

        if ($response === false) {
            $result['error'] = $this->client->getLastError();

            return $result;
        }

        $saveResult = $this->parseAndSaveManufacturerBatchResults($response, $manufacturer, $fieldPrompts, $idLang);
        $saveResult['skipped_fields'] = $skippedFields;
        $saveResult['tokens'] = $this->client->getLastTokensUsed();
        MlCategoryAiLogger::debug('generateManufacturerBatch completed', [
            'manufacturer_id' => $idManufacturer,
            'lang_id' => $idLang,
            'ms' => round((microtime(true) - $t0) * 1000),
        ]);

        return $saveResult;
    }

    /**
     * @param array $jsonData
     * @param Manufacturer $manufacturer
     * @param array $fieldPrompts
     * @param int $idLang
     *
     * @return array
     */
    protected function parseAndSaveManufacturerBatchResults($jsonData, $manufacturer, array $fieldPrompts, $idLang)
    {
        $result = [
            'success' => false,
            'results' => [],
            'error' => '',
        ];

        foreach (array_keys($fieldPrompts) as $fieldType) {
            if (!isset($jsonData[$fieldType])) {
                $result['error'] = 'Missing field in JSON response: ' . $fieldType;

                return $result;
            }
        }

        foreach (array_keys($fieldPrompts) as $fieldType) {
            $content = $this->cleanContent($jsonData[$fieldType], $fieldType);
            $updateResult = $this->updateManufacturerField($manufacturer, $fieldType, $content, $idLang);
            $result['results'][$fieldType] = $updateResult ? 'success' : 'failed';

            if ($updateResult) {
                $this->logGenerationEntry(
                    Mlcategoryaidescription::ENTITY_MANUFACTURER,
                    $idLang,
                    $fieldType,
                    'success',
                    '',
                    $this->client->getLastTokensUsed(),
                    null,
                    $manufacturer->id
                );
            } else {
                $this->logGenerationEntry(
                    Mlcategoryaidescription::ENTITY_MANUFACTURER,
                    $idLang,
                    $fieldType,
                    'error',
                    'Failed to update field',
                    0,
                    null,
                    $manufacturer->id
                );
            }
        }

        $result['success'] = true;

        return $result;
    }

    /**
     * Translate manufacturer fields (batched Google Translate).
     *
     * @param int $idManufacturer
     * @param int $idTargetLang
     * @param array $fields
     * @param int $idSourceLang
     * @param string $writeMode
     *
     * @return array
     */
    public function translateManufacturerBatch($idManufacturer, $idTargetLang, array $fields, $idSourceLang, $writeMode = 'fill_missing')
    {
        $result = [
            'success' => false,
            'results' => [],
            'chars_translated' => 0,
            'skipped' => false,
            'skipped_fields' => [],
            'error' => '',
        ];

        $sourceM = new Manufacturer((int) $idManufacturer, (int) $idSourceLang);
        $targetM = new Manufacturer((int) $idManufacturer, (int) $idTargetLang);

        if (!Validate::isLoadedObject($sourceM) || !Validate::isLoadedObject($targetM)) {
            $result['error'] = 'Manufacturer not found';

            return $result;
        }

        $textsToTranslate = [];
        $skippedFields = [];

        foreach ($fields as $fieldType) {
            if ($fieldType === Mlcategoryaidescription::FIELD_META_KEYWORDS
                && !self::hasManufacturerMetaKeywordsSupport()) {
                $skippedFields[] = $fieldType;
                continue;
            }

            if ($writeMode === Mlcategoryaidescription::WRITE_MODE_FILL_MISSING) {
                $existing = $this->getManufacturerLangFieldFromDb((int) $idManufacturer, (int) $idTargetLang, $fieldType);
                if (!empty(trim(strip_tags($existing)))) {
                    $skippedFields[] = $fieldType;
                    continue;
                }
            }

            $sourceText = $this->getManufacturerLangFieldFromDb((int) $idManufacturer, (int) $idSourceLang, $fieldType);
            if (!empty(trim(strip_tags($sourceText)))) {
                $textsToTranslate[$fieldType] = $sourceText;
            }
        }

        $result['skipped_fields'] = $skippedFields;

        if (empty($textsToTranslate)) {
            $result['success'] = true;
            $result['skipped'] = true;

            return $result;
        }

        $sourceIso = Language::getIsoById($idSourceLang);
        $targetIso = Language::getIsoById($idTargetLang);

        if (!$sourceIso || !$targetIso) {
            $result['error'] = 'Could not get language ISO codes';

            return $result;
        }

        $translator = MlCategoryAiTranslator::createFromConfig($this->module);
        $translations = $translator->translateBatch(
            array_values($textsToTranslate),
            $sourceIso,
            $targetIso,
            'html'
        );

        if ($translations === false) {
            $result['error'] = 'Translation failed: ' . $translator->getLastError();

            return $result;
        }

        $fieldKeys = array_keys($textsToTranslate);
        foreach ($translations as $index => $translatedText) {
            $fieldType = $fieldKeys[$index];
            if ($fieldType !== Mlcategoryaidescription::FIELD_DESCRIPTION) {
                $translatedText = strip_tags($translatedText);
                $translatedText = html_entity_decode($translatedText, ENT_QUOTES, 'UTF-8');
            }

            $updateResult = $this->updateManufacturerField($targetM, $fieldType, $translatedText, $idTargetLang);
            $result['results'][$fieldType] = $updateResult ? 'success' : 'failed';
        }

        $result['success'] = true;
        $result['chars_translated'] = $translator->getLastCharactersTranslated();

        return $result;
    }

    /**
     * WHERE clause for manufacturer_lang row (multistore-aware).
     *
     * @param int $idManufacturer
     * @param int $idLang
     *
     * @return string
     */
    protected function manufacturerLangSqlWhere($idManufacturer, $idLang)
    {
        $w = '`id_manufacturer` = ' . (int) $idManufacturer . ' AND `id_lang` = ' . (int) $idLang;
        if (self::manufacturerLangTableHasIdShop()) {
            $w .= ' AND `id_shop` = ' . (int) $this->idShop;
        }

        return $w;
    }

    /**
     * Ensure a manufacturer_lang row exists so UPDATE is not a no-op.
     *
     * @param int $idManufacturer
     * @param int $idLang
     *
     * @return bool
     */
    protected function ensureManufacturerLangRowExists($idManufacturer, $idLang)
    {
        $idManufacturer = (int) $idManufacturer;
        $idLang = (int) $idLang;
        if ($idManufacturer <= 0 || $idLang <= 0) {
            return false;
        }

        $exists = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'manufacturer_lang`
            WHERE ' . $this->manufacturerLangSqlWhere($idManufacturer, $idLang)
        );
        if ($exists > 0) {
            return true;
        }

        $data = [
            'id_manufacturer' => $idManufacturer,
            'id_lang' => $idLang,
            'description' => '',
            'short_description' => '',
            'meta_title' => '',
            'meta_description' => '',
        ];
        if (self::hasManufacturerMetaKeywordsSupport()) {
            $data['meta_keywords'] = '';
        }
        if (self::manufacturerLangTableHasIdShop()) {
            $data['id_shop'] = (int) $this->idShop;
        }

        $inserted = (bool) Db::getInstance()->insert('manufacturer_lang', $data);
        if ($inserted) {
            return true;
        }

        $existsAfter = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'manufacturer_lang`
            WHERE ' . $this->manufacturerLangSqlWhere($idManufacturer, $idLang)
        );

        return $existsAfter > 0;
    }

    /**
     * Read field text for exact manufacturer + language from DB (no ObjectModel fallback).
     *
     * @param int $idManufacturer
     * @param int $idLang
     * @param string $fieldType
     *
     * @return string
     */
    protected function getManufacturerLangFieldFromDb($idManufacturer, $idLang, $fieldType)
    {
        $fieldMap = [
            Mlcategoryaidescription::FIELD_DESCRIPTION => 'description',
            Mlcategoryaidescription::FIELD_SHORT_DESCRIPTION => 'short_description',
            Mlcategoryaidescription::FIELD_META_TITLE => 'meta_title',
            Mlcategoryaidescription::FIELD_META_DESCRIPTION => 'meta_description',
            Mlcategoryaidescription::FIELD_META_KEYWORDS => 'meta_keywords',
        ];
        if (!isset($fieldMap[$fieldType])) {
            return '';
        }
        if ($fieldType === Mlcategoryaidescription::FIELD_META_KEYWORDS && !self::hasManufacturerMetaKeywordsSupport()) {
            return '';
        }

        $col = $fieldMap[$fieldType];
        $sql = 'SELECT `' . bqSQL($col) . '` FROM `' . _DB_PREFIX_ . 'manufacturer_lang`
                WHERE ' . $this->manufacturerLangSqlWhere((int) $idManufacturer, (int) $idLang);
        $val = Db::getInstance()->getValue($sql);

        return $val !== false && $val !== null ? (string) $val : '';
    }

    /**
     * @param Manufacturer $manufacturer
     * @param string $fieldType
     *
     * @return string
     */
    protected function getFieldValueManufacturer($manufacturer, $fieldType)
    {
        switch ($fieldType) {
            case Mlcategoryaidescription::FIELD_DESCRIPTION:
                return $manufacturer->description ?: '';
            case Mlcategoryaidescription::FIELD_SHORT_DESCRIPTION:
                return $manufacturer->short_description ?: '';
            case Mlcategoryaidescription::FIELD_META_TITLE:
                return $manufacturer->meta_title ?: '';
            case Mlcategoryaidescription::FIELD_META_DESCRIPTION:
                return $manufacturer->meta_description ?: '';
            case Mlcategoryaidescription::FIELD_META_KEYWORDS:
                return $manufacturer->meta_keywords ?: '';
            default:
                return '';
        }
    }

    /**
     * @param Manufacturer $manufacturer
     * @param string $fieldType
     * @param string $content
     * @param int $idLang
     *
     * @return bool
     */
    protected function updateManufacturerField($manufacturer, $fieldType, $content, $idLang)
    {
        if ($fieldType === Mlcategoryaidescription::FIELD_META_KEYWORDS && !self::hasManufacturerMetaKeywordsSupport()) {
            return true;
        }

        $fieldMap = [
            Mlcategoryaidescription::FIELD_DESCRIPTION => 'description',
            Mlcategoryaidescription::FIELD_SHORT_DESCRIPTION => 'short_description',
            Mlcategoryaidescription::FIELD_META_TITLE => 'meta_title',
            Mlcategoryaidescription::FIELD_META_DESCRIPTION => 'meta_description',
            Mlcategoryaidescription::FIELD_META_KEYWORDS => 'meta_keywords',
        ];

        if (!isset($fieldMap[$fieldType])) {
            return false;
        }

        $dbField = $fieldMap[$fieldType];
        $isHtml = in_array($fieldType, [
            Mlcategoryaidescription::FIELD_DESCRIPTION,
            Mlcategoryaidescription::FIELD_SHORT_DESCRIPTION,
        ], true);

        if (!$this->ensureManufacturerLangRowExists((int) $manufacturer->id, (int) $idLang)) {
            MlCategoryAiLogger::error('ensureManufacturerLangRowExists failed for manufacturer_lang', [
                'id_manufacturer' => (int) $manufacturer->id,
                'id_lang' => (int) $idLang,
            ]);

            return false;
        }

        return Db::getInstance()->update(
            'manufacturer_lang',
            [
                $dbField => pSQL($content, $isHtml),
            ],
            $this->manufacturerLangSqlWhere((int) $manufacturer->id, (int) $idLang)
        );
    }

    /**
     * Translate all fields for a category in a single API call (batched)
     *
     * @param int $idCategory
     * @param int $idTargetLang Target language ID
     * @param array $fields Fields to translate
     * @param int $idSourceLang Source language ID (primary language)
     * @param string $writeMode overwrite|fill_missing
     *
     * @return array Result with per-field status
     */
    public function translateCategoryBatch($idCategory, $idTargetLang, array $fields, $idSourceLang, $writeMode = 'fill_missing')
    {
        $result = [
            'success' => false,
            'results' => [],
            'chars_translated' => 0,
            'skipped' => false,
            'skipped_fields' => [],
            'error' => '',
        ];

        $sourceCategory = new Category((int) $idCategory, (int) $idSourceLang);
        $targetCategory = new Category((int) $idCategory, (int) $idTargetLang);

        if (!Validate::isLoadedObject($sourceCategory)) {
            $result['error'] = 'Source category not found: ' . $idCategory;

            return $result;
        }

        if (!Validate::isLoadedObject($targetCategory)) {
            $result['error'] = 'Target category not found: ' . $idCategory;

            return $result;
        }

        // Collect texts to translate (skip fields with existing content in fill_missing mode)
        $textsToTranslate = [];
        $skippedFields = [];
        $hasLinkRewrite = false;

        foreach ($fields as $fieldType) {
            if ($fieldType === Mlcategoryaidescription::FIELD_LINK_REWRITE) {
                $hasLinkRewrite = true;
                continue;  // Generated locally, not translated
            }

            if ($writeMode === Mlcategoryaidescription::WRITE_MODE_FILL_MISSING) {
                $existing = $this->getFieldValue($targetCategory, $fieldType);
                if (!empty(trim(strip_tags($existing)))) {
                    $skippedFields[] = $fieldType;
                    continue;
                }
            }

            $sourceText = $this->getFieldValue($sourceCategory, $fieldType);
            if (!empty(trim(strip_tags($sourceText)))) {
                $textsToTranslate[$fieldType] = $sourceText;
            }
        }

        $result['skipped_fields'] = $skippedFields;

        if (empty($textsToTranslate)) {
            $result['success'] = true;
            $result['skipped'] = true;

            // Still generate link_rewrite if requested
            if ($hasLinkRewrite) {
                $linkResult = $this->generateLinkRewriteFromMetaTitle($idCategory, $idTargetLang, $writeMode);
                $result['results'][Mlcategoryaidescription::FIELD_LINK_REWRITE] = $linkResult['success'] ? 'success' : 'failed';
            }

            return $result;
        }

        // Get ISO codes
        $sourceIso = Language::getIsoById($idSourceLang);
        $targetIso = Language::getIsoById($idTargetLang);

        if (!$sourceIso || !$targetIso) {
            $result['error'] = 'Could not get language ISO codes';

            return $result;
        }

        // Single API call for all fields (use HTML format, strip later for non-HTML fields)
        $translator = MlCategoryAiTranslator::createFromConfig($this->module);
        $translations = $translator->translateBatch(
            array_values($textsToTranslate),
            $sourceIso,
            $targetIso,
            'html'
        );

        if ($translations === false) {
            $result['error'] = 'Translation failed: ' . $translator->getLastError();
            MlCategoryAiLogger::error('translateCategoryBatch failed', [
                'category_id' => $idCategory,
                'source_lang' => $sourceIso,
                'target_lang' => $targetIso,
                'error' => $translator->getLastError(),
            ]);

            return $result;
        }

        // Map translations back to fields and save
        $fieldKeys = array_keys($textsToTranslate);
        foreach ($translations as $index => $translatedText) {
            $fieldType = $fieldKeys[$index];

            // Strip HTML from non-description fields (purify after translation)
            if ($fieldType !== Mlcategoryaidescription::FIELD_DESCRIPTION) {
                $translatedText = strip_tags($translatedText);
                $translatedText = html_entity_decode($translatedText, ENT_QUOTES, 'UTF-8');
            }

            $updateResult = $this->updateCategoryField($targetCategory, $fieldType, $translatedText, $idTargetLang);
            $result['results'][$fieldType] = $updateResult ? 'success' : 'failed';
        }

        // Generate link_rewrite locally if included in fields
        if ($hasLinkRewrite) {
            $linkResult = $this->generateLinkRewriteFromMetaTitle($idCategory, $idTargetLang, $writeMode);
            $result['results'][Mlcategoryaidescription::FIELD_LINK_REWRITE] = $linkResult['success'] ? 'success' : 'failed';
        }

        $result['success'] = true;
        $result['chars_translated'] = $translator->getLastCharactersTranslated();

        MlCategoryAiLogger::debug('translateCategoryBatch completed', [
            'category_id' => $idCategory,
            'source_lang' => $sourceIso,
            'target_lang' => $targetIso,
            'fields_translated' => count($translations),
            'chars' => $result['chars_translated'],
        ]);

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
    protected function getPromptTemplate($fieldType, $idLang, $entityType = null)
    {
        if ($entityType === null) {
            $entityType = Mlcategoryaidescription::ENTITY_CATEGORY;
        }
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
        $query->where('pt.`entity_type` = "' . pSQL($entityType) . '"');
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
            $query->where('pt.`entity_type` = "' . pSQL($entityType) . '"');
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
            $query->where('pt.`entity_type` = "' . pSQL($entityType) . '"');
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
            case Mlcategoryaidescription::FIELD_META_DESCRIPTION:
            case Mlcategoryaidescription::FIELD_META_KEYWORDS:
            case Mlcategoryaidescription::FIELD_SHORT_DESCRIPTION:
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
    protected function logGenerationEntry($entityType, $idLang, $fieldType, $status, $errorMessage = '', $tokensUsed = 0, $idCategory = null, $idManufacturer = null)
    {
        $data = [
            'entity_type' => pSQL($entityType),
            'id_lang' => (int) $idLang,
            'id_shop' => (int) $this->idShop,
            'field_type' => pSQL($fieldType),
            'generated_at' => date('Y-m-d H:i:s'),
            'model_used' => pSQL($this->client->getModel()),
            'prompt_hash' => '',
            'tokens_used' => (int) $tokensUsed,
            'status' => pSQL($status),
            'error_message' => pSQL($errorMessage),
        ];
        if ($idCategory !== null && (int) $idCategory > 0) {
            $data['id_category'] = (int) $idCategory;
        }
        if ($idManufacturer !== null && (int) $idManufacturer > 0) {
            $data['id_manufacturer'] = (int) $idManufacturer;
        }

        return Db::getInstance()->insert('mlcategoryai_generation_log', $data);
    }

    /**
     * Log generation for a category (wrapper).
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
        return $this->logGenerationEntry(
            Mlcategoryaidescription::ENTITY_CATEGORY,
            $idLang,
            $fieldType,
            $status,
            $errorMessage,
            $tokensUsed,
            $idCategory,
            null
        );
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
