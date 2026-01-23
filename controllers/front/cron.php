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
     * @var string Lock file path
     */
    private $lockFile;

    /**
     * @var resource|null Lock file handle
     */
    private $lockHandle;

    /**
     * Initialize controller
     */
    public function init()
    {
        parent::init();

        // Set plain text content type
        header('Content-Type: text/plain');

        // Set lock file path
        $this->lockFile = _PS_MODULE_DIR_ . 'mlcategoryaidescription/logs/cron.lock';
    }

    /**
     * Check if running from CLI
     *
     * @return bool
     */
    private function isCli()
    {
        return php_sapi_name() === 'cli' || defined('STDIN');
    }

    /**
     * Acquire exclusive lock to prevent concurrent runs
     *
     * @return bool True if lock acquired, false if another process is running
     */
    private function acquireLock()
    {
        $this->lockHandle = fopen($this->lockFile, 'c');
        if (!$this->lockHandle) {
            return false;
        }

        // Try to acquire exclusive non-blocking lock
        if (!flock($this->lockHandle, LOCK_EX | LOCK_NB)) {
            fclose($this->lockHandle);
            $this->lockHandle = null;

            return false;
        }

        // Write PID and timestamp to lock file
        ftruncate($this->lockHandle, 0);
        fwrite($this->lockHandle, json_encode([
            'pid' => getmypid(),
            'started_at' => date('Y-m-d H:i:s'),
        ]));
        fflush($this->lockHandle);

        return true;
    }

    /**
     * Release lock
     */
    public function releaseLock()
    {
        if ($this->lockHandle) {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
            $this->lockHandle = null;
        }
    }

    /**
     * Get info about currently running process (if any)
     *
     * @return array|null
     */
    private function getLockInfo()
    {
        if (!file_exists($this->lockFile)) {
            return null;
        }

        $content = file_get_contents($this->lockFile);
        if (empty($content)) {
            return null;
        }

        return json_decode($content, true);
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

        // Try to acquire lock
        if (!$this->acquireLock()) {
            $lockInfo = $this->getLockInfo();
            echo "Another cron process is already running.\n";
            if ($lockInfo) {
                echo 'PID: ' . ($lockInfo['pid'] ?? 'unknown') . "\n";
                echo 'Started: ' . ($lockInfo['started_at'] ?? 'unknown') . "\n";
            }
            exit;
        }

        // Register shutdown function to release lock
        register_shutdown_function([$this, 'releaseLock']);

        // Set unlimited execution time for CLI mode, 5 min for web
        if ($this->isCli()) {
            set_time_limit(0);
            ini_set('max_execution_time', '0');
            echo "Running in CLI mode (no timeout)\n\n";
        } else {
            // For web requests, set a reasonable timeout (5 minutes)
            set_time_limit(300);
            ini_set('max_execution_time', '300');
            echo "Running in web mode (5 min timeout)\n\n";
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
            echo "No pending jobs\n";
            $this->releaseLock();
            exit;
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
        $lastProgressOutput = 0;

        // Process batches until completion
        while (true) {
            $result = $jobQueue->processNextBatch($job['id_job'], $this->module, $batchSize);

            if (!$result['success']) {
                echo 'Error: ' . ($result['error'] ?? 'Unknown error') . "\n";
                break;
            }

            $batchCount = count($result['batch_results'] ?? []);
            $totalProcessed += $batchCount;

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

            // Output progress every 10 seconds or every 100 items
            $now = time();
            if ($now - $lastProgressOutput >= 10 || $totalProcessed % 100 < $batchCount) {
                $elapsed = $now - $startTime;
                $rate = $elapsed > 0 ? round($totalProcessed / $elapsed, 1) : 0;
                echo sprintf(
                    "[%s] Progress: %.1f%% (%d/%d) - Rate: %.1f items/sec\n",
                    date('H:i:s'),
                    $result['progress_percent'] ?? 0,
                    $result['processed'] ?? 0,
                    $job['total_items'],
                    $rate
                );
                $lastProgressOutput = $now;

                // Flush output buffer for real-time monitoring
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            if ($result['completed']) {
                echo "\n=== Job completed! ===\n";
                echo 'Total processed: ' . $result['processed'] . "\n";
                echo 'Total failed: ' . $result['failed'] . "\n";
                break;
            }

            // Small delay between batches to avoid hammering the API
            usleep(100000); // 0.1 seconds
        }

        $elapsedTime = time() - $startTime;
        echo "\n--- Cron Summary ---\n";
        echo 'Items processed this run: ' . $totalProcessed . "\n";
        echo 'Failures this run: ' . $totalFailed . "\n";
        echo 'Execution time: ' . $elapsedTime . " seconds\n";
        echo 'Average rate: ' . ($elapsedTime > 0 ? round($totalProcessed / $elapsedTime, 2) : 0) . " items/sec\n";

        $this->releaseLock();

        // Exit to prevent Smarty template rendering
        exit;
    }
}
