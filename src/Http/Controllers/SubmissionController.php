<?php

namespace Jevo\JForms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Jevo\JForms\SubmissionService;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubmissionController
{
    public function asset(): \Illuminate\Http\Response
    {
        $path = dirname(__DIR__, 3) . '/resources/js/jforms.js';
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new HttpException(404);
        }

        return response($contents, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function token(Request $request, string $form, SubmissionService $submissions): JsonResponse
    {
        $options = $submissions->form($form);
        $ttl = min(86400, max(60, (int) ($options['token_ttl_seconds'] ?? 3600)));
        $token = $submissions->issueToken($form, $ttl);
        $cookieName = $submissions->cookieNameForForm($form);

        return response()->json(['token' => $token])
            ->cookie(new Cookie($cookieName, $token, time() + $ttl, '/', null, $request->isSecure(), true, false, 'lax'));
    }

    public function store(Request $request, string $form, SubmissionService $submissions): JsonResponse
    {
        if (!$request->isJson() && !$request->expectsJson()) {
            throw new HttpException(406, 'JSON requests are required.');
        }
        $input = $request->all();
        $submissionToken = $input['_jforms_token'] ?? null;
        if (is_string($submissionToken)) {
            $input['_jforms_cookie_token'] = $request->cookie($submissions->cookieNameForForm($form));
        }
        $result = $submissions->submit($form, $input, (string) $request->ip());
        $options = $submissions->form($form);
        $ttl = min(86400, max(60, (int) ($options['token_ttl_seconds'] ?? 3600)));
        $nextToken = (string) $result['next_token'];
        $nextCookieName = $submissions->cookieNameForForm($form);
        $response = response()->json(['ok' => true] + $result, 201);

        $response->cookie(new Cookie($nextCookieName, $nextToken, time() + $ttl, '/', null, $request->isSecure(), true, false, 'lax'));

        return $response;
    }
}
