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

class AdminMlCategoryAiAjaxController extends ModuleAdminController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        // Disable debug mode output for AJAX
        @ini_set('display_errors', 'Off');
    }

    /**
     * Initialize - handle AJAX request early
     */
    public function init()
    {
        parent::init();

        // Process AJAX immediately
        if (Tools::getValue('ajax') || Tools::isSubmit('action')) {
            $this->ajax = true;
            $this->processAjaxRequest();
            exit;
        }
    }

    /**
     * Process AJAX requests
     */
    protected function processAjaxRequest()
    {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        $action = Tools::getValue('action');

        try {
            switch ($action) {
                case 'testConnection':
                    $this->handleTestConnection();
                    break;

                case 'createJob':
                    $this->handleCreateJob();
                    break;

                case 'processJob':
                    $this->handleProcessJob();
                    break;

                case 'pauseJob':
                    $this->handlePauseJob();
                    break;

                case 'resumeJob':
                    $this->handleResumeJob();
                    break;

                case 'cancelJob':
                    $this->handleCancelJob();
                    break;

                case 'savePrompts':
                    $this->handleSavePrompts();
                    break;

                case 'previewPrompt':
                    $this->handlePreviewPrompt();
                    break;

                default:
                    $this->jsonResponse(['success' => false, 'error' => 'Unknown action: ' . $action]);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
        }
    }

    /**
     * Test API connection
     */
    protected function handleTestConnection()
    {
        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiClient.php';

        $client = MlCategoryAiClient::createFromConfig($this->module);
        $result = $client->testConnection();

        $this->jsonResponse([
            'success' => $result,
            'message' => $result ? 'API connection successful' : $client->getLastError(),
        ]);
    }

    /**
     * Create a new batch job
     */
    protected function handleCreateJob()
    {
        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiJobQueue.php';

        $categoryIds = Tools::getValue('category_ids');
        $languageIds = Tools::getValue('language_ids');
        $fieldsToGenerate = Tools::getValue('fields');
        $writeMode = Tools::getValue('write_mode', 'fill_missing');

        if (empty($categoryIds) || !is_array($categoryIds)) {
            $this->jsonResponse(['success' => false, 'error' => 'No categories selected']);

            return;
        }

        if (empty($languageIds) || !is_array($languageIds)) {
            $this->jsonResponse(['success' => false, 'error' => 'No languages selected']);

            return;
        }

        if (empty($fieldsToGenerate) || !is_array($fieldsToGenerate)) {
            $this->jsonResponse(['success' => false, 'error' => 'No fields selected']);

            return;
        }

        $jobQueue = new MlCategoryAiJobQueue();
        $jobId = $jobQueue->createJob(
            array_map('intval', $categoryIds),
            array_map('intval', $languageIds),
            $fieldsToGenerate,
            $writeMode
        );

        if ($jobId) {
            $this->jsonResponse([
                'success' => true,
                'job_id' => $jobId,
                'message' => 'Job created successfully',
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'error' => 'Failed to create job']);
        }
    }

    /**
     * Process job batch
     */
    protected function handleProcessJob()
    {
        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiJobQueue.php';

        $jobId = (int) Tools::getValue('job_id');

        if (!$jobId) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid job ID']);

            return;
        }

        $jobQueue = new MlCategoryAiJobQueue();

        // Get batch size from config
        $batchSize = (int) Configuration::get('MLCATEGORYAI_BATCH_SIZE');
        if ($batchSize <= 0) {
            $batchSize = 5;
        }

        // Pass module instance - processNextBatch creates its own generator
        $result = $jobQueue->processNextBatch($jobId, $this->module, $batchSize);

        $this->jsonResponse($result);
    }

    /**
     * Pause job
     */
    protected function handlePauseJob()
    {
        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiJobQueue.php';

        $jobId = (int) Tools::getValue('job_id');
        $jobQueue = new MlCategoryAiJobQueue();
        $result = $jobQueue->pauseJob($jobId);

        $this->jsonResponse([
            'success' => $result,
            'message' => $result ? 'Job paused' : 'Failed to pause job',
        ]);
    }

    /**
     * Resume job
     */
    protected function handleResumeJob()
    {
        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiJobQueue.php';

        $jobId = (int) Tools::getValue('job_id');
        $jobQueue = new MlCategoryAiJobQueue();
        $result = $jobQueue->resumeJob($jobId);

        $this->jsonResponse([
            'success' => $result,
            'message' => $result ? 'Job resumed' : 'Failed to resume job',
        ]);
    }

    /**
     * Cancel job
     */
    protected function handleCancelJob()
    {
        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiJobQueue.php';

        $jobId = (int) Tools::getValue('job_id');
        $jobQueue = new MlCategoryAiJobQueue();
        $result = $jobQueue->cancelJob($jobId);

        $this->jsonResponse([
            'success' => $result,
            'message' => $result ? 'Job cancelled' : 'Failed to cancel job',
        ]);
    }

    /**
     * Save prompts
     */
    protected function handleSavePrompts()
    {
        $prompts = Tools::getValue('prompts');
        $idShop = (int) Shop::getContextShopID();

        if (empty($prompts) || !is_array($prompts)) {
            $this->jsonResponse(['success' => false, 'error' => 'No prompts data received']);

            return;
        }

        foreach ($prompts as $fieldType => $langPrompts) {
            $idTemplate = (int) Db::getInstance()->getValue(
                'SELECT `id_prompt_template` FROM `' . _DB_PREFIX_ . 'mlcategoryai_prompt_template`
                WHERE `field_type` = "' . pSQL($fieldType) . '" AND `id_shop` = ' . $idShop
            );

            if (!$idTemplate) {
                Db::getInstance()->insert('mlcategoryai_prompt_template', [
                    'id_shop' => $idShop,
                    'name' => 'Custom ' . ucfirst($fieldType) . ' Prompt',
                    'field_type' => pSQL($fieldType),
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $idTemplate = (int) Db::getInstance()->Insert_ID();
            }

            foreach ($langPrompts as $idLang => $promptText) {
                $exists = (int) Db::getInstance()->getValue(
                    'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'mlcategoryai_prompt_template_lang`
                    WHERE `id_prompt_template` = ' . $idTemplate . ' AND `id_lang` = ' . (int) $idLang
                );

                if ($exists) {
                    Db::getInstance()->update(
                        'mlcategoryai_prompt_template_lang',
                        ['prompt_template' => pSQL($promptText, true)],
                        '`id_prompt_template` = ' . $idTemplate . ' AND `id_lang` = ' . (int) $idLang
                    );
                } else {
                    Db::getInstance()->insert('mlcategoryai_prompt_template_lang', [
                        'id_prompt_template' => $idTemplate,
                        'id_lang' => (int) $idLang,
                        'prompt_template' => pSQL($promptText, true),
                    ]);
                }
            }

            Db::getInstance()->update(
                'mlcategoryai_prompt_template',
                ['updated_at' => date('Y-m-d H:i:s')],
                '`id_prompt_template` = ' . $idTemplate
            );
        }

        $this->jsonResponse(['success' => true, 'message' => 'Prompts saved successfully']);
    }

    /**
     * Preview prompt
     */
    protected function handlePreviewPrompt()
    {
        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiPlaceholder.php';

        $idCategory = (int) Tools::getValue('id_category');
        $idLang = (int) Tools::getValue('id_lang');
        $promptTemplate = Tools::getValue('prompt_template');

        if (!$idCategory || !$idLang) {
            $this->jsonResponse(['success' => false, 'error' => 'Category and language are required']);

            return;
        }

        $category = new Category($idCategory, $idLang);
        if (!Validate::isLoadedObject($category)) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid category']);

            return;
        }

        $placeholder = new MlCategoryAiPlaceholder($category, $idLang);
        $resolvedPrompt = $placeholder->resolve($promptTemplate);

        $this->jsonResponse([
            'success' => true,
            'resolved_prompt' => $resolvedPrompt,
        ]);
    }

    /**
     * Send JSON response and exit
     *
     * @param array $data
     */
    protected function jsonResponse($data)
    {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
