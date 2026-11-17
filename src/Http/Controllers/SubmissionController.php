<?php

namespace Jevo\JForms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Jevo\JForms\SubmissionService;

class SubmissionController
{
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
