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

// Set error handler to return JSON errors instead of HTML
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Clear any output
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'PHP Error: ' . $errstr . ' in ' . basename($errfile) . ':' . $errline,
    ]);
    exit;
});

// Set exception handler
set_exception_handler(function ($e) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Exception: ' . $e->getMessage(),
    ]);
    exit;
});

if (!defined('_PS_VERSION_')) {
    exit;
}

class MlcategoryaidescriptionAjaxModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool Disable SSL requirement for AJAX
     */
    public $ssl = true;

    /**
     * @var bool Disable display of header/footer
     */
    public $display_header = false;
    public $display_footer = false;
    public $display_column_left = false;
    public $display_column_right = false;

    /**
     * Initialize controller
     */
    public function init()
    {
        // Suppress any PHP errors/warnings from being output as HTML
        @ini_set('display_errors', 'Off');

        parent::init();

        // Set JSON content type
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * Process AJAX requests
     */
    public function postProcess()
    {
        try {
            // Validate admin token for security
            if (!$this->validateAdminToken()) {
                $this->ajaxDie([
                    'success' => false,
                    'error' => 'Invalid security token',
                ]);
            }

            $action = Tools::getValue('action');

            switch ($action) {
                case 'createJob':
                    $this->processCreateJob();
                    break;

                case 'processJob':
                $this->processProcessJob();
                break;

            case 'pauseJob':
                $this->processPauseJob();
                break;

            case 'resumeJob':
                $this->processResumeJob();
                break;

            case 'cancelJob':
                $this->processCancelJob();
                break;

            case 'getJobStatus':
                $this->processGetJobStatus();
                break;

            case 'testConnection':
                $this->processTestConnection();
                break;

            case 'previewPrompt':
                $this->processPreviewPrompt();
                break;

            case 'savePrompts':
                $this->processSavePrompts();
                break;

            default:
                $this->ajaxDie([
                    'success' => false,
                    'error' => 'Unknown action: ' . $action,
                ]);
            }
        } catch (Exception $e) {
            $this->ajaxDie([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate admin token
     *
     * @return bool
     */
    protected function validateAdminToken()
    {
        $token = Tools::getValue('token');

        // Check if request comes from admin with valid token
        if (empty($token)) {
            return false;
        }

        // Check if there's an active admin employee session
        $cookie = new Cookie('psAdmin');
        if ($cookie->id_employee) {
            $employee = new Employee((int) $cookie->id_employee);
            if (Validate::isLoadedObject($employee) && $employee->active) {
                // Generate expected token matching the module's getAjaxToken method
                $expectedToken = Tools::hash($this->module->name . (int) $cookie->id_employee . _COOKIE_KEY_);

                if ($token === $expectedToken) {
                    return true;
                }
            }
        }

        // Fallback: Accept module's configured cron token
        $cronToken = Configuration::get('MLCATEGORYAI_CRON_TOKEN');
        if (!empty($cronToken) && $token === $cronToken) {
            return true;
        }

        return false;
    }

    /**
     * Create a new batch job
     */
    protected function processCreateJob()
    {
        $categoryIds = Tools::getValue('category_ids');
        $languageIds = Tools::getValue('language_ids');
        $fieldsToGenerate = Tools::getValue('fields');
        $writeMode = Tools::getValue('write_mode', 'fill_missing');

        // Validate inputs
        if (empty($categoryIds) || !is_array($categoryIds)) {
            $this->ajaxDie([
                'success' => false,
                'error' => 'No categories selected',
            ]);
        }

        if (empty($languageIds) || !is_array($languageIds)) {
            $this->ajaxDie([
                'success' => false,
                'error' => 'No languages selected',
            ]);
        }

        if (empty($fieldsToGenerate) || !is_array($fieldsToGenerate)) {
            $this->ajaxDie([
                'success' => false,
                'error' => 'No fields selected',
            ]);
        }

        // Validate write mode
        $validModes = [
            Mlcategoryaidescription::WRITE_MODE_OVERWRITE,
            Mlcategoryaidescription::WRITE_MODE_FILL_MISSING,
        ];
        if (!in_array($writeMode, $validModes)) {
            $writeMode = Mlcategoryaidescription::WRITE_MODE_FILL_MISSING;
        }

        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiJobQueue.php';

        $jobQueue = new MlCategoryAiJobQueue();
        $idJob = $jobQueue->createJob(
            $categoryIds,
            $languageIds,
            $fieldsToGenerate,
            $writeMode
        );

        if (!$idJob) {
            $this->ajaxDie([
                'success' => false,
                'error' => 'Failed to create job',
            ]);
        }

        $this->ajaxDie([
            'success' => true,
            'job_id' => $idJob,
            'message' => 'Job created successfully',
        ]);
    }

    /**
     * Process next batch of a job
     */
    protected function processProcessJob()
    {
        $idJob = (int) Tools::getValue('job_id');

        if (!$idJob) {
            $this->ajaxDie([
                'success' => false,
                'error' => 'Invalid job ID',
            ]);
        }

        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiJobQueue.php';

        $batchSize = (int) Configuration::get(Mlcategoryaidescription::CONFIG_BATCH_SIZE);
        if ($batchSize < 1) {
            $batchSize = 5;
        }

        $jobQueue = new MlCategoryAiJobQueue();
        $result = $jobQueue->processNextBatch($idJob, $this->module, $batchSize);

        $this->ajaxDie($result);
    }

    /**
     * Pause a running job
     */
    protected function processPauseJob()
    {
        $idJob = (int) Tools::getValue('job_id');

        if (!$idJob) {
            $this->ajaxDie([
                'success' => false,
                'error' => 'Invalid job ID',
            ]);
        }

        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiJobQueue.php';

        $jobQueue = new MlCategoryAiJobQueue();
        $result = $jobQueue->pauseJob($idJob);

        $this->ajaxDie([
            'success' => $result,
            'message' => $result ? 'Job paused' : 'Failed to pause job',
        ]);
    }

    /**
     * Resume a paused job
     */
    protected function processResumeJob()
    {
        $idJob = (int) Tools::getValue('job_id');

        if (!$idJob) {
            $this->ajaxDie([
                'success' => false,
                'error' => 'Invalid job ID',
            ]);
        }

        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiJobQueue.php';

        $jobQueue = new MlCategoryAiJobQueue();
        $result = $jobQueue->resumeJob($idJob);

        $this->ajaxDie([
            'success' => $result,
            'message' => $result ? 'Job resumed' : 'Failed to resume job',
        ]);
    }

    /**
     * Cancel a job
     */
    protected function processCancelJob()
    {
        $idJob = (int) Tools::getValue('job_id');

        if (!$idJob) {
            $this->ajaxDie([
                'success' => false,
                'error' => 'Invalid job ID',
            ]);
        }

        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiJobQueue.php';

        $jobQueue = new MlCategoryAiJobQueue();
        $result = $jobQueue->cancelJob($idJob);

        $this->ajaxDie([
            'success' => $result,
            'message' => $result ? 'Job cancelled' : 'Failed to cancel job',
        ]);
    }

    /**
     * Get job status
     */
    protected function processGetJobStatus()
    {
        $idJob = (int) Tools::getValue('job_id');

        if (!$idJob) {
            $this->ajaxDie([
                'success' => false,
                'error' => 'Invalid job ID',
            ]);
        }

        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiJobQueue.php';

        $jobQueue = new MlCategoryAiJobQueue();
        $job = $jobQueue->getJob($idJob);

        if (!$job) {
            $this->ajaxDie([
                'success' => false,
                'error' => 'Job not found',
            ]);
        }

        $this->ajaxDie([
            'success' => true,
            'job' => [
                'id' => $job['id_job'],
                'status' => $job['status'],
                'total_items' => $job['total_items'],
                'processed_items' => $job['processed_items'],
                'failed_items' => $job['failed_items'],
                'progress_percent' => $job['total_items'] > 0
                    ? round(($job['processed_items'] / $job['total_items']) * 100, 1)
                    : 0,
                'created_at' => $job['created_at'],
                'started_at' => $job['started_at'],
                'completed_at' => $job['completed_at'],
            ],
        ]);
    }

    /**
     * Test API connection
     */
    protected function processTestConnection()
    {
        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiClient.php';

        $client = MlCategoryAiClient::createFromConfig($this->module);
        $result = $client->testConnection();

        $this->ajaxDie([
            'success' => $result,
            'message' => $result ? 'API connection successful' : $client->getLastError(),
        ]);
    }

    /**
     * Preview resolved prompt
     */
    protected function processPreviewPrompt()
    {
        $idCategory = (int) Tools::getValue('id_category');
        $idLang = (int) Tools::getValue('id_lang');
        $promptTemplate = Tools::getValue('prompt_template');

        if (!$idCategory || !$idLang) {
            $this->ajaxDie([
                'success' => false,
                'error' => 'Category and language are required',
            ]);
        }

        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiPlaceholder.php';

        $placeholder = new MlCategoryAiPlaceholder($idCategory, $idLang);
        $resolvedPrompt = $placeholder->resolve($promptTemplate);

        // Append language instruction
        $languageName = $placeholder->resolve('{language_name}');
        $resolvedPrompt .= PHP_EOL . PHP_EOL . 'IMPORTANT: You MUST write your response in ' . $languageName . '.';

        $this->ajaxDie([
            'success' => true,
            'resolved_prompt' => $resolvedPrompt,
        ]);
    }

    /**
     * Save prompt templates
     */
    protected function processSavePrompts()
    {
        $promptsJson = Tools::getValue('prompts');
        $prompts = json_decode($promptsJson, true);

        if (empty($prompts) || !is_array($prompts)) {
            $this->ajaxDie([
                'success' => false,
                'error' => 'Invalid prompts data',
            ]);
        }

        $idShop = (int) Shop::getContextShopID();
        $db = Db::getInstance();

        foreach ($prompts as $fieldType => $languages) {
            // Get or create prompt template for this field type
            $idTemplate = (int) $db->getValue(
                'SELECT id_prompt_template FROM `' . _DB_PREFIX_ . 'mlcategoryai_prompt_template`
                WHERE field_type = "' . pSQL($fieldType) . '" AND id_shop = ' . $idShop
            );

            if (!$idTemplate) {
                // Create new template
                $db->insert('mlcategoryai_prompt_template', [
                    'id_shop' => $idShop,
                    'name' => 'Default ' . ucfirst($fieldType) . ' Prompt',
                    'field_type' => pSQL($fieldType),
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $idTemplate = (int) $db->Insert_ID();
            }

            // Save each language version
            foreach ($languages as $idLang => $template) {
                // Delete existing
                $db->delete(
                    'mlcategoryai_prompt_template_lang',
                    'id_prompt_template = ' . $idTemplate . ' AND id_lang = ' . (int) $idLang
                );

                // Insert new
                if (!empty(trim($template))) {
                    $db->insert('mlcategoryai_prompt_template_lang', [
                        'id_prompt_template' => $idTemplate,
                        'id_lang' => (int) $idLang,
                        'prompt_template' => pSQL($template, true),
                    ]);
                }
            }

            // Update timestamp
            $db->update('mlcategoryai_prompt_template', [
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id_prompt_template = ' . $idTemplate);
        }

        $this->ajaxDie([
            'success' => true,
            'message' => 'Prompts saved successfully',
        ]);
    }

    /**
     * Output JSON response and exit
     *
     * @param array $data
     */
    protected function ajaxDie($data)
    {
        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
