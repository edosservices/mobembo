<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResumeTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.templates', [
            'templates' => ResumeTemplate::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, ResumeTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
            'is_premium' => ['required', 'boolean'],
        ]);

        $template->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'is_active' => $request->boolean('is_active'),
            'is_premium' => $request->boolean('is_premium'),
        ]);

        return back()->with('status', 'Modèle mis à jour.');
    }
}
