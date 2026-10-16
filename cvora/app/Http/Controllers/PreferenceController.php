<?php

namespace App\Http\Controllers;

use App\Models\ResumeTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreferenceController extends Controller
{
    public function edit(Request $request): View
    {
        $profile = $request->user()->profile;

        return view('settings.edit', [
            'preferences' => $profile?->preferences() ?? [],
            'templates' => ResumeTemplate::query()->active()->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'template' => ['nullable', 'string', 'max:40'],
            'notify_email' => ['nullable', 'boolean'],
        ]);

        $profile = $request->user()->profile()->firstOrCreate([]);
        $preferences = $profile->preferences();
        $preferences['template'] = $data['template'] ?? '';
        $preferences['notify_email'] = $request->boolean('notify_email');
        $profile->update(['preferences' => $preferences]);

        return back()->with('status', 'Préférences enregistrées.');
    }
}
