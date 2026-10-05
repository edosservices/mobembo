<?php

namespace App\Http\Controllers;

use App\Enums\KycStatus;
use App\Models\KycDocument;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KycController extends Controller
{
    public function edit(Request $request)
    {
        return view('kyc.edit', [
            'documents' => $request->user()->kycDocuments()->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'document_type' => ['required', Rule::in(['id_card', 'passport', 'proof_of_address'])],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $path = $request->file('document')->store('kyc/'.$request->user()->id, 'local');

        KycDocument::query()->create([
            'user_id' => $request->user()->id,
            'document_type' => $data['document_type'],
            'path' => $path,
            'status' => KycStatus::Pending,
        ]);

        $request->user()->forceFill(['kyc_status' => KycStatus::Pending])->save();

        return back()->with('success', 'Document envoyé. Il sera examiné par l’administration.');
    }
}
