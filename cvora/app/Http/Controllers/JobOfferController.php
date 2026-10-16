<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobOfferController extends Controller
{
    public function index(Request $request): View
    {
        return view('offers.index', [
            'offers' => $request->user()->jobOffers()->latest()->paginate(15),
            'resumes' => $request->user()->resumes()->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'company' => ['nullable', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:8000'],
        ], [
            'title.required' => 'Indiquez le poste.',
            'description.required' => 'Collez le texte de l’offre.',
        ]);

        $request->user()->jobOffers()->create($data);

        return redirect()->route('offers.index')->with('status', 'Offre enregistrée. Elle n’a pas modifié votre CV.');
    }

    public function destroy(JobOffer $offer): RedirectResponse
    {
        $this->authorize('delete', $offer);
        $offer->delete();

        return redirect()->route('offers.index')->with('status', 'Offre supprimée.');
    }
}
