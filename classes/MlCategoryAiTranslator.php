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

require_once __DIR__ . '/MlCategoryAiLogger.php';

/**
 * Google Translate API Client
 *
 * Handles translation of content using Google Cloud Translation API v2
 */
class MlCategoryAiTranslator
{
    /**
     * Google Translate API v2 endpoint
     */
    const API_ENDPOINT = 'https://translation.googleapis.com/language/translate/v2';

    /**
     * @var string API key
     */
    protected $apiKey;

    /**
     * @var string Last error message
     */
    protected $lastError = '';

    /**
     * @var int Last request time in milliseconds
     */
    protected $lastRequestTimeMs = 0;

    /**
     * @var int Total characters translated in last operation
     */
    protected $lastCharactersTranslated = 0;

    /**
     * ISO code mapping: PrestaShop code => Google Translate code
     * Maps non-standard PrestaShop codes to Google API expected codes
     */
    protected static $isoCodeMap = [
        'gb' => 'en',  // British English
        'br' => 'pt',  // Brazilian Portuguese
        'mx' => 'es',  // Mexican Spanish
        'qc' => 'fr',  // Quebec French
        'tw' => 'zh-TW',  // Traditional Chinese (Taiwan)
        'cn' => 'zh-CN',  // Simplified Chinese
    ];

    /**
     * Constructor
     *
     * @param string $apiKey Google Translate API key
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Create translator from module configuration
     *
     * @param Module $module Module instance
     *
     * @return MlCategoryAiTranslator
     */
    public static function createFromConfig($module)
    {
        $encryptedKey = Configuration::get(Mlcategoryaidescription::CONFIG_GOOGLE_TRANSLATE_API_KEY);
        $apiKey = $module->decryptApiKey($encryptedKey);

        return new self($apiKey);
    }

    /**
     * Map PrestaShop ISO code to Google Translate ISO code
     *
     * @param string $psIsoCode PrestaShop language ISO code
     *
     * @return string Google Translate ISO code
     */
    public function mapIsoCode($psIsoCode)
    {
        $code = strtolower($psIsoCode);

        return isset(self::$isoCodeMap[$code]) ? self::$isoCodeMap[$code] : $code;
    }

    /**
     * Translate a single text
     *
     * @param string $text Text to translate
     * @param string $sourceLang Source language ISO code
     * @param string $targetLang Target language ISO code
     * @param string $format 'text' or 'html'
     *
     * @return string|false Translated text or false on error
     */
    public function translate($text, $sourceLang, $targetLang, $format = 'text')
    {
        $result = $this->translateBatch([$text], $sourceLang, $targetLang, $format);

        if ($result === false || empty($result)) {
            return false;
        }

        return $result[0];
    }

    /**
     * Translate multiple texts in a single API call (more efficient)
     *
     * @param array $texts Array of texts to translate
     * @param string $sourceLang Source language ISO code
     * @param string $targetLang Target language ISO code
     * @param string $format 'text' or 'html'
     *
     * @return array|false Array of translated texts or false on error
     */
    public function translateBatch($texts, $sourceLang, $targetLang, $format = 'text')
    {
        if (empty($this->apiKey)) {
            $this->lastError = 'Google Translate API key not configured';
            MlCategoryAiLogger::error($this->lastError);

            return false;
        }

        if (empty($texts)) {
            return [];
        }

        // Map ISO codes
        $source = $this->mapIsoCode($sourceLang);
        $target = $this->mapIsoCode($targetLang);

        // Calculate characters for logging
        $totalChars = 0;
        foreach ($texts as $text) {
            $totalChars += mb_strlen($text);
        }

        MlCategoryAiLogger::debug('Google Translate START', [
            'source' => $source,
            'target' => $target,
            'texts_count' => count($texts),
            'total_chars' => $totalChars,
            'format' => $format,
        ]);

        $startTime = microtime(true);

        // Build request data
        $requestData = [
            'q' => $texts,
            'source' => $source,
            'target' => $target,
            'format' => $format,
            'key' => $this->apiKey,
        ];

        // Make API request
        $response = $this->makeRequest(self::API_ENDPOINT, $requestData);

        $this->lastRequestTimeMs = (int) ((microtime(true) - $startTime) * 1000);

        if ($response === false) {
            return false;
        }

        // Parse response
        if (!isset($response['data']['translations'])) {
            $this->lastError = 'Invalid response format from Google Translate API';
            MlCategoryAiLogger::error($this->lastError, ['response' => $response]);

            return false;
        }

        $translations = [];
        foreach ($response['data']['translations'] as $translation) {
            $translations[] = $translation['translatedText'];
        }

        $this->lastCharactersTranslated = $totalChars;

        MlCategoryAiLogger::debug('Google Translate END', [
            'time_ms' => $this->lastRequestTimeMs,
            'translations_count' => count($translations),
            'chars_translated' => $totalChars,
        ]);

        return $translations;
    }

