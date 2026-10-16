<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\ResumeTemplate;
use App\Services\Resumes\ResumeContent;
use Illuminate\View\View;

class PublicSiteController extends Controller
{
    public function home(): View
    {
        return view('public.home', [
            'plans' => Plan::query()->publiclyListed()->get(),
            'templates' => ResumeTemplate::query()->active()->get(),
        ]);
    }

    public function templates(): View
    {
        return view('public.templates', [
            'templates' => ResumeTemplate::query()->active()->get(),
        ]);
    }

    public function templatePreview(string $slug): View
    {
        $template = ResumeTemplate::query()->active()->where('slug', $slug)->firstOrFail();

        return view('resumes.print', [
            'content' => ResumeContent::sample(),
            'template' => $template->slug,
            'accent' => '#2563EB',
            'watermark' => 'Exemple de présentation',
        ]);
    }

    public function pricing(): View
    {
        return view('public.pricing', [
            'plans' => Plan::query()->publiclyListed()->get(),
        ]);
    }

    public function page(string $page): View
    {
        abort_unless(view()->exists('public.pages.'.$page), 404);

        return view('public.pages.'.$page);
    }
}
