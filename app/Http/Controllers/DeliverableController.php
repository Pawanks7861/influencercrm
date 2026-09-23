<?php

namespace App\Http\Controllers;

use App\Enums\DeliverableStatus;
use App\Http\Requests\DeliverableStoreRequest;
use App\Models\Deliverable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliverableController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:deliverables.manage');
    }

    public function store(DeliverableStoreRequest $request): RedirectResponse
    {
        Deliverable::create($request->validated());

        return back()->with('success', 'Deliverable created.');
    }

    public function update(Request $request, Deliverable $deliverable): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'required', 'string'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'content_url' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::enum(DeliverableStatus::class)],
            'remarks' => ['nullable', 'string'],
        ]);

        if (($validated['status'] ?? null) === DeliverableStatus::Submitted->value) {
            $validated['submitted_at'] = now();
        }

        if (($validated['status'] ?? null) === DeliverableStatus::Approved->value) {
            $validated['approved_at'] = now();
        }

        if (($validated['status'] ?? null) === DeliverableStatus::Posted->value) {
            $validated['posted_at'] = now();
        }

        $deliverable->update($validated);

        return back()->with('success', 'Deliverable updated.');
    }

    public function destroy(Deliverable $deliverable): RedirectResponse
    {
        $deliverable->delete();

        return back()->with('success', 'Deliverable deleted.');
    }
}
