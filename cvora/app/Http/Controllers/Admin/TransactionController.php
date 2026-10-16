<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(): View
    {
        return view('admin.transactions', [
            'transactions' => PaymentTransaction::query()->with(['user:id,name,email', 'plan:id,name'])->latest()->paginate(30),
        ]);
    }
}
