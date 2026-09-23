<?php

namespace App\Http\Controllers;

use App\Http\Requests\InfluencerStoreRequest;
use App\Http\Requests\InfluencerUpdateRequest;
use App\Models\Influencer;
use App\Models\UserTablePreference;
use App\Services\Influencer\DuplicateDetectionService;
use App\Services\Influencer\InfluencerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InfluencerController extends Controller
{
    public function __construct(
        protected InfluencerService $influencerService,
        protected DuplicateDetectionService $duplicateDetection
    ) {
        $this->middleware('permission:influencers.view')->only(['index', 'show']);
        $this->middleware('permission:influencers.create')->only(['create', 'store']);
        $this->middleware('permission:influencers.edit')->only(['edit', 'update', 'preferences']);
        $this->middleware('permission:influencers.delete')->only(['destroy']);
    }

    public function index(Request $request): Response
    {
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['name', 'instagram_username', 'mobile', 'email', 'location', 'influencer_type', 'default_price', 'status', 'created_at'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $query = Influencer::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('instagram_username', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('influencer_type')) {
            $query->where('influencer_type', $type);
        }

        if ($location = $request->get('location')) {
            $query->where('location', $location);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        } else {
            $query->where('status', '!=', 'archived');
        }

        if ($request->filled('price_min')) {
            $query->where('default_price', '>=', $request->get('price_min'));
        }

        if ($request->filled('price_max')) {
            $query->where('default_price', '<=', $request->get('price_max'));
        }

        $influencers = $query->orderBy($sort, $direction)
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        $preferences = UserTablePreference::query()
            ->where('user_id', $request->user()->id)
            ->where('table_key', 'influencers')
            ->first();

        return Inertia::render('Influencers/Index', [
            'influencers' => $influencers,
            'filters' => $request->only(['search', 'influencer_type', 'location', 'status', 'price_min', 'price_max', 'sort', 'direction', 'per_page']),
            'preferences' => $preferences?->columns,
            'locations' => Influencer::query()->whereNotNull('location')->distinct()->orderBy('location')->pluck('location'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Influencers/Create');
    }

    public function store(InfluencerStoreRequest $request): RedirectResponse
    {
        $influencer = $this->influencerService->create(
            $request->validated(),
            (bool) $request->boolean('force')
        );

        return redirect()
            ->route('influencers.show', $influencer)
            ->with('success', 'Influencer created successfully.');
    }

    public function show(Request $request, Influencer $influencer): Response
    {
        $influencer->load([
            'user:id,name,email,username',
            'campaignInfluencers.campaign.client',
            'activities' => fn ($q) => $q->latest('activity_at')->limit(20),
            'followUps' => fn ($q) => $q->latest('follow_up_date')->limit(10),
            'notes' => fn ($q) => $q->latest()->limit(20),
            'payments' => fn ($q) => $q->latest('payment_date')->limit(20),
        ]);

        return Inertia::render('Influencers/Show', [
            'influencer' => $influencer,
            'canManageLogin' => $request->user()?->can('influencers.manage_login')
                || $request->user()?->can('influencers.edit'),
        ]);
    }

    public function edit(Influencer $influencer): Response
    {
        return Inertia::render('Influencers/Edit', [
            'influencer' => $influencer,
        ]);
    }

    public function update(InfluencerUpdateRequest $request, Influencer $influencer): RedirectResponse
    {
        $this->influencerService->update(
            $influencer,
            $request->validated(),
            (bool) $request->boolean('force')
        );

        return redirect()
            ->route('influencers.show', $influencer)
            ->with('success', 'Influencer updated successfully.');
    }

    public function destroy(Influencer $influencer): RedirectResponse
    {
        try {
            $this->influencerService->delete($influencer);
        } catch (ValidationException $e) {
            return redirect()
                ->route('influencers.index')
                ->with('error', collect($e->errors())->flatten()->first() ?: 'Unable to delete influencer.');
        }

        return redirect()
            ->route('influencers.index')
            ->with('success', 'Influencer deleted successfully.');
    }

    public function checkDuplicate(Request $request): JsonResponse
    {
        $duplicates = $this->duplicateDetection->findDuplicates(
            $request->only(['instagram_username', 'instagram_url', 'email', 'mobile']),
            $request->integer('exclude_id') ?: null
        );

        return response()->json([
            'has_duplicates' => $duplicates->isNotEmpty(),
            'duplicates' => $duplicates,
        ]);
    }

    public function preferences(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'table_key' => ['required', 'string', 'max:100'],
            'columns' => ['required', 'array'],
        ]);

        $preference = UserTablePreference::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'table_key' => $request->input('table_key'),
            ],
            ['columns' => $request->input('columns')]
        );

        if ($request->wantsJson()) {
            return response()->json($preference);
        }

        return back()->with('success', 'Table preferences saved.');
    }
}
