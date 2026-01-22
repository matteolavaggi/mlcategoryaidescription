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

class MlcategoryaidescriptionCronModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool Disable display of header/footer
     */
    public $display_header = false;
    public $display_footer = false;

    /**
     * @var int Max execution time in seconds
     */
    const MAX_EXECUTION_TIME = 55;

    /**
     * Initialize controller
     */
    public function init()
    {
        parent::init();

        // Set plain text content type
        header('Content-Type: text/plain');
    }

    /**
     * Process cron request
     */
    public function postProcess()
    {
        // Check if cron is enabled
        if (!Configuration::get(Mlcategoryaidescription::CONFIG_CRON_ENABLED)) {
            exit('Cron processing is disabled');
        }

        // Validate cron token
        $token = Tools::getValue('token');
        $validToken = Configuration::get(Mlcategoryaidescription::CONFIG_CRON_TOKEN);

        if (empty($token) || $token !== $validToken) {
            header('HTTP/1.1 403 Forbidden');
            exit('Invalid token');
        }

        require_once _PS_MODULE_DIR_ . 'mlcategoryaidescription/classes/MlCategoryAiJobQueue.php';

        $jobQueue = new MlCategoryAiJobQueue();

        // Check for specific job ID
        $idJob = (int) Tools::getValue('job_id');

        if ($idJob) {
            $job = $jobQueue->getJob($idJob);
        } else {
            // Get any active job
            $job = $jobQueue->getActiveJob();
        }

        if (!$job) {
            exit('No pending jobs');
        }

        echo 'Processing job #' . $job['id_job'] . "\n";
        echo 'Status: ' . $job['status'] . "\n";
        echo 'Progress: ' . $job['processed_items'] . '/' . $job['total_items'] . "\n\n";

        $startTime = time();
        $batchSize = (int) Configuration::get(Mlcategoryaidescription::CONFIG_BATCH_SIZE);
        if ($batchSize < 1) {
            $batchSize = 5;
        }

        $totalProcessed = 0;
        $totalFailed = 0;

        // Process batches until timeout or completion
        while (true) {
            // Check execution time
            $elapsedTime = time() - $startTime;
            if ($elapsedTime >= self::MAX_EXECUTION_TIME) {
                echo "\nTimeout reached after " . $elapsedTime . " seconds\n";
                break;
            }

            $result = $jobQueue->processNextBatch($job['id_job'], $this->module, $batchSize);

            if (!$result['success']) {
                echo 'Error: ' . ($result['error'] ?? 'Unknown error') . "\n";
                break;
            }

            $totalProcessed += count($result['batch_results'] ?? []);

            // Count failures in this batch
            $batchFailed = 0;
            if (isset($result['batch_results'])) {
                foreach ($result['batch_results'] as $batchResult) {
                    if (!$batchResult['success'] && !$batchResult['skipped']) {
                        ++$batchFailed;
                    }
                }
            }
            $totalFailed += $batchFailed;

            echo sprintf(
                "Batch processed: %d items (Progress: %.1f%%)\n",
                count($result['batch_results'] ?? []),
                $result['progress_percent'] ?? 0
            );

            if ($result['completed']) {
                echo "\nJob completed!\n";
                echo 'Total processed: ' . $result['processed'] . "\n";
                echo 'Total failed: ' . $result['failed'] . "\n";
                break;
            }

            // Small delay between batches
            usleep(100000); // 0.1 seconds
        }

        echo "\n--- Cron Summary ---\n";
        echo 'Items processed this run: ' . $totalProcessed . "\n";
        echo 'Failures this run: ' . $totalFailed . "\n";
        echo 'Execution time: ' . (time() - $startTime) . " seconds\n";

        // Exit to prevent Smarty template rendering
        exit;
    }
}
