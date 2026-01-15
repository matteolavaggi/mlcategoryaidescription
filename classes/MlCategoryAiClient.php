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
     * @var int Last tokens used
     */
    protected $lastTokensUsed = 0;

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
     *
     * @return string|false Response text or false on error
     */
    public function generate($prompt, $systemMessage = '')
    {
        $this->lastError = '';
        $this->lastTokensUsed = 0;

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
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
        ];

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

        curl_close($ch);

        if ($curlError) {
            $this->lastError = 'cURL error: ' . $curlError;

            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['error']['message'])
                ? $errorData['error']['message']
                : 'HTTP error ' . $httpCode;
            $this->lastError = $errorMessage;

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
        // Track token usage
        if (isset($response['usage']['total_tokens'])) {
            $this->lastTokensUsed = (int) $response['usage']['total_tokens'];
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
     * Get last tokens used
     *
     * @return int
     */
    public function getLastTokensUsed()
    {
        return $this->lastTokensUsed;
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
