<?php

namespace Jevo\JForms;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubmissionService
{
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
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ];
        DB::table('jforms_responses')->insert($response);

        return [
            'message' => (string) ($options['success_message'] ?? 'Дякуємо! Дані надіслано.'),
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

}
