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
 * AI API Client for OpenAI and compatible endpoints
 */
class MlCategoryAiClient
{
    /**
     * @var string API key
     */
    protected $apiKey;

    /**
     * @var string API endpoint URL
     */
    protected $endpoint;

    /**
     * @var string API provider (openai|azure|custom)
     */
    protected $provider;

    /**
     * @var string Model name
     */
    protected $model;

    /**
     * @var int Max tokens for response
     */
    protected $maxTokens;

    /**
     * @var float Temperature for generation
     */
    protected $temperature;

    /**
     * @var string Last error message
     */
    protected $lastError = '';

    /**
     * @var int Last tokens used (total for last operation)
     */
    protected $lastTokensUsed = 0;

    /**
     * @var int Last input tokens used
     */
    protected $lastInputTokens = 0;

    /**
     * @var int Last output tokens used
     */
    protected $lastOutputTokens = 0;

    /**
     * @var int Last request time in milliseconds
     */
    protected $lastRequestTimeMs = 0;

    /**
     * @var bool Enable prompt caching (OpenAI feature)
     */
    protected $enablePromptCache = true;

    /**
     * Models that require max_completion_tokens instead of max_tokens
     * These are typically newer reasoning/mini models
     */
    /**
     * Legacy models that still require max_tokens instead of max_completion_tokens
     * Most newer models (2024+) use max_completion_tokens
     */
    protected static $legacyMaxTokensModels = [
        'gpt-3.5-turbo',
        'gpt-4',
        'gpt-4-turbo',
        'gpt-4-0125',
        'gpt-4-1106',
        'text-davinci',
        'text-curie',
        'text-babbage',
        'text-ada',
    ];

    /**
     * Models that only support temperature = 1.0
     */
    protected static $fixedTemperatureModels = [
        'o1',
        'o1-mini',
        'o1-preview',
        'gpt-4o-mini',
        'gpt-5-nano',
        'gpt-5-mini',
    ];