    /**
     * Validate API key by making a test translation request
     *
     * @return bool True if API key is valid
     */
    public function validateApiKey()
    {
        if (empty($this->apiKey)) {
            $this->lastError = 'API key is empty';

            return false;
        }

        // Make a simple test translation
        $result = $this->translate('Hello', 'en', 'es', 'text');

        if ($result === false) {
            return false;
        }

        // Check if we got a response (should be "Hola" or similar)
        return !empty($result);
    }

    /**
     * Get last error message
     *
     * @return string
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Get last request time in milliseconds
     *
     * @return int
     */
    public function getLastRequestTimeMs()
    {
        return $this->lastRequestTimeMs;
    }

    /**
     * Get characters translated in last operation
     *
     * @return int
     */
    public function getLastCharactersTranslated()
    {
        return $this->lastCharactersTranslated;
    }

    /**
     * Make HTTP POST request to Google Translate API
     *
     * @param string $url API endpoint
     * @param array $data Request data
     *
     * @return array|false Decoded response or false on error
     */
    protected function makeRequest($url, $data)
    {
        $ch = curl_init();

        // Google Translate API v2 accepts query parameters for simple requests
        // But for batch translations, we use POST with JSON body
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);

        // Configure SSL certificates
        $this->configureSslOptions($ch);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            $this->lastError = 'cURL error: ' . $curlError;
            MlCategoryAiLogger::error($this->lastError);

            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['error']['message'])
                ? $errorData['error']['message']
                : 'HTTP error ' . $httpCode;

            // Check for specific error codes
            if ($httpCode === 403) {
                $errorMessage = 'API key invalid or quota exceeded: ' . $errorMessage;
            } elseif ($httpCode === 429) {
                $errorMessage = 'Rate limit exceeded: ' . $errorMessage;
            }

            $this->lastError = $errorMessage;
            MlCategoryAiLogger::error('Google Translate API error', [
                'http_code' => $httpCode,
                'message' => $errorMessage,
                'response' => substr($response, 0, 500),
            ]);

            return false;
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = 'JSON decode error: ' . json_last_error_msg();
            MlCategoryAiLogger::error($this->lastError);

            return false;
        }

        return $decoded;
    }

    /**
     * Configure SSL options for cURL
     *
     * @param resource $ch cURL handle
     *
     * @return void
     */
    protected function configureSslOptions($ch)
    {
        // Try to find CA bundle in common locations
        $caBundlePaths = [
            // PrestaShop bundled
            _PS_ROOT_DIR_ . '/var/ca-bundle.crt',
            _PS_MODULE_DIR_ . 'mlcategoryaidescription/ca-bundle.crt',
            // Linux common paths
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/ca-bundle.pem',
            '/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem',
            // Windows WAMP/XAMPP
            'C:/wamp64/bin/php/cacert.pem',
            'C:/xampp/php/extras/ssl/cacert.pem',
            'C:/laragon/etc/ssl/cacert.pem',
        ];

        $caBundle = null;
        foreach ($caBundlePaths as $path) {
            if (file_exists($path)) {
                $caBundle = $path;
                break;
            }
        }

        if ($caBundle) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
        } else {
            // Fallback: use system default
            $phpCaFile = ini_get('openssl.cafile');
            $curlCaInfo = ini_get('curl.cainfo');

            if (!empty($phpCaFile) && file_exists($phpCaFile)) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_CAINFO, $phpCaFile);
            } elseif (!empty($curlCaInfo) && file_exists($curlCaInfo)) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_CAINFO, $curlCaInfo);
            } else {
                // Enable verification but let curl find certificates
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            }
        }
    }
}
