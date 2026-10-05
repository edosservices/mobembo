<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralCommission;

class ReferralController extends Controller
{
    public function index()
    {
        $commissions = ReferralCommission::query()->with(['referrer', 'referred'])->latest()->paginate(30);

        return view('admin.referrals.index', compact('commissions'));
    }
}
