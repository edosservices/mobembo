<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Models\PlatformSetting;
use App\Models\Project;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', [
            'projects' => Project::query()
                ->where('status', ProjectStatus::Active)
                ->latest()
                ->take(6)
                ->get(),
            'disclaimer' => PlatformSetting::current()->legal_disclaimer,
        ]);
    }
}
