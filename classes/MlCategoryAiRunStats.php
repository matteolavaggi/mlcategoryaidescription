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
 * Lightweight performance metrics tracker for AI generation runs
 */
class MlCategoryAiRunStats
{
    /**
     * @var int|null Current run ID
     */
    protected $idRun = null;

    /**
     * @var float Start time in microseconds
     */
    protected $startTime;

    /**
     * @var int Total input tokens
     */
    protected $tokensInput = 0;

    /**
     * @var int Total output tokens
     */
    protected $tokensOutput = 0;

    /**
     * @var int Total request times in ms (for averaging)
     */
    protected $totalRequestTimeMs = 0;

    /**
     * @var int Number of API requests made
     */
    protected $requestCount = 0;

    /**
     * Start tracking a new run
     *
     * @param int $idJob Job ID
     * @param int $categoriesCount Number of categories
     * @param int $languagesCount Number of languages
     * @param int $fieldsCount Number of field types
     * @param string $writeMode Write mode (overwrite|fill_missing)
     * @param int $parallelRequests Number of parallel requests
     *
     * @return int|false Run ID or false on failure
     */
    public function startRun($idJob, $categoriesCount, $languagesCount, $fieldsCount, $writeMode, $parallelRequests = 1)
    {
        $this->startTime = microtime(true);
        $this->tokensInput = 0;
        $this->tokensOutput = 0;
        $this->totalRequestTimeMs = 0;
        $this->requestCount = 0;

        $result = Db::getInstance()->insert('mlcategoryai_run_stats', [
            'id_job' => (int) $idJob,
            'id_shop' => (int) Context::getContext()->shop->id,
            'started_at' => date('Y-m-d H:i:s'),
            'categories_count' => (int) $categoriesCount,
            'languages_count' => (int) $languagesCount,
            'fields_count' => (int) $fieldsCount,
            'write_mode' => pSQL($writeMode),
            'parallel_requests' => (int) $parallelRequests,
        ]);

        if ($result) {
            $this->idRun = (int) Db::getInstance()->Insert_ID();

            return $this->idRun;
        }

        return false;
    }

    /**
     * Add tokens from a request (call after each API request)
     *
     * @param int $inputTokens Input tokens used
     * @param int $outputTokens Output tokens used
     * @param int $requestTimeMs Request time in milliseconds
     */
    public function addRequestMetrics($inputTokens, $outputTokens, $requestTimeMs)
    {
        $this->tokensInput += (int) $inputTokens;
        $this->tokensOutput += (int) $outputTokens;
        $this->totalRequestTimeMs += (int) $requestTimeMs;
        ++$this->requestCount;
    }

    /**
     * Complete the run and save final metrics
     *
     * @param int $itemsProcessed Number of items successfully processed
     * @param int $itemsSkipped Number of items skipped
     * @param int $itemsFailed Number of items failed
     *
     * @return bool
     */
    public function completeRun($itemsProcessed, $itemsSkipped, $itemsFailed)
    {
        if (!$this->idRun) {
            return false;
        }

        $executionTimeMs = (int) ((microtime(true) - $this->startTime) * 1000);
        $avgRequestTimeMs = $this->requestCount > 0
            ? (int) ($this->totalRequestTimeMs / $this->requestCount)
            : null;

        return Db::getInstance()->update(
            'mlcategoryai_run_stats',
            [
                'completed_at' => date('Y-m-d H:i:s'),
                'execution_time_ms' => $executionTimeMs,
                'items_processed' => (int) $itemsProcessed,
                'items_skipped' => (int) $itemsSkipped,
                'items_failed' => (int) $itemsFailed,
                'tokens_input' => $this->tokensInput,
                'tokens_output' => $this->tokensOutput,
                'avg_request_time_ms' => $avgRequestTimeMs,
            ],
            '`id_run` = ' . (int) $this->idRun
        );
    }

    /**
     * Get recent run statistics
     *
     * @param int $limit Number of runs to retrieve
     *
     * @return array
     */
    public static function getRecentRuns($limit = 20)
    {
        return Db::getInstance()->executeS('
            SELECT *
            FROM `' . _DB_PREFIX_ . 'mlcategoryai_run_stats`
            WHERE `id_shop` = ' . (int) Context::getContext()->shop->id . '
            ORDER BY `started_at` DESC
            LIMIT ' . (int) $limit
        );
    }

    /**
     * Get aggregate statistics
     *
     * @return array
     */
    public static function getAggregateStats()
    {
        $row = Db::getInstance()->getRow('
            SELECT
                COUNT(*) as total_runs,
                SUM(items_processed) as total_items,
                SUM(tokens_input) as total_tokens_in,
                SUM(tokens_output) as total_tokens_out,
                AVG(execution_time_ms) as avg_execution_time_ms,
                AVG(avg_request_time_ms) as avg_request_time_ms
            FROM `' . _DB_PREFIX_ . 'mlcategoryai_run_stats`
            WHERE `id_shop` = ' . (int) Context::getContext()->shop->id . '
                AND `completed_at` IS NOT NULL
        ');

        return $row ?: [
            'total_runs' => 0,
            'total_items' => 0,
            'total_tokens_in' => 0,
            'total_tokens_out' => 0,
            'avg_execution_time_ms' => 0,
            'avg_request_time_ms' => 0,
        ];
    }

    /**
     * Clean up old run stats (keep last N days)
     *
     * @param int $daysToKeep Number of days to keep
     *
     * @return bool
     */
    public static function cleanupOldRuns($daysToKeep = 30)
    {
        return Db::getInstance()->execute('
            DELETE FROM `' . _DB_PREFIX_ . 'mlcategoryai_run_stats`
            WHERE `started_at` < DATE_SUB(NOW(), INTERVAL ' . (int) $daysToKeep . ' DAY)
        ');
    }
}
