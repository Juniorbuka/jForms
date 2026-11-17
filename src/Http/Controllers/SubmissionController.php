<?php

namespace Jevo\JForms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Jevo\JForms\SubmissionService;
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

    public function store(Request $request, string $form, SubmissionService $submissions): JsonResponse
    {
        if (!$request->isJson() && !$request->expectsJson()) {
            throw new HttpException(406, 'JSON requests are required.');
        }
        $input = $request->all();
        $result = $submissions->submit($form, $input, (string) $request->ip());
        return response()->json(['ok' => true] + $result, 201);
    }
}
