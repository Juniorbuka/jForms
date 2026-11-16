<?php

namespace Jevo\JForms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Jevo\JForms\SubmissionService;

class SubmissionController
{
    public function store(Request $request, string $form, SubmissionService $submissions): JsonResponse
    {
        abort_unless($request->isJson() || $request->expectsJson(), 406, 'JSON requests are required.');
        $input = $request->all();
        $input['_jforms_idempotency'] = $request->header('Idempotency-Key');
        $result = $submissions->submit($form, $input, (string) $request->ip());
        return response()->json(['ok' => true] + $result, 201);
    }
}
