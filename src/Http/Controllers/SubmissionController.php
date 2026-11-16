<?php

namespace Jevo\JForms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Jevo\JForms\SubmissionService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubmissionController
{
    public function store(Request $request, string $form, SubmissionService $submissions): JsonResponse
    {
        if (!$request->isJson() && !$request->expectsJson()) {
            throw new HttpException(406, 'JSON requests are required.');
        }
        $input = $request->all();
        $input['_jforms_idempotency'] = $request->header('Idempotency-Key');
        $result = $submissions->submit($form, $input, (string) $request->ip());
        return response()->json(['ok' => true] + $result, 201);
    }
}
