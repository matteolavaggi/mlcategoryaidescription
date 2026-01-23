#!/usr/bin/env php
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
 *
 * CLI Cron Script - Run from command line for unlimited execution time
 *
 * Usage:
 *   php cron-cli.php
 *   php cron-cli.php --job=3
 */

// Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    die('This script must be run from command line');
}

// Set unlimited execution time
set_time_limit(0);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '512M');

// Parse command line arguments
$options = getopt('', ['job::', 'help']);

if (isset($options['help'])) {
    echo "ML Category AI Description - CLI Cron\n";
    echo "=====================================\n\n";
    echo "Usage:\n";
    echo "  php cron-cli.php           Process all pending jobs\n";
    echo "  php cron-cli.php --job=3   Process specific job ID\n";
    echo "  php cron-cli.php --help    Show this help\n\n";
    exit(0);
}

// Find PrestaShop root directory
$psRoot = dirname(__FILE__, 3);
$configFile = $psRoot . '/config/config.inc.php';

if (!file_exists($configFile)) {
    die("Error: Cannot find PrestaShop config at: $configFile\n");
}

// Load PrestaShop
require_once $configFile;

// Check if cron is enabled
if (!Configuration::get('MLCATEGORYAI_CRON_ENABLED')) {
    echo "Cron processing is disabled in module settings.\n";
    exit(0);
}

// Lock file to prevent concurrent runs
$lockFile = dirname(__FILE__) . '/logs/cron.lock';
$lockHandle = fopen($lockFile, 'c');

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    $lockInfo = json_decode(file_get_contents($lockFile), true);
    echo "Another cron process is already running.\n";
    if ($lockInfo) {
        echo 'PID: ' . ($lockInfo['pid'] ?? 'unknown') . "\n";
        echo 'Started: ' . ($lockInfo['started_at'] ?? 'unknown') . "\n";
    }
    exit(1);
}

// Write lock info
ftruncate($lockHandle, 0);
fwrite($lockHandle, json_encode([
    'pid' => getmypid(),
    'started_at' => date('Y-m-d H:i:s'),
    'mode' => 'cli',
]));
fflush($lockHandle);

// Register cleanup on exit
register_shutdown_function(function () use ($lockHandle) {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
});

echo "ML Category AI Description - CLI Cron\n";
echo "=====================================\n";
echo "Running in CLI mode (no timeout)\n\n";

// Load module
require_once dirname(__FILE__) . '/mlcategoryaidescription.php';
require_once dirname(__FILE__) . '/classes/MlCategoryAiJobQueue.php';

$module = Module::getInstanceByName('mlcategoryaidescription');
if (!$module) {
    die("Error: Cannot load module\n");
}

$jobQueue = new MlCategoryAiJobQueue();

// Get job
$idJob = isset($options['job']) ? (int) $options['job'] : 0;

if ($idJob) {
    $job = $jobQueue->getJob($idJob);
    if (!$job) {
        die("Error: Job #$idJob not found\n");
    }
} else {
    $job = $jobQueue->getActiveJob();
    if (!$job) {
        echo "No pending jobs\n";
        exit(0);
    }
}

echo 'Processing job #' . $job['id_job'] . "\n";
echo 'Status: ' . $job['status'] . "\n";
echo 'Progress: ' . $job['processed_items'] . '/' . $job['total_items'] . "\n\n";

$startTime = time();
$batchSize = (int) Configuration::get('MLCATEGORYAI_BATCH_SIZE');
if ($batchSize < 1) {
    $batchSize = 5;
}

$totalProcessed = 0;
$totalFailed = 0;
$lastProgressOutput = 0;

// Process batches until completion
while (true) {
    $result = $jobQueue->processNextBatch($job['id_job'], $module, $batchSize);

    if (!$result['success']) {
        echo 'Error: ' . ($result['error'] ?? 'Unknown error') . "\n";
        break;
    }

    $batchCount = count($result['batch_results'] ?? []);
    $totalProcessed += $batchCount;

    // Count failures
    $batchFailed = 0;
    if (isset($result['batch_results'])) {
        foreach ($result['batch_results'] as $batchResult) {
            if (!$batchResult['success'] && !$batchResult['skipped']) {
                ++$batchFailed;
            }
        }
    }
    $totalFailed += $batchFailed;

    // Output progress every 10 seconds
    $now = time();
    if ($now - $lastProgressOutput >= 10 || $totalProcessed % 100 < $batchCount) {
        $elapsed = $now - $startTime;
        $rate = $elapsed > 0 ? round($totalProcessed / $elapsed, 1) : 0;
        
        // Calculate ETA
        $remaining = $job['total_items'] - ($result['processed'] ?? 0);
        $eta = $rate > 0 ? round($remaining / $rate / 60, 1) : 0;
        
        echo sprintf(
            "[%s] Progress: %.1f%% (%d/%d) - Rate: %.1f/sec - ETA: %.1f min\n",
            date('H:i:s'),
            $result['progress_percent'] ?? 0,
            $result['processed'] ?? 0,
            $job['total_items'],
            $rate,
            $eta
        );
        $lastProgressOutput = $now;
    }

    if ($result['completed']) {
        echo "\n=== Job completed! ===\n";
        echo 'Total processed: ' . $result['processed'] . "\n";
        echo 'Total failed: ' . $result['failed'] . "\n";
        break;
    }

    // Small delay between batches
    usleep(100000); // 0.1 seconds
}

$elapsedTime = time() - $startTime;
echo "\n--- Summary ---\n";
echo 'Items processed: ' . $totalProcessed . "\n";
echo 'Failures: ' . $totalFailed . "\n";
echo 'Execution time: ' . gmdate('H:i:s', $elapsedTime) . "\n";
echo 'Average rate: ' . ($elapsedTime > 0 ? round($totalProcessed / $elapsedTime, 2) : 0) . " items/sec\n";
