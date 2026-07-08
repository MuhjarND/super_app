<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ChatbotGatewayService
{
    public function validateMagicToken(string $token): array
    {
        $validateUrl = config('chatbot.validate_url');
        $internalApiKey = config('chatbot.internal_api_key');
        $applicationCode = config('chatbot.application_code', 'papeda');

        if (!$validateUrl || !$internalApiKey) {
            $this->logInvalid($token, 'missing_config');

            return ['valid' => false, 'reason' => 'missing_config'];
        }

        try {
            $client = new Client(['timeout' => 5]);
            $response = $client->post($validateUrl, [
                'headers' => [
                    'X-INTERNAL-API-KEY' => $internalApiKey,
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'token' => $token,
                    'application_code' => $applicationCode,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logInvalid($token, 'gateway_exception', [
                'exception' => get_class($e),
            ]);

            return ['valid' => false, 'reason' => 'gateway_exception'];
        }

        $statusCode = (int) $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            $this->logInvalid($token, 'gateway_http_error', [
                'status' => $statusCode,
            ]);

            return ['valid' => false, 'reason' => 'gateway_http_error'];
        }

        $data = json_decode((string) $response->getBody(), true) ?: [];
        $appUserId = $this->extractAppUserId($data);
        $isValid = $this->isValidResponse($data);

        if (!$isValid || empty($appUserId)) {
            $this->logInvalid($token, 'gateway_invalid_payload', [
                'valid_flag' => $isValid,
                'has_app_user_id' => !empty($appUserId),
                'response_keys' => array_keys($data),
            ]);

            return ['valid' => false, 'reason' => 'gateway_invalid_payload'];
        }

        Log::info('Chatbot magic login validation succeeded', [
            'token_hash' => substr(hash('sha256', $token), 0, 16),
            'app_user_id_hash' => substr(hash('sha256', (string) $appUserId), 0, 16),
        ]);

        return [
            'valid' => true,
            'app_user_id' => (string) $appUserId,
        ];
    }

    private function isValidResponse(array $data): bool
    {
        $valid = data_get($data, 'valid');
        $success = data_get($data, 'success');
        $status = strtolower((string) data_get($data, 'status', ''));

        return $valid === true
            || $valid === 1
            || $valid === '1'
            || strtolower((string) $valid) === 'true'
            || $success === true
            || $success === 1
            || $success === '1'
            || strtolower((string) $success) === 'true'
            || in_array($status, ['valid', 'success', 'ok'], true);
    }

    private function extractAppUserId(array $data)
    {
        $paths = [
            'app_user_id',
            'data.app_user_id',
            'user.app_user_id',
            'data.user.app_user_id',
            'user_id',
            'data.user_id',
            'id',
            'data.id',
            'email',
            'data.email',
        ];

        foreach ($paths as $path) {
            $value = data_get($data, $path);

            if (!empty($value)) {
                return $value;
            }
        }

        return null;
    }

    private function logInvalid(string $token, string $reason, array $context = []): void
    {
        Log::warning('Chatbot magic login validation failed', array_merge([
            'reason' => $reason,
            'token_hash' => substr(hash('sha256', $token), 0, 16),
        ], $context));
    }
}
