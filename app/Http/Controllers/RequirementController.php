<?php

namespace App\Http\Controllers;

use App\Enums\RequirementStatus;
use App\Enums\RequirementType;
use App\Models\Client;
use App\Models\ClientRequirement;
use App\Models\Influencer;
use App\Models\RequirementAttachment;
use App\Models\RequirementMessage;
use App\Models\User;
use App\Models\PortalNotification;
use App\Services\Requirement\ClientRequirementService;
use App\Services\Requirement\RequirementCampaignConverter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequirementController extends Controller
{
    public function __construct(
        protected ClientRequirementService $requirementService,
        protected RequirementCampaignConverter $campaignConverter
    ) {
        $this->middleware('permission:requirements.view')->only(['index', 'show', 'downloadAttachment']);
        $this->middleware('permission:requirements.create')->only(['create', 'store']);
        $this->middleware('permission:requirements.edit')->only(['update', 'updateStatus', 'storeMessage', 'storeAttachment']);
        $this->middleware('permission:requirements.assign')->only(['assign']);
        $this->middleware('permission:requirements.manage|campaigns.create')->only(['convertToCampaign']);
    }

    public function index(Request $request): Response
    {
        $query = ClientRequirement::query()
            ->with(['client:id,name,company_name', 'assignee:id,name'])
            ->withCount('shortlists')
            ->latest();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('requirement_number', 'like', "%{$search}%")
                    ->orWhere('brand_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($type = $request->string('requirement_type')->toString()) {
            $query->where('requirement_type', $type);
        }

        if ($clientId = $request->integer('client_id')) {
            $query->where('client_id', $clientId);
        }

        return Inertia::render('Requirements/Index', [
            'requirements' => $query->paginate($request->integer('per_page', 20))->withQueryString(),
            'filters' => $request->only(['search', 'status', 'requirement_type', 'client_id', 'per_page']),
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'name', 'company_name']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Requirements/Create', [
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'name', 'company_name', 'email']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'prefill_client_id' => $request->integer('client_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRequirement($request);

        $requirement = $this->requirementService->createForStaff($validated);

        return redirect()
            ->route('requirements.show', $requirement)
            ->with('success', 'Requirement created.');
    }

    public function show(ClientRequirement $requirement): Response
    {
        $requirement->load([
            'client:id,name,company_name,email,mobile,contact_person',
            'assignee:id,name,email',
            'deliverables',
            'messages.user:id,name',
            'attachments',
            'shortlists.items.influencer:id,name,default_price,instagram_username',
        ]);

        $shortlists = $requirement->shortlists->map(function ($shortlist) {
            return [
                'id' => $shortlist->id,
                'title' => $shortlist->title,
                'status' => $shortlist->status?->value ?? $shortlist->status,
                'shared_at' => optional($shortlist->shared_at)->toIso8601String(),
                'expires_at' => optional($shortlist->expires_at)->toDateString(),
                'message' => $shortlist->message,
                'items' => $shortlist->items->map(fn ($item) => [
                    'id' => $item->id,
                    'influencer_id' => $item->influencer_id,
                    'display_name' => $item->display_name,
                    'instagram_username' => $item->instagram_username,
                    'instagram_url' => $item->instagram_url,
                    'location' => $item->location,
                    'influencer_type' => $item->influencer_type,
                    'client_price' => $item->client_price,
                    'internal_price' => $item->influencer?->default_price,
                    'description' => $item->description,
                    'status' => $item->status?->value ?? $item->status,
                    'client_remark' => $item->client_remark,
                    'display_order' => $item->display_order,
                    'show_name' => $item->show_name,
                    'show_instagram' => $item->show_instagram,
                    'show_price' => $item->show_price,
                    'show_location' => $item->show_location,
                    'show_type' => $item->show_type,
                    'show_note' => $item->show_note,
                ]),
            ];
        });

        return Inertia::render('Requirements/Show', [
            'requirement' => $requirement,
            'shortlists' => $shortlists,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'influencers' => Influencer::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'instagram_username', 'instagram_url', 'location', 'influencer_type', 'default_price']),
        ]);
    }

    public function update(Request $request, ClientRequirement $requirement): RedirectResponse
    {
        $validated = $this->validateRequirement($request, false);
        $this->requirementService->update($requirement, $validated);

        return back()->with('success', 'Requirement updated.');
    }

    public function updateStatus(Request $request, ClientRequirement $requirement): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(RequirementStatus::options()))],
            'internal_notes' => ['nullable', 'string'],
        ]);

        $previous = $requirement->status?->value ?? $requirement->status;

        $this->requirementService->updateStatus(
            $requirement,
            $validated['status'],
            $validated['internal_notes'] ?? null
        );

        if ($previous !== $validated['status']) {
            $this->notifyClient(
                $requirement,
                'requirement_status',
                'Requirement status updated',
                "{$requirement->requirement_number} is now ".str_replace('_', ' ', $validated['status']),
                route('client.requirements.show', $requirement)
            );
        }

        return back()->with('success', 'Status updated.');
    }

    public function convertToCampaign(Request $request, ClientRequirement $requirement): RedirectResponse
    {
        $validated = $request->validate([
            'shortlist_id' => ['required', 'exists:influencer_shortlists,id'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'exists:influencer_shortlist_items,id'],
            'include_interested' => ['sometimes', 'boolean'],
        ]);

        $allowed = ['selected'];
        if ($request->boolean('include_interested')) {
            $allowed[] = 'interested';
        }

        $campaign = $this->campaignConverter->convert(
            $requirement,
            (int) $validated['shortlist_id'],
            $validated['item_ids'],
            $allowed
        );

        return redirect()
            ->route('campaigns.edit', $campaign)
            ->with('success', 'Campaign created from selected influencers. Review pricing before finalizing.');
    }

    public function assign(Request $request, ClientRequirement $requirement): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $this->requirementService->assign($requirement, $validated['assigned_to'] ?? null);

        return back()->with('success', 'Assignment updated.');
    }

    public function storeMessage(Request $request, ClientRequirement $requirement): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        RequirementMessage::query()->create([
            'client_requirement_id' => $requirement->id,
            'user_id' => $request->user()->id,
            'sender_type' => 'staff',
            'message' => $validated['message'],
        ]);

        $this->notifyClient(
            $requirement,
            'requirement_message',
            'New message on your requirement',
            $requirement->requirement_number.': '.str($validated['message'])->limit(80),
            route('client.requirements.show', $requirement)
        );

        return back()->with('success', 'Message posted.');
    }

    protected function notifyClient(
        ClientRequirement $requirement,
        string $type,
        string $title,
        ?string $body,
        ?string $link
    ): void {
        $requirement->loadMissing('client');
        $userId = $requirement->client?->user_id;

        if (! $userId) {
            return;
        }

        PortalNotification::query()->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'data' => [
                'requirement_id' => $requirement->id,
            ],
        ]);
    }

    public function storeAttachment(Request $request, ClientRequirement $requirement): RedirectResponse
    {
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

        return back()->with('success', 'Attachment uploaded.');
    }

    public function downloadAttachment(RequirementAttachment $attachment): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($attachment->stored_path), 404);

        return Storage::disk('local')->download(
            $attachment->stored_path,
            $attachment->original_filename
        );
    }

    protected function validateRequirement(Request $request, bool $requireClient = true): array
    {
        return $request->validate([
            'client_id' => [$requireClient ? 'required' : 'sometimes', 'exists:clients,id'],
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
            'internal_notes' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', Rule::in(array_keys(RequirementStatus::options()))],
            'deliverables' => ['nullable', 'array'],
            'deliverables.*.platform' => ['nullable', 'string', 'max:50'],
            'deliverables.*.deliverable_type' => ['required_with:deliverables', 'string', 'max:50'],
            'deliverables.*.quantity' => ['nullable', 'integer', 'min:1'],
            'deliverables.*.notes' => ['nullable', 'string'],
        ]);
    }
}
