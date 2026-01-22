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

require_once dirname(__FILE__) . '/MlCategoryAiGenerator.php';
require_once dirname(__FILE__) . '/MlCategoryAiLogger.php';

/**
 * Job Queue manager for batch processing
 */
class MlCategoryAiJobQueue
{
    /**
     * Job statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * @var int Shop ID
     */
    protected $idShop;

    /**
     * Constructor
     *
     * @param int|null $idShop
     */
    public function __construct($idShop = null)
    {
        $this->idShop = $idShop ? (int) $idShop : (int) Shop::getContextShopID();
    }

    /**
     * Create a new batch job
     *
     * @param array $categoryIds
     * @param array $languageIds
     * @param array $fieldsToGenerate
     * @param string $writeMode
     *
     * @return int|false Job ID or false on failure
     */
    public function createJob($categoryIds, $languageIds, $fieldsToGenerate, $writeMode = 'fill_missing')
    {
        // Calculate total items: categories × languages × fields
        $totalItems = count($categoryIds) * count($languageIds) * count($fieldsToGenerate);

        $result = Db::getInstance()->insert('mlcategoryai_job_queue', [
            'id_shop' => (int) $this->idShop,
            'job_type' => 'batch_generation',
            'status' => self::STATUS_PENDING,
            'total_items' => (int) $totalItems,
            'processed_items' => 0,
            'failed_items' => 0,
            'category_ids' => pSQL(json_encode(array_map('intval', $categoryIds))),
            'language_ids' => pSQL(json_encode(array_map('intval', $languageIds))),
            'fields_to_generate' => pSQL(json_encode($fieldsToGenerate)),
            'write_mode' => pSQL($writeMode),
            'current_position' => 0,
            'last_processed_category_id' => null,
            'last_processed_lang_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'started_at' => null,
            'completed_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
            'error_log' => null,
        ]);

        if (!$result) {
            return false;
        }

        return (int) Db::getInstance()->Insert_ID();
    }

    /**
     * Get job by ID
     *
     * @param int $idJob
     *
     * @return array|null
     */
    public function getJob($idJob)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mlcategoryai_job_queue`
                WHERE `id_job` = ' . (int) $idJob . '
                AND `id_shop` = ' . (int) $this->idShop;

        $result = Db::getInstance()->getRow($sql);

        if ($result) {
            $result['category_ids'] = json_decode($result['category_ids'], true) ?: [];
            $result['language_ids'] = json_decode($result['language_ids'], true) ?: [];
            $result['fields_to_generate'] = json_decode($result['fields_to_generate'], true) ?: [];
        }

        return $result ?: null;
    }

    /**
     * Get pending or running job
     *
     * @return array|null
     */
    public function getActiveJob()
    {
        // Note: getRow() automatically adds LIMIT 1, so don't add it manually
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mlcategoryai_job_queue`
                WHERE `status` IN ("' . self::STATUS_PENDING . '", "' . self::STATUS_RUNNING . '")
                AND `id_shop` = ' . (int) $this->idShop . '
                ORDER BY `created_at` ASC';

        $result = Db::getInstance()->getRow($sql);

        if ($result) {
            $result['category_ids'] = json_decode($result['category_ids'], true);
            $result['language_ids'] = json_decode($result['language_ids'], true);
            $result['fields_to_generate'] = json_decode($result['fields_to_generate'], true);
        }