    /**
     * Check if current model requires fixed temperature (1.0)
     *
     * @return bool
     */
    protected function requiresFixedTemperature()
    {
        $modelLower = strtolower($this->model);

        foreach (self::$fixedTemperatureModels as $pattern) {
            if (strpos($modelLower, strtolower($pattern)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the correct token limit parameter name for current model
     * Default: max_completion_tokens (for newer models 2024+)
     * Legacy: max_tokens (for older models like gpt-3.5, gpt-4)
     *
     * @return string 'max_completion_tokens' or 'max_tokens'
     */
    protected function getTokenLimitParamName()
    {
        $modelLower = strtolower($this->model);

        // Check if it's a legacy model that needs max_tokens
        foreach (self::$legacyMaxTokensModels as $pattern) {
            if (strpos($modelLower, strtolower($pattern)) !== false) {
                return 'max_tokens';
            }
        }

        // Default to max_completion_tokens for all newer models (2024+)
        return 'max_completion_tokens';
    }

    /**
     * Constructor
     *
     * @param string $apiKey
     * @param string $endpoint
     * @param string $provider
     * @param string $model
     * @param int $maxTokens
     * @param float $temperature
     */
    public function __construct(
        $apiKey,
        $endpoint = 'https://api.openai.com/v1',
        $provider = 'openai',
        $model = 'gpt-4o-mini',
        $maxTokens = 1000,
        $temperature = 0.7
    ) {
        $this->apiKey = $apiKey;
        $this->endpoint = rtrim($endpoint, '/');
        $this->provider = $provider;
        $this->model = $model;
        $this->maxTokens = (int) $maxTokens;
        $this->temperature = (float) $temperature;
    }

    /**
     * Create client from module configuration
     *
     * @param Module $module
     *
     * @return MlCategoryAiClient
     */
    public static function createFromConfig($module)
    {
        $apiKey = '';
        if (method_exists($module, 'getApiKey')) {
            $apiKey = $module->getApiKey();
        }

        return new self(
            $apiKey,
            Configuration::get(Mlcategoryaidescription::CONFIG_API_ENDPOINT),
            Configuration::get(Mlcategoryaidescription::CONFIG_API_PROVIDER),
            Configuration::get(Mlcategoryaidescription::CONFIG_API_MODEL),
            (int) Configuration::get(Mlcategoryaidescription::CONFIG_MAX_TOKENS),
            (float) Configuration::get(Mlcategoryaidescription::CONFIG_TEMPERATURE)
        );
    }

    /**
     * Send a prompt to the AI and get a response
     *
     * @param string $prompt The prompt to send
     * @param string $systemMessage Optional system message
     * @param string $cacheKey Optional cache key for prompt caching (field type)
     *
     * @return string|false Response text or false on error
     */
    public function generate($prompt, $systemMessage = '', $cacheKey = '')
    {
        $this->lastError = '';
        $this->lastTokensUsed = 0;
        $this->lastInputTokens = 0;
        $this->lastOutputTokens = 0;
        $this->lastRequestTimeMs = 0;

        if (empty($this->apiKey)) {
            $this->lastError = 'API key is not configured';

            return false;
        }

        $messages = [];

        if (!empty($systemMessage)) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemMessage,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        $requestData = [
            'model' => $this->model,
            'messages' => $messages,
        ];

        // Add temperature (mini/nano models only support 1.0)
        if (!$this->requiresFixedTemperature()) {
            $requestData['temperature'] = $this->temperature;
        }

        // Add token limit with correct parameter name for model
        // Newer models (o1, mini, nano) use max_completion_tokens, older use max_tokens
        if ($this->maxTokens > 0) {
            $tokenParam = $this->getTokenLimitParamName();
            $requestData[$tokenParam] = $this->maxTokens;
        }

        // Add prompt caching for OpenAI (reduces input token costs by up to 50%)
        if ($this->enablePromptCache && $this->provider === 'openai' && !empty($cacheKey)) {
            $requestData['prompt_cache_key'] = 'mlcategoryai-' . $cacheKey;
        }


        $url = $this->buildUrl();
        $response = $this->sendRequest($url, $requestData);

        if ($response === false) {
            return false;
        }

        return $this->parseResponse($response);
    }

    /**
     * Build API URL based on provider
     *
     * @return string
     */
    protected function buildUrl()
    {
        switch ($this->provider) {
            case 'azure':
                // Azure uses a different URL format
                return $this->endpoint . '/openai/deployments/' . $this->model . '/chat/completions?api-version=2024-02-01';

            case 'openai':
            case 'custom':
            default:
                return $this->endpoint . '/chat/completions';
        }
    }

    /**
     * Send HTTP request to API
     *
     * @param string $url
     * @param array $data
     *
     * @return array|false
     */
    protected function sendRequest($url, $data)
    {
        $headers = $this->buildHeaders();
        $startTime = microtime(true);

        MlCategoryAiLogger::debug('API request START - model=' . $this->model);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        $elapsed = round((microtime(true) - $startTime) * 1000);
        MlCategoryAiLogger::debug('API request END - ' . $elapsed . 'ms - httpCode=' . $httpCode);

        curl_close($ch);

        // Track request time
        $this->lastRequestTimeMs = (int) ((microtime(true) - $startTime) * 1000);

        if ($curlError) {
            $this->lastError = 'cURL error: ' . $curlError;
            MlCategoryAiLogger::error('cURL error: ' . $curlError);

            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['error']['message'])
                ? $errorData['error']['message']
                : 'HTTP error ' . $httpCode;
            $this->lastError = $errorMessage;

            // Log API errors for debugging
            MlCategoryAiLogger::error('API ERROR: ' . $errorMessage . ' | Model: ' . $this->model . ' | Response: ' . substr($response, 0, 500));

            return false;
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = 'Failed to parse API response';

            return false;
        }

        return $decoded;
    }

    /**
     * Build request headers based on provider
     *
     * @return array
     */
    protected function buildHeaders()
    {
        $headers = [
            'Content-Type: application/json',
        ];

        switch ($this->provider) {
            case 'azure':
                $headers[] = 'api-key: ' . $this->apiKey;
                break;

            case 'openai':
            case 'custom':
            default:
                $headers[] = 'Authorization: Bearer ' . $this->apiKey;
                break;
        }

        return $headers;
    }

    /**
     * Parse API response and extract generated text
     *
     * @param array $response
     *
     * @return string|false
     */
    protected function parseResponse($response)
    {
        // Track token usage (detailed)
        if (isset($response['usage'])) {
            $this->lastTokensUsed = (int) ($response['usage']['total_tokens'] ?? 0);
            $this->lastInputTokens = (int) ($response['usage']['prompt_tokens'] ?? 0);
            $this->lastOutputTokens = (int) ($response['usage']['completion_tokens'] ?? 0);
        }

        // Extract content from response
        if (isset($response['choices'][0]['message']['content'])) {
            return trim($response['choices'][0]['message']['content']);
        }

        $this->lastError = 'Unexpected API response format';

        return false;
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
     * Get last tokens used (total)
     *
     * @return int
     */
    public function getLastTokensUsed()
    {
        return $this->lastTokensUsed;
    }

    /**
     * Get last input tokens used
     *
     * @return int
     */
    public function getLastInputTokens()
    {
        return $this->lastInputTokens;
    }

    /**
     * Get last output tokens used
     *
     * @return int
     */
    public function getLastOutputTokens()
    {
        return $this->lastOutputTokens;
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
     * Send multiple prompts in parallel using curl_multi
     *
     * @param array $requests Array of ['prompt' => string, 'system' => string, 'cache_key' => string, 'id' => mixed]
     *
     * @return array Results indexed by 'id' with ['success' => bool, 'content' => string, 'error' => string, 'tokens_in' => int, 'tokens_out' => int, 'time_ms' => int]
     */
    public function generateParallel($requests)
    {
        if (empty($this->apiKey)) {
            $results = [];
            foreach ($requests as $req) {
                $results[$req['id']] = [
                    'success' => false,
                    'content' => '',
                    'error' => 'API key is not configured',
                    'tokens_in' => 0,
                    'tokens_out' => 0,
                    'time_ms' => 0,
                ];
            }

            return $results;
        }

        $url = $this->buildUrl();
        $headers = $this->buildHeaders();
        $multiHandle = curl_multi_init();
        $handles = [];
        $startTimes = [];

        // Prepare all requests
        foreach ($requests as $index => $req) {
            $messages = [];
            if (!empty($req['system'])) {
                $messages[] = ['role' => 'system', 'content' => $req['system']];
            }
            $messages[] = ['role' => 'user', 'content' => $req['prompt']];

            $requestData = [
                'model' => $this->model,
                'messages' => $messages,
            ];

            // Add temperature (mini/nano models only support 1.0)
            if (!$this->requiresFixedTemperature()) {
                $requestData['temperature'] = $this->temperature;
            }

            // Add token limit with correct parameter name for model
            if ($this->maxTokens > 0) {
                $tokenParam = $this->getTokenLimitParamName();
                $requestData[$tokenParam] = $this->maxTokens;
            }

            // Add prompt caching
            if ($this->enablePromptCache && $this->provider === 'openai' && !empty($req['cache_key'])) {
                $requestData['prompt_cache_key'] = 'mlcategoryai-' . $req['cache_key'];
            }

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($requestData),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            curl_multi_add_handle($multiHandle, $ch);
            $handles[$index] = [
                'handle' => $ch,
                'id' => $req['id'],
            ];
            $startTimes[$index] = microtime(true);
        }

        // Execute all requests in parallel
        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            if ($running > 0) {
                curl_multi_select($multiHandle, 0.1);
            }
        } while ($running > 0);

        // Collect results
        $results = [];
        foreach ($handles as $index => $handleData) {
            $ch = $handleData['handle'];
            $id = $handleData['id'];
            $timeMs = (int) ((microtime(true) - $startTimes[$index]) * 1000);

            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);

            if ($curlError) {
                $results[$id] = [
                    'success' => false,
                    'content' => '',
                    'error' => 'cURL error: ' . $curlError,
                    'tokens_in' => 0,
                    'tokens_out' => 0,
                    'time_ms' => $timeMs,
                ];
                continue;
            }

            if ($httpCode < 200 || $httpCode >= 300) {
                $errorData = json_decode($response, true);
                $errorMessage = isset($errorData['error']['message'])
                    ? $errorData['error']['message']
                    : 'HTTP error ' . $httpCode;
                $results[$id] = [
                    'success' => false,
                    'content' => '',
                    'error' => $errorMessage,
                    'tokens_in' => 0,
                    'tokens_out' => 0,
                    'time_ms' => $timeMs,
                ];
                continue;
            }

            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $results[$id] = [
                    'success' => false,
                    'content' => '',
                    'error' => 'Failed to parse API response',
                    'tokens_in' => 0,
                    'tokens_out' => 0,
                    'time_ms' => $timeMs,
                ];
                continue;
            }

            $content = isset($decoded['choices'][0]['message']['content'])
                ? trim($decoded['choices'][0]['message']['content'])
                : '';
            $tokensIn = (int) ($decoded['usage']['prompt_tokens'] ?? 0);
            $tokensOut = (int) ($decoded['usage']['completion_tokens'] ?? 0);

            $results[$id] = [
                'success' => !empty($content),
                'content' => $content,
                'error' => empty($content) ? 'Empty response from API' : '',
                'tokens_in' => $tokensIn,
                'tokens_out' => $tokensOut,
                'time_ms' => $timeMs,
            ];
        }

        curl_multi_close($multiHandle);

        return $results;
    }

    /**
     * Test API connection
     *
     * @return bool
     */
    public function testConnection()
    {
        // Check if API key is configured first
        if (empty($this->apiKey)) {
            $this->lastError = 'API key is not configured. Please enter your API key in the settings.';

            return false;
        }

        $result = $this->generate('Say "OK" if you can read this.', 'You are a helpful assistant. Respond with just "OK".');

        if ($result === false) {
            // lastError is already set by generate()
            if (empty($this->lastError)) {
                $this->lastError = 'Unknown error occurred during API test';
            }

            return false;
        }

        if (stripos($result, 'OK') === false) {
            $this->lastError = 'API responded but unexpected response: ' . substr($result, 0, 100);

            return false;
        }

        return true;
    }

    /**
     * Get model name
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }
}
