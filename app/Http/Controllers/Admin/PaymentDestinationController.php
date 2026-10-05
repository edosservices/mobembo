<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\PaymentDestination;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentDestinationController extends Controller
{
    public function store(Request $request, AuditService $audit)
    {
        $data = $this->validated($request);
        $destination = PaymentDestination::query()->create($data);

        $audit->record($request->user(), null, 'payment_destination_created', null, null, null, 'Numéro de réception ajouté', [
            'id' => $destination->id,
            'method' => $destination->method->value,
            'holder_name' => $destination->holder_name,
            'phone' => $destination->phone,
        ]);

        return back()->with('success', 'Numéro ajouté. Il apparaît dans la fenêtre de dépôt du client.');
    }

    public function update(Request $request, PaymentDestination $destination, AuditService $audit)
    {
        $before = $destination->only(['method', 'holder_name', 'phone']);
        $destination->fill($this->validated($request))->save();

        $audit->record($request->user(), null, 'payment_destination_updated', null, null, null, 'Numéro de réception modifié', [
            'id' => $destination->id,
            'before' => $before,
            'after' => $destination->only(['method', 'holder_name', 'phone']),
        ]);

        return back()->with('success', 'Numéro mis à jour.');
    }

    public function destroy(Request $request, PaymentDestination $destination, AuditService $audit)
    {
        $audit->record($request->user(), null, 'payment_destination_removed', null, null, null, 'Numéro de réception retiré', [
            'id' => $destination->id,
            'method' => $destination->method->value,
            'phone' => $destination->phone,
        ]);

        $destination->delete();

        return back()->with('success', 'Numéro retiré. Les dépôts déjà envoyés ne sont pas modifiés.');
    }

    /**
     * @return array{method: string, holder_name: string, phone: string}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'holder_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $data['holder_name'] = trim($data['holder_name']);
        $data['phone'] = trim($data['phone']);

        return $data;
    }
}
