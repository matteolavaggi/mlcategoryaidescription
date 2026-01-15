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
 * Simple file-based logger for debugging API calls and generation process
 */
class MlCategoryAiLogger
{
    /**
     * @var string Log file path
     */
    protected static $logFile = null;

    /**
     * @var bool Whether logging is enabled
     */
    protected static $enabled = true;

    /**
     * Get log file path
     *
     * @return string
     */
    public static function getLogFilePath()
    {
        if (self::$logFile === null) {
            self::$logFile = _PS_MODULE_DIR_ . 'mlcategoryaidescription/logs/debug.log';
        }

        return self::$logFile;
    }

    /**
     * Get log directory path
     *
     * @return string
     */
    public static function getLogDir()
    {
        return _PS_MODULE_DIR_ . 'mlcategoryaidescription/logs/';
    }

    /**
     * Ensure log directory exists
     *
     * @return bool
     */
    protected static function ensureLogDir()
    {
        $dir = self::getLogDir();
        if (!is_dir($dir)) {
            return @mkdir($dir, 0755, true);
        }

        return true;
    }

    /**
     * Write a log entry
     *
     * @param string $message Log message
     * @param string $level Log level (INFO, ERROR, DEBUG, WARN)
     *
     * @return bool
     */
    public static function log($message, $level = 'INFO')
    {
        if (!self::$enabled) {
            return false;
        }

        self::ensureLogDir();

        $timestamp = date('Y-m-d H:i:s.') . sprintf('%03d', (int) ((microtime(true) - floor(microtime(true))) * 1000));
        $line = sprintf("[%s] [%s] %s\n", $timestamp, strtoupper($level), $message);

        return (bool) @file_put_contents(self::getLogFilePath(), $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log info message
     *
     * @param string $message
     *
     * @return bool
     */
    public static function info($message)
    {
        return self::log($message, 'INFO');
    }

    /**
     * Log error message
     *
     * @param string $message
     *
     * @return bool
     */
    public static function error($message)
    {
        return self::log($message, 'ERROR');
    }

    /**
     * Log debug message
     *
     * @param string $message
     *
     * @return bool
     */
    public static function debug($message)
    {
        return self::log($message, 'DEBUG');
    }

    /**
     * Log warning message
     *
     * @param string $message
     *
     * @return bool
     */
    public static function warn($message)
    {
        return self::log($message, 'WARN');
    }

    /**
     * Start a new run section in log
     *
     * @param int $jobId
     *
     * @return bool
     */
    public static function startRun($jobId)
    {
        $separator = str_repeat('=', 80);

        return self::log($separator . "\n[JOB #$jobId] NEW RUN STARTED\n" . $separator, 'INFO');
    }

    /**
     * Get log file contents
     *
     * @param int $lines Number of lines to return (0 = all)
     *
     * @return string
     */
    public static function getLogContents($lines = 0)
    {
        $file = self::getLogFilePath();
        if (!file_exists($file)) {
            return 'No log file found.';
        }

        if ($lines === 0) {
            return file_get_contents($file);
        }

        // Get last N lines
        $content = file_get_contents($file);
        $allLines = explode("\n", $content);
        $lastLines = array_slice($allLines, -$lines);

        return implode("\n", $lastLines);
    }

    /**
     * Get log file size
     *
     * @return int
     */
    public static function getLogSize()
    {
        $file = self::getLogFilePath();
        if (!file_exists($file)) {
            return 0;
        }

        return filesize($file);
    }

    /**
     * Clear log file
     *
     * @return bool
     */
    public static function clearLog()
    {
        $file = self::getLogFilePath();
        if (file_exists($file)) {
            return (bool) @file_put_contents($file, '');
        }

        return true;
    }

    /**
     * Rotate log if too large (> 5MB)
     *
     * @return bool
     */
    public static function rotateIfNeeded()
    {
        $maxSize = 5 * 1024 * 1024; // 5MB
        if (self::getLogSize() > $maxSize) {
            $file = self::getLogFilePath();
            $backupFile = str_replace('.log', '.old.log', $file);
            @unlink($backupFile);
            @rename($file, $backupFile);

            return true;
        }

        return false;
    }
}
