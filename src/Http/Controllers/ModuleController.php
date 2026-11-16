<?php

namespace Jevo\JForms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ModuleController
{
    public function index()
    {
        $responses = DB::table('jforms_responses')
            ->select('form_key', 'form_name', DB::raw('COUNT(*) as total'), DB::raw('MAX(created_at) as latest'))
            ->groupBy('form_key', 'form_name')
            ->orderBy('form_name')
            ->get();

        return view('jForms::module.forms_list', compact('responses'));
    }

    public function show(string $form)
    {
        $responses = DB::table('jforms_responses')
            ->where('form_key', $form)
            ->orderByDesc('id')
            ->paginate(50);

        return view('jForms::module.form_results', compact('form', 'responses'));
    }

    public function response(string $form, int $response)
    {
        $result = DB::table('jforms_responses')
            ->where('form_key', $form)
            ->where('id', $response)
            ->first();

        if (!$result) {
            throw new HttpException(404);
        }
        $fields = json_decode($result->data, true) ?: [];

        return view('jForms::module.form_result', compact('form', 'result', 'fields'));
    }

    public function destroy(Request $request, int $id)
    {
        DB::table('jforms_responses')->where('id', $id)->delete();
        return redirect()->back()->with('status', __('jForms::module.deleted'));
    }
}
