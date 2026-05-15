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
        if ($this->idShop <= 0) {
            $this->idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
        }
        if ($this->idShop <= 0) {
            $this->idShop = 1;
        }
    }

    /**
     * Create a new batch job
     *
     * @param array $categoryIds
     * @param array $languageIds
     * @param array $fieldsToGenerate
     * @param string $writeMode
     * @param array $gtOptions Google Translate options (optional)
     *
     * @return int|false Job ID or false on failure
     */
    public function createJob($categoryIds, $languageIds, $fieldsToGenerate, $writeMode = 'fill_missing', $gtOptions = [])
    {
        $useGoogleTranslate = !empty($gtOptions['use_google_translate']);
        $primaryLanguageId = isset($gtOptions['primary_language_id']) ? (int) $gtOptions['primary_language_id'] : null;
        $translateLanguageIds = isset($gtOptions['translate_language_ids']) ? $gtOptions['translate_language_ids'] : [];

        // v1.7.0: Count by category/language pairs, NOT by fields (batched API calls)
        if ($useGoogleTranslate && $primaryLanguageId) {
            if ($writeMode === 'fill_missing') {
                // Real item count (skips categories/langs already filled)
                $probeJob = [
                    'entity_type' => Mlcategoryaidescription::ENTITY_CATEGORY,
                    'category_ids' => array_map('intval', $categoryIds),
                    'language_ids' => array_map('intval', $languageIds),
                    'fields_to_generate' => $fieldsToGenerate,
                    'write_mode' => $writeMode,
                    'use_google_translate' => true,
                    'primary_language_id' => (int) $primaryLanguageId,
                    'translate_language_ids' => array_map('intval', $translateLanguageIds),
                    'phase' => 'processing',
                ];
                $totalItems = count($this->buildItemsList($probeJob));
            } else {
                // Overwrite: every category gets primary + each target
                $totalItems = count($categoryIds) * (1 + count($translateLanguageIds));
            }
        } else {
            // Simple: categories × languages (all fields in 1 call per category/lang)
            $totalItems = count($categoryIds) * count($languageIds);
        }

        if ($useGoogleTranslate && $primaryLanguageId && $writeMode === 'fill_missing' && $totalItems < 1) {
            return false;
        }

        $jobData = [
            'id_shop' => (int) $this->idShop,
            'entity_type' => pSQL(Mlcategoryaidescription::ENTITY_CATEGORY),
            'job_type' => 'batch_generation',
            'status' => self::STATUS_PENDING,
            'total_items' => (int) $totalItems,
            'processed_items' => 0,
            'failed_items' => 0,
            'category_ids' => pSQL(json_encode(array_map('intval', $categoryIds))),
            'language_ids' => pSQL(json_encode(array_map('intval', $languageIds))),
            'fields_to_generate' => pSQL(json_encode($fieldsToGenerate)),
            'write_mode' => pSQL($writeMode),
            'use_google_translate' => $useGoogleTranslate ? 1 : 0,
            'primary_language_id' => $primaryLanguageId,
            'translate_language_ids' => !empty($translateLanguageIds) ? pSQL(json_encode(array_map('intval', $translateLanguageIds))) : null,
            'phase' => 'processing', // v1.8.0: Simplified - no more phase transitions
            'current_translate_lang_index' => 0,
            'current_translate_position' => 0,
            'current_position' => 0,
            'last_processed_category_id' => null,
            'last_processed_lang_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'started_at' => null,
            'completed_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
            'error_log' => null,
        ];

        $result = Db::getInstance()->insert('mlcategoryai_job_queue', $jobData);

        if (!$result) {
            return false;
        }

        return (int) Db::getInstance()->Insert_ID();
    }

    /**
     * Batch job for manufacturers (same queue/cron as categories).
     *
     * @param array $manufacturerIds
     * @param array $languageIds
     * @param array $fieldsToGenerate
     * @param string $writeMode
     * @param array $gtOptions
     *
     * @return int|false
     */
    public function createManufacturerJob($manufacturerIds, $languageIds, $fieldsToGenerate, $writeMode = 'fill_missing', $gtOptions = [])
    {
        $useGoogleTranslate = !empty($gtOptions['use_google_translate']);
        $primaryLanguageId = isset($gtOptions['primary_language_id']) ? (int) $gtOptions['primary_language_id'] : null;
        $translateLanguageIds = isset($gtOptions['translate_language_ids']) ? $gtOptions['translate_language_ids'] : [];

        if ($useGoogleTranslate && $primaryLanguageId) {
            if ($writeMode === 'fill_missing') {
                $probeJob = [
                    'entity_type' => Mlcategoryaidescription::ENTITY_MANUFACTURER,
                    'category_ids' => [],
                    'manufacturer_ids' => array_map('intval', $manufacturerIds),
                    'language_ids' => array_map('intval', $languageIds),
                    'fields_to_generate' => $fieldsToGenerate,
                    'write_mode' => $writeMode,
                    'use_google_translate' => true,
                    'primary_language_id' => (int) $primaryLanguageId,
                    'translate_language_ids' => array_map('intval', $translateLanguageIds),
                    'phase' => 'processing',
                ];
                $totalItems = count($this->buildItemsList($probeJob));
            } else {
                $totalItems = count($manufacturerIds) * (1 + count($translateLanguageIds));
            }
        } else {
            $totalItems = count($manufacturerIds) * count($languageIds);
        }

        if ($useGoogleTranslate && $primaryLanguageId && $writeMode === 'fill_missing' && $totalItems < 1) {
            return false;
        }

        $jobData = [
            'id_shop' => (int) $this->idShop,
            'entity_type' => pSQL(Mlcategoryaidescription::ENTITY_MANUFACTURER),
            'job_type' => 'batch_generation',
            'status' => self::STATUS_PENDING,
            'total_items' => (int) $totalItems,
            'processed_items' => 0,
            'failed_items' => 0,
            'category_ids' => pSQL(json_encode([])),
            'manufacturer_ids' => pSQL(json_encode(array_map('intval', $manufacturerIds))),
            'language_ids' => pSQL(json_encode(array_map('intval', $languageIds))),
            'fields_to_generate' => pSQL(json_encode($fieldsToGenerate)),
            'write_mode' => pSQL($writeMode),
            'use_google_translate' => $useGoogleTranslate ? 1 : 0,
            'primary_language_id' => $primaryLanguageId,
            'translate_language_ids' => !empty($translateLanguageIds) ? pSQL(json_encode(array_map('intval', $translateLanguageIds))) : null,
            'phase' => 'processing',
            'current_translate_lang_index' => 0,
            'current_translate_position' => 0,
            'current_position' => 0,
            'last_processed_category_id' => null,
            'last_processed_manufacturer_id' => null,
            'last_processed_lang_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'started_at' => null,
            'completed_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
            'error_log' => null,
        ];

        $result = Db::getInstance()->insert('mlcategoryai_job_queue', $jobData);

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
            // Google Translate fields
            $result['use_google_translate'] = !empty($result['use_google_translate']);
            $result['translate_language_ids'] = !empty($result['translate_language_ids'])
                ? (json_decode($result['translate_language_ids'], true) ?: [])
                : [];
            if (!isset($result['entity_type']) || $result['entity_type'] === '') {
                $result['entity_type'] = Mlcategoryaidescription::ENTITY_CATEGORY;
            }
            $result['manufacturer_ids'] = !empty($result['manufacturer_ids'])
                ? (json_decode($result['manufacturer_ids'], true) ?: [])
                : [];
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
            // Google Translate fields
            $result['use_google_translate'] = !empty($result['use_google_translate']);
            $result['translate_language_ids'] = !empty($result['translate_language_ids'])
                ? (json_decode($result['translate_language_ids'], true) ?: [])
                : [];
            if (!isset($result['entity_type']) || $result['entity_type'] === '') {
                $result['entity_type'] = Mlcategoryaidescription::ENTITY_CATEGORY;
            }
            $result['manufacturer_ids'] = !empty($result['manufacturer_ids'])
                ? (json_decode($result['manufacturer_ids'], true) ?: [])
                : [];
        }

        return $result ?: null;
    }

    /**
     * Get all active jobs (pending, running, paused, failed)
     * v1.7.1: Include failed jobs for UX management
     *
     * @return array
     */
    public function getAllActiveJobs()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mlcategoryai_job_queue`
                WHERE `status` IN ("' . self::STATUS_PENDING . '", "' . self::STATUS_RUNNING . '", "' . self::STATUS_PAUSED . '", "' . self::STATUS_FAILED . '")
                AND `id_shop` = ' . (int) $this->idShop . '
                ORDER BY `created_at` DESC';

        $results = Db::getInstance()->executeS($sql);

        if ($results) {
            foreach ($results as &$result) {
                $result['category_ids'] = json_decode($result['category_ids'], true);
                $result['language_ids'] = json_decode($result['language_ids'], true);
                $result['fields_to_generate'] = json_decode($result['fields_to_generate'], true);
                // Google Translate fields
                $result['use_google_translate'] = !empty($result['use_google_translate']);
                $result['translate_language_ids'] = !empty($result['translate_language_ids'])
                    ? (json_decode($result['translate_language_ids'], true) ?: [])
                    : [];
                if (!isset($result['entity_type']) || $result['entity_type'] === '') {
                    $result['entity_type'] = Mlcategoryaidescription::ENTITY_CATEGORY;
                }
                $result['manufacturer_ids'] = !empty($result['manufacturer_ids'])
                    ? (json_decode($result['manufacturer_ids'], true) ?: [])
                    : [];
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

        // v1.7.1: Check for failed status to stop orphan processes
        if ($job['status'] === self::STATUS_FAILED) {
            MlCategoryAiLogger::info('Job #' . $idJob . ' is failed, stopping processing');

            return [
                'success' => false,
                'error' => 'Job has been marked as failed',
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

        // Keep DB total_items in sync with actual work (Google + fill_missing often skips rows)
        $actualItemCount = count($items);
        $storedTotal = (int) $job['total_items'];
        if ($currentPosition === 0 && $actualItemCount !== $storedTotal) {
            $this->updateJob($idJob, ['total_items' => $actualItemCount]);
            $job['total_items'] = $actualItemCount;
            MlCategoryAiLogger::info('Adjusted total_items to ' . $actualItemCount . ' (was ' . $storedTotal . ') for job #' . $idJob);
        }

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
        $useParallel = (bool) Configuration::get(Mlcategoryaidescription::CONFIG_PARALLEL_REQUESTS, null, null, null, true);

        // Check if all items are OpenAI source (parallel only works for OpenAI)
        $allOpenAi = true;
        foreach ($batchItems as $item) {
            $source = isset($item['source']) ? $item['source'] : 'openai';
            if ($source !== 'openai') {
                $allOpenAi = false;
                break;
            }
        }

        // v1.7.0: Parallel batch uses legacy per-field approach, not compatible with new batched structure
        // For now, use sequential processing for batched items (75% fewer API calls already)
        // TODO: Update processParallelBatch to work with generateCategoryBatch if needed
        $canUseParallel = $useParallel && $allOpenAi && isset($batchItems[0]['field_type']);

        $t3 = microtime(true);
        MlCategoryAiLogger::info('processBatch START - items=' . count($batchItems) . ' parallel=' . ($canUseParallel ? 'YES' : 'NO'));

        if ($canUseParallel && count($batchItems) > 1) {
            $batchResult = $this->processParallelBatch($batchItems, $module, $job);
        } else {
            $batchResult = $this->processSequentialBatch($batchItems, $module, $job, $idJob);
        }

        $batchTime = round((microtime(true) - $t3) * 1000);
        MlCategoryAiLogger::info('processBatch END - took ' . $batchTime . 'ms - processed=' . $batchResult['processed'] . ' failed=' . $batchResult['failed']);

        // Update job progress
        $t4 = microtime(true);
        $newPosition = $currentPosition + count($batchItems);
        $lastItem = end($batchItems);
        $progressUpdate = [
            'current_position' => $newPosition,
            'processed_items' => (int) $job['processed_items'] + $batchResult['processed'],
            'failed_items' => (int) $job['failed_items'] + $batchResult['failed'],
            'last_processed_lang_id' => (int) $lastItem['id_lang'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (!empty($lastItem['id_manufacturer'])) {
            $progressUpdate['last_processed_manufacturer_id'] = (int) $lastItem['id_manufacturer'];
            $progressUpdate['last_processed_category_id'] = ['type' => 'sql', 'value' => 'NULL'];
        } else {
            $progressUpdate['last_processed_category_id'] = (int) $lastItem['id_category'];
            $progressUpdate['last_processed_manufacturer_id'] = ['type' => 'sql', 'value' => 'NULL'];
        }
        $this->updateJob($idJob, $progressUpdate);
        MlCategoryAiLogger::debug('updateJob took ' . round((microtime(true) - $t4) * 1000) . 'ms');

        // Log errors
        foreach ($batchResult['errors'] as $error) {
            $this->appendErrorLog($idJob, $error);
            MlCategoryAiLogger::error('Item error: ' . $error);
        }

        // Check if completed
        $isCompleted = ($newPosition >= count($items));

        // v1.8.0: Removed phase transitions - now using interleaved per-category flow
        // No need to transition phases since OpenAI + Translate items are interleaved

        if ($isCompleted) {
            $this->updateJobStatus($idJob, self::STATUS_COMPLETED);
            $this->updateJob($idJob, [
                'phase' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
            MlCategoryAiLogger::info('Job #' . $idJob . ' COMPLETED');

            // Log final run stats
            $this->logCompletedJobStats($job, $batchResult);
        }

        // Calculate progress percent
        $progressPercent = $this->calculateProgressPercent($job, $newPosition, count($items));

        return [
            'success' => true,
            'completed' => $isCompleted,
            'processed' => (int) $job['processed_items'] + $batchResult['processed'],
            'failed' => (int) $job['failed_items'] + $batchResult['failed'],
            'skipped' => $batchResult['skipped'],
            'total' => (int) $job['total_items'],
            'current_position' => $newPosition,
            'batch_results' => $batchResult['results'],
            'progress_percent' => $progressPercent,
            'tokens_input' => $batchResult['tokens_input'],
            'tokens_output' => $batchResult['tokens_output'],
            'batch_time_ms' => $batchResult['time_ms'],
            'parallel' => $useParallel && count($batchItems) > 1,
            'phase' => isset($job['phase']) ? $job['phase'] : 'processing',
        ];
    }

    /**
     * Calculate progress percentage
     * v1.8.0: Simplified - no longer uses phase-based calculation
     *
     * @param array $job Job data
     * @param int $currentPosition Current position in items list
     * @param int $totalItems Total items to process
     *
     * @return float Progress percentage
     */
    protected function calculateProgressPercent($job, $currentPosition, $totalItems)
    {
        // v1.8.0: Simple linear progress (interleaved processing)
        return $totalItems > 0 ? round(($currentPosition / $totalItems) * 100, 1) : 0;
    }

    /**
     * Log stats for a completed job
     *
     * @param array $job Job data
     * @param array $lastBatchResult Last batch result with token counts
     */
    protected function logCompletedJobStats($job, $lastBatchResult)
    {
        $parallelEnabled = (bool) Configuration::get(Mlcategoryaidescription::CONFIG_PARALLEL_REQUESTS, null, null, null, true);

        // Calculate execution time from job timestamps with validation
        $startedAt = !empty($job['started_at']) ? strtotime($job['started_at']) : 0;
        $completedAt = time();

        // Validate timestamps and calculate execution time safely
        if ($startedAt > 0 && $startedAt <= $completedAt) {
            $executionTimeMs = ($completedAt - $startedAt) * 1000;
        } else {
            // Fallback: use the batch time if timestamps are invalid
            $executionTimeMs = isset($lastBatchResult['time_ms']) ? (int) $lastBatchResult['time_ms'] : 0;
        }

        // Ensure execution time is within reasonable bounds (max 24 hours = 86,400,000 ms)
        $maxExecutionMs = 86400000;
        if ($executionTimeMs < 0 || $executionTimeMs > $maxExecutionMs) {
            $executionTimeMs = isset($lastBatchResult['time_ms']) ? (int) $lastBatchResult['time_ms'] : 0;
        }

        $entityCount = (isset($job['entity_type']) && $job['entity_type'] === Mlcategoryaidescription::ENTITY_MANUFACTURER)
            ? count($job['manufacturer_ids'] ?: [])
            : count($job['category_ids'] ?: []);

        // Insert directly to have accurate timing
        Db::getInstance()->insert('mlcategoryai_run_stats', [
            'id_job' => (int) $job['id_job'],
            'id_shop' => (int) Shop::getContextShopID(),
            'started_at' => pSQL($job['started_at']),
            'completed_at' => date('Y-m-d H:i:s'),
            'execution_time_ms' => (int) $executionTimeMs,
            'categories_count' => (int) $entityCount,
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
     * v1.7.0: One item per category/language (all fields bundled)
     *
     * @param array $job
     *
     * @return array
     */
    protected function buildItemsList($job)
    {
        $items = [];

        $entityType = isset($job['entity_type']) ? $job['entity_type'] : Mlcategoryaidescription::ENTITY_CATEGORY;

        if ($entityType === Mlcategoryaidescription::ENTITY_MANUFACTURER) {
            if (!empty($job['use_google_translate']) && !empty($job['primary_language_id'])) {
                return $this->buildItemsListGoogleTranslateManufacturer($job);
            }
            $mfrIds = isset($job['manufacturer_ids']) ? $job['manufacturer_ids'] : [];
            foreach ($mfrIds as $idMfr) {
                foreach ($job['language_ids'] as $idLang) {
                    $items[] = [
                        'id_manufacturer' => (int) $idMfr,
                        'id_lang' => (int) $idLang,
                        'fields' => $job['fields_to_generate'],
                        'source' => 'openai',
                    ];
                }
            }

            return $items;
        }

        // Check if using Google Translate two-phase processing
        if (!empty($job['use_google_translate']) && !empty($job['primary_language_id'])) {
            return $this->buildItemsListGoogleTranslate($job);
        }

        // v1.7.0: One item per category/language (fields bundled for batch processing)
        foreach ($job['category_ids'] as $idCategory) {
            foreach ($job['language_ids'] as $idLang) {
                $items[] = [
                    'id_category' => (int) $idCategory,
                    'id_lang' => (int) $idLang,
                    'fields' => $job['fields_to_generate'],
                    'source' => 'openai',
                ];
            }
        }

        return $items;
    }

    /**
     * Build items list for Google Translate two-phase processing
     * v1.8.0: Per-category interleaved flow - OpenAI then Translate for each category
     *
     * This ensures a category is fully processed (all languages) before moving to the next.
     * If process dies, we have complete categories, not partial translations.
     *
     * Structure: [cat1_openai, cat1_translate_en, cat1_translate_de, cat2_openai, cat2_translate_en, ...]
     *
     * @param array $job
     *
     * @return array
     */
    protected function buildItemsListGoogleTranslate($job)
    {
        $items = [];
        $primaryLangId = (int) $job['primary_language_id'];
        $translateLangIds = $job['translate_language_ids'] ?: [];
        $fields = $job['fields_to_generate'];
        $writeMode = isset($job['write_mode']) ? $job['write_mode'] : 'overwrite';
        $phase = isset($job['phase']) ? $job['phase'] : 'processing';

        $preloadMap = null;
        if ($writeMode === 'fill_missing') {
            $langUnion = array_unique(array_merge([$primaryLangId], array_map('intval', $translateLangIds)));
            $preloadMap = $this->preloadCategoryLangContentMap($job['category_ids'], $langUnion);
        }

        // v1.8.0: translate_only phase - skip OpenAI, only add translation items
        if ($phase === 'translate_only') {
            foreach ($job['category_ids'] as $idCategory) {
                foreach ($translateLangIds as $targetLangId) {
                    // In translate_only mode, check if target needs translation
                    $needsTranslate = true;
                    if ($writeMode === 'fill_missing') {
                        $needsTranslate = !$this->categoryHasPrimaryContent($idCategory, (int) $targetLangId, $fields, $preloadMap);
                    }

                    if ($needsTranslate) {
                        $items[] = [
                            'id_category' => (int) $idCategory,
                            'id_lang' => (int) $targetLangId,
                            'fields' => $fields,
                            'source' => 'google_translate',
                            'source_lang_id' => $primaryLangId,
                        ];
                    }
                }
            }

            return $items;
        }

        // v1.8.0: Interleaved per-category processing
        // For each category: OpenAI primary lang, then translate to all other langs
        foreach ($job['category_ids'] as $idCategory) {
            // v1.8.0: Smart fill-missing - check if primary language content exists
            $needsOpenAi = true;
            if ($writeMode === 'fill_missing') {
                $needsOpenAi = !$this->categoryHasPrimaryContent($idCategory, $primaryLangId, $fields, $preloadMap);
            }

            // 1. First: OpenAI generation for primary language (unless we're filling missing and content exists)
            if ($needsOpenAi) {
                $items[] = [
                    'id_category' => (int) $idCategory,
                    'id_lang' => $primaryLangId,
                    'fields' => $fields,
                    'source' => 'openai',
                ];
            }

            // 2. Then: Google Translate for each target language
            foreach ($translateLangIds as $targetLangId) {
                // v1.8.0: In fill_missing mode, check if target language needs translation
                $needsTranslate = true;
                if ($writeMode === 'fill_missing') {
                    $needsTranslate = !$this->categoryHasPrimaryContent($idCategory, (int) $targetLangId, $fields, $preloadMap);
                }

                if ($needsTranslate) {
                    $items[] = [
                        'id_category' => (int) $idCategory,
                        'id_lang' => (int) $targetLangId,
                        'fields' => $fields,
                        'source' => 'google_translate',
                        'source_lang_id' => $primaryLangId,
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Google Translate interleaved list for manufacturers (same shape as categories).
     *
     * @param array $job
     *
     * @return array
     */
    protected function buildItemsListGoogleTranslateManufacturer($job)
    {
        $items = [];
        $primaryLangId = (int) $job['primary_language_id'];
        $translateLangIds = $job['translate_language_ids'] ?: [];
        $fields = $job['fields_to_generate'];
        $writeMode = isset($job['write_mode']) ? $job['write_mode'] : 'overwrite';
        $phase = isset($job['phase']) ? $job['phase'] : 'processing';
        $mfrIds = isset($job['manufacturer_ids']) ? $job['manufacturer_ids'] : [];

        $preloadMap = null;
        if ($writeMode === 'fill_missing') {
            $langUnion = array_unique(array_merge([$primaryLangId], array_map('intval', $translateLangIds)));
            $preloadMap = $this->preloadManufacturerLangContentMap($mfrIds, $langUnion);
        }

        if ($phase === 'translate_only') {
            foreach ($mfrIds as $idMfr) {
                foreach ($translateLangIds as $targetLangId) {
                    $needsTranslate = true;
                    if ($writeMode === 'fill_missing') {
                        $needsTranslate = !$this->manufacturerHasPrimaryContent((int) $idMfr, (int) $targetLangId, $fields, $preloadMap);
                    }
                    if ($needsTranslate) {
                        $items[] = [
                            'id_manufacturer' => (int) $idMfr,
                            'id_lang' => (int) $targetLangId,
                            'fields' => $fields,
                            'source' => 'google_translate',
                            'source_lang_id' => $primaryLangId,
                        ];
                    }
                }
            }

            return $items;
        }

        foreach ($mfrIds as $idMfr) {
            $needsOpenAi = true;
            if ($writeMode === 'fill_missing') {
                $needsOpenAi = !$this->manufacturerHasPrimaryContent((int) $idMfr, $primaryLangId, $fields, $preloadMap);
            }
            if ($needsOpenAi) {
                $items[] = [
                    'id_manufacturer' => (int) $idMfr,
                    'id_lang' => $primaryLangId,
                    'fields' => $fields,
                    'source' => 'openai',
                ];
            }
            foreach ($translateLangIds as $targetLangId) {
                $needsTranslate = true;
                if ($writeMode === 'fill_missing') {
                    $needsTranslate = !$this->manufacturerHasPrimaryContent((int) $idMfr, (int) $targetLangId, $fields, $preloadMap);
                }
                if ($needsTranslate) {
                    $items[] = [
                        'id_manufacturer' => (int) $idMfr,
                        'id_lang' => (int) $targetLangId,
                        'fields' => $fields,
                        'source' => 'google_translate',
                        'source_lang_id' => $primaryLangId,
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Preload category_lang rows for many categories and languages (one query).
     *
     * @param array $categoryIds
     * @param array $langIds
     *
     * @return array<int, array<int, array>> Map id_category => id_lang => row
     */
    protected function preloadCategoryLangContentMap(array $categoryIds, array $langIds)
    {
        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));
        $langIds = array_values(array_unique(array_map('intval', $langIds)));
        if (empty($categoryIds) || empty($langIds)) {
            return [];
        }

        $sql = 'SELECT `id_category`, `id_lang`, `description`, `meta_title`, `meta_description`
                FROM `' . _DB_PREFIX_ . 'category_lang`
                WHERE `id_shop` = ' . (int) $this->idShop . '
                AND `id_category` IN (' . implode(',', $categoryIds) . ')
                AND `id_lang` IN (' . implode(',', $langIds) . ')';

        $rows = Db::getInstance()->executeS($sql);
        if (!$rows) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $ic = (int) $row['id_category'];
            $il = (int) $row['id_lang'];
            if (!isset($map[$ic])) {
                $map[$ic] = [];
            }
            $map[$ic][$il] = $row;
        }

        return $map;
    }

    /**
     * Check if a category has content in primary fields
     * v1.8.0: Used for smart fill-missing detection
     *
     * NOTE: Queries database directly to avoid PrestaShop's language fallback behavior
     * (Category object returns default language content when target language is empty)
     *
     * @param int $idCategory
     * @param int $idLang
     * @param array $fields Fields to check (description, meta_title, meta_description)
     * @param array|null $preloadMap Optional map from preloadCategoryLangContentMap()
     *
     * @return bool True if ALL specified fields have content
     */
    protected function categoryHasPrimaryContent($idCategory, $idLang, array $fields, $preloadMap = null)
    {
        $row = null;
        if ($preloadMap !== null && isset($preloadMap[(int) $idCategory][(int) $idLang])) {
            $row = $preloadMap[(int) $idCategory][(int) $idLang];
        } else {
            $sql = 'SELECT `description`, `meta_title`, `meta_description`
                    FROM `' . _DB_PREFIX_ . 'category_lang`
                    WHERE `id_category` = ' . (int) $idCategory . '
                    AND `id_lang` = ' . (int) $idLang . '
                    AND `id_shop` = ' . (int) $this->idShop;

            $row = Db::getInstance()->getRow($sql);
        }

        return $this->categoryLangRowHasFilledFields($row, $fields);
    }

    /**
     * @param array|null $row category_lang row
     * @param array $fields
     *
     * @return bool
     */
    protected function categoryLangRowHasFilledFields($row, array $fields)
    {
        if (empty($row) || !is_array($row)) {
            return false;
        }

        foreach ($fields as $field) {
            $value = '';
            switch ($field) {
                case 'description':
                    $value = isset($row['description']) ? $row['description'] : '';
                    break;
                case 'meta_title':
                    $value = isset($row['meta_title']) ? $row['meta_title'] : '';
                    break;
                case 'meta_description':
                    $value = isset($row['meta_description']) ? $row['meta_description'] : '';
                    break;
                default:
                    $value = '';
            }

            $value = trim(strip_tags($value));
            if ($value === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $manufacturerIds
     * @param array $langIds
     *
     * @return array<int, array<int, array>>
     */
    protected function preloadManufacturerLangContentMap(array $manufacturerIds, array $langIds)
    {
        $manufacturerIds = array_values(array_unique(array_map('intval', $manufacturerIds)));
        $langIds = array_values(array_unique(array_map('intval', $langIds)));
        if (empty($manufacturerIds) || empty($langIds)) {
            return [];
        }

        $cols = '`id_manufacturer`, `id_lang`, `description`, `short_description`, `meta_title`, `meta_description`';
        if (MlCategoryAiGenerator::hasManufacturerMetaKeywordsSupport()) {
            $cols .= ', `meta_keywords`';
        }

        $sql = 'SELECT ' . $cols . '
                FROM `' . _DB_PREFIX_ . 'manufacturer_lang`
                WHERE `id_manufacturer` IN (' . implode(',', $manufacturerIds) . ')
                AND `id_lang` IN (' . implode(',', $langIds) . ')';
        if (MlCategoryAiGenerator::manufacturerLangTableHasIdShop()) {
            $sql .= ' AND `id_shop` = ' . (int) $this->idShop;
        }

        $rows = Db::getInstance()->executeS($sql);
        if (!$rows) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $im = (int) $row['id_manufacturer'];
            $il = (int) $row['id_lang'];
            if (!isset($map[$im])) {
                $map[$im] = [];
            }
            $map[$im][$il] = $row;
        }

        return $map;
    }

    /**
     * @param int $idManufacturer
     * @param int $idLang
     * @param array $fields
     * @param array|null $preloadMap
     *
     * @return bool
     */
    protected function manufacturerHasPrimaryContent($idManufacturer, $idLang, array $fields, $preloadMap = null)
    {
        $row = null;
        if ($preloadMap !== null && isset($preloadMap[(int) $idManufacturer][(int) $idLang])) {
            $row = $preloadMap[(int) $idManufacturer][(int) $idLang];
        } else {
            $cols = '`description`, `short_description`, `meta_title`, `meta_description`';
            if (MlCategoryAiGenerator::hasManufacturerMetaKeywordsSupport()) {
                $cols .= ', `meta_keywords`';
            }
            $sql = 'SELECT ' . $cols . '
                    FROM `' . _DB_PREFIX_ . 'manufacturer_lang`
                    WHERE `id_manufacturer` = ' . (int) $idManufacturer . '
                    AND `id_lang` = ' . (int) $idLang;
            if (MlCategoryAiGenerator::manufacturerLangTableHasIdShop()) {
                $sql .= ' AND `id_shop` = ' . (int) $this->idShop;
            }
            $row = Db::getInstance()->getRow($sql);
        }

        return $this->manufacturerLangRowHasFilledFields($row, $fields);
    }

    /**
     * @param array|null $row
     * @param array $fields
     *
     * @return bool
     */
    protected function manufacturerLangRowHasFilledFields($row, array $fields)
    {
        if (empty($row) || !is_array($row)) {
            return false;
        }

        foreach ($fields as $field) {
            if ($field === Mlcategoryaidescription::FIELD_META_KEYWORDS
                && !MlCategoryAiGenerator::hasManufacturerMetaKeywordsSupport()) {
                continue;
            }
            $value = '';
            switch ($field) {
                case 'description':
                    $value = isset($row['description']) ? $row['description'] : '';
                    break;
                case 'short_description':
                    $value = isset($row['short_description']) ? $row['short_description'] : '';
                    break;
                case 'meta_title':
                    $value = isset($row['meta_title']) ? $row['meta_title'] : '';
                    break;
                case 'meta_description':
                    $value = isset($row['meta_description']) ? $row['meta_description'] : '';
                    break;
                case 'meta_keywords':
                    $value = isset($row['meta_keywords']) ? $row['meta_keywords'] : '';
                    break;
                default:
                    $value = '';
            }

            $value = trim(strip_tags($value));
            if ($value === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Find categories that need translation only (have primary content but missing target languages)
     * v1.8.0: Used for "Translate Missing" feature to recover from interrupted jobs
     *
     * @param int $primaryLangId Source language ID
     * @param array $targetLangIds Target language IDs to check
     * @param array $fields Fields to check (description, meta_title, meta_description)
     * @param int $idShop Shop ID (default: current context)
     *
     * @return array Array of category IDs that need translation
     */
    public function findCategoriesNeedingTranslation($primaryLangId, $targetLangIds, $fields, $idShop = null)
    {
        if ($idShop === null) {
            $idShop = $this->idShop;
        }

        $categoriesNeedingTranslation = [];

        // Get all categories for this shop
        $sql = 'SELECT c.id_category
                FROM `' . _DB_PREFIX_ . 'category` c
                INNER JOIN `' . _DB_PREFIX_ . 'category_shop` cs 
                    ON c.id_category = cs.id_category AND cs.id_shop = ' . (int) $idShop . '
                WHERE c.id_category > 2
                ORDER BY c.id_category';

        $categories = Db::getInstance()->executeS($sql);

        foreach ($categories as $cat) {
            $idCategory = (int) $cat['id_category'];

            // Check if primary language has content
            if (!$this->categoryHasPrimaryContent($idCategory, $primaryLangId, $fields)) {
                continue; // Skip - needs OpenAI first
            }

            // Check if any target language is missing content
            foreach ($targetLangIds as $targetLangId) {
                if (!$this->categoryHasPrimaryContent($idCategory, (int) $targetLangId, $fields)) {
                    // This category needs translation for this target language
                    $categoriesNeedingTranslation[$idCategory] = $idCategory;
                    break; // Only need to add once
                }
            }
        }

        return array_values($categoriesNeedingTranslation);
    }

    /**
     * Create a translate-only job for categories with missing translations
     * v1.8.0: Recovery feature for interrupted jobs
     *
     * @param int $primaryLangId Source language ID
     * @param array $targetLangIds Target language IDs
     * @param array $fields Fields to translate
     * @param string $writeMode Write mode (fill_missing recommended)
     *
     * @return int|false Job ID or false on failure
     */
    public function createTranslateOnlyJob($primaryLangId, $targetLangIds, $fields, $writeMode = 'fill_missing')
    {
        // Find categories that need translation
        $categoryIds = $this->findCategoriesNeedingTranslation($primaryLangId, $targetLangIds, $fields);

        if (empty($categoryIds)) {
            return false; // Nothing to translate
        }

        $probeJob = [
            'entity_type' => Mlcategoryaidescription::ENTITY_CATEGORY,
            'category_ids' => array_map('intval', $categoryIds),
            'language_ids' => array_map('intval', $targetLangIds),
            'fields_to_generate' => $fields,
            'write_mode' => $writeMode,
            'use_google_translate' => true,
            'primary_language_id' => (int) $primaryLangId,
            'translate_language_ids' => array_map('intval', $targetLangIds),
            'phase' => 'translate_only',
        ];
        $totalItems = count($this->buildItemsList($probeJob));

        if ($totalItems < 1) {
            return false;
        }

        // Create the job
        $result = Db::getInstance()->insert('mlcategoryai_job_queue', [
            'id_shop' => (int) $this->idShop,
            'entity_type' => pSQL(Mlcategoryaidescription::ENTITY_CATEGORY),
            'category_ids' => pSQL(json_encode($categoryIds)),
            'language_ids' => pSQL(json_encode($targetLangIds)), // Target languages
            'fields_to_generate' => pSQL(json_encode($fields)),
            'total_items' => (int) $totalItems,
            'processed_items' => 0,
            'failed_items' => 0,
            'current_position' => 0,
            'status' => self::STATUS_PENDING,
            'write_mode' => pSQL($writeMode),
            'use_google_translate' => 1,
            'primary_language_id' => (int) $primaryLangId,
            'translate_language_ids' => pSQL(json_encode($targetLangIds)),
            'phase' => 'translate_only', // Special phase to indicate translate-only mode
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$result) {
            return false;
        }

        return (int) Db::getInstance()->Insert_ID();
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
            if (is_array($value) && array_key_exists('type', $value) && $value['type'] === 'sql') {
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

        // Pending so the next processNextBatch sets running + started_at (was incorrectly set to running here)
        return $this->updateJobStatus($idJob, self::STATUS_PENDING);
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
        $generator = new MlCategoryAiGenerator($module, $this->idShop);
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
        $generator = new MlCategoryAiGenerator($module, $this->idShop);
        $requestDelay = (int) Configuration::get(Mlcategoryaidescription::CONFIG_REQUEST_DELAY);

        $processed = 0;
        $failed = 0;
        $skipped = 0;
        $results = [];
        $errors = [];
        $tokensInput = 0;
        $tokensOutput = 0;

        $entityType = isset($job['entity_type']) ? $job['entity_type'] : Mlcategoryaidescription::ENTITY_CATEGORY;

        foreach ($batchItems as $index => $item) {
            try {
                $source = isset($item['source']) ? $item['source'] : 'openai';

                if ($entityType === Mlcategoryaidescription::ENTITY_MANUFACTURER) {
                    if ($source === 'google_translate') {
                        $result = $generator->translateManufacturerBatch(
                            (int) $item['id_manufacturer'],
                            (int) $item['id_lang'],
                            $item['fields'],
                            (int) $item['source_lang_id'],
                            $job['write_mode']
                        );
                    } else {
                        $result = $generator->generateManufacturerBatch(
                            (int) $item['id_manufacturer'],
                            (int) $item['id_lang'],
                            $item['fields'],
                            $job['write_mode']
                        );
                    }
                } elseif ($source === 'google_translate') {
                    // Google Translate: translate all fields from primary language
                    $result = $generator->translateCategoryBatch(
                        (int) $item['id_category'],
                        (int) $item['id_lang'],
                        $item['fields'],
                        (int) $item['source_lang_id'],
                        $job['write_mode']
                    );
                } else {
                    // OpenAI batch generation (all fields in one call)
                    $result = $generator->generateCategoryBatch(
                        (int) $item['id_category'],
                        (int) $item['id_lang'],
                        $item['fields'],
                        $job['write_mode']
                    );
                }
            } catch (Exception $e) {
                $result = [
                    'success' => false,
                    'skipped' => false,
                    'error' => 'Exception: ' . $e->getMessage(),
                    'tokens' => 0,
                    'results' => [],
                ];
            }

            $resultRow = [
                'id_lang' => $item['id_lang'],
                'fields' => $item['fields'],
                'source' => $source,
                'success' => $result['success'],
                'skipped' => isset($result['skipped']) ? $result['skipped'] : false,
                'error' => isset($result['error']) ? $result['error'] : '',
                'field_results' => isset($result['results']) ? $result['results'] : [],
            ];
            if ($entityType === Mlcategoryaidescription::ENTITY_MANUFACTURER) {
                $resultRow['id_manufacturer'] = $item['id_manufacturer'];
            } else {
                $resultRow['id_category'] = $item['id_category'];
            }
            $results[] = $resultRow;

            if (isset($result['skipped']) && $result['skipped']) {
                ++$skipped;
                ++$processed; // Skipped counts as processed
            } elseif ($result['success']) {
                ++$processed;
                $tokensInput += isset($result['tokens']) ? (int) ($result['tokens'] * 0.7) : 0; // Estimate
                $tokensOutput += isset($result['tokens']) ? (int) ($result['tokens'] * 0.3) : 0;
            } else {
                ++$failed;
                if ($entityType === Mlcategoryaidescription::ENTITY_MANUFACTURER) {
                    $errors[] = sprintf(
                        'Manufacturer %d, Lang %d (%s): %s',
                        $item['id_manufacturer'],
                        $item['id_lang'],
                        $source,
                        isset($result['error']) ? $result['error'] : 'Unknown error'
                    );
                } else {
                    $errors[] = sprintf(
                        'Category %d, Lang %d (%s): %s',
                        $item['id_category'],
                        $item['id_lang'],
                        $source,
                        isset($result['error']) ? $result['error'] : 'Unknown error'
                    );
                }
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