        return $result ?: null;
    }

    /**
     * Get all active jobs (pending, running, paused)
     *
     * @return array
     */
    public function getAllActiveJobs()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mlcategoryai_job_queue`
                WHERE `status` IN ("' . self::STATUS_PENDING . '", "' . self::STATUS_RUNNING . '", "' . self::STATUS_PAUSED . '")
                AND `id_shop` = ' . (int) $this->idShop . '
                ORDER BY `created_at` DESC';

        $results = Db::getInstance()->executeS($sql);

        if ($results) {
            foreach ($results as &$result) {
                $result['category_ids'] = json_decode($result['category_ids'], true);
                $result['language_ids'] = json_decode($result['language_ids'], true);
                $result['fields_to_generate'] = json_decode($result['fields_to_generate'], true);
            }
        }

        return $results ?: [];
    }

    /**
     * Delete a job
     *
     * @param int $idJob
     *
     * @return bool
     */
    public function deleteJob($idJob)
    {
        return Db::getInstance()->delete(
            'mlcategoryai_job_queue',
            'id_job = ' . (int) $idJob . ' AND id_shop = ' . (int) $this->idShop
        );
    }

    /**
     * Process next batch of items using parallel API calls
     *
     * @param int $idJob
     * @param Module $module
     * @param int $batchSize Number of items to process (will be sent in parallel)
     *
     * @return array Processing result
     */
    public function processNextBatch($idJob, $module, $batchSize = 5)
    {
        $t0 = microtime(true);
        MlCategoryAiLogger::info('processNextBatch START jobId=' . $idJob);

        $job = $this->getJob($idJob);

        if (!$job) {
            MlCategoryAiLogger::error('Job not found: ' . $idJob);

            return [
                'success' => false,
                'error' => 'Job not found',
                'completed' => false,
            ];
        }

        $t1 = microtime(true);
        MlCategoryAiLogger::debug('getJob took ' . round(($t1 - $t0) * 1000) . 'ms');

        if ($job['status'] === self::STATUS_COMPLETED) {
            return [
                'success' => true,
                'completed' => true,
                'message' => 'Job already completed',
            ];
        }

        if ($job['status'] === self::STATUS_PAUSED) {
            return [
                'success' => false,
                'error' => 'Job is paused',
                'completed' => false,
            ];
        }

        // Mark as running if pending
        if ($job['status'] === self::STATUS_PENDING) {
            $this->updateJobStatus($idJob, self::STATUS_RUNNING);
            $this->updateJob($idJob, ['started_at' => date('Y-m-d H:i:s')]);
        }

        // Build list of items to process
        $t2 = microtime(true);
        $items = $this->buildItemsList($job);
        $currentPosition = (int) $job['current_position'];
        MlCategoryAiLogger::debug('buildItemsList took ' . round((microtime(true) - $t2) * 1000) . 'ms - items=' . count($items));

        // Check if job is complete
        if ($currentPosition >= count($items)) {
            $this->updateJobStatus($idJob, self::STATUS_COMPLETED);
            $this->updateJob($idJob, ['completed_at' => date('Y-m-d H:i:s')]);

            return [
                'success' => true,
                'completed' => true,
                'processed' => $job['processed_items'],
                'failed' => $job['failed_items'],
                'total' => $job['total_items'],
            ];
        }

        // Get next batch
        $batchItems = array_slice($items, $currentPosition, $batchSize);

        if (empty($batchItems)) {
            return [
                'success' => false,
                'error' => 'No items to process in batch',
                'debug' => [
                    'items_count' => count($items),
                    'current_position' => $currentPosition,
                    'batch_size' => $batchSize,
                ],
            ];
        }

        // Check if parallel processing is enabled (default: yes)
        $useParallel = (bool) Configuration::get(Mlcategoryaidescription::CONFIG_PARALLEL_REQUESTS, true);

        $t3 = microtime(true);
        MlCategoryAiLogger::info('processBatch START - items=' . count($batchItems) . ' parallel=' . ($useParallel ? 'YES' : 'NO'));

        if ($useParallel && count($batchItems) > 1) {
            $batchResult = $this->processParallelBatch($batchItems, $module, $job);
        } else {
            $batchResult = $this->processSequentialBatch($batchItems, $module, $job, $idJob);
        }

        $batchTime = round((microtime(true) - $t3) * 1000);
        MlCategoryAiLogger::info('processBatch END - took ' . $batchTime . 'ms - processed=' . $batchResult['processed'] . ' failed=' . $batchResult['failed']);

        // Update job progress
        $t4 = microtime(true);
        $newPosition = $currentPosition + count($batchItems);
        $this->updateJob($idJob, [
            'current_position' => $newPosition,
            'processed_items' => (int) $job['processed_items'] + $batchResult['processed'],
            'failed_items' => (int) $job['failed_items'] + $batchResult['failed'],
            'last_processed_category_id' => end($batchItems)['id_category'],
            'last_processed_lang_id' => end($batchItems)['id_lang'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        MlCategoryAiLogger::debug('updateJob took ' . round((microtime(true) - $t4) * 1000) . 'ms');

        // Log errors
        foreach ($batchResult['errors'] as $error) {
            $this->appendErrorLog($idJob, $error);
            MlCategoryAiLogger::error('Item error: ' . $error);
        }

        // Check if completed
        $isCompleted = ($newPosition >= count($items));
        if ($isCompleted) {
            $this->updateJobStatus($idJob, self::STATUS_COMPLETED);
            $this->updateJob($idJob, ['completed_at' => date('Y-m-d H:i:s')]);
            MlCategoryAiLogger::info('Job #' . $idJob . ' COMPLETED');

            // Log final run stats
            $this->logCompletedJobStats($job, $batchResult);
        }

        return [
            'success' => true,
            'completed' => $isCompleted,
            'processed' => (int) $job['processed_items'] + $batchResult['processed'],
            'failed' => (int) $job['failed_items'] + $batchResult['failed'],
            'skipped' => $batchResult['skipped'],
            'total' => $job['total_items'],
            'current_position' => $newPosition,
            'batch_results' => $batchResult['results'],
            'progress_percent' => round(($newPosition / count($items)) * 100, 1),
            'tokens_input' => $batchResult['tokens_input'],
            'tokens_output' => $batchResult['tokens_output'],
            'batch_time_ms' => $batchResult['time_ms'],
            'parallel' => $useParallel && count($batchItems) > 1,
        ];
    }

    /**
     * Log stats for a completed job
     *
     * @param array $job Job data
     * @param array $lastBatchResult Last batch result with token counts
     */
    protected function logCompletedJobStats($job, $lastBatchResult)
    {
        $parallelEnabled = (bool) Configuration::get(Mlcategoryaidescription::CONFIG_PARALLEL_REQUESTS, true);

        // Calculate execution time from job timestamps
        $startedAt = strtotime($job['started_at']);
        $completedAt = time();
        $executionTimeMs = ($completedAt - $startedAt) * 1000;

        // Insert directly to have accurate timing
        Db::getInstance()->insert('mlcategoryai_run_stats', [
            'id_job' => (int) $job['id_job'],
            'id_shop' => (int) Context::getContext()->shop->id,
            'started_at' => pSQL($job['started_at']),
            'completed_at' => date('Y-m-d H:i:s'),
            'execution_time_ms' => (int) $executionTimeMs,
            'categories_count' => count($job['category_ids']),
            'languages_count' => count($job['language_ids']),
            'fields_count' => count($job['fields_to_generate']),
            'items_processed' => (int) $job['processed_items'] + $lastBatchResult['processed'],
            'items_skipped' => (int) $lastBatchResult['skipped'],
            'items_failed' => (int) $job['failed_items'] + $lastBatchResult['failed'],
            'write_mode' => pSQL($job['write_mode']),
            'tokens_input' => (int) $lastBatchResult['tokens_input'],
            'tokens_output' => (int) $lastBatchResult['tokens_output'],
            'parallel_requests' => $parallelEnabled ? (int) Configuration::get(Mlcategoryaidescription::CONFIG_BATCH_SIZE) : 1,
            'avg_request_time_ms' => (int) $lastBatchResult['time_ms'],
        ]);
    }

    /**
     * Build flat list of items to process
     *
     * @param array $job
     *
     * @return array
     */
    protected function buildItemsList($job)
    {
        $items = [];

        foreach ($job['category_ids'] as $idCategory) {
            foreach ($job['language_ids'] as $idLang) {
                foreach ($job['fields_to_generate'] as $fieldType) {
                    $items[] = [
                        'id_category' => (int) $idCategory,
                        'id_lang' => (int) $idLang,
                        'field_type' => $fieldType,
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Update job status
     *
     * @param int $idJob
     * @param string $status
     *
     * @return bool
     */
    public function updateJobStatus($idJob, $status)
    {
        return Db::getInstance()->update(
            'mlcategoryai_job_queue',
            [
                'status' => pSQL($status),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            '`id_job` = ' . (int) $idJob
        );
    }

    /**
     * Update job fields
     *
     * @param int $idJob
     * @param array $data
     *
     * @return bool
     */
    public function updateJob($idJob, $data)
    {
        $updateData = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                // Use SQL NULL properly with type hint
                $updateData[$key] = ['type' => 'sql', 'value' => 'NULL'];
            } elseif (is_int($value)) {
                $updateData[$key] = (int) $value;
            } else {
                $updateData[$key] = pSQL($value);
            }
        }

        // Build UPDATE query manually to handle NULL values
        $set = [];
        foreach ($updateData as $key => $value) {
            if (is_array($value) && isset($value['type']) && $value['type'] === 'sql') {
                $set[] = '`' . bqSQL($key) . '` = ' . $value['value'];
            } else {
                $set[] = '`' . bqSQL($key) . '` = "' . pSQL($value) . '"';
            }
        }

        if (empty($set)) {
            return true;
        }

        $sql = 'UPDATE `' . _DB_PREFIX_ . 'mlcategoryai_job_queue`
                SET ' . implode(', ', $set) . '
                WHERE `id_job` = ' . (int) $idJob;

        return Db::getInstance()->execute($sql);
    }

    /**
     * Append to error log
     *
     * @param int $idJob
     * @param string $message
     *
     * @return bool
     */
    protected function appendErrorLog($idJob, $message)
    {
        $job = $this->getJob($idJob);
        $errorLog = $job['error_log'] ?? '';
        $errorLog .= '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";

        return $this->updateJob($idJob, ['error_log' => $errorLog]);
    }

    /**
     * Pause a running job
     *
     * @param int $idJob
     *
     * @return bool
     */
    public function pauseJob($idJob)
    {
        $job = $this->getJob($idJob);

        if (!$job || $job['status'] !== self::STATUS_RUNNING) {
            return false;
        }

        return $this->updateJobStatus($idJob, self::STATUS_PAUSED);
    }

    /**
     * Resume a paused job
     *
     * @param int $idJob
     *
     * @return bool
     */
    public function resumeJob($idJob)
    {
        $job = $this->getJob($idJob);

        if (!$job || $job['status'] !== self::STATUS_PAUSED) {
            return false;
        }

        return $this->updateJobStatus($idJob, self::STATUS_RUNNING);
    }

    /**
     * Cancel a job
     *
     * @param int $idJob
     *
     * @return bool
     */
    public function cancelJob($idJob)
    {
        $job = $this->getJob($idJob);

        if (!$job) {
            return false;
        }

        if (in_array($job['status'], [self::STATUS_COMPLETED, self::STATUS_FAILED])) {
            return false;
        }

        return $this->updateJobStatus($idJob, self::STATUS_FAILED);
    }

    /**
     * Get all jobs with pagination
     *
     * @param int $page
     * @param int $limit
     * @param string $status Filter by status
     *
     * @return array
     */
    public function getJobs($page = 1, $limit = 20, $status = null)
    {
        $offset = ((int) $page - 1) * (int) $limit;

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mlcategoryai_job_queue`
                WHERE `id_shop` = ' . (int) $this->idShop;

        if ($status !== null) {
            $sql .= ' AND `status` = "' . pSQL($status) . '"';
        }

        $sql .= ' ORDER BY `created_at` DESC
                  LIMIT ' . (int) $offset . ', ' . (int) $limit;

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Delete old completed/failed jobs
     *
     * @param int $daysOld Jobs older than X days
     *
     * @return bool
     */
    public function cleanupOldJobs($daysOld = 30)
    {
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'mlcategoryai_job_queue`
                WHERE `status` IN ("' . self::STATUS_COMPLETED . '", "' . self::STATUS_FAILED . '")
                AND `created_at` < DATE_SUB(NOW(), INTERVAL ' . (int) $daysOld . ' DAY)
                AND `id_shop` = ' . (int) $this->idShop;

        return Db::getInstance()->execute($sql);
    }

    /**
     * Process batch items in parallel using curl_multi
     *
     * @param array $batchItems Items to process
     * @param Module $module Module instance
     * @param array $job Job data
     *
     * @return array ['processed', 'failed', 'skipped', 'results', 'errors', 'tokens_input', 'tokens_output', 'time_ms']
     */
    protected function processParallelBatch($batchItems, $module, $job)
    {
        $startTime = microtime(true);
        $generator = new MlCategoryAiGenerator($module);
        $client = MlCategoryAiClient::createFromConfig($module);

        // Prepare all requests
        $requests = [];
        $skipResults = [];

        foreach ($batchItems as $index => $item) {
            $itemId = $item['id_category'] . '-' . $item['id_lang'] . '-' . $item['field_type'];

            // Check if should skip (fill_missing mode)
            if ($job['write_mode'] === Mlcategoryaidescription::WRITE_MODE_FILL_MISSING) {
                $category = new Category((int) $item['id_category'], (int) $item['id_lang']);
                if (Validate::isLoadedObject($category)) {
                    $existingContent = $generator->getFieldValuePublic($category, $item['field_type']);
                    if (!empty(trim(strip_tags($existingContent)))) {
                        $skipResults[$itemId] = [
                            'id_category' => $item['id_category'],
                            'id_lang' => $item['id_lang'],
                            'field_type' => $item['field_type'],
                            'success' => true,
                            'skipped' => true,
                            'error' => '',
                        ];
                        continue;
                    }
                }
            }

            // Build prompt for this item
            $promptData = $generator->buildPromptForItem($item['id_category'], $item['id_lang'], $item['field_type']);
            if ($promptData === false) {
                $skipResults[$itemId] = [
                    'id_category' => $item['id_category'],
                    'id_lang' => $item['id_lang'],
                    'field_type' => $item['field_type'],
                    'success' => false,
                    'skipped' => false,
                    'error' => 'Failed to build prompt',
                ];
                continue;
            }

            $requests[] = [
                'id' => $itemId,
                'prompt' => $promptData['prompt'],
                'system' => '',
                'cache_key' => $item['field_type'],
                'item' => $item,
            ];
        }

        // Send all requests in parallel
        $apiResults = [];
        if (!empty($requests)) {
            $apiResults = $client->generateParallel($requests);
        }

        // Process results
        $processed = 0;
        $failed = 0;
        $skipped = count($skipResults);
        $results = array_values($skipResults);
        $errors = [];
        $tokensInput = 0;
        $tokensOutput = 0;

        foreach ($requests as $req) {
            $itemId = $req['id'];
            $item = $req['item'];
            $apiResult = isset($apiResults[$itemId]) ? $apiResults[$itemId] : null;

            if (!$apiResult || !$apiResult['success']) {
                $errorMsg = $apiResult ? $apiResult['error'] : 'No API response';
                $results[] = [
                    'id_category' => $item['id_category'],
                    'id_lang' => $item['id_lang'],
                    'field_type' => $item['field_type'],
                    'success' => false,
                    'skipped' => false,
                    'error' => $errorMsg,
                ];
                $errors[] = sprintf(
                    'Category %d, Lang %d, Field %s: %s',
                    $item['id_category'],
                    $item['id_lang'],
                    $item['field_type'],
                    $errorMsg
                );
                ++$failed;
                continue;
            }

            // Clean and save the content
            $content = $generator->cleanContentPublic($apiResult['content'], $item['field_type']);
            $category = new Category((int) $item['id_category'], (int) $item['id_lang']);

            if (!Validate::isLoadedObject($category)) {
                $results[] = [
                    'id_category' => $item['id_category'],
                    'id_lang' => $item['id_lang'],
                    'field_type' => $item['field_type'],
                    'success' => false,
                    'skipped' => false,
                    'error' => 'Category not found',
                ];
                ++$failed;
                continue;
            }

            $updateResult = $generator->updateCategoryFieldPublic($category, $item['field_type'], $content, $item['id_lang']);

            if ($updateResult) {
                $results[] = [
                    'id_category' => $item['id_category'],
                    'id_lang' => $item['id_lang'],
                    'field_type' => $item['field_type'],
                    'success' => true,
                    'skipped' => false,
                    'error' => '',
                ];
                ++$processed;

                // Log generation
                $generator->logGenerationPublic(
                    $item['id_category'],
                    $item['id_lang'],
                    $item['field_type'],
                    'success',
                    '',
                    $apiResult['tokens_in'] + $apiResult['tokens_out']
                );
            } else {
                $results[] = [
                    'id_category' => $item['id_category'],
                    'id_lang' => $item['id_lang'],
                    'field_type' => $item['field_type'],
                    'success' => false,
                    'skipped' => false,
                    'error' => 'Failed to update category',
                ];
                ++$failed;
            }

            $tokensInput += $apiResult['tokens_in'];
            $tokensOutput += $apiResult['tokens_out'];
        }

        $timeMs = (int) ((microtime(true) - $startTime) * 1000);

        return [
            'processed' => $processed,
            'failed' => $failed,
            'skipped' => $skipped,
            'results' => $results,
            'errors' => $errors,
            'tokens_input' => $tokensInput,
            'tokens_output' => $tokensOutput,
            'time_ms' => $timeMs,
        ];
    }

    /**
     * Process batch items sequentially (fallback)
     *
     * @param array $batchItems Items to process
     * @param Module $module Module instance
     * @param array $job Job data
     * @param int $idJob Job ID
     *
     * @return array ['processed', 'failed', 'skipped', 'results', 'errors', 'tokens_input', 'tokens_output', 'time_ms']
     */
    protected function processSequentialBatch($batchItems, $module, $job, $idJob)
    {
        $startTime = microtime(true);
        $generator = new MlCategoryAiGenerator($module);
        $requestDelay = (int) Configuration::get(Mlcategoryaidescription::CONFIG_REQUEST_DELAY);

        $processed = 0;
        $failed = 0;
        $skipped = 0;
        $results = [];
        $errors = [];
        $tokensInput = 0;
        $tokensOutput = 0;

        foreach ($batchItems as $index => $item) {
            try {
                $result = $generator->generateField(
                    (int) $item['id_category'],
                    (int) $item['id_lang'],
                    (string) $item['field_type'],
                    $job['write_mode']
                );
            } catch (Exception $e) {
                $result = [
                    'success' => false,
                    'skipped' => false,
                    'error' => 'Exception: ' . $e->getMessage(),
                    'tokens' => 0,
                ];
            }

            $results[] = [
                'id_category' => $item['id_category'],
                'id_lang' => $item['id_lang'],
                'field_type' => $item['field_type'],
                'success' => $result['success'],
                'skipped' => isset($result['skipped']) ? $result['skipped'] : false,
                'error' => isset($result['error']) ? $result['error'] : '',
            ];

            if (isset($result['skipped']) && $result['skipped']) {
                ++$skipped;
                ++$processed; // Skipped counts as processed
            } elseif ($result['success']) {
                ++$processed;
                $tokensInput += isset($result['tokens']) ? (int) ($result['tokens'] * 0.7) : 0; // Estimate
                $tokensOutput += isset($result['tokens']) ? (int) ($result['tokens'] * 0.3) : 0;
            } else {
                ++$failed;
                $errors[] = sprintf(
                    'Category %d, Lang %d, Field %s: %s',
                    $item['id_category'],
                    $item['id_lang'],
                    $item['field_type'],
                    $result['error']
                );
            }

            // Delay between requests (except for last item)
            if ($index < count($batchItems) - 1 && $requestDelay > 0) {
                sleep($requestDelay);
            }
        }

        $timeMs = (int) ((microtime(true) - $startTime) * 1000);

        return [
            'processed' => $processed,
            'failed' => $failed,
            'skipped' => $skipped,
            'results' => $results,
            'errors' => $errors,
            'tokens_input' => $tokensInput,
            'tokens_output' => $tokensOutput,
            'time_ms' => $timeMs,
        ];
    }
}
