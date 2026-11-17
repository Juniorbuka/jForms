<?php

namespace Jevo\JForms;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubmissionService
{
    /** Issue a random, signed token bound to a form and its expiration time. */
    public function issueToken(string $formKey, int $ttlSeconds = 3600): string
    {
        $issuedAt = time();
        $token = $this->uuidV4();
        $signature = hash_hmac('sha256', $this->tokenPayload($formKey, $token, $issuedAt), $this->applicationKey());

        return $token . '.' . $issuedAt . '.' . $signature;
    }

    public function cookieNameForForm(string $formKey): string
    {
        return 'jforms_token_' . substr(hash('sha256', $formKey), 0, 24);
    }

    /** @return array<string, mixed> */
    public function submit(string $formKey, array $input, string $ip): array
    {
        $form = $this->form($formKey);
        return $this->submitDefinition(
            $formKey,
            (string) ($form['name'] ?? $formKey),
            $input,
            (array) ($form['rules'] ?? []),
            $ip,
            $form
        );
    }

    /**
     * Let a site controller define a form without relying on package config.
     * Rules must be application code, never request data.
     *
     * @param array<string, mixed> $input
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function submitDefinition(
        string $formKey,
        string $formName,
        array $input,
        array $rules,
        string $ip,
        array $options = []
    ): array {
        $limitKey = 'jforms:v2:' . $formKey . ':' . hash('sha256', $ip);
        $maxAttempts = max(1, (int) ($options['rate_limit']['attempts'] ?? 10));
        $decaySeconds = max(60, (int) ($options['rate_limit']['decay_seconds'] ?? 60));

        if (RateLimiter::tooManyAttempts($limitKey, $maxAttempts)) {
            throw new HttpException(429, 'Too many submissions. Please try again later.');
        }
        RateLimiter::hit($limitKey, $decaySeconds);

        $maxPayloadBytes = max(1024, (int) ($options['max_payload_bytes'] ?? 16384));
        $encodedInput = json_encode($input);
        if ($encodedInput === false || strlen($encodedInput) > $maxPayloadBytes) {
            throw new HttpException(413, 'The submitted form is too large.');
        }

        $honeypot = (string) ($options['honeypot'] ?? 'website');
        if ($honeypot !== '' && (!array_key_exists($honeypot, $input) || (is_scalar($input[$honeypot]) && trim((string) $input[$honeypot]) !== ''))) {
            throw new HttpException(422, 'Unable to process this submission.');
        }
        if ($honeypot !== '' && !is_scalar($input[$honeypot])) {
            throw new HttpException(422, 'Unable to process this submission.');
        }

        $submissionToken = $input['_jforms_token'] ?? null;
        if (!is_string($submissionToken) || !preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12})\.([0-9]{10})\.([a-f0-9]{64})$/i', $submissionToken, $tokenParts)) {
            throw new HttpException(422, 'A valid form token is required.');
        }
        $idempotencyKey = $tokenParts[1];
        $issuedAt = (int) $tokenParts[2];
        $ttlSeconds = min(86400, max(60, (int) ($options['token_ttl_seconds'] ?? 3600)));
        $expectedSignature = hash_hmac('sha256', $this->tokenPayload($formKey, $idempotencyKey, $issuedAt), $this->applicationKey());
        if ($issuedAt > time() || time() - $issuedAt > $ttlSeconds || !hash_equals($expectedSignature, strtolower($tokenParts[3]))) {
            throw new HttpException(419, 'The form token is invalid or expired. Reload the form and try again.');
        }
        $cookieToken = $input['_jforms_cookie_token'] ?? null;
        if (!is_string($cookieToken) || !hash_equals($submissionToken, $cookieToken)) {
            throw new HttpException(419, 'The form token does not match its cookie. Reload the form and try again.');
        }

        $usedTokenKey = 'jforms:used-token:' . $formKey . ':' . hash('sha256', $idempotencyKey);
        if (Cache::has($usedTokenKey) || DB::table('jforms_responses')->where('form_key', $formKey)->where('idempotency_key', $idempotencyKey)->exists()) {
            throw new HttpException(419, 'This form token has already been used. Reload the form and try again.');
        }

        $allowed = array_keys($rules);
        $submitted = array_intersect_key($input, array_flip($allowed));
        $validator = Validator::make($submitted, $rules, (array) ($options['messages'] ?? []));
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        $response = [
            'form_key' => $formKey,
            'form_name' => $formName,
            'idempotency_key' => $idempotencyKey,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ];
        $duplicate = false;
        try {
            DB::table('jforms_responses')->insert($response);
        } catch (QueryException $exception) {
            $duplicate = DB::table('jforms_responses')
                ->where('form_key', $formKey)
                ->where('idempotency_key', $idempotencyKey)
                ->exists();
            if ($duplicate) {
                Cache::put($usedTokenKey, true, $ttlSeconds);
                throw new HttpException(419, 'This form token has already been used. Reload the form and try again.');
            }
            throw $exception;
        }
        Cache::put($usedTokenKey, true, $ttlSeconds);

        return [
            'message' => (string) ($options['success_message'] ?? 'Дякуємо! Дані надіслано.'),
            'duplicate' => $duplicate,
            'next_token' => $this->issueToken($formKey, (int) ($options['token_ttl_seconds'] ?? 3600)),
        ];
    }

    /** @return array<string, mixed> */
    public function form(string $formKey): array
    {
        $customConfigPath = EVO_CORE_PATH . 'custom/config/jevo/jforms.php';
        $legacyConfigPath = EVO_CORE_PATH . 'custom/config/jforms.php';
        $forms = is_file($customConfigPath)
            ? config('jevo.jforms.forms', [])
            : (is_file($legacyConfigPath)
                ? config('jforms.forms', config('jevo.jforms.forms', []))
                : config('jevo.jforms.forms', []));
        if (!isset($forms[$formKey]) || !is_array($forms[$formKey])) {
            throw new HttpException(404);
        }
        return $forms[$formKey];
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }

    private function tokenPayload(string $formKey, string $tokenId, int $issuedAt): string
    {
        return implode('|', [$formKey, $tokenId, (string) $issuedAt]);
    }

    private function applicationKey(): string
    {
        $key = (string) config('app.key', '');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            $key = $decoded === false ? '' : $decoded;
        }
        if ($key === '') {
            throw new HttpException(500, 'The application key is not configured.');
        }

        return $key;
    }
}
