<?php

namespace Jevo\JForms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Jevo\JForms\SubmissionService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubmissionController
{
    public function token(Request $request, string $form, SubmissionService $submissions): JsonResponse
    {
        $submissions->form($form);

        $limitKey = 'jforms:token:' . $form . ':' . hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($limitKey, 60)) {
            throw new HttpException(429, 'Too many token requests. Please wait a moment.');
        }
        RateLimiter::hit($limitKey, 60);

        return response()->json(['token' => $submissions->issueToken($form)], 200, [
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function store(Request $request, string $form, SubmissionService $submissions): JsonResponse|RedirectResponse
    {
        $expectsJson = $request->isJson() || $request->expectsJson();
        $input = $request->all();
        $result = $submissions->submit($form, $input, (string) $request->ip());

        if ($expectsJson) {
            return response()->json(['ok' => true] + $result, 201);
        }

        return redirect()->back()->with('jforms_success', $result['message']);
    }
}
