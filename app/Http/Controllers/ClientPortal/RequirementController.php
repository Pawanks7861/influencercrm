<?php

namespace App\Http\Controllers\ClientPortal;

use App\Enums\RequirementType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Client\ClientRequirementResource;
use App\Models\Client;
use App\Models\ClientRequirement;
use App\Models\RequirementAttachment;
use App\Models\RequirementMessage;
use App\Services\Requirement\ClientRequirementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequirementController extends Controller
{
    public function __construct(protected ClientRequirementService $requirementService)
    {
    }

    public function index(): Response
    {
        $client = $this->client();

        $requirements = ClientRequirement::query()
            ->where('client_id', $client->id)
            ->withCount('shortlists')
            ->latest()
            ->paginate(20);

        return Inertia::render('ClientPortal/Requirements/Index', [
            'requirements' => $requirements->through(
                fn (ClientRequirement $requirement) => (new ClientRequirementResource($requirement))->resolve()
            ),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('ClientPortal/Requirements/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requirement_type' => ['required', Rule::in(array_keys(RequirementType::options()))],
            'title' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0'],
            'preferred_start_date' => ['nullable', 'date'],
            'preferred_end_date' => ['nullable', 'date', 'after_or_equal:preferred_start_date'],
            'expected_posting_date' => ['nullable', 'date'],
            'preferred_location' => ['nullable', 'string', 'max:255'],
            'preferred_category' => ['nullable', 'string', 'max:50'],
            'preferred_platforms' => ['nullable', 'array'],
            'preferred_platforms.*' => ['string'],
            'influencers_required' => ['nullable', 'integer', 'min:1'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'services_required' => ['nullable', 'array'],
            'services_required.*' => ['string'],
            'duration' => ['nullable', 'string', 'max:255'],
            'posting_frequency' => ['nullable', 'string', 'max:255'],
            'goals' => ['nullable', 'string'],
            'additional_instructions' => ['nullable', 'string'],
            'client_notes' => ['nullable', 'string'],
            'deliverables' => ['nullable', 'array'],
            'deliverables.*.platform' => ['nullable', 'string', 'max:50'],
            'deliverables.*.deliverable_type' => ['required_with:deliverables', 'string', 'max:50'],
            'deliverables.*.quantity' => ['nullable', 'integer', 'min:1'],
            'deliverables.*.notes' => ['nullable', 'string'],
        ]);

        // Never accept client_id from request — bind from auth.
        unset($validated['client_id']);

        $requirement = $this->requirementService->createForClient($this->client(), $validated);

        return redirect()
            ->route('client.requirements.show', $requirement)
            ->with('success', 'Requirement submitted.');
    }

    public function show(ClientRequirement $requirement): Response
    {
        $this->authorizeRequirement($requirement);

        $requirement->load(['deliverables', 'messages.user:id,name', 'attachments']);

        return Inertia::render('ClientPortal/Requirements/Show', [
            'requirement' => (new ClientRequirementResource($requirement))->resolve(),
        ]);
    }

    public function storeMessage(Request $request, ClientRequirement $requirement): RedirectResponse
    {
        $this->authorizeRequirement($requirement);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        RequirementMessage::query()->create([
            'client_requirement_id' => $requirement->id,
            'user_id' => $request->user()->id,
            'sender_type' => 'client',
            'message' => $validated['message'],
        ]);

        return back()->with('success', 'Message sent.');
    }

    public function storeAttachment(Request $request, ClientRequirement $requirement): RedirectResponse
    {
        $this->authorizeRequirement($requirement);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $file = $validated['file'];
        $path = $file->store("requirement-attachments/{$requirement->id}", 'local');

        RequirementAttachment::query()->create([
            'client_requirement_id' => $requirement->id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'File uploaded.');
    }

    public function downloadAttachment(RequirementAttachment $attachment): StreamedResponse
    {
        $attachment->loadMissing('requirement');
        $this->authorizeRequirement($attachment->requirement);

        abort_unless(Storage::disk('local')->exists($attachment->stored_path), 404);

        return Storage::disk('local')->download(
            $attachment->stored_path,
            $attachment->original_filename
        );
    }

    protected function authorizeRequirement(ClientRequirement $requirement): void
    {
        abort_unless($requirement->client_id === $this->client()->id, 404);
    }

    protected function client(): Client
    {
        return Auth::user()->client;
    }
}
