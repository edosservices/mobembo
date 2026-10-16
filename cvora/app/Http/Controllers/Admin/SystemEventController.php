<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemEvent;
use Illuminate\View\View;

class SystemEventController extends Controller
{
    public function index(): View
    {
        return view('admin.events', [
            'events' => SystemEvent::query()->latest('id')->paginate(40),
        ]);
    }
}
